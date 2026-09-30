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
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
$msg = "";$mostra_qdc="";
require("../../library/include/header.php");
$script_transl = HeadMain();
require("lang.".$admin_aziend['lang'].".php");

if (isset($_GET['all'])) {
	$where = "";
	$passo = 100000;
} else {
	$implode = array();
	if (isset($_GET['movimento']) && !empty($_GET['movimento'])) {
		$movimento = $_GET['movimento'];
		$implode[] = $gTables['movmag'].".id_mov = " . $_GET['movimento'];
	}

	if (isset($_GET['causale']) && !empty($_GET['causale'])) {
		$causale = $_GET['causale'];
		$implode[] = "caumag LIKE '" . $_GET['causale'] . "%'";
	}

	if (isset($_GET['campo']) && !empty($_GET['campo'])) {
		$campo = $_GET['campo'];
		$implode[] = "campo_impianto LIKE '%".$_GET['campo']."%'";
	}

	if (isset($_GET['articolo']) && !empty($_GET['articolo'])) {
		$articolo = $_GET['articolo'];
		$implode[] = "artico LIKE '%".$_GET['articolo']."%'";
	}

	if (isset($_GET['avversita']) && !empty($_GET['avversita'])) {
		$avversita = $_GET['avversita'];
		$implode[] = "id_avversita LIKE '%".$_GET['avversita']."%'";
	}

	$where = implode(" AND ", $implode);
}
// escludo i movimenti Acqua dal report
if (strlen($where)>1){
	$where=$where." AND type_mov = '1' AND ". $gTables['movmag'] .".id_rif >= ". $gTables['movmag'] .".id_mov";
} else {
	$where=" type_mov = '1' AND ". $gTables['movmag'] .".id_rif >= ". $gTables['movmag'] .".id_mov";
}

if (!isset($_GET['flag_order']) || empty($_GET['flag_order'])) {
   $orderby = "datdoc desc";
   $field = 'id_mov';
   $flag_order = 'DESC';
   $flagorpost = 'ASC';
}
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("caudes"));
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
						data: {'type':'campmovmag',ref:id},
						type: 'POST',
						url: '../camp/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./camp_report_movmag.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<div align="center" class="FacetFormHeaderFont "><?php echo $script_transl[14]; ?></div>
<form method="GET">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
		<p><b>movimento quaderno:</b></p>
		<p>Codice</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
	<div class="table-responsive">
		<table class="Tlarge table table-striped table-bordered table-condensed">
			<tr>
				<td class="FacetFieldCaptionTD">
				<input type="text" name="movimento" placeholder="Movimento" class="input-sm form-control"  value="<?php echo (isset($movimento))? $movimento : ""; ?>" maxlength ="6" tabindex="1" class="FacetInput">
				</td>
				<td class="FacetFieldCaptionTD"></td>
				<td class="FacetFieldCaptionTD"></td>
				<td class="FacetFieldCaptionTD">
					<input type="text" name="causale" placeholder="<?php echo "ID ",$strScript['camp_admin_movmag.php'][2];?>" class="input-sm form-control" value="<?php echo (isset($causale))? $causale : ""; ?>" maxlength="6" tabindex="1" class="FacetInput">
				</td>
				<!-- Antonio Germani - inserisco l'intestazione cerca per campi di coltivazione e avversità -->
				<td class="FacetFieldCaptionTD">
					<input type="text" name="campo" placeholder="<?php echo "ID ",$script_transl[11];?>" class="input-sm form-control" value="<?php echo (isset($campo))? $campo : ""; ?>" maxlength="" tabindex="1" class="FacetInput">
				</td>
				<td class="FacetFieldCaptionTD"></td>
				<td class="FacetFieldCaptionTD"></td>
				<td class="FacetFieldCaptionTD">
					<input type="text" name="articolo" placeholder="<?php echo $script_transl[5];?>" class="input-sm form-control" value="<?php echo (isset($articolo))? $articolo : ""; ?>" maxlength="15" tabindex="1" class="FacetInput">
				</td>
				<td class="FacetFieldCaptionTD"></td>
				<td class="FacetFieldCaptionTD"></td>
				<td class="FacetFieldCaptionTD">
					<input type="text" name="avversita" placeholder="<?php echo "ID ",$script_transl[7];?>" class="input-sm form-control" value="<?php echo (isset($avversita))? $avversita : ""; ?>" maxlength="15" tabindex="1" class="FacetInput">
				</td>
				<td class="FacetFieldCaptionTD"></td>
				<td class="FacetFieldCaptionTD" colspan="4">
					<input type="submit" class="btn btn-xs btn-default" name="search" value="<?php echo $script_transl['search'];?>" tabindex="1" onClick="javascript:document.report.all.value=1;">
					<input type="submit" class="btn btn-xs btn-default" name="all" value="<?php echo $script_transl['vall']; ?>" onClick="javascript:document.report.all.value=1;">
				</td>
			</tr>
