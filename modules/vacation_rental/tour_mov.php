<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2025-present - Antonio Germani, Massignano (AP)
  (https://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
 --------------------------------------------------------------------------
                            GAzie - Gestione Azienda
    Copyright (C) 2004-2023 - Antonio De Vincentiis Montesilvano (PE)
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
require_once("lib.function.php");
$admin_aziend=checkAdmin();
$msg = "";
$xmlFileP ="";
$path = "files/".$admin_aziend['company_id']."/mov_turistiche";

function camere_occupate($day) {// Calcolo le camere occupate per un dato giorno
  global $gTables;
  $day = substr($day, 0, 10); // Assicura formato YYYY-MM-DD

  $select = $gTables['rental_events'].".id, SUM(JSON_EXTRACT(".$gTables['artico'].".custom_field, '$.vacation_rental.room_qta')) AS camere_occupate_struttura";

  $tabella = $gTables['rental_events']."
      LEFT JOIN ".$gTables['artico']."
      ON ".$gTables['rental_events'].".house_code = ".$gTables['artico'].".codice";

  $where =
      "'".$day."' >= DATE(".$gTables['rental_events'].".checked_in_date)
	  AND ".$gTables['rental_events'].".checked_in_date IS NOT NULL AND ".$gTables['rental_events'].".checked_in_date != '0000-00-00 00:00:00'
      AND '".$day."' < ".$gTables['rental_events'].".end
      AND ".$gTables['rental_events'].".type = 'ALLOGGIO'
      AND ".$gTables['artico'].".id_artico_group = ".intval($_GET['XML'])."
      AND JSON_EXTRACT(".$gTables['artico'].".custom_field, '$.vacation_rental.room_qta') IS NOT NULL";
  $res_sum = gaz_dbi_dyn_query($select, $tabella, $where);

  $sum = gaz_dbi_fetch_assoc($res_sum) ?: [];
  $sum['camere_occupate_struttura'] = isset($sum['camere_occupate_struttura']) && $sum['camere_occupate_struttura'] !== null ? $sum['camere_occupate_struttura']  : 0;

  return $sum;
}

if(isset($_GET['Return'])){
  header("Location: ../../modules/vacation_rental/report_booking.php");
  exit;
}

if (!isset($_GET['gioini'])) { //al primo accesso allo script
    $_GET['gioini'] = date("d");
    $_GET['mesini'] = date("m");
    $_GET['annini'] = date("Y");
}

if (!checkdate( $_GET['mesini'], $_GET['gioini'], $_GET['annini'])){
    $msg .= "1+";
}


if ($admin_aziend['conmag'] == 0){
    $msg .= "3+";
}

$utsini= mktime(0,0,0,$_GET['mesini'],$_GET['gioini'],$_GET['annini']);
$datainizio = date("Y-m-d",$utsini);

?>
<script type="text/javascript">
      function disable_xml(){
        $('#XML').css("display", "none");
      };
</script>
<?php

require("../../library/include/header.php");

$script_transl = HeadMain();
echo "<form method=\"GET\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">Creazione file xml per la trasmissione telematica della movimentazione turistica</div>\n";

echo "<table class=\"Tmiddle table-striped\" align=\"center\">\n";
if (!empty($msg)) {
    $message = "";
    $rsmsg = array_slice( explode('+',chop($msg)),0,-1);
    foreach ($rsmsg as $value){
      $message .= $script_transl['error']."! -> ";
      $rsval = explode('-',chop($value));
      foreach ($rsval as $valmsg){
              $message .= (isset($script_transl[$valmsg]))?$script_transl[$valmsg]." ":'';
      }
      $message .= "<br />";
    }
    echo '<tr><td colspan="5" class="FacetDataTDred">'.$message."</td></tr>\n";
}
echo "<tr><td class=\"FacetFieldCaptionTD\">Movimenti del giorno</td><td class=\"FacetDataTD\" colspan=\"3\">";
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

if ($msg == "") {
    echo "<tr><td class=\"FacetFieldCaptionTD\"></td><td align=\"right\" colspan=\"4\"  class=\"FacetFooterTD\">
         <input type=\"submit\" name=\"Return\" value=\"".$script_transl['return']."\">&nbsp;<input type=\"submit\" name=\"anteprima\" value=\"".$script_transl['view']."!\">&nbsp;</td></tr>\n";
}
echo "</table>\n";
?>
<div class="text-center my-4">
  <button class="openIframeBtn" data-url="consultazione_schedine.php?path=<?php echo $path; ?>" type="button">Consultazione ricevute alloggiati Polizia</button>
</div>

<?php

if (isset($_GET['anteprima']) and $msg == "") {

    $select = $gTables['rental_events'].".*,".$gTables['artico'].".custom_field AS art_custom,".$gTables['artico'].".id_artico_group , ".$gTables['artico_group'].".descri, JSON_EXTRACT(".$gTables['artico_group'].".custom_field, '$.vacation_rental.csmt') AS csmt, ".$gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2, JSON_EXTRACT(".$gTables['tesbro'].".custom_field, '$.vacation_rental.self_checkin_status') AS self_checkin_status, JSON_EXTRACT(".$gTables['tesbro'].".custom_field, '$.vacation_rental.pre_checkin_status') AS pre_checkin_status, JSON_EXTRACT(".$gTables['tesbro'].".custom_field, '$.vacation_rental.man_checkin_status') AS man_checkin_status";
    $tabella = $gTables['rental_events']." LEFT JOIN ".$gTables['artico']." ON ".$gTables['rental_events'].".house_code = ".$gTables['artico'].".codice LEFT JOIN ".$gTables['artico_group']." ON ".$gTables['artico_group'].".id_artico_group = ".$gTables['artico'].".id_artico_group LEFT JOIN ".$gTables['tesbro']." ON ".$gTables['tesbro'].".id_tes = ".$gTables['rental_events'].".id_tesbro LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['clfoco'].".codice = ".$gTables['tesbro'].".clfoco LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra ";
    $where = $gTables['rental_events'].".type = 'ALLOGGIO' AND DATE(".$gTables['rental_events'].".checked_in_date) = '".$datainizio."'";

    $result = gaz_dbi_dyn_query($select, $tabella, $where , 'start');

    //echo "<br>Tabella: ",$tabella,"<br> where: ",$where;

    $numrow = gaz_dbi_num_rows($result);

    foreach ($result as $elemento) {
      // Usa il valore descri come chiave nell'array di accumulo
      $raggruppato[$elemento['descri']][] = $elemento;// questo è l'array dei check-in raggruppati per struttura
    }

    ?>
      <div align="center" class="FacetFormHeaderFont">
        <?php echo "<h2>Creazione file xml per movimentazione turistica </h2><p><h3>Check-in del ",date("d-m-Y",strtotime($datainizio)),"</h3></p>"; ?>
      </div>
      <div class="panel panel-default gaz-table-form div-bordered" style="max-width:80%;">
        <div class="container-fluid">

            <div align="center" class="FacetFormHeaderFont">
              <?php echo "Numero di check-in odierni: ",$numrow;?>
            </div>
              <?php
              if ($numrow==0){

              }else{
                foreach ($raggruppato as $key => $value){

                  ?>
                  <div class="row text-info bg-info">
                    <?php if (strlen($key) > 0) {
                      if ($raggruppato[$key][0]['csmt']==null){
                        ?>
                        <h4>STRUTTURA <?php echo $key; ?></h4><p class="text-danger"> senza codice codice identificativo per movimentazione turistica </p>
                        <?php
                      }else{
                      ?>
                        <h4>STRUTTURA <?php echo $key; ?></h4>
                        <div id="XML">
                          crea file XML<input  type="submit" name="XML" value="<?php echo $raggruppato[$key][0]['id_artico_group']. "~" . substr($raggruppato[$key][0]['csmt'], 1, -1); ?>">
                        </div>
                      <?php
                      }
                    } else { ?>
                        <h4>ALLOGGIO</h4> senza una struttura di appartenenza
                        <div id="XML">
                          <!-- <input type="submit" name="XML" value="Crea file XML"> -->
                        </div>
                    <?php

                    }
                    ?>

                  </div><!-- chiude row -->

                  <!-- Intestazione -->
                  <div class="row" style="font-weight: bold; border-bottom: 2px solid #333; padding: 8px 0;">
                    <div class="col-xs-3 text-center">Nome capogruppo</div>
                    <div class="col-xs-2 text-center">Accettazione</div>
                    <div class="col-xs-1 text-center">Persone (adulti+minori)</div>
                    <div class="col-xs-2 text-center">Alloggio</div>
                    <div class="col-xs-1 text-center">Camere</div>
                    <div class="col-xs-1 text-center">SELF-checked in</div>
                    <div class="col-xs-1 text-center">PRE-checked in</div>
					<div class="col-xs-1 text-center">Admin-checked in</div>
                  </div>

                    <?php
                    $disable_xml=0;
                  foreach ($raggruppato[$key] as $alloggio) { // per ogni check-in giornaliero della struttura

					// carico il json del pre-checkin
					$DownloadDir = __DIR__.DIRECTORY_SEPARATOR.'self_checkin'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$alloggio['id_tesbro'].'/data.json';
					if ($json_string = @file_get_contents($DownloadDir)){
						$dati = json_decode($json_string, true); // true = converte in array associativo
						//echo "<pre>",print_r($dati),"</pre>";
					}else{
					  echo "<br>ERRORE: manca il file del checkin";exit;
					}
                      $data = json_decode($alloggio['art_custom'],true);
                      if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental" nel custom field
						if (isset($data['vacation_rental']['accommodation_type']) && isset ($data['vacation_rental']['room_qta'])){
                           $type= array(3 => 'Appartamento', 4 => 'Casa vacanze', 5=> 'Bed & breakfast', 6=> 'Camera', 7=> 'Locazione turistica');
						}else{
							echo "ERRORE: Alcuni dati della struttura non sono stati impostati (tipo o numero camere)";exit;
						}
                      }else{
                        echo "ERRORE: manca il custom field";exit;
                      }

                      if (intval($alloggio['self_checkin_status'])==0 && intval($alloggio['pre_checkin_status'])==0 ){// NON posso creare il file, manca il check-in
                        $disable_xml=1;
                      }
					  if ($alloggio['man_checkin_status'] == 1 ){// Correggo, posso, perché è stato fatto dal lato admin di GAzie
                        $disable_xml=0;
                      }
                    ?>
                    <!-- Righe di dati -->
                    <div class="row" style="padding: 8px 0; border-bottom: 1px solid #ccc;">
                      <div class="col-xs-3 text-left"><?php echo $dati[0]['nome']," ",$dati[0]['cognome']; ?></div>
                      <div class="col-xs-2 text-left"><?php echo $alloggio['checked_in_date']; ?></div>
                      <div class="col-xs-1 text-left"><?php echo $alloggio['adult'],"+",$alloggio['child']; ?></div>
                      <div class="col-xs-2 text-center"><?php echo $type[$data['vacation_rental']['accommodation_type']]," ",$alloggio['house_code']; ?></div>
                      <div class="col-xs-1 text-center"><?php echo $data['vacation_rental']['room_qta']; ?></div>
                      <div class="col-xs-1 text-left"><?php echo $alloggio['self_checkin_status']?? '-'; ?></div>
                      <div class="col-xs-1 text-left"><?php echo $alloggio['pre_checkin_status']?? '-'; ?></div>
					  <div class="col-xs-1 text-left"><?php echo $alloggio['man_checkin_status']?? '-'; ?></div>
                    </div>
                    <?php
                  }
                  if ($disable_xml==1){// disabilito il pulsante
                    ?>
                    <script>
                      disable_xml();
                    </script>
                    <?php
                  }
                }
              }
              ?>
        </div>
       </div>
<?php
}

