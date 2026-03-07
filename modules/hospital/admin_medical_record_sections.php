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
if (!isset($_POST['hidden_req']) && isset($_GET['id_ref']) && intval($_GET['id_ref']) >= 1 ) { //al primo accesso allo script per update
  $form = gaz_dbi_get_row($gTables['company_data'], 'id_ref', intval($_GET['id_ref']), " AND ref = 'medical_record_section'");
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
} elseif (!isset($_POST['hidden_req']) && !isset($_GET['id_ref'])) { //al primo accesso allo script per insert
  $form = gaz_dbi_fields('company_data');
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
} elseif (isset($_POST['id_ref'])) { // accessi successivi
  $form = gaz_dbi_parse_post('company_data');
  $form['ritorno'] = $_POST['ritorno'];
  $form['hidden_req'] = $_POST['hidden_req'];
  $form['description'] = trim($form['description']);
  if (isset($_POST['ins'])) { // conferma tutto
    if ($form['id_ref'] == 0 && strlen($form['description']) > 5 ) {
      $ap = gaz_dbi_get_row($gTables['company_data'], 'description', $form['description']);
      if ($ap){ // esite con lo stesso nome
        $msg['err'][] = 'existname';
      }
    }
    if (strlen($form['description']) < 6) {
      $msg['err'][] = 'description';
    }
    if ( count($msg['err']) == 0 || ( count($msg['war']) == 1 )) { // nessun errore oppure ho scelto di usare una anagrafica esistente
      if ($form['id_ref']==0) { // ho un inserimento
        $form['ref'] = 'medical_record_section';
        // riprendo l'ultima sezione di cartella clinica
        $rs = gaz_dbi_query("SELECT id_ref FROM " . $gTables['company_data'] . " WHERE ref = 'medical_record_section' ORDER BY id_ref DESC LIMIT 0,1");
        $last_mrs = gaz_dbi_fetch_assoc($rs);
        $form['id_ref'] = $last_mrs ? ($last_mrs['id_ref']+1) : 1;
        gaz_dbi_table_insert('company_data', $form);
      } else { // modifica
        gaz_dbi_table_update('company_data', ['id_ref',$form['id_ref']], $form);
      }
      header("Location: report_medical_record_sections.php");
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
$upd=($form['id_ref']>0)?'upd_':'ins_';
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
  <input type="hidden" value="<?php echo $form['id_ref'] ?>" name="id_ref" />
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

if ($form['id_ref'] == 0 ) { // inserimento
?>


<?php
}
?>
<div class="panel panel-default gaz-table-form div-bordered">
  <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="roomname" class="col-sm-4 control-label"><?php echo $script_transl['description']; ?> *</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['description']; ?>" name="description" minlenght="1" maxlength="100" placeholder="minimo 1 carattere"/>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="var" class="col-sm-4 control-label"><?php echo $script_transl['var']; ?> </label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['var']; ?>" name="var" maxlength="100" placeholder="Note o altro"/>
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
