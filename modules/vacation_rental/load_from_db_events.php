<?php
/*
    --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (http://www.programmisitiweb.lacasettabio.it)

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
include_once("manual_settings.php");
if ($_GET['token'] == md5($token.date('Y-m-d'))){
  //require("../../library/include/datlib.inc.php");
  include ("../../config/config/gconfig.myconf.php");

  include_once("manual_settings.php");
  $azTables = constant("table_prefix").$idDB;
  $IDaz=preg_replace("/[^1-9]/", "", $azTables );

  $servername = constant("Host");
  $username = constant("User");
  $pass = constant("Password");
  $dbname = constant("Database");
  $genTables = constant("table_prefix")."_";

  // Create connection
  $link = mysqli_connect($servername, $username, $pass, $dbname);
  // Check connection
  if (!$link) {
      die("Connection DB failed: " . mysqli_connect_error());
  }
  $link -> set_charset("utf8");
  
	if (isset($_GET['id'])){ // se ho ricevuto il codice alloggio controllo se è aperto o chiuso
		//$sql = "SELECT ordinabile FROM ".$azTables."artico WHERE codice = '".substr(mysqli_escape_string($link,$_GET['id']), 0, 32)."' LIMIT 1";	 
		$house_code = substr(mysqli_real_escape_string($link, $_GET['id']), 0, 32);
		$sql = "
		SELECT a.ordinabile, g.custom_field
		FROM ".$azTables."artico AS a
		LEFT JOIN ".$azTables."artico_group AS g
		  ON a.id_artico_group = g.id_artico_group
		WHERE a.codice = '".$house_code."'
		LIMIT 1
		";
		
		$result = mysqli_query($link, $sql);
		$row_artico = mysqli_fetch_assoc($result); // restituisce array 
		
		 if ($row_artico === null) {
		  $ordinabile = '';
		} else {
			$ordinabile = $row_artico['ordinabile'] ?? '';
			if ($ordinabile == "N"){// se l'alloggio è chiuso blocca
				echo "L'alloggio non è al momento prenotabile. Contattare il gestore per maggiori informazioni";
				return;
			}	
		}
	}
  
  $data = array();

  if(isset($_GET['id'])){
    $sql = "SELECT * FROM ".$azTables."rental_events LEFT JOIN ".$azTables."tesbro ON  ".$azTables."rental_events.id_tesbro = ".$azTables."tesbro.id_tes WHERE (custom_field IS NULL OR custom_field LIKE '%PENDING%' OR custom_field LIKE '%CONFIRMED%' OR custom_field LIKE '%FROZEN%') AND house_code='".substr(mysqli_escape_string($link,$_GET['id']), 0, 32)."' AND (start >= '".date('Y-m-d')."' OR end >= '".date('Y-m-d')."') ORDER BY id ASC";
    $result = mysqli_query($link, $sql);
    if (isset($result)){
    foreach($result as $row){
		if ($row["type"]=="PAUSA"){
			$row["title"]="Pausa";
		}
		$data[] = array(
		'id'   => $row["id"],
		'title'   => addslashes($row["title"]),
		'start'   => $row["start"],
		'end'   => $row["end"]
		);
		}
    }
	
	
	if(isset($row_artico['custom_field']) && $dataJson = json_decode($row_artico['custom_field'],true)){
		if (intval($dataJson['vacation_rental']['open_from'])>0 && intval($dataJson['vacation_rental']['open_to'])){ // se è impostato un periodo di apertura
			$year = date('Y');
			list($day, $month) = explode('-', $dataJson['vacation_rental']['open_from']);			
			$iso_date_from = sprintf('%04d-%02d-%02d', $year, $month, $day); 
			$iso_date_from_nextyear = sprintf('%04d-%02d-%02d', $year+1, $month, $day);
			$iso_date_from_nextyear2 = sprintf('%04d-%02d-%02d', $year+2, $month, $day);
			list($day, $month) = explode('-', $dataJson['vacation_rental']['open_to']);			
			$iso_date_to = sprintf('%04d-%02d-%02d', $year, $month, $day);
			$iso_date_to_nextyear = sprintf('%04d-%02d-%02d', $year+1, $month, $day);
			 
			$addrow = [
			  'id' => 0,
			  'title' => 'Struttura chiusa',
			  'start' => sprintf('%04d-%02d-%02d', (int)date('Y'), (int)date('m'), (int)date('d')),// da oggi
			  'end' => $iso_date_from,// alla data di apertura
			  'house_code' => $house_code,
			  'id_tesbro' => 0
			];	
			$data[]=$addrow;
			
			$addrow = [
			  'id' => 0,
			  'title' => 'Struttura chiusa',
			  'start' =>  $iso_date_to,// dalla data di chiusura
			  'end' => $iso_date_from_nextyear, // alla data di apertura dell'anno successivo
			  'house_code' => $house_code,
			  'id_tesbro' => 0
			];	
			$data[]=$addrow;
			 
			$addrow = [
			  'id' => 0,
			  'title' => 'Struttura chiusa',
			  'start' => $iso_date_to_nextyear, // dalla data chiusura dell'anno successivo
			  'end' => $iso_date_from_nextyear2, // alla data di apertura dell'anno successivo +2
			  'house_code' => $house_code,
			  'id_tesbro' => 0
			];	
			$data[]=$addrow;
			
		}			
	}
	
	
	
	
    echo json_encode($data);
  }
}
?>
