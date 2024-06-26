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
$where = "codice BETWEEN ".$admin_aziend['masban']."000001 AND ".$admin_aziend['masban']."999999";
$anagrafica = new Anagrafica();
$result=$anagrafica->queryPartners('*', $where, $orderby, $limit, $passo);
echo '<div align="center" class="FacetFormHeaderFont">'.$script_transl['title'].'</div>';
$recordnav = new recordnav($gTables['clfoco'], $where, $limit, $passo);
$recordnav -> output();
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
						data: {'type':'piacon',ref:id},
						type: 'POST',
						url: '../contab/delete.php',
						success: function(output){
							window.location.replace("./report_banche.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<form method="POST">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
		<p><b>conto:</b></p>
		<p>ID:</p>
		<p class="ui-state-highlight" id="idcodice"></p>
		<p>Descrizione:</p>
		<p class="ui-state-highlight" id="iddescri"></p>
	</div>
<?php
echo '<div class="table-responsive"><table class="Tlarge table table-striped table-bordered table-condensed">';
$headers = array  (
            $script_transl['codice']=>'codice',
            $script_transl['ragso1']=>'ragso1',
            $script_transl['iban']=>'iban',
            $script_transl['citspe']=>'citspe',
            $script_transl['prospe']=>'prospe',
            $script_transl['telefo']=>'telefo',
            $script_transl['view']=>'',
            $script_transl['delete']=>''
            );
$linkHeaders = new linkHeaders($headers);
$linkHeaders -> output();
foreach($result as $r) {
    echo "<tr class=\"FacetDataTD\">";
    echo "<td align=\"center\"><a class=\"btn btn-xs btn-edit\" href=\"admin_bank_account.php?Update&codice=".substr($r["codice"],3)."\" title=\"Modifica\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;".substr($r["codice"],3)."</a> &nbsp</td>";
    echo "<td>".$r["ragso1"]." &nbsp;</td>";
    if (!empty($r['iban'])) {
       echo "<td>".$r["iban"]." &nbsp;</td>";
       echo "<td>".$r["citspe"]." &nbsp;</td>";
       echo "<td>".$r["prospe"]." &nbsp;</td>";
       echo "<td>".$r["telefo"]." &nbsp;</td>";
    } else {
       echo "<td colspan=\"4\">".$script_transl['msg'][0]."</td>\n";
    }
    echo "<td align=\"center\"><a class=\"btn btn-xs btn-default\" href=\"../contab/select_partit.php?id=".$r["codice"]."\" target=\"_blank\"><i class=\"glyphicon glyphicon-check\"></i>&nbsp;<i class=\"glyphicon glyphicon-print\"></a></td>";
    echo "<td align=\"center\"><a class=\"btn btn-xs  btn-elimina dialog_delete\" ref=\"".$r["codice"]."\"><i class=\"glyphicon glyphicon-trash\"></i></a></td>";
    echo "</tr>";
}
?>
</table></div>
</form>
<?php
require("../../library/include/footer.php");
?>



