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
  Controlli sui righi dei documenti di vendita (DDT, fatture, ordini, preventivi):
  - un rigo normale con quantita' o prezzo a zero non viene inserito;
  - se l'articolo e' gia' in un altro rigo compare l'avviso "possibile doppia immissione".
  Si attiva in Configurazione avanzata azienda con controlli_righi = 1.
*/

// true se l'azienda ha attivato i controlli sui righi
function controlli_righi_attivo() {
  global $gTables;
  static $attivo = null;
  if ($attivo === null) {
    $r = gaz_dbi_get_row($gTables['company_config'], 'var', 'controlli_righi');
    $attivo = ($r && trim($r['val']) == '1');
  }
  return $attivo;
}

// prezzo che avra' il rigo normale una volta inserito (stessa scelta di admin_docven e admin_broven)
function controlli_righi_prezzo($form) {
  global $gTables, $msgtoast;
  if (substr($form['in_status'], 0, 6) == 'UPDROW') { // rigo in modifica: resta il suo prezzo
    return floatval($form['in_prelis']);
  }
  $artico = gaz_dbi_get_row($gTables['artico'], 'codice', $form['in_codart']);
  if (!$artico) {
    return 0;
  }
  if (trim($form['in_sconto']) == '#' && floatval($form['sconto']) <= 0 && class_exists('venditCalc')) {
    $toast = $msgtoast; // trovaPrezzoNetto_Sconto scrive il messaggio del rigo: lo rimetto com'era
    $comp = new venditCalc();
    $netto = $comp->trovaPrezzoNetto_Sconto($form['clfoco'], $form['in_codart']);
    $msgtoast = $toast;
    if ($netto < 0) { // prezzo netto riservato al cliente
      return -$netto;
    }
  }
  $listini = array(2 => 'preve2', 3 => 'preve3', 4 => 'preve4', 5 => 'web_price');
  $campo = isset($listini[intval($form['listin'])]) ? $listini[intval($form['listin'])] : 'preve1';
  return floatval($artico[$campo]);
}

// controlla il rigo normale che si sta inserendo o modificando:
// restituisce il campo da correggere ('in_quanti' o 'search_cosear') oppure '' se va bene
function controlli_righi_zero($form) {
  if (intval($form['in_tiprig']) != 0) {
    return '';
  }
  if (abs(floatval($form['in_quanti'])) < 0.00001) {
    return 'in_quanti';
  }
  if (abs(controlli_righi_prezzo($form)) < 0.00001) { // il prezzo viene dall'articolo
    return 'search_cosear';
  }
  return '';
}

// true se l'articolo del rigo e' gia' presente in un altro rigo normale del documento
function controlli_righi_doppio($form) {
  $codart = trim($form['in_codart']);
  if (intval($form['in_tiprig']) != 0 || $codart === '' || empty($form['rows'])) {
    return false;
  }
  $escludi = (substr($form['in_status'], 0, 6) == 'UPDROW') ? intval(substr($form['in_status'], 6)) : -1;
  foreach ($form['rows'] as $k => $v) {
    if ($k != $escludi && isset($v['tiprig'], $v['codart']) && intval($v['tiprig']) == 0 && trim($v['codart']) === $codart) {
      return true;
    }
  }
  return false;
}

// script che colora di rosso il campo da correggere e ci rimette il cursore
function controlli_righi_script($campo) {
  $sel = ($campo == 'in_quanti') ? '[name="in_quanti"]' : '#search_cosear';
  return '<script>
window.addEventListener("load", function() {
  var c = document.querySelector(' . json_encode($sel) . ');
  if (!c) { return; }
  c.style.border = "2px solid #d9534f";
  c.style.background = "#f2dede";
  setTimeout(function() { c.focus(); if (c.select) { c.select(); } }, 400);
});
</script>
';
}
