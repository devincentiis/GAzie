<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (http://www.programmisitiweb.lacasettabio.it)

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
require("../../modules/magazz/lib.function.php");
$admin_aziend=checkAdmin();
require("../../library/include/header.php");
// campi ammissibili per la ricerca
$search_fields = [
    'sea_codice' => "{$gTables['artico_group']}.id_artico_group LIKE '%%%s%%'",
	'des_artico' => "{$gTables['artico_group']}.descri LIKE '%%%s%%'",
];
// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
            "Id" => 'id_artico_group',
            "Descrizione"=>'descri',
			"Disponibilità" => '',
            'Elimina' => ''
);
$tablejoin = $gTables['artico_group'];

$ts = new TableSorter(
    $tablejoin,
    $passo,
    ['last_modified'=>'desc'],
    ['descri' => 'descri']);
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
						data: {'type':'facility',ref:id},
						type: 'POST',
						url: '../vacation_rental/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_facility.php");
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
		$('.framePdf').css({'display': 'none'});
	});
function openframe(url,codice){
	$(function(){
		$("#titolo").append(codice);
		$('#framePdf').attr('src',url);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
    $("html, body").delay(100).animate({scrollTop: $('#framePdf').offset().top},200, function() {
        $("#framePdf").focus();
    });
	});
	$('#closePdf').on( "click", function() {
		$("#titolo").empty();
		$('.framePdf').css({'display': 'none'});
	});
};

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
        <p><b>Struttura:</b></p>
        <p>codice:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
	<div class="framePdf panel panel-success" style="display: none; position: fixed; left: 5%; top: 10px">
			<div class="col-lg-12">
				<h4><div class="col-xs-11" id="titolo" ></div></h4>
				<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
			</div>
			<iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
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
			<input type="text" name="sea_codice" placeholder="codice" id="suggest_codice_artico" class="input-sm form-control" value="<?php echo (isset($sea_codice))? htmlentities($sea_codice, ENT_QUOTES) : ""; ?>" maxlength="15">
		</td>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="des_artico" placeholder="descrizione"  id="suggest_descri_artico" class="input-sm form-control" value="<?php echo (isset($des_artico))? htmlentities($des_artico, ENT_QUOTES) : ""; ?>" maxlength="30">
        </td>


		<td class="FacetFieldCaptionTD"></td>
		<td class="FacetFieldCaptionTD" colspan="7">
			<input type="submit" class="btn btn-sm btn-default" name="search" value="<?php echo $script_transl['search'];?>" onClick="javascript:document.report.all.value=1;">
			<a class="btn btn-sm btn-default" href="?">Reset</a>
			<?php  $ts->output_order_form(); ?>
		</td>
	</tr>

<?php
$gForm = new magazzForm();

$result = gaz_dbi_dyn_query ( "*", $gTables['artico_group'], $ts->where , $ts->orderby, $ts->getOffset(), $ts->getLimit());

echo '<tr>';
$ts->output_headers();
echo '</tr>';
while ($r = gaz_dbi_fetch_array($result)) {
	// escludo dal report se non sono alloggi
	if ($data = json_decode($r['custom_field'], TRUE)){  // se esiste un json nel custom field

		if (is_array($data['vacation_rental'])){// se è un alloggio lo mostro nel report

			switch ($r['web_public']) {// 1=attivo su web; 2=attivo e prestabilito; 3=attivo e pubblicato in home; 4=attivo, in home e prestabilito; 5=disattivato su web
				case "0":
					$ecomGlobe="";
					break;
				case "1":
					$ecomGlobe="class='glyphicon glyphicon-globe' style='color:rgba(26, 209, 44);' title='Attivato su e-commerce'";
					break;
				case "2":
					$ecomGlobe="class='glyphicon glyphicon-globe' style='color:rgba(255, 203, 71);' title='Attivato e prestabilito su e-commerce'";
					break;
				case "3":
					$ecomGlobe="class='glyphicon glyphicon-globe' style='color:rgba(255, 99, 71);' title='Attivato e in home su e-commerce'";
					break;
				case "4":
					$ecomGlobe="class='glyphicon glyphicon-globe' style='color:red;' title='Attivato, prestabilito e in home su e-commerce'";
					break;
				case "5":
					$ecomGlobe="class='glyphicon glyphicon-globe' title='Disattivato su e-commerce'";
					break;
			}
			echo "<tr>\n";
			echo '<td>
			<a class="btn btn-xs btn" href="../vacation_rental/admin_facility.php?Update&id_artico_group='.$r['id_artico_group'].'" ><i class="glyphicon glyphicon-edit"></i> '.$r['id_artico_group'].'</a>';

			echo "<i ".$ecomGlobe." ></i>";// globo per e-commerce
			echo '</td>';
			echo '<td><span class="gazie-tooltip" data-type="group-thumb" data-id="'. $r['id_artico_group'] .'" data-title="" >'.$r['descri'].'</span>';
			echo "</td>\n";
			echo '<td class="text-center"><a class="btn btn-xs btn-default" style="cursor:pointer;" onclick="openframe(\'facility_availability.php?code='.$r["id_artico_group"].'\',\'Calendario disponibilità della struttura: '.$r["descri"].'. Le caselle bianche indicano tutto disponibile.\')" data-toggle="modal" data-target="#iframe"> <i class="glyphicon glyphicon-calendar" title="Calendario della disponibilità"></i></a>';
			echo "</td>\n";
			echo '<td class="text-center"><a class="btn btn-xs  btn-elimina dialog_delete" ref="'. $r['id_artico_group'].'" artico="'. $r['descri'].'"> <i class="glyphicon glyphicon-trash"></i></a>';
			echo "</td>\n";
			echo "</tr>\n";
		}
	}
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
