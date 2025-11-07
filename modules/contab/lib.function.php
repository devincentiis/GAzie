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

class contabForm extends GAzieForm {
  public $master_value;
  public $sub_name;
  public $gTables;
  public $name;
  public $what;

    function selMasterAcc($name, $val, $val_hiddenReq = '', $class = 'FacetSelect') {
        global $gTables, $admin_aziend;
        $bg_class = Array(1 => "gaz-attivo", 2 => "gaz-passivo", 3 => "gaz-costi", 4 => "gaz-ricavi", 5 => "gaz-transitori",
            6 => "gaz-transitori", 7 => "gaz-transitori", 8 => "gaz-transitori", 9 => "gaz-transitori");
        $refresh = '';
        if (!empty($val_hiddenReq)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$name'; this.form.submit();\"";
        }
        $query = 'SELECT * FROM `' . $gTables['clfoco'] . "` WHERE codice LIKE '%000000' ORDER BY codice ASC";
        echo "\t <select name=\"$name\" class=\"$class\" $refresh >\n";
        echo "\t\t <option value=\"\">---------</option>\n";
        $result = gaz_dbi_query($query);
        while ($r = gaz_dbi_fetch_array($result)) {
            $v = intval($r['codice'] / 1000000);
            $c = intval($v / 100);
            $selected = '';
            if ($r['codice'] == $val) {
                $selected = "selected ";
            }
            $selected .= ' class="' . $bg_class[$c] . '" ';
            if ($v == $admin_aziend['mascli'] || $v == $admin_aziend['masfor']) {
                $selected .= ' style=" color: red; font-weight: bold;" ';
                $view = $v . '-' . strtoupper($r['descri']);
            } else {
                $view = $v . '-' . $r['descri'];
            }

            echo "\t\t <option value=\"" . $r['codice'] . "\" $selected >$view</option>\n";
            $c = $v;
        }
        echo "\t </select>\n";
    }

    function lockSubtoMaster($master_value, $subName) {
        /* questa funzione dev'essere richiamata per legare la select case dei mastri
          con quella successiva dei sottoconti */
        $this->master_value = $master_value;
        $this->sub_name = $subName;
    }

