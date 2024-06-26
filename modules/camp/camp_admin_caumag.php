<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
require("../../library/include/datlib.inc.php");

$admin_aziend = checkAdmin();
$msg = "";


if ((isset($_POST['Update'])) or ( isset($_GET['Update']))) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}

if ((isset($_GET['Update']) and ! isset($_GET['codice'])) or isset($_POST['Return'])) {
    header("Location: " . $_POST['ritorno']);
    exit;
}

if ((isset($_POST['Insert'])) or ( isset($_POST['Update']))) {   //se non e' il primo accesso
    //qui si dovrebbe fare un parsing di quanto arriva dal browser...
    $form['codice'] = intval($_POST['codice']);
    $form['descri'] = $_POST['descri'];
    $form['insdoc'] = $_POST['insdoc'];
    $form['operat'] = $_POST['operat'];
    $form['clifor'] = $_POST['clifor'];
	$form['type_cau'] = $_POST['type_cau'];
    // Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {
        if ($toDo == 'insert') { // e' un inserimento, controllo se il codice esiste
            $rs_ctrl = gaz_dbi_get_row($gTables['caumag'], "codice", $form['codice']);
            if ($rs_ctrl) {
                $msg .= "15+";
            }
        }
        if (empty($form['descri'])) {  //descrizione vuota
            $msg .= "16+";
        }
        if ($form['codice'] >= 80) {  // codice non utilizzabile perché bloccato da altri moduli
            $msg .= "17+";
        }
        if ($msg == "") {// nessun errore
            if ($toDo == 'update') { // e' una modifica
                gaz_dbi_table_update('caumag', $form["codice"], $form);
            } else { // e' un'inserimento
                gaz_dbi_table_insert('caumag', $form);
            }
            header("Location: " . $_POST['ritorno']);
            exit;
        }
    }
} elseif ((!isset($_POST['Update'])) and ( isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $caumag = gaz_dbi_get_row($gTables['caumag'], "codice", intval($_GET['codice']));
    $form['ritorno'] = $_POST['ritorno'];
    $form['codice'] = $caumag['codice'];
    $form['descri'] = $caumag['descri'];
    $form['insdoc'] = $caumag['insdoc'];
    $form['clifor'] = $caumag['clifor'];
    $form['operat'] = $caumag['operat'];
	$form['type_cau'] = intval($caumag['type_cau']);
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $rs_ultimo_codice = gaz_dbi_dyn_query("*", $gTables['caumag'], 'codice <= 79', "codice desc", 0, 1);// icodici da 80 in poi sono riservati
    $ultimo_codice = gaz_dbi_fetch_array($rs_ultimo_codice);
    $form['codice'] = $ultimo_codice['codice'] + 1;
    $form['descri'] = "";
    $form['clifor'] = 0;
    $form['insdoc'] = 1;
    $form['operat'] = 1;
	$form['type_cau'] = 1;
}
require("../../library/include/header.php");
$script_transl = HeadMain();
if ($toDo == 'update') {
    $title = ucwords($script_transl[$toDo] . $script_transl[0]) . " n." . $form['codice'];
} else {
    $title = ucwords($script_transl[$toDo] . $script_transl[0]);
}
print "<form method=\"POST\">\n";
print "<input type=\"hidden\" name=\"" . ucfirst($toDo) . "\" value=\"\">\n";
print "<input type=\"hidden\" value=\"" . $_POST['ritorno'] . "\" name=\"ritorno\">\n";
print "<div align=\"center\" class=\"FacetFormHeaderFont\">$title</div>";
print "<table class=\"Tmiddle table-striped\" align=\"center\">\n";
if (!empty($msg)) {
    $message = "";
    $rsmsg = array_slice(explode('+', chop($msg)), 0, -1);
    foreach ($rsmsg as $value) {
        $message .= $script_transl['error'] . "! -> ";
        $rsval = explode('-', chop($value));
        foreach ($rsval as $valmsg) {
            $message .= $script_transl[$valmsg] . " ";
        }
        $message .= "<br />";
    }
    echo '<tr><td colspan="5" class="FacetDataTDred">' . $message . "</td></tr>\n";
}
if ($toDo == 'update') {
    print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[1]</td><td class=\"FacetDataTD\"><input type=\"hidden\" name=\"codice\" value=\"" . $form['codice'] . "\" />" . $form['codice'] . "</td></tr>\n";
} else {
    print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[1]</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"codice\" value=\"" . $form['codice'] . "\" maxlength=\"2\"  /></td></tr>\n";
}
print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[2]</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"descri\" value=\"" . $form['descri'] . "\" maxlength=\"50\"  /></td></tr>\n";
print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[11]</td><td class=\"FacetDataTD\">\n";
/*
print "<select name=\"clifor\" class=\"FacetSelect\">\n";
for ($counter = -1; $counter <= 1; $counter++) {
    $selected = "";
    if ($form["clifor"] == $counter) {
        $selected = " selected ";
    }
    print "<option value=\"$counter\" $selected > " . $script_transl[$counter + 13] . "</option>\n";
}
print "</select></td></tr>\n";

print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[3]</td><td class=\"FacetDataTD\">\n";
print "<select name=\"insdoc\" class=\"FacetSelect\">\n";
for ($counter = 0; $counter <= 1; $counter++) {
    $selected = "";
    if ($form["insdoc"] == $counter) {
        $selected = " selected ";
    }
    print "<option value=\"$counter\" $selected > " . $script_transl[$counter + 6] . "</option>\n";
}
print "</select></td></tr>\n";
*/
?>
<input type="hidden" name="clifor" value="0" />
<input type="hidden" name="insdoc" value="1" />
<?
print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[4]</td><td class=\"FacetDataTD\">\n";
print "<select name=\"operat\" class=\"FacetSelect\">\n";
for ($counter = -1; $counter <= 1; $counter++) {
    $selected = "";
    if ($form["operat"] == $counter) {
        $selected = " selected ";
    }
    print "<option value=\"$counter\" $selected > " . $script_transl[$counter + 9] . "</option>\n";
}
print "</select></td></tr>\n";

print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[18]</td><td class=\"FacetDataTD\">\n";
print "<select name=\"type_cau\" class=\"FacetSelect\">\n";
unset ($sel);
for ($counter = 0; $counter <= 9; $counter++) {
    if ($form['type_cau'] == $counter) {
        $sel[$counter] = " selected ";
    } else {
		$sel[$counter] = $counter;
	}
}
print "<option value=\"0\" $sel[0] > " . $script_transl[19] . "</option>\n";
print "<option value=\"1\" $sel[1] > " . $script_transl[20] . "</option>\n";
print "<option value=\"9\" $sel[9] > " . $script_transl[21] . "</option>\n";
print "</select></td></tr>\n";

print "<tr><td class=\"FacetFieldCaptionTD\"><input type=\"reset\" name=\"Cancel\" value=\"" . $script_transl['cancel'] . "\">\n";
print "</td><td class=\"FacetDataTD\" align=\"right\">\n";
print "<input type=\"submit\" name=\"Return\" value=\"" . $script_transl['return'] . "\">\n";
if ($toDo == 'update') {
    print '<input type="submit" accesskey="m" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="' . ucfirst($script_transl['update']) . '!"></td></tr><tr></tr>';
} else {
    print '<input type="submit" accesskey="i" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="' . ucfirst($script_transl['insert']) . '!"></td></tr><tr></tr>';
}
print "</td></tr></table>\n";
?>
</form>
<?php
require("../../library/include/footer.php");
?>
