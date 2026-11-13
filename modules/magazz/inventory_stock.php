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
//require("../vendit/lib.function.php");
$admin_aziend = checkAdmin();
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
$gForm = new magazzForm;
$msg = '';
if (!isset($_POST['ritorno'])) { //al primo accesso allo script
  $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
	$form['datref']=date("d-m-Y");
  $rs_first = gaz_dbi_dyn_query("codice", $gTables['catmer'], 1, "codice ASC", 0, 1);
  $cm_first = gaz_dbi_fetch_array($rs_first);
  $form['catmer'] = $cm_first['codice'];
} else { //nelle  successive entrate
    if (isset($_POST['Return'])) {
      header("Location: " . $_POST['ritorno']);
      exit;
    }
    $form['datref'] = substr($_POST['datref'],0,10);
    $form['catmer'] = intval($_POST['catmer']);
}
$data = getInventoryData($form['datref'],$form["catmer"],$admin_aziend['decimal_price']);

if (isset($_POST['export'])) {
  $acc=[];
  foreach ($data as $k => $v) {
    if (count($acc)==0) {
      $acc[0] = ['Codice', 'Descrizione articolo', 'U.M.', 'Valore attuale', 'Giacenza', 'Valore giacenza'];
    }
    $acc[$k][0] = $k;
    $acc[$k][1] = $v['i_d'];
    $acc[$k][2] = $v['i_u'];
    $acc[$k][3] = $v['v_a'];
    $acc[$k][4] = $v['g_a'];
    $acc[$k][5] = round($v['v_g'],5);
  }
  $spreadsheet = new Spreadsheet();
  $spreadsheet->setActiveSheetIndex(0);
  $spreadsheet->getActiveSheet()->fromArray($acc);
  $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
  $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(90);
  $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(8);
  $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(15);
  $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(15);
  $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
  $spreadsheet->getActiveSheet()->getStyle('A:B')->getAlignment()->setHorizontal('left');
  $spreadsheet->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal('center');
  $spreadsheet->getActiveSheet()->getStyle('D:F')->getAlignment()->setHorizontal('right');
  $highestColumn = $spreadsheet->getActiveSheet()->getHighestColumn();
  $spreadsheet->getActiveSheet()->getStyle('A1:' . $highestColumn . '1' )->getFont()->setBold(true);
  //mime type
  header('Content-Type: application/vnd.ms-excel');
  header('Content-Disposition: attachment;filename="Inventario_del_'.$form['datref'].'.xls"');
  header('Cache-Control: max-age=0'); // no cache
  $writer = new Xls($spreadsheet);
  //force user to download the Excel file without writing it to server's HD
  $writer->save('php://output');
  exit;
}


