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

function salvaClientiSelezionati() {
   global $gTables;
   $clfoco = $gTables['clfoco'];
// deselezioniamo tutti i clienti
   $query = "update $clfoco clfoco set sel4esp_art=false";
   $rs = gaz_dbi_query($query);
// selezioniamo solo i clienti che hanno la spunta
   $name = $_POST['checkCodice'];
   foreach ($name as $cliente) {
      $query = "update $clfoco clfoco set sel4esp_art=true where codice=$cliente";
      $rs = gaz_dbi_query($query);
   }
}

function esportaFattureVendita() {
   global $gTables;
   $tesdoc = $gTables['tesdoc'];
   $rigdoc = $gTables['rigdoc'];
   $artico = $gTables['artico'];
   $clfoco = $gTables['clfoco'];
   $fornitore = intval($_POST['codfor']);
   $anno = $_POST['anno'];
   $query = "select tesdoc.clfoco, tesdoc.datemi, rigdoc.codart, rigdoc.unimis, rigdoc.quanti "
           . "from $tesdoc tesdoc "
           . "join $rigdoc rigdoc on tesdoc.id_tes=rigdoc.id_tes "
           . "join $artico artico on rigdoc.codart=artico.codice "
           . "where artico.clfoco='$fornitore' and "
           . "year(tesdoc.datemi)=$anno and "
           . "(tipdoc like 'DD_' or tipdoc = 'FAD') and "
           . "tesdoc.clfoco in (select codice from $clfoco clfoco where sel4esp_art)";
   $rs = gaz_dbi_query($query);
   while ($row = gaz_dbi_fetch_array($rs, 'NUM'))
      $rows[] = $row;

   $_SESSION['rs_ftven'] = $rows; //$rs;
   echo "<script type='text/javascript'>window.open('export_ft_ven.php');</script>";
}

function esportaAnagrafeArticoli() {
   global $gTables;
   $tesdoc = $gTables['tesdoc'];
   $rigdoc = $gTables['rigdoc'];
   $artico = $gTables['artico'];
   $clfoco = $gTables['clfoco'];
   $fornitore = intval($_POST['codfor']);
   $anno = $_POST['anno'];
//   $query = "select artico.codice, artico.descri "
//           . "from $artico artico "
//           . "where artico.clfoco='$fornitore'";
   $query = "select distinct artico.codice, artico.descri "
           . "from $tesdoc tesdoc "
           . "join $rigdoc rigdoc on tesdoc.id_tes=rigdoc.id_tes "
           . "join $artico artico on rigdoc.codart=artico.codice "
           . "where artico.clfoco='$fornitore' and "
           . "year(tesdoc.datemi)=$anno and "
           . "(tipdoc like 'DD_' or tipdoc = 'FAD') and "
           . "tesdoc.clfoco in (select codice from $clfoco clfoco where sel4esp_art)";
   $rs = gaz_dbi_query($query);
   while ($row = gaz_dbi_fetch_array($rs, 'NUM'))
      $rows[] = $row;

   $_SESSION['rs_artven'] = $rows; //$rs;
   echo "<script type='text/javascript'>window.open('export_art_ven.php');</script>";
}

function esportaAnagrafeClienti() {
   global $gTables,$mascli;
   $tesdoc = $gTables['tesdoc'];
   $rigdoc = $gTables['rigdoc'];
   $artico = $gTables['artico'];
   $clfoco = $gTables['clfoco'];
   $anagra = $gTables['anagra'];
   $fornitore = intval($_POST['codfor']);
   $anno = $_POST['anno'];
   $query = "SELECT clfoco.codice,clfoco.descri AS ragsoc, anagra.sedleg, "
           . "anagra.indspe, anagra.capspe, anagra.citspe, anagra.prospe, "
           . "anagra.codfis, anagra.pariva, "
           . "anagra.telefo, anagra.fax, anagra.e_mail, anagra.cell "
           . "FROM $clfoco clfoco join $anagra anagra on `id_anagra`=anagra.id "
           . "where clfoco.codice like '$mascli%' and clfoco.sel4esp_art "
           . "order by clfoco.descri";
   $rs = gaz_dbi_query($query);
   while ($row = gaz_dbi_fetch_array($rs, 'NUM'))
      $rows[] = $row;

   $_SESSION['rs_cliven'] = $rows; //$rs;
   echo "<script type='text/javascript'>window.open('export_cli_ven.php');</script>";
}

if (!isset($_POST['ckdata']))
   $_POST['ckdata'] = 0;
$msg = '';

if (!isset($_POST['ritorno'])) { //al primo accesso allo script
   $msg = '';
   $form['ritorno'] = $_SERVER['HTTP_REFERER'];
   $form['codfor'] = '';
   $form['ragso1'] = '';
   $form['anno'] = date("Y");
} else { // le richieste successive
   $form['ritorno'] = $_POST['ritorno'];
   $form['codfor'] = intval($_POST['codfor']);
   $form['ragso1'] = substr($_POST['ragso1'], 0, 15);
   $form['anno'] = $_POST['anno'];
}


if (isset($_POST['Esporta'])) {
   salvaClientiSelezionati(); // salvo anche se ci sono errori per evitare di perdere le selezioni effettuate
   if (empty($form['anno'])) {
      $msg .= "0+";
   }
   if (empty($form['codfor'])) {
      $msg .= "1+";
   }
   if (empty($msg)) { //non ci sono errori
      $_SESSION['print_request'] = $form;
      $_SESSION['print_request']['ckdata'] = $_POST['ckdata'];
      esportaFattureVendita();
      esportaAnagrafeArticoli();
      esportaAnagrafeClienti();
//      header("Location: ".$form['ritorno']);
//      echo "<a href='" . $form['ritorno'] . "'>" . $script_transl['fine'] . "</a>";
//      exit;
   }
}

