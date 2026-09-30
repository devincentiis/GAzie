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
set_time_limit(3600);
global $gTables;


gaz_dbi_put_row($gTables['company_config'],'var','last_fae_email','val',0);

$mails = array();

// Get some mail



$array_extimg=array('.xml');
$arrayfile=array();


$xxx =   $_SERVER['DOCUMENT_ROOT']."code/data/files/inviatesdi";
$arrayfile = dirContents($xxx, array('/^.*\.xml$/i'), true);

$domDoc = new DOMDocument;


echo "I file vengono letti da: " .  $_SERVER['DOCUMENT_ROOT']."code/data/files/inviatesdi" . "<br/>";

foreach($arrayfile[1] as $mailId) {
 
    $nome_file=$mailId; 
    $nome_file1=substr($mailId, strlen($xxx)+1);

    $nome_file_ret = "";
    
    
    $domDoc->load($mailId);
    $xpath = new DOMXPath($domDoc);	
	
    $result = $xpath->query("//Data")->item(0);
    $data_ora_ricezione = $result->textContent;      
    
    $result = $xpath->query("//ProgressivoInvio")->item(0);
    $progressivo_invio = $result->textContent;
    
    $sev_iva =substr($progressivo_invio,2,1);
    $protocollo =substr($progressivo_invio,3);
    
    
    
    
    
    
    $where = " protoc = ".$protocollo . ' and seziva = '. $sev_iva . " and datemi = '". $data_ora_ricezione . "'";
    
    
    $result = gaz_dbi_dyn_query ("*", $gTables['tesdoc'], $where, $orderby, $limit, $passo);
    $r = gaz_dbi_fetch_array($result);
    
    
    if ($r == false) {
     $id_tes = 0; }
    else {
     $id_tes = $r['id_tes'];
    }
    
    echo $progressivo_invio . "_" . $sev_iva . "_" .   $protocollo . "_" .$id_tes . "<br/>";
    
	  $errore = "";  
    $status=""; 
    
   
   $verifica = gaz_dbi_get_row($gTables['fae_flux'], 'filename_ori', $nome_file1);   
   if ($verifica == false) { 
 
   $valori=array('filename_ori'=>$nome_file1,
         'id_tes_ref'=>$id_tes,
				 'exec_date'=>$data_ora_ricezione,
         'received_date'=>$data_ora_ricezione,
         'delivery_date'=>$data_ora_ricezione,
				 'filename_son'=>'',
				 'id_SDI'=>0,
         'filename_ret'=>$nome_file_ret,
         'mail_id'=>0,
				 'data'=>'',
				 'flux_status'=>'@',
         'progr_ret'=>'000',
				 'flux_descri'=>'');
    
    fae_fluxInsert($valori);
    echo "Processo  ".$nome_file."<br/>";
    } else {
    echo "File esistente  ".$nome_file."<br/>";
    }
        
    flush();
    ob_flush();
    sleep(1);
    
}
    echo "Completato";


function dirContents($searchDir, $pregarr = array(), $inclusive = false){
 	$lar = array(array(), array());
	if(false === $handle = opendir($searchDir)) return false;
	while(false !== $link = readdir($handle)){
		if($link !== '.' && $link !== '..'){
			$validLink = true;
			foreach($pregarr as $value){
				$validLink ^= preg_match($value, $link);
			}
			$validLink ^= $inclusive;
			if($validLink){
				$temp = $searchDir . DIRECTORY_SEPARATOR . $link;
				if(is_dir($temp)){
					array_push($lar[0], $temp);
					$temp = dirContents($temp, $pregarr, $inclusive);
					$lar[0] = array_merge($lar[0], $temp[0]);
					$lar[1] = array_merge($lar[1], $temp[1]);
				}else{
					array_push($lar[1], $temp);
				}
			}
		}
	}
	closedir($handle);
	return $lar;
}

?>