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
$admin_aziend=checkAdmin();
require("../vendit/lib.function.php");
$lm = new lotmag;

$datref = substr($_GET['dr'],0,10);
$catmer = intval($_GET['cm']);
$cmsel = $catmer < 100 ? ' per '.gaz_dbi_get_row($gTables['catmer'], 'codice', $catmer)['descri'] : '';
$title = array('luogo_data'=>$admin_aziend['citspe'].", lì ".date('m-d-Y'),
               'title'=>'Inventario al '.$datref.$cmsel,
               'hile'=>array(array('lun' => 28,'nam'=>'Codice'),
                             array('lun' => 83,'nam'=>'Descrizione articolo'),
                             array('lun' => 10,'nam'=>'U.M.'),
                             array('lun' => 24,'nam'=>'Valore attuale'),
                             array('lun' => 24,'nam'=>'Giacenza'),
                             array('lun' => 24,'nam'=>'Valore giacenza')
                             )
              );
require('../../config/templates/report_template.php');

$pdf = new Report_template();
$pdf->setVars($admin_aziend,$title,1);
$pdf->setFooterMargin(20);
$pdf->setTopMargin(43);
$pdf->AddPage();
$data = getInventoryData($datref,$catmer,$admin_aziend['decimal_price']);
if (isset($data)) {
  $rf=false;
  $nr=0;
  $pdf->SetFillColor(238,238,238);
  $totq_g = 0;
  $totv_g = 0;
  $ctrl_cm = 0;
  $pdf->SetFont('helvetica','',7);
  foreach ($data as $k => $v) {
    $lots = [];
    if ($v['i_l'] >= 1 && $v['g_a'] >= 0.00001 ) {  // se è un articolo con lotti o numero seriale riprendo gli eventuali post delle rimanenze dei singoli lotti
      $lm->getAvailableLots($k,0,gaz_format_date($datref,true));
      $lotrests = $lm->available;
      foreach($lotrests as $kl=>$vl) {
        $lots[] = ['id' => $vl['identifier'],'rs' => floatval($vl['rest'])];
      }
    }

    $rf=$nr%2;
    if ($ctrl_cm <> $v['i_g']) {
      $pdf->SetFont('helvetica','B',8);
      if ($ctrl_cm > 0) {
        $pdf->Cell(145,5,' TOTALE ',1,0,'R',$rf);
        $pdf->Cell(24,5,gaz_format_quantity($totq_g, 1, $admin_aziend['decimal_quantity']),1,0,'R',$rf);
        $pdf->Cell(24,5,gaz_format_number($totv_g),1,1,'R',$rf);
        $totq_g = 0;
        $totv_g = 0;
        $pdf->AddPage();
      }
      $cm = gaz_dbi_get_row($gTables['catmer'], 'codice', $v['i_g']);
      $pdf->Cell(193,5,$cm['codice'].' - '.$cm['descri'],1,1,'',$rf);
      $nr++;
      $rf=$nr%2;
      $pdf->SetFont('helvetica','',7);
    }
    $pdf->Cell(28,4,$k,'LTR',0,'',$rf,'',1);
    $pdf->Cell(83,4,$v['i_d'],'TR',0,'',$rf,'',1);
    $pdf->Cell(10,4,$v['i_u'],'TR',0,'C',$rf);
    $va =  $v['v_a'] >= 0.00001 ? gaz_format_quantity($v['v_a'], 0, $admin_aziend['decimal_price']) : '';
    $pdf->Cell(24,4,$va,'TR',0,'R',$rf);
    $pdf->Cell(24,4,gaz_format_quantity($v['g_a'], 1, $admin_aziend['decimal_quantity']),'TR',0,'R',$rf);
    $pdf->Cell(24,4,gaz_format_number($v['v_g']),'TR',1,'R',$rf);
    foreach ( $lots as $kl => $vl ) {
      $pdf->Cell(111,4,'','LR',0,'',0,'',1);
      if ( $kl == 0 ) {
        $pdf->Cell(20,4,'LOTTI:','L',0,'R','',1);
      } else {
        $pdf->Cell(20,4,'','L',0,'',0,'',1);
      }
      $pdf->Cell(20,4,$vl['id'],'',0,'R');
      $pdf->Cell(18,4,$vl['rs'],'R',0,'R');
      $pdf->Cell(24,4,'','R',1);
    }
    $totq_g += $v['g_a'];
    $totv_g += $v['v_g'];
    $ctrl_cm = $v['i_g'];
    $nr++;
  }
  $pdf->SetFont('helvetica','B',8);
  $pdf->Cell(145,5,' TOTALE ',1,0,'R',$rf);
  $pdf->Cell(24,5,gaz_format_quantity($totq_g, 1, $admin_aziend['decimal_quantity']),1,0,'R',$rf);
  $pdf->Cell(24,5,gaz_format_number($totv_g),1,1,'R',$rf);

}
$pdf->Output();
?>
