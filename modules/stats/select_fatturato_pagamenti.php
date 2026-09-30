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
$msg = ['err'=>[],'war'=>[]];
$movres=[];
$totres=['noacc'=>0];
$totale=0.00;
$gForm = new statsForm();
$anagrafica = new Anagrafica();


if (!isset($_POST['ritorno'])) { // al primo accesso allo script
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
  $form['datini'] = date('01/01/Y');
  $form['datfin'] = date('d/m/Y', strtotime('last day of previous month'));

} else { // le richieste successive
  $form['ritorno'] = $_POST['ritorno'];
  $form['hidden_req'] = $_POST['hidden_req'];
  $form['datini'] = substr($_POST['datini'],0,10);
  $datini = gaz_format_date($form['datini'],true); // adatto al db;
  $utsemi = gaz_format_date($form['datini'],2); // mktime
  if (!gaz_format_date($form['datini'],'chk')) $msg['err'][] = "datini";
  $form['datfin'] = substr($_POST['datfin'],0,10);
  $datfin = gaz_format_date($form['datfin'],true); // adatto al db;
  $utsemi = gaz_format_date($form['datfin'],2); // mktime
  if (!gaz_format_date($form['datfin'],'chk')) $msg['err'][] = "datfin";
}

if (isset($_POST['preview'])){
	$query="SELECT ".$gTables['tesdoc'].".id_tes, ".$gTables['tesdoc'].".id_con, ".$gTables['tesdoc'].".clfoco, ".$gTables['tesdoc'].".pagame, ".$gTables['tesdoc'].".tipdoc, ".$gTables['tesdoc'].".seziva, ".$gTables['tesdoc'].".protoc, ".$gTables['tesdoc'].".datfat, ".$gTables['tesdoc'].".numfat, ".$gTables['tesdoc'].".id_contract, ".
  $gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2
FROM ".$gTables['tesdoc']."
LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['tesdoc'].".clfoco = ".$gTables['clfoco'].".codice
LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id
WHERE tipdoc LIKE 'F%' AND datfat BETWEEN '".$datini."' AND '".$datfin."' GROUP BY seziva, protoc, datfat ORDER BY datfat, seziva, protoc";
	$resoper = gaz_dbi_query($query);
  while($r=gaz_dbi_fetch_array($resoper) ) {
    if ($r['tipdoc'] == 'FAI'||$r['tipdoc'] == 'FAA') {
      $r['descridoc'] = "Fattura Immediata";
    } elseif($r['tipdoc'] == 'FAF'){
      $r['descridoc'] = "Autofattura (TD26)";
    } elseif ($r['tipdoc'] == 'FAD') {
      $r['descridoc'] = "Fattura Differita";
    } elseif ($r['tipdoc'] == 'FAP'||$r['tipdoc'] == 'FAQ') {
      $r['descridoc'] = "Parcella";
    } elseif ($r['tipdoc'] == 'FNC') {
      $r['descridoc'] = "Nota Credito";
    } elseif ($r['tipdoc'] == 'FND') {
      $r['descridoc'] = "Nota Debito";
    } else {
      $r['descridoc'] = "DOC.SCONOSCIUTO";
    }
    $importo = gaz_dbi_get_row($gTables['rigmoc'], 'id_tes', $r['id_con'], "AND codcon = ".$r['clfoco']);
    if ($importo && $importo['import'] >= 0.01 ) {
      $r['class']='';
      if (substr($r['tipdoc'],2,1)=='C'){
        $importo['import'] = -$importo['import'];
        $r['class']='bg-danger text-danger';
      }
      $totale+=$importo['import'];
      if (isset($totres[$r['pagame']])) {
        $totres[$r['pagame']] +=  $importo['import'];
      } else {
        $totres[$r['pagame']] =  $importo['import'];
      }
      $r['importo'] = $importo['import'];
    } else {
      // incremento il numero di documenti non contabilizzati per metterli in evidenza
      $totres['noacc']++;
      $r['importo'] = 'NON CONTABILIZZATA';
      $r['class']='bg-danger text-danger text-bold';
    }
    $movres[$r['pagame']][] = $r;
  }
}



if (isset($_POST['Print'])) {
   if (empty($form['anno'])) {
      $msg .= "0+";
   }
   if (empty($msg)) { //non ci sono errori
      $_SESSION['print_request'] = $form;
      $_SESSION['print_request']['ckdata']=$_POST['ckdata'];
      header("Location: invsta_analisi_acquisti_clienti.php");
      exit;
   }
}

