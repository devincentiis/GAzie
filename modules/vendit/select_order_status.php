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
$msg = ['err' => [], 'war' => []];

function getDateLimit($status)
  {
    global $gTables,$admin_aziend;
    $sql="SELECT MIN(".$gTables['tesbro'].".datemi) AS mindatemi FROM ".$gTables['rigbro']."
          LEFT JOIN ".$gTables['tesbro']." ON ".$gTables['rigbro'].".id_tes = ".$gTables['tesbro'].".id_tes
          LEFT JOIN ".$gTables['rigdoc']." ON ( ".$gTables['rigbro'].".id_tes = ".$gTables['rigdoc'].".id_order AND ".$gTables['rigbro'].".codart = ".$gTables['rigdoc'].".codart )
          WHERE ".$gTables['tesbro'].".tipdoc LIKE 'VO_' AND ".$gTables['rigdoc'].".id_order > 0";
    $rs = gaz_dbi_query ($sql);
    return gaz_dbi_fetch_array($rs);
}

$setDateLimit=false;
if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
  $form['hidden_req'] = '';
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $date = new DateTime('first day of this month');
  $date->modify('-3 months');
  $form['dateini'] = $date->format('d/m/Y');
  $form['datefin'] = date("d/m/Y");
  $form['status'] = 3; // Inevasi e con residuo
} else { // accessi successivi
  $form['hidden_req']=htmlentities($_POST['hidden_req']);
  $form['ritorno']=$_POST['ritorno'];
  $form['dateini']=substr($_POST['dateini'],0,10);
  $form['datefin']=substr($_POST['datefin'],0,10);
  $form['status'] = intval($_POST['status']);
  if ($form['hidden_req'] == 'status' && $form['status'] > 0 && $form['status'] < 9 ) {
    // solo se ho modificato la scelta degli status da visualizzare e non è Evasi o Tutti allora reimposto anche i limiti di date
    $mindatemi = getDateLimit($form['status']);
    $form['dateini'] = gaz_format_date($mindatemi['mindatemi'],false,true);
    $form['hidden_req'] = '';
  }
  if (isset($_POST['return'])) {
    header("Location: ".$form['ritorno']);
    exit;
  }
}

//controllo i campi
$utsini= mktime(0,0,0,substr($form['dateini'],3,2), substr($form['dateini'],0,2), substr($form['dateini'],6,4));
$utsfin= mktime(0,0,0,substr($form['datefin'],3,2), substr($form['datefin'],0,2), substr($form['datefin'],6,4));
if ($utsini > $utsfin) {
  $msg['err'][] = "wrongdate";
}
// fine controlli

if (isset($_POST['print']) && $msg=='') {
  $_SESSION['print_request']=array('script_name'=>'stampa_order_status','utsdateini'=>$utsini,'utsdatefin'=>$utsfin,'status'=>$form['status']);
  header("Location: sent_print.php");
  exit;
}
require("../../library/include/header.php");
$script_transl=HeadMain();
$gForm = new GazieForm();
if (count($msg['err']) >= 1) {
  echo '<div class="text-center">'.$gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err')."</div>";
}
?>
<script>
$(function () {
  $(".datepicker").datepicker({dateFormat: 'dd-mm-yy'});
  $("#dateini").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
  $("#dateini").change(function () {
    this.form.submit();
  });
  $("#datefin").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
  $("#datefin").change(function () {
    this.form.submit();
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
			$('.framePdf').css({'display': 'none'});
		});
	});
};
</script>
<div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
	<div class="col-lg-12">
		<div class="col-xs-11"><h4><?php echo $script_transl['print']; ?></h4></div>
		<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
	</div>
	<iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
</div>
<form method="POST" name="select">
  <input type="hidden" value="<?= $form['hidden_req'] ?>" name="hidden_req" />
  <input type="hidden" value="<?= $form['ritorno'] ?>" name="ritorno" />
