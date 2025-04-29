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
$admin_aziend = checkAdmin();
$msg = array('err' => array(), 'war' => array());
$modal_ok_insert = false;
$today=	strtotime(date("Y-m-d H:i:s",time()));
$presente="";
$largeimg="";

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

if(isset($_GET['delete'])) {
	gaz_dbi_table_update ("artico", $_GET['delete'], array("id_artico_group"=>"") );
	header("Location: ../vacation_rental/admin_facility.php?Update&id_artico_group=".$_GET['group']."&tab=variant");
}

if(isset($_GET['group_delete'])) {

	$query = "SELECT codice, descri FROM " . $gTables['artico'] . " WHERE id_artico_group = '".$_GET['group_delete']."'";
	$arts = gaz_dbi_query($query);
	while ($art = $arts->fetch_assoc()) {// scollego tutti gli alloggi
	gaz_dbi_table_update ("artico", $art['codice'], array("id_artico_group"=>"") );
	}
	gaz_dbi_del_row($gTables['artico_group'], "id_artico_group", $_GET['group_delete']);// cancello la struttura
  gaz_dbi_del_row($gTables['body_text'], "table_name_ref", "artico_group' AND code_ref = '".$_GET['group_delete']);// cancello i bodytext in lingua del gruppo

	header("Location: ../vacation_rental/report_accommodation.php");
	exit;
}

//Carico tutte le lingue del gestionale
$langs=gaz_dbi_fetch_all(gaz_dbi_dyn_query("*",$gTables['languages'],'lang_id > 1','lang_id'));