if (isset($_GET['XML']) and $msg == "") {

  // Calcolo le camere occupate nella struttura per il dato giorno di check-in
   $camere_occupate_struttura = camere_occupate($datainizio)['camere_occupate_struttura'];


  // prendo i movimenti check-in da registrare
  $select = $gTables['rental_events'].".*,".$gTables['artico'].".custom_field AS art_custom,".$gTables['artico'].".id_artico_group , ".$gTables['artico_group'].".descri, JSON_EXTRACT(".$gTables['artico_group'].".custom_field, '$.vacation_rental.csmt') AS csmt, ".$gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2, JSON_EXTRACT(".$gTables['tesbro'].".custom_field, '$.vacation_rental.self_checkin_status') AS self_checkin_status, JSON_EXTRACT(".$gTables['tesbro'].".custom_field, '$.vacation_rental.pre_checkin_status') AS pre_checkin_status";
  $tabella = $gTables['rental_events']." LEFT JOIN ".$gTables['artico']." ON ".$gTables['rental_events'].".house_code = ".$gTables['artico'].".codice LEFT JOIN ".$gTables['artico_group']." ON ".$gTables['artico_group'].".id_artico_group = ".$gTables['artico'].".id_artico_group LEFT JOIN ".$gTables['tesbro']." ON ".$gTables['tesbro'].".id_tes = ".$gTables['rental_events'].".id_tesbro LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['clfoco'].".codice = ".$gTables['tesbro'].".clfoco LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra ";
  $where = $gTables['rental_events'].".type = 'ALLOGGIO' AND ".$gTables['rental_events'].".checked_in_date BETWEEN '".$datainizio." 00:00:00' AND '".$datainizio." 23:59:59' AND ".$gTables['artico'].".id_artico_group = ".intval($_GET['XML']);
  $result = gaz_dbi_dyn_query($select, $tabella, $where , 'checked_in_date');

  list($id_artico_group, $csmt) = explode('~', $_GET['XML']);

  // prendo i dati da inserire in struttura
  $select = $gTables['artico'].".custom_field";
  $tabella = $gTables['artico'];
  $where = $gTables['artico'].".id_artico_group = ".$id_artico_group;
  $resstr = gaz_dbi_dyn_query($select, $tabella, $where );
  $total_guests=$room_qta=0;
  while ($rowcustom = gaz_dbi_fetch_array($resstr)){ // questo è il totale per struttura
    $datastr = json_decode($rowcustom['custom_field'], true);
    $total_guests += intval($datastr['vacation_rental']['total_guests']);
    $room_qta += intval($datastr['vacation_rental']['room_qta']);// totale camere struttura
    $id_polstat = (isset($datastr['vacation_rental']['id_polstat']))?$datastr['vacation_rental']['id_polstat']:'';
  }


  // creo il file xml
	$xml_output = '<?xml version="1.0" encoding="UTF-8"?><!-- GAzieDocuments AppVersion="1" Creator="Antonio Germani Copyright" CreatorUrl="https://www.programmisitiweb.lacasettabio.it" -->';
	$xml_output .= "\n<movimenti>\n";
  $xml_output .= "\t<codice>".$csmt."</codice>\n";
  $xml_output .= "\t<prodotto>"."GAzie Vacation rental"."</prodotto>\n";
  $xml_output .= "\t<movimento>\n";
  $xml_output .= "\t\t<data>".date("Ymd",$utsini)."</data>\n";// data dell'effettivo check-in
  $xml_output .= "\t\t<struttura>\n";
  $xml_output .= "\t\t\t<apertura>SI</apertura>\n";
  $xml_output .= "\t\t\t<camereoccupate>".$camere_occupate_struttura."</camereoccupate>\n";// effettivamente occupate
  $xml_output .= "\t\t\t<cameredisponibili>".$room_qta."</cameredisponibili>\n";// potenzialmente disponibili
  $xml_output .= "\t\t\t<lettidisponibili>".$total_guests."</lettidisponibili>\n";// persone potenzialmente ospitabili
  $xml_output .= "\t\t</struttura>\n";
  $xml_output .= "\t\t<arrivi>\n";

  $file_polstat=array(); // inizializzo la matrice che comporrà il file per la Polizia di Stato. Ogni elemento sarà una riga/alloggiato
  $check_outs=array();
  $nguest=0;
  $maxend=0;
  while ($row = gaz_dbi_fetch_array($result)){// CICLO LE PRENOTAZIONI:  per ogni prenotazione
    $dataart = json_decode($row['art_custom'], true);
    $room_house = (isset($dataart['vacation_rental']['room_qta']))?$dataart['vacation_rental']['room_qta']:'';
    if (strtotime($row['end']) > strtotime($maxend)){
      $maxend=$row['end'];
    }
    //$date1 = new DateTime($row['start']);
    $date1 = new DateTime($datainizio);// devo usare la data dell'effettivo check-in perché potrebbe non essere stato eseguito come da prenotazione
    $date2 = new DateTime($row['end']);
    $diff = $date1->diff($date2);
    $nights = $diff->days;
    // carico il json del pre-checkin
    $DownloadDir = __DIR__.DIRECTORY_SEPARATOR.'self_checkin'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$row['id_tesbro'].'/data.json';
    if ($json_string = @file_get_contents($DownloadDir)){
    $dati = json_decode($json_string, true); // true = converte in array associativo
    //echo "<pre>",print_r($dati);
    }else{
      echo "<br>ERRORE: manca il file del checkin";exit;
    }
    $n=0;
    $testate[]=$row['id_tesbro'];
    foreach($dati as $guest){// per ogni ospite presente nel file del pre checkin

      $file_polstat[$n]='';
      $sex=($guest['sex']=='F')?2:1;
      $idswh=(string)(intval($row['id_tesbro'])).$n;
      $xml_output .= "\t\t\t<arrivo>\n";
      $xml_output .= "\t\t\t\t<idswh>".$idswh."</idswh>\n";
      if(intval($row['adult'])+intval($row['child']) > 1){// più di una persona
        if ($n==0){// se è il capogruppo
          $tipoalloggiato='18';
        }else{
          $tipoalloggiato='20';// membro gruppo
        }
      }else{
        $tipoalloggiato='16';// single
      }
      // in $check_outs mi prendo gli elementi per registrare poi le partenze check-out
      $check_outs[$row['end']][$nguest]['idswh']=$idswh;
      $check_outs[$row['end']][$nguest]['tipoalloggiato']=$tipoalloggiato;
      $check_outs[$row['end']][$nguest]['arrivo']=$datainizio;

      $guest['cognome'] = preg_replace('/[^A-Za-z\'\-\s]/', '', $guest['cognome']);
      $guest['nome'] = preg_replace('/[^A-Za-z\'\-\s]/', '', $guest['nome']);

      $file_polstat[$n].=str_pad($tipoalloggiato, 2);
      $file_polstat[$n].=str_pad(date("d/m/Y",$utsini), 10);
      $file_polstat[$n].=str_pad($nights, 2);
      $file_polstat[$n].=str_pad($guest['cognome'], 50);// cognome
      $file_polstat[$n].=str_pad($guest['nome'], 30);// nome
      $file_polstat[$n].=str_pad($sex, 1);
      $file_polstat[$n].=str_pad((new DateTime($guest['datnas']))->format('d/m/Y'), 10);// data nascita

      if ($guest['coucard']<>"IT"){
        $cittadinanza="100000".gaz_dbi_get_row($gTables['country'], 'iso', $guest['coucard'])['istat_country'];
        $luogorilascidoc=$cittadinanza;
      }else{
        $cittadinanza="100000100";// Italia
        $municip=gaz_dbi_get_row($gTables['municipalities'], 'name', $guest['loccard']);
		if (isset($municip['id_province'])){
			$provin=gaz_dbi_get_row($gTables['provinces'], 'id', $municip['id_province']);
			$luogorilascidoc="4".str_pad($provin['id_region'], 2, "0", STR_PAD_LEFT).$municip['stat_code'];
		}else{
			$luogorilascidoc="";
		}
      }
      if ($guest['country']<>"IT"){
        $statoresidenza="100000".gaz_dbi_get_row($gTables['country'], 'iso', $guest['country'])['istat_country'];
        $luogoresidenza="";
      }else{
        $statoresidenza="100000100";// Italia

        $municip=gaz_dbi_get_row($gTables['municipalities'], 'name', $guest['citspe']);
        $provin=gaz_dbi_get_row($gTables['provinces'], 'id', $municip['id_province']);
        $luogoresidenza="4".str_pad($provin['id_region'], 2, "0", STR_PAD_LEFT).$municip['stat_code'];
      }
      if ($guest['counas']<>"IT"){
        $statonascita="100000".gaz_dbi_get_row($gTables['country'], 'iso', $guest['counas'])['istat_country'];
        $comunenascita="";
      }else{
        $statonascita="100000100";// Italia
        $municip=gaz_dbi_get_row($gTables['municipalities'], 'name', $guest['luonas']);
        $provin=gaz_dbi_get_row($gTables['provinces'], 'id', $municip['id_province']);
        $comunenascita="4".str_pad($provin['id_region'], 2, "0", STR_PAD_LEFT).$municip['stat_code'];
      }

      $file_polstat[$n].=str_pad($comunenascita, 9);
      $file_polstat[$n].=str_pad($guest['pronas'], 2);
      $file_polstat[$n].=str_pad($statonascita, 9);// stato di nascita
      $file_polstat[$n].=str_pad($cittadinanza, 9);// stato cittadinanza

      $xml_output .= "\t\t\t\t<tipoalloggiato>".$tipoalloggiato."</tipoalloggiato>\n";
      if ($n==0){// è il capogruppo
        $idcapo=$idswh;
        $xml_output .= "\t\t\t\t<idcapo></idcapo>\n";
        // $xml_output .= "\t\t\t\t<camere>".$room_house."</camere>\n"; // NON ammesso. Strano perché l'insermiento manuale lo richiede ...
        $file_polstat[$n].=str_pad($guest['tipdoc'], 5);
        $file_polstat[$n].=str_pad($guest['numdoc'], 20);
        $file_polstat[$n].=str_pad($luogorilascidoc, 9);// Luogo di rilascio documento

      }else{
        $xml_output .= "\t\t\t\t<idcapo>".$idcapo."</idcapo>\n";

        $file_polstat[$n].=str_pad(' ', 34);// riempimento vuoto per i membri o familiari

      }
      $xml_output .= "\t\t\t\t<cognome>".$guest['cognome']."</cognome>\n";
      $xml_output .= "\t\t\t\t<nome>".$guest['nome']."</nome>\n";
      $xml_output .= "\t\t\t\t<sesso>".$guest['sex']."</sesso>\n";
      $xml_output .= "\t\t\t\t<cittadinanza>".$cittadinanza."</cittadinanza>\n";
      $xml_output .= "\t\t\t\t<statoresidenza>".$statoresidenza."</statoresidenza>\n";
      $xml_output .= "\t\t\t\t<luogoresidenza>".$luogoresidenza."</luogoresidenza>\n";
      $xml_output .= "\t\t\t\t<datanascita>" . (new DateTime($guest['datnas']))->format('Ymd') . "</datanascita>\n";
      $xml_output .= "\t\t\t\t<statonascita>".$statonascita."</statonascita>\n";
      $xml_output .= "\t\t\t\t<comunenascita>".$comunenascita."</comunenascita>\n";
      $xml_output .= "\t\t\t\t<tipoturismo>Non specificato</tipoturismo>\n";
      $xml_output .= "\t\t\t\t<mezzotrasporto>Non specificato</mezzotrasporto>\n";
      $xml_output .= "\t\t\t</arrivo>\n";
      $fileUnico=0;
      if (strlen($id_polstat)>0){// FILE UNICO: se l'alloggio dispone di identificativo polstat lo aggiungo
        $file_polstat[$n].=str_pad($id_polstat, 6);// id appartamento polstat
        $fileUnico=1;
      }

     $n++;
     $nguest++;
    }

  }

  $xml_output .= "\t\t</arrivi>\n";
  $xml_output .= "\t</movimento>\n";

  //Adesso creo i movimenti per le partenze
$exclude=array();
foreach ($check_outs as $data => $alloggiati) {
  $exclude[] = $data;
  $camere_occupate_struttura = camere_occupate($data)['camere_occupate_struttura'];

  $xml_output .= "\t<movimento>\n";
  $xml_output .= "\t\t<data>". (new DateTime($data))->format('Ymd') ."</data>\n";// data dell'effettivo check-in
  $xml_output .= "\t\t<struttura>\n";
  $xml_output .= "\t\t\t<apertura>SI</apertura>\n";
  $xml_output .= "\t\t\t<camereoccupate>".$camere_occupate_struttura."</camereoccupate>\n";// effettivamente occupate
  $xml_output .= "\t\t\t<cameredisponibili>".$room_qta."</cameredisponibili>\n";// potenzialmente disponibili
  $xml_output .= "\t\t\t<lettidisponibili>".$total_guests."</lettidisponibili>\n";// persone potenzialmente ospitabili
  $xml_output .= "\t\t</struttura>\n";
  $xml_output .= "\t\t<partenze>\n";

  foreach ($alloggiati as $persona) {
    $xml_output .= "\t\t\t<partenza>\n";
    $xml_output .= "\t\t\t\t<idswh>".$persona['idswh']."</idswh>\n";
    $xml_output .= "\t\t\t\t<tipoalloggiato>".$persona['tipoalloggiato']."</tipoalloggiato>\n";
    $xml_output .= "\t\t\t\t<arrivo>".(new DateTime($persona['arrivo']))->format('Ymd')."</arrivo>\n";
    $xml_output .= "\t\t\t</partenza>\n";
  }

  $xml_output .= "\t\t</partenze>\n";
  $xml_output .= "\t</movimento>\n";

}

// qui devo creare i movimenti per la struttura per ogni giorno di permanenza
// Escludo gli estremi che vanno gestiti con movimento di arrivo e partenza
$date2 = new DateTime($maxend);
$date1->modify('+1 day');
$interval = new DateInterval('P1D');
$periodo = new DatePeriod($date1, $interval, $date2); // fine già corretta
foreach ($periodo as $date) {
  if (in_array($date->format('Y-m-d'), $exclude)) {
      continue; // salto la data se è nella lista delle escluse perché c'è stato un check-out
  }
  //DEVO RICALCOLARE LE CAMERE OCCUPATE GIORNO PER GIORNO
  $camere_occupate_struttura = camere_occupate($date->format('Y-m-d'))['camere_occupate_struttura'];
  $xml_output .= "\t<movimento>\n";
  $xml_output .= "\t\t<data>". $date->format('Ymd') ."</data>\n";// data dell'effettivo check-in
  $xml_output .= "\t\t<struttura>\n";
  $xml_output .= "\t\t\t<apertura>SI</apertura>\n";
  $xml_output .= "\t\t\t<camereoccupate>".$camere_occupate_struttura."</camereoccupate>\n";// effettivamente occupate
  $xml_output .= "\t\t\t<cameredisponibili>".$room_qta."</cameredisponibili>\n";// potenzialmente disponibili
  $xml_output .= "\t\t\t<lettidisponibili>".$total_guests."</lettidisponibili>\n";// persone potenzialmente ospitabili
  $xml_output .= "\t\t</struttura>\n";
  $xml_output .= "\t</movimento>\n";
}
  $xml_output .="</movimenti>\n";

  //echo "<br>📄 Corpo xml:\n" . htmlspecialchars($xml_output) . "<br>";

  $path .="/".$id_artico_group;

  // Controlla se la cartelle per scrivere i files esistono
  if (!is_dir($path)) {
      // La cartella non esiste, quindi la creiamo
      if (mkdir($path, 0777, true)) {
      } else {
          echo "Errore nella creazione della cartella.",$path;
      }
  }
  $now = new DateTime();
  $timestamp = $now->format('Y-m-d_H-i-s');
  $xmlFileP = $path."/".$timestamp.".xml";
  $xmlHandle = fopen($xmlFileP, "w");

  // creo e scrivo il file polstat
  $contenuto = implode("\r\n", $file_polstat);// Unisci con "\r\n" senza aggiungerlo alla fine

	if (@fwrite($xmlHandle, $xml_output) === false || @file_put_contents($path."/polstat.txt", $contenuto) === false){
    ?>
    <div class="panel panel-default gaz-table-form div-bordered" style="max-width:80%;">
        <div class="container-fluid">
          <div align="center" class="FacetFormHeaderFont">
            <p class="text-danger">ERRORE nella generazione dei files</p>
          </div>
        </div>
    </div>
    <?php
  }else{// nessun errore nella creazione e salvataggio dei file
    // creo un file temporaneo per passare tutte le testate interessate
    $filepath = 'files/temp_ids.json';
    file_put_contents($filepath, json_encode($testate));
    ?>
    <div class="panel panel-default gaz-table-form div-bordered" style="max-width:80%;">
        <div class="container-fluid">
          <div align="center" class="FacetFormHeaderFont">
            <p class="text-success">I file statistico e alloggiati Polizia sono stati generati correttamente</p>
            <!-- Pulsante di download -->
            <button id="downloadBtn" type="button">Scarica File XML e TXT</button>

            <!-- Pulsanti per aprire l'iframe -->
            <button class="openIframeBtn" data-url="API_istat.php?ref=<?php echo $xmlFileP ; ?>&id=<?php echo $id_artico_group; ?>" type="button">Invio a servizio ISTAT</button>
            <button class="openIframeBtn" data-url="API_Polizia.php?ref=<?php echo $path; ?>&id=<?php echo $id_artico_group; ?>&type=<?php echo $fileUnico; ?>&checkin=<?php echo date("Ymd",strtotime($datainizio)); ?>" type="button">Invio a Alloggiati Polizia</button>
             <script>
              // Percorsi dei file da scaricare
              const xmlFilePath = '<?php echo $xmlFileP; ?>';
              const txtFilePath = '<?php echo $path,"/polstat.txt"; ?>';

              document.getElementById('downloadBtn').addEventListener('click', () => {
                // Crea un link per il file XML
                const xmlLink = document.createElement('a');
                xmlLink.href = xmlFilePath;
                xmlLink.download = xmlFilePath.split('/').pop(); // Estrae il nome del file

                // Crea un link per il file TXT
                const txtLink = document.createElement('a');
                txtLink.href = txtFilePath;
                txtLink.download = txtFilePath.split('/').pop(); // Estrae il nome del file

                // Aggiungi i link al DOM e simula il click
                document.body.appendChild(xmlLink);
                xmlLink.click();
                document.body.removeChild(xmlLink); // Rimuovi il link dopo il clic

                // Aggiungi il link per il file TXT al DOM e simula il click
                document.body.appendChild(txtLink);
                txtLink.click();
                document.body.removeChild(txtLink); // Rimuovi il link dopo il clic
              });

            </script>

          </div>

        </div>
    </div>
    <?php
  }

  fclose($xmlHandle);
}
?>
<!-- Contenitore iframe -->
            <div id="iframeContainer" style="display: none; position: fixed; top: 10%; left: 5%; width: 90%; height: 80%; background: #fff; z-index: 2000; border: 2px solid #28a745; box-shadow: 0 0 10px rgba(0,0,0,0.3);">
              <div style="text-align: right; padding: 10px;">
                <button id="closeIframeBtn" type="button" style="font-size: 18px;">&times;</button>
              </div>
              <iframe id="myIframe" src="" style="width: 100%; height: calc(100% - 40px); border: none;"></iframe>
            </div>

            <script>

              // Gestione pulsanti con data-url dinamico
            document.querySelectorAll('.openIframeBtn').forEach(button => {
              button.addEventListener('click', function (event) {
                event.preventDefault();
                const url = this.getAttribute('data-url');
                document.getElementById("myIframe").src = url;
                document.getElementById("iframeContainer").style.display = "block";
              });
            });

            // Pulsante per chiudere l'iframe
            document.getElementById('closeIframeBtn').addEventListener('click', function(event) {
              event.preventDefault();
              document.getElementById("iframeContainer").style.display = "none";
              document.getElementById("myIframe").src = "";
            });

            </script>
</form>
<?php
require("../../library/include/footer.php");
?>
