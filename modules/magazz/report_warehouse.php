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
$admin_aziend=checkAdmin();
require("../../library/include/header.php");
$script_transl = HeadMain('', '', 'admin_warehouse');

// campi ammissibili per la ricerca
$search_fields = [
    'id' => "id = %d",
    'name'=> "name LIKE '%%%s%%'",
    'note_other'=> "note_other LIKE '%%%s%%'"
];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
            "ID" => 'id',
            $script_transl['name'] => 'name',
            $script_transl['image'] => 'image',
            $script_transl['web_url'] => 'web_url',
            $script_transl['note_other'] => 'note_other',
            $script_transl['print'] => "",
            $script_transl['delete'] => ""
);

echo "<div align='center' class='FacetFormHeaderFont '>{$script_transl['title']}</div>\n";
$table = $gTables['warehouse'];

$t = new TableSorter(
    $table,
    $passo,
    ['id' => 'desc']);
$t->output_navbar();

?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("warehouse"));
		var id = $(this).attr('ref');
		$( "#dialog_delete" ).dialog({
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
						data: {'type':'warehouse',ref:id},
						type: 'POST',
						url: '../magazz/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_warehouse.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<form method="GET" class="clean_get">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
		<p><b>ID</b></p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Nome</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
	<div class="table-responsive">
	<table class="Tlarge table table-striped table-bordered table-condensed">
	<tr>
        <td class="FacetFieldCaptionTD">
            <?php gaz_flt_disp_int("id", "ID"); ?>
        </td>
        <td class="FacetFieldCaptionTD">
            <?php gaz_flt_disp_int("name", $script_transl['name']); ?>
        </td>
        <td class="FacetFieldCaptionTD">
        </td>
        <td class="FacetFieldCaptionTD">
        </td>
        <td class="FacetFieldCaptionTD">
            <?php gaz_flt_disp_int("note_other", $script_transl['note_other']); ?>
        </td>
		<td class="FacetFieldCaptionTD" colspan="3">
			<input type="submit" class="btn btn-xs btn-default" name="search" value="<?php echo $script_transl['search'];?>" tabindex="1" onClick="javascript:document.report.all.value=1;">
			<a class="btn btn-xs btn-default" href="?">Reset</a>
			<?php  $t->output_order_form(); ?>
		</td>
	</tr>

<?php
$today = strtotime(date("Y-m-d"));
$rs=gaz_dbi_dyn_query ("*", $table, $t->where, $t->orderby, $t->getOffset(), $t->getLimit());

echo '<tr>';
$t->output_headers();
echo '</tr>';
while ($r = gaz_dbi_fetch_array($rs)) {
  $rs_numw=gaz_dbi_dyn_query ("COUNT(*) AS moved", $gTables['movmag'], 'id_warehouse='.$r['id'],'id_mov',0,1);
  $moved=gaz_dbi_fetch_array($rs_numw)['moved'];
  $rs_nums=gaz_dbi_dyn_query ("COUNT(*) AS moved", $gTables['shelves'], 'id_warehouse='.$r['id'],'id_shelf',0,1);
  $yshel=gaz_dbi_fetch_array($rs_nums)['moved'];
  $rs_nump=gaz_dbi_dyn_query ("COUNT(*) AS moved", $gTables['artico_position'], 'id_warehouse='.$r['id'],'id_position',0,1);
  $yposi=gaz_dbi_fetch_array($rs_nump)['moved'];
?>
<tr>
 <td class="text-center"><a class="btn btn-xs btn-edit" href="admin_warehouse.php?Update&id=<?php echo $r["id"]; ?>"><i class="glyphicon glyphicon-edit"></i>&nbsp;<?php echo $r["id"];?></a></td>
 <td><?php echo $r["name"]; ?></td>
 <td align="center"> <img width="100" style="cursor: zoom-in;" <?php echo 'src="data:image/jpeg;base64,'.base64_encode( $r['image'] ).'"';?> onclick="this.width=500;" ondblclick="this.width=100;" title="click=zoom doubleclick=thumb" alt="no image" /></td>
 <td><?php echo $r["web_url"]; ?></td>
 <td><?php echo $r["note_other"]; ?></td>
 <td></td>
 <td class="text-center">
<?php
  if ($moved>=1){
?>
 <a class="btn btn-xs  btn-elimina dialog_delete" title="Magazzino non eliminabile perché movimentato" disabled ><i class="glyphicon glyphicon-trash"></i></a>
<?php
  } elseif ($yshel>=1){
?>
 <a class="btn btn-xs  btn-elimina dialog_delete" title="Magazzino non eliminabile perché contenente uno scaffale" disabled ><i class="glyphicon glyphicon-trash"></i></a>
<?php
  } elseif ($yposi>=1){
?>
 <a class="btn btn-xs  btn-elimina dialog_delete" title="Magazzino non eliminabile perché contenente una posizione" disabled ><i class="glyphicon glyphicon-trash"></i></a>
<?php
  } else {
?>
 <a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $r['id'];?>" warehouse="<?php echo $r['name'];?>"><i class="glyphicon glyphicon-trash"></i></a>
<?php
  }
?>
</td></tr>
<?php
}
?>
     </table>
	</div>
</form>

<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>
