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
use ParagonIE\CipherSweet\Backend\FIPSCrypto;
use ParagonIE\CipherSweet\KeyProvider\StringProvider;
use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\BlindIndex;
//use ParagonIE\CipherSweet\EncryptedField;
use ParagonIE\CipherSweet\EncryptedRow;


//Aggiunta di tabelle aziendali specifiche del modulo Hospital
$tn = ['encrypted_personal_data','bed','room','ward','hospital_pdflog'];
foreach ($tn as $v) {
  $gTables[$v] = $table_prefix . "_" . $id . $v;
}
$gTables['asl'] = $table_prefix . "_asl";

function getDecryptedPersonalData($table,$bidx,$values, &$row, &$indexes): int
{
  $fields =['id_patient','first_name','last_name','sexper','birth_date','birth_place','birth_prov_code','birth_country','tax_code','iban','health_card_number','telephone','residence_address','residence_place','residence_postal_code','residence_prov_code','affiliated_health_company','marital_status','e_mail','note','legal_trustee','status','doc_expiry'];
  try {
    $fips = new FIPSCrypto();
    $provider = new StringProvider(
      hash('sha256',$_SESSION['aes_key'])
    );
    $engine = new CipherSweet($provider, $fips);
    $row = new EncryptedRow($engine, $table);
    foreach($fields as $v){
      $row->addTextField($v);
    }
    $row->addBlindIndex( substr($bidx,0,-5), new BlindIndex( $bidx, [], 32 ));
    $indexes = $row->getAllBlindIndexes([substr($bidx,0,-5) => $values]);
    return 0;  //function processed without errors
  } catch (Exception $ex) {
    echo "Error: " . $ex->getMessage();
    return -1;
  }
}


function DecryptPersonalData($table,$bidx,$value) {
  $ret = getDecryptedPersonalData($table, $bidx, $value, $rowP, $indexes);
  try {
    $acc=[];
    $blindindex = $indexes[$bidx];
    $sql = "SELECT * FROM `".$table."` WHERE `".$bidx."` = '" . $blindindex . "'";
    $rs = gaz_dbi_query($sql);
    while($r = gaz_dbi_fetch_assoc($rs)){
      $row = $rowP->decryptRow($r);
      $row['id'] = $row['id_patient'];
      $row['patient_number'] = hexdec($row['id_patient_bidx']);
      $row['value'] = $row[substr($bidx,0,-5)];
      $row['label'] = $row['first_name'].' '.$row['last_name'].' '.$row['telephone'].' '.$row['residence_address'];
      // i campi non criptati li riprendo in chiaro
      $row['adminid']=$r['adminid'];
      $row['last_revision']=$r['last_revision'];
      $acc[] = $row;
    }
    return $acc;
  } catch (Exception $ex) {
    echo "Error: " . $ex->getMessage();
  }
}

function DeletePersonalData($table,$bidx,$value) {
  $ret = getDecryptedPersonalData($table, $bidx, $value, $rowP, $indexes);
  try {
    $blindindex = $indexes[$bidx];
    $sql = "DELETE FROM `".$table."` WHERE `".$bidx."` = '" . $blindindex . "'";
    $rs = gaz_dbi_query($sql);
  } catch (Exception $ex) {
    echo "Error: " . $ex->getMessage();
  }
}


function prepareEncryptedPersonalData($table,$fields, $bidx, $values, &$prepared): int
{
  try {
    $fips = new FIPSCrypto();
    $provider = new StringProvider(
      hash('sha256',$_SESSION['aes_key'])
    );
    $engine = new CipherSweet($provider, $fips);
    $row = new EncryptedRow($engine, $table);
    foreach($fields as $v){
      $row->addTextField($v);
    }
    if (is_array($bidx)){
      foreach($bidx as $b){
        $row->addBlindIndex(substr($b,0,-5), new BlindIndex($b,[],32));
      }
    } else {
      $row->addBlindIndex(substr($bidx,0,-5), new BlindIndex($bidx,[],32));
    }
    foreach($fields as $v){
      $values[$v]=isset($values[$v])?$values[$v]:'';
    }
    $prepared = $row->prepareRowForStorage($values);
    return 0;
  } catch (Exception $ex) {
    echo "Error: " . $ex->getMessage();
    return -1;
  }
}

function EncryptPersonalData($table,$values) {
  $fields =['id_patient','first_name','last_name','sexper','birth_date','birth_place','birth_prov_code','birth_country','tax_code','iban','health_card_number','telephone','residence_address','residence_place','residence_postal_code','residence_prov_code','affiliated_health_company','marital_status','e_mail','note','legal_trustee','status','doc_expiry'];
  $bidx=['id_patient_bidx','first_name_bidx','last_name_bidx','tax_code_bidx','health_card_number_bidx'];
  foreach($fields as $v){
    $values[$v]=isset($values[$v])?$values[$v]:'';
  }
  $ret = prepareEncryptedPersonalData($table, $fields, $bidx, $values, $prepared);
  try {
    $kvq = array_merge($prepared[0],$prepared[1]);
    $kacc=" (";
    $vacc=" VALUES (";
    foreach($kvq as $k=>$v){
      $kacc .="`".$k."`, ";
      $vacc .="'".$v."', ";
    }
    $kacc .="`adminid`,`last_revision` ";
    $vacc .="'".$_SESSION['user_name']."', '".date("Y-m-d H:i:s")."' ";
    $sql = "INSERT INTO `".$table."`".$kacc.")".$vacc.")";
    $rs = gaz_dbi_query($sql);
  } catch (Exception $ex) {
    echo "Error: " . $ex->getMessage();
  }
}
?>
