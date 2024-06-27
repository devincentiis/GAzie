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
$admin_aziend = checkAdmin(9);
$msg = array('err' => array(), 'war' => array());
$rs_text = gaz_dbi_dyn_query('*', $gTables['body_text'], "table_name_ref = 'privacy_regol'");
$exist_true = gaz_dbi_fetch_array($rs_text);

if ($exist_true) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
	$form['body_text']=filter_input(INPUT_POST, 'body_text', FILTER_SANITIZE_SPECIAL_CHARS);
    if (count($msg['err']) == 0) { // nessun errore
        // aggiorno il db
        if ($toDo == 'insert') {
			$form['table_name_ref']='privacy_regol';
			regolInsert($form);
        } elseif ($toDo == 'update') {
			gaz_dbi_put_row($gTables['body_text'], 'table_name_ref', 'privacy_regol', 'body_text', $form['body_text']);
        }
        header("Location: print_privacy_regol.php");
        exit;
    }
} elseif ($exist_true) { //se e' il primo accesso per UPDATE
    $form = gaz_dbi_get_row($gTables['body_text'], 'table_name_ref', 'privacy_regol');
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['body_text'] = '';
}

require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<script>
</script>
<?php
$gForm = new configForm();
if (count($msg['err']) > 0) { // ho un errore
    $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
?>
<form method="POST" name="form" enctype="multipart/form-data">
    <input type="hidden" name="<?php echo ucfirst($toDo) ?>" value="">
    <?php
    if ($toDo == 'insert') {
        echo '<div class="text-center"><b>' . $script_transl['ins_this'] . "</b></div>\n";
    } else {
        echo '<div class="text-center"><b>' . $script_transl['upd_this'] ."</b></div>\n";
    }
    ?>
    <div class="panel panel-default">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                        <label for="body_text" class="col-sm-4 control-label"><?php echo $script_transl['body_text']; ?></label>
						<textarea id="body_text" name="body_text" class="mceClass"><?php echo $form['body_text']; ?></textarea>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group text-center">
                        <input class="btn btn-warning" name="Submit" type="submit" value="<?php echo ucfirst($script_transl[$toDo]); ?> & <?php echo ucfirst($script_transl['print']); ?> ">
                    </div>
                </div>
            </div><!-- chiude row  -->
        </div><!-- chiude container  -->
    </div><!-- chiude panel  -->

</form>
<?php
require("../../library/include/footer.php");
?>