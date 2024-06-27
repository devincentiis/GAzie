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
require ("../../modules/vendit/lib.function.php");
$lm = new lotmag;
$admin_aziend = checkAdmin();
$message = "";

$partner_select = !gaz_dbi_get_row($gTables['company_config'], 'var', 'partner_select_mode')['val'];
$tesdoc_e_partners = $gTables['tesdoc'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesdoc'] . ".clfoco = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id LEFT JOIN ' . $gTables['fae_flux'] . " ON " . $gTables['tesdoc'] . ".id_tes = " . $gTables['fae_flux'] . '.id_tes_ref';

// campi ammissibili per la ricerca
$search_fields = [
    'sezione'
        => "seziva = %d",
    'proto'
        => "protoc = %d",
    'tipo'
        => "tipdoc LIKE '%s'",
    'numero'
        => "numfat LIKE '%%%s%%'",
    'anno'
        => "YEAR(datreg) = %d",
    'fornitore'
        => $partner_select ? "clfoco = '%s'" : "ragso1 LIKE '%s%%'"
];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array(
    "Prot." => "protoc",
    "Dat.Reg." => "datreg",
    "Documento" => "tipdoc",
    "Numero" => "numfat",
    "Data" => "datfat",
    "Fornitore" => "ragso1",
    "Info" => "",
    "Stampa" => "",
    "Cancella" => ""
);

require("../../library/include/header.php");
$script_transl = HeadMain();
if (count($_GET)<1) {
	// ultima fattura registrata
	$rs_last = gaz_dbi_dyn_query('seziva, YEAR(datreg) AS yearde', $gTables['tesdoc'], "tipdoc LIKE 'AF%'", 'datreg DESC, id_tes DESC', 0, 1);
	$last = gaz_dbi_fetch_array($rs_last);
	if ($last) {
		$default_where=['sezione' => $last['seziva'], 'tipo' => 'AF_', 'anno'=>$last['yearde']];
		$_GET['anno']=$last['yearde'];
	} else {
		$default_where=['sezione' => 1, 'tipo' => 'AF_', 'anno'=> date('Y')];
	}
} else {
	$si=(isset($_GET['sezione']))?intval($_GET['sezione']):1;
	$default_where=['sezione' => $si, 'tipo' => 'AF_'];
}
$ts = new TableSorter(
    !$partner_select && isset($_GET["fornitore"]) ? $tesdoc_e_partners : $gTables['tesdoc'],
    $passo,
    ['datreg' => 'desc', 'protoc' => 'desc'],
    $default_where
);

# le select spaziano solo tra i documenti d'acquisto del sezionale corrente
$where_select = sprintf("tipdoc LIKE 'AF_' AND seziva = %d", $sezione);

?>
<script>
$(function() {
	$( "#dialog_fae" ).dialog({
		autoOpen: false
	});

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
						data: {'type':'docacq',id_tes:id},
						type: 'POST',
						url: '../acquis/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_docacq.php");
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

function confirFae(link){
	tes_id = link.id.replace("doc1_", "");
	$.fx.speeds._default = 500;
	var dialog_fae_title = $("#dialog_fae_title").attr("title") + $("#doc1_"+tes_id).attr("dialog_fae_numfat");
    $("#dialog_fae_filename span").html("<a href=\'"+link.href+"\' >"+$("#doc1_"+tes_id).attr("dialog_fae_filename")+"</a>");
	var numrei = parseInt($("#doc1_"+tes_id).attr("dialog_fae_numrei"))+1;
    var flux_status = $("#doc1_"+tes_id).attr("dialog_flux_status");
    var flux_descri = $("#doc1_"+tes_id).attr("dialog_flux_descri");
    var sdiflux = $("#doc1_"+tes_id).attr("dialog_fae_sdiflux");
    var zipref = $("#doc1_"+tes_id).attr("zip_ref");
    sdiflux = (sdiflux)?"&sdiflux="+sdiflux:"";
    switch (flux_status) {
        case "PA":
            $("#dialog_fae_content_PA").addClass("bg-default");
            $("#dialog_fae_content_PA").show();
            console.log(flux_status);
        break;
        case "DI":
            $("#dialog_fae_content_DI").addClass("bg-default");
            $("#dialog_fae_content_DI span").html("<p class=\'text-center\'><a href=\'"+link.href+"&invia"+sdiflux+"\' class=\'btn btn-default\'><b><i class=\'glyphicon glyphicon-send\'></i> Invia solo " + $("#doc1_"+tes_id).attr("dialog_fae_filename")+ "</i> </b></a></p><p><a href=\'..\\vendit\\"+zipref+"\' class=\'btn btn-warning\'><b><i class=\'glyphicon glyphicon-compressed\'> </i> Impacchetta con eventuali altri precedenti</b></a></p>");
            $("#dialog_fae_content_DI").show();
            console.log(flux_status);
        break;
        case "ZI":
            $("#dialog_fae_content_ZI").addClass("bg-default");
            $("#dialog_fae_content_ZI span").html("<p class=\'text-center\'><a href=\'"+link.href+"&invia"+sdiflux+"\' class=\'btn btn-warning\'><b><i class=\'glyphicon glyphicon-send\'></i> Invia il pacchetto " + $("#doc1_"+tes_id).attr("dialog_fae_filename")+ "</i> </b></a></p><p></p>");
            $("#dialog_fae_content_ZI").show();
            console.log(flux_status);
        break;
        case "RC":
            $("#dialog_fae_content_RC").addClass("bg-success text-center");
            $("#dialog_fae_content_RC").show();
            console.log(flux_status);
        break;
        case "MC":
            $("#dialog_fae_content_MC").addClass("bg-warning text-center");
            $("#dialog_fae_content_MC").show();
            console.log(flux_status);
        break;
        case "NS":
            $("#dialog_fae_content_NS span").html("<p class=\'text-center bg-danger\'>" + flux_descri.replace(/<[^>]*>?/gm, "") + "</p><p class=\'text-center\'> re: <a href=\'"+link.href+"&reinvia"+sdiflux+"\' class=\'btn btn-danger\'><b> " + $("#doc1_"+tes_id).attr("dialog_fae_reinvio")+ "</b> <br/>" + numrei.toString() + "° reinvio </a></p>");
            $("#dialog_fae_content_NS").show();
            console.log(flux_status);
        break;
        case "RE":
            $("#dialog_fae_content_RE").addClass("bg-info text-center");
            $("#dialog_fae_content_RE span").html("<p><a href=\'"+link.href+"&reinvia\' class=\'btn btn-danger\'>" + $("#doc1_"+tes_id).attr("dialog_fae_reinvio")+ " <br/>" + numrei.toString() + "° reinvio </a></p><p>Oppure <a href=\'"+zipref+"&packet\' class=\'btn btn-warning\'><b><i class=\'glyphicon glyphicon-compressed\'> </i></b> Impacchetta con eventuali altri precedenti</a></p>");
            $("#dialog_fae_content_RE").show();
            console.log(flux_status);
        break;
        case "RZ":
            $("#dialog_fae_content_RE").addClass("bg-info text-center");
            $("#dialog_fae_content_RE span").html("<p><a href=\'"+link.href+"&reinvia\' class=\'btn btn-danger\'>" + $("#doc1_"+tes_id).attr("dialog_fae_reinvio")+ " <br/>" + numrei.toString() + "° reinvio </a></p>");
            $("#dialog_fae_content_RE").show();
            console.log(flux_status);
        break;
        default:
            console.log("errore: stato "+flux_status+" non identificato");
    }
	$("#dialog_fae").dialog({
	  title: dialog_fae_title,
      modal: "true",
      show: "blind",
      hide: "explode",
      buttons: {" X ": function() {
                        $(".dialog_fae_content").hide();
                        $(this).dialog("close");
                 }
               }
         });
	$("#dialog_fae").dialog( "open" );
}
$(function() {
	$("#dialog_packet").dialog({ autoOpen: false });
	$('.dialog_packet').click(function() {
		$("p#idcodice").html("<a title='scarica il pacchetto' class='btn btn-xs btn-warning ' href='fae_acq_packaging.php?name=" + $(this).attr('ref') + "'><i class='glyphicon glyphicon-compressed'></i>"+ $(this).attr('ref') +"</a>");
		var id = $(this).attr('ref');
		$( "#dialog_packet" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				"Invia Email":{
					text:'Invia email al consulente',
					'class':'btn btn-success',
					click:function (event, ui) {
						$("#mailbutt div").remove();
						var dest=$("#mailaddress").val();
						$("#mailaddress").val('');
						$('#frame_email').attr('src','fae_acq_packaging.php'+'?name='+id+'&email='+'email');
						$('#frame_email').css({'height': '100%'});
						$('.frame_email').css({'display': 'block','width': '40%', 'margin-left': '25%', 'z-index':'2000'});
						$('#close_email').on( "click", function() {
						$('#frame_email').attr('src','');
						$('.frame_email').css({'display': 'none'});
						});
						$(this).dialog("close");
					}
				},
				delete:{
					text:'Elimina il pacchetto',
					'class':'btn btn-danger',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'packacq','ref':id},
						type: 'POST',
						url: '../acquis/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_docacq.php");
						}
					});
					}
				},
				"Esci": function() {
					$(this).dialog("close");
				}

			},
			close: function(){
				$("#mailbutt div").remove();
				$("#mailaddress").val('');
				$(this).dialog('destroy');
			}
		});
		$("#dialog_packet" ).dialog( "open" );
	});
});
</script>
<form method="GET" class="clean_get">
	<div class="frame_email panel panel-success" style="display: none; position: fixed; left: 5%; top: 15%; margin-left: 30%; height: 40%">
		<div class="col-lg-12">
			<div class="col-xs-11"><h4>e-mail</h4></div>
			<div class="col-xs-1"><h4><button type="button" id="close_email"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
		</div>
		<iframe id="frame_email"  style="height: 90%; width: 100%" src=""></iframe>
	</div>
	<div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
		<div class="col-lg-12">
			<div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
			<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
		</div>
		<iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
	</div>

    <div style="display:none" id="dialog_fae">
        <div style="display:none;" id="dialog_fae_title" title="<?php echo $script_transl['dialog_fae_title']; ?>"></div>
        <p class="ui-state-highlight" id="dialog_fae_filename"><?php echo $script_transl['dialog_fae_filename']; ?><span></span></p>
        <?php
        $statuskeys=array('PA','DI','RE','IN','RC','MC','NS','ZI');
        foreach ( $statuskeys as $v ) {
            echo '<p style="display:none;" class="dialog_fae_content" id="dialog_fae_content_'.$v.'">'.$script_transl['dialog_fae_content_'.$v]."<span></span></p>";
        }
        ?>
    </div>
  <input type="hidden" name="info" value="none" />
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>documento di acquisto:</b></p>
        <p>ID:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Fornitore</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
	<div style="display:none" id="dialog_packet" title="Pacchetto di fatture di acquisto">
        <p><b>Scarica il pacchetto:</b></p>
        <p class="ui-state-highlight" id="idcodice"></p>
		<p><b>Invia il pacchetto</b></p>
	</div>
    <div align="center" class="FacetFormHeaderFont">
        <?php echo $script_transl['title']; ?>

        <select name="sezione" class="FacetSelect" onchange="this.form.submit()">
