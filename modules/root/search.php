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

// prevent direct access
*/
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}

if (isset($_GET['term'])) { //	Evitiamo errori se lo script viene chiamato direttamente
  if (isset($_GET['opt'])) {
      $opt = $_GET['opt'];
  } else {
      $opt = 'anagra';
  }
  require("../../library/include/datlib.inc.php");
  $admin_aziend = checkAdmin();
  $return_arr = array();
	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {
		$term=filter_var(substr($_GET['term'],0,30),FILTER_SANITIZE_ADD_SLASHES);
	} else {
		$term=addslashes(substr($_GET['term'],0,30));
	}
  $term = gaz_dbi_real_escape_string($term);
  $a_json_invalid = array(array("id" => "#", "value" => $term, "label" => "Alcuni caratteri impedisco la ricerca..."));
  $json_invalid = json_encode($a_json_invalid);
  // replace multiple spaces with one
  $term = preg_replace('/\s+/', ' ', $term);
  // allow space, any unicode letter and digit, underscore, dash, slash, percent, dot,
  if (preg_match("/[^\040\pL\pN\&\%\@\#\/\.,_-]/u", $term)) {
    print $json_invalid;
    exit;
  }
	$tl=strlen($term);
    if ($opt=='suggest_new_codart'){
		$tl++; // in caso di proposta codice articolo forzo la ricerca ad un carattere in meno
	}
	if($tl<2) {
    return;
  }
  $parts = array(0 => $term);
  // ENRICO FEDELE
  switch ($opt) {
    case 'product':
      $fields = array("codice", "descri", "barcode","codice_fornitore"); //	Sono i campi sui quali effettuare la ricerca
      foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
          foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
              $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
          }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
      }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
      $like = implode(" OR ", $like);    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
      $result = gaz_dbi_dyn_query("codice AS id, CONCAT(codice,' - ',descri,' - ',barcode,' - ',codice_fornitore) AS label, codice AS value, movimentabile", $gTables['artico'], $like, "CASE movimentabile WHEN 'N' THEN 2 WHEN 'E' THEN 1 WHEN 'S' THEN 0 END ASC , catmer ASC, codice ASC",0,500);
      break;
    case 'order':
      $fields = array("numdoc", "descri"); //	Sono i campi sui quali effettuare la ricerca
      foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
          foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
              $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
          }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
      }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
      $like = implode(" OR ", $like);    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
      $result = gaz_dbi_dyn_query("id_tes AS id, CONCAT('n.',numdoc,' del ',datemi,' - ',descri) AS label, id_tes AS value, 'S' AS movimentabile ",
              $gTables['tesbro']. " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesbro'] . ".clfoco = " . $gTables['clfoco'] . ".codice",
              "(".$like.") AND tipdoc='VOR'", // così prendo solo gli ordini da clienti
              "datemi DESC, numdoc DESC",0,500);
      break;
    case 'production':
      $fields = array("id", "description"); //	Sono i campi sui quali effettuare la ricerca
      foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
          foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
              $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
          }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
      }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
      $like = implode(" OR ", $like);    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
      $result = gaz_dbi_dyn_query("id, CONCAT(id,' - ',description) AS label, id AS value, 'S' AS movimentabile, description ",
              $gTables['orderman'], "(".$like.") AND stato_lavorazione < 9", // così prendo solo gli ordini da clienti
              "id DESC",0,500);
      break;
    case 'contract':
      $clfoco=(isset($_GET['clfoco']))?$_GET['clfoco']:'0';
      $fields = array("doc_number", "conclusion_date"); //	Sono i campi sui quali effettuare la ricerca
      foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
          foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
              $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
          }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
      }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
      $like1 = implode(" OR ", $like);    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
      $result = gaz_dbi_dyn_query("id_contract, CONCAT('N.',doc_number,' del ',DATE_FORMAT(conclusion_date,'%d-%m-%Y')) AS label, id_contract AS value, conclusion_date", $gTables['contract'], "(".$like1.") AND id_customer = ".$clfoco,"id_contract DESC",0,500);
      while ($row = gaz_dbi_fetch_assoc($result)) {
          $return_arr[] = $row;
      }
      $like=[];
      $fields = array("numdoc", "datemi"); //	Sono i campi sui quali effettuare la ricerca
      foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
          foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
              $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
          }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
      }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
      $like2 = implode(" OR ", $like);    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
      $result = gaz_dbi_dyn_query("id_tes AS id_contract, CONCAT('N.',numdoc,' del ',DATE_FORMAT(datemi,'%d-%m-%Y')) AS label, id_tes AS value, datemi AS conclusion_date", $gTables['tesbro'], "(".$like2.") AND tipdoc ='CON' AND clfoco=".$clfoco,"id_tes DESC",0,500);
      while ($row = gaz_dbi_fetch_assoc($result)) {
          $return_arr[] = $row;
      }
      if ($term != '%%') { //	E' indispensabile, altrimenti si possono generare warning che non fanno funzionare l'autocompletamento
        $return_arr = apply_highlight($return_arr, str_replace("%", '', $parts));
      }
      $return_arr = apply_evidenze($return_arr);
      echo json_encode($return_arr);
      exit;
      break;
    case 'quality':
      $fields = array("quality"); //	Sono i campi sui quali effettuare la ricerca
      foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
          foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
              $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
          }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
      }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
      $like = implode(" OR ", $like).' GROUP BY quality';    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
      $result = gaz_dbi_dyn_query("id_rig, quality AS label, quality AS value, 'S' AS movimentabile ", $gTables['rigbro'], $like, "id_rig",0,500);
      break;
    case 'municipalities':
      $fields = array("name"); //	Sono i campi sui quali effettuare la ricerca
      foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
          foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
              $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
          }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
      }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
      $like = implode(" OR ", $like);    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
      $result = gaz_dbi_dyn_query("id, CONCAT(id,' - ',name) AS label, id AS value, 'S' AS movimentabile ", $gTables['municipalities'], $like, "name",0,500);
      break;
    case 'location':
      foreach ($parts as $id => $part) {
          $like[] = like_prepare($gTables['municipalities'] . ".name", $part);
      }
      $like = implode(" AND ", $like); //	creo la porzione di query per il like
      $result = gaz_dbi_dyn_query("UPPER(" . $gTables['municipalities'] . ".name) AS value,
        " . $gTables['municipalities'] . ".postal_code AS id,
        " . $gTables['provinces'] . ".abbreviation AS prospe,
        " . $gTables['country'] . ".name AS nation,
        " . $gTables['country'] . ".iso AS country, 'S' AS movimentabile,
        CONCAT(" . $gTables['municipalities'] . ".postal_code, ' ', " . $gTables['municipalities'] . ".name, ' (', " . $gTables['provinces'] . ".abbreviation, ') ', " . $gTables['regions'] . ".name, ' ', " . $gTables['country'] . ".name) AS label ",
        $gTables['municipalities'] . "
        LEFT JOIN " . $gTables['provinces'] . " ON
        " . $gTables['municipalities'] . ".id_province = " . $gTables['provinces'] . ".id
        LEFT JOIN " . $gTables['regions'] . " ON
        " . $gTables['provinces'] . ".id_region = " . $gTables['regions'] . ".id
        LEFT JOIN " . $gTables['country'] . " ON
        " . $gTables['regions'] . ".iso_country = " . $gTables['country'] . ".iso", $like, $gTables['municipalities'] . ".name ASC",0,500);
      break;
    case 'supplier':
      $fields = array("ragso1", "ragso2");    //	Sono i campi sui quali effettuare la ricerca
      foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
          foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
              $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
          }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
      }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
      $like = implode(" OR ", $like);    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
      $result = gaz_dbi_dyn_query("codice, CONCAT(ragso1,' ',ragso2) AS label, ragso1 AS value, 'S' AS movimentabile ", $gTables['clfoco']. " LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['clfoco'] . ".id_anagra = " . $gTables['anagra'] . ".id", '('.$like.') AND codice BETWEEN '.$admin_aziend['masfor'].'000001 AND '.$admin_aziend['masfor'].'999999', 'ragso1',0,500);
      break;
    case 'employee':
      $fields = array("ragso1", "ragso2");    //	Sono i campi sui quali effettuare la ricerca
      foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
          foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
              $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
          }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
      }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
      $like = implode(" OR ", $like);    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
      $result = gaz_dbi_dyn_query("id_staff AS id, CONCAT(ragso1,' ',ragso2) AS label, CONCAT(ragso1,' ',ragso2) AS value, 'S' AS movimentabile ", $gTables['staff'] . ' AS staff LEFT JOIN ' . $gTables['clfoco'] . ' AS worker ON staff.id_clfoco=worker.codice LEFT JOIN ' . $gTables['anagra'] . ' AS ana ON worker.id_anagra=ana.id ', $like, 'ragso1',0,500);
      break;
    case 'suggest_new_codart':
      $result = gaz_dbi_dyn_query("codice AS id, CONCAT('Ultimo:',codice) AS label, codice AS value, movimentabile", $gTables['artico'], "codice LIKE '".$term."%'", "codice DESC", 0,1);
      break;
    case 'suggest_descri_artico':
      $result = gaz_dbi_dyn_query("descri AS id, CONCAT(codice,' - ',descri) AS label, descri AS value, movimentabile", $gTables['artico'], "descri LIKE '%".$term."%'", "CASE movimentabile WHEN 'N' THEN 2 WHEN 'E' THEN 1 WHEN 'S' THEN 0 END ASC, codice ASC",0,500);
      break;
    case 'suggest_codice_artico':
      $result = gaz_dbi_dyn_query("codice AS id, CONCAT(codice,' - ',descri) AS label, codice AS value, movimentabile", $gTables['artico'], "codice LIKE '%".$term."%'", "CASE movimentabile WHEN 'N' THEN 2 WHEN 'E' THEN 1 WHEN 'S' THEN 0 END ASC, codice ASC",0,500);
      break;
    case 'position':
      $fields = array("id_position","position"); //	Sono i campi sui quali effettuare la ricerca
      foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
          foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
              $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
          }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
      }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
      $like = implode(" OR ", $like);    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
      $result = gaz_dbi_dyn_query("id_position AS id, CONCAT(position, ' > ', descri ) AS label, id_position AS value, 'S' AS movimentabile",
      $gTables['artico_position']."
        LEFT JOIN " . $gTables['shelves'] . " ON
        " . $gTables['artico_position'] . ".id_shelf = " . $gTables['shelves'] . ".id_shelf
        LEFT JOIN " . $gTables['warehouse'] . " ON
        " . $gTables['artico_position'] . ".id_warehouse = " . $gTables['warehouse'] . ".id", $like,"position",0,500);
      break;
    default:
      $fields = array("ragso1", "ragso2");    //	Sono i campi sui quali effettuare la ricerca
      foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
        foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
          $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
        }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
      }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
      $like = implode(" OR ", $like);    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
      $result = gaz_dbi_dyn_query("id, CONCAT(ragso1,' ',ragso2) AS label, ragso1 AS value, 'S' AS movimentabile ", $gTables['anagra'], $like, 'ragso1',0,500);
  }
  while ($row = gaz_dbi_fetch_assoc($result)) {
      $return_arr[] = $row;
  }
  if ($opt=='suggest_new_codart'){ // in caso di suggerimento codice
    (isset($return_arr[0]['value']))?$return_arr[0]['value']++:$return_arr[0]['value']=1;
    (isset($return_arr[0]['label']))?$return_arr[0]['label'].=' successivo: '.$return_arr[0]['value']:$return_arr[0]['label'] =' successivo: '.$return_arr[0]['value'];
	}
  if ($term != '%%') { //	E' indispensabile, altrimenti si possono generare warning che non fanno funzionare l'autocompletamento
    $return_arr = apply_highlight($return_arr, str_replace("%", '', $parts));
  }
	$return_arr = apply_evidenze($return_arr);
  echo json_encode($return_arr);
} else {
  return;
}

