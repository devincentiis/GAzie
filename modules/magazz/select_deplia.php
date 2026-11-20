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
$gForm = new magazzForm();
$msg='';

function getExtremeValue($table_name,$min_max='MIN') {
  $rs=gaz_dbi_dyn_query ($min_max.'(codice) AS value',$table_name);
  $data=gaz_dbi_fetch_array($rs);
  return $data['value'];
}

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['jumpcat'] = '';
    $form['this_date_Y']=date("Y");
    $form['this_date_M']=date("m");
    $form['this_date_D']=date("d");
    if (isset($_GET['li'])) {
        $form['listino']=substr($_GET['lis'],0,3);
    }  else {
        $form['listino']=1;
    }
    $form['barcode']=0;
    if (isset($_GET['id'])) {
       $item=gaz_dbi_get_row($gTables['artico'],'codice',substr($_GET['id'],0,15));
       $form['art_ini']=$item['codice'];
       $form['art_fin']=$item['codice'];
       $form['cm_ini']=$item['catmer'];
       $form['cm_fin']=$item['catmer'];
    }  else {
       if (isset($_GET['ai'])) {
          $form['art_ini']=substr($_GET['ai'],0,15);
       } else {
          $form['art_ini']=getExtremeValue($gTables['artico']);
       }
       if (isset($_GET['af'])) {
          $form['art_fin']=substr($_GET['af'],0,15);
       } else {
          $form['art_fin']=getExtremeValue($gTables['artico'],'MAX');
       }
       if (isset($_GET['ci'])) {
          $form['cm_ini']=intval($_GET['ci']);
       } else {
          $form['cm_ini']=getExtremeValue($gTables['catmer']);
       }
       if (isset($_GET['cf'])) {
          $form['cm_fin']=intval($_GET['cf']);
       } else {
          $form['cm_fin']=getExtremeValue($gTables['catmer'],'MAX');
       }
    }
    $form['search']['art_ini']='';
    $form['search']['art_fin']='';
} else { // accessi successivi
    $form['hidden_req']=htmlentities($_POST['hidden_req']);
    $form['ritorno']=$_POST['ritorno'];
    $form['this_date_Y']=intval($_POST['this_date_Y']);
    $form['this_date_M']=intval($_POST['this_date_M']);
    $form['this_date_D']=intval($_POST['this_date_D']);
    $form['listino']=substr($_POST['listino'],0,3);
    $form['barcode']=intval($_POST['barcode']);
    $form['cm_ini']=intval($_POST['cm_ini']);
    $form['cm_fin']=intval($_POST['cm_fin']);
    $form['art_ini']=substr($_POST['art_ini'],0,15);
    $form['art_fin']=substr($_POST['art_fin'],0,15);
    foreach($_POST['search'] as $k=>$v){
       $form['search'][$k]=$v;
    }
    if (isset($_POST['return'])) {
        header("Location: ".$form['ritorno']);
        exit;
    }
    $form['jumpcat'] = ( isset($_POST['jumpcat']) ? 'checked' : '' );
}

//controllo i campi
if (!checkdate( $form['this_date_M'],$form['this_date_D'],$form['this_date_Y'])) {
  $msg .='0+';
}
$utsexe= mktime(0,0,0,$form['this_date_M'],$form['this_date_D'],$form['this_date_Y']);
if (strcasecmp($form['art_ini'],$form['art_fin'])>0) {
  $msg .='1+';
}
if ($form['cm_ini'] > $form['cm_fin']) {
  $msg .='2+';
}
// fine controlli


require("../../library/include/header.php");
$script_transl=HeadMain(0,array('calendarpopup/CalendarPopup'));
if (isset($admin_aziend['lang'])){
  $price_list_names = gaz_dbi_dyn_query('*', $gTables['company_data'], "ref = '" . $admin_aziend['lang'] . "_artico_pricelist' && var NOT LIKE 'preacq'", "id_ref ASC");
  if ($price_list_names->num_rows == 5){
    $script_transl['listino_value']=array();
    $n=0;
    while ($list_name = gaz_dbi_fetch_array($price_list_names)){
      $n++;
      $script_transl['listino_value'][$n]=$list_name["description"];
    }
  }
}
?>
<script>
var cal = new CalendarPopup();
var calName = '';
function setMultipleValues(y,m,d) {
     document.getElementById(calName+'_Y').value=y;
     document.getElementById(calName+'_M').selectedIndex=m*1-1;
     document.getElementById(calName+'_D').selectedIndex=d*1-1;
}
function setDate(name) {
  calName = name.toString();
  var year = document.getElementById(calName+'_Y').value.toString();
  var month = document.getElementById(calName+'_M').value.toString();
  var day = document.getElementById(calName+'_D').value.toString();
  var mdy = month+'/'+day+'/'+year;
  cal.setReturnFunction('setMultipleValues');
  cal.showCalendar('anchor', mdy);
}
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
<?php
echo "<form method=\"POST\" name=\"select\">\n";
echo "<input type=\"hidden\" value=\"".$form['hidden_req']."\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\" />\n";
?>
  <div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
    <div class="col-lg-12">
      <div class="col-xs-11"><h4><?php echo $script_transl['print']; ?></h4></div>
      <div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
    </div>
    <iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
  </div>
<?php
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tsmall table-striped\" align=\"center\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="2" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date']."</td><td  class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('this_date',$form['this_date_D'],$form['this_date_M'],$form['this_date_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['listino']."</td><td  class=\"FacetDataTD\">\n";
$gForm->variousSelect('listino',$script_transl['listino_value'],$form['listino'],'',false,'refresh');
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['barcode']."</td><td  class=\"FacetDataTD\">\n";
$gForm->variousSelect('barcode',$script_transl['barcode_value'],$form['barcode'],'',false,'refresh');
echo ' '.$script_transl['jumpcat'].' <input type="checkbox" name="jumpcat" onchange="this.form.submit()" '.$form['jumpcat'].'></td>';
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['cm_ini']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectFromDB('catmer','cm_ini','codice',$form['cm_ini'],false,false,'-','descri','cm_ini');
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['cm_fin']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectFromDB('catmer','cm_fin','codice',$form['cm_fin'],false,false,'-','descri','cm_fin');
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['art_ini']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selItem('art_ini',$form['art_ini'],$form['search']['art_ini'],$script_transl['mesg'],$form['hidden_req']);
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['art_fin']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selItem('art_fin',$form['art_fin'],$form['search']['art_fin'],$script_transl['mesg'],$form['hidden_req']);
echo "</tr>\n";
// bottone stampa
$form['art_fin'] = $form['art_fin'] == 0 ? $form['art_ini'] : $form['art_fin'] ;
$ref = 'li='.$form['listino'].'&bc='.$form['barcode'].'&ai='.$form['art_ini'].'&af='.$form['art_fin'].'&ci='.$form['cm_ini'].'&cf='.$form['cm_fin'].'&ds='.date("dmY",$utsexe).'&jumpcat='.( isset($_POST['jumpcat']) ? '1' : '0' );
echo '<tr><td class="text-center" colspan=2><a class="btn btn-warning" title="Stampa con PDF" style="cursor:pointer;" onclick="printPdf(\'stampa_depliant.php?'. $ref.'\')" \> <i class="fa fa-file-pdf-o"></i> '.$script_transl['print'].' <i class="glyphicon glyphicon-print" ></i></a>';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";
?>
</form>
<?php
require("../../library/include/footer.php");
?>
