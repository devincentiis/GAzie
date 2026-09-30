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
$msg = array('err' => array(), 'war' => array());

function lastAccount($mas, $ss) {
    /* funzione per trovare i numeri dei nuovi sottoconto da creare sui mastri
     * scelti per le immobilizzazioni, i fondi e i costi d'ammortamento dove i
     * due numeri successivi indicano la sottospecie della tabella ministeriale
     * degli ammortamenti e i restanti 4 (9999) sono attribuiti automaticamente
     * al singolo bene da questa funzione                                     */
    global $gTables;
    $subacc = $mas * 1000000 + $ss * 10000;
    $rs_last_subacc = gaz_dbi_dyn_query("*", $gTables['clfoco'], "codice BETWEEN " . $subacc . " AND " . intval($subacc + 9999), "codice DESC", 0, 1);
    $last_subacc = gaz_dbi_fetch_array($rs_last_subacc);
    if ($last_subacc) {
        return $last_subacc['codice'] + 1;
    } else {
        return $subacc + 1;
    }
}

function tryBase64Decode($s)
{
	// Check if there are valid base64 characters
	if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s)) {
		// Decode the string in strict mode and check the results
		try {
			$decoded = base64_decode($s, true);
			if ($decoded !== false) {
				// Encode the string again
				if(base64_encode($decoded) == $s) {
                	return $decoded;
                } else {
					error_log('Charset non gestito in tryBase64Decode ' . print_r($decoded, true), 0);
                	return $decoded;
                }
			}
		} catch (Exception $ex) {
			//$ex->getMessage();
		}
	}

	return $s;
}

function der2smime($file)
{
$to = <<<TXT
MIME-Version: 1.0
Content-Disposition: attachment; filename="smime.p7m"
Content-Type: application/x-pkcs7-mime; smime-type=signed-data; name="smime.p7m"
Content-Transfer-Encoding: base64
\n
TXT;
	$from = file_get_contents($file);
	$to.= chunk_split(base64_encode($from));
	return file_put_contents($file,$to);
}

function extractDER($file)
{
	$tmp = tempnam(DATA_DIR . 'files/tmp/', 'ricder');
	$txt = tempnam(DATA_DIR . 'files/tmp/', 'rictxt');
	$flags = PKCS7_BINARY|PKCS7_NOVERIFY|PKCS7_NOSIGS;
	openssl_pkcs7_verify($file, $flags, $tmp); // estrazione certificato
	@openssl_pkcs7_verify($file, $flags, '/dev/null', array(), $tmp, $txt); // estrazione contenuto - questo potrebbe fallire se il file non è ASN.1 clean
	unlink($tmp);
	$out = file_get_contents($txt);
	unlink($txt);
	return $out;
}

function removeSignature($s)
{
	$start_xml = strpos($s, '<?xml ');
	if ($start_xml !== FALSE) {
		$s = substr($s, $start_xml);
	} else {
		$start_xml = strpos($s, '<?xml-stylesheet ');
		if ($start_xml !== FALSE) {
			$s = substr($s, $start_xml);
		}
	}
	// elimino le sequenze di caratteri aggiunti dalla firma (ancora da testare approfonditamente)
	$s = preg_replace('/[\x{0004}]{1}[\x{0082}]{1}[\x{0001}-\x{001F}]{1}[\s\S]{1}/i', '', $s);
	$s = preg_replace('/[\x{0004}]{1}[\x{0082}]{1}[\s\S]{1}[\x{0000}]{1}/i', '', $s);
	$s = preg_replace('/[\x{0004}]{1}[\x{0081}]{1}[\s\S]{1}/i', '', $s);
	$s = preg_replace('/[\x{0004}]{1}[\s\S]{1}/i', '', $s);
	$s = preg_replace('/[\x{0003}]{1}[\s\S]{1}/i', '', $s);
	//$s = preg_replace('/[\x{0004}]{1}[A-Za-z]{1}/i', '', $s); // per eliminare tag finale
	preg_match_all('/<\/.+?>/', $s, $matches, PREG_OFFSET_CAPTURE);
	$lastMatch = end($matches[0]);
	// trovo l'ultimo carattere del tag di chiusura per eliminare la coda
	$f_end = $lastMatch[1]+strlen($lastMatch[0]);
	$s = substr($s, 0, $f_end);
	return $s;
}

function recoverCorruptedXML($s)
{
	libxml_use_internal_errors(true);
	$xml = @simplexml_load_string($s);
	$errors = libxml_get_errors();
	if (!empty($errors) && is_array($errors) && count($errors)>0) {
		$lines = explode("\n", $s);
		foreach ($errors as $error) {
			if (strpos($error->message, 'Opening and ending tag mismatch')!==false) {
				$tag   = trim(preg_replace('/Opening and ending tag mismatch: (.*) line.*/', '$1', $error->message));
				$line  = $error->line-1;
				$lines[$line] = substr($lines[$line], 0, strpos($lines[$line], '</')).'</'.$tag.'>';
			}
		}
		libxml_clear_errors();
		return implode("\n", $lines);
	} else {
		return $s;
	}
}


if (isset($_GET['Update']) && !isset($_GET['id'])) {
    header("Location: " . $form['ritorno']);
    exit;
}

