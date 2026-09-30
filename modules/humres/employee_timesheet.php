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


// Antonio Germani -  sett. 2021

function getWorkedHours($mese,$anno) { // Carico staff worked hours per il dato mese e anno
	global $gTables;
	$month_res=array();
	$query="SELECT DAY(work_day) AS daynum,work_day,hours_normal,hours_extra,hours_absence,hours_other,".$gTables['staff'] .".id_staff,id_work_type_extra,id_absence_type,id_other_type,note FROM ".$gTables['staff_worked_hours']."
	LEFT JOIN ". $gTables['staff'] . " ON ". $gTables['staff_worked_hours'] .".id_staff = ". $gTables['staff'] .".id_staff
	WHERE MONTH(work_day) = '". $mese ."' AND YEAR(work_day) = '". $anno ."' ORDER BY id_staff ASC";
	$resc = gaz_dbi_query($query);
	while($r = mysqli_fetch_array($resc)){
		$month_res[$r['daynum']][$r['id_staff']] = $r;

		$des=gaz_dbi_get_row($gTables['staff_work_type'], "id_work", $r['id_work_type_extra']);
		$month_res[$r['daynum']][$r['id_staff']]['extra_des']=($des)?$des['descri_ext']:'';

		$des=gaz_dbi_get_row($gTables['staff_work_type'], "id_work", $r['id_absence_type']);
		$month_res[$r['daynum']][$r['id_staff']]['absence_des']=($des)?$des['descri_ext']:'';

		$des=gaz_dbi_get_row($gTables['staff_work_type'], "id_work", $r['id_other_type']);
		$month_res[$r['daynum']][$r['id_staff']]['other_des']=($des)?$des['descri_ext']:'';
		// riprendo pure tutte le note da staff_work_movements (cartellino)
		$card_res = gaz_dbi_dyn_query('note', $gTables['staff_work_movements'], "id_staff = " . intval($r['id_staff']). " AND start_work BETWEEN '" . $r['work_day'] ." 00:00:00' AND '" . $r['work_day'] ." 23:59:59'");
		$accnote=(empty($r['note']))?'':$r['note'].', ';
		while($cr=gaz_dbi_fetch_array($card_res) ) {

			$accnote.=(empty($cr['note']))?'':$cr['note'].', ';
		}
		$month_res[$r['daynum']][$r['id_staff']]['mov_note'] = substr($accnote,0,-2);
	}
	return $month_res;
}

function getWorkers($mese,$anno) { // carico i collaboratori ancora in forza per il dato mese e anno e non nascosti alla ricerca
	global $gTables;
	$cols=array();
	$query="SELECT ragso1,ragso2,id_staff,id_clfoco,last_hourly_cost FROM ".$gTables['staff']."
	LEFT JOIN ". $gTables['clfoco'] . " ON ". $gTables['staff'] .".id_clfoco = ". $gTables['clfoco'] .".codice
	LEFT JOIN ". $gTables['anagra'] . " ON ". $gTables['anagra'] .".id = ". $gTables['clfoco'] .".id_anagra
	WHERE DATE_FORMAT(start_date, '%Y%m') <=  ".$anno.str_pad($mese,2,"0",STR_PAD_LEFT)." AND (DATE_FORMAT(end_date, '%Y%m') >= ".$anno.str_pad($mese,2,"0",STR_PAD_LEFT)." OR end_date IS NULL OR end_date <= '2004-01-27') AND status <> 'HIDDEN'";
	$coll = gaz_dbi_query($query);
	while($col = $coll->fetch_assoc()){
		$cols[]=$col;
	}
	return $cols;
}


// carico i dati per la select work type del jquery
$query = 'SELECT id_work, descri FROM `' . $gTables['staff_work_type'] . '` ORDER BY `id_work_type` ASC';
$result = gaz_dbi_query($query);
$work_types="0:'Lavoro normale'";
$invalid_characters = array("'", ",", ":");
while ($r = gaz_dbi_fetch_array($result)) {// carico i dati di staff_work_type
	$work_types .= ", ".$r['id_work'].":'". substr(str_replace($invalid_characters, " ", $r['descri']), 0, 75)."'";
}

