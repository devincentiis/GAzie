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
include_once("manual_settings.php");
if ($_GET['token'] == md5($token.date('Y-m-d'))){
  require("../../library/include/datlib.inc.php");
  $err='';
  $extra = gaz_dbi_get_row($gTables['rental_extra'], "codart", substr($_GET['house_code'],0,32));
  if ($_GET['end']==null){
   $_GET['end']=$_GET['start'];
  }
  $start=substr($_GET['start'],0,10);
  //$end=substr($_GET['end'],0,10);
  $end=date('Y-m-d', strtotime($_GET['end']. ' - 25 hour')); // devo togliere un giorno perché le prenotazioni sono a cavallo quindi l'ulrimo giorno va tolto

  if (strtotime($start)>strtotime($end)){ // se sono andato oltre, voleva dire che la richiesta è di un solo giorno
    $end=$start;
  }
  //echo "<br>start:",$start," - end:",$end;
  while (strtotime($start) <= strtotime($end)) {// ciclo il periodo giorno per giorno per vedere se è stato superata la disponibilità massima dell'extra
    $what = "title";
    $table = $gTables['rental_events'];
    $where = "house_code = '".substr($_GET['house_code'],0,32)."' AND start <= '". $start ."' AND end >= '". date('Y-m-d', strtotime($_GET['start']. ' + 25 hour'))."'";
   // echo "<br>",$where;
    $result = gaz_dbi_dyn_query($what, $table, $where);
    if ( $result->num_rows >= intval($extra['max_quantity'])){
      $err="Raggiunta massima disponibilità";
      break;
    }
    $start = date ("Y-m-d", strtotime("+1 days", strtotime($start)));// aumento di un giorno il ciclo
  }
 //echo "<br>",$result->num_rows,"pippo",$err;die;
  if ($err==''){// se posso inserisco
    $columns = array('id','title', 'start','end','house_code');
    $_GET['start']=date('Y-m-d', strtotime($_GET['start']));
    $_GET['end']=date('Y-m-d', strtotime($_GET['end']));
    $newValue = array('title'=>substr($_GET['title'],0,128), 'start'=>substr($_GET['start'],0,10),'end'=>substr($_GET['end'],0,10),'house_code'=>substr($_GET['house_code'],0,32));
    tableInsert('rental_events', $columns, $newValue);
  }
}
?>
