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
$msg = ['err' => [], 'war' => []];

if (isset($_GET['id_tes']) || (isset($_POST['id_tes']) && $_POST['id_tes'] >= 1  ) ) {
  $toDo = 'update';
	$class_btn_confirm='btn-warning';
} else {
  $toDo = 'insert';
	$class_btn_confirm='btn-warning';
}

if (isset($_POST['hidden_req'])) {   //se non e' il primo accesso
  //qui si dovrebbe fare un parsing di quanto arriva dal browser...
  $form['id_tes'] = intval($_POST['id_tes']);
  $form['id_con'] = intval($_POST['id_con']);
  $form['template'] = substr($_POST['template'],0,32);
  $form['tipdoc'] = substr($_POST['tipdoc'],0,3);
  $form['numdoc'] = intval($_POST['numdoc']);
  $form['regime'] = substr($_POST['regime'],0,1); // uso tipdoc_buf per il regime (residenziale,day-hospital, ecc)
  $form['seziva'] = intval($_POST['seziva']); // uso seziva per il letto
  $form['tutor_type'] = intval($_POST['tutor_type']);
  $form['tutor_descri'] = strtoupper(substr($_POST['tutor_descri'],0,10));
  $form['datemi'] = substr($_POST['datemi'],0,10); // data ammissione
  $form['datfat'] = substr($_POST['datfat'],0,10); // data registrazione
  $form['tutor_fname']=strtoupper(substr($_POST['tutor_fname'],0,60));
  $form['tutor_lname']=strtoupper(substr($_POST['tutor_lname'],0,60));
  $form['tutor_sex']=substr($_POST['tutor_sex'],0,1);
  $form['tutor_birth_date']=substr($_POST['tutor_birth_date'],0,10);
  $form['tutor_birth_place']=strtoupper(substr($_POST['tutor_birth_place'],0,80));
  $form['tutor_birth_prov_code']=substr($_POST['tutor_birth_prov_code'],0,2);
  $form['tutor_birth_country']=substr($_POST['tutor_birth_country'],0,2);
  $form['tutor_tax_code']=strtoupper(substr($_POST['tutor_tax_code'],0,16));
  $form['tutor_telephone']=substr($_POST['tutor_telephone'],0,30);
  $form['tutor_residence_address']=substr($_POST['tutor_residence_address'],0,60);
  $form['tutor_residence_place']=strtoupper(substr($_POST['tutor_residence_place'],0,60));
  $form['tutor_residence_postal_code']=substr($_POST['tutor_residence_postal_code'],0,5);
  $form['tutor_residence_prov_code']=strtoupper(substr($_POST['tutor_residence_prov_code'],0,2));
  $form['hidden_req'] = $_POST['hidden_req'];
  if (isset($_POST['docs'])){
    foreach ($_POST['docs'] as $kr => $vr) {
      $form['docs'][$kr] = $vr;
      // eseguo i controlli sui documenti solo se submit
      if (isset($_POST['ins'])) {
        if ($kr=='titpos' && empty($vr) ){
          $msg['err'][] = 'titpos';
        }
      }
      if (!empty($_FILES['docfile_' . $kr]['name'])) {
        if (!($_FILES['docfile_' . $kr]['type'] == "image/png" ||
              $_FILES['docfile_' . $kr]['type'] == "image/x-png" ||
              $_FILES['docfile_' . $kr]['type'] == "image/jpeg" ||
              $_FILES['docfile_' . $kr]['type'] == "image/jpg" ||
              $_FILES['docfile_' . $kr]['type'] == "image/gif" ||
              $_FILES['docfile_' . $kr]['type'] == "image/x-gif")) $msg['err'][] = 'image';
        if ($_FILES['docfile_' . $kr]['size'] > 1999999) $msg['err'][] = 'imasize';
        if (count($msg['err'])==0) {
          $move = false;
          $mt = strtolower(substr($_FILES['docfile_' . $kr]['name'], -3));
          if (($mt == 'png' || $mt == 'peg' || $mt == 'jpg' || $mt == 'gif') && $_FILES['docfile_' . $kr]['size'] > 1000) { //se c'e' un nuovo documento nel buffer
            $move = move_uploaded_file($_FILES['docfile_' . $kr]['tmp_name'], DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/tmp/'.$kr.'_'.$_FILES['docfile_' . $kr]['name']);
            correctImageOrientation(DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/tmp/'.$kr.'_'.$_FILES['docfile_' . $kr]['name']);
          }
          if (!$move) {
            $msg['err'][] = 'noimgl';
          } else {
            $form['docs'][$kr] = $_FILES['docfile_' . $kr]['name'];
            // ricordo che l'immagine si trova in tmp
            if (!in_array($kr,$tmpdocs)) {
              $tmpdocs[]=$kr;
            }
            $form['tmpdocs']=implode('_',$tmpdocs);
          }
        }
      }
    }
    }
    // Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {
      if (intval($form['id_con']) < 1 ) { // paziente non selezionato ?!?!
        $msg['err'][] = 'id_con';
      }
      if ($form['regime']=='') { // regime non indicato
        $msg['err'][] = 'regime';
      }
      if ($form['regime']==0 && $form['seziva'] < 1) { // se residenziale si deve indicare il letto (usato "seziva")
        $msg['err'][] = 'bed';
      }
      if ($form['tutor_type'] < 9 ) { // paziente con tutore
        if (strlen(trim($form['tutor_descri'])) < 3) {
          if ($form['tutor_type'] == 8 ) {
            $msg['err'][] = 'tutor_descri8';
          } else if ($form['tutor_type'] == 1) {
            $msg['err'][] = 'tutor_descri1';
          }
        }
        if (strlen(trim($form['tutor_fname'])) < 3) {
          $msg['err'][] = 'tutor_fname';
        }
        if (strlen(trim($form['tutor_lname'])) < 3) {
          $msg['err'][] = 'tutor_lname';
        }
        if (strlen(trim($form['tutor_sex'])) < 1) {
          $msg['err'][] = 'tutor_sex';
        }
      }

      if (count($msg['err'])==0) { // nessun errore
        // formatto le date per il db
        $form['datemi']= gaz_format_date($form['datemi'],true);
        $form['datfat']= gaz_format_date($form['datfat'],true);
        $form['tipdoc_buf']= $form['regime'];
        if ($toDo=='update') { // modifica
          $idtes = ['id_tes', $form['id_tes']];
          gaz_dbi_table_update('tesbro',$idtes,$form);
          // serializzo e cripto i dati del tuto mettendoli in custom_field
          $custom_field['tutor']=['type'=>$form['tutor_type'],'descri'=>$form['tutor_descri'],'fname'=>$form['tutor_fname'],'lname'=>$form['tutor_lname'],'sex'=>$form['tutor_sex'],'birth_date'=>$form['tutor_birth_date'],'birth_place'=>$form['tutor_birth_place'],'birth_prov_code'=>$form['tutor_birth_prov_code'],'birth_country'=>$form['tutor_birth_country'],'tax_code'=>$form['tutor_tax_code'],'telephone'=>$form['tutor_telephone'],'residence_address'=>$form['tutor_residence_address'],'residence_place'=>$form['tutor_residence_place'],'residence_postal_code'=>$form['tutor_residence_postal_code'],'residence_prov_code'=>$form['tutor_residence_prov_code']];
          gaz_dbi_query("UPDATE ".$gTables['tesbro']." SET custom_field=TO_BASE64(AES_ENCRYPT('".bin2hex(json_encode($custom_field))."','".$_SESSION['aes_key']."')) WHERE id_tes=".$form['id_tes']);

        } else { // inserimento
          // cerco l'ultima ammissione dello stesso tipo
          $rs_last = gaz_dbi_dyn_query("*", $gTables['tesbro'],"tipdoc = '" . $form['tipdoc'] . "'",'numdoc DESC', 0, 1);
          $last = gaz_dbi_fetch_array($rs_last);
          // se e' il primo documento dell'anno, resetto il contatore
          if ($last) {
              $form['numdoc'] = $last['numdoc'] + 1;
          } else {
              $form['numdoc'] = 1;
          }
          $id_tes=gaz_dbi_table_insert('tesbro', $form);
          // serializzo e cripto i dati del tuto mettendoli in custom_field
          $custom_field['tutor']=['type'=>$form['tutor_type'],'descri'=>$form['tutor_descri'],'fname'=>$form['tutor_fname'],'lname'=>$form['tutor_lname'],'sex'=>$form['tutor_sex'],'birth_date'=>$form['tutor_birth_date'],'birth_place'=>$form['tutor_birth_place'],'birth_prov_code'=>$form['tutor_birth_prov_code'],'birth_country'=>$form['tutor_birth_country'],'tax_code'=>$form['tutor_tax_code'],'telephone'=>$form['tutor_telephone'],'residence_address'=>$form['tutor_residence_address'],'residence_place'=>$form['tutor_residence_place'],'residence_postal_code'=>$form['tutor_residence_postal_code'],'residence_prov_code'=>$form['tutor_residence_prov_code']];
          gaz_dbi_query("UPDATE ".$gTables['tesbro']." SET custom_field=TO_BASE64(AES_ENCRYPT('".bin2hex(json_encode($custom_field))."','".$_SESSION['aes_key']."')) WHERE id_tes=".$id_tes);
        }
        header("Location: patient_dashboard.php");
        exit;
      }
    }
} elseif (isset($_GET['id_tes']) && $_GET['id_tes'] >= 1 ) { //se e' il primo accesso per UPDATE
    $form = gaz_dbi_get_row($gTables['tesbro'], "id_tes", intval($_GET['id_tes']));
    // riprendo i valori del tutor criptati su custom_field
    $rs_cf=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(custom_field),'".$_SESSION['aes_key']."') AS custom_field FROM ".$gTables['tesbro']." WHERE id_tes = ".$form['id_tes']);
    $doc=gaz_dbi_fetch_array($rs_cf);
    $cf_array=json_decode(hex2bin($doc['custom_field']),true);
    foreach ($cf_array['tutor'] as $k=>$v ) { // rivalorizzo con gli indici di immissione
      $form['tutor_'.$k]=$v;
    }
    // torno indietro se il tipdoc non è tra quelli gestiti da questo modulo ( tesbro può essere usato per molto altro)
    if (!($form['tipdoc']=='HAD'||$form['tipdoc']=='HDI')){
      header("Location: ../../modules/root/admin.php");
    }
    $form['hidden_req'] = '';
    $form['datemi'] = gaz_format_date($form['datemi'],false,4);
    $form['datfat'] = gaz_format_date($form['datfat'],false,4);
    $form['regime'] = $form['tipdoc_buf'];
} else { //se e' il primo accesso per INSERT
  $toDo='insert';
  $form['id_con']=$_SESSION['id_patient'];
  $form['tipdoc']= "HAD";
  $form['template']= "admission";
  $form['datemi']= date('d/m/Y');
  $form['datfat']= $form['datemi'];
  $form['numdoc']= 0;
  $form['id_tes']= 0;
  $form['regime']= '';
  $form['seziva'] = 0; // uso seziva per il letto
  $form['tutor_type'] = 0;
  $form['tutor_descri']= '';
  $form['tutor_fname']='';
  $form['tutor_lname']='';
  $form['tutor_sex']='';
  $form['tutor_sex_value']='';
  $form['tutor_birth_date']='';
  $form['tutor_birth_place']='';
  $form['tutor_birth_prov_code']='';
  $form['tutor_birth_country']='IT';
  $form['tutor_tax_code']='';
  $form['tutor_telephone']='';
  $form['tutor_residence_address']='';
  $form['tutor_residence_place']='';
  $form['tutor_residence_postal_code']='';
  $form['tutor_residence_prov_code']='';
  $form['hidden_req']='';
}
require("../../library/include/header.php");
$script_transl = HeadMain(0,['calendarpopup/CalendarPopup','custom/autocomplete','custom/miojs']);
if ($form['id_tes'] > 0) {
  $title = ucfirst($script_transl[$toDo] . $script_transl['title']) . " n." . $form['numdoc'];
} else {
  $title = ucfirst($script_transl[$toDo] . $script_transl['title']);
  $prefix='';
}
?>
<style>
	.ui-widget-overlay.ui-front {
		opacity: 0.75;
    z-index: 3000;
	}