if (isset($_POST['Return'])) {
   header("Location:docume_vendit.php");
   exit;
}
require("../../library/include/header.php");
$script_transl = HeadMain(); //'', '', 'select_esportazione_articoli_venduti');
echo "<form method=\"POST\">";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $form['ritorno'] . "\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tmiddle table-striped\" align=\"center\">";
if (!empty($msg)) {
   $message = "";
   $rsmsg = array_slice(explode('+', chop($msg)), 0, -1);
   foreach ($rsmsg as $value) {
      $message .= $script_transl['error'] . "! -> ";
      $rsval = explode('-', chop($value));
      foreach ($rsval as $valmsg) {
         $message .= $script_transl['errors'][$valmsg] . " ";
      }
      $message .= "<br>";
   }
   echo '<tr><td colspan="5" class="FacetDataTDred">' . $message . '</td></tr>';
}
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[0]</td>";
echo "<td class=\"FacetDataTD\">";
$messaggio = '';
$tabula = " tabindex=\"1\" ";
$cerca = $form['ragso1'];
echo "<select name=\"codfor\" class=\"FacetSelect\">";
echo "\t\t <option value=\"\">$script_transl[2]</option>\n";
if (strlen($form['ragso1']) >= 2) {
   $mascon = $admin_aziend['masfor'] . '000000';
   $result = gaz_dbi_dyn_query("codice,ragso1,citspe", $gTables['clfoco'] . ' LEFT JOIN ' . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id', "codice like '" . $admin_aziend['masfor'] . "%' and codice > '$mascon'  and ragso1 like '" . addslashes($cerca) . "%'", "ragso1 desc");
   $numclfoco = gaz_dbi_num_rows($result);
   if ($numclfoco > 0) {
      $tabula = "";
      while ($a_row = gaz_dbi_fetch_array($result)) {
         $selected = "";
         if ($a_row["codice"] == $form['codfor'])
            $selected = "selected";
         echo "\t\t <option value=\"" . $a_row["codice"] . "\" $selected >" . $a_row["ragso1"] . "&nbsp;" . $a_row["citspe"] . "</option>\n";
      }
   } else
      $messaggio = $script_transl[3];
} else {
   $messaggio = $script_transl[1];
}
echo "\t </select>\n";
echo "\t<input type=\"text\" name=\"ragso1\" " . $tabula . " accesskey=\"e\" value=\"" . $form['ragso1'] . "\" maxlength=\"15\"  class=\"FacetInput\">\n";
echo $messaggio;
//echo "\t <input type=\"image\" align=\"middle\" accesskey=\"c\" ".$tabula." name=\"clfoco\" src=\"../../library/images/cerbut.gif\">\n";
/** ENRICO FEDELE */
/* Cambio l'aspetto del pulsante per renderlo bootstrap, con glyphicon */
echo '&nbsp;<button type="submit" class="btn btn-default btn-sm" name="clfoco" accesskey="c" ' . $tabula . '><i class="glyphicon glyphicon-search"></i></button>';
/** ENRICO FEDELE */
echo "</td></tr>";

echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[4]</td>";
echo "<td class=\"FacetDataTD\">";
echo "<input title=\"anno da analizzare\" type=\"text\" name=\"anno\" value=\"" .
 $form["anno"] . "\" maxlength=\"5\"  class=\"FacetInput\">";
echo "</td></tr>";

//echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[5]</td>";
//echo "<td class=\"FacetDataTD\">";
//if ($_POST['ckdata'] == 0) {
//   $checked0 = "checked";
//   $checked1 = "";
//} else {
//   $checked1 = "checked";
//   $checked0 = "";
//}
//echo "<input type=\"radio\" name=\"ckdata\" value=0 $checked0> Mensile \n";
//echo "<input type=\"radio\" name=\"ckdata\" value=1 $checked1> Trimestrale \n";
//echo "</td></tr>";

echo "<tr>\n
     <td class=\"FacetFieldCaptionTD\"><input type=\"submit\" name=\"Return\" value=\"" . ucfirst($script_transl['return']) . "\"></td>\n
     <td align=\"right\" class=\"FacetFooterTD\"><input type=\"submit\" name=\"Esporta\" value=\"" . ucfirst($script_transl['esporta']) . "\"></td>\n
     </tr>\n";
echo "</table>";

$clfoco = $gTables['clfoco'];
$anagra = $gTables['anagra'];
$mascli = $admin_aziend['mascli'];
$query = "SELECT clfoco.codice,clfoco.descri AS ragsoc,anagra.sedleg, anagra.telefo, anagra.cell, sel4esp_art "
        . "FROM $clfoco clfoco join $anagra anagra on `id_anagra`=anagra.id "
        . "where clfoco.codice like '$mascli%' "
        . "order by clfoco.descri";
$rs = gaz_dbi_query($query);


echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['titleLista'];
echo "</div>\n";
echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
echo "<tr>";
$linkHeaders = new linkHeaders($script_transl['header']);
$linkHeaders->output();
echo "</tr>";
$arrayCodici = array();
while ($mv = gaz_dbi_fetch_array($rs)) {
   echo "<tr>";
   $partner = $mv['ragsoc'] . " - " . $mv['sedleg'] . " - " . $mv['telefo'];
   $codice = $mv['codice'];
   $checked = ($mv['sel4esp_art'] ? "checked" : "");
   echo "<td>" . $codice . " &nbsp;</td>";
   echo "<td>" . $partner . " &nbsp;</td>";
   echo "<td align=\"center\"><input type=\"checkbox\" name=\"checkCodice[]\" value=$codice $checked> &nbsp;</td>";
   echo "</tr>\n";
}
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>