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
$mastroclienti = $admin_aziend['mascli'] . "000000";
$mastrofornitori = $admin_aziend['masfor'] . "000000";
$scorrimento = gaz_dbi_get_row($gTables['company_config'], 'var', 'autoscroll_to_last_row')['val'];
$anagrafica = new Anagrafica();
$msg = "";
$form = array();
if (!isset($_POST['ritorno'])) {
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
} else {
    $form['ritorno'] = $_POST['ritorno'];
}

if ((isset($_POST['Update'])) or ( isset($_GET['Update']))) {
  if (!isset($_GET['id_tes'])) {
      header("Location: " . $_POST['ritorno']);
      exit;
  } else {
      $_POST['id_tes'] = $_GET['id_tes'];
  }
  $toDo = 'update';
} else {
  $toDo = 'insert';
}

if ((!isset($_POST['Update'])) and ( isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $form['hidden_req'] = '';
    //recupero la testata con la causale
    $rs_testata = gaz_dbi_dyn_query("*", $gTables['tesmov'], "id_tes = '" . intval($_GET['id_tes']) . "'", "id_tes asc", 0, 1);
    $testata = gaz_dbi_fetch_array($rs_testata);
    $form['id_testata'] = $testata['id_tes'];
    $form['codcausale'] = $testata['caucon'];
    $form['descrizion'] = $testata['descri'];
    $form['notess'] = $testata['notess'];
    $form['registroiva'] = $testata['regiva'];
    $form['operatore'] = $testata['operat'];
    $form['date_reg_D'] = substr($testata['datreg'], 8, 2);
    $form['date_reg_M'] = substr($testata['datreg'], 5, 2);
    $form['date_reg_Y'] = substr($testata['datreg'], 0, 4);
    $form['sezioneiva'] = $testata['seziva'];
    $form['protocollo'] = $testata['protoc'];
    $form['numdocumen'] = $testata['numdoc'];
    $form['id_doc'] = $testata['id_doc'];
    $form['datdoc'] = gaz_format_date($testata['datdoc'], false, true);
    $form['datliq'] = gaz_format_date($testata['datliq'], false, true);
    $form['cod_partner'] = $testata['clfoco'];
    $form['pay_closure'] = 0;
    $partnersel = $anagrafica->getPartner($form['cod_partner']);
    $form['pagame'] =($partnersel)?$partnersel['codpag']:'';
    if ($form['numdocumen'] > 0 or ! empty($form['numdocumen'])) {
        $form['inserimdoc'] = '1';
    } else {
        $form['inserimdoc'] = '0';
    }
    $form['registroiva'] = $testata['regiva'];
    $form['operatore'] = $testata['operat'];
    $form['insert_mastro'] = '000000000';
    $form['insert_conto'] = '000000000';
    $form['search']['insert_conto'] = '';
    $form['paymov'] = array();
    $form['insert_darave'] = 'A';
    $form['insert_conto'] = '000000000';
    $form['insert_codiva'] = $admin_aziend['preeminent_vat'];
    $form['insert_imponi'] = 0;
    $form['reverse_charge'] = '';
    $form['operation_type'] = '';
    //recupero i righi iva
    $rs_righiva = gaz_dbi_dyn_query("*", $gTables['rigmoi'], "id_tes = '" . intval($form['id_testata']) . "'", "id_rig asc");
    $i = 0;
    while ($row = gaz_dbi_fetch_array($rs_righiva)) {
        $msg = "13+";
        $form['insert_codiva'] = $row['codiva'];
        $form['id_rig_ri'][$i] = $row['id_rig'];
        $form['codiva_ri'][$i] = $row['codiva'];
        $form['imponi_ri'][$i] = $row['imponi'];
        $form['impost_ri'][$i] = $row['impost'];
        $form['reverse_charge_ri'][$i] = $row['reverse_charge_idtes'];
        $form['operation_type_ri'][$i] = $row['operation_type'];
        $i++;
        $_POST['rigiva'] = $i;
    }
    //recupero i righi contabili
    $rs_righcon = gaz_dbi_dyn_query("*", $gTables['rigmoc'], "id_tes = '" . intval($form['id_testata']) . "'", "id_rig asc");
    $i = 0;
    while ($row = gaz_dbi_fetch_array($rs_righcon)) {
        $form['id_rig_rc'][$i] = $row['id_rig'];
        $form['mastro_rc'][$i] = substr($row['codcon'], 0, 3) . '000000';
        $form['conto_rc' . $i] = $row['codcon'];
        $form['search']['conto_rc' . $i] = '';
        $form['darave_rc'][$i] = $row['darave'];
        $form['importorc'][$i] = $row['import'];
        $form['paymov_op_cl'][$i] = 0;
        // recupero le eventuali partite aperte
        if (($form['mastro_rc'][$i] == $mastroclienti || $form['mastro_rc'][$i] == $mastrofornitori) && $form['conto_rc' . $i] > 0) {
            if (($form['mastro_rc'][$i] == $mastroclienti && $form['darave_rc'][$i] == 'D') || ($form['mastro_rc'][$i] == $mastrofornitori && $form['darave_rc'][$i] == 'A')) { // è un rigo di documento o addebito (apertura partita)
                $form['paymov_op_cl'][$i] = 1;
            } else {                            // E' un rigo di pagamento o storno (chiusura partita)
                $form['paymov_op_cl'][$i] = 2;
            }
            $where = "id_rigmoc_pay = " . $row['id_rig'] . " OR id_rigmoc_doc = " . $row['id_rig'];
            $numpaymov = gaz_dbi_record_count($gTables['paymov'], $where);
            $rs_paymov = gaz_dbi_dyn_query("*", $gTables['paymov'], $where, "id asc");
            if ($numpaymov > 0) {
                while ($rpm = gaz_dbi_fetch_array($rs_paymov)) {
                    $form['paymov'][$i][$rpm['id']] = $rpm;
                    $form['paymov'][$i][$rpm['id']]['expiry'] = gaz_format_date($rpm['expiry']);
                }
            } else {
                $form['paymov'][$i]['new'] = array('id' => 'new', 'id_tesdoc_ref' => 'new', 'amount' => '0.00', 'expiry' => '');
            }
        }
        // fine recupero partite aperte
        $i++;
        $_POST['rigcon'] = $i;
    }
} elseif ((isset($_POST['Insert'])) or ( isset($_POST['Update']))) {   //se non e' il primo accesso
    $form['hidden_req'] = htmlentities($_POST['hidden_req']);
    //ricarico i registri per il form della testata
    $form['id_testata'] = $_POST['id_testata'];
    $form['codcausale'] = $_POST['codcausale'];
    $form['descrizion'] = $_POST['descrizion'];
    $form['notess'] = $_POST['notess'];

    $form['date_reg_D'] = intval($_POST['date_reg_D']);
    $form['date_reg_M'] = intval($_POST['date_reg_M']);
    $form['date_reg_Y'] = intval($_POST['date_reg_Y']);

    $form['inserimdoc'] = $_POST['inserimdoc'];
    $form['registroiva'] = $_POST['registroiva'];
    $form['operatore'] = $_POST['operatore'];
    if ($form['registroiva']>0 && $form['registroiva']<9) {
        $form['inserimdoc'] = 1;
    }
    $form['sezioneiva'] = $_POST['sezioneiva'];
    $form['protocollo'] = $_POST['protocollo'];
    $form['numdocumen'] = $_POST['numdocumen'];
    $form['id_doc'] = $_POST['id_doc'];
    $form['datdoc'] = substr($_POST['datdoc'], 0, 10);
    $form['datliq'] = substr($_POST['datliq'], 0, 10);
    $form['cod_partner'] = $_POST['cod_partner'];
    $form['pay_closure'] = $_POST['pay_closure'];
    $partnersel = $anagrafica->getPartner($form['cod_partner']);
    $form['pagame'] = intval($_POST['pagame']);
    //ricarico i registri per il form del rigo di inserimento contabile
    $form['insert_mastro'] = $_POST['insert_mastro'];
    $form['insert_conto'] = $_POST['insert_conto'];
    foreach ($_POST['search'] as $k => $v) {
        $form['search'][$k] = $v;
    }
    $form['insert_darave'] = $_POST['insert_darave'];
    //ricarico i registri per il form del rigo di inserimento iva
    if (!isset($_POST['rigiva'])) {  //se non c'erano righi in precedenza
        $_POST['rigiva'] = 0;
        $form['insert_codiva'] = $admin_aziend['preeminent_vat'];
        $form['insert_imponi'] = 0;
        $form['reverse_charge'] = '';
        $form['operation_type'] = '';
    } else {
        $form['insert_codiva'] = $_POST['insert_codiva'];
        $form['insert_imponi'] = $_POST['insert_imponi'];
        $form['reverse_charge'] = substr($_POST['reverse_charge'], 0, 9);
        $form['operation_type'] = substr($_POST['operation_type'], 0, 15);
    }
    //ricarico i registri per il form dei righi contabili già  immessi
    $loadCosRic = 0; //  1 se cliente, 2 se fornitore
    $countPartners = 0;
    for ($i = 0; $i < $_POST['rigcon']; $i++) {
        $form['id_rig_rc'][$i] = $_POST['id_rig_rc'][$i];
        $form['mastro_rc'][$i] = $_POST['mastro_rc'][$i];
        $form['conto_rc' . $i] = $_POST['conto_rc' . $i];
        $form['darave_rc'][$i] = $_POST['darave_rc'][$i];
        $form['importorc'][$i] = $_POST['importorc'][$i];
        $form['paymov_op_cl'][$i] = 0;
        if ($_POST['mastro_rc'][$i] == $mastroclienti || $_POST['mastro_rc'][$i] == $mastrofornitori) {
            if ($_POST['conto_rc' . $i] > 0) {
                if ($_POST['conto_rc' . $i] != $form['cod_partner']) { // ho già un partner selezionato e questo è diverso
                    $countPartners++;
                }
                $partnersel = $anagrafica->getPartner($form['conto_rc' . $i]);
                //se viene inserito un nuovo partner do l'ok alla ricarica della contropartita costi/ricavi in base al conto presente sull'archivio clfoco
                if ($_POST['cod_partner'] == 0 and $form['conto_rc' . $i] > 0) {
                    $partner = $partnersel;
                    $loadCosRic = substr($form['conto_rc' . $i], 0, 1);
                    $form['cod_partner'] = '';
                    // prpongo l'eventuale aliquota IVA diversa associata al partner
                    if ($partner['aliiva'] >= 1 ) {
                        $form['insert_codiva'] = $partner['aliiva'];
                    }
                    // ricarico pure l'eventuale riferimento al tipo di operazione ma solo se vuoto
                    if ($form['operation_type'] == '') {
                        $form['operation_type'] = $partner['operation_type'];
                    }
                    if ($countPartners == 1) { // solo se è previsto l'utilizzo dei dati dei documenti ed ho un solo partner lo setto
                        $form['cod_partner'] = $_POST['conto_rc' . $i];
                    } else {
                        $form['cod_partner'] = '';
                    }
                    $form['pagame'] = $partner['codpag'];
                }
                $pay = gaz_dbi_get_row($gTables['pagame'], "codice", $form['pagame']);
                // in caso di pagamento immediato dovrò settare l'importo di chiusura ed il relativo conto
                if (!$pay){
                  $pay=['pagaut'=>0,'incaut'=>0];
                }
                if ($pay['pagaut'] > 1 && ($form['registroiva'] >= 6 && $form['registroiva'] <= 9) && $form['operatore'] == 1) { // è un documento di acquisto pagato immediatamente (es.contanti-assegno-bancomat-carta)
                    $payacc = gaz_dbi_get_row($gTables['clfoco'], "codice", $pay['pagaut']);
                    $form['pay_closure'] = $payacc['codice'];
                    $form['pay_importo'] = $form['importorc'][$i];
                } elseif ($pay['incaut'] > 1 && ($form['registroiva'] >= 1 && $form['registroiva'] <= 5) && $form['operatore'] == 1) { // è un documento di vendita con pagamento immediato
                    $payacc = gaz_dbi_get_row($gTables['clfoco'], "codice", $pay['incaut']);
                    $form['pay_closure'] = $payacc['codice'];
                    $form['pay_importo'] = $form['importorc'][$i];
                } else {
                    $form['pay_closure'] = 0;
                }
                // in $form['pay_closure'] ho la contropartita di chiusura
            } else {
                $form['pay_closure'] = 0;
                $form['cod_partner'] = 0;
            }
            if (($form['mastro_rc'][$i] == $mastroclienti && $form['darave_rc'][$i] == 'D') || ($form['mastro_rc'][$i] == $mastrofornitori && $form['darave_rc'][$i] == 'A')) { // è un rigo di documento o addebito (apertura partita)
                $form['paymov_op_cl'][$i] = 1;
            } else {                            // E' un rigo di pagamento o storno (chiusura partita)
                $form['paymov_op_cl'][$i] = 2;
            }
            if (isset($_POST['paymov'][$i])) { // se ho dati sul form delle partite aperte dei clienti/fornitori li ricarico
                $paymov_tot[$i] = 0.00;
                foreach ($_POST['paymov'][$i] as $k => $v) {
                    $form['paymov'][$i][$k] = $v;  // qui devo ancora fare il parsing
                    $paymov_tot[$i] += $v['amount'];
                }
                if ($paymov_tot[$i] >= 0.01 && round($paymov_tot[$i], 2) > $form['importorc'][$i]) {
                    $msg .= '15+';
                }
            } else {
                $form['paymov'][$i]['new'] = array('id' => 'new', 'id_tesdoc_ref' => 'new', 'amount' => '0.00', 'expiry' => '');
            }
            /* controllo se il pagamento del cliente/fornitore prevede che vengano
               eseguite le scritture di chiusura e nel caso setto il valore giusto */
        }
        if ($loadCosRic == 1 && substr($form['conto_rc' . $i], 0, 1) == 4 && $partner['cosric'] > 0 && $form['registroiva'] > 0) {  //e' un  cliente agisce sui ricavi
            $form['mastro_rc'][$i] = substr($partner['cosric'], 0, 3) . "000000";
            $form['conto_rc' . $i] = $partner['cosric'];
            $loadCosRic = 0;
        } elseif ($loadCosRic == 2 && substr($form['conto_rc' . $i], 0, 1) == 3 && $partner['cosric'] > 0 && $form['registroiva'] > 0) { //è un fornitore  agisce sui costi
            $form['mastro_rc'][$i] = substr($partner['cosric'], 0, 3) . "000000";
            $form['conto_rc' . $i] = $partner['cosric'];
            $loadCosRic = 0;
        }
        if($form['registroiva'] == 9){ // è un versamento IVA forzo tutti gli importi al valore del rigo IVA
            $form['importorc'][$i] = floatval($_POST['impost_ri'][0]);
        }
    }
    //ricarico i registri per il form dei righi iva già  immessi
    for ($i = 0; $i < $_POST['rigiva']; $i++) {
        $form['id_rig_ri'][$i] = $_POST['id_rig_ri'][$i];
        $form['codiva_ri'][$i] = $_POST['codiva_ri'][$i];
        $form['imponi_ri'][$i] = $_POST['imponi_ri'][$i];
        $form['impost_ri'][$i] = $_POST['impost_ri'][$i];
        $form['reverse_charge_ri'][$i] = $_POST['reverse_charge_ri'][$i];
        $form['operation_type_ri'][$i] = $_POST['operation_type_ri'][$i];
    }


    // Se viene inviata la richiesta di conferma della causale la carico con le relative contropartite...
    if (isset($_POST['inscau'])) {
        // Se la descrizione è vuota e la causale è stata selezionata
        if (!empty($form['codcausale']) and empty($form['descrizion'])) {

            function getLastNumber($type, $year, $sezione, $registro = 6) {  // questa funzione trova l'ultimo numero di protocollo                                                           // controllando sia l'archivio documenti che sul
                global $gTables;                                      // registro IVA passato come variabile (default acquisti)
                $rs_ultimo_tesdoc = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datemi) = $year AND tipdoc LIKE '$type' AND tipdoc <> 'ADT' AND seziva = $sezione", "protoc DESC", 0, 1);
                $ultimo_tesdoc = gaz_dbi_fetch_array($rs_ultimo_tesdoc);
                $rs_ultimo_tesmov = gaz_dbi_dyn_query("*", $gTables['tesmov'], "YEAR(datreg) = $year AND regiva = $registro AND seziva = $sezione", "protoc DESC", 0, 1);
                $ultimo_tesmov = gaz_dbi_fetch_array($rs_ultimo_tesmov);
                $lastProtocol = 0;
                if ($ultimo_tesdoc) {
                    $lastProtocol = $ultimo_tesdoc['protoc'];
                }
                if ($ultimo_tesmov) {
                    if ($ultimo_tesmov['protoc'] > $lastProtocol) {
                        $lastProtocol = $ultimo_tesmov['protoc'];
                    }
                }
                return $lastProtocol + 1;
            }

            $causa = gaz_dbi_get_row($gTables['caucon'], 'codice', $form['codcausale']);
            if ($causa['regiva'] > 0) { // trovo l'ultimo numero di protocollo e di documento
                $form['protocollo'] = getLastNumber(substr($form['codcausale'], 0, 1) . '__', $form['date_reg_Y'], $form['sezioneiva'], $causa['regiva']);
                if ($causa['regiva'] <= 5) { // il numero di documento solo se è di vendita
                    $form['numdocumen'] = getLastNumber($form['codcausale'], $form['date_reg_Y'], $form['sezioneiva'], $causa['regiva']);
                }
            }
            if ($causa['regiva'] == 0 and $_POST['registroiva'] > 0) {//se la nuova causale non prevede righi IVA mentre la precedente lo prevedeva, elimino i righi
                for ($i = $_POST['rigiva'] - 1; $i >= 0; $i--) { //qui cancello tutti i movimenti IVA
                    array_splice($form['id_rig_ri'], $i, 1);
                    array_splice($form['codiva_ri'], $i, 1);
                    array_splice($form['imponi_ri'], $i, 1);
                    array_splice($form['impost_ri'], $i, 1);
                }
            } elseif ($causa['regiva'] == 9) { // pagamento IVA a debito
			    $_POST['rigiva']=1;
                $form['imponi_ri'][0]=0.00;
                $form['impost_ri'][0]=0.00;
                $form['id_rig_ri'][0]=0;
                $form['codiva_ri'][0]=0;
                $form['reverse_charge_ri'][0]='';
                $form['operation_type_ri'][0]='';
				$dr = $form['date_reg_Y'].'-'.$form['date_reg_M'].'-'.$form['date_reg_D'];
				$dl = new DateTime($dr);
				$dl->modify('last day of previous month');
                $form['datliq']=$dl->format('d-m-Y');
                $form['datdoc']=$form['date_reg_D'].'-'.$form['date_reg_M'].'-'.$form['date_reg_Y'];
            }elseif($causa['regiva']>0 && $_POST['registroiva'] > 0) {
				//se la nuova causale prevede righi IVA come la precedente li riuso per caricarci le nuove
                //calcolo il totale dell'imponibile e dell'iva postati
                $imponi = 0;
                $impost = 0;
                for ($i = 0; $i < $_POST['rigiva']; $i++) {
                    $imponi += $form['imponi_ri'][$i];
                    $impost += $form['impost_ri'][$i];
                }
                $newRow = 0;
                for ($i = 1; $i <= 6; $i++) { //se ce ne sono, carico le contropartite
                    switch ($causa["tipim$i"]) {
                        case "A": //totale
                            $nuovo_importo[$newRow] = $imponi + $impost;
                            break;
                        case "B": //imponibile
                            $nuovo_importo[$newRow] = $imponi;
                            break;
                        case "C": //iva
                            $nuovo_importo[$newRow] = $impost;
                            break;
                    }
                    $newRow++;
                }
            }
            $form['descrizion'] = $causa['descri'];
            $form['inserimdoc'] = $causa['insdoc'];
            $form['registroiva'] = $causa['regiva'];
            $form['operatore'] = $causa['operat'];
            $newRow = 0;
            $rs_caucon_rows = gaz_dbi_dyn_query("*", $gTables['caucon_rows'], "caucon_cod = '" . $form['codcausale']."'", "n_order");
            while ($caucon_rows = gaz_dbi_fetch_array($rs_caucon_rows)) { //se ce ne sono, carico le contropartite
                if ($caucon_rows["clfoco_ref"] > 100000000) { // ho una contropartita da proporre
                    if (!isset($form['id_rig_rc'][$newRow])) { //se e' un rigo inesistente
                        $form['id_rig_rc'][$newRow] = 'NUOVO';
                    }
                    if (substr($caucon_rows["clfoco_ref"], 3, 6) == 0) {
                        if (substr($caucon_rows["clfoco_ref"], 0, 3) == substr($form['cod_partner'], 0, 3)) {
                            $form['conto_rc' . $newRow] = $form['cod_partner'];
                            ;
                        } else {
                            $form['conto_rc' . $newRow] = 0;
                        }
                    } else {
                        $form['conto_rc' . $newRow] = $caucon_rows["clfoco_ref"];
                    }
                    $form['mastro_rc'][$newRow] = substr($caucon_rows["clfoco_ref"], 0, 3) . "000000";
                    $form['search']['conto_rc' . $newRow] = '';
                    $form['darave_rc'][$newRow] = $caucon_rows["dare_avere"];
                    if (isset($nuovo_importo[$newRow])) {
                        $form['importorc'][$newRow] = $nuovo_importo[$newRow];
                    } else {
                        $form['importorc'][$newRow] = 0;
                    }
                    $form['paymov_op_cl'][$newRow] = 0; // serve in caso di partita aperta
                    $newRow++;
                }
            }
            //qui cancello tutti gli eventuali successivi movimenti contabili
            for ($i = $_POST['rigcon'] - 1; $i >= $newRow; $i--) { //se ce ne sono, carico le contropartite
                array_splice($form['id_rig_rc'], $i, 1);
                array_splice($form['mastro_rc'], $i, 1);
                unset($form['conto_rc' . $i]);
                array_splice($form['darave_rc'], $i, 1);
                array_splice($form['importorc'], $i, 1);
            }
            $_POST['rigcon'] = $newRow;
        }
    }
    if (isset($_POST['add'])) {
        $rigo = $_POST['rigcon'];
        $form['id_rig_rc'][$rigo] = "";
        $form['mastro_rc'][$rigo] = intval($_POST['insert_mastro']);
        $form['conto_rc'.$rigo] = substr($_POST['insert_conto'], 0, 12);
        // ripulisco il sottoconto usato
        $form['insert_conto'] = 0;
        $form['search']['insert_conto'] = '';
        $form['search']['conto_rc' . $rigo] = '';
        $form['darave_rc'][$rigo] = $_POST['insert_darave'];
        $form['importorc'][$rigo] = preg_replace("/\,/", '.', $_POST['insert_import']);
        $form['paymov_op_cl'][$rigo] = 0;
        // se è un partner permetto l'input del dialog-schedule
        if ($form['mastro_rc'][$rigo] == $mastroclienti || $form['mastro_rc'][$rigo] == $mastrofornitori) {
            if (($form['mastro_rc'][$rigo] == $mastroclienti && $form['darave_rc'][$rigo] == 'D') || ($form['mastro_rc'][$rigo] == $mastrofornitori && $form['darave_rc'][$rigo] == 'A')) { // è un rigo di documento o addebito (apertura partita)
                $form['paymov_op_cl'][$rigo] = 1;
            } else {                            // è un rigo di pagamento o storno (chiusura partita)
                $form['paymov_op_cl'][$rigo] = 2;
            }
            $form['paymov'][$rigo]['new'] = array('id' => 'new', 'id_tesdoc_ref' => 'new', 'amount' => '0.00', 'expiry' => '');
        }
        $_POST['rigcon'] ++;
    }

    // Se viene inviata la richiesta di eliminazione, elimina il rigo contabile
    if (isset($_POST['del'])) {
        $delri = key($_POST['del']);
        array_splice($form['id_rig_rc'], $delri, 1);
        array_splice($form['mastro_rc'], $delri, 1);
        for ($i = $delri; $i < $_POST['rigcon'] - 1; $i++) {
            $form['conto_rc' . $i] = $form['conto_rc' . ($i + 1)];
        }
        unset($form['conto_rc' . ($i + 1)]);
        array_splice($form['darave_rc'], $delri, 1);
        array_splice($form['importorc'], $delri, 1);
        $_POST['rigcon'] --;
    }

    // Se viene cambiato il mastro sul rigo di input azzero il sottoconto
    if ($form['hidden_req']=='insert_mastro') {
      $form['insert_conto'] = 0;
    }

    // Se viene inviata la richiesta di aggiunta, aggiunge un rigo iva
    if (isset($_POST['insert_imponi'])) {
        $_POST['insert_imponi'] = preg_replace("/\,/", '.', $_POST['insert_imponi']);
    }
    if (isset($_POST['adi']) && $_POST['insert_imponi'] <> 0) {
        if ($_POST['insert_codiva'] > 0) {
            $causa = gaz_dbi_get_row($gTables['caucon'], "codice", $form['codcausale']);
            $riiv = $_POST['rigiva'];
            $form['id_rig_ri'][$riiv] = "";
            $form['codiva_ri'][$riiv] = $_POST['insert_codiva'];
            $form['operation_type_ri'][$riiv] = $_POST['operation_type'];
            $form['reverse_charge_ri'][$riiv] = '';
            $ivarigo = gaz_dbi_get_row($gTables['aliiva'], "codice", $_POST['insert_codiva']);
            // se il nuovo rigo prevede un tipo di iva per il reverse charge (natura fattura elettronica=N6) lo indico sull'apposita variabile
            if (intval($form['reverse_charge']) == 0 && substr($ivarigo['fae_natura'],0,2) == 'N6') {
                $form['reverse_charge'] = $ivarigo['fae_natura'];
                $form['reverse_charge_ri'][$riiv] = $ivarigo['fae_natura'];
            }
            // riporterò il tipo operazione al giusto campo
            if ($ivarigo['operation_type'] != '' && $_POST['operation_type'] == '') {
                $form['operation_type_ri'][$riiv] = $ivarigo['operation_type'];
            }
            if ($form['registroiva'] == 4) { //se è un corrispettivo faccio lo scorporo
                $form['imponi_ri'][$riiv] = number_format(round(preg_replace("/\,/", '.', $_POST['insert_imponi']) / (100 + $ivarigo['aliquo']) * 10000) / 100, 2, '.', '');
                $form['impost_ri'][$riiv] = number_format(preg_replace("/\,/", '.', $_POST['insert_imponi']) - $form['imponi_ri'][$riiv], 2, '.', '');
            } else { //altrimenti calcolo solo l'iva
                $form['imponi_ri'][$riiv] = number_format(preg_replace("/\,/", '.', $_POST['insert_imponi']), 2, '.', '');
                $form['impost_ri'][$riiv] = number_format(round($form['imponi_ri'][$riiv] * $ivarigo['aliquo']) / 100, 2, '.', '');
            }
            //ricalcolo il totale dell'imponibile e dell'iva postati
            $imponi = 0;
            $impost = 0;
            for ($i = 0; $i <= $_POST['rigiva']; $i++) {
                $imponi += $form['imponi_ri'][$i];
                $impost += $form['impost_ri'][$i];
            } //fine calcolo

            for ($rc = 0; $rc < $_POST['rigcon']; $rc++) { //mi ripasso le contropartite inserite e ci introduco l'eventuale giusto valore
				$rs_caucon_rows = gaz_dbi_dyn_query("*", $gTables['caucon_rows'], "caucon_cod = '" . $form['codcausale']."'", "n_order");
				while ($caucon_rows = gaz_dbi_fetch_array($rs_caucon_rows)) { //contropartite in causale
                    if ($caucon_rows["clfoco_ref"] == $form['conto_rc' . $rc] || ( substr($caucon_rows["clfoco_ref"], 3, 6) == 0 and substr($form['mastro_rc'][$rc], 0, 3) == substr($caucon_rows["clfoco_ref"], 0, 3))) {
                        switch ($caucon_rows["type_imp"]) {
                            case "A": //totale
                                $form['importorc'][$rc] = $imponi + $impost;
                                break;
                            case "B": //imponibile
                                $form['importorc'][$rc] = $imponi;
                                break;
                            case "C": //iva
                                $form['importorc'][$rc] = $impost;
                                break;
                        }
                    }
                }
            }
            $form['insert_imponi'] = 0;
            $_POST['rigiva'] ++;
        }
    }
    // Se viene inviata la richiesta di eliminazione, elimina il rigo iva
    if (isset($_POST['dei'])) {
      $delri = key($_POST['dei']);
      $cod = array_splice($form['codiva_ri'], $delri, 1);
      array_splice($form['imponi_ri'], $delri, 1);
      array_splice($form['impost_ri'], $delri, 1);
      array_splice($form['operation_type_ri'], $delri, 1);
      $_POST['rigiva'] --;
      if (intval($form['reverse_charge']) >= 1) {
          // se sto eliminando un rigo che aveva già generato un movimento in registro vendite lo dovrò eliminare
          $form['reverse_charge'] = 'del';
      } elseif (substr($form['reverse_charge'],0,2) == 'N6') {
          // se sto eliminando un rigo che NON aveva  generato un movimento in registro vendite mi basta deselez
          $ivarigo = gaz_dbi_get_row($gTables['aliiva'], "codice", $cod[0]);
          if (substr($ivarigo['fae_natura'],0,2) == 'N6') {
              $form['reverse_charge'] = '';
          }
      }
    }

    // Se viene inviata la richiesta di bilanciamento dei righi contabili aggiungo il valore pasato al primo rigo (rudimentale)  si potrebbe fare meglio e in modo più intelligente, ma non ho tempo...
    if (isset($_POST['balb'])) {
      $bb = floatval($_POST['diffV']);
      if ($bb > 0) { //eccesso in dare
        $key = array_search('A', $form['darave_rc']);
        if ($key || $key === 0) {
            $form['importorc'][$key] += $bb;
        }
      } else {        //eccesso in avere
        $key = array_search('D', $form['darave_rc']);
        if ($key || $key === 0) {
            $form['importorc'][$key] -= $bb;
        }
      }
    }

    // Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {
      $ctrl_tot_D = 0.00;
      $ctrl_tot_A = 0.00;
      $ctrl_mov_iva = 0.00;
      $ctrl_bal = 0.00;
      $ctrl_ritenute = 0.00; // per aggiungere le ritenute al valore cliente/fornitore e fare il controllo
      $ctrl_mov_con = 0.00;
      $acc_partner_mov = array();
      $fattura_allegata = false;
      $datareg = $_POST['date_reg_Y'] . "-" . $_POST['date_reg_M'] . "-" . $_POST['date_reg_D'];
      $ctrldatreg = new DateTime($datareg);
      $business_date_cessation = gaz_dbi_get_row($gTables['company_config'], 'var', 'business_date_cessation')['val'];
      if (strlen($business_date_cessation)==10){ // in configurazione avanzata azienda
        $cessation = new DateTime(gaz_format_date($business_date_cessation,true));
        if ($ctrldatreg > $cessation){ // in configurazione azienda ho settato l'ultimo giorno di operatività dell'azienda
         $msg .= "17+";
        }
      }

      //calcolo i totali dare e avere per poter eseguire il controllo
      for ($i = 0; $i < $_POST['rigcon']; $i++) {
        $_POST['importorc'][$i] = preg_replace("/\,/", '.', $_POST['importorc'][$i]);
        $nr = $i + 1;
        if (substr($_POST['conto_rc' . $i], 3, 6) < 1) { //controllo che tutti i conti siano stati introdotti...
            $msg .= "0+";
        }
        if ($_POST['importorc'][$i] == 0) { //controllo che non ci siamo valori a 0
            $msg .= "1+";
        }
        if ($_POST['registroiva'] == 4){ // il movimento riguarda il registro  IVA corrispettivi
          if (substr($_POST['conto_rc' . $i], 0, 3) == $admin_aziend['mascli'] || substr($_POST['conto_rc' . $i], 0, 3) == $admin_aziend['masfor'] || (preg_match("/^id_([0-9]+)$/", $_POST['conto_rc' . $i], $match))) { // in caso di scontrino intestato faccio il push al valore massimo
            $fattura_allegata = true;
            if ($ctrl_mov_con <= $_POST['importorc'][$i]) {
              $ctrl_mov_con = number_format($_POST['importorc'][$i], 2, '.', '');
            }
          } elseif ((substr($_POST['conto_rc' . $i], 0, 3) == $admin_aziend['masban'] || substr($_POST['conto_rc' . $i], 0, 3) == substr($admin_aziend['cassa_'], 0, 3)) && $fattura_allegata == false ) {
                      $ctrl_mov_con += number_format($_POST['importorc'][$i], 2, '.', '');
          }
        } elseif (substr($_POST['conto_rc' . $i], 0, 3) == $admin_aziend['mascli'] || substr($_POST['conto_rc' . $i], 0, 3) == $admin_aziend['masfor'] || (preg_match("/^id_([0-9]+)$/", $_POST['conto_rc' . $i], $match))) {
          // ... ed anche in caso di cliente/fornitore eseguo il push del valore massimo
          if ($ctrl_mov_con <= $_POST['importorc'][$i]) {
              $ctrl_mov_con = number_format($_POST['importorc'][$i], 2, '.', '');
          }
        }
        if ($_POST['conto_rc' . $i] == $admin_aziend['c_ritenute'] || $_POST['conto_rc' . $i] == $admin_aziend['c_ritenute_autonomi']) {
          $ctrl_ritenute +=$_POST['importorc'][$i];
        }
        if ($_POST['darave_rc'][$i] == "D") {
            $ctrl_tot_D += $_POST['importorc'][$i];
        } else {
            $ctrl_tot_A += $_POST['importorc'][$i];
        }
        $ctrl_bal = round($ctrl_tot_D - $ctrl_tot_A, 2);
      }
      //calcolo i totali iva per poter eseguire il controllo
      if (!isset($_POST['rigiva'])) {
          $_POST['rigiva'] = 0;
      }
      for ($i = 0; $i < $_POST['rigiva']; $i++) {
          $_POST['imponi_ri'][$i] = number_format(preg_replace("/\,/", '.', $_POST['imponi_ri'][$i]),2,'.','');
          $_POST['impost_ri'][$i] = number_format(preg_replace("/\,/", '.', $_POST['impost_ri'][$i]),2,'.','');
          $ctrl_mov_iva += $_POST['imponi_ri'][$i] + $_POST['impost_ri'][$i];
      }
      $ctrl_mov_iva = round(abs($ctrl_mov_iva), 2);
      if ($ctrl_bal != 0) {
          $msg .= "2+";
      }
      if ($ctrl_tot_D == 0) {
          $msg .= "3+";
      }
      if ($ctrl_tot_A == 0) {
          $msg .= "4+";
      }
      $ctrl_mov_con += $ctrl_ritenute;
      if (abs($ctrl_mov_con)>=0.01){ // controlli solo in caso di totale imputabile a fornitore/cliente
        if ($_POST['registroiva'] > 0 && $ctrl_mov_iva == 0) {
            $msg .= "5+";
        }
        if ($_POST['registroiva'] > 0 && !((abs($ctrl_mov_iva-$ctrl_mov_con)/$ctrl_mov_con) < 0.00001)) {
          print $ctrl_mov_iva . ' ' . $ctrl_mov_con . '<br><hr>';
          $msg .= "6+";
        }
      }
      if (empty($_POST['descrizion'])) {
          $msg .= "7+";
      }
      //controllo le date
      if (!checkdate($_POST['date_reg_M'], $_POST['date_reg_D'], $_POST['date_reg_Y']))
          $msg .= "8+";
        //controllo che siano stati inseriti in maniera giusta i dati del documento
        if ($_POST['inserimdoc'] > 0) {
            if (!gaz_format_date($form['datdoc'], 'chk')) {
                $msg .= "9+";
            }
            if ($_POST['protocollo'] <= 0) {
                $msg .= "10+";
            }
            if (empty($_POST['numdocumen'])) {
                $msg .= "11+";
            }
            $ctrldatreg = mktime(0, 0, 0, $_POST['date_reg_M'], $_POST['date_reg_D'], $_POST['date_reg_Y']);
            $ctrldatdoc = gaz_format_date($form['datdoc'], 2);
            if ($ctrldatreg < $ctrldatdoc) {
                $msg .= "12+";
            }
            // controllo se ci documenti con lo stesso numero e anno dello stesso cliente/fornitore (duplicato) tranne che per gli scontrini
            if ($_POST['cod_partner'] > 0 && $_POST['codcausale']!='VCO') {
                $dupli = gaz_dbi_record_count($gTables['tesmov'], "caucon = '" . substr($_POST['codcausale'], 0, 3) . "' AND numdoc = '" . trim(substr($_POST['numdocumen'], 0, 20)) . "' AND seziva = " . intval($_POST['sezioneiva']) . " AND clfoco = " . intval($_POST['cod_partner']) . " AND YEAR(datdoc) = " . intval(substr($_POST['datdoc'], -4)));
                if ($dupli > 1 || ($dupli == 1 && $toDo == 'insert')) {
                    $msg .= "14+";
                }
            }
        }

        if ($msg == "") { // nessun errore
            require("../../library/include/check.inc.php");
            $calc = new Schedule;
            //se è un update recupero i vecchi righi per trovare quelli da inserire/modificare/cancellare
            //formatto le date
            $datadoc = gaz_format_date($form['datdoc'], true);
            $dataliq = gaz_format_date($form['datliq'], true);
            if ($_POST['inserimdoc'] == 0 and $_POST['registroiva'] == 0) { //se non sono richisti i dati documenti e iva
                $_POST['sezioneiva'] = 0;
                $_POST['protocollo'] = 0;
                $_POST['numdocumen'] = "";
                $datadoc = 0;
            }
            if ($toDo == 'update') {  //se è una modifica
                // MODIFICO I RIGHI CONTABILI
                $vecchi_righcon = gaz_dbi_dyn_query("*", $gTables['rigmoc'], "id_tes = '" . intval($_POST['id_testata']) . "'", "id_rig asc");
                $i = 0;
                $count = count($_POST['id_rig_rc']) - 1;
                while ($row_con = gaz_dbi_fetch_array($vecchi_righcon)) {
                    if ($i <= $count) { //se l'id del vecchio rigo e' ancora presente nel nuovo lo modifico
                        $account_new = intval($_POST['conto_rc' . $i]);
                        if (preg_match("/^id_([0-9]+)$/", substr($_POST['conto_rc' . $i], 0, 12), $match)) { // E' un partner da inserire sul piano dei conti
                            $new_clfoco = $anagrafica->getPartnerData($match[1], 1);
                            $account_new = $anagrafica->anagra_to_clfoco($new_clfoco, substr($_POST['mastro_rc'][$i], 0, 3),$form['pagame']);
                        }
                        rigmocUpdate(array('id_rig', $row_con['id_rig']), array('id_tes' => intval($_POST['id_testata']), 'darave' => substr($_POST['darave_rc'][$i], 0, 1), 'codcon' => $account_new, 'import' => floatval($_POST['importorc'][$i])));
                        // questa era troppo lenta nelle macchine molto lente
                        //gaz_dbi_table_update('rigmoc',array('id_rig',$row_con['id_rig']),array('id_tes'=>intval($_POST['id_testata']),'darave'=>substr($_POST['darave_rc'][$i],0,1),'codcon'=>$account_new,'import'=>floatval($_POST['importorc'][$i])));
                        // MODIFICO PURE I RELATIVI MOVIMENTI DI PARTITE APERTE (in paymov)
                        $calc->setRigmocEntries($row_con['id_rig']);
                        $count_oldpaymov = count($calc->RigmocEntries);
                        if (isset($form['paymov'][$i])) {
                            // HO DELLE PARTITE POSTATE SU QUESTO RIGO
                            $new_paymov = array_values($form['paymov'][$i]);
                            $count_newpaymov = count($new_paymov);
                            if ($count_oldpaymov > 0) { // ...e se prima li avevo anche : li devo aggiornare
                                $j = 0;
                                foreach ($calc->RigmocEntries as $v) { // attraverso il vecchio array
                                    if ($j <= ($count_newpaymov - 1)) { //  se non è un rigo eccedente lo modifico mantenendo il vecchio indice
                                        if ($form['paymov_op_cl'][$i] == 1) { // apertura partita
                                            $new_paymov[$j]['id_rigmoc_doc'] = $row_con['id_rig'];
                                            $new_paymov[$j]['id_rigmoc_pay'] = 0;
                                        } else {  // chiusura partita
                                            $new_paymov[$j]['id_rigmoc_doc'] = 0;
                                            $new_paymov[$j]['id_rigmoc_pay'] = $row_con['id_rig'];
                                        }
                                        $new_paymov[$j]['expiry'] = gaz_format_date($new_paymov[$j]['expiry'], true);
                                        $calc->updatePaymov($new_paymov[$j]);
                                    } else {  // altrimenti lo elimino ma passando il SOLO id
                                        $calc->updatePaymov(array('id_del' => $v['id']));
                                    }
                                    $j++;
                                }
                                // se i nuovi righi paymov eccedono i vecchi li inserisco
                                for ($j = $j; $j < $count_newpaymov; $j++) { // attraverso l'eccedenza dei nuovi righi
                                    if ($v['amount'] >= 0.01) { // ma solo se è stato valorizzato
                                        if ($new_paymov[$j]['id'] == 'new') { // nuovo rigo
                                            unset($new_paymov[$j]['id']);
                                        }
                                        if ($form['paymov_op_cl'][$i] == 1) { // apertura partita
                                            $new_paymov[$j]['id_rigmoc_doc'] = $row_con['id_rig'];
                                        } else {  // chiusura partita
                                            $new_paymov[$j]['id_rigmoc_pay'] = $row_con['id_rig'];
                                        }
                                        $new_paymov[$j]['expiry'] = gaz_format_date($new_paymov[$j]['expiry'], true);
                                        $calc->updatePaymov($new_paymov[$j]);
                                    }
                                }
                            } else { // prima non li avevo quindi adesso devo introdurre TUTTI I NUOVI
                                foreach ($new_paymov as $k => $v) { // attraverso il nuovo array
                                    $j = $k;
                                    if ($v['amount'] >= 0.01) { // nuovo rigo solo se è stato valorizzato
                                        if ($v['id'] == 'new') { // nuovo rigo
                                            unset($new_paymov[$j]['id']);
                                            if ($form['registroiva'] == 0) {
                                                $y_paymov = $form['date_reg_Y'];
                                                $num_paymov = $row_con['id_rig']; // in caso di mancanza di riferimento al documento metto quello del rigo contabile
                                            } else {
                                                $y_paymov = substr($form['date_reg_Y'], -4);
                                                $num_paymov = intval($_POST['protocollo']);
                                            }
                                        }
                                        if (intval(substr($v['id_tesdoc_ref'], 0, 4)) <= 2000) {
                                            $new_paymov[$j]['id_tesdoc_ref'] = $y_paymov . $form['registroiva'] . $form['sezioneiva'] . str_pad($num_paymov, 9, 0, STR_PAD_LEFT);
                                        }
                                        if ($form['paymov_op_cl'][$i] == 1) { // apertura partita
                                            $new_paymov[$j]['id_rigmoc_doc'] = $row_con['id_rig'];
                                        } else {  // chiusura partita
                                            $new_paymov[$j]['id_rigmoc_pay'] = $row_con['id_rig'];
                                        }
                                        $new_paymov[$j]['expiry'] = gaz_format_date($new_paymov[$j]['expiry'], true);
                                        $calc->updatePaymov($new_paymov[$j]);
                                    }
                                }
                            }
                        } else {
                            // NON HO PARTITE POSTATE SU QUESTO RIGO
                            if ($count_oldpaymov > 0 && $form['pay_closure'] <= 0) {
                                // ...e se prima li avevo: li devo eliminare  TUTTI
                                // ma solo se il pagamento non prevede una lo deselezione automatica
                                foreach ($calc->RigmocEntries as $v) { // attraverso il vecchio array
                                    $calc->updatePaymov(array('id_del' => $v['id']));
                                }
                            }
                        }
                        // se su questo rigo ci sono rimasti
                    } else { //altrimenti elimino i righi e le relative partite
                        gaz_dbi_del_row($gTables['rigmoc'], "id_rig", $row_con['id_rig']);
                        // ...elimino pure eventuali relativi movimenti di partite aperte
                        $calc->updatePaymov($row_con['id_rig']);
                    }
                    $i++;
                }
                //qualora i nuovi righi fossero di più dei vecchi inserisco l'eccedenza
                for ($i = $i; $i <= $count; $i++) {
                    if (preg_match("/^id_([0-9]+)$/", substr($_POST['conto_rc' . $i], 0, 12), $match)) { // è un partner da inserire sul piano dei conti
                        $new_clfoco = $anagrafica->getPartnerData($match[1], 1);
                        $_POST['conto_rc' . $i] = $anagrafica->anagra_to_clfoco($new_clfoco, substr($_POST['mastro_rc'][$i], 0, 3),$form['pagame']);
                    }
                    rigmocInsert(array('id_tes' => intval($_POST['id_testata']), 'darave' => substr($_POST['darave_rc'][$i], 0, 1), 'codcon' => intval($_POST['conto_rc' . $i]), 'import' => floatval($_POST['importorc'][$i])));
                    $last_id_rig = gaz_dbi_last_id();
                    // INSERISCO PURE LE EVENTUALI PARTITE APERTE
                    if (isset($form['paymov'][$i])) {
                        $new_paymov = array_values($form['paymov'][$i]);
                        foreach ($new_paymov as $k => $v) { // attraverso il nuovo array
                            $j = $k;
                            if ($v['id'] == 'new') { // nuovo rigo
                                unset($new_paymov[$j]['id']);
                                if ($form['registroiva'] == 0) {
                                    $y_paymov = $form['date_reg_Y'];
                                    $num_paymov = $last_id_rig; // in caso di mancanza di riferimento al documento metto quello del rigo contabile
                                } else {
                                    $y_paymov = $form['date_reg_Y'];
                                    $num_paymov = intval($_POST['protocollo']);
                                }
                                if (intval(substr($v['id_tesdoc_ref'], 0, 4)) <= 2000) {
                                    $new_paymov[$j]['id_tesdoc_ref'] = $y_paymov . $form['registroiva'] . $form['sezioneiva'] . str_pad($num_paymov, 9, 0, STR_PAD_LEFT);
                                }
                            }
                            if ($form['paymov_op_cl'][$i] == 1) { // apertura partita
                                $new_paymov[$j]['id_rigmoc_doc'] = $last_id_rig;
                            } else {  // chiusura partita
                                $new_paymov[$j]['id_rigmoc_pay'] = $last_id_rig;
                            }
                            $new_paymov[$j]['expiry'] = gaz_format_date($new_paymov[$j]['expiry'], true);
                            $calc->updatePaymov($new_paymov[$j]);
                        }
                    }
                }

                // MODIFICO I RIGHI IVA
                $vecchi_righiva = gaz_dbi_dyn_query("*", $gTables['rigmoi'], "id_tes = '" . intval($_POST['id_testata']) . "'", "id_rig asc");
                $i = 0;
                if ($_POST['registroiva'] > 0) {
                    $count = count($_POST['id_rig_ri']) - 1;
                } else {
                    $count = 0;
                    $i = 1;
                }
                while ($row_iva = gaz_dbi_fetch_array($vecchi_righiva)) {
                    //se l'id del vecchio rigo e' ancora presente nel nuovo lo modifico
                    if ($i <= $count) {
                        //recupero i dati dell'aliquota iva
                        $vv = gaz_dbi_get_row($gTables['aliiva'], 'codice', intval($_POST['codiva_ri'][$i]));
                        //aggiungo i valori mancanti all'array
                        $vv['codiva'] = $vv['codice'];
                        $vv['id_tes'] = intval($_POST['id_testata']);
                        $vv['periva'] = $vv['aliquo'];
                        $vv['imponi'] = floatval($_POST['imponi_ri'][$i]);
                        $vv['impost'] = floatval($_POST['impost_ri'][$i]);
                        $vv['reverse_charge_idtes'] = intval($_POST['reverse_charge_ri'][$i]);
                        if ($i==0 && $vv['reverse_charge_idtes'] == 0 && $row_iva['reverse_charge_idtes'] >= 1 ) { // eseguo un controllo per non perdere il riferimento incrociato al vecchio  "reverse_charge_idtes" nel caso l'utente abbia eliminato il rigo IVA e poi riaggiunto allora riprendo il vecchio
                          $vv['reverse_charge_idtes'] = $row_iva['reverse_charge_idtes'];
                        }
                        $vv['operation_type'] = substr($_POST['operation_type_ri'][$i], 0, 15);
                        if ($form['registroiva']==9){$vv['tipiva']='V';}
                        gaz_dbi_table_update('rigmoi', array('id_rig', $row_iva['id_rig']), $vv);
             						if ($i==0) { // sul primo rigo IVA inserisco un documento fittizio in tesdoc al fine di generare un XML dal registro con il sezionale (normalmente 9) del Reverse Charge
                          // stabilisco il tipo di documento per lo SdI (TD16,TD17,TD18,TD19,TD20) e lo insterisco sulla colonna status di tesdoc
                          $status='TD16'; // operazioni interne (italiani)
                          if ($partnersel['country']<>'IT') {
                            $istat_area = gaz_dbi_get_row($gTables['country'], "iso", $partnersel['country'])['istat_area'];
                            $status='TD17'; // acquisto servizi dall'estero
                            if ($vv['operation_type']<>'SERVIZ'&& $istat_area==11) {
                              $status='TD18';
                            }
                            // se il fornitore ha una partita IVA italiana pur essendo straniero diventa TD19
                            $cf_pi = new check_VATno_TAXcode();
                            $r_pi = $cf_pi->check_VAT_reg_no($partnersel['pariva'], 'IT');
                            if (empty($r_pi)) {
                              $status='TD19';
                            }
                          }
                          // adesso faccio l'update di tesdoc con tipdoc XFA portando all'eventuale nuovo valore di status
                          gaz_dbi_query("UPDATE " . $gTables['tesdoc'] . " SET status = '" . $status . "' WHERE `id_con` = ". $vv['reverse_charge_idtes']." AND `tipdoc` LIKE 'X%'");
                        }
                    } else { //altrimenti lo elimino
                        gaz_dbi_del_row($gTables['rigmoi'], "id_rig", $row_iva['id_rig']);
                    }
                    $i++;
                }
                //qualora i nuovi righi iva fossero di più dei vecchi inserisco l'eccedenza
                for ($i = $i; $i <= $count; $i++) {
                    $vv = gaz_dbi_get_row($gTables['aliiva'], 'codice', intval($_POST['codiva_ri'][$i]));
                    //aggiungo i valori mancanti all'array
                    $vv['codiva'] = $vv['codice'];
                    $vv['id_tes'] = intval($_POST['id_testata']);
                    $vv['periva'] = $vv['aliquo'];
                    $vv['imponi'] = floatval($_POST['imponi_ri'][$i]);
                    $vv['impost'] = floatval($_POST['impost_ri'][$i]);
                    $vv['reverse_charge_idtes'] = intval($_POST['reverse_charge_ri'][$i]);
                    $vv['operation_type'] = substr($_POST['operation_type_ri'][$i], 0, 15);
                    if ($form['registroiva']==9){$vv['tipiva']='V';}
                     rigmoiInsert($vv);
                }
                //modifico la testata
                $codice = array('id_tes', intval($_POST['id_testata']));
                $newValue = array('caucon' => substr($_POST['codcausale'], 0, 3),
                    'descri' => substr($_POST['descrizion'], 0, 100),
                    'notess' => $_POST['notess'],
                    'datreg' => $datareg,
                    'datliq' => $dataliq,
                    'seziva' => intval($_POST['sezioneiva']),
                    'protoc' => intval($_POST['protocollo']),
                    'numdoc' => substr($_POST['numdocumen'], 0, 20),
                    'datdoc' => $datadoc,
                    'clfoco' => intval($_POST['cod_partner']),
                    'regiva' => substr($_POST['registroiva'], 0, 1),
                    'operat' => intval($_POST['operatore'])
                );
                tesmovUpdate($codice, $newValue);
            } else { //se è un'inserimento
                //inserisco la testata
                $newValue = array('caucon' => substr($_POST['codcausale'], 0, 3),
                    'descri' => substr($_POST['descrizion'], 0, 100),
                    'notess' => $_POST['notess'],
                    'datreg' => $datareg,
                    'datliq' => $dataliq,
                    'seziva' => intval($_POST['sezioneiva']),
                    'protoc' => intval($_POST['protocollo']),
                    'numdoc' => substr($_POST['numdocumen'], 0, 20),
                    'datdoc' => $datadoc,
                    'clfoco' => intval($_POST['cod_partner']),
                    'regiva' => substr($_POST['registroiva'], 0, 1),
                    'operat' => intval($_POST['operatore'])
                );
                // INSERISCO e recupero l'id assegnato
                $ultimo_id = tesmovInsert($newValue);

                //inserisco i righi iva
                for ($i = 0; $i < $_POST['rigiva']; $i++) {
                    $vv = gaz_dbi_get_row($gTables['aliiva'], 'codice', intval($_POST['codiva_ri'][$i]));
                    //aggiungo i valori mancanti all'array
                    $vv['codiva'] = $vv['codice'];
                    $vv['id_tes'] = $ultimo_id;
                    $vv['periva'] = $vv['aliquo'];
                    $vv['imponi'] = floatval($_POST['imponi_ri'][$i]);
                    $vv['impost'] = floatval($_POST['impost_ri'][$i]);
                    $vv['operation_type'] = substr($_POST['operation_type_ri'][$i], 0, 15);
                    if ($form['registroiva']==9){$vv['tipiva']='V';}
                    $reverse_charge_iva = 0;
                    if (substr($form['reverse_charge_ri'][$i],0,2) == 'N6') { // dovrò inserire una testata per il reverse charge
                        // per prima cosa dovrò controllare se c'è il cliente con la stessa anagrafica
                        $partner = $anagrafica->getPartner(intval($_POST['cod_partner']));
                        $rc_cli = gaz_dbi_get_row($gTables['clfoco'], "codice LIKE '" . $admin_aziend['mascli'] . "%' AND id_anagra ", $partner['id']);
                        if ($rc_cli) { // ho già il cliente
                        } else { // non ho il cliente lo dovrò creare sul piano dei conti
                            $new_cli = $anagrafica->getPartnerData($partner['id']);
                            $rc_cli['codice'] = $anagrafica->anagra_to_clfoco($new_cli, $admin_aziend['mascli'],$form['pagame']);
                        }
                        $rc_val = array('caucon' => 'FAI',
                            'descri' => 'FATTURA REVERSE CHARGE',
                            'datreg' => $datareg,
                            'seziva' => $admin_aziend['reverse_charge_sez'],
                            'numdoc' => substr($_POST['numdocumen'], 0, 20),
                            'datdoc' => $datadoc,
                            'datliq' => $dataliq,
                            'clfoco' => $rc_cli['codice'],
                            'regiva' => 2,
                            'operat' => 1
                        );
                        // trovo l'ultimo protocollo della sezione del reverse charge
                        $rs_ultimo_protocollo = gaz_dbi_dyn_query("protoc", $gTables['tesmov'], "YEAR(datreg) = " . substr($datareg, 0, 4) . " AND regiva = 2 AND seziva =" . $admin_aziend['reverse_charge_sez'], "protoc DESC", 0, 1);
                        $ultimo_protocollo = gaz_dbi_fetch_array($rs_ultimo_protocollo);
                        // se e' il primo protocollo dell'anno, resetto il contatore
                        if ($ultimo_protocollo) {
                            $rc_val['protoc'] = $ultimo_protocollo['protoc'] + 1;
                        } else {
                            $rc_val['protoc'] = 1;
                        }
                        // inserisco la testata e recupero l'id assegnato
                        $rc_lastid = tesmovInsert($rc_val);

                        // vado ad indicare l'id sul rigo iva
                        $vv['reverse_charge_idtes'] = $rc_lastid;

                        // inserisco il rigo IVA N6
                        $rcv = gaz_dbi_get_row($gTables['aliiva'], 'codice', intval($_POST['codiva_ri'][0]));
                        $rcv['codiva'] = $rcv['codice'];
                        $rcv['id_tes'] = $rc_lastid;
                        $rcv['periva'] = $rcv['aliquo'];
                        $rcv['imponi'] = floatval($_POST['imponi_ri'][$i]);
                        $rcv['impost'] = floatval($_POST['impost_ri'][$i]);
                        $rcv['reverse_charge_idtes'] = $ultimo_id;
                        $rcv['operation_type'] = substr($_POST['operation_type_ri'][$i], 0, 15);
                        rigmoiInsert($rcv);
                        // mi servirà per detrarre l'imposta relativa al rigo del reverse charge dall'apertura della partita
                        $reverse_charge_iva += $rcv['impost'];
                        // inserisco i tre righi contabili della fattura che va sul registro IVA vendite
                        rigmocInsert(array('id_tes' => $rc_lastid, 'darave' => 'D', 'codcon' => $rc_cli['codice'], 'import' => $rcv['imponi'] + $rcv['impost']));
                        rigmocInsert(array('id_tes' => $rc_lastid, 'darave' => 'A', 'codcon' => $rc_cli['codice'], 'import' => $rcv['imponi']));
                        rigmocInsert(array('id_tes' => $rc_lastid, 'darave' => 'A', 'codcon' => $admin_aziend['ivaven'], 'import' => $rcv['impost']));

                        // infine creo un movimento di storno dell'IVA
                        rigmocInsert(array('id_tes' => $rc_lastid, 'darave' => 'D', 'codcon' => $newValue['clfoco'], 'import' => $rcv['impost']));
                        rigmocInsert(array('id_tes' => $rc_lastid, 'darave' => 'A', 'codcon' => $rc_cli['codice'], 'import' => $rcv['impost']));

						if ($i==0) { // sul primo rigo IVA inserisco un documento fittizio in tesdoc al fine di generare un XML dal registro con il sezionale (normalmente 9) del Reverse Charge
							// stabilisco il tipo di documento per lo SdI (TD16,TD17,TD18,TD19,TD20) e lo insterisco sulla colonna status di tesdoc
							$status='TD16'; // operazioni interne (italiani)
							if ($partner['country']<>'IT') {
								$istat_area = gaz_dbi_get_row($gTables['country'], "iso", $partner['country'])['istat_area'];
								$status='TD17'; // acquisto servizi dall'estero
                if ($vv['operation_type']<>'SERVIZ' && $istat_area==11 ) { // non è un servizio distinguo se intra o extra
                  $status='TD18';
                }
                // se il fornitore ha una partita IVA italiana pur essendo straniero diventa TD19
                $cf_pi = new check_VATno_TAXcode();
                $r_pi = $cf_pi->check_VAT_reg_no($partner['pariva'], 'IT');
                if (empty($r_pi)) {
                  $status='TD19';
                }
							}
							$tesdocVal = ['tipdoc' => 'XFA',
								'template' => 'FatturaAcquisto',
								'id_con' => $rc_lastid,
								'datreg' => $datareg,
								'seziva' => $admin_aziend['reverse_charge_sez'],
								'protoc' => $rc_val['protoc'],
								'numdoc' => $rc_val['protoc'], // nelle autofatture utilizzo il numero di protocollo del sezionale al fine di avere sequezialità, il numero reale dato dal fornitore è scritto sulla descrizione del rigo
								'numfat' => substr($_POST['numdocumen'], 0, 20),
								'datemi' => $datadoc,
								'datfat' => $datadoc,
								'initra' => $datadoc,
								'clfoco' => intval($_POST['cod_partner']),
								'pagame' => $form['pagame'],
								'regiva' => 2,
								'operat' => 1,
								'status' => $status
							];
							if (substr($_POST['codcausale'], 0, 3) == 'AFC') {
								$tesdocVal['tipdoc'] = 'XNC';
								$tesdocVal['operat'] = 2;
							}
							$last_id_tes_tesdoc=tesdocInsert($tesdocVal);
							$rigdocVal = ['id_tes'=> $last_id_tes_tesdoc,
								'tiprig' => 1,
								'descri' => (substr($_POST['codcausale'], 0, 3)=='AFC')?'NOTA CREDITO PER ':'FATTURA DI '
							];
							$rigdocVal['descri'] .= 'ACQUISTO n.'.substr($_POST['numdocumen'], 0, 20).' del '.gaz_format_date($datadoc);
						}
						// per ogni rigo IVA inserisco un rgo sul documento fittizio del reverse
						// sul documento inserisco un rigo per ogni aliquota riportante il totale imponibile del Reverse Charge
						$rigdocVal['descri'] .= ' '.$rcv['descri'];
						$rigdocVal['codvat'] = $rcv['codiva'];
						$rigdocVal['prelis'] = substr($_POST['codcausale'], 0, 3)=='AFC'?-abs($rcv['imponi']):$rcv['imponi'];
						$rigdocVal['pervat'] = $rcv['periva'];
						rigdocInsert($rigdocVal);

                    }
                    // infine inserisco il relativo rigo iva
                    rigmoiInsert($vv);
                }
                //inserisco i righi contabili
                $last_open_id_tesdoc_ref = 0; // lo userò per inserire una eventuale chiusura
                for ($i = 0; $i < $_POST['rigcon']; $i++) {
                    $account = substr($_POST['conto_rc' . $i], 0, 12);
                    $ad = substr($_POST['darave_rc'][$i], 0, 1);
                    if (preg_match("/^id_([0-9]+)$/", $account, $match)) { // è un partner da inserire sul piano dei conti
                        $new_clfoco = $anagrafica->getPartnerData($match[1], 1);
                        $_POST['conto_rc' . $i] = $anagrafica->anagra_to_clfoco($new_clfoco, substr($_POST['mastro_rc'][$i], 0, 3),$form['pagame']);
                        // modifico la testata precedentemente introdotta per aggiungerci
                        gaz_dbi_table_update('tesmov', array('id_tes', $ultimo_id), array('clfoco' => $_POST['conto_rc' . $i]));
                    }
                    rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => $ad, 'codcon' => intval($_POST['conto_rc' . $i]), 'import' => floatval($_POST['importorc'][$i])));
                    $last_id_rig = gaz_dbi_last_id();
                    // INSERISCO PURE LE EVENTUALI PARTITE APERTE
                    if (isset($form['paymov'][$i]) && $form['pay_closure'] <= 0) { // ma solo se non ho un pagamento contestuale
                        $new_paymov = array_values($form['paymov'][$i]);
                        foreach ($new_paymov as $k => $v) { // attraverso il nuovo array
                            $j = $k;
                            if (isset($v['id'])) { // nuovo rigo
                                unset($new_paymov[$j]['id']);
                            }
                            if ($form['paymov_op_cl'][$i] == 1) { // apertura partita
                                if ($v['id_tesdoc_ref'] > 10000) {  // se ho messo manualmente il riferimento ad una partita
                                    $new_paymov[$j]['id_tesdoc_ref'] = $v['id_tesdoc_ref'];
                                } else {
                                    if ($form['registroiva'] == 0) {
                                        $y_paymov = $form['date_reg_Y'];
                                        $num_paymov = $last_id_rig; // in caso di mancanza di riferimento al documento metto quello del rigo contabile
                                    } else {
                                        $y_paymov = $form['date_reg_Y'];
                                        $num_paymov = intval($_POST['protocollo']);
                                    }
                                    $new_paymov[$j]['id_tesdoc_ref'] = $y_paymov .
                                            intval($_POST['registroiva']) .
                                            intval($_POST['sezioneiva']) .
                                            str_pad($num_paymov, 9, 0, STR_PAD_LEFT);
                                }
                                $new_paymov[$j]['id_rigmoc_doc'] = $last_id_rig;
                                if ($v['amount'] < 0.01) {  // se non ho messo manualmente le scadenze lo faccio in automatico
                                    require_once("../../library/include/expiry_calc.php");
                                    $ex = new Expiry;
                                    $partner = $anagrafica->getPartner(intval($_POST['conto_rc' . $i]));
                                    $pag = gaz_dbi_get_row($gTables['pagame'], "codice", $form['pagame']);
                                    if ($datadoc == 0) {
                                        $datadoc = $datareg;
                                    }
                                    $rs_ex = $ex->CalcExpiry(round($_POST['importorc'][$i] - $reverse_charge_iva, 2), $datadoc, $pag['tipdec'], $pag['giodec'], $pag['numrat'], $pag['tiprat'], $pag['mesesc'], $pag['giosuc']);
                                    foreach ($rs_ex as $ve) { // attraverso le rate
                                        $new_paymov[$j]['amount'] = $ve['amount'];
                                        $new_paymov[$j]['expiry'] = $ve['date'];
                                        $calc->updatePaymov($new_paymov[$j]);
                                    }
                                } else {
                                    $new_paymov[$j]['expiry'] = gaz_format_date($new_paymov[$j]['expiry'], true);
                                    $calc->updatePaymov($new_paymov[$j]);
                                }
                                // aggiorno il riferimento all'ultima partita aperta, servirà per chiudere con lo stesso se dovessi avere id_tesdoc_ref=new
                                $last_open_id_tesdoc_ref = $new_paymov[$j]['id_tesdoc_ref'];
                            } else {  // chiusura partita
                                if ($new_paymov[$j]['id_tesdoc_ref'] == 'new' && $last_open_id_tesdoc_ref > 1) {
                                    // ho una chiusura partita senza riferimenti (new): se ce l'ho utilizzo quello d'apertura 202161000000001
                                    $new_paymov[$j]['id_tesdoc_ref'] = $last_open_id_tesdoc_ref;
                                } elseif (is_numeric($new_paymov[$j]['id_tesdoc_ref'])&&$new_paymov[$j]['id_tesdoc_ref']>200400000000000) {
									// lascio il riferimento alla partita scelta dall'utente
                                } else {
                                    $new_paymov[$j]['id_tesdoc_ref'] = $form['date_reg_Y'] . str_pad($last_id_rig,11,'0',STR_PAD_LEFT);
								}
                                $new_paymov[$j]['id_rigmoc_pay'] = $last_id_rig;
                                if (!isset($new_paymov[$j]['amount']) || $new_paymov[$j]['amount'] < 0.01) { // se no ho una partita impostata manualmente uso i dati del rigo
                                    $new_paymov[$j]['expiry'] = $newValue['datreg'];
                                    $new_paymov[$j]['amount'] = floatval($_POST['importorc'][$i]);
                                } else {
                                    $new_paymov[$j]['expiry'] = gaz_format_date($new_paymov[$j]['expiry'], true);
                                }
                                $calc->updatePaymov($new_paymov[$j]);
                            }
                        }
                    }
                    // qui controllo se il conto è una immobilizzazione movimentata dal libro cespiti
                    $am = gaz_dbi_get_row($gTables['assets'], 'acc_fixed_assets', $account);
                    if ($am && $ad == 'D') {
                        /* lo è, quindi sto aggiungendo un valore al costo storico del bene ammortizzabile
                          allora scrivo un incremento (type_mov=10) anche sul libro cespiti
                         */
                        $new_am = $am; // uso gli stessi valori del bene originario
                        $new_am['id_movcon'] = $ultimo_id;
                        $new_am['type_mov'] = 10; // è il tipo movimento per le rivalutazioni
                        $new_am['descri'] = $newValue['descri'] . ' ' . $newValue['numdoc'] . ' del ' . gaz_format_date($newValue['datdoc']);
                        $new_am['quantity'] = 1;
                        $new_am['pagame'] = 0; // non lo conosco
                        $new_am['unimis'] = ''; // non lo conosco
                        $new_am['a_value'] = floatval($_POST['importorc'][$i]);
                        gaz_dbi_table_insert('assets', $new_am);
                    }
                }
                // qui inserisco l'eventuale movimento di pagamento
                if ($form['pay_closure'] >= 1) {
                    if (substr($form['cod_partner'], 0, 3) == $admin_aziend['mascli']) { // un cliente
                        rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => 'D', 'codcon' => $form['pay_closure'], 'import' => $form['pay_importo']));
                        rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => 'A', 'codcon' => $form['cod_partner'], 'import' => $form['pay_importo']));
                    } else {
                        rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => 'D', 'codcon' => $form['cod_partner'], 'import' => $form['pay_importo']));
                        rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => 'A', 'codcon' => $form['pay_closure'], 'import' => $form['pay_importo']));
                    }
                }
            }
            if ($toDo == 'insert') {
                header("Location: admin_movcon.php?Insert&new=".$ultimo_id); // ritorno su questo script per inserirne un altro
            } else {
                header("Location: " . $form['ritorno']);
            }
            exit;
        }
    }
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['hidden_req'] = '';
    //registri per il form della testata
    $form['id_testata'] = "";
    $form['codcausale'] = "";
    $form['descrizion'] = "";
    $form['notess'] = "";
    // ricerco l'ultimo inserimento per ricavarne la data
    $rs_last = gaz_dbi_dyn_query('datreg', $gTables['tesmov'], 1, "id_tes DESC", 0, 1);
    $last = gaz_dbi_fetch_array($rs_last);
    if ($last) {
        $form['date_reg_D'] = substr($last['datreg'], 8, 2);
        $form['date_reg_M'] = substr($last['datreg'], 5, 2);
        $form['date_reg_Y'] = substr($last['datreg'], 0, 4);
    } else {
        $form['date_reg_D'] = date("d");
        $form['date_reg_M'] = date("m");
        $form['date_reg_Y'] = date("Y");
    }
    $form['sezioneiva'] = 1;
    $form['protocollo'] = "";
    $form['id_doc'] =0;
    $form['numdocumen'] = "";
    $form['datdoc'] = date("d/m/Y");
    $form['datliq'] = $form['datdoc'];
    $form['inserimdoc'] = 0;
    $form['registroiva'] = 0;
    $form['operatore'] = 0;
    //registri per il form del rigo di inserimento contabile
    $form['insert_mastro'] = 0;
    $form['insert_conto'] = 0;
    $form['search']['insert_conto'] = '';
    $form['paymov'] = array();
    $form['insert_darave'] = "A";
    //registri per il form del rigo di inserimento iva
    $form['insert_imponi'] = 0;
    $form['insert_codiva'] = $admin_aziend['preeminent_vat'];
    $form['insert_imponi'] = 0;
    $form['reverse_charge'] = '';
    $form['operation_type'] = '';
    //registri per il form dei righi contabili
    $_POST['rigcon'] = 0;
    $form['id_rig_rc'] = array();
    $form['mastro_rc'] = array();
    $form['darave_rc'] = array();
    $form['importorc'] = array();
    $form['cod_partner'] = 0;
    $form['pagame'] = 0;
    $form['pay_closure'] = 0;
    //registri per il form dei righi iva
    $_POST['rigiva'] = 0;
    $form['id_rig_ri'] = array();
    $form['codiva_ri'] = array();
    $form['imponi_ri'] = array();
    $form['impost_ri'] = array();
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup', 'custom/modal_form'));