require("../../library/include/header.php");
$script_transl = HeadMain(0);
?>
<script>
$(function () {
	$('#datref').each(function(){
		$(this).datepicker({ dateFormat: 'dd-mm-yy' });
	});
	$("#datref").change(function () {
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
<form method="POST" name="maschera">
	<input type="hidden" name="hidden_req" value="" />
	<input type="hidden" name="ritorno" value="<?php echo $_POST['ritorno']; ?>" />
  <div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
    <div class="col-lg-12">
      <div class="col-xs-11"><h4><?php echo $script_transl['print']; ?></h4></div>
      <div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
    </div>
    <iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
  </div>
  <div align="center" class="FacetFormHeaderFont">
<?php
echo ucfirst($script_transl['title']) . ' ' . $script_transl['del'];
echo '<input name="datref" id="datref" value="'.$form['datref'].'" />';
echo $script_transl['catmer'];
$gForm->selectFromDB('catmer', 'catmer', 'codice', $form['catmer'], false, false, '-', 'descri', 'catmer', 'FacetSelect', array('value' => 100, 'descri' => '*** ' . $script_transl['all'] . ' ***'));
?>
</div>
<div class="table-responsive">
  <table class="Tlarge table table-striped table-bordered table-condensed">
    <?php
    if (!empty($msg)) {
    ?>
    <tr>
			<td colspan="9" class="FacetDataTDred"><?php echo $gForm->outputErrors($msg, $script_transl['errors']); ?></td>
		</tr>
    <?php
    }
    ?>
    <thead>
      <tr>
        <th class="FacetFieldCaptionTD"><?php echo $script_transl['code']; ?><span class="pull-right">Scheda</span></th>
        <th class="FacetFieldCaptionTD"><?php echo $script_transl['descri']; ?></th>
        <th class="FacetFieldCaptionTD"><?php echo $script_transl['mu']; ?></th>
        <th class="FacetFieldCaptionTD"><?php echo $script_transl['v_a']; ?></th>
        <th class="FacetFieldCaptionTD"><?php echo $script_transl['g_a']; ?></th>
        <th class="FacetFieldCaptionTD"><?php echo $script_transl['g_v']; ?></th>
      </tr>
    </thead>
    <tbody>
    <?php
    if (isset($data)) {
      $totq_g = 0;
      $totv_g = 0;
      $ctrl_cm = 0;
      foreach ($data as $k => $v) {
        //ini default value
        $tooltip = ' class="gazie-tooltip" data-type="product-thumb" data-id="' . $k . '" data-title="' . $v['i_a'] . '"';
        // end default value
        if ($ctrl_cm <> $v['i_g']) {
          $cm_tooltip = ' class="gazie-tooltip" data-type="catmer-thumb" data-id="' . $v['i_g'] . '" data-title="' . $v['g_d'] . '"';
        ?>
          <tr><td class="bg-info" colspan=7 ><h4 <?php echo $cm_tooltip; ?>><?php echo $v['i_g'] . ' - ' . $v['g_d'] ?></h4></td></tr>
        <?php
        }
        ?>
        <tr class="" <?= $v['class']=='default'? 'title="Non movimentato fino al '.$form['datref'].'"':'' ?> >
          <td class="text-left"><span <?php echo $tooltip; ?>><a class="btn btn-<?= $v['class'] ?> btn-xs" href="./admin_artico.php?codice=<?php echo $k; ?>" target="_blank"><?php echo $k; ?></a></span><span class="pull-right"><a class="btn btn-xs btn-info" href="select_schart.php?di=0101<?= (date('Y')-10).'&df='.date('dmY').'&id='.$k ?>" target="_blank"><i class="glyphicon glyphicon-list"></i></span></a></td>
          <?php
          echo
         '<td align="left"><span ' . $tooltip . '>' . $v['i_d'] . '</span></td>
            <td align="center">' . $v['i_u'] . '</td>
            <td align="right">' . gaz_format_quantity($v['v_a'], 0, $admin_aziend['decimal_price']) . '</td>
            <td class="text-right text-'.$v['class'].'">' . gaz_format_quantity($v['g_a'], 1, $admin_aziend['decimal_quantity']) . '</td>
            <td align="right">' . gaz_format_number($v['v_g']) . '</td>
        </tr>';
        $totq_g += $v['g_a'];
        $totv_g += $v['v_g'];
        $ctrl_cm = $v['i_g'];
      }
      echo '<tr><td colspan=4></td><td class="text-right">' . gaz_format_quantity($totq_g, 1, $admin_aziend['decimal_quantity']) . '</td> <td class="text-right">' . gaz_format_number($totv_g) . '</td></tr>';
      echo '</tbody></table>';
      ?>
<div class="panel panel-info">
 <div class="container-fluid">
	<div class="row col-xs-12">
		<?php
    echo '<div class="col-xs-12 col-md-6 text-center"><input class="btn btn-info" type="submit" name="export" value="Esporta in formato XLS"></div>';
		echo '<div class="col-xs-12 col-md-6 text-center"><a class="btn btn-warning" title="Stampa documento PDF" style="cursor:pointer;" onclick="printPdf(\'print_inventory.php?dr='. $form['datref']."&cm=".$form['catmer']."')\">".$script_transl['print'].$script_transl['title']." <i class=\"glyphicon glyphicon-print\" ></i></a></div>";
		?>
	</div>
 </div>
</div>
<?php
} else {
  echo '<tr>
  				<td colspan="9" class="FacetDataTDred">' . $script_transl['noitem'] . '</td>
        </tr></tbody></table>';
}
?>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
