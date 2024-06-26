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
if ($admin_aziend['mas_staff'] <= 199) { // non ho messo il mastro collaboratori in configurazione azienda
    $msg .= "21+";
}
if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
  $form = array_merge(gaz_dbi_parse_post('clfoco'), gaz_dbi_parse_post('staff'), gaz_dbi_parse_post('anagra'));
  $form['ritorno'] = $_POST['ritorno'];
  $form['hidden_req'] = $_POST['hidden_req'];
  $form['e_mail'] = trim($form['e_mail']);
  $form['datnas_Y'] = intval($_POST['datnas_Y']);
  $form['datnas_M'] = intval($_POST['datnas_M']);
  $form['datnas_D'] = intval($_POST['datnas_D']);
	if (substr($form['end_date'],-4)<=1999) {
		$form['end_date'] = '';
	}
  $toDo = 'update';
  if (isset($_POST['Insert'])) {
      $toDo = 'insert';
  }

  if ($form['hidden_req'] == 'toggle') { // e' stato accettato il link ad una anagrafica esistente
    $rs_a = gaz_dbi_get_row($gTables['anagra'], 'id', $form['id_anagra']);
    $form = array_merge($form, $rs_a);
    $form['ragso1'] = empty($rs_a['legrap_pf_cognome']) ? $rs_a['ragso1'] : $rs_a['legrap_pf_cognome'];
    $form['ragso2'] = empty($rs_a['legrap_pf_nome'])?$rs_a['ragso1']:$rs_a['legrap_pf_nome'];
    $form['hidden_req'] = '';
  }

  if (isset($_POST['Submit'])) { // conferma tutto
    $anagrafica = new Anagrafica();
    $real_code = $admin_aziend['mas_staff'] * 1000000 + $form['codice'];
    $rs_same_code = gaz_dbi_dyn_query('*', $gTables['clfoco'], " codice = " . $real_code, "codice", 0, 1);
    $same_code = gaz_dbi_fetch_array($rs_same_code);
    // inizio controllo campi
    if ($same_code && $toDo == 'insert') { // c'è già uno stesso codice ed e' un inserimento
      $form['codice'] ++; // lo aumento di 1
      $msg .= "18+";
    }
    if (strlen($form["ragso1"]) < 3) {
        $msg.='0+';
    }
    if ( gaz_dbi_get_row($gTables['company_config'], 'var', 'consenti_nofisc')['val']==0 ) { // se la configurazione avanzata azienda è molto permissiva non eseguo i controlli sui dati fiscali
      $rs_same_id_contract = gaz_dbi_dyn_query('*', $gTables['staff'], " id_contract = " . $form['id_contract']." AND id_clfoco <> ".$real_code , "id_staff", 0, 1);
      $same_id_contract = gaz_dbi_fetch_array($rs_same_id_contract);
      if ($same_id_contract) { // matricola esistente
          $msg .= "22+";
      }
      require("../../library/include/check.inc.php");
      if ($form["sexper"] <>'G' ) {
        if (empty($form["indspe"])) {
          $msg.='1+';
        }
        // faccio i controlli sul codice postale
        $rs_pc = gaz_dbi_get_row($gTables['country'], 'iso', $form["country"]);
        $cap = new postal_code;
        if ($cap->check_postal_code($form["capspe"], $form["country"], $rs_pc['postal_code_length'])) {
          $msg.='2+';
        }
        if (empty($form["citspe"])) {
          $msg.='3+';
        }
        if (empty($form["prospe"])) {
          $msg.='4+';
        }
        if (empty($form["sexper"])) {
          $msg.='5+';
        }
        $cf_pi = new check_VATno_TAXcode();
        $r_cf = $cf_pi->check_TAXcode($form['codfis'], $form['country']);
        if (!empty($r_pi)) {
          $msg .= "9+";
        }
        if (!empty($r_cf)) {
          $msg .= "11+";
        }
        if (!($form['codfis'] == "") && !($form['codfis'] == "00000000000") && $toDo == 'insert') {
          $partner_with_same_cf = $anagrafica->queryPartners('*', "codice <> " . $real_code . " AND codice BETWEEN " . $admin_aziend['mas_staff'] . "000000 AND " . $admin_aziend['mas_staff'] . "999999 AND codfis = '" . $form['codfis'] . "'", "codfis DESC", 0, 1);
          if ($partner_with_same_cf) { // c'è già un lavoratore sul piano dei conti
            $msg .= "12+";
          } elseif ($form['id_anagra'] == 0) { // � un nuovo lavoratore senza anagrafica
            $rs_anagra_with_same_cf = gaz_dbi_dyn_query('*', $gTables['anagra'], " codfis = '" . $form['codfis'] . "'", "codfis DESC", 0, 1);
            $anagra_with_same_cf = gaz_dbi_fetch_array($rs_anagra_with_same_cf);
            if ($anagra_with_same_cf) { // c'è già un'anagrafica con lo stesso CF non serve reinserirlo ma avverto
              // devo attivare tutte le interfacce per la scelta!
              $anagra = $anagra_with_same_cf;
              $msg .= '16+';
            }
          }
        }
        if (empty($form['codfis'])) {
          $msg .= "14+";
        }
        $uts_datnas = mktime(0, 0, 0, $form['datnas_M'], $form['datnas_D'], $form['datnas_Y']);
        if (!checkdate($form['datnas_M'], $form['datnas_D'], $form['datnas_Y']) && ($admin_aziend['country'] != $form['country'] )) {
          $msg .= "19+";
        }
        if (!filter_var($form['e_mail'], FILTER_VALIDATE_EMAIL) && !empty($form['e_mail'])) {
          $msg .= "20+";
        }
      } else { // è una persona giuridica che uso per fare un bonifico (es.cessione del quinto)
        $form['codfis'] = "00000000000";
      }
      $iban = new IBAN;
      if (!empty($form['iban']) && !$iban->checkIBAN($form['iban'])) {
          $msg.='6+';
      }
      if (!empty($form['iban']) && (substr($form['iban'], 0, 2) <> $form['country'])) {
          $msg.='7+';
      }
    }
    if (empty($msg)) { // nessun errore
        $form['codice'] = $real_code;
        $form['id_clfoco'] = $real_code;
        $form['datnas'] =(isset($uts_datnas))?date("Ymd", $uts_datnas):date("Ymd");
        $form['legrap_pf_cognome'] = trim($form['ragso1']);
        $form['legrap_pf_nome'] = trim($form['ragso2']);
        $form['start_date'] = gaz_format_date($form['start_date'], true);
        $form['end_date'] = gaz_format_date($form['end_date'], true);
        if ($toDo == 'insert') {
            if ($form['id_anagra'] > 0) {
                gaz_dbi_table_insert('clfoco', $form);
                gaz_dbi_table_insert('staff', $form);
            } else {
                $anagrafica->insertPartner($form);
                gaz_dbi_table_insert('staff', $form);
            }
        } elseif ($toDo == 'update') {
            $anagrafica->updatePartners($form['codice'], $form);
            gaz_dbi_table_update('staff',array('id_clfoco',$form['codice']), $form);
        }
        header("Location: staff_report.php");
        exit;
    }
  } elseif (isset($_POST['Return'])) { // torno indietro
    header("Location: " . $form['ritorno']);
    exit;
  }
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
  $anagrafica = new Anagrafica();
  $form = $anagrafica->getPartner(intval($admin_aziend['mas_staff'] * 1000000 + intval($_GET['codice'])));
  $staff = gaz_dbi_get_row($gTables['staff'], 'id_clfoco', $form['codice']);
  $form += $staff;
  $form['codice'] = intval(substr($form['codice'], 3));
  $toDo = 'update';
  $form['search']['id_des'] = '';
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
  $form['datnas_Y'] = substr($form['datnas'], 0, 4);
  $form['datnas_M'] = substr($form['datnas'], 5, 2);
  $form['datnas_D'] = substr($form['datnas'], 8, 2);
  $form['start_date'] = gaz_format_date($staff['start_date'], false, false);
	if (is_string($staff['end_date']) && substr($staff['end_date'],0,4)>1999) {
		$form['end_date'] = gaz_format_date($staff['end_date'], false, false);
	} else {
		$form['end_date'] = '';
	}
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
  $anagrafica = new Anagrafica();
  $last = $anagrafica->queryPartners('*', "codice BETWEEN " . $admin_aziend['mas_staff'] . "000000 AND " . $admin_aziend['mas_staff'] . "999999", "codice DESC", 0, 1);
  $form = array_merge(gaz_dbi_fields('clfoco'), gaz_dbi_fields('staff'), gaz_dbi_fields('anagra'));
  if (isset($last[0]['codice'])) {
      $form['codice'] = substr($last[0]['codice'], 3) + 1;
  } else {
      $form['codice'] = 1;
  }
  $toDo = 'insert';
  $form['search'] = '';
  $form['country'] = $admin_aziend['country'];
  $form['datnas_Y'] = 1900;
  $form['datnas_M'] = 1;
  $form['datnas_D'] = 1;
  $form['start_date'] = date("d/m/Y");
  $form['end_date'] = '';
  $form['counas'] = $admin_aziend['country'];
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup', 'custom/autocomplete'));
echo "<SCRIPT type=\"text/javascript\">\n";
echo "function toggleContent(currentContent) {
        var thisContent = document.getElementById(currentContent);
        if ( thisContent.style.display == 'none') {
           thisContent.style.display = '';
           return;
        }
        thisContent.style.display = 'none';
      }
      function selectValue(currentValue) {
         document.form.id_anagra.value=currentValue;
         document.form.hidden_req.value='toggle';
         document.form.submit();
      }
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
$(function () {
    $('#start_date').datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
    $('#end_date').datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
});

