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

function getPreviousCredit($date)
{
        global $gTables,$admin_aziend;
        $rs_last_opening = gaz_dbi_dyn_query("*", $gTables['tesmov'], "caucon = 'APE' AND datliq <= ".$date,"datliq DESC",0,1);
        $last_opening = gaz_dbi_fetch_array($rs_last_opening);
        if ($last_opening) {
           $date_ini = substr($last_opening['datliq'],0,4).substr($last_opening['datliq'],5,2).substr($last_opening['datliq'],8,2);
        } else {
           $date_ini = '20040101';
        }
        if ($date_ini>$date) {
           $date_ini = '20040101';
        }
        $utsdatera = mktime(12,0,0,substr($date,4,2)+2,0,substr($date,0,4));
        $date_era=date("Ymd",$utsdatera);
        $where = "(datliq BETWEEN $date_ini AND $date AND (codcon=".$admin_aziend['ivaven']." OR codcon=".$admin_aziend['ivacor']." OR codcon=".$admin_aziend['ivaacq']."))
                 OR (datliq BETWEEN $date_ini AND $date_era AND codcon=".$admin_aziend['ivaera'].") GROUP BY darave";
        $orderby = " datliq ";
        $select = "darave,SUM(import) AS value";
        $table = $gTables['tesmov']." LEFT JOIN ".$gTables['rigmoc']." ON ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes ";
        $rs=gaz_dbi_dyn_query ($select, $table, $where, $orderby);
        $m=0;
        while ($r = gaz_dbi_fetch_array($rs)) {
           if ($r['darave']=='D'){
              $m+=$r['value'];
           } else {
              $m-=$r['value'];
           }
        }
        $m=round($m,2);
        if ($m<0){$m=0;}
        return $m;
}

