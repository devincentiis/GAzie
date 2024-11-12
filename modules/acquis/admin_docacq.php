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
require("../../modules/magazz/lib.function.php");
require("../../modules/vendit/lib.function.php");
require("../../modules/camp/lib.function.php");
$admin_aziend = checkAdmin();
$pdf_to_modal = gaz_dbi_get_row($gTables['company_config'], 'var', 'pdf_reports_send_to_modal')['val'];
$scorrimento = gaz_dbi_get_row($gTables['company_config'], 'var', 'autoscroll_to_last_row')['val'];
$after_newdoc_back = gaz_dbi_get_row($gTables['company_config'], 'var', 'after_newdoc_back_to_doclist')['val'];
$msg = array('err' => array(), 'war' => array());
$anagrafica = new Anagrafica();
$gForm = new acquisForm();
$calc = new Compute;
$magazz = new magazzForm;
$docOperat = $magazz->getOperators();
$lm = new lotmag;
$sil = new silos;
$value_sian=array();
$ddt = (object)[];
$ddt->num_rows = 0;

function get_tmp_doc($i) {
    global $admin_aziend;
    return true;
}

if (isset($_POST['newdestin'])) {
    $_POST['id_des'] = 0;
    $_POST['id_des_same_company'] = 0;
    $_POST['destin'] = "";
}

if (!isset($_POST['ritorno'])) {
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
} else {
  $form['ritorno'] = $_POST['ritorno'];
}

if ((isset($_GET['Update']) && !isset($_GET['id_tes'])) && !isset($_GET['tipdoc'])) {
    header("Location: " . $form['ritorno']);
    exit;
}

if ((isset($_POST['Update'])) || ( isset($_GET['Update']))) {
  $toDo = 'update';
} else {
  $toDo = 'insert';
}

if ((isset($_POST['Insert'])) || ( isset($_POST['Update']))) {   //se non e' il primo accesso
//qui si dovrebbe fare un parsing di quanto arriva dal browser...
    $form['id_tes'] = intval($_POST['id_tes']);
    $anagrafica = new Anagrafica();
    $fornitore = $anagrafica->getPartner(intval($_POST['clfoco']));
    $form['hidden_req'] = $_POST['hidden_req'];
// ...e della testata
    foreach ($_POST['search'] as $k => $v) {
        $form['search'][$k] = $v;
    }
    $form['cosear'] = (isset($_POST['cosear']))?$_POST['cosear']:'';
    $form['coseprod'] = $_POST['coseprod'];
    $form['seziva'] = $_POST['seziva'];
    $form['id_con'] = intval($_POST['id_con']);
    $form['tipdoc'] = $_POST['tipdoc'];
    $form['giotra'] = $_POST['giotra'];
    $form['mestra'] = $_POST['mestra'];
    $form['anntra'] = $_POST['anntra'];
    $form['oratra'] = $_POST['oratra'];
    $form['mintra'] = $_POST['mintra'];
	$form['datreg'] = substr($_POST['datreg'],0,10);
	$form['datfat'] = substr($_POST['datfat'],0,10);
    $form['datemi'] = substr($_POST['datemi'],0,10);
    $form['protoc'] = intval($_POST['protoc']);
    $form['numdoc'] = intval($_POST['numdoc']);
    $form['numfat'] = $_POST['numfat'];
	if ($form['tipdoc']=='AFA' || $form['tipdoc']=='AFC'){ // sulle fatture-n.c. forzo datemi e numdoc agli stessi valori di datfat e numfat
	    $form['datemi'] = $form['datfat'];
	    $form['numdoc'] = $form['numfat'];
	}
    $form['clfoco'] = $_POST['clfoco'];
    $form['address'] = $_POST['address'];
//tutti i controlli su  tipo di pagamento e rate
    $form['speban'] = $_POST['speban'];
    $form['numrat'] = $_POST['numrat'];
    $form['pagame'] = $_POST['pagame'];
    $form['change_pag'] = $_POST['change_pag'];
    $ddtchecked=0;
	if (isset($_POST['Insert'])){// se insert carico in $ddt i ddt che non sono ancora fatturati
		$ddt_rs = gaz_dbi_query ('SELECT * FROM '.$gTables['tesdoc'].' WHERE clfoco = \''.$form['clfoco'].'\' AND tipdoc = \'ADT\' AND ddt_type = \'\' ORDER BY id_tes');
    if ($ddt_rs) $ddt = $ddt_rs;
		if (isset($_POST['ddt'])){ // se cliccato ddt azzero i righi nel caso fossero cambiati
			unset ($_POST['rows']);
		}
		$i=0;
    if (!isset($_POST['num_ddt'])) $_POST['num_ddt']=-1;
		for ($ddtrow=0 ; $ddtrow<=$_POST['num_ddt']; $ddtrow++){
			$form['id_tes'.$ddtrow] = $_POST['id_tes'.$ddtrow];
			if (isset($_POST['check_ddt'.$ddtrow]) AND $_POST['check_ddt'.$ddtrow]=="checked"){
				$form['check_ddt'.$ddtrow] = "checked";

				if (isset($_POST['ddt'])){ // se cliccato ddt carico pure tutti i righi dei DDT checked
					$rigdoc = gaz_dbi_dyn_query('*', $gTables['rigdoc'], 'id_tes = '.$_POST['id_tes'.$ddtrow],"id_rig ASC");
					while ($row = gaz_dbi_fetch_array($rigdoc)) {
						$_POST['rows'][$i]['descri'] = substr($row['descri'], 0, 100);
						$_POST['rows'][$i]['tiprig'] = intval($row['tiprig']);
						$_POST['rows'][$i]['codart'] = substr($row['codart'], 0,32);
						$_POST['rows'][$i]['codice_fornitore'] = substr($row['codice_fornitore'], 0, 50);	// Aggiunto a Mano
						$_POST['rows'][$i]['pervat'] = preg_replace("/\,/", '.', $row['pervat']);
						$_POST['rows'][$i]['ritenuta'] = floatval(preg_replace("/\,/", '.', $row['ritenuta']));
						$_POST['rows'][$i]['unimis'] = substr($row['unimis'], 0, 3);
						$_POST['rows'][$i]['prelis'] = floatval(preg_replace("/\,/", '.', $row['prelis']));
						$_POST['rows'][$i]['sconto'] = floatval(preg_replace("/\,/", '.', $row['sconto']));
						$_POST['rows'][$i]['quanti'] = gaz_format_quantity($row['quanti'], 0, $admin_aziend['decimal_quantity']);
						$_POST['rows'][$i]['codvat'] = intval($row['codvat']);
						$_POST['rows'][$i]['codric'] = intval($row['codric']);
						$_POST['rows'][$i]['provvigione'] = floatval($row['provvigione']);
						$_POST['rows'][$i]['id_mag'] = intval($row['id_mag']);
						$_POST['rows'][$i]['id_order'] = intval($row['id_order']);
						$_POST['rows'][$i]['id_orderman'] = intval($row['id_orderman']);
						$_POST['rows'][$i]['id_rig'] = intval($row['id_rig']);
            // dati da movimento di magazzino
            $row['id_warehouse']=0;
						if($row['id_mag']>0){
              $movmag = gaz_dbi_get_row($gTables['movmag'], "id_mov", $row['id_mag']);
              $row['id_warehouse'] = $movmag ? $movmag['id_warehouse'] : 0;
            }
						$_POST['rows'][$i]['id_warehouse'] = $row['id_warehouse'];
            $_POST['in_id_warehouse']=$row['id_warehouse'];
            // dati da anagrafica articolo
						$artico = gaz_dbi_get_row($gTables['artico'], "codice", substr($row['codart'], 0,32));
            if ($artico){
              $_POST['rows'][$i]['quality'] = strval($artico['quality']);
              $_POST['rows'][$i]['annota'] = substr($artico['annota'], 0, 50);
              $_POST['rows'][$i]['pesosp'] = floatval($artico['peso_specifico']);
              $_POST['rows'][$i]['gooser'] = intval($artico['good_or_service']);
              $_POST['rows'][$i]['quamag'] = floatval($artico['quality']);
              $_POST['rows'][$i]['scorta'] = floatval($artico['scorta']);
              $_POST['rows'][$i]['lot_or_serial'] = intval($artico['lot_or_serial']);
              $_POST['rows'][$i]['SIAN'] = intval($artico['SIAN']);
              if (intval($artico['lot_or_serial'])>0 AND intval($row['id_rig'])>0){
                $lotres = gaz_dbi_get_row($gTables['lotmag'], "id_rigdoc", intval($row['id_rig']));
                $_POST['rows'][$i]['id_lotmag'] = $lotres['id'];
                $_POST['rows'][$i]['identifier'] = $lotres['identifier'];
                $_POST['rows'][$i]['expiry'] = $lotres['expiry'];
              } else{
                $_POST['rows'][$i]['id_lotmag'] = '';
              }
            }else{
              $_POST['rows'][$i]['quality'] = '';
              $_POST['rows'][$i]['annota'] = '';
              $_POST['rows'][$i]['pesosp'] = 0;
              $_POST['rows'][$i]['gooser'] = 0;
              $_POST['rows'][$i]['quamag'] = 0;
              $_POST['rows'][$i]['scorta'] = 0;
              $_POST['rows'][$i]['lot_or_serial'] = 0;
              $_POST['rows'][$i]['SIAN'] = 0;
              $_POST['rows'][$i]['id_lotmag'] = '';
            }
						$_POST['rows'][$i]['filename'] ='';
						$_POST['rows'][$i]['status']='';
						$i++;
					}
				}
				$ddtchecked++;
			} else {
				$form['check_ddt'.$ddtrow]="";
			}
			$form['num_ddt'.$ddtrow] = (isset($_POST['num_ddt'.$ddtrow]))?$_POST['num_ddt'.$ddtrow]:'';
		}


	}
	if ($form['change_pag'] != $form['pagame']) {  //se è stato cambiato il pagamento
        $new_pag = gaz_dbi_get_row($gTables['pagame'], "codice", $form['pagame']);
        $old_pag = gaz_dbi_get_row($gTables['pagame'], "codice", $form['change_pag']);
		if (isset($new_pag)) {
			if (($new_pag['tippag'] == 'B' || $new_pag['tippag'] == 'T' || $new_pag['tippag'] == 'V')
					&& ( $old_pag['tippag'] == 'C' || $old_pag['tippag'] == 'D' || $old_pag['tippag'] == 'O')) { // se adesso devo mettere le spese e prima no
				$form['numrat'] = $new_pag['numrat'];
				if ($toDo == 'update') {  //se è una modifica mi baso sulle vecchie spese
					$old_header = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", $form['id_tes']);
					if ($old_header['speban'] > 0 && $fornitore['speban'] == "S") {
						$form['speban'] = 0;
					} elseif ($old_header['speban'] == 0 && $fornitore['speban'] == "S") {
						$form['speban'] = 0;
					} else {
						$form['speban'] = 0.00;
					}
				} else { //altrimenti mi avvalgo delle nuove dell'azienda
					$form['speban'] = 0;
				}
			} elseif (($new_pag['tippag'] == 'C' || $new_pag['tippag'] == 'D' || $new_pag['tippag'] == 'O')
					&& ( $old_pag['tippag'] == 'B' || $old_pag['tippag'] == 'T' || $old_pag['tippag'] == 'V')) { // se devo togliere le spese
				$form['speban'] = 0.00;
				$form['numrat'] = 1;
			}
		} else {
			$form['speban'] = 0.00;
			$form['numrat'] = 1;
		}
        $form['pagame'] = $_POST['pagame'];
        $form['change_pag'] = $_POST['pagame'];
    }
    $form['banapp'] = $_POST['banapp'];
    $form['vettor'] = $_POST['vettor'];
    $form['listin'] = $_POST['listin'];
    $form['spediz'] = $_POST['spediz'];
    $form['portos'] = $_POST['portos'];
    $form['imball'] = $_POST['imball'];
    $form['destin'] = $_POST['destin'];
    $form['id_des'] = substr($_POST['id_des'], 3);
    $form['id_des_same_company'] = intval($_POST['id_des_same_company']);

    /** inizio modifica FP 09/01/2016
     * modifica piede DDT
     */
    $form['net_weight'] = floatval($_POST['net_weight']);
    $form['gross_weight'] = floatval($_POST['gross_weight']);
    $form['units'] = intval($_POST['units']);
    $form['volume'] = floatval($_POST['volume']);
    $strArrayDest = $_POST['rs_destinazioni'];
    $array_destinazioni = unserialize(base64_decode($strArrayDest)); // recupero l'array delle destinazioni
    /** fine modifica FP */
    $form['traspo'] = $_POST['traspo'];
    $form['spevar'] = $_POST['spevar'];
    $form['ivaspe'] = $_POST['ivaspe'];
    $form['pervat'] = $_POST['pervat'];
    $form['cauven'] = $_POST['cauven'];
    $form['caucon'] = $_POST['caucon'];
    $form['caumag'] = $_POST['caumag'];
    $form['caucon'] = $_POST['caucon'];
    $form['id_parent_doc'] = $_POST['id_parent_doc'];
    $form['sconto'] = $_POST['sconto'];
// inizio rigo di input
    $form['in_descri'] = $_POST['in_descri'];
    $form['in_tiprig'] = $_POST['in_tiprig'];
    /*    $form['in_artsea'] = $_POST['in_artsea']; Non serve più */
    $form['in_codart'] = $_POST['in_codart'];
    $form['in_codice_fornitore'] = $_POST['in_codice_fornitore'];
    $form['in_pervat'] = $_POST['in_pervat'];
    $form['in_ritenuta'] = $_POST['in_ritenuta'];
    $form['in_provvigione'] = $_POST['in_provvigione']; // in caso tiprig=4 questo campo è utilizzato per indicare l'aliquota della cassa     $form['in_unimis'] = $_POST['in_unimis'];
    $form['in_prelis'] = $_POST['in_prelis'];
    $form['in_sconto'] = $_POST['in_sconto'];
	$form['in_SIAN'] = $_POST['in_SIAN'];
    $form['in_unimis'] = $_POST['in_unimis'];
    $form['in_quanti'] = floatval($_POST['in_quanti']);
	$form['in_quality'] = strval($_POST['in_quality']);
    $form['in_codvat'] = $_POST['in_codvat'];
    $form['in_codric'] = $_POST['in_codric'];
    $form['in_id_mag'] = $_POST['in_id_mag'];
    $form['in_id_warehouse'] = (isset($_POST['in_id_warehouse']))?intval($_POST['in_id_warehouse']):0;
    $form['in_id_order'] = intval($_POST['in_id_order']);
    $form['in_id_orderman'] = $_POST['in_id_orderman'];
    $form['in_annota'] = $_POST['in_annota'];
    $form['in_pesosp'] = $_POST['in_pesosp'];
    $form['in_gooser'] = intval($_POST['in_gooser']);
    $form['in_quamag'] = $_POST['in_quamag'];
    $form['in_scorta'] = intval($_POST['in_scorta']);
    $form['in_lot_or_serial'] = intval($_POST['in_lot_or_serial']);
    $form['in_extdoc'] = $_POST['in_extdoc'];
    $form['in_status'] = $_POST['in_status'];
// fine rigo input
    $form['rows'] = [];
    $i = 0;
    if (isset($_POST['rows'])) {
      foreach ($_POST['rows'] as $i => $value) {
        if (isset($_POST["row_$i"])) { //se ho un rigo testo
            $form["row_$i"] = $_POST["row_$i"];
        }
        $form['rows'][$i]['descri'] = substr($value['descri'], 0, 100);
        $form['rows'][$i]['tiprig'] = intval($value['tiprig']);
        $form['rows'][$i]['codart'] = substr($value['codart'], 0,32);
        $form['rows'][$i]['codice_fornitore'] = substr($value['codice_fornitore'], 0, 50);	// Aggiunto a Mano
        $form['rows'][$i]['pervat'] = preg_replace("/\,/", '.', $value['pervat']);
        $form['rows'][$i]['ritenuta'] = floatval(preg_replace("/\,/", '.', $value['ritenuta']));
        $form['rows'][$i]['unimis'] = substr($value['unimis'], 0, 3);
        $form['rows'][$i]['prelis'] = floatval(preg_replace("/\,/", '.', $value['prelis']));
        $form['rows'][$i]['sconto'] = floatval(preg_replace("/\,/", '.', $value['sconto']));
        $form['rows'][$i]['quanti'] = gaz_format_quantity($value['quanti'], 0, $admin_aziend['decimal_quantity']);
        $form['rows'][$i]['codvat'] = intval($value['codvat']);
        $form['rows'][$i]['codric'] = intval($value['codric']);
        $form['rows'][$i]['provvigione'] = floatval($value['provvigione']);
        $form['rows'][$i]['id_mag'] = intval($value['id_mag']);
        $form['rows'][$i]['id_warehouse'] = intval($value['id_warehouse']);
        $form['rows'][$i]['id_order'] = intval($value['id_order']);
        $form['rows'][$i]['id_orderman'] = intval($value['id_orderman']);
        if(isset($_POST['all_same_orderman'])){$form['rows'][$i]['id_orderman']=$form['in_id_orderman'];}
        $form['rows'][$i]['annota'] = substr($value['annota'], 0, 50);
        $form['rows'][$i]['pesosp'] = floatval($value['pesosp']);
        $form['rows'][$i]['gooser'] = intval($value['gooser']);
        $form['rows'][$i]['quamag'] = floatval($value['quamag']);
        $form['rows'][$i]['quality'] = strval($value['quality']);
        $form['rows'][$i]['scorta'] = floatval($value['scorta']);
        $form['rows'][$i]['lot_or_serial'] = intval($value['lot_or_serial']);
        $form['rows'][$i]['SIAN'] = intval($value['SIAN']);
        $form['rows'][$i]['id_rig'] = intval($value['id_rig']);
        $form['rows'][$i]['extdoc'] = filter_var($value['extdoc'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!empty($_FILES['docfile_' . $i]['name'])) {
          $move = false;
          $mt = strtolower(substr($_FILES['docfile_' . $i]['name'], -3));
          $prefix = $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $i;
          if ($mt == 'pdf' && $_FILES['docfile_' . $i]['size'] > 1000) { //se c'e' un nuovo documento nel buffer
            if ($_FILES['docfile_' . $i]['size'] > 4500000) $msg['err'][] = "filesize";
            if (count($msg['err'])==0) {
              $move = move_uploaded_file($_FILES['docfile_' . $i]['tmp_name'], DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/tmp/'.$i.'_rigdoc_'.$_FILES['docfile_' . $i]['name']);
              $form['rows'][$i]['extdoc'] = $_FILES['docfile_' . $i]['name'];
              $form['rows'][$i]['pesosp'] = $_FILES['docfile_' . $i]['size']/1000;
            }
          }
          if (!$move) {
            $msg['err'][] = "filenoload";
          }
        }
        if ($value['lot_or_serial'] == 2) { // se è prevista la gestione per numero seriale/matricola la quantità non può essere diversa da 1
            if ($form['rows'][$i]['quanti'] <> 1) {
                $msg['err'][] = "forceone";
            }
            $form['rows'][$i]['quanti'] = 1;
        }
        if (isset($_POST['rows'][$i]['cod_operazione'])){
          $form['rows'][$i]['cod_operazione'] = intval($_POST['rows'][$i]['cod_operazione']);
        } else {
          $form['rows'][$i]['cod_operazione']="";
        }
        if (isset($_POST['rows'][$i]['recip_stocc'])){
          $form['rows'][$i]['recip_stocc'] = $_POST['rows'][$i]['recip_stocc'];
        } else {
          $form['rows'][$i]['recip_stocc']="";
        }
        if (isset($_POST['rows'][$i]['recip_stocc_destin'])){
          $form['rows'][$i]['recip_stocc_destin'] = $_POST['rows'][$i]['recip_stocc_destin'];
        } else {
          $form['rows'][$i]['recip_stocc_destin']="";
        }

        if (isset($_POST['new_lotmag'][$i])) {// se è stato assegnato un nuovo lotto da un DDR
          // assegno il nuovo lotto al rigo
          $form['rows'][$i]['id_lotmag'] = key($_POST['new_lotmag'][$i]);
          $getlot = $lm->getLot($form['rows'][$i]['id_lotmag']);
          $form['rows'][$i]['identifier'] = $getlot['identifier'];
        } else {
          $form['rows'][$i]['identifier'] = (empty($_POST['rows'][$i]['identifier'])) ? '' : filter_var($_POST['rows'][$i]['identifier'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
          $form['rows'][$i]['id_lotmag'] = $_POST['rows'][$i]['id_lotmag'];

          if (isset($_POST['rows'][$i]['expiry']) AND $_POST['rows'][$i]['expiry']>0){
            $form['rows'][$i]['expiry'] = (empty($_POST['rows'][$i]['expiry'])) ? '' : filter_var($_POST['rows'][$i]['expiry'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
          } else {
            $form['rows'][$i]['expiry']="0000-00-00 00:00:00";
          }
        }

        $form['rows'][$i]['filename'] = filter_var($_POST['rows'][$i]['filename'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!empty($_FILES['certfile_' . $i]['name'])) {
            $move = false;
            $mt = substr($_FILES['certfile_' . $i]['name'], -3);
            $prefix = $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $i;
            if (($mt == 'png' || $mt == 'peg' || $mt == 'jpg' || $mt == 'pdf') && $_FILES['certfile_' . $i]['size'] > 1000) { //se c'e' una nuova immagine nel buffer
                foreach (glob( DATA_DIR . 'files/tmp/' . $prefix . '_*.*') as $fn) {// prima cancello eventuali precedenti file temporanei
                    unlink($fn);
                }
                $move = move_uploaded_file($_FILES['certfile_' . $i]['tmp_name'], DATA_DIR . 'files/tmp/' . $prefix . '_' . $_FILES['docfile_' . $i]['name']);
                $form['rows'][$i]['filename'] = $_FILES['certfile_' . $i]['name'];
            }
            if (!$move) {
                $msg['err'][] = "notrack";
            }
        }
        $form['rows'][$i]['status'] = substr($value['status'], 0, 10);

        if (isset($_POST['upd_row'])) {
            $key_row = key($_POST['upd_row']);
            if ($key_row == $i) {
                $form['in_descri'] = $form['rows'][$key_row]['descri'];
                $form['in_tiprig'] = $form['rows'][$key_row]['tiprig'];
                $form['in_codice_fornitore'] = $form['rows'][$key_row]['codice_fornitore'];
                $form['in_codart'] = $form['rows'][$key_row]['codart'];
                $form['in_pervat'] = $form['rows'][$key_row]['pervat'];
                $form['in_ritenuta'] = $form['rows'][$key_row]['ritenuta'];
                $form['in_unimis'] = $form['rows'][$key_row]['unimis'];
                $form['in_prelis'] = $form['rows'][$key_row]['prelis'];
                $form['in_sconto'] = $form['rows'][$key_row]['sconto'];
                $form['in_quanti'] = $form['rows'][$key_row]['quanti'];
                $form['in_codvat'] = $form['rows'][$key_row]['codvat'];
                $form['in_codric'] = $form['rows'][$key_row]['codric'];
                $form['in_provvigione'] = $form['rows'][$key_row]['provvigione'];// in caso tiprig=4 questo campo è utilizzato per indicare l'aliquota della cassa previdenziale
                $form['in_id_mag'] = $form['rows'][$key_row]['id_mag'];
                $form['in_id_warehouse'] = $form['rows'][$key_row]['id_warehouse'];
                $form['in_id_order'] = $form['rows'][$key_row]['id_order'];
                //$orderman = gaz_dbi_get_row($gTables['orderman'], "id", $form['rows'][$key_row]['id_orderman']);
                //$form['coseprod'] =($orderman)?$orderman['description']:'';
                //$form['in_id_orderman'] = $form['rows'][$key_row]['id_orderman'];
                $form['in_annota'] = $form['rows'][$key_row]['annota'];
                $form['in_pesosp'] = $form['rows'][$key_row]['pesosp'];
                $form['in_gooser'] = $form['rows'][$key_row]['gooser'];
                $form['in_scorta'] = $form['rows'][$key_row]['scorta'];
                $form['in_quamag'] = $form['rows'][$key_row]['quamag'];
                $form['in_quality'] = $form['rows'][$key_row]['quality'];
                $form['in_lot_or_serial'] = $form['rows'][$key_row]['lot_or_serial'];
                $form['in_SIAN'] = $form['rows'][$key_row]['SIAN'];
                $form['in_cod_operazione'] = $form['rows'][$key_row]['cod_operazione'];
                $form['in_recip_stocc'] = $form['rows'][$key_row]['recip_stocc'];
                $form['in_recip_stocc_destin'] = $form['rows'][$key_row]['recip_stocc_destin'];
                $form['in_status'] = "UPDROW" . $key_row;
                // sottrazione ai totali peso,pezzi,volume
                $artico = gaz_dbi_get_row($gTables['artico'], "codice", $form['rows'][$key_row]['codart']);
                $form['net_weight'] -= $form['rows'][$key_row]['quanti'] * ($artico['peso_specifico'] ?? 1);
                $form['gross_weight'] -= $form['rows'][$key_row]['quanti'] * ($artico['peso_specifico'] ?? 1);
                if ($artico && $artico['pack_units'] > 0) {
                    $form['units'] -= intval(round($form['rows'][$key_row]['quanti'] / $artico['pack_units']));
                }
                $form['volume'] -= $form['rows'][$key_row]['quanti'] * ($artico['volume_specifico'] ?? 1);
                $form['cosear'] = $form['rows'][$key_row]['codart'];
                $form['in_extdoc'] = $form['rows'][$key_row]['extdoc'];
                if (!empty($_FILES['docfile_' . $key_row]['name'])) {
                    $move = false;
                    $mt = strtolower(substr($_FILES['docfile_' . $i]['name'], -3));
                    $prefix = $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $i;
                    if ($mt == 'pdf' && $_FILES['docfile_' . $i]['size'] > 1000) { //se c'e' un nuovo documento nel buffer
                      if ($_FILES['docfile_' . $i]['size'] > 1999999) $msg['err'][] = "filesize";
                      if (count($msg['err'])==0) {
                        $move = move_uploaded_file($_FILES['docfile_' . $i]['tmp_name'], DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/tmp/'.$i.'_rigdoc_'.$_FILES['docfile_' . $i]['name']);
                        $form['rows'][$i]['extdoc'] = $_FILES['docfile_' . $i]['name'];
                        $form['rows'][$i]['pesosp'] = $_FILES['docfile_' . $i]['size']/1000;
                      }
                    }
                    if (!$move) {
                      $msg['err'][] = "filenoload";
                    }
                }
                array_splice($form['rows'], $key_row, 1);
                $i--;
            }
        } elseif ($_POST['hidden_req'] == 'ROW') {
            if (!empty($form['hidden_req'])) { // al primo ciclo azzero ma ripristino il lordo
                $form['gross_weight'] -= $form['net_weight'];
                $form['net_weight'] = 0;
                $form['units'] = 0;
                $form['volume'] = 0;
                $form['hidden_req'] = '';
            }
            $artico = gaz_dbi_get_row($gTables['artico'], "codice", $form['rows'][$i]['codart']);
            $form['net_weight'] += $form['rows'][$i]['quanti'] * $artico['peso_specifico'];
            $form['gross_weight'] += $form['rows'][$i]['quanti'] * $artico['peso_specifico'];
            if ($artico['pack_units'] > 0) {
                $form['units'] += intval(round($form['rows'][$i]['quanti'] / $artico['pack_units']));
            }
            $form['volume'] += $form['rows'][$i]['quanti'] * $artico['volume_specifico'];
        }
        $i++;
			if ($value['SIAN']>0){
			$uldtfile=getLastSianDay();
			$datem=substr($form['datemi'],6,4) . "-" . substr($form['datemi'],3,2) . "-" . substr($form['datemi'],0,2);
			if (strtotime($datem) < strtotime($uldtfile)){
				$msg['war'][] = "siandate";
			}
		}
        }
    }
// Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {
        $sezione = $form['seziva'];
        $datemi = gaz_format_date($form['datemi'],true);
        $utsemi = mktime(0, 0, 0, substr($form['datemi'],3,2), substr($form['datemi'],0,2), substr($form['datemi'],6,4) );
        $utsreg = mktime(0, 0, 0, substr($form['datreg'],3,2), substr($form['datreg'],0,2), substr($form['datreg'],6,4) );
        $initra = $form['anntra'] . "-" . $form['mestra'] . "-" . $form['giotra'];
        $utstra = mktime(0, 0, 0, $form['mestra'], $form['giotra'], $form['anntra']);
        if ($form['tipdoc'] == 'DDR' || $form['tipdoc'] == 'DDL') {  //se è un DDT vs Fattura differita
            if ($utstra < $utsemi) {
               $msg['err'][] = "dtintr";
            }
            if (!checkdate($form['mestra'], $form['giotra'], $form['anntra'])) {
               $msg['err'][] = "dttrno";
            }
        } elseif ($form['tipdoc'] == 'ADT'  || $form['tipdoc'] == 'AFT' || $form['tipdoc'] == 'RDL') { // è un ddt ricevuto da fornitore non effettuo controlli su date e numeri
			if (empty($form['numdoc'])) {
               $msg['err'][] = "nonudo";
            }
		} else {
			$utsfat = mktime(0, 0, 0, substr($form['datfat'],3,2), substr($form['datfat'],0,2), substr($form['datfat'],6,4));
            if ($utsfat > $utsreg) {
               $msg['err'][] = "dregpr";
            }
            if (empty($form['numfat'])) {
               $msg['err'][] = "nonudo";
            }
        }
        if (!isset($_POST['rows'])) {
            $msg['err'][] = "norows";
        }
// --- inizio controllo coerenza date-numerazione
        if ($toDo == 'update') {  // controlli in caso di modifica
            if ($form['tipdoc'] == 'DDR' || $form['tipdoc'] == 'DDL') {  //se è un DDL o DDR
                $rs_query = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datemi) = " . substr($datemi,0,4) . " AND datemi < '$datemi' AND ( tipdoc like 'DD_' OR tipdoc = 'FAD') AND seziva = $sezione", "numdoc desc", 0, 1);
                $result = gaz_dbi_fetch_array($rs_query); //giorni precedenti
                if ($result && ( $form['numdoc'] < $result['numdoc'])) {
                    $msg['err'][]= "dtnuan";
                }
                $rs_query = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datemi) = " . substr($datemi,0,4) . " AND datemi > '$datemi' AND ( tipdoc like 'DD_' OR tipdoc = 'FAD') AND seziva = $sezione", "numdoc asc", 0, 1);
                $result = gaz_dbi_fetch_array($rs_query); //giorni successivi
                if ($result && ( $form['numdoc'] > $result['numdoc'])) {
                    $msg['err'][]= "dtnusc";
                }
            } elseif ($form['tipdoc'] == 'ADT' || $form['tipdoc'] == 'AFT' || $form['tipdoc'] == 'RDL') { //se è un DDT acquisto non faccio controlli
              // ma effettuo il controllo se è stato già inserito con lo stesso numero, anno e tipo
              $checkdouble = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "tipdoc = '".$form['tipdoc']."' AND YEAR(datemi) = " . substr($datemi,0,4) . " AND numdoc = " . $form['numdoc'] . " AND seziva = $sezione AND clfoco = ". intval($form['clfoco']) ." AND id_tes <> ". $form['id_tes'], 2,0,1);
              $check = gaz_dbi_fetch_array($checkdouble);
              if ($check){
                //var_dump($check);
                $msg['err'][] = "ddtesist";
              }
            } else { //se sono altri documenti - AFA AFC
                $rs_query = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datreg) = " . substr($form['datreg'],-4) . " AND datreg < '".gaz_format_date($form['datreg'],true)."' AND tipdoc LIKE '" . substr($form['tipdoc'], 0, 2) . "_' AND seziva = ".$sezione, "protoc desc", 0, 1);
                $result = gaz_dbi_fetch_array($rs_query); //giorni precedenti
                if ($result && ($form['protoc'] < $result['protoc'])) {
                    $msg['err'][] = "dtante";
                }
                $rs_query = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datreg) = " . substr($form['datreg'],-4) . " AND datreg > '".gaz_format_date($form['datreg'],true)."' AND tipdoc LIKE '" . substr($form['tipdoc'], 0, 2) . "_' AND seziva = ".$sezione, "protoc asc", 0, 1);
                $result = gaz_dbi_fetch_array($rs_query); //giorni successivi
                if ($result && ($form['protoc'] > $result['protoc'])) {
                    $msg['err'][] = "dtsucc";
                }
            }
        } else {    //controlli in caso di inserimento
            if ($form['tipdoc'] == 'DDR' || $form['tipdoc'] == 'DDL') {  //se è un DDT
                $rs_ultimo_ddt = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datemi) = " . substr($datemi,0,4) . " AND tipdoc like 'DD_' AND seziva = $sezione", "numdoc desc, datemi desc", 0, 1);
                $ultimo_ddt = gaz_dbi_fetch_array($rs_ultimo_ddt);
				if ($ultimo_ddt){
					$utsUltimoDdT = mktime(0, 0, 0, substr($ultimo_ddt['datemi'], 5, 2), substr($ultimo_ddt['datemi'], 8, 2), substr($ultimo_ddt['datemi'], 0, 4));
                }
				if ($ultimo_ddt && ( $utsUltimoDdT > $utsemi)) {
                    $msg['err'][] = "ddtpre";
                }
            } elseif ($form['tipdoc'] == 'ADT'  || $form['tipdoc'] == 'AFT' || $form['tipdoc'] == 'RDL') {  //se è un DDT d'acquisto non effettuo controlli sulle date
				// ma effettuo il controllo se è stato già inserito con lo stesso numero e data
				if ($form['numdoc']>0){
					$checkdouble = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "tipdoc IN ('ADT', 'AFT', 'RDL') AND YEAR(datemi) = " . substr($datemi,0,4) . " AND numdoc = " . $form['numdoc'] . " AND seziva = $sezione AND clfoco = ". intval($form['clfoco']), 2,0,1);
					$check = gaz_dbi_fetch_array($checkdouble);
					if ($check){
						$msg['err'][] = "ddtesist";
					}
				}
			} else { //se sono altri documenti AFA AFC
                $rs_ultimo_tipo = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datreg) = " . substr($form['datreg'],-4) . " AND tipdoc LIKE '" . substr($form['tipdoc'], 0, 2) . "%' AND seziva = ".$sezione, "protoc desc, datreg desc, datfat desc", 0, 1);
                $ultimo_tipo = gaz_dbi_fetch_array($rs_ultimo_tipo);
				if ($ultimo_tipo){
					$utsUltimoProtocollo = mktime(0, 0, 0, substr($ultimo_tipo['datreg'], 5, 2), substr($ultimo_tipo['datreg'], 8, 2), substr($ultimo_tipo['datreg'], 0, 4));
					if ($utsUltimoProtocollo > $utsreg) {
						$msg['err'][] = "docpre";
					}
                }
				if (!empty($form["clfoco"])) {
					if (substr($form['tipdoc'], 0, 1)=="R"){
						if (!preg_match("/^id_([0-9]+)$/", $form['clfoco'], $match)) {
							//controllo se ci sono altri documenti "R__" con lo stesso numero fornitore
							$rs_stesso_numero = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datemi) = " .substr($datemi,0,4) . " AND tipdoc like '" . substr($form['tipdoc'], 0, 1) . "%' AND clfoco = " . $form['clfoco'] . " AND numdoc = '" . $form['numdoc'] . "'", "protoc desc, datfat desc, datemi desc", 0, 1);
							$stesso_numero = gaz_dbi_fetch_array($rs_stesso_numero);
							if ($stesso_numero) {
								$msg['err'][] = "samedoc";
							}
						}
					} else {
						if (!preg_match("/^id_([0-9]+)$/", $form['clfoco'], $match)) {
							//controllo se ci sono altri documenti con lo stesso numero fornitore
							$rs_stesso_numero = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datemi) = " .substr($datemi,0,4) . " AND tipdoc like '" . substr($form['tipdoc'], 0, 1) . "%' AND clfoco = " . $form['clfoco'] . " AND numfat = '" . $form['numfat'] . "'", "protoc desc, datfat desc, datemi desc", 0, 1);
							$stesso_numero = gaz_dbi_fetch_array($rs_stesso_numero);
							if ($stesso_numero) {
								$msg['err'][] = "samedoc";
							}
						}
					}
				}
            }
        }
