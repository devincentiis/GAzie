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

require_once("../../library/include/datlib.inc.php");
require_once('../magazz/lib.function.php');

function elenca($form) {
   global $gTables, $elencoSconti;
   $tabellaSconti = $gTables['sconti_articoli'];
   $tabellaArticoli = $gTables['artico'];
   $tabellaClfoco = $gTables['clfoco'];
//   $tabellaAnagrafe = $gTables['anagra'];
   $where = "true";
   if (!empty($form['partner'])) {
      $codcli = $form['partner'];
      $where = $where . " and sconti.clfoco = '$codcli'";
   }
   if (!empty($form['cod_art'])) {
      $codart = $form['cod_art'];
      $where = $where . " and sconti.codart = '$codart'";
   }
   $query = "select sconti.clfoco as codclfoco, clfoco.descri as cliente, "
           . "sconti.codart as codart, articoli.descri as articolo, "
           . "sconti.sconto, sconti.prezzo_netto "
           . "from $tabellaSconti sconti "
           . "join $tabellaArticoli articoli on articoli.codice=sconti.codart "
           . "join $tabellaClfoco clfoco on clfoco.codice=sconti.clfoco "
           . "where $where "
           . "order by clfoco.descri, articoli.descri";
   $elencoSconti = gaz_dbi_query($query);
}

function inserisci($form) {
   global $gTables, $script_transl;
   $codcli = $form['partner'];
   $codart = $form['cod_art'];
   $sconto = $form['sconto'];
   $prezzo_netto = $form['prezzo_netto'];
   $tabella = $gTables['sconti_articoli'];
   $messaggi = $script_transl['mesg'];
   $valori = array('clfoco' => $codcli, 'codart' => $codart, 'sconto' => $sconto, 'prezzo_netto' => $prezzo_netto);
   if (gaz_dbi_record_count($tabella, "clfoco='$codcli' and codart='$codart'") == 0) { // sconto non presente, inserirlo
      gaz_dbi_table_insert('sconti_articoli', $valori);
      $msg = $messaggi[3];
   } else { //sconto presente, aggiornarlo
      gaz_dbi_put_query($tabella, "clfoco = '$codcli' and codart = '$codart'", "sconto", $sconto);
      gaz_dbi_put_query($tabella, "clfoco = '$codcli' and codart = '$codart'", "prezzo_netto", $prezzo_netto);
      $msg = $messaggi[4];
   }
   alert($msg);
}

$admin_aziend = checkAdmin();
$elencoSconti = null;
if (!isset($_POST['ckdata']))
   $_POST['ckdata'] = 0;
$msg = '';

if (!isset($_POST['ritorno'])) { //al primo accesso allo script
   $msg = '';
   $form['ritorno'] = $_SERVER['HTTP_REFERER'];
   $form['cod_art'] = "";
   $form['sconto'] = 0;
   $form['prezzo_netto'] = 0;
   $form['search']['cod_art'] = '';
   $form['search']['partner'] = '';
   $form['partner'] = 0;
   $form['hidden_req'] = '';
} else { // le richieste successive
   $form['hidden_req'] = $_POST['hidden_req'];
   $form['ritorno'] = $_POST['ritorno'];
   $form['cod_art'] = $_POST['cod_art'];
   $form['sconto'] = $_POST['sconto'];
   $form['prezzo_netto'] = $_POST['prezzo_netto'];
   $form['search']['partner'] = substr($_POST['search']['partner'], 0, 20);
   $form['partner'] = intval($_POST['partner']);

   foreach ($_POST['search'] as $k => $v) {
      $form['search'][$k] = $v;
   }
}


if (isset($_POST['Return'])) {
   header("Location:docume_vendit.php");
   exit;
}
require("../../library/include/header.php");
$script_transl = HeadMain();
echo "<script type=\"text/javascript\">
function clickAndDisable(link) {
   // disable subsequent clicks
   link.onclick = function(event) {
      event.preventDefault();
   }
 }  
</script>
";
if (isset($_POST['Elenca'])) {
//   if (empty($form['anno'])) {
//      $msg .= "0+";
//   }
   if (empty($msg)) { //non ci sono errori
      elenca($form);
   }
}

