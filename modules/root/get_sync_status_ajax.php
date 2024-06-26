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
require_once("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
$datares=false;
$syncmod=explode(',',$admin_aziend['gazSynchro']);
foreach($syncmod as $k=>$v){
  // richiamo i file contenenti le classi di sync tranne quello del primo modulo che già ho richiamato con library/include/function.inc.php
  if ( $k > 0 ){
    require_once("../".$v."/sync.function.php");
  }
  $classname=preg_replace("/[^a-zA-Z]/", "", $v)."gazSynchro";
  if (class_exists($classname)) {
	// controllo se ci sono sincronizzazioni da effettuare
	$gSync = new $classname();
	if($gSync->api_token){
	  $datares=[];
      $gSync->get_sync_status(0);
	}
	$datares[$v]=$gSync->rawres;
    $_SESSION['menu_alerts'][$v]=$datares[$v];
  }
  
}
echo json_encode($datares);
?>