<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
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


function getMovements($cm_ini,$cm_fin,$art_ini,$art_fin,$date_ini,$date_fin)
    {
        global $gTables,$admin_aziend;
        $m=array();
        if ($art_fin=='') {
              $art_fin='zzzzzzzzzzzzzzz';
        }
        $where=" catmer BETWEEN ".$cm_ini." AND ".$cm_fin." AND".
               " artico BETWEEN '".$art_ini."' AND '".$art_fin."' AND".
               " datreg BETWEEN ".$date_ini." AND ".$date_fin;
        $what=$gTables['movmag'].".*, ".
              $gTables['caumag'].".codice, ".$gTables['caumag'].".descri, ".
              $gTables['clfoco'].".codice, ".
			  $gTables['camp_mov_sian'].".recip_stocc, ".
			  $gTables['lotmag'].".identifier, ".
              $gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2, ".
              $gTables['artico'].".codice, ".$gTables['artico'].".descri AS desart, ".$gTables['artico'].".unimis, ".$gTables['artico'].".scorta, ".$gTables['artico'].".image, ".$gTables['artico'].".catmer, ".$gTables['artico'].".quality ";
        $table=$gTables['movmag']." LEFT JOIN ".$gTables['caumag']." ON ".$gTables['movmag'].".caumag = ".$gTables['caumag'].".codice
               LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['movmag'].".campo_impianto = ".$gTables['clfoco'].".codice
               LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra
			   LEFT JOIN ".$gTables['lotmag']." ON ".$gTables['lotmag'].".id_movmag = ".$gTables['movmag'].".id_mov
			   LEFT JOIN ".$gTables['camp_mov_sian']." ON ".$gTables['camp_mov_sian'].".id_movmag = ".$gTables['movmag'].".id_mov
               LEFT JOIN ".$gTables['artico']." ON ".$gTables['movmag'].".artico = ".$gTables['artico'].".codice";
        $rs=gaz_dbi_dyn_query ($what, $table,$where,"catmer ASC, artico ASC, datreg ASC, id_mov ASC");
        while ($r = gaz_dbi_fetch_array($rs)) {
            $m[] = $r;
        }
        return $m;
    }
function getExtremeValue($table_name,$min_max='MIN')
    {
        $rs=gaz_dbi_dyn_query ($min_max.'(codice) AS value',$table_name);
        $data=gaz_dbi_fetch_array($rs);
        return $data['value'];
    }

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['this_date_Y']=date("Y");
    $form['this_date_M']=date("m");
    $form['this_date_D']=date("d");
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
    $form['date_ini_D']=intval($_POST['date_ini_D']);
    $form['date_ini_M']=intval($_POST['date_ini_M']);
    $form['date_ini_Y']=intval($_POST['date_ini_Y']);
    $form['date_fin_D']=intval($_POST['date_fin_D']);
    $form['date_fin_M']=intval($_POST['date_fin_M']);
    $form['date_fin_Y']=intval($_POST['date_fin_Y']);
    $form['this_date_Y']=intval($_POST['this_date_Y']);
    $form['this_date_M']=intval($_POST['this_date_M']);
    $form['this_date_D']=intval($_POST['this_date_D']);
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
}

//controllo i campi
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
if (strcasecmp($form['art_ini'],$form['art_fin'])>0) {
    $msg .='3+';
}
if ($form['cm_ini'] > $form['cm_fin']) {
    $msg .='4+';
}
// fine controlli

