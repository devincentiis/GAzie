<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
  (https://www.devincentiis.it)
  <https://gazie.sourceforge.net>
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
  AUTORE: Andrea Biasi
  -------------------------------------------------------------------
 */


// fonti della ricerca globale: chiave => nome, icona, modulo e script che servono per vederla
function gsCatalog() {
    return array(
        'pagine'        => array('name' => 'Pagine del menu', 'icon' => 'fa-bars', 'module' => '', 'script' => ''),
        'codici'        => array('name' => 'Funzioni rapide (codici del menu)', 'icon' => 'fa-bolt', 'module' => '', 'script' => ''),
        'clienti'       => array('name' => 'Clienti', 'icon' => 'fa-user', 'module' => 'vendit', 'script' => 'admin_client'),
        'fornitori'     => array('name' => 'Fornitori', 'icon' => 'fa-truck', 'module' => 'acquis', 'script' => 'admin_fornit'),
        'articoli'      => array('name' => 'Articoli', 'icon' => 'fa-cube', 'module' => 'magazz', 'script' => 'admin_artico'),
        'docven'        => array('name' => 'Documenti di vendita', 'icon' => 'fa-file-text-o', 'module' => 'vendit', 'script' => 'admin_docven'),
        'docacq'        => array('name' => 'Documenti di acquisto', 'icon' => 'fa-file-o', 'module' => 'acquis', 'script' => 'admin_docacq'),
        'broven'        => array('name' => 'Preventivi e ordini di vendita', 'icon' => 'fa-shopping-cart', 'module' => 'vendit', 'script' => 'admin_broven'),
        'broacq'        => array('name' => 'Preventivi e ordini di acquisto', 'icon' => 'fa-shopping-basket', 'module' => 'acquis', 'script' => 'admin_broacq'),
        'contratti'     => array('name' => 'Contratti', 'icon' => 'fa-handshake-o', 'module' => 'vendit', 'script' => 'admin_contract'),
        'assistenze'    => array('name' => 'Assistenze', 'icon' => 'fa-wrench', 'module' => 'suppor', 'script' => 'admin_assist'),
        // fase 2 (le righe dei documenti si cercano solo nella pagina completa dei risultati)
        'righe_ven'     => array('name' => 'Righe dei documenti di vendita', 'icon' => 'fa-list', 'module' => 'vendit', 'script' => 'admin_docven', 'page_only' => true),
        'righe_acq'     => array('name' => 'Righe dei documenti di acquisto', 'icon' => 'fa-list', 'module' => 'acquis', 'script' => 'admin_docacq', 'page_only' => true),
        'righe_broven'  => array('name' => 'Righe di preventivi e ordini di vendita', 'icon' => 'fa-list', 'module' => 'vendit', 'script' => 'admin_broven', 'page_only' => true),
        'righe_broacq'  => array('name' => 'Righe di preventivi e ordini di acquisto', 'icon' => 'fa-list', 'module' => 'acquis', 'script' => 'admin_broacq', 'page_only' => true),
        'movcon'        => array('name' => 'Movimenti contabili', 'icon' => 'fa-book', 'module' => 'contab', 'script' => 'admin_movcon'),
        'movmag'        => array('name' => 'Movimenti di magazzino', 'icon' => 'fa-exchange', 'module' => 'magazz', 'script' => 'admin_movmag'),
        'installazioni' => array('name' => 'Installazioni', 'icon' => 'fa-desktop', 'module' => 'suppor', 'script' => 'admin_install'),
        'personale'     => array('name' => 'Personale', 'icon' => 'fa-users', 'module' => 'humres', 'script' => 'admin_staff'),
        'produzioni'    => array('name' => 'Produzioni e commesse', 'icon' => 'fa-industry', 'module' => 'orderman', 'script' => 'admin_orderman'),
    );
}

// l'utente può vedere la fonte se ha accesso al modulo e lo script di destinazione non è tra quelli esclusi
function gsCanSee($module,$script,$user) {
  global $admin_aziend;
  if ($module === '') return true;
  $rights = checkAccessRights($user, $module,$admin_aziend['company_id']);
  if ($rights == 0) return false;
  if (is_array($rights) && in_array($script, $rights)) return false;
  return true;
}

// funzioni rapide: voci di menu (secondo e terzo livello) il cui codice trans_code inizia con il testo digitato.
// Restituisce le righe per la tendina e, se il testo coincide esattamente con un codice, l'indirizzo da aprire subito in $exactUrl.
function gsQuickCodes($q, $limit, &$exactUrl,$user) {
    global $gTables, $admin_aziend;
    $exactUrl = null;
    if ($q !== '' && !preg_match('/^[\pL\pN*?%]+$/u', $q)) return array(); // i codici non contengono spazi né simboli; testo vuoto = tutti i codici
    $t = gaz_dbi_real_escape_string(strtr($q, array('*' => '%', '?' => '_')));
    $pattern = "'" . trim($t, '%') . "%'";
    $tables = array(
        3 => $gTables['menu_script'] . " s LEFT JOIN " . $gTables['menu_module'] . " mm ON s.id_menu = mm.id LEFT JOIN " . $gTables['module'] . " m ON mm.id_module = m.id",
        2 => $gTables['menu_module'] . " s LEFT JOIN " . $gTables['module'] . " m ON s.id_module = m.id",
    );
    $names = array();   // traduzioni dei menu già caricate, per modulo
    $rows = array();
    foreach ($tables as $level => $from) {
        $rs = gaz_dbi_dyn_query("s.link AS link, s.translate_key AS tk, s.accesskey AS ak, s.trans_code AS code, m.name AS modname", $from,
            "s.trans_code IS NOT NULL AND s.trans_code <> '' AND s.trans_code LIKE " . $pattern, "s.trans_code", 0, $limit);
        while ($r = gaz_dbi_fetch_array($rs)) {
            $mod = (string) $r['modname'];
            if ($mod === '' || (int) $admin_aziend['Abilit'] < (int) $r['ak']) continue;
            $script = pathinfo(parse_url($r['link'], PHP_URL_PATH), PATHINFO_FILENAME);
            if (!gsCanSee($mod, $script,$user)) continue;
            if (!isset($names[$mod])) {
                $transl = array();
                $file = __DIR__ . '/../' . $mod . '/menu.' . $admin_aziend['lang'] . '.php';
                if (is_file($file)) include $file; // definisce $transl[$mod]
                $names[$mod] = isset($transl[$mod]) ? $transl[$mod] : array();
            }
            $tr = $names[$mod];
            $key = 'm' . $level;
            $label = isset($tr[$key][$r['tk']]) ? $tr[$key][$r['tk']] : array($r['link'], $r['link']);
            $url = '../' . $mod . '/' . $r['link'];
            // le traduzioni del menu possono contenere entità HTML (es. &agrave;): qui servono testo semplice
            $dec = function ($s) { return html_entity_decode(stripslashes(trim($s)), ENT_QUOTES | ENT_HTML5, 'UTF-8'); };
            $rows[] = array('code' => $r['code'], 'label' => $dec($label[1] !== '' ? $label[1] : $label[0]), 'title' => $r['code'] . ' - ' . $dec($label[1] !== '' ? $label[1] : $label[0]),
                            'detail' => $dec(isset($tr['name']) ? $tr['name'] : $mod),
                            'url' => $url);
            if (strcasecmp($r['code'], $q) === 0) $exactUrl = $url;
        }
    }
    return array_slice($rows, 0, $limit);
}


// ==================================================================
// INIZIO FUNZIONI PER TEMI CHE SI APPOGGIANO SU FILES (adminlte_clean)
/*
  GAzie - ricerca globale: elenco delle fonti e impostazioni dell'utente.
  Le fonti escluse dall'utente sono salvate in data/files/<company_id>/<user_name>_ricerca.json, come i preferiti.
*/

function gsSettingsFile($companyId, $user) {
    $name = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $user);
    return __DIR__ . '/../../data/files/' . (int) $companyId . '/' . $name . '_ricerca.json';
}

