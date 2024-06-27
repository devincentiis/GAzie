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
require_once("lib.function.php");
$admin_aziend=checkAdmin();
$msg = "";

if(isset($_GET['Return'])){
  header("Location: ../../modules/vacation_rental/report_booking.php");
  exit;
}

if (!isset($_GET['gioini'])) { //al primo accesso allo script
    $_GET['gioini'] = "1";
    $_GET['mesini'] = "1";
    $_GET['annini'] = date("Y");
    $_GET['giofin'] = date("d");
    $_GET['mesfin'] = date("m");
    $_GET['annfin'] = date("Y");
}

if (!checkdate( $_GET['mesini'], $_GET['gioini'], $_GET['annini'])){
    $msg .= "1+";
}

if (!checkdate( $_GET['mesfin'], $_GET['giofin'], $_GET['annfin'])){
    $msg .= "2+";
}

if ($admin_aziend['conmag'] == 0){
    $msg .= "3+";
}

$utsini= mktime(0,0,0,$_GET['mesini'],$_GET['gioini'],$_GET['annini']);
$utsfin= mktime(0,0,0,$_GET['mesfin'],$_GET['giofin'],$_GET['annfin']);
$datainizio = date("Y-m-d",$utsini);
$datafine = date("Y-m-d",$utsfin);

if ($utsini > $utsfin)
    $msg .="1-4-2+";


require("../../library/include/header.php");
$script_transl = HeadMain();
echo "<form method=\"GET\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".ucfirst($script_transl[0])."</div>\n";
echo "<table class=\"Tmiddle table-striped\" align=\"center\">\n";
if (!empty($msg)) {
    $message = "";
    $rsmsg = array_slice( explode('+',chop($msg)),0,-1);
    foreach ($rsmsg as $value){
            $message .= $script_transl['error']."! -> ";
            $rsval = explode('-',chop($value));
            foreach ($rsval as $valmsg){
                    $message .= $script_transl[$valmsg]." ";
            }
            $message .= "<br />";
    }
    echo '<tr><td colspan="5" class="FacetDataTDred">'.$message."</td></tr>\n";
}
echo "<tr><td class=\"FacetFieldCaptionTD\">Statistiche dal giorno</td><td class=\"FacetDataTD\" colspan=\"3\">";
echo "\t <select name=\"gioini\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter = 1; $counter <= 31; $counter++ ){
    $selected = "";
    if($counter ==  $_GET['gioini'])
            $selected = "selected";
    echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"mesini\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
