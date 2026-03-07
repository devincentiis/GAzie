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
require("../../modules/config/lib.function.php");
$aut = 8;
if (!isset($_POST['ritorno'])) {
	$_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}
$global_config = new Config;
$user_data = gaz_dbi_get_row($gTables['admin'], "user_name", $_SESSION["user_name"]);
$msg=['err'=>[],'war'=>[]];
if ((isset($_POST['Update'])) || ( isset($_GET['Update']))) {
	$toDo = 'update';
	if (!isset($_GET["user_name"])) {
		header("Location: " . $_POST['ritorno']);
		exit;
	}
} else {
	$toDo = 'insert';
	$aut = 5;
}
$admin_aziend = checkAdmin($aut);
if (isset($_POST['Return'])) {
	header("Location: " . $_POST['ritorno']);
	exit;
}

// DEFINIZIONE SCRIPT DA ESCLUDERE PER COLLABORATORI E CAPI-REPARTO
$excluded_script=[
  0=>['employees_timesheet','admin_healtworker','report_healtworkers'],
  5=>['employees_timesheet','admin_healtworker','report_healtworkers'],
  6=>['employees_timesheet','admin_healtworker'],
  7=>[]
];

if (!empty($_FILES['signimg']['name'])) { // ho aggiunto l'immagine signimg
  $un=preg_replace("/[^A-Za-z0-9]/", '',substr($_POST['user_name'], 0, 64));
  if (!($_FILES['signimg']['type'] == "image/png" || $_FILES['signimg']['type'] == "image/x-png" )) $msg['err'][]= 'signimg_type'; // accetto solo png con trasparenze
  $extension = pathinfo($_FILES['signimg']['name'])['extension'];
  $tmp_name = $_FILES['signimg']['tmp_name'];
  $src = imagecreatefromstring(file_get_contents($tmp_name));
  $is_alpha = (ord(@file_get_contents($tmp_name, NULL, NULL, 25, 1)) == 6);
  if (!$is_alpha) {  $msg['err'][]= 'signimg_noalpha'; } // non ha trasparenze
  if ($_FILES['signimg']['size'] > 1000000) {
    // anzichè segnalare l'errore ridimensiono
    $maxDim=1024;
    list($width,$height,$type,$attr) = getimagesize($tmp_name);
    if ( $width > $maxDim || $height > $maxDim ) {
      $ratio = $width/$height;
      if( $ratio > 1) {
        $new_width = intval($maxDim);
        $new_height = intval($maxDim/$ratio);
      } else {
        $new_width = intval($maxDim*$ratio);
        $new_height = intval($maxDim);
      }
      $dst = imagecreatetruecolor( $new_width, $new_height );
      imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
      imagedestroy( $src );
      imagepng( $dst, $tmp_name); // adjust format as needed
      imagedestroy( $dst );
      $extension = 'png';
    }
    // fine ridimensionamento immagine di base
  }
  if (count($msg['err']) < 1 ) {
    $rs_signimg = gaz_dbi_dyn_query("id_doc", $gTables['files'],"table_name_ref='worker_signimg' AND adminid='".$un."'","id_doc",0,1);
    $signimg=gaz_dbi_fetch_array($rs_signimg);
    if ($signimg) { // controllo presenza immagine signimg in caso di update
      gaz_dbi_query("UPDATE ".$gTables['files']." SET status=".time().",content=TO_BASE64(AES_ENCRYPT('".bin2hex(file_get_contents($tmp_name))."','".$_SESSION['aes_key']."')),extension='".$extension."', adminid='".$un."',item_ref='signature_img' WHERE id_doc=".$signimg['id_doc']);
    } else {
      gaz_dbi_query("INSERT INTO ".$gTables['files']." (table_name_ref, item_ref, content, extension, status, adminid) VALUES ('worker_signimg','signature_img',TO_BASE64(AES_ENCRYPT('".bin2hex(file_get_contents($tmp_name))."','".$_SESSION['aes_key']."')), 'png',".time().", '".$un."' )");

    }
  }
}


