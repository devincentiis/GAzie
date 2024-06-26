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

$tipdoc=[
0 => "Tutti i documenti",
1 => "Fattura Differita",
2 => "Fattura Immediata",
3 => "Nota Credito a Cliente",
4 => "Nota Debito a Cliente",
5 => "Parcella",
6 => "D.d.T.",
7 => "Corrispettivo"
];

require("../../library/include/datlib.inc.php");

$admin_aziend = checkAdmin();

$msg = '';

function getLastDocument($tipo, $sezione, $anno) {
   //recupero l'ultimo documento dello stesso tipo emesso nell'anno
   global $gTables;
   switch ($tipo) {
      case 0:  // tutti
         $where = "tipdoc LIKE 'F__' AND YEAR(datfat) = $anno";
         $orderby= "datfat DESC, protoc DESC";
         break;
      case 1:  //fattura differita
         $where = "tipdoc = 'FAD' AND YEAR(datfat) = $anno";
         $orderby= "datfat DESC, protoc DESC";
         break;
      case 2:  //fattura immediata
         $where = "(tipdoc = 'FAI' OR tipdoc = 'FAA' ) AND YEAR(datfat) = $anno";
         $orderby= "datfat DESC, protoc DESC";
         break;
      case 3: //nota di credito
         $where = "tipdoc = 'FNC' AND YEAR(datfat) = $anno";
         $orderby= "datfat DESC, protoc DESC";
         break;
      case 4: //nota di debito
         $where = "tipdoc = 'FND' AND YEAR(datfat) = $anno";
         $orderby= "datfat DESC, protoc DESC";
         break;
      case 5: //parcella
         $where = "tipdoc = 'FAP' AND YEAR(datfat) = $anno";
         $orderby= "datfat DESC, protoc DESC";
         break;
      case 6: //ddt
         $where = "tipdoc LIKE 'DD_' AND YEAR(datemi) = $anno";
         $orderby= "datemi DESC, numdoc DESC";
         break;
      case 7: //corrispettivo
         $where = "tipdoc LIKE 'VCO' AND YEAR(datemi) = $anno";
         $orderby= "datemi DESC, numdoc DESC";
         break;
   }
   $rs_lastdoc = gaz_dbi_dyn_query("*", $gTables['tesdoc'], $where . " AND seziva = $sezione", $orderby, 0, 1);
   $last = gaz_dbi_fetch_array($rs_lastdoc);
   if ($last) {
      $last['protoc'] = $last['protoc'];
      $last['numero'] = ($tipo==6)?$last['numdoc']:$last['numfat'];
      $last['data_fine'] = ($tipo==6)?$last['datemi']:$last['datfat'];
   } else {
      $last['protoc'] = 1;
      $last['numero'] = 1;
      $last['data_fine'] = date("Y-m-d");
   }
   return array('protoc' => $last['protoc'], 'numero' => $last['numero'], 'datfin' => $last['data_fine']);
}


