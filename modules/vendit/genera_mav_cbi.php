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
$admin_aziend = checkAdmin();
if (! isset($_GET['banacc']) or ! isset($_GET['scaini']) or ! isset($_GET['scafin']) or ! isset($_GET['proini']) or ! isset($_GET['profin'])) {
    header("Location: report_effett.php");
    exit;
}
require("../../library/include/mav_cbi.inc.php");

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
$filename = "MAVdel_$defiles.cbi";
$where = "(".$gTables['effett'] . ".id_distinta = 0 OR id_distinta IS NULL) AND tipeff = 'V' AND scaden BETWEEN '" . $_GET['scaini'] . "' AND '" . $_GET['scafin'] . "' AND progre BETWEEN '" . $_GET['proini'] . "' AND '" . $_GET['profin'] . "' ";
//recupero le testate in base alle scelte impostate
$result = gaz_dbi_dyn_query("*", $gTables['effett'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['effett'] . ".clfoco = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['anagra'] . ".id = " . $gTables['clfoco'] . ".id_anagra LEFT JOIN " . $gTables['banapp'] . " ON " . $gTables['effett'] . ".banapp = " . $gTables['banapp'] . ".codice", $where, "tipeff ASC,scaden ASC, id_tes ASC");
//C.F. o P.I. creditore
if (empty($admin_aziend['pariva'])) {
    $codfis = $admin_aziend['codfis'];
} else {
    $codfis = $admin_aziend['pariva'];
}
$arrayTestata = array('sia_mittente' => '',
    'abi_assuntrice' => $bancaAccredito['codabi'],
    'data_creazione' => $dataemissione,
    'nome_supporto' => "SC_" . substr($_GET['scaini'], 8, 2) . "." . substr($_GET['scaini'], 5, 2) . "." . substr($_GET['scaini'], 2, 2) . "-" . substr($_GET['scafin'], 8, 2) . "." . substr($_GET['scafin'], 5, 2) . "." . substr($_GET['scafin'], 2, 2),
    'cab_assuntrice' => $bancaAccredito['codcab'],
    'conto' => substr($contoAccredito['iban'], $countryData['account_number_pos'] - 1, $countryData['account_number_lenght']),
    'sia_ordinante' => '',
    'ragione_soc1_creditore' => $admin_aziend['ragso1'],
    'ragione_soc2_creditore' => $admin_aziend['ragso2'],
    'indirizzo_creditore' => $admin_aziend['indspe'],
    'cap_citta_prov_creditore' => $admin_aziend['capspe'] . " " . $admin_aziend['citspe'] . " " . $admin_aziend['prospe'],
);
// inserisco il riferimento al file della distinta
$id_doc=gaz_dbi_table_insert('files', array('table_name_ref'=>'effett','id_ref'=>intval($_GET['banacc']),'item_ref'=>'distinta','extension'=>'cbi', 'title'=>$filename, 'custom_field'=>'{"vendit":{"credttm":"'.$defiles.'","tipeff":"V","scaini":"'.substr($_GET['scaini'],0,10).'","scafin":"'.substr($_GET['scafin'], 0, 10).'","proini":"'.intval($_GET['proini']).'","profin":"'.intval($_GET['profin']).'"}}'));

$arrayMAV = array();
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
    $arrayMAV[] = array('scadenza' => substr($row['scaden'], 8, 2) . substr($row['scaden'], 5, 2) . substr($row['scaden'], 2, 2),
        'importo' => $row['impeff'] * 100,
        'nome_debitore' => $row['ragso1'] . $row['ragso2'],
        'codice_fiscale_debitore' => $codfis,
        'indirizzo_debitore' => $row['indspe'],
        'cap_debitore' => $row['capspe'],
        'comune_provincia_debitore' => $row['citspe'] . " " . $row['prospe'],
        'descrizione_domiciliataria' => $row['descri'] . " " . $row['locali'] . " " . $row['codpro'],
        'descrizione_debito' => $descrizione_debito,
        'numero_disposizione' => $row['progre'],
        'tipo_bollettino' => '',
        'tipo_codice' => '1',
        'codice_cliente' => $row['clfoco'],
    );
    // aggiorno l'effetto sul db indicando in id_distinta l'id_doc di gaz_NNNfiles  
    gaz_dbi_query("UPDATE ". $gTables['effett']." SET id_distinta=".$id_doc.", banacc=".intval($_GET['banacc'])." WHERE id_tes=".$row['id_tes']);		
}
$MAV = new MavAbiCbi();
// Impostazione degli header per l'opozione "save as" dello standard input che verr� generato
header('Content-Type: text/x-cbi');
header("Content-Disposition: attachment; filename=" . $filename);
header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // per poter ripetere l'operazione di back-up pi� volte.
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
} else {
    header('Pragma: no-cache');
}
$cont = $MAV->creaFile($arrayTestata, $arrayMAV);
$h=fopen(DATA_DIR . "files/" .$admin_aziend['company_id']."/doc/". $id_doc . ".cbi", 'x+');
fwrite($h,$cont);
fclose($h);
print $cont;
exit;
?>