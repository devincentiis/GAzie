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
  Lista della spesa: quanto comprare per evadere gli ordini dei clienti.
  Da comprare = ordinato negli ordini clienti (VOR) aperti del periodo, meno
  quanto gia' evaso, meno la giacenza; diviso per fornitore di riferimento
  dell'articolo. Chi compra scrive il costo del giorno: la pagina propone il
  nuovo listino base (costo + ricarico del raggruppamento statistico) e gli
  eventuali prezzi netti dei clienti da adeguare. Prima di salvare mostra il
  riepilogo di cosa cambia e scrive solo dopo la conferma.
  Si attiva in Configurazione avanzata azienda con lista_spesa = 1.
*/
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();

$cfg_ls = gaz_dbi_get_row($gTables['company_config'], 'var', 'lista_spesa');
if (!$cfg_ls || trim($cfg_ls['val']) != '1') {
  require("../../library/include/header.php");
  $script_transl = HeadMain();
  echo '<div class="alert alert-warning text-center">Attiva la lista della spesa in Configurazione avanzata (lista_spesa = 1)</div>';
  require("../../library/include/footer.php");
  exit;
}

$dec_prezzo = intval($admin_aziend['decimal_price']);

// data aaaa-mm-gg valida dal GET, altrimenti quella predefinita
function ls_data($nome, $predef) {
  $v = isset($_GET[$nome]) ? (string) $_GET[$nome] : '';
  if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) && checkdate(intval($m[2]), intval($m[3]), intval($m[1]))) {
    return $v;
  }
  return $predef;
}

// numero scritto dall'utente (accetta la virgola); 0 se vuoto o non valido
function ls_numero($v) {
  $v = trim((string) $v);
  if ($v === '') {
    return 0;
  }
  if (strpos($v, ',') !== false) {
    $v = str_replace(',', '.', str_replace('.', '', $v));
  }
  return is_numeric($v) ? round(floatval($v), 5) : 0;
}

// elenco di codici articolo per una clausola IN
function ls_in($codici) {
  return "'" . implode("','", array_map('gaz_dbi_real_escape_string', $codici)) . "'";
}

$flt = array(
  'dal' => ls_data('dal', date('Y-m-d', strtotime('-1 day'))),
  'al' => ls_data('al', date('Y-m-d')),
  'fornitore' => isset($_GET['fornitore']) ? intval($_GET['fornitore']) : 0,
  // alla prima apertura si vedono solo gli articoli da comprare
  'solo_fabbisogno' => isset($_GET['filtra']) ? (isset($_GET['solo_fabbisogno']) ? 1 : 0) : 1,
);
$qs_flt = array('dal' => $flt['dal'], 'al' => $flt['al'], 'fornitore' => $flt['fornitore'], 'filtra' => 1);
if ($flt['solo_fabbisogno']) {
  $qs_flt['solo_fabbisogno'] = 1;
}

