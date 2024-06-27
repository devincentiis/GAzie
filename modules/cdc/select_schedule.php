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
require("../../library/include/electronic_invoice.inc.php");

$debug = false;
$fileConfPers = dirname(__FILE__) . '/cdc_debug.php';
if (file_exists($fileConfPers)) {
    include_once($fileConfPers);
}

$isAccettazione = $debug;
$admin_aziend = checkAdmin();
if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['this_date_D'] = date("d");
    $form['orderby'] = 2;
} else { // accessi successivi
    $form['hidden_req'] = htmlentities($_POST['hidden_req']);
    $form['ritorno'] = $_POST['ritorno'];
    if (isset($_POST['return'])) {
        header("Location: " . $form['ritorno']);
        exit;
    }
    $form['orderby'] = intval($_POST['orderby']);
}
// fine controlli
$scdl = new Schedule;
//$m = $scdl->getScheduleEntries($form['orderby'], $admin_aziend['masfor']);

$mCli = $scdl->getScheduleEntries($form['orderby'], $admin_aziend['mascli']);
$entriesCli = $scdl->Entries;
$mFor = $scdl->getScheduleEntries($form['orderby'], $admin_aziend['masfor']);
$entriesFor = $scdl->Entries;
$entries = array_merge($entriesCli, $entriesFor);

//var_dump($scdl->Entries);

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup', 'custom/autocomplete'));
echo '<form method="POST" name="select">
		<input type="hidden" value="' . $form['hidden_req'] . '" name="hidden_req" />
		<input type="hidden" value="' . $form['ritorno'] . '" name="ritorno" />';
if (isset($_POST['invia'])) {
//    var_dump($_POST['check_ddt']);
    inviaFatture($_POST['check_ddt'], $entries, $admin_aziend);
}
if (isset($_POST['invia_manuale'])) {
//    var_dump($_POST['check_ddt']);
    inviaDatiManuali($_POST['check_ddt'], $entries, $admin_aziend);
}

$gForm = new cdcForm();
/** Modifico il form per l'ordinamento, lo rendo più snello, niente più tasto anteprima (vedi considerazioni di seguito) */
echo '<div align="center" class="FacetFormHeaderFont">' . $script_transl['title'] . '</div>
	  <table class="Tmiddle table table-striped table-bordered table-condensed table-responsive">
	  	<tr>
			<td class="FacetFieldCaptionTD">' . $script_transl['orderby'] . '</td>
			<td class="FacetDataTD">';
$gForm->variousSelect('orderby', $script_transl['orderby_value'], $form['orderby'], 'FacetSelect', 0, 'orderby');
echo '		</td>
			<td align="left">
				<input type="submit" name="return" value="' . $script_transl['return'] . '" />
			</td>
		</tr>
	  </table>
	  <br />';
echo '<div class="row"><div class="col-md-12">'
 . $script_transl['precisazione']
 . '</div></div>';
echo '<table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
  			<thead>';
