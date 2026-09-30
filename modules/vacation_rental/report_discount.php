<?php
/*
    --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (https://www.programmisitiweb.lacasettabio.it)

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
require("../../modules/magazz/lib.function.php");
$admin_aziend=checkAdmin();
require("../../library/include/header.php");
// campi ammissibili per la ricerca
$search_fields = [
    'sea_codice' => "{$gTables['rental_discounts']}.title LIKE '%%%s%%'",
	'des_artico' => "{$gTables['rental_discounts']}.description LIKE '%%%s%%'",
  'voucher' => "{$gTables['rental_discounts']}.discount_voucher_code LIKE '%%%s%%'",

];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
  "ID" => '',
  "Titolo" => 'title',
  "Descrizione"=>'description',
  "ID struttura" => 'facility_id',
  "Codice alloggio" => 'accommodation_code',
  "Tipo di sconto" => '',
  "Valido a partire da"=>'',
  "Valido fino a"=>'',
  'Valore' => '',
  'Codice buono' => 'discount_voucher_code',
  'Riservato' => '',
  'Notti minime' =>'',
  'Last min' =>'',
  'Priorità' =>'',
  'Blocca successivi' =>'',
  'Stato' =>'',
  'Cancella' => ''
);
$tablejoin = $gTables['rental_discounts'];

$ts = new TableSorter(
    $tablejoin,
    $passo,
    ['valid_from'=>'desc']);
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("artico"));
		var id = $(this).attr('ref');
		$( "#dialog_delete" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'Elimina',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'discount',ref:id},
						type: 'POST',
						url: '../vacation_rental/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_discount.php");
						}
					});
				}},
				"Non eliminare": function() {
					$(this).dialog("close");
				}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});

	$( "#suggest_codice_artico" ).autocomplete({
		source: "../../modules/root/search.php?opt=suggest_codice_artico",
		minLength: 3,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#suggest_codice_artico").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});

});

$('#closePdf').on( "click", function() {
		$('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
	});
function openframe(url){
	$(function(){
		$('#framePdf').attr('src',url);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
    $("html, body").delay(100).animate({scrollTop: $('#framePdf').offset().top},200, function() {
        $("#framePdf").focus();
    });
	});
	$('#closePdf').on( "click", function() {
		$('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
	});
};


function Copy() {
	 /* Get the text field */
  var copyText = document.getElementById("copy");

  /* Select the text field */
  copyText.select();
  copyText.setSelectionRange(0, 99999); /* For mobile devices */

   /* Copy the text inside the text field */
  navigator.clipboard.writeText(copyText.value);

  /* Alert the copied text */
  //alert("Copied the text: " + copyText.value);
}

</script>
<?php
$script_transl = HeadMain(0, array('custom/autocomplete'));
?>
<div class="text-center"><h3><?php echo $script_transl['title'];?></h3></div>
<?php
$ts->output_navbar();

