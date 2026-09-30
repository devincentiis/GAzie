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

if (!isset($_GET['an']) || !isset($_GET['pe'])) {
   header("Location: select_analisi_acquisti_clienti.php");
   exit;
}
$anno = intval($_GET['an']);
$periodo = intval($_GET['pe']);
if (!isset($_GET['cl']) or ( empty($_GET['cl']))) {
   $cliente = '';
} else {
   $cliente = ' AND tesdoc.clfoco = ' . intval($_GET['cl']);
}
if (!ini_get('safe_mode')) { //se me lo posso permettere...
   ini_set('memory_limit', '128M');
   gaz_set_time_limit(0);
}

$where = "tesdoc.tipdoc like 'F%' and rigdoc.quanti>0 and artico.ragstat is not null and artico.ragstat!=''" . $cliente;
$what = " clienti.codice as codice_cliente, concat(dati_clienti.ragso1,' ',dati_clienti.ragso2) as nome_cliente,fornitori.codice as codice_fornitore, concat(dati_fornitori.ragso1,' ',dati_fornitori.ragso2) as nome_fornitore, artico.ragstat as codice_ragstat, artico.codice as codice_articolo, artico.descri as descrizione, ragstat.descri as nome_ragstat ";
$table = $gTables['rigdoc'] . " rigdoc left join "
        . $gTables['tesdoc'] . " tesdoc on rigdoc.id_tes=tesdoc.id_tes left join "
        . $gTables['artico'] . " artico on artico.codice=rigdoc.codart left join "
        . $gTables['clfoco'] . " fornitori on artico.clfoco=fornitori.codice join "
        . $gTables['anagra'] . " dati_fornitori on fornitori.id_anagra=dati_fornitori.id join "
        . $gTables['clfoco'] . " clienti on tesdoc.clfoco=clienti.codice join "
        . $gTables['anagra'] . " dati_clienti on clienti.id_anagra=dati_clienti.id left join "
        . $gTables['ragstat'] . " ragstat on artico.ragstat=ragstat.codice";
$group = "clienti.codice, artico.codice";
$order = "nome_cliente,codice_ragstat,nome_fornitore";
$contaRagstat = 0;
if ($periodo == 1) {  // trimestre
   $incMMInizio = 3;
   $incMMFine = 2;
   $maxPeriodi = 4;
   $dimPagina = $config->getValue('page_format');
   $dimCol = 20;
} else {
   $incMMInizio = 1;
   $incMMFine = 0;
   $maxPeriodi = 12;
   $descrPeriodo = "M";
   $dimPagina = 'A3';
   $dimCol = 13;
}
for ($cat = 1; $cat <= 12; $cat+=$incMMInizio) { // costruiamo la query per ogni periodo (mese o trimestre)
   $contaRagstat++;
   $dTmp = new DateTime("$anno-$cat-01");
   $dataInizio = $dTmp->format('Y-m-d');
   $meseFine = $cat + $incMMFine;
   $dTmp = new DateTime("$anno-$meseFine-01");
   $dataFine = $dTmp->format('Y-m-t');
   $what = $what . ", sum(CASE WHEN (tesdoc.datfat between '$dataInizio' and '$dataFine' and tesdoc.tipdoc like 'FA%') THEN rigdoc.quanti ELSE 0 END) as qt_ft$contaRagstat,
sum(CASE WHEN (tesdoc.datfat between '$dataInizio' and '$dataFine' and tesdoc.tipdoc like 'FA%') THEN rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100) ELSE 0 END) as imp_ft$contaRagstat,
sum(CASE WHEN (tesdoc.datfat between '$dataInizio' and '$dataFine' and tesdoc.tipdoc like 'FN%') THEN rigdoc.quanti ELSE 0 END) as qt_nc$contaRagstat,
sum(CASE WHEN (tesdoc.datfat between '$dataInizio' and '$dataFine' and tesdoc.tipdoc like 'FN%') THEN rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100) ELSE 0 END) as imp_nc$contaRagstat ";
}
$result = gaz_dbi_dyn_query($what, $table, $where, $order, 0, 20000, $group);

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
    'title' => "Analisi acquisti clienti",
    'hile' => array(
        array('lun' => 16, 'nam' => 'Cod.Art.'),
        array('lun' => 62, 'nam' => 'Articolo'),
   )
);
$gazTimeFormatter->setPattern('MMM');
for ($k = 1; $k <= $maxPeriodi; $k++) {
	if ($periodo == 1) {  // trimestre
		$descrPeriodo = $k."°Trim";
	} else {
		$descrPeriodo = ucwords($gazTimeFormatter->format(new DateTime("2000-".$k."-01")));
	}
  $title['hile'][] = array('lun' => $dimCol*2, 'nam' => "Qt/Imp ".$descrPeriodo);
}
$item_head['top'] = array(
);

$pdf = new Report_template();
$pdf->setVars($admin_aziend, $title);
$pdf->SetTopMargin(47);
$pdf->SetFooterMargin(18);
$pdf->SetFont('helvetica', '', 7);

