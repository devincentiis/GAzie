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
  scriva   alla   Free  Software Foundation,  Inc.,   59
  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
$msg = '';


function getPage_ini($sez, $reg) {
    global $gTables;
    if ($reg == 6) {
        $reg = 'upgac' . $sez;
    } elseif ($reg == 4) {
        $reg = 'upgco' . $sez;
    } else {
        $reg = 'upgve' . $sez;
    }
    $r = gaz_dbi_get_row($gTables['company_data'], 'var', $reg);
    return (!is_numeric($r['data'])) ? 1 : $r['data'] + 1;
}

function getLastMonth($sez, $reg) { // Antonio Germani - funzione per recuperare, dal DB, l'ultimo mese stampato
    global $gTables;
    if ($reg == 6) {
        $reg = 'umeac' . $sez;
    } elseif ($reg == 4) {
        $reg = 'umeco' . $sez;
    } else {
        $reg = 'umeve' . $sez;
    }
    $r = gaz_dbi_get_row($gTables['company_data'], 'var', $reg);
	return $r['data'];
}

function getMovements($vat_section, $vat_reg, $date_ini, $date_fin) {
    global $gTables, $admin_aziend;
    // BEGIN fromthestone: prendo il valore della configurazione per numerazione note credito/debito
    $num_nc_nd_res = gaz_dbi_get_row($gTables['company_config'], 'var', 'num_note_separate');
    $num_nc_nd=(isset($num_nc_nd_res))?intval($num_nc_nd_res['val']):0;
    // END fromthestone
    $m = array();
    $where = "(datreg BETWEEN $date_ini AND $date_fin OR datliq BETWEEN $date_ini AND $date_fin) AND seziva = $vat_section AND regiva = $vat_reg";
    $orderby = "datreg, protoc";
    $rs = gaz_dbi_dyn_query("YEAR(datreg) AS ctrl_sr,
					  DATE_FORMAT(datliq,'%Y%m%d') AS dl,
                      DATE_FORMAT(datreg,'%Y%m%d') AS dr,
                      CONCAT(" . $gTables['anagra'] . ".ragso1, ' '," . $gTables['anagra'] . ".ragso2) AS ragsoc,clfoco,codiva,
                      protoc,numdoc,datreg,datliq,datdoc,caucon,regiva,operat,imponi,impost,periva,
                      " . $gTables['tesmov'] . ".descri AS descri,
                      " . $gTables['aliiva'] . ".descri AS desvat,
                      " . $gTables['tesmov'] . ".id_tes AS id_tes,
                      " . $gTables['rigmoi'] . ".tipiva AS tipiva", $gTables['rigmoi'] . " LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoi'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes
        LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesmov'] . ".clfoco = " . $gTables['clfoco'] . ".codice
        LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['anagra'] . ".id = " . $gTables['clfoco'] . ".id_anagra
        LEFT JOIN " . $gTables['aliiva'] . " ON " . $gTables['rigmoi'] . ".codiva = " . $gTables['aliiva'] . ".codice", $where, $orderby);
    $c_sr = 0;
    $c_liq = true;
    $c_id = 0;
    $c_p = 0;
    $c_nabs = 0; // nemmto il numero documento assoluto, mi serve per controllare se la sequenza dei numeri quando questa comprende tutti i tipi di documenti (fatt, nc, nd, ecc.)
    $c_ndoc = [];
    while ($r = gaz_dbi_fetch_array($rs)) {
      $r['numdoc']=is_numeric($r['numdoc'])?$r['numdoc']: (int)filter_var($r['numdoc'], FILTER_SANITIZE_NUMBER_INT);;
      // inizio controllo errori di numerazione
      $date_reg=gaz_format_date($r['datreg'],false,3);
      if (empty($r['tipiva'])) {  // errore: aliquota IVA non tipizzata
        $r['err_t'] = 'ERROR';
      }
      if ($c_sr != ($r['ctrl_sr'])) { // devo azzerare tutto perché cambiato l'anno
        $c_sr = 0;
        $c_liq = true;
        $c_id = 0;
        $c_p = 0;
        $c_nabs = $r['numdoc'];
        $c_ndoc = [];
        if ($r['protoc'] <> 1) { // errore: il protocollo non é 1
          // non lo rilevo in quanto i registri IVA non sono annuali
        }
      } else {
        $ex = $c_p + 1;
        $c_nabs++;
        if ($r['protoc'] <> $ex && $r['id_tes'] <> $c_id) {  // errore: il protocollo non è consecutivo
          if ($date_reg>=$date_ini&&$date_reg<=$date_fin && !$c_liq){ // controllo solo i movimenti registrati nel periodo selezionato, gli altri liquidabili no
              $r['err_p'] = $ex;
              print '<br/>'.$c_liq.'<br>';
          }
        }
      }
      if ($r['regiva'] < 4 && $vat_section <> $admin_aziend['reverse_charge_sez']) { // il controllo sul numero solo per i registri delle fatture di vendita e non reverse charge
        // fromthestone: comportamento standard, note credito e debito con diversa numerazione da fatture ->
        // num_note_separate = 1
        // per evitare la segnalazione di errore quando si passa da fattura immediata a differita e viceversa
        if ($num_nc_nd == 1) {
          $r['caucon'] = ($r['caucon']=='FAD')?'FAI':$r['caucon'];
        } else {
          // fromthestone: note credito e debito stessa numerazione da fatture
          $r['caucon'] = ($r['caucon'] == 'FAD' || $r['caucon'] == 'FNC' || $r['caucon'] == 'FND') ? 'FAI' : $r['caucon'];
        }
        if (isset($c_ndoc[$r['caucon']])) { // controllo se il numero precedente è questo-1
          $ex = $c_ndoc[$r['caucon']] + 1;
          if ($r['numdoc'] <> $ex && $r['numdoc'] <> $c_nabs && $c_id <> $r['id_tes']) {  // errore: il numero non è consecutivo
            $r['err_n'] = $ex;
          }
        } else {  // dal primo documento di questo tipo ci si aspetta il n.1
          if ($r['numdoc'] <> 1) { // errore: il numero non � 1
            // non lo rilevo in quanto i registri IVA non sono annuali
          }
        }
      }
      $c_ndoc[$r['caucon']] = $r['numdoc'];
      $c_nabs = $r['numdoc'];
      $c_sr = $r['ctrl_sr'];
      $c_liq = ( substr($r['datreg'],0,4).substr($r['datreg'],5,2) <> substr($r['datliq'],0,4).substr($r['datliq'],5,2) ) ? true : false; // escludo il prossimo controllo protocollo perché questa è una liquidazione posticipata
      $c_id = $r['id_tes'];
      $c_p = $r['protoc'];
      // fine controllo errori di numerazione
      $m[] = $r;
    }
    return $m;
}

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
  $form['hidden_req'] = '';
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  require("lang." . $admin_aziend['lang'] . ".php");
	$last_month_print = intval(getLastMonth(1,2)); // Antonio Germani - prendo l'ultimo mese stampato dal DB e propongo nel form il mese successivo
  if ($admin_aziend['ivam_t'] == 'M') {
      $utsdatini = mktime(0, 0, 0, $last_month_print + 1, 1, date("Y"));
      $utsdatfin = mktime(0, 0, 0, $last_month_print + 2, 0, date("Y"));
  } elseif ($last_month_print >= 1 and $last_month_print < 4) {
      $utsdatini = mktime(0, 0, 0, 4, 1, date("Y"));
      $utsdatfin = mktime(0, 0, 0, 6, 30, date("Y"));
  } elseif ($last_month_print >= 4 and $last_month_print < 7) {
      $utsdatini = mktime(0, 0, 0, 7, 1, date("Y"));
      $utsdatfin = mktime(0, 0, 0, 9, 30, date("Y"));
  } elseif ($last_month_print >= 7 and $last_month_print < 10) {
      $utsdatini = mktime(0, 0, 0, 10, 1, date("Y"));
      $utsdatfin = mktime(0, 0, 0, 12, 31, date("Y"));
  } elseif ($last_month_print >= 10 and $last_month_print <= 12) {
      $utsdatini = mktime(0, 0, 0, 1, 1, date("Y")+1);
      $utsdatfin = mktime(0, 0, 0, 3, 31, date("Y")+1);
  } elseif ($last_month_print == 0 ) {
      $utsdatini = mktime(0, 0, 0, 1, 1, date("Y"));
      $utsdatfin = mktime(0, 0, 0, 3, 31, date("Y"));
  }
  $form['jump'] = 'jump';
  $form['date_ini_D'] = 1;
  $form['date_ini_M'] = date("m", $utsdatini);
  $form['date_ini_Y'] = date("Y", $utsdatini);
  $form['date_fin_D'] = date("d", $utsdatfin);
  $form['date_fin_M'] = date("m", $utsdatfin);
  $form['date_fin_Y'] = date("Y", $utsdatfin);
  $form['vat_section'] = 1;
  $form['vat_reg'] = 2;
  $form['lastvatreg'] = $form['vat_reg'];
  $form['lastvatsection'] = $form['vat_section'];
  $form['sta_def'] = false;
  $form['sem_ord'] = 1;
  $form['cover'] = false;
  $form['page_ini'] = getPage_ini(1, 2);
} else { // accessi successivi
  $form['hidden_req'] = htmlentities($_POST['hidden_req']);
  $form['ritorno'] = $_POST['ritorno'];
	$form['lastvatreg']=$_POST['lastvatreg'];
	$form['lastvatsection']=$_POST['lastvatsection'];
	// Antonio Germani - se è stato cambiato registro IVA o sezione IVA prendo l'ultimo mese stampato dal DB e propongo nel form il mese successivo
	if (intval($_POST['vat_reg']) <> intval($_POST['lastvatreg']) OR intval($_POST['vat_section']) <> intval($_POST['lastvatsection'])){
		$last_month_print = intval(getLastMonth($_POST['vat_section'], $_POST['vat_reg']));
		if ($admin_aziend['ivam_t'] == 'M') {
			$utsdatini = mktime(0, 0, 0, $last_month_print + 1, 1, date("Y"));
			$utsdatfin = mktime(0, 0, 0, $last_month_print + 2, 0, date("Y"));
		} elseif ($last_month_print >= 1 and $last_month_print < 4) {
			$utsdatini = mktime(0, 0, 0, 4, 1, date("Y"));
			$utsdatfin = mktime(0, 0, 0, 6, 30, date("Y"));
		} elseif ($last_month_print >= 4 and $last_month_print < 7) {
			$utsdatini = mktime(0, 0, 0, 7, 1, date("Y"));
			$utsdatfin = mktime(0, 0, 0, 9, 30, date("Y"));
		} elseif ($last_month_print >= 7 and $last_month_print < 10) {
			$utsdatini = mktime(0, 0, 0, 10, 1, date("Y"));
			$utsdatfin = mktime(0, 0, 0, 12, 31, date("Y"));
		} elseif ($last_month_print >= 10 and $last_month_print <= 12) {
			$utsdatini = mktime(0, 0, 0, 1, 1, date("Y")+1);
			$utsdatfin = mktime(0, 0, 0, 3, 31, date("Y")+1);
		} elseif ($last_month_print == 0 ) {
			$utsdatini = mktime(0, 0, 0, 1, 1, date("Y"));
			$utsdatfin = mktime(0, 0, 0, 3, 31, date("Y"));
		}
		$form['date_ini_D'] = 1;
		$form['date_ini_M'] = date("m", $utsdatini);
		$form['date_ini_Y'] = date("Y", $utsdatini);
		$form['date_fin_D'] = date("d", $utsdatfin);
		$form['date_fin_M'] = date("m", $utsdatfin);
		$form['date_fin_Y'] = date("Y", $utsdatfin);
		$form['lastvatreg']=$_POST['vat_reg'];
		$form['lastvatsection']=$_POST['vat_section'];
	} else {
		$form['date_ini_D'] = intval($_POST['date_ini_D']);
		$form['date_ini_M'] = intval($_POST['date_ini_M']);
		$form['date_ini_Y'] = intval($_POST['date_ini_Y']);
		$form['date_fin_D'] = intval($_POST['date_fin_D']);
		$form['date_fin_M'] = intval($_POST['date_fin_M']);
		$form['date_fin_Y'] = intval($_POST['date_fin_Y']);
	}
  $form['vat_section'] = intval($_POST['vat_section']);
  $form['vat_reg'] = intval($_POST['vat_reg']);
  if (isset($_POST['sta_def'])) {
    $form['sta_def'] = substr($_POST['sta_def'], 0, 8);
  } else {
    $form['sta_def'] = '';
  }
  if (isset($_POST['jump'])) {
    $form['jump'] = substr($_POST['jump'], 0, 8);
  } else {
    $form['jump'] = '';
  }
  $form['sem_ord'] = intval($_POST['sem_ord']);
  if (isset($_POST['cover'])) {
    $form['cover'] = substr($_POST['cover'], 0, 8);
  } else {
    $form['cover'] = '';
  }
  if ($form['hidden_req'] == 'vat_reg' || $form['hidden_req'] == 'vat_section') {
      require("lang." . $admin_aziend['lang'] . ".php");
      $form['page_ini'] = getPage_ini($form['vat_section'], $form['vat_reg']);
  if ($form['vat_reg']==9){ // ho cambiato per vedere i versamenti propongo tutta la lista dall'anno precedente
    $dl = new DateTime('-1 year');
    $form['date_ini_D'] = 1;
    $form['date_ini_M'] = 1;
    $form['date_ini_Y'] = $dl->format('Y');
    $form['jump'] = '';
  }
    $form['hidden_req'] = '';
  } else {
    $form['page_ini'] = intval($_POST['page_ini']);
  }
  if (isset($_POST['return'])) {
    header("Location: " . $form['ritorno']);
    exit;
  }
}

