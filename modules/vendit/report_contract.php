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

$anagrafica = new Anagrafica();

if (!empty($_GET['auxil'])) {
   $auxil = $_GET['auxil'];
	$where = "vat_section='$auxil'";
} else {
   $auxil = 1;
	$where = "vat_section='$auxil'";
}
if (!empty($_GET['all'])) {
	$where = "vat_section='$auxil'";
   $auxil = $_GET['auxil']."&all=yes";
   $passo = 100000;
	$protocollo = '';
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new GAzieForm();
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("cliente"));
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
						data: {'type':'contract',ref:id},
						type: 'POST',
						url: '../vendit/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_contract.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
   $( "#dialog" ).dialog({
      autoOpen: false
   });
});
function confirMail(link){
   tes_id = link.id.replace("doc", "");
   $.fx.speeds._default = 500;
   targetUrl = $("#doc"+tes_id).attr("url");
   $("p#mail_adrs").html($("#doc"+tes_id).attr("mail"));
   $("p#mail_attc").html($("#doc"+tes_id).attr("namedoc"));
   $( "#dialog" ).dialog({
         modal: "true",
      show: "blind",
      hide: "explode",
         buttons: {
                      "<?php echo $script_transl['submit']; ?>": function() {
                         window.location.href = targetUrl;
                      },
                      "<?php echo $script_transl['cancel']; ?>": function() {
                        $(this).dialog("close");
                      }
                  }
         });
   $("#dialog" ).dialog( "open" );
}
function printPdf(urlPrintDoc){
	$(function(){
		$('#framePdf').attr('src',urlPrintDoc);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
    $("html, body").delay(100).animate({scrollTop: $('#framePdf').offset().top},200, function() {
        $("#framePdf").focus();
    });
		$('#closePdf').on( "click", function() {
			$('.framePdf').css({'display': 'none'});
		});
	});
};
</script>
<?php
echo "<form method=\"GET\" name=\"report\">\n";
?>
<div class="framePdf panel panel-success" style="display: none; position: fixed; left: 5%; top: 10px">
	<div class="col-lg-12">
		<div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
		<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
	</div>
	<iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
</div>
<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
	<p><b>contratto</b></p>
	<p>Codice:</p>
	<p class="ui-state-highlight" id="idcodice"></p>
	<p>Cliente:</p>
	<p class="ui-state-highlight" id="iddescri"></p>
</div>
<?php
echo "<input type=\"hidden\" name=\"hidden_req\">\n";
?>
    <div style="display:none" id="dialog" title="<?php echo $script_transl['mail_alert0']; ?>">
        <p id="mail_alert1"><?php echo $script_transl['mail_alert1']; ?></p>
        <p class="ui-state-highlight" id="mail_adrs"></p>
        <p id="mail_alert2"><?php echo $script_transl['mail_alert2']; ?></p>
        <p class="ui-state-highlight" id="mail_attc"></p>
    </div>
<?php
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'].$script_transl['vat_section'];
$gForm->selectNumber('auxil',$auxil,0,1,9,'FacetSelect','auxil');
echo "</div>\n";
if (!isset($_GET['field']) or ($_GET['field'] == 2) or(empty($_GET['field'])))
        $orderby = "conclusion_date DESC, doc_number DESC";
