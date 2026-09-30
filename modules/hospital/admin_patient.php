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
$gForm = new hospitalForm();
$msg['err'] = [];

if (isset($_POST['ritorno'])) {   //se non e' il primo accesso
  $form = gaz_dbi_parse_post('encrypted_personal_data');
  $form['patient_number'] = ($form['id_patient']>=1)?intval($_POST['patient_number']):'';
  if (!empty($_FILES['imgavatar']['name'])) { // ho aggiunto una immagine in più
    if (!($_FILES['imgavatar']['type'] == "image/png" ||
      $_FILES['imgavatar']['type'] == "image/x-png" ||
      $_FILES['imgavatar']['type'] == "image/jpeg" ||
      $_FILES['imgavatar']['type'] == "image/jpg" ||
      $_FILES['imgavatar']['type'] == "image/gif" ||
      $_FILES['imgavatar']['type'] == "image/x-gif")) $msg['err'][]='imgavatar_type';
    $extension = pathinfo($_FILES['imgavatar']['name'])['extension'];
    $tmp_name = $_FILES['imgavatar']['tmp_name'];
    if ($_FILES['imgavatar']['size'] > 1000000) {
      // anzichè segnalare l'errore ridimensiono
      $maxDim=1200;
      list($width,$height,$type,$attr) = getimagesize($tmp_name);
      if ( $width > $maxDim || $height > $maxDim ) {
        $ratio = $width/$height;
        if( $ratio > 1) {
          $new_width = intval($maxDim);
          $new_height = intval($maxDim/$ratio);
        } else {
          $new_width = intval($maxDim*$ratio);
          $new_height = intval($maxDim);
        }
        $src = imagecreatefromstring( file_get_contents( $tmp_name ) );
        $dst = imagecreatetruecolor( $new_width, $new_height );
        imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
        imagedestroy( $src );
        imagepng( $dst, $tmp_name); // adjust format as needed
        imagedestroy( $dst );
        $extension = 'png';
      }
			// fine ridimensionamento immagine in più
    }
    if (count($msg['err']) < 1 ) {
      gaz_dbi_query("INSERT INTO ".$gTables['files']." (table_name_ref, id_ref, status, content, extension, adminid) VALUES ('patient_imgavatar', '" .$form['id_patient']. "', ".time().", TO_BASE64(AES_ENCRYPT('".bin2hex(file_get_contents($tmp_name))."','".$_SESSION['aes_key']."')), '".$extension."', '".$_SESSION['user_name']."' )");
    }
  }
  if (!empty($_FILES['patient_doc']['name'])) { // ho aggiunto un documento
    if (!($_FILES['patient_doc']['type'] == "image/png" ||
      $_FILES['patient_doc']['type'] == "image/x-png" ||
      $_FILES['patient_doc']['type'] == "application/pdf" ||
      $_FILES['patient_doc']['type'] == "image/pjpeg" ||
      $_FILES['patient_doc']['type'] == "image/jpeg" )) $msg['err'][]= 'patient_doc_type';
    $fileinfo=pathinfo($_FILES['patient_doc']['name']);
    $extension = $fileinfo['extension'];
    $tmp_name = $_FILES['patient_doc']['tmp_name'];
    if ($_FILES['patient_doc']['size'] > 5000000) {
      $msg['err'][]= 'patient_doc_size';
    }
    if (count($msg['err']) < 1 ) { // non ho errori, procedo con l'upload e con l'inserimento sulla tabella files
      $iddoc=tableInsert('files',
      ['table_name_ref', 'id_ref', 'status', 'title', 'extension', 'adminid'],
      ['table_name_ref'=>'patient_doc', 'id_ref'=>$form['id_patient'],'status'=> time(),'title'=>$fileinfo['basename'],'extension'=>$extension, 'adminid'=>$_SESSION['user_name']]
      );
      $encdoc = $gForm->encryptDoc(file_get_contents($tmp_name));
      file_put_contents(DATA_DIR . "files/" .$admin_aziend['company_id']."/hospital/". $iddoc . "." . $extension, $encdoc);
    }
  }
  if (!empty($_FILES['avatar']['name'])) { // ho aggiunto l'immagine avatar di base
    if (!($_FILES['avatar']['type'] == "image/png" ||
      $_FILES['avatar']['type'] == "image/x-png" ||
      $_FILES['avatar']['type'] == "image/jpeg" ||
      $_FILES['avatar']['type'] == "image/jpg" ||
      $_FILES['avatar']['type'] == "image/gif" ||
      $_FILES['avatar']['type'] == "image/x-gif")) $msg['err'][]= 'imgavatar_type';
    $extension = pathinfo($_FILES['avatar']['name'])['extension'];
    $tmp_name = $_FILES['avatar']['tmp_name'];
    if ($_FILES['avatar']['size'] > 1000000) {
      // anzichè segnalare l'errore ridimensiono
      $maxDim=1200;
      list($width,$height,$type,$attr) = getimagesize($tmp_name);
      if ( $width > $maxDim || $height > $maxDim ) {
        $ratio = $width/$height;
        if( $ratio > 1) {
          $new_width = intval($maxDim);
          $new_height = intval($maxDim/$ratio);
        } else {
          $new_width = intval($maxDim*$ratio);
          $new_height = intval($maxDim);
        }
        $src = imagecreatefromstring( file_get_contents( $tmp_name ) );
        $dst = imagecreatetruecolor( $new_width, $new_height );
        imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
        imagedestroy( $src );
        imagepng( $dst, $tmp_name); // adjust format as needed
        imagedestroy( $dst );
        $extension = 'png';
      }
			// fine ridimensionamento immagine di base
    }
    if (count($msg['err']) < 1 ) {
      $rs_avatar = gaz_dbi_dyn_query("id_doc", $gTables['files'],"table_name_ref='patient_imgavatar' AND id_ref=".$form['id_patient'],"id_doc",0,1);
      $avatar=gaz_dbi_fetch_array($rs_avatar);
      if ($form['id_patient'] >= 1 && $avatar) { // controllo presenza immagine avatar in caso di update
        gaz_dbi_query("UPDATE ".$gTables['files']." SET status=".time().",content=TO_BASE64(AES_ENCRYPT('".bin2hex(file_get_contents($tmp_name))."','".$_SESSION['aes_key']."')),extension='".$extension."', adminid='".$_SESSION['user_name']."' WHERE id_doc=".$avatar['id_doc']);
      }
    }
  }
  if (isset($_POST['medrec'])) { // vado sulla cartella clinica
    $_SESSION['id_patient']=$form['id_patient'];
    header("Location: patient_dashboard.php");
    exit;
  } elseif (isset($_POST['Submit'])) { // conferma tutto
    require("../../library/include/check.inc.php");
    if (strlen($form["first_name"]) < 3) {
      $msg['err'][] = 'first_name';
    }
    if (strlen($form["last_name"]) < 3) {
      $msg['err'][] = 'last_name';
    }
    if ( strlen($form["health_card_number"]) > 0 && strlen($form["health_card_number"]) < 20 && !is_integer($form["health_card_number"])) {
      $msg['err'][] = 'health_card_number';
    }
    if (empty($form["sexper"])) {
      $msg['err'][] = 'sexper';
    }
    if (empty($form["birth_country"])) {
      $msg['err'][] = 'birth_country';
    }
    $cf_pi = new check_VATno_TAXcode();
    $r_cf = $cf_pi->check_TAXcode($form['tax_code'], $form['birth_country']);
    if (!empty($r_cf)) {
      $msg['err'][] = 'tax_code';
    } elseif($form['id_patient']==0 && !empty($form['tax_code'])) { // è corretto ma se è un inserimento allora controllo se esiste già
      $ccf=DecryptPersonalData($gTables['encrypted_personal_data'],'tax_code_bidx',$form['tax_code']);
      if(is_array($ccf) && count($ccf)>=1){ // il controllo CF ha trovato un altro paziente con lo stesso CF
        $msg['err'][] = 'tax_code_exist';
      }

    }
    if (!filter_var($form['e_mail'], FILTER_VALIDATE_EMAIL) && !empty($form['e_mail'])) {
      $msg['err'][] = 'e_mail';
    }
    if (count($msg['err']) <= 0) { // nessun errore
      // strtoupper su tutti i campi ricercabili ed indirizzo
      $form['first_name']=strtoupper(trim($form['first_name']));
      $form['last_name']=strtoupper(trim($form['last_name']));
      $form['tax_code']=strtoupper($form['tax_code']);
      $form['residence_address']=strtoupper($form['residence_address']);
      $form['residence_place']=strtoupper($form['residence_place']);
      $form['residence_postal_code']=strtoupper($form['residence_postal_code']);
      $form['residence_prov_code']=strtoupper($form['residence_prov_code']);
      // aggiorno o inserisco sul database
      require_once("./lib.data.php");
      if ($form['id_patient']>=1) { // update
        // non faccio altro che eliminare il vecchio e andrò a rimettere il nuovo sotto
        DeletePersonalData($gTables['encrypted_personal_data'],'id_patient_bidx',$form['id_patient']);
      } else { // insert
        // prendo un nuovo id_patient appoggiandomi alla colonna id_ref della tabella files dove comunque metterò almeno una immagine, anche solo un avatar
        $rs_last_id_ref = gaz_dbi_dyn_query("id_ref", $gTables['files'],"table_name_ref = 'patient_imgavatar' AND id_ref >= 1","id_ref DESC", 0, 1);
        $last_id_ref = gaz_dbi_fetch_array($rs_last_id_ref);
        if ($last_id_ref) { // ho un id_patient su files
          $form['id_patient']=$last_id_ref['id_ref']+1;
        } else { // non ho alcun id_patient
          $form['id_patient']=1;
        }
        // controllo presenza immagine
        $rs_my_img = gaz_dbi_dyn_query("id_doc", $gTables['files'],"table_name_ref = 'patient_imgavatar' AND id_ref = 0 AND adminid='".$admin_aziend['user_name']."'","id_ref DESC", 0, 1);
        $my_img = gaz_dbi_fetch_array($rs_my_img);
        if ($my_img) { // ho inserito almeno una immagine, modifico gli status con id_patient
          gaz_dbi_query("UPDATE ".$gTables['files']." SET id_ref = ".$form['id_patient']." WHERE id_doc = ".$my_img['id_doc']);
        } else { // non ho immagini, uso un avatar in base al sesso
          gaz_dbi_query("INSERT INTO ".$gTables['files']." (table_name_ref, id_ref, item_ref, content, extension, status, adminid) VALUES ('patient_imgavatar', '" .$form['id_patient']. "','avatar',TO_BASE64(AES_ENCRYPT('".bin2hex(file_get_contents(($form['sexper']=='F'?'donna':'uomo').'.png'))."','".$_SESSION['aes_key']."')), 'png',".time().", '".$_SESSION['user_name']."' )");
        }
      }
      unset($form['patient_number']);
      EncryptPersonalData($gTables['encrypted_personal_data'],$form);
      $_SESSION['id_patient']=$form['id_patient'];
      header("Location: patient_dashboard.php");
      exit;
    }
  }
  $form['ritorno'] = $_POST['ritorno'];
  $form['hidden_req'] = $_POST['hidden_req'];
} elseif (isset($_SESSION['id_patient']) && !isset($_POST['id_patient']) && !isset($_GET['new'])) { //se e' il primo accesso per UPDATE
  require_once("./lib.data.php");
  $form=DecryptPersonalData($gTables['encrypted_personal_data'],'id_patient_bidx',intval($_SESSION['id_patient']))[0];
  $form['birth_date']=gaz_format_date($form['birth_date'],1);
  $form['doc_expiry']=gaz_format_date($form['doc_expiry'],1);
  unset($_SESSION['id_patient']);
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
} else { // è il primo accesso per insert
  unset($_SESSION['id_patient']);
  $form = gaz_dbi_fields('encrypted_personal_data');
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['id_patient'] = 0;
  $form['birth_country'] = 'IT';
  $form['hidden_req'] = '';
  $region = gaz_dbi_get_row($gTables['provinces']." LEFT JOIN ".$gTables['asl']." ON ".$gTables['provinces'].".id_region = ".$gTables['asl'].".regione", 'abbreviation', $admin_aziend['REA_ufficio']);
  $form['affiliated_health_company']= $region?$region['id_asl']:0;
  // ripulisco la tabella files se ho fatto degli upload di foto o documenti senza confermare il form e quindi id_ref rimasto a 0
  gaz_dbi_del_row($gTables['files'], "table_name_ref LIKE 'patient_%' AND adminid = '".$admin_aziend['user_name']."' AND id_ref",0);
}

