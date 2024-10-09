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
    preg_match_all('/(BEGIN:VEVENT.*?END:VEVENT)/si', $ical, $result, PREG_PATTERN_ORDER);
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
    if (isset($icalarray)){
      return $icalarray;
    }
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

// calcolo dei giorni da pagare per la tassa turistica fra due date specifiche
function tour_tax_daytopay($night,$start,$end,$tour_tax_from,$tour_tax_to,$tour_tax_day=0){
  $tour_tax_from=$tour_tax_from."-".date("Y", strtotime($start)); // aggiungo l'anno all'inizio pagamento tassa turistica
  $tour_tax_to=$tour_tax_to."-".date("Y", strtotime($start)); // aggiungo l'anno alla fine pagamento tassa turistica

  $daytopay=intval($night);
  if (strtotime($tour_tax_from)){// se è stato impostato un periodo specifico per la tassa turistica

    if (strtotime($start)>= strtotime($tour_tax_from) && strtotime($start)<= strtotime($tour_tax_to)){// se la data di inizio è dentro al periodo tassa turistica

     if (strtotime($end) > strtotime($tour_tax_to)){// se la fine prenotazione va fuori dal periodo tassa turistica
         $diff=date_diff(date_create($tour_tax_to),date_create($start));

         $daytopay= $diff->format("%a");

      }else{// se la fine prenotazione è dentro al periodo tassa turistica
        $diff=date_diff(date_create($end),date_create($start));
        $daytopay= $diff->format("%a");
      }
    }else{// se la data di inizio è fuori dal periodo tassa turistica
      if (strtotime($end) >= strtotime($tour_tax_from) AND strtotime($end)<= strtotime($tour_tax_to)){// se la fine prenotazione è dentro al periodo tassa turistica
        $diff=date_diff(date_create($end),date_create($tour_tax_from));
        $daytopay= $diff->format("%a");

      }else{// se la fine è fuori al periodo tassa turistica
        if (strtotime($start) < strtotime($tour_tax_from) && strtotime($end) > strtotime($tour_tax_to)){// se la prenotazione è a cavallo, cioè ingloba il periodo
          $diff=date_diff(date_create($tour_tax_to),date_create($tour_tax_from));// paga per il periodo della tassa turistica
          $daytopay= $diff->format("%a");
        }else{// se è fuori non paga nulla
          $daytopay=0;
        }
      }
    }
  }

  if (intval($tour_tax_day) >0 && intval($daytopay) > intval($tour_tax_day)){// se è stato impostato un numero massimo di giorni e i giorni da pagare sono di più di quelli pagabili, li riduco
    $daytopay=$tour_tax_day;
  }

  return $daytopay;
}

