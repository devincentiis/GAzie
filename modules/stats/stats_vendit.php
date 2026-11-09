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
// Cruscotto delle vendite: fatturato, confronto con l'anno precedente, clienti, articoli e categorie.
// Fonte: righe delle fatture di vendita (tesdoc/rigdoc); imponibile = quantità x prezzo con lo sconto di riga e di testata,
// come CalcolaImportoRigo; le note di credito si sottraggono. Trasporto, spese bancarie e varie non sono comprese.
// Grafici con Chart.js 2.7.2 già presente in js/chartjs. Nessuna tabella o file aggiuntivo.


require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();

$tT = $gTables['tesdoc'];
$tR = $gTables['rigdoc'];
$tA = $gTables['artico'];
$tC = $gTables['catmer'];
$tF = $gTables['clfoco'];
$tN = $gTables['anagra'];

// documenti che fanno fatturato: fatture (FA*: immediate, differite...), note di credito (FNC, in negativo), note di debito (FND)
$svDoc = "(t.tipdoc LIKE 'FA%' OR t.tipdoc IN ('FNC','FND'))";
$svSign = "(CASE WHEN t.tipdoc = 'FNC' THEN -1 ELSE 1 END)";
// imponibile della riga: normale = quantità x prezzo scontato, forfait = prezzo; poi lo sconto di testata
$svAmt = "((CASE WHEN r.tiprig = 0 THEN r.quanti * r.prelis * (1 - r.sconto / 100) WHEN r.tiprig = 1 THEN r.prelis ELSE 0 END) * (1 - IFNULL(t.sconto, 0) / 100) * " . $svSign . ")";
// righe che entrano nel margine: articolo con prezzo d'acquisto plausibile (non oltre 5 volte il prezzo di vendita netto:
// esclude i valori di comodo come 999999 che falserebbero tutto)
$svCostOk = "(r.tiprig = 0 AND a.preacq > 0 AND a.preacq <= 5 * r.prelis * (1 - r.sconto / 100))";