if (isset($_POST['Return'])) {
   header("Location:stats_vendit.php");
   exit;
}
require("../../library/include/header.php");
$script_transl = HeadMain(0,['custom/autocomplete']);
?>
<script>
$(function() {
  $("#datini").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
  $("#datfin").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
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
<form method="POST" id="myForm">
<div style="display:none" id="dialog_toggle" title="">
  <input type="password" value="" id="toggle_pin" name="toggle_pin" maxlength=4 size=10 />
</div>
<div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
	<div class="col-lg-12">
		<div class="col-xs-11"><h4><?php echo $script_transl['print']; ?></h4></div>
		<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
	</div>
	<iframe id="framePdf"  style="height: 100%; width: 100%" src="../../library/images/wait_spinner.html"></iframe>
</div>
<?php
echo '<input type="hidden" name="ritorno" value="' . $form['ritorno'] . '">';
echo '<input type="hidden" value="' . $form['hidden_req'] . '" name="hidden_req" id="hidden_req" />';
?>
<div class="FacetFormHeaderFont text-center bg-info">Vendite per tipologia di pagamento</div>
<div class="row">
  <div class="col-sm-12 col-md-6 text-right">
    <div class="form-group">
      <label for="datini" class="control-label text-right">Inizio:</label>
      <input class="" type="text" value="<?php echo $form['datini']; ?>" id="datini" name="datini" maxlength=10 />
    </div>
  </div>
  <div class="col-sm-12 col-md-6">
    <div class="form-group">
      <label for="datfin" class="control-label text-right">Fine:</label>
      <input class="" type="text" value="<?php echo $form['datfin']; ?>" id="datfin" name="datfin" maxlength=10 />
    </div>
  </div>
</div><!-- chiude row  -->
<div class="text-center bg-info text-info">
  <input class="btn btn-info" type="submit" name="preview" id='preview' value="Anteprima" />
</div>
</form>
<?php
if (count($movres)>=1) {
?>
<div class="table-responsive">
	<table class="table table-striped" >
    <tr><th>Data</th><th>Prot.</th><th>N.</th><th>Cliente</th><th class="text-center">Importo</th></tr>
<?php
foreach($movres as $kpag=>$vpag){
  $pagame=gaz_dbi_get_row($gTables['pagame'], 'codice', $kpag);
  echo '<tr><td colspan=4 > <b> Pagamento: '.$pagame['descri'].'</b></td></tr>';
  foreach($vpag as $v){
    // Data
    echo '<tr class="'.$v['class'].'"><td>'.gaz_format_date($v['datfat']).'</td>';
    // Protocollo/Sezione
    echo '<td>'.$v['protoc'].'</td>';
    // Numero
    echo '<td>'.$v['descridoc'].' n.'.$v['numfat'].'/'.$v['seziva'].'</td>';
    // Cliente
    echo '<td>'.$v['ragso1']. '' .$v['ragso2'].'</td>';
    // Importo
    if ($v['importo']<>'NON CONTABILIZZATA') {
      $v['importo']=gaz_format_number($v['importo']);
    }
    echo '<td class="text-right">'.$v['importo'].'</td></tr>';
  }
}
?>
  </table>
</div>
<div class="table-responsive">
	<table class="Tmiddle table-striped" >
    <tr><td colspan=3 class="bg-info text-center "><b>TOTALI RAGGRUPPATI</b></td></tr>
<?php
  echo '<tr><td class="bg-warning"><b>Pagamenti:</b></td><td class="text-center"></td></tr>';
  foreach($totres as $k=>$v){
    if ($k=='noacc'){
      if ($v>=1) {
        echo '<tr><td colspan=2 class="text-danger bg-danger text-bold">'.$v.' FATTURE NON CONTABILIZZATE</td></tr>';
      }
    } else {
      $pagame=gaz_dbi_get_row($gTables['pagame'], 'codice', $k);
      echo '<tr><td>'.$k.' - '.$pagame['descri'].'</td><td class="text-right">'.gaz_format_number($v).'</td></tr>';
    }
  }
  echo '<tr><td class="text-info bg-info text-bold">TOTALE CONTABILIZZATO</td><td class="text-info bg-info text-bold text-right">€ '.gaz_format_number($totale).'</td></tr>';

?>
  </table>
<?php
	echo '<div class="text-right col-sm-12 col-md-9"><a class="btn btn-info" title="Stampa documento PDF" style="cursor:pointer;" onclick="printPdf(\'stampa_fatturato_pagamenti.php?datini='.$form['datini']."&datfin=". $form['datfin'] ."')\">".$script_transl['print']." <i class=\"glyphicon glyphicon-print\" ></i></a></div>";
  echo '</div>';
}
require("../../library/include/footer.php");
?>
