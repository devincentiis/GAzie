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
$msg = "";

if (!isset($_GET['date_ini_D'])) {  //al primo accesso
    $form['date_ini_D'] = 1;
    $form['date_ini_M'] = 1;
    $form['date_ini_Y'] =  date("Y");
    $form['date_fin_D'] =  date("d");
    $form['date_fin_M'] =  date("m");
    $form['date_fin_Y'] =  date("Y");
} else {
    $form['date_ini_D'] = intval($_GET['date_ini_D']);
    $form['date_ini_M'] = intval($_GET['date_ini_M']);
    $form['date_ini_Y'] = intval($_GET['date_ini_Y']);
    $form['date_fin_D'] = intval($_GET['date_fin_D']);
    $form['date_fin_M'] = intval($_GET['date_fin_M']);
    $form['date_fin_Y'] = intval($_GET['date_fin_Y']);
}
//controllo i campi
if (!checkdate( $form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']))
    $msg .= "0+";
if (!checkdate( $form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y']))
    $msg .= "1+";
$utsini= mktime(0,0,0,$form['date_ini_M'],$form['date_ini_D'],$form['date_ini_Y']);
$utsfin= mktime(0,0,0,$form['date_fin_M'],$form['date_fin_D'],$form['date_fin_Y']);
$datainizio = date("Ymd",$utsini);
$datafine = date("Ymd",$utsfin);
if ($utsini > $utsfin) $msg .="2+";
if ($msg==""){
	if (isset($_GET['stampa'])) {
        $locazione = "Location: stampa_libgio.php?regini=".date("d-m-Y",$utsini)."&regfin=".date("d-m-Y",$utsfin);
        header($locazione);
        exit;
	}
	if (isset($_GET['stampa_a'])) {
        $locazione = "Location: stampa_libgio.php?regini=".date("d-m-Y",$utsini)."&regfin=".date("d-m-Y",$utsfin)."&pdfa";
        header($locazione);
        exit;
	}
	if (isset($_GET['stampa_a_mese'])) {
        $locazione = "Location: stampa_libgio.php?regini=".date("d-m-Y",$utsini)."&regfin=".date("d-m-Y",$utsfin)."&pdfamese";
        header($locazione);
        exit;
	}
}
if (isset($_GET['Return'])) {
        header("Location:docume_contab.php");
        exit;
}
require("../../library/include/header.php");
$script_transl=HeadMain(0,array('calendarpopup/CalendarPopup'));
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
echo "<form method=\"GET\">\n";
$gForm = new contabForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="4" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_ini']."</td><td colspan=\"3\" class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_ini',$form['date_ini_D'],$form['date_ini_M'],$form['date_ini_Y'],'FacetSelect','date_ini');
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_fin']."</td><td colspan=\"3\" class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_fin',$form['date_fin_D'],$form['date_fin_M'],$form['date_fin_Y'],'FacetSelect','date_fin');
echo "</td>\n";
echo "</tr>\n";
$result = gaz_dbi_dyn_query ("darave,datreg, SUM(import) AS import, COUNT(*) AS nrow",$gTables['rigmoc']." LEFT JOIN ".$gTables['tesmov']." ON (".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes) ","datreg BETWEEN ".$datainizio." AND ".$datafine." GROUP BY darave");
$nr=0;
$dare=0;$avere=0;
while ($rs = gaz_dbi_fetch_array($result)){
      $nr+=$rs['nrow'];
      if ($rs['darave']== 'D'){
         $dare= $rs['import'];
      } else {
         $avere= $rs['import'];
      }
}
echo "<tr><td class=\"FacetFieldCaptionTD\">".$script_transl['nrow']."</td><td class=\"FacetDataTD\" colspan=\"3\">".$nr." &nbsp;</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">".$script_transl['tot_d']."</td><td class=\"FacetDataTD\">".gaz_format_number($dare)."</td><td class=\"FacetFieldCaptionTD\">".$script_transl['tot_a']."</td><td class=\"FacetDataTD\">".gaz_format_number($avere)." &nbsp;</td></tr>";
echo "<tr><td class=\"bg-info text-right\"><input type=\"submit\" class=\"btn btn-info\" title=\"Se il libro giornale ha molte pagine c'è il rischio di mandare in timeout il sever\" name=\"stampa_a_mese\" value=\"".$script_transl['print']." PDF/A mese per mese (lento)\"> </td><td class=\"bg-info\"> <input  class=\"btn btn-info\" type=\"submit\" title=\"Se il libro giornale ha molte pagine c'è il rischio di mandare in timeout il sever!\" name=\"stampa_a\" value=\"".$script_transl['print']." PDF/A (lento)\" ></td><td class=\"bg-info\"></td><td  class=\"bg-info text-right\" colspan=2><input type=\"submit\" class=\"btn btn-info\" name=\"stampa\" value=\"".$script_transl['print']." PDF (veloce)\" ></td></tr>";
?>
</table>
<input type="hidden" name="hidden_req" />
</form>
<?php
require("../../library/include/footer.php");
?>
