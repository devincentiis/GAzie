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
require("../../library/include/datlib.inc.php");
require("../../library/export/export_csv.php");

$admin_aziend=checkAdmin();
$gForm = new magazzForm;
$msg='';
$name_file = "inventory.xls";

if ( !isset($_POST['this_date_Y']) )
{
	$form['this_date_Y'] = date("Y") - 1;
	$form['this_date_M'] = 12;
	$form['this_date_D'] = 31;
} else
{
	$form['this_date_Y'] = intval($_POST['this_date_Y']);
	$form['this_date_M'] = intval($_POST['this_date_M']);
	$form['this_date_D'] = intval($_POST['this_date_D']);
}

if (isset($_POST['catmer']))
    $form['catmer'] = intval($_POST['catmer']);
else
	$form['catmer'] = 100;
if ( !isset($_POST['this_date_Y']) )
{
	require("../../library/include/header.php");
	$script_transl=HeadMain(0,array('calendarpopup/CalendarPopup'));
	echo "<script type=\"text/javascript\">
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
	</script>
	";
	echo "<form method=\"POST\" name=\"\">\n";
	$gForm = new magazzForm();
	echo "<div align=\"center\" class=\"FacetFormHeaderFont\">Esporta Inventario"
			//.$script_transl['title']
			;
	echo "</div>\n";
	echo "<table class=\"Tsmall\" align=\"center\">\n";
	if (!empty($msg)) {
		echo '<tr><td colspan="2" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
	}
	echo "<tr>\n";
	echo "<td class=\"FacetFieldCaptionTD\">Alla Data"
			//.$script_transl['date']
			."</td><td  class=\"FacetDataTD\">\n";
	$gForm->CalendarPopup('this_date',$form['this_date_D'],$form['this_date_M'],$form['this_date_Y'],'FacetSelect',1);
	echo "</tr>\n";
	echo "<tr>\n";
	echo "\t<td class=\"FacetFieldCaptionTD\">Categoria"
			//.$script_transl['categoria']
			."</td>
			<td  class=\"FacetDataTD\">\n";
	$gForm->selectFromDB('catmer','catmer','codice',$form['catmer'],false,false,'-','descri','catmer','FacetSelect',array('value'=>100,'descri'=>'*** '.$script_transl['all'].' ***'));
	echo "\t </td>\n";
	echo "</tr>\n";
	echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
	echo '<td align="right"> <input type="submit" accesskey="i" name="print" value="';
	echo "Esporta";
	//$script_transl['export'];
	echo '" tabindex="100" >';
	echo "\t </td>\n";
	echo "\t </tr>\n";
	echo "</table>\n";
	echo "</form>\n";
    require("../../library/include/footer.php");
}
else {
$utsdate= mktime(0,0,0,$form['this_date_M'],$form['this_date_D'],$form['this_date_Y']);
$date = date("Y-m-d",$utsdate);
$where="catmer = ".$form["catmer"];
if ($form['catmer'] == 100) {
       $where=1;
}
      $ctrl_cm=0;
      $result = gaz_dbi_dyn_query($gTables['artico'].'.*, '.$gTables['catmer'].'.descri AS descat,'.$gTables['catmer'].'.annota AS anncat', $gTables['artico'].' LEFT JOIN '.$gTables['catmer'].' ON catmer = '.$gTables['catmer'].'.codice', $where,'catmer ASC, '.$gTables['artico'].'.codice ASC');
      if ($result) {
      	 // Imposto totale valore giacenza by DF
      	 $tot_val_giac = 0;
         while ($r = gaz_dbi_fetch_array($result)) {
           if ($r['catmer']<>$ctrl_cm ){
             $ctrl_cm=$r['catmer'];
           }

          $mv=$gForm->getStockValue(false,$r['codice'],$date,null,$admin_aziend['decimal_price']);
          $magval=array_pop($mv);
          $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
           if ($magval && $magval['q_g'] > 0 )
           {
	           $form['a'][$r['codice']]['i_d'] = $r['descri'];
	           $form['a'][$r['codice']]['i_u'] = $r['unimis'];
	           $form['a'][$r['codice']]['v_a'] = $magval['v'];
	           $form['a'][$r['codice']]['v_r'] = $magval['v'];
	           $form['a'][$r['codice']]['i_a'] = $r['annota'];
	           $form['a'][$r['codice']]['i_g'] = $r['catmer'];
	           $form['a'][$r['codice']]['g_d'] = $r['descat'];
	           $form['a'][$r['codice']]['g_r'] = $magval['q_g'];
	           $form['a'][$r['codice']]['g_a'] = $magval['q_g'];
	           $form['a'][$r['codice']]['v_g'] = $magval['v_g'];
	           $form['vac_on'.$r['codice']] = '';
	           if ($magval['q_g'] < 0 ){
	                 $form['chk_on'.$r['codice']] = ' checked ';
	                 $form['a'][$r['codice']]['col'] = 'red';
	           } elseif ($magval['q_g']>0) {
	                 $form['chk_on'.$r['codice']] = ' checked ';
	                 $form['a'][$r['codice']]['col'] = '';
	           } else {
	                 $form['chk_on'.$r['codice']] = '';
	                 $form['a'][$r['codice']]['col'] = '';
	           }
	           // Calcolo totale valore giacenza by DF
	           $tot_val_giac += $magval['v_g'];
           }
         }
      }


$exporter = new ExportDataExcel('browser', $name_file);

if (isset($form['a'])) {
	$exporter->initialize();
	$exporter->addRow( array( "Categoria", "Codice", "Descrizione","Valore Unitario", "Quantita", "Giac.", "Val Totale", "Alla Data: ". $date ) );
	$elem_n=0;

	foreach($form['a'] as $k=>$v) {
		if ($ctrl_cm <> $v['i_g']) {
			$ctrl_cm = $v['i_g'];
		}

		//echo "<br>".$v['g_d']." ".$k . " ". $v['i_d']." ".gaz_format_quantity($v['v_a'],0,$admin_aziend['decimal_price'] )." ".gaz_format_quantity($v['g_a'],0,$admin_aziend['decimal_quantity'] )." ". gaz_format_quantity($v['v_g'],0,$admin_aziend['decimal_price'] );

		$exporter->addRow(
				array(
						$v['g_d'],
						$k,
						$v['i_d'],
						gaz_format_quantity($v['v_a'],0,$admin_aziend['decimal_price'] ),
						gaz_format_quantity($v['g_a'],0,$admin_aziend['decimal_quantity'] ),
						gaz_format_quantity($v['v_g'],0,$admin_aziend['decimal_price'] ),
				));
		$elem_n++;
	}
}

$exporter->addRow(
		array(
				"",
				"",
				"",
				"Valore Totale Magazzino",
				"",
				$tot_val_giac,
		));

$exporter->finalize();

exit();
}