echo '<script type="text/javascript">
      $(function() {
           $( "#search_insert_conto" ).autocomplete({
			source: "../../modules/root/search.php",
			minLength: 2,
			html: true,
			open: function(event, ui) {
				$(".ui-autocomplete").css("z-index", 1000);
			},
			select: function(event, ui) {
				$("#search_insert_conto").val(ui.item.value);
				$(this).closest("form").submit();
			}
           });';
for ($i = 0; $i < $_POST['rigcon']; $i++) {
    echo '   $( "#search_conto_rc' . $i . '" ).autocomplete({
				source: "../../modules/root/search.php",
				minLength: 2,
				html: true,
				open: function(event, ui) {
					$(".ui-autocomplete").css("z-index", 1000);
				},
				select: function(event, ui) {
					$("#search_conto_rc'.$i.'").val(ui.item.value);
					$(this).closest("form").submit();
				}
			 });
        ';
    if ($form['paymov_op_cl'][$i] == 1) { // apertura partita
        echo '   $( "#dialog_open' . $i . '").dialog({
              autoOpen: false
           });
        ';
    } else {  // chiusura partita
        echo '   $( "#dialog_close' . $i . '").dialog({
              autoOpen: false
           });
        ';
    }
}
echo '});
</script>';
echo '<script type="text/javascript" src="./dialog_schedule.js"></script>';
echo "<script type=\"text/javascript\">\n";




