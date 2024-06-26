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
$admin_aziend = checkAdmin();
$pdf_to_modal = gaz_dbi_get_row($gTables['company_config'], 'var', 'pdf_reports_send_to_modal')['val'];
$partner_select = !gaz_dbi_get_row($gTables['company_config'], 'var', 'partner_select_mode')['val'];
$tesbro_e_partners = $gTables['tesbro'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesbro'] . ".clfoco = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id';

if (isset($_GET['flt_tipo'])) {
  $flt_tipo = substr($_GET['flt_tipo'],0,3);
} else {
	$flt_tipo='APR';
}

// funzione di utilità generale, adatta a mysqli.inc.php
function cols_from($table_name, ...$col_names) {
    $full_names = array_map(function ($col_name) use ($table_name) { return "$table_name.$col_name"; }, $col_names);
    return implode(", ", $full_names);
}

// campi ammissibili per la ricerca
$search_fields = [
    'sezione' => "seziva = %d",
    'numdoc'  => "numdoc = %d",
    'id_orderman'  => "id_orderman = %d",
    'flt_tipo'    => "tipdoc LIKE '%s'",
    'numero'  => "numfat LIKE '%%%s%%'",
    'anno'    => "YEAR(datemi) = %d",
    'fornitore'=> $partner_select ? "clfoco = '%s'" : "ragso1 LIKE '%%%s%%'"
];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array(
    "Numero" => "id_tes",
    "Produzione" => "id_orderman",
    "Data" => "datemi",
    "Fornitore" => "",
    "Stato" => "",
    "Stampa" => "",
    "Operazioni<br/>Stato" => "",
    "Mail" => "",
    "Cancella" => ""
);

/*
// prendo i dati facendo il join con le anagrafiche
$what=$gTables['tesbro'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesbro'] . ".clfoco = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['clfoco'] . ".id_anagra = " . $gTables['anagra'] . ".id";*/

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('custom/modal_form'));

$ts = new TableSorter(
    !$partner_select && isset($_GET["fornitore"]) ? $tesbro_e_partners : $gTables['tesbro'],
    $passo,
    ['id_tes' => 'desc'],
    ['sezione'=>1, 'flt_tipo'=>$flt_tipo]
);

$gForm = new acquisForm();
?>
<script>
function confirmemail(cod_partner,id_tes,genorder=false) {
	var fornitore=$("#fornitore_"+id_tes).attr('value');
	var tipdoc=$("#tipdoc_"+id_tes).attr('value');
	if (tipdoc=='AOR') {
			$("#confirm_email").attr('title', 'Invia ORDINE a '+fornitore);
	} else if (tipdoc=='APR' && genorder ) {
			$("#confirm_email").attr('title', 'Genera ORDINE a '+fornitore);
			$("#mailaddress").remove();
			$("#mailbutt").remove();
			$("#maillabel").remove();
	} else {
			$("#confirm_email").attr('title', 'Invia Preventivo a '+fornitore);
	}
	$.get("search_email_address.php",
		  {clfoco: cod_partner},
		  function (data) {
			var j=0;
			$.each(data, function (i, value) {
				if (j==0){
					$("#mailbutt").append("<div>Indirizzi archiviati:</div>");
				}
				$("#mailbutt").append("<div id='rowmail_"+j+"' align='center'><button id='fillmail_" + j+"'>" + value.email + "</button></div>");
                $("#fillmail_" + j).click(function () {
					$("#mailaddress").val(value.email);
				});
				$("#rowmail_"+j).append(" <button id='deletemail_" + j+"' class='btn-elimina' title='rimuovi indirizzo'> <i class='glyphicon glyphicon-trash'></i> </button>");
				$("#deletemail_" + j).click(function () { // cliccando sulla X elimino da tesbro una email non più utilizzabile
					// richiamo il delete.php per eliminare la email dalle tesbro
					$.ajax({
						data: {'type':'email',ref:value.email},
						type: 'POST',
						url: '../acquis/delete.php',
						success: function(output){
							window.location.replace("./report_broacq.php?flt_tipo=<?php echo $flt_tipo; ?> ");
						}
					});
				});
				j++;
			});
		  }, "json"
         );

	$( function() {
    var dialog
	,
    emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/,
	dialog = $("#confirm_email").dialog({
		modal: true,
		show: "blind",
		hide: "explode",
		width: "auto",
		buttons: {
			Annulla: function() {
				$(this).dialog('close');
			},
			Conferma: function() {
				if ( !( emailRegex.test( $("#mailaddress").val() ) ) && !genorder ) {
					alert('Mail formalmente errata');
				} else {
					$("#mailbutt div").remove();
					var dest=$("#mailaddress").val();
					if (tipdoc=='AOR') { // è già un ordine lo reinvio
            $('#frame_email').attr('src','stampa_ordfor.php?id_tes='+id_tes+"&dest="+dest);
            $('#frame_email').css({'height': '100%'});
            $('.frame_email').css({'display': 'block','width': '40%', 'margin-left': '25%', 'z-index':'2000'});
            $('#close_email').on( "click", function() {
            $('#frame_email').attr('src','');
            $('.frame_email').css({'display': 'none'});
            });
            $(this).dialog("close");
						//window.location.href = 'stampa_ordfor.php?id_tes='+id_tes+'&dest='+dest;
					} else if (tipdoc=='APR' && genorder ) { // in caso di generazione ordine vado sull'apposito script php per la generazione ma non lo invio tramite email
						window.location.href = 'duplicate_broacq.php?id_tes='+id_tes+'&dest='+dest;
					} else { // il preventivo lo invio solamente
            $('#frame_email').attr('src','stampa_prefor.php?id_tes='+id_tes+"&dest="+dest);
            $('#frame_email').css({'height': '100%'});
            $('.frame_email').css({'display': 'block','width': '40%', 'margin-left': '25%', 'z-index':'2000'});
            $('#close_email').on( "click", function() {
            $('#frame_email').attr('src','');
            $('.frame_email').css({'display': 'none'});
            });
            $(this).dialog("close");
						//window.location.href = 'stampa_prefor.php?id_tes='+id_tes+'&dest='+dest;
					}
				}
			}
		},
		close: function(){
				$("#mailbutt div").remove();
				$(this).dialog('destroy');
		}
	});
	});
}

