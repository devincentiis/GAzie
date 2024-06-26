<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
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
require("../../library/include/datlib.inc.php");
require ("../../modules/magazz/lib.function.php");
$admin_aziend=checkAdmin();
$msg='';

function getMovements($where){
	global $gTables,$admin_aziend;
	$m=array();
	//$where = "mostra_qdc = 1 AND ".$gTables['movmag'] .".id_rif >= ". $gTables['movmag'] .".id_mov AND ".$where;
	// il where precedente non caricava gli acquisti dei prodotti agricoli
	$where = "mostra_qdc = 1 AND good_or_service = 0 AND ".$where;

	$what=$gTables['movmag'].".*, ".
		  $gTables['caumag'].".codice, ".$gTables['caumag'].".descri, ".
		  $gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2, ".
		  $gTables['artico'].".codice, ".$gTables['artico'].".descri AS desart, ".$gTables['artico'].".unimis, ".$gTables['artico'].".scorta, ".$gTables['artico'].".catmer, ".$gTables['artico'].".mostra_qdc, ".$gTables['artico'].".classif_amb ";
	$table=$gTables['movmag']." LEFT JOIN ".$gTables['caumag']." ON (".$gTables['movmag'].".caumag = ".$gTables['caumag'].".codice)
		   LEFT JOIN ".$gTables['anagra']." ON (".$gTables['anagra'].".id = ".$gTables['movmag'].".clfoco)
		   LEFT JOIN ".$gTables['artico']." ON (".$gTables['movmag'].".artico = ".$gTables['artico'].".codice)";
	$rs=gaz_dbi_dyn_query ($what,$table,$where, 'datreg ASC, tipdoc ASC, clfoco ASC, operat DESC, id_mov ASC');
	while ($r = gaz_dbi_fetch_array($rs)) {
		$m[] = $r;
	}
	return $m;
}

// Antonio Germani carico la tabella campi di coltivazione
$res = gaz_dbi_dyn_query ('*', $gTables['campi']);
// fine carico tabella campi

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['this_date_Y']=date("Y");
    $form['this_date_M']=date("m");
    $form['this_date_D']=date("d");
	$form['type']="";
    if (!isset($_GET['di'])) {
       $form['date_ini_D']=1;
       $form['date_ini_M']=1;
       $form['date_ini_Y']=date("Y");
    } else {
       $form['date_ini_D']=intval(substr($_GET['di'],0,2));
       $form['date_ini_M']=intval(substr($_GET['di'],2,2));
       $form['date_ini_Y']=intval(substr($_GET['di'],4,4));
    }
    if (!isset($_GET['df'])) {
       $form['date_fin_D']=date("d");
       $form['date_fin_M']=date("m");
       $form['date_fin_Y']=date("Y");
    } else {
       $form['date_fin_D']= intval(substr($_GET['df'],0,2));
       $form['date_fin_M']= intval(substr($_GET['df'],2,2));
       $form['date_fin_Y']= intval(substr($_GET['df'],4,4));
    }
} else { // accessi successivi
    $form['hidden_req']=htmlentities($_POST['hidden_req']);
    $form['ritorno']=$_POST['ritorno'];
	$form['type']=$_POST['type'];
    $form['date_ini_D']=intval($_POST['date_ini_D']);
    $form['date_ini_M']=intval($_POST['date_ini_M']);
    $form['date_ini_Y']=intval($_POST['date_ini_Y']);
    $form['date_fin_D']=intval($_POST['date_fin_D']);
    $form['date_fin_M']=intval($_POST['date_fin_M']);
    $form['date_fin_Y']=intval($_POST['date_fin_Y']);
    $form['this_date_Y']=intval($_POST['this_date_Y']);
    $form['this_date_M']=intval($_POST['this_date_M']);
    $form['this_date_D']=intval($_POST['this_date_D']);
    if (isset($_POST['return'])) {
        header("Location: ".$form['ritorno']);
        exit;
    }
}

