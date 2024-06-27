<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (https://www.programmisitiweb.lacasettabio.it)

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
require("../../modules/magazz/lib.function.php");
$admin_aziend=checkAdmin();
require("../../library/include/header.php");
$firstpart_ical_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off') ? 'https://'.$_SERVER['SERVER_NAME'] : 'https://'.$_SERVER['SERVER_NAME'];
// campi ammissibili per la ricerca
$search_fields = [
    'sea_codice' => "{$gTables['artico']}.codice LIKE '%%%s%%'",
	'des_artico' => "{$gTables['artico']}.descri LIKE '%%%s%%'",
  'des_facility' => "{$gTables['rental_extra']}.rif_facility LIKE '%%%s%%'",
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
            'Prezzo' => '',
            "Modalità prezzo" => 'accommodation_type',
			"Alloggio di appartenenza" => '',
      "Struttura di appartenenza" => '',
            "Categoria" => 'catmer',
			"Icalendar url"=>'',
			'Disponibilità' => '',
            'Duplica' => '',
            'Elimina' => ''
);

$tablejoin = $gTables['artico']. " LEFT JOIN " . $gTables['catmer'] . " ON " . $gTables['artico'] . ".catmer = " . $gTables['catmer'] . ".codice LEFT JOIN " . $gTables['rental_extra'] . " ON " . $gTables['artico'] . ".codice = " . $gTables['rental_extra'] . ".codart " ;

$ts = new TableSorter(
    $tablejoin,
    $passo,
    ['last_modified'=>'desc'],
    ['asset' => 0]);
?>
<script>
$(function() {
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
						data: {'type':'extra',ref:id},
						type: 'POST',
						url: '../vacation_rental/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_extra.php");
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

$('#closePdf').on( "click", function() {
		$('.framePdf').css({'display': 'none'});
	});
function openframe(url,codice){
	$(function(){
		$("#titolo").append(codice);
		$('#framePdf').attr('src',url);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '90%', 'height': '100%', 'z-index':'2000'});
    $("html, body").delay(100).animate({scrollTop: $('#framePdf').offset().top},200, function() {
        $("#framePdf").focus();
    });
	});
	$('#closePdf').on( "click", function() {
    $("#titolo").empty();
		$('.framePdf').css({'display': 'none'});
	});
};


function Copy(id) { // ATTENZIONE questa funzione, se online, richiede che il sito sia in navigazione sicura (https) altrimenti non funziona
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
        <p><b>extra:</b></p>
        <p>codice:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
	<div class="framePdf panel panel-success" style="display: none; position: fixed; left: 5%; top: 5px">
			<div class="col-lg-12">
				<h4><div class="col-xs-11" id="titolo" ></div></h4>
				<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
			</div>
			<iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
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
        </td>
		<td class="FacetFieldCaptionTD">
        </td>
    <td class="FacetFieldCaptionTD"></td>
    <td class="FacetFieldCaptionTD">
      <input type="text" name="des_facility" placeholder="ID struttura"  id="suggest_facility_artico" class="input-sm form-control" value="<?php echo (isset($des_facility))? htmlentities($des_facility, ENT_QUOTES) : ""; ?>" maxlength="30">

        </td>
		<td class="FacetFieldCaptionTD">
        <?php gaz_flt_disp_select("codcat", $gTables['catmer'].".codice AS codcat, ". $gTables['catmer'].".descri AS descat", $tablejoin, 1,'codcat ASC','descat'); ?>
        </td>


		<td class="FacetFieldCaptionTD" colspan="7">
			<input type="submit" class="btn btn-sm btn-default" name="search" value="<?php echo $script_transl['search'];?>" onClick="javascript:document.report.all.value=1;">
			<a class="btn btn-sm btn-default" href="?">Reset</a>
			<?php  $ts->output_order_form(); ?>
		</td>
	</tr>

<?php
$gForm = new magazzForm();

$result = gaz_dbi_dyn_query ( $gTables['artico']. ".*, ".$gTables['catmer']. ".descri AS descat, ".$gTables['catmer']. ".codice AS codcat",$tablejoin, $ts->where." AND good_or_service=1 AND (custom_field REGEXP 'extra') AND (custom_field REGEXP 'vacation_rental')", $ts->orderby, $ts->getOffset(), $ts->getLimit());

echo '<tr>';
$ts->output_headers();
echo '</tr>';
// da configurazione azienda
$show_artico_composit = gaz_dbi_get_row($gTables['company_config'], 'var', 'show_artico_composit');
$tipo_composti = gaz_dbi_get_row($gTables['company_config'], 'var', 'tipo_composti');
while ($r1 = gaz_dbi_fetch_array($result)) {
			$data = json_decode($r1['custom_field'],true);// trasformo il json custom_field in array
      $r2 = gaz_dbi_get_row($gTables['rental_extra'], 'id', $data['vacation_rental']['extra']);
      $rif_facility_arr = explode(',',$r2['rif_facility']);
      foreach($rif_facility_arr as $facil){
        if (intval($facil)>0){
          $r2['riffacility'][$facil]=gaz_dbi_get_row($gTables['artico_group'], 'id_artico_group', $facil)['descri'];
        }else{
          $r2['riffacility'][$facil]='';
        }
      }
			$r=array_merge($r1,$r2);
			$r['accommodation_type'] = "";
			$class = 'success';
			// contabilizzazione magazzino
			$com = '';
			if ($admin_aziend['conmag'] > 0 && $r["good_or_service"] != 1 && $tipo_composti['val']=="STD") {
				$com = '<a class="btn btn-xs btn-'.$class.'" href="../magazz/select_schart.php?di=0101' . date('Y') . '&df=' . date('dmY') . '&id=' . $r['codice'] . '" target="_blank">
			  <i class="glyphicon glyphicon-check"></i><i class="glyphicon glyphicon-print"></i>
			  </a>';
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
			<a class="btn btn-xs btn-'.$class.'" href="../vacation_rental/admin_extra.php?Update&codice='.$r['codice'].'" ><i class="glyphicon glyphicon-edit"></i> '.$r['codice'].'</a>';
			echo "<i ".$ecomGlobe." ></i>";// globo per e-commerce
			echo '</td>';
			echo '<td><span class="gazie-tooltip" data-type="product-thumb" data-id="'. $r['codice'] .'" data-title="'. $r['annota'].'" >'.get_string_lang($r['descri'], $lang).'</span>';
			echo "</td>\n";
      echo '<td class="text-center">'.$admin_aziend['symbol']," ",gaz_format_quantity($r['web_price'],1,$admin_aziend['decimal_price']);
			echo "</td>\n";
			echo '<td class="text-center">';
			switch($r['mod_prezzo']){// 0 => 'a prenotazione', 1 => 'a persona', 2 => 'a notte', 3 => 'a persona e a notte, 4 => 'cadauno'
				case "0":
					echo "a prenotazione";
				break;
				case "1":
					echo "a persona";
				break;
				case "2":
					echo "a notte";
				break;
				case "3":
					echo "a persona e a notte";
				break;
        case "4":
					echo "cadauno";
				break;
			}
    if ($r['id_artico_group']>0){
      echo '<a class="btn btn-xs btn-default" title="Struttura"  onclick="getgroup(\''.$r['id_artico_group'].'\');"> <i class="glyphicon glyphicon-level-up"></i> </a> ';
    }
			echo "</td>\n";
			echo '<td class="text-center">'.$r['rif_alloggio'];
			echo "</td>\n";
      echo '<td class="text-center">';
       if (count($r['riffacility'])>0){
         foreach ( $r['riffacility'] as $key => $val){
           echo $key," - ",$val,"  ";
         }
       }
			echo "</td>\n";
			echo "</td>\n";
			echo '<td class="text-center">'.$r['catmer'].'-'.$r['descat'];
			echo "</td>\n";
      if ($r['max_quantity']>0){
			?>
			<td class="text-center">
				<input type="text" value="<?php echo $firstpart_ical_url,"/modules/vacation_rental/ical.php?house_code=",$r['codice']; ?>" id="copy<?php echo $r['codice'];?>" readonly width="100">
				<a class="btn btn-xs btn-default" style="cursor:pointer;" onclick="Copy('<?php echo $r['codice'];?>')">
					<i class="glyphicon glyphicon-copy" title="Copia url Ical">
					</i>
				</a>
			</td>
			<?php
      }else{
        echo "<td></td>";
      }

      if ($r['max_quantity']>0){
			echo '<td class="text-center"><a class="btn btn-xs btn-default" style="cursor:pointer;" onclick="openframe(\'extra_availability.php?extra_code='.$r["codice"].'\',\'Calendario disponibilità extra: <b>'.$r["codice"].'</b>\')" data-toggle="modal" data-target="#iframe"> <i class="glyphicon glyphicon-calendar" title="Calendario della disponibilità degli extra"></i></a>';
			echo "</td>\n";
      }else{
        echo "<td></td>";
      }
			echo '<td class="text-center"><a class="btn btn-xs btn-default" title="Duplica questo extra" href="clone_extra.php?codice='.$r["codice"].'"> <i class="glyphicon glyphicon-export"></i></a>';
			echo "</td>\n";
			echo '<td class="text-center"><a class="btn btn-xs  btn-elimina dialog_delete" ref="'. $r['codice'].'" artico="'. $r['descri'].'"> <i class="glyphicon glyphicon-trash"></i></a>';
			echo "</td>\n";
			echo "</tr>\n";
}
?>
     </table>
	</div>
</form>
<a href="https://programmisitiweb.lacasettabio.it/gazie/vacation-rental-il-gestionale-per-case-vacanza-residence-bb-e-agriturismi/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:20%; z-index:2000;"> Vacation rental è un modulo di Antonio Germani</a>
<script src="../../js/custom/fix_select.js" type="text/javascript"></script>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>
