<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-present - Antonio Germani, Massignano (AP)
  (http://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------

  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
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
include_once("manual_settings.php");
require("../../library/include/datlib.inc.php");
require("../../modules/magazz/lib.function.php");
$admin_aziend=checkAdmin();
require("../../library/include/header.php");
$ivac = gaz_dbi_get_row($gTables['company_config'], 'var', 'vacation_ivac')['val'];
if ($ivac=="si"){
  $ivac="IVA compresa";
}else {
  $ivac="imponibili";
}
$firstpart_ical_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off') ? 'https://'.$_SERVER['SERVER_NAME'] : 'http://'.$_SERVER['SERVER_NAME'];
// campi ammissibili per la ricerca
$search_fields = [
    'sea_codice' => "{$gTables['artico']}.codice LIKE '%%%s%%'",
	'des_artico' => "{$gTables['artico']}.descri LIKE '%%%s%%'",

    'asset' => "id_assets = %d",
    'codcat' => "{$gTables['catmer']}.codice = %d",
];
if ($admin_aziend['lang']=="italian"){
	$lang="it";
}else{
	$lang="en";
}

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
            "Codice" => 'codice',
            "Descrizione"=>'descri',
            "Tipo di alloggio" => 'accommodation_type',
            "Categoria" => 'catmer',
			"Icalendar url"=>'',
      'Prezzo base' => '',
            'prezzi' => '',
			'Disponibilità' => '',
            'Duplica' => '',
            'Elimina' => ''
);

$tablejoin = $gTables['artico']. " LEFT JOIN " . $gTables['catmer'] . " ON " . $gTables['artico'] . ".catmer = " . $gTables['catmer'] . ".codice LEFT JOIN " . $gTables['artico_group'] ." ON " . $gTables['artico'] . ".id_artico_group = " . $gTables['artico_group'] . ".id_artico_group";

$ts = new TableSorter(
    $tablejoin,
    $passo,
    ['last_modified'=>'desc'],
    ['asset' => 0]);
