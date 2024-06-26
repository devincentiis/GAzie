<?php /*
--------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (http://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
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
include_once("manual_settings.php");
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
<?php
$id=substr($_GET['extra_code'],0,32);
$initialDate=(isset($_GET['initialDate']))?$_GET['initialDate']:date('Y-m-d');
?>
<script>
      document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
          height: 600,
          initialView: 'dayGridMonth',
          initialDate: '<?php echo $initialDate; ?>',
          selectable: true, //abilita il controllo del passaggio mouse cliccato sopra i giorni
          headerToolbar:{
           left:'prev,next today,dayGridMonth,timeGridWeek,timeGridDay,listWeek',
           center:'title',
           right:'prevYear,nextYear'
          },
          editable: true,
          eventColor: '#378006',
          timeZone: 'local',
          locale: 'it',
          eventDisplay  : 'block',// tutti gli eventi vengono mostrati con un rettangolo pieno
          displayEventTime : false,
          events : 'load_from_db_events_extra.php?id=<?php echo $id; ?>& token=<?php echo md5($token.date('Y-m-d')); ?>',
          loading: function( isLoading, view ) {
            if(isLoading) {// isLoading gives boolean value
              calendarEl.classList.add("overlay");
            } else {
              calendarEl.classList.remove("overlay");
            }
          },

/* ***** L'EVENTO, AD ESEMPIO DI UN GIORNO, COMINCIA ALLE ORE 00:00 DEL GIORNO DI INIZIO E FINISCE ALLE ORE 00:00 DEL GIORNO DOPO (SONO DUE DATE DIFFERENTI MA SONO 24 ORE E QUINDI VIENE MOSTRATO PIENO SOLO UN GIORNO) ***** */

			select: function(info) {// seleziona più giorni passando sopra con il mouse cliccato
				/*
				alert('Clicked on: ' + info.startStr);
				alert('Clicked to: ' + info.endStr);
				*/
				var title = prompt("Enter Event Title select");
				var start = info.startStr;
				var end = info.endStr;
				var xhttp = new XMLHttpRequest();
				xhttp.open("GET", "save_to_db_events_extra.php?title="+ title +"&start="+ start +"&end="+ end +"&house_code=<?php echo $id; ?>& token=<?php echo md5($token.date('Y-m-d')); ?>", true);
				xhttp.send();
				xhttp.onload = function(){
					calendar.refetchEvents();
					let url = window.location.href;
          url += '&initialDate='+start.toISOString().substring(0, 10);
          window.location.href = url;
				};

			},

			eventDrop:function(info){
				 var start = info.event.start;
				 var end = info.event.end;
				 var title = info.event.title;
				 var id = info.event.id;
				// alert ("update title:"+ title);
				//alert ("update_db_events.php?title="+ title +"&start="+ start.toISOString() +"&end="+ end.toISOString() +"&id="+ id);


				 if (end == null){// nel caso di evento di un solo giorno
					 var end = start;
				 }
				 var xhttp = new XMLHttpRequest();
				 xhttp.open("GET", "update_db_events.php?title="+ title +"&start="+ start.toISOString() +"&end="+ end.toISOString() +"&id="+ id +"&house_code=<?php echo $id; ?>& token=<?php echo md5($token.date('Y-m-d')); ?>", true);
				xhttp.send();
				xhttp.onload = function(){
					calendar.refetchEvents();
					let url = window.location.href;
          url += '&initialDate='+start.toISOString().substring(0, 10);
          window.location.href = url;
				};

			},

			eventResize:function(info){
				 var start = info.event.start;
				 var end = info.event.end;
				 var title = info.event.title;
				 var id = info.event.id;
				//alert ("update_isostring=start="+ start.toISOString() +"&end="+ end.toISOString() +"&id="+ id);
				//alert ("update_normal=start="+ start +"&end="+ end +"&id="+ id);


				 var xhttp = new XMLHttpRequest();
				 xhttp.open("GET", "update_db_events.php?title="+ title +"&start="+ start.toISOString() +"&end="+ end.toISOString() +"&id="+ id +"&house_code=<?php echo $id; ?>& token=<?php echo md5($token.date('Y-m-d')); ?>", true);
				xhttp.send();
				xhttp.onload = function(){
					calendar.refetchEvents();
					let url = window.location.href;
          url += '&initialDate='+start.toISOString().substring(0, 10);
          window.location.href = url;
				};

			},

			eventMouseEnter: function (info) {
				//alert(info.event.title);

				 //document.getElementById("tooltip").innerHTML = this.responseText;

			},

			eventClick: function(info) {
				//alert('Event: ' + info.event.title);
				//alert('Coordinates: ' + info.jsEvent.pageX + ',' + info.jsEvent.pageY);
				//alert('View: ' + info.view.type);
				if(confirm("Are you sure you want to remove it?")){
					var id = info.event.id;
					//alert('Test get id: ' + id);
					var xhttp = new XMLHttpRequest();
					 xhttp.open("GET", "delete_db_events.php?id="+ id +"& token=<?php echo md5($token.date('Y-m-d')); ?>", true);
					xhttp.send();
					xhttp.onload = function(){
						calendar.refetchEvents();
						let url = window.location.href;
            url += '&initialDate='+start.toISOString().substring(0, 10);
            window.location.href = url;
					};
				}
				/* per fare una modifica bisogna usare un pop up ma c'è il problema che bisogna aggiornare il calendario dopo aver chiuso il popup
				let newWindow = open('/', 'example', 'width=300,height=300');
					if(newWindow.closed){
					alert(newWindow.closed); // true
					}
				*/
			  }
        });
        calendar.render();
      });

</script>
</head>
  <body>
    <div id='calendar'>
	</div>
  </body>
</html>