?>
<form method="GET" class="clean_get">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>SCONTO:</b></p>
        <p>ID:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Titolo</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>

	<div style="display:none; min-width:150px; " id="dialog_orders" title="">
		<p class="ui-state-highlight" id="idartico"></p>
		<div class="list_orders">
		</div>
	</div>
	<div style="display:none; min-width:350px; " id="dialog_group" title="">
		<p class="ui-state-highlight" id="idgroup"></p>
		<div class="list_group">
		</div>
		<p class="ui-state-highlight" id="idvar"></p>
		<div class="list_variants">
		</div>
	</div>
	<div class="table-responsive">
	<table class="Tlarge table table-striped table-bordered table-condensed">
	<tr>
		<td class="FacetFieldCaptionTD">
      <!--
        <input type="text" name="sea_codice" placeholder="titolo" id="suggest_codice_artico" class="input-sm form-control" value="<?php echo (isset($sea_codice))? htmlentities($sea_codice, ENT_QUOTES) : ""; ?>" maxlength="15">
      -->
    </td>
		<td class="FacetFieldCaptionTD">
			<!--
      <input type="text" name="des_artico" placeholder="descrizione"  id="suggest_descri_artico" class="input-sm form-control" value="<?php echo (isset($des_artico))? htmlentities($des_artico, ENT_QUOTES) : ""; ?>" maxlength="30">
      -->
    </td>
		<td class="FacetFieldCaptionTD">
      <!--
      <input type="text" name="des_artico" placeholder="voucher"  id="suggest_descri_artico" class="input-sm form-control" value="<?php echo (isset($voucher))? htmlentities($voucher, ENT_QUOTES) : ""; ?>" maxlength="30">
      -->
    </td>
		<td class="FacetFieldCaptionTD">
    </td>
		<td class="FacetFieldCaptionTD">
      <?php //gaz_flt_disp_select("codcat", $gTables['catmer'].".codice AS codcat, ". $gTables['catmer'].".descri AS descat", $tablejoin, 1,'codcat ASC','descat'); ?>
    </td>

		<td class="FacetFieldCaptionTD"></td>
		<td class="FacetFieldCaptionTD" colspan="7">
			<input type="submit" class="btn btn-sm btn-default" name="search" value="<?php echo $script_transl['search'];?>" onClick="javascript:document.report.all.value=1;">
			<a class="btn btn-sm btn-default" href="?">Reset</a>
			<?php  $ts->output_order_form(); ?>
		</td>
    <td class="FacetFieldCaptionTD">
    </td>
    <td class="FacetFieldCaptionTD">
    </td>
    <td class="FacetFieldCaptionTD">
    </td>
	</tr>

<?php
$gForm = new magazzForm();

$result = gaz_dbi_dyn_query ( $gTables['rental_discounts']. ".* ",$tablejoin, $ts->where."", $ts->orderby, $ts->getOffset(), $ts->getLimit());
echo '<tr>';
$ts->output_headers();
echo '</tr>';
while ($r = gaz_dbi_fetch_array($result)) {
  if (strlen($r['discount_voucher_code'])>1){
    $type="Buono sconto";
  }else{
    $type="Sconto";
  }
  if (intval($r['is_percent'])==1){
    $type.=" in percentuale";
    $symb="%";
  }else{
    $type.=" a valore";
    $symb=$admin_aziend['symbol'];
  }
  ?>
  <tr>
    <td class="text-center">
    <a class="btn btn-xs btn-success" href="../vacation_rental/admin_discount.php?Update&id=<?php echo $r['id'];?>" ><i class="glyphicon glyphicon-edit"></i><?php echo $r['id'];?></a>

    </td>
    <td class="text-center"><?php echo $r['title'];?>
    </td>
    <td class="text-center"><?php echo $r['description'];?>
    </td>
    <td class="text-center"><?php echo $r['facility_id'];?>
    </td>
    <td class="text-center"><?php echo $r['accommodation_code'];?>
    </td>
		<td class="text-center"><?php echo $type;?>
    </td>
    <td class="text-center"><?php echo $r['valid_from'];?>
    </td>
    <td class="text-center"><?php echo $r['valid_to'];?>
    </td>
    <td class="text-center"><?php echo $r['value']," ",$symb;?>
    </td>
		<td class="text-center"><?php echo $r['discount_voucher_code'];?>
    </td>
		<td class="text-center"><?php echo $r['id_anagra'];?>
    </td>
    <td class="text-center"><?php echo $r['min_stay'];?>
    </td>
    <td class="text-center"><?php echo $r['last_min'];?>
    </td>
    <td class="text-center"><?php echo $r['priority'];?>
    </td>
    <td class="text-center"><?php echo $r['stop_further_processing'];?>
    </td>
    <td class="text-center"><?php echo $r['STATUS'];?>
    </td>
    <td class="text-center"><a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $r['id']; ?>" artico="<?php echo $r['title']; ?>"> <i class="glyphicon glyphicon-trash"></i></a>
		</td>
  </tr>
  <?php
}
?>
   </table>
	</div>
</form>
<a href="https://programmisitiweb.lacasettabio.it/gazie/vacation-rental-il-gestionale-per-case-vacanza-residence-bb-e-agriturismi/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:20%; z-index:2000;"> Vacation rental è un modulo di Antonio Germani</a>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>
