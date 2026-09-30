<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
require("../../library/include/datlib.inc.php");

$admin_aziend=checkAdmin();

if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
    gaz_set_time_limit (0);
}

function getMovements($date_ini,$date_fin,$type){
	global $gTables,$admin_aziend;
    $m=array();
	if ($type == "di campagna"){
		$where="mostra_qdc = '1' AND type_mov = '1' AND datreg BETWEEN $date_ini AND $date_fin AND ". $gTables['movmag'] .".id_rif >= ". $gTables['movmag'] .".id_mov" ;
	} else {
		//$where="mostra_qdc = '1' AND ". $gTables['movmag'].".operat = '1' AND datreg BETWEEN $date_ini AND $date_fin AND ". $gTables['movmag'] .".id_rif >= ". $gTables['movmag'] .".id_mov" ;
		$where="mostra_qdc = '1' AND ". $gTables['movmag'].".operat = '1' AND datreg BETWEEN $date_ini AND $date_fin AND good_or_service = 0" ;

	}
	$what=$gTables['movmag'].".*, ".
		  $gTables['caumag'].".codice, ".$gTables['caumag'].".descri, ".
		  $gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2, ".
		  $gTables['artico'].".codice, ".$gTables['artico'].".descri AS desart, ".$gTables['artico'].".unimis, ".$gTables['artico'].".scorta, ".$gTables['artico'].".catmer, ".$gTables['artico'].".perc_N, ".$gTables['artico'].".perc_P, ".$gTables['artico'].".perc_K, ".$gTables['artico'].".mostra_qdc, ".$gTables['artico'].".classif_amb ";
	$table=$gTables['movmag']." LEFT JOIN ".$gTables['caumag']." ON (".$gTables['movmag'].".caumag = ".$gTables['caumag'].".codice)
			LEFT JOIN ".$gTables['anagra']." ON (".$gTables['anagra'].".id = ".$gTables['movmag'].".clfoco)
		   LEFT JOIN ".$gTables['artico']." ON (".$gTables['movmag'].".artico = ".$gTables['artico'].".codice)";
	$rs=gaz_dbi_dyn_query ($what,$table,$where, 'datreg ASC, tipdoc ASC, campo_impianto ASC, operat DESC, id_mov ASC');
	while ($r = gaz_dbi_fetch_array($rs)) {
		$m[] = $r;
	}
	return $m;
}

$luogo_data=$admin_aziend['citspe'].", lì ";
$gazTimeFormatter->setPattern('dd MMMM yyyy');
if (isset($_GET['ds'])) {
   $giosta = substr($_GET['ds'],0,2);
   $messta = substr($_GET['ds'],2,2);
   $annsta = substr($_GET['ds'],4,4);
   $utssta= mktime(0,0,0,$messta,$giosta,$annsta);
   $luogo_data .= ucwords($gazTimeFormatter->format(new DateTime($annsta.'-'.$messta.'-'.$giosta)));
} else {
   $luogo_data .= ucwords($gazTimeFormatter->format(new DateTime()));
}
$giori = substr($_GET['ri'],0,2);
$mesri = substr($_GET['ri'],2,2);
$annri = substr($_GET['ri'],4,4);
$utsri= mktime(0,0,0,$mesri,$giori,$annri);
$giorf = substr($_GET['rf'],0,2);
$mesrf = substr($_GET['rf'],2,2);
$annrf = substr($_GET['rf'],4,4);
$utsrf= mktime(0,0,0,$mesrf,$giorf,$annrf);
$type = substr($_GET['type'],0,11);
$gazTimeFormatter->setPattern('yyyyMMdd');
$result=getMovements($gazTimeFormatter->format(new DateTime('@'.$utsri)),$gazTimeFormatter->format(new DateTime('@'.$utsrf)),$type);

