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
  scriva   alla   Free  Software Foundation,  Inc.,   675  Mass Ave,
  Cambridge, MA 02139, Stati Uniti.
  --------------------------------------------------------------------------
 */

function connectIsOk() {
   global $Host, $User, $Password, $link;
   $result = True;
   $link = @mysqli_connect($Host, $User, $Password) or ( $result = False); // In $result l'esito della connessione
   mysqli_options($link, MYSQLI_INIT_COMMAND, "SET SQL_MODE = ''");
   return $result;
}

function gaz_dbi_query($query, $ar = false) {
   global $link;
   $result = mysqli_query($link, $query);
   if (!$result) gaz_die ( $query, "72", __FUNCTION__ );
   if ($ar) {
      return mysqli_affected_rows($link);
   } else {
      return $result;
   }
}

function connectToDB() {
  global $link, $Host, $Database, $User, $Password;
  mysqli_report(MYSQLI_REPORT_OFF);
  $link = @mysqli_connect($Host, $User, $Password, $Database);
  if(!$link) {
    print "Was not found, << $Database >>  database! <br />
             Could not be installed, try to do so by <a href=\"../../setup/install/install.php\"> clicking HERE! </a><br />
             <br />Non &egrave; stata trovata la base dati di nome << $Database >>! <br />
             Potrebbe non essere stato installata, prova a farlo <a href=\"../../setup/install/install.php\"> cliccando QUI! </a> <br />
             <br />No se ha encontrado, la base de datos << $Database >>  ! <br />
			No pudo ser instalado, trate de hacerlo haciendo <a href=\"../../setup/install/install.php\">  clic AQU&Iacute;! </a>";
  } else {
    gaz_dbi_query("/*!50701 SET SESSION sql_mode='' */");
    gaz_dbi_query("/*M!100204 SET SESSION sql_mode='' */");
    mysqli_set_charset($link, 'utf8');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  }
}

function createDatabase($Database) {
   gaz_dbi_query("CREATE DATABASE $Database DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;") or die("ERRORE: il database $Database non &egrave; stato creato!");
}

function databaseIsOk() {
  global $link, $Host, $Database, $User, $Password;
  mysqli_report(MYSQLI_REPORT_OFF);
  $result = false;
  $link = @mysqli_connect($Host, $User, $Password, $Database);
  if($link) {
    $result = true;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  }
  return $result;
}

function gaz_dbi_fetch_array($resource, $mode='') {
	if (empty($mode)) {
		$result = mysqli_fetch_array($resource);
	} else {
		switch ($mode) {
			case 'NUM':
				$mode = MYSQLI_NUM;
				break;
		}
		$result = mysqli_fetch_array($resource, $mode);
	}
	return $result;
}

/** ENRICO FEDELE */
/* Possiamo usare questa funzione in futuro?
 *  Ritengo che sia decisamente più immediata, perchè restituisce giù un array associativo
 *  e ci evita di dover creare un array apposito in cui mettere quello che ci interessa
 */
function gaz_dbi_fetch_assoc($resource) {
   $result = mysqli_fetch_assoc($resource);
   return $result;
}

function gaz_dbi_real_escape_string($resource) {
   global $link;
   $result = mysqli_real_escape_string($link, $resource);
   return $result;
}

function gaz_dbi_fetch_row($resource) {
   $result = mysqli_fetch_row($resource);
   return $result;
}

function gaz_dbi_num_rows($resource) {
   $result = mysqli_num_rows($resource);
   return $result;
}

function gaz_dbi_fetch_object($resource) {
   $result = mysqli_fetch_object($resource);
   return $result;
}

function gaz_dbi_free_result($result) {
   mysqli_free_result($result);
}

//uso un metodo simile a quello di phpMyAdmin in sql.php per controllare i tipi di campo
function gaz_dbi_get_fields_meta($result) {
   $fields = array();
   $fields['num'] = mysqli_num_fields($result);
   for ($i = 0; $i < $fields['num']; $i++) {
      $data = mysqli_fetch_field($result);
      switch ($data->type) {
         // i numerici
         case 1:
         case 2:
         case 3:
         case 4:
         case 5:
         case 8:
         case 9:
         case 16:
         case 246: // campi numerici
            $data->numeric = 1;
            $data->blob = 0;
            $data->datetimestamp = 0;
            break;
         case 7:
         case 10:
         case 11:
         case 12:
         case 13: // campi datetimestamp
            $data->numeric = 0;
            $data->blob = 0;
            $data->datetimestamp = 1;
            break;
         case 252: // blob
            $data->numeric = 0;
            $data->blob = 1;
            $data->datetimestamp = 0;
            break;
         default:
            $data->blob = 0;
            $data->numeric = 0;
            $data->datetimestamp = 0;
            break;
      }
      $fields['data'][] = $data;
   }
   return $fields;
}

function gaz_dbi_get_row($table, $fnm, $fval, $other="", $cell="*") {
  global $link;
  $fval = mysqli_real_escape_string($link, $fval);
  $query = "SELECT $cell FROM $table WHERE $fnm = '$fval' $other";
  $result = gaz_dbi_query($query);
  if (!$result) gaz_die ( $query, "168", __FUNCTION__ );
  if (strpos($cell, "*") === FALSE) {
    $row = gaz_dbi_fetch_assoc($result);
    if ($row) {
      return $row[$cell];
    } else {
      return '';
    }
  } else {
    return gaz_dbi_fetch_assoc($result);
  }
}

function gaz_dbi_get_single_value($table, $campo, $where) {
  global $link;
  $query = "SELECT $campo FROM $table WHERE $where";
  $result = gaz_dbi_query($query);
  if (!$result) gaz_die ( $query, "182", __FUNCTION__ );
  $ris = gaz_dbi_fetch_array($result, MYSQLI_NUM);
  $rn = gaz_dbi_num_rows($result);
  if ($rn == 1) {
    return $ris[0];
  } else {
    return null;
  }
}

