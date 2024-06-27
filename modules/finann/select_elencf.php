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
  scriva   alla   Free  Software Foundation,  Inc.,   59
  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
require("../../library/include/datlib.inc.php");

$admin_aziend = checkAdmin();
$msg = '';

require("../../library/include/check.inc.php");

if (!isset($_GET['anno'])) { //al primo accesso allo script suppongo che si debba produrre l'elenco per l'anno precedente
    $_GET['anno'] = date("Y") - 1;
    $_GET['partner'] = 3;
}

function printPartners($partner, $error, $total, $tipo) {
    global $script_transl;
    echo "<tr>\n
               <td class=\"FacetFormHeaderFont\" align=\"center\" colspan=\"10\">" . $script_transl[$tipo + 1] . "</td>
               </tr>\n";
    echo "<tr>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >N.</td>";
    echo "<td colspan=\"3\" class=\"FacetDataTD\" >Rag.Sociale</td>";
    echo "<td colspan=\"3\" class=\"FacetDataTD\" >Indirizzo</td>";
    echo "<td colspan=\"2\" class=\"FacetDataTD\" >Comune</td>";
    echo "<td class=\"FacetDataTD\" >Prov.</td>";
    echo "</tr>\n";
    echo "<tr>";
    echo "<td align=\"right\" class=\"FacetDataTD\" ></td>";
    echo "<td align=\"center\" class=\"FacetDataTD\" >PF</td>";
    echo "<td class=\"FacetDataTD\" >Codice Fiscale Partita I.V.A.</td>";
    echo "<td class=\"FacetDataTD\" >N.Doc.</td>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >Imponibile</td>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >Imposta</td>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >Non Imponibile</td>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >Esente</td>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >Totale</td>";
    echo "</tr>\n";
    foreach ($partner as $key => $value) {
        if (isset($error[$key])) {
            $class = ' class="FacetDataTDred" ';
        } else {
            $class = ' ';
        }
        $totale = gaz_format_number($value['operazioni_imponibili'] +
                $value['imposte_addebitate'] +
                $value['operazioni_nonimp'] +
                $value['operazioni_esente']);
        echo "<tr>";
        echo "<td align=\"right\" $class>" . $value['Progressivo'] . "</td>";
        echo "<td colspan=\"3\" $class>" . $value['Rag_Sociale'] . "</td>";
        echo "<td colspan=\"3\" $class>" . $value['Indirizzo'] . "</td>";
        echo "<td colspan=\"2\" $class>" . $value['Comune'] . "</td>";
        echo "<td $class>" . $value['Provincia'] . "</td>";
        echo "</tr>\n";
        echo "<tr>";
        echo "<td align=\"right\" $class></td>";
        echo "<td align=\"right\" $class>" . $value['persona_fisica'] . " </td>";
        echo "<td $class>" . $value['Codice_Fiscale'] . " " . $value['Partita_IVA'] . "</td>";
        echo "<td $class>" . $value['Num_Documenti'] . "</td>";
        echo "<td align=\"right\" $class>" . gaz_format_number($value['operazioni_imponibili']) . "</td>";
        echo "<td align=\"right\" $class>" . gaz_format_number($value['imposte_addebitate']) . "</td>";
        echo "<td align=\"right\" $class>" . gaz_format_number($value['operazioni_nonimp']) . "</td>";
        echo "<td align=\"right\" $class>" . gaz_format_number($value['operazioni_esente']) . "</td>";
        echo "<td align=\"right\" $class>$totale</td>";
        echo "</tr>\n";
        if (isset($error[$key])) {
            foreach ($error[$key] as $value) {
                echo "<tr>";
                echo "<td class=\"FacetDataTDred\" colspan=\"10\">" . $value;
                if ($tipo == 1) {
                    echo ", <a href='../vendit/admin_client";
                } else {
                    echo ", <a href='../acquis/admin_fornit";
                }
                echo ".php?codice=" . substr($key, -6) . "&Update' target='_NEW'>$script_transl[20]</a><br /></td>
                               </tr>\n";
            }
        }
    }
    echo "<tr  class=\"FacetFieldCaptionTD\" >";
    echo "<td align=\"right\" class=\"FacetDataTD\" colspan=\"4\">Totali:</td>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >" . gaz_format_number($total['operazioni_imponibili']) . "</td>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >" . gaz_format_number($total['imposte_addebitate']) . "</td>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >" . gaz_format_number($total['operazioni_nonimp']) . "</td>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >" . gaz_format_number($total['operazioni_esente']) . "</td>";
    echo "<td align=\"right\" class=\"FacetDataTD\" >" . gaz_format_number(array_sum($total)) . "</td>";
    echo "<td class=\"FacetDataTD\"></td>";
    echo "</tr>\n";
}

