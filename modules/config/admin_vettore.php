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
$msg = array('err' => array(), 'war' => array());
$sameidfis = false;
if ( !isset($_POST['hidden_req']) && isset($_GET['codice']) && intval($_GET['codice']) >= 1 ) { //al primo accesso allo script per update
    $vettor = gaz_dbi_get_row($gTables['vettor'], 'codice', intval($_GET['codice']));
    $anagra = gaz_dbi_get_row($gTables['anagra'], 'id', $vettor['id_anagra']);
    $form=array_merge($vettor,$anagra);
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
} elseif ( !isset($_POST['hidden_req']) && !isset($_GET['codice'])) { //al primo accesso allo script per insert
    $lsr = gaz_dbi_dyn_query("codice", $gTables['vettor'],1, 'codice DESC',0,1);
	$last = gaz_dbi_fetch_array($lsr);
    $form = array_merge(gaz_dbi_fields('vettor'), gaz_dbi_fields('anagra'));
    $form['codice'] = $last['codice'] + 1;
    $form['sexper'] = 'G';
    $form['country'] = $admin_aziend['country'];
    $form['id_language'] = $admin_aziend['id_language'];
    $form['id_currency'] = $admin_aziend['id_currency'];
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
} elseif (isset($_POST['codice'])) { // accessi successivi
    $form = array_merge(gaz_dbi_parse_post('vettor'), gaz_dbi_parse_post('anagra'));
    $form['ritorno'] = $_POST['ritorno'];
    $form['hidden_req'] = $_POST['hidden_req'];
    $form['pec_email'] = trim($form['pec_email']);
    $form['e_mail'] = trim($form['e_mail']);
    if (isset($_POST['ins'])) { // conferma tutto
        require("../../library/include/check.inc.php");
        $cf_pi = new check_VATno_TAXcode();
        $r_pi = $cf_pi->check_VAT_reg_no($form['pariva'], $form['country']);
        if (!empty($r_pi)) {
			// se la partita iva è sbagliata
            $msg['err'][] = "pariva";
        }
        if (strlen($form["ragso1"])<4) {
            $msg['err'][] = 'ragso1';
        }
        if (strlen($form["indspe"])<4) {
            $msg['err'][] = 'indiri';
        }
        if (strlen($form["citspe"])<3) {
            $msg['err'][] = 'citspe';
        }
        if (strlen($form["capspe"])<3) {
            $msg['err'][] = 'capspe';
        }
        if (empty($form["sexper"])) {
            $msg['err'][] = 'sexper';
        }
        if (empty($form["country"])) {
            $msg['err'][] = 'country';
        }
        if (!filter_var($form['pec_email'], FILTER_VALIDATE_EMAIL) && !empty($form['pec_email'])) {
            $msg['err'][] = 'pec_email';
        }
        if (!filter_var($form['e_mail'], FILTER_VALIDATE_EMAIL) && !empty($form['e_mail'])) {
            $msg['err'][] = 'e_mail';
        }
        if (strlen(trim($form["pariva"]))<4) {
            $msg['err'][] = 'pariva';
            $sameidfis = false;
        } else {
            if (strlen(trim($form["codfis"]))<4) { $form["codfis"]=trim($form["pariva"]);}
            // controllo se c'è già una anagrafica con la stessa partita iva /codice fiscale
            $sameidfis = gaz_dbi_get_row($gTables['anagra'], '(pariva', $form["pariva"]," OR codfis LIKE '".$form["codfis"]."' ) AND id != ".intval($form['id_anagra']));
            if ($sameidfis && $form['id_anagra']>=1 && $form['id_anagra'] != $sameidfis['id'] ) { // in update non posso cambiare partita iva con una utilizzata da altra anagrafica
                $msg['err'][] = 'pariva_used';
            }
        }
        if ($sameidfis && $form['id_anagra']==0) {
            $msg['war'][] = 'sameidfis';
        }
        if ( count($msg['err']) == 0 || ( count($msg['war']) == 1 && $form['hidden_req'] == 'useanagra' )) { // nessun errore oppure ho scelto di usare una anagrafica esistente
            $form['ragione_sociale'] = $form["ragso1"].' '.$form["ragso2"];
            $form['indirizzo'] = $form["indspe"];
            $form['cap'] = $form["capspe"];
            $form['citta'] = $form["citspe"];
            $form['provincia'] = $form["prospe"];
            $form['partita_iva'] = $form["pariva"];
            $form['codice_fiscale'] = $form["codfis"];
            if ($form['id_anagra']==0) { // ho un inserimento
                if ($form['hidden_req'] == 'useanagra') { // ho già l'anagrafica la devo solo linkare
                    $form['id_anagra'] = $sameidfis['id'];
                    $form['ragione_sociale'] = $sameidfis["ragso1"].' '.$sameidfis["ragso2"];
                    $form['indirizzo'] = $sameidfis["indspe"];
                    $form['cap'] = $sameidfis["capspe"];
                    $form['citta'] = $sameidfis["citspe"];
                    $form['provincia'] = $sameidfis["prospe"];
                    $form['partita_iva'] = $sameidfis["pariva"];
                    $form['codice_fiscale'] = $sameidfis["codfis"];
                } else {
                    $form['id_anagra'] = gaz_dbi_table_insert('anagra', $form);
                }
                gaz_dbi_table_insert('vettor', $form);
                header("Location: report_vettor.php");
                exit;
            } else { // modifica
                gaz_dbi_table_update('anagra', array('id',$form['id_anagra']), $form);
                gaz_dbi_table_update('vettor', $form['codice'], $form);
                header("Location: report_vettor.php");
                exit;
            }
        }
    } elseif (isset($_POST['Return'])) { // torno indietro
        header("Location: " . $form['ritorno']);
        exit;
    }
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('custom/autocomplete'));
$gForm = new configForm();
$upd=($form['id_anagra']>0)?'_upd':'';
?>
<script>
$(function () {
    $("#link_anagra").click(function() {
       $("input[name=hidden_req]").val('useanagra');
       $("input[name=ins]").trigger('click');
    });
});
</script>
<form role="form" method="post" name="pay_riba" enctype="multipart/form-data" >
    <input type="hidden" value="<?php echo $form['codice'] ?>" name="codice" />
    <input type="hidden" value="<?php echo $form['id_anagra'] ?>" name="id_anagra" />
    <input type="hidden" value="<?php echo $form['hidden_req'] ?>" name="hidden_req" />
    <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno">
