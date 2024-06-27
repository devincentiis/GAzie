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
$script_transl=HeadMain();
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("ragso"));
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
						data: {'type':'aliiva',ref:id},
						type: 'POST',
						url: '../config/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_aliiva.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
	<p><b>Aliquota IVA:</b></p>
	<p>Codice:</p>
	<p class="ui-state-highlight" id="idcodice"></p>
	<p>Descrizione:</p>
	<p class="ui-state-highlight" id="iddescri"></p>
</div>
<?php
echo '<div align="center" class="FacetFormHeaderFont">'.$script_transl['title'].'</div>';
if(!isset($_GET['field'])){
	$field='codice';
	$orderby='codice';
	$flagorder='DESC';
	$flagorpost='DESC';
}
$recordnav = new recordnav($gTables['aliiva'], $where, $limit, $passo);
$recordnav -> output();
echo '<div class="table-responsive"><table class="Tlarge table table-striped table-bordered table-condensed">';
$headers = array  (
  $script_transl['codice']=>'codice',
  $script_transl['descri']=>'descri',
  $script_transl['type']=>'tipiva',
  $script_transl['operation_type']=>'operation_type',
  $script_transl['aliquo']=>'aliquo',
  $script_transl['taxstamp']=>'taxstamp',
  $script_transl['fae_natura']=>'fae_natura',
  $script_transl['delete']=>''
);
$linkHeaders = new linkHeaders($headers);
$linkHeaders -> output();
$accmov=[];
$rs=gaz_dbi_query("SELECT codiva , COUNT(*) FROM ".$gTables['rigmoi']."  GROUP BY codiva");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};
$rs=gaz_dbi_query("SELECT codvat, COUNT(*) FROM ".$gTables['rigdoc']."  GROUP BY codvat");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};
$rs=gaz_dbi_query("SELECT codvat, COUNT(*) FROM ".$gTables['rigbro']."  GROUP BY codvat");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};
//var_dump($accmov);
$result = gaz_dbi_dyn_query ('*', $gTables['aliiva'], $where, $orderby, $limit, $passo);
while ($r = gaz_dbi_fetch_array($result)) {
  echo "<tr class=\"FacetDataTD\">";
  echo "<td align=\"center\"><a class=\"btn btn-xs btn-edit\" href=\"admin_aliiva.php?Update&codice=".$r["codice"]."\"><i class=\"glyphicon glyphicon-edit\"></i> ".$r["codice"]."</a> &nbsp</td>";
  echo "<td>".$r["descri"]."  </td>";
  echo "<td align=\"center\">".$script_transl['tipiva'][$r["tipiva"]]."</td>";
  echo "<td align=\"center\">".$r["operation_type"]."  </td>";
  echo "<td align=\"center\">".$r["aliquo"]."  </td>";
  echo "<td align=\"center\">".$script_transl['yn_value'][$r["taxstamp"]]."  </td>";
  echo "<td align=\"center\">".$r["fae_natura"]."  </td><td align=\"center\">";
  if (isset($accmov[$r["codice"]])) {
		?>
		<button title="Impossibile cancellare perché ci sono  <?php  echo ($accmov[$r["codice"]]); ?>  movimenti associati" class="btn btn-xs btn-default disabled"> <i class="glyphicon glyphicon-trash"></i></button>
		<?php
	} else {
		?>
		<a class="btn btn-xs btn-elimina dialog_delete" title="Cancella l'aliquota IVA" ref="<?php  echo $r['codice'];?>" ragso="<?php echo $r['descri'];?>">
			<i class="glyphicon glyphicon-trash"></i>
		</a>
		<?php
	}
  echo "</td>";
  echo "</tr>";
}
?>
</table></div>
<?php
require("../../library/include/footer.php");
?>
