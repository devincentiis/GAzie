<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
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

include_once("manual_settings.php");
$genTables = constant("table_prefix")."_";
$azTables = constant("table_prefix").$idDB;
$IDaz=preg_replace("/[^1-9]/", "", $azTables );

$servername = constant("Host");
$username = constant("User");
$pass = constant("Password");
$dbname = constant("Database");
// Create connection
$link = mysqli_connect($servername, $username, $pass, $dbname);
// Check connection
if (!$link) {
    die("Connection DB failed: " . mysqli_connect_error());
}
$link -> set_charset("utf8");

require("document.php");

$sql = "SELECT * FROM ".$azTables."tesbro"." WHERE id_tes = ". intval($_GET['id_tes'])." LIMIT 1";
if ($result = mysqli_query($link, $sql)) {
  $tesbro = mysqli_fetch_assoc($result);
}else{
echo "Error: " . $sql . "<br>" . mysqli_error($link);
}

if ($tesbro_data = json_decode($tesbro['custom_field'], TRUE)){// se la testata ha un custom field
  if (is_array($tesbro_data['vacation_rental']) && isset($tesbro_data['vacation_rental']['security_deposit'])){
    $tesbro['security_deposit']=$tesbro_data['vacation_rental']['security_deposit'];
  }
}
$id_ag='';
if (isset($_GET['id_ag']) && $_GET['id_ag']>0){// se è stato passato un proprietario/agente
	$id_ag=intval($_GET['id_ag']);
}
$lang = false;

$sql = "SELECT id_anagra FROM ".$azTables."clfoco"." WHERE codice = ". $tesbro['clfoco']." LIMIT 1";
if ($result = mysqli_query($link, $sql)) {
  $res = mysqli_fetch_assoc($result);
  $id_anagra['id_anagra']=$res['id_anagra'];
}else{
echo "Error: " . $sql . "<br>" . mysqli_error($link);
}

$stato = gaz_dbi_get_row($gTables['anagra'], 'id', $id_anagra['id_anagra']);
if ($stato AND $stato['id_language'] == 1 or $stato['id_language'] == 0){// se è italiano o non è impostato
    $lang = '';
} elseif ($stato AND $stato['id_language'] == 2 ) {// se è inglese
  $lang = 'english';
}elseif ($stato AND $stato['id_language'] == 3 ) {// se è spagnolo
  $lang = 'spanish';
}
if ($tesbro['tipdoc']=='VOR' || $tesbro['tipdoc']=='VOG') {
	$type=false;
	$template='Lease';
  if (isset($_GET['dest'])&& $_GET['dest']=='E' ){ // se l'utente vuole inviare una mail
    $type='E';
  }
	if (isset($_GET['lh'])){ // se l'utente vuole che venga stampata su una carta intestata
		$type='H';
	}
	if ($tesbro['template']=='Ticket'){
		$template='Ticket';
	}
  $save=(isset($_GET['save']))?true:false;
  createDocument($tesbro,$template,$gTables,'rigbro',$type,$lang,$genTables,$azTables,$IDaz,'',$id_ag,'it',"",$save);
} elseif ($tesbro['tipdoc']=='VOW'){
	$type=false;
  createDocument($tesbro, 'OrdineWeb',$gTables,'rigbro',$type,$lang);
} else {
    header("Location: report_booking.php");
    exit;
}
?>
