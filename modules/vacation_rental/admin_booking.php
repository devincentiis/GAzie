<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (https://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2022 - Antonio De Vincentiis Montesilvano (PE)
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
//echo "<pre>",print_r($_POST);die;
require("../../library/include/datlib.inc.php");
require("../../modules/magazz/lib.function.php");
require("../../modules/vendit/lib.function.php");
require("../../modules/acquis/lib.data.php");
include_once("manual_settings.php");

$admin_aziend = checkAdmin();
$min_stay = gaz_dbi_get_row($gTables['company_config'], 'var', 'vacation_minnights')['val'];
$vacation_blockdays = gaz_dbi_get_row($gTables['company_config'], 'var', 'vacation_blockdays')['val'];
$ivac = gaz_dbi_get_row($gTables['company_config'], 'var', 'vacation_ivac')['val'];
$pdf_to_modal = gaz_dbi_get_row($gTables['company_config'], 'var', 'pdf_reports_send_to_modal')['val'];
$scorrimento = gaz_dbi_get_row($gTables['company_config'], 'var', 'autoscroll_to_last_row')['val'];
$after_newdoc_back_to_doclist=gaz_dbi_get_row($gTables['company_config'], 'var', 'after_newdoc_back_to_doclist')['val'];
$msgtoast = "";
$msg = "";
$show_artico_composit = gaz_dbi_get_row($gTables['company_config'], 'var', 'show_artico_composit');
$tipo_composti = gaz_dbi_get_row($gTables['company_config'], 'var', 'tipo_composti');
$pointenable = gaz_dbi_get_row($gTables['company_config'], 'var', 'pointenable')['val'];
$points_expiry = gaz_dbi_get_row($gTables['company_config'], 'var', 'points_expiry')['val'];

function getDayNameFromDayNumber($day_number) {
  global $gazTimeFormatter;
  $gazTimeFormatter->setPattern('eeee');
  return ucfirst(utf8_encode($gazTimeFormatter->format(new DateTime('@'.mktime(12,0,0,3,19+$day_number, 2017)))));
}
function validateDate($date, $format = 'Y-m-d H:i:s') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

$upd_mm = new magazzForm;
$docOperat = $upd_mm->getOperators();
if (!isset($_POST['ritorno'])) {
    if (isset($after_newdoc_back_to_doclist)){
      if ($after_newdoc_back_to_doclist==0){
        $form['ritorno']="admin_booking.php?Insert&tipdoc=VOR";
      }else{
         $form['ritorno']="report_booking.php";
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
	$class_btn_confirm='btn-success';
}
if ((isset($_POST['Insert'])) or ( isset($_POST['Update']))) {   //se non e' il primo accesso
    //qui si dovrebbe fare un parsing di quanto arriva dal browser...

    if ($_POST['in_codart']!==$_POST['cosear']){// è appena stato selezionato un inserimento articolo alloggio
      $artico = gaz_dbi_get_row($gTables['artico'], "codice", $_POST['cosear']);
      if (isset($artico) && $artico['id_artico_group']>0 && ($data = json_decode($artico['custom_field'], TRUE))) { // se l'alloggio fa parte di una struttura e se esiste un json nel custom field dell'articolo
        if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['accommodation_type'])){// se è un alloggio
          $facility = gaz_dbi_get_row($gTables['artico_group'], "id_artico_group", $artico['id_artico_group']);// leggo la struttura
          if ($data = json_decode($facility['custom_field'], TRUE)) { // se esiste un json nel custom field della struttura
            if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['facility_type'])){// se è una struttura prendo i dati che mi serviranno
              $form['minor']=(isset($data['vacation_rental']['minor']))?$data['vacation_rental']['minor']:'12';// se non c'è l'età dei minori la imposto a 12 anni d'ufficio
              $form['tour_tax_from']=(isset($data['vacation_rental']['tour_tax_from']))?$data['vacation_rental']['tour_tax_from']:'';
              $form['tour_tax_to']=(isset($data['vacation_rental']['tour_tax_to']))?$data['vacation_rental']['tour_tax_to']:'';
              $form['tour_tax_day']=(isset($data['vacation_rental']['tour_tax_day']))?$data['vacation_rental']['tour_tax_day']:'';
              $form['max_booking_days']=(isset($data['vacation_rental']['max_booking_days']))?$data['vacation_rental']['max_booking_days']:'';
            }
          }
        }
      }else{
        $form['minor']=(intval($_POST['minor'])>0)?$_POST['minor']:'';
        $form['tour_tax_from']=(intval($_POST['tour_tax_from'])>0)?$_POST['tour_tax_from']:'';
        $form['tour_tax_to']=(intval($_POST['tour_tax_to'])>0)?$_POST['tour_tax_to']:'';
        $form['tour_tax_day']=(intval($_POST['tour_tax_day'])>0)?$_POST['tour_tax_day']:'';
        $form['max_booking_days']=(intval($_POST['max_booking_days'])>0)?$_POST['max_booking_days']:'';
      }
    }else{
      $form['minor']=(intval($_POST['minor'])>0)?$_POST['minor']:'';
      $form['tour_tax_from']=(intval($_POST['tour_tax_from'])>0)?$_POST['tour_tax_from']:'';
      $form['tour_tax_to']=(intval($_POST['tour_tax_to'])>0)?$_POST['tour_tax_to']:'';
      $form['tour_tax_day']=(intval($_POST['tour_tax_day'])>0)?$_POST['tour_tax_day']:'';
      $form['max_booking_days']=(intval($_POST['max_booking_days'])>0)?$_POST['max_booking_days']:'';

    }

    $form['id_tes'] = $_POST['id_tes'];
    $anagrafica = new Anagrafica();
    $cliente = $anagrafica->getPartner($_POST['clfoco']);
    if (!isset($cliente['speban'])){
      $cliente['speban']="N";
    }
    $form['hidden_req'] = $_POST['hidden_req'];
    // ...e della testata
    foreach ($_POST['search'] as $k => $v) {
        $form['search'][$k] = $v;
    }
    $form['codice_fornitore'] = $_POST['codice_fornitore'];
    $form['tur_tax'] = $_POST['tur_tax'];
    $form['tur_tax_mode'] = $_POST['tur_tax_mode'];
    $form['start'] = $_POST['start'];
    $form['end'] = $_POST['end'];
    $form['adult'] = $_POST['adult'];
    $form['child'] = $_POST['child'];
    $form['access_code'] = $_POST['access_code'];
    $gen_iva_perc =  $_POST['gen_iva_perc'];
    $gen_iva_code =  $_POST['gen_iva_code'];
    $form['extra'] = (isset($_POST['extra']))?$_POST['extra']:array();
    $form['qtaextra'] = (isset($_POST['qtaextra']))?$_POST['qtaextra']:0;
    $form['print_total'] = intval($_POST['print_total']);
    $form['delivery_time'] = intval($_POST['delivery_time']);
    $form['day_of_validity'] = intval($_POST['day_of_validity']);
    $form['cosear'] = $_POST['cosear'];
    $form['seziva'] = $_POST['seziva'];
    $form['indspe'] = $_POST['indspe'];
    $form['user_points'] = $_POST['user_points'];
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
    $form['email'] = $_POST['email'];
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
    if ($form['change_pag'] != $form['pagame']) {  //se e' stato cambiato il pagamento
        $new_pag = gaz_dbi_get_row($gTables['pagame'], "codice", $form['pagame']);
        if ($toDo == 'update') {  //se � una modifica mi baso sulle vecchie spese
            $old_header = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", $form['id_tes']);
            if ($cliente['speban'] == "S" && isset($new_pag['tippag']) && ($new_pag['tippag'] == 'T' || $new_pag['tippag'] == 'B')) {
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
    $form['id_agente'] = intval($_POST['id_agente']);
    $form['id_tourOp'] = intval($_POST['id_tourOp']);// NB: PIù SOTTO AVVERRà UNO SCAMBIO FRA AGENTE E TOUROP
    $form['sconto'] = $_POST['sconto'];
    // inizio rigo di input
    $form['in_descri'] = $_POST['in_descri'];
    $form['in_tiprig'] = $_POST['in_tiprig'];
    $form['in_id_doc'] = $_POST['in_id_doc'];
    $form['in_codart'] = $_POST['in_codart'];
	/*   $form['in_artsea'] = $_POST['in_artsea']; */
    $form['in_pervat'] = $_POST['in_pervat'];
    $form['in_tipiva'] = $_POST['in_tipiva'];
    $form['in_ritenuta'] = $_POST['in_ritenuta'];
    $form['in_unimis'] = $_POST['in_unimis'];
    $form['in_prelis'] = $_POST['in_prelis'];
    $form['in_sconto'] = $_POST['in_sconto'];
    $form['in_quanti'] = gaz_format_quantity($_POST['in_quanti'], 0, $admin_aziend['decimal_quantity']);
    $form['in_codvat'] = $_POST['in_codvat'];
    $form['in_codric'] = $_POST['in_codric'];
    $form['in_provvigione'] = $_POST['in_provvigione'];
    $form['in_id_mag'] = $_POST['in_id_mag'];
    $form['in_annota'] = $_POST['in_annota'];
    $form['in_scorta'] = $_POST['in_scorta'];
    $form['in_quamag'] = $_POST['in_quamag'];
    $form['in_pesosp'] = $_POST['in_pesosp'];
    $form['in_extdoc'] = $_POST['in_extdoc'];
    $form['in_status'] = $_POST['in_status'];
    // fine rigo input
    $lang="it";
    if (isset($cliente['id_language']) && intval($cliente['id_language'])>0 ){
      $lang=gaz_dbi_get_row($gTables['languages'], 'lang_id', $cliente['id_language'])['sef'];
    }

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
    if ($_POST['id_tourOp'] > 0) { // se c'è un agente/tour operator carico la provvigione standard
      $provvigione = new Agenti;// NB: PIù SOTTO AVVERRà UNO SCAMBIO FRA AGENTE E TOUROP
      $form['in_provvigione'] = $provvigione->getPercent($form['id_tourOp']);
    }

    $form['rows'] = array();
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
            $form['rows'][$next_row]['codice_fornitore'] = substr($v['codice_fornitore'], 0, 32);
            $form['rows'][$next_row]['good_or_service'] = intval($v['good_or_service']);
            $form['rows'][$next_row]['accommodation_type'] = intval($v['accommodation_type']);
            $form['rows'][$next_row]['adult'] = intval($v['adult']);
            $form['rows'][$next_row]['child'] = intval($v['child']);
            $form['rows'][$next_row]['total_guests'] = intval($v['total_guests']);
            $form['rows'][$next_row]['fixquote'] = intval($v['fixquote']);
            $form['rows'][$next_row]['facility_id'] = intval($v['facility_id']);
            $form['rows'][$next_row]['pervat'] = preg_replace("/\,/", '.', $v['pervat']);
            $form['rows'][$next_row]['tipiva'] = strtoupper(substr($v['tipiva'], 0, 1));
            $form['rows'][$next_row]['ritenuta'] = preg_replace("/\,/", '.', $v['ritenuta']);
            $form['rows'][$next_row]['unimis'] = (isset($v['unimis']))?substr($v['unimis'], 0, 3):'n';
            $form['rows'][$next_row]['prelis'] = number_format(floatval(preg_replace("/\,/", '.', $v['prelis'])), $admin_aziend['decimal_price'], '.', '');
            $form['rows'][$next_row]['sconto'] = floatval(preg_replace("/\,/", '.', $v['sconto']));
            $form['rows'][$next_row]['quanti'] = (isset($v['quanti']))?gaz_format_quantity($v['quanti'], 0, $admin_aziend['decimal_quantity']):0;
            $form['rows'][$next_row]['codvat'] = intval($v['codvat']);
            $form['rows'][$next_row]['codric'] = intval($v['codric']);
            if (isset($form['in_provvigione']) && $_POST['id_tourOp'] > 0){// NB: PIù SOTTO AVVERRà UNO SCAMBIO FRA AGENTE E TOUROP
              $form['rows'][$next_row]['provvigione'] = $provvigione->getPercent($form['id_tourOp'], substr($v['codart'], 0, 32));
            }else{
              $form['rows'][$next_row]['provvigione'] = 0;
            }
            if (substr($v['codart'], 0, 15)=="TASSA-TURISTICA" && strlen($v['codice_fornitore'])<2){
              $msg .= '67+';
            }
            $form['rows'][$next_row]['id_mag'] = intval($v['id_mag']);
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
                    $form['net_weight'] -= $form['rows'][$k_row]['quanti'] * $artico['peso_specifico'];
                    $form['gross_weight'] -= $form['rows'][$k_row]['quanti'] * $artico['peso_specifico'];
                    if ($artico['pack_units'] > 0) {
                        $form['units'] -= intval(round($form['rows'][$k_row]['quanti'] / $artico['pack_units']));
                    }
                    $form['volume'] -= $form['rows'][$k_row]['quanti'] * $artico['volume_specifico'];
                    // fine sottrazione peso,pezzi,volume
                    $form['in_descri'] = $form['rows'][$k_row]['descri'];
                    $form['in_tiprig'] = $form['rows'][$k_row]['tiprig'];
                    $form['in_codart'] = $form['rows'][$k_row]['codart'];
                    $form['in_codice_fornitore'] = $form['rows'][$k_row]['codice_fornitore'];
                    $form['in_good_or_service'] = $form['rows'][$k_row]['good_or_service'];
                    $form['in_accommodation_type'] = $form['rows'][$k_row]['accommodation_type'];
                    $form['in_adult'] = $form['rows'][$k_row]['adult'];
                    $form['in_child'] = $form['rows'][$k_row]['child'];
                    $form['in_total_guests'] = $form['rows'][$k_row]['total_guests'];
                    $form['in_fixquote'] = $form['rows'][$k_row]['fixquote'];
                    $form['in_facility_id'] = $form['rows'][$k_row]['facility_id'];
                    $form['in_pervat'] = $form['rows'][$k_row]['pervat'];
                    $form['in_tipiva'] = $form['rows'][$k_row]['tipiva'];
                    $form['in_ritenuta'] = $form['rows'][$k_row]['ritenuta'];
                    $form['in_unimis'] = $form['rows'][$k_row]['unimis'];
                    $form['in_prelis'] = $form['rows'][$k_row]['prelis'];
                    $form['in_sconto'] = $form['rows'][$k_row]['sconto'];
                    $form['in_quanti'] = $form['rows'][$k_row]['quanti'];
                    $form['in_codvat'] = $form['rows'][$k_row]['codvat'];
                    $form['in_codric'] = $form['rows'][$k_row]['codric'];
                    $form['in_provvigione'] = $form['rows'][$k_row]['provvigione'];
                    $form['in_id_mag'] = $form['rows'][$k_row]['id_mag'];
                    $form['in_annota'] = $form['rows'][$k_row]['annota'];
                    $form['in_scorta'] = $form['rows'][$k_row]['scorta'];
                    $form['in_quamag'] = $form['rows'][$k_row]['quamag'];
                    $form['in_pesosp'] = $form['rows'][$k_row]['pesosp'];
                    $form['in_extdoc'] = $form['rows'][$k_row]['extdoc'];
                    $form['in_status'] = "UPDROW" . $k_row;
                    /* if ($form['in_artsea'] == 'D') {
                      $artico_u = gaz_dbi_get_row($gTables['artico'], 'codice', $form['rows'][$k_row]['codart']);
                      $form['cosear'] = $artico_u['descri'];
                      } elseif ($form['in_artsea'] == 'B') {
                      $artico_u = gaz_dbi_get_row($gTables['artico'], 'codice', $form['rows'][$k_row]['codart']);
                      $form['cosear'] = $artico_u['barcode'];
                      } else { */
                    $form['cosear'] = $form['rows'][$k_row]['codart'];
                    //}
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
                $form['net_weight'] += $form['rows'][$next_row]['quanti'] * $artico['peso_specifico'];
                $form['gross_weight'] += $form['rows'][$next_row]['quanti'] * $artico['peso_specifico'];
                if ($artico['pack_units'] > 0) {
                    $form['units'] += intval(round($form['rows'][$next_row]['quanti'] / $artico['pack_units']));
                }
                $form['volume'] += $form['rows'][$next_row]['quanti'] * $artico['volume_specifico'];
            }
            $next_row++;
        }
    }

    if ($_POST['start'] > $_POST['end']){
		$msg .= "38+";
	}

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

        // --- inizio controllo coerenza date-numerazione
        if (validateDate($_POST['start'], 'Y-m-d') == false || validateDate($_POST['end'], 'Y-m-d') == false){// controllo date check-in/out
          $msg .= "37+";
        }
        if ($toDo == 'update') {  // controlli in caso di modifica
            $rs_query = gaz_dbi_dyn_query("numdoc", $gTables['tesbro'], "YEAR(datemi) = " . $form['annemi'] . " and datemi < '$datemi' and tipdoc = '" . $form['tipdoc'] . "' and seziva = $sezione", "datemi DESC, numdoc DESC", 0, 1);
            $result = gaz_dbi_fetch_array($rs_query); //giorni precedenti
            if ($result and ( $form['numdoc'] < $result['numdoc'])) {
                $msg .= "42+";
                echo "la prenotazione successiva a questa (",$result['numdoc'],") ha una data precedente";
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
        if (empty($form['clfoco'])&& $toDo == 'insert')
            $msg .= "47+";
        if (empty($form['pagame']))
            $msg .= "48+";

		// controlli sul numero di notti
		$datediff = strtotime($form['end'])-strtotime($form['start']);
		$night=round($datediff / (60 * 60 * 24));
		if (intval($form['max_booking_days'])>0 && $night > intval($form['max_booking_days'])){// se si supera il numero delle notti prenotabili consentito
		  $msg .= "66+";
		}

        //controllo che i rows non abbiano descrizioni  e unita' di misura vuote in presenza di quantita diverse da 0
        foreach ($form['rows'] as $i => $v) {
          // controllo capienza ospiti
          if ($v['accommodation_type']>2){// se è un alloggio (1=extra)
            if (intval($v['total_guests']) < (intval($form['adult'])+intval($form['child']))){
              $msg .= "59+";
            }
            if ($v['adult'] < $form['adult']){
              $msg .= "60+";
            }
            if ($v['child'] < $form['child']){
              $msg .= "61+";
            }
          }
          //fine controllo capienza ospiti

          if ($v['codart']=="TASSA-TURISTICA"){
            if (strlen($v['codice_fornitore'])<2){ // manca il riferimento alloggio alla tassa turistica
              $msg .= "67+";
            }
          }

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
        //echo "<pre>",print_r($form);die;
             $initra .= " " . $form['oratra'] . ":" . $form['mintra'] . ":00";
            if (preg_match("/^id_([0-9]+)$/", $form['clfoco'], $match)) {
                $new_clfoco = $anagrafica->getPartnerData($match[1], 1);
                $form['clfoco'] = $anagrafica->anagra_to_clfoco($new_clfoco, $admin_aziend['mascli'],$form['pagame']);
            }
            if ($toDo == 'update') { // e' una modifica

              // CANCELLO TUTTO
              $tesbro = gaz_dbi_get_row($gTables['tesbro'], "id_tes", intval($form['id_tes'])); // prima di cancellare prendo il vecchio custom field
              $form['custom_field']=$tesbro['custom_field']; // riprendo il custom_field esistente

              gaz_dbi_del_row($gTables['tesbro'], "id_tes", $form['id_tes']);

              $rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes =". intval($form['id_tes']),"id_tes DESC");
              while ($a_row = gaz_dbi_fetch_array($rs_righidel)) {
                gaz_dbi_del_row($gTables['body_text'], "table_name_ref = 'rigbro' AND id_ref ",$a_row['id_rig']);
                gaz_dbi_del_row($gTables['rigbro'], "id_rig", $a_row['id_rig']);

              }

              $rental_events = gaz_dbi_get_row($gTables['rental_events'], "id_tesbro", intval($form['id_tes']), " AND type = 'ALLOGGIO'"); // prima di cancellare prendo il vecchio rental_events custom field
              $access = (strlen($rental_events['access_code']>0))?$rental_events['access_code']:bin2hex(openssl_random_pseudo_bytes(5));// se non c'è un codice accesso lo creo e inserisco;
              gaz_dbi_del_row($gTables['rental_events'], "id_tesbro", $form['id_tes']);
              // dovrò aggiornare tesbro negli eventuali pagamenti effettuati su rental payment ma posso farlo solo dopo aver creato il nuovo tesbro
              $form['status'] = 'UPDATED';

            } else{
              $form['status'] = 'GENERATO';
              $data=[];//inizializzo l'array per il nuovo custom field
              $access = bin2hex(openssl_random_pseudo_bytes(5));// creo una pseudo-password casuale di 10 caratteri
            }
            // INSERIMENTO DATA BASE valido per INSERT e per UPDATE

            //echo "<pre>",print_r($form),"<br>blockdays:",intval($vacation_blockdays);die;
            // ricavo i progressivi in base al tipo di documento
            $where = "numdoc desc";
            $sql_documento = "YEAR(datemi) = " . $form['annemi'] . " and tipdoc = '" . $form['tipdoc'] . "'";
            $rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesbro'], $sql_documento, $where, 0, 1);
            $ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
            if (strlen($form['numdoc'])==0){// se è insert, mi ricavo il numdoc altrimenti ce l'ho già
              if ($ultimo_documento) {// se non è il primo documento dell'anno proseguo la numerazione
                  $form['numdoc'] = $ultimo_documento['numdoc'] + 1;
              } else {// se e' il primo documento dell'anno, resetto il contatore
                  $form['numdoc'] = 1;
              }
            }
            $form['protoc'] = 0;
            $form['numfat'] = 0;
            $form['datfat'] = 0;
            //inserisco la testata

            $form['destin'] = '';
            $form['template'] = 'booking';
            $form['initra'] = $initra;
            $form['datemi'] = $datemi;
            $form['id_agente'] = $form['id_tourOp'];// scambio perché l'agente è il tour operator mentre in id_agente ho il proprietario

            if ($toDo == 'update') { // e' una modifica riscrivo tesbro con lo stesso vecchio id
              $table = 'tesbro';
              $columns = array('id_tes','seziva', 'tipdoc','ref_ecommerce_id_order', 'template', 'email', 'print_total', 'delivery_time', 'day_of_validity', 'datemi', 'protoc', 'numdoc', 'numfat', 'datfat', 'clfoco', 'pagame', 'banapp', 'vettor', 'weekday_repeat', 'listin', 'destin', 'id_des', 'id_des_same_company', 'spediz', 'portos', 'imball', 'traspo', 'speban', 'spevar', 'round_stamp', 'cauven', 'caucon', 'caumag', 'id_agente', 'id_parent_doc', 'sconto', 'expense_vat', 'stamp', 'net_weight', 'gross_weight', 'taxstamp', 'virtual_taxstamp', 'units', 'volume', 'initra', 'geneff', 'id_contract', 'id_con', 'id_orderman', 'status', 'custom_field', 'adminid');
              tableInsert($table, $columns, $form);
              $ultimo_id = $form['id_tes'];
            }else{// è un nuovo inserimento
              tesbroInsert($form); // scrivo tesbro
              //recupero l'id_tesbro assegnato dall'inserimento
              $ultimo_id = gaz_dbi_last_id();
              if ($form['tipdoc']=='VPR'){// se è un preventivo inserisco acc_prev aggiornando tesbro
                $data['vacation_rental']['status']="QUOTE";
                $data['vacation_rental']['ip']="diretto";
                $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-128-CBC'));
                $cripted_pwr=base64_encode(openssl_encrypt($ultimo_id, 'aes-128-CBC', $token, 0, $iv).'::'.$iv);
                list($encrypted_data, $iv) = explode('::', base64_decode($cripted_pwr), 2);
                $id_tes=intval(openssl_decrypt($encrypted_data, 'aes-128-CBC', $token, 0, $iv));
                $data['vacation_rental']['acc_prev']=$cripted_pwr;
                $form['custom_field'] = json_encode($data);
                gaz_dbi_put_row($gTables['tesbro'], 'id_tes', $ultimo_id, 'custom_field', $form['custom_field']);
              }else{
                if (isset($form['custom_field'])){
                  $data = json_decode($form['custom_field'],true);
                }else{
                  $data=array();
                }
                $data['vacation_rental']['status']=(isset($data['vacation_rental']['status']))?$data['vacation_rental']['status']:'CONFIRMED';
                $data['vacation_rental']['ip']=(isset($data['vacation_rental']['ip']))?$data['vacation_rental']['ip']:'diretto';
                $form['custom_field'] = json_encode($data);
                gaz_dbi_put_row($gTables['tesbro'], 'id_tes', $ultimo_id, 'custom_field', $form['custom_field']);
              }
            }

            if ($toDo == 'update') { // se e' una modifica risincronizzo gli eventuali pagamenti con il nuovo id_tesbro
              $table="rental_payments";
              $columns = array('id_tesbro');// colonne da aggiornare
              $codice[0] = "id_tesbro";
              $codice[1] = $form['id_tes'];
              $newvalue['id_tesbro'] = $ultimo_id;
              tableUpdate($table, $columns, $codice, $newvalue);
            }

            //inserisco i rows
            foreach ($form['rows'] as $i => $v) {
              $form['rows'][$i]['id_tes'] = $ultimo_id;

              //inserisco il rigo
              $last_rigbro_id = rigbroInsert($form['rows'][$i]);
              $form['id_rigbro']=  $last_rigbro_id;

              if ($form['rows'][$i]['accommodation_type']>2){// se è un alloggio inserisco l'evento in rental_events
                $table = 'rental_events';
                $form['id_tesbro']=  $form['rows'][$i]['id_tes'];
                switch ($form['rows'][$i]['accommodation_type']) {//3 => 'Appartamento', 4 => 'Casa indipendente', 5=> 'Bed & breakfast'
                  case "3":
                    $accomodation_type="Appartamento";
                    break;
                  case "4":
                    $accomodation_type="Casa indipendente";
                    break;
                  case "5":
                    $accomodation_type="Bed & breakfast";
                    break;
                  case "6":
                    $accomodation_type="Camera";
                    break;
                  case "7":
                    $accomodation_type="Locazione turistica";
                    break;
                }
                $form['checked_in_date']=(isset($rental_events['checked_in_date']))?$rental_events['checked_in_date']:NULL;
                $form['checked_out_date']=(isset($rental_events['checked_out_date']))?$rental_events['checked_out_date']:NULL;
                $form['voucher_id']=(isset($rental_events['voucher_id']))?$rental_events['voucher_id']:NULL;
                $form['type']="ALLOGGIO";
                $form['access_code'] = $access;
                $form['title']= "Prenotazione ".$accomodation_type." ".$form['rows'][$i]['codart']." - ".$form['search']['clfoco'];
                $form['house_code']=$form['rows'][$i]['codart'];
                $columns = array('id', 'title', 'start', 'end', 'house_code', 'id_tesbro', 'id_rigbro', 'voucher_id', 'checked_in_date', 'checked_out_date', 'adult', 'child', 'type', 'access_code');
                tableInsert($table, $columns, $form);
                $artico = gaz_dbi_get_row($gTables['artico'], "codice", $form['rows'][$i]['codart']);
                $data = json_decode($artico['custom_field'], TRUE);
                $data['vacation_rental']['pause']=(isset($data['vacation_rental']['pause']))?$data['vacation_rental']['pause']:0;
                if (intval($vacation_blockdays)>0 ||  intval($data['vacation_rental']['pause'])>0){// se ci sono giorni cuscinetto o pausa li aggiungo a rental_events
                  $form['type']="PAUSA";
                  $form['id_rigbro'] = 0;
                  $realstart=$form['start'];
                  $realend=$form['end'];
                  $form['access_code'] = "";
                  if (intval($vacation_blockdays)>0){
                    $form['start']=$date1 = date("Y-m-d", strtotime($realstart.'- '.(intval($vacation_blockdays)+intval($data['vacation_rental']['pause'])).' days'));
                    $form['end']=$date1 = date("Y-m-d", strtotime($realstart));
                    tableInsert($table, $columns, $form);// scrivo il cuscinetto iniziale sommando il cuscinetto generale con quello specifico dell'alloggio
                  }
                  $form['start']=date("Y-m-d", strtotime($realend));
                  $form['end']=date("Y-m-d", strtotime($realend.'+ '. (intval($vacation_blockdays)+intval($data['vacation_rental']['pause'])) .' days'));
                  tableInsert($table, $columns, $form);// scrivo il cuscinetto finale sommando il cuscinetto generale con quello specifico dell'alloggio
                  $form['start'] = $realstart;
                  $form['end'] = $realend;
                }
              }

              // se è un extra e l'extra prevede dei limiti sulle quantità inserisco anche l'extra in rental_events
              if ($form['rows'][$i]['accommodation_type']==1){
                if (gaz_dbi_get_row($gTables['rental_extra'], "codart", $form['rows'][$i]['codart'])['max_quantity']>0){
                  $form['id_rigbro']=  $last_rigbro_id;// ripristino id_rigbro qualora fosse stato cambiato dai cuscinetti
                  $table = 'rental_events';
                  $form['id_tesbro']=  $form['rows'][$i]['id_tes'];
                  $form['type']="EXTRA";
                  $form['access_code'] = "";
                  $form['title']= "Prenotazione n.". $form['numdoc'] ." EXTRA ".$form['rows'][$i]['codart'];
                  $form['house_code']=$form['rows'][$i]['codart'];
                  $columns = array('id', 'title', 'start', 'end', 'house_code', 'id_tesbro', 'id_rigbro', 'adult', 'child', 'type');
                  tableInsert($table, $columns, $form);
                }
              }

              // INIZIO INSERIMENTO DOCUMENTI ALLEGATI
              if (!empty($form['rows'][$i]['extdoc'])) {
                  $tmp_file = DATA_DIR . 'files/tmp/' . $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $i . '_' . $form['rows'][$i]['extdoc'];
                  // sposto e rinomino il relativo file temporaneo
                  $fd = pathinfo($form['rows'][$i]['extdoc']);
                  rename($tmp_file, DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/rigbrodoc_' . $last_rigbro_id . '.' . $fd['extension']);
              }
              // FINE INSERIMENTO DOCUMENTI ALLEGATI

              if (isset($form["row_$i"])) { //se è un rigo testo lo inserisco in rigbro con il suo contenuto in body_text
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
              header("Location: report_booking.php?auxil=$auxil");
              exit;
            }
            $_SESSION['print_request'] = $ultimo_id;
            if ($pdf_to_modal==0){
              header("Location: invsta_booking.php");
              exit;
            }

        }
    } elseif (isset($_POST['ord']) and $toDo == 'update') {  // si vuole generare una prenotazione da un preventivo

      $columns = array('tipdoc');// colonne da aggiornare
      $codice[0] = "id_tes";
      $codice[1] = $form['id_tes'];
      $newvalue['tipdoc'] = "VOR";
      tableUpdate('tesbro', $columns, $codice, $newvalue);
      header("Location: report_booking.php?auxil=VOR");
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
        $cliente['custom_field']=gaz_dbi_get_row($gTables['anagra'], "id", $cliente['id_anagra'])['custom_field']; // carico il custom field del cliente in quanto la classe Anagrafica non lo carica correttamente
        if (isset($cliente['custom_field']) && $data = json_decode($cliente['custom_field'],true)){// se c'è un json in anagra
          if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental" lo aggiorno
            if (isset($data['vacation_rental']['points'])){
              $form['user_points'] = intval($data['vacation_rental']['points']);
              if (intval($points_expiry)==0){
                $expired=2;
              }else{
                $date=(isset($data['vacation_rental']['points_date']))?date_create($data['vacation_rental']['points_date']):date_create("2023-09-01");
                date_add($date,date_interval_create_from_date_string(intval($points_expiry)." days"));// aggiungo la durata dei punti
                $expiry_points_date=date_format($date,"d-m-Y");// questa è la data di scadenza
                $expired=0;
                if (strtotime(date_format($date,"Y-m-d")) < strtotime(date("Y-m-d"))){// se i punti sono scaduti
                  $expired=1;
                }
              }
            }else{
              $form['user_points']=0;
              $expired=0;
              if (intval($points_expiry)==0){
                $expired=2;
              }
            }
          }
        }else{
          $form['user_points']=0;
        }
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
    if ($_POST['hidden_req'] == 'TOUROP') { // da rivedere perché qui, come sopra, non ci passa mai... e poi il proprietario non genera provvigioni mentre il tour operator sì
        if ($form['id_tourOp'] > 0) { // carico la provvigione standard
            $provvigione = new Agenti;
            $form['in_provvigione_tourOp'] = $provvigione->getPercent($form['id_tourOp']);
            if (isset($_POST['rows'])) {  // aggiorno le provvigioni sui rows
                foreach ($_POST['rows'] as $k => $val) {
                    $form['rows'][$k]['provvigione_tourOp'] = $form['in_provvigione_tourOp'];
                    $form['rows'][$k]['provvigione_tourOp'] = $provvigione->getPercent($form['id_tourOp'], $val['codart']);
                }
            }
        }
        $form['hidden_req'] = '';
    }

    // Se viene inviata la richiesta di conferma rigo

	if (isset($_POST['in_submit_desc'])) { //rigo Descrittivo rapido
        $form['rows'][$next_row]['codart'] = '';
        $form['rows'][$next_row]['annota'] = '';
        $form['rows'][$next_row]['pesosp'] = '';
        $form['rows'][$next_row]['good_or_service'] = 0;
        $form['rows'][$next_row]['accommodation_type'] = 0;
        $form['rows'][$next_row]['adult'] = 0;
        $form['rows'][$next_row]['child'] = 0;
        $form['rows'][$next_row]['total_guests'] = 0;
        $form['rows'][$next_row]['fixquote'] = 0;
        $form['rows'][$next_row]['facility_id'] = 0;
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
        $form['rows'][$next_row]['accommodation_type'] = 0;
        $form['rows'][$next_row]['adult'] = 0;
        $form['rows'][$next_row]['child'] = 0;
        $form['rows'][$next_row]['total_guests'] = 0;
        $form['rows'][$next_row]['fixquote'] = 0;
        $form['rows'][$next_row]['facility_id'] = 0;
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
        $form['rows'][$next_row]['status'] = 'INSERT';
        $form['rows'][$next_row]['scorta'] = 0;
        $form['rows'][$next_row]['quamag'] = 0;
        $form['rows'][$next_row]['tiprig'] = 6;
        $next_row++;
    } else if ((isset($_POST['in_submit']) && strlen($form['in_codart'])>0 && $form['start']!="" && $form['end']!="") || (isset($_POST['extra_submit']) && strlen($form['extra'][   $_POST['extra_submit']])>0 && isset($_POST['rows']))) { // conferma inserimento alloggio o extra o tassa turistica

      if (isset($_POST['extra_submit']) && strlen($form['extra'][$_POST['extra_submit']])>0){// se è un extra (ci deve per forza essere l'alloggio e quindi per forza anche le date)
        // faccio tutto più sotto
        $form['in_codart']=$form['extra'][$_POST['extra_submit']];
        $night=0;
        $start="";
      }

      $total_price=0;// inizializzo il prezzo totale della locazione per il successivo calcolo
      $artico = gaz_dbi_get_row($gTables['artico'], "codice", $form['in_codart']);
      $form['in_fixquote'] = 0;
      if (isset($_POST['in_submit']) && strlen($form['in_codart'])>0 && $form['start']!="" && $form['end']!="" && $form['in_codart']<>"TASSA-TURISTICA"){// se è un alloggio e ci sono le date CALCOLO IL PREZZO
        if ($data = json_decode($artico['custom_field'], TRUE)) { // se esiste un json nel custom field
          if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['accommodation_type'])){// se è un alloggio
            $form['in_fixquote'] = (isset($data['vacation_rental']['fixquote']))?$data['vacation_rental']['fixquote']:0;
          }
        }
        $start=$form['start'];
        $gen_iva_perc = gaz_dbi_get_row($gTables['aliiva'], 'codice', $artico['aliiva'])['aliquo'];
        $gen_iva_code = $artico['aliiva'];
        $night=0;
        $check_availability=check_availability($form['start'],$form['end'],$form['in_codart'], $open_from="", $open_to="");// controllo disponibilità
        if (intval($check_availability)==0){
          $overbooking=1;
        }
        while (strtotime($start) < strtotime($form['end'])) {// ciclo il periodo della locazione giorno per giorno
          //Calcolo del prezzo locazione e conteggio notti
          $what = "price";
          $table = $gTables['rental_prices'];
          $where = "start <= '". $start ."' AND end >= '". $start."' AND house_code='".$form['in_codart']."'";

          $result = gaz_dbi_dyn_query($what, $table, $where);
          $prezzo = gaz_dbi_fetch_array($result);
          if (isset($prezzo) && $ivac=="si"){// se i prezzi nel calendario sono iva compresa

            $prezzo['price']=floatval($prezzo['price'])/floatval("1.".$gen_iva_perc); // scorporo l'iVA
          }
          if (isset($prezzo)){
            $total_price += floatval($prezzo['price']);// aggiungo il prezzo giornaliero trovato
          } elseif(floatval($artico['web_price'])>0){
            $total_price += floatval($artico['web_price']);// in mancanza del prezzo giornaliero aggiungo il prezzo base
          }else{
            echo "ERRORE MANCA IL PREZZO in uno o più giorni e non esiste un prezzo base";
          }

          $start = date ("Y-m-d", strtotime("+1 days", strtotime($start)));// aumento di un giorno il ciclo
          $night++;
        }
        if (isset($overbooking)){
           $msg .= "63+";// Overbooking
        }
        $start="";

        $form['in_prelis']=$total_price;
        if ($night<intval($min_stay)){// se non si raggiunge il minimo prenotabile
          $msg .= "65+";
        }
      }
      if ($form['in_codart']<>"TASSA-TURISTICA"){// se è ALLOGGIO calcolo e applico gli eventuali sconti
        $form['in_prelis'] += $form['in_fixquote'];// se c'è, aggiungo la quota fissa al prezzo
        $total_price += $form['in_fixquote'];// se c'è, aggiungo la quota fissa al prezzo

        // calcolo gli sconti
        $discounts=searchdiscount($form['in_codart'],$artico['id_artico_group'],$form['start'],$form['end'],$night,$anagra=0,$gTables['rental_discounts']);
        $form['discount']=array();
        $form['descri_discount']=array();
        $form['discountable_price']=array();
        $total_price_disc=$total_price;
        $today=date('Y-m-d');
        if (isset($discounts) && $discounts->num_rows >0){// se c'è almeno uno sconto
          if (isset($cliente['id_anagra'])){
            $level=get_user_points_level($cliente['id_anagra']);
            $cliente['custom_field']=gaz_dbi_get_row($gTables['anagra'], "id", $cliente['id_anagra'])['custom_field']; // carico il custom field del cliente in quanto la classe Anagrafica non lo carica correttamente
          }else{
            $level=0;
          }
          $expired=0;
          if (intval($points_expiry)==0){
            $expired=2;
          }elseif (isset($cliente['custom_field']) && $data = json_decode($cliente['custom_field'],true)){// se c'è un json in anagra
            if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental"
              if (isset($data['vacation_rental']['points'])){// se ci sono punti, ne vedo la scadenza
                $date=(isset($data['vacation_rental']['points_date']))?date_create($data['vacation_rental']['points_date']):date_create("2023-09-01");
                date_add($date,date_interval_create_from_date_string(intval($points_expiry)." days"));// aggiungo la durata dei punti
                $expiry_points_date=date_format($date,"d-m-Y");// questa è la data di scadenza
                if (strtotime(date_format($date,"Y-m-d")) < strtotime(date("Y-m-d"))){// se i punti sono scaduti
                  $expired=1;
                }
              }
            }
          }
          foreach ($discounts as $discount){ // li ciclo e applico lo sconto
            if (intval($discount['last_min'])>0){// se è un lastmin controllo la validità
              $date=date_create($today);
              date_add($date,date_interval_create_from_date_string($discount['last_min']." days"));
              $time=strtotime(date_format($date,"Y-m-d"));
              if ($time < strtotime($form['start'])){
                continue; // non è valido, continuo con l'eventuale prossimo sconto
              }
            }

            if ($expired<>1 && $pointenable==1 && (intval($discount['level_points'])>0 && intval($level)==intval($discount['level_points'])) || intval($discount['level_points'])==0){//calcolo sconti se c'è un livello punti raggiunto dal cliente o se gli sconti sono senza livello punti

              if ($discount['is_percent']==1){
                $form['discountable_price'][]=$total_price_disc;
                $form['discount'][]= (floatval($total_price_disc)*floatval($discount['value']))/100;// aggiungo al totale sconti, lo sconto calcolato in percentuale
                $form['descri_discount'][]=$discount['title']." ".$discount['value']."%";// incremento la descrizione con lo sconto applicato
                $total_price_disc = $total_price-((floatval($total_price_disc)*floatval($discount['value']))/100);
              }else{
                $form['discountable_price'][]=$total_price_disc;
                $form['discount'][]= floatval($discount['value'])/floatval("1.".$gen_iva_perc);// aggiungo al totale sconti, lo sconto a valore scorporando IVA
                $form['descri_discount'][]= $discount['title']." ". number_format(floatval($discount['value'])/floatval("1.".$gen_iva_perc),3)."€";/// incremento la descrizione con lo sconto applicato
                $total_price_disc = $total_price-(floatval($discount['value'])/floatval("1.".$gen_iva_perc));
              }

              if ($discount['stop_further_processing']==1){// se questo deve bloccare i successivi eventuali, interrompo il conteggio
                break;
              }
            }
          }
        }

      }else{
        $form['discount']=0;
      }


      gaz_dbi_query ("UPDATE ".$gTables['artico']." SET `last_used`='".date("Y-m-d")."' WHERE codice='".$form['in_codart']."';");
      // addizione ai totali peso,pezzi,volume
      $form['net_weight'] += $form['in_quanti'] * $artico['peso_specifico'];
      $form['gross_weight'] += $form['in_quanti'] * $artico['peso_specifico'];
      if ($artico['pack_units'] > 0) {
          $form['units'] += intval(round($form['in_quanti'] / $artico['pack_units']));
      }
      $form['volume'] += $form['in_quanti'] * $artico['volume_specifico'];
      $form['in_good_or_service']=$artico['good_or_service'];

      // calcolo il numero di notti
      $diff=date_diff(date_create($form['start']),date_create($form['end']));
      $night= $diff->format("%a");

      // carico i dati del json articolo
      if ($form['in_codart']<>"TASSA-TURISTICA" && $data = json_decode($artico['custom_field'], TRUE)) { // se esiste un json nel custom field
        if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['accommodation_type'])){// se è un alloggio
            $form['codice_fornitore']=$artico['codice'];
            $form['in_accommodation_type'] = $data['vacation_rental']['accommodation_type'];
            $form['in_adult'] = $data['vacation_rental']['adult'];
            $form['in_child'] = $data['vacation_rental']['child'];
            $form['in_total_guests'] = $data['vacation_rental']['total_guests'];
            $form['in_fixquote'] = (isset($data['vacation_rental']['fixquote']))?$data['vacation_rental']['fixquote']:0;
            $form['tur_tax'] = $data['vacation_rental']['tur_tax'];
            $form['tur_tax_mode'] = $data['vacation_rental']['tur_tax_mode'];
            $form['id_agente'] = $data['vacation_rental']['agent'];// questo è il proprietario
            $form['in_facility_id'] = $artico['id_artico_group'];
        } elseif (isset($data['vacation_rental']['extra'])){// se è un extra
          $extra = gaz_dbi_get_row($gTables['rental_extra'], "id", $data['vacation_rental']['extra']);
          if ($extra['max_quantity']>0){ //e controllo se si supera il quantitativo massimo disponibile ammesso che ci sia
            $result=gaz_dbi_query("SELECT SUM(quanti) AS booked FROM ". $gTables['rental_events'] ." LEFT JOIN " . $gTables['rigbro'] . " ON " . $gTables['rental_events'] . ".id_rigbro = " . $gTables['rigbro'] . ".id_rig WHERE ((start >= '". $form['start'] ."' AND start <= '". $form['end']."') OR (start  <'". $form['start'] ."' AND (end <= '". $form['end']."' AND end > '". $form['start'] ."')) ) AND house_code = '".$form['extra'][$_POST['extra_submit']]."' ORDER BY id ASC LIMIT 0, 2000000");
            $res = $result->fetch_assoc();
            if (floatval($extra['max_quantity'])-floatval($res['booked'])-floatval($form['qtaextra'])<0){
               $msg .= "64+";// Overbooking
            }
          }
          $form['in_quanti']=$form['qtaextra'];
          $form['in_unimis']=$artico['unimis'];
          $form['qtaextra']=0;
          $form['in_accommodation_type'] = 1;// è un extra
          $form['in_adult'] = 0;
          $form['in_child'] = 0;
          $form['in_total_guests'] = 0;
          $form['in_fixquote'] = 0;
          $form['in_facility_id'] = 0;
          // calcolo il prezzo dell'extra

          switch ($extra['mod_prezzo']) {//0 => 'a prenotazione', 1 => 'a persona', 2 => 'a notte', 3 => 'a persona e a notte', 4 => 'cadauno'
            case "0":
              $form['in_prelis']=$artico['web_price'];
              break;
            case "1":
              $form['in_prelis']=floatval($artico['web_price'])*(intval($form['adult'])+intval($form['child']));
              break;
            case "2":
              $form['in_prelis']=floatval($artico['web_price'])*(intval($night));
              break;
            case "3":
              $form['in_prelis']=(floatval($artico['web_price'])*(intval($form['adult'])+intval($form['child'])))*(intval($night));
              break;
            case "4":
              $form['in_prelis']=$artico['web_price'];
              break;
          }

          $form['extra'][$_POST['extra_submit']]="";
        } else {
          $form['in_accommodation_type'] = 0;
          $form['in_adult'] = 0;
          $form['in_child'] = 0;
          $form['in_total_guests'] = 0;
          $form['in_fixquote'] = 0;
          $form['in_facility_id'] = 0;
        }
      } elseif ($form['in_codart']=="TASSA-TURISTICA") {//SE E' tassa turistica

        // calcolo prezzo tassa turistica
        switch ($form['tur_tax_mode']) {//0 => 'a persona', '1' => 'a persona escluso i minori', '2' => 'a notte', '3' => 'a notte escluso i minori'
          case "0":
            $tot_turtax=floatval($form['tur_tax'])*(intval($form['adult'])+intval($form['child']));
            $daytopay=0;
            break;
          case "1":
            $tot_turtax=floatval($form['tur_tax'])*(intval($form['adult']));
            $daytopay=0;
            break;
          case "2":
            if (strlen($form['tour_tax_from'])>2){//se ci sono impostazioni nella struttura
              $daytopay=tour_tax_daytopay($night,$form['start'],$form['end'],$form['tour_tax_from'],$form['tour_tax_to'],$form['tour_tax_day']);
            }else{// se non ci sono utilizzo tutte le notti
              $daytopay = $night;
            }
            $tot_turtax=(floatval($form['tur_tax'])*(intval($daytopay)))*(intval($form['adult'])+intval($form['child']));
            break;
          case "3":
            if (strlen($form['tour_tax_from'])>2){//se ci sono impostazioni nella struttura
              $daytopay=tour_tax_daytopay($night,$form['start'],$form['end'],$form['tour_tax_from'],$form['tour_tax_to'],$form['tour_tax_day']);
            }else{// se non ci sono utilizzo tutte le notti
              $daytopay = $night;
            }
            $tot_turtax=(floatval($form['tur_tax'])*(intval($daytopay)))*(intval($form['adult']));
            break;
          case "4":
            $tot_turtax=$form['tur_tax'];
            $daytopay=0;
            break;
				}
        $form['in_accommodation_type'] = 0;
        $form['in_adult'] = 0;
        $form['in_child'] = 0;
        $form['in_total_guests'] = 0;
        $form['in_fixquote'] = 0;
        $form['in_facility_id'] = 0;
        $form['in_prelis'] = $tot_turtax;

      } else {
        $form['in_accommodation_type'] = 0;
        $form['in_adult'] = 0;
        $form['in_child'] = 0;
        $form['in_total_guests'] = 0;
        $form['in_fixquote'] = 0;
        $form['in_facility_id'] = 0;
        $form['rows'][$next_row]['in_total_guests'] = 0;
        $form['rows'][$next_row]['in_facility_id'] = 0;
        $form['rows'][$next_row]['in_fixquote'] = 0;
      }

      if (substr($form['in_status'], 0, 6) == "UPDROW") { //se e' un rigo da modificare
            $old_key = intval(substr($form['in_status'], 6));
            $form['rows'][$old_key]['tiprig'] = $form['in_tiprig'];
            $form['rows'][$old_key]['id_doc'] = $form['in_id_doc'];
            $form['rows'][$old_key]['descri'] = $form['in_descri'];
            $form['rows'][$old_key]['id_mag'] = $form['in_id_mag'];
            $form['rows'][$old_key]['status'] = "UPDATE";
            $form['rows'][$old_key]['unimis'] = $form['in_unimis'];
            $form['rows'][$old_key]['quanti'] = $form['in_quanti'];
            $form['rows'][$old_key]['codart'] = $form['in_codart'];
            $form['rows'][$old_key]['good_or_service'] = $form['in_good_or_service'];
            $form['rows'][$old_key]['accommodation_type'] = $form['in_accommodation_type'];
            $form['rows'][$old_key]['adult'] = $form['in_adult'];
            $form['rows'][$old_key]['child'] = $form['in_child'];
            $form['rows'][$old_key]['total_guests'] = $form['in_total_guests'];
            $form['rows'][$old_key]['fixquote'] = $form['in_fixquote'];
            $form['rows'][$old_key]['facility_id'] = $form['in_facility_id'];
            $form['rows'][$old_key]['ritenuta'] = $form['in_ritenuta'];
            $form['rows'][$old_key]['provvigione'] = $form['in_provvigione'];
            $form['rows'][$old_key]['prelis'] = number_format($form['in_prelis'], $admin_aziend['decimal_price'], '.', '');
            $form['rows'][$old_key]['sconto'] = $form['in_sconto'];
            if ($artico['aliiva'] > 0) {
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
                $form['rows'][$old_key]['accommodation_type'] = "";
                $form['rows'][$old_key]['adult'] = 0;
                $form['rows'][$old_key]['child'] = 0;
                $form['rows'][$old_key]['total_guests'] = 0;
                $form['rows'][$old_key]['fixquote'] = 0;
                $form['rows'][$old_key]['facility_id'] = 0;
                $form['rows'][$old_key]['unimis'] = "";
                $form['rows'][$old_key]['quanti'] = 0;
                $form['rows'][$old_key]['sconto'] = 0;
            } elseif ($form['in_tiprig'] == 2 || $form['in_tiprig'] == 51) { //descrittivo o descrittivo con allegato
                $form['rows'][$old_key]['codart'] = "";
                $form['rows'][$old_key]['good_or_service'] = "";
                $form['rows'][$old_key]['accommodation_type'] = "";
                $form['rows'][$old_key]['adult'] = 0;
                $form['rows'][$old_key]['child'] = 0;
                $form['rows'][$old_key]['total_guests'] = 0;
                $form['rows'][$old_key]['fixquote'] = 0;
                $form['rows'][$old_key]['facility_id'] = 0;
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
                $form['rows'][$old_key]['accommodation_type'] = "";
                $form['rows'][$old_key]['adult'] = 0;
                $form['rows'][$old_key]['child'] = 0;
                $form['rows'][$old_key]['total_guests'] = 0;
                $form['rows'][$old_key]['fixquote'] = 0;
                $form['rows'][$old_key]['facility_id'] = 0;
                $form['rows'][$old_key]['quanti'] = "";
                $form['rows'][$old_key]['unimis'] = "";
                $form['rows'][$old_key]['sconto'] = 0;
            } elseif ($form['in_tiprig'] == 11 or $form['in_tiprig'] == 12 or $form['in_tiprig'] == 13) { //rigo fattura elettronica
                $form['rows'][$old_key]['codart'] = "";
                $form['rows'][$old_key]['good_or_service'] = "";
                $form['rows'][$old_key]['accommodation_type'] = "";
                $form['rows'][$old_key]['adult'] = 0;
                $form['rows'][$old_key]['child'] = 0;
                $form['rows'][$old_key]['total_guests'] = 0;
                $form['rows'][$old_key]['fixquote'] = 0;
                $form['rows'][$old_key]['facility_id'] = 0;
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
            $form['rows'][$next_row]['tiprig'] = $form['in_tiprig'];
            $form['rows'][$next_row]['id_doc'] = $form['in_id_doc'];
            $form['rows'][$next_row]['descri'] = $form['in_descri'];
            $form['rows'][$next_row]['id_mag'] = $form['in_id_mag'];
            $form['rows'][$next_row]['extdoc'] = 0;
            $form['rows'][$next_row]['status'] = "INSERT";
            $form['rows'][$next_row]['scorta'] = 0;
            $form['rows'][$next_row]['quamag'] = 0;
            if ($form['in_tiprig'] == 0) {  //rigo normale
                $form['rows'][$next_row]['codart'] = $form['in_codart'];
                $form['rows'][$next_row]['good_or_service'] = $form['in_good_or_service'];
                $form['rows'][$next_row]['accommodation_type'] = $form['in_accommodation_type'];
                $form['rows'][$next_row]['adult'] = $form['in_adult'];
                $form['rows'][$next_row]['child'] = $form['in_child'];
                $form['rows'][$next_row]['total_guests'] = $form['in_total_guests'];
                $form['rows'][$next_row]['fixquote'] = $form['in_fixquote'];
                $form['rows'][$next_row]['facility_id'] = $form['in_facility_id'];
                $form['rows'][$next_row]['annota'] = $artico['annota'];
                $form['rows'][$next_row]['pesosp'] = $artico['peso_specifico'];
                if($cliente['id_language']>1 && $translation=get_lang_translation($form['in_codart'], 'artico', $cliente['id_language'])){// se non è la lingua default, traduco
                  $form['rows'][$next_row]['descri'] = (($night>0)?$night." notti - ":"")."check-in ".gaz_format_date($form['start'])." check-out ".gaz_format_date($form['end'])." - ".$translation['descri'];
                }else{
                  $form['rows'][$next_row]['descri'] = (($night>0)?$night." notti - ":"")."check-in ".gaz_format_date($form['start'])." check-out ".gaz_format_date($form['end'])." - ".get_string_lang($artico['descri'], $lang);
                }
                if ($form['in_codart']=="TASSA-TURISTICA"){// se è il rigo della tassa turistica modifico la descrizione
                  $form['rows'][$next_row]['descri'] = (($daytopay>0)?"Tassa per ".$daytopay." notti - ":"")."check-in ".gaz_format_date($form['start'])." check-out ".gaz_format_date($form['end'])." - ".$artico['descri'];
                  $form['rows'][$next_row]['codice_fornitore']=$form['codice_fornitore'];
                }else{
                  $form['rows'][$next_row]['codice_fornitore']="";
                }
                $form['rows'][$next_row]['unimis'] = $artico['unimis'];
                $form['rows'][$next_row]['prelis'] = number_format($form['in_prelis'], $admin_aziend['decimal_price'], '.', '');
                $form['rows'][$next_row]['codric'] = $form['in_codric'];
                $form['rows'][$next_row]['quanti'] = $form['in_quanti'];
                $form['rows'][$next_row]['sconto'] = $form['in_sconto'];
                $in_sconto = $form['in_sconto'];
                if ($in_sconto != "#") {
                    $form['rows'][$next_row]['sconto'] = $in_sconto;
                } else {
                  if ($form["sconto"] > 0) { // gestione sconto cliente sul totale merce o sul rigo
                                $form['rows'][$next_row]['sconto'] = 0;
                  } elseif (isset($cliente)) {
                    $comp = new venditCalc();
                    $tmpPrezzoNetto_Sconto = $comp->trovaPrezzoNetto_Sconto($cliente['codice'], $form['rows'][$next_row]['codart'], $artico['sconto']);
                    if ($tmpPrezzoNetto_Sconto < 0) { // è un prezzo netto
                      $form['rows'][$next_row]['prelis'] = -$tmpPrezzoNetto_Sconto;
                      $form['rows'][$next_row]['sconto'] = 0;
                    } else {
                      $form['rows'][$next_row]['sconto'] = $tmpPrezzoNetto_Sconto;
                    }
                  }
                }
                $form['rows'][$next_row]['ritenuta'] = $form['in_ritenuta'];
                $provvigione = new Agenti;
                $form['rows'][$next_row]['provvigione'] = $provvigione->getPercent($form['id_tourOp'], $form['in_codart']);
                if (!isset($tmpPrezzoNetto_Sconto) or ( $tmpPrezzoNetto_Sconto >= 0)) { // non ho trovato un prezzo netto per il cliente/articolo
                    if ($form['listin'] == 2) {
                      if (floatval($artico['preve2'])<=0){
                        echo "ERRORE il cliente ha il listino ",$form['listin']," ma questo listino non ha un prezzo inserito";
                      }else{
                        $form['rows'][$next_row]['prelis'] = number_format($artico['preve2'], $admin_aziend['decimal_price'], '.', '');
                      }
                    } elseif ($form['listin'] == 3) {
                      if (floatval($artico['preve3'])<=0){
                        echo "ERRORE il cliente ha il listino ",$form['listin']," ma questo listino non ha un prezzo inserito";
                      }else{
                        $form['rows'][$next_row]['prelis'] = number_format($artico['preve3'], $admin_aziend['decimal_price'], '.', '');
                      }
                    } elseif ($form['listin'] == 4) {
                      if (floatval($artico['preve4'])<=0){
                        echo "ERRORE il cliente ha il listino ",$form['listin']," ma questo listino non ha un prezzo inserito";
                      }else{
                        $form['rows'][$next_row]['prelis'] = number_format($artico['preve4'], $admin_aziend['decimal_price'], '.', '');
                      }
                    } elseif ($form['listin'] == 5) {// prezzo web
                      if (floatval($artico['preve5'])<=0){
                        echo "ERRORE il cliente ha il listino ",$form['listin']," ma questo listino non ha un prezzo inserito";
                      }else{
                        $form['rows'][$next_row]['prelis'] = number_format($form['in_prelis'], $admin_aziend['decimal_price'], '.', '');
                      }
                    } else {
                        $form['rows'][$next_row]['prelis'] = number_format($form['in_prelis'], $admin_aziend['decimal_price'], '.', '');
                    }
                }

                $form['rows'][$next_row]['codvat'] = $admin_aziend['preeminent_vat'];
                $iva_azi = gaz_dbi_get_row($gTables['aliiva'], "codice", $admin_aziend['preeminent_vat']);
                $form['rows'][$next_row]['pervat'] = $iva_azi['aliquo'];
                $form['rows'][$next_row]['tipiva'] = $iva_azi['tipiva'];
                if (intval($artico['aliiva']) > 0) {
                    $form['rows'][$next_row]['codvat'] = $artico['aliiva'];
                    $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", intval($artico['aliiva']));
                    $form['rows'][$next_row]['pervat'] = $iva_row['aliquo'];
                    $form['rows'][$next_row]['tipiva'] = $iva_row['tipiva'];
                }
                if (intval($form['in_codvat']) > 0) {
                    $form['rows'][$next_row]['codvat'] = $form['in_codvat'];
                    $iva_row = gaz_dbi_get_row($gTables['aliiva'], "codice", intval($form['in_codvat']));
                    $form['rows'][$next_row]['pervat'] = $iva_row['aliquo'];
                    $form['rows'][$next_row]['tipiva'] = $iva_row['tipiva'];
                }

                if (intval($artico['codcon']) > 0) {
                    $form['rows'][$next_row]['codric'] = $artico['codcon'];
                    $form['in_codric'] = $artico['codcon'];
                }

                if ($form['in_codart']=="TASSA-TURISTICA") {//SE E' tassa turistica
                  $form['rows'][$next_row]['pervat']=0;// iva imposta turistica è esclusa art15
                }
                if (intval($form['id_agente'])>0){// se c'è un proprietario (visto che gli extra possono essere in comune fra i vari proprietari)
                  if (floatval($gen_iva_perc)==0){// e se l'IVA dell'alloggio è zero, vuol dire che il proprietario è un privato
                    $form['rows'][$next_row]['prelis'] = $form['rows'][$next_row]['prelis'] + (($form['rows'][$next_row]['prelis']*$form['rows'][$next_row]['pervat'])/100);// ci aggiungo l'IVA
                    $form['rows'][$next_row]['pervat']=0;// e forzo la percentuale iva dell'extra a zero
                    $form['rows'][$next_row]['codvat'] = $gen_iva_code;	// forzo anche il codice iva come quello dell'alloggio
                  }
                }
                $mv = $upd_mm->getStockValue(false, $form['in_codart'], $form['annemi'] . '-' . $form['mesemi'] . '-' . $form['gioemi'], $admin_aziend['stock_eval_method']);
                $magval = array_pop($mv);
                $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
                $form['rows'][$next_row]['scorta'] = $artico['scorta'];
                $form['rows'][$next_row]['quamag'] = $magval['q_g'];
                if ($artico['good_or_service']==2 and $tipo_composti['val']=="KIT") {
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
                        $form['rows'][$next_row]['status'] = "INSERT";
                        $form['rows'][$next_row]['scorta'] = 0;
                        $form['rows'][$next_row]['codart'] = $row2['codice'];
                        $form['rows'][$next_row]['good_or_service'] = $row2['good_or_service'];
                        $form['rows'][$next_row]['descri'] = $row2['descri'];
                        $form['rows'][$next_row]['unimis'] = $row2['unimis'];
                        $form['rows'][$next_row]['prelis'] = 0;
                        $form['rows'][$next_row]['quanti'] = $row_dis['quantita_artico_base'];
                        $form['rows'][$next_row]['id_doc'] = "";
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
            } elseif ($form['in_tiprig'] == 1 || $form['in_tiprig'] == 50) { //rigo forfait o normale con allegato
                $form['rows'][$next_row]['codart'] = "";
                $form['rows'][$next_row]['good_or_service'] = "";
                $form['rows'][$next_row]['accommodation_type'] = 0;
                $form['rows'][$next_row]['adult'] = 0;
                $form['rows'][$next_row]['child'] = 0;
                $form['rows'][$next_row]['total_guests'] = 0;
                $form['rows'][$next_row]['fixquote'] = 0;
                $form['rows'][$next_row]['facility_id'] = 0;
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
                $form['rows'][$next_row]['accommodation_type'] = 0;
                $form['rows'][$next_row]['adult'] = 0;
                $form['rows'][$next_row]['child'] = 0;
                $form['rows'][$next_row]['total_guests'] = 0;
                $form['rows'][$next_row]['fixquote'] = 0;
                $form['rows'][$next_row]['facility_id'] = 0;
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
                $form['rows'][$next_row]['accommodation_type'] = 0;
                $form['rows'][$next_row]['adult'] = 0;
                $form['rows'][$next_row]['child'] = 0;
                $form['rows'][$next_row]['total_guests'] = 0;
                $form['rows'][$next_row]['fixquote'] = 0;
                $form['rows'][$next_row]['facility_id'] = 0;
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
                $form['rows'][$next_row]['accommodation_type'] = 0;
                $form['rows'][$next_row]['adult'] = 0;
                $form['rows'][$next_row]['child'] = 0;
                $form['rows'][$next_row]['total_guests'] = 0;
                $form['rows'][$next_row]['fixquote'] = 0;
                $form['rows'][$next_row]['facility_id'] = 0;
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
            } elseif ($form['in_tiprig'] == 11 or $form['in_tiprig'] == 12 or $form['in_tiprig'] == 13) { //dati fattura elettronica
                $form['rows'][$next_row]['codart'] = "";
                $form['rows'][$next_row]['good_or_service'] = "";
                $form['rows'][$next_row]['accommodation_type'] = 0;
                $form['rows'][$next_row]['adult'] = 0;
                $form['rows'][$next_row]['child'] = 0;
                $form['rows'][$next_row]['total_guests'] = 0;
                $form['rows'][$next_row]['fixquote'] = 0;
                $form['rows'][$next_row]['facility_id'] = 0;
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


            if ($form['in_tiprig'] == 0) {   // è un rigo normale controllo se l'articolo ha avuto uno sconto

              if (is_array($form['discount']) && count($form['discount'])>0 && intval($form['in_accommodation_type'])>1) { // lo sconto c'è e non è un extra(accomodation_type>1)
                $ndis=0;
                $codvat=$form['rows'][$next_row]['codvat'];// prendo i codici iva dell'appartamento
                $pervat=$form['rows'][$next_row]['pervat'];
                $tipiva=$form['rows'][$next_row]['tipiva'];
                $provv=$form['rows'][$next_row]['provvigione'];
                foreach($form['discount'] as $dis){
                  $next_row++;
                  $form["row_$next_row"] = "";
                  $form['rows'][$next_row]['tiprig'] = 0;
                  $form['rows'][$next_row]['descri'] = 'Applicato '.$form['descri_discount'][$ndis].' su € '.number_format($form['discountable_price'][$ndis], $admin_aziend['decimal_price'], '.', '');
                  $form['rows'][$next_row]['id_mag'] = 0;
                  $form['rows'][$next_row]['id_lotmag'] = 0;
                  $form['rows'][$next_row]['identifier'] = '';
                  $form['rows'][$next_row]['cod_operazione'] = 11;
                  $form['rows'][$next_row]['recip_stocc'] = '';
                  $form['rows'][$next_row]['recip_stocc_destin'] = '';
                  $form['rows'][$next_row]['codice_fornitore'] = $form['in_codart'];
                  $form['rows'][$next_row]['lot_or_serial'] = 0;
                  $form['rows'][$next_row]['SIAN'] = 0;
                  $form['rows'][$next_row]['status'] = '';
                  $form['rows'][$next_row]['scorta'] = 0;
                  $form['rows'][$next_row]['quamag'] = 0;
                  $form['rows'][$next_row]['codart'] = '';
                  $form['rows'][$next_row]['annota'] = '';
                  $form['rows'][$next_row]['pesosp'] = '';
                  $form['rows'][$next_row]['gooser'] = 0;
                  $form['rows'][$next_row]['unimis'] = 'n';
                  $form['rows'][$next_row]['quanti'] = 1;
                  $form['rows'][$next_row]['prelis'] = number_format(-$dis, $admin_aziend['decimal_price'], '.', '');
                  $form['rows'][$next_row]['codric'] = 0;
                  $form['rows'][$next_row]['sconto'] = 0;
                  $form['rows'][$next_row]['pervat'] = $pervat;
                  $form['rows'][$next_row]['tipiva'] = $tipiva;
                  $form['rows'][$next_row]['ritenuta'] = 0;
                  $form['rows'][$next_row]['codvat'] = $codvat;
                  $form['rows'][$next_row]['provvigione'] = $provv;
                  $form['rows'][$next_row]['codric'] = $form['in_codric'];
                  $ndis++;
                }
              }
            }
            if ($form['in_tiprig'] == 0) {   // è un rigo normale controllo se l'articolo prevede un rigo testuale a seguire

              $article_text = gaz_dbi_get_row($gTables['company_config'], 'var', 'article_text');
              if ($article_text['val'] < 2){
                if ($cliente['id_language']>1 && $bodytext = get_lang_translation($form['in_codart'], 'artico', $cliente['id_language'])){// se non è richiesta la lingua di default, prendo la traduzione
                }else{
                  $bodytext = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", 'artico_' . $form['in_codart']);
                }
              } else {
                $bodytext = '';
              }
              if (!empty($bodytext) && !empty($bodytext['body_text'])) { // il testo aggiuntivo c'è (e non è vuoto)
                $next_row++;
                $form["row_$next_row"] = $bodytext['body_text'];
                $form['rows'][$next_row]['tiprig'] = 6;
                $form['rows'][$next_row]['descri'] = '';
                $form['rows'][$next_row]['id_mag'] = 0;
                $form['rows'][$next_row]['id_lotmag'] = 0;
                $form['rows'][$next_row]['identifier'] = '';
                $form['rows'][$next_row]['cod_operazione'] = 11;
                $form['rows'][$next_row]['recip_stocc'] = '';
                $form['rows'][$next_row]['recip_stocc_destin'] = '';
                $form['rows'][$next_row]['lot_or_serial'] = 0;
                $form['rows'][$next_row]['SIAN'] = 0;
                $form['rows'][$next_row]['status'] = '';
                $form['rows'][$next_row]['scorta'] = 0;
                $form['rows'][$next_row]['quamag'] = 0;
                $form['rows'][$next_row]['codart'] = '';
                $form['rows'][$next_row]['annota'] = '';
                $form['rows'][$next_row]['pesosp'] = '';
                $form['rows'][$next_row]['gooser'] = 0;
                $form['rows'][$next_row]['unimis'] = '';
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

        }
        // reinizializzo rigo di input tranne che per il tipo rigo e aliquota iva
        $form['in_descri'] = "";
        $form['in_codart'] = "";
        $form['in_good_or_service'] = "";
        $form['in_accommodation_type'] = 0;
        $form['in_adult'] = 0;
        $form['in_child'] = 0;
        $form['in_total_guests'] = 0;
        $form['in_fixquote'] = 0;
        $form['in_facility_id'] = 0;
        $form['in_unimis'] = "";
        $form['in_prelis'] = 0;
//      $form['in_sconto'] = '#';  non azzero il campo in_sconto (sconto rigo)
        $form['in_quanti'] = 1;
        $form['in_codric'] = "420000007";// prestazioni e servizi
        $form['in_id_mag'] = 0;
        $form['in_annota'] = "";
        $form['in_scorta'] = 0;
        $form['in_quamag'] = 0;
        $form['in_pesosp'] = 0;
        $form['in_status'] = "INSERT";
        // fine reinizializzo rigo input
        $form['cosear'] = "";
        $next_row++;
    } else if (isset($_POST['in_submit']) && strlen($form['in_codart'])>0){
		 $msg .= "62+";// mancano del date check-in e check-out
	}

    // Se viene inviata la richiesta di spostamento verso l'alto del rigo
    if (isset($_POST['upper_row'])) {
        $upp_key = key($_POST['upper_row']);
        if ($upp_key > 0) {
            $new_key = $upp_key - 1;
        } else {
            $new_key = $next_row - 1;
        }
        if (isset($form["row_$upp_key"])) { //se sto spostando un rigo testo
            $form["row_$new_key"] = $form["row_$upp_key"];
            unset($form["row_$upp_key"]);
        } elseif(isset($form["row_$new_key"]))  { //se lo sto spostando dove prima c'era un rigo testo
            $form["row_$upp_key"] = $form["row_$new_key"];
            unset($form["row_$new_key"]);
        }
        $updated_row = $form['rows'][$new_key];
        $form['rows'][$new_key] = $form['rows'][$upp_key];
        $form['rows'][$upp_key] = $updated_row;
        ksort($form['rows']);
        unset($updated_row);
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
        $form['codice_fornitore']="";
      }
        // fine sottrazione peso,pezzi,volume
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
        $next_row--;
    }
} elseif ((!isset($_POST['Update'])) and ( isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $tesbro = gaz_dbi_get_row($gTables['tesbro'], "id_tes", intval($_GET['id_tes']));
    $anagrafica = new Anagrafica();
    $cliente = $anagrafica->getPartner($tesbro['clfoco']);
    $form['indspe'] = $cliente['indspe'];
    if (isset($cliente['custom_field']) && $data = json_decode($cliente['custom_field'],true)){// se c'è un json in anagra
      if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental" lo aggiorno
        if (isset($data['vacation_rental']['points'])){
          $form['user_points']=$data['vacation_rental']['points'];
        }else{
           $form['user_points']=0;
        }
      }
    }else{
       $form['user_points']=0;
    }
    $rs_rig = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = " . intval($_GET['id_tes']), "id_rig asc");
    $id_des = $anagrafica->getPartner($tesbro['id_des']);
    $form['id_tes'] = intval($_GET['id_tes']);
    $form['hidden_req'] = '';
    $gen_iva_perc = 0;
    $gen_iva_code = 0;
    // inizio rigo di input
    $form['in_descri'] = "";
    $form['in_tiprig'] = 0;
    $form['in_id_doc'] = 0;
    /*   $form['in_artsea'] = $admin_aziend['artsea']; */
    $form['in_codart'] = "";
    $form['in_custom_field'] = "";
    $form['in_good_or_service'] = "";
    $form['in_accommodation_type'] = 0;
    $form['in_adult'] = 0;
    $form['in_child'] = 0;
    $form['in_total_guests'] = 0;
    $form['in_fixquote'] = 0;
    $form['in_facility_id'] = 0;
    $form['in_pervat'] = 0;
    $form['in_tipiva'] = 0;
    $form['in_ritenuta'] = 0;
    $form['in_unimis'] = "";
    $form['in_prelis'] = 0;
	// carico nel form i dati dell'evento alloggio
    $event = gaz_dbi_get_row($gTables['rental_events'], "id_tesbro", $form['id_tes'], " AND type = 'ALLOGGIO'");
    $form['adult'] = $event['adult'];
    $form['child'] = $event['child'];
    $form['start'] = $event['start'];
    $form['end'] = $event['end'];
    $form['access_code'] = $event['access_code'];
    //$form['extra'] = "";
    $form['qtaextra'] ="";
    $form['in_sconto'] = '#';
    /* fine modifica FP */
    $form['in_quanti'] = 1;
    $form['in_codvat'] = 0;
    $form['in_codric'] = "420000007";
    $form['in_id_mag'] = 0;
    $form['in_annota'] = "";
    $form['in_pesosp'] = 0;
    $form['in_scorta'] = 0;
    $form['in_quamag'] = 0;
    $form['in_extdoc'] = 0;
    $form['in_status'] = "INSERT";
    // fine rigo input
    $form['rows'] = array();
    // ...e della testata
    $form['search']['clfoco'] = substr($cliente['ragso1'], 0, 10);
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
    $form['email'] = $tesbro['email'];
    $form['numfat'] = $tesbro['numfat'];
    $form['datfat'] = $tesbro['datfat'];
    $form['clfoco'] = $tesbro['clfoco'];
    $form['pagame'] = $tesbro['pagame'];
    $form['change_pag'] = $tesbro['pagame'];
    $form['speban'] = $tesbro['speban'];
    $pagame = gaz_dbi_get_row($gTables['pagame'], "codice", $form['pagame']);
    if (isset($pagame) && ($pagame['tippag'] == 'B' or $pagame['tippag'] == 'T' or $pagame['tippag'] == 'V' or $pagame['tippag'] == 'K') and $cliente['speban'] == 'S') {
        $form['numrat'] = $pagame['numrat'];
    } else {
        $form['numrat'] = 1;
    }
    $form['banapp'] = $tesbro['banapp'];
    $form['weekday_repeat'] = $tesbro['weekday_repeat'];
    $form['vettor'] = $tesbro['vettor'];
    $form['id_tourOp'] = $tesbro['id_agente'];// questo è il tour operator
    $provvigione = new Agenti;
    $form['in_provvigione'] = $provvigione->getPercent($form['id_tourOp']);
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
    $next_row = 0;
    $ex=0;
    $lang="it";
    if (isset($cliente['id_language']) && intval($cliente['id_language'])>0 ){
      $lang=gaz_dbi_get_row($gTables['languages'], 'lang_id', $cliente['id_language'])['sef'];
    }
    while ($rigo = gaz_dbi_fetch_array($rs_rig)) {
        $articolo = gaz_dbi_get_row($gTables['artico'], "codice", $rigo['codart']);
        if ($rigo['id_body_text'] > 0) { //se ho un rigo testo
            $text = gaz_dbi_get_row($gTables['body_text'], "id_body", $rigo['id_body_text']);
            $form["row_$next_row"] = (isset($text['body_text']))?$text['body_text']:'';
            $form['rows'][$next_row]['good_or_service'] = "";
            $form['rows'][$next_row]['accommodation_type'] = "";
            $form['rows'][$next_row]['adult'] = 0;
            $form['rows'][$next_row]['child'] = 0;
            $form['rows'][$next_row]['total_guests'] = 0;
            $form['rows'][$next_row]['fixquote'] =0 ;
            $form['rows'][$next_row]['facility_id'] = 0;
            $form['rows'][$next_row]['annota'] = "";
            $form['rows'][$next_row]['scorta'] = 0;
            $form['rows'][$next_row]['pesosp'] = 0;
            $form['rows'][$next_row]['tipiva'] = "";
        }else{
          $iva_row = gaz_dbi_get_row($gTables['aliiva'], 'codice', $rigo['codvat']);

          if (isset ($articolo) && $data = json_decode($articolo['custom_field'], TRUE)) { // se esiste un json nel custom field
            if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['accommodation_type'])){// è un alloggio
              $form['extra'][$ex]="";$ex++;// inizializzo a niente il valore dell'input extra
              $form['rows'][$next_row]['accommodation_type'] = $data['vacation_rental']['accommodation_type'];
              $form['rows'][$next_row]['adult'] = $data['vacation_rental']['adult'];
              $form['rows'][$next_row]['child'] = $data['vacation_rental']['child'];
              $form['rows'][$next_row]['total_guests'] = $data['vacation_rental']['total_guests'];
              $form['rows'][$next_row]['fixquote']=(isset($data['vacation_rental']['fixquote']))?$data['vacation_rental']['fixquote']:0;
              $form['codice_fornitore'] = $articolo['codice'];
              $form['tur_tax'] = $data['vacation_rental']['tur_tax'];
              $form['tur_tax_mode'] = $data['vacation_rental']['tur_tax_mode'];
              $form['id_agente'] = $data['vacation_rental']['agent'];// questo è il proprietario
              $gen_iva_perc = $iva_row['aliquo'];
              $gen_iva_code = $rigo['codvat'];

              if (intval ($articolo['id_artico_group'])>0){// se l'alloggio fa parte di una struttura
                $facility = gaz_dbi_get_row($gTables['artico_group'], "id_artico_group", $articolo['id_artico_group']);// leggo la struttura
                if ($data = json_decode($facility['custom_field'], TRUE)) { // se esiste un json nel custom field della struttura
                  if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['facility_type'])){// se è una struttura prendo i dati che mi serviranno
                    $form['minor']=(isset($data['vacation_rental']['minor']))?$data['vacation_rental']['minor']:'12';// se non c'è l'età dei minori la imposto a 12 anni d'ufficio
                    $form['tour_tax_from']=(isset($data['vacation_rental']['tour_tax_from']))?$data['vacation_rental']['tour_tax_from']:'';
                    $form['tour_tax_to']=(isset($data['vacation_rental']['tour_tax_to']))?$data['vacation_rental']['tour_tax_to']:'';
                    $form['tour_tax_day']=(isset($data['vacation_rental']['tour_tax_day']))?$data['vacation_rental']['tour_tax_day']:'';
                    $form['max_booking_days']=(isset($data['vacation_rental']['max_booking_days']))?$data['vacation_rental']['max_booking_days']:'';
                    $form['rows'][$next_row]['facility_id'] = $articolo['id_artico_group'];
                  }
                }
              } else{
                $form['minor']='';
                $form['tour_tax_from']='';
                $form['tour_tax_to']='';
                $form['tour_tax_day']='';
                $form['max_booking_days']='';
                $form['rows'][$next_row]['facility_id'] = 0;
              }

            } elseif (is_array($data['vacation_rental']) && isset($data['vacation_rental']['extra'])){
              $form['rows'][$next_row]['accommodation_type'] = 1;// è un extra
              $form['rows'][$next_row]['adult'] = 0;
              $form['rows'][$next_row]['child'] = 0;
              $form['rows'][$next_row]['total_guests'] = 0;
              $form['rows'][$next_row]['fixquote'] = 0;
              $form['rows'][$next_row]['facility_id'] = 0;
            } else {
              $form['rows'][$next_row]['accommodation_type'] = 0;
              $form['rows'][$next_row]['adult'] = 0;
              $form['rows'][$next_row]['child'] = 0;
              $form['rows'][$next_row]['total_guests'] = 0;
              $form['rows'][$next_row]['fixquote'] = 0;
              $form['rows'][$next_row]['facility_id'] = 0;
            }
          } else {
            $form['rows'][$next_row]['accommodation_type'] = 0;
            $form['rows'][$next_row]['adult'] = 0;
            $form['rows'][$next_row]['child'] = 0;
            $form['rows'][$next_row]['total_guests'] = 0;
            $form['rows'][$next_row]['fixquote'] = 0;
            $form['rows'][$next_row]['facility_id'] = 0;
          }

          $form['rows'][$next_row]['good_or_service'] = (isset($articolo['good_or_service']))?$articolo['good_or_service']:1;
          $form['rows'][$next_row]['annota'] = (isset($articolo['annota']))?$articolo['annota']:0;
          $form['rows'][$next_row]['scorta'] = (isset($articolo['scorta']))?$articolo['scorta']:'';
          $form['rows'][$next_row]['pesosp'] = (isset($articolo['peso_specifico']))?$articolo['peso_specifico']:0;
          $form['rows'][$next_row]['tipiva'] = (isset($iva_row['tipiva']))?$iva_row['tipiva']:'';
        }

        $form['rows'][$next_row]['descri'] = $rigo['descri'];
        $form['rows'][$next_row]['tiprig'] = $rigo['tiprig'];
        $form['rows'][$next_row]['id_doc'] = $rigo['id_doc'];
        $form['rows'][$next_row]['codart'] = $rigo['codart'];
        $form['rows'][$next_row]['codice_fornitore'] = $rigo['codice_fornitore'];
        $form['rows'][$next_row]['pervat'] = $rigo['pervat'];
        $form['rows'][$next_row]['ritenuta'] = $rigo['ritenuta'];
        $form['rows'][$next_row]['unimis'] = $rigo['unimis'];
        $form['rows'][$next_row]['prelis'] = number_format($rigo['prelis'], $admin_aziend['decimal_price'], '.', '');
        $form['rows'][$next_row]['sconto'] = $rigo['sconto'];
        $form['rows'][$next_row]['quanti'] = gaz_format_quantity($rigo['quanti'], 0, $admin_aziend['decimal_quantity']);
        $form['rows'][$next_row]['codvat'] = $rigo['codvat'];
        $form['rows'][$next_row]['codric'] = $rigo['codric'];
        $form['rows'][$next_row]['provvigione'] = $rigo['provvigione'];
        $form['rows'][$next_row]['id_mag'] = $rigo['id_mag'];
        $mv = $upd_mm->getStockValue(false, $rigo['codart'], "", $admin_aziend['stock_eval_method']);
        $magval = array_pop($mv);
        $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
        $form['rows'][$next_row]['quamag'] = $magval['q_g'];
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
    $gen_iva_perc = 0;
	$gen_iva_code = 0;
    $form['id_tes'] = "";
    $form['start'] = "";
    $form['end'] = "";
    $form['adult'] = 0;
    $form['child'] = 0;
    $form['access_code'] = '';
    $form['minor'] = '12';// di defaul imposto l'età dei minori a 12 anni; si cambierà in automatico qualora impostata nella struttura
    $form['tour_tax_from'] = "";
    $form['tour_tax_to'] = "";
    $form['tour_tax_day'] = "";
    $form['max_booking_days'] = "";
    $form['extra'] = [];
    $form['qtaextra'] = 0;
    $form['tur_tax'] =0;
    $form['tur_tax_mode'] =0;
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
    $form['in_accommodation_type'] = "";
    $form['in_adult'] = 0;
    $form['in_child'] = 0;
    $form['in_total_guests'] = 0;
    $form['in_fixquote'] = 0;
    $form['in_facility_id'] = 0;
    $form['in_pervat'] = "";
    $form['in_tipiva'] = "";
    $form['in_ritenuta'] = 0;
    $form['in_unimis'] = "";
    $form['in_prelis'] = 0.000;
    /** inizio modifica FP 09/10/2015
     * inizializzo il campo con '#' per indicare che voglio lo sconto standard dell'articolo
     */
