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

//*+ DC - 23/05/2018
// Nuova analisi statistica:
// - Viene calcolato l'avanzamento delle vendite (in %) rispetto all'acquistato raggruppato per fornitore abituale presente su articolo.
// - L'analisi parte dai movimenti di magazzino (movmag) ed estrae per periodo (distinto tra acquistato e venduto) il totale acquistato/venduto.
// - I dati vengono raggruppati per fornitore impostato su anagrafica articolo.
// - Risulta quindi indispensabile la corretta imputazione del codice fornitore sull'anagrafica articolo.
// - Questa analisi mi fa capire tra i fornitori che tratto quelli che sono più remunerativi nei periodi indicati dandomi la possibilità di
//   valutare quali fornitori tenere e quali escludere per il rifornimento di merce (settore abbigliamento al dettaglio).
// - Rappresentazione dei dati tramite grafici creati con libreria OPEN SOURCE Chart.js
//*- DC - 23/05/2018

require("../../library/include/datlib.inc.php");

$admin_aziend = checkAdmin();

$msg = ''; // anche se non sono previste situazioni di errori da gestire (lascio per uso futuro)

if (!isset($_POST['ritorno'])) { //al primo accesso allo script
   $msg = '';
   $form['ritorno'] = $_SERVER['HTTP_REFERER'];

   // Data inizio / fine per vendite
   if (isset($_POST['datini'])) {
      $form['gi'] = substr($_POST['datini'], 6, 2);
      $form['mi'] = substr($_POST['datini'], 4, 2);
      $form['ai'] = substr($_POST['datini'], 0, 4);
   } else {
      $form['gi'] = 1;
      $form['mi'] = 1;
      $form['ai'] = date("Y");
   }
   if (isset($_POST['datfin'])) {
      $form['gf'] = substr($_POST['datfin'], 6, 2);
      $form['mf'] = substr($_POST['datfin'], 4, 2);
      $form['af'] = substr($_POST['datfin'], 0, 4);
   } else {
      $form['gf'] = date("d");
      $form['mf'] = date("m");
      $form['af'] = date("Y");
   }

   // Data inizio / fine per acquisti
   if (isset($_POST['datiniA'])) {
      $form['giA'] = substr($_POST['datiniA'], 6, 2);
      $form['miA'] = substr($_POST['datiniA'], 4, 2);
      $form['aiA'] = substr($_POST['datiniA'], 0, 4);
   } else {
      $form['giA'] = 1;
      $form['miA'] = 1;
      $form['aiA'] = date("Y");
   }
   if (isset($_POST['datfinA'])) {
      $form['gfA'] = substr($_POST['datfinA'], 6, 2);
      $form['mfA'] = substr($_POST['datfinA'], 4, 2);
      $form['afA'] = substr($_POST['datfinA'], 0, 4);
   } else {
      $form['gfA'] = date("d");
      $form['mfA'] = date("m");
      $form['afA'] = date("Y");
   }

   unset($resultAnalisi);
   $form['hidden_req'] = '';
} else { // le richieste successive
   // Data inizio / fine per vendite
   $form['ritorno'] = $_POST['ritorno'];
   $form['gi'] = intval($_POST['gi']);
   $form['mi'] = intval($_POST['mi']);
   $form['ai'] = intval($_POST['ai']);
   $form['gf'] = intval($_POST['gf']);
   $form['mf'] = intval($_POST['mf']);
   $form['af'] = intval($_POST['af']);
   // Data inizio / fine per acquisti
   $form['giA'] = intval($_POST['giA']);
   $form['miA'] = intval($_POST['miA']);
   $form['aiA'] = intval($_POST['aiA']);
   $form['gfA'] = intval($_POST['gfA']);
   $form['mfA'] = intval($_POST['mfA']);
   $form['afA'] = intval($_POST['afA']);

   $form['hidden_req'] = $_POST['hidden_req'];
}


