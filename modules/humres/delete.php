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
if (isset($_POST['type'])&&isset($_POST['ref'])) { 
	require("../../library/include/datlib.inc.php");
	$admin_aziend = checkAdmin();
	switch ($_POST['type']) {
        case "staff":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['clfoco'], "codice", $i);
			gaz_dbi_del_row($gTables['staff'], "id_clfoco", $i);
		break;
        case "paysalary":
			$i=intval($_POST['ref']);
			// riprendo gli id_tes precedenti per cancellarli
			$files=gaz_dbi_get_row($gTables['files'], 'id_doc', $i);
			$custom_field=json_decode($files['custom_field']);
			foreach($custom_field->salary->id_tes as $id_tes){
				gaz_dbi_del_row($gTables['tesmov'],'id_tes',$id_tes);
				gaz_dbi_del_row($gTables['rigmoc'],'id_tes',$id_tes);
			}
			gaz_dbi_del_row($gTables['files'], 'id_doc', $i);
			unlink(DATA_DIR."files/".$_SESSION['company_id']."/doc/". $i . ".xml");
		break;
	}
}
?>