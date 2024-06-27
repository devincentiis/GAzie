<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2022 - Antonio De Vincentiis Montesilvano (PE)
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
    $form['descri'] = substr($_POST['descri'], 0, 50);
    $form['tippag'] = substr($_POST['tippag'], 0, 1);
    $form['incaut'] = intval($_POST['incaut']);
    $form['pagaut'] = intval($_POST['pagaut']);
    $form['tipdec'] = substr($_POST['tipdec'], 0, 1);
    $form['giodec'] = intval($_POST['giodec']);
    $form['mesesc'] = intval($_POST['mesesc']);
    $form['messuc'] = intval($_POST['messuc']);
    $form['giosuc'] = intval($_POST['giosuc']);
    $form['numrat'] = intval($_POST['numrat']);
	$form['web_payment_ref'] = $_POST['web_payment_ref'];
    $form['tiprat'] = substr($_POST['tiprat'], 0, 1);
    $form['fae_mode'] = substr($_POST['fae_mode'], 0, 4);
    $form['id_bank'] = intval($_POST['id_bank']);
    $form['annota'] = substr($_POST['annota'], 0, 50);
    if (isset($_POST['Submit'])) { // conferma tutto
        //eseguo i controlli formali
        $code_exist = gaz_dbi_dyn_query('codice', $gTables['pagame'], "codice = '" . $form['codice'] . "'", 'codice DESC', 0, 1);
        $code = gaz_dbi_fetch_array($code_exist);
        if ($code and $toDo == 'insert') {
            $msg .= "18+";
        }
        if (empty($form['descri'])) {
            $msg .= "19+";
        }
        if ($form['codice'] <= 0) {
            $msg .= "20+";
        }
        if (empty($form['fae_mode'])) {
            $msg .= "21+";
        }
        if (empty($msg)) { // nessun errore
            // aggiorno il db
            if ($toDo == 'insert') {
                pagameInsert($form);
            } elseif ($toDo == 'update') {
                pagameUpdate($form['codice'], $form);
            }
            header("Location: report_pagame.php");
            exit;
        }
    } elseif (isset($_POST['Return'])) { // torno indietro
        header("Location: " . $_POST['ritorno']);
        exit;
    }
} elseif ((!isset($_POST['Update'])) and ( isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $codice = intval($_GET['codice']);
    $form = gaz_dbi_get_row($gTables['pagame'], 'codice', $codice);
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $rs_ultimo = gaz_dbi_dyn_query('codice', $gTables['pagame'], '1', 'codice DESC', 0, 1);
    $ultimo = gaz_dbi_fetch_array($rs_ultimo);
    $form['codice'] = $ultimo['codice'] + 1;
    $form['descri'] = '';
    $form['tippag'] = 'D';
    $form['incaut'] = 0;
    $form['pagaut'] = 0;
    $form['tipdec'] = 'D';
    $form['giodec'] = 1;
    $form['mesesc'] = 0;
    $form['messuc'] = 0;
    $form['giosuc'] = 0;
    $form['numrat'] = 0;
    $form['tiprat'] = 'M';
    $form['fae_mode'] = '';
    $form['web_payment_ref'] = '';
    $form['id_bank'] = 0;
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
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[2] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "\t<input type=\"text\" name=\"descri\" value=\"" . $form['descri'] . "\" maxlength=\"50\"  class=\"FacetInput\">\n";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[3] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "<select name=\"tippag\" class=\"FacetSelect\">";
foreach ($script_transl[14] as $key => $value) {
    $selected = "";
    if ($form['tippag'] == $key) {
        $selected = " selected ";
    }
    echo "<option value=\"" . $key . "\"" . $selected . ">" . $key . '-' . $value . "</option>";
}
echo "</select></td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[4] . "</td>
     <td class=\"FacetDataTD\">\n";
$gForm->selectAccount('incaut', $form['incaut'], substr($admin_aziend['cassa_'], 0, 3), '', false, "col-sm-8", '', false);
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl['pagaut'] . "</td>
     <td class=\"FacetDataTD\">\n";
$gForm->selectAccount('pagaut', $form['pagaut'], array('sub', 1, 2, 5), '', false, "col-sm-8");
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[5] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "<select name=\"tipdec\" class=\"FacetSelect\">";
foreach ($script_transl[16] as $key => $value) {
    $selected = "";
    if ($form['tipdec'] == $key) {
        $selected = " selected ";
    }
    echo "<option value=\"" . $key . "\"" . $selected . ">" . $key . '-' . $value . "</option>";
}
echo "</select></td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[6] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "\t<input type=\"text\" name=\"giodec\" value=\"" . $form['giodec'] . "\" maxlength=\"3\"  class=\"FacetInput\">\n";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[7] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "\t<input type=\"text\" name=\"mesesc\" value=\"" . $form['mesesc'] . "\" maxlength=\"2\"  class=\"FacetInput\">\n";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[8] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "\t<input type=\"text\" name=\"messuc\" value=\"" . $form['messuc'] . "\" maxlength=\"2\"  class=\"FacetInput\">\n";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[9] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "\t<input type=\"text\" name=\"giosuc\" value=\"" . $form['giosuc'] . "\" maxlength=\"2\"  class=\"FacetInput\">\n";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[10] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "<select name=\"numrat\" class=\"FacetSelect\">";
for ($counter = 1; $counter <= 24; $counter++) {
    $selected = "";
    if ($form['numrat'] == $counter) {
        $selected = " selected ";
    }
    echo "<option value=\"" . $counter . "\"" . $selected . ">" . $counter . "</option>";
}
echo "</select></td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[11] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "<select name=\"tiprat\" class=\"FacetSelect\">";
foreach ($script_transl[17] as $key => $value) {
    $selected = "";
    if ($form['tiprat'] == $key) {
        $selected = " selected ";
    }
    echo "<option value=\"" . $key . "\"" . $selected . ">" . $key . '-' . $value . "</option>";
}
echo "</select></td></tr>";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['fae_mode'] . "</td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\">";
$gForm->selectFromXML('../../library/include/fae_payment_mode.xml', 'fae_mode', 'fae_mode', $form['fae_mode'], true);
echo "</td>\n";
echo "</tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[12] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "<select name=\"id_bank\" class=\"FacetSelect\">";
$result = gaz_dbi_dyn_query("codice,descri,iban", $gTables['clfoco'], "codice NOT LIKE '%000000' AND codice LIKE '" . $admin_aziend['masban'] . "%' AND iban != ''", "descri ASC");
echo "<option value=\"0\"> ---------- </option>";
while ($a_row = gaz_dbi_fetch_array($result)) {
    $selected = "";
    if ($form['id_bank'] == $a_row['codice']) {
        $selected = " selected ";
    }
    echo "<option value=\"" . $a_row['codice'] . "\"" . $selected . ">" . $a_row['descri'] . " - " . $a_row['iban'] . "</option>";
}
echo "</select></td></tr>\n";
?>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['web_payment_ref']; ?></td>
	<td colspan="2" class="FacetDataTD">
		<input type="text" value="<?php echo $form['web_payment_ref']; ?>" name="web_payment_ref" maxlength="50" />
	</td>
</tr>
<?php
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[13] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "\t<input type=\"text\" name=\"annota\" value=\"" . $form['annota'] . "\" maxlength=\"50\"  class=\"FacetInput\">\n";
echo "</td></tr>";
echo "<tr><td colspan=2 class=\"FacetFooterTD text-center\">\n";
if ($toDo == 'update') {
    echo '<input name="Submit" class="btn btn-warning" title="Accetta tutto e modifica" type="submit" value="Modifica">';
} else {
    echo '<input name="Submit" class="btn btn-warning" title="Accetta tutto e inserisce" type="submit" value="Inserisci">';
}
?>
</td>
</tr>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
