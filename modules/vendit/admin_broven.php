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
$admin_aziend = checkAdmin();
$pdf_to_modal = gaz_dbi_get_row($gTables['company_config'], 'var', 'pdf_reports_send_to_modal')['val'];
$scroll_input_row = gaz_dbi_get_row($gTables['company_config'], 'var', 'autoscroll_to_last_row')['val'];
$after_newdoc_back_to_doclist=gaz_dbi_get_row($gTables['company_config'], 'var', 'after_newdoc_back_to_doclist')['val'];
$msgtoast = "";
$msg = "";
$show_artico_composit = gaz_dbi_get_row($gTables['company_config'], 'var', 'show_artico_composit');
$tipo_composti = gaz_dbi_get_row($gTables['company_config'], 'var', 'tipo_composti');

function getDayNameFromDayNumber($day_number) {
  global $gazTimeFormatter;
  $gazTimeFormatter->setPattern('eeee');
  return ucfirst(utf8_encode($gazTimeFormatter->format(new DateTime('@'.mktime(12,0,0,3,19+$day_number, 2017)))));
}

$upd_mm = new magazzForm;
$docOperat = $upd_mm->getOperators();


// INIZIO CONTROLLER
if (!isset($_POST['ritorno'])) {
    if (isset($after_newdoc_back_to_doclist)){
      if ($after_newdoc_back_to_doclist==0){
        $td=(isset($_GET['tipdoc']) && $_GET['tipdoc']=='VPR')?'VPR':'VOR';
        $form['ritorno']="admin_broven.php?Insert&tipdoc=".$td;
      }else{
        $td=(isset($_GET['tipdoc']) && $_GET['tipdoc']=='VPR')?'?tipdoc=VPR':'';
        $form['ritorno']="report_broven.php".$td;
      }
    } else{
      $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    }
} else {
    $form['ritorno'] = $_POST['ritorno'];
}

if ((isset($_GET['Update']) and ! isset($_GET['id_tes'])) and ! isset($_GET['tipdoc'])) {
    header("Location: " . $form['ritorno']);
    exit;
}


if (isset($_POST['newdestin'])) {
    $_POST['id_des'] = 0;
    $_POST['destin'] = "";
}

if ((isset($_POST['Update'])) or ( isset($_GET['Update']))) {
    $toDo = 'update';
	$class_btn_confirm='btn-warning';
} else {
    $toDo = 'insert';
	$class_btn_confirm='btn-warning';
}