if (isset($_POST['Inserisci'])) {
   if (empty($form['partner'])) {
      $msg .= "0+";
   }
   if (empty($form['cod_art'])) {
      $msg .= "1+";
   }
   if (empty($form['sconto']) and empty($form['prezzo_netto'])) {
      $msg .= "2+";
   } elseif (($form['sconto'] <= 0) or ( $form['sconto'] > 100)) {
      $msg .= "3+";
   }
   if ($form['prezzo_netto'] < 0) {
      $msg .= "4+";
   }
   if (empty($msg)) { //non ci sono errori
      inserisci($form);
   }
}


$magForm = new magazzForm();
$vendForm = new venditForm();

echo "<form method=\"POST\">";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $form['ritorno'] . "\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'];
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

echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['partner'] . "</td><td colspan=\"3\" class=\"FacetDataTD\">\n";
$vendForm->selectCustomer('partner', $form['partner'], $form['search']['partner'], $form['hidden_req'], $script_transl['mesg']);
echo "</td>\n";
echo "</tr>\n";

echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['cod_art'] . "</td><td  class=\"FacetDataTD\">\n";
$magForm->selItem('cod_art', $form['cod_art'], $form['search']['cod_art'], $script_transl['mesg']);
echo "</tr>\n";

echo "<tr><td class=\"FacetFieldCaptionTD\"> $script_transl[6] </td><td  class=\"FacetDataTD\"> <input type=\"number\" step=\"any\" min=\"0\" value=\"" . $form['prezzo_netto'] . "\" maxlength=\"14\"  name=\"prezzo_netto\" ></td>";

echo "<tr><td class=\"FacetFieldCaptionTD\"> $script_transl[5] </td><td  class=\"FacetDataTD\"> <input type=\"number\" step=\"any\" min=\"0\" max=\"100\" value=\"" . $form['sconto'] . "\" maxlength=\"6\"  name=\"sconto\" ></td>";

echo "</td>\n
     </tr>\n";

echo "<tr>\n
    <td class=\"FacetFieldCaptionTD\"><input type=\"submit\" name=\"Return\" value=\"" . ucfirst($script_transl['return']) . "\"></td>\n
    <td align=\"right\" class=\"FacetFooterTD\">
    <input type=\"submit\" name=\"Elenca\" value=\"" . ucfirst($script_transl['elenca']) . "\">
    <input type=\"submit\" name=\"Inserisci\" value=\"" . ucfirst($script_transl['inserisci']) . "\">
       </td></tr>";
if (!empty($elencoSconti)) {
   echo "</table><table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
   $linkHeaders = new linkHeaders($script_transl['header']);
   $linkHeaders->setAlign(array('left', 'left', 'left', 'left', 'right', 'right', 'center'));
   $linkHeaders->output();
   foreach ($elencoSconti as $riga) {
      echo "<tr class=\"FacetDataTD\">";
      $campo = $riga['codclfoco'];
      echo "<td class=\"FacetDataTD\">$campo</td>";
      $campo = $riga['cliente'];
      echo "<td class=\"FacetDataTD\">$campo</td>";
      $campo = $riga['codart'];
      echo "<td class=\"FacetDataTD\">$campo</td>";
      $campo = $riga['articolo'];
      echo "<td class=\"FacetDataTD\">$campo</td>";
      $campo = gaz_format_number($riga['prezzo_netto']);
      echo "<td class=\"FacetDataTD\" align=\"right\">$campo</td>";
      $campo = gaz_format_number($riga['sconto']);
      echo "<td class=\"FacetDataTD\" align=\"right\">$campo</td>";
      echo "<td class=\"FacetDataTD\" align=\"center\"><a class=\"btn btn-xs  btn-elimina\" "
      . "title=\"Cancella sconto\" "
      . "href=\"delete_sconto_articolo.php?"
      . "codclfoco=" . $riga['codclfoco']
      . "&clfoco=" . $riga['cliente']
      . "&codart=" . $riga['codart']
      . "&descrart=" . $riga['articolo']
      . "&sconto=" . gaz_format_number($riga['sconto'])
      . "&prezzo_netto=" . gaz_format_number($riga['prezzo_netto'])
      . "\" "
      . "onclick=\"clickAndDisable(this);\" "
      . "target=\"_blank\" >"
      . "<i class=\"glyphicon glyphicon-trash\"></i></a></td>";
      echo "</tr>";
   }
}
?>
</table>

</form>
<?php
require("../../library/include/footer.php");
?>