if (sizeof($entries) > 0) {
    $ctrl_partner = 0;
    $ctrl_id_tes = 0;
    $ctrl_paymov = 0;

    /* ENRICO FEDELE */
    /* Inizializzo le variabili per il totale */
    /* $tot_dare  = 0;
      $tot_avere = 0; */
    $tot = array('dare' => 0, 'avere' => 0);
    /* ENRICO FEDELE */

    echo '	<tr>';
    $linkHeaders = new linkHeaders($script_transl['header']);
    $linkHeaders->output();
    echo '		</tr>
				</thead>
				<tbody>';
    $status_descr = '';
    foreach ($entries AS $key => $mv) {
        $class_partner = '';
        $class_paymov = '';
        $class_id_tes = '';
        $partner = '';
        $id_tes = '';
        $paymov = '';
        $status_del = false;
        $status_descr = '';
        $id_doc = $mv["id_doc"];
        if ($mv["codice"] <> $ctrl_partner) {
            $class_partner = 'FacetDataTD';
            $partner = $mv["ragsoc"];
        }
        if ($mv["id_tes"] <> $ctrl_id_tes) {
            $class_id_tes = 'FacetFieldCaptionTD';
            $id_tes = $mv["id_tes"];
            $mv["datdoc"] = $mv["id_doc"] ? gaz_format_date($mv["datdoc"]) : '';
        } else {
            $mv['descri'] = '';
            $mv['numdoc'] = '';
            $mv['datdoc'] = '';
            $class_partner = '';
            $partner = '';
            $status_descr = '';
        }
        if ($mv["id_tesdoc_ref"] <> $ctrl_paymov) {
            $paymov = $mv["id_tesdoc_ref"];
            $scdl->getStatus($paymov);
            $r = $scdl->Status;
            $status_descr .= $script_transl['status_value'][$r['sta']];
            // link 
//            $riscuoti_btn = sprintf('&nbsp; <a title="Riscuoti" class="btn btn-xs btn-default btn-pagamento" href="customer_payment.php?partner=' . $mv["codice"] . '%s"><i class="glyphicon glyphicon-euro"></i></a>',
//                    $mv['id_doc'] ? '&amp;numdoc=' . $mv['numdoc'] . '&amp;datdoc=' . gaz_format_date($mv['datdoc'], true) : '');

            if ($r['sta'] == 1 || $r['sta'] == 9) // CHIUSA o anticipo
                continue;   // non visualizziamo questa riga
            switch ($r['sta']) {
                case 1: // CHIUSA
                    $class_paymov = '';
                    $status_del = true;
                    break;
                case 2: // ESPOSTA
                    $class_paymov = 'FacetDataTDevidenziaOK';
                    break;
                case 3: // SCADUTA
                    $class_paymov = 'FacetDataTDevidenziaKO';
//                    $status_descr .= $riscuoti_btn;
                    break;
                case 9: // ANTICIPO
                    $class_paymov = 'FacetDataTDevidenziaBL';
                    break;
                default: //APERTA
                    $class_paymov = 'FacetDataTDevidenziaCL';
//                    $status_descr .= $riscuoti_btn;
            }
        }
        if (isVendita($mv, $admin_aziend)) {// è una vendita
            $class_riga = "gaz-ricavi";
        } else {
            $class_riga = "gaz-costi";
        }
        echo "<tr>";
        echo "<td class=\"$class_partner $class_riga\">" . $partner . " &nbsp;</td>";
        echo "<td align=\"center\" class=\"$class_paymov \">" . (empty($id_doc) ? "" : "<input type=\"checkbox\" name=\"check_ddt[]\"value=\"" . $mv["id_tesdoc_ref"] . "\">") . "</td>";
//        echo "<td align=\"center\" class=\"$class_paymov\">" . "$id_doc <input type=\"checkbox\" name=\"check_ddt[]\" value=\"$id_tes\"></td>";
        echo "<td align=\"center\" class=\"$class_paymov \">" . $paymov . " &nbsp;</td>";
        echo "<td align=\"center\" class=\"$class_paymov \">" . $status_descr . " &nbsp;</td>";
        echo "<td align=\"center\" class=\"$class_id_tes $class_riga\"><a href=\"../contab/admin_movcon.php?id_tes=" . $mv["id_tes"] . "&Update\">" . $id_tes . "</a> &nbsp</td>";
        echo "<td class=\"$class_id_tes $class_riga\"><a href=\"../contab/admin_movcon.php?id_tes=" . $mv["id_tes"] . "&Update\">" . $mv['descri'] . "</a> &nbsp;</td>";
        echo "<td align=\"center\" class=\" $class_riga\">" . $mv["numdoc"] . " &nbsp;</td>";
        echo "<td align=\"center\" class=\" $class_riga\">" . $mv["datdoc"] . " &nbsp;</td>";
        echo "<td align=\"center\" class=\" $class_riga\">" . gaz_format_date($mv["datreg"]) . " &nbsp;</td>";
        /* ENRICO FEDELE */
        if ($mv['id_rigmoc_pay'] == 0) {
            /* Incremento il totale del dare */
            $tot['dare'] += $mv['amount'];
            /* Allineo a destra il testo, i numeri sono così più leggibili e ordinati, li formatto con apposita funzione */
            echo "<td class=\" $class_riga\" align=\"right\">" . gaz_format_number($mv["amount"]) . " &nbsp;</td>";
            echo "<td class=\" $class_riga\"></td>";
        } else {
            /* Incremento il totale dell'avere, e decremento quello del dare */
            $tot['avere'] += $mv['amount'];
            $tot['dare'] -= $mv['amount'];
            echo "<td class=\" $class_riga\"></td>";
            echo "<td class=\" $class_riga\" align=\"right\">" . gaz_format_number($mv["amount"]) . " &nbsp;</td>";
        }
        /* ENRICO FEDELE */
        echo "<td align=\"center\" class=\" $class_riga\">" . gaz_format_date($mv["expiry"]) . " &nbsp;</td>";
        /*
          echo "<td align=\"center\" class=\"FacetDataTD\"> ";

          // Permette di cancellare il documento.
          if ($status_del) {
          echo "<a class=\"btn btn-xs  btn-elimina\" title=\"Cancella tutti i movimenti relativi a questa partita oramai chiusa (rimarranno comunque i movimenti contabili)\" href=\"delete_schedule.php?id_tesdoc_ref=" . $paymov . "\"><i class=\"glyphicon glyphicon-trash\"></i></a>";
          } else {
          echo "<button title=\"Non &egrave; possibile cancellare una partita ancora aperta\" class=\"btn btn-xs   disabled\"><i class=\"glyphicon glyphicon-trash\"></i></button>";
          }
          echo "</td>";

         */
        echo "</tr>\n";
        $ctrl_id_tes = $mv["id_tes"];
        $ctrl_paymov = $mv["id_tesdoc_ref"];
        $ctrl_partner = $mv["codice"];
    }
} else {
    echo '	<tr>
	 			<td class="FacetDataTDred" align="center">' . $script_transl['errors'][1] . '</td>
			</tr>';
}
//echo '</table>';
echo '<td></td>'
 . '<td colspan="4">'
 . $script_transl['tutti']
 . '<input type="checkbox" onClick="check(this)">'
 . '</td>'
 . '<td colspan="5">'
 . $script_transl['accettazione']
 . '<input type="checkbox" name="accettazione" onClick="abilitaBottoni(this.checked);"' . ($isAccettazione ? "checked" : "") . '>'
 . '</td>'
