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
$admin_aziend = checkAdmin(7);
$msg=['err'=>[],'war'=>[]];
if (!isset($_POST['hidden_req']) && isset($_GET['id_ward']) && intval($_GET['id_ward']) >= 1 ) { //al primo accesso allo script per update
  $form = gaz_dbi_get_row($gTables['ward'], 'id_ward', intval($_GET['id_ward']));
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
} elseif (!isset($_POST['hidden_req']) && !isset($_GET['id_ward'])) { //al primo accesso allo script per insert
  $form = gaz_dbi_fields('ward');
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
} elseif (isset($_POST['id_ward'])) { // accessi successivi
  $form = gaz_dbi_parse_post('ward');
  $form['ritorno'] = $_POST['ritorno'];
  $form['hidden_req'] = $_POST['hidden_req'];
  $form['wardname'] = trim($form['wardname']);
  if (isset($_POST['ins'])) { // conferma tutto
    if (strlen($form['wardname'])<3) {
      $msg['err'][] = 'wardname';
    }
    if ( count($msg['err']) == 0 || ( count($msg['war']) == 1 )) { // nessun errore oppure ho scelto di usare una anagrafica esistente
      if ($form['id_ward']==0) { // ho un inserimento
        unset($form['id_ward']);
        gaz_dbi_table_insert('ward', $form);
      } else { // modifica
        gaz_dbi_table_update('ward', ['id_ward',$form['id_ward']], $form);
      }
      header("Location: report_wards.php");
      exit;
    }
  } elseif (isset($_POST['Return'])) { // torno indietro
      header("Location: " . $form['ritorno']);
      exit;
  }
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new hospitalForm();
$upd=($form['id_ward']>0)?'upd_':'ins_';
?>
<script>
$(function () {
  $("#link_anagra").click(function() {
    $("input[name=hidden_req]").val('useanagra');
    $("input[name=ins]").trigger('click');
  });
});
</script>
<form role="form" method="post" name="myform" enctype="multipart/form-data" >
  <input type="hidden" value="<?php echo $form['id_ward'] ?>" name="id_ward" />
  <input type="hidden" value="<?php echo $form['hidden_req'] ?>" name="hidden_req" />
  <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno">
<div class="text-center">
  <p><b><?php echo $script_transl[$upd.'this']; ?></b></p>
</div>
<?php
if (count($msg['err']) > 0) { // ho un errore
  $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
if (count($msg['war']) > 0) { // ho un alert
  $gForm->gazHeadMessage($msg['war'], $script_transl['err'], 'war');
}
?>
<div class="panel panel-default gaz-table-form div-bordered">
  <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="wardname" class="col-sm-4 control-label"><?php echo $script_transl['wardname']; ?> *</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['wardname']; ?>" name="wardname" minlenght="1" maxlength="50" placeholder="minimo 3 caratteri"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="note_other" class="col-sm-4 control-label"><?php echo $script_transl['note_other']; ?> </label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['note_other']; ?>" name="note_other" maxlength="50" placeholder="Note o altro"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12 text-center">
            <input class="btn btn-warning" id="preventDuplicate" onClick="chkSubmit();" type="submit" name="ins" value="<?php echo $script_transl['submit']; ?>" />
            </div>
        </div><!-- chiude row  -->
  </div>
</div>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