echo "var cal = new CalendarPopup();
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
      }\n";

echo "function toggleContent(currentContent) {
        var thisContent = document.getElementById(currentContent);
        if ( thisContent.style.display == 'none') {
           thisContent.style.display = '';
           return;
        }
        thisContent.style.display = 'none';
      }

      function selectValue(currentValue,currentContent) {
         if (currentValue < 0) {
            currentValue = -currentValue;
            document.getElementById(currentContent+'_darave').options[0].selected=true;
         } else {
            document.getElementById(currentContent+'_darave').options[1].selected=true;
         }
         document.getElementById(currentContent+'_import').value=currentValue;
         toggleContent(currentContent);
      }\n";

echo "function balance(row)
      {
      var rw = Number([row]);
      var sumD = 0;
      var sumA = 0;
      for (i=0; i<" . $_POST['rigcon'] . "; i++) {
        if (i == rw) {
           var rva = document.getElementById('impoRC'+i).value*1;
           var rda = document.getElementById('daavRC'+i).value;
        }
        var elva = document.getElementById('impoRC'+i).value*1;
        var elda = document.getElementById('daavRC'+i).value;
        if (elda == 'D') {
           sumD += elva;
        } else {
           sumA += elva;
        }
        document.getElementById('balbRC'+i).value='\u21D4';
        document.getElementById('balbRC'+i).disabled=true;
      }
      var difSUM = sumD - sumA;
      if (((rda == 'D' && difSUM > 0) || (rda == 'A' && difSUM < 0 )) && Math.abs(difSUM) < rva ) {
          var nv = Math.abs(Math.abs(difSUM) - rva);
          var ntot = Math.min(sumD,sumA);
      } else if ((rda == 'D' && difSUM < 0) || (rda == 'A' && difSUM > 0)) {
          var nv = Math.abs(difSUM) + rva;
          var ntot = Math.max(sumD,sumA);
      } else {
          var nv = rva;
          var ntot = sumA;
      }
      document.getElementById('impoRC'+rw).value = (Math.round(nv*100)/100).toFixed(2);
      document.getElementById('impoRC'+rw).style.backgroundColor='#fff';
      document.myform.tot_A.value=(Math.round(ntot*100)/100).toFixed(2);
      document.myform.tot_D.value=(Math.round(ntot*100)/100).toFixed(2);
      document.myform.tot_A.disabled=true;
      document.myform.tot_D.disabled=true;
      document.myform.tot_A.style.backgroundColor='#BBBBBB';
      document.myform.tot_D.style.backgroundColor='#BBBBBB';
      document.myform.ins.disabled=false;
      document.myform.diffV.value='" . $script_transl['bal'] . "';
      }\n";

