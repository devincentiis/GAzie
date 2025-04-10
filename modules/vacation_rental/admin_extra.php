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
require("../../library/include/datlib.inc.php");
require("../../modules/magazz/lib.function.php");
require("../../modules/acquis/lib.data.php");

 function serchCOD()
   {
	global $gTables;
	$max_codice = gaz_dbi_query("select codice from ".$gTables['artico']." order by CAST(codice AS DECIMAL) desc limit 1");
    $max_cod = gaz_dbi_fetch_array($max_codice);
    return ++$max_cod[0];
   }
 function Barcode($EAN)
   {
    $dispari = substr($EAN,1,1)+substr($EAN,3,1)+substr($EAN,5,1)+substr($EAN,7,1)+substr($EAN,9,1)+substr($EAN,11,1);
    $dispari = $dispari * 3;
    $pari = substr($EAN,0,1)+substr($EAN,2,1)+substr($EAN,4,1)+substr($EAN,6,1)+substr($EAN,8,1)+substr($EAN,10,1);
    $totale = $pari + $dispari;
    while ($totale > 10) $totale = $totale - 10;
    return (substr($EAN,0,12).(10 - $totale));
   }

  function serchEAN()
   {
	global $gTables;
    $max_barcode = gaz_dbi_query("select max(barcode) from ".$gTables['artico']." where barcode like '3333333%';");
    $max_barcode = gaz_dbi_fetch_array($max_barcode);
	if ($max_barcode[0] == null) $max_barcode[0] ='3333333000000';
    return Barcode($max_barcode[0]+10);
   }

$admin_aziend = checkAdmin();
$msg = array('err' => array(), 'war' => array());
$modal_ok_insert = false;
$today=	strtotime(date("Y-m-d H:i:s",time()));
$presente="";
$largeimg=0;

/* Inizializzo per aprire in finestra modale */
$modal = false;
if (isset($_POST['mode']) || isset($_GET['mode'])) {
    $modal = true;
    if (isset($_GET['ok_insert'])) {
        $modal_ok_insert = true;
    }
}

