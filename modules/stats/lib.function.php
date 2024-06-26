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

class statsForm extends GAzieForm {
	function selectCustomer($name, $val, $strSearch = '', $val_hiddenReq = '', $mesg='', $class = 'FacetSelect') {
      global $gTables, $admin_aziend;
      $anagrafica = new Anagrafica();
      if ($val > 100000000) { //vengo da una modifica della precedente select case quindi non serve la ricerca
         $partner = $anagrafica->getPartner($val);
         echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
         echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr($partner['ragso1'], 0, 8) . "\">\n";
         echo "\t<input type=\"submit\" value=\"" . $partner['ragso1'] . " " . $partner["ragso2"] . " " . $partner["citspe"] . " (" . $partner["codice"] . ")\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
      } else {
         if (strlen($strSearch) >= 2) { //sto ricercando un nuovo partner
            echo "\t<select tabindex=\"1\" name=\"$name\" class=\"FacetSelect\" onchange=\"this.form.hidden_req.value='$name'; this.form.submit();\">\n";
            echo "<option value=\"0\"> ---------- </option>";
            $partner = $anagrafica->queryPartners("*", "codice LIKE '" . $admin_aziend['mascli'] . "%' AND codice >" . intval($admin_aziend['mascli'] . '000000') . "  AND ragso1 LIKE '" . addslashes($strSearch) . "%'", "codice ASC");
            if (count($partner) > 0) {
               foreach ($partner as $r) {
                  $selected = '';
                  if ($r['codice'] == $val) {
                     $selected = "selected";
                  }
                  echo "\t\t <option value=\"" . $r['codice'] . "\" $selected >" . $r['ragso1'] . " " . $r["ragso2"] . " " . $r["citspe"] . "</option>\n";
               }
               echo "\t </select>\n";
            } else {
               $msg = $mesg[0];
            }
         } else {
            $msg = $mesg[1];
            echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
         }
         echo "\t<input tabindex=\"2\" type=\"text\" id=\"search_$name\" name=\"search[$name]\" value=\"" . $strSearch . "\" maxlength=\"15\"  class=\"FacetInput\">\n";
         if (isset($msg)) {
            echo "<input type=\"text\" style=\"color: red; font-weight: bold;\"  disabled value=\"$msg\">";
         }
//echo "\t<input tabindex=\"3\" type=\"image\" align=\"middle\" name=\"search_str\" src=\"../../library/images/cerbut.gif\">\n";
         /** ENRICO FEDELE */
         /* Cambio l'aspetto del pulsante per renderlo bootstrap, con glyphicon */
         echo '<button type="submit" class="btn btn-default btn-sm" name="search_str" tabindex="3"><i class="glyphicon glyphicon-search"></i></button>';
         /** ENRICO FEDELE */
      }
   }

}
?>