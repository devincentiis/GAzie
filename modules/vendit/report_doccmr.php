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
$admin_aziend = checkAdmin();
$partner_select_mode = gaz_dbi_get_row($gTables['company_config'], 'var', 'partner_select_mode');
$message = "";
$anno = date("Y");
if (isset($_GET['auxil'])) {
    $auxil = filter_input(INPUT_GET, 'auxil');
} else {
    $auxil = 1;
}
$where = " ((tipdoc = 'FAD' and ddt_type='R') or tipdoc like 'CMR') and seziva = '$auxil'";
$all = $where;

$documento = '';
$cliente = '';

gaz_flt_var_assign('id_tes', 'i');
gaz_flt_var_assign('numdoc', 'i');
gaz_flt_var_assign('tipdoc', 'v');
gaz_flt_var_assign('datemi', 'd');
gaz_flt_var_assign('clfoco', 'v');

$lot = new lotmag();

if (isset($_GET['datemi'])) {
    $datemi = $_GET['datemi'];
}

if (isset($_GET['cliente'])) {
    if ($_GET['cliente'] <> '') {
        $cliente = $_GET['cliente'];
        $where = " ((tipdoc = 'FAD' and ddt_type='R') or tipdoc like 'CMR') and seziva = '$auxil' ";
        $limit = 0;
        $passo = 2000000;
        $auxil = $_GET['auxil'] . "&cliente=" . $cliente;
        unset($documento);
    }
}