require("../../library/include/header.php");
$script_transl = HeadMain(0,['custom/autocomplete']);
?>
<script>
  $(function () {
    $("#birth_date,#doc_expiry").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});

    $( "#search_birth_place" ).autocomplete({
      source: "../root/search.php?opt=location",
      minLength: 2,
      html: true, // optional (jquery.ui.autocomplete.html.js required)
      focus: function( event, ui ) {
        $( "#search_birth_place" ).val( ui.item.value );
        $( "#search_birth_prov_code" ).val( ui.item.prospe );
        $( "#birth_country").val( ui.item.country );
        return false;
      },
      select: function( event, ui ) {
        $( "#search_birth_place" ).val( ui.item.value );
        $( "#search_birth_prov_code" ).val( ui.item.prospe );
        $( "#birth_country").val( ui.item.country );  //grazie ad Emanuele Ferrarini
        return false;
      }
    });
    $('#search_birth_place').blur(function() {
      if( !$(this).val() ) {
        $( "#search_birth_prov_code" ).val("");
        $( "#birth_country").val("IT");
      }
    });

    $( "#search_residence_place" ).autocomplete({
      source: "../root/search.php?opt=location",
      minLength: 2,
      html: true, // optional (jquery.ui.autocomplete.html.js required)
      focus: function( event, ui ) {
        $( "#search_residence_place" ).val( ui.item.value );
        $( "#search_residence_postal_code" ).val( ui.item.id );
        $( "#search_residence_prov_code" ).val( ui.item.prospe );
        return false;
      },
      select: function( event, ui ) {
        $( "#search_residence_place" ).val( ui.item.value );
        $( "#search_residence_postal_code" ).val( ui.item.id );
        $( "#search_residence_prov_code" ).val( ui.item.prospe );
        return false;
      }
    });
    $('#search_residence_place').blur(function() {
      if( !$(this).val() ) {
        $( "#search_residence_prov_code" ).val("");
      }
    });

    $("#dialog_del_imgdoc").dialog({ autoOpen: false });
    $('.dialog_del_imgdoc').click(function() {
      var status = $(this).attr("idref");
      var status = $(this).attr("nfile");
      var typedel = $(this).attr("typedel");
      $("p#dfile").html($(this).attr("nfile"));
      $( "#dialog_del_imgdoc" ).dialog({
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
              data: {'type':typedel,ref:status},
              type: 'POST',
              url: './delete.php',
              success: function(output){
                //alert(output);
                $("#myform" ).submit();
              }
            });
          }}
        }
      });
      $("#dialog_del_imgdoc" ).dialog( "open" );
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

