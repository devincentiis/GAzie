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
$date = new DateTime();

$luogo_data = $admin_aziend['citspe'].", lì ".date_format($date, 'd/m/Y');


$anagrafica = new Anagrafica();

$id_distinta=(isset($_GET['id_distinta']))?intval($_GET['id_distinta']):false; // se è stata passata la referenza id_doc ad una distinta già generata è una semplice ristampa

if ($id_distinta){ // chiedo una distinta già prodotta
    $where = "id_distinta = ".$id_distinta;
	$effdata=gaz_dbi_get_row($gTables['effett'], 'id_distinta',$id_distinta);
	$efffile=gaz_dbi_get_row($gTables['files'], 'id_doc',$id_distinta);
    $title = 'Stampa distinta contenuta nel file "'.$efffile['title'].'"';    
    $filename = $efffile['title'];
    $banacc = $anagrafica->getPartner($effdata['banacc']);
} else {
    $filename = 'TRATTEdel_'.date_format($date, 'Y-m-d').'.pdf';
    $banacc = $anagrafica->getPartner(intval($_GET['banacc']));
    $where = "(".$gTables['effett'] . ".id_distinta = 0 OR id_distinta IS NULL) AND tipeff = 'T' AND scaden BETWEEN '".substr($_GET['scaini'],0,10)."' AND '".substr($_GET['scafin'],0,10)."' AND progre BETWEEN '".intval($_GET['proini'])."' AND '".intval($_GET['profin'])."'";
    gaz_dbi_query("INSERT INTO ". $gTables['files'] . " SET table_name_ref='effett', id_ref=".intval($_GET['banacc']).", item_ref='distinta', extension='pdf', title='".$filename."', custom_field='{\"vendit\":{\"credttm\":\"".date_format($date, 'Y-m-d')."\",\"tipeff\":\"T\",\"scaini\":\"".substr($_GET['scaini'],0,10)."\",\"scafin\":\"".substr($_GET['scafin'],0,10)."\",\"proini\":\"".intval($_GET['proini'])."\",\"profin\":\"".intval($_GET['profin'])."\"}}'");
    $first_id_distinta=gaz_dbi_last_id();
    $title = 'Distinta effetti dal '.gaz_format_date($_GET['scaini']).' al '.gaz_format_date($_GET['scafin']);    
}

$descbanacc = $banacc['ragso1'];

$title = array('luogo_data'=>$luogo_data,
               'title'=>$title,
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
$totaletipo=0;
$totaleff=0.00;
$totnumeff=0;
$pdf->SetFont('helvetica','',8);
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));

$result = gaz_dbi_dyn_query("*", $gTables['effett'],$where,"tipeff, scaden, id_tes");
while ($r = gaz_dbi_fetch_array($result)) {
    if ($r["tipeff"] <> $ctrltipo){
        if ($totaletipo>=0.01) $pdf->Cell(190,4,$totnumtipo.' '.$descreff.' per un totale di '.gaz_format_number($totaletipo),1,1,'R',1);
        $totaletipo = 0.00;
        $totnumtipo = 0;
        switch($r['tipeff'])
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
    $totaleff += $r["impeff"];
    $totaletipo += $r["impeff"];
    $cliente = $anagrafica->getPartner($r['clfoco']);
    $banapp = gaz_dbi_get_row($gTables['banapp'],"codice",$r['banapp']);
    $banapp=($banapp)?$banapp:array('descri'=>'','codabi'=>'','codcab'=>'','codpro'=>'','locali'=>'');
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
    $pdf->Cell(100,4,$cliente["indspe"].' '.sprintf("%05d",$cliente["capspe"]).' '.$cliente["citspe"].' ('.$cliente["prospe"].') P.IVA '.$cliente["pariva"],0,0,'L',0,'',1);
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
    //aggiorno il db solo se di tipo "T" tratte
    if ($r["tipeff"] == 'T') {
        if ( $r['banacc'] < 100000000 && intval($_GET['banacc']) > 100000000 ) {
            gaz_dbi_put_row($gTables['effett'], "id_tes",$r["id_tes"],"banacc",intval($_GET['banacc']));
        }
        if (!$id_distinta) { // se non viene passato il riferimento alla distinta vuol dire che l'ho generata e quindi metto il riferimento sull'effeto
            gaz_dbi_query("UPDATE ". $gTables['effett']." SET id_distinta=".$first_id_distinta.", banacc=".intval($_GET['banacc'])." WHERE id_tes=".$r['id_tes']);
        }
    }
    $ctrltipo = $r["tipeff"];
}
$pdf->setRiporti();
$pdf->Cell(190,4,$totnumtipo.' '.$descreff.' per un totale di € '.gaz_format_number($totaletipo),1,1,'R',1);
$pdf->SetFont('helvetica','B',12);
$pdf->Cell(160,10,'TOTALE DEGLI EFFETTI VERSATI PRESSO '.strtoupper($descbanacc).': ',1,0,'R',0,'',1);
$pdf->Cell(30,10,'€ '.gaz_format_number($totaleff),1,1,'R',1);
if ($id_distinta) { // è una ristampa quindi faccio solo l'output a video 
    $pdf->Output($filename);
} else {
    $cont = $pdf->Output($filename, 'S');
    $h=fopen(DATA_DIR . "files/" .$admin_aziend['company_id']."/doc/". $first_id_distinta . ".pdf", 'x+');
    fwrite($h,$cont);
    fclose($h);
    header("Content-type: application/pdf");
    header("Content-Disposition: inline; filename=".$filename);
    @readfile(DATA_DIR . "files/" .$admin_aziend['company_id']."/doc/". $first_id_distinta . ".pdf");
}
?>