$recordnav = new recordnav($gTables['contract'], $where, $limit, $passo);
$recordnav -> output();
gaz_flt_var_assign('id_customer', 'i', $gTables['contract']);
?>
<div class="box-primary table-responsive">
<table class="Tlarge table table-striped table-bordered table-condensed">
<tr>
<td colspan="2" class="FacetFieldCaptionTD" align="right"><?php echo $script_transl['number']; ?> :
<input type="text" name="doc_number" value="<?php if (isset($doc_number)) print $doc_number; ?>" maxlength="6" tabindex="1" class="FacetInput">
</td>
<td class="FacetFieldCaptionTD" align="center">
<input type="submit" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value=1;">
</td>
<td class="FacetFieldCaptionTD">
	<?php
	$tesdoc_e_partners = $gTables['contract'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['contract'] . ".id_customer=" . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['clfoco'] . ".id_anagra=" . $gTables['anagra'] . ".id";
	$where_select = $gTables['contract'] . ".status<>''";
	gaz_flt_disp_select("id_customer", $gTables['contract'] . ".id_customer," . $gTables['anagra'] . ".ragso1",
	$tesdoc_e_partners,
	$where_select, "ragso1 ASC", "ragso1");
	?>
</td>
<td colspan="5" class="FacetFieldCaptionTD" align="center">
<input type="submit" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value=1;">
</td>
</tr>
<tr>
<?php
// creo l'array (header => campi) per l'ordinamento dei record
$headers_tesdoc = array  (
            $script_transl['id'] => "id_customer",
            $script_transl['date'] => "conclusion_date",
            $script_transl['number'] => "doc_number",
            $script_transl['customer'] => "id_customer",
            $script_transl['current_fee'] => "current_fee",
            $script_transl['periodicity'] => "periodicity",
            $script_transl['print'] => "",
            "Mail" => "",
            $script_transl['delete'] => ""
            );
$linkHeaders = new linkHeaders($headers_tesdoc);
$linkHeaders -> output();
?>
</tr>
<?php
//recupero le testate in base alle scelte impostate
$result = gaz_dbi_dyn_query('*',$gTables['contract'], $where, $orderby,$limit, $passo);
while ($row = gaz_dbi_fetch_array($result)) {
        $cliente = $anagrafica->getPartner($row['id_customer']);
        print "<tr>";
        print "<td class=\"FacetDataTD\" align=\"center\"><a class=\"btn btn-xs btn-edit\" href=\"admin_contract.php?Update&id_contract=".$row['id_contract']."\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;".$row["id_contract"]."</a></td>";
        print "<td class=\"FacetDataTD\" align=\"center\">".gaz_format_date($row['conclusion_date'])."</td>";
        print "<td class=\"FacetDataTD\" align=\"center\">".$row['doc_number']." &nbsp;</td>";
        print "<td class=\"FacetDataTD\" align=\"center\"><a href=\"report_client.php?nome=".$cliente['ragso1']."\">".$cliente['ragso1']."</a>";
		if (!empty($row['note'])) {
			print "&nbsp;<span title=\"" . $row['note'] . "\"><i class=\"glyphicon glyphicon-map-marker\"></i></span>";
		}
        print "</td>";
        print "<td class=\"FacetDataTD\" align=\"center\">".$row['current_fee']." &nbsp;</td>";
        print "<td class=\"FacetDataTD\" align=\"center\">".$script_transl['periodicity_value'][$row['periodicity']]." &nbsp;</td>";
		echo "<td align=\"center\"><a class=\"btn btn-xs btn-default\" style=\"cursor:pointer;\" onclick=\"printPdf('print_contract.php?id_contract=".$row['id_contract']."')\"><i class=\"glyphicon glyphicon-print\" title=\"Stampa documento PDF\"></i></a>";
		// Colonna "Mail"
		print "<td align=\"center\">";
		if (!empty($cliente['e_mail'])) { // ho una mail sul cliente
			print '<a class="btn btn-xs btn-default btn-email" onclick="confirMail(this);return false;" id="doc' . $row['id_contract'] . '" url="print_contract.php?id_contract=' . $row['id_contract'] . '&dest=E" href="#" title="mailto: ' . $cliente['e_mail'] . '"
	mail="' . $cliente['e_mail'] . '" namedoc="Contratto n.' . $row['doc_number'] . ' del ' . gaz_format_date($row['conclusion_date']) . '"><i class="glyphicon glyphicon-envelope"></i></a>';
		} else { // non ho mail
			print '<a title="Non hai memorizzato l\'email per questo cliente, inseriscila ora" href="admin_client.php?codice=' . substr($row['id_customer'], 3) . '&Update"><i class="glyphicon glyphicon-edit"></i></a>';
		}
		print "</td>";
        print "<td class=\"FacetDataTD\" align=\"center\">";
		?>
		<a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $row['id_contract'];?>" cliente="<?php echo $cliente['ragso1']; ?>">
			<i class="glyphicon glyphicon-trash"></i>
		</a>
		<?php
        print "</td></tr>\n";
}
?>
</form>
</table>
</div>
<?php
require("../../library/include/footer.php");
?>