</script>
";
echo "<form method=\"POST\" name=\"form\">\n";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $form['ritorno'] . "\">\n";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['id_anagra'] . "\" name=\"id_anagra\" />\n";
echo "<input type=\"hidden\" name=\"" . ucfirst($toDo) . "\" value=\"\">";
$gForm = new GAzieForm();
if ($toDo == 'insert') {
    echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['ins_this'] . ' con ' . $script_transl['codice'] . " n° <input type=\"text\" name=\"codice\" value=\"" . $form['codice'] . "\" align=\"right\" maxlength=\"6\" /></div>\n";
} else {
    echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['upd_this'] . " '" . $form['codice'] . "'";
    echo "<input type=\"hidden\" value=\"" . $form['codice'] . "\" name=\"codice\" /></div>\n";
}
if (!empty($msg)) {
    echo '<div align="center"><table>';
    if (isset($anagra)) {
        echo "<tr style=\"cursor:pointer;\">\n";
        echo "\t <td>\n";
        echo "\t </td>\n";
        echo "<td colspan=\"2\"><div onmousedown=\"toggleContent('id_anagra')\" class=\"FacetDataTDred\">";
        echo ' &dArr; ' . $script_transl['link_anagra'] . " &dArr;</div>\n";
        echo "<div  style=\"z-index:1000;\" class=\"selectContainer\" id=\"id_anagra\" onclick=\"selectValue('" . $anagra['id'] . "');\" >\n";
        echo "<div class=\"selectHeader\"> ID = " . $anagra['id'] . "</div>\n";
        echo '<table cellspacing="0" cellpadding="0" width="100%" class="selectTable">';
        echo "\n<tr class=\"odd\"><td>" . $script_transl['ragso1'] . " </td><td> " . $anagra['ragso1'] . "</td></tr>\n";
        echo "<tr class=\"even\"><td>" . $script_transl['ragso2'] . " </td><td> " . $anagra['ragso2'] . "</td></tr>\n";
        echo "<tr class=\"odd\"><td>" . $script_transl['sexper'] . " </td><td> " . $anagra['sexper'] . "</td></tr>\n";
        echo "<tr class=\"even\"><td>" . $script_transl['indspe'] . " </td><td> " . $anagra['indspe'] . "</td></tr>\n";
        echo "<tr class=\"odd\"><td>" . $script_transl['capspe'] . " </td><td> " . $anagra['capspe'] . "</td></tr>\n";
        echo "<tr class=\"even\"><td>" . $script_transl['citspe'] . " </td><td> " . $anagra['citspe'] . " (" . $anagra['prospe'] . ")</td></tr>\n";
        echo "<tr class=\"odd\"><td>" . $script_transl['telefo'] . " </td><td> " . $anagra['telefo'] . "</td></tr>\n";
        echo "<tr class=\"even\"><td>" . $script_transl['cell'] . " </td><td> " . $anagra['cell'] . "</td></tr>\n";
        echo "</div></table></div>\n";
        echo "\t </td>\n";
        echo "</tr>\n";
    } else {
      echo '<tr><td colspan="3" class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
    }
    echo '</table></div>';
}
?>
<div class="panel panel-default gaz-table-form div-bordered">
  <div class="container-fluid">
  <ul class="nav nav-pills">
    <li class="active"><a data-toggle="pill" href="#home">Anagrafica</a></li>
    <li style="float: right;"><input class="btn btn-warning" name="Submit" type="submit" value="<?php echo ucfirst($script_transl[$toDo]); ?>"></li>
  </ul>

  <div class="tab-content">
    <div id="home" class="tab-pane fade in active">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ragso1" class="col-sm-4 control-label"><?php echo $script_transl['ragso1']; ?> *</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['ragso1']; ?>" name="ragso1" minlenght="2" maxlength="50"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ragso2" class="col-sm-4 control-label"><?php echo $script_transl['ragso2']; ?> </label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['ragso2']; ?>" name="ragso2" maxlength="50"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="id_contract" class="col-sm-4 control-label"><?php echo $script_transl['id_contract']; ?> </label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['id_contract']; ?>" name="id_contract" maxlength="9"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="job_title" class="col-sm-4 control-label"><?php echo $script_transl['job_title']; ?> </label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['job_title']; ?>" name="job_title" maxlength="50"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="Codice_CCNL" class="col-sm-4 control-label"><?php echo $script_transl['Codice_CCNL']; ?> </label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['Codice_CCNL']; ?>" name="Codice_CCNL" id="search_Codice_CCNL" maxlength="30"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="sexper" class="col-sm-4 control-label"><?php echo $script_transl['sexper']; ?> </label>
    <?php
