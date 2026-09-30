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

if (!ini_get('safe_mode')) { //se me lo posso permettere...
    ini_set('memory_limit', '128M');
    gaz_set_time_limit(0);
}
if (isset($_GET['date'])) { //se non mi viene passata la data
    $dt = substr($_GET['date'], 0, 10);
} else {
    $dt = date("Y-m-d");
}

function getAssets($date) {
    /*  funzione per riprendere dal database tutti i beni ammortizzabili
      e proporre una anteprima di ammortamenti */
    global $gTables, $admin_aziend;
    $ctrl_fix = 0;
    // riprendo i righi da assets
    $from = $gTables['assets'] . ' AS assets ' .
            'LEFT JOIN ' . $gTables['tesmov'] . ' AS tesmov ON assets.id_movcon=tesmov.id_tes ' .
            'LEFT JOIN ' . $gTables['clfoco'] . ' AS fornit ON tesmov.clfoco=fornit.codice ';
    $field = ' assets.*, tesmov.datreg AS dtrtes, tesmov.seziva, tesmov.numdoc AS nudtes, tesmov.datreg AS dtdtes, tesmov.descri AS destes, fornit.descri as desfor';
    $where = " datreg <= '" . $date . "'";
    $orderby = "acc_fixed_assets ASC, datreg ASC, type_mov ASC, id ASC";
    $result = gaz_dbi_dyn_query($field, $from, $where, $orderby);
    $acc = array();
    while ($row = gaz_dbi_fetch_array($result)) {
        // ad ogni cambio di bene creo un array e sulla radice metto tutti i dati che mi servono sulla intestazione del bene stesso
        $movcon = "AND id_tes = '{$row['id_movcon']}'";
        if ($ctrl_fix <> $row['acc_fixed_assets']) {
            // azzero i totali delle colonne
            // in ordine di data necessariamente il primo rigo dev'essere l'acquisto
            $acc[$row['acc_fixed_assets']][1] = $row;
            // prendo il valore della immobilizzazione dal rigo contabile
            $f = gaz_dbi_get_row($gTables['rigmoc'], 'codcon', $row['acc_fixed_assets'], $movcon);
            $acc[$row['acc_fixed_assets']][1]['fixed_val'] = $f['import'];
            $acc[$row['acc_fixed_assets']][1]['found_val'] = 0;
            $acc[$row['acc_fixed_assets']][1]['cost_val'] = 0;
            $acc[$row['acc_fixed_assets']][1]['noded_val'] = 0;
            $acc[$row['acc_fixed_assets']][1]['lost_cost'] = 0; // non è più fiscalmente una quota persa ma da segnalare sul libro
            // questi sono i totali
            $acc[$row['acc_fixed_assets']][1]['fixed_tot'] = $f['import'];
            $acc[$row['acc_fixed_assets']][1]['found_tot'] = 0;
            $acc[$row['acc_fixed_assets']][1]['cost_tot'] = 0;
            $acc[$row['acc_fixed_assets']][1]['noded_tot'] = 0;
            // i subtotali
            $acc[$row['acc_fixed_assets']][1]['fixed_subtot'] = $f['import'];
            $acc[$row['acc_fixed_assets']][1]['found_subtot'] = 0;
            $acc[$row['acc_fixed_assets']][1]['cost_subtot'] = 0;
            $acc[$row['acc_fixed_assets']][1]['noded_subtot'] = 0;

            // trovo i giorni dall'ultimo ammortamento o acquisto
            $dateamm = new DateTime($date);
            $rs_gglast = gaz_dbi_dyn_query("*", $gTables['tesmov'], "caucon = 'AMM'", 'datreg DESC', 0, 1);
            $r_gglast = gaz_dbi_fetch_array($rs_gglast);
            if ($r_gglast) {
                // dall'ultimo ammortamento
                $datelast = new DateTime($r_gglast['datreg']);
            } else {
                // dall'acquisto
                $datelast = new DateTime($row['dtrtes']);
            }
            $ddays = $dateamm->diff($datelast);
            $acc[$row['acc_fixed_assets']][1]['gglast'] = $ddays->days;
            // ricavo il gruppo e la specie dalla tabella ammortamenti ministeriali
            $xml = simplexml_load_file('../../library/include/ammortamenti_ministeriali.xml') or die("Error: Cannot create object");
            preg_match("/^([0-9 ]+)([a-zA-Z ]+)$/", $admin_aziend['amm_min'], $m);
            foreach ($xml->gruppo as $vg) {
                if ($vg->gn[0] == $m[1]) {
                    foreach ($vg->specie as $v) {
                        if ($v->ns[0] == $m[2]) {
                            $acc[$row['acc_fixed_assets']][1]['ammmin_gruppo'] = $vg->gn[0] . '-' . $vg->gd[0];
                            $acc[$row['acc_fixed_assets']][1]['ammmin_specie'] = $v->ns[0] . '-' . $v->ds[0];
                            $acc[$row['acc_fixed_assets']][1]['ammmin_ssd'] = $v->ssd[intval($row['ss_amm_min'])] . ' ';
                            $acc[$row['acc_fixed_assets']][1]['ammmin_ssrate'] = $v->ssrate[intval($row['ss_amm_min'])] . ' ';
                        }
                    }
                }
            }
        } else {
            //nei movimenti successivi a seconda del tipo di rigo agisco in maniera differente
            switch ($row['type_mov']) {
                case '10' : // incremento valore del bene (accessorio/ampliamento/ammodernamento/manutenzione)
                    // prendo il valore dell'incremento del costo storico dal rigo contabile
                    if (!isset($acc[$row['acc_fixed_assets']][1]['fixed_tot'])){
                      //var_dump($row);
                    }
                    $fx = gaz_dbi_get_row($gTables['rigmoc'], 'codcon', $row['acc_fixed_assets'], $movcon);
                    $acc[$row['acc_fixed_assets']][1]['fixed_tot'] += $fx['import'];
                    $row['fixed_subtot'] = $acc[$row['acc_fixed_assets']][1]['fixed_tot'];
                    $row['fixed_val'] = $fx['import'];
                    $row['found_val'] = 0;
                    $row['found_subtot'] = $acc[$row['acc_fixed_assets']][1]['found_tot'];
                    $row['cost_val'] = 0;
                    $row['cost_subtot'] = $acc[$row['acc_fixed_assets']][1]['cost_tot'];
                    $row['noded_val'] = 0;
                    $row['noded_subtot'] = $acc[$row['acc_fixed_assets']][1]['noded_tot'];
                    $row['lost_cost'] = 0;
                    $acc[$row['acc_fixed_assets']][] = $row;
                    break;
                case '50' : // decremento valore del bene per ammortamento
                    // prendo il valore del fondo ammortamento dal rigo contabile
                    $f = gaz_dbi_get_row($gTables['rigmoc'], 'codcon', $row['acc_found_assets'], $movcon);
                    $row['fixed_val'] = 0;
                    $row['fixed_subtot'] = $acc[$row['acc_fixed_assets']][1]['fixed_tot'];
                    $row['found_val'] = $f['import'];
                    $acc[$row['acc_fixed_assets']][1]['found_tot'] += $f['import'];
                    $row['found_subtot'] = $acc[$row['acc_fixed_assets']][1]['found_tot'];
                    // prendo il valore dell'ammortamento dal rigo contabile
                    $c = gaz_dbi_get_row($gTables['rigmoc'], 'codcon', $row['acc_cost_assets'], $movcon);
                    $row['cost_val'] = $c['import'];
                    $acc[$row['acc_fixed_assets']][1]['cost_tot'] += $c['import'];
                    $row['cost_subtot'] = $acc[$row['acc_fixed_assets']][1]['cost_tot'];
                    // prendo il valore della quota indeducibile dal rigo contabile
                    $n = gaz_dbi_get_row($gTables['rigmoc'], 'codcon', $row['acc_no_deduct_cost'], $movcon);
                    $row['noded_val'] = $n?$n['import']:0;
                    $acc[$row['acc_fixed_assets']][1]['noded_tot'] += $n?$n['import']:0;
                    $row['noded_subtot'] = $acc[$row['acc_fixed_assets']][1]['noded_tot'];
                    /* anche se da qualche anno non è più fiscalmente una quota persa si deve segnalare sul libro
                     */
                    $row['lost_cost'] = ($acc[$row['acc_fixed_assets']][1]['valamm'] * $row['fixed_subtot'] / 200) - ($c['import'] + ($n?$n['import']:0));
                    if ($row['lost_cost'] < 0) {
                        $row['lost_cost'] = 0;
                    }
                    // aggiungo all'array del bene
                    $acc[$row['acc_fixed_assets']][] = $row;
                    break;
                case '80' : // alienazione parziale
                    break;
                case '90' : // alienazione del bene
                    // prendo il valore del decremento del costo storico dal rigo contabile
                    $fx = gaz_dbi_get_row($gTables['rigmoc'], 'codcon', $row['acc_fixed_assets'], $movcon);
                    $acc[$row['acc_fixed_assets']][1]['fixed_tot'] -= $fx['import'];
                    $row['fixed_subtot'] = $acc[$row['acc_fixed_assets']][1]['fixed_tot'];
                    $row['fixed_val'] = $fx['import'];
                    $row['found_val'] = 0;
                    $row['found_subtot'] = $acc[$row['acc_fixed_assets']][1]['found_tot'];
                    $row['cost_val'] = 0;
                    $row['cost_subtot'] = $acc[$row['acc_fixed_assets']][1]['cost_tot'];
                    $row['noded_val'] = 0;
                    $row['noded_subtot'] = $acc[$row['acc_fixed_assets']][1]['noded_tot'];
                    $row['lost_cost'] = 0;
                    // prendo il valore del fondo ammortamento dal rigo contabile
                    $f = gaz_dbi_get_row($gTables['rigmoc'], 'codcon', $row['acc_found_assets'], $movcon);
                    $acc[$row['acc_fixed_assets']][1]['found_tot'] -= $f['import'];
                    $row['found_val'] = $f['import'];
                    $row['found_subtot'] = $acc[$row['acc_fixed_assets']][1]['found_tot'];
                    // aggiungo all'array del bene
                    $acc[$row['acc_fixed_assets']][] = $row;
                    break;
            }
        }
        $ctrl_fix = $row['acc_fixed_assets'];
    }
    return $acc;
}
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data = $admin_aziend['citspe'] . ", lì " . ucwords($gazTimeFormatter->format(new DateTime(substr($dt, 8, 2).'-'.substr($dt, 5, 2).'-'.substr($dt, 0, 4))));

