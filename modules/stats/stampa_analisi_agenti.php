<?php

/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
  (https://www.devincentiis.it)
  <https://gazie.sourceforge.net>
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

require("../../library/include/datlib.inc.php");
require("../../config/templates/report_template.php");
$admin_aziend = checkAdmin();
$config = new Config;
require("lang." . $admin_aziend['lang'] . ".php");

if (!isset($_GET['livello']) || !isset($_GET['datini']) || !isset($_GET['datfin'])) {
   header("Location: select_analisi_agenti.php");
   exit;
}
$livello = $_GET['livello'];
$datini = $_GET['datini'];
$datfin = $_GET['datfin'];
if (!ini_get('safe_mode')) { //se me lo posso permettere...
   ini_set('memory_limit', '128M');
   gaz_set_time_limit(0);
}
$ragstatArray = caricaElencoRagstat($livello, $gTables);
$where = "tesdoc.tipdoc like 'F%' and rigdoc.quanti>0 and tesdoc.id_agente>0"
        . " AND tesdoc.datfat BETWEEN " . intval($datini) . " AND " . intval($datfin);
$what = " clienti.codice as codice_cliente, concat(dati_clienti.ragso1,' ',dati_clienti.ragso2) as nome_cliente,"
        . "fornitori.codice as codice_fornitore, concat(dati_fornitori.ragso1,' ',dati_fornitori.ragso2) as nome_fornitore, "
//        . "artico.ragstat as codice_ragstat, artico.codice as codice_articolo, artico.descri as descrizione, "
        . "ragstat.descri as nome_ragstat, tesdoc.id_agente as codice_agente ";
$table = $gTables['rigdoc'] . " rigdoc left join "
        . $gTables['tesdoc'] . " tesdoc on rigdoc.id_tes=tesdoc.id_tes left join "
        . $gTables['artico'] . " artico on artico.codice=rigdoc.codart left join "
        . $gTables['clfoco'] . " fornitori on artico.clfoco=fornitori.codice left join "
        . $gTables['anagra'] . " dati_fornitori on fornitori.id_anagra=dati_fornitori.id left join "
        . $gTables['clfoco'] . " clienti on tesdoc.clfoco=clienti.codice left join "
        . $gTables['anagra'] . " dati_clienti on clienti.id_anagra=dati_clienti.id left join "
        . $gTables['ragstat'] . " ragstat on artico.ragstat=ragstat.codice";
$group = "tesdoc.clfoco, tesdoc.id_agente";
$order = "tesdoc.id_agente,nome_cliente";
$contaRagstat = 0;
foreach ($ragstatArray as $cat) { // costruiamo la query per ogni raggruppamento
   $codice_ragstat = $cat['codice'];
   $what = $what . ", sum(CASE WHEN (artico.ragstat like '$codice_ragstat%' and tesdoc.tipdoc like 'FA%') THEN rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100) ELSE 0 END) as imp_ft$contaRagstat,"
           . "sum(CASE WHEN (artico.ragstat like '$codice_ragstat%' and tesdoc.tipdoc like 'FN%') THEN rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100) ELSE 0 END) as imp_nc$contaRagstat, "
           . "sum(CASE WHEN (artico.ragstat like '$codice_ragstat%' and tesdoc.tipdoc like 'FA%') THEN rigdoc.quanti ELSE 0 END) as qt_ft$contaRagstat,"
           . "sum(CASE WHEN (artico.ragstat like '$codice_ragstat%' and tesdoc.tipdoc like 'FN%') THEN rigdoc.quanti ELSE 0 END) as qt_nc$contaRagstat ";
   $contaRagstat++;
}
// aggiungiamo la colonna NO Ragg
$what = $what . ", sum(CASE WHEN ((artico.ragstat is null or artico.ragstat='') and tesdoc.tipdoc like 'FA%') THEN rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100) ELSE 0 END) as imp_ft$contaRagstat,"
        . "sum(CASE WHEN ((artico.ragstat is null or artico.ragstat='') and tesdoc.tipdoc like 'FN%') THEN rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100) ELSE 0 END) as imp_nc$contaRagstat, "
        . "sum(CASE WHEN ((artico.ragstat is null or artico.ragstat='') and tesdoc.tipdoc like 'FA%') THEN rigdoc.quanti ELSE 0 END) as qt_ft$contaRagstat,"
        . "sum(CASE WHEN ((artico.ragstat is null or artico.ragstat='') and tesdoc.tipdoc like 'FN%') THEN rigdoc.quanti ELSE 0 END) as qt_nc$contaRagstat ";
$ragstatArray[] = array('codice' => '', 'descri' => 'NO Ragg');
// aggiungiamo la colonna TOTALE
$contaRagstat++;
$what = $what . ", sum(CASE WHEN (tesdoc.tipdoc like 'FA%') THEN rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100) ELSE 0 END) as imp_ft$contaRagstat,"
        . "sum(CASE WHEN (tesdoc.tipdoc like 'FN%') THEN rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100) ELSE 0 END) as imp_nc$contaRagstat, "
        . "sum(CASE WHEN (tesdoc.tipdoc like 'FA%') THEN rigdoc.quanti ELSE 0 END) as qt_ft$contaRagstat,"
        . "sum(CASE WHEN (tesdoc.tipdoc like 'FN%') THEN rigdoc.quanti ELSE 0 END) as qt_nc$contaRagstat ";
$ragstatArray[] = array('codice' => '', 'descri' => 'TOTALE');

$result = gaz_dbi_dyn_query($what, $table, $where, $order, 0, 20000, $group);
$dimCol = 20;
$aRiportare = array('top' => array(array('lun' => 168, 'nam' => 'da riporto : '),
        array('lun' => 19, 'nam' => '')
    ),
    'bot' => array(array('lun' => 168, 'nam' => 'a riportare : '),
        array('lun' => 19, 'nam' => '')
    )
);
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data = $admin_aziend['citspe'] . ", lì " . ucwords($gazTimeFormatter->format(new DateTime()));
$title = array('luogo_data' => $luogo_data,
    'title' => "Analisi agenti dal $datini al $datfin",
    'hile' => array(
        array('lun' => 62, 'nam' => "Cliente\n "),
    )
);
foreach ($ragstatArray as $cat) { // costruiamo la query per ogni raggruppamento
   $descri_ragstat = $cat['descri'];
   $codice_ragstat = $cat['codice'];
   $titoloCol = substr("$codice_ragstat\n$descri_ragstat", 0, $dimCol - 3);   // evitiamo che il titolo esca dallo spazio dell'intestazione
   $title['hile'][] = array('lun' => $dimCol, 'nam' => "Imp. " . $titoloCol);
   $title['hile'][] = array('lun' => $dimCol, 'nam' => "Qt. " . $titoloCol);
}

$item_head['top'] = array(
);

$pdf = new Report_template();
$pdf->setVars($admin_aziend, $title);
$pdf->SetTopMargin(56);
$pdf->SetFooterMargin(18);
$pdf->SetFont('helvetica', '', 9);

$ctrlAgente = 0;
$numCol = count($ragstatArray);
initTotali($totAgente, $numCol);
while ($row = gaz_dbi_fetch_array($result)) {
   intestaPagina($pdf, $config, $ctrlAgente, $row, $aRiportare, $item_head, $dimCol, $totAgente, $numCol, $datini, $datfin);
   $pdf->Cell(62, 4, $row["nome_cliente"], 1, 0, '', false, '', 1);
   for ($k = 0; $k < $numCol; $k++) {
      $imp = $row["imp_ft$k"] - $row["imp_nc$k"];
      $pdf->SetFillColor(235, 235, 235);
      $pdf->Cell($dimCol, 4, gaz_format_number($imp), 1, 0, 'R', FALSE, '', 1);
      $totAgente['imp'][$k] +=$imp;
      $qt = $row["qt_ft$k"] - $row["qt_nc$k"];
      $pdf->Cell($dimCol, 4, gaz_format_number($qt), 1, ($k == $numCol - 1 ? 1 : 0), 'R', true, '', 1);
      $totAgente['qt'][$k] +=$qt;
   }
//   $pdf->Cell(1, 4, "", 1, 1, 'R', true, '', 1);
   $ctrlAgente = $row["codice_agente"];
}
rigaTotali($pdf, "TOTALE AGENTE", $totAgente, $dimCol, $numCol);
$pdf->Output();

function intestaPagina($pdf, $config, $ctrlAgente, $row, $aRiportare, $item_head, $dimCol, &$totAgente, $numColonne, $datini, $datfin) {
   if ($ctrlAgente != $row['codice_agente']) {
      if ($ctrlAgente > 0) {
         rigaTotali($pdf, "TOTALE AGENTE", $totAgente, $dimCol, $numColonne);
      }
      $item_head['bot'] = array();
      $agente = getNewAgente($row['codice_agente']);

      $pdf->setPageTitle('Analisi agente dal ' . format_date($datini) . " al " . format_date($datfin) . ': '
              . $row['codice_agente'] . " - " . $agente['ragso1'] . ' ' . $agente['ragso2']);
      $pdf->setItemGroup($item_head);
      $dimPagina = ($numColonne > 4 ? ($numColonne > 9 ? ($numColonne > 16 ? "A1" : "A2") : "A3") : "A4");
	  if ($numColonne < 4) {
         $pdf->AddPage();
	  } else {
         $pdf->AddPage('L', $dimPagina);
	  }
   }
}

function initTotali(&$totArray, $dim) {
   for ($k = 0; $k < $dim; $k++) {
      $totArray['imp'][$k] = 0;
      $totArray['qt'][$k] = 0;
   }
}

function rigaTotali($pdf, $stringa, &$totArray, $dimCol, $numCol) {
   $pdf->Cell(62, 4, $stringa, 1, 0, 'R', true, '', 1);
//   $totaleRiga = 0;
   for ($k = 0; $k < $numCol; $k++) {
      $imp = $totArray['imp'][$k];
      $pdf->SetFillColor(194, 249, 129);
      $pdf->Cell($dimCol, 4, gaz_format_number($imp), 1, 0, 'R', true, '', 1);
      $qt = $totArray['qt'][$k];
      $pdf->SetFillColor(249, 249, 129);
      $pdf->Cell($dimCol, 4, gaz_format_number($qt), 1, ($k == $numCol - 1 ? 1 : 0), 'R', true, '', 1);
//      $totaleRiga +=$imp;
   }
//   $pdf->Cell(1, 4, "", 1, 1, 'R', true, '', 1);
   initTotali($totArray, $numCol);
}

function caricaElencoRagstat($livello, $gTables) {   // restituisce un array con tutte le categorie statisstiche con codice <= $livello
   $result = gaz_dbi_dyn_query("codice, descri", $gTables['ragstat'], "length(codice)<=$livello", "codice");
   return gaz_dbi_fetch_all($result);
}

function getNewAgente($id) {
   global $gTables;
   $agente = gaz_dbi_get_row($gTables['agenti'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['agenti'] . ".id_fornitore = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id', $gTables['agenti'] . '.id_agente', $id);
   return $agente;
}

function format_date($date) {
   $uts = mktime(0, 0, 0, intval(substr($date, 4, 2)), intval(substr($date, 6, 2)), intval(substr($date, 0, 4)));
   return date("d-m-Y", $uts);
}

?>