    function selSubAccount($name, $val, $strSearch = '', $val_hiddenReq = '', $mesg='', $class = 'FacetSelect') {
        global $gTables, $admin_aziend;
        $mas_query = '';
        $ctrl_mas = substr($val, 0, 3);
        $master = intval($ctrl_mas * 1000000);
        if (isset($this->master_value)) {
            if ($this->sub_name == $name && $this->master_value > 100) { // // se e' gia' stato selezionato un conto legato al mastro
                $ctrl_mas = substr($this->master_value, 0, 3);
                $where = "codice LIKE '" . intval($ctrl_mas) . "%' AND codice > " . $this->master_value;
            } else { // nessuno
                $where = "codice < 0";
            }
        } else { //altrimenti tutti tranne i mastri
            $where = "codice NOT LIKE '%000000'";
        }
        if ($ctrl_mas == $admin_aziend['mascli'] || $ctrl_mas == $admin_aziend['masfor']) {
            // cliente o fornitore
            $anagrafica = new Anagrafica();
            if ($val > 100000000 && $ctrl_mas == substr($val, 0, 3)) { //vengo da una modifica della precedente select case quindi non serve la ricerca
                $partner = $anagrafica->getPartner($val);
                echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
                echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr((string)$partner['ragso1'], 0, 8) . "\">\n";
                echo "\t<input type=\"submit\" value=\"" . $partner['ragso1'] . "\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
                echo ' <button class="btn btn-warning btn-xs" title="Scadenzario" onclick="dialogSchedule(this);return false;" id="paymov' . $val . $name . '"><i class="glyphicon glyphicon-time"></i></button> ';
            } else {
                if (strlen($strSearch) >= 2) { //sto ricercando un nuovo partner
                    echo "\t<select name=\"$name\" class=\"".$class."\" onchange=\"this.form.hidden_req.value='$name'; this.form.submit();\">\n";
                    echo "<option value=\"0\"> ---------- </option>";
                    $partner = $anagrafica->queryPartners("*", $where . " AND ragso1 LIKE '" . addslashes($strSearch) . "%'", "codice ASC");
                    if (count($partner) > 0) {
                        foreach ($partner as $r) {
                            $selected = '';
                            if ($r['codice'] == $val) {
                                $selected = "selected";
                            }
                            echo "\t\t <option value=\"" . $r['codice'] . "\" $selected >" . intval($r['codice']) . "-" . $r["ragso1"] . " " . $r["citspe"] . "</option>\n";
                        }
                        echo "\t </select>\n";
                    } else {
                        $msg = $mesg[0];
                    }
                } else {
                    $msg = $mesg[1];
                    echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
                }
                echo "\t<input type=\"text\" id=\"search_$name\" name=\"search[$name]\" value=\"" . $strSearch . "\" maxlength=\"15\"  class=\"FacetInput\">\n";
                if (isset($msg)) {
                    echo "<input type=\"text\" style=\"color: red; font-weight: bold;\"  disabled value=\"$msg\">";
                }
                //echo "\t<input type=\"image\" align=\"middle\" name=\"search_str\" src=\"../../library/images/cerbut.gif\">\n";
                /** ENRICO FEDELE */
                /* Cambio l'aspetto del pulsante per renderlo bootstrap, con glyphicon */
                echo '<button type="submit" class="btn btn-default btn-sm" name="search_str"><i class="glyphicon glyphicon-search"></i></button>';
                /** ENRICO FEDELE */
            }
        } else {   // altri sottoconti
            echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"\">\n";
            echo "\t<select name=\"$name\" class=\"".$class."\" onchange=\"this.form.hidden_req.value='$name'; this.form.submit();\">\n";
            echo "<option value=\"0\"> ---------- </option>";
            $result = gaz_dbi_dyn_query("*", $gTables['clfoco'], $where, "codice ASC");
            while ($r = gaz_dbi_fetch_array($result)) {
                $selected = '';
                if ($val == $r['codice']) {
                    $selected = " selected ";
                }
                if (isset($this->master_value)) {
                    $descri = substr($r["codice"], 3, 6);
                } else {
                    $descri = $r["codice"];
                }
                echo "<option value=\"" . $r['codice'] . "\"" . $selected . ">$descri-" . $r['descri'] . "</option>";
            }
            echo "</select>\n";
        }
    }

