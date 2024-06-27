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

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['clfoco'] = '';
    $form['search']['clfoco'] = '';
    $form['date_ini_D'] = date("d");
    $form['date_ini_M'] = date("m");
    $form['date_ini_Y'] = date("Y");
    $form['search']['account'] = '';
    if (isset($_GET['id'])) {
        $form['account'] = intval($_GET['id']);
    } else {
        $form['account'] = 0;
    }
    $form['orderby'] = 0;
} else { // accessi successivi
    $form['hidden_req'] = htmlentities($_POST['hidden_req']);
    $form['ritorno'] = $_POST['ritorno'];
    $form['clfoco'] = intval($_POST['clfoco']);
    foreach ($_POST['search'] as $k => $v) {
        $form['search'][$k] = $v;
    }
    $form['date_ini_D'] = intval($_POST['date_ini_D']);
    $form['date_ini_M'] = intval($_POST['date_ini_M']);
    $form['date_ini_Y'] = intval($_POST['date_ini_Y']);
    if (isset($_POST['return'])) {
        header("Location: " . $form['ritorno']);
        exit;
    }
    if ($_POST['hidden_req'] == 'clfoco') {
        $anagrafica = new Anagrafica();
        if (preg_match("/^id_([0-9]+)$/", $form['clfoco'], $match)) {
            $fornitore = $anagrafica->getPartnerData($match[1], 1);
        } else {
            $fornitore = $anagrafica->getPartner($form['clfoco']);
        }
        $form['hidden_req'] = '';
    }
}

