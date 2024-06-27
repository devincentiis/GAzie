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
  scriva   alla   Free  Software Foundation,  Inc.,   59
  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
require("../../library/include/datlib.inc.php");

require("../../library/include/agenzia_entrate.inc.php");
$admin_aziend = checkAdmin();
$anno = date("Y");
$msg = "";
$codice_carica = array(0 => 0, 16 => 1, 17 => 2, 18 => 4, 19 => 5, 20 => 6, 21 => 7, 22 => 8, 23 => 9);
if (!isset($_POST['ritorno'])) { //al primo accesso allo script
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];

    $_POST['annimp'] = $anno - 1;
    $_POST['codfis'] = $admin_aziend['codfis'];
    $_POST['ragsoc'] = '';
    $_POST['cognom'] = '';
    $_POST['nome'] = '';
    if ($admin_aziend['sexper'] == 'G') {
        $_POST['ragsoc'] = strtoupper($admin_aziend['ragso1'] . " " . $admin_aziend['ragso2']);
    } else {
        $line = strtoupper($admin_aziend['ragso1'] . " " . $admin_aziend['ragso2']);
        $nuova = explode(' ', chop($line));
        $lenght = count($nuova);
        $middle = intval(($lenght + 1) / 2);
        for ($i = 0; $i < $lenght; $i++) {
            if ($i < $middle) {
                $_POST['cognom'] .= $nuova[$i] . " ";
            } else {
                $_POST['nome'] .= $nuova[$i] . " ";
            }
        }
    }
    $_POST['pariva'] = $admin_aziend['pariva'];
    $_POST['codatt'] = $admin_aziend['cod_ateco'];
    $_POST['cfdich'] = '';
    $_POST['cfcont'] = '';
    $_POST['codcar'] = '';
    $_POST['oroimp'] = '';
    $_POST['oroiva'] = '';
    $_POST['rotimp'] = '';
    $_POST['rotiva'] = '';
}

