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
$admin_aziend=checkAdmin(8);
require("../../library/include/header.php");
$script_transl = HeadMain();

// cerco gli ultimi scontrini emessi distinguendo per RT e per data
$last_tickets=[];
$rs1 = gaz_dbi_query("SELECT id_contract, datemi FROM ".$gTables['tesdoc']." WHERE tipdoc='VCO' GROUP BY datemi , id_contract");
while ($r1 = gaz_dbi_fetch_array($rs1)) {
  $rs2 = gaz_dbi_query("SELECT id_tes FROM ".$gTables['tesdoc']." WHERE tipdoc='VCO' AND datemi='".$r1['datemi']."' AND id_contract='".$r1['id_contract']."' ORDER BY numdoc DESC LIMIT 0,1");
  while ($r2 = gaz_dbi_fetch_array($rs2)) {
    $last_tickets[] = $r2['id_tes'];// accumulo gli id_tes relativi agli ultimi scontrini del giorno che quindi potranno essere eliminati se sbagliati
  }
}

$search_fields = [
    'tipo' => "tipdoc = '%s'",
    'id_tes' => "{$gTables['tesdoc']}.id_tes LIKE %d",
	'sezione' => "{$gTables['tesdoc']}.seziva = %d",
	'numdoc' => "{$gTables['tesdoc']}.numdoc = %d",
    'anno' => "YEAR({$gTables['tesdoc']}.datemi) = %d",
	'cliente' => "{$gTables['anagra']}.ragso1 LIKE '%%%s%%'",
	'cash' => "{$gTables['tesdoc']}.id_contract = %d"
];
// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
    $script_transl['id'] => 'id_tes',
    $script_transl['date'] => "YEAR({$gTables['tesdoc']}.datemi)",
    "Registratore" => "id_contract", // registratore telematico
    $script_transl['seziva']=>"seziva",
    $script_transl['number']=>"numdoc",
    $script_transl['invoice'] => "cliente",
    $script_transl['pagame'] => "",
    $script_transl['status'] => "",
    $script_transl['amount'] => "",
    'Cert.' => "",
    $script_transl['delete'] => "",
);

$tablejoin = $gTables['tesdoc']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['tesdoc'].".clfoco = ".$gTables['clfoco'].".codice
                                  LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id
                                  LEFT JOIN ".$gTables['cash_register']." ON ".$gTables['tesdoc'].".id_contract = ".$gTables['cash_register'].".id_cash
                                  LEFT JOIN ".$gTables['pagame']." ON ".$gTables['tesdoc'].".pagame = ".$gTables['pagame'].".codice";

$ts = new TableSorter(
    $tablejoin,
    $passo,
    ['datemi'=>'desc','id_contract'=>'desc','seziva'=>'desc','numdoc'=>'desc'],
    ['tipo' => 'VCO']);
?>
    <div class="text-center"><h3><?php echo $script_transl['title'];?></h3></div>

<?php
$ts->output_navbar();

?>
<script>
function confirFae(link){
	tes_id = link.id.replace("doc1", "");
	$.fx.speeds._default = 500;
	var new_title = "Genera file XML per fattura n." + $("#doc1"+tes_id).attr("n_fatt");
	var n_reinvii = parseInt($("#doc1"+tes_id).attr("fae_n_reinvii"))+1;
	$("p#fae1").html("nome file: " + $("#doc1"+tes_id).attr("fae_attuale"));
	$("span#fae2").html("<a href=\'"+link.href+"&reinvia\'> " + $("#doc1"+tes_id).attr("fae_reinvio")+ " (" + n_reinvii.toString() + "° reinvio) </a>");
	$("#dialog1").dialog({
	  title: new_title,
      modal: "true",
      show: "blind",
      hide: "explode",
      buttons: {
        "Conferma ": function() {
            window.location.href = link.href;
            $(this).dialog("close");
        },
        " Elimina ": function() {
            $(this).dialog("close");
        }
      }
    });
	$("#dialog1").dialog( "open" );
}

