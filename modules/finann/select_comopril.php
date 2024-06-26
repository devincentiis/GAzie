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
$msg = '';

require("../../library/include/check.inc.php");

if (!isset($_GET['anno'])) { //al primo accesso allo script suppongo che si debba produrre l'elenco per l'anno precedente
    $_GET['anno'] = date("Y") - 1;
    $_GET['min_limit'] = 2951;
    $Testa = getHeaderData();
    if ($admin_aziend['sexper'] != 'G') { // le persone fisiche hanno due campi separati
        $_GET['ragso1'] = $Testa['cognome'];
        $_GET['ragso2'] = $Testa['nome'];
    } else {
        $_GET['ragso1'] = strtoupper($admin_aziend['ragso1']);
        $_GET['ragso2'] = strtoupper($admin_aziend['ragso2']);
    }
}

function printTransact($transact, $error) {
    global $script_transl, $admin_aziend;
    $nrec = 0;
    echo "<td align=\"center\" class=\"FacetDataTD\" >N.Rec.</td>";
    echo "<td align=\"center\" class=\"FacetDataTD\" >N.Mov.</td>";
    echo "<td align=\"center\" class=\"FacetDataTD\" >" . $script_transl['sourcedoc'] . "</td>";
    echo "<td class=\"FacetDataTD\" >" . $script_transl['soggetto'] . "</td>";
    echo "<td align=\"center\" class=\"FacetDataTD\" >" . $script_transl['pariva'] . "</td>";
    echo "<td align=\"center\" class=\"FacetDataTD\" >" . $script_transl['codfis'] . "</td>";
    echo "<td align=\"center\" class=\"FacetDataTD\" >" . $script_transl['quadro'] . "</td>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >" . $script_transl['amount'] . "</td>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >" . $script_transl['tax'] . "</td>";
    echo "</tr>\n";
    foreach ($transact as $key => $value) {
        $nrec++;
        $totale = gaz_format_number($value['operazioni_imponibili'] + $value['operazioni_nonimp'] + $value['operazioni_esente']);
        $class = ' ';
        switch ($value['quadro']) {
            case 'FE':
                $class = 'style="color:#000000; background-color: #FFDDDD;"';
                break;
            case 'NE':
                $class = 'style="color:#000000; background-color: #DDFFDD;"';
                break;
            case 'FR':
                $class = 'style="color:#000000; background-color: #AFC8D8"';
                break;
            case 'NR':
                $class = 'style="color:#000000; background-color: #D3CFA8;"';
                break;
            case 'DF':
                $class = 'style="color:#000000; background-color: #DDDDFF;"';
                break;
            case 'SE':
                $class = 'style="color:#000000; background-color: #C8AFD8;"';
                break;
        }
        if (isset($error[$key])) {
            $class = ' class="FacetDataTDred" ';
        }
        echo "<tr>";
        echo "<td align=\"right\" $class>$nrec</a></td>";
        echo "<td align=\"center\" $class><a href=\"../contab/admin_movcon.php?id_tes=" . $value['id_tes'] . "&Update\">n." . $value['id_tes'] . " - " . gaz_format_date($value['datreg']) . "</a></td>";
        echo "<td align=\"center\" $class> sez." . $value['seziva'] . " n." . $value['numdoc'] . ' del ' . gaz_format_date($value['datdoc']) . "</td>";
        echo "<td $class>" . $value['ragso1'] . ' ' . $value['ragso2'] . "</td>";
        if ($value['riepil'] == 1) { // è un riepilogativo quindi il tracciato dovrà prevedere l'apposito flag
            echo "<td align=\"center\" colspan=\"2\" style=\"color:#000000; background-color: #DDADAF;\">" . $script_transl['riepil'] . "</td>";
        } else {
            echo "<td align=\"center\" $class>" . $value['iso'] . " " . $value['pariva'] . "</td>";
            echo "<td align=\"center\" $class>" . $value['codfis'] . "</td>";
        }
        echo "<td align=\"center\" $class>" . $value['quadro'] . "</td>";
        echo "<td align=\"right\" $class>$totale</td>";
        echo "<td align=\"right\" $class>" . gaz_format_number($value['imposte_addebitate']) . "</td>";
        echo "</tr>\n";
        if (isset($error[$key])) {
            foreach ($error[$key] as $val_err) {
                echo "<tr>";
                echo "<td class=\"FacetDataTDred\" colspan=\"10\">" . $val_err;
                if (substr($value['clfoco'], 0, 3) == $admin_aziend['mascli']) {
                    echo ", <a href='../vendit/admin_client";
                } else {
                    echo ", <a href='../acquis/admin_fornit";
                }
                echo ".php?codice=" . substr($value['clfoco'], 3, 6) . "&Update' target='_NEW'>" . $script_transl['errors'][0] . "</a><br /></td>
                               </tr>\n";
            }
        }
    }
}

