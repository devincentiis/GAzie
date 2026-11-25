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
// Gestione ortofrutta: tabella delle provenienze (paesi di origine, codice ISO 3166)
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

$msg = '';
$form = ['codice' => '', 'descrizione' => '', 'ricerca' => ''];
if (isset($_POST['codice'])) {
  $form['codice'] = preg_replace('/[^A-Z0-9]/', '', strtoupper(substr($_POST['codice'], 0, 3)));
  $form['descrizione'] = trim(substr($_POST['descrizione'], 0, 60));
  $form['ricerca'] = trim(substr($_POST['ricerca'], 0, 30));
} elseif (isset($_GET['codice'])) { // modifica di una riga dell'elenco
  $riga = gaz_dbi_get_row($gTables['provenienze'], 'codice', substr($_GET['codice'], 0, 3));
  if ($riga) {
    $form['codice'] = $riga['codice'];
    $form['descrizione'] = $riga['descrizione'];
  }
}

if (isset($_POST['Inserisci'])) {
  if ($form['codice'] == '') {
    $msg = 'Manca il codice';
  } elseif ($form['descrizione'] == '') {
    $msg = 'Manca la descrizione';
  } else {
    if (gaz_dbi_get_row($gTables['provenienze'], 'codice', $form['codice'])) {
      gaz_dbi_table_update('provenienze', $form['codice'], ['descrizione' => $form['descrizione']]);
    } else {
      gaz_dbi_table_insert('provenienze', ['codice' => $form['codice'], 'descrizione' => $form['descrizione']]);
    }
    $form['codice'] = '';
    $form['descrizione'] = '';
  }
} elseif (isset($_POST['Nuovo'])) {
  $form['codice'] = '';
  $form['descrizione'] = '';
}

require("../../library/include/header.php");
$script_transl = HeadMain();

$where = '1';
if ($form['ricerca'] != '') {
  $ric = gaz_dbi_real_escape_string($form['ricerca']);
  $where = "codice LIKE '%$ric%' OR descrizione LIKE '%$ric%'";
}
$elenco = gaz_dbi_dyn_query('codice, descrizione', $gTables['provenienze'], $where, 'descrizione');
?>
<form method="POST" action="select_provenienze.php">
<div class="text-center FacetFormHeaderFont">Provenienze (paesi di origine)</div>
<table class="Tmiddle table-striped" align="center">
<?php
if ($msg != '') {
  echo '<tr><td colspan="2" class="FacetDataTDred">' . $msg . '</td></tr>';
}
?>
<tr><td class="FacetFieldCaptionTD">Codice ISO</td><td class="FacetDataTD"><input type="text" name="codice" value="<?php echo htmlspecialchars($form['codice']); ?>" maxlength="3" size="4" style="text-transform:uppercase" /></td></tr>
<tr><td class="FacetFieldCaptionTD">Descrizione</td><td class="FacetDataTD"><input type="text" name="descrizione" value="<?php echo htmlspecialchars($form['descrizione']); ?>" maxlength="60" size="30" /></td></tr>
<tr><td class="FacetFieldCaptionTD">Cerca</td><td class="FacetDataTD"><input type="text" name="ricerca" value="<?php echo htmlspecialchars($form['ricerca']); ?>" maxlength="30" size="20" placeholder="codice o descrizione" /> <input type="submit" class="btn btn-default btn-xs" name="Cerca" value="Cerca" /></td></tr>
<tr><td colspan="2" class="FacetFooterTD text-right">
<input type="submit" class="btn btn-default" name="Nuovo" value="Nuovo" />
<input type="submit" class="btn btn-warning" name="Inserisci" value="Inserisci/Modifica" />
</td></tr>
</table>
<table class="Tlarge table table-striped table-bordered table-condensed">
<tr><th>Codice</th><th>Descrizione</th><th class="text-center">Elimina</th></tr>
<?php
while ($r = gaz_dbi_fetch_array($elenco)) {
  $cod = htmlspecialchars($r['codice']);
  echo '<tr><td><a class="btn btn-xs btn-edit" href="select_provenienze.php?codice=' . urlencode($r['codice']) . '"><i class="glyphicon glyphicon-edit"></i> ' . $cod . '</a></td>';
  echo '<td>' . htmlspecialchars($r['descrizione']) . '</td>';
  echo '<td class="text-center"><a class="btn btn-xs btn-elimina" title="Elimina" href="delete_provenienza.php?codice=' . urlencode($r['codice']) . '"><i class="glyphicon glyphicon-trash"></i></a></td></tr>';
}
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