//controllo le date
if (!checkdate( $form['this_date_M'],$form['this_date_D'],$form['this_date_Y']) ||
    !checkdate( $form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']) ||
    !checkdate( $form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y'])) {
    $msg .='0+';
}
$utsexe= mktime(0,0,0,$form['this_date_M'],$form['this_date_D'],$form['this_date_Y']);
$utsini= mktime(0,0,0,$form['date_ini_M'],$form['date_ini_D'],$form['date_ini_Y']);
$utsfin= mktime(0,0,0,$form['date_fin_M'],$form['date_fin_D'],$form['date_fin_Y']);
if ($utsini > $utsfin) {
    $msg .='1+';
}
if ($utsexe < $utsfin) {
    $msg .='2+';
}
// fine controlli

if (isset($_POST['print']) && $msg=='') {
    $_SESSION['print_request']=array('script_name'=>'stampa_giomag',
                                     'ri'=>date("dmY",$utsini),
                                     'rf'=>date("dmY",$utsfin),
                                     'ds'=>date("dmY",$utsexe),
									 'type'=>$_POST['type']
                                     );
    header("Location: sent_print.php");
    exit;
}
if (isset($_POST['print_cop']) && $msg=='') {
    $_SESSION['print_request']=array('script_name'=>'stampa_cop_giomag',
                                     'ri'=>date("dmY",$utsini),
                                     'rf'=>date("dmY",$utsfin),
                                     'ds'=>date("dmY",$utsexe),
									 'type'=>$_POST['type']
                                     );
    header("Location: sent_print.php");
    exit;
}

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
echo "<form method=\"POST\" name=\"select\">\n";
echo "<input type=\"hidden\" value=\"".$form['hidden_req']."\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\" />\n";
echo "<input type=\"hidden\" value=\"".$form['type']."\" name=\"type\" />\n";
$gForm = new magazzForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tsmall\" align=\"center\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="2" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date']."</td><td  class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('this_date',$form['this_date_D'],$form['this_date_M'],$form['this_date_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_ini']."</td><td  class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_ini',$form['date_ini_D'],$form['date_ini_M'],$form['date_ini_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_fin']."</td><td  class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_fin',$form['date_fin_D'],$form['date_fin_M'],$form['date_fin_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
echo "<td align=\"left\"><input type=\"submit\" name=\"return\" value=\"".$script_transl['return']."\">\n";
echo '<td align="center"> <input type="submit" accesskey="i" name="preview" value="di carico"';
echo $script_transl['view']," Carico materie prime",'" tabindex="99" >';
echo ' <input type="submit" accesskey="i" name="preview" value="di campagna"';
echo $script_transl['view']," Quaderno di campagna";
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";

$date_ini =  sprintf("%04d%02d%02d",$form['date_ini_Y'],$form['date_ini_M'],$form['date_ini_D']);
$date_fin =  sprintf("%04d%02d%02d",$form['date_fin_Y'],$form['date_fin_M'],$form['date_fin_D']);

if (isset($_POST['preview']) and $msg=='') {
	if ($_POST['preview'] == "di campagna"){
		$where="type_mov = '1' AND datreg BETWEEN $date_ini AND $date_fin";
	} else {
		$where=$gTables['movmag'].".operat = '1' AND datreg BETWEEN $date_ini AND $date_fin";
	}
	echo "<input type=\"hidden\" value=\"".$_POST['preview']."\" name=\"type\" />\n";
	$m=getMovements($where);
	echo "<div align = \"center\" > Registro ", $_POST['preview'], " </div>";
	echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
	if (sizeof($m) > 0) {
        $ctr_mv='';
        echo "<tr>";
        $linkHeaders=new linkHeaders($script_transl['header']);
        $linkHeaders->output();
        echo "</tr>";
        $sum=0.00;
		foreach($m as $key => $mv){

			$datedoc = substr($mv['datdoc'],8,2).'-'.substr($mv['datdoc'],5,2).'-'.substr($mv['datdoc'],0,4);
			$datereg = substr($mv['datreg'],8,2).'-'.substr($mv['datreg'],5,2).'-'.substr($mv['datreg'],0,4);
			$movQuanti = $mv['quanti']*$mv['operat'];
			$sum += $movQuanti;
			echo "<tr><td class=\"FacetDataTD\">".$datedoc." &nbsp;</td>";
			echo "<td  align=\"center\" class=\"FacetDataTD\">".$mv['caumag'].'-'.substr($mv['descri'],0,20)." &nbsp</td>";

			// Antonio Germani Inserisco campo, superficie e coltura
			echo "<td align=\"right\" class=\"FacetDataTD\">".$mv['campo_impianto']." &nbsp;</td>";
			$colonna="0";
			$res = gaz_dbi_get_row ($gTables['campi'], 'codice', $mv['campo_impianto'] );
			echo "<td class=\"FacetDataTD\" align=\"center\">", gaz_format_quantity(($res)?$res['ricarico']:0,1,$admin_aziend['decimal_quantity']), " &nbsp;</td>\n";
			$res2 = gaz_dbi_get_row($gTables['camp_colture'], 'id_colt', $mv['id_colture']);
			echo "<td class=\"FacetDataTD\" align=\"center\">", ($res2)?$res2['nome_colt']:'' ," &nbsp;</td>\n";
			$colonna="1";

			if ($colonna<1) {
				echo "<td class=\"FacetDataTD\" align=\"center\"></td>\n";
				echo "<td class=\"FacetDataTD\" align=\"center\"></td>\n";
			 }
			// fine inserisco campo, superficie, coltura
			echo "<td class=\"FacetDataTD\" align=\"center\">".$mv['artico']." &nbsp;</td>\n";
			If ($mv['classif_amb']==0) {echo "<td class=\"FacetDataTD\" align=\"center\">". "<img src=\"../camp/media/classe_0.gif\" alt=\"Non classificato\" width=\"50 px\">" ." &nbsp;</td>\n";}
			If ($mv['classif_amb']==1) {echo "<td class=\"FacetDataTD\" align=\"center\">". "<img src=\"../camp/media/classe_1.gif\" alt=\"Irritante\" width=\"50 px\">" ." &nbsp;</td>\n";}
			If ($mv['classif_amb']==2) {echo "<td class=\"FacetDataTD\" align=\"center\">". "<img src=\"../camp/media/classe_2.gif\" alt=\"Nocivo\" width=\"50 px\">" ." &nbsp;</td>\n";}
			If ($mv['classif_amb']==3) {echo "<td class=\"FacetDataTD\" align=\"center\">". "<img src=\"../camp/media/classe_3.gif\" alt=\"Tossico\" width=\"50 px\">" ." &nbsp;</td>\n";}
			If ($mv['classif_amb']==4) {echo "<td class=\"FacetDataTD\" align=\"center\">". "<img src=\"../camp/media/classe_4.gif\" alt=\"Molto tossico\" width=\"50 px\">" ." &nbsp;</td>\n";}
			If ($mv['classif_amb']==5) {echo "<td class=\"FacetDataTD\" align=\"center\">". "<img src=\"../camp/media/classe_5.gif\" alt=\"Pericoloso ambiente\" width=\"50 px\">" ." &nbsp;</td>\n";}
			echo "<td class=\"FacetDataTD\" align=\"center\">".gaz_format_quantity($mv['quanti'],1,$admin_aziend['decimal_quantity'])."</td>\n";
			echo "<td align=\"right\" class=\"FacetDataTD\">".$mv['unimis']." &nbsp;</td>\n";
			$res = gaz_dbi_get_row($gTables['camp_avversita'], 'id_avv', $mv['id_avversita']);
			echo "<td class=\"FacetDataTD\" align=\"right\">", ($res)?$res['nome_avv']:'', " </td>\n";
			if ($mv['clfoco']>0){
				echo "<td class=\"FacetDataTD\" align=\"right\">".$mv['ragso1']." </td>\n";
			} else {
				echo "<td class=\"FacetDataTD\" align=\"right\">".$mv['adminid']." </td>\n";
			}
			echo "<td class=\"FacetDataTD\">".$mv['desdoc']." &nbsp;</td>";
			echo "</tr>\n";
			$ctr_mv = $mv['artico'];

		}
        echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
        echo '<td colspan="7" align="right"><input type="submit" name="print" value="';
        echo $script_transl['print'];
        echo '">';
        echo "\t </td>\n";
		echo '<td colspan="7" align="right"><input type="submit" name="print_cop" value="';
        echo $script_transl['print']." copertina";
        echo '">';
        echo "\t </td>\n";
        echo "\t </tr>\n";
	}
	echo "</table></form>";
}
require("../../library/include/footer.php");
?>