function getHeaderData() {
    global $admin_aziend;
    // preparo il nome dell'azienda e faccio i controlli di errore
    $Testa['anno'] = $_GET['anno'];
    $Testa['pariva'] = $admin_aziend['pariva'];
    $Testa['codfis'] = $admin_aziend['codfis'];
    if ($admin_aziend['sexper'] == 'G') {
        // persona giuridica
        if (strlen($Testa['codfis']) <> 11) {
            $Testa['fatal_error'] = '';
        }
        if (empty($admin_aziend['ragso1']) and empty($admin_aziend['ragso2'])) {
            $Testa['fatal_error'] = '';
        } else {
            $Testa['ragsoc'] = strtoupper($admin_aziend['ragso1'] . " " . $admin_aziend['ragso2']);
        }
        if (empty($admin_aziend['citspe'])) {
            $Testa['fatal_error'] = '';
        } else {
            $Testa['sedleg'] = strtoupper($admin_aziend['citspe']);
        }
        if (strlen(trim($admin_aziend['prospe'])) < 2) {
            $Testa['fatal_error'] = '';
        } else {
            $Testa['proleg'] = strtoupper($admin_aziend['prospe']);
        }
    } elseif ($admin_aziend['sexper'] == 'F' or $admin_aziend['sexper'] == 'M') {
        // persona fisica
        $gn = substr($Testa['codfis'], 9, 2);
        if (($admin_aziend['sexper'] == 'M' and ( $gn < 1 or $gn > 31))
                or ( $admin_aziend['sexper'] == 'F' and ( $gn < 41 or $gn > 71))) {
            $Testa['fatal_error'] = '';
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
            $Testa['fatal_error'] = '';
        }
        if (empty($admin_aziend['luonas'])) {
            $Testa['fatal_error'] = 'Manca il luogo di nascita in configurazione azienda';
        } else {
            $Testa['luonas'] = strtoupper($admin_aziend['luonas']);
        }
        if (strlen(trim($admin_aziend['pronas'])) < 2) {
            $Testa['fatal_error'] = '';
        } else {
            $Testa['pronas'] = strtoupper($admin_aziend['pronas']);
        }
        $d = substr($admin_aziend['datnas'], 8, 2);
        $m = substr($admin_aziend['datnas'], 5, 2);
        $Y = substr($admin_aziend['datnas'], 0, 4);
        if (checkdate($m, $d, $Y)) {
            $Testa['datnas'] = $d . $m . $Y;
        } else {
            $Testa['fatal_error'] = '';
        }
    } else {
        $Testa['fatal_error'] = '';
    }
    return $Testa;
}

