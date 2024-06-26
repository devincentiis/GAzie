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

// configurazione avanzata azienda: la descrizione estesa dell'articolo
$cbt_res=gaz_dbi_get_row($gTables['company_config'], 'var', 'ext_artico_description');
$cbt=(isset($cbt_res['val']))?$cbt_res['val']:0;
$cbt=($cbt==1||$cbt==2)?$cbt:0;

// m1 Modificato a mano
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
// m1 fine Modificato a mano

$admin_aziend = checkAdmin();

$suggest_new_codart = gaz_dbi_get_row($gTables['company_config'], 'var', 'suggest_new_codart');

$msg = array('err' => array(), 'war' => array());
$modal_ok_insert = false;
$today=	strtotime(date("Y-m-d H:i:s",time()));
$presente="";
$largeimg=0;
/** ENRICO FEDELE */
/* Inizializzo per aprire in finestra modale */
$modal = false;
if (isset($_POST['mode']) || isset($_GET['mode'])) {
    $modal = true;
    if (isset($_GET['ok_insert'])) {
        $modal_ok_insert = true;
    }
}
/** ENRICO FEDELE */
if (isset($_POST['Update']) || isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
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
	$form['codice'] = trim($form['codice']);
	$form['ritorno'] = $_POST['ritorno'];
	$form['hidden_req'] = $_POST['hidden_req'];
	$form['web_public_init'] = $_POST['web_public_init'];
	$form['var_id'] = (isset($_POST['var_id']))?$_POST['var_id']:'';
	$form['var_name'] = (isset($_POST['var_name']))?$_POST['var_name']:'';
	$form['ref_code'] = substr($_POST['ref_code'], 0,32);
	// i prezzi devono essere arrotondati come richiesti dalle impostazioni aziendali
	$form["preacq"] = number_format($form['preacq'], $admin_aziend['decimal_price'], '.', '');
	$form["preve1"] = number_format($form['preve1'], $admin_aziend['decimal_price'], '.', '');
	$form["preve2"] = number_format($form['preve2'], $admin_aziend['decimal_price'], '.', '');
	$form["preve3"] = number_format($form['preve3'], $admin_aziend['decimal_price'], '.', '');
	$form["preve4"] = number_format($form['preve4'], $admin_aziend['decimal_price'], '.', '');
	$form["web_price"] = number_format($form['web_price'], $admin_aziend['decimal_price'], '.', '');
	$form['rows'] = [];
	$form['cosepos']= (isset($_POST['cosepos']))?$_POST['cosepos']:'';
	$form['id_position'] = (isset($_POST['id_position']))?intval($_POST['id_position']):0;
  $form['id_anagra'] = filter_input(INPUT_POST, 'id_anagra');
  if (isset ($_POST['search'])){
	  foreach ($_POST['search'] as $k => $v) {
		  $form['search'][$k] = $v;
	  }
  }
  /** fine modifica FP */
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
  /** ENRICO FEDELE */
  /* Controllo se il submit viene da una modale */
  if (isset($_POST['Confirm']) || ($modal === true && isset($_POST['mode-act']))) { // ***  CONFERMA TUTTO ***
    /** ENRICO FEDELE */
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
            // controllo che il precedente non faccia parte di una distinta base
            $rs_distintabase = gaz_dbi_dyn_query('id', $gTables['distinta_base'], "codice_artico_base = '" . $form['ref_code'] . "'", "id", 0, 1);
            $rs = gaz_dbi_fetch_array($rs_distintabase);
            if ($rs) {
                $msg['err'][] = 'disbas';
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
						$new_width = intval($maxDim);
						$new_height = intval($maxDim/$ratio);
					} else {
						$new_width = intval($maxDim*$ratio);
						$new_height = intval($maxDim);
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
	/*$pattern = '/[\'\/~`\!@#\$%\^&\*\(\) \+=\{\}\[\]\|;:"\<\>,\.\?\\\]/';
		if (preg_match($pattern, $form["codice"],$match)) {
		$form["codice"] = str_replace($match,'_',$form["codice"]);
      $msg['err'][] = 'char';
  }*/
	$codart_len = gaz_dbi_get_row($gTables['company_config'], 'var', 'codart_len')['val'];
  if ($codart_len > 0 && strlen(trim($form['codice'])) <> $codart_len) {
      $msg['err'][] = 'codart_len';
  }
  if ($form['web_public']>0 && strlen($form['ref_ecommerce_id_product'])==0 && $toDo=="update"){// in update, senza id riferimento all'e-commerce non si può attivare
    $change_todo = "insert";// vuol dire che si deve inserire nell'e-commerce
  }
  if (intval($form['lot_or_serial'])>0 && intval($form['good_or_service'])==1){
    $msg['err'][] = 'no_lot';
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
    /** inizio modifica FP 03/12/2015
     * aggiorno il campo con il codice fornitore
     */
    $form['clfoco'] = $form['id_anagra'];
    /** fine modifica FP */
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

    // se esiste un json per l'attributo della variante dell'e-commerce creo il json
    if (isset ($form['var_id']) && isset ($form['var_name'])){
      $arrayvar= array("var_id" => strval($form['var_id']), "var_name" => strval($form['var_name']));
      $form['ecomm_option_attribute'] = json_encode ($arrayvar);
    }

    if ($toDo == 'insert') {
      gaz_dbi_table_insert('artico', $form);
      if (!empty($tbt)) {
        bodytextInsert(array('table_name_ref' => 'artico_' . $form['codice'], 'body_text' => $form['body_text'], 'lang_id' => $admin_aziend['id_language']));
      }
      if ($form['id_position'] > 0) { // è stata indicata una ubicazione
        $position = gaz_dbi_get_row($gTables['artico_position'], 'id_position', $form['id_position']); // prendo i valori magazzino e scaffale dal principale (senza codart)
        gaz_dbi_query("INSERT INTO ".$gTables['artico_position']." (id_warehouse, id_shelf, artico_id_position, codart) VALUES (".$position['id_warehouse'].", ".$position['id_shelf'].", ".$form['id_position'].", '".$form['codice']."')");
      }
    } elseif ($toDo == 'update') {
      gaz_dbi_table_update('artico', $form['ref_code'], $form);
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
        if (isset($change_todo)){
          $toDo=$change_todo;
        }
        $gSync->UpsertProduct($form,$toDo);
        //print $gSync->rawres;
        //exit;
      }
    }

    if ($modal === false) {
			if ($toDo == 'insert') {
        // riprendo il codice e se non è stato realmente inserito sul db lo segnalo all'utente e non reindirizzo
        $catch = gaz_dbi_get_row($gTables['artico'], 'codice', $form['codice']);
        if ($catch){
          $_SESSION['ok_ins']=$form['codice'].' - '.$form['descri'];
          header("Location: ../../modules/magazz/admin_artico.php?Update&codice=".$form['codice']);
          exit;
        } else {
          $msg['err'][] = 'no_ins';
        }
			} else{
				header("Location: " . $form['ritorno']);
        exit;
			}
    } else {
			header("Location: ../../modules/magazz/admin_artico.php?mode=modal&ok_insert=1");
      exit;
    }
  }
  /** ENRICO FEDELE */
} elseif (isset($_POST['Return']) && $modal === false) { // torno indietro
      /* Solo se non sono in finestra modale */
      /** ENRICO FEDELE */
      header("Location: " . $form['ritorno']);
      exit;
  }
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
    $form = gaz_dbi_get_row($gTables['artico'], 'codice', substr($_GET['codice'],0,32));
    /** ENRICO FEDELE */
    if ($modal === false) {
        $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    } else {
        $form['ritorno'] = 'admin_artico.php';
    }
  $form['hidden_req'] = '';
	$form['web_public_init']=$form['web_public'];
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
    /** ENRICO FEDELE */
    $form['ref_code'] = $form['codice'];
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
    /** fine modifica FP */
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
	$autoincrement_id_ecomm = gaz_dbi_get_row($gTables['company_config'], 'var', 'autoincrement_id_ecomm')['val'];// acquisico impostazione per autoincremento ID ref ecommerce
    $form = gaz_dbi_fields('artico');
    /** ENRICO FEDELE */
    if ($modal === false) {
        $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    } else {
        $form['ritorno'] = 'admin_artico.php';
    }
  $form['hidden_req'] = '';
	$form['web_public_init'] = 0;
    /** ENRICO FEDELE */
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
    $form['cosepos']= '';
    $form['id_position'] = 0;
    $form['id_anagra'] = '';
    $form['search']['id_anagra'] = '';
    /** fine modifica FP */
    // eventuale descrizione amplia
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

/** ENRICO FEDELE */
/* Solo se non sono in finestra modale carico il file di lingua del modulo */
if ($modal === false) {
    require("../../library/include/header.php");
    $script_transl = HeadMain(0, array('custom/autocomplete','appendgrid/AppendGrid'));
    // trovo la posizione nel magazzino (se presente)
    $query = 'SELECT * FROM `' . $gTables['artico_position'] . '` ap
          LEFT JOIN `' . $gTables['warehouse'] . '` wh ON ap.id_warehouse=wh.id
          LEFT JOIN `' . $gTables['shelves'] . "` sh ON ap.id_shelf=sh.id_shelf WHERE `codart` = '".$form['codice']."' AND codart<>'' ORDER BY `ap`.`id_warehouse`,`ap`.`id_shelf`,`position`";
    $rs_pos = gaz_dbi_query($query);
    $accpos='';
    if ($rs_pos->num_rows > 0){
      while ($r = gaz_dbi_fetch_array($rs_pos)) {
        $poscodart = gaz_dbi_get_row($gTables['artico_position'], 'id_position', $r['artico_id_position']);
        $accpos .= '<p class"bg-info">'.(empty($r['name'])?'SEDE':$r['name']).' Sca: '.(empty($r['descri'])?'nessun scaffale':$r['descri']).' Ubi: '.$poscodart['position'].
        ' <a class="btn btn-xs  btn-elimina dialog_posdelete" ref="'.$r['id_position'].'" codart="'.$form['codice'].'" descriposition="'.(empty($r['name'])?'SEDE':$r['name']).' - '.(empty($r['descri'])?'nessun scaffale':$r['descri']).' - '.$poscodart['position'].'" title="Elimina ubicazione"> <i class="glyphicon glyphicon-trash"></i></a></p>';
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
/** ENRICO FEDELE */
/* Assegno un id al form, quindi distinguo tra modale e non
 * in caso di finestra modale, aggiungo un campo nascosto che mi serve per salvare nel database
 */
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

  $("#dialog_delete_blob").dialog({ autoOpen: false });
	$('.dialog_delete_blob').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		var id = $(this).attr('ref');
		$( "#dialog_delete_blob" ).dialog({
			minHeight: 1,
      minWidth: 200,
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
   			close: {
					text:'Non eliminare',
					'class':'btn btn-default',
          click:function() {
            $(this).dialog("close");
          }
        },
				delete:{
					text:'Elimina',
					'class':'btn btn-danger',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'blob',ref:id},
						type: 'POST',
						url: '../magazz/delete.php',
						success: function(output){
		                    //alert(output);
							location.reload();
						}
					});
				}}
			}
		});
		$("#dialog_delete_blob" ).dialog( "open" );
	});

  $("#preve1,#preve2,#preve3,#preve4,#sconto").change(function () {
      var v = $(this).val().replace(/,/, '.');
      $(this).val(v);
      calcDiscount();
  });

  $("[href='#"+$('#tabpill').val()+"']").click()

	$("#dialog_posdelete").dialog({ autoOpen: false });
	$('.dialog_posdelete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("descriposition"));
		var id = $(this).attr('ref');
		var ca = $(this).attr('codart');
		$( "#dialog_posdelete" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
   			close: {
					text:'Non eliminare',
					'class':'btn btn-default',
          click:function() {
            $(this).dialog("close");
          }
        },
				delete:{
					text:'Elimina',
					'class':'btn btn-danger',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'position',ref:id},
						type: 'POST',
						url: '../magazz/delete.php',
						success: function(output){
		          //alert(output);
              //window.location.replace("./report_artico.php");
              window.location.replace("./admin_artico.php?Update&codice="+ca+"&tab=magazz");
						}
					});
				}}
			}
		});
		$("#dialog_posdelete" ).dialog( "open" );
	});

  $('#suggest_new_codart, #actcodice').bind("change keyup", function() {
    var val = $(this).val();
    var regex = /[^a-zA-Z0-9 _\-\.\/,!Ф()?]/g;
    if (val.match(regex)) {
      $(this).css("background", "red");
      val = val.replace(regex, "");
      $(this).val(val);
    } else {
      $(this).css("background", "white");
    }
  });

});

