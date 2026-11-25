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

  Trasmissione delle fatture elettroniche tramite YouDOX (DocuMI), canale Web Services.
  Si attiva scrivendo "youdox" in Configurazione azienda > Configurazione Avanzata > send_fae_zip_package
  e compilando nella stessa pagina:
    youdox_user, youdox_psw (salvata cifrata), youdox_client_id: le credenziali dell'utenza YouDOX;
    youdox_sub_user: solo se le credenziali sono di un rivenditore (utenza "vendor"): l'utenza
      dell'azienda su fatturazione.youdox.it, dove finiscono le fatture;
    youdox_endpoint: vuoto = produzione (https://servizi.youdox.it/fatturazione/api/).
  Funzioni richieste da GAzie (stesse della libreria catsrl):
    SendFatturaElettronica($file)      -> array(riferimento) da salvare in fae_flux.id_SDI, oppure messaggio d'errore
    SendFattureElettroniche($zip)      -> array(nome file => riferimento), oppure messaggio d'errore
    ReceiveNotifiche(array(cf => rif)) -> array(riferimento => array('esito' => RC|NS|MC|NE|DT|AT, 'motivo' => ...))
    ReceiveFattF / DownloadFattF       -> fatture dei fornitori: con YouDOX si scaricano dal portale (messaggio)
  Il riferimento salvato in id_SDI e' un numero ricavato dal nome del file inviato; lo stato si chiede a YouDOX
  per nome file (Invoices_ListSentByFilename), preso da fae_flux.filename_ori.
  Riferimenti: SpecificheTecniche_YouDOXFatturazione v.2.3 (GetToken.aspx, ExchangeService, InvoicesService).
*/

define('YOUDOX_ENDPOINT_PRODUZIONE', 'https://servizi.youdox.it/fatturazione/api/');

function youdox_config()
{
	global $gTables;
	$c = array();
	foreach (array('youdox_user', 'youdox_client_id', 'youdox_sub_user', 'youdox_endpoint') as $v) {
		$r = gaz_dbi_get_row($gTables['company_config'], 'var', $v);
		$c[$v] = $r ? trim((string)$r['val']) : '';
	}
	$c['youdox_psw'] = '';
	if (isset($_SESSION['aes_key'])) { // salvata cifrata da Configurazione Avanzata (come smtp_password)
		$rs = gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'" . $_SESSION['aes_key'] . "') FROM " . $gTables['company_config'] . " WHERE var = 'youdox_psw'");
		$r = gaz_dbi_fetch_row($rs);
		$c['youdox_psw'] = $r ? (string)$r[0] : '';
	}
	$c['youdox_endpoint'] = rtrim($c['youdox_endpoint'] !== '' ? $c['youdox_endpoint'] : YOUDOX_ENDPOINT_PRODUZIONE, '/') . '/';
	return $c;
}

function youdox_token($c)
{
	if ($c['youdox_user'] === '' || $c['youdox_psw'] === '' || $c['youdox_client_id'] === '') {
		return array(false, 'YouDOX: mancano le credenziali (youdox_user, youdox_psw, youdox_client_id in Configurazione Avanzata)');
	}
	$campi = array('username' => $c['youdox_user'], 'password' => $c['youdox_psw'], 'client_id' => $c['youdox_client_id']);
	if ($c['youdox_sub_user'] !== '') {
		$campi['sub_username'] = $c['youdox_sub_user'];
	}
	$ch = curl_init($c['youdox_endpoint'] . 'GetToken.aspx');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($campi)); // in POST: le credenziali non finiscono nei log
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	$risposta = curl_exec($ch);
	$errore = curl_error($ch);
	curl_close($ch);
	if ($risposta === false) {
		return array(false, 'YouDOX: connessione non riuscita (' . $errore . ')');
	}
	$d = json_decode($risposta, true);
	if (!is_array($d) || empty($d['access_token'])) {
		$msg = is_array($d) && !empty($d['error']) ? $d['error'] . ' ' . (isset($d['error_message']) ? $d['error_message'] : '') : 'risposta non valida';
		return array(false, 'YouDOX: accesso non riuscito (' . trim($msg) . ')');
	}
	return array(true, $d['access_token']);
}

