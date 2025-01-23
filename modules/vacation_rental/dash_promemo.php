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

}else{
  $form['start']=$_POST['start'];
  $form['end']=$_POST['end'];
}
$checkimp=(isset($_POST['set']) && $_POST['set']=="IMPORTI")?"checked":'';
if ((isset($_POST['set']) && $_POST['set']=="IMPORTI") ){// se selezionato
  $checkimp="checked";
}elseif(!isset($_POST['set'])){ // di default
  $checkimp="checked";
  $_POST['set']="IMPORTI";
}else{

  $checkimp="";
}
$checkocc=(isset($_POST['set']) && $_POST['set']=="OCCUPAZIONE")?"checked":'';
?>
<script>
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

    });
  }else{
    alert('Il file richiesto fa parte della versione PRO di questo modulo: contattare lo sviluppatore');
  };
	$('#closePdf').on( "click", function() {
		$("#titolo").empty();
		$('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
	});
};
</script>

  <div id="generale" class="tab-pane fade in ">
    <form method="post" id="sbmt-form" enctype="multipart/form-data">
    	<div class="framePdf panel panel-success" style="display: none; position: fixed; left: 5%; top: 5px">
          <div class="col-lg-12">
            <div class="col-xs-11" id="titolo" ></div>
            <div class="col-xs-1"><span><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></span></div>
          </div>
          <iframe id="framePdf"  style="height: 100%; width: 100%" src="../../library/images/wait_spinner.html"></iframe>
      </div>
      <div class="panel panel-info col-sm-12">
        <div class="box-header company-color">
          <h4 class="box-title"><i class="glyphicon glyphicon-blackboard"></i> Riepilogo Vacation rental</h4>
          <a class="pull-left" style="cursor:pointer;" onclick="openframe('../../modules/vacation_rental/total_availability_it.php?price','<h3>Calendario generale</h3>')" data-toggle="modal" data-target="#iframe"> <i class="glyphicon glyphicon-calendar" title="Calendario della disponibilità"></i></a>
          <a class="pull-center" href="../vacation_rental/report_booking.php" style="cursor:pointer;"><i class="glyphicon glyphicon-tasks" title="vai alle prenotazioni"></i></a>
		  <a class="pull-right dialog_grid" id_bread="<?php echo $grr['id_bread']; ?>" style="cursor:pointer;"><i class="glyphicon glyphicon-cog"></i></a>
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
					  <td class="text-center"><?php echo "€ ",number_format($tot_promemo['totalprice_booking'], 2, '.', ''); ?></td>
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
   <iframe src="../../modules/vacation_rental/flot_graph.php?start=<?php echo $form['start'];?>&end=<?php echo $form['end'];?>&set=<?php echo $_POST['set'];?>" width="100%" height="800px" title="Grafico statistiche"></iframe>
  </div>
  <?php }else{
    echo "<br>Il grafico interattivo delle statische non è dispobile in questa versione";
  }

  ?>
