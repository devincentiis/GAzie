<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-present - Antonio Germani, Massignano (AP)
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
$admin_aziend = checkAdmin();
$pointenable = gaz_dbi_get_row($gTables['company_config'], 'var', 'pointenable')['val'];
for($xl=1; $xl<=3; $xl++){
  $pointlevel[$xl] = gaz_dbi_get_row($gTables['company_config'], 'var', 'pointlevel'.$xl)['val'];
  $pointlevelname[$xl] = gaz_dbi_get_row($gTables['company_config'], 'var', 'pointlevel'.$xl.'name')['val'];
}
$points_expiry = gaz_dbi_get_row($gTables['company_config'], 'var', 'points_expiry')['val'];

function getDayNameFromDayNumber($day_number) {
    return ucfirst(utf8_encode(strftime('%A', mktime(0, 0, 0, 3, 19+$day_number, 2017))));
}

// funzione di utilità generale, adatta a mysqli.inc.php
function cols_from($table_name, ...$col_names) {
    $full_names = array_map(function ($col_name) use ($table_name) { return "$table_name.$col_name"; }, $col_names);
    return implode(", ", $full_names);
}

// visualizza i bottoni dei documenti di evasione associati all'ordine
function mostra_documenti_associati($ordine, $paid) {
    global $gTables;
    $admin_aziend = checkAdmin();
    include_once("manual_settings.php");

    // seleziono i documenti evasi che contengono gli articoli di questo ordine
    $rigdoc_result = gaz_dbi_dyn_query('DISTINCT id_tes', $gTables['rigdoc'], "id_order = " . $ordine, 'id_tes ASC');
    while ( $rigdoc = gaz_dbi_fetch_array($rigdoc_result) ) {
        // per ogni documento vado a leggere il numero documento
        $tesdoc_result = gaz_dbi_dyn_query('*', $gTables['tesdoc'], "id_tes = " . $rigdoc['id_tes'], 'id_tes DESC');
        $tesdoc_r = gaz_dbi_fetch_array($tesdoc_result);

        // a seconda del tipo di documento visualizzo il bottone corrispondente
        $btn="btn-default";
        if ($tesdoc_r["id_con"] > 0) {// se è già stato contabilizzato rendo verde il pulsante e chiedo se contabilizzare i pagamenti
          $btn="btn-success";
          if (floatval($paid)>0){// se sono stati anticipati dei pagamenti, visualizzo la possibilità di contabilizzarli
            $sqlquery= "SELECT COUNT(DISTINCT ".$gTables['rigmoc'].".id_tes) as nummov,codcon,".$gTables['clfoco'].".codice, sum(import*(darave='D')) as dare,sum(import*(darave='A')) as avere, sum(import*(darave='D') - import*(darave='A')) as saldo, darave FROM ".$gTables['rigmoc']." LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['rigmoc'].".codcon = ".$gTables['clfoco'].".codice LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra WHERE ".$gTables['rigmoc'].".id_tes = ".intval($tesdoc_r['id_con'])." AND codcon like '".$admin_aziend['mascli']."%' and caucon <> 'CHI' and caucon <> 'APE' or (caucon = 'APE' and codcon like '".$admin_aziend['mascli']."%') GROUP BY codcon ORDER BY darave";
            $rs_castel = gaz_dbi_query($sqlquery);
            $r = gaz_dbi_fetch_array($rs_castel);
            if (abs($r['saldo']) >= 0.001) {
              echo "<a  class=\"btn btn-xs btn-default \"";
              echo " style=\"cursor:pointer;\" onclick=\"reg_movcon_payment('". $ordine."','". $r['codcon'] ."','".$tesdoc_r['id_con']."')\"";// devo passare id_tes di tesbro perché i rental payment sono connessi alla prenotazione
              echo "><i class=\"glyphicon glyphicon-transfer \" title=\"Contabilizza i Pagamenti\"></i></a>";
            }
          }
        }
        if ($tesdoc_r["tipdoc"] == "FAI") {
            // fattura immediata
            echo "<a class=\"btn btn-xs ",$btn,"\" title=\"visualizza la fattura immediata\" href=\"../vendit/stampa_docven.php?id_tes=" . $tesdoc_r['id_tes'] . "\">";
            echo "fatt. " . $tesdoc_r["numfat"];
            echo "</a> ";
        } elseif ($tesdoc_r["tipdoc"] == "DDT" || ($tesdoc_r["tipdoc"] == "FAD" && $tesdoc_r["ddt_type"]!='R')) {
            // documento di trasporto
            echo "<a class=\"btn btn-xs ",$btn,"\" title=\"visualizza il documento di trasporto\" href=\"../vendit/stampa_docven.php?id_tes=" . $tesdoc_r['id_tes'] . "&template=DDT\">";
            echo "ddt " . $tesdoc_r["numdoc"];
            echo "</a> ";
        } elseif ($tesdoc_r["tipdoc"] == "CMR" || ($tesdoc_r["tipdoc"] == "FAD" && $tesdoc_r["ddt_type"]='R')) {
            // documento cmr
            echo "<a class=\"btn btn-xs ",$btn,"\" title=\"visualizza il cmr\" href=\"../vendit/stampa_docven.php?id_tes=" . $tesdoc_r['id_tes'] . "&template=CMR\">";
            echo "cmr " . $tesdoc_r["numdoc"];
            echo "</a> ";
        } elseif ($tesdoc_r["tipdoc"] == "VCO") {
            // scontrino
            echo "<a class=\"btn btn-xs ",$btn,"\" title=\"visualizza lo scontrino come fattura\" href=\"../vendit/stampa_docven.php?id_tes=" . $tesdoc_r['id_tes'] . "&template=".$tesdoc_r["template"]."\">";
            echo "scontr. " . $tesdoc_r["numdoc"] . "<br /> " . gaz_format_date($tesdoc_r["datemi"]);
            echo "</a> ";
        } elseif ($tesdoc_r["tipdoc"] == "VRI") {
            // ricevuta
            echo "<a class=\"btn btn-xs ",$btn,"\" title=\"visualizza la ricevuta\" href=\"../vendit/stampa_docven.php?id_tes=" . $tesdoc_r['id_tes'] . "&template=Received\">";
            echo "ricevuta " . $tesdoc_r["numdoc"] . "<br /> " . gaz_format_date($tesdoc_r["datemi"]);
            echo "</a> ";
        } else {
            echo $tesdoc_r["tipdoc"];
        }
        if ($tesdoc_r["id_con"] == 0) {
          echo " <a class=\"btn btn-xs btn-default btn-cont\" style=\"font-size:10px;\" href=\"../../modules/vendit/accounting_documents.php?type=F&vat_section=" . $seziva . "&last=" . $tesdoc_r["protoc"] . "\"><i class=\"glyphicon glyphicon-euro\"></i>&nbsp;CONTABILIZZA</a>";
        }
    }
}
if (isset ($_GET['inevasi'])){
	$form['swStatus']=$_GET['inevasi'];
} elseif (isset ($_GET['tutti'])){
	$form['swStatus']=$_GET['tutti'];
} else {
	$form['swStatus']=(isset($_GET['swStatus']))?$_GET['swStatus']:'';
}

$partner_select = !gaz_dbi_get_row($gTables['company_config'], 'var', 'partner_select_mode')['val'];
$tesbro_e_partners = "{$gTables['tesbro']} LEFT JOIN {$gTables['clfoco']} ON {$gTables['tesbro']}.clfoco = {$gTables['clfoco']}.codice LEFT JOIN {$gTables['anagra']} ON {$gTables['clfoco']}.id_anagra = {$gTables['anagra']}.id";
$tesbro_e_destina = $tesbro_e_partners . " LEFT JOIN {$gTables['destina']} ON {$gTables['tesbro']}.id_des_same_company = {$gTables['destina']}.codice";

// campi ammissibili per la ricerca
$search_fields = [
    'id_doc'
    => "id_tes = %d",
    'numero'
    => "numdoc = %d",
    'auxil'  // leggi: 'tipo' (per compatibilità con link menù esistenti)
    => "tipdoc LIKE '%s'",
    'anno'
    => "YEAR(datemi) = %d",
    'cliente'
    => $partner_select ? "clfoco = '%s'" : "ragso1 LIKE '%%%1\$s%%' OR ragso2 LIKE '%%%1\$s%%'",
    'giorno'
    => "weekday_repeat = %d"
];

require("../../library/include/header.php");

$res=gaz_dbi_get_row($gTables['company_config'], "var", 'vacation_url_user');
$vacation_url_user=$res['val'];// carico l'url per la pagina front-end utente

?>
<link rel="stylesheet" type="text/css" href="jquery/jquery.datetimepicker.min.css"/ >
<script src="jquery/jquery.datetimepicker.full.min.js" type="text/javascript"></script>
<?php
$script_transl = HeadMain(0, array('custom/modal_form'));

// creo l'array (header => campi) per l'ordinamento dei record
$terzo = (isset($_GET['auxil']) && $_GET['auxil'] == 'VOG') ? ['weekday_repeat' => 'weekday_repeat'] : ['date' => 'start'];
$sortable_headers = array(
    "ID" => "id_tes",
    $script_transl['number'] => "numdoc",
    $script_transl[key($terzo)] => current($terzo),
    "Codice alloggio" => "house_code",
    "Check-in" => "start",
    "Check-out" => "end",
    "Notti" => "",
    "Persone" => "",
    "Tour op." => "id_agente",
    "Cliente" => "clfoco",
    "Località" => "",
    "Importo" => "",
    "" => "",
    $script_transl['status'] => "status",
    $script_transl['print'] => "",
    "Mail" => "",
    $script_transl['duplicate'] => "",
    $script_transl['delete'] => ""
);
unset($terzo);
if (isset($form['swStatus']) AND $form['swStatus']=="Inevasi"){
	$passo=1000;
}
if (!isset($_GET['auxil'])){
	$auxil='VOR';
}else{
	$auxil=$_GET['auxil'];
}
if (count($_GET)<=1){
	// ultimo documento
	$rs_last = gaz_dbi_dyn_query('seziva, YEAR(datemi) AS yearde', $gTables['tesbro'], "tipdoc LIKE '".substr($auxil,0,3)."'", 'datemi DESC, id_tes DESC', 0, 1);
	$last = gaz_dbi_fetch_array($rs_last);
	if ($last) {
		$default_where=['sezione' => $last['seziva'], 'tipo' => 'F%', 'anno'=>$last['yearde']];
        $_GET['anno']=$last['yearde'];		
	} else {
		$default_where= ['auxil' => 'VOR'];
	}

} else {
   $default_where= ['auxil' => 'VOR'];
}
$ts = new TableSorter(
    isset($_GET["destinaz"]) ? $tesbro_e_destina :
	(!$partner_select && isset($_GET["cliente"]) ? $tesbro_e_partners : $gTables['tesbro']),
    $passo,
    ['start' => 'asc', 'numdoc' => 'desc'],
    $default_where
);
$tipo = $auxil;
# le <select> spaziano tra i documenti di un solo tipo (VPR, VOR o VOG)
$where_select = sprintf("tipdoc LIKE '%s'", gaz_dbi_real_escape_string($tipo));