if ((isset($_POST['Insert'])) || (isset($_POST['Update']))) {   //se non e' il primo accesso
	$form['user_lastname'] = substr($_POST['user_lastname'], 0, 30);
	$form['user_firstname'] = substr($_POST['user_firstname'], 0, 30);
	$form['sexper'] = substr($_POST['sexper'], 0, 1);
	$form['indspe'] = substr($_POST['indspe'], 0, 60);
	$form['capspe'] = substr($_POST['capspe'], 0, 10);
	$form['citspe'] = substr($_POST['citspe'], 0, 60);
	$form['prospe'] = substr($_POST['prospe'], 0, 2);
	$form['codfis'] = substr($_POST['codfis'], 0, 16);
	$form['datnas'] = substr($_POST['datnas'], 0, 10);
	$form['user_email'] = trim(substr($_POST['user_email'],0,50));
	$form['telefo'] = trim(substr($_POST['telefo'],0,30));
	$form['id_anagra'] =  intval($_POST['id_anagra']);
	$form['id_staff'] =  intval($_POST['id_staff']);
  $form['az_email'] = trim($_POST['az_email']);
	$form['lang'] = substr($_POST['lang'], 0, 15);
	$form['theme'] = filter_input(INPUT_POST,'theme');
	$form['style'] = substr($_POST['style'], 0, 30);
	$form['skin'] = substr($_POST['skin'], 0, 30);
	$form['Abilit'] = intval($_POST['Abilit']);
  $form['hidden_req'] = $_POST['hidden_req'];
	$form['company_id'] = intval($_POST['company_id']);
	$form['Access'] = intval($_POST['Access']);
	$form['user_name'] = preg_replace("/[^A-Za-z0-9]/", '',substr($_POST['user_name'], 0, 64));
	$form['user_password_new'] = $toDo=='insert'?substr($_POST['user_password_new'], 0, 65):'';
	$form['user_active'] = intval($_POST['user_active']);
	$form['id_contract'] = intval($_POST['id_contract']);
	$form['codice_campi'] = intval($_POST['codice_campi']);
	$form['iban'] = substr($_POST['iban'],0,27);
	$form['body_text'] = filter_input(INPUT_POST, 'body_text');
	if ($toDo == 'insert') {
		$rs_utente = gaz_dbi_dyn_query("*", $gTables['admin'], "user_name = '" . $form["user_name"] . "'", "user_name DESC", 0, 1);
		$risultato = gaz_dbi_fetch_array($rs_utente);
		if ($risultato) {	$msg['err'][] = 'exlogin'; }
	}
} elseif ((!isset($_POST['Update'])) && (isset($_GET['Update']))) { // primo accesso update
  $anagrafica = new Anagrafica();
	$form = gaz_dbi_get_row($gTables['admin'], "user_name", preg_replace("/[^A-Za-z0-9]/", '',substr($_GET['user_name'], 0, 64)));
	if (!$form){
		header("Location: " . $_POST['ritorno']);
		exit;
	}
	// attingo il valore del motore di template dalla tabella configurazione utente
	$admin_config = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'theme', "AND adminid = '{$form['user_name']}'");
	$form = gaz_dbi_get_row($gTables['admin'], "user_name", preg_replace("/[^A-Za-z0-9]/", '',substr($_GET["user_name"], 0, 64)));
	$anagra = gaz_dbi_get_row($gTables['anagra'], "id",$form['id_anagra']);
	$staff_clfoco = gaz_dbi_get_row($gTables['clfoco'],"id_anagra",$form['id_anagra']);
	$staff = gaz_dbi_get_row($gTables['staff'], "id_clfoco",$staff_clfoco['codice']);
  $form['telefo'] = $anagra['telefo'];
  $form['id_anagra'] = $anagra['id'];
	$form['id_staff'] = $staff['id_staff'];
  $form['id_clfoco']=$staff_clfoco['codice'];
	$form['sexper'] = $anagra['sexper'];
	$form['indspe'] = $anagra['indspe'];
	$form['capspe'] = $anagra['capspe'];
	$form['citspe'] = $anagra['citspe'];
	$form['prospe'] = $anagra['prospe'];
	$form['codfis'] = $anagra['codfis'];
  $form['datnas'] = gaz_format_date($anagra['datnas'],false,4);
	$form['user_password_new'] = '';
	$form['theme'] = $admin_config['var_value'];
  // attingo il testo delle email dalla tabella configurazione utente
	$az_mail = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'az_email', "AND adminid = '{$form['user_name']}' AND company_id = '".$admin_aziend['company_id']."'");
	$form['az_email'] = ($az_mail)?$az_mail['var_value']:'';
	// attingo il testo delle email dalla tabella configurazione utente
	$bodytext = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'body_send_doc_email', "AND adminid = '{$form['user_name']}'");
	$form['id_contract'] = $staff['id_contract'];
	$form['codice_campi'] = $staff['codice_campi'];
	$form['iban'] = $staff_clfoco['iban'];
	$form['body_text'] = ($bodytext)?$bodytext['var_value']:'';
  $form['hidden_req'] = '';
} else {
  $anagrafica = new Anagrafica();
  $rs_last_staff = gaz_dbi_dyn_query("id_staff", $gTables['staff'], 1,"id_staff DESC",0,1);
  $last_staff = gaz_dbi_fetch_array($rs_last_staff);
  $form['id_staff'] = $last_staff?$last_staff['id_staff']+1:1;
  $staff_clfoco = $anagrafica->queryPartners('*', "codice BETWEEN " . $admin_aziend['mas_staff'] . "000000 AND " . $admin_aziend['mas_staff'] . "999999", "codice DESC", 0, 1);
  if (isset($last_clfoco[0]['codice'])) {
      $form['codice'] = substr($staff_clfoco[0]['codice'], 3) + 1;
  } else {
      $form['codice'] = 1;
  }
  $form['id_clfoco']=$form['codice'];
	$form['user_lastname'] = '';
	$form['user_firstname'] = '';
	$form['sexper'] = '';
	$form['indspe'] = '';
	$form['capspe'] = '';
	$form['citspe'] = '';
	$form['prospe'] = '';
	$form['codfis'] = '';
  $form['datnas']='01/01/1980';
	$form['user_email'] = '';
	$form['id_anagra'] = 0;
	$form['telefo'] = '';
  $form['az_email'] = '';
	$form['image'] = '';
	$form['theme'] = '/library/theme/lte';
	$form['style'] = $admin_aziend['style'];
	$form['skin'] = $admin_aziend['skin'];
	$form['lang'] = $admin_aziend['lang'];
	$form['id_warehouse'] = 0;
	$form['Abilit'] = 5; // collaboratore (default)
	// propongo la stessa azienda attiva sull'utente amministratore
  $form['hidden_req'] = '';
  $form['company_id'] = $user_data['company_id'];
	$form['Access'] = 0;
	$form['user_name'] = '';
	$form['user_password_new'] = '';
	$form['user_active'] = 1;
  $rs_last_contract = gaz_dbi_dyn_query("id_contract", $gTables['staff'], 1,"id_contract DESC",0,1);
  $last_contract = gaz_dbi_fetch_array($rs_last_contract);
  $form['id_contract'] = $last_contract?$last_contract['id_contract']+1:1;
	$form['codice_campi'] = 0;
	$form['iban'] = '';
	$form['body_text'] = '';
	if (preg_match("/school/", $_SERVER['HTTP_REFERER'])) {
		// nel caso voglio inserire un nuovo insegnante propongo abilitazione a 9
		$form['Abilit'] = 9;
	};
}

