<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2023 - Antonio De Vincentiis Montesilvano (PE)
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
$datares=false;
if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname'])){
	// aggiorno l'e-commerce ove presente
    $gs=$admin_aziend['synccommerce_classname'];
	$gSync = new $gs();
	if($gSync->api_token){
    $rs_last_id = gaz_dbi_dyn_query("*", $gTables['tesbro'],"`tipdoc` = 'VOW'" ,'`ref_ecommerce_id_order` DESC',0,1);
		$last_id = gaz_dbi_fetch_array($rs_last_id);
		if ($last_id){
			$id=intval($last_id['ref_ecommerce_id_order']);
		} else{
			$id=0;
		}
		$gSync->get_sync_status($id);
	}
	$datares=$gSync->rawres;
	//exit;
}
echo $datares;
?>