// --- fine controllo coerenza date-numeri
        if (empty($form["clfoco"]))
            $msg['err'][] = "noforn";
        if (empty($form["pagame"]))
            $msg['err'][] = "nopaga";
//controllo che i righi non abbiano descrizioni  e unita' di misura vuote in presenza di quantita diverse da 0
        foreach ($form['rows'] as $i => $value) {
            if ($value['descri'] == '' &&
                $value['tiprig'] <= 1) {
                $msgrigo = $i + 1;
                $msg['err'][] = "norwde";
            }
            if ($value['unimis'] == '' &&
                    $value['quanti'] &&
                    $value['tiprig'] == 0) {
                $msgrigo = $i + 1;
                $msg['err'][] = "norwum";
            }
			if ($value['SIAN']>0){ // se movimento SIAN Faccio i reletivi controlli
				if ($value['cod_operazione'] < 0 || $value['cod_operazione']==11){ // controllo se è stato inserito il codice operazione SIAN
					$msgrigo = $i + 1;
					$msg['err'][] = "nocod_operaz";
				}
				$clfoco = gaz_dbi_get_row($gTables['clfoco'], "codice", $form['clfoco']);
				$anagra = gaz_dbi_get_row($gTables['anagra'], "id", $clfoco['id_anagra']);
				if ($anagra['id_SIAN']<=0 && $value['cod_operazione']<>12){ // controllo se il fornitore ha il codice SIAN solo se non è Campionamento/analisi
					$msgrigo = $i + 1;
					$msg['err'][] = "nofor_sian";
				}
				$art = gaz_dbi_get_row($gTables['camp_artico'], "codice", $value['codart']);
				if ($value['cod_operazione'] == 9 AND strlen($value['recip_stocc_destin'])==0 AND $art['confezione']==0){
					$msgrigo = $i + 1;
					$msg['err'][] = "norecipdestin"; // manca il recipiente di destinazione
				}
				if ($value['cod_operazione'] == 8 AND $art['confezione']==0){
					$msgrigo = $i + 1;
					$msg['err'][] = "soloconf"; // Operazione effettuabile solo con colio confezionato
				}
				if (strlen($value['recip_stocc'])==0 AND $art['confezione']==0){
					$msgrigo = $i + 1;
					$msg['err'][] = "norecipstocc"; // manca il recipiente di stoccaggio
				}
				if (strlen($value['recip_stocc'])==0 AND $value['cod_operazione']==10){
					$msgrigo = $i + 1;
					$msg['err'][] = "norecipstocc"; // manca il recipiente di stoccaggio
				}
				if (strlen($value['recip_stocc'])>0){
					$content=$sil->getCont($value['recip_stocc']);
					if ($toDo == 'update'){
						$content=$content-$value['quanti'];
					}
					$recip = gaz_dbi_get_row($gTables['camp_recip_stocc'], "cod_silos", $value['recip_stocc']);
					if ($content+$value['quanti']>$recip['capacita']){
						$msg['err'][] = "capsuperata"; // Superata la capacità del recipiente
					}
				}
				if (strlen($value['recip_stocc_destin'])>0){
					$content=$sil->getCont($value['recip_stocc_destin']);
					if ($toDo == 'update'){
						$content=$content-$value['quanti'];
					}
					$recip = gaz_dbi_get_row($gTables['camp_recip_stocc'], "cod_silos", $value['recip_stocc_destin']);
					if ($content+$value['quanti']>$recip['capacita']){
						$msg['err'][] = "capsuperata"; // Superata la capacità del recipiente
					}
				}
			}
        }
        if (count($msg['err']) == 0) {// nessun errore
            if (preg_match("/^id_([0-9]+)$/", $form['clfoco'], $match)) {
                  $new_clfoco = $anagrafica->getPartnerData($match[1], 1);
                  $form['clfoco'] = $anagrafica->anagra_to_clfoco($new_clfoco, $admin_aziend['masfor'],$form['pagame']);
            }
            function getProtocol($type, $year, $sezione) {  // questa funzione trova l'ultimo numero di protocollo                                           // controllando sia l'archivio documenti che il
                global $gTables;                      // registro IVA acquisti
                $rs_ultimo_tesdoc = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datreg) = $year AND tipdoc LIKE '" . substr($type, 0, 2) . "_' AND seziva = ".$sezione, "protoc DESC", 0, 1);
                $ultimo_tesdoc = gaz_dbi_fetch_array($rs_ultimo_tesdoc);
                $rs_ultimo_tesmov = gaz_dbi_dyn_query("*", $gTables['tesmov'], "YEAR(datreg) = $year AND regiva = 6 AND seziva = $sezione", "protoc DESC", 0, 1);
                $ultimo_tesmov = gaz_dbi_fetch_array($rs_ultimo_tesmov);
                $lastProtocol = 0;
                if ($ultimo_tesdoc) {
                    $lastProtocol = $ultimo_tesdoc['protoc'];
                }
                if ($ultimo_tesmov) {
                    if ($ultimo_tesmov['protoc'] > $lastProtocol) {
                        $lastProtocol = $ultimo_tesmov['protoc'];
                    }
                }
                return $lastProtocol + 1;
            }

            $initra .= " " . $form['oratra'] . ":" . $form['mintra'] . ":00";
            $form['spediz'] = addslashes($form['spediz']);
            $form['portos'] = addslashes($form['portos']);
            $form['imball'] = addslashes($form['imball']);
            $form['destin'] = addslashes($form['destin']);
            $form['datreg'] = gaz_format_date($form['datreg'],true);
            $form['datfat'] = gaz_format_date($form['datfat'],true);
            $form['datemi'] = gaz_format_date($form['datemi'],true);
            if ($toDo == 'update') { // e' una modifica
                $old_rows = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = " . $form['id_tes'], "id_rig asc");

                $i = 0;
                $count = count($form['rows']) - 1;
                while ($val_old_row = gaz_dbi_fetch_array($old_rows)) {

                // per evitare problemi qualora siano stati modificati i righi o comunque cambiati di ordine elimino sempre il vecchio movimento di magazzino e sotto ne inserisco un altro attenendomi a questo
                    if (intval($val_old_row['id_mag']) > 0) {  //se c'è stato un movimento di magazzino lo azzero
                        $magazz->uploadMag('DEL', $form['tipdoc'], '', '', '', '', '', '', '', '', '', '', $val_old_row['id_mag'], $admin_aziend['stock_eval_method']);
                        // se c'è stato, azzero pure il movimento sian
                        gaz_dbi_del_row($gTables['camp_mov_sian'], "id_movmag", $val_old_row['id_mag']);
                    }
                    if ($form['tipdoc'] == 'AFA' || $form['tipdoc'] == 'AFC') { // su fatture immediate e note credito metto il numero documento ugale al numero fatture
                        $form['numdoc'] = $form['numfat'];
                    }
                    if ($i <= $count) { //se il vecchio rigo e' ancora presente nel nuovo lo modifico
                        $form['rows'][$i]['id_tes'] = $form['id_tes'];
                        $codice = array('id_rig', $val_old_row['id_rig']);
                        unset($form['rows'][$i]['status']);
                        rigdocUpdate($codice, $form['rows'][$i]);
                        if (isset($form["row_$i"]) && $val_old_row['id_body_text'] > 0) { //se è un rigo testo già presente lo modifico
                            bodytextUpdate(array('id_body', $val_old_row['id_body_text']), array('table_name_ref' => 'rigdoc', 'id_ref' => $val_old_row['id_rig'], 'body_text' => $form["row_$i"], 'lang_id' => $admin_aziend['id_language']));
                            gaz_dbi_put_row($gTables['rigdoc'], 'id_rig', $val_old_row['id_rig'], 'id_body_text', $val_old_row['id_body_text']);
                        } elseif (isset($form["row_$i"]) && $val_old_row['id_body_text'] == 0) { //prima era un rigo diverso da testo
                            bodytextInsert(array('table_name_ref' => 'rigdoc', 'id_ref' => $val_old_row['id_rig'], 'body_text' => $form["row_$i"], 'lang_id' => $admin_aziend['id_language']));
                            gaz_dbi_put_row($gTables['rigdoc'], 'id_rig', $val_old_row['id_rig'], 'id_body_text', gaz_dbi_last_id());
                        } elseif (!isset($form["row_$i"]) && $val_old_row['id_body_text'] > 0) { //un rigo che prima era testo adesso non lo è più
                            gaz_dbi_del_row($gTables['body_text'], "table_name_ref = 'rigdoc' AND id_ref", $val_old_row['id_rig']);
                        }
                        if  (( $form['rows'][$i]['tiprig']==51 || $form['rows'][$i]['tiprig']==50 ) && !empty($form['rows'][$i]['extdoc'])) {
                          if (file_exists(DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/tmp/'.$i.'_rigdoc_'.$form['rows'][$i]['extdoc'] )) { // se ho scelto un altro file cancello tutti i vecchi e sposto il nuovo
                            // se ho scelto un altro file cancello tutti i vecchi e sposto il nuovo
                            $files = glob( DATA_DIR.'files/'.$admin_aziend['company_id'].'/doc/'.$val_old_row['id_rig'].'_rigdoc_*.*');
                            foreach($files as $file) { unlink($file); }
                            rename( DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/tmp/'.$i.'_rigdoc_'.$form['rows'][$i]['extdoc']  , DATA_DIR.'files/'.$admin_aziend['company_id'].'/doc/'.$val_old_row['id_rig'].'_rigdoc_'.$form['rows'][$i]['extdoc']);
                          }
                        }
                      // Antonio Germani - inizio salvataggio lotti e magazzino
                      if ($form['rows'][$i]['lot_or_serial'] > 0){ // se l'articolo prevede lotti

                        if ($form['rows'][$i]['expiry']>0){ // se c'è una scadenza
                          $form['rows'][$i]['expiry']=gaz_format_date($form['rows'][$i]['expiry'],true);// converto la data di scadenza per mysql
                        } else { // se non c'è la imposto a zero
                          $form['rows'][$i]['expiry']="0000-00-00 00:00:00";
                        }
                        if (strlen ($form['rows'][$i]['identifier'])<1){ // se non è stato inserito un identificativo lotto lo inserisco d'ufficio
                          $form['rows'][$i]['identifier'] = time();
                        }

                        // Anche se UPDATE verrà comunque rigenerato il movimento di magazzino
                        // quindi vedo in anticipo in quale ID verrà memorizzato il prossimo movimento di magazzino
                        if ($form['tipdoc']=="DDR" || $form['tipdoc']=="DDL"){
                          $id_lotmag=$form['rows'][$i]['id_lotmag'];
                        } else {
                          if ($toDo == 'update'){ // se è UPDATE
                            $check_lot= gaz_dbi_query("SELECT id FROM " . $gTables['lotmag'] . " WHERE id_movmag = '" . $form['rows'][$i]['id_mag']."'");// controllo se il lotto inserito nel form esiste già
                            $rowc = $check_lot->fetch_assoc();
                            if (isset($rowc['id']) && $rowc['id']>0) {  // se il lotto c'era lo aggiorno
                              $id_lotmag=$rowc['id']; // ne prendo l'id che andrò a memorizzare nel movimento di magazzino ancora da riscrivere
                              gaz_dbi_query("UPDATE " . $gTables['lotmag'] . " SET codart = '" . $form['rows'][$i]['codart'] . "' , id_rigdoc = '". $form['rows'][$i]['id_rig'] ."', identifier = '" . $form['rows'][$i]['identifier'] . "', expiry = '". $form['rows'][$i]['expiry'] ."' WHERE id = '" . $id_lotmag . "'");
                            }elseif(intval($val_old_row['id_lotmag'])>0){// se il vecchio rigo aveva gia un id_lotmag, visto che potrebbero esserci stati dei movimenti di questo id lotto, lo lascio modificandolo
                              $id_lotmag=intval($val_old_row['id_lotmag']); // ne prendo l'id che andrò a memorizzare nel movimento di magazzino ancora da riscrivere
                              gaz_dbi_query("UPDATE " . $gTables['lotmag'] . " SET codart = '" . $form['rows'][$i]['codart'] . "' , id_rigdoc = '". $form['rows'][$i]['id_rig'] ."', identifier = '" . $form['rows'][$i]['identifier'] . "', expiry = '". $form['rows'][$i]['expiry'] ."' WHERE id = '" . $id_lotmag . "'");

                            }else { // se non c'era creo il rigo lotto nella tabella lotmag

                              gaz_dbi_query("INSERT INTO " . $gTables['lotmag'] . "(id_rigdoc,codart,identifier,expiry) VALUES ('" . $form['rows'][$i]['id_rig'] . "','" . $form['rows'][$i]['codart'] . "','" . $form['rows'][$i]['identifier'] . "','" . $form['rows'][$i]['expiry'] . "')");
                              $id_lotmag=gaz_dbi_last_id();
                            }
                          } else { // se è INSERT creo il rigo lotto nella tabella lotmag

                            gaz_dbi_query("INSERT INTO " . $gTables['lotmag'] . "(id_rigdoc,codart,identifier,expiry) VALUES ('" . $form['rows'][$i]['id_rig'] . "','" . $form['rows'][$i]['codart'] . "','" . $form['rows'][$i]['identifier'] . "','" . $form['rows'][$i]['expiry'] . "')");
                            $id_lotmag=gaz_dbi_last_id();
                          }

                          // aggiorno pure i documenti relativi ai lotti
                          $old_lm = gaz_dbi_get_row($gTables['lotmag'], 'id', $id_lotmag);
                          if ($old_lm && substr($form['rows'][$i]['filename'], 0, 7) <> 'lotmag_') {
                            // se a questo rigo corrispondeva un certificato controllo che però è stato aggiornato lo cambio
                            $dh = opendir( DATA_DIR . 'files/' . $admin_aziend['company_id'] );
                            while (false !== ($filename = readdir($dh))) {
                              $fd = pathinfo($filename);
                              if ($fd['filename'] == 'lotmag_' . $old_lm['id']) {
                                // cancello il file precedente indipendentemente dall'estensione
                                $frep = glob( DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/lotmag_' . $old_lm['id'] . '.*');
                                foreach ($frep as $fdel) {// prima cancello eventuali precedenti file temporanei
                                  unlink($fdel);
                                }
                              }
                            }
                            $tmp_file = DATA_DIR . 'files/tmp/' . $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $i . '_' . $form['rows'][$i]['filename'];
                            // sposto e rinomino il relativo file temporaneo
                            if ($form['rows'][$i]['filename']){
                              $fn = pathinfo($form['rows'][$i]['filename']);
                              rename($tmp_file, DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/lotmag_' . $old_lm['id'] . '.' . $fn['extension']);
                            }
                          }
                        }
                      } else { // se l'articolo non prevede lotti
                          $id_lotmag=0;
                      }

                        if ($form['rows'][$i]['tiprig'] <> 2 && $admin_aziend['conmag'] == 2) { // se NON è un rigo descrittivo e se non ho la contabilità automatica di magazzino
                        // reinserisco il movimento magazzino associato e lo aggiorno
                          $id_movmag=$magazz->uploadMag($val_old_row['id_rig'], $form['tipdoc'], $form['numdoc'], $form['seziva'], $datemi, $form['clfoco'], $form['sconto'], $form['caumag'], $form['rows'][$i]['codart'], $form['rows'][$i]['quanti'], $form['rows'][$i]['prelis'], $form['rows'][$i]['sconto'], 0, $admin_aziend['stock_eval_method'], false, $form['protoc'],$id_lotmag,0,0,'',$form['rows'][$i]['id_warehouse']);

                          gaz_dbi_put_row($gTables['rigdoc'], 'id_rig', $val_old_row['id_rig'], 'id_mag', $id_movmag);// metto il nuovo id_mag nel rigo documento

                          if ($form['rows'][$i]['lot_or_serial'] > 0 && ($form['tipdoc']!="DDR" || $form['tipdoc']!="DDL")){ // se l'articolo ha un lotto metto l'id_movmag di riferimento nel lotto
                            gaz_dbi_put_row($gTables['lotmag'], 'id', $id_lotmag, 'id_movmag', $id_movmag);
                          }
                          if ($form['rows'][$i]['SIAN'] > 0) { // se l'articolo deve movimentare il SIAN creo anche il movimento
                            if ($form['tipdoc']=="DDL" && intval($form['rows'][$i]['cod_operazione'])==12) {// se è scarico per conto lavorazione e campionamento
                              $form['rows'][$i]['cod_operazione']="P";
                            }
                            $value_sian['cod_operazione']= $form['rows'][$i]['cod_operazione'];
                            $value_sian['recip_stocc']= $form['rows'][$i]['recip_stocc'];
                            $value_sian['recip_stocc_destin']= $form['rows'][$i]['recip_stocc_destin'];
                            $value_sian['id_movmag']=$id_movmag;
                            $value_sian['varieta']=$form['rows'][$i]['quality'];
                            gaz_dbi_table_insert('camp_mov_sian', $value_sian);
                          }
                        }
                      if ( strlen($form['rows'][$i]['codart']) >= 1  && $admin_aziend['conmag'] == 2 ) { // se l'articolo è in magazzino (codart è valorizzato) aggiorno l'anagrafica articolo movimentato con l'ultimo costo in anagrafica articolo, presumibilmente questo
                        $rlb = $magazz->getLastBuys($form['rows'][$i]['codart']);
                        $rlbk = key($rlb);
                        gaz_dbi_put_row( $gTables['artico'], 'codice', $form['rows'][$i]['codart'], 'preacq', round($rlb[$rlbk]['prezzo']*(100-$rlb[$rlbk]['scorig'])/100,8) );
                      }
                    } else { //altrimenti lo elimino
                      if ($val_old_row['id_mag'] > 0) {  //se c'era stato un movimento di magazzino lo azzero
                        $magazz->uploadMag('DEL', $form['tipdoc'], '', '', '', '', '', '', '', '', '', '', $val_old_row['id_mag'], $admin_aziend['stock_eval_method']);
                        // se c'è stato, azzero pure il movimento sian
                        gaz_dbi_del_row($gTables['camp_mov_sian'], "id_movmag", $val_old_row['id_mag']);
                      }
                      gaz_dbi_del_row($gTables['rigdoc'], "id_rig", $val_old_row['id_rig']);
                    }
                    $i++;
                }
//qualora i nuovi righi fossero di più dei vecchi inserisco l'eccedenza
                for ($i = $i; $i <= $count; $i++) {
                  $form['rows'][$i]['id_tes'] = $form['id_tes'];
                    $last_rigdoc_id =rigdocInsert($form['rows'][$i]);
                    // INIZIO INSERIMENTO DOCUMENTI ALLEGATI
                    if  (( $form['rows'][$i]['tiprig']==51 || $form['rows'][$i]['tiprig']==50 ) && !empty($form['rows'][$i]['extdoc'])) {
                      if (file_exists(DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/tmp/'.$i.'_rigdoc_'.$form['rows'][$i]['extdoc'] )) {
                        // se ho scelto un altro file cancello tutti i vecchi e sposto il nuovo
                        $files = glob( DATA_DIR.'files/'.$admin_aziend['company_id'].'/doc/'.$last_rigdoc_id.'_rigdoc_*.*');
                        foreach($files as $file) { unlink($file); }
                        rename( DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/tmp/'.$i.'_rigdoc_'.$form['rows'][$i]['extdoc']  , DATA_DIR.'files/'.$admin_aziend['company_id'].'/doc/'.$last_rigdoc_id.'_rigdoc_'.$form['rows'][$i]['extdoc']);
                      }
                    }
                    // FINE INSERIMENTO DOCUMENTI ALLEGATI
                      if ($admin_aziend['conmag'] == 2 &&
                            $form['rows'][$i]['tiprig'] == 0 &&
                            $form['rows'][$i]['gooser'] == 0 &&
                            !empty($form['rows'][$i]['codart'])) { //se l'impostazione in azienda prevede l'aggiornamento automatico dei movimenti di magazzino
                          $last_movmag_id = $magazz->uploadMag($last_rigdoc_id, $form['tipdoc'], $form['numdoc'], $form['seziva'], $datemi, $form['clfoco'], $form['sconto'], $form['caumag'], $form['rows'][$i]['codart'], $form['rows'][$i]['quanti'], $form['rows'][$i]['prelis'], $form['rows'][$i]['sconto'], 0, $admin_aziend['stock_eval_method'], false, $form['protoc'],0,0,0,$form['rows'][$i]['id_orderman']);
                  gaz_dbi_put_row($gTables['rigdoc'], 'id_rig', $last_rigdoc_id, 'id_mag', $last_movmag_id);
                  if ($form['rows'][$i]['SIAN'] > 0) { // se l'articolo deve movimentare il SIAN creo anche il movimento
                    if ($form['tipdoc']=="DDL" && intval($form['rows'][$i]['cod_operazione'])==12) {// se è scarico per conto lavorazione e campionamento
                      $form['rows'][$i]['cod_operazione']="P";
                    }
                    $value_sian['cod_operazione']= $form['rows'][$i]['cod_operazione'];
                    $value_sian['recip_stocc']= $form['rows'][$i]['recip_stocc'];
                    $value_sian['recip_stocc_destin']= $form['rows'][$i]['recip_stocc_destin'];
                    $value_sian['id_movmag']=$last_movmag_id;
                    $value_sian['varieta']=$form['rows'][$i]['quality'];
                    gaz_dbi_table_insert('camp_mov_sian', $value_sian);
                  }
                    }
// se l'articolo prevede la gestione dei  lotti o della matricola/numero seriale creo un rigo in lotmag
// ed eventualmente sposto e rinomino il relativo documento dalla dir temporanea a quella definitiva
                    if ($form['rows'][$i]['lot_or_serial'] > 0) {
                        $form['rows'][$i]['id_rigdoc'] = $last_rigdoc_id;
                        $form['rows'][$i]['id_movmag'] = $last_movmag_id;
                        $form['rows'][$i]['expiry'] = gaz_format_date($form['rows'][$i]['expiry'], true);
                        if (empty($form['rows'][$i]['identifier'])) {
// creo un identificativo del lotto/matricola interno
                            $form['rows'][$i]['identifier'] = $form['datemi'] . '_' . $form['rows'][$i]['id_rigdoc'];
                        }
                        $last_lotmag_id = lotmagInsert($form['rows'][$i]);
                        // inserisco il riferimento anche sul relativo movimento di magazzino
                        gaz_dbi_put_row($gTables['movmag'], 'id_mov', $last_movmag_id, 'id_lotmag', $last_lotmag_id);
                        if (!empty($form['rows'][$i]['filename'])) {
                            $tmp_file = DATA_DIR . 'files/tmp/' . $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $i . '_' . $form['rows'][$i]['filename'];
// sposto e rinomino il relativo file temporaneo
                            $fd = pathinfo($form['rows'][$i]['filename']);
                            rename($tmp_file, DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/lotmag_' . $last_lotmag_id . '.' . $fd['extension']);
                        }
                    }
                  if ( strlen($form['rows'][$i]['codart']) >= 1 ) { // se l'articolo è in magazzino (codart è valorizzato) aggiorno l'anagrafica articolo movimentato con l'ultimo costo in anagrafica articolo, presumibilmente questo
                    $rlb = $magazz->getLastBuys($form['rows'][$i]['codart']);
                    $rlbk=key($rlb);
                    gaz_dbi_put_row( $gTables['artico'], 'codice', $form['rows'][$i]['codart'], 'preacq', round($rlb[$rlbk]['prezzo']*(100-$rlb[$rlbk]['scorig'])/100,8) );
                  }
                }
//modifico la testata con i nuovi dati...
                $old_head = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', $form['id_tes']);
                if (substr($form['tipdoc'], 0, 2) == 'DD') { //se è un DDT non fatturato
                    $form['numfat'] = '';
                }
                $form['geneff'] = $old_head['geneff'];
                $form['id_contract'] = $old_head['id_contract'];
                $form['id_con'] = $old_head['id_con'];
                $form['status'] = $old_head['status'];
                $form['initra'] = $initra;
                $form['datemi'] = $datemi;
                $form['id_orderman'] = $form['in_id_orderman'];
                $codice = array('id_tes', $form['id_tes']);
                tesdocUpdate($codice, $form);
                $prefix = $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'];
// prima di uscire cancello eventuali precedenti file temporanei
                foreach (glob( DATA_DIR . 'files/tmp/' . $prefix . '_*.*') as $fn) {
                    unlink($fn);
                }
                header('Location: ' . $form['ritorno']);
                exit;
            } else { // e' un'inserimento
              if ($ddtchecked>0){ // se ci sono DDT a riferimento fattura
                $updtesdoc['protoc'] = getProtocol($form['tipdoc'], substr($form['datreg'],0,4), $sezione);
                for ($ddtrow=0 ; $ddtrow<=$_POST['num_ddt']; $ddtrow++){ // ciclo i ddt

                  if ($_POST['check_ddt'.$ddtrow]=="checked"){ // se è stato selezionato il ddt lo trasformo in fattura
                    $codice = array('id_tes', $form['id_tes'.$ddtrow]);
                    $updtesdoc['datreg']=$form['datreg'];$updtesdoc['ddt_type']="T";$updtesdoc['tipdoc']="AFT";$updtesdoc['numfat']=$form['numfat'];$updtesdoc['datfat']=$form['datfat'];

                    tesdocUpdate($codice, $updtesdoc);

                    $query = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes=".$form['id_tes'.$ddtrow], "id_rig asc");
                    $i=0;

                    foreach ($form['rows'] as $row){
                      $codice = array('id_rig', $row['id_rig']);
                      rigdocUpdate($codice, $row);
                      $i++;
                    }

                  }
                }
                header("Location: " . $form['ritorno']);
                exit;
              } else {
      // ricavo i progressivi in base al tipo di documento
                $where = "numdoc desc";
                switch ($form['tipdoc']) {
                  case "DDR":
                    $sql_documento = "YEAR(datemi) = " . substr($datemi,0,4) . " AND ( tipdoc like 'DD_' OR tipdoc = 'FAD') AND seziva = $sezione";
                    break;
                  case "DDL":
                    $sql_documento = "YEAR(datemi) = " . substr($datemi,0,4) . " AND ( tipdoc like 'DD_' OR tipdoc = 'FAD') AND seziva = $sezione";
                    break;
                  case "AFA":
                    $sql_documento = "YEAR(datemi) = " . substr($datemi,0,4) . " AND tipdoc like 'AFA' AND seziva = $sezione";
                    $where = "numfat desc";
                    break;
                  case "ADT":
                    $sql_documento = "YEAR(datemi) = " . substr($datemi,0,4) . " AND tipdoc like 'ADT' AND seziva = $sezione";
                    break;
                  case "AFC":
                    $sql_documento = "YEAR(datemi) = " . substr($datemi,0,4) . " AND tipdoc = 'AFC' AND seziva = $sezione";
                    $where = "numfat desc";
                    break;
                  case "RDL": // Antonio Germani aggiunto case RDL perché si creava un "Notice: Undefined variable: sql_documento
                    $sql_documento = "YEAR(datemi) = " . substr($datemi,0,4);
                    break;
                }
                $rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesdoc'], $sql_documento, $where, 0, 1);
                $ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
        // se e' il primo documento dell'anno, resetto il contatore
                if ($form['tipdoc']=='ADT') {  //ma se e' un ddt a fornitore il protocollo è 0 così come il numero e data fattura
                  $form['protoc'] = 0;
                  $form['numfat'] = '';
                  $form['datfat'] = $datemi;
                } elseif ($form['tipdoc']=='RDL') {  //se e' un ddt di ritorno da lavorazione non modifico il numero che ho inserito sul form
                } elseif ($ultimo_documento) {
                  $form['numdoc'] = $ultimo_documento['numdoc'] + 1;
                } else {
                  $form['numdoc'] = 1;
                }
                if (substr($form['tipdoc'], 0, 2) == 'DD') {  //ma se e' un ddt a fornitore il protocollo è 0 così come il numero e data fattura
                  $form['protoc'] = 0;
                  $form['numfat'] = '';
                } else if (substr($form['tipdoc'], 0, 2) == 'AF') {
                  $form['protoc'] = getProtocol($form['tipdoc'], substr($form['datreg'],0,4), $sezione);
                } else { //in tutti gli altri casi si deve prendere quanto inserito nel form
                  $form['protoc'] = getProtocol($form['tipdoc'], substr($datemi,0,4), $sezione);
                }
        //inserisco la testata
                $form['status'] = '';
                $form['initra'] = $initra;
                $form['datemi'] = $datemi;
                $form['id_orderman'] = $form['in_id_orderman'];
                $ultimo_id = tesdocInsert($form);
        //inserisco i righi

                foreach ($form['rows'] as $i => $value) {
                  $form['rows'][$i]['id_tes'] = $ultimo_id;
                  $last_rigdoc_id=rigdocInsert($form['rows'][$i]);
                  // INIZIO INSERIMENTO DOCUMENTI ALLEGATI
                  if  (( $form['rows'][$i]['tiprig']==51 || $form['rows'][$i]['tiprig']==50 ) && !empty($form['rows'][$i]['extdoc'])) {
                    if (file_exists(DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/tmp/'.$i.'_rigdoc_'.$form['rows'][$i]['extdoc'] )) { // se ho scelto un altro file cancello tutti i vecchi e sposto il nuovo
                      // se ho scelto un altro file cancello tutti i vecchi e sposto il nuovo
                      $files = glob( DATA_DIR.'files/'.$admin_aziend['company_id'].'/doc/'.$last_rigdoc_id.'_rigdoc_*.*');
                      foreach($files as $file) { unlink($file); }
                      rename( DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/tmp/'.$i.'_rigdoc_'.$form['rows'][$i]['extdoc']  , DATA_DIR.'files/'.$admin_aziend['company_id'].'/doc/'.$last_rigdoc_id.'_rigdoc_'.$form['rows'][$i]['extdoc']);
                    }
                  }
                  // FINE INSERIMENTO DOCUMENTI ALLEGATI
                  if (isset($form["row_$i"])) { //se è un rigo testo lo inserisco il contenuto in body_text
                    $last_bodytext_id=bodytextInsert(array('table_name_ref' => 'rigdoc', 'id_ref' => $last_rigdoc_id, 'body_text' => $form["row_$i"], 'lang_id' => $admin_aziend['id_language']));
                    gaz_dbi_put_row($gTables['rigdoc'], 'id_rig', $last_rigdoc_id, 'id_body_text',$last_bodytext_id);
                  }
                  if ($admin_aziend['conmag'] == 2 &&
                      $form['rows'][$i]['tiprig'] == 0 &&
                      $form['rows'][$i]['gooser'] != 1 &&
                      !empty($form['rows'][$i]['codart'])) { //se l'impostazione in azienda prevede l'aggiornamento automatico dei movimenti di magazzino
                    $last_movmag_id = $magazz->uploadMag($last_rigdoc_id, $form['tipdoc'], $form['numdoc'], $form['seziva'], $datemi, $form['clfoco'], $form['sconto'], $form['caumag'], $form['rows'][$i]['codart'], $form['rows'][$i]['quanti'], $form['rows'][$i]['prelis'], $form['rows'][$i]['sconto'], 0, $admin_aziend['stock_eval_method'], false, $form['protoc'],0,$form['rows'][$i]['id_orderman'],0,'', $form['rows'][$i]['id_warehouse']);
                    gaz_dbi_put_row($gTables['rigdoc'], 'id_rig', $last_rigdoc_id, 'id_mag',$last_movmag_id);
                  }
                  // se l'articolo prevede la gestione dei  lotti o della matricola/numero seriale creo un rigo in lotmag
                  // ed eventualmente sposto e rinomino il relativo documento dalla dir temporanea a quella definitiva
                  if ($form['rows'][$i]['lot_or_serial'] > 0) {
                    if ($form['tipdoc']=="DDR" || $form['tipdoc']=="DDL"){ // se è un lotto in uscita
                      $id_lotmag=$form['rows'][$i]['id_lotmag'];
                      gaz_dbi_put_row($gTables['movmag'], 'id_mov', $last_movmag_id, 'id_lotmag', $id_lotmag);
                    } else {// se è un lotto in entrata
                      $form['rows'][$i]['id_rigdoc'] = $last_rigdoc_id;
                      $form['rows'][$i]['id_movmag'] = $last_movmag_id;
                      if (intval($form['rows'][$i]['expiry'])>0){
                        $form['rows'][$i]['expiry'] = gaz_format_date($form['rows'][$i]['expiry'], true);
                      } else {
                        $form['rows'][$i]['expiry']="0000-00-00 00:00:00";
                      }
                      if (empty($form['rows'][$i]['identifier'])) {
          // creo un identificativo del lotto/matricola interno
                        $form['rows'][$i]['identifier'] = $form['datemi'] . '_' . $form['rows'][$i]['id_rigdoc'];
                      }
                      $last_lotmag_id = lotmagInsert($form['rows'][$i]);
                      // inserisco il riferimento anche sul relativo movimento di magazzino
                      gaz_dbi_put_row($gTables['movmag'], 'id_mov', $last_movmag_id, 'id_lotmag', $last_lotmag_id);
                      if (!empty($form['rows'][$i]['filename'])) {
                        $tmp_file = DATA_DIR . 'files/tmp/' . $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $i . '_' . $form['rows'][$i]['filename'];
          // sposto e rinomino il relativo file temporaneo
                        $fd = pathinfo($form['rows'][$i]['filename']);
                        rename($tmp_file, DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/lotmag_' . $last_lotmag_id . '.' . $fd['extension']);
                      }
                    }
                  }
                  if ($form['rows'][$i]['SIAN'] > 0) { // se l'articolo deve movimentare il SIAN creo il movimento
                    if ($form['tipdoc']=="DDL" && intval($form['rows'][$i]['cod_operazione'])==12) {// se è scarico per conto lavorazione e campionamento
                      $form['rows'][$i]['cod_operazione']="P";
                    }
                    $value_sian['cod_operazione']= $form['rows'][$i]['cod_operazione'];
                    $value_sian['recip_stocc']= $form['rows'][$i]['recip_stocc'];
                    $value_sian['recip_stocc_destin']= $form['rows'][$i]['recip_stocc_destin'];
                    $value_sian['id_movmag']=$last_movmag_id;
                    $value_sian['varieta']=$form['rows'][$i]['quality'];
                    gaz_dbi_table_insert('camp_mov_sian', $value_sian);
                  }
                  if ( strlen($form['rows'][$i]['codart']) >= 1 ) { // se l'articolo è in magazzino (codart è valorizzato) aggiorno l'anagrafica articolo movimentato con l'ultimo costo in anagrafica articolo, presumibilmente questo
                    $rlb = $magazz->getLastBuys($form['rows'][$i]['codart']);
                    $rlbk=key($rlb);
                    gaz_dbi_put_row( $gTables['artico'], 'codice', $form['rows'][$i]['codart'], 'preacq', round($rlb[$rlbk]['prezzo']*(100-$rlb[$rlbk]['scorig'])/100,8) );
                  }
                }

                $prefix = $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'];
        // prima di uscire cancello eventuali precedenti file temporanei
                foreach (glob(DATA_DIR . 'files/tmp/' . $prefix . '_*.*') as $fn) {
                  unlink($fn);
                }
                $_SESSION['print_request'] = $ultimo_id;
                if ($pdf_to_modal==0){
                  header('Location: invsta_docacq.php');
                  exit;
                }
              }
            }
        }
    }
// Se viene inviata la richiesta di conferma fornitore
    if ($_POST['hidden_req'] == 'clfoco') {
        $anagrafica = new Anagrafica();
        if (preg_match("/^id_([0-9]+)$/", $form['clfoco'], $match)) {
            $fornitore = $anagrafica->getPartnerData($match[1], 1);
        } else {
            $fornitore = $anagrafica->getPartner($form['clfoco']);
        }
        if (substr($form['tipdoc'], 0, 1) != 'A') {
          $result = gaz_dbi_get_row($gTables['imball'], "codice", $fornitore['imball']);
          $form['imball']=($result)?$result['descri']:'';
        }
        $result = gaz_dbi_get_row($gTables['portos'], "codice", $fornitore['portos']);
        $form['portos']=($result)?$result['descri']:'';
        $result = gaz_dbi_get_row($gTables['spediz'], "codice", $fornitore['spediz']);
        $form['spediz']=($result)?$result['descri']:'';
        $form['destin'] = $fornitore['destin'];
        $form['id_des'] = $fornitore['id_des'];
        $id_des = $anagrafica->getPartner($form['id_des']);
        $form['search']['id_des']=($id_des)?substr($id_des['ragso1'], 0, 10):'';
        if ($fornitore['aliiva'] > 0) {
            $form['ivaspe'] = $fornitore['aliiva'];
            $result = gaz_dbi_get_row($gTables['aliiva'], 'codice', $fornitore['aliiva']);
            $form['pervat'] = $result['aliquo'];
        }
        $form['in_codvat'] = $fornitore['aliiva'];
        $form['sconto'] = $fornitore['sconto'];
        $form['pagame'] = $fornitore['codpag'];
        $form['change_pag'] = $fornitore['codpag'];
        $form['banapp'] = $fornitore['banapp'];
        $form['listin'] = $fornitore['listin'];
        $form['address'] = $fornitore['indspe'] . ' ' . $fornitore['citspe'];
        $pagame = gaz_dbi_get_row($gTables['pagame'], "codice", $form['pagame']);
        if ($pagame && ($pagame['tippag'] == 'B' || $pagame['tippag'] == 'T' || $pagame['tippag'] == 'V') && $fornitore['speban'] == 'S') {
            $form['speban'] = 0;
            $form['numrat'] = $pagame['numrat'];
        } else {
            $form['speban'] = 0.00;
            $form['numrat'] = 1;
        }
        if ($fornitore['cosric'] > 0) {
            $form['in_codric'] = $fornitore['cosric'];
        }
        if ($fornitore['ritenuta'] > 0 ) { // carico la ritenuta se previsto
            $form['in_ritenuta'] = $fornitore['ritenuta'];
        }
        $form['hidden_req'] = '';
    }

// Se viene inviata la richiesta di conferma rigo
    if (isset($_POST['in_submit'])) {
        $artico = gaz_dbi_get_row($gTables['artico'], "codice", $form['in_codart']);
        if (!$artico) {
          $artico['peso_specifico']=0;
          $artico['volume_specifico']=0;
          $artico['pack_units']=0;
          $artico['annota']='';
          $artico['peso_specifico']=0;
          $artico['quality']='';
          $artico['good_or_service']='';
          $artico['uniacq']='';
          $artico['scorta']=0;
          $artico['descri']='';
          $artico['codice_fornitore']='';
          $artico['lot_or_serial']=0;
          $artico['SIAN']='';
          $artico['preacq']=0;
          $artico['aliiva']=0;
          $artico['id_cost']=0;
          $artico['sconto']=0;
        }
// addizione ai totali peso,pezzi,volume
        $form['net_weight'] += $form['in_quanti'] * $artico['peso_specifico'];
        $form['gross_weight'] += $form['in_quanti'] * $artico['peso_specifico'];
        if ($artico['pack_units'] > 0) {
            $form['units'] += intval(round($form['in_quanti'] / $artico['pack_units']));
        }
        $form['volume'] += $form['in_quanti'] * $artico['volume_specifico'];
// fine addizione peso,pezzi,volume
        $idAnagrafe = (isset($fornitore['id_anagra']))?$fornitore['id_anagra']:0;
        $rs_query_destinazioni = gaz_dbi_dyn_query("*", $gTables['destina'], "id_anagra='$idAnagrafe'");
        $array_destinazioni = gaz_dbi_fetch_all($rs_query_destinazioni);
        if (substr($form['in_status'], 0, 6) == "UPDROW") { //se è un rigo da modificare
            $old_key = intval(substr($form['in_status'], 6));
            $form['rows'][$old_key]['tiprig'] = $form['in_tiprig'];
            $form['rows'][$old_key]['descri'] = $form['in_descri'];
            $form['rows'][$old_key]['id_mag'] = $form['in_id_mag'];
            $form['rows'][$old_key]['id_warehouse'] = $form['in_id_warehouse'];
            $form['rows'][$old_key]['id_order'] = $form['in_id_order'];
            $form['rows'][$old_key]['id_orderman'] = $form['in_id_orderman'];
            $form['rows'][$old_key]['status'] = "UPDATE";
            $form['rows'][$old_key]['unimis'] = $form['in_unimis'];
            $form['rows'][$old_key]['quanti'] = $form['in_quanti'];
            $form['rows'][$old_key]['codart'] = $form['in_codart'];
            $form['rows'][$old_key]['codric'] = $form['in_codric'];
            $form['rows'][$old_key]['ritenuta'] = $form['in_ritenuta'];
            $form['rows'][$old_key]['provvigione'] = $form['in_provvigione']; // in caso tiprig=4 questo campo è utilizzato per indicare l'aliquota della cassa previdenziale
            $form['rows'][$old_key]['prelis'] = floatval(preg_replace("/\,/", '.', $form['in_prelis']));
            $form['rows'][$old_key]['sconto'] = $form['in_sconto'];
            $form['rows'][$old_key]['extdoc'] = $form['in_extdoc'];
            $form['rows'][$old_key]['codvat'] = $form['in_codvat'];
            $form['rows'][$old_key]['codice_fornitore'] = $form['in_codice_fornitore'];
            $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['in_codvat']);
            $form['rows'][$old_key]['pervat'] = $iva_row['aliquo'];
            $form['rows'][$old_key]['annota'] = '';
            $form['rows'][$old_key]['pesosp'] = 0;
            $form['rows'][$old_key]['gooser'] = 0;
            $form['rows'][$old_key]['scorta'] = 0;
            $form['rows'][$old_key]['quamag'] = 0;
            $form['rows'][$old_key]['lot_or_serial'] = $form['in_lot_or_serial'];
            $form['rows'][$old_key]['SIAN'] = $form['in_SIAN'];
            $form['rows'][$old_key]['quality'] = $form['in_quality'];
            $form['rows'][$old_key]['cod_operazione'] = 11;
            $form['rows'][$old_key]['recip_stocc'] = "";
            $form['rows'][$old_key]['recip_stocc_destin'] = "";
            $form['rows'][$old_key]['identifier'] = '';
            $form['rows'][$old_key]['expiry'] = '';
            $form['rows'][$old_key]['filename'] = '';
            if ($form['in_tiprig'] == 0 && ! empty($form['in_codart'])) {  //rigo normale
                $form['rows'][$old_key]['annota'] = $artico['annota'];
                $form['rows'][$old_key]['pesosp'] = $artico['peso_specifico'];
                $form['rows'][$old_key]['gooser'] = $artico['good_or_service'];
                $form['rows'][$old_key]['unimis'] = $artico['uniacq'];
                $form['rows'][$old_key]['descri'] = $artico['descri'];
                $mv = $magazz->getStockValue(false, $form['in_codart'], gaz_format_date($form['datemi'], true), $admin_aziend['stock_eval_method']);
                $magval = array_pop($mv);
                $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
                $form['rows'][$old_key]['scorta'] = $artico['scorta'];
                $form['rows'][$old_key]['quamag'] = $magval['q_g'];
                $form['rows'][$old_key]['lot_or_serial'] = $artico['lot_or_serial'];
                $form['rows'][$old_key]['SIAN'] = $artico['SIAN'];
                if ($artico['lot_or_serial'] == 2) {
// se è prevista la gestione per numero seriale/matricola la quantità non può essere diversa da 1
                    if ($form['rows'][$old_key]['quanti'] <> 1) {
                        $msg['err'][] = "forceone";
                    }
                    $form['rows'][$old_key]['quanti'] = 1;
                    $msg['err'][] = "forceone";
                }
                $form['rows'][$old_key]['prelis'] = floatval($form['in_prelis']);
            } elseif ($form['in_tiprig'] == 2) { //rigo descrittivo
                $form['rows'][$old_key]['codart'] = "";
                $form['rows'][$old_key]['annota'] = "";
                $form['rows'][$old_key]['pesosp'] = "";
                $form['rows'][$old_key]['gooser'] = 0;
                $form['rows'][$old_key]['unimis'] = "";
                $form['rows'][$old_key]['quanti'] = 0;
                $form['rows'][$old_key]['prelis'] = 0;
                $form['rows'][$old_key]['codric'] = 0;
                $form['rows'][$old_key]['sconto'] = 0;
                $form['rows'][$old_key]['pervat'] = 0;
                $form['rows'][$old_key]['codvat'] = 0;
            } elseif ($form['in_tiprig'] == 1) { //rigo forfait
                $form['rows'][$old_key]['codart'] = "";
                $form['rows'][$old_key]['unimis'] = "";
                $form['rows'][$old_key]['quanti'] = 0;
                $form['rows'][$old_key]['sconto'] = 0;
            } elseif ($form['in_tiprig'] == 3) {   //var.tot.fatt.
                $form['rows'][$old_key]['codart'] = "";
                $form['rows'][$old_key]['quanti'] = "";
                $form['rows'][$old_key]['unimis'] = "";
                $form['rows'][$old_key]['sconto'] = 0;
            }
            ksort($form['rows']);
            $form['in_status']='';
        } else { //se è un rigo da inserire
            $form['rows'][$i]['tiprig'] = $form['in_tiprig'];
            $form['rows'][$i]['descri'] = $form['in_descri'];
            $form['rows'][$i]['id_mag'] = $form['in_id_mag'];
            $form['rows'][$i]['id_warehouse'] = $form['in_id_warehouse'];
            $form['rows'][$i]['id_order'] = $form['in_id_order'];
            $form['rows'][$i]['id_orderman'] = $form['in_id_orderman'];
            $form['rows'][$i]['provvigione'] = $form['in_provvigione'];
            $form['rows'][$i]['codice_fornitore'] = '';
            $form['rows'][$i]['scorta'] = '';
            $form['rows'][$i]['quamag'] = '';
            $form['rows'][$i]['status'] = "INSERT";
            $form['rows'][$i]['ritenuta'] = $form['in_ritenuta'];
            $form['rows'][$i]['identifier'] = '';
            $form['rows'][$i]['cod_operazione'] = 11;
            $form['rows'][$i]['recip_stocc'] = "";
            $form['rows'][$i]['recip_stocc_destin'] = "";
            $form['rows'][$i]['expiry'] = '';
            $form['rows'][$i]['extdoc'] = 0;
            $form['rows'][$i]['filename'] = '';
            $form['rows'][$i]['quality'] = '';
            if ($form['in_tiprig'] == 0 || $form['in_tiprig'] == 50) {  //rigo normale
                $form['rows'][$i]['codart'] = $form['in_codart'];
                $form['rows'][$i]['codice_fornitore'] = $artico['codice_fornitore']; //M1 aggiunto a mano
                $form['rows'][$i]['annota'] = $artico['annota'];
                $form['rows'][$i]['pesosp'] = $artico['peso_specifico'];
                $form['rows'][$i]['gooser'] = $artico['good_or_service'];
                $form['rows'][$i]['descri'] = $artico['descri'];
                $form['rows'][$i]['quality'] = $artico['quality'];
                $form['rows'][$i]['lot_or_serial'] = $artico['lot_or_serial'];
                $form['rows'][$i]['SIAN'] = $artico['SIAN'];
                $form['rows'][$i]['codric'] = $form['in_codric'];
                $form['rows'][$i]['quanti'] = $form['in_quanti'];
                if ($artico['lot_or_serial'] == 2) {// se è prevista la gestione per numero seriale/matricola la quantità non può essere diversa da 1
                    if ($form['rows'][$i]['quanti'] <> 1) {
                        $msg['err'][] = "forceone";
                    }
                    $form['rows'][$i]['quanti'] = 1;
                }
                if ($artico['lot_or_serial'] > 0 && ($form['tipdoc']!="DDR" || $form['tipdoc']!="DDL")) {
                  if ($artico['lot_or_serial'] >= 1) {
                    $lm->getAvailableLots($form['in_codart'], $form['in_id_mag']);
                    $ld = $lm->divideLots($form['in_quanti']);
                    foreach ($lm->divided as $k => $v) {
                        if ($v['qua'] >= 0.00001) {
                            $form['rows'][$i]['id_lotmag'] = $k; // setto il lotto
                           	$getlot = $lm->getLot($form['rows'][$i]['id_lotmag']);
                            $form['rows'][$i]['identifier'] = $getlot['identifier'];
                        }
                    }
                  }
                }
                $form['rows'][$i]['prelis'] = $artico['preacq'];
                $form['rows'][$i]['unimis'] = $artico['uniacq'];
                $form['rows'][$i]['prelis'] = $artico['preacq'];
                if ($form['in_sconto'] >= 0.01 ) {
                    $form['rows'][$i]['sconto'] = $form['in_sconto'];
                } else {
                  $form['rows'][$i]['sconto'] = $artico['sconto'];
                }
                // attingo il prezzo in base alla scelta fatta in configurazione avanzata azienda
                $preacq_mode_res = gaz_dbi_get_row($gTables['company_config'], 'var', 'preacq_mode');
                $preacq_mode=(isset($preacq_mode_res['val']))?$preacq_mode_res['val']:'';
                if ( $preacq_mode == 1 ) { // modo prezzo ultimo acquisto
                  $lastbuys= $magazz->getLastBuys($form['in_codart'],false);
                  $klb=key($lastbuys);
                  $form['rows'][$i]['unimis'] = $klb?$lastbuys[$klb]['unimis']:$artico['uniacq'];
                  $form['rows'][$i]['prelis'] = $klb?$lastbuys[$klb]['prezzo']:$artico['preacq'];
                  if ( $form['in_sconto'] >= 0.01 ) {
                      $form['rows'][$i]['sconto'] = $form['in_sconto'];
                  } else {
                    $form['rows'][$i]['sconto'] = $klb?$lastbuys[$klb]['scorig']:$artico['sconto'];
                  }
                }
                $form['rows'][$i]['codvat'] = $admin_aziend['preeminent_vat'];
                $iva_azi = gaz_dbi_get_row($gTables['aliiva'], "codice", $admin_aziend['preeminent_vat']);
                $form['rows'][$i]['pervat'] = $iva_azi['aliquo'];
                if ($artico['aliiva'] > 0) {
                    $form['rows'][$i]['codvat'] = $artico['aliiva'];
                    $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", $artico['aliiva']);
                    $form['rows'][$i]['pervat'] = $iva_row['aliquo'];
                }
                if ($form['in_codvat'] > 0) {
                    $form['rows'][$i]['codvat'] = $form['in_codvat'];
                    $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['in_codvat']);
                    $form['rows'][$i]['pervat'] = $iva_row['aliquo'];
                }
                if ($artico['id_cost'] > 0) {
                    $form['rows'][$i]['codric'] = $artico['id_cost'];
                    $form['in_codric'] = $artico['id_cost'];
                }
                if ($form['tipdoc'] == 'AFC') { // nel caso che si tratti di nota di credito
                    $form['in_codric'] = $admin_aziend['purchases_return'];
                }
                $mv = $magazz->getStockValue(false, $form['in_codart'], gaz_format_date($form['datemi'], true), $admin_aziend['stock_eval_method']);
                $magval = array_pop($mv);
                $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
                $form['rows'][$i]['scorta'] = $artico['scorta'];
                $form['rows'][$i]['quamag'] = $magval['q_g'];
            } elseif ($form['in_tiprig'] == 1) { //forfait
                $form['rows'][$i]['codart'] = "";
                $form['rows'][$i]['annota'] = "";
                $form['rows'][$i]['pesosp'] = 0;
                $form['rows'][$i]['gooser'] = 0;
                $form['rows'][$i]['unimis'] = "";
                $form['rows'][$i]['lot_or_serial'] = '';
                $form['rows'][$i]['cod_operazione'] = 11;
                $form['rows'][$i]['recip_stocc'] = "";
                $form['rows'][$i]['recip_stocc_destin'] = "";
                $form['rows'][$i]['SIAN'] = 0;
                $form['rows'][$i]['quanti'] = 0;
                $form['rows'][$i]['prelis'] = 0;
                $form['rows'][$i]['codric'] = $form['in_codric'];
                $form['rows'][$i]['sconto'] = 0;
                $form['rows'][$i]['codvat'] = $admin_aziend['preeminent_vat'];
                $iva_azi = gaz_dbi_get_row($gTables['aliiva'], "codice", $admin_aziend['preeminent_vat']);
                $form['rows'][$i]['pervat'] = $iva_azi['aliquo'];
                $form['rows'][$i]['tipiva'] = $iva_azi['tipiva'];
                if ($form['in_codvat'] > 0) {
                    $form['rows'][$i]['codvat'] = $form['in_codvat'];
                    $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['in_codvat']);
                    $form['rows'][$i]['pervat'] = $iva_row['aliquo'];
                    $form['rows'][$i]['tipiva'] = $iva_row['tipiva'];
                }
            } elseif ($form['in_tiprig'] == 2 || $form['in_tiprig'] == 51) { //descrittivo
                $form['rows'][$i]['codart'] = "";
                $form['rows'][$i]['annota'] = "";
                $form['rows'][$i]['pesosp'] = 0;
                $form['rows'][$i]['gooser'] = 0;
                $form['rows'][$i]['lot_or_serial'] = '';
                $form['rows'][$i]['cod_operazione'] = 11;
                $form['rows'][$i]['recip_stocc'] = "";
                $form['rows'][$i]['recip_stocc_destin'] = "";
                $form['rows'][$i]['SIAN'] = 0;
                $form['rows'][$i]['unimis'] = "";
                $form['rows'][$i]['quanti'] = 0;
                $form['rows'][$i]['prelis'] = 0;
                $form['rows'][$i]['codric'] = 0;
                $form['rows'][$i]['sconto'] = 0;
                $form['rows'][$i]['pervat'] = 0;
                $form['rows'][$i]['codvat'] = 0;
            } elseif ($form['in_tiprig'] == 3) {
                $form['rows'][$i]['codart'] = "";
                $form['rows'][$i]['annota'] = "";
                $form['rows'][$i]['pesosp'] = 0;
                $form['rows'][$i]['gooser'] = 0;
                $form['rows'][$i]['lot_or_serial'] = '';
                $form['rows'][$i]['cod_operazione'] = 11;
                $form['rows'][$i]['recip_stocc'] = "";
                $form['rows'][$i]['recip_stocc_destin'] = "";
                $form['rows'][$i]['SIAN'] = 0;
                $form['rows'][$i]['unimis'] = "";
                $form['rows'][$i]['quanti'] = 0;
                $form['rows'][$i]['prelis'] = $form['in_prelis'];
                $form['rows'][$i]['codric'] = $form['in_codric'];
                $form['rows'][$i]['sconto'] = 0;
                $form['rows'][$i]['ritenuta'] = 0;
                $form['rows'][$i]['codvat'] = $form['in_codvat'];
                $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['in_codvat']);
                $form['rows'][$i]['pervat'] = $iva_row['aliquo'];
            } elseif ($form['in_tiprig'] == 4) { // cassa previdenziale
                $form['rows'][$i]['codart'] = $admin_aziend['fae_tipo_cassa'];// propongo quella aziendale uso il codice articolo
                $form['rows'][$i]['annota'] = "";
                $form['rows'][$i]['pesosp'] = "";
                $form['rows'][$i]['gooser'] = 0;
                $form['rows'][$i]['unimis'] = "";
                $form['rows'][$i]['quanti'] = 0;
                $form['rows'][$i]['prelis'] = 0;
                $form['rows'][$i]['provvigione'] = $form['in_provvigione'];
                $form['rows'][$i]['codric'] = $admin_aziend['c_payroll_tax'];
                $form['rows'][$i]['sconto'] = 0;
                $form['rows'][$i]['codvat'] = $admin_aziend['preeminent_vat'];
                $iva_azi = gaz_dbi_get_row($gTables['aliiva'], "codice", $admin_aziend['preeminent_vat']);
                $form['rows'][$i]['pervat'] = $iva_azi['aliquo'];
                if ($form['in_codvat'] > 0) {
                    $form['rows'][$i]['codvat'] = $form['in_codvat'];
                    $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['in_codvat']);
                    $form['rows'][$i]['pervat'] = $iva_row['aliquo'];
                    $form['rows'][$i]['tipiva'] = $iva_row['tipiva'];
                }
                $form['rows'][$i]['ritenuta'] = $form['in_ritenuta'];
                // carico anche la descrizione corrispondente dal file xml
                      $xml = simplexml_load_file('../../library/include/fae_tipo_cassa.xml');
                foreach ($xml->record as $v) {
                  $selected = '';
                  if ($v->field[0] == $form['rows'][$i]['codart']) {
                    $form['rows'][$i]['descri']= 'Contributo '.strtolower($v->field[1]);
                  }
                }
            } elseif ($form['in_tiprig'] > 5 && $form['in_tiprig'] < 9 ) { //testo
                $form["row_$i"] = "";
                $form['rows'][$i]['codart'] = "";
                $form['rows'][$i]['annota'] = "";
                $form['rows'][$i]['pesosp'] = 0;
                $form['rows'][$i]['gooser'] = 0;
                $form['rows'][$i]['lot_or_serial'] = '';
                $form['rows'][$i]['SIAN'] = 0;
                $form['rows'][$i]['cod_operazione'] = 11;
                $form['rows'][$i]['recip_stocc'] = "";
                $form['rows'][$i]['recip_stocc_destin'] = "";
                $form['rows'][$i]['unimis'] = "";
                $form['rows'][$i]['quanti'] = 0;
                $form['rows'][$i]['prelis'] = 0;
                $form['rows'][$i]['codric'] = 0;
                $form['rows'][$i]['sconto'] = 0;
                $form['rows'][$i]['pervat'] = 0;
                $form['rows'][$i]['tipiva'] = 0;
                $form['rows'][$i]['ritenuta'] = 0;
                $form['rows'][$i]['codvat'] = 0;
            }
        }
// reinizializzo rigo di input tranne che per il tipo rigo e aliquota iva
        $form['in_descri'] = "";
        $form['in_codart'] = "";
        $form['in_unimis'] = "";
        $form['in_prelis'] = 0.000;
        $form['in_sconto'] = 0;
        /** inizio modifica FP 09/10/2015
         * inizializzo il campo con '#' per indicare che voglio lo sconto standard dell'articolo
         */
        /* carico gli indirizzi di destinazione dalla tabella gaz_destina */
         $idAnagrafe = (isset($fornitore['id_anagra']))?$fornitore['id_anagra']:0;
        $rs_query_destinazioni = gaz_dbi_dyn_query("*", $gTables['destina'], "id_anagra='$idAnagrafe'");
        $array_destinazioni = gaz_dbi_fetch_all($rs_query_destinazioni);
        /* fine modifica FP */
        $form['in_quanti'] = 0;
		$form['in_quality'] = 0;
        $form['in_id_mag'] = 0;
        $form['in_id_order'] = 0;
        $form['in_annota'] = "";
        $form['in_pesosp'] = 0;
        $form['in_gooser'] = 0;
        $form['in_scorta'] = 0;
        $form['in_quamag'] = 0;
        $form['in_status'] = "INSERT";
// fine reinizializzo rigo input
        $form['cosear'] = "";
        $i++;
    }


// Se viene inviata la richiesta di spostamento verso l'alto del rigo
    if (isset($_POST['upper_row'])) {
        $upp_key = key($_POST['upper_row']);
        $k_next = $upp_key - 1;
        if (isset($form["row_$k_next"])) { //se ho un rigo testo prima gli cambio l'index
            $form["row_$upp_key"] = $form["row_$k_next"];
            unset($form["row_$k_next"]);
        }
        if ($upp_key > 0) {
            $new_key = $upp_key - 1;
        } else {
            $new_key = $i - 1;
        }
        $tmp_path = DATA_DIR . 'files/tmp/' . $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_';
        // rinomino prima il documento della linea target new key ( se esiste )
        @rename($tmp_path . $new_key . '_' . $form['rows'][$new_key]['filename'], $tmp_path . '_tmp_' . $new_key . '_' . $form['rows'][$new_key]['filename']);
        // rinomino il documento della linea spostata verso l'alto dandogli gli indici di quello precedente
        @rename($tmp_path . $upp_key . '_' . $form['rows'][$upp_key]['filename'], $tmp_path . $new_key . '_' . $form['rows'][$upp_key]['filename']);
        // rinomino nuovamente il documento della linea target dandogli gli indici di quella spostata
        @rename($tmp_path . '_tmp_' . $new_key . '_' . $form['rows'][$new_key]['filename'], $tmp_path . $upp_key . '_' . $form['rows'][$new_key]['filename']);
        $updated_row = $form['rows'][$new_key];
        $form['rows'][$new_key] = $form['rows'][$upp_key];
        $form['rows'][$upp_key] = $updated_row;
        ksort($form['rows']);
        unset($updated_row);
    }

// Se viene inviata la richiesta elimina il rigo corrispondente
    if (isset($_POST['del'])) {
        $delri = key($_POST['del']);

        /** inizio modifica FP 09/01/2016
         * modifica piede ddt
         */
// sottrazione ai totali peso,pezzi,volume
        $artico = gaz_dbi_get_row($gTables['artico'], "codice", $form['rows'][$delri]['codart']);
        if (!$artico) $artico=array('peso_specifico'=>false,'pack_units'=>false,'volume_specifico'=>false);
        $form['net_weight'] -= $form['rows'][$delri]['quanti'] * $artico['peso_specifico'];
        $form['gross_weight'] -= $form['rows'][$delri]['quanti'] * $artico['peso_specifico'];
        if ($artico['pack_units'] > 0) {
            $form['units'] -= intval(round($form['rows'][$delri]['quanti'] / $artico['pack_units']));
        }
        $form['volume'] -= $form['rows'][$delri]['quanti'] * $artico['volume_specifico'];
// fine sottrazione peso,pezzi,volume
        /** fine modifica FP */
// diminuisco o lascio inalterati gli index dei testi
        foreach ($form['rows'] as $k => $val) {
            if (isset($form["row_$k"])) { //se ho un rigo testo
                if ($k > $delri) { //se ho un rigo testo dopo
                    $new_k = $k - 1;
                    $form["row_$new_k"] = $form["row_$k"];
                    unset($form["row_$k"]);
                }
            }
        }

        array_splice($form['rows'], $delri, 1);
        $i--;
    }
} elseif ((!isset($_POST['Update'])) && ( isset($_GET['Update'])) || ( isset($_GET['Duplicate']))) { //se e' il primo accesso per UPDATE
    if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname'])){
		// allineo l'e-commerce con eventuali ordini non ancora caricati
		$gs=$admin_aziend['synccommerce_classname'];
		$gSync = new $gs();
		if($gSync->api_token){
			$gSync->get_sync_status(0);
		}
	}
	$tesdoc = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", intval($_GET['id_tes']));
    $anagrafica = new Anagrafica();
    $fornitore = $anagrafica->getPartner($tesdoc['clfoco']);
    $id_des = $anagrafica->getPartner($tesdoc['id_des']);
    $rs_rig = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = " . $tesdoc['id_tes'], "id_rig asc");
	$rs_tes =false;
	if ($tesdoc['ddt_type']=="T" && !isset($_GET['DDT'])){ // Antonio Germani - se è una fattura con DDT, carico tutti i tesdoc
	$rs_tes = gaz_dbi_dyn_query("*", $gTables['tesdoc'], " YEAR (datfat) = " . substr($tesdoc['datfat'],0,4). " AND protoc = ".$tesdoc['protoc']. " AND tipdoc = 'AFT'" , "id_tes asc");

	}
    $form['id_tes'] = $tesdoc['id_tes'];
    $form['hidden_req'] = '';
// inizio rigo di input
    $form['in_descri'] = "";
    $form['in_tiprig'] = 0;
    /*    $form['in_artsea'] = $admin_aziend['artsea']; */
    $form['in_codart'] = "";
    $form['in_quality'] = "";
    $form['SIAN']= 0;
    $form['cod_operazione']=11;
    $form['recip_stocc']="";
    $form['recip_stocc_destin']="";
    $form['in_codice_fornitore'] = '';
    $form['in_pervat'] = 0;
    $form['in_ritenuta'] = 0;
    $form['in_provvigione'] = 0;
    $form['in_unimis'] = "";
    $form['in_prelis'] = 0.000;
    $form['in_sconto'] = 0;
    $form['in_quanti'] = 0;
    $form['in_codvat'] = $admin_aziend['preeminent_vat'];
    if ($fornitore['cosric'] > 0) {
        $form['in_codric'] = $fornitore['cosric'];
    } else {
        $form['in_codric'] = $admin_aziend['impacq'];
    }
    if ($tesdoc['tipdoc'] == 'AFC') { // nel caso che si tratti di nota di credito
        $form['in_codric'] = $admin_aziend['purchases_return'];
        if ($form['in_codric'] < 300000000) {
            $form['in_codric'] = '3';
        }
    }
    $form['in_id_mag'] = 0;
    // adesso metto uno ma dovrò proporre il magazzino di riferimento dell'utente
    $magmodule = gaz_dbi_get_row($gTables['module'], "name",'magazz');
    $magadmin_module = gaz_dbi_get_row($gTables['admin_module'], "moduleid",$magmodule['id']," AND adminid='{$admin_aziend['user_name']}' AND company_id=" . $admin_aziend['company_id']);
    $magcustom_field=is_array($magadmin_module['custom_field'])?json_decode($magadmin_module['custom_field']):false;
    $form["in_id_warehouse"] = (isset($magcustom_field->user_id_warehouse))?$magcustom_field->user_id_warehouse:0;
    $form['in_id_order'] = 0;
    $form['in_id_orderman'] = 0;
    $form['in_annota'] = "";
    $form['in_extdoc'] = 0;
    $form['in_pesosp'] = 0;
    $form['in_gooser'] = 0;
    $form['in_scorta'] = 0;
    $form['in_quamag'] = 0;
    $form['in_lot_or_serial'] = 0;
    $form['in_SIAN'] = 0;
    $form['in_status'] = "";
// fine rigo input
    $form['rows'] = array();
// ...e della testata
    $form['search']['clfoco'] = substr($fornitore['ragso1'], 0, 10);
    $form['cosear'] = "";
    $form['coseprod'] = "";
    $form['address'] = $fornitore['indspe'] . ' ' . $fornitore['citspe'];
    $form['seziva'] = $tesdoc['seziva'];
    $form['id_con'] = $tesdoc['id_con'];
    $form['tipdoc'] = $tesdoc['tipdoc'];
    if ($tesdoc['id_con'] > 0) {
        $msg['war'][] = 'accounted';
    }
    $form['datreg']=gaz_format_date($tesdoc['datreg'], false, false);
    $form['datfat']=gaz_format_date($tesdoc['datfat'], false, false);
    $form['datemi']=gaz_format_date($tesdoc['datemi'], false, false);
    $form['giotra'] = substr($tesdoc['initra'], 8, 2);
    $form['mestra'] = substr($tesdoc['initra'], 5, 2);
    $form['anntra'] = substr($tesdoc['initra'], 0, 4);
    $form['oratra'] = substr($tesdoc['initra'], 11, 2);
    $form['mintra'] = substr($tesdoc['initra'], 14, 2);
    $form['protoc'] = $tesdoc['protoc'];
    $form['numdoc'] = $tesdoc['numdoc'];
    $form['numfat'] = $tesdoc['numfat'];
    $form['clfoco'] = $tesdoc['clfoco'];
    $form['pagame'] = $tesdoc['pagame'];
    $form['change_pag'] = $tesdoc['pagame'];
    $form['speban'] = 0;
    $pagame = gaz_dbi_get_row($gTables['pagame'], "codice", $form['pagame']);
    if (($pagame['tippag'] == 'B' || $pagame['tippag'] == 'T' || $pagame['tippag'] == 'V') && $fornitore['speban'] == 'S') {
        $form['numrat'] = $pagame['numrat'];
    } else {
        $form['speban'] = 0.00;
        $form['numrat'] = 1;
    }
    $form['banapp'] = $tesdoc['banapp'];
    $form['vettor'] = $tesdoc['vettor'];
    $form['net_weight'] = $tesdoc['net_weight'];
    $form['gross_weight'] = $tesdoc['gross_weight'];
    $form['units'] = $tesdoc['units'];
    $form['volume'] = $tesdoc['volume'];
    $array_destinazioni = array();
    $form['listin'] = $tesdoc['listin'];
    $form['spediz'] = $tesdoc['spediz'];
    $form['portos'] = $tesdoc['portos'];
    $form['imball'] = $tesdoc['imball'];
    $form['destin'] = $tesdoc['destin'];
    $form['id_des'] = $tesdoc['id_des'];
    $form['id_des_same_company'] = $tesdoc['id_des_same_company'];
    $form['search']['id_des'] =($id_des)?substr($id_des['ragso1'], 0, 10):'';
    $form['traspo'] = $tesdoc['traspo'];
    $form['spevar'] = $tesdoc['spevar'];
    $form['ivaspe'] = 0;
    $form['pervat'] = 0;
    $form['cauven'] = $tesdoc['cauven'];
    $form['caucon'] = $tesdoc['caucon'];
    $form['caumag'] = $tesdoc['caumag'];
    $form['caucon'] = $tesdoc['caucon'];
    $form['id_parent_doc'] = $tesdoc['id_parent_doc'];
    $form['sconto'] = $tesdoc['sconto'];
    $form['lotmag'] = array();
    $i = 0;
	if ($rs_tes){ // se ci sono ddt devo caricare tutti i righi per ogni tesdoc

		while ($tesdoc = gaz_dbi_fetch_array($rs_tes)){// per ogni tesdoc
			$rs_rig = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = " . $tesdoc['id_tes'], "id_rig asc");

			while ($row = gaz_dbi_fetch_array($rs_rig)) {

				$articolo = gaz_dbi_get_row($gTables['artico'], "codice", $row['codart']);
				if ($row['id_body_text'] > 0) { //se ho un rigo testo
					$text = gaz_dbi_get_row($gTables['body_text'], "id_body", $row['id_body_text']);
					$form["row_$i"] = $text['body_text'];
				}
				$form['rows'][$i]['descri'] = $row['descri'];
				$form['rows'][$i]['tiprig'] = $row['tiprig'];
				$form['rows'][$i]['codart'] = $row['codart'];
				$form['rows'][$i]['quality'] = $articolo['quality'];
				$form['rows'][$i]['codice_fornitore'] = $row['codice_fornitore'];//M1 aggiunto a mano
				$form['rows'][$i]['pervat'] = $row['pervat'];
				$form['rows'][$i]['ritenuta'] = $row['ritenuta'];
				$form['rows'][$i]['unimis'] = $row['unimis'];
				$form['rows'][$i]['prelis'] = $row['prelis'];
				$form['rows'][$i]['sconto'] = $row['sconto'];
				$form['rows'][$i]['quanti'] = gaz_format_quantity($row['quanti'], 0, $admin_aziend['decimal_quantity']);
				$form['rows'][$i]['codvat'] = $row['codvat'];
        // ripropongo l'ultima aliquota usata sul rigo di input
        $form['in_codvat'] = $row['codvat'];
				$form['rows'][$i]['codric'] = $row['codric'];
				$form['rows'][$i]['id_mag'] = $row['id_mag'];
				$form['rows'][$i]['id_warehouse'] = 0;
				if ($row['id_mag']>0){ // dovrò riprendere l'id del magazzino dal relativo movmag
					$movmag = gaz_dbi_get_row($gTables['movmag'], "id_mov", $row['id_mag']);
					if ($movmag&&$movmag['id_warehouse']>0){
						$form['rows'][$i]['id_warehouse'] = $movmag['id_warehouse'];
					}
				}
				$form['rows'][$i]['id_order'] = $row['id_order'];
				$form['rows'][$i]['id_rig'] = $row['id_rig'];
				$form['rows'][$i]['provvigione'] = $row['provvigione'];// in caso tiprig=4 questo campo è utilizzato per indicare l'aliquota della cassa
				$form['in_id_orderman'] = $row['id_orderman'];
				$orderman = gaz_dbi_get_row($gTables['orderman'], "id", $row['id_orderman']);
				$form['coseprod'] = $orderman['description'];
				$form['rows'][$i]['id_orderman'] = $row['id_orderman'];
				$form['rows'][$i]['annota'] = $articolo['annota'];
				$mv = $magazz->getStockValue(false, $row['codart'], gaz_format_date($form['datemi'], true), $admin_aziend['stock_eval_method']);
				$magval = array_pop($mv);
				$magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
				$form['rows'][$i]['scorta'] = $articolo['scorta'];
				$form['rows'][$i]['quamag'] = $magval['q_g'];
				$form['rows'][$i]['pesosp'] = $articolo['peso_specifico'];
				$form['rows'][$i]['gooser'] = $articolo['good_or_service'];
				$form['rows'][$i]['lot_or_serial'] = $articolo['lot_or_serial'];
				$form['rows'][$i]['SIAN'] = $articolo['SIAN'];
				$form['rows'][$i]['filename'] = '';
				$form['rows'][$i]['identifier'] = '';
				$form['rows'][$i]['expiry'] = '';
				$form['rows'][$i]['status'] = "";

				if ($form['rows'][$i]['SIAN']>0){
					$camp_mov_sian = gaz_dbi_get_row($gTables['camp_mov_sian'], "id_movmag", $form['rows'][$i]['id_mag']);
					$form['rows'][$i]['cod_operazione'] = $camp_mov_sian['cod_operazione'];
					$form['rows'][$i]['recip_stocc'] = $camp_mov_sian['recip_stocc'];
					$form['rows'][$i]['recip_stocc_destin'] = $camp_mov_sian['recip_stocc_destin'];
				}

				// recupero eventuale movimento di tracciabilità ma solo se non è stata richiesta una duplicazione (di un ddt c/lavorazione)
				if (file_exists( DATA_DIR . 'files/' . $admin_aziend['company_id'] ) > 0) {
				if (!isset($_GET['Duplicate']) || $form['tipdoc']=='DDR') {
					$result_movmag = gaz_dbi_get_row($gTables['movmag'], "id_mov", $row['id_mag']);
					$lotmag = gaz_dbi_get_row($gTables['lotmag'], 'id', $result_movmag['id_lotmag']);
					// recupero il filename dal filesystem e lo sposto sul tmp
					$dh = opendir( DATA_DIR . 'files/' . $admin_aziend['company_id'] );
					while (false !== ($filename = readdir($dh))) {
						$fd = pathinfo($filename);
						$r = explode('_', $fd['filename']);
						if ($r[0] == 'lotmag' && $r[1] == $lotmag['id']) {
							// riassegno il nome file
							$form['rows'][$i]['filename'] = $fd['basename'];
						}
					}
					$form['rows'][$i]['identifier'] = $lotmag['identifier'];
					$form['rows'][$i]['id_lotmag'] = $lotmag['id'];
					if (intval($lotmag['expiry'])>0){
						$form['rows'][$i]['expiry'] = gaz_format_date($lotmag['expiry']);
					} else {
						$form['rows'][$i]['expiry']="0000-00-00 00:00:00";
					}
				} else {
					$form['rows'][$i]['id_mag'] = 0;
				}
				} else {
					$msg['err'][] = "nofold";
				}
				$i++;
			}


		}
	} else {
    while ($row = gaz_dbi_fetch_array($rs_rig)) {
        $articolo = gaz_dbi_get_row($gTables['artico'], "codice", $row['codart']);
        if ($row['id_body_text'] > 0) { //se ho un rigo testo
            $text = gaz_dbi_get_row($gTables['body_text'], "id_body", $row['id_body_text']);
            $form["row_$i"] = $text['body_text'];
        }
        $form['rows'][$i]['descri'] = $row['descri'];
        $form['rows'][$i]['tiprig'] = $row['tiprig'];
        $form['rows'][$i]['codart'] = $row['codart'];
        $form['rows'][$i]['quality'] = ($articolo)?$articolo['quality']:'';
        $form['rows'][$i]['codice_fornitore'] = $row['codice_fornitore'];//M1 aggiunto a mano
        $form['rows'][$i]['pervat'] = $row['pervat'];
        $form['rows'][$i]['ritenuta'] = $row['ritenuta'];
        $form['rows'][$i]['unimis'] = $row['unimis'];
        $form['rows'][$i]['prelis'] = $row['prelis'];
        $form['rows'][$i]['sconto'] = $row['sconto'];
        $form['rows'][$i]['quanti'] = gaz_format_quantity($row['quanti'], 0, $admin_aziend['decimal_quantity']);
        $form['rows'][$i]['codvat'] = $row['codvat'];
        // ripropongo l'ultima aliquota usata sul rigo di input
        $form['in_codvat'] = $row['codvat'];
        $form['rows'][$i]['codric'] = $row['codric'];
        $form['rows'][$i]['id_mag'] = $row['id_mag'];
        $form['rows'][$i]['id_warehouse'] = 0;
        if ($row['id_mag']>0){ // dovrò riprendere l'id del magazzino dal relativo movmag
          $movmag = gaz_dbi_get_row($gTables['movmag'], "id_mov", $row['id_mag']);
          if ($movmag&&$movmag['id_warehouse']>0){
            $form['rows'][$i]['id_warehouse'] = $movmag['id_warehouse'];
          }
        }
        $form['rows'][$i]['id_rig'] = $row['id_rig'];
        $form['rows'][$i]['id_order'] = $row['id_order'];
        $form['rows'][$i]['provvigione'] = $row['provvigione'];// in caso tiprig=4 questo campo è utilizzato per indicare l'aliquota della cassa
        $form['in_id_orderman'] = $row['id_orderman'];
        $orderman = gaz_dbi_get_row($gTables['orderman'], "id", $row['id_orderman']);
        $form['coseprod'] =($orderman)?$orderman['description']:'';
        $form['rows'][$i]['id_orderman'] = $row['id_orderman'];
        $form['rows'][$i]['cod_operazione'] = '';
        $form['rows'][$i]['recip_stocc'] = '';
        $form['rows'][$i]['recip_stocc_destin'] ='';
      if ($articolo){
        $form['rows'][$i]['annota'] = $articolo['annota'];
        $mv = $magazz->getStockValue(false, $row['codart'], gaz_format_date($form['datemi'], true), $admin_aziend['stock_eval_method']);
        $magval = array_pop($mv);
        $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
        $form['rows'][$i]['scorta'] = $articolo['scorta'];
        $form['rows'][$i]['quamag'] = $magval['q_g'];
        $form['rows'][$i]['pesosp'] = $articolo['peso_specifico'];
        $form['rows'][$i]['gooser'] = $articolo['good_or_service'];
        $form['rows'][$i]['lot_or_serial'] = $articolo['lot_or_serial'];
        $form['rows'][$i]['SIAN'] = $articolo['SIAN'];
        $form['rows'][$i]['quality'] = $articolo['quality'];
        if ($form['rows'][$i]['SIAN']>0){
          $camp_mov_sian = gaz_dbi_get_row($gTables['camp_mov_sian'], "id_movmag", $form['rows'][$i]['id_mag']);
          if ($camp_mov_sian) {
            $form['rows'][$i]['cod_operazione'] = ($camp_mov_sian['cod_operazione']=="P")?"12":$camp_mov_sian['cod_operazione'];
            $form['rows'][$i]['recip_stocc'] = $camp_mov_sian['recip_stocc'];
            $form['rows'][$i]['recip_stocc_destin'] = $camp_mov_sian['recip_stocc_destin'];
          }
        }
      } else {
        $form['rows'][$i]['codart']='';
        $form['rows'][$i]['quality']='';
        $form['rows'][$i]['scorta']=0;
        $form['rows'][$i]['quamag']=0;
        $form['rows'][$i]['pesosp']=0;
        $form['rows'][$i]['gooser']=0;
        $form['rows'][$i]['lot_or_serial']='';
        $form['rows'][$i]['SIAN']='';
        $form['rows'][$i]['annota']='';
      }
      $form['rows'][$i]['filename'] = '';
      $form['rows'][$i]['identifier'] = '';
      $form['rows'][$i]['expiry'] = '';
      $form['rows'][$i]['extdoc'] = '';
      if ($row['tiprig']==50||$row['tiprig']==51){
        $form['rows'][$i]['pesosp'] = $row['peso_specifico'];
        // recupero il filename dal filesystem
        $dh = opendir( DATA_DIR . 'files/' . $admin_aziend['company_id'].'/doc' );
        while (false !== ($filename = readdir($dh))) {
          $fd = pathinfo($filename);
          $e = explode('_rigdoc_', $fd['basename']);
          if ($e[0] == $row['id_rig']) {
            $form['rows'][$i]['extdoc'] = $e[1];
          }
        }
      }
      if ($form['rows'][$i]['SIAN']>0){
        $camp_mov_sian = gaz_dbi_get_row($gTables['camp_mov_sian'], "id_movmag", $form['rows'][$i]['id_mag']);
        if ($camp_mov_sian) {
          $form['rows'][$i]['cod_operazione'] = ($camp_mov_sian['cod_operazione']=="P")?"12":$camp_mov_sian['cod_operazione'];
          $form['rows'][$i]['recip_stocc'] = $camp_mov_sian['recip_stocc'];
          $form['rows'][$i]['recip_stocc_destin'] = $camp_mov_sian['recip_stocc_destin'];
        }
      }
      // recupero eventuale movimento di tracciabilità ma solo se non è stata richiesta una duplicazione (di un ddt c/lavorazione)
      if (file_exists( DATA_DIR . 'files/' . $admin_aziend['company_id'] ) > 0) {
        if (!isset($_GET['Duplicate']) || $form['tipdoc']=='DDR') {
          $result_movmag = gaz_dbi_get_row($gTables['movmag'], "id_mov", $row['id_mag']);
          if (!$result_movmag) $result_movmag['id_lotmag']=0;
          $lotmag = gaz_dbi_get_row($gTables['lotmag'], 'id', $result_movmag['id_lotmag']);
          if (!$lotmag) $lotmag=['identifier'=>'','id'=>0,'expiry'=>0];
          // recupero il filename dal filesystem e lo sposto sul tmp
          $dh = opendir( DATA_DIR . 'files/' . $admin_aziend['company_id'] );
          while (false !== ($filename = readdir($dh))) {
            $fd = pathinfo($filename);
            $r = explode('_', $fd['filename']);
            if ($r[0] == 'lotmag' && $r[1] == $lotmag['id']) {
              // riassegno il nome file
              $form['rows'][$i]['filename'] = $fd['basename'];
            }
          }
			$form['rows'][$i]['identifier'] = $lotmag['identifier'];
			$form['rows'][$i]['id_lotmag'] = $lotmag['id'];
			if (intval($lotmag['expiry'])>0){
				$form['rows'][$i]['expiry'] = gaz_format_date($lotmag['expiry']);
			} else {
				$form['rows'][$i]['expiry']="0000-00-00 00:00:00";
        $form['rows'][$i]['status'] = "";
			}
		} else {
			$form['rows'][$i]['status'] = "";
			$form['rows'][$i]['id_mag'] = 0;
		}
		} else {
			$msg['err'][] = "nofold";
		}
        $i++;
    }
	}
    if (isset($_GET['Duplicate'])) {  // duplicate: devo reinizializzare i campi come per la insert
        $form['id_doc_ritorno'] = 0;
        $form['id_tes'] = "";
        $form['datemi'] = date("d/m/Y");
        $form['giotra'] = date("d");
        $form['mestra'] = date("m");
        $form['anntra'] = date("Y");
        $form['oratra'] = date("H");
        $form['mintra'] = date("i");
    }
    $ddtchecked=0;
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
	if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname'])){
		// allineo l'e-commerce con eventuali ordini non ancora caricati
		$gs=$admin_aziend['synccommerce_classname'];
		$gSync = new $gs();
		if($gSync->api_token){
			$gSync->get_sync_status(0);
		}
	}
    $form['tipdoc'] = substr($_GET['tipdoc'],0,3);
    $form['address'] = '';
    $form['hidden_req'] = '';
    $form['id_tes'] = "";
	$form['datreg'] = date("d/m/Y");
	$form['datfat'] = date('d/m/Y', strtotime(' +1 day'));
    $form['datemi'] = date("d/m/Y");;
    if (substr($form['tipdoc'], 0, 1) == 'A') { //un documento d'acquisto ricevuto (non fiscale) imposto l'ultimo giorno del mese in modo da evidenziare un eventuale errore di mancata introduzione manuale del dato
        $utstra = mktime(0, 0, 0, date("m") + 1, date("d"), date("Y"));
    } else {
        $utstra = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
    }
    $form['giotra'] = date("d", $utstra);
    $form['mestra'] = date("m", $utstra);
    $form['anntra'] = date("Y", $utstra);
    $form['oratra'] = date("H");
    $form['mintra'] = date("i");
    $form['rows'] = array();
// tracciabilità
    $form['lotmag'] = array();
// fine tracciabilità
    $i = 0;
// inizio rigo di input
    $form['in_descri'] = "";
    $form['in_tiprig'] = 0;
    $form['in_codart'] = "";
    $form['in_quality'] = "";
    $form['in_SIAN'] = 0;
    $form['in_codice_fornitore'] = '';
    $form['in_pervat'] = "";
    $form['in_ritenuta'] = 0;
    $form['in_unimis'] = "";
    $form['in_extdoc'] = 0;
    $form['in_prelis'] = 0.000;
    $form['in_sconto'] = 0;
    $form['in_quanti'] = 0;
    $form['in_codvat'] = $admin_aziend['preeminent_vat'];
    $form['in_codric'] = $admin_aziend['impacq'];
    $form['in_provvigione'] = 0;// in caso tiprig=4 questo campo è utilizzato per indicare l'aliquota della cassa previdenziale
    if ($form['tipdoc'] == 'AFC') { // nel caso che si tratti di nota di credito
        $form['in_codric'] = $admin_aziend['purchases_return'];
    }
    $form['in_id_mag'] = 0;
    // dal custom field di admin_module relativo al magazzino trovo il magazzino di default
    $magmodule = gaz_dbi_get_row($gTables['module'], "name",'magazz');
    $magadmin_module = gaz_dbi_get_row($gTables['admin_module'], "moduleid",$magmodule['id']," AND adminid='{$admin_aziend['user_name']}' AND company_id=" . $admin_aziend['company_id']);
    $magcustom_field=json_decode($magadmin_module['custom_field']);
    $form["in_id_warehouse"] = (isset($magcustom_field->user_id_warehouse))?$magcustom_field->user_id_warehouse:0;
    $form['in_id_order'] = 0;
    $form['in_id_orderman'] = 0;
    $form['in_annota'] = "";
    $form['in_pesosp'] = 0;
    $form['in_gooser'] = 0;
    $form['in_scorta'] = 0;
    $form['in_quamag'] = 0;
    $form['in_lot_or_serial'] = '';
    $form['cod_operazione'] = 11;
    $form['recip_stocc'] = "";
    $form['recip_stocc_destin'] = "";
    $form['in_status'] = "";
// fine rigo input
    $form['search']['clfoco'] = '';
    $form['cosear'] = "";
    $form['coseprod'] = "";
    if (isset($_GET['seziva'])) {
        $form['seziva'] = intval($_GET['seziva']);
    } else {
        $form['seziva'] = 1;
    }
    $form['id_con'] = '';
    $form['protoc'] = "";
    $form['numdoc'] = "";
    $form['numfat'] = "";
    $form['clfoco'] = "";
    $form['pagame'] = "";
    $form['change_pag'] = "";
    $form['banapp'] = "";
    $form['vettor'] = "";
    $form['net_weight'] = 0;
    $form['gross_weight'] = 0;
    $form['units'] = 0;
    $form['volume'] = 0;
    $array_destinazioni = array();
    $form['listin'] = "";
    $form['destin'] = "";
    $form['id_des'] = 0;
    $form['id_des_same_company'] = 0;
    $form['search']['id_des'] = '';
    $form['spediz'] = "";
    $form['portos'] = "";
    $form['imball'] = "";
    $form['traspo'] = 0.00;
    $form['numrat'] = 1;
    $form['speban'] = 0;
    $form['spevar'] = 0;
    if ($admin_aziend['preeminent_vat'] > 0) {
        $form['ivaspe'] = $admin_aziend['preeminent_vat'];
    } else {
        $form['ivaspe'] = 1;
    }
    $result = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['ivaspe']);
    $form['pervat'] = $result['aliquo'];
    $form['cauven'] = 0;
    $form['caucon'] = '';
    if ($form['tipdoc'] == 'DDR') {
        $form['caumag'] = 4; //causale: 4 	SCARICO PER RESO A FORNITORE
    } else if ($form['tipdoc'] == 'DDL') {
        $form['caumag'] = 3; //causale: 3 	SCARICO PER C/LAVORAZIONE
    } else {
        $form['caumag'] = 5; //causale: 5 	CARICO PER ACQUISTO
    }
    $form['id_parent_doc'] = 0;
    $form['sconto'] = 0;
    $ddtchecked=0;
}
require("../../library/include/header.php");
$script_transl = HeadMain(0, array(
    'calendarpopup/CalendarPopup',
    'custom/autocomplete',
    'custom/modal_form'
        ));