if (isset($_POST['Return'])) {
    header("Location:docume_finean.php");
    exit;
} elseif (isset($_POST['Insert'])) {
    require("../../library/include/check.inc.php");
    $nuw = new check_VATno_TAXcode();
    $form['codfis'] = strtoupper($_POST['codfis']);
    if (strlen(trim($form['codfis'])) <= 11) {
        $resultcf = $nuw->check_VAT_reg_no($form['codfis']);
    } else {
        $resultcf = $nuw->check_TAXcode($form['codfis']);
    }
    if (!empty($resultcf) or empty($form['codfis'])) {
        $msg .= "1-2+";
    }
    if (empty($_POST['ragsoc'])) {
        $form['ragsoc'] = '';
        $form['cognom'] = $_POST['cognom'];
        $form['nome'] = $_POST['nome'];
    } else {
        $form['ragsoc'] = $_POST['ragsoc'];
        $form['cognom'] = '';
        $form['nome'] = '';
    }
    $form['pariva'] = substr($_POST['pariva'], 0, 11);
    $resultcf = $nuw->check_VAT_reg_no($form['pariva']);
    if (!empty($resultcf) or empty($form['pariva'])) {
        $msg .= "7-8+";
    }
    $form['annimp'] = intval($_POST['annimp']);
    $form['totatt'] = intval($_POST['totatt']);
    $form['attnim'] = intval($_POST['attnim']);
    $form['attese'] = intval($_POST['attese']);
    $form['attint'] = intval($_POST['attint']);
    $form['attben'] = intval($_POST['attben']);
    if ($form['totatt'] < ($form['attnim'] + $form['attese'] + $form['attint'])) {
        $msg .= "26+";
    }
    $form['totpas'] = intval($_POST['totpas']);
    $form['pasnim'] = intval($_POST['pasnim']);
    $form['pasese'] = intval($_POST['pasese']);
    $form['pasint'] = intval($_POST['pasint']);
    $form['pasben'] = intval($_POST['pasben']);
    if ($form['totpas'] < ($form['pasnim'] + $form['pasese'] + $form['pasint'])) {
        $msg .= "31+";
    }
    $form['ivaatt'] = intval($_POST['ivaatt']);
    $form['ivapas'] = intval($_POST['ivapas']);
    if (isset($_POST['cont_sepa'])) {
        $form['cont_sepa'] = ' checked="on" ';
        $cont = 1;
    } else {
        $form['cont_sepa'] = "";
        $cont = 0;
    }
    if (isset($_POST['soci_grup'])) {
        $form['soci_grup'] = ' checked="on" ';
        $sogr = 1;
    } else {
        $form['soci_grup'] = "";
        $sogr = 0;
    }
    if (isset($_POST['even_ecce'])) {
        $form['even_ecce'] = ' checked="on" ';
        $even = 1;
    } else {
        $form['even_ecce'] = "";
        $even = 0;
    }
    $form['cfdich'] = substr($_POST['cfdich'], 0, 11);
    $resultcf = $nuw->check_VAT_reg_no($form['cfdich']);
    if (!empty($resultcf)) {
        $msg .= "13+";
    }
    $form['cfcont'] = $_POST['cfcont'];
    $resultcf = $nuw->check_TAXcode($form['cfcont']);
    if (!empty($resultcf)) {
        $msg .= "14+";
    }
    $form['codcar'] = intval($_POST['codcar']);
    $form['codatt'] = $_POST['codatt'];
    $form['oroimp'] = $_POST['oroimp'];
    $form['oroiva'] = $_POST['oroiva'];
    $form['rotimp'] = $_POST['rotimp'];
    $form['rotiva'] = $_POST['rotiva'];
    if ($form['ivaatt'] > $form['ivapas']) {
        $ivadebit = round($form['ivaatt']) - round($form['ivapas']);
        $ivacredit = "0";
    } elseif ($form['ivaatt'] < $form['ivapas']) {
        $ivadebit = "0";
        $ivacredit = round($form['ivapas']) - round($form['ivaatt']);
    } else {
        $ivadebit = "0";
        $ivacredit = "0";
    }
    //eseguo i controlli formali
    if (empty($msg)) { //non ci sono errori formali -> mando l'output al browser per il download del file
        $year = intval(substr($form['annimp'], -2));
        $A = array("IVC", $year, $form['codfis']);
        $B = array($form['codfis'], $form['pariva'], $form['ragsoc'], $form['cognom'], $form['nome'], $form['annimp'],
            $form['pariva'], $form['codatt'], $cont, $sogr, $even, $form['cfcont'],
            $form['codcar'], $form['cfdich'], $form['totatt'], $form['attnim'], $form['attese'], $form['attint'], $form['attben'],
            $form['totpas'], $form['pasnim'], $form['pasese'], $form['pasint'], $form['pasben'], $form['oroimp'], $form['oroiva'],
            $form['rotimp'], $form['rotiva'], $form['ivaatt'], $form['ivapas'], $ivadebit, $ivacredit);
        // Impostazione degli header per l'opozione "save as" dello standard input che verr� generato
        header('Content-Type: text/x-ivc');
        header("Content-Disposition: attachment; filename=" . $form['codfis'] . "_" . date("y") . '_' . $A[0] . "10.ivc");
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // per poter ripetere l'operazione di back-up pi� volte.
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        } else {
            header('Pragma: no-cache');
        }
        $agenzia = new AgenziaEntrate;
        $content = $agenzia->creaFileIVC($A, $B);
        print $content;
        exit;
    }
} else { //in tutti gli altri casi ricalcolo i dati in base all'anno d'imposta
    $form['codfis'] = strtoupper($_POST['codfis']);
    $form['ragsoc'] = $_POST['ragsoc'];
    $form['cognom'] = $_POST['cognom'];
    $form['nome'] = $_POST['nome'];
    $form['pariva'] = $_POST['pariva'];
    $form['annimp'] = $_POST['annimp'];
    //recupero i dati iva da DB
    $where = $gTables['tesmov'] . ".datreg LIKE '" . $form['annimp'] . "%' GROUP BY regiva, operat, tipiva";
    $rs_rigiva = gaz_dbi_dyn_query($gTables['tesmov'] . ".regiva, " . $gTables['tesmov'] . ".operat, " . $gTables['rigmoi'] . ".tipiva, SUM(" . $gTables['rigmoi'] . ".imponi) AS imponibile, SUM(" . $gTables['rigmoi'] . ".impost) AS imposta", $gTables['rigmoi'] . " left join " . $gTables['tesmov'] . " on " . $gTables['rigmoi'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes", $where);
    $form['totatt'] = 0;
    $form['attnim'] = 0;
    $form['attese'] = 0;
    $form['attint'] = 0;
    $form['attben'] = 0;
    $form['totpas'] = 0;
    $form['pasnim'] = 0;
    $form['pasese'] = 0;
    $form['pasint'] = 0;
    $form['pasben'] = 0;
    $form['ivaatt'] = 0;
    $form['ivapas'] = 0;
    while ($rs = gaz_dbi_fetch_array($rs_rigiva)) {
        if ($rs['regiva'] <= 5) { //registri iva vendite
            if ($rs['operat'] == 1) {       //in caso di operazione somma su registro
                $form['totatt'] += $rs['imponibile'];
                $form['ivaatt'] += $rs['imposta'];
                if ($rs['tipiva'] == 'E') {
                    $form['attese'] += $rs['imponibile'];
                } elseif ($rs['tipiva'] == 'N') {
                    $form['attnim'] += $rs['imponibile'];
                }
            } elseif ($rs['operat'] == 2) { //in caso di operazione sottrazione su registro
                $form['totatt'] -= $rs['imponibile'];
                $form['ivaatt'] -= $rs['imposta'];
                if ($rs['tipiva'] == 'E') {
                    $form['attese'] -= $rs['imponibile'];
                } elseif ($rs['tipiva'] == 'N') {
                    $form['attnim'] -= $rs['imponibile'];
                }
            }
        } elseif ($rs['regiva'] == 6) {                 //registri iva acquisti
            if ($rs['operat'] == 1) {       //in caso di operazione somma su registro
                $form['totpas'] += $rs['imponibile'];
                if ($rs['tipiva'] != 'D' || $rs['tipiva'] != 'T') {
                    $form['ivapas'] += $rs['imposta'];
                }
                if ($rs['tipiva'] == 'E') {
                    $form['pasese'] += $rs['imponibile'];
                } elseif ($rs['tipiva'] == 'N') {
                    $form['pasnim'] += $rs['imponibile'];
                }
            } elseif ($rs['operat'] == 2) { //in caso di operazione sottrazione su registro
                $form['totpas'] -= $rs['imponibile'];
                if ($rs['tipiva'] != 'D' || $rs['tipiva'] != 'T') {
                    $form['ivapas'] -= $rs['imposta'];
                }
                if ($rs['tipiva'] == 'E') {
                    $form['pasese'] -= $rs['imponibile'];
                } elseif ($rs['tipiva'] == 'N') {
                    $form['pasnim'] -= $rs['imponibile'];
                }
            }
        }
    }
    if (isset($_POST['cont_sepa'])) {
        $form['cont_sepa'] = ' checked="on" ';
    } else {
        $form['cont_sepa'] = "";
    }
    if (isset($_POST['soci_grup'])) {
        $form['soci_grup'] = ' checked="on" ';
    } else {
        $form['soci_grup'] = "";
    }
    if (isset($_POST['even_ecce'])) {
        $form['even_ecce'] = ' checked="on" ';
    } else {
        $form['even_ecce'] = "";
    }
    $form['cfdich'] = $_POST['cfdich'];
    $form['cfcont'] = $_POST['cfcont'];
    $form['codcar'] = $_POST['codcar'];
    $form['codatt'] = $_POST['codatt'];
    $form['oroimp'] = $_POST['oroimp'];
    $form['oroiva'] = $_POST['oroiva'];
    $form['rotimp'] = $_POST['rotimp'];
    $form['rotiva'] = $_POST['rotiva'];
    if ($form['ivaatt'] > $form['ivapas']) {
        $ivadebit = round($form['ivaatt']) - round($form['ivapas']);
        $ivacredit = "0";
    } elseif ($form['ivaatt'] < $form['ivapas']) {
        $ivadebit = "0";
        $ivacredit = round($form['ivapas']) - round($form['ivaatt']);
    } else {
        $ivadebit = "0";
        $ivacredit = "0";
    }
}
require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<form method="POST">
    <div align="center" class="FacetFormHeaderFont"><?php print ucfirst($script_transl[0]); ?></div>
    <table border="0" cellpadding="3" cellspacing="1" class="FacetFormTABLE" align="center">
        <?php
        print "<input type=\"hidden\" value=\"{$_POST['ritorno']}\" name=\"ritorno\">\n";
        if (!empty($msg)) {
            $message = "";
            $rsmsg = array_slice(explode('+', chop($msg)), 0, -1);
            foreach ($rsmsg as $value) {
                $message .= $script_transl['error'] . "! -> ";
                $rsval = explode('-', chop($value));
                foreach ($rsval as $valmsg) {
                    $message .= $script_transl[$valmsg] . " ";
                }
                $message .= "<br>";
            }
            echo '<tr><td colspan="5" class="FacetDataTDred">' . $message . '</td></tr>';
        }
        print "<tr><td colspan=\"5\" class=\"FacetFieldCaptionTD\">-- " . str_pad("Sez.I " . $script_transl[1], 120, '-') . "</td></tr>\n";
        print "<tr><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl[2] . "</td><td colspan=\"3\" class=\"FacetDataTD\">" . $script_transl[3] . "</td></tr>\n";
        print "<tr><td colspan=\"2\" class=\"FacetDataTD\"><input type=\"text\" value=\"" . $form['codfis'] . "\" maxlength=\"16\"  name=\"codfis\"></td><td colspan=\"3\" class=\"FacetDataTD\"><input type=\"text\" value=\"{$form['ragsoc']}\" maxlength=\"60\"  name=\"ragsoc\"></td></tr>\n";
        print "<tr><td colspan=\"3\" class=\"FacetDataTD\">" . $script_transl[4] . "</td><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl[5] . "</td></tr>\n";
        print "<tr><td colspan=\"3\" class=\"FacetDataTD\"><input type=\"text\" value=\"{$form['cognom']}\" maxlength=\"24\"  name=\"cognom\"></td><td colspan=\"2\" class=\"FacetDataTD\"><input type=\"text\" value=\"{$form['nome']}\" maxlength=\"20\"  name=\"nome\"></td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetDataTD\"><hr></td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetDataTD\">" . $script_transl[6] . "</td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetDataTD\">";
        print "\t <select name=\"annimp\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
        for ($counter = $anno - 10; $counter <= $anno + 10; $counter++) {
            $selected = "";
            if ($counter == $form['annimp'])
                $selected = "selected";
            print "\t <option value=\"$counter\"  $selected >$counter</option>\n";
        }
        print "\t </select></td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetFieldCaptionTD\">-- " . str_pad($script_transl[7], 120, '-') . "</td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetDataTD\">" . $script_transl[8] . "</td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetDataTD\"><input type=\"text\" value=\"{$form['pariva']}\" maxlength=\"11\"  name=\"pariva\"></td></tr>\n";
        print "<tr><td class=\"FacetDataTD\"><input type=\"checkbox\" name=\"cont_sepa\" {$form['cont_sepa']} /> " . $script_transl[9] . "</td><td colspan=\"2\" class=\"FacetDataTD\"><input type=\"checkbox\" name=\"soci_grup\" {$form['soci_grup']} /> " . $script_transl[10] . "</td><td colspan=\"2\" class=\"FacetDataTD\"><input type=\"checkbox\" name=\"even_ecce\" {$form['even_ecce']} /> " . $script_transl[11] . "</td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetFieldCaptionTD\">-- " . str_pad($script_transl[12], 120, '-') . "</td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetDataTD\">" . $script_transl[13] . "</td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetDataTD\"><input type=\"text\" value=\"{$form['cfdich']}\" maxlength=\"11\"  name=\"cfdich\"></td></tr>\n";
        print "<tr><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl[14] . "</td><td colspan=\"3\" class=\"FacetDataTD\">" . $script_transl[15] . "</td></tr>\n";
        print "<tr><td colspan=\"2\" class=\"FacetDataTD\"><input type=\"text\" value=\"{$form['cfcont']}\" maxlength=\"16\"  name=\"cfcont\"></td><td colspan=\"3\" class=\"FacetDataTD\">";
        print "\t <select name=\"codcar\" class=\"FacetSelect\">\n";
        print "<option value=\"0\"> 0 - </option>\n";
        for ($i = 16; $i <= 23; $i++) {
            $selected = "";
            if ($codice_carica[$i] == $form['codcar'])
                $selected = "selected";
            print "\t <option value=\"$codice_carica[$i]\"  $selected > $codice_carica[$i]- $script_transl[$i]</option>\n";
        }
        print " </select></td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetDataTD\"><hr></td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetFieldCaptionTD\">-- " . str_pad("Sez.II " . $script_transl[24], 120, '-') . "</td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetDataTD\">" . $script_transl[25] . "</td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetDataTD\"><input type=\"text\" value=\"{$form['codatt']}\" maxlength=\"6\"  name=\"codatt\"></td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetFieldCaptionTD\">-- " . str_pad($script_transl[26], 120, '-') . "</td></tr>\n";
        print "<tr><td class=\"FacetDataTD\"> CD1 </td><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl[27] . "</td><td colspan=\"2\" class=\"FacetDataTD\"><input type=\"text\" value=\"" . round($form['totatt']) . "\" maxlength=\"11\"  name=\"totatt\"></td></tr>\n";
        print "<tr><td colspan=\"2\" class=\"FacetDataTD\"></td><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl[28] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round($form['attnim']) . "\" maxlength=\"11\"  name=\"attnim\"></td></tr>\n";
        print "<tr><td colspan=\"2\" class=\"FacetDataTD\"></td><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl[29] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round($form['attese']) . "\" maxlength=\"11\"  name=\"attese\"></td></tr>\n";
        print "<tr><td colspan=\"2\" class=\"FacetDataTD\"></td><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl[30] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round($form['attint']) . "\" maxlength=\"11\"  name=\"attint\"></td></tr>\n";
        print "<tr><td colspan=\"2\" class=\"FacetDataTD\"></td><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl['attben'] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round($form['attben']) . "\" maxlength=\"11\"  name=\"attben\"></td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetFieldCaptionTD\">-- " . str_pad($script_transl[31], 120, '-') . "</td></tr>\n";
        print "<tr><td class=\"FacetDataTD\"> CD2 </td><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl[32] . "</td><td colspan=\"2\" class=\"FacetDataTD\"><input type=\"text\" value=\"" . round($form['totpas']) . "\" maxlength=\"11\"  name=\"totpas\"></td></tr>\n";
        print "<tr><td colspan=\"2\" class=\"FacetDataTD\"></td><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl[33] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round($form['pasnim']) . "\" maxlength=\"11\"  name=\"pasnim\"></td></tr>\n";
        print "<tr><td colspan=\"2\" class=\"FacetDataTD\"></td><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl[34] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round($form['pasese']) . "\" maxlength=\"11\"  name=\"pasese\"></td></tr>\n";
        print "<tr><td colspan=\"2\" class=\"FacetDataTD\"></td><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl[35] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round($form['pasint']) . "\" maxlength=\"11\"  name=\"pasint\"></td></tr>\n";
        print "<tr><td colspan=\"2\" class=\"FacetDataTD\"></td><td colspan=\"2\" class=\"FacetDataTD\">" . $script_transl['pasben'] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round($form['pasben']) . "\" maxlength=\"11\"  name=\"pasben\"></td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetFieldCaptionTD\">-- " . str_pad($script_transl[36], 120, '-') . "</td></tr>\n";
        print "<tr><td class=\"FacetDataTD\"> CD3 </td><td class=\"FacetDataTD\">" . $script_transl[38] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round(floatval($form['oroimp'])) . "\" maxlength=\"11\"  name=\"oroimp\"></td><td class=\"FacetDataTD\">" . $script_transl[39] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round(floatval($form['oroiva'])) . "\" maxlength=\"11\"  name=\"oroiva\"></td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetFieldCaptionTD\">-- " . str_pad($script_transl[37], 120, '-') . "</td></tr>\n";
        print "<tr><td class=\"FacetDataTD\"> CD3 </td><td class=\"FacetDataTD\">" . $script_transl[38] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round(floatval($form['rotimp'])) . "\" maxlength=\"11\"  name=\"rotimp\"></td><td class=\"FacetDataTD\">" . $script_transl[39] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round(floatval($form['rotiva'])) . "\" maxlength=\"11\"  name=\"rotiva\"></td></tr>\n";
        print "<tr><td colspan=\"5\" class=\"FacetFieldCaptionTD\">-- " . str_pad("Sez.III " . $script_transl[40], 120, '-') . "</td></tr>\n";
        print "<tr><td class=\"FacetDataTD\"> CD4 </td><td class=\"FacetDataTD\">" . $script_transl[41] . $script_transl[42] . "</td><td colspan=\"3\" class=\"FacetDataTD\"><input type=\"text\" value=\"" . round($form['ivaatt']) . "\" maxlength=\"11\"  name=\"ivaatt\"></td></tr>\n";
        print "<tr><td class=\"FacetDataTD\"> CD5 </td><td colspan=\"3\" class=\"FacetDataTD\">" . $script_transl[41] . $script_transl[43] . "</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . round($form['ivapas']) . "\" maxlength=\"11\"  name=\"ivapas\"></td></tr>\n";
        print "<tr><td class=\"FacetDataTD\"> CD6 </td><td class=\"FacetDataTD\">" . $script_transl[41] . $script_transl[44] . "</td><td class=\"FacetDataTD\">$ivadebit</td><td class=\"FacetDataTD\">" . $script_transl[45] . "</td><td class=\"FacetDataTD\">$ivacredit</td></tr>\n";
        ?>
        <tr>
            <td colspan="5" align="right" nowrap class="FacetFooterTD">
                <input type="reset" name="Cancel" value="<?php print $script_transl['cancel']; ?>">&nbsp;
                <input type="submit" name="Return" value="<?php print $script_transl['return']; ?>">&nbsp;
                <input type="submit" name="Insert" value="<?php print $script_transl['submit']; ?>">&nbsp;
            </td>
        </tr>
    </table>
</form>
<?php
require("../../library/include/footer.php");
?>