/**
 * Funzione per evidenziare gli articoli segnati in esaurimento
 * @param string $evidenza: array su cui mettere in evidenza gli articoli esauriti o non movimentabili
 *
 */

function apply_evidenze($evidenza)
{
	$rows = count($evidenza);

    for ($row = 0; $row < $rows; $row++) {
		if (isset($evidenza[$row]["movimentabile"])){
			switch ($evidenza[$row]["movimentabile"]) {
			case 'E':
				$evidenza[$row]["label"] = '<mark class="apply-evidenze-e">' . $evidenza[$row]["label"] . '</mark>';
			break;
			case 'N':
				$evidenza[$row]["label"] = '<mark class="apply-evidenze-n">' . $evidenza[$row]["label"] . '</mark>';
			break;
			}
		}
	}

	return $evidenza;
}

/** ENRICO FEDELE */

/**
 * prepara la porzione di like per la query
 * se l'utente non ha inserito
 *
 * @param string $dbfield: campo del db sul quale fare la like
 * @param string $txtsearch: testo da cercare nel campo
 * @return array or false
 */
function like_prepare($dbfield, $txtsearch) {
    if (mb_stripos($txtsearch, "%") === false) { //	L'utente non ha inserito il carattere jolly
        return $dbfield . " LIKE '%" . $txtsearch . "%'";
    } else { //	L'utente sta usanto il carattere jolly %, quindi non devo inserirlo nella query
        return $dbfield . " LIKE '" . $txtsearch . "'";
    }
}

