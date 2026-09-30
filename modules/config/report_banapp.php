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
$script_transl = HeadMain('','','admin_banapp');
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
						data: {'type':'banapp',ref:id},
						type: 'POST',
						url: '../config/delete.php',
						success: function(output){
							window.location.replace("./report_banapp.php");
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
	<p><b>Banca d'appoggio:</b></p>
	<p>Codice:</p>
	<p class="ui-state-highlight" id="idcodice"></p>
	<p>Descrizione:</p>
	<p class="ui-state-highlight" id="iddescri"></p>
</div>
<div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['report']; ?></div>
<?php
$recordnav = new recordnav($gTables['banapp'], $where, $limit, $passo);
$recordnav -> output();
?>
<div class="table-responsive"><table class="Tlarge table table-striped table-bordered table-condensed">
<?php
$headers_banapp = array  (
              $script_transl['codice'] => "codice",
              $script_transl['descri'] => "descri",
              $script_transl['locali'] => "locali",
              $script_transl['codabi'] => "codabi",
              $script_transl['codcab'] => "codcab",
              $script_transl['delete'] => ""
              );
$linkHeaders = new linkHeaders($headers_banapp);
$linkHeaders -> output();
?>
   </tr>
<?php
$accmov=[];
$rs=gaz_dbi_query("SELECT banapp , COUNT(*) FROM ".$gTables['tesdoc']."  GROUP BY banapp");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};
$rs=gaz_dbi_query("SELECT banapp, COUNT(*) FROM ".$gTables['tesbro']."  GROUP BY banapp");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};
$rs=gaz_dbi_query("SELECT banapp, COUNT(*) FROM ".$gTables['clfoco']."  GROUP BY banapp");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};

$result = gaz_dbi_dyn_query ('*', $gTables['banapp'], $where, $orderby, $limit, $passo);
while ($r = gaz_dbi_fetch_array($result)) {
  echo "<tr class=\"FacetDataTD\">";
  echo "<td align=\"center\"><a class=\"btn btn-xs btn-edit\" href=\"admin_banapp.php?Update&codice=".$r["codice"]."\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;".$r["codice"]."</a> &nbsp</td>";
  echo "<td>".$r["descri"]." &nbsp;</td>";
  echo "<td align=\"center\">".$r["locali"]." &nbsp;</td>";
  echo "<td align=\"center\">". sprintf("%'.05d\n", $r["codabi"]) ."</td>";
  echo "<td align=\"center\">". sprintf("%'.05d\n", $r["codcab"]) ."</td><td align=\"center\">";
  if (isset($accmov[$r["codice"]])){
		?>
		<button title="Impossibile cancellare perché usata da clienti e/o documenti" class="btn btn-xs   disabled"><i class="glyphicon glyphicon-trash"></i></button>
		<?php
	} else {
		?>
		<a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella la banca d'appoggio" ref="<?php echo $r['codice'];?>" ragso="<?php echo $r['descri'];?>">
			<i class="glyphicon glyphicon-trash"></i>
		</a>
		<?php
	}
  echo "</td></tr>";
}
?>
 </table></div>
<?php
require("../../library/include/footer.php");
?>
