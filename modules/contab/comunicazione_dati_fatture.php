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
$admin_aziend = checkAdmin();
$msg = array('err' => array(), 'war' => array());
require("../../library/include/check.inc.php");

if (isset($_POST['Update']) || isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

function createRowsAndErrors($anno, $periodicita, $mese_trimestre_semestre,$esterometro=false) {
    global $gTables, $admin_aziend, $script_transl;
    $nuw = new check_VATno_TAXcode();
    if ($periodicita == 'M') { // mensile
        $date_ini = new DateTime($anno . '-'.$mese_trimestre_semestre.'-01');
        $di = $date_ini->format('Y-m-d');
        $df = $date_ini->format('Y-m-t');
    } elseif ($periodicita == 'T') { // trimestrale
        if ($mese_trimestre_semestre == 1) {
            $date_ini = new DateTime($anno . '-01-01');
        } elseif ($mese_trimestre_semestre == 2) {
            $date_ini = new DateTime($anno . '-04-01');
        } elseif ($mese_trimestre_semestre == 3) {
            $date_ini = new DateTime($anno . '-07-01');
        } else {
            $date_ini = new DateTime($anno . '-10-01');
        }
        $di = $date_ini->format('Y-m-d');
        $date_ini->modify('+2 month');
        $df = $date_ini->format('Y-m-t');
    } else { // semestrale
        if ($mese_trimestre_semestre == 1) {
            $date_ini = new DateTime($anno . '-01-01');
        } else {
            $date_ini = new DateTime($anno . '-07-01');
        }
        $di = $date_ini->format('Y-m-d');
        $date_ini->modify('+5 month');
        $df = $date_ini->format('Y-m-t');
    }
	$esterometro=($esterometro)?(" AND " . $gTables['anagra'].".country <>'IT' AND " . $gTables['anagra'].".fiscal_rapresentative_id < 1"):'';
    $sqlquery = "SELECT " . $gTables['rigmoi'] . ".*, ragso1,ragso2,sedleg,sexper,indspe,regiva,allegato,
               citspe,prospe,capspe,legrap_pf_nome,legrap_pf_cognome,country,codfis,pariva,id_anagra,fae_natura," .
            $gTables['tesmov'] . ".clfoco," . $gTables['tesmov'] . ".protoc," . $gTables['tesmov'] . ".numdoc," .
            $gTables['tesmov'] . ".datdoc," . $gTables['tesmov'] . ".seziva," . $gTables['tesmov'] . ".caucon," . $gTables['tesmov'] . ".datreg,datnas,luonas,pronas,counas,
               id_doc,iso,black_list,cod_agenzia_entrate, operat, impost AS imposta," . $gTables['rigmoi'] . ".id_tes
               AS idtes, imponi AS imponibile FROM " . $gTables['rigmoi'] . "
               LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoi'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes
               LEFT JOIN " . $gTables['tesdoc'] . " ON " . $gTables['tesmov'] . ".id_doc = " . $gTables['tesdoc'] . ".id_tes
               LEFT JOIN " . $gTables['aliiva'] . " ON " . $gTables['rigmoi'] . ".codiva = " . $gTables['aliiva'] . ".codice
               LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesmov'] . ".clfoco = " . $gTables['clfoco'] . ".codice
               LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['anagra'] . ".id = " . $gTables['clfoco'] . ".id_anagra
               LEFT JOIN " . $gTables['country'] . " ON " . $gTables['anagra'] . ".country = " . $gTables['country'] . ".iso
               WHERE " . $gTables['tesmov'] . ".datreg BETWEEN '" . $di . "' AND '" . $df . "'
                 AND ( " . $gTables['tesmov'] . ".clfoco LIKE '" . $admin_aziend['masfor'] . "%' OR " . $gTables['tesmov'] . ".clfoco LIKE '" . $admin_aziend['mascli'] . "%')
                 AND " . $gTables['clfoco'] . ".allegato > 0 AND " . $gTables['tesmov'] . ".seziva <> " . $admin_aziend['reverse_charge_sez'] .$esterometro." ORDER BY regiva,operat,clfoco," . $gTables['tesmov'] . ".datreg,protoc";
    $result = gaz_dbi_query($sqlquery);
    $castel_transact = array();
    $error_transact = array();
    if (gaz_dbi_num_rows($result) > 0) {
        // inizio creazione array righi ed errori
        $progressivo = 0;
        $ctrl_id = 0;
        $value_imponi = 0.00;
        $value_impost = 0.00;
        while ($row = gaz_dbi_fetch_array($result)) {
            if ($row['operat'] >= 1) {
                $value_imponi = $row['imponibile'];
                $value_impost = $row['imposta'];
            } else {
                $value_imponi = 0;
                $value_impost = 0;
            }
            if ($ctrl_id <> $row['idtes']) {
                $chk_intra = 'IT';
                // inizio controlli su CF e PI
                $resultpi = $nuw->check_VAT_reg_no($row['pariva']);
                // danielemz - temporaneo per imposta 2017- bolle doganali
                if ($row['pariva'] == '99999999999') {
                    $resultpi = "";
                }
                $resultcf = $nuw->check_VAT_reg_no($row['codfis']);
                if ($admin_aziend['country'] != $row['country']) {
                    // È uno non residente 
                    if ($row['country'] == 'SM') {
                        // SAN MARINO 
                    } else {
                        
                    }
                } elseif (empty($resultpi) && !empty($row['pariva'])) {
                    // ha la partita IVA ed è giusta 
                    if (trim($row['pariva']) == "99999999999") {
                        // danielemz - temporaneo, forzatura per superare i controlli bolletta doganale imposta 2017
                    } elseif (strlen(trim($row['codfis'])) == 11) {
                        // È una persona giuridica
                        if (intval($row['codfis']) == 0 && $row['allegato'] < 2) { // se non è un riepilogativo 
                            $error_transact[$row['idtes']][] = $script_transl['errors'][1];
                        } elseif ($row['sexper'] != 'G') {
                            $error_transact[$row['idtes']][] = $script_transl['errors'][2];
                        }
                    } else {
                        // È una una persona fisica
                        $resultcf = $nuw->check_TAXcode($row['codfis']);
                        if (empty($row['codfis'])) {
                            $error_transact[$row['idtes']][] = $script_transl['errors'][3];
                        } elseif ($row['sexper'] == 'G' and empty($resultcf)) {
                            $error_transact[$row['idtes']][] = $script_transl['errors'][4];
                        } elseif ($row['sexper'] == 'M' and empty($resultcf) and ( intval(substr($row['codfis'], 9, 2)) > 31 or
                                intval(substr($row['codfis'], 9, 2)) < 1)) {
                            $error_transact[$row['idtes']][] = $script_transl['errors'][5];
                        } elseif ($row['sexper'] == 'F' and empty($resultcf) and ( intval(substr($row['codfis'], 9, 2)) > 71 or
                                intval(substr($row['codfis'], 9, 2)) < 41)) {
                            $error_transact[$row['idtes']][] = $script_transl['errors'][6];
                        } elseif (!empty($resultcf)) {
                            $error_transact[$row['idtes']][] = $script_transl['errors'][7];
                        }
                    }
                    if ($row['sexper'] != 'G' && (empty($row['legrap_pf_nome']) || empty($row['legrap_pf_cognome']))) {
                        $error_transact[$row['idtes']][] = $script_transl['errors']['legrap_pf_nome'];
                    }
                } else {
                    // È un soggetto con codice fiscale senza partita IVA 
                    $resultcf = $nuw->check_TAXcode($row['codfis']);
                    if (strlen(trim($row['codfis'])) == 11) { // È una persona giuridica
                        $resultcf = $nuw->check_VAT_reg_no($row['codfis']);
                    }
                    if (empty($row['codfis'])) {
                        $error_transact[$row['idtes']][] = $script_transl['errors'][3];
                    } elseif ($row['sexper'] == 'G' and ! empty($resultcf)) {
                        $error_transact[$row['idtes']][] = $script_transl['errors'][4];
                    } elseif ($row['sexper'] == 'M' and empty($resultcf) and ( intval(substr($row['codfis'], 9, 2)) > 31 or
                            intval(substr($row['codfis'], 9, 2)) < 1)) {
                        $error_transact[$row['idtes']][] = $script_transl['errors'][5];
                    } elseif ($row['sexper'] == 'F' and empty($resultcf) and ( intval(substr($row['codfis'], 9, 2)) > 71 or
                            intval(substr($row['codfis'], 9, 2)) < 41)) {
                        $error_transact[$row['idtes']][] = $script_transl['errors'][6];
                    } elseif (!empty($resultcf)) {
                        $error_transact[$row['idtes']][] = $script_transl['errors'][7];
                    }
                    if ($row['sexper'] != 'G' && (empty($row['legrap_pf_nome']) || empty($row['legrap_pf_cognome']))) {
                        $error_transact[$row['idtes']][] = $script_transl['errors']['legrap_pf_nome'];
                    }
                }
                // fine controlli su CF e PI
                $castel_transact[$row['idtes']] = $row;
                $castel_transact[$row['idtes']]['riepil'] = 0;
                // determino il tipo di soggetto residente all'estero
                $castel_transact[$row['idtes']]['istat_country'] = 0;
                // --------- TIPIZZAZIONE DEI MOVIMENTI -----------------
                $castel_transact[$row['idtes']]['quadro'] = 'ZZ';
                $castel_transact[$row['idtes']]['tipo_documento'] = 'TD99'; // NON TIPIZZATO
                if ($row['country'] <> $admin_aziend['country']) { // ESTERO
                    $country = gaz_dbi_get_row($gTables['country'], "iso", $row['country']);
                    $castel_transact[$row['idtes']]['istat_country'] = $row['country'];
                    $castel_transact[$row['idtes']]['cod_ade'] = $row['cod_agenzia_entrate'];
                    if ($country['istat_area'] == 11) { // INTRACOMUNITARIO
                        $chk_intra = 'EU';
                    } else {
                        $chk_intra = 'ZZ'; // EXTRACEE
                    }
                    if ($row['operat'] == 1) { // Fattura
                        $castel_transact[$row['idtes']]['tipo_documento'] = 'TD10';
                    } else {                // Note
                        $castel_transact[$row['idtes']]['tipo_documento'] = 'TD04';
                    }
                } else { //ITALIA
                    if ($row['regiva'] == 4 && (!empty($row['n_fatt']))) { // se è un documento allegato ad uno scontrino utilizzo il numero fattura in tesdoc
                        $castel_transact[$row['idtes']]['numdoc'] = $row['n_fatt'] . ' scontr.n.' . $row['numdoc'];
                        $castel_transact[$row['idtes']]['seziva'] = '';
                    }
                    if ($row['pariva'] > 0) {
                        // RESIDENTE con partita IVA
                        if ($row['regiva'] < 6) { // VENDITE - Fatture Emesse o Note Emesse
                            if ($row['operat'] == 1) { // Fattura
                                $castel_transact[$row['idtes']]['quadro'] = 'FE';
                                $castel_transact[$row['idtes']]['tipo_documento'] = 'TD01';
                            } else {                // Note
                                $castel_transact[$row['idtes']]['quadro'] = 'NE';
                                $castel_transact[$row['idtes']]['tipo_documento'] = 'TD04';
                            }
                            // aggiungo la sezione al numero documento
                            if (!empty($castel_transact[$row['idtes']]['seziva'])) {
                                $castel_transact[$row['idtes']]['numdoc'] .= '/' . $castel_transact[$row['idtes']]['seziva'];
                            }
                        } elseif ($row['regiva'] == 6) {                // ACQUISTI - Fatture Ricevute o Note Ricevute
                            if ($row['operat'] == 1) { // Fattura
                                $castel_transact[$row['idtes']]['quadro'] = 'FR';
                                $castel_transact[$row['idtes']]['tipo_documento'] = 'TD01';
                            } else {                // Note
                                $castel_transact[$row['idtes']]['quadro'] = 'NR';
                                $castel_transact[$row['idtes']]['tipo_documento'] = 'TD04';
                            }
                        }
                    } else { // senza partita iva
                        if ($row['allegato'] == 2) { // riepilogativo es.scheda carburante
                            $castel_transact[$row['idtes']]['quadro'] = 'FR';
                            $castel_transact[$row['idtes']]['riepil'] = 1;
                        } elseif (empty($resultcf) && strlen($row['codfis']) == 11) { // associazioni/noprofit
                            // imposto il codice fiscale come partita iva
                            if ($row['regiva'] < 6) { // VENDITE - Fatture Emesse o Note Emesse
                                if ($row['operat'] == 1) { // Fattura
                                    $castel_transact[$row['idtes']]['quadro'] = 'FE';
                                    $castel_transact[$row['idtes']]['tipo_documento'] = 'TD01';
                                } else {                // Note
                                    $castel_transact[$row['idtes']]['quadro'] = 'NE';
                                    $castel_transact[$row['idtes']]['tipo_documento'] = 'TD04';
                                }
                                // aggiungo la sezione al numero documento
                                if (!empty($castel_transact[$row['idtes']]['seziva'])) {
                                    $castel_transact[$row['idtes']]['numdoc'] .= '/' . $castel_transact[$row['idtes']]['seziva'];
                                }
                            } elseif ($row['regiva'] == 6) {                // ACQUISTI - Fatture Ricevute o Note Ricevute
                                // nei quadri FR NR è possibile indicare la sola partita iva
                                $castel_transact[$row['idtes']]['pariva'] = $castel_transact[$row['idtes']]['codfis'];
                                $castel_transact[$row['idtes']]['codfis'] = 0;
                                if ($row['operat'] == 1) { // Fattura
                                    $castel_transact[$row['idtes']]['quadro'] = 'FR';
                                    $castel_transact[$row['idtes']]['tipo_documento'] = 'TD01';
                                } else {                // Note
                                    $castel_transact[$row['idtes']]['quadro'] = 'NR';
                                    $castel_transact[$row['idtes']]['tipo_documento'] = 'TD04';
                                }
                            }
                        } elseif (empty($resultcf) && strlen($row['codfis']) == 16) { // privato servito con fattura
                            if ($row['operat'] == 1) { // Fattura
                                $castel_transact[$row['idtes']]['quadro'] = 'FE';
                                $castel_transact[$row['idtes']]['tipo_documento'] = 'TD01';
                            } else {                // Note
                                $castel_transact[$row['idtes']]['quadro'] = 'NE';
                                $castel_transact[$row['idtes']]['tipo_documento'] = 'TD04';
                            }
                            // aggiungo la sezione al numero documento
                            if (!empty($castel_transact[$row['idtes']]['seziva'])) {
                                $castel_transact[$row['idtes']]['numdoc'] .= '/' . $castel_transact[$row['idtes']]['seziva'];
                            }
                        } else {                // privati con scontrino
                            $castel_transact[$row['idtes']]['quadro'] = 'DF';
                        }
                    }
                }

                // ricerco gli eventuali contratti che hanno generato la transazione
                $castel_transact[$row['idtes']]['n_rate'] = 1;
                $castel_transact[$row['idtes']]['contract'] = 0;
                if ($row['id_doc'] > 0) {
                    $contr_query = "SELECT " . $gTables['tesdoc'] . ".*," . $gTables['contract'] . ".* FROM " . $gTables['tesdoc'] . "
                            LEFT JOIN " . $gTables['contract'] . " ON " . $gTables['tesdoc'] . ".id_contract = " . $gTables['contract'] . ".id_contract 
                            WHERE id_tes = " . $row['id_doc'] . " AND (" . $gTables['tesdoc'] . ".id_contract > 0 AND tipdoc NOT LIKE 'VCO')";
                    $result_contr = gaz_dbi_query($contr_query);

                    if (gaz_dbi_num_rows($result_contr) > 0) {
                        $contr_r = gaz_dbi_fetch_array($result_contr);
                        // devo ottenere l'importo totale del contratto
                        $castel_transact[$row['idtes']]['contract'] = $contr_r['current_fee'] * $contr_r['months_duration'];
                        $castel_transact[$row['idtes']]['n_rate'] = 2;
                    }
                }
                // fine ricerca contratti

                if ($admin_aziend['country'] == $row['country']) {
                    if (strlen(trim($row['sedleg'])) > 4) {
                        if (preg_match("/([\w\,\.\s]+)([0-9]{5})[\s]+([\w\s\']+)\(([\w]{2})\)/", $row['sedleg'], $regs)) {
                            $castel_transact[$row['idtes']]['Indirizzo'] = $regs[1];
                            $castel_transact[$row['idtes']]['Comune'] = $regs[1];
                            $castel_transact[$row['idtes']]['Provincia'] = $regs[4];
                        } else {
                            $error_transact[$row['idtes']][] = $script_transl['errors'][10];
                        }
                    }
                }

                // inizio valorizzazione imponibile,imposta,senza_iva,art8
                $castel_transact[$row['idtes']]['operazioni_imponibili'] = 0;
                $castel_transact[$row['idtes']]['imposte_addebitate'] = 0;
                $castel_transact[$row['idtes']]['operazioni_esente'] = 0;
                $castel_transact[$row['idtes']]['operazioni_nonimp'] = 0;
                $castel_transact[$row['idtes']]['tipiva'] = '';
                $castel_transact[$row['idtes']]['esigibilita_iva'] = 'I'; // [I]: esigibilità immediata [D]: esigibilità differita [S] scissione dei pagamenti
                switch ($row['tipiva']) {
                    case 'I':
                    case 'D':
                    case 'T':
                    case 'R':
                        $castel_transact[$row['idtes']]['operazioni_imponibili'] = $value_imponi;
                        $castel_transact[$row['idtes']]['imposte_addebitate'] = $value_impost;
                        if ($value_impost == 0) {  //se non c'è imposta il movimento è sbagliato
                            $error_transact[$row['idtes']][] = $script_transl['errors'][11];
                        }
                        if ($row['tipiva'] == 'T') {  //scissione dei pagamenti
                            $castel_transact[$row['idtes']]['esigibilita_iva'] = 'S';
                        }
                        break;
                    case 'E':
                        $castel_transact[$row['idtes']]['tipiva'] = 3;
                        $castel_transact[$row['idtes']]['operazioni_esente'] = $value_imponi;
                        if ($value_impost != 0) {  //se c'è imposta il movimento è sbagliato
                            $error_transact[$row['idtes']][] = $script_transl['errors'][12];
                        }
                        break;
                    default : // ex case 'N':
                        $castel_transact[$row['idtes']]['tipiva'] = 2;
                        $castel_transact[$row['idtes']]['operazioni_nonimp'] = $value_imponi;
                        if ($value_impost != 0) {  //se c'è imposta il movimento è sbagliato
                            $error_transact[$row['idtes']][] = $script_transl['errors'][12];
                        }
                        break;
                }
            } else { //movimenti successivi al primo ma dello stesso id
                // inizio addiziona valori imponibile,imposta,esente,non imponibile
                switch ($row['tipiva']) {
                    case 'I':
                    case 'D':
                    case 'T':
                    case 'R':
                        $castel_transact[$row['idtes']]['operazioni_imponibili'] += $value_imponi;
                        $castel_transact[$row['idtes']]['imposte_addebitate'] += $value_impost;
                        if ($value_impost == 0) {  //se non c'è imposta il movimento è sbagliato
                            $error_transact[$row['idtes']][] = $script_transl['errors'][11];
                        }
                        if ($row['tipiva'] == 'T') {  //scissione dei pagamenti
                            $castel_transact[$row['idtes']]['esigibilita_iva'] = 'S';
                        } elseif ($row['tipiva'] == 'R') {  //INVERSIONE CONTABILE
                            $castel_transact[$row['idtes']]['esigibilita_iva'] = 'I';
                        }
                        break;
                    case 'E':
                        $castel_transact[$row['idtes']]['operazioni_esente'] += $value_imponi;
                        if ($value_impost != 0) {  //se c'è imposta il movimento è sbagliato
                            $error_transact[$row['idtes']][] = $script_transl['errors'][12];
                        }
                        break;
                    default : // ex case 'N':
                        $castel_transact[$row['idtes']]['operazioni_nonimp'] += $value_imponi;
                        if ($value_impost != 0) {  //se c'è imposta il movimento è sbagliato
                            $error_transact[$row['idtes']][] = $script_transl['errors'][12];
                        }
                        break;
                }
                // fine addiziona valori imponibile,imposta,esente,non imponibile
            }
            // fine valorizzazione imponibile,imposta,esente,non imponibile
            //  INIZIO creazione castelletto iva
            if (!isset($castel_transact[$row['idtes']]['riepilogo'][$row['codiva']])) {
                $castel_transact[$row['idtes']]['riepilogo'][$row['codiva']] = array('imponibile' => 0,
                    'imposta' => 0,
                    'aliquota' => $row['periva'],
                    'natura' => ($row['tipiva']=='R')?'':$row['fae_natura'],
                    'detraibile' => '',
                    'deducibile' => '',
                    'esigibilita' => 'I');
            }
            if ($row['tipiva'] == 'T') {  // se è una aliquota con scissione dei pagamenti
               $castel_transact[$row['idtes']]['riepilogo'][$row['codiva']]['esigibilita'] = 'S';
            } else if ($row['tipiva'] == 'D') { // se è una imposta indetraibile
                $castel_transact[$row['idtes']]['riepilogo'][$row['codiva']]['detraibile'] = 0.00;
            } elseif ($row['tipiva'] == 'R') {  //INVERSIONE CONTABILE
               $castel_transact[$row['idtes']]['riepilogo'][$row['codiva']]['esigibilita'] = 'I';
            } 
            $castel_transact[$row['idtes']]['riepilogo'][$row['codiva']]['imponibile'] += $row['imponi'];
            $castel_transact[$row['idtes']]['riepilogo'][$row['codiva']]['imposta'] += $row['impost'];
            // FINE creazione castelletto iva
            // 
            // Accumulo i valori dei beni e dei servizi per tipizzare i documenti intra
            if (!isset($castel_transact[$row['idtes']]['beni'])) {
                $castel_transact[$row['idtes']]['beni'] = 0.00;
            }
            if (!isset($castel_transact[$row['idtes']]['servizi'])) {
                $castel_transact[$row['idtes']]['servizi'] = 0.00;
            }
            if ($row['operation_type'] == 'SERVIZ' ||
                    $row['operation_type'] == 'ASNRES') {
                $castel_transact[$row['idtes']]['servizi'] += $row['imponi']; // servizio
            } else {
                $castel_transact[$row['idtes']]['beni'] += $row['imponi']; // bene
            }
            if ($chk_intra == 'EU') { // PARTNER INTRACOMUNITARIO
                if ($row['regiva'] == 6 && $row['operat'] == 1)  { // ACQUISTI INTRACOMUNITARIO tipizzo in base alla prevalenza
                    if ($castel_transact[$row['idtes']]['servizi'] > $castel_transact[$row['idtes']]['beni']) {
                        // C'è una prevalenza di SERVIZI
                        $castel_transact[$row['idtes']]['tipo_documento'] = 'TD11';
                    } else {
                        // acquisto di BENI
                        $castel_transact[$row['idtes']]['tipo_documento'] = 'TD10';
                    }
                }
            } elseif($chk_intra == 'ZZ') { // EXTRA COMUNITARIO
                $castel_transact[$row['idtes']]['tipo_documento'] = 'TD01';
            }
            $ctrl_id = $row['idtes'];
        }
        // se il precedente movimento non ha raggiunto l'importo lo elimino
        if (isset($castel_transact[$ctrl_id]) && $castel_transact[$ctrl_id]['operazioni_imponibili'] < 0.5 && $castel_transact[$ctrl_id]['operazioni_esente'] < 0.5 && $castel_transact[$ctrl_id]['operazioni_nonimp'] < 0.5 && $castel_transact[$ctrl_id]['contract'] < 0.5) {
            unset($castel_transact[$ctrl_id]);
            unset($error_transact[$ctrl_id]);
        }
        if (isset($castel_transact[$ctrl_id]) && $castel_transact[$ctrl_id]['quadro'] == 'DF' && $castel_transact[$ctrl_id]['operazioni_imponibili'] < $min_limit && $castel_transact[$ctrl_id]['contract'] < $min_limit) {
            unset($castel_transact[$ctrl_id]);
            unset($error_transact[$ctrl_id]);
        }
    } else {
        $error_transact[0] = $script_transl['errors'][15];
    }
    // fine creazione array righi ed errori
    return array($castel_transact, $error_transact);
}

if (isset($_POST['Update']) || isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (!isset($_POST['ritorno'])) {
// al primo accesso allo script
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
    $form['esterometro'] = 0;
    if (isset($_GET['esterometro'])) { // viene chiesto un esterometro
		$form['esterometro'] = 1;
	}
    if ((isset($_GET['Update']) && isset($_GET['id']))) { // è una modifica
        $cdf = gaz_dbi_get_row($gTables['comunicazioni_dati_fatture'], "id", intval($_GET['id']));
        $form['trimestre_semestre'] = $cdf['trimestre_semestre'];
        $form['anno'] = $cdf['anno'];
        $form['periodicita'] = $cdf['periodicita'];
    } else { // è un inserimento
// controllo se ad oggi è possibile fare una comunicazione
        $y = date('Y');
        $m = floor((date('m') - 1) / 3);
        if ($m == 0) {
            $y--;
            $m = 4;
        }
        $mese_trimestre_semestre = $y . $m;
        $form['trimestre_semestre'] = $m;
        $form['anno'] = $y;
        $form['periodicita'] = 'T';
// cerco l'ultimo file xml generato
        $rs_query = gaz_dbi_dyn_query("*", $gTables['comunicazioni_dati_fatture'],"nome_file_ZIP LIKE '%DF_Z%'", "anno DESC, trimestre_semestre DESC", 0, 1);
        $ultima_comunicazione = gaz_dbi_fetch_array($rs_query);
        if ($ultima_comunicazione) {
            $ultimo_trimestre_comunicato = $ultima_comunicazione['anno'] . $ultima_comunicazione['trimestre_semestre'];
        } else { // non ho mai fatto liquidazioni, propongo la prima da fare
            $ultimo_trimestre_comunicato = 0;
        }
        if ($ultimo_trimestre_comunicato >= $mese_trimestre_semestre) {
            $msg['err'][] = "eseguita";
        } else {
            // propongo una comunicazione in base ai dati che trovo sui movimenti IVA
        }
    }
} else { // nei post successivi (submit)
    $form['anno'] = intval($_POST['anno']);
    $form['trimestre_semestre'] = intval($_POST['trimestre_semestre']);
    $form['periodicita'] = substr($_POST['periodicita'], 0, 1);
    $form['esterometro'] = intval($_POST['esterometro']);
    $form['ritorno'] = $_POST['ritorno'];
    $form['hidden_req'] = htmlentities($_POST['hidden_req']);
    if (isset($_POST['Submit'])) {
        $queryData = createRowsAndErrors($form['anno'], $form['periodicita'], $form['trimestre_semestre'],boolval($form['esterometro']));
        if ($toDo == 'update') { // e' una modifica
            // aggiorno il database
            $id = array('anno', "'" . $form['anno'] . "' AND trimestre_semestre = '" . $form['trimestre_semestre'] . "'");
            require("../../library/include/agenzia_entrate.inc.php");
            $files = creaFileDAT20($admin_aziend, $queryData[0], substr($form['anno'], -2) . str_pad($form['trimestre_semestre'], 2, '0', STR_PAD_LEFT));
            foreach ($files['files'] as $n_f) {
				if (substr($n_f,-9,1)=='R'){
					$form['nome_file_DTR'] = $n_f;
				} else {
					$form['nome_file_DTE'] = $n_f;
				}
            }
            $form['nome_file_ZIP'] = $files['ZIP'];
            gaz_dbi_table_update('comunicazioni_dati_fatture', $id, $form);
            $msg['war'][] = "download";
        } else { // e' un'inserimento
            require("../../library/include/agenzia_entrate.inc.php");
            $files = creaFileDAT20($admin_aziend, $queryData[0], substr($form['anno'], -2) . str_pad($form['trimestre_semestre'], 2, '0', STR_PAD_LEFT),boolval($form['esterometro']));
            foreach ($files['files'] as $n_f) {
				if (substr($n_f,-9,1)=='R'){
					$form['nome_file_DTR'] = $n_f;
				} else {
					$form['nome_file_DTE'] = $n_f;
				}
            }
            $form['nome_file_ZIP'] = $files['ZIP'];
            gaz_dbi_table_insert('comunicazioni_dati_fatture', $form);
            $msg['war'][] = "download";
        }
    } elseif (isset($_POST['Download'])) {
        $file = DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . $admin_aziend['country'] . $admin_aziend['codfis'] . '_DF_Z' . substr($form['anno'], -2) . str_pad($form['trimestre_semestre'], 2, '0', STR_PAD_LEFT) . '.zip';
        header("Pragma: public", true);
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=" . basename($file));
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($file));
        die(file_get_contents($file));
        exit;
    }
}

if ((isset($_GET['Update']) && !isset($_GET['id']))) {
    header("Location: " . $form['ritorno']);
    exit;
}
require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new contabForm();
?>
<STYLE>
    .verticaltext {
        position: relative; 
        padding-left:50px;
        margin:1em 0;
        min-height:120px;
    }

    .verticaltext_content {
        -webkit-transform: rotate(-90deg);
        -moz-transform: rotate(-90deg);
        -ms-transform: rotate(-90deg);
        -o-transform: rotate(-90deg);
        filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=3);
        position: absolute;
        left: -130px;
        top: 300px;
        color: #000;
        text-transform: uppercase;
        font-size:30px;

    </STYLE>
    <form method="POST" name="form" enctype="multipart/form-data">
        <input type="hidden" name="hidden_req" value="<?php echo $form['hidden_req']; ?>">
        <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno">
        <input type="hidden" name="<?php echo ucfirst($toDo) ?>" value="">
        <div class="text-center"><b><?php echo $script_transl['title']; ?></b></div>
        <?php
        if (count($msg['err']) > 0) { // ho un errore
            $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
        } elseif (count($msg['war']) > 0) {
            $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
            ?>
            <input type="hidden" name="anno" value="<?php echo $form['anno']; ?>">
            <input type="hidden" name="periodicita" value="<?php echo $form['periodicita']; ?>">
            <input type="hidden" name="esterometro" value="<?php echo $form['esterometro']; ?>">
            <input type="hidden" name="trimestre_semestre" value="<?php echo $form['trimestre_semestre']; ?>">
            <?php
        } else {
            ?>
            <div class="panel panel-default gaz-table-form">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="anno" class="col-sm-4 control-label"><?php echo $script_transl['anno_imposta']; ?></label>
                                <?php
                                $gForm->selectNumber('anno', $form['anno'], 0, $form['anno'] - 5, $form['anno'] + 5, "col-sm-8", 'anno_imposta', 'style="max-width: 100px;"');
                                ?>
                            </div>
                        </div>
                    </div><!-- chiude row  -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="periodicita" class="col-sm-4 control-label"><?php echo $script_transl['periodicita']; ?></label>
                                <?php
                                $gForm->variousSelect('periodicita', $script_transl['periodicita_value'], $form['periodicita'], "col-sm-8", false, 'periodicita', false, 'style="max-width: 300px;"');
                                ?>
                            </div>
                        </div>
                    </div><!-- chiude row  -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="trimestre_semestre" class="col-sm-4 control-label"><?php echo $script_transl['trimestre_semestre']; ?></label>
                                <?php
                                $gForm->variousSelect('trimestre_semestre', $script_transl['trimestre_semestre_value'][$form['periodicita']], $form['trimestre_semestre'], "col-sm-8", false, 'trimestre_semestre', false, 'style="max-width: 300px;"');
                                ?>
                            </div>
                        </div>
                    </div><!-- chiude row  -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="esterometro" class="col-sm-4 control-label"><?php echo $script_transl['esterometro']; ?></label>
                                <?php
								$gForm->selectNumber('esterometro', $form["esterometro"],true, 0, 1, "col-sm-8",'esterometro', 'style="max-width: 100px;"');
                                ?>
                            </div>
                        </div>
                    </div><!-- chiude row  -->
                </div><!-- chiude container  -->
            </div><!-- chiude panel  -->
            <?php
            $queryData = createRowsAndErrors($form['anno'], $form['periodicita'], $form['trimestre_semestre'], boolval($form['esterometro']));
            if (count($queryData[1]) >= 1) { // ho degli errori
                echo '<div class="container">';
                foreach ($queryData[1] as $k => $v) {
                    echo '<div class="row alert alert-warning fade in" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Chiudi">
					<span aria-hidden="true">&times;</span>
				</button>';
                    if ($k == 0) {
                        echo '<span class="glyphicon glyphicon-alert" aria-hidden="true"></span> ERROR! => ' . $v . '<br>';
                    } else {
                        echo '<span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <a class="btn btn-xs btn-default" href="../inform/admin_anagra.php?id=' . $queryData[0][$k]['id_anagra'] . '&Update" > ' . $queryData[0][$k]['ragso1'] . '</a> ERROR! => ' . $v[0] . ' <a class="btn btn-xs btn-default" href="admin_movcon.php?Update&id_tes=' . $k . '">' . $queryData[0][$k]['numdoc'] . '</a><br>';
                    }
                    echo "</div>\n";
                }
                echo "</div>\n";
            } else {
                // bottoni differenziati in base al tipo ddi documento
                $td_class = array('TD01' => 'btn-default', 'TD04' => 'btn-warning', 'TD10' => 'btn-info', 'TD11' => 'btn-success', 'TD99' => 'btn-danger');
                ?> 
                <div class="panel panel-info">
                    <div id="gaz-responsive-table"  class="container-fluid">
                        <div class="col-xs-12 text-center bg-danger"><b>FILE DTE -FATTURE EMESSE</b></div>
                        <table class="table table-responsive table-striped table-condensed cf">
                            <thead>
                                <tr class="bg-success">              
                                    <th>
                                        <?php echo $script_transl["TipoDocumento"]; ?>
                                    </th>
                                    <th>
                                        <?php echo $script_transl["Numero"]; ?>
                                    </th>
                                    <th>
                                        <?php echo $script_transl["Data"]; ?>
                                    </th>
                                    <th>
                                        <?php echo $script_transl["DataRegistrazione"]; ?>
                                    </th>
                                    <th>
                                        <?php echo $script_transl["ImponibileImporto"]; ?>
                                    </th>
                                    <th>
                                        <?php echo $script_transl["NonImponibile"]; ?>
                                    </th>
                                    <th class="text-right">
                                        <?php echo $script_transl["Imposta"]; ?>
                                    </th>
                                    <th class="text-right">
                                        <?php echo $script_transl["Aliquota"]; ?>
                                    </th>
                                </tr>      
                            </thead>    
                            <tbody id="all_rows">

                                <?php
                                // CREO L'ARRAY ASSOCIATIVO DEI TIPI DOCUMENTI
                                $xml = simplexml_load_file('../../library/include/tipi_documenti.xml');
                                foreach ($xml as $d) {
                                    $v_td = get_object_vars($d);
                                    $td[$v_td['field'][0]] = $v_td['field'][1];
                                }
                                $td['TD99'] = 'NON INSERIBILE';
                                // FINE CREAZIONE
                                $ctrl_quadro = 'DTE';
                                $quadro = 'DTE';
                                $ctrl_partner = 0;
                                foreach ($queryData[0] as $k => $v) {
                                    if ($v['regiva'] == 6) {
                                        $quadro = 'DTR';
                                    } elseif ($v['regiva'] < 6) {
                                        $quadro = 'DTE';
                                    }
                                    if ($ctrl_quadro != $quadro) { // AL CAMBIO QUADRO STAMPO L'INTESTAZIONE
                                        ?>
                                    </tbody>     
                                </table>
                            </div>  
                        </div>
                        <div class="panel panel-default">
                            <div id="gaz-responsive-table"  class="container-fluid">
                                <div class="col-xs-12 text-center bg-danger"><b>FILE DTR - FATTURE RICEVUTE</b></div>
                                <table class="table table-responsive table-striped table-condensed cf">
                                    <thead>
                                        <tr class="bg-success">              
                                            <th>
                                                <?php echo $script_transl["TipoDocumento"]; ?>
                                            </th>
                                            <th>
                                                <?php echo $script_transl["Numero"]; ?>
                                            </th>
                                            <th>
                                                <?php echo $script_transl["Data"]; ?>
                                            </th>
                                            <th>
                                                <?php echo $script_transl["DataRegistrazione"]; ?>
                                            </th>
                                            <th>
                                                <?php echo $script_transl["ImponibileImporto"]; ?>
                                            </th>
                                            <th>
                                                <?php echo $script_transl["NonImponibile"]; ?>
                                            </th>
                                            <th class="text-right">
                                                <?php echo $script_transl["Imposta"]; ?>
                                            </th>
                                            <th class="text-right">
                                                <?php echo $script_transl["Aliquota"]; ?>
                                            </th>
                                        </tr>      
                                    </thead>    
                                    <tbody id="all_rows">

                                        <?php
                                    }
                                    if ($ctrl_partner <> $v['clfoco']) {
                                        ?>
                                        <tr>              
                                            <td colspan=7 data-title="<?php echo $script_transl["CessionarioCommittente"]; ?>" class="text-info">
                                                <b>   <?php echo $v["ragso1"] . ' ' . $v["ragso2"]; ?> </b> 
                                                <?php
                                                if ($v["pariva"] >= 1) {
                                                    echo $script_transl["partita_iva"] . ' ' . $v["pariva"];
                                                }
                                                echo ' ' . $script_transl["codice_fiscale"] . ' ' . $v["codfis"];
                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                    <tr>
                                        <td data-title="<?php echo $script_transl["TipoDocumento"]; ?>">
                                            <?php echo $v["tipo_documento"]; ?>  <a class="btn btn-xs <?php echo $td_class[$v["tipo_documento"]]; ?>" href="admin_movcon.php?Update&id_tes=<?php echo $k; ?>" title="<?php echo $v["caucon"]; ?>"><i class="glyphicon glyphicon-edit"></i>&nbsp;<?php echo ucfirst($td[$v["tipo_documento"]]) . ' prot.' . $v["protoc"]; ?></a>
                                        </td>
                                        <td data-title="<?php echo $script_transl["Numero"]; ?>" class="text-center">
                                            <?php echo $v["numdoc"]; ?>
                                        </td>
                                        <td data-title="<?php echo $script_transl["Data"]; ?>" class="text-center">
                                            <?php echo gaz_format_date($v["datdoc"]); ?>
                                        </td>
                                        <td data-title="<?php echo $script_transl["DataRegistrazione"]; ?>" class="text-center">
                                            <?php echo gaz_format_date($v["datreg"]); ?>
                                        </td>
                                        <td data-title="<?php echo $script_transl["ImponibileImporto"]; ?>" class="text-right">
                                            <?php echo gaz_format_number($v['operazioni_imponibili']); ?>
                                        </td>
                                        <td data-title="<?php echo $script_transl["NonImponibile"]; ?>"  class="<?php
										if ($v['operazioni_nonimp']>=0.01){
											echo 'text-right warning'; 
										} else {
											echo 'text-right'; 
										}
										?>">
                                            <?php echo gaz_format_number($v['operazioni_nonimp']); ?>
                                        </td>
                                        <td data-title="<?php echo $script_transl["Imposta"]; ?>"  class="text-right">
                                            <?php echo gaz_format_number($v['imposte_addebitate']); ?>
                                        </td>
                                        <td data-title="<?php echo $script_transl["Aliquota"]; ?>"  class="text-right">
                                            <?php echo floatval($v['periva']); ?>%
                                        </td>
                                    </tr> 
                                    <?php
                                    $ctrl_quadro = $quadro;
                                    $ctrl_partner = $v['clfoco'];
                                }
                                ?>
                            </tbody>     
                        </table>
                    </div>  
                </div>
                <?php
            }
        }
        if (count($msg['war']) > 0) {
            ?>
            <div class="col-sm-12 text-center"><input name="Download" type="submit" class="btn btn-success" value="<?php echo $admin_aziend['country'] . $admin_aziend['codfis'] . "_DF_Z" . substr($form['anno'], -2) . str_pad($form['trimestre_semestre'], 2, '0', STR_PAD_LEFT) . ".zip"; ?>" /></div>
        <?php } else if (count($msg['err']) == 0) {
            ?>
            <div class="col-sm-12 text-center"><input name="Submit" type="submit" class="btn btn-success" value="<?php echo $script_transl["ok"]; ?>" /></div>
            <?php } ?>   
    </form>
    <?php
    require("../../library/include/footer.php");
    ?>