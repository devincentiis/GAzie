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
$admin_aziend=checkAdmin();
$msg=['err'=>[],'war'=>[]];
$msg['war'][] = 'alert';
if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
  $form['hidden_req'] = '';
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['oldcodart']='';
	$form['cosear'] = '';
  $form['newcodart']='';
} else { // accessi successivi
  $form['hidden_req']=htmlentities($_POST['hidden_req']);
  $form['ritorno']=$_POST['ritorno'];
  $form['oldcodart']=substr(trim($_POST['oldcodart']),0,32);
  $form['newcodart']=substr(trim($_POST['newcodart']),0,32);
	$form['cosear'] = filter_var($_POST['cosear'],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  if (isset($_POST['yeschange']) || isset($_POST['view']) || isset($_POST['mergecodart']) ) {
    $artico = gaz_dbi_get_row($gTables['artico'], 'codice' , $form['newcodart']); // se il nuovo codice è esistente segnalo errore e chiedo se fonderlo
    if ($artico && !isset($_POST['mergecodart'])){
      $msg['err'][] = 'codexist';
    }
    if (strlen($form['oldcodart'])<=1){
      $msg['err'][] = 'noold';
    }
    if (strlen($form['newcodart'])<=1){
      $msg['err'][] = 'nonew';
    }
    if (count($msg['err'])<=0 && (isset($_POST['yeschange']) || isset($_POST['mergecodart'])) ){
      // procedo con le modifiche
      if (isset($_POST['mergecodart'])){ // ho fuso con codice esistente, elimino il vecchio
        gaz_dbi_query ("DELETE FROM ".$gTables['artico']." WHERE codice = '".$form['oldcodart']."'");
      } else {
        gaz_dbi_query ("UPDATE ".$gTables['artico']." SET codice = '".$form['newcodart']."' WHERE codice = '".$form['oldcodart']."'");
      }
     	gaz_dbi_query ("UPDATE ".$gTables['artico_position']." SET codart = '".$form['newcodart']."' WHERE codart = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['assets']." SET codice_artico = '".$form['newcodart']."' WHERE codice_artico = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['assist']." SET codart = '".$form['newcodart']."' WHERE codart = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['camp_artico']." SET codice = '".$form['newcodart']."' WHERE codice = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['distinta_base']." SET codice_artico_base = '".$form['newcodart']."' WHERE codice_artico_base = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['distinta_base']." SET codice_composizione = '".$form['newcodart']."' WHERE codice_composizione = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['lotmag']." SET codart = '".$form['newcodart']."' WHERE codart = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['movmag']." SET artico = '".$form['newcodart']."' WHERE artico = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['provvigioni']." SET cod_articolo = '".$form['newcodart']."' WHERE cod_articolo = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['rigbro']." SET codart = '".$form['newcodart']."' WHERE codart = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['rigdoc']." SET codart = '".$form['newcodart']."' WHERE codart = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['sconti_articoli']." SET codart = '".$form['newcodart']."' WHERE codart = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['shelves']." SET code = '".$form['newcodart']."' WHERE code = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['camp_artico']." SET codice = '".$form['newcodart']."' WHERE codice = '".$form['oldcodart']."'");
      gaz_dbi_query ("UPDATE ".$gTables['distinta_base']." SET codice_artico_base = '".$form['newcodart']."' WHERE codice_artico_base = '".$form['oldcodart']."'");
      header("Location: ".$form['ritorno']);
      exit;
    }
  }
  if (isset($_POST['return'])) {
    header("Location: ".$form['ritorno']);
    exit;
  }
}

require("../../library/include/header.php");
$script_transl=HeadMain(0,['custom/autocomplete']);
$gForm = new magazzForm();
?>
<script>
$(function () {
  $('#newcodart').bind("change keyup", function() {
    var val = $(this).val();
    var regex = /[^a-zA-Z0-9 _\-\.\/,!Ф()?]/g;
    if (val.match(regex)) {
      $(this).css("background", "red");
      val = val.replace(regex, "");
      $(this).val(val);
    } else {
      $(this).css("background", "white");
    }
  });
});
</script>
<form method="POST" name="myForm">
<input type="hidden" value="<?php echo $form['hidden_req']; ?>" name="hidden_req" />
<input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno" />
<div class="FacetFormHeaderFont text-center"><?php echo $script_transl['title']; ?></div>
<?php
if (count($msg['war']) > 0) { // ho un errore
  $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
}
if (count($msg['err']) > 0) { // ho un errore
  $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
?>
<div class="panel panel-info gaz-table-form div-bordered">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="oldcodart" class="col-xs-12 col-md-4 control-label"><?php echo $script_transl['oldcodart']; ?></label>
          <?php
          $select_artico = new selectartico("oldcodart");
          $select_artico->addSelected($form['oldcodart']);
          $select_artico->output(substr($form['cosear'], 0,32),'C',"");
          ?>
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="newcodart" class="col-xs-12 col-md-4 control-label">Nuovo codice</label>
          <input type="text" name="newcodart" id="newcodart" value="<?php echo $form['newcodart']; ?>" maxlenght="32" />
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="text-center bg-info col-xs-12">
    <input type="submit" class="btn btn-info" name="view" value="<?php echo $script_transl['view']; ?>" >
    </div>
  </div>
</div>
<?php
if (strlen($form['oldcodart'])>1 && strlen($form['oldcodart'])>1 && isset($_POST['view'])){
  $nr=0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['artico_position']." WHERE codart = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['assets']." WHERE codice_artico = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['assist']." WHERE codart = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['camp_artico']." WHERE codice = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['distinta_base']." WHERE codice_artico_base = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['distinta_base']." WHERE codice_composizione = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['lotmag']." WHERE codart = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['movmag']." WHERE artico = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['provvigioni']." WHERE cod_articolo = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['rigbro']." WHERE codart = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['rigdoc']." WHERE codart = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['sconti_articoli']." WHERE codart = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['shelves']." WHERE code = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['camp_artico']." WHERE codice = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;
	$rs = gaz_dbi_query ("SELECT COUNT(*) FROM ".$gTables['distinta_base']." WHERE codice_artico_base = '".$form['oldcodart']."'");
  $r = gaz_dbi_fetch_row($rs);
  $nr += $r?$r[0]:0;

  if (count($msg['err'])<=0){
?>
  <div class="text-center FacetFooterTD col-xs-12">
  <input type="submit" class="btn btn-danger" accesskey="i" name="yeschange" value="<?php echo $script_transl['update'].' il codice sull\'anagrafica e su '.$nr; ?> righi delle tabelle del database">
  </div><!-- chiude row  -->
<?php
  }
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
