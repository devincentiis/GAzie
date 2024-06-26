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

if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
    gaz_set_time_limit (0);
}

if (!isset($_GET['ri']) or
    !isset($_GET['rf'])) {
    header("Location: select_giomag.php");
    exit;
}

function getMovements($date_ini,$date_fin)
    {
        global $gTables,$admin_aziend;
        $m=array();
		if ($_GET['md']=="1"){
			$where="good_or_service != '1' AND datreg BETWEEN $date_ini AND $date_fin";
		} else if ($_GET['md']=="2"){
			$where=$gTables['movmag'].".operat = '1' AND good_or_service != '1' AND datreg BETWEEN $date_ini AND $date_fin";
		} else {
			$where=$gTables['movmag'].".operat = '-1' AND good_or_service != '1' AND datreg BETWEEN $date_ini AND $date_fin";
		}
		$where .=  ' AND '. $gTables['artico'].'.id_assets = 0 AND type_mov <> 1';
        $what=$gTables['movmag'].".*, ".
              $gTables['caumag'].".codice, ".$gTables['caumag'].".descri, ".
			  $gTables['clfoco'].".codice, ".$gTables['clfoco'].".descri AS ragsoc, ".
              $gTables['artico'].".codice, ".$gTables['artico'].".descri AS desart, ".$gTables['artico'].".unimis, ".$gTables['artico'].".scorta, ".$gTables['artico'].".catmer ";
        $table=$gTables['movmag']." LEFT JOIN ".$gTables['caumag']." ON (".$gTables['movmag'].".caumag = ".$gTables['caumag'].".codice)
               LEFT JOIN ".$gTables['clfoco']." ON (".$gTables['movmag'].".clfoco = ".$gTables['clfoco'].".codice)
			   LEFT JOIN ".$gTables['artico']." ON (".$gTables['movmag'].".artico = ".$gTables['artico'].".codice)";
        $rs=gaz_dbi_dyn_query ($what,$table,$where, 'datreg ASC, clfoco ASC');
        while ($r = gaz_dbi_fetch_array($rs)) {
			if ($r['id_lotmag']>0){
				$identifier = gaz_dbi_get_row($gTables['lotmag'], "id", $r['id_lotmag']);
				$r['id_lotmag'] = $identifier['identifier'];
			} else {
				$r['id_lotmag']="";
			}
            $m[] = $r;
        }
        return $m;
    }

$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data=$admin_aziend['citspe'].", lì ";
if (isset($_GET['ds'])) {
  $giosta = substr($_GET['ds'],0,2);
  $messta = substr($_GET['ds'],2,2);
  $annsta = substr($_GET['ds'],4,4);
  $utssta= mktime(0,0,0,$messta,$giosta,$annsta);
  $luogo_data .= ucwords($gazTimeFormatter->format(new DateTime($annsta.'-'.$messta.'-'.$giosta)));
} else {
  $luogo_data .= ucwords($gazTimeFormatter->format(new DateTime()));
}

if ($_GET['md']=="1"){
	$title="";
} else if ($_GET['md']=="2"){
	$title=" - Entrate";
} else {
	$title=" - Uscite";
}

$giori = substr($_GET['ri'],0,2);
$mesri = substr($_GET['ri'],2,2);
$annri = substr($_GET['ri'],4,4);
$utsri= mktime(12,0,0,$mesri,$giori,$annri);
$giorf = substr($_GET['rf'],0,2);
$mesrf = substr($_GET['rf'],2,2);
$annrf = substr($_GET['rf'],4,4);
$utsrf= mktime(12,0,0,$mesrf,$giorf,$annrf);
$gazTimeFormatter->setPattern('yyyyMMdd');
$result=getMovements($gazTimeFormatter->format(new DateTime('@'.$utsri)),$gazTimeFormatter->format(new DateTime('@'.$utsrf)));