// righe della lista e fornitori presenti
function ls_carica($flt) {
  global $gTables;
  $dal = gaz_dbi_real_escape_string($flt['dal']);
  $al = gaz_dbi_real_escape_string($flt['al']);
  // righi degli ordini clienti non ancora evasi, per ordine e articolo (servizi esclusi)
  $per_ordine = array();
  $rs = gaz_dbi_query("SELECT r.id_tes, r.codart, SUM(r.quanti) AS q FROM " . $gTables['rigbro'] . " r"
    . " JOIN " . $gTables['tesbro'] . " t ON t.id_tes = r.id_tes"
    . " LEFT JOIN " . $gTables['artico'] . " a ON a.codice = r.codart"
    . " WHERE t.tipdoc = 'VOR' AND t.status <> 'EVASO' AND t.datemi BETWEEN '$dal' AND '$al'"
    . " AND r.tiprig = 0 AND r.codart <> '' AND (a.good_or_service IS NULL OR a.good_or_service <> 1)"
    . " GROUP BY r.id_tes, r.codart");
  while ($r = gaz_dbi_fetch_array($rs)) {
    $per_ordine[intval($r['id_tes'])][$r['codart']] = floatval($r['q']);
  }
  // meno le quantita' gia' evase in DDT/fatture (ordini evasi in parte)
  if (count($per_ordine) > 0) {
    $ids = implode(',', array_keys($per_ordine));
    $rs = gaz_dbi_query("SELECT id_order, codart, SUM(quanti) AS q FROM " . $gTables['rigdoc']
      . " WHERE id_order IN ($ids) AND tiprig < 900 AND codart <> '' GROUP BY id_order, codart");
    while ($r = gaz_dbi_fetch_array($rs)) {
      $id = intval($r['id_order']);
      if (isset($per_ordine[$id][$r['codart']])) {
        $per_ordine[$id][$r['codart']] -= floatval($r['q']);
      }
    }
  }
  $ordinato = array();
  $n_ordini = array();
  foreach ($per_ordine as $righi) {
    foreach ($righi as $cod => $q) {
      if ($q <= 0.00001) {
        continue;
      }
      $ordinato[$cod] = (isset($ordinato[$cod]) ? $ordinato[$cod] : 0) + $q;
      $n_ordini[$cod] = (isset($n_ordini[$cod]) ? $n_ordini[$cod] : 0) + 1;
    }
  }
  if (count($ordinato) == 0) {
    return array('righe' => array(), 'fornitori' => array());
  }
  $in = ls_in(array_keys($ordinato));

  // giacenza attuale: come getStockValue, l'ultimo inventario (causale 99) azzera
  // il conto e nel suo giorno contano solo i movimenti successivi
  $stock = array();
  $data_inv = array();
  $rs = gaz_dbi_query("SELECT artico, caumag, operat, quanti, datreg FROM " . $gTables['movmag']
    . " WHERE artico IN ($in) ORDER BY datreg, id_mov");
  while ($m = gaz_dbi_fetch_array($rs)) {
    $a = $m['artico'];
    if (!isset($stock[$a])) {
      $stock[$a] = 0;
    }
    if (intval($m['caumag']) == 99) {
      $stock[$a] = floatval($m['quanti']);
      $data_inv[$a] = $m['datreg'];
      continue;
    }
    if (isset($data_inv[$a]) && $data_inv[$a] == $m['datreg']) {
      continue;
    }
    $stock[$a] += floatval($m['quanti']) * intval($m['operat']);
  }

  // anagrafica, ricarichi dei raggruppamenti statistici, fornitori di riferimento
  $arti = array();
  $rs = gaz_dbi_query("SELECT codice, descri, unimis, last_cost, preve1, ragstat, clfoco FROM " . $gTables['artico'] . " WHERE codice IN ($in)");
  while ($r = gaz_dbi_fetch_array($rs)) {
    $arti[$r['codice']] = $r;
  }
  $ricarichi = array();
  $rs = gaz_dbi_query("SELECT codice, ricarico FROM " . $gTables['ragstat']);
  while ($r = gaz_dbi_fetch_array($rs)) {
    $ricarichi[trim($r['codice'])] = floatval($r['ricarico']);
  }
  $forn_nomi = array();
  $forn_codici = array();
  foreach ($arti as $a) {
    if (intval($a['clfoco']) > 0) {
      $forn_codici[intval($a['clfoco'])] = 1;
    }
  }
  if (count($forn_codici) > 0) {
    $rs = gaz_dbi_query("SELECT codice, descri FROM " . $gTables['clfoco'] . " WHERE codice IN (" . implode(',', array_keys($forn_codici)) . ")");
    while ($r = gaz_dbi_fetch_array($rs)) {
      $forn_nomi[intval($r['codice'])] = $r['descri'];
    }
  }

  // prezzi netti concordati con i singoli clienti (sconti_articoli)
  $accordi = array();
  $rs = gaz_dbi_query("SELECT s.codart, s.clfoco, s.prezzo_netto, c.descri FROM " . $gTables['sconti_articoli'] . " s"
    . " JOIN " . $gTables['clfoco'] . " c ON c.codice = s.clfoco"
    . " WHERE s.codart IN ($in) AND s.prezzo_netto > 0 ORDER BY c.descri");
  while ($r = gaz_dbi_fetch_array($rs)) {
    $accordi[$r['codart']][] = array('clfoco' => intval($r['clfoco']), 'nome' => $r['descri'], 'prezzo' => floatval($r['prezzo_netto']));
  }

  $righe = array();
  foreach ($ordinato as $cod => $q_ord) {
    $a = isset($arti[$cod]) ? $arti[$cod] : null;
    $g = isset($stock[$cod]) ? round($stock[$cod], 3) : 0;
    $da_comprare = max(0, round($q_ord - max(0, $g), 3));
    if ($flt['solo_fabbisogno'] && $da_comprare <= 0) {
      continue;
    }
    $fc = $a ? intval($a['clfoco']) : 0;
    if ($flt['fornitore'] > 0 && $fc != $flt['fornitore']) {
      continue;
    }
    $rag = $a ? trim($a['ragstat']) : '';
    $righe[] = array(
      'codart' => $cod,
      'descri' => $a ? $a['descri'] : $cod . ' (non in anagrafica)',
      'unimis' => $a ? $a['unimis'] : '',
      'ordinato' => $q_ord,
      'n_ordini' => $n_ordini[$cod],
      'giacenza' => $g,
      'da_comprare' => $da_comprare,
      'last_cost' => $a ? floatval($a['last_cost']) : 0,
      'preve1' => $a ? floatval($a['preve1']) : 0,
      'ricarico' => isset($ricarichi[$rag]) ? $ricarichi[$rag] : 0,
      'forn_cod' => isset($forn_nomi[$fc]) ? $fc : 0,
      'fornitore' => isset($forn_nomi[$fc]) ? $forn_nomi[$fc] : 'Senza fornitore di riferimento',
      'accordi' => ($a && isset($accordi[$cod])) ? $accordi[$cod] : array(),
    );
  }
  // per fornitore (quelli senza in fondo), poi per descrizione
  usort($righe, function ($x, $y) {
    if (($x['forn_cod'] == 0) != ($y['forn_cod'] == 0)) {
      return ($x['forn_cod'] == 0) ? 1 : -1;
    }
    $c = strcmp($x['fornitore'], $y['fornitore']);
    return ($c != 0) ? $c : strcmp($x['descri'], $y['descri']);
  });
  return array('righe' => $righe, 'fornitori' => $forn_nomi);
}