function createRowsAndErrors($partner) {
    global $gTables, $admin_aziend, $script_transl;
    if ($partner == 1) {
        $search_partner = " clfoco LIKE '" . $admin_aziend['mascli'] . "%'";
    } else {
        $search_partner = " clfoco LIKE '" . $admin_aziend['masfor'] . "%'";
    }
    $sqlquery = "SELECT COUNT(" . $gTables['rigmoi'] . ".id_tes) AS numdoc,
           codiva," . $gTables['aliiva'] . ".tipiva,SUM(impost - impost*2*((caucon LIKE '_NC') or (caucon LIKE '_FC' ))) AS imposta,
           SUM(imponi - imponi*2*((caucon LIKE '_NC') or (caucon LIKE '_FC' ))) AS imponibile,clfoco,CONCAT(ragso1,' ',ragso2) AS ragsoc, sedleg,sexper,indspe,
           citspe,prospe,codfis,pariva,allegato,operat
           FROM " . $gTables['rigmoi'] . "
           LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoi'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes
           LEFT JOIN " . $gTables['aliiva'] . " ON " . $gTables['rigmoi'] . ".codiva = " . $gTables['aliiva'] . ".codice
           LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesmov'] . ".clfoco = " . $gTables['clfoco'] . ".codice
           LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['anagra'] . ".id = " . $gTables['clfoco'] . ".id_anagra
           WHERE allegato = 1 AND YEAR(datdoc) = " . intval($_GET['anno']) . " AND $search_partner
           GROUP BY clfoco, codiva
           ORDER BY ragso1";
    $result = gaz_dbi_query($sqlquery);
    $castel_partners = array();
    $error_partners = array();
    $total_column = array();
    if (gaz_dbi_num_rows($result) > 0) {
        // inizio creazione array righi ed errori
        $total_column['operazioni_imponibili'] = 0.00;
        $total_column['imposte_addebitate'] = 0.00;
        $total_column['operazioni_esente'] = 0.00;
        $total_column['operazioni_nonimp'] = 0.00;
        $progressivo = 0;
        $ctrl_partner = 0;
        $value_imponi = 0.00;
        $value_impost = 0.00;
        while ($row = gaz_dbi_fetch_array($result)) {
            if ($row['operat'] == 1) {
                $value_imponi = $row['imponibile'];
                $value_impost = $row['imposta'];
            } elseif ($row['operat'] == 2) {
                $value_imponi = $row['imponibile'];
                $value_impost = $row['imposta'];
            } else {
                $value_imponi = 0;
                $value_impost = 0;
            }
            if ($ctrl_partner != $row['clfoco']) {
                // inizio controlli su CF e PI
                $nuw = new check_VATno_TAXcode();
                $resultpi = $nuw->check_VAT_reg_no($row['pariva']);
                if (strlen(trim($row['codfis'])) == 11) {
                    $resultcf = $nuw->check_VAT_reg_no($row['codfis']);
                    if (intval($row['codfis']) == 0) {
                        $error_partners[$row['clfoco']][] = $script_transl[7];
                    } elseif ($row['sexper'] != 'G') {
                        $error_partners[$row['clfoco']][] = $script_transl[8];
                    }
                } else {
                    $resultcf = $nuw->check_TAXcode($row['codfis']);
                    if (empty($row['codfis'])) {
                        $error_partners[$row['clfoco']][] = $script_transl[9];
                    } elseif ($row['sexper'] == 'G' and
                            empty($resultcf)) {
                        $error_partners[$row['clfoco']][] = $script_transl[10];
                    } elseif ($row['sexper'] == 'M' and
                            empty($resultcf) and ( intval(substr($row['codfis'], 9, 2)) > 31 or
                            intval(substr($row['codfis'], 9, 2)) < 1)) {
                        $error_partners[$row['clfoco']][] = $script_transl[11];
                    } elseif ($row['sexper'] == 'F' and
                            empty($resultcf) and ( intval(substr($row['codfis'], 9, 2)) > 71 or
                            intval(substr($row['codfis'], 9, 2)) < 41)) {
                        $error_partners[$row['clfoco']][] = $script_transl[12];
                    } elseif (!empty($resultcf)) {
                        $error_partners[$row['clfoco']][] = $script_transl[13];
                    }
                }
                if (!empty($resultpi)) {
                    $error_partners[$row['clfoco']][] = $script_transl[14];
                    $error_partners['fatal_error'] = '';
                } elseif (empty($row['pariva'])) {
                    $error_partners[$row['clfoco']][] = $script_transl[15];
                    $error_partners['fatal_error'] = '';
                }
                // fine controlli su CF e PI
                $progressivo ++;
                $castel_partners[$row['clfoco']] = array(
                    'Progressivo' => $progressivo,
                    'Num_Documenti' => $row['numdoc'],
                    'Rag_Sociale' => $row['ragsoc'],
                    'Indirizzo' => $row['indspe'],
                    'Comune' => $row['citspe'],
                    'Provincia' => $row['prospe'],
                    'Partita_IVA' => $row['pariva'],
                    'Codice_Fiscale' => $row['codfis']
                );
                if ($row['sexper'] == 'G') {
                    $castel_partners[$row['clfoco']]['persona_fisica'] = '';
                } else {
                    $castel_partners[$row['clfoco']]['persona_fisica'] = 'X';
                }
                if (!empty($row['sedleg'])) {
                    if (preg_match("/([\w\,\.\s]+)([0-9]{5})[\s]+([\w\s\']+)\(([\w]{2})\)/", $row['sedleg'], $regs)) {
                        $castel_partners[$row['clfoco']]['Indirizzo'] = $regs[1];
                        $castel_partners[$row['clfoco']]['Comune'] = $regs[3];
                        $castel_partners[$row['clfoco']]['Provincia'] = $regs[4];
                    } else {
                        $error_partners[$row['clfoco']][] = $script_transl[16];
                    }
                }
                // inizio valorizzazione imponibile,imposta,senza_iva,art8
                $castel_partners[$row['clfoco']]['operazioni_imponibili'] = 0;
                $castel_partners[$row['clfoco']]['imposte_addebitate'] = 0;
                $castel_partners[$row['clfoco']]['operazioni_esente'] = 0;
                $castel_partners[$row['clfoco']]['operazioni_nonimp'] = 0;
                switch ($row['tipiva']) {
                    case 'I':
                    case 'D':
                        $castel_partners[$row['clfoco']]['operazioni_imponibili'] = $value_imponi;
                        $total_column['operazioni_imponibili'] += $value_imponi;
                        $castel_partners[$row['clfoco']]['imposte_addebitate'] = $value_impost;
                        $total_column['imposte_addebitate'] += $value_impost;
                        if ($value_impost == 0) {  //se non c'� imposta il movimento � sbagliato
                            $error_partners[$row['clfoco']][] = $script_transl[17];
                        }
                        break;
                    case 'E':
                        $castel_partners[$row['clfoco']]['operazioni_esente'] = $value_imponi;
                        $total_column['operazioni_esente'] += $value_imponi;
                        if ($value_impost != 0) {  //se c'� imposta il movimento � sbagliato
                            $error_partners[$row['clfoco']][] = $script_transl[18];
                        }
                        break;
                    case 'N':
                        // case 'C':
                        $castel_partners[$row['clfoco']]['operazioni_nonimp'] = $value_imponi;
                        $total_column['operazioni_nonimp'] += $value_imponi;
                        if ($value_impost != 0) {  //se c'� imposta il movimento � sbagliato
                            $error_partners[$row['clfoco']][] = $script_transl[18];
                        }
                        break;
                }
                // fine valorizzazione imponibile,imposta,esente,non imponibile
            } else { //movimenti successivi al primo ma dello stesso cliente/fornitore
                // inizio addiziona valori imponibile,imposta,esente,non imponibile
                switch ($row['tipiva']) {
                    case 'I':
                    case 'D':
                        $castel_partners[$row['clfoco']]['operazioni_imponibili'] += $value_imponi;
                        $total_column['operazioni_imponibili'] += $value_imponi;
                        $castel_partners[$row['clfoco']]['imposte_addebitate'] += $value_impost;
                        $total_column['imposte_addebitate'] += $value_impost;
                        if ($value_impost == 0) {  //se non c'� imposta il movimento � sbagliato
                            $error_partners[$row['clfoco']][] = $script_transl[17];
                        }
                        break;
                    case 'E':
                        $castel_partners[$row['clfoco']]['operazioni_esente'] = $value_imponi;
                        $total_column['operazioni_esente'] += $value_imponi;
                        if ($value_impost != 0) {  //se c'� imposta il movimento � sbagliato
                            $error_partners[$row['clfoco']][] = $script_transl[18];
                        }
                        break;
                    case 'N':
                        // case 'C':
                        $castel_partners[$row['clfoco']]['operazioni_nonimp'] = $value_imponi;
                        $total_column['operazioni_nonimp'] += $value_imponi;
                        if ($value_impost != 0) {  //se c'� imposta il movimento � sbagliato
                            $error_partners[$row['clfoco']][] = $script_transl[18];
                        }
                        break;
                }
                // fine addiziona valori imponibile,imposta,esente,non imponibile
            }
            $ctrl_partner = $row['clfoco'];
        }
    } else {
        $error_partners[0] = $script_transl[21];
    }
    // fine creazione array righi ed errori
    return array($castel_partners, $error_partners, $total_column);
}

