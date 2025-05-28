<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2025-present - Antonio Germani, Massignano (AP)
  (https://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------

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

$xmlPath = urldecode($_GET['ref']);

require("../../library/include/datlib.inc.php");
require_once("lib.function.php");
$admin_aziend=checkAdmin();
$form = gaz_dbi_get_row($gTables['artico_group'], 'id_artico_group', intval($_GET['id']));
if ($data = json_decode($form['custom_field'], TRUE)) { // se esiste un json nel custom field
    if (is_array($data['vacation_rental'])){
        //$form['csmt'] = (isset($data['vacation_rental']['csmt']))?$data['vacation_rental']['csmt']:'';
        $username = (isset($data['vacation_rental']['userIstat']))?$data['vacation_rental']['userIstat']:'';
        $password = (isset($data['vacation_rental']['pwIstat']))?$data['vacation_rental']['pwIstat']:'';
        $wsdl = (isset($data['vacation_rental']['endpointIstat']))?$data['vacation_rental']['endpointIstat']:'';
    }
}
if($username=="" || $password=="" || $wsdl==""){
   die("❌ Alcune impostazioni del web service sono mancanti nella struttura\n");
}

$xmlContent = file_get_contents($xmlPath);
if ($xmlContent === false) {
    die("❌ Errore nel caricamento del file XML.");
}
echo "<h1>📋 Invio movimenti turistici per ISAT Ross1000\n</h1><br>";
// Rimuove dichiarazione XML e commenti
$xmlContent = preg_replace('/<\?xml.*?\?>/', '', $xmlContent);
$xmlContent = preg_replace('/<!--.*?-->/s', '', $xmlContent);
$xmlContent = trim($xmlContent);

// Sostituisce tag radice <movimenti> con <movimentazione>
$xmlContent = preg_replace('/^<movimenti>/i', '<movimentazione>', $xmlContent);
$xmlContent = preg_replace('/<\/movimenti>$/i', '</movimentazione>', $xmlContent);

// === SOAP Envelope conforme al manuale ===
$soapEnvelope = <<<XML
<?xml version="1.0"?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
  <S:Body>
    <ns2:inviaMovimentazione xmlns:ns2="http://checkin.ws.service.turismo5.gies.it/">
      $xmlContent
    </ns2:inviaMovimentazione>
  </S:Body>
</S:Envelope>
XML;

// Autenticazione base64
$credentials = base64_encode("$username:$password");
//echo "Authorization: Basic " . $credentials . "\n<br>";
// Headers SOAP
$headers = [
    "Content-Type: text/xml; charset=utf-8",
    "Authorization: Basic $credentials",
    "SOAPAction: \"\"",
    "Accept: text/xml, multipart/related",
    "User-Agent: JAX-WS RI 2.2.9-b130926.1035",
    "Connection: keep-alive"
];

//echo "\n\nSOAP Envelope inviato:\n" . htmlspecialchars($soapEnvelope) . "\n\n<br>";

// cURL call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $wsdl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $soapEnvelope);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_VERBOSE, true); // Debug
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);

if ($response === false) {
    echo "❌ Errore cURL: " . curl_error($ch);
    curl_close($ch);
    exit;
}

// === Separazione header e body ===
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);

// Controlla il codice HTTP e mostra un messaggio con l'icona appropriata
echo "📡 Risposta invio: ";
if ($http_code == 200) {
    echo "✅ Successo! Codice HTTP: $http_code<br>";
} else {
    echo "❌ Errore! Codice HTTP: $http_code<br>";
}

