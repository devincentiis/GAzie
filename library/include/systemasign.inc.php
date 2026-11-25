<?php
/*
  SystemaSign per GAzie - funzioni comuni (25/11/2026).
  GAzie si collega all'istanza SystemaSign come un "PC" (invito monouso -> chiave propria) e le manda i DDT:
  PDF fatto da GAzie stesso + dati veri del documento (numero, data, cliente, righe) + destinazione (autista, banco, solo collegamento).
  Impostazioni in company_config (systemasign_url, systemasign_codice, systemasign_chiave);
  lo stato dei DDT mandati nella tabella <prefisso>systemasign (creata al primo uso).
*/

define('SYSTEMASIGN_VERSIONE', 'gazie-1.0');
define('SYSTEMASIGN_RICHIESTE', 'https://systemasign.com/richiesta.php');

/* richiesta di informazioni e listino: parte solo quando l'utente la manda dal riquadro della pagina, coi dati che vede */
function ss_richiedi_listino($dati) {
    $ch = curl_init(SYSTEMASIGN_RICHIESTE);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($dati),
        CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Accept: application/json'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ));
    $corpo = curl_exec($ch);
    $codice = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errore = curl_error($ch);
    curl_close($ch);
    if ($corpo === false) {
        return 'servizio non raggiungibile (' . $errore . '): scrivi a richieste@systemasign.com';
    }
    $r = json_decode($corpo, true);
    if ($codice == 200 && is_array($r) && !empty($r['ok'])) {
        return '';
    }
    return is_array($r) && !empty($r['errore']) ? $r['errore'] : 'richiesta non riuscita (' . $codice . ')';
}

function ss_cfg($var) {
    global $gTables;
    $r = gaz_dbi_get_row($gTables['company_config'], 'var', $var);
    return $r ? (string)$r['val'] : '';
}

function ss_cfg_metti($var, $val, $descrizione) {
    global $gTables;
    $esc = gaz_dbi_real_escape_string($val);
    if (gaz_dbi_get_row($gTables['company_config'], 'var', $var)) {
        gaz_dbi_query("UPDATE " . $gTables['company_config'] . " SET val='" . $esc . "' WHERE var='" . gaz_dbi_real_escape_string($var) . "'");
    } else {
        gaz_dbi_query("INSERT INTO " . $gTables['company_config'] . " (description, var, val) VALUES ('" . gaz_dbi_real_escape_string($descrizione) .
            "', '" . gaz_dbi_real_escape_string($var) . "', '" . $esc . "')");
    }
}

function ss_collegato() {
    return ss_cfg('systemasign_url') !== '' && ss_cfg('systemasign_chiave') !== '';
}

function ss_tabella() {
    global $gTables;
    $t = str_replace('tesdoc', 'systemasign', $gTables['tesdoc']);
    static $fatta = false;
    if (!$fatta) {
        gaz_dbi_query("CREATE TABLE IF NOT EXISTS " . $t . " (
            id_tes INT NOT NULL PRIMARY KEY,
            ss_id INT NOT NULL,
            stato VARCHAR(20) NOT NULL DEFAULT 'da_firmare',
            link VARCHAR(255) NOT NULL DEFAULT '',
            destinazione VARCHAR(100) NOT NULL DEFAULT '',
            firmatario VARCHAR(120) NOT NULL DEFAULT '',
            inviato_il DATETIME NULL,
            aggiornato_il DATETIME NULL
        ) DEFAULT CHARSET=utf8");
        $fatta = true;
    }
    return $t;
}

/* stato dei DDT gia' mandati: id_tes => stato (per i pulsanti dell'elenco); vuoto se SystemaSign non e' collegato */
function ss_stati_ddt() {
    if (!ss_collegato()) {
        return array();
    }
    $out = array();
    $rs = gaz_dbi_query("SELECT id_tes, stato FROM " . ss_tabella());
    while ($r = gaz_dbi_fetch_assoc($rs)) {
        $out[(int)$r['id_tes']] = $r['stato'];
    }
    return $out;
}

