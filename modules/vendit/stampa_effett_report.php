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


if (!isset($_GET['cl']) ||
    !isset($_GET['rp']) ||
    !isset($_GET['ba']) ||
    !isset($_GET['ni']) ||
    !isset($_GET['nf']) ||
    !isset($_GET['ri']) ||
    !isset($_GET['rf'])) {
    header("Location: select_effett_report.php");
    exit;
}

$clfoco = intval($_GET['cl']);
$banacc = intval($_GET['ba']);
$reprint = substr($_GET['rp'],0,1);
$gioini = substr($_GET['ri'],0,2);
$mesini = substr($_GET['ri'],2,2);
$annini = substr($_GET['ri'],4,4);
$utsini= mktime(12,0,0,$mesini,$gioini,$annini);
$datainizio = date("Ymd",$utsini);
$giofin = substr($_GET['rf'],0,2);
$mesfin = substr($_GET['rf'],2,2);
$annfin = substr($_GET['rf'],4,4);
$utsfin= mktime(0,0,0,$mesfin,$giofin,$annfin);
$datafine = date("Ymd",$utsfin);
if ($reprint=='S'){
  $where= '';
} else if ($reprint==''){
  $where= '';
} else {
  $where= "(".$gTables['effett'].".id_distinta = 0  OR ".$gTables['effett'].".id_distinta IS NULL) AND ";
}

if ($clfoco>=100){
  $where.=$gTables['effett'].".clfoco = ".$clfoco." AND ";
  $orderby="scaden, progre";
  // quando seleziono il cliente ignoro la banca di accredito ed il tipo di effetto
} else {
  if ($banacc>=100){
    $where.=$gTables['effett'].".banacc = ".$banacc." AND ";
  }
}

$where .= " scaden BETWEEN '".$datainizio."' AND '".$datafine."' AND progre BETWEEN '".intval($_GET['ni'])."' AND '".intval($_GET['nf'])."'";
$result = gaz_dbi_dyn_query("*", $gTables['effett'],$where,"banacc, tipeff, scaden, progre");
$anagrafica = new Anagrafica();
$luogo_data=$admin_aziend['citspe'].", lì ";
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data .= ucwords($gazTimeFormatter->format(new DateTime()));
$gazTimeFormatter->setPattern('dd/MM/yyyy');
$title = array('luogo_data'=>$luogo_data,
               'title'=>'Effetti dal '.$gazTimeFormatter->format(new DateTime('@'.$utsini)).' al '.$gazTimeFormatter->format(new DateTime('@'.$utsfin)),
               'hile'=>array(array('lun' => 18,'nam'=>'Scadenza'),
                             array('lun' => 18,'nam'=>'Effetto'),
                             array('lun' => 100,'nam'=>'Cliente / Indirizzo,P.IVA / Fattura'),
                             array('lun' => 30,'nam'=>'Appoggio'),
                             array('lun' => 24,'nam'=>'Importo')
                             )
              );
$aRiportare = array('top'=>array(array('lun' => 166,'nam'=>'da riporto : '),
                           array('lun' => 24,'nam'=>'')
                           ),
                    'bot'=>array(array('lun' => 166,'nam'=>'a riportare : '),
                           array('lun' => 24,'nam'=>'')
                           )
                    );

require('../../config/templates/report_template.php');

