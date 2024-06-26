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
if (!isset($_GET['datini']) or ! isset($_GET['datfin']) or ! isset($_GET['id_agente'])) {
   header("Location: select_provvigioni.php");
   exit;
}
require("../../config/templates/report_template.php");

function getNewAgente($id) {
   global $gTables;
   $agente = gaz_dbi_get_row($gTables['agenti'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['agenti'] . ".id_fornitore = " . $gTables['clfoco'] . ".codice
                                                  LEFT JOIN " . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id', $gTables['agenti'] . '.id_agente', $id);
   return $agente;
}

if ($_GET['id_agente'] > 0) {
   $sql_agente = $gTables['tesdoc'] . '.id_agente = ' . intval($_GET['id_agente']) . ' AND ';
} else {
   $sql_agente = $gTables['tesdoc'] . '.id_agente > 0 AND';
}

$dataini = substr($_GET['datini'], 0, 4) . '-' . substr($_GET['datini'], 4, 2) . '-' . substr($_GET['datini'], 6, 2);
$datafin = substr($_GET['datfin'], 0, 4) . '-' . substr($_GET['datfin'], 4, 2) . '-' . substr($_GET['datfin'], 6, 2);
$where = $sql_agente . " tipdoc LIKE 'F__' AND tiprig = 0 AND datfat BETWEEN " . intval($_GET['datini']) . " AND " . intval($_GET['datfin']);
$what = $gTables['tesdoc'] . ".id_agente, " .
        $gTables['tesdoc'] . ".id_tes, " .
        $gTables['tesdoc'] . ".datfat, " .
        $gTables['tesdoc'] . ".datemi, " .
        $gTables['tesdoc'] . ".clfoco, " .
        $gTables['tesdoc'] . ".tipdoc, " .
        $gTables['tesdoc'] . ".protoc, " .
        $gTables['tesdoc'] . ".numdoc, " .
        $gTables['tesdoc'] . ".numfat, " .
        $gTables['tesdoc'] . ".seziva, " .
        $gTables['tesdoc'] . ".sconto AS scochi, " .
        $gTables['anagra'] . ".ragso1, " .
        $gTables['anagra'] . ".ragso2, " .
        $gTables['anagra'] . ".citspe, " .
        $gTables['anagra'] . ".prospe, " .
        $gTables['rigdoc'] . ".id_tes, " .
        $gTables['rigdoc'] . ".id_rig, " .
        $gTables['rigdoc'] . ".tiprig, " .
        $gTables['rigdoc'] . ".codart, " .
        $gTables['rigdoc'] . ".descri, " .
        $gTables['rigdoc'] . ".unimis, " .
        $gTables['rigdoc'] . ".quanti, " .
        $gTables['rigdoc'] . ".prelis, " .
        $gTables['rigdoc'] . ".sconto, " .
        $gTables['rigdoc'] . ".provvigione";
$table = $gTables['rigdoc'] . " LEFT JOIN " . $gTables['tesdoc'] . " ON " . $gTables['tesdoc'] . ".id_tes = " . $gTables['rigdoc'] . ".id_tes
                              LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesdoc'] . ".clfoco = " . $gTables['clfoco'] . ".codice
                              LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['anagra'] . ".id = " . $gTables['clfoco'] . ".id_anagra ";
$result = gaz_dbi_dyn_query($what, $table, $where, "id_agente, datfat , clfoco, protoc, id_rig");

$aRiportare = array('top' => array(array('lun' => 168, 'nam' => 'da riporto : '),
        array('lun' => 19, 'nam' => '')
    ),
    'bot' => array(array('lun' => 168, 'nam' => 'a riportare : '),
        array('lun' => 19, 'nam' => '')
    )
);
$title = array('title' => '',
    'hile' => array(array('lun' => 25, 'nam' => 'Codice'),
        array('lun' => 74, 'nam' => 'Descrizione'),
        array('lun' => 6, 'nam' => 'Um'),
        array('lun' => 13, 'nam' => 'Quant.'),
        array('lun' => 14, 'nam' => 'Prezzo'),
        array('lun' => 8, 'nam' => '%Sc.'),
        array('lun' => 20, 'nam' => 'Importo'),
        array('lun' => 11, 'nam' => '%Prov.'),
        array('lun' => 16, 'nam' => 'Provv.')
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
$totalegeneraleprovvigioni = 0.00;
$totalegeneralefatturato = 0;
$totaleprovvigioni = 0.00;
$totalefatturato = 0;
while ($row = gaz_dbi_fetch_array($result)) {
   $pdf->setRiporti($aRiportare);
   if ($ctrlAgente != $row['id_agente']) {
      if ($ctrlAgente > 0) {
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($aRiportare['top'][0]['lun'], 4, 'Totale provvigioni: ', 1, 0, 'R');
        $pdf->Cell($aRiportare['top'][1]['lun'], 4, $aRiportare['top'][1]['nam'], 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $totaleprovvigioni = 0.00;
        $totalefatturato = 0;
      }
      $agente = getNewAgente($row['id_agente']);
      $item_head['bot'] = array(array('lun' => 50, 'nam' => $agente['indspe']),
          array('lun' => 60, 'nam' => $agente['citspe'] . ' (' . $agente['prospe'] . ') ' . $agente['telefo']),
          array('lun' => 37, 'nam' => substr($_GET['datini'], 6, 2) . '.' . substr($_GET['datini'], 4, 2) . '.' . substr($_GET['datini'], 0, 4) . '-' . substr($_GET['datfin'], 6, 2) . '.' . substr($_GET['datfin'], 4, 2) . '.' . substr($_GET['datfin'], 0, 4))
      );
      $aRiportare['top'][1]['fat'] = 0;
      $aRiportare['bot'][1]['fat'] = 0;
      $aRiportare['top'][1]['nam'] = 0;
      $aRiportare['bot'][1]['nam'] = 0;
      $pdf->setRiporti('');
      $pdf->setPageTitle('Agente: ' . $agente['ragso1'] . ' ' . $agente['ragso2']);
      $pdf->setItemGroup($item_head);
      $pdf->AddPage('P', $config->getValue('page_format'));
   }
   if ($row['tipdoc'] == 'FNC') {
      $row['quanti'] = -$row['quanti'];
   }
   $row_importo = CalcolaImportoRigo($row['quanti'], $row['prelis'], array($row['scochi'], $row['sconto']));
   $row_provvig = round($row_importo * $row['provvigione'] / 100, 3);
   $totalegeneralefatturato += $row_importo;
   $totalegeneraleprovvigioni += $row_provvig;
   $aRiportare['top'][1]['fat'] = gaz_format_number($totalegeneralefatturato);
   $aRiportare['bot'][1]['fat'] = gaz_format_number($totalegeneralefatturato);
   $aRiportare['top'][1]['nam'] = gaz_format_number($totalegeneraleprovvigioni);
   $aRiportare['bot'][1]['nam'] = gaz_format_number($totalegeneraleprovvigioni);
   if ($ctrlDoc != $row['id_tes']) {
	  if ($totalefatturato>0) {
		$pdf->SetFont('helvetica', 'B', 8);
		$pdf->Cell(141, 5, 'Totali Fattura: ', 0, 0, 'R');
		$pdf->Cell(20, 5, gaz_format_number($totalefatturato), 0, 0, 'R');
		$pdf->Cell(27, 5, gaz_format_number($totaleprovvigioni), 0, 1, 'R');
		$pdf->SetFont('helvetica', '', 7);
		$totalefatturato = 0;
		$totaleprovvigioni = 0;
	  };
      $tmpDescr = $strScript['admin_docven.php']['doc_name'][$row['tipdoc']];
      if ($row['tipdoc'] == 'FAD') {
         $desdoc = 'da ' . $tmpDescr . ' n.' . $row['numdoc'] . ' del ' . gaz_format_date($row['datemi']) . ' -> Fattura n.' . $row['numfat'] . '/' . $row['seziva'] . ' del ' . gaz_format_date($row['datfat']) . ' a ' . $row['ragso1'] . ' ' . $row['ragso2'];
      } else {
         $desdoc = 'da ' . $tmpDescr . ' n.' . $row['numfat'] . '/' . $row['seziva'] . ' del ' . gaz_format_date($row['datfat']) . ' a ' . $row['ragso1'] . ' ' . $row['ragso2'];
      }
      $pdf->Cell(187, 4, $desdoc, 1, 1);
   }
   $pdf->Cell(25, 4, $row['codart'], 1);
   $pdf->Cell(74, 4, $row['descri'], 1,0,'L',0,'',1);
   $pdf->Cell(6, 4, $row['unimis'], 1);
   $pdf->Cell(13, 4, gaz_format_number($row['quanti']), 1, 0, 'R');
   $pdf->Cell(14, 4, number_format($row['prelis'], $admin_aziend['decimal_price'], ',', '.'), 1, 0, 'R');
   $pdf->Cell(8, 4, floatval($row['sconto']), 1, 0, 'R');
   $pdf->Cell(20, 4, gaz_format_number($row_importo), 1, 0, 'R');
   $pdf->Cell(11, 4, gaz_format_number($row['provvigione']), 1, 0, 'R');
   $pdf->Cell(16, 4, gaz_format_number($row_provvig), 1, 1, 'R');
   $totalefatturato +=  $row_importo;
   $totaleprovvigioni +=  $row_provvig;
   $ctrlAgente = $row['id_agente'];
   $ctrlDoc = $row['id_tes'];
}
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(141, 5, 'Totali Fatture: ', 0, 0, 'R');
$pdf->Cell(20, 5, gaz_format_number($totalefatturato), 0, 0, 'R');
$pdf->Cell(27, 5, gaz_format_number($totaleprovvigioni), 0, 1, 'R');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(141, 8, 'Totali provvigioni: ', 0, 0, 'R');
$pdf->Cell(20, 8, $aRiportare['top'][1]['fat'], 0, 0, 'R');
$pdf->Cell(27, 8, $aRiportare['top'][1]['nam'], 0, 1, 'R');
$pdf->SetFont('helvetica', '', 7);
$pdf->setRiporti('');
$pdf->Output();
?>
