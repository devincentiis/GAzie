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
 // prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
$deleted_rows = (isset($_POST['deleted_rows']))?$_POST['deleted_rows']:$deleted_rows=[];
$codart=substr($_POST['codart'],0,15);
if (isset($_POST['rec_artico_positions'])) {
	$artico_positions = $_POST['rec_artico_positions'];

// INIZIO CONTROLLO ERRORI E ACCUMULO DATI PER AGGIORNAMENTO artico_position
	$noerr=true; // non ho errori
	$n=1;
  $ctrl_acc=[];
	foreach ($artico_positions as $k=>$v){ // faccio un primo ciclo per controllare se ci sono errori, per eliminare i righi eliminati e per accumulare i nuovi valori
	  if (in_array($v['id_position'], $deleted_rows)) { // è un rigo da eliminare, non lo accumulo ma anzi lo elimino sia dal db che da questo array
  		gaz_dbi_del_row($gTables['artico_position'], "id_position",$v['id_position']);
      unset($artico_positions[$k]);
	  } else {
      if (isset($ctrl_acc[$v['id_warehouse']][$v['id_shelf']])){ // ho due righi riferiti allo stesso scaffale
        echo "ERRORE riga ",$n,": hai indicato più posizioni per lo stesso scaffale\n";
        $noerr=false;
      } else {
        $ctrl_acc[$v['id_warehouse']][$v['id_shelf']]=true;
      }
      if (strlen(trim($v['position']))<1){ // non ho indicato la posizione
        echo "ERRORE riga ",$n,": Non hai indicato la posizione\n";
        $noerr=false;
      }
	  }
    $n++;
	}
// FINE CONTROLLO ERRORI E ACCUMULO DATI PER AGGIORNAMENTO artico_position

	if ($noerr) { // non ho errori posso aggiornare
	  $n=0;
	  reset($artico_positions);
	  foreach ($artico_positions as $v){
      $v['codart']=$codart;
      $n++;
      if ($v['id_position']>0){ // è un update
        gaz_dbi_table_update("artico_position", array('id_position', $v['id_position']), $v);
      } else { // è un insert
        gaz_dbi_table_insert("artico_position", $v);
      }
      //print_r($v);
	  }
	}
} else {
}
foreach ($deleted_rows as $del_row) { // gli eventuali righi rimanenti
	gaz_dbi_del_row($gTables['artico_position'], "id_position", $del_row);
}