require("../../config/templates/report_template_qc.php");
$gazTimeFormatter->setPattern('dd MMMM yyyy');
if ($type=="di campagna"){
	$title = array('luogo_data'=>$luogo_data,
	'title'=>"Registro ". $type ." dal ". $gazTimeFormatter->format(new DateTime('@'.$utsri))." al ".$gazTimeFormatter->format(new DateTime('@'.$utsrf)),
	'hile'=>array(array('lun' => 17,'nam'=>'Data att.'),
				 array('lun' => 35,'nam'=>'Causale'),
				 array('lun' => 12,'nam'=>'Campo'),
				 array('lun' => 10,'nam'=>'ha'),
				 array('lun' => 38,'nam'=>'Coltura'),
				 array('lun' => 58,'nam'=>'Prodotto'),
				 array('lun' => 6,'nam'=>'Cl.'),
				 array('lun' => 8,'nam'=>'U.M.'),
				 array('lun' => 13,'nam'=>'Q.tà'),
				 array('lun' => 13,'nam'=>'Acqua'),
				 array('lun' => 28,'nam'=>'Avversità'),
				 array('lun' => 18,'nam'=>'Operat.'),
				 array('lun' => 30,'nam'=>'Annotazioni')
				)
	);
} else {
	$title = array('luogo_data'=>$luogo_data,
	   'title'=>"Registro ". $type ." dei prodotti agricoli dal ".$gazTimeFormatter->format(new DateTime('@'.$utsri))." al ".$gazTimeFormatter->format(new DateTime('@'.$utsrf)),
	   'hile'=>array(array('lun' => 20,'nam'=>'Data Reg.'),
					 array('lun' => 36,'nam'=>'Causale'),
					 array('lun' => 83,'nam'=>'Articolo'),
					 array('lun' => 22,'nam'=>'Lotto'),
					 array('lun' => 56,'nam'=>'Rif.Documento'),
					 array('lun' => 20,'nam'=>'Rag. sociale'),
					 array('lun' => 10,'nam'=>'U.M.'),
					 array('lun' => 15,'nam'=>'Quantità')
					)
	);
}