if (isset($_GET['all'])) {
    $_GET['id_tes'] = "";
    $_GET['numdoc'] = "";
    $_GET['tipdoc'] = "";
    $_GET['datemi'] = "";
    $_GET['clfoco'] = "";
    gaz_set_time_limit(0);
    $auxil = filter_input(INPUT_GET, 'auxil') . "&all=yes";
    $passo = 100000;
    $where = " ((tipdoc = 'FAD' and ddt_type='R') or tipdoc like 'CMR') and seziva = '$auxil'";
    unset($documento);
    $cliente = '';
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('custom/modal_form'));
echo '<script>
$(function() {
   $( "#dialog" ).dialog({
      autoOpen: false
   });
});
function confirMail(link){
   tes_id = link.id.replace("doc", "");
   $.fx.speeds._default = 500;
   targetUrl = $("#doc"+tes_id).attr("url");
   //alert (targetUrl);
   $("p#mail_adrs").html($("#doc"+tes_id).attr("mail"));
   $("p#mail_attc").html($("#doc"+tes_id).attr("namedoc"));
   $( "#dialog" ).dialog({
         modal: "true",
      show: "blind",
      hide: "explode",
         buttons: {
                      " ' . $script_transl['submit'] . ' ": function() {
                         window.location.href = targetUrl;
                      },
                      " ' . $script_transl['cancel'] . ' ": function() {
                        $(this).dialog("close");
                      }
                  }
         });
   $("#dialog" ).dialog( "open" );
}
</script>';
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("ragso1"));
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
							window.location.replace("./report_doccmr.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<form method="GET">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>CMR:</b></p>
        <p>Numero:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Cliente:</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
    <div style="display:none" id="dialog" title="<?php echo $script_transl['mail_alert0']; ?>">
        <p id="mail_alert1"><?php echo $script_transl['mail_alert1']; ?></p>
        <p class="ui-state-highlight" id="mail_adrs"></p>
        <p id="mail_alert2"><?php echo $script_transl['mail_alert2']; ?></p>
        <p class="ui-state-highlight" id="mail_attc"></p>
    </div>
    <div align="center" class="FacetFormHeaderFont"> <?php echo $script_transl['title']; ?>
        <select name="auxil" class="FacetSelect" onchange="this.form.submit()">
            <?php
            for ($sez = 1; $sez <= 9; $sez++) {
                $selected = "";
                if (substr($auxil, 0, 1) == $sez)
                    $selected = " selected ";
                echo "<option value=\"" . $sez . "\"" . $selected . ">" . $sez . "</option>";
            }
            ?>
        </select>
    </div>
    <?php
    if (!isset($_GET['field']) or ( $_GET['field'] == 2) or ( empty($_GET['field'])))
        $orderby = "datemi desc, numdoc desc";
    $recordnav = new recordnav($gTables['tesdoc'], $where, $limit, $passo);
    $recordnav->output();
    ?>
    <div class="box-primary table-responsive">
        <table class="Tlarge table table-striped table-bordered table-condensed">
            <tr>
                <td class="FacetFieldCaptionTD">
                    <?php gaz_flt_disp_int("id_tes", "Numero Prot."); ?>
                    <!--<input placeholder="Cerca Numero" class="input-xs form-control" type="text" name="numdoc" value="<?php if (isset($documento) && $documento > 0) print $documento; ?>" maxlength="6" tabindex="1" class="FacetInput">-->
                </td>
                <td class="FacetFieldCaptionTD">
                    <?php gaz_flt_disp_int("numdoc", "Numero Doc."); ?>
                </td>
                <td class="FacetFieldCaptionTD">
                    <?php gaz_flt_disp_select("tipdoc", "tipdoc", $gTables["tesdoc"], $all, $orderby); ?>
                </td>
                <td class="FacetFieldCaptionTD">
                    <?php gaz_flt_disp_select("datemi", "YEAR(datemi) as datemi", $gTables["tesdoc"], $all, $orderby); ?>
                </td>
                <td class="FacetFieldCaptionTD">

                    <?php
                    if ($partner_select_mode['val'] == null or $partner_select_mode['val'] == "0") {
                        gaz_flt_disp_select("clfoco", $gTables['anagra'] . ".ragso1," . $gTables["tesdoc"] . ".clfoco", $gTables['tesdoc'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesdoc'] . ".clfoco = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['clfoco'] . ".id_anagra = " . $gTables['anagra'] . ".id", $all, "ragso1", "ragso1");
                    } else {
                        gaz_flt_disp_int("cliente", "Cliente");
                    }
                    ?>
                </td>
                <td class="FacetFieldCaptionTD">
                    &nbsp;
                </td>
                <td class="FacetFieldCaptionTD">
                    &nbsp;
                </td>
                <td class="FacetFieldCaptionTD">
                    &nbsp;
                </td>
                <td class="FacetFieldCaptionTD">
                    &nbsp;
                </td>
                <td class="FacetFieldCaptionTD">
                    &nbsp;
                </td>
                <td class="FacetFieldCaptionTD">
                    <input class="btn btn-sm btn-default" type="submit" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value = 1;">
                </td>
                <td class="FacetFieldCaptionTD">
                    <input class="btn btn-sm btn-default" type="submit" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value = 1;">
                </td>
            </tr>

            <tr>
                <?php
                $linkHeaders = new linkHeaders($script_transl['header']);
                $linkHeaders->setAlign(array('left', 'left', 'center', 'center', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'center'));
                $linkHeaders->output();
                ?>
            </tr>
            <?php
            $rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesdoc'], $where, "datemi desc, numdoc desc", 0, 1);
            $ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
            if ($ultimo_documento)
                $ultimoddt = $ultimo_documento['numdoc'];
            else
                $ultimoddt = 1;
//recupero le testate in base alle scelte impostate
            $result = gaz_dbi_dyn_query("*", $gTables['tesdoc'], $where, $orderby, $limit, $passo);
            while ($r = gaz_dbi_fetch_array($result)) {
                // customer data
                $match_cust = true;
                $clfoco = gaz_dbi_get_row($gTables['clfoco'], 'codice', $r['clfoco']);
                $anagra = gaz_dbi_get_row($gTables['anagra'], 'id', $clfoco['id_anagra']);
                $destina = gaz_dbi_get_row($gTables['destina'], 'codice', $r['id_des_same_company']);
				if(!$destina){$destina=['codice'=>'','unita_locale1'=>''];}
                if (!empty($cliente) && stripos($anagra['ragso1'], $_GET['cliente']) === false ) {
                    $match_cust=false;
                }
                if ($match_cust) {
                    switch ($r['tipdoc']) {
                        case "CMR":
                            echo "<tr class=\"FacetDataTD\">";
                            // Colonna id
                            echo "<td align=\"left\"><a class=\"btn btn-xs btn-edit\" href=\"admin_docven.php?Update&id_tes=" . $r["id_tes"] . "\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;" . $r['tipdoc'].' '. $r["id_tes"] . "</a></td>";
                            // Colonna protocollo
                            echo "<td align=\"left\"><a href=\"admin_docven.php?Update&id_tes=" . $r["id_tes"] . "\">" . $r["numdoc"] . "</a> &nbsp;</td>";
                            // Colonna type
                            echo "<td align=\"center\"><a class=\"btn btn-xs btn-primary btn-primary \" href=\"admin_docven.php?Update&id_tes=" . $r["id_tes"] . "\">&nbsp;" . $script_transl['cmr_type'][$r["ddt_type"]] . "</a> &nbsp;</td>";
                            // Colonna data emissione
                            echo "<td align=\"center\">" . gaz_format_date($r["datemi"]). " &nbsp;</td>";
                            // Colonna Cliente
                            ?>
                            <td>
                                <a href="report_client.php?nome=<?php echo htmlspecialchars($anagra["ragso1"]); ?>">
                                    <?php echo $anagra["ragso1"]; ?>
                                </a>
                            </td>
                            <td>
                                <?php echo "<a href=\"admin_destinazioni.php?codice=".$destina["codice"]."&Update\">".$destina["unita_locale1"]."</a>"; ?>
                            </td>
                            <?php
                            // Colonna status
                            if ($r['numfat'] > 0) {
                                echo "<td align=\"center\"><a class=\"btn btn-xs btn-default\" title=\"" . $script_transl['print_invoice'] . " n. " . $r["numfat"] . "\" href=\"stampa_docven.php?td=2&si=" . $r["seziva"] . "&pi=" . $r['protoc'] . "&pf=" . $r['protoc'] . "&di=" . $r['datfat'] . "&df=" . $r['datfat'] . "\" target=\"_blank\"><i class=\"glyphicon glyphicon-print\"></i> fatt. n. " . $r["numfat"] . "</a></td>";
                                if ($r["id_con"] > 0) {
                                    echo "<a title=\"" . $script_transl['acc_entry'] . "\" href=\"../contab/admin_movcon.php?id_tes=" . $r["id_con"] . "&Update\">cont. n." . $r["id_con"] . "</a>";
                                }
                            } else {
                                if ($r['tipdoc'] == 'DDV' && $r['id_doc_ritorno'] > 0) {
                                    echo "<td align=\"center\">"
                                    . "<a class=\"btn btn-xs btn-warning\" href=\"admin_docven.php?Update&id_tes=" . $r['id_doc_ritorno'] . "\">" . $script_transl['doc_returned'] . "</a>"
                                    . "<a class=\"btn btn-xs  btn-elimina\"href=\"delete_docven.php?id_tes=" . $r['id_doc_ritorno'] . "\" title=\"" . $script_transl['delete_returned'] . "\"><i class=\"glyphicon glyphicon-trash\"></i></a>"
                                    . "</td>";
                                } else {
                                    echo "<td align=\"center\"><a class=\"btn btn-xs btn-success\" href=\"emissi_fatdif.php?tipodocumento=CMR\">" . $script_transl['to_invoice'] . "</a></td>";
                                }
                            }
                            // Colonna stampa

                            $urlPrintDoc = "stampa_docven.php?id_tes=" . $r["id_tes"] . "&template=CMR";
                            $urlPrintEtichette = "stampa_docven.php?id_tes=" . $r["id_tes"] . "&template=Etichette";
                            $urlPrintCmr = "stampa_docven.php?id_tes=" . $r["id_tes"]."&template=Cmr";
                            echo "<td align=\"center\">";
                            echo "<a class=\"btn btn-xs btn-default\" href=\"$urlPrintDoc\" target=\"_blank\"><i class=\"glyphicon glyphicon-print\" title=\"Stampa documento\"></i></a>";
                            echo "<a class=\"btn btn-xs btn-default\" href=\"$urlPrintEtichette\" target=\"_blank\"><i class=\"glyphicon glyphicon-tag\" title=\"Stampa etichetta\"></i></a>";
                            if ( $anagra['country']!="IT") {
                                echo "<a class=\"btn btn-xs btn-default\" href=\"$urlPrintCmr\" target=\"_blank\"><i class=\"glyphicon glyphicon-plane\" title=\"Stampa modulo CMR\"></i></a>";
                            }
                            echo "</td>\n";

                            // Colonna "Mail"
                            echo "<td align=\"center\">";
                            if (!empty($anagra["e_mail"])) {
                                echo '<a class="btn btn-xs btn-default btn-mail" onclick="confirMail(this);return false;" id="doc' . $r["id_tes"] . '" url="' . $urlPrintDoc . '&dest=E" href="#" title="mailto: ' . $anagra["e_mail"] . '"
                mail="' . $anagra["e_mail"] . '" namedoc="' . $r['tipdoc'] . ' n.' . $r["numdoc"] . ' del ' . gaz_format_date($r["datemi"]) . '"><i class="glyphicon glyphicon-envelope" title="Invia documento per email"></i></a>';
                            } else {
                                echo '<a title="' . $script_transl['no_mail'] . '" target="_blank" href="admin_client.php?codice=' . substr($clfoco["codice"], 3) . '&Update"><i class="glyphicon glyphicon-edit"></i></a>';
                            }
                            echo "</td>\n";

                            // Colonna Origine
                            echo "<td>";
                            $resorigine = gaz_dbi_dyn_query('*', $gTables['rigdoc'], "id_tes = " . $r["id_tes"], 'id_tes', 1,1);
                            if ( gaz_dbi_num_rows( $resorigine )>0 ) {
                                $rigdoc_result = gaz_dbi_dyn_query('DISTINCT id_order', $gTables['rigdoc'], "id_tes = " . $r["id_tes"], 'id_tes');
                                while ( $rigdoc = gaz_dbi_fetch_array($rigdoc_result) ) {
                                    if($rigdoc['id_order']>0){
                                        $tesbro_result = gaz_dbi_dyn_query('*', $gTables['tesbro'], "id_tes = " . $rigdoc['id_order'], 'id_tes');
                                        $t_r = gaz_dbi_fetch_array($tesbro_result);
                                        echo " <a class=\"btn btn-xs btn-default\" title=\"" . $script_transl['view_ord'] . "\" href=\"stampa_ordcli.php?id_tes=" . $rigdoc['id_order'] . "\" style=\"font-size:10px;\">Ord." . $t_r['numdoc'] . "</a>\n";
                                    }
                                }
                            }
                            echo "</td>\n";
                            echo "<td align=\"center\"><a class=\"btn btn-xs btn-default btn-duplica\" href=\"admin_docven.php?Duplicate&id_tes=" . $r["id_tes"] . "\"><i class=\"glyphicon glyphicon-duplicate\"></i></a>";
                            echo "</td>";

                            if($ultimoddt==$r["numdoc"]&&$r['numfat']==0){
							?>
							<td class="text-center">
								<a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella il documento" ref="<?php echo $r['id_tes'];?>" ragso1="<?php echo $anagra['ragso1'];?>"><i class="glyphicon glyphicon-trash"></i>
								</a>
							</td>
							<?php
							}else{
								echo "<td align=\"center\"><button class=\"btn btn-xs   disabled\"><i class=\"glyphicon glyphicon-trash\"></i></button></td>";
							}
                            echo "</tr>\n";
                            break;
                        case "FAD":
                            if ( $r['ddt_type']=='R') {
                            echo "<tr class=\"FacetDataTD\">";
                            // Colonna id
                            echo "<td align=\"left\"><a class=\"btn btn-xs btn-edit\" href=\"admin_docven.php?Update&id_tes=" . $r["id_tes"] . "\"><i class=\"glyphicon glyphicon-edit\"></i>".$r['tipdoc']."&nbsp;" . $r["id_tes"] . "</a></td>";
                            // Colonna protocollo
                            echo "<td align=\"left\"><a href=\"admin_docven.php?Update&id_tes=" . $r["id_tes"] . "\">" . $r["numdoc"] . "</a></td>";
                            // Colonna type
                            echo "<td align=\"center\"><a class=\"btn btn-xs btn-primary btn-primary \" href=\"admin_docven.php?Update&id_tes=" . $r["id_tes"] . "\">&nbsp;" . $script_transl['cmr_type'][$r["ddt_type"]] . "</a> &nbsp;</td>";
                            // Colonna Data emissione
                            echo "<td align=\"center\">" . gaz_format_date($r["datemi"]) . " &nbsp;</td>";
                            // Colonna Cliente
                            ?>
                            <td class="">
                                <a href="report_client.php?nome=<?php echo htmlspecialchars($anagra["ragso1"]); ?>">
                                    <?php echo $anagra["ragso1"]; ?>
                                </a>
                            </td>
                            <td>
                                <?php echo "<a href=\"admin_destinazioni.php?codice=".$destina["codice"]."&Update\">".$destina["unita_locale1"]."</a>"; ?>
                            </td>
                            <?php
                            // Colonna Stato
                            echo "<td align=\"center\"><a class=\"btn btn-xs btn-default\" title=\"" . $script_transl['print_invoice'] . " n. " . $r["numfat"] . "\" href=\"stampa_docven.php?td=2&si=" . $r["seziva"] . "&pi=" . $r['protoc'] . "&pf=" . $r['protoc'] . "&di=" . $r['datfat'] . "&df=" . $r['datfat'] . "\">Fat " . $r["numfat"] . "</a>";
                            if ($r["id_con"] > 0) {
                                echo "&nbsp;<a class=\"btn btn-xs btn-default btn-registrazione\" title=\"" . $script_transl['acc_entry'] . "\" href=\"../contab/admin_movcon.php?id_tes=" . $r["id_con"] . "&Update\">Cont " . $r["id_con"] . "</a>";
                            }
                            echo "</td>";

                            $urlPrintDoc = "stampa_docven.php?id_tes=" . $r["id_tes"] . "&template=CMR";
                            // Colonna stampa
                            echo "<td align=\"center\">
            <a class=\"btn btn-xs btn-default\" title=\"" . $script_transl['print_cmr'] . " n. " . $r["numdoc"] . "\" href=\"$urlPrintDoc\" target=\"_blank\"><i class=\"glyphicon glyphicon-print\"></i></a>";
                            echo "</td>";

                            // Colonna "Mail"
                            echo "<td align=\"center\">";
                            if (!empty($anagra["e_mail"])) {
                                echo '<a class="btn btn-xs btn-default btn-mail" onclick="confirMail(this);return false;" id="doc' . $r["id_tes"] . '" url="' . $urlPrintDoc . '&dest=E" href="#" title="mailto: ' . $anagra["e_mail"] . '"
                mail="' . $anagra["e_mail"] . '" namedoc="CMR n.' . $r["numdoc"] . ' del ' . gaz_format_date($r["datemi"]) . '"><i class="glyphicon glyphicon-envelope"></i></a>';
                            } else {
                                echo '<a title="' . $script_transl['no_mail'] . '" target="_blank" href="admin_client.php?codice=' . substr($clfoco["codice"], 3) . '&Update"><i class="glyphicon glyphicon-edit"></i></a>';
                            }
                            echo "</td>";
                            // Colonna origine
                            echo "<td align=\"center\">";
                            $resorigine = gaz_dbi_dyn_query('*', $gTables['rigdoc'], "id_tes = " . $r["id_tes"], 'id_tes', 1,1);
                            if ( gaz_dbi_num_rows( $resorigine )>0 ) {
                                $rigdoc_result = gaz_dbi_dyn_query('DISTINCT id_order', $gTables['rigdoc'], "id_tes = " . $r["id_tes"], 'id_tes');
                                while ( $rigdoc = gaz_dbi_fetch_array($rigdoc_result) ) {
                                    if($rigdoc['id_order']>0){
                                        $tesbro_result = gaz_dbi_dyn_query('*', $gTables['tesbro'], "id_tes = " . $rigdoc['id_order'], 'id_tes');
                                        $t_r = gaz_dbi_fetch_array($tesbro_result);
                                        echo "<a class=\"btn btn-xs btn-default\" title=\"" . $script_transl['view_ord'] . "\" href=\"stampa_ordcli.php?id_tes=" . $rigdoc['id_order'] . "\" style=\"font-size:10px;\">Ord." . $t_r['numdoc'] . "</a>\n";
                                    }
                                }
                            }
                            echo "</td>";
                            echo "<td></td>";
                            echo "<td></td>";
                            echo "</tr>\n";
                            }
                            break;
                    }
                }
            }
            ?>
            <tr><th class="FacetFieldCaptionTD" colspan="12"></th></tr>
        </table>
    </div>
</form>
<?php
require("../../library/include/footer.php");
?>
