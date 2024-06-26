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

if ($admin_aziend['ivam_t']== 'M') {
    $mesetrim='Mese';
} else {
    $mesetrim='Trimestre';
}
$intesta1=$admin_aziend['ragso1'].$admin_aziend['ragso2'];
$intesta2=$admin_aziend['indspe'];
$intesta3=sprintf("%05d",$admin_aziend['capspe']).' '.$admin_aziend['citspe'].' ('.$admin_aziend['prospe'].')';
$intesta4='P.I. '.$admin_aziend['pariva'];
$intesta5=$admin_aziend['luonas'].' '.substr($admin_aziend['datnas'],8,2).'-'.substr($admin_aziend['datnas'],5,2).'-'.substr($admin_aziend['datnas'],0,4);



$pdf=new TCPDF();
$pdf->Open();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetTopMargin(5);
$pdf->SetHeaderMargin(5);
$pdf->AddPage();
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
$pdf->SetFont('helvetica','B',10);
$pdf->Image('@'.$admin_aziend['image'],10,8,20,0);
$pdf->Cell(25);
$pdf->Cell(103,6,$intesta1,0,0,'L');
$pdf->Cell(62,6,'ACQUISTI DI CARBURANTE','LTR',1,'C',1);
$pdf->SetFont('helvetica','',8);
$pdf->Cell(25);
$pdf->Cell(103,6,$intesta2,0,0,'L');
$pdf->SetFont('helvetica','B',10);
$pdf->Cell(62,6,'PER AUTOTRAZIONE','LBR',1,'C',1);
$pdf->SetFont('helvetica','',8);
$pdf->Cell(25);
$pdf->Cell(103,4,$intesta3,0,0,'L');
$pdf->SetFont('helvetica','',10);
$pdf->Cell(31,4,'Scheda n.','LR',0,'L');
$pdf->Cell(31,4,'Registrata il','R',1,'L');
$pdf->SetFont('helvetica','',8);
$pdf->Cell(25);
$pdf->Cell(103,4,$intesta4,0,0,'L');
$pdf->Cell(31,6,'','LBR',0);
$pdf->Cell(31,6,'','BR',1);
$pdf->SetFont('helvetica','',10);
$pdf->Cell(104,4,'Targa o telaio del veicolo','LTR',0,'L');
$pdf->Cell(62,4,$mesetrim,'TR',0,'L');
$pdf->Cell(24,4,'Anno','R',1,'L');
$pdf->SetFont('helvetica','',10);
$pdf->Cell(104,7,'','LB',0);
$pdf->Cell(62,7,'','LBR',0);
$pdf->Cell(6,7,'','BR',0);
$pdf->Cell(6,7,'','BR',0);
$pdf->Cell(6,7,'','BR',0);
$pdf->Cell(6,7,'','BR',1);
$pdf->SetFont('helvetica','',10);
$pdf->Cell(104,4,'Intestatario del veicolo','LTR',0,'L');
$pdf->Cell(86,4,'Luogo e data di nascita','R',1,'L');
$pdf->SetFont('helvetica','',10);
$pdf->Cell(104,7,'','LB',0);
$pdf->Cell(86,7,$intesta5,'LBR',1,'C');
$pdf->Ln(5);
$pdf->SetFont('helvetica','',10);
$pdf->Cell(10,4,'N.',1,0,'L',1);
$pdf->Cell(23,4,'Data',1,0,'L',1);
$pdf->Cell(20,4,'Tipo(*)',1,0,'L',1);
$pdf->Cell(20,4,'Quant.',1,0,'L',1);
$pdf->Cell(20,4,'Prezzo',1,0,'L',1);
$pdf->Cell(40,4,'Firma',1,0,'L',1);
$pdf->Cell(57,4,'Timbro dati anagrafici e fiscali',1,1,'L',1);
for ($i = 1; $i < 12; $i++) {
    $pdf->SetFont('helvetica','B',10);
    $pdf->Cell(10,13,$i,1,0);
    $pdf->Cell(23,13,'',1,0);
    $pdf->Cell(20,13,'',1,0);
    $pdf->Cell(20,13,'',1,0);
    $pdf->Cell(20,13,'',1,0);
    $pdf->Cell(40,13,'',1,0);
    $pdf->Cell(57,13,'',1,1);
    $pdf->Ln(5);
}
$pdf->SetFont('helvetica','',10);
$pdf->Cell(35,4,'Imponibile','LTR',0,'L');
$pdf->Cell(10,4,'IVA','T',0,'L');
$pdf->Cell(10,4,'____','T',0,'L');
$pdf->Cell(10,4,'%','T',0,'L');
$pdf->Cell(35,4,'Totale','LTR',0,'L',1);
$pdf->Cell(42,4,'Km/Ore Iniziali','TR',0,'L');
$pdf->Cell(48,4,'Km/Ore Fine '.$mesetrim,'TR',1,'L');
$pdf->Cell(35,10,'','LBR',0,'L');
$pdf->Cell(30,10,'','B',0,'L');
$pdf->Cell(35,10,'','LBR',0,'L',1);
$pdf->Cell(42,10,'','BR',0,'L');
$pdf->Cell(48,10,'','BR',1,'L');
$pdf->Output();
?>