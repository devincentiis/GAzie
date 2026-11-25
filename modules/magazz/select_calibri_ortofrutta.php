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
// Gestione ortofrutta: tabella dei calibri per prodotto (chiave: id automatico)
require("../../library/include/datlib.inc.php");
require_once("../../library/include/ortofrutta.inc.php");
$admin_aziend = checkAdmin();

if (!ortofrutta_attiva()) {
  require("../../library/include/header.php");
  $script_transl = HeadMain();
  ortofrutta_avviso_spenta();
  require("../../library/include/footer.php");
  exit;
}

$tipiUnita = [
  'DIAMETRO_MM' => 'Diametro (mm)',
  'PESO_G' => 'Peso (g)',
  'LUNGHEZZA_CM' => 'Lunghezza (cm)',
  'NUM_PEZZI_COLLO' => 'N. pezzi per collo',
  'CODICE_LETTERA' => 'Codice a lettera',
  'NON_CLASSIFICATO' => 'Non classificato'
];
$campi_testo = ['prodotto' => 50, 'codice_calibro' => 20, 'codice_numerico' => 10, 'destinazione' => 100];
$campi_numero = ['valore_min', 'valore_max', 'peso_min_g', 'peso_max_g'];

// valore numerico per la query, NULL se vuoto
function calibro_numero($v) {
  $v = str_replace(',', '.', trim($v));
  return is_numeric($v) ? (string)floatval($v) : 'NULL';
}

$msg = '';
$vuoto = ['id' => 0, 'tipo_unita_calibro' => 'DIAMETRO_MM', 'ricerca' => ''];
foreach ($campi_testo as $c => $l) { $vuoto[$c] = ''; }
foreach ($campi_numero as $c) { $vuoto[$c] = ''; }
$form = $vuoto;
if (isset($_POST['prodotto'])) {
  $form['id'] = intval($_POST['id']);
  foreach ($campi_testo as $c => $l) { $form[$c] = trim(substr($_POST[$c], 0, $l)); }
  foreach ($campi_numero as $c) { $form[$c] = trim(substr($_POST[$c], 0, 12)); }
  $form['tipo_unita_calibro'] = isset($tipiUnita[$_POST['tipo_unita_calibro']]) ? $_POST['tipo_unita_calibro'] : 'NON_CLASSIFICATO';
  $form['ricerca'] = trim(substr($_POST['ricerca'], 0, 30));
} elseif (isset($_GET['id'])) { // modifica di una riga dell'elenco
  $riga = gaz_dbi_get_row($gTables['calibri_ortofrutta'], 'id', intval($_GET['id']));
  if ($riga) {
    $form = array_merge($vuoto, array_intersect_key($riga, $vuoto));
  }
}

if (isset($_POST['Inserisci'])) {
  if ($form['prodotto'] == '') {
    $msg = 'Manca il prodotto';
  } elseif ($form['codice_calibro'] == '') {
    $msg = 'Manca il codice del calibro';
  } else {
    $set = ["tipo_unita_calibro = '" . $form['tipo_unita_calibro'] . "'"];
    foreach ($campi_testo as $c => $l) { $set[] = $c . " = '" . gaz_dbi_real_escape_string($form[$c]) . "'"; }
    foreach ($campi_numero as $c) { $set[] = $c . ' = ' . calibro_numero($form[$c]); }
    if ($form['id'] > 0) {
      gaz_dbi_query('UPDATE ' . $gTables['calibri_ortofrutta'] . ' SET ' . implode(', ', $set) . ' WHERE id = ' . $form['id']);
    } else {
      gaz_dbi_query('INSERT INTO ' . $gTables['calibri_ortofrutta'] . ' SET ' . implode(', ', $set));
    }
    $ricerca = $form['ricerca'];
    $form = $vuoto;
    $form['ricerca'] = $ricerca;
  }
} elseif (isset($_POST['Nuovo'])) {
  $ricerca = $form['ricerca'];
  $form = $vuoto;
  $form['ricerca'] = $ricerca;
}

require("../../library/include/header.php");
$script_transl = HeadMain();

$where = '1';
if ($form['ricerca'] != '') {
  $ric = gaz_dbi_real_escape_string($form['ricerca']);
  $where = "prodotto LIKE '%$ric%' OR codice_calibro LIKE '%$ric%'";
}
$elenco = gaz_dbi_dyn_query('*', $gTables['calibri_ortofrutta'], $where, 'prodotto, codice_calibro');

