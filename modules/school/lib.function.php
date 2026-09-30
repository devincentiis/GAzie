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

class schoolForm extends GAzieForm {

    function selectTeacher($val, $class = 'FacetSelect') {
        global $gTables;
        $query = "SELECT * FROM " . $gTables['admin'] . " WHERE Abilit > 8";
        echo "\t <select name=\"teacher\" class=\"$class\" >\n";
        echo "\t\t <option value=\"\">---------</option>\n";
        $rs = gaz_dbi_query($query);
        while ($r = gaz_dbi_fetch_array($rs)) {
            $selected = '';
            if ($r["user_name"] == $val) {
                $selected = "selected";
            }
            echo "\t\t <option value=\"" . $r["user_name"] . "\" $selected >" . $r['user_firstname'].' '.$r['user_lastname'] . "</option>\n";
        }
        echo "\t </select>\n";
    }
}

?>