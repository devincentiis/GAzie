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
$msg = "";

$anno = date("Y");

function ValoriConti($datainizio,$datafine,$datadopo,$mastrocli,$mastrofor,$dettcf) //funzione per la creazione dell'array dei conti con saldo diverso da 0 e ordinati per tipo e numero di conto
{
    global $gTables;
    $sqlquery = 'SELECT codcon, SUM(import) AS somma, darave '.
                'FROM '.$gTables['rigmoc'].' LEFT JOIN '.$gTables['tesmov'].' ON '.
                $gTables['rigmoc'].'.id_tes = '.$gTables['tesmov'].'.id_tes '.
                'WHERE datreg BETWEEN '.$datainizio.' AND '.$datafine.' '.
                'AND caucon <> \'CHI\' AND caucon <> \'APE\' '.
                'OR (caucon = \'APE\' AND datreg BETWEEN '.$datainizio.' AND '.$datadopo.') '.
                'GROUP BY codcon, darave '.
                'ORDER BY codcon desc, darave';
    $rs_castel = gaz_dbi_query($sqlquery);
    $ctrlcodcon=0;
    $ctrlsaldo=0;
	$totclienti=0;
	$totfornitori=0;
    $costi =  array();
    $ricavi =  array();
    $attivo =  array();
    $passivo =  array();
    $clienti =  array();
    $fornitori =  array();
    while ($castel = gaz_dbi_fetch_array($rs_castel)) {
         if ($dettcf==2 && substr($castel["codcon"],0,3)==$mastrocli) {
               $codcon=$mastrocli*1000000;
		 } elseif ($dettcf==2 && substr($castel["codcon"],0,3)==$mastrofor) {
               $codcon=$mastrofor*1000000;
		 } else {
               $codcon=$castel["codcon"];
		 }


         if ($codcon != $ctrlcodcon and $ctrlcodcon != 0 ) {
            if ($ctrlsaldo != 0) {
               $ctrltipcon = substr($ctrlcodcon,0,1);
               switch  ($ctrltipcon){
                       case 4:  //economici
                       case 3:
                       if  ($ctrlsaldo > 0) {
                           $costi[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                       } else {
                           $ricavi[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                       }
                       break;
                       default: //patrimoniali
					   if  ($dettcf==3 && substr($ctrlcodcon,0,3)==$mastrocli) {
                            $clienti[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
							$totclienti += $ctrlsaldo;
					   } elseif ($dettcf==3 && substr($ctrlcodcon,0,3)==$mastrofor) {
                            $fornitori[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
							$totfornitori += $ctrlsaldo;
					   } else {
                          if  ($ctrlsaldo > 0) {
                              $attivo[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                          } else {
                              $passivo[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                          }
					   }
                       break;
                }
            }
            $ctrlsaldo=0;
        }
        if ($castel["darave"] == 'D') {
            $ctrlsaldo += $castel["somma"];
        } else {
            $ctrlsaldo -= $castel["somma"];
        }
        $ctrlcodcon=$codcon;
    }
    if ($ctrlsaldo != 0) {
        $ctrltipcon = substr($ctrlcodcon,0,1);
        switch  ($ctrltipcon){
                case 4:  //economici
                case 3:
                       if  ($ctrlsaldo > 0) {
                           $costi[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                       } else {
                           $ricavi[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                       }
                break;
                default: //patrimoniali
					   if  ($dettcf==3 && substr($ctrlcodcon,0,3)==$mastrocli) {
                            $clienti[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
							$totclienti += $ctrlsaldo;
					   } elseif ($dettcf==3 && substr($ctrlcodcon,0,3)==$mastrofor) {
                            $fornitori[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
							$totfornitori += $ctrlsaldo;
					   } else {
                          if  ($ctrlsaldo > 0) {
                              $attivo[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                          } else {
                              $passivo[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                          }
					   }
                       break;
        }
    }
	if ($dettcf==3) {
       $attivo[$mastrocli*1000000]=number_format($totclienti,2,'.','');
       $passivo[$mastrofor*1000000]=number_format($totfornitori,2,'.','');
    }
    ksort($costi);
    ksort($ricavi);
    ksort($attivo);
    ksort($passivo);
    ksort($clienti);
    ksort($fornitori);
    $conti = array("cos" => $costi,"ric" => $ricavi,"att" => $attivo,"pas" => $passivo,"cli" => $clienti,"for" => $fornitori);
    return $conti;
}

if (!empty($_GET['sd'])){
     $sd = ' checked ';
} else {
     $sd = '';
}

if (empty($_GET['gioini'])) { //al primo accesso allo script
    $_GET['gioini'] = "1";
    $_GET['mesini'] = "1";
    $_GET['annini'] = $anno-1;
    $_GET['giofin'] = "31";
    $_GET['mesfin'] = "12";
    $_GET['annfin'] = $anno-1;
    $_GET['stadef'] = 0;
    $_GET['pagini'] = (!empty($admin_aziend['upginv'])) ? $admin_aziend['upginv']+1 : 1;
    $_GET['dettcf'] = 1;
} else {
	if (!empty($_GET['stadef'])) {
		$sd="checked=\"checked\"";
	}
}

if (!checkdate( $_GET['mesini'], $_GET['gioini'], $_GET['annini'])){
    $msg .= "1+";
}

if (!checkdate( $_GET['mesfin'], $_GET['giofin'], $_GET['annfin'])){
    $msg .= "2+";
}

$utsdop= mktime(0,0,0,$_GET['mesini'],$_GET['gioini']-1,$_GET['annini']+1);
$utsini= mktime(0,0,0,$_GET['mesini'],$_GET['gioini'],$_GET['annini']);
$utsfin= mktime(0,0,0,$_GET['mesfin'],$_GET['giofin'],$_GET['annfin']);
$datadopo = date("Ymd",$utsdop);
$datainizio = date("Ymd",$utsini);
$datafine = date("Ymd",$utsfin);

if ($utsini >= $utsfin)
    $msg .="1-18-2+";

if (!empty($_GET['stampa'])) {
    $locazione = "Location: stampa_bilanc.php?&di=".$datainizio."&df=".$datafine."&pi=".$_GET['pagini']."&cf=".$_GET['dettcf'];
    if (!empty($_GET['stadef'])) {
        $locazione.= "&sd=".$_GET['stadef'];
    }
    header($locazione);
    exit;
}

if (!empty($_GET['Return'])) {
    header("Location:docume_finean.php");
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain();

echo "<form method=\"GET\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".ucfirst($script_transl[0])."</div>\n";
echo "<table class=\"Tmiddle table-striped\" align=\"center\">\n";
if (!empty($msg)) {
    $message = "";
    $rsmsg = array_slice( explode('+',chop($msg)),0,-1);
    foreach ($rsmsg as $value){
            $message .= $script_transl['error']."! -> ";
            $rsval = explode('-',chop($value));
            foreach ($rsval as $valmsg){
                    $message .= $script_transl[$valmsg]." ";
            }
            $message .= "<br />";
    }
    echo '<tr><td colspan="5" class="FacetDataTDred">'.$message."</td></tr>\n";
}
echo "<tr>
     <td class=\"FacetFieldCaptionTD\">".$script_transl[3]."</td>
     <td class=\"FacetDataTD\"><input title=\"$script_transl[20]\" type=\"checkbox\" name=\"stadef\" $sd></td>
     <td class=\"FacetFieldCaptionTD\">".$script_transl[4]."</td>
     <td class=\"FacetDataTD\"><input title=\"$script_transl[21]\" type=\"text\" name=\"pagini\" value=\"".$_GET['pagini']."\" maxlength=\"4\"  class=\"FacetInput\"></td>
     </tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">".$script_transl[1]."</td><td class=\"FacetDataTD\" colspan=\"3\">";
echo "\t <select name=\"gioini\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter = 1; $counter <= 31; $counter++ ){
    $selected = "";
    if($counter ==  $_GET['gioini'])
            $selected = "selected";
    echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"mesini\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
$gazTimeFormatter->setPattern('MMMM');
for( $counter = 1; $counter <= 12; $counter++ ){
    $selected = "";
    if($counter == $_GET['mesini']) $selected = "selected";
    $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
    echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"annini\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter =  $anno-10; $counter <=  $anno+10; $counter++ ){
    $selected = "";
    if($counter == $_GET['annini'])
            $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select></td></tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">".$script_transl[2]."</td><td class=\"FacetDataTD\" colspan=\"3\">";
echo "\t <select name=\"giofin\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter = 1; $counter <= 31; $counter++ ){
    $selected = "";
    if($counter ==  $_GET['giofin'])
            $selected = "selected";
    echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"mesfin\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter = 1; $counter <= 12; $counter++ ){
    $selected = "";
    if($counter == $_GET['mesfin']) $selected = "selected";
    $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
    echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"annfin\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter =  $anno-10; $counter <=  $anno+10; $counter++ ){
    $selected = "";
    if($counter == $_GET['annfin'])
            $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select></td></tr>\n";
echo "<tr>\n\t<td class=\"FacetFieldCaptionTD\">".$script_transl[28]."</td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"3\"><select name=\"dettcf\" class=\"FacetSelect\">\n";
for($cf=1;$cf<4;$cf++){
	echo "\t<option value=\"".$cf."\"";
	if ($_GET['dettcf']==$cf){
		echo "selected=\"selected\"";
	}
	echo ">".$script_transl['cf_value'][$cf]."</option>\n";
}
echo "\t</select>";
echo "</td>\n</tr>\n";

if ($msg == "") {
  echo "<tr><td class=\"bg-info text-center\" colspan=4><input type=\"submit\" class=\"btn btn-info\" name=\"anteprima\" value=\"".$script_transl['view']."\">&nbsp;</td></tr>\n";
}
echo "</table>\n";

if (!empty($_GET['anteprima']) && empty($msg)) {
    $conti = ValoriConti($datainizio,$datafine,$datadopo,$admin_aziend['mascli'],$admin_aziend['masfor'],$_GET['dettcf']);
    if ($conti) {
        $loss = round(array_sum($conti['cos']),2);
        $profit = round(array_sum($conti['ric']),2);
        $assets = round(array_sum($conti['att']),2);
        $liabilities = round(array_sum($conti['pas']),2);
        $ctrl_bal = round($loss + $profit + $assets + $liabilities,2);
        $income = round($loss + $profit,2);
        echo "<br /><table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        if ($ctrl_bal != 0 ) {
          echo "<tr><td colspan=\"4\" class=\"FacetDataTDred\">".$script_transl['error']."! -> ".$admin_aziend['symbol']." ".$ctrl_bal." ".$strScript['select_chiape.php'][14]." <a href=\"".$strMenu2[0][0]."\">".$strMenu2[0][1]."</a></td></tr>\n";
        }
        echo "<tr><td colspan=\"4\" class=\"FacetDataTD\" align=\"center\">".$script_transl['view'].$script_transl[6]."</td></tr>\n";
        //------------- STATO PATRIMONIALE -------------------------
        echo "<tr><td align=\"center\" class=\"FacetFieldCaptionTD\" colspan=\"4\">".$script_transl[9].$script_transl[8].$_GET['giofin']."-".$_GET['mesfin']."-".$_GET['annfin']."</td><tr>";
        $mas=0;
        $ctrlmas=0;
        $totmas=0;
        echo "<tr><td align=\"center\" class=\"FacetDataTDred\">$script_transl[12]</td><td colspan=\"2\"></td><tr>";
        foreach ($conti['att'] as $key => $value) {
            $mas=substr($key,0,3);
            if ($ctrlmas != $mas) {
               if ($ctrlmas != 0) {
                  echo "<tr><td colspan=\"2\"></td><td colspan=\"2\"><hr></td><tr>";
                  echo "<tr><td colspan=\"3\"></td><td align=\"right\" class=\"FacetDataTD\">".gaz_format_number($totmas)."</td><tr>";
                  $totmas = 0;
               }
               $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
               echo "<tr><td class=\"FacetDataTD\">$mas</td><td class=\"FacetDataTD\">".$descri['descri']."</td><td colspan=\"2\"></td><tr>";
            }
            $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
            echo "<tr><td>".$key."</td><td>".$descri['descri']."</td><td align=\"right\">".gaz_format_number($value)."</td><td></td><tr>";
            $totmas += $value;
            $ctrlmas = $mas;
        }
        $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
        echo "<tr><td colspan=\"2\"></td><td colspan=\"2\"><hr></td><tr>";
        echo "<tr><td colspan=\"3\"></td><td align=\"right\" class=\"FacetDataTD\">".gaz_format_number($totmas)."</td><tr>";
        if ($income > 0 ) {    //perdita
            echo "<tr><td colspan=\"2\"></td><td align=\"right\" class=\"FacetDataTDred\">".$script_transl[11]."</td><td align=\"right\">".gaz_format_number($income)."</td><tr>";
            $assets += $income;
        }
        echo "<tr><td colspan=\"2\"></td><td align=\"right\" class=\"FacetDataTD\">".$script_transl[16].$script_transl[12]."</td><td class=\"FacetDataTDred\" align=\"right\">".gaz_format_number($assets)."</td><tr>";
        $ctrlmas=0;
        $totmas=0;
        echo "<tr><td align=\"center\" class=\"FacetDataTDred\">$script_transl[13]</td><td colspan=\"2\"></td><tr>";
        foreach ($conti['pas'] as $key => $value){
            $mas=substr($key,0,3);
            if ($ctrlmas != $mas) {
               if ($ctrlmas != 0) {
                  echo "<tr><td colspan=\"2\"></td><td colspan=\"2\"><hr></td><tr>";
                  echo "<tr><td colspan=\"3\"></td><td align=\"right\" class=\"FacetDataTD\">".gaz_format_number(-$totmas)."</td><tr>";
                  $totmas = 0;
               }
               $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
               echo "<tr><td class=\"FacetDataTD\">$mas</td><td class=\"FacetDataTD\">".$descri['descri']."</td><td colspan=\"2\"></td><tr>";
            }
            $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
            echo "<tr><td>".$key."</td><td>".$descri['descri']."</td><td align=\"right\">".gaz_format_number(-$value)."</td><td></td><tr>";
            $totmas += $value;
            $ctrlmas = $mas;
        }
        $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
        echo "<tr><td colspan=\"2\"></td><td colspan=\"2\"><hr></td><tr>";
        echo "<tr><td colspan=\"3\"></td><td align=\"right\" class=\"FacetDataTD\">".gaz_format_number(-$totmas)."</td><tr>";
        if ($income < 0 ) {    //utile
            echo "<tr><td colspan=\"2\"></td><td align=\"right\" class=\"FacetDataTD\">".$script_transl[10]."</td><td align=\"right\">".gaz_format_number(-$income)."</td><tr>";
            $liabilities += $income;
        }
        echo "<tr><td colspan=\"2\"></td><td align=\"right\" class=\"FacetDataTD\">".$script_transl[16].$script_transl[13]."</td><td class=\"FacetDataTDred\" align=\"right\">".gaz_format_number(-$liabilities)."</td><tr>";
        // ------------------- CONTO ECONOMICO --------------------------------------
        echo "<tr><td align=\"center\" class=\"FacetFieldCaptionTD\" colspan=\"4\">".$script_transl[17].$script_transl[7].$_GET['gioini']."-".$_GET['mesini']."-".$_GET['annini'].$script_transl[8].$_GET['giofin']."-".$_GET['mesfin']."-".$_GET['annfin']."</td><tr>";
        $ctrlmas=0;
        $totmas=0;
        echo "<tr><td align=\"center\" class=\"FacetDataTDred\">$script_transl[15]</td><td colspan=\"2\"></td><tr>";
        foreach ($conti['ric'] as $key => $value){
            $mas=substr($key,0,3);
            if ($ctrlmas != $mas) {
               if ($ctrlmas != 0) {
                  echo "<tr><td colspan=\"2\"></td><td colspan=\"2\"><hr></td><tr>";
                  echo "<tr><td colspan=\"3\"></td><td align=\"right\" class=\"FacetDataTD\">".gaz_format_number(-$totmas)."</td><tr>";
                  $totmas = 0;
               }
               $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
               echo "<tr><td class=\"FacetDataTD\">$mas</td><td class=\"FacetDataTD\">".$descri['descri']."</td><td colspan=\"2\"></td><tr>";
            }
            $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
            echo "<tr><td>".$key."</td><td>".$descri['descri']."</td><td align=\"right\">".gaz_format_number(-$value)."</td><td></td><tr>";
            $totmas += $value;
            $ctrlmas = $mas;
        }
        $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
        echo "<tr><td colspan=\"2\"></td><td colspan=\"2\"><hr></td><tr>";
        echo "<tr><td colspan=\"3\"></td><td align=\"right\" class=\"FacetDataTD\">".gaz_format_number(-$totmas)."</td><tr>";
        if ($income > 0 ) {    //perdita
            echo "<tr><td colspan=\"2\"></td><td align=\"right\" class=\"FacetDataTDred\">".$script_transl[11]."</td><td align=\"right\">".gaz_format_number($income)."</td><tr>";
            $profit -= $income;
        }
        echo "<tr><td colspan=\"2\"></td><td align=\"right\" class=\"FacetDataTD\">".$script_transl[16].$script_transl[15]."</td><td class=\"FacetDataTDred\" align=\"right\">".gaz_format_number(-$profit)."</td><tr>";
        $ctrlmas=0;
        $totmas=0;
        echo "<tr><td align=\"center\" class=\"FacetDataTDred\">$script_transl[14]</td><td colspan=\"2\"></td><tr>";
        foreach ($conti['cos'] as $key => $value){
            $mas=substr($key,0,3);
            if ($ctrlmas != $mas) {
               if ($ctrlmas != 0) {
                  echo "<tr><td colspan=\"2\"></td><td colspan=\"2\"><hr></td><tr>";
                  echo "<tr><td colspan=\"3\"></td><td align=\"right\" class=\"FacetDataTD\">".gaz_format_number($totmas)."</td><tr>";
                  $totmas = 0;
               }
               $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
               echo "<tr><td class=\"FacetDataTD\">$mas</td><td class=\"FacetDataTD\">".$descri['descri']."</td><td colspan=\"2\"></td><tr>";
            }
            $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
            echo "<tr><td>".$key."</td><td>".$descri['descri']."</td><td align=\"right\">".gaz_format_number($value)."</td><td></td><tr>";
            $totmas += $value;
            $ctrlmas = $mas;
        }
        $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
        echo "<tr><td colspan=\"2\"></td><td colspan=\"2\"><hr></td><tr>";
        echo "<tr><td colspan=\"3\"></td><td align=\"right\" class=\"FacetDataTD\">".gaz_format_number($totmas)."</td><tr>";
        if ($income < 0 ) {    //utile
            echo "<tr><td colspan=\"2\"></td><td align=\"right\" class=\"FacetDataTD\">".$script_transl[10]."</td><td align=\"right\">".gaz_format_number(-$income)."</td><tr>";
            $loss -= $income;
        }
        echo "<tr><td colspan=\"2\"></td><td align=\"right\" class=\"FacetDataTD\">".$script_transl[16].$script_transl[14]."</td><td class=\"FacetDataTDred\" align=\"right\">".gaz_format_number($loss)."</td><tr>";
        if ($_GET['dettcf']==3) {
           echo "<tr><td align=\"center\" class=\"FacetDataTDred\">DETTAGLIO CLIENTI E FORNITORI</td><td colspan=\"2\"></td><tr>";
           $totmas=0;
           echo "<tr><td class=\"FacetDataTD\">".$admin_aziend['mascli']."</td><td class=\"FacetDataTD\">CLIENTI</td><td colspan=\"2\"></td><tr>";
           foreach ($conti['cli'] as $key => $value){
               $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
               echo "<tr><td>".$key."</td><td>".$descri['descri']."</td><td align=\"right\">".gaz_format_number($value)."</td><td></td><tr>";
               $totmas += $value;
           }
           $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$admin_aziend['mascli']*1000000);
           echo "<tr><td colspan=\"2\"></td><td colspan=\"2\"><hr></td><tr>";
           echo "<tr><td colspan=\"3\"></td><td align=\"right\" class=\"FacetDataTD\">".gaz_format_number($totmas)."</td><tr>";
           $totmas=0;
           echo "<tr><td class=\"FacetDataTD\">".$admin_aziend['masfor']."</td><td class=\"FacetDataTD\">FORNITORI</td><td colspan=\"2\"></td><tr>";
           foreach ($conti['for'] as $key => $value){
               $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
               echo "<tr><td>".$key."</td><td>".$descri['descri']."</td><td align=\"right\">".gaz_format_number(-$value)."</td><td></td><tr>";
               $totmas += $value;
           }
           $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$admin_aziend['mascli']*1000000);
           echo "<tr><td colspan=\"2\"></td><td colspan=\"2\"><hr></td><tr>";
           echo "<tr><td colspan=\"3\"></td><td align=\"right\" class=\"FacetDataTD\">".gaz_format_number(-$totmas)."</td><tr>";
	    }
      if ($ctrl_bal == 0 ) {
        echo "<tr class=\"FacetFooterTD\"><td colspan=\"4\" align=\"center\"><input type=\"submit\" class=\"btn btn-warning\" name=\"stampa\" value=\"".ucfirst($script_transl['print'].$script_transl[0])."\"></td></tr>";
      }
      echo "</table>\n";
    }
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
