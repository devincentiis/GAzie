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
require("../../library/include/header.php");
$script_transl = HeadMain();

// campi ammissibili per la ricerca
$search_fields = [
    'id_doc' => $gTables['files'].".id_doc = %d",
    'tipeff'=> "tipeff LIKE '%s'",
    'anno' => "YEAR(".$gTables['files'].".last_modified) = %d",
    'codbanacc' => $gTables['clfoco'].".codice = %d",
];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
            "ID" => 'id_doc',
            $script_transl['date'] => 'last_modified',
            $script_transl['desbanacc'] => 'banacc',
            'File'=>'title',
            'Info'=>'neff',
            $script_transl['print'] => "",
            $script_transl['delete'] => ""
);

echo "<div align='center' class='FacetFormHeaderFont '>{$script_transl['title']}</div>\n";
$table = $gTables['effett']." LEFT JOIN ".$gTables['files']." ON (".$gTables['effett'].".id_distinta = ".$gTables['files'].".id_doc)
		 LEFT JOIN ".$gTables['clfoco']." ON (".$gTables['effett'].".banacc = ".$gTables['clfoco'].".codice)";

$t = new TableSorter(
    $table,
    $passo,
    ['id_doc' => 'desc'],
    ['item_ref'=>'distinta'],
    ['id_distinta'],
    " table_name_ref='effett' AND id_ref > 0");
$t->output_navbar();

$rs=gaz_dbi_dyn_query ($gTables['clfoco'].".descri AS desbanacc, ".$gTables['clfoco'].".codice AS codbanacc", $table, $t->where, "codbanacc", $t->getOffset(), $t->getLimit(), "codbanacc");
$optval='';
while($r= gaz_dbi_fetch_array($rs)) {
    $optval=($optval=='')?[]:$optval;
    $optval[$r['codbanacc']] = $r['desbanacc'];
}
?>
<script>
$(function() {
    $("#datareg").datepicker({ dateFormat: 'yy-mm-dd',showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html('ID '+$(this).attr("ref")+' tipo effetti: '+$(this).attr("tipeff"));
		$("p#iddescri").html($(this).attr("filename"));
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
						data: {'type':'distinte',ref:id},
						type: 'POST',
						url: './delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_distinte.php");
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
		<p><b>Distinta effetti</b></p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>File</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
	<div class="table-responsive">
	<table class="Tlarge table table-striped table-bordered table-condensed">
	<tr>
        <td class="FacetFieldCaptionTD">
            <?php gaz_flt_disp_int("id_doc", "ID"); ?>
        </td>
        <td class="FacetFieldCaptionTD">
            <?php gaz_flt_disp_select("tipeff", $gTables['effett'].".tipeff", $table, $t->where, "tipeff DESC",$script_transl['tipeff_value']); ?>
        </td>
        <td class="FacetFieldCaptionTD">
            <?php gaz_flt_disp_select("codbanacc", $gTables['clfoco'].".descri AS desbanacc,".$gTables['clfoco'].".codice AS codbanacc ", $table, $t->where, "codice DESC",$optval); ?>
        </td>
        <td class="FacetFieldCaptionTD">
            <?php gaz_flt_disp_select("anno", "YEAR(".$gTables['files'].".last_modified) AS anno", $table, $t->where, "anno DESC"); ?>
        </td>
		<td class="FacetFieldCaptionTD" colspan="2">
			<input type="submit" class="btn btn-xs btn-default" name="search" value="<?php echo $script_transl['search'];?>" tabindex="1" onClick="javascript:document.report.all.value=1;">
			<a class="btn btn-xs btn-default" href="?">Reset</a>
			<?php  $t->output_order_form(); ?>
		</td>
        <td class="FacetFieldCaptionTD" colspan="2">
        </td>
	</tr>

<?php
$today = strtotime(date("Y-m-d"));
$rs=gaz_dbi_dyn_query ("COUNT(".$gTables['effett'].".id_tes) AS neff, MAX(".$gTables['effett'].".scaden) AS maxsca, MIN(".$gTables['effett'].".scaden) AS minsca, ".$gTables['effett'].".tipeff, ".$gTables['files'].".*, ".$gTables['clfoco'].".descri AS desbanacc, ".$gTables['clfoco'].".codice AS codbanacc", $table, $t->where, $t->orderby, $t->getOffset(), $t->getLimit(), $t->group_by);
echo '<tr>';
$t->output_headers();
echo '</tr>';
while ($r = gaz_dbi_fetch_array($rs)) {
    // controllo possibile cancellazione distinta solo se la prima scadenza è maggiore di oggi
    $expire = strtotime($r['minsca']);
    $disabled='';
    $dialogdel='dialog_delete';
?>
<tr>
    <td class="text-center"><?php echo $r["id_doc"]; ?></td>
    <td class="text-center"><?php echo gaz_format_date(substr($r["last_modified"],0,10)); ?></td>
    <td> <?php echo $r["desbanacc"]; ?></td>
    <td><a href="../root/retrieve.php?id_doc=<?php echo $r["id_doc"]; ?>" class="btn btn-default btn-sm" title="download"><?php echo $r["title"]; ?> <i class="glyphicon glyphicon-download"></i> </a></td>
    <td class="text-center small"> <?php echo $r["neff"].' disposizioni<br/>prima scadenza: '.gaz_format_date($r["minsca"]).'<br/>ultima scadenza: '.gaz_format_date($r["maxsca"]); ?></td>
    <td class="text-center"><?php echo '<a class="btn btn-xs btn-default" href="stampa_distint.php?id_distinta='.$r["id_doc"].'">Distinta '.$r["id_doc"].' (pdf) <i class="glyphicon glyphicon-print"></i></a> '; ?></td>
    <td class="text-center">
    <a class="btn btn-xs  btn-elimina <?php echo $dialogdel; ?>" title="Cancella la distinta <?php echo $script_transl['tipeff_value'][$r['tipeff']]; ?>" ref="<?php echo $r['id_doc'];?>" filename="<?php echo $r['title']; ?>" tipeff="<?php echo $script_transl['tipeff_value'][$r['tipeff']]; ?>" <?php echo $disabled; ?> ><i class="glyphicon glyphicon-trash"></i></a>
    </td>
</tr>
<?php
}
?>
     </table>
	</div>
</form>

<script src="../../js/custom/fix_select.js" type="text/javascript"></script>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>

<?php
require("../../library/include/footer.php");
?>