if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
	$form = gaz_dbi_parse_post('artico_group');
	$form['id_artico_group'] = trim($form['id_artico_group']);
	$form['ritorno'] = $_POST['ritorno'];
  $form['paypal_email'] = $_POST['paypal_email'];
  $form['hype_transf'] = $_POST['hype_transf'];
  $form['stripe_pub_key'] = $_POST['stripe_pub_key'];
  $form['stripe_sec_key'] = $_POST['stripe_sec_key'];
  $form['check_in'] = $_POST['check_in'];
  $form['week_check_in'] = $_POST['week_check_in'];
  $form['week_check_out'] = $_POST['week_check_out'];
  $form['check_out'] = $_POST['check_out'];
  $form['minor'] = $_POST['minor'];
  $form['open_from'] = $_POST['open_from'];
  $form['open_to'] = $_POST['open_to'];
  $form['tour_tax_from'] = $_POST['tour_tax_from'];
  $form['tour_tax_to'] = $_POST['tour_tax_to'];
  $form['tour_tax_day'] = intval($_POST['tour_tax_day']);
  $form['max_booking_days'] = intval($_POST['max_booking_days']);
	$form['ref_ecommerce_id_main_product'] = substr($_POST['ref_ecommerce_id_main_product'], 0, 9);
	$form['large_descri'] = $_POST['large_descri'];
	$form['cosear'] = filter_var($_POST['cosear'],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$form['codart'] = filter_var($_POST['codart'],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$form['lat'] = $_POST['lat'];
  $form['long'] = $_POST['long'];
  $form['cin'] = $_POST['cin'];
  $form['csmt'] = $_POST['csmt'];
  $form['lang_id'] = intval($_POST['lang_id']);
  foreach($langs as $lang){
    if (intval($lang['lang_id'])==1){ continue;}
    $form['lang_descri'.$lang['lang_id']]=filter_var(substr($_POST['lang_descri'.$lang['lang_id']],0,100), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $form['lang_bodytext'.$lang['lang_id']]=filter_var($_POST['lang_bodytext'.$lang['lang_id']], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $form['lang_web_url'.$lang['lang_id']]=filter_var(substr($_POST['lang_web_url'.$lang['lang_id']],0,100), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $form['lang_check_in'.$lang['lang_id']]=filter_var(substr($_POST['lang_check_in'.$lang['lang_id']],0,100), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $form['lang_check_out'.$lang['lang_id']]=filter_var(substr($_POST['lang_check_out'.$lang['lang_id']],0,100), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  }
  $form['hidden_req'] = $_POST['hidden_req'];
  if ($form['hidden_req']=='refresh_language') { // se ho cambiato la lingua ricarico dal database i valori di descrizione e descrizione estesa
    $bodytextol = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", 'artico', " AND code_ref = '" . $form['id_artico_group']."' AND lang_id = '".$form['lang_id']."'");
    if ($bodytextol && $form['lang_id'] > 1 ) { // riprendo dal db solo se non è italiano ed esiste
      $form['lang_descri']=$bodytextol['descri'];
      $form['lang_bodytext']=$bodytextol['body_text'];
    }
    $form['hidden_req']='';
  }
  if ((isset($_GET['tab']) && $_GET['tab']=="variant") || ($_POST['cosear'] <> $_POST['codart']) ){
		$cl_home="";
		$cl_home_tab="";
		$cl_variant="active";
		$cl_variant_tab="in active";
	} else {
		$cl_home="active";
		$cl_home_tab="in active";
		$cl_variant="";
		$cl_variant_tab="";
	}
	if(isset($_POST['codart']) AND isset($_POST['OKsub'])&& $_POST['OKsub']=="Salva"){	// se si salva la selezione degli alloggi facenti parte della struttura
		if ($toDo == 'insert'){// se è un nuovo inserimento struttura
			 if (empty($form["descri"])) { // controllo che sia stata inserita almeno la descrizione
				$msg['err'][] = 'descri';
			}
			if (strlen($_POST['codart'])==0){
				$msg['err'][] = 'empty_var';
				$cl_home="";
				$cl_home_tab="";
				$cl_variant="active";
				$cl_variant_tab="in active";
			}
		}
		// devo controllare se l'alloggio che si sta inserendo appartiene già ad un'altra struttura
		$ckart=gaz_dbi_get_row($gTables['artico'], 'codice', $_POST['codart']);

		if (!isset($ckart['id_artico_group']) && strlen($_POST['codart'])>0){
			$msg['err'][] = 'grcod';
			$cl_home="";
			$cl_home_tab="";
			$cl_variant="active";
			$cl_variant_tab="in active";
		}
		if (count($msg['err']) == 0) {// nessun errore
			if (isset($_POST['codart']) && $toDo == 'insert'){// inserisco l'alloggio nella struttura
				$array= array('vacation_rental'=>array('facility_type' => ''));// creo l'array per il custom field
				$form['custom_field'] = json_encode($array);// codifico in json  e lo inserisco nel form
				gaz_dbi_table_insert('artico_group', $form);
				$form['custom_field']=(isset($_POST['custom_field']))?$_POST['custom_field']:''; // riporto il custom field a quello di artico
				$form['id_artico_group']=gaz_dbi_last_id();
				gaz_dbi_table_update ("artico", $_POST['codart'], array("id_artico_group"=>$form['id_artico_group']) );
        if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname']) && intval($_POST['web_public'])>0){
          // Aggiornamento parent su e-commerce
          $gs=$admin_aziend['synccommerce_classname'];
          $gSync = new $gs();
          if($gSync->api_token){
            $gSync->UpsertParent($form,$toDo);
          }
        }
        foreach($langs as $l){
          if ($l['lang_id']==1){ continue;}
          $custom_field_url = array('web_url'=>$form['lang_web_url'.$l['lang_id']],'check_in'=>$form['lang_check_in'.$l['lang_id']],'check_out'=>$form['lang_check_out'.$l['lang_id']]);
          $custom=json_encode($custom_field_url);
          bodytextInsert(['table_name_ref'=>'artico_group','code_ref'=>strval($form['id_artico_group']),'body_text'=>$form['lang_bodytext'.$l['lang_id']],'descri'=>$form['lang_descri'.$l['lang_id']],'lang_id'=>$l['lang_id'],'custom_field'=>$custom]);
        }
				// il redirect deve modificare il form in update perché è stato già inserito
				header("Location: ../vacation_rental/admin_facility.php?Update&id_artico_group=".$form['id_artico_group']."&tab=variant");
			} elseif (isset($_POST['codart'])){
				gaz_dbi_table_update ("artico", $_POST['codart'], array("id_artico_group"=>$form['id_artico_group']));
        if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname']) && intval($_POST['web_public'])>0){
          // Aggiornamento parent su e-commerce
          $gs=$admin_aziend['synccommerce_classname'];
          $gSync = new $gs();
          if($gSync->api_token){
            $gSync->UpsertParent($form,$toDo);
          }
        }
				// il redirect deve modificare il form in update perché è stato già inserito
				header("Location: ../vacation_rental/admin_facility.php?Update&id_artico_group=".$form['id_artico_group']."&tab=variant");
			}
		}
	}
	/** ENRICO FEDELE */
	/* Controllo se il submit viene da una modale */
	if (isset($_POST['Submit']) || ($modal === true && isset($_POST['mode-act']))) { // conferma tutto

		if ($toDo == 'update') {  // controlli in caso di modifica

		} else {
			// controllo che l'alloggio ci sia gia'
			$rs_articolo = gaz_dbi_dyn_query('id_artico_group', $gTables['artico_group'], "id_artico_group = '" . $form['id_artico_group'] . "'", "id_artico_group DESC", 0, 1);
			$rs = gaz_dbi_fetch_array($rs_articolo);
			if ($rs) {
				$msg['err'][] = 'codice';
			}
		}
    if ($_FILES['userfile']['error']==1){
      $msg['err'][] = 'filetoobig';
    } else {
      if (!empty($_FILES['userfile']['name'])) {
        if (!( strtolower($_FILES['userfile']['type']) == "image/png" ||
            strtolower($_FILES['userfile']['type']) == "image/x-png" ||
            strtolower($_FILES['userfile']['type']) == "image/jpeg" ||
            strtolower($_FILES['userfile']['type']) == "image/jpg" ||
            strtolower($_FILES['userfile']['type']) == "image/gif" ||
            strtolower($_FILES['userfile']['type']) == "image/x-gif")) $msg['err'][] = 'filmim';
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
                $new_height = $maxDim/$ratio;
              } else {
                $new_width = $maxDim*$ratio;
                $new_height = $maxDim;
              }
              $src = imagecreatefromstring( file_get_contents( $file_name ) );
              $dst = imagecreatetruecolor( intval($new_width), intval($new_height) );
              imagecopyresampled( $dst, $src, 0, 0, 0, 0, intval($new_width), intval($new_height), $width, $height );
              imagedestroy( $src );
              imagepng( $dst, $target_filename); // adjust format as needed
              imagedestroy( $dst );
            }
          // fine ridimensionamento immagine
          $largeimg=1;
          } else {
            $target_filename=$file_name = $_FILES['userfile']['tmp_name'];
          }
      }
    }
		if (empty($form["id_artico_group"]) AND $toDo == 'update') {
			$msg['err'][] = 'valcod';
		}
		if (empty($form["descri"])) {
			$msg['err'][] = 'descri';
		}
		if ($toDo == 'insert') {
			if (!isset($_POST['variant'])){
				$msg['err'][] = 'empty_var';
			}
		}

    //Latitude
    if(strlen($form['lat'])>0 && !preg_match('/^-?(90|[1-8][0-9][.][0-9]{1,20}|[0-9][.][0-9]{1,20})$/', $form['lat'])) {
      $msg['err'][] = 'issue_lat';
    }
    //Longitude
    if(strlen($form['long'])>0 && !preg_match('/^-?(180|1[1-7][0-9][.][0-9]{1,20}|[1-9][0-9][.][0-9]{1,20}|[0-9][.][0-9]{1,20})$/', $form['long'])) {
      $msg['err'][] = 'issue_long';
    }

		if (count($msg['err']) == 0) { // nessun errore
			if (!empty($_FILES['userfile']) && $_FILES['userfile']['size'] > 0) { //se c'e' una nuova immagine nel buffer
					if ($largeimg==0){
						$form['image'] = file_get_contents($_FILES['userfile']['tmp_name']);
					} else {
						$form['image'] = file_get_contents($target_filename);
					}
			} elseif ($toDo == 'update') { // altrimenti riprendo la vecchia ma solo se è una modifica
			  $oldimage = gaz_dbi_get_row($gTables['artico_group'], 'id_artico_group', $form['ref_ecommerce_id_main_product']);
			  $form['image'] = ($oldimage)?$oldimage['image']:'';
			} else {
			  $form['image'] = '';
			}
      $form['large_descri'] = htmlspecialchars_decode ($form['large_descri']);

			// aggiorno il db
			if ($toDo == 'insert') {
				$array= array('vacation_rental'=>array('facility_type' => '', 'paypal_email' => $form['paypal_email'], 'hype_transf' => $form['hype_transf'], 'stripe_pub_key' => $form['stripe_pub_key'], 'stripe_sec_key' => $form['stripe_sec_key'], 'check_in' => $form['check_in'], 'check_out' => $form['check_out'], 'week_check_in' => $form['week_check_in'], 'week_check_out' => $form['week_check_out'], 'minor' => $form['minor'], 'tour_tax_from' => $form['tour_tax_from'], 'tour_tax_to' => $form['tour_tax_to'], 'open_from' => $form['open_from'], 'open_to' => $form['open_to'], 'tour_tax_day' => $form['tour_tax_day'], 'max_booking_days' => $form['max_booking_days'], 'latitude' => $form['lat'], 'longitude' => $form['long'], 'cin' => $form['cin'], 'csmt' => $form['csmt']));// creo l'array per il custom field
				$form['custom_field'] = json_encode($array);// codifico in json  e lo inserisco nel form
				gaz_dbi_table_insert('artico_group', $form);
        // in inserimento scrivo tutte le lingue straniere
        foreach($langs as $l){
          if ($l['lang_id']==1){ continue;}
          $custom_field_url = array('web_url'=>$form['lang_web_url'.$l['lang_id']], 'check_in'=>$form['lang_check_in'.$l['lang_id']], 'check_out'=>$form['lang_check_out'.$l['lang_id']]);
          $custom=json_encode($custom_field_url);
          bodytextInsert(['table_name_ref'=>'artico_group','code_ref'=>$form['id_artico_group'],'body_text'=>$form['lang_bodytext'.$l['lang_id']],'descri'=>$form['lang_descri'.$l['lang_id']],'lang_id'=>$l['lang_id'],'custom_field'=>$custom]);
        }
			} elseif ($toDo == 'update') {

				$custom_field=gaz_dbi_get_row($gTables['artico_group'], "id_artico_group", $form['id_artico_group'])['custom_field']; // carico il vecchio json custom_field
        if ($data = json_decode($custom_field,true)){// se c'è un json
          if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental" lo aggiorno
            $data['vacation_rental']['facility_type']='';
            $data['vacation_rental']['paypal_email']=$_POST['paypal_email'];
            $data['vacation_rental']['hype_transf']=$_POST['hype_transf'];
            $data['vacation_rental']['stripe_pub_key']=$_POST['stripe_pub_key'];
            $data['vacation_rental']['stripe_sec_key']=$_POST['stripe_sec_key'];
            $data['vacation_rental']['check_in']=$_POST['check_in'];
            $data['vacation_rental']['week_check_in']=$_POST['week_check_in'];
            $data['vacation_rental']['week_check_out']=$_POST['week_check_out'];
            $data['vacation_rental']['check_out']=$_POST['check_out'];
            $data['vacation_rental']['minor']=$_POST['minor'];
            $data['vacation_rental']['tour_tax_from']=$_POST['tour_tax_from'];
            $data['vacation_rental']['tour_tax_to']=$_POST['tour_tax_to'];
            $data['vacation_rental']['open_from']=$_POST['open_from'];
            $data['vacation_rental']['open_to']=$_POST['open_to'];
            $data['vacation_rental']['tour_tax_day']=$_POST['tour_tax_day'];
            $data['vacation_rental']['max_booking_days']=$_POST['max_booking_days'];
            $data['vacation_rental']['latitude']=$_POST['lat'];
            $data['vacation_rental']['longitude']=$_POST['long'];
            $data['vacation_rental']['cin']=$_POST['cin'];
            $data['vacation_rental']['csmt']=$_POST['csmt'];
            $form['custom_field'] = json_encode($data);
          } else { //se non c'è il modulo "vacation_rental" lo aggiungo
            $data['vacation_rental']= array('facility_type' => '', 'paypal_email' => $_POST['paypal_email'], 'hype_transf' => $form['hype_transf'], 'stripe_pub_key' => $_POST['stripe_pub_key'], 'stripe_sec_key' => $_POST['stripe_sec_key'], 'check_in' => $_POST['check_in'], 'check_out' => $_POST['check_out'], 'week_check_in' => $_POST['week_check_in'], 'week_check_out' => $_POST['week_check_out'], 'minor' => $_POST['minor'], 'tour_tax_from' => $_POST['tour_tax_from'], 'tour_tax_to' => $_POST['tour_tax_to'], 'open_from' => $_POST['open_from'], 'open_to' => $_POST['open_to'], 'tour_tax_day' => $_POST['tour_tax_day'], 'max_booking_days' => $_POST['max_booking_days'], 'latitude' => $_POST['lat'], 'longitude' => $_POST['long'], 'cin' => $_POST['cin'], 'csmt' => $_POST['csmt']);
            $form['custom_field'] = json_encode($data);
          }
        }
				gaz_dbi_table_update('artico_group', array( 0 => "id_artico_group", 1 => $form['id_artico_group']), $form);
        foreach($langs as $lang){// in aggiornamento modifico comunque tutte le traduzioni
          //per retrocompatibilità devo controllare sempre se esiste la traduzione
          if ($lang['lang_id']==1){ continue;}
          $custom_field_url = array('web_url'=>$form['lang_web_url'.$lang['lang_id']]);
          $custom_field_url = array('web_url'=>$form['lang_web_url'.$lang['lang_id']], 'check_in'=>$form['lang_check_in'.$lang['lang_id']], 'check_out'=>$form['lang_check_out'.$lang['lang_id']]);
          $custom=json_encode($custom_field_url);
          $bodytextol = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", 'artico_group', " AND code_ref = '" . $form['id_artico_group']."' AND lang_id = '".$lang['lang_id']."'");
          if (!$bodytextol) { // non c'è la traduzione in lingua straniera, la creo
             bodytextInsert(['table_name_ref'=>'artico_group','code_ref'=>$form['id_artico_group'],'body_text'=>$form['lang_bodytext'.$lang['lang_id']],'descri'=>$form['lang_descri'.$lang['lang_id']],'lang_id'=>$lang['lang_id'],'custom_field'=>$custom]);
          }else{// altrimenti la aggiorno
            gaz_dbi_query("UPDATE ".$gTables['body_text']." SET body_text='".$form['lang_bodytext'.$lang['lang_id']]."', descri='".$form['lang_descri'.$lang['lang_id']]."', custom_field='".$custom."' WHERE table_name_ref='artico_group' AND code_ref='".$form['id_artico_group']."' AND lang_id = '".$lang['lang_id']."'");
          }
        }
			}

			if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname'])&& intval($_POST['web_public'])>0){
				// Aggiornamento parent su e-commerce
				$gs=$admin_aziend['synccommerce_classname'];
				$gSync = new $gs();
				if($gSync->api_token){
					$gSync->UpsertParent($form,$toDo);
				}
			}
			/** ENRICO FEDELE */
			/* Niente redirect se sono in finestra modale */
			if ($modal === false) {

				header("Location: ../../modules/vacation_rental/report_facility.php");
				exit;

			} else {
				header("Location: ../../modules/vacation_renatl/admin_facility.php?mode=modal&ok_insert=1");
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
  $form = gaz_dbi_get_row($gTables['artico_group'], 'id_artico_group', intval($_GET['id_artico_group']));
	$form['cosear'] = "";
	$form['codart'] = "";
  $form['lang_id'] = 1;
  foreach($langs as $lang){// carico le traduzioni dal DB e le metto nelle rispettive lingue
    if (intval($lang['lang_id'])==1){ continue;}
    $bodytextlang = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", 'artico_group', " AND code_ref = '".substr($_GET['id_artico_group'],0,32)."' AND lang_id = ".$lang['lang_id']);
    $form['lang_descri'.$lang['lang_id']] = (isset($bodytextlang['descri']))?$bodytextlang['descri']:$form['descri'];
    $form['lang_bodytext'.$lang['lang_id']] = (isset($bodytextlang['body_text']))?$bodytextlang['body_text']:filter_var($form['large_descri'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $obj = (isset($bodytextlang['custom_field']))?json_decode($bodytextlang['custom_field']):'';
    $form['lang_web_url'.$lang['lang_id']] = (isset($obj->web_url))?$obj->web_url:$form['web_url'];
    $form['lang_check_in'.$lang['lang_id']] = (isset($obj->check_in))?$obj->check_in:'';
    $form['lang_check_out'.$lang['lang_id']] = (isset($obj->check_out))?$obj->check_out:'';
  }
  $form['hidden_req'] = '';
  if ($data = json_decode($form['custom_field'], TRUE)) { // se esiste un json nel custom field
    if (is_array($data['vacation_rental'])){
				$form['facility_type'] = $data['vacation_rental']['facility_type'];
				$form['paypal_email'] = (isset($data['vacation_rental']['paypal_email']))?$data['vacation_rental']['paypal_email']:'';
        $form['hype_transf'] = (isset($data['vacation_rental']['hype_transf']))?$data['vacation_rental']['hype_transf']:'';
        $form['stripe_pub_key'] = (isset($data['vacation_rental']['stripe_pub_key']))?$data['vacation_rental']['stripe_pub_key']:'';
        $form['stripe_sec_key'] = (isset($data['vacation_rental']['stripe_sec_key']))?$data['vacation_rental']['stripe_sec_key']:'';
        $form['check_in'] = (isset($data['vacation_rental']['check_in']))?$data['vacation_rental']['check_in']:'';
        $form['week_check_in'] = (isset($data['vacation_rental']['week_check_in']))?$data['vacation_rental']['week_check_in']:'';
        $form['week_check_out'] = (isset($data['vacation_rental']['week_check_out']))?$data['vacation_rental']['week_check_out']:'';
        $form['check_out'] = (isset($data['vacation_rental']['check_out']))?$data['vacation_rental']['check_out']:'';
        $form['minor'] = (isset($data['vacation_rental']['minor']))?$data['vacation_rental']['minor']:'';
        $form['tour_tax_from'] = (isset($data['vacation_rental']['tour_tax_from']))?$data['vacation_rental']['tour_tax_from']:'';
        $form['tour_tax_to'] = (isset($data['vacation_rental']['tour_tax_to']))?$data['vacation_rental']['tour_tax_to']:'';
        $form['open_from'] = (isset($data['vacation_rental']['open_from']))?$data['vacation_rental']['open_from']:'';
        $form['open_to'] = (isset($data['vacation_rental']['open_to']))?$data['vacation_rental']['open_to']:'';
        $form['tour_tax_day'] = (isset($data['vacation_rental']['tour_tax_day']))?intval($data['vacation_rental']['tour_tax_day']):0;
        $form['max_booking_days'] = (isset($data['vacation_rental']['max_booking_days']))?intval($data['vacation_rental']['max_booking_days']):0;
        $form['lat'] = (isset($data['vacation_rental']['latitude']))?$data['vacation_rental']['latitude']:'';
        $form['long'] = (isset($data['vacation_rental']['longitude']))?$data['vacation_rental']['longitude']:'';
        $form['cin'] = (isset($data['vacation_rental']['cin']))?$data['vacation_rental']['cin']:'';
        $form['csmt'] = (isset($data['vacation_rental']['csmt']))?$data['vacation_rental']['csmt']:'';
    } else {
				$form['facility_type'] = '';
				$form['paypal_email'] ='';
        $form['hype_transf'] ='';
        $form['stripe_pub_key'] = '';
        $form['stripe_sec_key'] = '';
        $form['check_in'] = "";
        $form['week_check_in'] = "";
        $form['week_check_out'] = "";
        $form['check_out'] = "";
        $form['minor'] = "";
        $form['tour_tax_from'] = "";
        $form['tour_tax_to'] = 0;
        $form['open_from'] = "";
        $form['open_to'] = "";
        $form['tour_tax_day'] = "";
        $form['max_booking_days'] = "";
        $form['lat'] = "";
        $form['long'] = "";
        $form['cin'] = "";
        $form['csmt'] = "";
    }
	} else {
    $form['facility_type'] = '';
		$form['paypal_email'] = '';
    $form['hype_transf'] ='';
    $form['stripe_pub_key'] = '';
    $form['stripe_sec_key'] = '';
    $form['check_in'] = "";
    $form['check_out'] = "";
    $form['week_check_in'] = "";
    $form['week_check_out'] = "";
    $form['minor'] = "";
    $form['tour_tax_from'] = "";
    $form['tour_tax_to'] = "";
    $form['open_from'] = "";
    $form['open_to'] = "";
    $form['tour_tax_day'] = 0;
    $form['max_booking_days'] = 0;
	}

	if (isset($_GET['tab']) && $_GET['tab']=="variant"){
		$cl_home="";
		$cl_home_tab="";
		$cl_variant="active";
		$cl_variant_tab="in active";
	} else {
		$cl_home="active";
		$cl_home_tab="in active";
		$cl_variant="";
		$cl_variant_tab="";
	}
  /** ENRICO FEDELE */
  if ($modal === false) {
      $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  } else {
      $form['ritorno'] = 'admin_facility.php';
  }

} else { //se e' il primo accesso per INSERT
    $form = gaz_dbi_fields('artico');
	$form['cosear'] = "";
	$form['codart'] = "";
    /** ENRICO FEDELE */
    if ($modal === false) {
        $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    } else {
        $form['ritorno'] = 'admin_facility.php';
    }
    $form['lang_id'] = 1;
    // solo se cambio lingua andrò a valorizzare i lang_descri e lang_bodytext (e sul form metterò hidden all'italiano)
    foreach($langs as $lang){
      if (intval($lang['lang_id'])==1){ continue;}
      $form['lang_descri'.$lang['lang_id']] = '';
      $form['lang_bodytext'.$lang['lang_id']] = '';
      $form['lang_web_url'.$lang['lang_id']] = '';
      $form['lang_check_in'.$lang['lang_id']] = '';
      $form['lang_check_out'.$lang['lang_id']] = '';
    }
    $form['hidden_req'] = '';
    $form['web_public'] = 5;
    $form['depli_public'] = 1;
    // eventuale descrizione ampliata
    $form['large_descri'] = '';
    $form['paypal_email'] = '';
    $form['hype_transf'] ='';
    $form['stripe_pub_key'] = '';
    $form['stripe_sec_key'] = '';
    $form['check_in'] = "";
    $form['check_out'] = "";
    $form['week_check_in'] = "";
    $form['week_check_out'] = "";
    $form['minor'] = "";
    $form['tour_tax_from'] = "";
    $form['tour_tax_to'] = "";
    $form['open_from'] = "";
    $form['open_to'] = "";
    $form['tour_tax_day'] = 0;
    $form['max_booking_days'] = 0;
    $form['ref_ecommerce_id_main_product']="";
    $form['id_artico_group'] = "";
    $form['lat'] = "";
    $form['long'] = "";
    $form['cin'] = "";
    $form['csmt'] = "";
    $cl_home="active";
    $cl_home_tab="in active";
    $cl_variant="";
    $cl_variant_tab="";
}

/** ENRICO FEDELE */
/* Solo se non sono in finestra modale carico il file di lingua del modulo */
if ($modal === false) {
    require("../../library/include/header.php");
    $script_transl = HeadMain(0, array('calendarpopup/CalendarPopup','custom/autocomplete'));

} else {
    $script = basename($_SERVER['PHP_SELF']);
    require("../../language/" . $admin_aziend['lang'] . "/menu.inc.php");
    require("../../modules/magazz/lang." . $admin_aziend['lang'] . ".php");
    if (isset($script)) { // se è stato tradotto lo script lo ritorno al chiamante
        $script_transl = $strScript[$script];
    }

    $script_transl = $strCommon + $script_transl;
}
if (intval($form['id_artico_group'])>0){
$query = "SELECT codice, descri FROM " . $gTables['artico'] . " WHERE id_artico_group = '".$form['id_artico_group']."'";
$arts = gaz_dbi_query($query);
}
?>
<script>
function itemErase(id,descri,group){
	$(".compost_name").append(descri);

	$("#confirm_erase").dialog({
		modal: true,
		show: "blind",
		hide: "explode",
		buttons: {
			No: function() {
				$(".compost_name").empty();
				$( this ).dialog( "close" );
			},
			Togli: function() {
				window.location.href = 'admin_facility.php?delete='+id+'&group='+group;
			}

		  },
		  close: function(){
			$(".compost_name").empty();
		  }
		});
}
function groupErase(group,descri){
	$(".group_name").append(group+' '+descri);

	$("#confirm_destroy").dialog({
		modal: true,
		show: "blind",
		hide: "explode",
		buttons: {
			No: function() {
				$(".group_name").empty();
				$( this ).dialog( "close" );
			},
			Togli: function() {
				window.location.href = 'admin_facility.php?group_delete='+group;
			}

		  },
		  close: function(){
			$(".group_name").empty();
		  }
		});
}
$(function () {
$(".DateTextBox.NoYear").datepicker();
$(".DateTextBox.NoYear").datepicker("option", "dateFormat", "dd-mm");
$("#datepicker_from").datepicker("setDate", "<?php echo $form['tour_tax_from']; ?>");
$("#datepicker_to").datepicker("setDate", "<?php echo $form['tour_tax_to']; ?>");
$("#datepicker_open_from").datepicker("setDate", "<?php echo $form['open_from']; ?>");
$("#datepicker_open_to").datepicker("setDate", "<?php echo $form['open_to']; ?>");
});
</script>
<style type="text/css">
.ui-datepicker-year
{
 display:none;
}
</style>
<form method="POST" name="form" enctype="multipart/form-data" id="add-product">
	<?php
	if (!empty($form['descri'])) $form['descri'] = htmlentities($form['descri'], ENT_QUOTES);
	if ($modal === true) {
		echo '<input type="hidden" name="mode" value="modal" />
			  <input type="hidden" name="mode-act" value="submit" />';
	}
	echo '<input type="hidden" name="ritorno" value="' . $form['ritorno'] . '" />';
	echo '<input type="hidden" name="ref_ecommerce_id_main_product" value="' . $form['ref_ecommerce_id_main_product'] . '" />';

	if ($modal_ok_insert === true) {
		echo '<div class="alert alert-success" role="alert">' . $script_transl['modal_ok_insert'] . '</div>';
		echo '<div class=" text-center"><button class="btn btn-lg btn-default" type="submit" name="none">' . $script_transl['iterate_invitation'] . '</button></div>';
	} else {
	   $gForm = new magazzForm();
		/** ENRICO FEDELE */
		/* Se sono in finestra modale, non visualizzo questo titolo */
		$changesubmit = '';
		if ($modal === false) {
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
		}
    echo '<input type="hidden" name="hidden_req" value="' . $form['hidden_req'] . '" />';
		echo '<input type="hidden" name="' . ucfirst($toDo) . '" value="" />';
		if (count($msg['err']) > 0) { // ho un errore
			$gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
		}
		if (isset($_SESSION['ok_ins'])){
			$gForm->toast('L\'alloggio ' . $_SESSION['ok_ins'].' è stato inserito con successo, sotto per modificarlo. Oppure puoi: <a class="btn btn-info" href="admin_house.php?Insert">Inserire uno nuovo alloggio</a> ' , 'alert-last-row', 'alert-success');
			unset($_SESSION['ok_ins']);
		}
		if ($toDo == 'insert') {
			echo '<div class="text-center"><h3>' . $script_transl['ins_this'] . '</h3></div>';
		} else {
			echo '<div class="text-center"><h3>' . $script_transl['upd_this'] . ' ' . $form['id_artico_group'] . '</h3></div>';
		}
		?>
		<div class="panel panel-warning gaz-table-form"><p><?php echo $script_transl['info']; ?> </p></div>
			<div class="panel panel-default gaz-table-form div-bordered">
				<div class="container-fluid">
					<ul class="nav nav-pills">
						<li class="<?php echo $cl_home;?>"><a data-toggle="pill" href="#home"><?php echo $script_transl['home']; ?></a></li>
						<li class="<?php echo $cl_variant;?>"><a data-toggle="pill" href="#variant"><?php echo $script_transl['variant']; ?></a></li>
						<li style="float: right;"><?php echo '<input name="Submit" type="submit" class="btn btn-warning" value="' . ucfirst($script_transl[$toDo]) . '" />'; ?></li>
            <li class="">
              <?php
              $gForm->selectLanguage('lang_id', $form['lang_id'],false,'lang-select','refresh_language');
              ?>
            </li>
					</ul>
					<div class="tab-content">
						<div id="home" class="tab-pane fade <?php echo $cl_home_tab;?>">
							<?php if ($toDo !== 'insert'){?>
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<label for="codice" class="col-sm-4 control-label"><?php echo $script_transl['codice']; ?></label>
										<input class="col-sm-4" type="text" value="<?php echo $form['id_artico_group']; ?>" name="id_artico_group" id="id_artico_group" maxlength="9" tabindex="1" readonly="readonly"/>
									</div>
								</div>
							</div><!-- chiude row  -->
							<?php } else {
								echo '<input type="hidden" name="id_artico_group" value="" />';
							}?>
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<label for="descri" class="col-sm-4 control-label"><?php echo $script_transl['descri']; ?></label>

<?php
                      if ($form['lang_id']>1) {
                        ?>
                        <input class="col-xs-12 col-md-8" type="text" value="<?php echo $form['lang_descri'.$form['lang_id']]; ?>" name="lang_descri<?php echo $form['lang_id']; ?>" maxlength="255" id="suggest_descri_artico" />
                        <input type="hidden" value="<?php echo $form['descri']; ?>" name="descri" />
                        <?php
                         foreach($langs as $lang){
                           if (intval($lang['lang_id'])==1){ continue;}
                           if ($lang['lang_id']==$form['lang_id']){
                             continue;
                           }
                           ?>
                          <input type="hidden" value="<?php echo $form['lang_descri'.$lang['lang_id']]; ?>" name="lang_descri<?php echo $lang['lang_id']; ?>" />
                          <?php
                         }
                      } else {

                        ?>
                        <input class="col-xs-12 col-md-8" type="text" value="<?php echo $form['descri']; ?>" name="descri" maxlength="255" id="suggest_descri_artico" />
                        <?php
                         foreach($langs as $lang){
                           if (intval($lang['lang_id'])==1){ continue;}
                           ?>
                          <input type="hidden" value="<?php echo $form['lang_descri'.$lang['lang_id']]; ?>" name="lang_descri<?php echo $lang['lang_id']; ?>" />
                          <?php
                         }
                      }

                    ?>
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
							<!--+ DC - 06/02/2019 div class="row" --->
							<div id="bodyText" class="row IERincludeExcludeRow">
								<div class="col-md-12">
									<div class="form-group">
										<label for="large_descri" class="col-sm-4 control-label"><?php echo $script_transl['body_text']; ?></label>
										<div class="col-sm-8">

<?php
                        if ($form['lang_id']>1) {
                          ?>
                          <textarea id="lang_bodytext" name="lang_bodytext<?php echo $form['lang_id']; ?>" class="mceClass"><?php echo $form['lang_bodytext'.$form['lang_id']]; ?></textarea>
                          <input type="hidden" value="<?php echo filter_var($form['large_descri'], FILTER_SANITIZE_FULL_SPECIAL_CHARS); ?>" name="large_descri" />
                          <?php
                          foreach($langs as $lang){
                            if (intval($lang['lang_id'])==1){ continue;}
                             if ($lang['lang_id']==$form['lang_id']){
                               continue;
                             }
                             ?>
                             <input type="hidden" value="<?php echo filter_var($form['lang_bodytext'.$lang['lang_id']], FILTER_SANITIZE_FULL_SPECIAL_CHARS); ?>" name="lang_bodytext<?php echo $lang['lang_id']; ?>" />
                             <?php
                          }
                        } else {
                          ?>
                          <textarea id="body_text" name="large_descri" class="mceClass"><?php echo $form['large_descri']; ?></textarea>
                          <?php
                          foreach($langs as $lang){
                            if (intval($lang['lang_id'])==1){ continue;}
                            ?>
                            <input type="hidden" value="<?php echo filter_var($form['lang_bodytext'.$lang['lang_id']], FILTER_SANITIZE_FULL_SPECIAL_CHARS); ?>" name="lang_bodytext<?php echo $lang['lang_id']; ?>" />
                            <?php
                          }
                        }
                      ?>
										</div>
									</div>
								</div>
							</div><!-- chiude row  -->
							<!--+ DC - 06/02/2019 div class="row" --->
							<div id="image" class="row IERincludeExcludeRow">
								<div class="col-md-12">
									<div class="form-group">
										<label for="image" class="col-sm-4 control-label"><img src="../root/view.php?table=artico_group&value=<?php echo $form['id_artico_group']; ?>&field=id_artico_group" width="100" >*</label>
										<div class="col-sm-8"><?php echo $script_transl['image']; ?><input type="file" name="userfile" /></div>
									</div>
								</div>
							</div><!-- chiude row  -->
							<!--+ DC - 06/02/2019 div class="row" --->
							<div id="refEcommercIdProduct" class="row IERincludeExcludeRow">
								<div class="col-md-12">
									<div class="form-group">
										<label for="ref_ecommerce_id_product" class="col-sm-4 control-label">ID riferimento e-commerce</label>
										<input class="col-sm-4" type="text" value="<?php echo $form['ref_ecommerce_id_main_product']; ?>" name="ref_ecommerce_id_main_product" maxlength="15" />
									</div>
								</div>
							</div><!-- chiude row  -->
							<!--+ DC - 06/02/2019 div class="row" --->
							<div id="webUrl" class="row IERincludeExcludeRow">
							<div class="col-md-12">
								<div class="form-group">
									<label for="web_url" class="col-sm-4 control-label"><?php echo $script_transl['web_url']; ?></label>
									<?php
                    if ($form['lang_id']>1) {
                      ?>
                      <input class="col-xs-12 col-md-8" type="text" value="<?php echo $form['lang_web_url'.$form['lang_id']]; ?>" name="lang_web_url<?php echo $form['lang_id']; ?>" maxlength="255" id="suggest_descri_artico" />
                      <input type="hidden" value="<?php echo $form['web_url']; ?>" name="web_url" maxlength="255" />
                      <?php
                       foreach($langs as $lang){
                         if ($lang['lang_id']==$form['lang_id']){
                           continue;
                         }
                         ?>
                        <input type="hidden" value="<?php echo $form['lang_web_url'.$lang['lang_id']]; ?>" name="lang_web_url<?php echo $lang['lang_id']; ?>" />
                        <?php
                       }
                    } else {
                      ?>
                      <input class="col-xs-12 col-md-8" type="text" value="<?php echo $form['web_url']; ?>" name="web_url" maxlength="255" id="suggest_web_url" />
                      <?php
                       foreach($langs as $lang){
                         ?>
                        <input type="hidden" value="<?php echo $form['lang_web_url'.$lang['lang_id']]; ?>" name="lang_web_url<?php echo $lang['lang_id']; ?>" />
                        <?php
                       }
                    }
                  ?>
								</div>
							</div>
							</div><!-- chiude row  -->
              <div id="webUrl" class="row IERincludeExcludeRow">
							<div class="col-md-12">
								<div class="form-group">
									<label for="paypal_email" class="col-sm-4 control-label">Eventuale e-mail account PayPal</label>
									<input class="col-sm-8" type="text" value="<?php echo $form['paypal_email']; ?>" name="paypal_email" maxlength="60" />
								</div>
							</div>
							</div><!-- chiude row  -->
              <div id="webUrl" class="row IERincludeExcludeRow">
							<div class="col-md-12">
								<div class="form-group">
									<label for="hype_transf" class="col-sm-4 control-label">Eventuale telefono trasferimento Hyper</label>
									<input class="col-sm-8" type="text" value="<?php echo $form['hype_transf']; ?>" name="hype_transf" maxlength="60" />
								</div>
							</div>
							</div><!-- chiude row  -->
              <div id="webUrl" class="row IERincludeExcludeRow">
							<div class="col-md-12">
								<div class="form-group">
									<label for="stripe_pub_key" class="col-sm-4 control-label">Eventuale publicable Stripe API key</label>
									<input class="col-sm-8" type="text" value="<?php echo $form['stripe_pub_key']; ?>" name="stripe_pub_key" maxlength="60" />
								</div>
							</div>
							</div><!-- chiude row  -->
              <div id="webUrl" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="stripe_sec_key" class="col-sm-4 control-label">Eventuale secrete Stripe key</label>
                    <input class="col-sm-8" type="password" value="<?php echo $form['stripe_sec_key']; ?>" name="stripe_sec_key" maxlength="60" />
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="check-in" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="check-in" class="col-sm-4 control-label">Orario check-in&nbsp;<i class="glyphicon glyphicon-flag" title="accetta tag lingue (<it></it>)"></i></label>
                    <?php
                      if ($form['lang_id']>1) {
                        ?>
                        <input class="col-sm-8" type="text" value="<?php echo $form['lang_check_in'.$form['lang_id']]; ?>" name="lang_check_in<?php echo $form['lang_id']; ?>" maxlength="90" id="suggest_descri_artico" />
                        <input type="hidden" value="<?php echo $form['check_in']; ?>" name="check_in" maxlength="90" />
                        <?php
                         foreach($langs as $lang){
                           if ($lang['lang_id']==$form['lang_id']){
                             continue;
                           }
                           ?>
                          <input type="hidden" value="<?php echo $form['lang_check_in'.$lang['lang_id']]; ?>" name="lang_check_in<?php echo $lang['lang_id']; ?>" />
                          <?php
                         }
                      } else {
                        ?>
                        <input class="col-sm-8" type="text" value="<?php echo $form['check_in']; ?>" name="check_in" maxlength="90" />
                        <?php
                         foreach($langs as $lang){
                           ?>
                          <input type="hidden" value="<?php echo $form['lang_check_in'.$lang['lang_id']]; ?>" name="lang_check_in<?php echo $lang['lang_id']; ?>" />
                          <?php
                         }
                      }
                    ?>
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="week-check-in" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="check-in" class="col-sm-4 control-label">Giorno check-in (vuoto=sempre ammesso. Inserire, separati dalla virgola (es. 1,7), i giorni numerici della settimana in cui è ammesso (0=domenica 1=lunedì etc.)&nbsp;</i></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['week_check_in']; ?>" name="week_check_in" maxlength="90"  oninput="this.value = this.value.replace(/[^0-6,]/g, '').replace(/(\..*)\./g, '$1');"/>
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="check-out" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="check-out" class="col-sm-4 control-label">Orario check-out&nbsp;<i class="glyphicon glyphicon-flag" title="accetta tag lingue (<it></it>)"></i></label>
                    <?php
                      if ($form['lang_id']>1) {
                        ?>
                        <input class="col-sm-8" type="text" value="<?php echo $form['lang_check_out'.$form['lang_id']]; ?>" name="lang_check_out<?php echo $form['lang_id']; ?>" maxlength="90" id="suggest_descri_artico" />
                        <input type="hidden" value="<?php echo $form['check_out']; ?>" name="check_out" maxlength="90" />
                        <?php
                         foreach($langs as $lang){
                           if ($lang['lang_id']==$form['lang_id']){
                             continue;
                           }
                           ?>
                          <input type="hidden" value="<?php echo $form['lang_check_out'.$lang['lang_id']]; ?>" name="lang_check_out<?php echo $lang['lang_id']; ?>" />
                          <?php
                         }
                      } else {
                        ?>
                        <input class="col-sm-8" type="text" value="<?php echo $form['check_out']; ?>" name="check_out" maxlength="90" />
                        <?php
                         foreach($langs as $lang){
                           ?>
                          <input type="hidden" value="<?php echo $form['lang_check_out'.$lang['lang_id']]; ?>" name="lang_check_out<?php echo $lang['lang_id']; ?>" />
                          <?php
                         }
                      }
                    ?>

                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="week-check-out" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="check-in" class="col-sm-4 control-label">Giorno check-out (vuoto=sempre ammesso. Inserire, separati dalla virgola, i giorni numerici della settimana in cui è ammesso (0=domenica, 1=lunedì etc.)&nbsp;</i></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['week_check_out']; ?>" name="week_check_out" maxlength="90"  oninput="this.value = this.value.replace(/[^0-6,]/g, '').replace(/(\..*)\./g, '$1');"/>
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="limit-open-from" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="limit-open-from" class="col-sm-4 control-label">Apertura dal (vuoto = sempre aperto)</label>

                    <input type="text" id="datepicker_open_from" class="col-sm-8 DateTextBox NoYear" name="open_from" />
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="limit-open-to" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="limit-open-to" class="col-sm-4 control-label">Apertura fino al</label>

                    <input type="text" id="datepicker_open_to" class="col-sm-8 DateTextBox NoYear" name="open_to" />
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="minor" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="minor" class="col-sm-4 control-label">Età massima bambini/minorenni</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['minor']; ?>" name="minor" maxlength="2" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" />
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="limit-tour-tax-from" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="limit-tour-tax-from" class="col-sm-4 control-label">Tassa turistica a partire dal</label>

                    <input type="text" id="datepicker_from" class="col-sm-8 DateTextBox NoYear" name="tour_tax_from" />
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="limit-tour-tax-to" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="limit-tour-tax-to" class="col-sm-4 control-label">Tassa turistica fino al</label>

                    <input type="text" id="datepicker_to" class="col-sm-8 DateTextBox NoYear" name="tour_tax_to" />
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="limit-tour-tax-day" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="limit-tour-tax-day" class="col-sm-4 control-label">Tassa turistica per un massimo di giorni (0 = senza limiti)</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['tour_tax_day']; ?>" name="tour_tax_day" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" />
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="limit-booking-days" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="limit-booking-days" class="col-sm-4 control-label">Limite di notti per ciascuna prenotazione(0 = senza limiti)</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['max_booking_days']; ?>" name="max_booking_days" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" />
                  </div>
                </div>
							</div><!-- chiude row  -->
							<!--+ DC - 06/02/2019 div class="row" --->
<!--
							<div id="depliPublic" class="row IERincludeExcludeRow">
								<div class="col-md-12">
									<div class="form-group">
										<label for="depli_public" class="col-sm-4 control-label"><?php echo $script_transl['depli_public']; ?></label>
                  <?php
                  /*
                  $gForm->variousSelect('depli_public', $script_transl['depli_public_value'], $form['depli_public'], "col-sm-8", true, '', false, 'style="max-width: 200px;"');
                  */?>
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
              <div id="lat" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="lat" class="col-sm-4 control-label">Ubicazione, latitudine (numero decimale)</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['lat']; ?>" name="lat" maxlength="19" min="0" max="19" oninput="this.value = this.value.replace(/[^0-9.-]/g, '').replace(/(\..*)\./g, '$1');" />
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="long" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="long" class="col-sm-4 control-label">Ubicazione, longitudine (numero decimale)</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['long']; ?>" name="long" maxlength="19" min="0" max="19" oninput="this.value = this.value.replace(/[^0-9.-]/g, '').replace(/(\..*)\./g, '$1');" />
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="cin" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="cin" class="col-sm-4 control-label">CIN(Codice Identificativo Nazionale)</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['cin']; ?>" name="cin" maxlength="18" min="0" max="19"  />
                  </div>
                </div>
							</div><!-- chiude row  -->
              <div id="csmt" class="row IERincludeExcludeRow">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="csmt" class="col-sm-4 control-label">Codice struttura per movimentazione turistica</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['csmt']; ?>" name="csmt" maxlength="18" min="0" max="19"  />
                  </div>
                </div>
							</div><!-- chiude row  -->
							</div><!-- chiude tab-pane  -->

							<div id="variant" class="tab-pane fade <?php echo $cl_variant_tab;?>">
								<div class="container-fluid">
								<?php $color='eeeeee';

								echo '<ul class="col-xs-12 col-sm-12 col-md-11 col-lg-10">';
								$v=0;
								if (isset($arts)){
								while ($art = $arts->fetch_assoc()) {

									$icona=(is_array($art['codice']))?'<a class="btn btn-xs btn-warning collapsible" id="'.$art['codice'].'" data-toggle="collapse" data-target=".' . $art['codice'] . '"><i class="glyphicon glyphicon-list"></i></a>':'';
									echo '<div style="background-color: #'.$color.'">
									<a class="btn btn-xs btn-success" href="admin_artico.php?Update&amp;codice=' . $art['codice'] . '">'.$art['codice'].'</a> - '.$art['descri'].' '.$icona.' _ _ _ _ ';
									if (intval($arts->num_rows)>1){
										echo '<a class="btn btn-xs btn-danger" onclick="itemErase(\''.addslashes($art['codice']).'\', \''.addslashes($art['descri']).'\', \''.addslashes($form['id_artico_group']).'\');">  togli X </a>';
									}
									echo '</div>';
									$color=($color=='fcfcfc')?'eeeeee':'fcfcfc';
									echo '<input type="hidden" name="variant['.$v.']" value="' . $art['codice'] . '" />';
									$v++;
								}
								}
								?>
								</ul>
								<div class="col-xs-12 col-md-6">Nuovo alloggio:
									<?php
									$select_artico = new selectartico("codart");
									$select_artico->addSelected($form['codart']);
                  $select_artico->output($form['cosear'], " AND custom_field LIKE '%vacation_rental\":%' ");
									//$select_artico->output(substr($form['cosear'], 0,32),'C',"");
									?>
								</div>
								<div class="col-xs-12 col-md-2">
									<input type="submit" class="btn btn-warning" name="OKsub" value="Salva">
								</div>
							</div>
						</div>

					</div><!-- chiude tab-pane  -->

				<div class="col-sm-12">
					<?php
					/** ENRICO FEDELE */
					/* SOlo se non sono in finestra modale */
					if ($modal === false) {
						echo '<div class="col-sm-4 text-left"><input name="none" type="submit" value="" disabled></div>';
					}
					?>
					<div class="col-md-12">
						<div class="col-sm-6 text-center">
							<a class="btn btn-xs btn-danger" onclick="groupErase('<?php echo addslashes($form['id_artico_group']); ?>','<?php echo addslashes($form['descri']); ?>')">  Elimina </a>
						</div>
						<div class="col-sm-6 text-center">
							<input name="Submit" type="submit" class="btn btn-warning" value="<?php echo ucfirst($script_transl[$toDo]);?>" />
						</div>
					</div>
					<?php
				}
				?>
				</div>
			</div> <!-- chiude container -->
		</div><!-- chiude panel -->
</form>
<div class="modal" id="confirm_erase" title="Togli questo alloggio dalla struttura">
    <fieldset>
       <div class="compost_name"></div>
    </fieldset>
</div>
<div class="modal" id="confirm_destroy" title="Distruggi questa struttura">
    <fieldset>
       <div class="group_name"></div>
    </fieldset>
<p>NB: Eliminerai anche i collegamenti alle varianti</p>
</div>
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
                url: "../../modules/magazz/admin_group.php",
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
