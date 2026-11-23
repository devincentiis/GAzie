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

// ---------------------------------------------------------------- acquisizione massiva: coda delle fatture da acquisire
// Come per le fatture di acquisto (acquis/acquire_invoice.php) la pagina iniziale carica più fatture insieme (anche pacchetti ZIP)
// in una coda: gaz_NNNfiles con item_ref = 'faeven' (diverso da 'faesync' degli acquisti) e file in data/files/<azienda>/doc/<id_doc>.<estensione>.
// Ogni fattura della coda si apre in un riquadro su questa stessa pagina (POST aei_from_queue) per scegliere pagamento, conti, IVA e articoli;
// dopo la conferma la pagina va su report_docven.php, segnale che la fattura è registrata, e in modalità coda si passa da soli alla successiva.
$aeiSelf = basename($_SERVER['SCRIPT_NAME']);
$aeiRef = 'faeven';
$aeiDocDir = DATA_DIR . 'files/' . $admin_aziend['codice'] . '/doc/';
$aeiTmpDir = DATA_DIR . 'files/tmp/';
$aeiMaxZipEntries = 2000;
$aeiInFrame = isset($_POST['aei_in_frame']);
$aeiQueueId = isset($_POST['aei_queue_id']) ? intval($_POST['aei_queue_id']) : 0;
// primo caricamento di una fattura (upload singolo o dalla coda): si propongono conti e IVA invece di riprendere le scelte postate
$aeiFirst = !empty($_FILES['userfile']['name']) || isset($_POST['aei_from_queue']);

function aeiH($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

// "INSERISCI COME NUOVO" su un rigo: crea l'articolo di magazzino dai dati del rigo e ne restituisce il codice.
// Codice: il CodiceValore della fattura (il codice del programma che l'ha emessa) oppure, se manca, uno ricavato dalla descrizione;
// se esiste già un articolo con quel codice si usa quello, così righi e fatture con lo stesso codice non creano doppioni.
function aeiNewArticle($row) {
    global $gTables, $admin_aziend;
    $descri = trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $row['descri'], ENT_QUOTES | ENT_XML1, 'UTF-8')));
    $code = preg_replace('/[^A-Za-z0-9_.-]/', '_', trim((string) (isset($row['codice_valore']) ? $row['codice_valore'] : '')));
    $code = trim(substr($code, 0, 32), '_');
    if ($code === '') $code = 'IMP_' . strtoupper(dechex(crc32(mb_strtolower($descri))));
    if (gaz_dbi_get_row($gTables['artico'], 'codice', $code)) return $code;
    $um = substr(trim((string) $row['unimis']), 0, 3);
    gaz_dbi_table_insert('artico', array('codice' => $code, 'iso_country' => $admin_aziend['country'], 'descri' => mb_substr($descri, 0, 255),
        'catmer' => 1, 'unimis' => $um, 'web_mu' => $um, 'uniacq' => $um, 'aliiva' => intval($row['codvat']),
        'preve1' => floatval($row['prelis']), 'good_or_service' => 0));
    return $code;
}

// nome del file come lo usa questa pagina (che lo copia in data/files/<azienda>/<nome>): senza percorso né caratteri strani
function aeiCleanName($name) {
    $name = basename(str_replace('\\', '/', (string) $name));
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    return substr($name, 0, 200);
}

function aeiExt($name) {
    $e = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return in_array($e, array('xml', 'p7m')) ? $e : '';
}

// file di servizio dello SdI che non sono fatture: metadati (_MT_), ricevute e notifiche
function aeiIsSdiService($name) {
    return (bool) preg_match('/_(MT|RC|NS|MC|NE|DT|AT|EC|SE)_\d{3}\.xml$/i', $name);
}

// contenuto XML di una fattura .xml o .p7m (anche codificata in base64); null se non si riesce
function aeiXmlContent($raw) {
    global $aeiTmpDir;
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string) $raw);
    if (preg_match('/^\s*</', $raw)) return $raw;
    $t = preg_replace('/\s+/', '', $raw);
    if ($t !== '' && preg_match('/^[A-Za-z0-9\/+]+={0,2}$/', $t)) {
        $d = base64_decode($t, true);
        if ($d !== false) $raw = $d;
        if (preg_match('/^\s*</', $raw)) return $raw;
    }
    // busta di firma PKCS#7 (CAdES): si estrae il contenuto con openssl, senza verificare la firma
    $xml = null;
    if (function_exists('openssl_pkcs7_verify') && is_dir($aeiTmpDir)) {
        $in = tempnam($aeiTmpDir, 'aein');
        $cert = tempnam($aeiTmpDir, 'aecrt');
        $out = tempnam($aeiTmpDir, 'aeout');
        file_put_contents($in, "MIME-Version: 1.0\nContent-Disposition: attachment; filename=\"smime.p7m\"\n"
            . "Content-Type: application/x-pkcs7-mime; smime-type=signed-data; name=\"smime.p7m\"\nContent-Transfer-Encoding: base64\n\n"
            . chunk_split(base64_encode($raw)));
        $flags = PKCS7_BINARY | PKCS7_NOVERIFY | PKCS7_NOSIGS;
        if (@openssl_pkcs7_verify($in, $flags, $cert, array(), $cert, $out) !== -1) {
            $c = @file_get_contents($out);
            if ($c && strpos($c, '<') !== false) $xml = $c;
        }
        @unlink($in); @unlink($cert); @unlink($out);
    }
    if ($xml === null && preg_match('#(<\?xml.*</[^>]*FatturaElettronica(Semplificata)?>)#s', $raw, $m)) {
        // ultima possibilità: il testo tra l'intestazione XML e il tag di chiusura, ripulito dai byte della busta
        $xml = preg_replace('/[\x{0004}][\x{0082}][\s\S]{2}|[\x{0004}][\x{0081}][\s\S]|[\x{0004}][\s\S]/', '', $m[1]);
    }
    return $xml;
}

