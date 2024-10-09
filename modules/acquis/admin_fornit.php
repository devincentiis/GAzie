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
if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
  $form = array_merge(gaz_dbi_parse_post('clfoco'), gaz_dbi_parse_post('anagra'));
  $form['old_id_SIAN']=$_POST['old_id_SIAN'];
  $form['ritorno'] = $_POST['ritorno'];
  $form['hidden_req'] = $_POST['hidden_req'];
  $form['pec_email'] = trim($form['pec_email']);
  $form['e_mail'] = trim($form['e_mail']);
  $form['datnas_Y'] = intval($_POST['datnas_Y']);
  $form['datnas_M'] = intval($_POST['datnas_M']);
  $form['datnas_D'] = intval($_POST['datnas_D']);
  foreach ($_POST['search'] as $k => $v) {
      $form['search'][$k] = $v;
  }

  $toDo = 'update';
  if (isset($_POST['Insert'])) {
      $toDo = 'insert';
  }

  if ($form['hidden_req'] == 'toggle') { // e' stato accettato il link ad una anagrafica esistente
      $rs_a = gaz_dbi_get_row($gTables['anagra'], 'id', $form['id_anagra']);
      $form = array_merge($form, $rs_a);
  }

  if (isset($_POST['Submit'])) { // conferma tutto
    $anagrafica = new Anagrafica();
    // inizio controllo campi
    $real_code = $admin_aziend['masfor'] * 1000000 + $form['codice'];
    require("../../library/include/check.inc.php");
    $rs_same_code = gaz_dbi_dyn_query('*', $gTables['clfoco'], " codice = " . $real_code, "codice", 0, 1);
    $same_code = gaz_dbi_fetch_array($rs_same_code);
    if ($same_code && ($toDo == 'insert')) { // c'� gi� uno stesso codice
        $form['codice'] ++; // lo aumento di 1
        $msg .= "18+";
    }
    if (strlen($form["ragso1"]) < 3) {
        if (!empty($form["legrap_pf_nome"]) && !empty($form["legrap_pf_cognome"]) && $form["sexper"] != 'G') {// setto la ragione sociale con l'eventuale legale rappresentante
            $form["ragso1"] = strtoupper($form["legrap_pf_cognome"] . ' ' . $form["legrap_pf_nome"]);
        } else { // altrimenti do errore
            $msg .= '0+';
        }
    }
    if ( gaz_dbi_get_row($gTables['company_config'], 'var', 'consenti_nofisc')['val']==0 ) { // se la configurazione avanzata azienda è molto permissiva non eseguo i controlli sui dati fiscali
      if (empty($form["indspe"])) {
          $msg .= '1+';
      }
      // faccio i controlli sul codice postale
      $rs_pc = gaz_dbi_get_row($gTables['country'], 'iso', $form["country"]);
      if ( gaz_dbi_get_row($gTables['company_config'], 'var', 'check_cust_address')['val']==1 ) {
        $cap = new postal_code;
        if ($cap->check_postal_code($form["capspe"], $form["country"], $rs_pc['postal_code_length']) && $rs_pc['postal_code_length']>0) {
            $msg .= '2+';
        }
        if (empty($form["citspe"])) {
            $msg .= '3+';
        }
        if (empty($form["prospe"])) {
            $msg .= '4+';
        }
      }
      if (empty($form["sexper"])) {
          $msg .= '5+';
      }
      $iban = new IBAN;
      if (!empty($form['iban']) && !$iban->checkIBAN($form['iban'])) {
          $msg .= '6+';
      }
      if (!empty($form['iban']) && (substr($form['iban'], 0, 2) <> $form['country'])) {
          $msg .= '7+';
      }
      $cf_pi = new check_VATno_TAXcode();
      $r_pi = $cf_pi->check_VAT_reg_no($form['pariva'], $form['country']);

      // danielemz - temporaneo per imposta 2017- bolle doganali
      if (isset($form['pariva']) && trim($form['pariva']) == '99999999999') {
          $r_pi = "";
      }
      if (strlen(trim($form['codfis'])) == 11) {
          $r_cf = $cf_pi->check_VAT_reg_no($form['codfis'], $form['country']);
          if ($form['sexper'] != 'G') {
              $r_cf = 'Codice fiscale sbagliato per una persona fisica';
              $msg .= '8+';
          }
      } else {
          $r_cf = $cf_pi->check_TAXcode($form['codfis'], $form['country']);
      }
      if (!empty($r_pi)) {
          $msg .= "9+";
      }
      if ($form['codpag'] < 1) {
          $msg .= "17+";
      }

      if (!($form['pariva'] == "") && !($form['pariva'] == "00000000000")) {
          $partner_with_same_pi = $anagrafica->queryPartners('*', "codice <> " . $real_code . " AND codice BETWEEN " . $admin_aziend['masfor'] . "000000 AND " . $admin_aziend['masfor'] . "999999 AND pariva = '" . $form['pariva'] . "'", "pariva DESC", 0, 1);
          if ($partner_with_same_pi) { // c'� gi� un fornitore sul piano dei conti
              $msg .= "10+";
          } elseif ($form['id_anagra'] == 0) { // � un nuovo fornitore senza anagrafica
              $rs_anagra_with_same_pi = gaz_dbi_query_anagra(array("*"), $gTables['anagra'], array("pariva" => "='" . $form['pariva'] . "'"), array("pariva" => "DESC"), 0, 1);
              $anagra_with_same_pi = gaz_dbi_fetch_array($rs_anagra_with_same_pi);
              if ($anagra_with_same_pi) { // c'� gi� un'anagrafica con la stessa PI non serve reinserirlo ma avverto
                  // devo attivare tutte le interfacce per la scelta!
                  $anagra = $anagra_with_same_pi;
                  $msg .= '15+';
              }
          }
      }
      if (!empty($r_cf)) {
          $msg .= "11+";
      }
      if (!($form['codfis'] == "") && !($form['codfis'] == "00000000000")) {
          $partner_with_same_cf = $anagrafica->queryPartners('*', "codice <> " . $real_code . " AND codice BETWEEN " . $admin_aziend['masfor'] . "000000 AND " . $admin_aziend['masfor'] . "999999 AND codfis = '" . $form['codfis'] . "'", "codfis DESC", 0, 1);
          if ($partner_with_same_cf) { // c'� gi� un fornitore sul piano dei conti
              $msg .= "12+";
          } elseif ($form['id_anagra'] == 0) { // � un nuovo fornitore senza anagrafica
              $rs_anagra_with_same_cf = gaz_dbi_query_anagra(array("*"), $gTables['anagra'], array("codfis" => "='" . $form['codfis'] . "'"), array("codfis" => "DESC"), 0, 1);
              $anagra_with_same_cf = gaz_dbi_fetch_array($rs_anagra_with_same_cf);
              if ($anagra_with_same_cf) { // c'� gi� un'anagrafica con lo stesso CF non serve reinserirlo ma avverto
                  // devo attivare tutte le interfacce per la scelta!
                  $anagra = $anagra_with_same_cf;
                  $msg .= '16+';
              }
          }
      }
      if (empty($form['codfis'])) {
          if ($form['sexper'] == 'G') {
              $msg .= "13+";
              $form['codfis'] = $form['pariva'];
          } else {
              $msg .= "14+";
          }
      }
      if (empty(trim($form['external_service_descri'])) && $form['external_resp'] > 0) {
          $msg .= "21+";
      }
      $uts_datnas = mktime(0, 0, 0, $form['datnas_M'], $form['datnas_D'], $form['datnas_Y']);
      if (!checkdate($form['datnas_M'], $form['datnas_D'], $form['datnas_Y']) && ($admin_aziend['country'] != $form['country'] )) {
          $msg .= "19+";
      }
      if (!filter_var($form['pec_email'], FILTER_VALIDATE_EMAIL) && !empty($form['pec_email'])) {
          $msg .= "20+";
      }
      if (!filter_var($form['e_mail'], FILTER_VALIDATE_EMAIL) && !empty($form['e_mail'])) {
          $msg .= "20+";
      }
      if (!filter_var($form['e_mail'], FILTER_VALIDATE_EMAIL) && !empty($form['e_mail'])) {
          $msg .= "20+";
      }
      // il codice SIAN deve essere univoco nell'ambito clienti e fornitori
      if (intval($form['id_SIAN'])>0){
        $rs_same_code = gaz_dbi_dyn_query('*', $gTables['anagra'], " id_SIAN = " . $form['id_SIAN']);
        $rows=gaz_dbi_num_rows($rs_same_code);
        if ($rows>0 && ($toDo == 'insert')) { // c'è già uno stesso codice
          $form['id_SIAN'] ++; // lo aumento di 1 e segnalo
          $msg .= "22+";
        }
        if ($toDo == 'update' && intval($form['old_id_SIAN'])<>intval($form['id_SIAN'])) {// se sono in update faccio il controllo solo se ho cambiato il codice SIAN
          foreach ($rs_same_code as $row){
            if ($row['ragso1']!==$form['ragso1'] AND $row['id_SIAN']==$form['id_SIAN']){
              $form['id_SIAN'] ++; // c'è già uno stesso codice lo aumento di 1 e segnalo
              $msg .= "22+";
            }
          }
        }
      }
    }
    if (empty($msg)) { // nessun errore
      $form['codice'] = $real_code;
      $form['datnas'] = date("Ymd", $uts_datnas);
      if ($toDo == 'insert') {
          if ($form['id_anagra'] > 0) {
              gaz_dbi_table_insert('clfoco', $form);
          } else {
              $anagrafica->insertPartner($form);
          }
      } elseif ($toDo == 'update') {
          $anagrafica->updatePartners($form['codice'], $form);
      }
      header('Location: report_fornit.php');
      exit;
    }
  } elseif (isset($_POST['Return'])) { // torno indietro
      header("Location: " . $form['ritorno']);
      exit;
  }
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
  $anagrafica = new Anagrafica();
  $form = $anagrafica->getPartner(intval($admin_aziend['masfor'] * 1000000 + $_GET['codice']));
  $form['codice'] = intval(substr($form['codice'], 3));
  $toDo = 'update';
  $form['search']['id_des'] = '';
  $form['search']['fiscal_rapresentative_id'] = '';
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
  $form['datnas_Y'] = ($form['datnas'])?substr($form['datnas'], 0, 4):'';
  $form['datnas_M'] = ($form['datnas'])?substr($form['datnas'], 5, 2):'';
  $form['datnas_D'] = ($form['datnas'])?substr($form['datnas'], 8, 2):'';
	$form['external_resp']=$form['external_resp'];
  $form['old_id_SIAN']=$form['id_SIAN'];
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
  $anagrafica = new Anagrafica();
  $last = $anagrafica->queryPartners('*', "codice BETWEEN " . $admin_aziend['masfor'] . "000000 AND " . $admin_aziend['masfor'] . "999999", "codice DESC", 0, 1);
  $form = array_merge(gaz_dbi_fields('clfoco'), gaz_dbi_fields('anagra'));
  $form['codice'] = substr($last[0]['codice'], 3) + 1;
  $toDo = 'insert';
  $form['search']['id_des'] = '';
  $form['search']['fiscal_rapresentative_id'] = '';
  $form['country'] = $admin_aziend['country'];
  $form['id_language'] = $admin_aziend['id_language'];
  $form['id_currency'] = $admin_aziend['id_currency'];
  $form['datnas_Y'] = 1900;
  $form['datnas_M'] = 1;
  $form['datnas_D'] = 1;
  $form['counas'] = $admin_aziend['country'];
  $form['spefat'] = 'N';
  $form['stapre'] = 'N';
  $form['allegato'] = 1;
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
	$form['external_resp']="";
	$form["external_service_descri"]="";
	$form['id_SIAN']="";
  $form['old_id_SIAN']="";
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('custom/autocomplete',
    'calendarpopup/CalendarPopup',
        /** ENRICO FEDELE */
        /* 'jquery/jquery-1.7.1.min',
          'jquery/ui/jquery.ui.core',
          'jquery/ui/jquery.ui.widget',
          'jquery/ui/jquery.ui.position',
          'jquery/ui/jquery.ui.autocomplete', */
        /** ENRICO FEDELE */        ));
