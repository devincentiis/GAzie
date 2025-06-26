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
$filepath = 'files/temp_ids.json';
if (file_exists($filepath)) {
    $testate = json_decode(file_get_contents($filepath), true);
    unlink($filepath);
}
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

    // Crea cartella ricevute
    $savePath = rtrim(dirname($savePath), "/") . "/ricevute_alloggiati/" . $utente;
    if (!is_dir($savePath)) {
        mkdir($savePath, 0775, true);
    }

    $logCsv = $savePath . "/log_ricevute.csv";
    $righeCsv = [];

    // === CARICA DATE GIÀ LOGGATE (PDF o TXT) ===
    $dateLoggate = []; // formato: ['YYYY-MM-DD' => 'PDF' o 'TXT']
    if (file_exists($logCsv)) {
        $fp = fopen($logCsv, 'r');
        if ($fp) {
            fgetcsv($fp); // salta intestazione
            while (($row = fgetcsv($fp)) !== false) {
                if (!empty($row[0]) && in_array($row[1], ['PDF', 'TXT'])) {
                    $dateLoggate[$row[0]] = $row[1];
                }
            }
            fclose($fp);
        }
    }

    // === RICHIESTA RICEVUTE PER OGNI GIORNO ===
    for ($i = 0; $i < $giorniIndietro; $i++) {
        $data = (new DateTime())->modify("-$i days");
        $dataIso = $data->format('Y-m-d');
        $dataFormattata = $dataIso . "T00:00:00";
        $dataDisplay = $data->format('d/m/Y');
        $nomeFilePdf = $savePath . "/ricevuta_alloggiati_" . $data->format('Ymd') . ".pdf";
        $nomeFileTxt = $savePath . "/ricevuta_alloggiati_" . $data->format('Ymd') . ".txt";

        // === Salta se abbiamo già un PDF
        if (($dateLoggate[$dataIso] ?? '') === 'PDF' || file_exists($nomeFilePdf)) {
            echo "🟢 Ricevuta PDF già acquisita per $dataDisplay — skip<br>";
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
                $pdfBase64Clean = preg_replace('/\s+/', '', $pdfBase64); // Rimuove spazi bianchi e linee
                
				$decoded = base64_decode($pdfBase64Clean, true);
				if ($decoded !== false) {
					// È una stringa Base64 valida
					//echo "Decodificato: " . $decoded;
				} else {
					// Non è una stringa Base64 valida
					//echo "Non è Base64 valido NON decodifico";
					$decoded=$pdfBase64;
				}
                if (strpos($decoded, '%PDF-') !== false) {
                    // Salva PDF, rimuovi eventuale TXT precedente
                    file_put_contents($nomeFilePdf, $decoded);
                    if (file_exists($nomeFileTxt)) {
                        unlink($nomeFileTxt);
                    }
                    echo "📄 Ricevuta PDF salvata per $dataDisplay<br>";
                    $righeCsv[] = [$dataIso, 'PDF', basename($nomeFilePdf), 'OK'];
                } elseif (mb_detect_encoding($decoded, ['UTF-8', 'ISO-8859-1', 'ASCII'], true) !== false && preg_match('//u', $decoded)) {
                    // Salva TXT solo se non esiste già
                    if (!file_exists($nomeFileTxt)) {
                        file_put_contents($nomeFileTxt, $decoded);
                        echo "📝 Ricevuta in chiaro (TXT) salvata per $dataDisplay<br>";
                        $righeCsv[] = [$dataIso, 'TXT', basename($nomeFileTxt), 'Ricevuta testuale non firmata'];
                    } else {
                        echo "🟡 TXT già presente per $dataDisplay — skip<br>";
                    }
                } else {
                    $corruptPath = $savePath . "/_corrupt_ricevuta_" . $data->format('Ymd');
                    file_put_contents($corruptPath . ".b64.txt", $pdfBase64);
                    file_put_contents($corruptPath . ".bin", $decoded);
                    echo "❗ Ricevuta per $dataDisplay NON valida (formato ignoto)<br>";
                    $righeCsv[] = [$dataIso, 'Corrotta', '-', 'Formato sconosciuto. Base64 salvato'];
                }
            } else {
                echo "ℹ️ Nessuna ricevuta disponibile per $dataDisplay<br>";
                $righeCsv[] = [$dataIso, 'Non trovata', '-', 'Nessuna ricevuta disponibile'];
            }

        } catch (SoapFault $e) {
            echo "❗ Errore SOAP per $dataDisplay: " . $e->getMessage() . "<br>";
            $righeCsv[] = [$dataIso, 'Errore SOAP', '-', $e->getMessage()];
        }
    }

    // === LOG FINALE: DEDUPLICATO E ORDINATO ===
    if (!empty($righeCsv)) {
        $tutteRighe = [];

        // Leggi righe esistenti
        $header = ['Data', 'Esito', 'File', 'Note'];
        if (file_exists($logCsv) && is_readable($logCsv)) {
            $fpOld = fopen($logCsv, 'r');
            if ($fpOld !== false) {
                $readHeader = fgetcsv($fpOld); // intestazione
                if ($readHeader) {
                    $header = $readHeader;
                }
                while (($row = fgetcsv($fpOld)) !== false) {
                    $tutteRighe[] = $row;
                }
                fclose($fpOld);
            }
        }

        // Aggiungi nuove righe
        $tutteRighe = array_merge($tutteRighe, $righeCsv);

        // Deduplica per data → tiene l'ultima (PDF > TXT > altro)
        $deduplicato = [];
        foreach ($tutteRighe as $riga) {
            $dataKey = $riga[0];
            $deduplicato[$dataKey] = $riga;
        }

        // Ordina per data desc
        uksort($deduplicato, function($a, $b) {
            return strcmp($b, $a);
        });

        // Scrivi log nuovo
        $fp = fopen($logCsv, 'w');
        if ($fp === false) {
            die("❌ Impossibile scrivere il log: $logCsv");
        }
        fputcsv($fp, $header);
        foreach ($deduplicato as $riga) {
            fputcsv($fp, $riga);
        }
        fclose($fp);

        echo "<br>📝 Log aggiornato e ripulito in: $logCsv<br>";
    } else {
        echo "<br>✅ Nessuna nuova ricevuta da loggare.<br>";
    }

    echo "✅ Download ricevute completato.<br>";
}