if (isset($_POST['Update']) || isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (isset($_POST['icalsub']) && strlen($_POST['ical'])>3) {
	$ical['url']=$_POST['ical'];
	$ical['ical_descri']=$_POST['ical_descri'];
	$ical['codice_alloggio']=$_POST['codice'];
	$ical_sync_id = gaz_dbi_table_insert('rental_ical', $ical); // inserisco l'Ical nel DB
	// sincronizzo tutti gli eventi in esso contenuti
	$events = iCalDecoder($ical['url']);
	$columns = array('ical_sync_id','id','title', 'start','end','house_code');
	foreach ($events as $event){
		$newValue = array('ical_sync_id' => $ical_sync_id, 'title'=>substr($ical['ical_descri'].$event['uid'],0,128), 'start'=>substr($event['start'],0,10), 'end'=>substr($event['end'],0,10),'house_code'=>substr($ical['codice_alloggio'],0,32));
		tableInsert('rental_events', $columns, $newValue);
	}
}

// carico i dati per la select warehouse del jquery
$query = 'SELECT id, name FROM `' . $gTables['warehouse'] . '` ORDER BY `id`';
$result = gaz_dbi_query($query);
$warehouses="0:'Sede'";
$invalid_characters = array("'", ",", ":");
while ($r = gaz_dbi_fetch_array($result)) {// carico i dati di staff_work_type
	$warehouses .= ", ".$r['id'].":'". substr(str_replace($invalid_characters, " ", $r['name']), 0, 25)."'";
}

// carico i dati per la select shelves del jquery
$query = 'SELECT id_shelf,descri FROM `' . $gTables['shelves'] . '` WHERE 1 ORDER BY `id_shelf`';
$result = gaz_dbi_query($query);
$shelf="0:'Nessun scaffale associato'";
$invalid_characters = array("'", ",", ":");
while ($r = gaz_dbi_fetch_array($result)) {
	$shelf .= ", ".$r['id_shelf'].":'".substr(str_replace($invalid_characters, " ", $r['descri']), 0, 25)."'";
}

if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
  $form = gaz_dbi_parse_post('artico');
  $query = "SELECT * FROM " . $gTables['rental_ical'] . " WHERE codice_alloggio = '".substr(trim($form['codice']),0,32)."' ORDER BY id ASC";
  $resical = gaz_dbi_query($query);
  $form['codice'] = trim($form['codice']);
  $form['ritorno'] = $_POST['ritorno'];
  $form['hidden_req'] = $_POST['hidden_req'];
  $form['web_public_init'] = $_POST['web_public_init'];
  $form['var_id'] = (isset($_POST['var_id']))?$_POST['var_id']:'';
  $form['var_name'] = (isset($_POST['var_name']))?$_POST['var_name']:'';
  $form['ref_code'] = substr($_POST['ref_code'], 0,32);
  $form['rif_facility'] = $_POST['rif_facility'];
  if (isset($_POST['facility']) && intval($_POST['facility'])>0){// se è stato richiesto di aggiungere una struttura
    if (strlen($form['rif_facility'])>0){
      $comma=",";
    }else{
      $comma="";
    }
    $form['rif_facility']=$form['rif_facility'].$comma.$_POST['facility'];// la aggiungo alla stringa
  }
  if (isset($_POST['del_rif_fac'])){// se è stato richiesto di cancellare una struttura a riferimento
    $form['rif_facility']=explode(',',$form['rif_facility']);
    unset ($form['rif_facility'][$_POST['del_rif_fac']]);
    $form['rif_facility']=implode(',',$form['rif_facility']);
  }
  $form['facility'] = '';
  // i prezzi devono essere arrotondati come richiesti dalle impostazioni aziendali
  $form["preacq"] = number_format($form['preacq'], $admin_aziend['decimal_price'], '.', '');
  $form["preve1"] = number_format($form['preve1'], $admin_aziend['decimal_price'], '.', '');
  $form["preve2"] = number_format($form['preve2'], $admin_aziend['decimal_price'], '.', '');
  $form["preve3"] = number_format($form['preve3'], $admin_aziend['decimal_price'], '.', '');
  $form["preve4"] = number_format($form['preve4'], $admin_aziend['decimal_price'], '.', '');
  $form["web_price"] = number_format($form['web_price'], 3, '.', '');
  $form['rows'] = array();
  $form['accommodation_type'] = $_POST['accommodation_type'];
  $form['adult'] = $_POST['adult'];
  $form['child'] = $_POST['child'];
  $form['total_guests'] = $_POST['total_guests'];
  $form['max_quantity'] = $_POST['max_quantity'];
  $form['mod_prezzo'] = $_POST['mod_prezzo'];
  $form['rif_alloggio'] = $_POST['rifalloggio'];
  $form['obligatory'] = $_POST['obligatory'];
  $form['id_anagra'] = filter_input(INPUT_POST, 'id_anagra');
  if (isset ($_POST['search'])){
	  foreach ($_POST['search'] as $k => $v) {
		  $form['search'][$k] = $v;
	  }
  }

  // inizio documenti/certificati
  $ndoc = 0;
  if (isset($_POST['rows'])) {
    foreach ($_POST['rows'] as $ndoc => $value) {
      $form['rows'][$ndoc]['id_doc'] = intval($value['id_doc']);
      $form['rows'][$ndoc]['extension'] = substr($value['extension'], 0, 5);
      $form['rows'][$ndoc]['title'] = substr($value['title'], 0, 255);
      $ndoc++;
    }
  }
  // fine documenti/certificati

	// Antonio Germani - inizio immagini e-commerce
  $nimg = 0;
  if (isset($_POST['imgrows']) && isset($_POST['rows'])) {
    foreach ($_POST['rows'] as $nimg => $value) {
      $form['imgrows'][$nimg]['id_doc'] = intval($value['id_doc']);
      $form['imgrows'][$nimg]['extension'] = substr($value['extension'], 0, 5);
      $form['imgrows'][$nimg]['title'] = substr($value['title'], 0, 255);
      $nimg++;
    }
  }
  // fine inizio immagini e-commerce

  $form['body_text'] = filter_input(INPUT_POST, 'body_text');

  /* Controllo se il submit viene da una modale */
  if (isset($_POST['Submit']) || ($modal === true && isset($_POST['mode-act']))) { // ***  CONFERMA TUTTO ***

    if ($toDo == 'update') {  // controlli in caso di modifica
        if ($form['codice'] != $form['ref_code']) { // se sto modificando il codice originario
            // controllo che l'articolo ci sia gia'
            $rs_articolo = gaz_dbi_dyn_query('codice', $gTables['artico'], "codice = '" . $form['codice'] . "'", "codice DESC", 0, 1);
            $rs = gaz_dbi_fetch_array($rs_articolo);
            if ($rs) {
                $msg['err'][] = 'codice';
            }
            // controllo che il precedente non abbia movimenti di magazzino associati
            $rs_articolo = gaz_dbi_dyn_query('artico', $gTables['movmag'], "artico = '" . $form['ref_code'] . "'", "artico DESC", 0, 1);
            $rs = gaz_dbi_fetch_array($rs_articolo);
            if ($rs) {
                $msg['err'][] = 'movmag';
            }
        }
    } else {
        // controllo che l'articolo ci sia gia'
        $rs_articolo = gaz_dbi_dyn_query('codice', $gTables['artico'], "codice = '" . $form['codice'] . "'", "codice DESC", 0, 1);
        $rs = gaz_dbi_fetch_array($rs_articolo);
        if ($rs) {
            $msg['err'][] = 'codice';
        }
    }
    if (!empty($_FILES['userfile']['name'])) {
      if (!( $_FILES['userfile']['type'] == "image/png" ||
              $_FILES['userfile']['type'] == "image/x-png" ||
              $_FILES['userfile']['type'] == "image/jpeg" ||
              $_FILES['userfile']['type'] == "image/jpg" ||
              $_FILES['userfile']['type'] == "image/gif" ||
              $_FILES['userfile']['type'] == "image/x-gif")) $msg['err'][] = 'filmim';
				// controllo che il file non sia piu' grande di circa 64kb
      if ($_FILES['userfile']['size'] > 65530){
				//Antonio Germani anziche segnalare errore ridimensiono l'immagine
				$maxDim = 190;
				$file_name = $_FILES['userfile']['tmp_name'];
				list($width, $height, $type, $attr) = getimagesize( $file_name );
				if ( $width > $maxDim || $height > $maxDim ) {
					$target_filename = $file_name;
					$ratio = $width/$height;
					if( $ratio > 1) {
						$new_width = $maxDim;
						$new_height = intval($maxDim/$ratio);
					} else {
						$new_width = $maxDim*$ratio;
						$new_height = $maxDim;
					}
					$src = imagecreatefromstring( file_get_contents( $file_name ) );
					$dst = imagecreatetruecolor( $new_width, $new_height );
					imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
					imagedestroy( $src );
					imagepng( $dst, $target_filename); // adjust format as needed
					imagedestroy( $dst );
				}
			// fine ridimensionamento immagine
			$largeimg=1;
			}
    }
    if (empty($form["codice"])) {
        $msg['err'][] = 'valcod';
    }
    if (empty($form["descri"])) {
        $msg['err'][] = 'descri';
    }
		/*OBBLIGO AD UNA SOLA UNITA' DI MISURA*/
    if (empty($form["unimis"])&&empty($form["uniacq"])) {
        $msg['err'][] = 'unimis';
    }elseif(empty($form["unimis"])){
		$form["unimis"]=$form["uniacq"];
	}elseif(empty($form["uniacq"])){
		$form["uniacq"]=$form["unimis"];
	}
  if (empty($form["aliiva"])) {
      $msg['err'][] = 'aliiva';
  }
  // per poter avere la tracciabilità è necessario attivare la contabità di magazzino in configurazione azienda
  if ($form["lot_or_serial"] > 0 && $admin_aziend['conmag'] <= 1) {
      $msg['err'][] = 'lotmag';
  }
	// controllo che non ci siano caratteri speciali sul codice articolo (danno problemi con l'inventario)
	$pattern = '/[\'\/~`\!@#\$%\^&\*\(\) \+=\{\}\[\]\|;:"\<\>,\.\?\\\]/';
		if (preg_match($pattern, $form["codice"],$match)) {
		$form["codice"] = str_replace($match,'_',$form["codice"]);
      $msg['err'][] = 'char';
  }
	$codart_len = gaz_dbi_get_row($gTables['company_config'], 'var', 'codart_len')['val'];
  if ($codart_len > 0 && strlen(trim($form['codice'])) <> $codart_len) {
      $msg['err'][] = 'codart_len';
  }
  if ($form['web_public']>0 && intval($form['ref_ecommerce_id_product'])==0 && $toDo=="update"){// in update, senza id riferimento all'e-commerce non si può attivare
    $msg['err'][] = 'no_web';
  }

  if (count($msg['err']) == 0) { // ***  NESSUN ERRORE  ***

    if (!empty($_FILES['userfile']) && $_FILES['userfile']['size'] > 0) { //se c'e' una nuova immagine nel buffer
			if ($largeimg==0){
				$form['image'] = file_get_contents($_FILES['userfile']['tmp_name']);
			} else {
				$form['image'] = file_get_contents($target_filename);
			}
    } elseif ($toDo == 'update') { // altrimenti riprendo la vecchia ma solo se è una modifica
      $oldimage = gaz_dbi_get_row($gTables['artico'], 'codice', $form['ref_code']);
      $form['image'] = $oldimage['image'];
    } else {
      $form['image'] = '';
    }
    $form['clfoco'] = $form['id_anagra'];
    $tbt = trim($form['body_text']);

    // aggiorno il db
    // Una sola variante può essere prestabilita
    // legenda web_public: 1=attivo su web; 2=attivo e prestabilito; 3=attivo e pubblicato in home; 4=attivo, in home e prestabilito; 5=disattivato su web"
    if ($form['web_public_init']<>$form['web_public'] AND $form['id_artico_group']>0 AND $form['web_public']>1){ // se è una variante, ed è stata modificata la pubblicazione su e-commerce, e gli si vuole dare una priorità
      // prendo tutte le varianti esistenti di questo gruppo
      $var_row = gaz_dbi_dyn_query("*", $gTables['artico'], "id_artico_group = '" . $form['id_artico_group'] . "'");
      while ($row = gaz_dbi_fetch_array($var_row)) { // le ciclo
        // devo togliere l'eventuale prestabilito delle altre varianti
        if ($row['codice'] <> $form['codice'] AND $row['web_public']>0 AND $row['web_public']<5){ // se non è la variante in questione, cioè quella oggetto del form e non è disattivata
          $where = array("0" => "codice", "1" => $row['codice']);
          $what = array("web_public" => "1");
          gaz_dbi_table_update("artico",$where, $what); // riporto web_public a 1
        }
      }
    }
//echo "<pre>",print_r($form);die;
    // se esiste un json per l'attributo della variante dell'e-commerce creo il json
    if (isset ($form['var_id']) && isset ($form['var_name'])){
      $arrayvar= array("var_id" => intval($form['var_id']), "var_name" => strval($form['var_name']));
      $form['ecomm_option_attribute'] = json_encode ($arrayvar);
    }
    $form['preve1']=$form['web_price'];// al momento, imposto il prezzo di vendita 1 uguale a quello web
    if ($toDo == 'insert') {
      $form['codart']=substr($form['codice'],0,32);// il riferimento al codice articolo per la tabella rental_extra NB:il codice della tab artico ha 32 caratteri
      $id_extra=gaz_dbi_table_insert('rental_extra', $form);
      $array= array('vacation_rental'=>array('extra' => $id_extra));// creo l'array per il custom field
      $form['custom_field'] = json_encode($array);// codifico in json  e lo inserisco nel form
      gaz_dbi_table_insert('artico', $form);
      if (!empty($tbt)) {
        bodytextInsert(array('table_name_ref' => 'artico_' . $form['codice'], 'body_text' => $form['body_text'], 'lang_id' => $admin_aziend['id_language']));
      }
    } elseif ($toDo == 'update') {
      $form['codart']=substr($form['codice'],0,32);// il riferimento al codice articolo per la tabella rental_extra NB:il codice della tab artico ha 32 caratteri
      $custom_field=gaz_dbi_get_row($gTables['artico'], "codice", $form['ref_code'])['custom_field']; // carico il vecchio json custom_field
      $data = json_decode($custom_field,true);// trasformo il json custom_field in array
      $where = array();
      $where[]="id";
      $where[]=$data['vacation_rental']['extra'];
      gaz_dbi_table_update('rental_extra', $where, $form);// aggiorno rental extra
      gaz_dbi_table_update('artico', $form['ref_code'], $form);// aggiorno l'artico extra
      $bodytext = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", 'artico_' . $form['codice']);
      if (empty($tbt) && $bodytext) {
        // è vuoto il nuovo ma non lo era prima, allora lo cancello
        gaz_dbi_del_row($gTables['body_text'], 'id_body', $bodytext['id_body']);
      } elseif (!empty($tbt) && $bodytext) {
        // c'è e c'era quindi faccio l'update
        bodytextUpdate(array('id_body', $bodytext['id_body']), array('table_name_ref' => 'artico_' . $form['codice'], 'body_text' => $form['body_text'], 'lang_id' => $admin_aziend['id_language']));
      } elseif (!empty($tbt)) {
        // non c'era lo inserisco
        bodytextInsert(array('table_name_ref' => 'artico_' . $form['codice'], 'body_text' => $form['body_text'], 'lang_id' => $admin_aziend['id_language']));
      }
    }
    if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname'])){
          // aggiorno l'e-commerce ove presente
          $gs=$admin_aziend['synccommerce_classname'];
          $gSync = new $gs();
      if($gSync->api_token){
        $form['heximage']=bin2hex($form['image']);
        if($admin_aziend['conmag'] <= 1){ // se non gestisco la contabilità di magazzino ci indico solo la scorta e metto sempre disponibile
          $form['quantity']=intval($form['scorta']);
        } else {
          $gForm = new magazzForm();
          $mv = $gForm->getStockValue(false, $form['codice']);
          $magval = array_pop($mv);
          $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
          $form['quantity']=intval($magval['q_g']);
        }
        $gSync->UpsertProduct($form,$toDo);
        //print $gSync->rawres;
        //exit;
      }
    }

    /* Niente redirect se sono in finestra modale */
    if ($modal === false) {
			if ($toDo == 'insert') {
        // riprendo il codice e se non è stato realmente inserito sul db lo segnalo all'utente e non reindirizzo
        $catch = gaz_dbi_get_row($gTables['artico'], 'codice', $form['codice']);
        if ($catch){
          $_SESSION['ok_ins']=$form['codice'].' - '.$form['descri'];
          header("Location: ../../modules/vacation_rental/admin_extra.php?Update&codice=".$form['codice']);
          exit;
        } else {
          $msg['err'][] = 'no_ins';
        }
			}else{
				header("Location: ../../modules/vacation_rental/report_extra.php");
        exit;
			}
    } else {
			header("Location: ../../modules/vacation_rental/admin_extra.php?mode=modal&ok_insert=1");
      exit;
    }
  }

} elseif (isset($_POST['Return']) && $modal === false) { // torno indietro
      /* Solo se non sono in finestra modale */
      header("Location: " . $form['ritorno']);
      exit;
  }
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
  $form1 = gaz_dbi_get_row($gTables['artico'], 'codice', substr($_GET['codice'],0,32));
	$data = json_decode($form1['custom_field'],true);// trasformo il json custom_field in array
	$form2 = gaz_dbi_get_row($gTables['rental_extra'], 'id', substr($data['vacation_rental']['extra'],0,32));
	$form= array_merge($form1, $form2);
  $form['facility']='';
  unset ($form1);unset ($form2);
	$query = "SELECT * FROM " . $gTables['rental_ical'] . " WHERE codice_alloggio = '".substr($_GET['codice'],0,32)."' ORDER BY id ASC";
	$resical = gaz_dbi_query($query);
    /** ENRICO FEDELE */
    if ($modal === false) {
        $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    } else {
        $form['ritorno'] = 'admin_artico.php';
    }
	$form['hidden_req'] = '';
	$form['web_public_init']=$form['web_public'];
	$form['accommodation_type'] = "";
	$form['adult'] = 0;
	$form['child'] = 0;
	$form['total_guests'] = 0;
	if (json_decode($form['ecomm_option_attribute']) != null){ // se esiste un json per attributo della variante dell'e-commerce
		$opt_att=json_decode($form['ecomm_option_attribute']);
		if (isset ($opt_att -> var_id) OR isset ($opt_att -> var_name)){
		$form['var_id'] = $opt_att -> var_id;
		$form['var_name'] = $opt_att -> var_name;
		} else {
			$form['var_id'] = 0;
			$form['var_name'] = "";
		}
	}
    $form['ref_code'] = $form['codice'];// tengo il codice articolo originario nel caso venisse modificato
    // i prezzi devono essere arrotondati come richiesti dalle impostazioni aziendali
    $form["preacq"] = number_format($form['preacq'], $admin_aziend['decimal_price'], '.', '');
    $form["preve1"] = number_format($form['preve1'], $admin_aziend['decimal_price'], '.', '');
    $form["preve2"] = number_format($form['preve2'], $admin_aziend['decimal_price'], '.', '');
    $form["preve3"] = number_format($form['preve3'], $admin_aziend['decimal_price'], '.', '');
    $form["preve4"] = number_format($form['preve4'], $admin_aziend['decimal_price'], '.', '');
    $form["web_price"] = number_format($form['web_price'], $admin_aziend['decimal_price'], '.', '');
    $form['rows'] = array();
    $form['id_anagra'] = $form['clfoco'];
    $form['search']['id_anagra'] = '';

    // inizio documenti/certificati
    $ndoc = 0;
    $rs_row = gaz_dbi_dyn_query("*", $gTables['files'], "item_ref = '" . $form['codice'] . "' AND id_ref = '0'", "id_doc DESC");
    while ($row = gaz_dbi_fetch_array($rs_row)) {
        $form['rows'][$ndoc] = $row;
        $ndoc++;
    }
    // fine documenti/certificati

    // Antonio Germani - inizio immagini e-commerce
    $nimg = 0;
    $rs_row = gaz_dbi_dyn_query("*", $gTables['files'], "item_ref = '" . $form['codice'] . "' AND id_ref = '1'", "id_doc DESC");
    while ($row = gaz_dbi_fetch_array($rs_row)) {
        $form['imgrows'][$nimg] = $row;
        $nimg++;
    }
    // fine immagini e-commerce
    $bodytext = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", 'artico_' . $form['codice']);
    $form['body_text'] = ($bodytext)?$bodytext['body_text']:'';
} else { //se e' il primo accesso per INSERT
	$autoincrement_id_ecomm = gaz_dbi_get_row($gTables['company_config'], 'var', 'autoincrement_id_ecomm')['val'];// acquisisco impostazione per autoincremento ID ref ecommerce
    $form = gaz_dbi_fields('artico');
    /** ENRICO FEDELE */
    if ($modal === false) {
        $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    } else {
        $form['ritorno'] = 'admin_artico.php';
    }
    $form['hidden_req'] = '';
    $form['accommodation_type'] = '';
    $form['mod_prezzo'] = '';
    $form['rif_alloggio'] = '';
    $form['rif_facility'] = '';
    $form['facility']='';
    $form['adult'] = 1;
    $form['child'] = 0;
    $form['total_guests'] = 0;
    $form['max_quantity'] = 0;
    $form['web_public_init'] = 0;
    $form['web_multiplier'] = 1;
    $form['obligatory'] =0;
    $form['ref_code'] = '';
    $form['aliiva'] = $admin_aziend['preeminent_vat'];
    // i prezzi devono essere arrotondati come richiesti dalle impostazioni aziendali
    $form["preacq"] = number_format($form['preacq'], $admin_aziend['decimal_price'], '.', '');
    $form["preve1"] = number_format($form['preve1'], $admin_aziend['decimal_price'], '.', '');
    $form["preve2"] = number_format($form['preve2'], $admin_aziend['decimal_price'], '.', '');
    $form["preve3"] = number_format($form['preve3'], $admin_aziend['decimal_price'], '.', '');
    $form["preve4"] = number_format($form['preve4'], $admin_aziend['decimal_price'], '.', '');
    $form["web_price"] = number_format($form['web_price'], $admin_aziend['decimal_price'], '.', '');
    $form['web_public'] = 0;
    $form['depli_public'] = 1;
    $form['id_anagra'] = '';
    $form['search']['id_anagra'] = '';
    $form['body_text'] = '';
    // propongo il primo ID libero per l'ecommerce
    if ($autoincrement_id_ecomm==1){// se è stato impostato in configurazione avanzata azienda
      $max_ref_ecommerce_id_product = gaz_dbi_query("select ref_ecommerce_id_product from ".$gTables['artico']." ORDER BY ref_ecommerce_id_product DESC LIMIT 1");
      $max_id = gaz_dbi_fetch_array($max_ref_ecommerce_id_product);
      $form['ref_ecommerce_id_product'] = ++$max_id[0];
    } else {// altrimenti lascio non impostato
      $form['ref_ecommerce_id_product']="";
    }
    // ripropongo le ultime unità di misura più utilizzate
    $rs_unimis = gaz_dbi_query("SELECT unimis, COUNT(unimis) c FROM ".$gTables['artico']." GROUP BY unimis ORDER BY c DESC LIMIT 1");
    $unimis = gaz_dbi_fetch_array($rs_unimis);
    $form['unimis'] = $unimis['unimis'];
    $rs_uniacq = gaz_dbi_query("SELECT uniacq, COUNT(uniacq) c FROM ".$gTables['artico']." GROUP BY uniacq ORDER BY c DESC LIMIT 1");
    $uniacq = gaz_dbi_fetch_array($rs_uniacq);
    $form['uniacq'] = $uniacq['uniacq'];
}