?>
<script language="JavaScript">
$(function () {
    $(".datepicker").datepicker({dateFormat: 'dd-mm-yy'});
    $("#datreg").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
    $("#datreg").change(function () {
      this.form.submit();
    });
    $("#datfat").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
    $("#datfat").change(function () {
      this.form.submit();
    });
    $("#datemi").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
    $("#datemi").change(function () {
      this.form.submit();
    });
    $('#numdoc').keyup(function(){
        this.value = this.value.replace(/[^\d]/g, '');
    });
    <?php
    if ( count($msg['err'])<=0 && count($msg['war'])<=0 && $form['clfoco']>=100000000 && !isset($_POST['ins']) && $scorrimento=='1') { // scrollo solo se voluto, ho selezionato il cliente e non ci sono errori
        ?>
        $("html, body").delay(100).animate({scrollTop: $('#search_cosear').offset().top-100}, 200);
        <?php
    }
    ?>
});
function pulldown_menu(selectName, destField)
{
    // Create a variable url to contain the value of the
    // selected option from the the form named broven and variable selectName
    var url = document.tesdoc[selectName].options[document.tesdoc[selectName].selectedIndex].value;
    document.tesdoc[destField].value = url;
}
function printPdf(urlPrintDoc){
	$(function(){
		$('#framePdf').attr('src',urlPrintDoc);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
		$('#closePdf').on( "click", function() {
			$('.framePdf').css({'display': 'none'});
      window.location.href = "<?php echo $form['ritorno']; ?>";
		});
	});
};
</script>
<form class="form-horizontal" role="form" method="post" name="tesdoc" enctype="multipart/form-data" >
    <input type="hidden" name="<?php echo ucfirst($toDo); ?>" value="">
    <input type="hidden" value="<?php echo $form['id_tes']; ?>" name="id_tes">
    <input type="hidden" value="<?php echo $form['tipdoc']; ?>" name="tipdoc">
    <input type="hidden" value="<?php echo $form['id_con']; ?>" name="id_con">
    <input type="hidden" value="<?php echo $form['address']; ?>" name="address">
    <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno">
    <input type="hidden" value="<?php echo $form['giotra']; ?>" name="giotra">
    <input type="hidden" value="<?php echo $form['mestra']; ?>" name="mestra">
    <input type="hidden" value="<?php echo $form['anntra']; ?>" name="anntra">
    <input type="hidden" value="<?php echo $form['oratra']; ?>" name="oratra">
    <input type="hidden" value="<?php echo $form['mintra']; ?>" name="mintra">
    <input type="hidden" value="<?php echo $form['protoc']; ?>" name="protoc">
    <input type="hidden" value="<?php echo $form['speban']; ?>" name="speban">
    <input type="hidden" value="<?php echo $form['numrat']; ?>" name="numrat">
    <input type="hidden" value="<?php echo $form['change_pag']; ?>" name="change_pag">
    <input type="hidden" value="<?php echo $form['listin']; ?>" name="listin">
    <input type="hidden" value="<?php echo $form['destin']; ?>" name="destin">
    <input type="hidden" value="<?php echo $form['id_des']; ?>" name="id_des">
    <input type="hidden" value="<?php echo $form['id_des_same_company']; ?>" name="id_des_same_company">
    <input type="hidden" value="<?php echo $form['gross_weight']; ?>" name="gross_weight">
    <input type="hidden" value="<?php echo $form['banapp']; ?>" name="banapp">
    <input type="hidden" value="<?php echo $form['net_weight']; ?>" name="net_weight">
    <input type="hidden" value="<?php echo $form['units']; ?>" name="units">
    <input type="hidden" value="<?php echo $form['volume']; ?>" name="volume">
    <input type="hidden" value="<?php echo $form['id_parent_doc']; ?>" name="id_parent_doc" />