<?php
$table = $gTables['movmag']." LEFT JOIN ".$gTables['caumag']." on (".$gTables['movmag'].".caumag = ".$gTables['caumag'].".codice)
         LEFT JOIN ".$gTables['campi']." ON (".$gTables['movmag'].".campo_impianto = ".$gTables['campi'].".codice)
		 LEFT JOIN ".$gTables['artico']." ON (".$gTables['movmag'].".artico = ".$gTables['artico'].".codice)
		 LEFT JOIN ".$gTables['camp_colture']." ON (".$gTables['movmag'].".id_colture = ".$gTables['camp_colture'].".id_colt)
         LEFT JOIN ".$gTables['rigdoc']." ON (".$gTables['movmag'].".id_rif = ".$gTables['rigdoc'].".id_rig)";
		 $result = gaz_dbi_dyn_query ($gTables['movmag'].".*, ".$gTables['camp_colture'].".nome_colt, ".$gTables['campi'].".ricarico AS superf, ".$gTables['campi'].".descri AS descamp, ".$gTables['caumag'].".descri AS descau, ".$gTables['rigdoc'].".id_tes AS testata, " .$gTables['artico'].".unimis, " .$gTables['artico'].".mostra_qdc"
		 , $table, $where, $orderby, $limit, $passo);// acquisisco solo i movimenti con type_mov=1, cioè generati dal modulo di campagna
// creo l'array (header => campi) per l'ordinamento dei record
$headers_mov = array  (
            "n.ID" => "id_mov",
			$script_transl[4] => "datdoc",
            $script_transl[15] => "datreg",
            $strScript["camp_admin_movmag.php"][2] => "caumag",
			$script_transl[11] => "",
			$script_transl[12] => "",
			$script_transl[13] => "",
            $script_transl[5] => "artico",
            $script_transl[6] => "",
			$script_transl[17] => "",
            $script_transl[7] => "",
			$script_transl[8] => "",
			$script_transl[16] => "",
            $script_transl['delete'] => ""
            );
$linkHeaders = new linkHeaders($headers_mov);
$linkHeaders -> output();
$recordnav = new recordnav($gTables['movmag'], $where, $limit, $passo);
$recordnav -> output();

/** ENRICO FEDELE */
/* Inizializzo la variabile */
$tot_movimenti = 0;
/** ENRICO FEDELE */

