<?php

/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
  (http://www.devincentiis.it)
  <http://gazie.sourceforge.net>
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
    
    function get_backup_path() {
        $sysdisk = getcwd();
        if(php_uname('s')=='Windows NT'){
            $sysdisk=explode("\\", $sysdisk );
            $sysdisk = array_slice( $sysdisk, 0, count($sysdisk)-2);
            return implode("\\", $sysdisk)."\\data\\files\\backups\\";
        } else {
            $sysdisk=explode("/", $sysdisk );
            $sysdisk = array_slice( $sysdisk, 0, count($sysdisk)-2);
            return implode("/", $sysdisk)."/data/files/backups/";
        }
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

    function testDbBackup( $days = 10 ) {
        // Antonio De Vincentiis 2 Luglio 2009
        global $gTables;
        $r = gaz_dbi_get_row($gTables['config'], 'variable', 'last_backup');
        if ( $days>0) {
            $dl = new DateTime($r['cvalue']);
            $dl->modify('+'.$days.' days');
            $dn = new DateTime("now");
            if ($dn > $dl) {
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return $r['cvalue'];
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

?>