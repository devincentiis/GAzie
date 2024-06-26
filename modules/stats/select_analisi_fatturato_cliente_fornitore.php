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

//*+ DC - 23/05/2018
// - Rappresentazione dei dati tramite grafici creati con libreria OPEN SOURCE Chart.js
//*- DC - 23/05/2018

require("../../library/include/datlib.inc.php");

$admin_aziend = checkAdmin();

$mastrofornitori = $admin_aziend['masfor'] . "000000";
$inifornitori = $admin_aziend['masfor'] . '000001';
$finfornitori = $admin_aziend['masfor'] . '999999';
$msg = '';

if (!isset($_POST['ritorno'])) { //al primo accesso allo script
   $msg = '';
   $form['ritorno'] = $_SERVER['HTTP_REFERER'];
//   if (isset($_GET['id_agente'])) { //se mi viene richiesto un agente specifico...
//      $form['id_agente'] = intval($_GET['id_agente']);
//   } else {
//      $form['id_agente'] = 0;
//   }
//   $form['cerca_agente'] = '';
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
   $form['search']['partner'] = '';
   $form['partner'] = 0;
   unset($resultFatturato);
   $form['hidden_req'] = '';
} else { // le richieste successive
   $form['ritorno'] = $_POST['ritorno'];
   $form['gi'] = intval($_POST['gi']);
   $form['mi'] = intval($_POST['mi']);
   $form['ai'] = intval($_POST['ai']);
   $form['gf'] = intval($_POST['gf']);
   $form['mf'] = intval($_POST['mf']);
   $form['af'] = intval($_POST['af']);
   $form['search']['partner'] = substr($_POST['search']['partner'], 0, 20);
   $form['partner'] = intval($_POST['partner']);
   $form['hidden_req'] = $_POST['hidden_req'];
}


if (isset($_POST['preview'])) {
   if (empty($form['partner'])) {
      $msg .= "0+";
   }
   if (empty($msg)) { //non ci sono errori
      $datini = sprintf("%04d%02d%02d", $form['ai'], $form['mi'], $form['gi']);
      $datfin = sprintf("%04d%02d%02d", $form['af'], $form['mf'], $form['gf']);
//       $_SESSION['print_request'] = array('livello'=>$form['livello'],'di'=>$datini,'df'=>$datfin);
//       header("Location: invsta_analisi_agenti.php");
      $what = "fornitori.codice as codice_fornitore, concat(dati_fornitori.ragso1,' ',dati_fornitori.ragso2) as nome_fornitore,
sum(CASE WHEN (tesdoc.datfat between '$datini' and '$datfin' and tesdoc.tipdoc like 'FA%') THEN rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100) ELSE 0 END) as imp_ven,
sum(CASE WHEN (tesdoc.datfat between '$datini' and '$datfin' and tesdoc.tipdoc like 'FA%') THEN rigdoc.quanti*artico.preacq ELSE 0 END) as imp_acq";
      $tab_rigdoc = $gTables['rigdoc'];
      $tab_tesdoc = $gTables['tesdoc'];
      $tab_artico = $gTables['artico'];
      $tab_anagra = $gTables['anagra'];
      $tab_clfoco = $gTables['clfoco'];
      $table = "$tab_rigdoc rigdoc
left join $tab_tesdoc tesdoc on rigdoc.id_tes=tesdoc.id_tes
left join $tab_artico artico on artico.codice=rigdoc.codart
left join $tab_clfoco fornitori on artico.clfoco=fornitori.codice
left join $tab_anagra dati_fornitori on fornitori.id_anagra=dati_fornitori.id
left join $tab_clfoco clienti on tesdoc.clfoco=clienti.codice
left join $tab_anagra dati_clienti on clienti.id_anagra=dati_clienti.id ";
      $codcli = $form['partner'];
      $where = "tesdoc.tipdoc like 'F%' and rigdoc.quanti>0 " .
              " and clienti.codice = '$codcli'";
      $order = "nome_fornitore";
      $group = "fornitori.codice";
      $resultFatturato = gaz_dbi_dyn_query($what, $table, $where, $order, 0, 20000, $group);
   }
}

