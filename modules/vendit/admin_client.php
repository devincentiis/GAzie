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
    $form['ritorno'] = $_POST['ritorno'];
    $form['hidden_req'] = $_POST['hidden_req'];
    $form['tab'] = substr($_POST['tab'],0,20);
    if (!empty($_FILES['docfile']['name'])) { // ho aggiunto un documento
      if (!($_FILES['docfile']['type'] == "image/png" ||
              $_FILES['docfile']['type'] == "image/x-png" ||
              $_FILES['docfile']['type'] == "image/jpeg" ||
              $_FILES['docfile']['type'] == "image/jpg" ||
              $_FILES['docfile']['type'] == "application/pdf" ||
              $_FILES['docfile']['type'] == "image/gif" ||
              $_FILES['docfile']['type'] == "image/x-gif")) $msg .= '22+';
      if ($_FILES['docfile']['size'] > 5000000) $msg .= '23+'; // su MariaDB impostare la direttiva max_allowed_packed ad almeno 8M
      if (empty($msg)) {
        $fileinfo = pathinfo($_FILES['docfile']['name']);
        gaz_dbi_query("INSERT INTO ".$gTables['files']." (table_name_ref, id_ref, content, extension, title, adminid) VALUES ('clfoco_doc', '" .intval($admin_aziend['mascli'] * 1000000 + $_POST['codice']). "', TO_BASE64(AES_ENCRYPT('".bin2hex(file_get_contents($_FILES['docfile']['tmp_name']))."','".$_SESSION['aes_key']."')), '".$fileinfo['extension']."','".$fileinfo['filename']."', '".$_SESSION['user_name']."' )");
      }
      $form['tab'] = 'licenses';
    }
    $form['pec_email'] = trim($form['pec_email']);
    $form['e_mail'] = trim($form['e_mail']);
    $form['last_modified'] = date("Y-m-d H:i:s");
    $form['datnas_Y'] = intval($_POST['datnas_Y']);
    $form['datnas_M'] = intval($_POST['datnas_M']);
    $form['datnas_D'] = intval($_POST['datnas_D']);
    $form['fe_cod_univoco'] = strtoupper($form['fe_cod_univoco']);// porto il codice univoco tutto con caratteri maiuscoli
    foreach ($_POST['search'] as $k => $v) {
        $form['search'][$k] = $v;
    }
    // inizio mandati rid
    $nd = 0;
    if (isset($_POST['MndtRltdInf'])) {
      foreach ($_POST['MndtRltdInf'] as $nd => $v) {
        $form['MndtRltdInf'][$nd]['id_doc'] = intval($v['id_doc']);
        $form['MndtRltdInf'][$nd]['extension'] = substr($v['extension'], 0, 5);
        $form['MndtRltdInf'][$nd]['title'] = substr($v['title'], 0, 80);
        $nd++;
      }
    }
    // fine mandati rid

    $toDo = 'update';
    if (isset($_POST['Insert'])) {
        $toDo = 'insert';
    }

    if ($form['hidden_req'] == 'toggle') { // e' stato accettato il link ad una anagrafica esistente
        $rs_a = gaz_dbi_get_row($gTables['anagra'], 'id', $form['id_anagra']);
        $form = array_merge($form, $rs_a);
    }

    if (isset($_POST['Conferma'])) { // conferma tutto
        // inizio controllo campi
        $real_code = $admin_aziend['mascli'] * 1000000 + $form['codice'];
        $rs_same_code = gaz_dbi_dyn_query('*', $gTables['clfoco'], " codice = " . $real_code, "codice", 0, 1);
        $same_code = gaz_dbi_fetch_array($rs_same_code);
        if ($same_code && ($toDo == 'insert')) { // c'� gi� uno stesso codice ed e' un inserimento
            $form['codice'] ++; // lo aumento di 1
            $msg .= "18+";
        }
        require("../../library/include/check.inc.php");
        if (strlen($form["ragso1"]) < 3) {
            if (!empty($form["legrap_pf_nome"]) && !empty($form["legrap_pf_cognome"]) && $form["sexper"] != 'G') {// setto la ragione sociale con l'eventuale legale rappresentante
                $form["ragso1"] = strtoupper($form["legrap_pf_cognome"] . ' ' . $form["legrap_pf_nome"]);
            } else { // altrimenti do errore
                $msg .= '0+';
            }
        }
        if (empty($form["indspe"])) {
            $msg .= '1+';
        }
        // se il cliente è straniero formatto i campi pariva e codis per poter generare una fattura elettronica corretta
        if ($form['country']!='IT') {
            if (strlen($form['pariva']) < 5 && strlen($form['codfis']) < 5) { // non ho scelto nulla, uso il codice cliente del piano dei conti per entrambi
                $form['pariva']=$real_code;
                $form['codfis']=$real_code;
            } elseif (strlen($form['pariva']) < 5) { // ho scelto solo il codice fiscale, imposto la partita iva allo stesso valore
                $form['pariva']=$form['codfis'];
            } else if (strlen($form['codfis']) < 5) { // ho scelto solo la partita iva, imposto il codice fiscale allo stesso valore
                $form['codfis']=$form['pariva'];
            }
        }
        // faccio i controlli sul codice postale
        $rs_pc = gaz_dbi_get_row($gTables['country'], 'iso', $form["country"]);
        $cap = new postal_code;
        if ( gaz_dbi_get_row($gTables['company_config'], 'var', 'check_cust_address')['val']==1 ) {
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
        if (strlen(trim($form['codfis'])) == 11) {
            $r_cf = $cf_pi->check_VAT_reg_no($form['codfis'], $form['country']);
            if ($form['sexper'] != 'G') {
                $r_cf = 'Codice fiscale sbagliato per una persona fisica';
                $msg .= '8+';
            }
        } else {
            $r_cf = $cf_pi->check_TAXcode($form['codfis'], $form['country']);
        }
        if (!empty($r_pi) || ( $form['sexper']=='G' && intval(substr($form['codfis'],0,1)) < 8 && $form['country']=='IT' && strlen(trim($form['pariva'])) < 11 )) {
			// se la partita iva è sbagliata o un cliente persona giuridica senza partita iva e non ha un codice fiscale di una associazione
            $msg .= "9+";
        }
        if ($form['codpag'] < 1) {
            $msg .= "17+";
        }
        $anagrafica = new Anagrafica();
        if ( gaz_dbi_get_row($gTables['company_config'], 'var', 'consenti_nofisc')['val']==0 ) {
            if (!empty($form['pariva']) && !($form['pariva'] == '00000000000')) {
                $partner_with_same_pi = $anagrafica->queryPartners('*', "codice <> " . $real_code . " AND codice BETWEEN " . $admin_aziend['mascli'] . "000000 AND " . $admin_aziend['mascli'] . "999999 AND pariva = '" . addslashes($form['pariva']) . "'", "pariva DESC", 0, 1);
                if ($partner_with_same_pi) {
                    if ($partner_with_same_pi[0]['fe_cod_univoco'] == $form['fe_cod_univoco']) { // c'� gi� un cliente sul piano dei conti ed � anche lo stesso ufficio ( amministrativo della PA )
                        $msg .= "10+";
                    }
                } elseif ($form['id_anagra'] == 0) { // � un nuovo cliente senza anagrafica
                    $rs_anagra_with_same_pi = gaz_dbi_query_anagra(array("*"), $gTables['anagra'], array("pariva" => "='" . addslashes($form['pariva']) . "'"), array("pariva" => "DESC"), 0, 1);
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
            if (!empty($form['codfis']) && !($form['codfis'] == '00000000000')) {
                $partner_with_same_cf = $anagrafica->queryPartners('*', "codice <> " . $real_code . " AND codice BETWEEN " . $admin_aziend['mascli'] . "000000 AND " . $admin_aziend['mascli'] . "999999 AND codfis = '" . $form['codfis'] . "'", "codfis DESC", 0, 1);
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
        }
        if (!filter_var($form['pec_email'], FILTER_VALIDATE_EMAIL) && !empty($form['pec_email'])) {
            $msg .= "20+";
        }

        if (!filter_var($form['e_mail'], FILTER_VALIDATE_EMAIL) && !empty($form['e_mail'])) {
            $msg .= "20+";
        }
		// il codice SIAN deve essere univoco nell'ambito clienti e fornitori
		if (intval($form['id_SIAN'])>0){
			$rs_same_code = gaz_dbi_dyn_query('*', $gTables['anagra'], " id_SIAN = " . $form['id_SIAN']);
			$rows=gaz_dbi_num_rows($rs_same_code);
			if ($rows>0 && ($toDo == 'insert')) { // c'� gi� uno stesso codice
				$form['id_SIAN'] ++; // lo aumento di 1
				$msg .= "21+";
			}
			if ($toDo == 'update') {
				foreach ($rs_same_code as $row){
					if ($row['ragso1']!==$form['ragso1'] AND $row['id_SIAN']==$form['id_SIAN']){
						$form['id_SIAN'] ++; // c'� gi� uno stesso codice lo aumento di 1
						$msg .= "21+";
					}
				}
			}
		}

        if (empty($msg)) { // nessun errore
            $form['codice'] = $real_code;
            $form['datnas'] = date("Ymd", $uts_datnas);
            if ($toDo == 'insert') {
                if (!empty($form['fe_cod_univoco']) && $form['fatt_email'] <= 1) { // qui forzo all'utilizzo della PEC i nuovi clienti dalla PA
                    $form['fatt_email'] = 2;
                }
                if ($form['id_anagra'] > 0) {
                    $form['descri']= $form['ragso1'].' '. $form['ragso2'];
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
    // riprendo gli eventuali documenti
    $rs_docs = gaz_dbi_dyn_query("*", $gTables['files'], "table_name_ref = 'clfoco_doc' AND id_ref='".$form['codice']."'", "status ASC");
    while ($r = gaz_dbi_fetch_array($rs_docs)) {
      $form['docs'][$r['id_doc']] = $r; // riprendo tutto ma i documenti con status > 9 es. quelli di identità sono criptati/decriptati
    }
    $form['codice'] = intval(substr($form['codice'], 3));
    $toDo = 'update';
    $form['search']['id_des'] = '';
    $form['search']['fiscal_rapresentative_id'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
    $form['tab'] = 'home';
    $form['datnas_Y'] = substr($form['datnas'], 0, 4);
    $form['datnas_M'] = substr($form['datnas'], 5, 2);
    $form['datnas_D'] = substr($form['datnas'], 8, 2);
    // inizio mandati rid
    $nd = 0;
    $rs_r = gaz_dbi_dyn_query("*", $gTables['files'], "id_ref = '" . intval($admin_aziend['mascli'] * 1000000 + $_GET['codice']) . "' AND table_name_ref = 'clfoco'", "id_doc DESC");
    while ($r = gaz_dbi_fetch_array($rs_r)) {
        $form['MndtRltdInf'][$nd] = $r;
        $nd++;
    }
    // fine mandati rid

} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
  $anagrafica = new Anagrafica();
  $last = $anagrafica->queryPartners('*', "codice BETWEEN " . $admin_aziend['mascli'] . "000000 AND " . $admin_aziend['mascli'] . "999999", "codice DESC", 0, 1);
  $form = array_merge(gaz_dbi_fields('clfoco'), gaz_dbi_fields('anagra'));
  $form['codice'] = substr($last[0]['codice'], 3) + 1;
  // cancello i documenti eventualmente rimasti in sospeso da questo utente, non da altri che potrebbero starci lavorando contemporaneamente
  gaz_dbi_query("DELETE FROM " . $gTables['files'] . " WHERE table_name_ref = 'clfoco_doc' AND id_ref = " .intval($admin_aziend['mascli'] * 1000000 + $form['codice']));
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
  $form['codpag'] = 1;
  $form['spefat'] = 'N';
  $form['stapre'] = 'N';
  $form['allegato'] = 1;
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['tab'] = 'home';
  $form['hidden_req'] = '';
  $form['visannota'] = 'N';
	$form['id_SIAN']="";
	$nd=0;
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup', 'custom/autocomplete'));
if (isset($admin_aziend['lang'])){
  $price_list_names = gaz_dbi_dyn_query('*', $gTables['company_data'], "ref = '" . $admin_aziend['lang'] . "_artico_pricelist' && var NOT LIKE 'preacq'", "id_ref ASC");
  if ($price_list_names->num_rows == 5){
    $script_transl['listino_value']=array();
    $n=0;
    while ($list_name = gaz_dbi_fetch_array($price_list_names)){
      $n++;
      $script_transl['listino_value'][$n]=$list_name["description"];
    }
  }
}
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
}";
?>
$(function() {
	$('.tabtoggle').click(function() {
    $("#tab").val($(this).attr("href").substring(1));
  });
  $("#search_id_des").autocomplete({
    html: true,
    source: "../../modules/root/search.php",
    minLength: 2,
    open: function(event, ui) {
      $(".ui-autocomplete").css("z-index", 1000);
    },
		select: function(event, ui) {
			$("#search_id_des").val(ui.item.value);
			$("#id_des").val(ui.item.id);
			$(this).closest("form").submit();
		}
  });

	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("mndtid"));
		$("p#iddescri").html($(this).attr("dtofsgntr"));
		var id_con = $(this).attr('ref');
		$( "#dialog_delete" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
   			close: {
					text:'Non eliminare',
					'class':'btn btn-default',
          click:function() {
            $(this).dialog("close");
          }
        },
				delete:{
					text:'Elimina',
					'class':'btn btn-danger',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'mndtritdinf',ref:id_con},
						type: 'POST',
						url: '../vendit/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_client.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});

  $('#iban,#codfis').keyup(function(){
      this.value = this.value.toUpperCase();
  });

	$("#dialog_clfoco_doc_del").dialog({ autoOpen: false });
	$('.dialog_clfoco_doc_del').click(function() {
		$("p#nfile").html($(this).attr("ref"));
		$("p#dfile").html($(this).attr("nf"));
		var id = $(this).attr('ref');
		$( "#dialog_clfoco_doc_del" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
   			close: {
					text:'Non eliminare',
					'class':'btn btn-default',
          click:function() {
            $(this).dialog("close");
          }
        },
				delete:{
					text:'Elimina',
					'class':'btn btn-danger',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'clfoco_doc',ref:id},
						type: 'POST',
						url: './delete.php',
						success: function(output){
		          //alert(output);
							form.submit();
						}
					});
				}}
			}
		});
		$("#dialog_clfoco_doc_del" ).dialog( "open" );
	});

});
function printDoc(urlPrintDoc,nf){
	$(function(){
		$("#filen").html('File: ' + nf);
		$('#frameDoc').attr('src',urlPrintDoc);
		$('#frameDoc').css({'height': '100%'});
		$('.frameDoc').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
		$('#closeDoc').on( "click", function() {
      $('.frameDoc').css({'display': 'none'});
    });
	});
};
</script>
<form method="POST" name="form" enctype="multipart/form-data">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione Mandato">
    <p><b>Mandato RID</b></p>
    <p>Numero:</p>
    <p class="ui-state-highlight" id="idcodice"></p>
    <p>Data firma:</p>
    <p class="ui-state-highlight" id="iddescri"></p>
	</div>
	<div style="display:none" id="dialog_clfoco_doc_del" title="Conferma eliminazione documento">
    <p><b>DOCUMENTO</b></p>
    <p>ID: </p>
    <p class="ui-state-highlight" id="nfile"></p>
    <p>Nome File: </p>
    <p class="ui-state-highlight" id="dfile"></p>
	</div>
	<div class="frameDoc panel panel-success" style="display: none; position: fixed; left: 5%; top: 10px">
		<div class="col-lg-12">
			<div class="col-xs-11"><h4 id="filen"></h4></div>
			<div class="col-xs-1"><h4><button type="button" id="closeDoc"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
		</div>
		<iframe id="frameDoc" style="height: auto; width: 100%;" src=""></iframe>
	</div>
<?php

echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $form['ritorno'] . "\">\n";
echo '<input type="hidden" value="'. $form['tab'] .'" name="tab" id="tab" />';
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['id_anagra'] . "\" name=\"id_anagra\" />\n";
echo "<input type=\"hidden\" name=\"" . ucfirst($toDo) . "\" value=\"\">";
$gForm = new venditForm();
if ($toDo == 'insert') {
    echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['ins_this'] . ' con ' . $script_transl['codice'] . " n° <input type=\"text\" name=\"codice\" value=\"" . $form['codice'] . "\" align=\"right\" maxlength=\"6\" /></div>\n";
} else {
    echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['upd_this'] . " '" . $form['codice'] . "'";
    echo "<input type=\"hidden\" value=\"" . $form['codice'] . "\" name=\"codice\" /></div>\n";
}
?>
<?php
if (!empty($msg)) {
    echo '<div align="center"><table>';
    if (isset($anagra)) {
        echo '<tr><td colspan="3" class="FacetDataTDred">' . ((strpos($msg,'15+'))?$script_transl['errors'][15]:''). ((strpos($msg,'16+'))?$script_transl['errors'][16]:''). "</td></tr>\n";
        echo "<tr>\n";
        echo "\t <td>\n";
        echo "\t </td>\n";
        echo "<td colspan=\"2\"><div onmousedown=\"toggleContent('id_anagra')\" class=\"FacetDataTDred\" style=\"cursor:pointer;\">";
        echo ' &dArr; ' . $script_transl['link_anagra'] . " &dArr;</div>\n";
        echo "<div id=\"id_anagra\" onclick=\"selectValue('" . $anagra['id'] . "');\" style=\"cursor: pointer;\">\n";
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
    } else {
      echo '<tr><td colspan="3" class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
    }
    echo '</table></div>';
}
?>

<div class="panel panel-default gaz-table-form div-bordered">
  <div class="container-fluid">
  <ul class="nav nav-pills">
    <li class="<?php echo $form['tab']=='home'?'active':''; ?>"><a data-toggle="pill" class="tabtoggle" href="#home">Anagrafica</a></li>
    <li class="<?php echo $form['tab']=='commer'?'active':''; ?>"><a data-toggle="pill" class="tabtoggle" href="#commer">Impostazioni</a></li>
    <li class="<?php echo $form['tab']=='licenses'?'active':''; ?>"><a data-toggle="pill" class="tabtoggle" href="#licenses">Documenti</a></li>
    <li style="float: right;"><input class="btn btn-warning" name="Conferma" type="submit" value="<?php echo ucfirst($script_transl[$toDo]); ?>"></li>
  </ul>

  <div class="tab-content">
    <div id="home" class="tab-pane fade <?php echo $form['tab']=='home'?'in active':''; ?>">
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
                    <input class="col-sm-8" type="text" value="<?php echo $form['indspe']; ?>" name="indspe" maxlength="60"/>
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
                    <label for="fatt_email" class="col-sm-4 control-label"><?php echo $script_transl['fatt_email']; ?> </label>
    <?php
$gForm->variousSelect('fatt_email', $script_transl['fatt_email_value'], $form['fatt_email']);
    ?>
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
                    <label for="e_mail2" class="col-sm-4 control-label"><?php echo $script_transl['e_mail2']; ?></label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['e_mail2']; ?>" name="e_mail2" id="email2" maxlength="60"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="fe_cod_univoco" class="col-sm-4 control-label"><a href="https://www.indicepa.gov.it/ipa-portale/consultazione/domicilio-digitale/ricerca-domicili-digitali-ente" target="blank"><?php echo $script_transl['fe_cod_univoco']; ?></a></label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['fe_cod_univoco']; ?>" name="fe_cod_univoco" id="fe_cod_univoco" maxlength="7" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="id_SIAN" class="col-sm-4 control-label">Codice identificativo SIAN</label>
                    <input class="col-sm-4" type="text" onkeyup="this.value=this.value.replace(/[^\d]/,'');" value="<?php echo $form['id_SIAN']; ?>" name="id_SIAN" id="id_SIAN" maxlength="10" />
                </div>
            </div>
        </div><!-- chiude row  -->
      </div><!-- chiude tab-pane  -->
      <div id="commer" class="tab-pane fade <?php echo $form['tab']=='commer'?'in active':''; ?>">
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
                    <label for="id_customer_group" class="col-sm-4 control-label"><?php echo $script_transl['customer_group']; ?> </label>
    <?php
$gForm->selectFromDB('customer_group', 'id_customer_group', 'id', $form['id_customer_group'], 'id', true, ' ', 'descri');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="MndtRltdInf" class="col-sm-4 control-label"><?php echo $script_transl['MndtRltdInf']; ?></label>
<?php if ($nd > 0) { // se ho dei documenti  ?>
                        <div>
                        <?php foreach ($form['MndtRltdInf'] as $k => $val) { ?>
                                <input type="hidden" value="<?php echo $val['id_doc']; ?>" name="MndtRltdInf[<?php echo $k; ?>][id_doc]">
                                <input type="hidden" value="<?php echo $val['extension']; ?>" name="MndtRltdInf[<?php echo $k; ?>][extension]">
                                <input type="hidden" value="<?php echo $val['title']; ?>" name="MndtRltdInf[<?php echo $k; ?>][title]">
    <?php echo DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/doc/' . $val['id_doc'] . '.' . $val['extension']; ?>
                                <a href="../root/retrieve.php?id_doc=<?php echo $val["id_doc"]; ?>" title="<?php echo $script_transl['view']; ?>!" class="btn btn-default btn-sm">
                                    <i class="glyphicon glyphicon-file"></i>
                                </a><?php echo $val['title']; ?>
                                <input type="button" value="<?php echo ucfirst($script_transl['update']); ?>" onclick="location.href = 'admin_mndtritdinf.php?id_doc=<?php echo $val['id_doc']; ?>&Update'" />
							<a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella il mandato" ref="<?php echo $val['id_doc'];?>"
                            <?php
                           	if ($data=json_decode($val['custom_field'],true)){// se c'è un json nel custom_field
                                if (is_array($data['vendit']) && strlen($data['vendit']['dtofsgntr'])>0) { // se è riferito al modulo vendit e contiene la data di firma del RID
                                    echo ' dtofsgntr="'.$data['vendit']['dtofsgntr'].'" mndtid="'.$data['vendit']['mndtid'].'"';
                                }
                            }
                            ?>
                            >
								<i class="glyphicon glyphicon-trash"></i>
							</a>

<?php } ?>
                            <input type="button" value="<?php echo ucfirst($script_transl['insert']); ?>" onclick="location.href = 'admin_mndtritdinf.php?id_ref=<?php echo $form['codice']; ?>&Insert'" />
                        </div>
                        <?php } else { // non ho documenti  ?>
                        <input type="button" value="<?php echo ucfirst($script_transl['insert']); ?>" onclick="location.href = 'admin_mndtritdinf.php?id_ref=<?php echo $form['codice']; ?>&Insert'">
                    <?php } ?>
                </div>
            </div>
        </div>
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
                    <label for="sconto_rigo" class="col-sm-4 control-label"><?php echo $script_transl['sconto_rigo']; ?></label>
                    <input class="col-sm-1" type="text" value="<?php echo $form['sconto_rigo']; ?>" name="sconto_rigo" id="sconto_rigo" maxlength="5" />
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
    $gForm->variousSelect('listin', $script_transl['listino_value'], $form['listin'], 'FacetSelect', false);
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
$gForm->selectAccount('cosric', $form['cosric'], 4);
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
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <label for="ref_ecommerce_id_customer" class="col-sm-4 control-label"><?php echo $script_transl['ref_ecommerce_id_customer']; ?> </label>
              <input class="col-sm-8" type="text" value="<?php echo $form['ref_ecommerce_id_customer']; ?>" name="ref_ecommerce_id_customer" id="ref_ecommerce_id_customer" maxlength="50" />
            </div>
          </div>
        </div><!-- chiude row  -->
  </div>
      <div id="licenses" class="tab-pane fade <?php echo $form['tab']=='licenses'?'in active':''; ?>">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="codpag" class="col-sm-4 control-label">Carte d'identità, autorizzazioni,<br/>licenze, patenti, ecc.<br/><small style="font-weight: 400;"> (criptati sul database)</small></label>
                    <div class="col-sm-8">
<?php
// riprendo sia i files già confermati
$rdocs = gaz_dbi_dyn_query("*", $gTables['files'],"id_ref = '" .intval($admin_aziend['mascli'] * 1000000 + $form['codice']). "' AND table_name_ref = 'clfoco_doc'", "id_doc");
while ($doc = gaz_dbi_fetch_array($rdocs)) {
  echo  '<div class="col-xs-12"><a class="btn btn-xs btn-default" style="cursor:pointer;" onclick="return printDoc(\'get_files_doc.php?id_doc='. $doc['id_doc'].'\',\''.$doc['title'].'.'.$doc['extension'].'\')" > '.$doc['title'].'.'.$doc['extension'].' &nbsp; <i class="glyphicon glyphicon-eye-open"></i> &nbsp; </a>	<a style="float:right;" class="btn btn-xs btn-elimina dialog_clfoco_doc_del" title="Elimina il documento" ref="'. $doc['id_doc'].'" nf="'.$doc['title'].'.'.$doc['extension'].'" ><i class="glyphicon glyphicon-trash"></i></a><br/>&nbsp;</div>';
}
echo '<div class="col-xs-12"><button class="btn btn-md btn-warning" type="image" data-toggle="collapse" href="#extdoc_dialog_othfot" style="font-size: 1.2em;"> <i class="glyphicon glyphicon-camera"></i> <i class="fa fa-file-pdf-o"></i>  &nbsp; Nuovo documento</button></div>';
echo '<div id="extdoc_dialog_othfot" class="collapse col-xs-12"><input style="margin-left:20%;"  type="file" accept=".png,.jpg,.gif,.pdf" onchange="this.form.submit();" name="docfile"></div>';
?>
                  </div>
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