<div class="FacetFormHeaderFont text-center bg-info"><?= $script_transl['title']; ?></div>
<div>
  <div class="form-group text-center">
    <label for="dateini" class="form-label"><?= $script_transl['dateini']; ?>: </label>
    <input type="text" class="" id="dateini" name="dateini" value="<?php echo $form['dateini']; ?>" maxlength="10" />
  </div>
</div>
<div>
  <div class="form-group text-center">
    <label for="datefin" class="form-label"><?php echo $script_transl['datefin']; ?>: </label>
    <input type="text" class="" id="datefin" name="datefin" value="<?php echo $form['datefin']; ?>" maxlength="10"/>
  </div>
</div>
<div>
  <div class="form-group text-center">
    <label for="status" class="form-label"><?php echo $script_transl['status']; ?>: </label>
    <?php $gForm->variousSelect('status', $script_transl['status_value'], $form['status'], '', 0, 'status'); ?>
  </div>
</div>
<?php
if ( count($msg['err']) == 0) {
  $m=getOrders($form['dateini'],$form['datefin'],$form['status']);
  if (sizeof($m) > 0) {
    $ctr_mv='';
    echo "<table class=\"Tlarge table table-striped table-responsive\"><tr>";
    $linkHeaders=new linkHeaders($script_transl['header']);
    $linkHeaders->output();
    echo "</tr>";
		foreach ($m AS $key => $mv) {
      // start prepare column
      $colqua = '';
      $colimp = '';
      $colsta = '';
      switch($mv['stato_evasione']) { // 0 = Inevaso, 1 = Evasione parziale, 2 = Evaso
        case 0: // 0 = Inevaso
          $colqor = $mv['unimis'].' '.floatval(round($mv['totquanti_da_evadere'],5));
          $colqev = '<b class="text-danger"> - - - </b>';
          if ($mv['zerorow']){ // ho un rigo dell'ordine a zero, segnalo e propongo la modifica
            $colimp = '<span class="text-danger"><b> <a href="./admin_broven.php?id_tes='.$mv['id_tes'].'&Update" target="_blank" title="Modifica righi con importo a zero" class="text-danger"> <i class="fa fa-exclamation-triangle"></i> </a> € '.gaz_format_number($mv['totimpbro_da_evadere']).' - '.gaz_format_number($mv['totimpdoc_evaso']).'</b></span>';
          } else {
            $colimp = '<span  class="text-default"> € '.gaz_format_number($mv['totimpbro_da_evadere']).' <b class="text-danger"> - '.gaz_format_number($mv['totimpdoc_evaso']).'</b></span>';;
          }
          $colsta = '<a class="btn btn-xs btn-danger" href="select_evaord.php?id_tes='.$mv['id_tes'].'"  title="Evadi" target="_blank" > Inevaso </a>';
        break;
        case 1: // 1 = Evasione parziale
          $colqor = $mv['unimis'].' '.floatval(round($mv['totquanti_da_evadere'],5));
          $colqev = '<b class="text-warning">'.$mv['unimis'].' '.floatval(round($mv['totquanti_evaso'],5)).'</b>';
          if ($mv['zerorow']){ // ho un rigo dell'ordine a zero, segnalo e propongo la modifica
            $colimp = '<b class="text-danger"> <a href="./admin_broven.php?id_tes='.$mv['id_tes'].'&Update" target="_blank" title="Modifica righi con importo a zero" class="text-danger"> <i class="fa fa-exclamation-triangle"></i> </a> € '.gaz_format_number($mv['totimpbro_da_evadere']).'</b> <b class="text-warning">  - '.gaz_format_number($mv['totimpdoc_evaso']).'</b>';
          } else {
            $colimp = '<span  class="text-default"> € '.gaz_format_number($mv['totimpbro_da_evadere']).' <b class="text-warning">  - '.gaz_format_number($mv['totimpdoc_evaso']).'</b></span>';
          }
          $colsta = '<a class="btn btn-xs btn-warning" href="select_evaord.php?id_tes='.$mv['id_tes'].'" title="Evadi saldo" target="_blank" > Parzialmente evaso </a>';
          foreach ($mv['doc'] as $k=>$d){
            $colsta .= '<br/><a class="btn btn-xs btn-default" href="stampa_docven.php?id_tes='.$k.'"  target="_blank">Doc. n.'.$d['numdoc'].' del '.gaz_format_date($d['datemi']).'</a>';
          }
        break;
        case 2: // 2 = Evaso
          $colqor = $mv['unimis'].' '.floatval(round($mv['totquanti_da_evadere'],5));
          $colqev = $mv['unimis'].' '.floatval(round($mv['totquanti_evaso'],5));
          if ($mv['zerorow']){ // ho un rigo dell'ordine a zero, segnalo e propongo la modifica
            $colimp = '<span class="text-danger"> <b> <a href="./admin_broven.php?id_tes='.$mv['id_tes'].'&Update" target="_blank" title="Modifica righi con importo a zero" class="text-danger"> <i class="fa fa-exclamation-triangle"></i> </a> € '.gaz_format_number($mv['totimpbro_da_evadere']).'</b> - '.gaz_format_number($mv['totimpdoc_evaso']).'</span>';
          } else {
            $colimp = '<span  class="text-default"> € '.gaz_format_number($mv['totimpbro_da_evadere']).' - '.gaz_format_number($mv['totimpdoc_evaso']).'</span>';
          }
          $colsta = '<div class="btn btn-xs btn-success" style="cursor: default;">Evaso </div>';
          foreach ($mv['doc'] as $k=>$d){
            $colsta .= '<br/><a class="btn btn-xs btn-default" href="stampa_docven.php?id_tes='.$k.'"  target="_blank">Doc. n.'.$d['numdoc'].' del '.gaz_format_date($d['datemi']).'</a>';
          }
        break;
      }
      // end prepare column
      echo "<tr>";
      echo '<td class="FacetDataTD text-center"><a class="btn btn-xs btn-success" href="./admin_broven.php?id_tes='.$mv['id_tes'].'&Update" target="_blank">'.$mv['id_tes']."</a></td>";
      echo '<td class="FacetDataTD text-center">'.$mv['numdoc']."</td>";
      echo '<td class="FacetDataTD text-center">'.gaz_format_date($mv['datemi'])."</td>";
      echo '<td class="FacetDataTD text-center"><a href="./admin_client.php?codice='.substr($mv['clfoco'],-6).'&Update" target="_blank">'.$mv['ragso1'].'</a></td>';
      echo '<td class="FacetDataTD text-center">'.$colqor."</td>";
      echo '<td class="FacetDataTD text-center">'.$colqev."</td>";
      echo '<td class="FacetDataTD text-center">'.$colimp."</td>";
      echo '<td class="FacetDataTD text-center">'.$colsta."</td>";
      echo "</tr>\n";
    }
      echo '<tr class="text-bold text-right">';
      echo '<td colspan=4>TOTALI:</td>';
      echo '<td class="FacetDataTD text-center">'.$mv['tot']['qtotord']."</td>";
      echo '<td class="FacetDataTD text-center">'.$mv['tot']['qtoteva']."</td>";
      echo '<td class="FacetDataTD text-center"> € '.gaz_format_number($mv['tot']['vtotord']).' - '.gaz_format_number($mv['tot']['vtoteva'])."</td>";
      echo '<td></td>';
      echo "</tr>\n";
      echo '<tr><td class="FacetFooterTD text-center" colspan=8>
      <a class="btn btn-info" title="PDF per stampa" style="cursor:pointer;" onclick="printPdf(\'print_order_status.php?utsini='.$utsini."&utsfin=". $utsfin ."&status=". $form['status'] ."')\">Visualizza PDF <i class=\"glyphicon glyphicon-print\" ></i></a></td></tr>";
  }
  echo "</table>\n";
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
