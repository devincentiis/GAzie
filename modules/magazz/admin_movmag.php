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
  scriva   alla   Free  Software Foundation,  Inc.,   59
  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
require("../../library/include/datlib.inc.php");
require ("../../modules/vendit/lib.function.php");
require ("../../modules/camp/lib.function.php");

$admin_aziend = checkAdmin();
$msg = "";
$warnmsg = "";
$lm = new lotmag;
$gForm = new magazzForm;
$sil= new silos();
$show_artico_composit = gaz_dbi_get_row($gTables['company_config'], 'var', 'show_artico_composit');
$tipo_composti = gaz_dbi_get_row($gTables['company_config'], 'var', 'tipo_composti');

if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}

$form=[];
function getItemPrice($item, $partner = 0) {
    global $admin_aziend, $gTables;
    $artico = gaz_dbi_get_row($gTables['artico'], 'codice', $item);
    if ($partner > 0) {
        $partner = gaz_dbi_get_row($gTables['clfoco'], 'codice', $partner);
        $list = $partner['listin'];
        if (substr($partner['codice'], 0, 3) == $admin_aziend['mascli'] && $list > 0 && $list <= 3) {
            $price = ($artico)?$artico["preve$list"]:0.00;
        } else {
            $price = ($artico)?$artico["preacq"]:0.00;
        }
        $sconto = $partner['sconto'];
    } else { // prezzo articolo
        $sconto = 0;
        $price = ($artico)?$artico["preve1"]:0.00;
    }
    return CalcolaImportoRigo(1, $price, $sconto, $admin_aziend['decimal_price']);
}

