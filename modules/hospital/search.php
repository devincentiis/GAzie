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

if (isset($_GET['term'])) { //	evitiamo errori se lo script viene chiamato direttamente
  $opt = (isset($_GET['opt']))?substr($_GET['opt'],0,20):'first_name';
  require("../../library/include/datlib.inc.php");
  $admin_aziend = checkAdmin();
  require_once("./lib.data.php");
  $return_arr =[];
	$term=(defined('FILTER_SANITIZE_ADD_SLASHES'))?filter_var(substr($_GET['term'],0,30),FILTER_SANITIZE_ADD_SLASHES):addslashes(substr($_GET['term'],0,30));
  $term = gaz_dbi_real_escape_string($term);
  $a_json_invalid = [["id" => "#", "value" => $term, "label" => "Alcuni caratteri impedisco la ricerca..."]];
  $json_invalid = json_encode($a_json_invalid);
  $term = preg_replace('/\s+/', ' ', strtoupper($term));  // replace multiple spaces with one
  if (preg_match("/[^\040\pL\pN\&\%\@\#\/\.,_-]/u", $term)) {
    print $json_invalid;
    exit;
  }
	$tl=strlen($term);
	if($tl<3) {
    return;
  }
  switch($opt) {
    case 'first_name':
    case 'last_name':
    case 'tax_code':
    case 'health_card_number':
      $return_arr = DecryptPersonalData($gTables['encrypted_personal_data'],$opt.'_bidx',$term);
    break;
  }
  echo json_encode($return_arr);
} else {
  return;
}
?>

