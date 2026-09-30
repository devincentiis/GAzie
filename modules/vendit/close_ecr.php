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
require("../../library/include/calsca.inc.php");

$msg = array('err' => array(), 'war' => array());
// se l'utente non ha alcun registratore di cassa associato nella tabella cash_register non può inviare scontrini al RT (ecr) allora creerò un file XML
$gForm = new venditForm();
$ecr = $gForm->getECR_userData($admin_aziend["user_name"]);
$ecr_user = gaz_dbi_get_row($gTables['cash_register'], 'adminid', $admin_aziend["user_name"]);


if (!$ecr_user) { // creerò un XML con id_cash '0' oppure invierò all'ecr (RT)
	$ecr=array('id_cash'=>0,'seziva'=>1,'descri'=>'File XML');
}

if (!isset($_POST['ritorno'])) {
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
} else {
    $form['ritorno'] = $_POST['ritorno'];
}

if (isset($_POST['return'])) {
    header("Location: " . $form['ritorno']);
    exit;
}


function getLastProtoc($year, $seziva, $reg = 4) {
    global $gTables;
    $rs_last = gaz_dbi_dyn_query("protoc", $gTables['tesmov'], "YEAR(datreg) = " . intval($year) . " AND regiva = " . intval($reg) . " AND seziva = " . intval($seziva), 'protoc DESC', 0, 1);
    $last = gaz_dbi_fetch_array($rs_last);
    $p = 1;
    if ($last) {
        $p = $last['protoc'] + 1;
    }
    return $p;
}

function getLastNumdoc($year, $seziva, $reg = 4) {
    global $gTables;
    $rs_last = gaz_dbi_dyn_query("numdoc", $gTables['tesmov'], "YEAR(datreg) = " . intval($year) . " AND regiva = " . intval($reg) . " AND seziva = " . intval($seziva), 'protoc DESC', 0, 1);
    $last = gaz_dbi_fetch_array($rs_last);
    $p = 1;
    if ($last) {
        $p = $last['numdoc'] + 1;
    }
    return $p;
}