$pdf = new Report_template();
$pdf->setVars($admin_aziend,$title,1);
$pdf->setFooterMargin(22);
$pdf->setTopMargin(43);
$pdf->setRiporti('');
$pdf->AddPage();
$ctrl_type="";
$ctrl_banacc="";
$totaleff=0.00;
$totnumeff=0;
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
$pdf->SetFont('helvetica','',8);
while ($r = gaz_dbi_fetch_array($result)) {
  if ($r["banacc"] <> $ctrl_banacc) {
    $pdf->SetFont('helvetica','B',8);
    if ($ctrl_banacc!='') {
      $pdf->Cell(190,4,$banacc['descri'].' n.'.$totnumtipo.' '.$descreff.' per € '.gaz_format_number($totaletipo),1,1,'R',1);
      $pdf->Ln(4);
    }
    $totaletipo = 0.00;
    $totnumtipo = 0;
    $banacc = gaz_dbi_get_row($gTables['clfoco'],"codice",$r['banacc']);
    $pdf->Cell(190,6,'Banca di accredito: '.($banacc?$banacc['descri']:''),1,1,'',1);
    $pdf->SetFont('helvetica','',8);
  }
  if ($r["tipeff"] <> $ctrl_type) {
    if ($ctrl_type!='') $pdf->Cell(190,4,$totnumtipo.' '.$descreff.' per un totale di '.gaz_format_number($totaletipo),1,1,'R',1);
    $totaletipo = 0.00;
    $totnumtipo = 0;
    switch($r['tipeff']){
      case "B":
      $descreff = 'RICEVUTE BANCARIE ';
      break;
      case "I":
      $descreff = 'RID ';
      break;
      case "T":
      $descreff = 'CAMBIALI TRATTE ';
      break;
      case "V":
      $descreff = 'MAV ';
      break;
    }
  }
  $totnumeff++;
  $totnumtipo++;
  $totaleff += $r["impeff"];
  $totaletipo += $r["impeff"];
  $cliente = $anagrafica->getPartner($r['clfoco']);
  $banapp = gaz_dbi_get_row($gTables['banapp'],"codice",$r['banapp']);
  $scadenza = substr($r['scaden'],8,2).'-'.substr($r['scaden'],5,2).'-'.substr($r['scaden'],0,4);
  $emission = substr($r['datemi'],8,2).'-'.substr($r['datemi'],5,2).'-'.substr($r['datemi'],0,4);
  $datafatt = substr($r['datfat'],8,2).'-'.substr($r['datfat'],5,2).'-'.substr($r['datfat'],0,4);
  if ($r["salacc"] == 'S')
      $saldoacco = "a saldo";
  else    $saldoacco = "in conto";
  $pdf->Cell(18,4,'','LTR',0,'L');
  $pdf->Cell(18,4,'n.'.$r["progre"].' del','LTR',0,'L');
  $pdf->Cell(100,4,$cliente["ragso1"].' '.$cliente["ragso2"],'LTR',0,'L');
  $pdf->Cell(30,4,'ABI '.$banapp["codabi"],'LTR',0,'R');
  $pdf->Cell(24,4,'','LTR',1,'R');
  $pdf->Cell(18,4,$scadenza,'LR',0,'L');
  $pdf->Cell(18,4,$emission,'R',0,'L');
  $pdf->Cell(100,4,$cliente["indspe"].' '.sprintf("%05d",$cliente["capspe"]).' '.$cliente["citspe"].' ('.$cliente["prospe"].') P.IVA '.$cliente["pariva"],0,0,'L', 0, '', 1);
  $pdf->Cell(30,4,'CAB '.$banapp["codcab"],'R',0,'R');
  $pdf->Cell(24,4,'','R',1,'R');
  $pdf->Cell(18,4,'','LRB',0,'L');
  $pdf->Cell(18,4,$saldoacco,'RB',0,'R');
  $pdf->Cell(80,4,'Fatt.n.'.$r["numfat"].' del '.$datafatt,'B',0,'L');
  $pdf->Cell(20,4,'','B');
  $pdf->Cell(30,4,$banapp["descri"],'RB',0,'R');
  $aRiportare['top'][1]['nam'] = gaz_format_number($totaletipo);
  $aRiportare['bot'][1]['nam'] = gaz_format_number($totaletipo);
  $pdf->setRiporti($aRiportare);
  $pdf->Cell(24,4,gaz_format_number($r["impeff"]),'RB',1,'R');
  $ctrl_type = $r["tipeff"];
  $ctrl_banacc = $r["banacc"];
}
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
$pdf->setRiporti();
$pdf->SetFont('helvetica','B',8);
$pdf->Cell(190,4,$banacc['descri'].' n.'.$totnumtipo.' '.$descreff.' per € '.gaz_format_number($totaletipo),1,1,'R',1);
$pdf->Ln(4);
$pdf->SetFont('helvetica','B',12);
$pdf->Cell(80);
$pdf->Cell(80,10,'Totale degli effetti nella lista € ',1,0,'R');
$pdf->Cell(30,10,gaz_format_number($totaleff),1,1,'R',1);
$pdf->Output();
?>