echo "function tot_bal(da)
      {
      var d_a = [da];
      var ovD = document.getElementById('tot_D').value*1;
      var ovA = document.getElementById('tot_A').value*1;
      var ref = document.getElementById('tot_'+d_a).value*1
      var difSUM = ovD - ovA;
      if ((d_a == 'D' && difSUM > 0) || (d_a == 'A' && difSUM < 0)){
         var oper = 1;
      } else {
         var oper = -1;
      }
      var accu = Math.abs(difSUM);
      for (i=0; i<" . $_POST['rigcon'] . "; i++) {
        var elva = document.getElementById('impoRC'+i).value*1;
        var elda = document.getElementById('daavRC'+i).value;
        if (elda != d_a && accu > 0) {
           if (oper == 1) {
               document.getElementById('impoRC'+i).value=(Math.round((elva + accu)*100)/100).toFixed(2);
               accu = 0;
           } else if (accu < elva && oper == -1) {
               document.getElementById('impoRC'+i).value=(Math.round((elva - accu)*100)/100).toFixed(2);
               accu = 0;
           } else if (accu > elva && oper == -1) {
               accu -= elva;
               document.getElementById('impoRC'+i).value=0;
               document.getElementById('impoRC'+i).style.backgroundColor='#FFAAAA';
           }
        }
        document.getElementById('balbRC'+i).value='\u21D4';
        document.getElementById('balbRC'+i).disabled=true;
      }
      document.myform.tot_A.value=(Math.round(ref*100)/100).toFixed(2);
      document.myform.tot_D.value=(Math.round(ref*100)/100).toFixed(2);
      document.myform.tot_A.disabled=true;
      document.myform.tot_D.disabled=true;
      document.myform.tot_A.style.backgroundColor='#BBBBBB';
      document.myform.tot_D.style.backgroundColor='#BBBBBB';
      document.myform.ins.disabled=false;
      document.myform.diffV.value='" . $script_transl['bal'] . "';
      }\n";

