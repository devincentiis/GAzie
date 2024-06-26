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
  if (strtotime($_GET['end']) > strtotime($_GET['start'])){
    require("../../library/include/datlib.inc.php");
     $columns = array('id','title', 'start','end','house_code');
     if ($_GET['end']==null){
       $_GET['end']=$_GET['start'];
     }
     $newValue = array('title'=>substr($_GET['title'],0,128), 'start'=>substr($_GET['start'],0,10),'end'=>substr($_GET['end'],0,10),'house_code'=>substr($_GET['house_code'],0,32));
     tableInsert('rental_events', $columns, $newValue);
  }else{
    echo "errore";
  }
}
?>
