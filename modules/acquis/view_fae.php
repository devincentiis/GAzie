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
$admin_aziend=checkAdmin();

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
    /* COMMENTO per non sovrascrivere il file id_tes.inv

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
    */
    return true;
}

function recursiveDecodeContent($temp_content, $fn)
{
$to = <<<TXT
MIME-Version: 1.0
Content-Disposition: attachment; filename="smime.p7m"
Content-Type: application/x-pkcs7-mime; smime-type=signed-data; name="smime.p7m"
Content-Transfer-Encoding: base64
\n
TXT;
    $restorefile=false;
    $count=1;
    while($count > 0) {
        $last_temp_content=$temp_content;
        $removed_header = str_replace($to,'',$temp_content,$count);
        if ($count==1){ $restorefile = true; }
        $temp_content = base64_decode($removed_header,true);
    }
    if ($restorefile) { // ripristino il file in formato binario
        file_put_contents($fn,$last_temp_content);
    }
    return $last_temp_content;
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
	preg_match_all('/<\/.+?>/', $s, $matches, PREG_OFFSET_CAPTURE);
	$lastMatch = end($matches[0]);
	// trovo l'ultimo carattere del tag di chiusura per eliminare la coda
	$f_end = $lastMatch[1]+strlen($lastMatch[0]);
	$s = substr($s, 0, $f_end);
	// elimino le sequenze di caratteri aggiunti dalla firma (ancora da testare approfonditamente)
	$s = preg_replace('/[\x{0004}]{1}[\x{0082}]{1}[\x{0001}-\x{001F}]{1}[\s\S]{1}/i', '', $s);
	$s = preg_replace('/[\x{0004}]{1}[\x{0082}]{1}[\s\S]{1}[\x{0000}]{1}/i', '', $s);
	$s = preg_replace('/[\x{0004}]{1}[\x{0081}]{1}[\s\S]{1}/i', '', $s);
	$s = preg_replace('/[\x{0004}]{1}[\s\S]{1}/i', '', $s);
	$s = preg_replace('/[\x{0003}]{1}[\s\S]{1}/i', '', $s);
	//$s = preg_replace('/[\x{0004}]{1}[A-Za-z]{1}/i', '', $s); // per eliminare tag finale
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

if (isset($_POST['Download'])) { // è stato richiesto il download dell'allegato
		$name = filter_var($_POST['Download'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment;  filename="'.$name.'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize( DATA_DIR . 'files/tmp/' . $name ));
		readfile( DATA_DIR . 'files/tmp/' . $name );
		exit;
}

if (isset($_GET['id_tes'])){
  if (isset($_GET['fromdoc'])){ // mi viene indicato di attingere dalla cartella /doc
    $nf=substr($_GET['id_tes'],0,11);
    $fattxml = DATA_DIR . 'files/' . $admin_aziend["codice"] . '/doc/'.$nf;
  } else {
    $id=intval($_GET['id_tes']);
    $fattxml = DATA_DIR . 'files/' . $admin_aziend["codice"] . '/'.$id.'.inv';
  }
  $p7mContent = file_get_contents($fattxml);
  $p7mContent = recursiveDecodeContent($p7mContent,$fattxml);
	if (FALSE !== der2smime($fattxml)) {
	$cert = @tempnam(DATA_DIR . 'files/tmp/', 'pem');
	$retn = openssl_pkcs7_verify($fattxml, PKCS7_NOVERIFY, $cert);
	unlink($cert);
	if (!$retn) {
		echo "Error verifying PKCS#7 signature in {$fattxml}";
		return false;
	}

	$fatt = extractDER($fattxml);
	if (empty($fatt)) {
		$test = @base64_decode(file_get_contents($fattxml));
		// Salto lo header (INDISPENSABILE perché la regexp funzioni sempre)
		if (strpos($test, 'FatturaElettronicaSemplificata') !== FALSE) {
			if (preg_match('#(<[^>]*FatturaElettronicaSemplificata.*</[^>]*FatturaElettronicaSemplificata>)#', substr($test, 54), $gregs)) {
				$fatt = '<'.'?'.'xml version="1.0"'.'?'.'>' . $gregs[1]; // RECUPERO INTESTAZIONE XML
			}
		} else {
			if (preg_match('#(<[^>]*FatturaElettronica.*</[^>]*FatturaElettronica>)#', substr($test, 54), $gregs)) {
				$fatt = '<'.'?'.'xml version="1.0"'.'?'.'>' . $gregs[1]; // RECUPERO INTESTAZIONE XML
			}
		}
	}
	}

	if (!empty($fatt)) {
		$invoiceContent = $fatt;
    } else {
		$invoiceContent = removeSignature($p7mContent);
    }

	$doc = new DOMDocument;
	$doc->preserveWhiteSpace = false;
	$doc->formatOutput = true;

	if (FALSE === @$doc->loadXML(mb_convert_encoding($invoiceContent, 'UTF-8', mb_list_encodings()))) {
    	// elimino le sequenze di caratteri non stampabili aggiunti dalla firma (da testare approfonditamente)
    	$invoiceContent = preg_replace('/[[:^print:]]/', '', $invoiceContent);
		if (FALSE === @$doc->loadXML(mb_convert_encoding($invoiceContent, 'UTF-8', mb_list_encodings()))) {
        	$invoiceContent = recoverCorruptedXML($invoiceContent);
        	if (FALSE === @$doc->loadXML($invoiceContent)) {
				function HandleXmlError($errno, $errstr, $errfile, $errline)
				{
					echo($errno . ' - ' . $errstr . ' - ' . $errfile . ' - ' . $errline);
				}
				set_error_handler('HandleXmlError');
				$doc->loadXML($invoiceContent);
				restore_error_handler();
    	   		echo '<pre>' . $invoiceContent . '</pre>';
            }
		}
	}

	// ricavo l'allegato, e se presente metterò un bottone per permettere il download
	$nf = $doc->getElementsByTagName('NomeAttachment')->item(0);
	if ($nf){
		$name_file = preg_replace('/[^[:print:]]/', '',$nf->textContent);
		$att = $doc->getElementsByTagName('Attachment')->item(0);
		$base64 = $att->textContent;
		$bin = base64_decode($base64);
		file_put_contents( DATA_DIR . 'files/tmp/' . $name_file, $bin );
		echo '<form method="POST"><div class="col-sm-6"> Allegato: <input name="Download" type="submit" class="btn btn-default" value="'.$name_file.'" /></div></form>';
	}
	$xpath = new DOMXpath($doc);
	$fae_xsl_file = gaz_dbi_get_row($gTables['company_config'], 'var', 'fae_style');
	$xslDoc = new DOMDocument();
	$xslDoc->load('../../library/include/' . $fae_xsl_file['val'] . '.xsl');
	$xslt = new XSLTProcessor();
	$xslt->importStylesheet($xslDoc);
	echo $xslt->transformToXML($doc);
}
?>