function ss_riga($id_tes) {
    $rs = gaz_dbi_query("SELECT * FROM " . ss_tabella() . " WHERE id_tes=" . intval($id_tes));
    $r = gaz_dbi_fetch_assoc($rs);
    return $r ? $r : null;
}

function ss_salva($id_tes, $risposta, $destinazione = null) {
    $t = ss_tabella();
    $id = intval($id_tes);
    $campi = array(
        'ss_id' => intval($risposta['id']),
        'stato' => substr((string)$risposta['stato'], 0, 20),
        'link' => substr((string)$risposta['link'], 0, 255),
        'firmatario' => substr((string)(isset($risposta['firmatario']) ? $risposta['firmatario'] : ''), 0, 120),
    );
    if ($destinazione !== null) {
        $campi['destinazione'] = substr($destinazione, 0, 100);
    }
    $set = array();
    foreach ($campi as $k => $v) {
        $set[] = $k . "='" . gaz_dbi_real_escape_string((string)$v) . "'";
    }
    $set[] = "aggiornato_il=NOW()";
    if (ss_riga($id)) {
        gaz_dbi_query("UPDATE " . $t . " SET " . implode(', ', $set) . ($destinazione !== null ? ", inviato_il=NOW()" : "") . " WHERE id_tes=" . $id);
    } else {
        gaz_dbi_query("INSERT INTO " . $t . " SET id_tes=" . $id . ", " . implode(', ', $set) . ", inviato_il=NOW()");
    }
}

/* chiamata all'istanza: ritorna array(codice_http, corpo) con corpo gia' decodificato se JSON */
function ss_api($metodo, $percorso, $json = null, $intestazioni_pc = true) {
    $url = rtrim(ss_cfg('systemasign_url'), '/') . $percorso;
    $ch = curl_init($url);
    $h = array('Accept: application/json');
    if ($intestazioni_pc) {
        $h[] = 'X-PC: ' . ss_cfg('systemasign_codice');
        $h[] = 'X-Chiave: ' . ss_cfg('systemasign_chiave');
        $h[] = 'X-Versione: ' . SYSTEMASIGN_VERSIONE;
    }
    if ($json !== null) {
        $h[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
    }
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_HTTPHEADER => $h,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 90,
    ));
    $corpo = curl_exec($ch);
    $codice = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tipo = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    if ($corpo === false) {
        $corpo = array('ok' => false, 'errore' => 'istanza non raggiungibile: ' . curl_error($ch));
        $codice = 0;
    } elseif (strpos($tipo, 'json') !== false) {
        $dec = json_decode($corpo, true);
        $corpo = is_array($dec) ? $dec : array('ok' => false, 'errore' => 'risposta non valida');
    }
    curl_close($ch);
    return array($codice, $corpo);
}

/* collega GAzie all'istanza con l'invito del rivenditore */
function ss_collega($url, $invito, $nome) {
    $url = rtrim(trim($url), '/');
    if (!preg_match('#^https://[a-z0-9.-]+(:[0-9]+)?$#i', $url)) {
        return 'indirizzo non valido: deve essere tipo https://azienda.systemasign.com';
    }
    $codice = ss_cfg('systemasign_codice');
    if ($codice === '') {
        $codice = 'gazie-' . bin2hex(random_bytes(10));
    }
    ss_cfg_metti('systemasign_url', $url, 'SystemaSign: indirizzo dell\'istanza');
    list($c, $r) = ss_api('POST', '/api/agente/registra', array('codice' => $codice, 'nome' => $nome, 'utente' => 'gazie',
        'versione' => SYSTEMASIGN_VERSIONE, 'invito' => trim($invito)), false);
    if ($c != 200 || empty($r['chiave'])) {
        return is_array($r) && !empty($r['errore']) ? $r['errore'] : 'collegamento non riuscito (' . $c . ')';
    }
    ss_cfg_metti('systemasign_codice', $codice, 'SystemaSign: codice di questo GAzie');
    ss_cfg_metti('systemasign_chiave', $r['chiave'], 'SystemaSign: chiave di questo GAzie');
    return '';
}

