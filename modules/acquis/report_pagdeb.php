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

$message = "";
$anno = date("Y");
require("../../library/include/header.php");
$script_transl=HeadMain();
?>
<div align="center" class="FacetFormHeaderFont">Bonifici e Ordini di Addebito</div>
<?php
$where = "tipdoc like 'AO_' ";
$all = $where;

if (!isset($_GET['flag_order'])) {
    $orderby = "id_tes desc";
}

gaz_flt_var_assign('id_tes','i');
gaz_flt_var_assign('tipdoc','i');
gaz_flt_var_assign('numdoc','i');
gaz_flt_var_assign('datemi','d' );
gaz_flt_var_assign('clfoco','v' );

if (isset($_GET['all'])) {
	$_GET['id_tes']="";
	$_GET['tipdoc']="";
	$_GET['numdoc']="";
	$_GET['datfat']="";
	$_GET['clfoco']="";
	$where=$all;
	$auxil="&all=yes";
}

$recordnav = new recordnav($gTables['tesbro'], $where, $limit, $passo);
$recordnav -> output();
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("fornitore"));
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
						data: {'type':'pagdeb',id_tes:id},
						type: 'POST',
						url: '../acquis/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_pagdeb.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
function printPdf(urlPrintDoc){
	$(function(){
		$('#framePdf').attr('src',urlPrintDoc);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
		$('#closePdf').on( "click", function() {
			$('.framePdf').css({'display': 'none'});
		});
	});
};
</script>
<form method="GET" >
	<div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
		<div class="col-lg-12">
			<div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
			<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
		</div>
		<iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
	</div>
<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>pagamento:</b></p>
        <p>ID:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Fornitore</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
<div class="table-responsive">
<table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
<tr>
	<td class="FacetFieldCaptionTD">
		<?php gaz_flt_disp_int ( "id_tes", "Numero Id" ); ?>
	</td>
	<td class="FacetFieldCaptionTD">
		<?php gaz_flt_disp_select ( "tipdoc", "tipdoc", $gTables["tesbro"], $all, $orderby); ?>
	</td>
	<td class="FacetFieldCaptionTD">
		<?php gaz_flt_disp_int ( "numdoc", "Numero Doc." );?>
	</td>
	<td class="FacetFieldCaptionTD">
		<?php gaz_flt_disp_select ( "datfat", "YEAR(datfat) as datfat", $gTables["tesbro"], $all, $orderby); ?>
	</td>
	<td class="FacetFieldCaptionTD">
		<?php gaz_flt_disp_select ( "clfoco", $gTables['anagra'].".ragso1,".$gTables["tesbro"].".clfoco", $gTables['tesbro']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['tesbro'].".clfoco = ".$gTables['clfoco'].".codice LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id", $all, $orderby, "ragso1"); ?>
	</td>
	<td class="FacetFieldCaptionTD">
		&nbsp;
	</td>
	<td colspan="1" class="FacetFieldCaptionTD">
        <input type="submit" class="btn btn-sm btn-default" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value = 1;">
    </td>
    <td colspan="1" class="FacetFieldCaptionTD">
        <input type="submit" class="btn btn-sm btn-default" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value = 1;">
    </td>
</tr>
<tr>
<?php
// creo l'array (header => campi) per l'ordinamento dei record
$headers_tesdoc = array  (
              "ID" => "id_tes",
              "Tipo" => "tipdoc",
              "Num." => "numdoc",
              "Data" => "datemi",
              "Fornitore" => "clfoco",
              "Importo" => "portos",
              "Stampa" => "",
              "Cancella" => ""
              );
$linkHeaders = new linkHeaders($headers_tesdoc);
$linkHeaders -> output();
?>
</tr>
<?php

$result = gaz_dbi_dyn_query ('*', $gTables['tesbro'], $where, $orderby, $limit, $passo);
$ctrlprotoc = "";
$anagrafica = new Anagrafica();
while ($a_row = gaz_dbi_fetch_array($result)) {
    $tipodoc="Pagamento";
    $modulo="stampa_ordban.php?id_tes=".$a_row['id_tes'];
    $modifi="update_pagdeb.php?id_tes=".$a_row['id_tes'];
    if ($a_row["tipdoc"] == 'AOB') {
        $tipodoc="Bonifico";
        $modulo="stampa_ordban.php?id_tes=".$a_row['id_tes'];
        $modifi="update_pagdeb.php?id_tes=".$a_row['id_tes'];
    } elseif ($a_row["tipdoc"] == 'AOA') {
        $tipodoc="Ordine di Addebito";
        $modulo="stampa_ordban.php?id_tes=".$a_row['id_tes'];
        $modifi="update_pagdeb.php?id_tes=".$a_row['id_tes'];
    }

    $fornitore = $anagrafica->getPartner($a_row['clfoco']);

    echo "<tr class=\"FacetDataTD\">";
    if (! empty ($modifi)) {
       echo "<td><a href=\"".$modifi."\">".$a_row["id_tes"]."</td>";
    } else {
       echo "<td>".$a_row["id_tes"]." &nbsp;</td>";
    }
    echo "<td>".$tipodoc." &nbsp;</td>";
    echo "<td align=\"center\">".$a_row["numdoc"]." &nbsp;</td>";
    echo "<td align=\"center\">".$a_row["datemi"]." &nbsp;</td>";
    echo "<td>".$fornitore["ragso1"]."&nbsp;</td>";
    echo "<td align=\"right\">".$a_row["portos"]." &nbsp;</td>";
	echo "<td align=\"center\"><a class=\"btn btn-xs btn-default\" style=\"cursor:pointer;\" onclick=\"printPdf('".$modulo."')\"><i class=\"glyphicon glyphicon-print\" title=\"Stampa documento PDF\"></i></a></td>
			  <td align=\"center\">";
	?>
	<a class="btn btn-xs  btn-elimina dialog_delete" title="Elimina questo documento" ref="<?php echo $a_row['id_tes'];?>" fornitore="<?php echo $fornitore['ragso1']; ?>">
		<i class="glyphicon glyphicon-trash"></i>
	</a>
	<?php
	echo "</td></tr>";
}
?>
</table></div>
</form>
<?php
require("../../library/include/footer.php");
?>
