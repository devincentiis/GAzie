<?php
// visualizza_ricevute.php — Consultazione ricevute alloggiati con selezione struttura dinamica
$path = isset($_GET['path']) ? urldecode($_GET['path']) : '';
$basePath = __DIR__;
$percorsoRicevute = "$path/ricevute_alloggiati";
$perPagina = 30;
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);

$codiceStruttura = isset($_GET['codice_struttura']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['codice_struttura']) : '';
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$filtroMese = isset($_GET['mese']) ? $_GET['mese'] : '';
$cartellaRicevute = null;

function mostraErrore($messaggio) {
    echo "<div class='container mt-5'><div class='alert alert-danger' role='alert'>❌ $messaggio</div></div>";
    exit;
}

// === Trova tutti i codici struttura disponibili ===
$codiciDisponibili = [];
if (is_dir($percorsoRicevute)) {
    foreach (scandir($percorsoRicevute) as $entry) {
        if ($entry !== '.' && $entry !== '..' && is_dir("$percorsoRicevute/$entry")) {
            $codiciDisponibili[] = $entry;
        }
    }
    sort($codiciDisponibili);
} else {
    mostraErrore("Cartella ricevute_alloggiati non trovata.");
}

// === Se c'è una sola struttura e non è stato selezionato manualmente, la selezioniamo automaticamente
if (count($codiciDisponibili) === 1 && !$codiceStruttura) {
    $codiceStruttura = $codiciDisponibili[0];
}

// === Se codice struttura non selezionato, mostra solo il form ===
if (!$codiceStruttura) {
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Seleziona struttura</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
    <div class="container my-5">
        <h2 class="mb-4">📋 Consultazione ricevute alloggiati</h2>
        <form method="GET" class="row gy-2 gx-3 align-items-center">
            <div class="col-md-6">
                <label for="codice_struttura" class="form-label">🏨 Seleziona codice struttura:</label>
                <select name="codice_struttura" id="codice_struttura" class="form-select" required>
                    <option value="">-- seleziona --</option>
                    <?php foreach ($codiciDisponibili as $codice): ?>
                        <option value="<?php echo htmlspecialchars($codice); ?>"><?php echo htmlspecialchars($codice); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="path" value="<?php echo htmlspecialchars($path); ?>">
            </div>
            <div class="col-md-2 mt-4">
                <button type="submit" class="btn btn-primary">Visualizza</button>
            </div>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// === Continua normale se struttura è selezionata ===
$cartellaRicevute = "$percorsoRicevute/$codiceStruttura";
$logFile = "$cartellaRicevute/log_ricevute.csv";

if (!file_exists($logFile)) {
    mostraErrore("Log ricevute non trovato per codice struttura '$codiceStruttura'.");
}

$righe = array_map('str_getcsv', file($logFile));
$intestazione = array_shift($righe);

if ($filtroMese) {
    $righe = array_filter($righe, fn($r) => strpos($r[0], $filtroMese) === 0);
}

$totale = count($righe);
$totPagine = ceil($totale / $perPagina);
$offset = ($pagina - 1) * $perPagina;
$righePagina = array_slice($righe, $offset, $perPagina);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Ricevute - <?php echo htmlspecialchars($codiceStruttura); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h2 class="mb-4">📋 Ricevute per codice struttura <code><?php echo htmlspecialchars($codiceStruttura); ?></code></h2>

    <form method="GET" class="row gy-2 gx-3 align-items-center mb-4">
        <input type="hidden" name="codice_struttura" value="<?php echo htmlspecialchars($codiceStruttura); ?>">
        <input type="hidden" name="path" value="<?php echo htmlspecialchars($path); ?>">
        <div class="col-auto">
            <label class="form-label" for="mese">📅 Mese (es: 2025-06):</label>
            <input type="text" class="form-control" id="mese" name="mese" placeholder="YYYY-MM" value="<?php echo htmlspecialchars($filtroMese); ?>">
        </div>
        <div class="col-auto mt-4">
            <button type="submit" class="btn btn-primary">Filtra</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
            <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>File</th>
                <th>Note</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($righePagina as $riga): ?>
                <?php
                list($data, $tipo, $nomeFile, $note) = $riga;
               $percorsoCompleto = realpath("$cartellaRicevute/$nomeFile");
				if ($nomeFile !== '-' && $percorsoCompleto && strpos($percorsoCompleto, realpath($cartellaRicevute)) === 0) {
					$webPath = str_replace('\\', '/', substr($percorsoCompleto, strlen($documentRoot)));
					$ext = pathinfo($nomeFile, PATHINFO_EXTENSION);
					
					$classeBtn = $ext === 'pdf' ? 'btn-outline-primary' : 'btn-outline-secondary';
					$target = '_blank';
					
					// ✅ Rimuovi "download" per txt per visualizzazione inline
					$downloadAttr = ($ext === 'pdf') ? 'download' : '';
					
					$link = "<a href='" . htmlspecialchars($webPath) . "' class='btn btn-sm $classeBtn' target='$target' $downloadAttr>$nomeFile</a>";
				} else {
					$link = htmlspecialchars($nomeFile);
				}
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($data); ?></td>
                    <td><?php echo htmlspecialchars($tipo); ?></td>
                    <td><?php echo $link; ?></td>
                    <td><?php echo htmlspecialchars($note); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($p = 1; $p <= $totPagine; $p++): ?>
                <li class="page-item <?php echo $p == $pagina ? 'active' : ''; ?>">
                    <a class="page-link"
                       href="?codice_struttura=<?php echo urlencode($codiceStruttura); ?>&path=<?php echo urlencode($path); ?>&pagina=<?php echo $p; ?>&mese=<?php echo urlencode($filtroMese); ?>">
                        <?php echo $p; ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
</body>
</html>
