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
$pdf_to_modal = gaz_dbi_get_row($gTables['company_config'], 'var', 'pdf_reports_send_to_modal')['val'];
$partner_select = !gaz_dbi_get_row($gTables['company_config'], 'var', 'partner_select_mode')['val'];
$tesdoc_e_partners = $gTables['tesdoc'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesdoc'] . ".clfoco = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id';


// funzione di utilità generale, adatta a mysqli.inc.php
function cols_from($table_name, ...$col_names) {
    $full_names = array_map(function ($col_name) use ($table_name) { return "$table_name.$col_name"; }, $col_names);
    return implode(", ", $full_names);
}

// campi ammissibili per la ricerca
$search_fields = [
    'sezione'
    => "seziva = %d",
    'id_tes'
    => "id_tes = %d",
    'tipoddt'
    => " tipdoc = '%s' ",
    'tipo'
    => " ( tipdoc LIKE '%s' OR tipdoc = 'FAD' OR tipdoc = 'RPL') ",
    'numero'
    => "numdoc LIKE '%%%s%%'",
    'anno'
    => "YEAR(datemi) = %d",
    'cliente'
    => $partner_select ? "clfoco = %s" : "ragso1 LIKE '%%%s%%'"
];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array(
    "ID" => "id_tes",
    "Numero" => "numdoc",
    "Data" => "datemi",
    "Cliente" => "",
    "Destinazione" => "",
    "Status" => "",
    "Stampa" => "",
    "Mail" => "",
    "Origine" => "",
    "Duplica" => "",
    "Elimina" => ""
);

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('custom/modal_form'));

if (!isset($_GET['sezione'])) {
	// ultima fattura emessa
	$rs_last = gaz_dbi_dyn_query('seziva, YEAR(datemi) AS yearde', $gTables['tesdoc'], " (tipdoc = 'FAD' OR tipdoc LIKE 'DD_')", 'datemi DESC, id_tes DESC', 0, 1);
	$last = gaz_dbi_fetch_array($rs_last);
	if ($last) {
		$default_where=['sezione' => $last['seziva'], 'tipo' => 'DD_', 'anno'=>$last['yearde']];
        $_GET['anno']=$last['yearde'];
	} else {
		$default_where=['sezione' => 1, 'tipo' => 'DD_', 'anno'=> date('Y')];
	}
} else {
	$default_where=['sezione' => intval($_GET['sezione']), 'tipo' => 'DD_'];
}
$ts = new TableSorter(
    $tesdoc_e_partners,
    $passo,
    ['datemi' => 'desc', 'numdoc' => 'desc'],
    $default_where
);

# le <select> spaziano solo tra i documenti di vendita del sezionale corrente
$where_select = sprintf(" (tipdoc = 'RPL' OR tipdoc = 'FAD' OR tipdoc LIKE 'DD_') AND seziva = %d", $sezione);
?>
<script>
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
        buttons: [{
        text: "<?php echo $script_transl['submit']; ?> ",
        "class": 'btn',
        click: function () {
          $('#frame_email').attr('src',targetUrl);
          $('#frame_email').css({'height': '100%'});
          $('.frame_email').css({'display': 'block','width': '40%', 'margin-left': '25%', 'z-index':'2000'});
          $('#close_email').on( "click", function() {
          $('#frame_email').attr('src','');
          $('.frame_email').css({'display': 'none'});
          });
          $(this).dialog("close");
        },
      },
      {
        text: "<?php echo $script_transl['cancel']; ?>",
        "class": 'btn',
        click: function () {
          $(this).dialog("close");
        },
      }]
   });
   $("#dialog" ).dialog( "open" );
}

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
							window.location.replace("./report_doctra.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
	$('#closePdf').on( "click", function() {
		$('.framePdf').css({'display': 'none'});
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
	});
};
</script>
<?php
if (isset($_SESSION['print_request']) && intval($_SESSION['print_request'])>0){
?>
<script> printPdf('stampa_docven.php?id_tes=<?php echo $_SESSION['print_request'].$_SESSION['template']; ?>'); </script>
		<?php
		$_SESSION['print_request']="";
		$_SESSION['template']="";
}
?>

