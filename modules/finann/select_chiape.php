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
if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
    gaz_set_time_limit (0);
}

function accountValue($last_closing,$date_closing) //funzione per la creazione dell'array dei conti con saldo diverso da 0 e ordinati per tipo e numero di conto
{
    global $gTables;
    $where = "datreg BETWEEN $last_closing AND $date_closing GROUP BY codcon";
    $orderby = " codcon ASC ";
    $select = $gTables['clfoco'].".descri AS name,codcon,(SUM(import*(darave='D')) - SUM(import*(darave='A'))) AS val";
    $table = $gTables['rigmoc']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['rigmoc'].".codcon = ".$gTables['clfoco'].".codice "
            ."LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes ";
    $rs=gaz_dbi_dyn_query($select, $table, $where, $orderby);
    $result=array();
    $result['att']=array();
    $result['pas']=array();
    $result['cos']=array();
    $result['ric']=array();
    $result['tot']['cos']=0;
    $result['tot']['ric']=0;
    $result['tot']['att']=0;
    $result['tot']['pas']=0;
    while ($r = gaz_dbi_fetch_array($rs)) {
       if ($r['val'] <> 0) {
               $type='pas';
               switch  (substr($r['codcon'],0,1)) {
                       case 4:  //economici
                       case 3:
                         if  ($r['val'] > 0) {
                           $type='cos';
                         } else {
                           $type='ric';
                         }
                       break;
                       default: //patrimoniali
                       if  ($r['val'] > 0) {
                           $type='att';
                       }
                       break;
                }
                $result[$type][$r['codcon']]=$r;
                $result['tot'][$type]+=$r['val'];
       }
    }
    return $result;
}

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['date_closing_D']=31;
    $form['date_closing_M']=12;
    $form['date_closing_Y']=date("Y")-1;
    $form['date_opening_D']=1;
    $form['date_opening_M']=1;
    $form['date_opening_Y']=date("Y");
    $form['closing_balance']=$admin_aziend['closing_balance'];
    $form['economic_result']=$admin_aziend['economic_result'];
    $form['operating_profit']=$admin_aziend['operating_profit'];
    $form['operating_losses']=$admin_aziend['operating_losses'];
    $form['opening_balance']=$admin_aziend['opening_balance'];
} else { // accessi successivi
    $form['hidden_req']=htmlentities($_POST['hidden_req']);
    $form['ritorno']=$_POST['ritorno'];
    $form['date_closing_D']=intval($_POST['date_closing_D']);
    $form['date_closing_M']=intval($_POST['date_closing_M']);
    $form['date_closing_Y']=intval($_POST['date_closing_Y']);
    $form['date_opening_D']=intval($_POST['date_opening_D']);
    $form['date_opening_M']=intval($_POST['date_opening_M']);
    $form['date_opening_Y']=intval($_POST['date_opening_Y']);
    $form['closing_balance']=intval($_POST['closing_balance']);
    $form['economic_result']=intval($_POST['economic_result']);
    $form['operating_profit']=intval($_POST['operating_profit']);
    $form['operating_losses']=intval($_POST['operating_losses']);
    $form['opening_balance']=intval($_POST['opening_balance']);
    if (isset($_POST['return'])) {
        header("Location: ".$form['ritorno']);
        exit;
    }
}

//controllo i campi
if (!checkdate( $form['date_closing_M'], $form['date_closing_D'], $form['date_closing_Y']) ||
    !checkdate( $form['date_opening_M'], $form['date_opening_D'], $form['date_opening_Y'])) {
    $msg .='0+';
}
$uts_date_closing= mktime(0,0,0,$form['date_closing_M'],$form['date_closing_D'],$form['date_closing_Y']);
$uts_last_closing= mktime(0,0,0,$form['date_closing_M'],$form['date_closing_D']+1,$form['date_closing_Y']-1);
$uts_date_opening= mktime(0,0,0,$form['date_opening_M'],$form['date_opening_D'],$form['date_opening_Y']);

if ($uts_date_closing > $uts_date_opening) {
    $msg .='1+';
}

//controllo se sono state gi&agrave; fatte delle chiusure durante l'anno...
$rs_chiusu = gaz_dbi_dyn_query ('*', $gTables['tesmov'], "caucon='CHI' AND datreg BETWEEN ".date("Ymd",$uts_last_closing)." AND ".date("Ymd",$uts_date_closing),'datreg',0,1);
$nummov = gaz_dbi_num_rows($rs_chiusu);
if ($nummov > 0) {
   $msg .= "2+";
}

// fine controlli

