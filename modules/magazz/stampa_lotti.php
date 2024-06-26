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
//        >>>>>> Antonio Germani -- STAMPA Lotti  <<<<<<

require("../../library/include/datlib.inc.php");
require("../../modules/vendit/lib.function.php");
$admin_aziend=checkAdmin();

if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
}

require("../../config/templates/report_template.php");
require("lang.".$admin_aziend['lang'].".php");
$passo=1000;
$limit=0;
$lm = new lotmag;
$gForm = new magazzForm;
$codice = filter_input(INPUT_GET, 'codice');

$des = gaz_dbi_get_row($gTables['artico'], 'codice', $codice);
$title = "       Movimenti lotti disponibili per articolo ".$codice." - ".$des['descri'];

$lm->getAvailableLots($codice,0,'',1,true);

$pdf = new Report_template();
$filename = $title.'_'.date("Ymd").'.pdf';
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(36);

$pdf->SetFont('helvetica','',10);

$light = array(
    'B' => array('width' => 0, 'color' => array(200,200,200), 'solid' => '1,15', 'cap' => 'butt'),
);
$heavy = array (
    'TRBL' => array('width' => 0, 'color' => array(0,0,0), 'solid' => 1, 'cap' => 'butt'),
);
$i=0;
$orderby = "datdoc,id_mov";
if (count($lm->available) > 0) {
	foreach ($lm->available as $v_lm) { // per ogni lotto disponibile
		$totale = 0;
		$where= $gTables['movmag'] . ".id_lotmag = '".$v_lm['id']."'";
		$rs = gaz_dbi_dyn_query($gTables['movmag'] . ".artico,".
		$gTables['movmag'] . ".quanti,".
		$gTables['movmag'] . ".tipdoc,".
		$gTables['movmag'] . ".desdoc,".
		$gTables['movmag'] . ".datdoc,".
		$gTables['movmag'] . ".operat,".
		$gTables['movmag'] . ".id_mov,".
		$gTables['clfoco'].".codice, ".$gTables['clfoco'].".descri AS ragsoc, ".
		$gTables['lotmag'] . ".id,".
		$gTables['artico'] . ".unimis,".
		$gTables['lotmag'] . ".identifier,".
		$gTables['lotmag'] . ".expiry ",
		$gTables['movmag'] . " LEFT JOIN " . $gTables['lotmag'] . " ON ". $gTables['movmag'] . ".id_lotmag = " . $gTables['lotmag'] . ".id ".
		" LEFT JOIN " . $gTables['artico'] . " ON ". $gTables['movmag'] . ".artico = " . $gTables['artico'] . ".codice ".
		" LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['movmag'].".clfoco = ".$gTables['clfoco'].".codice"
		, $where, $orderby);

		foreach ($rs as $movlot){ // stampo tutti i movimenti del singolo lotto
			if ( $i % 24 == 0 ) {
				// cambio pagina e stampo intestazione colonne
				$pdf->AddPage('L',"A4");
				$pdf->Cell(50,5,"Lotto",$heavy,0,'C');
				$pdf->Cell(25,5,"Scadenza",$heavy,0,'C');
				$pdf->Cell(85,5,"Descrizione movimento",$heavy,0,'L');
				$pdf->Cell(25,5,"Data",$heavy,0,'C');
				$pdf->Cell(15,5,"U.m.",$heavy,0,'C');

				$pdf->Cell(20,5,"Entrata",$heavy,0,'R');
				$pdf->Cell(20,5,"Uscita",$heavy,0,'R');

				$pdf->Cell(30,5,"Totale",$heavy,1,'R');
			}
			$totale=$totale+($movlot['operat']*$movlot['quanti']);
			$pdf->SetTextColor(0);
			if ($totale<0){
				$pdf->SetTextColor(255,0,0);
			}
			$pdf->Cell(50,5,$movlot['identifier']." - id:".$movlot['id'],$light,0,'L');
			if ($movlot['expiry']>0){
				$pdf->Cell(25,5,gaz_format_date($movlot['expiry']),$light,0,'C');
			} else {
				$pdf->Cell(25,5,'',$light);
			}
			$pdf->Cell(85,5,substr($movlot['tipdoc']." - ".$movlot['desdoc']." ".$movlot['ragsoc'],0,50),$light,0,'L');
			$pdf->Cell(25,5,gaz_format_date($movlot['datdoc']),$light,0,'C');
			$pdf->Cell(15,5,$movlot['unimis'],$light,0,'C');
			if ($movlot['operat']>0) {
				$pdf->Cell(20,5,gaz_format_number($movlot['quanti']),$light,0,'R', 0, '', 1);
				$pdf->Cell(20,5,'',$light);
			} else {
				$pdf->Cell(20,5,'',$light);
				$pdf->Cell(20,5,gaz_format_number($movlot['quanti']),$light,0,'R', 0, '', 1);
			}
			$pdf->Cell(30,5,gaz_format_number($totale),$light,1,'R');
			$i++;
		}
		$pdf->Cell(270,5,'','',1);
	}
}
$pdf->SetFont('helvetica','B',9);
$pdf->Output($filename);
?>
