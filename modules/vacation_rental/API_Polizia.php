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

$path = isset($_GET['ref']) ? urldecode($_GET['ref']) : '';
$txtFile = $path . "/polstat.txt";

$id_polstat = 0; // 0 = invio normale; > 0 = file unico con IdAppartamento

// LO PRENDO DALLE IMPOSTAZIONI $wsdl = "https://alloggiatiweb.poliziadistato.it/service/service.asmx?wsdl";

require("../../library/include/datlib.inc.php");
require_once("lib.function.php");
$admin_aziend=checkAdmin();
$form = gaz_dbi_get_row($gTables['artico_group'], 'id_artico_group', intval($_GET['id']));
if ($data = json_decode($form['custom_field'], TRUE)) { // se esiste un json nel custom field
    if (is_array($data['vacation_rental'])){
        $utente = (isset($data['vacation_rental']['userPol']))?$data['vacation_rental']['userPol']:'';
        $wskey = (isset($data['vacation_rental']['wskey']))?$data['vacation_rental']['wskey']:'';
        $password = (isset($data['vacation_rental']['pwPol']))?$data['vacation_rental']['pwPol']:'';
        $wsdl = (isset($data['vacation_rental']['endpointPol']))?$data['vacation_rental']['endpointPol']:'';
    }
}
if(isset($_GET['type']) && intval($_GET['type'])<2){
  $id_polstat = intval($_GET['type']);
}else{
  die("❌ Manca la specifica tipo file\n");
}
if($utente=="" || $wskey=="" || $password=="" || $wsdl==""){
  die("❌ Alcune impostazioni del web service sono mancanti nella struttura\n");
}else{
  $id_polstat = intval($_GET['type']);
}
if (!filter_var($wsdl, FILTER_VALIDATE_URL) && !file_exists($wsdl)) {
    die("❌ WSDL-end point non valido o mancante: $wsdl");
}
echo "<h1>📋 Invio schedine alloggiati alla Polizia di Stato\n</h1><br>";
function scaricaRicevuteDisponibili($client, $utente, $token, $savePath, $giorniIndietro = 30) {
    echo "<br>📥 Inizio download ricevute (ultimi $giorniIndietro giorni)...<br>";

    $logCsv = $savePath . "/log_ricevute.csv";
    $righeCsv = [];

    // === CARICA DATE GIÀ LOGGATE ===
    $dateLoggate = [];
    if (file_exists($logCsv)) {
        $fp = fopen($logCsv, 'r');
        fgetcsv($fp); // salta intestazione
        while (($row = fgetcsv($fp)) !== false) {
            if (!empty($row[0])) {
                $dateLoggate[$row[0]] = true;
            }
        }
        fclose($fp);
    }

    for ($i = 0; $i < $giorniIndietro; $i++) {
        $data = (new DateTime())->modify("-$i days");
        $dataIso = $data->format('Y-m-d');
        $dataFormattata = $dataIso . "T00:00:00";
        $nomeFile = $savePath . "/ricevuta_alloggiati_" . $data->format('Ymd') . ".pdf";
        $dataDisplay = $data->format('d/m/Y');

        // ❗ Se già loggata, salta
        if (isset($dateLoggate[$dataIso])) {
            //echo "🟡 Già loggata: $dataDisplay<br>";
            continue;
        }

        try {
            $ricevutaResponse = $client->__soapCall("Ricevuta", [[
                'Utente' => $utente,
                'token'  => $token,
                'Data'   => $dataFormattata
            ]]);

            $pdfBase64 = $ricevutaResponse->PDF ?? null;
            if ($pdfBase64) {
                file_put_contents($nomeFile, base64_decode($pdfBase64));
                echo "📄 Ricevuta salvata per $dataDisplay<br>";
                $righeCsv[] = [$dataIso, 'Scaricata', basename($nomeFile), 'OK'];
            } else {
                echo "ℹ️ Nessuna ricevuta disponibile per $dataDisplay<br>";
                $righeCsv[] = [$dataIso, 'Non trovata', '-', 'Nessuna ricevuta disponibile'];
            }
        } catch (SoapFault $e) {
            echo "❗ Errore SOAP per $dataDisplay: " . $e->getMessage() . "<br>";
            $righeCsv[] = [$dataIso, 'Errore SOAP', '-', $e->getMessage()];
        }
    }

    // === APPENDE SOLO NUOVE RIGHE AL LOG ===
    if (!empty($righeCsv)) {
        $fileEsiste = file_exists($logCsv);
        $fp = fopen($logCsv, 'a');
        if (!$fileEsiste) {
            fputcsv($fp, ['Data', 'Esito', 'File', 'Note']); // intestazione solo se nuovo
        }
        foreach ($righeCsv as $riga) {
            fputcsv($fp, $riga);
        }
        fclose($fp);
        echo "<br>📝 Log aggiornato in: $logCsv<br>";
    } else {
        echo "<br>✅ Nessuna nuova ricevuta da loggare.<br>";
    }

    echo "✅ Download ricevute completato.<br>";
}

// === CARICA LE RIGHE DAL FILE TXT ===
$txtFileFlagged = $path . "/polstat_flagged.txt";