function gaz_dbi_put_row($table, $CampoCond, $ValoreCond, $Campo, $Valore) {
  $field_results = gaz_dbi_query("SELECT * FROM " . $table . " LIMIT 1");
  $field_meta = gaz_dbi_get_fields_meta($field_results);
  $where = ' WHERE ' . $CampoCond . ' = ';
  $query = "UPDATE " . $table . ' SET ' . $Campo . ' = ';
  for ($j = 0; $j < $field_meta['num']; $j++) {
    if ($field_meta['data'][$j]->name == $Campo) {
       if ($field_meta['data'][$j]->blob && !empty($Valore)) {
          $query .= '0x' . bin2hex($Valore);
       } elseif ($field_meta['data'][$j]->numeric && $field_meta['data'][$j]->type != 'timestamp') {
          $query .= floatval($Valore);
       } else {
          $elem = addslashes($Valore); // risolve il classico problema dei caratteri speciali per inserimenti in SQL
          $elem = preg_replace("/\\\'/", "''", $elem); //cambia lo backslash+singlequote con 2 singlequote come fa phpmyadmin.
          $query .= "'" . $elem . "'";
       }
    }
    if ($field_meta['data'][$j]->name == $CampoCond) {
       if ($field_meta['data'][$j]->blob && !empty($ValoreCond)) {
          $where .= '0x' . bin2hex($Valore);
       } elseif ($field_meta['data'][$j]->numeric && $field_meta['data'][$j]->type != 'timestamp') {
          $where .= floatval($ValoreCond);
       } else {
          $elem = addslashes($ValoreCond); // risolve il classico problema dei caratteri speciali per inserimenti in SQL
          $elem = preg_replace("/\\\'/", "''", $elem); //cambia lo backslash+singlequote con 2 singlequote come fa phpmyadmin.
          $where .= "'" . $elem . "'";
       }
    }
  }
  $query .= $where . ' LIMIT 1';
  $result = gaz_dbi_query($query);
  if (!$result) gaz_die ( $query, "224", __FUNCTION__ );
  return $result;
}

function gaz_dbi_put_query($table, $where, $Campo, $Valore) {
  $query = "UPDATE $table SET $Campo='$Valore' WHERE $where";
  $result = gaz_dbi_query($query);
  if (!$result) gaz_die ( $query, "231", __FUNCTION__ );
}

function gaz_dbi_del_row($table, $fname, $fval) {
  global $link;
  $query = "DELETE FROM $table WHERE $fname = '$fval'";
  $result = gaz_dbi_query($query) or die(" Errore di cancellazione: " . mysqli_error($link));
  if (!$result) gaz_die ( $query, "238", __FUNCTION__ );
}

// restituisce l'id dell'ultimo insert
function gaz_dbi_last_id() {
   global $link;
   $num_id = mysqli_insert_id($link);
   return $num_id;
}

// restituisce il numero record di una query
// versione piu' lineare della precedente MR
function gaz_dbi_record_count($table, $where = '', $group_by = '') {
   $where = $where ? "WHERE $where" : '';
   $group_by = $group_by ? "GROUP BY $group_by" : '';
   $query = "SELECT NULL FROM $table $where $group_by";
   $result = gaz_dbi_query($query);
   if (!$result) gaz_die($query, __LINE__, __FUNCTION__);
   return gaz_dbi_num_rows($result);
}

// funzione che compone una query con i parametri: tabella, where, orderby, limit e passo (riga di inizio e n. record)
function gaz_dbi_dyn_query($select, $tabella, $where = 1, $orderby = 2, $limit = 0, $passo = 2000000, $groupby = '') {
   global $session;
   $query = "SELECT " . $select . " FROM " . $tabella;
   if ($where != '') {
      $query .= " WHERE ". $where;
   }

   if ($groupby != '') {
      $query .= " GROUP BY ". $groupby;
   }

   if ($orderby == '2') {
      $query .= " LIMIT " . $limit . ", " . $passo;
   } else {
      $query .= " ORDER BY " . $orderby . " LIMIT " . $limit . ", " . $passo;
   }
   //echo $query."<br>";
   //msgDebug($query);

   $result = gaz_dbi_query($query);
   if (!$result) gaz_die ( $query, "277", __FUNCTION__ );
   return $result;
}

// funzione array_is_assoc
function array_is_assoc(array $a) {
   $i = 0;
   foreach ($a as $k => $v) {
      if ($k !== $i++) {
         return true;
      }
   }
   return false;
}

// funzione gaz_dbi_fields_anagra
function gaz_dbi_fields_anagra() {
   global $gTables, $fields_anagra;
   if (!isset($fields_anagra)) {
       $fields_anagra = array();
       $rs_fields_anagra = gaz_dbi_query("SHOW COLUMNS FROM " . $gTables['anagra'] . " WHERE Field NOT LIKE '%_aes'");
       while ($rs_field_anagra = mysqli_fetch_assoc($rs_fields_anagra)) {
           $fields_anagra[] = $rs_field_anagra['Field'];
       }
   }
   return $fields_anagra;
}

// funzione gaz_aes_field_anagra
function gaz_aes_field_anagra($field) {
   $aes_field = '';
   $field_name = $field;
   $field_name_point = strrpos($field_name, '.');
   if ($field_name_point !== false) {
      $field_name = substr($field_name, $field_name_point + 1, strlen($field_name) - $field_name_point);
   }
   switch ($field_name) {
      case 'ragso1':
      case 'ragso2':
      case 'sedleg':
      case 'legrap_pf_nome':
      case 'legrap_pf_cognome':
      case 'indspe':
      case 'latitude':
      case 'longitude':
      case 'telefo':
      case 'fax':
      case 'cell':
      case 'codfis':
      case 'pariva':
      case 'e_mail':
      case 'pec_email':
         //$aes_field.= "CONVERT(AES_DECRYPT(UNHEX(" . $field . "_aes), '" . $_SESSION['aes_key'] . "') USING utf8)";
         /**/$aes_field .= $field;
         break;
      default:
         $aes_field .= $field;
         break;
   }
   return $aes_field;
}

function gaz_dbi_get_anagra($table, $fnm, $fval) {
   global $gTables;
   $fields_anagra = gaz_dbi_fields_anagra();
   $fields = '';
   foreach ($fields_anagra as $field_anagra) {
      if ($field_anagra == 'fatt_email') { // gestione ambiguità fatt_email
         $fields .= (empty($fields) ? '' : ', ') . $gTables['anagra'] . "." . gaz_aes_field_anagra($field_anagra) . " AS $field_anagra";
      } elseif ($field_anagra == 'custom_field') { // gestione ambiguità custom_field
         $fields .= (empty($fields) ? '' : ', ') . $gTables['anagra'] . "." . gaz_aes_field_anagra($field_anagra) . " AS $field_anagra";
      } else {
         $fields .= (empty($fields) ? '' : ', ') . gaz_aes_field_anagra($field_anagra) . " AS $field_anagra";
      }
   }
   $query = "SELECT $fields, " . $gTables['clfoco'] . ".* FROM $table WHERE $fnm = '$fval'";
   $result = gaz_dbi_query($query);
   if (!$result) gaz_die ( $query, "353", __FUNCTION__ );
   return gaz_dbi_fetch_assoc($result);
}

