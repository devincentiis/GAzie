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
$aut = 9;
if (!isset($_POST['ritorno'])) {
	$_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}
$global_config = new Config;
$user_data = gaz_dbi_get_row($gTables['admin'], "user_name", $_SESSION["user_name"]);

$msg = array('err' => array(), 'war' => array());
if ((isset($_POST['Update'])) or ( isset($_GET['Update']))) {
	$toDo = 'update';
	$accessi = $_GET["user_name"];
	if (!isset($_GET["user_name"])) {
		header("Location: " . $_POST['ritorno']);
		exit;
	}
	if ($_SESSION["user_name"] == $_GET["user_name"] or $user_data['Abilit'] == 9) {
		$aut = 0;
	}
} elseif ((isset($_POST['Insert'])) or ( isset($_GET['Insert']))) {
	$toDo = 'insert';
	$accessi = "";
	$aut = 9;
} else {
	header("Location: " . $_POST['ritorno']);
	exit;
}

$admin_aziend = checkAdmin($aut);
if (isset($_POST['Return'])) {
	header("Location: " . $_POST['ritorno']);
	exit;
}

if ((isset($_POST['Insert'])) || (isset($_POST['Update']))) {   //se non e' il primo accesso
	$form["user_lastname"] = substr($_POST['user_lastname'], 0, 30);
	$form["user_firstname"] = substr($_POST['user_firstname'], 0, 30);
	$form['user_email'] = trim($_POST['user_email']);
  $form['az_email'] = trim($_POST['az_email']);
	$form["lang"] = substr($_POST['lang'], 0, 15);
	$form["id_warehouse"] = intval($_POST['id_warehouse']);
	$form["theme"] = filter_input(INPUT_POST,'theme');
	$form["style"] = substr($_POST['style'], 0, 30);
	$form["skin"] = substr($_POST['skin'], 0, 30);
	$form["Abilit"] = intval($_POST['Abilit']);
  $form['hidden_req'] = $_POST['hidden_req'];
	$form['company_id'] = intval($_POST['company_id']);
	$form['search']['company_id'] = isset($_POST['search'])?$_POST['search']['company_id']:'';
	$form["Access"] = intval($_POST['Access']);
	$form["user_name"] = preg_replace("/[^A-Za-z0-9]/", '',substr($_POST["user_name"], 0, 64));
	$form["user_password_new"] = $toDo=='insert'?substr($_POST['user_password_new'], 0, 65):'';
	$form["user_active"] = intval($_POST['user_active']);
	$form['body_text'] = filter_input(INPUT_POST, 'body_text');
  $form['imap_usr'] = filter_input(INPUT_POST,'imap_usr');
  $form['imap_pwr'] = $_POST['imap_pwr'];// andrà criptata al momento del salvataggio
  $form['imap_sent_folder'] = filter_input(INPUT_POST,'imap_sent_folder');
  $form['id_anagra'] = $_POST['id_anagra'];
	if ($toDo == 'insert') {
		$rs_utente = gaz_dbi_dyn_query("*", $gTables['admin'], "user_name = '" . $form["user_name"] . "'", "user_name DESC", 0, 1);
		$risultato = gaz_dbi_fetch_array($rs_utente);
		if ($risultato) {
			$msg['err'][] = 'exlogin';
		}
	}

} elseif ((!isset($_POST['Update'])) && (isset($_GET['Update']))) { // primo accesso per update
	$form = gaz_dbi_get_row($gTables['admin'], "user_name", preg_replace("/[^A-Za-z0-9]/", '',substr($_GET["user_name"], 0, 64)));
	if (!$form){
		header("Location: " . $_POST['ritorno']);
		exit;
	}
	// attingo il valore del motore di template dalla tabella configurazione utente
	$admin_config = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'theme', "AND adminid = '{$form['user_name']}'");

	$custom_field = gaz_dbi_get_row($gTables['anagra'], "id", $form['id_anagra'])['custom_field'];
  if ( isset($custom_field) && $custom_field!="" ) {
    $data = json_decode($custom_field,true);
    if (isset($data['config']) && is_array($data['config']) && isset($data['config'][$form['company_id']])){
      $form['imap_usr']=(isset($data['config'][$form['company_id']]['imap_usr']))?$data['config'][$form['company_id']]['imap_usr']:'';
      //$form['imap_pwr']=(isset($data['config'][$form['company_id']]['imap_pwr']))?$data['config'][$form['company_id']]['imap_pwr']:'';
      $form['imap_pwr']='';// non carico la password perché tanto non si può vedere.
      $form['imap_sent_folder']=(isset($data['config'][$form['company_id']]['imap_sent_folder']))?$data['config'][$form['company_id']]['imap_sent_folder']:'';
    }else{
      $form['imap_usr']='';
      $form['imap_pwr']='';
      $form['imap_sent_folder']='';
    }
  }else{
    $form['imap_usr']='';
    $form['imap_pwr']='';
    $form['imap_sent_folder']='';
  }

	// dal custom field di admin_module relativo al magazzino trovo il magazzino di default
	$magmodule = gaz_dbi_get_row($gTables['module'], "name",'magazz');
	$mod_customfield = gaz_dbi_get_row($gTables['admin_module'], "moduleid",$magmodule['id']," AND adminid='{$form['user_name']}' AND company_id=" . $admin_aziend['company_id']);
  $mod_customfield['custom_field'] = (!$mod_customfield ||$mod_customfield['custom_field'] === NULL) ? '' : $mod_customfield['custom_field'];
	$customfield=json_decode($mod_customfield['custom_field']);
	$form['id_warehouse'] = (isset($customfield->user_id_warehouse))?$customfield->user_id_warehouse:0;
	$form['user_password_new'] = '';
	$form['theme'] = $admin_config['var_value'];
  // attingo il testo delle email dalla tabella configurazione utente
	$az_mail = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'az_email', "AND adminid = '{$form['user_name']}' AND company_id = '".$admin_aziend['company_id']."'");
	$form['az_email'] = ($az_mail)?$az_mail['var_value']:'';
	// attingo il testo delle email dalla tabella configurazione utente
	$bodytext = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'body_send_doc_email', "AND adminid = '{$form['user_name']}'");
	$form['body_text'] = ($bodytext)?$bodytext['var_value']:'';
    $form['hidden_req'] = '';
    $form['search']['company_id'] = '';
} else { // primo accesso
	$form["user_lastname"] = "";
	$form["user_firstname"] = "";
	$form['user_email'] = '';
  $form['az_email'] = '';
	$form["image"] = "";
	$form["theme"] = "/library/theme/lte";
	$form["style"] = $admin_aziend['style'];
	$form["skin"] = $admin_aziend['skin'];
	$form["lang"] = $admin_aziend['lang'];
	$form["id_warehouse"]=0;
	$form["Abilit"] = 5;
	// propongo la stessa azienda attiva sull'utente amministratore
  $form['hidden_req'] = '';
  $form['company_id'] = $user_data['company_id'];
  $form['search']['company_id'] = '';
	$form["Access"] = 0;
	$form["user_name"] = "";
	$form["user_password_new"] = "";
	$form["user_active"] = 1;
	$form['body_text'] = "";
  $form['imap_usr']='';
  $form['imap_pwr']='';
  $form['imap_sent_folder']='';
  $form['id_anagra']='';

	if (preg_match("/school/", $_SERVER['HTTP_REFERER'])) {
		// nel caso voglio inserire un nuovo insegnante propongo abilitazione a 9
		$form["Abilit"] = 9;
	};
}