// casella di testo del form
function calibro_input($name, $val, $size, $placeholder = '', $type = 'text') {
  return '<input type="' . $type . '"' . ($type == 'number' ? ' step="0.01"' : '') . ' name="' . $name . '" value="' . htmlspecialchars((string)$val) . '" size="' . $size . '" placeholder="' . $placeholder . '" />';
}
?>
<form method="POST" action="select_calibri_ortofrutta.php">
<input type="hidden" name="id" value="<?php echo intval($form['id']); ?>" />
<div class="text-center FacetFormHeaderFont">Calibri ortofrutta<?php echo ($form['id'] > 0 ? ' (modifica n.' . intval($form['id']) . ')' : ''); ?></div>
<table class="Tmiddle table-striped" align="center">
<?php
if ($msg != '') {
  echo '<tr><td colspan="2" class="FacetDataTDred">' . $msg . '</td></tr>';
}
echo '<tr><td class="FacetFieldCaptionTD">Prodotto</td><td class="FacetDataTD">' . calibro_input('prodotto', $form['prodotto'], 25, 'es. Mele, Kiwi, Zucchine') . '</td></tr>';
echo '<tr><td class="FacetFieldCaptionTD">Codice calibro</td><td class="FacetDataTD">' . calibro_input('codice_calibro', $form['codice_calibro'], 12, 'es. 70/75, 3, AA') . '</td></tr>';
echo '<tr><td class="FacetFieldCaptionTD">Tipo di misura</td><td class="FacetDataTD"><select name="tipo_unita_calibro">';
foreach ($tipiUnita as $k => $d) {
  echo '<option value="' . $k . '"' . ($form['tipo_unita_calibro'] == $k ? ' selected' : '') . '>' . $d . '</option>';
}
echo '</select></td></tr>';
echo '<tr><td class="FacetFieldCaptionTD">Valore min / max</td><td class="FacetDataTD">' . calibro_input('valore_min', $form['valore_min'], 6, 'min', 'number') . ' - ' . calibro_input('valore_max', $form['valore_max'], 6, 'max', 'number') . ' <small>(nella misura scelta sopra)</small></td></tr>';
echo '<tr><td class="FacetFieldCaptionTD">Peso min / max (g)</td><td class="FacetDataTD">' . calibro_input('peso_min_g', $form['peso_min_g'], 6, 'min', 'number') . ' - ' . calibro_input('peso_max_g', $form['peso_max_g'], 6, 'max', 'number') . ' <small>(facoltativo)</small></td></tr>';
echo '<tr><td class="FacetFieldCaptionTD">Codice numerico</td><td class="FacetDataTD">' . calibro_input('codice_numerico', $form['codice_numerico'], 8) . '</td></tr>';
echo '<tr><td class="FacetFieldCaptionTD">Destinazione</td><td class="FacetDataTD">' . calibro_input('destinazione', $form['destinazione'], 30, 'es. GDO, export') . '</td></tr>';
echo '<tr><td class="FacetFieldCaptionTD">Cerca</td><td class="FacetDataTD">' . calibro_input('ricerca', $form['ricerca'], 20, 'prodotto o calibro') . ' <input type="submit" class="btn btn-default btn-xs" name="Cerca" value="Cerca" /></td></tr>';
?>
<tr><td colspan="2" class="FacetFooterTD text-right">
<input type="submit" class="btn btn-default" name="Nuovo" value="Nuovo" />
<input type="submit" class="btn btn-warning" name="Inserisci" value="Inserisci/Modifica" />
</td></tr>
</table>
<table class="Tlarge table table-striped table-bordered table-condensed">
<tr><th>Prodotto</th><th>Calibro</th><th>Misura</th><th>Min</th><th>Max</th><th>Peso min/max (g)</th><th>Destinazione</th><th class="text-center">Elimina</th></tr>
<?php
while ($r = gaz_dbi_fetch_array($elenco)) {
  echo '<tr><td><a class="btn btn-xs btn-edit" href="select_calibri_ortofrutta.php?id=' . intval($r['id']) . '"><i class="glyphicon glyphicon-edit"></i> ' . htmlspecialchars($r['prodotto']) . '</a></td>';
  echo '<td>' . htmlspecialchars($r['codice_calibro']) . '</td>';
  echo '<td>' . ($tipiUnita[$r['tipo_unita_calibro']] ?? htmlspecialchars($r['tipo_unita_calibro'])) . '</td>';
  echo '<td>' . htmlspecialchars((string)$r['valore_min']) . '</td><td>' . htmlspecialchars((string)$r['valore_max']) . '</td>';
  echo '<td>' . htmlspecialchars((string)$r['peso_min_g']) . ' / ' . htmlspecialchars((string)$r['peso_max_g']) . '</td>';
  echo '<td>' . htmlspecialchars($r['destinazione']) . '</td>';
  echo '<td class="text-center"><a class="btn btn-xs btn-elimina" title="Elimina" href="delete_calibro_ortofrutta.php?id=' . intval($r['id']) . '"><i class="glyphicon glyphicon-trash"></i></a></td></tr>';
}
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
