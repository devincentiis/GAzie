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
$admin_aziend=checkAdmin(8);
require_once("./lib.data.php");
if (isset($_POST['hidden_req'])) {   //se e' avvenuta una scelta
  $_SESSION['id_patient']=$_POST['hidden_req'];
  header("Location: patient_dashboard.php");
  exit;
} elseif(isset($_SESSION['id_patient'])) {
  unset($_SESSION['id_patient']);
}
require("../../library/include/header.php");
$script_transl = HeadMain(0,['custom/autocomplete']);
?>
<script>
$(function() {
	$( "#search_first_name" ).autocomplete({
		source: "./search.php?opt=first_name",
		minLength: 3,
    html: true, // optional (jquery.ui.autocomplete.html.js required)
    // optional (if other layers overlap autocomplete list)
    open: function(event, ui) {
      $(".ui-autocomplete").css("z-index", 1000);
    },
		select: function(event, ui) {
			$("#hidden_req").val(ui.item.id_patient);
      $("#myform").submit();
		}
	});
	$( "#search_last_name" ).autocomplete({
		source: "./search.php?opt=last_name",
		minLength: 3,
    html: true, // optional (jquery.ui.autocomplete.html.js required)
    // optional (if other layers overlap autocomplete list)
    open: function(event, ui) {
      $(".ui-autocomplete").css("z-index", 1000);
    },
		select: function(event, ui) {
			$("#hidden_req").val(ui.item.id_patient);
      $("#myform").submit();
		}
	});
	$( "#search_tax_code" ).autocomplete({
		source: "./search.php?opt=tax_code",
		minLength: 3,
    html: true, // optional (jquery.ui.autocomplete.html.js required)
    // optional (if other layers overlap autocomplete list)
    open: function(event, ui) {
      $(".ui-autocomplete").css("z-index", 1000);
    },
		select: function(event, ui) {
			$("#hidden_req").val(ui.item.id_patient);
      $("#myform").submit();
		}
	});
	$( "#search_health_card_number" ).autocomplete({
		source: "./search.php?opt=health_card_number",
		minLength: 3,
    html: true, // optional (jquery.ui.autocomplete.html.js required)
    // optional (if other layers overlap autocomplete list)
    open: function(event, ui) {
      $(".ui-autocomplete").css("z-index", 1000);
    },
		select: function(event, ui) {
			$("#hidden_req").val(ui.item.id_patient);
      $("#myform").submit();
		}
	});
});
</script>
<form method="POST" name="myform" enctype="multipart/form-data" id="myform" />
<input type="hidden" name="hidden_req" id="hidden_req"  value="" />
<div class="FacetFormHeaderFont text-center"><?php echo $script_transl['title']; ?></div>
<div class="panel panel-default gaz-table-form">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="first_name" class="col-sm-4 control-label text-right"><?php echo $script_transl['first_name']; ?> </label>
          <input class="col-sm-8" type="text" placeholder="<?php echo $script_transl['first_name']; ?>" value="" name="first_name" id="search_first_name" maxlength="60" />
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="last_name" class="col-sm-4 control-label text-right"><?php echo $script_transl['last_name']; ?></label>
          <input class="col-sm-8" type="text" placeholder="<?php echo $script_transl['last_name']; ?>" value="" name="last_name" id="search_last_name" maxlength="60" />
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="tax_code" class="col-sm-4 control-label text-right"><?php echo $script_transl['tax_code']; ?></label>
          <input type="text" class="col-sm-8" placeholder="<?php echo $script_transl['tax_code']; ?>" value="" name="tax_code" id="search_tax_code" maxlength="16" />
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="health_card_number" class="col-sm-4 control-label text-right"><?php echo $script_transl['health_card_number']; ?> </label>
          <input type="text" class="col-sm-8" placeholder="<?php echo $script_transl['health_card_number']; ?>" value="" name="health_card_number" id="search_health_card_number" maxlength="20" />
        </div>
      </div>
    </div><!-- chiude row  -->
  </div>
</div>
</form>
<?php
// divido la password in 2, e la intercalerò nella data di stampa
//$split=str_split($d,ceil(strlen($d)/2));
//var_dump($split);

//$dr=DecryptPersonalData($gTables['encrypted_personal_data'],'id_patient_bidx','1');
//var_dump($dr);
$values =['id_patient'=>'IDperCaso','first_name'=>'acCaso','last_name'=>'acCasuygfuyftyftcf tyfiytdftd trd rd utdrtrdrt6gd&_-.o','birth_date'=>'acCaso','birth_place'=>'acCaso','birth_country'=>'acCaso','doc_expiry'=>'2024-12-12'];
//EncryptPersonalData($gTables['encrypted_personal_data'],$values);
?>
</div>
<?php
require("../../library/include/footer.php");
?>
