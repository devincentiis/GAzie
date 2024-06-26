<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
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
// gestione fitofarmaci

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
   $where = "cod_art like '%'";
   $passo = 100000;
} else {
   if (isset($_GET['auxil'])) {
      $where = "cod_art like '".addslashes($_GET['auxil'])."%'";
   }
}

if (!isset($_GET['auxil'])) {
   $auxil = "";
   $where = "cod_art like '".addslashes($auxil)."%'";
}
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("codart"));
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
						data: {'type':'usefito',ref:id},
						type: 'POST',
						url: '../camp/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_fitofarmaci.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<div align="center" class="FacetFormHeaderFont">Modalità d'uso dei fitofarmaci</div>
<?php
$recordnav = new recordnav($gTables['camp_uso_fitofarmaci'], $where, $limit, $passo);
$recordnav -> output();
?>
<form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
		<p><b>modo d'uso fitofarmaco:</b></p>
		<p>Codice</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
    <table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
    	<thead>
            <tr>
                <td></td>
                <td class="FacetFieldCaptionTD">Nome fitofarmaco:
                    <input type="text" name="auxil" value="<?php if ($auxil != "&all=yes") echo $auxil; ?>" maxlength="6" tabindex="1" class="FacetInput" />
					<input type="submit" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value=1;" />

                    <input type="submit" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value=1;" />

                </td>
                <td align="center">
				<a class="btn btn-xs btn-default" href="admin_usofito.php?insert" title="Aggiungi nuovo uso fitofarmaco">
					<i class="glyphicon glyphicon-plus-sign"></i> Aggiungi
				</a>
			</td>
            </tr>
            <tr>
<?php
	$result = gaz_dbi_dyn_query ('*', $gTables['camp_uso_fitofarmaci'], $where, $orderby, $limit, $passo);
	// creo l'array (header => campi) per l'ordinamento dei record
	$headers_avv = array("ID"      => "id",
							"Nome fitofarmaco" => "cod_art",
							"Coltura" => "id_colt",
							"Avversità" => "id_avv",
							"Dose" => "dose",
							"Tempo sosp." => "tempo_sosp"
							);
	$linkHeaders = new linkHeaders($headers_avv);
	$linkHeaders -> output();
?>
        	</tr>
        </thead>
        <tbody>
<?php
while ($a_row = gaz_dbi_fetch_array($result)) {
?>		<tr class="FacetDataTD">
			<td>
				<a class="btn btn-xs btn-success btn-block" title="Modifica" href="admin_usofito.php?Update&id=<?php echo $a_row["id"]; ?>">
					<i class="glyphicon glyphicon-edit"></i>&nbsp;<?php echo $a_row["id"];?>
				</a>
			</td>
			<td>
				<span class="gazie-tooltip" data-type="catmer-thumb" data-id="<?php echo $a_row['id']; ?>" data-title="<?php echo $a_row['cod_art']; ?>"><?php echo $a_row["cod_art"]; ?></span>
			</td>
			<?php
			$res2 = gaz_dbi_get_row($gTables['artico'], 'codice', $a_row['cod_art']);
      if (!isset($res2)){
        ?>
        <td>
          <span>ERRORE: questo prodotto non è presente fra le merci di magazzino</span>

        <?php
      }else{
        $unimis=$res2['uniacq'];
        $res = gaz_dbi_get_row($gTables['camp_colture'], 'id_colt', $a_row['id_colt']);
        ?>
        <td>
          <span><?php echo $res["nome_colt"]; ?></span>
        </td>
        <?php
        $res = gaz_dbi_get_row($gTables['camp_avversita'], 'id_avv', $a_row['id_avv']);
        ?>
        <td>
          <span><?php echo $res["nome_avv"]; ?></span>
        </td>
        <td>
          <?php
          if (floatval($a_row["dose"])>0){
            ?>
            <span><?php echo number_format ($a_row["dose"],$admin_aziend['decimal_price'], ',', '')," ",$unimis,"/ha"; ?></span>&nbsp;&nbsp;
            <?php
          }
          if (isset($a_row["dose_hl"]) && floatval($a_row["dose_hl"])>0){
            ?>
            <span><?php echo number_format ($a_row["dose_hl"],$admin_aziend['decimal_price'], ',', '')," ",$unimis,"/hl"; ?></span>
            <?php
          }
        }
        ?>
			</td>
			<td>
				<span><?php echo $a_row["tempo_sosp"]," gg"; ?></span>
			</td>
			<td align="center">
				<a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $a_row['id'];?>" codart="<?php echo $a_row['cod_art']; ?>">
					<i class="glyphicon glyphicon-trash"></i>
				</a>
			</td>
		</tr>
<?php
}
?>
    	</tbody>
    </table>
    <?php
?>

<?php
require("../../library/include/footer.php");
?>