/* i dati veri del DDT, come li stampa GAzie */
function ss_dati_ddt($testata) {
    global $gTables;
    $cliente = gaz_dbi_get_row($gTables['clfoco'] . ' LEFT JOIN ' . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id',
        $gTables['clfoco'] . '.codice', $testata['clfoco']);
    $nome = $cliente ? trim($cliente['ragso1'] . ' ' . $cliente['ragso2']) : '';
    $destinazione = trim((string)$testata['destin']);
    if ($destinazione === '' && intval($testata['id_des']) > 0) {
        $d = gaz_dbi_get_row($gTables['anagra'], 'id', intval($testata['id_des']));
        if ($d) {
            $destinazione = trim($d['ragso1'] . ' ' . $d['ragso2'] . ', ' . $d['indspe'] . ' ' . $d['citspe']);
        }
    }
    $righe = array();
    $rs = gaz_dbi_query("SELECT tiprig, codart, descri, unimis, quanti FROM " . $gTables['rigdoc'] . " WHERE id_tes=" . intval($testata['id_tes']) .
        " AND tiprig IN (0,1) ORDER BY id_rig");
    while ($r = gaz_dbi_fetch_assoc($rs)) {
        if (trim($r['descri']) === '') {
            continue;
        }
        $righe[] = array('codice' => trim($r['codart']), 'descrizione' => trim($r['descri']), 'um' => trim($r['unimis']),
            'quantita' => $r['tiprig'] == 0 ? floatval($r['quanti']) : null);
    }
    $codice = (string)$testata['clfoco'];
    // fattura accompagnatoria: numero e data della fattura
    $fattura = substr($testata['tipdoc'], 0, 1) == 'F';
    $numero = $fattura && intval($testata['numfat']) > 0 ? $testata['numfat'] : $testata['numdoc'];
    $data = $fattura && !empty($testata['datfat']) && $testata['datfat'] != '0000-00-00' ? $testata['datfat'] : $testata['datemi'];
    // email: quella usata per mandare il documento, altrimenti quella dell'anagrafica (non la PEC)
    $email = '';
    foreach (array(isset($testata['email']) ? $testata['email'] : '', $cliente ? $cliente['e_mail'] : '') as $e) {
        $e = trim((string)$e);
        if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
            $email = $e;
            break;
        }
    }
    return array(
        'numero' => $numero . '/' . $testata['seziva'],
        'data_doc' => substr($data, 8, 2) . '/' . substr($data, 5, 2) . '/' . substr($data, 0, 4),
        'cliente' => $nome,
        'cod_cliente' => strlen($codice) > 3 ? (string)intval(substr($codice, 3)) : $codice,
        'destinazione' => $destinazione,
        'righe' => $righe,
        'email' => $email,
        'cellulare' => $cliente ? ss_cellulare($cliente['cell'], $cliente['telefo']) : '',
    );
}

/* numero per WhatsApp: il cellulare, altrimenti il telefono se e' un cellulare italiano (3xx); col prefisso 39 */
function ss_cellulare($cell, $telefono) {
    foreach (array($cell, $telefono) as $n) {
        $n = preg_replace('/[^0-9+]/', '', (string)$n);
        $n = preg_replace('/^(\+|00)/', '', $n);
        if ($n === '') {
            continue;
        }
        if (substr($n, 0, 2) == '39' && strlen($n) >= 11) {
            $n = substr($n, 2);
            if (substr($n, 0, 1) == '3') return '39' . $n;
            continue;
        }
        if (substr($n, 0, 1) == '3' && strlen($n) >= 9 && strlen($n) <= 10) {
            return '39' . $n;
        }
        if ($n === preg_replace('/[^0-9]/', '', (string)$cell) && strlen($n) >= 8 && substr($n, 0, 1) != '0') {
            return $n; // cellulare estero gia' col prefisso internazionale
        }
    }
    return '';
}