// . '<td colspan="2">'
 . '<td>'
 . '<input id="invia" class="btn btn-primary" onClick="chkSubmit();" type="submit" name="invia" value="'
 . $script_transl['confirm_entry']
 . '" ' . ($isAccettazione ? "" : "disabled") . ' />'
 . '</td>'
 . '<td>'
 . '<input id="invia_manuale" class="btn btn-success" onClick="chkSubmit();" type="submit" name="invia_manuale" value="'
 . $script_transl['confirm_manual_entry']
 . '" ' . ($isAccettazione ? "" : "disabled") . ' />'
 . '</td>'
 . '</table>'
 . '</form>';

/** ENRICO FEDELE */
/* Chiudeva il controllo if (isset($_POST['preview'])) */
//}
/** ENRICO FEDELE */
?>
<script>
    function selectCheckbox() {
        var inputs = document.getElementsByTagName('input');
        var checkboxes = [];
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            if (input.getAttribute('type') == 'checkbox' && input.getAttribute("name") != 'accettazione') {
                checkboxes.push(input);
            }
        }
        return checkboxes;
    }
    function check(checks) {
        var checkboxes = selectCheckbox();
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = checks.checked;
        }
        // document.forms['tesdoc'].submit();
    }

    function abilitaBottoni(privacyAccettata) {
        document.getElementById('invia').disabled = !privacyAccettata;
        document.getElementById('invia_manuale').disabled = !privacyAccettata;
    }

</script>
<?php

require("../../library/include/footer.php");

function isVendita($mv, $admin_aziend) {
    return (substr($mv['codice'], 0, strlen($admin_aziend['mascli'])) === $admin_aziend['mascli']);
}