require("../../config/templates/report_template.php");


$form['assets'] = getAssets($dt);
$title = array('luogo_data' => $luogo_data,
    'title' => 'LIBRO DEI CESPITI - BENI AMMORTIZZABILI',
    'hile' => array(array('lun' => 84, 'nam' => 'Descrizione bene'),
        array('lun' => 18, 'nam' => '%'),
        array('lun' => 28, 'nam' => 'Immobilizzazione'),
        array('lun' => 28, 'nam' => 'Fondo'),
        array('lun' => 28, 'nam' => 'Quota deducibile'),
        array('lun' => 28, 'nam' => 'Quota non deduc.'),
        array('lun' => 28, 'nam' => 'Residuo'),
        array('lun' => 28, 'nam' => 'Amm.<50%')
        ));
$pdf = new Report_template('L','mm','A4',true,'UTF-8',false,true);
$pdf->setVars($admin_aziend, $title);
$pdf->SetTopMargin(39);
$pdf->SetFooterMargin(20);
$pdf->AddPage('L');
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));

$head = true;
foreach ($form['assets'] as $ka => $va) {
    // ogni assets ha più righi-movimenti
    foreach ($va as $k => $v) {
        if ($head) {
            $pdf->Ln(4);
            $pdf->Cell(270, 4, "Gruppo: " . $v['ammmin_gruppo'] . " - Specie: " . $v['ammmin_specie'], 1, 1, 'L', 0, '', 2);
            $pdf->Ln(4);
            $pdf->SetFont('helvetica', '', 8);
            $head = false;
        }
        if ($v['type_mov'] == 1) {
            $pdf->MultiCell(84, 4, $v['descri'] . " (". $v["acc_fixed_assets"].")\n" . $v["desfor"] . ' Fatt.' . $v["nudtes"] . ' del ' . gaz_format_date($v['dtdtes'], false, true) . "\n" . $v['ammmin_ssd'] . "\n Ammortamento normale = " . $v['ammmin_ssrate'] . '%', 1, 'L', true, 2);
            $pdf->Ln(-4);
            $pdf->Cell(84, 4);
            $pdf->Cell(18, 4, '', 1, 0, 'C');
            $pdf->Cell(28, 4, gaz_format_number($v['fixed_val']), 1, 0, 'R');
            $pdf->Cell(28, 4, '', 1);
            $pdf->Cell(28, 4, '', 1);
            $pdf->Cell(28, 4, '', 1);
            $pdf->Cell(28, 4, gaz_format_number($v['fixed_val']), 1, 0, 'R');
            $pdf->Cell(28, 4, '', 1, 1);
        } elseif ($v['type_mov'] == 10) {
            $pdf->Cell(84, 4, gaz_format_date($v['dtdtes']) . ' INCREMENTATO IL VALORE DEL BENE CON:', 'LTR', 0, 'L', 0, '', 1);
            $pdf->Cell(18, 4, '', 'LTR', 0, 'C');
            $pdf->Cell(28, 4, '+' . gaz_format_number($v['fixed_val']), 'LTR', 0, 'L');
            $pdf->Cell(28, 4, '', 'LTR');
            $pdf->Cell(28, 4, '', 'LTR');
            $pdf->Cell(28, 4, '', 'LTR');
            $pdf->Cell(28, 4, '', 'LTR', 0, 'R');
            $pdf->Cell(28, 4, '', 'LTR', 1);
            $pdf->Cell(84, 4, $v['descri'], 'LBR', 0, 'L', 0, '', 1);
            $pdf->Cell(18, 4, '', 'LBR', 0, 'C');
            $pdf->Cell(28, 4, gaz_format_number($v['fixed_subtot']), 'LBR', 0, 'R');
            $pdf->Cell(28, 4, '', 'LBR');
            $pdf->Cell(28, 4, '', 'LBR');
            $pdf->Cell(28, 4, '', 'LBR');
            $pdf->Cell(28, 4, gaz_format_number($v['fixed_subtot'] - $v['found_subtot']), 'LBR', 0, 'R');
            $pdf->Cell(28, 4, '', 'LBR', 1);
        } elseif ($v['type_mov'] == 90) {
            $pdf->Cell(84, 4, gaz_format_date($v['dtdtes']) . ' VENDITA DEL BENE CON:', 'LTR', 0, 'L', 0, '', 1);
            $pdf->Cell(18, 4, '', 'LTR', 0, 'C');
            $pdf->Cell(28, 4, '-' . gaz_format_number($v['fixed_val']), 'LTR', 0, 'L');
            $pdf->Cell(28, 4, '-' . gaz_format_number($v['found_val']), 'LTR', 0, 'L');
            $pdf->Cell(28, 4, '', 'LTR');
            $pdf->Cell(28, 4, '', 'LTR');
            $pdf->Cell(28, 4, '', 'LTR', 0, 'R');
            $pdf->Cell(28, 4, '', 'LTR', 1);
            $pdf->Cell(84, 4, 'FATTURA ' . $v["nudtes"] . '/' . $v["seziva"] . ' del ' . gaz_format_date($v['dtdtes']), 'LBR', 0, 'L', 0, '', 1);
            $pdf->Cell(18, 4, '', 'LBR', 0, 'C');
            $pdf->Cell(28, 4, gaz_format_number($v['fixed_subtot']), 'LBR', 0, 'R');
            $pdf->Cell(28, 4, gaz_format_number($v['found_subtot']), 'LBR', 0, 'R');
            $pdf->Cell(28, 4, '', 'LBR');
            $pdf->Cell(28, 4, '', 'LBR');
            $pdf->Cell(28, 4, gaz_format_number($v['fixed_subtot'] - $v['found_subtot']), 'LBR', 0, 'R');
            $pdf->Cell(28, 4, '', 'LBR', 1);
            // trovo la eventuale plus/minusvalenza
            $loss_gains = gaz_dbi_get_row($gTables['rigmoc'], 'codcon', $admin_aziend['capital_gains_account'], "AND id_tes = '{$v['id_movcon']}'");
            $loss_gains_descri = gaz_format_number($v['fixed_val'] - $v['found_val'] + $loss_gains['import']) . '  REALIZZANDO UNA PLUSVALENZA DI € ';
            if (!$loss_gains) {
                $loss_gains = gaz_dbi_get_row($gTables['rigmoc'], 'codcon', $admin_aziend['capital_loss_account'], "AND id_tes = '{$v['id_movcon']}'");
                $loss_gains_descri = gaz_format_number($v['fixed_val'] - $v['found_val'] - $loss_gains['import']) . ' ACCUSANDO UNA MINUSVALENZA DI € ';
            }
            $pdf->Cell(84, 4, '    ##########    B E N E    A L I E N A T O    #########', 1, 0, 'L', 1);
            $pdf->Cell(186, 4, ' VENDUTO AL PREZZO DI € ' . $loss_gains_descri . gaz_format_number($loss_gains['import']), 'LBR', 1, 'L', 0);
        } else {
            $pdf->Cell(84, 4, gaz_format_date($v['dtdtes']) . ' ' . $v['descri'], 1, 0, 'L', 0, '', 1);
            $pdf->Cell(18, 4, gaz_format_number($v['valamm']), 1, 0, 'C');
            $pdf->Cell(28, 4, gaz_format_number($v['fixed_subtot']), 1, 0, 'R');
            $pdf->Cell(28, 4, gaz_format_number($v['found_subtot']), 1, 0, 'R');
            $pdf->Cell(28, 4, gaz_format_number($v['cost_val']), 1, 0, 'R');
            $pdf->Cell(28, 4, gaz_format_number($v['noded_val']), 1, 0, 'R');
            $pdf->Cell(28, 4, gaz_format_number($v['fixed_subtot'] - $v['found_subtot']), 1, 0, 'R');
            $pdf->Cell(28, 4, gaz_format_number($v['lost_cost']), 1, 1, 'R');
        }
    }
	$residuo = $v['fixed_subtot'] - $v['found_subtot'];
	if($va[1]['super_ammort']>=0.1 && $residuo >= 0.01) { // ho un super_ammortamento da utilizzare in sede di dichiarazione dei redditi
		$pdf->Cell(270, 4, 'SUPERAMMORTAMENTO AL '.gaz_format_number(100+$va[1]['super_ammort']).'% - In sede di dichiarazione potranno essere portate in diminuzione della base imponibile € '.gaz_format_number($v['cost_val']*$va[1]['super_ammort']/100), 'LBR', 1, 'C');
	}
    $pdf->Ln(4);
}
$pdf->Output();
?>