function getAccountableTickets($id_cash) {
    global $gTables, $admin_aziend;
    $from = $gTables['tesdoc'] . ' AS tesdoc
         LEFT JOIN ' . $gTables['pagame'] . ' AS pay
         ON tesdoc.pagame=pay.codice
         LEFT JOIN ' . $gTables['clfoco'] . ' AS customer
         ON tesdoc.clfoco=customer.codice
         LEFT JOIN ' . $gTables['anagra'] . ' AS anagraf
         ON anagraf.id=customer.id_anagra';
    $where = "id_con = 0 AND id_contract = " . intval($id_cash) . " AND tipdoc = 'VCO'"; // uso impropriamente id_contract per contenere il riferimento all'id dell'ecr (RT) se 0 è un XML
    $orderby = "datemi ASC, numdoc ASC";
    $result = gaz_dbi_dyn_query('tesdoc.*,
            pay.tippag,pay.numrat,pay.incaut,pay.tipdec,pay.giodec,pay.tiprat,pay.mesesc,pay.giosuc,pay.id_bank,
            customer.codice,
            customer.speban AS addebitospese,
            CONCAT(anagraf.ragso1,\' \',anagraf.ragso2) AS ragsoc,CONCAT(anagraf.citspe,\' (\',anagraf.prospe,\')\') AS citta', $from, $where, $orderby);
    $doc['all'] = [];
    $tot = 0;
    while ($tes = gaz_dbi_fetch_array($result)) {
        //$cast_DTE=[];
        $cast_vat=[];
        $cast_acc=[];
        $tot_tes=0;
        $carry = 0;
        //recupero i dati righi per creare i castelletti
        $rs_rig = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = " . $tes['id_tes'], "id_rig");
        while ($v = gaz_dbi_fetch_array($rs_rig)) {
            if ($v['tiprig'] <= 1) {    //ma solo se del tipo normale o forfait
                if ($v['tiprig'] == 0) { // tipo normale
                    $tot_row = CalcolaImportoRigo($v['quanti'], $v['prelis'], array($v['sconto'], $tes['sconto'], -$v['pervat']));
                } else {                 // tipo forfait
                    $tot_row = CalcolaImportoRigo(1, $v['prelis'], -$v['pervat']);
                }
                if (!isset($cast_vat[$v['codvat']])) {
                    $cast_vat[$v['codvat']]['totale'] = 0.00;
                    $cast_vat[$v['codvat']]['imponi'] = 0.00;
                    $cast_vat[$v['codvat']]['impost'] = 0.00;
                    $cast_vat[$v['codvat']]['periva'] = $v['pervat'];
                }
                $cast_vat[$v['codvat']]['totale'] += $tot_row;
                // calcolo il totale del rigo stornato dell'iva
                $imprig = round($tot_row / (1 + ($v['pervat'] / 100)), 2);
                $cast_vat[$v['codvat']]['imponi'] += $imprig;
                $cast_vat[$v['codvat']]['impost'] += $tot_row - $imprig;
                $tot += $tot_row;
                $tot_tes += $tot_row;
                // inizio AVERE
                if (!isset($cast_acc[$admin_aziend['ivacor']]['A'])) {
                    $cast_acc[$admin_aziend['ivacor']]['A'] = 0;
                }
                $cast_acc[$admin_aziend['ivacor']]['A'] += $tot_row - $imprig;
                if (!isset($cast_acc[$v['codric']]['A'])) {
                    $cast_acc[$v['codric']]['A'] = 0;
                }
                $cast_acc[$v['codric']]['A'] += $imprig;
                // inizio DARE
                if ($tes['clfoco'] > 100000000) { // c'è un cliente selezionato
                    if (!isset($cast_acc[$tes['clfoco']]['D'])) {
                        $cast_acc[$tes['clfoco']]['D'] = 0;
                    }
                    $cast_acc[$tes['clfoco']]['D'] += $tot_row; // metto in dare il cliente
                    if ($tes['incaut'] > 100000000) { // pagamento che prevede incasso automatico
                            if (!isset($cast_acc[$tes['clfoco']]['A'])) {
                                $cast_acc[$tes['clfoco']]['A'] = 0;
                            }
                            $cast_acc[$tes['clfoco']]['A'] += $tot_row;
                            if (!isset($cast_acc[$tes['incaut']]['D'])) {
                                $cast_acc[$tes['incaut']]['D'] = 0;
                            }
                            $cast_acc[$tes['incaut']]['D'] += $tot_row;
                    }
                } else {  // il cliente è anonimo
                    if ($tes['incaut'] > 100000000) { // pagamento che prevede incasso automatico
                        if (!isset($cast_acc[$tes['incaut']]['D'])) {
                            $cast_acc[$tes['incaut']]['D'] = 0;
                        }
                        $cast_acc[$tes['incaut']]['D'] += $tot_row;
                    } else { //vado per cassa
                        if (!isset($cast_acc[$admin_aziend['cassa_']]['D'])) {
                            $cast_acc[$admin_aziend['cassa_']]['D'] = 0;
                        }
                        $cast_acc[$admin_aziend['cassa_']]['D'] += $tot_row;
                    }
                }
            } elseif ($v['tiprig'] == 3) { // variazione pagamento
              $carry += $v['prelis'];
            }
        }
        $doc['all'][] = array('tes' => $tes,
            'vat' => $cast_vat,
            'acc' => $cast_acc,
            'tot' => $tot_tes,
            'car' => $carry);
        if ($tes['clfoco'] > 100000000) {
            $doc['invoice'][] = array('tes' => $tes,
                'vat' => $cast_vat,
                'acc' => $cast_acc,
                'tot' => $tot_tes,
                'car' => $carry);
        } else {
            $doc['ticket'][] = array('tes' => $tes,
                'vat' => $cast_vat,
                'acc' => $cast_acc,
                'tot' => $tot_tes,
                'car' => $carry);
        }
    }
    $doc['tot'] = $tot;
    return $doc;
}
$ultimo_file = 1;
if (isset($_POST['submit'])) {

	// cerco l'ultimo file xml generato
    $rs_query = gaz_dbi_dyn_query("*", $gTables['comunicazioni_dati_fatture'], "nome_file_DTE LIKE '%DF_C%'", "anno DESC, id DESC", 0, 1);
    $ultima_comunicazione = gaz_dbi_fetch_array($rs_query);
    if ($ultima_comunicazione) {
        $ultimo_file = $ultima_comunicazione['trimestre_semestre']+1;
    }
    $filename = DATA_DIR.'files/' . $admin_aziend['codice'] . '/' . $admin_aziend['country'] . $admin_aziend['codfis'] . "_DF_C".str_pad($ultimo_file, 4, "0", STR_PAD_LEFT).".xml";


	if ($ecr_user){ // se è un utente abilitato all'invio all'ecr procedo in tal senso , altrimenti genererò un file XML dopo aver contabilizzato
        // INIZIO l'invio della richiesta al'ecr (RT) dell'utente
        require("../../library/cash_register/" . $ecr['driver'] . ".php");
        $ticket_printer = new $ecr['driver'];
        $ticket_printer->set_serial($ecr['serial_port']);
        $ticket_printer->fiscal_report();
	}
    // INIZIO contabilizzazione scontrini con fatture
    $rs = getAccountableTickets($ecr['id_cash']);

    if (isset($rs['invoice'])) {
        foreach ($rs['invoice'] as $v) { //prima quelli con fattura allegata
            $n_prot = getLastProtoc(substr($v['tes']['datemi'], 0, 4), $v['tes']['seziva']);
            //inserisco la testata
            $newValue = array('caucon' => 'VCO',
                'descri' => 'SCONTRINO con Fattura n.' . $v['tes']['numfat'] . ' allegata',
                'datreg' => $v['tes']['datemi'],
                'datliq' => $v['tes']['datemi'],
                'seziva' => $v['tes']['seziva'],
                'id_doc' => $v['tes']['id_tes'],
                'protoc' => $n_prot,
                'numdoc' => $v['tes']['numfat'],
                'datdoc' => $v['tes']['datemi'],
                'clfoco' => $v['tes']['clfoco'],
                'regiva' => 4,
                'operat' => 1
            );
            $tes_id = tesmovInsert($newValue);
            gaz_dbi_put_row($gTables['tesdoc'], 'id_tes', $v['tes']['id_tes'], 'id_con', $tes_id);
            //inserisco i righi iva nel db
            $tot = 0.00;
            foreach ($v['vat'] as $k => $vv) {
                $vat = gaz_dbi_get_row($gTables['aliiva'], 'codice', $k);
                //aggiungo i valori mancanti all'array
                $vv['tipiva'] = $vat['tipiva'];
                $vv['codiva'] = $k;
                $vv['id_tes'] = $tes_id;
                $tot += round($vv['imponi']+$vv['impost'],2);
                rigmoiInsert($vv);
            }

            // calcolo le rate al fine di inserire le partite aperte
            $rate = CalcolaScadenze( ($tot + $v['car']), substr($v['tes']['datfat'], 8, 2), substr($v['tes']['datfat'], 5, 2), substr($v['tes']['datfat'], 0, 4), $v['tes']['tipdec'], $v['tes']['giodec'], $v['tes']['numrat'], $v['tes']['tiprat'], $v['tes']['mesesc'], $v['tes']['giosuc']);

            //inserisco i righi contabili nel db
            foreach ($v['acc'] as $acc_k => $acc_v) {
                foreach ($acc_v as $da_k => $da_v) {
                    if (round($da_v,2) == 0.00) continue; // no inserisco righi a zero
                    $rigmoc_id = rigmocInsert(array('id_tes' => $tes_id, 'darave' => $da_k, 'codcon' => $acc_k, 'import' => $da_v));
                    if ($admin_aziend['mascli']==substr($acc_k,0,3) && $v['tes']['incaut'] < 100000000 ) {
                      foreach ($rate['import'] as $k_rate => $v_rate) {
                        // preparo l'array da inserire sui movimenti delle partite aperte
                        $paymov_value = array('id_tesdoc_ref' => substr($v['tes']['datemi'], 0, 4) . 4 . $v['tes']['seziva'] . str_pad($n_prot, 9, 0, STR_PAD_LEFT),
                            'id_rigmoc_doc' => $rigmoc_id,
                            'amount' => $v_rate,
                            'expiry' => $rate['anno'][$k_rate] . '-' . $rate['mese'][$k_rate] . '-' . $rate['giorno'][$k_rate]);
                        paymovInsert($paymov_value);
                      }
                    }
                }
            }

        }
    }

    if (isset($rs['ticket']) > 0) {
        // poi gli scontrini senza fattura (anonimi)
        // devo accumulare i valori per data
        // INIZIO accumulatore per data
        $cast_vat=[];
        $cast_acc=[];
        foreach ($rs['ticket'] as $v) {
            foreach ($v['vat'] as $k => $iva) { // accumulo l'iva
                if (!isset($cast_vat[$v['tes']['datemi']][$k])) {
                    $cast_vat[$v['tes']['datemi']][$k]['totale'] = 0;
                    $cast_vat[$v['tes']['datemi']][$k]['imponi'] = 0;
                    $cast_vat[$v['tes']['datemi']][$k]['impost'] = 0;
                    $cast_vat[$v['tes']['datemi']][$k]['periva'] = $iva['periva'];
                }
                $cast_vat[$v['tes']['datemi']][$k]['totale'] += $iva['totale'];
                $cast_vat[$v['tes']['datemi']][$k]['imponi'] += $iva['imponi'];
                $cast_vat[$v['tes']['datemi']][$k]['impost'] += $iva['impost'];
            }
            foreach ($v['acc'] as $k => $acc) {  // accumulo i conti
                foreach ($acc as $da_k => $da_v) {
                    if (!isset($cast_acc[$v['tes']['datemi']][$k][$da_k])) {
                        $cast_acc[$v['tes']['datemi']][$k][$da_k] = 0;
                    }
                    $cast_acc[$v['tes']['datemi']][$k][$da_k] += $da_v;
                }
            }
        }
        // FINE accumulatore per data

        // INIZIO contabilizzazione scontrini anonimi
        foreach ($cast_vat as $k => $v) {
            $n_prot = getLastProtoc(substr($k, 0, 4), $ecr['seziva']);
            $n_docu = getLastNumdoc(substr($k, 0, 4), $ecr['seziva']);
            //inserisco la testata
            $newValue = array('caucon' => 'VCO',
                'descri' => 'SCONTRINI ' . $ecr['descri'],
                'datreg' => $k,
                'datliq' => $k,
                'seziva' => $ecr['seziva'],
                'id_doc' => 0,
                'protoc' => $n_prot,
                'numdoc' => $n_docu,
                'datdoc' => $k,
                'clfoco' => 0,
                'regiva' => 4,
                'operat' => 1
            );
            $tes_id = tesmovInsert($newValue);
            tableUpdate('tesdoc', array('id_con'), array('id_contract', $ecr['id_cash'] . "' AND tipdoc = 'VCO' AND datemi = '" . substr($k, 0, 4) . substr($k, 5, 2) . substr($k, 8, 2)), array('id_con' => $tes_id));
            //inserisco i righi iva nel db
            foreach ($cast_vat[$k] as $key => $vv) {
                $vat = gaz_dbi_get_row($gTables['aliiva'], 'codice', $key);
                //aggiungo i valori mancanti all'array
                $vv['tipiva'] = $vat['tipiva'];
                $vv['codiva'] = $key;
                $vv['id_tes'] = $tes_id;
                rigmoiInsert($vv);
            }
            //inserisco i righi contabili nel db
            foreach ($cast_acc[$k] as $acc_k => $acc_v) {
                foreach ($acc_v as $da_k => $da_v) {
                    rigmocInsert(array('id_tes' => $tes_id, 'darave' => $da_k, 'codcon' => $acc_k, 'import' => $da_v));
                }
            }
        }
		// FINE CONTABILIZZAZIONE

    }
	if (!$ecr_user){ // NON è un utente abilitato all'invio all'ecr, genererò un file XML
        // devo accumulare i valori per data di tutto: sia degli anonimi che con fatture
        // INIZIO accumulatore per data
		$anagrafica = new Anagrafica();
        $cast_COR10=[];
        foreach ($rs['all'] as $v) {
            foreach ($v['vat'] as $k => $iva) { // accumulo l'iva, in $k ho codvat per aliiva
                if (!isset($cast_COR10[$v['tes']['datemi']]['tot_imponibile_giorno'])) {
                    $cast_COR10[$v['tes']['datemi']]['tot_imponibile_giorno'] = 0;
				}
                $cast_COR10[$v['tes']['datemi']]['tot_imponibile_giorno'] += $iva['imponi'];
                if (!isset($cast_COR10[$v['tes']['datemi']][$k])) {
					$vat = gaz_dbi_get_row($gTables['aliiva'], 'codice', $k);
                    $cast_COR10[$v['tes']['datemi']][$k]['fae_natura'] = $vat['fae_natura'];
                    $cast_COR10[$v['tes']['datemi']][$k]['totale'] = 0;
                    $cast_COR10[$v['tes']['datemi']][$k]['imponi'] = 0;
                    $cast_COR10[$v['tes']['datemi']][$k]['impost'] = 0;
                    $cast_COR10[$v['tes']['datemi']][$k]['periva'] = $iva['periva'];
                }
                $cast_COR10[$v['tes']['datemi']][$k]['totale'] += $iva['totale'];
                $cast_COR10[$v['tes']['datemi']][$k]['imponi'] += $iva['imponi'];
                $cast_COR10[$v['tes']['datemi']][$k]['impost'] += $iva['impost'];
				/* non mi interessa l'invio delle fatture allegate, in quanto già indicato sul traccciato xml delle fatture stesse
				if ($v['tes']['clfoco']>100000000){ // se il movimento deriva da una fattura allegata la accumulo sul valore della stessa assieme ai dati anagrafici
					if (!isset($cast_COR10[$v['tes']['datemi']]['clfoco'][$v['tes']['numfat']])){
						$cliente=$anagrafica->getPartner($v['tes']['clfoco']);
						$cast_COR10[$v['tes']['datemi']]['clfoco'][$v['tes']['numfat']] = array_merge($cliente,$v['tes'],array('totale_fat'=>0.00));
					}
					$cast_COR10[$v['tes']['datemi']]['clfoco'][$v['tes']['numfat']]['totale_fat'] += $iva['totale'];
				}
				*/
            }
			gaz_dbi_put_row($gTables['tesdoc'], 'id_tes', $v['tes']['id_tes'], 'fattura_elettronica_original_name', basename($filename));
        }
        $form['nome_file_DTE'] = basename($filename);
		$form['trimestre_semestre']= $ultimo_file;
		$form['anno']= substr($v['tes']['datemi'],0,4);
        gaz_dbi_table_insert('comunicazioni_dati_fatture', $form);

        require("../../library/include/agenzia_entrate.inc.php");
		creaFileCOR10($admin_aziend, $cast_COR10,$ultimo_file);
		$msg['war'][] = "download";
	}
} elseif (isset($_POST['Download'])) {
        $filename = DATA_DIR.'files/' . $admin_aziend['codice'] . '/' . $admin_aziend['country'] . $admin_aziend['codfis'] . "_DF_C" . str_pad(intval($_POST['ultimo_file']), 4, '0', STR_PAD_LEFT) . ".xml";
        header("Pragma: public", true);
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=" . basename($filename));
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($filename));
        die(file_get_contents($filename));
        exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain(0);
echo "<div>";
echo "<form method=\"POST\" name=\"accounting\">\n";
echo "<input type=\"hidden\" value=\"" . $form['ritorno'] . "\" name=\"ritorno\" />\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title1'] . $ecr['descri'] . $script_transl['title2'] . "</div>\n";
if (count($msg['war']) == 0) {
  $rs = getAccountableTickets($ecr['id_cash']);
	echo "<div class=\"box-primary table-responsive\">";
	echo "<table class=\"Tlarge table table-striped table-bordered\">";
	echo "<th class=\"FacetFieldCaptionTD\">" . $script_transl['date'] . "</th>
      <th class=\"FacetFieldCaptionTD\">" . $script_transl['num'] . "</th>
      <th class=\"FacetFieldCaptionTD\">" . $script_transl['sez'] . "</th>
      <th class=\"FacetFieldCaptionTD\">" . $script_transl['customer'] . "</th>
      <th class=\"FacetFieldCaptionTD\">" . $script_transl['importo'] . "</th>";
  if (count($rs['all']) > 0) {
	$butt=($ecr_user)?' chiusura RT ':' generazione file ';
    foreach ($rs['all'] as $k => $v) {
        if ($v['tes']['clfoco'] < 100000000) {
            $v['tes']['ragsoc'] = $script_transl['anony'];
        }
        echo "<tr class=\"FacetDataTD\">
            <td align=\"center\">" . gaz_format_date($v['tes']['datemi']) . "</td>
            <td align=\"center\">" . $v['tes']['numdoc'] . "</td>
            <td align=\"center\">" . $v['tes']['seziva'] . "</td>
            <td>" . $v['tes']['ragsoc'] . $v['tes']['citta'] . "</td>
            <td align=\"right\">" . gaz_format_number($v['tot']) . "</td>
            </tr>\n";
    }
    echo "<tr class=\"FacetFieldCaptionTD\">\n";
    echo '<td colspan="4" align="right"><input type="submit" class="btn btn-warning" name="submit" value="';
    echo $script_transl['submit'].$butt.$ecr['descri'];
    echo '">';
    echo "</td>\n";
    echo '<td align="right" style="font-weight=bolt;">';
    echo gaz_format_number($rs['tot']);
    echo "\t </td>\n";
    echo "</tr>\n";
  } else {
    echo "\t<tr>\n";
    echo '<td colspan="3" align="center" class="FacetDataTDred">';
    echo $script_transl['message'];
    echo "\t </td>\n";
    echo '<td colspan="2" align="center" class="FacetDataTDred">';
    echo "<input type=\"submit\" name=\"return\" value=\"" . $script_transl['return'] . "\" />\n";
    echo "\t </td>\n";
    echo "\t </tr>\n";
  }
?>
</table>
</div>
<?php
} else {
    $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
?>
    <input type="hidden" name="ultimo_file" value="<?php echo $ultimo_file; ?>">
    <div class="col-sm-12 text-center"><input name="Download" type="submit" class="btn btn-success" value="<?php echo $admin_aziend['country'] . $admin_aziend['codfis'] . "_DF_C" . str_pad(intval($ultimo_file), 4, '0', STR_PAD_LEFT) . ".xml"; ?>" /></div>
<?php
}
?>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