/* function inviaFattureOld($idtesDaInviare, $partiteAperte, $admin_aziend) {
  global $gTables, $debug;
  define("MIN_FATTURA_DA_CONSIDERARE", 100.0);

  if (count($idtesDaInviare) == 0)
  return;
  require("inc/cdc_inc.php");
  echo '<div class="error_box bg-danger">';

  $url = $debug ? 'https://localhost/cc_webapp/webservices/' : 'https://webapp.cameracompensazione.it/webservices/';
  $p1 = base64_decode($ca);
  $p2 = base64_decode($tk);
  $jsonLogin = "{\"op\":\"gjwt\",\"dati\":{\"cod_affiliato\":\"$p1\",\"token\":\"$p2\"}}";

  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonLogin);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // per i certificati autofirmati
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  $response = curl_exec($ch);
  if ($response === false) {
  echo '<p>Curl error: ' . curl_error($ch) . "</p>";
  } else {
  print "<p>Connesso con Camera di Compensazione</p>";
  //var_dump($response);
  $risposta = json_decode($response);
  //        var_dump($risposta);

  $jwt = $risposta->jwt;
  //        echo("jwt: $jwt");
  //        var_dump($partiteAperte);
  echo "<ul>";
  foreach ($idtesDaInviare as $key => $id_mov) {
  //            modules/vendit/electronic_invoice.php?id_tes=3&viewxml
  $residuo = 0;
  $id_doc = 0;
  //            echo "\n<h1> id_tes: $id_mov </h1>\n";

  foreach ($partiteAperte as $keyPartite => $mv) {
  if ($mv["id_tesdoc_ref"] != $id_mov)
  continue;
  //                var_dump($mv);
  //                echo "{$mv['id_doc']} {$mv['amount']} <p> \n";
  if ($mv['id_doc'] != 0) {
  $id_doc = $mv['id_doc'];
  $numdoc = $mv["numdoc"];
  $ragsoc = $mv['ragsoc'];
  $datdoc = $mv['datdoc'];
  if (isVendita($mv, $admin_aziend)) {
  $tipoFattura = "v";
  $descrTipoFattura = "VENDITA";
  } else {
  $tipoFattura = "a";
  $descrTipoFattura = "ACQUISTO";
  }
  }
  if ($mv['id_rigmoc_pay'] == 0) {
  $residuo += $mv['amount'];
  } else {
  $residuo -= $mv['amount'];
  }
  }
  //            echo "<h1> $id_doc, $residuo, </h1>";
  echo "<li><b>$descrTipoFattura</b>: $ragsoc ft. n. $numdoc del $datdoc residuo=€" . $residuo;
  if ($id_doc != 0 && $residuo > MIN_FATTURA_DA_CONSIDERARE) {
  if ($tipoFattura == "v") {// fattura di vendita, devo costruirla
  $id_testata = intval($id_doc);
  $testata = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', $id_testata);
  $where = "tipdoc = '" . $testata['tipdoc'] . "' AND seziva = " . $testata['seziva'] . " AND YEAR(datfat) = " . substr($testata['datfat'], 0, 4) . " AND protoc = " . $testata['protoc'];
  if ($testata['tipdoc'] == 'VCO') { // in caso di fattura allegata a scontrino mi baso solo sull'id_tes
  $where = "id_tes = " . $id_testata;
  }
  $testate = gaz_dbi_dyn_query("*", $gTables['tesdoc'], $where, 'datemi ASC, numdoc ASC, id_tes ASC');
  $fattura = create_XML_invoice($testate, $gTables, 'rigdoc', false, false, true);
  //                echo $fattura["nome_file"];
  //                echo $fattura["documento"];
  } else {// fattura di acquisto, devo leggerla
  $fe = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", intval($id_doc));
  $file = DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . intval($id_doc) . '.inv';
  $fattura["nome_file"] = $fe['fattura_elettronica_original_name'];
  $fattura["documento"] = file_get_contents($file);
  }
  $codProvenienza = null;
  $nomeFile = $fattura["nome_file"];
  $contenuto = $fattura["documento"];
  if (strlen($contenuto) < 100) {// file troppo piccolo, non può essere un xml
  echo " non inviata perchè non contiene xml valido: $contenuto<br>\n";
  continue;
  } else {
  $cont_b64 = base64_encode($contenuto);
  }

  $jsonOp = <<<EOD
  {
  "op": "ins_dati",
  "jwt": "$jwt",
  "dati": {
  "codProvenienza":null,
  "tipo_fattura": "$tipoFattura",
  "nome_file": "$nomeFile",
  "documento_base64": "$cont_b64",
  "importo_residuo": "$residuo"
  }
  }
  EOD;
  //                echo($jsonOp);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonOp);
  $response = curl_exec($ch);
  $risposta = json_decode($response);
  //                var_dump($risposta);

  $esito = $risposta->result;
  $errore = $risposta->message;
  if ($esito == "ok") {
  echo " <b>correttamente inviata</b></li>\n";
  } else {
  echo " invio fallito: " . $errore . "</li>\n";
  }
  } else {
  echo " non inviata perchè minore di €" . MIN_FATTURA_DA_CONSIDERARE . "</li>\n";
  }
  }
  }
  curl_close($ch);
  echo '</ul></div>';
  } */

