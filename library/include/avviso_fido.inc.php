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
  Avviso fido nei documenti di vendita: scelto il cliente, in alto una striscia
  con l'esposizione (partite aperte dello scadenzario, di cui scaduto) piu' il
  documento in corso, confrontata con il fido (clfoco.max_overdraft), e il
  livello di affidabilita' (clfoco.commercial_reliability). Solo un avviso,
  non blocca nulla. Si attiva in Configurazione avanzata azienda con avviso_fido = 1.
*/

// soglia (frazione del fido) oltre la quale la striscia diventa gialla
define('AVVISO_FIDO_SOGLIA_GIALLO', 0.8);

// true se l'azienda ha attivato l'avviso fido
function avviso_fido_attivo() {
  global $gTables;
  static $attivo = null;
  if ($attivo === null) {
    $r = gaz_dbi_get_row($gTables['company_config'], 'var', 'avviso_fido');
    $attivo = ($r && trim($r['val']) == '1');
  }
  return $attivo;
}

// testi dai file lang del modulo vendite (sezione avviso_fido e livelli di affidabilita' della scheda cliente)
function avviso_fido_testi() {
  global $admin_aziend;
  $lingue = array('italian'); // se la lingua dell'azienda non ha i testi uso l'italiano
  if (!empty($admin_aziend['lang']) && $admin_aziend['lang'] != 'italian') {
    array_unshift($lingue, $admin_aziend['lang']);
  }
  foreach ($lingue as $lang) {
    $file = __DIR__ . '/../../modules/vendit/lang.' . basename($lang) . '.php';
    $strScript = array();
    if (is_file($file)) {
      include($file);
    }
    if (isset($strScript['avviso_fido'])) {
      $t = $strScript['avviso_fido'];
      $t['livelli'] = isset($strScript['admin_client.php']['commercial_reliability_value']) ? $strScript['admin_client.php']['commercial_reliability_value'] : array();
      return $t;
    }
  }
  return array('ok' => '', 'quasi' => '', 'superato' => '', 'esposizione' => '', 'scaduto' => '', 'documento' => '', 'su_fido' => '', 'affidabilita' => '', 'livelli' => array());
}

// esposizione del cliente dallo scadenzario (paymov) con la classe Schedule:
// aperture meno chiusure (pagamenti e note di credito non abbinati compresi), e quanto e' scaduto
function avviso_fido_esposizione($clfoco) {
  $sched = new Schedule;
  $sched->getPartnerStatus($clfoco);
  $saldo = 0;
  $scaduto = 0;
  foreach ($sched->PartnerStatus as $partita) {
    foreach ($partita as $rata) {
      $aperto = round($rata['op_val'] - $rata['cl_val'], 2);
      $saldo += $aperto;
      if ($aperto >= 0.01 && $rata['status'] == 3) { // rata scaduta e non pagata
        $scaduto += $aperto;
      }
    }
  }
  $esposizione = max(0, round($saldo, 2));
  return array('esposizione' => $esposizione, 'scaduto' => min(round($scaduto, 2), $esposizione));
}

// totale IVA compresa dei righi del documento in compilazione (righi normali, forfait e cespiti)
function avviso_fido_totale_documento($rows, $sconto_chiusura) {
  $tot = 0;
  if (!is_array($rows)) {
    return 0;
  }
  foreach ($rows as $r) {
    if (!isset($r['tiprig'], $r['prelis'])) {
      continue;
    }
    if ($r['tiprig'] == 0) {
      $imp = CalcolaImportoRigo($r['quanti'], $r['prelis'], array($r['sconto'], $sconto_chiusura));
    } elseif ($r['tiprig'] == 1 || $r['tiprig'] == 90) {
      $imp = CalcolaImportoRigo(1, $r['prelis'], $sconto_chiusura);
    } else {
      continue;
    }
    $tot += $imp * (1 + (isset($r['pervat']) ? floatval($r['pervat']) : 0) / 100);
  }
  return round($tot, 2);
}