<?php
            echo "<option value=''>1</option>\n"; # è l'opzione di default perciò ha valore vuoto
            for ($sez = 2; $sez <= 9; $sez++) {
                $selected = $sezione == $sez ? "selected" : "";
                echo "<option value='$sez' $selected > $sez </option>\n";
            }
?>
        </select>
    </div>
<?php
    $ts->output_navbar();
?>
    <div class="box-primary table-responsive">
    <table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
        <tr>
            <td colspan="1" class="FacetFieldCaptionTD">
                <input type="text" placeholder="Cerca Prot." class="input-sm form-control" name="proto" value="<?php if (isset($proto)) print $proto; ?>" maxlength="6" tabindex="1" class="FacetInput">
            </td>
            <td colspan="1" class="FacetFieldCaptionTD">
<?php
                gaz_flt_disp_select("anno", "YEAR(datreg) AS anno", $gTables["tesdoc"],  $where_select, "anno DESC");
?>
            </td>
            <td colspan="1" class="FacetFieldCaptionTD">
<?php
                gaz_flt_disp_select("tipo", "tipdoc as tipo", $gTables["tesdoc"], $where_select, "tipo ASC");
?>
            </td>
            <td colspan="1" class="FacetFieldCaptionTD">
                <input type="text" placeholder="Cerca Num." class="input-sm form-control" name="numero" value="<?php if (isset($numero)) print $numero; ?>" tabindex="3" class="FacetInput">
            </td>
            <td colspan="1" class="FacetFieldCaptionTD">
            </td>
            <td colspan="1" class="FacetFieldCaptionTD">