// dati principali per l'elenco: cliente (cessionario), partita IVA, numero, data, totale, tipo, e se l'emittente è questa azienda
function aeiInfo($raw) {
    global $admin_aziend;
    $xml = aeiXmlContent($raw);
    if ($xml === null) return array('error' => 'contenuto non leggibile');
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $ok = $dom->loadXML($xml);
    if (!$ok) $ok = $dom->loadXML(preg_replace('/[^\P{C}\n\r\t]/u', '', $xml));
    libxml_clear_errors();
    if (!$ok) return array('error' => 'XML non valido');
    $xp = new DOMXPath($dom);
    $v = function ($q) use ($xp) { $n = $xp->query($q); return ($n && $n->length) ? trim($n->item(0)->nodeValue) : ''; };
    $root = $dom->documentElement ? $dom->documentElement->localName : '';
    if (stripos($root, 'FatturaElettronica') === false) return array('error' => 'non è una fattura elettronica');
    // emittente: deve essere l'azienda che sta acquisendo (stesso controllo della pagina di acquisizione)
    $cedPiva = $v("//*[local-name()='CedentePrestatore']//*[local-name()='IdFiscaleIVA']/*[local-name()='IdCodice']");
    $cedCf = $v("//*[local-name()='CedentePrestatore']//*[local-name()='CodiceFiscale']");
    $mine = ($cedPiva !== '' && $cedPiva == $admin_aziend['pariva']) || ($cedCf !== '' && $cedCf == $admin_aziend['codfis']);
    $ces = "//*[local-name()='FatturaElettronicaHeader']/*[local-name()='CessionarioCommittente']/*[local-name()='DatiAnagrafici']";
    $name = $v($ces . "/*[local-name()='Anagrafica']/*[local-name()='Denominazione']");
    if ($name === '') $name = trim($v($ces . "/*[local-name()='Anagrafica']/*[local-name()='Nome']") . ' ' . $v($ces . "/*[local-name()='Anagrafica']/*[local-name()='Cognome']"));
    $piva = $v($ces . "/*[local-name()='IdFiscaleIVA']/*[local-name()='IdCodice']");
    $paese = $v($ces . "/*[local-name()='IdFiscaleIVA']/*[local-name()='IdPaese']");
    $cf = $v($ces . "/*[local-name()='CodiceFiscale']");
    $gen = "//*[local-name()='FatturaElettronicaBody'][1]/*[local-name()='DatiGenerali']/*[local-name()='DatiGeneraliDocumento']";
    $tot = $v($gen . "/*[local-name()='ImportoTotaleDocumento']");
    if ($tot === '') {
        // senza totale dichiarato: somma di imponibile e imposta del riepilogo IVA
        $s = 0;
        foreach ($xp->query("//*[local-name()='FatturaElettronicaBody'][1]//*[local-name()='DatiRiepilogo']") as $r) {
            foreach (array('ImponibileImporto', 'Imposta') as $f) {
                $n = $xp->query("*[local-name()='" . $f . "']", $r);
                if ($n->length) $s += (float) $n->item(0)->nodeValue;
            }
        }
        $tot = $s ? (string) $s : '';
    }
    return array('cliente' => $name, 'piva' => $piva, 'paese' => $paese, 'cf' => $cf, 'mine' => $mine,
        'tipo' => $v($gen . "/*[local-name()='TipoDocumento']"), 'numero' => $v($gen . "/*[local-name()='Numero']"),
        'data' => $v($gen . "/*[local-name()='Data']"), 'totale' => $tot);
}

// mette una fattura nella coda; restituisce array(esito, messaggio)
function aeiQueue($name, $content) {
    global $gTables, $aeiDocDir, $aeiRef;
    $name = aeiCleanName($name);
    $ext = aeiExt($name);
    if ($ext === '') return array('skip', 'non è un file XML o P7M');
    if (aeiIsSdiService($name)) return array('skip', 'file di servizio dello SdI (metadati o notifica), non una fattura');
    if (strlen($content) == 0) return array('err', 'file vuoto');
    $info = aeiInfo($content);
    if (isset($info['error'])) return array('err', $info['error']);
    if (!$info['mine']) return array('err', 'l\'emittente non è questa azienda: non è una fattura di vendita propria');
    $e = gaz_dbi_real_escape_string($name);
    $rs = gaz_dbi_query("SELECT id_doc, status FROM " . $gTables['files'] . " WHERE item_ref = '" . $aeiRef . "' AND title = '" . $e . "' LIMIT 1");
    if ($r = gaz_dbi_fetch_array($rs)) return array('skip', $r['status'] ? 'già acquisita in precedenza' : 'già in coda');
    $rs = gaz_dbi_query("SELECT id_tes FROM " . $gTables['tesdoc'] . " WHERE BINARY fattura_elettronica_original_name = '" . $e . "' LIMIT 1");
    if (gaz_dbi_fetch_array($rs)) return array('skip', 'già acquisita (c\'è un documento con questo file)');
    if (!is_dir($aeiDocDir) && !@mkdir($aeiDocDir, 0775, true)) return array('err', 'impossibile creare la cartella data/files/.../doc');
    $id = gaz_dbi_table_insert('files', array('table_name_ref' => date('Y-m-d H:i:s'), 'item_ref' => $aeiRef, 'extension' => $ext, 'title' => $name, 'status' => 0));
    if (!$id || file_put_contents($aeiDocDir . intval($id) . '.' . $ext, $content) === false) {
        if ($id) gaz_dbi_del_row($gTables['files'], 'id_doc', intval($id));
        return array('err', 'impossibile salvare il file (controlla i permessi di data/files)');
    }
    return array('ok', trim($info['cliente'] . ' - n. ' . $info['numero'] . ' del ' . ($info['data'] ? gaz_format_date($info['data']) : '?')));
}

