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
  Magazzino facile: situazione in sola lettura degli articoli movimentati,
  divisi in esauriti, sotto scorta minima e regolari, con filtri, esportazione
  CSV e stampa PDF.
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

$flt = array(
  'ricerca' => isset($_REQUEST['ricerca']) ? substr(trim($_REQUEST['ricerca']), 0, 50) : '',
  'fornitore' => isset($_REQUEST['fornitore']) ? intval($_REQUEST['fornitore']) : 0,
  'catmer' => isset($_REQUEST['catmer']) ? intval($_REQUEST['catmer']) : 0,
  'stato' => isset($_REQUEST['stato']) ? (string)$_REQUEST['stato'] : 'tutti',
);
$stati_opts = array('tutti' => 'Tutti', 'esauriti' => 'Esauriti', 'sottoscorta' => 'Sotto scorta minima', 'regolari' => 'Regolari');
if (!isset($stati_opts[$flt['stato']])) {
  $flt['stato'] = 'tutti';
}
$titoli = array(
  'esauriti' => array('Prodotti esauriti', 'danger', array(255, 200, 200)),
  'sottoscorta' => array('Prodotti sotto scorta minima', 'warning', array(255, 240, 190)),
  'regolari' => array('Prodotti con giacenza regolare', 'success', array(210, 240, 210)),
);

$mov = magazzino_facile_movimenti();
$forn_nomi = magazzino_facile_nomi_fornitori($mov['fornitore']);
$catmer_nomi = magazzino_facile_categorie();

// solo gli articoli movimentati (esclusi i servizi)
$sezioni = array('esauriti' => array(), 'sottoscorta' => array(), 'regolari' => array());
$codici = array();
$where_art = "good_or_service <> 1";
if ($flt['ricerca'] !== '') {
  $ric = gaz_dbi_real_escape_string($flt['ricerca']);
  $where_art .= " AND (codice LIKE '%$ric%' OR descri LIKE '%$ric%' OR barcode LIKE '%$ric%')";
}
if ($flt['catmer'] > 0) {
  $where_art .= " AND catmer = " . $flt['catmer'];
}
$visti = array();
$rs = gaz_dbi_query("SELECT codice, descri, unimis, catmer, scorta FROM " . $gTables['artico'] . " WHERE $where_art ORDER BY descri, codice");
while ($a = gaz_dbi_fetch_array($rs)) {
  $cod = $a['codice'];
  if (!isset($mov['stock'][$cod]) || isset($visti[$cod])) {
    continue;
  }
  $visti[$cod] = true;
  if ($flt['fornitore'] > 0 && (!isset($mov['fornitore'][$cod]) || $mov['fornitore'][$cod] != $flt['fornitore'])) {
    continue;
  }
  $g = round($mov['stock'][$cod], 3);
  $scorta = floatval($a['scorta']);
  if ($g <= 0) {
    $sez = 'esauriti';
  } elseif ($scorta > 0 && $g <= $scorta) {
    $sez = 'sottoscorta';
  } else {
    $sez = 'regolari';
  }
  if ($flt['stato'] !== 'tutti' && $flt['stato'] !== $sez) {
    continue;
  }
  $sezioni[$sez][] = array(
    'categoria' => isset($catmer_nomi[$a['catmer']]) ? $catmer_nomi[$a['catmer']] : '',
    'codice' => $cod,
    'descri' => $a['descri'],
    'fornitore' => (isset($mov['fornitore'][$cod]) && isset($forn_nomi[$mov['fornitore'][$cod]])) ? $forn_nomi[$mov['fornitore'][$cod]] : '',
    'unimis' => $a['unimis'],
    'giacenza' => $g,
    'scorta' => $scorta,
  );
  $codici[] = $cod;
}
$lotinfo = magazzino_facile_info_lotti($codici, $mov['lotti']);

$export = isset($_GET['export']) ? $_GET['export'] : '';

