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

function getLimit() {
    global $gTables;
    $acc = array();
    $where = "(id_distinta = 0  OR id_distinta IS NULL)  AND tipeff = 'B' ";
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

function getData($date_ini, $date_fin, $num_ini, $num_fin,$bank) {
    global $gTables, $admin_aziend;
    $m = array();
    $where = " (".$gTables['effett'] . ".id_distinta = 0 OR id_distinta IS NULL) AND tipeff = 'B' AND scaden BETWEEN $date_ini AND $date_fin AND progre BETWEEN $num_ini AND $num_fin";
    $orderby = "tipeff, scaden, progre";
    $rs = gaz_dbi_dyn_query($gTables['effett'] . ".*," .
            $gTables['anagra'] . ".pariva,
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
    return array('data' => $m, 'tot' => $total);
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
	$rs_last_bank = gaz_dbi_query("SELECT banacc FROM ".$gTables['effett']." WHERE tipeff='B' AND banacc > 0 ORDER BY id_tes DESC LIMIT 1");
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
  $form['eof'] = false;
} else { // accessi successivi
	if (!isset($_POST['bank'])){
		$_POST['bank']=0;
		$msg .='4+';
	}
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
  if (isset($_POST['eof'])) {
      $form['eof'] = substr($_POST['eof'], 0, 8);
  } else {
      $form['eof'] = '';
  }
  if (isset($_POST['return'])) {
      header("Location: " . $form['ritorno']);
      exit;
  }
	if (isset($_POST['submit']) && empty($msg) ) {
		// prima di passare allo script per la generazione del file distinta segno le riba scelte sul db portando il loro status a "CHK"
		foreach($_POST['chk'] as $k=>$v){
			gaz_dbi_put_row($gTables['effett'],'id_tes',intval($k),'status','CHK');
		}
	}
}

//controllo i campi
if (!checkdate($form['date_emi_M'], $form['date_emi_D'], $form['date_emi_Y']) ||
        !checkdate($form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']) ||
        !checkdate($form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y'])) {
    $msg .='1+';
}
$utsemi = mktime(12,0,0,$form['date_emi_M'],$form['date_emi_D'],$form['date_emi_Y']);
$utsini = mktime(12,0,0,$form['date_ini_M'],$form['date_ini_D'],$form['date_ini_Y']);
$utsfin = mktime(12,0,0,$form['date_fin_M'],$form['date_fin_D'],$form['date_fin_Y']);
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
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup'));
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
	$('input.checkeffett').change(function () {
		  var total = 0;
		  $('input.checkeffett:checked').each(function(){ // iterate through each checked element.
			total += isNaN(parseFloat($(this).val())) ? 0 : parseFloat($(this).val());
		  });
		  $("span#totval").text(total.toLocaleString("it-IT",{ maximumFractionDigits: 2, minimumFractionDigits: 2 }));
		  if (total<=0.01){
			$('input#submitform').prop('disabled', true);
		  } else {
			$('input#submitform').prop('disabled', false);
		  }
	});
	$('input#selall').click (function () {
	  $('input.checkeffett').prop('checked', this.checked);
	  $('input.checkeffett').trigger('change');
	});
});
</script>
<?php
echo '<form method="POST">';
echo "<input type=\"hidden\" value=\"\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['ritorno'] . "\" name=\"ritorno\" />\n";
$gForm = new venditForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'];
echo "</div>\n";
echo '<div class="table-responsive text-center"><table class="Tsmall table-bordered table-striped  text-left">';
if (!empty($msg)) {
    echo '<tr><td class="FacetDataTDred" colspan=2>' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"text-right\">" . $script_transl['date_emi'] . " : </td><td>\n";
$gForm->CalendarPopup('date_emi', $form['date_emi_D'], $form['date_emi_M'], $form['date_emi_Y'], 'FacetSelect', 1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"text-right\">" . $script_transl['bank'] . " : </td><td  class=\"FacetDataTD\">\n";
$rsbanacc=$gForm->selectBanacc($form['bank']);
if ($form['bank'] > 0) {
    $form['eof']=($rsbanacc)?'eof':'';
}
echo "<br/>" . $script_transl['eof'];
$gForm->selCheckbox('eof', $form['eof'], $script_transl['eof_title']);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"text-right\">" . $script_transl['num_ini'] . " : </td>\n";
echo "\t<td class=\"text-left\"><input type=\"text\" name=\"num_ini\" value=\"" . $form['num_ini'] . "\" /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"text-right\">" . $script_transl['num_fin'] . " : </td>\n";
echo "\t<td class=\"text-left\"><input type=\"text\" name=\"num_fin\" value=\"" . $form['num_fin'] . "\" /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"text-right\">" . $script_transl['date_ini'] . " : </td><td class=\"text-left\">\n";
$gForm->CalendarPopup('date_ini', $form['date_ini_D'], $form['date_ini_M'], $form['date_ini_Y'], 'FacetSelect', 1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"text-right\">" . $script_transl['date_fin'] . " : </td><td class=\"text-left\">\n";
$gForm->CalendarPopup('date_fin', $form['date_fin_D'], $form['date_fin_M'], $form['date_fin_Y'], 'FacetSelect', 1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"text-right\">" . $script_transl['period'] . " : </td>
      <td class=\"text-left\"><input type=\"submit\" name=\"period\" value=\"" . $script_transl['period_value'] . "\">";
echo "</td>\n";
echo "</tr>\n";
echo "\t<tr>\n";
echo "<td class=\"FacetFooterTD\"></td>";
echo '<td class="FacetFooterTD" align="center"> <input type="submit" class="btn btn-info" accesskey="i" name="preview" value="';
echo $script_transl['view'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table></div><br/>";
if (isset($_POST['preview']) && $msg == '') {
    $date_ini = sprintf("%04d%02d%02d", $form['date_ini_Y'], $form['date_ini_M'], $form['date_ini_D']);
    $date_fin = sprintf("%04d%02d%02d", $form['date_fin_Y'], $form['date_fin_M'], $form['date_fin_D']);
    $r = getData($date_ini, $date_fin, $form['num_ini'], $form['num_fin'],$form['bank']);
    echo '<div class="table-responsive text-center">'."<table class=\"Tlarge table\">";
    if (sizeof($r['data']) > 0) {
        echo '<tr>';
        echo '<td colspan="7" align="center" class="FacetDataTD">' . $script_transl['preview'] . "</td>\n";
        echo "</tr>";
        echo "<tr>";
        $linkHeaders = new linkHeaders($script_transl['header']);
        $linkHeaders->output();
        echo "</tr>\n";
        foreach ($r['data'] as $v) {
            echo "<tr>";
            echo '<td>' . $v["progre"] . "</td>";
            echo "<td>" . $v["customer"] . " - " . $v["pariva"] . " </td>";
            echo '<td>' . $v["desban"] . " </td>";
            echo '<td>' . gaz_format_date($v["scaden"]) . "</td>";
            echo '<td class="text-right"></td>';
            echo '<td></td>';
            echo "</tr>\n";
            echo "<tr>";
            echo "<td></td>";
            echo '<td class="text-right">Fattura n.' . $v["numfat"] . "/" . $v["seziva"] . " - " . gaz_format_date($v["datfat"]) . " - € " . gaz_format_number($v["totfat"]) . "</td>";
            echo '<td>' . $v["coordi"] . " </td>";
            echo '<td>' . $script_transl['salacc_value'][$v['salacc']] ." </td>";
            echo '<td class="text-right"><b>' . $admin_aziend['html_symbol'] . ' ' . gaz_format_number($v["impeff"]) . "</b> </td>";
            echo '<td><input type="checkbox" name="chk['.$v['id_tes'].']" class="checkeffett"  title"seleziona" checked value="'.$v["impeff"].'" ></td>';
            echo "</tr>\n";
        }
        echo '<tr><td colspan="5" class="text-right">Tutti</td><td><input type="checkbox" id="selall" checked ></td></tr><tr><td colspan="5" class="FacetDataTD text-right"><b>'.$script_transl['tot'] . " n." . $r['tot']['num'] . ' ' . $admin_aziend['html_symbol'] . ' <span id="totval">' . gaz_format_number($r['tot']['value']) . '</span></b></td>';
        echo "</tr><tr><td></td></tr>";
        echo "\t<tr>\n";
		echo '<td class="FacetFooterTD" class="text-center" colspan="6"><input type="submit" class="btn btn-warning" name="submit"  id="submitform" value="' . $script_transl['submit'] . '" accesskey="c" />';
        echo "\t </td>\n";
        echo "\t </tr>\n";
    } else {
        echo "<tr>";
        echo '<td colspan="5" class="FacetDataTDred">' . $script_transl['errors'][0] . "</td>\n";
        echo "</tr>";
    }
    echo '</table></div>';
} elseif (isset($_POST['submit']) && empty($msg) ) {
    echo '<div class="text-center"><a class="btn btn-xs btn-warning godistintaBtn" ref="genera_rb_cbi.php?datemi=' . $datemi . "&banacc=" . $form['bank'] . "&proini=" . $form['num_ini'] . "&profin=" . $form['num_fin'] . "&scaini=" . $datini . "&scafin=" . $datfin.((empty($form['eof']))?'':'&eof=1').'" id="godistintaBtn">'.$script_transl['submit'].'</a></div>';

}
?>
</form>
<?php
require("../../library/include/footer.php");
if (isset($_POST['submit']) && empty($msg) ) {
echo '
<script>
$(function() {
    var ref = $("a#godistintaBtn").attr("ref");
    $("a.godistintaBtn").attr("disabled", true);
    window.location.replace(ref);
});
</script>
';

}

?>