echo "function updateTot(row,newva)
      {
      var nv = [newva.value].toString().replace(/\,/g,'.').split(/\./);
      if (!nv[1]){
           nv[1] = '0';
      }
      nv = (Math.round(Number(nv[0]+'.'+nv[1])*100)/100).toFixed(2);
      if (isNaN(nv)){
           nv = 0;
      }
      var rw = Number([row]);
      var sumD = 0;
      var sumA = 0;
      for (i=0; i<" . $_POST['rigcon'] . "; i++) {
        if (i == rw) {
           document.getElementById('impoRC'+i).value=nv;
           if (nv < 0.01) {
               document.getElementById('impoRC'+i).style.backgroundColor='#FFAAAA';
           } else {
               document.getElementById('impoRC'+i).style.backgroundColor='transparent';
           }
        }
        var elva = document.getElementById('impoRC'+i).value*1;
        var elda = document.getElementById('daavRC'+i).value;
        if (elda == 'D') {
           sumD = sumD + elva;
        } else {
           sumA = sumA + elva;
        }
      }
      var difSUM = (Math.round((sumD - sumA)*100)/100).toFixed(2);
      var dtit = ' " . $script_transl['subval'] . " ';
      for (i=0; i<" . $_POST['rigcon'] . "; i++) {
          var elda = document.getElementById('daavRC'+i).value;
          var elva = document.getElementById('impoRC'+i).value*1;
          if ((elda == 'D' && difSUM > 0) || (elda == 'A' && difSUM < 0)) {
             if (Math.abs(difSUM) < elva ) {
                document.getElementById('balbRC'+i).value='\u21D3';
                document.getElementById('balbRC'+i).disabled=false;
                document.getElementById('balbRC'+i).title=dtit + Math.abs(difSUM) + ' " . $admin_aziend['symbol'] . "';
             } else {
                document.getElementById('balbRC'+i).value='\u21D3';
                document.getElementById('balbRC'+i).disabled=true;
             }
          } else if ((elda == 'D' && difSUM < 0) || (elda == 'A' && difSUM > 0)) {
             document.getElementById('balbRC'+i).value='\u21D1';
             document.getElementById('balbRC'+i).disabled=false;
             document.getElementById('balbRC'+i).title='" . $script_transl['addval'] . " ' + Math.abs(difSUM) + ' " . $admin_aziend['symbol'] . "';
          } else {
             document.getElementById('balbRC'+i).value='\u21D4';
             document.getElementById('balbRC'+i).disabled=true;
          }
      }
      if (difSUM != 0) {
           document.myform.tot_A.style.backgroundColor='#FFAAAA';
           document.myform.tot_D.style.backgroundColor='#FFAAAA';
           if (sumA == 0 ) {
              document.myform.tot_A.disabled=true;
              document.myform.tot_D.disabled=false;
              document.myform.tot_D.title='" . $script_transl['bal_title'] . "';
           } else if (sumD == 0 ){
              document.myform.tot_A.disabled=false;
              document.myform.tot_D.disabled=true;
              document.myform.tot_A.title='" . $script_transl['bal_title'] . "';
           } else {
              document.myform.tot_A.disabled=false;
              document.myform.tot_D.disabled=false;
              document.myform.tot_A.title='" . $script_transl['bal_title'] . "';
              document.myform.tot_D.title='" . $script_transl['bal_title'] . "';
           }
           document.myform.ins.disabled=true;
           document.myform.diffV.value='" . $script_transl['diff'] . " ' + difSUM + ' " . $admin_aziend['symbol'] . "';
      } else if (sumA == 0 ) {
           document.myform.tot_A.style.backgroundColor='#FFAAAA';
           document.myform.tot_D.style.backgroundColor='#FFAAAA';
           document.myform.ins.disabled=true;
           document.myform.diffV.value='" . $script_transl['zero'] . "';
      } else {
           document.myform.tot_A.disabled=true;
           document.myform.tot_D.disabled=true;
           document.myform.tot_A.style.backgroundColor='#BBBBBB';
           document.myform.tot_D.style.backgroundColor='#BBBBBB';
           document.myform.ins.disabled=false;
           document.myform.diffV.value='" . $script_transl['bal'] . "';
      }
      document.myform.tot_A.value = (Math.round(sumA*100)/100).toFixed(2);
      document.myform.tot_D.value = (Math.round(sumD*100)/100).toFixed(2);
      }\n";