$gForm->variousSelect('sexper', $script_transl['sexper_value'], $form['sexper']);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="indspe" class="col-sm-4 control-label"><?php echo $script_transl['indspe']; ?> </label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['indspe']; ?>" name="indspe" maxlength="50"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="capspe" class="col-sm-4 control-label"><?php echo $script_transl['capspe']; ?> </label>
                    <input class="col-sm-4" type="text" id="search_location-capspe" value="<?php echo $form['capspe']; ?>" name="capspe" maxlength="10"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="citspe" class="col-sm-4 control-label"><?php echo $script_transl['citspe']; ?> </label>
                    <input class="col-sm-4" type="text" id="search_location" value="<?php echo $form['citspe']; ?>" name="citspe" maxlength="60"/>
                    <div class="text-right"><input class="col-sm-1" type="text" id="search_location-prospe" value="<?php echo $form['prospe']; ?>" name="prospe" maxlength="2"/></div>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="country" class="col-sm-4 control-label"><?php echo $script_transl['country']; ?> </label>
    <?php
$gForm->selectFromDB('country', 'country', 'iso', $form['country'], 'iso', 0, ' - ', 'name');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="datnas" class="col-sm-4 control-label"><?php echo $script_transl['datnas']; ?> </label>
    <?php
$gForm->CalendarPopup('datnas', $form['datnas_D'], $form['datnas_M'], $form['datnas_Y']);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="luonas" class="col-sm-4 control-label"><?php echo $script_transl['luonas']; ?> </label>
                    <input class="col-sm-4" type="text" id="search_luonas" value="<?php echo $form['luonas']; ?>" name="luonas" maxlength="50"/>
                    <div class="text-right"><input class="col-sm-1" type="text" id="search_pronas" value="<?php echo $form['pronas']; ?>" name="pronas" maxlength="2"/></div>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="counas" class="col-sm-4 control-label"><?php echo $script_transl['counas']; ?> </label>
    <?php