// funzione gaz_dbi_query_anagra
function gaz_dbi_query_anagra($select, $tabella, $where, $orderby, $limit = 0, $passo = 2000000, $groupby = '') {
   global $session;
   $select_fields = '';
   $select_is_assoc = array_is_assoc($select);
   foreach ($select as $field_name => $field_alias) {
      $field = ($select_is_assoc) ? $field_name : $field_alias;
      $field_alias_point = strrpos($field_alias, '.');
      if ($field_alias_point !== false) {
         $field_alias = substr($field_alias, $field_alias_point + 1, strlen($field_alias) - $field_alias_point);
      }
      $select_fields .= ((empty($select_fields)) ? "" : ", ") . gaz_aes_field_anagra($field) . (($field == '*' || empty($field_alias)) ? "" : " AS $field_alias");
   }
   $query = "SELECT " . $select_fields . " FROM " . $tabella;
   if (count($where)) {
      $query .= " WHERE 1 ";
      foreach ($where as $condition_field => $condition_compare) {
         $query .= " AND " . gaz_aes_field_anagra($condition_field) . $condition_compare;
      }
   }

   if ($groupby != '') {
      $query .= " GROUP BY $groupby ";
   }

   if (count($orderby)) {
      $query .= " ORDER BY ";
      foreach ($orderby as $order_field => $order_value) {
         $query .= gaz_aes_field_anagra($order_field) . " " . $order_value . " ";
      }
   }
   $query .= " LIMIT " . $limit . ", " . $passo;
   //echo $query."<br>";
   //msgDebug($query);

   $result = gaz_dbi_query($query);
   if (!$result) gaz_die ( $query, "394", __FUNCTION__ );
   return $result;
}

function gaz_dbi_fields($table) {
   /*
    * $table - il nome della tabella all'interno dell'array $gTables
    * questa funzione genera un array(chiave=>valore) contenente tutte le chiavi
    * della tabella richiesta a valori nulli o 0 a secondo del tipo
    */
   global $link, $gTables;
   $acc = array();
   $field_results = gaz_dbi_query("SELECT * FROM " . $gTables[$table] . " LIMIT 1");
   $field_meta = gaz_dbi_get_fields_meta($field_results);
   for ($j = 0; $j < $field_meta['num']; $j++) {
      switch ($field_meta['data'][$j]->type) {
         // i numerici
         case 1:
         case 2:
         case 3:
         case 4:
         case 5:
         case 8:
         case 9:
         case 16:
         case 246:
            $acc[$field_meta['data'][$j]->name] = 0;
            break;
         default:
            $acc[$field_meta['data'][$j]->name] = '';
            break;
      }
   }
   return $acc;
}

function gaz_dbi_parse_post($table) {
   /*
    * $table - il nome della tabella all'interno dell'array $gTables
    * questa funzione genera un array(chiave=>valore) contenente le sole chiavi
    * omonime presenti in $_POST e con i valori parsati in base al tipo di colonna
    */
   global $link, $gTables;
   $acc = array();
   $field_results = gaz_dbi_query("SELECT * FROM " . $gTables[$table] . " LIMIT 1");
   $field_meta = gaz_dbi_get_fields_meta($field_results);
   for ($j = 0; $j < $field_meta['num']; $j++) {
      $nomeCampo = $field_meta['data'][$j]->name;
      if (isset($_POST[$nomeCampo])) {
         switch ($field_meta['data'][$j]->type) {
            // i numerici
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 8:
            case 9:
            case 16:
            case 246:
               $acc[$field_meta['data'][$j]->name] = floatval(preg_replace("/\,/", '.', $_POST[$field_meta['data'][$j]->name]));
               break;
            case 7:
            case 10:
            case 11:
            case 12:
            case 13: // campi datetimestamp
               $acc[$field_meta['data'][$j]->name] = substr($_POST[$field_meta['data'][$j]->name], 0, 20);
               break;
            // i binari non li considero
            case 252:
               break;
            // gli altri eventualmente li tronco
            default:
               $acc[$field_meta['data'][$j]->name] = substr($_POST[$field_meta['data'][$j]->name], 0, $field_meta['data'][$j]->length);
               break;
         }
      }
   }
   return $acc;
}

function gaz_dbi_table_insert($table, $value) {
   /*
    * $table - il nome della tabella all'interno dell'array $gTables
    * $value - array associativo del tipo nome_colonna=>valore con i valori da inserire
    */
   global $link, $gTables;
   $first = true;
   $auto_increment = false;
   $colName = '';
   $colValue = '';
   $field_results = gaz_dbi_query("SELECT * FROM " . $gTables[$table] . " LIMIT 1");
   $rs_auto_increment = gaz_dbi_query("SHOW COLUMNS FROM " . $gTables[$table]);
   while ($ai = mysqli_fetch_assoc($rs_auto_increment)) {
      if ($ai['Extra'] == 'auto_increment') {
         $auto_increment = $ai['Field'];
      }
   }
   $field_meta = gaz_dbi_get_fields_meta($field_results);
   for ($j = 0; $j < $field_meta['num']; $j++) {
      if ($field_meta['data'][$j]->name != $auto_increment) {  // il campo auto increment non dev'essere passato
         $colName .= ($first ? '`' . $field_meta['data'][$j]->name . '`' : ', `' . $field_meta['data'][$j]->name . '`');
         $colValue .= ($first ? " " : ", ");
         $first = false;
         if (isset($value[$field_meta['data'][$j]->name])) {
            if ($field_meta['data'][$j]->blob && !empty($value[$field_meta['data'][$j]->name])) {
               $colValue .= '0x' . bin2hex($value[$field_meta['data'][$j]->name]);
            } elseif ($field_meta['data'][$j]->numeric) { // NUMERICO
               $colValue .= floatval($value[$field_meta['data'][$j]->name]);
            } elseif ($field_meta['data'][$j]->datetimestamp) { // date datetime o timestamp
               if (empty($value[$field_meta['data'][$j]->name])) {
                  $colValue .= "NULL";
               } else {
                  $colValue .= "'" . $value[$field_meta['data'][$j]->name] . "'";
               }
            } else {
               $elem = addslashes($value[$field_meta['data'][$j]->name]); // risolve il classico problema dei caratteri speciali per inserimenti in SQL
               $elem = preg_replace("/\\\'/", "''", $elem); //cambia lo backslash+singlequote con 2 singlequote come fa phpmyadmin.
               $colValue .= "'" . $elem . "'";
            }
         } elseif ($field_meta['data'][$j]->name == 'adminid') { //l'adminid non lo si deve passare
            $colValue .= "'" . $_SESSION["user_name"] . "'";
         } elseif ($field_meta['data'][$j]->name == 'last_modified') {
            $colValue .= "'" . date("Y-m-d H:i:s") . "'";
         } else {
            if ($field_meta['data'][$j]->numeric) {
               $colValue .= 0;
            } elseif ($field_meta['data'][$j]->datetimestamp) { // date datetime o timestamp
               $colValue .= "NULL";
            } else {
               $colValue .= "''";
            }
         }
      }
   }
   $query = "INSERT INTO " . $gTables[$table] . " ( " . $colName . " ) VALUES ( " . $colValue . ");";
   $result = gaz_dbi_query($query);
   if (!$result) {
	  gaz_die ( $query, "532", __FUNCTION__ );
   } else {
	  return mysqli_insert_id($link);
   };
}

