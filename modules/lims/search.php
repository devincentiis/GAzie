<?php
/*
  --------------------------------------------------------------------------
  Copyright (C) - Antonio De Vincentiis Montesilvano (PE)  (http://www.devincentiis.it)
  --------------------------------------------------------------------------
// prevent direct access
/*/
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}

// *****************************************************************************/
if (isset($_GET['term'])) { //	Evitiamo errori se lo script viene chiamato direttamente
  if (isset($_GET['opt'])) {
      $opt = $_GET['opt'];
  } else {
      $opt = 'instal';
  }
  require("../../library/include/datlib.inc.php");
  $admin_aziend = checkAdmin();
  $return_arr = array();
  $term = filter_var(substr($_GET['term'], 0, 20), FILTER_SANITIZE_ADD_SLASHES);
  $term = gaz_dbi_real_escape_string($term);
  $a_json_invalid = array(array("id" => "#", "value" => $term, "label" => "Alcuni caratteri impedisco la ricerca..."));
  $json_invalid = json_encode($a_json_invalid);
  // replace multiple spaces with one
  $term = preg_replace('/\s+/', ' ', $term);
  // SECURITY HOLE ***************************************************************
  // allow space, any unicode letter and digit, underscore, dash, slash, percent, dot,
  if (preg_match("/[^\040\pL\pN\&\%\@\#\/\.,_-]/u", $term)) {
      print $json_invalid;
      exit;
  }
	$tl=strlen($term);

	if($tl<2) {
    return;
  }

  $parts = array(0 => $term);

  switch ($opt) {
      case 'orderman':
          $fields = array("id", "description"); //	Sono i campi sui quali effettuare la ricerca
          foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
              foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
                  $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
              }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
          }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
          $like = implode(" OR ", $like);    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
          $result = gaz_dbi_dyn_query("id, description AS label, id AS value", $gTables['orderman'], $like, "id");
          break;
      case 'staff':
          $fields = array("id_staff", "descri"); //	Sono i campi sui quali effettuare la ricerca
          foreach ($fields as $id1 => $field) {   //	preparo i diversi campi per il like, questo funziona meglio del concat
              foreach ($parts as $id => $part) {   //	(inteso come stringa sulla quale fare il like) perchè è più flessibile con i caratteri jolly
                  $like[] = like_prepare($field, $part); //	Altrimenti se si cerca za%, il like viene fatto su tutto il concat, e se il codice prodotto
              }           //	non inizia per za il risultato è nullo, così invece se cerco za%, viene fuori anche un prodotto il
          }            //  cui nome (o descrizione) inizia per za ma il cui codice può anche essere TPQ
          $like = implode(" OR ", $like);    //	creo la porzione di query per il like, con OR perchè cerco in campi differenti
          $result = gaz_dbi_dyn_query("id_staff AS id, descri AS label, id_staff AS value", $gTables['staff']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['staff'].".id_clfoco=".$gTables['clfoco'].".codice", $like, "id");
          break;
  }
  while ($row = gaz_dbi_fetch_assoc($result)) {
      $return_arr[] = $row;
  }
  if ($term != '%%') { //	E' indispensabile, altrimenti si possono generare warning che non fanno funzionare l'autocompletamento
      $return_arr = apply_highlight($return_arr, str_replace("%", '', $parts));
  }
  echo json_encode($return_arr);
} else {
  return;
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
  http://www.pontikis.net/blog/jquery-ui-autocomplete-step-by-step
 */

/**
 * mb_stripos all occurences
 * based on http://www.php.net/manual/en/function.strpos.php#87061
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