/* Solo se non sono in finestra modale carico il file di lingua del modulo */
if ($modal === false) {
    require("../../library/include/header.php");
    $script_transl = HeadMain(0, array('custom/autocomplete','appendgrid/AppendGrid'));
    // trovo la posizione nel magazzino (se presente)
    $query = 'SELECT * FROM `' . $gTables['artico_position'] . '` ap
          LEFT JOIN `' . $gTables['warehouse'] . '` wh ON ap.id_warehouse=wh.id
          LEFT JOIN `' . $gTables['shelves'] . "` sh ON ap.id_shelf=sh.id_shelf WHERE `codart` = '".$form['codice']."' ORDER BY `ap`.`id_warehouse`,`ap`.`id_shelf`,`position`";
    $rs_pos = gaz_dbi_query($query);
    $accpos='';
    if ($rs_pos->num_rows > 0){
      while ($r = gaz_dbi_fetch_array($rs_pos)) {
        $accpos .= '<p>'.(empty($r['name'])?'Sede':$r['name']).' -> '.(empty($r['descri'])?'nessun scaffale':$r['descri']).' -> '.$r['position'].'</p>';
      }
    }
} else {
    $script = basename($_SERVER['PHP_SELF']);
    require("../../language/" . $admin_aziend['lang'] . "/menu.inc.php");
    require("../../modules/magazz/lang." . $admin_aziend['lang'] . ".php");
    if (isset($script)) { // se è stato tradotto lo script lo ritorno al chiamante
        $script_transl = $strScript[$script];
    }
    $script_transl = $strCommon + $script_transl;
}
?>
<script>
function calcDiscount() {
    var p1 = ($("#preve1").val() * (1 - $("#sconto").val() / 100)).toFixed(<?php echo $admin_aziend['decimal_price']; ?>);
    $("#preve1_sc").val(p1);
    var p2 = ($("#preve2").val() * (1 - $("#sconto").val() / 100)).toFixed(<?php echo $admin_aziend['decimal_price']; ?>);
    $("#preve2_sc").val(p2);
    var p3 = ($("#preve3").val() * (1 - $("#sconto").val() / 100)).toFixed(<?php echo $admin_aziend['decimal_price']; ?>);
    $("#preve3_sc").val(p3);
    var p4 = ($("#preve4").val() * (1 - $("#sconto").val() / 100)).toFixed(<?php echo $admin_aziend['decimal_price']; ?>);
    $("#preve4_sc").val(p4);
}

