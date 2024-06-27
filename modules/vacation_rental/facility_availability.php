<?php
/*
   --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (https://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
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
?>
<!DOCTYPE html>
<html lang='en'>
  <head>
    <meta charset='utf-8' />
    <link href='fullcalendar-5.11.5/lib/main.css' rel='stylesheet' />
    <script src='fullcalendar-5.11.5/lib/main.js'></script>
	<style>
		.overlay{

			position: fixed;
			width: 100%;
			height: 100%;
			top: 0;
			left: 0;
			z-index: 999;
			background: rgba(255,255,255,0.8) url("spinner.gif") center no-repeat;
		}
	</style>
	<!-- questo style insieme a 'display' => 'background' inviato da load db from event e inviando il title '' crea il calendario per il frontend
	<style>
	.fc-bg-event {
		  background-color: red !important;
		  opacity: 1 !important;
	}
	</style>
	-->
<?php
//require("../../library/include/datlib.inc.php");
include_once("manual_settings.php");
$id=substr($_GET['code'],0,9);
?>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
      height: 600,
      initialView: 'dayGridMonth',
      selectable: true, //abilita il controllo del passaggio mouse cliccato sopra i giorni
      headerToolbar:{
       left:'prev,next today,dayGridMonth',
       center:'title',
       right:'prevYear,nextYear'
      },
      editable: true,
      eventColor: '#378006',
      timeZone: 'local',
      locale: 'it',
      eventDisplay  : 'block',// tutti gli eventi vengono mostrati con un rettangolo pieno in visualizzazione giornaliera
      events : 'load_from_db_facilityevents.php?id=<?php echo $id; ?>& token=<?php echo md5($token.date('Y-m-d')); ?>',
	  loading: function( isLoading, view ) {
			if(isLoading) {// isLoading gives boolean value
				calendarEl.classList.add("overlay");
			} else {
				calendarEl.classList.remove("overlay");
			}
		}

/* ***** L'EVENTO, AD ESEMPIO DI UN GIORNO, COMINCIA ALLE ORE 00:00 DEL GIORNO DI INIZIO E FINISCE ALLE ORE 00:00 DEL GIORNO DOPO (SONO DUE DATE DIFFERENTI MA SONO 24 ORE E QUINDI VIENE MOSTRATO PIENO SOLO UN GIORNO) ***** */



    });
    calendar.render();
  });
</script>
  </head>
  <body>
    <div id='calendar'>
      <form method="GET">
        <div style="display:none" id="tooltip" title="Tooltip">
          <p><b>tooltip bla test:</b></p>
          <p>Codice</p>
          <p class="ui-state-highlight" id="idcodice"></p>
          <p>Descrizione</p>
          <p class="ui-state-highlight" id="iddescri"></p>
        </div>
      </form>
    </div>
  </body>
 </html>