function youdox_soap($c, $servizio, $azione, $corpo)
{
	$busta = '<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
		. ' xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
		. '<soap:Body>' . $corpo . '</soap:Body></soap:Envelope>';
	$ch = curl_init($c['youdox_endpoint'] . $servizio . '.svc');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $busta);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml; charset=utf-8', 'SOAPAction: http://tempuri.org/I' . $servizio . '/' . $azione));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 90);
	$risposta = curl_exec($ch);
	$errore = curl_error($ch);
	curl_close($ch);
	if ($risposta === false) {
		return array(false, 'YouDOX: connessione non riuscita (' . $errore . ')');
	}
	if (preg_match('/<(?:\w+:)?faultstring[^>]*>(.*?)<\/(?:\w+:)?faultstring>/s', $risposta, $m)) {
		return array(false, 'YouDOX: ' . html_entity_decode(trim($m[1])));
	}
	return array(true, $risposta);
}

function youdox_tag($xml, $tag)
{
	if (preg_match('/<(?:\w+:)?' . $tag . '(?:\s[^>]*)?>(.*?)<\/(?:\w+:)?' . $tag . '>/s', $xml, $m)) {
		$v = html_entity_decode(trim($m[1]));
		return $v === '' ? null : $v;
	}
	return null;
}

/* riferimento da salvare in fae_flux.id_SDI: solo cifre (check_fae_sdi.php cerca "id_SDI != 0", e un testo come
   "IT0123..." per MySQL vale 0), ricavato dal nome del file; il nome completo si ritrova poi in fae_flux */
function youdox_riferimento($nome_file)
{
	return sprintf('%u', crc32($nome_file));
}

/* manda un file (XML o XML firmato) col suo nome; true oppure messaggio d'errore.
   Con la costante YOUDOX_SOLO_VERIFICA definita il file viene solo controllato (Exchange_ValidateXML), non trasmesso. */
function youdox_invia($c, $token, $contenuto, $nome_file)
{
	if (defined('YOUDOX_SOLO_VERIFICA')) {
		$azione = 'Exchange_ValidateXML';
		$corpo = '<Exchange_ValidateXML xmlns="http://tempuri.org/"><client_id>' . htmlspecialchars($c['youdox_client_id'], ENT_XML1) . '</client_id>'
			. '<input_bytes>' . base64_encode($contenuto) . '</input_bytes><filename>' . htmlspecialchars($nome_file, ENT_XML1) . '</filename></Exchange_ValidateXML>';
	} else {
		$azione = 'Exchange_ImportXMLToSend';
		$corpo = '<Exchange_ImportXMLToSend xmlns="http://tempuri.org/"><token>' . htmlspecialchars($token, ENT_XML1) . '</token>'
			. '<input_bytes>' . base64_encode($contenuto) . '</input_bytes><filename>' . htmlspecialchars($nome_file, ENT_XML1) . '</filename>'
			. '<please_validate>true</please_validate></Exchange_ImportXMLToSend>';
	}
	list($ok, $risposta) = youdox_soap($c, 'ExchangeService', $azione, $corpo);
	if (!$ok) {
		return $risposta;
	}
	$errore = true; // senza IsErrorExchange la risposta e' inattesa: meglio non considerarla riuscita
	if (preg_match('/<(?:\w+:)?IsErrorExchange>(.*?)<\//', $risposta, $m)) {
		$errore = (trim($m[1]) !== 'false');
	}
	if ($errore) {
		$msg = youdox_tag($risposta, 'Message');
		$cod = youdox_tag($risposta, 'ErrorCode');
		return 'YouDOX ha rifiutato ' . $nome_file . ': ' . ($msg ? $msg : 'risposta non riconosciuta') . ($cod ? ' (' . $cod . ')' : '');
	}
	return true;
}

function SendFatturaElettronica($xml_fattura)
{
	$c = youdox_config();
	list($ok, $token) = youdox_token($c);
	if (!$ok) {
		return $token;
	}
	$nome = basename($xml_fattura);
	$contenuto = @file_get_contents($xml_fattura);
	if ($contenuto === false || $contenuto === '') {
		return 'YouDOX: file non leggibile ' . $nome;
	}
	$esito = youdox_invia($c, $token, $contenuto, $nome);
	return $esito === true ? array(youdox_riferimento($nome)) : $esito;
}