    function settleAccount($name, $val, $date_r = false) {
        if (preg_match("/^id_([0-9]+)$/", $val, $match)) { // è un partner da inserire sul piano dei conti
            $val = 0;
        }
        global $gTables, $admin_aziend;
        $rs_display = array();
        // INIZIO determinazione limiti di date
        if ($date_r) {
            $final_date = $date_r;
        } else {
            $final_date = date("Ymd");
        }
        $rs_last_opening = gaz_dbi_dyn_query("*", $gTables['tesmov'], "caucon = 'APE' AND datreg <= " . $final_date, "datreg DESC", 0, 1);
        $last_opening = gaz_dbi_fetch_array($rs_last_opening);
        if ($last_opening) {
            $date_ini = substr($last_opening['datreg'], 0, 4) . substr($last_opening['datreg'], 5, 2) . substr($last_opening['datreg'], 8, 2);
        } else {
            $date_ini = '20040101';
        }
        // FINE determinazione limiti di date

        if ($val > 100000000 && $val < 299999999 && intval(substr($val, 3, 6)) > 0 && $val != $admin_aziend['cassa_']) {
            $where = " codcon = $val AND datreg BETWEEN $date_ini AND " . $final_date;
            $orderby = " datreg ASC ";
            $select = $gTables['tesmov'] . ".id_tes,datreg,codice," . $gTables['clfoco'] . ".descri,numdoc,datdoc,import*(darave='D') AS dare,import*(darave='A') AS avere";
            $table = $gTables['clfoco'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON " . $gTables['clfoco'] . ".codice = " . $gTables['rigmoc'] . ".codcon "
                    . "LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes ";
            $rs = gaz_dbi_dyn_query($select, $table, $where, $orderby);
            while ($r = gaz_dbi_fetch_array($rs)) {
                $rs_display[] = $r;
            }
        } else {
            $where = " codcon = $val AND datreg BETWEEN $date_ini AND " . $final_date . " GROUP BY codcon";
            $orderby = " codcon ";
            $select = "codice," . $gTables['clfoco'] . ".descri,codcon,SUM(import*(darave='D')) AS dare, SUM(import*(darave='A')) AS avere,datreg";
            $table = $gTables['clfoco'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON " . $gTables['clfoco'] . ".codice = " . $gTables['rigmoc'] . ".codcon "
                    . "LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes ";
            $rs = gaz_dbi_dyn_query($select, $table, $where, $orderby);
            while ($r = gaz_dbi_fetch_array($rs)) {
                $r['datreg'] = substr($final_date, 0, 4) . '-' . substr($final_date, 4, 2) . '-' . substr($final_date, -2);
                $r['descri'] = 'Saldo ';
                $rs_display[] = $r;
            }
        }
        echo '<div style="display:none;" class="selectContainer" id="' . $name . "\">\n";
        echo '<div class="selectHeader">' . $val . "</div>\n";
        echo '<table cellspacing="0" cellpadding="0" width="100%" class="selectTable">';
        $saldo = 0.00;
        $c = false;
        foreach ($rs_display as $r) {
            if ($c) {
                $class = 'odd';
            } else {
                $class = 'even';
            }
            $c = !$c;
            $saldo += $r['dare'];
            $saldo -= $r['avere'];
            echo "<tr class=\"$class\"> \n
                  <td>" . gaz_format_date($r['datreg']) . ' - ' . $r['descri'] . " </td>\n
                  <td style=\"text-align:right;\"> " . $r['dare'] . " </td>\n
                  <td style=\"text-align:right;\"> " . $r['avere'] . " </td>\n
                  <td style=\"text-align:right;cursor:pointer;\"> <a onclick=\"selectValue('$saldo','$name')\">" . gaz_format_number($saldo) . "</a> </td>\n
                  </tr>\n";
        }
        echo "</table></div>\n";
    }

// ------- INIZIO NUOVA VERSIONE DELLE FUNZIONI PER LA SELEZIONE DEI SOTTOCONTI-----------------------------

    function setWhat($m) {

        $this->what = "a.id AS id,pariva,codfis,a.citspe AS citta, ragso1 AS ragsoc,
                     (SELECT " . $this->gTables['clfoco'] . ".codice FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra AND " . $this->gTables['clfoco'] . ".codice BETWEEN " . substr($m, 0, 3) . "000001 AND " . substr($m, 0, 3) . "999999 LIMIT 1) AS codpart ,
                     (SELECT " . $this->gTables['clfoco'] . ".codice FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra LIMIT 1) AS codice,
                     (SELECT " . $this->gTables['clfoco'] . ".status FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra LIMIT 1) AS status ";
    }

    function queryAnagra($where = 1) {
        $rs = gaz_dbi_dyn_query($this->what, $this->gTables['anagra'] . ' AS a', $where, "a.ragso1 ASC");
        $anagrafiche = array();
        while ($r = gaz_dbi_fetch_array($rs)) {
            $anagrafiche[] = $r;
        }
        return $anagrafiche;
    }

    function sub_Account($name, $val, $strSearch = '', $val_hiddenReq = '', $mesg='',$hidden=false) {
        global $gTables, $admin_aziend;
        $this->gTables = $gTables;
        $this->name = $name;
        $this->what = "a.id AS id,pariva,codfis,a.citspe AS citta, ragso1 AS ragsoc,
                     (SELECT " . $this->gTables['clfoco'] . ".codice FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra LIMIT 1) AS codice,
                     (SELECT " . $this->gTables['clfoco'] . ".status FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra LIMIT 1) AS status, 0 AS codpart ";
        $mas_query = '';
        $ctrl_mas = substr($val, 0, 3);
        if (isset($this->master_value)) {
            if ($this->sub_name == $name && $this->master_value > 100) { // // se e' gia' stato selezionato un conto legato al mastro
                $ctrl_mas = substr($this->master_value, 0, 3);
                $where = "codice LIKE '" . intval($ctrl_mas) . "%' AND codice > " . $this->master_value;
            } else { // nessuno
                $where = "codice < 0";
            }
        } else { //altrimenti tutti tranne i mastri
            $where = "codice NOT LIKE '%000000'";
        }
        if ($ctrl_mas == $admin_aziend['mascli'] || $ctrl_mas == $admin_aziend['masfor']) { // se è un partner commerciale
          // cliente o fornitore
          $anagrafica = new Anagrafica();
          if ($val > 100000000 && $ctrl_mas == substr($val, 0, 3)) { //vengo da una modifica della precedente select case quindi non serve la ricerca
              $partner = gaz_dbi_get_row($gTables['clfoco'] . ' LEFT JOIN ' . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id', "codice", $val);
              echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
              echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr($partner['ragso1'], 0, 8) . "\">\n";
              echo "\t<input type=\"submit\" tabindex=\"999\" value=\"" . $partner['ragso1'] . "\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"PI=" . $partner['pariva'] . ' ' . $mesg[2] . "\">\n";
              echo ' <button  class="btn btn-warning btn-xs" title="Scadenzario" onclick="dialogSchedule(this);return false;" id="paymov' . $val . $name . '"><i class="glyphicon glyphicon-time"></i></button> ';
          } elseif (preg_match("/^id_([0-9]+)$/", $val, $match)) { // e' stata selezionata la sola anagrafica
              $partner = gaz_dbi_get_row($gTables['anagra'], 'id', $match[1]);
              echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
              echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr($partner['ragso1'], 0, 8) . "\">\n";
              echo "\t<input type=\"submit\" tabindex=\"999\" style=\"background:#FFBBBB\"; value=\"" . $partner['ragso1'] . "\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
          } else {
            if (strlen($strSearch) >= 2) { //sto ricercando un nuovo partner
              if ($this->master_value > 100) { //ho da ricercare nell'ambito di un mastro
                  $this->setWhat($this->master_value);
              }
              if (is_numeric($strSearch)) {                      //ricerca per partita iva
                  $partner = $this->queryAnagra(" pariva = " . intval($strSearch));
              } else {                                      //ricerca per ragione sociale
                  $partner = $this->queryAnagra(" a.ragso1 LIKE '" . addslashes($strSearch) . "%'");
              }
              if (count($partner) > 1 || $_POST['hidden_req']=='change') {
                echo "\t<select name=\"$name\" class=\"FacetSelect\"  onchange=\"if(typeof(this.form.hidden_req)!=='undefined'){this.form.hidden_req.value='$name';} this.form.submit();\">\n";
                echo "<option value=\"0\"> ---------- </option>";
                preg_match("/^id_([0-9]+)$/", $val, $match);
                foreach ($partner as $r) {
                  if ($r['codpart'] > 0) {
                      $r['codice'] = $r['codpart'];
                  }
                  $style = '';
                  $selected = '';
                  $disabled = '';
                  if ($r['status'] == 'HIDDEN') {
                      $disabled = ' disabled ';
                  }
                  if (isset($match[1]) && $match[1] == $r['id']) {
                      $selected = "selected";
                  } elseif ($r['codice'] == $val && $val > 0) {
                      $selected = "selected";
                  }
                  if ($this->master_value < 0) { // vado cercando tutti i partner del piano dei conti
                      if ($r["codice"] < 1) {  // disabilito le anagrafiche presenti solo in altre aziende
                          $disabled = ' disabled ';
                          $style = 'style="background:#FF6666";';
                      }
                  } elseif ($r["codice"] < 1) {
                      $style = 'style="background:#FF6666";';
                      $r['codice'] = 'id_' . $r['id'];
                  } elseif (substr($r["codice"], 0, 3) != substr($this->master_value, 0, 3)) {
                      $style = 'style="background:#FFBBBB";';
                      $r['codice'] = 'id_' . $r['id'];
                  }
                  echo "\t\t <option $style value=\"" . $r['codice'] . "\" $selected $disabled>" . substr($r["codice"], 3, 6) . '-' . $r["ragsoc"] . " " . $r["citta"] . "</option>\n";
                }
                echo "\t </select>\n";
              } elseif(count($partner) == 1){
                $style='';
                if ($this->master_value < 0) { // vado cercando tutti i partner del piano dei conti
                  if ($partner[0]["codpart"] < 1) {  // disabilito le anagrafiche presenti solo in altre aziende
                  }
                } elseif ($partner[0]["codpart"] < 1) {
                  $partner[0]['codpart'] = 'id_' . $partner[0]['id'];
                  $style = 'style="background:#FF6666";';
                } elseif (substr($partner[0]["codpart"], 0, 3) != substr($this->master_value,0,3)) {// non appartiene al mastro passato in $m
                  $partner[0]['codpart'] = 'id_' . $partner[0]['id'];
                  $style = 'style="background:#FF6666";';
                }
                $val=$partner[0]['codpart'];
                echo "\t<input type=\"submit\" id=\"onlyone_submit\" value=\"→ \" onclick=\"if(typeof(this.form.hidden_req)!=='undefined'){this.form.hidden_req.value='$name';} this.form.submit();\">\n";
                echo "\t<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$val\">\n";
                echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr($partner[0]['ragsoc'], 0, 8) . "\">\n";
                echo "\t<input type=\"submit\" tabindex=\"999\" value=\"" . $partner[0]['ragsoc'] . "\" name=\"change\" ".$style." onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
              } else {
                $msg = $mesg[0];
                echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
              }
            } else {
                $msg = $mesg[1];
                echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
            }
            if( !strstr($val,'id') && $val<=100000000){
              echo "\t<input type=\"text\" id=\"search_$name\" name=\"search[$name]\" value=\"" . $strSearch . "\" maxlength=\"16\" size=\"10\" class=\"FacetInput\">\n";
            }
            if (isset($msg)) {
              echo "<input type=\"text\" style=\"color: red; font-weight: bold;\" size=\"" . strlen($msg) . "\" disabled value=\"$msg\">\n";
            }
            if( !strstr($val,'id') && $val<=100000000){
               echo '<button type="submit" class="btn btn-default btn-sm" name="search_str"><i class="glyphicon glyphicon-search"></i></button>';
            }
          }
        } else {   // altri sottoconti
            echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"\">\n";
            echo "\t<select name=\"$name\" class=\"FacetSelect\" onchange=\"this.form.hidden_req.value='$name'; this.form.submit();\">\n";
            echo "<option value=\"0\"> - - - - - - - - - - - - - - - - - - - </option>";
            $where = $hidden ? $where." AND status <> 'HIDDEN' " : $where;
            $result = gaz_dbi_dyn_query("*", $gTables['clfoco'], $where, "codice ASC");
            while ($r = gaz_dbi_fetch_array($result)) {
                $selected = '';
                if ($val == $r['codice']) {
                    $selected = " selected ";
                }
                if (isset($this->master_value)) {
                    $descri = substr($r["codice"], 3, 6);
                } else {
                    $descri = $r["codice"];
                }
                echo "<option value=\"" . $r['codice'] . "\"" . $selected . ">$descri-" . $r['descri'] . "</option>";
            }
            echo "</select>\n";
        }
    }

    /* sends a Javascript toast to the client */

    function toast($message, $id = 'alert-discount', $class = 'alert-warning') {
        /*
          echo "<script type='text/javascript'>toast('$message');</script>"; */
        if (!empty($message)) {
            echo '<div class="container">
					<div id="' . $id . '" class="row alert ' . $class . ' fade in" role="alert">
						<button type="button" class="close" data-dismiss="alert" aria-label="Chiudi">
							<span aria-hidden="true">&times;</span>
						</button>
						<span class="glyphicon glyphicon-alert" aria-hidden="true"></span>&nbsp;' . $message . '
					</div>
				  </div>';
        }
        return '';
    }

    function getPeriodicyDescription($dates, $lang_transl) {
      // passare $date nel formato array('datainizio-GG/MM/AAAA','datafine-GG/MM/AAAA')
      $descri = '';
      $inisfirst=false;
      $di=substr($dates[0],0,2);
      $mi=substr($dates[0],3,2);
      $yi=substr($dates[0],6,4);
      $in= new DateTime($di.'-'.$mi.'-'.$yi);
      $in->modify('first day of this month');
      $inctrl=$in->format('d-m-Y');
      // se non coincide non ha periodicità mensile
      if ($di.'-'.$mi.'-'.$yi == $inctrl) {
        $inisfirst=true;
      }
      $fiislast=false;
      $df=substr($dates[1],0,2);
      $mf=substr($dates[1],3,2);
      $yf=substr($dates[1],6,4);
      $fi= new DateTime($df.'-'.$mf.'-'.$yf);
      $fi->modify('last day of this month');
      $fictrl=$fi->format('d-m-Y');
      // se non coincide non ha periodicità mensile
      if ($df.'-'.$mf.'-'.$yf == $fictrl) {
        $fiislast=true;
      }
      // solo se le date sono inizio e fine mese la descrizione sarà riferita ad una periodicità
      if ($inisfirst&&$fiislast){
        if ($yi==$yf){ // stesso anno
          $mdiff=$mf-$mi;
          switch ($mdiff) {
            case 0: // stesso mese
              $descri = 'del mese di '. $lang_transl['trimestre_semestre_value']['M'][intval($mi)] . ' ' .$yi;
            break;
            case 1: // bimestre
              $descri = 'del bimestre '. $lang_transl['trimestre_semestre_value']['M'][intval($mi)] . ' - ' . $lang_transl['trimestre_semestre_value']['M'][intval($mf)] . ' ' .$yi;
            break;
            case 2: // trimestre
              $descri = 'del trimestre '. $lang_transl['trimestre_semestre_value']['M'][intval($mi)] . ' - ' . $lang_transl['trimestre_semestre_value']['M'][intval($mf)] . ' ' .$yi;
            break;
            case 3: // quadrimestre
              $descri = 'del quadrimestre '. $lang_transl['trimestre_semestre_value']['M'][intval($mi)] . ' - ' . $lang_transl['trimestre_semestre_value']['M'][intval($mf)] . ' ' .$yi;
            break;
            case 5: // semestre
              $descri = 'del semestre '. $lang_transl['trimestre_semestre_value']['M'][intval($mi)] . ' - ' . $lang_transl['trimestre_semestre_value']['M'][intval($mf)] . ' ' .$yi;
            break;
            case 11: // anno
              $descri = 'dell\'anno '. $yi;
            break;
            default: // altri
              $descri = 'da '. $lang_transl['trimestre_semestre_value']['M'][intval($mi)] . ' a ' . $lang_transl['trimestre_semestre_value']['M'][intval($mf)] . ' ' .$yi;
            break;
          }
        } else { // anni diversi
          if ($mi==1 &&  $mf== 12) { // annualità intere
            $descri = 'dall\'anno '. $yi. ' all\'anno ' .$yf;
          } else {
            $descri = 'dal mese di '. $lang_transl['trimestre_semestre_value']['M'][intval($mi)]. ' ' .$yi. ' al mese di '.$lang_transl['trimestre_semestre_value']['M'][intval($mf)]. ' ' . $yf;
          }
        }
      } else { // altrimenti la descrizione conterrà solo data inizio e data fine, dal  - al
        $descri = 'dal '. $dates[0] . ' al ' .$dates[1];
      }
      return $descri;
    }
}

function rigmocUpdate($id, $newValue) {
    $columns = array('id_tes', 'darave', 'codcon', 'import');
    tableUpdate('rigmoc', $columns, $id, $newValue);
}


function calcNumPartitaAperta(&$mv) {
    if ($mv && empty($mv["id_tesdoc_ref"])) { // non è stata aperta una partita perchè pagamento immediato
        $mv["id_tesdoc_ref"] = substr($mv["datdoc"], 0, 4) . "/" . $mv["numdoc"]; // i movimenti si differenziano per id_tesdoc
    }
}

function rif_dichiarazione_iva($region_stat_code,$year=2019){
	$data=array(
		2019=>array(1=>'VT14',2=>'VT21',3=>'VT11',4=>'VT19',5=>'VT22',6=>'VT8',7=>'VT10',8=>'VT7',9=>'VT18',10=>'VT20',11=>'VT12',12=>'VT9',13=>'VT2',14=>'VT13',15=>'VT6',16=>'VT15',17=>'VT3',18=>'VT5',19=>'VT17',20=>'VT16', 21=>'VT4')
		// ATTENZIONE LA REGIONE TRENTINO E' STATA DIVISA NELLE DUE PROVINCIE (21->VT4 = BOLZANO E 4-> VT19 TRENTO)
		);
	return $data[intval($year)][intval($region_stat_code)];
}
?>