$gazTimeFormatter->setPattern('MMMM');
for( $counter = 1; $counter <= 12; $counter++ ){
  $selected = "";
  if($counter == $_GET['mesini']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"annini\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter =  date("Y")-10; $counter <=  date("Y")+10; $counter++ ){
    $selected = "";
    if($counter == $_GET['annini'])
            $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select></td></tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">al giorno</td><td class=\"FacetDataTD\" colspan=\"3\">";
echo "\t <select name=\"giofin\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter = 1; $counter <= 31; $counter++ ){
    $selected = "";
    if($counter ==  $_GET['giofin'])
            $selected = "selected";
    echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"mesfin\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter = 1; $counter <= 12; $counter++ ){
  $selected = "";
  if($counter == $_GET['mesfin']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"annfin\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter =  date("Y")-10; $counter <=  date("Y")+10; $counter++ ){
    $selected = "";
    if($counter == $_GET['annfin'])
            $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select></td></tr>\n";
if ($msg == "") {
    echo "<tr><td class=\"FacetFieldCaptionTD\"></td><td align=\"right\" colspan=\"4\"  class=\"FacetFooterTD\">
         <input type=\"submit\" name=\"Return\" value=\"".$script_transl['return']."\">&nbsp;<input type=\"submit\" name=\"anteprima\" value=\"".$script_transl['view']."!\">&nbsp;</td></tr>\n";
}
echo "</table>\n";
if (isset($_GET['anteprima']) and $msg == "") {

    $select = $gTables['rental_events'].".*,".$gTables['tesbro'].".*,".$gTables['artico'].".id_artico_group";
    $tabella = $gTables['rental_events']." LEFT JOIN ".$gTables['tesbro']." ON ".$gTables['rental_events'].".id_tesbro = ".$gTables['tesbro'].".id_tes LEFT JOIN ".$gTables['artico']." ON ".$gTables['rental_events'].".house_code = ".$gTables['artico'].".codice";
    $where = $gTables['rental_events'].".type = 'ALLOGGIO' AND ((".$gTables['rental_events'].".start BETWEEN '$datainizio' AND '$datafine') OR (".$gTables['rental_events'].".end BETWEEN '$datainizio' AND '$datafine') ) AND ".$gTables['tesbro'].".custom_field REGEXP 'CONFIRMED'";

    $result = gaz_dbi_dyn_query($select, $tabella, $where , 'start');
    $numrow = gaz_dbi_num_rows($result);

    while($rows[] = mysqli_fetch_assoc($result));array_pop($rows);// creo un array con tutte le prenotazioni

    $currentDate = strtotime($datainizio);
    $count=array();
    $fmt = new \IntlDateFormatter('it_IT', IntlDateFormatter::FULL, IntlDateFormatter::FULL);
    $fmt->setPattern('MMMM');

    echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
    if ($numrow > 0) {// se ci sono state prenotazioni avvio i calcoli statistici

      while ($currentDate <= strtotime($datafine)){ // ciclo un giorno alla volta tutto l'intervallo richiesto

        $month = $fmt->format(new \DateTime(date("Y-m-d",$currentDate)));
        $n=0;

        foreach ($rows as $row) {// per ogni locazione
          //echo "<pre>",print_r($row);
          if (($currentDate > strtotime($row['start'])) && ($currentDate <= strtotime($row['end']))){// se il giorno che sto analizzando è dentro la locazione
            (isset($rows[$n]['dayStat']))?$rows[$n]['dayStat']++:$rows[$n]['dayStat']=1;// contatore giorni di ogni locazione ($n) che sono dentro al periodo selezionato

            $facil=$row['id_artico_group'];// Chiave struttura turistica per raggruppamento statistiche
            //conteggio pernottamenti
            if( !array_key_exists($facil, $count) || !array_key_exists($month, $count[$facil])){// se è la prima volta che trovo questa struttura turistica
              // definisco i contatori e/o avvio i conteggi per la prima volta
              $count[$facil][$month]['pern_tot'] = $row['adult']+$row['child'];// pernottamenti totali per struttura e per mese
              $count[$facil][$month]['pern_tot_child'] = $row['child'];
              $count[$facil][$month]['pern_tot_ag'][$row['id_agente']] = $row['adult']+$row['child'];
              $count[$facil][$month]['tot_turtax']=0;
              $count[$facil][$month]['daytopay']=0;
            }else{
              // aggiungo i pernottamenti per mese al precedente valore
              $count[$facil][$month]['pern_tot'] += $row['adult']+$row['child'];
              $count[$facil][$month]['pern_tot_child'] += $row['child'];
              if (!array_key_exists($row['id_agente'], $count[$facil][$month]['pern_tot_ag'])){
                $count[$facil][$month]['pern_tot_ag'][$row['id_agente']] = $row['adult']+$row['child'];
              }else{
                $count[$facil][$month]['pern_tot_ag'][$row['id_agente']] += $row['adult']+$row['child'];
              }
            }
            if (!isset($count[$facil]['pern_tot'])){
              $count[$facil]['pern_tot'] = $row['adult']+$row['child'];// pernottamenti totali per struttura
            }else{
              $count[$facil]['pern_tot'] += $row['adult']+$row['child'];
            }
          }
          $n++;
        }

        $currentDate = strtotime("+1 day", $currentDate);
      }
      //echo "<pre>",print_r($rows);die;
      // eseguo i calcoli
      $general_tot_nights=0;
      foreach ($rows as $row) {// per ogni locazione
        //echo "<pre>locazione:",print_r($row),"</pre>";

        $row['end'] = date('Y-m-d', strtotime($row['end']. ' - 1 days'));// il giorno del check-out non conta per una notte

        $diff = date_diff(date_create($row['start']),date_create($row['end']));
        $nights = $diff->format("%a")+1;
        $general_tot_nights += $nights;
        $facil_gen_tot_nights[$row['id_artico_group']]=(isset($facil_gen_tot_nights[$row['id_artico_group']]))?($facil_gen_tot_nights[$row['id_artico_group']]+$nights):$nights;
        $facil_loca_tot[$row['id_artico_group']]=(isset($facil_loca_tot[$row['id_artico_group']]))?($facil_loca_tot[$row['id_artico_group']]+1):1;

        if (isset($row['dayStat']) && $row['dayStat']>0){// totale notti locazione
            //echo "<pre>",print_r($row);
            $facil=$row['id_artico_group'];// Chiave struttura turistica per raggruppamento statistiche
            // prendo dati
            $tabella = $gTables['rigbro']." LEFT JOIN ".$gTables['artico']." ON ".$gTables['rigbro'].".codart = ".$gTables['artico'].".codice LEFT JOIN ".$gTables['aliiva']." ON ".$gTables['rigbro'].".codvat = ".$gTables['aliiva'].".codice";
            $select = $gTables['rigbro'].".quanti, id_rig, ". $gTables['rigbro'].".codice_fornitore, id_tes, prelis,". $gTables['rigbro'].".sconto, ".$gTables['aliiva'].".aliquo, ".$gTables['artico'].".custom_field, ".$gTables['artico'].".codice";
            //$where = $gTables['rigbro'].".id_tes = '".$row['id_tes']."' AND ((".$gTables['artico'].".custom_field REGEXP 'accommodation_type') OR (".$gTables['artico'].".codice = 'TASSA-TURISTICA'))";
            $where = $gTables['rigbro'].".id_tes = '".$row['id_tes']."'";
            $result = gaz_dbi_dyn_query($select, $tabella, $where);
            $room_type=0;
            if ($facil > 0){ // se c'è un riferimento alla struttura turistica ne prendo i dati
              $facil_row = gaz_dbi_get_row($gTables['artico_group'], "id_artico_group",$facil);
              $data = json_decode($facil_row['custom_field'], TRUE);
              $tour_tax_from=(isset($data['vacation_rental']['tour_tax_from']))?$data['vacation_rental']['tour_tax_from']:'';
              $tour_tax_to=(isset($data['vacation_rental']['tour_tax_to']))?$data['vacation_rental']['tour_tax_to']:'';
              $tour_tax_day=(isset($data['vacation_rental']['tour_tax_day']))?$data['vacation_rental']['tour_tax_day']:'';// numero max giorni in cui si paga tassa turistica
              $open_from=(isset($data['vacation_rental']['open_from']))?$data['vacation_rental']['open_from']:'';
              $open_to=(isset($data['vacation_rental']['open_to']))?$data['vacation_rental']['open_to']:'';
              $year=date("Y",strtotime($datainizio));
              if ($open_from!==''){
                if (intval(substr($open_to,-2))<intval(substr($open_from,-2))){
                $open_from = $open_from."-".$year;
                $open_to = $open_to."-".($year+1);
                }else{
                $open_from = $open_from."-".$year;
                $open_to = $open_to."-".$year;
                }
                if(strtotime($datainizio)>strtotime($open_from)){// se il periodo analizzato è inferiore al periodo di apertura
                $open_from=$datainizio;
                }
                if(strtotime($datafine)<strtotime($open_to)){
                $open_to=$datafine;
                }
                $diff = date_diff(date_create($open_to),date_create($open_from));
                $open_nights = ($diff->format("%a"))+1;
                $open_day[$facil]=$open_nights;
              }else{
              $open_from='';
              $open_to='';
              $open_nights='';
              $open_day[$facil]=365;
              }
            } else {
              $tour_tax_from='';
              $tour_tax_to='';
              $tour_tax_day='';
              $open_from='';
              $open_to='';
              $open_nights='';
            }

            while ($val_row = gaz_dbi_fetch_array($result)){ // per ogni riga della locazione
              //echo "<pre>riga:",print_r($val_row),"</pre>";
              $tour_tax_day_count=0;
              unset($tot_turtax_memo);
              unset($presenze_turtax_memo);

              if (isset($val_row['custom_field']) && ($custom_field=json_decode($val_row['custom_field'], TRUE)) && isset($custom_field['vacation_rental']['accommodation_type'])){// se c'è il custom field ed è l'alloggio
              //echo "<pre>Alloggio:",print_r($val_row),"</pre>";

                $room_type = $custom_field['vacation_rental']['room_type'];// chiave per raggruppamento prezzo medio per tipo di alloggio

                $conta[$facil][$room_type]=(isset($conta[$facil][$room_type]))?$conta[$facil][$room_type]+1:1;// contatore locazioni per struttura e per tipo alloggio

                //tariffa applicata a notte
                $tarif_night= (($val_row['prelis']*$val_row['quanti'])-((($val_row['prelis']*$val_row['quanti'])*$val_row['sconto'])/100))/$nights;

                if( !array_key_exists('tot_tarif_periodo', $count[$facil])){
                  $count[$facil]['tot_tarif_periodo'] =  $tarif_night*$row['dayStat'];// totale vendite per la struttura imponibile
                  $count[$facil]['tot_tarif_periodo_ivac'] =  ($tarif_night*$row['dayStat'])+((($tarif_night*$row['dayStat'])*$val_row['aliquo'])/100);// per B12 andrà diviso per pernottamenti totali
                  $count[$facil]['tarif_periodo_pers'] =  ($tarif_night*$row['dayStat'])/$count[$facil]['pern_tot'];// Somma delle tariffe media a persona per la struttura imponibile
                  $count[$facil]['room_tarif_periodo_pers'][$room_type] =  ($tarif_night/($row['adult']+$row['child']))+((($tarif_night/($row['adult']+$row['child']))*$val_row['aliquo'])/100);//Somma delle Tariffe a notte per tipo di alloggio, media a persona IVA compresa
                  $count[$facil]['MIN_room_tarif_periodo_pers'][$room_type] =  $count[$facil]['room_tarif_periodo_pers'][$room_type]; // questo è il prezzo minimo

                } else {
                  $count[$facil]['tot_tarif_periodo'] +=  $tarif_night*$row['dayStat'];
                  $count[$facil]['tot_tarif_periodo_ivac'] +=  ($tarif_night*$row['dayStat'])+((($tarif_night*$row['dayStat'])*$val_row['aliquo'])/100);
                  $count[$facil]['tarif_periodo_pers'] +=  ($tarif_night*$row['dayStat'])/$count[$facil]['pern_tot'];
                  if (isset($count[$facil]['room_tarif_periodo_pers'][$room_type])){
                    $count[$facil]['room_tarif_periodo_pers'][$room_type] +=  ($tarif_night/($row['adult']+$row['child']))+((($tarif_night/($row['adult']+$row['child']))*$val_row['aliquo'])/100);

                    if ((($tarif_night/($row['adult']+$row['child']))+((($tarif_night/($row['adult']+$row['child']))*$val_row['aliquo'])/100))<$count[$facil]['MIN_room_tarif_periodo_pers'][$room_type]){
                      $count[$facil]['MIN_room_tarif_periodo_pers'][$room_type] =  ($tarif_night/($row['adult']+$row['child']))+((($tarif_night/($row['adult']+$row['child']))*$val_row['aliquo'])/100);
                    }
                  }else{
                    $count[$facil]['room_tarif_periodo_pers'][$room_type] =  ($tarif_night/($row['adult']+$row['child']))+((($tarif_night/($row['adult']+$row['child']))*$val_row['aliquo'])/100);
                    $count[$facil]['MIN_room_tarif_periodo_pers'][$room_type] =  $count[$facil]['room_tarif_periodo_pers'][$room_type];
                  }
                }

                if ($open_nights!==''){ // se c'è un periodo di apertura della struttura
                  $currentDate = strtotime($open_from);
                  while ($currentDate <= strtotime($open_to)){ // ciclo un giorno alla volta l'intervallo del periodo di apertura

                    if (($currentDate >= strtotime($row['start'])) && ($currentDate <= strtotime($row['end']))){ // se il giorno che sto analizzando è dentro la locazione

                        if (($currentDate > strtotime($open_from.'-'.date("Y",$currentDate)) && ($currentDate <= strtotime($open_to.'-'.date("Y",$currentDate))))){// il giorno fa parte della locazione quindi vedo se è dentro il periodo di apertura
                          // conteggio l'occupazione giornaliera della struttura (una sola volta al giorno)
                            if (isset($countDay[$facil]) && (!in_array($currentDate,$countDay[$facil]))){
                              $count[$facil]['count_pres'] = $count[$facil]['count_pres']-1;
                              $countDay[$facil][]=$currentDate;
                            }elseif (!isset($countDay[$facil])){
                              $count[$facil]['count_pres'] = $open_nights-1;
                              $countDay[$facil][]=$currentDate;
                            }
                        }
                    }
                    $currentDate = strtotime("+1 day", $currentDate);
                  }
                  if (!isset($count[$facil]['count_pres'])){
                    $count[$facil]['count_pres']=$open_nights;
                  }
                }

              }elseif ($val_row['codice']=="TASSA-TURISTICA"){// altrimenti è la tassa turistica
              //echo "<pre>Tassa turistica:",print_r($val_row);

                $currentDate = strtotime($datainizio);
                $rif_house = gaz_dbi_get_row($gTables['artico'], "codice",$val_row['codice_fornitore']);

                if (isset($rif_house) && $data_rif = json_decode($rif_house['custom_field'], TRUE)){
                }else{
                  echo "ERRORE nella tassa turistica manca il riferimento alloggio. Rif. id_rig:",$val_row['id_rig'];
                  die;
                }
                while ($currentDate <= strtotime($datafine)){ // RIciclo un giorno alla volta tutto l'intervallo richiesto per calcolare la tassa turistica

                if (intval($tour_tax_day) >0 && intval($tour_tax_day_count) >= intval($tour_tax_day)){// se ho raggiunto l'eventuale limite dei giorni da pagare
                  //echo"<br>RAGGIUNTO LIMITE:",intval($tour_tax_day_count);
                  break;// esco
                }
                $month = $fmt->format(new \DateTime(date("Y-m-d",$currentDate)));
                $tot_turtax_memo[$month] = (isset($tot_turtax_memo[$month]))?$tot_turtax_memo[$month]:0;
                $presenze_turtax_memo[$month] = (isset($presenze_turtax_memo[$month]))?$presenze_turtax_memo[$month]:0;
                  if (($currentDate >= strtotime($row['start'])) && ($currentDate <= strtotime($row['end'])) ){// se il giorno che sto analizzando è dentro la locazione
                    if (strlen($tour_tax_from)==0 ){// se la tassa turistica non è limitata ad un periodo di tempo definito, aggiungo un giorno
                      (isset($count[$facil][$month]['daytopay']))?$count[$facil][$month]['daytopay'] +=1:$count[$facil][$month]['daytopay'] =1;
                      $daytopay=1;

                    }else{// se ci sono condizioni speciali passo attraverso la funzione specifica
                      $daytopay =tour_tax_daytopay('1',date("Y-m-d",$currentDate),date("Y-m-d",strtotime("+1 day", $currentDate)),$tour_tax_from,$tour_tax_to,$tour_tax_day);

                      (isset($count[$facil][$month]['daytopay']))?$count[$facil][$month]['daytopay'] += $daytopay:$count[$facil][$month]['daytopay'] = $daytopay;

                      //echo "<br>start:",date("Y-m-d",$currentDate),"-end:",date("Y-m-d",strtotime("+1 day", $currentDate));
                    }

                    if (isset($data_rif['vacation_rental']['tur_tax_mode']) && intval($tour_tax_day_count) <= intval($tour_tax_day)){
                      //echo "<br>calcolo importo tassa giornaliera. day count=",intval($tour_tax_day_count)," - giorni massimi pagamento:",intval($tour_tax_day);
                      $tour_tax_day_count += $daytopay;
                      // calcolo prezzo tassa turistica
                      switch ($data_rif['vacation_rental']['tur_tax_mode']) {//0 => 'a persona', '1' => 'a persona escluso i minori', '2' => 'a notte', '3' => 'a notte escluso i minori'
                        case "0":
                          $count[$facil][$month]['tot_turtax'] +=floatval($data_rif['vacation_rental']['tur_tax'])*(intval($row['adult'])+intval($row['child']));
                          (isset($count[$facil][$month]['presenze']))?$count[$facil][$month]['presenze'] += (intval($row['adult'])+intval($row['child'])):$count[$facil][$month]['presenze'] = (intval($row['adult'])+intval($row['child']));
                          $presenze_turtax_memo[$month] += (intval($row['adult'])+intval($row['child']));
                          break;
                        case "1":
                          $count[$facil][$month]['tot_turtax'] +=floatval($data_rif['vacation_rental']['tur_tax'])*(intval($row['adult']));
                          (isset($count[$facil][$month]['presenze']))?$count[$facil][$month]['presenze'] += intval($row['adult']):$count[$facil][$month]['presenze'] = intval($row['adult']);
                          $presenze_turtax_memo[$month] += (intval($row['adult']));
                          break;
                        case "2":
                          if (isset($count[$facil][$month]['daytopay'])){
                          $count[$facil][$month]['tot_turtax'] +=(floatval($data_rif['vacation_rental']['tur_tax'])*(intval($daytopay)))*(intval($row['adult'])+intval($row['child']));
                          $tot_turtax_memo[$month] += (floatval($data_rif['vacation_rental']['tur_tax'])*(intval($daytopay)))*(intval($row['adult'])+intval($row['child']));
                          $presenze_turtax_memo[$month] += (intval($row['adult'])+intval($row['child']));
                          }else{

                          }
                          (isset($count[$facil][$month]['presenze']))?$count[$facil][$month]['presenze'] += (intval($row['adult'])+intval($row['child'])):$count[$facil][$month]['presenze'] = (intval($row['adult'])+intval($row['child']));
                          break;
                        case "3":
                          if (isset($count[$facil][$month]['daytopay'])){
                            $count[$facil][$month]['tot_turtax'] +=(floatval($data_rif['vacation_rental']['tur_tax'])*(intval($daytopay)))*(intval($row['adult']));
                            $tot_turtax_memo[$month] += (floatval($data_rif['vacation_rental']['tur_tax'])*(intval($daytopay)))*(intval($row['adult']));
                            $presenze_turtax_memo[$month] += intval($row['adult']);
                            (isset($count[$facil][$month]['presenze']))?$count[$facil][$month]['presenze'] += intval($row['adult']):$count[$facil][$month]['presenze'] = intval($row['adult']);
                            //echo "<br>mese:",$month," - contatore tassa per questa struttura importo:",$count[$facil][$month]['tot_turtax']," - tur tax giornaliera euro:",floatval($data_rif['vacation_rental']['tur_tax'])," - contatore conteggio giorni da pagare per struttura:",intval($count[$facil][$month]['daytopay'])," - adulti che pagano:",intval($row['adult'])," - giorni pagati per questo rigo locazione:",$daytopay;
                          }else{

                          }
                          break;
                        case "4":
                          $count[$facil][$month]['tot_turtax'] +=$data_rif['vacation_rental']['tur_tax'];
                          break;
                      }
                    }
                  }
                  $currentDate = strtotime("+1 day", $currentDate);
                }
				$tot_memo = 0;
				foreach ($tot_turtax_memo as $key => $value){// controllo finale con tassa realmente addebitata
					$tot_memo +=$value;
				}
				if ($tot_memo <> $val_row['prelis']){// se c'è differenza fra il calcolato e l'addebitato correggo
					//echo "<br>RISCONTRATA differenza. Calcolata:",$tot_memo," - Addebitata:",$val_row['prelis'];
					foreach ($presenze_turtax_memo as $key => $value){
						if (isset($count[$facil][$key]['presenze']) && intval($count[$facil][$key]['presenze'])>0 && intval($value)>0){
							$count[$facil][$key]['presenze']= $count[$facil][$key]['presenze'] - $value + ($val_row['prelis']/floatval($data_rif['vacation_rental']['tur_tax']));
						}
					}
					foreach ($tot_turtax_memo as $key => $value){
						if (isset($count[$facil][$key]['tot_turtax']) && intval($count[$facil][$key]['tot_turtax'])>0 && intval($value)>0){
							$count[$facil][$key]['tot_turtax']= $count[$facil][$key]['tot_turtax'] - $value + $val_row['prelis'];
							$val_row['prelis']=0;
						}
					}

				}
              }else{// altrimenti è un extra o un rigo testo
				if (floatval($val_row['prelis'])>0 && isset($val_row['custom_field']) && ($custom_field=json_decode($val_row['custom_field'], TRUE)) && isset($custom_field['vacation_rental']['extra'])){// se c'è un prezzo allora è un extra e lo conteggio
					if( !array_key_exists('tot_extra_periodo', $count[$facil])){
					  $count[$facil]['tot_extra_periodo'] =  number_format((($val_row['prelis']*$val_row['quanti'])-((($val_row['prelis']*$val_row['quanti'])*$val_row['sconto'])/100)),2);
					} else {
					  $count[$facil]['tot_extra_periodo'] +=  number_format((($val_row['prelis']*$val_row['quanti'])-((($val_row['prelis']*$val_row['quanti'])*$val_row['sconto'])/100)),2);
					}
				}
			  }


            }
        }
      }
      //echo "<pre> array generale contatori:",print_r ($count),"</pre>";
      ?>
      <div align="center" class="FacetFormHeaderFont">
        <?php echo "<h2>STATISTICHE delle prenotazioni </h2><p><h3>periodo dal ",date("d-m-Y",strtotime($datainizio)), " al ",date("d-m-Y",strtotime($datafine)),"</h3></p>"; ?>
      </div>
      <div class="panel panel-default gaz-table-form div-bordered" style="max-width:80%;">
        <div class="container-fluid">
          <div class="tab-content">
            <div align="center" class="FacetFormHeaderFont">
              <?php echo "Numero di prenotazioni: ",$numrow,"  -  permanenza media: ",gaz_format_number($general_tot_nights/$numrow)," notti";
; ?>
            </div>
            <?php
            $n=0;
            foreach ($count as $key => $item) {
              if (intval($key)>0){ // se c'è una struttura ne prendo i dati
                $facility = gaz_dbi_get_row($gTables['artico_group'], "id_artico_group",$key);
                $data = json_decode($facility['custom_field'], TRUE);
                $minor=(isset($data['vacation_rental']['minor']))?intval($data['vacation_rental']['minor']):'18';
                $open_from=(isset($data['vacation_rental']['open_from']))?$data['vacation_rental']['open_from']:'';
                $open_to=(isset($data['vacation_rental']['open_to']))?$data['vacation_rental']['open_to']:'';
                if (intval($minor==1)){
                  $minor=18;
                }
              }else{// senza struttura imposto dati standard (minori fino a 17 e sempre aperto
				  $minor=17;
				  $open_from='';
				  $open_to='';
			  }
              ?>
              <div class="row text-info bg-info">
                <?php if (intval($key)>0){ ?>
                <h4>STRUTTURA <?php echo $key, " ", $facility['descri']; ?></h4>
                <?php }else{?>
                <h4>ALLOGGI</h4> senza una struttura di appartenenza
                <?php }?>
              </div><!-- chiude row  -->
              <div class="row">
                <div class="form-group" >
                  <label for="descrizione" class="col-sm-4 control-label">Dati statistici</label>
                  <div class="col-sm-8">
                      <?php
                      //echo "<pre>",print_r($count[$key]);
                      $perntot_ag=0;$perntot_dir=0;
                      foreach ($count[$key] as $key2 => $item2) {
                        if ($key2=="pern_tot"){
                          $perntot=$count[$key]['pern_tot'];
                        }elseif($key2=="tot_tarif_periodo"){
                          echo "<br>Totale locazioni: € ",gaz_format_number($count[$key]['tot_tarif_periodo'])," (imponibile, escluso extra, esclusa tassa turistica)";
                          echo "<br>Numero locazioni: ",$facil_loca_tot[$key];
                          echo "<br>Permanenza media per locazione: ",gaz_format_number($facil_gen_tot_nights[$key]/$facil_loca_tot[$key])," notti";
                        }elseif($key2=="tot_tarif_periodo_ivac"){
                          echo "<br>B12- Tariffa media per presenza: € ",gaz_format_number(($count[$key]['tot_tarif_periodo_ivac'])/intval($perntot))," (iva compresa)";
                        }elseif($key2=="tarif_periodo_pers"){
                          echo "<br>Tariffa media a persona: € ",gaz_format_number($count[$key]['tarif_periodo_pers'])," (imponibile)";
                        }elseif($key2=="count_pres"){
                          echo "<br>B10- Giorni di apertura dell'esercizio nell'anno: ",$open_day[$key];
                          echo "<br>B11- Giorni di apertura senza alcuna presenza: ",$count[$key]['count_pres'];
                        }elseif($key2=="room_tarif_periodo_pers"){
                          foreach ($count[$key]['room_tarif_periodo_pers'] as $key3 => $item3) {
                            switch($key3){
                              case "1":
                                echo "<br>B20- Tariffa settimanale <b>monolocale</b> media a persona: € ",gaz_format_number(($count[$key]['room_tarif_periodo_pers'][1]/$conta[$key][1])*7)," (iva compresa)";
                                break;
                              case "2":
                                echo "<br>B21- Tariffa settimanale <b>bilocale</b> media a persona: € ",gaz_format_number(($count[$key]['room_tarif_periodo_pers'][2]/$conta[$key][2])*7)," (iva compresa)";
                                break;
                              case "3":
                                echo "<br>B22- Tariffa settimanale <b>trilocale</b> media a persona: € ",gaz_format_number(($count[$key]['room_tarif_periodo_pers'][3]/$conta[$key][3])*7)," (iva compresa)";
                                break;
                              case "4":
                                echo "<br>B23- Tariffa settimanale <b>quadrilocale</b> media a persona: € ",gaz_format_number(($count[$key]['room_tarif_periodo_pers'][4]/$conta[$key][4])*7)," (iva compresa)";
                                break;
                            }
                          }
                        }elseif($key2=="MIN_room_tarif_periodo_pers"){
                          foreach ($count[$key]['MIN_room_tarif_periodo_pers'] as $key3 => $item3) {
                            switch($key3){
                              case "1":
                                echo "<br>B20- Tariffa settimanale <b>monolocale</b> minima a persona: € ",gaz_format_number(($count[$key]['MIN_room_tarif_periodo_pers'][1])*7)," (iva compresa)";
                                break;
                              case "2":
                                echo "<br>B21- Tariffa settimanale <b>bilocale</b> minima a persona: € ",gaz_format_number(($count[$key]['MIN_room_tarif_periodo_pers'][2])*7)," (iva compresa)";
                                break;
                              case "3":
                                echo "<br>B22- Tariffa settimanale <b>trilocale</b> minima a persona: € ",gaz_format_number(($count[$key]['MIN_room_tarif_periodo_pers'][3])*7)," (iva compresa)";
                                break;
                              case "4":
                                echo "<br>B23- Tariffa settimanale <b>quadrilocale</b> minima a persona: € ",gaz_format_number(($count[$key]['MIN_room_tarif_periodo_pers'][4])*7)," (iva compresa)";
                                break;
                            }
                          }
                        }elseif($key2=="tot_extra_periodo"){
                         // questo totale lo srcivo in fondo
                        }else{ // è un mese
                            ?>
                            <div class="row text-info bg-info">
                            <b>MESE <?php echo $key2; ?></b>
                            <?php
                          foreach ($count[$key][$key2] as $key4 => $item4){

                            if ($key4=="pern_tot"){
                              echo "<br>Persone pernottate, totale mese: ",$count[$key][$key2]['pern_tot'];
                            }elseif ($key4=="pern_tot_child"){
                              echo "<br>Persone pernottate fino a ",$minor," anni di età, totale mese: ",$count[$key][$key2]['pern_tot_child'];
                            }elseif ($key4=="tot_turtax"){
                              echo "<br>Tassa turistica, importo totale mese € :",gaz_format_number($count[$key][$key2]['tot_turtax']);
                            }elseif ($key4=="daytopay"){
                              echo "<br>Tassa turistica, numero notti, delle locazioni, in cui è stata calcolata: " ,$count[$key][$key2]['daytopay'];
                            }elseif ($key4=="presenze"){
                              echo "<br>Tassa turistica, numero presenze a tariffa ordinaria: " ,$count[$key][$key2]['presenze'];
                            }else{
                              foreach ($count[$key][$key2]['pern_tot_ag'] as $key5 => $item5) {
                                if (intval($key5)>0){
                                  $clfoco_agente = gaz_dbi_get_row($gTables['agenti'], "id_agente",$key5)['id_fornitore'];
                                  $agente = gaz_dbi_get_row($gTables['clfoco'], "codice", $clfoco_agente)['descri'];
                                  echo "<br>Persone pernottate, da vendita con agenzia/tour operator ",$key5,"-<b>",$agente,"</b>: n. ",$count[$key][$key2]['pern_tot_ag'][$key5];
                                  $perntot_ag += intval($count[$key][$key2]['pern_tot_ag'][$key5]);
                                }else{
                                  echo "<br>Persone pernottate da vendita diretta: n.",$count[$key][$key2]['pern_tot_ag'][$key5];
                                  $perntot_dir += intval($count[$key][$key2]['pern_tot_ag'][$key5]);
                                }
                              }
                            }
                          }
                          ?>
                          </div><!-- chiude row  -->
                          <?php
                        }

                      }
                      echo "<br>B07- Pernottamenti totali: ",$perntot;
                      echo "<br>C20- Pernottamenti agenzie/tour operator: n ",$perntot_ag," = ",number_format(($perntot_ag/$perntot)*100,2,",","")," % ";
                      echo "<br>C19- Pernottamenti vendita diretta: n ",$perntot_dir," = ",number_format(($perntot_dir/$perntot)*100,2,",","")," % ";
                      if (isset($count[$key]['tot_extra_periodo'])){
                        echo "<br>Totale extra venduti: € ",gaz_format_number($count[$key]['tot_extra_periodo'])," (imponibile)";
                      }

					  ?>
                    </div>
                </div>
              </div><!-- chiude row  -->
              <?php
              $n++;
            }

          } else {
             echo "<tr><td class=\"FacetDataTDred\" align=\"center\">Non ci sono locazione nel periodo selezionato</td></tr>";
          }
          ?>
          </div><!-- chiude tab-content  -->
        </div><!-- chiude container-fluid  -->
      </div><!-- chiude panel  -->
    <?php
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
