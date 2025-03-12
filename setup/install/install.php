<?php
/* $Id: install.php,v 1.17 2011/01/01 11:08:15 devincen Exp $
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
require("../../config/config/gconfig.php");
require('../../library/include/'.$NomeDB.'.lib.php');
if ( $debug_active ) {
	error_reporting(E_ALL);
} else {
	error_reporting($error_reporting_level);
}

$err=[];
//
// Ottiene in qualche modo il prefisso delle tabelle.
//
if (isset($_SESSION['table_prefix'])) {
   $table_prefix=substr($_SESSION['table_prefix'],0,12);
} elseif (isset($_POST['tp'])) {
	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {
		$table_prefix=filter_var(substr($_POST['tp'],0,12),FILTER_SANITIZE_ADD_SLASHES);
	} else {
		$table_prefix=addslashes(substr($_POST['tp'],0,12));
	}
} elseif(isset($_GET['tp'])) {
	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {
		$table_prefix=filter_var(substr($_GET['tp'],0,12),FILTER_SANITIZE_ADD_SLASHES);
	} else {
		$table_prefix=addslashes(substr($_GET['tp'],0,12));
	}
} else {
	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {
		$table_prefix=filter_var(substr($table_prefix,0,12),FILTER_SANITIZE_ADD_SLASHES);
	} else {
		$table_prefix=addslashes(substr($table_prefix,0,12));
	}
}
//
// controllo directory scrivibili da apache (www-data) ed estensioni del php
// estensioni richieste da GAzie
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
  $usrwww = ['name' => 'web server'];
} else {
	$usrid=posix_getuid();
	$usrwww=posix_getpwuid($usrid);
}
if (!is_writable(DATA_DIR.'files/')) { //questa per archiviare i documenti
  echo DATA_DIR.'files/ --> '.$usrwww['name'].' permission = '.substr(sprintf('%o', fileperms(DATA_DIR.'files/')),-3).'<br/>';
  $err[] = 'no_data_files_writable';
}
if (!is_writable(K_PATH_CACHE)) { //questa per permettere a TCPDF di inserire le immagini
  echo K_PATH_CACHE.' --> '.$usrwww['name'].' permission = '.substr(sprintf('%o', fileperms(K_PATH_CACHE)),-3).'<br/>';
  $err[] = 'no_tcpdf_cache_writable';
}
$extreq=['MySQLi','intl','xml','gd','xsl','openssl'];
foreach($extreq as $v){
  if(!extension_loaded($v)){
    $err[] = 'Extension <b>php-'.$v.'</b> is required';
  }
}
//
// fine controllo directory scrivibili
//
if (!isset($_POST['hidden_req'])){           // al primo accesso allo script
    $form['hidden_req'] = '';
    $form['lang'] = 'italian';
    if(connectIsOk() && databaseIsOk() && getDbVersion()!==false) {    // verifico la presenza della base dati
      $form['install_upgrade'] = 'upgrade';
      $form['lang'] = getLang();
      if (databaseIsAlign()) {               // la base dati e' aggiornata!
         $err[] = 'is_align';
      }
    } elseif (!connectIsOk()) {              // non si connette al server
      $err[] = 'no_conn';
    } else {                                 // se si connette ma non trova la base dati allora prova ad installarla
      $form['install_upgrade'] = 'install';
    }
} else {                                     // negli accessi successivi
    connectIsOk();
    $form['hidden_req'] = substr($_POST['hidden_req'],0,20);
    $form['lang'] = substr($_POST['lang'],0,16);
    $form['install_upgrade'] = substr($_POST['install_upgrade'],0,16);
    if (isset($_POST['upgrade'])) {          // AGGIORNO
      if (databaseIsAlign()) {             // la base dati e' aggiornata!
        $err[] = 'is_align';
        if (strlen($form['hidden_req'])>10 && substr($form['hidden_req'], 0, 10)=='update_to_' && substr($form['hidden_req'], -4)=='.php') {
          // il db è allineato ma ho trovato da eseguire uno script php  correlato
          include($form['hidden_req']);
        }
      } else {
        connectToDB();
        executeModulesUpdate();// Antonio Germani - prima di eseguire la modifica del numero versione archivi controllo l'aggiornamento dei moduli extra GAzie
        $exe_script = executeQueryFileUpgrade($table_prefix);
        if ($exe_script) {
          include($exe_script);
        }

      }
    }
    if (isset($_POST['install'])) {          //INSTALLO
        // recupero il file sql d'installazione nella directory setup/install/
        // e possibilmente nella lingua selezonata dall'utente
        // che deve avere il nome example: "install_5.2.english.php"
        $file=getInstallSqlFile($form['lang']);
        if (executeQueryFileInstall($file,$Database,$table_prefix)){
            // se va a buon fine controllo eventuali file di aggiornamento
            $form['install_upgrade'] = 'upgrade';
            $form['lang'] = getLang();
            if (databaseIsAlign()) {         // la base dati e' aggiornata!
               $form['lang'] = getLang();
               $err[] = 'is_align';
            }
        }
    }
}

require("../../language/".$form['lang']."/setup.php");

function databaseIsAlign()
{
      // Antonio De Vincentiis 2 Luglio 2009
      connectToDB ();
      $lastSql=getSqlFileVersion();
      if (getDbVersion() < $lastSql[2]) {
        return false;
      } else {
        return true;
      }
}

function archiviIsOk($currentDbVersion, $sqlFiles)
{
    $last = end($sqlFiles);
    if ($last[2] == $currentDbVersion) {
        return True;
    } else {
        return False;
    }
}

function getDbVersion()
{
    global $table_prefix;
    $query = "SELECT cvalue FROM `".$table_prefix."_config` WHERE variable = 'archive'";
	try {
		$result = gaz_dbi_query ($query);
		if ($result) {
			$versione = gaz_dbi_fetch_array($result);
			return $versione[0];
		}
	} catch (Exception $e) {
	}
	return false;
}

function getCompanyNumbers()
{
    global $table_prefix;
    $query = "SELECT codice FROM `".$table_prefix."_aziend`";
    $result = gaz_dbi_query ($query);
    $companyNo = array();
	while($r=gaz_dbi_fetch_array($result)){
		$companyNo[]=$r['codice'];
	}
    return $companyNo;
}

function getLang()
{
    global $table_prefix;
	try {
		$query = "SELECT cvalue FROM `".$table_prefix."_config` WHERE variable = 'install_lang'";
		$result = gaz_dbi_query ($query);
		if ($result) {
			$versione = gaz_dbi_fetch_array($result);
			if ($versione) {
				return $versione[0];
			}
		}
	} catch (Exception $e) {
	}
	return 'italian';
}

function getSqlFileVersion()
{
    // Luigi Rambaldi 13 Ottobre 2005
    $fileArray = Array();
    $structArray = Array();
    $disorderedStructArray = Array();
    $relativePath = '../../setup/install/';
    if ($handle = opendir($relativePath)) {
       while ($file = readdir($handle)) {
             if(($file == ".") or ($file == "..")) continue;
             if(!preg_match("/^update_to_[0-9]+\.[0-9]\.[0-9]+\.sql$/",$file) &&
                !preg_match("/^update_to_[0-9]+\.[0-9]+\.sql$/",$file)) continue; //filtro per estensione .sql dei nomi dei file
             $fileArray[] = $file; // push sull'accumulatore
       }
       // conversione del $fileArray nelle corrispondenti strutture (si ottiene un array disordinato).
       foreach($fileArray as $fileItem){
               $version = sqlFileScan($relativePath.$fileItem);
               if($version == Array()) continue; // bypass dei file sql che non contengono gli aggiornamenti
               $initVersion = $version[0];
               $finalVersion = end($version);
               $disorderedStructArray[] = Array($fileItem, $initVersion, $finalVersion);
       }
       usort($disorderedStructArray,"compareSqlFiles");
       foreach ($disorderedStructArray as $key => $value) {
               $structArray[$value[1]] = $value;
       }
       closedir($handle);
    }
    return end($structArray);
}

function getNextSqlFileName($currentDbVersion, $sqlFiles)
{
	$newvers = $currentDbVersion + 1;
    $namefile = '';
	foreach ($sqlFiles as $v) {
        if ($v[1] <= $newvers && $v[2] >= $newvers) {
            $namefile = $v[0];
        }
    }
	echo 'FILE='.$namefile.'<br>';
    return $namefile;
}

function executeQueryFileInstall($sqlFile,$Database,$table_prefix)
{
    // Luigi Rambaldi 13 Ottobre 2005 - last rev. Antonio de Vincentiis 27 Giugno 2011
    global $Database,$link;
    // Inizializzazione accumulatore
    $tmpSql=file_get_contents( "../../setup/install/". $sqlFile );
    $tmpSql = preg_replace("/gaz_/", $table_prefix.'_', $tmpSql);  //sostituisco gaz_ con il prefisso personalizzato
    $tmpSql = preg_replace("/CREATE DATABASE IF NOT EXISTS gazie/", "CREATE DATABASE IF NOT EXISTS ".$Database, $tmpSql);
    $tmpSql = preg_replace("/USE gazie/", "USE ".$Database, $tmpSql);
    // Iterazione per ciascuna linea del file.
    $lineArray = explode(";\n",$tmpSql);
    foreach($lineArray as $l){
        $l=ltrim($l);
        if (!empty($l)) {
           gaz_dbi_query($l);
        }
    }
    return true;
}

function executeQueryFileUpgrade($table_prefix) // funzione dedicata alla gestione delle sottosezioni
{
    global $disable_set_time_limit;
    if (!$disable_set_time_limit) {
        set_time_limit (300);
    }
    // Luigi Rambaldi 13 Ottobre 2005
    // Inizializzazione accumulatore
    $sql = "";
    $currentDbVersion=getDbVersion();
    $nextDbVersion =  $currentDbVersion + 1; // versione del'upgrade da individuare per l'aggiornamento corrente (contiguità nella numerazione delle versioni).
    $stopDbVersion = $currentDbVersion + 2;
    $sqlFile = getNextSqlFileName($currentDbVersion,getSqlFiles());
    // trovo l'ultima  sottosezione (individuabile a partire dalla versione corrente del Database)
    // Iterazione per ciascuna linea del file.
    $lineArray = file($sqlFile);
    $parsingFlag = False; // flag per individuare ciascuna sottosezione, corrispondente a cisacuna versione del DB
    $companies=getCompanyNumbers();
    $activateWhile = False; // flag per attivare il ciclo while
    foreach($lineArray as $line) {
        if (preg_match("/UPDATE[ \n\r\t\x0B]+(`){0,1}gaz_config(`){0,1}[ \n\r\t\x0B]+SET[ \n\r\t\x0B]+(`){0,1}cvalue(`){0,1}[ \n\r\t\x0B]*=[ \n\r\t\x0B]*\'$nextDbVersion\'/i", $line)) {
            $parsingFlag = True;
        }
        if (preg_match("/UPDATE[ \n\r\t\x0B]+(`){0,1}gaz_config(`){0,1}[ \n\r\t\x0B]+SET[ \n\r\t\x0B]+(`){0,1}cvalue(`){0,1}[ \n\r\t\x0B]*=[ \n\r\t\x0B]*\'$stopDbVersion\'/i", $line)) {
            $parsingFlag = False;
            break;
        }
        if($parsingFlag) {
            if (preg_match("/START_WHILE/i", $line)) {
              $activateWhile = True;
              $line='';
            }
            if (preg_match("/STOP_WHILE/i", $line)) {
              $activateWhile = False;
              $line='';
            }
            $sql .= $line;
            // Il punto e virgola indica la fine di ciascuna istruzione SQL , ciascuna di esse viene accumulata
			if (!preg_match("/;\s*\n/", $sql)) {
                continue;// incremento dell'accumulatore
            }
            // Sostituisce il prefisso standard ed elimina il punto e virgola
            $sql = preg_replace("/gaz_/", $table_prefix.'_', $sql);
            $sql = preg_replace("/;\s*\n/", "\n", $sql);
            if ($activateWhile){
               // Esegue l'istruzione sulle tabelle di tutte le aziende installate.
               $sql_ori=$sql;
               foreach ($companies as $i) {
                    $sql = preg_replace("/XXX/", sprintf('%03d',$i), $sql_ori);
                    if (!gaz_dbi_query($sql)) { // si collega al DB
                        echo "Query Fallita";
                        echo "$sql <br/>";
                        exit;
                    }
               }
               $sql = "";// ripristino dell'accumulatore
            } else {
               // Esegue una singola istruzione.
               if (!gaz_dbi_query($sql)) { // si collega al DB
                   echo "Query Fallita";
                   echo "$sql <br/>";
                   exit;
               } else {
                   $sql = "";// ripristino dell'accumulatore a seguito dell'istruzione
               }
            }
        }
    }
	// trovo un eventuale file per  script php correlato alle query di aggiornamento
	$exe_script=executeScriptFileUpgrade($sqlFile);
	return $exe_script;
}


function getInstallSqlFile($lang)
{
//serve per trovare il primo file .sql di installazione piu' recente e possibilmente nella lingua scelta
$lastInstallSqlFile = "";
$ctrlLastVersion = 0;
$relativePath = '../../setup/install';
if ($handle = opendir($relativePath)) {
    while ($file = readdir($handle)) {
        if(($file == ".") || ($file == "..")) continue;
        if (preg_match("/^install_([0-9]{1,2})\.([0-9]{1,2})\.sql$/", $file, $regs)) {
           //faccio il push solo se e' una versione di valore maggiore della precedente
           $versionFile =  $regs[1]*100+$regs[2];
           if ($versionFile > $ctrlLastVersion) {
              $lastInstallSqlFile = $file;
              $ctrlLastVersion = $versionFile;
           }
        } elseif (preg_match("/^install_([0-9]{1,2})\.([0-9]{1,2})\.$lang\.sql$/", $file, $regs)) {
           // ho trovato una versione in lingua di valore almeno uguale
           $versionFile =  $regs[1]*100+$regs[2];
           if ($versionFile >= $ctrlLastVersion) {
              $lastInstallSqlFile = $file;
              $ctrlLastVersion = $versionFile;
           }
        } else {
           continue;
        }
    }
    closedir($handle);
}
return $lastInstallSqlFile;
}

function compareSqlFiles($struct1, $struct2)
{
    return ($struct2[2] < $struct1[1]) ? 1 : -1;
}


function sqlFileScan($file)
{
    global $table_prefix;
    $versions = Array();
    $relativePath = '../../setup/install';
    $lineArray = file($file);
    foreach($lineArray as $line) {
         if(preg_match("/UPDATE[ \n\r\t\x0B]+(`){0,1}gaz_config(`){0,1}[ \n\r\t\x0B]+SET[ \n\r\t\x0B]+(`){0,1}cvalue(`){0,1}[ \n\r\t\x0B]+=[ \n\r\t\x0B]+'/i", $line)){
             $versionArray = preg_split("/[=']/", $line) ;// In caso dell'uso degli apici per denotare i valori delle versioni
             $versions[] = trim ($versionArray[2]);// Eliminazione spazi e posizionamento.
         }
         if(preg_match("/UPDATE[ \n\r\t\x0B]+(`){0,1}gaz_config(`){0,1}[ \n\r\t\x0B]+SET[ \n\r\t\x0B]+(`){0,1}cvalue(`){0,1}[ \n\r\t\x0B]+=[ \n\r\t\x0B]+[0-9]+/i", $line)){
             $versionArray = preg_split("/[=Ww]/", $line) ;// In caso in cui non vengono usato gli apici per denotare i valori delle versioni (wW serve per identificare il where/WHERE)
             $versions[] = trim ($versionArray[1]);
         }
    }
return $versions;
}

function getSqlFiles()
{
$fileArray = Array();
$structArray = Array();
$disorderedStructArray = Array();
$relativePath = '../../setup/install';
if ($handle = opendir($relativePath)) {
    while ($file = readdir($handle)) {
        if(($file == ".") or ($file == ".."))
            continue;
        if(!preg_match("/^update_to_[0-9]+\.[0-9]\.[0-9]+\.sql$/",$file) &&
           !preg_match("/^update_to_[0-9]+\.[0-9]+\.sql$/",$file) ) continue; //filtro per estensione .sql dei nomi dei file
        $fileArray[] = $file; // push sull'accumulatore
    }
    // conversione del $fileArray nelle corrispondenti strutture (si ottiene un array disordinato).
    foreach($fileArray as $fileItem){
        $version = sqlFileScan($fileItem);
        if($version == Array()) continue; // bypass dei file sql che non contengono gli aggiornamenti
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
return $structArray;
}

function executeScriptFileUpgrade($name_sql){ // se ho un file php da eseguire dopo la query sql
	$filename = pathinfo($name_sql, PATHINFO_FILENAME).'.php';
	if (file_exists($filename)) {
		// ho un file da eseguire alla fine delle query
		return $filename;
	} else {
		return false;
	}
}

function executeModulesUpdate(){// Antonio Germani 12/07/2022 - funzione per eseguire gli eventuali upgrade inviati dai moduli tramite il file upgrade_db.php
  global $table_prefix;
  $companies=getCompanyNumbers();
  $query = "SELECT name FROM `".$table_prefix."_module`";// prendo tutti i nomi dei moduli attivi
  $result = gaz_dbi_query ($query);

  while($module=gaz_dbi_fetch_array($result)){ // in ogni modulo attivo
    $upgrade_db=[];
    if (file_exists("../../modules/". $module['name'] ."/upgrade_db.php")){ // cerco se c'è il file di aggiornamento
      include("../../modules/". $module['name'] ."/upgrade_db.php"); // carico l'array
      if (isset($upgrade_db)){ //se c'è
        // prendo l'ultima versione archivio
        $version = gaz_dbi_get_row($table_prefix.'_config', 'variable', 'archive')['cvalue']+1;
        foreach ($upgrade_db as $k => $v){ //ciclo le istruzioni in base alla chiave
          if ($k == $version){ // se la chiave è la stessa della versione db che risulterà dopo questo aggiornamento (vedi +1 sopra)
            foreach ($upgrade_db[$k] as $instruction){ //ciclo le istruzioni e le eseguo per ogni azienda
              if (preg_match("/XXX/",$instruction)) { // query ricorsive sulle tabelle di tutte le aziende
                foreach ($companies as $i) {
                  $sql = preg_replace("/XXX/", sprintf('%03d',$i), $instruction);
                  if (!gaz_dbi_query($sql)) { //se non è stata eseguita l'istruzione lo segnalo
                    echo "Query Fallita";
                    echo "$sql <br/>";
                    exit;
                  }
                }
              }
            }
          }
        }
        foreach ($upgrade_db as $k => $v){ //ciclo nuovamente le istruzioni in base alla chiave
          if ($k == $version){  // se la chiave è la stessa della versione db che risulterà dopo questo aggiornamento (vedi +1 sopra)
            foreach ($upgrade_db[$k] as $instruction){ //ciclo le istruzioni e le eseguo per le tabelle comuni
              if (!preg_match("/XXX/",$instruction)) { // query ricorsive sulle tabelle comuni
                if (!gaz_dbi_query($instruction)) { //se non è stata eseguita l'istruzione lo segnalo
                  echo "Query Fallita";
                  echo "$instruction <br/>";
                  exit;
                }
              }
            }
          }
        }

      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="author" content="Antonio De Vincentiis https://www.devincentiis.it">
    <link rel="stylesheet" type="text/css" href="../../library/theme/g7/scheletons/default.css">
    <link rel="shortcut icon" href="../library/images/favicon.ico">
    <title><?php echo $msg['title'];?></title>
</head>
<body>
    <br /><br /><br />
    <form method="POST">
    <input type="hidden" value="<?php echo $form['hidden_req'];?>"      name="hidden_req">
    <input type="hidden" value="<?php echo $form['install_upgrade'];?>" name="install_upgrade">
    <input type="hidden" value="<?php echo $form['lang'];?>"            name="lang">
    <input type="hidden" value="<?php echo $table_prefix; ?>"           name="tp">
    <table align="center">
    <tbody>
        <tr>
            <td align="center"><img src="../../library/images/logo_180x180.png">
            </td>
            <td colspan="2" align="center"  style="vertical-align:middle">
        <?php
        if ($form['install_upgrade']=='install') {
            echo $msg['gi_lang'].': <select name="lang" class="FacetSelect" onchange="this.form.submit();">';
            if ($handle = opendir('../../language')) {
              while ($dir = readdir($handle)) {
                  if(($dir == ".") || ($dir == "..") || ($dir == ".svn")) continue;
                     $selected="";
                     if ($form['lang'] == $dir) {
                        $selected = " selected ";
                     }
                     echo "<option value=\"".$dir."\"".$selected." >".ucfirst($dir)."</option>\n";
                  }
                  closedir($handle);
            }
            echo '</select> _ <img src="../../language/'.$form['lang'].'/flag.png" >';
        } else {
            echo '<img src="../../language/'.$form['lang'].'/flag.png" >';
        }
        ?>
        </td>
        </tr>
        <tr>
            <td colspan="3" class="FacetDataTD" align="center">
            <strong><?php echo $msg['gi_'.$form['install_upgrade']].' GAzie '.GAZIE_VERSION ?></strong>
                <?php
                if ($form['install_upgrade']=='upgrade') {
                     $lastSql=getSqlFileVersion();
                     echo '<br />'.$msg['gi_upg_from'].' '.getDbVersion().' '.$msg['gi_upg_to'].' '.$lastSql[2];
                }
                ?>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="FacetDataTD" align="center">
            <?php
            if (count($err)==0) {
               echo '<input name="'.$form['install_upgrade'].'" type="submit" value="'.strtoupper($msg[$form['install_upgrade']]).'!">';
            } else {
               foreach ($err as $v){
                  echo $v." <br>";
                  if ($v=='is_align'){
                     echo '<input  onClick="location.href=\'../../modules/root/admin.php\'" name="'.$form['install_upgrade'].'" type="button" value="'.$msg['gi_is_align'].'">';
                     echo "\n <br />".$msg['gi_usr_psw']." <br />";
                  } else {
                     echo '<span class=\"btn btn-xs btn-default\"><i class=\"glyphicon glyphicon-remove\"></i></span><br /> ';
                 }
               }
            }
            ?>
            </td>
        </tr>
</tbody>
</table>
</form>
</body>
</html>