$pdf = new Report_template('L','mm','A4',true,'UTF-8',false,true);
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(42);
$pdf->SetleftMargin(6);
$pdf->SetFooterMargin(20);
$config = new Config;
$pdf->AddPage('L',$config->getValue('page_format'));
$pdf->SetFont('helvetica','',9);
if (sizeof($result) > 0 AND $type=="di campagna") {
	foreach($result as $key => $row)	{

		$res = gaz_dbi_get_row ($gTables['campi'], 'codice', $row['campo_impianto']);// Antonio Germani carico il campo
		if (!isset($res)){
			$row['campo_impianto']="-";
		}
		$datadoc = substr($row['datdoc'],8,2).'-'.substr($row['datdoc'],5,2).'-'.substr($row['datdoc'],0,4);
		$datareg = substr($row['datreg'],8,2).'-'.substr($row['datreg'],5,2).'-'.substr($row['datreg'],0,4);
		$movQuanti = $row['quanti']*$row['operat'];
		$pdf->Cell(17,6,$datadoc,1,0,'C');
		$pdf->Cell(35,6,$row['descri'],1, 0, 'l', 0, '', 1);

		if (isset($res['zona_vulnerabile']) AND $res['zona_vulnerabile']==0){
			$pdf->Cell(12,6,substr($row['campo_impianto'],0,5),1,0,'L',0,'',1);
		} else {
			$pdf->Cell(12,6,substr($row['campo_impianto']." ZVN",0,5),1,0,'L',0,'',1);
		}
		// Antonio Germani Inserisco superficie e coltura
		$pdf->Cell(10,6,str_replace('.', ',',($res)?$res['ricarico']:0),1,0,'L',0,'',1);
		$res4 = gaz_dbi_get_row($gTables['camp_colture'], 'id_colt', ($res)?$res['id_colture']:0);
		$fase="";
		if ($data=json_decode($row['custom_field'],true)){// se c'è un json nel custom_field
			if (is_array($data['camp']) AND strlen($data['camp']['fase_fenologica'])>0){ // se è riferito al modulo camp
				$fase = " >> ".$data['camp']['fase_fenologica'];
			}
		}
		$pdf->Cell(38,6,substr(($res4)?$res4['nome_colt'].$fase:'',0,40),1,0,'L',0,'',1);
		// fine inserisco superficie, coltura

		$pdf->Cell(58,6,$row['artico'].' - '.$row['desart'], 1, 0, 'l', 0, '', 1);
		If ($row['classif_amb']==0){$pdf->Cell(6,6,"Nc",1);}
		If ($row['classif_amb']==1){$pdf->Cell(6,6,"Xi",1);}
		If ($row['classif_amb']==2){$pdf->Cell(6,6,"Xn",1);}
		If ($row['classif_amb']==3){$pdf->Cell(6,6,"T",1);}
		If ($row['classif_amb']==4){$pdf->Cell(6,6,"T+",1);}
		If ($row['classif_amb']==5){$pdf->Cell(6,6,"Pa",1);}
		$pdf->Cell(8,6,$row['unimis'],1,0,'C');
		$pdf->Cell(13,6,gaz_format_quantity($row["quanti"],1,$admin_aziend['decimal_quantity']),1, 0, 'l', 0, '', 1);

		if ($row['id_rif']>$row['id_mov']){

			$acqua = gaz_dbi_get_row($gTables['movmag'], 'id_mov', $row['id_rif']);
			$unimis_acqua = gaz_dbi_get_row($gTables['artico'], 'codice', $acqua['artico'])['unimis'];

			$pdf->Cell(13,6,$unimis_acqua. " ".gaz_format_quantity($acqua['quanti'],1,$admin_aziend['decimal_quantity']),1,0,'L',0,'',1);
		} else {
			$pdf->Cell(13,6,"",1,0,'L',0,'',1);
		}

		$res3 = gaz_dbi_get_row($gTables['camp_avversita'], 'id_avv', $row['id_avversita']);
		$pdf->Cell(28,6,($res3)?$res3['nome_avv']:'',1, 0, 'l', 0, '', 1);

		if ($row['clfoco']>0){
			$pdf->Cell(18,6,$row['ragso1'].' '.$row['ragso2'],1, 0, 'l', 0, '', 1);
		} else {
		/* Antonio Germani - trasformo admin in cognome e nome e lo stampo */
		$res2 = gaz_dbi_get_row ($gTables['admin'], 'user_name', $row['adminid'] );
		$pdf->Cell(18,6,$res2['user_lastname']." ".$res2['user_firstname'],1, 0, 'l', 0, '', 1);
		/* Antonio Germani FINE trasformo nome utente login in cognome e nome */
		}
		if ($row['perc_N']>0){
			$row['desdoc']="NPK=".intval($row['perc_N'])."-".intval($row['perc_P'])."-".intval($row['perc_K'])." ".$row['desdoc'];
		}
		$pdf->Cell(30,6,$row['desdoc'],1, 1, 'l', 0, '', 1);

		$colonna="1";
	}
}
if (sizeof($result) > 0 AND $type=="di carico") {
	foreach ($result AS $key => $row) {
		$datadoc = substr($row['datdoc'],8,2).'-'.substr($row['datdoc'],5,2).'-'.substr($row['datdoc'],0,4);
		$datareg = substr($row['datreg'],8,2).'-'.substr($row['datreg'],5,2).'-'.substr($row['datreg'],0,4);
		$movQuanti = $row['quanti']*$row['operat'];
		$pdf->Cell(20,3,$datareg,1,0,'C');
		$pdf->Cell(36,3,$row['caumag'].'-'.substr($row['descri'],0,25),1, 0, 'l', 0, '', 1);
		$pdf->Cell(83,3,$row['artico'].' - '.substr($row['desart'],0,70),1, 0, 'l', 0, '', 1);
		$pdf->Cell(22,3,substr($row['id_lotmag'],-20),1, 0, 'l', 0, '', 1); // L'identificatore lotto, se troppo lungo, viene accorciato agli ultimi 15 caratteri
		$pdf->Cell(56,3,$row['desdoc'].' del '.$datadoc,1, 0, 'l', 0, '', 1);
		$pdf->Cell(20,3,substr($row['ragso1'].' '.$row['ragso2'],0,30),1, 0, 'l', 0, '', 1);
		$pdf->Cell(10,3,$row['unimis'],1,0,'C');
		$pdf->Cell(15,3,gaz_format_quantity($movQuanti,1,$admin_aziend['decimal_quantity']),1,1,'R');
	}
}
$pdf->Output();
?>
