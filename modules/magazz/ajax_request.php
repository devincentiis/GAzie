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
$admin_aziend=checkAdmin();
$libFunc = new magazzForm();
if (isset($_GET['term'])) {
    if (isset($_GET['opt'])) {
        $opt = $_GET['opt'];
    } else {
        $opt = 'orders';
    }
    switch ($opt) {
      case 'orders':
        $codice= substr($_GET['term'],0,15);
        $orders= $libFunc->getorders($codice);
        echo json_encode($orders);
      break;
      case 'lastbuys':
        $codice= substr($_GET['term'],0,15);
        $lastbuys= $libFunc->getLastBuys($codice,false);
        echo json_encode($lastbuys);
      break;
	  case 'group':
        $codice= intval($_GET['term']);
		$query = "SELECT descri, id_artico_group FROM " . $gTables['artico_group'] . " WHERE id_artico_group ='". $codice ."' LIMIT 1";
		$result = gaz_dbi_query($query);
		$n=0;
		while ($res = $result->fetch_assoc()){       
			$return[$n]=$res;
			$n++;
		}
		$query = "SELECT codice, descri FROM " . $gTables['artico'] . " WHERE id_artico_group ='". $codice ."'";
		$result = gaz_dbi_query($query);
		while ($res = $result->fetch_assoc()){       
			$return[$n]=$res;
			$n++;
		}	
        echo json_encode($return);
      break;
      default:
      return false;
    }
}

?>