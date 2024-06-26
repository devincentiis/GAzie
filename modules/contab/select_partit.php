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
$msg = "";

function getMovements($account_ini, $account_fin, $date_ini, $date_fin) {
    global $gTables, $admin_aziend;
    if ($account_ini == $account_fin || $account_fin == 0) { // i conti coincidono
        if ($account_fin == 0) {
            $account_fin = $account_ini;
        }
        $where = " codcon = $account_ini AND datreg BETWEEN $date_ini AND $date_fin";
        $orderby = " datreg, id_tes ASC ";
        $select = $gTables['tesmov'] . ".id_tes," .$gTables['tesmov'] . ".caucon," .$gTables['tesmov'] . ".clfoco AS codpart," . $gTables['tesmov'] . ".descri AS tesdes,datreg,codice,protoc,numdoc,datdoc," . $gTables['clfoco'] . ".descri,import*(darave='D') AS dare,import*(darave='A') AS avere";
    } else {
        $where = $gTables['clfoco'] . ".codice BETWEEN $account_ini AND $account_fin AND datreg BETWEEN $date_ini AND $date_fin GROUP BY " . $gTables['clfoco'] . ".codice";
        $orderby = " codice ASC ";
        $select = $gTables['tesmov'] . ".id_tes,".$gTables['tesmov'] . ".clfoco AS codpart," . "codice," . $gTables['clfoco'] . ".descri AS tesdes, COUNT(id_rig) AS `rows`, SUM(import*(darave='D')) AS dare, SUM(import*(darave='A')) AS avere";
    }
    $table = $gTables['clfoco'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON " . $gTables['clfoco'] . ".codice = " . $gTables['rigmoc'] . ".codcon "
            . "LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes ";

    $m = array();
    $rs = gaz_dbi_dyn_query($select, $table, $where, $orderby);
    $anagrafica = new Anagrafica();
    while ($r = gaz_dbi_fetch_array($rs)) {
        $r['tt'] = '';
        if ($account_ini == $account_fin || $account_fin == 0) {
            // INIZIO crezione tabella per la visualizzazione sul tootip di tutto il movimento e facccio la somma del totale movimento
            $res_rig = gaz_dbi_dyn_query("*", $gTables['rigmoc'], 'id_tes=' . $r["id_tes"], 'id_rig');
            $r['tt'] = '<p class=\'bg-info text-primary\'><b>' . $r['tesdes'] . '</b></p>';
            $tot = 0.00;
            $refclfoco=0;
            while ($rr = gaz_dbi_fetch_array($res_rig)) {
              $account = $anagrafica->getPartner($rr['codcon']);
              $r['tt'] .= '<p class=\'text-right\'>'.($account==null?'':htmlspecialchars($account['descri'])).'  '.$rr['import'].'  ' . $rr['darave'] . '</p>';
              if ($rr['darave'] == 'D') {
                  $tot += $rr['import'];
              }
              // faccio l'upload di tesmov quando incontro un rigo con testata senza riferimento al partner pur avendo un rigo con un cliente o fornitore
              if ($r['codpart']==0 && (substr($rr['codcon'],0,3) == $admin_aziend['mascli'] || substr($rr['codcon'],0,3) == $admin_aziend['masfor'] )){
                if ( $refclfoco == 0 ) {
                  gaz_dbi_query("UPDATE ".$gTables['tesmov']." SET clfoco = ".$rr['codcon']." WHERE id_tes = ".$r['id_tes']);
                } elseif ( $refclfoco  != $rr['codcon'] ) { // se ho troppi partner non posso riferirli
                  gaz_dbi_query("UPDATE ".$gTables['tesmov']." SET clfoco = 0 WHERE id_tes = ".$r['id_tes']);
                }
                $refclfoco=$rr['codcon'];
              }
            }
            $r['tt'] = str_replace("\"", "'", $r['tt']);
            // FINE creazione tabella per il tooltip
        }
        $m[] = $r;
    }
    return $m;
}

$rs_last_opening = gaz_dbi_dyn_query("YEAR(datreg) AS anno, MONTH(datreg) AS mese, DAY(datreg) AS giorno", $gTables['tesmov'], "caucon = 'APE'", "datreg DESC", 0, 1);
$last_opening = gaz_dbi_fetch_array($rs_last_opening); // trovo la data dell'ultima apertura
if ($last_opening) {
	$last_opening_year = $last_opening['anno'];
	$last_opening_month = $last_opening['mese'];
	$last_opening_day = $last_opening['giorno'];
} else {
	$last_opening_year = '2004';
	$last_opening_month = '1';
	$last_opening_day = '27';
}

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    if (!isset($_GET['di'])) {
        $form['date_ini_D'] = $last_opening_day;
    } else {
        $form['date_ini_D'] = intval($_GET['di']);
    }
    if (!isset($_GET['mi'])) {
        $form['date_ini_M'] = $last_opening_month;
    } else {
        $form['date_ini_M'] = intval($_GET['mi']);
    }
    if (!isset($_GET['yi'])) {
        $form['date_ini_Y'] = $last_opening_year;
    } else {
        $form['date_ini_Y'] = intval($_GET['yi']);
    }
    if (!isset($_GET['df'])) {
        $form['date_fin_D'] = date('d');
    } else {
        $form['date_fin_D'] = intval($_GET['df']);
    }
    if (!isset($_GET['mf'])) {
        $form['date_fin_M'] = date('m');
    } else {
        $form['date_fin_M'] = intval($_GET['mf']);
    }
    if (!isset($_GET['yf'])) {
        $form['date_fin_Y'] = date('Y');
    } else {
        $form['date_fin_Y'] = intval($_GET['yf']);
    }
    $form['this_date_Y'] = date('Y');
    $form['this_date_M'] = date('m');
    $form['this_date_D'] = date('d');
    if (isset($_GET['id'])) {
        $form['master_ini'] = substr($_GET['id'], 0, 3) . '000000';
        $form['account_ini'] = intval($_GET['id']);
        $form['master_fin'] = $form['master_ini'];
        $form['account_fin'] = $form['account_ini'];
    } elseif (isset($_GET['msf']) && isset($_GET['msi']) && isset($_GET['aci']) && isset($_GET['acf'])) {
        $form['master_ini'] = intval($_GET['msi']);
        $form['account_ini'] = intval($_GET['aci']);
        $form['master_fin'] = intval($_GET['msf']);
        $form['account_fin'] = intval($_GET['acf']);
    } else {
        $form['master_ini'] = 0;
        $form['account_ini'] = 0;
        $form['master_fin'] = 999000000;
        $form['account_fin'] = 999999999;
    }
    $form['search']['account_ini'] = '';
    $form['search']['account_fin'] = '';
} else { // accessi successivi
    $form['hidden_req'] = htmlentities($_POST['hidden_req']);
    $form['ritorno'] = $_POST['ritorno'];
    $form['date_ini_D'] = intval($_POST['date_ini_D']);
    $form['date_ini_M'] = intval($_POST['date_ini_M']);
    $form['date_ini_Y'] = intval($_POST['date_ini_Y']);
    $form['date_fin_D'] = intval($_POST['date_fin_D']);
    $form['date_fin_M'] = intval($_POST['date_fin_M']);
    $form['date_fin_Y'] = intval($_POST['date_fin_Y']);
    $form['this_date_Y'] = intval($_POST['this_date_Y']);
    $form['this_date_M'] = intval($_POST['this_date_M']);
    $form['this_date_D'] = intval($_POST['this_date_D']);
    $form['master_ini'] = intval($_POST['master_ini']);
    $form['account_ini'] = intval($_POST['account_ini']);
    $form['master_fin'] = intval($_POST['master_fin']);
    $form['account_fin'] = isset($_POST['account_fin']) ? intval($_POST['account_fin']) : 0;
    foreach ($_POST['search'] as $k => $v) {
        $form['search'][$k] = $v;
    }
    if (isset($_POST['selall'])) {
        $query = 'SELECT MAX(codice) AS max, MIN(codice) AS min ' .
                'FROM ' . $gTables['clfoco'] .
                " WHERE codice NOT LIKE '%000000'";
        $rs_extreme_accont = gaz_dbi_query($query);
        $extreme_account = gaz_dbi_fetch_array($rs_extreme_accont);
        if ($extreme_account) {
            $form['master_ini'] = substr($extreme_account['min'], 0, 3) . '000000';
            $form['account_ini'] = $extreme_account['min'];
            $form['master_fin'] = substr($extreme_account['max'], 0, 3) . '000000';
            $form['account_fin'] = $extreme_account['max'];
        }
    }
    if (isset($_POST['pull_sbm'])) {
        $query = 'SELECT MIN(codice) AS min, descri ' .
                'FROM ' . $gTables['clfoco'] .
                " WHERE codice NOT LIKE '%000000' AND codice LIKE '" . substr($form['master_ini'], 0, 3) . "%'";
        $rs_extreme_accont = gaz_dbi_query($query);
        $extreme_account = gaz_dbi_fetch_array($rs_extreme_accont);
        if ($extreme_account) {

            $form['account_ini'] = $extreme_account['min'];
            $form['search']['account_ini'] = $extreme_account['descri'];
        }
    }
    if (isset($_POST['copy_to_fin'])) {
      $form['master_fin'] = $form['master_ini'];
      $form['account_fin'] =$form['account_ini'];
      $form['search']['account_fin'] = $form['search']['account_ini'];
    }

    if (isset($_POST['selfin'])) {
        $form['master_fin'] = $form['master_ini'];
        $form['account_fin'] = $form['account_ini'];
    }
    if (isset($_POST['return'])) {
        header("Location: " . $form['ritorno']);
        exit;
    }
}

