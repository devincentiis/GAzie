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
$Cu_limit_anno=4; // Limite annuo in Kg di rame metallo ad ettaro
$admin_aziend=checkAdmin();

if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
    gaz_set_time_limit (0);
}



function getMovements($date_ini,$date_fin)
    {
        global $gTables,$admin_aziend;
        $m=array();
        $where="datdoc BETWEEN $date_ini AND $date_fin"; // Antonio Germani prendo la data di attuazione
        $what=$gTables['movmag'].".*, ".
              $gTables['caumag'].".codice, ".$gTables['caumag'].".descri, ".
              $gTables['artico'].".codice, ".$gTables['artico'].".descri AS desart, ".$gTables['artico'].".perc_K, ".$gTables['artico'].".perc_P, ".$gTables['artico'].".perc_N, ".$gTables['artico'].".unimis, ".$gTables['artico'].".scorta, ".$gTables['artico'].".catmer, ".$gTables['artico'].".rame_metallico ";
        $table=$gTables['movmag']." LEFT JOIN ".$gTables['caumag']." ON (".$gTables['movmag'].".caumag = ".$gTables['caumag'].".codice)
               LEFT JOIN ".$gTables['artico']." ON (".$gTables['movmag'].".artico = ".$gTables['artico'].".codice)";
        $rs=gaz_dbi_dyn_query ($what,$table,$where, 'datreg ASC, tipdoc ASC, clfoco ASC, operat DESC, id_mov ASC');
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
$sta_fert = (isset($_GET['stf']))?$_GET['stf']:NULL;
$giori = substr($_GET['ri'],0,2);
$mesri = substr($_GET['ri'],2,2);
$annri = substr($_GET['ri'],4,4);
$utsri= mktime(0,0,0,$mesri,$giori,$annri);
$giorf = substr($_GET['rf'],0,2);
$mesrf = substr($_GET['rf'],2,2);
$annrf = substr($_GET['rf'],4,4);
$utsrf= mktime(0,0,0,$mesrf,$giorf,$annrf);
$gazTimeFormatter->setPattern('yyyyMMdd');
$result=getMovements($gazTimeFormatter->format(new DateTime('@'.$utsri)),$gazTimeFormatter->format(new DateTime('@'.$utsrf)));

$giorni=intval(($utsrf-$utsri)/86400);
require("../../config/templates/report_template_qc.php");
$gazTimeFormatter->setPattern('dd MMMM yyyy');
if ($sta_fert==false){
	$title = array('luogo_data'=>$luogo_data,
				'title'=>"DICHIARAZIONE RAME METALLO USATO dal ".$gazTimeFormatter->format(new DateTime('@'.$utsri))." al ".$gazTimeFormatter->format(new DateTime('@'.$utsrf))." = ".$giorni." giorni",
				'hile'=>array(
							array('lun' => 13,'nam'=>'N.'),
							array('lun' => 55,'nam'=>'Campo'),
							array('lun' => 17,'nam'=>'Superficie'),
                            array('lun' => 30,'nam'=>'Rame metallo usato'),
                            array('lun' => 30,'nam'=>'Rame ammesso'),

							array('lun' => 70,'nam'=>'Immagine')
                            )
			);
} else {
	$title = array('luogo_data'=>$luogo_data,
				'title'=>"DICHIARAZIONE RAME METALLO E FERTILIZZANTI USATI dal ".$gazTimeFormatter->format(new DateTime('@'.$utsri))." al ".$gazTimeFormatter->format(new DateTime('@'.$utsrf))." = ".$giorni." giorni",
				'hile'=>array(
							array('lun' => 13,'nam'=>'N.'),
							array('lun' => 55,'nam'=>'Campo'),
							array('lun' => 17,'nam'=>'Superficie'),
                            array('lun' => 30,'nam'=>'Rame metallo usato'),
                            array('lun' => 30,'nam'=>'Rame ammesso'),
							array('lun' => 15,'nam'=>'N usato'),
							array('lun' => 15,'nam'=>'P usato'),
							array('lun' => 15,'nam'=>'K usato'),
                            array('lun' => 18,'nam'=>'N ammesso'),
							array('lun' => 70,'nam'=>'Immagine')
                            )
			);
}
$n=0; $campi=array();
if (sizeof($result) > 0) {
	foreach ($result as $key => $row) {

		if ($row['campo_impianto']>0 && $row['type_mov']==1){ // se nel movimento è inserito un campo di coltivazione ed è un movimento del registro di campagna

			if ($row['rame_metallico']>0 OR ($row['perc_N']>0 AND $sta_fert==true)){ // se l'articolo contiene rame metallo o azoto con stampa fertilizzazioni
				//carico i dati per ogni campo di coltivazione
				$camp = gaz_dbi_get_row($gTables['campi'], "codice", $row['campo_impianto']);
				$array[$n]= array(
							'campo_impianto'=>$row['campo_impianto'],
							'descri_campo'=>$camp['descri'],
							'img_campo'=>$camp['image'],
							'rame_metallo_prodotto'=>$row['rame_metallico'],
							'superficie'=>$camp['ricarico'],
							'rame_metallo_usato_su_campo'=>$row['rame_metallico']*$row['quanti'],
							'qta_N'=>($row['perc_N']*$row['quanti'])/100,
							'qta_P'=>($row['perc_P']*$row['quanti'])/100,
							'qta_K'=>($row['perc_K']*$row['quanti'])/100,
							'ZVN'=>$camp['zona_vulnerabile'],
							'lim_N_ZVN'=>$camp['limite_azoto_zona_vulnerabile'],
							'lim_N'=>$camp['limite_azoto_zona_non_vulnerabile']
							);
				$n++;  //ho creato un array con i dati che mi servono
			}
		}
	}

	rsort ($array); // ordino l'array per il primo valore che è il campo di coltivazione
	$c=0;
	for ($i=0; $i<$n; $i++){
	 	if ($i==0){
			$campi[$c]=array(
						'campo_impianto'=>$array[$i]['campo_impianto'],
						'descri_campo'=>$array[$i]['descri_campo'],
						'img_campo'=>$array[$i]['img_campo'],
						'superficie'=> $array[$i]['superficie'],
						'totale_rame'=> $array[$i]['rame_metallo_usato_su_campo'],
						'tot_N'=>$array[$i]['qta_N'],
						'tot_P'=>$array[$i]['qta_P'],
						'tot_K'=>$array[$i]['qta_K'],
						'ZVN'=>$array[$i]['ZVN'],
						'lim_N_ZVN'=>$array[$i]['lim_N_ZVN'],
						'lim_N'=>$array[$i]['lim_N_ZVN']
						);
		} else {
			if ($array[$i]['campo_impianto']==$array[$i-1]['campo_impianto']){
				$campi[$c]['totale_rame']=$campi[$c]['totale_rame']+$array[$i]['rame_metallo_usato_su_campo'];
				$campi[$c]['tot_N']=$campi[$c]['tot_N']+$array[$i]['qta_N'];
				$campi[$c]['tot_P']=$campi[$c]['tot_P']+$array[$i]['qta_P'];
				$campi[$c]['tot_K']=$campi[$c]['tot_K']+$array[$i]['qta_K'];

			} else {
			$c=$c+1;
			$campi[$c]=array(
						'campo_impianto'=>$array[$i]['campo_impianto'],
						'descri_campo'=>$array[$i]['descri_campo'],
						'img_campo'=>$array[$i]['img_campo'],
						'superficie'=> $array[$i]['superficie'],
						'totale_rame'=> $array[$i]['rame_metallo_usato_su_campo'],
						'tot_N'=> $array[$i]['qta_N'],
						'tot_P'=> $array[$i]['qta_P'],
						'tot_K'=> $array[$i]['qta_K'],
						'ZVN'=>$array[$i]['ZVN'],
						'lim_N_ZVN'=>$array[$i]['lim_N_ZVN'],
						'lim_N'=>$array[$i]['lim_N']
						);
			}
		}
	}

 // inizio creazione PDF
$pdf = new Report_template('L','mm','A4',true,'UTF-8',false,true);
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(42);
$pdf->SetFooterMargin(20);
$config = new Config;
$pdf->AddPage('L',$config->getValue('page_format'));
$pdf->SetFont('helvetica','',9);

	for ($i=0; $i<$c+1; $i++) {
		$rame_ammesso = $campi[$i]['superficie']*$Cu_limit_anno;
		if ($campi[$i]['ZVN']==0){
			$N_ammesso = $campi[$i]['lim_N']*$campi[$i]['superficie'];
		} else {
			$N_ammesso = $campi[$i]['lim_N_ZVN']*$campi[$i]['superficie'];
			$campi[$i]['campo_impianto'] = $campi[$i]['campo_impianto']." ZVN";
		}
		$pdf->Cell(13,6,$campi[$i]['campo_impianto'],1,0,'L',0,'',1);
		$pdf->Cell(55,6,$campi[$i]['descri_campo'],1,0,'L',0,'',1);
		$pdf->Cell(17,6,"ha ".gaz_format_quantity($campi[$i]['superficie'],1,$admin_aziend['decimal_quantity']),1,0,'L',0,'',1);
		$pdf->Cell(30,6,"Kg ".gaz_format_quantity($campi[$i]['totale_rame'],1,$admin_aziend['decimal_quantity']),1,0,'L',0,'',1);
		$pdf->Cell(30,6,"Kg ".gaz_format_quantity($rame_ammesso,1,$admin_aziend['decimal_quantity'])." annuo",1,0,'L',0,'',1);
		if ($sta_fert==true){
			$pdf->Cell(15,6,"Kg ".gaz_format_quantity($campi[$i]['tot_N'],1,$admin_aziend['decimal_quantity']),1,0,'L',0,'',1);
			$pdf->Cell(15,6,"Kg ".gaz_format_quantity($campi[$i]['tot_P'],1,$admin_aziend['decimal_quantity']),1,0,'L',0,'',1);
			$pdf->Cell(15,6,"Kg ".gaz_format_quantity($campi[$i]['tot_K'],1,$admin_aziend['decimal_quantity']),1,0,'L',0,'',1);
			$pdf->Cell(18,6,"Kg ".gaz_format_quantity($N_ammesso,1,$admin_aziend['decimal_quantity']),1,0,'L',0,'',1);
		}

		if (strlen($campi[$i]['img_campo'])>0){
			$pdf->Image('@'.$campi[$i]['img_campo'], $x='', $y='', $w=70, $h=0, $type='', $link='', $align='', $resize=true, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false);
		}
		if ($i<$c) {
			$pdf->AddPage();
		}

	}
}
$pdf->Output();
?>