<div class="text-center">
   <p><b><?php echo $script_transl['title'.$upd]; ?></b></p>
</div>
<?php
if (count($msg['err']) > 0) { // ho un errore
    $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
if (count($msg['war']) > 0) { // ho un alert
    $gForm->gazHeadMessage($msg['war'], $script_transl['err'], 'war');
}

if ($sameidfis && $form['id_anagra'] == 0 ) {
?>
<div class="panel panel-info gaz-table-form">
    <div class="col-xs-12 btn btn-danger" id="link_anagra"><?php echo $script_transl['link_anagra'].' ( ID: '.$sameidfis['id']." )" ; ?></div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ragso1" class="col-sm-4 control-label"><?php echo $script_transl['ragso1']; ?></label>
                    <div class="col-sm-8"><?php echo $sameidfis['ragso1']; ?></div>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ragso1" class="col-sm-4 control-label"><?php echo $script_transl['indspe']; ?></label>
                    <div class="col-sm-8"><?php echo $sameidfis['indspe']; ?></div>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ragso1" class="col-sm-4 control-label"><?php echo $script_transl['citspe']; ?></label>
                    <div class="col-sm-8"><?php echo $sameidfis['citspe']; ?></div>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ragso1" class="col-sm-4 control-label"><?php echo $script_transl['pariva']; ?></label>
                    <div class="col-sm-8"><?php echo $sameidfis['pariva']; ?></div>
                </div>
            </div>
        </div><!-- chiude row  -->
    </div>
</div>
<?php
}
?>
<div class="panel panel-default gaz-table-form">
  <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ragso1" class="col-sm-4 control-label"><?php echo $script_transl['ragso1']; ?> *</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['ragso1']; ?>" name="ragso1" minlenght="4" maxlength="50"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ragso2" class="col-sm-4 control-label"><?php echo $script_transl['ragso2']; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['ragso2']; ?>" name="ragso2" maxlength="50"/>
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
                    <label for="indspe" class="col-sm-4 control-label"><?php echo $script_transl['indspe']; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['indspe']; ?>" name="indspe" maxlength="50"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="capspe" class="col-sm-4 control-label"><?php echo $script_transl['capspe']; ?></label>
                    <input class="col-sm-4" type="text" id="search_location-capspe" value="<?php echo $form['capspe']; ?>" name="capspe" maxlength="10"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="citspe" class="col-sm-4 control-label"><?php echo $script_transl['citspe']; ?></label>
                    <input class="col-sm-4" type="text" id="search_location" value="<?php echo $form['citspe']; ?>" name="citspe" maxlength="60"/>
                    <div class="text-right"><input class="col-sm-1" type="text" id="search_location-prospe" value="<?php echo $form['prospe']; ?>" name="prospe" maxlength="2"/></div>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="country" class="col-sm-4 control-label"><?php echo $script_transl['country']; ?></label>
    <?php
$gForm->selectFromDB('country', 'country', 'iso', $form['country'], 'iso', 0, ' - ', 'name');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="codfis" class="col-sm-4 control-label"><a href="https://telematici.agenziaentrate.gov.it/VerificaCF/Scegli.do?parameter=verificaCf" target="blank"><?php echo $script_transl['codfis']; ?></a></label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['codfis']; ?>" name="codfis" maxlength="16"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="pariva" class="col-sm-4 control-label"><a href="https://telematici.agenziaentrate.gov.it/VerificaPIVA/Scegli.do?parameter=verificaPiva" target="blank"><?php echo $script_transl['pariva']; ?></a></label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['pariva']; ?>" name="pariva" maxlength="20"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="n_albo" class="col-sm-4 control-label"><?php echo $script_transl['n_albo']; ?> </label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['n_albo']; ?>" name="n_albo" maxlength="100"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="id_language" class="col-sm-4 control-label"><?php echo $script_transl['id_language']; ?></label>
    <?php
$gForm->selectFromDB('languages', 'id_language', 'lang_id', $form['id_language'], 'lang_id', 1, ' - ', 'title_native');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="id_currency" class="col-sm-4 control-label"><?php echo $script_transl['id_currency']; ?></label>
    <?php
$gForm->selectFromDB('currencies', 'id_currency', 'id', $form['id_currency'], 'id', 1, ' - ', 'curr_name');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="conducente" class="col-sm-4 control-label"><?php echo $script_transl['conducente']; ?> </label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['conducente']; ?>" name="conducente" maxlength="100"/>
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
                    <label for="targa" class="col-sm-4 control-label"><?php echo $script_transl['targa']; ?> </label>
                    <input class="col-sm-4" type="text" value="<?php echo $form['targa']; ?>" name="targa" maxlength="20"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="pec_email" class="col-sm-4 control-label"><?php echo $script_transl['pec_email']; ?></label>
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
                    <label for="annota" class="col-sm-4 control-label"><?php echo $script_transl['annota']; ?> </label>
                    <textarea name="annota" rows="2" cols="50" maxlength="3000"><?php echo $form['annota']; ?></textarea>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12 FacetFooterTD text-center">
            <input class="btn btn-warning" id="preventDuplicate" onClick="chkSubmit();" type="submit" name="ins" value="<?php echo $script_transl['confirm_entry'.$upd]; ?>" />
            </div>
        </div><!-- chiude row  -->
  </div>
</div>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