<form method="GET" class="clean_get">
  <div class="frame_email panel panel-success" style="display: none; position: fixed; left: 5%; top: 15%; margin-left: 30%;">
    <div class="col-lg-12">
      <div class="col-xs-11"><h4>e-mail</h4></div>
      <div class="col-xs-1"><h4><button type="button" id="close_email"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
    </div>
    <iframe id="frame_email"  style="height: 90%; width: 100%" src=""></iframe>
  </div>
	<div class="framePdf panel panel-success" style="display: none; position: fixed; left: 5%; top: 10px">
			<div class="col-lg-12">
				<div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
				<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
			</div>
			<iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
	</div>
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>documento di trasporto:</b></p>
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
    <div class="FacetFormHeaderFont text-center"> <?php echo $script_transl['title']; ?>
       <select name="sezione" class="FacetSelect" onchange="this.form.submit()">
	    <?php
            for ($i = 1; $i <= 9; $i++) {
                $selected = ($sezione == $i) ? "selected" : "";
                echo "<option value='$i' $selected > $i </option>\n";
            }
	    ?>
        </select>
     </div>
                <?php
        $ts->output_navbar();
                ?>

    <div class="table-responsive">
        <table class="Tlarge table table-striped">
            <tr>
                <td class="FacetFieldCaptionTD">
                    <?php gaz_flt_disp_int("id_tes", "ID"); ?>
                </td>
                <td class="FacetFieldCaptionTD">
                    <?php gaz_flt_disp_int("numero", "Numero DdT"); ?>
                </td>
                <td class="FacetFieldCaptionTD">
					<?php gaz_flt_disp_select("tipoddt", "tipdoc AS tipoddt", $tesdoc_e_partners,  $where_select.((isset($_GET['anno']) && intval($_GET['anno']) >= 2000)?' AND YEAR(datemi)='.intval($_GET['anno']):''),'tipoddt'); ?>
                </td>
                <td class="FacetFieldCaptionTD">
                    <?php gaz_flt_disp_select("anno", "YEAR(datemi) as anno", $gTables["tesdoc"], $where_select, "anno DESC"); ?>
                </td>
                <td class="FacetFieldCaptionTD" colspan=2>
		    <?php
                    if ($partner_select) {
                        gaz_flt_disp_select("cliente", "clfoco AS cliente, ragso1 as nome",$tesdoc_e_partners, $where_select.((isset($_GET['anno']) && intval($_GET['anno']) >= 2000)?' AND YEAR(datemi)='.intval($_GET['anno']):''), "nome ASC", "nome");
                    } else {
                        gaz_flt_disp_int("cliente", "Cliente");
                    }
		    ?>
                </td>
                <td class="FacetFieldCaptionTD text-center">
                    <input type="submit" class="btn btn-sm btn-default btn-50" name="search" value="Cerca" tabindex="1">
                    <?php $ts->output_order_form(); ?>
                </td>
                <td class="FacetFieldCaptionTD">
                </td>
                <td class="FacetFieldCaptionTD">
                    <a class="btn btn-sm btn-default btn-50" href="?">Reset</a>
                </td>
                <td class="FacetFieldCaptionTD" colspan=3>
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

            $rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesdoc'], $where_select.((isset($_GET['anno']) && intval($_GET['anno']) >= 2000)?' AND YEAR(datemi)='.intval($_GET['anno']):''), "datemi desc, numdoc desc", 0, 1);
            $ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
            if ($ultimo_documento)
                $ultimoddt = $ultimo_documento['numdoc'];
            else
                $ultimoddt = 1;