echo "</script>\n";
?>
<script type="text/javascript">
    $(function () {
        $("#datdoc").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
        $("#datliq").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
		$("#versamentoIVA").change(function(){this.form.submit();});
    });
</script>
<form method="POST" name="myform" id="myform">
    <?php
    $gForm = new contabForm();
    if (isset($_GET['new']) && !isset($_POST['Insert'])) { // se ho inserito il movimento senza errori lo ricordo ma rimango sullo script
        $gForm->toast('Il movimento <a href="admin_movcon.php?id_tes='.$_GET['new'].'&Update" >'.$_GET['new'].'</a> è stato inserito con successo', 'alert-last-row', 'alert-success');
    }
    echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $form['ritorno'] . "\">";
    echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" id=\"hidden_req\"/>\n";
    echo "<input type=\"hidden\" name=\"" . ucfirst($toDo) . "\" value=\"\">\n";
    echo "<input type=\"hidden\" name=\"id_doc\" value=\"" . $form['id_doc'] . "\">";
    if ($toDo == 'insert') {
        echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['ins_this'] . "</div>\n";
    } else {
        echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['upd_this'] . " n." . $form['id_testata'] . "</div>\n";
    }
    ?>
<div class="table-responsive">
    <table class="Tmiddle table-striped">

        <?php
        if (!empty($msg)) {
            echo '<tr><td colspan="6" class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
        }
        echo "<tr>\n";
        echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['date_reg'] . "</td><td colspan=\"5\" class=\"FacetDataTD\">\n";
        $gForm->CalendarPopup('date_reg', $form['date_reg_D'], $form['date_reg_M'], $form['date_reg_Y'], 'FacetSelect', 1);
        echo "</td>\n";
        echo "</tr>\n";
        ?>
        <tr>
            <td class="FacetFieldCaptionTD"><?php echo $script_transl['caucon']; ?></td>
            <td  class="FacetDataTD" colspan="5">
                <?php
                echo '<select name="codcausale" class="FacetSelect" ';
                if (empty($form["codcausale"])) {
                    echo ' tabindex="14"';
                    $tabsmt = ' tabindex="15"';
                } else {
                    $tabsmt = '';
                }
                echo '><option value="">Libera</option>';
                $result = gaz_dbi_dyn_query("*", $gTables['caucon'], 1, "regiva DESC, operat DESC, descri ASC");
                while ($row = gaz_dbi_fetch_array($result)) {
                    $selected = "";
                    if ($form["codcausale"] == $row['codice']) {
                        $selected = " selected ";
                    }
                    echo "<option value=\"" . $row['codice'] . "\"" . $selected . ">" . $row['codice'] . " - " . $row['descri'] . "</option>\n";
                }
                echo '  </select>&nbsp;<button type="submit" class="btn btn-default btn-sm" name="inscau" title="' . $script_transl['v_caucon'] . '!" ' . $tabsmt . '><i class="glyphicon glyphicon-ok"></i></button>
		</td>
	   </tr>';
                echo "<tr>\n";
                echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['descri'] . "</td>\n";
                echo "\t<td colspan=\"5\" class=\"FacetDataTD\"><input type=\"text\" name=\"descrizion\" value=\"" . $form['descrizion'] . "\" maxlength=\"100\"  /></td>\n";
                echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['insdoc'] . "</td><td class=\"FacetDataTD\" >\n";
                $gForm->variousSelect('inserimdoc', $script_transl['insdoc_value'], $form['inserimdoc'], 'FacetSelect', false, 'inserimdoc');
                echo "\t </td>\n";
                echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['regiva'] . "</td><td class=\"FacetDataTD\">\n";
                $gForm->variousSelect('registroiva', $script_transl['regiva_value'], $form['registroiva'], 'FacetSelect', false, 'registroiva');
                echo "\t </td>\n";
                echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['operat'] . "</td><td class=\"FacetDataTD\">\n";
                $gForm->variousSelect('operatore', $script_transl['operat_value'], $form['operatore'], 'FacetSelect', false, 'operatore');
                echo "\t </td>\n";
                echo "</tr>\n";
                echo "\t<td class=\"FacetFieldCaptionTD\">" . "Note" . "</td>\n";
                echo "\t<td colspan=\"5\" class=\"FacetDataTD\"><textarea name=\"notess\" style=\"width: 90%;\">" . $form['notess'] . "</textarea></td>\n";
                echo "</tr>\n";
                ?>
    </table>
  </div>
    <?php
