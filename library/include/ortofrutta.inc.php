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
  Gestione ortofrutta: provenienza, calibro e categoria di articoli e righi
  dei documenti di vendita (dati richiesti dalle norme UE di commercializzazione).
  Si attiva in Configurazione avanzata azienda con gestione_ortofrutta = 1.
*/

// true se l'azienda ha attivato la gestione ortofrutta
function ortofrutta_attiva() {
  global $gTables;
  static $attiva = null;
  if ($attiva === null) {
    $r = gaz_dbi_get_row($gTables['company_config'], 'var', 'gestione_ortofrutta');
    $attiva = ($r && trim($r['val']) == '1');
  }
  return $attiva;
}

// elenchi di provenienze, calibri e categorie, letti una sola volta
function ortofrutta_elenchi() {
  global $gTables;
  static $el = null;
  if ($el === null) {
    $el = ['provenienza' => [], 'calibro' => [], 'categoria' => [], 'paese' => [], 'descri_categoria' => []];
    $rs = gaz_dbi_dyn_query('codice, descrizione', $gTables['provenienze'], '1', 'descrizione');
    while ($r = gaz_dbi_fetch_array($rs)) {
      $el['provenienza'][$r['codice']] = $r['codice'] . ' - ' . $r['descrizione'];
      $el['paese'][$r['codice']] = $r['descrizione'];
    }
    // lo stesso calibro puo' valere per piu' prodotti: una sola voce con i prodotti accanto
    $prodotti = [];
    $rs = gaz_dbi_dyn_query('prodotto, codice_calibro', $gTables['calibri_ortofrutta'], '1', 'codice_calibro, prodotto');
    while ($r = gaz_dbi_fetch_array($rs)) {
      $prodotti[$r['codice_calibro']][] = $r['prodotto'];
    }
    foreach ($prodotti as $cal => $prod) {
      $el['calibro'][$cal] = $cal . ' - ' . implode(', ', array_unique($prod));
    }
    $rs = gaz_dbi_dyn_query('codice, descrizione', $gTables['categorie_ortofrutta'], '1', 'codice');
    while ($r = gaz_dbi_fetch_array($rs)) {
      $el['categoria'][$r['codice']] = $r['codice'] . ' - ' . $r['descrizione'];
      $el['descri_categoria'][$r['codice']] = $r['descrizione'];
    }
  }
  return $el;
}

// select per provenienza, calibro o categoria ($tipo), con la voce vuota
function ortofrutta_select($name, $tipo, $val, $class = 'FacetSelect') {
  $el = ortofrutta_elenchi();
  $voci = $el[$tipo];
  $val = (string)$val;
  if ($val !== '' && !isset($voci[$val])) { // valore non piu' in tabella: lo mostro comunque
    $voci[$val] = $val;
  }
  $acc = '<select name="' . $name . '" class="' . $class . '"><option value=""></option>';
  foreach ($voci as $k => $d) {
    $acc .= '<option value="' . htmlspecialchars($k) . '"' . ((string)$k === $val ? ' selected' : '') . '>' . htmlspecialchars(mb_substr($d, 0, 50, 'UTF-8')) . '</option>';
  }
  return $acc . '</select>';
}

// testo da stampare in coda alla descrizione del rigo, vuoto se non ci sono dati
function ortofrutta_testo_rigo($rigo) {
  $parti = [];
  $el = ortofrutta_elenchi();
  if (!empty($rigo['provenienza'])) {
    $parti[] = 'Origine: ' . ($el['paese'][$rigo['provenienza']] ?? $rigo['provenienza']);
  }
  if (!empty($rigo['categoria'])) {
    $parti[] = $el['descri_categoria'][$rigo['categoria']] ?? 'Cat. ' . $rigo['categoria'];
  }
  if (!empty($rigo['calibro'])) {
    $parti[] = 'Cal. ' . $rigo['calibro'];
  }
  return implode(', ', $parti);
}

// messaggio per le pagine di gestione quando la funzione e' spenta
function ortofrutta_avviso_spenta() {
  echo '<div class="alert alert-warning text-center">Attiva la gestione ortofrutta in Configurazione avanzata (gestione_ortofrutta = 1)</div>';
}
?>