function svRows($sql) {
    $out = array();
    $rs = gaz_dbi_query($sql);
    while ($r = gaz_dbi_fetch_array($rs)) $out[] = $r;
    return $out;
}
function svH($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function svEur($v, $dec = 0) { return '&euro;&nbsp;' . number_format((float) $v, $dec, ',', '.'); }
function svNum($v, $dec = 0) { return number_format((float) $v, $dec, ',', '.'); }
// variazione percentuale con freccia colorata
function svVar($cur, $prev) {
    if (!$prev) return '<span class="sv-var sv-neu">&mdash;</span>';
    $p = ($cur - $prev) / abs($prev) * 100;
    $cls = $p > 0.05 ? 'sv-up' : ($p < -0.05 ? 'sv-down' : 'sv-neu');
    $ico = $p > 0.05 ? 'fa-arrow-up' : ($p < -0.05 ? 'fa-arrow-down' : 'fa-minus');
    return '<span class="sv-var ' . $cls . '"><i class="fa ' . $ico . '"></i> ' . svNum(abs($p), 1) . '%</span>';
}
function svCliUrl($clfoco) { return '../vendit/admin_client.php?codice=' . urlencode(substr((string) $clfoco, 3)) . '&amp;Update'; }

// ---------------------------------------------------------------- filtri
$years = array();
foreach (svRows("SELECT DISTINCT YEAR(t.datfat) y FROM " . $tT . " t WHERE " . $svDoc . " AND t.datfat > '1990-01-01' ORDER BY y DESC") as $r) $years[] = intval($r['y']);
$today = new DateTime('today');
$thisYear = intval($today->format('Y'));
if (!in_array($thisYear, $years)) array_unshift($years, $thisYear);
$year = (isset($_GET['anno']) && in_array(intval($_GET['anno']), $years)) ? intval($_GET['anno']) : $thisYear;
$sezList = array();
foreach (svRows("SELECT DISTINCT t.seziva s FROM " . $tT . " t WHERE " . $svDoc . " ORDER BY s") as $r) $sezList[] = intval($r['s']);
$sez = (isset($_GET['sez']) && in_array(intval($_GET['sez']), $sezList)) ? intval($_GET['sez']) : 0;
$svSez = $sez ? " AND t.seziva = " . $sez : '';

// periodo: anno scelto (quello in corso fino a oggi) e stesso periodo dell'anno prima, per un confronto alla pari
$partial = ($year == $thisYear);
$curFrom = $year . '-01-01';
$curTo = $partial ? $today->format('Y-m-d') : $year . '-12-31';
$prevFrom = ($year - 1) . '-01-01';
$prevTo = $partial ? ($year - 1) . '-' . ($today->format('m-d') == '02-29' ? '02-28' : $today->format('m-d')) : ($year - 1) . '-12-31';
$mesi = array(1 => 'gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic');
$periodLabel = $partial ? '1 gen &ndash; ' . intval($today->format('d')) . ' ' . $mesi[intval($today->format('m'))] : 'anno intero';

// ---------------------------------------------------------------- indicatori del periodo
function svKpi($from, $to) {
    global $tT, $tR, $tA, $svDoc, $svSign, $svAmt, $svSez, $svCostOk;
    $between = " AND t.datfat BETWEEN '" . $from . "' AND '" . $to . "'";
    $k = svRows("SELECT SUM(" . $svAmt . ") imp,
            SUM(CASE WHEN " . $svCostOk . " THEN " . $svAmt . " ELSE 0 END) cov_imp,
            SUM(CASE WHEN " . $svCostOk . " THEN r.quanti * a.preacq * " . $svSign . " ELSE 0 END) cov_cost
        FROM " . $tT . " t JOIN " . $tR . " r ON r.id_tes = t.id_tes LEFT JOIN " . $tA . " a ON a.codice = r.codart AND r.codart <> ''
        WHERE " . $svDoc . $svSez . $between);
    // una fattura differita raccoglie più DDT (più testate con lo stesso protocollo): si conta una volta sola
    $d = svRows("SELECT COUNT(DISTINCT CASE WHEN t.tipdoc <> 'FNC' THEN CONCAT(t.tipdoc, '-', t.seziva, '-', t.protoc) END) inv,
            COUNT(DISTINCT CASE WHEN t.tipdoc = 'FNC' THEN CONCAT(t.seziva, '-', t.protoc) END) nc,
            COUNT(DISTINCT CASE WHEN t.tipdoc <> 'FNC' THEN t.clfoco END) cli
        FROM " . $tT . " t WHERE " . $svDoc . $svSez . $between);
    // clienti nuovi: la loro prima fattura in assoluto cade nel periodo
    $n = svRows("SELECT COUNT(*) nuovi FROM (SELECT t.clfoco, MIN(t.datfat) f FROM " . $tT . " t
        WHERE " . $svDoc . $svSez . " AND t.tipdoc <> 'FNC' AND t.datfat > '1990-01-01' GROUP BY t.clfoco) x WHERE x.f BETWEEN '" . $from . "' AND '" . $to . "'");
    $out = array();
    foreach (array_merge($k[0], $d[0], $n[0]) as $kk => $v) $out[$kk] = (float) $v;
    $out['avg'] = $out['inv'] ? $out['imp'] / $out['inv'] : 0;
    $out['marg'] = $out['cov_imp'] - $out['cov_cost'];
    $out['marg_pct'] = $out['cov_imp'] ? $out['marg'] / $out['cov_imp'] * 100 : 0;
    $out['cov_pct'] = $out['imp'] ? $out['cov_imp'] / $out['imp'] * 100 : 0;
    return $out;
}
$kc = svKpi($curFrom, $curTo);
$kp = svKpi($prevFrom, $prevTo);

// ---------------------------------------------------------------- andamento mensile: anno scelto e anno precedente intero
$monthly = array($year => array_fill(1, 12, 0), $year - 1 => array_fill(1, 12, 0));
foreach (svRows("SELECT YEAR(t.datfat) y, MONTH(t.datfat) m, SUM(" . $svAmt . ") imp FROM " . $tT . " t JOIN " . $tR . " r ON r.id_tes = t.id_tes
        WHERE " . $svDoc . $svSez . " AND t.datfat BETWEEN '" . $prevFrom . "' AND '" . $year . "-12-31' GROUP BY y, m") as $r) {
    $monthly[intval($r['y'])][intval($r['m'])] = round((float) $r['imp'], 2);
}
$lastMonth = $partial ? intval($today->format('m')) : 12;
$curMonths = $cumCur = $cumPrev = array();
$c1 = $c2 = 0;
for ($m = 1; $m <= 12; $m++) {
    $c1 += $monthly[$year][$m];
    $c2 += $monthly[$year - 1][$m];
    $curMonths[] = $m <= $lastMonth ? $monthly[$year][$m] : null;
    $cumCur[] = $m <= $lastMonth ? round($c1, 2) : null;
    $cumPrev[] = round($c2, 2);
}

// ---------------------------------------------------------------- ultimi sei anni
$yearsTrend = array();
for ($y = $year - 5; $y <= $year; $y++) $yearsTrend[$y] = 0;
foreach (svRows("SELECT YEAR(t.datfat) y, SUM(" . $svAmt . ") imp FROM " . $tT . " t JOIN " . $tR . " r ON r.id_tes = t.id_tes
        WHERE " . $svDoc . $svSez . " AND t.datfat BETWEEN '" . ($year - 5) . "-01-01' AND '" . $year . "-12-31' GROUP BY y") as $r) {
    $yearsTrend[intval($r['y'])] = round((float) $r['imp'], 2);
}

// ---------------------------------------------------------------- clienti: migliori e non più fatturati
$cli = svRows("SELECT t.clfoco, MAX(TRIM(CONCAT(IFNULL(n.ragso1, ''), ' ', IFNULL(n.ragso2, '')))) nome,
        SUM(CASE WHEN t.datfat BETWEEN '" . $curFrom . "' AND '" . $curTo . "' THEN " . $svAmt . " ELSE 0 END) cur,
        SUM(CASE WHEN t.datfat BETWEEN '" . $prevFrom . "' AND '" . $prevTo . "' THEN " . $svAmt . " ELSE 0 END) prev
    FROM " . $tT . " t JOIN " . $tR . " r ON r.id_tes = t.id_tes
    LEFT JOIN " . $tF . " f ON f.codice = t.clfoco LEFT JOIN " . $tN . " n ON n.id = f.id_anagra
    WHERE " . $svDoc . $svSez . " AND t.datfat BETWEEN '" . $prevFrom . "' AND '" . $curTo . "' GROUP BY t.clfoco");
foreach ($cli as $i => $c) { $cli[$i]['cur'] = (float) $c['cur']; $cli[$i]['prev'] = (float) $c['prev']; }
$topCli = array_values(array_filter($cli, function ($r) { return $r['cur'] > 0; }));
usort($topCli, function ($a, $b) { return $b['cur'] <=> $a['cur']; });
$topCli = array_slice($topCli, 0, 10);
$lostCli = array_values(array_filter($cli, function ($r) { return $r['prev'] > 0 && $r['cur'] <= 0; }));
usort($lostCli, function ($a, $b) { return $b['prev'] <=> $a['prev']; });
$lostTot = array_sum(array_column($lostCli, 'prev'));
$lostCount = count($lostCli);
$lostCli = array_slice($lostCli, 0, 10);
// concentrazione: quota del fatturato fatta dai primi 10 clienti
$top10Share = $kc['imp'] ? array_sum(array_column($topCli, 'cur')) / $kc['imp'] * 100 : 0;

// ---------------------------------------------------------------- articoli più venduti
$topArt = svRows("SELECT r.codart, MAX(IFNULL(a.descri, r.descri)) descri, SUM(r.quanti * " . $svSign . ") qta, MAX(r.unimis) um,
        SUM(" . $svAmt . ") imp, SUM(CASE WHEN " . $svCostOk . " THEN " . $svAmt . " END) cov_imp,
        SUM(CASE WHEN " . $svCostOk . " THEN r.quanti * a.preacq * " . $svSign . " END) cost
    FROM " . $tT . " t JOIN " . $tR . " r ON r.id_tes = t.id_tes LEFT JOIN " . $tA . " a ON a.codice = r.codart
    WHERE " . $svDoc . $svSez . " AND r.tiprig = 0 AND r.codart <> '' AND t.datfat BETWEEN '" . $curFrom . "' AND '" . $curTo . "'
    GROUP BY r.codart HAVING imp > 0 ORDER BY imp DESC LIMIT 10");

// ---------------------------------------------------------------- categorie merceologiche
$cats = svRows("SELECT IFNULL(c.codice, 0) cod, MAX(IFNULL(c.descri, '')) descri, SUM(" . $svAmt . ") imp,
        SUM(CASE WHEN " . $svCostOk . " THEN " . $svAmt . " ELSE 0 END) cov_imp,
        SUM(CASE WHEN " . $svCostOk . " THEN r.quanti * a.preacq * " . $svSign . " ELSE 0 END) cov_cost
    FROM " . $tT . " t JOIN " . $tR . " r ON r.id_tes = t.id_tes
    LEFT JOIN " . $tA . " a ON a.codice = r.codart AND r.codart <> '' LEFT JOIN " . $tC . " c ON c.codice = a.catmer
    WHERE " . $svDoc . $svSez . " AND r.tiprig IN (0, 1) AND t.datfat BETWEEN '" . $curFrom . "' AND '" . $curTo . "'
    GROUP BY cod HAVING imp <> 0 ORDER BY imp DESC");
// nel grafico le prime 7 categorie, le altre raggruppate in "Altre"
$catChart = array('labels' => array(), 'data' => array());
$other = 0;
foreach ($cats as $i => $c) {
    $cats[$i]['label'] = $c['cod'] ? ($c['descri'] !== '' ? $c['descri'] : 'Categoria ' . $c['cod']) : 'Senza categoria / righe libere';
    if ($i < 7) {
        $catChart['labels'][] = $cats[$i]['label'];
        $catChart['data'][] = round((float) $c['imp'], 2);
    } else {
        $other += $c['imp'];
    }
}
if ($other) { $catChart['labels'][] = 'Altre'; $catChart['data'][] = round($other, 2); }

require("../../library/include/header.php");
$script_transl = HeadMain();
$jsFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
$prevY = $year - 1;
?>
<div class="sv-page">
  <div class="sv-head">
    <div class="sv-period"><i class="fa fa-calendar"></i> <b><?php echo $year; ?></b> &middot; <?php echo $periodLabel . ($sez ? ' &middot; sezione IVA ' . $sez : ''); ?>
      <span class="text-muted">&middot; confronto con lo stesso periodo del <?php echo $prevY; ?></span></div>
    <form method="GET" class="sv-filter">
      <label for="sv-anno">Anno</label>
      <select id="sv-anno" name="anno" class="form-control input-sm" onchange="this.form.submit()">
        <?php foreach ($years as $y) echo '<option value="' . $y . '"' . ($y == $year ? ' selected' : '') . '>' . $y . '</option>'; ?>
      </select>
      <?php if (count($sezList) > 1) { ?>
      <label for="sv-sez">Sezione IVA</label>
      <select id="sv-sez" name="sez" class="form-control input-sm" onchange="this.form.submit()">
        <option value="0">Tutte</option>
        <?php foreach ($sezList as $s) echo '<option value="' . $s . '"' . ($s == $sez ? ' selected' : '') . '>' . $s . '</option>'; ?>
      </select>
      <?php } ?>
      <noscript><button type="submit" class="btn btn-sm btn-default">Mostra</button></noscript>
    </form>
  </div>

  <div class="sv-kpis">
    <div class="sv-kpi"><i class="fa fa-eur sv-ico"></i>
      <div class="sv-lbl">Fatturato imponibile</div>
      <div class="sv-val"><?php echo svEur($kc['imp']); ?></div>
      <div class="sv-sub"><?php echo svVar($kc['imp'], $kp['imp']); ?> rispetto a <?php echo svEur($kp['imp']); ?> del <?php echo $prevY; ?></div>
    </div>
    <div class="sv-kpi"><i class="fa fa-file-text-o sv-ico"></i>
      <div class="sv-lbl">Fatture emesse</div>
      <div class="sv-val"><?php echo svNum($kc['inv']); ?></div>
      <div class="sv-sub"><?php echo svVar($kc['inv'], $kp['inv']); ?> &middot; note di credito: <?php echo svNum($kc['nc']); ?></div>
    </div>
    <div class="sv-kpi"><i class="fa fa-calculator sv-ico"></i>
      <div class="sv-lbl">Valore medio fattura</div>
      <div class="sv-val"><?php echo svEur($kc['avg']); ?></div>
      <div class="sv-sub"><?php echo svVar($kc['avg'], $kp['avg']); ?> rispetto a <?php echo svEur($kp['avg']); ?></div>
    </div>
    <div class="sv-kpi"><i class="fa fa-users sv-ico"></i>
      <div class="sv-lbl">Clienti fatturati</div>
      <div class="sv-val"><?php echo svNum($kc['cli']); ?></div>
      <div class="sv-sub"><?php echo svVar($kc['cli'], $kp['cli']); ?> &middot; nuovi: <b><?php echo svNum($kc['nuovi']); ?></b></div>
    </div>
    <div class="sv-kpi" title="Ricavo meno il costo d'acquisto attuale degli articoli venduti, sulle righe con un articolo che ha il prezzo d'acquisto (<?php echo svNum($kc['cov_pct']); ?>% del fatturato)">
      <i class="fa fa-pie-chart sv-ico"></i>
      <div class="sv-lbl">Margine stimato</div>
      <div class="sv-val"><?php echo $kc['cov_imp'] ? svNum($kc['marg_pct'], 1) . '%' : '&mdash;'; ?></div>
      <div class="sv-sub"><?php echo svEur($kc['marg']); ?> &middot; su <?php echo svNum($kc['cov_pct']); ?>% del fatturato</div>
    </div>
  </div>

  <div class="sv-grid">
    <div class="sv-card sv-w8">
      <h4>Fatturato mensile <small><?php echo $year; ?> e <?php echo $prevY; ?></small>
        <label class="sv-cum"><input type="checkbox" id="sv-cum"> progressivo</label></h4>
      <div class="sv-body"><div class="sv-chart"><canvas id="sv-month"></canvas></div></div>
    </div>
    <div class="sv-card sv-w4">
      <h4>Fatturato per categoria <small><?php echo $periodLabel; ?></small></h4>
      <div class="sv-body"><?php echo $catChart['data'] ? '<div class="sv-chart"><canvas id="sv-cat"></canvas></div>' : '<div class="sv-empty">Nessuna vendita nel periodo</div>'; ?></div>
    </div>

    <div class="sv-card sv-w6">
      <h4>Migliori clienti <small>i primi 10 fanno il <?php echo svNum($top10Share); ?>% del fatturato</small></h4>
      <div class="sv-body">
        <?php if ($topCli) { ?>
        <table class="sv-tab">
          <tr><th>Cliente</th><th class="r">Fatturato</th><th class="r"><?php echo $prevY; ?></th><th class="r">Var.</th></tr>
          <?php $maxCli = $topCli[0]['cur'];
          foreach ($topCli as $c) {
            $name = $c['nome'] !== '' ? $c['nome'] : $c['clfoco'];
            echo '<tr><td class="sv-name"><a href="' . svCliUrl($c['clfoco']) . '" title="' . svH($name) . '">' . svH($name) . '</a>'
              . '<div class="sv-bar"><span style="width:' . round($c['cur'] / $maxCli * 100) . '%"></span></div></td>'
              . '<td class="r">' . svEur($c['cur']) . '</td><td class="r text-muted">' . ($c['prev'] ? svEur($c['prev']) : '&mdash;') . '</td><td class="r">' . svVar($c['cur'], $c['prev']) . '</td></tr>';
          } ?>
        </table>
        <?php } else echo '<div class="sv-empty">Nessuna vendita nel periodo</div>'; ?>
      </div>
    </div>
    <div class="sv-card sv-w6">
      <h4>Articoli più venduti <small>per fatturato</small></h4>
      <div class="sv-body">
        <?php if ($topArt) { ?>
        <table class="sv-tab">
          <tr><th>Articolo</th><th class="r">Quantità</th><th class="r">Fatturato</th><th class="r" title="Margine stimato con il prezzo d'acquisto attuale">Margine</th></tr>
          <?php $maxArt = (float) $topArt[0]['imp'];
          foreach ($topArt as $a) {
            $marg = ($a['cost'] !== null && (float) $a['cov_imp']) ? ((float) $a['cov_imp'] - (float) $a['cost']) / (float) $a['cov_imp'] * 100 : null;
            $q = (float) $a['qta'];
            echo '<tr><td class="sv-name"><a href="../magazz/admin_artico.php?Update&amp;codice=' . urlencode($a['codart']) . '" title="' . svH($a['codart'] . ' - ' . $a['descri']) . '">' . svH($a['descri']) . '</a>'
              . ' <small class="text-muted">' . svH($a['codart']) . '</small><div class="sv-bar"><span style="width:' . round((float) $a['imp'] / $maxArt * 100) . '%"></span></div></td>'
              . '<td class="r">' . svNum($q, floor($q) == $q ? 0 : 2) . ' ' . svH($a['um']) . '</td>'
              . '<td class="r">' . svEur($a['imp']) . '</td><td class="r">' . ($marg === null ? '<span class="text-muted">&mdash;</span>' : svNum($marg, 1) . '%') . '</td></tr>';
          } ?>
        </table>
        <?php } else echo '<div class="sv-empty">Nessun articolo venduto nel periodo</div>'; ?>
      </div>
    </div>

    <div class="sv-card sv-w6">
      <h4>Clienti non più fatturati <small>fatturati nel <?php echo $prevY; ?> (<?php echo $periodLabel; ?>), non nel <?php echo $year; ?></small></h4>
      <div class="sv-body">
        <?php if ($lostCli) { ?>
        <p class="sv-hint"><?php echo svNum($lostCount); ?> clienti, che nel <?php echo $prevY; ?> avevano fatturato <?php echo svEur($lostTot); ?>.</p>
        <table class="sv-tab">
          <tr><th>Cliente</th><th class="r">Fatturato <?php echo $prevY; ?></th></tr>
          <?php foreach ($lostCli as $c) {
            $name = $c['nome'] !== '' ? $c['nome'] : $c['clfoco'];
            echo '<tr><td class="sv-name"><a href="' . svCliUrl($c['clfoco']) . '" title="' . svH($name) . '">' . svH($name) . '</a></td><td class="r">' . svEur($c['prev']) . '</td></tr>';
          } ?>
        </table>
        <?php } else echo '<div class="sv-empty">Nessuno: tutti i clienti dell\'anno prima sono stati fatturati anche quest\'anno</div>'; ?>
      </div>
    </div>
    <div class="sv-card sv-w6">
      <h4>Andamento negli anni <small>fatturato imponibile<?php echo $partial ? ', ' . $year . ' fino a oggi' : ''; ?></small></h4>
      <div class="sv-body"><div class="sv-chart sv-chart-sm"><canvas id="sv-years"></canvas></div></div>
    </div>

    <?php if ($cats) { ?>
    <div class="sv-card sv-w12">
      <h4>Dettaglio per categoria merceologica <small><?php echo $year . ' &middot; ' . $periodLabel; ?></small></h4>
      <div class="sv-body">
        <table class="sv-tab">
          <tr><th>Categoria</th><th class="r">Fatturato</th><th class="r">Quota</th><th class="r" title="Margine stimato con il prezzo d'acquisto attuale, sulle righe con articolo e prezzo d'acquisto">Margine stimato</th></tr>
          <?php foreach ($cats as $c) {
            $m = (float) $c['cov_imp'] ? ((float) $c['cov_imp'] - (float) $c['cov_cost']) / (float) $c['cov_imp'] * 100 : null;
            $q = $kc['imp'] ? (float) $c['imp'] / $kc['imp'] * 100 : 0;
            echo '<tr><td>' . svH($c['label']) . '<div class="sv-bar"><span style="width:' . max(0, round($q)) . '%"></span></div></td><td class="r">' . svEur($c['imp'], 2) . '</td><td class="r">' . svNum($q, 1) . '%</td>'
              . '<td class="r">' . ($m === null ? '<span class="text-muted">&mdash;</span>' : svNum($m, 1) . '%') . '</td></tr>';
          } ?>
        </table>
      </div>
    </div>
    <?php } ?>
  </div>
  <p class="sv-note"><i class="fa fa-info-circle"></i> Imponibile delle righe delle fatture di vendita (immediate e differite) meno le note di credito, con gli sconti di riga e di testata; trasporto, spese bancarie e varie non sono compresi.
    Il confronto con il <?php echo $prevY; ?> usa lo stesso periodo (<?php echo $periodLabel; ?>). Il margine è una stima: usa il prezzo d'acquisto attuale degli articoli, non quello del momento della vendita, ed esclude le righe senza articolo o con un prezzo d'acquisto assente o anomalo (oltre 5 volte il prezzo di vendita).</p>
</div>
<script src="../../js/chartjs/2.7.2/Chart.bundle.min.js"></script>
<script>
(function () {
  if (typeof Chart === 'undefined') return;
  var y = <?php echo $year; ?>;
  var months = <?php echo json_encode(array_values($mesi), $jsFlags); ?>;
  var cur = <?php echo json_encode($curMonths); ?>, prev = <?php echo json_encode(array_values($monthly[$prevY])); ?>;
  var cumCur = <?php echo json_encode($cumCur); ?>, cumPrev = <?php echo json_encode($cumPrev); ?>;
  var eur = function (v, d) { return '€ ' + Number(v || 0).toLocaleString('it-IT', {minimumFractionDigits: d || 0, maximumFractionDigits: d || 0}); };
  var short = function (v) { v = Number(v); return Math.abs(v) >= 1000 ? '€ ' + (v / 1000).toLocaleString('it-IT', {maximumFractionDigits: 1}) + 'k' : eur(v); };
  var blue = '#3c8dbc', grey = '#b8c2cc';
  Chart.defaults.global.defaultFontFamily = '"Source Sans Pro","Helvetica Neue",Helvetica,Arial,sans-serif';
  Chart.defaults.global.defaultFontColor = '#777';
  var money = { callbacks: { label: function (t, d) { return d.datasets[t.datasetIndex].label + ': ' + eur(t.yLabel, 2); } } };
  var yMoney = [{ ticks: { beginAtZero: true, callback: short }, gridLines: { color: '#f0f2f4' } }];
  var xPlain = [{ gridLines: { display: false } }];

  var mChart = new Chart(document.getElementById('sv-month'), {
    type: 'bar',
    data: { labels: months, datasets: [
      { type: 'line', label: String(y - 1), data: prev, borderColor: grey, backgroundColor: 'transparent', borderWidth: 2, pointRadius: 3, pointBackgroundColor: grey, lineTension: 0.25, fill: false },
      { label: String(y), data: cur, backgroundColor: blue, borderWidth: 0 }
    ] },
    options: { maintainAspectRatio: false, legend: { position: 'bottom' }, tooltips: money, scales: { yAxes: yMoney, xAxes: xPlain } }
  });
  // progressivo: somme dall'inizio dell'anno, per vedere se si è avanti o indietro rispetto all'anno prima
  document.getElementById('sv-cum').addEventListener('change', function () {
    mChart.data.datasets[0].data = this.checked ? cumPrev : prev;
    mChart.data.datasets[1].data = this.checked ? cumCur : cur;
    mChart.update();
  });

  var catEl = document.getElementById('sv-cat');
  if (catEl) {
    new Chart(catEl, {
      type: 'doughnut',
      data: { labels: <?php echo json_encode($catChart['labels'], $jsFlags); ?>, datasets: [{ data: <?php echo json_encode($catChart['data']); ?>,
        backgroundColor: ['#3c8dbc', '#00a65a', '#f39c12', '#dd4b39', '#605ca8', '#39cccc', '#d81b60', '#b8c2cc'], borderWidth: 1 }] },
      options: { maintainAspectRatio: false, cutoutPercentage: 60, legend: { position: 'bottom', labels: { boxWidth: 12, fontSize: 11 } },
        tooltips: { callbacks: { label: function (t, d) {
          var v = d.datasets[0].data[t.index], tot = d.datasets[0].data.reduce(function (a, b) { return a + b; }, 0);
          return d.labels[t.index] + ': ' + eur(v) + ' (' + (tot ? (v / tot * 100).toLocaleString('it-IT', {maximumFractionDigits: 1}) : 0) + '%)';
        } } } }
    });
  }

  var yrs = <?php echo json_encode(array_map('strval', array_keys($yearsTrend))); ?>, yv = <?php echo json_encode(array_values($yearsTrend)); ?>;
  new Chart(document.getElementById('sv-years'), {
    type: 'bar',
    data: { labels: yrs, datasets: [{ label: 'Fatturato', data: yv, backgroundColor: yrs.map(function (l) { return l === String(y) ? blue : grey; }), borderWidth: 0 }] },
    options: { maintainAspectRatio: false, legend: { display: false }, tooltips: money, scales: { yAxes: yMoney, xAxes: xPlain } }
  });
})();
</script>
<?php
require("../../library/include/footer.php");
?>