if (isset($_POST['conferma'])) {
	$old_data = gaz_dbi_get_row($gTables['admin'], 'user_name', $form['user_name']);
	//controllo i campi
	if (empty($form['user_lastname']))
	$msg['err'][] = 'user_lastname';
	if (empty($form['user_name']))
	$msg['err'][] = 'user_name';
	if (!filter_var($form['user_email'], FILTER_VALIDATE_EMAIL) && !empty($form['user_email'])) {
		$msg['err'][] = 'email'; // non coincide, segnalo l'errore
	}
  if (!filter_var($form['az_email'], FILTER_VALIDATE_EMAIL) && !empty($form['az_email'])) {
		$msg['err'][] = 'email'; // errore email
	}
  if ($toDo == 'insert'){
		if (strlen($form['user_password_new']) < $global_config->getValue('psw_min_length'))
		$msg['err'][] = 'passlen';

	}
	if (preg_match("/[<> \/\"]+/i", $form['user_password_new'])) {
		$msg['err'][] = 'charpass';
	}
	if ($form['Abilit'] > $user_data['Abilit'])
	$msg['err'][] = 'upabilit';
	if (!empty($_FILES['userfile']['name'])) {
		if (!( $_FILES['userfile']['type'] == 'image/jpeg' || $_FILES['userfile']['type'] == 'image/pjpeg'))
		$msg['err'][] = 'filmim';
		// controllo che il file non sia pi&ugrave; grande di 64kb
		if ($_FILES['userfile']['size'] > 63999)
		$msg['err'][] = 'filsiz';
	}
	if ( $form['user_name'] == $user_data['user_name'] && $user_data['Abilit'] >= 8 ) { // gli utenti amministratori rimangono con il loro Abilit
    $form['Abilit'] = $user_data['Abilit'];
	}


	if (count($msg['err']) == 0) { // nessun errore
		// preparo la stringa dell'immagine
		if ($_FILES['userfile']['size'] > 0) { //se c'e' una nuova immagine nel buffer
			$form['image'] = file_get_contents($_FILES['userfile']['tmp_name']);
		} else {   // altrimenti riprendo la vecchia
			$form['image'] = $old_data?$old_data['image']:'';
		}

		$form['datacc'] = date("YmdHis");
		$tbt = trim($form['body_text']);
		if ($toDo == 'insert') {
			$form['company_id'] = $user_data['company_id'];
			$form['user_registration_datetime']= date('Y-m-d H:i:s');
			$form['user_active']=1;
			// faccio l'hash della password prima di scrivere sul db
			require_once('../../modules/root/config_login.php');
			$hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);
			$form['user_password_hash'] = password_hash($form['user_password_new'] , PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));
      // ripreparo la chiave per criptare la chiave contenuta in $_SESSION con la nuova password e metterla aes_key di gaz_admin
      $prepared_key = openssl_pbkdf2($form['user_password_new'].$form['user_name'], AES_KEY_SALT, 16, 1000, 'sha256');
      $form['aes_key'] = base64_encode(openssl_encrypt($_SESSION['aes_key'],'AES-128-CBC',$prepared_key,OPENSSL_RAW_DATA, AES_KEY_IV));
			$form['ragso1']=$form['user_lastname'];
			$form['ragso2']=$form['user_firstname'];
			$form['legrap_pf_nome']='';
			$form['legrap_pf_cognome']='';
			$form['email']=$form['user_email'];
			$form['id_anagra']=gaz_dbi_table_insert('anagra', $form);
      $form['datpas'] = date("YmdHis");
			gaz_dbi_table_insert('admin', $form);
			$form['adminid'] = $form['user_name'];
			$form['var_descri'] = 'Menu/header/footer personalizzabile';
			$form['var_name'] = 'theme';
			$form['var_value'] = $form['theme'];
			gaz_dbi_table_insert('admin_config', $form);
			if (!empty($tbt)) {
				$form['var_descri'] = 'Contenuto in HTML del testo del corpo delle email inviate dell\'utente';
				$form['var_name'] = 'body_send_doc_email';
				$form['var_value'] = $tbt;
				gaz_dbi_table_insert('admin_config', $form);
			}
      $form['datnas'] = gaz_format_date($form['datnas'],true);
      $anagrafica = new Anagrafica();
      $last = $anagrafica->queryPartners('*', "codice BETWEEN " . substr($admin_aziend['mas_staff'],0,3). "000001 AND " . substr($admin_aziend['mas_staff'],0,3) . "999999", "codice DESC", 0, 1);
      $form['codice'] = isset($last[0]) ? ($last[0]['codice'] + 1) : $admin_aziend['mas_staff'].'000001';
      $form['id_clfoco'] = $form['codice'];
      // lo inserisco  nella tabella operatori
      $form['start_date']=date('Y-m-d');
			gaz_dbi_table_insert('staff', $form);
      // ed anche come nuovo staff sul piano dei conti
 			$form['descri'] = $form['user_lastname']. ' '. $form['user_firstname'];
      gaz_dbi_table_insert('clfoco', $form);
      // infine utilizzo il tema lte con il menù ridotto
      gaz_dbi_query("INSERT INTO ".$gTables['admin_config']." ( `adminid`, `company_id`, `var_descri`, `var_name`, `var_value`) VALUES ( '".$form['user_name']."', 0, 'Attiva lo stile boxed', 'LTE_Fixed', 'false')");
      gaz_dbi_query("INSERT INTO ".$gTables['admin_config']." ( `adminid`, `company_id`, `var_descri`, `var_name`, `var_value`) VALUES ( '".$form['user_name']."', 0, 'Attiva lo stile boxed', 'LTE_Boxed', 'false')");
      gaz_dbi_query("INSERT INTO ".$gTables['admin_config']." ( `adminid`, `company_id`, `var_descri`, `var_name`, `var_value`) VALUES ( '".$form['user_name']."', 0, 'Attiva lo stile boxed', 'LTE_Collapsed', 'true')");
      gaz_dbi_query("INSERT INTO ".$gTables['admin_config']." ( `adminid`, `company_id`, `var_descri`, `var_name`, `var_value`) VALUES ( '".$form['user_name']."', 0, 'Attiva lo stile boxed', 'LTE_Onhover', 'false')");
      gaz_dbi_query("INSERT INTO ".$gTables['admin_config']." ( `adminid`, `company_id`, `var_descri`, `var_name`, `var_value`) VALUES ( '".$form['user_name']."', 0, 'Attiva lo stile boxed', 'LTE_SidebarOpen', 'false')");
		} elseif ($toDo == 'update') {
			gaz_dbi_table_update('admin', array('user_name', $form['user_name']), $form);
			// se esiste aggiorno anche il tema
			$admin_config_theme = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'theme', "AND adminid = '{$form['user_name']}'");
			if ($admin_config_theme) {
				gaz_dbi_put_query($gTables['admin_config'], "adminid = '" . $form["user_name"] . "' AND var_name ='theme'", 'var_value', $form['theme']);
			} else { // altrimenti lo inserisco
				$form['adminid'] = $form['user_name']; // user_name contiene quello dell'operatore e non quello daell'utente come in $admin_aziend['user_name']
				$form['var_descri'] = 'Menu/header/footer personalizzabile';
				$form['var_name'] = 'theme';
				$form['var_value'] = $form['theme'];
				gaz_dbi_table_insert('admin_config', $form);
			}
      // aggiorno o inserisco la email aziendale riferita all'utente
			$az_email = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'az_email', "AND adminid = '{$form['user_name']}' AND company_id = '".$admin_aziend['company_id']."'");
			if ($az_email) {
				gaz_dbi_put_query($gTables['admin_config'], "adminid = '" . $form["user_name"] . "' AND var_name ='az_email' AND company_id = '".$admin_aziend['company_id']."'", 'var_value', $form['az_email']);
			} else {  // non c'era lo inserisco
				$form['adminid'] = $form['user_name']; // user_name contiene quello dell'operatore e non quello daell'utente come in $admin_aziend['user_name']
				$form['var_descri'] = 'Mail aziendale dell\'utente';
				$form['var_name'] = 'az_email';
				$form['var_value'] = $form['az_email'];
        $form['company_id'] = $admin_aziend['company_id'];
				gaz_dbi_table_insert('admin_config', $form);
			}
			// aggiorno o inserisco il testo da inserire nelle email trasmesse dall'utente
			$bodytext = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'body_send_doc_email', "AND adminid = '{$form['user_name']}'");
			if ($bodytext) {
				gaz_dbi_put_query($gTables['admin_config'], "adminid = '" . $form['user_name'] . "' AND var_name ='body_send_doc_email'", 'var_value', $tbt);
			} else {  // non c'era lo inserisco
				$form['adminid'] = $form['user_name'];
				$form['var_descri'] = 'Contenuto in HTML del testo del corpo delle email inviate dell\'utente';
				$form['var_name'] = 'body_send_doc_email';
				$form['var_value'] = $tbt;
				gaz_dbi_table_insert('admin_config', $form);
			}
      $form['datnas'] = gaz_format_date($form['datnas'],true);
 			gaz_dbi_table_update('anagra',['id', $form['id_anagra']], $form);
      $form['adminid'] = $form['user_name']; // user_name contiene quello dell'operatore e non quello daell'utente come in $admin_aziend['user_name']
      gaz_dbi_table_update('staff',['id_staff', $form['id_staff']], $form);
 			$form['descri'] = $form['user_lastname']. ' '. $form['user_firstname'];
      $staff_clfoco = gaz_dbi_get_row($gTables['clfoco'],"id_anagra",$form['id_anagra']);
      gaz_dbi_table_update('clfoco',['codice', $staff_clfoco['codice']], $form);
		}
    // preparo l'update di custom_field che potrebbe contenere altri dati
    $legmodule = gaz_dbi_get_row($gTables['module'], 'name','hospital');
		updateAccessRights($form['user_name'], 1, 3, $user_data['company_id']);
		updateAccessRights($form['user_name'], $legmodule['id'], 3, $user_data['company_id']);
    $thisadmin_module = gaz_dbi_get_row($gTables['admin_module'], "moduleid",$legmodule['id']," AND adminid='{$form['user_name']}' AND company_id=" . $admin_aziend['company_id']);
    $custom_field=is_string($thisadmin_module['custom_field'])?json_decode($thisadmin_module['custom_field'],true):[];
    $custom_field['excluded_script']=$excluded_script[$form['Abilit']];
    $custom_field=json_encode($custom_field);
    $query="UPDATE ".$gTables['admin_module']." SET custom_field='".$custom_field."' WHERE moduleid=".$legmodule['id']." AND adminid='{$form['user_name']}' AND company_id=" . $admin_aziend['company_id'];
    gaz_dbi_query($query);
		header('Location: report_healtworkers.php');
		exit;
	}
}
require('../../library/include/header.php');
$script_transl = HeadMain(0,['appendgrid/AppendGrid','capslockstate/src/jquery.capslockstate','custom/autocomplete']);
?>
<script src='../../js/sha256/forge-sha256.min.js'></script>
<script>
$(function(){
  $("#datnas").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
  $('#user_password_new').keypress(function(e) {
    var s = String.fromCharCode( e.which );
    var pfield = $(this).position();
    if ((s.toUpperCase() === s && s.toLowerCase() !== s && !e.shiftKey) || (s.toUpperCase() !== s && s.toLowerCase() === s && e.shiftKey)){
      if ($(this).parent().find('#capsalert').length < 1) {
        $('#capsalert').remove();
        $(this).after('<b id="capsalert" onclick="$(this).remove();">Lettere maiuscole attivo, Caps-Lock on!</b>');
        $('#capsalert')
          .css('position', 'absolute')
          .css('top', (pfield.top + $(this).outerHeight() + 1) + 'px')
          .css('left', (pfield.left) + 'px')
          .css('border-radius', '5px')
          .css('padding', '5px')
          .css('cursor', 'pointer')
          .css('background-color', '#ffe599')
          .css('border', '1px solid #e6ab00');
        setTimeout(function(){
          $('#capsalert').remove();
        },'5000');
      }
    } else {
      $('#capsalert').remove();
    }
  });
});
</script>
<form method="post" enctype="multipart/form-data"
<?php
$student = false;
if (preg_match("/([a-z0-9]{1,9})[0-9]{4}$/", $table_prefix, $tp)) {
	$rs_student = gaz_dbi_dyn_query("*", $tp[1] . '_students', "student_name = '" . $user_data['user_name'] . "'");
	$student = gaz_dbi_fetch_array($rs_student);
}
if (!is_array($student)){
?>
onsubmit="document.getElementById('user_password_new').value=forge_sha256(document.getElementById('user_password_new').value);"
<?php
}
?>
 id="logform" autocomplete="off">
