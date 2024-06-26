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
$data =[];
if (isset($_POST['term'])) { //	Evitiamo errori se lo script viene chiamato direttamente
  require("../../library/include/datlib.inc.php");
  $admin_aziend = checkAdmin();
	switch ($_POST['opt']) {
		case "module":
      // preparo l'array con gli script già esclusi
      $module = gaz_dbi_get_row($gTables['module'], "name",substr($_POST['term'],0,30));
      $admin_module = gaz_dbi_get_row($gTables['admin_module'], "moduleid",$module['id']," AND adminid='".substr($_POST['adminid'],0,30)."' AND company_id=" . $admin_aziend['company_id']);
      $custom_field=is_string($admin_module['custom_field'])?json_decode($admin_module['custom_field'],true):[];
      // fine prep array in $custom_field['excluded_script']
      $files = glob('../'.substr($_POST['term'],0,30).'/*.php');
      $patterns ='/\/docume|\/search|\/lang|\/lib|\/delete|\/ajax|\/rec_|\/get_|\/menu|\/sync/';
      foreach ($files as $rf) {
        $bnrf=basename($rf);
        if (preg_match($patterns,$rf)) continue;
        $data[]=(isset($custom_field['excluded_script']) && in_array(substr($bnrf,0,-4),$custom_field['excluded_script']))?["script_name"=>$bnrf,"chk_script"=>'checked']:["script_name"=>basename($rf)];
      }
  }
}
$json= json_encode(array($data));
echo substr($json, 1, -1); // tolgo la prima e l ultima parentesi quadra
?>

