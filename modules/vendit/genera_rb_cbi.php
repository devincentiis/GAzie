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

$admin_aziend = checkAdmin();
if (! isset($_GET['banacc']) or ! isset($_GET['scaini']) or ! isset($_GET['scafin']) or ! isset($_GET['proini']) or ! isset($_GET['profin'])) {
    header("Location: report_effett.php");
    exit;
}
require("../../library/include/riba_cbi.inc.php");

$anagrafica = new Anagrafica();
$contoAccredito = $anagrafica->getPartner(intval($_GET['banacc']));
$countryData = gaz_dbi_get_row($gTables['country'], "iso", $contoAccredito['country']);
$bancaAccredito = gaz_dbi_get_row($gTables['banapp'], "codice", $contoAccredito['banapp']);
if (isset($_GET['datemi'])) {
    $dataemissione = substr($_GET['datemi'], 8, 2) . substr($_GET['datemi'], 5, 2) . substr($_GET['datemi'], 2, 2);
    $defiles=substr($_GET['datemi'],0,10);
} else {
    $dataemissione = date("dmy");
    $defiles = date("Y-m-d");
}
// creo il file con il nome ottenuto in precedenza.
$filename = "RIBAdel_$defiles.cbi";
$where = $gTables['effett'] .".status = 'CHK' AND (".$gTables['effett'] . ".id_distinta = 0 OR id_distinta IS NULL) AND tipeff = 'B' AND scaden BETWEEN '" . $_GET['scaini'] . "' AND '" . $_GET['scafin'] . "' AND progre BETWEEN '" . $_GET['proini'] . "' AND '" . $_GET['profin'] . "' ";
//recupero le testate in base alle scelte impostate
$result = gaz_dbi_dyn_query("*", $gTables['effett'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['effett'] . ".clfoco = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['anagra'] . ".id = " . $gTables['clfoco'] . ".id_anagra LEFT JOIN " . $gTables['banapp'] . " ON " . $gTables['effett'] . ".banapp = " . $gTables['banapp'] . ".codice", $where, "tipeff ASC,scaden ASC, id_tes ASC");
//C.F. o P.I. creditore
if (empty($admin_aziend['pariva'])) {
    $codfis = $admin_aziend['codfis'];
} else {
    $codfis = $admin_aziend['pariva'];
}
$arrayTestata = array($bancaAccredito['codabi'],
    $bancaAccredito['codcab'],
    substr($contoAccredito['iban'], $countryData['account_number_pos'] - 1, $countryData['account_number_lenght']),
    $dataemissione,
    "SC_" . substr($_GET['scaini'], 8, 2) . "." . substr($_GET['scaini'], 5, 2) . "." . substr($_GET['scaini'], 2, 2) . "-" . substr($_GET['scafin'], 8, 2) . "." . substr($_GET['scafin'], 5, 2) . "." . substr($_GET['scafin'], 2, 2),
    "E",
    $admin_aziend['ragso1'],
    $admin_aziend['ragso2'],
    $admin_aziend['indspe'],
    $admin_aziend['capspe'] . " " . $admin_aziend['citspe'] . " " . $admin_aziend['prospe'],
    $codfis,
    $contoAccredito['sia_code']);
if (isset($_GET['eof'])) {
    $arrayTestata[12] = 1;
}
// inserisco il riferimento al file della distinta
$id_doc=gaz_dbi_table_insert('files', array('table_name_ref'=>'effett','id_ref'=>intval($_GET['banacc']),'item_ref'=>'distinta','extension'=>'cbi', 'title'=>$filename, 'custom_field'=>'{"vendit":{"credttm":"'.$defiles.'","tipeff":"B","scaini":"'.substr($_GET['scaini'],0,10).'","scafin":"'.substr($_GET['scafin'], 0, 10).'","proini":"'.intval($_GET['proini']).'","profin":"'.intval($_GET['profin']).'"}}'));

$arrayRiba =[];
while ($row = gaz_dbi_fetch_array($result)) {
    //C.F. o P.I. debitore
    if (empty($row['pariva'])) {
        $codfis = $row['codfis'];
    } else {
        $codfis = $row['pariva'];
    }
    // a saldo o in acconto
    if ($row['salacc'] == "S") {
        $descrizione_debito = "SALDO FT." . $row['numfat'] . "/" . $row['seziva'] . " DEL " . substr($row['datfat'], 8, 2) . "/" . substr($row['datfat'], 5, 2) . "/" . substr($row['datfat'], 2, 2);
    } else {
        $descrizione_debito = "ACCONTO FT." . $row['numfat'] . "/" . $row['seziva'] . " DEL " . substr($row['datfat'], 8, 2) . "/" . substr($row['datfat'], 5, 2) . "/" . substr($row['datfat'], 2, 2);
    }
    $arrayRiba[] = array($row['progre'],
        substr($row['scaden'], 8, 2) . substr($row['scaden'], 5, 2) . substr($row['scaden'], 2, 2),
        $row['impeff'] * 100,
        $row['ragso1'] . $row['ragso2'],
        $codfis,
        $row['indspe'],
        $row['capspe'],
        $row['citspe'],
        $row['codabi'],
        $row['codcab'],
        $row['descri'] . " " . $row['locali'] . " " . $row['codpro'],
        $row['clfoco'],
        $descrizione_debito,
        $row['prospe'],
        $row['cigcup']);
        // aggiorno l'effetto sul db indicando in id_distinta l'id_doc di gaz_NNNfiles  
        gaz_dbi_query("UPDATE ". $gTables['effett']." SET id_distinta=".$id_doc.", banacc=".intval($_GET['banacc']).", status='DISTINTATA' WHERE id_tes=".$row['id_tes']);		
}
$RB = new RibaAbiCbi();
// Impostazione degli header per l'opozione "save as" dello standard input che verr� generato
header('Content-Type: text/x-cbi');
header("Content-Disposition: attachment; filename=" . $filename);
header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
} else {
    header('Pragma: no-cache');
}
$cont = $RB->creaFile($arrayTestata, $arrayRiba);
$h=fopen(DATA_DIR . "files/" .$admin_aziend['company_id']."/doc/". $id_doc . ".cbi", 'x+');
fwrite($h,$cont);
fclose($h);
print $cont;
exit;
?>