<?php
if (count($msg['err']) > 0) { // ho un errore
  $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
?>

<form method="POST" name="myform" enctype="multipart/form-data" id="myform" />
<input type="hidden" name="ritorno" value="<?php echo $form['ritorno'] ?>" />
<input type="hidden" name="hidden_req" value="<?php echo $form['hidden_req'] ?>" />
<input type="hidden" name="id_patient" value="<?php echo $form['id_patient']; ?>" />
	<div class="frameDoc panel panel-success" style="display: none; position: fixed; left: 5%; top: 10px">
		<div class="col-lg-12">
			<div class="col-xs-11"><h4 id="filen"></h4></div>
			<div class="col-xs-1"><h4><button type="button" id="closeDoc"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
		</div>
		<iframe id="frameDoc" style="height: auto; width: 100%;" src="../../library/images/wait_spinner.html"></iframe>
	</div>
	<div style="display:none" id="dialog_del_imgdoc" title="Conferma eliminazione foto">
    <p><b>Documento o foto</b></p>
    <p>File: </p>
    <p class="ui-state-highlight" id="dfile"></p>
	</div>
  <div class="col-xs-4 text-right">
<?php
if ($form['id_patient']>=1) {
?>
  <button class="btn btn-md btn-info" name="medrec" type="submit" value="" title="Gestione anamnesi, esami, terapie, cartella clinica"><i class="fa fa-bars"></i> Gestione paziente</button>
<?php
}
?>

  </div>
  <h3 class="text-bold">
  <?php
  echo ($form['id_patient']>=1?$script_transl['update']:$script_transl['insert']).' '.$script_transl['title'];
  if ($form['id_patient']>=1){
    echo '<input type="hidden" name="patient_number" value="'.$form['patient_number'].'" /> <small>('.$form['patient_number'].')</small>';
  }
  ?></h3>
  <div class="panel panel-default gaz-table-form">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="first_name" class="col-sm-4 control-label"><?php echo $script_transl['first_name']; ?> *</label>
                    <input class="col-sm-8" type="text" placeholder="<?php echo $script_transl['first_name']; ?>" value="<?php echo $form['first_name']; ?>" name="first_name" maxlength="60" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="last_name" class="col-sm-4 control-label"><?php echo $script_transl['last_name']; ?> *</label>
                    <input class="col-sm-8" type="text" placeholder="<?php echo $script_transl['last_name']; ?>" value="<?php echo $form['last_name']; ?>" name="last_name" maxlength="60" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="sexper" class="col-sm-4 control-label"><?php echo $script_transl['sexper']; ?> *</label>
                    <?php
                    $gForm->variousSelect('sexper', $script_transl['sexper_value'], $form['sexper'], "col-sm-8", true, '', false);
                    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="foto" class="col-sm-4 control-label">Foto</label>
                    <div class="col-sm-8">
<?php
// riprendo il file confermato
$rdocs = gaz_dbi_dyn_query("*", $gTables['files'],"id_ref = '" .$form['id_patient']. "' AND table_name_ref = 'patient_imgavatar'", "id_doc");
$first=true;
while ($doc = gaz_dbi_fetch_array($rdocs)) {
  echo  '<div class="col-xs-12"><a class="btn btn-xs btn-default" style="cursor:pointer;" onclick="return printDoc(\'get_files_doc.php?id_img='. $doc['id_doc'].'\',\''.$doc['status'].'.'.$doc['extension'].'\')" > '.$doc['status'].'.'.$doc['extension'].' &nbsp; <i class="glyphicon glyphicon-eye-open"></i> &nbsp; </a>';
  if (!$first) {
    echo '<a style="float:right;" class="btn btn-xs btn-elimina dialog_del_imgdoc" title="Elimina la foto" idref="'. $doc['status'].'" nfile="'.$doc['status'].'.'.$doc['extension'].'" typedel="patient_img" ><i class="glyphicon glyphicon-trash"></i></a> ';
  } else {
    echo ' <button class="btn btn-xs btn-warning" type="image" data-toggle="collapse" href="#extdoc_dialog_avatar" title="Sostituisci la foto"> <i class="glyphicon glyphicon-refresh"></i></button>';
    echo '<div id="extdoc_dialog_avatar" class="collapse col-xs-12"><input style="margin-left:20%;" type="file" accept=".png,.jpg,.gif" onchange="this.form.submit();" name="avatar"></div>';
  }
    echo '<br/>&nbsp;</div>';
  $first=false;
}
echo '<div class="col-xs-12 text-center"><button class="btn btn-sm btn-info" type="image" data-toggle="collapse" href="#extdoc_dialog_othfot" style="font-size: 1em;"> <i class="glyphicon glyphicon-camera"></i> Nuova foto</button></div>';
echo '<div id="extdoc_dialog_othfot" class="collapse col-xs-12"><input style="margin-left:20%;" type="file" accept=".png,.jpg,.gif" onchange="this.form.submit();" name="imgavatar"></div>';
?>
                  </div>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="birth_date" class="col-sm-4 control-label"><?php echo $script_transl['birth_date']; ?></label>
                    <input type="text" class="col-sm-8"  id="birth_date" placeholder="<?php echo $script_transl['birth_date']; ?>" value="<?php echo $form['birth_date']; ?>" name="birth_date" maxlength="10" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="birth_place" class="col-sm-4 control-label"><?php echo $script_transl['birth_place']; ?></label>
                    <input type="text" class="col-sm-8" id="search_birth_place" placeholder="<?php echo $script_transl['birth_place']; ?>" name="birth_place" value="<?php echo $form['birth_place']; ?>" maxlength="50">
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="birth_prov_code" class="col-sm-4 control-label"><?php echo $script_transl['birth_prov_code']; ?></label>
                    <input type="text" class="col-sm-8" id="search_birth_prov_code" placeholder="<?php echo $script_transl['birth_prov_code']; ?>" value="<?php echo $form['birth_prov_code']; ?>" name="birth_prov_code" maxlength="2" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="birth_country" class="col-sm-4 control-label"><?php echo $script_transl['birth_country']; ?></label>
                    <?php
                    $gForm->selectFromDB('country','birth_country','iso',$form['birth_country'],'iso',1,' - ','name','',"col-sm-8");
                    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="tax_code" class="col-sm-4 control-label"><?php echo $script_transl['tax_code']; ?></label>
                    <input type="text" class="col-sm-8" placeholder="<?php echo $script_transl['tax_code']; ?>" value="<?php echo $form['tax_code']; ?>" name="tax_code" maxlength="16" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="iban" class="col-sm-4 control-label"><?php echo $script_transl['iban']; ?> </label>
                    <input type="text" class="col-sm-8" placeholder="<?php echo $script_transl['iban']; ?>" value="<?php echo $form['iban']; ?>" name="iban" maxlength="27" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="health_card_number" class="col-sm-4 control-label"><?php echo $script_transl['health_card_number']; ?> </label>
                    <input type="text" class="col-sm-8" placeholder="<?php echo $script_transl['health_card_number']; ?>" value="<?php echo $form['health_card_number']; ?>" name="health_card_number" maxlength="20" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="telephone" class="col-sm-4 control-label"><?php echo $script_transl['telephone']; ?></label>
                    <input type="text" class="col-sm-8" placeholder="<?php echo $script_transl['telephone']; ?>" value="<?php echo $form['telephone']; ?>" name="telephone" maxlength="50" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="residence_address" class="col-sm-4 control-label"><?php echo $script_transl['residence_address']; ?> </label>
                    <input type="text" class="col-sm-8" placeholder="<?php echo $script_transl['residence_address']; ?>" value="<?php echo $form['residence_address']; ?>" name="residence_address" maxlength="50" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="residence_place" class="col-sm-4 control-label"><?php echo $script_transl['residence_place']; ?></label>
                    <input type="text" class="col-sm-8" id="search_residence_place" placeholder="<?php echo $script_transl['residence_place']; ?>" value="<?php echo $form['residence_place']; ?>" name="residence_place" maxlength="50" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="residence_postal_code" class="col-sm-4 control-label"><?php echo $script_transl['residence_postal_code']; ?> </label>
                    <input type="text" class="col-sm-8" id="search_residence_postal_code" placeholder="<?php echo $script_transl['residence_postal_code']; ?>" value="<?php echo $form['residence_postal_code']; ?>" name="residence_postal_code" maxlength="5" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="residence_prov_code" class="col-sm-4 control-label"><?php echo $script_transl['residence_prov_code']; ?></label>
                    <input type="text" class="col-sm-8" id="search_residence_prov_code" placeholder="<?php echo $script_transl['residence_prov_code']; ?>" value="<?php echo $form['residence_prov_code']; ?>" name="residence_prov_code" maxlength="2" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="affiliated_health_company" class="col-sm-4 control-label"><?php echo $script_transl['affiliated_health_company']; ?> </label>
                    <?php
                    $gForm->selectAsl($gTables,'affiliated_health_company',$form['affiliated_health_company'],false,'col-sm-8');
                    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="marital_status" class="col-sm-4 control-label"><?php echo $script_transl['marital_status']; ?> </label>
                    <?php
                    $gForm->variousSelect('marital_status', $script_transl['marital_status_value'], $form['marital_status'], "col-sm-8", false, '', false);
                    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="e_mail" class="col-sm-4 control-label"><?php echo $script_transl['e_mail']; ?></label>
                    <input type="text" class="col-sm-8" placeholder="<?php echo $script_transl['e_mail']; ?>" value="<?php echo $form['e_mail']; ?>" name="e_mail" maxlength="50" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="codpag" class="col-sm-4 control-label">Documenti</label>
                    <div class="col-sm-8">
<?php
// riprendo il file confermato
$rdocs = gaz_dbi_dyn_query("*", $gTables['files'],"id_ref = '" .$form['id_patient']. "' AND table_name_ref = 'patient_doc'", "id_doc");
while ($doc = gaz_dbi_fetch_array($rdocs)) {
  echo  '<div class="col-xs-12"><a class="btn btn-xs btn-default" style="cursor:pointer;" onclick="return printDoc(\'get_files_doc.php?id_doc='. $doc['id_doc'].'\',\''.$doc['status'].'.'.$doc['extension'].'\')" > '.$doc['status'].'.'.$doc['extension'].' &nbsp; <i class="glyphicon glyphicon-eye-open"></i> &nbsp; </a>';
    echo '<a style="float:right;" class="btn btn-xs btn-elimina dialog_del_imgdoc" title="Elimina il documento" idref="'. $doc['status'].'" nfile="'.$doc['status'].'.'.$doc['extension'].'" typedel="patient_doc" ><i class="glyphicon glyphicon-trash"></i></a> ';
  echo '<br/>&nbsp;</div>';
}
echo '<div class="col-xs-12 text-center"><button class="btn btn-sm btn-info" type="image" data-toggle="collapse" href="#extdoc_dialog_doc" style="font-size: 1em;"> <i class="fa fa-file-pdf-o"></i> Nuovo documento</button></div>';
echo '<div id="extdoc_dialog_doc" class="collapse col-xs-12"><input style="margin-left:20%;" type="file" accept=".png,.jpg,.pdf" onchange="this.form.submit();" name="patient_doc"></div>';
?>
                  </div>
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="doc_expiry" class="col-sm-4 control-label"><?php echo $script_transl['doc_expiry']; ?></label>
                    <input type="text" class="col-sm-8" id="doc_expiry" placeholder="<?php echo $script_transl['doc_expiry']; ?>" value="<?php echo $form['doc_expiry']; ?>" name="doc_expiry" maxlength="10" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="note" class="col-sm-4 control-label"><?php echo $script_transl['note']; ?></label>
                    <input type="text" class="col-sm-8" placeholder="<?php echo $script_transl['note']; ?>" value="<?php echo $form['note']; ?>" name="note" maxlength="1000" />
                </div>
            </div>
        </div><!-- chiude row  -->
        <div class="row">
            <div class="col-md-12 FacetFooterTD">
                <div class="form-group text-center">
                    <input class="btn btn-warning" name="Submit" type="submit" value="<?php echo ucfirst($form['id_patient']>=1?$script_transl['update']:$script_transl['insert']); ?>">
                </div>
            </div>
        </div><!-- chiude row  -->
    </div> <!-- chiude container -->
</div><!-- chiude panel -->
</form>
<?php
require("../../library/include/footer.php");
?>
