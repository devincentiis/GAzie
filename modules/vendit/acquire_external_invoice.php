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
$tipdoc_conv=array('TD01'=>'FAI','TD02'=>'FAA','TD03'=>'FAQ','TD04'=>'FNC','TD05'=>'FND','TD06'=>'FAP','TD24'=>'FAD','TD25'=>'FND','TD26'=>'FAF');
$toDo = 'upload';
$f_ex=false; // visualizza file
function removeSignature($string, $filename) {
    $string = substr($string, strpos($string, '<?xml '));
    preg_match_all('/<\/.+?>/', $string, $matches, PREG_OFFSET_CAPTURE);
    $lastMatch = end($matches[0]);
	// trovo l'ultimo carattere del tag di chiusura per eliminare la coda
	$f_end = $lastMatch[1]+strlen($lastMatch[0]);
    $string = substr($string, 0, $f_end);
	// elimino le sequenze di caratteri aggiunti dalla firma (ancora da testare approfonditamente)
	$string = preg_replace ('/[\x{0004}]{1}[\x{0082}]{1}[\x{0001}\x{0002}\x{0003}\x{0004}]{1}[\s\S]{1}/i', '', $string);
	$string = preg_replace ('/[\x{0004}]{1}[\x{0081}]{1}[\s\S]{1}/i', '', $string);
	$string = preg_replace ('/[\x{0004}]{1}[A-Za-z]{1}/i', '', $string); // per eliminare tag finale
	return $string;
}

function getLastProtocol($type, $year, $sezione) {
	/* 	questa funzione trova l'ultimo numero di protocollo
	*	controllando sia l'archivio documenti che il registro IVA vendite
	*/
	global $gTables;
    $rs_ultimo_tesdoc = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datfat) = ".$year." AND tipdoc LIKE '" . substr($type, 0, 1) . "__' AND seziva = ".$sezione, "protoc DESC", 0, 1);
    $ultimo_tesdoc = gaz_dbi_fetch_array($rs_ultimo_tesdoc);
    $rs_ultimo_tesmov = gaz_dbi_dyn_query("*", $gTables['tesmov'], "YEAR(datdoc) = ".$year." AND regiva = 2 AND seziva = ".$sezione, "protoc DESC", 0, 1);
    $ultimo_tesmov = gaz_dbi_fetch_array($rs_ultimo_tesmov);
    $lastProtocol = 0;
    $lastDatreg = date("Y-m-d");
    if ($ultimo_tesdoc) {
        $lastProtocol = $ultimo_tesdoc['protoc'];
        $lastDatreg = $ultimo_tesdoc['datreg'];
    }
    if ($ultimo_tesmov) {
        if ($ultimo_tesmov['protoc'] > $lastProtocol) {
            $lastProtocol = $ultimo_tesmov['protoc'];
            $lastDatreg = $ultimo_tesmov['datreg'];
        }
    }
    return array('last_protoc'=>$lastProtocol + 1,'last_datreg'=>$lastDatreg);
}

