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
/*
  GAzie - preferiti della barra laterale (tema adminlte_clean).
  POST action=toggle&link=/modulo/pagina.php&name=Nome: aggiunge o toglie la pagina dai preferiti dell'utente.
  I preferiti sono salvati per azienda e utente in data/files/<company_id>/<user_name>.json.
*/
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
require_once("theme_favorites.lib.php");
$admin_aziend['user_name'] = $admin_aziend['user_name'];
$action = $_POST['action'] ?? '';
$link = $_POST['link'] ?? '';
$name = trim(strip_tags((string) ($_POST['name'] ?? '')));
$name = mb_substr($name, 0, 60);
// pagina del modulo, eventualmente con parametri (es. admin_docven.php?Insert&tipdoc=FAI)
if ($action === 'toggle' && preg_match('#^/[a-z0-9_]+/[a-z0-9_]+\.php(\?[a-z0-9_&=.%-]*)?$#i', $link)) {
    $list = gzFavoritesFor($admin_aziend['company_id'], $admin_aziend['user_name']);
    $found = false;
    foreach ($list as $i => $f) {
        if ($f['link'] === $link) {
            unset($list[$i]);
            $found = true;
        }
    }
    if (!$found) {
        $list[] = ['link' => $link, 'name' => $name !== '' ? $name : $link];
    }
    gzFavoritesSave($admin_aziend['company_id'], $admin_aziend['user_name'], $list);
    echo $found ? 'removed' : 'added';
    exit;
}
// nuovo ordine dei preferiti dell'utente: order è la lista dei link nell'ordine voluto
if ($action === 'riordina') {
    $list = gzFavoritesFor($admin_aziend['company_id'], $admin_aziend['user_name']);
    $order = json_decode((string) ($_POST['order'] ?? '[]'), true);
    if (!is_array($order)) {
        http_response_code(400);
        exit('ordine non valido');
    }
    $mine = [];
    foreach ($list as $f) {
        if (isset($f['link'])) {
            $mine[$f['link']] = $f;
        }
    }
    $sorted = [];
    foreach ($order as $link) {
        if (is_string($link) && isset($mine[$link])) {
            $sorted[] = $mine[$link];
            unset($mine[$link]);
        }
    }
    // i preferiti non presenti nell'ordine inviato restano in fondo, nel loro ordine
    $sorted = array_merge($sorted, array_values($mine));
    gzFavoritesSave($admin_aziend['company_id'], $admin_aziend['user_name'], $sorted);
    echo 'ok';
    exit;
}