//rimossa    $form['in_sconto'] = 0;
    $form['in_sconto'] = '#';
    /* fine modifica FP */
    $form['in_quanti'] = 1;
    $form['in_codvat'] = 0;
    $form['in_provvigione'] = 0;
    $form['in_id_mag'] = 0;
    $form['in_annota'] = "";
    $form['in_scorta'] = 0;
    $form['in_quamag'] = 0;
    $form['in_pesosp'] = 0;
    $form['in_extdoc'] = 0;
    $form['in_status'] = "INSERT";
    $form['in_codric'] = "420000007";// prestazioni e servizi
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
    $form['email'] = "";
    $form['numfat'] = "";
    $form['datfat'] = "";
    $form['clfoco'] = "";
    $form['pagame'] = "";
    $form['change_pag'] = "";
    $form['banapp'] = "";
    $form['vettor'] = "";
    $form['id_agente'] = 0;
    $form['id_tourOp'] = 0;
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
    $form['user_points'] = 0;
	$ultimoprezzo=''; //info sugli ultimi prezzi
}

require("../../library/include/header.php");
?>
<style>
* {
  box-sizing: border-box;
}

/* Create three equal columns that floats next to each other */
.column {
  float: left;
  width: 33.33%;
  padding: 10px;
}

/* Clear floats after the columns */
.row:after {
  content: "";
  display: table;
  clear: both;
}

