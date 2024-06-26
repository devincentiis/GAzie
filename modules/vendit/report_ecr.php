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
$admin_aziend=checkAdmin(9);
require("../../library/include/header.php");
$script_transl = HeadMain('', '', 'admin_ecr');
$search_fields = [];
// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
            'ID' => 'id_cash',
            $script_transl['descri']=>'descri',
            'Users'=>'',
            'Driver' => 'driver',
            $script_transl['serial_port'] => 'serial_port',
            $script_transl['path_data'] => 'path_data',
            $script_transl['delete'] => ''
);

$tablejoin = $gTables['cash_register'];

$ts = new TableSorter(
    $tablejoin, 
    $passo, 
    ['id_cash'=>'asc']);
?>
    <div class="text-center"><h3><?php echo $script_transl['report'];?></h3></div>

<?php
$ts->output_navbar();

?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("nome"));
		$("p#ecrdescri").html($(this).attr("ecrdescri"));
		var id = $(this).attr('ref');		
		$( "#dialog_delete" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{ 
					text:'Elimina il R.T.', 
					'class':'btn btn-danger',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'ecr',ref:id},
						type: 'POST',
						url: './delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_ecr.php");
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
});
</script>
<form method="GET" class="clean_get">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
		<p><b>ID:</b></p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Registratore Telematico:</p>
        <p class="ui-state-highlight" id="ecrdescri"></p>
	</div>
	<div class="table-responsive">
	<table class="Tlarge table table-striped table-bordered table-condensed">
<?php
$result = gaz_dbi_dyn_query ($gTables['cash_register'].".*", 
                $tablejoin, $ts->where, $ts->orderby, $ts->getOffset(), $ts->getLimit());

echo '<tr>';
$ts->output_headers();
echo '</tr>';

while ($r = gaz_dbi_fetch_array($result)) {
    $utenti=json_decode($r['enabled_users']);
    echo "<tr>\n";
    echo "<td>";
	echo '<a class="btn btn-xs btn-success" href="admin_ecr.php?id_cash='.$r["id_cash"].'" title="'.ucfirst($script_transl['update']).'!"><i class="glyphicon glyphicon-edit text-success"></i>&nbsp;'.$r['id_cash'].'</a> &nbsp</td>';
	echo "<td>".$r["descri"]." &nbsp;</td>\n";
    echo "<td>";
    foreach($utenti as $v){
        echo $v.'<br>';
    }
    echo "</td>\n";
	echo "<td>".$r["driver"]." &nbsp;</td>\n";
    echo "<td>".$r["serial_port"]."</td>\n";
    echo "<td>".$r["path_data"]."</td>\n";
	echo '<td align="center"><a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella il Registratore Telematico" ref="'.$r['id_cash'].'" nome="'.$r['id_cash'].'" ecrdescri="'.$r['descri'].'">
		<i class="glyphicon glyphicon-trash"></i></a></td>';				
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
