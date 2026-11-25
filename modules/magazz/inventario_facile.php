<?php
/*
 --------------------------------------------------------------------------
                            GAzie - Gestione Azienda
    Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
         (https://www.devincentiis.it)
           <https://gazie.sourceforge.net>
  Questo file: Copyright (C) 2026 Domenico Seccia - Assistenzacassa (https://assistenzacassa.it)
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
/*
  Magazzino facile: inventario in sola lettura con giacenza e valore di ogni
  articolo, filtri (ricerca, categoria, fornitore, data), totali, esportazione
  CSV e stampa PDF. Da ogni riga si aprono i movimenti dell'articolo e la
  rettifica della giacenza.
*/
require("../../library/include/datlib.inc.php");
require_once("../../library/include/magazzino_facile.inc.php");
$admin_aziend = checkAdmin();

if (!magazzino_facile_attivo()) {
  require("../../library/include/header.php");
  $script_transl = HeadMain();
  magazzino_facile_avviso_spento();
  require("../../library/include/footer.php");
  exit;
}

// filtri
$flt = array(
  'ricerca' => isset($_REQUEST['ricerca']) ? substr(trim($_REQUEST['ricerca']), 0, 50) : '',
  'catmer' => isset($_REQUEST['catmer']) ? intval($_REQUEST['catmer']) : 0,
  'fornitore' => isset($_REQUEST['fornitore']) ? intval($_REQUEST['fornitore']) : 0,
  'valorizza' => isset($_REQUEST['valorizza']) ? (string)$_REQUEST['valorizza'] : 'ultimo_costo',
  'alla_data' => isset($_REQUEST['alla_data']) ? magazzino_facile_data($_REQUEST['alla_data']) : '',
  'anche_zero' => isset($_REQUEST['anche_zero']) ? intval($_REQUEST['anche_zero']) : 0,
);
$valorizza_opts = array(
  'ultimo_costo' => 'Ultimo costo di acquisto',
  'costo_medio' => "Costo medio d'acquisto",
  'vendita_media' => 'Prezzo medio di vendita',
  'listino' => 'Listino di vendita 1',
);
if (!isset($valorizza_opts[$flt['valorizza']])) {
  $flt['valorizza'] = 'ultimo_costo';
}

$mov = magazzino_facile_movimenti($flt['alla_data']);
$forn_nomi = magazzino_facile_nomi_fornitori($mov['fornitore']);
$catmer_nomi = magazzino_facile_categorie();

// articoli (esclusi i servizi) con i filtri scelti
$where_art = "good_or_service <> 1";
if ($flt['ricerca'] !== '') {
  $ric = gaz_dbi_real_escape_string($flt['ricerca']);
  $where_art .= " AND (codice LIKE '%$ric%' OR descri LIKE '%$ric%' OR barcode LIKE '%$ric%')";
}
if ($flt['catmer'] > 0) {
  $where_art .= " AND catmer = " . $flt['catmer'];
}
$righe = array();
$visti = array();
$tot_val = 0;
$tot_qta = 0;
$rs = gaz_dbi_query("SELECT codice, descri, unimis, catmer, last_cost, preve1 FROM " . $gTables['artico'] . " WHERE $where_art ORDER BY descri, codice");
while ($a = gaz_dbi_fetch_array($rs)) {
  $cod = $a['codice'];
  if (isset($visti[$cod])) {
    continue;
  }
  $visti[$cod] = true;
  $g = isset($mov['stock'][$cod]) ? round($mov['stock'][$cod], 3) : 0;
  if (!$flt['anche_zero'] && abs($g) < 0.001) {
    continue;
  }
  if ($flt['fornitore'] > 0 && (!isset($mov['fornitore'][$cod]) || $mov['fornitore'][$cod] != $flt['fornitore'])) {
    continue;
  }
  switch ($flt['valorizza']) {
    case 'costo_medio':
      $vu = (isset($mov['costo_q'][$cod]) && $mov['costo_q'][$cod] > 0) ? $mov['costo_v'][$cod] / $mov['costo_q'][$cod] : 0;
      break;
    case 'vendita_media':
      $vu = (isset($mov['vend_q'][$cod]) && $mov['vend_q'][$cod] > 0) ? $mov['vend_v'][$cod] / $mov['vend_q'][$cod] : 0;
      break;
    case 'listino':
      $vu = floatval($a['preve1']);
      break;
    default:
      $vu = floatval($a['last_cost']);
  }
  $vt = round($g * $vu, 2);
  $righe[] = array(
    'categoria' => isset($catmer_nomi[$a['catmer']]) ? $catmer_nomi[$a['catmer']] : '',
    'codice' => $cod,
    'descri' => $a['descri'],
    'fornitore' => (isset($mov['fornitore'][$cod]) && isset($forn_nomi[$mov['fornitore'][$cod]])) ? $forn_nomi[$mov['fornitore'][$cod]] : '',
    'giacenza' => $g,
    'unimis' => $a['unimis'],
    'vu' => $vu,
    'vt' => $vt,
  );
  $tot_val += $vt;
  $tot_qta += $g;
}
$lotinfo = magazzino_facile_info_lotti(array_column($righe, 'codice'), $mov['lotti']);