if (isset($_POST['genera']) and $msg == "") {
    $accounts = accountValue(date("Ymd",$uts_last_closing),date("Ymd",$uts_date_closing));
    $loss = $accounts['tot']['cos'];
    $profit = $accounts['tot']['ric'];
    $assets = $accounts['tot']['att'];
    $liabilities = $accounts['tot']['pas'];
    $income = $loss+$profit;

    //--------------------------- CHIUSURA ----------------------------
    $newValue=array('caucon'=>'CHI',
                    'descri'=> substr($_POST['acc_c'],0,50),
                    'datreg'=> date("Y-m-d",$uts_date_closing)
                    );
    tesmovInsert($newValue);
    $last_id = gaz_dbi_last_id();
    foreach ($accounts['cos'] as $k=>$v){
       rigmocInsert(array('id_tes'=>$last_id,'darave'=>'A','codcon'=>$k,'import'=>$v['val']));
    }
    if ($income<0){   //profitto
            if (isset($accounts['pas'][$form['operating_profit']]['val'])) {
               $accounts['pas'][$form['operating_profit']]['val']+=$income;
            } else {
               $accounts['pas'][$form['operating_profit']]=array('val'=>$income,'name'=>'');
            }
            $liabilities += $income;
            rigmocInsert(array('id_tes'=>$last_id,'darave'=>'A','codcon'=>$form['economic_result'],'import'=>(-$income)));
    }
    foreach ($accounts['ric'] as $k=>$v){
       rigmocInsert(array('id_tes'=>$last_id,'darave'=>'D','codcon'=>$k,'import'=>(-$v['val'])));
    }
    if ($income>0) {    //perdita
            if (isset($accounts['att'][$form['operating_losses']]['val'])) {
               $accounts['att'][$form['operating_losses']]['val']+=$income;
            } else {
              $accounts['att'][$form['operating_losses']]=array('val'=>$income,'name'=>'');
            }
            $assets += $income;
            rigmocInsert(array('id_tes'=>$last_id,'darave'=>'D','codcon'=>$form['economic_result'],'import'=>$income));
    }
    tesmovInsert($newValue);
    $last_id=gaz_dbi_last_id();
    if ($income<0) {    //profitto
        rigmocInsert(array('id_tes'=>$last_id,'darave'=>'D','codcon'=>$form['economic_result'],'import'=>(-$income)));
        rigmocInsert(array('id_tes'=>$last_id,'darave'=>'A','codcon'=>$form['operating_profit'],'import'=>(-$income)));
    } else {   //perdita
        rigmocInsert(array('id_tes'=>$last_id,'darave'=>'A','codcon'=>$form['economic_result'],'import'=>$income));
        rigmocInsert(array('id_tes'=>$last_id,'darave'=>'D','codcon'=>$form['operating_losses'],'import'=>$income));
    }
    tesmovInsert($newValue);
    $last_id = gaz_dbi_last_id();
    foreach ($accounts['att'] as $k=>$v){
        rigmocInsert(array('id_tes'=>$last_id,'darave'=>'A','codcon'=>$k,'import'=>$v['val']));
    }
    rigmocInsert(array('id_tes'=>$last_id,'darave'=>'D','codcon'=>$form['closing_balance'],'import'=>$assets));
    tesmovInsert($newValue);
    $last_id = gaz_dbi_last_id();
    foreach ($accounts['pas'] as $k=>$v){
        rigmocInsert(array('id_tes'=>$last_id,'darave'=>'D','codcon'=>$k,'import'=>(-$v['val'])));
    }
    rigmocInsert(array('id_tes'=>$last_id,'darave'=>'A','codcon'=>$form['closing_balance'],'import'=>(-$liabilities)));

    // --------------------------- APERTURA ----------------------------
    $newValue=array('caucon'=>'APE',
                    'descri'=>substr($_POST['acc_o'],0,50),
                    'datreg'=>date("Y-m-d",$uts_date_opening)
                    );
    tesmovInsert($newValue);
    $last_id = gaz_dbi_last_id();
    foreach ($accounts['att'] as $k=>$v){
       rigmocInsert(array('id_tes'=>$last_id,'darave'=>'D','codcon'=>$k,'import'=>$v['val']));
    }
    rigmocInsert(array('id_tes'=>$last_id,'darave'=>'A','codcon'=>$form['opening_balance'],'import'=>$assets));
    tesmovInsert($newValue);
    $last_id = gaz_dbi_last_id();
    foreach ($accounts['pas'] as $k=>$v){
       rigmocInsert(array('id_tes'=>$last_id,'darave'=>'A','codcon'=>$k,'import'=>(-$v['val'])));
    }
    rigmocInsert(array('id_tes'=>$last_id,'darave'=>'D','codcon'=>$form['opening_balance'],'import'=>(-$liabilities)));
    // ----------- AGGIORNO I VALORI DEI CONTI PREDEFINITI DELL'AZIENDA ---------
    gaz_dbi_put_row($gTables['aziend'],'codice',1,'closing_balance',$form['closing_balance']);
    gaz_dbi_put_row($gTables['aziend'],'codice',1,'economic_result',$form['economic_result']);
    gaz_dbi_put_row($gTables['aziend'],'codice',1,'operating_profit',$form['operating_profit']);
    gaz_dbi_put_row($gTables['aziend'],'codice',1,'operating_losses',$form['operating_losses']);
    gaz_dbi_put_row($gTables['aziend'],'codice',1,'opening_balance',$form['opening_balance']);
    header("Location:../contab/report_movcon.php");
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
echo "<input type=\"hidden\" value=\"\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\" />\n";
echo "<input type=\"hidden\" value=\"".$script_transl['acc_o']."\" name=\"acc_o\">\n";
echo "<input type=\"hidden\" value=\"".$script_transl['acc_c']."\" name=\"acc_c\">\n";
$gForm = new finannForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_closing']."</td><td class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_closing',$form['date_closing_D'],$form['date_closing_M'],$form['date_closing_Y'],'FacetSelect',1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_opening']."</td><td class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_opening',$form['date_opening_D'],$form['date_opening_M'],$form['date_opening_Y'],'FacetSelect',1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['closing_balance']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectAccount('closing_balance',$form['closing_balance'],5);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['economic_result']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectAccount('economic_result',$form['economic_result'],5);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['operating_profit']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectAccount('operating_profit',$form['operating_profit'],2);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['operating_losses']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectAccount('operating_losses',$form['operating_losses'],2);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['opening_balance']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectAccount('opening_balance',$form['opening_balance'],5);
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
  $accounts = accountValue(date("Ymd",$uts_last_closing),date("Ymd",$uts_date_closing));
  if ($accounts) {
        $anagrafica = new Anagrafica();
        $loss = $accounts['tot']['cos'];
        $profit = $accounts['tot']['ric'];
        $assets = $accounts['tot']['att'];
        $liabilities = $accounts['tot']['pas'];
        $ctrl_bal = round($loss + $profit + $assets + $liabilities,2);
        $income = round($loss + $profit,2);
        $eco = $anagrafica->getPartner($form['economic_result']);
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        if ($ctrl_bal != 0 ) {
          echo "<tr><td colspan=\"4\" ><a href=\"./error_rigmoc.php\" class=\"FacetDataTDred\">".$script_transl['errors'][3]."! -> ".$admin_aziend['symbol']." $ctrl_bal </a></td></tr>";
        }
        echo "<tr><td><hr></td><td class=\"FacetFieldCaptionTD\" align=\"center\">".$script_transl['view'].$script_transl['closing'].$script_transl['of'].date("d-m-Y",$uts_date_closing)."</td><td colspan=\"2\"><hr></td></tr>";
        echo "<tr><td>".$script_transl['code']."</td><td>".$script_transl['descr']."</td><td align=\"right\">".$script_transl['entry']."</td><td align=\"right\">".$script_transl['exit']."</td><tr>";
        echo "<tr><td class=\"FacetSelect\" align=\"center\" colspan=\"4\">".$script_transl['economic']."</td></tr>";
        echo "<tr><td class=\"FacetSelect\">".$script_transl['costs']."</td><td colspan=\"3\"></td></tr>";
        foreach ($accounts['cos'] as $k => $v){
             echo "<tr><td>".$k."</td><td>".$v['name']."</td><td></td><td align=\"right\">".gaz_format_number($v['val'])."</td><tr>";
        }
        if ($income < 0 ) {   //profitto
            $pro = $anagrafica->getPartner($form['operating_profit']);
            if (isset($accounts['pas'][$form['operating_profit']]['val'])) {
               $accounts['pas'][$form['operating_profit']]['val']+=$income;
            } else {
               $accounts['pas'][$form['operating_profit']]=array('val'=>$income,'name'=>$pro['descri']);
            }
            $liabilities += $income;
            echo "<tr><td>".$form['economic_result']."</td><td>".$eco['descri']."</td><td></td><td align=\"right\">".gaz_format_number(-$income)."</td><tr>";
            echo "<tr><td>".$form['economic_result']."</td><td>".$eco['descri']."</td><td align=\"right\">".gaz_format_number(-$income)."</td><td></td><tr>";
            echo "<tr><td class=\"FacetDataTD\">".$form['operating_profit']."</td><td class=\"FacetDataTD\">".$pro['descri']."</td><td class=\"FacetDataTD\">".$script_transl['operating_profit']."</td><td class=\"FacetDataTD\" align=\"right\">".gaz_format_number(-$income)."</td><tr>";
        }
        echo "<tr><td class=\"FacetSelect\">".$script_transl['revenues']."</td><td colspan=\"3\"></td></tr>";
        foreach ($accounts['ric'] as $k => $v){
             echo "<tr><td>".$k."</td><td>".$v['name']."</td><td align=\"right\">".gaz_format_number(-$v['val'])."</td><td></td><tr>";
        }
        if ($income > 0 ) {    //perdita
            $los = $anagrafica->getPartner($form['operating_losses']);
            if (isset($accounts['att'][$form['operating_losses']]['val'])) {
               $accounts['att'][$form['operating_losses']]['val']+=$income;
            } else {
              $accounts['att'][$form['operating_losses']]=array('val'=>$income,'name'=>$los['descri']);
            }
            $assets += $income;
            echo "<tr><td>".$form['economic_result']."</td><td>".$eco['descri']."</td><td></td><td align=\"right\">".gaz_format_number($income)."</td><tr>";
            echo "<tr><td>".$form['economic_result']."</td><td>".$eco['descri']."</td><td align=\"right\">".gaz_format_number($income)."</td><td></td><tr>";
            echo "<tr><td class=\"FacetDataTDred\">".$form['operating_losses']."</td><td class=\"FacetDataTDred\">".$los['descri']."</td><td class=\"FacetDataTDred\">".$script_transl['operating_losses']."</td><td class=\"FacetDataTDred\" align=\"right\">".gaz_format_number($income)."</td><tr>";
        }
        echo "<tr><td class=\"FacetSelect\" align=\"center\" colspan=\"4\">".$script_transl['sheet']."</td></tr>";
        echo "<tr><td class=\"FacetSelect\">".$script_transl['assets']."</td><td colspan=\"3\"></td></tr>";
        foreach ($accounts['att'] as $k => $v){
              echo "<tr><td>".$k."</td><td>".$v['name']."</td><td></td><td align=\"right\">".gaz_format_number($v['val'])."</td><tr>";
        }
        $clo = $anagrafica->getPartner($form['closing_balance']);
        echo "<tr><td class=\"FacetSelect\" >".$form['closing_balance']."</td><td class=\"FacetSelect\" >".$clo['descri']."</td><td  class=\"FacetSelect\"  align=\"right\">".gaz_format_number($assets)."</td><td></td><tr>";
        echo "<tr><td class=\"FacetSelect\">".$script_transl['liabilities']."</td><td colspan=\"3\"></td></tr>";
        foreach ($accounts['pas'] as $k => $v){
            echo "<tr><td>".$k."</td><td>".$v['name']."</td><td align=\"right\">".gaz_format_number(-$v['val'])."</td><td></td><tr>";
        }
        echo "<tr><td>".$form['closing_balance']."</td><td>".$clo['descri']."</td><td></td><td align=\"right\">".gaz_format_number(-$liabilities)."</td><tr>";
        echo "<tr><td><hr></td><td class=\"FacetFieldCaptionTD\" align=\"center\">".$script_transl['view'].$script_transl['opening'].$script_transl['of'].date("d-m-Y",$uts_date_opening)."</td><td colspan=\"2\"><hr></td></tr>";
        echo "<tr><td>".$script_transl['code']."</td><td>".$script_transl['descr']."</td><td align=\"right\">".$script_transl['entry']."</td><td align=\"right\">".$script_transl['exit']."</td><tr>";
        echo "<tr><td class=\"FacetSelect\">".$script_transl['assets']."</td><td colspan=\"3\"></td></tr>";
        foreach ($accounts['att'] as $k => $v){
            echo "<tr><td>".$k."</td><td>".$v['name']."</td><td align=\"right\">".gaz_format_number($v['val'])."</td><td></td><tr>";
        }
        $ope = $anagrafica->getPartner($form['opening_balance']);
        echo "<tr><td>".$form['opening_balance']."</td><td>".$ope['descri']."</td><td></td><td align=\"right\">".gaz_format_number($assets)."</td><tr>";
        echo "<tr><td class=\"FacetSelect\">".$script_transl['liabilities']."</td><td colspan=\"3\"></td></tr>";
        foreach ($accounts['pas'] as $k => $v){
            echo "<tr><td>".$k."</td><td>".$v['name']."</td><td></td><td align=\"right\">".gaz_format_number(-$v['val'])."</td><tr>";
        }
        echo "<tr><td>".$form['opening_balance']."</td><td>".$ope['descri']."</td><td align=\"right\">".gaz_format_number(-$liabilities)."</td></td><td><tr>";
        if ($ctrl_bal == 0 ) {
          echo "<tr><td colspan=\"4\" align=\"center\"><input type=\"submit\" name=\"genera\" value=\"".ucfirst($script_transl['submit'])." !\"></td></tr>";
        }
    }
    echo "</table>";
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>