?>
<script>
<?php
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
      }\n
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
";
?>
$(function() {
    $('#iban,#codfis').keyup(function(){
        this.value = this.value.toUpperCase();
    });

});
</script>
<?php
echo "<form method=\"POST\" name=\"form\">\n";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $form['ritorno'] . "\">\n";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['id_anagra'] . "\" name=\"id_anagra\" />\n";
echo "<input type=\"hidden\" name=\"" . ucfirst($toDo) . "\" value=\"\">";
$gForm = new gazieForm();
if ($toDo == 'insert') {
    echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['ins_this'] . ' con ' . $script_transl['codice'] . " n° <input type=\"text\" name=\"codice\" value=\"" . $form['codice'] . "\" align=\"right\" maxlength=\"6\" /></div>\n";
} else {
    echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['upd_this'] . " '" . $form['codice'] . "'";
    echo "<input type=\"hidden\" value=\"" . $form['codice'] . "\" name=\"codice\" /></div>\n";
}
if (!empty($msg)) {
    echo '<div align="center"><table>';
    if (isset($anagra)) {
        echo "<tr>\n";
        echo "\t <td>\n";
        echo "\t </td>\n";
        echo "<td colspan=\"2\"><div onmousedown=\"toggleContent('id_anagra')\" class=\"FacetDataTDred\" style=\"cursor:pointer;\">";
        echo ' &dArr; ' . $script_transl['link_anagra'] . " &dArr;</div>\n";
        echo "<div style=\"display: ;\" class=\"selectContainer\" id=\"id_anagra\" onclick=\"selectValue('" . $anagra['id'] . "');\" >\n";
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
        echo "<tr class=\"odd\"><td>" . $script_transl['fax'] . " </td><td> " . $anagra['fax'] . "</td></tr>\n";
        echo "</div></table></div>\n";
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
    <li><a data-toggle="pill" href="#commer">Impostazioni</a></li>
    <li style="float: right;"><input class="btn btn-warning" name="Submit" type="submit" value="<?php echo ucfirst($script_transl[$toDo]); ?>"></li>
  </ul>
  <div class="tab-content">
    <div id="home" class="tab-pane fade in active">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ragso1" class="col-sm-4 control-label"><?php echo $script_transl['ragso1']; ?> *</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['ragso1']; ?>" name="ragso1" minlenght="8" maxlength="50" placeholder="<?php echo $script_transl['ragso1_placeholder']; ?>"/>
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
                    <label for="legrap_pf_nome" class="col-sm-4 control-label"><?php echo $script_transl['legrap_pf_nome']; ?> </label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['legrap_pf_nome']; ?>" name="legrap_pf_nome" maxlength="50"/>
                    <div class="text-right"><input class="col-sm-4" type="text" value="<?php echo $form['legrap_pf_cognome']; ?>" name="legrap_pf_cognome" maxlength="50"/></div>
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
                    <label for="indspe" class="col-sm-4 control-label"><?php echo $script_transl['indspe']; ?> *</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['indspe']; ?>" name="indspe" maxlength="50"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="capspe" class="col-sm-4 control-label"><?php echo $script_transl['capspe']; ?> *</label>
                    <input class="col-sm-4" type="text" id="search_location-capspe" value="<?php echo $form['capspe']; ?>" name="capspe" maxlength="10"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="citspe" class="col-sm-4 control-label"><?php echo $script_transl['citspe']; ?> *</label>
                    <input class="col-sm-4" type="text" id="search_location" value="<?php echo $form['citspe']; ?>" name="citspe" maxlength="60"/>
                    <div class="text-right"><input class="col-sm-1" type="text" id="search_location-prospe" value="<?php echo $form['prospe']; ?>" name="prospe" maxlength="2"/></div>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="country" class="col-sm-4 control-label"><?php echo $script_transl['country']; ?> *</label>
    <?php
$gForm->selectFromDB('country', 'country', 'iso', $form['country'], 'iso', 0, ' - ', 'name');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="id_language" class="col-sm-4 control-label"><?php echo $script_transl['id_language']; ?> *</label>
    <?php
$gForm->selectFromDB('languages', 'id_language', 'lang_id', $form['id_language'], 'lang_id', 1, ' - ', 'title_native');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="id_currency" class="col-sm-4 control-label"><?php echo $script_transl['id_currency']; ?> *</label>
    <?php
$gForm->selectFromDB('currencies', 'id_currency', 'id', $form['id_currency'], 'id', 1, ' - ', 'curr_name');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="fiscal_rapresentative_id" class="col-sm-4 control-label"><?php echo $script_transl['fiscal_rapresentative_id']; ?> </label>
    <?php
$select_fiscal_rapresentative_id = new selectPartner("fiscal_rapresentative_id");
$select_fiscal_rapresentative_id->selectAnagra('fiscal_rapresentative_id', $form['fiscal_rapresentative_id'], $form['search']['fiscal_rapresentative_id'], 'fiscal_rapresentative_id', $script_transl['mesg']);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="sedleg" class="col-sm-4 control-label"><?php echo $script_transl['sedleg']; ?> </label>
                    <textarea name="sedleg" rows="3" cols="50" maxlength="200" placeholder="scrivere nel formato:
Via del Quirinale, 1
00100 ROMA (RM)" ><?php echo $form['sedleg']; ?></textarea>
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
                    <label for="telefo" class="col-sm-4 control-label"><?php echo $script_transl['telefo']; ?> </label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['telefo']; ?>" name="telefo" maxlength="50"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="fax" class="col-sm-4 control-label"><?php echo $script_transl['fax']; ?> </label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['fax']; ?>" name="fax" maxlength="50"/>
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
                    <label for="codfis" class="col-sm-4 control-label"><a href="https://telematici.agenziaentrate.gov.it/VerificaCF/Scegli.do?parameter=verificaCf" target="blank"><?php echo $script_transl['codfis']; ?></a></label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['codfis']; ?>" name="codfis" id="codfis" maxlength="16"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="pariva" class="col-sm-4 control-label"><a href="https://telematici.agenziaentrate.gov.it/VerificaPIVA/Scegli.do?parameter=verificaPiva" target="blank"><?php echo $script_transl['pariva']; ?></a></label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['pariva']; ?>" name="pariva" maxlength="28"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="fiscal_reg" class="col-sm-4 control-label"><?php echo $script_transl['fiscal_reg']; ?></label>
                    <?php
                      $gForm->selectFromXML('../../library/include/fae_regime_fiscale.xml', 'fiscal_reg', 'fiscal_reg', $form['fiscal_reg'], true,'','col-xs-8');
                    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="pec_email" class="col-sm-4 control-label"><a href="https://www.inipec.gov.it/cerca-pec" target="blank"><?php echo $script_transl['pec_email']; ?></a></label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['pec_email']; ?>" name="pec_email" id="pec_email" maxlength="60"/>
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
                    <label for="id_SIAN" class="col-sm-4 control-label">Codice identificativo SIAN</label>
                    <input class="col-sm-4" type="text" onkeyup="this.value=this.value.replace(/[^\d]/,'');" value="<?php echo $form['id_SIAN']; ?>" name="id_SIAN" id="id_SIAN" maxlength="10" />
                    <input type="hidden" value="<?php echo $form['old_id_SIAN']; ?>" name="old_id_SIAN" id="old_id_SIAN"  />
                </div>
            </div>
        </div><!-- chiude row  -->
      </div><!-- chiude tab-pane  -->
      <div id="commer" class="tab-pane fade">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="codpag" class="col-sm-4 control-label"><?php echo $script_transl['codpag']; ?> </label>
    <?php
$gForm->selectFromDB('pagame', 'codpag', 'codice', $form['codpag'], 'tippag`, `giodec`, `numrat', true, ' ', 'descri');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="external_resp" class="col-sm-4 control-label"><?php echo $script_transl['external_resp']; ?> </label>
    <?php
$gForm->variousSelect('external_resp', $script_transl['external_resp_value'], $form['external_resp'], 'FacetSelect', false);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                  <label for="external_service_descri" class="col-sm-4 control-label"><?php echo $script_transl['external_service_descri']; ?> </label>
                  <textarea name="external_service_descri" rows="2" cols="50" class="FacetInput"><?php echo $form["external_service_descri"]; ?></textarea></td>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="sconto" class="col-sm-4 control-label"><?php echo $script_transl['sconto']; ?></label>
                    <input class="col-sm-1" type="text" value="<?php echo $form['sconto']; ?>" name="sconto" id="sconto" maxlength="5" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="banapp" class="col-sm-4 control-label"><?php echo $script_transl['banapp']; ?> </label>
    <?php
$select_banapp = new selectbanapp("banapp");
$select_banapp->addSelected($form["banapp"]);
$select_banapp->output();
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="portos" class="col-sm-4 control-label"><?php echo $script_transl['portos']; ?> </label>
    <?php
$gForm->selectFromDB('portos', 'portos', 'codice', $form['portos'], 'codice', false, ' ', 'descri');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="spediz" class="col-sm-4 control-label"><?php echo $script_transl['spediz']; ?> </label>
    <?php
$gForm->selectFromDB('spediz', 'spediz', 'codice', $form['spediz'], 'codice', false, ' ', 'descri');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="imball" class="col-sm-4 control-label"><?php echo $script_transl['imball']; ?> </label>
    <?php
$gForm->selectFromDB('imball', 'imball', 'codice', $form['imball'], 'codice', true, ' ', 'descri');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="listin" class="col-sm-4 control-label"><?php echo $script_transl['listin']; ?> </label>
    <?php
$gForm->selectNumber('listin', $form['listin'], 0, 1, 4);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="id_agente" class="col-sm-4 control-label"><?php echo $script_transl['id_agente']; ?> </label>
    <?php
$select_agente = new selectAgente("id_agente", "C");
$select_agente->addSelected($form["id_agente"]);
$select_agente->output();
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="cosric" class="col-sm-4 control-label"><?php echo $script_transl['cosric']; ?> </label>
    <?php
$gForm->selectAccount('cosric', $form['cosric'], 3);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="operation_type" class="col-sm-4 control-label"><?php echo $script_transl['operation_type']; ?> </label>
    <?php
$gForm->selectFromXML('../../library/include/operation_type.xml', 'operation_type', 'operation_type', $form['operation_type'], true, '', 'col-sm-6');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="destin" class="col-sm-4 control-label"><?php echo $script_transl['destin']; ?> </label>
                    <textarea name="destin" rows="2" cols="50" maxlength="200"><?php echo $form['destin']; ?></textarea>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="id_des" class="col-sm-4 control-label"><?php echo $script_transl['id_des']; ?> </label>
    <?php
$select_id_des = new selectPartner("id_des");
$select_id_des->selectAnagra('id_des', $form['id_des'], $form['search']['id_des'], 'id_des', $script_transl['mesg']);
    ?>
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
                    <label for="maxrat" class="col-sm-4 control-label"><?php echo $script_transl['maxrat']; ?> </label>
                    <input class="col-sm-8" type="maxrat" value="<?php echo $form['maxrat']; ?>" name="maxrat" id="maxrat" maxlength="16" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ragdoc" class="col-sm-4 control-label"><?php echo $script_transl['ragdoc']; ?> </label>
    <?php
$gForm->variousSelect('ragdoc', $script_transl['yn_value'], $form['ragdoc']);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="speban" class="col-sm-4 control-label"><?php echo $script_transl['speban']; ?> </label>
    <?php
$gForm->variousSelect('speban', $script_transl['yn_value'], $form['speban']);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="addbol" class="col-sm-4 control-label"><?php echo $script_transl['addbol']; ?> </label>
    <?php
$gForm->variousSelect('addbol', $script_transl['yn_value'], $form['addbol']);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="spefat" class="col-sm-4 control-label"><?php echo $script_transl['spefat']; ?> </label>
    <?php
$gForm->variousSelect('spefat', $script_transl['yn_value'], $form['spefat']);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="stapre" class="col-sm-4 control-label"><?php echo $script_transl['stapre']; ?> </label>
    <?php
$gForm->variousSelect('stapre', $script_transl['stapre_value'], $form['stapre']);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="aliiva" class="col-sm-4 control-label"><?php echo $script_transl['aliiva']; ?> </label>
    <?php
$gForm->selectFromDB('aliiva', 'aliiva', 'codice', $form['aliiva'], 'codice', 1, ' - ', 'descri');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ritenuta" class="col-sm-4 control-label"><?php echo $script_transl['ritenuta']; ?> </label>
                    <input class="col-sm-8" type="ritenuta" value="<?php echo $form['ritenuta']; ?>" name="ritenuta" id="ritenuta" maxlength="4" />
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
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="visannota" class="col-sm-4 control-label"><?php echo $script_transl['visannota']; ?> </label>
    <?php
$gForm->variousSelect('visannota', $script_transl['yn_value'], $form['visannota']);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="allegato" class="col-sm-4 control-label"><?php echo $script_transl['allegato']; ?> </label>
    <?php
$gForm->selectNumber('allegato', $form['allegato'], true);
    ?>
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
  </div>
</div>
</div>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