//inserimento dati documenti
    if ($form["inserimdoc"] == 1) {
        if (empty($form['numdocumen'])) {
            $tabnum = ' tabindex="10" ';
        } else {
            $tabnum = '';
        }
        ?>
        <div class="panel panel-default">
            <div>
                <ul class="nav nav-tabs">
                    <li class="active bg-info"><a data-toggle="tab" href="#insdoc"><i class="glyphicon glyphicon-tag"></i> <?php echo $script_transl['insdoc']; ?></a></li>
					<?php
					$urldoc = '';
					if ($toDo=='update') {
            switch ($form['codcausale']){
              case "FAD":
                $urldoc = '../vendit/report_docven.php?sezione=' . $form['sezioneiva'] . '&protoc=' . $form['protocollo'] . '&anno=' . intval(substr($form['datdoc'], 6, 4)) . '&info=none';
              break;
              case "FAI":
              $urldoc = $form['id_doc']>0?'../vendit/admin_docven.php?Update&id_tes='.$form['id_doc']:'';
              break;
              case "AFA":
              $urldoc = $form['id_doc']>0?'../acquis/admin_docacq.php?Update&id_tes='.$form['id_doc']:'';
              break;
            }
            ?>
            <li><?php if (strlen($urldoc)>10) {?><a href="<?php echo $urldoc; ?>" target="_blank">Documento di origine</a><?php } ?></li>
            <?php
          }
					?>
                </ul>
                <div class="tab-content col-sm-12 col-md-12 col-lg-12 bg-info">
                    <div id="insdoc" class="tab-pane fade in active">
                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="form-group">
                                <label for="sezioneiva" class="col-sm-6 control-label"><?php echo $script_transl['seziva']; ?></label>
                                <?php $gForm->selectNumber('sezioneiva', $form['sezioneiva'], 0, 1, 9, 'col-sm-6'); ?>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="form-group">
                                <label for="protocollo" class="col-sm-6 control-label"><?php echo $script_transl['protoc']; ?></label>
                                <input class="col-sm-6" type="number" step="1" min="1" id="protocollo" name="protocollo" value="<?php echo $form['protocollo']; ?>">
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="form-group">
                                <label for="numdocumen" class="col-sm-6 control-label"><?php echo $script_transl['numdoc']; ?></label>
                                <input class="col-sm-6" type="text" <?php echo $tabnum; ?> placeholder="<?php echo $script_transl['numdoc']; ?>" value="<?php echo $form['numdocumen']; ?>" name="numdocumen" />
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="form-group">
                                <label for="datdoc" class="col-sm-6 control-label"><?php echo $script_transl['date_doc']; ?></label>
                                <input class="col-sm-6" type="text" class="form-control" id="datdoc" name="datdoc" value="<?php echo $form['datdoc']; ?>">
                            </div>
                        </div>
                    </div><!-- chiude tab-pane  -->
                </div><!-- chiude tab-content  -->
                <?php
                if ($partnersel && $partnersel['ragso1'] != '') {
                    ?>
                    <div class="tab-content col-sm-12 col-md-12 col-lg-12">
                        <?php
                        echo $partnersel['ragso1'] . " " . $partnersel['ragso2'] . " - " . $partnersel['indspe'] . " - " . $partnersel['citspe'] . " - Partita IVA:" . $partnersel['pariva'];
						if ($toDo == 'insert'){
							echo  " Pagamento:";
							$select_pagame = new selectpagame("pagame");
							$select_pagame->addSelected($form["pagame"]);
							$select_pagame->output('change_pag', "small");
                        } else {
						?>
						<input type="hidden" name="pagame" value="<?php echo $form['pagame']; ?>" />
						<?php
						}
						?>
                    </div><!-- chiude tab-content  -->
                    <?php
				} else {
                    ?>
                    <input type="hidden" name="pagame" value="<?php echo $form['pagame']; ?>" />
                    <?php
                }
                ?>
            </div><!-- chiude container  -->
        </div><!-- chiude panel  -->
        <?php
    } else {
        ?>
        <input type="hidden" name="datdoc" value="<?php echo $form['datdoc']; ?>" />
        <input type="hidden" name="pagame" value="<?php echo $form['pagame']; ?>" />
        <input type="hidden" name="sezioneiva" value="<?php echo $form['sezioneiva']; ?>" />
        <input type="hidden" name="numdocumen" value="<?php echo $form['numdocumen']; ?>" />
        <input type="hidden" name="protocollo" value="<?php echo $form['protocollo']; ?>" />
        <?php
    }
    ?>
    <input type="hidden" name="cod_partner" value="<?php echo $form['cod_partner']; ?>" />
    <input type="hidden" name="pay_closure" value="<?php echo $form['pay_closure']; ?>" />
    <input type="hidden" name="reverse_charge" value="<?php echo $form['reverse_charge']; ?>" />
    <?php
//inserimento movimento iva
	if($form['registroiva'] == 9){ // ho un versamento IVA
		// rigo di input non utilizzato
		echo '<input type="hidden" value="' . $_POST['rigiva'] . '" name="rigiva">';
		echo '<input type="hidden" name="insert_imponi" value="' . $form['insert_imponi']. '">';
    echo '<input type="hidden" name="insert_codiva" value="' . $form['insert_codiva'] . '">';
    echo '<input type="hidden" name="operation_type" value="' . $form['operation_type'] . '">';
		// rigo unico indice zero
		echo '<input type="hidden" name="id_rig_ri[0]" value="' . $form['id_rig_ri'][0] . '">';
		echo '<input type="hidden" name="imponi_ri[0]" value="' . $form['imponi_ri'][0] . '">';
    echo '<input type="hidden" name="codiva_ri[0]" value="' . $form['codiva_ri'][0] . '">';
    echo '<input type="hidden" name="reverse_charge_ri[0]" value="' . $form['reverse_charge_ri'][0] . '">';
    echo '<input type="hidden" name="operation_type_ri[0]" value="' . $form['operation_type_ri'][0] . '">';
    // creo l'array da passare alla funzione per la creazione della tabella responsive
    $resprow[0] = array(
        array('head' => $script_transl["vat"], 'class' => 'text-center',
            'value' => 'VERSAMENTO DEBITO IVA'),
        array('head' => $script_transl["tax"], 'class' => 'text-right numeric',
            'value' => '<input type="number" step="0.01" name="impost_ri[0]" value="' . $form['impost_ri'][0]. '" maxlength="13"  tabindex="20" id="versamentoIVA" />'),
        array('head' => $script_transl["datliq"], 'class' => 'text-right numeric',
              'value' => '<input type="text" id="datliq" name="datliq" value="' . $form['datliq'] . '" />'),
    );
		$gForm->gazResponsiveTable($resprow, 'gaz-responsive-table');
	}elseif($form["registroiva"] > 0) {
    if (substr($form['reverse_charge'],0,2) == 'N6') {
        $gForm->toast("L'aliquota I.V.A. selezionata (natura=N6) prevede che al termine dell'inserimento del movimento venga aggiunto un rigo sul Registro IVA vendite (REVERSE CHARGE)", 'alert-last-row', 'alert-success');
    } elseif ($form['reverse_charge'] >= 1) { // vengo da un reverse charge già inserito
        $gForm->toast('Il movimento ha una aliquota IVA (natura=N6) che ha aggiunto un rigo (n.<a  href="select_partit.php?id=' . $form['reverse_charge'] . '">' . $form['reverse_charge'] . "</a>) sul Registro IVA vendite per REVERSE CHARGE", 'alert-last-row', 'alert-success');
    }
    if ($_POST['rigiva'] == 0) { //se non ci sono righi tabulo
        $tabimp = ' tabindex="20" ';
        $tabsmt = ' tabindex="21" ';
    } else {
        $tabimp = '';
        $tabsmt = '';
    }
    ?>
  <div align="center"><b><?php echo $script_transl['insiva']; ?></b></div>
        <?php
        $resprow[0] = array();
        echo '<input type="hidden" value="' . $_POST['rigiva'] . '" name="rigiva">';
        for ($i = 0; $i < $_POST['rigiva']; $i++) {
          $operation_type_dropdown = $gForm->selectFromXML('../../library/include/operation_type.xml', 'operation_type_ri[' . $i . ']', 'operation_type_ri['.$i.']', $form['operation_type_ri'][$i], true, '', '',null,'',false);
          $rigoi = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['codiva_ri'][$i]);
          echo '<input type="hidden" name="id_rig_ri[' . $i . ']" value="' . $form['id_rig_ri'][$i] . '">';
          echo '<input type="hidden" name="codiva_ri[' . $i . ']" value="' . $form['codiva_ri'][$i] . '">';
          echo '<input type="hidden" name="reverse_charge_ri[' . $i . ']" value="' . $form['reverse_charge_ri'][$i] . '">';
          if (!isset($form['imponi_ri'][$i])) {
              $form['imponi_ri'][$i] = "";
          }
          if (!isset($form['impost_ri'][$i])) {
              $form['impost_ri'][$i] = "";
          }
          if (!isset($form['codiva_ri'][$i])) {
              $form['codiva_ri'][$i] = "";
          }
          // creo l'array da passare alla funzione per la creazione della tabella responsive
          $resprow[$i] = array(
              array('head' => $script_transl["taxable"], 'class' => 'text-right numeric',
                  'value' => '<input type="number" step="0.01" name="imponi_ri[' . $i . ']" value="' . sprintf("%01.2f", preg_replace("/\,/", '.', $form['imponi_ri'][$i])) . '" maxlength="13" onchange="this.form.submit()" />'),
              array('head' => $script_transl["vat"], 'class' => 'text-center',
                  'value' => $rigoi["descri"]),
              array('head' => $script_transl["operation_type"], 'class' => 'text-center',
                  'value' => $operation_type_dropdown),
              array('head' => $script_transl["tax"], 'class' => 'text-right numeric',
                  'value' => '<input type="number" step="0.01" name="impost_ri[' . $i . ']" value="' . sprintf("%01.2f", preg_replace("/\,/", '.', $form['impost_ri'][$i])) . '" maxlength="13" />'),
              array('head' => $script_transl["delete"], 'class' => 'text-center',
                  'value' => '<button type="submit" class="btn btn-default btn-sm btn-elimina" name="dei[' . $i . ']" title="' . $script_transl['delrow'] . '"><i class="glyphicon glyphicon-trash"></i></button>')
          );
        }
        $gForm->gazResponsiveTable($resprow, 'gaz-responsive-table');
?>
        <div class="panel input-area">
            <div class="container-fluid">
                <ul class="nav nav-tabs">
                    <li class="active bg-info"><a data-toggle="tab" href="#insdoc"><i class="glyphicon glyphicon-indent-right"></i> <?php echo $script_transl['insiva']; ?></a></li>
                </ul>
                <div class="col-sm-12">
                    <div id="insdoc" class="tab-pane fade in active">
						<div class="form-group col-md-6 col-lg-3">
                            <label for="taxable" class="col-sm-6 control-label"><?php echo $script_transl['taxable']; ?></label>
                            <input class="col-sm-6" type="text" <?php echo $tabimp; ?> placeholder="<?php echo $script_transl['taxable']; ?>" value="<?php echo $form['insert_imponi']; ?>" name="insert_imponi" />
                        </div>
						<div class="form-group col-md-6 col-lg-2">
                            <label for="insert_codiva" class="col-sm-4 control-label"><?php echo $script_transl['vat']; ?></label>
                            <div>
                                <?php
                                $sel_vat = new selectaliiva("insert_codiva");
                                $sel_vat->addSelected($form["insert_codiva"]);
                                $sel_vat->output("col-sm-8");
                                ?>
                            </div>
                        </div>
						<div class="form-group col-md-6 col-lg-3">
                             <label for="operation_type" class="col-sm-6 control-label"><?php echo $script_transl['operation_type']; ?></label>
                             <?php
                             $gForm->selectFromXML('../../library/include/operation_type.xml', 'operation_type', 'operation_type', $form['operation_type'], true, '', 'col-sm-6');
                             ?>
                        </div>
						<div class="form-group col-md-6 col-lg-3">
							<label for="datliq" class="col-sm-6 control-label"><?php echo $script_transl['datliq']; ?></label>
							<input class="col-sm-6" type="text" id="datliq" name="datliq" value="<?php echo $form['datliq']; ?>">
                        </div>
						<div class="form-group col-md-6 col-lg-1 text-center">
                             <button type="submit" class="btn btn-success btn-sm" name="adi" <?php echo $tabsmt; ?> ><?php echo $script_transl['addrow']; ?> IVA <i class="glyphicon glyphicon-ok"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php
    } else {
		?>
        <input type="hidden" name="datliq" value="<?php echo $form['datliq']; ?>" />
		<?php
	}
    ?>
    <!--</div><!-- chiude panel  -->
    <?php
//inserimento movimento contabile
    echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['del_this'] . "</div>\n";
    if ($form['pay_closure'] >= 1 && $toDo == 'insert') {
        $pay = gaz_dbi_get_row($gTables['pagame'], "codice", $form['pagame']);
        $payacc = gaz_dbi_get_row($gTables['clfoco'], "codice", $form['pay_closure']);
        $gForm->toast("ATTENZIONE!!! Il pagamento <span style='background-color: yellow;'>" . $pay['descri'] . "</span> prevede che al termine della registrazione siano aggiunti due righi per la chiusura automatica della partita sul conto: <span style='background-color: yellow;'>" . $pay['pagaut'] . '-' . $payacc['descri'] . "</span>", 'alert-last-row', 'alert-success');  //lo mostriamo
    }
    echo "<div class=\"panel panel-succes table-responsive\">";
    echo '<table class="Tlarge table table-striped">';
