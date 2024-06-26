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
$testat = $_GET['id_tes'];
$tesbro = gaz_dbi_get_row($gTables['tesbro'],"id_tes", $testat);
$lang = false;
$id_anagra = gaz_dbi_get_row($gTables['clfoco'], 'codice', $tesbro['clfoco']);
$id_lang = gaz_dbi_get_row($gTables['anagra'], 'id', $id_anagra['id_anagra'])['id_language'];
$lan_row = gaz_dbi_get_row($gTables['languages'], 'lang_id', $id_lang);
$lan_sef = (isset($lan_row))?$lan_row['sef']:'';

switch($lan_sef){
	case 'it':
		$lang='';
		break;
	case 'en':
		$lang='english';
		break;
	case 'es':
		$lang='espanol';
		break;
	default:
		$lang='';
		break;
}

if ($tesbro['tipdoc'] <> 'VPR') {
  header("Location: report_broven.php");
  exit;
}
if (isset($_GET['dest'])&& $_GET['dest']=='E' ){ // se l'utente vuole inviare una mail
  createDocument($tesbro, 'PreventivoCliente',$gTables,'rigbro','E', $lang, false);
} elseif (isset($_GET['lh'])){ // se l'utente vuole che venga stampata su una carta intestata
  createDocument($tesbro, 'PreventivoCliente',$gTables,'rigbro','H', $lang, false);
}else {
  createDocument($tesbro, 'PreventivoCliente',$gTables,'rigbro',false, $lang, false);
}
?>
