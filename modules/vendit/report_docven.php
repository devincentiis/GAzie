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
$tesdoc_e_partners = $gTables['tesdoc'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesdoc'] . ".clfoco = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id LEFT JOIN ' . $gTables['fae_flux'] . " ON " . $gTables['tesdoc'] . ".id_tes = " . $gTables['fae_flux'] . '.id_tes_ref';

// funzione di utilità generale, adatta a mysqli.inc.php
function cols_from($table_name, ...$col_names) {
    $full_names = array_map(function ($col_name) use ($table_name) { return "$table_name.$col_name"; }, $col_names);
    return implode(", ", $full_names);
}

// campi ammissibili per la ricerca
$search_fields = [
    'sezione'
    => "seziva = %d",
    'protoc'
    => "protoc = %d",
    'tipo'
    => "tipdoc LIKE '%s'",
    'numero'
    => "numfat LIKE '%%%s%%'",
    'anno'
    => "YEAR(datfat) = %d",
    'cliente'
    => $partner_select ? "clfoco = %s" : "ragso1 LIKE '%%%s%%'"
];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array(
    "Prot." => "protoc",
    "Numero" => "numfat",
    "Data" => "datfat",
    "Cliente" => "",
    "Info" => "",
    "Stampa" => "",
    "FAE" => "",
    "Mail" => "",
    "Origine" => "",
    "Cancella" => ""
);

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('custom/modal_form'));
$title_doc="Documenti di vendita della sezione";
if (!isset($_GET['sezione'])) {
	// ultima fattura emessa
	$rs_last = gaz_dbi_dyn_query('seziva, YEAR(datemi) AS yearde', $gTables['tesdoc'], "tipdoc LIKE 'F%'", 'datemi DESC, id_tes DESC', 0, 1);
	$last = gaz_dbi_fetch_array($rs_last);
	if ($last) {
		$default_where=['sezione' => $last['seziva'], 'tipo' => 'F%', 'anno'=>$last['yearde']];
    $_GET['anno']=$last['yearde'];
    $_GET['sezione']= $last['seziva'];
	} else {
		$default_where=['sezione' => 1, 'tipo' => 'F%', 'anno'=> date('Y')];
    $_GET['sezione']= 1;
	}
} else {
	if (intval($admin_aziend['reverse_charge_sez'])<>intval($_GET['sezione'])){
		$default_where=['sezione' => intval($_GET['sezione']), 'tipo' => 'F%'];
	}else{
		$default_where=['sezione' => intval($_GET['sezione']), 'tipo' => 'X%'];
		$title_doc="Autofatture Reverse charge della sezione";
	}
}
$ts = new TableSorter(
    !$partner_select && isset($_GET["cliente"]) ? $tesdoc_e_partners : $gTables['tesdoc'],
    $passo,
    ['datfat' => 'desc', 'protoc' => 'desc'],
    $default_where,
    ['protoc', 'datfat']
);

# le <select> spaziano solo tra i documenti di vendita del sezionale corrente
$where_select = sprintf("tipdoc LIKE 'F%%' AND seziva = %d", $sezione);

echo '<script>
$(function() {
   $( "#dialog" ).dialog({
      autoOpen: false
   });

   $( "#dialog_fae" ).dialog({
      autoOpen: false
   });

   $( "#dialog2" ).dialog({
      autoOpen: false
   });
   $( "#dialog3" ).dialog({
      autoOpen: false
   });

});

function confirPecSdi(link){
   codice = link.id.replace("doc3_", "");
   $.fx.speeds._default = 500;
   targetUrl = $("#doc3_"+codice).attr("url");
   $("p#mailpecsdi").html($("#doc3_"+codice).attr("mail"));
   $("p#mail_attc").html($("#doc3_"+codice).attr("namedoc"));
   $( "#dialog3" ).dialog({
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
   $("#dialog3" ).dialog( "open" );
}


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
    case "##":
    case "PA":
      $("#dialog_fae_content_PA").addClass("bg-default");
      $("#dialog_fae_content_PA span").html("<div class=\'text-center col-xs-9\'><input type=\'file\'  accept=\'.xml,.p7m\' id=\'file\' name=\'file\' title=\' Carica il  file firmato digitalmente\' ></div><div class=\'btn btn-xs btn-warning col-xs-3\' value=\'Upload\' ref=\'"+tes_id+"\' onclick=\'but_upload_signed("+tes_id+");return false;\'>Carica file firmato</div>");
      $("#dialog_fae_content_PA").show();
    break;
    case "PI":
    case "DI":
      $("#dialog_fae_content_DI").addClass("bg-default");
      $("#dialog_fae_content_DI span").html("<p class=\'text-center\'><a href=\'"+link.href+"&invia"+sdiflux+"\' class=\'btn btn-default\'><b><i class=\'glyphicon glyphicon-send\'></i> Invia solo " + $("#doc1_"+tes_id).attr("dialog_fae_filename")+ "</i> </b></a></p><p><a href=\'"+zipref+"\' class=\'btn btn-warning\'><b><i class=\'glyphicon glyphicon-compressed\'> </i> Impacchetta con eventuali altri precedenti</b></a></p>");
      $("#dialog_fae_content_DI").show();
    break;
    case "ZI":
      $("#dialog_fae_content_ZI").addClass("bg-default");
      $("#dialog_fae_content_ZI span").html("<p class=\'text-center\'><a href=\'"+link.href+"&invia"+sdiflux+"\' class=\'btn btn-warning\'><b><i class=\'glyphicon glyphicon-send\'></i> Invia il pacchetto " + $("#doc1_"+tes_id).attr("dialog_fae_filename")+ "</i> </b></a></p><p></p>");
      $("#dialog_fae_content_ZI").show();
    break;
    case "RC":
      $("#dialog_fae_content_RC").addClass("bg-success text-center");
      $("#dialog_fae_content_RC").show();
    break;
    case "MC":
      $("#dialog_fae_content_MC").addClass("bg-warning text-center");
      $("#dialog_fae_content_MC").show();
    break;
    case "NS":
    case "NEEC02":
      $("#dialog_fae_content_NS span").html("<p class=\'text-center bg-danger\'>" + flux_descri.replace(/<[^>]*>?/gm, "") + "</p><p class=\'text-center\'> re: <a href=\'"+link.href+"&id_tes="+tes_id+"&reinvia=reinvia"+sdiflux+"\' class=\'btn btn-danger\'><b> " + $("#doc1_"+tes_id).attr("dialog_fae_reinvio")+ "</b> <br/>" + numrei.toString() + "° reinvio </a></p>");
      $("#dialog_fae_content_NS").show();
    break;
    case "RE":
      $("#dialog_fae_content_RE").addClass("bg-info text-center");
      $("#dialog_fae_content_RE span").html("<p><a href=\'"+link.href+"&reinvia\' class=\'btn btn-danger\'>" + $("#doc1_"+tes_id).attr("dialog_fae_reinvio")+ " <br/>" + numrei.toString() + "° reinvio </a></p><p>Oppure <a href=\'"+zipref+"&packet\' class=\'btn btn-warning\'><b><i class=\'glyphicon glyphicon-compressed\'> </i></b> Impacchetta con eventuali altri precedenti</a></p>");
      $("#dialog_fae_content_RE").show();
    break;
    case "RZ":
      $("#dialog_fae_content_RE").addClass("bg-info text-center");
      $("#dialog_fae_content_RE span").html("<p><a href=\'"+link.href+"&reinvia\' class=\'btn btn-danger\'>" + $("#doc1_"+tes_id).attr("dialog_fae_reinvio")+ " <br/>" + numrei.toString() + "° reinvio </a></p>");
      $("#dialog_fae_content_RE").show();
    break;
    default:
      console.log("errore: stato "+flux_status+" non identificato");
  }
	$("#dialog_fae").dialog({
	  title: dialog_fae_title,
    modal: "true",
    show: "blind",
    width: "600px",
    hide: "explode",
    buttons: {" X ": function() {
      $(".dialog_fae_content").hide();
      $(this).dialog("close");
      }
    }
  });
	$("#dialog_fae").dialog( "open" );
}