if (isset($_GET['pdf'])) {
    header("Location: stampa_elencf.php?anno=" . $_GET['anno'] . "&partner=" . $_GET['partner']);
    exit;
}

if (isset($_GET['file_agenzia'])) {
    require("../../library/include/agenzia_entrate.inc.php");

    function prepareAgenziaEntrateData($data, $tipo) {
        $pr = 1;
        $el = 2;
        $tot['imponibile'] = 0;
        $tot['imposta'] = 0;
        $tot['nonimp'] = 0;
        $tot['esente'] = 0;
        foreach ($data as $value) {
            $acc[$pr]['tipo'] = $tipo;
            $acc[$pr]['progressivo'] = $pr;
            if (!empty($value['Codice_Fiscale'])) {
                $acc[$pr]['codfis'] = strtoupper($value['Codice_Fiscale']);
                $el ++;
            }
            $acc[$pr]['pariva'] = $value['Partita_IVA'];
            if ($value['operazioni_imponibili'] <> 0) {
                $acc[$pr]['imponibile'] = round($value['operazioni_imponibili']);
                $tot['imponibile'] += $acc[$pr]['imponibile'];
                $el ++;
            }
            if ($value['imposte_addebitate'] <> 0) {
                $acc[$pr]['imposta'] = round($value['imposte_addebitate']);
                $tot['imposta'] += $acc[$pr]['imposta'];
                $el ++;
            }
            if ($value['operazioni_nonimp'] <> 0) {
                $acc[$pr]['nonimp'] = round($value['operazioni_nonimp']);
                $tot['nonimp'] += $acc[$pr]['nonimp'];
                $el ++;
            }
            if ($value['operazioni_esente'] <> 0) {
                $acc[$pr]['esente'] = round($value['operazioni_esente']);
                $tot['esente'] += $acc[$pr]['esente'];
                $el ++;
            }
            $acc[$pr]['elementi'] = $el;
            $el = 2;
            $tot['numero'] = $pr;
            $pr ++;
        }
        // --- fine preparazione
        return array($acc, $tipo => $tot);
    }

    $annofornitura = date("y");
    // Impostazione degli header per l'opozione "save as" dello standard input che verr� generato
    header('Content-Type: text/x-ecf');
    header("Content-Disposition: attachment; filename=" . $admin_aziend['codfis'] . '_' . $_GET['anno'] . ".ecf");
    header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // per poter ripetere l'operazione di back-up pi� volte.
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
    } else {
        header('Pragma: no-cache');
    }
    // --- preparo gli array da passare alla classe AgenziaEntrate a secondo della scelta effettuata
    if ($_GET['partner'] == 1) {
        $queryData = createRowsAndErrors(1);
        $rs_dati = prepareAgenziaEntrateData($queryData[0], 1);
        $Dati = $rs_dati[0];
        $totali = array(1 => $rs_dati[1]);
    } elseif ($_GET['partner'] == 2) {
        $queryData = createRowsAndErrors(2);
        $rs_dati = prepareAgenziaEntrateData($queryData[0], 2);
        $Dati = $rs_dati[0];
        $totali = array(2 => $rs_dati[2]);
    } else {
        $queryData = createRowsAndErrors(1);
        $rs_dati1 = prepareAgenziaEntrateData($queryData[0], 1);
        $queryData = createRowsAndErrors(2);
        $rs_dati2 = prepareAgenziaEntrateData($queryData[0], 2);
        $Dati = array_merge($rs_dati1[0], $rs_dati2[0]);
        $totali = array(1 => $rs_dati1[1], 2 => $rs_dati2[2]);
    }
    $Testa = getHeaderData();
    $agenzia = new AgenziaEntrate;
    $content = $agenzia->creaFileECF($Testa, $Dati, $totali);
    print $content;
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain();
echo "<form method=\"GET\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . ucfirst($script_transl[0]) . "</div>\n";
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
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[1] . "</td>
     <td class=\"FacetDataTD\">\n";
