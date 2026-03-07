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

if (isset($_POST['id_patient']) && $_POST['id_patient'] > 0 ) {   //se e' avvenuta una scelta
  $_SESSION['id_patient']=$_POST['id_patient'];
  echo '<script> window.location = "../hospital/patient_dashboard.php"; </script>';
  exit;
} elseif(isset($_SESSION['id_patient'])) {
  unset($_SESSION['id_patient']);
}

require("../hospital/lang." . $admin_aziend['lang'] . ".php");
$transl_hospital=$strScript['select_patient.php'];
?>

<script src="../../js/custom/autocomplete.js"></script>
<div class="panel panel-info col-xs-12">
	<div class="box-header company-color">
		<a class="pull-right dialog_grid" id_bread="<?php echo $grr['id_bread']; ?>" style="cursor:pointer;"><i class="glyphicon glyphicon-cog"></i></a>
		<div class="box-body">
			<div class="text-left col-xs-6"><a class="btn btn-md btn-warning" href="../hospital/admin_patient.php?new">Nuovo paziente <i class="glyphicon glyphicon-pencil"></i></a></div>
		</div>
		<div class="box-title"><input type="hidden" id="delete_fep" name="delete_fep" /><div id="confirmdelfep" title="Conferma cancellazione"></div>
			<b><?php echo $transl_hospital['title']; ?>: </b>
		</div>
  </div>
  <div id="accorarticoDiv1" class="collapse-div" role="tablist">
    <input type="hidden" name="id_patient" id="id_patient"  value="" />
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <label for="first_name" class="col-sm-4 control-label text-right"><?php echo $transl_hospital['first_name']; ?> </label>
              <input class="col-sm-8" type="text" placeholder="<?php echo $transl_hospital['first_name']; ?>" value="" name="first_name" id="search_first_name" maxlength="60" />
            </div>
          </div>
        </div><!-- chiude row  -->
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <label for="last_name" class="col-sm-4 control-label text-right"><?php echo $transl_hospital['last_name']; ?></label>
              <input class="col-sm-8" type="text" placeholder="<?php echo $transl_hospital['last_name']; ?>" value="" name="last_name" id="search_last_name" maxlength="60" />
            </div>
          </div>
        </div><!-- chiude row  -->
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <label for="tax_code" class="col-sm-4 control-label text-right"><?php echo $transl_hospital['tax_code']; ?></label>
              <input type="text" class="col-sm-8" placeholder="<?php echo $transl_hospital['tax_code']; ?>" value="" name="tax_code" id="search_tax_code" maxlength="16" />
            </div>
          </div>
        </div><!-- chiude row  -->
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <label for="health_card_number" class="col-sm-4 control-label text-right"><?php echo $transl_hospital['health_card_number']; ?> </label>
              <input type="text" class="col-sm-8" placeholder="<?php echo $transl_hospital['health_card_number']; ?>" value="" name="health_card_number" id="search_health_card_number" maxlength="20" />
            </div>
          </div>
        </div><!-- chiude row  -->
      </div>
	</div> <!-- chiude accorarticoDiv1  -->
</div>

<script>
$(function() {
	$( "#search_first_name" ).autocomplete({
		source: "../hospital/search.php?opt=first_name",
		minLength: 3,
    html: true, // optional (jquery.ui.autocomplete.html.js required)
    // optional (if other layers overlap autocomplete list)
    open: function(event, ui) {
      $(".ui-autocomplete").css("z-index", 1000);
    },
		select: function(event, ui) {
			$("#id_patient").val(ui.item.id_patient);
      $('form[name="gaz_form"]').submit();
		}
	});
	$( "#search_last_name" ).autocomplete({
		source: "../hospital/search.php?opt=last_name",
		minLength: 3,
    html: true, // optional (jquery.ui.autocomplete.html.js required)
    // optional (if other layers overlap autocomplete list)
    open: function(event, ui) {
      $(".ui-autocomplete").css("z-index", 1000);
    },
		select: function(event, ui) {
			$("#id_patient").val(ui.item.id_patient);
      $('form[name="gaz_form"]').submit();
		}
	});
	$( "#search_tax_code" ).autocomplete({
		source: "../hospital/search.php?opt=tax_code",
		minLength: 3,
    html: true, // optional (jquery.ui.autocomplete.html.js required)
    // optional (if other layers overlap autocomplete list)
    open: function(event, ui) {
      $(".ui-autocomplete").css("z-index", 1000);
    },
		select: function(event, ui) {
			$("#id_patient").val(ui.item.id_patient);
      $('form[name="gaz_form"]').submit();
		}
	});
	$( "#search_health_card_number" ).autocomplete({
		source: "../hospital/search.php?opt=health_card_number",
		minLength: 3,
    html: true, // optional (jquery.ui.autocomplete.html.js required)
    // optional (if other layers overlap autocomplete list)
    open: function(event, ui) {
      $(".ui-autocomplete").css("z-index", 1000);
    },
		select: function(event, ui) {
			$("#id_patient").val(ui.item.id_patient);
      $('form[name="gaz_form"]').submit();
		}
	});
});
</script>