// === CARICA LE RIGHE DAL FILE TXT ===
$txtFileFlagged = $path . "/polstat_flagged.txt";


// Se il file normale non esiste, errore
if (!file_exists($txtFile)) {
    die("❌ File non trovato: $txtFile\n");
}

$schedine = file($txtFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$schedine) {
    die("❌ Il file è vuoto o non leggibile.\n");
}

// === CONTROLLO E LOG SCHEDINE INVIATE (con auto-pulizia vecchi hash) ===
$logHashFile = $path . "/schedine_inviate_log.csv";
$hashInviate = [];

// Carica e filtra hash validi (ultimi 30 giorni)
if (file_exists($logHashFile)) {
    $lines = file($logHashFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $nuoveRighe = [];
    $oggi = new DateTime();

    foreach ($lines as $line) {
        [$dataStr, $hash] = explode(',', $line);
        $data = DateTime::createFromFormat('Y-m-d', trim($dataStr));
        if ($data !== false && $oggi->diff($data)->days <= 30) {
            $hashInviate[] = $hash;
            $nuoveRighe[] = trim($dataStr) . "," . trim($hash);
        }
    }

    // Sovrascrive il file con solo hash validi
    file_put_contents($logHashFile, implode("\n", $nuoveRighe) . "\n");
}

// Calcola hash delle schedine attuali
$schedine = file($txtFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$schedine) {
    die("❌ Il file è vuoto o non leggibile.\n");
}
$hashCorrente = md5(implode("\n", $schedine));

// Se hash già presente → non inviare
$nessun_invio=0; ///NOTA BENE questo deve essere 0 altrimenti non invierà mai le schedine mettere uno solo per saltare l'invio(test)
if (in_array($hashCorrente, $hashInviate)) {
    die("✅ Le schedine sono già state inviate in precedenza (entro 30 giorni).\n");
	$nessun_invio=1;
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
	
if ($nessun_invio==0){// le schedine sono da inviare

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
      // ✅ Rinomina il file con timestamp + progressivo (es. polstat_20250603_flagged_1.txt)
      $timestamp = date('Ymd');
      $index = 1;
      do {
        $txtFileFlagged = $path . "/polstat_{$timestamp}_flagged_{$index}.txt";
        $index++;
      } while (file_exists($txtFileFlagged));

      $renamed = rename($txtFile, $txtFileFlagged);
      if ($renamed) {
        echo "<br>🏁 File rinominato a: $txtFileFlagged (inviato con successo)\n";
      } else {
        echo "<br>⚠️ ATTENZIONE: invio riuscito, ma non è stato possibile rinominare il file.\n";
      }

      // Salva hash invio nel log
      file_put_contents($logHashFile, date('Y-m-d') . "," . $hashCorrente . "\n", FILE_APPEND);

      // visto che è tutto ok memorizzo il corretto invio in rental events
      foreach ($testate as $tes){
        $test_row = gaz_dbi_get_row($gTables['rental_events'], 'id_tesbro', intval($tes), " AND `type` = 'ALLOGGIO'" );
        $valore=$test_row['status_webservice'];
        if ($test_row['status_webservice']==0){// nessuno
          $valore=2; // solo polizia
        }elseif ($test_row['status_webservice']==1){// era già solo istat
          $valore=3; // entrambi
        }elseif ($test_row['status_webservice']==2){// era solo polizia

        }

        $query = "UPDATE `".$gTables['rental_events']."` SET `status_webservice` = '$valore' WHERE `id_tesbro` = ".intval($tes)." AND `type` = 'ALLOGGIO'";
        if (gaz_dbi_query($query, true)){
        }else{
          echo "<br>❌ C'è stato un errore nell'aggiornare la tabella rental_events:<br>";
        }
      }
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
