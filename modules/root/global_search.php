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
 */
// Ricerca globale: cerca il termine in tutte le fonti (anagrafiche, articoli, documenti, contratti, assistenze)
// che l'utente può vedere. Con ajax=1 risponde in JSON (tendina della barra in alto), altrimenti mostra la pagina dei risultati.
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
require_once("global_search.lib.php");
$gsSettings = gsSettingsFor($admin_aziend['company_id'], $admin_aziend['user_name']);
$gsDisabled = $gsSettings['disabled'];

$isAjax = !empty($_GET['ajax']);
$q = isset($_GET['q']) ? trim(preg_replace('/\s+/', ' ', (string) $_GET['q'])) : '';
$q = mb_substr($q, 0, 50);
// stessi caratteri ammessi dall'autocompletamento (search.php): lettere, cifre, spazio e pochi simboli
$valid = ($q !== '' && !preg_match("/[^\040\pL\pN\&\%\*\?\@\#\/\.,_-]/u", $q));
$limit = $isAjax ? 5 : 50;

// cerca il termine in tutti i campi indicati (OR), sempre come "contiene".
// Jolly: * o % = qualsiasi testo, ? = un solo carattere (es. "toner*hp" trova "TONER NERO PER HP")
function gsLike($fields, $term) {
    $t = gaz_dbi_real_escape_string(strtr($term, array('*' => '%', '?' => '_')));
    $pattern = "'%" . trim($t, '%') . "%'";
    $parts = array();
    foreach ($fields as $f) {
        $parts[] = $f . " LIKE " . $pattern;
    }
    return '(' . implode(' OR ', $parts) . ')';
}


