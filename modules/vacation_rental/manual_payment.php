<?php
/*
 --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (https://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
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
  $conto=strtok( $_POST['target_account'], '-' );
  $type=substr($_POST['type'],0,25);
  $sql="INSERT INTO ".$azTables."rental_payments (type,item_number,txn_id,payment_gross,currency_code,payment_status,id_tesbro,created,conto) VALUES('".addslashes($type)."','".addslashes($item_number)."','".addslashes($txn_id)."','".addslashes($payment_gross)."','".addslashes($currency_code)."','".addslashes($payment_status)."','".addslashes($id_tesbro)."','".date("Y-m-d h:i:s")."','".intval($conto)."' )";
  if ($result = mysqli_query($link, $sql)) {
    $rental_id = gaz_dbi_last_id();
  }else{
    echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
  if (substr($type,0,21)=="Caparra_confirmatoria"){// se è stata inserita una caparra confirmatoria
    $vacation_caparra_dare=gaz_dbi_get_row($gTables['company_config'], "var", 'vacation_caparra_dare')['val'];
    $vacation_caparra_avere=gaz_dbi_get_row($gTables['company_config'], "var", 'vacation_caparra_avere')['val'];
    if($vacation_caparra_dare>0 && $vacation_caparra_avere>0){// se sono stati impostati i conti
      // registro il movimento contabile del pagamento 'provvisorio'

      $tesbro=gaz_dbi_get_row($gTables['tesbro'], "id_tes", $id_tesbro);
      $tes_val = array('caucon' => '',
        'descri' => "RISCOSSO ".$type." prenotazione n.".$tesbro['numdoc']." del ".gaz_format_date($tesbro["datemi"]),
        'datreg' => date("Y-m-d"),
        'datdoc' => date("Y-m-d"),
        'datliq' => date("Y-m-d"),
        'seziva' => $seziva,
        'clfoco' => $tesbro['clfoco'],
        'id_doc' => 0,
        'protoc' => 0,
        'operat' => 0
      );
      tesmovInsert($tes_val);

      $tes_id = gaz_dbi_last_id();
      rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'D', 'codcon' => intval($conto), 'import' => $payment_gross, 'id_orderman' => 0 ));
      rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'A', 'codcon' => $vacation_caparra_avere, 'import' => $payment_gross, 'id_orderman' => 0 ));
      $rig_id = gaz_dbi_last_id();
      $id_tesdoc_ref=intval(date("Y").str_pad($rig_id,11,"0",STR_PAD_LEFT));
      paymovInsert(array('id_tesdoc_ref' => $id_tesdoc_ref, 'id_rigmoc_pay' => $rig_id, 'id_rigmoc_doc' => 0, 'amount' => $payment_gross, 'expiry' => substr(date("Y-m-d"),0,10)));
      $paymov_id = gaz_dbi_last_id();
      gaz_dbi_put_query($gTables['rental_payments'], " payment_id = ".$rental_id, "id_paymov", $paymov_id);

    }
  }
  if (substr($type,0,21)=="Deposito_cauzionale"){// se è stato inserito un deposito cauzionale
    $vacation_cauzione_dare=gaz_dbi_get_row($gTables['company_config'], "var", 'vacation_cauzione_dare')['val'];
    $vacation_cauzione_avere=gaz_dbi_get_row($gTables['company_config'], "var", 'vacation_cauzione_avere')['val'];
    if($vacation_cauzione_dare>0 && $vacation_cauzione_avere>0){// se sono stati impostati i conti
      // registro il movimento contabile del pagamento 'provvisorio'

      $tesbro=gaz_dbi_get_row($gTables['tesbro'], "id_tes", $id_tesbro);
      $tes_val = array('caucon' => '',
        'descri' => "RISCOSSO ".$type." prenotazione n.".$tesbro['numdoc']." del ".gaz_format_date($tesbro["datemi"]),
        'datreg' => date("Y-m-d"),
        'datdoc' => date("Y-m-d"),
        'datliq' => date("Y-m-d"),
        'seziva' => $seziva,
        'clfoco' => $tesbro['clfoco'],
        'id_doc' => 0,
        'protoc' => 0,
        'operat' => 0
      );
      tesmovInsert($tes_val);

      $tes_id = gaz_dbi_last_id();
      rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'D', 'codcon' => intval($conto), 'import' => $payment_gross, 'id_orderman' => 0 ));
      rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'A', 'codcon' => $vacation_cauzione_avere, 'import' => $payment_gross, 'id_orderman' => 0 ));
      $rig_id = gaz_dbi_last_id();
      $id_tesdoc_ref=intval(date("Y").str_pad($rig_id,7,"0",STR_PAD_LEFT));
      paymovInsert(array('id_tesdoc_ref' => $id_tesdoc_ref, 'id_rigmoc_pay' => $rig_id, 'id_rigmoc_doc' => 0, 'amount' => $payment_gross, 'expiry' => substr(date("Y-m-d"),0,10)));
      $paymov_id = gaz_dbi_last_id();
      gaz_dbi_put_query($gTables['rental_payments'], " payment_id = ".$rental_id, "id_paymov", $paymov_id);

    }

  }

  echo "Pagamento registrato";

}elseif (isset($_POST['type']) && $_POST['type']=="payment_list" && isset($_POST['ref'])){
  $n=0;
  $id_tesbro=  intval($_POST['ref']);
   $sql = "SELECT * FROM ".$azTables."rental_payments"." LEFT JOIN ".$azTables."clfoco"." ON ".$azTables."rental_payments".".conto = ".$azTables."clfoco".".codice WHERE id_tesbro = '".$id_tesbro."'";
  if ($result = mysqli_query($link, $sql)) {
    $return=array();
    while ($res = $result->fetch_assoc()){
			$return[$n]=$res;
			$n++;
		}
    echo json_encode($return);
  }else{
    echo "Error: " , $sql ;
  }
}else{
  echo "Non posso registrare pagamento";
}
?>
