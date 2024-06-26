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

/* TIPI DI DOCUMENTO SELEZIONABILI PER LA RI/STAMPA:
  valori che si possono dare alla variabile "tipdoc" da passare tramite URL (metodo GET)
  1 => "D.d.T. di Vendita"
  2 => "Fattura Differita"
  3 => "Fattura Immediata Accompagnatoria"
  4 => "Fattura Immediata Semplice"
  5 => "Nota Credito a Cliente"
  6 => "Nota Debito a Cliente"
  7 => "Ricevuta"
 */
require("../../library/include/datlib.inc.php");

$admin_aziend = checkAdmin();

if (!isset($_POST['ckdata']))
   $_POST['ckdata'] = 0;
$msg = '';

if (!isset($_POST['ritorno'])) { //al primo accesso allo script
   $msg = '';
   $form['ritorno'] = $_SERVER['HTTP_REFERER'];
   $form['codcli'] = '';
   $form['ragso1'] = '';
   $form['anno'] = date("Y");
} else { // le richieste successive
   $form['ritorno'] = $_POST['ritorno'];
   $form['codcli'] = intval($_POST['codcli']);
   $form['ragso1'] = substr($_POST['ragso1'], 0, 15);
   $form['anno'] = $_POST['anno'];
}


if (isset($_POST['Print'])) {
   if (empty($form['anno'])) {
      $msg .= "0+";
   }
   if (empty($msg)) { //non ci sono errori
      $_SESSION['print_request'] = $form;
      $_SESSION['print_request']['ckdata']=$_POST['ckdata'];
      header("Location: invsta_analisi_acquisti_clienti.php");
      exit;
   }
}

if (isset($_POST['Return'])) {
   header("Location:stats_vendit.php");
   exit;
}
require("../../library/include/header.php");
$script_transl = HeadMain('', '', 'select_analisi_acquisti_clienti');
echo "<form method=\"POST\">";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $form['ritorno'] . "\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">Analisi acquisti clienti";
echo "</div>";
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
echo "<select name=\"codcli\" class=\"FacetSelect\">";
echo "\t\t <option value=\"\">$script_transl[2]</option>\n";
if (strlen($form['ragso1']) >= 2) {
   $mascon = $admin_aziend['mascli'] . '000000';
   $result = gaz_dbi_dyn_query("codice,ragso1,citspe", $gTables['clfoco'] . ' LEFT JOIN ' . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id', "codice like '" . $admin_aziend['mascli'] . "%' and codice > '$mascon'  and ragso1 like '" . addslashes($cerca) . "%'", "ragso1 desc");
   $numclfoco = gaz_dbi_num_rows($result);
   if ($numclfoco > 0) {
      $tabula = "";
      while ($a_row = gaz_dbi_fetch_array($result)) {
         $selected = "";
         if ($a_row["codice"] == $form['codcli'])
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

echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[5]</td>";
echo "<td class=\"FacetDataTD\">";
if ($_POST['ckdata'] == 0) {
   $checked0 = "checked";
   $checked1 = "";
} else {
   $checked1 = "checked";
   $checked0 = "";
}
echo "<input type=\"radio\" name=\"ckdata\" value=0 $checked0> Mensile \n";
echo "<input type=\"radio\" name=\"ckdata\" value=1 $checked1> Trimestrale \n";
echo "</td></tr>";

echo "<tr>\n
     <td class=\"FacetFieldCaptionTD\"><input type=\"submit\" name=\"Return\" value=\"" . ucfirst($script_transl['return']) . "\"></td>\n
     <td align=\"right\" class=\"FacetFooterTD\"><input type=\"submit\" name=\"Print\" value=\"" . ucfirst($script_transl['print']) . "\"></td>\n
     </tr>\n";
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>