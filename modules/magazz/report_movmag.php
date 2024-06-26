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
$msg = "";
require("../../library/include/header.php");
$script_transl = HeadMain();
require("lang.".$admin_aziend['lang'].".php");

// campi ammissibili per la ricerca
$search_fields = [
	'movimento'
        => "{$gTables['movmag']}.id_mov = %d",
	'datareg'
        => "datreg = '%s'",
	'causale'
        => "caumag LIKE '%s%%'",
	'documento'
        => "desdoc LIKE '%%%s%%'",
	'articolo'
        => "artico LIKE '%%%s%%'",
	'lotto'
        => "id_lotmag LIKE '%%%s%%'"
];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
            "n.ID" => 'id_mov',
            $script_transl[4] => 'datreg',
            $strScript["admin_movmag.php"][2] => 'caumag',
            'Magazzino' => 'id_warehouse',
            $script_transl[8] => "",
            $script_transl[5] => 'artico',
            $script_transl[11] => 'identifier',
            $script_transl[6] => "",
            $script_transl[7] => "",
            $script_transl['delete'] => ""
);

echo "<div align='center' class='FacetFormHeaderFont '>{$script_transl[3]}{$script_transl[0]}</div>";

$t = new TableSorter($gTables['movmag'], $passo, ['id_mov' => 'desc']);
$t->output_navbar();

