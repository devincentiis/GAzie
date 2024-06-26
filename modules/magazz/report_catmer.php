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
$titolo = 'Categorie Merceologiche';
require("../../library/include/header.php");
$script_transl = HeadMain();
if (isset($_GET['auxil'])) {
   $auxil = $_GET['auxil'];
}
if (isset($_GET['all'])) {
   $auxil = "&all=yes";
   $where = "descri like '%'";
   $passo = 100000;
} else {
   if (isset($_GET['auxil'])) {
      $where = "descri like '".addslashes($_GET['auxil'])."%'";
   }
}

if (!isset($_GET['auxil'])) {
   $auxil = "";
   $where = "descri like '".addslashes($auxil)."%'";
}
/** ENRICO FEDELE */
/* pulizia del codice, eliminato boxover, aggiunte classi bootstrap alla tabella, convertite immagini in glyphicons */
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("catdes"));
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
						data: {'type':'catmer',ref:id},
						type: 'POST',
						url: '../magazz/delete.php',
						success: function(output){
							window.location.replace("./report_catmer.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<div align="center" class="FacetFormHeaderFont">Categorie Merceologiche</div>
<?php
$recordnav = new recordnav($gTables['catmer'], $where, $limit, $passo);
$recordnav -> output();
?>


<form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>CATEGORIA MERCEOLOGICA:</b></p>
        <p>codice:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
  <div class="table-responsive">
    <table class="Tlarge table table-striped table-bordered table-condensed">
    	<thead>
            <tr>
                <td></td>
                <td class="FacetFieldCaptionTD">Descrizione:
                    <input type="text" name="auxil" value="<?php if ($auxil != "&all=yes") echo $auxil; ?>" maxlength="100" tabindex="1" class="FacetInput" />
					<input type="submit" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value=1;" />
                </td>
                <td>
                    <input type="submit" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value=1;" />
                </td>
                <td>
                </td>
            </tr>
            <tr>
<?php
	$result = gaz_dbi_dyn_query ('*', $gTables['catmer'], $where, $orderby, $limit, $passo);
	// creo l'array (header => campi) per l'ordinamento dei record
	$headers_catmer = array("Codice"      => "codice",
							"Descrizione" => "descri",
							"% Ricarico"  => "ricarico",
							"Annotazioni" => "annota",
							"Cancella"    => ""
							);
	$linkHeaders = new linkHeaders($headers_catmer);
	$linkHeaders -> output();
?>
        	</tr>
        </thead>
        <tbody>
<?php
while ($a_row = gaz_dbi_fetch_array($result)) {
?>		<tr class="FacetDataTD">
			<td align="center">
				<a class="btn btn-xs btn-edit" href="admin_catmer.php?Update&codice=<?php echo $a_row["codice"]; ?>">
					<i class="glyphicon glyphicon-edit"></i>&nbsp;<?php echo $a_row["codice"];?>
				</a>
			</td>
			<td>
				<span class="gazie-tooltip col-xs-12" data-type="catmer-thumb" data-id="<?php echo $a_row['codice']; ?>" data-title="<?php echo $a_row['annota']; ?>" data-maxsize="360"><?php echo $a_row["descri"]; ?></span>
			</td>
			<td align="center"><?php echo $a_row["ricarico"];?></td>
			<td align="center"><?php echo $a_row["annota"];?></td>
			<td align="center">
				<a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $a_row["codice"];?>" catdes="<?php echo $a_row["descri"]; ?>">
					<i class="glyphicon glyphicon-trash"></i>
				</a>
			</td>
		</tr>
<?php
}
?>
    		</tbody>
        </table></div>
    <?php
require("../../library/include/footer.php");
?>