$(function() {
    $("#dialog1").dialog({autoOpen: false });
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("datemi"));
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
						data: {'type':'docven',id_tes:id},
						type: 'POST',
						url: '../vendit/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_scontr.php");
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
    $("html, body").delay(100).animate({scrollTop: $('#framePdf').offset().top},200, function() {
        $("#framePdf").focus();
    });
		$('#closePdf').on( "click", function() {
			$('.framePdf').css({'display': 'none'});
		});
	});
};
</script>
<form method="GET" class="clean_get">
	<div class="framePdf panel panel-success" style="display: none; position: fixed; left: 5%; top: 10px">
		<div class="col-lg-12">
			<div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
			<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
		</div>
		<iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
	</div>
    <input type="hidden" name="hidden_req">
    <div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>scontrino:</b></p>
        <p>Numero ID:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Data:</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
    <div style="display:none" id="dialog1" title="<?php echo $script_transl['fae_alert0']; ?>">
        <p id="fae_alert1"><?php echo $script_transl['fae_alert1']; ?></p>
        <p class="ui-state-highlight" id="fae1"></p>
        <p id="fae_alert2"><?php echo $script_transl['fae_alert2']; ?><span id="fae2" class="bg-warning"></span></p>
    </div>
	<div class="table-responsive">
	<table class="Tlarge table table-striped table-bordered table-condensed">
	<tr>
		<td class="FacetFieldCaptionTD">
			<?php gaz_flt_disp_int('id_tes', "ID"); ?>
		</td>
		<td class="FacetFieldCaptionTD">
            <?php gaz_flt_disp_select("anno", "YEAR(datemi) AS anno ", $gTables["tesdoc"]," tipdoc = 'VCO'" , "datemi"); ?>
        </td>
		<td class="FacetFieldCaptionTD">
            <?php gaz_flt_disp_select("cash", "id_contract AS cash", $gTables["tesdoc"], " tipdoc = 'VCO'","id_contract"); ?>
        </td>
		<td class="FacetFieldCaptionTD">
            <?php gaz_flt_disp_select("sezione", $gTables['tesdoc'].".seziva AS sezione ", $gTables["tesdoc"]," tipdoc = 'VCO'" , $gTables['tesdoc'].".seziva"); ?>
        </td>
		<td class="FacetFieldCaptionTD">
            <input type="text" name="cliente" placeholder="Cliente" class="input-sm form-control" value="<?php echo (isset($cliente))? $cliente : ""; ?>" maxlength="15">
        </td>
		<td class="FacetFieldCaptionTD"></td>
		<td class="FacetFieldCaptionTD" colspan="3">
			<input type="submit" class="btn btn-xs btn-default" name="search" value="<?php echo $script_transl['search'];?>" onClick="javascript:document.report.all.value=1;">
			<a class="btn btn-xs btn-default" href="?">Reset</a>
			<?php  $ts->output_order_form(); ?>
		</td>
	</tr>
