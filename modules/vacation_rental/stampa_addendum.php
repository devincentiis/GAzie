<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
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
    scriva   alla   Free  Software Foundation, 51 Franklin Street,
    Fifth Floor Boston, MA 02110-1335 USA Stati Uniti.
 --------------------------------------------------------------------------
*/
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
include_once("manual_settings.php");
$genTables = constant("table_prefix")."_";
$azTables = constant("table_prefix").$idDB;
$IDaz=preg_replace("/[^1-9]/", "", $azTables );
$directory = __DIR__ . "/files/".$IDaz."/addendum_pdf/".intval($_GET['id_tes']);

// Usa i namespace di TCPDF e FPDI
use setasign\Fpdi\Tcpdf\Fpdi;

// Funzione per ottenere tutti i file PDF da una directory
function getPdfFilesFromDirectory($directory) {
    // Scansione della cartella
    $files = scandir($directory);

    // Filtrare solo i file PDF
    $pdfFiles = array_filter($files, function($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'pdf';
    });

    return $pdfFiles;
}

// Funzione per concatenare i file PDF
function mergePdfs($directory, $pdfFiles) {
    // Crea un oggetto FPDI che estende TCPDF
    $pdf = new Fpdi();
    $pdf->SetAutoPageBreak(true, 0);  // Disabilita il margine inferiore automatico

    // Verifica che ci siano effettivamente file PDF
    if (empty($pdfFiles)) {
        echo "Nessun file PDF trovato.";
        return;
    }

    // Loop attraverso i file PDF
    foreach ($pdfFiles as $file) {
        $filePath = $directory . DIRECTORY_SEPARATOR . $file;

        // Verifica che il file PDF esista
        if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'pdf') {
            // Aggiungi una pagina vuota per iniziare
            // $pdf->AddPage();

            // Aggiungi il PDF al documento corrente
            $pageCount = $pdf->setSourceFile($filePath);

            // Debug: Mostra quante pagine ci sono nel PDF
            //echo "File: $file - Numero di pagine: $pageCount<br>";

            // Copia ogni pagina del PDF nel documento
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplIdx = $pdf->importPage($i);

                // Ottieni le dimensioni della pagina
                $size = $pdf->getTemplateSize($tplIdx);

                // Aggiungi una pagina con le dimensioni corrette
                $pdf->AddPage('P', [$size['width'], $size['height']]);

                // Usa il template importato
                $pdf->useTemplate($tplIdx);

                // Aggiungi una nuova pagina solo se ci sono altre pagine
                if ($i < $pageCount) {
                    $pdf->AddPage();  // Aggiungi una nuova pagina per il prossimo PDF
                }
            }
        } else {
            echo "File $file non trovato o non è un file PDF valido.<br>";
        }
    }

    // Restituisci il PDF generato direttamente al browser
    $pdf->Output('output.pdf', 'I');
}

// Ottieni tutti i file PDF nella directory
$pdfFiles = getPdfFilesFromDirectory($directory);

// Se ci sono file PDF, concatenali e visualizzali
if (!empty($pdfFiles)) {
    mergePdfs($directory, $pdfFiles);
} else {
    echo "Nessun file PDF trovato nella directory.";
}
?>
