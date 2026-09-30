<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	 VACATION RENTAL è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
require_once("../../library/include/datlib.inc.php");
require("../../modules/vacation_rental/lib.function.php");
require("../../modules/vacation_rental/lib.data.php");
if (!isset($_POST['access'])){// primo accesso
  $form['start']=date("Y-m-d");
  $form['end']=date('Y-m-d', strtotime($form['start'] . ' +10 day'));
	$stat_start=date("Y") . "-01-01";
	$stat_end=date("Y") . "-12-31";
}else{
  $form['start']=$stat_start=$_POST['start'];
  $form['end']=$stat_end=$_POST['end'];
}
$checkimp=(isset($_POST['set']) && $_POST['set']=="IMPORTI")?"checked":'';
if ((isset($_POST['set']) && $_POST['set']=="IMPORTI") ){// se selezionato
  $checkimp="checked";
}elseif(!isset($_POST['set'])){ // di default
  $checkocc="checked";
  $_POST['set']="OCCUPAZIONE";
}else{

  $checkimp="";
}
$checkocc=(isset($_POST['set']) && $_POST['set']=="OCCUPAZIONE")?"checked":'';
?>
<script>
$('#closePdf').on( "click", function() {
		$('.framePdf').css({'display': 'none'});
		$('#framePdf').attr('src','../../library/images/wait_spinner.html');
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
      $('#framePdf').css({'height': '90%'});
      $('.framePdf').css({'display': 'block','width': '90%', 'height': '90%', 'z-index':'2000'});

    });
  }else{
    alert('Il file richiesto fa parte della versione PRO di questo modulo: contattare lo sviluppatore');
  };
	$('#closePdf').on( "click", function() {
		$("#titolo").empty();
		$('.framePdf').css({'display': 'none'});
		$('#framePdf').attr('src','../../library/images/wait_spinner.html');
	});
};
</script>
<style>
@keyframes pulse-danger {
    0% { transform: scale(0.98); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.5); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
    100% { transform: scale(0.98); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}
.pulsante-allerta {
    color: #dc3545 !important;
    border-color: #dc3545 !important;
    animation: pulse-danger 2s infinite;
}
</style>

  <div id="generale" class="tab-pane fade in ">
    <form method="post" id="sbmt-form" enctype="multipart/form-data">
    	<div class="framePdf panel panel-success" style="display: none; position: fixed; left: 5%; top: 5px; height: 90%;">
          <div class="col-lg-12">
            <div class="col-xs-11" id="titolo" ></div>
            <div class="col-xs-1"><span><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></span></div>
          </div>
		  <iframe id="framePdf"  style="height: 100%; width: 100%" src="../../library/images/wait_spinner.html"></iframe>
		</div>
		<!-- Struttura della Finestra Modale per selezione pagamenti sospesi -->
		<div class="modal fade" id="modalSospesi" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="myModalLabel"><i class="glyphicon glyphicon-transfer"></i> Gestione Bonifici Sospesi</h4>
					</div>
					<div class="modal-body" id="corpo-modal-sospesi">
						<!-- La tabella dei sospesi verrà iniettata qui dinamicamente da JavaScript -->
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
					</div>
				</div>
			</div>
		</div>

      <div class="panel panel-info col-sm-12">
        <div class="box-header company-color">
          <h4 class="box-title"><i class="glyphicon glyphicon-blackboard"></i> Riepilogo Vacation rental</h4>
          <a class="pull-left" style="cursor:pointer; margin-right:10%;" onclick="openframe('../../modules/vacation_rental/MyCalendar.php?price','<h3>Calendario generale</h3>')" data-toggle="modal" data-target="#iframe"> <i class="glyphicon glyphicon-calendar" title="Calendario della disponibilità"></i></a>
          <a class="pull-left" style="cursor:pointer;" onclick="openframe('../../modules/vacation_rental/dashboard_Android.php','<h3>Dashboard Android</h3>')" data-toggle="modal" data-target="#iframe"> <span title="Dashboard Android">📱</span></a>&nbsp;&nbsp;&nbsp;


          <a class="pull-left" style="cursor:pointer; margin-left:10px;" onclick="openframe('../../modules/vacation_rental/Dashboard_User_Tracking.php','<h3>Dashboard Users Tracking</h3>')" data-toggle="modal" data-target="#iframe"> <span title="Dashboard Users Tracking">📈</span></a>
			
			<a class="pull-center" style="cursor:pointer; margin-right:20px;" onclick="openframe('../../modules/vacation_rental/admin_chat.php','<h3>APP CHAT</h3>')" data-toggle="modal" data-target="#iframe"> <span title="Chat in App Android">💬</span></a>

          <a class="pull-center" href="../vacation_rental/report_booking.php" style="cursor:pointer;"><i class="glyphicon glyphicon-tasks" title="vai alle prenotazioni"></i></a>
		  <a class="pull-right dialog_grid" id_bread="<?php echo $grr['id_bread']; ?>" style="cursor:pointer;"><i class="glyphicon glyphicon-cog"></i></a>
        </div>
		<div id="container-widget-sospesi" style="display: inline-block; margin: 10px;">
			<button type="button" class="btn btn-outline-secondary" id="btn-sospesi" onclick="caricaEMostraPopUp()" disabled>
				<i class="glyphicon glyphicon-transfer"></i> Bonifici Sospesi
				<span id="badge-conteggio" class="badge" style="display:none;">0</span>
			</button>
		</div>
        <div class="box-body">

			<div class="box-body" style="border: solid 3px blue;">
				<table class="Tlarge table table-striped table-bordered table-condensed">
				<tr>
				  <td class="FacetFieldCaptionTD text-right">Occupazione periodo</td>
				  <td class="FacetDataTD">
					dal <input type="date" name="start" value="<?php echo $form['start']; ?>" class="FacetInput" onchange="this.form.submit()">
				  </td>
				  <td class="FacetDataTD">
					al <input type="date" name="end" value="<?php echo $form['end']; ?>" class="FacetInput" onchange="this.form.submit()">
					<input type="hidden" value="access" maxlength="6" name="access">
				  </td>
				</tr>
				</table>
				<?php
				// prendo i dati statistici
				$tot_promemo = get_total_promemo($form['start'],$form['end']);
				// prendo i check-in nei prossimi 7 giorni
				$next_check = get_next_check(date("Y-m-d"),date('Y-m-d', strtotime(date("Y-m-d") . ' + 10 day')));
				?>
				<div class="table-responsive table-bordered table-striped">
				<table class="col-xs-12">
					<tr>
					  <th class="text-center">Importo totale imponibile</th>
					  <th class="text-center">Notti periodo</th>
					  <th class="text-center">Notti vendute</th>
					  <th class="text-center">Occupazione</th>
					</tr>
					<tr>
					  <td class="text-center"><?php echo "€ ",number_format($tot_promemo['totalprice_booking'], 2, '.', '')," solo locazioni"; ?></td>
					  <td class="text-center"><?php echo $tot_promemo['tot_nights_bookable']; ?></td>
					  <td class="text-center"><?php echo $tot_promemo['tot_nights_booked']; ?></td>
					  <td class="text-center"><?php echo number_format($tot_promemo['perc_booked'], 2, '.', ''),"%"; ?></td>
					</tr>
				</table>
				</div>
			</div>
          <div class="box-body">
            <table class="Tlarge table table-striped table-bordered table-condensed">
              <h5 class="box-title"><i class="glyphicon glyphicon-pushpin"></i> Nei prossimi 10 giorni </h5>
              <?php
              if (count($next_check['in']) >0){
                $keys = array_column($next_check['in'], 'start');
                array_multisort($keys, SORT_ASC, $next_check['in']);// ordino per start
                ?>

                <table class="Tlarge table table-striped table-bordered text-left">
                  <tr>
                    <th class="text-center"><i class="glyphicon glyphicon-log-in"></i>&nbsp;&nbsp;<?php echo "Check-in"; ?></th>

                  </tr>
                  <?php
                  foreach($next_check['in'] as $next_row){

                    $table = $gTables['rental_events'] ." LEFT JOIN ". $gTables['tesbro'] ." ON ". $gTables['tesbro'] .".id_tes = " . $gTables['rental_events'] . ".id_tesbro LEFT JOIN ". $gTables['clfoco'] ." ON ". $gTables['clfoco'] .".codice = " . $gTables['tesbro'] . ".clfoco LEFT JOIN ". $gTables['anagra'] ." ON ". $gTables['anagra'] .".id = " . $gTables['clfoco'] . ".id_anagra";
                    $where = $gTables['rental_events'].".id = '".$next_row['id']."'";
                    $what = $gTables['rental_events'] .".*, ". $gTables['anagra'] . ".ragso1, ".	$gTables['anagra'] .".ragso2, ". 	$gTables['tesbro'] . ".numdoc, ".	$gTables['tesbro'] . ".datemi, ". $gTables['tesbro'] .".id_tes";
                    $result = gaz_dbi_dyn_query($what, $table, $where, "start DESC");
                    $row=gaz_dbi_fetch_array($result);
                    if (isset($row)){
                      $style="";
                      if (date("Y-m-d")==$row['start']){
                        $style="style='background-color: #f2caca;'";
                      }
					  if (intval($row['checked_in_date'])==0){
						  ?>
						  <tr <?php echo $style; ?>>
						  <td><?php echo "<b>",gaz_format_date($row['start']),"</b> ",$row['type']," ",$row['house_code'],"<b> -> </b>",$row['ragso1']," ",$row['ragso2']; ?>
						  <a href="../vacation_rental/report_booking.php?info=none&id_doc=<?php echo $row['id_tes']; ?>"> prenotazione n. <?php echo $row['numdoc']; ?> del <?php echo gaz_format_date($row['datemi']); ?></a></td>
						  </tr>
						  <?php
					  }
                    }
                  }
                  ?>
                </table>

                <?php
              }
              if (count($next_check['out']) >0){
                $keys = array_column($next_check['out'], 'end');
                array_multisort($keys, SORT_ASC, $next_check['out']);// ordino per end
                ?>

                <table class="Tlarge table table-striped table-bordered text-left">
                  <tr>
                    <th class="text-center"><i class="glyphicon glyphicon-log-out"></i>&nbsp;&nbsp;<?php echo "Check-out"; ?></th>
                  </tr>
                  <?php
                  foreach($next_check['out'] as $next_row){
                    $table = $gTables['rental_events'] ." LEFT JOIN ". $gTables['tesbro'] ." ON ". $gTables['tesbro'] .".id_tes = " . $gTables['rental_events'] . ".id_tesbro LEFT JOIN ". $gTables['clfoco'] ." ON ". $gTables['clfoco'] .".codice = " . $gTables['tesbro'] . ".clfoco LEFT JOIN ". $gTables['anagra'] ." ON ". $gTables['anagra'] .".id = " . $gTables['clfoco'] . ".id_anagra";
                    $where = $gTables['rental_events'].".id = '".$next_row['id']."'";
                    $what = $gTables['rental_events'] .".*, ". $gTables['anagra'] . ".ragso1, ".	$gTables['anagra'] .".ragso2, ". 	$gTables['tesbro'] . ".numdoc, ".	$gTables['tesbro'] . ".datemi, ". $gTables['tesbro'] .".id_tes";
                    $result = gaz_dbi_dyn_query($what, $table, $where, "end DESC");
                    $row=gaz_dbi_fetch_array($result);
                    if (isset($row)){
                      $style="";
                      if (date("Y-m-d")==$row['end']){
                        $style="style='background-color: #f2caca;'";
                      }
					  if (intval($row['checked_out_date'])==0){
						  ?>
						  <tr <?php echo $style; ?>>
						  <td><?php echo "<b>",gaz_format_date($row['end']),"</b> ",$row['type']," ",$row['house_code'],"<b> -> </b>",$row['ragso1']," ",$row['ragso2']; ?>
						  <a href="../vacation_rental/report_booking.php?info=none&id_doc=<?php echo $row['id_tes']; ?>"> prenotazione n. <?php echo $row['numdoc']; ?> del <?php echo gaz_format_date($row['datemi']); ?></a></td>
						  </tr>
						  <?php
					  }
                    }
                  }
                  ?>
                </table>

                <?php
              }
              ?>

            </table>
          </div>

        </div>


      </div>
    </form>
  </div>
  <?php if(file_exists("../../modules/vacation_rental/flot_graph.php")){?>
  <div>
  <input type="radio" name="set" onchange="this.form.submit();" value="OCCUPAZIONE" <?php echo $checkocc; ?>>Occupazione
  <input type="radio" name="set" onchange="this.form.submit();" value="IMPORTI" <?php echo $checkimp; ?>>Importi
   <iframe src="../../modules/vacation_rental/flot_graph.php?start=<?php echo $stat_start;?>&end=<?php echo $stat_end;?>&set=<?php echo $_POST['set'];?>" width="100%" height="800px" title="Grafico statistiche"></iframe>
  </div>
  <?php }else{
    echo "<br>Il grafico interattivo delle statische non è dispobile in questa versione";
  }

  ?>
<script>
// Funzione automatica per controllare se ci sono sospesi senza rinfrescare la pagina
function controllaSospesi() {
    console.log("   [DEBUG AJAX] Avvio controllo sospesi...");
    
    $.ajax({
        url: '/modules/vacation_rental/api_sospesi.php?azione=count',
        method: 'GET',
        dataType: 'json',
        success: function(risposta) {
            console.log("   [DEBUG AJAX] Risposta ricevuta con successo:", risposta);
            
            var btn = $('#btn-sospesi');
            var badge = $('#badge-conteggio');
            
            if (risposta.status === 'success' && risposta.count > 0) {
                console.log("   [DEBUG AJAX] Sospesi trovati! Attivo il widget. Conteggio: " + risposta.count);
                btn.addClass('pulsante-allerta').prop('disabled', false);
                badge.text(risposta.count).show();
            } else {
                console.log("   [DEBUG AJAX] Risposta OK ma nessun sospeso attivo (count = 0).");
                btn.removeClass('pulsante-allerta').prop('disabled', true);
                badge.hide();
            }
        },
        error: function(xhr, status, error) {
            // 📌 QUESTO TI MOSTRERÀ L'ERRORE REALE IN CASO DI KO
            console.error("❌ [DEBUG AJAX ERROR] La chiamata è FALLITA!");
            console.error("Stato HTTP: " + xhr.status); // Es. 404 (Non trovato) o 500 (Errore server)
            console.error("Errore rilevato: " + error);
            console.error("Risposta grezza del server: ", xhr.responseText);
        }
    });
}

// Avvia il controllo automatico al caricamento della pagina e lo ripete ogni 30 secondi
$(document).ready(function() {
    console.log("🚀 [DEBUG AJAX] Dashboard caricata. Eseguo il primo avvio...");
    controllaSospesi();
    setInterval(controllaSospesi, 30000); 
});

// Funzione per il pop-up
function caricaEMostraPopUp() {
    $.ajax({
        url: '/modules/vacation_rental/api_sospesi.php?azione=get',
        method: 'GET',
        dataType: 'json',
        success: function(risposta) {
            if (risposta.status === 'success') {
                apriFinestraModale(risposta.data);
            }
        },
        error: function(xhr, status, error) {
            console.error("❌ [DEBUG AJAX GET ERROR]", error);
        }
    });
}
function apriFinestraModale(data) {
    console.log("   [DEBUG UI] Genero la tabella descrittiva per " + data.length + " sospesi.");
    
    var html = '<table class="table table-striped table-bordered table-hover" style="margin-bottom:0;">';
    html += '<thead><tr><th style="width:15%;">Data Notifica</th><th style="width:35%;">Dati Bonifico Ricevuto</th><th style="width:40%;">Risoluzione Consigliata (Incrocio DB)</th><th style="width:10%; text-align:center;">Azione</th></tr></thead>';
    html += '<tbody>';

    $.each(data, function(index, record) {
        html += '<tr id="riga-sospeso-' + index + '">';
        
        // 1. DATA RICEZIONE
        html += '<td>' + record.data_ricezione + '<br><small class="text-muted"><i class="glyphicon glyphicon-phone"></i> ' + record.banca + '</small></td>';
        
        // 2. DATI DEL BONIFICO REALE RICEVUTO
        html += '<td>';
        html += '<span class="label label-success" style="font-size:12px; display:inline-block; margin-bottom:5px;">' + record.importo + ' €</span><br>';
        html += '<strong>👤 Mittente: ' + record.mittente + '</strong><br>';
        html += '<small class="text-muted" style="display:block; margin-top:3px; word-break:break-all;">📝 Causale: ' + record.causale + '</small>';
        html += '</td>';
        
        // 3. OPZIONI DI RISOLUZIONE ACCREDITO (INCROCIO DB)
        html += '<td>';
        
        // OPZIONE A: Trovata la prenotazione per Numero (NUMDOC) con dettagli descrittivi completi
        if (record.probabili_per_id && record.probabili_per_id.length > 0) {
            $.each(record.probabili_per_id, function(i, pre) {
                // Formattiamo le date del soggiorno da YYYY-MM-DD a un più leggibile DD/MM/YYYY
                var inizio = pre.data_inizio ? pre.data_inizio.split('-').reverse().join('/') : '??';
                var fine = pre.data_fine ? pre.data_fine.split('-').reverse().join('/') : '??';
                var intestatario = ((pre.ragso1 || '') + ' ' + (pre.ragso2 || '')).trim();

                html += '<button class="btn btn-sm btn-success btn-block" style="margin-bottom:8px; text-align:left; padding:6px 12px;" onclick="eseguiAssociazione(' + index + ', \'id\', ' + pre.id_tes + ')">';
                html += '<i class="glyphicon glyphicon-ok"></i> <strong>Associa a Prenotazione N. ' + pre.numdoc + '</strong>';
                html += '<span style="display:block; margin-top:3px; padding-left:15px; font-size:11px; opacity:0.9; line-height:1.4;">';
                html += '🏢 Intestatario DB: <em>' + (intestatario ? intestatario : 'Sconosciuto') + '</em><br>';
                html += '📅 Periodo: dal <strong>' + inizio + '</strong> al <strong>' + fine + '</strong>';
                html += '</span>';
                html += '</button>';
            });
        }
        
		/*
        // OPZIONE B: Trovato un Conto Cliente alternativo per Nome (CLFOCO)
        if (record.clfoco_compatibili && record.clfoco_compatibili.length > 0) {
            $.each(record.clfoco_compatibili, function(i, clfoco) {
                html += '<button class="btn btn-xs btn-info btn-block" style="margin-bottom:4px; text-align:left; padding:4px 8px;" onclick="eseguiAssociazione(' + index + ', \'clfoco\', ' + clfoco + ')">';
                html += '<i class="glyphicon glyphicon-user"></i> Intesta direttamente a Conto Cliente Contabile: <strong>' + clfoco + '</strong>';
                html += '</button>';
            });
        }
		*/

        // Failsafe se non trova nulla
        if ((!record.probabili_per_id || record.probabili_per_id.length == 0) && (!record.clfoco_compatibili || record.clfoco_compatibili.length == 0)) {
            html += '<span class="text-danger" style="font-size:11px;"><i class="glyphicon glyphicon-warning-sign"></i> Nessuna corrispondenza trovata nel database. Rintracciare manualmente.</span>';
        }
        
        html += '</td>';
        
        // 4. COLONNA PER SCARTARE LA NOTIFICA SE ESTRANEA
        html += '<td style="vertical-align: middle; text-align: center;">';
        html += '<button class="btn btn-sm btn-danger" onclick="eseguiAssociazione(' + index + ', \'scarta\', 0)" title="Elimina operazione estranea">';
        html += '<i class="glyphicon glyphicon-trash"></i> Scarta';
        html += '</button>';
        html += '</td>';
        
        html += '</tr>';
    });

    html += '</tbody></table>';

    // Iniettiamo la tabella nel corpo della modale e la mostriamo
    $('#corpo-modal-sospesi').html(html);
    $('#modalSospesi').modal('show');
}

// Funzione reale di gestione dei click
function eseguiAssociazione(index, tipoAzione, idDestinazione) {
    // 📌 OPERAZIONE CHIRURGICA: Blocca sul nascere il ricaricamento automatico della pagina
    if (window.event) { window.event.preventDefault(); }
    
    console.log("   [DEBUG AJAX POST] Invio comando senza ricaricare. Riga: " + index);
    
    var testoConferma = "Sei sicuro di voler associare questo pagamento alla prenotazione selezionata?";
    if (tipoAzione === 'scarta') {
        testoConferma = "⚠️ ATTENZIONE: Sei sicuro di voler SCARTARE definitivamente questa notifica? Verrà cancellata dal file dei sospesi.";
    }

    if (confirm(testoConferma)) {
        $.ajax({
            url: '/modules/vacation_rental/api_sospesi.php?azione=associa', // Sfruttiamo lo stesso percorso relativo che ha funzionato prima per il 'count'
            method: 'POST',
            dataType: 'json',
            data: {
                index: index,
                tipo: tipoAzione,
                id_destinazione: idDestinazione
            },
            success: function(risposta) {
                if (risposta.status === 'success') {
                    alert("🎉 " + risposta.message);
                    $('#modalSospesi').modal('hide');
                    controllaSospesi(); // Rinfresca il widget
                } else {
                    alert("❌ Errore dal server: " + risposta.message);
                }
            },
            error: function(xhr, status, error) {
                var messaggioErrore = "❌ ERRORE DI COMUNICAZIONE!\n";
                messaggioErrore += "Stato HTTP: " + xhr.status + " (" + error + ")\n\n";
                messaggioErrore += "Risposta grezza:\n" + (xhr.responseText ? xhr.responseText.substring(0, 300) : "Nessuna");
                alert(messaggioErrore);
            }
        });
    }
}





</script>