// modifiche richieste dal modulo, confrontate con i valori attuali nel database
function ls_modifiche($p) {
  global $gTables;
  $mod = array();
  $art = (isset($p['art']) && is_array($p['art'])) ? $p['art'] : array();
  foreach ($art as $i => $cod) {
    $cod = substr(trim((string) $cod), 0, 32);
    $a = ($cod === '') ? false : gaz_dbi_get_row($gTables['artico'], 'codice', $cod);
    if (!$a) {
      continue;
    }
    $costo = isset($p['costo'][$i]) ? ls_numero($p['costo'][$i]) : 0;
    $costo_rif = floatval($a['last_cost']);
    if ($costo > 0 && abs($costo - $costo_rif) >= 0.00001) {
      $mod[] = array('tipo' => 'costo', 'codart' => $cod, 'descri' => $a['descri'], 'clfoco' => 0, 'nome' => '',
        'da' => $costo_rif, 'a' => $costo, 'costo' => $costo);
      $costo_rif = $costo;
    }
    $base = isset($p['base'][$i]) ? ls_numero($p['base'][$i]) : 0;
    if ($base > 0 && abs($base - floatval($a['preve1'])) >= 0.00001) {
      $mod[] = array('tipo' => 'base', 'codart' => $cod, 'descri' => $a['descri'], 'clfoco' => 0, 'nome' => '',
        'da' => floatval($a['preve1']), 'a' => $base, 'costo' => $costo_rif);
    }
    // prezzi cliente: solo quelli spuntati e gia' esistenti
    $cli = (isset($p['cli'][$i]) && is_array($p['cli'][$i])) ? $p['cli'][$i] : array();
    foreach ($cli as $j => $clfoco) {
      $clfoco = intval($clfoco);
      if ($clfoco <= 0 || empty($p['adegua_c'][$i][$j])) {
        continue;
      }
      $prezzo = isset($p['prezzo_c'][$i][$j]) ? ls_numero($p['prezzo_c'][$i][$j]) : 0;
      $sc = gaz_dbi_get_row($gTables['sconti_articoli'], 'codart', $cod, "AND clfoco = " . $clfoco);
      if ($prezzo <= 0 || !$sc || floatval($sc['prezzo_netto']) <= 0 || abs($prezzo - floatval($sc['prezzo_netto'])) < 0.00001) {
        continue;
      }
      $c = gaz_dbi_get_row($gTables['clfoco'], 'codice', $clfoco);
      $mod[] = array('tipo' => 'cliente', 'codart' => $cod, 'descri' => $a['descri'], 'clfoco' => $clfoco,
        'nome' => $c ? $c['descri'] : (string) $clfoco, 'da' => floatval($sc['prezzo_netto']), 'a' => $prezzo, 'costo' => $costo_rif);
    }
  }
  return $mod;
}

// campi nascosti per ripresentare i valori scritti (chiavi solo numeriche)
function ls_nascosti($nome, $val) {
  $html = '';
  if (is_array($val)) {
    foreach ($val as $k => $v) {
      $html .= ls_nascosti($nome . '[' . intval($k) . ']', $v);
    }
  } else {
    $html .= '<input type="hidden" name="' . $nome . '" value="' . htmlspecialchars((string) $val) . '" />';
  }
  return $html;
}

