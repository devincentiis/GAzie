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
$admin_aziend=checkAdmin();

//Creo l'array con i dati del bilancio IV direttiva CEE
$lines=file('../finann/IVdirCEE.bil');
foreach($lines as $line){
        $new = explode(';',$line,2);
        $data[] = array(trim($new[0]),$new[1]);
}
$nromani = array(0=>"",1=>"I",2=>"II",3=>"III",4=>"IV",5=>"V",6=>"VI",7=>"VII",8=>"VIII",9=>"IX",10=>"X",11=>"XI",12=>"XII",13=>"XIII",14=>"XIV",15=>"XV",16=>"XVI",17=>"XVII",18=>"XVIII",19=>"XIX");
// fine dati bilancio CEE

$msg = '';


if (isset($_POST['Update']) || isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
    $form['ritorno'] = $_POST['ritorno'];
    $form['codice'] = intval($_POST['mas']*1000000+$_POST['sub']);
    $form['descri'] = substr($_POST['descri'],0,50);
    $form['ceedar'] = substr($_POST['ceedar'],0,8);
    $form['ceeave'] = substr($_POST['ceeave'],0,8);
    $form['paymov'] = substr($_POST['paymov'],0,1);
    $form['annota'] = filter_var($_POST['annota'],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if (isset($_POST['Submit'])) { // conferma tutto
       //eseguo i controlli formali
       $code_exist = gaz_dbi_dyn_query('codice',$gTables['clfoco'],"codice = ".$form['codice'],'codice DESC',0,1);
       $code = gaz_dbi_fetch_array($code_exist);
       if ($code and $toDo == 'insert') {
          $msg .= "1+";
       }
       if (empty($form['descri'])) {
          $msg .= "2+";
       }
       if ($form['codice'] < 100000000 || $form['codice'] > 999999999) {
          $msg .= "0+";
       }
       if (empty($msg)) { // nessun errore
          // aggiorno il db
          if ($toDo == 'insert') {
             gaz_dbi_table_insert('clfoco',$form);
          } elseif ($toDo == 'update') {
             gaz_dbi_table_update('clfoco',$form['codice'],$form);
          }
          header("Location: report_piacon.php");
          exit;
       }
    } elseif (isset($_POST['Return'])) { // torno indietro
          header("Location: ".$form['ritorno']);
          exit;
    }
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
    $form = gaz_dbi_get_row($gTables['clfoco'], 'codice', intval($_GET['codice']));
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    if (!isset($_GET['codice'])) {
          header("Location: ".$form['ritorno']);
          exit;
    }
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    $form['codice'] = 0;
    $form['descri'] = '';
    $form['ceedar'] = '';
    $form['ceeave'] = '';
    $form['paymov'] = '';
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
   echo "<input type=\"hidden\" value=\"".intval($form['codice']/1000000)."\" name=\"mas\" />\n";
   echo "<input type=\"hidden\" value=\"".($form['codice']-intval($form['codice']/1000000)*1000000)."\" name=\"sub\" />\n";
}
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="3" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
}
if ($toDo == 'insert') {
   echo "<tr>\n";
   echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['mas']."* </td>\n";
   echo "\t<td class=\"FacetDataTD\" colspan=\"2\"><input type=\"text\" name=\"mas\" value=\"".intval($form['codice']/1000000)."\" align=\"right\" maxlength=\"3\"  /></td>\n";
   echo "</tr>\n";
   echo "<tr>\n";
   echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['sub']."* </td>\n";
   echo "\t<td class=\"FacetDataTD\" colspan=\"2\"><input type=\"text\" name=\"sub\" value=\"".($form['codice']-intval($form['codice']/1000000)*1000000)."\" align=\"right\" maxlength=\"6\"  /></td>\n";
   echo "</tr>\n";
}
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['descri']."* </td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\">
      <input type=\"text\" name=\"descri\" value=\"".$form['descri']."\" align=\"right\" maxlength=\"50\"  /></td>\n";
echo "</tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">".$script_transl['ceedar']."</td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\">\n";
echo "<select name=\"ceedar\" class=\"FacetSelectBil\">\n";
foreach ($data as $rigo) {
        $selected="";
        $nr=intval(substr($rigo[0],2,2));
        $spqr=$nromani[$nr];
        if(trim($form["ceedar"]) == $rigo[0])
            $selected = " selected ";
        echo "<option value=\"".$rigo[0]."\"".$selected."> ".substr($rigo[0],0,2)." ".$spqr." ".substr($rigo[0],4,4)." ".$rigo[1];
        echo "</option>\n";
}
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">".$script_transl['ceeave']."</td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\">\n";
echo "<select name=\"ceeave\" class=\"FacetSelectBil\">\n";
foreach ($data as $rigo) {
        $selected="";
        $nr=intval(substr($rigo[0],2,2));
        $spqr=$nromani[$nr];
        if(trim($form["ceeave"]) == $rigo[0])
            $selected = " selected ";
        echo "<option value=\"".$rigo[0]."\"".$selected."> ".substr($rigo[0],0,2)." ".$spqr." ".substr($rigo[0],4,4)." ".$rigo[1];
        echo "</option>\n";
}
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">".$script_transl['paymov']."</td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->variousSelect('paymov', $script_transl['paymov_value'], $form['paymov'], "col-sm-8", false, '', false);
echo "</td></tr>";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['annota']." </td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\">
      <textarea name=\"annota\" cols=50 rows=10 maxlength=\"100\" >".$form['annota']."</textarea></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFooterTD\">".$script_transl['sqn']."</td>";
echo "\t </td>\n";
echo "<td  class=\"FacetFooterTD text-center\">";
echo '<input name="Submit" class="btn btn-warning" type="submit" value="'.ucfirst($script_transl[$toDo]).'">';
echo "\t </td>\n";
echo "</tr>\n";
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
