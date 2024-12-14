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
$admin_aziend = checkAdmin();
if (!ini_get('safe_mode')) { //se me lo posso permettere...
    ini_set('memory_limit', '128M');
    gaz_set_time_limit(0);
}
$msg = '';
$clienti = $admin_aziend['mascli'];
if ( isset($_GET['tipodocumento'])) {
    $tipodocumento = $_GET['tipodocumento'];
} else {
    $tipodocumento = "DDT";
}

function getDateLimits($sez=1, $tipodocumento='DDT') {
    $acc = [];
    $now = new DateTime;
    $acc['date_exe'] = $now->format("Y-m-d");
    $acc['date_fin'] = $acc['date_exe'];
    $acc['date_ini'] = $acc['date_exe'];
    global $gTables;
    // ricavo i limiti di fatturabilitÃ  e le date dei vari tipi di DdT
    $doctype = $tipodocumento=='CMR'?['CMR']:['DDT','DDV','DDY','DDS','DDM','DDO'];
    foreach ($doctype as $k => $v) {
        switch ($v) {
            default :
            case 'DDT':
            case 'DDM':
            case 'DDO':
            case 'CMR':
                $rs_first = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "tipdoc = '$v' AND seziva = $sez", "numdoc ASC", 0, 1);
                $rs_last = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "tipdoc = '$v' AND seziva = $sez", "numdoc DESC", 0, 1);
                // in questo caso modifico la data di emissione e di fine periodo con l'ultimo del mese del primo ddt fatturabile
                $ddtfirst = gaz_dbi_fetch_array($rs_first);
                if ($ddtfirst) {
                    $nd = new DateTime($ddtfirst['datemi']);
                    $di = new DateTime($acc['date_ini']);
                    if ($di>$nd){
                      $acc['date_ini'] = $ddtfirst['datemi'];
                    }
                    $nd->modify('last day of this month');
                    $acc['date_fin'] = $nd->format('Y-m-d');
                    $acc['date_exe'] = $acc['date_fin'];
					if (strtotime($acc['date_exe'])>strtotime($now->format("Y-m-d"))){
						$acc['date_exe']=$now->format("Y-m-d");// NON posso emettere una fattura in un giorno successivo alla data odierna
						if (strtotime($acc['date_fin'])>strtotime($acc['date_exe'])){
							$acc['date_fin']=$acc['date_exe'];
						}						
					}
                }
                break;
            case 'DDV':
                // per quelli in c/visione non apporto modifiche ai limiti di date, mi baso sulla
                // data di emissione e quindi sull'obbligo di fatturazione  dopo 1 anno
                $nd = new DateTime($acc['date_exe']);
                $nd->modify('-1 year');
                break;
            case 'DDY':
            case 'DDS':
                // se sono notule di servizio o in conto triangolazione non apporto modifiche e al momento non li fatturo salvo richiesta contraria
                break;
        }
    }
    $acc['date_exe_Y'] = date("Y", strtotime($acc['date_exe']));
    $acc['date_exe_M'] = date("m", strtotime($acc['date_exe']));
    $acc['date_exe_D'] = date("d", strtotime($acc['date_exe']));
    $acc['date_ini_Y'] = date("Y", strtotime($acc['date_ini']));
    $acc['date_ini_M'] = date("m", strtotime($acc['date_ini']));
    $acc['date_ini_D'] = date("d", strtotime($acc['date_ini']));
    $acc['date_fin_Y'] = date("Y", strtotime($acc['date_fin']));
    $acc['date_fin_M'] = date("m", strtotime($acc['date_fin']));
    $acc['date_fin_D'] = date("d", strtotime($acc['date_fin']));

    return $acc;
}

function getBillsStatus($data_fin, $sez = 1) {
    $acc['n'][$v]['n_invoiceable'] = gaz_dbi_record_count($gTables['tesdoc'], "tipdoc = '$v' AND seziva = $sez AND datemi <= '" . $nd->format('Y-m-d') . "'");
    $acc['n'][$v]['n_remainder'] = gaz_dbi_record_count($gTables['tesdoc'], "tipdoc = '$v' AND seziva = $sez AND datemi > '" . $nd->format('Y-m-d') . "'");
}

