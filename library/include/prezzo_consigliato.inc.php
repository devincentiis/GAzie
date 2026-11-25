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
  Prezzo visibile e scrivibile, costo d'acquisto e prezzo consigliato nel
  riquadro di inserimento rigo dei documenti di vendita (admin_docven.php,
  admin_broven.php). Si attiva in Configurazione avanzata azienda con
  prezzo_consigliato = 1.
  - Acquisto: ultimo costo dell'articolo (artico.last_cost)
  - Prezzo consigliato: costo + ricarico % del raggruppamento statistico
  - Prezzo: quello scritto nel riquadro vince sul listino
  - il costo corretto a mano va nella scheda articolo alla conferma di un
    documento nuovo (non dai preventivi)
  Campi nascosti del riquadro: pc_codart (articolo a cui si riferiscono
  prezzo e costo), pc_costo (0 = dalla scheda, 1 = scritto a mano,
  2 = probabile, calcolato dal prezzo), pc_costi (costi scritti a mano nei
  righi gia' inseriti, in JSON).
*/

// true se l'azienda ha attivato la funzione
function prezzo_consigliato_attivo() {
  global $gTables;
  static $attivo = null;
  if ($attivo === null) {
    $r = gaz_dbi_get_row($gTables['company_config'], 'var', 'prezzo_consigliato');
    $attivo = ($r && trim($r['val']) == '1');
  }
  return $attivo;
}

// numero da un campo di testo (accetta la virgola); vuoto o non valido = 0
function prezzo_consigliato_numero($valore) {
  $valore = str_replace(',', '.', trim((string)$valore));
  return is_numeric($valore) ? floatval($valore) : 0;
}

// articolo a cui si riferiscono i campi del riquadro arrivati col POST
function prezzo_consigliato_codart_post() {
  return isset($_POST['pc_codart']) ? substr((string)$_POST['pc_codart'], 0, 32) : '';
}

/* prezzo scritto nel riquadro per l'articolo del rigo, false se non c'e'
   (prezzo a zero, oppure riferito a un altro articolo, es. lettore barcode) */
function prezzo_consigliato_scritto($codart, $prezzo) {
  if (!prezzo_consigliato_attivo() || $codart === '' || prezzo_consigliato_codart_post() !== (string)$codart) {
    return false;
  }
  $prezzo = prezzo_consigliato_numero($prezzo);
  return ($prezzo > 0.00001) ? $prezzo : false;
}

// costi scritti a mano nei righi gia' inseriti: array codart => costo
function prezzo_consigliato_costi_scritti() {
  $costi = array();
  $dati = isset($_POST['pc_costi']) ? json_decode((string)$_POST['pc_costi'], true) : null;
  if (is_array($dati)) {
    foreach ($dati as $codart => $costo) {
      if (count($costi) >= 500) {
        break;
      }
      $codart = substr((string)$codart, 0, 32);
      $costo = is_numeric($costo) ? floatval($costo) : 0;
      if ($codart !== '' && $costo > 0) {
        $costi[$codart] = $costo;
      }
    }
  }
  // rigo appena inserito con il costo scritto a mano
  if (isset($_POST['in_submit'], $_POST['in_codart'], $_POST['pc_costo'], $_POST['in_acquisto']) && $_POST['pc_costo'] === '1') {
    $codart = substr((string)$_POST['in_codart'], 0, 32);
    $costo = prezzo_consigliato_numero($_POST['in_acquisto']);
    $tiprig = isset($_POST['in_tiprig']) ? intval($_POST['in_tiprig']) : 0;
    if ($codart !== '' && $codart === prezzo_consigliato_codart_post() && ($tiprig == 0 || $tiprig == 50) && $costo > 0) {
      $costi[$codart] = $costo;
    }
  }
  return $costi;
}

/* campi del riquadro: Prezzo (id e name in_prelis), Acquisto, Prezzo consigliato
   e i campi nascosti; $tabella = true per una riga di tabella (admin_broven),
   altrimenti una riga bootstrap (admin_docven) */
function prezzo_consigliato_campi($form, $tabella = false) {
  global $admin_aziend;
  if (!prezzo_consigliato_attivo()) {
    return;
  }
  $codart = (string)$form['in_codart'];
  $codart_post = prezzo_consigliato_codart_post();
  // rigo appena portato nel riquadro per modificarlo: il suo prezzo resta
  $rigo_caricato = (substr($form['in_status'], 0, 6) == 'UPDROW' && (!isset($_POST['in_status']) || $_POST['in_status'] !== $form['in_status']));
  $stesso = ($codart !== '' && $codart === $codart_post && !$rigo_caricato);
  $riferimento = ($codart === '') ? '' : ($rigo_caricato ? $codart : $codart_post);
  $acquisto = ($stesso && isset($_POST['in_acquisto'])) ? substr((string)$_POST['in_acquisto'], 0, 15) : '';
  $stato = ($stesso && isset($_POST['pc_costo']) && in_array($_POST['pc_costo'], array('0', '1', '2'), true)) ? $_POST['pc_costo'] : '0';
  $costi = prezzo_consigliato_costi_scritti();
  $h = function ($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  };
  $tit_prezzo = 'Prezzo di vendita: se scritto vince sul listino';
  $tit_acquisto = 'Ultimo costo di acquisto: se corretto a mano si salva nella scheda articolo alla conferma del documento nuovo (non dai preventivi)';
  $tit_consigliato = 'Costo di acquisto maggiorato del ricarico del raggruppamento statistico';
  $campi = array(
    array('Prezzo', '<input type="text" id="in_prelis" name="in_prelis" value="' . $h($form['in_prelis']) . '" maxlength="15" size="11" tabindex="5" title="' . $tit_prezzo . '" />'),
    array('Acquisto', '<input type="text" id="in_acquisto" name="in_acquisto" value="' . $h($acquisto) . '" maxlength="15" size="11" title="' . $tit_acquisto . '" />'),
    array('Prezzo consigliato', '<input type="text" id="in_prezzo_consigliato" value="" size="11" readonly="readonly" style="background-color:#f5f5f5;" title="' . $tit_consigliato . '" />')
  );
  $nascosti = '<input type="hidden" id="pc_codart" name="pc_codart" value="' . $h($riferimento) . '" />'
    . '<input type="hidden" id="pc_costo" name="pc_costo" value="' . $h($stato) . '" />'
    . '<input type="hidden" name="pc_costi" value="' . $h(json_encode($costi, JSON_FORCE_OBJECT)) . '" />';
  if ($tabella) {
    echo "<tr>\n";
    foreach ($campi as $i => $c) {
      echo '<td>' . $c[0] . ':&nbsp;' . $c[1] . ($i == 0 ? $nascosti : '') . "</td>\n";
    }
    echo "</tr>\n";
  } else {
    echo '<div class="row">' . "\n";
    foreach ($campi as $i => $c) {
      $id = ($i == 0) ? 'in_prelis' : (($i == 1) ? 'in_acquisto' : 'in_prezzo_consigliato');
      echo '<div class="form-group col-xs-12 col-sm-6 col-md-3"><label for="' . $id . '">' . $c[0] . ':</label> ' . $c[1] . ($i == 0 ? $nascosti : '') . "</div>\n";
    }
    echo "</div>\n";
  }
  $opzioni = array(
    'clfoco' => intval($form['clfoco']),
    'listin' => isset($form['listin']) ? intval($form['listin']) : 0,
    'decimali' => isset($admin_aziend['decimal_price']) ? intval($admin_aziend['decimal_price']) : 2
  );
  echo '<script type="text/javascript" src="../../js/custom/prezzo_consigliato.js"></script>' . "\n";
  echo '<script type="text/javascript">prezzoConsigliato(' . json_encode($opzioni) . ');</script>' . "\n";
}

// alla conferma di un documento nuovo: i costi scritti a mano vanno nella scheda articolo
function prezzo_consigliato_salva_costi($rows) {
  global $gTables;
  if (!prezzo_consigliato_attivo()) {
    return;
  }
  $costi = prezzo_consigliato_costi_scritti();
  foreach ($rows as $r) {
    if (!isset($r['tiprig'], $r['codart']) || !in_array(intval($r['tiprig']), array(0, 50)) || !isset($costi[$r['codart']])) {
      continue;
    }
    $artico = gaz_dbi_get_row($gTables['artico'], 'codice', $r['codart']);
    if ($artico && abs(floatval($artico['last_cost']) - $costi[$r['codart']]) > 0.00001) {
      gaz_dbi_put_row($gTables['artico'], 'codice', $r['codart'], 'last_cost', $costi[$r['codart']]);
    }
    unset($costi[$r['codart']]);
  }
  unset($_POST['pc_costi']); // una volta sola
}