// carico i dati per la select orderman del jquery
$query = 'SELECT id,description FROM `' . $gTables['orderman'] . '` WHERE stato_lavorazione = 0 ORDER BY `id`';
$result = gaz_dbi_query($query);
$orderman="0:'Nessuna lavorazione associata'";
$invalid_characters = array("'", ",", ":");
while ($r = gaz_dbi_fetch_array($result)) {
	$orderman .= ", ".$r['id'].":'".substr(str_replace($invalid_characters, " ", $r['description']), 0, 40)."'";
}

if ($_POST) { // accessi successivi
	$form['mese']=intval($_POST['mese']);
	$form['anno']=intval($_POST['anno']);
	$month_res = getWorkedHours($form['mese'],$form['anno']);
	$cols=getWorkers($form['mese'],$form['anno']);
} else { // al primo accesso
	if (isset($_GET['yearmonth'])){ // se mi è stato passato il mese come referenza lo uso
		$refyearmonth=explode("-",$_GET['yearmonth']);
		$form['anno'] = intval($refyearmonth[0]);
		$form['mese'] = intval($refyearmonth[1]);
	} else { // altrimenti prendo il mese corrente
		$dto = new DateTime();
		$form['anno'] = $dto->format("Y");
		$form['mese'] = $dto->format("m");
	}
	$month_res = getWorkedHours($form['mese'],$form['anno']);
	$cols = getWorkers($form['mese'],$form['anno']);
}

require("../../library/include/header.php");
?>
<script type="text/javascript">
    $(function () {
		var wpx = $(window).width()*0.97;
		$("#dialog_worker_card").dialog({ autoOpen: false });
		$('.dialog_worker_card').click(function() {
			var id = $(this).attr('id_staff');
			var hourly_cost = $(this).attr('hourly_cost');
			var id2 = $(this).attr('date');
			const d2 = new Date(id2);
			const ye = new Intl.DateTimeFormat('it', { year: 'numeric' }).format(d2);
			const mo = new Intl.DateTimeFormat('it', { month: 'short' }).format(d2);
			const da = new Intl.DateTimeFormat('it', { day: '2-digit' }).format(d2);
			var jsondatastr = null;
			var deleted_rows = [];
			$("p#iddescri").html(id+' '+$(this).attr("staff_name")+' giorno <b>'+da+' '+mo+' '+ye+'</b>');
			$.ajax({ // chiedo tutte le registrazioni fatte nel cartellino presenze per quel giorno
				'async': false,
				url:"./get_pres.php",
				type: "POST",
				dataType: 'text',
				data: {id_staff: id, date: id2},
				success:function(jsonstr) {
					//alert(jsonstr);
					jsondatastr = jsonstr;
				}
			});

			var myAppendGrid = new AppendGrid({ // creo la tabella vuota
			  element: "tblAppendGrid",
			  uiFramework: "bootstrap4",
			  iconFramework: "default",
			  initRows: 1,
			  columns: [
				{
				  name: "id",
				  display: "ID",
				  type: "hidden"
				},
				{
				  name: "start_work",
				  display: "Ora di inizio",
				  type: "time"
				},
				{
				  name: "end_work",
				  display: "ora di fine",
				  type: "time"
				},
				{
				  name: "id_work_type",
				  display: "Tipo lavoro",
				  type: "select",
					ctrlOptions: {
					<?php echo $work_types;?>
					},

				},
				{
				  name: "min_delay",
				  display: "Ritardo in minuti",
				  type: "number",
				  ctrlAttr: {
					  min: 0,
					  max: 60
				  }
				},
				{
				  name: "id_orderman",
				  display: "Lavorazione",
					type: "select",
					ctrlOptions: {
					<?php echo $orderman;?>
					}
				},
				{
				  name: "note",
				  display: "Annotazione",
				  type: "textarea",
				  ctrlAttr: {
						"cols": 1
					}
				},
				{
				  name: "hourly_cost",
				  display: "Costo orario",
				  type: "text",
				    value : hourly_cost
				},
			  ],
			  beforeRowRemove: function(caller, rowIndex) {
				 var rowValues = myAppendGrid.getRowValue(rowIndex);
				 deleted_rows.push(rowValues.id);
				//alert("row index:" + rowIndex + " values:" + JSON.stringify(deleted_rows));
				return confirm("Sei sicuro di voler rimuovere la riga?");
				}
			});

			if (jsondatastr){
			// popolo la tabella
			var jsondata = $.parseJSON(jsondatastr);
			myAppendGrid.load( jsondata );
			}


			$( "#dialog_worker_card" ).dialog({
				minHeight: 1,
				width: wpx,
				modal: "true",
				show: "blind",
				hide: "explode",
				buttons: {
					delete:{
						text:'Annulla',
						'class':'btn btn-default',
						click:function (event, ui) {
							$(this).dialog("close");
						}
					},
					confirm :{
					  text:'CONFERMA',
					  'class':'btn btn-success pull-right btn-conferma',
					  click:function() {
						var msg = null;
						$.ajax({ // registro con i nuovi dati il cartellino presenze
							'async': false,
							data: {rec_pres: myAppendGrid.getAllValue(), date: id2, id_staff: id, deleted_rows: deleted_rows},
							type: 'POST',
							url: './rec_pres.php',
							success: function(output){
								msg = output;
								console.log(msg);
							}
						});
						if (msg) {
							alert(msg);
						} else {
							window.location.replace("./employee_timesheet.php?yearmonth="+id2);
						}
					  }
					}
				}
			});
			$("#dialog_worker_card" ).dialog( "open" );
		});
	});