function gaz_dbi_table_update($table, $id, $newValue) {
   /*
    * $table - il nome della tabella all'interno dell'array $gTables
    * $id - stringa con il valore del campo "codice" da aggiornare o array(0=>nome,1=>valore,2=>nuovo_valore)
    * $newValue - array associativo del tipo nome_colonna=>valore con i valori da inserire
    */
   global $gTables;
   $field_results = gaz_dbi_query("SELECT * FROM " . $gTables[$table] . " LIMIT 1");
   $field_meta = gaz_dbi_get_fields_meta($field_results);
   $query = "UPDATE " . $gTables[$table] . ' SET ';
   $first = true;
   $quote_id = "'";
   for ($j = 0; $j < $field_meta['num']; $j++) {
      if (isset($newValue[$field_meta['data'][$j]->name])) {
         $query .= ($first ? '`' . $field_meta['data'][$j]->name . '`' . " = " : ", " . '`' . $field_meta['data'][$j]->name . '`' . " = ");
         $first = false;
         if ($field_meta['data'][$j]->blob && !empty($newValue[$field_meta['data'][$j]->name])) {
            $query .= '0x' . bin2hex($newValue[$field_meta['data'][$j]->name]);
         } elseif ($field_meta['data'][$j]->numeric && $field_meta['data'][$j]->type != 'timestamp') {
            $query .= floatval($newValue[$field_meta['data'][$j]->name]);
         } else {
            $elem = addslashes($newValue[$field_meta['data'][$j]->name]); // risolve il classico problema dei caratteri speciali per inserimenti in SQL
            $elem = preg_replace("/\\\'/", "''", $elem); //cambia lo backslash+singlequote con 2 singlequote come fa phpmyadmin.
            $query .= "'" . $elem . "'";
         }
         //per superare lo STRICT_MODE del server non metto gli apici ai numerici
         if ((is_array($id) && $field_meta['data'][$j]->name == $id[0] && $field_meta['data'][$j]->numeric) || (is_string($id) && $field_meta['data'][$j]->name == 'codice' && $field_meta['data'][$j]->numeric)) {
            $quote_id = '';
         }
      } elseif ($field_meta['data'][$j]->name == 'adminid') { //l'adminid non lo si deve passare
         $query .= ", adminid = '" . $_SESSION["user_name"] . "'";
      }
   }
   //   se in $id c'è un array uso il nome del campo presente all'index [0] ed il valore dell'index [1],
   //   eventualmente anche l'index [2] per il nuovo valore del codice che quindi verrà modificato
   if (is_array($id)) {
      if (isset($id[2])) {
         $query .= ", $id[0] = $quote_id$id[2]$quote_id";
      }
      $query .= " WHERE $id[0] = $quote_id$id[1]$quote_id";
   } else { //altrimenti uso "codice"
      $query .= " WHERE codice = $quote_id$id$quote_id";
   }
   //msgDebug($query);
   $result = gaz_dbi_query($query);
   if (!$result) gaz_die ( $query, "580", __FUNCTION__ );
}

// funzione gaz_aes_value_anagra
function gaz_aes_value_anagra($field, $value) {
   $aes_value = '';
   $field_name = $field;
   $field_name_point = strrpos($field_name, '.');
   if ($field_name_point !== false) {
      $field_name = substr($field_name, $field_name_point + 1, strlen($field_name) - $field_name_point);
   }
   switch ($field_name) {
      case 'ragso1':
      case 'ragso2':
      case 'sedleg':
      case 'legrap_pf_nome':
      case 'legrap_pf_cognome':
      case 'indspe':
      case 'latitude':
      case 'longitude':
      case 'telefo':
      case 'fax':
      case 'cell':
      case 'codfis':
      case 'pariva':
      case 'e_mail':
      case 'pec_email':
         //$aes_value.= "HEX(AES_ENCRYPT(" . $value . ", '" . $_SESSION['aes_key'] . "'))";
         /**/$aes_value .= '';
         break;
      default:
         //$aes_value .= $value;
         break;
   }
   return $aes_value;
}

