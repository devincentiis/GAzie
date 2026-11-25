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
  Magazzino facile: rettifica della giacenza di un articolo. Registra gli stessi
  movimenti dell'inventario di GAzie (editable_inventory.php): causale 98 per la
  differenza e causale 99 con la quantita' rilevata, tipo documento INV.
  Gli articoli con lotti o matricole vanno inventariati con le pagine di GAzie.
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

$codice = isset($_REQUEST['codice']) ? substr($_REQUEST['codice'], 0, 32) : '';
$artico = ($codice !== '') ? gaz_dbi_get_row($gTables['artico'], 'codice', $codice) : false;
$oggi = date('Y-m-d');
$form = array('nuova_quantita' => '', 'data_rettifica' => $oggi);
$errore = '';
$esito = '';
$bloccato = '';
if ($artico) {
  if (intval($artico['good_or_service']) == 1) {
    $bloccato = "L'articolo e' un servizio: non ha giacenza di magazzino.";
  } elseif (intval($artico['lot_or_serial']) > 0) {
    $bloccato = "L'articolo e' gestito a lotti o matricole: per correggere la giacenza usa l'inventario di GAzie (Inventario editabile).";
  }
}

if ($artico && $bloccato == '' && isset($_POST['Rettifica'])) {
  $form['nuova_quantita'] = trim(str_replace(',', '.', substr(isset($_POST['nuova_quantita']) ? $_POST['nuova_quantita'] : '', 0, 15)));
  $form['data_rettifica'] = magazzino_facile_data(isset($_POST['data_rettifica']) ? $_POST['data_rettifica'] : '');
  if (!preg_match('/^\d+(\.\d{1,3})?$/', $form['nuova_quantita'])) {
    $errore = 'Quantita\' non valida: scrivere un numero uguale o maggiore di zero (al massimo 3 decimali).';
  } elseif ($form['data_rettifica'] == '' || $form['data_rettifica'] > $oggi) {
    $errore = 'Data non valida: deve essere una data non successiva a oggi.';
    $form['data_rettifica'] = $oggi;
  } elseif (gaz_dbi_get_row($gTables['movmag'], 'artico', $codice, " AND caumag = 99 AND datdoc = '" . $form['data_rettifica'] . "'")) {
    // come nell'inventario di GAzie: un solo inventario al giorno per articolo
    $errore = "Esiste gia' una rettifica o un inventario di questo articolo in data " . gaz_format_date($form['data_rettifica']) . ': scegliere un\'altra data.';
  } else {
    $nuova = round(floatval($form['nuova_quantita']), 3);
    $alla_data = magazzino_facile_giacenza($codice, $form['data_rettifica']);
    $delta = round($nuova - $alla_data, 3);
    if (abs($delta) < 0.001) {
      $errore = 'La quantita\' inserita e\' uguale alla giacenza: nessuna rettifica necessaria.';
    } else {
      $cau99 = gaz_dbi_get_row($gTables['caumag'], 'codice', 99);
      $prezzo = floatval($artico['last_cost']);
      movmagInsert(array('caumag' => 98,
        'operat' => ($delta > 0) ? 1 : -1,
        'datreg' => $form['data_rettifica'],
        'tipdoc' => 'INV',
        'desdoc' => 'Rettifica giacenza (' . ($delta > 0 ? '+' : '') . gaz_format_quantity($delta) . ')',
        'datdoc' => $form['data_rettifica'],
        'artico' => $codice,
        'quanti' => abs($delta),
        'prezzo' => $prezzo));
      movmagInsert(array('caumag' => 99,
        'operat' => 1,
        'datreg' => $form['data_rettifica'],
        'tipdoc' => 'INV',
        'desdoc' => ($cau99 ? $cau99['descri'] : 'Inventario'),
        'datdoc' => $form['data_rettifica'],
        'artico' => $codice,
        'quanti' => $nuova,
        'prezzo' => $prezzo));
      $esito = 'Rettifica registrata: ' . ($delta > 0 ? '+' : '') . gaz_format_quantity($delta)
        . ' (giacenza al ' . gaz_format_date($form['data_rettifica']) . ': ' . gaz_format_quantity($nuova)
        . ', giacenza attuale: ' . gaz_format_quantity(magazzino_facile_giacenza($codice)) . ').';
    }
  }
}