function inviaFatture($idtesDaInviare, $partiteAperte, $admin_aziend) {
    global $gTables, $debug;
    define("MIN_FATTURA_DA_CONSIDERARE", 100.0);

    if (count($idtesDaInviare) == 0)
        return;
    require_once "include/cdc.inc.php";
    require_once 'include/cdc.def.php';
    echo '<div class="error_box bg-danger">';

    $url = $debug ? 'https://localhost/cc_webapp/webservices/' : 'https://webapp.cameracompensazione.it/webservices/';
    $p1 = base64_decode($ca);
    $p2 = base64_decode($tk);

    $ch = creaCurl($url);
    $jwt = getJwt($ch, $p1, $p2);
    if ($jwt != null) {
        echo "<ul>";
        foreach ($idtesDaInviare as $key => $id_mov) {
//            modules/vendit/electronic_invoice.php?id_tes=3&viewxml
            $residuo = 0;
            $id_doc = 0;
//            echo "\n<h1> id_tes: $id_mov </h1>\n";

            foreach ($partiteAperte as $keyPartite => $mv) {
                if ($mv["id_tesdoc_ref"] != $id_mov)
                    continue;
//                var_dump($mv);
//                echo "{$mv['id_doc']} {$mv['amount']} <p> \n";
                if ($mv['id_doc'] != 0) {
                    $id_doc = $mv['id_doc'];
                    $numdoc = $mv["numdoc"];
                    $ragsoc = $mv['ragsoc'];
                    $datdoc = $mv['datdoc'];
                    if (isVendita($mv, $admin_aziend)) {
                        $tipoFattura = "v";
                        $descrTipoFattura = "VENDITA";
                    } else {
                        $tipoFattura = "a";
                        $descrTipoFattura = "ACQUISTO";
                    }
                }
                if ($mv['id_rigmoc_pay'] == 0) {
                    $residuo += $mv['amount'];
                } else {
                    $residuo -= $mv['amount'];
                }
            }
//            echo "<h1> $id_doc, $residuo, </h1>";
//                echo "<li><b>$descrTipoFattura</b>: $ragsoc ft. n. $numdoc del $datdoc residuo=€" . $residuo;
            if ($id_doc != 0 && $residuo > MIN_FATTURA_DA_CONSIDERARE) {
                if ($tipoFattura == "v") {// fattura di vendita, devo costruirla
                    $id_testata = intval($id_doc);
                    $testata = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', $id_testata);
                    $where = "tipdoc = '" . $testata['tipdoc'] . "' AND seziva = " . $testata['seziva'] . " AND YEAR(datfat) = " . substr($testata['datfat'], 0, 4) . " AND protoc = " . $testata['protoc'];
                    if ($testata['tipdoc'] == 'VCO') { // in caso di fattura allegata a scontrino mi baso solo sull'id_tes
                        $where = "id_tes = " . $id_testata;
                    }
                    $testate = gaz_dbi_dyn_query("*", $gTables['tesdoc'], $where, 'datemi ASC, numdoc ASC, id_tes ASC');
                    $fattura = create_XML_invoice($testate, $gTables, 'rigdoc', false, false, true);
//                echo $fattura["nome_file"];
//                echo $fattura["documento"];
                } else {// fattura di acquisto, devo leggerla
                    $fe = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", intval($id_doc));
                    $file = DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . intval($id_doc) . '.inv';
                    $fattura["nome_file"] = $fe['fattura_elettronica_original_name'];
                    $fattura["documento"] = file_get_contents($file);
                }
                $codProvenienza = null;
                $nomeFile = $fattura["nome_file"];
                $contenuto = $fattura["documento"];
                if (strlen($contenuto) < 100) {// file troppo piccolo, non può essere un xml
                    echo " non inviata perchè non contiene xml valido: $contenuto<br>\n";
                    continue;
                } else {
                    $cont_b64 = base64_encode($contenuto);
                }
                sendFattura($ch, $jwt, $tipoFattura, $nomeFile, base64_encode($contenuto), $residuo);
            } else {
                echo " non inviata perchè minore di €" . MIN_FATTURA_DA_CONSIDERARE . "</li>\n";
            }
        }
    }

    curl_close($ch);
    echo '</ul></div>';
}