// Controlla se esiste già un file con un timestamp nel nome (file già inviato)
$files = glob($path . "/polstat_*_flagged.txt");

if (!empty($files)) {
    // Se ci sono file con timestamp, significa che è già stato inviato
    die("✅ Il file è già stato inviato in precedenza. Nessuna azione necessaria.\n");
}

// Se il file normale non esiste, errore
if (!file_exists($txtFile)) {
    die("❌ File non trovato: $txtFile\n");
}

$schedine = file($txtFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$schedine) {
    die("❌ Il file è vuoto o non leggibile.\n");
}

// === CREA SOAP CLIENT ===
$client = new SoapClient($wsdl, ['trace' => true, 'exceptions' => true]);

try {
    // === 1. OTTIENI IL TOKEN ===
    $tokenResponse = $client->__soapCall("GenerateToken", [[
        'Utente' => $utente,
        'Password' => $password,
        'WsKey' => $wskey
    ]]);
    $token = $tokenResponse->GenerateTokenResult->token ?? null;
    if (!$token) {
        throw new Exception("Token non ricevuto.");
    }
    echo "<br>✅ Token ottenuto\n\n";

    // === 2. TEST SCHEDINE ===
    $testMethod = $id_polstat > 0 ? "GestioneAppartamenti_FileUnico_Test" : "Test";
    $testParams = [
        'Utente' => $utente,
        'token' => $token,
        'ElencoSchedine' => ['string' => $schedine],
    ];
    if ($id_polstat == 0) {
        // metodo Test richiede solo token e schedine
    } else {
        // file unico
        // metodo FileUnico richiede solo ElencoSchedine
    }

    echo "<br>🧪 Controllo schedine con metodo $testMethod...\n";
    $testResponse = $client->__soapCall($testMethod, [$testParams]);

    $testResultKey = $testMethod . "Result";
    $testResult = $testResponse->$testResultKey ?? null;
    $dettaglio = $testResponse->result->Dettaglio ?? null;

    $erroriTrovati = false;

   // Verifica se Dettaglio è presente e contiene almeno 1 elemento
if (!empty($dettaglio)) {
    echo "<br>\n🔍 Esiti del test:\n";

    // Se è un singolo oggetto, trasformalo in array
    $esiti = is_array($dettaglio->EsitoOperazioneServizio)
        ? $dettaglio->EsitoOperazioneServizio
        : [$dettaglio->EsitoOperazioneServizio];

    foreach ($esiti as $i => $esito) {
        if (!is_object($esito)) continue;

        $numRiga = $i + 1;
        if (isset($esito->esito) && $esito->esito === false) {
            $erroriTrovati = true;
            echo "<br>❌ Riga $numRiga: {$esito->ErroreDes} - {$esito->ErroreDettaglio}\n";
        } else {
            echo "<br>✅ Riga $numRiga: OK\n";
        }
    }
}

    if ($testResult && $testResult->esito === false) {
        echo "<br>\n⛔️ Test fallito: errore generale.\n";
        exit;
    }

    if ($erroriTrovati) {
        echo "<br>\n⚠️ Alcune schedine non sono valide. Correggile prima di procedere.\n";
        exit;
    }

    echo "<br>\n✅ Test superato. Invio delle schedine...\n";



    // === 3. INVIO SCHEDINE ===
    if ($id_polstat > 0) {
        $sendMethod = "GestioneAppartamenti_FileUnico_Send";
        $sendParams = [
            'Utente' => $utente,
            'token'  => $token,
            'ElencoSchedine' => ['string' => $schedine]
        ];
    } else {
        $sendMethod = "Send";
        $sendParams = [
            'Utente' => $utente,
            'token'  => $token,
            'ElencoSchedine' => ['string' => $schedine]
        ];
    }

    $sendResponse = $client->__soapCall($sendMethod, [$sendParams]);
    $sendResultKey = $sendMethod . "Result";
    $sendResult = $sendResponse->$sendResultKey ?? null;

    echo "<br>\n📨 Invio effettuato.\nEsito: " . ($sendResult->esito ? "✅ OK" : "❌ ERRORE") . "\n";

    if ($sendResult->esito) {
    // ✅ Rinomina il file con il timestamp
    $timestamp = date('Ymd_His');  // Ad esempio: 20230523_143500
    $txtFileFlagged = $path . "/polstat_{$timestamp}_flagged.txt";

    $renamed = rename($txtFile, $txtFileFlagged);
    if ($renamed) {
        echo "<br>🏁 File rinominato a: $txtFileFlagged (inviato con successo)\n";
    } else {
        echo "<br>⚠️ ATTENZIONE: invio riuscito, ma non è stato possibile rinominare il file.\n";
    }
}


    // === 4. RICHIESTA RICEVUTA ===
    scaricaRicevuteDisponibili($client, $utente, $token, $path);

} catch (SoapFault $e) {
    echo "<br>❗ Errore SOAP: " . $e->getMessage() . "\n";
    echo "<br>📡 Richiesta:\n" . $client->__getLastRequest() . "\n";
    echo "<br>📡 Risposta:\n" . $client->__getLastResponse() . "\n";
} catch (Exception $e) {
    echo "<br>❗ Errore: " . $e->getMessage() . "\n";
}