<?php
/** inizio modifica FP 28/10/2015 */
$strArrayDest = base64_encode(serialize($array_destinazioni));
echo '<input type="hidden" value="' . $strArrayDest . '" name="rs_destinazioni">' . "\n"; // salvo l'array delle destinazioni in un hidden input
/** fine modifica FP */
?>
    <input type="hidden" value="<?php echo $form['traspo']; ?>" name="traspo">
    <input type="hidden" value="<?php echo $form['spevar']; ?>" name="spevar">
    <input type="hidden" value="<?php echo $form['ivaspe']; ?>" name="ivaspe">
    <input type="hidden" value="<?php echo $form['pervat']; ?>" name="pervat">
    <input type="hidden" value="<?php echo $form['cauven']; ?>" name="cauven">
    <input type="hidden" value="<?php echo $form['caucon']; ?>" name="caucon">
    <input type="hidden" value="<?php echo $form['banapp']; ?>" name="banapp">
    <div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
		<div class="col-lg-12">
			<div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
        <div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
      </div>
      <iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
    </div>
    <div class="text-center">
        <div>
            <b>
            <?php
            if (isset($tesdoc) && ($tesdoc['ddt_type']=="T"||$tesdoc['ddt_type']=="L")){
               echo '<div class="container">
                  <div class="row alert alert-warning fade in" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Chiudi">
                  <span aria-hidden="true">&times;</span>
                  </button>';
                echo '<span class="glyphicon glyphicon-alert" aria-hidden="true"></span> ATTENZIONE!=> Si sta tentando di modificare un DDT per il quale è stata già registrata la fattura.';
                echo "</div></div>\n";
            }
            if (count($msg['err']) > 0) { // ho un errore
                $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
            }
            if (count($msg['war']) > 0) { // ho un alert
                $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
            }
            if (isset ($_GET['DDT'])){
              $doctransl="ADT";
            } else {
              $doctransl=$form['tipdoc'];
            }
            if ($form['id_tes'] > 0 && substr($form['tipdoc'], 0, 2) == 'AF') {
               //$title = $script_transl[0][$form['tipdoc']] . ' prot.<input type="text" class="text-right" style="width:5em;" id="protoc" name="protoc" value="'.$form['protoc'].'">';
                $title = $script_transl[0][$doctransl] . ' prot.'.$form['protoc'];
            } else {
                $title = $script_transl[0][$doctransl];
            }
            if ($form['id_tes'] > 0) { // è una modifica
              echo $script_transl['upd_this'].$title;
            } else {
                echo $script_transl['ins_this'].$title;
            }
            $select_fornitore = new selectPartner('clfoco');
            $select_fornitore->selectDocPartner('clfoco', $form['clfoco'], $form['search']['clfoco'], 'clfoco', $script_transl['search_partner'], $admin_aziend['masfor']);
            ?>
            </b>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="container-fluid">
            <div class="col-xs-12">
              <div class="col-sm-12 col-md-6">
                <div class="form-group col-sm-12 col-md-6">
                    <label for="address" class="col-form-label"><?php echo $script_transl['address']; ?></label>
                    <div><?php echo substr($form['address'],0,40); ?></div>
                </div>
                <div class="form-group col-sm-12 col-md-6">
                    <label for="datreg" class="col-form-label"><?php echo $script_transl['datreg']; ?></label>
                    <input type="text" class="form-control col-lg-2" id="datreg" name="datreg" value="<?php echo $form['datreg']; ?>">
                </div>
			  </div>
              <div class="form-col-sm-12 col-md-6">
                <div class="form-group col-sm-12 col-md-6">
                    <label for="seziva" class="col-form-label"><?php echo $script_transl['seziva']; ?></label>
                    <div><?php $gForm->selectNumber('seziva', $form['seziva'], 0, 1, 9, 'col-sm-4'); ?></div>
                </div>
				<div class="form-group col-sm-12 col-md-6">
                    <label for="caumag" class="col-form-label" ><?php echo $script_transl['caumag']; ?></label>
                    <div><?php $magazz->selectCaumag($form['caumag'], $docOperat[$form['tipdoc']], false, '', "col-sm-12",1);?></div>
				</div>
			  </div>
			</div>
            <div class="col-xs-12">