if ((isset($_POST['Update'])) || ( isset($_GET['Update']))) {
    if (!isset($_GET['id_mov'])) {
        header("Location: " . $_POST['ritorno']);
        exit;
    } else {
        $_POST['id_mov'] = intval($_GET['id_mov']);
    }
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
  $form['hidden_req'] = '';
  //recupero il movimento
  $result = gaz_dbi_get_row($gTables['movmag'], "id_mov", $_GET['id_mov']);
  $form['id_mov'] = $result['id_mov'];
	$form['type_mov'] = $result['type_mov'];
  $form['id_rif'] = $result['id_rif'];
  $form['caumag'] = $result['caumag'];
  $form['operat'] = $result['operat'];
  $form['gioreg'] = substr($result['datreg'], 8, 2);
  $form['mesreg'] = substr($result['datreg'], 5, 2);
  $form['annreg'] = substr($result['datreg'], 0, 4);
  $form['clfoco'] = $result['clfoco'];
  if (!empty($form['caumag'])) { //controllo quale partner prevede la causale
      $rs_causal = gaz_dbi_get_row($gTables['caumag'], "codice", $form['caumag']);
      $form['clorfo'] = $rs_causal['clifor']; //cliente, fornitore o entrambi
  } else {
      $form['clorfo'] = 0; // entrambi
  }
  $form['tipdoc'] = $result['tipdoc'];
  $form['desdoc'] = $result['desdoc'];
  $form['scochi'] = $result['scochi'];
  $form['giodoc'] = substr($result['datdoc'], 8, 2);
  $form['mesdoc'] = substr($result['datdoc'], 5, 2);
  $form['anndoc'] = substr($result['datdoc'], 0, 4);
	$form['id_lotmag'] = $result['id_lotmag'];
  $form['artico'] = $result['artico'];
	$form['cosear'] = $result['artico'];
	$item_artico = gaz_dbi_get_row($gTables['artico'], "codice", $form['artico']);
	$print_unimis =  $item_artico['unimis'];
	$print_uniacq =  $item_artico['uniacq'];
	$form['SIAN']= $item_artico['SIAN'];
	if (intval($form['SIAN'])>0) { // se movimenta il SIAN carico anche il contenuto relativo al SIAN
		$camp_artico = gaz_dbi_get_row($gTables['camp_artico'], "codice", $form['cosear']);
	}
	$resultsian = gaz_dbi_get_row($gTables['camp_mov_sian'], "id_movmag", intval($_GET['id_mov']));
  $form['recip_stocc'] = '';
	if ($resultsian){
		$form['cod_operazione'] = $resultsian['cod_operazione'];
		$form['recip_stocc'] = $resultsian['recip_stocc'];
		$form['recip_stocc_destin'] = $resultsian['recip_stocc_destin'];
	}
	if ($item_artico['lot_or_serial']==1){
		$result_lotmag = gaz_dbi_get_row($gTables['lotmag'], "id", $result['id_lotmag']);
		$form['identifier'] = $result_lotmag['identifier'];
		$form['expiry'] = $result_lotmag['expiry'];
	}
	$form['lot_or_serial']=$item_artico['lot_or_serial'];
  // Antonio Germani - se è presente, recupero il file documento lotto
  $form['filename'] = "";
  if (file_exists(DATA_DIR.'files/' . $admin_aziend['company_id']) > 0) {
      // recupero il filename dal filesystem
      $dh = opendir(DATA_DIR.'files/' . $admin_aziend['company_id']);
      while (false !== ($filename = readdir($dh))) {
          $fd = pathinfo($filename);
          $r = explode('_', $fd['filename']);
          if ($r[0] == 'lotmag' && $r[1] == $result['id_lotmag']) {
              // riassegno il nome file
              $form['filename'] = $fd['basename'];
          }
      }
  }
  $form['quanti'] = gaz_format_quantity($result['quanti'], 0, $admin_aziend['decimal_quantity']);
  $form['prezzo'] = number_format($result['prezzo'], $admin_aziend['decimal_price'], '.', '');
  $form['scorig'] = $result['scorig'];
  $form['status'] = $result['status'];
  $form['search_partner'] = "";

	if ($toDo == "update") { // se è un update prendo la quantità scritta nel data base per le disponibilità in uscita
		$prev_qta = gaz_dbi_get_row($gTables['movmag'], "id_mov", intval($_GET['id_mov']));
		// la qtà è in questa variabile $prev_qta['quanti'];
		$prev_idmov = intval($_GET['id_mov']);
	} else {
		$prev_qta['quanti']=0;
    $prev_idmov = 0;
	}
  $form['id_orderman'] = $result['id_orderman'];
  $resultorderman = gaz_dbi_get_row($gTables['orderman'], "id", $form['id_orderman']);
  if ($form['id_orderman'] > 0) {
    $form['coseprod']=$resultorderman['description'];
  } else {
    $form['coseprod']='';
  }
  $form['id_position'] = $result['id_artico_position']!== NULL ? $result['id_artico_position'] : 0;
  $resultposition = gaz_dbi_get_row($gTables['artico_position'], 'id_position', $form['id_position']);
  if ($form['id_position'] > 0) {
    $form['cosepos']=$resultposition['id_position'];
  } else {
    $form['cosepos']='';
  }

} elseif (isset($_POST['Insert']) or isset($_POST['Update'])) {   //      se non e' il primo accesso

	$form['hidden_req'] = htmlentities($_POST['hidden_req']);
	//ricarico i registri per il form facendo gli eventuali parsing
	$form['id_mov'] = intval($_POST['id_mov']);
	//$form['type_mov'] = intval ($_POST['type_mov']);
	$form['id_rif'] = intval($_POST['id_rif']);
	$form['caumag'] = intval($_POST['caumag']);
	$form['gioreg'] = intval($_POST['gioreg']);
	$form['mesreg'] = intval($_POST['mesreg']);
	$form['annreg'] = intval($_POST['annreg']);
	$form['clfoco'] = intval($_POST['clfoco']);
	$form['clorfo'] = intval($_POST['clorfo']); //cliente, fornitore o entrambi
	if ($form['caumag']>97){
		$form['tipdoc']="INV";
	} else {
		$form['tipdoc']=substr($_POST['tipdoc'],0,3);
  }
	$form['desdoc'] = substr($_POST['desdoc'], 0, 50);
	$form['giodoc'] = intval($_POST['giodoc']);
	$form['mesdoc'] = intval($_POST['mesdoc']);
	$form['anndoc'] = intval($_POST['anndoc']);
	$form['scochi'] = floatval(preg_replace("/\,/", '.', $_POST['scochi']));
	$form['id_lotmag'] = (isset($_POST['id_lotmag_change']))?$_POST['id_lotmag_change']:$_POST['id_lotmag'];
	$form['cosear'] = $_POST['cosear'];
	$form['artico'] = $_POST['artico'];
	$form['lot_or_serial']=$_POST['lot_or_serial'];
	$form['SIAN']=$_POST['SIAN'];
	$form['cod_operazione'] = $_POST['cod_operazione'];
	$form['recip_stocc'] = $_POST['recip_stocc'];
	$form['recip_stocc_destin'] = $_POST['recip_stocc_destin'];
	$form['coseprod']= $_POST['coseprod'];
	$form['cosepos']= $_POST['cosepos'];
	$form['id_orderman'] = intval($_POST['id_orderman']);
	$form['id_position'] = intval($_POST['id_position']);
	if (isset($_POST['caumag']) && intval($_POST['caumag'])>0 && intval($form['caumag'])<80) {
		$causa = gaz_dbi_get_row($gTables['caumag'], "codice", $form['caumag']);
    $_POST['operat']= $causa['operat'];
    $form['clorfo'] = $causa['clifor']; //cliente, fornitore o entrambi
    if (($causa['clifor'] < 0 && substr($form['clfoco'], 0, 3) == $admin_aziend['masfor']) or ( $causa['clifor'] > 0 && 	substr($form['clfoco'], 0, 3) == $admin_aziend['mascli'])) {
        $form['clfoco'] = 0;
        $form['search_partner'] = "";
    }
    if ($causa['insdoc'] == 0) {//se la nuova causale non prevede i dati del documento
        $form['tipdoc'] = "MAG";
        $form['desdoc'] = "";
        $form['giodoc'] = date("d");
        $form['mesdoc'] = date("m");
        $form['anndoc'] = date("Y");
        $form['scochi'] = 0;
        $form['id_rif'] = 0;
    }
	}


	if (intval($_POST['caumag'])== 82){
		$form['operat'] = 1;
	} elseif (intval($_POST['caumag'])== 81){
		$form['operat'] = -1;
	} else {
		$form['operat'] = intval($_POST['operat']);
	}
	$print_unimis = "";
	$print_uniacq = "";
	if (strlen($form['cosear'])>0) { // se c'è un articolo carico l'unità di misura e se ci sono anche gli identificativi del lotto
		$item_artico = gaz_dbi_get_row($gTables['artico'], "codice", $form['cosear']);
		$print_unimis=($item_artico)?$item_artico['unimis']:'';
		$print_uniacq=($item_artico)?$item_artico['uniacq']:'';
		$form['SIAN']=($item_artico)?$item_artico['SIAN']:'';
		if (intval($form['SIAN'])>0) { // se movimenta il SIAN carico anche il contenuto relativo al SIAN
			$camp_artico = gaz_dbi_get_row($gTables['camp_artico'], "codice", $form['cosear']);
		}
		if (isset($_POST['expiry'])){
			$form['filename'] = $_POST['filename'];
			$form['identifier'] = $_POST['identifier'];
			$form['expiry'] = $_POST['expiry'];
		}
	}
	$form['quanti'] = gaz_format_quantity($_POST['quanti'], 0, $admin_aziend['decimal_quantity']);
	$form['prezzo'] = number_format(preg_replace("/\,/", '.', floatval($_POST['prezzo'])), $admin_aziend['decimal_price'], '.', '');
	$form['scorig'] = floatval(preg_replace("/\,/", '.', $_POST['scorig']));
	$form['status'] = substr($_POST['status'], 0, 10);
	$form['search_partner'] = $_POST['search_partner'];

	if (isset($_POST['newpartner'])) {
	  $anagrafica = new Anagrafica();
	  $partner = $anagrafica->getPartner($_POST['clfoco']);
	  $form['search_partner'] = substr($partner['ragso1'], 0, 4);
	  $form['clfoco'] = 0;
	}
	if (isset($_POST['newitem'])) {
	  $result_newart = gaz_dbi_get_row($gTables['artico'], "codice", $_POST['artico']);
	  $form['cosear'] = substr($result_newart['codice'], 0, 4);
	  $form['artico'] = "";
	}
	if (isset($_POST['Return'])) {
	  header("Location: " . $_POST['ritorno']);
	  exit;
	}
	if ($_POST['hidden_req'] == 'new_price') {
	  $form['prezzo'] = getItemPrice($form['artico'], $form['clfoco']);
	  $form['hidden_req'] = '';
	}

	if (!empty($_FILES['docfile_']['name'])) { // Antonio Germani - se c'è un nome in $_FILES
		$prefix = $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'];
		foreach (glob(DATA_DIR."files/tmp/" . $prefix . "_*.*") as $fn) { // prima cancello eventuali precedenti file temporanei
			unlink($fn);
		}
		$mt = substr($_FILES['docfile_']['name'], -3);
		if (($mt == "png" || $mt == "odt" || $mt == "peg" || $mt == "jpg" || $mt == "pdf") && $_FILES['docfile_']['size'] > 1000) { // se rispetta limiti e parametri lo salvo nella cartella tmp
			move_uploaded_file($_FILES['docfile_']['tmp_name'], DATA_DIR.'files/tmp/' . $prefix . '_' . $_FILES['docfile_']['name']);
			$form['filename'] = $_FILES['docfile_']['name'];
		} else {
			$msg.= "14+";
		}
	}
	if (intval($form['SIAN'])>0){
		$uldtfile=getLastSianDay();
		$datem=$form['anndoc'] . "-" . $form['mesdoc'] . "-" . $form['giodoc'];
		if (strtotime($datem) < strtotime($uldtfile)){
			$warnmsg.="33+";
		}
	}

	if ($toDo == "update") { // se è un update prendo la quantità scritta nel data base per le disponibilità in uscita
		$prev_qta = gaz_dbi_get_row($gTables['movmag'], "id_mov", intval($_GET['id_mov']));
		$prev_idmov = intval($_GET['id_mov']);
		// la qtà è in questa variabile $prev_qta['quanti'];
	} else {
		$prev_qta['quanti']=0;
		$prev_idmov = 0;
	}
	// controllo e WARNING su quantità e lotti
	if (strlen($form['artico'])>0 && $form['quanti']>0 && ($form['operat']==-1 || $form['operat']==0)){
		$mv = $gForm->getStockValue(false, $form['artico']);
		$magval = array_pop($mv); // controllo disponibilità in magazzino
		$magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
		if (number_format(($magval['q_g']+$prev_qta['quanti']), 5) < number_format($form['quanti'], 5)){
			$warnmsg.="34+";
		}
	}

	if ($form['lot_or_serial']==1){ // se articolo con lotti ...
		$form['datreg'] = $form['annreg'] . "-" . substr("0".$form['mesreg'],-2) . "-" . substr("0".$form['gioreg'],-2);
		if ($form['operat'] == -1){
			$lm -> getAvailableLots($form['artico'],$form['id_mov']);// prendo i lotti disponibili per l'articolo escludendo, se siamo in update, il movimento di magazzino in questione
			$tot=0;
			foreach ($lm->available as $v_lm) {// ciclo tutti i lotti disponibili
				$tot+=$v_lm['rest']; // sommo le quantità
			}

			if ($tot < $form['quanti']){ // se la quantità richiesta è maggiore alla giacenza totale dei lotti!
				$msg .= "35+";echo "Giacenza lotti: ",$tot," data reg.: ",$form['datreg'];
			}
		}
	}

  if ($_POST['hidden_req'] == 'caumag') {
    $cm = gaz_dbi_get_row($gTables['caumag'], "codice", $form['caumag']);
		$form['operat'] = $cm['operat'];
    if ($form['caumag']>80 || $toDo=="insert"){ // non deriva da un documento cambio la descrizione
      $form['desdoc'] = $cm['descri'];
    }
    $form['hidden_req']='';
  }

  if (!empty($_POST['Insert'])) { // se viene inviata la richiesta di conferma totale ...
    //formatto le date
    $form['datreg'] = $form['annreg'] . "-" . $form['mesreg'] . "-" . $form['gioreg'];
    $form['datdoc'] = $form['anndoc'] . "-" . $form['mesdoc'] . "-" . $form['giodoc'];
    $utsreg = mktime(0, 0, 0, $form['mesreg'], $form['gioreg'], $form['annreg']);
    $utsdoc = mktime(0, 0, 0, $form['mesdoc'], $form['giodoc'], $form['anndoc']);
    if (!checkdate($form['mesreg'], $form['gioreg'], $form['annreg'])) $msg .= "16+";
    if (!checkdate($form['mesdoc'], $form['giodoc'], $form['anndoc'])) $msg .= "15+";
    if ($utsdoc > $utsreg) { $msg .= "17+"; }
    if ($form['lot_or_serial']==1 && $form['caumag']<>99){ // se è un articolo con lotti e non è un movimento inventario
      if (strlen ($form['identifier'])<= 0 || intval($form['id_lotmag'])==0 ){
        $msg .= "21+"; // manca il lotto
      }else{
		  $checklot = gaz_dbi_get_row($gTables['lotmag']." LEFT JOIN ".$gTables['movmag']." ON ".$gTables['movmag'].".id_mov = id_movmag", 'id', $form['id_lotmag']);
		  if (isset($checklot['datdoc']) && isset($form['datdoc']) && strtotime($form['datdoc']) < strtotime($checklot['datdoc']) && $form['operat']=="-1"){// non può uscire un lotto prima della data della sua creazione
			$msg .= "36+";// Il lotto non può uscire in tale data in quanto ancora inesistente
		  }
	  }
    }
    if (intval($form['caumag']) < 1){ // senza causale
      $msg .= "37+";
    }
    if (intval($form['caumag'])==98 && intval($form['operat'])==0){ // su storno inventario bisogna indicare se entrata o uscita
      $msg .= "29+";
    }
    if (intval($form['caumag'])==99 && intval($form['operat'])!==1){ // inventario deve essere entrata
      $msg .= "30+";
    }
    if (intval($form['caumag'])==98 && $form['prezzo'] <= 0) { // Se storno inventario e non è stato dato un prezzo controllo precedente inventario
      $rs_last_inventory = gaz_dbi_dyn_query("*", $gTables['movmag'], "artico = '".$form['artico']."' AND caumag = 99 AND datreg <= '" . $form['datreg'] . "'", "datreg DESC, id_mov DESC");
      $last_inventory = gaz_dbi_fetch_array($rs_last_inventory);
      if ($last_inventory) {
        $form['prezzo'] = $last_inventory['prezzo'];// imposto il valore sulla base dell'ultimo inventario 99
      }
    }
    if (empty($form['artico'])) {  //manca l'articolo
      $msg .= "18+";
    }
    if ($form['operat']==1 && $item_artico['good_or_service']==2 && $tipo_composti['val']=="STD") {  //E' un articolo composto che non può essere caricato da movmag
            $msg .= "22+";
    }
    if ($form['quanti'] == 0) {  //la quantità è zero
      $msg .= "19+";
    }
    if (isset($_GET['id_mov']) && intval($_GET['id_mov'])>0){
      $result = gaz_dbi_get_row($gTables['movmag'], "id_mov", intval($_GET['id_mov']));
      if ($result['type_mov']<>0){ //Antonio Germani è un movimento che va gestito esclusivamente con il modulo Camp
        $msg .="20+";
      }
    }
    // inizio controllo operazioni particolari SIAN
    if ($form['SIAN']>0 && $form['operat']==1 && $form['cod_operazione']==10 && $camp_artico['confezione']>0){
      $msg .="23+";
    }
    if ($form['SIAN']>0 && $form['operat']==1 && $form['cod_operazione']==10 && $camp_artico['confezione']==0 && $form['recip_stocc']==""){
      $msg .="24+";
    }
    if ($form['SIAN']>0 && $form['operat']==-1 && $form['cod_operazione']==0 && $camp_artico['confezione']==0){
      $msg .="25+";
    }
    if ($form['SIAN']>0 && $form['cod_operazione']==11){
      $msg .="26+";
    }
    if ($form['SIAN']>0 && $form['operat']==-1 && $form['cod_operazione']==6 && $camp_artico['confezione']==0){
      $msg .="27+";
    }
    if ($form['SIAN']>0 && $form['operat']==-1 && $form['cod_operazione']==7 && $camp_artico['confezione']==0 && $form['recip_stocc']==""){
      $msg .="24+";
    }
    if ($form['SIAN']>0 && $form['operat']==-1 && $form['cod_operazione']==8 && $camp_artico['confezione']==0 && $form['recip_stocc']==""){
      $msg .="24+";
    }
    if ($form['SIAN']>0 && $form['operat']==-1 && $form['cod_operazione']==13 && $camp_artico['confezione']>0){
      $msg .="28+";
    }
    if ($form['SIAN']>0 && $form['operat']==-1 && strlen($form['recip_stocc'])>0){
      $content = $sil -> getCont($form['recip_stocc'],$form['artico'],$prev_idmov);
      if ($content < $form['quanti']){ // se non c'è suffiente olio nel silos selezionato
        $msg .="32+";
      }
    }
    if (empty($msg)) { // nessun errore

      $upd_mm = new magazzForm;
      // Antonio Germani - inizio salvataggio lotto
      if ($form['lot_or_serial']==1){ // se l'articolo prevede un lotto
        if (strlen($form['identifier']) == 0) { // se non è stato digitato un lotto lo inserisco d'ufficio come data e ora
          $form['identifier'] = date("Ymd Hms");
        }
        if (strlen($form['expiry']) == 0) { // se non c'è la scadenza la inserisco a zero d'ufficio
          $form['expiry'] = "0000-00-00 00:00:00";
        }
        $form['identifier'] = (empty($form['identifier'])) ? '' : filter_var($form['identifier'], FILTER_SANITIZE_FULL_SPECIAL_CHARS); // ne ripulisco il numero da caratteri dannosi
        // Vedo dove andrà salvato il movimento di magazzino in movmag $id_movmag
        if ($toDo=="update") {
          $id_movmag=intval($_GET['id_mov']);
        } else {
          $query = "SHOW TABLE STATUS LIKE '" . $gTables['movmag'] . "'";
          unset($row);
          $result = gaz_dbi_query($query);
          $row = $result->fetch_assoc();
          $id_movmag = $row['Auto_increment'];
        }

        if (($toDo=="insert" && $form['operat']==1) || (intval($form['id_lotmag']) == 0 && $toDo=="update" && $form['operat']==1)) { // se è insert OR se è update ma non c'era il lotto creo il rigo lotto memorizzandolo nella tabella lotmag
            $query = "SHOW TABLE STATUS LIKE '" . $gTables['lotmag'] . "'";
            unset($check_lot);
            $check_lot = gaz_dbi_query($query);
            $row = $check_lot->fetch_assoc();
            $form['id_lotmag'] = $row['Auto_increment']; // trovo l'ID che avrà il lotto e  salvo il lotto
            gaz_dbi_query("INSERT INTO " . $gTables['lotmag'] . "(codart,id_movmag,identifier,expiry) VALUES ('" . $form['artico'] . "','" . $id_movmag. "','" . $form['identifier'] . "','" . $form['expiry'] . "')");
        }
        if (intval($form['id_lotmag']) > 0 && $toDo=="update" && $form['operat']==1 ) { // se esiste il lotto e siamo in update lo modifico
          gaz_dbi_query("UPDATE " . $gTables['lotmag'] . " SET codart = '" . $form['artico'] . "' , identifier = '" . $form['identifier'] . "' , expiry = '" . $form['expiry'] . "' WHERE id = '" . $form['id_lotmag'] . "'");
        }

        // Antonio Germani - salvo documento/CERTIFICATO del lotto
        if (substr($form['filename'], 0, 7) <> 'lotmag_') { // se è stato cambiato il file, cioè il nome non inizia con lotmag e, quindi, anche se è un nuovo insert
          if (!empty($form['filename'])) { // e se ha un nome impostato nel form
            $tmp_file = DATA_DIR."files/tmp/" . $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $form['filename'];
            // sposto il file nella cartella definitiva, rinominandolo e cancellandolo dalla temporanea
            $fd = pathinfo($form['filename']);
            rename($tmp_file, DATA_DIR."files/" . $admin_aziend['company_id'] . "/lotmag_" . $form['id_lotmag'] . '.' . $fd['extension']);
          }
        } // altrimenti se il file non è cambiato, anche se è update, non faccio nulla
      } else {
        $form['id_lotmag']=0;
      }
      // fine salvataggio lotti
      $new_caumag = gaz_dbi_get_row($gTables['caumag'], "codice", $form['caumag']);
      if (!empty($form['artico'])) {
     		$position_warehouse = gaz_dbi_get_row($gTables['artico_position'], "id_position", $form['id_position']);
        $form['id_warehouse'] = $position_warehouse?$position_warehouse['id_warehouse']:0;
        if ($form['caumag']==99){ // nel caso in cui voglio aggiornare un movimento di inventario questo lo porto sempre a fine giornata ovvero con id_mov il più alto possibile, quindi cancello e reinserisco
			if (isset($_GET['id_mov'])){
				gaz_dbi_del_row($gTables['movmag'], 'id_mov', intval($_GET['id_mov']));
			}
			$id_movmag=$upd_mm->uploadMag($form['id_rif'], $form['tipdoc'],0,0,$form['datdoc'], $form['clfoco'], $form['scochi'], $form['caumag'], $form['artico'], $form['quanti'], $form['prezzo'], $form['scorig'], 0, $admin_aziend['stock_eval_method'], array('datreg' => $form['datreg'], 'operat' => $form['operat'], 'desdoc' => $form['desdoc']));
			$query = "UPDATE " . $gTables['movmag'] . " SET id_artico_position=".$form['id_position'].", id_warehouse=".$form['id_warehouse']."  WHERE id_mov =" . $id_movmag ;
			gaz_dbi_query($query);
			header("Location: " . $_POST['ritorno']);
			exit;
        } else {
          $id_movmag=$upd_mm->uploadMag($form['id_rif'], $form['tipdoc'],0,0,$form['datdoc'], $form['clfoco'], $form['scochi'], $form['caumag'], $form['artico'], $form['quanti'], $form['prezzo'], $form['scorig'], $form['id_mov'], $admin_aziend['stock_eval_method'], array('datreg' => $form['datreg'], 'operat' => $form['operat'], 'desdoc' => $form['desdoc'], 'id_artico_position' => $form['id_position']));
          if ($form['SIAN']>0 && $toDo=="insert"){
            $form['id_movmag']=$id_movmag;// imposto l'id mov mag e salvo il movimento del SIAN
            $form['varieta']=$item_artico['quality'];
            gaz_dbi_table_insert('camp_mov_sian', $form);
          }
          if ($form['SIAN']>0 && $toDo=="update") {
            // aggiorno il movimento del SIAN
            $update = array();
            $update[]="id_movmag";
            $update[]=$form['id_mov'];
            $form['varieta']=$item_artico['quality'];
            gaz_dbi_table_update('camp_mov_sian',$update,$form);
          }
          // aggiorno id_lotmag nel rigo di movmag
          $query = "UPDATE " . $gTables['movmag'] . " SET id_lotmag = " . $form['id_lotmag'] . ", id_orderman=".$form['id_orderman'].", id_artico_position=".$form['id_position'].", id_warehouse=".$form['id_warehouse']."  WHERE id_mov ='" . $id_movmag . "'";
          gaz_dbi_query($query);
        }
      }

      header("Location:report_movmag.php");
      exit;
    }
  }
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
  $form['hidden_req'] = '';
  //registri per il form della testata
  $form['id_mov'] = 0;
  $form['gioreg'] = date("d");
  $form['mesreg'] = date("m");
  $form['annreg'] = date("Y");
  $form['caumag'] = "";
  $form['operat'] = 0;
  $form['clfoco'] = 0;
  $form['clorfo'] = 0;
  $form['tipdoc'] = "MAG";
  $form['desdoc'] = "Movimento di magazzino";
  $form['giodoc'] = date("d");
  $form['mesdoc'] = date("m");
  $form['anndoc'] = date("Y");
  $form['scochi'] = 0;
  $form['artico'] = "";
	$form['lot_or_serial']="";
	$form['SIAN']="";
	$form['cod_operazione'] = 11;
	$form['recip_stocc'] = "";
	$form['recip_stocc_destin'] = "";
	$form['filename'] ="";
	$form['id_lotmag'] ="";
	$form['identifier'] ="";
	$form['expiry'] ="";
  $form['quanti'] = 0;
	$print_unimis = "";
  $form['prezzo'] = 0;
  $form['scorig'] = 0;
  $form['status'] = "";
  $form['search_partner'] = "";
  $form['cosear'] = "";
  $form['id_rif'] = 0;
  $form['id_orderman'] = 0;
	// dal custom field di admin_module relativo al magazzino trovo il magazzino di default
	$magmodule = gaz_dbi_get_row($gTables['module'], "name",'magazz');
	$magadmin_module = gaz_dbi_get_row($gTables['admin_module'], "moduleid",$magmodule['id']," AND adminid='{$admin_aziend['user_name']}' AND company_id=" . $admin_aziend['company_id']);
	$magcustom_field=json_decode($magadmin_module['custom_field']);
	$form["id_position"] = 0;
	$form['coseprod']="";
	$form['cosepos']="";
}

