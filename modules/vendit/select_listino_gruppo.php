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
// Listino di gruppo: prezzi netti per gruppo clienti e articolo (modello: select_sconti_articoli.php)
require_once("../../library/include/datlib.inc.php");
require_once("../../library/include/listino_gruppo.inc.php");
$admin_aziend = checkAdmin();

$form = array('id_gruppo' => 0, 'cod_art' => '', 'cerca_art' => '', 'prezzo_netto' => '');
$errori = array();
$esito = '';
$elenca = false;
if (isset($_POST['hidden_req'])) { // richieste successive
  $form['id_gruppo'] = intval($_POST['id_gruppo'] ?? 0);
  $form['cod_art'] = substr(trim($_POST['cod_art'] ?? ''), 0, 32);
  $form['cerca_art'] = substr(trim($_POST['cerca_art'] ?? ''), 0, 30);
  $form['prezzo_netto'] = trim(str_replace(',', '.', $_POST['prezzo_netto'] ?? ''));
  if (isset($_POST['cambia_art'])) {
    $form['cod_art'] = '';
  }
  $elenca = isset($_POST['Elenca']);
} elseif (isset($_GET['gruppo'])) { // ritorno dalla cancellazione
  $form['id_gruppo'] = intval($_GET['gruppo']);
  $elenca = true;
  if (isset($_GET['cancellato'])) {
    $esito = 'cancellato';
  }
}
// l'articolo scelto deve esistere
$articolo = false;
if ($form['cod_art'] !== '') {
  $articolo = gaz_dbi_get_row($gTables['artico'], 'codice', $form['cod_art']);
  if (!$articolo) {
    $form['cod_art'] = '';
  }
}

if (isset($_POST['Return'])) {
  header("Location: report_client.php");
  exit;
}