<?php
                if ($partner_select) {
                    gaz_flt_disp_select("fornitore", "clfoco AS fornitore, ragso1 as nome",
                        $tesdoc_e_partners,
                        $where_select, "nome ASC", "nome");
                } else {
?>
                    <input type="text" placeholder="Cerca fornitore" class="input-sm form-control" name="fornitore" value="<?php if (isset($fornitore)) print $fornitore; ?>" tabindex="5" class="FacetInput">
<?php
                }
?>
            </td>
            <td colspan="1" class="FacetFieldCaptionTD">
                &nbsp;
            </td>
            <td colspan="1" class="FacetFieldCaptionTD">
                <input type="submit" class="btn btn-sm btn-default" name="search" value="Cerca" tabindex="6" onClick="javascript:document.report.all.value = 1;">
                <?php $ts->output_order_form(); ?>
            </td>
            <td colspan="1" class="FacetFieldCaptionTD">
                <a class="btn btn-xs btn-default" href="?" tabindex="7">Reset</a>
            </td>
        </tr>
        <tr>
<?php
			$ts->output_headers();
?>
        </tr>
<?php
//recupero le testate in base alle scelte impostate
$result = gaz_dbi_dyn_query($gTables['anagra'].".ragso1,".$gTables['tesdoc'].".*",$tesdoc_e_partners, $ts->where, $ts->orderby, $ts->getOffset(), $ts->getLimit(),"protoc,datfat");
$paymov = new Schedule();