<?php	switch($form['tipdoc']){ // sui DDT non ho numero e data fattura
				case 'DDR': case 'DDL': ?>
                <div class="col-sm-12 col-md-6">
                    <div class="form-group col-sm-12 col-md-6">
                        <label for="numdoc" class="col-form-label"><?php echo $script_transl['numdoc']; ?></label>
                        <div class="bg-success"><?php echo $form['numdoc']; ?>
                            <input type="hidden" id="numdoc" name="numdoc" value="<?php echo $form['numdoc']; ?>">
                            <input type="hidden" id="numfat" name="numfat" value="<?php echo $form['numfat']; ?>">
                        </div>
                    </div>
                    <div class="form-group col-sm-12 col-md-6">
                        <label for="datemi"  class="col-form-label"><?php echo $script_transl['datemi']; ?></label>
                        <div>
                            <input type="text" class="form-control" id="datemi" name="datemi" value="<?php echo $form['datemi']; ?>">
                            <input type="hidden" id="datfat" name="datfat" value="<?php echo $form['datfat']; ?>">
                        </div>
                    </div>
                </div>
<?php		break;
			case 'ADT': case 'RDL':?>
                <div class="col-sm-12 col-md-6">
                    <div class="form-group col-sm-12 col-md-6">
                        <label for="numdoc" class="col-form-label"><?php echo $script_transl['numdoc']; ?></label>
                        <div>
                            <input type="text" class="form-control" min="1" id="numdoc" name="numdoc" value="<?php echo $form['numdoc']; ?>">
                            <input type="hidden" id="numfat" name="numfat" value="<?php echo $form['numfat']; ?>">
                        </div>
                    </div>
                    <div class="form-group col-sm-12 col-md-6">
                        <label for="datemi" class="col-form-label"><?php echo $script_transl['datemi']; ?></label>
                        <div>
                            <input type="text" class="form-control" id="datemi" name="datemi" value="<?php echo $form['datemi']; ?>">
                            <input type="hidden" id="datfat" name="datfat" value="<?php echo $form['datfat']; ?>">
                        </div>
                    </div>
                </div>
<?php		break;
			case 'AFA': case 'AFC': case 'AFT': case 'AFD': ?>
                <div class="col-sm-12 col-md-6">
                    <div class="form-group col-sm-12 col-md-6">
                        <label for="numdoc" class="col-form-label"><?php echo $script_transl['numfat']; ?></label>
                        <div>
                            <input type="text" class="form-control" id="numfat" name="numfat" maxlength="20" value="<?php echo $form['numfat']; ?>">
                            <input type="hidden" id="numdoc" name="numdoc" value="<?php echo $form['numdoc']; ?>">
                        </div>
                    </div>
                    <div class="form-group col-sm-12 col-md-6">
                        <label for="datfat" class="col-form-label"><?php echo $script_transl['datfat']; ?></label>
                        <div>
                            <input type="text" class="form-control" id="datfat" name="datfat" value="<?php echo $form['datfat']; ?>">
                            <input type="hidden" id="datemi" name="datemi" value="<?php echo $form['datemi']; ?>">
                        </div>
                    </div>
                </div>
<?php	} ?>
                <div class="col-sm-12 col-md-6">
                    <div class="form-group col-sm-12 col-md-6">
                        <label for="banapp" class="col-form-label" ><?php echo $script_transl['banapp']; ?></label>
                        <div>
                            <?php
							$select_banapp = new selectbanapp("banapp");
							$select_banapp->addSelected($form["banapp"]);
							$select_banapp->output('','col-lg-12');
							?>
                        </div>
                    </div>
                    <div class="form-group col-sm-12 col-md-6">
                        <label for="pagame" class="col-form-label" ><?php echo $script_transl['pagame']; ?></label>
                        <div>
                            <?php
							$select_pagame = new selectpagame("pagame");
							$select_pagame->addSelected($form["pagame"]);
							$select_pagame->output('','col-lg-12');
							?>
                        </div>
                    </div>
                </div>
            </div> <!-- chiude group  -->
            <div class="col-sm-12">
              <div class="col-sm-6">
                <div class="form-group col-sm-12">
                  <label for="id_orderman" class="col-form-label col-xs-6"><?php echo $script_transl['orderman']; ?></label>
							<?php
							$select_prod = new selectproduction("in_id_orderman");
							$select_prod->addSelected($form['in_id_orderman']);
							$select_prod->output($form['coseprod'],true,'col-lg-12');
							?>
                </div>
              </div>
              <div class="col-sm-3">
							<?php
              echo ' <div class="form-groupcol-sm-12">';
              echo ' <label for="all_same_orderman" class="col-form-label col-xs-12">Attribusici <b>TUTTO</b> alla produzione:</label>';
              if ($form['in_id_orderman']>0){
                echo '<input type="submit" class="btn btn-info btn-xs" name="all_same_orderman" title="Tutti i righi alla produzione '.$form['coseprod'].'" value=" '.$form['coseprod'].'" />';
              }
              echo '</div>';
              ?>
              </div>
              <div class="col-sm-3">
                <div class="form-group col-sm-12">
                </div>
                <div class="form-group col-sm-12 col-md-6">
                    <label for="sconto" class="col-form-label"><?php echo $script_transl['sconto']; ?></label>
                    <div>
                        <input type="number" step="0.01" max="100" id="sconto" name="sconto" placeholder="<?php echo $script_transl['sconto']; ?>" value="<?php echo $form['sconto']; ?>" onchange="this.form.submit();">
                    </div>
                </div>
              </div>
            </div> <!-- chiude group  -->



				<script>
					function selectCheckbox() {
						var inputs = document.getElementsByTagName('input');
						var checkboxes = [];
						for (var i = 0; i < inputs.length; i++){
							var input = inputs[i];
							if (input.getAttribute('type') == 'checkbox'){
								checkboxes.push(input);
							}
						}
						return checkboxes;
					}
					function check(checks){
					  var checkboxes = selectCheckbox();
					  for(var i=0; i < checkboxes.length; i++){
						checkboxes[i].checked = checks.checked;
					  }
					  // document.forms['tesdoc'].submit();
					}
				</script>

				<?php
				if ($ddt->num_rows>0 AND $form['tipdoc']=="AFA"){//  se AFA e ci sono DDT da fatturare apro la scelta DDT da fatturare
					?>
					<div class="col-sm-12 col-xs-12">
						<div class="row bg-info" style="border-bottom: 1px solid;">
							<div class="col-sm-4 col-xs-4">
								Numero DDT acquisto
							</div>
							<div class="col-sm-4 col-xs-4">
								Data emissione
							</div>

							<div class="col-sm-2 col-xs-2" align="right">
								Seleziona
							</div>
							<div class="col-sm-2 col-xs-2" align="left">
								TUTTI <input type="checkbox" onClick="check(this)">
							</div>
						</div>
						<?php
						$n=0;
						while ($item = gaz_dbi_fetch_array($ddt)){ // li ciclo
							$avqty = 0;
							?>
							<div class="col-sm-12 col-xs-12" style="border-bottom: 1px solid;">
								<div class="col-sm-4 col-xs-4">
									<?php
                  echo '<a class="btn btn-xs btn-success" href="admin_docacq.php?id_tes='.$item['id_tes'].'&amp;Update&amp;DDT" title="Modifica il DdT di acquisto">  <i class="glyphicon glyphicon-edit"></i>&nbsp;'.$item['numdoc'].'</a>';
									echo '<input type="hidden" name="id_tes'. $n .'" value="'. $item['id_tes'] . '">';
									?>
								</div>
								<div class="col-sm-5 col-xs-5">
									<?php echo $item['datemi'];
									?>
								</div>
								<div class="col-sm-3 col-xs-3" align="left">
									<?php if (isset($form['check_ddt'.$n]) AND $form['check_ddt'.$n]=="checked"){?>
									<input type="checkbox" name="check_ddt<?php echo $n; ?>" value="checked" checked >
									<?php } else {?>
									<input type="checkbox" name="check_ddt<?php echo $n; ?>" value="checked" >
									<?php }?>
									<input type="hidden" name="num_ddt" value="<?php echo $n; ?>">
								</div>
							</div>
							<?php
							$n++;
						}
						?>
						<div class="col-sm-12 col-xs-12">
							<div class="col-sm-10 col-xs-10" align="right">
							</div>
							<div class="col-sm-2 col-xs-2" align="right">
							<input class="btn btn-block btn-warning" id="preventDuplicate" onClick="chkSubmit();" type="submit" name="ddt" value="Acquisisci DDT">
							</div>
						</div>
					</div><!-- chiude DDT container  -->
					<?php
				}
				?>
        </div><!-- chiude container  -->
    </div><!-- chiude panel  -->
    <div align="center"><b>Corpo</b></div>
		<input type="hidden" value="<?php echo $form['in_codice_fornitore']; ?>" name="in_codice_fornitore" />
		<input type="hidden" value="<?php echo $form['in_descri']; ?>" name="in_descri" />
		<input type="hidden" value="<?php echo $form['in_pervat']; ?>" name="in_pervat" />
		<input type="hidden" value="<?php echo $form['in_unimis']; ?>" name="in_unimis" />
		<input type="hidden" value="<?php echo $form['in_extdoc']; ?>" name="in_extdoc" />
		<input type="hidden" value="<?php echo $form['in_prelis']; ?>" name="in_prelis" />
		<input type="hidden" value="<?php echo $form['in_id_mag']; ?>" name="in_id_mag" />
		<input type="hidden" value="<?php echo $form['in_id_order']; ?>" name="in_id_order" />
		<input type="hidden" value="<?php echo $form['in_annota']; ?>" name="in_annota" />
		<input type="hidden" value="<?php echo $form['in_pesosp']; ?>" name="in_pesosp" />
		<input type="hidden" value="<?php echo $form['in_quamag']; ?>" name="in_quamag" />
		<input type="hidden" value="<?php echo $form['in_scorta']; ?>" name="in_scorta" />
		<input type="hidden" value="<?php echo $form['in_codric']; ?>" name="in_codric" />
		<input type="hidden" value="<?php echo $form['in_ritenuta']; ?>" name="in_ritenuta" />
		<input type="hidden" value="<?php echo $form['in_provvigione']; ?>" name="in_provvigione" />
		<input type="hidden" value="<?php echo $form['in_gooser']; ?>" name="in_gooser" />
		<input type="hidden" value="<?php echo $form['in_lot_or_serial']; ?>" name="in_lot_or_serial" />
		<input type="hidden" value="<?php echo $form['in_SIAN']; ?>" name="in_SIAN" />
		<input type="hidden" value="<?php echo $form['in_status']; ?>" name="in_status" />
		<input type="hidden" value="<?php echo $form['in_tiprig']; ?>" name="in_tiprig" />
		<input type="hidden" value="<?php echo $form['in_codart']; ?>" name="in_codart" />
		<input type="hidden" value="<?php echo $form['in_quality']; ?>" name="in_quality" />
		<input type="hidden" value="<?php echo $form['in_quanti']; ?>" name="in_quanti" />
		<input type="hidden" value="<?php echo $form['in_codvat']; ?>" name="in_codvat" />
		<input type="hidden" value="<?php echo $form['in_sconto']; ?>" name="in_sconto" />
		<input type="hidden" value="<?php echo $form['hidden_req']; ?>" name="hidden_req" />
		<?php
		$nr=1;
		if (count($form['rows']) > 0) {
			$tot = 0;
			$net_weight=0;
			$volume=0;
			$units=0;
			$totimp_body = 0.00;
			$totivafat = 0.00;
			$totimpfat = 0.00;
			$castle = array();
			$rit = 0;
			$carry = 0;
			$ctrl_orderman=0;
			$rowshead=array();
			foreach ($form['rows'] as $k => $v) {
				$nr++;
				// addizione ai totali peso,pezzi,volume
				$artico = gaz_dbi_get_row($gTables['artico'], 'codice', $v['codart']);
        if (!$artico) $artico=array('peso_specifico'=>false,'volume_specifico'=>false,'pack_units'=>false,'good_or_service'=>0,'annota'=>'','unimis'=>'');
				$campart = @gaz_dbi_get_row($gTables['camp_artico'], "codice", $v['codart']);
				$v['descri_codric'] = @gaz_dbi_get_row($gTables['clfoco'], 'codice', $v['codric'])['descri'];
				$net_weight += $v['quanti'] * $artico['peso_specifico'];
				if ($artico['pack_units'] > 0) {
					$units += intval(round($v['quanti'] / $artico['pack_units']));
				}
				$volume += $v['quanti'] * $artico['volume_specifico'];
				// fine addizione peso,pezzi,volume
				$btn_class = 'btn-success';
				$btn_title = '';
				$peso = 0;
				if ($v['tiprig'] == 0) {
					if ($artico['good_or_service']==1){
						$btn_class = 'btn-info';
						$btn_title = ' Servizio';
					} elseif ($v['quamag'] < 0.00001 && $admin_aziend['conmag']==2) { // se gestisco la contabilità di magazzino controllo presenza articolo
						$btn_class = 'btn-danger';
						$btn_title = ' ARTICOLO NON DISPONIBILE';
					} elseif ($v['quamag'] <= $v['scorta'] && $admin_aziend['conmag']==2) { // se gestisco la contabilità di magazzino controllo il sottoscorta
						$btn_class = 'btn-warning';
						$btn_title = ' Articolo sottoscorta: disponibili '.$v['quamag'].'/'.floatval($v['scorta']);
					} else {
						$btn_class = 'btn-success';
						$btn_title = $v['quamag'].' '.$v['unimis'].' disponibili';
					}
					if ($v['pesosp'] <> 0) {
						$peso = gaz_format_number($v['quanti'] / $v['pesosp']);

					}
				}
				$imprig = 0;
				 //creo il castelletto IVA
				if ($v['tiprig'] <= 1 || $v['tiprig'] == 4 || $v['tiprig'] == 50) { // calcolo per tipi righi normale, forfait e cassa previdenziale
					$imprig = CalcolaImportoRigo($v['quanti'], $v['prelis'], $v['sconto']);
					$v_for_castle = CalcolaImportoRigo($v['quanti'], $v['prelis'], array($v['sconto'], $form['sconto']));
					if ($v['tiprig'] == 1) {// se del tipo forfait
						$imprig = CalcolaImportoRigo(1, $v['prelis'], 0);
						$v_for_castle = CalcolaImportoRigo(1, $v['prelis'], $form['sconto']);
					}
					if ($v['tiprig'] == 4) {// e se del tipo cassa previdenziale
						$imprig = round($v['provvigione']* $v['prelis']/100,2);
						$v_for_castle =  $imprig;
					}
					if (!isset($castle[$v['codvat']])) {
						$castle[$v['codvat']]['impcast'] = 0.00;
					}
					$totimp_body += $imprig;
					$castle[$v['codvat']]['impcast'] += $v_for_castle;
					$rit += round($imprig * $v['ritenuta'] / 100, 2);
				} elseif ($v['tiprig'] == 3) {
					$carry += $v['prelis'];
				} elseif ($v['tiprig'] == 90) { // rigo vendita cespite ammortizzabile
					$imprig = CalcolaImportoRigo(1, $v['prelis'], 0);
					$v_for_castle = CalcolaImportoRigo(1, $v['prelis'], $form['sconto']);
					if (!isset($castle[$v['codvat']])) {
						$castle[$v['codvat']]['impcast'] = 0.00;
					}
					$totimp_body += $imprig;
					$castle[$v['codvat']]['impcast'] += $v_for_castle;
				}
				// fine calcolo importo rigo, totale e castelletto IVA
				// colonne non editabili
				$vidrig=(isset($v['id_rig']))?$v['id_rig']:0;
				echo "<input type=\"hidden\" value=\"" . $v['status'] . "\" name=\"rows[$k][status]\">\n";
				echo "<input type=\"hidden\" value=\"" . $vidrig . "\" name=\"rows[$k][id_rig]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['codart'] . "\" name=\"rows[$k][codart]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['quality'] . "\" name=\"rows[$k][quality]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['SIAN'] . "\" name=\"rows[$k][SIAN]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['tiprig'] . "\" name=\"rows[$k][tiprig]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['codvat'] . "\" name=\"rows[$k][codvat]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['pervat'] . "\" name=\"rows[$k][pervat]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['codric'] . "\" name=\"rows[$k][codric]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['id_mag'] . "\" name=\"rows[$k][id_mag]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['id_warehouse'] . "\" name=\"rows[$k][id_warehouse]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['id_order'] . "\" name=\"rows[$k][id_order]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['annota'] . "\" name=\"rows[$k][annota]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['scorta'] . "\" name=\"rows[$k][scorta]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['quamag'] . "\" name=\"rows[$k][quamag]\">\n";
        echo '<input type="hidden" value="' . $v['extdoc'] . '" name="rows[' . $k . '][extdoc]" />';
        echo "<input type=\"hidden\" value=\"" . $v['pesosp'] . "\" name=\"rows[$k][pesosp]\">\n";
				echo '<input type="hidden" value="' . $v['lot_or_serial'] . '" name="rows[' . $k . '][lot_or_serial]" />';
				echo '<input type="hidden" value="' . ((array_key_exists('id_lotmag',$v)) ? $v['id_lotmag'] : '') . '" name="rows[' . $k . '][id_lotmag]" />';
				// colonne editabili
				echo "<input type=\"hidden\" value=\"" . $v['descri'] . "\" name=\"rows[$k][descri]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['unimis'] . "\" name=\"rows[$k][unimis]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['quanti'] . "\" name=\"rows[$k][quanti]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['prelis'] . "\" name=\"rows[$k][prelis]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['sconto'] . "\" name=\"rows[$k][sconto]\">\n";

				echo "<input type=\"hidden\" value=\"" . $v['codice_fornitore'] . "\" name=\"rows[$k][codice_fornitore]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['ritenuta'] . "\" name=\"rows[$k][ritenuta]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['provvigione'] . "\" name=\"rows[$k][provvigione]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['id_orderman'] . "\" name=\"rows[$k][id_orderman]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['gooser'] . "\" name=\"rows[$k][gooser]\">\n";
				echo "<input type=\"hidden\" value=\"" . $v['filename'] . "\" name=\"rows[$k][filename]\">\n";

				if ($ddtchecked > 0 ){ // se ci sono DDT selezionati disabilito alcuni pulsanti.
					$disabled="disabled";
				} else {
					$disabled="";
				}
				// creo l'array da passare alla funzione per la creazione della tabella responsive
				$resprow[$k] = array(
					array('head' => $script_transl["nrow"], 'class' => '',
						'value' => '<button '.$disabled.' type="image" name="upper_row[' . $k . ']" class="btn btn-default btn-xs" title="' . $script_transl['upper_row'] . '">
									' . ($k + 1) . ' <i class="glyphicon glyphicon-arrow-up"></i></button>'),
					array('head' => $script_transl["codart"], 'class' => '',
						'value' => ' <button name="upd_row[' . $k . ']" class="btn ' . $btn_class . ' btn-xs"
						title="' . $script_transl['update'] . $script_transl['thisrow'] . '! ' . $btn_title . '"
						type="submit"'.$disabled.'>
									<i class="glyphicon glyphicon-refresh"></i>&nbsp;' . $v['codart'] . '
									</button>',
						'td_content' => ' title="' . $script_transl['update'] . $script_transl['thisrow'] . ' Sottoscorta =' . $v['scorta'] . '" '
					),
					array('head' => 'Magazzino', 'class' => '',
						'value' => 	'<small>'.$magazz->selectIdWarehouse('rows[' . $k . '][id_warehouse]',$v["id_warehouse"],true,'col-xs-12',$v['codart'],gaz_format_date($form['datreg'],true),($docOperat[$form['tipdoc']]*$v['quanti']*-1)).'</small>'
					),
					array('head' => $script_transl["codice_fornitore"], 'class' => '',
						'value' => '<input class="gazie-tooltip" data-type="product-thumb" data-id="' . $v["codart"] . '" data-title="' . $v['annota'] . '" type="text" name="rows[' . $k . '][codice_fornitore]" value="' . $v['codice_fornitore'] . '"/>'
					),
					array('head' => $script_transl["descri"], 'class' => 'col-lg-4',
						'value' => '<input class="gazie-tooltip col-lg-12" data-type="product-thumb" data-id="' . $v["codart"] . '" data-title="' . $v['annota'] . '" type="text" name="rows[' . $k . '][descri]" value="' . $v['descri'] . '" maxlength="100" />'
					),
					array('head' => $script_transl["unimis"], 'class' => '',
						'value' => '<input class="gazie-tooltip" data-type="weight" data-id="' . $peso . '" data-title="' . $script_transl['weight'] . '" type="text" name="rows[' . $k . '][unimis]" value="' . $v['unimis'] . '" maxlength="3" />'
					),
					array('head' => $script_transl["quanti"], 'class' => 'text-right numeric',
						'value' => '<input type="number" step="any" class="gazie-tooltip" data-type="weight" data-id="' . $peso . '" data-title="' . $script_transl['weight'] . '" name="rows[' . $k . '][quanti]" value="' . $v['quanti'] . '" style="width:8em;" maxlength="11" onchange="this.form.submit();" />'
					),
					array('head' => $script_transl["prezzo"], 'class' => 'text-right numeric',
						'value' => '<input type="number" step="any" name="rows[' . $k . '][prelis]" value="' . $v['prelis'] . '" style="width:8em;" maxlength="15" onchange="this.form.submit()" />'
					),
					array('head' => $script_transl["sconto"], 'class' => 'text-right numeric',
						'value' => '<input type="number" step="0.01" name="rows[' . $k . '][sconto]" value="' . $v['sconto'] . '" style="width:3.5em;" maxlength="4" onchange="this.form.submit()" />'),
					array('head' => $script_transl["amount"], 'class' => 'text-right numeric', 'value' => gaz_format_number($imprig), 'type' => ''),
					array('head' => $script_transl["codvat"], 'class' => 'text-center numeric', 'value' => $v['pervat'], 'type' => ''),
					array('head' => $script_transl["total"], 'class' => 'text-right numeric bg-warning', 'value' => gaz_format_number($imprig), 'type' => ''),
					array('head' => $script_transl["codric"], 'class' => 'text-center', 'value' =>'<span title="'.$v['descri_codric'].'">'. $v['codric'].'</span>'),
					array('head' => $script_transl["delete"], 'class' => 'text-center',
						'value' => '<button '.$disabled.' type="submit" class="btn btn-default btn-xs btn-elimina" name="del[' . $k . ']" title="' . $script_transl['delete'] . $script_transl['thisrow'] . '"><i class="glyphicon glyphicon-trash"></i></button>')
				);
				// creo una intestazione della produzione di provenienza
				if ($ctrl_orderman<>$v['id_orderman'] && $v['id_orderman'] != '') { // ricordo con un rigo la produzione di riferimento
					if ($v['id_orderman']==0){
						$descri_orderman='<div class="btn btn-xs btn-warning"> Non riferiti ad una produzione <i class="glyphicon glyphicon-arrow-down"> </i></div>';
					} else {
						$orderman = gaz_dbi_get_row($gTables['orderman'], "id", $v['id_orderman']);
            if (!$orderman ) $orderman = array('id'=>0,'description'=>'');
						$descri_orderman='<div class="btn btn-xs btn-info">per Produzione n.' .$orderman['id'].' - '.$orderman['description'].' '.$v['id_orderman'].' <i class="glyphicon glyphicon-arrow-down"> </i></div>';
					}
					$rowshead[$k]='<td colspan=13>'.$descri_orderman.'</td>';
				}

				switch ($v['tiprig']) {
					case "0":
						$lm_acc = '';
						if ($v['lot_or_serial'] > 0) {

							if ($form['tipdoc']!="DDR" AND $form['tipdoc']!="DDL"){ // Antonio Germani - se non è Documento di Reso o di conto lavorazione apro gestione lotti come nuovo inserimento
								if (empty($form['rows'][$k]['filename'])) {
									$lm_acc .='<div><button class="btn btn-xs btn-danger" type="image" data-toggle="collapse" href="#lm_dialog' . $k . '">'
									. $script_transl['insert'] . 'certificato  <i class="glyphicon glyphicon-tag"></i>'
									. '</button></div>';
								} else {
									$lm_acc .='<div><button class="btn btn-xs btn-success" type="image" data-toggle="collapse" href="#lm_dialog' . $k . '">'
									. $script_transl['lotmag']. ': '. $form['rows'][$k]['filename'] . ' <i class="glyphicon glyphicon-tag"></i>'
									. '</button></div>';
								}
								$lm_acc .='<div id="lm_dialog' . $k . '" class="collapse" >
								<div class="form-group">
								<div>';
								$lm_acc .='<input type="file" onchange="this.form.submit();" name="certfile_' . $k . '">
									<label>' . $script_transl['identifier'] . '</label><input type="text" name="rows[' . $k . '][identifier]" value="' . $form['rows'][$k]['identifier'] . '" ><br>
									<label>' . $script_transl['expiry'] . ' </label><input class="datepicker" type="text" name="rows[' . $k . '][expiry]"  value="' . $form['rows'][$k]['expiry'] . '" >
									</div>
									</div>
									</div>' . "\n";
								if (empty($form['rows'][$k]['identifier'])) {
									$lm_acc .='<div><button class="btn btn-xs btn-danger" type="image" data-toggle="collapse" href="#lm_dialog' . $k . '">'
									. $script_transl['insert'] . 'Lotto  <i class="glyphicon glyphicon-tag"></i>'
									. '</button></div>';
								} else {
									$lm_acc .='<div><button class="btn btn-xs btn-success" type="image" data-toggle="collapse" href="#lm_dialog' . $k . '">'
									. "Lotto: " .$form['rows'][$k]['identifier'] . ' - '. $form['rows'][$k]['expiry'] .' <i class="glyphicon glyphicon-tag"></i>'
									. '</button></div>';
								}

							} else { // altrimenti apro gestione lotti con scelta fra esistenti

								$lm->getAvailableLots($v['codart'], $v['id_mag']);
								// Antonio Germani - calcolo delle giacenze per ogni singolo lotto
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
								$v['id_lotmag']=(isset($v['id_lotmag']))?$v['id_lotmag']:'';
								$selected_lot = $lm->getLot($v['id_lotmag']);
								if(!isset($selected_lot['identifier'])){
									$selected_lot['identifier']="";
									$selected_lot['id']="";
									$selected_lot['expiry']="";
									$selected_lot['desdoc']="";
									$selected_lot['datdoc']="";
								}
								if (!isset($count[$selected_lot['identifier']])){
									$count[$selected_lot['identifier']]="";
								}
								if ($count[$selected_lot['identifier']]>=$v['quanti']){
									$lm_acc .='<div><button class="btn btn-xs btn-success" title="clicca per cambiare lotto" ';
								} else {
									$lm_acc .='<div><button class="btn btn-xs btn-danger" title="Disponibilità non sufficiente"';
								}
								$lm_acc .='type="image" data-toggle="collapse" href="#lm_dialog' . $k . '">'
								. $selected_lot['id']
								. '- lotto: ' . $selected_lot['identifier'];
								$lm_acc .=' <input type="hidden" value="' . $selected_lot['identifier'] . '" name="rows[' . $k . '][identifier]" />';
								$lm_acc .=' <input type="hidden" value="' . $selected_lot['id'] . '" name="rows[' . $k . '][id_lotmag]" />';
								if (intval ($selected_lot['expiry'])>0) {
									$lm_acc .=' scad:' . gaz_format_date($selected_lot['expiry']);
									$lm_acc .=' <input type="hidden" value="' . $selected_lot['expiry'] . '" name="rows[' . $k . '][expiry]" />';
								}
								$lm_acc .=' - disponibili: ' . gaz_format_quantity($count[$selected_lot['identifier']])
								. ' <i class="glyphicon glyphicon-tag"></i>'
								. ' rif:' . $selected_lot['desdoc']
								. ' - ' . gaz_format_date($selected_lot['datdoc']) .
								'</button>';
								if ($v['id_mag'] > 0) {
									$lm_acc .=' <a class="btn btn-xs btn-default" href="lotmag_print_cert.php?id_movmag=' . $v['id_mag'] . '" target="_blank"><i class="glyphicon glyphicon-print"></i></a>';
								}
								$lm_acc .='</div>';
								$lm_acc .='<div id="lm_dialog' . $k . '" class="collapse" >
										<div class="form-group">';
								if (count($lm->available) > 0) {
									foreach ($lm->available as $v_lm) {
										if ($v_lm['id'] <> $v['id_lotmag']) {
										if ($count[$v_lm['identifier']]>=$v['quanti']){
												$lm_acc .='<div>change to:<button class="btn btn-xs btn-warning" type="image" ';
											} else {
												$lm_acc .='<div>change to:<button class="btn btn-xs btn-danger" title="Q.tà non sufficiente" type="image" ';
											}
											$lm_acc .='onclick="this.form.submit();" name="new_lotmag[' . $k . '][' . $v_lm['id_lotmag'] . ']">'
											. $v_lm['id']
											. '- lotto: ' . $v_lm['identifier'];
											if (intval ($v_lm['expiry'])>0) {
												$lm_acc .=' scad:' . gaz_format_date($v_lm['expiry']);
											}
											$lm_acc .=' disponibili:' . gaz_format_quantity($count[$v_lm['identifier']]).'<i class="glyphicon glyphicon-tag"></i> rif:' . $v_lm['desdoc']
											. ' - ' . gaz_format_date($v_lm['datdoc'])
											. '</button></div>';
										}
									}
								} else {
									$lm_acc .='<div><button class="btn btn-xs btn-danger" type="button" disabled>Non sono disponibili altri lotti</button></div>';
								}
								$lm_acc .='</div>'
								. "</div>\n";
							}
						} else {
							$lm_acc .=' <input type="hidden" value="' . $v['identifier'] . '" name="rows[' . $k . '][identifier]" />';
							$lm_acc .=' <input type="hidden" value="' . $v['expiry'] . '" name="rows[' . $k . '][expiry]" />';
						}

						// Antonio Germani - Se l'articolo movimenta il SIAN apro il div SIAN
						if ($form['rows'][$k]['SIAN']>0) {
							?>
							<style>
							#gaz-responsive-table {
								float: left;
							}
							</style>
							<div class="col-md-4">
								<div class="form-group">
									<label for="oper_sian" class="col-sm-5 control-label"><?php echo "Operazione SIAN rigo",$k+1; ?></label>
									<?php
									$gForm->variousSelect('rows[' . $k . '][cod_operazione]', $script_transl['cod_operaz_value'], $form['rows'][$k]['cod_operazione'], "col-sm-7", false, '', false, 'style="max-width: 250px;"')
									?>
								</div>
							</div>
							<?php
							if (!isset($campart)){
								?>
								<div class="col-md-4">
									<div class="form-group">
									<p>ERRORE l'articolo non è impostato correttamente</p>
									</div>
								</div>
								<?php
							}elseif ($campart['confezione']==0){?>
							<div class="col-md-4">
								<div class="form-group">
									<label for="recip_stock" class="col-sm-5 control-label"><?php echo "recipiente stoccaggio rigo ",$k+1; ?></label>
									<?php
									$gForm->selectFromDB('camp_recip_stocc', 'rows[' . $k . '][recip_stocc]' ,'cod_silos', $form['rows'][$k]['recip_stocc'], 'cod_silos', 1, ' - Capacità kg. ','capacita','TRUE','col-sm-7' , null, '');
									?>
								</div>
							</div>
							<?php
							} else {
								echo '<input type="hidden" value="" name="rows[' . $k . '][recip_stocc]" />';
							}
							if ($form['rows'][$k]['cod_operazione']==9) { // se è un movimento aziendale chiedo recipiente destinazione
								?>
							<div class="col-md-4">
								<div class="form-group">
									<label for="good_or_service" class="col-sm-6 control-label"><?php echo "recipiente destinazione rigo ",$k+1; ?></label>
									<?php
									$gForm->selectFromDB('camp_recip_stocc', 'rows[' . $k . '][recip_stocc_destin]' ,'cod_silos', $form['rows'][$k]['recip_stocc_destin'], 'cod_silos', 1, ' - kg ','cod_silos','TRUE','col-sm-6' , null, '');
									?>
								</div>
							</div>
							<?php
							}
						} else {
							$lm_acc .=' <input type="hidden" value="" name="rows[' . $k . '][cod_operazione]" />';
							$lm_acc .=' <input type="hidden" value="" name="rows[' . $k . '][recip_stocc]" />';
							$lm_acc .=' <input type="hidden" value="" name="rows[' . $k . '][recip_stocc_destin]" />';
						}
						// fine apro SIAN

						$resprow[$k][3]['value'] .= $lm_acc;
						break;
					case "1":
						$resprow[$k][1]['value'] = '<button name="upd_row[' . $k . ']" class="btn btn-info btn-xs"
						title="' . $script_transl['update'] . $script_transl['thisrow'] . '"
						type="submit"><i class="glyphicon glyphicon-refresh"></i> forfait </button>';
						$resprow[$k][2]['value'] = ''; //magazzino
						$resprow[$k][3]['value'] = ''; //codice_fornitore
						// in caso di rigo forfait non stampo alcune colonne
						$resprow[$k][5]['value'] = ''; //unimis
						// scambio l'input con la colonna dell'importo...
						$resprow[$k][9]['value'] = $resprow[$k][7]['value'];
						$resprow[$k][6]['value'] = ''; //quanti
						$resprow[$k][7]['value'] = ''; //prelis
						$resprow[$k][8]['value'] = ''; //sconto
						break;
					case "2":
						$resprow[$k][1]['value'] = '<button name="upd_row[' . $k . ']" class="btn btn-info btn-xs"
						title="' . $script_transl['update'] . $script_transl['thisrow'] . '"
						type="submit"><i class="glyphicon glyphicon-refresh"></i> descrittivo </button>';
						$resprow[$k][2]['value'] = ''; //magazzino
						$resprow[$k][3]['value'] = ''; //codice_fornitore
						$resprow[$k][5]['value'] = ''; //unimis
						$resprow[$k][6]['value'] = ''; //quanti
						$resprow[$k][7]['value'] = ''; //prelis
						$resprow[$k][8]['value'] = ''; //sconto
						$resprow[$k][9]['value'] = ''; //quanti
						$resprow[$k][10]['value'] = ''; //prelis
						$resprow[$k][11]['value'] = '';
						$resprow[$k][12]['value'] = '';
						break;
					case "4":
						// in caso di rigo cassa previdenziale
						$resprow[$k][1]['value'] = '<button name="upd_row[' . $k . ']" class="btn btn-info btn-xs"
						title="' . $script_transl['update'] . $script_transl['thisrow'] . '"
						type="submit"><i class="glyphicon glyphicon-refresh"></i> cassa </button>';
						$resprow[$k][2]['value'] = ''; //magazzino
						$resprow[$k][3]['value'] = ''; //codice_fornitore
						$resprow[$k][5]['value'] = ''; //unimis
						// scambio l'input con la colonna dell'importo...
						$resprow[$k][9]['value'] = $resprow[$k][7]['value'];
						$resprow[$k][6]['value'] = ''; //quanti
						$resprow[$k][7]['value'] = 'Imponibile'; //prelis
						$resprow[$k][8]['value'] = '=>'; //sconto
						break;
					case "6":
						// in caso di rigo testo
						$resprow[$k][1]['value'] = '<button name="upd_row[' . $k . ']" class="btn btn-info btn-xs"
						title="' . $script_transl['update'] . $script_transl['thisrow'] . '"
						type="submit"><i class="glyphicon glyphicon-refresh"></i> testo </button>';
						$resprow[$k][2]['value'] = ''; //magazzino
						$resprow[$k][3]['value'] = ''; //codice_fornitore
            $resprow[$k][4]['value'] = '<textarea id="row_'.$k.'" name="row_'.$k.'" class="mceClass" style="width:100%;height:100px;">'.$form["row_$k"].'</textarea>'; // descri
						$resprow[$k][5]['value'] = ''; //unimis
						$resprow[$k][6]['value'] = ''; //quanti
						$resprow[$k][7]['value'] = ''; //prelis
						$resprow[$k][8]['value'] = ''; //sconto
						$resprow[$k][9]['value'] = ''; //quanti
						$resprow[$k][10]['value'] = ''; //prelis
						$resprow[$k][11]['value'] = '';
						$resprow[$k][12]['value'] = '';
						break;
          case "50":
						$resprow[$k][1]['value'] = '<button name="upd_row[' . $k . ']" class="btn btn-info btn-xs"
						title="' . $script_transl['update'] . $script_transl['thisrow'] . '"
						type="submit"><i class="glyphicon glyphicon-refresh"></i> Normale c/allegato</button>';
            if (empty($form['rows'][$k]['extdoc'])) {
              $html= '<div><button class="btn btn-xs btn-danger" type="image" data-toggle="collapse" href="#extdoc_dialog' . $k . '">'
              . $script_transl['insert'] . ' documento esterno <i class="glyphicon glyphicon-tag"></i>'
              . '</button></div>';
            } else {
              $html= '<div>documento esterno:<button class="btn btn-xs btn-success" type="image" data-toggle="collapse" href="#extdoc_dialog' . $k . '">'
              . $form['rows'][$k]['extdoc'] . ' <i class="glyphicon glyphicon-tag"></i>'
              . '</button> ' . round($form['rows'][$k]['pesosp']) . 'KB</div>';
            }
            $html .= '<div id="extdoc_dialog' . $k . '" class="collapse" >
                  <div class="form-group">
                  <div>';
            $html .= '<input type="file" onchange="this.form.submit();" name="docfile_' . $k . '" accept=".pdf" />
                            <label>File: ' . $form['rows'][$k]['extdoc'] . '</label><input type="hidden" name="rows[' . $k . '][extdoc]" value="' . $form['rows'][$k]['extdoc'] . '" >
              </div>
              </div>
              </div>';
						$resprow[$k][2]['value'] = $html; //magazzino
            break;
          case "51":
						$resprow[$k][1]['value'] = '<button name="upd_row[' . $k . ']" class="btn btn-info btn-xs"
						title="' . $script_transl['update'] . $script_transl['thisrow'] . '"
						type="submit"><i class="glyphicon glyphicon-refresh"></i> Descrittivo c/allegato</button>';
            if (empty($form['rows'][$k]['extdoc'])) {
              $html= '<div><button class="btn btn-xs btn-danger" type="image" data-toggle="collapse" href="#extdoc_dialog' . $k . '">'
              . $script_transl['insert'] . ' documento esterno <i class="glyphicon glyphicon-tag"></i>'
              . '</button></div>';
            } else {
              $html= '<div>documento esterno:<button class="btn btn-xs btn-success" type="image" data-toggle="collapse" href="#extdoc_dialog' . $k . '">'
              . $form['rows'][$k]['extdoc'] . ' <i class="glyphicon glyphicon-tag"></i>'
              . '</button> ' . round($form['rows'][$k]['pesosp']) . 'KB</div>';
            }
            $html .= '<div id="extdoc_dialog' . $k . '" class="collapse" >
                  <div class="form-group">
                  <div>';
            $html .= '<input type="file" onchange="this.form.submit();" name="docfile_' . $k . '" accept=".pdf" />
                            <label>File: ' . $form['rows'][$k]['extdoc'] . '</label><input type="hidden" name="rows[' . $k . '][extdoc]" value="' . $form['rows'][$k]['extdoc'] . '" >
              </div>
              </div>
              </div>';
						$resprow[$k][2]['value'] = $html; //magazzino
						$resprow[$k][3]['value'] = ''; //codice_fornitore
						$resprow[$k][5]['value'] = ''; //unimis
						$resprow[$k][6]['value'] = ''; //quanti
						$resprow[$k][7]['value'] = ''; //prelis
						$resprow[$k][8]['value'] = ''; //sconto
						$resprow[$k][9]['value'] = ''; //quanti
						$resprow[$k][10]['value'] = ''; //prelis
						$resprow[$k][11]['value'] = '';
						$resprow[$k][12]['value'] = '';
          break;
        }
				$ctrl_orderman=$v['id_orderman'];
			}
			if ($net_weight > 0 ){
				$form['net_weight']=$net_weight;
			}
			if ($units > 0 ){
				$form['units']=$units;
			}
			if ($volume > 0 ){
				$form['volume']=$volume;
			}
			$gForm->gazResponsiveTable($resprow, 'gaz-responsive-table',$rowshead);
		} else {
    echo '<div id="alert-zerorows" class="alert alert-danger col-xs-12">' . $script_transl['zero_rows'] . '</div>';
    }
		$class_conf_row='btn-success';
    $descributton = $script_transl['insert'];
    $nurig = count($form['rows'])+1;
		if ($ddtchecked < 1 ){ // se non ci sono DDT selezionati apro input manuale righi doc
      $expsts = explode('UPDROW',$form['in_status']);
      if (isset($expsts[1])){
        $nurig = (int)$expsts[1]+1;
        $class_conf_row = 'btn-warning';
        $descributton = $script_transl['update'];
      }
      $descributton .= ' il rigo '.$nurig;
		?>
		<div class="panel input-area">
		  <div class="container-fluid">
				<ul class="nav nav-tabs">
					<li><a href="#insrow1"> <?php echo $script_transl['conf_row']; ?> </a></li>
					<li><a href="#" id="addmodal" href="#myModal" data-toggle="modal" data-target="#edit-modal" class="btn btn-xs btn-default"><i class="glyphicon glyphicon-export"></i><?php echo $script_transl['add_article']; ?></a></li>
				</ul>
			<div class="panel-body col-xs-12">
				<div class="col-xs-12">
					<div class="col-sm-12 col-md-6">
						<div class="form-group col-sm-12 col-md-6">
									<label for="tiprig" class="col-form-label"><?php echo $script_transl['tiprig']; ?></label>
									<div>
										<?php $gForm->selTypeRow('in_tiprig', $form['in_tiprig'],'',$script_transl['tiprig_value']);
										?>
									</div>
						</div>
						<div class="form-group col-sm-12 col-md-6">
							<div>
									<label for="item" class="col-form-label"><?php echo $script_transl['item']; ?></label>
									<div>
									<?php
									$select_artico = new selectartico("in_codart");
									$select_artico->addSelected($form['in_codart']);
									$select_artico->output(substr($form['cosear'], 0, 32), 'C', "");
									?>
									</div>
							</div>
						</div>
					</div><!-- chiude form-group  -->
					<div class="col-sm-12 col-md-6">
						<div class="form-group col-sm-12 col-md-6">
									<label for="quanti" class="col-form-label"><?php echo $script_transl['quanti']; ?></label>
									<div>
									<input type="number" step="any" tabindex=6 value="<?php echo $form['in_quanti']; ?>" name="in_quanti" />
									</div>
						</div>
						<div class="form-group col-sm-12 col-md-6">
									<label for="sconto" class="col-form-label"><?php echo $script_transl['sconto']; ?></label>
									<div>
									<input type="number" step="0.01" value="<?php echo $form['in_sconto']; ?>" name="in_sconto" />
									</div>
						</div>
					</div><!-- chiude form-group  -->
				</div><!-- chiude form-row  -->
				<div class="col-xs-12">
					<div class="col-sm-12 col-md-6">
						<div class="form-group col-sm-12 col-md-6">
									<label for="vat_constrain" class="col-form-label"><?php echo $script_transl['vat_constrain']; ?></label>
									<div>
									<?php $gForm->selectFromDB('aliiva', 'in_codvat', 'codice', $form['in_codvat'], 'codice', true, '-', 'descri', '', 'col-sm-6'); ?>
									</div>
						</div>
						<div class="form-group col-sm-12 col-md-6">
									<label for="codric" class="col-form-label"><?php echo $script_transl['codric']; ?></label>
									<div>
									<?php
									$select_codric = new selectconven("in_codric");
									$select_codric->addSelected($form['in_codric']);
									$select_codric->output(substr($form['in_codric'], 0, 1), 'col-lg-12');
									?>
									</div>
						</div>
					</div><!-- chiude form-group  -->
					<div class="col-sm-12 col-md-6">
						<div class="form-group col-sm-12 col-md-6">
									<label for="in_ritenuta" class="col-form-label"><?php echo $script_transl['ritenuta']; ?></label>
									<div>
									<input type="number" step="any" value="<?php echo $form['in_ritenuta']; ?>" name="in_ritenuta" />
									</div>
						</div>
						<div class="form-group col-sm-12 col-md-6">
									<div class="col-xs-12 col-sm-6"><small>Magazzino</small><br/>
<?php
$magazz->selectIdWarehouse('in_id_warehouse',$form["in_id_warehouse"],false,'col-xs-12');
?>
									</div>
									<div class="col-xs-12 col-sm-6 text-right">
									<button type="submit" tabindex="7" class="btn <?php echo $class_conf_row; ?>" name="in_submit"><?php echo $descributton; ?><i class="glyphicon glyphicon-ok"></i>
									</button>
									</div>
						</div>
					</div><!-- chiude form-group  -->
				</div><!-- chiude form-row  -->
			</div><!-- chiude panel-body  -->
		  </div><!-- chiude container  -->
		</div><!-- chiude panel  -->

		<?php
		}
