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

// Antonio Germani    - STAMPA COPERTINA ANNUALE QUADERNO DI CAMPAGNA -

require("../../library/include/datlib.inc.php");
require_once('../../vendor/tecnickcom/tcpdf/tcpdf.php');
$admin_aziend=checkAdmin();

if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
    gaz_set_time_limit (0);
}

$rs_azienda = gaz_dbi_dyn_query('*', $gTables['aziend'], intval($_SESSION['company_id']), 'codice DESC', 0, 1);

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// remove default footer and header
$pdf->setPrintFooter(false);
$pdf->setPrintHeader(false);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);

// add an horizontal page (Landscape)
$pdf->AddPage('L', 'A4');

$imgdata = $admin_aziend['image'];
$intesta1 = $admin_aziend['ragso1'] . ' ' . $admin_aziend['ragso2'];
$intesta2 = $admin_aziend['indspe'] . ' ' . sprintf("%05d", $admin_aziend['capspe']) . ' ' . $admin_aziend['citspe'] . ' (' . $admin_aziend['prospe'] . ')';
$intesta4 =  ' C.F.:' . $admin_aziend['codfis'] . ' P.I.:' . $admin_aziend['pariva'];
$intesta3 = 'Tel.' . $admin_aziend['telefo'] .' E-mail '.$admin_aziend['e_mail'];

$intesta=$intesta1."\n".$intesta2."\n".$intesta3."\n".$intesta4;
// The '@' character is used to indicate that follows an image data stream and not an image file name
$pdf->Image('@'.$imgdata, 20, 40, 0,40, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
// set font
$pdf->SetFont('dejavusans', 'BI', 40);
// set color for background
$pdf->SetFillColor(220, 255, 220);

// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
$pdf->MultiCell(0, 0, 'Giornale di magazzino', 1, 'C', 1, 0, '', 110, true, 0, false, true, 40);
if (isset($_GET['sb']) AND strlen($_GET['sb'])>0) {
	$pdf->MultiCell(0, 0, $_GET['sb'], 1, 'C', 1, 0, '', 130, true, 0, false, true, 18,'M', true);
}
$pdf->MultiCell(100, 0, substr($_GET['ds'],4), 1, 'C', 0, 0, 100, 150, true);

$pdf->SetFont('times', 'BI', 18);
$pdf->setCellPaddings(80, 2, 2, 2);
$pdf->MultiCell(0, 41, $intesta, 1, 'L', 0, 0, '', 39, true, 0, false, true, 40);

$pdf->Output();
?>