function gaz_dbi_insert_anagra($value) {
   /*
    * $value - array associativo del tipo nome_colonna=>valore con i valori da inserire
    */
   global $link, $gTables;
   $first = true;
   $auto_increment = false;
   $colName = '';
   $colValue = '';
   $field_results = gaz_dbi_query("SELECT * FROM " . $gTables['anagra'] . " LIMIT 1");
   $rs_auto_increment = gaz_dbi_query("SHOW COLUMNS FROM " . $gTables['anagra']);
   while ($ai = mysqli_fetch_assoc($rs_auto_increment)) {
      if ($ai['Extra'] == 'auto_increment') {
         $auto_increment = $ai['Field'];
      }
   }
   $field_meta = gaz_dbi_get_fields_meta($field_results);
   for ($j = 0; $j < $field_meta['num']; $j++) {
      if ($field_meta['data'][$j]->name != $auto_increment && substr($field_meta['data'][$j]->name, -4) != '_aes') {  // il campo auto increment non dev'essere passato
         if (isset($value[$field_meta['data'][$j]->name])) {
            if ($field_meta['data'][$j]->blob && !empty($value[$field_meta['data'][$j]->name])) {
               $fieldValue = '0x' . bin2hex($value[$field_meta['data'][$j]->name]);
            } elseif ($field_meta['data'][$j]->numeric) { // NUMERICO
               $fieldValue = floatval($value[$field_meta['data'][$j]->name]);
            } elseif ($field_meta['data'][$j]->datetimestamp) { // date datetime o timestamp
               if (empty($value[$field_meta['data'][$j]->name])) {
                  $fieldValue = "NULL";
               } else {
                  $fieldValue = "'" . $value[$field_meta['data'][$j]->name] . "'";
               }
            } else {
               $elem = addslashes($value[$field_meta['data'][$j]->name]); // risolve il classico problema dei caratteri speciali per inserimenti in SQL
               $elem = preg_replace("/\\\'/", "''", $elem); //cambia lo backslash+singlequote con 2 singlequote come fa phpmyadmin.
               $fieldValue = "'" . $elem . "'";
            }
         } elseif ($field_meta['data'][$j]->name == 'adminid') { //l'adminid non lo si deve passare
            $fieldValue = "'" . $_SESSION["user_name"] . "'";
         } elseif ($field_meta['data'][$j]->name == 'last_modified') {
            $fieldValue = "'" . date("Y-m-d H:i:s") . "'";
         } else {
            if ($field_meta['data'][$j]->numeric) {
               $fieldValue = 0;
            } elseif ($field_meta['data'][$j]->datetimestamp) { // date datetime o timestamp
               $fieldValue = "NULL";
            } else {
               $fieldValue = "''";
            }
         }
         $colName .= ($first ? '`' . $field_meta['data'][$j]->name . '`' : ', `' . $field_meta['data'][$j]->name . '`');
         $colValue .= ($first ? " " : ", ");
         $first = false;
         $colValue .= $fieldValue;
         $aes_value = gaz_aes_value_anagra($field_meta['data'][$j]->name, $fieldValue);
         if (!empty($aes_value)) {
            $colName .= ($first ? '`' . $field_meta['data'][$j]->name . "_aes" . '`' : ", " . '`' . $field_meta['data'][$j]->name . "_aes" . '`');
            $colValue .= ($first ? " " : ", ");
            if (!empty($fieldValue) && $fieldValue != 'NULL' && $fieldValue != '0') {
               $colValue .= $aes_value;
            } else {
               $colValue .= $fieldValue;
            }
         }
      }
   }
   $query = "INSERT INTO " . $gTables['anagra'] . " ( " . $colName . " ) VALUES ( " . $colValue . ");";
   $result = gaz_dbi_query($query);
   if (!$result) gaz_die ( $query, "683", __FUNCTION__ );
}

function gaz_dbi_update_anagra($id, $newValue) {
   /*
    * $id - stringa con il valore del campo "codice" da aggiornare o array(0=>nome,1=>valore,2=>nuovo_valore)
    * $newValue - array associativo del tipo nome_colonna=>valore con i valori da inserire
    */
   global $link, $gTables;
   $field_results = gaz_dbi_query("SELECT * FROM " . $gTables['anagra'] . " LIMIT 1");
   $field_meta = gaz_dbi_get_fields_meta($field_results);
   $query = "UPDATE " . $gTables['anagra'] . ' SET ';
   $first = true;
   $quote_id = "'";
   for ($j = 0; $j < $field_meta['num']; $j++) {
      if (isset($newValue[$field_meta['data'][$j]->name]) && substr($field_meta['data'][$j]->name, -4) != '_aes') {
         if ($field_meta['data'][$j]->blob && !empty($newValue[$field_meta['data'][$j]->name])) {
            $fieldValue = '0x' . bin2hex($newValue[$field_meta['data'][$j]->name]);
         } elseif ($field_meta['data'][$j]->numeric && $field_meta['data'][$j]->type != 'timestamp') {
            $fieldValue = floatval($newValue[$field_meta['data'][$j]->name]);
         } else {
            $elem = addslashes($newValue[$field_meta['data'][$j]->name]); // risolve il classico problema dei caratteri speciali per inserimenti in SQL
            $elem = preg_replace("/\\\'/", "''", $elem); //cambia lo backslash+singlequote con 2 singlequote come fa phpmyadmin.
            $fieldValue = "'" . $elem . "'";
         }
         //per superare lo STRICT_MODE del server non metto gli apici ai numerici
         if ((is_array($id) && $field_meta['data'][$j]->name == $id[0] && $field_meta['data'][$j]->numeric) || (is_string($id) && $field_meta['data'][$j]->name == 'codice' && $field_meta['data'][$j]->numeric)) {
            $quote_id = '';
         }
         $query .= ($first ? '`' . $field_meta['data'][$j]->name . '`' . " = " : ", " . '`' . $field_meta['data'][$j]->name . '`' . " = ");
         $first = false;
         $query .= $fieldValue;
         $aes_value = gaz_aes_value_anagra($field_meta['data'][$j]->name, $fieldValue);
         if (!empty($aes_value)) {
            $query .= ($first ? '`' . $field_meta['data'][$j]->name . "_aes" . '`' . " = " : ", " . '`' . $field_meta['data'][$j]->name . "_aes" . '`' . " = ");
            if (!empty($fieldValue) && $fieldValue != "''" && $fieldValue != 'NULL') {
               $query .= $aes_value;
            } else {
               $query .= $fieldValue;
            }
         }
      } elseif ($field_meta['data'][$j]->name == 'adminid') { //l'adminid non lo si deve passare
         $query .= ", adminid = '" . $_SESSION['user_name'] . "'";
      }
   }
   //   se in $id c'è un array uso il nome del campo presente all'index [0] ed il valore dell'index [1],
   //   eventualmente anche l'index [2] per il nuovo valore del codice che quindi verrà modificato
   if (is_array($id)) {
      if (isset($id[2])) {
         $query .= ", $id[0] = $quote_id$id[2]$quote_id";
      }
      $query .= " WHERE $id[0] = $quote_id$id[1]$quote_id";
   } else { //altrimenti uso "codice"
      $query .= " WHERE codice = $quote_id$id$quote_id";
   }
   //msgDebug($query);
   $result = gaz_dbi_query($query);
   if (!$result) gaz_die ( $query, "738", __FUNCTION__ );
}

