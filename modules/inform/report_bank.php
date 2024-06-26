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
// campi ammissibili per la ricerca
$search_fields = [
    'sea_id' => $gTables['bank']. ".id = %d",
    'abi'  => "codabi = %d",
];
// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
            "ID" => 'id',
            "ABI"=>'codabi',
            "Banca"=>'descriabi',
            "CAB"=>'codcab',
            "Banca<br/>appoggio"=>'',
            "Sportello"=>'descricab',
            'Indirizzo' => 'indiri',
            'Comune' => 'descomune',
            'Elimina' => ''
);
$tablejoin = $gTables['bank']. " LEFT JOIN " . $gTables['municipalities'] . " ON " . $gTables['bank'] . ".id_municipalities = " . $gTables['municipalities'] . ".id";
$ts = new TableSorter(
    $tablejoin,
    $passo,
    ['codabi'=>'asc','codcab'=>'asc']
    );
?>
<script>
$(function() {

    $("#dialog_banapp").dialog({ autoOpen: false });
	$('.dialog_banapp').click(function() {
		$("p#banappabicab").html($(this).attr("banappabicab"));
		$("p#banappbank").html($(this).attr("banappbank"));
		var id = $(this).attr('ref');
		$( "#dialog_banapp" ).dialog({
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
					text:'Aggiungi',
					'class':'btn btn-danger',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'add_banapp',ref:id},
						type: 'POST',
						url: './operat.php',
						success: function(output){
							window.location.replace("./report_bank.php?abi=All&sea_id="+id);
						}
					});
				}}
			}
		});
		$("#dialog_banapp" ).dialog( "open" );
	});

    $("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#abicab").html($(this).attr("abicab"));
		$("p#describank").html($(this).attr("describank"));
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
						data: {'type':'del_bank',ref:id},
						type: 'POST',
						url: './operat.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_bank.php");
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

	$( "#suggest_search" ).autocomplete({
		source: "./search.php?opt=suggest_search",
		minLength: 4,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#suggest_search").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});
});
</script>
<?php
$script_transl = HeadMain(0, array('custom/autocomplete'));
?>
<div class="text-center"><h3>Sportelli bancari</h3>
</div>
<div class="col-xs-12 text-center"><div class="col-xs-6"></div><div class="col-xs-6 text-center"><a href="./admin_bank.php" class="btn btn-success">Inserisci Nuovo</a></div>
</div>
<?php
$ts->output_navbar();
?>
<form method="GET" class="clean_get">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>Banca:</b></p>
        <p class="ui-state-highlight" id="abicab"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="describank"></p>
	</div>
	<div style="display:none" id="dialog_banapp" title="Banca d'appoggio">
        <p><b>Aggiungi </b></p>
        <p class="ui-state-highlight" id="banappabicab"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="banappbank"></p>
	</div>
	<div class="table-responsive">
	<table class="Tlarge table table-striped table-bordered table-condensed">
	<tr>
		<td class="FacetFieldCaptionTD">
        </td>
		<td class="FacetFieldCaptionTD">
        <?php  gaz_flt_disp_select("abi", "codabi AS abi",$tablejoin, $ts->where, "codabi ASC"); ?>
		</td>
		<td class="FacetFieldCaptionTD">
        </td>
		<td class="FacetFieldCaptionTD" colspan="4">
			<input type="text" name="sea_id" placeholder="ricerca sportello ( min.5 caratteri )"  id="suggest_search" class="input-sm form-control" value="<?php echo (isset($sea_id))? htmlentities($sea_id, ENT_QUOTES) : ""; ?>" maxlength="20">
        </td>
		<td class="FacetFieldCaptionTD" colspan="2">
			<a class="btn btn-sm btn-default" href="?">Reset</a>
		</td>
	</tr>
<?php

$result = gaz_dbi_dyn_query ( $gTables['bank']. ".*, ".$gTables['municipalities']. ".name AS descomune ",$tablejoin, $ts->where, $ts->orderby, $ts->getOffset(), $ts->getLimit());
echo '<tr>';
$ts->output_headers();
echo '</tr>';
while ($r = gaz_dbi_fetch_array($result)) {
    $banapp = gaz_dbi_get_row($gTables['banapp'], 'codabi', $r['codabi'], "AND codcab ='".$r['codcab']."'");
    echo "<tr>\n";
    echo '<td align="center">
    <a class="btn btn-xs btn-edit" href="./admin_bank.php?id='.$r['id'].'" ><i class="glyphicon glyphicon-edit"></i> '.$r['id'].'</a>';
    echo '</td>';
    echo '<td class="text-center">'.$r['codabi'];
    echo "</td>\n";
    echo '<td>'.$r['descriabi'];
	echo "</td>\n";
    echo '<td>'.$r['codcab'];
	echo "</td>\n";
    echo '<td class="text-center">'.(($banapp)?'<a href="../config/admin_banapp.php?Update&codice='.$banapp['codice'].'" class="btn btn-xs btn-success" title="Banca d\'appoggio presente"><i class="glyphicon glyphicon-edit">'.$banapp['descri'].'</i></a>':'<div class="btn btn-xs btn-info dialog_banapp" ref="'.$r['id'].'" title="Aggiungi come banca d\'appoggio" banappabicab="ABI: '. $r['codabi'].' CAB:'.$r['codcab'].'" banappbank="'. $r['descriabi'].' '.$r['descricab'].'"><i class="glyphicon glyphicon-share"> aggiungi</i> </div>');
	echo "</td>\n";
    echo '<td>'.$r['descricab'];
	echo "</td>\n";
    echo '<td>'.$r['indiri'];
	echo "</td>\n";
    echo '<td>'.strtoupper($r['descomune']);
	echo "</td>\n";
    echo '<td class="text-center"><a class="btn btn-xs  btn-elimina dialog_delete" ref="'. $r['id'].'" abicab="ABI: '. $r['codabi'].' CAB:'.$r['codcab'].'" describank="'. $r['descriabi'].' '.$r['descricab'].'"> <i class="glyphicon glyphicon-trash"></i></a>';
	echo "</td>\n";
    echo "</tr>\n";
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