//controllo i campi
if (!checkdate($form['this_date_M'], $form['this_date_D'], $form['this_date_Y']) ||
        !checkdate($form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']) ||
        !checkdate($form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y'])) {
    $msg .= '0+';
}
$utsexe = mktime(0, 0, 0, $form['this_date_M'], $form['this_date_D'], $form['this_date_Y']);
$utsini = mktime(0, 0, 0, $form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']);
$utsfin = mktime(0, 0, 0, $form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y']);
if ($utsini > $utsfin) {
    $msg .= '1+';
}
if ($utsexe < $utsfin) {
    $msg .= '2+';
}
if ($form['account_fin'] < $form['account_ini'] && $form['account_fin'] > 0) {
    $msg .= '3+';
}
// fine controlli
$date_ini = sprintf("%04d%02d%02d", $form['date_ini_Y'], $form['date_ini_M'], $form['date_ini_D']);
$date_fin = sprintf("%04d%02d%02d", $form['date_fin_Y'], $form['date_fin_M'], $form['date_fin_D']);
$date_last_opening = sprintf("%04d%02d%02d", $last_opening_year, $last_opening_month, $last_opening_day);

$saldo_precedente = 0.00;
if ($form['account_ini'] == $form['account_fin']) {
	$query = "SELECT SUM((CASE WHEN darave='D' THEN 1 ELSE -1 END)*import) AS saldo" .
			 " FROM " . $gTables['rigmoc'] . " LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoc'] . ".id_tes=" . $gTables['tesmov'] . ".id_tes" .
			 " WHERE codcon = " . $form['account_ini'] . " AND datreg>='" . $date_last_opening . "' AND datreg<'" . $date_ini . "'";
	$rs_extreme_accont = gaz_dbi_query($query);
	$extreme_account = gaz_dbi_fetch_array($rs_extreme_accont);
	if ($extreme_account) {
		$saldo_precedente = $extreme_account['saldo'];
	}
}

if (isset($_POST['print']) && $msg == '') {
    //Mando in stampa i movimenti contabili generati
    if ($form['account_fin'] == 0) {
        $form['account_fin'] == $form['account_ini'];
    }
    $_SESSION['print_request'] = array('script_name' => 'stampa_partit',
        'codice' => $form['account_ini'],
        'codfin' => $form['account_fin'],
        'regini' => date("dmY", $utsini),
        'regfin' => date("dmY", $utsfin),
        'ds' => date("dmY", $utsexe)
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
$gForm = new contabForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="2" class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['date'] . "</td><td  colspan=\"2\">\n";
$gForm->CalendarPopup('this_date', $form['this_date_D'], $form['this_date_M'], $form['this_date_Y'], 'FacetSelect', 1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['master_ini'] . "</td><td>\n";
$gForm->selMasterAcc('master_ini', $form['master_ini'], 'master_ini');
echo "</td>\n";
echo "<td rowspan=\"2\">";
echo '<input type="submit" name="selall" value="';
echo $script_transl['selall'];
echo '">';
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['account_ini'] . "</td><td>\n";
$gForm->lockSubtoMaster($form['master_ini'], 'account_ini');
$gForm->selSubAccount('account_ini', $form['account_ini'], $form['search']['account_ini'], $form['hidden_req'], $script_transl['mesg']);
echo ' <button type="submit" class="btn btn-default btn-sm" name="pull_sbm" ><i class="glyphicon glyphicon-fast-backward"></i></button>';
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['master_fin'] . "</td><td>\n";
$gForm->selMasterAcc('master_fin', $form['master_fin'], 'master_fin');
echo "</td>\n";
echo "<td rowspan=\"2\">";
echo '<button type="submit" name="copy_to_fin">';
echo $script_transl['selfin'];
echo '</button>';
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['account_fin'] . "</td><td>\n";
$gForm->lockSubtoMaster($form['master_fin'], 'account_fin');
$gForm->selSubAccount('account_fin', $form['account_fin'], $form['search']['account_fin'], $form['hidden_req'], $script_transl['mesg']);
echo ' <button type="submit" class="btn btn-default btn-sm" name="push_sbm" ><i class="glyphicon glyphicon-fast-forward"></i></button>';
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['date_ini'] . "</td><td colspan=\"2\">\n";
$gForm->CalendarPopup('date_ini', $form['date_ini_D'], $form['date_ini_M'], $form['date_ini_Y'], 'FacetSelect', 1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['date_fin'] . "</td><td colspan=\"2\">\n";
$gForm->CalendarPopup('date_fin', $form['date_fin_D'], $form['date_fin_M'], $form['date_fin_Y'], 'FacetSelect', 1);
echo "</td>\n";
echo "</tr>\n";
echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
echo "<td></td>";
echo '<td align="center" colspan="2"> <input type="submit" class="btn btn-info" accesskey="i" name="preview" value="';
echo $script_transl['view'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";

//recupero tutti i movimenti contabili dei conti insieme alle relative testate...
if (isset($_POST['preview']) and $msg == '') {
  $span = 6;
  $totdare = 0.00;
  $totavere = 0.00;
  $saldo = $saldo_precedente;
  $m = getMovements($form['account_ini'], $form['account_fin'], $date_ini, $date_fin);
  echo "<div class=\"table-responsive\"><table class=\"Tlarge table table-striped\">";
  if (sizeof($m) > 0) {
    if ($form['account_ini'] < $form['account_fin']) {
      $trsl=array_keys($script_transl['header1']);
      echo '<thead><tr><th>'.$trsl[0].'</th><th class="text-center">'.$trsl[1].'</th><th>'.$trsl[2].'</th><th class="text-right">'.$trsl[3].'</th><th class="text-right">'.$trsl[4].'</th><th class="text-right">'.$trsl[5].'</th></tr></thead>';
      foreach ($m as $key => $mv) {
        echo "<tr>
              <td>" . $mv["codice"] . " &nbsp;</td>";
        echo '<td class="text-center">' . $mv["rows"] . " &nbsp</td>";
        echo "<td>" . $mv["tesdes"] . " &nbsp;</td>";
        echo "<td align=\"right\">" . gaz_format_number($mv['dare']) . " &nbsp;</td>";
        echo "<td align=\"right\">" . gaz_format_number($mv['avere']) . " &nbsp;</td>";
        echo "<td align=\"right\">" . gaz_format_number($mv['dare'] - $mv['avere']) . " &nbsp;</td>
              </tr>";
      }
    } else {
      $trsl=array_keys($script_transl['header2']);
      echo '<thead><tr><th>'.$trsl[0].'</th><th>'.$trsl[1].'</th><th>'.$trsl[2].'</th><th class="text-center">'.$trsl[3].'</th><th class="text-center">'.$trsl[4].'</th><th class="text-center">'.$trsl[5].'</th><th class="text-right">'.$trsl[6].'</th><th class="text-right">'.$trsl[7].'</th><th class="text-right">'.$trsl[8].'</th></tr></thead>';
      $span = 9;
      echo "<tr class=\"FacetDataTD\"><td colspan=\"8\" align=\"right\"><b>SALDO PRECEDENTE &nbsp;</b></td>";
      echo "<td align=\"right\"><b>" . gaz_format_number($saldo_precedente) . " &nbsp;</b></td></tr>";
      foreach ($m as $key => $mv) {
          $totdare+= $mv['dare'];
          $totavere+= $mv['avere'];
          $saldo += $mv['dare'];
          $saldo -= $mv['avere'];
          echo "<tr class=\"FacetDataTD\">
                <td>" . gaz_format_date($mv["datreg"]) . " &nbsp;</td>";
          echo "<td><a target=\"_blank\" class=\"btn btn-edit btn-xs\" href=\"admin_movcon.php?id_tes=" . $mv["id_tes"] . "&Update\">" . $mv["id_tes"] . "</a> &nbsp</td>";
          echo '<td><div class="gazie-tooltip" data-type="movcon-thumb" data-id="' . $mv["id_tes"] . '" data-title="' . str_replace("\"", "'", $mv["tt"]) . '" >' . $mv["tesdes"] . '</div></td>';
          if (!empty($mv['numdoc'])) {
              echo "<td align=\"center\">" . $mv["protoc"] . " &nbsp;</td>";
              echo "<td align=\"center\">" . $mv["numdoc"] . " &nbsp;</td>";
              echo "<td align=\"center\">" . gaz_format_date($mv["datdoc"]) . " &nbsp;</td>";
          } else {
              echo "<td colspan=\"3\"></td>";
          }
          echo "<td align=\"right\">" .(($mv['dare']>=0.01)?gaz_format_number($mv['dare']):''). " &nbsp;</td>";
          echo "<td align=\"right\">" .(($mv['avere']>=0.01)?gaz_format_number($mv['avere']):''). " &nbsp;</td>";
          echo "<td align=\"right\">" . gaz_format_number($saldo) . " &nbsp;</td></tr>";
      }
      echo "<tr class=\"FacetDataTD\"><td colspan=\"9\" align=\"right\"></td></tr>";
      echo "<tr class=\"FacetDataTD\"><td colspan=\"5\" align=\"right\"></td>";
      echo "<td align=\"center\"><b>" . "SALDO PERIODO" . "</b></td>";
      echo "<td align=\"right\"><b>" . gaz_format_number($totdare) . " &nbsp;</b></td>";
      echo "<td align=\"right\"><b>" . gaz_format_number($totavere) . " &nbsp;</b></td>";
      echo "<td align=\"right\"><b>" . gaz_format_number($saldo) . " &nbsp;</b></td>";
    }
    echo "\t<tr>\n";
    echo '<td colspan="' . $span . '" class="FacetFooterTD text-center"><input class="btn btn-warning" type="submit" name="print" value="';
    echo $script_transl['print'];
    echo '">';
    echo "\t </td>\n";
    echo "\t </tr>\n";
  } else {
    echo "<tr><td class=\"FacetDataTDred\" align=\"center\">" . $script_transl['errors'][4] . "</td></tr>\n";
  }
  echo "</table></div></form>";
}
?>
<?php

require("../../library/include/footer.php");
?>