//controllo i campi
if (!checkdate($form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y'])) {
    $msg .= '0+';
}
// fine controlli

if (isset($_POST['print']) && $msg == '') {
  $_SESSION['print_request'] = ['script_name' => 'print_partner_status', 'date' => $form['date_ini_Y'] . '-' . $form['date_ini_M'] . '-' . $form['date_ini_D'],'clfoco'=>$form['clfoco']];
  header("Location: sent_print.php");
  exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup',
    'custom/autocomplete'));
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
$gForm = new venditForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="2" class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['customer'] . "</td><td colspan=\"3\" class=\"FacetDataTD\">\n";
$select_fornitore = new selectPartner("clfoco");
$select_fornitore->selectDocPartner('clfoco', $form['clfoco'], $form['search']['clfoco'], 'clfoco', $script_transl['mesg'], $admin_aziend['mascli']);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['date_ini'] . "</td><td colspan=\"3\" class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_ini', $form['date_ini_D'], $form['date_ini_M'], $form['date_ini_Y'], 'FacetSelect', 1);
echo "</td>\n";
echo "</tr>\n";
echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
echo "<td align=\"left\"><input type=\"submit\" name=\"return\" value=\"" . $script_transl['return'] . "\">\n";
echo '<td align="right" colspan="2"> <input type="submit" accesskey="i" name="preview" value="';
echo $script_transl['view'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";

if (isset($_POST['preview'])) {
    $paymov = new Schedule;
    if ($form['clfoco']>=100000001){
      $admin_aziend['mascli']=$form['clfoco'];
    }

    $paymov->setScheduledPartner($admin_aziend['mascli']);
    echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
    if (sizeof($paymov->Partners) > 0) {
        $anagrafica = new Anagrafica();
        echo "<tr>";
        $linkHeaders = new linkHeaders($script_transl['header']);
        $linkHeaders->setAlign(array('right', 'right', 'right', 'center', 'center', 'center', 'center'));
        $linkHeaders->output();
        echo "</tr>";
        foreach ($paymov->Partners as $p) {
            $ctrl_close_partner = false;
            $prt = $anagrafica->getPartner($p);
            echo '<tr>';
            echo '<td class="bg-info text-info text-center" colspan=7><b>' . $prt['ragso1'] . " " . $prt['ragso2'] .
            "</b> tel:" . gaz_html_call_tel($prt['telefo']) .
            " fax:" . $prt['fax'] .
            " mob:" . gaz_html_call_tel($prt['cell']) . '<a class="btn btn-xs btn-info" target="_blank" href="../inform/reconstruction_schedule.php?id_partner='.$prt['codice'].'">Controlla</a></td>';
            echo "</tr>\n";
            $paymov->getPartnerStatus($p, $form['date_ini_Y'] . '-' . $form['date_ini_M'] . '-' . $form['date_ini_D']);
            foreach ($paymov->PartnerStatus as $k => $v) {
                echo "<tr>";
                echo "<td class=\"FacetDataTDred\" colspan='2'>REF: $k</td>";
                echo "<td colspan='3'><a class=\"btn btn-xs btn-edit\" href=\"../contab/admin_movcon.php?Update&id_tes=" . $paymov->docData[$k]['id_tes'] . "\"><i class=\"glyphicon glyphicon-edit\"></i>" .
                $paymov->docData[$k]['id_tes'] . ' ' .
                $paymov->docData[$k]['descri'];
                if ($paymov->docData[$k]['numdoc'] >= 1) {
                    echo ' n.' .
                    $paymov->docData[$k]['numdoc'] . '/' .
                    $paymov->docData[$k]['seziva'] . ' del ' .
                    gaz_format_date($paymov->docData[$k]['datdoc']);
                }
                echo "</a></td>\n<td colspan='2'></td>\n</tr>\n";
                foreach ($v as $ki => $vi) {
                    $ctrl_close_paymov = false;
                    $lnk = '';
                    $class_paymov = 'btn btn-success';
                    $v_op = '';
                    $cl_exp = '';
                    if ($vi['op_val'] >= 0.01) {
                        $v_op = gaz_format_number($vi['op_val']);
                    }
                    $v_cl = '';
                    if ($vi['cl_val'] >= 0.01) {
                        $v_cl = gaz_format_number($vi['cl_val']);
                        $cl_exp = gaz_format_date($vi['cl_exp']);
                    }
                    $expo = '';
                    if ($vi['expo_day'] >= 1) {
                        $expo = $vi['expo_day'];
                        if (round($vi['cl_val'],2) == round($vi['op_val'],2)) {
                            $vi['status'] = 2; // la partita è chiusa ma è esposta a rischio insolvenza
                            $class_paymov = 'btn btn-warning';
                        }
                    } else {
                        if (round($vi['cl_val'],2) == round($vi['op_val'],2)) { // chiusa e non esposta
                            $cl_exp = '';
                            $class_paymov = 'btn btn-success';
                            $ctrl_close_paymov = true;
                        } elseif ($vi['status'] == 3) { // SCADUTA
                            $cl_exp = '';
                            $class_paymov = 'btn btn-danger';
                            $lnk = " &nbsp;<a title=\"Riscuoti\" class=\"btn btn-xs btn-default btn-pagamento\" href=\"customer_payment.php?partner=" . $p . "\"><i class=\"glyphicon glyphicon-euro\"></i></a>";
                        } elseif ($vi['status'] == 9) { // PAGAMENTO ANTICIPATO
                            $class_paymov = 'btn btn-default';
                            $vi['expiry'] = $vi['cl_exp'];
                        } elseif ($vi['status'] == 0) { // APERTA
							$class_paymov = 'btn btn-edit';
                            $lnk = " &nbsp;<a title=\"Riscuoti\" class=\"btn btn-xs btn-default btn-pagamento\" href=\"customer_payment.php?partner=" . $p . "\"><i class=\"glyphicon glyphicon-euro\"></i></a>";
                        }
                    }
                    echo "<tr>";
                    echo "<td class='text-right'>" . $vi['id'] . "</td>";
                    echo "<td class='text-right'>" . $v_op . "</td>";
                    echo "<td class='text-center'>" . gaz_format_date($vi['expiry']) . "</td>";
                    echo "<td class='text-right'>" . $v_cl . "</td>";
                    echo "<td class='text-center'>" . $cl_exp . "</td>";
                    echo "<td class='text-center'>" . $expo . "</td>";
                    echo "<td class='" . $class_paymov . "' align=\"center\">" . $script_transl['status_value'][$vi['status']] . " &nbsp; $lnk</td>";
                    echo "</tr>\n";
                }
                if ($ctrl_close_paymov) {
                    $ctrl_close_partner = true;
                    echo "<tr>";
                    echo '<td class="text-right" colspan="7"> &nbsp;<a title="Cancella tutti i movimenti relativi a questa partita oramai chiusa (rimarranno comunque i movimenti contabili)" class="btn btn-xs  btn-elimina" href="delete_schedule.php?id_tesdoc_ref=' . $k . '">' . $script_transl['delete'] . ' <i class="glyphicon glyphicon-trash"></i></a></td>';
                    echo "</tr>\n";
                    echo '<tr><td colspan="7"></td></tr>';
                }
            }
            if ($ctrl_close_partner == true) {
                echo "<tr>";
                echo "<td class=\"text-right\" colspan='7'><a title=\"Elimina tutte le partite chiuse di questo cliente\" class=\"btn btn-xs  btn-elimina\" href=\"delete_schedule.php?partner=" . $p . "\"><i class=\"glyphicon glyphicon-trash\"></i> &nbsp;" . $script_transl['remove'] . $prt['ragso1'] . " " . $prt['ragso2'] . "</a></td>";
                echo "</tr>\n";
                echo '<tr><td colspan="7"></td></tr>';
            }
        }
        echo "\t<tr>\n";
        echo '<td class="FacetFieldCaptionTD" colspan="5" align="right"><input type="submit" name="print" value="';
        echo $script_transl['print'];
        echo '">';
        echo "\t </td>\n";
        echo "<td class=\"text-right\" colspan='2'><a title=\"Elimina tutte le partite chiuse di tutti i clienti\" class=\"btn btn-xs  btn-elimina\" href=\"delete_schedule.php?all\"><i class=\"glyphicon glyphicon-trash\"></i> &nbsp;" . $script_transl['remove'] .  " TUTTI!!!</a></td>";
        echo "\t </tr>\n";
    } else {
        echo "<tr><td class=\"FacetDataTDred\" align=\"center\">" . $script_transl['errors'][1] . "</td></tr>\n";
    }
    echo "</table></form>";
}
?>
<?php

require("../../library/include/footer.php");
?>
