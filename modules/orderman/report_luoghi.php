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
// gestione campi o appezzamenti di terreno

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
		$("p#iddescri").html($(this).attr("luodes"));
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
						data: {'type':'luoghi',ref:id},
						type: 'POST',
						url: '../orderman/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_luoghi.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<div align="center" class="FacetFormHeaderFont">Luoghi di produzione aziendali</div>
<?php
$recordnav = new recordnav($gTables['campi'], $where, $limit, $passo);
$recordnav -> output();
?>
<form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>luogo di produzione:</b></p>
        <p>codice:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
  <div class="table-responsive">
    <table class="Tlarge table table-striped table-bordered">
    	<thead>
            <tr>
                <td></td>
                <td class="FacetFieldCaptionTD">Descrizione:
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
	$groupby= "codice";
	$result = gaz_dbi_dyn_query ('*', $gTables['campi']. ' LEFT JOIN ' . $gTables['movmag'] . ' ON ' . $gTables['movmag'] . '.campo_impianto = ' . $gTables['campi'] . '.codice', $where, $orderby, $limit, $passo, $groupby);
	// creo l'array (header => campi) per l'ordinamento dei record
	$headers_campi = array("Codice"      => "codice",
							"Descrizione" => "descri",
							"Immagine" => "image",

							"Note" => "annota",

							"Mappa" => "web_url",

							"Cancella"    => ""
							);
	$linkHeaders = new linkHeaders($headers_campi);
	$linkHeaders -> output();
?>
        	</tr>
        </thead>

        <tbody>
<?php


while ($a_row = gaz_dbi_fetch_array($result)) {
?>		<tr class="FacetDataTD">
			<td class="text-center">
				<a class="btn btn-xs btn-edit" href="admin_luoghi.php?Update&codice=<?php echo $a_row["codice"]; ?>">
					<i class="glyphicon glyphicon-edit"></i>&nbsp;<?php echo $a_row["codice"];?>
				</a>
			</td>
			<td>
				<span class="gazie-tooltip" data-type="campi-thumb" data-id="<?php echo $a_row['codice']; ?>" data-title="<?php echo $a_row['annota']; ?>"><?php echo $a_row["descri"]; ?></span>
			</td>

			<td align="center"> <img height=200 style="cursor: -moz-zoom-in;"
			<?php echo 'src="data:image/jpeg;base64,'.base64_encode( $a_row['image'] ).'"';?>
			onclick="this.height=500;" ondblclick="this.height=200;" title="<?php echo $a_row["descri"]; ?>" alt="IMG non presente" /></td>

			<td align="center"><?php echo $a_row["annota"];?></td>

			<td align="center"><a  href="javascript:;" onclick="window.open('<?php echo($a_row["web_url"])?>', 'titolo', 'width=800, height=400, left=80%, top=80%, resizable, status, scrollbars=1, location');">
			<i class="glyphicon glyphicon-picture"></i>
			</a></td>

			<td align="center">
			<?php
			$used_from=explode(',',is_string($a_row['used_from_modules'])?$a_row['used_from_modules']:'');
			if (intval ($a_row['campo_impianto'])>0) {
				?>
				<button title="Luogo non cancellabile perche' ha movimenti di magazzino" class="btn btn-xs   disabled"><i class="glyphicon glyphicon-trash"></i></button>
				<?php
			} elseif (count($used_from)==1 AND ($used_from[0]=="orderman" OR  $used_from[0]=="" OR $used_from[0]=="NULL")){ // posso cancellare perché non ci sono moduli specifici associati
				?>
				<a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $a_row["codice"];?>" luodes="<?php echo $a_row["descri"]; ?>">
					<i class="glyphicon glyphicon-trash"></i>
				</a>
				<?php
			} else {
				?>
				<button title="Luogo non cancellabile perche' ci sono dei moduli specifici associati" class="btn btn-xs   disabled"><i class="glyphicon glyphicon-trash"></i></button>
				<?php
			}
			echo "</td></tr>";
}
?>
		</tbody>
	</table>
</div>
</form>
<form method="post" action="stampa_luoghi.php">
	<div class="FacetFooterTD text-center col-xs-12">
	<input type="submit" class="btn btn-warning" name="print" value="<?php echo $script_transl['print'];?>">
  </div>
</form>
<?php
require("../../library/include/footer.php");
?>