#search_cosear {
	width:auto !important;
	background-color: inherit;
}

/* Responsive layout - makes the three columns stack on top of each other instead of next to each other */
@media screen and (max-width: 980px) {
  .column {
    width: 100%;
  }
}
</style>
<?php
require("./lang." . $admin_aziend['lang'] . ".php");
$script_transl = $strScript["admin_booking.php"] + HeadMain(0, array(/* 'tiny_mce/tiny_mce', */
            /* 'boxover/boxover', */
            'calendarpopup/CalendarPopup',
            'custom/autocomplete',
            /* 'jquery/toast/javascript/jquery.toastmessage', */
            'custom/miojs'
                /** ENRICO FEDELE */
                /* 'jquery/jquery-1.7.1.min',
                  'jquery/ui/jquery.ui.core',
                  'jquery/ui/jquery.ui.widget',
                  'jquery/ui/jquery.ui.position',
                  'jquery/ui/jquery.ui.autocomplete', */
                /**/
                /** ENRICO FEDELE */                ));
if ($form['id_tes'] > 0) {
    $title = ucfirst($script_transl[$toDo] . $script_transl[0][$form['tipdoc']]) . " n." . $form['numdoc'];
} else {
    $title = ucfirst($script_transl[$toDo] . $script_transl[0][$form['tipdoc']]);
}
echo '<script type="text/javascript">';
if ( empty($msg) && !isset($_POST['ins']) && $scorrimento == '1' ) { // se ho un errore non scrollo
	if (!empty($_POST['last_focus'])){
		$idlf='#'.$_POST['last_focus'];
		$_POST['last_focus']='';
	} else {
		$idlf="#search_cosear";
	}
	echo '
	$( function() {
				$("html, body").delay(100).animate({scrollTop: $("'.$idlf.'").offset().top-100}, 100);
				}); ';
}