if ((isset($_POST['Insert'])) or ( isset($_POST['Update']))) {   //se non e' il primo accesso
    //qui si dovrebbe fare un parsing di quanto arriva dal browser...
    $form['id_tes'] = $_POST['id_tes'];
    $anagrafica = new Anagrafica();
    $cliente = $anagrafica->getPartner($_POST['clfoco']);
    $form['hidden_req'] = $_POST['hidden_req'];
    // ...e della testata
    foreach ($_POST['search'] as $k => $v) {
        $form['search'][$k] = $v;
    }
    $form['print_total'] = intval($_POST['print_total']);
    $form['delivery_time'] = intval($_POST['delivery_time']);
    $form['day_of_validity'] = intval($_POST['day_of_validity']);
    $form['cosear'] = $_POST['cosear'];
    $form['seziva'] = $_POST['seziva'];
    $form['indspe'] = $_POST['indspe'];
    $form['tipdoc'] = $_POST['tipdoc'];
    $form['gioemi'] = $_POST['gioemi'];
    $form['mesemi'] = $_POST['mesemi'];
    $form['annemi'] = $_POST['annemi'];
    $form['weekday_repeat'] = $_POST['weekday_repeat'];
    $form['giotra'] = $_POST['giotra'];
    $form['mestra'] = $_POST['mestra'];
    $form['anntra'] = $_POST['anntra'];
    $form['oratra'] = $_POST['oratra'];
    $form['mintra'] = $_POST['mintra'];
    $form['protoc'] = $_POST['protoc'];
    $form['numdoc'] = $_POST['numdoc'];
    $form['numfat'] = $_POST['numfat'];
    $form['datfat'] = $_POST['datfat'];
    $form['clfoco'] = substr($_POST['clfoco'], 0, 13);
    //tutti i controlli su  tipo di pagamento e rate
    $form['speban'] = $_POST['speban'];
    $form['numrat'] = $_POST['numrat'];
    $form['expense_vat'] = intval($_POST['expense_vat']);
    $form['virtual_taxstamp'] = intval($_POST['virtual_taxstamp']);
    $form['taxstamp'] = floatval($_POST['taxstamp']);
    $form['stamp'] = floatval($_POST['stamp']);
    $form['round_stamp'] = intval($_POST['round_stamp']);
    $form['pagame'] = $_POST['pagame'];
    $form['change_pag'] = $_POST['change_pag'];
    $form['shortdescri'] = substr($_POST['shortdescri'],0,20);
    if ($form['change_pag'] != $form['pagame']) {  //se e' stato cambiato il pagamento
        $new_pag = gaz_dbi_get_row($gTables['pagame'], "codice", $form['pagame']);
        if ($toDo == 'update') {  //se � una modifica mi baso sulle vecchie spese
            $old_header = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", $form['id_tes']);
            if ($cliente['speban'] == "S" && ($new_pag['tippag'] == 'T' || $new_pag['tippag'] == 'B')) {
                if ($old_header['speban'] > 0) {
                    $form['speban'] = $old_header['speban'];
                } else {
                    $form['speban'] = $admin_aziend['sperib'];
                }
            } else {
                $form['speban'] = 0.00;
            }
        } else { //altrimenti, se previste, mi avvalgo delle nuove dell'azienda
            if ($new_pag && $cliente['speban'] == "S" && ($new_pag['tippag'] == 'B' || $new_pag['tippag'] == 'T')) {
                $form['speban'] = $admin_aziend['sperib'];
            } else {
                $form['speban'] = 0;
            }
        }
        if ($new_pag && $new_pag['tippag'] == 'T' && $form['stamp'] == 0) {  //se il pagamento prevede il bollo
            $form['stamp'] = $admin_aziend['perbol'];
            $form['round_stamp'] = $admin_aziend['round_bol'];
        } elseif ($new_pag && $new_pag['tippag'] != 'T') {
            $form['stamp'] = 0;
            $form['round_stamp'] = 0;
        }
        $form['numrat'] =($new_pag)?$new_pag['numrat']:1;
        $form['pagame'] = intval($_POST['pagame']);
        $form['change_pag'] = intval($_POST['pagame']);
    }
    $form['banapp'] = $_POST['banapp'];
    $form['vettor'] = $_POST['vettor'];
    $form['id_agente'] = intval($_POST['id_agente']);
    $form['net_weight'] = floatval($_POST['net_weight']);
    $form['gross_weight'] = floatval($_POST['gross_weight']);
    $form['units'] = intval($_POST['units']);
    $form['volume'] = floatval($_POST['volume']);
    $form['listin'] = $_POST['listin'];
    $form['spediz'] = $_POST['spediz'];
    $form['portos'] = $_POST['portos'];
    $form['imball'] = $_POST['imball'];
    $form['destin'] = $_POST['destin'];
    $form['id_des'] = $_POST['id_des'];
    $form['id_des_same_company'] = intval($_POST['id_des_same_company']);
    $form['traspo'] = $_POST['traspo'];
    $form['spevar'] = $_POST['spevar'];
    $form['cauven'] = $_POST['cauven'];
    $form['caucon'] = $_POST['caucon'];
    $form['caumag'] = $_POST['caumag'];
    $form['id_agente'] = $_POST['id_agente'];
    $form['sconto'] = $_POST['sconto'];
    // inizio rigo di input
    $form['in_descri'] = $_POST['in_descri'];
    $form['in_tiprig'] = $_POST['in_tiprig'];
    $form['in_id_doc'] = $_POST['in_id_doc'];
    $form['in_codart'] = $_POST['in_codart'];
    $form['in_pervat'] = $_POST['in_pervat'];
    $form['in_tipiva'] = $_POST['in_tipiva'];
    $form['in_ritenuta'] = $_POST['in_ritenuta'];
    $form['in_unimis'] = $_POST['in_unimis'];
    $form['in_prelis'] = $_POST['in_prelis'];
    $form['in_sconto'] = $_POST['in_sconto'];
    $form['in_quanti'] = gaz_format_quantity($_POST['in_quanti'], 0, $admin_aziend['decimal_quantity']);
    $form['in_codvat'] = $_POST['in_codvat'];
    $form['in_codric'] = (isset($_POST['in_codric']))?$_POST['in_codric']:0;
    $form['in_provvigione'] = $_POST['in_provvigione'];
    $form['in_id_mag'] = $_POST['in_id_mag'];
    $form['in_id_rig'] = $_POST['in_id_rig'];
    $form['in_nrow'] = $_POST['in_nrow'];
    $form['in_nrow_linked'] = $_POST['in_nrow_linked'];
    $form['in_annota'] = $_POST['in_annota'];
    $form['in_scorta'] = $_POST['in_scorta'];
    $form['in_quamag'] = $_POST['in_quamag'];
    $form['in_pesosp'] = $_POST['in_pesosp'];
    $form['in_extdoc'] = $_POST['in_extdoc'];
    $form['in_status'] = $_POST['in_status'];
    // fine rigo input

    $ultimoprezzo=''; //info sugli ultimi prezzi
    if ($form['in_codart']<>$form['cosear']) { // ho cambiato articolo, cerco le 3 ultime vendite
      $what = $gTables['tesdoc'] . ".datfat, " .
			$gTables['tesdoc'] . ".numfat, " .
			$gTables['rigdoc'] . ".codart, " .
			$gTables['rigdoc'] . ".quanti, " .
			$gTables['rigdoc'] . ".prelis, " .
			$gTables['rigdoc'] . ".sconto, " .
			$gTables['rigdoc'] . ".provvigione";
      $table = $gTables['rigdoc'] . " LEFT JOIN " . $gTables['tesdoc'] . " ON "
   		.$gTables['tesdoc'] . ".id_tes = " . $gTables['rigdoc'] . ".id_tes";
      $where = $gTables['tesdoc'].".clfoco = '".$form['clfoco']."' AND ".$gTables['tesdoc'].".tipdoc LIKE 'FA_' AND ".$gTables['rigdoc'].".tiprig = 0 AND ".$gTables['rigdoc'].".codart = '".$form['cosear']."'";
      $result = gaz_dbi_dyn_query($what, $table, $where, "datfat DESC",0,3);
      while ($prezzi = gaz_dbi_fetch_array($result)) {
        $ultimoprezzo.="<br />Fattura n. ".$prezzi['numfat']." del ".gaz_format_date($prezzi['datfat'])." ____ quantit&agrave; ".gaz_format_quantity($prezzi['quanti'], 0, $admin_aziend['decimal_quantity'])." ____ prezzo ".gaz_format_number($prezzi['prelis'])." ____ sconto ".gaz_format_number($prezzi['sconto'])."% ____ provvigione ".gaz_format_number($prezzi['provvigione'])."%";
      }
    }

    $form['rows']=[];
    $next_row = 0;
    if (isset($_POST['rows'])) {
        foreach ($_POST['rows'] as $next_row => $v) {
            if (isset($_POST["row_$next_row"])) { //se ho un rigo testo
                $form["row_$next_row"] = $_POST["row_$next_row"];
            }
            $form['rows'][$next_row]['descri'] = substr($v['descri'], 0, 100);
            $form['rows'][$next_row]['tiprig'] = intval($v['tiprig']);
            $form['rows'][$next_row]['id_doc'] = intval($v['id_doc']);
            $form['rows'][$next_row]['codart'] = substr($v['codart'], 0, 32);
            $form['rows'][$next_row]['good_or_service'] = intval($v['good_or_service']);
            $form['rows'][$next_row]['pervat'] = preg_replace("/\,/", '.', $v['pervat']);
            $form['rows'][$next_row]['tipiva'] = strtoupper(substr($v['tipiva'], 0, 1));
            $form['rows'][$next_row]['ritenuta'] = preg_replace("/\,/", '.', $v['ritenuta']);
            $form['rows'][$next_row]['unimis'] = substr($v['unimis'], 0, 3);
            $form['rows'][$next_row]['prelis'] = number_format(floatval(preg_replace("/\,/", '.', $v['prelis'])), $admin_aziend['decimal_price'], '.', '');
            $form['rows'][$next_row]['sconto'] = floatval(preg_replace("/\,/", '.', $v['sconto']));
            $form['rows'][$next_row]['quanti'] = gaz_format_quantity($v['quanti'], 0, $admin_aziend['decimal_quantity']);
            $form['rows'][$next_row]['codvat'] = intval($v['codvat']);
            $form['rows'][$next_row]['codric'] = intval($v['codric']);
            if (isset($v['provvigione'])) {
                $form['rows'][$next_row]['provvigione'] = intval($v['provvigione']);
            }
            $form['rows'][$next_row]['id_mag'] = intval($v['id_mag']);
            $form['rows'][$next_row]['id_rig'] = intval($v['id_rig']);
            $form['rows'][$next_row]['nrow'] = intval($v['nrow']);
            $form['rows'][$next_row]['nrow_linked'] = intval($v['nrow_linked']);
            $form['rows'][$next_row]['annota'] = substr($v['annota'], 0, 50);
            $form['rows'][$next_row]['scorta'] = floatval($v['scorta']);
            $form['rows'][$next_row]['quamag'] = floatval($v['quamag']);
            $form['rows'][$next_row]['pesosp'] = floatval($v['pesosp']);
            $form['rows'][$next_row]['extdoc'] = filter_var($_POST['rows'][$next_row]['extdoc'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);//die(print_r($_POST['rows'],TRUE));
            if (!empty($_FILES['docfile_' . $next_row]['name'])) {//die(print_r($_FILES,TRUE));
                $move = false;
                $mt = substr($_FILES['docfile_' . $next_row]['name'], -3);
                $prefix = $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $next_row;
                if (($mt == 'png' || $mt == 'peg' || $mt == 'jpg' || $mt == 'pdf') && $_FILES['docfile_' . $next_row]['size'] > 1000) { //se c'e' un nuovo documento nel buffer
                    foreach (glob( DATA_DIR . 'files/tmp/' . $prefix . '_*.*') as $fn) {// prima cancello eventuali precedenti file temporanei
                        unlink($fn);
                    }
                    $move = move_uploaded_file($_FILES['docfile_' . $next_row]['tmp_name'], DATA_DIR . 'files/tmp/' . $prefix . '_' . $_FILES['docfile_' . $next_row]['name']);
                    $form['rows'][$next_row]['extdoc'] = $_FILES['docfile_' . $next_row]['name'];
                }
                if (!$move) {
                    $msg .= '56+';

                }
            }
            $form['rows'][$next_row]['status'] = substr($v['status'], 0, 10);
            if (isset($_POST['upd_row'])) {
                $k_row = key($_POST['upd_row']);
                if ($k_row == $next_row) {
                    // sottrazione ai totali peso,pezzi,volume
                    $artico = gaz_dbi_get_row($gTables['artico'], "codice", $form['rows'][$k_row]['codart']);
                    if (isset($artico)){
                      $form['net_weight'] -= $form['rows'][$k_row]['quanti'] * $artico['peso_specifico'];
                      $form['gross_weight'] -= $form['rows'][$k_row]['quanti'] * $artico['peso_specifico'];
                      if ($artico['pack_units'] > 0) {
                          $form['units'] -= intval(round($form['rows'][$k_row]['quanti'] / $artico['pack_units']));
                      }
                      $form['volume'] -= $form['rows'][$k_row]['quanti'] * $artico['volume_specifico'];
                    }else{
                      $form['net_weight']=0;
                      $form['gross_weight']=0;
                    }
                    // fine sottrazione peso,pezzi,volume
                    $form['in_descri'] = $form['rows'][$k_row]['descri'];
                    $form['in_tiprig'] = $form['rows'][$k_row]['tiprig'];
                    $form['in_codart'] = $form['rows'][$k_row]['codart'];
                    $form['in_good_or_service'] = $form['rows'][$k_row]['good_or_service'];
                    $form['in_pervat'] = $form['rows'][$k_row]['pervat'];
                    $form['in_tipiva'] = $form['rows'][$k_row]['tipiva'];
                    $form['in_ritenuta'] = $form['rows'][$k_row]['ritenuta'];
                    $form['in_unimis'] = $form['rows'][$k_row]['unimis'];
                    $form['in_prelis'] = $form['rows'][$k_row]['prelis'];
                    $form['in_sconto'] = $form['rows'][$k_row]['sconto'];
                    $form['in_quanti'] = $form['rows'][$k_row]['quanti'];
                    $form['in_codric'] = $form['rows'][$k_row]['codric'];
                    $form['in_provvigione'] = (isset($form['rows'][$k_row]['provvigione']))?$form['rows'][$k_row]['provvigione']:0;
                    $form['in_id_mag'] = $form['rows'][$k_row]['id_mag'];
                    $form['in_id_rig'] = $form['rows'][$k_row]['id_rig'];
                    $form['in_nrow'] = $form['rows'][$k_row]['nrow'];
                    $form['in_nrow_linked'] = $form['rows'][$k_row]['nrow_linked'];
                    $form['in_annota'] = $form['rows'][$k_row]['annota'];
                    $form['in_scorta'] = $form['rows'][$k_row]['scorta'];
                    $form['in_quamag'] = $form['rows'][$k_row]['quamag'];
                    $form['in_pesosp'] = $form['rows'][$k_row]['pesosp'];
                    $form['in_extdoc'] = $form['rows'][$k_row]['extdoc'];
                    $form['in_status'] = "UPDROW" . $k_row;
                    $form['cosear'] = $form['rows'][$k_row]['codart'];
                    array_splice($form['rows'], $k_row, 1);
                    $next_row--;
                }
            } elseif ($_POST['hidden_req'] == 'ROW') {
                if (!empty($form['hidden_req'])) { // al primo ciclo azzero ma ripristino il lordo
                    $form['gross_weight'] -= $form['net_weight'];
                    $form['net_weight'] = 0;
                    $form['units'] = 0;
                    $form['volume'] = 0;
                    $form['hidden_req'] = '';
                }
                $artico = gaz_dbi_get_row($gTables['artico'], "codice", $form['rows'][$next_row]['codart']);
                if(isset($artico)){
                  $form['net_weight'] += $form['rows'][$next_row]['quanti'] * $artico['peso_specifico'];
                  $form['gross_weight'] += $form['rows'][$next_row]['quanti'] * $artico['peso_specifico'];
                  if ($artico['pack_units'] > 0) {
                      $form['units'] += intval(round($form['rows'][$next_row]['quanti'] / $artico['pack_units']));
                  }
                }
                $form['volume'] += (isset($artico))?$form['rows'][$next_row]['quanti'] * $artico['volume_specifico']:0;
            }
            $next_row++;
        }
    }

// INIZIO MODEL
	// Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {
      $sezione = $form['seziva'];
      $datemi = $form['annemi'] . "-" . $form['mesemi'] . "-" . $form['gioemi'];
      $utsemi = mktime(0, 0, 0, $form['mesemi'], $form['gioemi'], $form['annemi']);
      $initra = $form['anntra'] . "-" . $form['mestra'] . "-" . $form['giotra'];
      $utstra = mktime(0, 0, 0, $form['mestra'], $form['giotra'], $form['anntra']);
      if (!checkdate($form['mestra'], $form['giotra'], $form['anntra']))
        $msg .= "37+";
      if (!isset($_POST['rows'])) {
        $msg .= "39+";
      }
      $ctrldatemi = new DateTime($datemi);
      $business_date_cessation = gaz_dbi_get_row($gTables['company_config'], 'var', 'business_date_cessation')['val'];
      if (strlen($business_date_cessation)==10){ // in configurazione avanzata azienda
        $cessation = new DateTime(gaz_format_date($business_date_cessation,true));
        if ($ctrldatemi > $cessation){ // in configurazione azienda ho settato l'ultimo giorno di operatività dell'azienda
         $msg .= "60+";
        }
      }

        // --- inizio controllo coerenza date-numerazione
        if ($toDo == 'update') {  // controlli in caso di modifica
            $rs_query = gaz_dbi_dyn_query("numdoc", $gTables['tesbro'], "YEAR(datemi) = " . $form['annemi'] . " and datemi < '$datemi' and tipdoc = '" . $form['tipdoc'] . "' and seziva = $sezione", "datemi DESC, numdoc DESC", 0, 1);
            $result = gaz_dbi_fetch_array($rs_query); //giorni precedenti
            if ($result and ( $form['numdoc'] < $result['numdoc'])) {
                $msg .= "42+";
            }
        } else {    //controlli in caso di inserimento
            $rs_ultimo_tipo = gaz_dbi_dyn_query("*", $gTables['tesbro'], "YEAR(datemi) = " . $form['annemi'] . " and tipdoc = '" . $form['tipdoc'] . "' and seziva = $sezione", "numdoc desc, datemi desc", 0, 1);
            $ultimo_tipo = gaz_dbi_fetch_array($rs_ultimo_tipo);
            if ($ultimo_tipo){
              $utsUltimoDocumento = mktime(0, 0, 0, substr($ultimo_tipo['datemi'], 5, 2), substr($ultimo_tipo['datemi'], 8, 2), substr($ultimo_tipo['datemi'], 0, 4));
              if ($ultimo_tipo and ( $utsUltimoDocumento > $utsemi)) {
                $msg .= "45+";
              }
            }
        }
        // --- fine controllo coerenza date-numeri
        if (!checkdate($form['mesemi'], $form['gioemi'], $form['annemi']))
            $msg .= "46+";
        if (empty($form['clfoco']))
            $msg .= "47+";
        if (empty($form['pagame']))
            $msg .= "48+";
        //controllo che i rows non abbiano descrizioni  e unita' di misura vuote in presenza di quantita diverse da 0
        foreach ($form['rows'] as $i => $v) {
            if ($v['descri'] == '' && ($v['quanti'] > 0 || $v['quanti'] < 0)) {
                $msgrigo = $i + 1;
                $msg .= "49+";
            }
            if ($v['unimis'] == '' && ($v['quanti'] > 0 || $v['quanti'] < 0)) {
                $msgrigo = $i + 1;
                $msg .= "50+";
            }
        }
        if ($msg == "") {// nessun errore

             $initra .= " " . $form['oratra'] . ":" . $form['mintra'] . ":00";
            if (preg_match("/^id_([0-9]+)$/", $form['clfoco'], $match)) {
                $new_clfoco = $anagrafica->getPartnerData($match[1], 1);
                $form['clfoco'] = $anagrafica->anagra_to_clfoco($new_clfoco, $admin_aziend['mascli'],$form['pagame']);
            }
            if ($toDo == 'update') { // e' una modifica
                // aggiorno il custom_field
                $gaz_custom_data = [];
                $gaz_custom_field = gaz_dbi_get_single_value( $gTables['tesbro'], 'custom_field', 'id_tes = '.$form['id_tes'] );
                if ( isset( $gaz_custom_field ) && $gaz_custom_field!="" ) {
                    $gaz_custom_data = json_decode($gaz_custom_field,true);
                }
                $gaz_custom_data['vendit']['shortdescri'] = $form['shortdescri'];
                $form['custom_field'] = json_encode($gaz_custom_data);

                // carico i vecchi righi presenti nel DB
                $oldresult = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = " . $form['id_tes'], "id_rig asc");
                while($old_rows[] = mysqli_fetch_assoc($oldresult));
                array_pop($old_rows);  // pop the last row off, which is an empty row
                $i = 0;$syncarticols=array();

                foreach ($form['rows'] as $row) {// ciclo i righi del form
                  array_push($syncarticols,$row['codart']);// Antonio Germani - aggiungo il codice articolo all'array per la sincronizzazione e-commerce
                  if (intval($row['id_rig'])>0) { //se è un rigo vecchio
                      // trovo quale old id ha questo rigo
                      foreach ($old_rows as $key => $val) {
                        if ($val['id_rig']==$row['id_rig']){
                          $oldid=$key;break;
                        }
                      }
                      // cancello il rigo per poi riscriverlo: in questa maniera preservo la sequenza dei righi operata nel form
                      gaz_dbi_del_row($gTables['rigbro'], "id_rig", $old_rows[$oldid]['id_rig']);
                      $row['id_tes'] = $form['id_tes'];
                      $last_rigbro_id = rigbroInsert($row);

                      if (substr($row['extdoc'],0,10) == "rigbrodoc_"){// c'è un file allegato che non è stato cambiato
                        $fn = pathinfo($row['extdoc']);
                        // gli modifico il riferimento rigo al suo nome
                        rename(DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/rigbrodoc_' . $old_rows[$oldid]['id_rig'] . '.' . $fn['extension'] , DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/rigbrodoc_' . $last_rigbro_id . '.' . $fn['extension']);

                      }elseif(!empty($row['extdoc'])){// c'è un nuovo file da inserire
                        // elimino il vecchio file sostituito
                        $urlarr=(glob(DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/rigbrodoc_' . $old_rows[$oldid]['id_rig'].".*"));
                        if (isset($urlarr)){
                          $fn = pathinfo($urlarr[0]);
                          unlink(DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/rigbrodoc_' . $old_rows[$oldid]['id_rig'] . '.' . $fn['extension']);
                        }
                        $tmp_file = DATA_DIR . 'files/tmp/' . $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $i . '_' . $row['extdoc'];
                        // sposto il file temporaneo nella cartella definitiva assegnandogli nome e riferimento rigo
                        $fn = pathinfo($row['extdoc']);
                        rename($tmp_file, DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/rigbrodoc_' . $last_rigbro_id . '.' . $fn['extension']);

                      }

                      if (intval($old_rows[$oldid]['id_body_text'])>0){// se il vecchio rigo aveva un bodytext
                        gaz_dbi_del_row($gTables['body_text'], "id_body", $old_rows[$oldid]['id_body_text']); // cancello il vecchio body text
                      }
                      if (isset($form["row_$i"])){// se questo rigo del form ha un bodytext lo inserisco
                        bodytextInsert(array('table_name_ref' => 'rigbro', 'id_ref' => $last_rigbro_id, 'body_text' => $form["row_$i"], 'lang_id' => $admin_aziend['id_language']));
                        gaz_dbi_put_row($gTables['rigbro'], 'id_rig', $last_rigbro_id, 'id_body_text', gaz_dbi_last_id());
                      }

                      unset($old_rows[$oldid]);// tolgo questo rigo vecchio dal relativo array in quanto già elaborato

                  } else { //altrimenti lo inserisco nuovo
                    array_push($syncarticols,$row['codart']);// Antonio Germani - aggiungo il codice articolo all'array per la sincronizzazione e-commerce
                    $row['id_tes'] = $form['id_tes'];
                    $last_rigbro_id = rigbroInsert($row);
                    if (!empty($row['extdoc'])) {
                        $tmp_file = DATA_DIR . 'files/tmp/' . $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $i . '_' . $row['extdoc'];
                        // sposto e rinomino il relativo file temporaneo
                        $fd = pathinfo($row['extdoc']);
                        rename($tmp_file, DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/rigbrodoc_' . $last_rigbro_id . '.' . $fd['extension']);
                    }
                    if (isset($form["row_$i"])) { //se è un rigo testo, inserisco il contenuto body_text
                        bodytextInsert(array('table_name_ref' => 'rigbro', 'id_ref' => $last_rigbro_id, 'body_text' => $form["row_$i"], 'lang_id' => $admin_aziend['id_language']));
                        gaz_dbi_put_row($gTables['rigbro'], 'id_rig', $last_rigbro_id, 'id_body_text', gaz_dbi_last_id());
                    }

                  }
                  $i++;
                }

                // finita l'elaborazione del form, se mi sono rimasti dei vecchi righi non più utilizzati li cancello
                if (count($old_rows)>0){
                  foreach($old_rows as $old_row){
                    array_push($syncarticols,$old_row['codart']);// Antonio Germani - aggiungo il codice articolo all'array per la sincronizzazione e-commerce
                    if (intval($old_row['id_body_text']) > 0) {  //se c'è un testo allegato al rigo elimino anch'esso
                      gaz_dbi_del_row($gTables['body_text'], "table_name_ref = 'rigbro' AND id_ref", $old_row['id_rig']);
                    }
                    gaz_dbi_del_row($gTables['rigbro'], "id_rig", $old_row['id_rig']);
                    if ($old_row['tiprig']==50 || $old_row['tiprig']==51){// se il rigo aveva un file allegato Cancello anche il file
                      $urlarr=(glob(DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/rigbrodoc_' . $old_row['id_rig'].".*"));
                      if (isset($urlarr)){
                        $fn = pathinfo($urlarr[0]);
                        unlink(DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/rigbrodoc_' . $old_row['id_rig'] . '.' . $fn['extension']);
                      }
                    }
                  }
                }

                //modifico la testata con i nuovi dati...
                $old_head = gaz_dbi_get_row($gTables['tesbro'], 'id_tes', $form['id_tes']);
                if (substr($form['tipdoc'], 0, 2) == 'DD') { //se � un DDT non fatturato
                    $form['datfat'] = '';
                    $form['numfat'] = 0;
                } else {
                    $form['datfat'] = $datemi;
                    $form['numfat'] = $old_head['numfat'];
                }
                $form['geneff'] = $old_head['geneff'];
                $form['id_contract'] = $old_head['id_contract'];
                $form['id_con'] = $old_head['id_con'];
                $form['status'] = $old_head['status'];
                $form['initra'] = $initra;
                $form['datemi'] = $datemi;
                $codice = array('id_tes', $form['id_tes']);

                tesbroUpdate($codice, $form);

                // aggiorno l'e-commerce ove presente con i dati raccolti in precedenza nell'apposito array
                if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname'])){
                    $gs=$admin_aziend['synccommerce_classname'];
                    $gSync = new $gs();
                    if($gSync->api_token){
                      foreach ($syncarticols as $syncarticol){
                        $gSync->SetProductQuantity($syncarticol);
                      }
                    }
                }
                header("Location: report_broven.php".($form['tipdoc']=='VPR'?'?auxil=VPR':'')); // in  modifica torno sempre sul report
                exit;
            } else { // e' un'inserimento
                // carico le impostazioni aggiuntive dal campo custom
                $gaz_custom_data = array();
                $gaz_custom_data['vendit']['shortdescri'] = $form['shortdescri'];
                $form['custom_field'] = json_encode($gaz_custom_data);

                // ricavo i progressivi in base al tipo di documento
                $where = "numdoc desc";
                $sql_documento = "YEAR(datemi) = " . $form['annemi'] . " and tipdoc = '" . $form['tipdoc'] . "'";
                $rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesbro'], $sql_documento, $where, 0, 1);
                $ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
                // se e' il primo documento dell'anno, resetto il contatore
                if ($ultimo_documento) {
                    $form['numdoc'] = $ultimo_documento['numdoc'] + 1;
                } else {
                    $form['numdoc'] = 1;
                }
                $form['protoc'] = 0;
                $form['numfat'] = 0;
                $form['datfat'] = 0;
                //inserisco la testata
                $form['status'] = 'GENERATO';
                $form['initra'] = $initra;
                $form['datemi'] = $datemi;
                tesbroInsert($form);
                //recupero l'id assegnato dall'inserimento
                $ultimo_id = gaz_dbi_last_id();
                //inserisco i rows
                foreach ($form['rows'] as $i => $v) {
                    $form['rows'][$i]['id_tes'] = $ultimo_id;
                    $last_rigbro_id = rigbroInsert($form['rows'][$i]);
					// INIZIO INSERIMENTO DOCUMENTI ALLEGATI
                    if (!empty($form['rows'][$i]['extdoc'])) {
                        $tmp_file = DATA_DIR . 'files/tmp/' . $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $i . '_' . $form['rows'][$i]['extdoc'];
						// sposto e rinomino il relativo file temporaneo
                        $fd = pathinfo($form['rows'][$i]['extdoc']);
                        rename($tmp_file, DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/rigbrodoc_' . $last_rigbro_id . '.' . $fd['extension']);
                    }
					// FINE INSERIMENTO DOCUMENTI ALLEGATI
                    if (isset($form["row_$i"])) { //se � un rigo testo lo inserisco il contenuto in body_text
                        bodytextInsert(array('table_name_ref' => 'rigbro', 'id_ref' => $last_rigbro_id, 'body_text' => $form["row_$i"], 'lang_id' => $admin_aziend['id_language']));
                        gaz_dbi_put_row($gTables['rigbro'], 'id_rig', $last_rigbro_id, 'id_body_text', gaz_dbi_last_id());
                    }

                    if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname'])){
                      // aggiorno l'e-commerce ove presente
                      $gs=$admin_aziend['synccommerce_classname'];
                      $gSync = new $gs();
                      if($gSync->api_token){
                        $gSync->SetProductQuantity($form['rows'][$i]['codart']);
                      }
                    }
                }
                if ($after_newdoc_back_to_doclist==1 && $pdf_to_modal==0) {
	                $_SESSION['print_queue'] = array();
	                $_SESSION['print_queue']['tpDoc'] =  $form['tipdoc'];
	                $_SESSION['print_queue']['idDoc'] = $ultimo_id;
	                $auxil = $form['tipdoc'];
	                if ($auxil == 'VOR') {
	                  $auxil = 'VO_';
	                }
	                header("Location: report_broven.php?auxil=$auxil");
	                exit;
                }
                $_SESSION['print_request'] = $ultimo_id;
                if ($pdf_to_modal==0){
                  header("Location: invsta_broven.php");
                  exit;
                }
            }
        }
    } elseif (isset($_POST['ord']) and $toDo == 'update') {  // si vuole generare un'ordine
        $sezione = $form['seziva'];
        $datemi = $form['annemi'] . "-" . $form['mesemi'] . "-" . $form['gioemi'];
        $utsemi = mktime(0, 0, 0, $form['mesemi'], $form['gioemi'], $form['annemi']);
        $initra = $form['anntra'] . "-" . $form['mestra'] . "-" . $form['giotra'];
        $utstra = mktime(0, 0, 0, $form['mestra'], $form['giotra'], $form['anntra']);
        if (!checkdate($form['mestra'], $form['giotra'], $form['anntra']))
            $msg .= "37+";
        if ($utstra < $utsemi) {
            $msg .= "38+";
        }
        if (!isset($_POST['rows'])) {
            $msg .= "39+";
        }
        if (!checkdate($form['mesemi'], $form['gioemi'], $form['annemi']))
            $msg .= "46+";
        if (empty($form['clfoco']))
            $msg .= "47+";
        if (empty($form['pagame']))
            $msg .= "48+";
        //controllo che i rows non abbiano descrizioni  e unita' di misura vuote in presenza di quantita diverse da 0
        foreach ($form['rows'] as $i => $v) {
            if ($v['descri'] == '' && ($v['quanti'] >= 0.00001 || $v['quanti'] <= -0.00001)) {
                $msgrigo = $i + 1;
                $msg .= "49+";
            }
            if ($v['unimis'] == '' && ($v['quanti'] >= 0.00001 || $v['quanti'] <= -0.00001)) {
                $msgrigo = $i + 1;
                $msg .= "50+";
            }
        }
        if ($msg == "") {// nessun errore
            // carico le impostazioni aggiuntive dal campo custom
            $gaz_custom_data = array();
            $gaz_custom_data['vendit']['shortdescri'] = $form['shortdescri'];
            $form['custom_field'] = json_encode($gaz_custom_data);

            // creo la descrizione del preventivo di origine
            require("lang." . $admin_aziend['lang'] . ".php");
            $descripreventivo = "rif. " . $strScript['admin_broven.php'][0]['VPR'] . " n." . $form['numdoc'] . " del " . $form['gioemi'] . "." . $form['mesemi'] . "." . $form['annemi'];
			// fine creazione descrizione preventivo di origine
			$sql_documento = "YEAR(datemi) = " . date("Y") . " and tipdoc = 'VOR'";
            $rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesbro'], $sql_documento, "numdoc desc", 0, 1);
            $ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
            if ($ultimo_documento) {
                $form['numdoc'] = $ultimo_documento['numdoc'] + 1;
            } else {
                $form['numdoc'] = 1;
            }
            //inserisco la testata
            $form['initra'] = $initra;
            $form['datemi'] = date("Y-m-d");
            $form['tipdoc'] = 'VOR';
            $form['status'] = 'GENERATO';
            tesbroInsert($form);
            //recupero l'id assegnato dall'inserimento
            $ultimo_id = gaz_dbi_last_id();
            //inserisco un rigo descrittivo per il riferimento al preventivo sull'ordine
            $descrirow = array('id_tes' => $ultimo_id, 'tiprig' => 2, 'descri' => $descripreventivo);
            rigbroInsert($descrirow);
            //inserisco i rows
            $count = count($form['rows']);
            for ($i = 0; $i < $count; $i++) {
                $form['rows'][$i]['id_tes'] = $ultimo_id;
                rigbroInsert($form['rows'][$i]);
                $last_rigbro_id = gaz_dbi_last_id();
                if (isset($form["row_$i"])) { //se è un rigo testo lo inserisco il contenuto in body_text
                    bodytextInsert(array('table_name_ref' => 'rigbro', 'id_ref' => $last_rigbro_id, 'body_text' => $form["row_$i"], 'lang_id' => $admin_aziend['id_language']));
                    gaz_dbi_put_row($gTables['rigbro'], 'id_rig', $last_rigbro_id, 'id_body_text', gaz_dbi_last_id());
                }
            }

            if ($after_newdoc_back_to_doclist==1) {
              $_SESSION['print_queue'] = array();
              $_SESSION['print_queue']['tpDoc'] =  $form['tipdoc'];
              $_SESSION['print_queue']['idDoc'] = $ultimo_id;
              $auxil = $form['tipdoc'];
              if ($auxil == 'VOR') {
                $auxil = 'VO_';
              }
              header("Location: report_broven.php?auxil=$auxil");
	          exit;
            }
            $_SESSION['print_request'] = $ultimo_id;
            header("Location: invsta_broven.php");
            exit;
        }
    }
    // Se viene inviata la richiesta di conferma cliente
    if ($_POST['hidden_req'] == 'clfoco') {
        $anagrafica = new Anagrafica();
        if (preg_match("/^id_([0-9]+)$/", $form['clfoco'], $match)) {
            $cliente = $anagrafica->getPartnerData($match[1], 1);
        } else {
            $cliente = $anagrafica->getPartner($form['clfoco']);
        }
        $result = gaz_dbi_get_row($gTables['imball'], "codice", $cliente['imball']);
        $form['imball'] =($result)?$result['descri']:'';
        if (($form['net_weight'] - $form['gross_weight']) >= 0) {
            $form['gross_weight'] +=($result)?$result['weight']:0;
        }
        $result = gaz_dbi_get_row($gTables['portos'], "codice", $cliente['portos']);
        $form['portos'] = ($result)?$result['descri']:'';
        $result = gaz_dbi_get_row($gTables['spediz'], "codice", $cliente['spediz']);
        $form['spediz'] = ($result)?$result['descri']:'';
        $form['destin'] = $cliente['destin'];
        $form['id_agente'] = $cliente['id_agente'];
        if ($form['id_agente'] > 0) { // carico la provvigione standard
            $provvigione = new Agenti;
            $form['in_provvigione'] = $provvigione->getPercent($form['id_agente']);
            if (isset($_POST['rows'])) {  // aggiorno le provvigioni sui rows
                foreach ($_POST['rows'] as $k => $val) {
                    $form['rows'][$k]['provvigione'] = $provvigione->getPercent($form['id_agente'], $val['codart']);
                }
            }
        }
        $form['id_des'] = $cliente['id_des'];
        $id_des = $anagrafica->getPartner($form['id_des']);
        $form['search']['id_des'] =($id_des)?substr($id_des['ragso1'], 0, 10):'';
        $des_same = gaz_dbi_get_row($gTables['destina'], "id_anagra", $cliente['id_anagra']);
        $form['id_des_same_company'] =($des_same)?$des_same['codice']:'';
        $form['in_codvat'] = $cliente['aliiva'];
        if ($cliente['cosric'] >= 100000000) {
            $form['in_codric'] = $cliente['cosric'];
        }
		if ($cliente['sconto_rigo']>=0.01){
			$form['in_sconto'] = $cliente['sconto_rigo'];
		} else {
			$form['in_sconto'] = '#';
		}
        $form['expense_vat'] = $admin_aziend['preeminent_vat'];
        if ($cliente['aliiva'] > 0) {
            $form['expense_vat'] = $cliente['aliiva'];
        }
        $form['sconto'] = $cliente['sconto'];
        $form['pagame'] = $cliente['codpag'];
        $form['change_pag'] = $cliente['codpag'];
        $form['banapp'] = $cliente['banapp'];
        $form['listin'] = $cliente['listin'];
        $form['indspe'] = $cliente['indspe'];
        $pagame = gaz_dbi_get_row($gTables['pagame'], "codice", $form['pagame']);
        if ($pagame && ($pagame['tippag'] == 'B' or $pagame['tippag'] == 'T' or $pagame['tippag'] == 'V') && $cliente['speban'] == 'S') {
            $form['speban'] = $admin_aziend['sperib'];
            $form['numrat'] = $pagame['numrat'];
        } else {
            $form['speban'] = 0.00;
            $form['numrat'] = 1;
        }
        if ($pagame && $pagame['tippag'] == 'T' && $form['stamp'] == 0) {  //se il pagamento prevede il bollo
            $form['stamp'] = $admin_aziend['perbol'];
            $form['round_stamp'] = $admin_aziend['round_bol'];
        } elseif ($pagame && $pagame['tippag'] != 'T') {
            $form['stamp'] = 0;
            $form['round_stamp'] = 0;
        }
        $form['hidden_req'] = '';
    }

    // Se viene modificato l'agente
    if ($_POST['hidden_req'] == 'AGENTE') {
        if ($form['id_agente'] > 0) { // carico la provvigione standard
            $provvigione = new Agenti;
            $form['in_provvigione'] = $provvigione->getPercent($form['id_agente']);
            if (isset($_POST['rows'])) {  // aggiorno le provvigioni sui rows
                foreach ($_POST['rows'] as $k => $val) {
                    $form['rows'][$k]['provvigione'] = $form['in_provvigione'];
                    $form['rows'][$k]['provvigione'] = $provvigione->getPercent($form['id_agente'], $val['codart']);
                }
            }
        }
        $form['hidden_req'] = '';
    }

    // Se viene inviata la richiesta di conferma rigo
    /** ENRICO FEDELE */
    /* Con button non funziona _x */
    //if (isset($_POST['in_submit_x'])) {
    /** ENRICO FEDELE */
	if (isset($_POST['in_submit_desc'])) { //rigo Descrittivo rapido
        $form['rows'][$next_row]['codart'] = '';
        $form['rows'][$next_row]['annota'] = '';
        $form['rows'][$next_row]['pesosp'] = '';
        $form['rows'][$next_row]['good_or_service'] = 0;
        $form['rows'][$next_row]['unimis'] = '';
        $form['rows'][$next_row]['quanti'] = 0;
        $form['rows'][$next_row]['prelis'] = 0;
        $form['rows'][$next_row]['codric'] = 0;
        $form['rows'][$next_row]['sconto'] = 0;
        $form['rows'][$next_row]['pervat'] = 0;
        $form['rows'][$next_row]['tipiva'] = 0;
        $form['rows'][$next_row]['ritenuta'] = 0;
        $form['rows'][$next_row]['codvat'] = 0;
        $form['rows'][$next_row]['id_doc'] = '';
        $form['rows'][$next_row]['descri'] = '';
        $form['rows'][$next_row]['id_mag'] = 0;
        $form['rows'][$next_row]['id_rig'] = 0;
        $form['rows'][$next_row]['status'] = 'INSERT';
        $form['rows'][$next_row]['scorta'] = 0;
        $form['rows'][$next_row]['quamag'] = 0;
        $form['rows'][$next_row]['tiprig'] = 2;
        $next_row++;
    } else if (isset($_POST['in_submit_text'])) { //rigo Testo rapido
        $form["row_$next_row"] = '';
        $form['rows'][$next_row]['codart'] = '';
        $form['rows'][$next_row]['annota'] = '';
        $form['rows'][$next_row]['pesosp'] = '';
        $form['rows'][$next_row]['good_or_service'] = 0;
        $form['rows'][$next_row]['unimis'] = '';
        $form['rows'][$next_row]['quanti'] = 0;
        $form['rows'][$next_row]['prelis'] = 0;
        $form['rows'][$next_row]['codric'] = 0;
        $form['rows'][$next_row]['sconto'] = 0;
        $form['rows'][$next_row]['pervat'] = 0;
        $form['rows'][$next_row]['tipiva'] = 0;
        $form['rows'][$next_row]['ritenuta'] = 0;
        $form['rows'][$next_row]['codvat'] = 0;
        $form['rows'][$next_row]['id_doc'] = '';
        $form['rows'][$next_row]['descri'] = '';
        $form['rows'][$next_row]['id_mag'] = 0;
        $form['rows'][$next_row]['id_rig'] = 0;
        $form['rows'][$next_row]['status'] = 'INSERT';
        $form['rows'][$next_row]['scorta'] = 0;
        $form['rows'][$next_row]['quamag'] = 0;
        $form['rows'][$next_row]['tiprig'] = 6;
        $next_row++;
    } else if (isset($_POST['in_submit'])) {
        $artico = gaz_dbi_get_row($gTables['artico'], "codice", $form['in_codart']);
        gaz_dbi_query ("UPDATE ".$gTables['artico']." SET `last_used`='".date("Y-m-d")."' WHERE codice='".$form['in_codart']."';");
        if (isset($artico)){
        // addizione ai totali peso,pezzi,volume
        $form['net_weight'] += $form['in_quanti'] * $artico['peso_specifico'];
        $form['gross_weight'] += $form['in_quanti'] * $artico['peso_specifico'];
        if ($artico['pack_units'] > 0) {
            $form['units'] += intval(round($form['in_quanti'] / $artico['pack_units']));
        }
        $form['volume'] += $form['in_quanti'] * $artico['volume_specifico'];
        $form['in_good_or_service']=$artico['good_or_service'];

        // fine addizione peso,pezzi,volume
        }else{
          $form['net_weight']=0;
          $form['gross_weight']=0;
          $form['volume']=0;
          $form['in_good_or_service']=0;
        }
        if (substr($form['in_status'], 0, 6) == "UPDROW") { //se e' un rigo da modificare
            $old_key = intval(substr($form['in_status'], 6));
            $form['rows'][$old_key]['tiprig'] = $form['in_tiprig'];
            $form['rows'][$old_key]['id_doc'] = $form['in_id_doc'];
            $form['rows'][$old_key]['descri'] = $form['in_descri'];
            $form['rows'][$old_key]['id_mag'] = $form['in_id_mag'];
            $form['rows'][$old_key]['id_rig'] = $form['in_id_rig'];
            $form['rows'][$old_key]['nrow'] = $form['in_nrow'];
            $form['rows'][$old_key]['nrow_linked'] = $form['in_nrow_linked'];
            $form['rows'][$old_key]['status'] = "UPDATE";
            $form['rows'][$old_key]['unimis'] = $form['in_unimis'];
            $form['rows'][$old_key]['quanti'] = $form['in_quanti'];
            $form['rows'][$old_key]['codart'] = $form['in_codart'];
            $form['rows'][$old_key]['good_or_service'] = $form['in_good_or_service'];
            $form['rows'][$old_key]['codric'] = $form['in_codric'];
            $form['rows'][$old_key]['ritenuta'] = $form['in_ritenuta'];
            $form['rows'][$old_key]['provvigione'] = $form['in_provvigione'];
            $form['rows'][$old_key]['prelis'] = number_format($form['in_prelis'], $admin_aziend['decimal_price'], '.', '');
            $form['rows'][$old_key]['sconto'] = $form['in_sconto'];
            if (isset($artico) && $artico['aliiva'] > 0) {
                $form['rows'][$old_key]['codvat'] = $artico['aliiva'];
                $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", $artico['aliiva']);
                $form['rows'][$old_key]['pervat'] = $iva_row['aliquo'];
                $form['rows'][$old_key]['tipiva'] = $iva_row['tipiva'];
            }
            if ($form['in_codvat'] > 0) {
                $form['rows'][$old_key]['codvat'] = $form['in_codvat'];
                $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['in_codvat']);
                $form['rows'][$old_key]['pervat'] = $iva_row['aliquo'];
                $form['rows'][$old_key]['tipiva'] = $iva_row['tipiva'];
            }
            /* $form['rows'][$old_key]['codvat'] = $form['in_codvat'];
              $iva_row = gaz_dbi_get_row($gTables['aliiva'],"codice",$form['in_codvat']);
              $form['rows'][$old_key]['pervat'] = $iva_row['aliquo'];
              $form['rows'][$old_key]['tipiva'] = $iva_row['tipiva']; */
            $form['rows'][$old_key]['scorta'] = '';
            $form['rows'][$old_key]['quamag'] = 0;
            $form['rows'][$old_key]['annota'] = '';
            $form['rows'][$old_key]['pesosp'] = '';
            $form['rows'][$old_key]['extdoc'] = $form['in_extdoc'];
            if ($form['in_tiprig'] == 0 and ! empty($form['in_codart'])) {  //rigo normale
              $form['rows'][$old_key]['annota'] = $artico['annota'];
              $form['rows'][$old_key]['pesosp'] = $artico['peso_specifico'];
              $form['rows'][$old_key]['unimis'] = $artico['unimis'];
              $form['rows'][$old_key]['descri'] = $artico['descri'];
              if ($form['listin'] == 2) {
                  $form['rows'][$old_key]['prelis'] = number_format($artico['preve2'], $admin_aziend['decimal_price'], '.', '');
              } elseif ($form['listin'] == 3) {
                  $form['rows'][$old_key]['prelis'] = number_format($artico['preve3'], $admin_aziend['decimal_price'], '.', '');
              } elseif ($form['listin'] == 4) {
                  $form['rows'][$old_key]['prelis'] = number_format($artico['preve4'], $admin_aziend['decimal_price'], '.', '');
              } elseif ($form['listin'] == 5) {
                  $form['rows'][$old_key]['prelis'] = number_format($artico['web_price'], $admin_aziend['decimal_price'], '.', '');
              } else {
                  $form['rows'][$old_key]['prelis'] = number_format($artico['preve1'], $admin_aziend['decimal_price'], '.', '');
              }
              $mv = $upd_mm->getStockValue(false, $form['in_codart'], $form['annemi'] . '-' . $form['mesemi'] . '-' . $form['gioemi'], $admin_aziend['stock_eval_method']);
              $magval = array_pop($mv);
              $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
              $form['rows'][$old_key]['scorta'] = $artico['scorta'];
              $form['rows'][$old_key]['quamag'] = $magval['q_g'];
            } elseif ($form['in_tiprig'] == 1 || $form['in_tiprig'] == 50) { //rigo forfait o normale con allegato
              $form['rows'][$old_key]['codart'] = "";
              $form['rows'][$old_key]['good_or_service'] = "";
              $form['rows'][$old_key]['unimis'] = "";
              $form['rows'][$old_key]['quanti'] = 0;
              $form['rows'][$old_key]['sconto'] = 0;
            } elseif ($form['in_tiprig'] == 2 || $form['in_tiprig'] == 51) { //descrittivo o descrittivo con allegato
              $form['rows'][$old_key]['codart'] = "";
              $form['rows'][$old_key]['good_or_service'] = "";
              $form['rows'][$old_key]['annota'] = "";
              $form['rows'][$old_key]['pesosp'] = "";
              $form['rows'][$old_key]['unimis'] = "";
              $form['rows'][$old_key]['quanti'] = 0;
              $form['rows'][$old_key]['prelis'] = 0;
              $form['rows'][$old_key]['codric'] = 0;
              $form['rows'][$old_key]['sconto'] = 0;
              $form['rows'][$old_key]['pervat'] = 0;
              $form['rows'][$old_key]['tipiva'] = 0;
              $form['rows'][$old_key]['ritenuta'] = 0;
              $form['rows'][$old_key]['codvat'] = 0;
            } elseif ($form['in_tiprig'] == 3) {   //var.tot.fatt.
              $form['rows'][$old_key]['codart'] = "";
              $form['rows'][$old_key]['good_or_service'] = "";
              $form['rows'][$old_key]['quanti'] = "";
              $form['rows'][$old_key]['unimis'] = "";
              $form['rows'][$old_key]['sconto'] = 0;
            } elseif ($form['in_tiprig'] >= 11 && $form['in_tiprig'] <= 31) { //rigo fattura elettronica
              $form['rows'][$old_key]['codart'] = "";
              $form['rows'][$old_key]['good_or_service'] = "";
              $form['rows'][$old_key]['annota'] = "";
              $form['rows'][$old_key]['pesosp'] = "";
              $form['rows'][$old_key]['gooser'] = 0;
              $form['rows'][$old_key]['unimis'] = "";
              $form['rows'][$old_key]['quanti'] = 0;
              $form['rows'][$old_key]['prelis'] = 0;
              $form['rows'][$old_key]['codric'] = 0;
              $form['rows'][$old_key]['sconto'] = 0;
              $form['rows'][$old_key]['pervat'] = 0;
              $form['rows'][$old_key]['tipiva'] = 0;
              $form['rows'][$old_key]['ritenuta'] = 0;
              $form['rows'][$old_key]['codvat'] = 0;
            }
            ksort($form['rows']);
        } else { //se è un rigo da inserire
          $nrow_linked = $next_row+1; // di default lo linko su se stesso
          if ($form['in_tiprig'] == 0) {   // è un rigo normale controllo se l'articolo prevede un rigo testuale che lo precede
            $article_text = gaz_dbi_get_row($gTables['company_config'], 'var', 'article_text');
            if ($article_text['val'] < 2){
              $bodytext = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", 'artico_' . $form['in_codart']);
            } else {
              $bodytext = '';
            }
            // configurazione avanzata azienda: la descrizione estesa dell'articolo
            $cbt=gaz_dbi_get_row($gTables['company_config'],'var','ext_artico_description')['val'];
            $cbt=($cbt==1||$cbt==2)?$cbt:0;
            if (!empty($bodytext) && !empty($bodytext['body_text'])) { // il testo aggiuntivo c'è (e non è vuoto)
              // creo il rigo che andrò a mettere prima o dopo o mai in base a ext_artico_description di configurazione avanzata azienda
              $rbt=[];
              $rbt['row_next_row'] = $bodytext['body_text'];
              $rbt['tiprig'] = 6;
              $rbt['descri'] = '';
              $rbt['id_mag'] = 0;
              $rbt['id_rig'] = 0;
              $rbt['nrow'] = $nrow_linked;
              $rbt['id_lotmag'] = 0;
              $rbt['identifier'] = '';
              $rbt['cod_operazione'] = 11;
              $rbt['recip_stocc'] = '';
              $rbt['recip_stocc_destin'] = '';
              $rbt['lot_or_serial'] = 0;
              $rbt['SIAN'] = 0;
              $rbt['status'] = '';
              $rbt['scorta'] = 0;
              $rbt['quamag'] = 0;
              $rbt['codart'] = '';
              $rbt['annota'] = '';
              $rbt['pesosp'] = '';
              $rbt['gooser'] = 0;
              $rbt['unimis'] = '';
              $rbt['quanti'] = 0;
              $rbt['prelis'] = 0;
              $rbt['codric'] = 0;
              $rbt['sconto'] = 0;
              $rbt['pervat'] = 0;
              $rbt['tipiva'] = 0;
              $rbt['ritenuta'] = 0;
              $rbt['codvat'] = 0;
              if ($cbt==1) {
                $rbt['nrow_linked'] = $next_row+2; // se il testuale viene prima lo linko al rigo successivo
                $form["row_$next_row"] = $bodytext['body_text'];
                $form['rows'][$next_row]=$rbt;
                $next_row++;
              } elseif ($cbt==2) {
                $nrow_linked = $next_row+2; // se il testuale viene dopo lo linko al rigo successivo
              }
            }
          }
          $form['rows'][$next_row]['tiprig'] = $form['in_tiprig'];
          $form['rows'][$next_row]['id_doc'] = $form['in_id_doc'];
          $form['rows'][$next_row]['descri'] = $form['in_descri'];
          $form['rows'][$next_row]['id_mag'] = $form['in_id_mag'];
          $form['rows'][$next_row]['id_rig'] = $form['in_id_rig'];
          $form['rows'][$next_row]['nrow'] =  $next_row+1;
          $form['rows'][$next_row]['nrow_linked'] =  $nrow_linked; // se non ho avuto $cbt=1 questo sarà lo stesso di nrow in cbt
          // se non è linkato ad altri righi li imposto uguali
          $form['rows'][$next_row]['nrow'] =$next_row+1;
          $form['rows'][$next_row]['extdoc'] = 0;
          $form['rows'][$next_row]['status'] = "INSERT";
          $form['rows'][$next_row]['scorta'] = 0;
          $form['rows'][$next_row]['quamag'] = 0;
          if ($form['in_tiprig'] == 0) {  //rigo normale
            $form['rows'][$next_row]['codart'] = $form['in_codart'];
            $form['rows'][$next_row]['good_or_service'] = $form['in_good_or_service'];
            $form['rows'][$next_row]['annota'] = (isset($artico))?$artico['annota']:'';
            $form['rows'][$next_row]['pesosp'] = (isset($artico))?$artico['peso_specifico']:0;
            $form['rows'][$next_row]['descri'] = (isset($artico))?$artico['descri']:'';
            $form['rows'][$next_row]['unimis'] = (isset($artico))?$artico['unimis']:'n';
            $form['rows'][$next_row]['prelis'] = number_format($form['in_prelis'], $admin_aziend['decimal_price'], '.', '');
            $form['rows'][$next_row]['codric'] = $form['in_codric'];
            $form['rows'][$next_row]['quanti'] = $form['in_quanti'];
            $form['rows'][$next_row]['sconto'] = $form['in_sconto'];
            /** inizio modifica FP 09/10/2015
             * se non ho inserito uno sconto nella maschera prendo quello standard registrato nell'articolo
             */
            //rimossa            $form['rows'][$next_row]['sconto'] = $form['in_sconto'];
            $in_sconto = $form['in_sconto'];
            if ($in_sconto != "#") {
                $form['rows'][$next_row]['sconto'] = $in_sconto;
            } else {
              if ($form["sconto"] > 0) { // gestione sconto cliente sul totale merce o sul rigo
                            $form['rows'][$next_row]['sconto'] = 0;
              } else {
                $comp = new venditCalc();
                $tmpPrezzoNetto_Sconto = $comp->trovaPrezzoNetto_Sconto((isset($cliente['codice']))?$cliente['codice']:0, $form['rows'][$next_row]['codart'], (isset($artico['sconto']))?$artico['sconto']:0);
                if ($tmpPrezzoNetto_Sconto < 0) { // è un prezzo netto
                  $form['rows'][$next_row]['prelis'] = -$tmpPrezzoNetto_Sconto;
                  $form['rows'][$next_row]['sconto'] = 0;
                } else {
                  $form['rows'][$next_row]['sconto'] = $tmpPrezzoNetto_Sconto;
                }
              }
            }
            /* fine modifica FP */
            $form['rows'][$next_row]['ritenuta'] = $form['in_ritenuta'];
            $provvigione = new Agenti;
            $form['rows'][$next_row]['provvigione'] = $provvigione->getPercent($form['id_agente'], $form['in_codart']);
            if (!isset($tmpPrezzoNetto_Sconto) or ( $tmpPrezzoNetto_Sconto >= 0)) { // non ho trovato un prezzo netto per il cliente/articolo
                if ($form['listin'] == 2) {
                    $form['rows'][$next_row]['prelis'] = number_format((isset($artico['preve2']))?$artico['preve2']:0, $admin_aziend['decimal_price'], '.', '');
                } elseif ($form['listin'] == 3) {
                    $form['rows'][$next_row]['prelis'] = number_format($artico['preve3'], $admin_aziend['decimal_price'], '.', '');
                } elseif ($form['listin'] == 4) {
                    $form['rows'][$next_row]['prelis'] = number_format($artico['preve4'], $admin_aziend['decimal_price'], '.', '');
                } elseif ($form['listin'] == 5) {
                    $form['rows'][$next_row]['prelis'] = number_format((isset($artico))?$artico['web_price']:0, $admin_aziend['decimal_price'], '.', '');
                } else {
                    $form['rows'][$next_row]['prelis'] = number_format((isset($artico['preve1']))?$artico['preve1']:0, $admin_aziend['decimal_price'], '.', '');
                }
            }
            $form['rows'][$next_row]['codvat'] = $admin_aziend['preeminent_vat'];
            $iva_azi = gaz_dbi_get_row($gTables['aliiva'], "codice", $admin_aziend['preeminent_vat']);
            $form['rows'][$next_row]['pervat'] = $iva_azi['aliquo'];
            $form['rows'][$next_row]['tipiva'] = $iva_azi['tipiva'];
            if (isset($artico) && $artico['aliiva'] > 0) {
                $form['rows'][$next_row]['codvat'] = $artico['aliiva'];
                $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", $artico['aliiva']);
                $form['rows'][$next_row]['pervat'] = $iva_row['aliquo'];
                $form['rows'][$next_row]['tipiva'] = $iva_row['tipiva'];
            }
            if ($form['in_codvat'] > 0) {
                $form['rows'][$next_row]['codvat'] = $form['in_codvat'];
                $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['in_codvat']);
                $form['rows'][$next_row]['pervat'] = $iva_row['aliquo'];
                $form['rows'][$next_row]['tipiva'] = $iva_row['tipiva'];
            }
            if (isset($artico) && $artico['codcon'] > 0) {
                $form['rows'][$next_row]['codric'] = $artico['codcon'];
                $form['in_codric'] = $artico['codcon'];
            }
            $mv = $upd_mm->getStockValue(false, $form['in_codart'], $form['annemi'] . '-' . $form['mesemi'] . '-' . $form['gioemi'], $admin_aziend['stock_eval_method']);
            $magval = array_pop($mv);
            $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
            $form['rows'][$next_row]['scorta'] = (isset($artico))?$artico['scorta']:0;
            $form['rows'][$next_row]['quamag'] = $magval['q_g'];
            if (isset($artico) && $artico['good_or_service']==2 and $tipo_composti['val']=="KIT") {
                $whe_dis = "codice_composizione = '".$form['in_codart']."'";
                $res_dis = gaz_dbi_dyn_query('*', $gTables['distinta_base'], $whe_dis, 'id', 0, PER_PAGE);
                while ($row_dis = gaz_dbi_fetch_array($res_dis)) {
                    $next_row++;
                    $result2 = gaz_dbi_dyn_query('*', $gTables['artico'], " codice = '".$row_dis['codice_artico_base']."'", 'codice', 0, PER_PAGE);
                    $row2 = gaz_dbi_fetch_array($result2);
                    $form['rows'][$next_row]['lot_or_serial'] = 0;
                    $form['rows'][$next_row]['id_lotmag'] = 0;
                    $form['rows'][$next_row]['tiprig'] = 210;
                    $form['rows'][$next_row]['id_mag'] = "";
                    $form['rows'][$next_row]['id_rig'] = "";
                    $form['rows'][$next_row]['status'] = "INSERT";
                    $form['rows'][$next_row]['scorta'] = 0;
                    $form['rows'][$next_row]['codart'] = $row2['codice'];
                    $form['rows'][$next_row]['good_or_service'] = $row2['good_or_service'];
                    $form['rows'][$next_row]['descri'] = $row2['descri'];
                    $form['rows'][$next_row]['unimis'] = $row2['unimis'];
                    $form['rows'][$next_row]['prelis'] = 0;
                    $form['rows'][$next_row]['quanti'] = $row_dis['quantita_artico_base'];
                    $form['rows'][$next_row]['id_doc'] = "";
                    $form['rows'][$next_row]['prelis'] = 0;
                    $form['rows'][$next_row]['codric'] = 0;
                    $form['rows'][$next_row]['sconto'] = 0;
                    $form['rows'][$next_row]['pervat'] = 0;
                    $form['rows'][$next_row]['tipiva'] = 0;
                    $form['rows'][$next_row]['ritenuta'] = 0;
                    $form['rows'][$next_row]['codvat'] = 0;
                    $form['rows'][$next_row]['annota'] = "";
                    $form['rows'][$next_row]['pesosp'] = 0;
                }
            }
            if (!empty($bodytext) && !empty($bodytext['body_text']) && $cbt== 2) { // il testo aggiuntivo c'è, non è vuoto e va dopo il rigo normale
                $next_row++;
                $rbt['nrow_linked'] = $next_row; // se il testuale viene dopo lo linko al rigo precedente
                $rbt['nrow'] = $next_row+1;
                $form["row_$next_row"] = $bodytext['body_text'];
                $form['rows'][$next_row]=$rbt;
            }
          } elseif ($form['in_tiprig'] == 1 || $form['in_tiprig'] == 50) { //rigo forfait o normale con allegato
                $form['rows'][$next_row]['codart'] = "";
                $form['rows'][$next_row]['good_or_service'] = "";
                $form['rows'][$next_row]['annota'] = "";
                $form['rows'][$next_row]['pesosp'] = "";
                $form['rows'][$next_row]['unimis'] = "";
                $form['rows'][$next_row]['quanti'] = 0;
                $form['rows'][$next_row]['prelis'] = 0;
                $form['rows'][$next_row]['codric'] = $form['in_codric'];
                $form['rows'][$next_row]['sconto'] = 0;
                $form['rows'][$next_row]['codvat'] = $admin_aziend['preeminent_vat'];
                $iva_azi = gaz_dbi_get_row($gTables['aliiva'], "codice", $admin_aziend['preeminent_vat']);
                $form['rows'][$next_row]['pervat'] = $iva_azi['aliquo'];
                $form['rows'][$next_row]['tipiva'] = $iva_azi['tipiva'];
                if ($form['in_codvat'] > 0) {
                  $form['rows'][$next_row]['codvat'] = $form['in_codvat'];
                  $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['in_codvat']);
                  $form['rows'][$next_row]['pervat'] = $iva_row['aliquo'];
                  $form['rows'][$next_row]['tipiva'] = $iva_row['tipiva'];
                }
                $form['rows'][$next_row]['ritenuta'] = $form['in_ritenuta'];
            } elseif ($form['in_tiprig'] == 2 || $form['in_tiprig'] == 51) { //descrittivo o descrittivo con allegato
                $form['rows'][$next_row]['codart'] = "";
                $form['rows'][$next_row]['good_or_service'] = "";
                $form['rows'][$next_row]['annota'] = "";
                $form['rows'][$next_row]['pesosp'] = "";
                $form['rows'][$next_row]['unimis'] = "";
                $form['rows'][$next_row]['quanti'] = 0;
                $form['rows'][$next_row]['prelis'] = 0;
                $form['rows'][$next_row]['codric'] = 0;
                $form['rows'][$next_row]['sconto'] = 0;
                $form['rows'][$next_row]['pervat'] = 0;
                $form['rows'][$next_row]['tipiva'] = 0;
                $form['rows'][$next_row]['ritenuta'] = 0;
                $form['rows'][$next_row]['codvat'] = 0;
            } elseif ($form['in_tiprig'] == 3) {
                $form['rows'][$next_row]['codart'] = "";
                $form['rows'][$next_row]['good_or_service'] = "";
                $form['rows'][$next_row]['annota'] = "";
                $form['rows'][$next_row]['pesosp'] = "";
                $form['rows'][$next_row]['quanti'] = 0;
                $form['rows'][$next_row]['prelis'] = number_format($form['in_prelis'], $admin_aziend['decimal_price'], '.', '');
                $form['rows'][$next_row]['codric'] = $form['in_codric'];
                $form['rows'][$next_row]['sconto'] = 0;
                $form['rows'][$next_row]['codvat'] = $form['in_codvat'];
                $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['in_codvat']);
                $form['rows'][$next_row]['pervat'] = $iva_row['aliquo'];
                $form['rows'][$next_row]['tipiva'] = $iva_row['tipiva'];
                $form['rows'][$next_row]['ritenuta'] = 0;
            } elseif ($form['in_tiprig'] > 5 && $form['in_tiprig'] < 9) { //testo
                $form["row_$next_row"] = "";
                $form['rows'][$next_row]['codart'] = "";
                $form['rows'][$next_row]['good_or_service'] = "";
                $form['rows'][$next_row]['annota'] = "";
                $form['rows'][$next_row]['pesosp'] = "";
                $form['rows'][$next_row]['unimis'] = "";
                $form['rows'][$next_row]['quanti'] = 0;
                $form['rows'][$next_row]['prelis'] = 0;
                $form['rows'][$next_row]['codric'] = 0;
                $form['rows'][$next_row]['sconto'] = 0;
                $form['rows'][$next_row]['pervat'] = 0;
                $form['rows'][$next_row]['tipiva'] = 0;
                $form['rows'][$next_row]['codvat'] = 0;
                $form['rows'][$next_row]['ritenuta'] = 0;
            } elseif ($form['in_tiprig'] >= 11 && $form['in_tiprig'] <= 31) { //dati fattura elettronica
                $form['rows'][$next_row]['codart'] = "";
                $form['rows'][$next_row]['good_or_service'] = "";
                $form['rows'][$next_row]['annota'] = "";
                $form['rows'][$next_row]['pesosp'] = "";
                $form['rows'][$next_row]['gooser'] = 0;
                $form['rows'][$next_row]['unimis'] = "";
                $form['rows'][$next_row]['quanti'] = 0;
                $form['rows'][$next_row]['prelis'] = 0;
                $form['rows'][$next_row]['codric'] = 0;
                $form['rows'][$next_row]['sconto'] = 0;
                $form['rows'][$next_row]['pervat'] = 0;
                $form['rows'][$next_row]['tipiva'] = 0;
                $form['rows'][$next_row]['ritenuta'] = 0;
                $form['rows'][$next_row]['codvat'] = 0;
            }
        }
        // reinizializzo rigo di input tranne che per il tipo rigo e aliquota iva
        $form['in_descri'] = "";
        $form['in_codart'] = "";
        $form['in_good_or_service'] = "";
        $form['in_unimis'] = "";
        $form['in_prelis'] = 0;
//      $form['in_sconto'] = '#';  non azzero il campo in_sconto (sconto rigo)
        $form['in_quanti'] = 0;
        $form['in_codric'] = substr($admin_aziend['impven'], 0, 3);
        $form['in_id_mag'] = 0;
        $form['in_id_rig'] = 0;
        $form['in_annota'] = "";
        $form['in_scorta'] = 0;
        $form['in_quamag'] = 0;
        $form['in_pesosp'] = 0;
        $form['in_status'] = "INSERT";
        // fine reinizializzo rigo input
        $form['cosear'] = "";
        $next_row++;
    }

    // Se viene richiesto lo spostamento di un rigo
    if ($_POST['hidden_req']=='moverow') {
      $form['hidden_req'] = '';
      $kFrom=intval($_POST['moved_nrow'])-1;
      $kTo=intval($_POST['moved_to'])-1;
      $accnew=[];
      $acctesto=[];
      $to_data=$form['rows'][$kTo];
      $nrow_data=$form['rows'][$kFrom];
      $from_islink = ($nrow_data['nrow']<$nrow_data['nrow_linked'])?$nrow_data['nrow_linked']:false;
      // controllo per evitare di spostare su un rigo linkato
      if($to_data['nrow']>$to_data['nrow_linked']) {
        $msg .= "59+";
      } else {
        // mi creo un array con i riferimenti ai righi movimentati
        $jumplnk=true;
        foreach($form['rows'] as $k=>$v){ // riattraverso tutti i righi per spostare il rigo richiesto (eventualmente assieme a quello linkato) ed i successivi
          $nextlink=($v['nrow']<$v['nrow_linked'])?true:false;

// si possono verificare due casi: il rigo è spostato in giù oppure in su

          if ($kFrom > $kTo) {
// SPOSTO SU
      // LINKATO
           if ($from_islink) {  // sto spostando uno linkato, dovrò aggiungere anche il rigo collegato e poi spostare di 2 tutti i successivi
            if ($k < $kTo || $k > ($kFrom+1) ) { // è un rigo che sta prima della destinazione o dopo il rigo spostato, lo riaccumulo così com'è
              $accnew[$k]=$v;
              if (isset($form["row_".$k])){
                $acctesto[$k]=$form["row_".$k];
                unset($form["row_".$k]);
              }
            } elseif($k==$kTo) {
              // è il posto dove è destinato il rigo, lo valorizzo con esso
              $rowFrom= $form['rows'][$kFrom];
              $rowFrom['nrow']=$k+1;
              $rowFrom['nrow_linked']=$k+2;
              $accnew[$k]=$rowFrom;
              // accumulo anche eventuale testo
              if (isset($form["row_".$kFrom])) {
                $acctesto[$k]=$form["row_".$kFrom];
                unset($form["row_".$kFrom]);
              }
              // aggiungo subito dopo il linkato all'accumulatore
              $form['rows'][($from_islink-1)]['nrow']=$k+1;
              $form['rows'][($from_islink-1)]['nrow_linked']=$k;
              $accnew[($k+1)]=$form['rows'][($from_islink-1)];
              // accumulo testo linkato
              if (isset($form["row_".($from_islink-1)])){
                $acctesto[($k+1)]=$form["row_".($from_islink-1)];
                unset($form["row_".($from_islink-1)]);
              }
              // riprendo il vecchio rigo e lo riposiziono più in basso tal quale
              $v['nrow'] += 2;
              $v['nrow_linked'] += 2;
              $accnew[($k+2)]=$v;
              // accumulo eventuale testo originale
              if (isset($form["row_".$k])){
                $acctesto[$k+2]=$form["row_".$k];
                unset($form["row_".$k]);
              }
            } elseif( $k == $kFrom || $k == ($from_islink-1)) { // quello spostato ed il relativo linkato l'ho accumulato sopra

            } else { // tutti i righi compresi tra partenza e destinazione li sposto in basso di 2
                $v['nrow'] += 2;
                $v['nrow_linked'] += 2;
                $accnew[($k+2)]=$v;
                // sposto un eventuale testo
                if (isset($form["row_".$k])){
                  $acctesto[($k+2)]=$form["row_".$k];
                  unset($form["row_".$k]);
                }
            }
           } else {
      // NON LINKATO
            if ($k < $kTo || $k > $kFrom) { // è un rigo che sta prima della destinazione o dopo il rigo spostato, lo riaccumulo così com'è
              $accnew[$k]=$v;
              if (isset($form["row_".$k])){
                $acctesto[$k]=$form["row_".$k];
                unset($form["row_".$k]);
              }
            } elseif($k==$kTo) { // è la posizione di destinazione
              $form['rows'][$kFrom]['nrow']=$k+1;
              $form['rows'][$kFrom]['nrow_linked']=$k+1;
              $accnew[$k]=$form['rows'][$kFrom];
              // se il rigo spostato è un testo lo accumulo sulla sua matrice
              if (isset($form["row_".$kFrom])){
                $acctesto[$k]=$form["row_".$kFrom];
                unset($form["row_".$kFrom]);
              }
              //  e sposto questo verso il basso quello che c'era prima
                $form['rows'][$k]['nrow']=$v['nrow']+1;
                $form['rows'][$k]['nrow_linked']=$v['nrow_linked']+1;
                $accnew[$k+1]=$form['rows'][$k];
                // sposto un eventuale testo
                if (isset($form["row_".$k])){
                  $acctesto[$k+1]=$form["row_".$k];
                  unset($form["row_".$k]);
                }
            } elseif( $k == $kFrom) { // quello spostato l'ho già accumulato  verso l'alto

            } else { // tutti i righi compresi tra partenza e destinazione
                $v['nrow']++;
                $v['nrow_linked']++;
                $accnew[($k+1)]=$v;
                // sposto un eventuale testo
                if (isset($form["row_".$k])){
                  $acctesto[($k+1)]=$form["row_".$k];
                  unset($form["row_".$k]);
                }
            }
           }

          } else {

// SPOSTO GIU

      // LINKATO
           if ($from_islink) {  // sto spostando uno linkato , devo partire da una posizione in meno rispetto al singolo, e poi aggiungere anche il rigo collegato
            if ($k < $kFrom || $k >= $kTo) { // è un rigo che stanno prima di quello spostato o quello di destinazione e successivi,  lo riaccumulo così com'è
              $accnew[$k]=$form['rows'][$k];
            } elseif( $k == $kFrom) { // è il rigo spostato
                $v['nrow']=$kTo-1;
                $v['nrow_linked']=$kTo;
                $accnew[$kTo-2]=$v; // lo accumulo
                // sposto un eventuale testo
                if (isset($form["row_$k"])){
                  $acctesto[$kTo-2]=$form["row_$k"];
                  unset($form["row_$k"]);
                }
                // aggiungo anche il relativo linkato all'accumulatore
                $form['rows'][($from_islink-1)]['nrow']=$kTo;
                $form['rows'][($from_islink-1)]['nrow_linked']=$kTo-1;
                $accnew[$kTo-1]=$form['rows'][($from_islink-1)];
                // sposto un eventuale testo
                if (isset($form["row_".($from_islink-1)])){
                  $acctesto[$kTo-1]=$form["row_".($from_islink-1)];
                  unset($form["row_".($from_islink-1)]);
                }
            } else { // tutti i righi compresi tra partenza e destinazione
                if ($jumplnk){ // salto il linkato perché già aggiunto sopra
                  $jumplnk=false;
                } else {
                  $v['nrow'] -=2;
                  $v['nrow_linked'] -=2;
                  $accnew[$k-2]=$v;
                 // sposto un eventuale testo
                  if (isset($form["row_".$k])){
                    $acctesto[$k-2]=$form["row_".$k];
                    unset($form["row_".$k]);
                  }
                }
            }
      // NON LINKATO
           } else {
            if ($k < $kFrom || $k >= $kTo) { // è un rigo che sta prima di quello spostato o quello di destinazione e successivi,  lo riaccumulo così com'è
              $accnew[$k]=$form['rows'][$k];
            } elseif( $k == $kFrom) { // è il rigo spostato
                $v['nrow']=$kTo-1;
                $v['nrow_linked']=$kTo-1;
                $accnew[$kTo-1]=$v; // lo accumulo di un rigo prima di quello indicato in destinazione
                // sposto un eventuale testo
                if (isset($form["row_$k"])){
                  $acctesto[$kTo-1]=$form["row_$k"];
                  unset($form["row_$k"]);
                }
            } else { // tutti i righi compresi tra partenza e destinazione
                $v['nrow']--;
                $v['nrow_linked']--;
                $accnew[$k-1]=$v;
                // sposto un eventuale testo
                if (isset($form["row_".$k])){
                  $acctesto[$k-1]=$form["row_".$k];
                  unset($form["row_".$k]);
                }
            }
           }
          }
        }

        ksort($accnew);
        foreach($acctesto as $kt => $vt){
          $form["row_$kt"]=$vt;
        }
        $form['rows']=$accnew;
      }
    }

    // Se viene inviata la richiesta elimina il rigo corrispondente
    if (isset($_POST['del'])) {
        $delri = key($_POST['del']);
        // sottrazione ai totali peso,pezzi,volume
        $artico = gaz_dbi_get_row($gTables['artico'], "codice", $form['rows'][$delri]['codart']);
        if (isset($artico)){
          $form['net_weight'] -= $form['rows'][$delri]['quanti'] * $artico['peso_specifico'];
          $form['gross_weight'] -= $form['rows'][$delri]['quanti'] * $artico['peso_specifico'];
          if ($artico['pack_units'] > 0) {
              $form['units'] -= intval(round($form['rows'][$delri]['quanti'] / $artico['pack_units']));
          }
          $form['volume'] -= $form['rows'][$delri]['quanti'] * $artico['volume_specifico'];
        }
        // fine sottrazione peso,pezzi,volume
        $islinked=false;
        foreach ($form['rows'] as $k => $val) { // primo ciclo per controllare i linked
          // trovo qual'è il k di un eventuale rigo linkato da eliminare
          if ( $k == $delri ) { // è il rigo da eliminare
            if (($val['nrow']+1) == $val['nrow_linked']) { // ha un link con il successivo
              $islinked=true;
            } else if (($val['nrow']-1) == $val['nrow_linked'] ){ // link con il precedente
              $islinked=true;
              $delri--; // parto con cancellare anche il precedente
            }
          }
        }
        $ndelrow=$islinked?2:1;
        foreach ($form['rows'] as $k => $val) {
          // diminuisco o lascio inalterati gli index dei testi
          if (isset($form["row_$k"])) { //se ho un rigo testo
            if ($k > ($delri+$ndelrow-1)) { //se ho un rigo testo dopo
              $new_k = $k - $ndelrow;
              $form["row_$new_k"] = $form["row_$k"];
              unset($form["row_$k"]);
            }
          }
        }
        array_splice($form['rows'], $delri, $ndelrow);
        $next_row -= $ndelrow;
    }
} elseif ((!isset($_POST['Update'])) and ( isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $tesbro = gaz_dbi_get_row($gTables['tesbro'], "id_tes", $_GET['id_tes']);
    // torno indietro se il tipdoc non è tra quelli gestiti da questo modulo ( tesbro può essere usato per molto altro)
    if (!$tesbro['tipdoc']=='VOR' && !$tesbro['tipdoc']=='VPR'){
      header("Location: " . $_SERVER['HTTP_REFERER']);
    }
    $anagrafica = new Anagrafica();
    $cliente = $anagrafica->getPartner($tesbro['clfoco']);
    $form['indspe'] = $cliente?$cliente['indspe']:'';
    $rs_rig = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = " . intval($_GET['id_tes']), "id_rig ASC");
    $id_des = $anagrafica->getPartner($tesbro['id_des']);
    $form['id_tes'] = intval($_GET['id_tes']);
    $form['hidden_req'] = '';
    // inizio rigo di input
    $form['in_descri'] = "";
    $form['in_tiprig'] = 0;
    $form['in_id_doc'] = 0;
    /*   $form['in_artsea'] = $admin_aziend['artsea']; */
    $form['in_codart'] = "";
    $form['in_good_or_service'] = "";
    $form['in_pervat'] = 0;
    $form['in_tipiva'] = 0;
    $form['in_ritenuta'] = 0;
    $form['in_unimis'] = "";
    $form['in_prelis'] = 0;
    /** inizio modifica FP 09/10/2015
     * inizializzo il campo con '#' per indicare che voglio lo sconto standard dell'articolo
     */
    //rimossa    $form['in_sconto'] = 0;
    $form['in_sconto'] = '#';
    /* fine modifica FP */
    $form['in_quanti'] = 0;
    $form['in_codvat'] = $cliente?$cliente['aliiva']:'';
    $form['in_codric'] = substr($admin_aziend['impven'], 0, 3);
    $form['in_id_mag'] = 0;
    $form['in_id_rig'] = 0;
    $form['in_nrow'] = 0;
    $form['in_nrow_linked'] = 0;
    $form['in_annota'] = "";
    $form['in_pesosp'] = 0;
    $form['in_scorta'] = 0;
    $form['in_quamag'] = 0;
    $form['in_extdoc'] = 0;
    $form['in_status'] = "INSERT";
    $form['in_codric'] = $admin_aziend['impven'];

    // fine rigo input
    $form['rows'] = array();
    // ...e della testata
    $form['search']['clfoco'] = $cliente?substr($cliente['ragso1'], 0, 10):'';
    $form['print_total'] = $tesbro['print_total'];
    $form['delivery_time'] = $tesbro['delivery_time'];
    $form['day_of_validity'] = $tesbro['day_of_validity'];
    $form['cosear'] = "";
    $form['seziva'] = $tesbro['seziva'];
    $form['tipdoc'] = $tesbro['tipdoc'];
    $form['gioemi'] = substr($tesbro['datemi'], 8, 2);
    $form['mesemi'] = substr($tesbro['datemi'], 5, 2);
    $form['annemi'] = substr($tesbro['datemi'], 0, 4);
    $form['giotra'] = substr($tesbro['initra'], 8, 2);
    $form['mestra'] = substr($tesbro['initra'], 5, 2);
    $form['anntra'] = substr($tesbro['initra'], 0, 4);
    $form['oratra'] = substr($tesbro['initra'], 11, 2);
    $form['mintra'] = substr($tesbro['initra'], 14, 2);
    $form['protoc'] = $tesbro['protoc'];
    $form['numdoc'] = $tesbro['numdoc'];
    $form['numfat'] = $tesbro['numfat'];
    $form['datfat'] = $tesbro['datfat'];
    $form['clfoco'] = $tesbro['clfoco'];
    $form['pagame'] = $tesbro['pagame'];
    $form['change_pag'] = $tesbro['pagame'];
    $form['speban'] = $tesbro['speban'];
    $pagame = gaz_dbi_get_row($gTables['pagame'], "codice", $form['pagame']);
    if ($pagame && ($pagame['tippag'] == 'B' || $pagame['tippag'] == 'T' || $pagame['tippag'] == 'V' || $pagame['tippag'] == 'K') && $cliente['speban'] == 'S') {
        $form['numrat'] = $pagame['numrat'];
    } else {
        $form['speban'] = 0.00;
        $form['numrat'] = 1;
    }
    $form['banapp'] = $tesbro['banapp'];
    $form['weekday_repeat'] = $tesbro['weekday_repeat'];
    $form['vettor'] = $tesbro['vettor'];
    $form['id_agente'] = $tesbro['id_agente'];
    $provvigione = new Agenti;
    $form['in_provvigione'] = $provvigione->getPercent($form['id_agente']);
    $form['net_weight'] = $tesbro['net_weight'];
    $form['gross_weight'] = $tesbro['gross_weight'];
    $form['units'] = $tesbro['units'];
    $form['volume'] = $tesbro['volume'];
    $form['listin'] = $tesbro['listin'];
    $form['spediz'] = $tesbro['spediz'];
    $form['portos'] = $tesbro['portos'];
    $form['imball'] = $tesbro['imball'];
    $form['destin'] = $tesbro['destin'];
    $form['id_des'] = $tesbro['id_des'];
    $form['id_des_same_company'] = $tesbro['id_des_same_company'];
    $form['search']['id_des'] =($id_des)?substr($id_des['ragso1'], 0, 10):'';
    $form['traspo'] = $tesbro['traspo'];
    $form['spevar'] = $tesbro['spevar'];
    $form['expense_vat'] = $tesbro['expense_vat'];
    $form['virtual_taxstamp'] = $tesbro['virtual_taxstamp'];
    $form['taxstamp'] = $tesbro['taxstamp'];
    $form['stamp'] = $tesbro['stamp'];
    $form['round_stamp'] = $tesbro['round_stamp'];
    $form['cauven'] = $tesbro['cauven'];
    $form['caucon'] = $tesbro['caucon'];
    $form['caumag'] = $tesbro['caumag'];
    $form['caucon'] = $tesbro['caucon'];
    $form['sconto'] = $tesbro['sconto'];

    // carico le impostazioni aggiuntive dal campo custom
    $gaz_custom_data = array();
    $gaz_custom_field = gaz_dbi_get_single_value( $gTables['tesbro'], 'custom_field', 'id_tes = '.$form['id_tes'] );
    if ( isset( $gaz_custom_field ) && $gaz_custom_field!="" ) {
        $gaz_custom_data = json_decode($gaz_custom_field,true);
        if (isset($gaz_custom_data['vendit'])){// se c'è il custom field vendit
          $form['shortdescri'] = $gaz_custom_data['vendit']['shortdescri'];
        }else{
          $form['shortdescri'] = "";
        }
    } else {
        $form['shortdescri'] = "";
    }

    // INIZIO rinumerazione per retrocompatibilità e bypass possibili errori introdotti da bug passati
    $prev_nrow=0;
    $prev_nrow_linked=0;
    $linked_with_next=false;
    $rows=[];
    $nr=0;
    while ($r = gaz_dbi_fetch_array($rs_rig)) {
      $nr++;
      $rows[$nr] = $r;
      if ($linked_with_next && $r['tiprig'] == 6 ) { // il precedente dovrebbe essere linkato con questo, lo accetto solo se di tipo 6 testo
        $rows[$nr]['nrow']=$nr;
        $rows[$nr]['nrow_linked']=intval($nr-1);
        $linked_with_next=false;
      } elseif ($r['nrow'] == $prev_nrow ) { // ha lo stesso nrow del precedente, è una anomalia di per se, lo svincolo
        $rows[$nr]['nrow']=$nr;
        $rows[$nr]['nrow_linked']=$nr;
        $linked_with_next=false;
      } elseif ($r['nrow_linked'] == $prev_nrow ) { // è linkato con il precedente
        if ($linked_with_next) { // ed anche il precedente era linkato, tutto ok
          $rows[$nr]['nrow']=$nr;
          $rows[$nr]['nrow_linked']=intval($nr-1);
          $linked_with_next=false;
        } else { // ... altrimenti li svincolo entrambi non sapendo a chi riferirli
          $rows[$nr]['nrow']=$nr;
          $rows[$nr]['nrow_linked']=$nr;
          $rows[intval($nr-1)]['nrow']=intval($nr-1);
          $rows[intval($nr-1)]['nrow_linked']=intval($nr-1);
          $linked_with_next=false;
        }
      } elseif ($r['nrow_linked'] == intval($r['nrow']+1) ) { // è linkato con il successivo
        $rows[$nr]['nrow']=$nr;
        $rows[$nr]['nrow_linked']= intval($nr+1);
        $linked_with_next =true; // tengo traccia per controllare il prossimo
      } else  {
        $rows[$nr]['nrow']=$nr;
        $rows[$nr]['nrow_linked']=$nr;
        $linked_with_next=false;
      }
      $prev_nrow = $r['nrow'];
    }
    // FINE rinumerazione
    $next_row = 0;
    foreach ($rows as $rigo ) {
        $articolo = gaz_dbi_get_row($gTables['artico'], "codice", $rigo['codart']);

        if ($rigo['id_body_text'] > 0) { //se ho un rigo testo
            $text = gaz_dbi_get_row($gTables['body_text'], "id_body", $rigo['id_body_text']);
            $form["row_$next_row"] = $text['body_text'];
        }
        $form['rows'][$next_row]['descri'] = $rigo['descri'];
        $form['rows'][$next_row]['tiprig'] = $rigo['tiprig'];
        $form['rows'][$next_row]['id_doc'] = $rigo['id_doc'];
        $form['rows'][$next_row]['codart'] = $rigo['codart'];
        $form['rows'][$next_row]['good_or_service'] = (isset($articolo['good_or_service']))?$articolo['good_or_service']:1;
        $form['rows'][$next_row]['pervat'] = $rigo['pervat'];
        $iva_row = gaz_dbi_get_row($gTables['aliiva'], 'codice', $rigo['codvat']);
        $form['rows'][$next_row]['tipiva'] = (isset($iva_row['tipiva']))?$iva_row['tipiva']:'';
        $form['rows'][$next_row]['ritenuta'] = $rigo['ritenuta'];
        $form['rows'][$next_row]['unimis'] = $rigo['unimis'];
        $form['rows'][$next_row]['prelis'] = number_format($rigo['prelis'], $admin_aziend['decimal_price'], '.', '');
        $form['rows'][$next_row]['sconto'] = $rigo['sconto'];
        $form['rows'][$next_row]['quanti'] = gaz_format_quantity($rigo['quanti'], 0, $admin_aziend['decimal_quantity']);
        $form['rows'][$next_row]['codvat'] = $rigo['codvat'];
        $form['rows'][$next_row]['codric'] = $rigo['codric'];
        $form['rows'][$next_row]['provvigione'] = $rigo['provvigione'];
        $form['rows'][$next_row]['id_mag'] = $rigo['id_mag'];
        $form['rows'][$next_row]['id_rig'] = $rigo['id_rig'];
        $form['rows'][$next_row]['nrow'] = $rigo['nrow'];
        $form['rows'][$next_row]['nrow_linked'] = $rigo['nrow_linked'];
        $form['rows'][$next_row]['annota'] = (isset($articolo['annota'])) ? $articolo['annota']:'';
        $mv = $upd_mm->getStockValue(false, $rigo['codart'], "", $admin_aziend['stock_eval_method']);
        $magval = array_pop($mv);
        $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
        $form['rows'][$next_row]['scorta'] = (isset($articolo['scorta']))?$articolo['scorta']:'';
        $form['rows'][$next_row]['quamag'] = $magval['q_g'];
        $form['rows'][$next_row]['pesosp'] = (isset($articolo['peso_specifico']))?$articolo['peso_specifico']:'';
        $form['rows'][$next_row]['extdoc'] = '';
        $form['rows'][$next_row]['status'] = "UPDATE";
		// recupero il filename dal filesystem e lo sposto sul tmp
		$dh = opendir( DATA_DIR . 'files/' . $admin_aziend['company_id'] );
		while (false !== ($filename = readdir($dh))) {
				$fd = pathinfo($filename);
				$r = explode('_', $fd['filename']);
				if ($r[0] == 'rigbrodoc' && $r[1] == $rigo['id_rig']) {
					/* 	uso id_body_text per mantenere il riferimento riferimento al file del documento esterno
					* 	e riassegno il nome file
					*/
					$form['rows'][$next_row]['extdoc'] = $fd['basename'];
				}
		}
        $next_row++;
    }
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    if (!isset($_GET['tipdoc'])) {
        $form['tipdoc'] = "VPR";
    } else {
        $form['tipdoc'] = $_GET['tipdoc'];
    }
    $form['id_tes'] = "";
    $form['weekday_repeat'] = date("N") - 1;
    $form['gioemi'] = date("d");
    $form['mesemi'] = date("m");
    $form['annemi'] = date("Y");
    $form['giotra'] = date("d");
    $form['mestra'] = date("m");
    $form['anntra'] = date("Y");
    $form['oratra'] = date("H");
    $form['mintra'] = date("i");
    $form['rows'] = array();
    $next_row = 0;
    $form['hidden_req'] = '';
    // inizio rigo di input
    $form['in_descri'] = "";
    $form['in_tiprig'] = 0;
    $form['in_id_doc'] = 0;
    /*   $form['in_artsea'] = $admin_aziend['artsea']; */
    $form['in_codart'] = "";
	$form['in_good_or_service'] = "";
    $form['in_pervat'] = "";
    $form['in_tipiva'] = "";
    $form['in_ritenuta'] = 0;
    $form['in_unimis'] = "";
    $form['in_prelis'] = 0.000;
    $form['shortdescri'] = "";

    /** inizio modifica FP 09/10/2015
     * inizializzo il campo con '#' per indicare che voglio lo sconto standard dell'articolo
     */
    //rimossa    $form['in_sconto'] = 0;
    $form['in_sconto'] = '#';
    /* fine modifica FP */
    $form['in_quanti'] = 0;
    $form['in_codvat'] = 0;
    $form['in_codric'] = substr($admin_aziend['impven'], 0, 3);
    $form['in_provvigione'] = 0;
    $form['in_id_mag'] = 0;
    $form['in_id_rig'] = 0;
    $form['in_nrow'] = 0;
    $form['in_nrow_linked'] = 0;
    $form['in_annota'] = "";
    $form['in_scorta'] = 0;
    $form['in_quamag'] = 0;
    $form['in_pesosp'] = 0;
    $form['in_extdoc'] = 0;
    $form['in_status'] = "INSERT";
    $form['in_codric'] = $admin_aziend['impven'];

    // fine rigo input
    $form['search']['clfoco'] = '';
	$print_total = gaz_dbi_get_row($gTables['company_config'], 'var', 'print_total');
    $form['print_total'] = intval($print_total['val']);
	$delivery_time = gaz_dbi_get_row($gTables['company_config'], 'var', 'delivery_time');
    $form['delivery_time'] = intval($delivery_time['val']);
	$day_of_validity = gaz_dbi_get_row($gTables['company_config'], 'var', 'day_of_validity');
    $form['day_of_validity'] = intval($day_of_validity['val']);
    $form['cosear'] = "";
    if (isset($_GET['seziva'])) {
        $form['seziva'] = intval($_GET['seziva']);
    } else {
      switch ($admin_aziend['fatimm']) {
        case 1:
        case 2:
        case 3:
        $form['seziva'] = $admin_aziend['fatimm'];
        break;
        default:
        $form['seziva'] = 1;
      }
    }
    $form['protoc'] = "";
    $form['numdoc'] = "";
    $form['numfat'] = "";
    $form['datfat'] = "";
    $form['clfoco'] = "";
    $form['pagame'] = "";
    $form['change_pag'] = "";
    $form['banapp'] = "";
    $form['vettor'] = "";
    $form['id_agente'] = 0;
    $form['net_weight'] = 0;
    $form['gross_weight'] = 0;
    $form['units'] = 0;
    $form['volume'] = 0;
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
    $form['expense_vat'] = $admin_aziend['preeminent_vat'];
    $form['stamp'] = 0;
    $form['round_stamp'] = $admin_aziend['round_bol'];
    $form['virtual_taxstamp'] = $admin_aziend['virtual_taxstamp'];
    $form['taxstamp'] = 0;
    $form['cauven'] = 0;
    $form['caucon'] = '';
    $form['caumag'] = 0;
    $form['sconto'] = 0;
    $form['indspe'] = "";
	$ultimoprezzo=''; //info sugli ultimi prezzi
}
require("../../library/include/header.php");
require("./lang." . $admin_aziend['lang'] . ".php");

// INIZIO VIEW
$script_transl = $strScript["admin_broven.php"] + HeadMain(0, array('calendarpopup/CalendarPopup','custom/autocomplete','custom/miojs'));
if ($form['id_tes'] > 0) {
    $title = ucfirst($script_transl[$toDo] . $script_transl[0][$form['tipdoc']]) . " n." . $form['numdoc'];
} else {
    $title = ucfirst($script_transl[$toDo] . $script_transl[0][$form['tipdoc']]);
}
if (isset($admin_aziend['lang'])){
  $price_list_names = gaz_dbi_dyn_query('*', $gTables['company_data'], "ref = '" . $admin_aziend['lang'] . "_artico_pricelist' && var NOT LIKE 'preacq'", "id_ref ASC");
  if ($price_list_names->num_rows == 5){
    $script_transl['listino_value']=array();
    $n=0;
    while ($list_name = gaz_dbi_fetch_array($price_list_names)){
      $n++;
      $script_transl['listino_value'][$n]=$list_name["description"];
    }
  }
}
?>
<script>
var cal = new CalendarPopup();
cal.setReturnFunction("setMultipleValues");
function setMultipleValues(y, m, d) {
    document.broven.anntra.value = y;
    document.broven.mestra.value = LZ(m);
    document.broven.giotra.value = LZ(d);
}
function pulldown_menu(selectName, destField)
{
  var url = document.broven[selectName].options[document.broven[selectName].selectedIndex].value;
  document.broven[destField].value = url;
}
function preStampa() // stampa il dettaglio del preventivo senza salvarlo
{
  var mywindow = window.open('', 'my div', 'height=400,width=600');
  mywindow.document.write('<html><head><title>Stampa</title>');
  mywindow.document.write('</head><body >');
  mywindow.document.write('<h1>CLIENTE: '+$('[name=\"change\"]').val()+'</h1>');
  mywindow.document.write('<table name=lista border=1> ');
  mywindow.document.write($('[name=\"elenco\"]').html());
  mywindow.document.write('</table> ');
  mywindow.document.write('<h2>TOTALE: &#8364; '+$('[name=\"totale\"]').html()+'</h2>');
  mywindow.document.write('</body></html>');
  mywindow.document.close(); // necessary for IE >= 10
  mywindow.focus(); // necessary for IE >= 10
  mywindow.print();
  mywindow.close();
  return true;
}

function printPdf(urlPrintDoc){
  $(function(){
    $('#framePdf').attr('src',urlPrintDoc);
    $('#framePdf').css({'height': '100%'});
    $('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
    $('#closePdf').on( "click", function() {
      $('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
      window.location.href = "<?php echo $form['ritorno']; ?>";
    });
  });
};

$( function() {
	$("#dialog_moverow").dialog({ autoOpen: false });
	$('.dialog_moverow').click(function() {
		var movdescr = $(this).attr("descr");
		var movnrow = $(this).attr('nrow');
    var nr=parseInt(movnrow)-1;
    var movnrow_linked = $('input[name="rows['+nr+'][nrow_linked]"]').val();
    var intmovnrow_linked = parseInt(movnrow_linked);
    var descri_linked = (movnrow_linked==movnrow)?'':' legato al rigo ' + movnrow_linked;
		var maxnrow = $('div#maxnrow').attr('movemax');
		var intmaxnrow = parseInt(maxnrow);
    $('input#moved_nrow').val(movnrow);
    $('input#obj_nrow').val(movnrow);
    $('input#moved_to').val(movnrow);
    $('input#obj_nrow').attr('max',maxnrow);
    $("p#movdescr").html(movdescr);
    $('input#obj_nrow').on( "change", function() {
      var intobj_nrow=parseInt($('input#obj_nrow').val());
      if (intobj_nrow == intmovnrow_linked ){ // se provo ad usare il numero linkato non lo permetto e lo riporto all'originale
        $('input#obj_nrow').val(movnrow);
      } else { // se pro
        $('input#moved_to').val($('input#obj_nrow').val());
      }
    });
    $('input#obj_nrow').on( "keyup", function() {
      var intobj_nrow=parseInt($('input#obj_nrow').val());
      if (intobj_nrow > intmaxnrow ) {
        $('input#obj_nrow').val(maxnrow);
      }
    });
		$( "#dialog_moverow" ).dialog({
      title: 'Spostamento del rigo '+ movnrow + descri_linked,
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
   			close: {
					text:'Non spostare',
					'class':'btn btn-default',
          click:function() {
            $(this).dialog("close");
          }
        },
        space: {
					text:' <-> ',
					'class':' none '
        },
        move: {
					text:'Conferma spostamento',
					'class':'btn btn-warning',
          click:function() {
            var newrow = $('input#moved_to').val();
            var newid = parseInt(newrow)-1;
            if (newrow==movnrow){
              alert('stai spostando un rigo su se stesso');
              //$(this).dialog("close");
            } else {
              $('input[name="last_focus"]').val('row_'+newid);
              $('input[name="hidden_req"]').val('moverow');
              $("#broven").submit();
            }
          }
        }
			}
		});
		$("#dialog_moverow" ).dialog( "open" );
	});
<?php
if ( empty($msg) && !isset($_POST['ins']) && $scroll_input_row == '1' ) { // se ho un errore non scrollo
	if (!empty($_POST['last_focus'])){
		$idlf='#'.$_POST['last_focus'];
		$_POST['last_focus']='';
	} else {
		$idlf="#search_cosear";
	}
	echo '$("html, body").delay(100).animate({scrollTop: $("'.$idlf.'").offset().top-100},200);';
}
?>

});
</script>

<form method="POST" name="broven" id="broven" enctype="multipart/form-data">

<!-- FINESTRE MODALI -->
<div class="modal" id="dialog_moverow" title='Spostamento rigo' style="display:none">
  <p>Rigo da spostare:</p>
  <p class="ui-state-highlight" id="movdescr"></p>
  <p>spostalo sopra al rigo: <input type="number" min="1" max="1" id="obj_nrow" name="obj_nrow" maxlength="3" value="1" /></p>
</div>

<div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
  <div class="col-lg-12">
    <div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
      <div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
    </div>
    <iframe id="framePdf"  style="height: 100%; width: 100%" src="../../library/images/wait_spinner.html"></iframe>
</div>
<?php
$gForm = new venditForm();

function printInputNewRowForm ($form,$trsl,$gForm) {
  $class_conf_row='btn-success';
  $descributton = $trsl['insert'];
  $nurig = count($form['rows'])+1;
  $expsts = explode('UPDROW',$form['in_status']);
  if (isset($expsts[1])){
    $nurig = (int)$expsts[1]+1;
    $class_conf_row = 'btn-warning';
    $descributton = $trsl['update'];
  }
  $descributton .= ' il rigo '.$nurig;
  echo '
	  <input type="hidden" value="' . $form['in_descri'] . '" name="in_descri" />
	  <input type="hidden" value="' . $form['in_pervat'] . '" name="in_pervat" />
	  <input type="hidden" value="' . $form['in_tipiva'] . '" name="in_tipiva" />
	  <input type="hidden" value="' . $form['in_ritenuta'] . '" name="in_ritenuta" />
    <input type="hidden" value="' . $form['in_unimis'] . '" name="in_unimis" />
	  <input type="hidden" value="' . $form['in_prelis'] . '" name="in_prelis" />
	  <input type="hidden" value="' . $form['in_id_mag'] . '" name="in_id_mag" />
    <input type="hidden" value="' . $form['in_id_rig'] . '" name="in_id_rig" />
    <input type="hidden" value="' . $form['in_nrow'] . '" name="in_nrow" />
    <input type="hidden" value="' . $form['in_nrow_linked'] . '" name="in_nrow_linked" />
	  <input type="hidden" value="' . $form['in_id_doc'] . '" name="in_id_doc" />
	  <input type="hidden" value="' . $form['in_annota'] . '" name="in_annota" />
	  <input type="hidden" value="' . $form['in_scorta'] . '" name="in_scorta" />
	  <input type="hidden" value="' . $form['in_quamag'] . '" name="in_quamag" />
	  <input type="hidden" value="' . $form['in_pesosp'] . '" name="in_pesosp" />
	  <input type="hidden" value="' . $form['in_extdoc'] . '" name="in_extdoc" />
	  <input type="hidden" value="' . $form['in_status'] . '" name="in_status" />
	  <input type="hidden" value="' . $form['hidden_req'] . '" name="hidden_req" />
	  <div class="table-responsive">
    <table class="Tlarge table input-area">
	  <tr>
			<td>' . $trsl[17] . ':';
  $gForm->selTypeRow('in_tiprig', $form['in_tiprig']);
  echo $trsl[15] . ':&nbsp;';
  $select_artico = new selectartico("in_codart");
  $select_artico->addSelected($form['in_codart']);
  //$select_artico->output($form['cosear'], $form['in_artsea']);
  $select_artico->output($form['cosear']);
?>
	</td>
	<td><?php echo $trsl[16] ?>:&nbsp;<input type="text" value="<?php echo $form['in_quanti'] ?>" maxlength=11 size=8 name="in_quanti" tabindex="5" accesskey="q" /></td>
	<td align="right">
<?php
if (substr($form['in_status'], 0, 6) != "UPDROW") { //se non è un rigo da modificare
?>
			<button type="submit" class="btn btn-info btn-sm" name="in_submit_desc" title="Aggiungi rigo Descrittivo"><i class="glyphicon glyphicon-pencil"></i></button>
			<button type="submit" class="btn btn-info btn-sm" name="in_submit_text" title="Aggiungi rigo Testo"><i class="glyphicon glyphicon-list"></i></button>
<?php
}
?>
			</td>
		</tr>
		<tr>
			<td>
<?php
  echo $trsl[18] . ": ";
  $select_codric = new selectconven("in_codric");
  $select_codric->addSelected($form['in_codric']);
  $select_codric->output(substr($form['in_codric'], 0, 1));
  echo '			%' . $trsl[24] . ': <input type="text" value="' . $form['in_sconto'] . '" maxlength=4 size=3 name="in_sconto">
             %' . $trsl[56] . ': <input type="text" value="' . $form['in_provvigione'] . '" maxlength=6 size=3 name="in_provvigione">'
   . ' %' . $trsl['ritenuta'] . ': <input type="text" value="' . $form['in_ritenuta'] . '" maxlength=6 size=3 name="in_ritenuta">
            </td>
          <td>' . $trsl['vat_constrain'];
  $select_in_codvat = new selectaliiva("in_codvat");
  $select_in_codvat->addSelected($form['in_codvat']);
  $select_in_codvat->output();
?>
</td>
<td>
  <button type="submit" class="btn <?php echo $class_conf_row; ?>" name="in_submit" tabindex="6"><?php echo $descributton ?>
    <i class="glyphicon glyphicon-ok"></i>
  </button>
</td>
</tr>
</table>
</div>
<?php
// FINE FUNZIONE STAMPA RIGO DI INPUT
}

echo '	<input type="hidden" name="' . ucfirst($toDo) . '" value="" />
    <input type="hidden" id="moved_nrow" name="moved_nrow" value=""/>
    <input type="hidden" id="moved_to" name="moved_to" value=""/>
		<input type="hidden" value="' . $form['id_tes'] . '" name="id_tes" />
		<input type="hidden" value="' . $form['indspe'] . '" name="indspe" />
		<input type="hidden" value="' . $form['tipdoc'] . '" name="tipdoc" />
		<input type="hidden" value="' . $form['ritorno'] . '" name="ritorno" />
		<input type="hidden" value="' . $form['change_pag'] . '" name="change_pag" />
		<input type="hidden" value="' . $form['protoc'] . '" name="protoc" />
		<input type="hidden" value="' . $form['numdoc'] . '" name="numdoc" />
		<input type="hidden" value="' . $form['numfat'] . '" name="numfat" />
		<input type="hidden" value="' . $form['datfat'] . '" name="datfat" />
		<input type="hidden" value="' . (isset($_POST['last_focus']) ? $_POST['last_focus'] : "") . '" name="last_focus" />
		<div align="center" class="FacetFormHeaderFont">' . $title . '  a :';
$select_cliente = new selectPartner('clfoco');
$select_cliente->selectDocPartner('clfoco', $form['clfoco'], $form['search']['clfoco'], 'clfoco', $script_transl['mesg'], $admin_aziend['mascli']);
echo '	</div><div class="table-responsive">
		<table class="Tlarge table table-striped table-bordered table-condensed">
			<tr>
				<td class="FacetFieldCaptionTD">' . $script_transl[4] . '</td>
				<td class="FacetDataTD">
					<select name="seziva" class="FacetSelect">';
for ($counter = 1; $counter <= 5; $counter++) {
    $selected = "";
    if ($form['seziva'] == $counter) {
        $selected = ' selected=""';
    }
    echo '				<option value="' . $counter . '"' . $selected . '>' . $counter . '</option>';
}
echo '				</select>
				</td>';
if (!empty($msg)) {
    $message = "";
    $rsmsg = array_slice(explode('+', chop($msg)), 0, -1);
    foreach ($rsmsg as $v) {
        $message .= $script_transl['error'] . "! -> ";
        $rsval = explode('-', chop($v));
        foreach ($rsval as $valmsg) {
            $message .= $script_transl[$valmsg] . " ";
        }
        $message .= "<br />";
    }
    echo '			<td colspan="2" class="FacetDataTDred">' . $message . '</td>';
} else {
    echo '			<td class="FacetFieldCaptionTD">' . $script_transl[5] . '</td>
   					<td class="FacetDataTD">' . $form['indspe'] . '<br /></td>';
}
echo '			<td class="FacetFieldCaptionTD">' . $script_transl[6] . '</td>
				<td class="FacetDataTD">';
if ($form['tipdoc'] == 'VOG') {
    echo "<input name='gioemi' type='hidden' value=" . $form['gioemi'] . ">";
    echo "<input name='mesemi' type='hidden' value=" . $form['mesemi'] . ">";
    echo "<input name='annemi' type='hidden' value=" . $form['annemi'] . ">";

    echo '<select name="weekday_repeat" class="FacetSelect">';
    for ($t = 0; $t != 7; $t++) {
        if ($t == $form['weekday_repeat'])
            $selected = " selected";
        else
            $selected = "";
        echo "<option value='" . $t . "' " . $selected . ">" . getDayNameFromDayNumber($t) . "</option>";
    }
    echo '</select>';
} else {
    echo "<input name='weekday_repeat' type='hidden' value=" . $form['weekday_repeat'] . ">";

    echo '<select name="gioemi" class="FacetSelect">';
    for ($counter = 1; $counter <= 31; $counter++) {
        $selected = "";
        if ($counter == $form['gioemi']) {
            $selected = ' selected=""';
        }
        echo '					<option value="' . $counter . '"' . $selected . '>' . $counter . '</option>';
    }
    echo '				</select>';
    // select del mese
    echo '				<select name="mesemi" class="FacetSelect">';
    $gazTimeFormatter->setPattern('MMMM');
    for ($counter = 1; $counter <= 12; $counter++) {
        $selected = "";
        if ($counter == $form['mesemi']) {
            $selected = ' selected=""';
        }
        $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
        echo '					<option value="' . $counter . '"' . $selected . '>' . $nome_mese . '</option>';
    }
    echo '				</select>';
    // select del anno
    echo '				<select name="annemi" class="FacetSelect" onchange="this.form.submit()">';
    for ($counter = $form['annemi'] - 10; $counter <= $form['annemi'] + 10; $counter++) {
        $selected = "";
        if ($counter == $form['annemi']) {
            $selected = ' selected=""';
        }
        echo '					<option value="' . $counter . '"' . $selected . '>' . $counter . '</option>';
    }
    echo '				</select>';
}
echo '
                </td>
			</tr>
			<tr>
				<td class="FacetFieldCaptionTD">' . $script_transl[7] . '</td>
				<td class="FacetDataTD">';
		$gForm->variousSelect('listin', $script_transl['listino_value'], $form['listin'], 'FacetSelect', false);

	echo '</td>
				<td class="FacetFieldCaptionTD">' . $script_transl[8] . '</td>
				<td class="FacetDataTD">';
$select_pagame = new selectpagame("pagame");
$select_pagame->addSelected($form['pagame']);
$select_pagame->output();
echo '			</td>
				<td class="FacetFieldCaptionTD">' . $script_transl[9] . '</td>
				<td class="FacetDataTD">';
$select_banapp = new selectbanapp("banapp");
$select_banapp->addSelected($form['banapp']);
$select_banapp->output();
echo '			</td>
			</tr>
			<tr>
				<td class="FacetFieldCaptionTD">' . $script_transl['print_total'] . '</td>
				<td class="FacetDataTD">';
$gForm->variousSelect('print_total', $script_transl['print_total_value'], $form['print_total']);
echo '			</td>
				<td class="FacetFieldCaptionTD" title="' . $script_transl['day_of_validity'] . '">' . $script_transl['day_of_validity'] . '</td>
				<td class="FacetDataTD" title="' . $script_transl['day_of_validity'] . '">
					<input type="text" value="' . $form['day_of_validity'] . '" name="day_of_validity" maxlength=3 size=3 />
				</td>
				<td class="FacetFieldCaptionTD" title="' . $script_transl['delivery_time'] . '">' . $script_transl['delivery_time'] . '</td>
				<td class="FacetDataTD" title="' . $script_transl['delivery_time'] . '">
					<input type="text" value="' . $form['delivery_time'] . '" name="delivery_time" maxlength=3 size=3 />
				</td>
			</tr>
			<tr>
			<td class="FacetFieldCaptionTD" title="' . $script_transl['speban_title'] . '">' . $script_transl['speban'] . '</td>
			<td class="FacetDataTD" title="' . $script_transl['speban_title'] . '">
				<input type="text" value="' . $form['speban'] . '" name="speban" maxlength=6 size=3 onchange="this.form.submit()" /> x ' . $form['numrat'] . '
			</td>
			<td class="FacetFieldCaptionTD">' . $script_transl[10] . '</td>';
if ($form['id_des_same_company'] > 0) { //  è una destinazione legata all'anagrafica
        echo "<td class=\"FacetDataTD\">\n";
        $gForm->selectFromDB('destina', 'id_des_same_company', 'codice', $form['id_des_same_company'], 'codice', true, '-', 'unita_locale1', '', 'FacetSelect', null, '', "id_anagra = '" . $cliente['id_anagra'] . "'");
        echo "	<input type=\"hidden\" name=\"id_des\" value=\"" . $form['id_des'] . "\">
                <input type=\"hidden\" name=\"destin\" value=\"" . $form['destin'] . "\" /></td>\n";
    } elseif ($form['id_des'] > 0) { // la destinazione è un'altra anagrafica
        echo "<td class=\"FacetDataTD\">\n";
        $select_id_des = new selectPartner('id_des');
        $select_id_des->selectDocPartner('id_des', 'id_' . $form['id_des'], $form['search']['id_des'], 'id_des', $script_transl['mesg'], $admin_aziend['mascli']);
        echo "			<input type=\"hidden\" name=\"id_des_same_company\" value=\"" . $form['id_des_same_company'] . "\">
                                <input type=\"hidden\" name=\"destin\" value=\"" . $form['destin'] . "\" />
						</td>\n";
    } else {
        echo "			<td class=\"FacetDataTD\">";
        echo "				<textarea rows=\"2\" name=\"destin\" class=\"FacetInput\" style=\"width:100%;\">" . $form["destin"] . "</textarea>
						</td>
						<input type=\"hidden\" name=\"id_des_same_company\" value=\"" . $form['id_des_same_company'] . "\">
						<input type=\"hidden\" name=\"id_des\" value=\"" . $form['id_des'] . "\">
						<input type=\"hidden\" name=\"search[id_des]\" value=\"" . $form['search']['id_des'] . "\"></td>\n";
    }
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['id_agente'] . "</td>";
echo "<td  class=\"FacetDataTD\">\n";
$select_agente = new selectAgente("id_agente");
$select_agente->addSelected($form["id_agente"]);
$select_agente->output();
echo '		</td>
		</tr>
	  </table></div>
    <div class="FacetSeparatorTD" align="center"><b>' . $script_transl[1] . '</b></div>';

if ( $scroll_input_row == 9 ) { // ho scelto di posizionare il rigo di input PRIMA della tabella dei righi
  echo printInputNewRowForm($form,$script_transl,$gForm);
}

echo '<div class="table-responsive">
	  <table name="elenco" class="Tlarge table table-striped table-bordered table-condensed">
		<thead>
			<tr>
      <td>Rigo</td>
				<td class="FacetFieldCaptionTD">' . $script_transl[20] . '</td>
				<td class="FacetFieldCaptionTD" >' . $script_transl[21] . '</td>
				<td class="FacetFieldCaptionTD">' . $script_transl[22] . '</td>
                <td class="FacetFieldCaptionTD">' . $script_transl[16] . '</td>
                <td class="FacetFieldCaptionTD">' . $script_transl[23] . '</td>
				<td class="FacetFieldCaptionTD">%' . substr($script_transl[24], 0, 2) . '.</td>
				<td class="FacetFieldCaptionTD">%' . substr($script_transl[56], 0, 5) . '.</td>
				<td class="FacetFieldCaptionTD text-right">' . $script_transl[25] . '</td>
				<td class="FacetFieldCaptionTD">' . $script_transl[19] . '</td>
				<td class="FacetFieldCaptionTD">' . $script_transl[18] . '</td>
				<td class="FacetFieldCaptionTD"></td>
			</tr>
		</thead>
		<tbody>';
$totimp_body = 0.00;
$totivafat = 0.00;
$totimpfat = 0.00;
$castle = array();
$rit = 0;
$carry = 0;
$last_row = array();
$vp = gaz_dbi_get_row($gTables['company_config'], 'var', 'vat_price')['val'];
$nr=0;
foreach ($form['rows'] as $k => $v) {
    $nr=$k+1;
    $v['provvigione']=(isset($v['provvigione']))?$v['provvigione']:0;
    //creo il castelletto IVA
    $imprig = 0;
    if ($v['tiprig'] <= 1) {
        $imprig = CalcolaImportoRigo($v['quanti'], $v['prelis'], $v['sconto']);
        $v_for_castle = CalcolaImportoRigo($v['quanti'], $v['prelis'], array($v['sconto'], $form['sconto']));
        if ($v['tiprig'] == 1) {//ma se del tipo forfait
            $imprig = CalcolaImportoRigo(1, $v['prelis'], 0);
            $v_for_castle = CalcolaImportoRigo(1, $v['prelis'], $form['sconto']);
        }
        if (!isset($castle[$v['codvat']])) {
            $castle[$v['codvat']]['impcast'] = 0.00;
        }
        $totimp_body += $imprig;
        $castle[$v['codvat']]['impcast'] += $v_for_castle;
        $rit += round($imprig * $v['ritenuta'] / 100, 2);
    } elseif ($v['tiprig'] == 3) {
        $carry += $v['prelis'];
    }
    $descrizione = htmlentities($v['descri'], ENT_QUOTES);
    ;

    echo "<input type=\"hidden\" value=\"" . $v['codart'] . "\" name=\"rows[$k][codart]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['good_or_service']))?$v['good_or_service']:1) . "\" name=\"rows[$k][good_or_service]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['status'] . "\" name=\"rows[$k][status]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['tiprig'] . "\" name=\"rows[$k][tiprig]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['id_doc']))?$v['id_doc']:0) . "\" name=\"rows[$k][id_doc]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['codvat'] . "\" name=\"rows[$k][codvat]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['pervat'] . "\" name=\"rows[$k][pervat]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['tipiva'] . "\" name=\"rows[$k][tipiva]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['ritenuta'] . "\" name=\"rows[$k][ritenuta]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['codric'] . "\" name=\"rows[$k][codric]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['id_mag'] . "\" name=\"rows[$k][id_mag]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['id_rig'] . "\" name=\"rows[$k][id_rig]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['nrow'] . "\" name=\"rows[$k][nrow]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['nrow_linked'] . "\" name=\"rows[$k][nrow_linked]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['annota'] . "\" name=\"rows[$k][annota]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['scorta'] . "\" name=\"rows[$k][scorta]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['quamag'] . "\" name=\"rows[$k][quamag]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['pesosp'] . "\" name=\"rows[$k][pesosp]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['extdoc']))?$v['extdoc']:'') . "\" name=\"rows[$k][extdoc]\">\n";
    //stampo i rows in modo diverso a secondo del tipo
    $btngly=($v['nrow_linked'] < $v['nrow'])?'link':'resize-vertical';
    $btntit=($v['nrow_linked'] < $v['nrow'])?'Legato al precedente':'Sposta rigo';
    $btndia=($v['nrow_linked'] < $v['nrow'])?'':'dialog_moverow';
    echo "<tr>";
    switch ($v['tiprig']) {
      case "0":
        if ($v['good_or_service']<>1){
          if ($v['quamag'] < 0.00001 && $admin_aziend['conmag']==2) { // se gestisco la contabilità di magazzino controllo presenza articolo
            $btn_class = 'btn-danger';
            $btn_title = ' ARTICOLO NON DISPONIBILE';
          } elseif ($v['quamag'] <= $v['scorta'] && $admin_aziend['conmag']==2) { // se gestisco la contabilità di magazzino controllo il sottoscorta
            $btn_class = 'btn-warning';
            $btn_title = ' Articolo sottoscorta: disponibili '.gaz_format_quantity($v['quamag'], 1, $admin_aziend['decimal_quantity']).'/'.floatval($v['scorta']);
          } else {
            $btn_class = 'btn-success';
            $btn_title = " ".gaz_format_quantity($v['quamag'], 1, $admin_aziend['decimal_quantity']).' '.$v['unimis'].' disponibili';
          }
        } else {
          $btn_class = 'btn-info';
          $btn_title = " Senza magazzino";
        }
        $peso = 0;
        if (is_numeric($v['pesosp']) && $v['pesosp'] <> 0) {
          $peso = gaz_format_number($v['quanti'] / $v['pesosp']);
        }
        echo '<td><a nrow="'.$nr.'" id="row_'.$k.'" class="btn btn-default btn-xs '.$btndia.'" title="'.$btntit.'" descr="<b>'.$v['codart'].'</b>  '.$v['descri'].'"><i class="glyphicon glyphicon-'.$btngly.'"></i></a> '.$nr.'</td>';
        echo'<td title="' . $script_transl['update'] . $script_transl['thisrow'] . '!' . $btn_title . '">
					<button name="upd_row[' . $k . ']" class="btn btn-xs ' . $btn_class . ' btn-block" type="submit">
						<i class="glyphicon glyphicon-refresh"></i>&nbsp;' . $v['codart'] . '
					</button>
			 	</td>
				<td>
		 			<input class="gazie-tooltip" data-type="product-thumb" data-id="' . $v["codart"] . '" data-title="' . $v['annota'] . '" type="text" name="rows[' . $k . '][descri]" value="' . $descrizione . '" maxlength=100 size=100/>
			   	</td>
			    <td>
					<input class="gazie-tooltip" data-type="weight" data-id="' . $peso . '" data-title="' . $script_transl['weight'] . '" type="text" name="rows[' . $k . '][unimis]" value="' . $v['unimis'] . '" maxlength="3" size=3/>
				</td>
				<td>
					<input class="gazie-tooltip" data-type="weight" data-id="' . $peso . '" data-title="' . $script_transl['weight'] . '" type="text" name="rows[' . $k . '][quanti]" value="' . $v['quanti'] . '" align="right" maxlength=11 size=6 id="righi_' . $k . '_quanti" onchange="document.broven.last_focus.value=\'righi_' . $k . '_prelis\'; this.form.hidden_req.value=\'ROW\'; this.form.submit();" />
                </td>';
            echo "<td><input type=\"text\" name=\"rows[$k][prelis]\" value=\"" . $v['prelis'] . "\" align=\"right\" maxlength=11 size=6 ";
			if ($vp>0) { // solo se scelto in configurazione avanzata azienda si vedrà il dialog per mettere il prezzo iva compresa
				echo ' onclick="vatPrice(\''.$k.'\',\''.$v['pervat'].'\');"';
			}
            echo " id=\"righi_" . $k . "_prelis\" onchange=\"document.broven.last_focus.value='righi_" . $k . "_sconto'; this.form.submit()\" /></td>\n";
            echo "<td><input type=\"text\" name=\"rows[$k][sconto]\" value=\"" . $v['sconto'] . "\" maxlength=6 size=3 id=\"righi_" . $k . "_sconto\" onchange=\"document.broven.last_focus.value=this.id; this.form.submit()\" /></td>\n";
            echo "<td><input type=\"text\" name=\"rows[$k][provvigione]\" value=\"" . $v['provvigione'] . "\" maxlength=6 size=3 /></td>\n";
            echo "<td class=\"text-right\">" . gaz_format_number($imprig) . "</td>\n";
            echo "<td class=\"text-right\">" . $v['pervat'] . "%</td>\n";
            echo "<td class=\"text-right codricTooltip\" title=\"Contropartita\">" . $v['codric'] . "</td>\n";

            $last_row[] = array_unshift($last_row, '<strong>' . $v['codart'] . '</strong>, ' . $v['descri'] . ', ' . $v['quanti'] . $v['unimis'] . ', <strong>' . $script_transl[23] . '</strong>: ' . gaz_format_number($v['prelis']) . ', %<strong>' . substr($script_transl[24], 0, 2) . '</strong>: ' . gaz_format_number($v['sconto']) . ', <strong>' . $script_transl[25] . '</strong>: ' . gaz_format_number($imprig) . ', <strong>' . $script_transl[19] . '</strong>: ' . $v['pervat'] . '%, <strong>' . $script_transl[18] . '</strong>: ' . $v['codric']);
            break;
        case "1":
          echo '<td><a nrow="'.$nr.'" id="row_'.$k.'" class="btn btn-default btn-xs '.$btndia.'" title="'.$btntit.'" descr="<b>'.$script_transl['typerow'][$v['tiprig']].'</b>  '.$v['descri'].'"><i class="glyphicon glyphicon-'.$btngly.'"></i></a> '.$nr.'</td>'
              .'<td title="' . $script_transl['update'] . $script_transl['thisrow'] . '!">
              <input class="FacetDataTDsmall" type="submit" name="upd_row[' . $k . ']" value="' . $script_transl['typerow'][$v['tiprig']] . '" />
					</td>
			  		<td>
		 				<input type="text" name="rows[' . $k . '][descri]" value="' . $descrizione . '" maxlength=100 size=100 />
					</td>

					<td>
						<input type="hidden" name="rows[' . $k . '][unimis]" value="" />
                    </td>
					<td>
                        <input type="hidden" name="rows[' . $k . '][quanti]" value="" />
                    </td>
					<td>
						<input type="hidden" name="rows[' . $k . '][sconto]" value="" />
					</td>
					<td>
						<input type="hidden" name="rows[' . $k . '][provvigione]" value="" />
					</td>
					<td></td>
					<td class="text-right">
						<input style="text-align:right" type="text" name="rows[' . $k . '][prelis]" value="' . number_format($v['prelis'], 2, '.', '') . '" align="right" maxlength=11 size=6';
						if ($vp>0) { // solo se scelto in configurazione avanzata azienda si vedrà il dialog per mettere il prezzo iva compresa
							echo ' onclick="vatPrice(\''.$k.'\',\''.$v['pervat'].'\');"';
						}
						echo ' id="righi_' . $k . '_prelis" onchange="document.broven.last_focus.value=this.id; this.form.submit()" />
					</td>
					<td class="text-right">' . $v['pervat'] . '%</td>
					<td class="text-right codricTooltip" title="Contropartita">' . $v['codric'] . '</td>';
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']].' ( digitare testo  e importo)');
            break;
        case "2": // descrittivo
          echo '<td><a nrow="'.$nr.'" id="row_'.$k.'" class="btn btn-default btn-xs dialog_moverow" title="'.$btntit.'" descr="<b>'. $script_transl['typerow'][$v['tiprig']].'</b>  '.$v['descri'].'"><i class="glyphicon glyphicon-'.$btngly.'"></i></a> '.$nr.'</td>';
          echo "<td title=\"" . $script_transl['update'] . $script_transl['thisrow'] . "!\">
                                <input class=\"FacetDataTDsmall\" type=\"submit\" name=\"upd_row[$k]\" value=\"" . $script_transl['typerow'][$v['tiprig']] . "\" />
			</td>
			<td>
				<input type=\"text\" name=\"rows[$k][descri]\" value=\"$descrizione\" maxlength=100 size=100 />
			</td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][unimis]\" value=\"\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][quanti]\" value=\"\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][prelis]\" value=\"\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][sconto]\" value=\"\" /></td>\n";
            echo "<td></td>\n";
            echo "<td></td>\n";
            echo "<td></td>\n";
            echo "<td></td>\n";
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']].' ( digitare il testo)');
            break;
        case "3":
       			echo '<td><a nrow="'.$nr.'" id="row_'.$k.'" class="btn btn-default btn-xs '.$btndia.'" title="'.$btntit.'" descr="<b>'. $script_transl['typerow'][$v['tiprig']].'</b>  '.$v['descri'].'"><i class="glyphicon glyphicon-'.$btngly.'"></i></a> '.$nr.'</td>';

            echo "	<td title=\"" . $script_transl['update'] . $script_transl['thisrow'] . "\">
              		<input class=\"FacetDataTDsmall\" type=\"submit\" name=\"upd_row[$k]\" value=\"" . $script_transl['typerow'][$v['tiprig']] . "\" />
				</td>
			  	<td>
		 			<input type=\"text\" name=\"rows[$k][descri]\" value=\"$descrizione\" maxlength=100 size=100 >
				</td>
				<td><input type=\"hidden\" name=\"rows[$k][unimis]\" value=\"\" /></td>
                <td><input type=\"hidden\" name=\"rows[$k][quanti]\" value=\"\" /></td>
				<td><input type=\"hidden\" name=\"rows[$k][sconto]\" value=\"\" /></td>
				<td></td>
				<td></td>
				<td class=\"text-right\"><input type=\"text\" name=\"rows[$k][prelis]\" value=\"" . $v['prelis'] . "\" align=\"right\" maxlength=11 size=8  /></td>
				<td></td>
				<td></td>\n";
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']].' ( digitare descrizione e importo variazione totale da pagare)');
            break;
        case "6":
        case "7":
        case "8":
          echo '<td><a nrow="'.$nr.'" id="row_'.$k.'" class="btn btn-default btn-xs '.$btndia.'" title="'.$btntit.'" descr="<b>'. $script_transl['typerow'][$v['tiprig']].'</b>  '.$v['descri'].'"><i class="glyphicon glyphicon-'.$btngly.'"></i></a> '.(($btndia=='')?'':$nr).'</td>';
					echo '<td>
		 			<input class="FacetDataTDsmall" type="submit" name="" value="' . $script_transl['typerow'][$v['tiprig']] . '"  disabled />
				</td>
				<td colspan="9">
					<textarea name="row_'.$k.'" class="mceClass" style="width:100%;height:100px;">'.$form["row_$k"].'</textarea>
				</td>
				<input type="hidden" value="" name="rows[' . $k . '][descri]" />
				<input type="hidden" value="" name="rows[' . $k . '][unimis]" />
                <input type="hidden" value="" name="rows[' . $k . '][quanti]" />
				<input type="hidden" value="" name="rows[' . $k . '][prelis]" />
				<input type="hidden" value="" name="rows[' . $k . '][sconto]" />
				<input type="hidden" value="" name="rows[' . $k . '][provvigione]" />';
          if ($v['nrow_linked'] == $v['nrow']) { // lo notifico solo se non linkato
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']]);
          }
            break;
        case "11": // CIG fattura PA
        case "12": // CUP fattura PA
        case "13": // ID documento fattura PA
        case "14": // Data documento
        case "15": // Num.Linea documento
        case "16": // Codice Commessa/Convenzione
        case "17": // Riferimento Amministrazione
        case "21": // Causale
        case "25": // Stato avanzamento
        case "26": // Lettera intento
        case "31": // Dati Veicoli
          echo '<td><a nrow="'.$nr.'" id="row_'.$k.'" class="btn btn-default btn-xs '.$btndia.'" title="'.$btntit.'" descr="<b>'. $script_transl['typerow'][$v['tiprig']].'</b>  '.$v['descri'].'"><i class="glyphicon glyphicon-'.$btngly.'"></i></a> '.$nr.'</td>';
          echo "<td title=\"" . $script_transl['update'] . $script_transl['thisrow'] . "!\">
              			<input class=\"btn btn-xs btn-success btn-block\" type=\"submit\" name=\"upd_row[$k]\" value=\"" . $script_transl['typerow'][$v['tiprig']] . "\" />
					</td>
					<td>
						<input type=\"text\"   name=\"rows[$k][descri]\" value=\"$descrizione\" maxlength=100 size=100  />
					</td>
					<td><input type=\"hidden\" name=\"rows[$k][unimis]\" value=\"\" /></td>
                    <td><input type=\"hidden\" name=\"rows[$k][quanti]\" value=\"\" /></td>
					<td><input type=\"hidden\" name=\"rows[$k][prelis]\" value=\"\" /></td>
					<td><input type=\"hidden\" name=\"rows[$k][sconto]\" value=\"\" /></td>
					<td><input type=\"hidden\" name=\"rows[$k][provvigione]\" value=\"\" /></td>
					<td></td>
					<td></td>
					<td></td>\n";
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']].' ( digitare i dati)');
            break;
        case "50":
          echo '<td><a nrow="'.$nr.'" id="row_'.$k.'" class="btn btn-default btn-xs '.$btndia.'" title="'.$btntit.'" descr="<b>Documento esterno</b>  '.$v['descri'].'"><i class="glyphicon glyphicon-'.$btngly.'"></i></a> '.$nr.'</td>';
          echo "<td title=\"" . $script_transl['update'] . $script_transl['thisrow'] . "\"><input class=\"FacetDataTDsmall\" type=\"submit\" name=\"upd_row[{$k}]\" value=\"* documento allegato *\" />\n";
                if (empty($form['rows'][$k]['extdoc'])) {
                    echo '<div><button class="btn btn-xs btn-danger" type="image" data-toggle="collapse" href="#extdoc_dialog' . $k . '">'
                    . $script_transl['insert'] . ' documento esterno <i class="glyphicon glyphicon-tag"></i>'
                    . '</button></div>';
                } else {
                    echo '<div>documento esterno:<button class="btn btn-xs btn-success" type="image" data-toggle="collapse" href="#extdoc_dialog' . $k . '">'
                    . $form['rows'][$k]['extdoc'] . ' <i class="glyphicon glyphicon-tag"></i>'
                    . '</button></div>';
                }
				echo '<div id="extdoc_dialog' . $k . '" class="collapse" >
                        <div class="form-group">
                          <div>';

                echo '<input type="file" onchange="this.form.submit();" name="docfile_' . $k . '">
                            <label>File: </label><input type="text" name="rows[' . $k . '][extdoc]" value="' . $form['rows'][$k]['extdoc'] . '" >
			</div>
		     </div>
              </div>' . "</td>\n";
            echo '
			   	<td>
		 			<input class="gazie-tooltip" data-type="product-thumb" data-id="' . $v["codart"] . '" data-title="' . $v['annota'] . '" type="text" name="rows[' . $k . '][descri]" value="' . $descrizione . '" maxlength=100 size=100 />
			   	</td>
			    <td>
					<input class="gazie-tooltip" data-type="weight" data-id="' . $peso . '" data-title="' . $script_transl['weight'] . '" type="text" name="rows[' . $k . '][unimis]" value="' . $v['unimis'] . '" maxlength=3 size=3 />
				</td>
				<td>
					<input class="gazie-tooltip" data-type="weight" data-id="' . $peso . '" data-title="' . $script_transl['weight'] . '" type="text" name="rows[' . $k . '][quanti]" value="' . $v['quanti'] . '" align="right" maxlength=11 size=6 id="righi_' . $k . '_quanti" onchange="document.broven.last_focus.value=\'righi_' . $k . '_prelis\'; this.form.hidden_req.value=\'ROW\'; this.form.submit();" />
                </td>';
            echo "<td><input type=\"text\" name=\"rows[$k][prelis]\" value=\"" . $v['prelis'] . "\" align=\"right\" maxlength=11 size=6 ";
			if ($vp>0) { // solo se scelto in configurazione avanzata azienda si vedrà il dialog per mettere il prezzo iva compresa
				echo ' onclick="vatPrice(\''.$k.'\',\''.$v['pervat'].'\');"';
			}
            echo " id=\"righi_" . $k . "_prelis\" onchange=\"document.broven.last_focus.value='righi_" . $k . "_sconto'; this.form.submit()\" /></td>\n";
            echo "<td><input type=\"text\" name=\"rows[$k][sconto]\" value=\"" . $v['sconto'] . "\" maxlength=6 size=3  id=\"righi_" . $k . "_sconto\" onchange=\"document.broven.last_focus.value=this.id; this.form.submit()\" /></td>\n";
            echo "<td><input type=\"text\" name=\"rows[$k][provvigione]\" value=\"" . $v['provvigione'] . "\" maxlength=6 size=3  /></td>\n";
            echo "<td class=\"text-right\">" . gaz_format_number($imprig) . "</td>\n";
            echo "<td class=\"text-right\">" . $v['pervat'] . "%</td>\n";
            echo "<td class=\"text-right codricTooltip\" title=\"Contropartita\">" . $v['codric'] . "</td>\n";

            $last_row[] = array_unshift($last_row, '<strong>' . $v['codart'] . '</strong>, ' . $v['descri'] . ', ' . $v['quanti'] . $v['unimis'] . ', <strong>' . $script_transl[23] . '</strong>: ' . gaz_format_number($v['prelis']) . ', %<strong>' . substr($script_transl[24], 0, 2) . '</strong>: ' . gaz_format_number($v['sconto']) . ', <strong>' . $script_transl[25] . '</strong>: ' . gaz_format_number($imprig) . ', <strong>' . $script_transl[19] . '</strong>: ' . $v['pervat'] . '%, <strong>' . $script_transl[18] . '</strong>: ' . $v['codric']);
            break;
        case "51":
			echo '<td><a nrow="'.$nr.'" id="row_'.$k.'" class="btn btn-default btn-xs '.$btndia.'" title="'.$btntit.'" descr="<b>'.$v['codart'].'</b>  '.$v['descri'].'"><i class="glyphicon glyphicon-'.$btngly.'"></i></a> '.$nr.'</td>';
            echo "<td title=\"" . $script_transl['update'] . $script_transl['thisrow'] . "\"><input class=\"FacetDataTDsmall\" type=\"submit\" name=\"upd_row[{$k}]\" value=\"* documento allegato *\" />\n";
                if (empty($form['rows'][$k]['extdoc'])) {
                    echo '<div><button class="btn btn-xs btn-danger" type="image" data-toggle="collapse" href="#extdoc_dialog' . $k . '">'
                    . $script_transl['insert'] . ' documento esterno <i class="glyphicon glyphicon-tag"></i>'
                    . '</button></div>';
                } else {
                    echo '<div>documento esterno:<button class="btn btn-xs btn-success" type="image" data-toggle="collapse" href="#extdoc_dialog' . $k . '">'
                    . $form['rows'][$k]['extdoc'] . ' <i class="glyphicon glyphicon-tag"></i>'
                    . '</button></div>';
                }
				echo '<div id="extdoc_dialog' . $k . '" class="collapse" >
                        <div class="form-group">
                          <div>';

                echo '<input type="file" onchange="this.form.submit();" name="docfile_' . $k . '">
                            <label>File: </label><input type="text" name="rows[' . $k . '][extdoc]" value="' . $form['rows'][$k]['extdoc'] . '" >
			</div>
		     </div>
              </div>' . "</td>\n";
            echo "<td><input type=\"text\" name=\"rows[$k][descri]\" value=\"$descrizione\" maxlength=100 size=100 /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][unimis]\" value=\"\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][quanti]\" value=\"\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][prelis]\" value=\"\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][sconto]\" value=\"\" /></td>\n";
            echo "<td></td>\n";
            echo "<td></td>\n";
            echo "<td></td>\n";
            echo "<td></td>\n";
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']].' ( digitare il testo)');
            break;
        case "210":  // serve per gli articoli composti contattare andrea
            if ( $show_artico_composit['val']=="1" && $tipo_composti['val']=="KIT") {
                if ($v['scorta'] < 0) {
                    //$scorta_col = 'FacetDataTDsmallRed';
                    $btn_class = 'btn-danger';
                } else {
                    //$scorta_col = 'FacetDataTDsmall';
                    $btn_class = 'btn-default';
                }
                echo '	<td></td>
                                    <td title="' . $script_transl['update'] . $script_transl['thisrow'] . '!' . $btn_title . '">
                        <button name="upd_row[' . $k . ']" class="btn btn-xs ' . $btn_class . ' btn-block" type="submit">
                            <i class="glyphicon glyphicon-refresh"></i>&nbsp;' . $v['codart'] . '
                        </button>
                    </td>
                    <td>
                        <input class="gazie-tooltip" data-type="product-thumb" data-id="' . $v["codart"] . '" data-title="' . $v['annota'] . '" type="text" name="rows[' . $k . '][descri]" value="' . $descrizione . '" maxlength=100 size=100 />
                    </td>
                    <td>
                        <input class="gazie-tooltip" data-type="weight" data-id="' . $peso . '" data-title="' . $script_transl['weight'] . '" type="text" name="rows[' . $k . '][unimis]" value="' . $v['unimis'] . '" maxlength=3 size=3 />
                    </td>
                    <td>
                        <input class="gazie-tooltip" data-type="weight" data-id="' . $peso . '" data-title="' . $script_transl['weight'] . '" type="text" name="rows[' . $k . '][quanti]" value="' . $v['quanti'] . '" align="right" maxlength=11 size=8 id="righi_' . $k . '_quanti" onchange="document.broven.last_focus.value=this.id; this.form.hidden_req.value=\'ROW\'; this.form.submit();" />
                    </td>';
                echo "<td></td>\n";
                echo "<td></td>\n";
                echo "<td></td>\n";
                echo "<td class=\"text-right\"></td>\n";
                echo "<td class=\"text-right\"></td>\n";
                echo "<td class=\"text-right\"></td>\n";
                $last_row[] = array_unshift($last_row, '<strong>' . $v['codart'] . '</strong>, ' . $v['descri'] . ', ' . $v['quanti'] . $v['unimis'] . ', <strong>' . $script_transl[23] . '</strong>: ' . gaz_format_number($v['prelis']) . ', %<strong>' . substr($script_transl[24], 0, 2) . '</strong>: ' . gaz_format_number($v['sconto']) . ', <strong>' . $script_transl[25] . '</strong>: ' . gaz_format_number($imprig) . ', <strong>' . $script_transl[19] . '</strong>: ' . $v['pervat'] . '%, <strong>' . $script_transl[18] . '</strong>: ' . $v['codric']);
            } else {
                echo "<input type=\"hidden\" name=\"rows[$k][descri]\" value=\"$descrizione\" maxlength=20 size=20  />
                    <input type=\"hidden\" class=\"gazie-tooltip\" data-type=\"weight\" data-id=\"" . $peso . "\" data-title=\"" . $script_transl["weight"] . "\" type=\"text\" name=\"rows[" . $k . "][unimis]\" value=\"" . $v["unimis"] . "\" maxlength=3 size=3 />
                    <input type=\"hidden\" class=\"gazie-tooltip\" data-type=\"weight\" data-id=\"" . $peso . "\" data-title=\"" . $script_transl['weight'] . "\" type=\"text\" name=\"rows[" . $k . "][quanti]\" value=\"" . $v["quanti"] . "\" align=\"right\" maxlength=11 size=11 id=\"righi_" . $k . "_quanti\" onchange=\"document.broven.last_focus.value=\"righi_" . $k . "_prelis\"; this.form.hidden_req.value=\"ROW\"; this.form.submit();\" />
                    <input type=\"hidden\" name=\"rows[$k][prelis]\" value=\"\" />
                    <input type=\"hidden\" name=\"rows[$k][sconto]\" value=\"\" />
                    <input type=\"hidden\" name=\"rows[$k][provvigione]\" value=\"\" />";
            }
            break;
        case "910": // annullato
          $cf = json_decode(gaz_dbi_get_row($gTables['rigbro'], 'id_rig', $v['id_rig'])['custom_field']);
          echo '<td><a nrow="'.$nr.'" id="row_'.$k.'" class="btn btn-default btn-xs dialog_moverow" title="'.$btntit.'" descr="<b>'. $script_transl['typerow'][$v['tiprig']].'</b>  '.$v['descri'].'"><i class="glyphicon glyphicon-'.$btngly.'"></i></a> '.$nr.'</td>';
          echo "<td title=\"" . $script_transl['update'] . $script_transl['thisrow'] . "!\">
                                <input class=\"FacetDataTDsmall\" type=\"submit\" name=\"upd_row[$k]\" value=\"" . $script_transl['typerow'][$v['tiprig']] . "\" />
			</td>
			<td colspan=8><span style=\"text-decoration:line-through;\">".$descrizione." ".$v['unimis']." ".floatval($v['quanti'])." x ".floatval($v['prelis'])."</span>
			<span class=\"text-danger\"> il ".$cf->cancellation->date." per ".$cf->cancellation->reason."</span></td>\n";
            echo '<td><input type="hidden" name="rows['.$k.'][descri]" value="'.$v['descri'].'" />
            <input type="hidden" name="rows['.$k.'][unimis]" value="'.$v['unimis'].'" />
            <input type="hidden" name="rows['.$k.'][quanti]" value="'.$v['quanti'].'" />
            <input type="hidden" name="rows['.$k.'][prelis]" value="'.$v['prelis'].'" />
            <input type="hidden" name="rows['.$k.'][sconto]" value="'.$v['sconto'].'" />
            </td>';
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']].' ( digitare il testo)');
            break;

    }
    if ( $v['tiprig']!="210") {
      if ($v['nrow_linked'] < $v['nrow']) {
        echo '<td></td>';
      } else {
        echo '<td class="text-right">
		    <button type="submit" class="btn btn-default btn-xs" name="del[' . $k . ']" title="' . $script_transl['delete'] . $script_transl['thisrow'] . '"><i class="glyphicon glyphicon-trash"></i></button>
		    </td>';
      }
    }
    echo "</tr>";
}
echo '<div id="maxnrow" movemax="'.$nr.'"</div>';

if (isset($ultimoprezzo) && $ultimoprezzo<>'') {
    $msgtoast = $upd_mm->toast(" <strong>Ultime vendite:</strong>".$ultimoprezzo, 'alert-last-row', 'alert-success');
}

if (count($form['rows']) > 0) {
  $msgtoast = $upd_mm->toast($msgtoast);  //lo mostriamo
  if (isset($_POST['in_submit']) && count($form['rows']) > 5) {
    $msgtoast = $upd_mm->toast($script_transl['last_row'] . ': ' . $last_row[0], 'alert-last-row', 'alert-success');  //lo mostriamo
  }
} else {
  echo '<tr id="alert-zerorows"><td colspan="12" class="alert alert-danger">' . $script_transl['zero_rows'] . '</td></tr>';
}
echo '</tbody></table></div>';
if ( $scroll_input_row < 9 ) { // ho il rigo di input posizionato dopo la tabella dei righi
  echo printInputNewRowForm($form,$script_transl,$gForm);
}

echo '<div class="FacetSeparatorTD text-center"><b>' . $script_transl[2] . '</b></div><div>
		<table class="Tlarge table table-striped table-bordered table-condensed">
			<input type="hidden" value="' . $form['numrat'] . '" name="numrat" />
			<input type="hidden" value="' . $form['expense_vat'] . '" name="expense_vat" />
			<input type="hidden" value="' . $form['spevar'] . '" name="spevar" />
			<input type="hidden" value="' . $form['stamp'] . '" name="stamp" />
			<input type="hidden" value="' . $form['round_stamp'] . '" name="round_stamp" />
			<input type="hidden" value="' . $form['cauven'] . '" name="cauven" />
			<input type="hidden" value="' . $form['caucon'] . '" name="caucon" />
			<input type="hidden" value="' . $form['caumag'] . '" name="caumag" />';

$somma_spese = floatval($form['traspo']) + floatval($form['speban']) * floatval($form['numrat']) + floatval($form['spevar']);
$calc = new Compute;
$calc->add_value_to_VAT_castle($castle, $somma_spese, $form['expense_vat']);
if ($calc->total_exc_with_duty > $admin_aziend['taxstamp_limit'] && $form['virtual_taxstamp'] > 0) {
    $form['taxstamp'] = $admin_aziend['taxstamp'];
}

echo "	<tr>
			<td class=\"FacetFieldCaptionTD text-right\">$script_transl[26]</td>
			<td class=\"FacetDataTD\">
				<input type=\"text\" name=\"imball\" value=\"" . $form['imball'] . "\" maxlength=50 size=20 class=\"FacetInput\">\n";
$select_spediz = new SelectValue("imballo");
$select_spediz->output('imball', 'imball');
echo "		</td>
			<td class=\"FacetFieldCaptionTD text-right\">$script_transl[27]</td>
			<td class=\"FacetDataTD\">
				<input type=\"text\" name=\"spediz\" value=\"" . $form["spediz"] . "\" maxlength=50 size=20 class=\"FacetInput\">\n";
$select_spediz = new SelectValue("spedizione");
$select_spediz->output('spediz', 'spediz');
/** ENRICO FEDELE */
/* td non chiuso */
echo "		</td>
			<td class=\"FacetFieldCaptionTD text-right\">$script_transl[14]</td>
			<td class=\"FacetDataTD\">\n";
$select_vettor = new selectvettor("vettor");
$select_vettor->addSelected($form["vettor"]);
$select_vettor->output();
echo "		</td>
			<td class=\"FacetFieldCaptionTD text-right\">$script_transl[29]</td>
			<td class=\"FacetDataTD\">
				<input type=\"text\" name=\"portos\" value=\"" . $form["portos"] . "\" maxlength=50 size=20 class=\"FacetInput\">\n";
$select_spediz = new SelectValue("portoresa");
$select_spediz->output('portos', 'portos');

echo "			</td>
			</tr>
			<tr>
				<td class=\"FacetFieldCaptionTD text-right\">$script_transl[28] " . $admin_aziend['html_symbol'] . "</td>
				<td class=\"FacetDataTD\">
					<input type=\"text\" value=\"" . $form['traspo'] . "\" name=\"traspo\" maxlength=6 size=6 onchange=\"this.form.submit()\" />
				</td>
				<td class=\"FacetFieldCaptionTD text-right\">$script_transl[30]</td>
				<td class=\"FacetDataTD\">
					<input class=\"FacetText\" type=\"text\" name=\"giotra\" VALUE=\"" . $form['giotra'] . "\" maxlength=2 size=2 >
					<input class=\"FacetText\" type=\"text\" name=\"mestra\" VALUE=\"" . $form['mestra'] . "\" maxlength=2 size=2 >
					<input class=\"FacetText\" type=\"text\" name=\"anntra\" VALUE=\"" . $form['anntra'] . "\" maxlength=4 size=3 >
					<a href=\"#\" onClick=\"cal.showCalendar('anchor','" . $form['mestra'] . "/" . $form['giotra'] . "/" . $form['anntra'] . "'); return false;\" title=\" cambia la data! \" name=\"anchor\" id=\"anchor\" class=\"btn btn-default btn-xs\">\n";
echo '<i class="glyphicon glyphicon-calendar"></i></a>' . $script_transl[31];
// select dell'ora
echo "\t <select name=\"oratra\" class=\"FacetText\" >\n";
for ($counter = 0; $counter <= 23; $counter++) {
    $selected = "";
    if ($counter == $form['oratra']) {
        $selected = 'selected=""';
    }
    echo "\t\t <option value=\"" . sprintf('%02d', $counter) . "\" $selected >" . sprintf('%02d', $counter) . "</option>\n";
}
echo "\t </select>\n ";
// select dell'ora
echo "\t <select name=\"mintra\" class=\"FacetText\" >\n";
for ($counter = 0; $counter <= 59; $counter++) {
    $selected = "";
    if ($counter == $form['mintra']) {
        $selected = ' selected=""';
    }
    echo "\t\t <option value=\"" . sprintf('%02d', $counter) . "\"$selected >" . sprintf('%02d', $counter) . "</option>\n";
}
echo "			</select>
			</td>
			<td class=\"FacetFieldCaptionTD text-right\">" . $script_transl[51] . "</td>
			<td class=\"FacetDataTD\">
				<select name=\"caumag\" class=\"FacetSelect\">\n";
$result = gaz_dbi_dyn_query("*", $gTables['caumag'], " clifor = -1 AND operat = " . $docOperat[$form['tipdoc']], "codice, descri");
while ($row = gaz_dbi_fetch_array($result)) {
    $selected = "";
    if ($form["caumag"] == $row['codice']) {
        $selected = ' selected=""';
    }
    echo "				<option value=\"" . $row['codice'] . "\"" . $selected . ">" . $row['codice'] . "-" . substr($row['descri'], 0, 20) . "</option>\n";
}
echo "			</select>
			</td>
			<td class=\"FacetFieldCaptionTD text-right\">$script_transl[55]</td>
			<td class=\"FacetDataTD\">
				<input type=\"text\" value=\"" . $form['volume'] . "\" name=\"volume\" maxlength=9 size=4 />
			</td>
		</tr>
		<tr>
			<td class=\"FacetFieldCaptionTD text-right\">$script_transl[52]</td>
			<td class=\"FacetDataTD\">
				<input type=\"text\" value=\"" . $form['net_weight'] . "\" name=\"net_weight\" maxlength=9 size=6 />
			</td>
			<td class=\"FacetFieldCaptionTD text-right\">$script_transl[53]</td>
			<td class=\"FacetDataTD\">
				<input type=\"text\" value=\"" . $form['gross_weight'] . "\" name=\"gross_weight\" maxlength=9 size=6 />
			</td>
			<td class=\"FacetFieldCaptionTD text-right\">$script_transl[54]</td>
			<td class=\"FacetDataTD\">
				<input type=\"text\" value=\"" . $form['units'] . "\" name=\"units\" maxlength=6 size=3 />
			</td>
			<td class=\"FacetFieldCaptionTD\" colspan=\"2\">
				" . $script_transl['taxstamp'] . "<input type=\"text\" value=\"" . $form['taxstamp'] . "\" name=\"taxstamp\" maxlength=6 size=3 /> " . $script_transl['virtual_taxstamp'];
$gForm->variousSelect('virtual_taxstamp', $script_transl['virtual_taxstamp_value'], $form['virtual_taxstamp']);
echo '			</td>
			</tr>
			<tr>
				<td class="FacetFieldCaptionTD text-right">' . $script_transl[32] . '</td>
				<td class="FacetFieldCaptionTD text-right">' . $script_transl[33] . '</td>
				<td class="FacetFieldCaptionTD text-right">' . $script_transl[34] . '</td>
				<td class="FacetFieldCaptionTD text-right">
					%' . $script_transl[24] . '<input type="text" name="sconto" value="' . $form["sconto"] . '" maxlength=3 size=3 onchange="this.form.submit()">
				</td>
				<td class="FacetFieldCaptionTD text-right">' . $script_transl[32] . '</td>
				<td class="FacetFieldCaptionTD text-right">' . $script_transl[19] . '</td>
				<td class="FacetFieldCaptionTD text-right">' . $script_transl['stamp'] . '</td>
				<td class="FacetFieldCaptionTD text-right">' . $admin_aziend['html_symbol'] . $script_transl[36] . '</td>
			</tr>';
foreach ($calc->castle as $k => $v) {
    echo '		<tr>
   					<td class="text-right">' . gaz_format_number($v['impcast']) . '</td>
					<td class="text-right">' . $v['descriz'] . ' ' . gaz_format_number($v['ivacast']) . '</td>
					<td colspan="6"></td>
				</tr>';
}

if ($next_row > 0) {
    if ($form['stamp'] > 0) {
        $calc->payment_taxstamp($calc->total_imp + $calc->total_vat + $carry - $rit + $form['taxstamp'], $form['stamp'], $form['round_stamp'] * $form['numrat']);
        $stamp = $calc->pay_taxstamp;
    } else {
        $stamp = 0;
    }
    echo '		<tr>
   					<td colspan="2"></td>
   					<td class="text-right">' . gaz_format_number($totimp_body) . '</td>
   					<td class="text-right">' . gaz_format_number(($totimp_body - $totimpfat + $somma_spese), 2, '.', '') . '</td>
					<td class="text-right">' . gaz_format_number($calc->total_imp) . '</td>
					<td class="text-right">' . gaz_format_number($calc->total_vat) . '</td>
					<td class="text-right">' . gaz_format_number($stamp) . '</td>
					<td name="totale" class="text-right" style="font-weight:bold;">' . gaz_format_number($calc->total_imp + $calc->total_vat + $stamp + $form['taxstamp']) . '</td>
				</tr>';

    if ($rit >= 0.01) {
        echo '		<tr>
	  					<td colspan="7" class="text-right">' . $script_transl['ritenuta'] . '</td>
						<td class="text-right">' . gaz_format_number($rit) . '</td>
					</tr>
					<tr>
						<td colspan="7" class="text-right">' . $script_transl['netpay'] . '</td>
						<td class="text-right">' . gaz_format_number($totimpfat + $totivafat + $stamp - $rit + $form['taxstamp']) . '</td>
					</tr>';
    }
    echo '		<tr>
					<td colspan="2" class="text-right">
						<input name="prestampa" class="btn btn-info" onClick="preStampa();" type="button" value="Prestampa">
					</td>
					<td colspan="2" class="text-center FacetFooterTD">
            <input name="shortdescri" maxlength=20 size=20 type="text" placeholder="Breve descrizione preventivo" value="'.$form['shortdescri'].'"/>
					</td>
					<td colspan="2" class="text-center FacetFooterTD">
						<input name="ins" class="btn '.$class_btn_confirm.'" id="preventDuplicate" onClick="chkSubmit();" type="submit" value="' . ucfirst($script_transl[$toDo]) . '">
					</td>
				';
} else {
  echo '<input name="shortdescri" type="hidden" value="'.$form['shortdescri'].'"/>';
}
if ($toDo == 'update' and $form['tipdoc'] == 'VPR') {
    echo '<td colspan="2"><input type="submit" class="btn btn-default" accesskey="o" name="ord" value="Genera ordine" /></td></tr>';
}else{
	echo '<td colspan="2"></td>';
}
echo "</tr>	</table></div>";
?>
</form>
<div class="modal" id="vat-price" title="IMPORTO IVA COMPRESA">
	<input type="text" id="cat_prevat" style="text-align: right;" maxlength=11 size=8 onkeyup="vatPriceCalc();" />
	<br /><br />
	<!--select id="codvat" name="cat_codvat" class="FacetSelect"></select-->
	<input type="text" id="cat_pervat" style="text-align: center;" maxlength=5 size=3 disabled="disabled" />
	<br /><br />
	<input type="text" id="cat_prelis" style="text-align: right;" maxlength=11 size=8 disabled="disabled" />
</div>
<script type="text/javascript">
	//var $options = $("#in_codvat > option").clone();
	//$('#cat_codvat').append($options);
	function vatPrice(row,pervat) {
		var prelis = $("[name='rows["+row+"][prelis]']").val();
		var prevat = parseFloat(prelis)*(1+parseFloat(pervat)/100);
    $("#cat_prevat").val(prevat.toFixed(<?php echo $admin_aziend['decimal_price'] ?>));
		$("#cat_pervat").val(pervat);
		$("#cat_prelis").val(prelis);
		$("#vat-price").dialog({
			modal: true,
			buttons: {
				Ok: function() {
					$("[name='rows["+row+"][prelis]']").val($("#cat_prelis").val());
					document.broven.last_focus.value="righi_" + row + "_sconto";
					$("[name='rows["+row+"][prelis]']").parents("form:first").submit();
					$(this).dialog("close");
				}
			}
		});
	};
	function vatPriceCalc() {
		var prevat = $("#cat_prevat").val();
		var pervat = $("#cat_pervat").val();
		if (prevat!="" && pervat!="") {
			var prelis = parseFloat(prevat)/(1+parseFloat(pervat)/100);
			$("#cat_prelis").val(prelis.toFixed(<?php echo $admin_aziend['decimal_price'] ?>));
		} else {
			$("#cat_prelis").val("0");
		}
	}
var last_focus_value;
var last_focus;
last_focus_value = document.broven.last_focus.value;
if (last_focus_value != "") {
    last_focus = document.getElementById(last_focus_value);
    if (last_focus != undefined) {
        last_focus.focus();
}
}
last_focus_value = "";

$( document ).ready(function() {
	$(".codricTooltip").each(function(index){$(this).attr('title', $("#in_codric option[value='"+$( this ).text().trim()+"']").text());});
});

</script>
<?php
if (isset($_POST['ins']) && empty($msg) && $pdf_to_modal!==0) {// stampa pdf in popup iframe
  ?>
  <script>
    printPdf('invsta_broven.php');
  </script>
  <?php
}
require("../../library/include/footer.php");
?>
