<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
  (http://www.devincentiis.it)
  <http://gazie.sourceforge.net>
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
$admin_aziend = checkAdmin();
require("lang." . $admin_aziend['lang'] . ".php");
require("../../config/templates/report_template.php");

function getNewAgente($id) {
   global $gTables;
   $agente = gaz_dbi_get_row($gTables['agenti'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['agenti'] . ".id_fornitore = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id', $gTables['agenti'] . '.id_agente', $id);
   return $agente;
}

if (!isset($_GET['datini']) or ! isset($_GET['datfin']) or ! isset($_GET['id_agente'])) {
   header("Location: select_provvigioni.php");
   exit;
}

if ($_GET['id_agente'] > 0) {
   $sql_agente = 'tesdoc.id_agente = ' . intval($_GET['id_agente']) . ' AND ';
} else {
   $sql_agente = 'tesdoc.id_agente > 0 AND';
}

$dataini = substr($_GET['datini'], 0, 4) . '-' . substr($_GET['datini'], 4, 2) . '-' . substr($_GET['datini'], 6, 2);
$datafin = substr($_GET['datfin'], 0, 4) . '-' . substr($_GET['datfin'], 4, 2) . '-' . substr($_GET['datfin'], 6, 2);
$where = $sql_agente . " tipdoc LIKE 'F__' AND tiprig = 0 AND datfat BETWEEN " . intval($_GET['datini']) . " AND " . intval($_GET['datfin']);
$what = "tesdoc.id_agente, " .
        "tesdoc.id_tes, " .
        "tesdoc.datfat, " .
        "tesdoc.datemi, " .
        "tesdoc.clfoco, " .
        "tesdoc.tipdoc, " .
        "tesdoc.protoc, " .
        "tesdoc.numdoc, " .
        "tesdoc.numfat, " .
        "tesdoc.seziva, " .
        "tesdoc.sconto AS scochi, " .
        "anagra.ragso1, " .
        "anagra.ragso2, " .
        "anagra.citspe, " .
        "anagra.prospe, " .
        "rigdoc.id_tes, " .
        "SUM(rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100)*(1-tesdoc.sconto/100)) as totaleFattura, " .
        "SUM(rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100)*(1-tesdoc.sconto/100)*rigdoc.provvigione/100) as totaleProvvigione," .
        "AVG(rigdoc.provvigione) as provvigione";
$table = $gTables['rigdoc'] . " rigdoc "
        . "LEFT JOIN " . $gTables['tesdoc'] . " tesdoc ON tesdoc.id_tes = rigdoc.id_tes "
        . "LEFT JOIN " . $gTables['clfoco'] . " clfoco ON tesdoc.clfoco = clfoco.codice "
        . "LEFT JOIN " . $gTables['anagra'] . " anagra ON anagra.id = clfoco.id_anagra ";
$groupBy = " numfat ";
$result = gaz_dbi_dyn_query($what, $table, $where, "id_agente, datfat , clfoco, protoc, id_rig", 0, 20000, $groupBy);

$aRiportare = array('top' => array(array('lun' => 140, 'nam' => 'da riporto : '),
        array('lun' => 20, 'nam' => ''),
        array('lun' => 11, 'nam' => ''),
        array('lun' => 20, 'nam' => ''),
    ),
    'bot' => array(array('lun' => 140, 'nam' => 'a riportare : '),
        array('lun' => 20, 'nam' => ''),
        array('lun' => 11, 'nam' => ''),
        array('lun' => 20, 'nam' => '')
    )
);
$title = array('title' => '',
    'hile' => array(
        array('lun' => 140, 'nam' => 'Descrizione'),
        array('lun' => 20, 'nam' => 'Importo'),
        array('lun' => 11, 'nam' => '%Prov.'),
        array('lun' => 20, 'nam' => 'Provv.')
    )
);
$item_head['top'] = array(array('lun' => 50, 'nam' => 'Indirizzo'),
    array('lun' => 60, 'nam' => 'Città/Telefono'),
    array('lun' => 37, 'nam' => 'Periodo')
);
$pdf = new Report_template();
$pdf->setVars($admin_aziend, $title);
$pdf->SetTopMargin(51);
$pdf->SetFooterMargin(18);
$config = new Config;
$pdf->SetFont('helvetica', '', 7);

$ctrlAgente = 0;
$ctrlDoc = 0;
$totgen_prov = 0.00;
$totgen_fatt = 0.00;
$tot_prov = 0.00;
$tot_fatt = 0.00;
while ($row = gaz_dbi_fetch_array($result)) {
   $pdf->setRiporti($aRiportare);
   if ($ctrlAgente != $row['id_agente']) {
      if ($ctrlAgente > 0) {
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($aRiportare['top'][0]['lun'], 4, 'Totale provvigioni: ', 1, 0, 'R');
        $pdf->Cell($aRiportare['top'][1]['lun'], 4, $aRiportare['top'][1]['nam'], 1, 0, 'R');
        $pdf->Cell(11, 4, "", 1, 0, 'R');
        $pdf->Cell($aRiportare['top'][3]['lun'], 4, $aRiportare['top'][3]['nam'], 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 8);
        $totgen_prov += $tot_prov;
        $totgen_fatt += $tot_fatt;
        $tot_prov = 0.00;
        $tot_fatt = 0.00;

      }
      $agente = getNewAgente($row['id_agente']);
      $item_head['bot'] = array(array('lun' => 50, 'nam' => $agente['indspe']),
          array('lun' => 60, 'nam' => $agente['citspe'] . ' (' . $agente['prospe'] . ') ' . $agente['telefo']),
          array('lun' => 37, 'nam' => substr($_GET['datini'], 6, 2) . '.' . substr($_GET['datini'], 4, 2) . '.' . substr($_GET['datini'], 0, 4) . '-' . substr($_GET['datfin'], 6, 2) . '.' . substr($_GET['datfin'], 4, 2) . '.' . substr($_GET['datfin'], 0, 4))
      );
      $aRiportare['top'][1]['nam'] = 0;
      $aRiportare['bot'][1]['nam'] = 0;
      $aRiportare['top'][3]['nam'] = 0;
      $aRiportare['bot'][3]['nam'] = 0;
      $pdf->setRiporti('');
      $pdf->setPageTitle('Agente: ' . $agente['ragso1'] . ' ' . $agente['ragso2']);
      $pdf->setItemGroup($item_head);
      $pdf->AddPage();
   }
   if ($row['tipdoc'] == 'FNC') {   // nota di credito
      $row['totaleFattura'] = -$row['totaleFattura'];
      $row['totaleProvvigione'] = -$row['totaleProvvigione'];
   }

   $row_importo = $row['totaleFattura'];
   $tot_fatt += $row_importo;
   $row_provvig = $row['totaleProvvigione'];
   $tot_prov += $row_provvig;
   $aRiportare['top'][1]['nam'] = gaz_format_number($tot_fatt);
   $aRiportare['bot'][1]['nam'] = gaz_format_number($tot_fatt);
   $aRiportare['top'][3]['nam'] = gaz_format_number($tot_prov);
   $aRiportare['bot'][3]['nam'] = gaz_format_number($tot_prov);
   if ($ctrlDoc != $row['id_tes']) {
      $tmpDescr = $strScript['admin_docven.php']['doc_name'][$row['tipdoc']];
      $desdoc = 'Fattura n.' . $row['numfat'] . '/' . $row['seziva'] . ' del ' . gaz_format_date($row['datfat']) . ' a ' . $row['ragso1'] . ' ' . $row['ragso2'];
      $pdf->Cell(140, 4, $desdoc, 1, 0);
   }
   $pdf->Cell(20, 4, gaz_format_number($row_importo), 1, 0, 'R');
   $pdf->Cell(11, 4, gaz_format_number($row['provvigione']), 1, 0, 'R');
   $pdf->Cell(20, 4, gaz_format_number($row_provvig), 1, 1, 'R');
   $ctrlAgente = $row['id_agente'];
   $ctrlDoc = $row['id_tes'];
}
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(140, 4, 'Totali: ', 1, 0, 'R');
$pdf->Cell(20, 4, $aRiportare['top'][1]['nam'], 1, 0, 'R');
$pdf->Cell(11, 4, "", 1, 0, 'R');
$pdf->Cell(20, 4, $aRiportare['top'][3]['nam'], 1, 0, 'R');
$pdf->SetFont('helvetica', '', 8);
$pdf->setRiporti('');
if ($_GET['id_agente'] == 0) { // se non ho scelto l'agente stampo il totale generale
  $totgen_prov += $tot_prov;
  $totgen_fatt += $tot_fatt;
  $pdf->SetFillColor(255,160,160);
  $pdf->Ln(20);
  $pdf->SetFont('helvetica', 'B', 8);
  $pdf->Cell($aRiportare['top'][0]['lun'], 4, 'TOTALE GENERALE: ', 1, 0, 'R',1);
  $pdf->Cell($aRiportare['top'][1]['lun'], 4, gaz_format_number($totgen_fatt), 1, 0, 'R',1);
  $pdf->Cell(11, 4, "", 1, 0, 'R',1);
  $pdf->Cell($aRiportare['top'][3]['lun'], 4, gaz_format_number($totgen_prov), 1, 0, 'R',1);
}
$pdf->Output();
?>
