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
$where = 1;
if (isset($_GET['ragso1'])) {
   if (!empty($_GET['ragso1'])) {
      $ragso1 = $_GET['ragso1'];
      $auxil = "&ragso1=".$ragso1;
      $where = "ragso1 LIKE '".addslashes($ragso1)."%' ";
      $passo = 1;
   }
}  else {
   $ragso1 ='';
}
if (isset($_GET['all'])) {
   $where = 1;
   $auxil = "&all=yes";
   $passo = 100000;
   $ragso1 ='';
}
require("../../library/include/header.php");
$script_transl=HeadMain('','','admin_agenti');
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("nome"));
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
						data: {'type':'agenti',ref:id},
						type: 'POST',
						url: '../vendit/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_agenti.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<form method="GET" >
<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
	<p><b>agente</b></p>
	<p>Codice:</p>
	<p class="ui-state-highlight" id="idcodice"></p>
	<p>Nome:</p>
	<p class="ui-state-highlight" id="iddescri"></p>
</div>
<div align="center" class="FacetFormHeaderFont">
<?php echo ucfirst($script_transl[0]);?>
</div>
<?php
if (!isset($_GET['field']) or ($_GET['field'] == 2) or(empty($_GET['field'])))
        $orderby = "id_agente DESC";
$recordnav = new recordnav($gTables['agenti']." LEFT JOIN ".$gTables['clfoco']." on ".$gTables['agenti'].".id_fornitore = ".$gTables['clfoco'].".codice "
            . "left join ".$gTables['anagra']." on ".$gTables['anagra'].".id=" . $gTables['clfoco'] . ".id_anagra", $where, $limit, $passo);
$recordnav -> output();
?>
<div class="box-primary table-responsive">
<table class="Tlarge table table-striped table-bordered table-condensed">
<tr>
<td colspan="2" class="FacetFieldCaptionTD"><?php echo $script_transl[1].' :'; ?>
<input type="text" name="ragso1" value="<?php if (isset($ragso1)) echo $ragso1; ?>" maxlength="6" tabindex="1" class="FacetInput">
</td>
<td colspan="4" class="FacetFieldCaptionTD">
</td>
<td class="FacetFieldCaptionTD">
<input type="submit" name="search" value="<?php echo $script_transl['search']; ?>" tabindex="1" onClick="javascript:document.report.all.value=1;">
</td>
<td class="FacetFieldCaptionTD">
<input type="submit" name="all" value="<?php echo $script_transl['vall']; ?>" onClick="javascript:document.report.all.value=1;">
</td>
</tr>
<tr>
<?php
// creo l'array (header => campi) per l'ordinamento dei record
$headers_tesdoc = array  (
            'N.' => 'id_agente',
            $script_transl[1] => 'ragso1',
            $script_transl[4]=> 'telefo',
            $script_transl[5]=> 'fax',
            $script_transl[20]=> '',
            $script_transl[19] => '',
            $script_transl[6] => 'base_percent',
            $script_transl['delete'] => ''
            );
$linkHeaders = new linkHeaders($headers_tesdoc);
$linkHeaders -> output();
?>
</tr>
<?php
$result = gaz_dbi_dyn_query($gTables['agenti'].".*,".$gTables['anagra'].".telefo,".$gTables['anagra'].".ragso1,".$gTables['anagra'].".ragso2,".$gTables['anagra'].".fax", $gTables['agenti']." LEFT JOIN ".$gTables['clfoco']." on ".$gTables['agenti'].".id_fornitore = ".$gTables['clfoco'].".codice LEFT JOIN ".$gTables['anagra'].' ON '.$gTables['clfoco'].'.id_anagra = '.$gTables['anagra'].'.id', $where, $orderby,$limit, $passo);
while ($a_row = gaz_dbi_fetch_array($result)) {
        echo "<tr><td class=\"FacetDataTD\" align=\"center\"><a class=\"btn btn-xs btn-edit\" href=\"admin_agenti.php?id_agente=".$a_row['id_agente']."&Update\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;".$a_row['id_agente']."</a></td>";
        echo "<td class=\"FacetDataTD\">".$a_row["ragso1"]." ".$a_row["ragso2"]." &nbsp;</td>";
        echo "<td class=\"FacetDataTD\" align=\"center\">".$a_row["telefo"]."&nbsp;</td>";
        echo "<td class=\"FacetDataTD\" align=\"center\">".$a_row["fax"]." &nbsp;</td>";
        echo "<td class=\"FacetDataTD\" align=\"center\"><a class=\"btn btn-xs btn-default\" href=\"print_anagrafe.php?id_agente=".$a_row['id_agente']."\" target=\"_blank\"><i class=\"glyphicon glyphicon-print\"></i>&nbsp;</a></td>";
        echo "<td class=\"FacetDataTD\" align=\"center\"><a class=\"btn btn-xs btn-default\" href=\"select_provvigioni.php?id_agente=".$a_row['id_agente']."\" target=\"_blank\"><i class=\"glyphicon glyphicon-print\"></i>&nbsp;</a></td>";
        echo "<td class=\"FacetDataTD\" align=\"center\">".$a_row["base_percent"]." &nbsp;</td>";
        echo "<td class=\"FacetDataTD\" align=\"center\">";
		?>
		<a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $a_row['id_agente'];?>" nome="<?php echo $a_row['ragso1']," ",$a_row['ragso2']; ?>">
			<i class="glyphicon glyphicon-trash"></i>
		</a>
		<?php
        echo "</td></tr>\n";
}
?>
<tr><th class="FacetFieldCaptionTD" colspan="8"></th></tr>
</form>
</table>
</div>
<?php
require("../../library/include/footer.php");
?>