if (isset($_POST['preview'])) {
   // controllo situazioni di errore // per ora nessuna prevista
   if (empty($msg)) { //non ci sono errori
	  // Data inizio / fine per vendite
      $datini = sprintf("%04d%02d%02d", $form['ai'], $form['mi'], $form['gi']);
      $datfin = sprintf("%04d%02d%02d", $form['af'], $form['mf'], $form['gf']);
	  // Data inizio / fine per acquisti
      $datiniA = sprintf("%04d%02d%02d", $form['aiA'], $form['miA'], $form['giA']);
      $datfinA = sprintf("%04d%02d%02d", $form['afA'], $form['mfA'], $form['gfA']);

	  $what = "fornitori.codice as codice_fornitore, dati_fornitori.ragso1 as nome_fornitore,
sum(CASE
                WHEN (movmag.datreg between '$datini' and '$datfin' and movmag.tipdoc='FAI') THEN movmag.quanti*movmag.prezzo*(1-movmag.scorig/100)
		        WHEN (movmag.datreg between '$datini' and '$datfin' and movmag.tipdoc='FNC') THEN (-1)*movmag.quanti*movmag.prezzo*(1-movmag.scorig/100)
				ELSE 0 END) as totValVen,
sum(CASE
                WHEN (movmag.datreg between '$datiniA' and '$datfinA' and ( movmag.tipdoc='AFA' OR movmag.tipdoc='AFT')) THEN movmag.quanti*movmag.prezzo*(1-movmag.scorig/100)
		        WHEN (movmag.datreg between '$datiniA' and '$datfinA' and movmag.tipdoc='AFC') THEN (-1)*movmag.quanti*movmag.prezzo*(1-movmag.scorig/100)
				ELSE 0 END) as totValAcq";

	  $tab_movmag = $gTables['movmag'];
      $tab_artico = $gTables['artico'];
      $tab_anagra = $gTables['anagra'];
      $tab_clfoco = $gTables['clfoco'];

	  $table = "$tab_movmag movmag
left join $tab_artico artico on artico.codice=movmag.artico
left join $tab_clfoco fornitori on artico.clfoco=fornitori.codice
left join $tab_anagra dati_fornitori on fornitori.id_anagra=dati_fornitori.id";

	  $where = "artico.clfoco>0 and movmag.quanti<>0 ";
      $order = "nome_fornitore, codice_fornitore";
      $group = "fornitori.codice"; // artico.clfoco
      $resultAnalisi = gaz_dbi_dyn_query($what, $table, $where, $order, 0, 20000, $group);
   }
}

if (isset($_POST['Return'])) {
   header("Location:../root/docume_root.php"); // richiamato script 'help' di base di GAzie
   exit;
}
require("../../library/include/header.php");
$script_transl = HeadMain();

echo "<form method=\"POST\">";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $form['ritorno'] . "\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'] . "</div>";
echo "<table class=\"Tmiddle table-striped\" align=\"center\">";
if (!empty($msg)) {
   $message = "";
   $rsmsg = array_slice(explode('+', chop($msg)), 0, -1);
   foreach ($rsmsg as $value) {
      $message .= $script_transl['error'] . "! -> ";
      $rsval = explode('-', chop($value));
      foreach ($rsval as $valmsg) {
         $message .= $script_transl['errors'][$valmsg] . " ";
      }
      $message .= "<br>";
   }
   echo '<tr><td colspan="5" class="FacetDataTDred">' . $message . '</td></tr>';
}