if ($http_code >= 200 && $http_code < 300) {

    echo "<br>📋 Analisi dei movimenti inviati:\n<br>";

    // Estraiamo tutti i tag <errore> dal corpo della risposta
    preg_match_all('/<errore>(.*?)<\/errore>/', $body, $erroreMatches);

    // Verifica se ci sono errori (verifica se c'è almeno un errore non vuoto)

    $hasError = false;
    $errorMessages = [];

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadXML($body);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Trova tutti i nodi <errore> che hanno contenuto
    $errors = $xpath->query('//errore[string-length(normalize-space()) > 0]');

    foreach ($errors as $errorNode) {
        $parent = $errorNode->parentNode;
        $parentName = $parent->nodeName;
        $errorText = trim($errorNode->nodeValue);

        $contextFields = [];

        foreach ($parent->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName !== 'errore') {
                $value = trim($child->nodeValue);
                if ($value !== '') {
                    $contextFields[] = "{$child->nodeName}: $value";
                }
            }
        }

        $contextStr = implode(', ', $contextFields);
        $errorMessages[] = "Errore in <$parentName> [$contextStr]: $errorText";
        $hasError = true;
    }

    // Mostra gli errori
    if ($hasError) {
        echo "<br>❌ Ci sono errori nei seguenti movimenti:<br>";
        foreach ($errorMessages as $errorMessage) {
            echo "• $errorMessage<br>";
        }
    } else {
        echo "<br>✅ Tutti i movimenti sono stati processati con successo!<br>";
    }

      // Mostra esito complessivo anche in assenza di errori
    $esitoNodes = $xpath->query('//esito | //codiceEsito | //esitoInvio');
    if ($esitoNodes->length > 0) {
        echo "<br>📨 Esito dell'invio:<br>";
        foreach ($esitoNodes as $node) {
            echo "• <strong>{$node->nodeName}</strong>: " . htmlspecialchars($node->nodeValue) . "<br>";
        }
    } else {
        echo "<br>ℹ️ Nessun esito esplicito trovato nella risposta.<br>";
    }

} else {
    echo "<br>❌ Errore HTTP: codice $http_code\n<br>";
     // Prova a trovare un messaggio di errore leggibile nel corpo HTML
   // Mostra eventuale errore SOAP in modo leggibile (versione semplice)
    if (strpos($body, '<faultstring>') !== false) {
      preg_match('/<faultcode>(.*?)<\/faultcode>/', $body, $codeMatch);
      preg_match('/<faultstring>(.*?)<\/faultstring>/', $body, $msgMatch);

      $faultcode = isset($codeMatch[1]) ? htmlspecialchars($codeMatch[1]) : 'N/D';
      $faultstringRaw = isset($msgMatch[1]) ? htmlspecialchars($msgMatch[1]) : 'N/D';

      // Formattazione avanzata
      $formattedMsg = $faultstringRaw;
      if (preg_match('/^(.*?)(Gli elementi previsti sono )(.*)$/', $faultstringRaw, $matches)) {
          $intro = $matches[1];
          $listIntro = $matches[2];
          $rawList = $matches[3];

          // Estrai gli elementi XML
          $elements = explode(',', $rawList);
          $elements = array_map('trim', $elements);
          $formattedList = implode("<br>• ", $elements);

          $formattedMsg = "$intro<br><br><strong>$listIntro</strong><br>• $formattedList";
      }

      echo "<div style='color: red; border: 1px solid red; padding: 10px; margin-top:20px;'>";
      echo "<strong>❌ Errore SOAP</strong><br>";
      echo "<strong>Codice:</strong> $faultcode<br>";
      echo "<strong>Messaggio:</strong><br><div style='white-space:pre-wrap;'>$formattedMsg</div>";
      echo "</div>";
    } else {
        // Prova a trovare un messaggio di errore leggibile nel corpo HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($body);
        libxml_clear_errors();

        $h1 = $dom->getElementsByTagName('h1');
        $h3 = $dom->getElementsByTagName('h3');

        $errorTitle = ($h1->length > 0) ? trim($h1->item(0)->textContent) : '';
        $errorDescription = ($h3->length > 0) ? trim($h3->item(0)->textContent) : '';

        if ($errorTitle || $errorDescription) {
            echo "🔒 Dettaglio: ";
            if ($errorTitle) echo "$errorTitle<br>";
            if ($errorDescription) echo "$errorDescription<br>";
        } else {
            echo "📄 Corpo risposta:<br><pre style='white-space:pre-wrap; background:#eee; padding:10px; border:1px solid #ccc;'>" . htmlspecialchars($body) . "</pre>";
        }
    }
}

curl_close($ch);

?>