echo "
function pulldown_menu(selectName, destField)
{
    // Create a variable url to contain the value of the
    // selected option from the the form named broven and variable selectName
    var url = document.broven[selectName].options[document.broven[selectName].selectedIndex].value;
    document.broven[destField].value = url;
}";
echo "
   function preStampa() // stampa il dettaglio del preventivo senza salvarlo
    {
        var mywindow = window.open('', 'my div', 'height=400,width=600');
        mywindow.document.write('<html><head><title>Stampa</title>');

        mywindow.document.write('</head><body >');
        //alert($('[name=\"change\"]').val());
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
";
?>
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
<?php
echo "</script>\n";
?>
<script LANGUAGE="JavaScript" ID="datapopup">
    var cal = new CalendarPopup();
    cal.setReturnFunction("setMultipleValues");
    function setMultipleValues(y, m, d) {
        document.broven.anntra.value = y;
        document.broven.mestra.value = LZ(m);
        document.broven.giotra.value = LZ(d);
    }

</script>
<?php

/******************************************************

             I N I Z I O   P A G I N A

******************************************************/
echo "<form method=\"POST\" name=\"broven\" enctype=\"multipart/form-data\">\n";
?>
<div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
  <div class="col-lg-12">
    <div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
      <div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
    </div>
    <iframe id="framePdf"  style="height: 100%; width: 100%" src="../../library/images/wait_spinner.html"></iframe>
</div>
<?php
$gForm = new venditForm();
echo '	<input type="hidden" name="' . ucfirst($toDo) . '" value="" />
		<input type="hidden" value="' . $form['id_tes'] . '" name="id_tes" />
    <input type="hidden" value="' . $gen_iva_perc . '" name="gen_iva_perc" />
	    <input type="hidden" value="' . $gen_iva_code . '" name="gen_iva_code" />
		<input type="hidden" value="' . $form['indspe'] . '" name="indspe" />
    <input type="hidden" value="' . $form['user_points'] . '" name="user_points" />
		<input type="hidden" value="' . $form['tipdoc'] . '" name="tipdoc" />
		<input type="hidden" value="' . $form['ritorno'] . '" name="ritorno" />
		<input type="hidden" value="' . $form['change_pag'] . '" name="change_pag" />
		<input type="hidden" value="' . $form['protoc'] . '" name="protoc" />
    <input type="hidden" value="' . $form['email'] . '" name="email" />
		<input type="hidden" value="' . $form['numdoc'] . '" name="numdoc" />
		<input type="hidden" value="' . $form['numfat'] . '" name="numfat" />
		<input type="hidden" value="' . $form['datfat'] . '" name="datfat" />
    <input type="hidden" value="' . ((isset($form['codice_fornitore']))?$form['codice_fornitore']:"") . '" name="codice_fornitore" />
    <input type="hidden" value="' . ((isset($form['tur_tax']))?$form['tur_tax']:"") . '" name="tur_tax" />
    <input type="hidden" value="' . ((isset($form['tur_tax_mode']))?$form['tur_tax_mode']:"") . '" name="tur_tax_mode" />
		<input type="hidden" value="' . (isset($_POST['last_focus']) ? $_POST['last_focus'] : "") . '" name="last_focus" />
    <input type="hidden" value="' . $form['minor'] . '" name="minor" />
    <input type="hidden" value="' . $form['tour_tax_from'] . '" name="tour_tax_from" />
    <input type="hidden" value="' . $form['tour_tax_to'] . '" name="tour_tax_to" />
    <input type="hidden" value="' . $form['tour_tax_day'] . '" name="tour_tax_day" />
    <input type="hidden" value="' . $form['max_booking_days'] . '" name="max_booking_days" />
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
				<td class="FacetDataTD">
					<select name="listin" class="FacetSelect">';
for ($lis = 1; $lis <= 5; $lis++) {
    $selected = "";
    if ($form['listin'] == $lis) {
        $selected = ' selected=""';
    }
    echo '					<option value="' . $lis . '"' . $selected . '>' . $lis . '</option>';
}
echo '				</select>
				</td>
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
					<input type="text" value="' . $form['day_of_validity'] . '" name="day_of_validity" maxlength="3" />
          <input type="hidden" value="' . $form['delivery_time'] . '" name="delivery_time" />
        </td>';

echo "<td class=\"FacetFieldCaptionTD\">Tour operator</td>";
echo "<td  class=\"FacetDataTD\">\n";
$select_tourOp = new selectAgente("id_tourOp");
$select_tourOp->addSelected($form["id_tourOp"]);
$select_tourOp->output();

echo'</td>
			</tr>
			<tr>
			<td class="FacetFieldCaptionTD" title="' . $script_transl['speban_title'] . '">' . $script_transl['speban'] . '</td>
			<td class="FacetDataTD" title="' . $script_transl['speban_title'] . '">
				<input type="text" value="' . $form['speban'] . '" name="speban" maxlength="6" onchange="this.form.submit()" /> x ' . $form['numrat'] . '
			</td>
			<td class="FacetFieldCaptionTD"></td>';

        echo "<td class=\"FacetDataTD\">";
        echo "<input type=\"hidden\" name=\"id_des_same_company\" value=\"" . $form['id_des_same_company'] . "\">
						<input type=\"hidden\" name=\"id_des\" value=\"" . $form['id_des'] . "\">
						<input type=\"hidden\" name=\"destin\" value=\"" . $form['destin'] . "\"></td>\n";

echo "<td class=\"FacetFieldCaptionTD\">Proprietario</td>";
echo "<td  class=\"FacetDataTD\">\n";
$select_agente = new selectAgente("id_agente");
$select_agente->addSelected($form["id_agente"]);
$select_agente->output();
echo '		</td>
		</tr>
	  </table></div>';
echo '<div class="table-responsive">
	  <table name="elenco" class="Tlarge table table-striped table-bordered table-condensed">
		<thead>
			<tr>
				<td class="FacetFieldCaptionTD">' . $script_transl[20] . '</td>
				<td class="FacetFieldCaptionTD" colspan="2">' . $script_transl[21] . '</td>
				<td class="FacetFieldCaptionTD">' . $script_transl[22] . '</td>
                <td class="FacetFieldCaptionTD">' . $script_transl[16] . '</td>
                <td class="FacetFieldCaptionTD">' . $script_transl[23] . '</td>
				<td class="FacetFieldCaptionTD">%' . substr($script_transl[24], 0, 2) . '.</td>
				<td class="FacetFieldCaptionTD"></td>
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



foreach ($form['rows'] as $k => $v) {
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
        $rit += round($imprig * floatval($v['ritenuta']) / 100, 2);
    } elseif ($v['tiprig'] == 3) {
        $carry += $v['prelis'];
    }

    $descrizione = htmlentities(get_string_lang($v['descri'], $lang), ENT_QUOTES);
    echo "<input type=\"hidden\" value=\"" . $v['codart'] . "\" name=\"rows[$k][codart]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['accommodation_type'])) ? $v['accommodation_type'] : '') . "\" name=\"rows[$k][accommodation_type]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['adult'])) ? $v['adult'] : '') . "\" name=\"rows[$k][adult]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['child'])) ? $v['child'] : '') . "\" name=\"rows[$k][child]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['total_guests'])) ? $v['total_guests'] : '') . "\" name=\"rows[$k][total_guests]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['fixquote'])) ? $v['fixquote'] : '') . "\" name=\"rows[$k][fixquote]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['codice_fornitore'])) ? $v['codice_fornitore'] : '') . "\" name=\"rows[$k][codice_fornitore]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['facility_id'])) ? $v['facility_id'] : '') . "\" name=\"rows[$k][facility_id]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['good_or_service'])) ? $v['good_or_service'] : '') . "\" name=\"rows[$k][good_or_service]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['status'] . "\" name=\"rows[$k][status]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['tiprig'] . "\" name=\"rows[$k][tiprig]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['id_doc'])) ? $v['id_doc'] : '') . "\" name=\"rows[$k][id_doc]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['codvat'] . "\" name=\"rows[$k][codvat]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['pervat'] . "\" name=\"rows[$k][pervat]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['tipiva'] . "\" name=\"rows[$k][tipiva]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['ritenuta'] . "\" name=\"rows[$k][ritenuta]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['codric'] . "\" name=\"rows[$k][codric]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['id_mag'] . "\" name=\"rows[$k][id_mag]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['annota'] . "\" name=\"rows[$k][annota]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['scorta'] . "\" name=\"rows[$k][scorta]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['quamag'] . "\" name=\"rows[$k][quamag]\">\n";
    echo "<input type=\"hidden\" value=\"" . $v['pesosp'] . "\" name=\"rows[$k][pesosp]\">\n";
    echo "<input type=\"hidden\" value=\"" . ((isset($v['extdoc'])) ? $v['extdoc'] : '') . "\" name=\"rows[$k][extdoc]\">\n";
    //stampo i rows in modo diverso a secondo del tipo
    echo "<tr>";
    switch ($v['tiprig']) {
        case "0":

				$btn_class = 'btn-info';
				$btn_title = " Senza magazzino";

            /* Peso */
            $peso = 0;

            echo '	<td>
					<button type="image" name="upper_row[' . $k . ']" class="btn btn-default btn-xs" title="' . $script_transl['3'] . '!">
						<i class="glyphicon glyphicon-arrow-up"></i>
					</button>
			  	</td>
                                <td title="' . $script_transl['update'] . $script_transl['thisrow'] . '!' . $btn_title . '">
					<button name="upd_row[' . $k . ']" class="btn btn-xs ' . $btn_class . ' btn-block" type="submit">
						<i class="glyphicon glyphicon-refresh"></i>&nbsp;' . $v['codart'] . '
					</button>
			 	</td>
				<td>
		 			<input class="gazie-tooltip" data-type="product-thumb" data-id="' . $v["codart"] . '" data-title="' . $v['annota'] . '" type="text" name="rows[' . $k . '][descri]" value="' . $descrizione . '" maxlength="100" />
			   	</td>
			    <td>
					<input readonly  type="text" name="rows[' . $k . '][unimis]" value="' . $v['unimis'] . '" maxlength="3" />
				</td>
				<td>
					<input readonly  type="text" name="rows[' . $k . '][quanti]" value="' . $v['quanti'] . '" align="right" maxlength="11" id="righi_' . $k . '_quanti"  />
                </td>';
            echo "<td><input type=\"text\" name=\"rows[$k][prelis]\" value=\"" . $v['prelis'] . "\" align=\"right\" maxlength=\"11\" ";
			if ($vp>0) { // solo se scelto in configurazione avanzata azienda si vedrà il dialog per mettere il prezzo iva compresa
				echo ' onclick="vatPrice(\''.$k.'\',\''.$v['pervat'].'\');"';
			}
            echo " id=\"righi_" . $k . "_prelis\" onchange=\"document.broven.last_focus.value='righi_" . $k . "_sconto'; this.form.submit()\" /></td>\n";
            echo "<td><input type=\"text\" name=\"rows[$k][sconto]\" value=\"" . $v['sconto'] . "\" maxlength=\"6\"  id=\"righi_" . $k . "_sconto\" onchange=\"document.broven.last_focus.value=this.id; this.form.submit()\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][provvigione]\" value=\"" . $v['provvigione'] . "\" maxlength=\"6\"  /></td>\n";
            echo "<td class=\"text-right\">" . gaz_format_number($imprig) . "</td>\n";
            echo "<td class=\"text-right\">" . $v['pervat'] . "%</td>\n";
            echo "<td class=\"text-right codricTooltip\" title=\"Contropartita\">" . $v['codric'] . "</td>\n";

            $last_row[] = array_unshift($last_row, '<strong>' . $v['codart'] . '</strong>, ' . $v['descri'] . ', ' . $v['quanti'] . $v['unimis'] . ', <strong>' . $script_transl[23] . '</strong>: ' . gaz_format_number($v['prelis']) . ', %<strong>' . substr($script_transl[24], 0, 2) . '</strong>: ' . gaz_format_number($v['sconto']) . ', <strong>' . $script_transl[25] . '</strong>: ' . gaz_format_number($imprig) . ', <strong>' . $script_transl[19] . '</strong>: ' . $v['pervat'] . '%, <strong>' . $script_transl[18] . '</strong>: ' . $v['codric']);
            break;
        case "1":
            echo '		<td>
						<button type="image" name="upper_row[' . $k . ']" class="btn btn-default btn-xs" title="' . $script_transl['3'] . '!">
							<i class="glyphicon glyphicon-arrow-up"></i>
						</button>
					</td>'
            . '<td title="' . $script_transl['update'] . $script_transl['thisrow'] . '!">
              			<input class="FacetDataTDsmall" type="submit" name="upd_row[' . $k . ']" value="' . $script_transl['typerow'][$v['tiprig']] . '" />
					</td>
			  		<td>
		 				<input type="text" name="rows[' . $k . '][descri]" value="' . $descrizione . '" maxlength="100" />
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
						<input style="text-align:right" type="text" name="rows[' . $k . '][prelis]" value="' . number_format($v['prelis'], 2, '.', '') . '" align="right" maxlength="11" ';
						if ($vp>0) { // solo se scelto in configurazione avanzata azienda si vedrà il dialog per mettere il prezzo iva compresa
							echo ' onclick="vatPrice(\''.$k.'\',\''.$v['pervat'].'\');"';
						}
						echo ' id="righi_' . $k . '_prelis" onchange="document.broven.last_focus.value=this.id; this.form.submit()" />
					</td>
					<td class="text-right">' . $v['pervat'] . '%</td>
					<td class="text-right codricTooltip" title="Contropartita">' . $v['codric'] . '</td>';
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']]);
            break;
        case "2": // descrittivo
            echo "	<td>
				<button type=\"image\" name=\"upper_row[" . $k . "]\" class=\"btn btn-default btn-xs\" title=\"" . $script_transl['3'] . "!\">
						<i class=\"glyphicon glyphicon-arrow-up\"></i>
				</button>
			</td>
                        <td title=\"" . $script_transl['update'] . $script_transl['thisrow'] . "!\">
                                <input class=\"FacetDataTDsmall\" type=\"submit\" name=\"upd_row[$k]\" value=\"" . $script_transl['typerow'][$v['tiprig']] . "\" />
			</td>
			<td>
				<input type=\"text\" name=\"rows[$k][descri]\" value=\"$descrizione\" maxlength=\"100\"  />
			</td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][unimis]\" value=\"\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][quanti]\" value=\"\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][prelis]\" value=\"\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][sconto]\" value=\"\" /></td>\n";
            echo "<td></td>\n";
            echo "<td></td>\n";
            echo "<td></td>\n";
            echo "<td></td>\n";
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']]);
            break;
        case "3":
            echo "	<td title=\"" . $script_transl['update'] . $script_transl['thisrow'] . "!\">
              		<input class=\"FacetDataTDsmall\" type=\"submit\" name=\"upd_row[$k]\" value=\"" . $script_transl['typerow'][$v['tiprig']] . "\" />
				</td>
			  	<td>
		 			<input type=\"text\" name=\"rows[$k][descri]\" value=\"$descrizione\" maxlength=\"100\" >
				</td>
				<td>
					<button type=\"image\" name=\"upper_row[" . $k . "]\" class=\"btn btn-default btn-xs\" title=\"" . $script_transl['3'] . "!\">
						<i class=\"glyphicon glyphicon-arrow-up\"></i>
					</button>
				</td>
				<td><input type=\"hidden\" name=\"rows[$k][unimis]\" value=\"\" /></td>
                <td><input type=\"hidden\" name=\"rows[$k][quanti]\" value=\"\" /></td>
				<td><input type=\"hidden\" name=\"rows[$k][sconto]\" value=\"\" /></td>
				<td></td>
				<td></td>
				<td class=\"text-right\"><input type=\"text\" name=\"rows[$k][prelis]\" value=\"" . $v['prelis'] . "\" align=\"right\" maxlength=\"11\"  /></td>
				<td></td>
				<td></td>\n";
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']]);
            break;
        case "6":
        case "7":
        case "8":
            /**
              <textarea id="row_'.$k.'" name="row_'.$k.'" class="mceClass'.$k.'" style="width:100%;height:100px;">'.$form["row_$k"].'</textarea>
             */
            echo '	<td title="' . $script_transl['update'] . $script_transl['thisrow'] . '!">
					<button type="image" name="upper_row[' . $k . ']" class="btn btn-default btn-xs" title="' . $script_transl['3'] . '!">
						<i class="glyphicon glyphicon-arrow-up"></i>
					</button>
		 			<!-- <input class="FacetDataTDsmall" type="submit" name="upd_row[' . $k . ']" value="' . $script_transl['typerow'][$v['tiprig']] . '" /> -->
				</td>
				<td colspan="10">
					<textarea id="row_' . $k .
                '" name="row_' . $k .
                '" class="mceClass" style="width:100%;height:100px;">'
                . $form["row_$k"] . '</textarea>
				</td>
				<input type="hidden" value="" name="rows[' . $k . '][descri]" />
				<input type="hidden" value="" name="rows[' . $k . '][unimis]" />
                <input type="hidden" value="" name="rows[' . $k . '][quanti]" />
				<input type="hidden" value="" name="rows[' . $k . '][prelis]" />
				<input type="hidden" value="" name="rows[' . $k . '][sconto]" />
				<input type="hidden" value="" name="rows[' . $k . '][provvigione]" />';
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']]);
            break;
        case "11": // CIG fattura PA
        case "12": // CUP fattura PA
            echo "	<td>
						<button type=\"image\" name=\"upper_row[" . $k . "]\" class=\"btn btn-default btn-xs\" title=\"" . $script_transl['3'] . "!\">
							<i class=\"glyphicon glyphicon-arrow-up\"></i>
						</button>
					</td>
					<td title=\"" . $script_transl['update'] . $script_transl['thisrow'] . "!\">
						<input class=\"btn btn-xs btn-success btn-block\" type=\"submit\" name=\"upd_row[$k]\" value=\"" . $script_transl['typerow'][$v['tiprig']] . "\" />
					</td>
					<td>
						<input type=\"text\"   name=\"rows[$k][descri]\" value=\"$descrizione\" maxlength=\"100\"  />
					</td>
					<td><input type=\"hidden\" name=\"rows[$k][unimis]\" value=\"\" /></td>
                    <td><input type=\"hidden\" name=\"rows[$k][quanti]\" value=\"\" /></td>
					<td><input type=\"hidden\" name=\"rows[$k][prelis]\" value=\"\" /></td>
					<td><input type=\"hidden\" name=\"rows[$k][sconto]\" value=\"\" /></td>
					<td><input type=\"hidden\" name=\"rows[$k][provvigione]\" value=\"\" /></td>
					<td></td>
					<td></td>
					<td></td>\n";
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']]);
            break;

        case "13": // ID documento fattura PA
            echo "	<td>
						<button type=\"image\" name=\"upper_row[" . $k . "]\" class=\"btn btn-default btn-xs\" title=\"" . $script_transl['3'] . "!\">
							<i class=\"glyphicon glyphicon-arrow-up\"></i>
						</button>
					</td>
                                        <td title=\"" . $script_transl['update'] . $script_transl['thisrow'] . "!\">
              			<input class=\"btn btn-xs btn-success btn-block\" type=\"submit\" name=\"upd_row[$k]\" value=\"" . $script_transl['typerow'][$v['tiprig']] . "\" />
					</td>
					<td>
						<input type=\"text\"   name=\"rows[$k][descri]\" value=\"$descrizione\" maxlength=\"100\"  />
					</td>
					<td><input type=\"hidden\" name=\"rows[$k][unimis]\" value=\"\" /></td>
                    <td><input type=\"hidden\" name=\"rows[$k][quanti]\" value=\"\" /></td>
					<td><input type=\"hidden\" name=\"rows[$k][prelis]\" value=\"\" /></td>
					<td><input type=\"hidden\" name=\"rows[$k][sconto]\" value=\"\" /></td>
					<td><input type=\"hidden\" name=\"rows[$k][provvigione]\" value=\"\" /></td>
					<td></td>
					<td></td>
					<td></td>\n";
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']]);
            break;
        case "50":
			echo "<td><button type=\"image\" name=\"upper_row[" . $k . "]\" class=\"btn btn-default btn-sm\" title=\"" . $script_transl['3'] . "!\"><i class=\"glyphicon glyphicon-arrow-up\"></i></button></td>";
            echo "<td title=\"" . $script_transl['update'] . $script_transl['thisrow'] . "!\"><input class=\"FacetDataTDsmall\" type=\"submit\" name=\"upd_row[{$k}]\" value=\"* documento allegato *\" />\n";
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
		 			<input class="gazie-tooltip" data-type="product-thumb" data-id="' . $v["codart"] . '" data-title="' . $v['annota'] . '" type="text" name="rows[' . $k . '][descri]" value="' . $descrizione . '" maxlength="100" />
			   	</td>
			    <td>
					<input class="gazie-tooltip" data-type="weight" data-id="' . $peso . '" data-title="' . $script_transl['weight'] . '" type="text" name="rows[' . $k . '][unimis]" value="' . $v['unimis'] . '" maxlength="3" />
				</td>
				<td>
					<input class="gazie-tooltip" data-type="weight" data-id="' . $peso . '" data-title="' . $script_transl['weight'] . '" type="text" name="rows[' . $k . '][quanti]" value="' . $v['quanti'] . '" align="right" maxlength="11" id="righi_' . $k . '_quanti" onchange="document.broven.last_focus.value=\'righi_' . $k . '_prelis\'; this.form.hidden_req.value=\'ROW\'; this.form.submit();" />
                </td>';
            echo "<td><input type=\"text\" name=\"rows[$k][prelis]\" value=\"" . $v['prelis'] . "\" align=\"right\" maxlength=\"11\" ";
			if ($vp>0) { // solo se scelto in configurazione avanzata azienda si vedrà il dialog per mettere il prezzo iva compresa
				echo ' onclick="vatPrice(\''.$k.'\',\''.$v['pervat'].'\');"';
			}
            echo " id=\"righi_" . $k . "_prelis\" onchange=\"document.broven.last_focus.value='righi_" . $k . "_sconto'; this.form.submit()\" /></td>\n";
            echo "<td><input type=\"text\" name=\"rows[$k][sconto]\" value=\"" . $v['sconto'] . "\" maxlength=\"6\"  id=\"righi_" . $k . "_sconto\" onchange=\"document.broven.last_focus.value=this.id; this.form.submit()\" /></td>\n";
            echo "<td><input type=\"text\" name=\"rows[$k][provvigione]\" value=\"" . $v['provvigione'] . "\" maxlength=\"6\"  /></td>\n";
            echo "<td class=\"text-right\">" . gaz_format_number($imprig) . "</td>\n";
            echo "<td class=\"text-right\">" . $v['pervat'] . "%</td>\n";
            echo "<td class=\"text-right codricTooltip\" title=\"Contropartita\">" . $v['codric'] . "</td>\n";

            $last_row[] = array_unshift($last_row, '<strong>' . $v['codart'] . '</strong>, ' . $v['descri'] . ', ' . $v['quanti'] . $v['unimis'] . ', <strong>' . $script_transl[23] . '</strong>: ' . gaz_format_number($v['prelis']) . ', %<strong>' . substr($script_transl[24], 0, 2) . '</strong>: ' . gaz_format_number($v['sconto']) . ', <strong>' . $script_transl[25] . '</strong>: ' . gaz_format_number($imprig) . ', <strong>' . $script_transl[19] . '</strong>: ' . $v['pervat'] . '%, <strong>' . $script_transl[18] . '</strong>: ' . $v['codric']);
            break;
        case "51":
			echo "<td><button type=\"image\" name=\"upper_row[" . $k . "]\" class=\"btn btn-default btn-sm\" title=\"" . $script_transl['3'] . "!\"><i class=\"glyphicon glyphicon-arrow-up\"></i></button></td>";
            echo "<td title=\"" . $script_transl['update'] . $script_transl['thisrow'] . "!\"><input class=\"FacetDataTDsmall\" type=\"submit\" name=\"upd_row[{$k}]\" value=\"* documento allegato *\" />\n";
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
            echo "<td><input type=\"text\" name=\"rows[$k][descri]\" value=\"$descrizione\" maxlength=\"100\"  /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][unimis]\" value=\"\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][quanti]\" value=\"\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][prelis]\" value=\"\" /></td>\n";
            echo "<td><input type=\"hidden\" name=\"rows[$k][sconto]\" value=\"\" /></td>\n";
            echo "<td></td>\n";
            echo "<td></td>\n";
            echo "<td></td>\n";
            echo "<td></td>\n";
            $last_row[] = array_unshift($last_row, $script_transl['typerow'][$v['tiprig']]);
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
                        <input class="gazie-tooltip" data-type="product-thumb" data-id="' . $v["codart"] . '" data-title="' . $v['annota'] . '" type="text" name="rows[' . $k . '][descri]" value="' . $descrizione . '" maxlength="100" />
                    </td>
                    <td>
                        <input class="gazie-tooltip" data-type="weight" data-id="' . $peso . '" data-title="' . $script_transl['weight'] . '" type="text" name="rows[' . $k . '][unimis]" value="' . $v['unimis'] . '" maxlength="3" />
                    </td>
                    <td>
                        <input class="gazie-tooltip" data-type="weight" data-id="' . $peso . '" data-title="' . $script_transl['weight'] . '" type="text" name="rows[' . $k . '][quanti]" value="' . $v['quanti'] . '" align="right" maxlength="11" id="righi_' . $k . '_quanti" onchange="document.broven.last_focus.value=this.id; this.form.hidden_req.value=\'ROW\'; this.form.submit();" />
                    </td>';
                echo "<td></td>\n";
                echo "<td></td>\n";
                echo "<td></td>\n";
                echo "<td class=\"text-right\"></td>\n";
                echo "<td class=\"text-right\"></td>\n";
                echo "<td class=\"text-right\"></td>\n";
                $last_row[] = array_unshift($last_row, '<strong>' . $v['codart'] . '</strong>, ' . $v['descri'] . ', ' . $v['quanti'] . $v['unimis'] . ', <strong>' . $script_transl[23] . '</strong>: ' . gaz_format_number($v['prelis']) . ', %<strong>' . substr($script_transl[24], 0, 2) . '</strong>: ' . gaz_format_number($v['sconto']) . ', <strong>' . $script_transl[25] . '</strong>: ' . gaz_format_number($imprig) . ', <strong>' . $script_transl[19] . '</strong>: ' . $v['pervat'] . '%, <strong>' . $script_transl[18] . '</strong>: ' . $v['codric']);
            } else {
                echo "<input type=\"hidden\" name=\"rows[$k][descri]\" value=\"$descrizione\" maxlength=\"20\"  />
                    <input type=\"hidden\" class=\"gazie-tooltip\" data-type=\"weight\" data-id=\"" . $peso . "\" data-title=\"" . $script_transl["weight"] . "\" type=\"text\" name=\"rows[" . $k . "][unimis]\" value=\"" . $v["unimis"] . "\" maxlength=\"3\"  />
                    <input type=\"hidden\" class=\"gazie-tooltip\" data-type=\"weight\" data-id=\"" . $peso . "\" data-title=\"" . $script_transl['weight'] . "\" type=\"text\" name=\"rows[" . $k . "][quanti]\" value=\"" . $v["quanti"] . "\" align=\"right\" maxlength=\"11\"  id=\"righi_" . $k . "_quanti\" onchange=\"document.broven.last_focus.value=\"righi_" . $k . "_prelis\"; this.form.hidden_req.value=\"ROW\"; this.form.submit();\" />
                    <input type=\"hidden\" name=\"rows[$k][prelis]\" value=\"\" />
                    <input type=\"hidden\" name=\"rows[$k][sconto]\" value=\"\" />
                    <input type=\"hidden\" name=\"rows[$k][provvigione]\" value=\"\" />";
            }
            break;
    }
    if ( $v['tiprig']!="210" ) {
        echo '<td class="text-right">
		    <button type="submit" class="btn btn-default btn-xs" name="del[' . $k . ']" title="' . $script_transl['delete'] . $script_transl['thisrow'] . '"><i class="glyphicon glyphicon-trash"></i></button>
		    </td>';
    }
    echo "</tr>";
}

