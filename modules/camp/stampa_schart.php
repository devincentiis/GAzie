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
require ("../../modules/magazz/lib.function.php");
$admin_aziend=checkAdmin();

if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
}

if (!isset($_GET['ri']) or
    !isset($_GET['rf']) or
    !isset($_GET['ci']) or
    !isset($_GET['cf']) or
    !isset($_GET['ai']) or
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
   $utssta= mktime(0,0,0,$messta,$giosta,$annsta);
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
$utsri= mktime(0,0,0,$mesri,$giori,$annri);
$giorf = substr($_GET['rf'],0,2);
$mesrf = substr($_GET['rf'],2,2);
$annrf = substr($_GET['rf'],4,4);
$utsrf= mktime(0,0,0,$mesrf,$giorf,$annrf);
$gazTimeFormatter->setPattern('yyyyMMdd');

$where = " catmer BETWEEN ".$_GET['ci']." AND ".$_GET['cf']." AND".
         " artico BETWEEN '".$_GET['ai']."' AND '".$_GET['af']."' AND".
         " datreg BETWEEN ".$gazTimeFormatter->format(new DateTime($annri.'-'.$mesri.'-'.$giori))." AND ".$gazTimeFormatter->format(new DateTime($annrf.'-'.$mesrf.'-'.$giorf));
$what = $gTables['movmag'].".*, ".
        $gTables['caumag'].".codice, ".$gTables['caumag'].".descri, ".
        $gTables['clfoco'].".codice, ".
			  $gTables['camp_mov_sian'].".recip_stocc, ".
			  $gTables['lotmag'].".identifier, ".
        $gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2, ".
        $gTables['artico'].".codice, ".$gTables['artico'].".descri AS desart, ".$gTables['artico'].".web_url, ".$gTables['artico'].".unimis, ".$gTables['artico'].".scorta, ".$gTables['artico'].".image, ".$gTables['artico'].".catmer, ".$gTables['artico'].".quality";
        $table=$gTables['movmag']." LEFT JOIN ".$gTables['caumag']." ON ".$gTables['movmag'].".caumag = ".$gTables['caumag'].".codice
               LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['movmag'].".clfoco = ".$gTables['clfoco'].".codice
               LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra
			   LEFT JOIN ".$gTables['lotmag']." ON ".$gTables['lotmag'].".id_movmag = ".$gTables['movmag'].".id_mov
			   LEFT JOIN ".$gTables['camp_mov_sian']." ON ".$gTables['camp_mov_sian'].".id_movmag = ".$gTables['movmag'].".id_mov
               LEFT JOIN ".$gTables['artico']." ON ".$gTables['movmag'].".artico = ".$gTables['artico'].".codice";
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
                             array('lun' => 50,'nam'=>$script_transl['header'][1]),
                             array('lun' => 160,'nam'=>$script_transl['header'][2]),
                            // array('lun' => 17,'nam'=>$script_transl['header'][3]),
                             array('lun' => 8,'nam'=>$script_transl['header'][4]),
                             array('lun' => 17,'nam'=>$script_transl['header'][5]),
                            // array('lun' => 17,'nam'=>$script_transl['header'][6]),
                            // array('lun' => 17,'nam'=>$script_transl['header'][7]),
                             array('lun' => 20,'nam'=>$script_transl['header'][8])     //,
                            // array('lun' => 20,'nam'=>$script_transl['header'][9])
                            )
              );
