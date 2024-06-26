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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
$admin_aziend = checkAdmin();
$msg = '';

function getExtremeValue($table_name, $min_max = 'MIN') {
   $rs = gaz_dbi_dyn_query($min_max . '(codice) AS value', $table_name);
   $data = gaz_dbi_fetch_array($rs);
   return $data['value'];
}

function selezionaOrdine($sceltaOrdine, $order) {
   global $gTables;

   $daAggiungere = "";
   switch ($sceltaOrdine) {
      case 1:  // codice
         $daAggiungere = $gTables['artico'] . ".codice ASC";
         break;
      case 2:  // descrizione
         $daAggiungere = $gTables['artico'] . ".descri ASC";
         break;
      case 3:  // categoria
         $daAggiungere = "catmer ASC";
         break;
      default:
         break;
   }
   if (!empty($daAggiungere)) {
      $daAggiungere = (empty($order) ? "" : ",") . $daAggiungere;
   }
   return $order . $daAggiungere;
}

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
   $form['hidden_req'] = '';
   $form['ritorno'] = $_SERVER['HTTP_REFERER'];
   $form['this_date_Y'] = date("Y");
   $form['this_date_M'] = date("m");
   $form['this_date_D'] = date("d");
   if (isset($_GET['li'])) {
      $form['listino'] = intval($_GET['lis']);
   } else {
      $form['listino'] = 1;
   }
   if (isset($_GET['id'])) {
      $item = gaz_dbi_get_row($gTables['artico'], 'codice', substr($_GET['id'], 0, 15));
      $form['art_ini'] = $item['codice'];
      $form['art_fin'] = $item['codice'];
      $form['cm_ini'] = $item['catmer'];
      $form['cm_fin'] = $item['catmer'];
   } else {
      if (isset($_GET['ai'])) {
         $form['art_ini'] = substr($_GET['ai'], 0, 15);
      } else {
         $form['art_ini'] = getExtremeValue($gTables['artico']);
      }
      if (isset($_GET['af'])) {
         $form['art_fin'] = substr($_GET['af'], 0, 15);
      } else {
         $form['art_fin'] = getExtremeValue($gTables['artico'], 'MAX');
      }
      if (isset($_GET['ci'])) {
         $form['cm_ini'] = intval($_GET['ci']);
      } else {
         $form['cm_ini'] = getExtremeValue($gTables['catmer']);
      }
      if (isset($_GET['cf'])) {
         $form['cm_fin'] = intval($_GET['cf']);
      } else {
         $form['cm_fin'] = getExtremeValue($gTables['catmer'], 'MAX');
      }
   }
   $form['search']['art_ini'] = '';
   $form['search']['art_fin'] = '';
   /** inizio modifica FP 28/11/2015
    * filtro per fornitore ed ordinamento
    */
   $form['id_anagra'] = '';
   $form['search']['id_anagra'] = '';
   $form['ordine1'] = 0;
   $form['ordine2'] = 0;
   $form['ordine3'] = 0;
   $form['tipoStampa'] = 1;

   /** fine modifica FP */
} else { // accessi successivi
   $form['hidden_req'] = htmlentities($_POST['hidden_req']);
   $form['ritorno'] = $_POST['ritorno'];
   $form['this_date_Y'] = intval($_POST['this_date_Y']);
   $form['this_date_M'] = intval($_POST['this_date_M']);
   $form['this_date_D'] = intval($_POST['this_date_D']);
   $form['listino'] = substr($_POST['listino'], 0, 3);
   $form['cm_ini'] = intval($_POST['cm_ini']);
   $form['cm_fin'] = intval($_POST['cm_fin']);
   $form['art_ini'] = substr($_POST['art_ini'], 0, 15);
   $form['art_fin'] = substr($_POST['art_fin'], 0, 15);

   $form['id_anagra'] = $_POST['id_anagra'];
   $form['ordine1'] = $_POST['ordine1'];
   $form['ordine2'] = $_POST['ordine2'];
   $form['ordine3'] = $_POST['ordine3'];
   $form['tipoStampa'] = $_POST['tipoStampa'];

   foreach ($_POST['search'] as $k => $v) {
      $form['search'][$k] = $v;
   }
   if (isset($_POST['export'])) {
    $what = $gTables['catmer'] . ".codice AS codcat , " . $gTables['catmer'] . ".descri AS descat , " .
            $gTables['artico'] . ".codice AS codart," . $gTables['artico'] . ".descri AS desart," . $gTables['artico'] . ".* , " .
            $gTables['aliiva'] . ".codice AS codiva, " . $gTables['aliiva'] . ".aliquo ";
    $table = $gTables['artico'] . " LEFT JOIN " . $gTables['catmer'] . " ON (" . $gTables['artico'] . ".catmer = " . $gTables['catmer'] . ".codice)
             LEFT JOIN " . $gTables['aliiva'] . " ON (" . $gTables['artico'] . ".aliiva = " . $gTables['aliiva'] . ".codice)";
    $where = "catmer BETWEEN '" . $form['cm_ini'] .
            "' AND '" . $form['cm_fin'] .
            "' AND " . $gTables['artico'] . ".codice BETWEEN '" . $form['art_ini'] .
            "' AND '" . $form['art_fin'] . "' AND id_assets = 0 AND movimentabile <> 'N'";
    $titoloAddizionale = "";
    if (intval($form['id_anagra']) > 0) {
       $where = $where . " and " . $gTables['artico'] . ".clfoco='" . intval($form['id_anagra'])  . "'";
       $titoloAddizionale = " - Fornitore: "  . $form['search']['id_anagra'] ;
    }
    $order = "";
    if ($form['ordine1'] >= 1) {
       $order = selezionaOrdine($form['ordine1'], $order);
    }
    if ($form['ordine2'] >= 1) {
       $order = selezionaOrdine($form['ordine2'], $order);
    }
    if ($form['ordine3'] >= 1) {
       $order = selezionaOrdine($form['ordine3'], $order);
    }
    if (empty($order)) {
       $order = "catmer ASC," . $gTables['artico'] . ".codice ASC";
    }
    $result = gaz_dbi_dyn_query($what, $table, $where, $order);
    $acc=[];
		while ($row = gaz_dbi_fetch_assoc($result)) {
      if (count($acc)==0){ $acc[0] = array_keys($row); }
      $acc[$row['codart']]=$row;
    }
    $spreadsheet = new Spreadsheet();
    $spreadsheet->setActiveSheetIndex(0);
    $sheet = $spreadsheet->getActiveSheet()->fromArray($acc);
    //mime type
    header('Content-Type: application/vnd.ms-excel');
    //tell browser what's the file name
    header('Content-Disposition: attachment;filename="Listino_'.$form['listino'].'.xls"');
    header('Cache-Control: max-age=0'); // no cache
    $writer = new Xls($spreadsheet);
    //force user to download the Excel file without writing it to server's HD
    $writer->save('php://output');
    exit;
  }
}