if (isset($_POST['Update']) || isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if ((isset($_POST['Insert'])) || ( isset($_POST['Update']))) {   //se non e' il primo accesso
//qui si dovrebbe fare un parsing di quanto arriva dal browser...
    $form['id_movcon'] = intval($_POST['id_movcon']);
    $anagrafica = new Anagrafica();
    $fornitore = $anagrafica->getPartner(intval($_POST['clfoco']));
    $form['hidden_req'] = filter_input(INPUT_POST, 'hidden_req');
// ...e della testata
    foreach ($_POST['search'] as $k => $v) {
        $form['search'][$k] = $v;
    }
    $form['seziva'] = intval($_POST['seziva']);
    $form['codvat'] = intval($_POST['codvat']);
    $form['datfat'] = substr($_POST['datfat'], 0, 10);
    $form['datreg'] = substr($_POST['datreg'], 0, 10);
    $form['numfat'] = substr($_POST['numfat'], 0, 40);
    $form['clfoco'] = intval($_POST['clfoco']);
    $form['mas_fixed_assets'] = substr($_POST['mas_fixed_assets'], 0, 3);
    $form['mas_found_assets'] = substr($_POST['mas_found_assets'], 0, 3);
    $form['mas_cost_assets'] = substr($_POST['mas_cost_assets'], 0, 3);
    $form['id_no_deduct_vat'] = intval($_POST['id_no_deduct_vat']);
    $form['no_deduct_vat_rate'] = floatval($_POST['no_deduct_vat_rate']);
    $form['acc_no_deduct_cost'] = intval($_POST['acc_no_deduct_cost']);
    $form['no_deduct_cost_rate'] = floatval($_POST['no_deduct_cost_rate']);
    $form['super_ammort'] = floatval($_POST['super_ammort']);
    $form['type_mov'] = intval($_POST['type_mov']);
    $form['descri'] = filter_input(INPUT_POST, 'descri');
    $form['unimis'] = filter_input(INPUT_POST, 'unimis');
    $form['quantity'] = floatval($_POST['quantity']);
    $form['a_value'] = floatval($_POST['a_value']);
    $form['ss_amm_min'] = intval($_POST['ss_amm_min']);
    $form['pagame'] = intval($_POST['pagame']);
    $form['change_pag'] = $_POST['change_pag'];
    $form['fattura_elettronica_original_name'] = filter_input(INPUT_POST,'fattura_elettronica_original_name');
    $form['id_doc'] = intval($_POST['id_doc']);


    if (isset($_GET['id_doc']) && intval($_GET['id_doc']) >=1 ) {
      $form['id_doc']= intval($_GET['id_doc']);
      // INIZIO acquisizione e pulizia file xml o p7m
      $tfiles=gaz_dbi_get_row($gTables['files'], 'id_doc', $form['id_doc']);
      $form['fattura_elettronica_original_name']=$tfiles['title'];
      $file_name = DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . $form['fattura_elettronica_original_name'];
      $p7mContent = @file_get_contents($file_name);
      $p7mContent = tryBase64Decode($p7mContent);
      $tmpfatt = tempnam(DATA_DIR . 'files/tmp/', 'ricfat');
      file_put_contents($tmpfatt, $p7mContent);
      if (FALSE !== der2smime($tmpfatt)) {
        $cert = tempnam(DATA_DIR . 'files/tmp/', 'ricpem');
        $retn = openssl_pkcs7_verify($tmpfatt, PKCS7_NOVERIFY, $cert);
        unlink($cert);
        $isFatturaElettronicaSemplificata = false;
        $fatt = extractDER($tmpfatt);
        if (empty($fatt)) {
          $test = @base64_decode(file_get_contents($tmpfatt));
          // Salto lo header (INDISPENSABILE perché la regexp funzioni sempre)
          if (strpos($test, 'FatturaElettronicaSemplificata') !== FALSE) {
            $isFatturaElettronicaSemplificata = true;
            if (preg_match('#(<[^>]*FatturaElettronicaSemplificata.*</[^>]*FatturaElettronicaSemplificata>)#', substr($test, 54), $gregs)) {
              $fatt = '<'.'?'.'xml version="1.0"'.'?'.'>' . $gregs[1]; // RECUPERO INTESTAZIONE XML
            }
          } else {
            if (preg_match('#(<[^>]*FatturaElettronica.*</[^>]*FatturaElettronica>)#', substr($test, 54), $gregs)) {
              $fatt = '<'.'?'.'xml version="1.0"'.'?'.'>' . $gregs[1]; // RECUPERO INTESTAZIONE XML
            }
          }
        } else {
          if (strpos($p7mContent, 'FatturaElettronicaSemplificata') !== FALSE) {
            $isFatturaElettronicaSemplificata = true;
          }
        }
      }
      unlink($tmpfatt);
      if (!empty($fatt)) {
        $invoiceContent = $fatt;
      } else {
        $invoiceContent = removeSignature($p7mContent);
      }
      $xml = new DOMDocument;
      $xml->preserveWhiteSpace = false;
      $xml->formatOutput = true;
      if (FALSE === @$xml->loadXML(mb_convert_encoding($invoiceContent, 'UTF-8', mb_list_encodings()))) {
        // elimino le sequenze di caratteri non stampabili aggiunti dalla firma (da testare approfonditamente)
        $invoiceContent = preg_replace('/[[:^print:]]/', "", $invoiceContent);
        if (FALSE === @$xml->loadXML(mb_convert_encoding($invoiceContent, 'UTF-8', mb_list_encodings()))) {
          $xml->loadXML(recoverCorruptedXML($invoiceContent));
        }
      }
    }

    if ($form['change_pag'] != $form['pagame']) {  //se è stato cambiato il pagamento
        $new_pag = gaz_dbi_get_row($gTables['pagame'], "codice", $form['pagame']);
        $old_pag = gaz_dbi_get_row($gTables['pagame'], "codice", $form['change_pag']);
        if ($new_pag && ($new_pag['tippag'] == 'B' || $new_pag['tippag'] == 'T' || $new_pag['tippag'] == 'V')
                && ( $old_pag['tippag'] == 'C' || $old_pag['tippag'] == 'D' || $old_pag['tippag'] == 'O')) { // se adesso devo mettere le spese e prima no
            $form['numrat'] = $new_pag['numrat'];
            if ($toDo == 'update') {  //se è una modifica mi baso sulle vecchie spese
                $old_header = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", $form['id_movcon']);
                if ($old_header['speban'] > 0 && $fornitore['speban'] == "S") {
                    $form['speban'] = 0;
                } elseif ($old_header['speban'] == 0 && $fornitore['speban'] == "S") {
                    $form['speban'] = 0;
                } else {
                    $form['speban'] = 0.00;
                }
            } else { //altrimenti mi avvalgo delle nuove dell'azienda
                $form['speban'] = 0;
            }
        } elseif ($new_pag && ($new_pag['tippag'] == 'C' || $new_pag['tippag'] == 'D' || $new_pag['tippag'] == 'O')
                && ($old_pag && ($old_pag['tippag'] == 'B' || $old_pag['tippag'] == 'T' || $old_pag['tippag'] == 'V'))) { // se devo togliere le spese
            $form['speban'] = 0.00;
            $form['numrat'] = 1;
        }
        $form['pagame'] = $_POST['pagame'];
        $form['change_pag'] = $_POST['pagame'];
    }
    $form['valamm'] = floatval($_POST['valamm']);

    if ($form['valamm'] < 0.1 || $form['valamm'] > 100) {
        // limito a valori reali
        $form['valamm'] = 0.00;
    }
// Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {
        $utsfat = gaz_format_date($form['datfat'], 3);
        $utsreg = gaz_format_date($form['datreg'], 3);
        if ($utsreg < $utsfat) {
            $msg['err'][] = 'regdat';
        }
        if (empty($form['numfat'])) {
            $msg['err'][] = 'numfat';
        }
// --- inizio controlli
        if ($toDo == 'update') {  // controlli in caso di modifica
        } else {                   //controlli in caso di inserimento
        }
        if ($form["clfoco"] < 100000001)
            $msg['err'][] = 'clfoco';
        if (!gaz_format_date($form["datreg"], 'chk'))
            $msg['err'][] = 'datreg';
        if (!gaz_format_date($form["datfat"], 'chk'))
            $msg['err'][] = 'datfat';
        if (empty($form["pagame"]))
            $msg['err'][] = 'pagame';
        if ($form["mas_fixed_assets"] < 100)
            $msg['err'][] = 'mas_fixed_assets';
        if ($form["mas_found_assets"] < 100)
            $msg['err'][] = 'mas_found_assets';
        if ($form["mas_cost_assets"] < 100)
            $msg['err'][] = 'mas_cost_assets';
        if (empty($form["descri"]))
            $msg['err'][] = 'descri';
        if ($form["no_deduct_cost_rate"] >= 0.01 && $form["acc_no_deduct_cost"] < 100000000)
            $msg['err'][] = 'deduct_cost';
        if ($form["no_deduct_vat_rate"] >= 0.01 && $form["id_no_deduct_vat"] < 1)
            $msg['err'][] = 'deduct_vat';
        if ($form["ss_amm_min"] >= 100)
            $msg['err'][] = 'ss_amm_min';
// --- fine controlli
        if (count($msg['err']) == 0) {// nessun errore
          if ($toDo == 'update') { // e' una modifica
              gaz_dbi_table_update('assets',array('id',intval($_GET['id'])), $form);
              header("Location: ../finann/report_assets.php");
              exit;
          } else { // e' un'inserimento
              $year = substr($form['datreg'], 6, 4);
              $descri = $form['descri'];
              // ricavo il protocollo da assegnare all'acquisto
              $rs_ultimo_tesdoc = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datemi) = $year AND tipdoc LIKE 'AF_' AND seziva = " . $form['seziva'], "protoc DESC", 0, 1);
              $ultimo_tesdoc = gaz_dbi_fetch_array($rs_ultimo_tesdoc);
              $rs_ultimo_tesmov = gaz_dbi_dyn_query("*", $gTables['tesmov'], "YEAR(datreg) = $year AND regiva = 6 AND seziva = " . $form['seziva'], "protoc DESC", 0, 1);
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
              $lastProtocol++;
              // testata movimento contabile
              $form['caucon'] = 'AFA';
              $form['descri'] = 'FATTURA DI ACQUISTO';
              $form['regiva'] = 6;
              $form['operat'] = 1;
              $form['protoc'] = $lastProtocol;
              $form['numdoc'] = $form['numfat'];

              $form['datreg'] = gaz_format_date($form['datreg'], true);
              $form['datdoc'] = gaz_format_date($form['datfat'], true);
              $id_tesmov =gaz_dbi_table_insert('tesmov', $form);
              $form['id_tes'] = $id_tesmov;
              $form['id_movcon'] = $id_tesmov;
              // trovo il conto immobilizzazione
              $form['acc_fixed_assets'] = lastAccount($form['mas_fixed_assets'], $form['ss_amm_min']);
              // trovo il conto fondo ammortamento
              $form['acc_found_assets'] = lastAccount($form['mas_found_assets'], $form['ss_amm_min']);
              // trovo il conto costo ammortamento
              $form['acc_cost_assets'] = lastAccount($form['mas_cost_assets'], $form['ss_amm_min']);
              // inserisco i dati sulla tabella assets
              $form['descri'] = $descri;
              $form['type_mov'] = 1; // è un acquisto ,10 rivalutazione, 50 ammortamento, 90 alienazione
              $form['id_assets'] = gaz_dbi_table_insert('assets', $form);


              // ripreno i file di traduzione
              require("./lang." . $admin_aziend['lang'] . ".php");
              $transl = $strScript['admin_assets.php'];
              // creo i tre conti relativi ai mastri scelti
              $form['descri'] = $transl['des_fixed_assets'] . strtolower($descri);
              $form['codice'] = $form['acc_fixed_assets'];
              gaz_dbi_table_insert('clfoco', $form);
              $form['descri'] = $transl['des_found_assets'] . strtolower($descri);
              $form['codice'] = $form['acc_found_assets'];
              gaz_dbi_table_insert('clfoco', $form);
              $form['descri'] = $transl['des_cost_assets'] . strtolower($descri);
              $form['codice'] = $form['acc_cost_assets'];
              gaz_dbi_table_insert('clfoco', $form);
              // recupero i dati iva ed eseguo i calcoli
              $iva = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['codvat']);
              $form['no_imponi'] = 0;
              $form['no_impost'] = 0;
              if ($form['id_no_deduct_vat'] > 0) { // ho una parte di iva indetraibile che si andrà a sommare ai costi
                  // per i righi iva
                  $no_iva = gaz_dbi_get_row($gTables['aliiva'], "codice", $form['id_no_deduct_vat']);
                  $form['no_imponi'] = round($form['quantity'] * $form['a_value'] * $form['no_deduct_vat_rate'] / 100, 2);
                  $form['no_impost'] = round($form['no_imponi'] * $no_iva['aliquo'] / 100, 2);
                  $form['imponi'] = round($form['quantity'] * $form['a_value'] - $form['no_imponi'], 2);
                  $form['impost'] = round($form['imponi'] * $iva['aliquo'] / 100, 2);
                  // per i righi contabili
                  $form['import'] = $form['imponi'] + $form['impost'] + $form['no_imponi'] + $form['no_impost'];
              } else {
                  $form['imponi'] = round($form['quantity'] * $form['a_value'], 2);
                  $form['impost'] = round($form['imponi'] * $iva['aliquo'] / 100, 2);
                  $form['import'] = $form['imponi'] + $form['impost'];
              }
              $import = $form['import'];
              // rigo conto fornitore con importo totale
              $form['codcon'] = $form['clfoco'];
              $form['darave'] = 'A';
              gaz_dbi_table_insert('rigmoc', $form);
              $last_id_rig = gaz_dbi_last_id();
              // inserisco lo scadenzario
              $pagame = gaz_dbi_get_row($gTables['pagame'], 'codice', $form['pagame']);
              require("../../library/include/expiry_calc.php");
              $ex = new Expiry;
              $rs_ex = $ex->CalcExpiry($import, gaz_format_date($form['datfat'], true), $pagame['tipdec'], $pagame['giodec'], $pagame['numrat'], $pagame['tiprat'], $pagame['mesesc'], $pagame['giosuc']);
              foreach ($rs_ex as $k => $v) {
                  $paymov_value = array('id_tesdoc_ref' => $year . '6' . $form['seziva'] . str_pad($form['protoc'], 9, 0, STR_PAD_LEFT),
                      'id_rigmoc_doc' => $last_id_rig,
                      'amount' => $v['amount'],
                      'expiry' => $v['date']);
                  paymovInsert($paymov_value);
              }
              // rigo conto immobilizzazione
              $form['codcon'] = $form['acc_fixed_assets'];
              $form['darave'] = 'D';
              // agli imponibili si dovrà sommare anche l'eventuale iva indetraibile (che diventa costo storico)
              $form['import'] = $form['imponi'] + $form['no_imponi'] + $form['no_impost'];
              gaz_dbi_table_insert('rigmoc', $form);
              // rigo iva
              $form['codiva'] = $form['codvat'];
              $form['periva'] = $iva['aliquo'];
              $form['tipiva'] = $iva['tipiva'];
              $form['operation_type'] = 'BENAMM';
              gaz_dbi_table_insert('rigmoi', $form);
              //e rigo conto imposta
              $form['codcon'] = $admin_aziend['ivaacq'];
              $form['import'] = $form['impost'];
              gaz_dbi_table_insert('rigmoc', $form);
              if ($form['id_no_deduct_vat'] > 0) { // ho iva indetraibile che genererà un apposito rigo iva
                  // rigo iva indetraibile
                  $form['imponi'] = $form['no_imponi'];
                  $form['impost'] = $form['no_impost'];
                  $form['codiva'] = $form['id_no_deduct_vat'];
                  $form['periva'] = $no_iva['aliquo'];
                  $form['tipiva'] = $no_iva['tipiva'];
                  gaz_dbi_table_insert('rigmoi', $form);
              }
              // lo inserisco anche come articolo (in futuro ho intenzione di automatizzare la rivendita)
              $form['codice']='ASSET_'.$form['id_assets'];
              gaz_dbi_put_row($gTables['assets'], 'id', $form['id_assets'], 'codice_artico', $form['codice']);
              $form['descri'] = ucfirst($descri);
              $form['preacq'] = $form['import'];
              $form['aliiva'] = $form['codvat'];
              $form['uniacq'] = $form['unimis'];
              gaz_dbi_table_insert('artico', $form);
              // inserisco anche in tesdoc-rigdoc
              $form['tipdoc'] = $form['caucon']; // AFA
              $form['id_con'] = $id_tesmov;
              $form['template'] = 'FatturaAcquisto';
              $form['datemi'] = $form['datdoc'];
              $form['datfat'] = $form['datdoc'];
              $ultimo_id =tesdocInsert($form);
              $form['id_tes'] = $ultimo_id;
              //$form['codart'] = $form['codice'];
              $form['quanti'] = $form['quantity'];
              $form['prelis'] = $form['a_value'];
              $form['pervat'] = $iva['aliquo'];
              $form['codric'] = $form['acc_fixed_assets'];
              rigdocInsert($form);
              // se elettronica cambio lo stato alla fattura nella tabella files per indicarla come acquisita
              if ($form['id_doc']>=1){
                $where=[];
                $where[]="id_doc";
                $where[]=$form['id_doc'];
                $set['status']=1;
                gaz_dbi_table_update("files", $where, $set);
              }
              // vado alla pagina del report sul modulo Fine Anno (finann)
              header("Location: ../finann/report_assets.php");
              exit;
          }
        }
    }