// Data inizio / fine per vendite
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[0]</td>";
echo "<td class=\"FacetDataTD\">";
// select del giorno
echo "\t <select name=\"gi\" class=\"FacetSelect\">\n";
for ($counter = 1; $counter <= 31; $counter++) {
   $selected = "";
   if ($counter == $form['gi'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
// select del mese
echo "\t <select name=\"mi\" class=\"FacetSelect\">\n";
$gazTimeFormatter->setPattern('MMMM');
for ($counter = 1; $counter <= 12; $counter++) {
  $selected = "";
  if ($counter == $form['mi']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
// select dell'anno
echo "\t <select name=\"ai\" class=\"FacetSelect\">\n";
for ($counter = date("Y") - 10; $counter <= date("Y") + 10; $counter++) {
   $selected = "";
   if ($counter == $form['ai'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}

echo "\t </select>\n";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[1]</td>";
echo "<td class=\"FacetDataTD\">";
// select del giorno
echo "\t <select name=\"gf\" class=\"FacetSelect\">\n";
for ($counter = 1; $counter <= 31; $counter++) {
   $selected = "";
   if ($counter == $form['gf'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
// select del mese
echo "\t <select name=\"mf\" class=\"FacetSelect\">\n";
for ($counter = 1; $counter <= 12; $counter++) {
  $selected = "";
  if ($counter == $form['mf']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
// select dell'anno
echo "\t <select name=\"af\" class=\"FacetSelect\">\n";
for ($counter = date("Y") - 10; $counter <= date("Y") + 10; $counter++) {
   $selected = "";
   if ($counter == $form['af'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select>\n";
echo "</td></tr>";

// Data inizio / fine per acquisti
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[2]</td>";
echo "<td class=\"FacetDataTD\">";
// select del giorno
echo "\t <select name=\"giA\" class=\"FacetSelect\">\n";
for ($counter = 1; $counter <= 31; $counter++) {
   $selected = "";
   if ($counter == $form['giA'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
// select del mese
echo "\t <select name=\"miA\" class=\"FacetSelect\">\n";
for ($counter = 1; $counter <= 12; $counter++) {
  $selected = "";
  if ($counter == $form['miA']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
// select dell'anno
echo "\t <select name=\"aiA\" class=\"FacetSelect\">\n";
for ($counter = date("Y") - 10; $counter <= date("Y") + 10; $counter++) {
   $selected = "";
   if ($counter == $form['aiA'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}

echo "\t </select>\n";
echo "</td></tr>";
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[3]</td>";
echo "<td class=\"FacetDataTD\">";
// select del giorno
echo "\t <select name=\"gfA\" class=\"FacetSelect\">\n";
for ($counter = 1; $counter <= 31; $counter++) {
   $selected = "";
   if ($counter == $form['gfA'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
// select del mese
echo "\t <select name=\"mfA\" class=\"FacetSelect\">\n";
for ($counter = 1; $counter <= 12; $counter++) {
  $selected = "";
  if ($counter == $form['mfA']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
// select dell'anno
echo "\t <select name=\"afA\" class=\"FacetSelect\">\n";
for ($counter = date("Y") - 10; $counter <= date("Y") + 10; $counter++) {
   $selected = "";
   if ($counter == $form['afA'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select>\n";
echo "</td></tr>";

echo "<tr>\n
     <td class=\"FacetFieldCaptionTD\"><input type=\"submit\" name=\"Return\" value=\"" . ucfirst($script_transl['return']) . "\"></td>\n
     <td align=\"right\" class=\"FacetFooterTD\"><input type=\"submit\" accesskey=\"i\" name=\"preview\" value=\"" . ucfirst($script_transl['preview']) . "\"></td>\n
     </tr>\n</table>";
echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
if (isset($resultAnalisi)) {
   $linkHeaders = new linkHeaders($script_transl['header']);
   $linkHeaders->output();
   $totFatturato = 0;
   $totCosti = 0;

   // array da usare per grafici
   $CJSarray = array();

   while ($mv = gaz_dbi_fetch_array($resultAnalisi)) {
      $nAcquistato = $mv['totValAcq'];
      if ($nAcquistato > 0) {

		 $CJSarray[] = $mv;

         $nVenduto = $mv['totValVen'];
         $avanzamento = ($nVenduto*100) / $nAcquistato;
         $totFatturato+=$nVenduto;
         $totCosti+=$nAcquistato;
         echo "<tr>";
         echo "<td class=\"FacetFieldCaptionTD\">" . substr($mv[0], 3) . " &nbsp;</td>";
         echo "<td align=\"left\" class=\"FacetDataTD\">" . $mv[1] . " &nbsp;</td>";
         echo "<td align=\"right\" class=\"FacetDataTD\">" . gaz_format_number($nAcquistato) . " &nbsp;</td>";
         echo "<td align=\"right\" class=\"FacetDataTD\">" . gaz_format_number($nVenduto) . " &nbsp;</td>";
         echo "<td align=\"right\" class=\"FacetDataTD\">" . gaz_format_number($avanzamento) . " &nbsp;</td>";
         echo "</tr>";
      }
   }

   $avanzamento = ($totCosti > 0 ? ($totFatturato*100) / $totCosti : 0);

   echo "<tr>";
   echo "<td class=\"FacetFieldCaptionTD\"> &nbsp;</td>";
   echo "<td align=\"left\" class=\"FacetDataTD\"><B>" . $script_transl['totale'] . "</B> &nbsp;</td>";
   echo "<td align=\"right\" class=\"FacetDataTD\"><B>" . gaz_format_number($totCosti) . "</B> &nbsp;</td>";
   echo "<td align=\"right\" class=\"FacetDataTD\"><B>" . gaz_format_number($totFatturato) . "</B> &nbsp;</td>";
   echo "<td align=\"right\" class=\"FacetDataTD\"><B>" . gaz_format_number($avanzamento) . "</B> &nbsp;</td>";
   echo "</tr>";
   echo '<tr class="FacetFieldCaptionTD">
	 			<td colspan="12" align="right"><input type="button" name="print" onclick="window.print();" value="' . $script_transl['print'] . '"></td>
	 	  </tr>';
}
?>
</table>
</form>

<!--+ DC - 23/05/2018 - Chart.js - include script/set css charts styles -->
<script type="text/javascript" src="../../js/chartjs/2.7.2/Chart.bundle.min.js"></script>
<script>
window.onload = function() {
  createChartJS();
}
</script>

<style>
.chart {
  width: 100%;
  border: 0px solid #d7d7d7;
  padding: 3px;
}

.pieChart {
	min-height: 600px;
}

.row {
  margin:0 !important;
}
</style>
<!--- DC - 23/05/2018 - Chart.js - include script/set css charts styles -->

<!--+ DC - 23/05/2018 - Chart.js - render charts -->
<script>

var pieChartData=[]; // global scope for retrieve length property

function createChartJS() {

// set css styles before render charts
document.getElementById("chart_pie_div").style.border = '1px solid #ccc';
document.getElementById("chart_horizontal_bar_div").style.border = '1px solid #ccc';
document.getElementById("chartsArea").style.display = 'block';

var chartLabels=[];
var chartPieSliceColors=[];

// Global Options
Chart.defaults.global.defaultFontFamily = 'sans-serif,Arial,Roboto,Courier New';
Chart.defaults.global.defaultFontSize = 14;
Chart.defaults.global.defaultFontColor = '#999';

// Pie Chart
// Populate pie chart dataset/labels
var numOfValuesInDataset=0;
<?php
if( !empty($CJSarray) && count($CJSarray)>0 ) {
	foreach ($CJSarray as $mvf)	{
		$avanzamento = ($mvf[3] > 0 ? ($mvf[2]*100) / $mvf[3] : 0);
?>
	numOfValuesInDataset++;
	chartLabels.push('<?php echo $mvf[1]?>');
	pieChartData.push(parseFloat(<?php echo $avanzamento?>).toFixed(2));
<?php
	}
}
?>
// assign random color for each pie slice
for(iColors=1;iColors<=chartLabels.length;iColors++) {
	chartPieSliceColors.push(dynamicColors());
}

// Get the 2d context for pie chart container (canvas)
let myChartPie = document.getElementById('myChartPie').getContext('2d');

// Create the pie chart
let chartPie = new Chart(myChartPie, {
  type:'doughnut', // bar, horizontalBar, pie, line, doughnut, radar, polarArea
  data:{
	labels:chartLabels,
	datasets:[{
	  data:pieChartData,
	  backgroundColor:chartPieSliceColors,
	  borderWidth:0,
	  borderColor:'#ccc',
	  hoverBorderWidth:2,
	  hoverBorderColor:'#fff',
	  pointStyle: 'rectRot',
	}]
  },
  options:{
	cutoutPercentage:30,
	rotation:-0.35*3.14,
	responsive:true,
	maintainAspectRatio:false,
	title:{
	  display:true,
	  text:'Avanzamento in % del Venduto su Acquistato (reale) per Fornitore', //not yet translated
	  fontSize:14
	},
	legend:{
	  display:true,
	  maxWidth:100,
	  position:'bottom',
	  labels:{
		fontColor:'#000',
		usePointStyle: true
	  }
	},
	layout:{
	  padding:{
		left:0,
		right:0,
		bottom:0,
		top:0
	  }
	},
	tooltips:{
	  enabled:true
	}
  }
});

// Horizontal Bar Chart
// Populate bar chart datasets (purchased/sold)
var barChartDataPurchased=[];
var barChartDataSold=[];

<?php
if( !empty($CJSarray) && count($CJSarray)>0 ) {
	foreach ($CJSarray as $mvf)	{
?>
	barChartDataPurchased.push(parseFloat(<?php echo $mvf[3]?>).toFixed(2));
	barChartDataSold.push(parseFloat(<?php echo $mvf[2]?>).toFixed(2));
<?php
	}
}
?>

// dynamically height for bar chart
// set inner height to 40 pixels per row
var chartAreaHeight = numOfValuesInDataset * 40;
// add padding to outer height to accomodate title, axis labels, etc
var chartHeight = chartAreaHeight + 80;

var rightHeight=chartHeight + "px";
document.getElementById("chart_horizontal_bar_div").style.height = rightHeight;

// Get the 2d context for pie chart container (canvas)
let myChartHorizontalBar = document.getElementById('myChartHorizontalBar').getContext('2d');

// Create the horizontal bar chart
let chartHorizontalBar = new Chart(myChartHorizontalBar, {
  type:'horizontalBar', // bar, horizontalBar, pie, line, doughnut, radar, polarArea
  data:{
	labels:chartLabels,
	datasets:[{
	  label:'Acquistato', //not yet translated
	  data:barChartDataPurchased,
	  backgroundColor:'rgba(66, 133, 244, 1)',
	  borderWidth:0,
	  borderColor:'#ccc',
	  hoverBorderWidth:1,
	  hoverBorderColor:'#777',
	}, {
	  label:'Venduto', //not yet translated
	  data:barChartDataSold,
	  backgroundColor:'rgba(186, 58, 47, 1)',
	  borderWidth:0,
	  borderColor:'#ccc',
	  hoverBorderWidth:1,
	  hoverBorderColor:'#777',
	}, {
	  label:'Avanzamento %', //not yet translated
	  data:pieChartData,
	  backgroundColor:'rgba(244, 180, 0, 1)',
	  borderWidth:0,
	  borderColor:'#ccc',
	  hoverBorderWidth:1,
	  hoverBorderColor:'#777',
	}]
  },
  options:{
	responsive:true,
	maintainAspectRatio:false,
	scales: {
		yAxes: [{
			/*ticks: {
				beginAtZero:true
			},*/
			gridLines: {
				display: false
			}
		}],
		xAxes: [{
			ticks: {
				beginAtZero:true
			},
			gridLines: {
				display: true,
				color: "rgba(192,192,192,1)"
			}
		}]
	},
	title:{
	  display:true,
	  text:'Avanzamento venduto su acquistato per fornitore', //not yet translated
	  fontSize:14
	},
	legend:{
	  display:true,
	  maxWidth:100,
	  position:'right',
	  labels:{
		fontColor:'#000',
		usePointStyle: true
	  }
	},
	layout:{
	  padding:{
		left:0,
		right:0,
		bottom:0,
		top:0
	  }
	},
	tooltips:{
	  enabled:true
	}
  }
});
}

function dynamicColors() {
  var r = Math.floor(Math.random() * 255);
  var g = Math.floor(Math.random() * 255);
  var b = Math.floor(Math.random() * 255);
  return "rgb(" + r + "," + g + "," + b + ")";
}

window.addEventListener('resize', function () {
		pieChartData=[];createChartJS();
}/*, false*/);

</script>
<!--- DC - 23/05/2018 - Chart.js - render charts -->

<!--+ DC - 23/05/2018 - Chart.js - html -->
<br/>

<div class="row">
  <!-- Titolo aggiuntivo (opzioneale, per ora disattivato)
  <div class="col-md-12 text-center">
    <h3>Rappresentazione grafica dati estrapolati</h3>
  </div>
  //-->
  <div class="col-md-4 col-md-offset-4">
  </div>
  <div class="clearfix"></div>
  <div id="chartsArea" style="display:none">
	<div id="chart_pie_div" class="col-md-4">
		<canvas id="myChartPie" class="chart pieChart"></canvas>
	</div>
	<!--div id="chart_hor_bar_div" style="position: relative;" class="col-md-8"-->
	<div id="chart_horizontal_bar_div" class="col-md-8">
		<canvas id="myChartHorizontalBar" style="position: relative;" class="chart"></canvas>
	</div>
  </div>
</div>
<!--- DC - 23/05/2018 - Chart.js - html -->

<?php
require("../../library/include/footer.php");
?>
