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
  Magazzino facile: funzioni comuni a inventario_facile.php, situazione_facile.php,
  movimenti_articolo.php e rettifica_giacenza.php (modulo magazz).
  Si attiva in Configurazione avanzata azienda con magazzino_facile = 1.
  La giacenza segue le stesse regole di magazzForm::getStockValue: l'ultimo
  inventario (causale 99) fa da punto di partenza, poi si sommano i movimenti
  con causale < 99 dei giorni successivi; i movimenti con la stessa data
  dell'inventario non contano.
*/

// true se l'azienda ha attivato il magazzino facile
function magazzino_facile_attivo() {
  global $gTables;
  static $attivo = null;
  if ($attivo === null) {
    $r = gaz_dbi_get_row($gTables['company_config'], 'var', 'magazzino_facile');
    $attivo = ($r && trim($r['val']) == '1');
  }
  return $attivo;
}

function magazzino_facile_avviso_spento() {
  echo '<div class="alert alert-warning text-center">Attiva il magazzino facile in Configurazione avanzata (magazzino_facile = 1)</div>';
}

// data AAAA-MM-GG valida oppure stringa vuota
function magazzino_facile_data($data) {
  $data = trim((string)$data);
  if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data, $p) && checkdate(intval($p[2]), intval($p[3]), intval($p[1]))) {
    return $data;
  }
  return '';
}

/*
  Una sola lettura dei movimenti (anche con molti articoli). Restituisce per articolo:
  stock = giacenza, lotti = residuo per lotto (come lotmag::getAvailableLots),
  fornitore = ultimo fornitore che ha caricato, costo_q/costo_v e vend_q/vend_v
  per i valori medi di acquisto e di vendita.
*/
function magazzino_facile_movimenti($alla_data = '', $codice = null) {
  global $gTables, $admin_aziend;
  $ris = array('stock' => array(), 'lotti' => array(), 'fornitore' => array(),
    'costo_q' => array(), 'costo_v' => array(), 'vend_q' => array(), 'vend_v' => array());
  $forn_min = intval($admin_aziend['masfor']) * 1000000 + 1;
  $forn_max = intval($admin_aziend['masfor']) * 1000000 + 999999;
  $where = array('1');
  $alla_data = magazzino_facile_data($alla_data);
  if ($alla_data !== '') {
    $where[] = "datreg <= '" . $alla_data . "'";
  }
  if ($codice !== null) {
    $where[] = "artico = '" . gaz_dbi_real_escape_string($codice) . "'";
  }
  $data_inv = array();
  $rs = gaz_dbi_query("SELECT artico, caumag, operat, datreg, quanti, prezzo, clfoco, id_lotmag FROM " . $gTables['movmag']
    . " WHERE " . implode(' AND ', $where) . " ORDER BY datreg, id_mov");
  while ($m = gaz_dbi_fetch_array($rs)) {
    $a = $m['artico'];
    $cau = intval($m['caumag']);
    $q = floatval($m['quanti']) * intval($m['operat']);
    if (!isset($ris['stock'][$a])) {
      $ris['stock'][$a] = 0;
    }
    if ($cau == 99) { // inventario: la giacenza riparte dalla quantita' rilevata
      $ris['stock'][$a] = floatval($m['quanti']);
      $data_inv[$a] = $m['datreg'];
      continue;
    }
    if ($cau < 99 && (!isset($data_inv[$a]) || $m['datreg'] != $data_inv[$a])) {
      $ris['stock'][$a] += $q;
    }
    if ($cau < 98 && intval($m['id_lotmag']) > 1) {
      $l = intval($m['id_lotmag']);
      $ris['lotti'][$a][$l] = (isset($ris['lotti'][$a][$l]) ? $ris['lotti'][$a][$l] : 0) + $q;
    }
    if ($cau >= 98) { // i movimenti d'inventario non entrano nei valori medi
      continue;
    }
    $prezzo = floatval($m['prezzo']);
    if ($m['operat'] == 1 && $prezzo > 0) {
      $ris['costo_q'][$a] = (isset($ris['costo_q'][$a]) ? $ris['costo_q'][$a] : 0) + floatval($m['quanti']);
      $ris['costo_v'][$a] = (isset($ris['costo_v'][$a]) ? $ris['costo_v'][$a] : 0) + floatval($m['quanti']) * $prezzo;
    } elseif ($m['operat'] == -1 && $prezzo > 0) {
      $ris['vend_q'][$a] = (isset($ris['vend_q'][$a]) ? $ris['vend_q'][$a] : 0) + floatval($m['quanti']);
      $ris['vend_v'][$a] = (isset($ris['vend_v'][$a]) ? $ris['vend_v'][$a] : 0) + floatval($m['quanti']) * $prezzo;
    }
    if ($m['operat'] == 1 && $m['clfoco'] >= $forn_min && $m['clfoco'] <= $forn_max) {
      $ris['fornitore'][$a] = intval($m['clfoco']); // in ordine di data: vale l'ultimo carico
    }
  }
  return $ris;
}

// giacenza di un solo articolo, eventualmente a una data
function magazzino_facile_giacenza($codice, $alla_data = '') {
  $ris = magazzino_facile_movimenti($alla_data, $codice);
  return isset($ris['stock'][$codice]) ? round($ris['stock'][$codice], 3) : 0;
}

