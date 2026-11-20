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

class CheckDbAlign {

    function TestDbAlign() {
        // Antonio De Vincentiis 2 Luglio 2009
        $lastSql = $this->getSqlFileVersion();
        $dbVer = $this->getDbVersion();
        if ($dbVer < $lastSql[2]) {
            return array($dbVer, $lastSql[2]);
        } else {
            return false;
        }
    }

    function getDbVersion() {
        // Antonio De Vincentiis 2 Luglio 2009
        global $gTables;
        $r = gaz_dbi_get_row($gTables['config'], 'variable', 'archive');
        return $r['cvalue'];
    }

    function backupMode($new_mode = false) {
        /* in $new_mode di deve passare il valore della nuova modalità
         * (external o internal) se la si vuole modificare tramite l'apposita
         * interfaccia sul report, altrimenti restituisce il valore presente sul db
         */
        global $gTables;
        if ($new_mode) {
            gaz_dbi_put_row($gTables['config'], 'variable', 'backup_mode', 'cvalue', $new_mode);
            return true;
        } else {
            $r = gaz_dbi_get_row($gTables['config'], 'variable', 'backup_mode');
            return $r['cvalue'];
        }
    }

    function keepMode(){
      global $gTables;
      $keep = gaz_dbi_get_row($gTables['config'], 'variable', 'keep_backup');
      $kvals= preg_split('/([^0-9]{1,})/', $keep['cvalue']);
      if (!isset($kvals[1])) $kvals[1] = '7';
      if (intval($kvals[0])<1) $kvals[0] = '1';
      return $kvals;
    }

    function get_system_disk() {
        $sysdisk = getcwd();
        if(php_uname('s')=='Windows NT'){
            $sysdisk=explode("\\", $sysdisk );
            return $sysdisk[0];
        } else {
            $sysdisk=explode("/", $sysdisk );
            $sysdisk = array_slice( $sysdisk, 0, count($sysdisk)-2);
            return implode("/", $sysdisk);
        }
    }

    function testDbBackup( int $days ) {
      global $gTables;
      $r = gaz_dbi_get_row($gTables['config'], 'variable', 'last_backup');
      if ( $days>0) {
        $dl = new DateTime($r['cvalue']);
        $dl->modify('+'.$days.' days');
        $dn = new DateTime("now");
        if ($dn > $dl) {
          return true;
        } else {
          return false;
        }
      } else {
        return false;
      }
    }

    function getSqlFileVersion() {
        // Luigi Rambaldi 13 Ottobre 2005
        $fileArray = Array();
        $structArray = Array();
        $disorderedStructArray = Array();
        $relativePath = '../../setup/install/';

        function compareSqlFiles($struct1, $struct2) {
            // Luigi Rambaldi 13 Ottobre 2005
            if ($struct2[2] < $struct1[1])
                return 1;
            else
                return 0;
        }

        if ($handle = opendir($relativePath)) {
            while ($file = readdir($handle)) {
                if (($file == ".") or ( $file == ".."))
                    continue;
                if (!preg_match("/^update_to_[0-9]+\.[0-9]\.[0-9]+\.sql$/", $file) &&
                        !preg_match("/^update_to_[0-9]+\.[0-9]+\.sql$/", $file))
                    continue; //filtro per estensione .sql dei nomi dei file
                $fileArray[] = $file; // push sull'accumulatore
            }
            // conversione del $fileArray nelle corrispondenti strutture (si ottiene un array disordinato).
            foreach ($fileArray as $fileItem) {
                $version = $this->sqlFileScan($relativePath . $fileItem);
                if ($version == Array())
                    continue; // bypass dei file sql che non contengono gli aggiornamenti
                $initVersion = $version[0];
                $finalVersion = end($version);
                $disorderedStructArray[] = Array($fileItem, $initVersion, $finalVersion);
            }
            usort($disorderedStructArray, "compareSqlFiles");
            foreach ($disorderedStructArray as $key => $value) {
                $structArray[$value[1]] = $value;
            }
            closedir($handle);
        }
        ksort($structArray);
        return end($structArray);
    }