//fine rigo inserimento
// inizio righi già inseriti
// faccio un primo ciclo del form per sommare e analizzare gli sbilanciamenti
    $form['tot_D'] = 0.00;
    $form['tot_A'] = 0.00;
    for ($i = 0; $i < $_POST['rigcon']; $i++) {
        $val = sprintf("%01.2f", preg_replace("/\,/", '.', $form['importorc'][$i]));
        if ($form["darave_rc"][$i] == 'D') {
            $form['tot_D'] += $val;
        } else {
            $form['tot_A'] += $val;
        }
    }
    $diffDA = number_format($form['tot_D'] - $form['tot_A'], 2, '.', '');
    if ($diffDA <> 0) {
        if ($form['tot_D'] == 0) {
            $d_but = ' style="text-align:right; background-color:#FFAAAA;" disabled ';
            $a_but = ' style="text-align:right; background-color:#FFAAAA;" title="' . $script_transl['bal_title'] . '" ';
        } elseif ($form['tot_A'] == 0) {
            $d_but = ' style="text-align:right; background-color:#FFAAAA;" title="' . $script_transl['bal_title'] . '" ';
            $a_but = ' style="text-align:right; background-color:#FFAAAA;" disabled ';
        } else {
            $d_but = ' style="text-align:right; background-color:#FFAAAA;" title="' . $script_transl['bal_title'] . '" ';
            $a_but = ' style="text-align:right; background-color:#FFAAAA;" title="' . $script_transl['bal_title'] . '" ';
        }
        $i_but = ' disabled ';
        $diffV = ' <input style="text-align:center;" value="' . $diffDA . '" type="text" name="diffV" disabled />';
    } elseif ($form['tot_A'] == 0) {
        $d_but = ' style="text-align:right; background-color:#FFAAAA;" title="' . $script_transl['bal_title'] . '" ';
        $a_but = ' style="text-align:right; background-color:#FFAAAA;" title="' . $script_transl['bal_title'] . '" ';
        $i_but = ' disabled ';
        $diffV = ' <input style="text-align:center;" value="Movimenti a zero" type="text" name="diffV" disabled />';
    } else {
        $d_but = ' style="text-align:right; background-color:#BBBBBB;" disabled ';
        $a_but = ' style="text-align:right; background-color:#BBBBBB;" disabled ';
        $i_but = '';
        $diffV = ' <input style="text-align:center;" value="' . $script_transl['bal'] . '" type="text" name="diffV" disabled />';
    }
//fine analisi sbilanciamento
if ( $_POST['rigcon']>=1){
?>
<thead><tr><th></th><th>Mastro</th><th>Conto</th><th class="text-center">Dare</th><th class="text-center"><i class="glyphicon glyphicon-refresh"></i></th><th class="text-center">Avere</th><th>X</th></tr></thead>
<?php
}
    for ($i = 0; $i < $_POST['rigcon']; $i++) {
        echo "<input type=\"hidden\" id=\"id_rig_rc$i\" name=\"id_rig_rc[$i]\" value=\"" . $form['id_rig_rc'][$i] . "\">\n";
        echo "<input type=\"hidden\" id=\"paymov_op_cl$i\" name=\"paymov_op_cl[$i]\" value=\"" . $form['paymov_op_cl'][$i] . "\">\n";
        if ($form['registroiva'] > 0 and ( substr($form['conto_rc' . $i], 0, 3) == $admin_aziend['mascli'] or
                substr($form['conto_rc' . $i], 0, 3) == $admin_aziend['masfor'])) {
            $form['insert_partner'] = $form['conto_rc' . $i];
        }
        echo "<tr>";
        echo "<td>".($i+1).'</td><td>';
        $gForm->selMasterAcc("mastro_rc[$i]", $form["mastro_rc"][$i], "mastro_rc[$i]");
        echo "</td>\n";
        echo "<td>";
        $gForm->lockSubtoMaster($form["mastro_rc"][$i], 'conto_rc' . $i);
        // visualizzo i conti HIDDEN solo se in modifica e conto già valorizzato
        $hidden = ($toDo == 'update' && $form['conto_rc' . $i]>=100000001) ? false : true ;
        $gForm->sub_Account('conto_rc' . $i, $form['conto_rc' . $i], $form['search']['conto_rc' . $i], $form['hidden_req'], $script_transl['mesg'],$hidden);
        if (!preg_match("/^id_([0-9]+)$/", $form['conto_rc' . $i], $match)) { // non è un partner da inserire sul piano dei conti
            echo '<a class="btn btn-xs btn-default" href="select_partit.php?id=' . $form['conto_rc' . $i] . '" title="' . $script_transl['visacc'] . '" target="_new">
								<i class="glyphicon glyphicon-eye-open"></i>
							  </a>';
        }
        echo "</td>\n";

        $val = sprintf("%01.2f", preg_replace("/\,/", '.', $form['importorc'][$i]));
        $valsty = ' style="text-align:right;" ';
        if ($val < 0.01) {
            $valsty = ' style="text-align:right; background-color:#FFAAAA;" ';
        }
        $acc_amount = "<td class=\"text-center\"><input type=\"text\" name=\"importorc[$i]\" id=\"impoRC$i\" value=\"$val\" $valsty onchange=\"updateTot($i,this);\" maxlength=\"13\" tabindex=\"" . (30 + $i * 2) . "\" >";
        if ($form['darave_rc'][$i] == 'D' && $form['tot_D'] > $form['tot_A'] ||
                $form['darave_rc'][$i] == 'A' && $form['tot_A'] > $form['tot_D']) {
            $r_but = ' value="&dArr;" title="' . $script_transl['subval'] . ' ';
            if (abs($diffDA) < $form['importorc'][$i]) {
                $r_but = ' value="&dArr;" title="' . $script_transl['subval'] . ' ' . abs($diffDA) . " " . $admin_aziend['symbol'] . "\" ";
            } else {
                $r_but = ' value="&dArr;" disabled ';
            }
        } elseif ($form['darave_rc'][$i] == 'D' && $form['tot_D'] < $form['tot_A'] ||
                $form['darave_rc'][$i] == 'A' && $form['tot_A'] < $form['tot_D']) {
            $r_but = ' value="&uArr;" title="' . $script_transl['addval'] . ' ' . abs($diffDA) . " " . $admin_aziend['symbol'] . "\" ";
        } else {                                     //bilanciato
            $r_but = ' value="&hArr;" disabled';
        }
        $acc_amount .= "<input type=\"button\" id=\"balbRC$i\" name=\"balb[$i]\" $r_but  onclick=\"balance($i);\"/></td>";
        $acc_darave = "<td class=\"text-center\"><select class=\"FacetSelect\" id=\"daavRC$i\" name=\"darave_rc[$i]\" onchange=\"this.form.submit()\" tabindex=\"" . (31 + $i * 2) . "\">";
        foreach ($script_transl['daav_value'] as $key => $value) {
          $selected = "";
          if ($form["darave_rc"][$i] == $key) {
              $selected = " selected ";
          }
          $acc_darave .= "<option value=\"" . $key . "\"" . $selected . ">" . $key . "</option>\n";
        }
        $acc_darave .= "</select></td>\n";
        if ($form['darave_rc'][$i]=='D') {
          echo $acc_amount.$acc_darave.'<td></td>';
        } else {
          echo '<td></td>'.$acc_darave.$acc_amount;
        }
        echo '  <td>
			  <button type="submit" class="btn  btn-elimina btn-sm" name="del[' . $i . ']" title="' . $script_transl['delrow'] . '!"><i class="glyphicon glyphicon-trash"></i></button>
			</td>
		  </tr>';
    }

//faccio il post del numero di righi
    echo "<input type=\"hidden\" value=\"" . $_POST['rigcon'] . "\" name=\"rigcon\">";
    echo "<input type=\"hidden\" value=\"" . $form['id_testata'] . "\" name=\"id_testata\">";
    echo '<tr><td colspan=3 class="text-right bg-info"><b>Totali:</b> '.$diffV.'</td><td class="bg-info text-center">';
    echo "DARE: <input type=\"button\" $d_but value=\"" . number_format($form['tot_D'], 2, '.', '') . "\" id=\"tot_D\" name=\"tot_D\" onclick=\"tot_bal('D');\" />\n";

    echo '<td colspan=2 class="bg-info text-center">AVERE: ';
    echo "<input type=\"button\" $a_but value=\"" . number_format($form['tot_A'], 2, '.', '') . "\" id=\"tot_A\" name=\"tot_A\" onclick=\"tot_bal('A');\" />\n";
    echo '</td><td class="bg-info"></td>';
    echo "</tr></table>";
    echo "<table class=\"table input-area\">\n";
    echo "<tr><td></td><td><b>" . $script_transl['mas'] . "</b></td><td><b>" . $script_transl['sub'] . "<b></td><td></b>" . $script_transl['amount'] . "</b></td><td><b>" . $script_transl['daav'] . "</b></td><td></td></tr>\n";
    echo "<tr>\n";
    echo "<td>#</td><td>";
    $gForm->selMasterAcc('insert_mastro', $form['insert_mastro'], 'insert_mastro');
    echo "</td>\n";
    echo "<td>\n";
    $gForm->lockSubtoMaster($form['insert_mastro'], 'insert_conto');
    // visualizzo i conti HIDDEN solo se in modifica e conto già valorizzato
    $hidden = ($toDo == 'update' && $form['insert_conto']>=100000001) ? false : true ;
    $gForm->sub_Account('insert_conto', $form['insert_conto'], $form['search']['insert_conto'], $form['hidden_req'], $script_transl['mesg'],$hidden);
    echo "</td>\n";
    echo "<td><div onmousedown=\"toggleContent('insert')\" class=\"clickarea\" style=\"cursor:pointer;\">";
    echo "<input style=\"text-align:right;\" type=\"text\" value=\"\" maxlength=\"13\" id=\"insert_import\" name=\"insert_import\"> &crarr;</div>\n";
    $gForm->settleAccount('insert', $form['insert_conto'], sprintf("%04d%02d%02d", $form['date_reg_Y'], $form['date_reg_M'], $form['date_reg_D']));
    echo "</td>";
    echo "\t<td>\n";
    $gForm->variousSelect('insert_darave', $script_transl['daav_value'], $form['insert_darave'], 'FacetSelect', false);
    echo "\t </td>\n";
    echo '  <td align="center"><button type="submit" class="btn btn-success btn-sm" name="add">'. $script_transl['addrow'] . ' contabile <i class="glyphicon glyphicon-ok"></i></button></td></tr>';
    echo '</table>';
    echo "</div>";
    echo '<br/><div class="text-center FacetFooterTD"><input name="ins" id="preventDuplicate" class="btn btn-warning" onClick="chkSubmit();" type="submit" ' . $i_but . ' tabindex="99" value="' . ucfirst($script_transl[$toDo]) . ' il movimento contabile"></div>';

// INIZIO creazione dialog-schedule dei partner
    for ($i = 0; $i < $_POST['rigcon']; $i++) {
        if (isset($form['paymov'][$i])) {
            $pm_row = 0;
            echo '
        <div id="pm_post_container_' . $i . '">';
            foreach ($form['paymov'][$i] as $i_j => $v_j) {
                echo '<div id="pm_post_' . $pm_row . '">
                  <input type="hidden" id="post_' . $i . '_' . $pm_row . '_id" name="paymov[' . $i . '][' . $pm_row . '][id]" value="' . $form['paymov'][$i][$i_j]['id'] . '" />
                  <input type="hidden" id="post_' . $i . '_' . $pm_row . '_id_tesdoc_ref" name="paymov[' . $i . '][' . $pm_row . '][id_tesdoc_ref]" value="' . $form['paymov'][$i][$i_j]['id_tesdoc_ref'] . '" />
                  <input type="hidden" id="post_' . $i . '_' . $pm_row . '_expiry" name="paymov[' . $i . '][' . $pm_row . '][expiry]" value="' . $form['paymov'][$i][$i_j]['expiry'] . '" />
                  <input type="hidden" id="post_' . $i . '_' . $pm_row . '_amount" name="paymov[' . $i . '][' . $pm_row . '][amount]" value="' . $form['paymov'][$i][$i_j]['amount'] . '" />
                  </div>
                 ';
                $pm_row++;
            }
            echo '</div>
        ';
            echo '
        <div id="paymov_last_id' . $i . '" value="' . $i_j . '"></div>
        ';
            $partner_paymov = $anagrafica->getPartner($form['conto_rc' . $i]);
			if (!$partner_paymov) { // non selezionato
                echo '<div id="dialog_open' . $i . '" >';
            } elseif ($form['paymov_op_cl'][$i] == 1) { // apertura partita
                echo '<div id="dialog_open' . $i . '" partner="' . $partner_paymov['ragso1'] . '" title="Apertura: ' . $form['descrizion'] . ' - ' . $partner_paymov['ragso1'] . ' - ' . $admin_aziend['html_symbol'] . ' ' . sprintf("%01.2f", preg_replace("/\,/", ".", $form["importorc"][$i])) . '">';
            } else {  // chiusura partita
                echo '<div id="dialog_close' . $i . '" partner="' . $partner_paymov['ragso1'] . '" title="Chiusura: ' . $form['descrizion'] . ' - ' . $partner_paymov['ragso1'] . ' - ' . $admin_aziend['html_symbol'] . ' ' . sprintf("%01.2f", preg_replace("/\,/", ".", $form["importorc"][$i])) . '">';
            }
            echo '<p class="validateTips"></p>
        <table id="pm_form_container_' . $i . '" class="ui-widget ui-widget-content" width="100%">
        <tbody>';
            echo '
             </tbody>
            </table>
            <table  width="100%" id="db-contain' . $i . '" class="ui-widget ui-widget-content">
             <tbody>
             </tbody>
            </table>
        </div>
        ';
        }
    }
// FINE creazione form dialog-schedule
    ?>
</form>
<?php
if ( empty($msg) && $scorrimento == '1' ) { // scrollo solo se voluto e non ci sono errori
?>
<script>
    $("html, body").stop().animate({scrollTop:$(document).height()}, 400, 'swing', function() {
   });</script>
<?php
}
?>
<script>
  $(function () {
    $( "#onlyone_submit" ).trigger( "click" );
  });
</script>

<?php
require("../../library/include/footer.php");
?>
