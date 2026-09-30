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

$admin_aziend = checkAdmin();
$msg = "";
$tipoLettera = array("LET" => '', "DIC" => '', "SOL" => '', "PRE" => '', "SMS" => '');
// il tipo documento dev'essere settato e del tipo giusto altrimenti torna indietro
if (((isset($_GET['Update']) or isset($_GET['Duplicate'])) and ! isset($_GET['id_let'])) or ( isset($_GET['tipo']) and ( !array_key_exists($_GET['tipo'], $tipoLettera)))) {
    header("Location: " . $form['ritorno']);
    exit;
}

if ((isset($_POST['Update'])) or ( isset($_GET['Update']))) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if ((isset($_POST['Insert'])) or ( isset($_POST['Update']))) {   //se non e' il primo accesso
    $form['ritorno'] = $_POST['ritorno'];
    $form['hidden_req'] = $_POST['hidden_req'];
    $form['id_let'] = intval($_POST['id_let']);
    $form['gioemi'] = intval($_POST['gioemi']);
    $form['mesemi'] = intval($_POST['mesemi']);
    $form['annemi'] = intval($_POST['annemi']);
    $form['numero'] = $_POST['numero'];
    $form['tipo'] = $_POST['tipo'];
    $form['clfoco'] = substr($_POST['clfoco'], 0, 13);
    $form['oggetto'] = $_POST['oggetto'];
    $form['c_a'] = $_POST['c_a'];
    $form['corpo'] = $_POST['corpo'];
    if (isset($_POST['signature'])) {
        $form['signature'] = 'checked';
    } else {
        $form['signature'] = '';
    }
    //--- variabili temporanee
    foreach ($_POST['search'] as $k => $v) {
        $form['search'][$k] = $v;
    }
    if ($_POST['hidden_req'] == 'clfoco') {
        $anagrafica = new Anagrafica();
        if (preg_match("/^id_([0-9]+)$/", $form['clfoco'], $match)) {
            $partner = $anagrafica->getPartnerData($match[1], 1);
        } else {
            $partner = $anagrafica->getPartner($form['clfoco']);
        }
        $form['hidden_req'] = '';
        //var_dump($_POST);
        //exit;
    }

    if (isset($_POST['ins'])) {   // Se viene inviata la richiesta di conferma totale ...
        $datemi = date("Ymd", mktime(0, 0, 0, $form['mesemi'], $form['gioemi'], $form['annemi']));
        if (!checkdate($form['mesemi'], $form['gioemi'], $form['annemi'])) {
            $msg .= "10+";
        }
        if ($form['clfoco'] == 0) {
            $msg .= "11+";
        }
        if ($msg == "") {// nessun errore
            $form['write_date'] = $datemi;
            if (isset($_POST['signature'])) {
                $form['signature'] = 1;
            } else {
                $form['signature'] = 0;
            }
            if ($toDo == 'update') {  // modifica
                $codice = array('id_let', $form['id_let']);
                letterUpdate($codice, $form);
                header("Location: " . $form['ritorno']);
                exit;
            } else {                  // inserimento
                letterInsert($form);
                $_SESSION['print_request'] = gaz_dbi_last_id();
                header("Location: invsta_letter.php");
                exit;
            }
        }
    }
} elseif ((!isset($_POST['Update'])) and ( isset($_GET['Update']) or isset($_GET['Duplicate']))) { //se e' il primo accesso per UPDATE
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
    $lettera = gaz_dbi_get_row($gTables['letter'], 'id_let', intval($_GET['id_let']));
    if ($lettera['adminid'] != $_SESSION["user_name"]) { //non � l'utente che ha scritto la lettera
        header("Location: report_letter.php");
        exit;
    }
    $anagrafica = new Anagrafica();
    $partner = $anagrafica->getPartner($lettera['clfoco']);
    $form['search']['clfoco'] = substr($partner['ragso1'], 0, 10);
    $form['id_let'] = $lettera['id_let'];
    $form['gioemi'] = substr($lettera['write_date'], 8, 2);
    $form['mesemi'] = substr($lettera['write_date'], 5, 2);
    $form['annemi'] = substr($lettera['write_date'], 0, 4);
    $form['numero'] = $lettera['numero'];
    $form['tipo'] = $lettera['tipo'];
    $form['clfoco'] = $lettera['clfoco'];
    $form['oggetto'] = $lettera['oggetto'];
    $form['c_a'] = $lettera['c_a'];
    $form['corpo'] = $lettera['corpo'];
    if ($lettera['signature'] == 1) {
        $form['signature'] = 'checked';
    } else {
        $form['signature'] = '';
    }
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
    $form['search']['clfoco'] = '';
    $form['id_let'] = "";
    $form['gioemi'] = date("d");
    $form['mesemi'] = date("m");
    $form['annemi'] = date("Y");
    $rs_ultima_lettera = gaz_dbi_dyn_query("*", $gTables['letter'], "YEAR(write_date) = " . date("Y"), 'write_date DESC, numero DESC, id_let DESC', 0, 1);
    $ultima_lettera = gaz_dbi_fetch_array($rs_ultima_lettera);
    if ($ultima_lettera) {
        $form['numero'] = intval($ultima_lettera['numero']) + 1;
        $form['tipo'] = $ultima_lettera['tipo'];
    } else {
        $form['numero'] = 1;
        $form['tipo'] = 'LET';
    }
    $form['clfoco'] = 0;
    $form['oggetto'] = '';
    $form['c_a'] = '';
    $form['corpo'] = '';
    $form['signature'] = 'checked';
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, ['custom/autocomplete']);
?>
<script>