//controllo i campi
if (!checkdate($form['this_date_M'], $form['this_date_D'], $form['this_date_Y'])) {
   $msg .='0+';
}
$utsexe = mktime(0, 0, 0, $form['this_date_M'], $form['this_date_D'], $form['this_date_Y']);
if (strcasecmp($form['art_ini'], $form['art_fin']) > 0) {
   $msg .='1+';
}
if ($form['cm_ini'] > $form['cm_fin']) {
   $msg .='2+';
}
// fine controlli

if (isset($_POST['print']) && $msg == '') {
   if ($form['art_fin'] == 0) {
      $form['art_fin'] == $form['art_ini'];
   }
   $_SESSION['print_request'] = array('script_name' => 'stampa_listin',
       'li' => $form['listino'],
       'ai' => $form['art_ini'],
       'af' => $form['art_fin'],
       'ci' => $form['cm_ini'],
       'cf' => $form['cm_fin'],
       'ds' => date("dmY", $utsexe),
       'fo' => $form['id_anagra'],
       'fn' => $form['search']['id_anagra'],
       'o1' => $form['ordine1'],
       'o2' => $form['ordine2'],
       'o3' => $form['ordine3'],
       'ts' => $form['tipoStampa']
   );
   header("Location: sent_print.php");
   exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup'));
echo "<script type=\"text/javascript\">
var cal = new CalendarPopup();
var calName = '';
function setMultipleValues(y,m,d) {
     document.getElementById(calName+'_Y').value=y;
     document.getElementById(calName+'_M').selectedIndex=m*1-1;
     document.getElementById(calName+'_D').selectedIndex=d*1-1;
}
function setDate(name) {
  calName = name.toString();
  var year = document.getElementById(calName+'_Y').value.toString();
  var month = document.getElementById(calName+'_M').value.toString();
  var day = document.getElementById(calName+'_D').value.toString();
  var mdy = month+'/'+day+'/'+year;
  cal.setReturnFunction('setMultipleValues');
  cal.showCalendar('anchor', mdy);
}
</script>
";
echo "<form method=\"POST\" name=\"select\">\n";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['ritorno'] . "\" name=\"ritorno\" />\n";
$gForm = new magazzForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tsmall\" align=\"center\">\n";
if (!empty($msg)) {
   echo '<tr><td colspan="2" class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['date'] . "</td><td  class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('this_date', $form['this_date_D'], $form['this_date_M'], $form['this_date_Y'], 'FacetSelect', 1);
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['listino'] . "</td><td  class=\"FacetDataTD\">\n";
$gForm->variousSelect('listino', $script_transl['listino_value'], $form['listino'], 'FacetSelect', false);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['cm_ini'] . "</td><td  class=\"FacetDataTD\">\n";
$gForm->selectFromDB('catmer', 'cm_ini', 'codice', $form['cm_ini'], false, false, '-', 'descri', 'cm_ini');
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['cm_fin'] . "</td><td  class=\"FacetDataTD\">\n";
$gForm->selectFromDB('catmer', 'cm_fin', 'codice', $form['cm_fin'], false, false, '-', 'descri', 'cm_fin');
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['art_ini'] . "</td><td  class=\"FacetDataTD\">\n";
$gForm->selItem('art_ini', $form['art_ini'], $form['search']['art_ini'], $script_transl['mesg'], $form['hidden_req']);
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['art_fin'] . "</td><td  class=\"FacetDataTD\">\n";
$gForm->selItem('art_fin', $form['art_fin'], $form['search']['art_fin'], $script_transl['mesg'], $form['hidden_req']);
echo "</tr>\n";

/** inizio modifica FP 28/11/2015
 * filtro per fornitore ed ordinamento
 */

/*filtro su fornitore*/
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['id_anagra'] . " </td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$select_id_anagra = new selectPartner("id_anagra");
$select_id_anagra->selectDocPartner('id_anagra', $form['id_anagra'], $form['search']['id_anagra'], 'id_anagra', $script_transl['mesg'], $admin_aziend['masfor'], -1, 1, true);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";

/*scelta ordine di stampa*/
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['ordineStampa'] . " </td><td class=\"FacetDataTD\" colspan=\"2\">\n1)&nbsp;";
$gForm->variousSelect("ordine1", $script_transl['alternativeOrdineStampa'], $form['ordine1']);
echo "<br>2)&nbsp;";
$gForm->variousSelect("ordine2", $script_transl['alternativeOrdineStampa'], $form['ordine2']);
echo "<br>3)&nbsp;";
$gForm->variousSelect("ordine3", $script_transl['alternativeOrdineStampa'], $form['ordine3']);
echo "</td>\n";
echo "</tr>\n";

/*scelta se stampa compatta*/
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['tipoStampa'] . " </td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->variousSelect("tipoStampa", $script_transl['alternativeTipoStampa'], $form['tipoStampa']);
echo "</td>\n";
echo "</tr>\n";

/** fine modifica FP */
echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
echo '<td align="right"><input class="btn btn-info" type="submit" name="export" value="Esporta XLS">';
echo '<td align="center"><input class="btn btn-warning" type="submit" accesskey="i" name="print" value="';
echo $script_transl['print'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";
?>
</form>
<?php
require("../../library/include/footer.php");
?>