$gForm->selectFromDB('country', 'counas', 'iso', $form['counas'], 'iso', 1, ' - ', 'name');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="codfis" class="col-sm-4 control-label"><a href="https://telematici.agenziaentrate.gov.it/VerificaCF/Scegli.do?parameter=verificaCf" target="blank"><?php echo $script_transl['codfis']; ?></a></label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['codfis']; ?>" name="codfis" id="codfis" maxlength="16"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="telefo" class="col-sm-4 control-label"><?php echo $script_transl['telefo']; ?> </label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['telefo']; ?>" name="telefo" maxlength="50"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="cell" class="col-sm-4 control-label"><?php echo $script_transl['cell']; ?> </label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['cell']; ?>" name="cell" maxlength="50"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="e_mail" class="col-sm-4 control-label"><?php echo $script_transl['e_mail']; ?></label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['e_mail']; ?>" name="e_mail" id="email" maxlength="60"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="iban" class="col-sm-4 control-label"><?php echo $script_transl['iban']; ?> </label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['iban']; ?>" name="iban" id="iban" maxlength="27" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="status" class="col-sm-4 control-label"><?php echo $script_transl['codice_campi']; ?> </label>
<?php
$gForm->selectFromDB('campi', 'codice_campi', 'codice', $form['codice_campi'], false, 1, ' - ', 'descri', '', 'col-sm-8', null, 'style="max-width: 250px;"');
?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="start_date" class="col-sm-4 control-label"><?php echo $script_transl['start_date']; ?> </label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['start_date']; ?>" name="start_date" id="start_date" maxlength="10" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="end_date" class="col-sm-4 control-label"><?php echo $script_transl['end_date']; ?> </label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['end_date']; ?>" name="end_date" id="end_date" maxlength="10" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="last_hourly_cost" class="col-sm-4 control-label"><?php echo $script_transl['last_hourly_cost']; ?> </label>
                    <input class="col-sm-8" type="number" value="<?php echo $form['last_hourly_cost']; ?>" name="last_hourly_cost" id="last_hourly_cost"  min="0" max="1000" step="0.01" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="status" class="col-sm-4 control-label"><?php echo $script_transl['status']; ?> </label>
    <?php
$gForm->variousSelect('status', $script_transl['status_value'], $form['status'], '', false);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="annota" class="col-sm-4 control-label"><?php echo $script_transl['annota']; ?> </label>
                    <textarea name="annota" rows="2" cols="50" maxlength="3000"><?php echo $form['annota']; ?></textarea>
                </div>
            </div>
        </div><!-- chiude row  -->
    </div>
  </div>
  </div>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