if (isset($_POST['conferma'])) {
	$old_data = gaz_dbi_get_row($gTables['admin'], "user_name", $form["user_name"]);
	//controllo i campi
	if (empty($form["user_lastname"]))
	$msg['err'][] = 'user_lastname';
	if (empty($form["user_name"]))
	$msg['err'][] = "user_name";
	if (!filter_var($form['user_email'], FILTER_VALIDATE_EMAIL) && !empty($form['user_email'])) {
		$msg['err'][] = 'email'; // non coincide, segnalo l'errore
	}
  if (!filter_var($form['az_email'], FILTER_VALIDATE_EMAIL) && !empty($form['az_email'])) {
		$msg['err'][] = 'email'; // errore email
	}
  if ($toDo == 'insert'){
		if (strlen($form["user_password_new"]) < $global_config->getValue('psw_min_length'))
		$msg['err'][] = 'passlen';

	}
	if (preg_match("/[<> \/\"]+/i", $form["user_password_new"])) {
		$msg['err'][] = 'charpass';
	}
	if ($form["Abilit"] > $user_data["Abilit"])
	$msg['err'][] = 'upabilit';
	if (!empty($_FILES['userfile']['name'])) {
		if (!( $_FILES['userfile']['type'] == "image/jpeg" || $_FILES['userfile']['type'] == "image/pjpeg"))
		$msg['err'][] = 'filmim';
		// controllo che il file non sia pi&ugrave; grande di 64kb
		if ($_FILES['userfile']['size'] > 63999)
		$msg['err'][] = 'filsiz';
	}
	if ($form["Abilit"] < 9) {
		$ricerca = trim($form["user_name"]);
		// impedisco agli utenti non amministratori di cambiarsi l'azienda di lavoro
		$form["company_id"] = ($old_data)?$old_data["company_id"]:0;
		$rs_utente = gaz_dbi_dyn_query("*", $gTables['admin'], "user_name <> '$ricerca' AND Abilit ='9'", "user_name", 0, 1);
		$risultato = gaz_dbi_fetch_array($rs_utente);
		$student = false;
		if (preg_match("/([a-z0-9]{1,9})[0-9]{4}$/", $table_prefix, $tp)) {
			$rs_student = gaz_dbi_dyn_query("*", $tp[1] . '_students', "student_name = '" . $ricerca . "'");
			$student = gaz_dbi_fetch_array($rs_student);
		}
		if (!$risultato && !$student) {
			$msg['err'][] = 'Abilit';
		} elseif ($form["Abilit"] < 8 && $student) {
			$msg['err'][] = 'Abilit_stud';
		}
	}
	if (count($msg['err']) == 0) { // nessun errore
		// preparo la stringa dell'immagine
		if ($_FILES['userfile']['size'] > 0) { //se c'e' una nuova immagine nel buffer
			$form['image'] = file_get_contents($_FILES['userfile']['tmp_name']);
		} else {   // altrimenti riprendo la vecchia
			$form['image'] = $old_data['image'];
		}
		// preparo l'update di custom_field che potrebbe contenere altri dati
		$magmodule = gaz_dbi_get_row($gTables['module'], "name",'magazz');
		$thisadmin_module = gaz_dbi_get_row($gTables['admin_module'], "moduleid",$magmodule['id']," AND adminid='{$form['user_name']}' AND company_id=" . $admin_aziend['company_id']);
		$thiscustom_field=(array)json_decode($thisadmin_module['custom_field']);
		$thiscustom_field['user_id_warehouse']=$form['id_warehouse'];
		$form['custom_field']=json_encode($thiscustom_field);
		// aggiorno il db
		$query="UPDATE ".$gTables['admin_module']." SET custom_field='".$form['custom_field']."' WHERE moduleid=".$magmodule['id']." AND adminid='{$form['user_name']}' AND company_id=" . $admin_aziend['company_id'];
		gaz_dbi_query($query);
		$form["datacc"] = date("YmdHis");
		$tbt = trim($form['body_text']);
		if ($user_data['Abilit'] == 9) {
			foreach ($_POST AS $key => $value) {
				if (preg_match("/^([0-9]{3})acc_/", $key, $id)) {
					updateAccessRights($form["user_name"], preg_replace("/^[0-9]{3}acc_/", '', $key), $value, $id[1]);
				} elseif (preg_match("/^([0-9]{3})nusr_/", $key, $id)) {
					updateAccessRights($form["user_name"], 1, 3, $user_data['company_id']);
					$mod_data = gaz_dbi_get_row($gTables['module'], 'name', preg_replace("/^[0-9]{3}nusr_/", '', $key));
					if (!empty($mod_data)) {
						updateAccessRights($form["user_name"], $mod_data['id'], $value, $id[1]);
					}
				} elseif (preg_match("/^([0-9]{3})new_/", $key, $id) && $value == 3) { // il nuovo modulo non è presente in gaz_module
				  $name = preg_replace("/^[0-9]{3}new_/", '', $key);
				  // controllo se il modulo è già stato attivato allora aggiungo solo l'utente
				  $mod_data = gaz_dbi_get_row($gTables['module'], 'name', $name);
				  // trovo l'ultimo peso assegnato ai moduli esistenti e lo accodo
				  $rs_last = gaz_dbi_dyn_query("MAX(weight)+1 AS max_we", $gTables['module'], 'id > 1');
				  $r = gaz_dbi_fetch_array($rs_last);
				  if($mod_data){ // il modulo è presente aggiungo solo l'utente in admin_module
					updateAccessRights($form["user_name"], $mod_data['id'], 3, $id[1]);
				  } else { // non c'è nulla aggiungo tutto e creo il menù
					require("../../modules/" . $name . "/menu.creation_data.php");
				  $modclass=(isset($module_class))?$module_class:'';
					$mod_id = gaz_dbi_table_insert('module', array('name' => $name, 'link' => $menu_data['m1']['link'], 'icon' => $name . '.png', 'class'=>$modclass, 'weight' => $r['max_we']));
					updateAccessRights($form["user_name"], $mod_id, 3, $id[1]);
					// trovo l'ultimo id del sub menu
					$rs_last = gaz_dbi_dyn_query("MAX(id)+1 AS max_id", $gTables['menu_module'], 1);
					$r = gaz_dbi_fetch_array($rs_last);
					$m2_id = $r['max_id'];
					foreach ($menu_data['m2'] as $k_m2 => $v_2) {
						gaz_dbi_table_insert('menu_module', array('id' => $m2_id, 'id_module' => $mod_id, 'link' => $v_2['link'], 'translate_key' => $k_m2, 'weight' => $v_2['weight']));
						if (isset($menu_data['m3']['m2'][$k_m2])) {
							foreach ($menu_data['m3']['m2'][$k_m2] as $v_3) {
								// trovo l'ultimo id del sub menu
								$rs_last = gaz_dbi_dyn_query("MAX(id)+1 AS max_id", $gTables['menu_script'], 1);
								$r = gaz_dbi_fetch_array($rs_last);
								gaz_dbi_table_insert('menu_script', array('id' => $r['max_id'], 'id_menu' => $m2_id, 'link' => $v_3['link'], 'translate_key' => $v_3['translate_key'], 'weight' => $v_3['weight']));
							}
						}
						$m2_id ++;
					}
					if (isset($update_db)&&is_array($update_db)){
						/*
						Se il nuovo modulo prevede un update della base dati allora eseguo (unatantum) le query in essa contenute;
						pertanto se si vuole modificare il database si deve valorizzare una variabile di nome "$update_db" del file
						menu.creatione_data.php  e mettere in essa tutte le query al database necessarie per il funzionamento del nuovo
						modulo
						*/
						global $table_prefix;
            $query = "SELECT codice FROM `".$table_prefix."_aziend`";
            $result = gaz_dbi_query ($query);
            $companies = array();
            while($r=gaz_dbi_fetch_array($result)){
              $companies[]=$r['codice'];
            }
						foreach ($update_db as $vq) {
              if (preg_match("/XXX/",$vq)) { // query ricorsive sulle tabelle di tutte le aziende
                foreach ($companies as $i) {
                  $sql = preg_replace("/XXX/", sprintf('%03d',$i), $vq);
                  if (!gaz_dbi_query($sql)) { //se non è stata eseguita l'istruzione lo segnalo
                    echo "Query Fallita";
                    echo "$sql <br/>";
                    exit;
                  }
                }
              } else { // query singola sulla tabella comune alle aziende
                if (!gaz_dbi_query($vq)) { //se non è stata eseguita l'istruzione lo segnalo
                  echo "Query Fallita";
                  echo "$sql <br/>";
                  exit;
                }
              }
						}
					}
				  }
				}
			}
		}

		if ($toDo == 'insert') {
			$form['company_id'] = $user_data['company_id'];
			$form['user_registration_datetime']= date('Y-m-d H:i:s');
			$form['user_active']=1;
			// faccio l'hash della password prima di scrivere sul db
			require_once('../../modules/root/config_login.php');
			$hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);
			$form["user_password_hash"] = password_hash($form["user_password_new"] , PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));
      // ripreparo la chiave per criptare la chiave contenuta in $_SESSION con la nuova password e metterla aes_key di gaz_admin
      $prepared_key = openssl_pbkdf2($form["user_password_new"].$form["user_name"], AES_KEY_SALT, 16, 1000, "sha256");
      $form["aes_key"] = base64_encode(openssl_encrypt($_SESSION['aes_key'],"AES-128-CBC",$prepared_key,OPENSSL_RAW_DATA, AES_KEY_IV));

			// Antonio Germani - Creo anche una nuova anagrafica nelle anagrafiche comuni
      if (strlen($form['imap_usr'])>2){// se è stato inserito l'utente nelle impostazioni imap creo i dati imap nel custom_field
        $data = json_decode($form['custom_field'],true);// aggiungo il custom field di config a quello di user_id_warehouse creato poco sopra

        /**** promemoria per decriptare ****
        list($encrypted_data, $iv) = explode('::', base64_decode($imap_pwr), 2);
        $imap_pwr=openssl_decrypt($encrypted_data, 'aes-128-cbc', $_SESSION['aes_key'], 0, $iv);
        ****/
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-128-cbc'));
        $cripted_pwr=base64_encode(openssl_encrypt($_POST['imap_pwr'], 'aes-128-cbc', $_SESSION['aes_key'], 0, $iv).'::'.$iv);

        $company_id[$form['company_id']]=array('imap_usr' => $_POST['imap_usr'],'imap_pwr' => $cripted_pwr,'imap_sent_folder' => $_POST['imap_sent_folder']);
        $data['config']= $company_id;
        $form['custom_field'] = json_encode($data);
      }
			$form['ragso1']=$form['user_lastname'];
			$form['ragso2']=$form['user_firstname'];
			$form['legrap_pf_nome']="";
			$form['legrap_pf_cognome']="";
			$form['email']=$form['user_email'];
			$form['id_anagra']=gaz_dbi_table_insert('anagra', $form);
      $form['datpas'] = date("YmdHis");
			gaz_dbi_table_insert('admin', $form);// salvo anagrafica

			$form['adminid'] = $form["user_name"];
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
			// qui aggiungo alla tabella breadcrumb/widget gli stessi che ha l'utente che abilita il nuovo, altrimenti sulla homepage non apparirebbe nulla
			$get_widgets = gaz_dbi_dyn_query("*", $gTables['breadcrumb'],"adminid='".$admin_aziend['user_name']."' AND exec_mode>=1", 'exec_mode,position_order');
			while($row=gaz_dbi_fetch_array($get_widgets)){
				$row['adminid']=$form["user_name"];
				gaz_dbi_table_insert('breadcrumb',$row);
			}

		} elseif ($toDo == 'update') {
      $custom_field=gaz_dbi_get_row($gTables['anagra'], "id", $form['id_anagra'])['custom_field']; // carico il json custom_field esistente
      if ($custom_field && $data = json_decode($custom_field,true)){// se c'è un json
        if (isset($data['config']) && is_array($data['config']) && isset($data['config'][$form['company_id']])){ // se c'è il modulo "config" e c'è l'azienda attuale aggiorno il custom field
          $data['config'][$form['company_id']]['imap_usr']=$form['imap_usr'];
          if (strlen($form['imap_pwr'])>4){// se è stata scritta una password la inserisco o modifico
            /**** promemoria per decriptare ****
            list($encrypted_data, $iv) = explode('::', base64_decode($imap_pwr), 2);
            $imap_pwr=openssl_decrypt($encrypted_data, 'aes-128-cbc', $_SESSION['aes_key'], 0, $iv);
            ****/
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-128-cbc'));
            $cripted_pwr=base64_encode(openssl_encrypt($form['imap_pwr'], 'aes-128-cbc', $_SESSION['aes_key'], 0, $iv).'::'.$iv);
            $data['config'][$form['company_id']]['imap_pwr']=$cripted_pwr;
          }
          $data['config'][$form['company_id']]['imap_sent_folder']=$form['imap_sent_folder'];
          $form['custom_field'] = json_encode($data);
        } else { //se non c'è il modulo "config" con l'attuale azienda lo aggiungo
          $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-128-cbc'));
          $cripted_pwr=base64_encode(openssl_encrypt($form['imap_pwr'], 'aes-128-cbc', $_SESSION['aes_key'], 0, $iv).'::'.$iv);
          $data['config'][$form['company_id']]= array('imap_usr' => $form['imap_usr'],'imap_pwr' => $cripted_pwr,'imap_sent_folder' => $form['imap_sent_folder']);
          $form['custom_field'] = json_encode($data);
        }
        gaz_dbi_put_row($gTables['anagra'], 'id', $form['id_anagra'], 'custom_field', $form['custom_field']);// aggiorno il DB
      }elseif (strlen($form['imap_usr'])>2 && strlen($form['imap_pwr'])>4){// se è stato inserito l'utente nelle impostazioni imap creo i dati imap nel custom_field
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-128-cbc'));
        $cripted_pwr=base64_encode(openssl_encrypt($form['imap_pwr'], 'aes-128-cbc', $_SESSION['aes_key'], 0, $iv).'::'.$iv);
        $data['config'][$form['company_id']]= array('imap_usr' => $form['imap_usr'],'imap_pwr' => $cripted_pwr,'imap_sent_folder' => $form['imap_sent_folder']);
        $form['custom_field'] = json_encode($data);
        gaz_dbi_put_row($gTables['anagra'], 'id', $form['id_anagra'], 'custom_field', $form['custom_field']);// aggiorno il DB
      }
			gaz_dbi_table_update('admin', array("user_name", $form["user_name"]), $form);
			// se esiste aggiorno anche il tema
			$admin_config_theme = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'theme', "AND adminid = '{$form['user_name']}'");
			if ($admin_config_theme) {
				gaz_dbi_put_query($gTables['admin_config'], "adminid = '" . $form["user_name"] . "' AND var_name ='theme'", 'var_value', $form['theme']);
			} else { // altrimenti lo inserisco
				$form['adminid'] = $form["user_name"];
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
				$form['adminid'] = $form["user_name"];
				$form['var_descri'] = 'Mail aziendale dell\'utente';
				$form['var_name'] = 'az_email';
				$form['var_value'] = $form['az_email'];
        $form['company_id'] = $admin_aziend['company_id'];
				gaz_dbi_table_insert('admin_config', $form);
			}
			// aggiorno o inserisco il testo da inserire nelle email trasmesse dall'utente
			$bodytext = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'body_send_doc_email', "AND adminid = '{$form['user_name']}'");
			if ($bodytext) {
				gaz_dbi_put_query($gTables['admin_config'], "adminid = '" . $form["user_name"] . "' AND var_name ='body_send_doc_email'", 'var_value', $tbt);
			} else {  // non c'era lo inserisco
				$form['adminid'] = $form["user_name"];
				$form['var_descri'] = 'Contenuto in HTML del testo del corpo delle email inviate dell\'utente';
				$form['var_name'] = 'body_send_doc_email';
				$form['var_value'] = $tbt;
				gaz_dbi_table_insert('admin_config', $form);
			}
			// vado ad aggiornare anche la tabella studenti dell'installazione di base qualora ce ne fosse uno
			if (@$student) {
				gaz_dbi_put_row($tp[1] . '_students', 'student_name', $form["user_name"], 'student_firstname', $form['user_firstname']);
				gaz_dbi_put_row($tp[1] . '_students', 'student_name', $form["user_name"], 'student_lastname', $form['user_lastname']);
			}
			if ($admin_config_theme['var_value']<>$form['theme']) {
				session_destroy();
				header("Location: ../root/login_user.php?tp=".$table_prefix);
				exit;
			}
		}
		header("Location: " . $_POST['ritorno']);
		exit;
	}
}
// e-mail TESTER
if (isset($_GET['e-test']) && $_GET['e-test']==TRUE){
  $custom_field = gaz_dbi_get_row($gTables['anagra'], 'id', $form['id_anagra'])['custom_field'];
  if ($data = json_decode($custom_field,true)){// se c'è un json
    if (isset($data['config'][$form['company_id']]) && is_array($data['config']) ){ // se c'è il modulo "config" e c'è l'azienda attuale posso procedere
      $az_email_admin_config = gaz_dbi_get_row($gTables['admin_config'], "adminid", $form['user_name'], "AND company_id = ".intval($form['company_id'])." AND var_name = 'az_email'");
      $form['user_email']=(isset($az_email_admin_config))?$az_email_admin_config['var_value']:'';
      if (!filter_var($form['user_email'], FILTER_VALIDATE_EMAIL)) {
        $data = ["error" =>  "Indirizzo e-mail aziendale utente non corretto o inesistente"];
        echo json_encode($data);exit;
      }
      list($encrypted_data, $iv) = explode('::', base64_decode($data['config'][$form['company_id']]['imap_pwr']), 2);
      $imap_pwr=openssl_decrypt($encrypted_data, 'aes-128-cbc', $_SESSION['aes_key'], 0, $iv);
      $imap_usr=$data['config'][$form['company_id']]['imap_usr'];
      $imap_sent_folder=$data['config'][$form['company_id']]['imap_sent_folder'];
    }else{
      $data = ["error" =>  "Non sono impostate le chiavi di accesso utente"];
      echo json_encode($data);exit;
    }
    $imap_server = gaz_dbi_get_row($gTables['company_config'], 'var', 'imap_server')['val'];
    $imap_port = gaz_dbi_get_row($gTables['company_config'], 'var', 'imap_port')['val'];
    $imap_secure = gaz_dbi_get_row($gTables['company_config'], 'var', 'imap_secure')['val'];
    if($imap = @imap_open("{".$imap_server.":".$imap_port."/".$imap_secure."}".$imap_sent_folder, $form['user_email'], $imap_pwr)){
      if ($append=@imap_append($imap, "{".$imap_server."}".$imap_sent_folder
                         , "From: ".$form['user_email']."\r\n"
                         . "To: ".$form['user_email']."\r\n"
                         . "Subject: TEST test\r\n"
                         . "\r\n"
                         . "This is a test message from GAzie, please ignore it!\r\n","\\seen"
                         )){
                          imap_close($imap);
                          $data = ["send" => "SUCCESS","sender" => $form['user_email']];
                          echo json_encode($data);exit;
                         }else{
                            $errors = @imap_errors();
                            $data = ["error" =>  $errors];
                            echo json_encode($data);exit;
                         }
    }else{
      $errors = @imap_errors();
      $data = ["error" =>  $errors];
      echo json_encode($data);exit;
    }
  }
}
require("../../library/include/header.php");
$script_transl = HeadMain(0,['appendgrid/AppendGrid','capslockstate/src/jquery.capslockstate']);
$imap_check = gaz_dbi_get_row($gTables['company_config'], 'var', 'imap_server');