if (isset($_GET['house_code'])){// se devo visualizzare solo un determinato alloggio
  $ts->where .= " AND ".$gTables['rental_events'].".house_code ='".$_GET['house_code']."'";
}

?>
<script>

$(function() {
   $( "#dialog" ).dialog({
      autoOpen: false
   });
});
function confirMail(link, cod_partner, dest=''){
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

            mails[j]=value.email;
            j++;
          }else{// se non ci sono indirizzi da scegliere valorizzo di default
            $("#mailaddress").val(dest);
          }

        });

		  }, "json"
    );

    tes_id = link.id.replace("doc", "");
    $.fx.speeds._default = 500;
    targetUrl = $("#doc"+tes_id).attr("url");
    //$("p#mail_adrs").html($("#doc"+tes_id).attr("mail"));
    $("p#mail_attc").html($("#doc"+tes_id).attr("namedoc"));
    $( "#dialog" ).dialog({
         modal: "true",
      show: "blind",
      hide: "explode",
      buttons: [{
        text: "<?php echo $script_transl['submit']; ?> ",
        "class": 'btn',
        click: function () {
          if ( !$("#mailaddress").val() ) {
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
            });
            $('#'+link.id).addClass("btn-success");
            $(this).dialog("close");
          }

        },
      },
      {
        text: "<?php echo $script_transl['cancel']; ?>",
        "class": 'btn',
        click: function () {
          $("#mailbutt div").remove();
          $("#mailaddress").val('');
          $(this).dialog('destroy');
        },
      }]
  });
   $("#dialog" ).dialog( "open" );
}
function confirMailC(link){
  tes_id = link.id.replace("docC", "");
  $.fx.speeds._default = 500;
  targetUrl = $("#docC"+tes_id).attr("urlC");
  //alert (targetUrl);
  $("p#mail_adrs").html($("#docC"+tes_id).attr("mail"));
  $("p#mail_attc").html($("#docC"+tes_id).attr("namedoc"));
  $( 'a#c'+tes_id ).css( "display", "none" );
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
        $('#'+link.id).addClass("btn-success");
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


function choice_template(modulo) {
	$( function() {
    var dialog
	,
	dialog = $("#confirm_print").dialog({
		modal: true,
		show: "blind",
		hide: "explode",
		width: "400",
		buttons:[{
			text: "Su carta bianca ",
			"class": 'btn',
			click: function () {
				$('#framePdf').attr('src',modulo);
        $('#framePdf').css({'height': '100%'});
        $('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
        $('#closePdf').on( "click", function() {
        $('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
        });
			},
		},
		{
			text: "Su carta intestata ",
			"class": 'btn',
			click: function () {
				window.location.href = modulo+'&lh';
			},
		}],
		close: function(){
				$(this).dialog('destroy');
		}
	});
	});
}

function pay(ref) {

		$( "#credit_card" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'visualizza',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
            $.ajax({
              data: {'type':'booking',ref:ref},
              type: 'POST',
              url: '../vacation_rental/decrypt.php',
              dataType: 'json',
              success: function(response){
                var response = JSON.stringify(response);
                // alert('success:'+response);
                arr = $.parseJSON(response); //convert to javascript array
                var n=0;
                $.each(arr,function(key,value){
                  n=n+1;
                  $("#cc"+n).html(value);
                });
              },
              error: function(response){
                var response = JSON.stringify(response);
                alert ('Error: '+response);
              }
            });
          }
				},
				"Chiudi": function() {
          $("#cc1").html('');
          $("#cc2").html('');
          $("#cc3").html('');
          $("#cc4").html('');
					$(this).dialog("close");
				}
			}
		});
		$("#credit_card" ).dialog( "open" );

    $('#delete_data').on( "click", function() {
      if (confirm("ATTENZIONE: Stai per distruggere i dati della carta di credito memorizzati.")){
        $.ajax({
          data: {'type':'delete_data',ref:ref},
          type: 'POST',
          url: '../vacation_rental/delete.php',
          dataType: 'text',
          success: function(response){
            $("#cc1").html('');
            $("#cc2").html('');
            $("#cc3").html('');
            $("#cc4").html('');
            window.location.replace("./report_booking.php?auxil=VOR");
          }
        });
      }else{
        $("#credit_card").dialog("close");
      }
		});
}

function delete_payment(ref,tes) {
  if (confirm("Stai per eliminare un pagamento.")){
    $.ajax({
      data: {'type':'delete_payment',ref:ref},
      type: 'POST',
      url: '../vacation_rental/delete.php',
      success: function(output){
        var tot=0;
        var tot_secdep=0;
        $.ajax({
          data: {'type':'payment_list',ref:tes},
          type: 'POST',
          url: '../vacation_rental/manual_payment.php',
          dataType: 'json',
          success: function(response){
            var response = JSON.stringify(response);
            arr = $.parseJSON(response); //convert to javascript array
            $.each(arr, function(n, val) {
              if (val.payment_status=="Completed"){
                if (val.type != "Deposito_cauzionale"){
                tot = tot+parseFloat(val.payment_gross);
                }else{
                  tot_secdep = tot_secdep+parseFloat(val.payment_gross)
                }
              }
            });
            if (tot>0){
              $("#atest"+tes).addClass("btn-success");
              $("#test"+tes).html(" Pagato "+tot.toFixed(2)+"");
            }else if(tot<=0){
              $("#atest"+tes).removeClass("btn-success");
              $("#atest"+tes).addClass("btn-default");
              $("#test"+tes).html("");
            }
            if (tot_secdep>0){
              $("#secdep"+tes).html(" Pagato "+tot_secdep.toFixed(2)+"");
            }else if(tot<=0){
              $("#secdep"+tes).html("");
            }
            tot=0;
            tot_secdep=0;
            //$("p#payment_des").append("<br><b>TOTALE "+tot.toFixed(2)+"</b>");
          }
        });
        $("#type").val('');
        $("#txn_id").val('');
        $("#payment_gross").val('');
        $("#payment_des").html('');
        $("p#payment_des").html('');
        tot=0;
        $("#dialog_payment").dialog("close");
      }
    });
  }
}


function reg_movcon_payment(ref,codcon,tescon) {
  if (confirm("Stai per registrare in contabilità i pagamenti effettuati.")){
        var tot=0;
        $.ajax({
          data: {opt:'reg_movcon_payment',term:ref,codcon:codcon,tescon:tescon},
          type: 'GET',
          url: '../vacation_rental/ajax_request.php',
          dataType: 'html',
          success: function(response){
            alert(response);
          }
        });
  }
}

function payment(ref) {
  var tot=0;
  var tot_secdep=0;
  setTimeout(function(){
        // all'apertura del dialog prendo tutti i pagamenti già fatti e mostro il totale;
        $.ajax({
          data: {'type':'payment_list',ref:ref},
          type: 'POST',
          url: '../vacation_rental/manual_payment.php',
          dataType: 'json',
          success: function(response){

            var response = JSON.stringify(response);
            arr = $.parseJSON(response); //convert to javascript array
            tot=0;tot_secdep=0;
            $.each(arr, function(n, val) {
              $("p#payment_des").append(val.currency_code+" "+val.payment_gross+" - "+val.payment_status+" - "+val.created+" "+val.type+" - "+val.descri+" <input type='submit' class='btn btn-sm btn-default' name='delete form='report_form' onClick='delete_payment("+val.payment_id+","+ref+");' value='ELIMINA'><br>");
              if (val.payment_status=="Completed"){
                if (val.type != "Deposito_cauzionale"){
                tot = tot+parseFloat(val.payment_gross);
                }else{
                  tot_secdep = tot_secdep+parseFloat(val.payment_gross)
                }
              }
            });
            $("p#payment_des").append("<br><b>TOTALE "+tot.toFixed(2)+"</b>");
          },
          error: function(error){
            var error = JSON.stringify(error);
            alert(error);
          }

        });
    },100);

		$( "#dialog_payment" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'conferma',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
            var sel = $("#target_account").val();
            var type = $("#type").val();
            var txn_id = $("#txn_id").val();
            var payment_gross = $("#payment_gross").val();
            $.ajax({
              data: {'type':'payment',ref:ref,type:type,txn_id:txn_id,payment_gross:payment_gross,target_account:sel},
              type: 'POST',
              url: '../vacation_rental/manual_payment.php',
              dataType: 'text',
              success: function(response){
                alert(response);
                if (type != "Deposito_cauzionale"){
                  tot=parseFloat(tot)+parseFloat(payment_gross);
                }else{
                  tot_secdep=parseFloat(tot_secdep)+parseFloat(payment_gross);
                }
                if (tot>0 && payment_gross){
                  if(type != 'Deposito_cauzionale'){
                    $("#atest"+ref).addClass("btn-success");
                    $("#test"+ref).html(" Pagato "+tot.toFixed(2)+"");
                  }else{
                    $("#secdep"+ref).html(" Pagato "+tot_secdep.toFixed(2)+"");
                  }
                }else if(tot<=0){
                  $("#atest"+ref).addClass("btn-default");
                  $("#test"+ref).html("");
                }
                $("#type").val('');
                $("#txn_id").val('');
                $("#payment_gross").val('');
                $("#payment_des").html('');
                $("#dialog_payment").dialog("close");
                tot=0;
                tot_secdep=0;
              }
            });
				}},
				"Chiudi": function() {
          $("#type").val('');
          $("#txn_id").val('');
          $("#payment_gross").val('');
          $("#payment_des").html('');
          tot=0;
					$("#dialog_payment").dialog("close");
				}
			}
		});
		$("#dialog_payment" ).dialog( "open" );
}