    function sqlFileScan($file) {
        // Luigi Rambaldi 13 Ottobre 2005
        global $table_prefix;
        $versions = Array();
        $lineArray = file($file);
        foreach ($lineArray as $line) {
            if (preg_match("/UPDATE[ \n\r\t\x0B]+(`){0,1}gaz_config(`){0,1}[ \n\r\t\x0B]+SET[ \n\r\t\x0B]+(`){0,1}cvalue(`){0,1}[ \n\r\t\x0B]+=[ \n\r\t\x0B]+'/", $line)) {
                $versionArray = preg_split("/[=']/", $line); // In caso dell'uso degli apici per denotare i valori delle versioni
                $versions[] = trim($versionArray[2]); // Eliminazione spazi e posizionamento.
            }
            if (preg_match("/UPDATE[ \n\r\t\x0B]+(`){0,1}gaz_config(`){0,1}[ \n\r\t\x0B]+SET[ \n\r\t\x0B]+(`){0,1}cvalue(`){0,1}[ \n\r\t\x0B]+=[ \n\r\t\x0B]+[0-9]+/", $line)) {
                $versionArray = preg_split("/[=Ww]/", $line); // In caso in cui non vengono usato gli apici per denotare i valori delle versioni (wW serve per identificare il where/WHERE)
                $versions[] = trim($versionArray[1]);
            }
        }
        return $versions;
    }

}

function getMenuData($lang_id, $module_name = false) {
  global $gTables;
  $lang = gaz_dbi_get_row($gTables['languages'],'lang_id',$lang_id);
  $data = [];
  $rsm1 = gaz_dbi_dyn_query("*", $gTables['module'],'id >= 2','weight ASC');
  while ($m1 = gaz_dbi_fetch_assoc($rsm1)) {
    if ($module_name && $module_name != $m1['name']) continue;
    // prendo i files degli script php ad eccezione di quelli delle funzioni, dei menu e delle lingue
    $data[$m1['name']]['scripts'] = [];
    $files = glob('../'.$m1['name'].'/*.php');
    $patterns ='/\/docume|\/search|\/lang|\/lib|\/delete|\/ajax|\/rec_|\/get_|\/menu|\/send|\/sync|\/dash|\/invsta/';
    foreach ($files as $rf) {
      $bnrf = basename($rf);
      if ( $bnrf == 'menu.'.strtolower($lang['title_native']).'.php') { // quando incontro quello relativo al menu della lingua sotto modifica mi carico i suoi valori che poi andrò a valorizzare sulla chiave file_menu
        require($rf);
        $data[$m1['name']]['file_menu'] = $transl[$m1['name']];
      }
      if (preg_match($patterns,$rf)  || !str_contains($bnrf, '_') ) continue;
      $data[$m1['name']]['scripts'][] = $bnrf;
    }
    $data[$m1['name']]['m1'] = $m1;
    $rsm2 = gaz_dbi_dyn_query("*", $gTables['menu_module'],'id_module = '.$m1['id'],'weight ASC');
    while ($m2 = gaz_dbi_fetch_assoc($rsm2)) {
      $m2id = $m2['id'];
      unset($m2['id']);
      $m2['file_label'] = $transl[$m1['name']]['m2'][$m2['translate_key']][1];
      $m2['file_title'] = $transl[$m1['name']]['m2'][$m2['translate_key']][0];
      $m2['module_name'] = $m1['name'];
      $m2['url_script_basename'] = parse_url($m2['link'],PHP_URL_PATH);
      $m2['id_menu_module'] = $m2id;
      $m2['id_menu_script'] = 0; // per distinguere un menù di secondo livello da uno di terzo che sarà > 0
      $data[$m1['name']]['db_menu'][] = $m2;
      $rsm3 = gaz_dbi_dyn_query("*", $gTables['menu_script'],'id_menu = '.$m2id,'weight ASC');
      while ($m3 = gaz_dbi_fetch_assoc($rsm3)) {
        $m3['file_label'] = $transl[$m1['name']]['m3'][$m3['translate_key']][1];
        $m3['file_title'] = $transl[$m1['name']]['m3'][$m3['translate_key']][0];
        $m3['module_name'] = $m1['name'];
        $m3['url_script_basename'] = parse_url($m3['link'],PHP_URL_PATH);
        $m3['id_module'] = $m1['id'];
        $m3['id_menu_module'] = $m2id;
        $m3['id_menu_script'] = $m3['id'];
        unset($m3['id']);
        unset($m3['id_menu']);
        $data[$m1['name']]['db_menu'][] = $m3;
      }
    }
  }
  return $data;
}


?>
