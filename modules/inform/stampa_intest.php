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
require("../vendit/lib.function.php");
$dati_intestazione = $_SESSION['print_request'];
unset ($_SESSION['print_request']);
$testata= array('id_tes'=> 0,'seziva'=>0,'protoc'=>0,'numdoc'=>0,'numfat'=>0,'destin'=>'  ',
          'datfat'=>$dati_intestazione['data'],'clfoco'=>$dati_intestazione['clfoco'],
          'datemi'=>$dati_intestazione['data'],'pagame'=>0,'banapp'=>0,'vettor'=>0,
          'listin'=>0,'spediz'=>'','portos'=>'','imball'=>'','traspo'=>0,'speban'=>0,
          'net_weight'=>'','gross_weight'=>'','units'=>'','volume'=>'',
          'id_agente'=>0,'spevar'=>0,'ivaspe'=>0,'sconto'=>0,'initra'=>0,'geneff'=>0,'id_contract'=>0,
          'id_con'=>0,'status'=>'','template'=>$dati_intestazione['template'],'caumag'=>0,'stamp'=>0,
          'virtual_taxstamp'=>0,'taxstamp'=>0,'expense_vat'=>0,'ddt_type'=>'');
$gazTimeFormatter->setPattern('MMMM');
$descrizione_data = $gazTimeFormatter->format(new DateTime($testata['datfat']));

switch ($dati_intestazione['template']) {
  case "CartaIntestata":
  $testata['tipdoc'] = 'NOP';
  //array('id_tes'=> 0,'seziva'=>0,'tipdoc'=>'','datemi'=>$data,'protoc'=>0,'numdoc'=>0,'numfat'=>0,'datfat'=>$data,'clfoco'=>$_POST['cod_partner'],'pagame'=>0,'banapp'=>0,'vettor'=>0,'listin'=>0,'spediz'=>$_POST['descri'],'portos'=>0,'imball'=>0,'traspo'=>0,'speban'=>0,'spevar'=>0,'ivaspe'=>0,'sconto'=>0,'initra'=>$data,'geneff'=>0,'id_contract'=>0,'id_con'=>0,'status'=>'','template'=>'CartaIntestata');
  break;
  case "FatturaImmediata":
  case "FatturaSemplice":
  $testata['tipdoc'] = 'FAI';
  break;
  case "DDT":
  $testata['tipdoc'] = 'DDT';
  break;
}
if(!empty($dati_intestazione['descrizione'])) {
  $testata['imball'] = $dati_intestazione['descrizione'];
}
if(!empty($testata['datemi'])) {
  if (empty($testata['imball'])) {
    $testata['imball'] .= '_____________________________ ';
  }
  $testata['imball'] .= ' del '.substr($testata['datemi'],8,2).' '.ucwords($gazTimeFormatter->format(new DateTime($testata['datemi']))).' '.substr($testata['datemi'],0,4);
}
createDocument($testata, $testata['template'],$gTables);
?>