require("../../config/templates/report_template.php");
$pdf = new Report_template('L','mm','A4',true,'UTF-8',false,true);
$gazTimeFormatter->setPattern('dd MMMM yyyy');
if ($_GET['pr']==1){
  $title = array('luogo_data'=>$luogo_data,
               'title'=>"GIORNALE DI MAGAZZINO".$title." dal ".$gazTimeFormatter->format(new DateTime('@'.$utsri))." al ".$gazTimeFormatter->format(new DateTime('@'.$utsrf))." - ".(isset($_GET['sb'])?$_GET['sb']:''),
               'hile'=>array(array('lun' => 20,'nam'=>'Data Reg.'),
                             array('lun' => 36,'nam'=>'Causale'),
                             array('lun' => 83,'nam'=>'Articolo'),
							 array('lun' => 22,'nam'=>'Lotto'),
                             array('lun' => 56,'nam'=>'Rif.Documento'),
                             array('lun' => 17,'nam'=>'Prezzo'),
                             array('lun' => 18,'nam'=>'Importo'),
                             array('lun' => 10,'nam'=>'U.M.'),
                             array('lun' => 15,'nam'=>'Quantità')
                            )
            );
} else {
	$title = array('luogo_data'=>$luogo_data,
               'title'=>"GIORNALE DI MAGAZZINO".$title." dal ".$gazTimeFormatter->format(new DateTime('@'.$utsri))." al ".$gazTimeFormatter->format(new DateTime('@'.$utsrf))." - ".$_GET['sb'],
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
$pdf->SetLeftMargin(16);
}

$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(39);
$pdf->SetFooterMargin(20);

$config = new Config;
$pdf->AddPage('L',$config->getValue('page_format'));
$pdf->SetFont('helvetica','',7);
if (sizeof($result) > 0) {
  $rf=false;
  $nr=0;
  $pdf->SetFillColor(238,238,238);
  foreach ($result as $key => $row) {
    $nr++;
    $rf=$nr%2;
    $datadoc = substr($row['datdoc'],8,2).'-'.substr($row['datdoc'],5,2).'-'.substr($row['datdoc'],0,4);
    $datareg = substr($row['datreg'],8,2).'-'.substr($row['datreg'],5,2).'-'.substr($row['datreg'],0,4);
    $movQuanti = $row['quanti']*$row['operat'];
    $pdf->Cell(20,3,$datareg,1,0,'C',$rf);
    $pdf->Cell(36,3,$row['caumag'].'-'.substr($row['descri'],0,25),1, 0, 'L',$rf, '', 1);
    $pdf->Cell(83,3,$row['artico'].' - '.substr($row['desart'],0,70),1, 0, 'L',$rf, '', 1);
	  $pdf->Cell(22,3,substr($row['id_lotmag'],-20),1, 0, 'L',$rf, '', 1); // L'identificatore lotto, se troppo lungo, viene accorciato agli ultimi 15 caratteri
    $pdf->Cell(56,3,$row['desdoc'].' del '.$datadoc,1, 0, 'L',$rf, '', 1);
	  if ($_GET['pr']==1){
      $pdf->Cell(17,3,number_format($row['prezzo'],$admin_aziend['decimal_price'],',','.'),1,0,'R',$rf);
      $pdf->Cell(18,3,gaz_format_number(CalcolaImportoRigo($row['quanti'],$row['prezzo'],array($row['scochi'],$row['scorig']))),1,0,'R',$rf);
    } else {
      $row['ragsoc']=(isset($row['ragsoc']))?$row['ragsoc']:'';
      $pdf->Cell(20,3,substr($row['ragsoc'],0,30),1, 0, 'L',$rf, '', 1);
	  }
	  $pdf->Cell(10,3,$row['unimis'],1,0,'C',$rf);
    $pdf->Cell(15,3,gaz_format_quantity($movQuanti,1,$admin_aziend['decimal_quantity']),1,1,'R',$rf);
  }
}
$pdf->Output();
?>
