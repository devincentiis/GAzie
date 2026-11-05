<?php
/*
  GAzie - ricerca globale: elenco delle fonti e impostazioni dell'utente.
  Le fonti escluse dall'utente sono salvate in data/files/<company_id>/<user_name>_ricerca.json, come i preferiti.
*/

// fonti della ricerca globale: chiave => nome, icona, modulo e script che servono per vederla
function gsCatalog() {
    return array(
        'pagine'        => array('name' => 'Pagine del menu', 'icon' => 'fa-bars', 'module' => '', 'script' => ''),
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
        'dipendenti'    => array('name' => 'Dipendenti', 'icon' => 'fa-id-badge', 'module' => 'humres', 'script' => 'admin_staff'),
        'produzioni'    => array('name' => 'Produzioni e commesse', 'icon' => 'fa-industry', 'module' => 'orderman', 'script' => 'admin_orderman'),
    );
}

// l'utente può vedere la fonte se ha accesso al modulo e lo script di destinazione non è tra quelli esclusi
function gsCanSee($module, $script) {
    global $admin_aziend;
    if ($module === '') return true;
    $rights = checkAccessRights($admin_aziend['user_name'], $module, $admin_aziend['company_id']);
    if ($rights == 0) return false;
    if (is_array($rights) && in_array($script, $rights)) return false;
    return true;
}

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
    $known = array_keys(gsCatalog());
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
