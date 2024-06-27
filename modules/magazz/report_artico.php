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
$admin_aziend=checkAdmin();
$pdf_to_modal = gaz_dbi_get_row($gTables['company_config'], 'var', 'pdf_reports_send_to_modal')['val'];
require("../../library/include/header.php");
// campi ammissibili per la ricerca
$search_fields = [
  'sea_codice' => "{$gTables['artico']}.codice LIKE '%%%s%%'",
	'des_artico' => "{$gTables['artico']}.descri LIKE '%%%s%%'",
	'codfor' => "{$gTables['artico']}.codice_fornitore LIKE '%%%s%%'",
  'gos' => "{$gTables['artico']}.good_or_service = %d",
	'unimis' => "{$gTables['artico']}.unimis LIKE '%%%s%%'",
  'asset' => "id_assets = %d",
  'codcat' => "{$gTables['catmer']}.codice = %d",
];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
  "Codice" => 'codice',
  "Descrizione"=>'descri',
  "Categoria" => 'catmer',
  "Merce<br/>Servizio" => 'good_or_service',
  'Codice<br/>Fornitore' => 'codice_fornitore',
  'U.M.' => 'unimis',
  'Prezzo vend.<br/>listino 1' => 'preve1',
  'Ordini clienti' => '',
  'Ultimi acquisti' => '',
  'Giacenza' => '',
  '% IVA' => 'aliiva',
  'Lotti' => '',
  'Duplica' => '',
  'Elimina' => ''
);

$tablejoin = $gTables['artico']. " LEFT JOIN " . $gTables['catmer'] . " ON " . $gTables['artico'] . ".catmer = " . $gTables['catmer'] . ".codice";

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
						data: {'type':'artico',ref:id},
						type: 'POST',
						url: '../magazz/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_artico.php");
						}
					});
				}}
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
	$("#idgroup").append("Gruppo");
    $("#dialog_group").attr("title","Gruppo articoli per varianti ID "+artico);
	$.get("ajax_request.php?opt=group",
		{term: artico},
		function (data) {
            var j=0;
			$.each(data, function(i, value) {
                j++;
                if (j==1) {
                    $(".list_group").append("<tr><td>"+value.descri+"&nbsp;&nbsp;</td></tr><tr><td>&nbsp;</td></tr>");
                    $("#idvar").append("composto dalle seguenti varianti:");
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
                    text:'Modifica il gruppo',
					'class':'btn btn-warning',
					click:function (event, ui) {
                        window.open('../magazz/admin_group.php?Update&id_artico_group='+ artico);
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
function printPdf(urlPrintDoc){
	$(function(){
		$('#framePdf').attr('src',urlPrintDoc);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '80%', 'left': '10%', 'height': '80%', 'z-index':'2000'});
    $("html, body").delay(100).animate({scrollTop: $('#framePdf').offset().top-80},200, function() {
      $("#framePdf").focus();
    });
		$('#closePdf').on( "click", function() {
			$('.framePdf').css({'display': 'none'});
		});
	});
};
</script>
<?php
$script_transl = HeadMain(0, array('custom/autocomplete'));
?>
<div class="text-center"><h3><?php echo $script_transl['title'];?></h3></div>
<?php
$ts->output_navbar();

?>
<form method="GET" class="clean_get">
  <div class="framePdf panel panel-success" style="display: none; position: absolute;">
		<div class="col-lg-12">
    <div class="col-xs-11"><h4></h4></div>
		<div class="col-xs-1"><h4><button type="button" title="chiudi" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
		</div>
		<iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
	</div>
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>articolo:</b></p>
        <p>codice:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="iddescri"></p>
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
	<table class="table table-responsive table-striped table-bordered table-condensed">
	<tr>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="sea_codice" placeholder="codice" id="suggest_codice_artico" class="input-sm form-control" value="<?php echo (isset($sea_codice))? htmlentities($sea_codice, ENT_QUOTES) : ""; ?>" maxlength="15">
		</td>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="des_artico" placeholder="descrizione"  id="suggest_descri_artico" class="input-sm form-control" value="<?php echo (isset($des_artico))? htmlentities($des_artico, ENT_QUOTES) : ""; ?>" maxlength="30">
        </td>
		<td class="FacetFieldCaptionTD">
        <?php gaz_flt_disp_select("codcat", $gTables['catmer'].".codice AS codcat, ". $gTables['catmer'].".descri AS descat", $tablejoin, 1,'codcat ASC','descat'); ?>
        </td>
		<td class="FacetFieldCaptionTD">
        <?php gaz_flt_disp_select("gos", $gTables['artico'].".good_or_service AS gos", $tablejoin, 1,'good_or_service ASC', $script_transl['good_or_service_value']); ?>
        </td>
		<td class="FacetFieldCaptionTD">
      <input type="text" name="codfor" placeholder="Codice fornitore"  class="input-sm form-control" value="<?php echo (isset($codfor))? htmlentities($codfor, ENT_QUOTES) : ""; ?>" maxlength="32">
    </td>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="unimis" placeholder="U.M." class="input-sm form-control" value="<?php echo (isset($unimis))? $unimis : ""; ?>" maxlength="3">
        </td>
		<td class="FacetFieldCaptionTD"></td>
		<td class="FacetFieldCaptionTD" colspan="7">
			<input type="submit" class="btn btn-sm btn-default" name="search" value="<?php echo $script_transl['search'];?>" onClick="javascript:document.report.all.value=1;">
			<a class="btn btn-sm btn-default" href="?">Reset</a>
			<?php  $ts->output_order_form(); ?>
		</td>
	</tr>
<?php
$gForm = new magazzForm();
$result = gaz_dbi_dyn_query ( $gTables['artico']. ".*, ".$gTables['catmer']. ".descri AS descat, ".$gTables['catmer']. ".codice AS codcat",$tablejoin, $ts->where, $ts->orderby, $ts->getOffset(), $ts->getLimit());
?>
	<tr class="visible-xs hidden-xs">
		<td colspan="2" class="text-center col-xs-12 text-small" style="padding: 2px;">
			<input type="submit" class="btn btn-xs btn-default col-xs-2" name="search" value="<?php echo $script_transl['search'];?>" style="color: green;" onClick="javascript:document.report.all.value=1;"><div class="col-xs-2 text-warning"><i class="glyphicon glyphicon-search"></i></div>
			<a class="btn btn-xs btn-default col-xs-2"  style="color: red;" href="?">Reset</a>
		</td>
	</tr>
<?php
echo '<tr>';
$ts->output_headers();
echo '</tr>';
// creo la matrice con il numero delle movimentazioni subite dagli articoli
$accmov=[];
$rs=gaz_dbi_query("SELECT artico , COUNT(*) FROM ".$gTables['movmag']." GROUP BY artico");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};
$rs=gaz_dbi_query("SELECT codart, COUNT(*) FROM ".$gTables['rigdoc']." GROUP BY codart");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};
$rs=gaz_dbi_query("SELECT codart, COUNT(*) FROM ".$gTables['rigbro']." GROUP BY codart");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};
$rs=gaz_dbi_query("SELECT codice_artico_base, COUNT(*) FROM ".$gTables['distinta_base']." GROUP BY codice_artico_base");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};