while ($a_row = gaz_dbi_fetch_array($result)) {

		if ($rowanagra = gaz_dbi_get_row($gTables['anagra'], "id", $a_row['clfoco'])){
		$operatore =  $rowanagra['ragso1']." ".$rowanagra['ragso2'];
		} else {
			$operatore=$a_row["adminid"];
		}
		$valore = CalcolaImportoRigo($a_row['quanti'], $a_row['prezzo'], $a_row['scorig']) ;
		$valore = CalcolaImportoRigo(1, $valore, $a_row['scochi']) ;
		$mostra_qdc=$a_row["mostra_qdc"];
		if ($a_row["id_rif"] !== $a_row["id_mov"]){ // se il movimento è connesso con un rigo acqua, carico il movmag rigo acqua
			$acqua = gaz_dbi_get_row($gTables['movmag'], 'id_mov', $a_row['id_rif']);
			$unimis_acqua = gaz_dbi_get_row($gTables['artico'], 'codice', $acqua['artico'])['unimis'];
		} else {
			$acqua['quanti']="";
			$unimis_acqua="";
		}
		echo "<tr>\n";
		echo "<td class=\"FacetDataTD\"><a class=\"btn btn-xs btn-default\" href=\"camp_admin_movmag.php?id_mov=".$a_row["id_mov"]."&Update\" title=\"".ucfirst($script_transl['update'])."!\"><i class=\"glyphicon glyphicon-edit text-success\"></i>&nbsp;".$a_row["id_mov"]."</a> &nbsp</td>";
		echo "<td class=\"FacetDataTD\" align=\"center\">".gaz_format_date($a_row["datreg"])." &nbsp;</td>\n";
		echo "<td class=\"FacetDataTD\" align=\"center\">".gaz_format_date($a_row["datdoc"])." &nbsp;</td>\n";
		echo "<td class=\"FacetDataTD\" align=\"center\">".$a_row["caumag"]." - ".$a_row["descau"]."</td>\n";

		// Antonio Germani inserico colonna campi di coltivazione, superficie, coltura
		echo "<td class=\"FacetDataTD\" align=\"center\">".$a_row['campo_impianto']." - ".$a_row['descamp']." &nbsp;</td>\n";
    $a_row_res=(is_null($a_row["superf"]))?'':str_replace('.', ',',$a_row["superf"]);
		echo "<td class=\"FacetDataTD\" align=\"center\">".$a_row_res." &nbsp;</td>\n";
		echo "<td class=\"FacetDataTD\" align=\"center\">".$a_row['id_colture']." - ".$a_row["nome_colt"]." &nbsp";
		if ($data=json_decode($a_row['custom_field'],true)){// se c'è un json nel custom_field
			if (is_array($data['camp']) AND strlen($data['camp']['fase_fenologica'])>0){ // se è riferito al modulo camp
				echo "<br>Fase fenologica: ", $data['camp']['fase_fenologica'];
			}
		}
    	echo "</td>\n";
		echo "<td class=\"FacetDataTD\" align=\"center\">".$a_row["artico"]." &nbsp;</td>\n";
		echo "<td class=\"FacetDataTD\" align=\"center\">".gaz_format_quantity($a_row["quanti"],1,$admin_aziend['decimal_quantity'])." ".$a_row["unimis"]."</td>\n";
		if ($acqua['quanti']>0){
			echo "<td class=\"FacetDataTD\" align=\"center\">".gaz_format_quantity($acqua['quanti'],1,$admin_aziend['decimal_quantity'])." ". $unimis_acqua ."</td>\n";
		} else {
			echo "<td class=\"FacetDataTD\" align=\"center\"></td>\n";
		}
		$res = gaz_dbi_get_row($gTables['camp_avversita'], 'id_avv', $a_row['id_avversita']);
		echo "<td class=\"FacetDataTD\" align=\"left\">", ($a_row)?$a_row['id_avversita']:0 ," - ", ($res)?$res["nome_avv"]:'' ," </td>\n";

		if ($a_row['id_rif'] == 0 OR $a_row['tipdoc'] == "CAM") {
			echo "<td class=\"FacetDataTD\" align=\"center\" title=\"\">", $a_row['desdoc'] ,"</td>\n";
		} else {
			if ($a_row['tipdoc'] == "ADT"
			|| $a_row['tipdoc'] == "AFA"
			|| $a_row['tipdoc'] == "AFT"
			|| $a_row['tipdoc'] == "AFC") {
            echo "<td class=\"FacetDataTD\" align=\"center\" title=\"\"><a href=\"../acquis/admin_docacq.php?Update&id_tes=".$a_row['testata']."\">".$a_row['desdoc']." ".$script_transl[9]." ".gaz_format_date($a_row["datdoc"])."</a></td>\n";
			} else {
				echo "<td class=\"FacetDataTD\" align=\"center\" title=\"\"><a href=\"../vendit/admin_docven.php?Update&id_tes=".$a_row['testata']."\">".$a_row['desdoc']." ".$script_transl[9]." ".gaz_format_date($a_row["datdoc"])."</a></td>\n";
			}
		}

		echo "<td class=\"FacetDataTD\" align=\"right\">".$operatore." </td>\n";

		echo "<td class=\"FacetDataTD\" align=\"center\">";

			?>
			<a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $a_row['id_mov'];?>" caudes="<?php echo $a_row['descau']; ?>">
				<i class="glyphicon glyphicon-trash"></i>
			</a>
			<?php

		echo "</td></tr>\n";
		/* Incremento il totale */
		$tot_movimenti += $valore;


} // end wile

echo "<tr>
	<td colspan=\"9\" class=\"FacetFieldCaptionTD\" align=\"right\"></td>
	</tr>";

?>
        </form>
    </table>
</div>
<a href="https://programmisitiweb.lacasettabio.it/quaderno-di-campagna/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:20%; z-index:2000;"> Registro di campagna è un modulo di Antonio Germani</a>
<?php
require("../../library/include/footer.php");
?>
