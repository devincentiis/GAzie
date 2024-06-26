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
$admin_aziend = checkAdmin();
$msg = '';

function getLimit() {
    global $gTables;
    $acc = array();
    $where = "(id_distinta = 0  OR id_distinta IS NULL) AND tipeff = 'I' ";
    $rs_d = gaz_dbi_dyn_query("scaden", $gTables['effett'], $where, "scaden ASC", 0, 1);
    $rs = gaz_dbi_fetch_array($rs_d);
    if ($rs) {
        $acc['si'] = $rs['scaden'];
    } else {
        $acc['si'] = date("Y-m-d");
    }
    $rs_d = gaz_dbi_dyn_query("scaden", $gTables['effett'], $where, "scaden DESC", 0, 1);
    $rs = gaz_dbi_fetch_array($rs_d);
    if ($rs) {
        $acc['sf'] = $rs['scaden'];
    } else {
        $acc['sf'] = date("Y-m-d");
    }
    $rs_d = gaz_dbi_dyn_query("MIN(progre) AS progre", $gTables['effett'], $where . ' GROUP BY YEAR(datemi) ');
    $rs = gaz_dbi_fetch_array($rs_d);
    if ($rs) {
        $acc['ni'] = $rs['progre'];
    } else {
        $acc['ni'] = 1;
    }
    $rs_d = gaz_dbi_dyn_query("MAX(progre) AS progre", $gTables['effett'], $where . ' GROUP BY YEAR(datemi) ');
    $rs = gaz_dbi_fetch_array($rs_d);
    if ($rs) {
        $acc['nf'] = $rs['progre'];
    } else {
        $acc['nf'] = 999999999;
    }
    return $acc;
}

function getData($date_ini, $date_fin, $num_ini, $num_fin, $bank) {
    global $gTables, $admin_aziend;
    $m = [];
    $where = " (".$gTables['effett'] . ".id_distinta = 0 OR id_distinta IS NULL) AND tipeff = 'I' AND scaden BETWEEN $date_ini AND $date_fin AND progre BETWEEN $num_ini AND $num_fin";
    $orderby = "tipeff, scaden, progre";
    $rs = gaz_dbi_dyn_query($gTables['effett'] . ".*," .
            $gTables['anagra'] . ".pariva," . $gTables['anagra'] . ".codfis,
                     CONCAT(" . $gTables['anagra'] . ".ragso1,' '," . $gTables['anagra'] . ".ragso2) AS customer,
                     CONCAT(" . $gTables['banapp'] . ".codabi,' - '," . $gTables['banapp'] . ".codcab) AS coordi,
                     " . $gTables['banapp'] . ".descri AS desban ", $gTables['effett'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['effett'] . ".clfoco = " . $gTables['clfoco'] . ".codice
                     LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['anagra'] . ".id = " . $gTables['clfoco'] . ".id_anagra
                     LEFT JOIN " . $gTables['banapp'] . " ON " . $gTables['effett'] . ".banapp = " . $gTables['banapp'] . ".codice", $where, $orderby);
    $total = array();
    while ($r = gaz_dbi_fetch_array($rs)) {
        if (isset($total['value'])) {
            $total['value']+=$r['impeff'];
            $total['num'] ++;
        } else {
            $total['value'] = $r['impeff'];
            $total['num'] = 1;
        }
        $m[] = $r;
    }
    return ['data' => $m, 'tot' => $total];
}

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
  $form['hidden_req'] = '';
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  require("lang." . $admin_aziend['lang'] . ".php");
  $iniData = getLimit();
  $form['date_emi_D'] = date("d");
  $form['date_emi_M'] = date("m");
  $form['date_emi_Y'] = date("Y");
  // propongo l'ultima banca utilizzata
  $rs_last_bank = gaz_dbi_query("SELECT banacc FROM ".$gTables['effett']." WHERE tipeff='I' AND banacc > 0 ORDER BY id_tes DESC LIMIT 1");
  $last_bank=gaz_dbi_fetch_array($rs_last_bank);
  $form['bank']=($last_bank)?$last_bank['banacc']:0;
  $form['date_ini_D'] = substr($iniData['si'], 8, 2);
  $form['date_ini_M'] = substr($iniData['si'], 5, 2);
  $form['date_ini_Y'] = substr($iniData['si'], 0, 4);
  $form['date_fin_D'] = substr($iniData['sf'], 8, 2);
  $form['date_fin_M'] = substr($iniData['sf'], 5, 2);
  $form['date_fin_Y'] = substr($iniData['sf'], 0, 4);
  $form['num_ini'] = $iniData['ni'];
  $form['num_fin'] = $iniData['nf'];
} else { // accessi successivi
  $form['hidden_req'] = htmlentities($_POST['hidden_req']);
  $form['ritorno'] = $_POST['ritorno'];
  $form['date_emi_D'] = intval($_POST['date_emi_D']);
  $form['date_emi_M'] = intval($_POST['date_emi_M']);
  $form['date_emi_Y'] = intval($_POST['date_emi_Y']);
  $form['bank'] = intval($_POST['bank']);
  $form['date_ini_D'] = intval($_POST['date_ini_D']);
  $form['date_ini_M'] = intval($_POST['date_ini_M']);
  $form['date_ini_Y'] = intval($_POST['date_ini_Y']);
  $form['date_fin_D'] = intval($_POST['date_fin_D']);
  $form['date_fin_M'] = intval($_POST['date_fin_M']);
  $form['date_fin_Y'] = intval($_POST['date_fin_Y']);
  $form['num_ini'] = intval($_POST['num_ini']);
  $form['num_fin'] = intval($_POST['num_fin']);
  if (isset($_POST['period'])) {
    $gazTimeFormatter->setPattern('MMyyyy');
    $new_date_ini=$gazTimeFormatter->format(new DateTime('@'.mktime(12,0,0,date("m")+1,16,date("Y"))));
    $new_date_fin=$gazTimeFormatter->format(new DateTime('@'.mktime(12,0,0,date("m")+2,15,date("Y"))));
    $form['date_ini_D']=16;
    $form['date_ini_M']=substr($new_date_ini,0,2);
    $form['date_ini_Y']=substr($new_date_ini,2,4);
    $form['date_fin_D']=15;
    $form['date_fin_M']=substr($new_date_fin,0,2);
    $form['date_fin_Y']=substr($new_date_fin,2,4);
    $form['num_ini']=1;
    $form['num_fin']=999999999;
  }
  if (isset($_POST['return'])) {
    header("Location: " . $form['ritorno']);
    exit;
  }
}