function tableInsert($table, $columns, $newValue) {
   global $link, $gTables;
   $first = True;
   $colName = "";
   $colValue = "";
   foreach ($columns AS $key => $field) {
      $colName .= ($first ? $field : ',' . $field);
      $colValue .= ($first ? " '" : ", '");
      $first = False;
      $colValue .= (isset($newValue[$field]) ? addslashes($newValue[$field]) : '') . "'";
   }
   $query = "INSERT INTO " . $gTables[$table] . " ( " . $colName . " ) VALUES ( " . $colValue . ")";
   $result = gaz_dbi_query($query);
   if (!$result) gaz_die ( $query, "754", __FUNCTION__ );
   return mysqli_insert_id($link);
}

function tableUpdate($table, $columns, $codice, $newValue) {
   global $link, $gTables;
   $first = True;
   $query = "UPDATE " . $gTables[$table] . ' SET';
   foreach ($columns AS $key => $field) {
	 if (isset($newValue[$field])){ // la colonna la aggiorno solo se passo un nuovo valore
	  $query .= ($first ? " $field = '" : ", $field = '");
      $first = False;
      $query .= addslashes($newValue[$field])."'";
	 }
   }
   //   se in $codice c'è un array uso il nome del campo presente all'index [0],
   //   eventualmente anche l'index [2] per il nuovo valore del codice che quindi verrà modificato
   if (is_array($codice)) {
      if (isset($codice[2])) {
         $query .= ", $codice[0] = '$codice[2]'";
      }
      $query .= " WHERE $codice[0] = '$codice[1]'";
   } else { //altrimenti uso "codice"
      $query .= " WHERE codice = '$codice'";
   }
   //echo $query;
   //msgDebug($query);
   $result = gaz_dbi_query($query);
   if (!$result) gaz_die ( $query, "782", __FUNCTION__ );
}

function mergeTable($table1, $campi1, $table2, $campi2, $campomerge, $where) {
   $result = gaz_dbi_query("SELECT $campi1 FROM $table1 LEFT JOIN $table2 ON $table1.$campomerge = $table2.$campomerge WHERE $where");
   if (!$result) gaz_die ( $query, "788", __FUNCTION__ );
   return $result;
}

function rigmoiInsert($newValue) {
   $table = 'rigmoi';
   $columns = array('id_rig', 'id_tes', 'tipiva', 'reverse_charge_idtes', 'operation_type', 'codiva', 'periva', 'imponi', 'impost');
   $last_id = tableInsert($table, $columns, $newValue);
   return $last_id;
}

function rigmocInsert($newValue) {
   $table = 'rigmoc';
   $columns = array('id_tes', 'darave', 'codcon', 'import', 'id_orderman');
   $last_id = tableInsert($table, $columns, $newValue);
   return $last_id;
}

function paymovInsert($newValue) {
   $table = 'paymov';
   $columns = array('id_tesdoc_ref', 'id_rigmoc_pay', 'id_rigmoc_doc', 'amount', 'expiry');
   tableInsert($table, $columns, $newValue);
}

function paymovUpdate($id, $newValue) {
   $table = 'paymov';
   $columns = array('id', 'id_tesdoc_ref', 'id_rigmoc_pay', 'id_rigmoc_doc', 'amount', 'expiry');
   tableUpdate($table, $columns, $id, $newValue);
}

function rigbroInsert($newValue) {
   $table = 'rigbro';
   $columns = array('id_tes', 'tiprig', 'nrow', 'nrow_linked', 'codart', 'codice_fornitore', 'descri', 'quality','id_body_text', 'unimis', 'lunghezza', 'larghezza', 'spessore', 'peso_specifico', 'pezzi', 'quanti', 'prelis', 'sconto', 'codvat', 'pervat', 'codric', 'provvigione', 'ritenuta', 'delivery_date', 'id_doc', 'id_mag', 'id_rigmoc', 'id_orderman', 'status');
   $last_id=tableInsert($table, $columns, $newValue);
   return $last_id;
}

function rigbroUpdate($codice, $newValue) {
   $table = 'rigbro';
   $columns = array('id_tes', 'tiprig', 'nrow', 'nrow_linked', 'codart', 'codice_fornitore', 'descri', 'quality', 'id_body_text', 'unimis', 'lunghezza', 'larghezza', 'spessore', 'peso_specifico', 'pezzi', 'quanti','prelis', 'sconto', 'codvat', 'pervat', 'codric', 'provvigione', 'ritenuta', 'delivery_date', 'id_doc', 'id_mag', 'id_rigmoc', 'id_orderman', 'status');
   tableUpdate($table, $columns, $codice, $newValue);
}

function rigcmrInsert($newValue) {
   $table = 'rigcmr';
   $columns = array('id_tes', 'tiprig', 'codart', 'codice_fornitore', 'descri', 'id_body_text', 'unimis', 'quanti', 'prelis', 'sconto', 'codvat', 'pervat', 'codric', 'provvigione', 'ritenuta', 'delivery_date', 'id_doc', 'id_mag', 'status');
   tableInsert($table, $columns, $newValue);
}

function rigcmrUpdate($codice, $newValue) {
   $table = 'rigcmr';
   $columns = array('id_tes', 'tiprig', 'codart', 'codice_fornitore', 'descri', 'id_body_text', 'unimis', 'quanti','prelis', 'sconto', 'codvat', 'pervat', 'codric', 'provvigione', 'ritenuta', 'delivery_date', 'id_doc', 'id_mag', 'status');
   tableUpdate($table, $columns, $codice, $newValue);
}

function rigdocInsert($newValue) {
   $table = 'rigdoc';
   $columns = array('id_tes', 'tiprig', 'nrow', 'nrow_linked', 'codart', 'codice_fornitore', 'descri', 'id_body_text', 'unimis', 'quanti', 'prelis', 'sconto', 'codvat', 'pervat', 'codric', 'provvigione', 'peso_specifico', 'ritenuta', 'id_order', 'id_mag', 'id_orderman', 'status');
   $last_id = tableInsert($table, $columns, $newValue);
   return $last_id;
}

function rigdocUpdate($codice, $newValue) {
   $table = 'rigdoc';
   $columns = array('id_tes', 'tiprig', 'nrow', 'nrow_linked', 'codart', 'codice_fornitore', 'descri', 'id_body_text', 'unimis', 'quanti', 'prelis', 'sconto', 'codvat', 'pervat', 'codric', 'provvigione', 'peso_specifico', 'ritenuta', 'id_order', 'id_mag', 'id_orderman', 'status');
   tableUpdate($table, $columns, $codice, $newValue);
}

