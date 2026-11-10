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
$action = $_POST['action'] ?? '';
$scriptlink = $_POST['link'] ?? '';
$mode = isset($_POST['mode']) ? substr($_POST['mode'],0,2) : '';
$name = trim(strip_tags((string) ($_POST['name'] ?? '')));
$name = mb_substr($name, 0, 60);
// pagina del modulo, eventualmente con parametri (es. admin_docven.php?Insert&tipdoc=FAI)
if ($action === 'toggle' && preg_match('#^/[a-z0-9_]+/[a-z0-9_]+\.php(\?[a-z0-9_&=.%-]*)?$#i', $scriptlink)) {
  if($mode == 'db') {
    $link_isset=gaz_dbi_get_row($gTables['breadcrumb'],'codice_aziend',$admin_aziend['company_id'],"AND adminid='".$admin_aziend['user_name']."' AND exec_mode = 4 AND link = '".gaz_dbi_real_escape_string($scriptlink)."' LIMIT 1");
    if ($link_isset){ // è attivata, disattivo rimuovendo
      $link_isset=gaz_dbi_del_row($gTables['breadcrumb'],'id_bread',$link_isset['id_bread']);
    } else { // attivo inserendo
      gaz_dbi_query("INSERT INTO `gaz_breadcrumb` (`exec_mode`,`link`,`titolo`,`adminid`,`codice_aziend`) VALUES ( 4, '".gaz_dbi_real_escape_string($scriptlink)."','".$name."','".$admin_aziend['user_name']."',".$admin_aziend['company_id']." )");
    }
  } else {
    $list = gzFavoritesFor($admin_aziend['company_id'], $admin_aziend['user_name']);
    $found = false;
    foreach ($list as $i => $f) {
      if ($f['link'] === $scriptlink) {
        unset($list[$i]);
        $found = true;
      }
    }
    if (!$found) {
        $list[] = ['link' => $scriptlink, 'name' => $name !== '' ? $name : $scriptlink];
    }
    gzFavoritesSave($admin_aziend['company_id'], $admin_aziend['user_name'], $list);
    echo $found ? 'removed' : 'added';
    exit;
  }
}
// nuovo ordine dei preferiti dell'utente: order è la lista dei link nell'ordine voluto
if ($action === 'riordina') {
  $order = json_decode((string) ($_POST['order'] ?? '[]'), true);
  if($mode == 'db') {
    $i=0;
    foreach ($order as $scriptlink) {
      gaz_dbi_query("UPDATE ".$gTables['breadcrumb']." SET position_order = ".$i." WHERE codice_aziend=".$admin_aziend['company_id']." AND adminid='". $admin_aziend['user_name']."' AND exec_mode = 4 AND link ='".$scriptlink."'");
      $i++;
    }
  } else {
    $list = gzFavoritesFor($admin_aziend['company_id'], $admin_aziend['user_name']);
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
    foreach ($order as $scriptlink) {
      if (is_string($scriptlink) && isset($mine[$scriptlink])) {
        $sorted[] = $mine[$scriptlink];
        unset($mine[$scriptlink]);
      }
    }
    // i preferiti non presenti nell'ordine inviato restano in fondo, nel loro ordine
    $sorted = array_merge($sorted, array_values($mine));
    gzFavoritesSave($admin_aziend['company_id'], $admin_aziend['user_name'], $sorted);
    echo 'ok';
    exit;
  }
}
