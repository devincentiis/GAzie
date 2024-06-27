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
  require("../../library/include/datlib.inc.php");
  if ( is_numeric($_GET['title']) ) {
    if ($_GET['end']==null){
      $_GET['end']=date('Y-m-d', strtotime($_GET['start']));;
    }
    $start=substr($_GET['start'],0,10);
    $end=substr($_GET['end'],0,10);
    $err='';
    while (strtotime($start) <= strtotime($end)) {// ciclo il periodo giorno per giorno per vedere se c'è già un prezzo
      $what = "title";
      $table = $gTables['rental_prices'];
      $where = "house_code = '".substr($_GET['house_code'],0,32)."' AND start < '". $start ."' AND end >= '". $start."'";
      $result = gaz_dbi_dyn_query($what, $table, $where);
      $available = gaz_dbi_fetch_array($result);
      if (isset($available)){
        $err="prezzo già inserito";
        break;
      }
      $start = date ("Y-m-d", strtotime("+1 days", strtotime($start)));// aumento di un giorno il ciclo
    }
    if ($err==''){// se posso inserisco il prezzo

      $columns = array('id','title', 'start','end','house_code','price','minstay');
      $_GET['start']=date('Y-m-d', strtotime($_GET['start']. ' + 1 hour'));
      $_GET['end']=date('Y-m-d', strtotime($_GET['end']. ' - 1 hour'));
      $newValue = array('title'=>substr(floatval($_GET['title']),0,128), 'start'=>substr($_GET['start'],0,10),'end'=>substr($_GET['end'],0,10),'house_code'=>substr($_GET['house_code'],0,32),'price'=>substr($_GET['title'],0,14),'minstay'=>intval($_GET['minstay']));
      tableInsert('rental_prices', $columns, $newValue);
    }
  }
}
?>