</script>
<style>
  #corpo_ifr{
	min-height: 400px;
  }
</style>
<?php
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . ucfirst($script_transl[$toDo] . $script_transl['title'] . $script_transl[0][$form['tipo']]) . "</div>\n";
echo "<form method=\"POST\">";
echo "<input type=\"hidden\" name=\"" . ucfirst($toDo) . "\" value=\"\">\n";
echo "<input type=\"hidden\" value=\"" . $form['ritorno'] . "\" name=\"ritorno\">\n";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" name=\"id_let\" value=\"" . $form['id_let'] . "\">\n";
echo "<div class=\"table-responsive\"><table class=\"Tlarge table table-striped table-bordered table-condensed\">\n";
if (!empty($msg)) {
    echo "<tr><td colspan=\"6\" class=\"FacetDataTDred\">";
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
    echo $message . "</td></tr>\n";
}
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[7]</td><td class=\"FacetDataTD\"><select name=\"tipo\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
foreach ($tipoLettera as $key => $value) {
    $selected = "";
    if ($form["tipo"] == $key) {
        $selected = " selected ";
    }
    echo "<option value=\"" . $key . "\"" . $selected . ">" . $script_transl[0][$key] . "</option>";
}
echo "</select></td>";
echo " <td align=\"right\" class=\"FacetFieldCaptionTD\">$script_transl[3]</td><td class=\"FacetDataTD\"> <input type=\"text\" value=\"" . $form['numero'] . "\" maxlength=\"20\"  name=\"numero\"></td>\n";
echo "<td class=\"FacetFieldCaptionTD\">$script_transl[1]</td><td class=\"FacetDataTD\"> \n";
// select del giorno
echo "\t <select name=\"gioemi\" class=\"FacetSelect\" >\n";
for ($counter = 1; $counter <= 31; $counter++) {
    $selected = "";
    if ($counter == $form['gioemi'])
        $selected = "selected";
    echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
// select del mese
echo "\t <select name=\"mesemi\" class=\"FacetSelect\" >\n";
$gazTimeFormatter->setPattern('MMMM');
for ($counter = 1; $counter <= 12; $counter++) {
  $selected = "";
  if ($counter == $form['mesemi']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
// select del anno
echo "\t <select name=\"annemi\" class=\"FacetSelect\">\n";
for ($counter = $form['annemi'] - 10; $counter <= $form['annemi'] + 10; $counter++) {
    $selected = "";
    if ($counter == $form['annemi'])
        $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select></td></tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[4] : </td><td colspan=\"3\" class=\"FacetDataTD\"><input type=\"text\" value=\"" . $form['oggetto'] . "\" maxlength=\"60\"  name=\"oggetto\"></td>\n";
echo "<td class=\"FacetFieldCaptionTD\">$script_transl[2] : </td><td class=\"FacetDataTD\">\n";
$select_cliente = new selectPartner('clfoco');
$select_cliente->selectDocPartner('clfoco', $form['clfoco'], $form['search']['clfoco'], 'clfoco', $script_transl['mesg'], -1);
echo "</td></tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[5] : </td><td colspan=\"3\" class=\"FacetDataTD\"><input type=\"text\" value=\"" . $form['c_a'] . "\" maxlength=\"60\"  name=\"c_a\"></td>\n";
echo "<td class=\"FacetFieldCaptionTD\"></td><td class=\"FacetDataTD\" ";
if (isset($partner['indspe'])) {
    echo "title=\"fax: " . $partner['fax'] . "\">" . $partner['indspe'] . "<br />" . $partner['capspe'] . " " . $partner['citspe'] . " (" . $partner['prospe'] . ")";
} else {
    echo ">";
}
echo "</td></tr>\n";
echo "<tr><td colspan=\"6\" class=\"FacetFieldCaptionTD\" align=\"center\">$script_transl[8]</td></tr>\n";
if ($form["tipo"] == 'SMS') {
	$lunghezza_sms = 160;
    echo "<tr><td colspan=\"6\"><small id=\"metro_messaggio\" class=\"muted\">($lunghezza_sms)</small> <input type=\"text\" id=\"corpo\" name=\"corpo\" value=\"" . $form["corpo"] . "\" maxlength=\"$lunghezza_sms\"/></td></tr>\n";
} else {
    echo "<tr><td colspan=\"6\"><textarea id=\"corpo\" name=\"corpo\" class=\"mceClass\">" . $form["corpo"] . "</textarea></td></tr>\n";
}
echo "<tr><td colspan=\"3\" class=\"FacetFieldCaptionTD\" align=\"right\">$script_transl[9]<input type=\"checkbox\" name=\"signature\" " . $form['signature'] . "></td>
          <td colspan=\"3\" class=\"FacetFieldCaptionTD\" align=\"center\"><input type=\"submit\" class=\"btn btn-warning\" name=\"ins\" value=\"" . $script_transl['submit'] . "\" /></td>
          </tr>";
echo "</table></div>";
?>
</form>
<?php
if ($form["tipo"] == 'SMS') {
?>
<script>
  var messaggio;var MSG_STRING="";
  messaggio=$("form input[name=corpo]");MSG_STRING=$("#corpo").val();MSG_STRING=reparse_special_chars(MSG_STRING);$("#metro_messaggio").text("("+(<?php echo $lunghezza_sms ?>-parseInt(MSG_STRING.length))+")");
  function reparse_special_chars(b){reps={"\n":"   ","\t":"   ","\u20a4":"   ","\u20ac":"   ","^":"^^^","{":"{{{","}":"}}}","\\":"\\\\\\","[":"[[[","~":"~~~","]":"]]]","|":"|||"};for(var a in reps){temp_string=b.split(a);b=temp_string.join(reps[a])}return b}
  $("#corpo").keyup(function(){MSG_STRING=$(this).val();MSG_STRING=reparse_special_chars(MSG_STRING);$("#metro_messaggio").text("("+(<?php echo $lunghezza_sms ?>-parseInt(MSG_STRING.length))+")")});$(".dyn_c_fields").click(function(){MSG_STRING=$(messaggio).val();MSG_STRING=reparse_special_chars(MSG_STRING);if(MSG_STRING.length>=<?php echo $lunghezza_sms ?>-$(this).attr("rel").length){return}moved_caret=$(messaggio).caret()+$(this).attr("rel").length;$(messaggio).val($(messaggio).val().substr(0,$(messaggio).caret())+$(this).attr("rel")+$(messaggio).val().substr($(messaggio).caret()));$(messaggio).caret(moved_caret);MSG_STRING=$("#corpo").val();MSG_STRING=reparse_special_chars(MSG_STRING);$("#metro_messaggio").text("("+(<?php echo $lunghezza_sms ?>-parseInt(MSG_STRING.length))+")")});
</script>
<?php
}
?>
<?php
require("../../library/include/footer.php");
?>
