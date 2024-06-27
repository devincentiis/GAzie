
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
include_once("manual_settings.php");
if ($_GET['token'] == md5($token.date('Y-m-d'))){
  //require("../../library/include/datlib.inc.php");
  include ("../../config/config/gconfig.myconf.php");

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





  $data = [];
  $dataTot = [];
  $count=0;
  $what = "codice";
  $where = "good_or_service=1 AND (custom_field REGEXP 'accommodation_type') AND id_artico_group = ". substr(mysqli_escape_string($link,$_GET['id']), 0, 9) ."";
  $sql = "SELECT ".$what." FROM ".$azTables."artico WHERE ".$where;
  $resulth = mysqli_query($link, $sql); // prendo tutti gli alloggi connessi alla struttura

  //$sql = "SELECT ".$azTables."rental_events.* FROM ".$azTables."rental_events LEFT JOIN ".$azTables."artico ON ".$azTables."artico.id_artico_group = ". substr(mysqli_escape_string($link,$_GET['id']), 0, 9) ." WHERE ".$azTables."rental_events.house_code = ".$azTables."artico.codice AND (start >= '".date('Y-m-d')."' OR end >= '".date('Y-m-d')."') ORDER BY id ASC";
 // $result = mysqli_query($link, $sql);


  foreach ($resulth as $resh){ // per ogni alloggio
    // prendo tutti gli eventi a partire da oggi dell'alloggio
    $sql = "SELECT * FROM ".$azTables."rental_events LEFT JOIN ".$azTables."tesbro ON  ".$azTables."rental_events.id_tesbro = ".$azTables."tesbro.id_tes WHERE (custom_field IS NULL OR custom_field LIKE '%PENDING%' OR custom_field LIKE '%CONFIRMED%' OR custom_field LIKE '%FROZEN%') AND house_code='".substr($resh['codice'], 0, 32)."' AND (start >= '".date('Y-m-d')."' OR end >= '".date('Y-m-d')."') ORDER BY id ASC";
    $result = mysqli_query($link, $sql);

    foreach($result as $row){ // per ogni evento dell'alloggio
  //echo "<pre>evento alloggio:",print_r($row);
      $start=$row['start'];
      $end=$row['end'];
      // ciclo i giorni dell'evento
      while (strtotime($start) < strtotime($end)) {// per ogni giorno dell'evento

        /*
        if (!isset($data[$row['house_code']])){
          $data[$row['house_code']][] = array('start' => $start);
        }elseif (!array_search($start, array_column($data[$row['house_code']], 'start'))){// escludo i giorni già occupati
          $data[$row['house_code']][] = array('start' => $start);
        }
        */
        if (!isset($data[$start])){
          $data[$start]=array();
        }
          if (!in_array($row['house_code'],$data[$start])){// escludo i giorni che hanno già quell'alloggio
           array_push($data[$start],$row['house_code']);
        }


        $start = date ("Y-m-d", strtotime("+1 days", strtotime($start)));// aumento di un giorno il ciclo

      }

    }
  }
  //echo "<pre>",print_r($data);
  // adesso creo l'array per il calendar
  foreach($data as $key => $value){// in key ho i giorni occupati mentre in value ho gli alloggi occupati in tale giorno
    //echo "<br>giorno:",$key," value:",print_r($value);
    if (intval(count($value)) == intval($resulth->num_rows)){// se il contatore alloggi è uguale al numero totale alloggi nel giorno
      // il giorno è totalmente occupato
      $dataTot[] = array(
      'id' => "",
      'title' => 'completo',
      'backgroundColor' => 'red',
      'start' => $key,
      'end' => $key,
      'display' => 'auto'
      );
    }else{

        foreach ($resulth as $resh){ // ciclo tutti gli alloggi esistenti
          if (!in_array($resh['codice'],$value)){// se non sono presenti nell'elenco occupati
            //segno il giorno libero
            $dataTot[] = array(
              'id'   => "",
              'title'   => $resh['codice'],
              'start'   => $key,
              'end'   => $key
              );
          }else{// altrimenti lo segno occupato per lo specifico alloggio
            $dataTot[] = array(
            'id'   => "",
            'title'   => $resh['codice'],
            'backgroundColor' => 'red',
            'start'   => $key,
            'end'   => $key
            );

          }
        }
    }
  }
  //print_r($dataTot);die;
echo json_encode($dataTot);
}

/*

  $data = [];
  $dataTot = [];

  // prendo gli eventi a partire da oggi connessi alla struttura
  $sql = "SELECT ".$azTables."rental_events.* FROM ".$azTables."rental_events LEFT JOIN ".$azTables."artico ON ".$azTables."artico.id_artico_group = ". substr(mysqli_escape_string($link,$_GET['id']), 0, 9) ." WHERE ".$azTables."rental_events.house_code = ".$azTables."artico.codice AND (start >= '".date('Y-m-d')."' OR end >= '".date('Y-m-d')."') ORDER BY id ASC";
  $result = mysqli_query($link, $sql);

	$n=-1;
  foreach($result as $res){// per ogni evento
    $start=$res['start'];
    $end=$res['end'];

    while (strtotime($start) < strtotime($end)) {// ciclo i giorni dell'evento

		if (!in_array($start,$data)){//se il giorno non è mai stato analizzato lo creo
			$data[]=$start;$n++;
			$house[$n][]= $res['house_code'];// con la stessa chiave $n, memorizzo il codice alloggio
			$dataTot[$n] = array(// il giorno è parzialmente occupato
			  'id'   => $res['id'],
			  'title'   => 'Occupato: '.$res['house_code'],
        'backgroundColor' => 'red',
			  'start'   => $start,
			  'end'   => $start,
			  'display' => 'auto'
			  );
		}else{
			$id = array_search($start, array_column($dataTot, 'start'));// trovo in quale array ho già il giorno analizzato
			if (!in_array($res['house_code'],$house[$id])){
				$house[$id][]= $res['house_code'];// nella stessa chiave $n, aggiungo il codice alloggio
				$dataTot[$id]['title']=$dataTot[$id]['title']." + ".$res['house_code'];
			}
		}
      $start = date ("Y-m-d", strtotime("+1 days", strtotime($start)));// aumento di un giorno il ciclo
    }
  }
   // echo "<br>DATA:",print_r($data);
  echo json_encode($dataTot);
}
*/
?>