//recupero le testate in base alle scelte impostate
            $result = gaz_dbi_dyn_query("*", $tesdoc_e_partners, $ts->where, $ts->orderby, $ts->getOffset(), $ts->getLimit());
            while ($r = gaz_dbi_fetch_array($result)) {
                $destina = gaz_dbi_get_row($gTables['destina'], 'codice', $r['id_des_same_company']);
                if(!$destina) $destina=['codice'=>'','unita_locale1'=>''];
                    switch ($r['tipdoc']) {
                        case "RPL":
                        case "DDT":
                        case "DDV":
                        case "DDY":
                        case "DDS":
                        case "DDX":
                        case "DDZ":
                        case "DDW":
                        case "DDD":
                        case "DDJ":
                        case "DDC":
                        case "DDM":
                        case "DDO":
                            echo "<tr class=\"text-center\">";
                            // Colonna id
                            echo "<td><a class=\"btn btn-xs btn-edit\" href=\"admin_docven.php?Update&id_tes=" . $r["id_tes"] . "\"><i class=\"glyphicon glyphicon-edit\"></i> " . $r['tipdoc'].' '. $r["id_tes"] . "</a></td>";
                            // Colonna protocollo
                            echo "<td class=\"text-center\"><a href=\"admin_docven.php?Update&id_tes=" . $r["id_tes"] . "\">" . $r["numdoc"] . "</a> </td>";
                            // Colonna type

                            echo "<td><div class=\"btn btn-xs btn-primary btn-primary\" style=\"cursor: default;\"> " . $script_transl['ddt_type'][$r["ddt_type"]] . "</div> </td>";
                            // Colonna data emissione
                            echo "<td>" . gaz_format_date($r["datemi"]). "  </td>";
                            // Colonna Cliente
                            ?>
                            <td class="text-left">
                                <a href="report_client.php?nome=<?php echo htmlspecialchars($r["ragso1"]); ?>">
                                    <?php echo $r["ragso1"]; ?>
                                </a>
                            </td>
                            <td>
                                <?php echo "<a href=\"admin_destinazioni.php?codice=".$destina["codice"]."&Update\">".$destina["unita_locale1"]."</a>"; ?>
                            </td>
                            <?php
                            // Colonna status
                            if ($r['numfat'] > 0) {
                                echo "<td style=\"white-space:unset;\"><a class=\"btn btn-xs btn-default\" title=\"" . $script_transl['print_invoice'] . " n. " . $r["numfat"] . "\" href=\"stampa_docven.php?td=2&si=" . $r["seziva"] . "&pi=" . $r['protoc'] . "&pf=" . $r['protoc'] . "&di=" . $r['datfat'] . "&df=" . $r['datfat'] . "\" target=\"_blank\"><i class=\"glyphicon glyphicon-print\"></i> fatt. n. " . $r["numfat"] . "</a></td>";
                                if ($r["id_con"] > 0) {
                                    echo "<a title=\"" . $script_transl['acc_entry'] . "\" href=\"../contab/admin_movcon.php?id_tes=" . $r["id_con"] . "&Update\">cont. n." . $r["id_con"] . "</a>";
                                }
                            } else if ($r['tipdoc'] == 'DDX' || $r['tipdoc'] == 'DDZ' || $r['tipdoc'] == 'DDW' || $r['tipdoc'] == 'DDJ' || $r['tipdoc'] == 'DDD' || $r['tipdoc'] == 'DDC' || $r['tipdoc'] == 'RPL') {
                               echo '<td><a class="btn btn-xs btn-default" style="cursor: none;">da non fatturare</a></td>';
                            } else {
                                if ($r['tipdoc'] == 'DDV' && $r['id_doc_ritorno'] > 0) {
                                  echo "<td><a class=\"btn btn-xs btn-warning\" href=\"admin_docven.php?Update&id_tes=" . $r['id_doc_ritorno'] . "\">" . $script_transl['doc_returned'] . "</a>";
                                    ?>
                                    <a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella il documento" ref="<?php echo $r['id_tes'];?>" ragso1="<?php echo $r['ragso1'];?>">
                                      <i class="glyphicon glyphicon-trash"></i>
                                    </a>
                                  <?php
                                  echo "</td>";
                                } else {
                                  echo "<td><a class=\"btn btn-xs btn-success\" href=\"emissi_fatdif.php\">" . $script_transl['to_invoice'] . "</a></td>";
                                }
                            }
                            // Colonna stampa

                            $urlPrintDoc = "stampa_docven.php?id_tes=" . $r["id_tes"] . "&template=DDT";
                            $urlPrintEtichette = "stampa_docven.php?id_tes=" . $r["id_tes"] . "&template=Etichette";
                            $urlPrintCmr = "stampa_docven.php?id_tes=" . $r["id_tes"]."&template=Cmr";
                            $targetPrintDoc = ($pdf_to_modal==0)?'href="stampa_docven.php?id_tes=' . $r["id_tes"] .'&template=DDT" target="_blank" ':"onclick=\"printPdf('stampa_docven.php?id_tes=" . $r["id_tes"] . "&template=DDT')\"";
                            echo "<td>";
                            echo '<a class="btn btn-xs btn-default" style="cursor:pointer;" '.$targetPrintDoc.' ><i class="glyphicon glyphicon-print" title="Stampa documento"></i></a>';
                            echo "<a class=\"btn btn-xs btn-default\" style=\"cursor:pointer;\" onclick=\"printPdf('".$urlPrintEtichette."')\" data-toggle=\"modal\" data-target=\"#print_doc\" ><i class=\"glyphicon glyphicon-tag\" title=\"Stampa etichetta\"></i></a>";
                            echo ' <a class="btn btn-xs btn-default" title="XML Peppol" href="./peppol.php?id_tes='.$r['id_tes'].'&viewxml" target="_blank"> XML </a>';
                            echo "</td>\n";

                            // Colonna "Mail"
                            echo "<td>";
                            if (!empty($r["e_mail"])) {
                              $gaz_custom_field = gaz_dbi_get_single_value( $gTables['tesdoc'], 'custom_field', 'id_tes = '.$r['id_tes'] );
                              if (isset($gaz_custom_field) && $gaz_custom_data = json_decode($gaz_custom_field,true)){
                                if ( !isset($gaz_custom_data['email']['ddt'])) {
                                    $classe_mail = "btn-default";
                                    $title= "Mai inviata. Inviala a ".$r["e_mail"];
                                } else {
                                    $classe_mail = "btn-success";
                                    $title="Ultimo invio: ".$gaz_custom_data['email']['ddt'];
                                }
                              }else{
                                $classe_mail = "btn-default";
                                $title= "Mai inviata. Inviala a ".$r["e_mail"];
                              }
                                echo '<a class="btn btn-xs '.$classe_mail.' btn-default btn-mail" title="',$title,'" onclick="confirMail(this);return false;" id="doc' . $r["id_tes"] . '" url="' . $urlPrintDoc . '&dest=E" href="#" title="' . $title . '"
                                mail="' . $r["e_mail"] . '" namedoc="' . $r['tipdoc'] . ' n.' . $r["numdoc"] . ' del ' . gaz_format_date($r["datemi"]) . '"><i class="glyphicon glyphicon-envelope" title="Invia documento per email"></i></a>';
                            } else {
                                echo '<a title="' . $script_transl['no_mail'] . '" target="_blank" href="admin_client.php?codice=' . substr($r["clfoco"], 3) . '&Update"><i class="glyphicon glyphicon-edit"></i></a>';
                            }
                            echo "</td>\n";

                            // Colonna Origine
                            echo '<td style="white-space:unset;">';
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
                            echo "<td><a class=\"btn btn-xs btn-default btn-duplica\" href=\"admin_docven.php?Duplicate&id_tes=" . $r["id_tes"] . "\"><i class=\"glyphicon glyphicon-duplicate\"></i></a>";
                            echo "</td>";
// colonna elimina
                            if ($ultimoddt == $r["numdoc"] && $r['numfat'] < 1 ){
                              echo "<td>";
                              ?>
                              <a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella il documento" ref="<?php echo $r['id_tes'];?>" ragso1="<?php echo $r['ragso1'];?>">
                                <i class="glyphicon glyphicon-trash"></i>
                              </a>
                              <?php
                              echo "</td>";
                            } else{
                              echo "<td></td>";
                            }
                            echo "</tr>\n";
                            break;
                            case "DDR":
                            case "DDL":
                            $btnclass=($r['tipdoc']=='DDR')?'danger':'warning';
                            echo "<tr class=\"text-center\">";
                            // Colonna id
                            echo "<td><a class=\"btn btn-xs btn-".$btnclass."\" href=\"../acquis/admin_docacq.php?Update&id_tes=" . $r["id_tes"] . "\"><i class=\"glyphicon glyphicon-edit\"></i>" . $r["tipdoc"] . "" . $r["id_tes"] . "</a></td>";
                            echo "<td><a href=\"../acquis/admin_docacq.php?Update&id_tes=" . $r["id_tes"] . "\"> " . $r["numdoc"] . "</a>  </td>";
                            // Colonna type
                            echo "<td><div class=\"btn btn-xs btn-".$btnclass."\" style=\"cursor: default;\" > " . $script_transl['ddt_type'][$r["tipdoc"]] . "</div>  </td>";
                            echo "<td>" . gaz_format_date($r["datemi"]) . "  </td>";
                            ?>
                            <td class="text-left">
                                <a href="../acquis/report_fornit.php?nome=<?php echo htmlspecialchars($r["ragso1"]); ?>">
                                    <?php echo $r["ragso1"]; ?>
                                </a>
                            </td>
                            <td>
                                <?php echo "<a href=\"admin_destinazioni.php?codice=".$destina["codice"]."&Update\">".$destina["unita_locale1"]."</a>"; ?>
                            </td>
                            <?php
                            echo "<td><div class=\"btn btn-xs btn-".$btnclass."\">" . $script_transl['from_suppl'] . "</div></td>";

                            $urlPrintDoc = "../acquis/stampa_docacq.php?id_tes=" . $r["id_tes"] . "&template=DDT";
                            $urlPrintEtichette = "stampa_docven.php?id_tes=" . $r["id_tes"] . "&template=Etichette";
                            $targetPrintDoc = ($pdf_to_modal==0)?'href="stampa_docven.php?id_tes=' . $r["id_tes"] .'&template=DDT" target="_blank" ':"onclick=\"printPdf('stampa_docven.php?id_tes=" . $r["id_tes"] . "&template=DDT')\"";
                            echo "<td>";
                            echo '<a class="btn btn-xs btn-default" style="cursor:pointer;" '.$targetPrintDoc.' ><i class="glyphicon glyphicon-print" title="Stampa documento"></i></a>';
                            echo "<a class=\"btn btn-xs btn-default\" style=\"cursor:pointer;\" onclick=\"printPdf('".$urlPrintEtichette."')\" data-toggle=\"modal\" data-target=\"#print_doc\" ><i class=\"glyphicon glyphicon-tag\" title=\"Stampa etichetta\"></i></a>";
                            echo "</td>\n";

                            // Colonna "Mail"
                            echo "<td>";
                            if (!empty($r["e_mail"])) {
                              $gaz_custom_field = gaz_dbi_get_single_value( $gTables['tesdoc'], 'custom_field', 'id_tes = '.$r['id_tes'] );
                              if (isset($gaz_custom_field) && $gaz_custom_data = json_decode($gaz_custom_field,true)){
                                if ( !isset($gaz_custom_data['email']['ddt'])) {
                                    $classe_mail = "btn-default";
                                    $title= "Mai inviata. Inviala a ".$r["e_mail"];
                                } else {
                                    $classe_mail = "btn-success";
                                    $title="Ultimo invio: ".$gaz_custom_data['email']['ddt'];
                                }
                              }else{
                                $classe_mail = "btn-default";
                                $title= "Mai inviata. Inviala a ".$r["e_mail"];
                              }
                              echo '<a class="btn btn-xs '.$classe_mail.' btn-default btn-mail" onclick="confirMail(this);return false;" id="doc' . $r["id_tes"] . '" url="' . $urlPrintDoc . '&dest=E" href="#" title="' . $title . '"
                              mail="' . $r["e_mail"] . '" namedoc="' . $r['tipdoc'] . ' n.' . $r["numdoc"] . ' del ' . gaz_format_date($r["datemi"]) . '"><i class="glyphicon glyphicon-envelope"></i></a>';
                            } else {
                                echo '<a title="' . $script_transl['no_mail'] . '" target="_blank" href="../acquis/admin_fornit.php?codice=' . substr($r["clfoco"], 3) . '&Update"><i class="glyphicon glyphicon-edit"></i></a>';
                            }
                            echo "</td>\n";
                            echo "<td></td>";
                            if ($r['tipdoc']=='DDL'){ // i ddt per lavorazioni ricorrenti possono essere duplicati
                              echo "<td><a class=\"btn btn-xs btn-default btn-duplica\" href=\"../acquis/admin_docacq.php?Duplicate&id_tes=" . $r["id_tes"] . "\"><i class=\"glyphicon glyphicon-duplicate\"></i></a>";
                              echo "</td>";
                            } else {
                              echo "<td ></td>";
                            }
                            if ($ultimoddt == $r["numdoc"] && $r['numfat'] < 1){
                            // Colonna Elimina
                                echo "<td>";
                              ?>
                              <a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella il documento" ref="<?php echo $r['id_tes'];?>" ragso1="<?php echo $r['ragso1'];?>">
                                <i class="glyphicon glyphicon-trash"></i>
                              </a>
                              </td>
                              <?php
                            } else {
                              echo "<td></td>";
                              echo "</tr>\n";
                            }
                            break;
                        case "FAD":
                            if ( $r['ddt_type'] != 'R') {
                            echo "<tr class=\"text-center\">";
                            // Colonna id
                            echo "<td><a class=\"btn btn-xs btn-info\" href=\"admin_docven.php?Update&id_tes=" . $r["id_tes"] . "\"><i class=\"glyphicon glyphicon-edit\"></i>".$r['tipdoc']." " . $r["id_tes"] . "</a></td>";
                            // Colonna protocollo
                            echo "<td><a href=\"admin_docven.php?Update&id_tes=" . $r["id_tes"] . "\">" . $r["numdoc"] . "</a></td>";
                            // Colonna type
                            echo "<td><div class=\"btn btn-xs btn-primary btn-primary\" style=\"cursor: default;\"> " . $script_transl['ddt_type'][$r["ddt_type"]] . "</div>  </td>";
                            // Colonna Data emissione
                            echo "<td>" . gaz_format_date($r["datemi"]) . "  </td>";
                            // Colonna Cliente
                            ?>
                            <td class="text-left">
                                <a href="report_client.php?nome=<?php echo htmlspecialchars($r["ragso1"]); ?>">
                                    <?php echo $r["ragso1"]; ?>
                                </a>
                            </td>
                            <td>
                                <?php echo "<a href=\"admin_destinazioni.php?codice=".$destina["codice"]."&Update\">".$destina["unita_locale1"]."</a>"; ?>
                            </td>
                            <?php
                            // Colonna Stato
                            echo "<td style=\"white-space:unset;\"><a class=\"btn btn-xs btn-default\" title=\"" . $script_transl['print_invoice'] . " n. " . $r["numfat"] . "\" href=\"stampa_docven.php?td=2&si=" . $r["seziva"] . "&pi=" . $r['protoc'] . "&pf=" . $r['protoc'] . "&di=" . $r['datfat'] . "&df=" . $r['datfat'] . "\">Fat " . $r["numfat"] . "</a>";
                            if ($r["id_con"] > 0) {
                                echo " <a class=\"btn btn-xs btn-default btn-registrazione\" title=\"" . $script_transl['acc_entry'] . "\" href=\"../contab/admin_movcon.php?id_tes=" . $r["id_con"] . "&Update\">Cont " . $r["id_con"] . "</a>";
                            }
                            echo "</td>";

                            // Colonna stampa
                            $urlPrintDoc = "stampa_docven.php?id_tes=" . $r["id_tes"] . "&template=DDT";
                            $targetPrintDoc = ($pdf_to_modal==0)?'href="stampa_docven.php?id_tes=' . $r["id_tes"] .'&template=DDT" target="_blank" ':"onclick=\"printPdf('stampa_docven.php?id_tes=" . $r["id_tes"] . "&template=DDT')\"";
                            echo "<td>";
                            echo '<a class="btn btn-xs btn-default" style="cursor:pointer;" '.$targetPrintDoc.' ><i class="glyphicon glyphicon-print" title="'. $script_transl['print_ddt'] . " n. " . $r["numdoc"] .'"></i></a>';
                            echo "</td>";

                            // Colonna "Mail"
                            echo "<td>";
                            if (!empty($r["e_mail"])) {
                              $gaz_custom_field = gaz_dbi_get_single_value( $gTables['tesdoc'], 'custom_field', 'id_tes = '.$r['id_tes'] );
                              if (isset($gaz_custom_field) && $gaz_custom_data = json_decode($gaz_custom_field,true)){
                                if ( !isset($gaz_custom_data['email']['ddt'])) {
                                    $classe_mail = "btn-default";
                                    $title= "Mai inviata. Inviala a ".$r["e_mail"];
                                } else {
                                    $classe_mail = "btn-success";
                                    $title="Ultimo invio: ".$gaz_custom_data['email']['ddt'];
                                }
                              }else{
                                $classe_mail = "btn-default";
                                $title= "Mai inviata. Inviala a ".$r["e_mail"];
                              }
                              echo '<a class="btn btn-xs '.$classe_mail.' btn-default btn-mail" onclick="confirMail(this);return false;" id="doc' . $r["id_tes"] . '" url="' . $urlPrintDoc . '&dest=E" href="#" title="' . $title . '"
                              mail="' . $r["e_mail"] . '" namedoc="DDT n.' . $r["numdoc"] . ' del ' . gaz_format_date($r["datemi"]) . '"><i class="glyphicon glyphicon-envelope"></i></a>';
                            } else {
                                echo '<a title="' . $script_transl['no_mail'] . '" target="_blank" href="admin_client.php?codice=' . substr($r["clfoco"], 3) . '&Update"><i class="glyphicon glyphicon-edit"></i></a>';
                            }
                            echo "</td>";
                            // Colonna origine
                            echo '<td style="white-space:unset;">';
                            $resorigine = gaz_dbi_dyn_query('*', $gTables['rigdoc'], "id_tes = " . $r["id_tes"], 'id_tes', 1,1);
                            if ( gaz_dbi_num_rows( $resorigine )>0 ) {
                                $rigdoc_result = gaz_dbi_dyn_query('DISTINCT id_order', $gTables['rigdoc'], "id_tes = " . $r["id_tes"], 'id_tes');
                                while ( $rigdoc = gaz_dbi_fetch_array($rigdoc_result) ) {
                                    if($rigdoc['id_order']>0){
                                        $tesbro_result = gaz_dbi_dyn_query('*', $gTables['tesbro'], "id_tes = " . $rigdoc['id_order'], 'id_tes');
                                        $t_r = gaz_dbi_fetch_array($tesbro_result);
                                        if ($t_r) {
                                         echo "<a title=\"" . $script_transl['view_ord'] . "\" href=\"stampa_ordcli.php?id_tes=" . $rigdoc['id_order'] . "\" style=\"font-size:10px;\">Ord." . $t_r['numdoc'] . "</a>\n";
                                        }
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
            ?>
            <tr><th class="FacetFieldCaptionTD" colspan="12"></th></tr>
        </table>
    </div>
</form>
<script>
$(document).ready(function(){
  var _sezi = $("select[name='sezione'] option:selected").text().trim();
  $.each(['DDT','CMR'], function( i, v ) {
    var _href = $("a[href*='admin_docven.php?Insert&tipdoc=" + v + "']").attr('href');
    $("a[href*='admin_docven.php?Insert&tipdoc=" + v + "']").attr('href', _href + '&seziva=' + _sezi);
  });
});
</script>

<script src="../../js/custom/fix_select.js" type="text/javascript"></script>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>