// calcolo totale della locazione
function get_totalprice_booking($tesbro,$tourist_tax=TRUE,$vat=FALSE,$preeminent_vat="",$add_extra=FALSE,$security_deposit=FALSE){// security_deposit viene calcolato, se TRUE, solo se il totale deve essere iva compresa +++ preeminent vat serve solo per calcolare l'iva sulle eventuali spese se è nulla le spese vanno senza iva
  if ($tesbro!==''){
    $tesbro=intval($tesbro);
    global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
    if ($azTables){
      $tablerig = $azTables."rigbro";
      $tabletes = $azTables."tesbro";
      $tableiva = $azTables."aliiva";
      $tableaz = $azTables."aziend";
	  $tableart = $azTables."artico";
    }else{
      $tablerig = $gTables['rigbro'];
      $tabletes = $gTables['tesbro'];
      $tableiva = $gTables['aliiva'];
      $tableaz = $gTables['aziend'];
	  $tableart = $gTables['artico'];
    }
    $where = " WHERE id_tes = '".$tesbro."'";
    if ($tourist_tax == TRUE && $add_extra==FALSE){// se richiesta la tassa turistica ma esclusi gli extra
      $where .= " AND (codart LIKE 'TASSA-TURISTICA%' OR (".$tableart.".custom_field REGEXP 'accommodation_type'))";
    }
    if ($add_extra==FALSE && $tourist_tax == FALSE){// escludo gli extra ma anche la tassa turistica
      $where .= " AND (".$tableart.".custom_field REGEXP 'accommodation_type')";
    }
    if ($tourist_tax == TRUE && $add_extra==TRUE){// se richiesta la tassa turistica e gli extra
      $where .= "";
    }
     if ($tourist_tax == FALSE && $add_extra==TRUE){// se richiesti solo gli extra
      $where .= " AND codart NOT LIKE 'TASSA-TURISTICA%'";
    }
    if ($vat==FALSE){// devo restituire l'imponibile
      $sql = "SELECT SUM(quanti * prelis) AS totalprice FROM ".$tablerig." LEFT JOIN ".$tableart." ON ".$tablerig.".codart = ".$tableart.".codice ".$where;
      if ($result = mysqli_query($link, $sql)) {
         $row = mysqli_fetch_assoc($result);
          $where = " WHERE id_tes = '".$tesbro."'";
          $sql = "SELECT speban FROM ".$tabletes.$where." LIMIT 1";
          if ($result = mysqli_query($link, $sql)) {
            $rowtes = mysqli_fetch_assoc($result);
            $rowtes['speban']=(isset($rowtes['speban']))?$rowtes['speban']:0;
            $totalprice= $row['totalprice']+$rowtes['speban'];// aggiungo eventuali spese bancarie
             return  $totalprice;
          }else{
            echo "Error: " . $sql . "<br>" . mysqli_error($link);
          }
      }else {
         echo "Error: " . $sql . "<br>" . mysqli_error($link);
      }
    }else{// devo restituire iva compresa

      $sql = "SELECT ".$tablerig.".quanti, ".$tablerig.".prelis, ".$tableiva.".aliquo, ".$tableart.".codice FROM ".$tablerig." LEFT JOIN ".$tableiva." ON ".$tableiva.".codice = ".$tablerig.".codvat "." LEFT JOIN ".$tableart." ON ".$tablerig.".codart = ".$tableart.".codice ".$where;
      $totalprice=0;$totalsecdep=0;
      if ($result = mysqli_query($link, $sql)) {
        foreach ($result as $res){
          $totalprice += ($res['prelis']*$res['quanti'])+((($res['prelis']*$res['quanti'])*$res['aliquo'])/100);
          if ($security_deposit==TRUE){
            $sql = "SELECT custom_field FROM ".$tableart." WHERE ".$tableart.".codice = '".$res['codice']."'";
            if ($result = mysqli_query($link, $sql)) {
              $row = mysqli_fetch_assoc($result);
              if (isset($row['custom_field']) && ($data = json_decode($row['custom_field'],true))){// se c'è un json in codart
                if (isset($data['vacation_rental']['security_deposit']) && floatval($data['vacation_rental']['security_deposit'])>0){
                  $totalsecdep += floatval($data['vacation_rental']['security_deposit']);
                }
              }
            }else{
              echo "Error: " . $sql . "<br>" . mysqli_error($link);
            }
          }
        }
        if (intval($preeminent_vat)>0){
          $sql = "SELECT aliquo FROM ".$tableiva." WHERE ".$tableiva.".codice = ".intval($preeminent_vat);
          if ($result = mysqli_query($link, $sql)) {
            $row = mysqli_fetch_assoc($result);
            $spevat=$row['aliquo'];
          }else{
            echo "Error: " . $sql . "<br>" . mysqli_error($link);
          }
        }else{
          $spevat=0;
        }
        $where = " WHERE id_tes = '".$tesbro."'";
        $sql = "SELECT speban FROM ".$tabletes.$where." LIMIT 1";
        if ($result = mysqli_query($link, $sql)) {
          $rowtes = mysqli_fetch_assoc($result);
          $rowtes['speban']=(isset($rowtes['speban']))?$rowtes['speban']:0;
          $rowtes['speban'] = $rowtes['speban']+(($rowtes['speban']*$spevat)/100);
          $totalprice= $totalprice+$rowtes['speban'];// aggiungo eventuali spese bancarie
          return  $totalprice+$totalsecdep;
        }else{
          echo "Error: " . $sql . "<br>" . mysqli_error($link);
        }
      }else {
         echo "Error: " . $sql . "<br>" . mysqli_error($link);
      }
    }
  }else{
    $err="tesbro vuoto";
    return $err ;
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
    $result = mysqli_query($link, $sql);

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
      $ret['totalprice_booking'] += ((get_totalprice_booking($row['id_tesbro'],FALSE))/$nights_event)*$tot_n_event_in_promemo;// aggiungo il costo medio della locazione(evento) calcolata sui giorni che rientrano nell'arco di tempo richiesto
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
      $ret['totalprice_booking'] += ((get_totalprice_booking($row['id_tesbro'],FALSE))/$nights_event)*$tot_n_event_in_promemo;// aggiungo il costo medio della locazione(evento) calcolata sui giorni che rientrano nell'arco di tempo richiesto
	  //il prezzo è imponibile e senza tassa turistica
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
  $where = " WHERE id_tesbro = '".$idtesbro."' AND payment_status = 'Completed'";
  $sql = "SELECT SUM(payment_gross) AS totalpaid FROM ".$tablerent_pay.$where;
  if ($result = mysqli_query($link, $sql)) {
    $row = mysqli_fetch_assoc($result);

    return $row['totalpaid'];
  }else {
     echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
}

function get_user_points_level($id_anagra){// determina il livello punti raggiunto dal cliente
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
    }else{
      $user_point=0;
    }
    if ($azTables){
      $table = $azTables."company_config";
    }else{
      $table = $gTables['company_config'];
    }
    $sql = "SELECT * FROM ". $table ." WHERE var = 'pointenable' ORDER BY id ASC LIMIT 1";
    if ($result = mysqli_query($link, $sql)) {// prendo il customfield in anagra
      $row = mysqli_fetch_assoc($result);
      $pointenable=$row['val'];
    }

    $sql = "SELECT * FROM ". $table ." WHERE var LIKE 'pointlevel%' ORDER BY id ASC";
    if ($result = mysqli_query($link, $sql)) {// prendo i livelli dalle impostazioni generali
      $levname="";$user_lev="";
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
      return $user_lev;// restituisco il numero del livello
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

function set_imap($id_anagra){// restituisce le impostazioni imap tranne la password
  global $genTables,$azTables,$link,$IDaz;
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
      return;
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
?>
