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
}

if (!isset($_GET['ri']) ||
    !isset($_GET['rf']) ||
    !isset($_GET['ci']) ||
    !isset($_GET['cf']) ||
    !isset($_GET['ai']) ||
    !isset($_GET['af'])) {
    header("Location: select_schart.php");
    exit;
}
if (empty($_GET['af'])){
$_GET['af'] = 'zzzzzzzzzzzzzzzzz';
}
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data=$admin_aziend['citspe'].", lì ";
if (isset($_GET['ds'])) {
  $giosta = substr($_GET['ds'],0,2);
  $messta = substr($_GET['ds'],2,2);
  $annsta = substr($_GET['ds'],4,4);
  $utssta= mktime(12,0,0,$messta,$giosta,$annsta);
  $luogo_data .= ucwords($gazTimeFormatter->format(new DateTime($annsta.'-'.$messta.'-'.$giosta)));
} else {
  $luogo_data .= ucwords($gazTimeFormatter->format(new DateTime()));
}

require("../../config/templates/report_template.php");
require("lang.".$admin_aziend['lang'].".php");
$script_transl=$strScript['stampa_schart.php'];
$giori = substr($_GET['ri'],0,2);
$mesri = substr($_GET['ri'],2,2);
$annri = substr($_GET['ri'],4,4);
$utsri= mktime(0,0,0,intval($mesri),intval($giori),intval($annri));
$giorf = substr($_GET['rf'],0,2);
$mesrf = substr($_GET['rf'],2,2);
$annrf = substr($_GET['rf'],4,4);
$utsrf= mktime(0,0,0,$mesrf,$giorf,$annrf);
$gazTimeFormatter->setPattern('yyyyMMdd');
$where = " catmer BETWEEN ".$_GET['ci']." AND ".$_GET['cf']." AND".
         " artico BETWEEN '".$_GET['ai']."' AND '".$_GET['af']."' AND".
         " datreg BETWEEN ".$gazTimeFormatter->format(new DateTime($annri.'-'.$mesri.'-'.$giori))." AND ".$gazTimeFormatter->format(new DateTime($annrf.'-'.$mesrf.'-'.$giorf));
$what = $gTables['movmag'].".*, ".
        $gTables['caumag'].".codice, ".$gTables['caumag'].".descri AS descau, ".
        $gTables['clfoco'].".codice, ".
		$gTables['lotmag'].".identifier, ".
        $gTables['orderman'].".id AS id_orderman, ".$gTables['orderman'].".description AS desorderman, ".
        $gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2, ".
        $gTables['artico'].".codice, ".$gTables['artico'].".descri AS desart, ".$gTables['artico'].".web_url, ".$gTables['artico'].".unimis, ".$gTables['artico'].".scorta, ".$gTables['artico'].".image, ".$gTables['artico'].".catmer ";
        $table=$gTables['movmag']." LEFT JOIN ".$gTables['caumag']." ON ".$gTables['movmag'].".caumag = ".$gTables['caumag'].".codice
               LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['movmag'].".clfoco = ".$gTables['clfoco'].".codice
               LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra
               LEFT JOIN ".$gTables['orderman']." ON ".$gTables['movmag'].".id_orderman = ".$gTables['orderman'].".id
               LEFT JOIN ".$gTables['artico']." ON ".$gTables['movmag'].".artico = ".$gTables['artico'].".codice
			   LEFT JOIN ".$gTables['lotmag']." ON ".$gTables['movmag'].".id_lotmag = ".$gTables['lotmag'].".id";
$result = gaz_dbi_dyn_query ($what, $table,$where,"catmer ASC, artico ASC, datreg ASC, id_mov ASC");

$item_head = array('top'=>array(array('lun' => 32,'nam'=>$script_transl['item_head'][0]),
                                array('lun' => 18,'nam'=>$script_transl['item_head'][1]),
                                array('lun' => 110,'nam'=>$script_transl['item_head'][2]),
                                array('lun' => 10,'nam'=>$script_transl['item_head'][3]),
                                array('lun' => 18,'nam'=>$script_transl['item_head'][4])
                               )
                   );
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$title = array('luogo_data'=>$luogo_data,
               'title'=>$script_transl[0].$gazTimeFormatter->format(new DateTime('@'.$utsri)).$script_transl[1].$gazTimeFormatter->format(new DateTime('@'.$utsrf)),
               'hile'=>array(array('lun' => 16,'nam'=>$script_transl['header'][0]),
                             array('lun' => 30,'nam'=>$script_transl['header'][1]),
                             array('lun' => 100,'nam'=>$script_transl['header'][2]),
                             array('lun' => 17,'nam'=>$script_transl['header'][3]),
                             array('lun' => 8,'nam'=>$script_transl['header'][4]),
                             array('lun' => 17,'nam'=>$script_transl['header'][5]),
                             array('lun' => 17,'nam'=>$script_transl['header'][6]),
                             array('lun' => 17,'nam'=>$script_transl['header'][7]),
                             array('lun' => 20,'nam'=>$script_transl['header'][8]),
                             array('lun' => 20,'nam'=>$script_transl['header'][9])
                            )
              );
$aRiportare = array('top'=>array(array('lun' => 222,'nam'=>$script_transl['top']),
                           array('lun' => 20,'nam'=>''),
                           array('lun' => 20,'nam'=>'')
                           ),
                    'bot'=>array(array('lun' => 222,'nam'=>$script_transl['bot']),
                           array('lun' => 20,'nam'=>''),
                           array('lun' => 20,'nam'=>'')
                           )
                    );
