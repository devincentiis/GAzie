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
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}

if (isset($_GET['type'])&&isset($_GET['ref'])) {
	require("../../library/include/datlib.inc.php");
	$admin_aziend = checkAdmin();
	$i=substr($_GET['ref'],0,32);
	switch ($_GET['type']) {
    case "setposition":
      $position = gaz_dbi_get_row($gTables['artico_position'], 'id_position', intval($_GET['val'])); // prendo i valori magazzino e scaffale dal principale (senza codart)
      $poscodart = gaz_dbi_get_row($gTables['artico_position'], 'artico_id_position', intval($_GET['val']), " AND codart='".$i."'");
      // aggiungo solo se non è già presente nella stessa posizione
      if (!$poscodart){
        gaz_dbi_query("INSERT INTO ".$gTables['artico_position']." (id_warehouse, id_shelf, artico_id_position, codart) VALUES (".$position['id_warehouse'].", ".$position['id_shelf'].", ".intval($_GET['val']).", '".$i."')");
      }
		break;
	}
}
?>
