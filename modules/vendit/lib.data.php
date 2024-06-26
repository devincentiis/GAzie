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

function effettInsert ($newValue)
{
    $table = 'effett';
    $columns = array('tipeff','datemi','progre','numfat','seziva','protoc','datfat',
                     'totfat','salacc','impeff','scaden','clfoco','pagame','banapp',
                     'iban','banacc','id_doc','id_con','mndtritdinf','cigcup','status','adminid');
    $newValue['adminid'] = $_SESSION["user_name"];
    return tableInsert($table, $columns, $newValue);
}

function agentiInsert ($codice, $newValue)
{
    $table = 'agenti';
    $columns = array('id_agente','id_agente_coord','id_fornitore','base_percent','coord_percent','tipo_contratto','adminid');
    //$newValue['adminid'] = $_SESSION["user_name"];
    return tableInsert($table, $columns, $newValue);
}

function agentiUpdate ($codice, $newValue)
{
    $table = 'agenti';
    $columns = array('id_agente','id_agente_coord','id_fornitore','base_percent','coord_percent','tipo_contratto','adminid');
    //$newValue['adminid'] = $_SESSION["user_name"];
    tableUpdate($table, $columns, $codice, $newValue);
}

function bodytextInsert ($newValue)
{
    $table = 'body_text';
    $columns = array('table_name_ref','id_ref','body_text','lang_id');
    return tableInsert($table, $columns, $newValue);
}

function bodytextUpdate ($codice, $newValue)
{
    $table = 'body_text';
    $columns = array('table_name_ref','id_ref','body_text','lang_id');
    tableUpdate($table, $columns, $codice, $newValue);
}

function contractUpdate ($newValue,$codice=false,$tesdoc='')
{
  // per fare l'upload in $codice dev'essere passato un: array(0=>'id_contract',1=>valore di id_contract da aggiornare)
  // altrimenti si fa l'insert
  $table = 'contract';
  $columns = array( 'id_customer', 'vat_section', 'doc_number', 'doc_type', 'conclusion_date',
                    'start_date', 'months_duration', 'initial_fee','periodic_reassessment',
                    'bank', 'periodicity', 'payment_method', 'tacit_renewal', 'current_fee',
                    'id_con', 'cod_revenue', 'vat_code', 'id_body_text', 'last_reassessment',
                    'id_agente', 'provvigione', 'status', 'note', 'adminid');
  $newValue['adminid'] = $_SESSION["user_name"];
  if (is_array($codice)) {
    tableUpdate($table, $columns, $codice, $newValue);
    // se ho l'indice data_ordine che è usato per indicare l'ultimo mese  fatturato questo è sull'ultimo tesdoc inserito quindi lo devo cambiare
    if (strlen($newValue['data_ordine']) >= 9){
      $sqlquery="UPDATE " . $tesdoc . " SET data_ordine = '".$newValue['data_ordine']."' WHERE id_contract = ".$codice[1]." ORDER BY data_ordine DESC LIMIT 1";
      gaz_dbi_query($sqlquery);
    }
  } else {
    return tableInsert($table, $columns, $newValue);
  }
}

function contractRowUpdate ($newValue,$codice=false)
{
    // per fare l'upload in $codice dev'essere passato un: array(0=>'id_row',1=>valore di id_row da aggiornare)
    // altrimenti si fa l'insert
    $table = 'contract_row';
    $columns = array( 'id_contract','descri','unimis','quanti',
                      'price','discount','vat_code','cod_revenue','status');
    $newValue['adminid'] = $_SESSION["user_name"];
    if (is_array($codice)) {
       tableUpdate($table, $columns, $codice, $newValue);
    } else {
       return tableInsert($table, $columns, $newValue);
    }
}

function provvigioniInsert ($newValue)
{
    $table = 'provvigioni';
    $columns = array('id_agente','id_provvigione','cod_articolo','cod_catmer','percentuale');
    $newValue['adminid'] = $_SESSION["user_name"];
    return tableInsert($table, $columns, $newValue);
}

function provvigioniUpdate ($codice, $newValue)
{
    $table = 'provvigioni';
    $columns = array('id_agente','id_provvigione','cod_articolo','cod_catmer','percentuale');
    $newValue['adminid'] = $_SESSION["user_name"];
    tableUpdate($table, $columns, $codice, $newValue);
}


function fae_fluxInsert($newValue)
{
    $table = 'fae_flux';
    $columns = array('filename_ori','filename_zip_package','id_tes_ref','exec_date','received_date','delivery_date','filename_son','id_SDI','filename_ret','mail_id','data','flux_status','n_invio','progr_ret','flux_descri');
    return tableInsert($table, $columns, $newValue);
}


?>