function getHeaderData() {
    global $admin_aziend, $gTables;
    // preparo il nome dell'azienda e faccio i controlli di errore
    $Testa['anno'] = intval($_GET['anno']);
    $Testa['pariva'] = $admin_aziend['pariva'];
    $Testa['codfis'] = $admin_aziend['codfis'];
    $Testa['ateco'] = $admin_aziend['cod_ateco'];
    $Testa['e_mail'] = $admin_aziend['e_mail'];
    $Testa['telefono'] = filter_var($admin_aziend['telefo'], FILTER_SANITIZE_NUMBER_INT);
    $Testa['fax'] = filter_var($admin_aziend['fax'], FILTER_SANITIZE_NUMBER_INT);
    // aggiungo l'eventuale intermediario in caso di installazione "da commercialista"
    $intermediary_code = gaz_dbi_get_row($gTables['config'], 'variable', 'intermediary');
    if ($intermediary_code['cvalue'] > 0) {
        $intermediary = gaz_dbi_get_row($gTables['aziend'], 'codice', $intermediary_code['cvalue']);
        $Testa['intermediario'] = $intermediary['codfis'];
    } else {
        $Testa['intermediario'] = '';
    }

    if ($admin_aziend['sexper'] == 'G') {
        // persona giuridica
        if (strlen($Testa['codfis']) <> 11) {
            $Testa['fatal_error'] = 'codfis';
        }
        if (empty($admin_aziend['ragso1']) and empty($admin_aziend['ragso2'])) {
            $Testa['fatal_error'] = 'ragsoc';
        } else {
            $Testa['ragsoc'] = strtoupper($admin_aziend['ragso1'] . " " . $admin_aziend['ragso2']);
        }
        if (empty($admin_aziend['citspe'])) {
            $Testa['fatal_error'] = 'citspe';
        } else {
            $Testa['sedleg'] = strtoupper($admin_aziend['citspe']);
        }
        if (strlen(trim($admin_aziend['prospe'])) < 2) {
            $Testa['fatal_error'] = 'prospe';
        } else {
            $Testa['proleg'] = strtoupper($admin_aziend['prospe']);
        }
    } elseif ($admin_aziend['sexper'] == 'F' or $admin_aziend['sexper'] == 'M') {
        // persona fisica
        $gn = substr($Testa['codfis'], 9, 2);
        if (($admin_aziend['sexper'] == 'M' and ( $gn < 1 or $gn > 31))
                or ( $admin_aziend['sexper'] == 'F' and ( $gn < 41 or $gn > 71))) {
            $Testa['fatal_error'] = 'sexper';
        }
        $Testa['sesso'] = strtoupper($admin_aziend['sexper']);
        if (!empty($admin_aziend['legrap_pf_nome'])) {
            // persona fisica con cognome e nome non separati nel campo legale rappresentante
            $Testa['cognome'] = '';
            $Testa['nome'] = '';
            $line = strtoupper($admin_aziend['legrap_pf_nome']);
            $nuova = explode(' ', chop($line));
            $lenght = count($nuova);
            $middle = intval(($lenght + 1) / 2);
            for ($i = 0; $i < $lenght; $i++) {
                if ($i < $middle) {
                    $Testa['cognome'] .= $nuova[$i] . " ";
                } else {
                    $Testa['nome'] .= $nuova[$i] . " ";
                }
            }
        } elseif (!empty($admin_aziend['ragso1']) and ! empty($admin_aziend['ragso2'])) {
            // persona fisica con cognome e nome separati tra ragso1 e ragso2
            $Testa['cognome'] = strtoupper($admin_aziend['ragso1']);
            $Testa['nome'] = strtoupper($admin_aziend['ragso2']);
        } else {
            $Testa['fatal_error'] = 'legrap_pf_nome';
        }
        if (empty($admin_aziend['luonas'])) {
            $Testa['fatal_error'] = 'luonas';
        } else {
            $Testa['luonas'] = strtoupper($admin_aziend['luonas']);
        }
        if (strlen(trim($admin_aziend['pronas'])) < 2) {
            $Testa['fatal_error'] = 'pronas';
        } else {
            $Testa['pronas'] = strtoupper($admin_aziend['pronas']);
        }
        $d = substr($admin_aziend['datnas'], 8, 2);
        $m = substr($admin_aziend['datnas'], 5, 2);
        $Y = substr($admin_aziend['datnas'], 0, 4);
        if (checkdate($m, $d, $Y)) {
            $Testa['datnas'] = $admin_aziend['datnas'];
        } else {
            $Testa['fatal_error'] = 'datnas';
        }
    } else {
        $Testa['fatal_error'] = 'nosexper';
    }
    return $Testa;
}