if (!isset($_POST['ritorno'])) { //al primo accesso allo script
   $msg = '';
   $form['ritorno'] = $_SERVER['HTTP_REFERER'];
   $form['hidden_req'] = '';
   if (isset($_GET['seziva'])) {
      $form['seziva'] = intval($_GET['seziva']);
   } else {
      $form['seziva'] = 1;
   }
   $form['codcli'] = '';
   $form['ragso1'] = '';
   if (isset($_GET['tipdoc'])) {
      $form['tipdoc'] = intval($_GET['tipdoc']);
   } else {
      $form['tipdoc'] = 0; //tutte le fatture
   }
   $last = getLastDocument($form['tipdoc'], $form['seziva'], date("Y"));
   if (isset($_GET['datini'])) {
      $form['gioini'] = substr($_GET['datini'], 6, 2);
      $form['mesini'] = substr($_GET['datini'], 4, 2);
      $form['annini'] = substr($_GET['datini'], 0, 4);
   } else {
      $form['gioini'] = 1;
      $form['mesini'] = substr($last['datfin'], 5, 2);
      $form['annini'] = substr($last['datfin'], 0, 4);
   }
   // controllo se un'altro script passa dei valori tramite URL per facilitare la scelta
   // ad esempio in fase di generazione e stampa fatture differite
   if (isset($_GET['proini'])) {
      $form['proini'] = intval($_GET['proini']);
   } else {
      $form['proini'] = 0;
   }
   if (isset($_GET['numini'])) {
      $form['numini'] = intval($_GET['numini']);
   } else {
      $form['numini'] = 1;
   }
   if (isset($_GET['datfin'])) {
      $form['giofin'] = substr($_GET['datfin'], 6, 2);
      $form['mesfin'] = substr($_GET['datfin'], 4, 2);
      $form['annfin'] = substr($_GET['datfin'], 0, 4);
   } else {
      $form['giofin'] = substr($last['datfin'], 8, 2);
      $form['mesfin'] = substr($last['datfin'], 5, 2);
      $form['annfin'] = substr($last['datfin'], 0, 4);
   }
   if (isset($_GET['profin'])) {
      $form['profin'] = intval($_GET['profin']);
   } else {
      $form['profin'] = $last['protoc'];
   }
   if (isset($_GET['numfin'])) {
      $form['numfin'] = intval($_GET['numfin']);
   } else {
      $form['numfin'] = $last['numero'];
   }

   $form['id_agente'] = 0;
   $form['caumag'] = 0;
} else { // le richieste successive

   $form['ritorno'] = $_POST['ritorno'];
   $form['hidden_req'] = $_POST['hidden_req'];
   $form['seziva'] = intval($_POST['seziva']);
   $form['codcli'] = intval($_POST['codcli']);
   $form['ragso1'] = substr($_POST['ragso1'], 0, 15);
   $form['tipdoc'] = intval($_POST['tipdoc']);
   $form['gioini'] = intval($_POST['gioini']);
   $form['mesini'] = intval($_POST['mesini']);
   $form['annini'] = intval($_POST['annini']);
   $form['giofin'] = intval($_POST['giofin']);
   $form['mesfin'] = intval($_POST['mesfin']);
   $form['annfin'] = intval($_POST['annfin']);
   $form['proini'] = intval($_POST['proini']);
   $form['numini'] = intval($_POST['numini']);
   $form['profin'] = intval($_POST['profin']);
   $form['numfin'] = intval($_POST['numfin']);
   $form['id_agente'] = intval($_POST['id_agente']);
  // Se viene inviata la richiesta di cambio tipo
  if ($_POST['hidden_req'] == 'change_tipo') {
    $last = getLastDocument($form['tipdoc'], $form['seziva'], date("Y"));
    if ($last){
      $form['gioini'] = 1;
      $form['mesini'] = substr($last['datfin'], 5, 2);
      $form['annini'] = substr($last['datfin'], 0, 4);
      $form['proini'] = 0;
      $form['numini'] = 1;
      $form['giofin'] = substr($last['datfin'], 8, 2);
      $form['mesfin'] = substr($last['datfin'], 5, 2);
      $form['annfin'] = substr($last['datfin'], 0, 4);
      $form['profin'] = $last['protoc'];
      $form['numfin'] = $last['numero'];
    }
    $form['hidden_req'] = '';
  }
}

if (isset($_POST['Print'])) {
   //Mando in stampa le fatture generate solo se non ci sono errori
   if ($form['numini'] <= 0) {
      $msg .= "12+";
   }
   if ($form['numfin'] < $form['numini']) {
      $msg .= "13+";
   }
   if ($form['proini'] < 0) {
      $msg .= "14+";
   }
   if ($form['profin'] < $form['proini']) {
      $msg .= "15+";
   }
   if (!checkdate($form['mesini'], $form['gioini'], $form['annini'])) {
      $msg .= "16+";
   }
   if (!checkdate($form['mesfin'], $form['giofin'], $form['annfin'])) {
      $msg .= "17+";
   }
   $utsini = mktime(0, 0, 0, $form['mesini'], $form['gioini'], $form['annini']);
   $utsfin = mktime(0, 0, 0, $form['mesfin'], $form['giofin'], $form['annfin']);
   if ($utsini > $utsfin) {
      $msg .="18+";
   }
   if (empty($msg)) {
      $datini = sprintf("%04d%02d%02d", $form['annini'], $form['mesini'], $form['gioini']);
      $datfin = sprintf("%04d%02d%02d", $form['annfin'], $form['mesfin'], $form['giofin']);
   }
   if (empty($msg)) { //non ci sono errori
      unset($form['gioini'], $form['giofin'], $form['mesini'], $form['mesfin'], $form['annini'], $form['annfin']);
      $form['datini'] = $datini;
      $form['datfin'] = $datfin;
      $tipi = unserialize(base64_decode($_POST['serialized_tipdoc']));
      $form['titolo'] = $tipi[$form['tipdoc']];
      $_SESSION['print_request'] = $form;
      header("Location: invsta_doclist.php");
      exit;
   }
}

