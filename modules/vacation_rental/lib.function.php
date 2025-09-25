<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (http://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
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

function iCalDecoder($file) {
    $ical = @file_get_contents($file);
	preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/si', $ical, $result, PREG_PATTERN_ORDER);
    for ($i = 0; $i < count($result[0]); $i++) {
      $tmpbyline = explode("\r\n", $result[0][$i]);
      if (count($tmpbyline)<3){// se non sono riuscito a separare i righi con \r\n
        $tmpbyline = explode("\n", $result[0][$i]); // provo solo con \n"
      }
      foreach ($tmpbyline as $item) {
        if (substr($item,0,7)=="DTSTART"){
            $majorarray['start']=substr($item,19,10);
        }
        if (substr($item,0,5)=="DTEND"){
            $majorarray['end']=substr($item,17,10);
        }
        if (substr($item,0,3)=="UID"){
            $majorarray['uid']=substr($item,3);
        }
      }
      $icalarray[] = $majorarray;
      unset($majorarray);
    }

    return !empty($icalarray) ? $icalarray : false;

}

// controlla se il numero carta di credito è corretto
function validatecard($cardnumber) {// L' algoritmo di Luhn , noto anche come algoritmo 10, è una semplice checksum utilizzata per convalidare numeri di identificazione, come il numero delle carte di credito
    $cardnumber=preg_replace("/\D|\s/", "", $cardnumber);  # strip any non-digits
    $cardlength=strlen($cardnumber);
    if($cardlength>0){// previene esito positivo se non è stato passato nulla
      $parity=$cardlength % 2;
      $sum=0;
      for ($i=0; $i<$cardlength; $i++) {
        $digit=$cardnumber[$i];
        if ($i%2==$parity) $digit=$digit*2;
        if ($digit>9) $digit=$digit-9;
        $sum=$sum+$digit;
      }
    $valid=($sum%10==0);
    }else{
      $valid=null;
    }
    return $valid;
}

// Ricerca gli sconti applicabili -> vengono esclusi i buoni sconto
function searchdiscount($house="",$facility="",$start="",$end="",$stay=0,$anagra=0,$table=""){
  global $link, $azTables;
  if ($table == ""){
	  $table = $azTables."rental_discounts";
  }
  $where=" ";
  $and=" WHERE (";
  if (strlen($house)>0){
    $where .= $and." accommodation_code = '".$house."' OR accommodation_code='')";
    $and=" AND (";
  }
  if (intval($facility)>0){
    $where .= $and." facility_id = '".$facility."' OR facility_id = 0)";
    $and=" AND (";
  }
  if (intval($start)>0){
    $where .= $and." valid_from <= '".date("Y-m-d", strtotime($start))."' OR valid_from = '0000-00-00')";
    $and=" AND (";
  }
  if (intval($end)>0){
    $where .= $and." valid_to >= '".date("Y-m-d", strtotime($end))."' OR valid_to = '0000-00-00')";
    $and=" AND (";
  }
  if (intval($stay)>0){
    $where .= $and." min_stay <= '".$stay."' OR min_stay = 0)";
    $and=" AND (";
  }
  if (intval($anagra)>0){
    $where .= $and." id_anagra = '".$anagra."' OR id_anagra = 0)";
    $and=" AND (";
  }
  $where .= $and." status = 'CREATED' AND (discount_voucher_code = '' OR discount_voucher_code = NULL ))";
  $sql = "SELECT * FROM ".$table.$where." ORDER BY priority DESC, id ASC";
  //echo "<br>query: ",$sql,"<br>";
  if ($result = mysqli_query($link, $sql)) {
    return ($result);
  }else {
     echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
}

// Ricerca gli sconti più vicini -> vengono esclusi i buoni sconto
function search_near_discount($house="",$facility="",$start="",$end="",$stay=0,$anagra=0,$table=""){
  global $link, $azTables;
  if ($table == ""){
	  $table = $azTables."rental_discounts";
  }
  $where=" ";
  $and=" WHERE (";
  if (strlen($house)>0){
    $where .= $and." accommodation_code = '".$house."' OR accommodation_code='')";
    $and=" AND (";
  }
  if (intval($facility)>0){
    $where .= $and." facility_id = '".$facility."' OR facility_id = 0)";
    $and=" AND (";
  }
  if (intval($start)>0){
    $where .= $and." valid_from <= '".date("Y-m-d", strtotime($start))."' OR valid_from = '0000-00-00')";
    $and=" AND (";
  }
  if (intval($end)>0){
    $where .= $and." valid_to >= '".date("Y-m-d", strtotime($end))."' OR valid_to = '0000-00-00')";
    $and=" AND (";
  }
  if (intval($stay)>0){
    $near_stay = $stay+3;
    $where .= $and." min_stay <= '".$near_stay."' AND min_stay > '".$stay."' )";
    $and=" AND (";
  }
  if (intval($anagra)>0){
    $where .= $and." id_anagra = '".$anagra."' OR id_anagra = 0)";
    $and=" AND (";
  }
  $where .= $and." status = 'CREATED' AND (discount_voucher_code = '' OR discount_voucher_code = NULL ))";
  $sql = "SELECT * FROM ".$table.$where." ORDER BY priority DESC, id ASC";
  //echo "<br>query: ",$sql,"<br>";
  if ($result = mysqli_query($link, $sql)) {
    return ($result);
  }else {
     echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
}

// come selectFromDB ma permette di fare join
function selectFromDBJoin($table, $name, $key, $val, $order = false, $empty = false, $bridge = '', $key2 = '', $val_hiddenReq = '', $class = 'FacetSelect', $addOption = null, $style = '', $where = false, $echo=false) {
        global $gTables;
		$acc='';
        $refresh = '';
        if (!$order) {
            $order = $key;
        }
        $query = 'SELECT * FROM ' . $table . ' ';
        if ($where) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' ORDER BY `' . $order . '`';
        if (!empty($val_hiddenReq)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$val_hiddenReq'; this.form.submit();\"";
        }
        $acc .= "\t <select id=\"$name\" name=\"$name\" class=\"$class\" $refresh $style>\n";
        if ($empty) {
            $acc .= "\t\t <option value=\"\"></option>\n";
        }

        $result = gaz_dbi_query($query);
        while ($r = gaz_dbi_fetch_array($result)) {
            $selected = '';
            if ($r[$key] == $val) {
                $selected = "selected";
            }
            $acc .= "\t\t <option value=\"" . $r[$key] . "\" $selected >";
            if (empty($key2)) {
                $acc .= substr($r[$key], 0, 43) . "</option>\n";
            } else {
                $acc .= substr($r[$key], 0, 28) . $bridge . substr($r[$key2], 0, 35) . "</option>\n";
            }
        }
        if ($addOption) {
            $acc .= "\t\t <option value=\"" . $addOption['value'] . "\"";
            if ($addOption['value'] == $val) {
                $acc .= " selected ";
            }
            $acc .= ">" . $addOption['descri'] . "</option>\n";
        }
        $acc .= "\t </select>\n";
		if ($echo){
			return $acc;
		} else {
			echo $acc;
		}
}
function get_string_lang($string, $lang){
	$string = " ".$string;
	$ini = strpos($string,"<".$lang.">");
	if ($ini == 0) return $string;
	$ini += strlen("<".$lang.">");
	$len = strpos($string,"</".$lang.">",$ini) - $ini;
  if (intval($len)>0){// se è stato trovato il tag lingua restituisco filtrato
    return substr($string,$ini,$len);
  }else{// altrimenti restituisco come era
    return $string;
  }
}
function get_lang_translation($ref, $table, $lang_id){// nuovo sistema traduzione tramite tabella body-text
    if ($lang_id>1){// traduco solo se non è la lingua di default
      global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
      if ($azTables){
        $table_body= $azTables."body_text";
      }else{
        $table_body= $gTables['body_text'];
      }
      $where = " WHERE (table_name_ref = '".$table."' AND code_ref = '".substr($ref,0,32)."' AND lang_id = ".$lang_id.")";
      $sql = "SELECT * FROM ".$table_body.$where." LIMIT 1";
      if ($result = mysqli_query($link, $sql)) {
        $bodytextlang = mysqli_fetch_assoc($result);
      }else{
        echo "Error: " . $sql . "<br>" . mysqli_error($link);
      }
      if (is_array($bodytextlang)){
      $ret=array();
      $ret['descri'] = (isset($bodytextlang['descri']))?$bodytextlang['descri']:'';
      $ret['body_text'] = (isset($bodytextlang['body_text']))?$bodytextlang['body_text']:'';
      $obj = $bodytextlang?json_decode($bodytextlang['custom_field']):false;
      $ret['web_url'] = (isset($obj->web_url))?$obj->web_url:'';
      if (isset($obj->check_in)){
        $ret['check_in']=$obj->check_in;
      }
      if (isset($obj->check_out)){
        $ret['check_out']=$obj->check_out;
      }
      return $ret;
      }else{
        return false;
      }
    }else{
      return false;
    }
}

// calcolo dei giorni da pagare per la tassa turistica fra due date specifiche
function tour_tax_daytopay($start, $end, $tour_tax_from, $tour_tax_to, $tour_tax_day = 0, $full_start = null, $full_end = null) {
    $year = date("Y", strtotime($start));
	$from_parts = explode('-', $tour_tax_from); // es: ['15', '12']
	$to_parts   = explode('-', $tour_tax_to);   // es: ['15', '01']

	$from_date = new DateTime("$year-{$from_parts[1]}-{$from_parts[0]}");
	$to_date   = new DateTime("$year-{$to_parts[1]}-{$to_parts[0]}");

	// Se to < from, vuol dire che to è nel gennaio dell’anno successivo
	if ($to_date < $from_date) {
		$to_date->modify('+1 year');
	}

	$tour_tax_from = $from_date->format('Y-m-d');
	$tour_tax_to   = $to_date->format('Y-m-d');
	
	
	$night = (new DateTime($start))->diff(new DateTime($end))->days;
    $daytopay = intval($night); // default: tutte le notti si pagano, se non c'è un periodo specifico

    if (strtotime($tour_tax_from)) { // Se c'è un periodo specifico per la tassa
        $start_date = new DateTime($start);
        $end_date = new DateTime($end);
        $tax_start = new DateTime($tour_tax_from);
        $tax_end = new DateTime($tour_tax_to);

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start_date, $interval, $end_date);

        $count = 0;
        foreach ($period as $night_date) {
            if ($night_date >= $tax_start && $night_date <= $tax_end) {
                $count++;
            }
        }

        $daytopay = $count; // sovrascrivo solo se c'è un periodo valido
    }
	
	// ** LOGICA PER IL CALCOLO STATISTICO **
    // Se viene passato anche il periodo completo locazione, controllo se start e end 
    // rientrano nelle prime tour_tax_day notti dalla locazione completa.
   if (strtotime($tour_tax_from) && strtotime($tour_tax_to) && $full_start !== null && $full_end !== null && intval($tour_tax_day) > 0) {
		$full_start_dt = new DateTime($full_start);
		$full_end_dt = new DateTime($full_end);
		$start_dt = new DateTime($start);
		$end_dt = new DateTime($end);
		$tax_start = new DateTime($tour_tax_from);
		$tax_end = new DateTime($tour_tax_to);

		// Iteriamo l'intero soggiorno (full_start → full_end), ma consideriamo solo i giorni nel periodo tassa
		$interval = new DateInterval('P1D');
		$full_period = new DatePeriod($full_start_dt, $interval, $full_end_dt);

		$tassabili = [];
		foreach ($full_period as $d) {
			if ($d >= $tax_start && $d <= $tax_end) {
				$tassabili[] = $d->format('Y-m-d');
			}
		}

		// Prendiamo solo le prime X notti tassabili
		$tassabili_limitate = array_slice($tassabili, 0, intval($tour_tax_day));

		// Ora verifichiamo se $start (giorno singolo, nel caso statistico) rientra in quelle notti tassabili
		$giorno_analizzato = $start_dt->format('Y-m-d');

		if (in_array($giorno_analizzato, $tassabili_limitate)) {
			$daytopay = 1;
		} else {
			$daytopay = 0;
		}
	}


    // Applico limite massimo di notti da pagare 
    if (intval($tour_tax_day) > 0 && $daytopay > intval($tour_tax_day)) {
        $daytopay = intval($tour_tax_day);
    }
	

    return $daytopay;
}


// calcolo totale della locazione
function get_totalprice_booking($tesbro, $tourist_tax = TRUE, $vat = FALSE, $preeminent_vat = "", $add_extra = FALSE, $security_deposit = FALSE) {
    if ($tesbro !== '') {
        $tesbro = intval($tesbro);
        global $link, $azTables, $gTables;

        if ($azTables) {
            $tablerig = $azTables . "rigbro";
            $tabletes = $azTables . "tesbro";
            $tableiva = $azTables . "aliiva";
            $tableaz = $azTables . "aziend";
            $tableart = $azTables . "artico";
        } else {
            $tablerig = $gTables['rigbro'];
            $tabletes = $gTables['tesbro'];
            $tableiva = $gTables['aliiva'];
            $tableaz = $gTables['aziend'];
            $tableart = $gTables['artico'];
        }

        // Leggo il deposito cauzionale da tesbro
        $sql = "SELECT custom_field FROM " . $tabletes . " WHERE id_tes = " . $tesbro . " LIMIT 1";
        $security_deposit_val = -1;

        if ($result = mysqli_query($link, $sql)) {
            $rowtesbf = mysqli_fetch_assoc($result);
            if (isset($rowtesbf['custom_field']) && ($data_tesbro = json_decode($rowtesbf['custom_field'], true))) {
                if (isset($data_tesbro['vacation_rental']['security_deposit'])) {
                    $security_deposit_val = $data_tesbro['vacation_rental']['security_deposit'];
                }
            }
        }

        // === COSTRUZIONE DELLA QUERY ===

        // VAT FALSE = imponibile
        if ($vat == FALSE) {
            $where = " WHERE r.id_tes = '" . $tesbro . "'";
            $need_artico_join = false;

            if ($tourist_tax == TRUE && $add_extra == FALSE) {
                $where .= " AND (r.codart LIKE 'TASSA-TURISTICA%' OR (a.custom_field REGEXP 'accommodation_type'))";
                $need_artico_join = true;
            } elseif ($add_extra == FALSE && $tourist_tax == FALSE) {
                $where .= " AND (a.custom_field REGEXP 'accommodation_type') AND r.codart NOT LIKE 'TASSA-TURISTICA%'";
                $need_artico_join = true;
            } elseif ($tourist_tax == FALSE && $add_extra == TRUE) {
                $where .= " AND r.codart NOT LIKE 'TASSA-TURISTICA%'";
            }

            $sql = "SELECT SUM(COALESCE(r.quanti, 0) * COALESCE(r.prelis, 0)) AS totalprice FROM " . $tablerig . " r";

            if ($need_artico_join) {
                $sql .= " LEFT JOIN " . $tableart . " a ON a.codice = CASE 
                    WHEN r.codart IS NOT NULL AND r.codart != '' THEN r.codart 
                    WHEN r.codice_fornitore IS NOT NULL AND r.codice_fornitore != '' THEN r.codice_fornitore 
                    ELSE NULL END";
            }

            $sql .= $where;

            if ($result = mysqli_query($link, $sql)) {
                $row = mysqli_fetch_assoc($result);

                // Somma spese bancarie
                $sql = "SELECT speban FROM " . $tabletes . " WHERE id_tes = " . $tesbro . " LIMIT 1";
                if ($result = mysqli_query($link, $sql)) {
                    $rowtes = mysqli_fetch_assoc($result);
                    $rowtes['speban'] = isset($rowtes['speban']) ? $rowtes['speban'] : 0;
                    $totalprice = $row['totalprice'] + $rowtes['speban'];
                    return $totalprice;
                } else {
                    echo "Error: " . $sql . "<br>" . mysqli_error($link);
                }
            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($link);
            }
        } else {
            // === VAT TRUE = IVA COMPRESA ===
            $where = " WHERE (r.id_tes = '" . $tesbro . "' OR (r.id_tes = '" . $tesbro . "' AND r.prelis < 0))";
            $sql = "SELECT r.quanti, r.prelis, i.aliquo, a.codice 
                    FROM " . $tablerig . " r
                    LEFT JOIN " . $tableiva . " i ON i.codice = r.codvat
                    LEFT JOIN " . $tableart . " a ON r.codart = a.codice " . $where;

            $totalprice = 0;
            $totalsecdep = 0;

            if ($result = mysqli_query($link, $sql)) {
                foreach ($result as $res) {
                    $prezzo = ($res['prelis'] * $res['quanti']);
                    $iva = ($prezzo * $res['aliquo']) / 100;
                    $totalprice += $prezzo + $iva;

                    if ($security_deposit == TRUE) {
                        $sql = "SELECT custom_field FROM " . $tableart . " WHERE codice = '" . $res['codice'] . "'";
                        if ($result2 = mysqli_query($link, $sql)) {
                            $row2 = mysqli_fetch_assoc($result2);
                            if (isset($row2['custom_field']) && ($data = json_decode($row2['custom_field'], true))) {
                                if (isset($data['vacation_rental']['accommodation_type'])) {
                                    if ($security_deposit_val == -1) {
                                        if (isset($data['vacation_rental']['security_deposit']) && floatval($data['vacation_rental']['security_deposit']) > 0) {
                                            $totalsecdep += floatval($data['vacation_rental']['security_deposit']);
                                        }
                                    } else {
                                        $totalsecdep += floatval($security_deposit_val);
                                    }
                                }
                            }
                        }
                    }
                }

                // Calcolo spese bancarie con IVA
                if (intval($preeminent_vat) > 0) {
                    $sql = "SELECT aliquo FROM " . $tableiva . " WHERE codice = " . intval($preeminent_vat);
                    if ($result = mysqli_query($link, $sql)) {
                        $row = mysqli_fetch_assoc($result);
                        $spevat = $row['aliquo'];
                    } else {
                        echo "Error: " . $sql . "<br>" . mysqli_error($link);
                    }
                } else {
                    $spevat = 0;
                }

                $sql = "SELECT speban FROM " . $tabletes . " WHERE id_tes = '" . $tesbro . "' LIMIT 1";
                if ($result = mysqli_query($link, $sql)) {
                    $rowtes = mysqli_fetch_assoc($result);
                    $rowtes['speban'] = isset($rowtes['speban']) ? $rowtes['speban'] : 0;
                    $rowtes['speban'] += ($rowtes['speban'] * $spevat) / 100;
                    $totalprice += $rowtes['speban'];
                } else {
                    echo "Error: " . $sql . "<br>" . mysqli_error($link);
                    die;
                }

                return $totalprice + $totalsecdep;
            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($link);
            }
        }
    } else {
        return "tesbro vuoto";
    }
}


function get_total_promemo($startprom,$endprom){// STAT
  global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
  if ($azTables){
    $tableart = $azTables."artico";
    $tablerent_ev = $azTables."rental_events";
    $tabletes = $azTables."tesbro";
  }else{
    $tableart = $gTables['artico'];
    $tablerent_ev = $gTables['rental_events'];
    $tabletes = $gTables['tesbro'];
  }
  $data = [];
  $tot_nights_booked=0;
  $ret=[];
  $ret['totalprice_booking']=0;
  $what = "codice";
  $datediff = strtotime($endprom)-strtotime($startprom);
  $night_promemo = round($datediff / (60 * 60 * 24));// numero notti dell'arco di tempo richiesto
  $where = "good_or_service=1 AND (custom_field REGEXP 'accommodation_type')";
  $sql = "SELECT ".$what." FROM ".$tableart." WHERE ".$where;
  $resulth = mysqli_query($link, $sql); // prendo tutti gli alloggi
  $num_all = $resulth->num_rows;// numero alloggi presenti in GAzie
  foreach ($resulth as $resh){ // per ogni alloggio
    // prendo tutti gli eventi dell'alloggio che interessano l'arco di tempo richiesto
    $sql = "SELECT * FROM ".$tablerent_ev." LEFT JOIN ".$tabletes." ON  ".$tablerent_ev.".id_tesbro = ".$tabletes.".id_tes WHERE  ".$tablerent_ev.".type = 'ALLOGGIO' AND ".$tablerent_ev.".id_tesbro > 0 AND (custom_field IS NULL OR custom_field LIKE '%PENDING%' OR custom_field LIKE '%CONFIRMED%' OR custom_field LIKE '%FROZEN%') AND house_code='".substr($resh['codice'], 0, 32)."' AND ( start <= '".$endprom."' AND(start >= '".$startprom."' OR start <= '".$endprom."') AND (end >= '".$startprom."' OR end <= '".$endprom."') AND end >= '".$startprom."') ORDER BY id ASC";
    //echo $sql;

    if ($result = @mysqli_query($link, $sql)){

	}else{
		echo "Error: " . $sql . "<br>" . mysqli_error($link);
	}


    foreach($result as $row){ // per ogni evento dell'alloggio
      //echo "<pre>evento alloggio:",print_r($row),"</pre>";
      $datediff = strtotime($row['end'])-strtotime($row['start']);
      $nights_event = round($datediff / (60 * 60 * 24));// numero notti totali della prenotazione(evento)
      $tot_n_event_in_promemo=0;
      $start=$row['start'];
      $end=$row['end'];
      // ciclo i giorni dell'evento
      while (strtotime($start) < strtotime($end)) {// per ogni giorno dell'evento

        if ($start >= $startprom AND $start <= date ("Y-m-d", strtotime("-1 days", strtotime($endprom)))) {// se il giorno è dentro l'arco di tempo richiesto (tolgo una giorno a endprom perché devo conteggiare le notti)
		  //echo "<br>",$start," è dentro";
          if (!isset($data[$start])){
            $data[$start]=array();
          }
            if (!in_array($row['house_code'],$data[$start])){// escludendo i giorni che hanno già quell'alloggio
             array_push($data[$start],$row['house_code']);// conteggio il giorno per questo alloggio
             $tot_nights_booked  ++;
             $tot_n_event_in_promemo ++;
          }

        }
        $start = date ("Y-m-d", strtotime("+1 days", strtotime($start)));// aumento di un giorno il ciclo
      }
      $ret['totalprice_booking'] += ((get_totalprice_booking($row['id_tesbro'],false,false,"",false,false))/$nights_event)*$tot_n_event_in_promemo;// aggiungo il costo medio della locazione(evento) calcolata sui giorni che rientrano nell'arco di tempo richiesto
	  //il prezzo è imponibile e senza tassa turistica
	}
  }
  $ret['tot_nights_bookable']= $num_all * $night_promemo;
  $ret['perc_booked'] = ($ret['tot_nights_bookable']>0)?(($tot_nights_booked/$ret['tot_nights_bookable'])*100):0;
  $ret['tot_nights_booked'] = $tot_nights_booked;

  return $ret;
}

function get_datasets($startprom,$endprom){// STAT graph
  global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
  if ($azTables){
    $tableart = $azTables."artico";
    $tablerent_ev = $azTables."rental_events";
    $tabletes = $azTables."tesbro";
  }else{
    $tableart = $gTables['artico'];
    $tablerent_ev = $gTables['rental_events'];
    $tabletes = $gTables['tesbro'];
  }
  $data = [];
  $tot_nights_booked=0;
  $ret=[];
  $ret['totalprice_booking']=0;
  $retsumdat=[];
  $what = "codice";
  $datediff = strtotime($endprom)-strtotime($startprom);
  $night_promemo = round($datediff / (60 * 60 * 24));// numero notti dell'arco di tempo richiesto
  $where = "good_or_service=1 AND (custom_field REGEXP 'accommodation_type')";
  $sql = "SELECT ".$what." FROM ".$tableart." WHERE ".$where;
  $resulth = mysqli_query($link, $sql); // prendo tutti gli alloggi
  $num_all = $resulth->num_rows;// numero alloggi presenti in GAzie
  foreach ($resulth as $resh){ // per ogni alloggio
    // prendo tutti gli eventi dell'alloggio che interessano l'arco di tempo richiesto
    $sql = "SELECT * FROM ".$tablerent_ev." LEFT JOIN ".$tabletes." ON  ".$tablerent_ev.".id_tesbro = ".$tabletes.".id_tes WHERE  ".$tablerent_ev.".type = 'ALLOGGIO' AND ".$tablerent_ev.".id_tesbro > 0 AND (custom_field IS NULL OR custom_field LIKE '%PENDING%' OR custom_field LIKE '%CONFIRMED%' OR custom_field LIKE '%FROZEN%') AND house_code='".substr($resh['codice'], 0, 32)."' AND ( start <= '".$endprom."' AND(start >= '".$startprom."' OR start <= '".$endprom."') AND (end >= '".$startprom."' OR end <= '".$endprom."') AND end >= '".$startprom."') ORDER BY id ASC";
    //echo $sql;
    $result = mysqli_query($link, $sql);
	if ($result->num_rows>0){

		foreach($result as $row){ // per ogni evento dell'alloggio
		  //echo "<pre>evento alloggio:",print_r($row),"</pre>";
		  $datediff = strtotime($row['end'])-strtotime($row['start']);
		  $nights_event = round($datediff / (60 * 60 * 24));// numero notti totali della prenotazione(evento)
		  $tot_n_event_in_promemo=0;
		  $start=$row['start'];
		  $end=$row['end'];
		  // ciclo i giorni dell'evento
		  while (strtotime($start) < strtotime($end)) {// per ogni giorno dell'evento
			$month=date("m",strtotime($start));
			$year=date("Y",strtotime($start));
			if ($start >= $startprom AND $start <= date ("Y-m-d", strtotime("-1 days", strtotime($endprom)))) {// se il giorno è dentro l'arco di tempo richiesto (tolgo una giorno a endprom perché devo conteggiare le notti)
			  //echo "<br>",$start," è dentro";
			  if (!isset($retsumdat['IMPORTI'][$year][substr($resh['codice'], 0, 32)][$month])){
				$retsumdat['IMPORTI'][$year][substr($resh['codice'], 0, 32)][$month]=0;
			  }
			  if (!isset($retsumdat['IMPORTI'][$year]['TUTTI'][$month])){
				$retsumdat['IMPORTI'][$year]['TUTTI'][$month]=0;
			  }
			  $retsumdat['IMPORTI'][$year][substr($resh['codice'], 0, 32)][$month]+= ((get_totalprice_booking($row['id_tesbro'],FALSE))/$nights_event);// aggiungo il costo della notte nel mese
			  $retsumdat['IMPORTI'][$year]['TUTTI'][$month]+= ((get_totalprice_booking($row['id_tesbro'],FALSE))/$nights_event);// aggiungo il costo della notte nel mese di tutti
			  if (!isset($data[$start])){
				$data[$start]=array();
			  }
				if (!in_array($row['house_code'],$data[$start])){// escludendo i giorni che hanno già quell'alloggio
				  array_push($data[$start],$row['house_code']);// conteggio il giorno per questo alloggio
				  if (!isset($retsumdat['OCCUPAZIONE'][$year][substr($resh['codice'], 0, 32).'-occupazione'][$month])){
					$retsumdat['OCCUPAZIONE'][$year][substr($resh['codice'], 0, 32).'-occupazione'][$month]=0;
				  }
				  $retsumdat['OCCUPAZIONE'][$year][(substr($resh['codice'], 0, 32)).'-occupazione'][$month] ++;
				  if (!isset($retsumdat['OCCUPAZIONE'][$year]['occup. tutti'][$month])){

					$retsumdat['OCCUPAZIONE'][$year]['occup. tutti'][$month]=0;
				  }
				  $retsumdat['OCCUPAZIONE'][$year]['occup. tutti'][$month] ++;
				  $tot_nights_booked  ++;
				  $tot_n_event_in_promemo ++;
			  }

			}
			$start = date ("Y-m-d", strtotime("+1 days", strtotime($start)));// aumento di un giorno il ciclo
		  }
		  $ret['totalprice_booking'] += ((get_totalprice_booking($row['id_tesbro'],false,false,"",false,false))/$nights_event)*$tot_n_event_in_promemo;// aggiungo il costo medio della locazione(evento) calcolata sui giorni che rientrano nell'arco di tempo richiesto
		  //il prezzo è imponibile e senza tassa turistica
		}
		//echo "<br><b>tot book:",$ret['totalprice_booking']," - night prenotaz:",$nights_event," - total preno:",get_totalprice_booking($row['id_tesbro'],false,false,"",false,false),"</b>";
	}
  }
  $ret['tot_nights_bookable']= $num_all * $night_promemo;
  $ret['perc_booked'] = ($ret['tot_nights_bookable']>0)?(($tot_nights_booked/$ret['tot_nights_bookable'])*100):0;
  $ret['tot_nights_booked'] = $tot_nights_booked;
  // adesso mi creo il dataset
  $datasets="";
  if (isset($retsumdat['IMPORTI'])){
	  $datasets="{";
	  foreach($retsumdat['IMPORTI'] as $key => $value){
		foreach($value as $key2 => $value2){// qui ho l'anno e l'alloggio
		  $datasets .= '"'.$key.'-'.$key2.'": {label: "'.$key.'-'.$key2.'", data: [';
		  ksort($value2);// ordino in base al mese
		  foreach ($value2 as $k => $v){// qui ho il mese e il valore
			$datasets .= '['.$k.', '.$v.'],';
		  }
		  $datasets .= ']},';
		}
	  }
	  $datasets.="}";
  }
  $dataret['IMPORTI']=$datasets;
  if (isset($retsumdat['OCCUPAZIONE'])){
	  $datasets="{";
	  foreach($retsumdat['OCCUPAZIONE'] as $key => $value){

		foreach($value as $key2 => $value2){// qui ho l'anno e l'alloggio
		  $datasets .= '"'.$key.'-'.$key2.'": {label: "'.$key.'-'.$key2.'", data: [';
		  ksort($value2);// ordino in base al mese
		  foreach ($value2 as $k => $v){// qui ho il mese e il valore
			$datasets .= '['.$k.', '.$v.'],';
		  }
		  $datasets .= ']},';
		}

	  }
	  $datasets.="}";
  }
  $dataret['OCCUPAZIONE']=$datasets;
  //echo "<pre>",print_r($dataret);die;

  return $dataret;
}

function get_next_check($startprom,$endprom){
  global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
  $next['in']=[];
  $next['out']=[];
  if ($azTables){
    $tableart = $azTables."artico";
    $tablerent_ev = $azTables."rental_events". " LEFT JOIN " . $azTables['tesbro'] . " ON " .$azTables['tesbro'] . ".id_tes = " . $azTables['rental_events'] . ".id_tesbro";
    $tabletes = $azTables."tesbro";
  }else{
    $tableart = $gTables['artico'];
    $tablerent_ev = $gTables['rental_events']. " LEFT JOIN " . $gTables['tesbro'] . " ON " .$gTables['tesbro'] . ".id_tes = " . $gTables['rental_events'] . ".id_tesbro";
    $tabletes = $gTables['tesbro'];
  }
  $rs_booking = gaz_dbi_dyn_query("id,start,end", $tablerent_ev, "(start >= ".$startprom." OR start <= ".$endprom." OR end >= ".$startprom." OR end <= ".$endprom.")  AND type = 'ALLOGGIO' AND ".$tabletes.".custom_field LIKE '%CONFIRMED%'", "id asc");
  while ($booking = gaz_dbi_fetch_assoc($rs_booking)){// ciclo le prenotazioni che interessano arco di tempo richiesto
    if (intval($booking['id'])>0 && $booking['start']>= date ("Y-m-d", strtotime("-3 days", strtotime($startprom))) && $booking['start'] <= $endprom){//se la data di check-in è dentro ( prendo anche eventuali checkin ritardatari fino a 3 giorni
      $next['in'][]=$booking;
    }
    if (intval($booking['id'])>0 && $booking['end']>= $startprom && $booking['end'] <= $endprom){//se la data di check-out è dentro
	  $next['out'][]=$booking;
    }
  }
  return $next;
}

function get_total_paid($idtesbro){// totale pagato nella locazione
  global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
  if ($azTables){
    $tablerent_pay = $azTables."rental_payments";
  }else{
    $tablerent_pay = $gTables['rental_payments'];
  }
  $where = " WHERE id_tesbro = '".$idtesbro."' AND payment_status = 'Completed' AND type <> 'Deposito_cauzionale'";
  $sql = "SELECT SUM(payment_gross) AS totalpaid FROM ".$tablerent_pay.$where;
  if ($result = mysqli_query($link, $sql)) {
    $row = mysqli_fetch_assoc($result);

    return $row['totalpaid'];
  }else {
     echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
}
function get_secdep_paid($idtesbro){// totale deposito cauzionale pagato per la locazione
  global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
  if ($azTables){
    $tablerent_pay = $azTables."rental_payments";
  }else{
    $tablerent_pay = $gTables['rental_payments'];
  }
  $where = " WHERE id_tesbro = '".$idtesbro."' AND payment_status = 'Completed' AND type = 'Deposito_cauzionale'";
  $sql = "SELECT SUM(payment_gross) as totalpaid FROM ".$tablerent_pay.$where;

  if ($result = mysqli_query($link, $sql)) {
    $row = mysqli_fetch_assoc($result);

    return $row['totalpaid'];
  }else {
     echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
}

function get_user_points_level($id_anagra, $point=false){// determina il livello punti raggiunto dal cliente. Restituisce null se il sistema punti è disabilitato o non correttamente impostato. Restituisce array con livello e punti se point=true
  global $link, $azTables, $gTables, $genTables;// posso chiamare la funzione con entrambi i metodi
  if ($azTables){
    $table = $genTables."anagra";
  }else{
    $table = $gTables['anagra'];
  }
  $where = " WHERE id = '".$id_anagra."'";
  $sql = "SELECT custom_field FROM ".$table.$where;
  if ($result = mysqli_query($link, $sql)) {// prendo il customfield in anagra
    $row = mysqli_fetch_assoc($result);
    $user_point=0;
    if (isset($row['custom_field']) && ($data = json_decode($row['custom_field'],true))){// se c'è un json in anagra
      if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental" lo aggiorno
        if (isset($data['vacation_rental']['points'])){
          $user_point = intval($data['vacation_rental']['points']);
        }
      }
    }
    if ($azTables){
      $table = $azTables."company_config";
    }else{
      $table = $gTables['company_config'];
    }
	$user_lev=null;
    $sql = "SELECT * FROM ". $table ." WHERE var = 'pointenable' ORDER BY id ASC LIMIT 1";
    if ($result = mysqli_query($link, $sql)) {// prendo il customfield in anagra
      $row = mysqli_fetch_assoc($result);
      $pointenable=$row['val'];
    }

    $sql = "SELECT * FROM ". $table ." WHERE var LIKE 'pointlevel%' ORDER BY id ASC";
    if ($result = mysqli_query($link, $sql)) {// prendo i livelli dalle impostazioni generali
      $levname="";
      if (intval($pointenable)>0 ){
        while ($rigpoint = mysqli_fetch_array($result)){
          if (substr($rigpoint['description'],0,12)=="Nome livello"){
            $lev_number=substr($rigpoint['description'],13);
          }
          if (substr($rigpoint['description'],0,13)=="Punti livello"){
            if ($user_point>=$rigpoint['val']){
              $user_lev=$lev_number;
            }
          }
        }
      }
	  if ($point == false){
		return $user_lev;// restituisco il numero del livello
	  }else{
		  $ret['user_level']=$user_lev;
		  $ret['user_point']=$user_point;
		  return $ret;
	  }
    }else {
       echo "Error: " . $sql . "<br>" . mysqli_error($link);
    }
  }else {
     echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
}

function check_availability($start,$end,$house_code, $open_from="", $open_to=""){// controllo disponibilità
  global $link, $azTables, $gTables, $genTables;// posso chiamare la funzione con entrambi i metodi
  $check_in=$start;
  $unavailable=1;
  if ($azTables){
    $table = $azTables."rental_events";
    $table_ts = $azTables."tesbro";
    $table_gr= $azTables."artico_group";
    $table_ar= $azTables."artico";
  }else{
    $table = $gTables['rental_events'];
    $table_ts = $gTables['tesbro'];
    $table_gr= $gTables['artico_group'];
    $table_ar= $gTables['artico'];
  }
  while (strtotime($start) < strtotime($end)) {// ciclo il periodo della locazione richiesta giorno per giorno
    if ((intval($open_from)>0 && strtotime($open_from."-".substr($start,0,4))<=strtotime($start) && strtotime($open_to."-".substr($start,0,4))>=strtotime($start)) || intval($open_from)==0){
      // Controllo disponibilità dopo aver controllato se è aperto qualora è stato passato open from e to
      $what = "title";
      $where = "(custom_field IS NULL OR custom_field LIKE '%PENDING%' OR custom_field LIKE '%CONFIRMED%' OR custom_field LIKE '%FROZEN%') AND house_code = '".mysqli_real_escape_string($link,$house_code)."' AND start <= '". $start ."' AND end > '". $start."'";
      $sql = "SELECT ".$what." FROM ".$table." LEFT JOIN ".$table_ts." ON ".$table.".id_tesbro = ".$table_ts.".id_tes  WHERE ".$where;
      if ($available = mysqli_query($link, $sql)) {
        $available = mysqli_fetch_array($available);
      }else {
       echo "Error: " . $sql . "<br>" . mysqli_error($link);
      }
    }else{
      $available="chiuso";
    }
    //echo "<br>",$sql;
    if (isset($available)){
      // NON disponibile
      $unavailable=0;
      //echo "<br>NON disponibile...",print_r($available);
      break;
    }
    $start = date ("Y-m-d", strtotime("+1 days", strtotime($start)));// aumento di un giorno il ciclo
  }

  if ($unavailable==1){  // quì, se disponibile, controllo se ci sono limitazioni nel giorno di entrata e di uscita
    $sql = "SELECT ".$table_gr.".custom_field FROM ".$table_gr." LEFT JOIN ".$table_ar." ON ".$table_gr.".id_artico_group = ".$table_ar.".id_artico_group  WHERE ".$table_ar.".codice = '".mysqli_real_escape_string($link,$house_code)."'";
    if ($res_cust = mysqli_query($link, $sql)) {
      $row = mysqli_fetch_array($res_cust);
      if (isset($row['custom_field']) && ($data = json_decode($row['custom_field'],true))){// se c'è un json in artico_group
        if ((isset($data['vacation_rental']['week_check_in'])&& strlen($data['vacation_rental']['week_check_in'])>0 )|| (isset($data['vacation_rental']['week_check_out'])&& strlen($data['vacation_rental']['week_check_out'])>0 )){
          // controllo il check-in
          $in=explode(",", $data['vacation_rental']['week_check_in']);
          $unavailable=2;
          foreach ($in as $inday){
            if (date('w', strtotime($check_in))== intval($inday)){
              $unavailable=1; // disponibile per giorno check-in
              break;
            }
          }
          if ($unavailable==1){ // controllo il check-out
            $out=explode(",", $data['vacation_rental']['week_check_out']);
            $unavailable=3;
            foreach ($out as $outday){
              if (date('w', strtotime($end))== intval($outday)){
                $unavailable=1; // disponibile per giorno check-out

                break;
              }
            }
          }
        }
      }
    }else {
     echo "Error: " . $sql . "<br>" . mysqli_error($link);
    }
  }

  return $unavailable;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use Ddeboer\Imap\Server;
function set_mailer() {
  global $gTables;  // Accedi alla variabile globale gTables

  // Impostazioni per PHPMailer
  $host = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_server')['val'];
  $usr = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_user')['val'];

  $rsdec = gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val), '".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'smtp_password'");
  $rdec = gaz_dbi_fetch_row($rsdec);
  $psw = $rdec ? $rdec[0] : '';

  $port = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_port')['val'];

  // Imposta l'oggetto PHPMailer
  $mail = new PHPMailer(true);
  $mail->CharSet = 'UTF-8';
  $mail->isSMTP();  // Usa SMTP
  $mail->Host = $host;  // Server SMTP
  $mail->SMTPAuth = true;  // Abilita l'autenticazione SMTP
  $mail->Username = $usr;  // Nome utente SMTP
  $mail->Password = $psw;  // Password SMTP
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // TLS/SSL
  $mail->Port = $port;  // Porta SMTP
  $mail->Timeout = 10;  // Timeout in secondi

  return $mail;  // Restituisce l'oggetto PHPMailer
}

function set_imap($id_anagra){// restituisce le impostazioni imap in un array
  global $genTables,$azTables,$link,$IDaz;
  include("./manual_settings.php");
  if (intval($id_anagra)>0){
    $sql = "SELECT ".$genTables."anagra.custom_field, codice FROM ".$genTables."anagra"." LEFT JOIN ".$azTables."clfoco"." ON ".$azTables."clfoco".".id_anagra = ".$id_anagra." WHERE id = ".$id_anagra." AND codice LIKE '2%' LIMIT 1";
    if ($result = mysqli_query($link, $sql)) { // prendo il custom field del proprietario
      $anagra = mysqli_fetch_assoc($result);
      $custom_field=(isset($anagra['custom_field']))?$anagra['custom_field']:'';
    }else {
       echo "Error: " . $sql . "<br>" . mysqli_error($link);
    }
    if (isset($custom_field) && $data = json_decode($custom_field,true)){// se c'è un json e c'è una mail aziendale utente
      $imap=[]; // imap_pwr me la devo prendere per forza dal manul setting perché la decriptazione di quella di GAzie usa $_SESSION['aes_key'] e qui non ce l'ho
      if (isset($data['config']) && isset($data['config'][$IDaz])){ // se c'è il modulo "config" e c'è l'azienda attuale posso procedere
        $imap['imap_usr']=$data['config'][$IDaz]['imap_usr'];
        $imap['imap_sent_folder']=$data['config'][$IDaz]['imap_sent_folder'];
        $sql = "SELECT val FROM ".$azTables."company_config"." WHERE var = 'imap_server' LIMIT 1";
        if ($result = mysqli_query($link, $sql)) {
          $val = mysqli_fetch_assoc($result);
          $imap['imap_server']=$val['val'];
        }
        $sql = "SELECT val FROM ".$azTables."company_config"." WHERE var = 'imap_port' LIMIT 1";
        if ($result = mysqli_query($link, $sql)) {
          $val = mysqli_fetch_assoc($result);
          $imap['imap_port']=$val['val'];
        }
        $sql = "SELECT val FROM ".$azTables."company_config"." WHERE var = 'imap_secure' LIMIT 1";
        if ($result = mysqli_query($link, $sql)) {
          $val = mysqli_fetch_assoc($result);
          $imap['imap_secure']=$val['val'];
        }
        return $imap;
      } else{// provo a vedere se è connesso con un utente amministratore

        $sql = "SELECT adminid FROM ".$azTables."agenti"." WHERE id_fornitore = '".$anagra['codice']."' LIMIT 1";
        if ($result = mysqli_query($link, $sql)) {
          $val = mysqli_fetch_assoc($result);
         if (isset($val) && $val['adminid'] !== "no_user"){// se il proprietario è connesso con un utente admin
          $sql = "SELECT id_anagra FROM ".$genTables."admin"." WHERE user_name = '".$val['adminid']."' LIMIT 1";
          if ($result = mysqli_query($link, $sql)) {
            $val = mysqli_fetch_assoc($result);
            $sql = "SELECT custom_field FROM ".$genTables."anagra"." WHERE id = '".$val['id_anagra']."' LIMIT 1";
            if ($result = mysqli_query($link, $sql)) {
              $anagra = mysqli_fetch_assoc($result);
              $custom_field=$anagra['custom_field'];
              if ($data = json_decode($custom_field,true)){// se c'è un json
              if (isset($data['config']) && isset($data['config'][$IDaz])){ // se c'è il modulo "config" e c'è l'azienda attuale posso procedere
                $imap['imap_usr']=$data['config'][$IDaz]['imap_usr'];
                $imap['imap_sent_folder']=$data['config'][$IDaz]['imap_sent_folder'];
                $sql = "SELECT val FROM ".$azTables."company_config"." WHERE var = 'imap_server' LIMIT 1";
                if ($result = mysqli_query($link, $sql)) {
                  $val = mysqli_fetch_assoc($result);
                  $imap['imap_server']=$val['val'];
                }
                $sql = "SELECT val FROM ".$azTables."company_config"." WHERE var = 'imap_port' LIMIT 1";
                if ($result = mysqli_query($link, $sql)) {
                  $val = mysqli_fetch_assoc($result);
                  $imap['imap_port']=$val['val'];
                }
                $sql = "SELECT val FROM ".$azTables."company_config"." WHERE var = 'imap_secure' LIMIT 1";
                if ($result = mysqli_query($link, $sql)) {
                  $val = mysqli_fetch_assoc($result);
                  $imap['imap_secure']=$val['val'];
                }
                $imap['imap_pwr']=$imap_pwr;
                return $imap;
              }
              }
            }
          }
         }
        }
      }
    }
  }
  return false;
}

// Calcolo prezzo con sconti e controllo la prenotabilità in base min stay giornaliero del prezzo
function get_price_bookable($start,$end,$housecode,$aliquo,$ivac,$web_price,$web_url,$descri,$lang,$in_fixquote,$id_artico_group){
  global $genTables,$azTables,$link,$IDaz,$script_transl,$admin_aziend;
  $minstay_memo=0;
  $accommodations=array();
  $datediff = strtotime($end)-strtotime($start);
	$nights=round($datediff / (60 * 60 * 24));
  $accommodations['msg']=[];

  $accommodations['price']=0;
  $accommodations['codice']=$housecode;
  $accommodations['descri']=$descri;
  $accommodations['web_url']=get_string_lang($web_url, $lang);// se ci sono i tag lingua restituisco l'url nella lingua appropriata
  $accommodations['aliquo']=$aliquo;
  $startw=$start;
  while (strtotime($startw) < strtotime($end)) {// ciclo il periodo della locazione richiesta giorno per giorno
    //Calcolo del prezzo locazione
    $what = "price, minstay";
    $where = "start <= '". $startw ."' AND end >= '". $startw."' AND house_code ='".mysqli_real_escape_string($link,$housecode)."'";
    $sql = "SELECT ".$what." FROM ".$azTables."rental_prices"." WHERE ".$where;
    if ($result = mysqli_query($link, $sql)) {
      $prezzo = mysqli_fetch_array($result);
    }

    if (isset($prezzo['minstay']) && intval($prezzo['minstay'])>0 && intval($nights) < intval($prezzo['minstay'])){// se richiesto controllo se non si è raggiunto il soggiorno minimo giornaliero del prezzo
      if (intval($prezzo['minstay'])>$minstay_memo){
        $minstay_memo=intval($prezzo['minstay']);
        $accommodations['msg'][]=$script_transl['msg_minstay']." ".$prezzo['minstay']." ".$script_transl['nights']; //." ".$script_transl['msg_minstay2']." ".$nights;
        //echo "<br>",$housecode," Questo alloggio sarebbe disponibile ma il soggiorno minimo è di ",$prezzo['minstay']," notti mentre sono state richieste solo ",$nights, "notti";
        //break;
      }
    }

    // NB: il prezzo mostrato al pubblico deve essere sempre IVA compresa
    if (isset($prezzo)){// se c'è un prezzo nel calendario lo uso
      if ($ivac=="si"){
        $accommodations['price'] += floatval($prezzo['price']);// aggiungo il prezzo giornaliero torvato
      }else{
        $accommodations['price'] += floatval($prezzo['price'])+((floatval($prezzo['price'])*floatval($aliquo))/100);// aggiungo il prezzo e aggiungo l'iva
      }
    } elseif(floatval($web_price)>0){// altrimenti uso il prezzo base al quale devo sempre aggiungere l'iva
      $accommodations['price'] += floatval($web_price)+((floatval($web_price)*floatval($aliquo))/100);

    }else{// se non c'è alcun prezzo non posso prenotare e metto non prenotabile
      unset ($accommodations);
      return false;
    }
    $startw = date ("Y-m-d", strtotime("+1 days", strtotime($startw)));// aumento di un giorno il ciclo
  }
  // Se ho trovato prezzo disponibile procedo con il calcolo sconti
  $accommodations['fixquote'] = floatval($in_fixquote)+((floatval($in_fixquote)*floatval($aliquo))/100);// inizializzo eventuale quota fissa e aggiungo IVA
  $accommodations['price'] += $accommodations['fixquote'];

  // calcolo gli sconti
  $discounts=searchdiscount($housecode,$id_artico_group,$start,$end,$nights,$anagra=0);
  $accommodations['discount']=0;
  $accommodations['descri_discount']="";

  $today=date('Y-m-d');

  if (isset($discounts) && $discounts->num_rows >0){// se c'è almeno uno sconto
    foreach ($discounts as $discount){// li ciclo e applico lo sconto
      if (intval($discount['last_min'])>0){// se è un lastmin controllo la validità
        $date=date_create($today);
        date_add($date,date_interval_create_from_date_string($discount['last_min']." days"));
        $time=strtotime(date_format($date,"Y-m-d"));
        if ($time < strtotime($start)){
          continue; // non è valido, continuo con l'eventuale prossimo sconto

        }
      }
      if (intval($discount['level_points'])==0){// escludo gli eventuali sconto livello punti perché non ho ancora il cliente
        if ($accommodations['discount']>0){
          $accommodations['descri_discount'].="+";
        }
        if ($discount['is_percent']==1){
          $accommodations['discount']+= ((floatval($accommodations['price'])-$accommodations['discount'])*floatval($discount['value']))/100;// aggiungo al totale sconti, lo sconto calcolato in percentuale
          $accommodations['descri_discount'].=$discount['title']." ".$discount['value']."%";// incremento la descrizione con lo sconto applicato
        }else{
          $accommodations['discount']+= floatval($discount['value']);// aggiungo al totale sconti, lo sconto a valore
          $accommodations['descri_discount'].= $discount['title']." ".$admin_aziend['symbol']." ".$discount['value'];/// incremento la descrizione con lo sconto applicato

        }
        if ($discount['stop_further_processing']==1){// se questo deve bloccare i successivi eventuali, interrompo il conteggio
          break;
        }
      }
    }
  }
  return $accommodations;
}

function delete_id_cards($tesbro) {
    // Percorso della cartella principale
    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'self_checkin' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $tesbro;
    $keepFile = 'data.json';

    // Se la cartella dei documenti esiste
    if (is_dir($directory)) {
        $files = scandir($directory);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $directory . DIRECTORY_SEPARATOR . $file;

            if (is_file($filePath) && $file !== $keepFile) {
                @unlink($filePath); // @ sopprime eventuali warning
            }
        }
    }

    // Cerca la cartella selfie (es. self_12345_xyz)
    $pattern = __DIR__ . DIRECTORY_SEPARATOR . 'self_checkin' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'self_' . $tesbro . '*';
    $matchingFolders = glob($pattern, GLOB_ONLYDIR);
    // Se esiste almeno una cartella che corrisponde al pattern, cancellala
    if (!empty($matchingFolders)) {
        $selfieDir = $matchingFolders[0]; // Ne esiste solo una, per forza in quanto tesbro è univoco
        $files = scandir($selfieDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $filePath = $selfieDir . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }
        @rmdir($selfieDir); // Cancella la cartella una volta svuotata
    }
}
?>