function getMovements($date_ini,$date_fin)
{
        global $gTables,$admin_aziend;
        $where = "datliq BETWEEN $date_ini AND $date_fin GROUP BY seziva,regiva,codiva";
        $orderby="seziva, regiva, datliq, protoc";
        $rs=gaz_dbi_dyn_query("seziva,regiva,codiva,periva,operat,
                               SUM((imponi*(operat = 1) - imponi*(operat = 2))*(-2*(regiva = 6)+1)) AS imp,
                               SUM((impost*(operat = 1) - impost*(operat = 2))*(-2*(regiva = 6)+1)) AS iva,
                               SUM(impost*(regiva = 9)) AS vers,
                              ".$gTables['aliiva'].".descri AS desvat,
                              ".$gTables['aliiva'].".tipiva AS tipiva",
        $gTables['rigmoi']." LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoi'].".id_tes = ".$gTables['tesmov'].".id_tes
        LEFT JOIN ".$gTables['aliiva']." ON ".$gTables['rigmoi'].".codiva = ".$gTables['aliiva'].".codice",$where,$orderby);
        $m=array();
        $m['tot']=0;
        $m['versamenti']=array();
        while ($r=gaz_dbi_fetch_array($rs)) {
          if ($r['tipiva']=='D'){ // iva indetraibile
             $r['isp']=0;
             $r['ind']=$r['iva'];
             $r['iva']=0;
          } elseif ($r['tipiva']=='T'){ // iva split payment
             $r['isp']=$r['iva'];
             $r['ind']=0;
             $r['iva']=0;
          } else { // iva normale
             $r['ind']=0;
             $r['isp']=0;
          }
		  if($r['regiva']==9){
			$m['versamenti'][]=$r;
		  }else{
			$m['data'][]=$r;
			if(!isset($m['tot_rate'][$r['codiva']])) {
              $m['tot_rate'][$r['codiva']]=$r;
			}else{
              $m['tot_rate'][$r['codiva']]['imp']+=$r['imp'];
              $m['tot_rate'][$r['codiva']]['iva']+=$r['iva'];
              $m['tot_rate'][$r['codiva']]['ind']+=$r['ind'];
              $m['tot_rate'][$r['codiva']]['isp']+=$r['isp'];
			}
			$m['tot']+=$r['iva'];
		  }
        }
        return $m;
}

function sales_by_region($date_ini,$date_fin)
{
        global $gTables,$admin_aziend;
        $where = "regiva <= 4 AND datliq BETWEEN $date_ini AND $date_fin GROUP BY ".$gTables['anagra'].".country, ".$gTables['anagra'].".prospe, azienda";
        $orderby=$gTables['country'].".istat_area, ".$gTables['country'].".iso, ".$gTables['regions'].".id, ".$gTables['anagra'].".prospe, azienda";
        $rs=gaz_dbi_dyn_query("prospe, 1*(CAST(".$gTables['anagra'].".pariva AS UNSIGNED)>10000) AS azienda, SUM((imponi*(operat = 1) - imponi*(operat = 2))*(-2*(regiva = 6)+1)) AS imponibile, SUM((impost*(operat = 1) - impost*(operat = 2))*(-2*(regiva = 6)+1)) AS iva, ".$gTables['country'].".name AS nazione, ".$gTables['regions'].".name AS regione, ".$gTables['regions'].".stat_code AS codice_regione, ".$gTables['anagra'].".prospe AS provincia, regiva AS registro, COUNT(*) AS righi",
        $gTables['rigmoi']." LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoi'].".id_tes = ".$gTables['tesmov'].".id_tes
        LEFT JOIN ".$gTables['aliiva']." ON ".$gTables['rigmoi'].".codiva = ".$gTables['aliiva'].".codice
        LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['tesmov'].".clfoco = ".$gTables['clfoco'].".codice
        LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id
        LEFT JOIN ".$gTables['provinces']." ON ".$gTables['anagra'].".prospe = ".$gTables['provinces'].".abbreviation
        LEFT JOIN ".$gTables['regions']." ON (".$gTables['provinces'].".id_region = ".$gTables['regions'].".id AND ".$gTables['regions'].".iso_country = ".$gTables['anagra'].".country)
        LEFT JOIN ".$gTables['country']." ON ".$gTables['anagra'].".country = ".$gTables['country'].".iso",$where,$orderby);
        $m=array();
		$ctrl_nazione='INIZIO';
		$ctrl_regione='INIZIO';
		$ctrl_provinc='INIZIO';
		$m['italia']['totali']['totale_imponibile']=0.00;
		$m['italia']['totali']['totale_iva']=0.00;
  		$m['italia']['totali']['imponibile_aziende']=0.00;
		$m['italia']['totali']['iva_aziende']=0.00;
		$m['italia']['totali']['imponibile_consfin']=0.00;
		$m['italia']['totali']['iva_consfin']=0.00;
        while ($r=gaz_dbi_fetch_array($rs)) {
			if ($r['nazione']=='ITALY'){ //clienti italia
			 $m['italia']['totali']['totale_imponibile']+=$r['imponibile'];
			 $m['italia']['totali']['totale_iva']+=$r['iva'];
			 // qui separo le province di trento e bolzano in due regioni separate
			 if ($r['prospe']=='BZ'){ //bolzano
				$r['regione']='Bolzano';
				$r['codice_regione']=21; // forzo a 21 per aver 'VT4' della dichiarazione IVA
			 }
			 if ($r['prospe']=='TN'){ //trento
				$r['regione']='Trento';
			 }
			 if ($r['provincia']!=$ctrl_provinc){ // ho un cambio di provincia valorizzo i totali ad essa relativi
				$m['italia']['righi'][$r['provincia']]= $r;
				$m['italia']['righi'][$r['provincia']]['imponibile_aziende']=0.00;
				$m['italia']['righi'][$r['provincia']]['iva_aziende']=0.00;
				$m['italia']['righi'][$r['provincia']]['imponibile_consfin']=0.00;
				$m['italia']['righi'][$r['provincia']]['iva_consfin']=0.00;
			 } else {
				$m['italia']['righi'][$r['provincia']]['imponibile']+=$r['imponibile'];
				$m['italia']['righi'][$r['provincia']]['iva']+=$r['iva'];
			 }
			 if ($r['regione']!=$ctrl_regione){ // ho un cambio di regione azzero i totali ad essa relativi
			    $m['italia'][$r['regione']]['totale_imponibile']=$r['imponibile'];
			    $m['italia'][$r['regione']]['totale_iva']=$r['iva'];
  			    $m['italia'][$r['regione']]['imponibile_aziende']=0.00;
			    $m['italia'][$r['regione']]['iva_aziende']=0.00;
			    $m['italia'][$r['regione']]['imponibile_consfin']=0.00;
			    $m['italia'][$r['regione']]['iva_consfin']=0.00;
			 } else {
			    $m['italia'][$r['regione']]['totale_imponibile']+=$r['imponibile'];
			    $m['italia'][$r['regione']]['totale_iva']+=$r['iva'];
			 }
			 if ($r['azienda']==0){ // consumatore finale
				$m['italia']['righi'][$r['provincia']]['imponibile_consfin']+=$r['imponibile'];
				$m['italia']['righi'][$r['provincia']]['iva_consfin']+=$r['iva'];
			    $m['italia'][$r['regione']]['imponibile_consfin']+=$r['imponibile'];
			    $m['italia'][$r['regione']]['iva_consfin']+=$r['iva'];
				$m['italia']['totali']['imponibile_consfin']+=$r['imponibile'];
				$m['italia']['totali']['iva_consfin']+=$r['iva'];
			 }else{
				$m['italia']['righi'][$r['provincia']]['imponibile_aziende']+=$r['imponibile'];
				$m['italia']['righi'][$r['provincia']]['iva_aziende']+=$r['iva'];
  			    $m['italia'][$r['regione']]['imponibile_aziende']+=$r['imponibile'];
			    $m['italia'][$r['regione']]['iva_aziende']+=$r['iva'];
				$m['italia']['totali']['imponibile_aziende']+=$r['imponibile'];
				$m['italia']['totali']['iva_aziende']+=$r['iva'];
			 }
			} elseif ($r['nazione']==''){ //clienti anonimi, per esempio quelli serviti con scontrini
			 $proazienda = gaz_dbi_get_row($gTables['provinces'],'abbreviation',$admin_aziend['prospe']);
			 $regazienda = gaz_dbi_get_row($gTables['regions'],'id',$proazienda['id_region']);
     		 $r['regione']=$regazienda['name']; // per gli anonimi uso la regione dell'indirizzo aziendale
     		 $r['codice_regione']=$regazienda['id'];
			 // qui separo le province di trento e bolzano in due regioni separate
			 if ($admin_aziend['prospe']=='BZ'){ //bolzano
				$r['regione']='Bolzano';
				$r['codice_regione']=21; // forzo a 21 per aver 'VT4' della dichiarazione IVA
			 }
			 if ($admin_aziend['prospe']=='TN'){ //trento
				$r['regione']='Trento';
			 }
			 $m['anonimi'][$r['regione']]= $r;
			} else { // clienti esteri
			 if ($r['nazione']!=$ctrl_nazione){ // ho un cambio di nazione azzero i totali ad esso relativi
			  $m['estero'][$r['nazione']]= $r;
			 } else {
			  $m['estero'][$r['nazione']]['imponibile'] += $r['imponibile'];
			  $m['estero'][$r['nazione']]['iva'] += $r['iva'];
			 }
			}
			$ctrl_provinc=$r['provincia'];
			$ctrl_regione=$r['regione'];
			$ctrl_nazione=$r['nazione'];
        }
		return $m;
}
$gForm = new contabForm();
require("./lang." . $admin_aziend['lang'] . ".php");
$transl=$strScript['report_comunicazioni_dati_fatture.php'];
if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    require("lang.".$admin_aziend['lang'].".php");
    $form['descri'] = $strScript[$scriptname]['descri_value'][$admin_aziend['ivam_t']];
    if ($admin_aziend['ivam_t'] == 'M') {
       $utsdatini = mktime(12,0,0,date("m")-1,1,date("Y"));
       $utsdatfin = mktime(12,0,0,date("m"),0,date("Y"));
       $utsdatcar = mktime(12,0,0,date("m")-1,0,date("Y"));
    } else {
       if (date("m") >= 1 and date("m") < 4) {
          $utsdatini = mktime(12,0,0,10,1,date("Y")-1);
          $utsdatfin = mktime(12,0,0,12,31,date("Y")-1);
          $utsdatcar = mktime(12,0,0,9,30,date("Y"));
       } elseif (date("m") >= 4 and date("m") < 7) {
          $utsdatini = mktime(12,0,0,1,1,date("Y"));
          $utsdatfin = mktime(12,0,0,3,31,date("Y"));
          $utsdatcar = mktime(12,0,0,12,31,date("Y")-1);
       } elseif (date("m") >= 7 and date("m") < 10) {
          $utsdatini = mktime(12,0,0,4,1,date("Y"));
          $utsdatfin = mktime(12,0,0,6,31,date("Y"));
          $utsdatcar = mktime(12,0,0,3,31,date("Y"));
       } else {  // <=10 e <=12
          $utsdatini = mktime(12,0,0,7,1,date("Y"));
          $utsdatfin = mktime(12,0,0,9,30,date("Y"));
          $utsdatcar = mktime(12,0,0,6,30,date("Y"));
       }
    }
    $dtiniobj = new DateTime('@'.$utsdatini);
    $dtfinobj = new DateTime('@'.$utsdatfin);
    $form['date_ini_D']=1;
    $form['date_ini_M']=date("m",$utsdatini);
    $form['date_ini_Y']=date("Y",$utsdatini);
    $form['date_fin_D']=date("d",$utsdatfin);
    $form['date_fin_M']=date("m",$utsdatfin);
    $form['date_fin_Y']=date("Y",$utsdatfin);
    $form['sta_def']=false;
    $form['cover']=false;
    $upgrie = gaz_dbi_get_row($gTables['company_data'],'var','upgrie');
    $form['page_ini'] = intval($upgrie['data'])+1;
    $form['carry']=getPreviousCredit(date("Ymd",$utsdatcar));
    $pro_rata = gaz_dbi_get_row($gTables['company_data'], 'var', 'pro_rata'.$form['date_ini_Y'], '', 'data');
    $form['pro_rata'] = (empty($pro_rata)) ? 0 : $pro_rata;
    $form['advance']=0;
} else { // accessi successivi
    $form['hidden_req']=htmlentities($_POST['hidden_req']);
    $form['ritorno']=$_POST['ritorno'];
    $form['date_ini_D']=intval($_POST['date_ini_D']);
    $form['date_ini_M']=intval($_POST['date_ini_M']);
    $form['date_ini_Y']=intval($_POST['date_ini_Y']);
    $form['date_fin_D']=intval($_POST['date_fin_D']);
    $form['date_fin_M']=intval($_POST['date_fin_M']);
    $form['date_fin_Y']=intval($_POST['date_fin_Y']);
    $form['carry']=floatval(preg_replace("/\,/",'.',$_POST['carry']));
    $form['pro_rata']=intval($_POST['pro_rata']);
	if ($form['date_fin_M'] == 12) {
		$form['advance']=floatval(preg_replace("/\,/",'.',$_POST['advance']));
	}
    if (isset($_POST['sta_def'])){
       $form['sta_def']=substr($_POST['sta_def'],0,8);
    } else {
       $form['sta_def']='';
    }
    if (isset($_POST['cover'])){
       $form['cover']=substr($_POST['cover'],0,8);
    } else {
       $form['cover']='';
    }
    $dtiniobj = new DateTime('@'.mktime(12,0,0,$form['date_ini_M'],$form['date_ini_D'],$form['date_ini_Y']));
    $dtfinobj = new DateTime('@'.mktime(12,0,0,$form['date_fin_M'],$form['date_fin_D'],$form['date_fin_Y']));
    if ($form['hidden_req']=='vat_reg' || $form['hidden_req']=='vat_section'){
      //require("lang.".$admin_aziend['lang'].".php");
      $form['descri'] = ''; //$strScript[$scriptname]['descri_value'][$admin_aziend['ivam_t']];
      $form['page_ini'] = getPage_ini($form['vat_section'],$form['vat_reg']);
      if ($admin_aziend['ivam_t'] == 'M') {
//        $gazTimeFormatter->setPattern('MMMM yyyy');
//        $form['descri'].=ucwords($gazTimeFormatter->format($dtiniobj));
      } else {
//        $gazTimeFormatter->setPattern('MMMM');
//        $form['descri'].=ucwords($gazTimeFormatter->format($dtiniobj)).' - ';
//        $gazTimeFormatter->setPattern('MMMM yyyy');
//        $form['descri'].=ucwords($gazTimeFormatter->format($dtfinobj));
      }
      $form['hidden_req']='';
    } elseif ($form['hidden_req']=='date_fin'){
       if ($admin_aziend['ivam_t'] == 'M') {
          $utsdatcar = mktime(12,0,0,$form['date_ini_M'],0,$form['date_fin_Y']);
       } else {
          if ($form['date_fin_M'] >= 1 && $form['date_fin_M'] < 4) {
             $utsdatcar = mktime(12,0,0,9,30,$form['date_fin_Y']);
          } elseif ($form['date_fin_M'] >= 4 && $form['date_fin_M'] < 7) {
             $utsdatcar = mktime(12,0,0,12,31,$form['date_fin_Y']-1);
          } elseif ($form['date_fin_M'] >= 7 && $form['date_fin_M'] < 10) {
             $utsdatcar = mktime(12,0,0,3,31,$form['date_fin_Y']);
          } else { // <=10 e <=12
             $utsdatcar = mktime(12,0,0,6,30,$form['date_fin_Y']);
          }
       }
       $form['carry']=getPreviousCredit(date("Ymd",$utsdatcar));
       $form['page_ini'] = intval($_POST['page_ini']);
       $form['descri']=substr($_POST['descri'],0,50);
       $form['hidden_req']='';
    } else {
       $form['page_ini'] = intval($_POST['page_ini']);
       $form['descri']=substr($_POST['descri'],0,50);
       $form['hidden_req']='';
    }
    if (isset($_POST['return'])) {
        header("Location: ".$form['ritorno']);
        exit;
    }
    $form['descri'] = $gForm->getPeriodicyDescription([$dtiniobj->format('d-m-Y'),$dtfinobj->format('d-m-Y')],$transl);
}

//controllo i campi
if (!checkdate( $form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']) ||
    !checkdate( $form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y'])) {
    $msg .='0+';
}
$utsini= mktime(12,0,0,$form['date_ini_M'],$form['date_ini_D'],$form['date_ini_Y']);
$utsfin= mktime(12,0,0,$form['date_fin_M'],$form['date_fin_D'],$form['date_fin_Y']);
if ($utsini > $utsfin) {
    $msg .='1+';
}
// fine controlli

if (isset($_POST['print']) && $msg=='') {
    $_SESSION['print_request']=array('script_name'=>'stampa_liqiva',
                                     'ds'=>$form['descri'],
                                     'pi'=>$form['page_ini'],
                                     'sd'=>$form['sta_def'],
                                     'mt'=>'',
                                     'cv'=>$form['cover'],
                                     'cr'=>$form['carry'],
                                     'pr'=>$form['pro_rata'],
                                     'ad'=>(isset($form['advance']) ? $form['advance'] : 0),
                                     'ri'=>date("dmY",$utsini),
                                     'rf'=>date("dmY",$utsfin)
                                     );
    header("Location: sent_print.php");
    exit;
}
require("../../library/include/header.php");
$script_transl=HeadMain(0,['calendarpopup/CalendarPopup']);
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
}";
echo "
   function preStampa() // stampa il dettaglio del preventivo senza salvarlo
    {
        var mywindow = window.open('', 'my div', 'height=600,width=800');
        mywindow.document.write('<html><head><title>Stampa riparto vendite</title>');
        mywindow.document.write('</head><body >');
        mywindow.document.write('<table name=lista border=1> ');
        mywindow.document.write($('[name=\"elenco\"]').html());
        mywindow.document.write('</table> ');
        mywindow.document.write('</body></html>');
        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10
        mywindow.print();
        mywindow.close();
        return true;
    }
";
echo "</script>";
echo "<form method=\"POST\" name=\"select\">\n";
echo "<input type=\"hidden\" value=\"".$form['hidden_req']."\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\" />\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="4" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
}
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['page_ini']."</td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"page_ini\" value=\"".$form['page_ini']."\" maxlength=\"5\"  /></td>\n";
echo "</td>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['sta_def']."</td><td class=\"FacetDataTD\">\n";
$gForm->selCheckbox('sta_def',$form['sta_def'],$script_transl['sta_def_title']);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['descri']."</td>\n";
echo "\t<td colspan=\"3\" class=\"FacetDataTD\"><input type=\"text\" name=\"descri\" value=\"".$form['descri']."\" maxlength=\"50\"  /></td>\n";
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_ini']."</td><td colspan=\"3\" class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_ini',$form['date_ini_D'],$form['date_ini_M'],$form['date_ini_Y'],'FacetSelect',1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_fin']."</td><td colspan=\"3\" class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_fin',$form['date_fin_D'],$form['date_fin_M'],$form['date_fin_Y'],'FacetSelect','date_fin');
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['cover']."</td><td colspan=\"3\" class=\"FacetDataTD\">\n";
$gForm->selCheckbox('cover',$form['cover']);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['carry'].": </td>\n";
echo "\t<td colspan=\"3\" class=\"FacetDataTD\"><input type=\"text\" name=\"carry\" value=\"".$form['carry']."\" maxlength=\"15\"  /></td>\n";
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['pro_rata'].": </td>\n";
echo "\t<td colspan=\"3\" class=\"FacetDataTD\"><input type=\"text\" name=\"pro_rata\" value=\"".$form['pro_rata']."\" maxlength=\"2\"  /></td>\n";
echo "</td>\n";
echo "</tr>\n";
if ($form['date_fin_M'] == 12) {
	echo "<tr>\n";
	echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['advance'].": </td>\n";
	echo "\t<td colspan=\"3\" class=\"FacetDataTD\"><input type=\"text\" name=\"advance\" value=\"".$form['advance']."\" maxlength=\"15\"  /></td>\n";
	echo "</td>\n";
	echo "</tr>\n";
}
echo "\t<tr>\n";
echo '<td colspan=4 class="FacetFooterTD text-center"><input type="submit" class="btn btn-info" accesskey="i" name="preview" value="';
echo $script_transl['view'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";
if (isset($_POST['preview']) and $msg=='') {
  $date_ini =  sprintf("%04d%02d%02d",$form['date_ini_Y'],$form['date_ini_M'],$form['date_ini_D']);
  $date_fin =  sprintf("%04d%02d%02d",$form['date_fin_Y'],$form['date_fin_M'],$form['date_fin_D']);
  $m=getMovements($date_ini,$date_fin);
  echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
  if (isset($m['data']) && sizeof($m['data']) > 0) {
		$totiva_acquisti=0;
        $err=0;
        echo "<tr>";
        $linkHeaders=new linkHeaders($script_transl['header']);
        $linkHeaders->output();
        echo "</tr>\n";
        foreach($m['data'] as $k=>$v) {
           echo "<tr align=\"right\">\n";
           echo "<td>".$v['seziva']."</td><td align=\"center\">".$script_transl['regiva_value'][$v['regiva']]."</td><td>".$v['desvat']."</td><td>".gaz_format_number($v['imp'])."</td>";
           echo "<td>".$v['periva']."% </td><td>".gaz_format_number($v['iva'])."</td><td>".gaz_format_number($v['ind'])."</td>\n";
           echo "<td>".gaz_format_number($v['ind']+$v['imp']+$v['iva']+$v['isp'])."</td>\n";
           echo "</tr>\n";
		   if($v['regiva']==6){$totiva_acquisti+= $v['iva'];}
        }
        echo "<tr><td colspan=8><HR></td></tr>";
        foreach($m['tot_rate'] as $k=>$v) {
           echo "<tr align=\"right\">\n";
           echo "<td colspan=\"2\"></td><td>".$v['desvat']."</td><td>".gaz_format_number($v['imp'])."</td>";
           echo "<td>".$v['periva']."% </td><td>".gaz_format_number($v['iva'])."</td><td>".gaz_format_number($v['ind'])."</td>\n";
           echo "<td>".gaz_format_number($v['ind']+$v['imp']+$v['iva']+$v['isp'])."</td>\n";
           echo "</tr>\n";
        }
		if ($form['pro_rata'] > 0) {
			$tot_pro_rata = -$totiva_acquisti*((100-$form['pro_rata'])/100);
           echo "<tr align=\"right\">\n";
           echo "<td colspan=\"2\"></td><td>".$script_transl['pro_rata']."</td><td></td>";
           echo "<td></td><td>".gaz_format_number($tot_pro_rata)."</td><td></td>\n";
           echo "<td>".gaz_format_number($tot_pro_rata)."</td>\n";
           echo "</tr>\n";
		}
		if (!empty($tot_pro_rata)) {
			$m['tot']+= $tot_pro_rata;
		}
        if ($m['tot']<0){
           echo "<tr><td colspan=2></td><td class=\"FacetDataTDred\" align=\"right\" colspan=3>".$script_transl['tot'].$script_transl['t_neg']."</td><td class=\"FacetDataTDred\" align=\"right\">".gaz_format_number($m['tot'])."</td></tr>";
        } else {
           echo "<tr><td colspan=2></td><td class=\"FacetDataTD\" align=\"right\" colspan=3>".$script_transl['tot'].$script_transl['t_pos']."</td><td class=\"FacetDataTD\" align=\"right\">".gaz_format_number($m['tot'])."</td></tr>";
        }
		if (sizeof($m['versamenti']) > 0) {
			foreach($m['versamenti'] as $k=>$v) {
				echo "<tr><td colspan=2></td><td class=\"danger\" align=\"right\" colspan=3>".$script_transl['regiva_value'][$v['regiva']]."</td><td class=\"danger\" align=\"right\">".$v['vers']."</td></tr>";
			}
		}
        if ($err==0) {
            echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
            echo '<td colspan=8 class="FacetFooterTD text-center"><input type="submit" class="btn btn-warning" name="print" value="';
            echo $script_transl['print'];
            echo '">';
            echo "\t </td>\n";
            echo "\t </tr>\n";
        } else {
            echo "<tr>";
            echo "<td colspan=\"7\" align=\"right\" class=\"FacetDataTDred\">".$script_transl['errors']['err']."</td>";
            echo "</tr>\n";
        }
  }
  echo "</table>\n";
  $r=sales_by_region($date_ini,$date_fin);
?>
<table name="elenco" class="Tlarge table table-striped"><tbody>
	<tr><td colspan="8" class="text-center"><b><?php echo $admin_aziend['ragso1'];?> <br/>
	 RIPARTIZIONE DELLE VENDITE PER TIPO CLIENTI E NAZIONI-REGIONI-PROVINCIE</b><br/>
	 <?php echo 'dal '.$form['date_ini_D'].'-'.$form['date_ini_M'].'-'.$form['date_ini_Y'].' al '.$form['date_fin_D'].'-'.$form['date_fin_M'].'-'.$form['date_fin_Y'];?>
	 </td></tr>
<?php
  if (isset($r['anonimi'])){
	  $va=current($r['anonimi']);

?>
	<tr class="text-success">
		<td colspan="8"><b>VENDITE ANONIME</b></td>
	</tr>
	<tr>
		<td colspan="8" class="danger">ATTENZIONE!!! VERRANNO IMPUTATE alle regione <b><?php echo $va['regione']; ?></b>, sede dell'azienda, LE VENDITE EFFETTUATE VERSO CLIENTI CONSUMATORI FINALI ANONIMI (es. con scontrini fiscali)</td>
	</tr>
	<tr>
		<td><?php echo $va['regione']; ?></td>
		<td colspan="5"></td>
		<td class="danger text-right"><?php echo gaz_format_number($va['imponibile']); ?> </td>
		<td class="danger text-right"><?php echo gaz_format_number($va['iva']); ?> </td>
	</tr>
<?php
  }
  if (isset($r['italia']['righi'])){
?>
	<tr class="text-success">
		<td colspan="8"><b>VENDITE ITALIA
		</b></td>
	</tr>
	<tr>
		<td>REGIONE</td>
		<td>PROVINCIA</td>
		<td>IMPONIBILE TOTALE</td>
		<td>IVA TOTALE</td>
		<td>IMPONIBILE AZIENDE</td>
		<td>IVA AZIENDE</td>
		<td>IMPONIBILE CONSUMATORI FINALI</td>
		<td>IVA CONSUMATORI FINALI</td>
	</tr>
<?php
	$ctrl_codice_regione=0;
	$ctrl_regione='';
    foreach($r['italia']['righi'] as $k=>$v) {
	 if ($v['codice_regione']!=$ctrl_codice_regione && $ctrl_codice_regione>0){ // ho un cambio di regione scrivo i totali della precedente
	  // controllo se questa regione ha vendite a consumatori finali anonimi
	  if(isset($va) && $va['regione']==$ctrl_regione){
		  $r['italia'][$ctrl_regione]['iva_consfin']+=$va['iva'];
		  $r['italia'][$ctrl_regione]['imponibile_consfin']+=$va['imponibile'];
		  $r['italia']['totali']['totale_iva']+=$va['iva'];
		  $r['italia']['totali']['totale_imponibile']+=$va['imponibile'];
		  $r['italia']['totali']['iva_consfin']+=$va['iva'];
		  $r['italia']['totali']['imponibile_consfin']+=$va['imponibile'];
?>
	<tr>
		<td class="text-right gaz-costi" colspan="6"><b>Aggiungo i valori derivanti alle vendite fatte a consumatori finali anonimi ----> </b> </td>
		<td class="text-right gaz-costi"><?php echo gaz_format_number($va['imponibile']); ?></td>
		<td class="text-right gaz-costi"><?php echo gaz_format_number($va['iva']); ?></td>
	</tr>
<?php
	  }
?>
	<tr>
		<td colspan="2" class="gaz-attivo">Totali <b><?php echo $ctrl_regione; ?></b></td>
		<td class="text-right gaz-attivo"><?php echo gaz_format_number($r['italia'][$ctrl_regione]['totale_imponibile']); ?> </td>
		<td class="text-right gaz-attivo"><?php echo gaz_format_number($r['italia'][$ctrl_regione]['totale_iva']); ?> </td>
		<td class="text-right gaz-attivo"><?php echo gaz_format_number($r['italia'][$ctrl_regione]['imponibile_aziende']); ?> </td>
		<td class="text-right gaz-attivo"><?php echo gaz_format_number($r['italia'][$ctrl_regione]['iva_aziende']); ?> </td>
		<td class="text-right gaz-attivo"><?php if ($r['italia'][$ctrl_regione]['imponibile_consfin']>=0.01) { echo '<span><b>'.rif_dichiarazione_iva($ctrl_codice_regione,$year=2019).'-1 <br/>'.gaz_format_number($r['italia'][$ctrl_regione]['imponibile_consfin']).'</b></span>';} ?></td>
		<td class="text-right gaz-attivo"><?php if ($r['italia'][$ctrl_regione]['iva_consfin']>=0.01) { echo '<span><b>'.rif_dichiarazione_iva($ctrl_codice_regione,$year=2019).'-2 <br/>'.gaz_format_number($r['italia'][$ctrl_regione]['iva_consfin']).'</b></span>';} ?></td>
	</tr>
<?php
	 }
?>
	<tr>
		<td><?php echo $v['regione']; ?></td>
		<td><?php echo $v['provincia']; ?></td>
		<td class="text-right"><?php echo gaz_format_number($v['imponibile']); ?> </td>
		<td class="text-right"><?php echo gaz_format_number($v['iva']); ?> </td>
		<td class="text-right"><?php echo gaz_format_number($v['imponibile_aziende']); ?> </td>
		<td class="text-right"><?php echo gaz_format_number($v['iva_aziende']); ?> </td>
		<td class="text-right"><?php echo gaz_format_number($v['imponibile_consfin']); ?> </td>
		<td class="text-right"><?php echo gaz_format_number($v['iva_consfin']); ?> </td>
	</tr>
<?php
	 $ctrl_regione=$v['regione'];
	 $ctrl_codice_regione=$v['codice_regione'];
    }
	  // controllo se questa regione ha vendite a consumatori finali anonimi
	  if(isset($va) && $va['regione']==$ctrl_regione){
		  $r['italia'][$ctrl_regione]['iva_consfin']+=$va['iva'];
		  $r['italia'][$ctrl_regione]['imponibile_consfin']+=$va['imponibile'];
		  $r['italia']['totali']['totale_iva']+=$va['iva'];
		  $r['italia']['totali']['totale_imponibile']+=$va['imponibile'];
		  $r['italia']['totali']['iva_consfin']+=$va['iva'];
		  $r['italia']['totali']['imponibile_consfin']+=$va['imponibile'];
?>
	<tr>
		<td class="text-right gaz-costi" colspan="6"><b>Aggiungo i valori derivanti alle vendite fatte a consumatori finali anonimi ----></b></td>
		<td class="text-right gaz-costi"><?php echo gaz_format_number($va['imponibile']); ?></td>
		<td class="text-right gaz-costi"><?php echo gaz_format_number($va['iva']); ?></td>
	</tr>
<?php
	  }
?>
	<tr>
		<td colspan="2" class="gaz-attivo">Totali <b><?php echo $ctrl_regione; ?></b></td>
		<td class="text-right gaz-attivo"><?php echo gaz_format_number($r['italia'][$ctrl_regione]['totale_imponibile']); ?> </td>
		<td class="text-right gaz-attivo"><?php echo gaz_format_number($r['italia'][$ctrl_regione]['totale_iva']); ?> </td>
		<td class="text-right gaz-attivo"><?php echo gaz_format_number($r['italia'][$ctrl_regione]['imponibile_aziende']); ?> </td>
		<td class="text-right gaz-attivo"><?php echo gaz_format_number($r['italia'][$ctrl_regione]['iva_aziende']); ?> </td>
		<td class="text-right gaz-attivo"><?php if ($r['italia'][$ctrl_regione]['imponibile_consfin']>=0.01) { echo '<span><b>'.rif_dichiarazione_iva($ctrl_codice_regione,$year=2019).'-1 <br/>'.gaz_format_number($r['italia'][$ctrl_regione]['imponibile_consfin']).'</b></span>';} ?></td>
		<td class="text-right"><?php if ($r['italia'][$ctrl_regione]['iva_consfin']>=0.01) { echo '<span><b>'.rif_dichiarazione_iva($ctrl_codice_regione,$year=2019).'-2 <br/>'.gaz_format_number($r['italia'][$ctrl_regione]['iva_consfin']).'</b></span>';} ?></td>
	</tr>
	<tr>
		<td colspan="2" class="gaz-attivo"><b>TOTALI ITALIA</b></td>
		<td class="text-right gaz-attivo"><?php echo '<b>VT1-1 <br/>'.gaz_format_number($r['italia']['totali']['totale_imponibile']); ?></b></td>
		<td class="text-right gaz-attivo"><?php echo '<b>VT1-2 <br/>'.gaz_format_number($r['italia']['totali']['totale_iva']); ?></b></td>
		<td class="text-right gaz-attivo"><?php echo '<b>VT1-5 <br/>'.gaz_format_number($r['italia']['totali']['imponibile_aziende']); ?></b></td>
		<td class="text-right gaz-attivo"><?php echo '<b>VT1-6 <br/>'.gaz_format_number($r['italia']['totali']['iva_aziende']); ?></b></td>
		<td class="text-right gaz-attivo"><?php echo '<b>VT1-3 <br/>'.gaz_format_number($r['italia']['totali']['imponibile_consfin']); ?></b></td>
		<td class="text-right gaz-attivo"><?php echo '<b>VT1-4 <br/>'.gaz_format_number($r['italia']['totali']['iva_consfin']); ?> </b></td>
	</tr>
<?php
  }
  if (isset($r['estero'])){
?>
	<tr class="text-success">
		<td colspan="8"><b>VENDITE ESTERO</b></td>
	</tr>
<?php
   foreach($r['estero'] as $k=>$v) {
?>
	<tr>
		<td colspan="2" ><b><?php echo $k; ?></b></td>
		<td class="text-right"><?php echo gaz_format_number($v['imponibile']); ?> </td>
		<td class="text-right"><?php echo gaz_format_number($v['iva']); ?> </td>
		<td colspan="5"></td>
	</tr>
<?php
	}
  }
?>
</tbody>
</table>
<div class="text-center FacetFooterTD">
	  <input name="prestampa" class="btn btn-warning" id="preventDuplicate" onClick="preStampa();" type="button" value="STAMPA RIPARTO TIPO VENDITE">
</div>
<?php
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