function SendFattureElettroniche($zip_fatture)
{
	$c = youdox_config();
	list($ok, $token) = youdox_token($c);
	if (!$ok) {
		return $token;
	}
	$zip = new ZipArchive();
	if ($zip->open($zip_fatture) !== true) {
		return 'YouDOX: pacchetto non leggibile ' . basename($zip_fatture);
	}
	$riferimenti = array();
	$errori = array();
	for ($i = 0; $i < $zip->numFiles; $i++) {
		$nome = basename($zip->getNameIndex($i));
		if ($nome === '' || !preg_match('/\.xml(\.p7m)?$/i', $nome)) {
			continue;
		}
		$esito = youdox_invia($c, $token, $zip->getFromIndex($i), $nome);
		if ($esito === true) {
			$riferimenti[$nome] = youdox_riferimento($nome);
		} else {
			$errori[] = $esito;
		}
	}
	$zip->close();
	if (count($errori) > 0 && count($riferimenti) == 0) {
		return implode('<br />', $errori);
	}
	if (count($errori) > 0) { // alcune partite, altre no: le partite vanno registrate, gli errori si vedono
		echo '<p>' . implode('<br />', $errori) . '</p>';
	}
	return $riferimenti;
}

function ReceiveNotifiche($array_sdi)
{
	global $gTables;
	$c = youdox_config();
	list($ok, $token) = youdox_token($c);
	if (!$ok) {
		return $token;
	}
	// stati YouDOX (classe Status delle specifiche) -> codici dei flussi di GAzie; gli stati di lavorazione
	// (working, to_be_signed, waiting) e "inviata allo SdI non ancora consegnata" (sent_to_sdi) non cambiano nulla
	$esiti = array('sent_to_pa' => 'RC', 'sent_to_ade' => 'MC', 'sent_failed' => 'NS', 'accepted_by_pa' => 'NEEC01', 'over_time' => 'DT');
	$notifiche = array();
	foreach ($array_sdi as $riferimenti) {
		foreach ((array)$riferimenti as $rif) {
			// il nome completo del file sta in fae_flux (il riferimento puo' esserne solo la parte finale)
			$r = gaz_dbi_get_row($gTables['fae_flux'], 'id_SDI', $rif);
			$nome = ($r && !empty($r['filename_ori'])) ? $r['filename_ori'] : $rif;
			$corpo = '<Invoices_ListSentByFilename xmlns="http://tempuri.org/"><token>' . htmlspecialchars($token, ENT_XML1) . '</token>'
				. '<original_filename>' . htmlspecialchars($nome, ENT_XML1) . '</original_filename></Invoices_ListSentByFilename>';
			list($ok, $risposta) = youdox_soap($c, 'InvoicesService', 'Invoices_ListSentByFilename', $corpo);
			if (!$ok) {
				return $risposta;
			}
			if (!preg_match('/<(?:\w+:)?Invoices_ListSentByFilenameResult(?:\s[^>]*)?>.*<\/(?:\w+:)?Invoices_ListSentByFilenameResult>/s', $risposta, $m)) {
				continue; // YouDOX non la conosce (ancora)
			}
			$stato = strtolower((string)youdox_tag($m[0], 'Status'));
			if (isset($esiti[$stato])) {
				$motivo = youdox_tag($m[0], 'StatusMessage');
				$id_sdi = youdox_tag($m[0], 'IdentificativoSdI');
				$sdi = $id_sdi ? ' (identificativo SdI ' . $id_sdi . ')' : '';
				$motivo = $motivo ? mb_substr($motivo, 0, 240 - mb_strlen($sdi)) : ''; // fae_flux.flux_descri e' di 255 caratteri
				$notifiche[$rif] = array('esito' => $esiti[$stato], 'motivo' => trim($motivo . $sdi));
			}
		}
	}
	return $notifiche;
}

function ReceiveFattF($array_fattf)
{
	return 'Con YouDOX le fatture dei fornitori si scaricano dal portale fatturazione.youdox.it e si importano in GAzie dal file XML.';
}

function DownloadFattF($fattf_sdi)
{
	return ReceiveFattF($fattf_sdi);
}