$tot_imponibile = 0.00;
$tot_iva = 0.00;
$tot_spese = 0.00;
$tot_importo = 0.00;
$ctrlFornitore = 0;
$ctrlCliente = 0;
$ctrlCategoria = 0;
initTotali($totCategoria, $maxPeriodi);
initTotali($totAgente, $maxPeriodi);
while ($row = gaz_dbi_fetch_array($result)) {
//   $pdf->setRiporti($aRiportare);
   intestaPagina($pdf, $config, $ctrlFornitore, $ctrlCliente, $ctrlCategoria, $row, $aRiportare, $item_head, $dimPagina, $maxPeriodi, $dimCol, $totCategoria, $totAgente);
   $pdf->Cell(16, 4, $row["codice_articolo"], 1, 0, '', false, '', 1);
   $pdf->Cell(62, 4, $row["descrizione"], 1, 0, '', false, '', 1);
   for ($k = 1; $k <= $maxPeriodi; $k++) {
      $qt = $row["qt_ft$k"] - $row["qt_nc$k"];
      $totCategoria['qt'][$k] +=$qt;
      $totAgente['qt'][$k] +=$qt;
	  if ($qt>=0.00001){
		  $qt=floatval($qt);
	  } else {
		  $qt='';
	  }
      $pdf->Cell($dimCol, 4, $qt, 'LTB', 0, 'R', false, '', 1);
      $imp = $row["imp_ft$k"] - $row["imp_nc$k"];
      $pdf->SetFillColor(235, 235, 235);
      $totCategoria['imp'][$k] +=$imp;
      $totAgente['imp'][$k] +=$imp;
	  if ($imp>=0.00001){
		  $imp=gaz_format_number($imp);
	  } else {
		  $imp='';
	  }
      $pdf->Cell($dimCol, 4, $imp, 'RTB', ($k < $maxPeriodi ? 0 : 1), 'R', true, '', 1);
   }
   $ctrlFornitore = $row["codice_fornitore"];
   $ctrlCliente = $row["codice_cliente"];
   $ctrlCategoria = $row["codice_ragstat"];
}
rigaTotali($pdf, "Totale cat.statistica", $maxPeriodi, $totCategoria, $dimCol);
rigaTotali($pdf, "Totale cliente", $maxPeriodi, $totAgente, $dimCol, 'b');
$pdf->Output();

function intestaPagina($pdf, $config, $ctrlFornitore, $ctrlCliente, $ctrlCategoria, $row, $aRiportare, $item_head, $dimPagina, $maxPeriodi, $dimCol, &$totCategoria, &$totCliente) {
   if ($ctrlCliente != $row['codice_cliente']) {
      if ($ctrlCliente > 0) {
         rigaTotali($pdf, "Totale cat.statistica", $maxPeriodi, $totCategoria, $dimCol);
         rigaTotali($pdf, "Totale cliente", $maxPeriodi, $totCliente, $dimCol, 'b');
      }
      $item_head['bot'] = array();
      $pdf->setPageTitle('Analisi acquisti per categorie statistiche del cliente: ' . intval(substr($row['codice_cliente'],-6)) . " - " . $row['nome_cliente']);
      $pdf->setItemGroup($item_head);
      $pdf->AddPage('L', $dimPagina);
   }
   if ($ctrlCategoria != $row['codice_ragstat']) {
      rigaTotali($pdf, "totale categoria", $maxPeriodi, $totCategoria, $dimCol);
   }
   if ($ctrlFornitore != $row['codice_fornitore'] || $ctrlCategoria != $row['codice_ragstat']) {
      $pdf->SetFillColor(245, 249, 129);
      $pdf->Cell(78 + $maxPeriodi * 2 * $dimCol, 4, "Fornitore: " . $row['codice_fornitore'] . " - " . $row['nome_fornitore'] . " - Categoria: " . $row['codice_ragstat'] . " - " . $row['nome_ragstat'], 1, 1, 'L', true, '', 1);
   }
}

function initTotali(&$totArray, $maxPeriodi) {
   for ($k = 1; $k <= $maxPeriodi; $k++) {
      $totArray['qt'][$k] = 0;
      $totArray['imp'][$k] = 0;
   }
}

function rigaTotali($pdf, $stringa, $maxPeriodi, &$totArray, $dimCol, $fill = 'a') {
   if ($fill == 'a') {
      $pdf->SetFillColor(194, 249, 129);
   } else {
      $pdf->SetFillColor(235, 235, 235);
   }
   $pdf->Cell(78, 4, $stringa, 1, 0, 'L', true, '', 1);
   for ($k = 1; $k <= $maxPeriodi; $k++) {
	  $qt = $totArray['qt'][$k];
	  $imp = $totArray['imp'][$k];
	  if ($qt>=0.00001){
		  $qt=floatval($qt);
	  } else {
		  $qt='';
	  }
	  if ($imp>=0.00001){
		  $imp=gaz_format_number($imp);
	  } else {
		  $imp='';
	  }
      $pdf->Cell($dimCol, 4, $qt, 1, 0, 'R', true, '', 1);
      $pdf->Cell($dimCol, 4, $imp, 1, ($k < $maxPeriodi ? 0 : 1), 'R', true, '', 1);
   }
   initTotali($totArray, $maxPeriodi);
}

?>