$campi_modulo = array('art', 'costo', 'base', 'cli', 'prezzo_c', 'adegua_c');
$tipi_modifica = array('costo' => 'Costo di acquisto', 'base' => 'Listino base', 'cliente' => 'Prezzo netto cliente');

// salvataggio confermato: si scrive solo quanto mostrato nel riepilogo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['conferma'])) {
  $mod = ls_modifiche($_POST);
  $n = array('costo' => 0, 'base' => 0, 'cliente' => 0);
  foreach ($mod as $m) {
    $val = number_format($m['a'], 5, '.', '');
    $cod = gaz_dbi_real_escape_string($m['codart']);
    if ($m['tipo'] == 'costo') {
      gaz_dbi_query("UPDATE " . $gTables['artico'] . " SET last_cost = " . $val . " WHERE codice = '" . $cod . "'");
    } elseif ($m['tipo'] == 'base') {
      gaz_dbi_query("UPDATE " . $gTables['artico'] . " SET preve1 = " . $val . " WHERE codice = '" . $cod . "'");
    } else {
      gaz_dbi_query("UPDATE " . $gTables['sconti_articoli'] . " SET prezzo_netto = " . $val
        . " WHERE clfoco = " . intval($m['clfoco']) . " AND codart = '" . $cod . "'");
    }
    $n[$m['tipo']]++;
  }
  header("Location: lista_spesa.php?" . http_build_query($qs_flt + array('salvato' => 1, 'nc' => $n['costo'], 'nb' => $n['base'], 'np' => $n['cliente'])));
  exit;
}

// riepilogo di cosa cambia, da confermare
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['anteprima'])) {
  $mod = ls_modifiche($_POST);
  require("../../library/include/header.php");
  $script_transl = HeadMain();
  echo '<div align="center" class="FacetFormHeaderFont">Lista della spesa &mdash; controlla le modifiche prima di salvarle</div>';
  echo '<form method="POST" action="lista_spesa.php?' . htmlspecialchars(http_build_query($qs_flt)) . '">';
  foreach ($campi_modulo as $c) {
    if (isset($_POST[$c])) {
      echo ls_nascosti($c, $_POST[$c]);
    }
  }
  if (count($mod) == 0) {
    echo '<div class="alert alert-info text-center">Nessuna modifica: non hai scritto costi o prezzi diversi da quelli attuali.</div>';
  } else {
    echo '<div class="table-responsive"><table class="Tlarge table table-bordered table-condensed">';
    echo '<tr class="FacetFieldCaptionTD"><td>Articolo</td><td>Cosa cambia</td><td align="right">Attuale</td><td align="right">Nuovo</td><td align="right">Variazione</td><td>Avviso</td></tr>';
    foreach ($mod as $m) {
      $cosa = $tipi_modifica[$m['tipo']] . ($m['tipo'] == 'cliente' ? ': ' . htmlspecialchars($m['nome']) : '');
      $var = ($m['da'] > 0) ? number_format(($m['a'] - $m['da']) / $m['da'] * 100, 1, ',', '.') . '%' : '-';
      $avviso = ($m['tipo'] != 'costo' && $m['costo'] > 0 && $m['a'] < $m['costo']) ? '<span class="text-danger"><b>sotto costo</b> (' . gaz_format_number($m['costo']) . ')</span>' : '';
      echo '<tr class="FacetDataTD"><td>' . htmlspecialchars($m['descri']) . ' <small>(' . htmlspecialchars($m['codart']) . ')</small></td>'
        . '<td>' . $cosa . '</td>'
        . '<td align="right">' . number_format($m['da'], $dec_prezzo, ',', '.') . '</td>'
        . '<td align="right"><b>' . number_format($m['a'], $dec_prezzo, ',', '.') . '</b></td>'
        . '<td align="right">' . $var . '</td><td>' . $avviso . '</td></tr>';
    }
    echo '</table></div>';
  }
  echo '<div class="text-center" style="margin:10px;">';
  echo '<input type="submit" class="btn btn-default" name="correggi" value="Torna a correggere" /> &nbsp; ';
  if (count($mod) > 0) {
    echo '<input type="submit" class="btn btn-success" name="conferma" value="Conferma e salva ' . count($mod) . ' modifiche" />';
  }
  echo '</div></form>';
  require("../../library/include/footer.php");
  exit;
}

$dati = ls_carica($flt);
$righe = $dati['righe'];

