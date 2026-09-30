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
$msg = array('err' => array(), 'war' => array());

function suggestAmm($fixed, $found, $valamm, $no_deduct_cost_rate, $days) {
    if ($fixed < 0.01) {
        return false;
    }
    if ($days >= 360) { // ignoro i valori se maggiori o vicino ad un anno
        $days = 365;
    }
    $trunk = false;
    $vy = $fixed / 365 * $days;
    $vy = $vy * $valamm / 100;
    if ($vy >= ($fixed - $found)) {
        // se l'ammortamento supera il resido lo tronco
        $vy = $fixed - $found;
        $trunk = round($vy / $fixed * 100, 2);
    }
    $vn = $vy; //$vn contiene la quota annua
    $vy = round($vy - ($vy * $no_deduct_cost_rate / 100), 2);
    $vn = round($vn - $vy, 2);
    if ($days < 364) { // dovrò proporzionare anche il valore percentuale dell'amortamento proposto
        $trunk = round($valamm / 365 * $days, 2);
    }
    return array($vy, $vn, $trunk);
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
                    $row['noded_val'] = $n['import'];
                    $acc[$row['acc_fixed_assets']][1]['noded_tot'] += $n['import'];
                    $row['noded_subtot'] = $acc[$row['acc_fixed_assets']][1]['noded_tot'];
                    /* anche se da qualche anno non è più fiscalmente una quota persa si deve segnalare sul libro
                     */
                    $row['lost_cost'] = ($acc[$row['acc_fixed_assets']][1]['valamm'] * $row['fixed_subtot'] / 200) - ($c['import'] + $n['import']);
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

if (isset($_POST['ritorno'])) { // accessi successivi
    $form['ritorno'] = filter_input(INPUT_POST, 'ritorno');
    $form['datreg'] = filter_input(INPUT_POST, 'datreg');
    if (!gaz_format_date($form["datreg"], 'chk')) {
        $msg['err'][] = "datreg";
    }
    if (isset($_POST['assets'])) {
        $form['assets'] = filter_input(INPUT_POST, 'assets');
    }
    $form['datreg'] = gaz_format_date($form['datreg'], true);
    $form['assets'] = getAssets($form['datreg']);
    // eventualmente sostituisco le quote con quelle postate
    if (isset($_POST['assets']) && count($form['assets']) > 0) {
        $ctrl_first = true;
        foreach ($_POST['assets'] as $k => $v) {
            if (isset($_POST['insert'])) {
                $form['assets'][$k]['cost_suggest'] = floatval($v['cost_suggest']);
                $form['assets'][$k]['noded_suggest'] = floatval($v['noded_suggest']);
                $form['assets'][$k]['valamm_suggest'] = floatval($v['valamm_suggest']);
                if ($ctrl_first) {
                    // inserisco la testata del movimento contabile unica per tutti i righi
                    $form['caucon'] = 'AMM';
                    $form['descri'] = 'RILEVATE QUOTE AMMORTAMENTO ANNO ' . substr($form['datreg'], 0, 4);
                    gaz_dbi_table_insert('tesmov', $form);
                    $id_tesmov = gaz_dbi_last_id();
                    $form['id_tes'] = $id_tesmov;
                    $ctrl_first = false;
                }
                // inserisco i righi del movimento contabile
                $form['codcon'] = $form['assets'][$k][1]['acc_found_assets'];
                $form['darave'] = 'A';
                $form['import'] = round($form['assets'][$k]['cost_suggest'] + $form['assets'][$k]['noded_suggest'], 2);
                gaz_dbi_table_insert('rigmoc', $form);
                $form['codcon'] = $form['assets'][$k][1]['acc_cost_assets'];
                $form['darave'] = 'D';
                $form['import'] = $form['assets'][$k]['cost_suggest'];
                gaz_dbi_table_insert('rigmoc', $form);
                if ($form['assets'][$k]['noded_suggest'] >= 0.01) { // se ho valorizzato un costo indeducibile
                    $form['codcon'] = $form['assets'][$k][1]['acc_no_deduct_cost'];
                    $form['darave'] = 'D';
                    $form['import'] = $form['assets'][$k]['noded_suggest'];
                    gaz_dbi_table_insert('rigmoc', $form);
                }
                // inserisco il movimento sul libro cespiti
                $form['id_movcon'] = $id_tesmov;
                $form['type_mov'] = 50;
                $form['descri'] = 'AMMORTAMENTO (QUOTA ANNO ' . substr($form['datreg'], 0, 4) . ')';
                $form['a_value'] = $form['import'];
                $form['valamm'] = $form['assets'][$k]['valamm_suggest'];
                $form['acc_fixed_assets'] = $form['assets'][$k][1]['acc_fixed_assets'];
                $form['acc_found_assets'] = $form['assets'][$k][1]['acc_found_assets'];
                $form['acc_cost_assets'] = $form['assets'][$k][1]['acc_cost_assets'];
                $form['acc_no_deduct_cost'] = $form['assets'][$k][1]['acc_no_deduct_cost'];
                $form['no_deduct_cost_rate'] = $form['assets'][$k][1]['no_deduct_cost_rate'];
                gaz_dbi_table_insert('assets', $form);
            }
        }
    }
    if (isset($_POST['insert'])) {
        header("Location: ./report_assets.php");
        exit;
    }
    // riporto datreg al valore postato
    $form['datreg'] = filter_input(INPUT_POST, 'datreg');
} else { // al primo accesso
    $form['ritorno'] = filter_input(INPUT_SERVER, 'HTTP_REFERER');
    // consiglio una data
    $rs_datlast = gaz_dbi_dyn_query("*", $gTables['tesmov'], "caucon = 'AMM'", 'datreg DESC', 0, 1);
    $r_datlast = gaz_dbi_fetch_array($rs_datlast);
    $adesso = new DateTime();
    if ($r_datlast) {
        // data ultimo ammortamento
        $datelast = new DateTime($r_datlast['datreg']);
    } else {
        // mai fatto ammortamenti
        $msg['war'][] = 'noamm';
        $datelast = new DateTime();
        $datelast->modify('previous year');
        $datelast->format('31/12/Y');
    }
    $interv = $adesso->diff($datelast);
    if ($interv->days <= 365) {
        $msg['err'][] = 'datreg';
    }
    $adesso->modify('previous year');
    $adesso->modify('last day of december');
    $form['datreg'] = $adesso->format('d/m/Y');
    $form['assets'] = getAssets(gaz_format_date($form['datreg'], true));
}

require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<script>
    $(function () {
        $("#datreg").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
        $("#datreg").change(function () {
            this.form.submit();
        });
        $('.gaz-tooltip').tooltip({html: true, placement: 'auto bottom', delay: {show: 50}});
    });
    // ricalcolo i valori in caso di cambiamenti sugli importi
    $(document).ready(function () {
        $('table tbody tr td [orivalamm]').change(function () {
            var lc = 0;
            var fix = $(this).attr('name').match(/assets\[([0-9]+)\]\[[a-zA-Z_ ]+\]/i)[1];
            // e quelli modificabili dall'utente
            var ovala = $(this).val() * 1;
            var perc = $('.container-fluid input[name="' + fix + '_ammperc"]').val() * 1;
            var found = $('.container-fluid input[name="' + fix + '_ammfound"]').val() * 1;
            var fixed = $('.container-fluid input[name="' + fix + '_ammfixed"]').val() * 1;
            var noded = $('.container-fluid input[name="' + fix + '_nodedrate"]').val() * 1;
            var residuo = fixed - found;
            // calcolo i nuovi valori
            var nv = Math.round(ovala * fixed) / 100;
            if (residuo < nv) {
                // se non ho abbastanza residuo forzo ai valori possibili
                nv = residuo;
                var newperc = residuo / fixed * 100;
                alert('Ammortamento ridotto al valore residuo');
                $(this).val(newperc.toFixed(2));
            } else if (ovala < perc / 2) { // ho un costo <50% da segnalare
                lc = Math.round((perc / 2 - ovala) * fixed) / 100;
                alert('Ammortamento inferiore al 50% di quello ministeriale');

            }
            var ndv = Math.round(nv * noded) / 100;
            var dv = Math.round((nv - ndv) * 100) / 100;
            $('table tbody tr td input[name="assets[' + fix + '][cost_suggest]"]').val(dv.toFixed(2));
            $('table tbody tr td input[name="assets[' + fix + '][noded_suggest]"]').val(ndv.toFixed(2));
            $('table tbody tr td span[name="' + fix + '_lostcost"]').html(lc.toFixed(2));
        });
    });
</script>
<?php
$gForm = new GAzieForm();
if (count($msg['err']) > 0) { // ho un errore
    $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
if (count($msg['war']) > 0) { // ho un warning
    $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
}
?>
<form class="form-horizontal" role="form" method="post" id="gaz-form" name="form">
    <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno">
    <div class="panel panel-default">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="datreg" class="col-sm-6 control-label"><?php echo $script_transl['title'] . $script_transl['datreg']; ?></label>
                        <input type="text" class="col-sm-2" id="datreg" name="datreg" value="<?php echo $form['datreg']; ?>">
                        <a class="btn btn-large btn-custom col-sm-4" href="assets_book.php?date=<?php echo gaz_format_date($form['datreg'], true); ?>"><?php echo $script_transl['book']; ?></a>
                    </div>
                </div>
            </div><!-- chiude row  -->
            <?php
            $head = true;
            foreach ($form['assets'] as $ka => $va) {
                $r = array();
                // ogni assets ha più righi-movimenti
                foreach ($va as $k => $v) {
                    if ($head) {
                        ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="ammmin_gruppo" class="col-sm-6 control-label"><?php echo $v['ammmin_gruppo'] . $v['ammmin_specie']; ?></label>
                                    <span class="col-sm-6" > <?php echo $v['ammmin_specie']; ?></span>
                                </div>
                            </div>
                        </div><!-- chiude row  -->
                    </div>
                </div><!-- chiude panel panel-default  -->
                <?php
                $head = false;
            }

            if ($v['type_mov'] == 1) {
                ?>

                <input type="hidden" name="<?php echo $ka . '_ammperc'; ?>" value="<?php echo $v['valamm']; ?>" />
                <input type="hidden" name="<?php echo $ka . '_ammfound'; ?>" value="<?php echo $v['found_tot']; ?>" />
                <input type="hidden" name="<?php echo $ka . '_ammfixed'; ?>" value="<?php echo $v['fixed_tot']; ?>" />
                <input type="hidden" name="<?php echo $ka . '_nodedrate'; ?>" value="<?php echo $v['no_deduct_cost_rate']; ?>" />

                <?php
                $r[0] = [array('head' => $script_transl["asset_des"], 'class' => '', 'value' => '<b>' . $v['descri'] . $script_transl["clfoco"] . $v["desfor"] . $script_transl["movdes"] . $v["nudtes"] . ' - ' . gaz_format_date($v['dtdtes'], false, true) . '</b><br>' . $script_transl['ammmin_ssd'] . ': ' . $v['ammmin_ssd'] . '<br /> Ammortamento normale = ' . $v['ammmin_ssrate'] . '%'),
                    array('head' => '%', 'class' => 'text-center', 'value' => gaz_format_number($v['valamm'])),
                    array('head' => $script_transl["fixed_val"], 'class' => 'text-right',
                        'value' => gaz_format_number($v['fixed_val'])),
                    array('head' => $script_transl["found_val"], 'class' => 'text-right', 'value' => ''),
                    array('head' => $script_transl["cost_val"], 'class' => 'text-right', 'value' => ''),
                    array('head' => $script_transl["noded_val"], 'class' => 'text-right', 'value' => ''),
                    array('head' => $script_transl["rest_val"], 'class' => 'text-right', 'value' => gaz_format_number($v['fixed_val'])),
                    array('head' => $script_transl["lost_cost"], 'class' => 'text-center', 'value' => ''),
                ];
            } elseif ($v['type_mov'] == 10) { // se è un incremento di valore del bene visualizzo il valore del rigo  anzichè il subtotale
                $r[] = [array('head' => $script_transl["asset_des"], 'class' => '',
                'value' => gaz_format_date($v['dtdtes']) . ' ' . $v['descri']),
                    array('head' => '', 'class' => 'text-center', 'value' => ''),
                    array('head' => $script_transl["fixed_val"], 'class' => 'text-left bg-info',
                        'value' => '+' . gaz_format_number($v['fixed_val']) . '=' . gaz_format_number($v['fixed_subtot'])),
                    array('head' => $script_transl["found_val"], 'class' => 'text-right', 'value' => gaz_format_number($v['found_subtot'])),
                    array('head' => $script_transl["cost_val"], 'class' => 'text-right', 'value' => ''),
                    array('head' => $script_transl["noded_val"], 'class' => 'text-right', 'value' => ''),
                    array('head' => $script_transl["rest_val"], 'class' => 'text-right', 'value' => gaz_format_number($v['fixed_subtot'] - $v['found_subtot'])),
                    array('head' => $script_transl["lost_cost"], 'class' => 'text-center', 'value' => ''),
                ];
            } elseif ($v['type_mov'] == 90) { // se è un decremento di valore del bene per alienazione
                $r[] = [array('head' => $script_transl["asset_des"], 'class' => 'bg-danger',
                'value' => gaz_format_date($v['dtdtes']) . ' ' . $v['descri']),
                    array('head' => '', 'class' => 'text-center bg-danger', 'value' => ''),
                    array('head' => $script_transl["fixed_val"], 'class' => 'text-left bg-danger',
                        'value' => '-' . gaz_format_number($v['fixed_val']) . ' = ' . gaz_format_number($v['fixed_subtot'])),
                    array('head' => $script_transl["found_val"], 'class' => 'text-right bg-danger', 'value' => '-' . gaz_format_number($v['found_val']) . ' = ' . gaz_format_number($v['found_subtot'])),
                    array('head' => $script_transl["cost_val"], 'class' => 'text-right bg-danger', 'value' => ''),
                    array('head' => $script_transl["noded_val"], 'class' => 'text-right bg-danger', 'value' => ''),
                    array('head' => $script_transl["rest_val"], 'class' => 'text-right bg-danger', 'value' => gaz_format_number($v['fixed_subtot'] - $v['found_subtot'])),
                    array('head' => $script_transl["lost_cost"], 'class' => 'text-center bg-danger', 'value' => ''),
                ];
            } else {
                $r[] = [array('head' => $script_transl["asset_des"], 'class' => '',
                'value' => gaz_format_date($v['dtdtes']) . ' ' . $v['descri']),
                    array('head' => '%', 'class' => 'text-center', 'value' => gaz_format_number($v['valamm'])),
                    array('head' => $script_transl["fixed_val"], 'class' => 'text-right',
                        'value' => gaz_format_number($v['fixed_subtot'])),
                    array('head' => $script_transl["found_val"], 'class' => 'text-right', 'value' => gaz_format_number($v['found_subtot'])),
                    array('head' => $script_transl["cost_val"], 'class' => 'text-right', 'value' => gaz_format_number($v['cost_val'])),
                    array('head' => $script_transl["noded_val"], 'class' => 'text-right', 'value' => gaz_format_number($v['noded_val'])),
                    array('head' => $script_transl["rest_val"], 'class' => 'text-right', 'value' => gaz_format_number($v['fixed_subtot'] - $v['found_subtot'])),
                    array('head' => $script_transl["lost_cost"], 'class' => 'text-center', 'value' => gaz_format_number($v['lost_cost'])),
                ];
            }
        }
        // questo è il rigo di input alla fine della tabella di ogni cespite
        // calcolo una proposta d'ammortamento
        $suggest = suggestAmm($v['fixed_subtot'], $v['found_subtot'], $va[1]['valamm'], $va[1]['no_deduct_cost_rate'], $va[1]['gglast']);
        $disabl = '';
        if (!$suggest) {
            // se è stato alienato
            $script_transl["suggest_amm"] = $script_transl["sold_suggest_amm"];
            $disabl = ' disabled ';
            $v['valamm'] = 0.00;
        } elseif ($suggest[2]) {
            // se è stata troncata la percentuale...
            $v['valamm'] = $suggest[2];
        } elseif ($suggest[0] < 0.01) {
            $v['valamm'] = 0.00;
            $disabl = ' disabled ';
            $script_transl["suggest_amm"] = $script_transl["no_suggest_amm"];
        } else {
            $v['valamm'] = $va[1]['valamm'];
        }
        // ma prima controllo se ho fatto ammortamenti successivi a questa data
        // consiglio una data
        $rs_amm = gaz_dbi_dyn_query("*", $gTables['rigmoc'] . ' AS rig LEFT JOIN ' .
                $gTables['tesmov'] . ' AS tes ON rig.id_tes=tes.id_tes ', "tes.caucon = 'AMM' AND tes.datreg >='" . gaz_format_date($form['datreg'], true) . "' AND rig.codcon=" . $va[1]['acc_found_assets'], 'datreg DESC', 0);
        $r_amm = gaz_dbi_fetch_array($rs_amm);
        if ($r_amm) {
            // ho degli ammortamenti successivi, non posso farne altri
            $r[] = [array('head' => $script_transl["err"]['ammsuc'], 'class' => 'bg-danger',
            'value' => $script_transl["err"]['ammsuc']),
                array('head' => '', 'class' => 'text-right numeric bg-warning',
                    'value' => ''),
                array('head' => '', 'class' => 'text-right bg-warning',
                    'value' => ''),
                array('head' => '', 'class' => 'text-center bg-warning', 'value' => ''),
                array('head' => '', 'class' => 'text-right numeric bg-warning',
                    'value' => ''),
                array('head' => '', 'class' => 'text-right numeric bg-warning',
                    'value' => ''),
                array('head' => '', 'class' => 'text-right bg-warning', 'value' => ''),
                array('head' => '', 'class' => 'text-center bg-warning', 'value' => ''),
            ];
        } else {
            // rigo proposta ammortamento
            $r[] = [array('head' => $script_transl["suggest_amm"] . ' %', 'class' => 'text-right bg-warning',
            'value' => $script_transl["suggest_amm"] . ' %'),
                array('head' => '%', 'class' => 'text-right numeric bg-warning',
                    'value' => '<input ' . $disabl . ' type="number" step="0.01" max="' . $va[1]['valamm'] . '" min="0" name="assets[' . $ka . '][valamm_suggest]" orivalamm="' . $v['valamm'] . '" value="' . $v['valamm'] . '" maxlength="10" />'),
                array('head' => $script_transl["fixed_val"], 'class' => 'text-right bg-warning',
                    'value' => ''),
                array('head' => '', 'class' => 'text-center bg-warning', 'value' => ''),
                array('head' => $script_transl["cost_val"], 'class' => 'text-right numeric bg-warning',
                    'value' => '<input ' . $disabl . ' type="number" step="0.01" min="0" name="assets[' . $ka . '][cost_suggest]" value="' . $suggest[0] . '" maxlength="15" />'),
                array('head' => $script_transl["noded_val"], 'class' => 'text-right numeric bg-warning',
                    'value' => '<input ' . $disabl . ' type="number" step="0.01" min="0" name="assets[' . $ka . '][noded_suggest]" value="' . $suggest[1] . '" maxlength="15" />'),
                array('head' => '', 'class' => 'text-right bg-warning', 'value' => ''),
                array('head' => '', 'class' => 'text-center bg-warning', 'value' => '<span name="' . $ka . '_lostcost"></span>'),
            ];
        }
        $gForm->gazResponsiveTable($r, 'gaz-responsive-table');
    }
    if ($head) {
        ?>
    </div>
    </div><!-- chiude panel panel-default  -->
<?php }
?>
<div class="panel panel-info">
    <div class="container-fluid">
        <div class="col-sm-12 text-right alert-success">
            <div class="form-group">
                <div>
                    <input class="btn-danger" name="insert" type="submit" value="<?php echo ucfirst($script_transl['submit']); ?>">
                </div>
            </div>
        </div> <!-- chiude row  -->
    </div><!-- chiude container  -->
</div><!-- chiude panel  -->
</form>
<?php
require("../../library/include/footer.php");
?>