require("../../library/include/header.php");
$script_transl = HeadMain(0,array('custom/autocomplete',));
require("./lang." . $admin_aziend['lang'] . ".php");
if ($form['id_mov'] > 0) {
    $title = ucfirst($script_transl[$toDo] . $script_transl[0]) . " n." . $form['id_mov'];
} else {
    $title = ucfirst($script_transl[$toDo] . $script_transl[0]);
}
?>
<script>
function CalcolaImportoRigo()
{
  var p = document.myform.prezzo.value.toString().replace(/\,/g, '.') * 1;
  if (isNaN(p)) {
    p = 0;
  }
  var q = document.myform.quanti.value.toString().replace(/\,/g, '.') * 1;
  if (isNaN(q)) {
    q = 0;
  }
  var s = document.myform.scorig.value.toString().replace(/\,/g, '.') * 1;
  if (isNaN(s)) {
    s = 0;
  }
  var c = document.myform.scochi.value.toString().replace(/\,/g, '.') * 1;
  if (isNaN(c)) {
    c = 0;
  }
  var sommarigo = p * q - p * q * s / 100;
  var sommatotale = sommarigo - sommarigo * c / 100;
  return((Math.round(sommatotale * 100) / 100).toFixed(2));
}

$(function() {
  $( ".datepicker" ).datepicker({ dateFormat: 'yy-mm-dd' });
  $( ".submit_position" ).on( "click", function() {
 		var id = $(this).attr('val');
 		$("#search_position").val(id);
    $("#myform").submit();
  });
});
</script>