?>
<script>
$(function() {
    $("#datareg").datepicker({ dateFormat: 'yy-mm-dd',showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("movdes"));
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
						data: {'type':'movmag',ref:id},
						type: 'POST',
						url: '../magazz/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_movmag.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<form method="GET" class="clean_get">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
		<p><b>movimento magazzino:</b></p>
		<p>Codice</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
	<div class="table-responsive">
	<table class="Tlarge table table-striped table-bordered table-condensed">
	<tr>
		<td class="FacetFieldCaptionTD">
		  <input type="text" name="movimento" placeholder="Movimento" class="input-sm form-control"  value="<?php echo (isset($movimento))? $movimento : ""; ?>" maxlength ="6" >
		</td>
		<td class="FacetFieldCaptionTD">
		  <input type="text" name="datareg" id="datareg" placeholder="Data registrazione" class="input-sm form-control"  value="<?php echo (isset($datareg))? $datareg : ""; ?>" maxlength ="10">
        </td>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="causale" placeholder="<?php echo $strScript['admin_movmag.php'][2];?>" class="input-sm form-control" value="<?php echo (isset($causale))? $causale : ""; ?>" maxlength="6" tabindex="1">
		</td>
		<td class="FacetFieldCaptionTD">
		</td>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="documento" placeholder="<?php echo $script_transl[8];?>" class="input-sm form-control" value="<?php echo (isset($documento))? $documento : ""; ?>" maxlength="15" tabindex="1">
		</td>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="articolo" placeholder="<?php echo $script_transl[5];?>" class="input-sm form-control" value="<?php echo (isset($articolo))? $articolo : ""; ?>" maxlength="15" tabindex="1">
		</td>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="lotto" placeholder="<?php echo "ID ",$script_transl[11];?>" class="input-sm form-control" value="<?php echo (isset($lotto))? $lotto : ""; ?>" maxlength="15" tabindex="1">
		</td>
		<td class="FacetFieldCaptionTD" colspan="3">
			<input type="submit" class="btn btn-xs btn-default" name="search" value="<?php echo $script_transl['search'];?>" tabindex="1" onClick="javascript:document.report.all.value=1;">
			<a class="btn btn-xs btn-default" href="?">Reset</a>
			<?php  $t->output_order_form(); ?>
		</td>
	</tr>

<?php
$table = $gTables['movmag']." LEFT JOIN ".$gTables['caumag']." on (".$gTables['movmag'].".caumag = ".$gTables['caumag'].".codice)
         LEFT JOIN ".$gTables['warehouse']." ON ".$gTables['movmag'].".id_warehouse = ".$gTables['warehouse'].".id
         LEFT JOIN ".$gTables['artico']." ON (".$gTables['movmag'].".artico = ".$gTables['artico'].".codice)
		 LEFT JOIN ".$gTables['clfoco']." ON (".$gTables['movmag'].".clfoco = ".$gTables['clfoco'].".codice)
		 LEFT JOIN ".$gTables['lotmag']." ON (".$gTables['movmag'].".id_lotmag = ".$gTables['lotmag'].".id)";
		/* Antonio Germani - momentaneamente commentato, di comune accordo con Antonio de Vincentiis, perché causa un ambiguous column names con id_lotmag quando si utilizza l'ID lotto come filtro
		LEFT JOIN ".$gTables['orderman']." ON (".$gTables['movmag'].".id_orderman = ".$gTables['orderman'].".id)
		*/
$result = gaz_dbi_dyn_query ($gTables['movmag'].".*, ".$gTables['warehouse'].".name AS desmag, ".$gTables['artico'].".descri AS descart, ".$gTables['caumag'].".descri AS descau, ".$gTables['lotmag'].".*", $table, $t->where, $t->orderby, $t->getOffset(), $t->getLimit());

echo '<tr>';
$t->output_headers();
echo '</tr>';
$anagrafica = new Anagrafica();

$tot_movimenti = 0;

/*
QUESTA E' LA MATRICE ORIGINALE (PERSONALIZZABILE) DELLA RIGA 'report_movmag_ref_doc' della tabella "gaz_config" in formato json e serve per ottenere i riferimenti al documento di origine in base al "tipdoc" di origine ed al id_rif del movimento di magazzino passata alla funzione NOMEMODULO_prepare_ref_doc_movmag contenuta nel file incluso e presente sul modulo stesso e sempre di nome "prepare_ref_doc_movmag.php"
{
"ADT":"acquis",
"AFA":"acquis",
"AFC":"acquis",
"DDR":"acquis",
"ADT":"acquis",
"AFT":"acquis",
"DDL":"acquis",
"RDL":"acquis",
"DDR":"acquis",
"VCO":"vendit",
"VRI":"vendit",
"DDT":"vendit",
"FAD":"vendit",
"FAI":"vendit",
"FAA":"vendit",
"FAF":"vendit",
"FAQ":"vendit",
"FAP":"vendit",
"FNC":"vendit",
"FND":"vendit",
"DDV":"vendit",
"RDV":"vendit",
"RPL":"vendit",
"DDY":"vendit",
"DDS":"vendit",
"DDX":"vendit",
"DDZ":"vendit",
"DDW":"vendit",
"DDD":"vendit",
"DDJ":"vendit",
"DDC":"vendit",
"DDM":"vendit",
"DDO":"vendit",
"VPR":"vendit",
"VOR":"vendit",
"VOW":"vendit",
"VOG":"vendit",
"CMR":"vendit",
"CAM":"camp",
"PRO":"orderman",
"MAG":"magazz"
}
*/
$hrefdoc = json_decode(gaz_dbi_get_row($gTables['config'], 'variable', 'report_movmag_ref_doc')['cvalue']);
$rshref=get_object_vars($hrefdoc);

while ($r = gaz_dbi_fetch_array($result)) {
    // richiamo il file del modulo che ha generato il movimento di magazzino per avere le informazioni sul documento genitore
    require_once("../".$rshref[$r['tipdoc']]."/prepare_ref_doc_movmag.php");
    $funcn=preg_replace('/[0-9]+/', '', $rshref[$r['tipdoc']]);
    $funcn=$funcn.'_prepare_ref_doc';
    $r['id_rif']=($r['id_orderman']>0 && $r['tipdoc']=="PRO")?$r['id_orderman']:$r['id_rif'];
    $r['id_rif']=($r['id_rif']==0 && $r['tipdoc']=="MAG")?$r['id_mov']:$r['id_rif'];
    $docdata=$funcn($r['tipdoc'],$r['id_rif']);
    $partner = $anagrafica->getPartner($r['clfoco']);
    $title=($partner)?$partner['ragso1']." ".$partner['ragso2']:'';
	$descri=$r['descart'];
	if ($r['expiry']>0){
		$expiry="Scad.: ".gaz_format_date($r['expiry']);
	} else {
		$expiry="";
	}
    $valore = CalcolaImportoRigo($r['quanti'], $r['prezzo'], $r['scorig']) ;
    $valore = CalcolaImportoRigo(1, $valore, $r['scochi']) ;
    echo "<tr>";

    echo "<td>";
	if (($r['id_rif']==0||$r['tipdoc']=="MAG"||$r['tipdoc']=="PRO") && intval($r['id_orderman'])==0){
        // in caso di movimento proveniente da produzione forzo l'id_rif con id_orderman
		echo "<a class=\"btn btn-xs btn-default\" href=\"admin_movmag.php?id_mov=".$r["id_mov"]."&Update\" title=\"".ucfirst($script_transl['update'])."!\"><i class=\"glyphicon glyphicon-edit text-success\"></i> ".$r["id_mov"]."</a> &nbsp</td>";
    } else {
		echo "<button class=\"btn btn-xs btn-default disabled\" title=\"Questo movimento puo essere modificato solo nel documento che lo ha creato\"> ".$r["id_mov"]."</button> &nbsp</td>";
	}
	echo "<td align=\"center\">".gaz_format_date($r["datreg"])."  </td>";
  echo "<td align=\"center\">".$r["caumag"]." - ".$r["descau"]."</td>";
  echo '<td align="center">'.($r['desmag']==''?'Sede':substr($r['desmag'],0,25))."</td>";
  if (isset($hrefdoc->{$r['tipdoc']}) && $r['id_rif'] > 0){ // vedi sopra quando si vuole riferire ad un documento genitore di un modulo specifo
    echo '<td title="'.$title.'"><a href="'.$docdata['link'].'">'.$r['desdoc']." ".$script_transl[9]." ".gaz_format_date($r["datdoc"])."</a></td>";
  } elseif(intval($r['id_orderman'])==0) {
    echo '<td title="'.$title.'"><a href="admin_movmag.php?id_mov='.$r["id_mov"].'&Update">'.$r['desdoc']." ".$script_transl[9]." ".gaz_format_date($r["datdoc"])."</a></td>";
  } else{
    echo '<td title="'.$title.'"><a href="../orderman/admin_orderman.php?Update&codice='.intval($r['id_orderman']).'">'.$r['desdoc'].' '.$script_transl[9].' '.gaz_format_date($r["datdoc"]).'</a></td>';
  }

  echo "<td align=\"center\"><p data-toggle=\"tooltip\" data-placement=\"auto\" title=\"$descri\"><a href=\"select_schart.php?id=".$r["artico"]."\" >".$r["artico"]."</a></p></td>";
  if ($r['id']>0) {
    echo "<td align=\"center\"><p data-toggle=\"tooltip\" data-placement=\"auto\" title=\"$expiry\">"."ID:".$r['id']." - ".$r['identifier']."</td>";
  } else {
    echo "<td></td>";
  }
  echo "<td align=\"center\">".gaz_format_quantity($r["quanti"],1,$admin_aziend['decimal_quantity'])."</td>";
  echo "<td align=\"right\">".gaz_format_number($valore)." </td>";
  echo "<td align=\"center\">";
	if (($r['tipdoc'] == "MAG" OR $r['tipdoc'] == "INV") && intval($r['id_orderman'])==0){
		?>
		<a class="btn btn-xs  btn-elimina dialog_delete" title="Elimina movimento" ref="<?php echo $r['id_mov'];?>" movdes="<?php echo $r['descau']; ?>">
		<i class="glyphicon glyphicon-trash"></i>
		</a>
		<?php
	} else {
		?>
		<button title="Questo movimento puo essere eliminato solo dal documento che lo ha creato" class="btn btn-xs   disabled"><i class="glyphicon glyphicon-trash"></i></button>
		<?php
	}
	echo "</td>";
    echo "</tr>";
	/** ENRICO FEDELE */
	/* Incremento il totale */
	$tot_movimenti += $valore;
	/** ENRICO FEDELE */
}
	/** ENRICO FEDELE */
	/* Stampo il totale */
	//if($tot_movimenti!=0) {	//	Inizialmente avevo pensato di stampare il totale solo se diverso da zero, ma la cosa risulta fuorviante in alcuni casi
								//	meglio stamparlo sempre
		echo "<tr>
				<td colspan=\"8\" class=\"FacetFieldCaptionTD\" align=\"right\"><strong>TOTALE</strong></td>
				<td class=\"FacetFieldCaptionTD\" align=\"right\"><strong>".gaz_format_number($tot_movimenti)."</strong></td>
				<td class=\"FacetFieldCaptionTD\"> </td>
			  </tr>";
	//}
	/** ENRICO FEDELE */
?>
     </table>
	</div>
</form>
<script>
$(document).ready(function(){
	$('[data-toggle="tooltip"]').tooltip();
});
</script>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>
