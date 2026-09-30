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
$vrag = "";
$where = " tipo='ASS' ";

require("./lang.".$admin_aziend['lang'].".php");

if ( isset($_GET['flt_passo']) ) {
	$passo = "999"; //$_GET['flt_passo'];
} else {
	$passo = "";
}

// preparo la query
if ( isset($_GET['oggetto'])) {
	$where .= " and ".$gTables['assist'].".oggetto like '%".$_GET['oggetto']."%'";
}

if ( isset($_GET['clfoco']) && $_GET['clfoco']!="All" ) {
	$where .= " and ".$gTables['assist'].".clfoco='".$_GET['clfoco']."'";
}

if ( isset($_GET['flt_stato']) ) {
	if ( $_GET['flt_stato']!="All" ) {
		if ( $_GET['flt_stato']=="nochiusi" ) {
			$where .= " and ".$gTables['assist'].".stato != 'effettuato' and ".$gTables['assist'].".stato != 'fatturato' ";
		} else {
			$where .= " and ".$gTables['assist'].".stato = '".$_GET['flt_stato']."'";
		}
	}
}

if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
    gaz_set_time_limit (0);
}

require("../../config/templates/report_template.php");
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data=$admin_aziend['citspe'].", lì ".ucwords($gazTimeFormatter->format(new DateTime()));
$item_head = array('top'=>array(array('lun' => 80,'nam'=>'Descrizione'),
                                array('lun' => 25,'nam'=>'Numero Conto')
                               )
                   );
$acc=array();
$title = array('luogo_data'=>$luogo_data,
               'title'=>'RESOCONTO INTERVENTI DI ASSISTENZA TECNICA',
					'hile' => array()
              );
$pdf = new Report_template();
$pdf->setVars($admin_aziend,$title);
$pdf->setFooterMargin(22);
$pdf->setTopMargin(34);
$pdf->setRiporti('');
$pdf->AddPage();

$result = gaz_dbi_dyn_query($gTables['assist'].".*,
		".$gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2,
		".$gTables['anagra'].".telefo, ".$gTables['anagra'].".fax,
		".$gTables['anagra'].".cell, ".$gTables['anagra'].".e_mail
		", $gTables['assist'].
		" LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['assist'].".clfoco = ".$gTables['clfoco'].".codice".
		" LEFT JOIN ".$gTables['anagra'].' ON '.$gTables['clfoco'].'.id_anagra = '.$gTables['anagra'].'.id',
		$where, "clfoco,DATA ASC", $limit, $passo);

$totale_ore = -1;

while ($row = gaz_dbi_fetch_array($result)) {
	if ( $row["ragso1"] != $vrag ) {
		$pdf->SetFont('helvetica','B',10);
		$pdf->SetFillColor(255,255,255);
		if ( $totale_ore != -1 ) {
			$pdf->Cell(158,5,'Totale Ore :','LTB',0,'R',1,'',1);
			$pdf->Cell(12,5,gaz_format_number($totale_ore),1,1,'R',1);
		}
		$totale_ore ++;

	    $pdf->Ln(2);
		if ( $row['fax'] != "" ) $fax = "fax: ".$row['fax'];
		else $fax = "";
		if ( $row['cell'] != "" ) $mob = "mob:".$row['cell'];
		else $mob = "";
		if ( $row['telefo'] != "" ) $tel = "tel:".$row['telefo'];
		else $tel = "";
		if ( $row['e_mail'] != "" ) $email = $row['e_mail'];
		else $email = "";
        $pdf->Cell(188,6,$row['ragso1']." ".$row['ragso2']." ".$tel." ".$fax." ".$mob." ".$email,1,1,'',1,'',1);
		$vrag = $row["ragso1"];
		$totale_ore = 0;
	}
   	$pdf->SetFont('helvetica','',9);
	$pdf->Cell(12,5,$row['codice'],'LTB',0,'R',1,'',1);
	$pdf->Cell(20,5,gaz_format_date($row['data']),1,0,'C',1,'',1);
   	$pdf->Cell(62,5,$row['oggetto'],1,0,'L',1);
	$pdf->Cell(64,5,substr(strip_tags($row['descrizione']),0,50),1,0,'L',1);
	$pdf->Cell(12,5,gaz_format_number($row['ore']),1,0,'R',1);
	$totale_ore += $row['ore'];
  	$pdf->Cell(18,5,$row['stato'],1,1,'R',1);
}
$pdf->SetFont('helvetica','B',10);
$pdf->SetFillColor(255,255,255);
if ( $totale_ore != -1 ) {
	$pdf->Cell(158,5,'Totale Ore :','LTB',0,'R',1,'',1);
	$pdf->Cell(12,5,gaz_format_number($totale_ore),1,1,'R',1);
}

$pdf->setRiporti('');
$pdf->Output();
?>