$(document).ready(function(){
	$('[data-toggle="popover"]').popover({
		html: true
	});
});
function printPdf(urlPrintDoc){
	$(function(){
		$('#framePdf').attr('src',urlPrintDoc);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
    $("html, body").delay(100).animate({scrollTop: $('#framePdf').offset().top},200, function() {
        $("#framePdf").focus();
    });
		$('#closePdf').on( "click", function() {
			$('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
		});
	});
};
</script>
<?php
$script_transl = HeadMain(0,array('custom/autocomplete','appendgrid/AppendGrid'));
$gForm = new humresForm();

?>
<style>
#tblAppendGrid .form-control { height: 28px; }
.ui-dialog .ui-dialog-buttonpane .ui-dialog-buttonset { float: unset !important; }
.ui-dialog { z-index: 1000 !important; font-size: 12px;}
.btn-conferma {	color: #fff !important; background-color: #f0ad4e !important; border-color: #eea236 !important; }
</style>
<form method="POST" id="form">
<div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
	<div class="col-lg-12">
		<div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
		<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
	</div>
	<iframe id="framePdf"  style="height: 100%; width: 100%" src="../../library/images/wait_spinner.html"></iframe>
</div>
<div class="text-center FacetFormHeaderFont"><b><?php echo $script_transl['title']; ?></b></div>
<div class="panel panel-info">
	<div class="row">
		<div class="col-lg-12 text-center">
			<?php
      $gazTimeFormatter->setPattern('MMMM');
			echo "\t <select name=\"mese\" onchange=\"this.form.submit()\">\n";
			echo "\t <option value=\"\"> - - - - - - </option>\n";
			for ($counter = 1;$counter <= 12;$counter++) {
				$selected = "";
				if ($counter == $form['mese']) $selected = "selected";
        $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
				echo "\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
			}
			echo "\t </select>\n";
			echo "\t <select name=\"anno\" onchange=\"this.form.submit()\">\n";
			echo "\t <option value=\"\"> - - - - </option>\n";
      $dmiddle=($form['anno']>=2000)?$form['anno']:date("Y");
			for ($counter = $dmiddle - 10;$counter <= $dmiddle + 10;$counter++) {
				$selected = "";
				if ($counter == $form['anno']) $selected = "selected";
				echo "\t <option value=\"$counter\"  $selected >$counter</option>\n";
			}
			echo "\t </select>\n";
			$col = cal_days_in_month(CAL_GREGORIAN, $form['mese'], $form['anno']); //giorni nel mese e anno selezionato

			?>
		</div>
	</div>
	<div class="table-responsive">

		<table class="table table-hover" border="1" cellpadding="1">
			 <thead>
			 </thead>
			 <tbody>

				<?php
        $gazTimeFormatter->setPattern('E');
				foreach ($cols as $oper){
					?>
					<tr>
						<td style="line-height:6px;" >&nbsp;
						</td>
					</tr>
					<tr>
					<td class="bg-success">
					<a class="btn btn-edit btn-sm" href="./admin_staff.php?Update&codice=<?php echo substr($oper['id_clfoco'],-6); ?>"><i class="glyphicon glyphicon-edit"> </i><?php echo "<br/>".$oper['ragso1']," ",$oper['ragso2']; ?></a>
					</td>
					<?php
					for($c=0;$c<$col ; $c++){
            $week_day=$gazTimeFormatter->format(new DateTime($form['anno']."-".$form['mese']."-".($c+1)));
						if ($week_day=="sab"){
							$td[$c]='bg-warning text-center';
							$bt[$c]='btn-warning';
						}elseif ($week_day=="dom"){
							$td[$c]='bg-danger text-center';
							$bt[$c]='btn-danger';
						}else {
							$td[$c]='text-center';
							$bt[$c]='btn-default';
						}
						?>
						<td class="<?php echo $td[$c]; ?> text-center" title="<?php echo (isset($month_res[$c+1][$oper['id_staff']]['mov_note'])&&!empty($month_res[$c+1][$oper['id_staff']]['mov_note']))?$month_res[$c+1][$oper['id_staff']]['mov_note']:'nessuna nota'; ?>">
							<a class="btn btn-xs <?php echo $bt[$c]; ?> dialog_worker_card" staff_name="<?php echo (isset($oper['ragso1']))?$oper['ragso1']:''," ",(isset($oper['ragso2']))?$oper['ragso2']:''; ?>" id_staff="<?php echo (isset($oper['id_staff']))?$oper['id_staff']:''; ?>" hourly_cost="<?php echo (isset($oper['last_hourly_cost']))?$oper['last_hourly_cost']:0; ?>"  date="<?php echo $form['anno'],"-",sprintf("%02d", $form['mese']),"-",sprintf("%02d", $c+1); ?>" >
								<i class="glyphicon glyphicon-edit"><br/><?php echo ($c+1).'<br/>'.$week_day; ?></i>
							</a>
						</td>
						<?php
					}
					?>
					</tr>
					<tr class="bg-info">

					<td>
						<?php echo "Ore normali"; ?>
					</td>
					<?php
					for($c=1;$c<$col+1 ; $c++){
						?>
						<td class="<?php echo $td[$c-1]; ?>" ><b>
						<?php echo (isset($month_res[$c][$oper['id_staff']]['hours_normal'])&&$month_res[$c][$oper['id_staff']]['hours_normal']>=0.01)?floatval($month_res[$c][$oper['id_staff']]['hours_normal']):'-'; ?></b>
						</td>
						<?php
					}
					?> </tr><tr>
					<td class="text-warning" >
						<?php echo "Straordinario"; ?>
					</td>
					<?php
					for($c=1;$c<$col+1 ; $c++){
						?>
						<td class="<?php echo $td[$c-1]; ?> text-warning" >
						<?php if (isset($month_res[$c][$oper['id_staff']]['hours_extra']) && $month_res[$c][$oper['id_staff']]['hours_extra']>0 ){
							?>
							<a style="cursor: help;" data-toggle="popover" tabindex="<?php echo $c-1; ?>" data-placement="auto" data-trigger="focus" title="Ore di straordinario" data-content="<?php echo (isset($month_res[$c][$oper['id_staff']]['extra_des']))?$month_res[$c][$oper['id_staff']]['extra_des']:''; ?>">
							<?php
						}
						echo (isset($month_res[$c][$oper['id_staff']]['hours_extra'])&&$month_res[$c][$oper['id_staff']]['hours_extra']>=0.01)?floatval($month_res[$c][$oper['id_staff']]['hours_extra']):''; ?>
						</a>
						</td>
						<?php
					}
					?> </tr><tr>
					<td >
						<?php echo "Festivo e notturno"; ?>
					</td>
					<?php
					for($c=1;$c<$col+1 ; $c++){
						?>
						<td class="<?php echo $td[$c-1]; ?>" >
						<?php if (isset($month_res[$c][$oper['id_staff']]['hours_other']) && $month_res[$c][$oper['id_staff']]['hours_other']>0 ){
							?>
							<a style="cursor: help;" data-toggle="popover" tabindex="<?php echo $c-1; ?>" data-placement="auto" data-trigger="focus" title="Ore festive e notturne" data-content="<?php echo (isset($month_res[$c][$oper['id_staff']]['other_des']))?$month_res[$c][$oper['id_staff']]['other_des']:''; ?>">
							<?php
						}
						echo (isset($month_res[$c][$oper['id_staff']]['hours_other']) && $month_res[$c][$oper['id_staff']]['hours_other']>=0.01)?floatval($month_res[$c][$oper['id_staff']]['hours_other']):''; ?>
						</a>
						</td>
						<?php
					}
					?> </tr><tr>
					<td ">
						<?php echo "Assenza"; ?>
					</td>
					<?php
					for($c=1;$c<$col+1 ; $c++){
						?>
						<td class="<?php echo $td[$c-1]; ?>" >
						<?php if (isset($month_res[$c][$oper['id_staff']]['hours_absence']) && $month_res[$c][$oper['id_staff']]['hours_absence']>0 ){
							?>
							<a style="cursor: help;" data-toggle="popover" tabindex="<?php echo $c-1; ?>" data-placement="auto" data-trigger="focus" title="Ore di assenza" data-content="<?php echo (isset($month_res[$c][$oper['id_staff']]['absence_des']))?$month_res[$c][$oper['id_staff']]['absence_des']:''; ?>">
							<?php
						}
							echo (isset($month_res[$c][$oper['id_staff']]['hours_absence'])&&$month_res[$c][$oper['id_staff']]['hours_absence']>=0.01)?floatval($month_res[$c][$oper['id_staff']]['hours_absence']):''; ?>
						</a>
						</td>
						<?php
					}
					?>
					</tr>

					<?php
				}
				?>
			</tbody>
		</table>
	</div>
	<div class="row text-center" style="padding-top:12px;">
		<?php
    $gazTimeFormatter->setPattern('MMMM yyyy');
		echo "<td align=\"center\"><a class=\"btn btn-warning\" title=\"Stampa documento PDF\" style=\"cursor:pointer;\" onclick=\"printPdf('print_timesheet.php?year=". $form['anno'] ."&month=". $form['mese'] ."')\">".$script_transl['print'].$script_transl['title'].' '.ucwords($gazTimeFormatter->format(new DateTime($form['anno'].'-'.$form['mese'].'-01')))." <i class=\"glyphicon glyphicon-print\" ></i></a>";
		?>
	</div>
</div>
	<div style="display:none" id="dialog_worker_card" title="Cartellino presenze">
        <p><b>Dipendente:</b></p>
		<p class="ui-state-highlight" id="iddescri"></p>
		<table id="tblAppendGrid"></table>
	</div>






</form>
</div>
<?php
require("../../library/include/footer.php");
?>
