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
class humresForm extends GAzieForm {

    function selectHextraType($name,$val,$opt=false) {
        global $gTables;
        $query = 'SELECT id_work,descri,increase FROM `' . $gTables['staff_work_type'] . '` ';
        $query .= 'WHERE id_work_type = 1 ORDER BY `id_work_type`';
        $ret0 = '<div';
        $ret1 =  '<select name="'.$name.'" class="col-sm-12 dropdownmenustyle">';
        $ret1 .= '<option value="0"></option>';
		if ($opt){
			foreach($opt as $k=>$v){
				$selected = '';	
				if ($k == $val) {
					$ret0 .= ' title="'.$v.'"';
					$selected = " selected";
					$v=substr($v,0,8);
				}
				$ret1 .= '<option value="' . $k . '"'. $selected.' >'.$v. "</option>\n";
			}
			$ret0 .= '>';
			echo $ret0.$ret1."\t </select>\n</div>\n";
			return $opt;
		} else {
			$retopt=array();
			$result = gaz_dbi_query($query);
			while ($r = gaz_dbi_fetch_array($result)) {
				$retopt[$r['id_work']]=$r['id_work'].'-'.$r['descri'].' '.$r['increase'];
				$selected = '';
				if ($r['id_work'] == $val) {
					$ret0 .= ' title="'.$r['descri'].'"';
					$selected = " selected";
					$r['descri']=substr($r['descri'],0,5);
				}
				$ret1 .= '<option value="' . $r['id_work'] . '"'. $selected.' >'.$r['id_work'].'-'.$r['descri'].' '.$r['increase']. "</option>\n";
			}
			$ret0 .= '>';
			echo $ret0.$ret1."\t </select>\n</div>\n";
			return $retopt;
		}
    }

    function selectAbsenceCau($name,$val,$opt=false) {
        global $gTables;
        $query = 'SELECT id_absence,causal,descri FROM `' . $gTables['staff_absence_type'] . '` ';
        $query .= 'WHERE 1';
        $ret0 = '<div';
        $ret1 =  '<select name="'.$name.'" class="col-sm-12 dropdownmenustyle">';
        $ret1 .= '<option value="0"></option>';
		if ($opt){
			foreach($opt as $k=>$v){
				$selected = '';	
				if ($k == $val) {
					$ret0 .= ' title="'.$v.'"';
					$selected = " selected";
					$v=substr($v,0,8);
				}
				$ret1 .= '<option value="' . $k . '"'. $selected.' >'.$v. "</option>\n";
			}
			$ret0 .= '>';
			echo $ret0.$ret1."\t </select>\n</div>\n";
			return $opt;
		} else {
			$retopt=array();
			$result = gaz_dbi_query($query);
			while ($r = gaz_dbi_fetch_array($result)) {
				$retopt[$r['id_absence']]=$r['causal'].'-'.$r['descri'];
				$selected = '';
				if ($r['id_absence'] == $val) {
					$ret0 .= ' title="'.$r['descri'].'"';
					$selected = " selected";
					$r['descri']=substr($r['descri'],0,5);
				}
				$ret1 .= '<option value="' . $r['id_absence'] . '"'. $selected.' >'.$r['causal'].'-'.$r['descri']. "</option>\n";
			}
			$ret0 .= '>';
			echo $ret0.$ret1."\t </select>\n</div>\n";
			return $retopt;
		}
	}

    function selectOtherType($name,$val,$opt=false) {
        global $gTables;
        $query = 'SELECT id_work,descri,increase FROM `' . $gTables['staff_work_type'] . '` ';
        $query .= 'WHERE id_work_type > 1  ORDER BY id_work_type, descri';
        $ret0 = '<div';
        $ret1 =  '<select name="'.$name.'" class="col-sm-12 dropdownmenustyle">';
        $ret1 .= '<option value="0"></option>';
		if ($opt){
			foreach($opt as $k=>$v){
				$selected = '';	
				if ($k == $val) {
					$ret0 .= ' title="'.$v.'"';
					$selected = " selected";
					$v=substr($v,0,8);
				}
				$ret1 .= '<option value="' . $k . '"'. $selected.' >'.$v. "</option>\n";
			}
			$ret0 .= '>';
			echo $ret0.$ret1."\t </select>\n</div>\n";
			return $opt;
		} else {
			$retopt=array();
			$result = gaz_dbi_query($query);
			while ($r = gaz_dbi_fetch_array($result)) {
				$retopt[$r['id_work']]=$r['id_work'].'-'.$r['descri'].' '.$r['increase'];
				$selected = '';
				if ($r['id_work'] == $val) {
					$ret0 .= ' title="'.$r['descri'].'"';
					$selected = " selected";
					$r['descri']=substr($r['descri'],0,5);
				}
				$ret1 .= '<option value="' . $r['id_work'] . '"'. $selected.' >'.$r['id_work'].'-'.$r['descri'].' '.$r['increase']. "</option>\n";
			}
			$ret0 .= '>';
			echo $ret0.$ret1."\t </select>\n</div>\n";
			return $retopt;
		}
    }
}

class selectEmployee extends SelectBox {

     function output($cerca, $field = 'C', $class='',$sele=1) {
        global $gTables, $script_transl;
        $msg = "";
        $opera = "%'";
        if (strlen($cerca) >= 1) {
            $opera = "%'"; ////
            $field_sql = 'description';
            if (substr($cerca, 0, 1) == "@") {
                $cerca = substr($cerca, 1);
            }
			$sql='SELECT staff.id_staff AS id, CONCAT(ana.ragso1,\' \',ana.ragso2) AS description FROM '.$gTables['staff'] . ' AS staff ' .
            'LEFT JOIN ' . $gTables['clfoco'] . ' AS worker ON staff.id_clfoco=worker.codice ' .
            'LEFT JOIN ' . $gTables['anagra'] . ' AS ana ON worker.id_anagra=ana.id '." HAVING   ".$field_sql . " LIKE '" . addslashes($cerca). $opera.' ORDER BY id ASC LIMIT 0, 2000000';
			$result = gaz_dbi_query($sql);
            $numclfoco = gaz_dbi_num_rows($result);
            if ($numclfoco > 0) {
				if ($sele) {
					echo ' <select name="' . $this->name . '" class="' . $class . '">';
                    echo '<option value=""> ---------- </option>';
					while ($z_row = gaz_dbi_fetch_array($result)) {
						$selected = "";
						if ($numclfoco==1){ // ho un solo rigo che fa matching
							$this->selected=$z_row["id"];
							$selected = ' selected ';
						} elseif ($z_row["id"] == $this->selected) {
							$selected = ' selected ';
						}
						echo ' <option value="' . $z_row["id"] . '"' . $selected .'>' . $z_row["id"] .' - '.$z_row["description"] . '</option>';
					}
					echo ' </select>';
				}
			} else {
                $msg = $script_transl['notfound'] . '!';
                echo '<input type="hidden" name="' . $this->name . '" id="' . $this->name . '" value="" />';
            }
        } else {
            $msg = $script_transl['minins'] . ' 2 ' . $script_transl['charat'] . '!';
            echo '<input type="hidden" name="' . $this->name . '" id="' . $this->name . '"  value="" />';
        }
        echo '<input type="text" class="' . $class . '" name="cosemployee" placeholder="'.$msg.'" id="search_employee" value="' . $cerca . '" maxlength="30" />';
    }
}

?>