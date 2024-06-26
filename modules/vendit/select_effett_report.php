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
$admin_aziend=checkAdmin();
$msg='';


function getLimit($reprint=false)
{
    global $gTables;
    $acc=array();
    $where="(id_distinta = 0  OR id_distinta IS NULL)";
    if ($reprint){
       $where=' 1 ';
    }
    $rs_d = gaz_dbi_dyn_query("scaden", $gTables['effett'],$where,"scaden ASC",0,1);
    $rs=gaz_dbi_fetch_array($rs_d);
    if ($rs){
       $acc['si']=$rs['scaden'];
    } else {
       $acc['si']=date("Y-m-d");
    }
    $rs_d = gaz_dbi_dyn_query("scaden", $gTables['effett'],$where,"scaden DESC",0,1);
    $rs=gaz_dbi_fetch_array($rs_d);
    if ($rs){
       $acc['sf']=$rs['scaden'];
    } else {
       $acc['sf']=date("Y-m-d");
    }
    $rs_d = gaz_dbi_dyn_query("MIN(progre) AS progre", $gTables['effett'],$where.' GROUP BY YEAR(datemi) ');
    $rs=gaz_dbi_fetch_array($rs_d);
    if ($rs){
       $acc['ni']=$rs['progre'];
    } else {
       $acc['ni']=1;
    }
    $rs_d = gaz_dbi_dyn_query("MAX(progre) AS progre", $gTables['effett'],$where.' GROUP BY YEAR(datemi) ');
    $rs=gaz_dbi_fetch_array($rs_d);
    if ($rs){
       $acc['nf']=$rs['progre'];
    } else {
       $acc['nf']=999999999;
    }
    return $acc;
}

function getData($date_ini,$date_fin,$num_ini,$num_fin,$reprint='N',$banacc=0)
{
        global $gTables,$admin_aziend;
        $m=array();
        if ($reprint=='S'){
           $where=$gTables['effett'].".banacc = $banacc AND ";
        } else {
           $where="(".$gTables['effett'].".id_distinta = 0  OR ".$gTables['effett'].".id_distinta IS NULL) AND ";
        }
        $where.="scaden BETWEEN $date_ini AND $date_fin AND progre BETWEEN $num_ini AND $num_fin";
        $orderby="tipeff, scaden, progre";
        $rs=gaz_dbi_dyn_query($gTables['effett'].".*,".
                     $gTables['anagra'].".pariva,
                     CONCAT(".$gTables['anagra'].".ragso1,' ',".$gTables['anagra'].".ragso2) AS customer,
                     CONCAT(".$gTables['banapp'].".codabi,' - ',".$gTables['banapp'].".codcab) AS coordi,
                     ".$gTables['banapp'].".descri AS desban ",
                     $gTables['effett']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['effett'].".clfoco = ".$gTables['clfoco'].".codice
                     LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra
                     LEFT JOIN ".$gTables['banapp']." ON ".$gTables['effett'].".banapp = ".$gTables['banapp'].".codice",
                     $where,
                     $orderby);
        $type_val=array();
        $total=array();
        while ($r=gaz_dbi_fetch_array($rs)) {
            if (isset($type_val[$r['tipeff']])) {
                $type_val[$r['tipeff']]['value']+=$r['impeff'];
                $type_val[$r['tipeff']]['num']++;
            } else {
                $type_val[$r['tipeff']]['value']=$r['impeff'];
                $type_val[$r['tipeff']]['num']=1;
            }
            if (isset($total['value'])) {
                $total['value']+=$r['impeff'];
                $total['num']++;
            } else {
                $total['value']=$r['impeff'];
                $total['num']=1;
            }
            $m[] = $r;
        }
        return array('data'=>$m,'type'=>$type_val,'tot'=>$total);
}

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    require("lang.".$admin_aziend['lang'].".php");
    $iniData=getLimit();
    $form['date_emi_D']=date("d");
    $form['date_emi_M']=date("m");
    $form['date_emi_Y']=date("Y");
    $form['bank']='';
    $form['reprint']='N';
    $form['date_ini_D']=substr($iniData['si'],8,2);
    $form['date_ini_M']=substr($iniData['si'],5,2);
    $form['date_ini_Y']=substr($iniData['si'],0,4);
    $form['date_fin_D']=substr($iniData['sf'],8,2);
    $form['date_fin_M']=substr($iniData['sf'],5,2);
    $form['date_fin_Y']=substr($iniData['sf'],0,4);
    $form['num_ini']=$iniData['ni'];
    $form['num_fin']=$iniData['nf'];
} else { // accessi successivi
  $form['hidden_req']=htmlentities($_POST['hidden_req']);
  $form['ritorno']=$_POST['ritorno'];
  $form['date_emi_D']=intval($_POST['date_emi_D']);
  $form['date_emi_M']=intval($_POST['date_emi_M']);
  $form['date_emi_Y']=intval($_POST['date_emi_Y']);
  $form['bank']=intval($_POST['bank']);
  $form['reprint']=substr($_POST['reprint'],0,1);
  $form['date_ini_D']=intval($_POST['date_ini_D']);
  $form['date_ini_M']=intval($_POST['date_ini_M']);
  $form['date_ini_Y']=intval($_POST['date_ini_Y']);
  $form['date_fin_D']=intval($_POST['date_fin_D']);
  $form['date_fin_M']=intval($_POST['date_fin_M']);
  $form['date_fin_Y']=intval($_POST['date_fin_Y']);
  $form['num_ini']=intval($_POST['num_ini']);
  $form['num_fin']=intval($_POST['num_fin']);
  if ($form['hidden_req']=='reprint'){
     if ($form['reprint']=='S') {
        $iniData=getLimit(1);
    } else {
        $iniData=getLimit();
    }
    $form['date_ini_D']=substr($iniData['si'],8,2);
    $form['date_ini_M']=substr($iniData['si'],5,2);
    $form['date_ini_Y']=substr($iniData['si'],0,4);
    $form['date_fin_D']=substr($iniData['sf'],8,2);
    $form['date_fin_M']=substr($iniData['sf'],5,2);
    $form['date_fin_Y']=substr($iniData['sf'],0,4);
    $form['num_ini']=$iniData['ni'];
    $form['num_fin']=$iniData['nf'];
  }
  if (isset($_POST['period'])) {
    $gazTimeFormatter->setPattern('MMyyyy');
    $new_date_ini=$gazTimeFormatter->format(new DateTime('@'.mktime(12,0,0,date("m")+1,16,date("Y"))));
    $new_date_fin=$gazTimeFormatter->format(new DateTime('@'.mktime(12,0,0,date("m")+2,15,date("Y"))));
    $form['date_ini_D']=16;
    $form['date_ini_M']=substr($new_date_ini,0,2);
    $form['date_ini_Y']=substr($new_date_ini,2,4);
    $form['date_fin_D']=15;
    $form['date_fin_M']=substr($new_date_fin,0,2);
    $form['date_fin_Y']=substr($new_date_fin,2,4);
    $form['num_ini']=1;
    $form['num_fin']=999999999;
    $form['reprint']='S';
  }
  if (isset($_POST['return'])) {
      header("Location: ".$form['ritorno']);
      exit;
  }
}