<?php
echo '<form method="POST" name="myform" id="myform" enctype="multipart/form-data" >';
echo "<input type=\"hidden\" name=\"" . ucfirst($toDo) . "\" value=\"\">\n";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $_POST['ritorno'] . "\">\n";
echo "<input type=\"hidden\" name=\"id_mov\" value=\"" . $form['id_mov'] . "\">\n";
echo "<input type=\"hidden\" name=\"id_rif\" value=\"" . $form['id_rif'] . "\">\n";
echo "<input type=\"hidden\" name=\"tipdoc\" value=\"" . $form['tipdoc'] . "\">\n";
echo "<input type=\"hidden\" name=\"status\" value=\"" . $form['status'] . "\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">$title</div>\n";
$importo_rigo = CalcolaImportoRigo($form['quanti'], $form['prezzo'], $form['scorig']);
$importo_totale = CalcolaImportoRigo(1, $importo_rigo, $form['scochi']);
?>
<div class="table-responsive">
<table class="Tmiddle table table-striped table-bordered table-condensed">
<?php

if (!empty($msg)) {
    $message = "";
    $rsmsg = array_slice(explode('+', chop($msg)), 0, -1);
    foreach ($rsmsg as $value) {
        $message .= $script_transl['error'] . "! -> ";
        $rsval = explode('-', chop($value));
        foreach ($rsval as $valmsg) {
            $message .= $script_transl[$valmsg] . " ";
        }
        $message .= "<br />";
    }
    echo '<tr><td colspan="5" class="FacetDataTDred">' . $message . "</td></tr>\n";
}
if (!empty($warnmsg)) {
    $message = "";
    $rsmsg = array_slice(explode('+', chop($warnmsg)), 0, -1);
    foreach ($rsmsg as $value) {
        $message .= $script_transl['warning'] . "! -> ";
        $rsval = explode('-', chop($value));
        foreach ($rsval as $valmsg) {
            $message .= $script_transl[$valmsg] . " ";
        }
        $message .= "<br />";
    }
    echo '<tr><td colspan="5" class="FacetDataTDred">' . $message . "</td></tr>\n";
}
echo "<tr><td>" . $script_transl[1] . "</td><td>\n";
echo "\t <select name=\"gioreg\" class=\"FacetSelect\" onchange=\"this.form.submit();\">\n";
for ($counter = 1; $counter <= 31; $counter++) {
    $selected = "";
    if ($counter == $form['gioreg'])
        $selected = "selected";
    echo "\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"mesreg\" class=\"FacetSelect\" onchange=\"this.form.submit();\">\n";
$gazTimeFormatter->setPattern('MMMM');
for ($counter = 1; $counter <= 12; $counter++) {
  $selected = "";
  if ($counter == $form['mesreg']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"annreg\" class=\"FacetSelect\" onchange=\"this.form.submit();\">\n";
for ($counter = date("Y") - 10; $counter <= date("Y") + 10; $counter++) {
    $selected = "";
    if ($counter == $form['annreg'])
        $selected = "selected";
    echo "\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select></td>\n";
echo "<td>" . $script_transl[2] . "</td><td>\n";
echo "<select name=\"caumag\" class=\"FacetSelect\" onchange=\"this.form.hidden_req.value='caumag'; this.form.submit();\" >\n";
echo "<option value=\"\">-------------</option>\n";
$result = gaz_dbi_dyn_query("*", $gTables['caumag'], " type_cau <> 1 ", "codice desc, descri asc"); // Carico le causali escludendo quelle del modulo CAMP
while ($row = gaz_dbi_fetch_array($result)) {
    $selected = "";
    if ($form["caumag"] == $row['codice']) {
        $selected = " selected ";
    }
    echo "<option value=\"" . $row['codice'] . "\"" . $selected . ">" . $row['codice'] . " - " . $row['descri'] . "</option>\n";
}
echo "<tr><td>" . $script_transl[3] . "&hArr;" . $script_transl[4] . "</td><td>\n";
$messaggio = "";
$ric_mastro = substr($form['clfoco'], 0, 3);
echo "\t<input type=\"hidden\" name=\"clfoco\" value=\"" . $form['clfoco'] . "\">\n";
echo "\t<input type=\"hidden\" name=\"clorfo\" value=\"" . $form['clorfo'] . "\">\n";
$rs_partner = "(codice between " . $admin_aziend['mascli'] . "000001 AND " . $admin_aziend['mascli'] . "999999 or codice between " . $admin_aziend['masfor'] . "000001 AND " . $admin_aziend['masfor'] . "999999 )";
if ($form['clorfo'] < 0) { // cliente
    $rs_partner = "(codice between " . $admin_aziend['mascli'] . "000001 AND " . $admin_aziend['mascli'] . "999999 )";
} elseif ($form['clorfo'] > 0) {// fornitore
    $rs_partner = "(codice between " . $admin_aziend['masfor'] . "000001 AND " . $admin_aziend['masfor'] . "999999 )";
}
if ($form['clfoco'] == 0) {
    if (strlen($form['search_partner']) >= 2) {

        $anagrafica = new Anagrafica();
        $partner = $anagrafica->queryPartners("*", $rs_partner . " AND ragso1 like '" . addslashes($form['search_partner']) . "%'", "codice asc, ragso1 asc");
        if (sizeof($partner) > 0) {
            $clifor = $script_transl[5];
            echo "\t<select name=\"clfoco\" class=\"FacetSelect\" onchange=\"this.form.hidden_req.value='new_price'; this.form.submit();\">\n";
            echo "<option value=\"000000000\"> ---------- </option>";
			foreach ($partner AS $key => $row) {
                $selected = "";
                if ($row["codice"] == $form['clfoco']) {
                    $selected = "selected";
                }
                if (substr($row["codice"], 0, 3) == $admin_aziend['masfor']) {
                    $clifor = $script_transl[6];
                }
                echo "\t\t <option value=\"" . $row["codice"] . "\" $selected >" . $row["ragso1"] . " " . $row["citspe"] . "&nbsp;($clifor)</option>\n";
            }
            echo "\t </select>\n";
        } else {
            $messaggio = ucfirst($script_transl['notfound']) ;
        }
    } else {
        $messaggio = ucfirst($script_transl['minins']) . " 2 " . $script_transl['charat'];
    }
    echo "\t<input type=\"text\" name=\"search_partner\" accesskey=\"e\" value=\"" . $form['search_partner'] . "\" maxlength=\"15\"  class=\"FacetInput\">\n";
    echo $messaggio;
    //echo "\t <input type=\"image\" align=\"middle\" accesskey=\"c\" name=\"search\" src=\"../../library/images/cerbut.gif\">\n";
    /** ENRICO FEDELE */
    /* Cambio l'aspetto del pulsante per renderlo bootstrap, con glyphicon */
    echo '&nbsp;<button type="submit" class="btn btn-default btn-sm" name="search" accesskey="c"><i class="glyphicon glyphicon-search"></i></button>';
    /** ENRICO FEDELE */
} else {
    $anagrafica = new Anagrafica();
    $partner = $anagrafica->getPartner($form['clfoco']);
    echo "<input type=\"submit\" value=\"" . substr($partner['ragso1'], 0, 30) . "\" name=\"newpartner\" title=\"" . ucfirst($script_transl['update']) . "!\">\n";
    echo "\t<input type=\"hidden\" name=\"clfoco\" value=\"" . $form['clfoco'] . "\">\n";
    echo "\t<input type=\"hidden\" name=\"search_partner\" value=\"" . $form['search_partner'] . "\">\n";
}
if (substr($form["clfoco"], 0, 3) == $admin_aziend['masfor']) {
    $unimis = "uniacq";
} else {
    $unimis = "unimis";
}
echo "</td><td>" . $script_transl[8] . "</td><td>\n";
echo "\t <select name=\"giodoc\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for ($counter = 1; $counter <= 31; $counter++) {
    $selected = "";
    if ($counter == $form['giodoc'])
        $selected = "selected";
    echo "\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select>\n";

echo "\t <select name=\"mesdoc\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for ($counter = 1; $counter <= 12; $counter++) {
  $selected = "";
  if ($counter == $form['mesdoc']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"anndoc\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for ($counter = date("Y") - 10; $counter <= date("Y") + 10; $counter++) {
    $selected = "";
    if ($counter == $form['anndoc'])
        $selected = "selected";
    echo "\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select></td></tr>\n";
echo "<tr><td>" . $script_transl[9] . "</td><td ><input type=\"text\" value=\"" . $form['desdoc'] . "\" maxlength=\"50\"  name=\"desdoc\"></td>";
echo "<td>" . $script_transl[10] . "</td><td ><input type=\"text\" value=\"" . $form['scochi'] . "\" maxlength=\"5\"  name=\"scochi\" onChange=\"this.form.total.value=CalcolaImportoRigo();\"> %</td></tr>";
echo "<tr><td>" . $script_transl[7] . "</td><td>\n";
$messaggio = "";

$ric_mastro = substr($form['artico'], 0, 3);

$select_artico = new selectartico("artico");
$select_artico->addSelected($form['artico']);
$select_artico->output(substr($form['cosear'], 0,32));

if (($form['artico'] != "" || $form['cosear'] != "") && $form['id_position'] < 1 ) { // se l'articolo non è ancora ubicato ma ha ubicazioni propongo dei bottoni per ubicarli automaticamente
  $result = gaz_dbi_query("SELECT * FROM ".$gTables['artico_position']." t1 LEFT JOIN ".$gTables['artico_position']." t2 ON t1.artico_id_position = t2.id_position WHERE t1.codart = '".($form['artico']!=''?$form['artico']:$form['cosear'])."'" );
  $nump = gaz_dbi_num_rows($result);
  if ($nump > 1) {
    while ($rp = $result->fetch_assoc()) { // creo i bottoni
      echo '<br/><div style="padding-top: 3px;"><a class="btn btn-xs btn-success submit_position" val="'.$rp['id_position'].'">Usa ubicazione <b>' .$rp['position'] . '</b>' . '</a></div>';
    }
  } elseif ($nump==1 && $toDo=='insert') { // in insert, ne ho solo una e vengo da un cambio articolo
    $rp = $result->fetch_assoc();
    $form['cosepos']=$rp['id_position'];
    $form['id_position']=$rp['id_position'];
  }
}
    // Antonio Germani > Inizio LOTTO in uscita o in entrata o creazione nuovo
if ($form['artico'] != "" && intval( $item_artico['lot_or_serial'] && $form['caumag']<>99) == 1) { // se l'articolo prevede lotto e non è inventario, apro gestione lotti nel form
	$form['lot_or_serial']=$item_artico['lot_or_serial'];
	?>
		<div class="FacetFieldCaptionTD"><?php echo $script_transl[21]; ?></div>
		<div class="FacetDataTD" >
		<input type="hidden" name="filename" value="<?php echo (isset($form['filename']))?$form['filename']:''; ?>">

		</div>
	<?php
	if ($form['operat']==1 && $form['quanti']>0){ // se è carico di magazzino ed è impostata la quantità
		if (strlen($form['filename']) == 0) {
			echo '<div><button class="btn btn-xs btn-danger" type="image" data-toggle="collapse" href="#lm_dialog">' . 'Inserire nuovo certificato' . ' ' . '<i class="glyphicon glyphicon-tag"></i>' . '</button></div>';
		} else {
			echo '<div><button class="btn btn-xs btn-success" type="image" data-toggle="collapse" href="#lm_dialog">' . $form['filename'] . ' ' . '<i class="glyphicon glyphicon-tag"></i>' . '</button>';
			echo '</div>';
		}
		if (strlen($form['identifier']) == 0) {
			echo '<div><button class="btn btn-xs btn-danger" type="image" data-toggle="collapse" href="#lm_dialog_lot">' . 'Inserire nuovo Lotto' . ' ' . '<i class="glyphicon glyphicon-tag"></i></button></div>';
		} else {
			if (intval($form['expiry']) > 0) {
				echo '<div><button class="btn btn-xs btn-success" type="image" data-toggle="collapse" href="#lm_dialog_lot">' . $form['identifier'] . ' ' . gaz_format_date($form['expiry']) . '<i class="glyphicon glyphicon-tag"></i></button></div>';
			} else {
				echo '<div><button class="btn btn-xs btn-success" type="image" data-toggle="collapse" href="#lm_dialog_lot" >' . $form['identifier'] . '<i class="glyphicon glyphicon-tag" ></i></button></div>';
			}
		}
		echo '<div id="lm_dialog" class="collapse" ><div class="form-group"><div>';
		?>
		<input type="file" onchange="this.form.submit();" name="docfile_">
		</div>
		</div>
		</div>
		<?php
		echo '<div id="lm_dialog_lot" class="collapse" >
                        <div class="form-group">
                          <div>';
		echo '<label>' . "Numero: " . '</label><input type="text" name="identifier" value="' . $form['identifier'] . '" >';
		echo "<br>";
		echo '<label>' . 'Scadenza: ' . ' </label><input class="datepicker" type="text" onchange="this.form.submit();" name="expiry"  value="' . $form['expiry'] . '"></div></div></div>';
	} else {
		if (($form['operat']==-1 or $form['operat']==0) && $form['quanti']>0){  // se è scarico e è stata impostata la quantità
		 	$lm -> getAvailableLots($form['artico'],$form['id_mov']); // Antonio Germani -
			$ld = $lm->divideLots($form['quanti']);
			$l = 0;

			// calcolo delle giacenze per identifier raggruppando i vari id lot
			$count=array();
			foreach ($lm->available as $v_lm) {
				$key=$v_lm['identifier']; // chiave per il conteggio dei totali raggruppati per lotto
				if( !array_key_exists($key, $count) ){ // se la chiave ancora non c'è nell'array
					// Aggiungo la chiave con il rispettivo valore iniziale
					$count[$key] = $v_lm['rest'];
				} else {
					// Altrimenti, aggiorno il valore della chiave
					$count[$key] += $v_lm['rest'];
				}
			}
			if ((isset($ld) && $ld > 0 && $toDo <> "update") || (isset($lm->divided[$form['id_lotmag']]) && $lm->divided[$form['id_lotmag']]['rest']+$prev_qta['quanti'] < $form['quanti'])) { // segnalo preventivamente l'errore
				?>
				<div class="alert alert-warning alert-dismissible">
				<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
				<strong>Warning!</strong> <b>Quantità lotto non sufficiente!</b> </br>Se si conferma si creeranno incongruenze fra quantità e lotti! </br> Si consiglia di selezionare un lotto con sufficiente disponibilità</br> oppure di diminuire la quantità in uscita.
				</div>
				<?php
			}

			if (intval($form['id_lotmag'])>0 && strlen($form['recip_stocc'])>0){

				$sil_contents = $sil -> getContentSil($form['recip_stocc'],'',0,$prev_idmov);
				if (!array_key_exists($form['id_lotmag'],$sil_contents['id_lotti'])){// se il lotto selezionato non è dentro al silos selezionato
					?>
					<div class="alert alert-warning alert-dismissible">
					<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
					<strong>Warning!</strong> <b>L'id lotto selezionato non è contenuto nel silos inserito!</b> </br><font color="red">Se si conferma si creeranno <b>GRAVI</b> incongruenze fra quantità, lotti e silos! </font></br> Si consiglia di selezionare un lotto realmente contenuto.
					</div>
					<?php
				}
			}
			if (isset($form['id_lotmag']) && $form['id_lotmag'] > 0) { // Selezione manuale del lotto dopo quella iniziale
				echo "Lotto selezionato";
                $selected_lot = $lm->getLot($form['id_lotmag']);
                echo '<div><button class="btn btn-xs btn-success" title="Lotto selezionato. Cliccare per cambiare lotto" type="image"  data-toggle="collapse" href="#lm_dialog">' . $selected_lot['id'] . ' lotto n.:' . $selected_lot['identifier'];
                if (intval($selected_lot['expiry']) > 0) {
                    echo ' scadenza: ' . gaz_format_date($selected_lot['expiry']);
                }
				if (!isset($count[$selected_lot['identifier']])){
					?>
					<div class="alert alert-warning alert-dismissible">
					<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
					<strong>Warning!</strong> <b>LOTTO IN ERRORE!</b> </br>Questo lotto non ha più il documento/movimento madre! </br> Probabilmente è stato cancellato ma sono rimasti i documenti/movimenti in uscita<br>che devono essere modificati o cancellati a loro volta.
					</div></button>
					<?php
				} else {
                echo ' - disponibili: ' . gaz_format_quantity($lm->divided[$form['id_lotmag']]['rest']) . ' <i class="glyphicon glyphicon-tag"></i></button>';
				}
        $form['id_lotmag']=$selected_lot['id'];

				?>
				<input type="hidden" name="identifier" value="<?php echo $selected_lot['identifier']; ?>">
				<input type="hidden" name="expiry" value="<?php echo $selected_lot['expiry']; ?>">
				<?php

				if (isset ($count[$selected_lot['identifier']]) && $form['quanti']>$count[$selected_lot['identifier']]+$prev_qta['quanti']) { // Se il lotto scelto non ha disponibilità sufficienti segnalo errore
					?>
					<div class="alert alert-warning alert-dismissible">
					<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
					<strong>Warning!</strong> <b>Quantità lotto non sufficiente!</b> </br>Se si conferma si creeranno incongruenze fra quantità e lotti! </br> Si consiglia di selezionare un lotto con sufficiente disponibilità</br> oppure di diminuire la quantità in uscita.
					</div>
					<?php
				}
      } else { // selezione automatica INIZIALE  del lotto disponibile
				if (!isset($form['id_lotmag']) or (intval($form['id_lotmag'])==0)) {
					foreach ($lm->divided as $k => $v) { // ciclo i lotti scelti da getAvailableLots
						if ($v['rest'] >= 0.00001) {
							if ($v['rest'] >= $form['quanti']){
								$form['id_lotmag']= $v['id']; // al primo ciclo, cioè id lotto è zero, setto il lotto
								$selected_lot = $lm->getLot($form['id_lotmag']);
								echo '<div><button class="btn btn-xs btn-success"  title="Lotto selezionato automaticamente. Cliccare per cambiare lotto" data-toggle="collapse" href="#lm_dialog">' . $selected_lot['id'] . ' Lotto n.: ' . $selected_lot['identifier'];
								if ($selected_lot['expiry']>0){
									echo ' Scadenza: ' . gaz_format_date($selected_lot['expiry']);
								}
								echo ' disponibili:' . gaz_format_quantity($v['rest']+$prev_qta['quanti']);
								echo '  <i class="glyphicon glyphicon-tag"></i></button>';
                $form['id_lotmag']=$selected_lot['id'];
								?>
								<input type="hidden" name="identifier" value="<?php echo $selected_lot['identifier']; ?>">
								<input type="hidden" name="expiry" value="<?php echo $selected_lot['expiry']; ?>">
								<?php
								$l++;
								break;
							}
						}
					}
				}
				if ($l==0){
					?>
					<div class="alert alert-warning alert-dismissible">
					<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
					<strong>Warning!</strong> <b>La quantità richiesta non è disponibile in un singolo lotto</b> </br> <font color="red">Se si conferma si creeranno errori nella gestione lotti!</font></br> Si consiglia di frazionare questa operazione in più movimenti</br> rispettando le disponibilità per ciascun lotto.
					<a  title="Mostra lotti disponibili" class="btn btn-info btn-md" href="javascript:;" onclick="window.open('<?php echo"../../modules/magazz/mostra_lotti.php?codice=".$form['artico'];?>', 'titolo', 'menubar=no, toolbar=no, width=800, height=400, left=80%, top=80%, resizable, status, scrollbars=1, location');">
					<span class="glyphicon glyphicon-tag"></span></a>
					</div>
					<?php
					}
			}

			?>
			<!-- Antonio Germani - Cambio lotto  -->
			<div id="lm_dialog" class="collapse" >
			<?php
			if ((count($lm->available) >= 1)) {
				foreach ($lm->available as $v_lm) {
					if ($v_lm['id'] <> $form['id_lotmag']) {
						echo '<div>Cambia con:<button class="btn btn-xs btn-warning" type="text" onclick="this.form.submit();" name="id_lotmag_change" value="'.$v_lm['id'].'">'
						. $v_lm['id']. ' lotto n.:' . $v_lm['identifier'];
						if ($v_lm['expiry']>0){
							echo ' scadenza:' . gaz_format_date($v_lm['expiry']);
						}
						echo ' disponibili:' . gaz_format_quantity($v_lm['rest'])
						. '</button></div>';
					}
				}
			} else {
				echo '<div><button class="btn btn-xs btn-danger" type="image" >Non ci sono disponibili altri lotti.</button></div>';
			}
			?>
			</div>
			<?php
		} else {
			echo '<input type="hidden" name="filename" value="">';
			echo '<input type="hidden" name="identifier" value="">';
			echo '<input type="hidden" name="id_lotmag" value="">';
			echo '<input type="hidden" name="expiry" value="">';
		}
	}
  ?>
  <input type="hidden" name="id_lotmag" value="<?php echo $form['id_lotmag']; ?>">
<?php
} else {
        echo '<input type="hidden" name="filename" value="">';
        echo '<input type="hidden" name="identifier" value="">';
        echo '<input type="hidden" name="id_lotmag" value="">';
        echo '<input type="hidden" name="expiry" value="">';
}
if (isset($form['operat']) && strlen($form['artico'])>0 && ($form['operat']==1 && $item_artico['good_or_service']==2 && $tipo_composti['val']=="STD")){
	?>
	<div class="alert alert-warning alert-dismissible">
	<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
	<strong>Warning!</strong> <b>Articolo composto!</b> </br>E' possibile caricare gli articoli composti solo con una produzione.
	</div>
	<?php
}
// Se l'articolo movimenta il SIAN
if ($form['SIAN']>0 && $form['operat']<>0){
	?>
	<div class="container-fluid">
					<div class="row">
						<label for="cod_operazione" class="col-sm-6 control-label"><?php echo "Tipo operazione SIAN"; ?></label>
						<?php
						if ($form['operat']==-1){
							$gForm->variousSelect('cod_operazione', $script_transl['cod_operaz_value'], $form['cod_operazione'], "col-sm-6", false, '', false);
						} else {
							$gForm->variousSelect('cod_operazione', $script_transl['cod_operaz_value_carico'], $form['cod_operazione'], "col-sm-6", false, '', false);
						}
						?>
					</div>
					<?php if ($camp_artico['confezione']==0){;?>
						<div class="row">
							<label for="camp_recip_stocc" class="col-sm-4"><?php echo "Recipiente stoccaggio"; ?></label>
							<?php
							$sil -> selectSilos('recip_stocc' ,'cod_silos', $form['recip_stocc'], 'cod_silos', 1, ' - kg ','cod_silos','col-sm-8', null,'' , null, '');
							?>
						</div>
					<?php } else {
						echo '<input type="hidden" value="" name="recip_stocc" />';
					}
					if (($form['cod_operazione']==4 && $form['operat']==-1) || ($form['cod_operazione']==5 && $form['operat']==1)) { // se è un movimento aziendale chiedo recipiente destinazione
						?>
						<div class="row">
							<label for="camp_recip_destin" class="col-sm-6 control-label"><?php echo "Recipiente destinazione"; ?></label>
							<?php
							$gForm->selectFromDB('camp_recip_stocc', 'recip_stocc_destin' ,'cod_silos', $form['recip_stocc_destin'], 'cod_silos', 1, ' - kg ','cod_silos','TRUE','col-sm-6' , null, '');
							?>
						</div>
						<?php
					} else {
						echo '<input type="hidden" value="" name="recip_stocc_destin" />';
					}
				echo '</div>';





} else {
	?>
	<input type="hidden" name="cod_operazione" value=11>
	<input type="hidden" name="recip_stocc" value="">
	<input type="hidden" name="recip_stocc_destin" value="">
	<?php
}
?>
<input type="hidden" name="lot_or_serial" value="<?php echo $form['lot_or_serial']; ?>">
<input type="hidden" name="SIAN" value="<?php echo $form['SIAN']; ?>">



</td>
<?php
// fine LOTTO e SIAN



echo "<td>" . $script_transl[12] . "</td><td ><input type=\"text\" value=\"" . $form['quanti'] . "\" maxlength=\"10\"  name=\"quanti\" onChange=\"this.form.total.value=CalcolaImportoRigo();this.form.submit();\">".${'print_'.$unimis}."</td></tr>\n";
echo "<tr><td>" . $script_transl[13] . "</td><td ><input type=\"text\" value=\"" . $form['prezzo'] . "\" maxlength=\"12\"  name=\"prezzo\" onChange=\"this.form.total.value=CalcolaImportoRigo();\"> " . $admin_aziend['symbol'] . "</td>\n";
echo "<td>" . $script_transl[14] . "</td><td ><input type=\"text\" value=\"" . $form['scorig'] . "\" maxlength=\"4\"  name=\"scorig\" onChange=\"this.form.total.value=CalcolaImportoRigo();\"> %</td></tr>\n";
echo "<tr><td>" . $strScript["report_movmag.php"][7] . "</td><td ><input type=\"text\" value=\"" . $importo_totale . "\" name=\"total\"  readonly />\n";
echo "<td>" . $strScript["admin_caumag.php"][4] . "</td><td>\n";
echo "<select name=\"operat\" class=\"FacetSelect\">\n";
for ($counter = -1; $counter <= 1; $counter++) {
    $selected = "";
    if ($form["operat"] == $counter) {
        $selected = " selected ";
    }
    echo "<option value=\"$counter\" $selected > " . $strScript["admin_caumag.php"][$counter + 9] . "</option>\n";
}
echo "</select></td></tr><tr><td>Produzione</td><td>";
$select_production = new selectproduction("id_orderman");
$select_production->addSelected($form['id_orderman']);
$select_production->output($form['coseprod']);
echo '</td><td>Ubicazione</td><td>';
$select_position = new selectPosition("id_position");
$select_position->addSelected($form['id_position']);
$select_position->output($form['cosepos']);
echo "</td>\n";
echo '</tr><tr ><td colspan=4 class="FacetFooterTD text-center">';
if ($toDo == 'update') {
    echo '<input type="submit" class="btn btn-warning" accesskey="m" name="Insert" value="' . ucfirst($script_transl['update']) . '"></td></tr><tr></tr>';
} else {
    echo '<input type="submit" class="btn btn-warning" accesskey="i" name="Insert" value="' . ucfirst($script_transl['insert']) . '"></td></tr><tr></tr>';
}
echo "</td></tr></table>\n";
?>
</form>
</div>
<?php
require("../../library/include/footer.php");
?>