</style>
<script>
function printPdf(urlPrintDoc){
  $(function(){
    $('#framePdf').attr('src',urlPrintDoc);
    $('#framePdf').css({'height': '100%'});
    $('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
    $('#closePdf').on( "click", function() {
      $('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
      window.location.href = "<?php echo $form['ritorno']; ?>";
    });
  });
};
$(function(){
  $("#datemi,#datfat,#tutor_birth_date").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});

  $( "#search_tutor_birth_place" ).autocomplete({
    source: "../root/search.php?opt=location",
    minLength: 2,
    html: true, // optional (jquery.ui.autocomplete.html.js required)
    focus: function( event, ui ) {
      $( "#search_tutor_birth_place" ).val( ui.item.value );
      $( "#search_tutor_birth_prov_code" ).val( ui.item.prospe );
      $( "#tutor_birth_country").val( ui.item.country );
      return false;
    },
    select: function( event, ui ) {
      $( "#search_tutor_birth_place" ).val( ui.item.value );
      $( "#search_tutor_birth_prov_code" ).val( ui.item.prospe );
      $( "#tutor_birth_country").val( ui.item.country );  //grazie ad Emanuele Ferrarini
      return false;
    }
  });
  $('#search_tutor_birth_place').blur(function() {
    if( !$(this).val() ) {
      $( "#search_tutor_birth_prov_code" ).val("");
      $( "#tutor_birth_country").val("IT");
    }
  });

  $( "#search_tutor_residence_place" ).autocomplete({
    source: "../root/search.php?opt=location",
    minLength: 2,
    html: true, // optional (jquery.ui.autocomplete.html.js required)
    focus: function( event, ui ) {
      $( "#search_tutor_residence_place" ).val( ui.item.value );
      $( "#search_tutor_residence_postal_code" ).val( ui.item.id );
      $( "#search_tutor_residence_prov_code" ).val( ui.item.prospe );
      return false;
    },
    select: function( event, ui ) {
      $( "#search_tutor_residence_place" ).val( ui.item.value );
      $( "#search_tutor_residence_postal_code" ).val( ui.item.id );
      $( "#search_tutor_residence_prov_code" ).val( ui.item.prospe );
      return false;
    }
  });
  $('#search_tutor_residence_place').blur(function() {
    if( !$(this).val() ) {
      $( "#search_tutor_residence_prov_code" ).val("");
    }
  });

});
</script>
<form method="post" name="broven" enctype="multipart/form-data">
  <div style="display:none" id="dialog_delete_fotografia" title="Conferma eliminazione immagine">
    <p><b>Foto:</b></p>
    <p class="ui-state-highlight" id="idcodice"></p>
  </div>
  <div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
    <div class="col-lg-12">
      <div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
        <div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
      </div>
      <iframe id="framePdf"  style="height: 100%; width: 100%" src="../../library/images/wait_spinner.html"></iframe>
  </div>
  <div style="display:none" id="wait_upload"title="Attenti il caricamento del file"></div>
