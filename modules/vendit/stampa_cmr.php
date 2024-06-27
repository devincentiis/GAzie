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
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
require("../../library/include/document.php");
$tesbro = gaz_dbi_get_row($gTables['tescmr'],"id_tes", intval($_GET['id_tes']));
if ($tesbro['tipdoc']=='CMR') {
    $id_anagra = gaz_dbi_get_row( $gTables['clfoco'], 'codice', $tesbro['clfoco'] );
    $stato = gaz_dbi_get_row( $gTables['anagra'], 'id', $id_anagra['id_anagra']);
    $lang_template=false;
	if ($stato['country']!=="IT") {
		$lang_template='english';
	}
    if (isset($_GET['dest']) && $_GET['dest']=='E' ){ // se l'utente vuole inviare una mail
        createDocument($tesbro, 'Cmr',$gTables,'rigcmr','E',$lang_template);
    } else {
        createDocument($tesbro, 'Cmr',$gTables,'rigcmr',false,$lang_template);
    }
} else {
    header("Location: report_cmr.php");
    exit;
}
?>