echo "<select name=\"partner\" class=\"FacetSelect\">\n";
for ($counter = 1; $counter <= 3; $counter++) {
    $selected = '';
    if ($_GET['partner'] == $counter) {
        $selected = "selected";
    }
    if ($counter == 1) {
        echo "\t\t <option value=\"" . $counter . "\" $selected >" . $script_transl[2] . "</option>\n";
    } elseif ($counter == 2) {
        echo "\t\t <option value=\"" . $counter . "\" $selected >" . $script_transl[3] . "</option>\n";
    } else {
        echo "\t\t <option value=\"" . $counter . "\" $selected >" . $script_transl[2] . " &amp; " . $script_transl[3] . "</option>\n";
    }
}
echo "</select></td></tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[5] . "</td><td class=\"FacetDataTD\" colspan=\"3\">";
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
    if ($_GET['partner'] == 1) {
        $queryData = createRowsAndErrors(1);
        $caste_clienti = $queryData[0];
        $error_partners = $queryData[1];
        $total_clienti = $queryData[2];
    } elseif ($_GET['partner'] == 2) {
        $queryData = createRowsAndErrors(2);
        $caste_fornitori = $queryData[0];
        $error_partners = $queryData[1];
        $total_fornitori = $queryData[2];
    } else {
        $queryData = createRowsAndErrors(1);
        $caste_clienti = $queryData[0];
        $error_partners = $queryData[1];
        $total_clienti = $queryData[2];
        $queryData = createRowsAndErrors(2);
        $caste_fornitori = $queryData[0];
        $error_partners += $queryData[1];
        $total_fornitori = $queryData[2];
    }
    $Testa = getHeaderData();
    if (!isset($error_partners[0])) {
        echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['view'] . "</div>";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        echo "<tr>";
        echo "<td colspan=\"2\"></td>";
        echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">$script_transl[24]</td>";
        echo "<td colspan=\"4\" class=\"FacetDataTD\">" . $Testa['codfis'] . "</td>";
        echo "<td colspan=\"3\"></td>";
        echo "</tr>\n";
        echo "<tr>";
        echo "<td colspan=\"2\"></td>";
        echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">$script_transl[25]</td>";
        echo "<td colspan=\"4\" class=\"FacetDataTD\">" . $Testa['pariva'] . "</td>";
        echo "<td colspan=\"3\"></td>";
        echo "</tr>\n";
        if (!isset($Testa['fatal_error'])) {
            if (!isset($Testa['sesso'])) { // � una persona giuridica
                echo "<tr>";
                echo "<td colspan=\"2\"></td>";
                echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">$script_transl[32]</td>";
                echo "<td colspan=\"4\" class=\"FacetDataTD\">" . $Testa['ragsoc'] . "</td>";
                echo "<td colspan=\"3\"></td>";
                echo "</tr>\n";
                echo "<tr>";
                echo "<td colspan=\"2\"></td>";
                echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">$script_transl[33]</td>";
                echo "<td colspan=\"4\" class=\"FacetDataTD\">" . $Testa['sedleg'] . "</td>";
                echo "<td colspan=\"3\"></td>";
                echo "</tr>\n";
                echo "<tr>";
                echo "<td colspan=\"2\"></td>";
                echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">$script_transl[34]</td>";
                echo "<td colspan=\"4\" class=\"FacetDataTD\">" . $Testa['proleg'] . "</td>";
                echo "<td colspan=\"3\"></td>";
                echo "</tr>\n";
            } else {     // persona fisica
                echo "<tr>";
                echo "<td colspan=\"2\"></td>";
                echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">$script_transl[26]</td>";
                echo "<td colspan=\"4\" class=\"FacetDataTD\">" . $Testa['cognome'] . "</td>";
                echo "<td colspan=\"3\"></td>";
                echo "</tr>\n";
                echo "<tr>";
                echo "<td colspan=\"2\"></td>";
                echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">$script_transl[27]</td>";
                echo "<td colspan=\"4\" class=\"FacetDataTD\">" . $Testa['nome'] . "</td>";
                echo "<td colspan=\"3\"></td>";
                echo "</tr>\n";
                echo "<tr>";
                echo "<td colspan=\"2\"></td>";
                echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">$script_transl[28]</td>";
                echo "<td colspan=\"4\" class=\"FacetDataTD\">" . $Testa['sesso'] . "</td>";
                echo "<td colspan=\"3\"></td>";
                echo "</tr>\n";
                echo "<tr>";
                echo "<td colspan=\"2\"></td>";
                echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">$script_transl[29]</td>";
                echo "<td colspan=\"4\" class=\"FacetDataTD\">" . $Testa['datnas'] . "</td>";
                echo "<td colspan=\"3\"></td>";
                echo "</tr>\n";
                echo "<tr>";
                echo "<td colspan=\"2\"></td>";
                echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">$script_transl[30]</td>";
                echo "<td colspan=\"4\" class=\"FacetDataTD\">" . $Testa['luonas'] . "</td>";
                echo "<td colspan=\"3\"></td>";
                echo "</tr>\n";
                echo "<tr>";
                echo "<td colspan=\"2\"></td>";
                echo "<td colspan=\"1\" class=\"FacetFieldCaptionTD\">$script_transl[31]</td>";
                echo "<td colspan=\"4\" class=\"FacetDataTD\">" . $Testa['pronas'] . "</td>";
                echo "<td colspan=\"3\"></td>";
                echo "</tr>\n";
            }
        } else {
            echo "<tr>";
            echo "<td colspan=\"10\" align=\"center\" class=\"FacetDataTDred\" colspan=\"6\">$script_transl[23]:" . $Testa['fatal_error'] . "</td>\n";
            echo "</tr>\n";
        }
        if (!empty($error_partners) and $_GET['anno'] > 2007) {
            echo "<tr>\n
                    <td class=\"FacetDataTDred\" colspan=\"10\">$script_transl[19]:</td>
                    </tr>\n";
        } elseif (isset($Testa['fatal_error'])) {
            echo "<tr>\n
                   <td align=\"center\" class=\"FacetFieldCaptionTD\" colspan=\"4\"><input type=\"submit\" name=\"pdf\" value=\"PDF\"></td>\n
                   <td align=\"center\" class=\"FacetDataTDred\" colspan=\"6\">$script_transl[23]:" . $Testa['fatal_error'] . "</td>\n
                   </tr>\n";
        } else {
            echo "<tr>\n
                   <td align=\"center\" class=\"FacetFieldCaptionTD\" colspan=\"4\"><input type=\"submit\" name=\"pdf\" value=\"PDF\"></td>\n
                   <td align=\"center\" class=\"FacetFieldCaptionTD\" colspan=\"6\"><input type=\"submit\" name=\"file_agenzia\" value=\"File Internet (ECF)\"></td>\n
                   </tr>\n";
        }
        if ($_GET['partner'] == 1) {
            printPartners($caste_clienti, $error_partners, $total_clienti, 1);
        } elseif ($_GET['partner'] == 2) {
            printPartners($caste_fornitori, $error_partners, $total_fornitori, 2);
        } else {
            printPartners($caste_clienti, $error_partners, $total_clienti, 1);
            printPartners($caste_fornitori, $error_partners, $total_fornitori, 2);
        }
        echo "</table>";
    } else {
        echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $error_partners[0] . "</div>";
    }
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>