// stampa PDF per il giro di acquisto (colonna vuota per scrivere il costo a penna)
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
  require("../../config/templates/report_template.php");
  $title = array('luogo_data' => $admin_aziend['citspe'] . ", lì " . date('d-m-Y'),
    'title' => 'Lista della spesa - ordini dal ' . gaz_format_date($flt['dal']) . ' al ' . gaz_format_date($flt['al']),
    'hile' => array(
      array('lun' => 18, 'nam' => 'Cod.'),
      array('lun' => 62, 'nam' => 'Prodotto'),
      array('lun' => 18, 'nam' => 'Ordinato'),
      array('lun' => 18, 'nam' => 'Giacenza'),
      array('lun' => 22, 'nam' => 'Da comprare'),
      array('lun' => 10, 'nam' => 'U.M.'),
      array('lun' => 18, 'nam' => 'Costo prec.'),
      array('lun' => 20, 'nam' => 'Costo oggi'),
    )
  );
  $pdf = new Report_template();
  $pdf->setVars($admin_aziend, $title);
  $pdf->setFooterMargin(15);
  $pdf->setTopMargin(43);
  $pdf->setRiporti('');
  $pdf->AddPage();
  $forn_prec = null;
  foreach ($righe as $r) {
    if ($r['fornitore'] !== $forn_prec) {
      $forn_prec = $r['fornitore'];
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetFillColor(235, 235, 235);
      $pdf->Cell(186, 5, $r['fornitore'], 1, 1, 'L', 1);
    }
    $pdf->SetFont('helvetica', '', 7);
    $pdf->Cell(18, 5, $r['codart'], 1, 0, 'L', 0, '', 1);
    $pdf->Cell(62, 5, $r['descri'], 1, 0, 'L', 0, '', 1);
    $pdf->Cell(18, 5, gaz_format_quantity($r['ordinato'], 1, $admin_aziend['decimal_quantity']), 1, 0, 'R');
    $pdf->Cell(18, 5, gaz_format_quantity($r['giacenza'], 1, $admin_aziend['decimal_quantity']), 1, 0, 'R');
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Cell(22, 5, gaz_format_quantity($r['da_comprare'], 1, $admin_aziend['decimal_quantity']), 1, 0, 'R');
    $pdf->SetFont('helvetica', '', 7);
    $pdf->Cell(10, 5, $r['unimis'], 1, 0, 'C');
    $pdf->Cell(18, 5, ($r['last_cost'] > 0 ? gaz_format_number($r['last_cost']) : ''), 1, 0, 'R');
    $pdf->Cell(20, 5, '', 1, 1, 'R');
  }
  $pdf->SetFont('helvetica', 'B', 8);
  $pdf->Cell(186, 5, count($righe) . ' articoli', 1, 1, 'L');
  $pdf->Output('lista_spesa_' . date('Ymd') . '.pdf', 'I');
  exit;
}

// valori gia' scritti quando si torna dal riepilogo, per codice articolo
$pre = array('costo' => array(), 'base' => array(), 'cli' => array());
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['correggi']) && isset($_POST['art']) && is_array($_POST['art'])) {
  foreach ($_POST['art'] as $i => $cod) {
    $cod = (string) $cod;
    $pre['costo'][$cod] = isset($_POST['costo'][$i]) ? (string) $_POST['costo'][$i] : '';
    $pre['base'][$cod] = isset($_POST['base'][$i]) ? (string) $_POST['base'][$i] : '';
    if (isset($_POST['cli'][$i]) && is_array($_POST['cli'][$i])) {
      foreach ($_POST['cli'][$i] as $j => $clfoco) {
        $pre['cli'][$cod][intval($clfoco)] = array(
          'prezzo' => isset($_POST['prezzo_c'][$i][$j]) ? (string) $_POST['prezzo_c'][$i][$j] : '',
          'adegua' => !empty($_POST['adegua_c'][$i][$j]));
      }
    }
  }
}

require("../../library/include/header.php");
$script_transl = HeadMain();

