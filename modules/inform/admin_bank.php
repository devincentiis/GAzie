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
$msg = array('err' => array(), 'war' => array());

if ( !isset($_POST['hidden_req']) && isset($_GET['id']) && intval($_GET['id']) >= 1 ) { //al primo accesso allo script per update
	$form=gaz_dbi_get_row($gTables['bank'], 'id',intval($_GET['id']));
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
    $municipalities = gaz_dbi_get_row($gTables['municipalities'], 'id',  $form['id_municipalities']);
    $form['search_municipalities'] = $municipalities['name'];
    $form['descri_municipalities'] = $municipalities['name'];
} elseif (isset($_POST['id'])) { // accessi successivi
    $form = gaz_dbi_parse_post('bank');
	$form['id_municipalities']=intval($_POST['id_municipalities']);
    $form['ritorno'] = $_POST['ritorno'];
    $form['ritorno'] = $_POST['ritorno'];
    $form['hidden_req'] = $_POST['hidden_req'];
    // Se viene inviata la richiesta di cambio produzione
    if ($_POST['hidden_req'] == 'change_municipalities') {
        $form['id_municipalities'] = 0;
        $form['search_municipalities'] = '';
        $form['descri_municipalities'] = '';
        $form['hidden_req'] = '';
    }
    if (isset($_POST['ins'])) {
    if (intval($form['codabi']) < 1000) { // no ho selezionato l'abi
        $msg['err'][] = 'codabi';
    }
    if (intval($form['codcab']) < 1000) { // no ho selezionato il cab
        $msg['err'][] = 'codcab';
    }
    if (strlen($form['descriabi']) < 4) {
        $msg['err'][] = 'descriabi';
    }
    if (strlen($form['descricab']) < 4) { // no ho selezionato il cab
        $msg['err'][] = 'descricab';
    }
    if (strlen($form['indiri']) < 4) { // no ho selezionato il cab
        $msg['err'][] = 'indiri';
    }
    if (intval($form['id_municipalities']) < 1) { // no ho selezionato il cab
        $msg['err'][] = 'id_municipalities';
    }
    if ($form['id']==0) {
        $dupli = gaz_dbi_get_row($gTables['bank'], 'codabi', intval($form['codabi']), " AND codcab = ".intval($form['codcab']));
        if ($dupli) {
            $msg['err'][] = 'exist';
        }
    }

    if (count($msg['err']) <= 0) { // non ci sono errori, posso procedere
        if ($form['id']==0) { // ho un inserimento
            gaz_dbi_table_insert('bank',$form);
            header("Location: report_bank.php?ord_id=desc");
        } else { // aggiornamento
            gaz_dbi_table_update('bank',array('id',$form['id']),$form);
            header("Location: report_bank.php?abi=All&sea_id=".$form['id']);
        }
        exit;
    }
    }

} elseif ( !isset($_POST['hidden_req']) && !isset($_GET['id'])) { //al primo accesso allo script per insert
    $form = gaz_dbi_fields('bank');
    $form['iso_country'] = $admin_aziend['country'];
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
	$form['id_municipalities']=0;
    $form['search_municipalities'] = '';
    $form['descri_municipalities'] = '';
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('custom/autocomplete'));
?>
<script>
$( function() {
    $( "#search_municipalities" ).autocomplete({
        source: "search.php?opt=municipalities",
        minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)

        // optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
        select: function(event, ui) {
            $("#id_municipalities").val(ui.item.value);
            $(this).closest("form").submit();
        }
    });
});
</script>
<form method="POST" name="form">
<input type="hidden" name="ritorno" value="<?php echo $form['ritorno']; ?>">
<input type="hidden" name="hidden_req" value="<?php echo $form['hidden_req']; ?>">
<?php
$gForm = new informForm();
if ($form['id']>0) {
    echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['upd_this'] .  $form['id'];
} else {
    echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['ins_this'];
}
echo '<input type="hidden" value="'.$form['id'].'" name="id" /></div>';

if (count($msg['err']) > 0) { // ho un errore
    $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
if (count($msg['war']) > 0) { // ho un alert
    $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
}
?>

<div class="panel panel-default gaz-table-form div-bordered">
  <div class="container-fluid">
<div class="col-xs-12 text-right FacetFooterTd"><input class="btn btn-warning" id="preventDuplicate" onClick="chkSubmit();" type="submit" name="ins" value="<?php echo ucfirst($script_transl[($form['id']>0)?'update':'insert']); ?>"/></div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="iso_country" class="col-sm-4 control-label"><?php echo $script_transl['iso_country']; ?></label>
    <?php
$gForm->selectFromDB('country', 'iso_country', 'iso', $form['iso_country'], 'iso', 0, ' - ', 'name');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="codabi" class="col-sm-4 control-label"><?php echo $script_transl['codabi']; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['codabi']; ?>" name="codabi" maxlength="5"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="descriabi" class="col-sm-4 control-label"><?php echo $script_transl['descriabi']; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['descriabi']; ?>" name="descriabi" maxlength="100"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="codcab" class="col-sm-4 control-label"><?php echo $script_transl['codcab']; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['codcab']; ?>" name="codcab" maxlength="5"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="descricab" class="col-sm-4 control-label"><?php echo $script_transl['descricab']; ?> </label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['descricab']; ?>" name="descricab" maxlength="100"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="indiri" class="col-sm-4 control-label"><?php echo $script_transl['indiri']; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['indiri']; ?>" name="indiri" maxlength="100"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="id_municipalities" class="col-sm-4 control-label"><?php echo $script_transl['id_municipalities']; ?></label>
    <?php
    $gForm->selectMunicipalities($form['search_municipalities'], $form['id_municipalities']);

//$gForm->selectFromDB('municipalities', 'id_municipalities', 'id', $form['id_municipalities'], 'name', 1, ' - ', 'name');
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="cap" class="col-sm-4 control-label"><?php echo $script_transl['cap']; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['cap']; ?>" name="cap" maxlength="10"/>
                </div>
            </div>
        </div><!-- chiude row  -->
</div>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