function choicePosition(idartico)
{
	$( "#search_position"+idartico ).autocomplete({
		source: "../root/search.php?opt=position",
		minLength: 2,
    html: true, // optional (jquery.ui.autocomplete.html.js required)
    // optional (if other layers overlap autocomplete list)
    open: function(event, ui) {
      $(".ui-autocomplete").css("z-index", 1000);
    },
		select: function(event, ui) {
      var titleacc = '';
      accidartico = '';
      titleacc += "<br/>" + $(this).attr("label");
      accidartico = $('#actcodice').val();
      $("#workingrow").append('a: '+titleacc);
			$(".position_name").replaceWith(ui.item.label);
			$("#confirm_position").dialog({
				modal: true,
				show: "blind",
				hide: "explode",
				buttons: {
					Annulla:{
          text:'Annulla',
					'class':'btn btn-defautl',
					click:function() {
            $(this).dialog('destroy');
            }
          },
					Conferma: {
            text:'Aggiungi ubicazione',
            'class':'btn btn-warning',
            click:function() {
            $.ajax({
              data: {'type':'setposition',ref:accidartico, 'val':ui.item.value},
              type: 'GET',
              url: './operat.php',
              success: function(output){
                $("#confirmform").click();
              }
            });
            }
          }
				},
				close: function(){
          $(this).dialog('destroy');
        },
        width: "40%",
        minWidth: "500px"
			});
		}
  });
}

