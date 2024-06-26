<?php

/*
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
function letterInsert ($newValue)
{
    $table = 'letter';
    $columns = array( 'write_date','numero','revision','clfoco','tipo','c_a','oggetto','corpo','signature','note','status','adminid');
    $newValue['adminid'] = $_SESSION["user_name"];
    tableInsert($table, $columns, $newValue);
}

function letterUpdate ($codice, $newValue)
{
    $table = 'letter';
    $columns = array( 'write_date','numero','revision','clfoco','tipo','c_a','oggetto','corpo','signature','note','status','adminid');
    $newValue['adminid'] = $_SESSION["user_name"];
    tableUpdate($table, $columns, $codice, $newValue);
}

function company_dataUpdate ($codice, $newValue)
{
    $table = 'company_data';
    $columns = array('description','var','data','ref');
    tableUpdate($table, $columns, $codice, $newValue);
}

function company_dataInsert ($newValue)
{
    $table = 'company_data';
    $columns = array('description','var','data','ref');
    tableInsert($table, $columns, $newValue);
}

?>