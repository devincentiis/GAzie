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
require("../../library/include/ciftolet.inc.php");


$calc = new Compute;

if (! isset($_GET['id_tes'])){
    header("Location: report_effett.php");
    exit;
}

if ($_GET['id_tes'] == 'SEL') {
    $where = "scaden BETWEEN '".intval($_GET['scaini'])."' AND '".intval($_GET['scafin'])."' AND progre BETWEEN '".intval($_GET['proini'])."' AND '".intval($_GET['profin'])."'";
} else {
    $where = "id_tes = ".intval($_GET['id_tes']);
}

$logo=$admin_aziend['image'];
$result = gaz_dbi_dyn_query("*", $gTables['effett'], $where,"tipeff, id_tes");
$pdf=new TCPDF();
$pdf->Open();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(1,1);
$pdf->AddPage();
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
$nuw = new numberstowords_it();
//azzero il contatore di effetti
$numefftot = 0;
$passo=100;
$anagrafica = new Anagrafica();
while ($effetto = gaz_dbi_fetch_array($result))
    {
    $client = $anagrafica->getPartner($effetto['clfoco']);
    $banapp = gaz_dbi_get_row($gTables['banapp'],"codice",$effetto['banapp']);
    $banapp=($banapp)?$banapp:array('descri'=>'','codabi'=>'','codcab'=>'','codpro'=>'','locali'=>'');
    $banacc = $anagrafica->getPartner($effetto['banacc']);
    $impwords = $nuw->euro2assegno($effetto['impeff']);
    if ($effetto['salacc'] == 'S')
        $salcon = 'a SALDO della Ns. Fattura N. ';
    else    $salcon = 'in CONTO della Ns. Fattura N. ';
    if ($client['pariva'] > 0)
        $cfpiva = 'P.I. '.$client['pariva'];
    elseif (! empty ($client['codfis']))
        $cfpiva = 'C.F. '.$client['codfis'];
    else    $cfpiva = '';
    //formatto le date
    $scadenza = substr($effetto['scaden'],8,2).'-'.substr($effetto['scaden'],5,2).'-'.substr($effetto['scaden'],0,4);
    $datafatt = substr($effetto['datfat'],8,2).'-'.substr($effetto['datfat'],5,2).'-'.substr($effetto['datfat'],0,4);
    if ($numefftot == 3)
        {
        $pdf->AddPage();
        $numefftot = 0;
        }
    //a secondo del tipo di effetto stampo il relativo modulo
    $ridmav=($effetto['tipeff']=='V')?'mav':'rid';
    switch($effetto['tipeff'])
    {
    // modulo delle ricevute bancarie
    case "B":
        $pdf->SetFont('helvetica','',7);
        $pdf->Rect(5,5+$passo*$numefftot,200,50);
        $pdf->Rect(5,60+$passo*$numefftot,65,30);
        $pdf->Rect(71,60+$passo*$numefftot,85,30);
        $pdf->Rect(157,60+$passo*$numefftot,48,30);
        $pdf->Rect(75,20+$passo*$numefftot,125,10,'DF');
        $pdf->SetY(30+$numefftot*$passo);
        $pdf->Image('@'.$logo,6,6+$passo*$numefftot,30,0);
        $pdf->Cell(50,3,$admin_aziend['ragso1'],0,2,'L');
        $pdf->Cell(50,3,$admin_aziend['ragso2'],0,2,'L');
        $pdf->Cell(50,3,$admin_aziend['indspe'].' Tel.'.$admin_aziend['telefo'],0,2,'L');
        $pdf->Cell(50,3,$admin_aziend['capspe'].' '.$admin_aziend['citspe'].' ('.$admin_aziend['prospe'].')',0,2,'L');
        $pdf->Cell(50,3,'P.I. '.$admin_aziend['pariva'],0,2,'L');
        $pdf->Cell(50,3,'C.F. '.$admin_aziend['codfis'],0,0,'L');
        $pdf->SetXY(70,5+$numefftot*$passo);
        $pdf->SetFont('helvetica','B',10);
        $pdf->Cell(50,10,'RICEVUTA N. '.$effetto['progre'],0,0,'L');
        $pdf->SetFont('helvetica','',8);
        $pdf->MultiCell(22,10,'Data di pagamento','LTB','L',1);
        $pdf->SetXY(140,5+$numefftot*$passo);
        $pdf->SetFont('helvetica','B',10);
        $pdf->Cell(20,10,$scadenza.' ','RTB',0,'R',1);
        $pdf->Cell(10,10,'euro',0,0,'C');
        $pdf->Cell(5,10,'€','LTB',0,'C',1);
        $pdf->Cell(25,10,$effetto['impeff'],'RTB',1,'R',1);
        $pdf->SetFont('helvetica','',8);
        $pdf->SetXY(75,20+$numefftot*$passo);
        $pdf->Cell(25,3,'riceviamo la somma di ',0,2,'L');
        $pdf->SetFont('helvetica','B',10);
        $pdf->Cell(25,7,$impwords,0,2,'L');
        $pdf->SetFont('helvetica','',8);
        $pdf->Cell(25,3,'',0,2);
        $pdf->Cell(25,12,'versata a mezzo ',0,0,'L');
        $pdf->SetFont('helvetica','',10);
        $pdf->Cell(50,12,($banacc?$banacc['ragso1']:''),0,1,'L');
        $pdf->Cell(170,8,$salcon.$effetto['numfat'].'/'.$effetto['seziva'].' del '.$datafatt.' di € '.$effetto['totfat'],0,1,'R');
        $pdf->SetFont('helvetica','',7);
        $pdf->Cell(50,10,'INCASSARE TRAMITE',0,0,'C');
        $pdf->Cell(60,10,'EGR.SIG./SPETT.LE',0,1,'R');
        $pdf->SetFont('helvetica','',8);
        $pdf->Cell(60,5,substr($banapp['descri'],0,33),0,0,'L');
        $pdf->Cell(2,5);
        $pdf->Cell(80,5,$client['ragso1'],0,1,'L');
        $pdf->Cell(60,5,'ABI '.$banapp['codabi'],0,0,'L');
        $pdf->Cell(2,5);
        $pdf->Cell(80,5,$client['ragso2'],0,1,'L');
        $pdf->Cell(60,5,'CAB '.$banapp['codcab'],0,0,'L');
        $pdf->Cell(2,5);
        $pdf->Cell(90,5,$client['indspe'],0,0,'L');
        $pdf->Cell(80,5,'__________________',0,1,'L');
        $pdf->Cell(60,5,$banapp['locali'].' ('.$banapp['codpro'].')',0,0,'L');
        $pdf->Cell(2,5);
        $pdf->Cell(90,5,$client['capspe'].' '.$client['citspe'].' ('.$client['prospe'].')',0,0,'L');
        $pdf->Cell(35,5,' firma ',0,1,'C');
        $pdf->Cell(62,5);
        $pdf->Cell(80,5,$cfpiva,0,1,'L');
    break;
    // modulo delle cambiali tratte
    case "T":
        $calc->payment_taxstamp($effetto['impeff'],$admin_aziend['perbol']);
        $impbol = $calc->pay_taxstamp;
        $pdf->Image('cambiale-tratta.jpg',0,5+$passo*$numefftot,210);
        $pdf->SetXY(67,9+$numefftot*$passo);
        $pdf->SetFont('times','',14);
        $pdf->Cell(60,10,$admin_aziend['citspe'].', '.$datafatt);
        $pdf->SetXY(165,12+$numefftot*$passo);
        $pdf->Cell(67,10,gaz_format_number($effetto['impeff']));
        $pdf->SetXY(85,21+$numefftot*$passo);
        $pdf->Cell(50,10,$scadenza);
        $pdf->SetXY(76,34+$numefftot*$passo);
        $pdf->Cell(120,10,$admin_aziend['ragso1'].' '.$admin_aziend['ragso2']);
        $pdf->SetXY(90,45+$numefftot*$passo);
        $pdf->Cell(140,10,substr($impwords,4,99));
        $pdf->SetXY(5,60+$numefftot*$passo);
        $pdf->SetFont('helvetica','B',7);
        $pdf->Cell(71,6,substr($banapp['descri'],0,34));
        $pdf->Cell(80,6,$client['ragso1'].' '.$client['ragso2'],0,1,'L');
        $pdf->SetX(5);
        $pdf->Cell(71,6,'ABI: '.$banapp['codabi']);
        $pdf->Cell(80,6,$client['codfis'],0,1,'L');
        $pdf->SetX(5);
        $pdf->Cell(71,6,'CAB: '.$banapp['codcab']);
        $pdf->Cell(80,6,$client['indspe'],0,1,'L');
        $pdf->SetX(5);
        $pdf->Cell(71,6,$banapp['locali'].' ('.$banapp['codpro'].')');
        $pdf->Cell(80,6,$client['capspe'].' '.$client['citspe'].' ('.$client['prospe'].')',0,1,'L');
        $pdf->SetXY(5,90+$numefftot*$passo);
        $pdf->Cell(165,4,'Cambiale-tratta n.'.$effetto['progre'].' emessa '.$salcon.$effetto['numfat'].'/'.$effetto['seziva'].' del '.$datafatt.' di € '.$effetto['totfat'],'LTB');
        $pdf->Cell(37,4,'bolli a tergo €  '.gaz_format_number($impbol),'RTB',1,'R');
    break;
    // modulo delle cambiali tratte
    case "V":
    case "I":
        $calc->payment_taxstamp($effetto['impeff'],$admin_aziend['perbol']);
        $impbol = $calc->pay_taxstamp;
        $pdf->Image($ridmav.'.jpg',0,5+$passo*$numefftot,210);
        $pdf->SetXY(51,13+$numefftot*$passo);
        $pdf->SetFont('helvetica','B',7);
        $pdf->Cell(48,3,$admin_aziend['ragso1'].' '.$admin_aziend['ragso2'],'', 0, 'L', 0, '', 1);
        $pdf->Cell(9);
        $pdf->Cell(24,3,gaz_format_number($effetto['impeff']));
        $pdf->Cell(48,3,$admin_aziend['ragso1'].' '.$admin_aziend['ragso2'],'', 0, 'L', 0, '', 1);
        $pdf->Cell(9);
        $pdf->Cell(24,3,gaz_format_number($effetto['impeff']),0,1);
        $pdf->Cell(41,3);
        $pdf->Cell(81,3,$admin_aziend['indspe']);
        $pdf->Cell(81,3,$admin_aziend['indspe'],0,1);
        $pdf->Cell(41,3);
        $pdf->Cell(57,3,$admin_aziend['capspe'].' '.$admin_aziend['citspe'].' ('.$admin_aziend['prospe'].')');
        $pdf->Cell(24,3,$scadenza);
        $pdf->Cell(57,3,$admin_aziend['capspe'].' '.$admin_aziend['citspe'].' ('.$admin_aziend['prospe'].')');
        $pdf->Cell(24,3,$scadenza,0,1);
        $pdf->SetXY(70,25+$numefftot*$passo);
        $pdf->Cell(91,3,$effetto['clfoco']);
        $pdf->Cell(20,3,$effetto['clfoco'],0,1);
        $pdf->Cell(40,3);
        $pdf->Cell(90,3,$client['ragso1'].' '.$client['ragso2']);
        $pdf->Cell(50,3,$client['ragso1'].' '.$client['ragso2'],0,1);
        $pdf->Cell(40,3);
        $pdf->Cell(90,3,$client['indspe']);
        $pdf->Cell(50,3,$client['indspe'],0,1);
        $pdf->Cell(40,3);
        $pdf->Cell(90,3,$client['capspe'].' '.$client['citspe'].' ('.$client['prospe'].')');
        $pdf->Cell(50,3,$client['capspe'].' '.$client['citspe'].' ('.$client['prospe'].')',0,1);
        $pdf->Cell(40,3);
        $pdf->Cell(90,3,$cfpiva);
        $pdf->Cell(50,3,$cfpiva,0,1);
        $pdf->SetXY(97,71+$numefftot*$passo);
        $pdf->Cell(78,3,substr($banapp['descri'],0,30));
        $pdf->Cell(78,3,substr($banapp['descri'],0,30),0,1);
        $pdf->Cell(87,3);
        $pdf->Cell(78,3,'ABI: '.$banapp['codabi']);
        $pdf->Cell(78,3,'ABI: '.$banapp['codabi'],0,1);
        $pdf->Cell(87,3);
        $pdf->Cell(78,3,'CAB: '.$banapp['codcab']);
        $pdf->Cell(78,3,'CAB: '.$banapp['codcab'],0,1);
        $pdf->Cell(50,3);
        $pdf->Cell(37,3,$scadenza);
        $pdf->Cell(78,3,$banapp['locali'].' ('.$banapp['codpro'].')');
        $pdf->Cell(78,3,$banapp['locali'].' ('.$banapp['codpro'].')',0,1);
        $pdf->SetXY(10,20+$numefftot*$passo);
        $pdf->Cell(38,4,(($effetto['tipeff']=='I')?'RID':'MAV').' n.'.$effetto['progre'].' emesso ','LTR',1);
        $pdf->Cell(38,4,$salcon,'LR',1);
        $pdf->Cell(38,4,$effetto['numfat'].'/'.$effetto['seziva'].' del '.$datafatt,'LR',1);
        $pdf->Cell(38,4,'di € '.$effetto['totfat'],'LBR',1);
    break;
    }
    $numefftot++;
    }
$pdf->Output();
?>