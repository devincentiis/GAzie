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
if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
    $form = array_merge(gaz_dbi_parse_post('clfoco'), gaz_dbi_parse_post('anagra'));
    $form['ritorno'] = $_POST['ritorno'];
    $form['hidden_req'] = $_POST['hidden_req'];
    $form['pec_email'] = trim($form['pec_email']);
    $form['e_mail'] = trim($form['e_mail']);
    $form['last_modified'] = date("Y-m-d H:i:s");
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
        $rs_a = gaz_dbi_get_anagra($gTables['anagra'], 'id', $form['id_anagra']);
        $form = array_merge($form, $rs_a);
    }

    if (isset($_POST['Submit'])) { // conferma tutto
        // inizio controllo campi
        $real_code = $admin_aziend['mascli'] * 1000000 + $form['codice'];
        $rs_same_code = gaz_dbi_dyn_query('*', $gTables['clfoco'], " codice = " . $real_code, "codice", 0, 1);
        $same_code = gaz_dbi_fetch_array($rs_same_code);
        if ($same_code && ($toDo == 'insert')) { // c'� gi� uno stesso codice ed e' un inserimento
            $form['codice'] ++; // lo aumento di 1
            $msg .= "18+";
        }
        require("../../library/include/check.inc.php");
        if (strlen($form["ragso1"]) < 4) {
            if (!empty($form["legrap_pf_nome"]) && !empty($form["legrap_pf_cognome"]) && $form["sexper"] != 'G') {// setto la ragione sociale con l'eventuale legale rappresentante
                $form["ragso1"] = strtoupper($form["legrap_pf_cognome"] . ' ' . $form["legrap_pf_nome"]);
            } else { // altrimenti do errore                
                $msg .= '0+';
            }
        }
        if (empty($form["indspe"])) {
            $msg .= '1+';
        }
        // faccio i controlli sul codice postale
        $rs_pc = gaz_dbi_get_row($gTables['country'], 'iso', $form["country"]);
        $cap = new postal_code;
        if ($cap->check_postal_code($form["capspe"], $form["country"], $rs_pc['postal_code_length'])) {
            $msg .= '2+';
        }
        if (empty($form["citspe"])) {
            $msg .= '3+';
        }
        if (empty($form["prospe"])) {
            $msg .= '4+';
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
        $anagrafica = new Anagrafica();
        if (!($form['pariva'] == "") && !($form['pariva'] == '00000000000')) {
            $partner_with_same_pi = $anagrafica->queryPartnersAes(array('fe_cod_univoco'), array("codice" => " <> " . $real_code, "codice" => " BETWEEN " . $admin_aziend['mascli'] . "000000 AND " . $admin_aziend['mascli'] . "999999", "pariva" => " = '" . $form['pariva'] . "'"), array("pariva" => "DESC"), 0, 1);
            if ($partner_with_same_pi) {
                if ($partner_with_same_pi[0]['fe_cod_univoco'] == $form['fe_cod_univoco']) { // c'� gi� un cliente sul piano dei conti ed � anche lo stesso ufficio ( amministrativo della PA )
                    $msg .= "10+";
                }
            } elseif ($form['id_anagra'] == 0) { // � un nuovo cliente senza anagrafica
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
        if (!($form['codfis'] == "") && !($form['codfis'] == '00000000000')) {
            $partner_with_same_cf = $anagrafica->queryPartnersAes(array('fe_cod_univoco'), array("codice" => " <> " . $real_code, "codice" => " BETWEEN " . $admin_aziend['mascli'] . "000000 AND " . $admin_aziend['mascli'] . "999999", "codfis" => " = '" . $form['codfis'] . "'"), array("codfis" => "DESC"), 0, 1);
            if ($partner_with_same_cf) { // c'� gi� un cliente sul piano dei conti
                if ($partner_with_same_cf[0]['fe_cod_univoco'] == $form['fe_cod_univoco']) { // c'� gi� un cliente sul piano dei conti ed � anche lo stesso ufficio ( amministrativo della PA )
                    $msg .= "12+";
                }
            } elseif ($form['id_anagra'] == 0) { // � un nuovo cliente senza anagrafica
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

        if (empty($msg)) { // nessun errore
            $form['codice'] = $real_code;
            $form['datnas'] = date("Ymd", $uts_datnas);
            if ($toDo == 'insert') {
                if (!empty($form['fe_cod_univoco']) && $form['fatt_email'] <= 1) { // qui forzo all'utilizzo della PEC i nuovi clienti dalla PA 
                    $form['fatt_email'] = 2;
                }
                if ($form['id_anagra'] > 0) {
                    gaz_dbi_table_insert('clfoco', $form);
                } else {
                    $anagrafica->insertPartner($form);
                }
            } elseif ($toDo == 'update') {
                $anagrafica->updatePartners($form['codice'], $form);
            }
            header("Location: report_client.php?codice=".intval(substr($real_code,-6))."&privacy=" . $form['codice']);
            exit;
        }
    } elseif (isset($_POST['Return'])) { // torno indietro
        header("Location: " . $form['ritorno']);
        exit;
    }
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
    $anagrafica = new Anagrafica();
    $form = $anagrafica->getPartner(intval($admin_aziend['mascli'] * 1000000 + $_GET['codice']));
    $form['codice'] = intval(substr($form['codice'], 3));
    $toDo = 'update';
    $form['search']['id_des'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
    $form['datnas_Y'] = substr($form['datnas'], 0, 4);
    $form['datnas_M'] = substr($form['datnas'], 5, 2);
    $form['datnas_D'] = substr($form['datnas'], 8, 2);
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $anagrafica = new Anagrafica();
    $last = $anagrafica->queryPartnersAes(array('codice'), array("codice" => " BETWEEN " . $admin_aziend['mascli'] . "000000 AND " . $admin_aziend['mascli'] . "999999"), array("codice" => "DESC"), 0, 1);
    $form = array_merge(gaz_dbi_fields('clfoco'), gaz_dbi_fields_anagra());
    $form['codice'] = substr($last[0]['codice'], 3) + 1;
    $toDo = 'insert';
    $form['search']['id_des'] = '';
    $form['country'] = $admin_aziend['country'];
    $form['id_language'] = $admin_aziend['id_language'];
    $form['id_currency'] = $admin_aziend['id_currency'];
    $form['datnas_Y'] = 1900;
    $form['datnas_M'] = 1;
    $form['datnas_D'] = 1;
    $form['counas'] = $admin_aziend['country'];
    $form['codpag'] = 1;
    $form['spefat'] = 'N';
    $form['stapre'] = 'N';
    $form['allegato'] = 1;
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
</script>
";
echo "<form method=\"POST\" name=\"form\">\n";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $form['ritorno'] . "\">\n";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['id_anagra'] . "\" name=\"id_anagra\" />\n";
echo "<input type=\"hidden\" name=\"" . ucfirst($toDo) . "\" value=\"\">";
$gForm = new venditForm();
if ($toDo == 'insert') {
    echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['ins_this'] . "</div>\n";
} else {
    echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['upd_this'] . " '" . $form['codice'] . "'</div>\n";
    echo "<input type=\"hidden\" value=\"" . $form['codice'] . "\" name=\"codice\" />\n";
}
echo "<table class=\"table table-striped Tmiddle\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="3" class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
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
        echo "\t </td>\n";
        echo "</tr>\n";
    }
}
if ($toDo == 'insert') {
    echo "<tr>\n";
    echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['codice'] . "* </td>\n";
    echo "\t<td colspan=\"2\" class=\"FacetDataTD\"><input type=\"text\" name=\"codice\" value=\"" . $form['codice'] . "\" align=\"right\" maxlength=\"6\"  /></td>\n";
    echo "</tr>\n";
}
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['ragso1'] . "* </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"ragso1\" placeholder=\"" . $script_transl['ragso1_placeholder'] . "\" value=\"" . $form['ragso1'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['ragso2'] . " </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"ragso2\" value=\"" . $form['ragso2'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['legrap_pf_nome'] . " </td>\n";
echo "\t<td class=\"FacetDataTD\">
      <input type=\"text\" name=\"legrap_pf_nome\" title=\"" . $script_transl['legrap_pf_title'] . "\" value=\"" . $form['legrap_pf_nome'] . "\" align=\"right\" maxlength=\"60\"  /></td>\n";
echo "\t<td class=\"FacetDataTD\">
      <input type=\"text\" name=\"legrap_pf_cognome\" title=\"" . $script_transl['legrap_pf_title'] . "\" value=\"" . $form['legrap_pf_cognome'] . "\" align=\"right\" maxlength=\"60\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['sexper'] . "*</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->variousSelect('sexper', $script_transl['sexper_value'], $form['sexper']);
echo "\t </td>\n";
echo "</tr>\n";
/** ENRICO FEDELE */
/* Cambiato l'ordine dei campi per renderlo più coerente con l'autocompletamento (prima il campo comune che ha la funzione attiva) */
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['citspe'] . " *  </td>\n";
echo "\t<td class=\"FacetDataTD\">
      <input type=\"text\" name=\"citspe\" id=\"search_location\" value=\"" . $form['citspe'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
echo "\t<td class=\"FacetDataTD\">
      <input type=\"text\" name=\"prospe\" id=\"search_location-prospe\" value=\"" . $form['prospe'] . "\" align=\"right\" maxlength=\"2\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['indspe'] . " * </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"indspe\" value=\"" . $form['indspe'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['capspe'] . " * </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"capspe\" id=\"search_location-capspe\" value=\"" . $form['capspe'] . "\" align=\"right\" maxlength=\"10\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['country'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->selectFromDB('country', 'country', 'iso', $form['country'], 'iso', 0, ' - ', 'name');
echo "</td>\n";
echo "</tr>\n";
/** ENRICO FEDELE */
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['id_language'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->selectFromDB('languages', 'id_language', 'lang_id', $form['id_language'], 'lang_id', 1, ' - ', 'title_native');
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['id_currency'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->selectFromDB('currencies', 'id_currency', 'id', $form['id_currency'], 'id', 1, ' - ', 'curr_name');
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['sedleg'] . " </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <textarea name=\"sedleg\" rows=\"2\" cols=\"30\" maxlength=\"100\" >" . $form['sedleg'] . "</textarea></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['datnas'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('datnas', $form['datnas_D'], $form['datnas_M'], $form['datnas_Y']);
echo "\t</td>\n";
echo "</tr>\n";
echo "<tr>\n";

/** ENRICO FEDELE */
/* Aggiunto id per autocompletamento */
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['luonas'] . " </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" id=\"search_luonas\" name=\"luonas\" value=\"" . $form['luonas'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['pronas'] . " </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" id=\"search_pronas\" name=\"pronas\" value=\"" . $form['pronas'] . "\" align=\"right\" maxlength=\"2\"  /></td>\n";
echo "</tr>\n";
/** ENRICO FEDELE */
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['counas'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->selectFromDB('country', 'counas', 'iso', $form['counas'], 'iso', 1, ' - ', 'name');
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['telefo'] . " </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"telefo\" value=\"" . $form['telefo'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['fax'] . " </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"fax\" value=\"" . $form['fax'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['cell'] . " </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"cell\" value=\"" . $form['cell'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['codfis'] . " *</td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\">
      <input type=\"text\" name=\"codfis\" value=\"" . $form['codfis'] . "\" align=\"right\" maxlength=\"16\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['pariva'] . " </td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\">
      <input type=\"text\" name=\"pariva\" value=\"" . $form['pariva'] . "\" align=\"right\" maxlength=\"11\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['pec_email'] . "</td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\">
      <input type=\"text\" id=\"email\" name=\"pec_email\" value=\"" . $form['pec_email'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['fatt_email'] . "</td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">";
$gForm->variousSelect('fatt_email', $script_transl['fatt_email_value'], $form['fatt_email']);
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['e_mail'] . "</td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\">
      <input type=\"text\" id=\"email\" name=\"e_mail\" value=\"" . $form['e_mail'] . "\" align=\"right\" maxlength=\"50\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\"><a href=\"http://www.indicepa.gov.it/ricerca/n-lista-uffici-figli.php\" target=\"blank\">" . $script_transl['fe_cod_univoco'] . "</a></td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"fe_cod_univoco\" value=\"" . $form['fe_cod_univoco'] . "\" align=\"right\" maxlength=\"7\"  /></td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['codpag'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->selectFromDB('pagame', 'codpag', 'codice', $form['codpag'], 'tippag`, `giodec`, `numrat', true, ' ', 'descri');
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['sconto'] . "</td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"sconto\" value=\"" . $form['sconto'] . "\" align=\"right\" maxlength=\"5\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['banapp'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$select_banapp = new selectbanapp("banapp");
$select_banapp->addSelected($form["banapp"]);
$select_banapp->output();
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['portos'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->selectFromDB('portos', 'portos', 'codice', $form['portos'], 'codice', false, ' ', 'descri');
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['spediz'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->selectFromDB('spediz', 'spediz', 'codice', $form['spediz'], 'codice', false, ' ', 'descri');
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['imball'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
if (!isset($form['imball']))
    $form['imball'] = null;
$gForm->selectFromDB('imball', 'imball', 'codice', $form['imball'], 'codice', true, ' ', 'descri');
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['listin'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->selectNumber('listin', $form['listin'], 0, 1, 4);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['id_agente'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$select_agente = new selectAgente("id_agente", "C");
$select_agente->addSelected($form["id_agente"]);
$select_agente->output();
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['cosric'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->selectAccount('cosric', $form['cosric'], 4);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['operation_type'] . "</td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">";
$gForm->selectFromXML('../../library/include/operation_type.xml', 'operation_type', 'operation_type', $form['operation_type'], true, '', 'col-sm-6');
echo "</td></tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['destin'] . " </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <textarea name=\"destin\" rows=\"2\" cols=\"50\" class=\"FacetInput\">" . $form["destin"] . "</TEXTAREA></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['id_des'] . " </td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$select_id_des = new selectPartner("id_des");
$select_id_des->selectAnagra('id_des', $form['id_des'], $form['search']['id_des'], 'id_des', $script_transl['mesg']);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['iban'] . " </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"iban\" value=\"" . $form['iban'] . "\" align=\"right\" maxlength=\"27\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['maxrat'] . "</td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"maxrat\" value=\"" . $form['maxrat'] . "\" align=\"right\" maxlength=\"16\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['ragdoc'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->variousSelect('ragdoc', $script_transl['yn_value'], $form['ragdoc']);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['speban'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->variousSelect('speban', $script_transl['yn_value'], $form['speban']);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['addbol'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->variousSelect('addbol', $script_transl['yn_value'], $form['addbol']);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['spefat'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->variousSelect('spefat', $script_transl['yn_value'], $form['spefat']);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['stapre'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->variousSelect('stapre', $script_transl['yn_value'], $form['stapre']);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['aliiva'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->selectFromDB('aliiva', 'aliiva', 'codice', $form['aliiva'], 'codice', 1, ' - ', 'descri');
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['ritenuta'] . "</td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"ritenuta\" value=\"" . $form['ritenuta'] . "\" align=\"right\" maxlength=\"4\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['allegato'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->selectNumber('allegato', $form['allegato'], true);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['status'] . "</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->variousSelect('status', $script_transl['status_value'], $form['status'], 'FacetSelect', false);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['annota'] . "</td>\n";
//echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
//      <input type=\"text\" name=\"annota\" value=\"" . $form['annota'] . "\" align=\"right\" maxlength=\"100\"  /></td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <textarea name=\"annota\" rows=\"2\" cols=\"50\" class=\"FacetInput\">" . $form["annota"] . "</TEXTAREA></td>\n";

echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['sqn'] . "</td>";
echo "\t </td>\n";
echo "\t<td  class=\"FacetDataTD\">\n";
echo '<input name="Return" type="submit" value="' . $script_transl['return'] . '">';
echo "\t </td>\n";
echo "\t<td  class=\"FacetDataTD\" align=\"right\">\n";
echo '<input name="Submit" type="submit" value="' . ucfirst($script_transl[$toDo]) . '">';
echo "\t </td>\n";
echo "</tr>\n";
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>