function point(ref,point,name,idtes,expired,expiry_points_date) {

    $("p#point_amount").append(name+" ha<b> "+point+" punti</b> con scadenza "+expiry_points_date);// all'apertura del dialog mostro i punti totali
    if(expired==1){
      $("p#point_exp").append("Attenzione: i punti sono scaduti e saranno cancellati");// all'apertura del dialog se scaduti avviso cancellazione
    }
    $.ajax({// carico i movimenti e li mostro nel dialog
        data: {term:idtes,opt:'point_mov',ref:ref},
        type: 'GET',
        url: '../vacation_rental/ajax_request.php',
        dataType: 'text',
        success: function(output){
          //alert(output);
          $.each(JSON.parse(output), function(idx, obj) {
            var point = obj.points * obj.operat;
            $("#point_mov").append(obj.title+' : punti = '+point+' Attribuiti il '+obj.timestamp+' <br>');
          });
        }
      });
    	$( "#dialog_point" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'conferma',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
            var motive = $("#motive").val();// motivazione
            var points = $("#points").val();// punti attribuiti
            var email=$('#checkbox_email_point').prop('checked');
            $.ajax({
              data: {'term':'point',opt:'point','ref':ref,'motive':motive,'points':points,'email':email,'idtes':idtes},
              type: 'GET',
              url: '../vacation_rental/ajax_request.php',
              dataType: 'text',
              success: function(response){
                alert(response);
                $("#motive").val('');
                $("#points").val('');
                $("#point_amount").html('');
                $("#point_exp").html('');
                $("#dialog_point").dialog("close");
                window.location.reload(true);
              }
            });
				}},
				"Chiudi": function() {
          $("#point_mov").html('');
          $("#motive").val('');
          $("#points").val('');
          $("#point_amount").html('');
          $("#point_exp").html('');
          tot=0;
					$("#dialog_point").dialog("close");
				}
			}
		});
		$("#dialog_point").dialog( "open" );
}

$(function() {
  $("#dialog_bookcr").dialog({ autoOpen: false });
	$('.dialog_bookcr').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("nome"));
    var url = $(this).attr('url');
		var id = $(this).attr('ref');
		$( "#dialog_bookcr" ).dialog({
			minHeight: 1,
			width: "350",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'CREA NUOVO PDF',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
					$.ajax({
						type: 'GET',
            url: './'+url+'&save=true',
            dataType: 'text',
            beforeSend:function(){
               return confirm("Sei sicuro? Stai modificando senza firma del cliente!");
            },
						success: function(output){
		                    //alert(output);
                        $("#dialog_bookcr").dialog("close");
						}
					});
				}},
				"Annulla": function() {
					$(this).dialog("close");
				}
			}
		});
		$("#dialog_bookcr" ).dialog( "open" );
	});

  $("#dialog_leasecr").dialog({ autoOpen: false });
	$('.dialog_leasecr').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("nome"));
		var id = $(this).attr('ref');
    var url = $(this).attr('url');
		$( "#dialog_leasecr" ).dialog({
			minHeight: 1,
			width: "350",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'CREA NUOVO PDF',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
					$.ajax({
            type: 'GET',
            url: './'+url+'&save=true',
            dataType: 'text',
            beforeSend:function(){
               return confirm("Sei sicuro di voler generare il contratto? Il cliente ha firmato?");
            },
						success: function(output){
		                    //alert(output);
                        $("#dialog_leasecr").dialog("close");
						}
					});
				}},
				"Annulla": function() {
					$(this).dialog("close");
				}
			}
		});
		$("#dialog_leasecr" ).dialog( "open" );
	});

	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("nome"));
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
						data: {'type':'booking',id_tes:id},
						type: 'POST',
						url: '../vacation_rental/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_booking.php?auxil=<?php echo $tipo;?>");
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

  $("#dialog_feedback").dialog({ autoOpen: false });
	$('.dialog_feedback').click(function() {
    var ref = $(this).attr('ref');
    var feed_text = $(this).attr('feed_text');
    var feed_status = $(this).attr('feed_status');
    $("#sel_stato_feedback").val(feed_status);// imposto lo status nella select
    $('#sel_stato_feedback').on('change', function () {
      if (confirm("Confermi di voler cambiare stato?")){
        var feed_status = $("#sel_stato_feedback").val();
        $.ajax({
          data: {'opt':'change_feed_status',term:ref,status:feed_status},
          type: 'GET',
          url: '../vacation_rental/ajax_request.php',
          dataType: 'text',
          success: function(response){
            //alert(response);
            window.location.replace("./report_booking.php?auxil=VOR");
          }
        });
      }
    });
    $("#feedback_text").html($(this).attr("feed_text"));
      // Carico i voti e i relativi elementi
		 $.ajax({
      data: {'opt':'load_votes',term:ref},
      type: 'GET',
      url: '../vacation_rental/ajax_request.php',
      success: function(output){
        // visualizzo gli elementi e i voti nel dialog
        $.each(JSON.parse(output), function(idx, obj) {
          $("#feedback_vote").append(obj.element+': '+obj.score+' stelle<br>');
        });
      }
    });
    $( "#dialog_feedback" ).dialog({
      minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
        "Chiudi": function() {
          $("#feedback_vote").html('');
          $("#sel_stato_feedback").val('');
          $(this).dialog("close");
					$(this).dialog("destroy");
				}
      }
    });
		$("#dialog_feedback" ).dialog( "open" );
	});

  $("#dialog_stato_lavorazione").dialog({ autoOpen: false });
	$('.dialog_stato_lavorazione').click(function() {
		$("p#id_status").html($(this).attr("refsta"));
		$("p#de_status").html($(this).attr("prodes"));
		var refsta = $(this).attr('refsta');
    var new_stato_lavorazione = $(this).attr("prosta");
    var cust_mail = $(this).attr("cust_mail");
    var cust_mail2 = $(this).attr("cust_mail2");
    $("#sel_stato_lavorazione").val(new_stato_lavorazione);
    $('#sel_stato_lavorazione').on('change', function () {
        //ways to retrieve selected option and text outside handler
        new_stato_lavorazione = this.value;
    });
		$( "#dialog_stato_lavorazione" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'Modifica',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
            $("#dialog_stato_lavorazione").css("background", "url("+'spinner.gif'+") center no-repeat");
            var email=$('#checkbox_email').prop('checked');
            $.ajax({
              data: {'type':'set_new_stato_lavorazione','ref':refsta,'new_status':new_stato_lavorazione,email:email,cust_mail:cust_mail,cust_mail2:cust_mail2},
              type: 'POST',
              url: 'change_status.php',
              success: function(output) {
                 // alert('id:'+refsta+' new:'+new_stato_lavorazione+' email:'+email);
                 // alert(output);
                window.location.replace("./report_booking.php?auxil=VOR");
              }
            });
          }},
        "Non cambiare": function() {
          $(this).dialog("close");
          $(this).dialog("destroy");
				}
			}
		});
		$("#dialog_stato_lavorazione" ).dialog( "open" );
	});

  $.datetimepicker.setLocale('it');
  $("#datepicker").datetimepicker({
    defaultDate: new Date(),
    format:'d-m-Y H:i'
  });

  $("#dialog_check_inout").dialog({ autoOpen: false });
	$('.dialog_check_inout').click(function() {
		$("p#id_status_check").html($(this).attr("refcheck"));
		$("p#de_status_check").html($(this).attr("prodes"));
		var refcheck = $(this).attr('refcheck');
    var new_stato_check = $(this).attr("prostacheck");
    var cust_mail = $(this).attr("cust_mail");
    var cust_mail2 = $(this).attr("cust_mail2");
    var ckdate = $(this).attr("ckdate");
	if (ckdate.length>4){
		$("#datepicker").val(ckdate);
	}else{
		$("#datepicker").val('<?php echo date('d-m-Y H:i'); ?>');
	}
    var d = $("#datepicker").val();
    $("#sel_stato_check").val(new_stato_check);
    $("span#date_stato_check").html($(this).attr("ckdate"));

    if (new_stato_check=='OUT'){
      $("#feedback_email").show();
    }else{
      $("#feedback_email").hide();
    }
    $('#sel_stato_check').on('change', function () {
        //ways to retrieve selected option and text outside handler
		$("#datepicker").val('<?php echo date('d-m-Y H:i'); ?>');
        var d = $("#datepicker").val();
        //alert (d);
        new_stato_check = this.value;
        if (new_stato_check=='OUT'){
          $("#feedback_email").show();
        }else{
          $("#feedback_email").hide();
        }
    });

		$( "#dialog_check_inout" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'Modifica',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
            $("#dialog_check_inout").css("background", "url("+'spinner.gif'+") center no-repeat");
            var d = $("#datepicker").val();
            var email=$('#checkbox_email_inout').prop('checked');
            $.ajax({
              data: {'type':'set_new_status_check','ref':refcheck,'new_status':new_stato_check,email:email,cust_mail:cust_mail,cust_mail2:cust_mail2,datetime:d},
              type: 'POST',
              url: 'change_status.php',
              success: function(output) {
                  //alert('id:'+refcheck+' new:'+new_stato_check+' email:'+email + ' datetime:'+d);
                  //alert(output);
                window.location.replace("./report_booking.php?auxil=VOR");
              }
            });
          }},
        "Non cambiare": function() {
          $(this).dialog("close");
          $(this).dialog("destroy");
				}
			}
		});
		$("#dialog_check_inout" ).dialog( "open" );
	});

  $("#dialog_selfcheck").dialog({ autoOpen: false });
	$('.dialog_selfcheck').click(function() {
		$("p#num_status_self").append(" "+$(this).attr("numdoc"));
		$("p#de_status_self").html($(this).attr("status_now"));
    var msgself = $(this).attr('msgself');
    $("#msgself").val(msgself);
    $("#msgself").prop("disabled", "disabled");// all'inizio disabilito il test messaggio perché è anche disabilitato l'ivio mail
    var ref = $(this).attr('ref');
    var id_anagra = $(this).attr('id_anagra');
    var new_stato_lavorazione = $(this).attr("proself");

    var cust_mail = $(this).attr("cust_mail");
    $("#sel_stato_self").val(new_stato_lavorazione);
    $('#sel_stato_self').on('change', function () {
        //retrieve selected option and text outside handler
        new_stato_lavorazione = this.value;
    });
     $('#checkbox_email_self').on('change', function () {//retrieve click on check box mail

       if(($('#checkbox_email_self').prop('checked'))){// se invio mail posso scrivere il testo
         $("#msgself").prop("disabled", false);
       }else{// se non invio mail il testo è bloccato
          $("#msgself").prop("disabled", "disabled");
       }
    });
		$( "#dialog_selfcheck" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'Modifica',
					'class':'btn btn-danger delete-button',
					click:function (event, ui) {
            $("#dialog_selfcheck").css("background", "url("+'spinner.gif'+") center no-repeat");
            var email=$('#checkbox_email_self').prop('checked');
            var msgself=$('#msgself').val();
            var new_text_lavorazione = $(this).find("option:selected").text();
            $.ajax({
              data: {'opt':'selfcheck','term':ref,'new_status':new_stato_lavorazione,'new_text':new_text_lavorazione,'email':email,'cust_mail':cust_mail, 'msgself':msgself,'id_anagra':id_anagra},
              type: 'GET',
              url: 'ajax_request.php',
              success: function(output) {
                 //alert('ho passato questi >> ref:'+ref+' new:'+new_stato_lavorazione+' email:'+email+' cust mail:'+cust_mail+' text:'+msgself);
                 //alert(output);
                window.location.replace("./report_booking.php?auxil=VOR");
              }
            });
          }},
        "Non cambiare": function() {
          $(this).dialog("close");
          $(this).dialog("destroy");
				}
			}
		});
		$("#dialog_selfcheck" ).dialog( "open" );
	});

});


