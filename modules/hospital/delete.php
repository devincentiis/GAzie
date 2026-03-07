<?php
/*
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

// prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}

if ( isset($_POST['type']) && isset($_POST['ref']) ) {
	require("../../library/include/datlib.inc.php");
	$admin_aziend = checkAdmin();
	switch ($_POST['type']) {
    case "patient_img":
      $i=intval($_POST['ref']);
      $user = gaz_dbi_get_row($gTables['files'], 'status', $i)['adminid'];
      if ( $_SESSION['Abilit'] >= 8 || $_SESSION['user_name'] == $user ) { // solo chi lo ha inserito o l'amministratore può togliere il documento
        gaz_dbi_del_row($gTables['files'], 'status', $i);
      }
    break;
    case "patient_doc":
      $i=intval($_POST['ref']);
      $doc=gaz_dbi_get_row($gTables['files'], 'status', $i);
      if ( $_SESSION['Abilit'] >= 8 || $_SESSION['user_name'] == $doc['adminid'] ) { // solo chi lo ha inserito o l'amministratore può togliere il documento
        //  per tenere traccia di chi lo ha eliminato cancello il file ma e modifico il rigo sul database
        gaz_dbi_query("UPDATE ".$gTables['files']." SET table_name_ref='patient_doc_deleted',adminid='".$_SESSION['user_name']."'  WHERE status=".$i);
        @unlink(DATA_DIR."files/".$admin_aziend['company_id']."/hospital/".$doc['id_doc'].".".$doc['extension']);
      }

    break;
    case "bed":
      $i=intval($_POST['ref']);
      // se non è un file dell'utente e l'utente non è amministratore non consento la visualizzazione
      if ( $_SESSION['Abilit'] >= 7 ) { // solo chi lo ha inserito o l'amministratore può togliere il documento
        gaz_dbi_del_row($gTables['bed'], 'id_bed', $i);
      }
    break;
    case "room":
      $i=intval($_POST['ref']);
      // se non è un file dell'utente e l'utente non è amministratore non consento la visualizzazione
      if ( $_SESSION['Abilit'] >= 7 ) { // solo chi lo ha inserito o l'amministratore può togliere il documento
        gaz_dbi_del_row($gTables['room'], 'id_room', $i);
      }
    break;
    case "ward":
      $i=intval($_POST['ref']);
      // se non è un file dell'utente e l'utente non è amministratore non consento la visualizzazione
      if ( $_SESSION['Abilit'] >= 7 ) { // solo chi lo ha inserito o l'amministratore può togliere il documento
        gaz_dbi_del_row($gTables['ward'], 'id_ward', $i);
      }
    break;
		case "healthworker":
			$i=substr($_POST['ref'],0,15);
      gaz_dbi_del_row($gTables['admin'], "user_name",$i);
      gaz_dbi_del_row($gTables['admin_module'], "adminid",$i);
      gaz_dbi_del_row($gTables['admin_config'], "adminid",$i);
      gaz_dbi_del_row($gTables['breadcrumb'], "adminid",$i."' AND exec_mode> '0");
    break;
		case "admidimi": // ammissioni-dimissioni
      $i=intval($_POST['ref']);
      gaz_dbi_del_row($gTables['tesbro'], 'id_tes', $i);
    break;
	}
}
?>