if (isset($ultimoprezzo) && $ultimoprezzo<>'') {
    $msgtoast = $upd_mm->toast(" <strong>Ultime vendite:</strong>".$ultimoprezzo, 'alert-last-row', 'alert-success');
}

/* Nuovo alert per scontistica, da visualizzare rigorosamente dopo l'ultima riga inserita */
if (count($form['rows']) > 0) {
    $msgtoast = $upd_mm->toast($msgtoast);  //lo mostriamo

    if (isset($_POST['in_submit']) && count($form['rows']) > 5) {
        /* for($i=0;$i<3;$i++) {	//	Predisposizione per mostrare gli ultimi n articoli inseriti (in ordine inverso ovviamente)
          $msgtoast .= $last_row[$i].'<br />';
          } */
        //$msgtoast .= $last_row[0];
        $msgtoast = $upd_mm->toast($script_transl['last_row'] . ': ' . $last_row[0], 'alert-last-row', 'alert-success');  //lo mostriamo
    }
} else {
    echo '<tr id="alert-zerorows">
			<td colspan="12" class="alert alert-danger">' . $script_transl['zero_rows'] . '</td>
		  </tr>';
}

echo '</tbody></table></div>';

echo '<div class="fissa" ><div class="FacetSeparatorTD" align="center">Inserimento nuovo alloggio</div>
	<input type="hidden" value="' . $form['in_descri'] . '" name="in_descri" />
	<input type="hidden" value="' . $form['in_pervat'] . '" name="in_pervat" />
	<input type="hidden" value="' . $form['in_tipiva'] . '" name="in_tipiva" />
	<input type="hidden" value="' . $form['in_ritenuta'] . '" name="in_ritenuta" />
	<input type="hidden" value="' . $form['in_unimis'] . '" name="in_unimis" />
	<input type="hidden" value="' . $form['in_prelis'] . '" name="in_prelis" />
	<input type="hidden" value="' . $form['in_id_mag'] . '" name="in_id_mag" />
	<input type="hidden" value="' . $form['in_id_doc'] . '" name="in_id_doc" />
	<input type="hidden" value="' . $form['in_annota'] . '" name="in_annota" />
	<input type="hidden" value="' . $form['in_scorta'] . '" name="in_scorta" />
	<input type="hidden" value="' . $form['in_quamag'] . '" name="in_quamag" />
	<input type="hidden" value="' . $form['in_pesosp'] . '" name="in_pesosp" />
	<input type="hidden" value="' . $form['in_extdoc'] . '" name="in_extdoc" />
	<input type="hidden" value="' . $form['in_status'] . '" name="in_status" />
	<input type="hidden" value="' . $form['hidden_req'] . '" name="hidden_req" />
	<input type="hidden" value="' . $form['numrat'] . '" name="numrat" />
	<input type="hidden" value="' . $form['expense_vat'] . '" name="expense_vat" />
	<input type="hidden" value="' . $form['spevar'] . '" name="spevar" />
	<input type="hidden" value="' . $form['stamp'] . '" name="stamp" />
	<input type="hidden" value="' . $form['round_stamp'] . '" name="round_stamp" />
	<input type="hidden" value="' . $form['cauven'] . '" name="cauven" />
	<input type="hidden" value="' . $form['caucon'] . '" name="caucon" />
	<input type="hidden" value="' . $form['caumag'] . '" name="caumag" />
	  ';
	  ?>

	<div class="row" >
		<div class="column" >
			<table class="Tlarge table table-striped table-bordered table-condensed">
				<tr>
					<td colspan="2" class="FacetColumnTD"><?php echo $script_transl[17],':'; ?>
						<?php
						$gForm->selTypeRow('in_tiprig', $form['in_tiprig'], '', $script_transl['typerow_booking']);
						?>
							</td>
							</tr>
				<tr>
					<td colspan="2";>
						<?php
						echo $script_transl[15] . ':&nbsp;';
						$select_artico = new selectartico("in_codart");
						$select_artico->addSelected($form['in_codart']);

						$select_artico->output($form['cosear'], " AND custom_field LIKE '%vacation_rental%' ");
						?>
					</td>
				</tr>
				<tr>
					<td class="FacetColumnTD">
						<?php echo $script_transl[16] ?>:&nbsp;<input readonly type="text" value="<?php echo $form['in_quanti'] ?>" maxlength="11" name="in_quanti" tabindex="5" accesskey="q" />
					</td>
					<td class="FacetColumnTD" align="right">
						<?php
						if (substr($form['in_status'], 0, 6) != "UPDROW") { //se non è un rigo da modificare
						?>
						<button type="submit" class="btn btn-default btn-xs" name="in_submit_desc" title="Aggiungi rigo Descrittivo"><i class="glyphicon glyphicon-pencil"></i></button>
						<button type="submit" class="btn btn-default btn-xs" name="in_submit_text" title="Aggiungi rigo Testo"><i class="glyphicon glyphicon-list"></i></button>
						<span>||</span>
						<?php
						}
						?>
						<button type="submit" class="btn btn-default btn-lg" name="in_submit" title="<?php echo $script_transl['submit'] . $script_transl['thisrow'] ?>" tabindex="6">
							<i class="glyphicon glyphicon-ok"></i>
						</button>
					</td>
				</tr>
			</table>
		</div>
		<div class="column" >
			<table class="Tlarge table table-striped table-bordered table-condensed">

				<tr>
					<td colspan="3" class="FacetColumnTD">
					<?php
					echo $script_transl[18] . ": ";
					$select_codric = new selectconven("in_codric");
					$select_codric->addSelected($form['in_codric']);
					$select_codric->output(substr($form['in_codric'], 0, 1));

					?>
					</td>
				</tr>
				<tr>
					<td class="FacetColumnTD">
					<?php echo $script_transl[24],':'; ?>
					<input type="text" value="<?php $form['in_sconto'] ?>" maxlength="4" name="in_sconto">%

					<input type="hidden" value="<?php $form['in_provvigione'] ?>" maxlength="6" name="in_provvigione">

					<input type="hidden" value="<?php $form['in_ritenuta'] ?>" maxlength="6" name="in_ritenuta">
					</td>

				</tr>
				<tr>
					<td colspan="3" class="FacetColumnTD">
					<?php
					echo $script_transl['vat_constrain'];
					$select_in_codvat = new selectaliiva("in_codvat");
					$select_in_codvat->addSelected($form['in_codvat']);
					$select_in_codvat->output();
					?>
					</td>
				</tr>
			</table>
		</div>
		<div class="column" >
			<table class="Tlarge table table-striped table-bordered table-responsive">
				<tr>
					<td colspan="4" class="FacetFieldCaptionTD text-center">Seleziona un extra</td>
				</tr>
				<tr>
						<?php //
            $ex=0;
						foreach($form['rows'] as $row){
							if (isset ($row['accommodation_type']) && $row['accommodation_type']>2){// è un alloggio (1=extra)
								?>
								<tr>

								<td colspan="4" class="FacetFieldCaptionTD text-left"><?php echo "Alloggio ",$row['codart']; ?>
								</td>
								</tr>
								<tr>
								<td colspan="2">
								<?php
								selectFromDBJoin($gTables['artico'].' LEFT JOIN '.$gTables['rental_extra'].' ON '.$gTables['artico'].'.codice = '.$gTables['rental_extra'].'.codart', 'extra[]','codice', ((isset($form['extra'][$ex]))?$form['extra'][$ex]:''), 'codice', 1, ' - ','descri','TRUE','FacetSelect' , null, '',"(".$gTables['artico'].".custom_field REGEXP 'extra') AND (".$gTables['rental_extra'].".rif_alloggio ='".$row['codart']."' OR ".$gTables['rental_extra'].".rif_alloggio = '') AND (find_in_set(".$row['facility_id'].",".$gTables['rental_extra'].".rif_facility)>0 OR ".$gTables['rental_extra'].".rif_facility = '') AND ((".$gTables['artico'].".ref_ecommerce_id_product <> '' AND ".$gTables['artico'].".web_public BETWEEN 1 AND 4) OR (".$gTables['artico'].".ref_ecommerce_id_product = '' AND ".$gTables['artico'].".web_public =0))");
								?>
								</td>
								<td colspan="1" class="FacetFieldCaptionTD text-right">Quantità
								</td>
									<td colspan="1" class="FacetDataTD">
										<input type="number" name="qtaextra" value="<?php echo $form['qtaextra']; ?>" min="0" style="max-width: 50px;" >
										<button type="submit" class="btn btn-default btn-lg" name="extra_submit" value="<?php echo $ex; ?>" title="<?php echo $script_transl['submit'] . $script_transl['thisrow'] ?>" tabindex="6">
											<i class="glyphicon glyphicon-ok"></i>
										</button>
									</td>
								</tr>
								<?php
                $ex++;
							}
						}
						?>
				</tr>
			</table>
		</div>
	</div>