function printPdf(urlPrintDoc){
  //alert(urlPrintDoc);
	$(function(){
		$('#framePdf').attr('src',urlPrintDoc);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
		$('#closePdf').on( "click", function() {
			$('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
		});
	});
};
</script>
<div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['title_value'][substr($tipo,0,2).'R']; ?></div>
<?php
$ts->output_navbar();
?>
<form method="GET" id="report_form" class="clean_get">
<!-- inizio div per dialog -->
<div style="display:none" id="dialog_payment" title="Pagamenti">
  <p class="ui-state-highlight" id="payment_des"></p>
    <p><b>Inserisci pagamento manuale:</b></p>
    <p>
    <label>Tipo pagamento:</label>
    <select style="float: right;" name="type" id="type" tabindex="4" class="FacetSelect" >
      <option value="Locazione" > Locazione </option>
      <option value="Caparra_confirmatoria" > Caparra confirmatoria </option>
      <option value="Deposito_cauzionale" > Deposito cauzionale </option>
    </select>
    </p>
    <p>
    <label>Modalità pagamento:</label>
     <select style="float: right;" name="target_account" id="target_account" tabindex="4" class="FacetSelect" >
    <?php
    $masban = $admin_aziend['masban'] * 1000000;
    $casse = substr($admin_aziend['cassa_'], 0, 3);
    $mascas = $casse * 1000000;
    //recupero i conti correnti
    $res = gaz_dbi_dyn_query('*', $gTables['clfoco'], "(codice LIKE '$casse%' AND codice > '$mascas') or (codice LIKE '{$admin_aziend['masban']}%' AND codice > '$masban')", "codice ASC");
    while ($conto = gaz_dbi_fetch_array($res)) {
        echo "<option value=\"{$conto['codice']}-{$conto['descri']}\"> {$conto['codice']}-{$conto['descri']} </option>\n";
    }
    ?>
    </select>
    </p>
    <p>
    <label>ID:</label>
    <input style="float: right;" id="txn_id" name="txn_id" type="text">
    </p><p>
    <label>Importo: </label><?php echo $admin_aziend['curr_name']; ?>
    <input style="float: right;" id="payment_gross" name="payment_gross" type="text">
    </p>
</div>
<div style="display:none" id="dialog_point" title="Punti...">
  <p class="ui-state-highlight" id="point_amount"></p>
  <p class="ui-state-highlight" id="point_mov"></p>
  <p class="ui-state-highlight" style="border-color: red;" id="point_exp"></p>

    <p><b>Attribuisci punti manualmente:</b></p>
    <p>
    <label>Motivo attribuzione:</label>
    <input style="float: right;" id="motive" name="motive" type="text">
    </p>
    <p>
    <label>Punti attribuiti: </label>
    <input style="float: right;" id="points" name="points" type="text">
    </p>
    <div id="feedback_email_point">
      invia email di notifica <input id="checkbox_email_point"  type="checkbox" name="checkbox_email_point" value="0" >
    </div>
</div>
<div class="frame_email panel panel-success" style="display: none; position: fixed; left: 5%; top: 15%; margin-left: 30%;">
  <div class="col-lg-12">
    <div class="col-xs-11"><h4>e-mail</h4></div>
    <div class="col-xs-1"><h4><button type="button" id="close_email"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
  </div>
  <iframe id="frame_email"  style="height: 90%; width: 100%" src=""> </iframe>
</div>
<div class="framePdf panel panel-success" style="display: none; position: fixed; left: 5%; top: 10px">
  <div class="col-lg-12">
    <div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
    <div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
  </div>
  <iframe id="framePdf"  style="height: 100%; width: 100%" src="../../library/images/wait_spinner.html"></iframe>
</div>
<input type="hidden" name="info" value="none" />
<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
      <p><b>prenotazione:</b></p>
      <p>Numero ID:</p>
      <p class="ui-state-highlight" id="idcodice"></p>
      <p>Cliente:</p>
      <p class="ui-state-highlight" id="iddescri"></p>
</div>
<div style="display:none" id="dialog_bookcr" title="Conferma creazione PDF della prenotazione">
      <p><b>prenotazione:</b></p>
      <p>Numero ID:</p>
      <p class="ui-state-highlight" id="idcodice"></p>
      <p>Cliente:</p>
      <p class="ui-state-highlight" id="iddescri"></p>
</div>
<div style="display:none" id="dialog_leasecr" title="Conferma creazione PDF del contratto">
      <p><b>prenotazione:</b></p>
      <p>Numero ID:</p>
      <p class="ui-state-highlight" id="idcodice"></p>
      <p>Cliente:</p>
      <p class="ui-state-highlight" id="iddescri"></p>
</div>
<div style="display:none" id="credit_card" title="Pagamento con carta di credito off-line">
      <p><b>Dati parziali della carta di credito:</b></p>
      numeri iniziali:<p class="ui-state-highlight" id="cc1"></p>
      cvv:<p class="ui-state-highlight" id="cc2"></p>
      intestatario:<p class="ui-state-highlight" id="cc3"></p>
      importo:<p class="ui-state-highlight" id="cc4"></p>
      <p>L'altra parte dei dati è stata inviata via e-mail all'amministratore<P>
      <p><b>I dati memorizzati nel data base devono essere cancellati subito dopo l'utilizzo</b></p>
      <button type="button" class="btn-primary" id="delete_data"><i class="glyphicon glyphicon-fire" style="color: #ff9c9c;"></i> Distruggi dati</button>

</div>
<div style="display:none" id="dialog_stato_lavorazione" title="Cambia lo stato">
      <p><b>prenotazione:</b></p>
      <p class="ui-state-highlight" id="id_status"></p>
      <p class="ui-state-highlight" id="de_status"></p>
      <select name="sel_stato_lavorazione" id="sel_stato_lavorazione">
          <option value="GENERATO">GENERATO</option>
          <option value="PENDING">In attesa di pagamento</option>
          <option value="CONFIRMED">Confermato</option>
          <option value="FROZEN">Congelato, date bloccate</option>
          <option value="ISSUE">Incontrate difficoltà</option>
          <option value="CANCELLED">Annullato</option>
      </select>
      invia email al cliente<input id="checkbox_email"  type="checkbox" name="checkbox_email" value="1" checked="">
</div>
<div style="display:none" id="dialog_check_inout" title="Stato Accettazione">
    <p><b>prenotazione:</b></p>
    <p class="ui-state-highlight" id="id_status_check"></p>
    <p class="ui-state-highlight" id="de_status_check"></p>
    <select name="sel_stato_check" id="sel_stato_check">
        <option value="PENDING">IN ATTESA</option>
        <option value="IN">CHECKED-IN</option>
        <option value="OUT">CHECKED-OUT</option>
    </select>
    <span id="date_stato_check"></span>
    <p><br>Cambia stato il: <input type="text" id="datepicker" ></p>
    <?php if (isset($vacation_url_user) && strlen($vacation_url_user)>4){ ?>
    <div  id="feedback_email">
    invia email richiesta recensione <input id="checkbox_email_inout"  type="checkbox" name="checkbox_email_inout" value="0" >
    </div>
    <?php } ?>
</div>
<div style="display:none" id="dialog_feedback" title="Recensione lasciata dal cliente">
    <p><b>Recensione:</b></p>
    <p class="ui-state-highlight" id="feedback_text"></p>
    <span id="feedback_element"></span><p class="ui-state-highlight" id="feedback_vote"></p>
    <select name="sel_stato_feedback" id="sel_stato_feedback">
        <option value="0">IN ATTESA di approvazione</option>
        <option value="1">APPROVATO</option>
        <option value="2">BLOCCATO</option>
    </select>
</div>
<input type="hidden" name="auxil" value="<?php echo $tipo; ?>">
<div style="display:none" id="dialog" title="<?php echo $script_transl['mail_alert0']; ?>">
    <p id="mail_alert1"><?php echo $script_transl['mail_alert1']; ?></p>
    <div>
        <label id="maillabel" for="mailaddress">all'indirizzo:</label>
        <input type="text"  placeholder="seleziona sotto oppure digita" value="" id="mailaddress" name="mailaddress" maxlength="100" size="30" />
    </div>
    <div id="mailbutt"></div>
    <p class="ui-state-highlight" id="mail_adrs"></p>
    <p id="mail_alert2"><?php echo $script_transl['mail_alert2']; ?></p>
    <p class="ui-state-highlight" id="mail_attc"></p>
</div>
 <div style="display:none" id="dialog_selfcheck" title="Self Web Check-in">
    <p class="ui-state-highlight" id="num_status_self"><b>prenotazione</b></p>
    <p class="ui-state-highlight" id="de_status_self"></p>
    <select name="sel_stato_self" id="sel_stato_self">
        <option value="0">Non attivato</option>
        <option value="1">Da approvare</option>
        <option value="2">Approvato</option>
        <option value="3">Rifiutato</option>
    </select>
    <span id="date_stato_check"></span>
    <p><br>Invia messaggio: <input type="text" name="msgself" id="msgself" value=""></p>
    <?php if (isset($vacation_url_user) && strlen($vacation_url_user)>4){ ?>
    <div  id="selfcheck_email">
    invia email <input id="checkbox_email_self"  type="checkbox" name="checkbox_email_self" value="0" >
    </div>
    <?php } ?>
</div>
<!-- fine div dialog -->

    <div class="box-primary table-responsive">
    <table class="Tlarge table-striped table-bordered table-condensed table-responsive">
        <tr>
            <td class="FacetFieldCaptionTD">
                <?php gaz_flt_disp_int("id_doc", "Numero Prot."); ?>
            </td>
            <td class="FacetFieldCaptionTD">
                <?php gaz_flt_disp_int("numero", "Numero Doc."); ?>
            </td>
            <td class="FacetFieldCaptionTD">
                <?php
                    if ( $tipo=="VOG" ) {
                        ?>
                            <select class="form-control input-sm" onchange="this.form.submit()" name="giorno">
			                <?php
			                   $gg = isset($giorno) ? $giorno : 'All';
			                ?>
			                <option value="" <?php if ($gg=='All') echo "selected"; ?>>Tutti</option>
			                <option value="0" <?php if ($gg=='0') echo "selected"; ?>>Domenica</option>
			                <option value="1" <?php if ($gg=='1') echo "selected"; ?>>Lunedi</option>
			                <option value="2" <?php if ($gg=='2') echo "selected"; ?>>Martedi</option>
			                <option value="3" <?php if ($gg=='3') echo "selected"; ?>>Mercoledi</option>
			                <option value="4" <?php if ($gg=='4') echo "selected"; ?>>Giovedi</option>
			                <option value="5" <?php if ($gg=='5') echo "selected"; ?>>Venerdi</option>
			                <option value="6" <?php if ($gg=='6') echo "selected"; ?>>Sabato</option>
			                </select>
                        <?php
                    } else {
                        gaz_flt_disp_select("anno", "YEAR(datemi) as anno", $gTables["tesbro"], $where_select, "anno DESC");
                    }
                ?>
            </td>

            <td class="FacetFieldCaptionTD">
              <?php
              gaz_flt_disp_select("house_code", "house_code", $gTables["rental_events"]," type = 'ALLOGGIO' ", "house_code DESC");
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
                <?php
                if ($partner_select) {
                    gaz_flt_disp_select("cliente", "clfoco AS cliente, CONCAT(ragso1,' ',ragso2) AS ragso1",
                      $tesbro_e_partners,
                      $where_select,
                            "ragso1 ASC",
                      "ragso1");
                } else {
                    gaz_flt_disp_int("cliente", "Cliente");
                }?>
            </td>
            <td class=FacetFieldCaptionTD>
                <?php
                //gaz_flt_disp_select("destinaz","unita_locale1 AS destinaz",$tesbro_e_destina, $where_select . " AND unita_locale1 IS NOT NULL", "destinaz DESC",  "destinaz");
                ?>
            </td>
            <td class=FacetFieldCaptionTD>
                &nbsp;
            </td>
             <td class=FacetFieldCaptionTD style="text-align: center;">
              <?php
              if ($form['swStatus']=="" OR $form['swStatus']=="Tutti"){
                ?>
                <input type="submit" class="btn btn-sm btn-default" name="inevasi" onClick="chkSubmit();" value="Inevasi">
                <?php
              } else {
                ?>
                <input type="submit" class="btn btn-sm btn-default" name="tutti" onClick="chkSubmit();" value="Tutti" style="text-align: center;">
                <?php
              }
              ?>
              <input type="hidden" name="swStatus" id="preventDuplicate" value="<?php echo $form['swStatus']; ?>">
            </td>
            <td class="FacetFieldCaptionTD">
                <input type="submit" class="btn btn-sm btn-default" name="search" value="<?php echo $script_transl['search']; ?>" tabindex="1">
                <?php $ts->output_order_form(); ?>
            </td>
            <td class="FacetFieldCaptionTD">
                <a class="btn btn-sm btn-default" href="?auxil=<?php echo $tipo; ?>">Reset</a>
            </td>
            <td class="FacetFieldCaptionTD">
                &nbsp;
            </td>
            <td class="FacetFieldCaptionTD">
              <button type = "button">
                <a class="class1" href="stat.php">Statistiche</a>
              </button>
            </td>
             <td class="FacetFieldCaptionTD">
              <a href="tour_mov.php" class="class1">
                  <button type="button">File alloggiati</button>
              </a>
            </td>
        </tr>
        <tr>
            <?php $ts->output_headers(); ?>
        </tr>
        <?php
        $res1hp=gaz_dbi_get_row($gTables['company_config'], 'var', 'enable_lh_print_dialog');
        $enable_lh_print_dialog=(isset($res1hp))?$res1hp['val']:0;
        //recupero le testate in base alle scelte impostate
        $result = gaz_dbi_dyn_query(cols_from($gTables['tesbro'], "*") . ", " .cols_from($gTables['rental_feedbacks'], "id AS id_feedback","text","status AS feed_status") . ", " .
        cols_from($gTables['anagra'],
            "ragso1","ragso2","citspe","custom_field AS anagra_custom_field",
            "e_mail AS base_mail","id","e_mail2 AS base_mail2") . ", " .
        cols_from($gTables["destina"], "unita_locale1").", ".cols_from($gTables["rental_events"], "adult", "child", "start","end","house_code","checked_in_date","checked_out_date","id_tesbro"),
        $tesbro_e_destina." LEFT JOIN ".$gTables['rental_events']." ON  ".$gTables['rental_events'].".id_tesbro = ".$gTables['tesbro'].".id_tes AND ".$gTables['rental_events'].".type = 'ALLOGGIO' LEFT JOIN ".$gTables['rental_feedbacks']." ON  ".$gTables['rental_feedbacks'].".reservation_id = ".$gTables['rental_events'].".id_tesbro" ,
        $ts->where." AND template = 'booking' ", $ts->orderby,
        $ts->getOffset(), $ts->getLimit(),$gTables['rental_events'].".id_tesbro");
        $ctrlprotoc = "";
        while ($r = gaz_dbi_fetch_array($result)) {
            if ($datatesbro = json_decode($r['custom_field'], TRUE)) { // se esiste un json nel custom field della testata

            }
            $r['id_agent']=0;
            $row_artico=gaz_dbi_get_row($gTables['artico'], 'codice', $r['house_code']);
            $artico_custom_field=$row_artico['custom_field'];
            if ($datahouse = json_decode($artico_custom_field, TRUE)) { // se esiste un json nel custom field dell'alloggio
              if (is_array($datahouse['vacation_rental']) && isset($datahouse['vacation_rental']['agent'])){
                $agent = $datahouse['vacation_rental']['agent'];
                if (intval($agent)>0){// se c'è un proprietario/agente
                  $clfoco_agent=gaz_dbi_get_row($gTables['agenti'], 'id_agente', $agent)['id_fornitore'];
                  $res_agent=gaz_dbi_get_row($gTables['clfoco'], 'codice', $clfoco_agent);
                  $r['id_agent']=$res_agent['id_anagra'];
                }
              }
            }
            if (intval($r['id_agente'])>0){// se c'è un tour operator
              $clfoco_agent=gaz_dbi_get_row($gTables['agenti'], 'id_agente', $r['id_agente'])['id_fornitore'];
              $res_tour=gaz_dbi_get_row($gTables['clfoco'], 'codice', $clfoco_agent);
              $r['tour_descri']=$res_tour['descri'];
            }else{
              $r['tour_descri']='';
            }
            $ccoff=0;
            if (isset ($r['anagra_custom_field']) && $data = json_decode($r['anagra_custom_field'], TRUE)) { // se esiste un json nel custom field anagra
              if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['first_ccn']) && strlen($data['vacation_rental']['first_ccn'])>8){
                $ccoff=1;// ci sono dati per pagamento carta di credito off line
              }
              $r['user_points']=(is_array($data['vacation_rental']) && isset($data['vacation_rental']['points']))?intval($data['vacation_rental']['points']):0;
              $date=(isset($data['vacation_rental']['points_date']))?date_create($data['vacation_rental']['points_date']):date_create("2023-09-01");
              date_add($date,date_interval_create_from_date_string(intval($points_expiry)." days"));// aggiungo la durata dei punti
              $r['expiry_points_date']=date_format($date,"d-m-Y");// questa è la data di scadenza
              $r['expired']=0;
              if (strtotime(date_format($date,"Y-m-d")) < strtotime(date("Y-m-d"))){// se i punti sono scaduti
                $r['expired']=1;
              }
            }else{
              $r['user_points']=0;
            }

            if (isset($r['checked_out_date']) && intval($r['checked_out_date'])>0 && strtotime($r['checked_out_date'])){
              $check_inout="OUT";
              $check_icon="log-out";
              $ckdate=date ('d-m-Y H:i', strtotime($r['checked_out_date']));
              if (isset($r['checked_in_date'])){
                $title = "Checked-in ".date ('d-m-Y H:i', strtotime($r['checked_in_date']))." - Checked-out ".date ('d-m-Y H:i', strtotime($r['checked_out_date']));
              }else{
               $title = "" ;
              }
            }elseif (isset($r['checked_in_date']) && intval($r['checked_in_date'])>0 && strtotime($r['checked_in_date'])){
              $check_inout="IN";
              $check_icon="log-in";
              $ckdate=date ('d-m-Y H:i', strtotime($r['checked_in_date']));
              $title = "Checked-in ".date ('d-m-Y H:i', strtotime($r['checked_in_date']));
            }else {
              $check_inout="PENDING";
              $ckdate="";
              $check_icon="unchecked";
              $title = "in attesa di check-in";
            }

            $stato_check_btn = 'btn-default';
            if($check_inout=="IN"){
              $stato_check_btn = 'btn-success';
            }elseif ($check_inout=="OUT"){
              $stato_check_btn = 'btn-info';
            }

            //calcolo il numero di notti
            $interval = date_diff(date_create($r['start']), date_create($r['end']));

            $stato_btn = 'btn-default';
            if ($data = json_decode($r['custom_field'], TRUE)) { // se esiste un json nel custom field della testata
              if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['status'])){
                $r['status'] = $data['vacation_rental']['status'];
              } else {
                 $r['status'] = '';
              }
              $stato_btn_booking ='btn-default';
              $stato_btn_lease ='btn-default';
              if ($r['tipdoc']=='VPR'){
                $what="preventivo";
              }else{
                $what="prenotazione";
              }
              if (!empty($r['e_mail'])){
                $title_booking = 'Invia '.$what.' a: ' . $r['e_mail'];
                $title_lease = 'Invia contratto a: ' . $r['e_mail'];
                $em=$r['e_mail'];
              }else{
                $title_booking = 'Invia '.$what.' a: ' . $r['base_mail'];
                $title_lease = 'Invia contratto a: ' . $r['base_mail'];
                $em=$r['base_mail'];
              }
              if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['BookingSummary_email_inviata'])){
                $r['BookingSummary_email_inviata'] = $data['vacation_rental']['BookingSummary_email_inviata'];
                $stato_btn_booking = 'btn-success';
                $title_booking = "Ultimo invio a ".$em." : ". $r['BookingSummary_email_inviata'];
              }
              if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['Lease_email_inviata'])){
                $r['Lease_email_inviata'] = $data['vacation_rental']['Lease_email_inviata'];
                $stato_btn_lease = 'btn-success';
                $title_lease = "Ultimo invio a ".$em." : ". $r['Lease_email_inviata'];
              }
              if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['BookingQuote_email_inviata'])){
                $r['BookingQuote_email_inviata'] = $data['vacation_rental']['BookingQuote_email_inviata'];
                $stato_btn_booking = 'btn-success';
                $title_booking = "Ultimo invio: ". $r['BookingQuote_email_inviata'];
              }
              if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['self_checkin_status']) && isset($datahouse['vacation_rental']['self_checkin'])){// status: 0=disabled; 1=processing; 2=enabled; 3=issue
                $data['vacation_rental']['self_checkin_status_msg']=(isset($data['vacation_rental']['self_checkin_status_msg']))?$data['vacation_rental']['self_checkin_status_msg']:''; // se ancora non ci sono messaggi evito l'undefined
                if (intval($datahouse['vacation_rental']['self_checkin'])>0){// se è abilitato per l'alloggio
                  if (intval($data['vacation_rental']['self_checkin_status'])==0){
                    $stato_btn_selfcheck = 'btn-light';
                    $title_selfcheck = "self check-in non attivato";
                  }elseif(intval($data['vacation_rental']['self_checkin_status'])==1){
                    $stato_btn_selfcheck = 'btn-info';
                    $title_selfcheck = "self check-in da approvare";
                  }elseif(intval($data['vacation_rental']['self_checkin_status'])==2){
                    $stato_btn_selfcheck = 'btn-success';
                    $title_selfcheck = "self check-in approvato";
                  }elseif(intval($data['vacation_rental']['self_checkin_status'])==3){
                    $stato_btn_selfcheck = 'btn-danger';
                    $title_selfcheck = "self check-in rifiutato";
                  }
                }else{
                  $stato_btn_selfcheck="";
                }
              }else{
                $stato_btn_selfcheck="";
              }
            } else {
              $r['status'] = '';
              $stato_btn_booking ='btn-default';
              $title_booking='Errore manca customfield nella testata';
              $what = 'Errore manca customfield nella testata';
              $title_lease='Errore manca customfield nella testata';
              $stato_btn_lease='';
            }
            $disabled_email_style="style='pointer-events: none;'";
            $disabled_del_style="style='pointer-events: none;'";
            if ($r['status']=='CONFIRMED'){
              $stato_btn = 'btn-success';
              $disabled_email_style="";
            }elseif ($r['status']=='ISSUE'){
              $stato_btn = 'btn-warning';
            }elseif ($r['status']=='PENDING' || $r['status']=='FROZEN'){
              $stato_btn = 'btn-secondary';
            }elseif ($r['status']=='CANCELLED'){
              $stato_btn = 'btn-danger';
              $disabled_del_style="";
            }elseif($r['status']=='QUOTE'){
              $stato_btn = 'btn-danger';
              $disabled_del_style="";
            }
            $feed_stato_btn = 'btn-default';
            if ($r['feed_status']==1){
              $feed_stato_btn = 'btn-success';
            }elseif ($r['feed_status']==2){
              $feed_stato_btn = 'btn-danger';
            }
            $remains_atleastone = false; // Almeno un rigo e' rimasto da evadere.
            $processed_atleastone = false; // Almeno un rigo e' gia' stato evaso.
            $rigbro_result = gaz_dbi_dyn_query('*', $gTables['rigbro'], "id_tes = " . $r['id_tes'] . " AND tiprig <=1 ", 'id_tes DESC');
            while ( $rigbro_r = gaz_dbi_fetch_array($rigbro_result) ) {
                if ( $rigbro_r['tiprig']==1 ) $totale_da_evadere = 1;
                else $totale_da_evadere = $rigbro_r['quanti'];
                $totale_evaso = 0;
                $rigdoc_result = gaz_dbi_dyn_query('*', $gTables['rigdoc'], "id_order=" . $r['id_tes'] . " AND codart='".$rigbro_r['codart']."' AND tiprig <=1 ", 'id_tes DESC');
                while ($rigdoc_r = gaz_dbi_fetch_array($rigdoc_result)) {
                    $totale_evaso += $rigdoc_r['quanti'];
                    $processed_atleastone = true;
                }
                if ( $totale_evaso < $totale_da_evadere ) {
                    $remains_atleastone = true;
                }
            }
            if ( ($form['swStatus']=="Tutti" OR $form['swStatus']=="") OR ($form['swStatus']=="Inevasi" AND  $remains_atleastone == true) ){

              if ($r['tipdoc'] == 'VPR') {
                  $modulo = "stampa_precli.php?id_tes=" . $r['id_tes'];
                  $modifi = "admin_booking.php?Update&id_tes=" . $r['id_tes'];
              }
              if (substr($r['tipdoc'], 1, 1) == 'O') {
                  $modulo = "stampa_ordcli.php?id_tes=" . $r['id_tes'];
                  $modifi = "admin_booking.php?Update&id_tes=" . $r['id_tes'];
              }
              echo "<tr class=\"FacetDataTD\">";

              if ($r['tipdoc']=="VOW"){
                echo "<td><button title=\"Per modificare un ordine web lo si deve prima cancellare da GAzie, modificarlo nell'e-commerce e poi reimportarlo in GAzie\" class=\"btn btn-xs btn-default disabled\">&nbsp;" . substr($r['tipdoc'], 1, 2) . "&nbsp;" . $r['id_tes'] . " </button></td>";
              }elseif (!empty($modifi)) {
                  echo "<td><a class=\"btn btn-xs btn-edit\" title=\"" . $script_transl['type_value'][$r['tipdoc']] . "\" href=\"" . $modifi . "\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;" . substr($r['tipdoc'], 1, 2) . "&nbsp;" . $r['id_tes'] . "</a></td>";
              } else {
                  echo "<td><button class=\"btn btn-xs btn-default disabled\">&nbsp;" . substr($r['tipdoc'], 1, 2) . "&nbsp;" . $r['id_tes'] . " </button></td>";
              }
              echo "<td>" . $r['numdoc'] . " &nbsp;</td>";
              if ( $tipo=="VOG" ) {
                  echo "<td>". getDayNameFromDayNumber($r['weekday_repeat']). " &nbsp;</td>";
              } else {
                  echo "<td>" . gaz_format_date($r['datemi']) . " &nbsp;</td>";
              }
              echo "<td>" . $r['house_code'] . " &nbsp;</td>";
              echo "<td>" . gaz_format_date($r['start']) . " &nbsp;</td>";
              echo "<td>" . gaz_format_date($r['end']) . " &nbsp;</td>";
              echo "<td>" . $interval->days ."</td>";
              echo "<td> adulti:".$r['adult'];
              if (intval($r['child'])>0){
                echo "<br>minori:".$r['child'];
              }
              echo "</td><td>" . $r['tour_descri'] . "</td>";
              // Colonna cliente
              echo "<td><a title=\"Dettagli cliente\" href=\"../vendit/report_client.php?nome=" . $r['ragso1'] . "\">". $r['ragso1'] ." ".  $r['ragso2'] ."</a> &nbsp;";
              if ($r['user_points']>0 && intval($pointenable)==1){
                $stato_gift_btn = ($r['expired']==1)?'btn-danger':'btn-default';
                $pointlevelname[0]="Nessuno";$lev=0;
                for($xl=1; $xl<=3; $xl++){
                 if ($r['user_points']>=$pointlevel[$xl]){
                   $lev=$xl;
                 }
                }
                echo "&nbsp;&nbsp;<a class=\"btn btn-xs ",$stato_gift_btn," \"";
                echo " style=\"cursor:pointer;\" onclick=\"point('". $r['id'] ."','".$r['user_points']."','".addslashes($r['ragso1'])." ".addslashes($r['ragso2'])."','".$r['id_tes']."','".$r['expired']."','".$r['expiry_points_date']."')\"";
                echo ">".$pointlevelname[$lev]." <i class=\"glyphicon glyphicon-gift\" title=\"Punti: ".$r['user_points']." - Scadenza: ".$r['expiry_points_date']."\"></i></a></td>";
              }
              echo "<td>".$r['citspe']."</td>";

              // Colonna importo
              $amount=get_totalprice_booking($r['id_tes'],TRUE,FALSE,"",TRUE);
              $amountvat=get_totalprice_booking($r['id_tes'],TRUE,TRUE,$admin_aziend['preeminent_vat'],TRUE);
              $amountvat_secdep=get_totalprice_booking($r['id_tes'],TRUE,TRUE,$admin_aziend['preeminent_vat'],TRUE,TRUE);

              echo "<td class='text-right' style='min-width: 150px;'>","imp. € ".gaz_format_quantity($amount,1,2),"";
              echo "<br>","iva c. € ".gaz_format_quantity($amountvat,1,2),"";
              if (($amountvat_secdep-$amountvat)>0){
                echo "<br>","Dep.cauz. € ".gaz_format_quantity($amountvat_secdep-$amountvat,1,2),"";
              }
              if ( $tipo !== "VPR" ) {
                $paid=get_total_paid($r['id_tes']);
                $secdep_paid=get_secdep_paid($r['id_tes']);

                $stato_pig_btn = ($paid>0)?'btn-warning':'btn-default';
                $stato_pig_btn = ($paid>=gaz_format_quantity($amountvat,0,2))?'btn-success':$stato_pig_btn;
                $addtext=($paid>0)?"&nbsp;Pagato ".gaz_format_quantity($paid,1,2):"";
                echo "<br><a id=\"atest",$r['id_tes'],"\" class=\"btn btn-xs btn-default ",$stato_pig_btn,"\"";
                echo " style=\"cursor:pointer;\" onclick=\"payment('". $r['id_tes'] ."')\"";
                $balance=gaz_format_quantity(($amountvat-$paid),1,2);
                $addtitle="";
                if (floatval($balance)>0){
                  $addtitle="- ancora da pagare € ".$balance;
                }
                echo "><i id=\"test",$r['id_tes'],"\" class=\"glyphicon glyphicon-piggy-bank \" title=\"Pagamenti",$addtitle,"\">",$addtext,"</i></a>";
               if (file_exists("Stripe/integrated_pos.php")){
                echo '<a style="padding:10px;" class="glyphicon glyphicon-credit-card" href="Stripe/integrated_pos.php?deposit=',$amountvat-$paid,'&idtes=', $r['id_tes'],'&lang=','it','&house=',$r['house_code'],'&itemName=',$script_transl['booking'],' n.',$r['numdoc'],' ',gaz_format_date($r['datemi']),'" title="virtual-POS"</a>';
               }
                if (floatval($secdep_paid)>0){
                  $addtitle="- pagato € ".$secdep_paid;
                  $addtext=($secdep_paid>0)?"&nbsp;Deposito ".gaz_format_quantity($secdep_paid,1,2):"";
                  echo "<br/><i id=\"secdep",$r['id_tes'],"\" style='cursor: default;' class=\"btn btn-xs btn-default glyphicon glyphicon-piggy-bank \" title=\"Deposito cauzionale",$addtitle,"\">",$addtext,"</i></a>";
                }
              }
              echo"</td>";


              // colonna fiscale
              if ( $tipo !== "VPR" ){//  se non è preventivo
                echo "<td style='text-align: left;'>";
				if ( intval($agent)==0){// se non c'è un proprietario/agente 
					if ($remains_atleastone && !$processed_atleastone && $r['status']!=='CANCELLED' && $r['status']!=='ISSUE') {
						// L'ordine e'  da evadere.
					  if ( $tipo !== "VOG" && $tipo !== "VPR") {
						echo "<a class=\"btn btn-xs btn-warning\" href=\"../../modules/vendit/select_evaord.php?id_tes=" . $r['id_tes'] . "\">Emetti documento fiscale</a>&nbsp;";
					  }
					}elseif ($remains_atleastone && $r['status']!=='CANCELLED' && $r['status']!=='ISSUE') {
						  // l'a prenotazione è parzialmente evaso, mostro lista documenti e tasto per evadere rimanenze
						  $ultimo_documento = 0;
						  mostra_documenti_associati( $r['id_tes'], $paid );
						  if ( $tipo == "VOG" ) {
							  echo "<a class=\"btn btn-xs btn-default\" href=\"../../modules/vendit/select_evaord_gio.php\">evadi il rimanente</a>";
						  } else {
							  echo "<a class=\"btn btn-xs btn-warning\" href=\"../../modules/vendit/select_evaord.php?id_tes=" . $r['id_tes'] . "\">evadi il rimanente</a>&nbsp;";
							  echo "<a class=\"btn btn-xs btn-warning\" href=\"../../modules/vendit/select_evaord.php?clfoco=" . $r['clfoco'] . "\">evadi cliente</a>";
						  }
					} else {
					  // la prenotazione è completamente evasa, mostro i riferimenti ai documenti che l'hanno evasa
					  $ultimo_documento = 0;
					  mostra_documenti_associati( $r['id_tes'], $paid );
					}
				}
                  if ($r['status']=='CONFIRMED'){                         
					  ?>
					  <a style="float:right;" title="Genera pdf contratto" class="btn btn-xs dialog_leasecr" ref="<?php echo $r['id_tes']; ?>" nome="<?php echo $r['ragso1']; ?>" url=<?php echo "stampa_contratto.php?id_tes=". $r['id_tes'] . "&id_ag=". $r['id_agent']; ?>>
						<i class="glyphicon glyphicon-refresh"></i>
					  </a>
					  <?php
				  }
                 echo "</td>";
              }elseif(isset($datatesbro['vacation_rental']['id_booking']) && intval($datatesbro['vacation_rental']['id_booking'])>0){
                echo "<td><a class=\"btn btn-xs btn-warning\" href=\"../../modules/vacation_rental/report_booking.php?info=none&auxil=VOR&id_doc=" . intval($datatesbro['vacation_rental']['id_booking']) . "\">Prenotazione effettuata</a></td>";
              }else{
                echo "<td></td>";
              }
              // colonna stato prenotazione
              // Se la prenotazione e' da evadere , verifica lo status ed eventualmente lo aggiorna.
              echo "<td style='text-align: left;'>";
                if(isset($datatesbro['vacation_rental']['man_checkin_status']) && intval($datatesbro['vacation_rental']['man_checkin_status'])==1){
                  $class_man="btn btn-success";$title_man="title = 'Accettazione effettuata'";
                }else{
                  $class_man="";$title_man="title = 'Accettazione NON effettuata'";
                }
                  if ( $tipo == "VOG" ) {
                      echo "<a class=\"btn btn-xs btn-warning\" href=\"select_evaord_gio.php?weekday=".$r['weekday_repeat']."\">evadi</a>";
                  } elseif ( $tipo == "VPR" ) {
                    echo "PREVENTIVO";
                  } else {
                      if ($ccoff==1 ){// se ci sono dati per il pagamento con carta di credito off line
                        echo "&nbsp;&nbsp;<a class=\"btn btn-xs btn-default \"";
                        echo " style=\"cursor:pointer;\" onclick=\"pay('". $r['id'] ."')\"";
                        echo "><i class=\"glyphicon glyphicon-credit-card\" title=\"Carta di credito\"></i></a>";
                      }
                      ?><br><a style="white-space:nowrap;" title="Stato della prenotazione" class="btn btn-xs <?php echo $stato_btn; ?> dialog_stato_lavorazione" refsta="<?php echo $r['id_tes']; ?>" prodes="<?php echo $r['ragso1']," ",$r['ragso2']; ?>" prosta="<?php echo $r['status']; ?>" cust_mail="<?php echo $r['base_mail']; ?>" cust_mail2="<?php echo $r['base_mail2']; ?>">
                          <i class="glyphicon glyphicon-modal-window">&nbsp;</i><?php echo $r['status']; ?>
                        </a>
                        <?php
                        if ($r['status']=='CONFIRMED'){
                          ?>
                          <br><a style="white-space:nowrap;" title="Accettazione: <?php echo $title; ?>" class="btn btn-xs <?php echo $stato_check_btn; ?> dialog_check_inout" refcheck="<?php echo $r['id_tes']; ?>" prodes="<?php echo $r['ragso1']," ",$r['ragso2']; ?>" prostacheck="<?php echo $check_inout; ?>" cust_mail="<?php echo $r['base_mail']; ?>" cust_mail2="<?php echo $r['base_mail2']; ?>" ckdate="<?php echo $ckdate; ?>">
                            <i class="glyphicon glyphicon-<?php echo $check_icon; ?>">&nbsp;</i><?php echo "CHECK ",$check_inout; ?>
                          </a>
                          <button type = "button">
                            <a class="class1 <?php echo $class_man; ?>" href="MANUAL_checkin.php?tes=<?php echo $r['id_tes']; ?>" <?php echo $title_man; ?> onclick="checkFileExistence(event); return false;">accettazione</a>
                          </button>
                          <?php
                        }
                        if ($stato_btn_selfcheck!==""){// se c'è il self checkin inserisco icona
                         ?>
                        <a title="<?php echo $title_selfcheck; ?>" class="btn btn-xs <?php echo $stato_btn_selfcheck; ?> dialog_selfcheck" ref="<?php echo $r['id_tes']; ?>" status_now="<?php echo $title_selfcheck; ?>" proself="<?php echo $data['vacation_rental']['self_checkin_status']; ?>" cust_mail="<?php echo $r['base_mail']; ?>" numdoc="<?php echo $r['numdoc']; ?>" id_anagra="<?php echo $r['id']; ?>" msgself="<?php echo $data['vacation_rental']['self_checkin_status_msg']; ?>">
                          <i class="glyphicon glyphicon-ok-circle"></i>
                        </a>
                        <?php
                        }

                        if (isset($r['text'])){// se c'è una recensione inserisco icona
                         ?>
                        <a title="Recensione" class="btn btn-xs <?php echo $feed_stato_btn; ?> dialog_feedback" ref="<?php echo $r['id_feedback']; ?>" feed_text="<?php echo $r['text']; ?>" feed_status="<?php echo $r['feed_status']; ?>">
                          <i class="glyphicon glyphicon-comment"></i>
                        </a>
                        <?php
                        }
                  }

              echo "</td>";

              // stampa
              echo "<td align=\"center\">";
              echo "<a class=\"btn btn-xs btn-default btn-stampa\"";
              // vedo se è presente un file di template adatto alla stampa su carta già intestata
              if($enable_lh_print_dialog>0 && withoutLetterHeadTemplate($r['tipdoc'])){
                echo ' onclick="choice_template(\''.$modulo.'\');" title="Stampa" '.$what;
              }else{
                echo " style=\"cursor:pointer;\" onclick=\"printPdf('".$modulo."')\"";
              }
              echo "><i class=\"glyphicon glyphicon-print\" title=\"Stampa ".$what." PDF\"></i></a>";
              $PDFurl = (dirname(__DIR__, 2).'/data/' . 'files/' . $admin_aziend['company_id'] .'/pdf_Lease/'.$r['id_tes'].'.pdf');
              if ( $tipo !== "VPR" && file_exists($PDFurl)) {

                  echo "&nbsp;<a class=\"btn btn-xs btn-default btn-stampa\"";
                  // vedo se è presente un file di template adatto alla stampa su carta già intestata
                  if($enable_lh_print_dialog>0 && withoutLetterHeadTemplate($r['tipdoc'])){
                    echo ' onclick="choice_template(\''.$modulo.'\');" title="Stampa contratto"';
                  }else{
                    echo " style=\"cursor:pointer;\" onclick=\"printPdf('stampa_contratto.php?id_tes=". $r['id_tes'] . "&id_ag=". $r['id_agent'] ."')\"";
                  }
                  echo "><i class=\"glyphicon glyphicon-book\" title=\"Stampa contratto PDF\"></i></a>";

              }
              echo "</td>";

              // Colonna "Mail"
              echo "<td align=\"center\">";
			  //print_r($r);
              if (!empty($r['e_mail'])){ // ho una mail sulla destinazione
                  echo '<a class="btn btn-xs btn-email '.$stato_btn_booking.'" onclick="confirMail(this, '. $r['clfoco'] .', '. $r['e_mail'] .');return false;" id="doc' . $r['id_tes'] . '" url="' . $modulo . '" href="#" title="' .$title_booking . '"
                  mail="' . $r['e_mail'] . '" namedoc="' . $script_transl['type_value'][$r['tipdoc']] . ' n.' . $r['numdoc'] . ' del ' . gaz_format_date($r['datemi']) . '"><i class="glyphicon glyphicon-envelope"></i></a>';
                  if ( $tipo !== "VPR" && file_exists($PDFurl) ) {
                    echo ' <a class="btn btn-xs btn-emailC '.$stato_btn_lease.'" ',$disabled_email_style,' onclick="confirMailC(this);return false;" id="docC' . $r['id_tes'] . '" urlC="stampa_contratto.php?id_tes='. $r['id_tes']. '&dest=E&id_ag='.$r['id_agent'].'" href="#" title="' . $title_lease . '"
                    mail="' . $r['e_mail'] . '" namedoc="' . $script_transl['type_value'][$r['tipdoc']] . ' n.' . $r['numdoc'] . ' del ' . gaz_format_date($r['datemi']) . '"><i class="glyphicon glyphicon-send"></i></a>';
                  }
              } elseif (!empty($r['base_mail'])) { // ho una mail sul cliente
                  echo ' <a class="btn btn-xs btn-email '.$stato_btn_booking.'" onclick="confirMail(this, '. $r['clfoco'] .', \''. $r['base_mail'] .'\');return false;" id="doc' . $r['id_tes'] . '" url="' . $modulo . '" href="#" title="' .$title_booking . '"
                  mail="' . $r['base_mail'] . '" namedoc="' . $script_transl['type_value'][$r['tipdoc']] . ' n.' . $r['numdoc'] . ' del ' . gaz_format_date($r['datemi']) . '"><i class="glyphicon glyphicon-envelope"></i></a>';
                  if ( $tipo !== "VPR" && file_exists($PDFurl)) {
                    echo ' <a class="btn btn-xs btn-emailC '.$stato_btn_lease.'" ',$disabled_email_style,' onclick="confirMailC(this);return false;" id="docC' . $r['id_tes'] . '" urlC="stampa_contratto.php?id_tes='. $r['id_tes']. '&dest=E&id_ag='.$r['id_agent'].'" href="#" title="' . $title_lease . '"
                    mail="' . $r['base_mail'] . '" namedoc="Contratto n.' . $r['numdoc'] . ' del ' . gaz_format_date($r['datemi']) . '"><i class="glyphicon glyphicon-send"></i></a>';
                  }
              } else { // non ho mail
                  echo '<a title="Non hai memorizzato l\'email per questo cliente, inseriscila ora" href="../../modules/vendit/admin_client.php?codice=' . substr($r['clfoco'], 3) . '&Update"><i class="glyphicon glyphicon-edit"></i></a>';
              }
              echo "</td>";

              echo "<td align=\"center\"><a class=\"btn btn-xs btn-default btn-duplica\" disabled = \"disabled\" title=\"al momento non attivo\" href=\"duplicate_booking.php?id_tes=" . $r['id_tes'] . "\"><i class=\"glyphicon glyphicon-duplicate\"></i></a>";
              echo "</td>";

              echo "<td align=\"center\">";
              if (!$remains_atleastone || !$processed_atleastone) {
                  //possono essere cancellati solo gli ordini inevasi o completamente evasi
                ?>
                <a class="btn btn-xs  btn-elimina dialog_delete " <?php echo $disabled_del_style; ?> title="Cancella il documento" ref="<?php echo $r['id_tes'];?>" nome="<?php echo $r['ragso1']; ?>">
                  <i class="glyphicon glyphicon-remove"></i>
                </a>
                <?php
              }
              echo "</td>";
              echo "</tr>\n";
            }
        }
        ?>
        <tr><th class="FacetFieldCaptionTD" colspan="10"></th></tr>
    </table>
    </div>
	<div class="modal" id="confirm_print" title="Scegli la carta dove stampare"></div>
