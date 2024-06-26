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
if(isset($_POST["fn"])&& isset($_POST["filename"])){ // ho i dati di base
    require_once("../../library/include/datlib.inc.php");
    $admin_aziend = checkAdmin();
    // scrittura db
    if ($_POST["fn"]=='save'){ // upsert
        $ier=gaz_dbi_get_row($gTables['company_data'], 'var', filter_var($_POST['filename'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        if ($ier){
            gaz_dbi_put_row($gTables['company_data'], 'var', filter_var($_POST['filename'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),'data',filter_var($_POST['value'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        }else{
            gaz_dbi_table_insert('company_data', array('description'=>'Valori settaggio IERincludeExcludeRows', 'var'=>filter_var($_POST['filename'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),'data'=>filter_var($_POST['value'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)));		
        }
	// lettura db
    } else {
        $dt= gaz_dbi_get_row($gTables['company_data'], 'var', filter_var($_POST['filename'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
		if (isset($dt)){
			echo $dt['data'];
		}
    }
}
?>