if (!isset($_POST['fattura_elettronica_original_name'])) { // primo accesso nessun upload
	$form['fattura_elettronica_original_name'] = '';
} else { // accessi successivi
	$form['fattura_elettronica_original_name'] = filter_var($_POST['fattura_elettronica_original_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	if (!isset($_POST['datreg'])){
		$form['datreg'] = date("d/m/Y");
		$form['seziva'] = 1;
		$form['taxstamp'] = 0;
	} else {
		$form['datreg'] = substr($_POST['datreg'],0,10);
		$form['seziva'] = intval($_POST['seziva']);
		$form['taxstamp'] = floatval($_POST['taxstamp']);
	}
	if (isset($_POST['Submit_file'])) { // conferma invio upload file
        if (!empty($_FILES['userfile']['name'])) {
            if (!( $_FILES['userfile']['type'] == "application/pkcs7-mime" || $_FILES['userfile']['type'] == "text/xml")) {
				$msg['err'][] = 'filmim';
			} else {
                if (move_uploaded_file($_FILES['userfile']['tmp_name'], DATA_DIR.'files/' . $admin_aziend['codice'] . '/' . $_FILES['userfile']['name'])) { // nessun errore
					$form['fattura_elettronica_original_name'] = $_FILES['userfile']['name'];
				} else { // no upload
					$msg['err'][] = 'no_upload';
				}
			}
		} else {
			$msg['err'][] = 'no_upload';
		}
	} else if (isset($_POST['Submit_form'])) { // ho  confermato l'inserimento
		$form['pagame'] = intval($_POST['pagame']);
        if ($form['pagame'] <= 0 ) {  // ma non ho selezionato il pagamento
			$msg['err'][] = 'no_pagame';
		}
		// faccio i controlli sui righi
		foreach($_POST as $kr=>$vr){
			if (substr($kr,0,7)=='codvat_' && $vr<=0 && $vr !='000000000') {
				$msg['err'][] = 'no_codvat';
			}
			if (substr($kr,0,7)=='codric_' && $vr<=0 && $vr !='000000000') {
				$msg['err'][] = 'no_codric';
			}
		}

	} else if (isset($_POST['Download'])) { // faccio il download dell'allegato
		$name = filter_var($_POST['Download'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment;  filename="'.$name.'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header("Content-Length: " . filesize(DATA_DIR.'files/tmp/'.$name));
		readfile(DATA_DIR.'files/tmp/'.$name);
		exit;
	}

	$tesdoc = gaz_dbi_get_row($gTables['tesdoc'], 'fattura_elettronica_original_name', $form["fattura_elettronica_original_name"]);
	if ($tesdoc && 	!empty($form['fattura_elettronica_original_name'])) { // c'è anche sul database, è una modifica
		$toDo = 'update';
		$form['datreg'] = gaz_format_date($tesdoc['datreg'], false, false);
		$form['seziva'] = $tesdoc['seziva'];
		$form['taxstamp'] = $tesdoc['taxstamp'];
		$msg['err'][] = 'file_exists';
	} elseif (!empty($form['fattura_elettronica_original_name'])) { // non c'è sul database è un inserimento
		$toDo = 'insert';
		// INIZIO acquisizione e pulizia file xml o p7m
		$file_name = DATA_DIR.'files/' . $admin_aziend['codice'] . '/' . $form['fattura_elettronica_original_name'];
		$p7mContent = @file_get_contents($file_name);
		$invoiceContent = removeSignature($p7mContent,$file_name);
		$doc = new DOMDocument;
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		$doc->loadXML(mb_convert_encoding($invoiceContent, 'UTF-8', mb_list_encodings()));
		$xpath = new DOMXpath($doc);
		$f_ex=true;
	} else {
		$toDo = 'upload';
	}

	// definisco l'array dei righi
	$form['rows'] = array();

	$anagra_with_same_pi = false; // sarà true se è una anagrafica esistente ma non è un cliente sul piano dei conti
	$anagra_with_same_cf = false; // sarà true se è una anagrafica esistente ma non è un cliente sul piano dei conti
	$partner_with_same_pi = false; // sarà true se c'è un cliente con la stessa partita iva sul piano dei conti
	$partner_with_same_cf = false; // sarà true se c'è un cliente col lo stesso codice fiscale sul piano dei conti


	if ($f_ex) { // non ho errori di file, ne faccio altri controlli sul contenuto del file

		// INIZIO CONTROLLI CORRETTEZZA FILE
		$val_err = libxml_get_errors(); // se l'xml è valido restituisce 1
		libxml_clear_errors();
		if (empty($val_err)){
			/* INIZIO CONTROLLO NUMERO DATA, ovvero se nonostante il nome del file sia diverso il suo contenuto è già stato importato e già c'è uno con lo stesso tipo_documento-numero_documento-anno-fornitore
			*/
			$tipdoc=$tipdoc_conv[$xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/TipoDocumento")->item(0)->nodeValue];
			$datdoc=$xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Data")->item(0)->nodeValue;
			$numdoc=$xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Numero")->item(0)->nodeValue;
			if ($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdCodice")->length >= 1) {
                $codiva=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue;
			} else { // NON esiste il nodo <IdCodice> ovvero la partita IVA (è un privato)
                $codiva=0;
            }
            $r_invoice=gaz_dbi_dyn_query("*", $gTables['tesdoc']. " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesdoc'] . ".clfoco = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['clfoco'] . ".id_anagra = " . $gTables['anagra'] . ".id", "tipdoc='".$tipdoc."' AND pariva='".$codiva."' AND datfat='".$datdoc."' AND numfat='".$numdoc."'", "id_tes", 0, 1);
			$exist_invoice=gaz_dbi_fetch_array($r_invoice);
			if ($exist_invoice) { // esiste un file che pur avendo un nome diverso è già stato acquisito ed ha lo stesso numero e data
				$msg['err'][] = 'same_content';
				$f_ex=false; // non è visualizzabile
			}
			if ($doc->getElementsByTagName("FatturaElettronicaHeader")->length < 1) { // non esiste il nodo <FatturaElettronicaHeader>
				$msg['err'][] = 'invalid_fae';
				$f_ex=false; // non è visualizzabile
			} else if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue <> $admin_aziend['pariva'] && @$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0)->nodeValue <> $admin_aziend['codfis'] ) { // ne partita IVA ne codice fiscale coincidono con quella della azienda che sta acquisendo la fattura
				$msg['err'][] = 'not_mine';
				$f_ex=false; // non la visualizzo perché non è una mia fattura
			} else {
				$anagrafica = new Anagrafica();
				// controllo se ho il cliente in archivio
				$form['partner_revenues']=$admin_aziend['impven'];
				$form['partner_vat']=$admin_aziend['preeminent_vat'];
				if ($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdCodice")->length<1){ // non ho la partita IVA devo cercare per  codice fiscale
					$form['codfis'] = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/CodiceFiscale")->item(0)->nodeValue;
					$partner_with_same_cf = $anagrafica->queryPartners('*', "codice BETWEEN " . $admin_aziend['mascli'] . "000000 AND " . $admin_aziend['mascli'] . "999999 AND codfis = '" . $form['codfis']. "'", "codfis DESC", 0, 1);
                    if ($partner_with_same_cf) { // ho già il cliente sul piano dei conti
						$form['clfoco'] = $partner_with_same_cf[0]['codice'];
						if ($partner_with_same_cf[0]['cosric']>100000000) { // ho un costo legato al cliente
							$form['partner_revenues'] = $partner_with_same_cf[0]['cosric']; // ricavo legato al cliente
						}
						$form['pagame'] = $partner_with_same_cf[0]['codpag']; // condizione di pagamento
						if ( $partner_with_same_cf[0]['aliiva'] > 0 ){
							$form['partner_vat'] = $partner_with_same_cf[0]['aliiva'];
						}
                    } else { // se non ho già un cliente sul piano dei conti provo a vedere nelle anagrafiche
                        $rs_anagra_with_same_cf = gaz_dbi_query_anagra(array("*"), $gTables['anagra'], array("codfis" => "='" . $form['codfis'] . "'"), array("codfis" => "DESC"), 0, 1);
                        $anagra_with_same_pi = gaz_dbi_fetch_array($rs_anagra_with_same_cf);
                        if ($anagra_with_same_cf) { // c'è già un'anagrafica con la stessa PI non serve reinserirlo ma dovrò metterlo sul piano dei conti
							$msg['war'][] = 'no_suppl';
                        } else { // non c'è nemmeno nelle anagrafiche allora attingerò i dati da questa fattura
							$msg['war'][] = 'no_anagr';

						}
                    }
				} else { // ho la partita IVA
					$form['pariva'] = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue;
					$partner_with_same_pi = $anagrafica->queryPartners('*', "codice BETWEEN " . $admin_aziend['mascli'] . "000000 AND " . $admin_aziend['mascli'] . "999999 AND pariva = '" . $form['pariva']. "'", "pariva DESC", 0, 1);
                    if ($partner_with_same_pi) { // ho già il cliente sul piano dei conti
						$form['clfoco'] = $partner_with_same_pi[0]['codice'];
						if ($partner_with_same_pi[0]['cosric']>100000000) { // ho un costo legato al cliente
							$form['partner_revenues'] = $partner_with_same_pi[0]['cosric']; // ricavo legato al cliente
						}
						$form['pagame'] = $partner_with_same_pi[0]['codpag']; // condizione di pagamento
						if ( $partner_with_same_pi[0]['aliiva'] > 0 ){
							$form['partner_vat'] = $partner_with_same_pi[0]['aliiva'];
						}
                    } else { // se non ho già un cliente sul piano dei conti provo a vedere nelle anagrafiche
                        $rs_anagra_with_same_pi = gaz_dbi_query_anagra(array("*"), $gTables['anagra'], array("pariva" => "='" . $form['pariva'] . "'"), array("pariva" => "DESC"), 0, 1);
                        $anagra_with_same_pi = gaz_dbi_fetch_array($rs_anagra_with_same_pi);
                        if ($anagra_with_same_pi) { // c'è già un'anagrafica con la stessa PI non serve reinserirlo ma dovrò metterlo sul piano dei conti
							$msg['war'][] = 'no_suppl';
                        } else { // non c'è nemmeno nelle anagrafiche allora attingerò i dati da questa fattura
							$msg['war'][] = 'no_anagr';

						}
                    }
				}
			}
		} else {
			$msg['err'][] = 'invalid_xml';
			$f_ex=false; // non è visualizzabile
		}
		// FINE CONTROLLI SU FILE

	}

	if ($f_ex) { // non ho errori  vincolanti sul file posso proporre la visualizzazione
		/*	Prendo i valori delle ritenute d'acconto che purtroppo sul tracciato ufficiale non viene distinto a livello di linee pertanto devo ricavarmele */
		$tot_ritenute = ($doc->getElementsByTagName("ImportoRitenuta")->length >= 1 ? $doc->getElementsByTagName('ImportoRitenuta')->item(0)->nodeValue : 0 );
		$ali_ritenute = ($doc->getElementsByTagName("AliquotaRitenuta")->length >= 1 ? $doc->getElementsByTagName('AliquotaRitenuta')->item(0)->nodeValue : 0 );
		// mi calcolo le eventuali ritenute relative alle casse previdenziali da annotare sotto quando aggiungerò i righi tipo 4
		$ritenute_su_casse = 0;
		$DatiCassaPrevidenziale = $doc->getElementsByTagName('DatiCassaPrevidenziale');
		foreach ($DatiCassaPrevidenziale as $item) { // attraverso per trovare gli elementi cassa previdenziale
			if ($item->getElementsByTagName("Ritenuta")->length >= 1 && $item->getElementsByTagName('Ritenuta')->item(0)->nodeValue=='SI'){
				// su questo contributo cassa ho la ritenuta
				$ritenute_su_casse += round($item->getElementsByTagName('ImportoContributoCassa')->item(0)->nodeValue*$ali_ritenute/100,2);
			}
		}
		// calcolo il residuo ritenute che sono costretto a mettere sulla prima linea questa è sicuramente una carenza strutturale del tracciato che non fa alcun riferimento alle linee
		$res_ritenute=round($tot_ritenute-$ritenute_su_casse,2);

		/* mi serve per tenere traccia della linea con l'importo più grosso in modo da poterci sommare gli eventuali errori di arrotondamento sul totale imponibile
		 dovuto alla diversità del metodo di calcolo usato in gazie*/
		$max_val_linea=1;
		$tot_imponi=0.00;
		/* Prendo il valore del bollo, se c'è, altrimenti è 0.00 */
		$form['taxstamp'] = ($doc->getElementsByTagName("ImportoBollo")->length >= 1 ? $doc->getElementsByTagName('ImportoBollo')->item(0)->nodeValue : 0 );
		$form['virtual_taxstamp'] = 0;
		if ($doc->getElementsByTagName("BolloVirtuale")->length >= 1){
			$form['virtual_taxstamp'] = 1;
		}
    $df = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Data")->item(0)->nodeValue;
    // trovo l'ultima data di registrazione
    $lr=getLastProtocol('F__',substr($df,0,4),1)['last_datreg'];
    $lrt = strtotime($lr);
    $dft = strtotime($df);
    if ($lrt<=$dft) { // se l'ultima registrazione è precedente alla fattura propongo la data della fattura
      $form['datreg']	= gaz_format_date($df, false, false);
    } else {
      $form['datreg']	= gaz_format_date($lr, false, false);
    }
    // controllo se ho uno split payment
    $yes_split=false;
    if($xpath->query("//FatturaElettronicaBody/DatiBeniServizi/DatiRiepilogo/EsigibilitaIVA")->length >=1){
      $yes_split=$xpath->query("//FatturaElettronicaBody/DatiBeniServizi/DatiRiepilogo/EsigibilitaIVA")->item(0)->nodeValue;
    }
		/*

		INIZIO creazione array dei righi con la stessa nomenclatura usata sulla tabella rigdoc
		a causa della mancanza di rigore del tracciato ufficiale siamo costretti a crearci un castelletto conti e iva
		al fine contabilizzare direttamente qui senza passare per la contabilizzazione di GAzie e tentare di creare dei
		righi documenti la cui somma coincida con il totale imponibile riportato sul tracciato
		*/

		$DettaglioLinee = $doc->getElementsByTagName('DettaglioLinee');
		$nl=0;
		foreach ($DettaglioLinee as $item) {
			$nl++;
			if ($item->getElementsByTagName("CodiceTipo")->length >= 1) {
				$form['rows'][$nl]['codice_fornitore'] = trim($item->getElementsByTagName('CodiceTipo')->item(0)->nodeValue).'_'.trim($item->getElementsByTagName('CodiceValore')->item(0)->nodeValue);
			} else {
				$form['rows'][$nl]['codice_fornitore'] = ($item->getElementsByTagName("CodiceArticolo")->length >= 1 ? $item->getElementsByTagName('CodiceArticolo')->item(0)->nodeValue : '' );
			}
			$form['rows'][$nl]['descri'] = $item->getElementsByTagName('Descrizione')->item(0)->nodeValue;
			if ($item->getElementsByTagName("Quantita")->length >= 1) {
				$form['rows'][$nl]['quanti'] = $item->getElementsByTagName('Quantita')->item(0)->nodeValue;
				$form['rows'][$nl]['tiprig'] = 0;
			} else {
				$form['rows'][$nl]['quanti'] = '';
				$form['rows'][$nl]['tiprig'] = 1; // rigo forfait
			}
			$form['rows'][$nl]['unimis'] =  ($item->getElementsByTagName("UnitaMisura")->length >= 1 ? $item->getElementsByTagName('UnitaMisura')->item(0)->nodeValue :	'');
			$form['rows'][$nl]['prelis'] = $item->getElementsByTagName('PrezzoUnitario')->item(0)->nodeValue;
			// inizio procedura per applicazione sconto su rigo
			$form['rows'][$nl]['sconto'] = 0;
			if ($item->getElementsByTagName("Tipo")->length >= 1) { // ho uno sconto/maggiorazione
				if ($item->getElementsByTagName("Importo")->length >= 1 && $item->getElementsByTagName('Importo')->item(0)->nodeValue >= 0.00001){
					// calcolo la percentuale di sconto partendo dall'importo del rigo e da quello dello sconto, il funzionamento di GAzie prevede la percentuale e non l'importo dello sconto
					$tot_rig= $form['rows'][$nl]['quanti']*$form['rows'][$nl]['prelis'];
					$form['rows'][$nl]['sconto']=$item->getElementsByTagName('Importo')->item(0)->nodeValue*100/$tot_rig;
				} elseif($item->getElementsByTagName("Percentuale")->length >= 1 && $item->getElementsByTagName('Percentuale')->item(0)->nodeValue>=0.00001){
					$form['rows'][$nl]['sconto'] = ($item->getElementsByTagName('Tipo')->item(0)->nodeValue == 'SC' ? $item->getElementsByTagName('Percentuale')->item(0)->nodeValue : $item->getElementsByTagName('Percentuale')->item(0)->nodeValue);
				}
			}
			$form['rows'][$nl]['pervat'] = $item->getElementsByTagName('AliquotaIVA')->item(0)->nodeValue;
			// se ho un residuo di ritenuta d'acconto valorizzo con l'aliquota di cui sopra
			$form['rows'][$nl]['ritenuta'] = 0;
			// calcolo l'importo del rigo
			if ($form['rows'][$nl]['tiprig']==0){
				$form['rows'][$nl]['amount']=CalcolaImportoRigo($form['rows'][$nl]['quanti'],$form['rows'][$nl]['prelis'],array($form['rows'][$nl]['sconto']));
			} else {
				$form['rows'][$nl]['amount']=CalcolaImportoRigo(1,$form['rows'][$nl]['prelis'],array($form['rows'][$nl]['sconto']));
			}
			$tot_imponi += $form['rows'][$nl]['amount'];
			if ($form['rows'][$nl]['amount']>$form['rows'][$max_val_linea]['amount']){ // è una linea con valore più alto delle precedenti
				$max_val_linea=$nl;
			}
			if (round($res_ritenute,2)>=0.01){
				$res_ritenute -= $form['rows'][$nl]['amount']*$ali_ritenute/100;
				if (round($res_ritenute,2) >= 0) { // setto l'aliquota ritenuta ma solo se c'è stata capienza
					$form['rows'][$nl]['ritenuta'] = $ali_ritenute;
				}
			 }
			$post_nl = $nl-1;
			if (empty($_FILES['userfile']['name'])) { // l'upload del file è già avvenuto e sono nei refresh successivi quindi riprendo i valori scelti e postati dall'utente
				$form['codart_'.$post_nl] = preg_replace("/[^A-Za-z0-9_]i/", '',substr($_POST['codart_'.$post_nl],0,15));
				$form['rows'][$nl]['codart']=$form['codart_'.$post_nl];
				$form['codric_'.$post_nl] = intval($_POST['codric_'.$post_nl]);
				$form['codvat_'.$post_nl] = intval($_POST['codvat_'.$post_nl]);
			} else {
				if (isset( $form['rows'][$nl]['codart'])){
					$form['codart_'.$post_nl] = $form['rows'][$nl]['codart'];
				} else {
					$form['rows'][$nl]['codart'] = '';
					$form['codart_'.$post_nl] ='';
				}
				/* al primo accesso dopo l'upload del file propongo:
				   - la prima data di registrazione utile considerando quella di questa fattura e l'ultima registrazione
				   - i costi sulle linee (righe) in base al cliente
				   - le aliquote IVA in base a quanto trovato sul database e sul riepilogo del tracciato
				*/
				$form['codric_'.$post_nl] = $form['partner_revenues'];
				if (preg_match('/TRASP/i',strtoupper($form['rows'][$nl]['descri']))) { // se sulla descrizione ho un trasporto lo propongo come ricavo di vendita
					$form['codric_'.$post_nl] = $admin_aziend['cost_tra'];
				}
				$expect_vat = gaz_dbi_get_row($gTables['aliiva'], 'codice', $form['partner_vat']);
				// analizzo le possibilità
				if ($yes_split=='S'){
					$rs_split_vat = gaz_dbi_dyn_query("*", $gTables['aliiva'], "aliquo = " . $form['rows'][$nl]['pervat']." AND tipiva ='T'", "codice ASC", 0, 1);
					$split_vat = gaz_dbi_fetch_array($rs_split_vat);
					$form['codvat_'.$post_nl] = $split_vat['codice'];
				} elseif ( $expect_vat['aliquo'] == $form['rows'][$nl]['pervat']) { // coincide con le aspettative
					$form['codvat_'.$post_nl] = $expect_vat['codice'];
				} else { // non è quella che mi aspettavo allora provo a trovarne una tra quelle con la stessa aliquota
					$rs_last_codvat = gaz_dbi_dyn_query("*", $gTables['aliiva'], 'aliquo = ' . $form['rows'][$nl]['pervat']." AND tipiva <>'T'", "codice ASC", 0, 1);
					$last_codvat = gaz_dbi_fetch_array($rs_last_codvat);
					if ($last_codvat){
						$form['codvat_'.$post_nl] = $last_codvat['codice'];
					} else {
						$form['codvat_'.$post_nl] = 'non trovata';
					}
				}
			}
		}


		/*
			QUI TRATTERO' gli elementi <DatiCassaPrevidenziale> come righi accodandoli ad essi su rigdoc (tipdoc=4)
		*/
		foreach ($DatiCassaPrevidenziale as $item) { // attraverso per trovare gli elementi cassa previdenziale
			$nl++;
			$form['rows'][$nl]['codice_fornitore'] = $item->getElementsByTagName('TipoCassa')->item(0)->nodeValue;
			$form['rows'][$nl]['tiprig'] = 4;
			// carico anche la descrizione corrispondente dal file xml
            $xml = simplexml_load_file('../../library/include/fae_tipo_cassa.xml');
			foreach ($xml->record as $v) {
				$selected = '';
				if ($v->field[0] == $form['rows'][$nl]['codice_fornitore']) {
					$form['rows'][$nl]['descri']= 'Contributo '.strtolower($v->field[1]);
				}
			}
			$form['rows'][$nl]['unimis'] = '';
			$form['rows'][$nl]['quanti'] = '';
			$form['rows'][$nl]['sconto'] = 0;
			$form['rows'][$nl]['prelis'] = $item->getElementsByTagName('ImponibileCassa')->item(0)->nodeValue;
			$form['rows'][$nl]['provvigione'] = $item->getElementsByTagName('AlCassa')->item(0)->nodeValue; // così come per le vendite uso il campo provvigioni per mettere l'aliquota della cassa previdenziale (evidenziato anche sui commenti del database)
			$form['rows'][$nl]['amount'] = $form['rows'][$nl]['prelis'];
			$tot_imponi += $form['rows'][$nl]['amount'];
			$form['rows'][$nl]['pervat'] = $item->getElementsByTagName('AliquotaIVA')->item(0)->nodeValue;
			$form['rows'][$nl]['ritenuta']='';
			if ($item->getElementsByTagName("Ritenuta")->length >= 1 && $item->getElementsByTagName('Ritenuta')->item(0)->nodeValue=='SI'){
				// su questo contributo cassa ho la ritenuta
				$form['rows'][$nl]['ritenuta']= $ali_ritenute;
			}
			$post_nl = $nl-1;
			if (empty($_FILES['userfile']['name'])) { // l'upload del file è già avvenuto e sono nei refresh successivi quindi riprendo i valori scelti e postati dall'utente
				$form['codart_'.$post_nl] = preg_replace("/[^A-Za-z0-9_]i/", '',substr($_POST['codart_'.$post_nl],0,15));
				$form['codric_'.$post_nl] = intval($_POST['codric_'.$post_nl]);
				$form['codvat_'.$post_nl] = intval($_POST['codvat_'.$post_nl]);
			} else {
				if (isset( $form['rows'][$nl]['codart'])){
					$form['codart_'.$post_nl] = $form['rows'][$nl]['codart'];
				} else {
					$form['rows'][$nl]['codart'] = '';
					$form['codart_'.$post_nl] ='';
				}
				/* al primo accesso dopo l'upload del file propongo:
			   - i costi sulle linee (righe) in base al cliente
			   - le aliquote IVA in base a quanto trovato sul database e sul riepilogo del tracciato
				*/
				$form['codric_'.$post_nl] = $form['partner_revenues'];
				$expect_vat = gaz_dbi_get_row($gTables['aliiva'], 'codice', $form['partner_vat']);
				// analizzo le possibilità
				if ( $expect_vat['aliquo'] == $form['rows'][$nl]['pervat']) { // coincide con le aspettative
					$form['codvat_'.$post_nl] = $expect_vat['codice'];
				} else { // non è quella che mi aspettavo allora provo a trovarne una tra quelle con la stessa aliquota
					$form['codvat_'.$post_nl] = 'non trovata';
				}
			}
		}

		/* Infine aggiungo un eventuale differenza di centesimo di imponibile sul rigo di maggior valore, questo succede perché il tracciato non è rigoroso nei confronti dell'importo totale dell'elemento  */
		$ImponibileImporto = $xpath->query("//FatturaElettronicaBody/DatiBeniServizi/DatiRiepilogo/ImponibileImporto")->item(0)->nodeValue;
		if ($ImponibileImporto>$tot_imponi){ // qualora ci sia una differenza (in genere 1 cent) la aggiunto al rigo di maggior valore
			if ($form['rows'][$max_val_linea]['tiprig']==0){ //rigo normale con quantità variabile
				$form['rows'][$max_val_linea]['prelis']+= ($ImponibileImporto-$tot_imponi)/$form['rows'][$max_val_linea]['quanti'];
			} else {
				$form['rows'][$max_val_linea]['prelis']+= $ImponibileImporto-$tot_imponi;
			}
			$form['rows'][$max_val_linea]['amount'] += $ImponibileImporto-$tot_imponi;
		}
		// ricavo l'allegato, e se presente metterò un bottone per permettere il download
    $yesatt = $doc->getElementsByTagName('NomeAttachment')->item(0);
    if ($yesatt){
      $yesatt=[];
      $allegati = $doc->getElementsByTagName('Allegati');
      foreach ($allegati as $allitem){
        $nomeatt = $allitem->getElementsByTagName('NomeAttachment')->item(0);
        $name_file = $nomeatt->textContent;
        $contentatt = $allitem->getElementsByTagName('Attachment')->item(0);
        $yesatt[]='<div class="text-bold">Download allegato: <a download='.$name_file.'" href="data:application/'.pathinfo($name_file,PATHINFO_EXTENSION).';base64,'.$contentatt->textContent.'">'.$name_file.'</a></div>';
      }
    }

		if (isset($_POST['Submit_form']) && count($msg['err'])==0) { // confermo le scelte sul form, inserisco i dati sul db ma solo se non ho errori
			if (!$anagra_with_same_pi && !$anagra_with_same_cf && !$partner_with_same_pi && !$partner_with_same_cf ) { // non ho nulla: devo inserire tutto (anagrafica e cliente) basandomi sul pagamento e sui conti di costo scelti dall'utente
				$new_partner = array_merge(gaz_dbi_fields('clfoco'), gaz_dbi_fields('anagra'));
				$new_partner['codpag'] = $form['pagame'];
				$new_partner['sexper'] = 'G';
				// setto le colonne in base ai dati di questa fattura elettronica
				$new_partner['pariva'] = @$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue;
				if (@$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/CodiceFiscale")->item(0)){
					$new_partner['codfis'] = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/CodiceFiscale")->item(0)->nodeValue;
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
				if (@$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/Anagrafica/Nome")->item(0)){
					$new_partner['legrap_pf_nome'] = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/Anagrafica/Nome")->item(0)->nodeValue;
					$new_partner['legrap_pf_cognome'] = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/Anagrafica/Cognome")->item(0)->nodeValue;
					$new_partner['descri'] = $new_partner['legrap_pf_cognome']. ' '.$new_partner['legrap_pf_nome'];
					if (strlen($new_partner['descri'])>50){
						$new_partner['ragso1'] = $new_partner['legrap_pf_cognome'];
						$new_partner['ragso2'] = $new_partner['legrap_pf_nome'];
					} else {
						$new_partner['ragso1'] = $new_partner['descri'];
					}
				}
				if (@$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/Anagrafica/Denominazione")->item(0)){
					$new_partner['descri'] = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/Anagrafica/Denominazione")->item(0)->nodeValue;
					if (strlen($new_partner['descri'])>50){
						$new_partner['ragso1'] = substr($new_partner['descri'],0,50);
						$new_partner['ragso2'] = substr($new_partner['descri'],50,100);
					} else {
						$new_partner['ragso1'] = $new_partner['descri'];
					}
				}
				$new_partner['indspe'] = ucwords(strtolower($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Indirizzo")->item(0)->nodeValue));
				if (@$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/NumeroCivico")->item(0)){
					$new_partner['indspe'] .= ', '.$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/NumeroCivico")->item(0)->nodeValue;
				}
				$new_partner['capspe'] = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/CAP")->item(0)->nodeValue;
				$new_partner['citspe'] = strtoupper($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Comune")->item(0)->nodeValue);
				if (@$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Provincia")->item(0)){
					$new_partner['prospe'] = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Provincia")->item(0)->nodeValue;
				}
				$new_partner['country'] = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Nazione")->item(0)->nodeValue;
				$new_partner['counas'] = $new_partner['country'];
				$new_partner['id_currency'] =1;
				$new_partner['id_language'] =1;
				if (@$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Contatti/Telefono")->item(0)){
					$new_partner['telefo'] = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Contatti/Telefono")->item(0)->nodeValue;
				}
				if (@$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Contatti/Fax")->item(0)){
					$new_partner['fax'] = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Contatti/Fax")->item(0)->nodeValue;
				}
				if (@$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Contatti/Email")->item(0)){
					$new_partner['e_mail'] = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Contatti/Email")->item(0)->nodeValue;
				}
				// trovo l'ultimo codice disponibile sul piano dei conti
				$rs_last_partner = gaz_dbi_dyn_query("*", $gTables['clfoco'], 'codice BETWEEN ' . $admin_aziend['mascli'] . '000001 AND ' . $admin_aziend['mascli'] . '999999', "codice DESC", 0, 1);
				$last_partner = gaz_dbi_fetch_array($rs_last_partner);
				if(!$last_partner){
					$new_partner['codice']=$admin_aziend['mascli'].'000001';
				} else {
					$new_partner['codice'] =$last_partner['codice']+1;
				}
				// inserisco il partner
				$anagrafica->insertPartner($new_partner);
				$form['clfoco']=$new_partner['codice'];
			} else if ($anagra_with_same_pi) { // devo inserire il cliente, ho già l'anagrafica con partita iva
				$anagra_with_same_pi['id_anagra']=$anagra_with_same_pi['id'];
                $form['clfoco'] = $anagrafica->anagra_to_clfoco($anagra_with_same_pi, $admin_aziend['mascli'], $form['pagame']);
			} else if ($anagra_with_same_cf) { // devo inserire il cliente, ho già l'anagrafica con codice fiscale
				$anagra_with_same_cf['id_anagra']=$anagra_with_same_pi['id'];
                $form['clfoco'] = $anagrafica->anagra_to_clfoco($anagra_with_same_cf, $admin_aziend['mascli'], $form['pagame']);
			}
			$form['tipdoc'] = $tipdoc_conv[$xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/TipoDocumento")->item(0)->nodeValue];
			$form['numfat']=$xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Numero")->item(0)->nodeValue;
			$form['numdoc']=intval(preg_replace('/[^0-9]/', false, $form['numfat']));
			$form['datfat']=$xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Data")->item(0)->nodeValue;
			$form['fattura_elettronica_original_content'] = mb_convert_encoding($invoiceContent, 'UTF-8', mb_list_encodings());
			$form['datreg']=$form['datfat'];
			$form['datemi']=$form['datfat'];
			$form['protoc']=getLastProtocol($form['tipdoc'],substr($form['datreg'],0,4),$form['seziva'])['last_protoc'];
            $ultimo_id =tesdocInsert($form);
            $fn = DATA_DIR . 'files/' . $admin_aziend["codice"] . '/'.$ultimo_id.'.inv';
            file_put_contents($fn,$form['fattura_elettronica_original_content']);
            //recupero l'id assegnato dall'inserimento
            foreach ($form['rows'] as $i => $v) { // inserisco i righi
				if (abs($form['rows'][$i]['prelis'])<0.01){ // siccome il prezzo è a zero mi trovo di fronte ad un rigo di tipo descrittivo
					$form['rows'][$i]['tiprig']=2;
				}
				if ( $form['tipdoc']=='FNC' && $form['rows'][$i]['prelis']<0.01 ){ // ho un rigo negativo ma è una nota di credito
                    $form['rows'][$i]['prelis']=abs($form['rows'][$i]['prelis']);
                }
                $form['rows'][$i]['id_tes'] = $ultimo_id;
				// i righi postati hanno un indice diverso
				$post_nl=$i-1;
				$form['rows'][$i]['codart'] = preg_replace("/[^A-Za-z0-9_]i/",'',$_POST['codart_'.$post_nl]);
				$form['rows'][$i]['codric'] = intval($_POST['codric_'.$post_nl]);
				$form['rows'][$i]['codvat'] = intval($_POST['codvat_'.$post_nl]);
                rigdocInsert($form['rows'][$i]);
			}
            header("Location: report_docven.php");
			exit;
		} else { // non ho confermato, sono alla prima entrata dopo l'upload del file
			if (!isset($form['pagame'])){
				$form['pagame']=0;
			}
		}
	}
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new venditForm();
?>
<script type="text/javascript">
    $(function () {
        $("#datreg").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
        $("#datreg").change(function () {
            this.form.submit();
        });
    });
</script>
<div align="center" ><b><?php echo $script_transl['title'];?></b></div>
<form method="POST" name="form" enctype="multipart/form-data" id="add-invoice">
    <input type="hidden" name="fattura_elettronica_original_name" value="<?php echo $form['fattura_elettronica_original_name']; ?>">
<?php
	// INIZIO form che permetterà all'utente di interagire per (es.) imputare i vari costi al piano dei conti (contabilità) ed anche le eventuali merci al magazzino
    if (count($msg['err']) > 0) { // ho un errore
        $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
    }
    if (count($msg['war']) > 0) { // ho un alert
        $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
    }
if ($toDo=='insert' || $toDo=='update' ) {
	if ($f_ex){
 ?>
<div class="panel panel-default">
    <div class="panel-heading">
        <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12"><?php echo $script_transl['head_text1']. '<span class="label label-success">'.$form['fattura_elettronica_original_name'] .'</span>'.$script_transl['head_text2']; ?>
            </div>
        </div> <!-- chiude row  -->
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-12 col-md-4 col-lg-4">
                <div class="form-group">
                    <div class="form-group">
                        <label for="seziva" class="col-sm-8 control-label"><?php echo $script_transl['seziva']; ?></label>
                        <?php
                        $gForm->selectNumber('seziva', $form['seziva'], 0, 1, 9, "col-sm-4", '', 'style="max-width: 100px;"');
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4 col-lg-4">
                <div class="form-group">
                    <label for="datreg" class="col-sm-6 control-label"><?php echo $script_transl['datreg']; ?></label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" id="datreg" name="datreg" value="<?php echo $form['datreg']; ?>">
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4 col-lg-4">
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
        </div> <!-- chiude row  -->
    </div>
</div>
<?php
		foreach ($form['rows'] as $k => $v) {
			$k--;
            $codric_dropdown = $gForm->selectAccount('codric_'.$k, $form['codric_'.$k], array('sub',2,4), '', false, "col-sm-8 small",'style="max-width: 350px;"', false, true);
			$codvat_dropdown = $gForm->selectFromDB('aliiva', 'codvat_'.$k, 'codice', $form['codvat_'.$k], 'aliquo', true, '-', 'descri', '', 'col-sm-8 small', null, 'style="max-width: 350px;"', false, true);
			$codart_dropdown = $gForm->concileArtico('codart_'.$k,'codice',$form['codart_'.$k]);
			//forzo i valori diversi dalla descrizione a vuoti se è descrittivo
			if (abs($v['prelis'])<0.01){ // siccome il prezzo è a zero mi trovo di fronte ad un rigo di tipo descrittivo
				$v['codice_fornitore']='';
				$v['unimis']='';
				$v['quanti']='';
				$v['unimis']='';
				$v['prelis']='';
				$v['sconto']='';
				$v['amount']='';
				$v['ritenuta']='';
				$v['pervat']='';
				$codric_dropdown ='<input type="hidden" name="codric_'.$k.'" value="000000000" />';
				$codvat_dropdown ='<input type="hidden" name="codvat_'.$k.'" value="000000000" />';
				$codart_dropdown ='<input type="hidden" name="codart_'.$k.'" />';
			} else {
				$v['prelis']=gaz_format_number($v['prelis']);
				$v['amount']=gaz_format_number($v['amount']);
				$v['ritenuta']=floatval($v['ritenuta']);
				$v['pervat']=floatval($v['pervat']);
			}
			// creo l'array da passare alla funzione per la creazione della tabella responsive
            $resprow[$k] = array(
                array('head' => $script_transl["nrow"], 'class' => '',
                    'value' => $k+1),
                array('head' => $script_transl["codart"], 'class' => '',
                    'value' => $codart_dropdown),
                array('head' => $script_transl["descri"], 'class' => 'col-sm-12 col-md-3 col-lg-3',
                    'value' => $v['descri']),
                array('head' => $script_transl["unimis"], 'class' => '',
                    'value' => $v['unimis']),
                array('head' => $script_transl["quanti"], 'class' => 'text-right numeric',
                    'value' => $v['quanti']),
                array('head' => $script_transl["prezzo"], 'class' => 'text-right numeric',
                    'value' => $v['prelis']),
                array('head' => $script_transl["sconto"], 'class' => 'text-right numeric',
                    'value' => $v['sconto']),
                array('head' => $script_transl["amount"], 'class' => 'text-right numeric',
					'value' => $v['amount'], 'type' => ''),
                array('head' => $script_transl["tax"], 'class' => 'text-center numeric',
					'value' => $codvat_dropdown, 'type' => ''),
                array('head' => 'Ritenuta', 'class' => 'text-center numeric',
					'value' => $v['ritenuta'], 'type' => ''),
                array('head' => '%', 'class' => 'text-center numeric',
					'value' => $v['pervat'], 'type' => ''),
                array('head' => $script_transl["conto"], 'class' => 'text-center numeric',
					'value' => $codric_dropdown, 'type' => '')
            );
		}
		if ($form['taxstamp']>=0.01) { // ho un bollo lo accodo ai righi della tabella
			$resprow[] = array(
                array('head' => $script_transl["nrow"], 'class' => '',
                    'value' => ''),
                array('head' => $script_transl["codart"], 'class' => '',
                    'value' => ''),
                array('head' => $script_transl["descri"], 'class' => 'col-sm-12 col-md-3 col-lg-3',
                    'value' => 'Bolli'),
                array('head' => $script_transl["unimis"], 'class' => '',
                    'value' => ''),
                array('head' => $script_transl["quanti"], 'class' => 'text-right numeric',
                    'value' => ''),
                array('head' => $script_transl["prezzo"], 'class' => 'text-right numeric',
                    'value' => ''),
                array('head' => $script_transl["sconto"], 'class' => 'text-right numeric',
                    'value' => ''),
                array('head' => $script_transl["amount"], 'class' => 'text-right numeric',
					'value' => gaz_format_number($form['taxstamp']), 'type' => ''),
                array('head' => $script_transl["tax"], 'class' => 'text-center numeric',
					'value' => '', 'type' => ''),
                array('head' => 'Ritenuta', 'class' => 'text-center numeric',
					'value' => '', 'type' => ''),
                array('head' => '%', 'class' => 'text-center numeric',
					'value' => '', 'type' => ''),
                array('head' => $script_transl["conto"], 'class' => 'text-center numeric',
					'value' => '', 'type' => '')
            );
		}
		$gForm->gazResponsiveTable($resprow, 'gaz-responsive-table');
?>
    <div class="col-sm-6">
<?php
if ($yesatt){
  foreach ($yesatt as $yav){
    echo $yav;
  }
}
?>
	   </div>
	   <div class="col-sm-6 text-center">
		<input name="taxstamp" type="hidden" value="<?php echo $form['taxstamp']; ?>" />
		<input name="Submit_form" type="submit" class="btn btn-warning" value="<?php echo $script_transl['submit']; ?>" />
	   </div>
</form>
<br />
<?php
	}
	if ($f_ex) {	// visualizzo la fattura elettronica in calce

        $fae_xsl_file = gaz_dbi_get_row($gTables['company_config'], 'var', 'fae_style');
        $xslDoc = new DOMDocument();
        $xslDoc->load("../../library/include/".$fae_xsl_file['val'].".xsl");
        $xslt = new XSLTProcessor();
        $xslt->importStylesheet($xslDoc);
        $iframe_src = str_replace('"', '&quot;', $xslt->transformToXML($doc));
?>
        <iframe style="border: none" width="100%" height="400px" sandbox="allow-same-origin"
                srcdoc="<?=$iframe_src?>"
                onload="this.style.height = this.contentDocument.firstChild.scrollHeight + 'px';
                        this.contentDocument.body.style.textAlign = 'center';">
        </iframe>
<?php
    }
} else { // all'inizio chiedo l'upload di un file xml o p7m
?>
<div class="panel panel-default gaz-table-form">
    <div class="container-fluid">
       <div class="row">
           <div class="col-md-12">
               <div class="form-group">
                   <label for="image" class="col-sm-4 control-label">Seleziona il file xml o p7m</label>
                   <div class="col-sm-8">File: <input type="file" accept=".xml,.p7m" name="userfile" />
				   </div>
               </div>
           </div>
       </div><!-- chiude row  -->
	   <div class="col-sm-12 text-right"><input name="Submit_file" type="submit" class="btn btn-warning" value="<?php echo $script_transl['btn_acquire']; ?>" />
	   </div>
	</div> <!-- chiude container -->
</div><!-- chiude panel -->
<?php
}
require("../../library/include/footer.php");
?>