// esportazione CSV per Excel (prima di qualsiasi output html)
if ($export == 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="situazione_magazzino_' . date('Ymd') . '.csv"');
  $out = fopen('php://output', 'w');
  fputs($out, "\xEF\xBB\xBF"); // BOM per Excel
  fputcsv($out, array('Stato', 'Categoria', 'Codice', 'Descrizione', 'Fornitore', 'U.M.', 'Q.ta disponibile', 'Scorta minima'), ';', '"', '');
  foreach ($titoli as $sez => $t) {
    foreach ($sezioni[$sez] as $r) {
      fputcsv($out, array($t[0], $r['categoria'], $r['codice'], $r['descri'], $r['fornitore'], $r['unimis'],
        number_format($r['giacenza'], 3, ',', ''), ($r['scorta'] > 0 ? number_format($r['scorta'], 3, ',', '') : '')), ';', '"', '');
    }
  }
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
  $title = array('luogo_data' => $luogo_data,
    'title' => 'Situazione di magazzino - ' . $stati_opts[$flt['stato']],
    'hile' => array(
      array('lun' => 32, 'nam' => 'Categoria'),
      array('lun' => 20, 'nam' => 'Cod.'),
      array('lun' => 62, 'nam' => 'Descrizione'),
      array('lun' => 36, 'nam' => 'Fornitore'),
      array('lun' => 10, 'nam' => 'U.M.'),
      array('lun' => 13, 'nam' => 'Disp.'),
      array('lun' => 13, 'nam' => 'Scorta'),
    )
  );
  $pdf = new Report_template();
  $pdf->setVars($admin_aziend, $title);
  $pdf->setFooterMargin(15);
  $pdf->setTopMargin(43);
  $pdf->setRiporti('');
  $pdf->AddPage();
  foreach ($titoli as $sez => $t) {
    if ($flt['stato'] !== 'tutti' && $flt['stato'] !== $sez) {
      continue;
    }
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor($t[2][0], $t[2][1], $t[2][2]);
    $pdf->Cell(186, 5, $t[0] . ' - ' . count($sezioni[$sez]) . ' articoli', 1, 1, 'L', 1);
    $pdf->SetFont('helvetica', '', 7);
    foreach ($sezioni[$sez] as $r) {
      $pdf->SetTextColor($r['giacenza'] < 0 ? 180 : 0, 0, 0);
      $pdf->Cell(32, 4, $r['categoria'], 1, 0, 'L', 0, '', 1);
      $pdf->Cell(20, 4, $r['codice'], 1, 0, 'L', 0, '', 1);
      $pdf->Cell(62, 4, $r['descri'], 1, 0, 'L', 0, '', 1);
      $pdf->Cell(36, 4, $r['fornitore'], 1, 0, 'L', 0, '', 1);
      $pdf->Cell(10, 4, $r['unimis'], 1, 0, 'C');
      $pdf->Cell(13, 4, gaz_format_quantity($r['giacenza']), 1, 0, 'R');
      $pdf->Cell(13, 4, ($r['scorta'] > 0 ? gaz_format_quantity($r['scorta']) : '-'), 1, 1, 'R');
      magazzino_facile_pdf_lotti($pdf, $r['codice'], $mov['lotti'], $lotinfo);
    }
    $pdf->SetTextColor(0);
    $pdf->Ln(2);
  }
  $pdf->Output('situazione_magazzino_' . date('Ymd') . '.pdf', 'I');
  exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain();

echo "<form method=\"GET\" name=\"sitfacile\" action=\"situazione_facile.php\">";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">Situazione facile del magazzino</div>";

echo "<table class=\"Tmiddle table-striped\" align=\"center\"><tr>";
echo "<td class=\"FacetFieldCaptionTD\">Cerca</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"ricerca\" value=\"" . htmlspecialchars($flt['ricerca']) . "\" maxlength=\"50\" size=\"18\" placeholder=\"codice, descrizione, barcode\" /></td>";
echo "<td class=\"FacetFieldCaptionTD\">Categoria</td><td class=\"FacetDataTD\"><select name=\"catmer\"><option value=\"0\">- tutte -</option>";
foreach ($catmer_nomi as $k => $v) {
  echo "<option value=\"" . intval($k) . "\"" . ($flt['catmer'] == $k ? ' selected' : '') . ">" . htmlspecialchars($v) . "</option>";
}
echo "</select></td>";
echo "<td class=\"FacetFieldCaptionTD\">Stato</td><td class=\"FacetDataTD\"><select name=\"stato\">";
foreach ($stati_opts as $k => $v) {
  echo "<option value=\"$k\"" . ($flt['stato'] == $k ? ' selected' : '') . ">" . htmlspecialchars($v) . "</option>";
}
echo "</select></td>";
echo "<td class=\"FacetFieldCaptionTD\">Fornitore</td><td class=\"FacetDataTD\"><select name=\"fornitore\"><option value=\"0\">- tutti -</option>";
$forn_ordinati = $forn_nomi;
asort($forn_ordinati);
foreach ($forn_ordinati as $fc => $fn) {
  echo "<option value=\"" . intval($fc) . "\"" . ($flt['fornitore'] == $fc ? ' selected' : '') . ">" . htmlspecialchars($fn) . "</option>";
}
echo "</select></td>";
echo "<td style=\"white-space:nowrap;\"><input type=\"submit\" class=\"btn btn-primary btn-sm\" value=\"Aggiorna\" /> ";
echo "<a class=\"btn btn-default btn-sm\" href=\"situazione_facile.php?" . htmlspecialchars(http_build_query(array_merge($flt, array('export' => 'csv')))) . "\">Esporta Excel (CSV)</a> ";
echo "<a class=\"btn btn-default btn-sm\" href=\"situazione_facile.php?" . htmlspecialchars(http_build_query(array_merge($flt, array('export' => 'pdf')))) . "\" target=\"_blank\">Stampa PDF</a></td>";
echo "</tr></table>";

echo "<div class=\"table-responsive\"><table class=\"Tlarge table table-bordered table-condensed\">";
echo "<tr class=\"FacetFieldCaptionTD\"><td>Categoria</td><td>Cod.</td><td>Descrizione</td><td>Fornitore</td><td>U.M.</td><td align=\"right\">Q.t&agrave; disponibile</td><td align=\"right\">Scorta minima</td><td>Azioni</td></tr>";
$tot_mostrati = 0;
foreach ($titoli as $sez => $t) {
  if ($flt['stato'] !== 'tutti' && $flt['stato'] !== $sez) {
    continue;
  }
  echo "<tr><td colspan=\"8\" class=\"alert alert-" . $t[1] . "\" style=\"font-weight:bold;\">" . $t[0] . " &mdash; " . count($sezioni[$sez]) . " articoli</td></tr>";
  foreach ($sezioni[$sez] as $r) {
    $stile = ($r['giacenza'] < 0) ? ' style="color:#a94442;font-weight:bold;"' : '';
    $urlcod = urlencode($r['codice']);
    echo "<tr class=\"FacetDataTD\">";
    echo "<td>" . htmlspecialchars($r['categoria']) . "</td>";
    echo "<td>" . htmlspecialchars($r['codice']) . "</td>";
    echo "<td>" . htmlspecialchars($r['descri']) . "</td>";
    echo "<td>" . htmlspecialchars($r['fornitore']) . "</td>";
    echo "<td>" . htmlspecialchars($r['unimis']) . "</td>";
    echo "<td align=\"right\"$stile>" . gaz_format_quantity($r['giacenza']) . "</td>";
    echo "<td align=\"right\">" . ($r['scorta'] > 0 ? gaz_format_quantity($r['scorta']) : '-') . "</td>";
    echo "<td style=\"white-space:nowrap;\">"
      . "<a class=\"btn btn-xs btn-default\" title=\"Movimenti di magazzino dell'articolo\" href=\"movimenti_articolo.php?codice=$urlcod\" target=\"_blank\"><i class=\"glyphicon glyphicon-list-alt\"></i> Movimenti</a> "
      . "<a class=\"btn btn-xs btn-warning\" title=\"Rettifica la giacenza\" href=\"rettifica_giacenza.php?codice=$urlcod\" target=\"_blank\"><i class=\"glyphicon glyphicon-wrench\"></i> Rettifica</a>"
      . "</td>";
    echo "</tr>";
    magazzino_facile_riga_lotti($r['codice'], $mov['lotti'], $lotinfo, 8);
    $tot_mostrati++;
  }
}
if ($tot_mostrati == 0) {
  echo "<tr><td colspan=\"8\" class=\"alert alert-warning\">Nessun articolo movimentato trovato con questi filtri.</td></tr>";
}
echo "</table></div>";
echo "</form>";
require("../../library/include/footer.php");
?>
