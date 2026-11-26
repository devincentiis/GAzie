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
//        >>>>>> Antonio Germani -- STAMPA Lotti  <<<<<<

require("../../library/include/datlib.inc.php");
require("../../modules/vendit/lib.function.php");
$admin_aziend=checkAdmin();

$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data=$admin_aziend['citspe'].", lì ";
$luogo_data .= ucwords($gazTimeFormatter->format(new DateTime()));

require("../../config/templates/report_template.php");
$lm = new lotmag;
$gForm = new magazzForm;
$codice = filter_input(INPUT_GET, 'codice');

$des = gaz_dbi_get_row($gTables['artico'], 'codice', $codice);
$title = array('luogo_data' => $luogo_data,
 'title' => "Movimenti lotti disponibili per articolo ".$codice." - ".$des['descri'],
 'hile' => array(
   array('lun' => 50, 'nam' => 'Lotto'),
   array('lun' => 25, 'nam' => 'Scadenza'),
   array('lun' => 85, 'nam' => 'Descrizione movimento'),
   array('lun' => 25, 'nam' => 'Data'),
   array('lun' => 15, 'nam' => 'U.M.'),
   array('lun' => 20, 'nam' => 'Entrata'),
   array('lun' => 20, 'nam' => 'Uscita'),
   array('lun' => 30, 'nam' => 'Totale')
 )
);

$lm->getAvailableLots($codice,0,'',1,true);

$pdf = new Report_template('L','mm','A4',true,'UTF-8',false,true);
$filename = "Movimenti lotti disponibili per articolo_".$codice.'_'.date("Ymd").'.pdf';
$pdf->setVars($admin_aziend,$title);
$pdf->setAuthor($admin_aziend['ragso1'] . ' ' . $_SESSION["user_name"]);
$pdf->SetTopMargin(45);
$pdf->setFooterMargin(10);
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
$pdf->AddPage('L',"A4");

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
    $rf=false;
	  $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
		$pdf->Cell(270,4,'',0,1);
		$pdf->Cell(270,5,'LOTTO:'.$v_lm['identifier']." - id:".$v_lm['id'],'LBR',1,'C',1);
    $pdf->SetFillColor(235,235,235);
    foreach ($rs as $movlot){ // stampo tutti i movimenti del singolo lotto
			$totale=$totale+($movlot['operat']*$movlot['quanti']);
			$pdf->SetTextColor(0);
			if ($totale<0){
				$pdf->SetTextColor(255,0,0);
			}
			$pdf->Cell(50,5,$movlot['identifier']." - id:".$movlot['id'],1,0,'L',$rf);
			if ($movlot['expiry']>0){
				$pdf->Cell(25,5,gaz_format_date($movlot['expiry']),1,0,'C',$rf);
			} else {
				$pdf->Cell(25,5,'',1,0,'C',$rf);
			}
			$pdf->Cell(85,5,substr($movlot['tipdoc']." - ".$movlot['desdoc']." ".$movlot['ragsoc'],0,50),1,0,'L',$rf);
			$pdf->Cell(25,5,gaz_format_date($movlot['datdoc']),1,0,'C',$rf);
			$pdf->Cell(15,5,$movlot['unimis'],1,0,'C');
			if ($movlot['operat']>0) {
				$pdf->Cell(20,5,gaz_format_number($movlot['quanti']),1,0,'R',$rf, '', 1);
				$pdf->Cell(20,5,'',1);
			} else {
				$pdf->Cell(20,5,'',1);
				$pdf->Cell(20,5,gaz_format_number($movlot['quanti']),1,0,'R',$rf, '', 1);
			}
			$pdf->Cell(30,5,gaz_format_number($totale),1,1,'R',$rf);
			$i++;
      $rf = !$rf;
		}
	}
}
$pdf->SetFont('helvetica','B',9);
$pdf->Output($filename);
?>
