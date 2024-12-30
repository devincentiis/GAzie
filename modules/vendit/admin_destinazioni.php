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
$msg = '';

if (isset($_POST['Update']) || isset($_GET['Update'])) {
   $toDo = 'update';
} else {
   $toDo = 'insert';
}

$modal = false;
$modal_ok_insert = false;
if (isset($_POST['mode']) || isset($_GET['mode'])) {
  $modal = true;
  if (isset($_GET['ok_insert'])) {
    $modal_ok_insert = true;
  }
}

if (isset($_POST['ritorno'])) {   //se non e' il primo accesso
   $form = gaz_dbi_parse_post('destina');
   $form['codice'] = trim($form['codice']);
   $form['ritorno'] = $_POST['ritorno'];
   $form['ref_code'] = substr($_POST['ref_code'], 0, 15);
   $form['id_anagra'] = intval($_POST['id_anagra']);
   foreach ($_POST['search'] as $k => $v) {
      $form['search'][$k] = $v;
   }
   if (isset($_POST['conferma']) || ($modal === true && isset($_POST['mode-act']))) { // conferma tutto
      $msg .= (empty($form["id_anagra"]) ? "0+" : '');
      $msg .= (empty($form["indspe"]) ? "1+" : '');
      $msg .= (empty($form["capspe"]) ? "2+" : '');
      $msg .= (empty($form["citspe"]) ? "3+" : '');
      if (empty($msg)) { // nessun errore
        // aggiorno il db
        if ($toDo == 'insert') {
          gaz_dbi_table_insert('destina', $form);
        } elseif ($toDo == 'update') {
          gaz_dbi_table_update('destina', $form['codice'], $form);
        }
        if ($modal === false) {
          if ($toDo == 'insert') {
            if ($catch){
              header("Location: ./report_destinazioni.php?Update&codice=".$form['codice']);
              exit;
            } else {
              $msg['err'][] = 'no_ins';
            }
          } else{
            header("Location: " . $form['ritorno']);
            exit;
          }
        } else {
          header("Location: ./admin_destinazioni.php?mode=modal&ok_insert=1");
          exit;
        }
      }
   } elseif (isset($_POST['Return']) && $modal === false) {
      header("Location: " . $form['ritorno']);
      exit;
   }
} elseif (!isset($_POST['ritorno']) && (isset($_GET['Update']) || isset($_POST['Update']))) { //se e' il primo accesso per UPDATE
  $cod=isset($_GET['Update'])?intval($_GET['codice']):intval($_POST['codice']);
  $form = gaz_dbi_get_row($gTables['destina'],'codice',$cod);
  $form['ref_code'] = $form['codice'];
  if ($modal === false) {
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  } else {
    $form['ritorno'] = 'admin_destinazioni.php';
  }
  $form['search']['id_anagra'] = '';
} else { //se e' il primo accesso per INSERT
  $form = gaz_dbi_fields('destina');
  if ($modal === false) {
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  } else {
    $form['ritorno'] = 'admin_destinazioni.php';
  }
  if (isset($_POST['id_anagra']) && $_POST['id_anagra'] >= 1) { // vengo chiamato dal form di admin_client
    $anagra = gaz_dbi_get_row($gTables['anagra'],'id', intval($_POST['id_anagra']));
    $form['id_anagra']=$anagra['id'];
    $form['search']['id_anagra']=$anagra['ragso1'];
  } else {
    $form['search']['id_anagra'] = '';
  }
  $form['ref_code'] = "";
  $form['country'] = $admin_aziend['country'];
}
if ($modal === false) {
  require("../../library/include/header.php");
  $script_transl = HeadMain(0,['custom/autocomplete']);
} else {
  $script = basename($_SERVER['PHP_SELF']);
  require("../../language/" . $admin_aziend['lang'] . "/menu.inc.php");
  require("../vendit/lang." . $admin_aziend['lang'] . ".php");
  if (isset($script)) { // se è stato tradotto lo script lo ritorno al chiamante
    $script_transl = $strScript[$script];
  }
  $script_transl = $strCommon + $script_transl;
}

