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

class ordermanForm extends GAzieForm {

	// Antonio Germani - Come select selectFromDB ma con in più preleva $key4 da $table2, dove $key3 è uguale a $key2, e lo visualizza nella scelta del select. Cioè nelle scelte del select ci sarà $key e $key4
	function selectFrom2DB($table,$table2,$key3,$key4, $name, $key, $val, $order = false, $empty = false, $bridge = '', $key2 = '', $val_hiddenReq = '', $class = 'FacetSelect', $addOption = null, $style = '', $where = false, $echo=false) {
        global $gTables;
		$acc='';
        $refresh = '';

        if (!$order) {
            $order = $key;
        }

        $query = 'SELECT * FROM `' . $gTables[$table] . '` ';
        if ($where) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' ORDER BY `' . $order . '`';
        if (!empty($val_hiddenReq)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$val_hiddenReq'; this.form.submit();\"";
        }
        $acc .= "\t <select id=\"$name\" name=\"$name\" class=\"$class\" $refresh $style>\n";
        if ($empty) {
            $acc .= "\t\t <option value=\"\"></option>\n";
        }

        $result = gaz_dbi_query($query);
        while ($r = gaz_dbi_fetch_array($result)) {
            $selected = '';
            if ($r[$key] == $val) {
                $selected = "selected";
            }

			$r2 = gaz_dbi_get_row($gTables[$table2], $key3, $r[$key2]);

            $acc .= "\t\t <option value=\"" . $r[$key] . "\" $selected >";
            if (empty($key2)) {
                $acc .= substr($r[$key], 0, 43) . "</option>\n";
            } else {
                $acc .= substr($r[$key], 0, 28) . $bridge . substr($r2[$key4], 0, 35) . "</option>\n";
            }
        }
        if ($addOption) {
            $acc .= "\t\t <option value=\"" . $addOption['value'] . "\"";
            if ($addOption['value'] == $val) {
                $acc .= " selected ";
            }
            $acc .= ">" . $addOption['descri'] . "</option>\n";
        }
        $acc .= "\t </select>\n";
		if ($echo){
			return $acc;
		} else {
			echo $acc;
		}
  }

  function selectWorker($val, $anno=false, $mese=false, $class = 'FacetSelect', $val_hiddenReq=false) {
      global $gTables;
      $anno=$anno?$anno:date("Y");
      $mese=$mese?$mese:date("m");
      $refresh = $val_hiddenReq?'onchange="this.form.submit();"':'';
      $query = "SELECT " . $gTables['staff'] . ".id_staff," . $gTables['clfoco'] . ".descri
                FROM " . $gTables['staff'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['staff'] . ".id_clfoco = " . $gTables['clfoco'] . ".codice WHERE DATE_FORMAT(start_date, '%Y%m') <=  ".$anno.str_pad($mese,2,"0",STR_PAD_LEFT)." AND (DATE_FORMAT(end_date, '%Y%m') >= ".$anno.str_pad($mese,2,"0",STR_PAD_LEFT)." OR end_date IS NULL OR end_date <= '2004-01-27') AND status <> 'HIDDEN'";
      echo '<select name="id_staff_def" class="'.$class.'" '.$refresh.' >';
      echo "\t\t <option value=\"0\">---------</option>\n";
      $rs = gaz_dbi_query($query);
      while ($r = gaz_dbi_fetch_array($rs)) {
          $selected = '';
          if ($r["id_staff"] == $val) {
              $selected = "selected";
          }
          echo "\t\t <option value=\"" . $r["id_staff"] . "\" $selected >" . $r['id_staff'].' - '.$r['descri'] . "</option>\n";
      }
      echo "\t </select>\n";
  }

}
?>