function createRowsAndErrors($min_limit) {
    global $gTables, $admin_aziend, $script_transl;
    $nuw = new check_VATno_TAXcode();
    $sqlquery = "SELECT " . $gTables['rigmoi'] . ".*, ragso1,ragso2,sedleg,sexper,indspe,regiva,allegato,
               citspe,prospe,country,codfis,pariva," . $gTables['tesmov'] . ".clfoco," . $gTables['tesmov'] . ".protoc,
               " . $gTables['tesmov'] . ".numdoc," . $gTables['tesmov'] . ".datdoc," . $gTables['tesmov'] . ".seziva,
               " . $gTables['tesmov'] . ".caucon," . $gTables['tesdoc'] . ".numfat AS n_fatt,
			   " . $gTables['tesmov'] . ".datreg,datnas,luonas,pronas,counas,id_doc,iso,black_list,cod_agenzia_entrate,
               operat, impost AS imposta," . $gTables['rigmoi'] . ".id_tes AS idtes,
               imponi AS imponibile FROM " . $gTables['rigmoi'] . "
               LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoi'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes
               LEFT JOIN " . $gTables['tesdoc'] . " ON " . $gTables['tesmov'] . ".id_doc = " . $gTables['tesdoc'] . ".id_tes
               LEFT JOIN " . $gTables['aliiva'] . " ON " . $gTables['rigmoi'] . ".codiva = " . $gTables['aliiva'] . ".codice
               LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesmov'] . ".clfoco = " . $gTables['clfoco'] . ".codice
               LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['anagra'] . ".id = " . $gTables['clfoco'] . ".id_anagra
               LEFT JOIN " . $gTables['country'] . " ON " . $gTables['anagra'] . ".country = " . $gTables['country'] . ".iso
               WHERE YEAR(" . $gTables['tesmov'] . ".datreg) = " . intval($_GET['anno']) . "
                 AND ( " . $gTables['tesmov'] . ".clfoco LIKE '" . $admin_aziend['masfor'] . "%' OR " . $gTables['tesmov'] . ".clfoco LIKE '" . $admin_aziend['mascli'] . "%')
                 AND " . $gTables['clfoco'] . ".allegato > 0 
               ORDER BY regiva,operat,country,datreg,seziva,protoc";
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
                // se il precedente movimento non ha raggiunto l'importo lo elimino
                if (isset($castel_transact[$ctrl_id]) && $castel_transact[$ctrl_id]['operazioni_imponibili'] < 0.5 && $castel_transact[$ctrl_id]['operazioni_esente'] < 0.5 && $castel_transact[$ctrl_id]['operazioni_nonimp'] < 0.5 && $castel_transact[$ctrl_id]['contract'] < 0.5) {
                    unset($castel_transact[$ctrl_id]);
                    unset($error_transact[$ctrl_id]);
                }
                if (isset($castel_transact[$ctrl_id]) && $castel_transact[$ctrl_id]['quadro'] == 'DF' && $castel_transact[$ctrl_id]['operazioni_imponibili'] < $min_limit && $castel_transact[$ctrl_id]['contract'] < $min_limit) {
                    unset($castel_transact[$ctrl_id]);
                    unset($error_transact[$ctrl_id]);
                }
                // inizio controlli su CF e PI
                $resultpi = $nuw->check_VAT_reg_no($row['pariva']);
                $resultcf = $nuw->check_VAT_reg_no($row['codfis']);
                if ($admin_aziend['country'] != $row['country']) {
                    // È uno non residente 
                    if ($row['country'] == 'SM') {
                        // SAN MARINO 
                    } else {
                        if (!empty($row['datnas'])) { // È un persona fisica straniera
                            if (empty($row['pronas']) || empty($row['luonas']) || empty($row['counas'])) {
                                $error_transact[$row['idtes']][] = $script_transl['errors'][9];
                            }
                        }
                    }
                } elseif (empty($resultpi) && !empty($row['pariva'])) {
                    // ha la partita IVA ed è giusta 
                    if (strlen(trim($row['codfis'])) == 11) {
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
                }
                // fine controlli su CF e PI
                $castel_transact[$row['idtes']] = $row;
                $castel_transact[$row['idtes']]['riepil'] = 0;
                // determino il tipo di soggetto residente all'estero
                $castel_transact[$row['idtes']]['istat_country'] = 0;
                // --------- TIPIZZAZIONE DEI MOVIMENTI -----------------
                $castel_transact[$row['idtes']]['quadro'] = 'ZZ';
                if ($row['country'] <> $admin_aziend['country']) {
                        $castel_transact[$row['idtes']]['istat_country'] = $row['country'];
                        $castel_transact[$row['idtes']]['cod_ade'] = $row['cod_agenzia_entrate'];
                    if ($row['country'] == 'SM') {
                        // SAN MARINO 
                        $castel_transact[$row['idtes']]['quadro'] = 'SE';
                    } else {
                        // NON RESIDENTE
                        $castel_transact[$row['idtes']]['quadro'] = 'FN';
                    }
                } else {
                    if ($row['regiva'] == 4 && (!empty($row['n_fatt']))) { // se è un documento allegato ad uno scontrino utilizzo il numero fattura in tesdoc
                        $castel_transact[$row['idtes']]['numdoc'] = $row['n_fatt'] . ' scontr.n.' . $row['numdoc'];
                        $castel_transact[$row['idtes']]['seziva'] = '';
                    }
                    if ($row['pariva'] > 0) {
                        // RESIDENTE con partita IVA
                        if ($row['regiva'] < 6) { // VENDITE - Fatture Emesse o Note Emesse
                            if ($row['operat'] == 1) { // Fattura
                                $castel_transact[$row['idtes']]['quadro'] = 'FE';
                            } else {                // Note
                                $castel_transact[$row['idtes']]['quadro'] = 'NE';
                            }
                        } elseif ($row['regiva'] == 6) {                // ACQUISTI - Fatture Ricevute o Note Ricevute
                            if ($row['operat'] == 1) { // Fattura
                                $castel_transact[$row['idtes']]['quadro'] = 'FR';
                            } else {                // Note
                                $castel_transact[$row['idtes']]['quadro'] = 'NR';
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
                                } else {                // Note
                                    $castel_transact[$row['idtes']]['quadro'] = 'NE';
                                }
                            } elseif ($row['regiva'] == 6) {                // ACQUISTI - Fatture Ricevute o Note Ricevute
                                // nei quadri FR NR è possibile indicare la sola partita iva
                                $castel_transact[$row['idtes']]['pariva'] = $castel_transact[$row['idtes']]['codfis'];
                                $castel_transact[$row['idtes']]['codfis'] = 0;
                                if ($row['operat'] == 1) { // Fattura
                                    $castel_transact[$row['idtes']]['quadro'] = 'FR';
                                } else {                // Note
                                    $castel_transact[$row['idtes']]['quadro'] = 'NR';
                                }
                            }
                        } elseif (empty($resultcf) && strlen($row['codfis']) == 16) { // privato servito con fattura
                            if ($row['operat'] == 1) { // Fattura
                                $castel_transact[$row['idtes']]['quadro'] = 'FE';
                            } else {                // Note
                                $castel_transact[$row['idtes']]['quadro'] = 'NE';
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
                if (!empty($row['sedleg'])) {
                    if (preg_match("/([\w\,\.\s]+)([0-9]{5})[\s]+([\w\s\']+)\(([\w]{2})\)/", $row['sedleg'], $regs)) {
                        $castel_transact[$row['idtes']]['Indirizzo'] = $regs[1];
                        $castel_transact[$row['idtes']]['Comune'] = $regs[3];
                        $castel_transact[$row['idtes']]['Provincia'] = $regs[4];
                    } else {
                        $error_transact[$row['idtes']][] = $script_transl['errors'][10];
                    }
                }
                // inizio valorizzazione imponibile,imposta,senza_iva,art8
                $castel_transact[$row['idtes']]['operazioni_imponibili'] = 0;
                $castel_transact[$row['idtes']]['imposte_addebitate'] = 0;
                $castel_transact[$row['idtes']]['operazioni_esente'] = 0;
                $castel_transact[$row['idtes']]['operazioni_nonimp'] = 0;
                $castel_transact[$row['idtes']]['tipiva'] = 1;
                switch ($row['tipiva']) {
                    case 'I':
                    case 'D':
                        $castel_transact[$row['idtes']]['operazioni_imponibili'] = $value_imponi;
                        $castel_transact[$row['idtes']]['imposte_addebitate'] = $value_impost;
                        if ($value_impost == 0) {  //se non c'è imposta il movimento è sbagliato
                            $error_transact[$row['idtes']][] = $script_transl['errors'][11];
                        }
                        break;
                    case 'E':

                        $castel_transact[$row['idtes']]['tipiva'] = 3;
                        $castel_transact[$row['idtes']]['operazioni_esente'] = $value_imponi;
                        if ($value_impost != 0) {  //se c'è imposta il movimento è sbagliato
                            $error_transact[$row['idtes']][] = $script_transl['errors'][12];
                        }
                        break;
                    case 'N':
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
                        $castel_transact[$row['idtes']]['operazioni_imponibili'] += $value_imponi;
                        $castel_transact[$row['idtes']]['imposte_addebitate'] += $value_impost;
                        if ($value_impost == 0) {  //se non c'è imposta il movimento è sbagliato
                            $error_transact[$row['idtes']][] = $script_transl['errors'][11];
                        }
                        break;
                    case 'E':
                        $castel_transact[$row['idtes']]['operazioni_esente'] += $value_imponi;
                        if ($value_impost != 0) {  //se c'è imposta il movimento è sbagliato
                            $error_transact[$row['idtes']][] = $script_transl['errors'][12];
                        }
                        break;
                    case 'N':
                        $castel_transact[$row['idtes']]['operazioni_nonimp'] += $value_imponi;
                        if ($value_impost != 0) {  //se c'è imposta il movimento è sbagliato
                            $error_transact[$row['idtes']][] = $script_transl['errors'][12];
                        }
                        break;
                }
                // fine addiziona valori imponibile,imposta,esente,non imponibile
            }


            // fine valorizzazione imponibile,imposta,esente,non imponibile
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

if (isset($_GET['file_agenzia'])) {
    $year = intval($_GET['anno']);
    $queryData = createRowsAndErrors(intval($_GET['min_limit']));
    require("../../library/include/agenzia_entrate.inc.php");
    $annofornitura = date("y");
    // --- preparo gli array da passare alla classe AgenziaEntrate a secondo della scelta effettuata
    $Testa = getHeaderData();
    $agenzia = new AgenziaEntrate;

    // Impostazione degli header per l'opozione "save as" dello standard input che verrà generato
    header('Content-Type: text/x-art21');
    header("Content-Disposition: attachment; filename=" . $admin_aziend['codfis'] . $year . "NSP00.nsp");
    header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // per poter ripetere l'operazione di back-up più volte.
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
    } else {
        header('Pragma: no-cache');
    }
    if ($year > 2011) {
        $content = $agenzia->creaFileART21_poli($Testa, $queryData[0]);
    } else {
        $content = $agenzia->creaFileART21($Testa, $queryData[0]);
    }
    print $content;
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain();
echo "<form method=\"GET\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . ucfirst($script_transl['title']) . "</div>\n";
echo "<table class=\"Tmiddle table-striped\" align=\"center\">\n";
if (!empty($msg)) {
    $message = "";
    $rsmsg = array_slice(explode('+', chop($msg)), 0, -1);
    foreach ($rsmsg as $value) {
        $message .= $script_transl['error'] . "! -> ";
        $rsval = explode('-', chop($value));
        foreach ($rsval as $valmsg) {
            $message .= $script_transl[$valmsg] . " ";
        }
        $message .= "<br />";
    }
    echo '<tr><td colspan="5" class="FacetDataTDred">' . $message . "</td></tr>\n";
}
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl['limit'] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "<input type=\"text\" name=\"min_limit\" value=\"" . $_GET['min_limit'] . "\" align=\"right\" maxlength=\"10\"  /></td>\n";
echo "</tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl['ragso1'] . "</td>
     <td class=\"FacetDataTD\"><input type=\"text\" value=\"" . $_GET['ragso1'] . "\" maxlength=\"50\"  name=\"ragso1\"></td></td></tr>\n";
echo "</tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl['ragso2'] . "</td>
     <td class=\"FacetDataTD\"><input type=\"text\" value=\"" . $_GET['ragso2'] . "\" maxlength=\"50\"  name=\"ragso2\"></td></tr>\n";
echo "</tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl['year'] . "</td><td class=\"FacetDataTD\" colspan=\"3\">";
echo "\t <select name=\"anno\" class=\"FacetSelect\" >\n";
for ($counter = date("Y") - 10; $counter <= date("Y") + 10; $counter++) {
    $selected = "";
    if ($counter == $_GET['anno'])
        $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select></td></tr>\n";
echo "<tr>\n
     <td class=\"FacetFieldCaptionTD\"><input type=\"submit\" name=\"Return\" value=\"" . ucfirst($script_transl['return']) . "\"></td>\n
     <td align=\"right\" class=\"FacetFooterTD\"><input type=\"submit\" name=\"view\" value=\"" . ucfirst($script_transl['view']) . "\"></td>\n
     </tr>\n";
echo "</table>\n";
if (isset($_GET['view'])) {
    $queryData = createRowsAndErrors(intval($_GET['min_limit']));
    $Testa = getHeaderData();
    if (!isset($queryData[1][0])) { // nessun errore sulle impostazioni aziendali
        echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['view'] . "</div>";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        echo "<tr>";
        echo "<td colspan=\"1\"></td>";
        echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">" . $script_transl['codfis'] . "</td>";
        echo "<td colspan=\"2\" class=\"FacetDataTD\">" . $Testa['codfis'] . "</td>";
        echo "</tr>\n";
        echo "<tr>";
        echo "<td colspan=\"1\"></td>";
        echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">" . $script_transl['pariva'] . "</td>";
        echo "<td colspan=\"2\" class=\"FacetDataTD\">" . $Testa['pariva'] . "</td>";
        echo "</tr>\n";
        echo "<tr>";
        echo "<td colspan=\"1\"></td>";
        echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">" . $script_transl['sex'] . "</td>";
        echo "<td colspan=\"2\" class=\"FacetDataTD\">" . $admin_aziend['sexper'] . "</td>";
        if (!isset($Testa['sesso'])) { // È una persona giuridica
            echo "<tr>";
            echo "<td colspan=\"1\"></td>";
            echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">" . $script_transl['sedleg'] . "</td>";
            echo "<td colspan=\"2\" class=\"FacetDataTD\">" . $Testa['sedleg'] . "</td>";
            echo "</tr>\n";
            echo "<tr>";
            echo "<td colspan=\"1\"></td>";
            echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">" . $script_transl['proleg'] . "</td>";
            echo "<td colspan=\"2\" class=\"FacetDataTD\">" . $Testa['proleg'] . "</td>";
            echo "</tr>\n";
        } else {     // persona fisica
            echo "<tr>";
            echo "<td colspan=\"1\"></td>";
            echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">" . $script_transl['datnas'] . "</td>";
            echo "<td colspan=\"2\" class=\"FacetDataTD\">" . gaz_format_date($Testa['datnas']) . "</td>";
            echo "</tr>\n";
            echo "<tr>";
            echo "<td colspan=\"1\"></td>";
            echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">" . $script_transl['luonas'] . "</td>";
            echo "<td colspan=\"2\" class=\"FacetDataTD\">" . $Testa['luonas'] . "</td>";
            echo "</tr>\n";
            echo "<tr>";
            echo "<td colspan=\"1\"></td>";
            echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">" . $script_transl['pronas'] . "</td>";
            echo "<td colspan=\"2\" class=\"FacetDataTD\">" . $Testa['pronas'] . "</td>";
            echo "</tr>\n";
        }
        if (!empty($queryData[1])) { // ci sono errori tra i movimenti
            echo "<tr>\n
                    <td class=\"FacetDataTDred\" colspan=\"5\">" . $script_transl['errors'][13] . ":</td>
                    </tr>\n";
        } elseif (isset($Testa['fatal_error'])) {
            echo "<tr>\n
                   <td align=\"center\" class=\"FacetFieldCaptionTD\" colspan=\"2\"><input type=\"submit\" name=\"pdf\" value=\"PDF\"></td>\n
                   <td align=\"center\" class=\"FacetDataTDred\" colspan=\"6\">" . $script_transl['errors'][15] . "</td>\n
                   </tr>\n";
        } else {
            echo "<tr>\n
                   <td align=\"center\" class=\"FacetFieldCaptionTD\" colspan=\"8\"><input type=\"submit\" name=\"file_agenzia\" value=\"File Internet (ART21)\"></td>\n
                   </tr>\n";
        }
        printTransact($queryData[0], $queryData[1]);
        echo "</table>";
    } else {
        echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $queryData[1][0] . "</div>";
    }
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>