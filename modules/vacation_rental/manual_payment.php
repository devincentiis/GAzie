<?php
/*
 --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (http://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
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

// registrazione pagamento nel data base rental_payments

// prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}

require("../../library/include/datlib.inc.php");
//require_once("lib.function.php");

include_once("manual_settings.php");
$azTables = constant("table_prefix").$idDB;
$IDaz=preg_replace("/[^1-9]/", "", $azTables );

$servername = constant("Host");
$username = constant("User");
$pass = constant("Password");
$dbname = constant("Database");
$genTables = constant("table_prefix")."_";

$admin_aziend = checkAdmin();

// Create connection
$link = mysqli_connect($servername, $username, $pass, $dbname);
// Check connection
if (!$link) {
    die("Connection DB failed: " . mysqli_connect_error());
}
$link -> set_charset("utf8");

if (isset($_POST['type']) && isset($_POST['ref']) && isset($_POST['payment_gross']) && floatval($_POST['payment_gross'])<>0 ) {

  $id_tesbro=  intval($_POST['ref']);
  $type=substr($_POST['type'],0,10);
  $txn_id= substr($_POST['txn_id'],0,50);
  $payment_gross= floatval($_POST['payment_gross']);
  $currency_code=  $admin_aziend['curr_name'];
  $payment_status=  "Completed";

  $sql = "SELECT house_code FROM ".$azTables."rental_events"." WHERE id_tesbro = '".$id_tesbro."' AND type = 'ALLOGGIO' LIMIT 1";
  if ($result = mysqli_query($link, $sql)) {
    $rental = mysqli_fetch_assoc($result);
    $item_number =  $rental['house_code'];
  }else{
    echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }

  $sql="INSERT INTO ".$azTables."rental_payments (type,item_number,txn_id,payment_gross,currency_code,payment_status,id_tesbro,created) VALUES('".addslashes($type)."','".addslashes($item_number)."','".addslashes($txn_id)."','".addslashes($payment_gross)."','".addslashes($currency_code)."','".addslashes($payment_status)."','".addslashes($id_tesbro)."','".date("Y-m-d h:i:s")."')";
  if ($result = mysqli_query($link, $sql)) {
  }else{
    echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }

  echo "Pagamento registrato";

}elseif ($_POST['type']=="payment_list" && isset($_POST['ref'])){
  $n=0;
  $id_tesbro=  intval($_POST['ref']);
   $sql = "SELECT * FROM ".$azTables."rental_payments"." WHERE id_tesbro = '".$id_tesbro."'";
  if ($result = mysqli_query($link, $sql)) {
    $return=array();
    while ($res = $result->fetch_assoc()){
			$return[$n]=$res;
			$n++;
		}
    echo json_encode($return);
  }else{
    echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
}else{
  echo "Non posso registrare pagamento";
}
?>
