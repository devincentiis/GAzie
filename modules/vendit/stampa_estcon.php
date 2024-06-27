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

require('../../config/templates/report_template.php');

if (!isset($_GET['codice'])){
    header("Location: report_credit.php");
    exit;
}
if(!isset($_GET["annfin"])){
    $day_end = 31;
    $month_end = 12;
    $year_end = intval(date("Y"));
} else {
    $day_end = intval($_GET["giornfin"]);
    $month_end = intval($_GET["mesfin"]);
    $year_end = intval($_GET["annfin"]);
}

if(!isset($_GET["annini"])){
    $day_start = 1;
    $month_start = 1;
    $year_start = intval(date("Y"))-1;
} else {
    $day_start = intval($_GET["giornini"]);
    $month_start = intval($_GET["mesini"]);
    $year_start = intval($_GET["annini"]);
}

$day_end = str_pad($day_end, 2, "0", STR_PAD_LEFT);
$month_end = str_pad($month_end, 2, "0", STR_PAD_LEFT);

$day_start = str_pad($day_start, 2, "0", STR_PAD_LEFT);
$month_start = str_pad($month_start, 2, "0", STR_PAD_LEFT);

$anagrafica = new Anagrafica();
$conto = $anagrafica->getPartner(intval($_GET['codice']));
//recupero tutti i movimenti contabili del conto insieme alle relative testate...
$result = mergeTable($gTables['rigmoc'],"*",$gTables['tesmov'],"*","id_tes","codcon = ".intval($_GET['codice'])." AND datreg BETWEEN '".$year_start.$month_start.$day_start."' AND '".$year_end.$month_end.$day_end."' AND caucon <> 'CHI' AND caucon <> 'APE' OR (caucon = 'APE' AND codcon ='".intval($_GET['codice'])."%' AND datreg LIKE '".intval($_GET["annini"])."%') ORDER BY datreg ASC, ".$gTables['tesmov'].".id_tes");
$emissione = 'Estratto conto: '.$conto['ragso1'].' '.$conto['ragso2'];
$title = array('title'=>$emissione,
               'hile'=>array(array('lun' => 20,'nam'=>'Data'),
                             array('lun' => 75,'nam'=>'Descrizione'),
                             array('lun' => 18,'nam'=>'N.Doc.'),
                             array('lun' => 18,'nam'=>'Data.Doc.'),
                             array('lun' => 18,'nam'=>'Dare'),
                             array('lun' => 18,'nam'=>'Avere'),
                             array('lun' => 20,'nam'=>'Saldo')
                             )
              );

$pdf = new Report_template();
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(39);
$pdf->SetFooterMargin(20);
$config = new Config;
$pdf->AddPage('P',$config->getValue('page_format'));

$totmovpre =0.00;
$totmovsuc =0.00;
$ctrlmopre = 0;
$saldo = 0.00;
$pdf->SetFont('helvetica','',8);

while ($movimenti = gaz_dbi_fetch_array($result)){
    $giomov = substr($movimenti['datreg'],8,2);
    $mesmov = substr($movimenti['datreg'],5,2);
    $annmov = substr($movimenti['datreg'],0,4);
    $giodoc = substr($movimenti['datdoc'],8,2);
    $mesdoc = substr($movimenti['datdoc'],5,2);
    $anndoc = substr($movimenti['datdoc'],0,4);
    $utsmov= mktime(0,0,0,$mesmov,$giomov,$annmov);
    $utsdoc= mktime(0,0,0,$mesdoc,$giodoc,$anndoc);
    $datamov = date("d-m-Y",$utsmov);
        if ($anndoc > 0){
           $datadoc = date("d-m-Y",$utsdoc);
        } else {
           $datadoc = '';
        }
    if ($movimenti["darave"] == 'D')    {
        $dare = number_format($movimenti["import"],2, '.', '');
        $avere = 0;
        $saldo += $movimenti["import"];
    } else {
        $avere = number_format($movimenti["import"],2, '.', '');
        $dare = 0;
        $saldo -= $movimenti["import"];
    }
    $pdf->Cell(20,4,$datamov,1,0,'L');
    $pdf->Cell(75,4,$movimenti['descri'],1,0,'L');
    if ($movimenti['numdoc'] > 0) {
        $pdf->Cell(18,4,$movimenti['numdoc']."/".$movimenti['seziva'],1,0,'C');
        $pdf->Cell(18,4,$datadoc,1,0,'R');
    } else {
        $pdf->Cell(36,4,'',1);
    }
    if ($dare != 0) $pdf->Cell(18,4,$dare,1,0,'R'); else $pdf->Cell(18,4,'',1);
    if ($avere != 0) $pdf->Cell(18,4,$avere,1,0,'R'); else $pdf->Cell(18,4,'',1);
    $pdf->Cell(20,4,gaz_format_number($saldo),1,1,'R');
}

if (isset($_GET["dest"]) && $_GET["dest"]=='E'){ // � stata richiesta una e-mail
   $dest = 'S';     // Genero l'output pdf come stringa binaria
   // Costruisco oggetto con tutti i dati del file pdf da allegare
   $content = new StdClass; //PHP Strict standards: Creating default object from empty value
   $content->name = 'Estratto_conto_al_'.intval($_GET["giornfin"]).'_'.intval($_GET["mesfin"]).'_'.intval($_GET["annfin"]).'.pdf';
   $content->string = $pdf->Output('Estratto_conto_al_'.intval($_GET["giornfin"]).'_'.intval($_GET["mesfin"]).'_'.intval($_GET["annfin"]).'.pdf', $dest);
   $content->encoding = "base64";
   $content->mimeType = "application/pdf";
   $admin_aziend['doc_name']= str_replace('_', ' ', $content->name);
   $gMail = new GAzieMail();
   $gMail->sendMail($admin_aziend,$admin_aziend,$content,$conto);
} else { // va all'interno del browser
   $pdf->Output();
}
?>