// creo un array con gli ultimi documenti dei vari anni (gli unici eliminabili senza far saltare il protocollo del registro IVA)
$rs_last_docs = gaz_dbi_query("SELECT id_tes
  FROM ".$gTables['tesdoc']." AS t1
  JOIN ( SELECT MAX(protoc) AS max_protoc FROM ".$gTables['tesdoc']." WHERE tipdoc LIKE 'AF_' AND seziva = ".$sezione." GROUP BY YEAR(datreg)) AS t2
  ON t1.protoc = t2.max_protoc WHERE t1.tipdoc LIKE 'AF_' AND t1.seziva = ".$sezione);
$year_last_protoc_id_tes=[];
while ($ld = gaz_dbi_fetch_array($rs_last_docs)){
	$year_last_protoc_id_tes[$ld['id_tes']]=true;
}
// fine creazione array con i documenti eliminabili
$sdi_flux = gaz_dbi_get_row($gTables['company_config'], 'var', 'send_fae_zip_package')['val'];

while ($row = gaz_dbi_fetch_array($result)) {
  // faccio il check per vedere se ci sono righi da trasferire in contabilità di magazzino
  $ck = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes=". $row['id_tes']." AND  LENGTH(TRIM(codart))>=1 AND tiprig=0 AND id_mag=0");
  $check = gaz_dbi_fetch_array($ck);
  // fine check magazzino
	// se contabilizzato trovo l'eventuale stato dei pagamenti
	$paymov_status = false;
	if ($row['id_con'] > 0) {
		$tesmov = gaz_dbi_get_row($gTables['tesmov'], 'id_tes', $row['id_con']);
		if ($tesmov) {
			$paymov->getStatus(substr($tesmov['datreg'],0,4).$tesmov['regiva'].$tesmov['seziva']. str_pad($tesmov['protoc'], 9, 0, STR_PAD_LEFT)); // passo il valore formattato di id_tesdoc_ref
			$paymov_status = $paymov->Status;
			// riprendo il rigo  della contabilità con il cliente per avere l'importo
			$importo = gaz_dbi_get_row($gTables['rigmoc'], 'id_tes', $row['id_con'], "AND codcon = ".$row['clfoco']);
		}
	}
	$template="";
	$y = substr($row['datfat'], 0, 4);
	$btncol='edit';
	if ($row["tipdoc"] == 'AFA') {
		$tipodoc = "Fattura";
		$modulo = "stampa_docacq.php?id_tes=" . $row['id_tes']."&template=".$template;
		$modifi = "admin_docacq.php?Update&id_tes=" . $row['id_tes'];
	} elseif ($row["tipdoc"] == 'AFD') {
		$tipodoc = "Nota Debito";
		$modulo = "stampa_docacq.php?id_tes=" . $row['id_tes']."&template=".$template;
		$modifi = "admin_docacq.php?Update&id_tes=" . $row['id_tes'];
	} elseif ($row["tipdoc"] == 'AFC') {
		$tipodoc = "Nota Credito";
		$modulo = "stampa_docacq.php?id_tes=" . $row['id_tes']."&template=".$template;
		$modifi = "admin_docacq.php?Update&id_tes=" . $row['id_tes'];
		$btncol='danger';
	} elseif ($row["tipdoc"] == 'AFT') {
		$tipodoc = "Fattura";
		$modulo = "stampa_docacq.php?id_tes=" . $row['id_tes']."&template=".$template;
		$modifi = "";
	}
  echo '<tr class="FacetDataTD"><td align="center">';
  if (!empty($modifi)) {
    echo '<a class="btn btn-xs btn-'.$btncol.'" href="' . $modifi . "\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;" . $row["protoc"] . "</td>";
  } else {
    echo '<button class="btn btn-xs btn-'.$btncol.' disabled" title="Fattura differita, puoi modificare solo i DdT">' . $row["protoc"] . " &nbsp;</button></td>";
  }
  echo "<td>" . gaz_format_date($row["datreg"]) . " &nbsp;</td>";
  if (empty($row["fattura_elettronica_original_name"])) {
    print '<td>'.$tipodoc."</td>\n";
  } else {
    print '<td>';
    print '<a class="btn btn-xs btn-default btn-xml" target="_blank" href="view_fae.php?id_tes=' . $row["id_tes"] . '">'.$tipodoc.' '.$row["fattura_elettronica_original_name"] . '</a>';
    print '<a class="btn btn-xs btn-default" href="download_fattura_elettronica.php?id='.$row["id_tes"].'"><i class="glyphicon glyphicon-download"></i></a>';
    print '</td>';
  }
  echo "<td>" . $row["numfat"] . " &nbsp;</td>";
  echo "<td>" . gaz_format_date($row["datfat"]) . " &nbsp;</td>";
  echo "<td><a title=\"Dettagli fornitore\" href=\"report_fornit.php?nome=" . htmlspecialchars($row["ragso1"]) . "\">" . $row["ragso1"] . ((empty($row["ragso2"]))?"":" ".$row["ragso2"]) . "</a>&nbsp;</td>";
// Colonna movimenti (info)
  echo "<td align=\"center\">";
  if ($row["id_con"] > 0) {
    // non usando le transazioni devo aggiunger un controllo di effettiva esistenza della testata di movimento contabile, se qualcosa non è andato per il verso giusto elimini il riferimento
    $existtesmov = gaz_dbi_get_row($gTables['tesmov'], 'id_tes', $row['id_con']);
    $revch = gaz_dbi_get_row($gTables['tesdoc'] . " LEFT JOIN " . $gTables['fae_flux'] . " ON " . $gTables['tesdoc'] . ".id_tes=" . $gTables['fae_flux'] . ".id_tes_ref", $gTables['tesdoc'] . ".datfat", $row['datfat'], "AND " . $gTables['tesdoc'] . ".numfat = '".$row['numfat']."' AND " . $gTables['tesdoc'] . ".clfoco = ".$row['clfoco']." AND " . $gTables['tesdoc'] . ".tipdoc LIKE 'X__'", $gTables['tesdoc'] . ".*, GROUP_CONCAT(" . $gTables['fae_flux'] . ".flux_descri ORDER BY " . $gTables['fae_flux'] . ".received_date DESC) AS flux_descri, GROUP_CONCAT(" . $gTables['fae_flux'] . ".flux_status ORDER BY " . $gTables['fae_flux'] . ".received_date DESC, exec_date DESC) AS refs_flux_status"); // controllo l'esistenza di una fattura reverse charge per XML
    if (isset($revch) && !empty($revch['id_tes'])) {
		$faename_base = 62;
		$faename_maxsez = 9;
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$faename_base = 36;
			$faename_maxsez = 5;
		}
		$modulo_fae = '../vendit/electronic_invoice.php?id_tes=' . $revch['id_tes'];
		$revch['fae_attuale'] = 'IT' . $admin_aziend['codfis'] . '_' . encodeSendingNumber([
			'azienda' => $admin_aziend['codice'],
			'sezione' => min($faename_maxsez, $revch['seziva']),
			'anno' => '200'.$revch['seziva'],
			'fae_reinvii' => substr($revch['datreg'],3,1),
			'protocollo' => intval($revch['fattura_elettronica_reinvii']*10000 + $revch['protoc'])
		], $faename_base) . '.xml';
		$revch['fae_reinvio'] = 'IT' . $admin_aziend['codfis'] . '_' . encodeSendingNumber([
			'azienda' => $admin_aziend['codice'],
			'sezione' => min($faename_maxsez, $revch['seziva']),
			'anno' => '200'.$revch['seziva'],
			'fae_reinvii' => substr($revch['datreg'],3,1),
			'protocollo' => intval(($revch['fattura_elettronica_reinvii']+1)*10000 + $revch['protoc'])
		], $faename_base) . '.xml';
		$zipped = (preg_match("/^[A-Z0-9]{13,18}_([a-zA-Z0-9]{5}).zip$/",(is_string($revch['fattura_elettronica_zip_package'])?$revch['fattura_elettronica_zip_package']:''),$match))?$match[1]:false;
		if ($zipped) { // se è contenuto in un pacchetto di file permetterà sia il download del singolo XML che del pacchetto in cui è contenuto
			if ($revch['fattura_elettronica_reinvii']==0) {
				echo '<a class="btn btn-xs btn-success" title="Pacchetto di fatture elettroniche in cui &egrave; contenuta questa fattura" href="../vendit/download_zip_package.php?fn='.$revch['fattura_elettronica_zip_package'].'">'.$zipped.'.zip<i class="glyphicon glyphicon-compressed"></i> </a>';
			}
		}
		if ($sdi_flux) { // ho un modulo per la gestione dei flussi con il SdI: posso visualizzare lo stato
			$zip_ref = 'fae_packaging.php?sdiflux='.$sdi_flux;
			if ($revch['refs_flux_status']==null) {
				$last_flux_status = '';
			} else {
				$last_flux_status = explode(',',$revch['refs_flux_status'])[0];
			}
			$sdihilight = ( !empty($revch['refs_flux_status']) ) ? $script_transl['flux_status_val'][$last_flux_status][1] : 'default';
			$sdilabel = ( !empty($revch['refs_flux_status']) ) ? $script_transl['flux_status_val'][$last_flux_status][0] : 'da inviare';
			if (empty($last_flux_status)) {
				$last_flux_status = 'DI';
			}
			if (is_string($revch['fattura_elettronica_zip_package']) && strlen($revch['fattura_elettronica_zip_package'])>10 && $last_flux_status == 'DI') { // il documento è impacchettato e da inviare
				$revch['fae_attuale'] = $revch['fattura_elettronica_zip_package'];
				$sdihilight = ( !empty($revch['refs_flux_status']) ) ? $script_transl['flux_status_val'][$last_flux_status][1] : 'default';
				$sdilabel = ( !empty($revch['refs_flux_status']) ) ? $script_transl['flux_status_val'][$last_flux_status][0] : 'ZIP da inviare';
				$last_flux_status = 'ZI';
			}
		} else { //// installazione senza gestore dei flussi con il SdI
			$last_flux_status = ($zipped)?'RZ':'RE'; // gestendo il flusso manualmente darò sempre la possibilità di scegliere se reinviare o scaricare l'xml
			$zip_ref = 'fae_packaging.php?nolib';
			$sdihilight = 'default';
			$sdilabel = 'xml';
		}
		switch ($last_flux_status) {
			case "DI":
				$sdititle = 'Invia il file '.$revch['fae_attuale'].' o pacchetto';
				break;
			case "PC":
				$sdititle = 'Il file '.$revch['fae_attuale'].' è stato inviato al Sistema di Interscambio, attendere l\'esito ';
				break;
			case "RE":
				$sdititle = 'Invia il file '.$revch['fae_attuale'].' al Sistema di Interscambio ';
				break;
			case "IN":
				$sdititle = 'Il file '.$revch['fae_attuale'].' è stato inviato al Sistema di Interscambio, attendere la risposta di presa in carico ';
				break;
			case "RC":
				$sdititle = 'Il file '.$revch['fae_attuale'].' è stato inviato e consegnato al Sistema di Interscambio ';
				break;
			case "MC":
				$sdititle = 'Il file '.$revch['fae_attuale'].' è stato inviato e consegnato al Sistema di Interscambio ma non consegnato al destinatario ';
				break;
			case "NS":
				$sdititle = 'Il file '.$revch['fae_attuale'].' è stato Scartato, correggi prima di fare il reinviio ';
				break;
			default:
				$sdititle = 'genera il file '.$revch['fae_attuale'].' o fai il '.intval($revch['fattura_elettronica_reinvii']+1).'° reinvio ';
				break;
		}
		echo '<a class="btn btn-xs btn-'.$sdihilight.' btn-xml" onclick="confirFae(this);return false;" id="doc1_'.$revch['id_tes'].'" dialog_fae_reinvio="'.$revch['fae_reinvio'].'" dialog_flux_descri="'.(is_string($revch['flux_descri'])?htmlentities($revch['flux_descri']):'').'" dialog_fae_sdiflux="'.$sdi_flux.'" dialog_fae_filename="'.$revch['fae_attuale'].'" dialog_fae_numrei="'.$revch['fattura_elettronica_reinvii'].'" dialog_fae_numfat="'. $revch['tipdoc'].' '. $revch['numfat'].'/'. $revch['seziva'].'" dialog_flux_status="'. $last_flux_status.'" target="_blank" href="'.$modulo_fae.'" zip_ref="'.$zip_ref.'" title="'.$sdititle.'"> '.strtoupper($sdilabel).' </a><a class="btn btn-xs btn-default" title="Visualizza in stile" href="../vendit/electronic_invoice.php?id_tes='.$revch['id_tes'].'&viewxml" target="_blank"><i class="glyphicon glyphicon-eye-open"></i> </a>';
		if ($revch['fattura_elettronica_reinvii'] > 0) {
			echo '<br/><small>' . $revch['fattura_elettronica_reinvii'] . ($revch['fattura_elettronica_reinvii']==1 ? ' reinvio' : ' reinvii') . '</small><br/>';
		}
    }

    if ($existtesmov){
      echo " <a class=\"btn btn-xs btn-".$paymov_status['style']."\" style=\"font-size:10px;\" title=\"Modifica il movimento contabile " . $row["id_con"] . " generato da questo documento\" href=\"../contab/admin_movcon.php?id_tes=" . $row["id_con"] . "&Update\"> <i class=\"glyphicon glyphicon-euro\"></i> " .((isset($importo["import"]))?$importo["import"]:'0.00'). "</a> ";
    } else {
      echo "<a class=\"btn btn-xs btn-danger\" href=\"\">Contabilizzazione persa!</a>";
    }
  } else {
    echo "<a class=\"btn btn-xs btn-default btn-cont\" href=\"accounting_documents.php?type=AF&datreg=".gaz_format_date($row["datreg"],false,3)."&last=" . $row["protoc"] . "\">Contabilizza</a>";
  }
  if ($row['fattura_elettronica_zip_package'] != '' && strlen($row['fattura_elettronica_zip_package']) > 4){// se è stato creato un pacchetto .zip
	//echo "<a title=\"scarica il pacchetto\" class=\"btn btn-xs btn-warning \" href=\"fae_acq_packaging.php?name=" . $row['fattura_elettronica_zip_package'] . "\"><i class=\"glyphicon glyphicon-compressed\"></i> ".substr($row['fattura_elettronica_zip_package'],0,19)."</a>";
	?>
	<a class="btn btn-xs  btn-elimina dialog_packet" title="Apri il popup del pacchetto" ref="<?php echo $row['fattura_elettronica_zip_package'];?>">
		<i class="glyphicon glyphicon-compressed"></i>
		<?php echo substr($row['fattura_elettronica_zip_package'],0,19); ?>
	</a>
	<?php
  }
  if ($check) { // ho qualche rigo da traferire
    echo " <a class=\"btn btn-xs btn-default btn-warning\" href=\"../magazz/genera_movmag.php\">Movimenta magazzino</a> ";
  }
  echo "</td>";
  echo "<td align=\"center\"><a class=\"btn btn-xs btn-default\" style=\"cursor:pointer;\" onclick=\"printPdf('".$modulo."')\"><i class=\"glyphicon glyphicon-print\" title=\"Stampa documento PDF\"></i></a></td>";
  echo "<td>";

  $check_lot_exit = $lm -> check_lot_exit("",$row['id_tes']);// controllo se è già uscito qualche prodotto con lo stesso id lotto

  // faccio il controllo di eliminazione dell'ultima fattura ricevuta
  if (isset($year_last_protoc_id_tes[$row['id_tes']]) && $check_lot_exit===FALSE) {
	?>
	<a class="btn btn-xs  btn-elimina dialog_delete" title="Elimina questo documento" ref="<?php echo $row['id_tes'];?>" fornitore="<?php echo $row['ragso1']; ?>">
		<i class="glyphicon glyphicon-trash"></i>
	</a>
	<?php

  }elseif($check_lot_exit===TRUE){
    ?>
    <button title="Non puoi eliminare questo DDT perché almeno uno dei suoi articoli ha un ID lotto che è già uscito dal magazzino" class="btn btn-xs   disabled"><i class="glyphicon glyphicon-trash"></i></button>
    <?php
  } else {
		?>
		<button title="Non puoi eliminare un documento diverso dall'ultimo emesso" class="btn btn-xs   disabled"><i class="glyphicon glyphicon-trash"></i></button>
		<?php
  }
  echo "</td></tr>";
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
