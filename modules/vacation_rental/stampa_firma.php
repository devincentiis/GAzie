<?php
// Recupera il parametro URL
$pdf_path = $_GET['url']; // Es. './files/1/pdf_Lease/151_firma.pdf'

// Sanifica il percorso
$pdf_path = filter_var($pdf_path, FILTER_SANITIZE_URL);

// Verifica se il percorso è valido
if (strpos($pdf_path, '..') !== false || !file_exists($pdf_path)) {
    die('Il PDF della firma non è stato trovato o il percorso è invalido.');
}

// Verifica se il file è un PDF
if (mime_content_type($pdf_path) !== 'application/pdf') {
    die('Il file non è un PDF valido.');
}

// Imposta intestazioni HTTP per la visualizzazione del PDF nel browser
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="firma_contratto_' . $id_tes . '.pdf"');
header('Content-Length: ' . filesize($pdf_path));

// Svuota qualsiasi output precedente al file PDF
ob_clean();
flush();

// Mostra il file
readfile($pdf_path);
exit;