function tesbroInsert($newValue) {
   $table = 'tesbro';
   $columns = array('seziva', 'tipdoc', 'tipdoc_buf', 'ref_ecommerce_id_order', 'template', 'email', 'print_total', 'delivery_time', 'day_of_validity', 'datemi', 'protoc', 'numdoc', 'numfat', 'datfat', 'clfoco', 'pagame', 'banapp', 'vettor', 'weekday_repeat', 'listin', 'destin', 'id_des', 'id_des_same_company', 'spediz', 'portos', 'imball', 'traspo', 'speban', 'spevar', 'round_stamp', 'cauven', 'caucon', 'caumag', 'id_agente', 'id_parent_doc', 'sconto', 'expense_vat', 'stamp', 'net_weight', 'gross_weight', 'taxstamp', 'virtual_taxstamp', 'units', 'volume', 'initra', 'geneff', 'id_contract', 'id_con', 'id_orderman', 'status', 'custom_field', 'adminid');
   $newValue['adminid'] = $_SESSION["user_name"];
   $last_id=tableInsert($table, $columns, $newValue);
   return $last_id;
}

function tesbroUpdate($codice, $newValue) {
   $table = 'tesbro';
   $columns = array('seziva', 'tipdoc', 'tipdoc_buf', 'ref_ecommerce_id_order', 'template', 'email', 'print_total', 'delivery_time', 'day_of_validity', 'datemi', 'protoc', 'numdoc', 'numfat', 'datfat', 'clfoco', 'pagame', 'banapp', 'vettor', 'weekday_repeat', 'listin', 'destin', 'id_des', 'id_des_same_company', 'spediz', 'portos', 'imball', 'traspo', 'speban', 'spevar', 'round_stamp', 'cauven', 'caucon', 'caumag', 'id_agente', 'id_parent_doc', 'sconto', 'expense_vat', 'stamp', 'net_weight', 'gross_weight', 'taxstamp', 'virtual_taxstamp', 'units', 'volume', 'initra', 'geneff', 'id_contract', 'id_con', 'id_orderman', 'status', 'adminid', 'custom_field');
   $newValue['adminid'] = $_SESSION["user_name"];
   tableUpdate($table, $columns, $codice, $newValue);
}

function tesdocInsert($newValue) {
  $table = 'tesdoc';
  $columns = array('seziva', 'tipdoc', 'tipdoc_buf', 'ddt_type', 'id_doc_ritorno', 'template', 'datemi', 'protoc', 'numdoc', 'numfat', 'datfat', 'clfoco', 'pagame', 'banapp', 'vettor', 'listin',
      'destin', 'id_des', 'id_des_same_company', 'spediz', 'portos', 'imball', 'traspo', 'speban', 'spevar', 'round_stamp', 'cauven', 'caucon', 'caumag',
      'id_agente', 'id_parent_doc', 'sconto', 'expense_vat', 'stamp', 'net_weight', 'gross_weight', 'units', 'volume', 'initra', 'geneff',
      'taxstamp', 'virtual_taxstamp', 'id_contract', 'id_con', 'datreg', 'fattura_elettronica_original_name', 'status', 'adminid', 'ragbol', 'data_ordine'
  );
  $newValue['adminid'] = $_SESSION["user_name"];
  $last_id=tableInsert($table, $columns, $newValue);
	return $last_id;
}

function tesdocUpdate($codice, $newValue) {
   $table = 'tesdoc';
   $columns = array('seziva', 'tipdoc', 'tipdoc_buf', 'ddt_type', 'id_doc_ritorno', 'template', 'datemi', 'protoc', 'numdoc', 'numfat', 'datfat', 'clfoco', 'pagame', 'banapp', 'vettor', 'listin',
       'destin', 'id_des', 'id_des_same_company', 'spediz', 'portos', 'imball', 'traspo', 'speban', 'spevar', 'round_stamp', 'cauven', 'caucon', 'caumag',
       'id_agente', 'id_parent_doc', 'sconto', 'expense_vat', 'stamp', 'net_weight', 'gross_weight', 'units', 'volume', 'initra', 'geneff',
       'taxstamp', 'virtual_taxstamp', 'id_contract', 'id_con', 'datreg', 'fattura_elettronica_original_name', 'status', 'adminid', 'ragbol', 'data_ordine'
   );
   $newValue['adminid'] = $_SESSION["user_name"];
   tableUpdate($table, $columns, $codice, $newValue);
}

function tesmovUpdate($codice, $newValue) {
   $table = 'tesmov';
   $columns = array('caucon', 'caucon_buf', 'descri', 'notess', 'datreg', 'datliq', 'seziva', 'id_doc', 'protoc', 'numdoc', 'datdoc', 'clfoco', 'regiva', 'operat', 'libgio', 'adminid');
   $newValue['adminid'] = $_SESSION["user_name"];
   tableUpdate($table, $columns, $codice, $newValue);
}

function tesmovInsert($newValue) {
   $table = 'tesmov';
   $columns = array('caucon', 'caucon_buf', 'descri', 'notess', 'datreg', 'datliq', 'seziva', 'id_doc', 'protoc', 'numdoc', 'datdoc', 'clfoco', 'regiva', 'operat', 'libgio', 'adminid');
   $newValue['adminid'] = $_SESSION["user_name"];
   $last_id = tableInsert($table, $columns, $newValue);
   return $last_id;
}

function movmagInsert($newValue) {
  $table = 'movmag';
  $columns = array('caumag','operat','datreg','tipdoc','desdoc','datdoc','clfoco','scochi','id_rif','artico','id_warehouse','id_artico_position','id_lotmag','id_orderman','id_assets','quanti','prezzo','scorig','campo_impianto','custom_field','status','adminid');
  $newValue['adminid'] = $_SESSION["user_name"];
  $last_id=tableInsert($table, $columns, $newValue);
	// aggiorno l'e-commerce ove presente
	if (!empty($newValue['synccommerce_classname']) && class_exists($newValue['synccommerce_classname'])){
        $gs=$newValue['synccommerce_classname'];
		$gSync = new $gs();
		if($gSync->api_token && isset($newValue['artico'])){
			$gSync->SetProductQuantity($newValue['artico']);
		}
		//print $gSync->rawres;
		//exit;
	}
	return $last_id;
}