require("../../library/include/header.php");
$script_transl = HeadMain();

echo "<div align=\"center\" class=\"FacetFormHeaderFont\">Rettifica giacenza</div>";
if (!$artico) {
  echo "<div class=\"alert alert-danger\">Articolo non trovato.</div>";
} elseif ($bloccato != '') {
  echo "<div class=\"alert alert-warning\">" . htmlspecialchars($codice . ' - ' . $artico['descri']) . "<br>" . htmlspecialchars($bloccato) . "</div>";
  echo "<div class=\"text-center\"><button type=\"button\" class=\"btn btn-default\" onclick=\"window.close();\">Chiudi</button></div>";
} elseif ($esito != '') {
  echo "<div class=\"alert alert-success\">" . htmlspecialchars($codice . ' - ' . $artico['descri']) . "<br>" . htmlspecialchars($esito) . "</div>";
  echo "<div class=\"text-center\"><button type=\"button\" class=\"btn btn-default\" onclick=\"window.close();\">Chiudi</button></div>";
  // aggiorno la pagina da cui e' stata aperta la rettifica
  echo "<script>if (window.opener && !window.opener.closed) { window.opener.location.reload(); }</script>";
} else {
  $attuale = magazzino_facile_giacenza($codice);
  if ($form['nuova_quantita'] === '') {
    $form['nuova_quantita'] = round($attuale, 3);
  }
  $art_js = json_encode($codice . ' - ' . $artico['descri'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<form method="POST" action="rettifica_giacenza.php" id="form_rettifica">
<input type="hidden" name="codice" value="<?php echo htmlspecialchars($codice); ?>">
<table class="Tmiddle table-striped" align="center">
<?php
  if ($errore != '') {
    echo '<tr><td colspan="2" class="FacetDataTDred">' . htmlspecialchars($errore) . '</td></tr>';
  }
?>
<tr><td class="FacetFieldCaptionTD">Articolo</td><td class="FacetDataTD"><?php echo htmlspecialchars($codice . ' - ' . $artico['descri']); ?></td></tr>
<tr><td class="FacetFieldCaptionTD">Giacenza attuale</td><td class="FacetDataTD"><?php echo gaz_format_quantity($attuale) . ' ' . htmlspecialchars((string)$artico['unimis']); ?></td></tr>
<tr><td class="FacetFieldCaptionTD">Nuova quantit&agrave;</td><td class="FacetDataTD"><input type="text" name="nuova_quantita" id="nuova_quantita" value="<?php echo htmlspecialchars($form['nuova_quantita']); ?>" maxlength="15" size="10" autofocus></td></tr>
<tr><td class="FacetFieldCaptionTD">Data rettifica</td><td class="FacetDataTD"><input type="date" name="data_rettifica" id="data_rettifica" value="<?php echo htmlspecialchars($form['data_rettifica']); ?>" max="<?php echo $oggi; ?>"></td></tr>
<tr><td colspan="2" class="FacetDataTD"><small>La nuova quantit&agrave; vale come inventario alla data scelta. Come nell'inventario di GAzie, gli altri movimenti dello stesso giorno non vengono conteggiati: conviene rettificare a fine giornata.</small></td></tr>
<tr><td colspan="2" class="FacetFooterTD text-right">
<input type="button" class="btn btn-default btn-sm" value="Annulla" onclick="window.close();">
<input type="submit" class="btn btn-warning btn-sm" name="Rettifica" value="Rettifica">
</td></tr>
</table>
</form>
<script>
  var nq = document.getElementById('nuova_quantita');
  if (nq) { nq.focus(); nq.select(); }
  document.getElementById('form_rettifica').addEventListener('submit', function (e) {
    var testo = 'Confermi la rettifica della giacenza di ' + <?php echo $art_js; ?> + '?\nNuova quantita\': ' + nq.value + '\nData: ' + document.getElementById('data_rettifica').value;
    if (!confirm(testo)) { e.preventDefault(); }
  });
</script>
<?php
}
require("../../library/include/footer.php");
?>
