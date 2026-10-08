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
$cleanFAE = new cleaningElectronicInvoice;

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
  $p7mContent = $cleanFAE->recursiveDecodeContent($p7mContent,$fattxml);
  $cert = @tempnam(DATA_DIR . 'files/tmp/', 'pem');
  $retn = openssl_pkcs7_verify($fattxml, PKCS7_NOVERIFY, $cert);
  unlink($cert);
  if (!$retn) {
    echo "Error verifying PKCS#7 signature in {$fattxml}";
    return false;
  }

  $fatt = $cleanFAE->extractDER($fattxml);
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

	if (!empty($fatt)) {
		$invoiceContent = $fatt;
    } else {
		$invoiceContent = $cleanFAE->removeSignature($p7mContent);
    }

	$doc = new DOMDocument;
	$doc->preserveWhiteSpace = false;
	$doc->formatOutput = true;

	if (FALSE === @$doc->loadXML(mb_convert_encoding($invoiceContent, 'UTF-8', mb_list_encodings()))) {
   	// elimino le sequenze di caratteri non stampabili aggiunti dalla firma (da testare approfonditamente)
  	$invoiceContent = preg_replace('/[[:^print:]]/', '', $invoiceContent);
		if (FALSE === @$doc->loadXML(mb_convert_encoding($invoiceContent, 'UTF-8', mb_list_encodings()))) {
      $invoiceContent = $cleanFAE->recoverCorruptedXML($invoiceContent);
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