// fatture contenute in un pacchetto ZIP (anche ZIP dentro lo ZIP, un livello); si leggono in memoria, senza estrarre su disco
function aeiFromZip($path, $zipName, &$aeiReport, $depth = 0) {
    global $aeiTmpDir, $aeiMaxZipEntries;
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) { $aeiReport[] = array('err', $zipName, 'pacchetto ZIP non leggibile'); return; }
    $n = min($zip->numFiles, $aeiMaxZipEntries);
    for ($i = 0; $i < $n; $i++) {
        $entry = $zip->getNameIndex($i);
        if ($entry === false || substr($entry, -1) == '/') continue;
        $base = basename(str_replace('\\', '/', $entry));
        if ($base === '' || $base[0] == '.' || strpos($entry, '__MACOSX/') === 0) continue;
        $label = $zipName . ' &rsaquo; ' . aeiH($base);
        if (strtolower(pathinfo($base, PATHINFO_EXTENSION)) == 'zip' && $depth == 0) {
            $tmp = tempnam($aeiTmpDir, 'aezip');
            file_put_contents($tmp, $zip->getFromIndex($i));
            aeiFromZip($tmp, $label, $aeiReport, 1);
            @unlink($tmp);
            continue;
        }
        if (aeiExt($base) === '') continue; // pdf, html e altri allegati del pacchetto: si ignorano
        $res = aeiQueue($base, (string) $zip->getFromIndex($i));
        $aeiReport[] = array($res[0], $label, $res[1]);
    }
    if ($zip->numFiles > $aeiMaxZipEntries) $aeiReport[] = array('err', $zipName, 'lette solo le prime ' . $aeiMaxZipEntries . ' voci');
    $zip->close();
}

// scelta dell'articolo su un rigo: la tendina contiene solo le scelte fisse e l'articolo già scelto;
// gli altri articoli si trovano con il campo di ricerca accanto (con decine di migliaia di articoli una tendina completa
// per ogni rigo rendeva la pagina lentissima)
function aeiArticoSelect($name, $val) {
    global $gTables;
    $art = ($val !== '' && $val !== 'Insert_New') ? gaz_dbi_get_row($gTables['artico'], 'codice', $val) : false;
    $h = '<select id="' . $name . '" name="' . $name . '" class="small">';
    $h .= '<option value="" style="background-color:#5bc0de;">NON IN MAGAZZINO</option>';
    $h .= '<option value="Insert_New" style="background-color:#f0ad4e;"' . ($val === 'Insert_New' ? ' selected' : '') . '>INSERISCI COME NUOVO</option>';
    if ($art) $h .= '<option value="' . aeiH($art['codice']) . '" selected style="background-color:#5cb85c;">' . aeiH($art['codice'] . '-' . mb_substr($art['descri'], 0, 30)) . '</option>';
    $h .= '</select><br><input type="text" class="aei-cerca" data-target="' . $name . '" placeholder="Cerca articolo..." style="width:100%;max-width:220px;margin-top:2px">';
    return $h;
}
// ricerca articoli per il campo "Cerca articolo": ogni parola deve comparire nel codice o nella descrizione (o essere il barcode)
if (isset($_GET['aei_search'])) {
    $out = array();
    $words = preg_split('/\s+/', trim(mb_substr((string) $_GET['aei_search'], 0, 40)), -1, PREG_SPLIT_NO_EMPTY);
    if ($words && mb_strlen(implode('', $words)) >= 2) {
        $w = array();
        foreach ($words as $p) {
            $e = gaz_dbi_real_escape_string(addcslashes($p, '%_\\'));
            $w[] = "(codice LIKE '%" . $e . "%' OR descri LIKE '%" . $e . "%' OR barcode = '" . $e . "')";
        }
        $rs = gaz_dbi_query("SELECT codice, descri FROM " . $gTables['artico'] . " WHERE " . implode(' AND ', $w) . " ORDER BY codice LIMIT 50");
        while ($r = gaz_dbi_fetch_array($rs)) $out[] = array('value' => $r['codice'], 'label' => $r['codice'] . ' - ' . $r['descri']);
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out);
    exit;
}