// impostazioni dell'utente: 'disabled' = fonti tolte dalla ricerca, 'dropdown' = fonti "solo pagina completa" mostrate anche nella tendina
function gsSettingsFor($companyId, $user) {
    $file = gsSettingsFile($companyId, $user);
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    $out = array('disabled' => array(), 'dropdown' => array());
    foreach ($out as $k => $v) {
        if (is_array($data) && isset($data[$k]) && is_array($data[$k])) $out[$k] = $data[$k];
    }
    return $out;
}

function gsSaveSettings($companyId, $user, $settings) {
    $file = gsSettingsFile($companyId, $user);
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0775, true);
    }
    // oltre alle fonti, le opzioni che si possono disattivare (barcode_doc = codice articolo esatto inserito nel documento aperto)
    $known = array_merge(array_keys(gsCatalog()), array('barcode_doc'));
    $data = array(
        'disabled' => array_values(array_intersect($known, (array) ($settings['disabled'] ?? array()))),
        'dropdown' => array_values(array_intersect($known, (array) ($settings['dropdown'] ?? array()))),
    );
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// chiavi delle fonti che l'utente ha tolto dalla ricerca
function gsDisabledFor($companyId, $user) {
    $s = gsSettingsFor($companyId, $user);
    return $s['disabled'];
}