//controllo i campi
if (!checkdate( $form['date_emi_M'], $form['date_emi_D'], $form['date_emi_Y']) ||
    !checkdate( $form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']) ||
    !checkdate( $form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y'])) {
    $msg .='1+';
}

$utsemi= mktime(0,0,0,$form['date_emi_M'],$form['date_emi_D'],$form['date_emi_Y']);
$utsini= mktime(0,0,0,$form['date_ini_M'],$form['date_ini_D'],$form['date_ini_Y']);
$utsfin= mktime(0,0,0,$form['date_fin_M'],$form['date_fin_D'],$form['date_fin_Y']);
if ($utsini > $utsfin) {
    $msg .='2+';
}
if ($utsemi > $utsini) {
    $msg .='3+';
}
// fine controlli

if (isset($_POST['print']) && $msg=='') {
    $_SESSION['print_request']=array('script_name'=>'stampa_effett_report',
                                     'de'=>date("dmY",$utsemi),
                                     'rp'=>$form['reprint'],
                                     'ba'=>$form['bank'],
                                     'ni'=>$form['num_ini'],
                                     'nf'=>$form['num_fin'],
                                     'ri'=>date("dmY",$utsini),
                                     'rf'=>date("dmY",$utsfin)
                                     );
    header("Location: sent_print.php");
    exit;
}

require("../../library/include/header.php");
$script_transl=HeadMain(false,array('calendarpopup/CalendarPopup'));
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
echo "<input type=\"hidden\" value=\"\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\" />\n";
$gForm = new venditForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_emi']."</td><td class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_emi',$form['date_emi_D'],$form['date_emi_M'],$form['date_emi_Y'],'FacetSelect',1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['bank']."</td><td  class=\"FacetDataTD\">\n";
$select_bank = new selectconven("bank");
$select_bank -> addSelected($form['bank']);
$select_bank -> output($admin_aziend['masban']);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['reprint']."</td><td  class=\"FacetDataTD\">\n";
$gForm->variousSelect('reprint',$script_transl['reprint_value'],$form['reprint'],'FacetSelect',0,'reprint');
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['num_ini']."</td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"num_ini\" value=\"".$form['num_ini']."\" maxlength=\"5\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['num_fin']."</td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"num_fin\" value=\"".$form['num_fin']."\" maxlength=\"5\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_ini']."</td><td class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_ini',$form['date_ini_D'],$form['date_ini_M'],$form['date_ini_Y'],'FacetSelect',1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_fin']."</td><td class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_fin',$form['date_fin_D'],$form['date_fin_M'],$form['date_fin_Y'],'FacetSelect',1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['period']."</td>
      <td class=\"FacetDataTD\"><input type=\"submit\" name=\"period\" value=\"".$script_transl['period_value']."\">";
echo "</td>\n";
echo "</tr>\n";
echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
echo "<td align=\"left\"><input type=\"submit\" name=\"return\" value=\"".$script_transl['return']."\">\n";
echo '<td align="right"> <input type="submit" accesskey="i" name="preview" value="';
echo $script_transl['view'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";
if (isset($_POST['preview']) and $msg=='') {
  $date_ini =  sprintf("%04d%02d%02d",$form['date_ini_Y'],$form['date_ini_M'],$form['date_ini_D']);
  $date_fin =  sprintf("%04d%02d%02d",$form['date_fin_Y'],$form['date_fin_M'],$form['date_fin_D']);
  $r=getData($date_ini,$date_fin,$form['num_ini'],$form['num_fin'],$form['reprint'],$form['bank']);
  echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
  if (sizeof($r['data'])>0) {
        echo "<tr>";
        echo '<td colspan="7" align="center" class="FacetDataTD">'.$script_transl['preview']."</td>\n";
        echo "</tr>";
        echo "<tr>";
        $linkHeaders=new linkHeaders($script_transl['header']);
        $linkHeaders->output();
        echo "</tr>\n";
    $ctrl_type='';
    foreach($r['data'] as $v) {
        if ($ctrl_type!=$v['tipeff']){
           if ($ctrl_type!='') {
              echo "<tr>";
              echo '<td colspan="3" class="FacetDataTD">n.'.
                 $r['type'][$ctrl_type]['num'].' '.
                 $script_transl['type_value'][$ctrl_type]."</td>\n".
                 '<td class="FacetDataTD" align="right">'.
                 $admin_aziend['html_symbol'].' '.
                 gaz_format_number($r['type'][$ctrl_type]['value'])."</td>\n".
                 '<td colspan="3" class="FacetDataTD"></td>';
              echo "</tr>";
           }
           echo "<tr>";
           echo '<td colspan="7" class="FacetDataTDred">'.$script_transl['type_value'][$v['tipeff']].":</td>\n";
           echo "</tr>";
        }
        echo "<tr>";
        echo "<td class=\"FacetDataTD\"><A HREF=\"./update_effett.php?id_tes=".$v['id_tes']."\">".$v["progre"]."</A></td>";
        echo "<td class=\"FacetDataTD\">".$v["tipeff"]." </td>";
        echo "<td class=\"FacetDataTD\">".gaz_format_date($v["scaden"])."</td>";
        echo "<td class=\"FacetDataTD\" align=\"right\">".$admin_aziend['html_symbol'].' '.gaz_format_number($v["impeff"])." </td>";
        echo "<td class=\"FacetDataTD\">".$v["customer"]." </td>";
        echo "<td class=\"FacetDataTD\"><A HREF=\"http://localhost/gazie/modules/vendit/admin_docven.php?id_tes=".$v['id_tes']."&Update\">n.".$v["numfat"]."/".$v["seziva"]." - ".gaz_format_date($v["datfat"])."</a></td>";
        echo "<td class=\"FacetDataTD\">".$v["desban"]." </td>";
        echo "</tr>\n";
        echo "<tr>";
        if ($v["status"]=='DISTINTATO') {
           echo "<td class=\"FacetDataTDred\" align=\"center\" colspan=\"4\">".strtoupper($script_transl['reprint'])."!";
        } else {
           echo "<td colspan=\"4\">";
        }
        echo "</td>";
        echo "<td>".$v["pariva"]."</td>";
        echo "<td align=\"right\">".gaz_format_number($v["totfat"])."</td>";
        echo "<td align=\"center\">".$v["coordi"]." </td>";
        echo "</tr>\n";
        $ctrl_type=$v['tipeff'];
    }
    echo "<tr>";
    echo '<td colspan="3" class="FacetDataTD">n.'.
                 $r['type'][$ctrl_type]['num'].' '.
                 $script_transl['type_value'][$ctrl_type]."</td>\n".
                 '<td class="FacetDataTD" align="right">'.
                 $admin_aziend['html_symbol'].' '.
                 gaz_format_number($r['type'][$ctrl_type]['value'])."</td>\n".
                 '<td colspan="3" class="FacetDataTD"></td>';
    echo "</tr>";
    echo "<tr>";
    echo '<td colspan="4" class="FacetDataTD">'.
                 $script_transl['tot']." n.".
                 $r['tot']['num'].' '.
                 $admin_aziend['html_symbol'].' '.
                 gaz_format_number($r['tot']['value'])."</td>\n".
                 '<td colspan="3" class="FacetDataTD"></td>';
    echo "</tr>";
    echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
    echo '<td colspan="7" align="right"><input type="submit" name="print" value="';
    echo $script_transl['print'];
    echo '">';
    echo "\t </td>\n";
    echo "\t </tr>\n";
  } else {
        echo "<tr>";
        echo '<td colspan="7" align="center" class="FacetDataTDred">'.$script_transl['errors'][4]."</td>\n";
        echo "</tr>";
  }
  echo "</table>\n";
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