function inviaDatiManuali($idtesDaInviare, $partiteAperte, $admin_aziend) {
    global $gTables, $debug;
    define("MIN_FATTURA_DA_CONSIDERARE", 100.0);

    if (count($idtesDaInviare) == 0)
        return;
    require_once "include/cdc.inc.php";
    require_once 'include/cdc.def.php';
    echo '<div class="error_box bg-danger">';

    $url = $debug ? 'https://localhost/cc_webapp/webservices/' : 'https://webapp.cameracompensazione.it/webservices/';
    $p1 = base64_decode($ca);
    $p2 = base64_decode($tk);

    $ch = creaCurl($url);
    $jwt = getJwt($ch, $p1, $p2);
    if ($jwt != null) {
        echo "<ul>";
        foreach ($idtesDaInviare as $key => $id_mov) {
//            modules/vendit/electronic_invoice.php?id_tes=3&viewxml
            $residuo = 0;
            $id_doc = 0;
//            echo "\n<h1> id_tes: $id_mov </h1>\n";

            foreach ($partiteAperte as $keyPartite => $mv) {
                if ($mv["id_tesdoc_ref"] != $id_mov)
                    continue;
//                var_dump($mv);
//                echo "{$mv['id_doc']} {$mv['amount']} <p> \n";
                if ($mv['id_doc'] != 0) {
                    $id_doc = $mv['id_doc'];
                    $numdoc = $mv["numdoc"];
                    $ragsoc = $mv['ragsoc'];
                    $datdoc = $mv['datdoc'];
                    $importoTotale = $mv['amount'];
                    if (isVendita($mv, $admin_aziend)) {
                        $tipoFattura = "v";
                        $descrTipoFattura = "VENDITA";
                        $partitaIvaCreditore = $admin_aziend['pariva'];
                        $partitaIvaDebitore = $mv['pariva'];
                        $numdoc = $numdoc . '/' . $mv["seziva"]; // nelle fatture elettroniche emesse da Gazie, viene aggiunta la sezione IVA
                    } else {
                        $tipoFattura = "a";
                        $descrTipoFattura = "ACQUISTO";
                        $partitaIvaCreditore = $mv['pariva'];
                        $partitaIvaDebitore = $admin_aziend['pariva'];
                    }
                }
                if ($mv['id_rigmoc_pay'] == 0) {
                    $residuo += $mv['amount'];
                } else {
                    $residuo -= $mv['amount'];
                }
            }
//            echo "<h1> $id_doc, $residuo, </h1>";
//                echo "<li><b>$descrTipoFattura</b>: $ragsoc ft. n. $numdoc del $datdoc residuo=€" . $residuo;
            if ($id_doc != 0 && $residuo > MIN_FATTURA_DA_CONSIDERARE) {
                sendDatiManuali($ch, $jwt, $tipoFattura, $partitaIvaCreditore, $partitaIvaDebitore, $datdoc, $numdoc, $importoTotale, $residuo);
            } else {
                echo " non inviata perchè minore di €" . MIN_FATTURA_DA_CONSIDERARE . "</li>\n";
            }
        }
    }

    curl_close($ch);
    echo '</ul></div>';
}
?>