function gsSaveDisabled($companyId, $user, $disabled) {
    $s = gsSettingsFor($companyId, $user);
    $s['disabled'] = $disabled;
    gsSaveSettings($companyId, $user, $s);
}
// FINE FUNZIONI PER TEMI CHE SI APPOGGIANO SU FILES (adminlte_clean)
// ==================================================================


// ==================================================================
// INIZIO FUNZIONI PER TEMI CHE SI APPOGGIANO SU DATABASE (lte)
/*
  GAzie - ricerca globale: elenco delle fonti e impostazioni dell'utente.
  Le fonti scelte per l'utente sono salvate nella tabella gaz_breadcrumb con mode = 5 la logica è inversa: la ricerca avviene solo se presente sulla tabella colonna "file"
*/

// impostazioni dell'utente: 'disabled' = fonti tolte dalla ricerca, 'dropdown' = fonti "solo pagina completa" mostrate anche nella tendina
function gsSettingsForDb($companyId, $user) {
    global $gTables, $admin_aziend;
    $out=[];
    $out['disabled'] = gsCatalog(); // prima disabilito tutto
    $out['disabled']['barcode_doc'] = []; // accodo la scelta per invio return automatico
    $out['dropdown'] = []; // e nessun dropdown
    $rs = gaz_dbi_dyn_query("*",$gTables['breadcrumb'],"codice_aziend = ".$companyId." AND adminid = '".$user."' AND exec_mode = 5");
    while ($r = gaz_dbi_fetch_array($rs)) {
      unset($out['disabled'][$r['file']]); // se è stato abilitato lo elimino dalla matrice dei disabilitati
      if ($r['position_order']==1){
        $out['dropdown'][]=$r['file']; // se si vuole la tendina
      }
    }
    $out['disabled'] = array_keys($out['disabled']);
    return $out;
}

function gsSaveSettingsDb($companyId, $user, $settings) {
    // oltre alle fonti, le opzioni che si possono disattivare (barcode_doc = codice articolo esatto inserito nel documento aperto)
    $known = array_merge(array_keys(gsCatalog()), array('barcode_doc'));
    $data = array(
        'disabled' => array_values(array_intersect($known, (array) ($settings['disabled'] ?? array()))),
        'dropdown' => array_values(array_intersect($known, (array) ($settings['dropdown'] ?? array()))),
    );
    file_put_contents($y_row, json_encode($data, JSON_PRETTY_PRINT));
}

// chiavi delle fonti che l'utente ha tolto dalla ricerca
function gsDisabledForDb($companyId, $user) {
    $s = gsSettingsForDb($companyId, $user);
    return $s['disabled'];
}

function gsSaveDisabledDb($companyId, $user, $disabled) {
    $s = gsSettingsForDb($companyId, $user);
    $s['disabled'] = $disabled;
    gsSaveSettingsDb($companyId, $user, $s);
}
// FINE FUNZIONI PER TEMI CHE SI APPOGGIANO SU FILES (adminlte_clean)
// ==================================================================