// su telefono le righe diventano schede: chi compra usa lo smartphone
echo '<style>
@media (max-width: 800px) {
  .tabella-spesa, .tabella-spesa tbody, .tabella-spesa tr, .tabella-spesa td { display: block; width: 100%; box-sizing: border-box; }
  .tabella-spesa tr.intesta-colonne { display: none !important; }
  .tabella-spesa tr.riga-fornitore td { background: #444 !important; color: #fff; border-radius: 6px; margin-top: 12px; }
  .tabella-spesa tr.riga-spesa { border: 1px solid #ccc; border-radius: 8px; margin: 6px 0; padding: 6px 8px; background: #fff; }
  .tabella-spesa tr.riga-spesa td { border: 0 !important; padding: 3px 4px; text-align: left !important; }
  .tabella-spesa tr.riga-spesa td[data-label]::before { content: attr(data-label) ": "; color: #777; font-size: 12px; }
  .tabella-spesa td.cella-prodotto { font-weight: bold; font-size: 116%; }
  .tabella-spesa td.solo-schermo-largo { display: none !important; }
  .tabella-spesa input.costo-oggi, .tabella-spesa input.base-nuovo { font-size: 18px; padding: 5px; width: 110px; }
  .tabella-spesa tr.pannello-accordi td { border: 0; padding: 2px; }
  .tabella-spesa tr.pannello-accordi table tr { display: block; border-bottom: 1px solid #ddd; padding: 4px 0; }
  .tabella-spesa tr.pannello-accordi table td { display: inline-block; width: auto; padding: 2px 6px; }
  .tabella-spesa tr.pannello-accordi table td.nome-cliente { display: block; }
  .barra-salva { position: sticky; bottom: 0; background: rgba(255,255,255,.95); padding: 8px; border-top: 2px solid #ccc; z-index: 100; }
  .barra-salva input { width: 100%; font-size: 18px; padding: 12px; }
  form[name="lsfiltri"] table, form[name="lsfiltri"] tbody, form[name="lsfiltri"] tr, form[name="lsfiltri"] td { display: block; width: 100%; box-sizing: border-box; text-align: left; }
}
</style>';

echo '<div align="center" class="FacetFormHeaderFont">Lista della spesa &mdash; ordini dal ' . gaz_format_date($flt['dal']) . ' al ' . gaz_format_date($flt['al']) . '</div>';
if (isset($_GET['salvato'])) {
  echo '<div class="alert alert-success text-center">Salvato: ' . intval($_GET['nc'] ?? 0) . ' costi di acquisto, ' . intval($_GET['nb'] ?? 0)
    . ' listini base, ' . intval($_GET['np'] ?? 0) . ' prezzi netti cliente.</div>';
}

// filtri
echo '<form method="GET" name="lsfiltri"><input type="hidden" name="filtra" value="1" />';
echo '<table class="Tmiddle table-striped" align="center"><tr>';
echo '<td class="FacetFieldCaptionTD">Ordini dal</td><td class="FacetDataTD"><input type="date" name="dal" value="' . htmlspecialchars($flt['dal']) . '" /></td>';
echo '<td class="FacetFieldCaptionTD">al</td><td class="FacetDataTD"><input type="date" name="al" value="' . htmlspecialchars($flt['al']) . '" /></td>';
echo '<td class="FacetFieldCaptionTD">Fornitore</td><td class="FacetDataTD"><select name="fornitore"><option value="0">- tutti -</option>';
$forn_dd = $dati['fornitori'];
asort($forn_dd);
foreach ($forn_dd as $fc => $fn) {
  echo '<option value="' . intval($fc) . '"' . ($flt['fornitore'] == $fc ? ' selected' : '') . '>' . htmlspecialchars($fn) . '</option>';
}
echo '</select></td>';
echo '<td class="FacetFieldCaptionTD">Solo da comprare</td><td class="FacetDataTD"><input type="checkbox" name="solo_fabbisogno" value="1"' . ($flt['solo_fabbisogno'] ? ' checked' : '') . ' /></td>';
echo '</tr><tr><td colspan="8" align="center">';
echo '<input type="submit" class="btn btn-primary btn-sm" value="Aggiorna" /> &nbsp;';
echo '<a class="btn btn-default btn-sm" href="lista_spesa.php?' . htmlspecialchars(http_build_query($qs_flt + array('export' => 'pdf'))) . '" target="_blank">Stampa PDF</a>';
echo '</td></tr></table></form>';

// modulo dei costi del giorno
echo '<form method="POST" name="lspesa" action="lista_spesa.php?' . htmlspecialchars(http_build_query($qs_flt)) . '">';
echo '<div class="table-responsive"><table class="Tlarge table table-bordered table-condensed tabella-spesa">';
echo '<tr class="FacetFieldCaptionTD intesta-colonne"><td>Cod.</td><td>Prodotto</td><td align="right">Ordinato</td><td align="right">Giacenza</td><td align="right">Da comprare</td><td>U.M.</td>'
  . '<td align="right">Costo prec.</td><td align="right">Costo oggi</td><td align="right">Ric.%</td><td align="right">Consigliato</td><td align="right">Nuovo listino base</td><td>Prezzi cliente</td></tr>';

$forn_prec = null;
foreach ($righe as $i => $r) {
  if ($r['fornitore'] !== $forn_prec) {
    $forn_prec = $r['fornitore'];
    echo '<tr class="riga-fornitore"><td colspan="12" style="background:#e8e8e8;font-weight:bold;"><i class="glyphicon glyphicon-shopping-cart"></i> ' . htmlspecialchars($r['fornitore']) . '</td></tr>';
  }
  $cod = $r['codart'];
  $cod_html = htmlspecialchars($cod);
  $um = htmlspecialchars($r['unimis']);
  $n_acc = count($r['accordi']);
  $v_costo = isset($pre['costo'][$cod]) ? $pre['costo'][$cod] : '';
  $v_base = isset($pre['base'][$cod]) ? $pre['base'][$cod] : '';
  echo '<tr class="FacetDataTD riga-spesa" data-ricarico="' . floatval($r['ricarico']) . '" data-idx="' . $i . '">';
  echo '<td class="solo-schermo-largo">' . $cod_html . '<input type="hidden" name="art[' . $i . ']" value="' . $cod_html . '" /></td>';
  echo '<td class="cella-prodotto">' . htmlspecialchars($r['descri']) . '</td>';
  echo '<td align="right" data-label="Ordinato" title="' . intval($r['n_ordini']) . ' ordini">' . gaz_format_quantity($r['ordinato'], 1, $admin_aziend['decimal_quantity']) . '</td>';
  echo '<td align="right" data-label="Giacenza"' . ($r['giacenza'] < 0 ? ' class="text-danger"' : '') . '>' . gaz_format_quantity($r['giacenza'], 1, $admin_aziend['decimal_quantity']) . '</td>';
  echo '<td align="right" data-label="Da comprare"><b>' . gaz_format_quantity($r['da_comprare'], 1, $admin_aziend['decimal_quantity']) . ' ' . $um . '</b></td>';
  echo '<td class="solo-schermo-largo">' . $um . '</td>';
  echo '<td align="right" data-label="Costo prec.">' . ($r['last_cost'] > 0 ? gaz_format_number($r['last_cost']) : '-') . '</td>';
  echo '<td align="right" data-label="Costo oggi"><input type="text" inputmode="decimal" class="costo-oggi" name="costo[' . $i . ']" size="7" style="text-align:right;font-weight:bold;" value="' . htmlspecialchars($v_costo) . '" /></td>';
  echo '<td align="right" data-label="Ricarico %">' . ($r['ricarico'] > 0 ? number_format($r['ricarico'], 1, ',', '') : '-') . '</td>';
  echo '<td align="right" data-label="Consigliato" class="consigliato">-</td>';
  echo '<td align="right" data-label="Nuovo listino base"><input type="text" inputmode="decimal" class="base-nuovo" name="base[' . $i . ']" size="7" style="text-align:right;" value="' . htmlspecialchars($v_base) . '"'
    . ' placeholder="' . ($r['preve1'] > 0 ? number_format($r['preve1'], $dec_prezzo, ',', '') : '') . '" title="Vuoto = il listino base resta com\'e\'" /></td>';
  if ($n_acc > 0) {
    echo '<td data-label="Prezzi cliente"><button type="button" class="btn btn-xs btn-default apri-accordi" data-target="acc_' . $i . '">' . $n_acc . ' clienti <span class="acc-allarmi text-danger"></span></button></td>';
  } else {
    echo '<td data-label="Prezzi cliente" class="text-muted">nessuno</td>';
  }
  echo '</tr>';
  if ($n_acc > 0) {
    echo '<tr id="acc_' . $i . '" class="pannello-accordi" style="display:none;"><td class="solo-schermo-largo"></td><td colspan="11" style="background:#fbfbfb;">';
    echo '<table class="table-condensed"><tr class="FacetFieldCaptionTD intesta-colonne"><td>Cliente</td><td align="right">Prezzo attuale</td><td align="right">Margine col costo di oggi</td><td align="right">Nuovo prezzo</td><td>Adegua</td></tr>';
    foreach ($r['accordi'] as $j => $ac) {
      $p_cli = isset($pre['cli'][$cod][$ac['clfoco']]) ? $pre['cli'][$cod][$ac['clfoco']] : array('prezzo' => '', 'adegua' => false);
      echo '<tr class="FacetDataTD riga-accordo" data-prezzo="' . floatval($ac['prezzo']) . '">';
      echo '<td class="nome-cliente"><b>' . htmlspecialchars($ac['nome']) . '</b><input type="hidden" name="cli[' . $i . '][' . $j . ']" value="' . intval($ac['clfoco']) . '" /></td>';
      echo '<td align="right">' . gaz_format_number($ac['prezzo']) . '</td>';
      echo '<td align="right" class="margine">-</td>';
      echo '<td align="right"><input type="text" inputmode="decimal" class="proposta" name="prezzo_c[' . $i . '][' . $j . ']" size="7" style="text-align:right;" value="' . htmlspecialchars($p_cli['prezzo']) . '" /></td>';
      echo '<td align="center"><input type="checkbox" class="adegua" name="adegua_c[' . $i . '][' . $j . ']" value="1"' . ($p_cli['adegua'] ? ' checked' : '') . ' /></td>';
      echo '</tr>';
    }
    echo '</table></td></tr>';
  }
}
if (count($righe) == 0) {
  echo '<tr><td colspan="12" align="center" style="padding:16px;">Nessun articolo: non ci sono ordini clienti aperti nel periodo, oppure la giacenza copre tutto (togli la spunta "Solo da comprare" per vederli).</td></tr>';
}
echo '</table></div>';
if (count($righe) > 0) {
  echo '<div class="text-center barra-salva" style="margin:10px;"><input type="submit" class="btn btn-success" name="anteprima" value="Controlla le modifiche" /></div>';
}
echo '</form>';
?>
<script type="text/javascript">
$(function() {
  var dec = <?php echo $dec_prezzo; ?>;
  function numero(v) { return parseFloat(String(v).replace(',', '.')); }
  // consigliato = costo + ricarico; propone il listino base e i prezzi cliente
  // nei campi non ancora toccati e segnala i prezzi cliente sotto costo
  function aggiornaRiga(tr) {
    var ric = parseFloat(tr.attr('data-ricarico')) || 0;
    var costo = numero(tr.find('.costo-oggi').val());
    var pann = $('#acc_' + tr.attr('data-idx'));
    if (!(costo > 0)) {
      tr.find('.consigliato').text('-');
      pann.find('.margine').text('-');
      tr.find('.acc-allarmi').text('');
      return;
    }
    var proposta = (Math.round(costo * (1 + ric / 100) * Math.pow(10, dec)) / Math.pow(10, dec)).toFixed(dec);
    tr.find('.consigliato').html('<b>' + proposta + '</b>');
    var base = tr.find('.base-nuovo');
    if (!base.data('toccato')) { base.val(proposta); }
    var allarmi = 0;
    pann.find('.riga-accordo').each(function() {
      var ra = $(this);
      var prezzo = parseFloat(ra.attr('data-prezzo'));
      var m = (prezzo - costo) / costo * 100;
      var cel = ra.find('.margine').text(m.toFixed(1) + '%').removeClass('text-danger text-warning');
      if (prezzo < costo) { cel.addClass('text-danger').append(' sotto costo'); allarmi++; }
      else if (m < (ric > 0 ? ric / 2 : 10)) { cel.addClass('text-warning'); allarmi++; }
      var prop = ra.find('.proposta');
      if (!prop.data('toccato')) { prop.val(proposta); }
    });
    tr.find('.acc-allarmi').text(allarmi > 0 ? '(' + allarmi + ' da guardare)' : '');
  }
  // i valori ripresentati dal riepilogo non vanno sovrascritti
  $('.base-nuovo, .proposta').each(function() { if ($(this).val() !== '') { $(this).data('toccato', true); } });
  $('.costo-oggi').each(function() { if ($(this).val() !== '') { aggiornaRiga($(this).closest('tr')); } });
  $('.costo-oggi').on('input', function() { aggiornaRiga($(this).closest('tr')); });
  $('.base-nuovo, .proposta').on('input', function() { $(this).data('toccato', true); });
  $('.apri-accordi').on('click', function() { $('#' + $(this).attr('data-target')).toggle(); });
  // Invio passa al costo successivo, non invia il modulo
  $('form[name="lspesa"]').on('keydown', 'input[type="text"]', function(e) {
    if (e.which === 13) {
      e.preventDefault();
      if ($(this).hasClass('costo-oggi')) {
        var tutti = $('.costo-oggi');
        var i = tutti.index(this);
        if (i >= 0 && i < tutti.length - 1) { tutti.eq(i + 1).focus(); }
      }
    }
  });
});
</script>
<?php
require("../../library/include/footer.php");
?>
