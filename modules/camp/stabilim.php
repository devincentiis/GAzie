<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
// >> Gestione campi o appezzamenti di terreno <<

require("../../library/include/datlib.inc.php");

$admin_aziend=checkAdmin();
$titolo = 'Campi';
require("../../library/include/header.php");
$script_transl = HeadMain();

if (isset($_GET['auxil'])) {
   $auxil = $_GET['auxil'];
}
if (isset($_GET['all'])) {
   $auxil = "&all=yes";
   $where = "descri like '%' AND id_rif > 0";
   $passo = 100000;
} else {
   if (isset($_GET['auxil'])) {
      $where = "descri like '".addslashes($_GET['auxil'])."%' AND id_rif > 0";
   }
}

if (!isset($_GET['auxil'])) {
   $auxil = "";
   $where = "descri like '".addslashes($auxil)."%' AND id_rif > 0";
}
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("desstab"));
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
						data: {'type':'campi',ref:id},
						type: 'POST',
						url: '../root/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./stabilim.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<div align="center" class="FacetFormHeaderFont">Stabilimenti e depositi aggiuntivi a quello aziendale</div>
<?php
$recordnav = new recordnav($gTables['campi'], $where, $limit, $passo);
$recordnav -> output();
?>
<form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
		<p><b>stabilimento:</b></p>
		<p>Codice</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
    <table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
    	<thead>
            <tr>
                <td></td>
                <td class="FacetFieldCaptionTD">Denominazione:
                    <input type="text" name="auxil" value="<?php if ($auxil != "&all=yes") echo $auxil; ?>" maxlength="6" tabindex="1" class="FacetInput" />
                </td>
                <td>
                    <input type="submit" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value=1;" />
                </td>
                <td>
                    <input type="submit" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value=1;" />
                </td>
            </tr>
            <tr>
<?php
	$result = gaz_dbi_dyn_query ('*', $gTables['campi'], $where, $orderby, $limit, $passo);
	// creo l'array (header => campi) per l'ordinamento dei record
	$headers_campi = array("ID" => "codice",
							"Codice SIAN" => "id_rif",
							"Denominazione" => "descri",
							"Indirizzo" => "indirizzo",
							"Città" => "comune",
							"Provincia" => "provincia",
							"Cancella" => ""
							);
	$linkHeaders = new linkHeaders($headers_campi);
	$linkHeaders -> output();
?>
        	</tr>
        </thead></form>
        <tbody>
<?php


while ($a_row = gaz_dbi_fetch_array($result)) {
?>
			<tr class="FacetDataTD">
			<td>
				<a class="btn btn-xs btn-success btn-block" title="Modifica" href="admin_stabilim.php?Update&id_rif=<?php echo $a_row["id_rif"]; ?>">
					<i class="glyphicon glyphicon-edit"></i>&nbsp;<?php echo $a_row["codice"];?>
				</a>
			</td>
			<td align="center"><?php echo $a_row['id_rif'];?></td>
			<td align="center"><?php echo $a_row['descri'];?></td>
			<td align="center"><?php echo $a_row['indirizzo'];?></td>
			<td align="center"><?php echo $a_row['comune'];?></td>
			<td align="center"><?php echo $a_row['provincia'];?></td>
			<td align="center">
				<a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $a_row['codice'];?>" desstab="<?php echo $a_row['descri']; ?>">
					<i class="glyphicon glyphicon-trash"></i>
				</a>
			</td>
		</tr>
<?php
}
?>
		<tr class=\"FacetFieldCaptionTD\">
			<form method="post" action="admin_stabilim.php">
			<td colspan="7" align="right"><input class="btn btn-info" type="submit" name="aggiungi" value="<?php echo "Inserisci nuovo stabilimento";?>">
			</td>
		</tr>
<!-- se servirà la stampa ripristinare con le dovute modifiche
<tr class=\"FacetFieldCaptionTD\">
	<form method="post" action="stampa_campi.php">
    <td colspan="7" align="right"><input type="submit" name="print" value="<?php echo $script_transl['print'];?>">
	</td>
</tr>
-->
    		</tbody>
        </table></form>
    <?php
require("../../library/include/footer.php");
?>