if (isset($_POST['Return'])) {
   header("Location:report_docven.php");
   exit;
}
require("../../library/include/header.php");
$script_transl = HeadMain('', '', 'select_docforprint');
echo "<form method=\"POST\">";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $form['ritorno'] . "\">\n";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">Stampa elenco documenti gi&agrave; emessi " . $script_transl[1];
echo "<select name=\"seziva\" class=\"FacetFormHeaderFont\">\n";
for ($counter = 1; $counter <= 9; $counter++) {
   $selected = "";
   if ($counter == $form['seziva']) {
      $selected = " selected ";
   }
   echo "<option value=\"" . $counter . "\"" . $selected . ">" . $counter . "</option>\n";
}
echo "</select>\n";
echo "</div>";
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
   echo '<tr><td colspan="5" class="FacetDataTDred">' . $message . '</td></tr>';
}
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[7] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "<select name=\"tipdoc\" class=\"FacetSelect\" onchange=\"this.form.hidden_req.value='change_tipo'; this.form.submit();\">\n";
for ($counter = 0; $counter <= 7; $counter++) {
   $selected = '';
   if ($form['tipdoc'] == $counter) {
      $selected = "selected";
   }
   echo "\t\t <option value=\"" . $counter . "\" $selected >" . $tipdoc[$counter] . "</option>\n";
}
echo "</select></td></tr>\n";
$serialized_tipdoc = base64_encode(serialize($tipdoc));
echo "<input type='hidden' value='$serialized_tipdoc' name='serialized_tipdoc'>";
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[8]</td>";
echo "<td class=\"FacetDataTD\">";
$messaggio = '';
$tabula = " tabindex=\"1\" ";
$cerca = $form['ragso1'];
echo "<select name=\"codcli\" class=\"FacetSelect\">";
echo "\t\t <option value=\"\">$script_transl[10]</option>\n";
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
      $messaggio = $script_transl[11];
} else {
   $messaggio = $script_transl[9];
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
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[2] $script_transl[5]</td>";
echo "<td class=\"FacetDataTD\">";
// select del giorno
echo "\t <select name=\"gioini\" class=\"FacetSelect\">\n";
for ($counter = 1; $counter <= 31; $counter++) {
   $selected = "";
   if ($counter == $form['gioini'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
// select del mese
echo "\t <select name=\"mesini\" class=\"FacetSelect\">\n";
$gazTimeFormatter->setPattern('MMMM');
for ($counter = 1; $counter <= 12; $counter++) {
  $selected = "";
  if ($counter == $form['mesini']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
// select del anno
echo "\t <select name=\"annini\" class=\"FacetSelect\">\n";
for ($counter = date("Y") - 10; $counter <= date("Y") + 10; $counter++) {
   $selected = "";
   if ($counter == $form['annini'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}

echo "\t </select>\n";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[2] $script_transl[6]</td>";
echo "<td class=\"FacetDataTD\">";
// select del giorno
echo "\t <select name=\"giofin\" class=\"FacetSelect\">\n";
for ($counter = 1; $counter <= 31; $counter++) {
   $selected = "";
   if ($counter == $form['giofin'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
// select del mese
echo "\t <select name=\"mesfin\" class=\"FacetSelect\">\n";
for ($counter = 1; $counter <= 12; $counter++) {
  $selected = "";
  if ($counter == $form['mesfin']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
// select del anno
echo "\t <select name=\"annfin\" class=\"FacetSelect\">\n";
for ($counter = date("Y") - 10; $counter <= date("Y") + 10; $counter++) {
   $selected = "";
   if ($counter == $form['annfin'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select>\n";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[4] $script_transl[5]</td>";
echo "<td class=\"FacetDataTD\">";
echo "<input title=\"Numero del primo documento che si intende stampare\" type=\"text\" name=\"numini\" value=\"" .
 $form["numini"] . "\" maxlength=\"5\"  class=\"FacetInput\">";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[4] $script_transl[6]</td>";
echo "<td class=\"FacetDataTD\">";
echo "<input title=\"Numero dell'ultimo documento che si intende stampare\" type=\"text\" name=\"numfin\" value=\"" .
 $form["numfin"] . "\" maxlength=\"5\"  class=\"FacetInput\">";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[3] $script_transl[5]</td>";
echo "<td class=\"FacetDataTD\">";
echo "<input title=\"Numero di protocollo della prima fattura che si intende stampare\" type=\"text\" name=\"proini\" value=\"" .
 $form["proini"] . "\" maxlength=\"5\"  class=\"FacetInput\">";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[3] $script_transl[6]</td>";
echo "<td class=\"FacetDataTD\">";
echo "<input title=\"Numero di protocollo dell'ultima fattura che si intende stampare\" type=\"text\" name=\"profin\" value=\"" .
 $form["profin"] . "\" maxlength=\"5\"  class=\"FacetInput\">";
echo "</td></tr>";

echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['id_agente'] . "</td>";
echo "<td  class=\"FacetDataTD\">\n";
$select_agente = new selectAgente("id_agente");
$select_agente->addSelected($form["id_agente"]);
$select_agente->output();
echo "</td></tr>\n";

echo "<tr>\n
     <td class=\"FacetFooterTD text-center\" colspan=2><input type=\"submit\" class=\"btn btn-warning\" name=\"Print\" value=\"" . ucfirst($script_transl['print']) . "\"></td>\n
     </tr>\n";
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