$results = array(); // gruppo => array di risultati (title, detail, url)
$tc_link = ''; // valorizzato solo se il testo è un trans_code (vedi sotto)
if ($valid && mb_strlen(str_replace(array('%', '*', '?'), '', $q)) >= 2) {
  $T = $gTables;
  $notTransCode = true;
  $tc_link = '';
  if ( strlen($q) == 6 ) { // se ho digitato 6 caratteri controllo prima se coincide con un trans_code per link rapido da tastiera
    $sql = "SELECT CONCAT(" . $T['module'] . ".name,'/'," . $T['menu_module'] . ".link) AS tc_link FROM " . $T['menu_module'] . " LEFT JOIN " . $T['module'] . " ON " . $T['menu_module'] . ".id_module = " . $T['module'] . ".id WHERE ".$T['menu_module'].".trans_code = '".strtoupper($q)."' UNION ALL SELECT CONCAT(" . $T['module'] . ".name,'/'," . $T['menu_script'] . ".link) AS tc_link FROM " . $T['menu_script'] . " LEFT JOIN " . $T['menu_module'] . " ON " . $T['menu_script'] . ".id_menu = " . $T['menu_module'] . ".id  LEFT JOIN " . $T['module'] . " ON " . $T['menu_module'] . ".id_module = " . $T['module'] . ".id  WHERE " . $T['menu_script'] . ".trans_code = '".strtoupper($q)."'";
    $result = gaz_dbi_query($sql);
    if (gaz_dbi_num_rows($result) >= 1) {
      $row = gaz_dbi_fetch_array($result);
      $notTransCode = false;
      $tc_link = $row['tc_link'];
      //error_log($tc_link);
    }
  }
  if ($notTransCode){
    $num = ctype_digit($q) ? intval($q) : 0; // termine solo numerico: cerco anche per numero esatto
    $anag = $T['clfoco'] . " LEFT JOIN " . $T['anagra'] . " ON " . $T['clfoco'] . ".id_anagra = " . $T['anagra'] . ".id";
    $anagFields = array($T['anagra'] . '.ragso1', $T['anagra'] . '.ragso2', $T['anagra'] . '.pariva', $T['anagra'] . '.codfis',
                        $T['anagra'] . '.e_mail', $T['anagra'] . '.telefo', $T['anagra'] . '.cell', $T['anagra'] . '.citspe');
    $mascli = intval($admin_aziend['mascli']);
    // la colonna invoice_descri c'è solo dopo l'aggiornamento 9.13: senza, la fonte contratti cerca solo nelle altre colonne
    $contrDescri = gaz_dbi_query("SHOW COLUMNS FROM " . $T['contract'] . " LIKE 'invoice_descri'");
    $hasDescri = gaz_dbi_num_rows($contrDescri) > 0;
    $masfor = intval($admin_aziend['masfor']);

    // registro delle fonti: aggiungere una voce per cercare in un'altra tabella
    $sources = array(
        array('key' => 'clienti', 'group' => 'Clienti', 'icon' => 'fa-user', 'module' => 'vendit', 'script' => 'admin_client',
            'select' => $T['clfoco'] . ".codice AS k, " . $T['anagra'] . ".ragso1 AS t, CONCAT_WS(' - ', NULLIF(" . $T['anagra'] . ".citspe,''), NULLIF(" . $T['anagra'] . ".pariva,''), NULLIF(" . $T['anagra'] . ".telefo,'')) AS d",
            'from' => $anag,
            'where' => gsLike($anagFields, $q) . " AND " . $T['clfoco'] . ".codice BETWEEN " . $mascli . "000001 AND " . $mascli . "999999",
            'order' => $T['anagra'] . ".ragso1",
            'url' => function ($r) { return '../vendit/admin_client.php?codice=' . intval(substr($r['k'], 3)) . '&Update'; }),
        array('key' => 'fornitori', 'group' => 'Fornitori', 'icon' => 'fa-truck', 'module' => 'acquis', 'script' => 'admin_fornit',
            'select' => $T['clfoco'] . ".codice AS k, " . $T['anagra'] . ".ragso1 AS t, CONCAT_WS(' - ', NULLIF(" . $T['anagra'] . ".citspe,''), NULLIF(" . $T['anagra'] . ".pariva,''), NULLIF(" . $T['anagra'] . ".telefo,'')) AS d",
            'from' => $anag,
            'where' => gsLike($anagFields, $q) . " AND " . $T['clfoco'] . ".codice BETWEEN " . $masfor . "000001 AND " . $masfor . "999999",
            'order' => $T['anagra'] . ".ragso1",
            'url' => function ($r) { return '../acquis/admin_fornit.php?codice=' . intval(substr($r['k'], 3)) . '&Update'; }),
        array('key' => 'articoli', 'group' => 'Articoli', 'icon' => 'fa-cube', 'module' => 'magazz', 'script' => 'admin_artico',
            'select' => "codice AS k, CONCAT(codice, ' - ', descri) AS t, CONCAT_WS(' - ', NULLIF(barcode,''), NULLIF(codice_fornitore,'')) AS d",
            'from' => $T['artico'],
            'where' => gsLike(array('codice', 'descri', 'barcode', 'codice_fornitore'), $q),
            'order' => "codice",
            'url' => function ($r) { return '../magazz/admin_artico.php?Update&codice=' . urlencode($r['k']); }),
        array('key' => 'docven', 'group' => 'Documenti di vendita', 'icon' => 'fa-file-text-o', 'module' => 'vendit', 'script' => 'admin_docven',
            'select' => $T['tesdoc'] . ".id_tes AS k, CONCAT(" . $T['tesdoc'] . ".tipdoc, ' n.', IF(" . $T['tesdoc'] . ".numfat > 0, " . $T['tesdoc'] . ".numfat, " . $T['tesdoc'] . ".numdoc), ' del ', DATE_FORMAT(IFNULL(" . $T['tesdoc'] . ".datfat, " . $T['tesdoc'] . ".datemi), '%d-%m-%Y')) AS t, " . $T['anagra'] . ".ragso1 AS d",
            'from' => $T['tesdoc'] . " LEFT JOIN " . $anag . " ON " . $T['tesdoc'] . ".clfoco = " . $T['clfoco'] . ".codice",
            'where' => "(" . gsLike(array($T['anagra'] . '.ragso1', $T['anagra'] . '.ragso2'), $q) . ($num ? " OR " . $T['tesdoc'] . ".numfat = '" . $num . "' OR " . $T['tesdoc'] . ".numdoc = " . $num . " OR " . $T['tesdoc'] . ".protoc = " . $num : "") . ") AND " . $T['tesdoc'] . ".tipdoc NOT LIKE 'A%'",
            'order' => $T['tesdoc'] . ".datemi DESC, " . $T['tesdoc'] . ".id_tes DESC",
            'url' => function ($r) { return '../vendit/admin_docven.php?Update&id_tes=' . intval($r['k']); }),
        array('key' => 'docacq', 'group' => 'Documenti di acquisto', 'icon' => 'fa-file-o', 'module' => 'acquis', 'script' => 'admin_docacq',
            'select' => $T['tesdoc'] . ".id_tes AS k, CONCAT(" . $T['tesdoc'] . ".tipdoc, ' n.', IF(" . $T['tesdoc'] . ".numfat <> '', " . $T['tesdoc'] . ".numfat, " . $T['tesdoc'] . ".numdoc), ' del ', DATE_FORMAT(IFNULL(" . $T['tesdoc'] . ".datfat, " . $T['tesdoc'] . ".datemi), '%d-%m-%Y')) AS t, " . $T['anagra'] . ".ragso1 AS d",
            'from' => $T['tesdoc'] . " LEFT JOIN " . $anag . " ON " . $T['tesdoc'] . ".clfoco = " . $T['clfoco'] . ".codice",
            'where' => "(" . gsLike(array($T['anagra'] . '.ragso1', $T['anagra'] . '.ragso2', $T['tesdoc'] . '.numfat'), $q) . ($num ? " OR " . $T['tesdoc'] . ".numdoc = " . $num . " OR " . $T['tesdoc'] . ".protoc = " . $num : "") . ") AND " . $T['tesdoc'] . ".tipdoc LIKE 'A%'",
            'order' => $T['tesdoc'] . ".datemi DESC, " . $T['tesdoc'] . ".id_tes DESC",
            'url' => function ($r) { return '../acquis/admin_docacq.php?Update&id_tes=' . intval($r['k']); }),
        array('key' => 'broven', 'group' => 'Preventivi e ordini di vendita', 'icon' => 'fa-shopping-cart', 'module' => 'vendit', 'script' => 'admin_broven',
            'select' => $T['tesbro'] . ".id_tes AS k, CONCAT(" . $T['tesbro'] . ".tipdoc, ' n.', " . $T['tesbro'] . ".numdoc, ' del ', DATE_FORMAT(" . $T['tesbro'] . ".datemi, '%d-%m-%Y')) AS t, " . $T['anagra'] . ".ragso1 AS d",
            'from' => $T['tesbro'] . " LEFT JOIN " . $anag . " ON " . $T['tesbro'] . ".clfoco = " . $T['clfoco'] . ".codice",
            'where' => "(" . gsLike(array($T['anagra'] . '.ragso1', $T['anagra'] . '.ragso2', $T['tesbro'] . '.ref_ecommerce_id_order'), $q) . ($num ? " OR " . $T['tesbro'] . ".numdoc = " . $num : "") . ") AND " . $T['tesbro'] . ".tipdoc LIKE 'V%'",
            'order' => $T['tesbro'] . ".datemi DESC, " . $T['tesbro'] . ".id_tes DESC",
            'url' => function ($r) { return '../vendit/admin_broven.php?Update&id_tes=' . intval($r['k']); }),
        array('key' => 'broacq', 'group' => 'Preventivi e ordini di acquisto', 'icon' => 'fa-shopping-basket', 'module' => 'acquis', 'script' => 'admin_broacq',
            'select' => $T['tesbro'] . ".id_tes AS k, CONCAT(" . $T['tesbro'] . ".tipdoc, ' n.', " . $T['tesbro'] . ".numdoc, ' del ', DATE_FORMAT(" . $T['tesbro'] . ".datemi, '%d-%m-%Y')) AS t, " . $T['anagra'] . ".ragso1 AS d",
            'from' => $T['tesbro'] . " LEFT JOIN " . $anag . " ON " . $T['tesbro'] . ".clfoco = " . $T['clfoco'] . ".codice",
            'where' => "(" . gsLike(array($T['anagra'] . '.ragso1', $T['anagra'] . '.ragso2'), $q) . ($num ? " OR " . $T['tesbro'] . ".numdoc = " . $num : "") . ") AND " . $T['tesbro'] . ".tipdoc LIKE 'A%'",
            'order' => $T['tesbro'] . ".datemi DESC, " . $T['tesbro'] . ".id_tes DESC",
            'url' => function ($r) { return '../acquis/admin_broacq.php?id_tes=' . intval($r['k']) . '&Update'; }),
        array('key' => 'contratti', 'group' => 'Contratti', 'icon' => 'fa-handshake-o', 'module' => 'vendit', 'script' => 'admin_contract',
            'select' => $T['contract'] . ".id_contract AS k, CONCAT('Contratto n.', " . $T['contract'] . ".doc_number, ' del ', DATE_FORMAT(" . $T['contract'] . ".conclusion_date, '%d-%m-%Y')) AS t, " . $T['anagra'] . ".ragso1 AS d",
            'from' => $T['contract'] . " LEFT JOIN " . $anag . " ON " . $T['contract'] . ".id_customer = " . $T['clfoco'] . ".codice",
            'where' => "(" . gsLike(array($T['anagra'] . '.ragso1', $T['anagra'] . '.ragso2', $T['contract'] . '.note') + ($hasDescri ? array($T['contract'] . '.invoice_descri') : array()), $q) . ($num ? " OR " . $T['contract'] . ".doc_number = " . $num : "") . ")",
            'order' => $T['contract'] . ".conclusion_date DESC",
            'url' => function ($r) { return '../vendit/admin_contract.php?Update&id_contract=' . intval($r['k']); }),
        array('key' => 'assistenze', 'group' => 'Assistenze', 'icon' => 'fa-wrench', 'module' => 'suppor', 'script' => 'admin_assist',
            'select' => $T['assist'] . ".codice AS k, CONCAT('Intervento n.', " . $T['assist'] . ".codice, ' - ', " . $T['assist'] . ".oggetto) AS t, CONCAT_WS(' - ', DATE_FORMAT(" . $T['assist'] . ".data, '%d-%m-%Y'), " . $T['anagra'] . ".ragso1, " . $T['assist'] . ".stato) AS d",
            'from' => $T['assist'] . " LEFT JOIN " . $anag . " ON " . $T['assist'] . ".clfoco = " . $T['clfoco'] . ".codice",
            'where' => "(" . gsLike(array($T['assist'] . '.oggetto', $T['assist'] . '.descrizione', $T['assist'] . '.soluzione', $T['assist'] . '.tecnico', $T['anagra'] . '.ragso1'), $q) . ($num ? " OR " . $T['assist'] . ".codice = " . $num : "") . ")",
            'order' => $T['assist'] . ".data DESC",
            'url' => function ($r) { return '../suppor/admin_assist.php?codice=' . intval($r['k']) . '&Update'; }),

        // fase 2: righe dei documenti (solo nella pagina completa, sono le tabelle più grandi), contabilità, magazzino, moduli verticali
        array('key' => 'righe_ven', 'group' => 'Righe dei documenti di vendita', 'icon' => 'fa-list', 'module' => 'vendit', 'script' => 'admin_docven', 'page_only' => true,
            'select' => $T['tesdoc'] . ".id_tes AS k, CONCAT(" . $T['tesdoc'] . ".tipdoc, ' n.', IF(" . $T['tesdoc'] . ".numfat > 0, " . $T['tesdoc'] . ".numfat, " . $T['tesdoc'] . ".numdoc), ' del ', DATE_FORMAT(IFNULL(" . $T['tesdoc'] . ".datfat, " . $T['tesdoc'] . ".datemi), '%d-%m-%Y'), ': ', " . $T['rigdoc'] . ".descri) AS t, CONCAT_WS(' - ', " . $T['anagra'] . ".ragso1, NULLIF(" . $T['rigdoc'] . ".codart,'')) AS d",
            'from' => $T['rigdoc'] . " JOIN " . $T['tesdoc'] . " ON " . $T['rigdoc'] . ".id_tes = " . $T['tesdoc'] . ".id_tes LEFT JOIN " . $anag . " ON " . $T['tesdoc'] . ".clfoco = " . $T['clfoco'] . ".codice",
            'where' => gsLike(array($T['rigdoc'] . '.descri', $T['rigdoc'] . '.codart'), $q) . " AND " . $T['tesdoc'] . ".tipdoc NOT LIKE 'A%'",
            'order' => $T['tesdoc'] . ".datemi DESC, " . $T['tesdoc'] . ".id_tes DESC",
            'url' => function ($r) { return '../vendit/admin_docven.php?Update&id_tes=' . intval($r['k']); }),
        array('key' => 'righe_acq', 'group' => 'Righe dei documenti di acquisto', 'icon' => 'fa-list', 'module' => 'acquis', 'script' => 'admin_docacq', 'page_only' => true,
            'select' => $T['tesdoc'] . ".id_tes AS k, CONCAT(" . $T['tesdoc'] . ".tipdoc, ' n.', IF(" . $T['tesdoc'] . ".numfat <> '', " . $T['tesdoc'] . ".numfat, " . $T['tesdoc'] . ".numdoc), ' del ', DATE_FORMAT(IFNULL(" . $T['tesdoc'] . ".datfat, " . $T['tesdoc'] . ".datemi), '%d-%m-%Y'), ': ', " . $T['rigdoc'] . ".descri) AS t, CONCAT_WS(' - ', " . $T['anagra'] . ".ragso1, NULLIF(" . $T['rigdoc'] . ".codart,'')) AS d",
            'from' => $T['rigdoc'] . " JOIN " . $T['tesdoc'] . " ON " . $T['rigdoc'] . ".id_tes = " . $T['tesdoc'] . ".id_tes LEFT JOIN " . $anag . " ON " . $T['tesdoc'] . ".clfoco = " . $T['clfoco'] . ".codice",
            'where' => gsLike(array($T['rigdoc'] . '.descri', $T['rigdoc'] . '.codart', $T['rigdoc'] . '.codice_fornitore'), $q) . " AND " . $T['tesdoc'] . ".tipdoc LIKE 'A%'",
            'order' => $T['tesdoc'] . ".datemi DESC, " . $T['tesdoc'] . ".id_tes DESC",
            'url' => function ($r) { return '../acquis/admin_docacq.php?Update&id_tes=' . intval($r['k']); }),
        array('key' => 'righe_broven', 'group' => 'Righe di preventivi e ordini di vendita', 'icon' => 'fa-list', 'module' => 'vendit', 'script' => 'admin_broven', 'page_only' => true,
            'select' => $T['tesbro'] . ".id_tes AS k, CONCAT(" . $T['tesbro'] . ".tipdoc, ' n.', " . $T['tesbro'] . ".numdoc, ' del ', DATE_FORMAT(" . $T['tesbro'] . ".datemi, '%d-%m-%Y'), ': ', " . $T['rigbro'] . ".descri) AS t, CONCAT_WS(' - ', " . $T['anagra'] . ".ragso1, NULLIF(" . $T['rigbro'] . ".codart,'')) AS d",
            'from' => $T['rigbro'] . " JOIN " . $T['tesbro'] . " ON " . $T['rigbro'] . ".id_tes = " . $T['tesbro'] . ".id_tes LEFT JOIN " . $anag . " ON " . $T['tesbro'] . ".clfoco = " . $T['clfoco'] . ".codice",
            'where' => gsLike(array($T['rigbro'] . '.descri', $T['rigbro'] . '.codart'), $q) . " AND " . $T['tesbro'] . ".tipdoc LIKE 'V%'",
            'order' => $T['tesbro'] . ".datemi DESC, " . $T['tesbro'] . ".id_tes DESC",
            'url' => function ($r) { return '../vendit/admin_broven.php?Update&id_tes=' . intval($r['k']); }),
        array('key' => 'righe_broacq', 'group' => 'Righe di preventivi e ordini di acquisto', 'icon' => 'fa-list', 'module' => 'acquis', 'script' => 'admin_broacq', 'page_only' => true,
            'select' => $T['tesbro'] . ".id_tes AS k, CONCAT(" . $T['tesbro'] . ".tipdoc, ' n.', " . $T['tesbro'] . ".numdoc, ' del ', DATE_FORMAT(" . $T['tesbro'] . ".datemi, '%d-%m-%Y'), ': ', " . $T['rigbro'] . ".descri) AS t, CONCAT_WS(' - ', " . $T['anagra'] . ".ragso1, NULLIF(" . $T['rigbro'] . ".codart,'')) AS d",
            'from' => $T['rigbro'] . " JOIN " . $T['tesbro'] . " ON " . $T['rigbro'] . ".id_tes = " . $T['tesbro'] . ".id_tes LEFT JOIN " . $anag . " ON " . $T['tesbro'] . ".clfoco = " . $T['clfoco'] . ".codice",
            'where' => gsLike(array($T['rigbro'] . '.descri', $T['rigbro'] . '.codart'), $q) . " AND " . $T['tesbro'] . ".tipdoc LIKE 'A%'",
            'order' => $T['tesbro'] . ".datemi DESC, " . $T['tesbro'] . ".id_tes DESC",
            'url' => function ($r) { return '../acquis/admin_broacq.php?id_tes=' . intval($r['k']) . '&Update'; }),
        array('key' => 'movcon', 'group' => 'Movimenti contabili', 'icon' => 'fa-book', 'module' => 'contab', 'script' => 'admin_movcon',
            'select' => $T['tesmov'] . ".id_tes AS k, CONCAT(DATE_FORMAT(" . $T['tesmov'] . ".datreg, '%d-%m-%Y'), ' - ', " . $T['tesmov'] . ".descri) AS t, CONCAT_WS(' - ', NULLIF(CONCAT('doc. ', NULLIF(" . $T['tesmov'] . ".numdoc,'0')),'doc. '), " . $T['anagra'] . ".ragso1) AS d",
            'from' => $T['tesmov'] . " LEFT JOIN " . $anag . " ON " . $T['tesmov'] . ".clfoco = " . $T['clfoco'] . ".codice",
            'where' => "(" . gsLike(array($T['tesmov'] . '.descri', $T['tesmov'] . '.numdoc', $T['tesmov'] . '.notess', $T['anagra'] . '.ragso1'), $q) . ($num ? " OR " . $T['tesmov'] . ".protoc = " . $num : "") . ")",
            'order' => $T['tesmov'] . ".datreg DESC, " . $T['tesmov'] . ".id_tes DESC",
            'url' => function ($r) { return '../contab/admin_movcon.php?id_tes=' . intval($r['k']) . '&Update'; }),
        array('key' => 'movmag', 'group' => 'Movimenti di magazzino', 'icon' => 'fa-exchange', 'module' => 'magazz', 'script' => 'admin_movmag',
            'select' => $T['movmag'] . ".id_mov AS k, CONCAT(DATE_FORMAT(" . $T['movmag'] . ".datreg, '%d-%m-%Y'), ' - ', " . $T['movmag'] . ".artico, ' - ', IFNULL(" . $T['artico'] . ".descri, '')) AS t, CONCAT_WS(' - ', NULLIF(" . $T['movmag'] . ".desdoc,''), CONCAT('q.tà ', " . $T['movmag'] . ".quanti)) AS d",
            'from' => $T['movmag'] . " LEFT JOIN " . $T['artico'] . " ON " . $T['movmag'] . ".artico = " . $T['artico'] . ".codice",
            'where' => gsLike(array($T['movmag'] . '.artico', $T['movmag'] . '.desdoc', $T['artico'] . '.descri'), $q),
            'order' => $T['movmag'] . ".datreg DESC, " . $T['movmag'] . ".id_mov DESC",
            'url' => function ($r) { return '../magazz/admin_movmag.php?id_mov=' . intval($r['k']) . '&Update'; }),
        array('key' => 'installazioni', 'group' => 'Installazioni', 'icon' => 'fa-desktop', 'module' => 'suppor', 'script' => 'admin_install',
            'select' => $T['instal'] . ".codice AS k, CONCAT('Installazione n.', " . $T['instal'] . ".codice, ' - ', " . $T['instal'] . ".oggetto) AS t, CONCAT_WS(' - ', " . $T['anagra'] . ".ragso1, NULLIF(" . $T['instal'] . ".seriale,'')) AS d",
            'from' => $T['instal'] . " LEFT JOIN " . $anag . " ON " . $T['instal'] . ".clfoco = " . $T['clfoco'] . ".codice",
            'where' => "(" . gsLike(array($T['instal'] . '.oggetto', $T['instal'] . '.descrizione', $T['instal'] . '.seriale', $T['instal'] . '.note', $T['anagra'] . '.ragso1'), $q) . ($num ? " OR " . $T['instal'] . ".codice = " . $num : "") . ")",
            'order' => $T['instal'] . ".datainst DESC",
            'url' => function ($r) { return '../suppor/admin_install.php?codice=' . intval($r['k']) . '&Update'; }),
        array('key' => 'personale', 'group' => 'Personale', 'icon' => 'fa-users', 'module' => 'humres', 'script' => 'admin_staff',
            'select' => $T['staff'] . ".id_clfoco AS k, CONCAT_WS(' ', " . $T['anagra'] . ".ragso1, " . $T['anagra'] . ".ragso2) AS t, " . $T['staff'] . ".job_title AS d",
            'from' => $T['staff'] . " LEFT JOIN " . $anag . " ON " . $T['staff'] . ".id_clfoco = " . $T['clfoco'] . ".codice",
            'where' => gsLike(array($T['anagra'] . '.ragso1', $T['anagra'] . '.ragso2', $T['anagra'] . '.codfis', $T['staff'] . '.job_title'), $q),
            'order' => $T['anagra'] . ".ragso1",
            'url' => function ($r) { return '../humres/admin_staff.php?Update&codice=' . substr($r['k'], -6); }),
        array('key' => 'produzioni', 'group' => 'Produzioni e commesse', 'icon' => 'fa-industry', 'module' => 'orderman', 'script' => 'admin_orderman',
            'select' => $T['orderman'] . ".id AS k, CONCAT('Produzione n.', " . $T['orderman'] . ".id, ' - ', " . $T['orderman'] . ".description) AS t, " . $T['orderman'] . ".add_info AS d",
            'from' => $T['orderman'],
            'where' => "(" . gsLike(array($T['orderman'] . '.description', $T['orderman'] . '.add_info', $T['orderman'] . '.tracking_no'), $q) . ($num ? " OR " . $T['orderman'] . ".id = " . $num : "") . ")",
            'order' => $T['orderman'] . ".id DESC",
            'url' => function ($r) { return '../orderman/admin_orderman.php?Update&codice=' . intval($r['k']); }),
    );

    // funzioni rapide (codici del menu, es. FATVEN): elenco in cima alla tendina; il codice esatto apre la pagina direttamente (vedi tc_link)
    if (!in_array('codici', $gsDisabled)) {
        $quickUrl = null;
        $qrows = gsQuickCodes($q, $limit, $quickUrl);
        if ($qrows) $results[] = array('group' => 'Funzioni rapide', 'icon' => 'fa-bolt', 'rows' => $qrows);
    }

    foreach ($sources as $s) {
        // le fonti pesanti solo nella pagina completa, a meno che l'utente non le voglia anche nella tendina
        if ($isAjax && !empty($s['page_only']) && !in_array($s['key'], $gsSettings['dropdown'])) continue;
        if (in_array($s['key'], $gsDisabled) || !gsCanSee($s['module'], $s['script'])) continue;
        // una fonte che non risponde (tabella o colonna mancante) non blocca le altre
        try {
            $rs = gaz_dbi_dyn_query($s['select'], $s['from'], $s['where'], $s['order'], 0, $limit);
        } catch (Throwable $e) {
            continue;
        }
        $rows = array();
        while ($r = gaz_dbi_fetch_array($rs)) {
            $rows[] = array('title' => (string) $r['t'], 'detail' => (string) $r['d'], 'url' => $s['url']($r));
        }
        if ($rows) $results[] = array('group' => $s['group'], 'icon' => $s['icon'], 'rows' => $rows);
    }
  }
}

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('q' => $q, 'pages' => !in_array('pagine', $gsDisabled), 'groups' => $results,'tc_link'=>$tc_link));
    exit;
}