// da configurazione azienda
$tipo_composti = gaz_dbi_get_row($gTables['company_config'], 'var', 'tipo_composti');

while ($r = gaz_dbi_fetch_array($result)) {
  // giacenza
  $mv = $gForm->getStockValue(false, $r['codice']);
  $magval = array_pop($mv);
  $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
  if (isset($magval['q_g']) && round($magval['q_g'],6) == "-0"){
    $magval['q_g']=0;
  }
	$class = 'success';
  if ($r['good_or_service']==1) { // è un servizio
    $class = 'info';
  } elseif (is_numeric($magval)) { // giacenza = 0
    $class = 'danger';
    $magval=[];
    $magval['q_g']=0;
  } elseif ($magval['q_g'] < 0) { // giacenza inferiore a 0
    $class = 'danger';
  } elseif ($magval['q_g'] > 0) { //
    if ($magval['q_g']<=$r['scorta']){
      $class = 'warning';
    }
  } else { // giacenza = 0
      $class = 'danger';
  }
  // contabilizzazione magazzino
  $com = '';
  if ($admin_aziend['conmag'] > 0 && $r["good_or_service"] != 1 && $tipo_composti['val']=="STD") {
      $com = '<a class="btn btn-xs btn-'.$class.'" href="../magazz/select_schart.php?di=0101' . date('Y') . '&df=' . date('dmY') . '&id=' . $r['codice'] . '" target="_blank">
  <i class="glyphicon glyphicon-list"></i> <i class="glyphicon glyphicon-print"></i>
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
  <a class="btn btn-xs btn-'.$class.'" href="../magazz/admin_artico.php?Update&codice='.$r['codice'].'" ><i class="glyphicon glyphicon-edit"></i> '.$r['codice'].'</a>';
  if ( $r["good_or_service"] == 2 ) {
    echo '<a class="btn btn-xs btn-warning" href="../magazz/admin_artico_compost.php?Update&codice='.$r['codice'].'" title="Modifica la composizione"><i class="glyphicon glyphicon-list"></i></a>';
    $des_bom ='<span class="text-info bg-info"> <b> '.$script_transl['good_or_service_value'][$r['good_or_service']].' </b> </span> <a target="_blank" title="Stampa l\'albero della distinta base" class="btn btn-xs btn-info" href="stampa_bom.php?ri=' . $r["codice"] . '"><i class="glyphicon glyphicon-tasks"></i> <i class="fas fa-file-pdf"></i></a>';
  } else {
    $des_bom = $script_transl['good_or_service_value'][intval($r['good_or_service'])];
  }
	echo "<i ".$ecomGlobe." ></i>";// globo per e-commerce
  echo '</td>';
  echo '<td><span class="gazie-tooltip col-xs-12" data-type="product-thumb" data-id="'. $r['codice'] .'" data-title="'. $r['annota'].'" data-maxsize="360" >'.$r['descri'].'</span>';
  echo "</td>\n";
  echo '<td class="text-center">'.$r['catmer'].'-'.$r['descat'];
  echo "</td>\n";
  echo '<td class="text-center">'.$des_bom. ' ';
	if ($r['id_artico_group']>0){
		echo '<a class="btn btn-xs btn-default" title="Gruppo varianti"  onclick="getgroup(\''.$r['id_artico_group'].'\');"> <i class="glyphicon glyphicon-level-up"></i> </a> ';
  }
  // colonna codice fornitore
	echo '</td><td class="text-center">'.$r['codice_fornitore'].'</td>';
	echo "</td>\n";
  echo '<td class="text-center">'.$r['unimis'];
	echo "</td>\n";
  echo '<td class="text-center">'.number_format($r['preve1'], $admin_aziend['decimal_price'], ',', '.');
	echo "</td>\n";
  echo '<td class="text-center">';
  echo '<a class="btn btn-xs btn-default" title="Ordini aperti"  onclick="getorders(\''.$r['codice'].'\');"> <i class="glyphicon glyphicon-th-list"></i> </a> ';
	echo "</td>\n";
  echo '<td class="text-center">';
  echo ' <a class="btn btn-xs btn-default" title="Acquisti"  onclick="getlastbuys(\''.$r['codice'].'\');"> <i class="glyphicon glyphicon-download-alt"></i></a>';
	echo "</td>\n";
  if (($r['mostra_qdc']==1 && $r["good_or_service"]==1) or ($r["good_or_service"]==1 && floatval($magval['q_g'])==0)){//se è riservato al quaderno di campagna ed è servizio || è servizio e la q.tà è zero
    echo "<td></td>";// colonna quantità vuota
  }elseif ($r["good_or_service"]==1 && floatval($magval['q_g'])<>0 ){// se è un servizio ma sono stati registrati movimenti
    echo '<td class="text-right bg-danger text-danger">'.gaz_format_quantity(floatval(substr($magval['q_g'],0,15)),1,$admin_aziend['decimal_quantity']);
    echo "</td>\n";// segnalo in rosso
  }else{
    echo '<td class="text-right">'.gaz_format_quantity(floatval(substr($magval['q_g'],0,15)),1,$admin_aziend['decimal_quantity']).' '.$com;
    echo "</td>\n";
  }
  echo '<td class="text-center">'.floatval($iva['aliquo']);
	echo "</td>\n";
  echo '<td class="text-center">';
	if (intval($r['lot_or_serial'])>0) {
		$classcol=(intval($r['lot_or_serial'])==1)?'btn-info':'btn-success';
		$lor=(intval($r['lot_or_serial'])==1)?'Lot':'Ser';
    ?>
    <a class="btn <?php echo $classcol; ?> btn-xs" href="javascript:;" onclick ="printPdf('../../modules/magazz/mostra_lotti.php?codice=<?php echo $r["codice"];?>')"> <i class="glyphicon glyphicon-tag"></i></a>
    <?php
  }
  echo "</td>\n";
  echo '<td class="text-center"><a class="btn btn-xs btn-default" href="clone_artico.php?codice='.$r["codice"].'"> <i class="glyphicon glyphicon-export"></i></a>';
	echo "</td>\n";
  // colonna elimina
  echo '<td class="text-center"><a class="btn btn-xs ';
  if (isset($accmov[$r["codice"]])){
    echo 'btn-default" disabled title="Articolo non è eliminabile perché presente su '. $accmov[$r["codice"]].' registrazioni"';
  } else {
    echo 'btn-elimina dialog_delete" ref="'. $r['codice'].'" artico="'. $r['descri'].'"';
  }
  echo '> <i class="glyphicon glyphicon-trash"></i></a>';
	echo "</td>\n";
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