<?php
$gForm = new hospitalForm();
if (count($msg['err']) > 0) { // ho un errore
  $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
if (count($msg['war']) > 0) { // ho un alert
  $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
}
echo '<input type="hidden" value="' . $form['id_tes'] . '" name="id_tes" />
      <input type="hidden" value="' . $form['id_con'] . '" name="id_con" />
      <input type="hidden" value="' . $form['template'] . '" name="template" />
      <input type="hidden" value="' . $form['numdoc'] . '" name="numdoc" />
      <input type="hidden" value="' . $form['tipdoc'] . '" name="tipdoc" />
      <input type="hidden" value="' . $form['hidden_req'] . '" name="hidden_req" />
      <input type="hidden" value="' . (isset($_POST['last_focus']) ? $_POST['last_focus'] : "") . '" name="last_focus" />';
if ($form['id_con']>=1) {
  require_once("./lib.data.php");
  $patient=DecryptPersonalData($gTables['encrypted_personal_data'],'id_patient_bidx',$form['id_con'])[0];
  preg_match_all('/(?<=\b)\w/iu',$patient['last_name'],$matches);
  $patient_redname=$patient['first_name'].' '.implode('.',$matches[0]).'.';

?>
<div class="panel panel-success gaz-table-form ">
 <div class="container-fluid">
  <div class="bg-success text-center">
    <b>AMMISSIONE OSPITE</b>
  </div><!-- chiude text-center  -->
  <div class="row">
    <div class="col-md-12">
      <div class="form-group">
          <label for="datemi" class="col-xs-12 col-sm-4 control-label text-right"> Data Ammissione:</label>
          <input class="col-xs-12 col-sm-8" type="text" value="<?php echo $form['datemi']; ?>" id="datemi" name="datemi" maxlength=10 />
      </div>
    </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="regime" class="col-xs-12 col-sm-4 control-label text-right"><?php echo $script_transl['regime']; ?>*:</label>
              <?php
              $gForm->variousSelect('regime', $script_transl['regime_value'], $form['regime'], "col-xs-12 col-sm-8", false,'regime');
              ?>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="seziva" class="col-xs-12 col-sm-4 control-label text-right"><?php echo $script_transl['seziva']; ?> </label>
<?php $gForm->selectBed('seziva',$form["seziva"],false,'col-xs-12 col-sm-8', true); ?>
          </div>
      </div>
  </div><!-- chiude row  -->

 </div>
</div>
<div class="panel panel-warning gaz-table-form ">
 <div class="container-fluid">
  <div class="bg-warning text-center col-xs-12"><b>DATI DELL'OSPITE: <?php echo $patient['first_name'].' '.$patient['last_name']; ?></b></div>
  <div class="row">
    <div class="col-md-12">
      <div class="form-group">
        <label class="col-xs-12 col-sm-3"><small>Luogo e data di nascita: </small></label>
        <div class="col-xs-12 col-sm-9">
          <div class="col-xs-12 col-sm-6"><?php echo $patient['birth_place']; ?></div>
          <div class="col-xs-12 col-sm-6"><?php echo $patient['birth_date']; ?></div>
        </div>
      </div>
    </div>
  </div><!-- chiude row  -->
  <div class="row">
    <div class="col-md-12">
      <div class="form-group">
        <label class="col-xs-12 col-sm-3"><small>Residenza: </small></label>
        <div class="col-xs-12 col-sm-9">
          <div class="col-xs-12 col-sm-9"><?php echo $patient['residence_place']; ?></div>
          <div class="col-xs-12 col-sm-3"><?php echo $patient['residence_prov_code']; ?></div>
          <div class="col-xs-12 col-sm-12"><?php echo $patient['residence_address']; ?></div>
        </div>
      </div>
    </div>
  </div><!-- chiude row  -->
  <div class="row">
    <div class="col-md-12">
      <div class="form-group">
        <label class="col-xs-12 col-sm-3"><small>Codice fiscale: </small></label>
        <div class="col-xs-12 col-sm-9">
          <div class="col-xs-12 col-sm-12"><?php echo $patient['tax_code']; ?></div>
        </div>
      </div>
    </div>
  </div><!-- chiude row  -->
 </div>
</div>
<?php
}
?>
<div class="panel panel-info gaz-table-form">
 <div class="container-fluid">
  <div class="bg-info text-center">
    <b>DATI EVENTUALE TUTORE</b>
  </div><!-- chiude text-center  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
            <label for="tutor" class="col-xs-12 col-sm-2 text-right"><?php echo $script_transl['tutor']; ?></label>
    <?php
$gForm->variousSelect('tutor_type', $script_transl['tutor_value'],$form['tutor_type'],"col-xs-12 col-sm-4",false,'tutor_type');
    ?>
           <input class="col-xs-12 col-sm-6" type="text" value="<?php echo $form['tutor_descri']; ?>" name="tutor_descri" placeholder="grado e/o altre indicazioni" maxlength=100/>
          </div>
      </div>
  </div><!-- chiude row  -->
<?php
if ($form['tutor_type']<9) {
?>
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_fname" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_fname']; ?> *</label>
              <input class="col-xs-12 col-sm-8" type="text" placeholder="<?php echo $script_transl['tutor_fname']; ?>" value="<?php echo $form['tutor_fname']; ?>" name="tutor_fname" maxlength="60" />
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_lname" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_lname']; ?> *</label>
              <input class="col-xs-12 col-sm-8" type="text" placeholder="<?php echo $script_transl['tutor_lname']; ?>" value="<?php echo $form['tutor_lname']; ?>" name="tutor_lname" maxlength="60" />
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_sex" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_sex']; ?> *</label>
              <?php
              $gForm->variousSelect('tutor_sex', $script_transl['tutor_sex_value'], $form['tutor_sex'], "col-xs-12 col-sm-8", true, '', false);
              ?>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_birth_date" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_birth_date']; ?></label>
              <input type="text" class="col-xs-12 col-sm-8"  id="tutor_birth_date" placeholder="<?php echo $script_transl['tutor_birth_date']; ?>" value="<?php echo $form['tutor_birth_date']; ?>" name="tutor_birth_date" maxlength="10" />
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_birth_place" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_birth_place']; ?></label>
              <input type="text" class="col-xs-12 col-sm-8" id="search_tutor_birth_place" placeholder="<?php echo $script_transl['tutor_birth_place']; ?>" name="tutor_birth_place" value="<?php echo $form['tutor_birth_place']; ?>" maxlength="50">
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_birth_prov_code" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_birth_prov_code']; ?></label>
              <input type="text" class="col-xs-12 col-sm-8" id="search_tutor_birth_prov_code" placeholder="<?php echo $script_transl['tutor_birth_prov_code']; ?>" value="<?php echo $form['tutor_birth_prov_code']; ?>" name="tutor_birth_prov_code" maxlength="2" />
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_birth_country" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_birth_country']; ?></label>
              <?php
              $gForm->selectFromDB('country','tutor_birth_country','iso',$form['tutor_birth_country'],'iso',1,' - ','name','',"col-sm-8");
              ?>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_tax_code" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_tax_code']; ?></label>
              <input type="text" class="col-xs-12 col-sm-8" placeholder="<?php echo $script_transl['tutor_tax_code']; ?>" value="<?php echo $form['tutor_tax_code']; ?>" name="tutor_tax_code" maxlength="16" />
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_telephone" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_telephone']; ?></label>
              <input type="text" class="col-xs-12 col-sm-8" placeholder="<?php echo $script_transl['tutor_telephone']; ?>" value="<?php echo $form['tutor_telephone']; ?>" name="tutor_telephone" maxlength="50" />
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_residence_address" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_residence_address']; ?> </label>
              <input type="text" class="col-xs-12 col-sm-8" placeholder="<?php echo $script_transl['tutor_residence_address']; ?>" value="<?php echo $form['tutor_residence_address']; ?>" name="tutor_residence_address" maxlength="50" />
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_residence_place" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_residence_place']; ?></label>
              <input type="text" class="col-xs-12 col-sm-8" id="search_tutor_residence_place" placeholder="<?php echo $script_transl['tutor_residence_place']; ?>" value="<?php echo $form['tutor_residence_place']; ?>" name="tutor_residence_place" maxlength="50" />
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_residence_postal_code" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_residence_postal_code']; ?> </label>
              <input type="text" class="col-xs-12 col-sm-8" id="search_tutor_residence_postal_code" placeholder="<?php echo $script_transl['tutor_residence_postal_code']; ?>" value="<?php echo $form['tutor_residence_postal_code']; ?>" name="tutor_residence_postal_code" maxlength="5" />
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="tutor_residence_prov_code" class="col-xs-12 col-sm-4 control-label"><?php echo $script_transl['tutor_residence_prov_code']; ?></label>
              <input type="text" class="col-xs-12 col-sm-8" id="search_tutor_residence_prov_code" placeholder="<?php echo $script_transl['tutor_residence_prov_code']; ?>" value="<?php echo $form['tutor_residence_prov_code']; ?>" name="tutor_residence_prov_code" maxlength="2" />
          </div>
      </div>
  </div><!-- chiude row  -->
<?php
} else {
echo '<input type="hidden" value="' . $form['tutor_fname'] . '" name="tutor_fname" />
      <input type="hidden" value="' . $form['tutor_lname'] . '" name="tutor_lname" />
      <input type="hidden" value="' . $form['tutor_sex'] . '" name="tutor_sex" />
      <input type="hidden" value="' . $form['tutor_birth_date'] . '" name="tutor_birth_date" />
      <input type="hidden" value="' . $form['tutor_birth_place'] . '" name="tutor_birth_place" />
      <input type="hidden" value="' . $form['tutor_birth_prov_code'] . '" name="tutor_birth_prov_code" />
      <input type="hidden" value="' . $form['tutor_birth_country'] . '" name="tutor_birth_country" />
      <input type="hidden" value="' . $form['tutor_tax_code'] . '" name="tutor_tax_code" />
      <input type="hidden" value="' . $form['tutor_telephone'] . '" name="tutor_telephone" />
      <input type="hidden" value="' . $form['tutor_residence_address'] . '" name="tutor_residence_address" />
      <input type="hidden" value="' . $form['tutor_residence_place'] . '" name="tutor_residence_place" />
      <input type="hidden" value="' . $form['tutor_residence_postal_code'] . '" name="tutor_residence_postal_code" />
      <input type="hidden" value="' . $form['tutor_residence_prov_code'] . '" name="tutor_residence_prov_code" />';
}
?>
 </div>
</div>
<div class="panel panel-danger gaz-table-form div-bordered">
 <div class="container-fluid">
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="datfat" class="col-xs-12 col-sm-4 control-label text-right"> Data registrazione:</label>
              <input class="col-xs-12 col-sm-5" type="text" value="<?php echo $form['datfat']; ?>" id="datfat" name="datfat" maxlength=10 />
              <div class="col-sm-3"><input name="ins" class="btn <?php echo $class_btn_confirm; ?>" id="preventDuplicate" onClick="chkSubmit();" type="submit" value=" &nbsp; <?php echo ucfirst($script_transl[$toDo]); ?>"></div>
          </div>
      </div>
  </div><!-- chiude row  -->
 </div>
</div>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