$aRiportare = array('top'=>array(array('lun' => 251,'nam'=>$script_transl['top']),
                           array('lun' => 20,'nam'=>''),
                          // array('lun' => 20,'nam'=>'')
                           ),
                    'bot'=>array(array('lun' => 251,'nam'=>$script_transl['bot']),
                           array('lun' => 20,'nam'=>''),
                         //  array('lun' => 20,'nam'=>'')
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
$gazTimeFormatter->setPattern('dd-MM-yyyy');
while ($mv = gaz_dbi_fetch_array($result)) {
      $pdf->setRiporti($aRiportare);
      if ($ctrlArtico != $mv['artico']) {
         gaz_set_time_limit (30);
         if (!empty($ctrlArtico)) {
                   $pdf->StartPageGroup();
                   $pdf->SetFont('helvetica','B',8);
                   $pdf->Cell($aRiportare['top'][0]['lun'],4,$script_transl['tot'].$gazTimeFormatter->format(new DateTime('@'.$utsrf)).' : ',1,0,'R');
                   $pdf->Cell($aRiportare['top'][1]['lun'],4,$aRiportare['top'][1]['nam'],1,0,'R');
                   //$pdf->Cell($aRiportare['top'][2]['lun'],4,$aRiportare['top'][2]['nam'],1,0,'R');
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
        if (empty($mv['image'])){
           $pdf->setItemGroup($item_head);
        } else {
           $pdf->setItemGroup($item_head,$mv['image'],$mv['web_url']);
        }
        $pdf->setRiporti('');
        $pdf->AddPage('L',$config->getValue('page_format'));
      }

      // passo tutte le variabili al metodo in modo da non costringere lo stesso a fare le query per ricavarsele
      $magval= $gForm->getStockValue($mv['id_mov'],$mv['artico'],$mv['datreg'],$admin_aziend['stock_eval_method']);
      $r_span=count($magval);
      foreach ($magval as $mval) {
        $addes=$mv['desdoc'].' '.gaz_format_date($mv['datdoc']).' - '.$mv['ragso1'].' '.$mv['ragso2'];
        $aRiportare['top'][1]['nam'] = gaz_format_quantity($mval['q_g'],1,$admin_aziend['decimal_quantity']);
        $aRiportare['bot'][1]['nam'] = gaz_format_quantity($mval['q_g'],1,$admin_aziend['decimal_quantity']);
        //$aRiportare['top'][2]['nam'] = gaz_format_number($mval['v_g']);
        // $aRiportare['bot'][2]['nam'] = gaz_format_number($mval['v_g']);
        if ($ctrl_id <> $mv['id_mov']) {
          if (isset($mv['recip_stocc']) && strlen($mv['recip_stocc'])>0){
            $addes .=" Silos:".$mv['recip_stocc'];
          }
          if (strlen($mv['quality'])>0){
            $addes .=" Var.:".$mv['quality'];
          }if (isset($mv['identifier']) && strlen($mv['identifier'])>0){
            $addes .=" Lotto:".$mv['identifier'];
          }
          $pdf->Cell(16,4,gaz_format_date($mv['datreg']),'LTR',0,'C');
          $pdf->Cell(50,4,$mv['caumag'].'-'.substr($mv['descri'],0,17),'TR');
          $pdf->Cell(160,4,$addes,'TR', 0, 'L', 0, '', 1);
         /* $pdf->Cell(17,4,number_format($mv['prezzo'],$admin_aziend['decimal_price'],',',' '),'TR',0,'R');*/
          $pdf->Cell(8,4,$mv['unimis'],'TR',0,'C');
         } else {
              $pdf->Cell(16,4,'','LR');
              $pdf->Cell(50,4,'','R');
              $pdf->Cell(160,4,'','R');
              $pdf->Cell(17,4,'','R');
              $pdf->Cell(8,4,'','R');
         }
         $pdf->Cell(17,4,gaz_format_quantity($mval['q']*$mv['operat'],1,$admin_aziend['decimal_quantity']),1,0,'R');
       /*  if ($mv['operat']==1) {
             $pdf->Cell(17,4,number_format($mval['v'],$admin_aziend['decimal_price'],',',''),1,0,'R');
             $pdf->Cell(17,4,'',1);
         } else {
             $pdf->Cell(17,4,'',1);
             $pdf->Cell(17,4,number_format($mval['v'],$admin_aziend['decimal_price'],',',''),1,0,'R');
         }*/
         $pdf->Cell(20,4,gaz_format_quantity($mval['q_g'],1,$admin_aziend['decimal_quantity']),1,1,'R');
         /*$pdf->Cell(10,4,gaz_format_number($mval['v_g']),1,1,'R');*/
         $ctrl_id = $mv['id_mov'];
      }
      $ctrlArtico = $mv['artico'];
}
$pdf->SetFont('helvetica','B',8);
$pdf->Cell($aRiportare['top'][0]['lun'],4,$script_transl['tot'].$gazTimeFormatter->format(new DateTime('@'.$utsrf)).' : ',1,0,'R');
$pdf->Cell($aRiportare['top'][1]['lun'],4,$aRiportare['top'][1]['nam'],1,0,'R');
//$pdf->Cell($aRiportare['top'][2]['lun'],4,$aRiportare['top'][2]['nam'],1,0,'R');
$pdf->SetFont('helvetica','',7);
$pdf->setRiporti('');
$pdf->Output();
?>
