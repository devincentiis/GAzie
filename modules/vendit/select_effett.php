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
$msg='';

function getMovements($date_ini,$date_fin,$num_ini=1,$num_fin=999999999)
    {
        global $gTables,$admin_aziend;
        $m=array();
        $where="scaden BETWEEN ".$date_ini." AND ".$date_fin." AND
                progre BETWEEN ".$num_ini." AND ".$num_fin;
        $what=$gTables['effett'].".*, ".
              $gTables['clfoco'].".codice, ".
              $gTables['banapp'].".descri AS desban, ".
              $gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2 ";
        $table=$gTables['effett']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['effett'].".clfoco = ".$gTables['clfoco'].".codice
               LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra
               LEFT JOIN ".$gTables['banapp']." ON ".$gTables['banapp'].".codice = ".$gTables['effett'].".banapp";
        $rs=gaz_dbi_dyn_query ($what,$table,$where,"tipeff ASC, scaden ASC, progre ASC");
        while ($r = gaz_dbi_fetch_array($rs)) {
            $m[] = $r;
        }
        return $m;
    }

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
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
       $item=gaz_dbi_get_row($gTables['effett'],'id_tes',intval($_GET['id']));
       $form['num_ini']=$item['progre'];
       $form['num_fin']=$item['progre'];
    }  else {
       if (isset($_GET['ni'])) {
          $form['num_ini']=intval($_GET['ni']);
       } else {
          $form['num_ini']=1;
          //getExtremeValue($gTables['tipeff']);
       }
       if (isset($_GET['nf'])) {
          $form['num_fin']=intval($_GET['nf']);
       } else {
          $form['num_fin']=9999999;
          //getExtremeValue($gTables['tipeff'],'MAX');
       }
    }
} else { // accessi successivi
    $form['hidden_req']=htmlentities($_POST['hidden_req']);
    $form['ritorno']=$_POST['ritorno'];
    $form['date_ini_D']=intval($_POST['date_ini_D']);
    $form['date_ini_M']=intval($_POST['date_ini_M']);
    $form['date_ini_Y']=intval($_POST['date_ini_Y']);
    $form['date_fin_D']=intval($_POST['date_fin_D']);
    $form['date_fin_M']=intval($_POST['date_fin_M']);
    $form['date_fin_Y']=intval($_POST['date_fin_Y']);
    $form['num_ini']=intval($_POST['num_ini']);
    $form['num_fin']=intval($_POST['num_fin']);
    if (isset($_POST['return'])) {
        header("Location: ".$form['ritorno']);
        exit;
    }
}

//controllo i campi
if (!checkdate( $form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']) ||
    !checkdate( $form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y'])) {
    $msg .='0+';
}
$utsini= mktime(0,0,0,$form['date_ini_M'],$form['date_ini_D'],$form['date_ini_Y']);
$utsfin= mktime(0,0,0,$form['date_fin_M'],$form['date_fin_D'],$form['date_fin_Y']);
if ($utsini > $utsfin) {
    $msg .='1+';
}
if ($form['num_ini']>$form['num_fin']) {
    $msg .='3+';
}
// fine controlli

if (isset($_POST['print']) && $msg=='') {
    if ($form['num_fin']==0){
        $form['num_fin']=$form['num_ini'];
    }
    $_SESSION['print_request']=array('script_name'=>'stampa_effett',
                                     'id_tes'=>'SEL',
                                     'proini'=>$form['num_ini'],
                                     'profin'=>$form['num_fin'],
                                     'scaini'=>date("Ymd",$utsini),
                                     'scafin'=>date("Ymd",$utsfin),
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
$gForm = new GazieForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="2" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_ini']."</td><td  class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_ini',$form['date_ini_D'],$form['date_ini_M'],$form['date_ini_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_fin']."</td><td  class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_fin',$form['date_fin_D'],$form['date_fin_M'],$form['date_fin_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['num_ini']."</td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"num_ini\" value=\"".$form['num_ini']."\" maxlength=\"9\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['num_fin']."</td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"num_fin\" value=\"".$form['num_fin']."\" maxlength=\"9\"  /></td>\n";
echo "</tr>\n";
echo "\t<tr>\n";
echo "<td class=\"FacetFooterTD\"></td>\n";
echo '<td class="FacetFooterTD text-center"><input type="submit" class="btn btn-info" accesskey="i" name="preview" value="';
echo $script_transl['view'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";

$date_ini =  sprintf("%04d%02d%02d",$form['date_ini_Y'],$form['date_ini_M'],$form['date_ini_D']);
$date_fin =  sprintf("%04d%02d%02d",$form['date_fin_Y'],$form['date_fin_M'],$form['date_fin_D']);

if (isset($_POST['preview']) and $msg=='') {
  $m=getMovements($date_ini,$date_fin,$form['num_ini'],$form['num_fin']);
  echo "<table class=\"Tlarge table table-striped table-responsive\">";
  if (sizeof($m) > 0) {
        $ctr_mv='';
        echo "<tr>";
        $linkHeaders=new linkHeaders($script_transl['header']);
        $linkHeaders->output();
        echo "</tr>";
		foreach ($m AS $key => $mv) {
            if ($ctr_mv != $mv['tipeff']) {
                  echo "\t<tr>\n";
                  echo "\t<td colspan=\"6\">".$mv['tipeff'].' - '.$script_transl['type_value'][$mv['tipeff']]."</td>\n";
                  echo "\t </tr>\n";
            }
            echo "<td align=\"center\" class=\"FacetDataTD\"><A HREF=\"./update_effett.php?id_tes=".$mv['id_tes']."\">".$mv['progre']."</a></td>";
            echo "<td align=\"center\" class=\"FacetDataTD\">".gaz_format_date($mv['scaden'])."</td>";
            echo "<td align=\"right\" class=\"FacetDataTD\">".gaz_format_number($mv['impeff'])."</td>";
            echo "<td align=\"center\" class=\"FacetDataTD\">".$mv['ragso1'].' '.$mv['ragso2']."</td>";
            echo "<td align=\"center\" class=\"FacetDataTD\"> n.".$mv['numfat'].' sez.'.$mv['seziva']." del ".gaz_format_date($mv['datfat'])."</td>\n";
            echo "<td align=\"center\" class=\"FacetDataTD\">".$mv['desban']."</td>\n";
            echo "</tr>\n";
            $ctr_mv = $mv['tipeff'];
         }
         echo "\t<tr>\n";
         echo '<td class="FacetFooterTD text-center" colspan=7><input type="submit" class="btn btn-warning" name="print" value="';
         echo $script_transl['print'];
         echo '">';
         echo "\t </td>\n";
         echo "\t </tr>\n";
  }
  echo "</table>\n";
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
