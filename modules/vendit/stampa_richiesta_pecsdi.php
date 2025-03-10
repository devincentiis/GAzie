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
$dest=false;
$logo = $admin_aziend['image'];
if (!isset($_POST['ritorno'])) {
        $ritorno = $_SERVER['HTTP_REFERER'];
}
if (!isset($_GET['codice'])) {
     header("Location: ".$ritorno);
}
if (isset($_GET['dest']) && $_GET['dest']=='E') {
	$dest='E';
}
$codice = intval($_GET['codice']);
if (substr($codice,0,3) != $admin_aziend['mascli'] or substr($codice,3,9) == 0) {
        header("Location: ".$ritorno);
}
require("../../library/include/document.php");
$anagrafica = new Anagrafica();
$cliente = $anagrafica->getPartner($codice);
$testata= array('id_tes'=> 0,'seziva'=>0,'tipdoc'=>'','protoc'=>0,'numdoc'=>0,
          'numfat'=>0,'datfat'=>0,'clfoco'=>$codice,
          'datemi'=>0,'pagame'=>0,'banapp'=>0,'vettor'=>0,
          'listin'=>0,'spediz'=>'','portos'=>'','imball'=>'',
          'traspo'=>0,'speban'=>0,'spevar'=>0,'ivaspe'=>0,'sconto'=>0,'id_agente'=>0,'initra'=>0,
          'geneff'=>0,'id_contract'=>0,'id_con'=>0,'status'=>'','template'=>'InformativaPrivacy');
if (!empty(trim($cliente['sedleg']))){ // sposto l'eventuale sede legale al posto della destinazione merce in testata
	$testata['destin']=array('Sede legale',$cliente['sedleg']);	
}

createDocument($testata, 'RichiestaPecSdi',$gTables,'rigdoc',$dest);
?>