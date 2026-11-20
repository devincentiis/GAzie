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

// file dei preferiti di un utente in una azienda: data/files/<company_id>/<user_name>.json
// il nome utente è ripulito, così non può uscire dalla cartella
function gzFavoritesFile($companyId, $user) {
  $name = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $user);
  return __DIR__ . '/../../data/files/' . (int) $companyId . '/' . $name . '.json';
}

// preferiti dell'utente nell'azienda (stellina nella barra)
function gzFavoritesFor($companyId, $user) {
  $file = gzFavoritesFile($companyId, $user);
  return is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
}

// preferiti dell'utente nell'azienda (stellina nella barra)
function gzFavoritesForDb($companyId, $user) {
  global $gTables;
  $acc=[];
  $result = gaz_dbi_dyn_query("*", $gTables['breadcrumb'], "codice_aziend=" . $companyId." AND adminid='". $user . "' AND exec_mode = 4", 'position_order');
  if (gaz_dbi_num_rows($result) > 0) {
    while ($r = gaz_dbi_fetch_array($result)) {
      $r['name'] = $r['titolo'];
      $r['code'] = (string) $r['file'];
      $acc[]=$r;
    }
  }
  return $acc;
}

// salva i preferiti dell'utente nell'azienda; la cartella viene creata se manca
function gzFavoritesSave($companyId, $user, $list) {
  $file = gzFavoritesFile($companyId, $user);
  if (!is_dir(dirname($file))) {
      mkdir(dirname($file), 0775, true);
  }
  file_put_contents($file, json_encode(array_values($list), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// la pagina corrente è nei preferiti dell'utente?
function gzIsFavorite($companyId, $user, $scriptlink) {
  foreach (gzFavoritesFor($companyId, $user) as $f) {
      if ($f['link'] === $scriptlink) {
          return true;
      }
  }
  return false;
}

// la pagina corrente è nei preferiti dell'utente sulla tabella del DB? (es. tema lte)
function gzIsFavoriteDb($companyId, $user,$scriptlink) {
  global $gTables;
  $link_isset=gaz_dbi_get_row($gTables['breadcrumb'],'codice_aziend',$companyId,"AND adminid='".$user."' AND exec_mode = 4 AND link = '".gaz_dbi_real_escape_string($scriptlink)."' LIMIT 1");
  return $link_isset ? true : false;
}


// sigla di una pagina dal nome del file: prima lettera del prefisso + il resto in maiuscolo
// es. report_docven.php -> RDOCVEN, admin_utente.php -> AUTENTE
// sigla del pulsante: quella scelta dall'utente nei preferiti, altrimenti ricavata dal nome del file
function gzPageCode($scriptlink, $custom = '') {
  if ($custom !== '') {
      return $custom;
  }
  $base = pathinfo(basename((string) $scriptlink), PATHINFO_FILENAME);
  $parts = explode('_', $base);
  $code = mb_strtoupper(mb_substr($parts[0], 0, 1)) . mb_strtoupper(implode('', array_slice($parts, 1)));
  return $code !== '' ? $code : mb_strtoupper($base);
}
