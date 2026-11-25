<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
  (https://www.devincentiis.it)
  <https://gazie.sourceforge.net>
  Questo file: Copyright (C) 2026 Domenico Seccia - Assistenzacassa (https://assistenzacassa.it)
  --------------------------------------------------------------------------
  Questo programma e` free software;   e` lecito redistribuirlo  e/o
  modificarlo secondo i  termini della Licenza Pubblica Generica GNU
  come e` pubblicata dalla Free Software Foundation; o la versione 2
  della licenza o (a propria scelta) una versione successiva.

  Questo programma  e` distribuito nella speranza  che sia utile, ma
  SENZA   ALCUNA GARANZIA; senza  neppure  la  garanzia implicita di
  NEGOZIABILITA` o di  APPLICABILITA` PER UN  PARTICOLARE SCOPO.  Si
  veda la Licenza Pubblica Generica GNU per avere maggiori dettagli.

  Ognuno dovrebbe avere   ricevuto una copia  della Licenza Pubblica
  Generica GNU insieme a   questo programma; in caso  contrario,  si
  scriva   alla   Free  Software Foundation, 51 Franklin Street,
  Fifth Floor Boston, MA 02110-1335 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
/*
  Listino di gruppo: i clienti dello stesso gruppo (clfoco.id_customer_group)
  condividono i prezzi netti per articolo (tabella customer_group_prezzi).
  Si attiva in Configurazione avanzata azienda con listino_gruppo = 1.
  Con listino_aggiorna_da_documenti = 1 (vale solo insieme a listino_gruppo)
  il prezzo dei righi di DDT e fatture immediate nuovi diventa il nuovo
  prezzo netto del gruppo, o del cliente se non e' in un gruppo.
*/

// legge una volta per pagina un interruttore di company_config
function listino_gruppo_config($var) {
  global $gTables;
  static $valori = array();
  if (!isset($valori[$var])) {
    $r = gaz_dbi_get_row($gTables['company_config'], 'var', $var);
    $valori[$var] = ($r && trim($r['val']) == '1');
  }
  return $valori[$var];
}

// true se l'azienda usa il listino di gruppo
function listino_gruppo_attivo() {
  return listino_gruppo_config('listino_gruppo');
}

// true se i documenti nuovi aggiornano i prezzi netti
function listino_aggiorna_da_documenti_attivo() {
  return listino_gruppo_attivo() && listino_gruppo_config('listino_aggiorna_da_documenti');
}

// gruppo del cliente (0 se non ne ha)
function listino_gruppo_del_cliente($codcli) {
  global $gTables;
  if (intval($codcli) <= 0) {
    return 0;
  }
  return intval(gaz_dbi_get_single_value($gTables['clfoco'], 'id_customer_group', "codice = " . intval($codcli)));
}

// prezzo netto del gruppo per l'articolo (0 se manca)
function listino_gruppo_prezzo($id_gruppo, $codart) {
  global $gTables;
  $id_gruppo = intval($id_gruppo);
  if ($id_gruppo <= 0 || strlen(trim($codart)) < 1) {
    return 0;
  }
  $r = gaz_dbi_query("SELECT prezzo_netto FROM " . $gTables['customer_group_prezzi'] . " WHERE id_customer_group = " . $id_gruppo . " AND codart = '" . gaz_dbi_real_escape_string($codart) . "' LIMIT 1");
  $row = gaz_dbi_fetch_row($r);
  return $row ? floatval($row[0]) : 0;
}

// inserisce o aggiorna il prezzo netto di un gruppo; true se inserito, false se aggiornato
function listino_gruppo_salva($id_gruppo, $codart, $prezzo_netto) {
  global $gTables;
  $id_gruppo = intval($id_gruppo);
  $codart_sql = gaz_dbi_real_escape_string($codart);
  $where = "id_customer_group = " . $id_gruppo . " AND codart = '" . $codart_sql . "'";
  if (gaz_dbi_record_count($gTables['customer_group_prezzi'], $where) == 0) {
    gaz_dbi_query("INSERT INTO " . $gTables['customer_group_prezzi'] . " (id_customer_group, codart, prezzo_netto) VALUES (" . $id_gruppo . ", '" . $codart_sql . "', " . floatval($prezzo_netto) . ")");
    return true;
  }
  gaz_dbi_query("UPDATE " . $gTables['customer_group_prezzi'] . " SET prezzo_netto = " . floatval($prezzo_netto) . " WHERE " . $where);
  return false;
}

// cancella il prezzo netto di un gruppo; restituisce le righe cancellate
function listino_gruppo_cancella($id_gruppo, $codart) {
  global $gTables;
  return gaz_dbi_query("DELETE FROM " . $gTables['customer_group_prezzi'] . " WHERE id_customer_group = " . intval($id_gruppo) . " AND codart = '" . gaz_dbi_real_escape_string($codart) . "'", true);
}

/*
  Salvataggio di un rigo di documento nuovo: il prezzo netto del rigo
  (prezzo meno sconto del rigo) diventa quello che sara' proposto la volta dopo,
  nello stesso ordine della ricerca del prezzo (venditCalc::trovaPrezzoNetto_Sconto):
  - riga cliente/articolo, se il cliente ne ha gia' una (prezzo netto o sconto);
  - altrimenti prezzo del gruppo, se il cliente e' in un gruppo;
  - altrimenti nuova riga cliente/articolo con il prezzo netto.
*/
function listino_aggiorna_da_rigo($codcli, $codart, $prelis, $sconto_rigo, $decimali) {
  global $gTables;
  $codcli = intval($codcli);
  $netto = round(floatval($prelis) * (1 - floatval($sconto_rigo) / 100), intval($decimali));
  if ($codcli <= 0 || strlen(trim($codart)) < 1 || $netto <= 0) {
    return;
  }
  $tabella = $gTables['sconti_articoli'];
  $where_cliente = "clfoco = " . $codcli . " AND codart = '" . gaz_dbi_real_escape_string($codart) . "'";
  if (gaz_dbi_record_count($tabella, $where_cliente) > 0) {
    gaz_dbi_query("UPDATE " . $tabella . " SET prezzo_netto = " . $netto . " WHERE " . $where_cliente . " AND (prezzo_netto IS NULL OR prezzo_netto <> " . $netto . ")");
    return;
  }
  $id_gruppo = listino_gruppo_del_cliente($codcli);
  if ($id_gruppo > 0) {
    if (abs(listino_gruppo_prezzo($id_gruppo, $codart) - $netto) > 0.000001) {
      listino_gruppo_salva($id_gruppo, $codart, $netto);
    }
    return;
  }
  gaz_dbi_query("INSERT INTO " . $tabella . " (clfoco, codart, sconto, prezzo_netto) VALUES (" . $codcli . ", '" . gaz_dbi_real_escape_string($codart) . "', 0, " . $netto . ")");
}
?>
