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
// Cancella un prezzo del listino di gruppo (modello: delete_sconto_articolo.php)
require_once("../../library/include/datlib.inc.php");
require_once("../../library/include/listino_gruppo.inc.php");
$admin_aziend = checkAdmin();

$id_gruppo = intval(isset($_POST['gruppo']) ? $_POST['gruppo'] : (isset($_GET['gruppo']) ? $_GET['gruppo'] : 0));
$codart = substr(isset($_POST['codart']) ? $_POST['codart'] : (isset($_GET['codart']) ? $_GET['codart'] : ''), 0, 32);

if (isset($_POST['Return'])) {
  header("Location: select_listino_gruppo.php?gruppo=" . $id_gruppo);
  exit;
}
if (isset($_POST['Delete'])) {
  listino_gruppo_cancella($id_gruppo, $codart);
  header("Location: select_listino_gruppo.php?gruppo=" . $id_gruppo . "&cancellato=1");
  exit;
}

// dati da mostrare letti dal database, non dall'indirizzo
$riga = gaz_dbi_fetch_array(gaz_dbi_query("SELECT p.codart, p.prezzo_netto, g.descri AS gruppo, a.descri AS articolo
  FROM " . $gTables['customer_group_prezzi'] . " p
  LEFT JOIN " . $gTables['customer_group'] . " g ON g.id = p.id_customer_group
  LEFT JOIN " . $gTables['artico'] . " a ON a.codice = p.codart
  WHERE p.id_customer_group = " . $id_gruppo . " AND p.codart = '" . gaz_dbi_real_escape_string($codart) . "' LIMIT 1"));

require("../../library/include/header.php");
$script_transl = HeadMain();
$t = $script_transl;
?>
<form method="POST" action="delete_prezzo_gruppo.php">
<input type="hidden" name="gruppo" value="<?php echo $id_gruppo; ?>">
<input type="hidden" name="codart" value="<?php echo htmlspecialchars($codart); ?>">
<div class="FacetFormHeaderFont text-center"><?php echo htmlspecialchars($t['title']); ?></div>
<?php
if (!$riga) {
  echo '<div class="alert alert-warning text-center">' . htmlspecialchars($t['non_trovato']) . '</div>';
  echo '<div class="text-center"><input type="submit" class="btn btn-default" name="Return" value="' . htmlspecialchars($t['indietro']) . '"></div>';
} else {
?>
<table class="GazFormDeleteTable">
  <tr><td colspan="2" class="FacetDataTD" style="color: red;"><?php echo htmlspecialchars($t['domanda']); ?></td></tr>
  <tr><td class="FacetFieldCaptionTD"><?php echo htmlspecialchars($t['gruppo']); ?></td><td class="FacetDataTD"><?php echo htmlspecialchars((string)$riga['gruppo']); ?></td></tr>
  <tr><td class="FacetFieldCaptionTD"><?php echo htmlspecialchars($t['articolo']); ?></td><td class="FacetDataTD"><?php echo htmlspecialchars($riga['codart'] . ' - ' . $riga['articolo']); ?></td></tr>
  <tr><td class="FacetFieldCaptionTD"><?php echo htmlspecialchars($t['prezzo_netto']); ?></td><td class="FacetDataTD"><?php echo gaz_format_number($riga['prezzo_netto']); ?></td></tr>
  <tr><td colspan="2" class="text-right">
    <input type="submit" class="btn btn-default" name="Return" value="<?php echo htmlspecialchars($t['indietro']); ?>">
    <input type="submit" class="btn btn-danger" name="Delete" value="<?php echo htmlspecialchars($t['elimina']); ?>">
  </td></tr>
</table>
<?php
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