// pagina completa dei risultati
require("../../library/include/header.php");
$script_transl = HeadMain();
// evidenzia il termine nel testo già messo in sicurezza con htmlspecialchars
function gsMark($text, $q) {
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $pieces = array();
    foreach (preg_split('/[%*?]+/', $q) as $piece) {
        $piece = htmlspecialchars(trim($piece), ENT_QUOTES, 'UTF-8');
        if (mb_strlen($piece) >= 2) $pieces[] = preg_quote($piece, '/');
    }
    if (!$pieces) return $safe;
    return preg_replace('/' . implode('|', $pieces) . '/iu', '<mark>$0</mark>', $safe);
}
?>
<div class="gz-gs-page">
    <form method="GET" class="form-inline" style="margin-bottom: 15px;">
        <input type="text" name="q" class="form-control" style="width: 320px;" value="<?php echo htmlspecialchars($q); ?>" placeholder="Cerca in GAzie..." autofocus>
        <button type="submit" class="btn btn-default"><i class="fa fa-search"></i> Cerca</button>
    </form>
<?php
if ($q === '') {
    echo '<p class="text-muted">Scrivi almeno 2 caratteri: nomi, P.IVA, codici articolo, numeri di documento, oggetti degli interventi…</p>';
} elseif (!$valid) {
    echo '<div class="alert alert-warning">Il testo contiene caratteri non ammessi nella ricerca.</div>';
} elseif (!$results) {
    echo '<div class="alert alert-info">Nessun risultato per <b>' . htmlspecialchars($q) . '</b>.</div>';
} else {
    foreach ($results as $g) {
        echo '<div class="panel panel-default gz-gs-group">';
        echo '<div class="panel-heading"><i class="fa ' . $g['icon'] . '"></i> ' . htmlspecialchars($g['group']) . ' <span class="badge">' . count($g['rows']) . ($limit == count($g['rows']) ? '+' : '') . '</span></div>';
        echo '<div class="list-group">';
        foreach ($g['rows'] as $r) {
            echo '<a class="list-group-item" href="' . htmlspecialchars($r['url']) . '">' . gsMark($r['title'], $q);
            if ($r['detail'] !== '') echo ' <small class="text-muted">' . gsMark($r['detail'], $q) . '</small>';
            echo '</a>';
        }
        echo '</div></div>';
    }
}
?>
</div>
<?php
require("../../library/include/footer.php");
?>