$pdf = new Report_template();
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(51);
$config = new Config;
$gForm = new magazzForm();
$pdf->SetFont('helvetica','',7);
$ctrlArtico = "";
$ctrl_id=0;
$mval['q_g']=0;
$mval['q_g']=0;
$mval['v_g']=0;
$mval['v_g']=0;
$gazTimeFormatter->setPattern('dd-MM-yyyy');
$rf=false;
$nr=0;
$pdf->SetFillColor(238,238,238);
while ($mv = gaz_dbi_fetch_array($result)) {
  $nr++;
  $rf=$nr%2;
  $pdf->setRiporti($aRiportare);
  if ($ctrlArtico != $mv['artico']) {
    if (!empty($ctrlArtico)) {
      $pdf->StartPageGroup();
      $pdf->SetFont('helvetica','B',8);
      $pdf->Cell($aRiportare['top'][0]['lun'],4,$script_transl['tot'].$gazTimeFormatter->format(new DateTime('@'.$utsrf)).' : ',1,0,'R');
      $pdf->Cell($aRiportare['top'][1]['lun'],4,$aRiportare['top'][1]['nam'],1,0,'R');
      $pdf->Cell($aRiportare['top'][2]['lun'],4,$aRiportare['top'][2]['nam'],1,0,'R');
      $pdf->SetFont('helvetica','',7);
     }
     $aRiportare['top'][1]['nam'] = 0;
     $aRiportare['bot'][1]['nam'] = 0;
     $aRiportare['top'][2]['nam'] = 0;
     $aRiportare['bot'][2]['nam'] = 0;
     $item_head['bot']= array(array('lun' => 32,'nam'=>$mv['artico']),
                              array('lun' => 18,'nam'=>$mv['catmer']),
                              array('lun' => 110,'nam'=>$mv['desart']),
                              array('lun' => 10,'nam'=>$mv['unimis']),
                              array('lun' => 18,'nam'=>number_format($mv['scorta'],1,',',''))
                              );
    $pdf->setItemGroup($item_head);
    $pdf->setRiporti('');
    $pdf->AddPage('L',$config->getValue('page_format'));
  }
  if (!empty($mv['image'])){
    $pdf->Image('@'.$mv['image'], 250, 22, 10, 0,'', '','', true, 300, '', false, false, 0, false, false, false);
  }
  // passo tutte le variabili al metodo in modo da non costringere lo stesso a fare le query per ricavarsele
  $magval= $gForm->getStockValue($mv['id_mov'],$mv['artico'],$mv['datreg'],$admin_aziend['stock_eval_method']);
  $mval=end($magval);
  $aRiportare['top'][1]['nam'] = gaz_format_quantity($mval['q_g'],1,$admin_aziend['decimal_quantity']);
  $aRiportare['bot'][1]['nam'] = gaz_format_quantity($mval['q_g'],1,$admin_aziend['decimal_quantity']);
  $aRiportare['top'][2]['nam'] = gaz_format_number($mval['v_g']);
  $aRiportare['bot'][2]['nam'] = gaz_format_number($mval['v_g']);
  $pdf->Cell(16,4,gaz_format_date($mv['datreg']),'LTR',0,'C',$rf);
  $pdf->Cell(30,4,$mv['caumag'].'-'.substr($mv['descau'],0,17),'TR',0,'',$rf,'',1);
  $accdescr=$mv['desdoc'];
  if ($mv['id_orderman']>0){
    $accdescr.=' '.$mv['desorderman'];
  }
  $accdescr.= ' del '.gaz_format_date($mv['datdoc']);
  if (isset($mv['ragso1']) && strlen($mv['ragso1'])>3) {
    $accdescr.= $mv['ragso1'].' '.$mv['ragso2'];
  }
  if (intval($mv['id_lotmag'])>0){
    $accdescr.=" lotto: ".$mv['identifier'];
  }
  $pdf->Cell(100,4,substr($accdescr,0,120),'TR',0,'',$rf,'',1);
  $pdf->Cell(17,4,number_format($mv['prezzo'],$admin_aziend['decimal_price'],',',' '),'TR',0,'R',$rf);
  $pdf->Cell(8,4,$mv['unimis'],'TR',0,'C',$rf);
  $pdf->Cell(17,4,gaz_format_quantity($mv['quanti']*$mv['operat'],1,$admin_aziend['decimal_quantity']),1,0,'R',$rf);
  if ($mv['operat']==1) {
    $pdf->Cell(17,4,number_format($mv['prezzo']*$mv['quanti'],$admin_aziend['decimal_price'],',',''),1,0,'R',$rf);
    $pdf->Cell(17,4,'',1,0,'',$rf);
  } else {
    $pdf->Cell(17,4,'',1,0,'',$rf);
    $pdf->Cell(17,4,number_format($mv['prezzo']*$mv['quanti'],$admin_aziend['decimal_price'],',',''),1,0,'R',$rf);
  }
  $pdf->Cell(20,4,gaz_format_quantity($mval['q_g'],1,$admin_aziend['decimal_quantity']),1,0,'R',$rf);
  $pdf->Cell(20,4,gaz_format_number($mval['v_g']),1,1,'R',$rf);
  $ctrlArtico = $mv['artico'];
}
$pdf->SetFont('helvetica','B',8);
$pdf->Cell($aRiportare['top'][0]['lun'],4,$script_transl['tot'].$gazTimeFormatter->format(new DateTime('@'.$utsrf)).' : ',1,0,'R');
$pdf->Cell($aRiportare['top'][1]['lun'],4,$aRiportare['top'][1]['nam'],1,0,'R');
$pdf->Cell($aRiportare['top'][2]['lun'],4,$aRiportare['top'][2]['nam'],1,0,'R');
$pdf->SetFont('helvetica','',7);
$pdf->setRiporti('');
$pdf->Output();
?>
