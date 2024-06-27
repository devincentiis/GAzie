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
$admin_aziend=checkAdmin();
$msg = '';


if (isset($_POST['Update']) || isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
    $form['ritorno'] = $_POST['ritorno'];
    $form['codice'] = intval($_POST['codice']);
    $form['descri'] = substr($_POST['descri'],0,50);
    $form['incoterms'] = substr($_POST['incoterms'],0,3);
    $form['annota'] = substr($_POST['annota'],0,50);
    if (isset($_POST['Submit'])) { // conferma tutto
       //eseguo i controlli formali
       $code_exist = gaz_dbi_dyn_query('codice',$gTables['portos'],"codice = ".$form['codice'],'codice DESC',0,1);
       $code = gaz_dbi_fetch_array($code_exist);
       if ($code and $toDo == 'insert') {
          $msg .= "1+";
       }
       if (empty($form['descri'])) {
          $msg .= "2+";
       }
       if ($form['codice'] <= 0 || $form['codice'] > 99) {
          $msg .= "0+";
       }
       if ($form['weight'] < 0) {
          $msg .= "3+";
       }
       if (empty($msg)) { // nessun errore
          // aggiorno il db
          if ($toDo == 'insert') {
             gaz_dbi_table_insert('portos',$form);
          } elseif ($toDo == 'update') {
             gaz_dbi_table_update('portos',$form['codice'],$form);
          }
          header("Location: report_portos.php");
          exit;
       }
    } elseif (isset($_POST['Return'])) { // torno indietro
          header("Location: ".$form['ritorno']);
          exit;
    }
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
    $form = gaz_dbi_get_row($gTables['portos'], 'codice', intval($_GET['codice']));
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    $rs_last = gaz_dbi_dyn_query('codice',$gTables['portos'],'1','codice DESC',0,1);
    $last = gaz_dbi_fetch_array($rs_last);
    $form['codice'] = $last['codice']+1;
    $form['descri'] = '';
    $form['incoterms'] = '';
    $form['annota'] = '';
}

require("../../library/include/header.php");
$script_transl = HeadMain();
echo "<form method=\"POST\">";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"".$form['ritorno']."\">\n";
echo "<input type=\"hidden\" name=\"".ucfirst($toDo)."\" value=\"\">";
$gForm = new GAzieForm();
if ($toDo == 'insert') {
   echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['ins_this']."</div>\n";
} else {
   echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['upd_this']." '".$form['codice']."'</div>\n";
   echo "<input type=\"hidden\" value=\"".$form['codice']."\" name=\"codice\" />\n";
}
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="3" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
}
if ($toDo == 'insert') {
   echo "<tr>\n";
   echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['codice']."* </td>\n";
   echo "\t<td class=\"FacetDataTD\" colspan=\"2\"><input type=\"text\" name=\"codice\" value=\"".$form['codice']."\" align=\"right\" maxlength=\"3\"  /></td>\n";
   echo "</tr>\n";
}
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['descri']."* </td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\"><input type=\"text\" name=\"descri\" value=\"".$form['descri']."\" align=\"right\" maxlength=\"50\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['incoterms']."</td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\">";
$gForm->selectFromXML('../../library/include/stock_incoterms.xml', 'incoterms','incoterms',$form['incoterms']);
echo "</td>\n";
echo "</tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">".$script_transl['annota']."</td>
     <td class=\"FacetDataTD\" colspan=\"2\">\n";
echo "\t<input type=\"text\" name=\"annota\" value=\"".$form['annota']."\" maxlength=\"50\"  class=\"FacetInput\">\n";
echo "</td></tr>";
echo "<tr>\n";
echo "\t<td class=\"FacetFooterTD\">".$script_transl['sqn']."</td>";
echo "\t </td>\n";
echo '<td colspan=2 class="FacetFooterTD text-center">';
echo '<input name="Submit" class="btn btn-warning" type="submit" value="'.ucfirst($script_transl[$toDo]).'">';
echo "\t </td>\n";
echo "</tr>\n";
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