?>
<script>
$(function() {

  $("#dialog_import").dialog({ autoOpen: false });
	$('.dialog_import').click(function() {
    $("#dialog_import" ).dialog( "open" );
	$("p#idcodice_exp").append($(this).attr("ref"));
    var ref = $(this).attr('ref');
    $.ajax({
      data: {'opt':'get_files', 'term':ref},
      type: 'GET',
      url: '../vacation_rental/ajax_request.php',
      success: function(data){
          try{
          var as=JSON.parse(data);
            //alert(data);
            var size = Object.keys(as).length;
            var j=0;
            if (size>0){
              $.each(as, function (i, value) {
                $("#filebutt").append("<div id='rowmail_"+j+"' align='center'><button id='fillmail_" + j+"'>" + value + "</button></div>");
                $("#fillmail_" + j).click(function () {
                  $("#restorefile").text(value);

                });
                  $("#rowmail_"+j).append(" <button id='deletefile_" + j+"' class='btn-elimina' title='rimuovi file'> <i class='glyphicon glyphicon-trash'></i> </button>");
                  $("#deletefile_" + j).click(function () { // se clicco sulla X elimino il file che non si vuole più utilizzare
                    if (confirm('Sei sicuro di voler cancellare?') == true) {
                      // richiamo il delete per eliminare il file
                      $.ajax({
                        data: {'opt':'del_files','term':value, 'ref':ref},
                        type: 'GET',
                        url: '../vacation_rental/ajax_request.php',
                        success: function(output){
                          alert(output);
                          window.location.replace("./report_accommodation.php");
                        }
                      });
                    }
                  });
                j++;
              });
            }else{// se non ci sono files da scegliere
              $("#restorefile").text('Non ci sono file da importare');
            }
        } catch (error){
           $("#restorefile").text('Non ci sono file da importare');
        }
      }
    });
		$( function() {
      var rest= $("#restorefile").text();

      var dialog,
      dialog = $("#dialog_import").dialog({
        modal: true,
        show: "blind",
        hide: "explode",
        minWidth: 200,
        buttons: {
          Annulla: function() {
            $("#filebutt div").remove();

            $(this).dialog('close');
          },
          Conferma: function() {
              var rest= $("#restorefile").text();
			  var imp_year = $("#import_into").val();
              $.ajax({
                data: {'opt':'restore_files', 'term':rest, 'ref':ref, 'year':imp_year},
                type: 'GET',
                url: '../vacation_rental/ajax_request.php',
                success: function(data){

                  alert(data);
                  $("#filebutt div").remove();
                  $("#dialog_import").dialog("close");
                }

              });
            }
        }
      });
    });
  });

   $("#dialog_export").dialog({ autoOpen: false });
	$('.dialog_export').click(function() {
		$("p#idcodice_exp").append($(this).attr("ref"));
    var ref = $(this).attr('ref');
		$( "#dialog_export" ).dialog({
			minHeight: 1,
			minWidth: 300,
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'Esporta',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
            	var export_year = $("#export_year").val(); // The value of the selected option parent
            	var child_year = $("#child_year").val(); // The value of the selected option child
              var operat = $("#operat").val(); // The value of the selected option child
            	var percent = $("#percent").val(); // The value of the selected option child
              $.ajax({
                data: {'term':export_year,'opt':'export', 'ref':ref},
                type: 'GET',
                url: '../vacation_rental/ajax_request.php',
                success: function(output){
                  alert(output);
                  window.location.replace("./report_accommodation.php");
                }
              });
				}},
				"Annulla": function() {
					$(this).dialog("close");
				}
			}
		});
		$("#dialog_export" ).dialog( "open" );
	});

  $("#dialog_duplicate").dialog({ autoOpen: false });
	$('.dialog_duplicate').click(function() {
		$("p#idcodice").append($(this).attr("ref"));
    var ref = $(this).attr('ref');
		$( "#dialog_duplicate" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'Clona',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
            if (confirm("Are you sure?")){
            	var parent_year = $("#parent_year").val(); // The value of the selected option parent
            	var child_year = $("#child_year").val(); // The value of the selected option child
              var operat = $("#operat").val(); // The value of the selected option child
            	var percent = $("#percent").val(); // The value of the selected option child
              $.ajax({
                data: {'parent_year':parent_year,'child_year':child_year,'term':ref,'opt':'clone', 'operat':operat, 'percent':percent},
                type: 'GET',
                url: '../vacation_rental/ajax_request.php',
                success: function(output){
                  alert(output);
                  window.location.replace("./report_accommodation.php");
                }
              });
            }
				}},
				"Non clonare": function() {
					$(this).dialog("close");
				}
			}
		});
		$("#dialog_duplicate" ).dialog( "open" );
	});

	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("artico"));
		var id = $(this).attr('ref');
		$( "#dialog_delete" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'Elimina',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'artico',ref:id},
						type: 'POST',
						url: '../vacation_rental/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_accommodation.php");
						}
					});
				}},
				"Non eliminare": function() {
					$(this).dialog("close");
				}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});

  $("#dialog_limit").dialog({ autoOpen: false });
	$('.dialog_limit').click(function() {
		$("p#idaccommodation").html($(this).attr("ref"));
		var idacc = $(this).attr('ref');
		$( "#dialog_limit" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'Limita',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
            var start = $('input[name="start"]').val();
            var end = $('input[name="end"]').val();
            var token = '<?php echo md5($token.date('Y-m-d'));?>';
            $.ajax({
              data: {'start':start,'end':end,'token':token,'house_code':idacc,'title':'Prenotazioni bloccate'},
              type: 'GET',
              url: '../vacation_rental/save_to_db_events.php',
              success: function(output){
                //alert(output);
                //alert('Il periodo è stato limitato');
                $(".start").empty();
                $(".end").empty();
                $("#dialog_limit").dialog("close");
                window.location.replace("./report_accommodation.php");
              }
            });
				}},
				"Chiudi senza limitare": function() {
					$(this).dialog("close");
				}
			}
		});
		$("#dialog_limit" ).dialog( "open" );
	});

	$( "#suggest_codice_artico" ).autocomplete({
		source: "../../modules/root/search.php?opt=suggest_codice_artico",
		minLength: 3,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#suggest_codice_artico").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});

});
function getorders(artico) {
	$("#idartico").append("articolo: "+artico);
  $("#dialog_orders").attr("title","Ordini da clienti aperti");
	$.get("ajax_request.php?opt=orders",
		{term: artico},
		function (data) {
			var j=0;
				$.each(data, function(i, value) {
				j++;
				$(".list_orders").append("<tr><td><a>"+value.descri+"</a>&nbsp; </td><td align='right'>&nbsp;  <button> Ordine n."+ value.numdoc +" del "+ value.datemi + " </button></td></tr>");
				$(".list_orders").click(function () {
					window.open('../vendit/admin_broven.php?Update&id_tes='+ value.id_tes);
				});
				});
				if (j==0){
					$(".list_orders").append('<tr><td class="bg-danger">********* Non ci sono ordini *********</td></tr>');
				}
		}, "json"
	);
	$( function() {
    var dialog
	,
	dialog = $("#dialog_orders").dialog({
		modal: true,
		show: "blind",
		hide: "explode",
		width: "auto",
		buttons: {
			Chiudi: function() {
				$(this).dialog('close');
			}
		},
		close: function(){
				$("p#idartico").empty();
				$("div.list_orders tr").remove();
				$(this).dialog('destroy');
		}
	});
	});
};
function getgroup(artico) {
	$("#idgroup").append("Struttura");
    $("#dialog_group").attr("title","Struttura ID "+artico);
	$.get("ajax_request.php?opt=group",
		{term: artico},
		function (data) {
            var j=0;
			$.each(data, function(i, value) {
                j++;
                if (j==1) {
                    $(".list_group").append("<tr><td>"+value.descri+"&nbsp;&nbsp;</td></tr><tr><td>&nbsp;</td></tr>");
                    $("#idvar").append("composta dai seguenti alloggi:");
                    $(".list_variants").append("<tr><td>Codice&nbsp;</td><td>Descrizione</td></tr>");
                } else {
                    $(".list_variants").append("<tr><td> "+(j-1)+") "+value.codice+"&nbsp;</td><td>"+value.descri+"</td></tr>");
                }
			});
			if (j==0){
				$(".list_orders").append('<tr><td class="bg-danger">********* Non ci sono varianti in questo gruppo articoli*********</td></tr>');
			}
		}, "json"
	);
	$( function() {
        var dialog,
        dialog = $("#dialog_group").dialog({
            modal: true,
            show: "blind",
            hide: "explode",
            width: "auto",
            buttons: {
                Modifica:{
                    text:'Modifica la struttura',
					'class':'btn btn-warning',
					click:function (event, ui) {
                        window.open('../vacation_rental/admin_facility.php?Update&id_artico_group='+ artico);
                    }
                },
                Chiudi: function() {
                    $(this).dialog('close');
                }
            },
            close: function(){
				$("p#idgroup").empty();
				$("p#idvar").empty();
				$("div.list_group tr").remove();
				$("div.list_variants tr").remove();
				$(this).dialog('destroy');
            }
        });
	});
};
function getlastbuys(artico) {
	$("#idartico").append("articolo: "+artico);
  $("#dialog_orders").attr("title","Ultimi acquisti da fornitori");
	$.get("ajax_request.php?opt=lastbuys",
		{term: artico},
		function (data) {
			var j=0;
				$.each(data, function(i, value) {
				j++;
				$(".list_orders").append("<tr><td> "+value.supplier+"&nbsp; </td><td> &nbsp;<button>"+ value.desdoc + " </button> &nbsp;</td><td> &nbsp;"+value.desvalue+" </td></tr>");
				$(".list_orders").click(function () {
					window.open('../acquis/admin_docacq.php?Update&id_tes='+ value.docref);
				});
				});
				if (j==0){
					$(".list_orders").append('<tr><td class="bg-danger">********* Non ci sono acquisti *********</td></tr>');
				}
		}, "json"
	);
	$( function() {
    var dialog
	,
	dialog = $("#dialog_orders").dialog({
		modal: true,
		show: "blind",
		hide: "explode",
		width: "auto",
		buttons: {
			Chiudi: function() {
				$(this).dialog('close');
			}
		},
		close: function(){
				$("p#idartico").empty();
				$("div.list_orders tr").remove();
				$(this).dialog('destroy');
		}
	});
	});
};