// ragione sociale dei fornitori indicati (codici clfoco)
function magazzino_facile_nomi_fornitori($codici) {
  global $gTables;
  $nomi = array();
  $codici = array_unique(array_map('intval', $codici));
  if (count($codici) > 0) {
    $rs = gaz_dbi_query("SELECT codice, descri FROM " . $gTables['clfoco'] . " WHERE codice IN (" . implode(',', $codici) . ")");
    while ($r = gaz_dbi_fetch_array($rs)) {
      $nomi[$r['codice']] = $r['descri'];
    }
  }
  return $nomi;
}

// categorie merceologiche: codice => descrizione
function magazzino_facile_categorie() {
  global $gTables;
  $cat = array();
  $rs = gaz_dbi_query("SELECT codice, descri FROM " . $gTables['catmer'] . " ORDER BY descri");
  while ($r = gaz_dbi_fetch_array($rs)) {
    $cat[$r['codice']] = $r['descri'];
  }
  return $cat;
}

// dati dei lotti con residuo per gli articoli indicati
function magazzino_facile_info_lotti($codici, $lotti) {
  global $gTables, $admin_aziend;
  $ids = array();
  foreach ($codici as $cod) {
    if (isset($lotti[$cod])) {
      foreach ($lotti[$cod] as $lid => $resto) {
        if (abs($resto) >= 0.001) {
          $ids[] = intval($lid);
        }
      }
    }
  }
  $info = array();
  if (count($ids) > 0) {
    $masfor = intval($admin_aziend['masfor']);
    $rs = gaz_dbi_query("SELECT l.id, l.identifier, l.expiry, m.desdoc, m.datdoc, c.descri AS fornitore FROM " . $gTables['lotmag'] . " l"
      . " LEFT JOIN " . $gTables['movmag'] . " m ON l.id_movmag = m.id_mov"
      . " LEFT JOIN " . $gTables['clfoco'] . " c ON c.codice = m.clfoco AND m.clfoco BETWEEN " . ($masfor * 1000000 + 1) . " AND " . ($masfor * 1000000 + 999999)
      . " WHERE l.id IN (" . implode(',', array_unique($ids)) . ")");
    while ($r = gaz_dbi_fetch_array($rs)) {
      $info[$r['id']] = $r;
    }
  }
  return $info;
}

// riga html con i lotti dell'articolo sotto la riga principale
function magazzino_facile_riga_lotti($codice, $lotti, $info, $colonne) {
  if (!isset($lotti[$codice])) {
    return;
  }
  $out = '';
  foreach ($lotti[$codice] as $lid => $resto) {
    if (abs($resto) < 0.001 || !isset($info[$lid])) {
      continue;
    }
    $l = $info[$lid];
    $out .= '<span class="label ' . ($resto < 0 ? 'label-danger' : 'label-info') . '" style="display:inline-block;margin:1px 4px 1px 0;font-size:90%;font-weight:normal;">';
    $out .= '<i class="glyphicon glyphicon-tag"></i> ID:' . intval($l['id']) . ' lotto ' . htmlspecialchars($l['identifier']);
    if (intval($l['expiry']) > 0) {
      $out .= ' scad:' . gaz_format_date($l['expiry']);
    }
    $out .= ' disp: <b>' . gaz_format_quantity($resto) . '</b>';
    if (!empty($l['desdoc'])) {
      $out .= ' rif: ' . htmlspecialchars($l['desdoc']) . ' - ' . gaz_format_date($l['datdoc']);
    }
    if (!empty($l['fornitore'])) {
      $out .= ' <b>' . htmlspecialchars($l['fornitore']) . '</b>';
    }
    $out .= '</span> ';
  }
  if ($out !== '') {
    echo '<tr><td></td><td colspan="' . ($colonne - 1) . '" style="padding:2px 6px;border-top:0;">' . $out . '</td></tr>';
  }
}

// righe dei lotti nella stampa PDF
function magazzino_facile_pdf_lotti($pdf, $codice, $lotti, $info) {
  if (!isset($lotti[$codice])) {
    return;
  }
  foreach ($lotti[$codice] as $lid => $resto) {
    if (abs($resto) < 0.001 || !isset($info[$lid])) {
      continue;
    }
    $l = $info[$lid];
    $txt = '   ID:' . $l['id'] . ' lotto ' . $l['identifier'];
    if (intval($l['expiry']) > 0) {
      $txt .= ' scad:' . gaz_format_date($l['expiry']);
    }
    $txt .= ' disp: ' . gaz_format_quantity($resto);
    if (!empty($l['desdoc'])) {
      $txt .= ' rif: ' . $l['desdoc'] . ' - ' . gaz_format_date($l['datdoc']);
    }
    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetFont('helvetica', 'I', 6);
    $pdf->Cell(150, 3.5, $txt, 0, 0, 'L', 0, '', 1);
    $pdf->SetFont('helvetica', 'BI', 6);
    $pdf->Cell(36, 3.5, (!empty($l['fornitore']) ? $l['fornitore'] : ''), 0, 1, 'L', 0, '', 1);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(0);
  }
}