?>
<script>
$(function() {
  $("#search_id_anagra").autocomplete({
    html: true,
    source: "../../modules/root/search.php",
    minLength: 2,
    open: function(event, ui) {
      $(".ui-autocomplete").css("z-index", 1000);
    },
		select: function(event, ui) {
			$("#search_id_anagra").val(ui.item.value);
			$("#id_anagra").val(ui.item.id);
			$(this).closest("form").submit();
		}
  });
});
</script>
<form method="POST" name="form" enctype="multipart/form-data"  id="add-destina">
<?php
if ($modal === true) {
  echo '<input type="hidden" name="mode" value="modal" />
        <input type="hidden" name="mode-act" value="submit" />';
}
echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $form['ritorno'] . "\">\n";
echo "<input type=\"hidden\" name=\"ref_code\" value=\"" . $form['ref_code'] . "\">\n";
echo "<input type=\"hidden\" name=\"codice\" value=\"" . $form['codice'] . "\">\n";
echo "<input type=\"hidden\" name=\"" . ucfirst($toDo) . "\" value=\"\">";
if ($modal_ok_insert === true) {
  echo '<div class="alert alert-success" role="alert">' . $script_transl['modal_ok_insert'] . '</div>';
} else {
  $gForm = new GAzieForm();
  if ($toDo == 'insert') {
     echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['ins_this'] . "</div>\n";
  } else {
     echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['upd_this'] . " '" . $form['codice'] . "'</div>\n";
  }
  echo "<table class=\"Tmiddle table-striped\">\n";
  if (!empty($msg)) {
     echo '<tr><td colspan="3" class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
  }

  echo "<tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['id_anagra'] . " </td><td class=\"FacetDataTD\" colspan=\"2\">\n";
  $select_id_anagra = new selectPartner("id_anagra");
  $select_id_anagra->selectAnagra('id_anagra', $form['id_anagra'], $form['search']['id_anagra'], 'id_anagra', $script_transl['mesg']);
  echo "</td>\n";
  echo "</tr>\n";


  echo "<tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['unita_locale1'] . "</td>\n";
  echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
        <input type=\"text\" name=\"unita_locale1\" value=\"" . $form['unita_locale1'] . "\" align=\"right\" maxlength=\"255\"  /></td>\n";
  echo "</tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['unita_locale2'] . "</td>\n";
  echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
        <input type=\"text\" name=\"unita_locale2\" value=\"" . $form['unita_locale2'] . "\" align=\"right\" maxlength=\"255\"  /></td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['indspe'] . " * </td>\n";
  echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
        <input type=\"text\" name=\"indspe\" value=\"" . $form['indspe'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['capspe'] . " * </td>\n";
  echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
        <input type=\"text\" name=\"capspe\" codice=\"search_location-capspe\" value=\"" . $form['capspe'] . "\" align=\"right\" maxlength=\"10\"  /></td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['citspe'] . " *  </td>\n";
  echo "\t<td class=\"FacetDataTD\">
        <input type=\"text\" name=\"citspe\" codice=\"search_location\" value=\"" . $form['citspe'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
  echo "\t<td class=\"FacetDataTD\">
        <input type=\"text\" name=\"prospe\" codice=\"search_location-prospe\" value=\"" . $form['prospe'] . "\" align=\"right\" maxlength=\"2\"  /></td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['country'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
  $gForm->selectFromDB('country', 'country', 'iso', $form['country'], 'iso', 0, ' - ', 'name');
  echo "</td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['telefo'] . " </td>\n";
  echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
        <input type=\"text\" name=\"telefo\" value=\"" . $form['telefo'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['fax'] . " </td>\n";
  echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
        <input type=\"text\" name=\"fax\" value=\"" . $form['fax'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['cell'] . " </td>\n";
  echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
        <input type=\"text\" name=\"cell\" value=\"" . $form['cell'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['e_mail'] . "</td>\n";
  echo "\t<td class=\"FacetDataTD\" colspan=\"2\">
        <input type=\"text\" codice=\"email\" name=\"e_mail\" value=\"" . $form['e_mail'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\"><a href=\"https://www.indicepa.gov.it/ricerca/n-lista-uffici-figli.php\" target=\"blank\">" . $script_transl['fe_cod_ufficio'] . "</a></td>\n";
  echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
        <input type=\"text\" name=\"fe_cod_ufficio\" value=\"" . $form['fe_cod_ufficio'] . "\" align=\"right\" maxlength=\"7\"  /></td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['annota'] . "</td>\n";
  echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
        <input type=\"text\" name=\"annota\" value=\"" . $form['annota'] . "\" align=\"right\" maxlength=\"100\"  /></td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "<tr>\n";
  echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['sqn'] . "</td>";
  echo "\t </td>\n";
  echo "\t<td  class=\"FacetDataTD\">\n";
  echo '<input name="none" type="submit" value="" disabled>';
  echo "\t </td>\n";
  echo "\t<td  class=\"FacetDataTD\" align=\"right\">\n";
  echo '<input name="conferma" type="submit" class="btn btn-sm btn-warning" value=" ' . ucfirst($script_transl[$toDo]) . '">';
  echo "\t </td>\n";
  echo "</tr>\n";
}
?>
</table>
</form>
<?php
if ($modal) {
?>
<script type="text/javascript">
  $("#add-destina").submit(function (e)
  {
    $.ajax({
        type: "POST",
        url: "../vendit/admin_destinazioni.php",
        data: $("#add-destina").serialize(), // serializes the form's elements.
        success: function (data) {
            $("#edit-modal .modal-sm").css('width', '100%');
            $("#edit-modal .modal-body").html(data);
        }
    });
    e.preventDefault();
  });
</script>
<?php
	function get_rref_type($value) {
	}
  function pulisci_rref_name(){
  }
  function printCheckbox(){
  }
}
require("../../library/include/footer.php");
?>