$('#closePdf').on( "click", function() {
		$('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
	});
function openframe(url,codice){
  var response = jQuery.ajax({
		url: url,
		type: 'HEAD',
		async: false
	}).status;
	if(response == "200") {
    $(function(){
      $("#titolo").append(codice);
      $('#framePdf').attr('src',url);
      $('#framePdf').css({'height': '100%'});
      $('.framePdf').css({'display': 'block','width': '90%', 'height': '100%', 'z-index':'2000'});
      $("html, body").delay(100).animate({scrollTop: $('#framePdf').offset().top},200, function() {
          $("#framePdf").focus();
      });
    });
  }else{
    alert('Il file richiesto fa parte della versione PRO di questo modulo: contattare lo sviluppatore');
  };
	$('#closePdf').on( "click", function() {
		$("#titolo").empty();
		$('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
	});
};
function openframesync(url){
  var response = jQuery.ajax({
		url: url,
		type: 'HEAD',
		async: false
	}).status;
	if(response == "200") {
    $(function(){
      $('#framePdf').attr('src',url);
      $('#framePdf').css({'height': '100%'});
      $('.framePdf').css({'display': 'block','width': '90%', 'height': '100%', 'z-index':'2000'});
      $("html, body").delay(100).animate({scrollTop: $('#framePdf').offset().top},200, function() {
          $("#framePdf").focus();
      });
    });
  }else{
    alert('Il file richiesto fa parte della versione PRO di questo modulo: contattare lo sviluppatore');
  };
	$('#closePdf').on( "click", function() {
		$('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
	});
};


function Copy(id) {
	 /* Get the text field */
  var copyText = document.getElementById("copy"+id);

  /* Select the text field */
  copyText.select();
  copyText.setSelectionRange(0, 99999); /* For mobile devices */

   /* Copy the text inside the text field */
  navigator.clipboard.writeText(copyText.value);

  /* Alert the copied text */
  //alert("Copied the text: " + copyText.value);
}

</script>
<?php
$script_transl = HeadMain(0, array('custom/autocomplete'));
?>
<div class="text-center"><h3><?php echo $script_transl['title'];?></h3></div>
<?php
$ts->output_navbar();

?>
<form method="GET" class="clean_get">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>alloggio:</b></p>
        <p>codice:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>

  <div style="display:none" id="dialog_duplicate" title="Clona i prezzi">
        <p class="ui-state-highlight" id="idcodice"><b>alloggio: </b></p>
        <?php
        echo '<label>Clona i prezzi dell\'anno:</label><br><select id="parent_year" name="parent_year" data-component="date">';
        for ($year = date('Y')+1; $year >= 2020; $year--) {
          $selected=(intval(date('Y'))==$year)?'selected="selected"':'';
          echo '<option value="'.$year.'" '.$selected.'>' . $year . '</option>';
        }
        echo '</select><br>';
        ?><div>
        <input id="operat" class="col-sm-4" type="text"  value="+" name="operat" maxlength="1" size="2" oninput="this.value = this.value.replace(/[^+|-]/g, '');"/>
        <input id="percent" class="col-sm-4" type="text"  value="" name="percent" maxlength="2" size="5" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');"/>%
        </div>
        <?php
        echo '<br><label>nell\'anno:</label><br><select id="child_year" name="child_year" data-component="date" >';
        for ($year = (intval(date('Y'))); $year <= (intval(date('Y'))+10); $year++) {
          $selected=(intval(date('Y')+1)==$year)?'selected="selected"':'';
          echo '<option value="'.$year.'" '.$selected.'>' . $year . '</option>';
        }
        echo '</select>';
        ?>
	</div>
  <div class="modal" id="dialog_import" title="Importa prezzi">
      <fieldset>
          <div>
			<div >Importa nell'anno
               <input pattern=".{4,4}" required id="import_into" type="text" name="import_into" class="FacetInput" maxlength="4" onkeypress="return /[0-9]/i.test(event.key)" value="<?php echo date('Y'); ?>">
			</div>
              <div id="restorefile">File da importare</div>
          </div>
          <div id="filebutt"> </div>
      </fieldset>
    </div>

  <div style="display:none" id="dialog_export" title="Esporta i prezzi SQL formato xml">
        <p class="ui-state-highlight" id="idcodice_exp"><b>alloggio: </b></p>
        <?php
        echo '<label>Esporta i prezzi dell\'anno:</label><br><select id="export_year" name="export_year" data-component="date">';
        for ($year = (intval(date('Y')-5)); $year <= (intval(date('Y'))+2); $year++) {
          $selected=(intval(date('Y'))==$year)?'selected="selected"':'';
          echo '<option value="'.$year.'" '.$selected.'>' . $year . '</option>';
        }
        echo '</select><br>';
        ?>
	</div>

	<div class="framePdf panel panel-success" style="display: none; position: fixed; left: 5%; top: 5px">
			<div class="col-lg-12">
				<div class="col-xs-11" id="titolo" ></div>
				<div class="col-xs-1"><span><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></span></div>
			</div>
			<iframe id="framePdf"  style="height: 100%; width: 100%" src="../../library/images/wait_spinner.html"></iframe>
	</div>
	<div style="display:none; min-width:150px; " id="dialog_orders" title="">
		<p class="ui-state-highlight" id="idartico"></p>
		<div class="list_orders">
		</div>
	</div>
	<div style="display:none; min-width:350px; " id="dialog_group" title="">
		<p class="ui-state-highlight" id="idgroup"></p>
		<div class="list_group">
		</div>
		<p class="ui-state-highlight" id="idvar"></p>
		<div class="list_variants">
		</div>
	</div>
  <div style="display:none; min-width:350px; " id="dialog_limit" title="">
		<p class="ui-state-highlight" id="idaccommodation"></p>
		<div class="list_group">
		</div>
		<p>Blocca prenotazioni in questo intervallo di date</p>
		<div class="start">
    <input type="date" name="start" class="FacetInput">
		</div>
    <div class="end">
    <input type="date" name="end"  class="FacetInput">
		</div>
	</div>
	<div class="table-responsive">
	<table class="Tlarge table table-striped table-bordered table-condensed">
	<tr>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="sea_codice" placeholder="codice" id="suggest_codice_artico" class="input-sm form-control" value="<?php echo (isset($sea_codice))? htmlentities($sea_codice, ENT_QUOTES) : ""; ?>" maxlength="15">
		</td>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="des_artico" placeholder="descrizione"  id="suggest_descri_artico" class="input-sm form-control" value="<?php echo (isset($des_artico))? htmlentities($des_artico, ENT_QUOTES) : ""; ?>" maxlength="30">
        </td>
		<td class="FacetFieldCaptionTD">
        <?php gaz_flt_disp_select("gos", $gTables['artico'].".good_or_service AS gos", $tablejoin, 1,'good_or_service ASC', $script_transl['good_or_service_value']); ?>
        </td>
		<td class="FacetFieldCaptionTD">
        <?php gaz_flt_disp_select("codcat", $gTables['catmer'].".codice AS codcat, ". $gTables['catmer'].".descri AS descat", $tablejoin, 1,'codcat ASC','descat'); ?>
        </td>

		<td class="FacetFieldCaptionTD"></td>
		<td class="FacetFieldCaptionTD" colspan="7">
			<input type="submit" class="btn btn-sm btn-default" name="search" value="<?php echo $script_transl['search'];?>" onClick="javascript:document.report.all.value=1;">
			<a class="btn btn-sm btn-default" href="?">Reset</a>
			<?php  $ts->output_order_form(); ?>

			<a class="btn btn-sm btn-default glyphicon glyphicon-refresh" href="?" style="float:right;" onclick="openframesync('sync_event_ical.php')" data-toggle="modal" data-target="#iframe">Sincronizza ICal</a>
			<?php  $ts->output_order_form(); ?>
		</td>
	</tr>

<?php
$gForm = new magazzForm();

$result = gaz_dbi_dyn_query ( $gTables['artico']. ".*, ".$gTables['artico_group']. ".descri AS desgroup, ".$gTables['catmer']. ".descri AS descat, ".$gTables['catmer']. ".codice AS codcat",$tablejoin, $ts->where." AND good_or_service=1 AND (".$gTables['artico'].".custom_field REGEXP 'accommodation_type')", $ts->orderby, $ts->getOffset(), $ts->getLimit());

echo '<tr>';
$ts->output_headers();
echo '</tr>';
while ($r = gaz_dbi_fetch_array($result)) {
	// escludo dal report se non sono alloggi
	if ($data = json_decode($r['custom_field'], TRUE)){  // se esiste un json nel custom field

		if (is_array($data['vacation_rental'])){// se è un alloggio lo mostro nel report
			$r['accommodation_type'] = $data['vacation_rental']['accommodation_type'];
			// da configurazione azienda
			$show_artico_composit = gaz_dbi_get_row($gTables['company_config'], 'var', 'show_artico_composit');
			$tipo_composti = gaz_dbi_get_row($gTables['company_config'], 'var', 'tipo_composti');
			// acquisti

			$class = 'success';// di default l'alloggio è aperto
      $title = 'Aperto';
			if ($r['ordinabile']=='N') { // se l'alloggio è chiuso
				$class = 'danger';
        $title = 'Chiuso';
			}

			// IVA
			$iva = gaz_dbi_get_row($gTables['aliiva'], 'codice', $r['aliiva']);
			if (!$iva) $iva=array('aliquo'=>0);
			switch ($r['web_public']) {// 1=attivo su web; 2=attivo e prestabilito; 3=attivo e pubblicato in home; 4=attivo, in home e prestabilito; 5=disattivato su web
				case "0":
					$ecomGlobe="";
					break;
				case "1":
					$ecomGlobe="class='glyphicon glyphicon-globe' style='color:rgba(26, 209, 44);' title='Attivato su e-commerce'";
					break;
				case "2":
					$ecomGlobe="class='glyphicon glyphicon-globe' style='color:rgba(255, 203, 71);' title='Attivato e prestabilito su e-commerce'";
					break;
				case "3":
					$ecomGlobe="class='glyphicon glyphicon-globe' style='color:rgba(255, 99, 71);' title='Attivato e in home su e-commerce'";
					break;
				case "4":
					$ecomGlobe="class='glyphicon glyphicon-globe' style='color:red;' title='Attivato, prestabilito e in home su e-commerce'";
					break;
				case "5":
					$ecomGlobe="class='glyphicon glyphicon-globe' title='Disattivato su e-commerce'";
					break;
			}
			echo "<tr>\n";
			echo '<td>
			<a title="'.$title.'" class="btn btn-xs btn-'.$class.'" href="../vacation_rental/admin_house.php?Update&codice='.$r['codice'].'" ><i class="glyphicon glyphicon-edit"></i> '.$r['codice'].' </a>';

			echo "<i ".$ecomGlobe." ></i>";// globo per e-commerce
			echo '</td>';
			echo '<td><span class="gazie-tooltip" data-type="product-thumb" data-id="'. $r['codice'] .'" data-title="'. $r['annota'].'" >'.get_string_lang($r['descri'], $lang).'</span>';
			echo "</td>\n";
			echo '<td class="text-center">';
			switch($r['accommodation_type']){// 3 => 'Appartamento', 4 => 'Casa vacanze', 5=> 'Bed & breakfast'
				case "3":
					echo "Appartamento";
				break;
				case "4":
					echo "Casa vacanze";
				break;
				case "5":
					echo "Bed & breakfast";
				break;
        case "6":
					echo "Camera";
				break;
        case "7":
					echo "Locazione turistica";
				break;
			}

      if ($r['id_artico_group']>0){
        echo '<a class="btn btn-xs btn-default" title="Struttura: '.$r['desgroup'].'"  onclick="getgroup(\''.$r['id_artico_group'].'\');"> <i class="glyphicon glyphicon-level-up"></i> </a> ';
      }

			echo "</td>\n";
			echo '<td class="text-center">'.$r['catmer'].'-'.$r['descat'];
			echo "</td>\n";
			?>
			<td class="text-center">
				<input type="text" value="<?php echo $firstpart_ical_url,"/modules/vacation_rental/ical.php?house_code=",$r['codice']; ?>" id="copy<?php echo $r['codice'];?>" readonly width="100">
				<a class="btn btn-xs btn-default" style="cursor:pointer;" onclick="Copy('<?php echo $r['codice'];?>')">
					<i class="glyphicon glyphicon-copy" title="Copia url Ical">
					</i>
				</a>
			</td>
			<?php
      echo '<td class="text-center">'.$admin_aziend['symbol']," ",gaz_format_quantity($r['web_price'],1,$admin_aziend['decimal_price']);
			echo "</td>\n";
			echo '<td class="text-center"><a class="btn btn-xs btn-default" style="cursor:pointer;" onclick="openframe(\'accommodation_price.php?house_code='.$r["codice"].'\',\'Prezzi '.$ivac.' <b>'.$r["codice"].'</b>\')" data-toggle="modal" data-target="#iframe"> <i class="glyphicon glyphicon-eur" title="Calendario dei prezzi"></i></a>';
      echo '&nbsp; &nbsp; <a class="btn btn-xs btn-default dialog_duplicate" ref="'. $r['codice'].'"> <i class="glyphicon glyphicon-duplicate" title="Duplica prezzi"></i></a>';
			echo '&nbsp; &nbsp; <a class="btn btn-xs btn-default dialog_export" ref="'. $r['codice'].'"> <i class="glyphicon glyphicon-export" title="Esporta prezzi sql.xml"></i></a>';
      echo '&nbsp; &nbsp; <a class="btn btn-xs btn-default dialog_import" ref="'. $r['codice'].'"> <i class="glyphicon glyphicon-import" title="Importa prezzi sql.xml"></i></a>';
      echo "</td>\n";
			echo '<td class="text-center"><a class="btn btn-xs btn-default" style="cursor:pointer;" onclick="openframe(\'accommodation_availability.php?house_code='.$r["codice"].'\',\'Calendario <b>'.$r["codice"].'</b>\')" data-toggle="modal" data-target="#iframe"> <i class="glyphicon glyphicon-calendar" title="Calendario della disponibilità"></i></a>';
			echo '&nbsp; &nbsp; <a class="btn btn-xs btn-default dialog_limit" ref="'. $r['codice'].'"> <i class="glyphicon glyphicon-tasks" title="Limita lungo periodo""></i></a>';
      echo "</td>\n";
			echo '<td class="text-center"><a class="btn btn-xs btn-default" href="clone_house.php?codice='.$r["codice"].'"> <i class="glyphicon glyphicon-export"></i></a>';
			echo "</td>\n";
			echo '<td class="text-center"><a class="btn btn-xs  btn-elimina dialog_delete" ref="'. $r['codice'].'" artico="'. $r['descri'].'"> <i class="glyphicon glyphicon-trash"></i></a>';
			echo "</td>\n";
			echo "</tr>\n";
		}
	}
}
?>
     </table>
	</div>
</form>
<a href="https://programmisitiweb.lacasettabio.it/gazie/vacation-rental-il-gestionale-per-case-vacanza-residence-bb-e-agriturismi/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:10%; z-index:2000;"> Vacation rental è un modulo di Antonio Germani</a>
<script src="../../js/custom/fix_select.js" type="text/javascript"></script>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>
