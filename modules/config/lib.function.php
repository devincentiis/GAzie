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

class configForm extends GAzieForm {

    function selSpecieAmmortamentoMin($nameFileXML, $name, $val) {
        $refresh = '';
        if (file_exists('../../library/include/' . $nameFileXML)) {
            $xml = simplexml_load_file('../../library/include/' . $nameFileXML);
        } else {
            exit('Failed to open: ../../library/include/' . $nameFileXML);
        }
        echo "\t <select id=\"$name\" name=\"$name\" style=\"width: 350px; font-height:0.4em;\"  >\n";
        echo "\t\t <option value=\"\">-----------------</option>\n";
        foreach ($xml->gruppo as $vg) {
            echo "\t <optgroup label=\"" . $vg->gn[0] . '-' . $vg->gd[0] . "\" >\n";
            foreach ($vg->specie as $v) {
                $selected = '';
                if ($vg->gn[0] . $v->ns[0] == $val) {
                    $selected = "selected";
                }
                echo "\t\t <option value=\"" . $vg->gn[0] . $v->ns[0] . "\" $selected >• " . $v->ns[0] . " - " . $v->ds[0] . "</option>\n";
            }
            echo "\t </optgroup>\n";
        }
        echo "\t </select>\n";
    }

    function selThemeDir($name, $val) {
        echo '<select name="' . $name . '" >';
        foreach (glob('../../library/theme/*', GLOB_ONLYDIR) as $dir) {
            $selected = "";
            $expdir=explode('/',$dir);
            if (substr($dir,5) == $val) {
                $selected = " selected ";
            }
            echo "<option value=\"" . substr($dir,5) . "\"" . $selected . ">" . $expdir[4] . "</option>\n";
        }
        echo "</select>\n";
    }

	function selectCompany($name, $val, $strSearch = '', $val_hiddenReq = '', $mesg='', $class = 'FacetSelect') {
    	global $gTables, $admin_aziend;
        $table = $gTables['aziend'] . ' LEFT JOIN ' . $gTables['admin_module'] . ' ON ' . $gTables['admin_module'] . '.company_id = ' . $gTables['aziend'] . '.codice';
        $where = $gTables['admin_module'] . '.adminid=\'' . $admin_aziend["user_name"] . '\' GROUP BY company_id';
        if ($val > 0 && $val < 1000) { // vengo da una modifica della precedente select case quindi non serve la ricerca
            $co_rs = gaz_dbi_dyn_query("*", $table, 'company_id = ' . $val . ' AND ' . $where, "ragso1 ASC");
            $co = gaz_dbi_fetch_array($co_rs);
            changeEnterprise(intval($val));
            echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
            echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"%%\">\n";
            echo "\t<input type=\"submit\" value=\"" . $co['ragso1'] . "\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
        } else {
            if (strlen($strSearch) >= 2) { //sto ricercando un nuovo partner
                echo "\t<select name=\"$name\" class=\"FacetSelect\" onchange=\"this.form.hidden_req.value='$name'; this.form.submit();\">\n";
                $co_rs = gaz_dbi_dyn_query("*", $table, "ragso1 LIKE '" . addslashes($strSearch) . "%' AND " . $where, "ragso1 ASC");
                if ($co_rs) {
                    echo "<option value=\"0\"> ---------- </option>";
                    while ($r = gaz_dbi_fetch_array($co_rs)) {
                        $selected = '';
                        if ($r['company_id'] == $val) {
                            $selected = "selected";
                        }
                        echo "\t\t <option value=\"" . $r['company_id'] . "\" $selected >" . intval($r['company_id']) . "-" . $r["ragso1"] . "</option>\n";
                    }
                    echo "\t </select>\n";
                } else {
                    $msg = $mesg[0];
                }
            } else {
                $msg = $mesg[1];
                echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
            }
            echo "\t<input type=\"text\" name=\"search[$name]\" value=\"" . $strSearch . "\" maxlength=\"15\"  class=\"FacetInput\">\n";
            if (isset($msg)) {
                echo "<input type=\"text\" style=\"color: red; font-weight: bold;\"  disabled value=\"$msg\">";
            }
            //echo "\t<input type=\"image\" align=\"middle\" name=\"search_str\" src=\"../../library/images/cerbut.gif\">\n";
            /** ENRICO FEDELE */
            /* Cambio l'aspetto del pulsante per renderlo bootstrap, con glyphicon */
            echo '<button type="submit" class="btn btn-default btn-sm" name="search_str"><i class="glyphicon glyphicon-search"></i></button>';
            /** ENRICO FEDELE */
        }
	}


}

?>