<input type="hidden" name="ritorno" value="<?php echo $_POST['ritorno']; ?>">
<input type="hidden" name="id_anagra" value="<?php echo $form['id_anagra']; ?>">
<input type="hidden" name="id_staff" value="<?php echo $form['id_staff']; ?>">
<input type="hidden" name="hidden_req" value="<?php if (isset($_POST['hidden_req'])){ echo $_POST['hidden_req']; } ?>">
<div class="col-xs-12"><div class="col-xs-2"></div><div class="text-center col-xs-7"><h3>
<?php
if ($toDo == 'insert') {
	echo $script_transl['ins_this'] ;
} else {
	echo $script_transl['upd_this'] . " '" . $form["user_name"] . "'";
	echo '<input type="hidden" value="' . $form["user_name"] . '" name="user_name" />';
}
echo '</h3></div><div class="col-xs-3"><input name="conferma" id="conferma" class="btn btn-warning" type="submit" value="'.ucfirst($script_transl[$toDo]).'"></div></div>';
$gForm = new configForm();
if (count($msg['err']) > 0) { // ho un errore
	$gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
  // svuoto le password
	$form['user_password_new'] = '';
}
echo '<input type="hidden" name="' . ucfirst($toDo) . '" value="">';
?>
<table class="Tmiddle table-striped">
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['user_lastname']; ?>* </td>
<td colspan="2" class="FacetDataTD"><input title="Cognome" type="text" name="user_lastname" value="<?php echo $form["user_lastname"] ?>" maxlength="30"  class="FacetInput col-xs-12"> </td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['user_firstname']; ?></td>
<td colspan="2" class="FacetDataTD"><input title="Nome" type="text" name="user_firstname" value="<?php echo $form["user_firstname"] ?>" maxlength="30"  class="FacetInput col-xs-12"> </td>
</tr>
<?php
if ($toDo == 'insert') {
?>
<tr>
<td class="FacetFieldCaptionTD"><b><?php echo $script_transl["user_name"]; ?></b></td>
<td class="FacetDataTD" colspan="2"><input title="user_name" type="text" autocomplete="off" readonly onfocus="this.removeAttribute('readonly');" name="user_name" value="<?php echo  $form["user_name"]; ?>" maxlength="20" class="FacetInput col-xs-12"></td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><b><?php echo $script_transl['user_password_new']; ?></b></td>
<td colspan="2" class="FacetDataTD"><input title="Prima password" type="password"  autocomplete="off" readonly onfocus="this.removeAttribute('readonly');" id="user_password_new" name="user_password_new" value="<?php echo $form["user_password_new"]; ?>" maxlength="40" class="FacetInput" id="cpass" /><div class="FacetDataTDred" id="cmsg"></div></td>
</tr>
<?php
} else {
echo '<tr><td class="FacetFieldCaptionTD"></td><td colspan="2" class="FacetDataTD text-right"><a href="../root/login_password_change.php?un='.$form["user_name"].'" class="btn btn-warning">'.$script_transl['change'].' password</a></td></tr>';
}
?>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['id_contract']; ?> </td>
<td colspan="2" class="FacetDataTD"><input class="FacetInput col-xs-12" type="text" value="<?php echo $form['id_contract']; ?>" name="id_contract" maxlength="9"/></td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['sexper']; ?></td>
<td colspan="2" class="FacetDataTD">
<?php
$gForm->variousSelect('sexper', $script_transl['sexper_value'], $form['sexper'], "col-sm-8", true, '', false, 'style="width: 100%;"');
?>
</td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['indspe']; ?></td>
<td colspan="2" class="FacetDataTD"><input title="<?php echo $script_transl['indspe']; ?>" type="text" name="indspe" value="<?php echo $form["indspe"] ?>" maxlength="60"  class="FacetInput col-xs-12"> </td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['capspe']; ?></td>
<td colspan="2" class="FacetDataTD"><input title="<?php echo $script_transl['capspe']; ?>" type="text" id="search_location-capspe" name="capspe" value="<?php echo $form["capspe"] ?>" maxlength="10"  class="FacetInput col-xs-12"> </td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['citspe']; ?></td>
<td colspan="2" class="FacetDataTD"><input title="<?php echo $script_transl['citspe']; ?>" type="text" id="search_location" name="citspe" value="<?php echo $form["citspe"] ?>" maxlength="60"  class="FacetInput col-xs-12"> </td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['prospe']; ?></td>
<td colspan="2" class="FacetDataTD"><input title="<?php echo $script_transl['prospe']; ?>" type="text" id="search_location-prospe" name="prospe" value="<?php echo $form["prospe"] ?>" maxlength="2"  class="FacetInput col-xs-12"> </td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['codfis']; ?></td>
<td colspan="2" class="FacetDataTD"><input title="<?php echo $script_transl['codfis']; ?>" type="text" name="codfis" value="<?php echo $form["codfis"] ?>" maxlength="16"  class="FacetInput col-xs-12"> </td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['datnas']; ?></td>
<td colspan="2" class="FacetDataTD"><input title="<?php echo $script_transl['datnas']; ?>" type="text" name="datnas" id="datnas" value="<?php echo $form["datnas"] ?>" maxlength="10"  class="FacetInput col-xs-12"> </td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['Abilit']; ?></td>
<td colspan="2" class="FacetDataTD"><?php
$gForm->variousSelect('Abilit', $script_transl['Abilit_value'], $form['Abilit'],"col-sm-12",true, '', false);
?>
</td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['codice_campi']; ?> </td>
<td colspan="2" class="FacetDataTD">
<?php
$gForm->selectFromDB('campi', 'codice_campi', 'codice', $form['codice_campi'], false, 1, ' - ', 'descri', '', 'col-xs-12', null);
?>
</td>
</tr>
<tr>
<td class="FacetFieldCaptionTD">Telefono</td>
<td colspan="2" class="FacetDataTD"><input title="Telefono" type="text" name="telefo" value="<?php echo $form["telefo"] ?>" class="FacetInput col-xs-12" maxlength="30"> </td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['user_email']; ?></td>
<td colspan="2" class="FacetDataTD"><input title="Mail" type="email" name="user_email" value="<?php echo $form["user_email"] ?>" class="FacetInput col-xs-12" maxlength="50"> </td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['az_email']; ?></td>
<td colspan="2" class="FacetDataTD"><input title="Mail" type="email" name="az_email" value="<?php echo $form["az_email"] ?>" class="FacetInput col-xs-12" maxlength="50"> </td>
</tr>
<tr>
<?php
print "<td class=\"FacetFieldCaptionTD\"><img src=\"../root/view.php?table=admin&value=" . $form["user_name"] . "&field=user_name\" width=\"100\"></td>";
print "<td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl['image'] . ":<br /><input name=\"userfile\" type=\"file\" class=\"FacetDataTD\"></td>";
?>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['user_active']; ?></td>
<td colspan="2" class="FacetDataTD">
<?php
$gForm->variousSelect('user_active', $script_transl['user_active_value'], $form['user_active'], "col-xs-12", true, '', false);
?>
<div class="FacetDataTDred" id="user_active"></div></td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['body_text']; ?></td>
<td colspan="2" class="FacetDataTD">
<textarea id="body_text" name="body_text" class="mceClass" style="width:100%;"><?php echo $form['body_text']; ?></textarea>
</td>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['iban']; ?> </td>
<td colspan="2" class="FacetDataTD"><input class="FacetInput col-xs-12" type="text" value="<?php echo $form['iban']; ?>" name="iban" id="iban" maxlength="27" /></td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['signimg']; ?></td>
<td colspan="2" class="FacetDataTD">
<?php
$signimg = gaz_dbi_get_row($gTables['files'],"adminid",$form["user_name"]," AND table_name_ref = 'worker_signimg'");
if ($signimg) {
  echo '<img class="img-circle dit-picture col-sm-6" src="worker_img.php?id='.$signimg['id_doc'].'" alt="Logo" style="max-height:60px; max-width:100%;" border="0" ></a>';
  echo '<div class="col-sm-6"> <button class="btn btn-xs btn-warning" type="image" data-toggle="collapse" href="#imgsignimg_dialog" title="Sostituisci la firma con un\'altra"> <i class="glyphicon glyphicon-retweet"> Sostituisci la firma</i></button></div>';
} else {
  echo ' <button class="btn btn-xs btn-warning" type="image" data-toggle="collapse" href="#imgsignimg_dialog" title="Inserisci la firma"> <i class="glyphicon glyphicon-upload"></i></button>';
}
?>
<div id="imgsignimg_dialog" class="collapse col-xs-12"><input style="margin-left:20%;" type="file" accept=".png" onchange="this.form.submit();" name="signimg" /></div>
</td>
</tr>
</table><br/>
<input type="hidden" name="company_id" value="<?php echo $admin_aziend['company_id']; ?>" />
<input type="hidden" name="Access" value="<?php echo $form['Access']; ?>" />
<input type="hidden" name="style" value="default.css" />
<input type="hidden" name="skin" value="default.css" />
<input type="hidden" name="lang" value="italian" />
<input type="hidden" name="theme" value="/library/theme/lte" />
<div class="FacetFooterTD text-center"><input name="conferma" id="conferma" class="btn btn-warning" type="submit" value="<?php echo ucfirst($script_transl[$toDo]); ?>"></div>
</form>
<?php
require("../../library/include/footer.php");
?>