/* e' un documento che si fa firmare? DDT di vendita, fattura accompagnatoria (FAI col modello "FatturaImmediata") */
function ss_documento_firmabile($testata) {
    if (in_array($testata['tipdoc'], array('DDR', 'DDL'))) return false;
    if (substr($testata['tipdoc'], 0, 2) == 'DD' || $testata['tipdoc'] == 'RPL') return true;
    return $testata['tipdoc'] == 'FAI' && $testata['template'] == 'FatturaImmediata';
}

function ss_nome_documento($testata) {
    return $testata['tipdoc'] == 'FAI' ? 'Fattura accompagnatoria' : 'DDT';
}

/* il PDF del DDT fatto da GAzie, come la stampa normale */
function ss_pdf_ddt($testata) {
    global $gTables;
    require_once(dirname(__FILE__) . '/document.php');
    // come il pulsante di stampa dell'elenco DDT (template=DDT): il campo template del documento puo' valere
    // "FatturaSemplice" anche su un DDT e darebbe il foglio della fattura (prezzi, IVA, IBAN)
    if (substr($testata['tipdoc'], 0, 2) == 'DD') {
        $template = 'DDT';
    } elseif (!empty($testata['template'])) {
        $template = $testata['template'];
    } else {
        $template = 'FatturaImmediata';
    }
    ob_start();
    $pdf = createDocument($testata, $template, $gTables, 'rigdoc', 'X');
    ob_end_clean();
    return (is_string($pdf) && substr($pdf, 0, 4) == '%PDF') ? $pdf : '';
}

/* pulsante con la matita per gli elenchi: solo se GAzie e' collegato a SystemaSign */
function ss_pulsante($id_tes, $stati) {
    static $attivo = null;
    if ($attivo === null) {
        $attivo = ss_collegato();
    }
    if (!$attivo) {
        return '';
    }
    $s = isset($stati[(int)$id_tes]) ? $stati[(int)$id_tes] : '';
    return ' <a class="btn btn-xs btn-' . ($s ? ss_classe_stato($s) : 'default') . '" title="Firma con SystemaSign' . ($s ? ': ' . ss_testo_stato($s) : '') .
        '" href="systemasign.php?id_tes=' . intval($id_tes) . '"><i class="glyphicon glyphicon-pencil"></i></a>';
}

/* scollega: toglie la chiave (il "PC" va scollegato anche in SystemaSign, Impostazioni > Stampanti) */
function ss_scollega() {
    global $gTables;
    gaz_dbi_query("DELETE FROM " . $gTables['company_config'] . " WHERE var IN ('systemasign_chiave', 'systemasign_codice')");
}

/* dal file agente.ini (o dal testo incollato) prende indirizzo e invito */
function ss_leggi_ini($testo) {
    $out = array('server' => '', 'invito' => '');
    foreach (preg_split('/\r\n|\r|\n/', (string)$testo) as $riga) {
        if (preg_match('/^\s*(server|invito)\s*=\s*(\S+)\s*$/i', $riga, $m)) {
            $out[strtolower($m[1])] = $m[2];
        }
    }
    if ($out['invito'] === '' && preg_match('/\binv-[A-Za-z0-9_-]{10,}\b/', (string)$testo, $m)) {
        $out['invito'] = $m[0];
    }
    return $out;
}

function ss_testo_stato($stato) {
    $t = array('da_firmare' => 'Da firmare', 'firmato' => 'Firmato', 'firmato_riserva' => 'Firmato con riserva', 'respinto' => 'Respinto');
    return isset($t[$stato]) ? $t[$stato] : $stato;
}

function ss_classe_stato($stato) {
    if ($stato == 'firmato') return 'success';
    if ($stato == 'firmato_riserva') return 'warning';
    if ($stato == 'respinto') return 'danger';
    return 'info';
}