//controllo i campi
if (!checkdate($form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']) ||
        !checkdate($form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y'])) {
    $msg .= '0+';
}
$utsini = mktime(0, 0, 0, $form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']);
$utsfin = mktime(0, 0, 0, $form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y']);
if ($utsini > $utsfin) {
    $msg .= '1+';
}
// fine controlli

if (isset($_POST['print']) && $msg == '') {
    $_SESSION['print_request'] = array('script_name' => 'stampa_regiva',
        'vs' => $form['vat_section'],
        'vr' => $form['vat_reg'],
        'jp' => $form['jump'],
        'pi' => $form['page_ini'],
        'sd' => $form['sta_def'],
        'so' => $form['sem_ord'],
        'cv' => $form['cover'],
        'ri' => date("dmY", $utsini),
        'rf' => date("dmY", $utsfin),
		'lm' => $form['date_fin_M']
    );
    header("Location: sent_print.php");
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup'));
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
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['ritorno'] . "\" name=\"ritorno\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['lastvatreg'] . "\" name=\"lastvatreg\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['lastvatsection'] . "\" name=\"lastvatsection\" />\n";
$gForm = new contabForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="4" class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['vat_reg'] . "</td><td  class=\"FacetDataTD\">\n";
$gForm->variousSelect('vat_reg', $script_transl['vat_reg_value'], $form['vat_reg'], 'FacetSelect', false, 'vat_reg');
echo "</td>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['vat_section'] . "</td><td class=\"FacetDataTD\">\n";
$gForm->selectNumber('vat_section', $form['vat_section'], false, 1, 9, 'FacetSelect', 'vat_section');
echo "\t</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['page_ini'] . "</td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"page_ini\" value=\"" . $form['page_ini'] . "\" maxlength=\"5\"  /></td>\n";
echo "</td>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['sta_def'] . "</td><td class=\"FacetDataTD\">\n";
$gForm->selCheckbox('sta_def', $form['sta_def'], $script_transl['sta_def_title']);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['jump'] . "</td>\n<td class=\"FacetDataTD\" colspan=\"3\">";
$gForm->selCheckbox('jump', $form['jump'], $script_transl['jump_title']);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['date_ini'] . "</td><td colspan=\"3\" class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_ini', $form['date_ini_D'], $form['date_ini_M'], $form['date_ini_Y'], 'FacetSelect', 1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['date_fin'] . "</td><td colspan=\"3\" class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_fin', $form['date_fin_D'], $form['date_fin_M'], $form['date_fin_Y'], 'FacetSelect', 1);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['sem_ord'] . "</td><td class=\"FacetDataTD\">\n";
$gForm->variousSelect('sem_ord', $script_transl['sem_ord_value'], $form['sem_ord'], 'FacetSelect', false);
echo "</td>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['cover'] . "</td><td class=\"FacetDataTD\">\n";
$gForm->selCheckbox('cover', $form['cover']);
echo "</td>\n";
echo "</tr>\n";
echo "\t<tr>\n";
echo '<td colspan="4" class="bg-info text-center"> <input type="submit" class="btn btn-info" accesskey="i" name="preview" value="';
echo $script_transl['view'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";
if (isset($_POST['preview']) and $msg == '') {
    $date_ini = sprintf("%04d%02d%02d", $form['date_ini_Y'], $form['date_ini_M'], $form['date_ini_D']);
    $date_fin = sprintf("%04d%02d%02d", $form['date_fin_Y'], $form['date_fin_M'], $form['date_fin_D']);
    $m = getMovements($form['vat_section'], $form['vat_reg'], $date_ini, $date_fin);
    echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
    if (sizeof($m) > 0) {
        $err = 0;
        echo "<tr>";
        $linkHeaders = new linkHeaders($script_transl['header']);
        $linkHeaders->output();
        echo "</tr>";
        $totimponi = 0.00;
        $totimpost = 0.00;
        $totindetr = 0.00;
        $totimponi_liq = 0.00;
        $totimpost_liq = 0.00;
        $totindetr_liq = 0.00;
        $ctrlmopre = 0;
		$castle_imponi=array();
		$castle_descri[0]='';
		$castle_percen[0]='';
		foreach ($m AS $key => $mv) {
			$class_m='';
            if ($mv['operat'] == 1||$form['vat_reg']==9) {
                $imponi = $mv['imponi'];
                $impost = $mv['impost'];
            } elseif ($mv['operat'] == 2) {
                $imponi = -$mv['imponi'];
                $impost = -$mv['impost'];
            } else {
                $imponi = 0;
                $impost = 0;
            }
            if ($mv['regiva'] == 4) {
                $mv['ragsoc'] = $mv['descri'];
                $mv['descri'] = '';
            }
			if($mv['dr']<$date_ini){ // fattura pregressa, precedente al periodo selezionato ma che concorre alla liquidazione
				$class_m='danger';
			}elseif($mv['dr']>$date_fin){// fattura successiva al periodo selezionato ma che concorre alla liquidazione es. acquisto egistrato nei 15gg successivi
				$class_m='danger';
			}else{
				$totimponi += $imponi;
				if ($mv['tipiva'] != 'D' && $mv['tipiva'] != 'T') { // se indetraibili o split payment
					$totimpost += $impost;
				}
				if (!isset($castle_imponi[$mv['codiva']])) {
					$castle_imponi[$mv['codiva']] = 0;
					$castle_impost[$mv['codiva']] = 0;
					$castle_descri[$mv['codiva']] = $mv['desvat'];
					$castle_percen[$mv['codiva']] = $mv['periva'];
				}
				$castle_imponi[$mv['codiva']] += $imponi;
				$castle_impost[$mv['codiva']] += $impost;
			}
			if (!isset($castle_impost_liq[$mv['codiva']])){
                $castle_imponi_liq[$mv['codiva']] = 0;
				$castle_impost_liq[$mv['codiva']] = 0;
			}
			$liq_val='';
			if ($mv['dl']<$date_ini){
				$liq_val='<br>IMPOSTA GIÀ LIQUIDATA';
				$class_m='danger';
			} elseif ($mv['dl']>$date_fin){
				$liq_val='<br>IMPOSTA DA LIQUIDARE';
				$class_m='warning';
			} else {
				$liq_val='<br>'.gaz_format_number($impost);
				$totimponi_liq += $imponi;
				$totimpost_liq += $impost;
                $castle_imponi_liq[$mv['codiva']] += $imponi;
                $castle_impost_liq[$mv['codiva']] += $impost;
			}
            $red_p = '';
            if (isset($mv['err_p'])) {
                $red_p = 'red';
                $err++;
                echo "<tr>";
                echo "<td colspan=\"8\" class=\"FacetDataTDred\">" . $script_transl['errors']['P'] . ":&nbsp;</td>";
                echo "</tr>";
            }
            $red_d = '';
            if (isset($mv['err_n'])) {
                $red_d = 'red';
                $err++;
                echo "<tr>";
                echo "<td colspan=\"8\" class=\"FacetDataTDred\">" . $script_transl['errors']['N'] . ":&nbsp;</td>";
                echo "</tr>";
            }
            $red_t = '';
            if (isset($mv['err_t'])) {
                $red_t = 'red';
                $err++;
                echo "<tr>";
                echo "<td colspan=\"8\" class=\"FacetDataTDred\">" . $script_transl['errors']['T'] . ":&nbsp;</td>";
                echo "</tr>";
            }
            echo '<tr class="'.$class_m.'">';
            echo "<td align=\"right\" class=\"FacetDataTD$red_p\">" . $mv['protoc'] . " &nbsp;</td>";
            echo "<td align=\"center\"><a href=\"admin_movcon.php?id_tes=" . $mv['id_tes'] . "&Update\" title=\"Modifica il movimento contabile\">id " . $mv['id_tes'] . "</a><br />" . gaz_format_date($mv['datreg']). "</td>";
            echo "<td>" . $mv['descri'] . " n." . $mv['numdoc'] . $script_transl['of'] . gaz_format_date($mv['datdoc']) . " &nbsp;</td>";
            echo "<td>" . substr($mv['ragsoc'], 0, 30) . " &nbsp;</td>";
            echo "<td align=\"right\">" . gaz_format_number($imponi) . " &nbsp;</td>";
            echo "<td align=\"center\">" . $mv['periva'] . " &nbsp;</td>";
            echo "<td align=\"right\">" . gaz_format_number($impost) . " &nbsp;</td>";
            echo "<td align=\"center\">" . substr(gaz_format_date($mv['datliq']),3) . $liq_val." &nbsp;</td>";
            echo "</tr>";
        }
        echo '<tr><td colspan="8"><hr/></td></tr>';
        $totale = number_format(($totimponi + $totimpost), 2, '.', '');
        foreach ($castle_imponi as $key => $value) {
            echo "<tr><td colspan=3></td><td class=\"FacetDataTD\">" . $script_transl['tot'] .
			$castle_descri[$key] . ' '.$script_transl['reg'] .
			"</td><td align=\"right\">" . gaz_format_number($value) . " &nbsp;</td><td align=\"right\">" . $castle_percen[$key] .
			"% &nbsp;</td><td align=\"right\">" . gaz_format_number($castle_impost[$key]) . " &nbsp;</td><td></td></tr>";
        }

        foreach ($castle_imponi_liq as $key => $value) {
            echo "<tr><td colspan=3></td><td class=\"info\">" . $script_transl['tot'] .
			$castle_descri[$key]. ' '.$script_transl['liq']  .
			"</td><td align=\"right\">" . gaz_format_number($value) . " &nbsp;</td><td align=\"right\">" . $castle_percen[$key] .
			"% &nbsp;</td><td align=\"right\"></td><td align=\"center\" class=\"info\">" . gaz_format_number($castle_impost_liq[$key]) . " &nbsp; &nbsp;</td></tr>";
        }
        echo "<tr><td colspan=3></td><td colspan=4><HR></td></tr>";
        echo "<tr><td colspan=2></td><td>" . $script_transl['t_gen'] . "</td><td align=\"right\">" . gaz_format_number($totale) . " &nbsp;</td><td align=\"right\">" . gaz_format_number($totimponi, 2, '.', '') . " &nbsp;</td><td></td><td align=\"right\">" . gaz_format_number($totimpost, 2, '.', '') . " &nbsp;</td></tr>";
        echo "<tr><td colspan=2></td><td class=\"info\">" .$script_transl['t_liq'] . "</td><td align=\"right\">" . gaz_format_number($totimponi_liq+$totimpost_liq) . " &nbsp;</td><td align=\"right\">" . gaz_format_number($totimponi_liq, 2, '.', '') . " &nbsp;</td><td colspan=\"2\"></td><td align=\"center\" class=\"info\">" . gaz_format_number($totimpost_liq, 2, '.', '') . " &nbsp;</td></tr>";
        if ($err == 0) {
            echo "\t<tr>\n";
            echo '<td class="FacetFooterTD" colspan="8" align="center"><input type="submit" class="btn btn-warning" name="print" value="';
            echo $script_transl['print'];
            echo '">';
            echo "\t </td>\n";
            echo "\t </tr>\n";
        } else {
            echo "<tr>";
            echo "<td colspan=\"8\" align=\"right\" class=\"FacetDataTDred\">" . $script_transl['errors']['err'] . "</td>";
            echo "</tr>\n";
        }
    }
    echo "</table>\n";
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
