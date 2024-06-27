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
$title = "       Situazione magazzino";

if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
}

require("../../config/templates/report_template.php");
require("lang.".$admin_aziend['lang'].".php");
$passo=1000;
$limit=0;


if ( gaz_dbi_get_row($gTables['company_config'], 'var', 'tipo_composti')['val']=='STD') {
  $result = gaz_dbi_dyn_query("*", $gTables['artico'], "good_or_service!=1", $orderby, $limit, $passo);
}  else {
  $result = gaz_dbi_dyn_query("*", $gTables['artico'], "good_or_service=0", $orderby, $limit, $passo);
}


$pdf = new Report_template();
$filename = $title.'_'.date("Ymd").'.pdf';
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(32);
$config = new Config;
$gForm = new magazzForm();
$pdf->SetFont('helvetica','',10);

$light = array(
   /*'T' => array('width' => 1, 'color' => array(255,255,255), 'dash' => 0, 'cap' => 'butt'),
   'R' => array('width' => 2, 'color' => array(255,255,255), 'dash' => 0, 'cap' => 'round'),*/
   'B' => array('width' => 0, 'color' => array(200,200,200), 'solid' => '1,15', 'cap' => 'butt'),
   //'L' => array('width' => 4, 'color' => array(255,255,255), 'dash' => 0, 'cap' => 'butt'),
);
$heavy = array (
    'TRBL' => array('width' => 0, 'color' => array(0,0,0), 'solid' => 1, 'cap' => 'butt'),
);

$mval['q_g']=0;
$i=0;
while ($r = gaz_dbi_fetch_array($result)) {
    $totale = 0;
    $ordinatif = $gForm->get_magazz_ordinati($r['codice'], "AOR");
    $ordinatic = $gForm->get_magazz_ordinati($r['codice'], "VOR");
    $mv = $gForm->getStockValue(false, $r['codice']);
    $magval = array_pop($mv);
    $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
	if (isset ($magval['q_g']) && round($magval['q_g'],6) == "-0") { // Antonio Germani - se si crea erroneamente un numero esponenziale negativo forzo la quantità a zero
		$magval['q_g']=0;
	}
    $totale = ($magval['q_g']-$ordinatic)+$ordinatif;
	if (isset($_GET['esc']) && $_GET['esc']=="escludi" AND $totale<=0){
	} else {
		if ( $i % 30 == 0 ) {
			$pdf->AddPage('L',"A4");
			$pdf->Cell(35,5,"Codice",$heavy,0,'L');
			$pdf->Cell(100,5,"Descrizione",$heavy,0,'L');
			$pdf->Cell(15,5,"UmV",$heavy,0,'C');
			$pdf->Cell(30,5,"Pezzi in stock",$heavy,0,'R');
			$pdf->Cell(30,5,"Ordinato cliente",$heavy,0,'R');
			$pdf->Cell(30,5,"Ordinato fornitore",$heavy,0,'R');
			$pdf->Cell(30,5,"Totale",$heavy,1,'R');
		}
		$pdf->SetTextColor(0);
		if ($totale<=0.1||$magval['q_g']<=0.1){
			$pdf->SetTextColor(255,140,0);
		}
		if ($totale<=0){
			$pdf->SetTextColor(255,0,0);
		}
		$pdf->Cell(35,5,$r['codice'],$light,0,'L');
		$pdf->Cell(100,5,$r['descri'],$light,0,'L', 0, '', 1);
		$pdf->Cell(15,5,$r['unimis'],$light,0,'C');
		$pdf->Cell(30,5,gaz_format_quantity($magval['q_g'],1,3),$light,0,'R');
		$pdf->Cell(30,5,gaz_format_quantity($ordinatic,1,3),$light,0,'R');
		$pdf->Cell(30,5,gaz_format_quantity($ordinatif,1,3),$light,0,'R');
		$pdf->Cell(30,5,gaz_format_quantity($totale,1,3),$light,1,'R');
		$i++;
	}
}
$pdf->SetFont('helvetica','B',9);
$pdf->Output($filename);
?>
