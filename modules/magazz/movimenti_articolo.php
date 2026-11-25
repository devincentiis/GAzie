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
  Magazzino facile: movimenti di magazzino di un articolo con il saldo
  progressivo (si apre dall'inventario facile e dalla situazione facile).
*/
require("../../library/include/datlib.inc.php");
require_once("../../library/include/magazzino_facile.inc.php");
$admin_aziend = checkAdmin();
require("../../library/include/header.php");
$script_transl = HeadMain();

if (!magazzino_facile_attivo()) {
  magazzino_facile_avviso_spento();
  require("../../library/include/footer.php");
  exit;
}

$codice = isset($_GET['codice']) ? substr($_GET['codice'], 0, 32) : '';
$artico = ($codice !== '') ? gaz_dbi_get_row($gTables['artico'], 'codice', $codice) : false;

echo "<div align=\"center\" class=\"FacetFormHeaderFont\">Movimenti di magazzino &mdash; "
  . htmlspecialchars($codice) . " " . ($artico ? htmlspecialchars($artico['descri']) : '') . "</div>";

if (!$artico) {
  echo "<div class=\"alert alert-danger\">Articolo non trovato.</div>";
} else {
  $caunomi = array();
  $rs = gaz_dbi_query("SELECT codice, descri FROM " . $gTables['caumag']);
  while ($r = gaz_dbi_fetch_array($rs)) {
    $caunomi[$r['codice']] = $r['descri'];
  }
  $cod_sql = gaz_dbi_real_escape_string($codice);
  // giorni con un inventario: gli altri movimenti di quei giorni non entrano nel saldo
  $giorni_inv = array();
  $rs = gaz_dbi_query("SELECT DISTINCT datreg FROM " . $gTables['movmag'] . " WHERE artico = '$cod_sql' AND caumag = 99");
  while ($r = gaz_dbi_fetch_array($rs)) {
    $giorni_inv[$r['datreg']] = true;
  }

  echo "<div class=\"table-responsive\"><table class=\"Tlarge table table-striped table-bordered table-condensed\">";
  echo "<tr class=\"FacetFieldCaptionTD\"><td>Data</td><td>Causale</td><td>Documento</td><td>Cliente/Fornitore</td><td align=\"right\">Carico</td><td align=\"right\">Scarico</td><td align=\"right\">Saldo</td></tr>";
  $saldo = 0;
  $nmov = 0;
  $rs = gaz_dbi_query("SELECT m.id_mov, m.caumag, m.operat, m.datreg, m.desdoc, m.quanti, c.descri AS partner FROM " . $gTables['movmag'] . " m"
    . " LEFT JOIN " . $gTables['clfoco'] . " c ON c.codice = m.clfoco"
    . " WHERE m.artico = '$cod_sql' ORDER BY m.datreg, m.id_mov");
  while ($m = gaz_dbi_fetch_array($rs)) {
    $nmov++;
    $carico = '';
    $scarico = '';
    $evid = '';
    $nota = '';
    if ($m['operat'] == 1) {
      $carico = gaz_format_quantity($m['quanti']);
    } elseif ($m['operat'] == -1) {
      $scarico = gaz_format_quantity($m['quanti']);
    }
    if ($m['caumag'] == 99) { // inventario: il saldo riparte da qui
      $saldo = floatval($m['quanti']);
      $evid = ' style="background:#dff0d8;"';
    } elseif ($m['caumag'] > 99 || isset($giorni_inv[$m['datreg']])) {
      $evid = ' style="background:#fcf8e3;color:#777;"';
      $nota = ' <small>(non conteggiato' . ($m['caumag'] > 99 ? '' : ': stesso giorno dell\'inventario') . ')</small>';
    } else {
      $saldo += floatval($m['quanti']) * intval($m['operat']);
    }
    $causale = isset($caunomi[$m['caumag']]) ? $caunomi[$m['caumag']] : $m['caumag'];
    echo "<tr class=\"FacetDataTD\"$evid>";
    echo "<td>" . gaz_format_date($m['datreg']) . "</td>";
    echo "<td>" . htmlspecialchars($causale) . $nota . "</td>";
    echo "<td>" . htmlspecialchars($m['desdoc']) . "</td>";
    echo "<td>" . htmlspecialchars((string)$m['partner']) . "</td>";
    echo "<td align=\"right\">$carico</td>";
    echo "<td align=\"right\">$scarico</td>";
    echo "<td align=\"right\"><b>" . gaz_format_quantity($saldo) . "</b></td>";
    echo "</tr>";
  }
  if ($nmov == 0) {
    echo "<tr><td colspan=\"7\" class=\"alert alert-warning\">Nessun movimento per questo articolo.</td></tr>";
  }
  echo "</table></div>";
  echo "<div class=\"text-center\"><button type=\"button\" class=\"btn btn-default\" onclick=\"window.close();\">Chiudi</button></div>";
}
require("../../library/include/footer.php");
?>
