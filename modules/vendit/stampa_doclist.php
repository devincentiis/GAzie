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
require("../../library/include/calsca.inc.php");
$msg = '';

if (!isset($_GET['si'])) {
   $_GET['si'] = 1;
}
if (!isset($_GET['pi'])) {
   $_GET['pi'] = 1;
}
if (!isset($_GET['pf'])) {
   $_GET['pf'] = 999999999;
}
if (!isset($_GET['ni'])) {
   $_GET['ni'] = 1;
}
if (!isset($_GET['nf'])) {
   $_GET['nf'] = 999999999;
}
if (!isset($_GET['di'])) {
   $_GET['di'] = intval(date("Y").'0101');
}
if (!isset($_GET['df'])) {
   $_GET['df'] = date("Ymd");
}
if (!isset($_GET['cl'])) { // cliente
   $_GET['cl'] = 0;
}
if (!isset($_GET['ag'])) {   // selezione agente
   $_GET['ag'] = 0;
}

function getDocuments($td = 0, $si = 1, $where_data=['cl'=>0,'ag'=>0]) {
    global $gTables, $admin_aziend;
    $calc = new Compute;
    $type=[0=>'F__',1=>'FAD', 2=>'FAI', 3=> 'FNC', 4=> 'FND',5 => 'FAP',6=> 'DD_',7=> 'VCO'];
    $customer =($where_data['cl']>100000000)?' AND clfoco = '.$where_data['cl']:'';
    $agente =($where_data['ag']>=1)?' AND tesdoc.id_agente = '.$where_data['ag']:'';
    $datfat=($td==6)?'datemi':'datfat';
    $numfat=($td==6)?'numdoc':'REGEXP_SUBSTR(numfat,"[0-9]+")';
    $where_data['pi']=($td==6||$td==7)?'0':$where_data['pi'];
    $where_data['pf']=($td==6||$td==7)?'999999999':$where_data['pf'];
    $where =($td==2)?"(tipdoc = 'FAI' OR tipdoc = 'FAA')":"tipdoc LIKE '".$type[$td]."'";
    $where .= " AND seziva = $si AND $datfat BETWEEN ". $where_data['di'] ." AND ". $where_data['df']." AND protoc BETWEEN ". $where_data['pi'] ." AND ". $where_data['pf']
    ." AND $numfat BETWEEN ". $where_data['ni'] ." AND ". $where_data['nf']. $customer . $agente;
    $from = $gTables['tesdoc'] . ' AS tesdoc
             LEFT JOIN ' . $gTables['pagame'] . ' AS pay
             ON tesdoc.pagame=pay.codice
             LEFT JOIN ' . $gTables['clfoco'] . ' AS customer
             ON tesdoc.clfoco=customer.codice
             LEFT JOIN ' . $gTables['anagra'] . ' AS anagraf
             ON customer.id_anagra=anagraf.id';
    $orderby = "$datfat ASC, protoc ASC";
    $result = gaz_dbi_dyn_query('tesdoc.*,
                        pay.tippag,pay.numrat,pay.incaut,pay.tipdec,pay.giodec,pay.tiprat,pay.mesesc,pay.giosuc,pay.id_bank,
                        customer.codice,
                        customer.speban AS addebitospese,
                        CONCAT(anagraf.ragso1,\' \',anagraf.ragso2) AS ragsoc,CONCAT(anagraf.citspe,\' (\',anagraf.prospe,\')\') AS citta', $from, $where, $orderby);
    $doc = array();
    $ctrlp = 0;

    $carry = 0;
    $ivasplitpay = 0;
    $somma_spese = 0;
    $totimpdoc = 0;
    $rit = 0;
    while ($tes = gaz_dbi_fetch_array($result)) {
        if ($td==6) { // ddt
          $tes['protoc']=$tes['numdoc'];
        } elseif($td==7){ // corrispettivo
          $tes['protoc']= $tes['datemi'].$tes['numdoc'];
        }
        $tes['protoc']=($td==6)?$tes['numdoc']:$tes['protoc'];
        if ($tes['protoc'] <> $ctrlp) { // la prima testata della fattura
          switch ($tes['tipdoc']) {
            case "AFA":case "AFC":case "AFD":
            $bol=$admin_aziend['taxstamp_account'];
            break;
            default:
            $bol=$admin_aziend['boleff'];
            break;
          }
          if ($ctrlp > 0 && ($doc[$ctrlp]['tes']['stamp'] >= 0.01 || $doc[$ctrlp]['tes']['taxstamp'] >= 0.01 )) { // non è il primo ciclo faccio il calcolo dei bolli del pagamento e lo aggiungo ai castelletti
            $calc->payment_taxstamp($calc->total_imp + $calc->total_vat + $carry - $rit - $ivasplitpay + $taxstamp, $doc[$ctrlp]['tes']['stamp'], $doc[$ctrlp]['tes']['round_stamp'] * $doc[$ctrlp]['tes']['numrat']);
            $calc->add_value_to_VAT_castle($doc[$ctrlp]['vat'], $taxstamp + $calc->pay_taxstamp, $admin_aziend['taxstamp_vat']);
            $doc[$ctrlp]['vat'] = $calc->castle;
            // aggiungo il castelleto conti
            if (!isset($doc[$ctrlp]['acc'][$bol])) {
                $doc[$ctrlp]['acc'][$bol]['import'] = 0;
            }
            $doc[$ctrlp]['acc'][$bol]['import'] += $taxstamp + $calc->pay_taxstamp;
          }
          $carry = 0;
          $ivasplitpay = 0;
          $cast_vat = array();
          $cast_acc = array();
          $somma_spese = 0;
          $totimpdoc = 0;
          $totimp_decalc = 0.00;
          $n_vat_decalc = 0;
          $spese_incasso = $tes['numrat'] * $tes['speban'];
          $taxstamp = 0;
          $rit = 0;
        } else {
            $spese_incasso = 0;
        }
        // aggiungo il bollo sugli esenti/esclusi se nel DdT c'è ma non è ancora stato mai aggiunto
        if ($tes['taxstamp'] >= 0.01 && $taxstamp < 0.01) {
            $taxstamp = $tes['taxstamp'];
        }
        if ($tes['virtual_taxstamp'] == 0 || $tes['virtual_taxstamp'] == 3) { //  se è a carico dell'emittente non lo aggiungo al castelletto IVA
            $taxstamp = 0.00;
        }
        if ($tes['traspo'] >= 0.01) {
            if (!isset($cast_acc[$admin_aziend['imptra']]['import'])) {
                $cast_acc[$admin_aziend['imptra']]['import'] = $tes['traspo'];
            } else {
                $cast_acc[$admin_aziend['imptra']]['import'] += $tes['traspo'];
            }
        }
        if ($spese_incasso >= 0.01) {
            if (!isset($cast_acc[$admin_aziend['impspe']]['import'])) {
                $cast_acc[$admin_aziend['impspe']]['import'] = $spese_incasso;
            } else {
                $cast_acc[$admin_aziend['impspe']]['import'] += $spese_incasso;
            }
        }
        if ($tes['spevar'] >= 0.01) {
            if (!isset($cast_acc[$admin_aziend['impvar']]['import'])) {
                $cast_acc[$admin_aziend['impvar']]['import'] = $tes['spevar'];
            } else {
                $cast_acc[$admin_aziend['impvar']]['import'] += $tes['spevar'];
            }
        }
        //recupero i dati righi per creare il castelletto
        $from = $gTables['rigdoc'] . ' AS rs
                    LEFT JOIN ' . $gTables['aliiva'] . ' AS vat
                    ON rs.codvat=vat.codice';
        $rs_rig = gaz_dbi_dyn_query('rs.*,vat.tipiva AS tipiva', $from, "rs.id_tes = " . $tes['id_tes'], "id_tes DESC");
        while ($r = gaz_dbi_fetch_array($rs_rig)) {
            if ($r['tiprig'] <= 1  || $r['tiprig'] == 4 || $r['tiprig'] == 90) { // se del tipo normale, forfait, cassa previdenziale, vendita cespite
                //calcolo importo rigo
                $importo = CalcolaImportoRigo($r['quanti'], $r['prelis'], array($r['sconto'], $tes['sconto']));
                if ($r['tiprig']==1||$r['tiprig']== 90) { // se di tipo forfait e vendita cespite
                    $importo = CalcolaImportoRigo(1, $r['prelis'], $tes['sconto']);
                } elseif($r['tiprig']==4){ // cassa previdenziale sul database  trovo la percentuale sulla colonna provvigione
                    $importo = round($r['prelis']*$r['provvigione']/100,2);
				}
				if ($tes['tipdoc'] == 'FNC') {
					$importo*= -1;
				}
                //creo il castelletto IVA
                if (!isset($cast_vat[$r['codvat']]['impcast'])) {
                    $cast_vat[$r['codvat']]['impcast'] = 0;
                    $cast_vat[$r['codvat']]['ivacast'] = 0;
                    $cast_vat[$r['codvat']]['periva'] = $r['pervat'];
                    $cast_vat[$r['codvat']]['tipiva'] = $r['tipiva'];
                }
                $cast_vat[$r['codvat']]['impcast'] += $importo;
                $cast_vat[$r['codvat']]['ivacast'] += round(($importo * $r['pervat']) / 100, 2);
                $totimpdoc += $importo;
                //creo il castelletto conti
                if (!isset($cast_acc[$r['codric']]['import'])) {
                    $cast_acc[$r['codric']]['import'] = 0;
                }
                $cast_acc[$r['codric']]['import'] += $importo;
                if ($r['tiprig'] == 90) { // se è una vendita cespite lo indico sull'array dei conti
                    $cast_acc[$r['codric']]['asset'] = 1;
                }
                $rit += round($importo * $r['ritenuta'] / 100, 2);
                // aggiungo all'accumulatore l'eventuale iva non esigibile (split payment)
                if ($r['tipiva'] == 'T') {
                    $ivasplitpay += round(($importo * $r['pervat']) / 100, 2);
                }
            } elseif ($r['tiprig'] == 3) {
                $carry += $r['prelis'];
            }
        }
        $doc[$tes['protoc']]['tes'] = $tes;
        $doc[$tes['protoc']]['acc'] = $cast_acc;
        $doc[$tes['protoc']]['car'] = $carry;
        $doc[$tes['protoc']]['isp'] = $ivasplitpay;
        $doc[$tes['protoc']]['rit'] = $rit;
        $somma_spese += $tes['traspo'] + $spese_incasso + $tes['spevar'];
        $calc->add_value_to_VAT_castle($cast_vat, $somma_spese, $tes['expense_vat']);
        $doc[$tes['protoc']]['vat'] = $calc->castle;
        $ctrlp=$tes['protoc'];
    }
    if ($doc[$ctrlp]['tes']['stamp'] >= 0.01 || $taxstamp >= 0.01) { // a chiusura dei cicli faccio il calcolo dei bolli del pagamento e lo aggiungo ai castelletti
        $calc->payment_taxstamp($calc->total_imp + $calc->total_vat + $carry - $rit - $ivasplitpay + $taxstamp, $doc[$ctrlp]['tes']['stamp'], $doc[$ctrlp]['tes']['round_stamp'] * $doc[$ctrlp]['tes']['numrat']);
        // aggiungo al castelletto IVA
        $calc->add_value_to_VAT_castle($doc[$ctrlp]['vat'], $taxstamp + $calc->pay_taxstamp, $admin_aziend['taxstamp_vat']);
        $doc[$ctrlp]['vat'] = $calc->castle;
        // aggiungo il castelleto conti
        if (!isset($doc[$ctrlp]['acc'][$bol])) {
            $doc[$ctrlp]['acc'][$bol]['import'] = 0;
        }
        $doc[$ctrlp]['acc'][$bol]['import'] += $taxstamp + $calc->pay_taxstamp;
    }
    return $doc;
}

// preparo l'array dei limiti
$where_data = [
'di'=>intval($_GET['di']),
'df'=>intval($_GET['df']),
'pi'=>intval($_GET['pi']),
'pf'=>intval($_GET['pf']),
'ni'=>intval($_GET['ni']),
'nf'=>intval($_GET['nf']),
'cl'=>intval($_GET['cl']),
'ag'=>intval($_GET['ag'])];
$rs = getDocuments(intval($_GET['td']), intval($_GET['si']), $where_data);

require("../../config/templates/report_template.php");
require("lang." . $admin_aziend['lang'] . ".php");

$titolo = $_GET['ti'];
$tipdoc = $_GET['td'];
if (isset($_GET['ag'])&&is_numeric($_GET['ag'])){
    $agente = gaz_dbi_get_row($gTables['agenti'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['agenti'] . ".id_fornitore = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id', $gTables['agenti'] . '.id_agente', intval($_GET['ag']));
    $titolo .= ($agente)? ' - '.$agente['ragso1']:'';
}
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data=$admin_aziend['citspe'].", lì ".ucwords($gazTimeFormatter->format(new DateTime()));
$title = array('luogo_data' => $luogo_data,
'title' => 'Vendite: '.$titolo.' dal '.gaz_format_date(substr($_GET['di'],0,4).'-'.substr($_GET['di'],4,2).'-'.substr($_GET['di'],6,2)).' al '.gaz_format_date(substr($_GET['df'],0,4).'-'.substr($_GET['df'],4,2).'-'.substr($_GET['df'],6,2)),
'hile' => array(
array('lun' => 16, 'nam' => 'Data'),
array('lun' => 14, 'nam' => 'Num.'),
array('lun' => 12, 'nam' => 'Tipo'),
array('lun' => 62, 'nam' => 'Cliente'),
array('lun' => 20, 'nam' => 'Impon.'),
array('lun' => 36, 'nam' => '%'),
array('lun' => 16, 'nam' => 'Iva'),
array('lun' => 20, 'nam' => 'Totale')));
$pdf = new Report_template();
$pdf->setVars($admin_aziend, $title);
$pdf->SetTopMargin(40);
$pdf->SetFooterMargin(18);
$config = new Config;
$pdf->AddPage('P', $config->getValue('page_format'));
$pdf->SetFont('helvetica', '', 8);
$tot_imponibile = 0.00;
$tot_iva = 0.00;
$tot_importo = 0.00;
foreach($rs as $row){
  if ( substr($row['tes']['tipdoc'],0,2) =='DD' ) {
    $row['tes']['datfat']= $row['tes']['datemi'];
    $row['tes']['numfat']= $row['tes']['numdoc'];
  }
  $pdf->Cell(16, 4, gaz_format_date($row['tes']['datfat']), 1, 0, 'C');
  $pdf->Cell(14, 4, $row['tes']['numfat'], 1, 0, 'C');
  $pdf->Cell(12, 4, $row['tes']['tipdoc'], 1, 0, 'C');
  $pdf->Cell(62, 4, $row['tes']['ragsoc'], 1, 0, 'L', false, '', 1);
  $first=true;
  foreach($row['vat'] as $r){
    $tot_iva+=$r['ivacast'];
    $tot_imponibile += $r['impcast'];
    if (!$first){
      $pdf->Cell(104,4);
    }
    $first=false;
    $pdf->Cell(20, 4, gaz_format_number($r['impcast']), 1, 0, 'R');
    $pdf->Cell(36, 4, $r['descriz'], 1, 0, 'C', false, '', 1);
    $pdf->Cell(16, 4, gaz_format_number($r['ivacast']), 1, 0, 'R');
    $pdf->Cell(20, 4, gaz_format_number($r['impcast'] + $r['ivacast']), 1, 1, 'R');
  }
  if ($first){
    $pdf->Cell(92, 4,'', 1, 1);
  }
}
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Ln(2);
$pdf->Cell(104, 5, 'Totali: ', 1, 0, 'R',1);
$pdf->Cell(20, 5, gaz_format_number($tot_imponibile), 1, 0, 'R', 1, '', 1);
$pdf->Cell(36, 5,'', 1, 0, 'R', false, '', 1);
$pdf->Cell(16, 5, gaz_format_number($tot_iva), 1, 0, 'R', 1, '', 1);
$pdf->Cell(20, 5, gaz_format_number($tot_imponibile + $tot_iva ), 1, 0, 'R', 1, '', 1);
$pdf->Output();
?>