</form>
<a href="https://programmisitiweb.lacasettabio.it/gazie/vacation-rental-il-gestionale-per-case-vacanza-residence-bb-e-agriturismi/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:10%; z-index:2000;"> Vacation rental è un modulo di Antonio Germani</a>

<?php
if (isset($_SESSION['print_queue']['idDoc']) && !empty($_SESSION['print_queue']['idDoc'])) {
	$printIdDoc =  (int) $_SESSION['print_queue']['idDoc'];
	if (isset($_SESSION['print_queue']['tpDoc'])) {
		$target = "stampa_precli.php?id_tes=$printIdDoc";
		if ($_SESSION['print_queue']['tpDoc'] == 'VOR') {
			$target = "stampa_ordcli.php?id_tes=$printIdDoc";
		}
?>
<script>
  $(document).ready(function() {

    fileLoad('<?php echo $target;?>', false);

    $('.button').click(function() {
      $.ajax({
        type: "POST",
        url: "some.php",
        data: { name: "John" }
      }).done(function( msg ) {
        alert( "Data Saved: " + msg );
      });
    });


  });
</script>
<?php
  }
	unset($_SESSION['print_queue']);
}
?>

<script src="../../js/custom/fix_select.js" type="text/javascript"></script>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>

<?php
function withoutLetterHeadTemplate($tipdoc='VPR')
{
	$withoutLetterHeadTemplate=false;
	$nf="preventivo_cliente";
	if ($tipdoc=='VOR') $nf="ordine_cliente";
	$configTemplate = new configTemplate;
	$handle = opendir("../../config/templates".($configTemplate->template ? '.' . $configTemplate->template : ''));
	while ($file = readdir($handle)) {
		if(($file == ".")||($file == "..")) continue;
		if(!preg_match("/^".$nf."_lh.php$/",$file)) continue; // se è presente un template adatto per stampa su carta intestata (suffisso "_lh" )
		$withoutLetterHeadTemplate = true; //
	}
	return $withoutLetterHeadTemplate;
}
?>
<script>
function checkFileExistence(event) {// avviso della necessità della versione pro
    event.preventDefault();  // Prevenire il comportamento di default (navigazione del link)
    var link = event.target;
    var fileUrl = link.href; // L'URL del link che include i parametri
    // Verifica se il file esiste con una richiesta HEAD
    fetch(fileUrl, { method: 'HEAD' })
    .then(response => {
        if (response.ok) {
            // Se il file esiste, reindirizza l'utente al file mantenendo i parametri
            console.log('File trovato, reindirizzo...');
            window.location.href = fileUrl;  // Reindirizza al file mantenendo i parametri
        } else {
            // Se il file non esiste, mostra un messaggio
            alert('ATTENZIONE: questa funzione è presente solo nella versione PRO. Contatta lo sviluppatore.');
        }
    })
    .catch(() => {
        // In caso di errore nella richiesta
        alert('Errore nella verifica del file.');
    });
}
</script>
