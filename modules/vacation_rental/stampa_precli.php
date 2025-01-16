<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-present - Antonio Germani, Massignano (AP)
  (https://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
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
include_once("manual_settings.php");
$genTables = constant("table_prefix")."_";
$azTables = constant("table_prefix").$idDB;

require("document.php");
$tesbro = gaz_dbi_get_row($gTables['tesbro'],"id_tes", intval($_GET['id_tes']));

$lang_template = false;
$id_anagra = gaz_dbi_get_row($gTables['clfoco'], 'codice', $tesbro['clfoco']);
$stato = gaz_dbi_get_row($gTables['anagra'], 'id', $id_anagra['id_anagra']);
if ($stato AND $stato['id_language'] == 1 or $stato['id_language'] == 0){// se è italiano o non è impostato
    $lang_template = '';$lang="it";
} elseif ($stato AND $stato['id_language'] == 2 ) {// se è inglese
  $lang_template = 'english';$lang="en";
}elseif ($stato AND ($stato['id_language'] == 3 || $stato['id_language'] == 4 || $stato['id_language'] == 5) ) {// se è spagnolo
  $lang_template = 'spanish';$lang="es";
}elseif ($stato AND $stato['id_language'] == 6 ) {// se è inglese
  $lang_template = 'french';$lang="fr";
}elseif ($stato AND $stato['id_language'] == 7 ) {// se è inglese
  $lang_template = 'polish';$lang="pl";
}else{
	$lang_template = '';$lang="it";
}
$user_level=get_user_points_level($id_anagra['id_anagra']);

if ($tesbro['tipdoc']=='VPR') {
	$dest=false;
	$template='BookingQuote';
  if (isset($_GET['dest'])&& $_GET['dest']=='E' ){ // se l'utente vuole inviare una mail
    $dest='E';
  }elseif (isset($_GET['dest'])){
    $email=filter_var($_GET['dest'], FILTER_VALIDATE_EMAIL);
    $dest=$email;
    $r=gaz_dbi_put_row($gTables['tesbro'], 'id_tes', $tesbro['id_tes'], 'email',$email);
  }
	if (isset($_GET['lh'])){ // se l'utente vuole che venga stampata su una carta intestata
		$dest='H';
	}
	if ($tesbro['template']=='Ticket'){
		$template='Ticket';
	}
  createDocument($tesbro,$template,$gTables,'rigbro',$dest,$lang_template,$genTables,$azTables,"","","",$lang,$user_level);
} else {
    header("Location: report_booking?auxil=VPR.php");
    exit;
}
?>
