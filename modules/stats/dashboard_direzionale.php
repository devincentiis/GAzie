<?php
/*
  Dashboard direzionale: riepilogo del giorno o di un periodo con confronti (periodo precedente, stesse date
  dell'anno prima, stessa settimana degli anni passati), incassi per strumento, esposizione crediti/debiti,
  andamento giornaliero, top clienti/articoli/fornitori, esportazione CSV e PDF (Report_template).
  Fonti: tesdoc+rigdoc (documenti), tesbro+rigbro (ordini), paymov+rigmoc+tesmov (incassi/pagamenti),
  classe Schedule (esposizione). Clienti e fornitori sono riconosciuti dai mastri della configurazione
  dell'azienda (mascli, masfor). Nata come personalizzazione per un grossista ortofrutticolo (2026).
*/
require_once("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();

// conti di un mastro (es. mastro 103 = tutti i conti 103xxxxxx), come condizione SQL su un campo
function dash_mastro($campo, $mastro, $dentro = true) {
   $mastro = intval($mastro);
   return $campo . ($dentro ? '' : ' NOT') . ' BETWEEN ' . ($mastro * 1000000 + 1) . ' AND ' . ($mastro * 1000000 + 999999);
}
function dash_e_fornitore($codice) {
   global $admin_aziend;
   return intval(substr(strval($codice), 0, 3)) == intval($admin_aziend['masfor']);
}

// ---------- periodo richiesto ----------
$oggi = date('Y-m-d');
$preset = isset($_REQUEST['preset']) ? $_REQUEST['preset'] : 'oggi';
$d1 = isset($_REQUEST['d1']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['d1']) ? $_REQUEST['d1'] : '';
$d2 = isset($_REQUEST['d2']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['d2']) ? $_REQUEST['d2'] : '';
if ($d1 !== '' && $d2 !== '') {
   $preset = 'custom';
} else {
   switch ($preset) {
      case 'ieri': $d1 = $d2 = date('Y-m-d', strtotime('-1 day')); break;
      case 'settimana': $d1 = date('Y-m-d', strtotime('monday this week')); $d2 = $oggi; break;
      case 'mese': $d1 = date('Y-m-01'); $d2 = $oggi; break;
      case 'mese_scorso': $d1 = date('Y-m-01', strtotime('first day of last month')); $d2 = date('Y-m-t', strtotime('last day of last month')); break;
      case 'anno': $d1 = date('Y-01-01'); $d2 = $oggi; break;
      default: $preset = 'oggi'; $d1 = $d2 = $oggi; break;
   }
}
if ($d2 < $d1) { $t = $d1; $d1 = $d2; $d2 = $t; }

// ---------- metriche di un periodo ----------
function dash_metrics($gTables, $da, $a) {
   global $admin_aziend;
   $m = array('ddt_n' => 0, 'ddt_val' => 0, 'fat_n' => 0, 'fat_val' => 0, 'acq_n' => 0, 'acq_val' => 0,
      'ord_n' => 0, 'ord_val' => 0, 'incassato' => 0, 'pagato' => 0, 'margine' => 0, 'clienti_serviti' => 0);
   // documenti di vendita e acquisto (valori ivati dalle righe)
   $rs = gaz_dbi_query("SELECT t.id_tes, t.tipdoc, t.clfoco,
         SUM(r.quanti*r.prelis*(1-r.sconto/100)*(1+r.pervat/100)) AS ivato,
         SUM(CASE WHEN a.codice IS NOT NULL THEN (r.prelis*(1-r.sconto/100) - a.last_cost)*r.quanti ELSE 0 END) AS margine
      FROM " . $gTables['tesdoc'] . " t
      JOIN " . $gTables['rigdoc'] . " r ON r.id_tes = t.id_tes AND r.tiprig = 0
      LEFT JOIN " . $gTables['artico'] . " a ON a.codice = r.codart
      WHERE t.datemi BETWEEN '$da' AND '$a'
      GROUP BY t.id_tes");
   $clienti = array();
   while ($r = gaz_dbi_fetch_array($rs)) {
      $v = floatval($r['ivato']);
      if (dash_e_fornitore($r['clfoco'])) {
         $m['acq_n']++; $m['acq_val'] += $v;
      } elseif ($r['tipdoc'] == 'DDT') {
         $m['ddt_n']++; $m['ddt_val'] += $v; $m['margine'] += floatval($r['margine']);
         $clienti[$r['clfoco']] = 1;
      } elseif (substr($r['tipdoc'], 0, 2) == 'FA' || $r['tipdoc'] == 'VRI') {
         $m['fat_n']++; $m['fat_val'] += $v; $m['margine'] += floatval($r['margine']);
         $clienti[$r['clfoco']] = 1;
      } elseif ($r['tipdoc'] == 'FNC') {
         $m['fat_n']++; $m['fat_val'] -= $v;
      }
   }
   $m['clienti_serviti'] = count($clienti);
   // ordini da cliente (brogliacci VOR/VOG/VOW)
   $rs = gaz_dbi_query("SELECT COUNT(DISTINCT t.id_tes) AS n, COALESCE(SUM(r.quanti*r.prelis*(1-r.sconto/100)*(1+r.pervat/100)),0) AS v
      FROM " . $gTables['tesbro'] . " t
      JOIN " . $gTables['rigbro'] . " r ON r.id_tes = t.id_tes AND r.tiprig = 0
      WHERE t.tipdoc IN ('VOR','VOG','VOW') AND t.datemi BETWEEN '$da' AND '$a'");
   if ($r = gaz_dbi_fetch_array($rs)) { $m['ord_n'] = intval($r['n']); $m['ord_val'] = floatval($r['v']); }
   // pagato: chiusure di partite fornitori registrate in prima nota nel periodo
   $rs = gaz_dbi_query("SELECT
         SUM(CASE WHEN " . dash_mastro('rm.codcon', $admin_aziend['masfor']) . " THEN p.amount ELSE 0 END) AS pagato
      FROM " . $gTables['paymov'] . " p
      JOIN " . $gTables['rigmoc'] . " rm ON rm.id_rig = p.id_rigmoc_pay
      JOIN " . $gTables['tesmov'] . " tm ON tm.id_tes = rm.id_tes
      WHERE p.id_rigmoc_pay > 0 AND tm.datreg BETWEEN '$da' AND '$a'");
   if ($r = gaz_dbi_fetch_array($rs)) {
      $m['pagato'] = floatval($r['pagato']);
   }
   // incassato: tutti gli incassi da clienti (anche acconti senza partita), somma della tabella per strumento
   foreach (dash_incassi_strumenti($gTables, $da, $a) as $s) { $m['incassato'] += $s['importo']; }
   return $m;
}

// ---------- incassi da clienti per strumento (25/11/2026) ----------
// Lo strumento e' il "conto d'accredito" scelto registrando il pagamento (vendit/customer_payment.php, pulsante euro
// di report_credit): i conti del mastro cassa (Cassa contanti, Bancomat / carte, Assegni, Satispay) e delle banche.
// Un incasso = riga in DARE su quel conto in un movimento che ha un cliente (103xxxxxx) in AVERE, per data di registrazione.
function dash_conti_incasso() {
   global $gTables, $admin_aziend;
   $where = array();
   foreach (array(intval(substr($admin_aziend['cassa_'], 0, 3)), intval($admin_aziend['masban'])) as $mas) {
      if ($mas > 0) { $where[] = "(codice > " . ($mas * 1000000) . " AND codice <= " . ($mas * 1000000 + 999999) . ")"; }
   }
   $conti = array();
   if (count($where) == 0) { return $conti; }
   $rs = gaz_dbi_query("SELECT codice, descri FROM " . $gTables['clfoco'] . " WHERE " . implode(' OR ', $where) . " ORDER BY codice");
   while ($r = gaz_dbi_fetch_array($rs)) { $conti[intval($r['codice'])] = $r['descri']; }
   return $conti;
}

function dash_incassi_strumenti($gTables, $da, $a) {
   global $admin_aziend;
   static $conti = null;
   if ($conti === null) { $conti = dash_conti_incasso(); }
   $out = array();
   foreach ($conti as $cod => $nome) { $out[$cod] = array('nome' => $nome, 'importo' => 0, 'n' => 0); }
   if (count($conti) == 0) { return $out; }
   $rs = gaz_dbi_query("SELECT rm.codcon, SUM(rm.import) AS importo, COUNT(*) AS n
      FROM " . $gTables['rigmoc'] . " rm
      JOIN " . $gTables['tesmov'] . " tm ON tm.id_tes = rm.id_tes
      WHERE rm.darave = 'D' AND rm.codcon IN (" . implode(',', array_keys($conti)) . ") AND tm.datreg BETWEEN '$da' AND '$a'
        AND EXISTS (SELECT 1 FROM " . $gTables['rigmoc'] . " rc WHERE rc.id_tes = rm.id_tes AND rc.darave = 'A' AND " . dash_mastro('rc.codcon', $admin_aziend['mascli']) . ")
      GROUP BY rm.codcon");
   while ($r = gaz_dbi_fetch_array($rs)) {
      $c = intval($r['codcon']);
      if (isset($out[$c])) { $out[$c]['importo'] = floatval($r['importo']); $out[$c]['n'] = intval($r['n']); }
   }
   return $out;
}

// ---------- esposizione crediti/debiti (fotografia a oggi, indipendente dal periodo) ----------
function dash_esposizione($mastro) {
   $s = new Schedule;
   $s->getPartnerStatus($mastro);
   $tot = 0; $scad = 0;
   foreach ($s->PartnerStatus as $partita) {
      foreach ($partita as $rata) {
         $open = round($rata['op_val'] - $rata['cl_val'], 2);
         if ($open >= 0.01) {
            $tot += $open;
            if ($rata['status'] == 3) { $scad += $open; }
         }
      }
   }
   return array('tot' => $tot, 'scaduto' => $scad);
}

// ---------- andamento giornaliero nel periodo ----------
function dash_giornaliero($gTables, $da, $a) {
   global $admin_aziend;
   $for = dash_mastro('t.clfoco', $admin_aziend['masfor']);
   $g = array();
   $rs = gaz_dbi_query("SELECT t.datemi,
         SUM(CASE WHEN $for THEN 0 ELSE r.quanti*r.prelis*(1-r.sconto/100)*(1+r.pervat/100) END) AS venduto,
         SUM(CASE WHEN $for THEN r.quanti*r.prelis*(1-r.sconto/100)*(1+r.pervat/100) ELSE 0 END) AS acquistato
      FROM " . $gTables['tesdoc'] . " t
      JOIN " . $gTables['rigdoc'] . " r ON r.id_tes = t.id_tes AND r.tiprig = 0
      WHERE t.datemi BETWEEN '$da' AND '$a'
      GROUP BY t.datemi ORDER BY t.datemi");
   while ($r = gaz_dbi_fetch_array($rs)) {
      $g[$r['datemi']] = array('venduto' => floatval($r['venduto']), 'acquistato' => floatval($r['acquistato']));
   }
   return $g;
}

// ---------- top liste ----------
function dash_top($gTables, $da, $a, $tipo) {
   global $admin_aziend;
   $out = array();
   if ($tipo == 'clienti' || $tipo == 'fornitori') {
      $range = dash_mastro('t.clfoco', $admin_aziend['masfor'], $tipo != 'clienti');
      $extra = ($tipo == 'clienti') ? " AND t.tipdoc <> 'FNC'" : "";
      $rs = gaz_dbi_query("SELECT c.descri AS nome, COUNT(DISTINCT t.id_tes) AS n_doc,
            SUM(r.quanti*r.prelis*(1-r.sconto/100)*(1+r.pervat/100)) AS valore
         FROM " . $gTables['tesdoc'] . " t
         JOIN " . $gTables['rigdoc'] . " r ON r.id_tes = t.id_tes AND r.tiprig = 0
         JOIN " . $gTables['clfoco'] . " c ON c.codice = t.clfoco
         WHERE t.datemi BETWEEN '$da' AND '$a' AND $range $extra
         GROUP BY t.clfoco ORDER BY valore DESC LIMIT 10");
   } else { // articoli venduti
      $rs = gaz_dbi_query("SELECT CONCAT(r.codart, ' - ', r.descri) AS nome, SUM(r.quanti) AS n_doc,
            SUM(r.quanti*r.prelis*(1-r.sconto/100)*(1+r.pervat/100)) AS valore
         FROM " . $gTables['tesdoc'] . " t
         JOIN " . $gTables['rigdoc'] . " r ON r.id_tes = t.id_tes AND r.tiprig = 0
         WHERE t.datemi BETWEEN '$da' AND '$a' AND " . dash_mastro('t.clfoco', $admin_aziend['masfor'], false) . "
           AND t.tipdoc <> 'FNC' AND r.codart <> ''
         GROUP BY r.codart ORDER BY valore DESC LIMIT 10");
   }
   while ($r = gaz_dbi_fetch_array($rs)) { $out[] = $r; }
   return $out;
}

// ---------- calcolo di tutto ----------
$ndays = (strtotime($d2) - strtotime($d1)) / 86400 + 1;
$m_cur = dash_metrics($gTables, $d1, $d2);
// periodo precedente di pari durata
$p1 = date('Y-m-d', strtotime($d1 . " -$ndays days"));
$p2 = date('Y-m-d', strtotime($d2 . " -$ndays days"));
$m_prev = dash_metrics($gTables, $p1, $p2);
// stesse date anno precedente
$y1 = date('Y-m-d', strtotime($d1 . ' -1 year'));
$y2 = date('Y-m-d', strtotime($d2 . ' -1 year'));
$m_year = dash_metrics($gTables, $y1, $y2);
// stessa settimana ISO negli anni passati (solo per periodi fino a 7 giorni)
$week_rows = array();
if ($ndays <= 7) {
   $isoW = intval(date('W', strtotime($d1)));
   $isoY = intval(date('o', strtotime($d1)));
   for ($back = 1; $back <= 2; $back++) {
      $wy = $isoY - $back;
      $wts = strtotime(sprintf('%04dW%02d', $wy, $isoW));
      if ($wts === false) { continue; }
      $w1 = date('Y-m-d', $wts);
      $w2 = date('Y-m-d', $wts + 6 * 86400);
      $week_rows["Settimana $isoW/$wy"] = array('d1' => $w1, 'd2' => $w2, 'm' => dash_metrics($gTables, $w1, $w2));
   }
}
$esp_cli = dash_esposizione(intval($admin_aziend['mascli']));
$esp_for = dash_esposizione(intval($admin_aziend['masfor']));
$giorni = dash_giornaliero($gTables, $d1, $d2);
$top_clienti = dash_top($gTables, $d1, $d2, 'clienti');
$top_articoli = dash_top($gTables, $d1, $d2, 'articoli');
$top_fornitori = dash_top($gTables, $d1, $d2, 'fornitori');
$incassi = dash_incassi_strumenti($gTables, $d1, $d2);

function delta_pct($cur, $prev) {
   if (abs($prev) < 0.01) { return ($cur > 0.01) ? '+100%' : '='; }
   $d = ($cur - $prev) / abs($prev) * 100;
   return ($d >= 0 ? '+' : '') . number_format($d, 1, ',', '.') . '%';
}

// ---------- export CSV ----------
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
   header('Content-Type: text/csv; charset=utf-8');
   header('Content-Disposition: attachment; filename="dashboard_' . $d1 . '_' . $d2 . '.csv"');
   $out = fopen('php://output', 'w');
   fputs($out, "\xEF\xBB\xBF");
   fputcsv($out, array('DASHBOARD DIREZIONALE', 'dal ' . $d1 . ' al ' . $d2), ';');
   fputcsv($out, array(''), ';');
   fputcsv($out, array('INDICATORE', 'Periodo attuale', 'Periodo precedente', 'Anno precedente (stesse date)'), ';');
   $kpi_rows = array(
      'Consegnato DDT (n.)' => array($m_cur['ddt_n'], $m_prev['ddt_n'], $m_year['ddt_n']),
      'Consegnato DDT (euro)' => array($m_cur['ddt_val'], $m_prev['ddt_val'], $m_year['ddt_val']),
      'Fatturato (n.)' => array($m_cur['fat_n'], $m_prev['fat_n'], $m_year['fat_n']),
      'Fatturato (euro)' => array($m_cur['fat_val'], $m_prev['fat_val'], $m_year['fat_val']),
      'Acquistato (n.)' => array($m_cur['acq_n'], $m_prev['acq_n'], $m_year['acq_n']),
      'Acquistato (euro)' => array($m_cur['acq_val'], $m_prev['acq_val'], $m_year['acq_val']),
      'Ordini ricevuti (n.)' => array($m_cur['ord_n'], $m_prev['ord_n'], $m_year['ord_n']),
      'Ordini ricevuti (euro)' => array($m_cur['ord_val'], $m_prev['ord_val'], $m_year['ord_val']),
      'Incassato (euro)' => array($m_cur['incassato'], $m_prev['incassato'], $m_year['incassato']),
      'Pagato (euro)' => array($m_cur['pagato'], $m_prev['pagato'], $m_year['pagato']),
      'Margine stimato (euro)' => array($m_cur['margine'], $m_prev['margine'], $m_year['margine']),
      'Clienti serviti (n.)' => array($m_cur['clienti_serviti'], $m_prev['clienti_serviti'], $m_year['clienti_serviti']),
   );
   foreach ($kpi_rows as $k => $v) {
      fputcsv($out, array($k, number_format($v[0], 2, ',', ''), number_format($v[1], 2, ',', ''), number_format($v[2], 2, ',', '')), ';');
   }
   fputcsv($out, array(''), ';');
   fputcsv($out, array('INCASSI PER STRUMENTO', 'N. incassi', 'Importo'), ';');
   foreach ($incassi as $s) {
      fputcsv($out, array($s['nome'], $s['n'], number_format($s['importo'], 2, ',', '')), ';');
   }
   fputcsv($out, array('Totale incassato', '', number_format($m_cur['incassato'], 2, ',', '')), ';');
   fputcsv($out, array(''), ';');
   fputcsv($out, array('ESPOSIZIONE (a oggi)', 'Totale', 'Di cui scaduto'), ';');
   fputcsv($out, array('Crediti verso clienti', number_format($esp_cli['tot'], 2, ',', ''), number_format($esp_cli['scaduto'], 2, ',', '')), ';');
   fputcsv($out, array('Debiti verso fornitori', number_format($esp_for['tot'], 2, ',', ''), number_format($esp_for['scaduto'], 2, ',', '')), ';');
   fputcsv($out, array(''), ';');
   fputcsv($out, array('ANDAMENTO GIORNALIERO', 'Venduto', 'Acquistato'), ';');
   foreach ($giorni as $data => $g) {
      fputcsv($out, array($data, number_format($g['venduto'], 2, ',', ''), number_format($g['acquistato'], 2, ',', '')), ';');
   }
   foreach (array('TOP CLIENTI' => $top_clienti, 'TOP ARTICOLI' => $top_articoli, 'TOP FORNITORI' => $top_fornitori) as $tit => $lista) {
      fputcsv($out, array(''), ';');
      fputcsv($out, array($tit, ($tit == 'TOP ARTICOLI' ? 'Quantita' : 'N.doc'), 'Valore'), ';');
      foreach ($lista as $r) {
         fputcsv($out, array($r['nome'], number_format(floatval($r['n_doc']), 2, ',', ''), number_format(floatval($r['valore']), 2, ',', '')), ';');
      }
   }
   fclose($out);
   exit;
}

// ---------- export PDF ----------
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
   if (!ini_get('safe_mode')) { ini_set('memory_limit', '256M'); gaz_set_time_limit(0); }
   require("../../config/templates/report_template.php");
   $gazTimeFormatter->setPattern('dd MMMM yyyy');
   $luogo_data = $admin_aziend['citspe'] . ", lì " . ucwords($gazTimeFormatter->format(new DateTime()));
   $title = array('luogo_data' => $luogo_data,
      'title' => 'Dashboard direzionale dal ' . gaz_format_date($d1) . ' al ' . gaz_format_date($d2),
      'hile' => array(
         array('lun' => 66, 'nam' => 'Indicatore'),
         array('lun' => 40, 'nam' => 'Periodo attuale'),
         array('lun' => 40, 'nam' => 'Periodo precedente'),
         array('lun' => 40, 'nam' => 'Anno precedente'),
      )
   );
   $pdf = new Report_template();
   $pdf->setVars($admin_aziend, $title);
   $pdf->setFooterMargin(15);
   $pdf->setTopMargin(43);
   $pdf->setRiporti('');
   $pdf->AddPage();
   $pdf->SetFont('helvetica', '', 8);
   $kpi_rows = array(
      'Consegnato DDT' => array($m_cur['ddt_val'], $m_prev['ddt_val'], $m_year['ddt_val'], 'eur', $m_cur['ddt_n']),
      'Fatturato' => array($m_cur['fat_val'], $m_prev['fat_val'], $m_year['fat_val'], 'eur', $m_cur['fat_n']),
      'Acquistato' => array($m_cur['acq_val'], $m_prev['acq_val'], $m_year['acq_val'], 'eur', $m_cur['acq_n']),
      'Ordini ricevuti' => array($m_cur['ord_val'], $m_prev['ord_val'], $m_year['ord_val'], 'eur', $m_cur['ord_n']),
      'Incassato' => array($m_cur['incassato'], $m_prev['incassato'], $m_year['incassato'], 'eur', null),
      'Pagato' => array($m_cur['pagato'], $m_prev['pagato'], $m_year['pagato'], 'eur', null),
      'Margine stimato' => array($m_cur['margine'], $m_prev['margine'], $m_year['margine'], 'eur', null),
      'Clienti serviti' => array($m_cur['clienti_serviti'], $m_prev['clienti_serviti'], $m_year['clienti_serviti'], 'n', null),
   );
   foreach ($kpi_rows as $k => $v) {
      $lbl = $k . ($v[4] !== null ? ' (' . $v[4] . ' doc.)' : '');
      $fmt = function ($x) use ($v) { return $v[3] == 'eur' ? gaz_format_number($x) : strval(intval($x)); };
      $pdf->Cell(66, 5, $lbl, 1, 0, 'L');
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->Cell(40, 5, $fmt($v[0]), 1, 0, 'R');
      $pdf->SetFont('helvetica', '', 8);
      $pdf->Cell(40, 5, $fmt($v[1]) . '  (' . delta_pct($v[0], $v[1]) . ')', 1, 0, 'R');
      $pdf->Cell(40, 5, $fmt($v[2]) . '  (' . delta_pct($v[0], $v[2]) . ')', 1, 1, 'R');
   }
   if (count($incassi) > 0) {
      $pdf->Ln(3);
      $pdf->SetFont('helvetica', 'B', 9);
      $pdf->Cell(186, 5, 'Incassi per strumento', 0, 1, 'L');
      $pdf->SetFont('helvetica', '', 8);
      foreach ($incassi as $s) {
         $pdf->Cell(106, 5, $s['nome'], 1, 0, 'L', 0, '', 1);
         $pdf->Cell(40, 5, $s['n'] > 0 ? $s['n'] . ($s['n'] == 1 ? ' incasso' : ' incassi') : '', 1, 0, 'R');
         $pdf->Cell(40, 5, gaz_format_number($s['importo']), 1, 1, 'R');
      }
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->Cell(146, 5, 'Totale incassato', 1, 0, 'L');
      $pdf->Cell(40, 5, gaz_format_number($m_cur['incassato']), 1, 1, 'R');
      $pdf->SetFont('helvetica', '', 8);
   }
   $pdf->Ln(3);
   $pdf->SetFont('helvetica', 'B', 9);
   $pdf->Cell(186, 5, 'Esposizione a oggi', 0, 1, 'L');
   $pdf->SetFont('helvetica', '', 8);
   $pdf->Cell(66, 5, 'Crediti verso clienti', 1, 0, 'L');
   $pdf->Cell(40, 5, gaz_format_number($esp_cli['tot']), 1, 0, 'R');
   $pdf->SetTextColor(180, 0, 0);
   $pdf->Cell(80, 5, 'di cui scaduto: ' . gaz_format_number($esp_cli['scaduto']), 1, 1, 'R');
   $pdf->SetTextColor(0);
   $pdf->Cell(66, 5, 'Debiti verso fornitori', 1, 0, 'L');
   $pdf->Cell(40, 5, gaz_format_number($esp_for['tot']), 1, 0, 'R');
   $pdf->Cell(80, 5, 'di cui scaduto: ' . gaz_format_number($esp_for['scaduto']), 1, 1, 'R');
   foreach (array('Top clienti' => $top_clienti, 'Top articoli' => $top_articoli, 'Top fornitori' => $top_fornitori) as $tit => $lista) {
      if (count($lista) == 0) { continue; }
      $pdf->Ln(3);
      $pdf->SetFont('helvetica', 'B', 9);
      $pdf->Cell(186, 5, $tit, 0, 1, 'L');
      $pdf->SetFont('helvetica', '', 8);
      foreach ($lista as $r) {
         $pdf->Cell(120, 4, $r['nome'], 1, 0, 'L', 0, '', 1);
         $pdf->Cell(26, 4, gaz_format_quantity($r['n_doc']), 1, 0, 'R');
         $pdf->Cell(40, 4, gaz_format_number($r['valore']), 1, 1, 'R');
      }
   }
   $pdf->setRiporti('');
   $pdf->Output('dashboard_' . $d1 . '_' . $d2 . '.pdf', 'I');
   exit;
}

// ---------- rendering ----------
require("../../library/include/header.php");
$script_transl = HeadMain();

$flt_qs = array('preset' => $preset, 'd1' => $d1, 'd2' => $d2);
?>
<style>
.dash-wrap { max-width: 1280px; margin: 0 auto; padding: 6px; font-family: inherit; }
.dash-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 10px; }
.dash-title { font-size: 22px; font-weight: 700; color: #2c3e50; }
.dash-title small { font-weight: 400; color: #7f8c8d; font-size: 14px; }
.dash-presets a { display: inline-block; padding: 4px 12px; margin: 2px; border-radius: 16px; background: #ecf0f1; color: #2c3e50; text-decoration: none; font-size: 12px; }
.dash-presets a.active { background: #2c3e50; color: #fff; }
.dash-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(215px, 1fr)); gap: 10px; margin-bottom: 12px; }
.dash-card { border-radius: 10px; padding: 12px 14px; color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.12); position: relative; overflow: hidden; }
.dash-card .lbl { font-size: 12px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; }
.dash-card .val { font-size: 24px; font-weight: 700; margin: 2px 0; }
.dash-card .sub { font-size: 11px; opacity: 0.85; }
.dash-card .delta { position: absolute; top: 10px; right: 12px; font-size: 12px; font-weight: 700; background: rgba(255,255,255,0.25); border-radius: 10px; padding: 2px 8px; }
.dc-blue { background: linear-gradient(135deg, #2980b9, #3498db); }
.dc-green { background: linear-gradient(135deg, #27ae60, #2ecc71); }
.dc-orange { background: linear-gradient(135deg, #d35400, #e67e22); }
.dc-purple { background: linear-gradient(135deg, #8e44ad, #9b59b6); }
.dc-teal { background: linear-gradient(135deg, #16a085, #1abc9c); }
.dc-red { background: linear-gradient(135deg, #c0392b, #e74c3c); }
.dc-dark { background: linear-gradient(135deg, #2c3e50, #34495e); }
.dc-gray { background: linear-gradient(135deg, #7f8c8d, #95a5a6); }
.dash-panel { background: #fff; border: 1px solid #e4e7ea; border-radius: 10px; padding: 12px 14px; margin-bottom: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
.dash-panel h4 { margin: 0 0 8px 0; font-size: 14px; font-weight: 700; color: #2c3e50; }
.dash-cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 12px; }
.dash-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.dash-table th { text-align: left; color: #7f8c8d; font-weight: 600; border-bottom: 2px solid #ecf0f1; padding: 4px 6px; }
.dash-table td { padding: 4px 6px; border-bottom: 1px solid #f4f6f7; }
.dash-table td.num, .dash-table th.num { text-align: right; }
.dash-bar { height: 8px; border-radius: 4px; background: #3498db; display: inline-block; vertical-align: middle; }
.d-pos { color: #27ae60; font-weight: 700; } .d-neg { color: #c0392b; font-weight: 700; }
@media print { .no-print { display: none !important; } }
</style>
<div class="dash-wrap">
  <div class="dash-head">
    <div class="dash-title">Dashboard direzionale<br/><small>dal <?php echo gaz_format_date($d1); ?> al <?php echo gaz_format_date($d2); ?> (<?php echo intval($ndays); ?> gg)</small></div>
    <div class="dash-presets no-print">
<?php
$presets = array('oggi' => 'Oggi', 'ieri' => 'Ieri', 'settimana' => 'Settimana', 'mese' => 'Mese', 'mese_scorso' => 'Mese scorso', 'anno' => 'Anno');
foreach ($presets as $k => $v) {
   echo '<a href="dashboard_direzionale.php?preset=' . $k . '" class="' . ($preset == $k ? 'active' : '') . '">' . $v . '</a>';
}
?>
      <form method="GET" style="display:inline-block;margin-left:6px;">
        <input type="date" name="d1" value="<?php echo $d1; ?>" style="font-size:12px;">
        <input type="date" name="d2" value="<?php echo $d2; ?>" style="font-size:12px;">
        <input type="submit" class="btn btn-primary btn-xs" value="Vai">
      </form>
      <a class="btn btn-default btn-xs" href="dashboard_direzionale.php?<?php echo http_build_query(array_merge($flt_qs, array('export' => 'csv'))); ?>">CSV</a>
      <a class="btn btn-default btn-xs" href="dashboard_direzionale.php?<?php echo http_build_query(array_merge($flt_qs, array('export' => 'pdf'))); ?>" target="_blank">PDF</a>
    </div>
  </div>

  <div class="dash-grid">
    <div class="dash-card dc-blue"><span class="delta"><?php echo delta_pct($m_cur['ddt_val'], $m_prev['ddt_val']); ?></span>
      <div class="lbl">Consegnato (DDT)</div><div class="val">&euro; <?php echo gaz_format_number($m_cur['ddt_val']); ?></div>
      <div class="sub"><?php echo $m_cur['ddt_n']; ?> documenti &middot; prec: &euro; <?php echo gaz_format_number($m_prev['ddt_val']); ?></div></div>
    <div class="dash-card dc-green"><span class="delta"><?php echo delta_pct($m_cur['fat_val'], $m_prev['fat_val']); ?></span>
      <div class="lbl">Fatturato</div><div class="val">&euro; <?php echo gaz_format_number($m_cur['fat_val']); ?></div>
      <div class="sub"><?php echo $m_cur['fat_n']; ?> fatture &middot; prec: &euro; <?php echo gaz_format_number($m_prev['fat_val']); ?></div></div>
    <div class="dash-card dc-orange"><span class="delta"><?php echo delta_pct($m_cur['acq_val'], $m_prev['acq_val']); ?></span>
      <div class="lbl">Acquistato</div><div class="val">&euro; <?php echo gaz_format_number($m_cur['acq_val']); ?></div>
      <div class="sub"><?php echo $m_cur['acq_n']; ?> documenti &middot; prec: &euro; <?php echo gaz_format_number($m_prev['acq_val']); ?></div></div>
    <div class="dash-card dc-purple"><span class="delta"><?php echo delta_pct($m_cur['ord_val'], $m_prev['ord_val']); ?></span>
      <div class="lbl">Ordini ricevuti</div><div class="val">&euro; <?php echo gaz_format_number($m_cur['ord_val']); ?></div>
      <div class="sub"><?php echo $m_cur['ord_n']; ?> ordini</div></div>
  </div>
  <div class="dash-grid">
    <div class="dash-card dc-teal"><span class="delta"><?php echo delta_pct($m_cur['incassato'], $m_prev['incassato']); ?></span>
      <div class="lbl">Incassato</div><div class="val">&euro; <?php echo gaz_format_number($m_cur['incassato']); ?></div>
      <div class="sub">prec: &euro; <?php echo gaz_format_number($m_prev['incassato']); ?></div></div>
    <div class="dash-card dc-gray"><span class="delta"><?php echo delta_pct($m_cur['pagato'], $m_prev['pagato']); ?></span>
      <div class="lbl">Pagato</div><div class="val">&euro; <?php echo gaz_format_number($m_cur['pagato']); ?></div>
      <div class="sub">prec: &euro; <?php echo gaz_format_number($m_prev['pagato']); ?></div></div>
    <div class="dash-card dc-red">
      <div class="lbl">Crediti verso clienti</div><div class="val">&euro; <?php echo gaz_format_number($esp_cli['tot']); ?></div>
      <div class="sub">di cui scaduto: &euro; <?php echo gaz_format_number($esp_cli['scaduto']); ?></div></div>
    <div class="dash-card dc-dark">
      <div class="lbl">Debiti verso fornitori</div><div class="val">&euro; <?php echo gaz_format_number($esp_for['tot']); ?></div>
      <div class="sub">di cui scaduto: &euro; <?php echo gaz_format_number($esp_for['scaduto']); ?> &middot; Margine stimato periodo: &euro; <?php echo gaz_format_number($m_cur['margine']); ?></div></div>
  </div>

<?php
// ---- incassi per strumento (conto d'accredito scelto nel pagamento del cliente)
if (count($incassi) > 0) {
   echo '<div class="dash-panel"><h4>Incassi per strumento</h4><table class="dash-table" style="max-width:640px;">';
   echo '<tr><th>Strumento</th><th class="num">N. incassi</th><th class="num">Importo</th></tr>';
   foreach ($incassi as $s) {
      echo '<tr><td>' . htmlspecialchars($s['nome']) . '</td><td class="num">' . ($s['n'] > 0 ? $s['n'] : '') . '</td>';
      echo '<td class="num"><b>' . gaz_format_number($s['importo']) . '</b></td></tr>';
   }
   echo '<tr><td><b>Totale incassato</b></td><td></td><td class="num"><b>&euro; ' . gaz_format_number($m_cur['incassato']) . '</b></td></tr>';
   echo '</table><div style="font-size:11px;color:#7f8c8d;margin-top:4px;">Pagamenti dei clienti registrati nel periodo (pulsante &euro; in &laquo;Partite aperte clienti&raquo;), divisi per conto d\'accredito.</div></div>';
}

// ---- grafico giornaliero SVG (solo se piu' di un giorno con dati)
if (count($giorni) >= 1) {
   $maxv = 0.01;
   foreach ($giorni as $g) { $maxv = max($maxv, $g['venduto'], $g['acquistato']); }
   $n = count($giorni);
   $W = 1200; $H = 220; $pad = 30;
   $bw = max(4, min(40, intval(($W - 2 * $pad) / max(1, $n) / 2.4)));
   echo '<div class="dash-panel"><h4>Andamento giornaliero &mdash; <span style="color:#3498db;">&#9632; Venduto</span> &nbsp;<span style="color:#e67e22;">&#9632; Acquistato</span></h4>';
   echo '<div style="overflow-x:auto;"><svg viewBox="0 0 ' . $W . ' ' . $H . '" style="width:100%;min-width:600px;height:auto;">';
   $i = 0;
   foreach ($giorni as $data => $g) {
      $x = $pad + $i * (($W - 2 * $pad) / max(1, $n));
      $hv = round(($g['venduto'] / $maxv) * ($H - 60));
      $ha = round(($g['acquistato'] / $maxv) * ($H - 60));
      echo '<rect x="' . $x . '" y="' . ($H - 30 - $hv) . '" width="' . $bw . '" height="' . $hv . '" rx="2" fill="#3498db"><title>' . $data . ' venduto: ' . number_format($g['venduto'], 2) . '</title></rect>';
      echo '<rect x="' . ($x + $bw + 2) . '" y="' . ($H - 30 - $ha) . '" width="' . $bw . '" height="' . $ha . '" rx="2" fill="#e67e22"><title>' . $data . ' acquistato: ' . number_format($g['acquistato'], 2) . '</title></rect>';
      echo '<text x="' . ($x + $bw) . '" y="' . ($H - 14) . '" font-size="10" fill="#7f8c8d" text-anchor="middle">' . substr($data, 8, 2) . '/' . substr($data, 5, 2) . '</text>';
      $i++;
   }
   echo '<line x1="' . $pad . '" y1="' . ($H - 30) . '" x2="' . ($W - $pad) . '" y2="' . ($H - 30) . '" stroke="#bdc3c7" stroke-width="1"/>';
   echo '</svg></div></div>';
}

// ---- tabella confronti
echo '<div class="dash-panel"><h4>Confronti</h4><table class="dash-table">';
echo '<tr><th>Periodo</th><th>Date</th><th class="num">Consegnato</th><th class="num">Fatturato</th><th class="num">Acquistato</th><th class="num">Incassato</th><th class="num">Pagato</th><th class="num">Margine</th><th class="num">&Delta; venduto</th></tr>';
$conf_rows = array(
   'Periodo attuale' => array('d1' => $d1, 'd2' => $d2, 'm' => $m_cur),
   'Periodo precedente' => array('d1' => $p1, 'd2' => $p2, 'm' => $m_prev),
   'Anno precedente (stesse date)' => array('d1' => $y1, 'd2' => $y2, 'm' => $m_year),
);
foreach ($week_rows as $k => $v) { $conf_rows[$k] = $v; }
$base_venduto = $m_cur['ddt_val'] + $m_cur['fat_val'];
foreach ($conf_rows as $nome => $cr) {
   $mm = $cr['m'];
   $venduto_r = $mm['ddt_val'] + $mm['fat_val'];
   $delta = ($nome == 'Periodo attuale') ? '' : delta_pct($base_venduto, $venduto_r);
   $dcls = (strpos($delta, '+') === 0) ? 'd-pos' : ((strpos($delta, '-') === 0) ? 'd-neg' : '');
   echo '<tr><td><b>' . $nome . '</b></td><td>' . gaz_format_date($cr['d1']) . ' - ' . gaz_format_date($cr['d2']) . '</td>';
   echo '<td class="num">' . gaz_format_number($mm['ddt_val']) . '</td>';
   echo '<td class="num">' . gaz_format_number($mm['fat_val']) . '</td>';
   echo '<td class="num">' . gaz_format_number($mm['acq_val']) . '</td>';
   echo '<td class="num">' . gaz_format_number($mm['incassato']) . '</td>';
   echo '<td class="num">' . gaz_format_number($mm['pagato']) . '</td>';
   echo '<td class="num">' . gaz_format_number($mm['margine']) . '</td>';
   echo '<td class="num ' . $dcls . '">' . $delta . '</td></tr>';
}
echo '</table><div style="font-size:11px;color:#7f8c8d;margin-top:4px;">&Delta; venduto = variazione del periodo attuale (consegnato+fatturato) rispetto alla riga.</div></div>';

// ---- top liste
echo '<div class="dash-cols">';
$top_defs = array(
   array('Top clienti', $top_clienti, 'N.doc'),
   array('Top articoli venduti', $top_articoli, 'Q.t&agrave;'),
   array('Top fornitori', $top_fornitori, 'N.doc'),
);
foreach ($top_defs as $td) {
   list($tit, $lista, $collbl) = $td;
   echo '<div class="dash-panel"><h4>' . $tit . '</h4><table class="dash-table">';
   echo '<tr><th>#</th><th>Nome</th><th class="num">' . $collbl . '</th><th class="num">Valore</th><th></th></tr>';
   $maxtop = 0.01;
   foreach ($lista as $r) { $maxtop = max($maxtop, floatval($r['valore'])); }
   $pos = 1;
   foreach ($lista as $r) {
      $wbar = max(2, round(floatval($r['valore']) / $maxtop * 70));
      echo '<tr><td>' . $pos . '</td><td>' . htmlspecialchars(mb_substr($r['nome'], 0, 42)) . '</td>';
      echo '<td class="num">' . gaz_format_quantity($r['n_doc']) . '</td>';
      echo '<td class="num"><b>' . gaz_format_number($r['valore']) . '</b></td>';
      echo '<td style="width:76px;"><span class="dash-bar" style="width:' . $wbar . 'px;"></span></td></tr>';
      $pos++;
   }
   if (count($lista) == 0) { echo '<tr><td colspan="5" style="color:#95a5a6;">Nessun dato nel periodo.</td></tr>'; }
   echo '</table></div>';
}
echo '</div>';
?>
</div>
<?php
require("../../library/include/footer.php");
