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
if(!$isAjax) {
  $user_error = 'Access denied - not an AJAX request...';
  trigger_error($user_error, E_USER_ERROR);
}

if (isset($_GET['term'])) { //	Evitiamo errori se lo script viene chiamato direttamente
    require("../../library/include/datlib.inc.php");
    $admin_aziend = checkAdmin();
	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {
		$term=filter_var(substr($_GET['term'],0,20),FILTER_SANITIZE_ADD_SLASHES);
	} else {
		$term=addslashes(substr($_GET['term'],0,20));
	}
    $a_json_invalid = array(array("id" => "#", "value" => $term, "label" => "Sono consentiti solo lettere e numeri..."));
    $json_invalid = json_encode($a_json_invalid);
    // replace multiple spaces with one
    $term = preg_replace('/\s+/', ' ', $term);
    // SECURITY HOLE ***************************************************************
    // allow space, any unicode letter and digit, underscore, dash, slash, percent, dot,
    if (preg_match("/[^\040\pL\pN\%\/\._-]/u", $term)) {
        print $json_invalid;
        exit;
    }

    if (strlen($term) < 2) { //	Equivalente del precedente strlen($term)>1
        return;
    }
    $acc = array();
    if (($handle = fopen('opendata_CCNL.csv', "r")) !== FALSE) {
        $r = 0;
        while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
            $num = count($data);
            for ($c = 0; $c < $num; $c++) {
                if ($c == 0 && strpos($data[$c], $term) !== false) {
                    $acc[$r]['id'] = $r;
                    $acc[$r]['value'] = $data[8];
                    $acc[$r]['label'] = $data[0];
                    $r++;
                }
            }
        }
        fclose($handle);
    }
    echo json_encode($acc);
} else {
    return;
}
?>