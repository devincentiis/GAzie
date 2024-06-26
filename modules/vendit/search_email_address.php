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
$clfoco_ref=intval($_GET['clfoco']);
$ret=array();

// aggiungo le email che trovo sulle testate
$sqlquery= "SELECT email FROM ".$gTables['tesdoc']." WHERE email <> '' AND clfoco=".$clfoco_ref." UNION SELECT email FROM ".$gTables['letter']." WHERE email <> '' AND clfoco=".$clfoco_ref." GROUP BY email";
$result = gaz_dbi_query($sqlquery);
while($row = gaz_dbi_fetch_array($result)) {
	array_push($ret,$row);
}
// riprendo anche la mail del fornitore dalla anagrafica
$from = $gTables['clfoco'] . ' AS cli LEFT JOIN ' . $gTables['anagra'] . ' AS ana ON cli.id_anagra=ana.id ';
$rs = gaz_dbi_dyn_query('e_mail', $from, 'cli.codice ='.$clfoco_ref);
while ($row = gaz_dbi_fetch_array($rs)) {
	if (!empty($row['e_mail']) && !in_array($row['e_mail'], $ret)) {
		$ret[]['email']=$row['e_mail'];
	}
}

echo json_encode($ret);
?>