<div class="FacetSeparatorTD text-center">Dettaglio prenotazione</div>

<div class="row" >
	<div class="column" >
	<table class="Tlarge table table-striped table-bordered table-condensed">
		<tr>
			<td class="FacetFieldCaptionTD text-right">Data check-in</td>
			<td class="FacetDataTD">
				<input type="date" name="start" value="<?php echo $form['start']; ?>" class="FacetInput">
			</td>
		</tr>
		<tr>
			<td class="FacetFieldCaptionTD text-right">Data check-out</td>
			<td class="FacetDataTD">
				<input type="date" name="end" value="<?php echo $form['end']; ?>" class="FacetInput">
			</td>
		</tr>
	</table>
	</div>
	<div class="column"	>
	<table class="Tlarge table table-striped table-bordered table-condensed">
		<tr>
			<td class="FacetFieldCaptionTD text-right">Numero adulti</td>
			<td class="FacetDataTD">
				<input type="number" name="adult" value="<?php echo $form['adult']; ?>" min="1" max="5" class="FacetInput">
			</td>
		</tr>
		<tr>
			<td class="FacetFieldCaptionTD text-right">Numero minori di anni <?php echo $form['minor']; ?></td>
			<td class="FacetDataTD">
				<input type="number" name="child" value="<?php echo $form['child']; ?>" min="0" max="5" class="FacetInput">
        <input type="hidden" name="access_code" value="<?php echo $form['access_code']; ?>">
			</td>
		</tr>
		</table>
		</div>

		<?php