function choicePartner(row)
{
	$( "#search_partner"+row ).autocomplete({
		source: "../../modules/root/search.php?opt=supplier",
		minLength: 2,
    html: true,
    open: function(event, ui) {
      $(".ui-autocomplete").css("z-index", 1000);
    },
		select: function(event, ui) {
			$(".supplier_name").replaceWith(ui.item.value);
			$("#confirm_duplicate").dialog({
        width: "auto",
        show: "blind",
				hide: "explode",
				buttons: {
					Annulla: function() {
						$(this).dialog('destroy');
					},
					preventivo: {
            text:'su Preventivo',
            'class':'btn btn-info',
            click:function() {
              window.location.href = 'duplicate_broacq.php?id_tes='+row+'&duplicate='+ui.item.codice+'&tipdoc=APR';
            }
					},
					ordine: {
            text:'su Ordine',
            'class':'btn btn-success',
            click:function() {
              window.location.href = 'duplicate_broacq.php?id_tes='+row+'&duplicate='+ui.item.codice+'&tipdoc=AOR';
            }
					}
				},
				close: function(){}
			});
		}
	});
}

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
						data: {'type':'broacq',id_tes:id},
						type: 'POST',
						url: '../acquis/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_broacq.php?flt_tipo=<?php echo $flt_tipo; ?> ");
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
  <div class="frame_email panel panel-success" style="display: none; position: fixed; left: 5%; top: 15%; margin-left: 30%;">
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
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>ordine/preventivo:</b></p>
        <p>Codice:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Fornitore</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
  <div align="center" class="FacetFormHeaderFont"> <?php echo $script_transl['title_dist'][$flt_tipo]; ?>
    <input type="hidden" name="flt_tipo" value="<?php echo $flt_tipo; ?>" />
    <select name="sezione" class="FacetSelect" onchange="this.form.submit()">
      <?php
      for ($sez = 1; $sez <= 9; $sez++) {
          $selected = "";
          if (substr($sezione, 0, 1) == $sez)
              $selected = " selected ";
          echo "<option value=\"" . $sez . "\"" . $selected . ">" . $sez . "</option>";
      }
      ?>
    </select>
  </div>
	<?php
    $ts->output_navbar();
	?>
  <div class="box-primary table-responsive">
    <table class="Tlarge table table-striped table-bordered table-condensed">
      <tr>
        <td class="FacetFieldCaptionTD">
                <?php gaz_flt_disp_int("numdoc", "Numero"); ?>
        </td>
        <td class="FacetFieldCaptionTD">
                <?php gaz_flt_disp_int("id_orderman", "Produzione"); ?>
        </td>
        <td  class="FacetFieldCaptionTD">
            <?php  gaz_flt_disp_select("anno", "YEAR(datemi) as anno", $tesbro_e_partners, $ts->where, "anno DESC"); ?>
        </td>
        <td  class="FacetFieldCaptionTD">
      <?php
        if ($partner_select) {
            gaz_flt_disp_select("fornitore", "clfoco AS fornitore, ragso1 as nome", $tesbro_e_partners, $ts->where, "nome ASC", "nome");
        } else {
            gaz_flt_disp_int("fornitore", "Fornitore");
        }
      ?>
        </td>
        <td  class="FacetFieldCaptionTD">
          <input type="submit" class="btn btn-sm btn-default" name="search" value="<?php echo $script_transl['search'];?>" onClick="javascript:document.report.all.value=1;">
          <a class="btn btn-sm btn-default" href="?flt_tipo=<?php echo $flt_tipo ?>">Reset</a>
          <?php  $ts->output_order_form(); ?>
        </td>
      </tr>
      <tr>
        <?php
        $ts->output_headers();
        ?>
      </tr>
      <?php
      $rs_ultimo_documento = gaz_dbi_dyn_query("*", $tesbro_e_partners, $ts->where, "datemi desc, numdoc desc", 0, 1);
      $ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
      if ($ultimo_documento)
        $ultimoddt = $ultimo_documento['numdoc'];
      else
        $ultimoddt = 1;
			$anagrafica = new Anagrafica();
			//recupero le testate in base alle scelte impostate
      $result = gaz_dbi_dyn_query(cols_from($gTables['tesbro'],
        "id_tes","tipdoc","clfoco","seziva","datemi","email","id_parent_doc","initra","numdoc","status") . ", " .
      cols_from($gTables['anagra'],
        "pec_email",
        "ragso1",
        "ragso2",
        "e_mail")
      ,$tesbro_e_partners,
      $ts->where,
      $ts->orderby,
      $ts->getOffset(),
      $ts->getLimit());
      while ($r = gaz_dbi_fetch_array($result)) {
				$linkstatus=false;
				if ($r["tipdoc"] == 'APR') { // preventivo
					$rs_parent = gaz_dbi_get_row($gTables["tesbro"],'id_parent_doc',$r['id_tes']);
					$clastatus='info';
					$status='Ordina';
					if (strlen($r['email'])<8){
						$clastatus='warning';
						$status='da inviare';
					}
					if ($rs_parent && $rs_parent["tipdoc"] == 'APR') { // il genitore è pure un preventivo
					} elseif ($rs_parent && $rs_parent["tipdoc"] == 'AOR') { // è stato generato un ordine
						$clastatus='success';
						$status='Ordinato con n.'.$rs_parent["numdoc"];
						$linkstatus='stampa_ordfor.php?id_tes='.$rs_parent["id_tes"];
					}
          $tipodoc="Preventivo";
          $modulo="stampa_prefor.php?id_tes=".$r['id_tes'];
          $modifi="admin_broacq.php?id_tes=".$r['id_tes']."&Update";
        } elseif ($r["tipdoc"] == 'AOR') {
					$linkstatus='stampa_ordfor.php?id_tes='.$r['id_tes'];
					$rs_parent = gaz_dbi_get_row($gTables["tesbro"],'id_tes',$r['id_parent_doc']);
					if (strlen($r['email'])>8){
						$clastatus='success';
						$status='Inviato';
					} else {
						$clastatus='warning';
						$status='Inserito';
					}
					if ($rs_parent && $rs_parent["tipdoc"] == 'APR') { // il genitore è un preventivo
						$status .= '( da prev.n.'.$rs_parent["numdoc"].')';
					}
          $tipodoc="Ordine";
          $modulo="stampa_ordfor.php?id_tes=".$r['id_tes'];
          $modifi="admin_broacq.php?id_tes=".$r['id_tes']."&Update";
        }
        echo '<tr class="FacetDataTD text-center">';
// colonna numero documento
				echo "<td align=\"center\"><a class=\"btn btn-xs btn-edit\" id=\"tipdoc_".$r['id_tes']."\"  value=\"".$r["tipdoc"]."\" href=\"".$modifi."\"><i class=\"glyphicon glyphicon-edit\"></i> ".$tipodoc." n.".$r["numdoc"]." &nbsp;</a></td>\n";
// colonna produzione
				$orderman_descr='';
        $rigbro_result = gaz_dbi_dyn_query('*', $gTables['rigbro']." LEFT JOIN ".$gTables['orderman']." ON ".$gTables['rigbro'].".id_orderman = ".$gTables['orderman'].".id", "id_tes = " . $r["id_tes"] , 'id_tes DESC');
				// INIZIO crezione tabella per la visualizzazione sul tootip di tutto il documento
        $tt = '<p class=\'bg-info text-primary\'><b>' . $tipodoc." n.".$r["numdoc"].' del '. gaz_format_date($r["datemi"]).'</b></p>';
        while ( $rigbro_r = gaz_dbi_fetch_array($rigbro_result) ) {
					if ($rigbro_r['id_orderman']>0){
						$orderman_descr=$rigbro_r['id_orderman'].'-'.$rigbro_r['description'];
					}
					$tt .= '<p class=\'text-right\'>' . $rigbro_r['codart'] . '  ' . htmlspecialchars( $rigbro_r['descri'] ) . '  ' . $rigbro_r['unimis'] . '  ' . floatval($rigbro_r['quanti']) . '</p>';
				}
				// FINE creazione tabella per il tooltip dei righi
        echo '<td>'.$orderman_descr." &nbsp;</td>\n";
// colonna data documento
				echo "<td>".gaz_format_date($r["datemi"])." &nbsp;</td>\n";
// colonna fornitore
				echo '<td><div class="gazie-tooltip" data-type="movcon-thumb" data-id="' . $r["id_tes"] . '" data-title="' . $tt . '" >'."<a id=\"fornitore_".$r['id_tes']."\"  value=\"".$r["ragso1"]."\" href=\"report_fornit.php?nome=" . htmlspecialchars($r["ragso1"]) . "\">".$r["ragso1"]."&nbsp;</a></div></td>";
// colonna bottone cambia stato
				echo '<td><a class="btn btn-xs btn-'.$clastatus.'"';
				if ($clastatus=='warning'){ // Ordine non confermato
					echo ' onclick="confirmemail(\''.$r["clfoco"].'\',\''.$r['id_tes'].'\',true);" title="Invia mail di conferma"';
				}elseif($clastatus=='info'){ // Preventivo: chiedo generazione ordine
					echo ' onclick="confirmemail(\''.$r["clfoco"].'\',\''.$r['id_tes'].'\',true);" title="Genera un ordine da questo preventivo"';
				}else{ // Ordine confermato o preventivo che ha già generato ordine, visualizzo il pdf
					echo ' href="'.$linkstatus.'" title="Visualizza PDF"';
				}
                echo '>'.$status.'</a>';
				if ($r['tipdoc']=='AOR'){
					echo '<br><a class="btn btn-xs btn-default" title="Data consegna">';
					echo '<small> cons: '.gaz_format_date($r["initra"]).'</small></a>';
				}
				echo '</td>';
// colonna stampa
                $targetPrintDoc = ($pdf_to_modal==0)?'href="'.$modulo.'" target="_blank" ':"onclick=\"printPdf('".$modulo."')\"";
				echo "<td align=\"center\">";
				echo "<a class=\"btn btn-xs btn-default\" style=\"cursor:pointer;\" ".$targetPrintDoc."><i class=\"glyphicon glyphicon-print\" title=\"Stampa documento PDF\"></i></a>";
				if($r["tipdoc"] == 'AOR') {
					echo "<a class=\"btn btn-xs btn-default\" style=\"cursor:pointer;\" onclick=\"printPdf('stampa_ordfor.php?id_tes=".$r['id_tes']."&production')\"><i class=\"glyphicon glyphicon-fire\" title=\"Stampa per reparto produzioni PDF\"></i></a>";
				}
				echo "</td>";
// colonna operazioni/stato
				echo '<td class="text-center">';
				if ($r["tipdoc"] == 'APR'){
					echo '<button title="Stesso preventivo per altro fornitore" class="btn btn-default btn-xs" type="button" data-toggle="collapse" data-target="#duplicate_'.$r['id_tes'].'" aria-expanded="false" aria-controls="duplicate_'.$r['id_tes'].'"><i class="glyphicon glyphicon-tags">Duplica</i></button>&nbsp;';
          echo '<div class="collapse" id="duplicate_'.$r['id_tes'].'">Fornitore: <input id="search_partner'.$r['id_tes'].'" onClick="choicePartner(\''.$r['id_tes'].'\');"  value="" rigo="'. $r['id_tes'] .'" type="text" /></div>';
				}
				$st=$gForm->getOrderStatus($r['id_tes']);
				if ($r["tipdoc"] == 'AOR') {
					echo '<div><button title="Duplica questo ordine come altro ordine o preventivo" class="btn btn-default btn-xs" type="button" data-toggle="collapse" data-target="#duplicate_'.$r['id_tes'].'" aria-expanded="false" aria-controls="duplicate_'.$r['id_tes'].'"><i class="glyphicon glyphicon-tags"> Duplica</i></button></div>';
          echo '<div class="collapse" id="duplicate_'.$r['id_tes'].'">Fornitore: <input id="search_partner'.$r['id_tes'].'" onClick="choicePartner(\''.$r['id_tes'].'\');"  value="" rigo="'. $r['id_tes'] .'" type="text" /></div>';
          echo '<div>';
          if ($st[0]===0){ // tutto da ricevere
            echo '<a title="Il fornitore consegna la merce ordinata" class="btn btn-xs btn-danger" href="order_delivered.php?id_tes=' . $r['id_tes'] . '"><i class="glyphicon glyphicon-save-file">Ricevi</i></a>';
          }elseif ($st[0]==1){ //  da ricevere in parte
            foreach($st[2]as$kd=>$vd){
              echo '<div><a title="Modifica il documento di acconto" class="btn btn-xs btn-default" href="admin_docacq.php?id_tes=' . $kd . '&Update"><i class="glyphicon glyphicon-edit"> Doc.ID:'.$kd.'</i></a><div>';
            }
            echo '<a title="Il fornitore consegna il saldo della merce" class="btn btn-xs btn-warning" href="order_delivered.php?id_tes=' . $r['id_tes'] . '"><i class="glyphicon glyphicon-save-file pull-right">Salda</i></a>';
          }elseif($st[0] != false){ // completamente ricevuto
            foreach($st[2]as$kd=>$vd){
              echo '<div><a title="Modifica il documento di acconto" class="btn btn-xs btn-default" href="admin_docacq.php?id_tes=' . $kd . '&Update"><i class="glyphicon glyphicon-edit"> Doc.ID:'.$kd.'</i></a></div>';
            }
            echo '<a title="Il fornitore ha consegnato tutta la merce ordinata" disabled class="btn btn-xs btn-success" href=""><i class="glyphicon glyphicon-save-file">Saldato</i></a>';
          } else {
//            echo '<a title="Ordine senza righi normali, es: solo decrittivi" disabled class="btn btn-xs btn-default pull-right" href=""><i class="glyphicon glyphicon-save-file">Descrittivo</i></a>';
          }
          echo '</div>';
				}
        echo "</td>\n";
				// colonna mail
				echo '<td align="center">';
        if (!empty($r["e_mail"])) {
          $gaz_custom_field = gaz_dbi_get_single_value( $gTables['tesbro'], 'custom_field', 'id_tes = '.$r['id_tes'] );
          if (isset($gaz_custom_field) && $gaz_custom_data = json_decode($gaz_custom_field,true)){
            if ( !isset($gaz_custom_data['email']['ord'])) {
              $classe_mail = "btn-default";
              $title= "Mai inviata. Inviala a ".$r["e_mail"];
            } else {
              $classe_mail = "btn-success";
              $title="Ultimo invio: ".$gaz_custom_data['email']['ord'];
            }
          }else{
            $classe_mail = "btn-default";
            $title= "Mai inviata. Inviala a ".$r["e_mail"];
          }
          echo ' <a class="btn btn-xs btn-default btn-email ',$classe_mail,'" title="',$title,'" onclick="confirmemail(\''.$r["clfoco"].'\',\''.$r['id_tes'].'\',false);" id="doc'.$r["id_tes"].'"><i class="glyphicon glyphicon-envelope"></i></a>';
        } else {
					echo '<a title="Non hai memorizzato l\'email per questo fornitore, inseriscila ora" target="_blank" href="admin_fornit.php?codice='.substr($r["clfoco"],3).'&Update"><i class="glyphicon glyphicon-edit"></i></a>';
				}
        echo "	</td>\n";
				// colonna elimina
				echo "<td align=\"center\">";
				?>
				<a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $r['id_tes'];?>" catdes="<?php echo $r['ragso1']; ?>">
					<i class="glyphicon glyphicon-trash"></i>
				</a>
				<?php
				echo "</td></tr>";
      }
            ?>
            <tr><th class="FacetFieldCaptionTD" colspan="12"></th></tr>
        </table>
    </div>
</form>
<div class="modal" id="confirm_email" title="Invia mail...">
    <fieldset>
        <div>
            <label id="maillabel" for="mailaddress">all'indirizzo:</label>
            <input type="text"  placeholder="seleziona sotto oppure digita" value="" id="mailaddress" name="mailaddress" maxlength="100" />
        </div>
        <div id="mailbutt">
		</div>
    </fieldset>
</div>
<div class="modal" id="confirm_duplicate" title="Duplica documento">
    <fieldset>
        <div>
            <label for="duplicate">a:</label>
            <div class="supplier_name"></div>
        </div>
    </fieldset>
</div>

<script src="../../js/custom/fix_select.js" type="text/javascript"></script>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>
