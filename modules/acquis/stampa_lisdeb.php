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
$admin_aziend=checkAdmin();

require('../../config/templates/report_template.php');


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

//procedura per la creazione dell'array dei conti con saldo diverso da 0 e ordinati per nome...
$sqlquery= "SELECT codcon, SUM(import) AS somma, darave FROM ".$gTables['rigmoc'].
           " LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes
             LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['rigmoc'].".codcon = ".$gTables['clfoco'].".codice
             LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra
             WHERE datreg between ".$year_start.$month_start.$day_start." AND ".$year_end.$month_end.$day_end." AND codcon LIKE '".$admin_aziend['masfor']."%' AND caucon <> 'CHI' AND caucon <> 'APE' OR (caucon = 'APE' AND codcon LIKE '".$admin_aziend['masfor']."%' AND datreg LIKE '".$year_start.
            "%') GROUP BY codcon, darave ORDER BY ragso1, codcon, darave";
$rs_castel = gaz_dbi_query($sqlquery);
$ctrlcodcon = 0;
$ctrlsaldo = 0;
$rigo = 1;
$conti=array();
while($castel = gaz_dbi_fetch_array($rs_castel)) {
        if ($castel["codcon"] != $ctrlcodcon and $ctrlcodcon > 0) {
            if ($ctrlsaldo != 0) {
               $conti[$rigo]=$ctrlcodcon;
               $rigo++;
               $ctrlsaldo=0;
            }
        }
        if ($castel["darave"] == 'D') {
            $ctrlsaldo += $castel["somma"];
        } else {
            $ctrlsaldo -= $castel["somma"];
        }
        $ctrlcodcon = $castel["codcon"];
}
if ($ctrlsaldo != 0) {
           $conti[$rigo]=$ctrlcodcon;
}
//fine creazione array conti diversi da zero

$emissione = 'Debiti verso i fornitori per il periodo '.$day_start.'/'.$month_start.'/'.$year_start.' - '.$day_end.'/'.$month_end.'/'.$year_end;

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
$config = new Config;
$pdf->SetTopMargin(39);
$pdf->SetFooterMargin(20);
$pdf->AddPage('P',$config->getValue('page_format'));
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));

$ctrlfor=0;
$nummov = 0;
$totmovpre =0.00;
$totmovsuc =0.00;
$ctrlmopre = 0;
foreach ($conti as $value) {
    //recupero tutti i movimenti contabili dei fornitori tranne quelli di chiusura e apertura di fine/inizio anno del periodo selezionato...
    $result=gaz_dbi_dyn_query( $gTables['tesmov'].".*,".$gTables['rigmoc'].".*,ragso1,telefo,cell",
                               $gTables['rigmoc'].' LEFT JOIN '.$gTables['tesmov'].'
                               ON '.$gTables['rigmoc'].'.id_tes='.$gTables['tesmov'].'.id_tes
                               LEFT JOIN '.$gTables['clfoco'].'
                               ON '.$gTables['rigmoc'].'.codcon='.$gTables['clfoco'].'.codice
                               LEFT JOIN '.$gTables['anagra'].'
                               ON '.$gTables['anagra'].'.id='.$gTables['clfoco'].'.id_anagra',
                               "datreg BETWEEN '".intval($_GET["annini"])."0101' AND '".
                               intval($_GET["annfin"])."1231' AND codcon=".$value.
                               " AND caucon <> 'CHI' AND caucon <> 'APE'  OR
                               (caucon = 'APE' AND codcon=".$value." AND YEAR(datreg)=".intval($_GET["annini"]).")",
                               "datreg");
    while ($movimenti = gaz_dbi_fetch_array($result)) {
        if($ctrlfor != $movimenti["codcon"]){
            $pdf->SetFont('times','B',11);
            $pdf->Cell(187,6,$movimenti['ragso1'].' Tel. '.$movimenti['telefo'].' cell. '.$movimenti['cell'],1,1,'L',1);
            $pdf->SetFont('helvetica','',8);
            $saldo = 0.00;
        }
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
        if($movimenti["darave"] == 'D')
            {
            $dare = number_format($movimenti["import"],2, '.', '');
            $avere = 0;
            $saldo += $movimenti["import"];
        } else {
            $avere = number_format($movimenti["import"],2, '.', '');
            $dare = 0;
            $saldo -= $movimenti["import"];
        }
        $pdf->Cell(20,4,$datamov,1,0,'L');
        $pdf->Cell(75,4,$movimenti['descri'],1,0,'L',0,'',1);
        if ($movimenti['numdoc'] > 0) {
           $pdf->Cell(18,4,$movimenti['numdoc']."/".$movimenti['seziva'],1,0,'C');
        } else {
           $pdf->Cell(18,4,'',1);
        }
        $pdf->Cell(18,4,$datadoc,1,0,'R');
        if ($dare != 0) $pdf->Cell(18,4,$dare,1,0,'R'); else $pdf->Cell(18,4,'',1);
        if ($avere != 0) $pdf->Cell(18,4,$avere,1,0,'R'); else $pdf->Cell(18,4,'',1);
        $pdf->Cell(20,4,gaz_format_number(-$saldo),1,1,'R');
        $ctrlfor=$movimenti["codcon"];
        }
        }
$pdf->Output();
?>