//controllo i campi
if (!checkdate($form['date_emi_M'], $form['date_emi_D'], $form['date_emi_Y']) ||
        !checkdate($form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']) ||
        !checkdate($form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y'])) {
    $msg .='1+';
}
$utsemi= mktime(12,0,0,$form['date_emi_M'],$form['date_emi_D'],$form['date_emi_Y']);
$utsini= mktime(12,0,0,$form['date_ini_M'],$form['date_ini_D'],$form['date_ini_Y']);
$utsfin= mktime(12,0,0,$form['date_fin_M'],$form['date_fin_D'],$form['date_fin_Y']);
$gazTimeFormatter->setPattern('yyyy-MM-dd');
$datemi = $gazTimeFormatter->format(new DateTime('@'.$utsemi));
$datini = $gazTimeFormatter->format(new DateTime('@'.$utsini));
$datfin = $gazTimeFormatter->format(new DateTime('@'.$utsfin));

if ($utsini > $utsfin) {
    $msg .='2+';
}
if ($utsemi > $utsini) {
    $msg .='3+';
}
// fine controlli

$anagrafica = new Anagrafica();
require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup'),'select_filerb');
?>
<script>
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
$(function() {
    $("#godistintaBtn").click(function() {
  		var ref = $(this).attr('ref');
        $(".godistintaBtn").attr("disabled", true);
        window.location.replace(ref);
        return true;
    });
});
</script>
<?php
echo "<form method=\"POST\" name=\"select\">\n";
echo "<input type=\"hidden\" value=\"\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['ritorno'] . "\" name=\"ritorno\" />\n";
$gForm = new venditForm();
echo '<div align="center" class="FacetFormHeaderFont">Generazione file XML per distinta dei RID in formato SEPA </div>';
echo "<div align=\"center\">\n";
echo '<table class="table-striped table-bordered Tmiddle">';
if (!empty($msg)) {
    echo '<tr><td class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
}
echo "<tr>\n";
echo '<td class="col-xs-6">' . $script_transl['date_emi'] . '</td><td class="col-xs-6">';
$gForm->CalendarPopup('date_emi', $form['date_emi_D'], $form['date_emi_M'], $form['date_emi_Y'], 'col-xs-3', 1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td>" . $script_transl['bank'] . "</td><td  >\n";
$rsbanacc=$gForm->selectBanacc($form['bank']);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td>" . $script_transl['num_ini'] . "</td>\n";
echo "\t<td><input type=\"text\" name=\"num_ini\" value=\"" . $form['num_ini'] . "\" /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td>" . $script_transl['num_fin'] . "</td>\n";
echo "\t<td><input type=\"text\" name=\"num_fin\" value=\"" . $form['num_fin'] . "\" /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo '<td>' . $script_transl['date_ini'] . "</td><td>\n";
$gForm->CalendarPopup('date_ini', $form['date_ini_D'], $form['date_ini_M'], $form['date_ini_Y'], 'col-xs-3', 1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td>" . $script_transl['date_fin'] . "</td><td>\n";
$gForm->CalendarPopup('date_fin', $form['date_fin_D'], $form['date_fin_M'], $form['date_fin_Y'], 'col-xs-3', 1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td>" . $script_transl['period'] . "</td>
      <td><input type=\"submit\" name=\"period\" value=\"" . $script_transl['period_value'] . "\">";
echo "</td>\n";
echo "</tr>\n";
echo "\t<tr >\n";
echo "<td align=\"left\"><input type=\"submit\" name=\"return\" value=\"" . $script_transl['return'] . "\">\n";
echo '<td align="right"> <input type="submit" accesskey="i" name="preview" value="';
echo $script_transl['view'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";
echo "</div>\n";

if (isset($_POST['preview']) and $msg == '') {
    $date_ini = sprintf("%04d%02d%02d", $form['date_ini_Y'], $form['date_ini_M'], $form['date_ini_D']);
    $date_fin = sprintf("%04d%02d%02d", $form['date_fin_Y'], $form['date_fin_M'], $form['date_fin_D']);
    $r = getData($date_ini, $date_fin, $form['num_ini'], $form['num_fin'], $form['bank']);
    echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
    if (sizeof($r['data']) > 0) {
        echo '<tr>';
        echo '<td colspan=7 align="center"><b>ANTEPRIMA DEL CONTENUTO DEL FILE XML FORMATO CBI-SEPA<b></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>Num.</th>';
        echo '<th class="text-center">Scadenza</th>';
        echo '<th class="text-center">Importo</th>';
        echo '<th class="text-center">Cliente</th>';
        echo '<th class="text-center">Fattura</th>';
        echo '<th class="text-center">IBAN</th>';
        echo '</tr>';
        foreach ($r['data'] as $v) {
            echo '<tr class="'.($v['status']=='MODAMOUNT'?'text-danger':'').'">';
            echo "<td><a href=\"./admin_effett.php?Update&id_tes=" . $v['id_tes'] . "\">" . $v["progre"] . "</a>";
            if ($v['status']=='RAGGRUPPA'){
              echo '<span class="text-danger">[raggruppato] </span>';
            }
            echo "</td>";
            echo "<td>" . gaz_format_date($v["scaden"]) . "</td>";
            echo "<td  align=\"right\">". $admin_aziend['html_symbol'] . ' ' . gaz_format_number($v["impeff"]);
            if ($v['status']=='MODAMOUNT') {
              echo '<br /><span class="text-danger">[modificato]</span>';
            }
            echo " </td>";
            echo "<td>" . $v["customer"] . " </td>";
            echo '<td class="text-center">n.' . $v["numfat"] . "/" . $v["seziva"] . " - " . gaz_format_date($v["datfat"]) . ' € '.gaz_format_number($v["totfat"]) . "</td>";
            echo "</td>";
            echo '<td class="text-center">' . $v["iban"] ." </td>";
            echo "</tr>\n";
        }
        echo '<td colspan="4" >' .
        $script_transl['tot'] . " n." .
        $r['tot']['num'] . ' ' .
        $admin_aziend['html_symbol'] . ' ' .
        gaz_format_number($r['tot']['value']) . "</td>\n" .
        '<td colspan="3" ></td>';
        echo "</tr>";
        echo "\t<tr >\n";
        echo '<td colspan="7" align="right"><a class="btn btn-xs btn-warning godistintaBtn" ref="genera_rid_cbi.php?datemi=' . $datemi . "&banacc=" . $form['bank'] . "&proini=" . $form['num_ini'] . "&profin=" . $form['num_fin'] . "&scaini=" . $datini . "&scafin=" . $datfin.'" id="godistintaBtn">'.$script_transl['submit'].'</a>';
        echo "\t </td>\n";
        echo "\t </tr>\n";
    } else {
        echo "<tr>";
        echo '<td colspan="7" align="center" class="FacetDataTDred">' . $script_transl['errors'][0] . "</td>\n";
        echo "</tr>";
    }
    echo "</table>\n";
}

?>
</form>
<?php
require("../../library/include/footer.php");
?>
