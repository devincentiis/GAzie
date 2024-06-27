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


if (!isset($_GET['de']) ||
    !isset($_GET['rp']) ||
    !isset($_GET['ba']) ||
    !isset($_GET['ni']) ||
    !isset($_GET['nf']) ||
    !isset($_GET['ri']) ||
    !isset($_GET['rf'])) {
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit;
}

$gioemi = substr($_GET['de'],0,2);
$mesemi = substr($_GET['de'],2,2);
$annemi = substr($_GET['de'],4,4);
$utsemi= mktime(12,0,0,$mesemi,$gioemi,$annemi);
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

if ($_GET['rp'] <> 'S') {
    $ristampa = "(id_distinta = 0  OR id_distinta IS NULL) AND ";
} else {
    $ristampa = "(banacc = '".intval($_GET['ba'])."' OR banacc = 0) AND ";
}



$where = $ristampa." scaden BETWEEN '".$datainizio."' AND '".$datafine."' AND progre BETWEEN '".intval($_GET['ni'])."' AND '".intval($_GET['nf'])."'";
$result = gaz_dbi_dyn_query("*", $gTables['effett'],$where,"tipeff, scaden, id_tes");
$anagrafica = new Anagrafica();
$banacc = gaz_dbi_get_row($gTables['clfoco'],"codice",intval($_GET['ba']));
$descbanacc = $banacc['descri'];
$luogo_data=$admin_aziend['citspe'].", lì ";
$gazTimeFormatter->setPattern('dd MMMM yyyy');
if (isset($_GET['de'])) {
  $luogo_data .= ucwords($gazTimeFormatter->format(new DateTime('@'.$utsemi)));
} else {
  $luogo_data .= ucwords($gazTimeFormatter->format(new DateTime()));
}
$gazTimeFormatter->setPattern('dd/MM/yyyy');
$title = array('luogo_data'=>$luogo_data,
               'title'=>'Effetti su '.$descbanacc." dal ".$gazTimeFormatter->format(new DateTime('@'.$utsini)).' al '.$gazTimeFormatter->format(new DateTime('@'.$utsfin)),
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
$ctrltipo="";
$totaleff=0.00;
$totnumeff=0;
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
$pdf->SetFont('helvetica','',8);
while ($a_row = gaz_dbi_fetch_array($result)) {
    if ($a_row["tipeff"] <> $ctrltipo){
        if (isset($totaletipo))
            $pdf->Cell(190,4,$totnumtipo.' '.$descreff.' per un totale di '.gaz_format_number($totaletipo),1,1,'R',1);
        $totaletipo = 0.00;
        $totnumtipo = 0;
        switch($a_row['tipeff'])
            {
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
    $totaleff += $a_row["impeff"];
    $totaletipo += $a_row["impeff"];
    $cliente = $anagrafica->getPartner($a_row['clfoco']);
    $banapp = gaz_dbi_get_row($gTables['banapp'],"codice",$a_row['banapp']);
    $scadenza = substr($a_row['scaden'],8,2).'-'.substr($a_row['scaden'],5,2).'-'.substr($a_row['scaden'],0,4);
    $emission = substr($a_row['datemi'],8,2).'-'.substr($a_row['datemi'],5,2).'-'.substr($a_row['datemi'],0,4);
    $datafatt = substr($a_row['datfat'],8,2).'-'.substr($a_row['datfat'],5,2).'-'.substr($a_row['datfat'],0,4);
    if ($a_row["salacc"] == 'S')
        $saldoacco = "a saldo";
    else    $saldoacco = "in conto";
    $pdf->Cell(18,4,'','LTR',0,'L');
    $pdf->Cell(18,4,'n.'.$a_row["progre"].' del','LTR',0,'L');
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
    $pdf->Cell(80,4,'Fatt.n.'.$a_row["numfat"].' del '.$datafatt,'B',0,'L');
    $pdf->Cell(20,4,'','B');
    $pdf->Cell(30,4,$banapp["descri"],'RB',0,'R');
    $aRiportare['top'][1]['nam'] = gaz_format_number($totaletipo);
    $aRiportare['bot'][1]['nam'] = gaz_format_number($totaletipo);
    $pdf->setRiporti($aRiportare);
    $pdf->Cell(24,4,gaz_format_number($a_row["impeff"]),'RB',1,'R');
    $ctrltipo = $a_row["tipeff"];
}
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
$pdf->setRiporti();
$pdf->Cell(190,4,$totnumtipo.' '.$descreff.' per un totale di € '.gaz_format_number($totaletipo),1,1,'R',1);
$pdf->SetFont('helvetica','B',12);
$pdf->Cell(80);
$pdf->Cell(80,10,'Totale degli effetti nella lista € ',1,0,'R');
$pdf->Cell(30,10,gaz_format_number($totaleff),1,1,'R',1);
$pdf->Output();
?>