<?php
echo '<tr>';
$ts->output_headers();
echo '</tr>';
        $result = gaz_dbi_dyn_query ($gTables['tesdoc'].".*, ".$gTables['cash_register'].".descri AS des_rt, ".$gTables['anagra'].".ragso1 AS cliente, ".$gTables['pagame'].".descri AS despag ",
        $tablejoin, $ts->where, $ts->orderby, $ts->getOffset(), $ts->getLimit());
        $tot = 0;

        while ($row = gaz_dbi_fetch_array($result)) {
            $cast_vat = array();
            $cast_acc = array();
            $tot_tes = 0;
            //recupero i dati righi per creare i castelletti
            $rs_rig = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = " . $row['id_tes'], "id_rig");
            while ($v = gaz_dbi_fetch_array($rs_rig)) {
                if ($v['tiprig'] <= 1) {    //ma solo se del tipo normale o forfait
                    if ($v['tiprig'] == 0) { // tipo normale
                        $tot_row = CalcolaImportoRigo($v['quanti'], $v['prelis'], array($v['sconto'], $row['sconto'], -$v['pervat']));
                    } else {                 // tipo forfait
                        $tot_row = CalcolaImportoRigo(1, $v['prelis'], -$v['pervat']);
                    }
                    if (!isset($cast_vat[$v['codvat']])) {
                        $cast_vat[$v['codvat']]['totale'] = 0.00;
                        $cast_vat[$v['codvat']]['imponi'] = 0.00;
                        $cast_vat[$v['codvat']]['impost'] = 0.00;
                        $cast_vat[$v['codvat']]['periva'] = $v['pervat'];
                    }
                    $cast_vat[$v['codvat']]['totale']+=$tot_row;
                    // calcolo il totale del rigo stornato dell'iva
                    $imprig = round($tot_row / (1 + ($v['pervat'] / 100)), 2);
                    $cast_vat[$v['codvat']]['imponi']+=$imprig;
                    $cast_vat[$v['codvat']]['impost']+=$tot_row - $imprig;
                    $tot+=$tot_row;
                    $tot_tes+=$tot_row;
                    // inizio AVERE
                    if (!isset($cast_acc[$admin_aziend['ivacor']]['A'])) {
                        $cast_acc[$admin_aziend['ivacor']]['A'] = 0;
                    }
                    $cast_acc[$admin_aziend['ivacor']]['A']+=$tot_row - $imprig;
                    if (!isset($cast_acc[$v['codric']]['A'])) {
                        $cast_acc[$v['codric']]['A'] = 0;
                    }
                    $cast_acc[$v['codric']]['A']+=$imprig;
                    // inizio DARE
                    if ($row['clfoco'] > 100000000) { // c'� un cliente selezionato
                        if (!isset($cast_acc[$row['clfoco']]['D'])) {
                            $cast_acc[$row['clfoco']]['D'] = 0;
                        }
                        $cast_acc[$row['clfoco']]['D']+=$tot_row;
                    } else {  // il cliente � anonimo lo passo direttamente per cassa
                        if (!isset($cast_acc[$admin_aziend['cassa_']]['D'])) {
                            $cast_acc[$admin_aziend['cassa_']]['D'] = 0;
                        }
                        $cast_acc[$admin_aziend['cassa_']]['D']+=$tot_row;
                    }
                }
            }
            $doc['all'][] = array('tes' => $row,
                'vat' => $cast_vat,
                'acc' => $cast_acc,
                'tot' => $tot_tes);
            if ($row['clfoco'] > 100000000) {
                $doc['invoice'][] = array('tes' => $row,
                    'vat' => $cast_vat,
                    'acc' => $cast_acc,
                    'tot' => $tot_tes);
            } else {
                $doc['ticket'][] = array('tes' => $row,
                    'vat' => $cast_vat,
                    'acc' => $cast_acc,
                    'tot' => $tot_tes);
            }
            // ************* FINE CREAZIONE TOTALI SCONTRINO ***************
            if ($row['id_con'] > 0) {
                $status = $script_transl['status_value'][1];
            } else {
                $status = $script_transl['status_value'][0];
            }
            if ($row['numfat'] > 0) {
                $modulo_fae = "electronic_invoice.php?id_tes=" . $row['id_tes'];
				$row['fae_attuale']="IT" . $admin_aziend['codfis'] . "_".encodeSendingNumber(array('azienda' => $admin_aziend['codice'],
								'anno' => $row["datfat"],
								'sezione' => $row["seziva"],
								'fae_reinvii'=> $row["fattura_elettronica_reinvii"]+4,
								'protocollo' => $row["numfat"]), 36).".xml";
				$row['fae_reinvio']="IT" . $admin_aziend['codfis'] . "_".encodeSendingNumber(array('azienda' => $admin_aziend['codice'],
								'anno' => $row["datfat"],
								'sezione' => $row["seziva"],
								'fae_reinvii'=> intval($row["fattura_elettronica_reinvii"]+5),
								'protocollo' => $row["numfat"]), 36).".xml";
                $invoice = "<a href=\"stampa_docven.php?id_tes=" . $row['id_tes'] . "&template=FatturaAllegata\" class=\"btn btn-xs btn-default\" title=\"Stampa\" target=\"_blank\">n." . $row['numfat'] . " del " . gaz_format_date($row['datfat']) . ' a ' . $row['cliente']. "&nbsp;<i class=\"glyphicon glyphicon-print\"></i></a>\n";
				$invoice .= '<a class="btn btn-xs btn-default btn-xml" onclick="confirFae(this);return false;" id="doc1" '.$row["id_tes"].'" fae_reinvio="'.$row["fae_reinvio"].'" fae_attuale="'.$row["fae_attuale"].'" fae_n_reinvii="'.$row["fattura_elettronica_reinvii"].'" n_fatt="'. $row["numfat"]."/". $row["seziva"].'/SCONTR" target="_blank" href="'.$modulo_fae.'" title="genera il file '.$row["fae_attuale"].' o fai il '.intval($row["fattura_elettronica_reinvii"]+1).'° reinvio ">xml</a><a class="btn btn-xs btn-default" title="Visualizza in stile www.fatturapa.gov.it" href="electronic_invoice.php?id_tes='.$row['id_tes'].'&viewxml"><i class="glyphicon glyphicon-eye-open"></i> </a>';
				if(!empty($row["fattura_elettronica_zip_package"]) && strlen($row["fattura_elettronica_zip_package"])>10){
					$invoice.='<a class="btn btn-xs btn-edit" title="Pacchetto di fatture elettroniche in cui è contenuta questa fattura" href="download_zip_package.php?fn='.$row['fattura_elettronica_zip_package'].'">zip <i class="glyphicon glyphicon-compressed"></i> </a>';
				}
            } else {
                $invoice = '';
            }

            echo "<tr class=\"FacetDataTD\">";
            // Colonna ID scontrino
            echo "<td align=\"center\"><a class=\"btn btn-xs btn-edit\" href=\"admin_scontr.php?Update&id_tes=" . $row['id_tes'] . "\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;" . $row["id_tes"] . "</a></td>";
            // Colonna data emissione
            echo "<td align=\"center\">" . gaz_format_date($row['datemi']) . "</td>";
            // Colonna registratore
            $descash=($row['id_contract']==0)?'Su file XML':$row['id_contract'] . " - " . $row['des_rt'];
            echo "<td align=\"center\">" . $descash . "</td>";
            // Colonna sezione IVA
            echo "<td align=\"center\">" . $row["seziva"] . " &nbsp;</td>";
            // Colonna numero documento
            echo "<td align=\"center\">" . $row["numdoc"] . " &nbsp;</td>";
            // Colonna fattura
            echo "<td align=\"center\">$invoice</td>";
            // Colonna pagamento
            echo "<td align=\"center\">" . $row["despag"] . " &nbsp;</td>";
            // Colonna stato
            echo "<td align=\"center\">";
            if ($row["id_con"] > 0) {
                echo " <a class=\"btn btn-xs btn-default\" style=\"font-size:10px;\" title=\"Modifica il movimento contabile generato da questo documento\" href=\"../contab/admin_movcon.php?id_tes=" . $row["id_con"] . "&Update\">Cont." . $row["id_con"] . "</a> ";
				if(strlen($row["fattura_elettronica_original_name"])>10){
					echo " <a class=\"btn btn-xs btn-info\" title=\"Dato contenuto nel file\" href=\"download_zip_package.php?fn=" . $row["fattura_elettronica_original_name"] . "\"><small>" . $row["fattura_elettronica_original_name"] . "</small></a> ";
				}
            } else {
                echo " <a class=\"btn btn-xs btn-default btn-cont\" href=\"close_ecr.php\"><i class=\"glyphicon glyphicon-euro\"></i>&nbsp;Contabilizza</a>";
            }
            echo "&nbsp;</td>";
             // Colonna importo
            echo '<td align="right" style="font-weight=bolt;">';
            echo gaz_format_number($tot_tes);
            echo "\t </td>\n";
            // Colonna certificato
            echo "<td align=\"center\">";
            // Colonna Elimina
            echo "</td>";
            if ($row["id_con"] == 0) {
                if (in_array($row['id_tes'],$last_tickets)) {
                    echo "<td align=\"center\">";
					?>
					<a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella il documento e la registrazione contabile relativa" ref="<?php echo $row['id_tes'];?>" datemi="<?php echo $row['datemi']; ?>">
						<i class="glyphicon glyphicon-trash"></i>
					</a>
					</td>
					<?php
				} else {
                    echo "<td align=\"center\"><button class=\"btn btn-xs   disabled\"><i class=\"glyphicon glyphicon-trash\"></i></button></td>";
                }
            } else {
                echo "<td align=\"center\"><button class=\"btn btn-xs   disabled\"><i class=\"glyphicon glyphicon-trash\"></i></button></td>";
            }
            // Colonna invia a ECR
            echo "<td align=\"center\">";
            echo "<a class=\"btn btn-xs btn-primary btn-ecr\" href=\"resend_to_ecr.php?id_tes=" . $row['id_tes'] . "\" >" . $script_transl['send'] . "</a>";
            echo "<a class=\"btn btn-xs btn-default\" style=\"cursor:pointer;\" onclick=\"printPdf('stampa_docven.php?id_tes=".$row["id_tes"]."&template=Scontrino')\"><i class=\"glyphicon glyphicon-print\" title=\"Stampa non fiscale PDF\"></i> non fiscale</a>";
			echo "</tr>\n";
        }
?>
     </table>
	</div>
</form>

<script src="../../js/custom/fix_select.js" type="text/javascript"></script>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>