if (isset($_POST['print']) && $msg=='') {
    if ($form['art_fin']==0){
        $form['art_fin']==$form['art_ini'];
    }
    $_SESSION['print_request']=array('script_name'=>'stampa_schart',
                                     'ai'=>$form['art_ini'],
                                     'af'=>$form['art_fin'],
                                     'ci'=>$form['cm_ini'],
                                     'cf'=>$form['cm_fin'],
                                     'ri'=>date("dmY",$utsini),
                                     'rf'=>date("dmY",$utsfin),
                                     'ds'=>date("dmY",$utsexe)
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
//echo "<input type=\"hidden\" value=\"".$form['search']."\" name=\"search\" />\n";
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
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_ini']."</td><td  class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_ini',$form['date_ini_D'],$form['date_ini_M'],$form['date_ini_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_fin']."</td><td  class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_fin',$form['date_fin_D'],$form['date_fin_M'],$form['date_fin_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
echo "<td align=\"left\"><input type=\"submit\" name=\"return\" value=\"".$script_transl['return']."\">\n";
echo '<td align="right"> <input type="submit" accesskey="i" name="preview" value="';
echo $script_transl['view'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";

$date_ini =  sprintf("%04d%02d%02d",$form['date_ini_Y'],$form['date_ini_M'],$form['date_ini_D']);
$date_fin =  sprintf("%04d%02d%02d",$form['date_fin_Y'],$form['date_fin_M'],$form['date_fin_D']);

if (isset($_POST['preview']) and $msg=='') {
  $m=getMovements($form['cm_ini'],$form['cm_fin'],$form['art_ini'],$form['art_fin'],$date_ini,$date_fin);
  echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
  if (sizeof($m) > 0) {
        $ctr_mv='';
        $ctrl_id=0;
        echo "<tr>";
        $linkHeaders=new linkHeaders($script_transl['header']);
        $linkHeaders->output();
        echo "</tr>";
        foreach ($m AS $key => $mv) {
            if ($ctr_mv != $mv['artico']) {
               gaz_set_time_limit (30);
               if (!empty($ctr_mv)) {
                echo "\t<tr>\n";
                echo "\t<td colspan=\"10\" align=\"right\"></td>\n";
                echo "\t </tr>\n";
                $sum=0.00;
               }
               echo "\t<tr>\n";
               echo "\t<td colspan=\"10\">".$mv['artico']." - ".$mv['desart']."</td>\n";
               echo "\t </tr>\n";
            }

            // passo tutte le variabili al metodo in modo da non costringere lo stesso a fare le query per ricavarsele
            $magval= $gForm->getStockValue($mv['id_mov'],$mv['artico'],$mv['datreg'],$admin_aziend['stock_eval_method']);
            $r_span=count($magval);
            foreach ($magval as $mval) {
              $addes="";
              if ($ctrl_id <> $mv['id_mov']) {
                if (isset($mv['recip_stocc']) && strlen($mv['recip_stocc'])>0){
                  $addes .="-Silos:".$mv['recip_stocc'];
                }
                if (strlen($mv['quality'])>0){
                  $addes .="-Var.:".$mv['quality'];
                }
                if (isset($mv['identifier']) && strlen($mv['identifier'])>0){
                  $addes .="-Lotto:".$mv['identifier'];
                }
                echo "<tr><td class=\"FacetDataTD\" rowspan=\"$r_span\">".gaz_format_date($mv['datreg'])." id:".$mv['id_mov']."</td>";
                echo "<td align=\"center\" class=\"FacetDataTD\" rowspan=\"$r_span\">".$mv['caumag'].'-'.substr($mv['descri'],0,20)."</td>";
                echo "<td class=\"FacetDataTD\" rowspan=\"$r_span\">".substr($mv['desdoc'].' del '.gaz_format_date($mv['datdoc']).' - '.$mv['ragso1'].' '.$mv['ragso2'],0,85).$addes."</td>";
                /*echo "<td align=\"right\" class=\"FacetDataTD\" rowspan=\"$r_span\">".number_format($mv['prezzo'],$admin_aziend['decimal_price'],',','.')."</td>";*/
                echo "<td align=\"right\" class=\"FacetDataTD\" rowspan=\"$r_span\">".$mv['unimis']."</td>\n";
              } else {
                echo "<tr>";
              }
              echo "<td align=\"right\" class=\"FacetDataTD\">".gaz_format_quantity($mval['q']*$mv['operat'],1,$admin_aziend['decimal_quantity'])."</td>";
              /* if ($mv['operat']==1) {
                echo "<td align=\"right\" class=\"FacetDataTD\">".number_format($mval['v'],$admin_aziend['decimal_price'],',','')."</td><td></td>";
              } else {
                echo "<td></td><td align=\"right\" class=\"FacetDataTD\">".number_format($mval['v'],$admin_aziend['decimal_price'],',','')."</td>";
              }*/
              echo "<td align=\"right\" class=\"FacetDataTD\">".gaz_format_quantity($mval['q_g'],1,$admin_aziend['decimal_quantity'])."</td>";
              /* echo "<td align=\"right\" class=\"FacetDataTD\">".gaz_format_number($mval['v_g'])."</td>";*/
              echo "</tr>";
              $ctrl_id = $mv['id_mov'];
            }
            $ctr_mv = $mv['artico'];
       }
       echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
       echo '<td colspan="10" align="right"><input type="submit" name="print" value="'.$script_transl['print'].'">';
       echo "\t </td>\n";
       echo "\t </tr>\n";
  }
  echo "</table></form>";
}
?>
<span class="navbar-fixed-bottom" style="left:20%; z-index:2000;"> Registro di campagna è un modulo di Antonio Germani</span>
<?php
require("../../library/include/footer.php");
?>