/** ENRICO FEDELE */
/* Codice preso da
  https://www.pontikis.net/blog/jquery-ui-autocomplete-step-by-step
 */

/**
 * mb_stripos all occurences
 * based on https://www.php.net/manual/en/function.strpos.php#87061
 *
 * Find all occurrences of a needle in a haystack
 *
 * @param string $haystack
 * @param string $needle
 * @return array or false
 */
function mb_stripos_all($haystack, $needle) {
    $s = 0;
    $i = 0;

    while (is_integer($i)) {
        $i = mb_stripos($haystack, $needle, $s);

        if (is_integer($i)) {
            $aStrPos[] = $i;
            $s = $i + mb_strlen($needle);
        }
    }

    if (isset($aStrPos)) {
        return $aStrPos;
    } else {
        return false;
    }
}

/**
 * Apply highlight to row label
 *
 * @param string $a_json json data
 * @param array $parts strings to search
 * @return array
 */
function apply_highlight($a_json, $parts) {
    $p = count($parts);
    $rows = count($a_json);

    for ($row = 0; $row < $rows; $row++) {
        $label = $a_json[$row]["label"];
        $a_label_match = array();

        for ($i = 0; $i < $p; $i++) {
            $part_len = mb_strlen($parts[$i]);
            $a_match_start = mb_stripos_all($label, $parts[$i]);
            if (!is_array($a_match_start))
                continue;

            foreach ($a_match_start as $part_pos) {
                $overlap = false;
                foreach ($a_label_match as $pos => $len) {
                    if ($part_pos - $pos >= 0 && $part_pos - $pos < $len) {
                        $overlap = true;
                        break;
                    }
                }
                if (!$overlap) {
                    $a_label_match[$part_pos] = $part_len;
                }
            }
        }
        if (count($a_label_match) > 0) {
            ksort($a_label_match);

            $label_highlight = '';
            $start = 0;
            $label_len = mb_strlen($label);

            foreach ($a_label_match as $pos => $len) {
                if ($pos - $start > 0) {
                    $no_highlight = mb_substr($label, $start, $pos - $start);
                    $label_highlight .= $no_highlight;
                }
                $highlight = '<mark>' . mb_substr($label, $pos, $len) . '</mark>';
                $label_highlight .= $highlight;
                $start = $pos + $len;
            }
            if ($label_len - $start > 0) {
                $no_highlight = mb_substr($label, $start);
                $label_highlight .= $no_highlight;
            }
            $a_json[$row]["label"] = $label_highlight;

        }
    }
    return $a_json;
}
?>