$export = isset($_GET['export']) ? $_GET['export'] : '';

// esportazione CSV per Excel (prima di qualsiasi output html)
if ($export == 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="inventario_' . date('Ymd') . '.csv"');
  $out = fopen('php://output', 'w');
  fputs($out, "\xEF\xBB\xBF"); // BOM per Excel
  fputcsv($out, array('Categoria', 'Codice', 'Descrizione', 'Fornitore', 'Giacenza', 'U.M.', 'Valore unitario', 'Valore totale'), ';', '"', '');
  foreach ($righe as $r) {
    fputcsv($out, array($r['categoria'], $r['codice'], $r['descri'], $r['fornitore'],
      number_format($r['giacenza'], 3, ',', ''), $r['unimis'],
      number_format($r['vu'], 5, ',', ''), number_format($r['vt'], 2, ',', '')), ';', '"', '');
  }
  fputcsv($out, array('TOTALE', '', count($righe) . ' articoli', '', number_format($tot_qta, 3, ',', ''), '', '', number_format($tot_val, 2, ',', '')), ';', '"', '');
  fclose($out);
  exit;
}

// stampa PDF con il modello dei report di GAzie
if ($export == 'pdf') {
  if (!ini_get('safe_mode')) {
    ini_set('memory_limit', '256M');
    gaz_set_time_limit(0);
  }
  require("../../config/templates/report_template.php");
  $gazTimeFormatter->setPattern('dd MMMM yyyy');
  $luogo_data = $admin_aziend['citspe'] . ", lì " . ucwords($gazTimeFormatter->format(new DateTime()));
  $sottotitolo = $valorizza_opts[$flt['valorizza']];
  if ($flt['alla_data'] !== '') {
    $sottotitolo .= ' al ' . gaz_format_date($flt['alla_data']);
  }
  $title = array('luogo_data' => $luogo_data,
    'title' => 'Inventario di magazzino - ' . $sottotitolo,
    'hile' => array(
      array('lun' => 28, 'nam' => 'Categoria'),
      array('lun' => 18, 'nam' => 'Cod.'),
      array('lun' => 55, 'nam' => 'Prodotto'),
      array('lun' => 35, 'nam' => 'Fornitore'),
      array('lun' => 16, 'nam' => 'Giacenza'),
      array('lun' => 8, 'nam' => 'U.M.'),
      array('lun' => 13, 'nam' => 'Val.unit.'),
      array('lun' => 13, 'nam' => 'Val.tot.'),
    )
  );
  $pdf = new Report_template();
  $pdf->setVars($admin_aziend, $title);
  $pdf->setFooterMargin(15);
  $pdf->setTopMargin(43);
  $pdf->setRiporti('');
  $pdf->AddPage();
  $pdf->SetFont('helvetica', '', 7);
  foreach ($righe as $r) {
    $pdf->SetTextColor($r['giacenza'] < 0 ? 180 : 0, 0, 0);
    $pdf->Cell(28, 4, $r['categoria'], 1, 0, 'L', 0, '', 1);
    $pdf->Cell(18, 4, $r['codice'], 1, 0, 'L', 0, '', 1);
    $pdf->Cell(55, 4, $r['descri'], 1, 0, 'L', 0, '', 1);
    $pdf->Cell(35, 4, $r['fornitore'], 1, 0, 'L', 0, '', 1);
    $pdf->Cell(16, 4, gaz_format_quantity($r['giacenza']), 1, 0, 'R');
    $pdf->Cell(8, 4, $r['unimis'], 1, 0, 'C');
    $pdf->Cell(13, 4, gaz_format_number($r['vu']), 1, 0, 'R');
    $pdf->Cell(13, 4, gaz_format_number($r['vt']), 1, 1, 'R');
    magazzino_facile_pdf_lotti($pdf, $r['codice'], $mov['lotti'], $lotinfo);
  }
  $pdf->SetTextColor(0);
  $pdf->SetFont('helvetica', 'B', 8);
  $pdf->Cell(101, 5, count($righe) . ' articoli', 1, 0, 'L');
  $pdf->Cell(35, 5, 'TOTALE', 1, 0, 'R');
  $pdf->Cell(16, 5, gaz_format_quantity($tot_qta), 1, 0, 'R');
  $pdf->Cell(8, 5, '', 1, 0, 'C');
  $pdf->Cell(13, 5, '', 1, 0, 'C');
  $pdf->Cell(13, 5, gaz_format_number($tot_val), 1, 1, 'R');
  $pdf->Output('inventario_' . date('Ymd') . '.pdf', 'I');
  exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain();

echo "<form method=\"GET\" name=\"invfacile\" action=\"inventario_facile.php\">";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">Inventario facile &mdash; " . htmlspecialchars($valorizza_opts[$flt['valorizza']]);
if ($flt['alla_data'] !== '') {
  echo " al " . gaz_format_date($flt['alla_data']);
}
echo "</div>";

// filtri
echo "<table class=\"Tmiddle table-striped\" align=\"center\"><tr>";
echo "<td class=\"FacetFieldCaptionTD\">Cerca</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"ricerca\" value=\"" . htmlspecialchars($flt['ricerca']) . "\" maxlength=\"50\" size=\"18\" placeholder=\"codice, descrizione, barcode\" /></td>";
echo "<td class=\"FacetFieldCaptionTD\">Categoria</td><td class=\"FacetDataTD\"><select name=\"catmer\"><option value=\"0\">- tutte -</option>";
foreach ($catmer_nomi as $k => $v) {
  echo "<option value=\"" . intval($k) . "\"" . ($flt['catmer'] == $k ? ' selected' : '') . ">" . htmlspecialchars($v) . "</option>";
}
echo "</select></td>";
echo "<td class=\"FacetFieldCaptionTD\">Fornitore</td><td class=\"FacetDataTD\"><select name=\"fornitore\"><option value=\"0\">- tutti -</option>";
$forn_ordinati = $forn_nomi;
asort($forn_ordinati);
foreach ($forn_ordinati as $fc => $fn) {
  echo "<option value=\"" . intval($fc) . "\"" . ($flt['fornitore'] == $fc ? ' selected' : '') . ">" . htmlspecialchars($fn) . "</option>";
}
echo "</select></td>";
echo "</tr><tr>";
echo "<td class=\"FacetFieldCaptionTD\">Valorizzazione</td><td class=\"FacetDataTD\"><select name=\"valorizza\">";
foreach ($valorizza_opts as $k => $v) {
  echo "<option value=\"$k\"" . ($flt['valorizza'] == $k ? ' selected' : '') . ">" . htmlspecialchars($v) . "</option>";
}
echo "</select></td>";
echo "<td class=\"FacetFieldCaptionTD\">Giacenza alla data</td><td class=\"FacetDataTD\"><input type=\"date\" name=\"alla_data\" value=\"" . htmlspecialchars($flt['alla_data']) . "\" /></td>";
echo "<td class=\"FacetFieldCaptionTD\">Anche a zero</td><td class=\"FacetDataTD\"><input type=\"checkbox\" name=\"anche_zero\" value=\"1\"" . ($flt['anche_zero'] ? ' checked' : '') . " /></td>";
echo "</tr><tr><td colspan=\"6\" align=\"center\">";
echo "<input type=\"submit\" class=\"btn btn-primary btn-sm\" value=\"Aggiorna\" /> &nbsp;";
echo "<a class=\"btn btn-default btn-sm\" href=\"inventario_facile.php?" . htmlspecialchars(http_build_query(array_merge($flt, array('export' => 'csv')))) . "\">Esporta Excel (CSV)</a> &nbsp;";
echo "<a class=\"btn btn-default btn-sm\" href=\"inventario_facile.php?" . htmlspecialchars(http_build_query(array_merge($flt, array('export' => 'pdf')))) . "\" target=\"_blank\">Stampa PDF</a>";
echo "</td></tr></table>";

// elenco
echo "<div class=\"table-responsive\"><table class=\"Tlarge table table-striped table-bordered table-condensed\">";
echo "<tr class=\"FacetFieldCaptionTD\"><td>Categoria</td><td>Cod.</td><td>Prodotto</td><td>Fornitore</td><td align=\"right\">Giacenza</td><td>Azioni</td><td>U.M.</td><td align=\"right\">Valore unit.</td><td align=\"right\">Valore tot.</td></tr>";
foreach ($righe as $r) {
  $stile = ($r['giacenza'] < 0) ? ' style="color:#a94442;font-weight:bold;"' : '';
  $urlcod = urlencode($r['codice']);
  echo "<tr class=\"FacetDataTD\">";
  echo "<td>" . htmlspecialchars($r['categoria']) . "</td>";
  echo "<td>" . htmlspecialchars($r['codice']) . "</td>";
  echo "<td>" . htmlspecialchars($r['descri']) . "</td>";
  echo "<td>" . htmlspecialchars($r['fornitore']) . "</td>";
  echo "<td align=\"right\"$stile>" . gaz_format_quantity($r['giacenza']) . "</td>";
  echo "<td style=\"white-space:nowrap;\">"
    . "<a class=\"btn btn-xs btn-default\" title=\"Movimenti di magazzino dell'articolo\" href=\"movimenti_articolo.php?codice=$urlcod\" target=\"_blank\"><i class=\"glyphicon glyphicon-list-alt\"></i> Movimenti</a> "
    . "<a class=\"btn btn-xs btn-warning\" title=\"Rettifica la giacenza\" href=\"rettifica_giacenza.php?codice=$urlcod\" target=\"_blank\"><i class=\"glyphicon glyphicon-wrench\"></i> Rettifica</a>"
    . "</td>";
  echo "<td>" . htmlspecialchars($r['unimis']) . "</td>";
  echo "<td align=\"right\">&euro; " . gaz_format_number($r['vu']) . "</td>";
  echo "<td align=\"right\"$stile>&euro; " . gaz_format_number($r['vt']) . "</td>";
  echo "</tr>";
  magazzino_facile_riga_lotti($r['codice'], $mov['lotti'], $lotinfo, 9);
}
if (count($righe) == 0) {
  echo "<tr><td colspan=\"9\" class=\"alert alert-warning\">Nessun articolo con giacenza trovato con questi filtri.</td></tr>";
}
echo "<tr class=\"FacetFieldCaptionTD\"><td colspan=\"4\"><b>" . count($righe) . " articoli</b></td>";
echo "<td align=\"right\"><b>" . gaz_format_quantity($tot_qta) . "</b></td><td></td><td></td><td></td>";
echo "<td align=\"right\"><b>&euro; " . gaz_format_number($tot_val) . "</b></td></tr>";
echo "</table></div>";
echo "</form>";
require("../../library/include/footer.php");
?>
