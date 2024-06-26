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
$msg = '';


if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}

if (isset($_POST['Update']) or isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if ((isset($_POST['Insert'])) or ( isset($_POST['Update']))) {   //se non e' il primo accesso
    $form['codice'] = intval($_POST['codice']);
    $form['tipiva'] = substr($_POST['tipiva'], 0, 1);
    $form['operation_type'] = substr($_POST['operation_type'], 0, 15);
    $form['descri'] = substr($_POST['descri'], 0, 50);
    $form['aliquo'] = floatval($_POST['aliquo']);
    $form['taxstamp'] = intval($_POST['taxstamp']);
    $form['fae_natura'] = substr(trim($_POST['fae_natura']), 0, 4);
    ;
    $form['annota'] = substr($_POST['annota'], 0, 50);
    if (isset($_POST['Submit'])) { // conferma tutto
        //eseguo i controlli formali
        $code_exist = gaz_dbi_dyn_query('codice', $gTables['aliiva'], "codice = '" . $form['codice'] . "'", 'codice DESC', 0, 1);
        $code = gaz_dbi_fetch_array($code_exist);
        if ($code and $toDo == 'insert') {
            $msg .= "5+";
        }
        if (empty($form['descri'])) {
            $msg .= "7+";
        }
        if ($form['codice'] <= 0 || $form['codice'] > 99) {
            $msg .= "6+";
        }
        if ($form['aliquo'] < 0 || $form['aliquo'] > 99) {
            $msg .= "8+";
        }
        if ($form['aliquo'] == 0 && empty($form['fae_natura'])) {
            $msg .= "10+";
        }
        if (empty($msg)) { // nessun errore
            // aggiorno il db
            if ($toDo == 'insert') {
                aliivaInsert($form);
            } elseif ($toDo == 'update') {
                aliivaUpdate($form['codice'], $form);
            }
            header("Location: report_aliiva.php");
            exit;
        }
    } elseif (isset($_POST['Return'])) { // torno indietro
        header("Location: " . $_POST['ritorno']);
        exit;
    }
} elseif ((!isset($_POST['Update'])) and ( isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $codice = intval($_GET['codice']);
    $form = gaz_dbi_get_row($gTables['aliiva'], 'codice', $codice);
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $rs_ultimo = gaz_dbi_dyn_query('codice', $gTables['aliiva'], '1', 'codice DESC', 0, 1);
    $ultimo = gaz_dbi_fetch_array($rs_ultimo);
    $form['codice'] = $ultimo['codice'] + 1;
    $form['tipiva'] = 'I';
    $form['operation_type'] = '';
    $form['descri'] = '';
    $form['aliquo'] = '';
    $form['taxstamp'] = 1;
    $form['fae_natura'] = '';
    $form['annota'] = '';
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new GAzieForm();
echo "<form method=\"POST\">";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $_POST['ritorno'] . "\">\n";
echo "<input type=\"hidden\" name=\"" . ucfirst($toDo) . "\" value=\"\">";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl[$toDo] . $script_transl[0] . "</div>";
echo "<table class=\"Tmiddle table-striped\" align=\"center\">";
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
    echo '<tr><td colspan="2" class="FacetDataTDred">' . $message . '</td></tr>';
}
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[1] . "</td>
     <td class=\"FacetDataTD\">\n";
if ($toDo == 'update') {
    echo "\t<input type=\"hidden\" name=\"codice\" value=\"" . $form['codice'] . "\" ><div class=\"FacetDataTD\">" . $form['codice'] . "<div>\n";
} else {
    echo "\t<input type=\"text\" name=\"codice\" value=\"" . $form['codice'] . "\" maxlength=\"15\"  class=\"FacetInput\">\n";
}
echo "</td></tr>";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl[9] . "</td>\n";
echo "<td class=\"FacetDataTD\">\n";
echo "<select name=\"tipiva\" class=\"FacetSelect\">\n";
foreach ($script_transl['tipiva'] as $key => $value) {
    $selected = "";
    if ($form["tipiva"] == $key)
        $selected = " selected ";
    echo "<option value=\"" . $key . "\"" . $selected . ">" . $key . ' - ' . $value . "</option>";
}
echo "</select></td></tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl['operation_type'] . "</td>
     <td class=\"FacetDataTD\">\n";
$gForm->selectFromXML('../../library/include/operation_type.xml', 'operation_type', 'operation_type', $form['operation_type'], true);
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[2] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "\t<input type=\"text\" name=\"descri\" value=\"" . $form['descri'] . "\" maxlength=\"50\"  class=\"FacetInput\">\n";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[3] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "\t<input type=\"text\" name=\"aliquo\" value=\"" . $form['aliquo'] . "\" maxlength=\"9\"  class=\"FacetInput\">\n";
echo "</td></tr>";
echo "<tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['taxstamp'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->variousSelect('taxstamp', $script_transl['yn_value'], $form['taxstamp']);
echo "\t </td>\n";
echo "</tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['fae_natura'] . "</td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\">";
$gForm->selectFromXML('../../library/include/fae_natura_iva.xml', 'fae_natura', 'fae_natura', $form['fae_natura'], true);
echo "</td>\n";
echo "</tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl['annota'] . "</td>
     <td class=\"FacetDataTD\">\n";
echo '<textarea name="sedleg" rows="2" cols="60" maxlength="254" >'. $form['annota'].'</textarea>';
echo "</td></tr>";
echo '<tr><td colspan=2 class="FacetFooterTD text-center">';
echo '<input name="Submit" class="btn btn-warning" title="Accetta tutto e modifica" type="submit" value=' . ucfirst($script_transl['submit']) . '>';
?>
</td>
</tr>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
