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
if (!isset($_POST['hidden_req']) && isset($_GET['id_room']) && intval($_GET['id_room']) >= 1 ) { //al primo accesso allo script per update
  $form = gaz_dbi_get_row($gTables['room'], 'id_room', intval($_GET['id_room']));
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
} elseif (!isset($_POST['hidden_req']) && !isset($_GET['id_room'])) { //al primo accesso allo script per insert
  $form = gaz_dbi_fields('room');
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
} elseif (isset($_POST['id_room'])) { // accessi successivi
  $form = gaz_dbi_parse_post('room');
  $form['ritorno'] = $_POST['ritorno'];
  $form['hidden_req'] = $_POST['hidden_req'];
  $form['roomname'] = trim($form['roomname']);
  if (isset($_POST['ins'])) { // conferma tutto
    if ($form['id_room'] == 0 ) {
      $ap = gaz_dbi_get_row($gTables['room'], 'roomname', $form['roomname'], ' AND id_ward = '.$form['id_ward']);
      if ($ap){ // esite con lo stesso nome
        $msg['err'][] = 'existname';
      }
    }
    if (strlen($form['roomname'])<1) {
      $msg['err'][] = 'roomname';
    }
    if ($form['id_ward'] < 1) {
      $msg['err'][] = 'id_ward';
    }
    if ( count($msg['err']) == 0 || ( count($msg['war']) == 1 )) { // nessun errore oppure ho scelto di usare una anagrafica esistente
      if ($form['id_room']==0) { // ho un inserimento
          unset($form['id_room']);
          gaz_dbi_table_insert('room', $form);
      } else { // modifica
          gaz_dbi_table_update('room', ['id_room',$form['id_room']], $form);
      }
      header("Location: report_rooms.php");
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
$upd=($form['id_room']>0)?'upd_':'ins_';
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
  <input type="hidden" value="<?php echo $form['id_room'] ?>" name="id_room" />
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

if ($form['id_room'] == 0 ) { // inserimento
?>


<?php
}
?>
<div class="panel panel-default gaz-table-form div-bordered">
  <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="id_ward" class="col-sm-4 control-label"><?php echo $script_transl['id_ward']; ?> </label>
<?php $gForm->selectWard('id_ward',$form["id_ward"],false,'col-sm-8'); ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="roomname" class="col-sm-4 control-label"><?php echo $script_transl['roomname']; ?> *</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['roomname']; ?>" name="roomname" minlenght="1" maxlength="50" placeholder="minimo 1 carattere"/>
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
