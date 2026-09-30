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
if (isset($_GET['filename'])) {
  $doc = pathinfo($_GET['filename']);
  $doc['title']=$_GET['descriname'];
  $filepath = $admin_aziend['company_id']."/".$doc['basename'];
} elseif (isset($_GET['id_ref'])) {
  $doc = gaz_dbi_get_row($gTables['files'],'id_doc',intval($_GET['id_ref']));
	$filepath=$admin_aziend['company_id']."/images/".$doc['id_doc'].'.'.$doc['extension'];
} elseif (isset($_GET['filepath'])) {
  $doc = pathinfo($_GET['filepath']);
  $doc['title']=$_GET['descriname'];
	$filepath=$admin_aziend['company_id']."/".$_GET['filepath'];
} else {
  $doc = gaz_dbi_get_row($gTables['files'],'id_doc',intval($_GET['id_doc']));
	$filepath=$admin_aziend['company_id']."/doc/".$doc['id_doc'].'.'.$doc['extension'];
}
$doc['extension']=(strlen($doc['extension'])<3)?"octet-stream":$doc['extension'];// nell'ipotesi in cui non ci fosse una estensione metto quella di default per il Content-Type
if (strlen (substr(strrchr($doc['title'], "."), 1))<3){// aggiungo al title l'estensione, se non c'è (per retrocompatibilità)
  $doc['title'] .= ".".$doc['extension'];
}
//switch per content type
$lowext=strtolower($doc['extension']);
switch ($lowext) {
  case 'xml':
  case 'pdf':
  case 'zip':
    $cont="application";
    break;
  case 'gif':
  case 'jpeg':
  case 'png':
  case 'svg':
  case 'icon':
    $cont="image";
  break;
  case 'txt':
    $cont="text";
  break;
  default:
    $cont="application";
}

header("Content-Type: ".$cont."/".$lowext);
header('Content-Disposition: attachment; filename="'.$doc['title'].'"');
// data retrieved from filesystem
$doc=file_get_contents(DATA_DIR.'files/'.$filepath);
echo $doc;
?>