// Se viene inviata la richiesta di conferma fornitore
    if ($_POST['hidden_req'] == 'clfoco') {
        $anagrafica = new Anagrafica();
        if (preg_match("/^id_([0-9]+)$/", $form['clfoco'], $match)) {
            $fornitore = $anagrafica->getPartnerData($match[1], 1);
        } else {
            $fornitore = $anagrafica->getPartner($form['clfoco']);
        }
        $form['in_codvat'] = $fornitore['aliiva'];
        $form['pagame'] = $fornitore['codpag'];
        $form['change_pag'] = $fornitore['codpag'];
        $form['hidden_req'] = '';
    }
} elseif ((!isset($_POST['Update'])) and ( isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $form = gaz_dbi_get_row($gTables['assets'], "id", intval($_GET['id']));
    // recupero i dati iva ed eseguo i calcoli
    $tesmov = gaz_dbi_get_row($gTables['tesmov'], "id_tes", $form['id_movcon']);
    // è un acquisto (type_mov=1) quindi id_movcon contiene la testata del movimento contabile, in altri casi contiene il'id_rig
    $rigmoi = gaz_dbi_get_row($gTables['rigmoi'], "tipiva ='I' AND id_tes", $form['id_movcon']);
    $iva = gaz_dbi_get_row($gTables['aliiva'], "codice", $rigmoi['codiva']);
    $rigmoi_no = gaz_dbi_get_row($gTables['rigmoi'], "tipiva ='D' AND id_tes", $form['id_movcon']);
    $iva_no = gaz_dbi_get_row($gTables['aliiva'], "codice", ($rigmoi_no?$rigmoi_no['codiva']:$admin_aziend['preeminent_vat']));
    $anagrafica = new Anagrafica();
    $fornitore = $anagrafica->getPartner($tesmov['clfoco']);
    $form['hidden_req'] = '';
    $form['clfoco'] = $tesmov['clfoco'];
    $form['search']['clfoco'] = substr($fornitore['ragso1'], 0, 10);
    $form['seziva'] = $tesmov['seziva'];
    $form['codvat'] = $rigmoi['codiva'];
    $form['mas_fixed_assets'] = substr($form['acc_fixed_assets'], 0, 3);
    $form['mas_found_assets'] = substr($form['acc_found_assets'], 0, 3);
    $form['mas_cost_assets'] = substr($form['acc_cost_assets'], 0, 3);
    $form['id_no_deduct_vat'] = $rigmoi_no?$rigmoi_no['codiva']:$admin_aziend['preeminent_vat'];
    $form['datreg'] = gaz_format_date($tesmov['datreg'],false,false);
    $form['protoc'] = $tesmov['protoc'];
    $form['numfat'] = $tesmov['numdoc'];
    $form['datfat'] = gaz_format_date($tesmov['datdoc'],false,false);
    $form['change_pag'] = $form['pagame'];
    $form['fattura_elettronica_original_name'] = '';
    $form['id_doc'] = 0;
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['numfat'] = '';
    $form['a_value'] = 0;
    $form['datfat'] = '';
    $form['clfoco'] = '';
    $fornitore['indspe'] = "";
    $fornitore['citspe'] = "";
    $form['pagame'] = '';
    $form['change_pag'] = '';
    $form['fattura_elettronica_original_name'] = '';
    $form['id_doc'] = 0;
    $form['codvat'] = $admin_aziend['preeminent_vat'];
    // INIZIO acquisizione dati da fattura elettronica (quando passata referenza su url)
    if (isset($_GET['id_doc']) && intval($_GET['id_doc']) >=1 ) {
      $form['id_doc']= intval($_GET['id_doc']);
      // INIZIO acquisizione e pulizia file xml o p7m
      $tfiles=gaz_dbi_get_row($gTables['files'], 'id_doc', $form['id_doc']);
      $form['fattura_elettronica_original_name']=$tfiles['title'];
      $file_name = DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . $form['fattura_elettronica_original_name'];
      $p7mContent = @file_get_contents($file_name);
      $p7mContent = tryBase64Decode($p7mContent);
      $tmpfatt = tempnam(DATA_DIR . 'files/tmp/', 'ricfat');
      file_put_contents($tmpfatt, $p7mContent);
      if (FALSE !== der2smime($tmpfatt)) {
        $cert = tempnam(DATA_DIR . 'files/tmp/', 'ricpem');
        $retn = openssl_pkcs7_verify($tmpfatt, PKCS7_NOVERIFY, $cert);
        unlink($cert);
        $isFatturaElettronicaSemplificata = false;
        $fatt = extractDER($tmpfatt);
        if (empty($fatt)) {
          $test = @base64_decode(file_get_contents($tmpfatt));
          // Salto lo header (INDISPENSABILE perché la regexp funzioni sempre)
          if (strpos($test, 'FatturaElettronicaSemplificata') !== FALSE) {
            $isFatturaElettronicaSemplificata = true;
            if (preg_match('#(<[^>]*FatturaElettronicaSemplificata.*</[^>]*FatturaElettronicaSemplificata>)#', substr($test, 54), $gregs)) {
              $fatt = '<'.'?'.'xml version="1.0"'.'?'.'>' . $gregs[1]; // RECUPERO INTESTAZIONE XML
            }
          } else {
            if (preg_match('#(<[^>]*FatturaElettronica.*</[^>]*FatturaElettronica>)#', substr($test, 54), $gregs)) {
              $fatt = '<'.'?'.'xml version="1.0"'.'?'.'>' . $gregs[1]; // RECUPERO INTESTAZIONE XML
            }
          }
        } else {
          if (strpos($p7mContent, 'FatturaElettronicaSemplificata') !== FALSE) {
            $isFatturaElettronicaSemplificata = true;
          }
        }
      }
      unlink($tmpfatt);
      if (!empty($fatt)) {
        $invoiceContent = $fatt;
      } else {
        $invoiceContent = removeSignature($p7mContent);
      }
      $xml = new DOMDocument;
      $xml->preserveWhiteSpace = false;
      $xml->formatOutput = true;
      if (FALSE === @$xml->loadXML(utf8_encode($invoiceContent))) {
        // elimino le sequenze di caratteri non stampabili aggiunti dalla firma (da testare approfonditamente)
        $invoiceContent = preg_replace('/[[:^print:]]/', "", $invoiceContent);
        if (FALSE === @$xml->loadXML(utf8_encode($invoiceContent))) {
          $xml->loadXML(recoverCorruptedXML($invoiceContent));
        }
      }
      $xpath = new DOMXpath($xml);
      $f_ex=true;
   		$ndoc = 0;
      $docs = $xml->getElementsByTagName('FatturaElettronicaBody');
      foreach ($docs as $doc) {
        $ndoc++;
      }
      $curr_doc_cont = $xpath->query("//FatturaElettronicaBody");
      $cudo=$curr_doc_cont->item(0);
			$DatiRiepilogo = $cudo->getElementsByTagName('DatiRiepilogo');
			$ImpostaDocumento=0.00;
			foreach ($DatiRiepilogo as $dr) {
					if ($dr->getElementsByTagName('Imposta')->length >= 1) {
						$ImpostaDocumento +=  (float)$dr->getElementsByTagName('Imposta')->item(0)->nodeValue;
					}
					$form['a_value']+= (float)$dr->getElementsByTagName('ImponibileImporto')->item(0)->nodeValue;
					if ($dr->getElementsByTagName('Natura')->length >= 1) { // se ho l'elemento Natura = 6.x dovrò ricercare l'aliquota per il reverse charge a tutto il documento ed attribuirla a tutti i righi del documento
						$Natura = $dr->getElementsByTagName('Natura')->item(0)->nodeValue;
						if ( substr($Natura,0,2) == 'N6' ) { // dovrò fare il reverse charge sostituisco con il codice iva relativo
							$naturaN6 = $Natura;
						}
					}
          // controllo se lo SdI ha consentito la PORCATA!
          $ctrlaliquo=(float)$dr->getElementsByTagName('AliquotaIVA')->item(0)->nodeValue;
          $ctrlimponi=(float)$dr->getElementsByTagName('ImponibileImporto')->item(0)->nodeValue;
          if ( $ctrlaliquo > 0 ) {
            $ctrliva=round($ctrlimponi*$ctrlaliquo/100,2);
            $diffiva=round((float)$dr->getElementsByTagName('Imposta')->item(0)->nodeValue-$ctrliva,2);
            if ( abs($diffiva) >= 0.01) { // PORCATA! L'IVA NON RISPETTA LE REGOLE, sono costretto ad inserire questo rigo fittizio per indicare l'anomalia ma soprattutto al fine di contabilizzare in accordo con la stessa (porcata)
            }
          }
      }
      // Numero fattura
      $form['numfat'] = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Numero")->item(0)->nodeValue;
      // Date
      $form['datfat'] = gaz_format_date($xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Data")->item(0)->nodeValue,false,true);
      $form['datreg'] =$form['datfat'];
      //Fornitore
			$codiva=$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue;
      if ($xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->length>=1){
        $codfis=$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0)->nodeValue;
      } else {
        $codfis=$codiva;
      }
      $form['pariva'] = $codiva;
			$form['codfis'] = $codfis;
			$anagrafica = new Anagrafica();
      $partner_with_same_pi = $anagrafica->queryPartners('*', "codice BETWEEN " . $admin_aziend['masfor'] . "000000 AND " . $admin_aziend['masfor'] . "999999 AND pariva = '" . $form['pariva'] . "'", "CASE WHEN codfis LIKE '" . $form['codfis'] . "' THEN 1 ELSE 0 END DESC, codice DESC");
      $anagra_with_same_pi = false;
      if ($partner_with_same_pi) { // ho già il fornitore sul piano dei conti
        //var_dump($partner_with_same_pi);
        $form['clfoco'] = $partner_with_same_pi[0]['codice'];
        $form['pagame'] = $partner_with_same_pi[0]['codpag']; // condizione di pagamento
        $form['change_pag'] = $form['pagame'];
        $fornitore['indspe'] = $partner_with_same_pi[0]['indspe'];
        $fornitore['citspe'] = $partner_with_same_pi[0]['citspe'];
        if ( $partner_with_same_pi[0]['aliiva'] > 0 ){
          $form['codvat'] = $partner_with_same_pi[0]['aliiva'];
        }
      } else { // se non ho già un fornitore sul piano dei conti provo a vedere nelle anagrafiche
        $rs_anagra_with_same_pi = gaz_dbi_query_anagra(array("*"), $gTables['anagra'], array("pariva" => "='" . $form['pariva'] . "'"), array("pariva" => "DESC"), 0, 1);
        $anagra_with_same_pi = gaz_dbi_fetch_array($rs_anagra_with_same_pi);
      }
      // se non ce l'ho creo comunque il fornitore con i dati dell'xml
      if (!$anagra_with_same_pi && !$partner_with_same_pi) { // non ho nulla: devo inserire tutto (anagrafica e fornitore) basandomi sul pagamento e sui conti di costo scelti dall'utente
        $new_partner = array_merge(gaz_dbi_fields('clfoco'), gaz_dbi_fields('anagra'));
        $new_partner['codpag'] = 1;
        $new_partner['sexper'] = 'G';
        // setto le colonne in base ai dati di questa fattura elettronica
        $new_partner['pariva'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue;
        if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0)) {
          $new_partner['codfis'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0)->nodeValue;
          // ho un codice fiscale posso vedere se è una persona fisica e di quale sesso
          preg_match('/^[a-z]{6}[0-9]{2}[a-z]([0-9]{2})[a-z][0-9]{3}[a-z]$/i',trim($new_partner['codfis']),$match);
          if (count($match)>1){
            if ($match[1] > 40 ){  // è un codice fiscale femminile
              $new_partner['sexper'] = 'F';
            } else {
              $new_partner['sexper'] = 'M';
            }
          } else { // giuridica
            $new_partner['sexper'] = 'G';
          }
        }
        if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Nome")->item(0)) {
          $new_partner['legrap_pf_nome'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Nome")->item(0)->nodeValue;
          $new_partner['legrap_pf_cognome'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Cognome")->item(0)->nodeValue;
          $new_partner['descri'] = $new_partner['legrap_pf_cognome']. ' '.$new_partner['legrap_pf_nome'];
          if (strlen($new_partner['descri'])>50){
            $new_partner['ragso1'] = $new_partner['legrap_pf_cognome'];
            $new_partner['ragso2'] = $new_partner['legrap_pf_nome'];
          } else {
            $new_partner['ragso1'] = $new_partner['descri'];
          }
        }
        if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Denominazione")->item(0)) {
          $new_partner['descri'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Denominazione")->item(0)->nodeValue;
          if (strlen($new_partner['descri'])>50){
            $new_partner['ragso1'] = substr(str_replace(array("'",'"',"`"),"",$new_partner['descri']),0,50);
            $new_partner['ragso2'] = substr(str_replace(array("'",'"',"`"),"",$new_partner['descri']),50,100);
          } else {
            $new_partner['ragso1'] = str_replace(array("'",'"',"`"),"",$new_partner['descri']);
          }
        }
        $new_partner['indspe'] = ucwords(strtolower($xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Indirizzo")->item(0)->nodeValue));
        $form['indspe'] = $new_partner['indspe'];
        if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/NumeroCivico")->item(0)){
          $new_partner['indspe'] .= ', '.$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/NumeroCivico")->item(0)->nodeValue;
        }
        $new_partner['capspe'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/CAP")->item(0)->nodeValue;
        $new_partner['citspe'] = strtoupper($xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Comune")->item(0)->nodeValue);
        $form['citspe'] = $new_partner['citspe'];
        if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Provincia")->item(0)){
          $new_partner['prospe'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Provincia")->item(0)->nodeValue;
        }
        $new_partner['country'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Nazione")->item(0)->nodeValue;
        $new_partner['counas'] = $new_partner['country'];
        $new_partner['id_currency'] =1;
        $new_partner['id_language'] =1;
        if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Contatti/Telefono")->item(0)) {
          $new_partner['telefo'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Contatti/Telefono")->item(0)->nodeValue;
        }
        if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Contatti/Fax")->item(0)) {
          $new_partner['fax'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Contatti/Fax")->item(0)->nodeValue;
        }
        if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Contatti/Email")->item(0)) {
          $new_partner['e_mail'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Contatti/Email")->item(0)->nodeValue;
        }
        if (@$xpath->query("//FatturaElettronicaBody/DatiPagamento/DettaglioPagamento/IBAN")->item(0)) {
          $new_partner['iban'] = $xpath->query("//FatturaElettronicaBody/DatiPagamento/DettaglioPagamento/IBAN")->item(0)->nodeValue;
        }
        // trovo l'ultimo codice disponibile sul piano dei conti
        $rs_last_partner = gaz_dbi_dyn_query("*", $gTables['clfoco'], 'codice BETWEEN ' . $admin_aziend['masfor'] . '000001 AND ' . $admin_aziend['masfor'] . '999999', "codice DESC", 0, 1);
        $last_partner = gaz_dbi_fetch_array($rs_last_partner);
        if (!$last_partner) {
          $new_partner['codice']=$admin_aziend['masfor'].'000001';
        } else {
          $new_partner['codice']=$last_partner['codice']+1;
        }
        // inserisco il partner
        $anagrafica->insertPartner($new_partner);
        $form['clfoco']=$new_partner['codice'];
      } else if ($anagra_with_same_pi) { // devo inserire il fornitore, ho già l'anagrafica
        $anagra_with_same_pi['id_anagra'] = $anagra_with_same_pi['id'];
        $form['clfoco'] = $anagrafica->anagra_to_clfoco($anagra_with_same_pi, $admin_aziend['masfor'], $form['pagame']);
        $form['indspe'] = $anagra_with_same_pi['indspe'];
        $form['citspe'] = $anagra_with_same_pi['citspe'];
      }



    }
    // FINE acquisizione dati da fattura elettronica

    $form['hidden_req'] = '';
    $form['id_movcon'] = "";
    // ricerco l'ultimo inserimento per ricavarne la data
    $rs_last = gaz_dbi_dyn_query('datreg', $gTables['tesmov'], 1, "id_tes DESC", 0, 1);
    $last = gaz_dbi_fetch_array($rs_last);
    if ($form['id_doc']>=1){
    } else if ($last) {
      $form['datreg'] = gaz_format_date($last['datreg'], false, true);
    } else {
      $form['datreg'] = date("d/m/Y");
    }
    $form['search']['clfoco'] = '';
    if (isset($_GET['seziva'])) {
        $form['seziva'] = intval($_GET['seziva']);
    } else {
        $form['seziva'] = 1;
    }
    $form['protoc'] = 0;
    $form['valamm'] = 0;
    $form['mas_fixed_assets'] = $admin_aziend['mas_fixed_assets'];
    $form['mas_found_assets'] = $admin_aziend['mas_found_assets'];
    $form['mas_cost_assets'] = $admin_aziend['mas_cost_assets'];
    $form['super_ammort'] = $admin_aziend['super_amm_rate'];
    $form['id_no_deduct_vat'] = 0;
    $form['no_deduct_vat_rate'] = 0;
    $form['acc_no_deduct_cost'] = 0;
    $form['no_deduct_cost_rate'] = 0;
    $form['type_mov'] = '';
    $form['descri'] = '';
    $form['unimis'] = 'n';
    $form['quantity'] = 1;
    $form['ss_amm_min'] = 999;
}
if (isset($_POST['ritorno'])) {
    $form['ritorno'] = $_POST['ritorno'];
} else {
    $form['ritorno'] = $_SERVER['HTTP_REFERER'] . ' ';
}

// ricavo il gruppo e la specie dalla tabella ammortamenti ministeriali
$xmlamm = simplexml_load_file('../../library/include/ammortamenti_ministeriali.xml') or die("Error: Cannot create object for file ammortamenti ministeriali.xml");
preg_match("/^([0-9 ]+)([a-zA-Z ]+)$/", $admin_aziend['amm_min'], $m);
foreach ($xmlamm->gruppo as $vg) {
    if ($vg->gn[0] == $m[1]) {
        foreach ($vg->specie as $v) {
            if ($v->ns[0] == $m[2]) {
                $amm_gr = $vg->gn[0] . '-' . $vg->gd[0];
                $amm_sp = $v->ns[0] . '-' . $v->ds[0];
                // Se viene scelta o cambiata la voce tabella ammortamenti carico il suo nuovo valore
                if ($form['hidden_req'] == 'ss_amm_min') {
                    $form['valamm'] = $v->ssrate[$form['ss_amm_min']][0];
                    $form['hidden_req'] = '';
                }
            }
        }
    }
}
$amount = CalcolaImportoRigo($form['quantity'], $form['a_value'], 0);
$gg = intval(365 - date("z", gaz_format_date($form['datreg'], 2)));
require("../../library/include/header.php");
$script_transl = HeadMain(0, array('custom/autocomplete'));
?>
<script>
    $(function () {
        function sumVal() {
            var quantity = parseFloat($('#quantity').val());
            var valamm = parseFloat($('#valamm').val());
            var gg = parseFloat($('#gg').val());
            var a_value = parseFloat($('#a_value').val());
            var amount = a_value * quantity;
            var amount_rate = amount * valamm * gg / 36500;
            $("#amount").text(amount.toFixed(2).toString());
            ;
            $("#amount_rate").text(amount_rate.toFixed(2).toString());
            ;
        }
        $("#datreg, #datfat").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
        $("#datreg").change(function () {
            this.form.submit();
        });
        $('#valamm, #a_value, #quantity').change(function () {
            sumVal();
        });
<?php if ($toDo == 'update') {
    ?>
            $("#datreg,#numfat,#datfat,#mas_fixed_assets,#mas_found_assets,#mas_cost_assets,#codvat,#seziva,#clfoco").prop("disabled", true);
    <?php
}
?>
    });
</script>
<?php
$gForm = new acquisForm();
if (count($msg['err']) > 0) { // ho un errore
    $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
if ($toDo == 'update') { // allerto che le modifiche devono essere fatte anche sul movimento contabile
    $script_transl['war']['update'] .= ' n.<a class="btn btn-xs btn-default" href="../contab/admin_movcon.php?Update&id_tes='.$form['id_movcon'].'" >'.$form['id_movcon'].' <i class="glyphicon glyphicon-edit"></i></a>';
    $gForm->gazHeadMessage(array('update'), $script_transl['war'], 'war');
}
?>
<form class="form-horizontal" role="form" method="post" name="docacq" enctype="multipart/form-data" >
    <input type="hidden" name="<?php echo ucfirst($toDo); ?>" value="">
    <input type="hidden" value="<?php echo $form['hidden_req'] ?>" name="hidden_req" />
    <input type="hidden" value="<?php echo $form['fattura_elettronica_original_name']; ?>" name="fattura_elettronica_original_name" >
    <input type="hidden" value="<?php echo $form['id_doc']; ?>" name="id_doc" >
    <input type="hidden" value="<?php echo $form['id_movcon']; ?>" name="id_movcon">
    <input type="hidden" value="<?php echo $form['type_mov']; ?>" name="type_mov">
    <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno">
    <input type="hidden" value="<?php echo $form['change_pag']; ?>" name="change_pag">
    <input type="hidden" value="<?php echo $gg; ?>" id="gg">
    <div class="text-center">
        <p>
            <b>
                <?php
                echo $script_transl[$toDo] . ' ' . $script_transl['title'] . ':';
                if ($toDo == 'update') {
                    $anagrafica = new Anagrafica();
                    $fornitore= $anagrafica->getPartner($form['clfoco']);
                    echo $fornitore['ragso1'];
                ?>
    <input type="hidden" value="<?php echo $form['clfoco']; ?>" name="clfoco">
    <input type="hidden" value="<?php echo $form['seziva']; ?>" name="seziva">
    <input type="hidden" value="<?php echo $form['codvat']; ?>" name="codvat">
    <input type="hidden" value="<?php echo $form['datreg']; ?>" name="datreg">
    <input type="hidden" value="<?php echo $form['numfat']; ?>" name="numfat">
    <input type="hidden" value="<?php echo $form['datfat']; ?>" name="datfat">
    <input type="hidden" value="<?php echo $form['mas_fixed_assets']; ?>" name="mas_fixed_assets">
    <input type="hidden" value="<?php echo $form['mas_found_assets']; ?>" name="mas_found_assets">
    <input type="hidden" value="<?php echo $form['mas_cost_assets']; ?>" name="mas_cost_assets">
    <input type="hidden" value="<?php echo $form['search']['clfoco']; ?>" name="search[clfoco]">

                <?php
                } else {
                    $select_fornitore = new selectPartner("clfoco");
                    $select_fornitore->selectDocPartner('clfoco', $form['clfoco'], $form['search']['clfoco'], 'clfoco', $script_transl['mesg'], $admin_aziend['masfor']);
                }
                ?>
            </b>
        </p>
    </div>
    <div class="panel panel-default">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="indspe" class="col-sm-4 control-label"><?php echo $script_transl['indspe']; ?></label>
                        <div class="col-sm-8 text-left"><?php echo $fornitore['indspe'] . ' ' . $fornitore['citspe']; ?></div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="datreg" class="col-sm-4 control-label"><?php echo $script_transl['datreg']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="datreg" name="datreg" tabindex=7 value="<?php echo $form['datreg']; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="numfat" class="col-sm-4 control-label"><?php echo $script_transl['numfat']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="numfat" name="numfat" maxlength="20" tabindex=8 placeholder="<?php echo $script_transl['numfat']; ?>" value="<?php echo $form['numfat']; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="datfat" class="col-sm-4 control-label"><?php echo $script_transl['datfat']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="datfat" name="datfat" placeholder="GG/MM/AAAA" tabindex=9 value="<?php echo $form['datfat']; ?>">
                        </div>
                    </div>
                </div>
            </div> <!-- chiude row  -->
            <div class="row">
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="pagame" class="col-sm-4 control-label" ><?php echo $script_transl['pagame']; ?></label>
                        <div>
                            <?php
                            $select_pagame = new selectpagame("pagame");
                            $select_pagame->addSelected($form["pagame"]);
                            $select_pagame->output(false, "col-sm-8 small");
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="codvat" class="col-sm-4 control-label"><?php echo $script_transl['codvat']; ?></label>
                        <div>
                            <?php
                            $sel_vat = new selectaliiva("codvat");
                            $sel_vat->addSelected($form["codvat"]);
                            $sel_vat->output("col-sm-8 small");
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="mas_fixed_assets" class="col-sm-4 control-label"><?php echo $script_transl['mas_fixed_assets']; ?></label>
                        <div>
                            <?php
                            $gForm->selectAccount('mas_fixed_assets', $form['mas_fixed_assets'] . '000000', array(1, 9), '', 10, "col-sm-8 small");
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="seziva" class="col-sm-4 control-label"><?php echo $script_transl['seziva']; ?></label>
                        <div class="col-sm-8">
                            <?php $gForm->selectNumber('seziva', $form['seziva'], 0, 1, 9, 'col-sm-8 small'); ?>
                        </div>
                    </div>
                </div>
            </div> <!-- chiude row  -->
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <p class="col-sm-12 small bg-info">
                        <?php echo $amm_gr; ?>
                    </p>
                </div>
                <div class="col-md-12 col-lg-6">
                    <p class="col-sm-12 small bg-info">
                        <?php echo $amm_sp; ?>
                    </p>
                </div>
            </div> <!-- chiude row  -->
            <div class="row">
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="ss_amm_min" class="col-sm-4 control-label"><?php echo $script_transl['ss_amm_min']; ?></label>
                        <div>
                            <?php
                            $gForm->selAmmortamentoMin('ammortamenti_ministeriali.xml', 'ss_amm_min', $admin_aziend['amm_min'], $form["ss_amm_min"]);
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="valamm" class="col-sm-8 control-label"><?php echo $script_transl['valamm']; ?></label>
                        <div class="col-sm-4">
                            <input type="number" step="0.01" min="0.1" max="100" class="form-control" id="valamm" name="valamm" placeholder="<?php echo $script_transl['valamm']; ?>" value="<?php echo $form['valamm']; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="mas_found_assets" class="col-sm-4 control-label"><?php echo $script_transl['mas_found_assets']; ?></label>
                        <div>
                            <?php
                            $gForm->selectAccount('mas_found_assets', $form['mas_found_assets'] . '000000', array(2, 9), '', 11, "col-sm-8 small");
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="mas_cost_assets" class="col-sm-4 control-label"><?php echo $script_transl['mas_cost_assets']; ?></label>
                        <div>
                            <?php
                            $gForm->selectAccount('mas_cost_assets', $form['mas_cost_assets'] . '000000', array(3, 9), '', 12, "col-sm-8 small");
                            ?>
                        </div>
                    </div>
                </div>
            </div> <!-- chiude row  -->
            <div class="row">
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="no_deduct_cost_rate" class="col-sm-6 control-label"><?php echo $script_transl['no_deduct_cost_rate']; ?></label>
                        <div class="col-sm-6">
                            <input type="number" step="0.1" max="100" class="form-control" id="no_deduct_cost_rate" name="no_deduct_cost_rate" placeholder="<?php echo $script_transl['no_deduct_cost_rate']; ?>" value="<?php echo $form['no_deduct_cost_rate']; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="acc_no_deduct_cost" class="col-sm-6 control-label"><?php echo $script_transl['acc_no_deduct_cost']; ?></label>
                        <div>
                            <?php
                            $gForm->selectAccount('acc_no_deduct_cost', $form['acc_no_deduct_cost'], 3, '',false, "col-sm-6 small");
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="no_deduct_vat_rate" class="col-sm-8 control-label"><?php echo $script_transl['no_deduct_vat_rate']; ?></label>
                        <div class="col-sm-4">
                            <input type="number" step="0.1" max="100" class="form-control" id="valamm" name="no_deduct_vat_rate" placeholder="<?php echo $script_transl['no_deduct_vat_rate']; ?>" value="<?php echo $form['no_deduct_vat_rate']; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="id_no_deduct_vat" class="col-sm-4 control-label"><?php echo $script_transl['id_no_deduct_vat']; ?></label>
                        <div>
                            <?php
                            $sel_vat = new selectaliiva("id_no_deduct_vat");
                            $sel_vat->addSelected($form["id_no_deduct_vat"]);
                            $sel_vat->output("col-sm-8 small", 'D');
                            ?>
                        </div>
                    </div>
                </div>
            </div> <!-- chiude row  -->
            <div class="row">
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="descri" class="col-sm-4 control-label"><?php echo $script_transl['descri']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="descri" name="descri" maxlenght="100" tabindex=14 placeholder="<?php echo $script_transl['descri']; ?>" value="<?php echo $form['descri']; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="unimis" class="col-sm-4 control-label"><?php echo $script_transl['unimis']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="unimis" name="unimis" maxlenght="3" tabindex=15 placeholder="<?php echo $script_transl['unimis']; ?>" value="<?php echo $form['unimis']; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="quantity" class="col-sm-4 control-label"><?php echo $script_transl['quantity']; ?></label>
                        <div class="col-sm-8">
                            <input type="number" step="0.1" min="1" class="form-control" id="quantity" name="quantity" tabindex=16 placeholder="<?php echo $script_transl['quantity']; ?>" value="<?php echo $form['quantity']; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="a_value" class="col-sm-4 control-label"><?php echo $script_transl['a_value']; ?></label>
                        <div class="col-sm-8">
                            <input type="number" step="0.01" min="0.01" class="form-control" id="a_value" name="a_value" tabindex=17 placeholder="<?php echo $script_transl['a_value']; ?>" value="<?php echo $form['a_value']; ?>">
                        </div>
                    </div>
                </div>
            </div> <!-- chiude row  -->
            <div class="row">
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="super_ammort" class="col-sm-8 control-label"><?php echo $script_transl['super_ammort']; ?></label>
                        <div class="col-sm-4">
                            <input type="number" step="0.1" min="0.1" max="500" class="form-control" id="super_ammort" name="super_ammort" placeholder="<?php echo $script_transl['super_ammort']; ?>" value="<?php echo $form['super_ammort']; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="amount" class="col-sm-8 control-label"><?php echo $script_transl['amount']; ?></label>
                        <div class="col-sm-4 bg-success">
                            <span id="amount" class="text-right">
                                <?php echo round($amount, 2); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <p class="col-sm-12 small">
                            <?php echo $gg . $script_transl['info']['gg_to_year_end_1']; ?>
                            <span id="yreg" class="text-right"><?php echo substr($form['datreg'], 6, 4) ?></span>
                            <?php echo $script_transl['info']['gg_to_year_end_2'];
                            ?>
                            <span id="amount_rate">
                                <?php
                                echo gaz_format_number(round($amount * $form['valamm'] * $gg / 36500, 2));
                                ?></span>
                        </p>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                    </div>
                </div>
            </div> <!-- chiude row  -->
            <div class="row">
              <div class=" col-xs-12 text-center FacetFooterTD">
                <input name="ins" class="btn btn-warning" id="preventDuplicate" onClick="chkSubmit();" type="submit" value="<?php echo ucfirst($script_transl[$toDo]); ?>">
              </div>
            </div> <!-- chiude row  -->
        </div><!-- chiude container  -->
    </div><!-- chiude panel  -->
</form>
<?php
  if ($form['id_doc'] >= 1 ) {
		$fae_xsl_file = gaz_dbi_get_row($gTables['company_config'], 'var', 'fae_style');
		$xslDoc = new DOMDocument();
		$xslDoc->load('../../library/include/'.$fae_xsl_file['val'].'.xsl');
		$xslt = new XSLTProcessor();
		$xslt->importStylesheet($xslDoc);
		echo '<center>' . $xslt->transformToXML($xml) . '</center>';
  }
require("../../library/include/footer.php");
?>