$(function () {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("nome"));
		var id = $(this).attr('ref');

		$( "#dialog_delete" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'Elimina',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'ical',ref:id},
						type: 'POST',
						url: '../vacation_rental/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./admin_house.php?Update&codice=<?php echo $form['codice'];?>+&tab=chifis");
						}
					});
				}},
				"Non eliminare": function() {
					$(this).dialog("close");
				}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});

  $("#preve1,#preve2,#preve3,#preve4,#sconto").change(function () {
      var v = $(this).val().replace(/,/, '.');
      $(this).val(v);
      calcDiscount();
  });

  $("[href='#"+$('#tabpill').val()+"']").click();

	var wpx = $(window).width()*0.97;
	$("#dialog_artico_position").dialog({ autoOpen: false });
	$('.dialog_artico_position').click(function() {
		var codart = $(this).attr('codart');
		var jsondatastr = null;
		var deleted_rows = [];
		$("p#iddescri").html(codart+' '+$(this).attr("artico_name"));
		$.ajax({ // chiedo tutte le posizioni attribuite
			'async': false,
			url:"./get_artico_positions.php",
			type: "POST",
			dataType: 'text',
			data: {codart: codart},
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
			  name: "codart",
			  display: "Codice Articolo",
			  type: "hidden"
			},
			{
			  name: "id_position",
			  display: "ID Posizione",
			  type: "hidden"
			},
			{
			  name: "id_warehouse",
			  display: "Magazzino",
			  type: "select",
				ctrlOptions: {
				<?php echo $warehouses; ?>
				},
			},
			{
			  name: "id_shelf",
			  display: "Scaffale",
				type: "select",
				ctrlOptions: {
				<?php echo $shelf;?>
				}
			},
			{
			  name: "position",
			  display: "Posizione",
			  type: "text"
			},
		  ],
		  beforeRowRemove: function(caller, rowIndex) {
			 var rowValues = myAppendGrid.getRowValue(rowIndex);
			 deleted_rows.push(rowValues.id_position);
			//alert("row index:" + rowIndex + " values:" + JSON.stringify(deleted_rows));
			return confirm("Sei sicuro di voler rimuovere la riga?");
			}
		});

		if (jsondatastr){
		// popolo la tabella
		var jsondata = $.parseJSON(jsondatastr);
		myAppendGrid.load( jsondata );
		}

		$( "#dialog_artico_position" ).dialog({
			minHeight: 1,
			width: wpx,
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'Annulla',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
						$(this).dialog("close");
					}
				},
				confirm :{
				  text:'CONFERMA',
				  'class':'btn btn-success pull-right btn-conferma',
				  click:function() {
					var msg = null;
					$.ajax({ // registro con i nuovi dati delle posizioni
						'async': false,
						data: {rec_artico_positions: myAppendGrid.getAllValue(), codart: codart, deleted_rows: deleted_rows},
						type: 'POST',
						url: './rec_artico_positions.php',
						success: function(output){
							msg = output;
							console.log(msg);
						}
					});
					if (msg) {
						alert(msg);
					} else {
						window.location.replace("./admin_extra.php?Update&codice="+codart+"&tab=magazz");
					}
				  }
				}
			}
		});
		$("#dialog_artico_position" ).dialog( "open" );
	});
});
</script>
<style>
  .collapsible { cursor:pointer; }
  #tblAppendGrid .form-control { height: 28px; }
  .ui-dialog .ui-dialog-buttonpane .ui-dialog-buttonset { float: unset !important; }
  .ui-dialog { z-index: 1000 !important; font-size: 12px;}
  .btn-conferma {	color: #fff !important; background-color: #f0ad4e !important; border-color: #eea236 !important; }
</style>

<form method="POST" name="form" enctype="multipart/form-data" id="add-product">
<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>Attenzione: l'eliminazione di un Icalendar comporta anche l'eliminazione di tutti gli eventi ad esso connessi e già memorizzati in GAzie.</b></p>
        <p>Numero ID Icalendar:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione:</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
<?php
if (!empty($form['descri'])) $form['descri'] = htmlentities($form['descri'], ENT_QUOTES);
if ($modal === true) {
    echo '<input type="hidden" name="mode" value="modal" />
          <input type="hidden" name="mode-act" value="submit" />';
} elseif (isset($_GET['tab'])) {
  echo '<input type="hidden" id="tabpill" value="' . substr($_GET['tab'],0,10) . '" />';
} elseif ($form['hidden_req']=='change' || $form['hidden_req']=='id_anagra'){
  echo '<input type="hidden" id="tabpill" value="magazz" />';
}
echo '<input type="hidden" name="ritorno" value="' . $form['ritorno'] . '" />';
echo '<input type="hidden" name="hidden_req" value="' . $form['hidden_req'] . '" />';
echo '<input type="hidden" name="ref_code" value="' . $form['ref_code'] . '" />';

if ($modal_ok_insert === true) {
    echo '<div class="alert alert-success" role="alert">' . $script_transl['modal_ok_insert'] . '</div>';
    echo '<div class=" text-center"><button class="btn btn-lg btn-default" type="submit" name="none">' . $script_transl['iterate_invitation'] . '</button></div>';
} else {
    $gForm = new magazzForm();
    $mv = $gForm->getStockValue(false, $form['codice']);
    $magval = array_pop($mv);
    $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
    /** ENRICO FEDELE */
    /* Se sono in finestra modale, non visualizzo questo titolo */
    $changesubmit = '';
    if ($modal === false) {
		/* disattivo la personalizzazione del form perché va in conflitto con altro
        ?>
    		<!--+ DC - 06/02/2019 -->
    		<script type="text/javascript" src="../../library/IER/IERincludeExcludeRows.js"></script>

    		<input type="hidden" id="IERincludeExcludeRowsInput" name="IERincludeExcludeRowsInput" />

        <div id="IERenableIncludeExcludeRows" title="Personalizza videata" onclick="enableIncludeExcludeRows()"></div>
  			<a target="_blank" href="../wiki/099 - Interfaccia generale/99.. Personalizzare una form a run-time (lato utente).md"><div id="IERhelpIncludeExcludeRows" title="Aiuto"></div></a>
  			<div id="IERsaveIncludeExcludeRows" title="Nessuna modifica fatta" onclick="saveIncludeExcludeRows()"></div>
      	<div id="IERresetIncludeExcludeRows" title="Ripristina"></div>
        <!--- DC - 06/02/2019 -->
    		<?php
		*/
    }
    echo '<input type="hidden" name="' . ucfirst($toDo) . '" value="" />';
	echo '<input type="hidden" name="web_public_init" value="'.$form['web_public_init'].'" />';
	echo '<input type="hidden" name="id_artico_group" value="'.$form['id_artico_group'].'" />';
    if (count($msg['err']) > 0) { // ho un errore
        $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
    }
	if (isset($_SESSION['ok_ins'])){
        $gForm->toast('L\'articolo ' . $_SESSION['ok_ins'].' è stato inserito con successo, sotto per modificarlo. Oppure puoi: <a class="btn btn-info" href="admin_artico.php?Insert">Inserire uno nuovo articolo</a> ' , 'alert-last-row', 'alert-success');
		unset($_SESSION['ok_ins']);
	}
  if ($toDo == 'insert') {
      echo '<div class="text-center"><h3>' . $script_transl['ins_this'] . '</h3></div>';
  } else {
      echo '<div class="text-center"><h3>' . $script_transl['upd_this'] . ' ' . $form['codice'] . '</h3></div>';
  }
  ?>
  <div class="panel panel-default gaz-table-form div-bordered">
      <div class="container-fluid">
      <ul class="nav nav-pills">
          <li class="active"><a data-toggle="pill" href="#home">Dati principali</a></li>
          <li><a data-toggle="pill" href="#magazz">E-commerce</a></li>
          <li><a data-toggle="pill" href="#contab">Contabilità</a></li>
          <li><a data-toggle="pill" href="#chifis">Caratteristiche</a></li>
          <li style="float: right;"><?php echo '<input name="Submit" type="submit" class="btn btn-warning" value="' . ucfirst($script_transl[$toDo]) . '" />'; ?></li>
      </ul>
      <div class="tab-content">
        <div id="home" class="tab-pane fade in active">
           <div class="row">
              <div class="col-md-12">
                  <div class="form-group">
                      <label for="codice" class="col-sm-4 control-label"><?php echo $script_transl['codice']; ?></label>
                      <input class="col-sm-4" type="text" value="<?php echo $form["codice"] ?>" name="codice"  maxlength="32" tabindex="1" >
                  </div>
              </div>
          </div><!-- chiude row  -->

	<script>
<!-- Antonio Germani inizio script autocompletamento dalla tabella mysql artico per gli alloggi	-->
$(document).ready(function(){
//Autocomplete search using PHP, MySQLi, Ajax and jQuery
//generate suggestion on keyup
	$('#rifalloggio').keyup(function(e){
		e.preventDefault();
		var form = $('#add-product').serialize();
		$.ajax({
			type: 'GET',
			url: 'do_search.php',
			data: form,
			dataType: 'json',
			success: function(response){
				if(response.error){
					$('#product_search').hide();
				}
				else{
					$('#product_search').show().html(response.data);
				}
			}
		});
	});
	//fill the input
	$(document).on('click', '.dropdown-item', function(e){
		e.preventDefault();
		$('#product_search').hide();
		var fullname = $(this).data('fullname');
		$('#rifalloggio').val(fullname);
		$('#add-product').submit();
	});
});
<!-- fine autocompletamento -->
</script>
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="alloggio" class="col-sm-4 control-label">Riferimento alloggio</label>
							<input class="col-sm8" type="text" id="rifalloggio" name="rifalloggio" value="<?php echo $form['rif_alloggio']; ?>" placeholder="Ricerca codice alloggio" autocomplete="off" tabindex="1">
							<ul class="dropdown-menu" style="left: 20%; padding: 0px;" id="product_search"></ul>Sarà esclusivo per l'alloggio selezionato; lasciare vuoto per tutti gli alloggi
						</div>
					</div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="struttura" class="col-sm-4 control-label">Riferimento esclusivo alle strutture:</label>
                    <input type="hidden" name="rif_facility" value="<?php echo $form['rif_facility']; ?>" />
                  <?php
                  if (strlen($form['rif_facility'])>0){
                    $form['rif_facility']=explode(',',$form['rif_facility']);// trasformo gli eventuali riferimenti a strutture in array
                    $n=0;
                    foreach($form['rif_facility'] AS $facil){
                      ?>
                      <label>
                      <?php echo "ID struttura: ", $facil;?>
                      </label>
                      <button class="btn btn-xs btn-default" type="submit" value="<?php echo $n;?>" name="del_rif_fac" title="Togli questa struttura ID<?php echo $facil;?>">
                           <span class="glyphicon glyphicon-trash"></span>
                      </button>
                      <?php
                      $n++;
                    }
                  }
                  $gForm->selectFromDB('artico_group', 'facility', 'id_artico_group', $form['facility'], false, 1, ' - ', 'descri', TRUE, 'col-sm-4', null, 'style="max-width: 150px;"');
                  ?>
                  Lasciare vuoto per tutte le strutture

                </div>
                <div class="form-group">
                <?php

                ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="descri" class="col-sm-4 control-label"><?php echo $script_transl['descri']; ?>&nbsp;<i class="glyphicon glyphicon-flag" title="accetta tag lingue (<it></it>)"></i></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['descri']; ?>" name="descri" maxlength="255" id="suggest_descri_artico" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <!--+ DC - 06/02/2019 -->
        <!--
        Come rendere una videata personalizzabile:
        Su tutte le div con class="row" (tranne quelle che contengono campi obbligatori)
        sostituirle nel seguente modo:
        PRIMA:
        <div class="row">
        DOPO:
        <div id="catMer" class="row IERincludeExcludeRow">
        In pratica inserite un id (unico per ogni riga) ed aggiungere la classe "IERincludeExcludeRow"
        -->
        <!--- DC - 06/02/2019 -->
        <div id="catMer" class="row IERincludeExcludeRow">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="catmer" class="col-sm-4 control-label"><?php echo $script_transl['catmer']; ?></label>
                    <?php
                    $gForm->selectFromDB('catmer', 'catmer', 'codice', $form['catmer'], false, 1, ' - ', 'descri', '', 'col-sm-8', null, 'style="max-width: 250px;"');
                    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">

      <input type="hidden" name="accommodation_type" value="1" /><!-- un alloggio è sempre servizio, quindi '1'  -->

      <input type="hidden" name="good_or_service" value="1" /><!-- un alloggio è sempre servizio, quindi '1'  -->
                </div>
            </div>
        </div><!-- chiude row  -->

        <div class="row">
        </div><!-- chiude row  -->
        <!--+ DC - 06/02/2019 div class="row" --->
        <div id="bodyText" class="row IERincludeExcludeRow">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="body_text" class="col-sm-4 control-label"><?php echo $script_transl['body_text']; ?></label>
                    <div class="col-sm-8">
                        <textarea id="body_text" name="body_text" class="mceClass"><?php echo $form['body_text']; ?></textarea>
                    </div>
                </div>
            </div>
        </div><!-- chiude row  -->
        <!--+ - 06/02/2019 div class="row" --->
        <div id="barcode" class="row IERincludeExcludeRow">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="barcode" class="col-sm-4 control-label"><?php echo $script_transl['barcode']; ?></label>
                    <input class="col-sm-4" type="text" value="<?php echo (isset($_POST['EAN']))? serchEAN():$form["barcode"]; ?>" name="barcode" maxlength="13" />
                &nbsp;<input type="submit" name="EAN" value="Genera EAN13">
						</div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="lotOrSerial" class="row IERincludeExcludeRow">
					<input type="hidden" name="lot_or_serial" value="0" />

                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="image" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="image" class="col-sm-4 control-label"><img src="../root/view.php?table=artico&value=<?php echo $form['codice']; ?>" width="100" >*</label>
                            <div class="col-sm-8"><?php echo $script_transl['image']; ?><input type="file" name="userfile" /></div>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="uniMis" class="row IERincludeExcludeRow">
					<input type="hidden" name="unimis" value="n" />

                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="sconto" class="row IERincludeExcludeRow">
					<input type="hidden" name="sconto" value="" />

                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="preve1" class="row IERincludeExcludeRow">
					<input type="hidden" name="preve1" value="0" />
					<input type="hidden" name="preve1_sc" value="0" />
					<input type="hidden" name="preve2" value="0" />
					<input type="hidden" name="preve2_sc" value="0" />
					<input type="hidden" name="preve3" value="0" />
					<input type="hidden" name="preve3_sc" value="0" />
					<input type="hidden" name="preve4" value="0" />
					<input type="hidden" name="preve4_sc" value="0" />

                </div><!-- chiude row  -->

                <div id="codFor" class="row IERincludeExcludeRow">
					<input type="hidden" name="codice_fornitore" value="" >

                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="uniAcq" class="row IERincludeExcludeRow">
					<input type="hidden" name="uniacq" value="n" />

                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="preAcq" class="row IERincludeExcludeRow">
                  <input type="hidden" name="preacq" value="0" />

                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="aliIva" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="aliiva" class="col-sm-4 control-label"><?php echo $script_transl['aliiva']; ?></label>
                            <?php
                            $gForm->selectFromDB('aliiva', 'aliiva', 'codice', $form['aliiva'], 'codice', 0, ' - ', 'descri', 'reload', 'col-sm-8', null, 'style="max-width: 350px;"');
                            $aliquo = gaz_dbi_get_row($gTables['aliiva'], 'codice', $form['aliiva'])['aliquo'];
                            ?>
                            <input id="aliquo" type="hidden" name="aliquo" value="<?php echo $aliquo; ?>" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
              </div><!-- chiude tab-pane  -->
              <div id="magazz" class="tab-pane fade">
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="refEcommercIdProduct" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="ref_ecommerce_id_product" class="col-sm-4 control-label">ID ecommerce</label>
                            <input class="col-sm-4" type="text" value="<?php echo $form['ref_ecommerce_id_product']; ?>" name="ref_ecommerce_id_product" maxlength="15" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="webUrl" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="web_url" class="col-sm-4 control-label"><?php echo $script_transl['web_url']; ?></label>
                            <input class="col-sm-8" type="text" value="<?php echo $form['web_url']; ?>" name="web_url" maxlength="255" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <?php
                 // se esiste un json per l'attributo della variante dell'e-commerce
                 /*
                if (isset ($form['var_id']) OR isset ($form['var_name'])){
                  ?>
                  <!--+ DC - 06/02/2019 div class="row" --->
                  <div id="webUrl" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                      <div class="form-group">
                        <label for="var_id" class="col-sm-4 control-label">ID attributo variante</label>
                        <input class="col-sm-8" type="text" value="<?php echo $form['var_id']; ?>" name="var_id" maxlength="255" />
                      </div>
                    </div>
                  </div><!-- chiude row  -->
                  <!--+ DC - 06/02/2019 div class="row" --->
                  <div id="webUrl" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                      <div class="form-group">
                        <label for="var_name" class="col-sm-4 control-label">Nome attributo variante</label>
                        <input class="col-sm-8" type="text" value="<?php echo $form['var_name']; ?>" name="var_name" maxlength="255" />
                      </div>
                    </div>
                  </div><!-- chiude row  -->
                  <?php
                }
                */
                ?>
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="depliPublic" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                          <input type="hidden" name="depli_public">
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="webPublic" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="web_public" class="col-sm-4 control-label"><?php echo $script_transl['web_public']; ?></label>
                            <?php
                            $gForm->variousSelect('web_public', $script_transl['web_public_value'], $form['web_public'], "col-sm-8", true, '', false, 'style="max-width: 200px;"');
                            ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
              </div><!-- chiude tab-pane  -->
              <div id="contab" class="tab-pane fade">
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="webPrice" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="web_price" class="col-sm-4 control-label"><?php echo $script_transl['web_price']; ?></label>
                            <input id="webprice" class="col-sm-4" type="text"  value="<?php echo $form['web_price']; ?>" name="web_price" maxlength="15" />
                        </div>
                        <div class="col-sm-4">
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div class="row">
                <div class="col-sm-4"></div>
                    <p class="col-sm-4" id="ivac"><p>
                    <div class="col-sm-4">
                    </div>
                </div>
                  <div id="mod_prezzo" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                          <label for="mod_prezzo" class="col-sm-4 control-label"><?php echo $script_transl[3]; ?></label>
                          <?php
                          $gForm->selTypeRow('mod_prezzo', $form['mod_prezzo'], '', $script_transl['typerow_price'],false);
                          ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="webMultiplier" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="obligatory" class="col-sm-4 control-label"><?php echo "obbligatorietà"; ?></label>
                              <input class="col-sm-4" type="text"  value="<?php echo $form['obligatory']; ?>" name="obligatory" maxlength="1" />0=no 1=si 2=selezionato
                            <input class="col-sm-4" type="hidden"  value="<?php echo $form['web_multiplier']; ?>" name="web_multiplier" maxlength="15" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="webMu" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="web_mu" class="col-sm-4 control-label"><?php echo $script_transl['web_mu']; ?></label>
                            <input class="col-sm-4" type="text" value="<?php echo $form['web_mu']; ?>" name="web_mu" maxlength="15" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
  <!--
                <div id="retentionTax" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="retention_tax" class="col-sm-4 control-label"><?php echo $script_transl['retention_tax'] . ' (' . $admin_aziend['ritenuta'] . '%)'; ?></label>
                            <?php
                            /*
                            $gForm->variousSelect('retention_tax', $script_transl['retention_tax_value'], $form['retention_tax'], "col-sm-8", true, '', false, 'style="max-width: 200px;"');
                            */
                            ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
   <!--
                <div id="payrollTax" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="payroll_tax" class="col-sm-4 control-label"><?php echo $script_transl['payroll_tax']; ?>*</label>
                            <?php
                            /*
                            $gForm->variousSelect('payroll_tax', $script_transl['payroll_tax_value'], $form['payroll_tax'], "col-sm-8", true, '', false, 'style="max-width: 200px;"');
                            */
                            ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
   <!--
                <div id="codCon" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="codcon" class="col-sm-4 control-label"><?php echo $script_transl['codcon']; ?></label>
                            <?php
                            /*
                            $gForm->selectAccount('codcon', $form['codcon'], 4, '', false, "col-sm-8");
                            */
                            ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
    <!--
                <div id="idCost" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="id_cost" class="col-sm-4 control-label"><?php echo $script_transl['id_cost']; ?></label>
                            <?php
                            /*
                            $gForm->selectAccount('id_cost', $form['id_cost'], 3, '', false, "col-sm-8");
                            */
                            ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
              </div><!-- chiude tab-pane  -->
              <div id="chifis" class="tab-pane fade">
				<div id="icals" class="row IERincludeExcludeRow">
					<fieldset  style="border:1px solid silver;">
            <legend style="color:blue; font-size:16px; margin-bottom:4px; text-align: center; border-top: 1px solid silver">Sincronizzazione Icalendar</legend>
            <div>
              <table class="Tlarge table table-striped table-bordered table-condensed" >
                <tr>
                  <td>
                    <input  type="text" value="" name="ical"  maxlength="200" placeholder="URL di un ical per sincronizzare le prenotazioni">
                  </td>
                  <td>
                    <input  type="text" value="" name="ical_descri"  maxlength="100" placeholder="Descrizione Icalendar">
                  <td>
                    <button class="btn btn-xs btn-default" type="submit" value="ical" name="icalsub" title="Inserisci un nuovo Icalendar">
                       <span class="glyphicon glyphicon-plus"></span>
                    </button>
                  </td>
                </tr>
              </table>
            </div>
            <div>
              <table class="Tlarge table table-striped table-bordered table-condensed">
                <?php
                if (isset($resical)){
                  foreach ($resical as $rical){
                    ?>
                    <tr>
                      <td>
                        <?php echo $rical['id']; ?>
                      </td>
                      <td>
                        <?php echo $rical['url']; ?>
                      </td>
                      <td>
                        <?php echo $rical['ical_descri']; ?>
                      </td>
                      <td>
                        <a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella l'Ical" ref="<?php echo $rical['id'];?>" nome="<?php echo $rical['ical_descri'];?>">
                          <i class="glyphicon glyphicon-trash"></i>
                        </a>
                      </td>
                    </tr>
                  <?php
                  }
                }
                ?>
              </table>
            </div>
					</fieldset>
				</div>
				<div id="max_quantity" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="max_quantity" class="col-sm-4 control-label">Quantità massima disponibile</label>
                            <input class="col-sm-2" type="number" value="<?php echo $form['max_quantity']; ?>" name="max_quantity"> 0 (zero) è senza limiti
                        </div>
                    </div>
                </div><!-- chiude row  -->
				<div id="adult" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <input class="col-sm-2" type="hidden" value="" name="child">
                            <input class="col-sm-2" type="hidden" value="" name="adult">
                            <input class="col-sm-2" type="hidden" value="" name="total_guests">
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="quality" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                          <input type="hidden" name="quality" value="" >
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="lunghezza" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                          <input type="hidden" name="lunghezza" value="" >
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="larghezza" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                          <input type="hidden" name="larghezza" value="" >
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="spessore" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                          <input type="hidden" name="spessore" value="" >
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="pesoSpecifico" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                          <input type="hidden" name="peso_specifico" value="" >
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="bendingMoment" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                          <input type="hidden" name="bending_moment" value="" >
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="classifAmb" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                          <input type="hidden" name="classif_amb" value="" >
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="maintenance_period" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                          <input type="hidden" name="maintenance_period" value="" >
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="volumeSpecifico" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                          <input type="hidden" name="volume_specifico" value="" >
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <!--+ DC - 06/02/2019 div class="row" --->
                <div id="annota" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="annota" class="col-sm-4 control-label"><?php echo $script_transl['annota']; ?></label>
                            <input class="col-sm-8" type="text" value="<?php echo $form['annota']; ?>" name="annota" maxlength="50" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <?php if ($toDo == 'update') {
                    /*
                  ?>
                    <!--+ DC - 06/02/2019 div class="row" --->
                    <div id="docCert" class="row IERincludeExcludeRow">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="docCert" class="col-sm-4 control-label"><?php echo $script_transl['document']; ?></label>
                                  <?php if ($ndoc > 0) { // se ho dei documenti  ?>
                                    <div>
                                    <?php foreach ($form['rows'] as $k => $val) { ?>
                                            <input type="hidden" value="<?php echo $val['id_doc']; ?>" name="rows[<?php echo $k; ?>][id_doc]">
                                            <input type="hidden" value="<?php echo $val['extension']; ?>" name="rows[<?php echo $k; ?>][extension]">
                                            <input type="hidden" value="<?php echo $val['title']; ?>" name="rows[<?php echo $k; ?>][title]">
                                            <?php echo DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/doc/' . $val['id_doc'] . '.' . $val['extension']; ?>
                                            <a href="../root/retrieve.php?id_doc=<?php echo $val["id_doc"]; ?>" title="<?php echo $script_transl['view']; ?>!" class="btn btn-default btn-sm">
                                                <i class="glyphicon glyphicon-file"></i>
                                            </a><?php echo $val['title']; ?>
                                            <input type="button" value="<?php echo ucfirst($script_transl['update']); ?>" onclick="location.href = 'admin_document.php?id_doc=<?php echo $val['id_doc']; ?>&Update'" />

                                     <?php } ?>
                                        <input type="button" value="<?php echo ucfirst($script_transl['insert']); ?>" onclick="location.href = 'admin_document.php?item_ref=<?php echo $form['codice']; ?>&Insert'" />
                                    </div>
                                    <?php } else { // non ho documenti  ?>
                                    <input type="button" value="<?php echo ucfirst($script_transl['insert']); ?>" onclick="location.href = 'admin_document.php?item_ref=<?php echo $form['codice']; ?>&Insert'">
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <!-- Antonio Germani inserimento/modifica immagini di qualità per e-commerce -->
                    <div id="qualityImgs" class="row IERincludeExcludeRow">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="annotaUpdate" class="col-sm-4 control-label"><?php echo $script_transl['imageweb']; ?></label>
                                  <?php if ($nimg > 0) { // se ho dei documenti  ?>
                                    <div>
                                    <?php foreach ($form['imgrows'] as $k => $val) { ?>
                                            <input type="hidden" value="<?php echo $val['id_doc']; ?>" name="imgrows[<?php echo $k; ?>][id_doc]">
                                            <input type="hidden" value="<?php echo $val['extension']; ?>" name="imgrows[<?php echo $k; ?>][extension]">
                                            <input type="hidden" value="<?php echo $val['title']; ?>" name="imgrows[<?php echo $k; ?>][title]">
                                            <?php echo DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/images/' . $val['id_doc'] . '.' . $val['extension']; ?>
                                            <a href="../root/retrieve.php?id_doc=<?php echo $val["id_doc"]; ?>" title="<?php echo $script_transl['view']; ?>!" class="btn btn-default btn-sm">
                                                <i class="glyphicon glyphicon-file"></i>
                                            </a><?php echo $val['title']; ?>
                                            <input type="button" value="<?php echo ucfirst($script_transl['update']); ?>" onclick="location.href = 'admin_image.php?id_doc=<?php echo $val['id_doc']; ?>&Update'" />

                                    <?php } ?>
                                        <input type="button" value="<?php echo ucfirst($script_transl['insert']); ?>" onclick="location.href = 'admin_image.php?item_ref=<?php echo $form['codice']; ?>&Insert'" />
                                    </div>
                                    <?php } else { // non ho documenti  ?>
                                    <input type="button" value="<?php echo ucfirst($script_transl['insert']); ?>" onclick="location.href = 'admin_image.php?item_ref=<?php echo $form['codice']; ?>&Insert'">
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                  <?php
                  */
                }
                ?>
            </div><!-- chiude tab-pane  -->
          </div>
        <div class="col-sm-12">
    <?php
    /** ENRICO FEDELE */
    /* SOlo se non sono in finestra modale */
    if ($modal === false) {
        echo '<div class="col-sm-4 text-left"><input name="none" type="submit" value="" disabled></div>';
    }
    /** ENRICO FEDELE */
    echo '<div class="col-sm-8 text-center"><input name="Submit" type="submit" class="btn btn-warning" value="' . ucfirst($script_transl[$toDo]) . '" /></div>';
}
?>
            </div>
        </div> <!-- chiude container -->
    </div><!-- chiude panel -->
	<div style="display:none" id="dialog_artico_position" title="Posizione negli scaffali">
        <p><b>Articolo:</b></p>
		<p class="ui-state-highlight" id="iddescri"></p>
		<table id="tblAppendGrid"></table>
	</div>
</form>
<a href="https://programmisitiweb.lacasettabio.it/gazie/vacation-rental-il-gestionale-per-case-vacanza-residence-bb-e-agriturismi/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:20%; z-index:2000;"> Vacation rental è un modulo di Antonio Germani</a>
<script type="text/javascript">
    // Basato su: https://www.abeautifulsite.net/whipping-file-inputs-into-shape-with-bootstrap-3/
    $(document).on('change', '.btn-file :file', function () {
        var input = $(this),
                numFiles = input.get(0).files ? input.get(0).files.length : 1,
                label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
        input.trigger('fileselect', [numFiles, label]);
    });
    $(document).ready(function () {
        $('.btn-file :file').on('fileselect', function (event, numFiles, label) {

            var input = $(this).parents('.input-group').find(':text'),
                    log = numFiles > 1 ? numFiles + ' files selected' : label;
            if (input.length) {
                input.val(log);
            } else {
                if (log)
                    alert(log);
            }

        });
    });

 $(document).ready(function() {
  var aliquo = Number(document.getElementById("aliquo").value)
  var webprice = Number(document.getElementById("webprice").value)
  var webpriceic = webprice + ((webprice * aliquo)/100);
  $("#ivac").html("IVA comp."+webpriceic.toFixed(2).replace('.',','));

});
$("#aliiva, #webprice").on("keyup",function(){
 var aliquo = Number(document.getElementById("aliquo").value)
 var webprice = Number(document.getElementById("webprice").value)
 var webpriceic = webprice + ((webprice * aliquo)/100);
 $("#ivac").html("IVA comp."+webpriceic.toFixed(2).replace('.',','));
 });

    </script>
<?php
/** ENRICO FEDELE */
/* SOlo se non sono in finestra modale */
if ($modal === false) {
} else {
    ?>
    <script type="text/javascript">
        $("#add-product").submit(function (e) {
            $.ajax({
                type: "POST",
                url: "../../modules/vacation_rental/admin_extra.php",
                data: $("#add-product").serialize(), // serializes the form's elements.
                success: function (data) {
                    $("#edit-modal .modal-sm").css('width', '100%');
                    $("#edit-modal .modal-body").html(data);
                }
            });
            e.preventDefault(); // avoid to execute the actual submit of the form.
        });
    </script>
    <?php
}
require("../../library/include/footer.php");
?>
