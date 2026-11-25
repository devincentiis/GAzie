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
  Inserimento rapido da tastiera nei documenti di vendita e tasti rapidi:
  F5 nuovo DDT, F6 nuova fattura, F7 nuovo ordine cliente (da qualsiasi pagina),
  F10 conferma del documento (in DDT, fatture, ordini e preventivi).
  Si attiva in Configurazione avanzata azienda con inserimento_rapido = 1.
*/

// true se l'azienda ha attivato l'inserimento rapido
function inserimento_rapido_attivo() {
  global $gTables;
  static $attivo = null;
  if ($attivo === null) {
    $r = gaz_dbi_get_row($gTables['company_config'], 'var', 'inserimento_rapido');
    $attivo = ($r && trim($r['val']) == '1');
  }
  return $attivo;
}

// tasti rapidi delle voci di menu (link come in gaz_menu_script)
function inserimento_rapido_tasti() {
  return array(
    'admin_docven.php?Insert&tipdoc=DDT' => 'F5',
    'admin_docven.php?Insert&tipdoc=FAI' => 'F6',
    'admin_broven.php?Insert&tipdoc=VOR' => 'F7'
  );
}

// aggiunge " (F5)" ecc. alle voci di menu dei documenti con tasto rapido;
// da chiamare nel ciclo che costruisce il menu, dopo aver caricato menu.<lingua>.php
function inserimento_rapido_menu(&$transl, $row) {
  if ($row['name'] != 'vendit' || empty($row['m3_link']) || !inserimento_rapido_attivo()) {
    return;
  }
  $tasti = inserimento_rapido_tasti();
  if (!isset($tasti[$row['m3_link']]) || !isset($transl['vendit']['m3'][$row['m3_trkey']])) {
    return;
  }
  $suffisso = ' (' . $tasti[$row['m3_link']] . ')';
  foreach ($transl['vendit']['m3'][$row['m3_trkey']] as $i => $testo) {
    if (is_string($testo) && substr($testo, -strlen($suffisso)) !== $suffisso) {
      $transl['vendit']['m3'][$row['m3_trkey']][$i] = $testo . $suffisso;
    }
  }
}

// script dei tasti F5, F6, F7 (va nell'intestazione di ogni pagina)
function inserimento_rapido_script_tasti() {
  if (inserimento_rapido_attivo()) {
    echo '<script type="text/javascript" src="../../js/custom/tasti_rapidi.js"></script>' . "\n";
  }
}

/* script dell'inserimento rapido nei documenti; $fuoco = 'cliente', 'articolo' o ''
   (dove mettere il cursore all'apertura), $sposta_pannello = true per portare
   il riquadro di inserimento sopra i righi */
function inserimento_rapido_script_documento($fuoco, $sposta_pannello = false) {
  if (!inserimento_rapido_attivo()) {
    return;
  }
  $opzioni = array('fuoco' => $fuoco, 'spostaPannello' => (bool)$sposta_pannello);
  echo '<script type="text/javascript" src="../../js/custom/inserimento_rapido.js"></script>' . "\n";
  echo '<script type="text/javascript">inserimentoRapido(' . json_encode($opzioni) . ');</script>' . "\n";
}
