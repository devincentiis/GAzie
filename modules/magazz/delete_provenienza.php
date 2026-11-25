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
// Gestione ortofrutta: eliminazione di una provenienza
require("../../library/include/datlib.inc.php");
require_once("../../library/include/ortofrutta.inc.php");
$admin_aziend = checkAdmin();

if (!ortofrutta_attiva()) {
  require("../../library/include/header.php");
  HeadMain();
  ortofrutta_avviso_spenta();
  require("../../library/include/footer.php");
  exit;
}

$codice = preg_replace('/[^A-Z0-9]/', '', strtoupper(substr($_REQUEST['codice'] ?? '', 0, 3)));
if (isset($_POST['Delete']) && $codice != '') {
  gaz_dbi_del_row($gTables['provenienze'], 'codice', $codice);
  header("Location: select_provenienze.php");
  exit;
}
if (isset($_POST['Return'])) {
  header("Location: select_provenienze.php");
  exit;
}
$form = gaz_dbi_get_row($gTables['provenienze'], 'codice', $codice);
if (!$form) {
  header("Location: select_provenienze.php");
  exit;
}
$usato = gaz_dbi_record_count($gTables['artico'], "provenienza = '" . $codice . "'");

require("../../library/include/header.php");
HeadMain();
?>
<form method="POST">
<input type="hidden" name="codice" value="<?php echo $codice; ?>">
<div class="text-center text-danger">Attenzione!!! Eliminazione provenienza <?php echo $codice; ?></div>
<table class="GazFormDeleteTable">
<tr><td colspan="2" class="FacetDataTDred">Sei sicuro di voler rimuovere?<?php echo ($usato > 0 ? ' La usano ' . $usato . ' articoli.' : ''); ?></td></tr>
<tr><td class="FacetFieldCaptionTD">Codice</td><td class="FacetDataTD"><?php echo $codice; ?></td></tr>
<tr><td class="FacetFieldCaptionTD">Descrizione</td><td class="FacetDataTD"><?php echo htmlspecialchars($form['descrizione']); ?></td></tr>
<tr><td colspan="2" align="right">Se sei sicuro conferma l'eliminazione &nbsp;
<input type="submit" name="Return" class="btn btn-default" value="Indietro">&nbsp;
<input type="submit" name="Delete" class="btn btn-danger" value="Elimina">
</td></tr>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
