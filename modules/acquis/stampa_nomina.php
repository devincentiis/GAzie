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
$admin_aziend=checkAdmin(9);

$logo = $admin_aziend['image'];
if (!isset($_POST['ritorno'])) {
        $ritorno = $_SERVER['HTTP_REFERER'];
}
if (!isset($_GET['id'])) {
     header("Location: ".$ritorno);
}
$codice = intval($_GET['id']);

/* richiedo il template del responsabile o dell'incaricato a secondo che sia un utente:
 - con il massimo livello di abilitazione (9) assimilabile ad un amministratore di sistema quindi "RESPONSABILE"
 - oppure un utente normale "INCARICATO" 
*/
$ri='NominaResponsabileEsterno';
require("../../library/include/document.php");
$testata= array('id_tes'=> 0,'seziva'=>0,'tipdoc'=>'','protoc'=>0,'numdoc'=>0,
          'numfat'=>0,'datfat'=>0,'clfoco'=>$codice,
          'datemi'=>0,'pagame'=>0,'banapp'=>0,'vettor'=>0,
          'listin'=>0,'spediz'=>'','portos'=>'','imball'=>'',
          'traspo'=>0,'speban'=>0,'spevar'=>0,'ivaspe'=>0,'sconto'=>0,'id_agente'=>0,'initra'=>0,
          'geneff'=>0,'id_contract'=>0,'id_con'=>0,'status'=>'','template'=>'InformativaPrivacy');
createDocument($testata, $ri, $gTables);
?>