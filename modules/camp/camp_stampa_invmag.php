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

if (!isset($_GET['ri']) or
    !isset($_GET['rf'])) {
    header("Location: camp_select_invmag.php");
    exit;
}

function getMovements($date_ini,$date_fin)
    {
        global $gTables,$admin_aziend;
        $m=array();
        $where="datreg BETWEEN $date_ini AND $date_fin";
        $what=$gTables['movmag'].".*, ".
              $gTables['caumag'].".codice, ".$gTables['caumag'].".descri, ".
              $gTables['artico'].".codice, ".$gTables['artico'].".descri AS desart, ".$gTables['artico'].".unimis, ".$gTables['artico'].".scorta, ".$gTables['artico'].".catmer ";
        $table=$gTables['movmag']." LEFT JOIN ".$gTables['caumag']." ON (".$gTables['movmag'].".caumag = ".$gTables['caumag'].".codice)
               LEFT JOIN ".$gTables['artico']." ON (".$gTables['movmag'].".artico = ".$gTables['artico'].".codice)";
        $rs=gaz_dbi_dyn_query ($what,$table,$where, 'datreg ASC, clfoco ASC');
        while ($r = gaz_dbi_fetch_array($rs)) {
            $m[] = $r;
        }
        return $m;
    }
// Antonio Germani inizio - creo e popolo array per la descrizione categorie merceologiche $des_descri
$result = gaz_dbi_dyn_query ('*', $gTables['catmer']);
	$des_descri = array(); $nd=1;
	while ($a_row = gaz_dbi_fetch_array($result)){
	$des_descri[$nd]=$a_row["descri"];
	$nd=$nd+1;
	}
// Antonio Germani fine - creazione array descrizione categorie merceologiche $des_descri
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
$gazTimeFormatter->setPattern('yyyyMMdd');
$result=getMovements($gazTimeFormatter->format(new DateTime('@'.$utsri)),$gazTimeFormatter->format(new DateTime('@'.$utsrf)));
$gazTimeFormatter->setPattern('dd MMMM yyyy');
require("../../config/templates/report_template_qc.php");
$title = array('luogo_data'=>$luogo_data,
               'title'=>"Quaderno di Campagna - INVENTARIO DI MAGAZZINO al ".$gazTimeFormatter->format(new DateTime('@'.$utsri)),
               'hile'=>array(array('lun' => 20,'nam'=>'Data Reg.'),
                             array('lun' => 40,'nam'=>'Codice articolo'),
                             array('lun' => 70,'nam'=>'Descrizione articolo'),
                             array('lun' => 70,'nam'=>'Categoria articolo'),

                             array('lun' => 10,'nam'=>'U.M.'),
                             array('lun' => 20,'nam'=>'Quantità')
                            )
              );

$pdf = new Report_template('L','mm','A4',true,'UTF-8',false,true);
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(42);
$pdf->SetFooterMargin(20);
$config = new Config;
$pdf->AddPage('L',$config->getValue('page_format'));
$pdf->SetFont('helvetica','',7);
if (sizeof($result) > 0) {
  while (list($key, $row) = each($result)) {
	  if (intval($row['caumag']) == 99){
      $datadoc = substr($row['datdoc'],8,2).'-'.substr($row['datdoc'],5,2).'-'.substr($row['datdoc'],0,4);
      $datareg = substr($row['datreg'],8,2).'-'.substr($row['datreg'],5,2).'-'.substr($row['datreg'],0,4);
      $movQuanti = $row['quanti']*$row['operat'];
      $pdf->Cell(20,3,$datareg,1,0,'C');
      $pdf->Cell(40,3,$row['codice'],1);
      $pdf->Cell(70,3,$row['artico'].' - '.$row['desart'],1);
      $pdf->Cell(70,3,$des_descri[$row['catmer']],1);

      $pdf->Cell(10,3,$row['unimis'],1,0,'C');
      $pdf->Cell(20,3,gaz_format_quantity($movQuanti,1,$admin_aziend['decimal_quantity']),1,1,'R');
	  }
  }
}
$pdf->Output();
?>