if (isset($_POST['Return'])) {
   header("Location:docume_vendit.php");
   exit;
}
require("../../library/include/header.php");
$script_transl = HeadMain();
$statsForm = new statsForm();

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
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['partner'] . "</td><td colspan=\"3\" class=\"FacetDataTD\">\n";
$statsForm->selectCustomer('partner', $form['partner'], $form['search']['partner'], $form['hidden_req'], $script_transl['mesg']);
echo "</td>\n";
echo "</tr>\n";

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
// select del anno
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
// select del anno
echo "\t <select name=\"af\" class=\"FacetSelect\">\n";
for ($counter = date("Y") - 10; $counter <= date("Y") + 10; $counter++) {
   $selected = "";
   if ($counter == $form['af'])
      $selected = "selected";
   echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select>\n";
echo "</td></tr>";

//echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[2]</td>";
//echo "<td class=\"FacetDataTD\">";
//echo "<input title=\"anno da analizzare\" type=\"text\" name=\"livello\" value=\"" .
// $form["livello"] . "\" maxlength=\"5\"  class=\"FacetInput\">";
//echo "</td></tr>";
//echo "<tr>\n";
//echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['id_agente'] . "</td>";
//echo "<td  class=\"FacetDataTD\">\n";
//$select_agente = new selectAgente("id_agente");
//$select_agente->addSelected($form["id_agente"]);
//$select_agente->output();
//echo "</td></tr>\n";

echo "<tr>\n
     <td class=\"FacetFieldCaptionTD\"><input type=\"submit\" name=\"Return\" value=\"" . ucfirst($script_transl['return']) . "\"></td>\n
     <td align=\"right\" class=\"FacetFooterTD\"><input type=\"submit\" accesskey=\"i\" name=\"preview\" value=\"" . ucfirst($script_transl['preview']) . "\"></td>\n
     </tr>\n</table>";
echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
if (isset($resultFatturato)) {
   $linkHeaders = new linkHeaders($script_transl['header']);
   $linkHeaders->output();
   $totFatturato = 0;
   $totCosti = 0;

   //*+ DC - 23/05/2018
   // array da usare per grafici
   $CJSarray = array();
   //*- DC - 23/05/2018

   while ($mv = gaz_dbi_fetch_array($resultFatturato)) {
      $nFatturato = $mv['imp_ven'];
      if ($nFatturato > 0) {
				// utilizzo l'array associativo per l'output html e quello numerico per JS e la modalità di escape più appropriata per ognuno
				$mv['nome_cliente'] = htmlspecialchars($mv['nome_fornitore'], ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5);
				$mv[1] = json_encode($mv[1]);
				//*+ DC - 23/05/2018
				$CJSarray[] = $mv;
				//*- DC - 23/05/2018

         $nCosti = $mv['imp_acq'];
         $margine = ($nFatturato - $nCosti) * 100 / $nFatturato;
         $totFatturato+=$nFatturato;
         $totCosti+=$nCosti;
         echo "<tr>";
         echo "<td class=\"FacetFieldCaptionTD\">" . substr($mv[0], 3) . " &nbsp;</td>";
         echo "<td align=\"left\" class=\"FacetDataTD\">" . $mv['nome_fornitore'] . " &nbsp;</td>";
         echo "<td align=\"right\" class=\"FacetDataTD\">" . gaz_format_number($nFatturato) . " &nbsp;</td>";
         echo "<td align=\"right\" class=\"FacetDataTD\">" . gaz_format_number($nCosti) . " &nbsp;</td>";
         echo "<td align=\"right\" class=\"FacetDataTD\">" . gaz_format_number($margine) . " &nbsp;</td>";
         echo "</tr>";
      }
   }
   $margine = ($totFatturato > 0 ? ($totFatturato - $totCosti) * 100 / $totFatturato : 0);
   echo "<tr>";
   echo "<td class=\"FacetFieldCaptionTD\"> &nbsp;</td>";
   echo "<td align=\"left\" class=\"FacetDataTD\"><B>" . $script_transl['totale'] . "</B> &nbsp;</td>";
   echo "<td align=\"right\" class=\"FacetDataTD\"><B>" . gaz_format_number($totFatturato) . "</B> &nbsp;</td>";
   echo "<td align=\"right\" class=\"FacetDataTD\"><B>" . gaz_format_number($totCosti) . "</B> &nbsp;</td>";
   echo "<td align=\"right\" class=\"FacetDataTD\"><B>" . gaz_format_number($margine) . "</B> &nbsp;</td>";
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
		$margine = ($mvf[2] > 0 ? ($mvf[2] - $mvf[3]) * 100 / $mvf[2] : 0);
?>
	numOfValuesInDataset++;
	chartLabels.push(<?php echo $mvf[1]?>);
	pieChartData.push(parseFloat(<?php echo $margine?>).toFixed(2));
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
	  text:'MARGINE in % tra Fatturato/Costo per Fornitore', //not yet translated
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
// Populate bar chart datasets (sold/cost)
var barChartDataCost=[];
var barChartDataSold=[];

<?php
if( !empty($CJSarray) && count($CJSarray)>0 ) {
	foreach ($CJSarray as $mvf)	{
?>
	barChartDataSold.push(parseFloat(<?php echo $mvf[2]?>).toFixed(2));
	barChartDataCost.push(parseFloat(<?php echo $mvf[3]?>).toFixed(2));
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
	  label:'Fatturato', //not yet translated
	  data:barChartDataSold,
	  backgroundColor:'rgba(66, 133, 244, 1)',
	  borderWidth:0,
	  borderColor:'#ccc',
	  hoverBorderWidth:1,
	  hoverBorderColor:'#777',
	}, {
	  label:'Costi', //not yet translated
	  data:barChartDataCost,
	  backgroundColor:'rgba(186, 58, 47, 1)',
	  borderWidth:0,
	  borderColor:'#ccc',
	  hoverBorderWidth:1,
	  hoverBorderColor:'#777',
	}, {
	  label:'Margine %', //not yet translated
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
	  text:'Vendite per fornitore', //not yet translated
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