function getInvoiceableBills($date, $sez = 1, $cliente = 0) {
    $acc = array();
    global $gTables;
    $de = new DateTime($date['exe']);
    $di = new DateTime($date['ini']);
    $df = new DateTime($date['fin']);
    $Y = $de->format('Y');
    // ricavo il progressivo annuo del numero protocollo
    $rs_last_invoice_protoc = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datemi) = $Y AND tipdoc LIKE 'F%' AND seziva = $sez", "protoc DESC", 0, 1);
    $last_invoice_protoc = gaz_dbi_fetch_array($rs_last_invoice_protoc);
    if ($last_invoice_protoc) {
        $acc['last_protoc'] = $last_invoice_protoc['protoc'];
    } else {
        $acc['last_protoc'] = 0;
    }
    // ricavo il progressivo annuo del numero fattura
    // definisco la query in base alla scelta di numerazione continua Fatture-Note Credito/Debito
    $num_nc_nd = gaz_dbi_get_row($gTables['company_config'], 'var', 'num_note_separate')['val'];
    $tdsep=($num_nc_nd==1)?'FA_':'F__';
    $rs_last_invoice_numfat = gaz_dbi_dyn_query("numdoc, numfat*1 AS fattura", $gTables['tesdoc'], "YEAR(datemi) = $Y AND tipdoc LIKE '$tdsep' AND seziva = $sez", "fattura DESC", 0, 1);
    $last_invoice_numfat = gaz_dbi_fetch_array($rs_last_invoice_numfat);
    if ($last_invoice_numfat) {
        $acc['last_numfat'] = $last_invoice_numfat['fattura'];
    } else {
        $acc['last_numfat'] = 0;
    }
    //preparo la query al database
    $clientesel = '';
    if ($cliente > 0) {
        $clientesel = ' AND clfoco = ' . $cliente;
    }
    $orderby = "ragso1 ASC, ragbol ASC, pagame ASC, numdoc ASC";
    // mi serve la data di un anno prima per fare la ricerca dei DDV
    $where = " seziva = '$sez'" . $clientesel . " AND ("
            . "((tipdoc = 'DDT' OR tipdoc = 'DDM' OR tipdoc = 'DDO') AND datemi BETWEEN '" . $date['ini'] . "' AND '" . $date['fin'] . "')"
            . " OR "
            . "(tipdoc = 'DDV' AND datemi <= '" . $date['fin'] . "' AND id_doc_ritorno <= 0 )"
            . " OR "
            . "(tipdoc = 'DDY' AND datemi BETWEEN '" . $date['ini'] . "' AND '" . $date['fin'] . "')"
            . " OR "
            . "(tipdoc = 'DDS' AND datemi <= '" . $date['fin'] . "')"
            . " OR "
            . "(tipdoc = 'CMR' AND datemi BETWEEN '" . $date['ini'] . "' AND '" . $date['fin'] . "')"
            . ")";
    //recupero i dati dal DB (testate+cliente+pagamento+righi)
    $field = 'tes.id_tes,tes.clfoco,tes.pagame,tes.banapp,tes.datemi,tes.ragbol,tes.tipdoc,
              CONCAT(ana.ragso1,\' \',ana.ragso2,\' \',ana.citspe,\' \',ana.prospe) AS ragsoc,
              cli.codice,cli.ragdoc,pag.tippag,pag.incaut ';
    $from = $gTables['tesdoc'] . ' AS tes ' .
            'LEFT JOIN ' . $gTables['clfoco'] . ' AS cli ON tes.clfoco=cli.codice ' .
            'LEFT JOIN ' . $gTables['anagra'] . ' AS ana ON cli.id_anagra=ana.id ' .
            'LEFT JOIN ' . $gTables['pagame'] . ' AS pag ON pag.codice=tes.pagame ';
    $result = gaz_dbi_dyn_query($field, $from, $where, $orderby);
    $ctrlnum = gaz_dbi_num_rows($result);
    if ($ctrlnum) {
        //creo l'array associativo testate-righi
        $ctrlc = 0;
        $ctrlp = 0;
        $ctrld = 0;
        $ctrlr = 0;    // rappresenta il raggruppamento bolle
        $i = 0;
        $de->modify('-1 year');
        while ($row = gaz_dbi_fetch_array($result)) {
            $dm = new DateTime($row['datemi']);
            if ($row['clfoco'] != $ctrlc || $row['pagame'] != $ctrlp || $row['ragbol'] != $ctrlr || ( $row['id_tes'] != $ctrld && $row['ragdoc'] == 'N')) {
                //se Ã¨ un'altro cliente o il cliente ha un pagamento diverso dal precedente o  non c'Ã¨ il raggruppamento bolle o Ã¨ in conto visione
                $i++;
            }
            if ($row['tipdoc'] == 'DDV') { // CONTO VISIONE
                if ($dm <= $de) { // emesso oltre 1 anno prima
                    $acc['data'][$i][$row['id_tes']] = 'yes';
                } else { // non sono ancora trascorsi 365 gg
                    $acc['data'][$i][$row['id_tes']] = 'maybe';
                }
            } elseif ($row['tipdoc'] == 'DDS') { // NOTULA DI SERVIZIO
                $acc['data'][$i][$row['id_tes']] = 'maybe';
            } elseif ($row['tipdoc'] == 'DDY') { // TRIANGOLAZIONE
                $acc['excluded'][$i][$row['id_tes']] = 'no';
            } else {                            // DDT & OTHER
                $acc['data'][$i][$row['id_tes']] = 'yes';
            }
            if ($row['clfoco'] == $ctrlc && $row['pagame'] != $ctrlp) {
                $acc['error'][$i][$row['id_tes']][] = 'cust_pay';
            }
            if ($row['incaut'] > 1) {
                $acc['error'][$i][$row['id_tes']][] = 'aut_pay';
            }
            if (($row['tippag'] == 'B' || $row['tippag'] == 'T') && $row['banapp'] == 0) {
                $acc['error'][$i][$row['id_tes']][] = 'no_bank';
            }
            $ctrld = $row['id_tes'];
            $ctrlc = $row['clfoco'];
            $ctrlp = $row['pagame'];
            $ctrlr = $row['ragbol'];

        }
    }
    return $acc;

}

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    if (isset($_GET['seziva'])) {
        $form['seziva'] = intval($_GET['seziva']);
    } else {
        $form['seziva'] = 1;
    }
    $form['clfoco'] = 0;
    $form['search']['clfoco'] = '';
    $form['changeStatus'] = array();
    $ini_data = getDateLimits($form['seziva'],$tipodocumento);
    $form += $ini_data;
} else { // accessi successivi
    $form['hidden_req'] = filter_input(INPUT_POST, 'hidden_req');
    $form['ritorno'] = filter_input(INPUT_POST, 'ritorno');
    $form['seziva'] = intval($_POST['seziva']);
    $form['clfoco'] = substr($_POST['clfoco'], 0, 15);
    foreach ($_POST['search'] as $k => $v) {
        $form['search'][$k] = $v;
    }
    if (isset($_POST['changeStatus'])) {
        foreach ($_POST['changeStatus'] as $k => $v) {
            $form['changeStatus'][$k] = $v;
        }
    } else {
        $form['changeStatus'] = array();
    }
    $form['date_ini_D'] = intval($_POST['date_ini_D']);
    $form['date_ini_M'] = intval($_POST['date_ini_M']);
    $form['date_ini_Y'] = intval($_POST['date_ini_Y']);
    $form['date_fin_D'] = intval($_POST['date_fin_D']);
    $form['date_fin_M'] = intval($_POST['date_fin_M']);
    $form['date_fin_Y'] = intval($_POST['date_fin_Y']);
    $form['date_exe_Y'] = intval($_POST['date_exe_Y']);
    $form['date_exe_M'] = intval($_POST['date_exe_M']);
    $form['date_exe_D'] = intval($_POST['date_exe_D']);
    if ($form['hidden_req'] == 'clfoco') {
        $anagrafica = new Anagrafica();
        if (preg_match("/^id_([0-9]+)$/", $form['clfoco'], $match)) {
            $partner = $anagrafica->getPartnerData($match[1], 1);
        } else {
            $partner = $anagrafica->getPartner($form['clfoco']);
        }
        $form['hidden_req'] = '';
    }

    // cambio lo stato ddt
    if (isset($_POST['yes_change'])) {
        $form['changeStatus'][] = key($_POST['yes_change']);
    }
    // ripristino lo stato del ddt
    if (isset($_POST['no_change'])) {
        $key = array_search(key($_POST['no_change']), $form['changeStatus']);
        unset($form['changeStatus'][$key]);
    }
    if (!checkdate($form['date_exe_M'], $form['date_exe_D'], $form['date_exe_Y']) ||
            !checkdate($form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']) ||
            !checkdate($form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y'])) {
        $msg .= '0+';
    }
    $utsexe = mktime(0, 0, 0, $form['date_exe_M'], $form['date_exe_D'], $form['date_exe_Y']);
    $utsini = mktime(0, 0, 0, $form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']);
    $utsfin = mktime(0, 0, 0, $form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y']);
    if ($utsexe < $utsfin) {
        $msg .="1+";
    }
    if ($utsini > $utsfin) {
        $msg .="2+";
    }
    // controllo se la data di emissione non precede quella dell'ultima fattura emessa
    $rs_ultima_fattura = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datemi) = " . $form['date_exe_Y'] . " AND tipdoc LIKE 'F__' AND seziva = " . $form['seziva'], "protoc DESC, datfat DESC, datemi DESC", 0, 1);
    $ultima_fattura = gaz_dbi_fetch_array($rs_ultima_fattura);
	if ($ultima_fattura){
		$utsUltimoProtocollo = mktime(0, 0, 0, substr($ultima_fattura['datfat'], 5, 2), substr($ultima_fattura['datfat'], 8, 2), intval(substr($ultima_fattura['datfat'], 0, 4)));
		if ($utsexe && ( $utsUltimoProtocollo > $utsexe)) {
			$msg .= "4+";
		}
	}
}

if (isset($_POST['genera']) && $msg == "") {
    $date_exe = new DateTime($form['date_exe_Y'] . '-' . $form['date_exe_M'] . '-' . $form['date_exe_D']);
    $date_ini = new DateTime($form['date_ini_Y'] . '-' . $form['date_ini_M'] . '-' . $form['date_ini_D']);
    $date_fin = new DateTime($form['date_fin_Y'] . '-' . $form['date_fin_M'] . '-' . $form['date_fin_D']);
    $date = array('exe' => $date_exe->format('Y-m-d'), 'ini' => $date_ini->format('Y-m-d'), 'fin' => $date_fin->format('Y-m-d'));
    $invoices = getInvoiceableBills($date, $form['seziva'], $form['clfoco'], $form['changeStatus']);
    //var_dump($invoices);
    if (isset($invoices['excluded'])) {
        foreach ($invoices['excluded'] as $i => $testate) {
	    foreach ($testate as $id_tes => $v) {
                if (in_array($id_tes, $form['changeStatus'])) {
                    // lo aggiungo ai fatturabili
		    $invoices['data'][$i][$id_tes] = 'maybe';
                    // e lo tolgo dagli esclusi
                    unset($invoices['excluded'][$i][$id_tes]);
		}
            }
        }
    }
    if (isset($invoices['data'])) {
        $protoc = $invoices['last_protoc'];
        $numfat = $invoices['last_numfat'];
        foreach ($invoices['data'] as $vt) {
            $ctrl_first = true;
            // attraverso l'array delle fatture proposte
            foreach ($vt as $kr => $vr) {
                $tes = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", $kr);
                $checktipdoc=false;
                if ($tipodocumento == 'DDT' && ($tes['tipdoc'] == 'DDM' || $tes['tipdoc'] == 'DDO' || $tes['tipdoc']=='DDT')){
                  $checktipdoc=true;
                }
                $pag = gaz_dbi_get_row($gTables['pagame'], "codice", $tes['pagame']);
                if (($vr == 'yes' && $checktipdoc && !in_array($kr, $form['changeStatus'])) || (in_array($kr, $form['changeStatus']) && $checktipdoc==false)) { //||
                    // se Ã¨ un s da fatturare non escluso o  Ã¨ un DDV-Y normalmente escluso ma richiesto alla fatturazione
                    if ($ctrl_first) {
                        $protoc++;
                        $numfat++;
                        $ctrl_first = false;
                    }
                    //vado a modificare le testate cambiando il tipdoc e introducendo protocollo, numero e data fattura
                    gaz_dbi_query("UPDATE " . $gTables['tesdoc'] . " SET tipdoc = 'FAD', protoc = " . $protoc .
                            ", numfat = '" . $numfat . "', datfat = '" . $date['exe'] . "' WHERE id_tes = " . $kr . ";");
                }
            }
        }
        //Mando in stampa le fatture generate
        if (empty($sez)) $sez = 1;
        $locazione = "Location: select_docforprint.php?tipdoc=2&seziva=" . $sez . "&proini=" . $invoices['last_protoc'] .
                "&profin=" . $protoc .
                "&datini=" . date("Ymd", $utsexe) .
                "&datfin=" . date("Ymd", $utsexe);
        header($locazione);
        exit;
    }
}

if (isset($_POST['return'])) {
    header("Location:report_docven.php");
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup', 'custom/autocomplete'));
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
$gForm = new venditForm();
$select_customer = new selectPartner('clfoco');
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'];
echo "<select name=\"seziva\" class=\"FacetFormHeaderFont\" onchange=\"this.form.submit()\">\n";
for ($counter = 1; $counter <= 9; $counter++) {
    $selected = "";
    if ($form['seziva'] == $counter) {
        $selected = " selected ";
    }
    echo "<option value=\"" . $counter . "\"" . $selected . ">" . $counter . "</option>\n";
}
echo "</select>\n";
echo "</div>\n";
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="2" class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
}
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl['cliente'] . " </td><td class=\"FacetDataTD\">\n";
$select_customer->selectDocPartner('clfoco', $form['clfoco'], $form['search']['clfoco'], 'clfoco', $script_transl['mesg'], $admin_aziend['mascli'],-1,1,true);
echo "</td></tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['date_exe'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_exe', $form['date_exe_D'], $form['date_exe_M'], $form['date_exe_Y'], 'FacetSelect', 1);
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['date_ini'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_ini', $form['date_ini_D'], $form['date_ini_M'], $form['date_ini_Y'], 'FacetSelect', 1);
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['date_fin'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_fin', $form['date_fin_D'], $form['date_fin_M'], $form['date_fin_Y'], 'FacetSelect', 1);
echo "</tr>"
 . "</table>\n";

$date_exe = new DateTime($form['date_exe_Y'] . '-' . $form['date_exe_M'] . '-' . $form['date_exe_D']);
$date_ini = new DateTime($form['date_ini_Y'] . '-' . $form['date_ini_M'] . '-' . $form['date_ini_D']);
$date_fin = new DateTime($form['date_fin_Y'] . '-' . $form['date_fin_M'] . '-' . $form['date_fin_D']);
$date = array('exe' => $date_exe->format('Y-m-d'), 'ini' => $date_ini->format('Y-m-d'), 'fin' => $date_fin->format('Y-m-d'));
$invoices = getInvoiceableBills($date, $form['seziva'], $form['clfoco'], $form['changeStatus']);
echo '<div align="center"><b>' . $script_transl['preview_inv'] . '</b></div>';
echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
// qui faccio il push all'array dei fatturabili se richiesti esplicitamente
if (isset($invoices['excluded'])) {
    foreach ($invoices['excluded'] as $i => $testate) {
	foreach ($testate as $id_tes => $v) {
            if (in_array($id_tes, $form['changeStatus'])) {
		// lo aggiungo ai fatturabili
		$invoices['data'][$i][$id_tes] = 'maybe';
		// e lo tolgo dagli esclusi
		unset($invoices['excluded'][$i][$id_tes]);
	    }
        }
    }
}
if (isset($invoices['data'])) {
    $protoc = $invoices['last_protoc'];
    $numfat = $invoices['last_numfat'];
    $tot = 0.00;
    foreach ($invoices['data'] as $kt=>$vt) {
        $ctrl_first = true;
        // attraverso l'array delle fatture proposte
        foreach ($vt as $kr => $vr) {
            if ($vr == 'maybe') {
                $c = 'FacetDataTDred';
            } else {
                $c = 'FacetDataTD';
            }

            $tes = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", $kr);
            $checktipdoc=false;
            if ($tipodocumento == 'DDT' && ($tes['tipdoc'] == 'DDM' || $tes['tipdoc'] == 'DDO' || $tes['tipdoc']=='DDT')){
              $checktipdoc=true;
            }
            $anagrafica = new Anagrafica();
            $cliente = $anagrafica->getPartner($tes['clfoco']);
            $pag = gaz_dbi_get_row($gTables['pagame'], "codice", $tes['pagame']);
            if (($vr == 'yes' &&  $checktipdoc && !in_array($kr, $form['changeStatus'])) ||
                (in_array($kr, $form['changeStatus']) && $checktipdoc==false)) {
                // se c'è un DDT da fatturare non escluso o  un DDV-Y-S normalmente escluso ma richiesto alla fatturazione
                if ($ctrl_first) {
                    $protoc++;
                    $numfat++;
                    $tot = 0.00;
					if (isset($invoices['error'][$kt]) && $invoices['error'][$kt][$kr][0]=='cust_pay'){
						$btn_class ="btn-danger";
						$cliente['ragso2'] .= '<span class="bg-danger">'.$script_transl['errors']['cust_pay'].'</span>';
					} else {
						$btn_class ="btn-default";
					}
                    echo "<tr>";
                    if ( $tes['tipdoc'] == 'CMR') $transl_movimento = $script_transl['add_invoice_cmr'];
                    else $transl_movimento = $script_transl['add_invoice'];
                    echo '<td  class="FacetDataTDevidenziaOK" colspan="8"><b>' .$cliente['ragso1'] . ' ' . $cliente['ragso2'] .'</b> '. $transl_movimento . $numfat . '/' . $tes['seziva'] . ' pr.' . $protoc . " &nbsp;</td>";
                    echo "</tr>\n";
                    $ctrl_first = false;
                }
                echo "<tr>";
                echo "<td colspan=\"8\"> ";
                $descr_agg = " ";
                if (!empty($tes['ddt_type'])) {
                    $descr_agg = ' ' . $script_transl['ddt_type'][$tes['ddt_type']];
                }
                echo $tes['tipdoc'] . $descr_agg
                . ' &nbsp;<a class="btn btn-xs '.$btn_class.'"  href="admin_docven.php?Update&id_tes=' . $kr
                . "\" ><i class=\"glyphicon glyphicon-edit\"></i>" . $tes['numdoc'] . '/' . $tes['seziva'] . " </a>"
                . " del " . gaz_format_date($tes['datemi']) . " &nbsp;  &hArr; " . $pag['descri'];
                if ($vr == 'maybe') {
                    echo " &nbsp;<input class=\"btn btn-xs btn-warning\" type=\"submit\" name=\"no_change[$kr]\" value=\"Escludi!\" />";
                } else {
                    echo " &nbsp;<input class=\"btn btn-xs btn-info\" type=\"submit\" name=\"yes_change[$kr]\" value=\"Escludi!\" />";
                }
                echo "</td>";
                echo "</tr>\n";
                // attraverso l'array delle testate proposte
                // recupero i righi
                $rs_row = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = " . $kr, "id_rig asc");
                while ($row = gaz_dbi_fetch_array($rs_row)) {
                    $row_amount = CalcolaImportoRigo($row['quanti'], $row['prelis'], $row['sconto']);
                    if ($row['tiprig'] == 1) {
                        $row_amount = CalcolaImportoRigo(1, $row['prelis'], 0);
                    }
                    $tot += $row_amount;

                    echo "<tr>";
                    if ( $row['tiprig']>=11 && $row['tiprig']<=13 ) {
                        echo "<td class=\"$c\"> FAE </td>";
                    } else {
                        echo "<td class=\"$c\">" . $row['codart'] . " </td>";
                    }
                    echo "<td class=\"$c\">" . $row['descri'] . " </td>";
                    echo "<td class=\"$c\"> " . $row['unimis'] . " </td>";
                    if ( $row['tiprig']>=11 && $row['tiprig']<=13 || $row['tiprig']==2 ) {
                        echo "<td class=\"$c\" align=\"right\"></td>";
                        echo "<td class=\"$c\" align=\"right\"></td>";
                        echo "<td class=\"$c\" align=\"right\"></td>";
                        echo "<td class=\"$c\" align=\"right\"></td>";
                        echo "<td class=\"$c\" align=\"right\"></td>";
                    } else {
						if ($row['tiprig']<=0 && $row['prelis'] <= 0.00000 ) { $c = 'danger';	} // allerto di un possibile errore di importo a zero
                        echo "<td class=\"$c\" align=\"right\"> ";
						if ($row['tiprig']==0 || $row['tiprig']==210 ) {echo gaz_format_quantity($row['quanti'], true);	}
                        echo "</td>\n<td class=\"$c\" align=\"right\"> " . gaz_format_quantity($row['prelis'], true, $admin_aziend['decimal_price']) . " </td>";
                        echo "<td class=\"$c\" align=\"right\"> ";
						if ($row['sconto']>=0.00001) { echo floatval($row['sconto']);}
                        echo "</td>\n<td class=\"$c\" align=\"right\"> " . gaz_format_number($row['pervat']) . " </td>";
                        echo "<td class=\"$c\" align=\"right\"> " . gaz_format_number($row_amount) . " </td>";
                    }
                    echo "</tr>\n";
                }
                if ($tes['traspo'] > 0) {
                    echo "<tr>";
                    echo "<td> &nbsp;</td>";
                    echo "<td class=\"$c\">" . $script_transl['traspo'] . " </td>";
                    echo "<td colspan=\"5\">  &nbsp;</td>";
                    echo "<td class=\"$c\" align=\"right\"> " . gaz_format_number($tes['traspo']) . " </td>";
                    echo "</tr>\n";
                    $tot += $tes['traspo'];
                }
            } elseif ($vr == 'maybe') {
                // Ã¨ un ddt
                $tes['speban'] = 0;
                $tot = 0.00;
                echo "<tr class=\"alert alert-danger\">";
                echo "<td colspan=\"8\">" . $tes['tipdoc'] . ' ' . $script_transl['ddt_type'][$tes['ddt_type']] .
                " <a href=\"admin_docven.php?Update&id_tes=" . $kr . "\" > n." . $tes['numdoc'] . '/' . $tes['seziva'] . " </a> del " . gaz_format_date($tes['datemi']).' '.$cliente['descri'];
                echo " &nbsp;<input class=\"btn btn-xs btn-warning\" type=\"submit\" name=\"yes_change[$kr]\" value=\"FATTURA!\" /></td>";
                echo "</tr>\n";
            }
        }
        if ($tes['speban'] >= 0.01) {
            echo "<tr>";
            echo "<td colspan=\"6\">  &nbsp;</td>";
            echo "<td class=\"$c\">" . $script_transl['incasso'] . " </td>";
            echo "<td class=\"$c\" align=\"right\"> " . gaz_format_number($tes['speban'] * $pag['numrat']) . "</td>";
            echo "</tr>\n";
            $tot += $tes['speban']*$pag['numrat'];
        }
        if ($tot >= 0.01) {
            echo "<tr>";
            echo "<td colspan=\"6\">  &nbsp;</td>";
            echo "<td><b>TOTALE </b></td>";
            echo "<td align=\"right\"> <b>" . gaz_format_number($tot) . " </b>+IVA</td>";
            echo "</tr>\n";
        }
    }
    echo "<tr class=\"text-center\"><td align=\"center\" colspan=\"8\"><input class=\"btn btn-warning\" type=\"submit\" name=\"genera\" value=\"CONFERMA LA GENERAZIONE DELLE FATTURE COME DA ANTEPRIMA\"></td></tr>";
} else {
    echo "<tr><td class=\"FacetDataTDred\" colspan=\"7\" align=\"center\">Non ci sono DdT  da fatturare</td></tr>";
}
if (is_array($invoices) && isset($invoices['excluded']) && count($invoices['excluded'])) {
    echo "<tr><td class=\"FacetDataTDred\" colspan=\"7\">I seguenti ddt non verranno mai fatturati a meno di richiesta esplicita</td></tr>";
    foreach ($invoices['excluded'] as $i => $testate) {
	foreach ($testate as $id_tes => $v) {
            $tes = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", $id_tes);
            $anagrafica = new Anagrafica();
            $cliente = $anagrafica->getPartner($tes['clfoco']);
            echo "<tr>";
            echo "<td> " . $tes['clfoco'] . " &nbsp;</td>";
            echo "<td> " . $cliente['ragso1'] . ' ' . $cliente['ragso2'] . " &nbsp;</td>";
            echo "<td colspan=\"2\"> N." . $tes['numdoc'] . "/" . $tes['seziva'] . " del " . gaz_format_date($tes['datemi']) . " </td>";
            echo "<td colspan=\"2\"><input class=\"btn btn-xs btn-warning\" type=\"submit\" name=\"yes_change[$id_tes]\" value=\"Forza la fatturazione!\" /></td>";
            echo "</tr>\n";
	}
    }
}

if (count($form['changeStatus']) > 0) {
    echo "<tr><td class=\"FacetDataTDred\" colspan=\"7\">Ai Ddt sottosegnati è stato cambiato manualmente il loro stato rispetto alla proposta automatica:  </td></tr>";
    foreach ($form['changeStatus'] as $k => $id_tes) {
        $tes = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", $id_tes);
        $anagrafica = new Anagrafica();
        $cliente = $anagrafica->getPartner($tes['clfoco']);
        echo "\n<input type=\"hidden\" name=\"changeStatus[$k]\" value=\"" . $id_tes . "\" />\n";
        echo "<tr>";
        echo "<td colspan=\"4\">" . $tes['tipdoc'] . ' ' . $script_transl['ddt_type'][$tes['ddt_type']] .
        " &nbsp; <a href=\"admin_docven.php?Update&id_tes=" . $id_tes . "\">n." . $tes['numdoc'].'/'.$tes['seziva'] . "</a> ". $cliente['descri']." </td>"
        . "<td colspan=\"2\"><input  class=\"btn btn-xs btn-success\" type=\"submit\" name=\"no_change[" . $id_tes . "]\" value=\"Ripristina lo stato iniziale!\" /></td>";
        echo "</tr>\n";
    }
}
echo "</table>\n";
?>
</form>
<?php
require("../../library/include/footer.php");
?>
