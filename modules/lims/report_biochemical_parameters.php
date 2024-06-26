<?php
/*
 --------------------------------------------------------------------------
    Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
         (http://www.devincentiis.it)
 --------------------------------------------------------------------------
*/
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
$msg = "";
require("../../library/include/header.php");
$script_transl = HeadMain();
require("lang.".$admin_aziend['lang'].".php");
// campi ammissibili per la ricerca
$search_fields = [
  'param' => "Parametro LIKE '%s%%'",

];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
  "ID" => 'id',
  'Settore' => 'Settore',
  'Parte' => 'Parte',
  'Parametro' => 'Parametro',
  'Val.Minimo' => '',
  'Val.Massimo' => '',
  'Unita di misura' => '',
  'Note' => '',
  'RiferimentoNormativo' => 'RiferimentoNormativo',
  'Metodo' => '',
);

echo "<div align='center' class='FacetFormHeaderFont '>Parametri biochimici, riferimenti normativi, metodi di analisi</div>\n";

$ts = new TableSorter(
    $gTables['biochemical_parameters'],
    $passo,
    ['Settore' => 'asc','Parte' => 'asc','ordine'=>'asc','id'=>'asc']);
$where_select = "1";

$ts->output_navbar();

?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("idref"));
		$("p#iddescri").html($(this).attr("pardes"));
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
					'class':'btn btn-danger',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'parameters',ref:idref},
						type: 'POST',
						url: './delete.php',
						success: function(output){
							window.location.replace("./report_biochemical_parameters.php");
						}
					});
				}},
   			close: {
					text:'Non eliminare',
					'class':'btn btn-default',
          click:function() {
            $(this).dialog("close");
          }
        }
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<form method="GET" class="clean_get">
	<input type="hidden" name="flt_tipo" value="none" />
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
		<p><b>Trasferimento magazzino:</b></p>
		<p>Codice</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
	<div class="table-responsive">
	<table class="Tlarge table table-bordered table-condensed">
	<tr>
		<td class="FacetFieldCaptionTD" colspan=3></td>
		<td class="FacetFieldCaptionTD">
		  <input type="text" name="param" id="param" placeholder="Parametro" class="input-sm form-control"  value="<?php echo (isset($param))? $param : ""; ?>" maxlength ="12">
    </td>
		<td class="FacetFieldCaptionTD" colspan="6">
			<input type="submit" class="btn btn-xs btn-default" name="search" value="<?php echo $script_transl['search'];?>" tabindex="1" onClick="javascript:document.report.all.value=1;">
			<a class="btn btn-xs btn-default" href="?">Reset</a>
			<?php  $ts->output_order_form(); ?>
		</td>
	</tr>

<?php
$result = gaz_dbi_dyn_query("*",$gTables['biochemical_parameters'], $ts->where, $ts->orderby, $ts->getOffset(), $ts->getLimit());

echo '<tr>';
$ts->output_headers();
echo '</tr>';


while ($r = gaz_dbi_fetch_array($result)) {
  echo '<tr>';
  echo "<td>".$r["id"]."</td>";
  echo '<td><small>'.$r['Settore']."</small></td>";
  echo '<td>'.$r['Parte']."</td>";
  echo "<td>".$r["Parametro"]."</td>";
  echo '<td align="center">'.$r['ValMinimoParametro']."</td>";
  echo '<td align="center">'.$r['ValMassimoParametro']."</td>";
  echo '<td align="center">'.$r['UnitaMisura']."</td>";
  echo '<td><small>'.$r['Note']."</small></td>";
  echo '<td><small>'.$r['RiferimentoNormativo']."</small></td>";
  echo '<td><small>'.$r['Metodo']."</small></td>";
  echo "</tr>\n";
}
?>
    </table>
	</div>
</form>

<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>