$aeiReport = array();
if (isset($_POST['aei_upload'])) {
    $f = isset($_FILES['fae']) ? $_FILES['fae'] : null;
    $count = $f && is_array($f['name']) ? count($f['name']) : 0;
    if (!$count || ($count == 1 && $f['error'][0] == UPLOAD_ERR_NO_FILE)) {
        $aeiReport[] = array('err', 'Caricamento', 'nessun file scelto');
    }
    for ($i = 0; $i < $count; $i++) {
        $orig = (string) $f['name'][$i];
        if ($f['error'][$i] == UPLOAD_ERR_NO_FILE) continue;
        if ($f['error'][$i] != UPLOAD_ERR_OK) {
            $aeiReport[] = array('err', aeiH($orig), $f['error'][$i] == UPLOAD_ERR_INI_SIZE ? 'il file supera upload_max_filesize del server' : 'caricamento non riuscito (errore ' . intval($f['error'][$i]) . ')');
            continue;
        }
        if (strtolower(pathinfo($orig, PATHINFO_EXTENSION)) == 'zip') {
            aeiFromZip($f['tmp_name'][$i], aeiH($orig), $aeiReport);
        } else {
            $res = aeiQueue($orig, (string) file_get_contents($f['tmp_name'][$i]));
            $aeiReport[] = array($res[0], aeiH($orig), $res[1]);
        }
    }
    $_SESSION['aei_report'] = $aeiReport;
    header('Location: ' . $aeiSelf);
    exit;
}
// toglie dalla coda le fatture scelte (solo quelle ancora da acquisire)
if (isset($_POST['aei_remove']) && isset($_POST['aei_sel']) && is_array($_POST['aei_sel'])) {
    $n = 0;
    foreach ($_POST['aei_sel'] as $id) {
        $row = gaz_dbi_get_row($gTables['files'], 'id_doc', intval($id));
        if ($row && $row['item_ref'] == $aeiRef && intval($row['status']) == 0) {
            @unlink($aeiDocDir . intval($row['id_doc']) . '.' . aeiExt('x.' . $row['extension']));
            gaz_dbi_del_row($gTables['files'], 'id_doc', intval($row['id_doc']));
            $n++;
        }
    }
    $_SESSION['aei_report'] = array(array('info', 'Coda', $n . ' fatture tolte dalla coda'));
    header('Location: ' . $aeiSelf);
    exit;
}
if (isset($_SESSION['aei_report'])) {
    $aeiReport = $_SESSION['aei_report'];
    unset($_SESSION['aei_report']);
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
	if (isset($_POST['aei_from_queue'])) { // fattura scelta dalla coda (riquadro della pagina iniziale)
		$aeiQueueId = intval($_POST['aei_from_queue']);
		$aeiRow = gaz_dbi_get_row($gTables['files'], 'id_doc', $aeiQueueId);
		$aeiSrc = $aeiRow ? $aeiDocDir . $aeiQueueId . '.' . aeiExt('x.' . $aeiRow['extension']) : '';
		if ($aeiRow && $aeiRow['item_ref'] == $aeiRef && is_file($aeiSrc) && copy($aeiSrc, DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . aeiCleanName($aeiRow['title']))) {
			$form['fattura_elettronica_original_name'] = aeiCleanName($aeiRow['title']);
		} else {
			$msg['err'][] = 'no_upload';
		}
	} else if (isset($_POST['Submit_file'])) { // conferma invio upload file
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
				$country=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdPaese")->item(0)->nodeValue;
                $codiva=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue;
			} else { // NON esiste il nodo <IdCodice> ovvero la partita IVA (è un privato)
                $codiva=0;
            }
            $r_invoice=gaz_dbi_dyn_query("*", $gTables['tesdoc']. " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesdoc'] . ".clfoco = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['clfoco'] . ".id_anagra = " . $gTables['anagra'] . ".id", "tipdoc='".$tipdoc."' AND pariva='".$codiva."' AND country='".$country."' AND datfat='".$datdoc."' AND numfat='".$numdoc."'", "id_tes", 0, 1);
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
					$partner_with_same_pi = $anagrafica->queryPartners('*', "codice BETWEEN " . $admin_aziend['mascli'] . "000000 AND " . $admin_aziend['mascli'] . "999999 AND pariva='" . $form['pariva']. "' AND country='" . $country. "'", "pariva DESC", 0, 1);
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
                        $rs_anagra_with_same_pi = gaz_dbi_query_anagra(array("*"), $gTables['anagra'], array("pariva" => "='" . $form['pariva'] . "'", "country" => "='" . $country . "'"), array("pariva" => "DESC"), 0, 1);
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
			// codice dell'articolo per "INSERISCI COME NUOVO" (solo il valore, senza il tipo di codice)
			$form['rows'][$nl]['codice_valore'] = ($item->getElementsByTagName('CodiceValore')->length >= 1 ? trim($item->getElementsByTagName('CodiceValore')->item(0)->nodeValue) : '');
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
			if (!$aeiFirst) { // l'upload del file (o l'apertura dalla coda) è già avvenuto e sono nei refresh successivi quindi riprendo i valori scelti e postati dall'utente
				$form['codart_'.$post_nl] = preg_replace("/[^A-Za-z0-9_]i/", '',substr($_POST['codart_'.$post_nl],0,32));
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
			if (!$aeiFirst) { // l'upload del file (o l'apertura dalla coda) è già avvenuto e sono nei refresh successivi quindi riprendo i valori scelti e postati dall'utente
				$form['codart_'.$post_nl] = preg_replace("/[^A-Za-z0-9_]i/", '',substr($_POST['codart_'.$post_nl],0,32));
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
				if ($form['rows'][$i]['codart'] == 'Insert_New') {
					$form['rows'][$i]['codart'] = aeiNewArticle($form['rows'][$i]);
				}
                rigdocInsert($form['rows'][$i]);
			}
            if ($aeiQueueId > 0) { // acquisita dalla coda: non va più proposta
                gaz_dbi_table_update('files', array('id_doc', $aeiQueueId), array('status' => 1));
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
        $(".aei-cerca").each(function () {
            var inp = this, sel = document.getElementById($(this).data('target'));
            $(inp).autocomplete({
                minLength: 2,
                delay: 300,
                source: function (req, resp) {
                    $.getJSON('<?php echo $aeiSelf; ?>', {aei_search: req.term}, resp).fail(function () { resp([]); });
                },
                open: function () { $('.ui-autocomplete').css('z-index', 1000); },
                select: function (e, ui) {
                    var opt = Array.prototype.find.call(sel.options, function (o) { return o.value === ui.item.value; });
                    if (!opt) {
                        opt = new Option(ui.item.label.substr(0, 45), ui.item.value);
                        opt.style.backgroundColor = '#5cb85c';
                        sel.add(opt);
                    }
                    sel.value = ui.item.value;
                    inp.value = '';
                    return false;
                }
            });
        });
    });
</script>
<div align="center" ><b><?php echo $script_transl['title'];?></b></div>
<form method="POST" name="form" enctype="multipart/form-data" id="add-invoice">
    <input type="hidden" name="fattura_elettronica_original_name" value="<?php echo $form['fattura_elettronica_original_name']; ?>">
<?php if ($aeiQueueId > 0) echo '    <input type="hidden" name="aei_queue_id" value="' . $aeiQueueId . '">' . "\n"; ?>
<?php if ($aeiInFrame) echo '    <input type="hidden" name="aei_in_frame" value="1">' . "\n"; ?>
<?php
	// INIZIO form che permetterà all'utente di interagire per (es.) imputare i vari costi al piano dei conti (contabilità) ed anche le eventuali merci al magazzino
    if (count($msg['err']) > 0) { // ho un errore
        $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
    }
    if (count($msg['war']) > 0) { // ho un alert
        $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
    }
    if ($aeiInFrame && !$f_ex && $toDo != 'upload') {
        echo '<div class="alert alert-warning text-center">Questa fattura non si può acquisire. Premi <b>Salta</b> per passare alla successiva oppure chiudi e togli il file dalla coda.</div>';
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
			$codart_dropdown = aeiArticoSelect('codart_'.$k, $form['codart_'.$k]);
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
} elseif ($aeiInFrame) { // nel riquadro della coda la pagina iniziale non serve: si mostrano solo gli errori qui sopra
?>
<div class="alert alert-warning text-center">Questa fattura non si può acquisire. Premi <b>Salta</b> per passare alla successiva oppure chiudi e togli il file dalla coda.</div>
</form>
<?php
} else { // pagina iniziale: caricamento di più fatture (anche ZIP) e coda delle fatture da acquisire
  echo '</form>'; // il form della pagina di acquisizione si chiude qui: la coda ha i propri form
  $queue = array();
  $rs = gaz_dbi_query("SELECT * FROM " . $gTables['files'] . " WHERE item_ref = '" . $aeiRef . "' AND status = 0 ORDER BY id_doc");
  while ($r = gaz_dbi_fetch_array($rs)) $queue[] = $r;
  // clienti per partita IVA (o codice fiscale), per dire subito se il cliente è già in anagrafica
  $mascli = intval($admin_aziend['mascli']);
  $customers = array();
  $rs = gaz_dbi_query("SELECT f.codice, n.pariva, n.codfis, n.ragso1 FROM " . $gTables['clfoco'] . " f JOIN " . $gTables['anagra'] . " n ON n.id = f.id_anagra
      WHERE f.codice BETWEEN " . $mascli . "000001 AND " . $mascli . "999999");
  while ($r = gaz_dbi_fetch_array($rs)) {
      if (trim($r['pariva']) !== '') $customers['p' . ltrim(trim($r['pariva']), '0')] = $r;
      if (trim($r['codfis']) !== '') $customers['c' . strtoupper(trim($r['codfis']))] = $r;
  }
  $rows = array();
  $acquired = 0;
  foreach ($queue as $q) {
      $ext = aeiExt('x.' . $q['extension']);
      $path = $aeiDocDir . intval($q['id_doc']) . '.' . $ext;
      $info = is_file($path) ? aeiInfo(file_get_contents($path)) : array('error' => 'file mancante in data/files/.../doc');
      // acquisita nel frattempo (dal riquadro o con l'upload singolo): esce dalla coda
      $rsT = gaz_dbi_query("SELECT id_tes FROM " . $gTables['tesdoc'] . " WHERE BINARY fattura_elettronica_original_name = '" . gaz_dbi_real_escape_string($q['title']) . "' LIMIT 1");
      if (gaz_dbi_fetch_array($rsT)) {
          gaz_dbi_table_update('files', array('id_doc', intval($q['id_doc'])), array('status' => 1));
          $acquired++;
          continue;
      }
      $cus = null;
      if (!isset($info['error'])) {
          if ($info['piva'] !== '' && isset($customers['p' . ltrim($info['piva'], '0')])) $cus = $customers['p' . ltrim($info['piva'], '0')];
          elseif ($info['cf'] !== '' && isset($customers['c' . strtoupper($info['cf'])])) $cus = $customers['c' . strtoupper($info['cf'])];
      }
      // stesso numero allo stesso cliente già registrato nello stesso anno: probabile doppione
      $dup = false;
      if ($cus && $info['numero'] !== '' && $info['data'] !== '') {
          $dup = (bool) gaz_dbi_fetch_array(gaz_dbi_query("SELECT id_tes FROM " . $gTables['tesdoc'] . " WHERE tipdoc LIKE 'F%' AND clfoco = " . intval($cus['codice'])
              . " AND numfat = '" . gaz_dbi_real_escape_string($info['numero']) . "' AND YEAR(datfat) = " . intval(substr($info['data'], 0, 4)) . " LIMIT 1"));
      }
      $rows[] = array('q' => $q, 'ext' => $ext, 'info' => $info, 'cus' => $cus, 'dup' => $dup);
  }
  // ordine: per data della fattura, così si registrano in ordine cronologico (il protocollo segue la data)
  usort($rows, function ($a, $b) {
      $da = isset($a['info']['data']) ? $a['info']['data'] : '';
      $db = isset($b['info']['data']) ? $b['info']['data'] : '';
      return strcmp($da . sprintf('%010d', $a['q']['id_doc']), $db . sprintf('%010d', $b['q']['id_doc']));
  });
  $tipi = array('TD01' => 'Fattura', 'TD02' => 'Acconto su fattura', 'TD03' => 'Acconto su parcella', 'TD04' => 'Nota di credito', 'TD05' => 'Nota di debito',
      'TD06' => 'Parcella', 'TD24' => 'Fattura differita', 'TD25' => 'Fattura differita (terzi)', 'TD26' => 'Cessione beni ammortizzabili');
  $iniMax = ini_get('upload_max_filesize');
  $iniPost = ini_get('post_max_size');
  $iniFiles = intval(ini_get('max_file_uploads'));
?>
<div class="ai-page">
  <div class="ai-card">
    <h4><i class="fa fa-upload"></i> Carica le fatture di vendita <small>più file XML / P7M o pacchetti ZIP</small></h4>
    <div class="ai-body">
      <form method="POST" enctype="multipart/form-data" id="ai-form">
        <label class="ai-drop" id="ai-drop">
          <i class="fa fa-cloud-upload"></i>
          <b>Trascina qui i file</b> oppure clicca per sceglierli
          <input type="file" name="fae[]" id="ai-files" multiple accept=".xml,.p7m,.zip,.XML,.P7M,.ZIP">
          <div class="ai-picked" id="ai-picked"></div>
        </label>
        <div class="ai-actions">
          <button type="submit" name="aei_upload" value="1" class="btn btn-warning" id="ai-send" disabled><i class="fa fa-plus"></i> Metti in coda</button>
        </div>
      </form>
      <p class="ai-help">Si accettano solo fatture emesse da questa azienda. Dagli ZIP si prendono solo i file .xml e .p7m (anche dentro uno ZIP contenuto nello ZIP); i file di servizio dello SdI (metadati _MT_, ricevute, notifiche) e gli altri allegati si ignorano.
        Le fatture già in coda o già acquisite vengono saltate. Limiti del server: <?php echo aeiH($iniMax); ?> per file, <?php echo aeiH($iniPost); ?> in tutto,
        <?php echo $iniFiles; ?> file per invio: per molte fatture conviene uno ZIP.</p>
      <?php if ($aeiReport) {
        $cnt = array('ok' => 0, 'skip' => 0, 'err' => 0, 'info' => 0);
        foreach ($aeiReport as $r) $cnt[$r[0]]++;
        $ico = array('ok' => 'fa-check ai-ok', 'skip' => 'fa-minus-circle ai-skip', 'err' => 'fa-times ai-err', 'info' => 'fa-info-circle text-muted');
        echo '<hr class="ai-sep">' . ($cnt['info'] == count($aeiReport) ? '' : '<p><b>Esito:</b> <span class="ai-ok">' . $cnt['ok'] . ' in coda</span> &middot; <span class="ai-skip">' . $cnt['skip'] . ' saltate</span> &middot; <span class="ai-err">' . $cnt['err'] . ' con errori</span></p>') . '<ul class="ai-rep">';
        // i nomi in $r[1] sono già protetti (contengono il separatore &rsaquo; per i file dentro gli ZIP)
        foreach ($aeiReport as $r) echo '<li><i class="fa ' . $ico[$r[0]] . '"></i> ' . $r[1] . ' &mdash; <span class="text-muted">' . aeiH($r[2]) . '</span></li>';
        echo '</ul>';
      } ?>
    </div>
  </div>

  <div class="ai-card">
    <h4><i class="fa fa-list"></i> Fatture da acquisire <small><?php echo count($rows); ?> in coda<?php echo $acquired ? ' &middot; ' . $acquired . ' appena acquisite tolte dalla coda' : ''; ?></small></h4>
    <div class="ai-body">
      <?php if ($rows) { ?>
      <form method="POST" id="ai-qform">
      <div class="ai-tools">
        <span class="text-muted ai-note"><i class="fa fa-info-circle"></i> La fattura si apre qui sopra nella normale pagina di acquisizione: scegli pagamento, conti, IVA e articoli e confermi come sempre.
          Con "Elabora la coda", dopo ogni conferma si apre da sola la fattura successiva.</span>
        <span>
          <button type="button" class="btn btn-sm btn-success" id="ai-next"><i class="fa fa-play"></i> Elabora la coda</button>
          <button type="submit" name="aei_remove" value="1" class="btn btn-sm btn-default" onclick="return document.querySelectorAll('.ai-sel:checked').length ? confirm('Togliere dalla coda le fatture selezionate? I file vengono cancellati.') : (alert('Seleziona almeno una fattura'), false);"><i class="fa fa-trash"></i> Togli dalla coda</button>
        </span>
      </div>
      <div class="ai-scroll">
      <table class="ai-tab">
        <tr><th><input type="checkbox" id="ai-all" title="Seleziona tutte"></th><th>Cliente</th><th>Documento</th><th>Data</th><th class="r">Totale</th><th>Controlli</th><th></th></tr>
        <?php foreach ($rows as $r) {
          $q = $r['q'];
          $in = $r['info'];
          $id = intval($q['id_doc']);
          $bad = isset($in['error']) || !$in['mine'];
          $label = isset($in['error']) ? $q['title'] : trim(($in['cliente'] !== '' ? $in['cliente'] : '?') . ' - n. ' . $in['numero'] . ($in['data'] ? ' del ' . gaz_format_date($in['data']) : ''));
          echo '<tr data-id="' . $id . '" data-label="' . aeiH($label) . '"' . ($bad ? ' class="ai-bad"' : '') . '><td><input type="checkbox" class="ai-sel" name="aei_sel[]" value="' . $id . '"></td>';
          if (isset($in['error'])) {
            echo '<td colspan="4"><span class="ai-badge ai-b-err">' . aeiH($in['error']) . '</span><div class="ai-file">' . aeiH($q['title']) . '</div></td><td></td>';
          } else {
            $tipo = isset($tipi[$in['tipo']]) ? $tipi[$in['tipo']] : $in['tipo'];
            echo '<td>' . aeiH($in['cliente'] !== '' ? $in['cliente'] : '?') . '<div class="ai-file">' . aeiH(($in['piva'] !== '' ? $in['paese'] . $in['piva'] : $in['cf'])) . ' &middot; ' . aeiH($q['title']) . '</div></td>';
            echo '<td>' . aeiH($tipo) . ' n. <b>' . aeiH($in['numero']) . '</b></td>';
            echo '<td class="ai-nowrap">' . ($in['data'] ? gaz_format_date($in['data']) : '') . '</td>';
            echo '<td class="r">' . ($in['totale'] !== '' ? '&euro;&nbsp;' . number_format((float) $in['totale'], 2, ',', '.') : '') . '</td>';
            echo '<td>' . (!$in['mine'] ? '<span class="ai-badge ai-b-err"><i class="fa fa-ban"></i> emessa da un\'altra azienda</span>'
              : ($r['cus'] ? '<span class="ai-badge ai-b-ok" title="' . aeiH($r['cus']['ragso1']) . '"><i class="fa fa-check"></i> cliente in anagrafica</span>' : '<span class="ai-badge ai-b-new" title="Verrà inserito il nuovo cliente con il pagamento scelto"><i class="fa fa-user-plus"></i> cliente nuovo</span>'))
              . ($r['dup'] ? '<span class="ai-badge ai-b-err" title="Esiste già una fattura a questo cliente con lo stesso numero nello stesso anno"><i class="fa fa-exclamation-triangle"></i> numero già registrato</span>' : '') . '</td>';
          }
          echo '<td class="r">' . ($bad ? '' : '<button type="button" class="btn btn-xs btn-success ai-acq" data-id="' . $id . '" data-title="' . aeiH($q['title']) . '"><i class="fa fa-sign-in"></i> Acquisisci</button>') . '</td></tr>';
        } ?>
      </table>
      </div>
      </form>
      <?php } else { ?>
      <div class="ai-empty"><i class="fa fa-check-circle"></i><br>Nessuna fattura da acquisire.</div>
      <?php } ?>
    </div>
  </div>
</div>
<div class="ai-ov" id="ai-ov">
  <div class="ai-ov-box">
    <div class="ai-ov-bar">
      <span class="ai-ov-title"><i class="fa fa-sign-in"></i> <b id="ai-ov-title"></b> <span class="text-muted" id="ai-ov-pos"></span></span>
      <span id="ai-ov-msg" class="ai-ov-msg"></span>
      <span class="ai-ov-btns">
        <button type="button" class="btn btn-sm btn-default" id="ai-skip" title="Lascia questa fattura in coda e passa alla successiva"><i class="fa fa-step-forward"></i> Salta</button>
        <button type="button" class="btn btn-sm btn-default" id="ai-close" title="Chiudi e torna all'elenco"><i class="fa fa-times"></i> Chiudi</button>
      </span>
    </div>
    <iframe name="ai-frame" id="ai-frame" src="about:blank" title="Acquisizione fattura"></iframe>
  </div>
</div>
<form method="POST" action="<?php echo $aeiSelf; ?>" target="ai-frame" id="ai-go" style="display:none">
  <input type="hidden" name="fattura_elettronica_original_name" value="">
  <input type="hidden" name="aei_from_queue" id="ai-go-id" value="">
  <input type="hidden" name="aei_in_frame" value="1">
</form>
<script>
(function () {
  var inp = document.getElementById('ai-files'), drop = document.getElementById('ai-drop'), picked = document.getElementById('ai-picked'), send = document.getElementById('ai-send');
  function show() {
    var n = inp.files ? inp.files.length : 0, names = [];
    for (var i = 0; i < n && i < 8; i++) names.push(inp.files[i].name);
    picked.textContent = n ? n + ' file: ' + names.join(', ') + (n > 8 ? ' ...' : '') : '';
    send.disabled = !n;
  }
  inp.addEventListener('change', show);
  ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('ai-over'); }); });
  ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('ai-over'); }); });
  drop.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files.length) { inp.files = e.dataTransfer.files; show(); } });

  // la fattura si apre nel riquadro; quando la conferma riesce la pagina va su report_docven.php: è il segnale che è stata registrata
  var ov = document.getElementById('ai-ov'), frame = document.getElementById('ai-frame');
  var cur = null, queueMode = false, doneCount = 0;
  var rows = function () { return Array.prototype.slice.call(document.querySelectorAll('tr[data-id]')); };
  function pending() { return rows().filter(function (tr) { return tr.querySelector('.ai-acq') && !/ai-(done|skipped|bad)/.test(tr.className); }); }
  function msg(t, cls) { var m = document.getElementById('ai-ov-msg'); m.textContent = t || ''; m.className = 'ai-ov-msg ' + (cls || ''); }
  function open(tr) {
    cur = tr;
    document.getElementById('ai-go-id').value = tr.getAttribute('data-id');
    document.getElementById('ai-ov-title').textContent = tr.getAttribute('data-label');
    var p = pending();
    document.getElementById('ai-ov-pos').textContent = queueMode ? (p.length > 1 ? '(ne restano altre ' + (p.length - 1) + ')' : '(ultima della coda)') : '';
    rows().forEach(function (r) { r.classList.remove('ai-current'); });
    tr.classList.add('ai-current');
    ov.classList.add('ai-on');
    document.body.style.overflow = 'hidden';
    document.getElementById('ai-go').submit();
  }
  function advance() {
    var p = pending();
    if (queueMode && p.length) { open(p[0]); return; }
    close();
  }
  function close() {
    ov.classList.remove('ai-on');
    document.body.style.overflow = '';
    cur = null;
    frame.src = 'about:blank';
    // se qualcosa è stato acquisito si ricarica l'elenco, così le fatture registrate escono dalla coda
    if (doneCount) location.href = '<?php echo $aeiSelf; ?>';
  }
  frame.addEventListener('load', function () {
    if (!cur) return;
    var w, path = '';
    try { w = frame.contentWindow; path = w.location.pathname; } catch (e) { return; }
    if (/report_docven\.php$/.test(path)) {
      // registrata: la riga diventa "acquisita" e si passa alla successiva (in modalità coda)
      cur.classList.remove('ai-current');
      cur.classList.add('ai-done');
      var cell = cur.querySelector('.ai-acq');
      if (cell) cell.outerHTML = '<span class="ai-badge ai-b-ok"><i class="fa fa-check"></i> acquisita</span>';
      doneCount++;
      msg(doneCount + (doneCount == 1 ? ' fattura acquisita' : ' fatture acquisite'), 'ai-ok');
      cur = null;
      advance();
      return;
    }
    // dentro il riquadro basta la pagina di acquisizione: si nascondono barra in alto, menu laterale e piè di pagina
    try {
      var d = w.document, st = d.createElement('style');
      st.textContent = '.main-header,.main-sidebar,.main-footer,.gz-sidebar{display:none!important}.content-wrapper,.main-footer{margin-left:0!important}body,.wrapper,.content-wrapper{padding-top:0!important;min-height:0!important}';
      d.head.appendChild(st);
    } catch (e) {}
  });
  document.getElementById('ai-skip').addEventListener('click', function () {
    if (cur) { cur.classList.remove('ai-current'); cur.classList.add('ai-skipped'); }
    cur = null;
    advance();
  });
  document.getElementById('ai-close').addEventListener('click', close);
  Array.prototype.forEach.call(document.querySelectorAll('.ai-acq'), function (b) {
    b.addEventListener('click', function () { queueMode = false; open(b.closest('tr')); });
  });
  var next = document.getElementById('ai-next');
  if (next) next.addEventListener('click', function () {
    // una nuova passata riprende anche le fatture saltate in precedenza
    rows().forEach(function (r) { r.classList.remove('ai-skipped'); });
    var p = pending();
    if (!p.length) { alert('Nessuna fattura da acquisire'); return; }
    queueMode = true;
    open(p[0]);
  });
  var all = document.getElementById('ai-all');
  if (all) all.addEventListener('change', function () { Array.prototype.forEach.call(document.querySelectorAll('.ai-sel'), function (c) { c.checked = all.checked; }); });
})();
</script>
<?php
}
?>
<script>
// attesa durante i POST (lettura XML, controlli, registrazione): si copre lo schermo con library/images/wait_spinner.html.
// Se il form è indirizzato al riquadro della coda, lo spinner va dentro quel riquadro.
(function () {
  var SPIN = '../../library/images/wait_spinner.html';
  function show(doc) {
    try {
      if (!doc || !doc.body || doc.getElementById('gaz-wait')) return;
      var d = doc.createElement('div');
      d.id = 'gaz-wait';
      d.setAttribute('role', 'status');
      d.innerHTML = '<iframe src="' + SPIN + '" title="Attendere"></iframe>'
        + '<div class="gaz-wait-msg">Elaborazione in corso, attendere&hellip;</div>';
      doc.body.appendChild(d);
    } catch (e) {}
  }
  function hide() { var d = document.getElementById('gaz-wait'); if (d) d.parentNode.removeChild(d); }
  function onSend(form, submitter) {
    if (submitter && submitter.name === 'Download') return;
    var t = (submitter && submitter.getAttribute('formtarget')) || form.getAttribute('target') || '';
    if (t === '_blank') return;
    if (t && t !== '_self') {
      var f = document.querySelector('iframe[name="' + t + '"]');
      if (f) { try { show(f.contentDocument); } catch (e) {} }
      return;
    }
    show(document);
  }
  document.addEventListener('submit', function (e) {
    var form = e.target, sub = e.submitter;
    setTimeout(function () { if (!e.defaultPrevented) onSend(form, sub); }, 0);
  });
  var nativeSubmit = HTMLFormElement.prototype.submit;
  HTMLFormElement.prototype.submit = function () { onSend(this, null); return nativeSubmit.apply(this, arguments); };
  window.addEventListener('pageshow', hide);
})();
</script>
<?php
require("../../library/include/footer.php");
?>
