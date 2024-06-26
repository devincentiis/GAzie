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
require("../../library/include/datlib.inc.php");

$admin_aziend=checkAdmin();
require("../../library/include/document.php");
if (isset($_GET['id_tes'])){   //se viene richiesta la stampa di un solo documento attraverso il suo id_tes
	$id_testata = intval($_GET['id_tes']);
	$testata = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', $id_testata);
	if (!empty($_GET['template'])){
	  $template = substr($_GET['template'],0,25);
	} elseif(!empty($testata['template']))  {
	  $template = $testata['template'];
	} else {
	  $template = 'FatturaAcquisto';
	}
	if (($testata['ddt_type']<>"T" && $testata['ddt_type']<>"L") || $template=="DDT"){
		if (isset($_GET['dest']) && $_GET['dest'] == 'E') { // se l'utente vuole inviare una mail
			createDocument($testata, $template, $gTables, 'rigdoc', 'E');
		} else {
			createDocument($testata, $template, $gTables);
		}
	} else {
		$lang = "";
		$testate= gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datreg) = '".substr($testata['datreg'],0,4)."' AND (ddt_type = 'T' OR ddt_type = 'L') AND protoc = '{$testata['protoc']}'","id_tes ASC");

		// createDocument($testata, $template, $gTables);
		createInvoiceACQFromDDT($testate, $gTables, false, $lang);
	}


}
?>
