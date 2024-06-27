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
  if(isset($_GET['id']) && intval($_GET['id'])>0){
  // a causa di un problema di fuso orario bisogna aggiungere un'ora alle date
  $_GET['start']=date('Y-m-d', strtotime($_GET['start']. ' + 1 hour'));
  $_GET['end']=date('Y-m-d', strtotime($_GET['end']. ' + 1 hour'));
  $columns = array('id','title', 'start','end','house_code');
  $newValue = array('title'=>substr($_GET['title'],0,128), 'start'=>$_GET['start'],'end'=>$_GET['end'],'house_code'=>substr($_GET['house_code'],0,32));
  $codice=array();
  $codice[0]="id";
  $codice[1]=intval($_GET['id']);
  tableUpdate('rental_events', $columns, $codice, $newValue);
  }
}
?>
