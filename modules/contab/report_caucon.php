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
$script_transl=HeadMain('','','admin_caucon');
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("descri"));
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
						data: {'type':'caucon',ref:id},
						type: 'POST',
						url: '../contab/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_caucon.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['report']; ?></div>
<?php
$recordnav = new recordnav($gTables['caucon'], $where, $limit, $passo);
$recordnav -> output();
?>
<div class="table-responsive">
<table class="Tlarge table table-striped table-bordered table-condensed">
<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
		<p><b>causale contabile:</b></p>
		<p>ID:</p>
		<p class="ui-state-highlight" id="idcodice"></p>
		<p>Descrizione:</p>
		<p class="ui-state-highlight" id="iddescri"></p>
	</div>
<?php
$headers_caucon = array  (
            $script_transl['codice']=> "codice",
            $script_transl['descri'] => "descri",
            $script_transl['regiva']=> "regiva",
            $script_transl['operat']=> "operat",
            $script_transl['delete']=> ""
            );
$linkHeaders = new linkHeaders($headers_caucon);
$linkHeaders -> output();
$result = gaz_dbi_dyn_query ('*', $gTables['caucon'], $where, $orderby);
while ($row = gaz_dbi_fetch_array($result)) {
    echo "<tr>";
    echo "<td align=\"center\"><a class=\"btn btn-xs btn-edit\" href=\"admin_caucon.php?codice=".$row["codice"]."&Update\"><i class=\"glyphicon glyphicon-folder-open\"></i>&nbsp;&nbsp;".$row["codice"]."</a> &nbsp</td>";
    echo "<td>".$row["descri"]." &nbsp;</td>";
    echo "<td align=\"center\">".$script_transl['regiva_value'][$row["regiva"]]." &nbsp;</td>";
    echo "<td align=\"center\">".$script_transl['operat_value'][$row["operat"]]." &nbsp;</td>";
    echo "<td align=\"center\">";
	?>
		<a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella causale" ref="<?php echo $row['codice'];?>" descri="<?php echo $row['descri'];?>">
			<i class="glyphicon glyphicon-trash"></i>
		</a>
	<?php
    echo "</td></tr>";
}
?>
</table></div>
<?php
require("../../library/include/footer.php");
?>