</script>
<style>
.collapsible { cursor:pointer; }
#tblAppendGrid .form-control { height: 28px; }
.ui-dialog .ui-dialog-buttonpane .ui-dialog-buttonset { float: unset !important; }
.ui-dialog { z-index: 1000 !important; font-size: 12px;}
.btn-conferma {	color: #fff !important; background-color: #f0ad4e !important; border-color: #eea236 !important; }
</style>

<form method="POST" name="form" enctype="multipart/form-data" id="add-product">
  <div style="display:none" id="dialog_delete_blob" title="Conferma eliminazione immagine">
    <p><b>articolo:</b></p>
    <p class="ui-state-highlight" id="idcodice"></p>
  </div>
  <div class="modal" id="confirm_position" title="Aggiungi ubicazione:">
    <fieldset>
      <div>
        <div class="ui-state-highlight" id="workingrow"><b><div class="position_name" id="workingrow"></div> </b></div>
      </div>
    </fieldset>
  </div>
	<div style="display:none" id="dialog_posdelete" title="Conferma eliminazione">
        <p><b>ID:</b></p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Magazzino - scaffale - ubicazione</p>
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
    //  ENRICO FEDELE:  Se sono in finestra modale, non visualizzo questo titolo
    $changesubmit = '';
    if ($modal === false) {
        ?>
    		<script type="text/javascript" src="../../library/IER/IERincludeExcludeRows.js"></script>

    		<input type="hidden" id="IERincludeExcludeRowsInput" name="IERincludeExcludeRowsInput" />

        <div id="IERenableIncludeExcludeRows" title="Personalizza videata" onclick="enableIncludeExcludeRows()"></div>
  			<a target="_blank" href="../wiki/099 - Interfaccia generale/99.. Personalizzare una form a run-time (lato utente).md"><div id="IERhelpIncludeExcludeRows" title="Aiuto"></div></a>
  			<div id="IERsaveIncludeExcludeRows" title="Nessuna modifica fatta" onclick="saveIncludeExcludeRows()"></div>
      	<div id="IERresetIncludeExcludeRows" title="Ripristina"></div>

    		<?php
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
                <li><a data-toggle="pill" href="#magazz">Magazzino</a></li>
                <li><a data-toggle="pill" href="#contab">Contabilità</a></li>
                <li><a data-toggle="pill" href="#chifis">Chimico-fisiche</a></li>
                <li style="float: right;"><?php echo '<input name="Confirm" type="submit" class="btn btn-warning" value="' . ucfirst($script_transl[$toDo]) . '" />'; ?></li>
            </ul>
            <div class="tab-content">
              <div id="home" class="tab-pane fade in active">
                 <div class="row">
                    <div class="col-xs-12">
                        <div class="form-group">
                            <label for="codice" class="col-xs-12 col-md-4 control-label"><?php echo $script_transl['codice']; ?></label>
                            <input class="col-xs-12 col-md-4" type="text" value="<?php echo ((isset($_POST['cod']))? serchCOD():$form["codice"]); ?>" name="codice" <?php echo $suggest_new_codart?' id="actcodice" ':' id="suggest_new_codart"'; ?> maxlength="32" tabindex="1" /><div class="col-xs-12 col-md-4"><input type="submit" value="" />
              <?php
              if ($toDo != 'update'){
              ?>
                          <input type="submit" name="cod" value="Genera codice" <?php  echo ($toDo == 'update')?'disabled':'';?>>
              <?php
              }
              ?>
                       </div></div>
                    </div>
                </div><!-- chiude row  -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="descri" class="col-xs-12 col-md-4 control-label"><?php echo $script_transl['descri']; ?></label>
                            <input class="col-xs-12 col-md-8" type="text" value="<?php echo $form['descri']; ?>" name="descri" maxlength="255" id="suggest_descri_artico" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
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
                            <label for="good_or_service" class="col-sm-4 control-label"><?php echo $script_transl['good_or_service']; ?>*</label>
    <?php
    $gForm->variousSelect('good_or_service', $script_transl['good_or_service_value'], $form['good_or_service'], "col-sm-8", true, '', false, 'style="max-width: 200px;"');
    ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div class="row">
                    <div class="col-md-12">
    <?php
    $gForm->print_tree_BOM($form['codice']);
	if($form['good_or_service']==2){
	?>
                <div class="row">
                    <div class="col-md-6">
					<a href="stampa_bom.php?ri=<?php echo $form['codice']; ?>" class="btn btn-info btn-small pull-left" role="button" aria-pressed="true">Stampa la distinta base (BOM)</a>
                    </div>
                    <div class="col-md-12">
					<a href="admin_artico_compost.php?Update&codice=<?php echo $form['codice']; ?>" class="btn btn-warning btn-small pull-right" role="button" aria-pressed="true">Modifica la composizione</a>
                    </div>
                </div><!-- chiude row  -->
	<?php
	}
	$gForm->print_trunks_BOM($form['codice']);
    ?>
                    </div>
                </div><!-- chiude row  -->
                <div class="row">
                </div><!-- chiude row  -->
                <div id="bodyText" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="body_text" class="col-sm-4 control-label"><?php echo $script_transl['body_text'].'<br/><small>Inserimento documenti: '.$script_transl['body_text_val'][$cbt].'<br/><span style="font-weight: 200;">(vedi configurazione avanzata azienda)</span></small>'; ?></label>
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
                <div id="lotOrSerial" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="lot_or_serial" class="col-sm-4 control-label"><?php echo $script_transl['lot_or_serial']; ?></label>
    <?php
    $gForm->variousSelect('lot_or_serial', $script_transl['lot_or_serial_value'], $form['lot_or_serial'], "col-sm-8", true, '', false, 'style="max-width: 200px;"');
    ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="image" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="image" class="col-sm-4 control-label"><img src="../root/view.php?table=artico&value=<?php echo $form['codice']; ?>" width="100" ></label>
                            <?php
                            if (isset($form['image']) && strlen($form['image'])>10){
                              $addlabel=" Sostituisci con altra ";
                              ?>
                              <div class="col-sm-8">
                              <p ><a class="btn btn-xs  btn-elimina dialog_delete_blob" ref="<?php echo $form['codice']; ?>"> <i class="glyphicon glyphicon-trash"></i>&nbsp Elimina imagine</a></p>
                              </div>
                              <div class="col-sm-4">
                              </div>
                              <?php
                            }else{
                              $addlabel="";
                            }
                            ?>
                            <div class="col-sm-8"><?php echo $addlabel.$script_transl['image']; ?><input type="file" name="userfile" /></div>
                      </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="uniMis" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="unimis" class="col-sm-4 control-label"><?php echo $script_transl['unimis']; ?></label>
                            <input class="col-sm-2" type="text" value="<?php echo $form['unimis']; ?>" name="unimis" maxlength="3" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="sconto" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="sconto" class="col-sm-4 control-label"><?php echo $script_transl['sconto']; ?></label>
                            <input class="col-sm-2" name="sconto" id="sconto" type="number" step="0.01" min="0" max="100" value="<?php echo $form['sconto']; ?>" maxlength="6" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="preve1" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="preve1" class="col-sm-4 control-label"><?php echo $script_transl['preve1']; ?></label>
                            <input type="number" step="any" min="0" id="preve1" name="preve1" value="<?php echo $form['preve1']; ?>"  maxlength="32" />
    <?php echo $script_transl['preve1_sc']; ?>
                            <input type="text" readonly="true" id="preve1_sc" name="preve1_sc" value="<?php echo gaz_format_number($form['preve1'] * (1 - $form['sconto'] / 100)); ?>" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="preve2" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="preve2" class="col-sm-4 control-label"><?php echo $script_transl['preve2']; ?></label>
                            <input type="number" step="any" min="0" id="preve2" name="preve2" value="<?php echo $form['preve2']; ?>"  maxlength="15" />
    <?php echo $script_transl['preve2_sc']; ?>
                            <input type="text" readonly="true" id="preve2_sc" name="preve2_sc" value="<?php echo gaz_format_number($form['preve2'] * (1 - $form['sconto'] / 100)); ?>" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="preve3" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="preve3" class="col-sm-4 control-label"><?php echo $script_transl['preve3']; ?></label>
                            <input type="number" step="any" min="0" id="preve3" name="preve3" value="<?php echo $form['preve3']; ?>"  maxlength="15" />
    <?php echo $script_transl['preve3_sc']; ?>
                            <input type="text" readonly="true" id="preve3_sc" name="preve3_sc" value="<?php echo gaz_format_number($form['preve3'] * (1 - $form['sconto'] / 100)); ?>" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="preve4" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="preve4" class="col-sm-4 control-label"><?php echo $script_transl['preve4']; ?></label>
                            <input type="number" step="any" min="0" id="preve4" name="preve4" value="<?php echo $form['preve4']; ?>"  maxlength="15" />
    <?php echo $script_transl['preve4_sc']; ?>
                            <input type="text" readonly="true" id="preve4_sc" name="preve4_sc" value="<?php echo gaz_format_number($form['preve4'] * (1 - $form['sconto'] / 100)); ?>" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="codFor" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="codice_fornitore" class="col-sm-4 control-label"><?php echo $script_transl['codice_fornitore']; ?></label>
                            <input class="col-sm-8" type="text" value="<?php echo $form['codice_fornitore']; ?>" name="codice_fornitore" maxlength="50" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="uniAcq" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="uniacq" class="col-sm-4 control-label"><?php echo $script_transl['uniacq']; ?></label>
                            <input class="col-sm-2" type="text" value="<?php echo $form['uniacq']; ?>" name="uniacq" maxlength="3" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="preAcq" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="preacq" class="col-sm-4 control-label"><?php echo $script_transl['preacq'][$form['good_or_service']]; ?></label>
                            <input class="col-sm-4" type="number" step="any" min="0" value="<?php echo $form['preacq']; ?>" name="preacq" maxlength="15" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
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
                <div id="esiste" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="esiste" class="col-sm-4 control-label"><?php echo $script_transl['esiste']; ?></label>
                            <div class="col-sm-2"><?php echo $magval['q_g']; ?></div>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="valore" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="valore" class="col-sm-4 control-label"><?php echo $script_transl['valore']; ?></label>
                            <div class="col-sm-2"><?php echo $admin_aziend['symbol'] . $magval['v_g']; ?></div>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="position" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="valore" class="col-sm-4 control-label">Ubicazione</label><div class="col-sm-8">
 <i class="glyphicon glyphicon-map-marker"></i>

 <?php
if ($modal === false && $toDo=='update') {
  echo '<input id="search_position'.$form['codice'].'" onClick="choicePosition(\''.$form['codice'].'\');" value="" label="'.$form["descri"].'" rigo="'. $form['codice'] .'" type="text"  placeholder="Aggiungi nuova"/>';
?>
<?php
  echo $accpos;
} else {
  $select_position = new selectPosition("id_position");
  $select_position->addSelected($form['id_position']);
  $select_position->output($form['cosepos']);
}
?></div>
                        </div>

                        </div>
                </div><!-- chiude row  -->
                <div id="packUnits" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="pack_units" class="col-sm-4 control-label"><?php echo $script_transl['pack_units']; ?></label>
                            <input class="col-sm-4" type="number" min="0" step="any" value="<?php echo $form['pack_units']; ?>" name="pack_units" maxlength="6" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="scorta" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="scorta" class="col-sm-4 control-label"><?php echo $script_transl['scorta']; ?></label>
                            <input class="col-sm-4" type="number" min="0" step="any" value="<?php echo $form['scorta']; ?>" name="scorta" maxlength="13" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="riordino" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="riordino" class="col-sm-4 control-label"><?php echo $script_transl['riordino']; ?></label>
                            <input type="number" min="0" step="any" class="col-sm-4" type="text"  value="<?php echo $form['riordino']; ?>" name="riordino" maxlength="13" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="ragStat" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="ragstat" class="col-sm-4 control-label"><?php echo $script_transl['ragstat']; ?></label>
    <?php
    $gForm->selectFromDB('ragstat', 'ragstat', 'codice', $form['ragstat'], false, 1, ' - ', 'descri', '', 'col-sm-8', null, 'style="max-width: 250px;"');
    ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="refEcommercIdProduct" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="ref_ecommerce_id_product" class="col-sm-4 control-label">ID ecommerce</label>
                            <input class="col-sm-4" type="text" value="<?php echo $form['ref_ecommerce_id_product']; ?>" name="ref_ecommerce_id_product" maxlength="15" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
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
				if (isset ($form['var_id']) OR isset ($form['var_name'])){
					?>
					<div id="webUrl" class="row IERincludeExcludeRow">
						<div class="col-md-12">
							<div class="form-group">
								<label for="var_id" class="col-sm-4 control-label">ID attributo variante</label>
								<input class="col-sm-8" type="text" value="<?php echo $form['var_id']; ?>" name="var_id" maxlength="255" />
							</div>
						</div>
					</div><!-- chiude row  -->
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

				?>
                <div id="depliPublic" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="depli_public" class="col-sm-4 control-label"><?php echo $script_transl['depli_public']; ?></label>
    <?php
    $gForm->variousSelect('depli_public', $script_transl['depli_public_value'], $form['depli_public'], "col-sm-8", true, '', false, 'style="max-width: 200px;"');
    ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
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
                <div id="ordinabile" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="ordinabile" class="col-sm-4 control-label"><?php echo $script_transl['ordinabile']; ?></label>
	    <?php
    $gForm->variousSelect('ordinabile', $script_transl['ordinabile_value'], $form['ordinabile'], "col-sm-8", false, '', false, 'style="max-width: 200px;"');
    ?>
                         </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="movimentabile" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="movimentabile" class="col-sm-4 control-label"><?php echo $script_transl['movimentabile']; ?></label>
	    <?php
    $gForm->variousSelect('movimentabile', $script_transl['movimentabile_value'], $form['movimentabile'], "col-sm-8", false, '', false, 'style="max-width: 200px;"');
    ?>
                         </div>
                    </div>
                </div><!-- chiude row  -->
				<div id="depliPublic" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="durability_mu" class="col-sm-4 control-label"><?php echo $script_transl['durability_mu']; ?></label>
    <?php
    $gForm->variousSelect('durability_mu', $script_transl['unita_durability'], $form['durability_mu'], "col-sm-8", false, '', false, 'style="max-width: 200px;"');
    ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->

                <div id="webUrl" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="durability" class="col-sm-4 control-label"><?php echo $script_transl['durability']; ?></label>
                            <input class="col-sm-4" type="text" value="<?php echo $form['durability']; ?>" name="durability" maxlength="4" />
                        </div>
                    </div>
                </div><!-- chiude row  -->

                <div id="webUrl" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="warranty_days" class="col-sm-4 control-label"><?php echo $script_transl['warranty_days']; ?></label>
                            <input class="col-sm-4" type="text" value="<?php echo (isset($form['warranty_days']))?$form['warranty_days']:''; ?>" name="warranty_days" maxlength="4" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="id_anagra" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="id_anagra" class="col-sm-4 control-label"><?php echo $script_transl['id_anagra']; ?></label>
    <?php
    $select_id_anagra = new selectPartner("id_anagra");
    $select_id_anagra->selectDocPartner('id_anagra', $form['id_anagra'], $form['search']['id_anagra'], 'id_anagra', $script_transl['mesg'], $admin_aziend['masfor'], -1, 1, true);
    ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="last_buys" class="row IERincludeExcludeRow">
                    <div class="col-xs-12">
                        <div class="form-group">
                            <label for="last_buys" class="col-xs-12 control-label"><?php echo $script_transl['last_buys']; ?></label>
	    <?php
    echo $gForm->getLastBuys($form['codice'], true);
    ?>
                         </div>
                    </div>
                </div><!-- chiude row  -->
              </div><!-- chiude tab-pane  -->
              <div id="contab" class="tab-pane fade">
                <div id="webPrice" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="web_price" class="col-sm-4 control-label"><?php echo $script_transl['web_price']; ?></label>
                            <input class="col-sm-4" id="webprice" type="text"  value="<?php echo $form['web_price']; ?>" name="web_price" maxlength="15" />
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
                <div id="webMultiplier" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="web_multiplier" class="col-sm-4 control-label"><?php echo $script_transl['web_multiplier']; ?></label>
                            <input class="col-sm-4" type="text"  value="<?php echo $form['web_multiplier']; ?>" name="web_multiplier" maxlength="15" />
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
                <div id="retentionTax" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="retention_tax" class="col-sm-4 control-label"><?php echo $script_transl['retention_tax'] . ' (' . $admin_aziend['ritenuta'] . '%)'; ?></label>
    <?php
    $gForm->variousSelect('retention_tax', $script_transl['retention_tax_value'], $form['retention_tax'], "col-sm-8", true, '', false, 'style="max-width: 200px;"');
    ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="payrollTax" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="payroll_tax" class="col-sm-4 control-label"><?php echo $script_transl['payroll_tax']; ?>*</label>
    <?php
    $gForm->variousSelect('payroll_tax', $script_transl['payroll_tax_value'], $form['payroll_tax'], "col-sm-8", true, '', false, 'style="max-width: 200px;"');
    ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="codCon" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="codcon" class="col-sm-4 control-label"><?php echo $script_transl['codcon']; ?></label>
    <?php
    $gForm->selectAccount('codcon', $form['codcon'], 4, '', false, "col-sm-8");
    ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="idCost" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="id_cost" class="col-sm-4 control-label"><?php echo $script_transl['id_cost']; ?></label>
    <?php
    $gForm->selectAccount('id_cost', $form['id_cost'], 3, '', false, "col-sm-8");
    ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
              </div><!-- chiude tab-pane  -->
              <div id="chifis" class="tab-pane fade">
                <div id="quality" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="quality" class="col-sm-4 control-label"><?php echo $script_transl['quality']; ?></label>
                            <input class="col-sm-2" type="text" value="<?php echo $form['quality']; ?>" name="quality"maxlength="50"/>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="lunghezza" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="lunghezza" class="col-sm-4 control-label"><?php echo $script_transl['lunghezza']; ?></label>
                            <input class="col-sm-2" type="number" step="0.01" value="<?php echo $form['lunghezza']; ?>" name="lunghezza" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="larghezza" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="larghezza" class="col-sm-4 control-label"><?php echo $script_transl['larghezza']; ?></label>
                            <input class="col-sm-2" type="number" step="0.01" value="<?php echo $form['larghezza']; ?>" name="larghezza" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="spessore" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="spessore" class="col-sm-4 control-label"><?php echo $script_transl['spessore']; ?></label>
                            <input class="col-sm-2" type="number" step="0.01" value="<?php echo $form['spessore']; ?>" name="spessore" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="pesoSpecifico" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="peso_specifico" class="col-sm-4 control-label"><?php echo $script_transl['peso_specifico']; ?></label>
                            <input class="col-sm-4" type="number" min="0" step="any" value="<?php echo $form['peso_specifico']; ?>" name="peso_specifico" maxlength="13" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="bendingMoment" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="bending_moment" class="col-sm-4 control-label"><?php echo $script_transl['bending_moment']; ?></label>
                            <input class="col-sm-2" name="bending_moment" id="bending_moment" type="number" step="0.01" min="0" max="100000" value="<?php echo $form['bending_moment']; ?>" maxlength="8" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="classifAmb" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="classif_amb" class="col-sm-4 control-label"><?php echo $script_transl['classif_amb']; ?></label>
    <?php
    $gForm->variousSelect('classif_amb', $script_transl['classif_amb_value'], $form['classif_amb'], "col-sm-8", false, '', false, 'style="max-width: 200px;"');
    ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="SIAN" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="classif_amb" class="col-sm-4 control-label"><?php echo $script_transl['SIAN']; ?></label>
                            <div class="col-sm-8">
    <?php
    $gForm->radioSelect('SIAN', $script_transl['SIAN_value'], $form['SIAN'], 'col-md-3');
    ?>
                            </div>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="maintenance_period" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="maintenance_period" class="col-sm-4 control-label"><?php echo $script_transl['maintenance_period']; ?></label>
                            <input type="number" min="0" max="999" step="1" class="col-sm-4"  value="<?php echo $form['maintenance_period']; ?>" name="maintenance_period" maxlength="3" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="volumeSpecifico" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="volume_specifico" class="col-sm-4 control-label"><?php echo $script_transl['volume_specifico']; ?></label>
                            <input class="col-sm-4" type="number" min="0" step="any" value="<?php echo $form['volume_specifico']; ?>" name="volume_specifico" maxlength="13" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <div id="annota" class="row IERincludeExcludeRow">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="annota" class="col-sm-4 control-label"><?php echo $script_transl['annota']; ?></label>
                            <input class="col-sm-8" type="text" value="<?php echo $form['annota']; ?>" name="annota" maxlength="50" />
                        </div>
                    </div>
                </div><!-- chiude row  -->
    <?php if ($toDo == 'update') { ?>
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
                                            <a href="../root/retrieve.php?id_doc=<?php echo $val["id_doc"]; ?>" title="<?php echo $script_transl['view']; ?>!" class="btn btn-default btn-sm">
                                                <i class="glyphicon glyphicon-eye-open"></i>
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
                                            <a href="../root/retrieve.php?id_doc=<?php echo $val["id_doc"]; ?>" title="<?php echo $script_transl['view']; ?>!" class="btn btn-default btn-sm">
                                                <i class="glyphicon glyphicon-eye-open"></i>
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
    <?php } ?>
            </div><!-- chiude tab-pane  -->
          </div>
        <div class="col-sm-12 FacetFooterTD">
    <?php
    /** ENRICO FEDELE */
    /* SOlo se non sono in finestra modale */
    if ($modal === false) {
        echo '<div class="col-sm-4 text-left"><input name="none" type="submit" value="" disabled></div>';
    }
    /** ENRICO FEDELE */
    echo '<div class="col-sm-8 text-center"><input name="Confirm" id="confirmform" type="submit" class="btn btn-warning" value="' . ucfirst($script_transl[$toDo]) . '" /></div>';
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
<script type="text/javascript">
    // Basato su: http://www.abeautifulsite.net/whipping-file-inputs-into-shape-with-bootstrap-3/
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
// ENRICO FEDELE: Solo se non sono in finestra modale
if ($modal === false) {
} else {
    ?>
    <script type="text/javascript">
        $("#add-product").submit(function (e)
        {
            $.ajax({
                type: "POST",
                url: "../../modules/magazz/admin_artico.php",
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
	// solo per evitare errori in finestra modale
	$period=false;
	function get_rref_type($value) {
	}
  function pulisci_rref_name(){
  }
  function printCheckbox(){
  }
  $config = new UserConfig;
}
require("../../library/include/footer.php");
?>
