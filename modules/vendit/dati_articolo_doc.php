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
  Richiesta AJAX del riquadro di inserimento rigo (funzione prezzo_consigliato):
  restituisce in JSON costo di acquisto, ricarico del raggruppamento statistico,
  prezzo consigliato e prezzo di vendita per il cliente (prima il prezzo netto
  riservato al cliente, poi il listino del documento o dell'anagrafica).
  Parametri GET: codart, clfoco, listin (1-5, facoltativo).
*/
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
  $user_error = 'Access denied - not an AJAX request...';
  trigger_error($user_error, E_USER_ERROR);
}
require("../../library/include/datlib.inc.php");
require_once("../../library/include/prezzo_consigliato.inc.php");
require_once("lib.function.php");
$admin_aziend = checkAdmin();
header('Content-Type: application/json; charset=utf-8');

if (!prezzo_consigliato_attivo()) { // funzione spenta
  echo json_encode(array('errore' => 'funzione non attiva'));
  exit;
}
$codart = isset($_GET['codart']) ? substr((string)$_GET['codart'], 0, 32) : '';
$clfoco = isset($_GET['clfoco']) ? intval($_GET['clfoco']) : 0;
$listin = isset($_GET['listin']) ? intval($_GET['listin']) : 0;
$artico = ($codart !== '') ? gaz_dbi_get_row($gTables['artico'], 'codice', $codart) : false;
if (!$artico) {
  echo json_encode(array('errore' => 'articolo non trovato'));
  exit;
}
$dec = intval($admin_aziend['decimal_price']);
$out = array('codart' => $artico['codice'], 'last_cost' => round(floatval($artico['last_cost']), $dec), 'ricarico' => 0, 'prezzo_consigliato' => 0, 'prezzo_vendita' => 0);
if (trim($artico['ragstat']) !== '') {
  $out['ricarico'] = floatval(gaz_dbi_get_row($gTables['ragstat'], 'codice', $artico['ragstat'], '', 'ricarico'));
}
if ($out['last_cost'] > 0) {
  $out['prezzo_consigliato'] = round($out['last_cost'] * (1 + $out['ricarico'] / 100), $dec);
}
// prezzo di vendita: prima il prezzo netto riservato al cliente
$prezzo = 0;
if ($clfoco >= 100000000) {
  $comp = new venditCalc();
  $pn = $comp->trovaPrezzoNetto_Sconto($clfoco, gaz_dbi_real_escape_string($artico['codice']));
  if ($pn < 0) {
    $prezzo = -$pn;
  }
}
// poi il listino del documento o, se manca, quello del cliente
if ($prezzo <= 0) {
  if ($listin < 1 || $listin > 5) {
    $listin = 1;
    if ($clfoco >= 100000000) {
      $l = intval(gaz_dbi_get_row($gTables['clfoco'], 'codice', $clfoco, '', 'listin'));
      if ($l >= 1 && $l <= 5) {
        $listin = $l;
      }
    }
  }
  $prezzo = floatval(($listin == 5) ? $artico['web_price'] : $artico['preve' . $listin]);
}
$out['prezzo_vendita'] = round($prezzo, $dec);
echo json_encode($out);