$somma_spese = $form['traspo'] + $form['speban'] * $form['numrat'] + $form['spevar'];
$calc = new Compute;
$calc->add_value_to_VAT_castle($castle, $somma_spese, $form['expense_vat']);
if ($calc->total_exc_with_duty > $admin_aziend['taxstamp_limit'] && $form['virtual_taxstamp'] > 0) {
    $form['taxstamp'] = $admin_aziend['taxstamp'];
}
if ($form['stamp'] > 0) {
	$calc->payment_taxstamp($calc->total_imp + $calc->total_vat + $carry - $rit + $form['taxstamp'], $form['stamp'], $form['round_stamp'] * $form['numrat']);
	$stamp = $calc->pay_taxstamp;
} else {
	$stamp = 0;
}
?>

	<div class="column"	>
		<table class="Tlarge table table-striped table-bordered table-condensed">
			<tr>
				<td class="FacetFieldCaptionTD text-right">Sconto generale % </td>
				<td class="FacetDataTD">
					<input type="text" name="sconto" value="<?php echo $form["sconto"] ?>" maxlength="6" onchange="this.form.submit()">
				</td>
				<?php
					if ($rit >= 0.01) {
						echo '
							<td class="FacetFieldCaptionTD text-right">' . $script_transl['ritenuta'] . '</td>
							<td class="text-right">' . gaz_format_number($rit) . '</td>

							<td class="FacetFieldCaptionTD text-right">' . $script_transl['netpay'] . '</td>
							<td class="text-right">' . gaz_format_number($totimpfat + $totivafat + $stamp - $rit + $form['taxstamp']) . '</td>
						';
					}
				?>
			</tr>
			<tr>
			<td class="FacetFieldCaptionTD text-right"><?php echo $script_transl[32] ?></td>

				<td class="text-right"><?php echo gaz_format_number($calc->total_imp);?></td>
				<td class="FacetFieldCaptionTD text-right"><?php echo $script_transl[19] ?></td>
				<td class="text-right"><?php echo gaz_format_number($calc->total_vat);?></td>

				<td class="FacetFieldCaptionTD text-right"><?php echo $admin_aziend['html_symbol'], $script_transl[36] ?></td>
				<td name="totale" class="text-right" style="font-weight:bold;"><?php echo gaz_format_number($calc->total_imp + $calc->total_vat + $stamp + $form['taxstamp']); ?></td>
				<td class="FacetDataTD">
					<input type="hidden" name="imball" value="<?php echo $form['imball']; ?>" >
					<input type="hidden" name="spediz" value="<?php echo $form["spediz"]; ?>">
					<input type="hidden" name="vettor" value=" <?php echo $form["vettor"]?>">
					<input type="hidden" name="virtual_taxstamp" value="<?php echo $form['virtual_taxstamp'] ?>" >
					<input type="hidden" name="portos" value="<?php echo $form["portos"] ?>" >
					<input type="hidden" value="<?php echo $form['traspo'] ?>" name="traspo" >
					<input type="hidden" name="giotra" VALUE="<?php echo $form['giotra'] ?>" >
					<input type="hidden" name="mestra" VALUE="<?php echo $form['mestra'] ?>" >
					<input type="hidden" name="anntra" VALUE="<?php echo $form['anntra'] ?>" >
					<input type="hidden" name="oratra" VALUE="<?php echo $form['oratra'] ?>" >
					<input type="hidden" name="mintra" VALUE="<?php echo $form['mintra'] ?>" >
					<input type="hidden" name="caumag" VALUE="<?php echo $form['caumag'] ?>" >
					<input type="hidden" value=" <?php echo $form['volume']; ?>" name="volume" >
					<input type="hidden" value="<?php echo $form['net_weight'] ?>" name="net_weight" >
					<input type="hidden" value="<?php echo $form['gross_weight'] ?>" name="gross_weight" >
					<input type="hidden" value="<?php echo $form['units'] ?>" name="units" >
					<input type="hidden" value="<?php echo $form['taxstamp'] ?>" name="taxstamp">
				</td>
			</tr>
		</table>
	</div>
	<div>
		<table>
			<?php
			if ($next_row > 0) {
				echo '		<tr>
								<td class="text-center">
									<input name="ins" class="btn '.$class_btn_confirm.'" id="preventDuplicate" onClick="chkSubmit();" type="submit" value="' . ucfirst($script_transl[$toDo]) . '">
								</td>
							';
			}
			if ($toDo == 'update' and $form['tipdoc'] == 'VPR') {
				echo '<td colspan="2"> <input disabled title="Codice ancora da terminare. manca la generazione dei righi in rental_events" type="submit" class="btn btn-default" accesskey="o" name="ord" value="Genera prenotazione" /></td></tr>';
			}else{
				echo '<td colspan="2"></td>';
			}
			?>
			</tr>
		</table>
	</div>

</div>
</div> <!-- chiude class fissa -->
</form>
<div class="modal" id="vat-price" title="IMPORTO IVA COMPRESA">
	<input type="text" id="cat_prevat" style="text-align: right;" maxlength="11" onkeyup="vatPriceCalc();" >
	<br /><br />
	<!--select id="codvat" name="cat_codvat" class="FacetSelect"></select-->
	<input type="text" id="cat_pervat" style="text-align: center;" maxlength="5" readonly >
	<br /><br />
	<input type="text" id="cat_prelis" style="text-align: right;" maxlength="11" readonly >
</div>
<a href="https://programmisitiweb.lacasettabio.it/gazie/vacation-rental-il-gestionale-per-case-vacanza-residence-bb-e-agriturismi/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:20%; z-index:2000;"> Vacation rental è un modulo di Antonio Germani</a>
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
</script>
<script language="JavaScript">
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
    printPdf('invsta_booking.php');
  </script>
  <?php
}
require("../../library/include/footer.php");
?>