/*
  HTML della striscia (stringa vuota se non c'e' nulla da mostrare).
  $tipdoc: con FNC (nota di credito) il documento riduce l'esposizione;
  $id_tesdoc: documento di vendita gia' salvato, se contabilizzato e' gia' nelle partite aperte.
*/
function avviso_fido_striscia($clfoco, $rows, $sconto_chiusura, $tipdoc = '', $id_tesdoc = 0) {
  global $gTables;
  if ($clfoco < 100000000) {
    return '';
  }
  $cli = gaz_dbi_get_row($gTables['clfoco'], 'codice', $clfoco);
  if (!$cli) {
    return '';
  }
  $fido = floatval($cli['max_overdraft']);
  $affid = intval($cli['commercial_reliability']);
  $e = avviso_fido_esposizione($clfoco);
  $doc = avviso_fido_totale_documento($rows, $sconto_chiusura);
  if ($id_tesdoc > 0) {
    $tes = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', intval($id_tesdoc));
    if ($tes && $tes['id_con'] > 0) {
      $doc = 0;
    }
  }
  if ($tipdoc == 'FNC') {
    $doc = -$doc;
  }
  $tot = max(0, round($e['esposizione'] + $doc, 2));
  // senza fido mostro solo l'esposizione, e solo se c'e' qualcosa di aperto o l'affidabilita'
  if ($fido < 0.01 && $e['esposizione'] < 0.01 && $affid <= 0) {
    return '';
  }
  $t = avviso_fido_testi();
  $classe = 'alert-info';
  $html = '';
  if ($fido >= 0.01) {
    if ($tot > $fido) {
      $classe = 'alert-danger';
      $html .= '<b>' . $t['superato'] . '</b>';
    } elseif ($tot > $fido * AVVISO_FIDO_SOGLIA_GIALLO) {
      $classe = 'alert-warning';
      $html .= '<b>' . $t['quasi'] . '</b>';
    } else {
      $classe = 'alert-success';
      $html .= $t['ok'];
    }
    $html .= ' &mdash; ';
  }
  $html .= $t['esposizione'] . ': &euro; ' . gaz_format_number($e['esposizione']);
  if ($e['scaduto'] >= 0.01) {
    $html .= ' (' . $t['scaduto'] . ': <b>&euro; ' . gaz_format_number($e['scaduto']) . '</b>)';
  }
  if (abs($doc) >= 0.01) {
    $html .= ' ' . ($doc < 0 ? '&minus;' : '+') . ' ' . $t['documento'] . ': &euro; ' . gaz_format_number(abs($doc)) . ' = &euro; ' . gaz_format_number($tot);
  }
  if ($fido >= 0.01) {
    $html .= ' ' . $t['su_fido'] . ' &euro; ' . gaz_format_number($fido) . ' (' . round($tot / $fido * 100) . '%)';
  }
  if ($affid > 0) {
    $liv = isset($t['livelli'][$affid]) ? $t['livelli'][$affid] : $affid;
    $classe_liv = ($affid <= 5) ? 'label-danger' : (($affid <= 7) ? 'label-warning' : 'label-success');
    if ($affid <= 5) { // cliente inaffidabile: striscia rossa anche entro il fido
      $classe = 'alert-danger';
    }
    $html .= ' &nbsp;<span class="label ' . $classe_liv . '" style="font-size:95%;">' . $t['affidabilita'] . ': ' . htmlspecialchars($liv) . '</span>';
  }
  // la striscia resta visibile: quando esce dallo schermo ne compare una copia fissata in cima
  return '<div id="avviso_fido" class="alert ' . $classe . '" role="alert" style="font-size:108%;margin-bottom:4px;"><span class="glyphicon glyphicon-euro" aria-hidden="true"></span> ' . $html . '</div>
<script>
(function() {
  var b = document.getElementById("avviso_fido");
  if (!b) { return; }
  var c = b.cloneNode(true);
  c.id = "avviso_fido_fisso";
  c.style.cssText = "position:fixed;top:0;left:0;right:0;z-index:3000;margin:0;border-radius:0;display:none;box-shadow:0 2px 6px rgba(0,0,0,0.3);font-size:108%;";
  document.body.appendChild(c);
  var controlla = function() {
    var r = b.getBoundingClientRect();
    c.style.display = (r.bottom < 0 || r.top > window.innerHeight) ? "block" : "none";
  };
  window.addEventListener("scroll", controlla);
  window.addEventListener("resize", controlla);
  controlla();
})();
</script>
';
}