?>
<script src='../../js/sha256/forge-sha256.min.js'></script>
<script>
$(function(){
	$("#dialog_module_card").dialog({ autoOpen: false });
	$('.dialog_module_card').click(function() {
		var mod = $(this).attr('module');
		var username = $(this).attr('adminid');
		var jsondatastr = null;
		var deleted_rows = [];
		$("p#iddescri").html('<img src="../'+mod+'/'+mod+'.png" height="32"> '+$(this).attr("transl_name")+'</b>');
		$.ajax({ // prendo tutti i files php del modulo filtrati di quelli che so non essere di interesse
			'async': false,
			url:"./search.php",
			type: "POST",
			dataType: 'text',
			data: { term: mod, opt: 'module', adminid: username },
			success:function(jsonstr) {
				//alert(jsonstr);
				jsondatastr = jsonstr;
			}
		});

		var myAppendGrid = new AppendGrid({ // creo la tabella vuota
		  element: "tblAppendGrid",
		  uiFramework: "bootstrap4",
		  iconFramework: "default",
		  initRows: 1,
		  columns: [
        {
          name: "script_name",
          display: "Script",
          type: "text",
          ctrlAttr: { 'readonly': 'readonly' },
          ctrlCss: {'font-size': '12px'}
        },
        {
          name: "chk_script",
          display: "Nega accesso",
          type: "checkbox",
          cellCss: {'text-align': 'center'},
          cellCss: {'width': '20px'}
        },
		  ],
      hideButtons: {
        // Remove all buttons at the end of rows
        insert: true,
        remove: true,
        moveUp: true,
        moveDown: true,
        append: true,
        removeLast: true
      },
      hideRowNumColumn: true
		});

		if (jsondatastr){
      // popolo la tabella
      var jsondata = $.parseJSON(jsondatastr);
      myAppendGrid.load( jsondata );
		}

		$( "#dialog_module_card" ).dialog({
			minHeight: 1,
			width: 370,
      position: { my: "top+100", at: "top+100", of: "div.container-fluid,div.wrapper div.content-wrapper",collision:" none" },
      modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'Annulla',
					'class':'btn btn-default',
					click:function (event, ui) {
						$(this).dialog("close");
					}
				},
				confirm :{
				  text:'CONFERMA',
				  'class':'btn btn-warning',
				  click:function() {
					var msg = null;
					$.ajax({ // registro con i nuovi dati il cartellino presenze
						'async': false,
						data: {del_script: myAppendGrid.getAllValue(), type: 'module', ref: mod, adminid: username },
						type: 'POST',
						url: './delete.php',
						success: function(output){
							msg = output;
							console.log(msg);
						}
					});
					if (msg) {
						alert(msg);
					} else {
						window.location.replace("./admin_utente.php?user_name=<?php echo $admin_aziend['user_name']; ?>&Update");
					}
				  }
				}
			}
		});
		$("#dialog_module_card" ).dialog( "open" );
	});
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
<form method="POST" enctype="multipart/form-data"
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
<input type="hidden" name="ritorno" value="<?php print $_POST['ritorno']; ?>">
<input type="hidden" name="hidden_req" value="<?php if (isset($_POST['hidden_req'])){ print $_POST['hidden_req']; } ?>">
<div class="panel panel-default gaz-table-form div-bordered">
  <div class="container-fluid">
    <div class="col-xs-12"><div class="col-xs-2"></div><div class="text-center col-xs-7"><h3>
    <?php
    if ($toDo == 'insert') {
      echo $script_transl['ins_this'] ;
    } else {
      echo $script_transl['upd_this'] . " '" . $form["user_name"] . "'";
      echo '<input type="hidden" value="' . $form["user_name"] . '" name="user_name" />';
    }
    echo '</h3></div><div class="col-xs-3 text-right"><input name="conferma" id="conferma" class="btn btn-warning" type="submit" value="'.ucfirst($script_transl[$toDo]).'"> </div>';
    ?>
    </div>
    <ul class="nav nav-pills">
      <li class="active"><a data-toggle="pill" href="#generale">Dati utente</a></li>
      <li><a data-toggle="pill" href="#imap">Impostazioni IMAP</a></li>
    </ul>

    <div class="tab-content">
      <?php
      $gForm = new configForm();
      if (count($msg['err']) > 0) { // ho un errore
        $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
        // svuoto le password
        $form['user_password_new'] = '';
      }
      echo '<input type="hidden" name="' . ucfirst($toDo) . '" value="">';
      ?>
      <input type="hidden" name="id_anagra" value="<?php echo $form['id_anagra']; ?>">
      <div id="generale" class="tab-pane fade in active">

        <table class="table-striped">
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['user_lastname']; ?>* </td>
          <td colspan="2" class="FacetDataTD"><input title="Cognome" type="text" name="user_lastname" value="<?php print $form["user_lastname"] ?>" maxlength="30"  class="FacetInput">&nbsp;</td>
          </tr>
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['user_firstname']; ?></td>
          <td colspan="2" class="FacetDataTD"><input title="Nome" type="text" name="user_firstname" value="<?php print $form["user_firstname"] ?>" maxlength="30"  class="FacetInput">&nbsp;</td>
          </tr>
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['user_email']; ?></td>
          <td colspan="2" class="FacetDataTD"><input title="Mail" type="email" name="user_email" value="<?php print $form["user_email"] ?>" class="FacetInput" maxlength="50">&nbsp;</td>
          </tr>

          <tr>
          <?php
          if ($toDo == 'insert') {
          ?>
          <tr><td class="FacetFieldCaptionTD"><?php echo $script_transl["user_name"]; ?></td>
          <td class="FacetDataTD" colspan="2"><input title="user_name" type="text" name="user_name" value="<?php echo  $form["user_name"]; ?>" maxlength="20" class="FacetInput"></td>
            </tr>
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['user_password_new']; ?> </td>
          <td colspan="2" class="FacetDataTD"><input title="Prima password" type="password" id="user_password_new" name="user_password_new" value="<?php echo $form["user_password_new"]; ?>" maxlength="40" class="FacetInput" id="cpass" /><div class="FacetDataTDred" id="cmsg"></div></td>
          </tr>
          <?php
          } else {
          echo '<tr><td class="FacetFieldCaptionTD"></td><td colspan="2" class="FacetDataTD text-right"><a href="../root/login_password_change.php?un='.$form["user_name"].'" class="btn btn-warning">'.$script_transl['change'].' password</a></td></tr>';
          }
          ?>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['az_email']; ?></td>
          <td colspan="2" class="FacetDataTD"><input title="Mail" type="email" name="az_email" value="<?php print $form["az_email"] ?>" class="FacetInput" maxlength="50">&nbsp;</td>
          </tr>
          <tr>
          <?php
          print "<td class=\"FacetFieldCaptionTD\"><img src=\"../root/view.php?table=admin&value=" . $form["user_name"] . "&field=user_name\" width=\"100\"></td>";
          print "<td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl['image'] . ":<br /><input name=\"userfile\" type=\"file\" class=\"FacetDataTD\"></td>";
          ?>
          </tr>
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['lang']; ?></td>
          <?php
          echo '<td colspan="2" class="FacetDataTD">';
          echo '<select name="lang" class="FacetSelect">';
          $relativePath = '../../language';
          if ($handle = opendir($relativePath)) {
            while ($file = readdir($handle)) {
              if (($file == ".") or ( $file == "..") or ( $file == ".svn"))
              continue;
              $selected = "";
              if ($form["lang"] == $file) {
                $selected = " selected ";
              }
              echo "<option value=\"" . $file . "\"" . $selected . ">" . ucfirst($file) . "</option>";
            }
            closedir($handle);
          }
          echo "</td></tr>\n";
          ?>
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['theme']; ?> </td>
          <td colspan="2" class="FacetDataTD">
          <?php
          $gForm->selThemeDir('theme', $form["theme"]);
          ?>
          </td>
          </tr>
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['style']; ?></td>
          <?php
          echo '<td colspan="2" class="FacetDataTD">';
          echo '<select name="style" class="FacetSelect">';
          $relativePath = '../..' . $_SESSION['theme'] . '/scheletons/';
          if ($handle = opendir($relativePath)) {
            while ($file = readdir($handle)) {
              // accetto solo i file css
              if (!preg_match("/^[a-z0-9\s\_\-]+\.css$/", $file)) {
                continue;
              }
              $selected = "";
              if ($form["style"] == $file) {
                $selected = " selected ";
              }
              echo "<option value=\"" . $file . "\"" . $selected . ">" . $file . "</option>";
            }
            closedir($handle);
          }
          echo "</td></tr>\n";
          ?>
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['skin']; ?></td>
          <?php
          echo '<td colspan="2" class="FacetDataTD">';
          echo '<select name="skin" class="FacetSelect">';
          $relativePath = '../..' . $_SESSION['theme'] . '/skins/';
          if ($handle = opendir($relativePath)) {
            while ($file = readdir($handle)) {
              // accetto solo i file css
              if (!preg_match("/^[a-z0-9\s\_\-]+\.css$/", $file)) {
                continue;
              }
              $selected = "";
              if ($form["skin"] == $file) {
                $selected = " selected ";
              }
              echo "<option value=\"" . $file . "\"" . $selected . ">" . $file . "</option>";
            }
            closedir($handle);
          }
          echo "</td></tr>\n";
          ?>
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['Abilit']; ?></td>
          <td colspan="2" class="FacetDataTD">
          <?php
              $gForm->variousSelect('Abilit', $script_transl['Abilit_value'], $form['Abilit'], "col-sm-8", true, '', false, 'style="max-width: 300px;"');
          ?>
          </tr>
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['mesg_co'][2]; ?></td>
          <td class="FacetDataTD" colspan="2">
          <?php


          if ($user_data['Abilit'] == 9 || is_array($student)) {
            $gForm->selectCompany('company_id', $form['company_id'], $form['search']['company_id'], $form['hidden_req'], $script_transl['mesg_co']);
          } else {
            $company = gaz_dbi_get_row($gTables['aziend'], 'codice', $form['company_id']);
            echo '<input type="hidden" name="company_id" value="'.$form['company_id'].'">';
            echo $company['ragso1'].' '.$company['ragso2'];
          }
          ?>
          </td>
          </tr>
          <tr>
          <td class="FacetFieldCaptionTD">Magazzino predefinito</td>
          <td class="FacetDataTD" colspan="2">
          <?php
            $gForm->selectFromDB('warehouse','id_warehouse','id',$form["id_warehouse"],'id',false,' - ','name','0','col-sm-6',['value'=>0,'descri'=>'Sede'],'');
          ?>
          </tr>
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['Access']; ?></td>
          <td colspan="2" class="FacetDataTD">
          <?php
          if ($user_data['Abilit'] == 9){
          ?>
          <input title="Accessi" type="text" name="Access" value="<?php echo $form["Access"]; ?>" maxlength="7" class="FacetInput">
          <?php
          } else {
            echo '<input type="hidden" name="Access" value="'.$form["Access"].'">'.$form["Access"];
          }
          ?>
          </td>
          </tr>
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['user_active']; ?></td>
          <td colspan="2" class="FacetDataTD">
          <?php
              $gForm->variousSelect('user_active', $script_transl['user_active_value'], $form['user_active'], "col-sm-8", true, '', false, 'style="max-width: 300px;"');
          ?>
          <div class="FacetDataTDred" id="user_active"></div>&nbsp;</td>
          </tr>
          <tr>
          <td class="FacetFieldCaptionTD"><?php echo $script_transl['body_text']; ?></td>
          <td colspan="2" class="FacetDataTD">
          <textarea id="body_text" name="body_text" class="mceClass" style="width:100%;"><?php echo $form['body_text']; ?></textarea>
          </td>
          </tr>

          <?php
          if ($user_data["Abilit"] == 9) {
            function getModule($login, $company_id) {
              global $gTables, $admin_aziend;
              //trovo i moduli installati
              $mod_found = [];
              $relativePath = '../../modules';
              if ($handle = opendir($relativePath)) {
                while ($exist_mod = readdir($handle)) {
                  if ($exist_mod == "." || $exist_mod == ".." || $exist_mod == ".svn" || $exist_mod == "root" || !file_exists("../../modules/$exist_mod/menu." . $admin_aziend['lang'] . ".php"))
                  continue;
                  $rs_mod = gaz_dbi_dyn_query("am.access,am.moduleid, am.custom_field, module.name ", $gTables['admin_module'] . ' AS am LEFT JOIN ' . $gTables['module'] .
                  ' AS module ON module.id=am.moduleid ', " am.adminid = '" . $login . "' AND module.name = '$exist_mod' AND am.company_id = '$company_id'", "am.adminid", 0, 1);
                  require("../../modules/$exist_mod/menu." . $admin_aziend['lang'] . ".php");
                  $row = gaz_dbi_fetch_array($rs_mod);
                  $row['excluded_script'] = [];
                  if (!isset($row['moduleid'])) {
                    $row['name'] = $exist_mod;
                    $row['moduleid'] = 0;
                    $row['access'] = 0;
                    $row['custom_field'] = '';
                  }
                  $chkes = is_string($row['custom_field'])?json_decode($row['custom_field']):false;
                  if ($chkes && isset($chkes->excluded_script)) {
                    $row['excluded_script'] = $chkes->excluded_script;
                  }
                  $row['transl_name'] = $transl[$exist_mod]['name'];
                  $mod_found[$exist_mod] = $row;
                }
                closedir($handle);
              }
              return $mod_found;
            }


              ?>

          <?php
           //richiamo tutte le aziende installate e vedo se l'utente  e' abilitato o no ad essa
            $table = $gTables['aziend'] . ' AS a';
            $what = "a.codice AS id, ragso1 AS ragsoc, (SELECT COUNT(*) FROM " . $gTables['admin_module'] . " WHERE a.codice=" . $gTables['admin_module'] . ".company_id AND " . $gTables['admin_module'] . ".adminid='" . $form["user_name"] . "') AS set_co ";
            $co_rs = gaz_dbi_dyn_query($what, $table, 1, "ragsoc ASC");
           while ($co = gaz_dbi_fetch_array($co_rs)) {
              $co_id = sprintf('%03d', $co['id']);
            echo '<tr></tr><tr><td colspan="4"><h3><img src="../../modules/root/view.php?table=aziend&value='.$co['id'].'" alt="Logo" height="30"> ' . $co['ragsoc'] . '  - ID:' . $co['id'] . '</h3></td></tr>';
            echo "<tr><td class=\"FacetDataTD\">" .'<input type=hidden name="' . $co_id . 'nusr_root" value="3"><b>'. $script_transl['mod_perm'] . ":</b></td>\n";
            echo "<td><b>" . $script_transl['all'] . "</b></td>\n";
            echo '<td align="center"><b> Script esclusi</b></td>';
            echo "<td><b>" . $script_transl['none'] . "</b></td></tr>\n";
            $mod_found = getModule($form["user_name"], $co['id']);
            $mod_admin = getModule($user_data["user_name"], $co['id']);
            foreach ($mod_found as $mod) {
              echo "<tr>\n";
              echo '<td>
                        <img height="16" src="../' . $mod['name'] . '/' . $mod['name'] . '.png" /> ' . $mod['transl_name'] . ' (' . $mod['name'] . ")</td>\n";
              if ($mod['moduleid'] == 0) { // il modulo non è stato mai attivato
                if ($form["user_name"] <> $user_data["user_name"]) { // sono un amministratore che sta operando sul profilo di altro utente
                  if ($mod_admin[$mod['name']]['access']==3){ // il modulo è attivo sull'amministratore
                      // per evitare conflitti nemmeno l'amministratore può attivare un modulo se questo non lo è ancora sul suo
                      echo "  <td colspan=2 ><input type=radio name=\"" . $co_id . "nusr_" . $mod['name'] . "\" value=\"3\"></td>";
                      echo "  <td><input type=radio checked name=\"" . $co_id . "nusr_" . $mod['name'] . "\" value=\"0\"></td>";
                  } else { // modulo non attivo sull'amministratore
                      echo '  <td colspan=2 >Non attivato</td>';
                      echo '  <td><input type="hidden"  name="' . $co_id . "nusr_" . $mod['name'] . '" value="0"></td>';
                  }
                } elseif ($co['set_co'] == 0) { // il modulo mai attivato
                  echo "  <td colspan=2><input type=radio name=\"" . $co_id . "nusr_" . $mod['name'] . "\" value=\"3\"></td>";
                  echo "  <td><input type=radio checked name=\"" . $co_id . "nusr_" . $mod['name'] . "\" value=\"0\"></td>";
                } else { // se l'amministratore che sta operando sul proprio profilo può attivare un nuovo modulo e creare il relativo menù
                  echo "  <td class=\"FacetDataTDred\" colspan=2><input class=\"btn btn-warning\" type=radio name=\"" . $co_id . "new_" . $mod['name'] . "\" value=\"3\">Modulo attivabile</td>";
                  echo "  <td class=\"FacetDataTDred\"><input type=radio checked name=\"" . $co_id . "new_" . $mod['name'] . "\" value=\"0\"></td>";
                }
              } elseif ($mod['access'] == 0) { // il modulo è attivato, quindi propongo i valori precedenti
                echo "  <td colspan=2><input type=radio name=\"" . $co_id . "acc_" . $mod['moduleid'] . "\" value=\"3\"></td>";
                echo "  <td><input type=radio checked name=\"" . $co_id . "acc_" . $mod['moduleid'] . "\" value=\"0\"></td>";
              } else {
                echo '<td><input type=radio checked name="'. $co_id . 'acc_' . $mod['moduleid'] . '" value="3"> </td><td><a class="btn btn-xs dialog_module_card" module="'.$mod['name'].'" adminid="'.$form['user_name'].'" transl_name="'.$mod['transl_name'].'"><i class="glyphicon glyphicon-edit"></i>'.((count($mod['excluded_script'])>=1)?'<p class="text-left">'.implode('.php</p><p class="text-left">',$mod['excluded_script']).'.php</p>':'nessuno</p>').'</a></td>';
                echo "  <td><input type=radio name=\"" . $co_id . "acc_" . $mod['moduleid'] . "\" value=\"0\"></td>";
              }
              echo "</tr>\n";
            }
          }
          ?>
          </table>
      </div> <!-- chiude generale -->

      <div id="imap" class="tab-pane fade">
          <?php if ($imap_check){ ?>
          <table class="table-striped">
            <tr>
              <td colspan="3" class="FacetFieldCaptionTD"><b>Inserire le credenziali di accesso IMAP attiva la possibilità di avere le e-mail inviate da GAzie nella cartella di posta inviata specificata. Questo sistema sostituirà l'invio per conoscenza al proprio indirizzo</b></td>
            </tr>
            <tr>
              <td colspan="1" class="FacetFieldCaptionTD">IMAP user name</td>
              <td colspan="2" class="FacetDataTD"><input title="Nome utente IMAP (Lasciare vuoto se non serve)" type="text" name="imap_usr" value="<?php echo $form['imap_usr'] ?>" maxlength="40"  class="FacetInput">&nbsp;</td>
            </tr>
            <tr>
              <td class="FacetFieldCaptionTD">IMAP password</td>
              <td colspan="2" class="FacetDataTD"><input title="Password IMAP (lasciare vuoto se non serve)" type="password" name="imap_pwr" placeholder="Invisibile, digita solo se vuoi inserirla o cambiarla (minimo 4 caratteri)" value="<?php echo $form["imap_pwr"] ?>" maxlength="40"  class="FacetInput">&nbsp;</td>
            </tr>
            <tr>
              <td class="FacetFieldCaptionTD">IMAP percorso cartella utente della posta inviata</td>
              <td colspan="2" class="FacetDataTD"><input title="Password IMAP (lasciare vuoto se non serve)" type="text" name="imap_sent_folder" value="<?php echo $form["imap_sent_folder"] ?>" maxlength="40"  class="FacetInput">&nbsp;</td>
            </tr>
          </table>
          <div id="email" class="tab-pane">
            <div>Il test di configurazione ti permette di verificare le impostazioni IMAP inserite. <br><b>Salva</b> la configurazione prima di avviare il test.
            </div>
            <div id="wait">
              <span>Please wait...</span>
            </div>
            <div id="btn_send" class="btn btn-default">TEST CONFIGURAZIONE IMAP</div>
            <div id="reply_send"></div>
          </div><!-- chiude email  -->

          <script>
          $( "#wait" ).hide();
          $("#btn_send").click( function() {
            $.ajax({
              url: "admin_utente.php?user_name=<?php echo $form["user_name"] ?>&Update&e-test=true",
              type: "GET",
              data: { 'e-test': true },
               beforeSend: function () {
                // ... your initialization code here (so show loader) ...
                $( "#wait" ).show();
              },
              complete: function () {
                // ... your finalization code here (hide loader) ...
                $( "#wait" ).hide();
              },
              success: function(json) {
                result = JSON.parse(json);
                if (  result.send == 'SUCCESS') {
                    $("#reply_send").html( "<strong>Invio riuscito</strong><br><div>Controlla se c'è una email test nella cartella posta inviata che hai impostato in <i>"+result.sender+"</i></div>");
                } else {
                  $("#reply_send").html("<strong>Invio FALLITO!</strong><br><div>Errore: "+result.error+"!</div>");
                }
              },
              error: function(richiesta,stato,errori){
                  $("#reply_send").html("<strong>Invio FALLITO!</strong><br><div>"+errori+"</div>");
              }
            })
          });
          </script>
          <?php } else{ ?>
          <table class="table-striped" style="width:100%;">
            <tr>
            <td class="FacetFieldCaptionTD">Prima di inserire le credenziali di accesso IMAP dell'utente bisogna impostare il server IMAP in configurazione azienda, tab 'Avanzata'</td>
            </tr>
            <tr>
            <td class="FacetDataTD text-right"><a href="../config/admin_aziend.php" class="btn btn-warning"> Configura </a></td>
            </tr>
          </table>
          <?php
          }
          ?>
      </div><!-- chiude pill imap -->
    </div> <!-- chiude tab-content -->
  </div> <!-- chiude container-fluid -->
</div> <!-- chiude panel -->
            <?php

}

?>
</table><br/>
<div class="FacetFooterTD text-center"><input name="conferma" id="conferma" class="btn btn-warning" type="submit" value="<?php echo ucfirst($script_transl[$toDo]); ?>"></div>
</form>
<?php
if ($admin_aziend['Abilit']==9){
	?>
	<div style="display:none; padding-bottom: 30px;" id="dialog_module_card" title="Disabilitazione script">
    <p><b>Modulo:</b></p>
		<p class="ui-state-highlight" id="iddescri"></p>
		<table id="tblAppendGrid"></table>
	</div>
	<div style="padding-top: 30px; padding-bottom: 3000px;">
    <div class="col-sm-12 col-md-1"></div><div class="col-sm-12 col-md-11"><b>Gli amministratore possono </b> <a data-toggle="collapse" class="btn btn-sm btn-warning" href="#gconfig" aria-expanded="false" aria-controls="gconfig"> accedere ai dati globali ↕ </a></div>
    <div class="collapse" id="gconfig">
      <iframe src="../../modules/root/set_config_data.php?iframe=TRUE" title="Configurazione globale" width="100%" height="1330"  frameBorder="0"></iframe>
    </div>
	</div>
	<?php
}
?>
<?php
require("../../library/include/footer.php");
?>
