<?php
/*
  GAzie - preferiti della barra laterale (tema adminlte_clean).
  POST action=toggle&link=/modulo/pagina.php&name=Nome: aggiunge o toglie la pagina dai preferiti dell'utente.
  I preferiti sono salvati per azienda e utente in data/files/<company_id>/<user_name>.json.
*/
require("../../library/include/datlib.inc.php");
require_once("theme_favorites.lib.php");

if (empty($_SESSION['user_name']) || empty($_SESSION['company_id'])) {
    http_response_code(403);
    exit('accesso negato');
}
$user = $_SESSION['user_name'];
$companyId = $_SESSION['company_id'];
$action = $_POST['action'] ?? '';
$link = $_POST['link'] ?? '';
$name = trim(strip_tags((string) ($_POST['name'] ?? '')));
$name = mb_substr($name, 0, 60);

// pagina del modulo, eventualmente con parametri (es. admin_docven.php?Insert&tipdoc=FAI)
if ($action === 'toggle' && preg_match('#^/[a-z0-9_]+/[a-z0-9_]+\.php(\?[a-z0-9_&=.%-]*)?$#i', $link)) {
    $list = gzFavoritesFor($companyId, $user);
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
    gzFavoritesSave($companyId, $user, $list);
    echo $found ? 'removed' : 'added';
    exit;
}
// nuovo ordine dei preferiti dell'utente: order è la lista dei link nell'ordine voluto
if ($action === 'riordina') {
    $list = gzFavoritesFor($companyId, $user);
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
    gzFavoritesSave($companyId, $user, $sorted);
    echo 'ok';
    exit;
}
http_response_code(400);
echo 'richiesta non valida';