</script>';
?>
<script>
function confirMail(link,cod_partner,id_tes,genorder=false) {
  tes_id = link.id.replace("doc_", "");
  $.fx.speeds._default = 500;
  targetUrl = $("#doc_"+tes_id).attr("url");

	var namedoc=$("#doc_"+tes_id).attr("namedoc");
	$("#confirm_email").attr('title', 'Invia '+namedoc);

	$.get("search_email_address.php",
		  {clfoco: cod_partner},
		  function (data) {
        var size = Object.keys(data).length;
        var j=0;
        var c=0
        const mails = [];
        $.each(data, function (i, value) {

          if (size>1 && !mails.includes(value.email)){
            if (j==0){
              $("#mailbutt").append("<div>Indirizzi archiviati:</div>");
            }
            $("#mailbutt").append("<div id='rowmail_"+j+"' align='center'><button id='fillmail_" + j+"'>" + value.email + "</button></div>");
            $("#fillmail_" + j).click(function () {
              $("#mailaddress").val(value.email);
            });
            c=j+1;
            if (c < size){// non faccio rimuovere l'email del fornitore (che è sempre l'ultima) anche perché non la toglierebbe
              $("#rowmail_"+j).append(" <button id='deletemail_" + j+"' class='btn-elimina' title='rimuovi indirizzo'> <i class='glyphicon glyphicon-trash'></i> </button>");
              $("#deletemail_" + j).click(function () { // se clicco sulla X elimino da tesdoc l'email che non si vuole più utilizzare
                // richiamo il delete.php per eliminare la email dalle tesdoc
                $.ajax({
                  data: {'type':'email',ref:value.email,'tes_id':tes_id},
                  type: 'POST',
                  url: '../vendit/delete.php',
                  success: function(output){
                    window.location.replace("./report_docven.php");
                  }
                });
              });
            }
            mails[j]=value.email;
            j++;
          }else{// se non ci sono indirizzi da scegliere valorizzo di default
            $("#mailaddress").val(value.email);
          }

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
    minWidth: 200,
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
					$("#mailaddress").val('');
          $('#frame_email').attr('src',targetUrl+"&dest="+dest);
          $('#frame_email').css({'height': '100%'});
          $('.frame_email').css({'display': 'block','width': '40%', 'margin-left': '25%', 'z-index':'2000'});
          $('#close_email').on( "click", function() {
          $('#frame_email').attr('src','');
          $('.frame_email').css({'display': 'none'});
          location.reload(true);
          });
          $(this).dialog("close");
				}
			}
		},
		close: function(){
			$("#mailbutt div").remove();
			$("#mailaddress").val('');
			$(this).dialog('destroy');
		}
	});
	});
}



