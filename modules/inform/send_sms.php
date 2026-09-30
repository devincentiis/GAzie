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

$where = $gTables['letter'].".id_let=".$_GET['id_sms'];
$table = $gTables['letter']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['letter'].".clfoco=".$gTables['clfoco'].".codice
                              LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id=".$gTables['clfoco'].".id_anagra";
$result = gaz_dbi_dyn_query($gTables['letter'].".corpo, ".$gTables['anagra'].".cell ", $table, $where);

if (!$result || !($send_sms = gaz_dbi_fetch_array($result))) {
	die('Impossibile inoltrare questo SMS!');
}

$mobile_number = $send_sms['cell'];
$short_message = $send_sms['corpo'];

$send_sms_package = gaz_dbi_get_row($gTables['company_config'], 'var', 'send_sms');
require_once('../../library/' . $send_sms_package['val'] . '/SendSMS.php');
$sendSMS = new SendSMS();
$mobile_number = $sendSMS->validate_mobile_number($mobile_number);
if (!empty($mobile_number)) {
	$esitoSpedizioneSMS = $sendSMS->runInviaSMS($mobile_number, $short_message);
}

?>