if (count($form['rows']) > 0) {
	?>
  <div align="center"><b>Piede</b></div>
	<div class="panel panel-default">
	<div class="container-fluid">
	<?php
	$calc->add_value_to_VAT_castle($castle);
	foreach ($calc->castle as $k => $v) {
	?>
		<div class="form-row">
			<div class="col-sm-12 col-md-6">
				<div class="form-group col-sm-12 col-md-6">
					<label for="impcast" class="col-form-label"><?php echo $script_transl['taxable']; ?></label>
					<div class="bg-success text-center"><?php echo gaz_format_number($v['impcast']); ?></div>
				</div>
				<div class="form-group col-sm-12 col-md-6">
					<label for="descriz" class="col-form-label"> &nbsp; </label>
					<div class="bg-info text-center"><?php echo $v['descriz']; ?></div>
				</div>
			</div>
			<div class="col-sm-12 col-md-6">
				<div class="form-group col-sm-12 col-md-6">
					<label for="ivacast" class="col-form-label"><?php echo $script_transl['tax']; ?></label>
					<div class="bg-success text-center"><?php echo gaz_format_number($v['ivacast']) ; ?></div>
				</div>
				<div class="form-group col-sm-12 col-md-6">
					<label for="ivacast" class="col-form-label">Tot</label>
					<div class="bg-success text-center"><?php echo gaz_format_number($v['ivacast']+$v['impcast']) ; ?></div>
				</div>
			</div>
		</div> <!-- chiude row  -->

	<?php
	}
	?>
	<div class="form-row">
		<div class="col-sm-12 col-md-6">
			<div class="form-group col-sm-12 col-md-6">
			</div>
			<div class="form-group col-sm-12 col-md-6">
			</div>
		</div>
		<div class="col-sm-12 col-md-6">
			<div class="form-group col-sm-12 col-md-6">
			</div>
			<div class="form-group col-sm-12 col-md-6">
				<label for="total" class="col-form-label"><?php echo $script_transl['total']; ?></label>
				<div class="text-center bg-warning"><b><?php echo gaz_format_number($calc->total_imp + $calc->total_vat) ; ?></b></div>
			</div>
		</div>
	</div><!-- chiude form-row  -->
	<?php
	if ($rit > 0) { // ho la ritenuta d'acconto
	?>
		<div class="form-row">
			<div class="col-sm-12 col-md-6">
				<div class="form-group col-sm-12 col-md-6">
				</div>
				<div class="form-group col-sm-12 col-md-6">
				</div>
			</div>
			<div class="col-sm-12 col-md-6">
				<div class="form-group col-sm-12 col-md-6">
				</div>
				<div class="form-group col-md-6  col-lg-3 nopadding">
					<label for="ritenuta" class="col-form-label"><?php echo $script_transl['ritenuta']; ?></label>
					<div class="text-center"><?php echo gaz_format_number($rit); ?></div>
				</div>
			</div>
		</div><!-- chiude form-row  -->
		<div class="form-row">
			<div class="col-sm-12 col-md-6">
				<div class="form-group col-sm-12 col-md-6">
				</div>
				<div class="form-group col-sm-12 col-md-6">
				</div>
			</div>
			<div class="col-sm-12 col-md-6">
				<div class="form-group col-sm-12 col-md-6">
				</div>
				<div class="form-group col-sm-12 col-md-6">
					<label for="netpay" class="col-form-label"><?php echo $script_transl['netpay']; ?></label>
					<div class="bg-warning text-center"><b><?php echo gaz_format_number($calc->total_imp + $calc->total_vat - $rit); ?></b></div>
				</div>
			</div>
		</div><!-- chiude form-row  -->
	<?php
	}
	?>
	</div><!-- chiude container-fluid  -->
	</div><!-- chiude panel  -->

	<?php
	if ($form['tipdoc'] == 'DDR' || $form['tipdoc'] == 'DDL' ) { // per i documenti emessi stampo il form per i dati relativi al trasporto
	?>
	<div class="panel panel-default">
		<div class="container-fluid">
			 <div class="form-row">
				<div class="col-sm-12 col-md-6">
					<div class="form-group col-sm-12 col-md-6">
						<label for="net_weight" class="col-form-label"><?php echo $script_transl['net']; ?></label>
						<input type="text" class="form-control col-lg-2" id="net_weight" name="net_weight" value="<?php echo $form['net_weight']; ?>">
					</div>
					<div class="form-group col-sm-12 col-md-6">
						<label for="units" class="col-form-label"><?php echo $script_transl['units']; ?></label>
						<input type="text" class="form-control col-lg-2" id="units" name="units" value="<?php echo $form['units']; ?>">
					</div>
				</div>
				<div class="col-sm-12 col-md-6">
					<div class="form-group col-sm-12 col-md-6">
						<label for="volume" class="col-form-label"><?php echo $script_transl['volume']; ?></label>
						<input type="text" class="form-control col-lg-2" id="volume" name="volume" value="<?php echo $form['volume']; ?>">
					</div>
					<div class="form-group col-sm-12 col-md-6">
						<label for="imball" class="col-form-label"><?php echo $script_transl['imball']; ?></label>
						<div><input type="text" name="imball" value="<?php echo $form['imball']; ?>" maxlength="50"/>
						<?php
						$select_spediz = new SelectValue("imballo");
						$select_spediz->output('imball', 'imball');
						?>
						</div>
					</div>
				</div>
				<div class="col-sm-12 col-md-6">
					<div class="form-group col-sm-12 col-md-6">
						<label for="spediz" class="col-form-label"><?php echo $script_transl['spediz']; ?></label>
						<div><input type="text" name="spediz" value="<?php echo $form['spediz']; ?>" maxlength="50"/>
						<?php
						$select_spediz = new SelectValue("spedizione");
						$select_spediz->output('spediz', 'spediz');
						?>
						</div>
					</div>
					<div class="form-group col-sm-12 col-md-6">
						<label for="portos" class="col-form-label"><?php echo $script_transl['portos']; ?></label>
						<div><input type="text" name="portos" value="<?php echo $form['portos']; ?>" maxlength="50"/>
						<?php
						$select_spediz = new SelectValue("porto");
						$select_spediz->output('portos', 'portos');
						?>
						</div>
					</div>
				</div>
				<div class="col-sm-12 col-md-6">
					<div class="form-group col-sm-12 col-md-6">
						<label for="vettor" class="col-form-label"><?php echo $script_transl['vettor']; ?></label>
						<div>
						<?php
						$select_vettor = new selectvettor("vettor");
						$select_vettor->addSelected($form["vettor"]);
						$select_vettor->output();
						?>
						</div>
					</div>
					<div class="form-group col-sm-12 col-md-6">
						<label for="vettor" class="col-form-label"><?php echo $script_transl['initra']; ?></label>
						<div>
					<?php
					echo "		<input class=\"FacetText\" type=\"text\" name=\"giotra\" value=\"" . $form['giotra'] . "\" maxlength=2 size=2 >
							<input class=\"FacetText\" type=\"text\" name=\"mestra\" value=\"" . $form['mestra'] . "\" maxlength=2 size=2 >
							<input class=\"FacetText\" type=\"text\" name=\"anntra\" value=\"" . $form['anntra'] . "\" maxlength=4 size=4 >
							<a href=\"#\" onClick=\"cal.showCalendar('anchor','" . $form['mestra'] . "/" . $form['giotra'] . "/" . $form['anntra'] . "'); return false;\" title=\" cambia la data! \" name=\"anchor\" id=\"anchor\" class=\"btn btn-default btn-xs\">\n";
		//echo "<img border=\"0\" src=\"../../library/images/cal.png\"></A>$script_transl[31]";
		echo '					<i class="glyphicon glyphicon-calendar"></i>
							</a> '.$script_transl['iniore'];
		// select dell'ora
		echo "\t <select name=\"oratra\" class=\"FacetText\" >\n";
		for ($counter = 0; $counter <= 23; $counter++) {
			$selected = "";
			if ($counter == $form['oratra'])
				$selected = ' selected=""';
			echo "\t\t <option value=\"" . sprintf('%02d', $counter) . "\" $selected >" . sprintf('%02d', $counter) . "</option>\n";
		}
		echo "\t </select>\n ";
		// select dell'ora
		echo "\t <select name=\"mintra\" class=\"FacetText\" >\n";
		for ($counter = 0; $counter <= 59; $counter++) {
			$selected = "";
			if ($counter == $form['mintra'])
				$selected = ' selected=""';
			echo "\t\t <option value=\"" . sprintf('%02d', $counter) . "\" $selected >" . sprintf('%02d', $counter) . "</option>\n";
		}
		echo "				\t</select>";

					?>
						</div>
					</div>
				</div>
			</div><!-- chiude form-row  -->
		</div><!-- chiude container-fluid  -->
		</div><!-- chiude panel  -->
		<?php
	} else { // non servono i dati per il trasporto
		?>
		<input type="hidden" value="<?php echo $form['spediz']; ?>" name="spediz">
		<input type="hidden" value="<?php echo $form['portos']; ?>" name="portos">
		<input type="hidden" value="<?php echo $form['imball']; ?>" name="imball">
		<input type="hidden" value="<?php echo $form['vettor']; ?>" name="vettor">
		<?php
	}
	?>
	<div class="form-group"><div class="col-lg-6"></div><div class="col-lg-3"><input class="btn btn-block btn-warning" id="preventDuplicate" onClick="chkSubmit();" type="submit" name="ins" value="<?php
	if ($toDo == 'insert'){ // inserimento
		echo $script_transl['insert'].' '.$title;
	} else { // update
		echo $script_transl['update'].' '.$title;
	}
	?>" /></div></div>
	<?php
} else { // non ho righi  sul corpo
	?>
    <input type="hidden" value="<?php echo $form['spediz']; ?>" name="spediz">
    <input type="hidden" value="<?php echo $form['portos']; ?>" name="portos">
    <input type="hidden" value="<?php echo $form['imball']; ?>" name="imball">
    <input type="hidden" value="<?php echo $form['vettor']; ?>" name="vettor">
<?php
}
?>
</form>

<!-- ENRICO FEDELE - INIZIO FINESTRA MODALE -->
<div id="edit-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header active">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel"><?php echo $script_transl['add_article']; ?></h4>
            </div>
            <div class="modal-body edit-content small"></div>
            <!--<div class="modal-footer"></div>-->
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        //twitter bootstrap script
        $("#addmodal").click(function () {
            $.ajax({
                type: "POST",
                url: "../../modules/magazz/admin_artico.php",
                data: 'mode=modal',
                success: function (msg) {
                    $("#edit-modal .modal-sm").css('width', '100%');
                    $("#edit-modal .modal-body").html(msg);
                },
                error: function () {
                    alert("failure");
                }
            });
        });
    });
</script>
<!-- ENRICO FEDELE - FINE FINESTRA MODALE -->
<?php
if (isset($_POST['ins']) && count($msg['err']) == 0 && $pdf_to_modal!==0) {// stampa pdf in popup iframe
  ?>
  <script>
    printPdf('invsta_docacq.php');
  </script>
  <?php
}
require("../../library/include/footer.php");
?>