$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("ragso1"));
		var id = $(this).attr('ref');
		var id2 = $(this).attr('seziva');
		var id3 = $(this).attr('anno');
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
						data: {'type':'docven',ref:id,seziva:id2,anno:id3},
						type: 'POST',
						url: '../vendit/delete.php',
						success: function(output){
		          //alert(output);
							window.location.replace("./report_docven.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
function but_upload_signed(id_tes){
  var fd = new FormData();
  var files = $('#file')[0].files[0];
  var fn = $("#doc1_"+id_tes).attr("dialog_fae_filename");
  fd.append('file', files);
  fd.append('opt', 'upload_signed');
  fd.append('term', id_tes.toString());
  fd.append('fn', fn);
  $.ajax({
    url: 'ajax_request.php',
    type: 'post',
    data: fd,
    cache: false,
    contentType: false,
    processData: false,
    success: function(response){
    console.log(response);
      if(response != 0){
        alert('File ' + response + ' caricato con successo ');
        window.location.replace("./report_docven.php");
      } else {
        alert('Errore: File non caricato');
      }
    },
  });
}
function printPdf(urlPrintDoc){
	$(function(){
		$('#framePdf').attr('src',urlPrintDoc);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
		$('#closePdf').on( "click", function() {
			$('.framePdf').css({'display': 'none'});
		});
	});
};
</script>
<form method="GET" class="clean_get">
  <div class="frame_email panel panel-success" style="display: none; position: fixed; left: 5%; top: 15%; margin-left: 30%; height: 40%">
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
	<?php
	if (isset($_SESSION['print_request']) && intval($_SESSION['print_request'])>0){
		?>
		<script> printPdf('stampa_docven.php?id_tes=<?php echo $_SESSION['print_request'].$_SESSION['template']; ?>'); </script>
		<?php
		$_SESSION['print_request']="";
		$_SESSION['template']="";
	}
	?>
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>documento di vendita:</b></p>
        <p>Protocollo:</p>
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
    <div class="modal" id="confirm_email" title="Invia mail...">
      <fieldset>
          <div>
              <label id="maillabel" for="mailaddress">all'indirizzo:</label>
              <input type="text"  placeholder="seleziona sotto oppure digita" value="" id="mailaddress" name="mailaddress" maxlength="100" size="30" />
          </div>
          <div id="mailbutt">
      </div>
      </fieldset>
    </div>

    <div style="display:none" id="dialog_fae">
        <div style="display:none;" id="dialog_fae_title" title="<?php echo $script_transl['dialog_fae_title']; ?>"></div>
        <p class="ui-state-highlight" id="dialog_fae_filename"><?php echo $script_transl['dialog_fae_filename']; ?><span></span></p>
        <?php
        $statuskeys=array('PA','DI','RE','PI','IN','RC','MC','NS','ZI');
        foreach ( $statuskeys as $v ) {
            echo '<p style="display:none;" class="dialog_fae_content" id="dialog_fae_content_'.$v.'">'.$script_transl['dialog_fae_content_'.$v]."<span></span></p>";
        }
        ?>
    </div>

    <div style="display:none" id="dialog2" title="<?php echo $script_transl['report_alert0']; ?>">
        <p id="report_alert1"><?php echo $script_transl['report_alert1']; ?></p>
        <p class="ui-state-highlight" id="report1"></p>
    </div>

    <div style="display:none" id="dialog3" title="<?php echo $script_transl['faesdi_alert0']; ?>">
        <p id="faesdi_alert1"><?php echo $script_transl['faesdi_alert1']; ?></p>
        <p class="ui-state-highlight" id="mailpecsdi"></p>
    </div>

    <div align="center" class="FacetFormHeaderFont"><?php echo $title_doc; ?>
        <select name="sezione" class="FacetSelect" onchange="this.form.submit()">
	    <?php
            for ($i = 1; $i <= 9; $i++) {
                $selected = ($sezione == $i) ? "selected" : "";
                echo "<option value='$i' $selected > $i </option>\n";
            }
	    ?>

        </select>
    </div>

    <div align="center">
	<?php
        $ts->output_navbar();
	?>
    </div>

    <div class="table-responsive">
        <table class="Tlarge table table-bordered table-condensed table-striped">
          <tr>
            <td class="FacetFieldCaptionTD">
              <?php gaz_flt_disp_int("protoc", "Numero Prot."); ?>
            </td>
            <td class="FacetFieldCaptionTD">
              <?php gaz_flt_disp_int("numero", "Numero Fatt."); ?>
            </td>
            <td class="FacetFieldCaptionTD">
              <?php gaz_flt_disp_select("anno", "YEAR(datfat) as anno", $gTables["tesdoc"], $where_select, "anno DESC"); ?>
            </td>
            <td class="FacetFieldCaptionTD">
              <?php
              if ($partner_select) {
                gaz_flt_disp_select("cliente", "clfoco AS cliente, ragso1 as nome", $tesdoc_e_partners, $where_select.((isset($_GET['anno']) && intval($_GET['anno']) >= 2000)?' AND YEAR(datemi)='.intval($_GET['anno']):''), "nome ASC", "nome");
              } else {
                gaz_flt_disp_int("cliente", "Cliente");
              }
              ?>
            </td>
            <td class="FacetFieldCaptionTD" style="white-space:unset;">
              <?php
              // visualizzo il filtro per la colonna informazioni sul documento
                  $flt_info = "none";
                  if ( isset($_GET['info']) && $_GET['info']!="" ) {
                      $flt_info = $_GET['info'];
                  }
              ?>
              <select class="form-control input-sm" name="info" onchange="this.form.submit()">
                <option value="none" <?php if ($flt_info=="none" || $flt_info=="none") echo "selected";?>>Tutti</option>
                <option value="info" <?php if ($flt_info=="info" ) echo "selected";?>>Aperta</option>
                <option value="danger" <?php if ($flt_info=="danger" ) echo "selected";?>>Aperta e scaduta</option>
                <option value="warning" <?php if ($flt_info=="warning" ) echo "selected";?>>Esposta</option>
                <option value="success" <?php if ($flt_info=="success" ) echo "selected";?>>Chiusa</option>
                <option value="default" <?php if ($flt_info=="default" ) echo "selected";?>>da Contab.</option>
              </select>
            </td>
            <td class="FacetFieldCaptionTD">
              <input type="submit" class="btn btn-sm btn-default btn-50" name="search" value="Cerca" tabindex="1">
              <?php $ts->output_order_form(); ?>
            </td>
            <td class="FacetFieldCaptionTD">
              <a class="btn btn-sm btn-default btn-50" href="?">Reset</a>
            </td>
            <td class="FacetFieldCaptionTD"> &nbsp; </td>
            <td class="FacetFieldCaptionTD"> &nbsp; </td>
            <td class="FacetFieldCaptionTD"> &nbsp; </td>
          </tr>
          <tr>
            <?php
            $ts->output_headers();
            ?>
          </tr>
          <?php
          $rs_ultimo_documento = gaz_dbi_dyn_query("id_tes,tipdoc,protoc", $gTables['tesdoc'], "tipdoc LIKE 'F%' AND seziva = '$sezione'", "datfat DESC, protoc DESC, id_tes DESC", 0, 1);
          $ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
          // controllo se ho configurato un servizio di gestione flussi verso SdI
          $sdi_flux = gaz_dbi_get_row($gTables['company_config'], 'var', 'send_fae_zip_package')['val'];
          //recupero le testate in base alle scelte impostate
          $result = gaz_dbi_dyn_query(cols_from($gTables['tesdoc'], "id_tes","id_con","ddt_type","clfoco","pagame","fattura_elettronica_zip_package","tipdoc","seziva","protoc","datfat","fattura_elettronica_reinvii","geneff","numfat","id_contract","fattura_elettronica_original_name") . ", " .
          cols_from($gTables['anagra'],
					  "fe_cod_univoco",
					  "pec_email",
					  "ragso1",
					  "ragso2",
					  "e_mail") . ", " .
          cols_from($gTables['fae_flux'],
					  "flux_status, received_date, flux_descri") . ", " .
          "MAX(id_tes) AS reftes, " .
          "GROUP_CONCAT(id_tes ORDER BY datemi DESC) AS refs_id, " .
          "GROUP_CONCAT(flux_status ORDER BY received_date DESC, exec_date DESC) AS refs_flux_status, " .
          "GROUP_CONCAT(numdoc ORDER BY datemi DESC) AS refs_num",
          $tesdoc_e_partners,
          $ts->where,
          $ts->orderby,
          $ts->getOffset(),
          $flt_info != "none" ? 1000 : $ts->getLimit(),
          $ts->group_by);
          $ctrl_doc = "";
          $ctrl_eff = 999999;
          $last_fae_packet = '';
          $paymov = new Schedule();
          while ($r = gaz_dbi_fetch_array($result)) {
            // se contabilizzato trovo l'eventuale stato dei pagamenti e se qualcosa non è andato a buon fine riporto la contabilizzazione nello stato ancora da eseguire
            $paymov_status = false;
            if ($r['id_con'] > 0) {
              $tesmov = gaz_dbi_get_row($gTables['tesmov'], 'id_tes', $r['id_con']);
              // controllo effettiva presenza movimento completo
              if ($tesmov) {
                  $paymov->getStatus(substr($tesmov['datdoc'],0,4).$tesmov['regiva'].$tesmov['seziva']. str_pad($tesmov['protoc'], 9, 0, STR_PAD_LEFT)); // passo il valore formattato di id_tesdoc_ref
                  $paymov_status = $paymov->Status;
              } else {
                  gaz_dbi_query("UPDATE ".$gTables['tesdoc']." SET id_con = 0 WHERE id_tes = ".$r['id_tes']);
                  $r['id_con']=0;
              }
            }
            // riprendo il rigo  della contabilità con il cliente per avere l'importo
            $importo = gaz_dbi_get_row($gTables['rigmoc'], 'id_tes', $r['id_con'], "AND codcon = ".$r['clfoco']);
            $pagame = gaz_dbi_get_row($gTables['pagame'], 'codice', $r['pagame']);
            $modulo_fae = "electronic_invoice.php?id_tes=" . $r['id_tes'];
            $modulo_fae_report = "report_fae_sdi.php?id_tes=" . $r['id_tes'];
            $zipped = (preg_match("/^[A-Z0-9]{13,18}_([a-zA-Z0-9]{5}).zip$/",$r['fattura_elettronica_zip_package']?$r['fattura_elettronica_zip_package']:'',$match))?$match[1]:false;
            $classe_btn = "btn-default";
            if ($r["tipdoc"] == 'FAI'||$r["tipdoc"] == 'FAA') {
                $tipodoc = "Fattura Immediata";
                $modulo = "stampa_docven.php?id_tes=" . $r['id_tes'];
                $modifi = "admin_docven.php?Update&id_tes=" . $r['id_tes'];
                $classe_btn = "btn-edit";
            } elseif($r["tipdoc"] == 'FAF'){
                $tipodoc = "Autofattura (TD26)";
                $modulo = "stampa_docven.php?id_tes=" . $r['id_tes'];
                $modifi = "admin_docven.php?Update&id_tes=" . $r['id_tes'];
                $classe_btn = "btn-edit";
            } elseif ($r["tipdoc"] == 'FAD') {
                $tipodoc = "Fattura Differita";
                $classe_btn = "btn-edit";
                $modulo = "stampa_docven.php?td=2&si=" . $r["seziva"] . "&pi=" . $r['protoc'] . "&pf=" . $r['protoc'] . "&di=" . $r['datfat'] . "&df=" . $r['datfat'];
                $modulo_fae = "electronic_invoice.php?seziva=" . $r["seziva"] . "&protoc=" . $r['protoc'] . "&year=" . substr($r['datfat'], 0, 4);
                if ( !$modifica_fatture_ddt ) {
                    $modifi = "";
                } else {
                    $classe_btn = "btn-default";
                    $modifi = "admin_docven.php?Update&id_tes=" . $r["reftes"];
                }
            } elseif ($r["tipdoc"] == 'FAP'||$r["tipdoc"] == 'FAQ') {
                $tipodoc = "Parcella";
                $classe_btn = "btn-primary";
                $modulo = "stampa_docven.php?id_tes=" . $r['id_tes'];
                $modifi = "admin_docven.php?Update&id_tes=" . $r['id_tes'];
            } elseif ($r["tipdoc"] == 'FNC') {
                $tipodoc = "Nota Credito";
                $classe_btn = "btn-danger";
                $modulo = "stampa_docven.php?id_tes=" . $r['id_tes'];
                $modifi = "admin_docven.php?Update&id_tes=" . $r['id_tes'];
            } elseif ($r["tipdoc"] == 'FND') {
                $tipodoc = "Nota Debito";
                $classe_btn = "btn-edit";
                $modulo = "stampa_docven.php?id_tes=" . $r['id_tes'];
                $modifi = "admin_docven.php?Update&id_tes=" . $r['id_tes'];
            } else {
                $tipodoc = "DOC.SCONOSCIUTO";
                $classe_btn = "btn-warning";
                $modulo = "stampa_docven.php?id_tes=" . $r['id_tes'];
                $modifi = "admin_docven.php?Update&id_tes=" . $r['id_tes'];
            }
            if (sprintf('%09d', $r['protoc']) . $r['datfat'] <> $ctrl_doc) {
              $n_e = 0;
              // trovo il nome dei file xml delle fatture elettroniche, sia quello attuale sia quello frutto di un eventuale reinvii
              $r['fae_attuale']="IT" . $admin_aziend['codfis'] . "_".encodeSendingNumber(array('azienda' => $admin_aziend['codice'],
							  'anno' => $r["datfat"],
							  'sezione' => $r["seziva"],
							  'fae_reinvii'=> $r["fattura_elettronica_reinvii"],
							  'protocollo' => $r["protoc"]), 36).".xml";
              $r['fae_reinvio']="IT" . $admin_aziend['codfis'] . "_".encodeSendingNumber(array('azienda' => $admin_aziend['codice'],
							  'anno' => $r["datfat"],
							  'sezione' => $r["seziva"],
                'fae_reinvii'=> intval($r["fattura_elettronica_reinvii"]+1),
                'protocollo' => $r["protoc"]), 36).".xml";
              // Calcolo i valori prima di visualizzare la colonna info per poter far funzionare il filtro
              $idcon_maggiore_0 = "";
              $visualizza_effetto_ft = "";
              $genera_effetti_previsti = "";
              if ($r["id_con"] > 0) {
                $idcon_maggiore_0 = " <a class=\"btn btn-xs btn-".$paymov_status['style']."\" style=\"font-size:10px;\" title=\"Modifica il movimento contabile " . $r["id_con"] . " generato da questo documento\" href=\"../contab/admin_movcon.php?id_tes=" . $r["id_con"] . "&Update\"> <i class=\"glyphicon glyphicon-euro\"></i> " . $importo["import"] . "</a> ";
               	if (intval($admin_aziend['reverse_charge_sez'])==intval($_GET['sezione'])){
                  // non usando le transazioni devo aggiunger un controllo di effettiva esistenza della testata di movimento contabile, se qualcosa non è andato per il verso giusto elimini il riferimento
                  $existtesmov = gaz_dbi_get_row($gTables['tesmov'], 'id_tes', $r['id_con']);
                  $revch = gaz_dbi_get_row($gTables['tesdoc'] . " LEFT JOIN " . $gTables['fae_flux'] . " ON " . $gTables['tesdoc'] . ".id_tes=" . $gTables['fae_flux'] . ".id_tes_ref", $gTables['tesdoc'] . ".datfat", $r['datfat'], "AND " . $gTables['tesdoc'] . ".numfat = '".$r['numfat']."' AND " . $gTables['tesdoc'] . ".clfoco = ".$r['clfoco']." AND " . $gTables['tesdoc'] . ".tipdoc LIKE 'X__'", $gTables['tesdoc'] . ".*,  GROUP_CONCAT( " . $gTables['fae_flux'] . ".flux_descri ORDER BY " . $gTables['fae_flux'] . ".received_date DESC) AS flux_descri, GROUP_CONCAT(" . $gTables['fae_flux'] . ".flux_status ORDER BY " . $gTables['fae_flux'] . ".received_date DESC) AS refs_flux_status"); // controllo l'esistenza di una fattura reverse charge per XML
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
                        $idcon_maggiore_0 .= '<a class="btn btn-xs btn-success" title="Pacchetto di fatture elettroniche in cui &egrave; contenuta questa fattura" href="../vendit/download_zip_package.php?fn='.$revch['fattura_elettronica_zip_package'].'">'.$zipped.'.zip<i class="glyphicon glyphicon-compressed"></i> </a>';
                      }
                    }
                    if ($sdi_flux && $sdi_flux <> 'filezip') { // ho un modulo per la gestione dei flussi con il SdI: posso visualizzare lo stato
                      $zip_ref = 'fae_packaging.php?sdiflux='.$sdi_flux;
                      if ($revch['refs_flux_status']==null) {
                        $last_flux_status = '';
                      } else {
                        $last_flux_status = explode(',',$revch['refs_flux_status'])[0];
                      }
                      $sdihilight = ( !empty($revch['refs_flux_status']) ) ? $script_transl['flux_status_val'][$last_flux_status][1] : 'default';
                      $sdilabel = ( !empty($revch['refs_flux_status']) ) ? $script_transl['flux_status_val'][$last_flux_status][0] : 'da inviare';
                      $last_flux_status = (empty($last_flux_status)) ? 'DI' : '';
                      if (is_string($revch['fattura_elettronica_zip_package']) && strlen($revch['fattura_elettronica_zip_package'])>10 && $last_flux_status == 'DI') { // il documento è impacchettato e da inviare
                        //$revch['fae_attuale'] = $revch['fattura_elettronica_zip_package'];
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
                      case "PI":
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
                    $idcon_maggiore_0 .= '<a class="btn btn-xs btn-'.$sdihilight.' btn-xml" onclick="confirFae(this);return false;" id="doc1_'.$revch['id_tes'].'" dialog_fae_reinvio="'.$revch['fae_reinvio'].'" dialog_flux_descri="'.(is_string($revch['flux_descri'])?htmlentities($revch['flux_descri']):'').'" dialog_fae_sdiflux="'.$sdi_flux.'" dialog_fae_filename="'.$revch['fae_attuale'].'" dialog_fae_numrei="'.$revch['fattura_elettronica_reinvii'].'" dialog_fae_numfat="'. $revch['tipdoc'].' '. $revch['numfat'].'/'. $revch['seziva'].'" dialog_flux_status="'. $last_flux_status.'" target="_blank" href="'.$modulo_fae.'" zip_ref="'.$zip_ref.'" title="'.$sdititle.'"> '.strtoupper($sdilabel).' </a><a class="btn btn-xs btn-default" title="Visualizza in stile" href="../vendit/electronic_invoice.php?id_tes='.$revch['id_tes'].'&viewxml" target="_blank"><i class="glyphicon glyphicon-eye-open"></i> </a>';
                    if ($revch['fattura_elettronica_reinvii'] > 0) {
                      $idcon_maggiore_0 .= '<br/><small>' . $revch['fattura_elettronica_reinvii'] . ($revch['fattura_elettronica_reinvii']==1 ? ' reinvio' : ' reinvii') . '</small><br/>';
                    }
                  }
                  if (!$existtesmov){
                    $idcon_maggiore_0 .= "<a class=\"btn btn-xs btn-danger\" href=\"\">Contabilizzazione persa!</a>";
                  }
                }
              } else {
                $idcon_maggiore_0 = " <a class=\"btn btn-xs btn-default btn-cont\" style=\"font-size:10px;\" href=\"accounting_documents.php?type=F&vat_section=" . $sezione . "&last=" . $r["protoc"] . "\"><i class=\"glyphicon glyphicon-euro\"></i>&nbsp;CONTABILIZZA</a>";
              }
              $effett_result = gaz_dbi_dyn_query('*', $gTables['effett'], "id_doc = " . $r["reftes"], 'progre');
              while ($r_e = gaz_dbi_fetch_array($effett_result)) {
                // La fattura ha almeno un effetto emesso
                $n_e++;
                $map_eff = ['B' => ["la ricevuta bancaria generata", "RiBa", "riba"],
                            'I' => ["il RID generato", "RID", "rid"],
                            'T' => ["la cambiale tratta generata", "Tratta", "cambiale"],
                            'V' => ["il pagamento mediante avviso generato", "MAV", "avviso"]];
                list($eff_desc, $eff, $eff_class) = isset($map_eff[$r_e["tipeff"]]) ? $map_eff[$r_e["tipeff"]] : ["l'effetto generato", $r_e["tipeff"], "effetto"];
                $visualizza_effetto_ft .= " <a class='btn btn-xs btn-default btn-$eff_class' style='font-size:10px;' title='Visualizza $eff_desc per il regolamento della fattura' href='stampa_effett.php?id_tes={$r_e['id_tes']}'> $eff {$r_e['progre']} </a>\n";
              }
              if ($n_e == 0 && $r["geneff"]<>'S' && intval($admin_aziend['reverse_charge_sez'])<>intval($_GET['sezione']) ) {
                if ($pagame["tippag"] == 'B' || $pagame["tippag"] == 'I' || $pagame["tippag"] == 'T' || $pagame["tippag"] == 'V') {
                  $genera_effetti_previsti = " <a class=\"btn btn-xs btn-effetti\" title=\"Genera gli effetti previsti per il regolamento delle fatture\" href=\"genera_effett.php\"> Genera effetti</a>";
                }
              }
              // visualizzo la riga solo se rispetta il filtro informazioni
              if ( is_bool($paymov_status) || $paymov_status['style'] == $flt_info || $flt_info == "none" || ( $paymov_status['style'] == "" && $flt_info=="default") ) {
                echo "<tr class=\"FacetDataTD\">";

                // carico le impostazioni aggiuntive dal campo custom
                $gaz_custom_data = "";
                $gaz_custom_field = gaz_dbi_get_single_value( $gTables['tesdoc'], 'custom_field', 'id_tes = '.$r['id_tes'] );
                if ( isset( $gaz_custom_field ) && $gaz_custom_field!="" ) {
                    $gaz_custom_data = json_decode($gaz_custom_field,true);
                }

                // Colonna protocollo
                if (!empty($modifi)) {
                  echo "<td class=\"text-center\"><a href=\"" . $modifi . "\" class=\"btn btn-xs " . $classe_btn . "\" title=\"Modifica " . $tipodoc . " \">" . $r["protoc"] . "&nbsp;" . $r["tipdoc"] . "&nbsp;<i class=\"glyphicon glyphicon-edit\"></i></a></td>";
                } else {
                  echo "<td class=\"text-center\"><button class=\"btn btn-xs " . $classe_btn . " disabled\" title=\"Per poter modificare questa " . $tipodoc . " devi modificare i DdT in essa contenuti!\">" . $r["protoc"] . "&nbsp;" . $r["tipdoc"] . " &nbsp;<i class=\"glyphicon glyphicon-edit\"></i></button></td>";
                }
                // Colonna numero documento
                echo "<td align=\"center\">" . $r["numfat"] . " &nbsp;</td>";
                // Colonna data documento
                echo "<td align=\"center\">" . gaz_format_date($r["datfat"]) . " &nbsp;</td>";
                // Colonna cliente
                echo "<td><a title=\"Dettagli cliente\" href=\"report_client.php?nome=" . htmlspecialchars($r["ragso1"]) . "\">" . $r["ragso1"] . ((empty($r["ragso2"]))?"":" ".$r["ragso2"]) . "</a>";
                if (strlen(trim($r['fe_cod_univoco']))==6){
                  echo '<a class="btn btn-xs btn-info" title="Codice Univoco Ufficio della Pubblica Amministrazione" href="admin_client.php?codice='.intval(substr($r["clfoco"],-6,6)).'&Update">[pa]@ '.$r['fe_cod_univoco'].' </a>';
                }
                echo "</td>";
                // Colonna movimenti contabili
                echo "<td>";
                $res_consenti_nofisc = gaz_dbi_dyn_query("codfis,pariva","{$gTables['clfoco']} LEFT JOIN {$gTables['anagra']} ON {$gTables['clfoco']}.id_anagra = {$gTables['anagra']}.id","codice=".$r['clfoco'] );
                $r_consenti_nofisc = gaz_dbi_fetch_array($res_consenti_nofisc);
                if ( $r_consenti_nofisc['pariva']!="" || $r_consenti_nofisc['codfis']!="" ) {
                  echo $idcon_maggiore_0;
                  echo $visualizza_effetto_ft;
                  echo $genera_effetti_previsti;
                } else {
                  echo "<a href=\"admin_client.php?codice=".substr($r['clfoco'],3,6)."&Update\" target=\"_blank\" class=\"btn btn-100 btn-xs btn-danger\" title=\"Per poter contabilizzare questa " . $tipodoc . " devi modificare i dati del cliente!\">".$script_transl['consentivisua']."</a>";
                }
                echo "</td>";
                // Colonna "Stampa"
                $targetPrintDoc = ($pdf_to_modal==0)?'href="'.$modulo.'" target="_blank" ':"onclick=\"printPdf('".$modulo."')\"";
                echo "<td align=\"center\"><a class=\"btn btn-xs btn-default\" style=\"cursor:pointer;\" ".$targetPrintDoc." \"><i class=\"glyphicon glyphicon-print\" title=\"Stampa documento PDF\"></i></a>";
                echo "</td>";
                // Colonna "Fattura elettronica"
                if (substr($r['tipdoc'], 0, 1) == 'F') {
                  if($r['fattura_elettronica_original_name'] !== null && strlen($r['fattura_elettronica_original_name'])>10){ // ho un file importato dall'esterno
                    echo '<td><a class="btn btn-xs btn-warning" target="_blank" href="../acquis/view_fae.php?id_tes=' . $r["id_tes"] . '">File importato<i class="glyphicon glyphicon-eye-open"></i></a>'.'<a class="btn btn-xs btn-edit" title="Scarica il file XML originale" href="download_zip_package.php?fn='.$r['fattura_elettronica_original_name'].'">xml <i class="glyphicon glyphicon-download"></i> </a></td>';
                  } else { // il file è generato al volo dal database
                    echo '<td align="center"';
                    if($zipped){ // se è contenuto in un pacchetto di file permetterà sia il download del singolo XML che del pacchetto in cui è contenuto
                      echo ' style="white-space:unset;">';
                      if ($r['fattura_elettronica_reinvii']==0) {
                        echo '<a class="btn btn-xs btn-success" title="Pacchetto di fatture elettroniche in cui &egrave; contenuta questa fattura" href="download_zip_package.php?fn='.$r['fattura_elettronica_zip_package'].'">'.$zipped.'.zip<i class="glyphicon glyphicon-compressed"></i> </a>';
                      }
                    } elseif (strlen($r['pec_email'])<5 && strlen(trim($r['fe_cod_univoco']))<6) { //se il cliente non ha codice univoco o pec tolgo il link e do la possibilità di richiederli via mail o carta
                      $d_title = 'Invia richiesta PEC e/o codice SdI all\'indirizzo: '.$r['e_mail'];
                      $dest = '&dest=E';
                      if (strlen($r['e_mail'])<5) {
                        $dest = '';
                        $d_title = 'Stampa richiesta cartacea (cliente senza mail)';
                      }
                      echo '><button onclick="confirPecSdi(this);return false;" id="doc3_' . $r['clfoco'] . '" url="stampa_richiesta_pecsdi.php?codice='.$r['clfoco'].$dest.'" href="#" title="'. $d_title . '" mail="' . $r['e_mail'] . '" namedoc="Richiesta codice SdI o indirizzo PEC"  class="btn btn-xs  btn-elimina"><i class="glyphicon glyphicon-tag"></i></button>';
                    } else { // quando ho pec e/o codice univoco ma non ho creato pacchetti zip
                      echo ">\n";
                    }
                    if ( $sdi_flux  && $sdi_flux <> 'filezip' ) { // ho un modulo per la gestione dei flussi con il SdI: posso visualizzare lo stato
                      $zip_ref = 'fae_packaging.php?sdiflux='.$sdi_flux;
                      if (!empty($r['refs_flux_status'])){
                        $last_flux_status = explode(',',$r['refs_flux_status'])[0];
                      } else{
                        $last_flux_status='';
                      }
                      $sdihilight = ( !empty($r['refs_flux_status']) ) ? $script_transl['flux_status_val'][$last_flux_status][1] : 'default';
                      $sdilabel = ( !empty($r['refs_flux_status']) ) ? $script_transl['flux_status_val'][$last_flux_status][0] : 'da inviare';
                      if ( $last_flux_status == '' ) {
                        if ( strlen(trim($r['fe_cod_univoco']))==6 ) {
                          $sdilabel = 'da firmare';
                          $last_flux_status = 'PA';
                        } else {
                          $last_flux_status = 'DI';
                        }
                      }
                      if ( !empty($r['fattura_elettronica_zip_package']) && strlen($r['fattura_elettronica_zip_package'])>10 && ($last_flux_status=='DI' || $last_flux_status=='PI')) { // il documento è impacchettato e da inviare
                        //$r['fae_attuale']=$r['fattura_elettronica_original_name'];
                        $sdihilight = ( !empty($r['refs_flux_status']) ) ? $script_transl['flux_status_val'][$last_flux_status][1] : 'default';
                        $sdilabel = ( !empty($r['refs_flux_status']) ) ? $script_transl['flux_status_val'][$last_flux_status][0] : (($r['fattura_elettronica_zip_package']!='FAE_ZIP_NOGENERATED') ? 'ZIP da inviare' : '');
                        $last_flux_status = 'ZI';
                      }
                    } else { //// installazione senza gestore dei flussi con il SdI
                      $last_flux_status =($zipped)?'RZ':'RE'; // gestendo il flusso manualmente darò sempre la possibilità di scegliere se reinviare o scaricare l'xml
                      $zip_ref = 'fae_packaging.php?nolib';
                      $sdihilight = 'default';
                      $sdilabel = 'xml';
                    }
                    switch ($last_flux_status) {
                      case "##":
                      case "PA":
                      $sdititle = 'Scarica il file '.$r['fae_attuale'].' per firmarlo';
                      $sdihilight = 'warning';
                      break;
                      case "DI":
                      case "PI":
                      $sdititle = 'Invia il file '.$r['fae_attuale'].' o pacchetto';
                      break;
                      case "PC":
                      $sdititle = 'Il file '.$r['fae_attuale'].' è stato inviato al Sistema di Interscambio, attendere l\'esito ';
                      break;
                      case "RE":
                      $sdititle = 'Invia il file '.$r['fae_attuale'].' al Sistema di Interscambio ';
                      break;
                      case "IN":
                      $sdititle = 'Il file '.$r['fae_attuale'].' è stato inviato al Sistema di Interscambio, attendere la risposta di presa in carico ';
                      break;
                      case "RC":
                      $sdititle = 'Il file '.$r['fae_attuale'].' è stato inviato e consegnato al cliente ';
                      break;
                      case "MC":
                      $sdititle = 'Il file '.$r['fae_attuale'].' è stato inviato ma non consegnato al cliente che potrà recuperarlo dal suo cassetto fiscale ';
                      break;
                      case "DT":
                      $sdititle = 'Il file '.$r['fae_attuale'].' è stato accettato dalla PA cliente per decorrenza termini ';
                      break;
                      case "NEEC01":
                      $sdititle = 'Il file '.$r['fae_attuale'].' è stato accettato dalla PA cliente ';
                      break;
                      case "NEEC02":
                      $sdititle = 'Il file '.$r['fae_attuale'].' è stato rifiutato dalla PA cliente, fai verifiche prima di fare il reinviio ';
                      break;
                      case "NS":
                      $sdititle = 'Il file '.$r['fae_attuale'].' è stato Scartato, correggi prima di fare il reinviio ';
                      break;
                      default:
                      $sdititle = 'genera il file '.$r['fae_attuale'].' o fai il '.intval($r['fattura_elettronica_reinvii']+1).'° reinvio ';
                      break;
                    }
                    echo (empty($sdilabel)?'':'<a class="btn btn-xs btn-'.$sdihilight.' btn-xml" onclick="confirFae(this);return false;" id="doc1_'.$r['id_tes'].'" dialog_fae_reinvio="'.$r['fae_reinvio'].'" dialog_flux_descri="'.htmlentities($r['flux_descri']?$r['flux_descri']:'').'" dialog_fae_sdiflux="'.$sdi_flux.'" dialog_fae_filename="'.$r['fae_attuale'].'" dialog_fae_numrei="'.$r['fattura_elettronica_reinvii'].'" dialog_fae_numfat="'. $r['tipdoc'].' '. $r['numfat'].'/'. $r['seziva'].'" dialog_flux_status="'. $last_flux_status.'" target="_blank" href="'.$modulo_fae.'" zip_ref="'.$zip_ref.'" title="'.$sdititle.'"> '.strtoupper($sdilabel).' </a>').'<a class="btn btn-xs btn-default" title="Visualizza in stile" href="electronic_invoice.php?id_tes='.$r['id_tes'].'&viewxml" target="_blank"><i class="glyphicon glyphicon-eye-open"></i> </a>';
                    if ($r['fattura_elettronica_reinvii'] > 0) {
                      echo '<br/><small>' . $r['fattura_elettronica_reinvii'] . ($r['fattura_elettronica_reinvii']==1 ? ' reinvio' : ' reinvii') . '</small><br/>';
                    }
                    echo '</td>';
                  }
                } else {
                  echo '<td></td>';
                }
                // Colonna "Mail"
                echo "<td align=\"center\">";
                if (!empty($r["e_mail"])) {
                  if ( !isset($gaz_custom_data['email']['fat'])) {
                      $classe_mail = "btn-default";
                      $title= "Mai inviata. Inviala a ".$r["e_mail"];
                  } else {
                      $classe_mail = "btn-success";
                      $title="Ultimo invio: ".$gaz_custom_data['email']['fat'];
                  }
                  echo '<a class="btn btn-xs '.$classe_mail.' btn-email" onclick="confirMail(this,' . $r["clfoco"] . ',' . $r["id_tes"] . ',false);return false;" id="doc_' . $r["id_tes"] . '" url="' . $modulo . '" href="#" title="' . $title . '" mail="' . $r["e_mail"] . '" namedoc="' . $tipodoc . ' n.' . $r["numfat"] . ' del ' . gaz_format_date($r["datfat"]) . '"><i class="glyphicon glyphicon-envelope"></i></a>';
                } else {
                  if ($r["tipdoc"] == 'XFA'){// se è reverse charge questo è un fornitore
                    echo '<a title="Non hai memorizzato l\'email per questo fornitore, inseriscila ora" href="../acquis/admin_fornit.php?codice=' . substr($r['clfoco'], 3) . '&Update#email"><i class="glyphicon glyphicon-edit"></i></a>';
                  }else{
                    echo '<a title="Non hai memorizzato l\'email per questo cliente, inseriscila ora" href="admin_client.php?codice=' . substr($r['clfoco'], 3) . '&Update#email"><i class="glyphicon glyphicon-edit"></i></a>';
                  }
                }
                echo "</td>";
                // Colonna "Origine"
                if ($r["tipdoc"] == 'FAD') {
                  $docs = array_combine(explode(",", $r['refs_id']),explode(",", $r['refs_num']));
                  echo '<td align="center">';
                  list($doc_templ, $doc) = ($r['ddt_type'] == 'R') ? ['doccmr', 'CMR'] : ['doctra', 'DdT'];
                  $desc = $doc;
                  if (count($docs) > 5) {
                    echo "<a href='report_$doc_templ.php' style='font-size:10px;' class='btn btn-xs btn-default'><i class='glyphicon glyphicon-plane'></i>$doc</a>";
                    $desc = "";
                  }
                  foreach ($docs as $doc_id => $doc_num) {
                    echo " <a class='btn btn-xs btn-default btn-ddt' title='Visualizza il $doc' href='stampa_docven.php?id_tes=$doc_id&template=" . strtoupper($doc) . "' style='font-size:9px;'> $desc $doc_num </a>\n";
                  }
                  echo "</td>";
                } elseif ($r["id_contract"] > 0) {
                  $con_result = gaz_dbi_dyn_query('*', $gTables['contract'], "id_contract = " . $r["id_contract"], 'conclusion_date DESC');
                  echo "<td align=\"center\">";
                  while ($r_d = gaz_dbi_fetch_array($con_result)) {
                    echo " <a class=\"btn btn-xs btn-default btn-contr\" title=\"Visualizza il contratto\" href=\"print_contract.php?id_contract=" . $r_d['id_contract'] . "\" style=\"font-size:10px;\"><i class=\"glyphicon glyphicon-list-alt\"></i>&nbsp;Contr." . $r_d['doc_number'] . "/" . substr($r_d['conclusion_date'], 0, 4) . "</a>\n";
                  }
                  echo "</td>";
                } else {
                  echo "<td>";
                  $rigdoc_result = gaz_dbi_dyn_query('DISTINCT id_order', $gTables['rigdoc'], "id_tes = " . $r["id_tes"] ." AND id_order > 0", 'id_tes');
                  while ( $rigdoc = gaz_dbi_fetch_array($rigdoc_result) ) {
                    if($rigdoc['id_order']>0){
                      $tesbro_result = gaz_dbi_dyn_query('*', $gTables['tesbro'], "id_tes = " . $rigdoc['id_order'], 'id_tes');
                      $t_r = gaz_dbi_fetch_array($tesbro_result);
                      $tipo_doc_evaso = $t_r['tipdoc'];
                      switch($tipo_doc_evaso) {
                        case "VPR":
                          echo " <a class=\"btn btn-xs btn-default\" title=\"" . $script_transl['view_ord'] . "\" href=\"stampa_precli.php?id_tes=" . $rigdoc['id_order'] . "\" style=\"font-size:10px;\"><i class=\"glyphicon glyphicon-check\"></i>&nbsp;Prev." . $t_r['numdoc'] . "</a>\n";
                        break;
                        case "VOR":
                          echo " <a class=\"btn btn-xs btn-default\" title=\"" . $script_transl['view_ord'] . "\" href=\"stampa_ordcli.php?id_tes=" . $rigdoc['id_order'] . "\" style=\"font-size:10px;\"><i class=\"glyphicon glyphicon-check\"></i>&nbsp;Ord." . $t_r['numdoc'] . "</a>\n";
                        break;
                      }

                    }
                  }
                  echo "</td>";
                }
                // Colonna "Cancella"
                echo "<td align=\"center\">";
                if ($ultimo_documento && ($ultimo_documento['id_tes'] == $r["id_tes"] || ($ultimo_documento['tipdoc'] == 'FAD' && $ultimo_documento['protoc'] == $r['protoc']))) {
                  // Permette di cancellare il documento.
                  if ($r["id_con"] > 0) {
                  ?>
                    <a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella il documento e la registrazione contabile relativa" ref="<?php echo $r['protoc'];?>" ragso1="<?php echo $r['ragso1']; ?>" seziva="<?php echo $r['seziva']; ?>" anno="<?php echo substr($r["datfat"], 0, 4); ?>">
                      <i class="glyphicon glyphicon-trash"></i>
                    </a>
                  <?php
                  } else {
                  ?>
                    <a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella il documento" ref="<?php echo $r['protoc'];?>" ragso1="<?php echo $r['ragso1']; ?>" seziva="<?php echo $r['seziva']; ?>" anno="<?php echo substr($r["datfat"], 0, 4); ?>">
                      <i class="glyphicon glyphicon-trash"></i>
                    </a>
                  <?php
                  }
                } else {
                  echo "<button title=\"Per garantire la sequenza corretta della numerazione, non &egrave; possibile cancellare un documento diverso dall'ultimo\" class=\"btn btn-xs   disabled\"><i class=\"glyphicon glyphicon-trash\"></i></button>";
                }
                echo "</td>";
                echo "</tr>\n";
              }
            }
            $ctrl_doc = sprintf('%09d', $r['protoc']) . $r['datfat'];
          }
          ?>
        </table>
    </div>
</form>

<script src="../../js/custom/fix_select.js" type="text/javascript"></script>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<script>
$(document).ready(function(){
     var _sezi = $("select[name='sezione'] option:selected").text().trim();
     $.each(['FAI','FNC','FND','FAP'], function( i, v ) {
         var _href = $("a[href*='admin_docven.php?Insert&tipdoc=" + v + "']").attr('href');
         $("a[href*='admin_docven.php?Insert&tipdoc=" + v + "']").attr('href', _href + '&seziva=' + _sezi);
     });
});
</script>

<?php
require("../../library/include/footer.php");
?>
