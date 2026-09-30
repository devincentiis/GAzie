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

if (!strstr($_SERVER["REQUEST_URI"], "login_admin") == "login_admin.php") {
	$_SESSION['lastpage'] = $_SERVER["REQUEST_URI"];
}
if (!empty($_SESSION['theme']) && file_exists("../.." . $_SESSION['theme'] . "/header.php")) {
	include "../.." . $_SESSION['theme'] . "/header.php";
} else { // se non trovo il mio tema utilizzo il primo che incontro nella dir
	$theme=false;
	if ($handle = opendir("../../library/theme")) {
    while (false !== ($entry = readdir($handle))) {
			if ($entry === "." || $entry === "..") continue;
			if (is_dir("../../library/theme/".$entry)){
				//print $entry.'<br>';
				$theme=true;
				$_SESSION['theme']='/library/theme/'.$entry;
				include "../../library/theme/".$entry."/header.php";
				break;				
			}
		}

    closedir($handle);
	} 
	if(!$theme){
		echo "<p><br /><br />ERRORE: Non ho trovato un tema in 'library/theme' ! </p>";
	}
}
?>