if (isset($_POST['Salva'])) {
  if ($form['id_gruppo'] <= 0 || !gaz_dbi_get_row($gTables['customer_group'], 'id', $form['id_gruppo'])) {
    $errori[] = 'err_gruppo';
  }
  if (!$articolo) {
    $errori[] = 'err_articolo';
  }
  if (!is_numeric($form['prezzo_netto']) || floatval($form['prezzo_netto']) <= 0) {
    $errori[] = 'err_prezzo';
  }
  if (count($errori) == 0) {
    $prezzo = round(floatval($form['prezzo_netto']), $admin_aziend['decimal_price']);
    $esito = listino_gruppo_salva($form['id_gruppo'], $form['cod_art'], $prezzo) ? 'inserito' : 'modificato';
    $elenca = true;
  }
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$t = $script_transl;

// elenco dei prezzi (tutti i gruppi se non ne e' scelto uno)
$elenco = null;
if ($elenca) {
  $where = "1";
  if ($form['id_gruppo'] > 0) {
    $where .= " AND p.id_customer_group = " . intval($form['id_gruppo']);
  }
  $elenco = gaz_dbi_query("SELECT p.id_customer_group, p.codart, p.prezzo_netto, g.descri AS gruppo, a.descri AS articolo, a.unimis
    FROM " . $gTables['customer_group_prezzi'] . " p
    LEFT JOIN " . $gTables['customer_group'] . " g ON g.id = p.id_customer_group
    LEFT JOIN " . $gTables['artico'] . " a ON a.codice = p.codart
    WHERE " . $where . " ORDER BY g.descri, a.descri, p.codart");
}
?>
<form method="POST" action="select_listino_gruppo.php">
<input type="hidden" name="hidden_req" value="1">
<div class="FacetFormHeaderFont text-center"><?php echo htmlspecialchars($t['title']); ?></div>
<?php
if (!listino_gruppo_attivo()) {
  echo '<div class="alert alert-warning text-center">' . htmlspecialchars($t['non_attivo']) . '</div>';
}
if (count($errori) > 0) {
  echo '<div class="alert alert-danger text-center">';
  foreach ($errori as $e) {
    echo htmlspecialchars($t[$e]) . '<br>';
  }
  echo '</div>';
}
if ($esito != '') {
  echo '<div class="alert alert-success text-center">' . htmlspecialchars($t[$esito]) . '</div>';
}
?>
<table class="Tmiddle table-striped" align="center">
<tr>
  <td class="FacetFieldCaptionTD"><?php echo htmlspecialchars($t['gruppo']); ?></td>
  <td class="FacetDataTD">
    <select name="id_gruppo" class="FacetSelect">
      <option value="0"><?php echo htmlspecialchars($t['tutti_gruppi']); ?></option>
<?php
$rs = gaz_dbi_query("SELECT id, descri FROM " . $gTables['customer_group'] . " ORDER BY descri");
while ($r = gaz_dbi_fetch_array($rs)) {
  $sel = ($r['id'] == $form['id_gruppo']) ? ' selected' : '';
  echo '      <option value="' . intval($r['id']) . '"' . $sel . '>' . htmlspecialchars($r['descri']) . "</option>\n";
}
?>
    </select>
<?php
if ($form['id_gruppo'] > 0) {
  $n_clienti = gaz_dbi_record_count($gTables['clfoco'], "id_customer_group = " . intval($form['id_gruppo']));
  echo ' <small>' . htmlspecialchars($t['clienti_gruppo']) . ': ' . intval($n_clienti) . '</small>';
}
?>
  </td>
</tr>
<tr>
  <td class="FacetFieldCaptionTD"><?php echo htmlspecialchars($t['articolo']); ?></td>
  <td class="FacetDataTD">
<?php
if ($articolo) { // articolo scelto
  echo '    <input type="hidden" name="cod_art" value="' . htmlspecialchars($articolo['codice']) . '">';
  echo '<input type="hidden" name="cerca_art" value="' . htmlspecialchars($form['cerca_art']) . '">';
  echo '<b>' . htmlspecialchars($articolo['codice'] . ' - ' . $articolo['descri']) . '</b> ';
  echo '<input type="submit" class="btn btn-xs btn-default" name="cambia_art" value="' . htmlspecialchars($t['cambia_articolo']) . '">';
  if ($form['id_gruppo'] > 0 && !isset($_POST['Salva'])) { // propongo il prezzo gia' presente
    $attuale = listino_gruppo_prezzo($form['id_gruppo'], $articolo['codice']);
    if ($attuale > 0 && $form['prezzo_netto'] === '') {
      $form['prezzo_netto'] = number_format($attuale, $admin_aziend['decimal_price'], '.', '');
    }
  }
} else { // ricerca per codice o descrizione
  echo '    <input type="hidden" name="cod_art" value="">';
  echo '<input type="text" name="cerca_art" value="' . htmlspecialchars($form['cerca_art']) . '" maxlength="30" placeholder="' . htmlspecialchars($t['cerca_articolo']) . '" class="FacetInput"> ';
  echo '<button type="submit" class="btn btn-default btn-sm" name="cerca"><i class="glyphicon glyphicon-search"></i></button> ';
  if (strlen($form['cerca_art']) >= 2) {
    $cerca = gaz_dbi_real_escape_string($form['cerca_art']);
    $rs = gaz_dbi_dyn_query("codice, descri", $gTables['artico'], "codice LIKE '%" . $cerca . "%' OR descri LIKE '%" . $cerca . "%'", "descri ASC", 0, 200);
    if (gaz_dbi_num_rows($rs) > 0) {
      echo '<select name="cod_art" class="FacetSelect" onchange="this.form.submit();">';
      echo '<option value=""> ---------- </option>';
      while ($r = gaz_dbi_fetch_array($rs)) {
        echo '<option value="' . htmlspecialchars($r['codice']) . '">' . htmlspecialchars($r['codice'] . ' - ' . $r['descri']) . '</option>';
      }
      echo '</select>';
    } else {
      echo '<span class="text-danger">' . htmlspecialchars($t['nessun_articolo']) . '</span>';
    }
  } elseif ($form['cerca_art'] !== '') {
    echo '<span class="text-danger">' . htmlspecialchars($t['min_caratteri']) . '</span>';
  }
}
?>
  </td>
</tr>
<tr>
  <td class="FacetFieldCaptionTD"><?php echo htmlspecialchars($t['prezzo_netto']); ?></td>
  <td class="FacetDataTD"><input type="number" step="any" min="0" name="prezzo_netto" value="<?php echo htmlspecialchars($form['prezzo_netto']); ?>" maxlength="14"></td>
</tr>
<tr>
  <td class="FacetFieldCaptionTD"><input type="submit" class="btn btn-default" name="Return" value="<?php echo htmlspecialchars($t['indietro']); ?>"></td>
  <td class="FacetFooterTD text-right">
    <input type="submit" class="btn btn-default" name="Elenca" value="<?php echo htmlspecialchars($t['elenca']); ?>">
    <input type="submit" class="btn btn-warning" name="Salva" value="<?php echo htmlspecialchars($t['salva']); ?>">
  </td>
</tr>
</table>
<?php
if ($elenco) {
  echo '<table class="Tlarge table table-striped table-bordered table-condensed table-responsive">';
  echo '<tr>';
  foreach (array('gruppo', 'cod_art', 'articolo', 'prezzo_netto', 'elimina') as $h) {
    echo '<th class="FacetFieldCaptionTD">' . htmlspecialchars($t['col_' . $h]) . '</th>';
  }
  echo "</tr>\n";
  if (gaz_dbi_num_rows($elenco) == 0) {
    echo '<tr><td colspan="5" class="text-center">' . htmlspecialchars($t['nessun_prezzo']) . "</td></tr>\n";
  }
  while ($r = gaz_dbi_fetch_array($elenco)) {
    $link = 'delete_prezzo_gruppo.php?gruppo=' . intval($r['id_customer_group']) . '&codart=' . urlencode($r['codart']);
    echo '<tr class="FacetDataTD">';
    echo '<td>' . htmlspecialchars((string)$r['gruppo']) . '</td>';
    echo '<td>' . htmlspecialchars($r['codart']) . '</td>';
    echo '<td>' . htmlspecialchars((string)$r['articolo']) . '</td>';
    echo '<td class="text-right">' . gaz_format_number($r['prezzo_netto']) . ' ' . htmlspecialchars((string)$r['unimis']) . '</td>';
    echo '<td class="text-center"><a class="btn btn-xs btn-elimina" title="' . htmlspecialchars($t['col_elimina']) . '" href="' . htmlspecialchars($link) . '"><i class="glyphicon glyphicon-trash"></i></a></td>';
    echo "</tr>\n";
  }
  echo '</table>';
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