function movmagUpdate($codice, $newValue) {
  $table = 'movmag';
  $columns = array('caumag','operat','datreg','tipdoc','desdoc','datdoc','clfoco','scochi','id_rif','artico','id_warehouse','id_lotmag','id_orderman','id_assets','quanti','prezzo','scorig','campo_impianto','custom_field','status','adminid');
  $newValue['adminid'] = $_SESSION["user_name"];
  tableUpdate($table, $columns, $codice, $newValue);
	// aggiorno l'e-commerce ove presente
	if (!empty($newValue['synccommerce_classname']) && class_exists($newValue['synccommerce_classname'])){
        $gs=$newValue['synccommerce_classname'];
		$gSync = new $gs();
		if($gSync->api_token && isset($newValue['artico'])){
			$gSync->SetProductQuantity($newValue['artico']);
		}
		//print $gSync->rawres;
		//exit;
	}
	return;
}

function bodytextInsert ($newValue)
{
    $table = 'body_text';
    $columns = array('table_name_ref','id_ref','code_ref','body_text','descri','lang_id');
    tableInsert($table, $columns, $newValue);
}

function bodytextUpdate ($codice, $newValue)
{
    $table = 'body_text';
    $columns = array('table_name_ref','id_ref','code_ref','body_text','descri','lang_id');
    tableUpdate($table, $columns, $codice, $newValue);
}

//===============================================================
// Gestione Access Rights
//===============================================================
function updateAccessRights($adminid, $moduleid, $access, $company_id = 1) {
   global $gTables;

   $result = gaz_dbi_query("SELECT * FROM " . $gTables['admin_module'] . " WHERE adminid='" . $adminid . "' AND moduleid=" . $moduleid . ' AND company_id=' . $company_id);
   if (gaz_dbi_num_rows($result) < 1) {
      $query = "INSERT INTO " . $gTables['admin_module'] .
              " (adminid, company_id, moduleid, access) VALUES ('" . $adminid . "',$company_id,$moduleid,$access)";
   } else {
      $query = "UPDATE " . $gTables['admin_module'] .
              " SET access=" . $access .
              " WHERE adminid='" . $adminid . "' AND moduleid=" . $moduleid . ' AND company_id=' . $company_id;
   }
   $result = gaz_dbi_query($query) or gaz_die ( $query, "959", __FUNCTION__ );
}

function getAccessRights($userid = '', $company_id = 1) {
  global $gTables;
  $query_co = " AND am.company_id='" . $company_id . "'";
  $ck_co = gaz_dbi_fields('admin_module');
  if (!array_key_exists('company_id', $ck_co)) {
     $query_co = '';
  };
  if ($userid == '') {
    $query = 'SELECT module.name,
		module.link,
		module.id AS m1_id,
		module.access,
		module.weight
    FROM  ' . $gTables['module'] . ' AS module
		ORDER BY weight';
  } else {
    $query = 'SELECT am.adminid,
	 	am.access, am.custom_field,
		m1.id AS m1_id,
		m1.name,
		m1.link,
		m1.icon,
    m1.access as m1_ackey,
		m1.class,
		m1.weight,
		m2.id AS m2_id,
		m2.link AS m2_link,
		m2.icon AS m2_icon,
		m2.class AS m2_class,
		m2.translate_key AS m2_trkey,
		m2.accesskey AS m2_ackey,
		m2.weight AS m2_weight,
		m3.id AS m3_id,
		m3.link AS m3_link,
		m3.icon AS m3_icon,
		m3.class AS m3_class,
		m3.translate_key AS m3_trkey,
		m3.accesskey AS m3_ackey,
		m3.weight AS m3_weight
		FROM ' . $gTables['menu_module'] . '       AS m2
		LEFT JOIN ' . $gTables['module'] . '       AS m1 ON m1.id      = m2.id_module
		LEFT JOIN ' . $gTables['admin_module'] . ' AS am ON am.moduleid= m1.id
		LEFT JOIN ' . $gTables['menu_script'] . '  AS m3 ON m3.id_menu = m2.id
		WHERE am.adminid=\'' . $userid . '\' ' . $query_co . '
		ORDER BY m1.weight,
		m1_id,
		m2.weight,
		m2_id,
		m3.weight';
  }
  $result = gaz_dbi_query($query) or gaz_die ( $query, "1014", __FUNCTION__ );
  return $result;
}

function checkAccessRights($adminid, $module, $company_id = 0) {
  global $gTables;
  $ck_co = gaz_dbi_fields('admin_module');
  if ($company_id == 0 || (!array_key_exists('company_id', $ck_co))) {  // vengo da una vecchia versione (<4.0.12)
     $query = 'SELECT am.access FROM ' . $gTables['admin_module'] . ' AS am' .
             ' LEFT JOIN ' . $gTables['module'] . ' AS module ON module.id=am.moduleid' .
             " WHERE am.adminid='" . $adminid . "' AND module.name='" . $module . "'";
  } else {   //nuove versione >= 4.0.12
     $query = 'SELECT am.access, am.custom_field FROM ' . $gTables['admin_module'] . ' AS am' .
             ' LEFT JOIN ' . $gTables['module'] . ' AS module ON module.id=am.moduleid' .
             " WHERE am.adminid='" . $adminid . "' AND module.name='" . $module . "' AND am.company_id = $company_id ";
  }
  $result = gaz_dbi_query($query) or gaz_die ( $query, "1030", __FUNCTION__ );
  if (gaz_dbi_num_rows($result) < 1) {
     return 0;
  }
  $row = gaz_dbi_fetch_assoc($result);
  $chkes = is_string($row['custom_field'])? json_decode($row['custom_field']) : false;
  if ($chkes && isset($chkes->excluded_script)) {
    return $chkes->excluded_script;
  }
  return $row['access'];
}

function gaz_dbi_fetch_all($resource) {
   $result = array();
   while ($row = mysqli_fetch_assoc($resource)) {
      $result[] = $row;
   }
   return $result;
}

function gaz_die( $query, $riga, $funzione="" ) {
    global $debug_active,$link;
    $edie = "";
    if ( $debug_active ) {
        $edie .= "Query error ";
        if ( $riga!="" ) $edie .= "-r".$riga." ";
        if ( $funzione!="" ) $edie .= "funzione ".$funzione." ";
        $edie .= " : '".$query."' ". mysqli_error($link);
    } else {
        $edie = "Query error";
    }
    die ( $edie );
}
?>
