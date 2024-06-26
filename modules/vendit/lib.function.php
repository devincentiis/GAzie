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

class venditForm extends GAzieForm {

   function ticketPayments($name, $val, $class = 'FacetSelect') {
      global $gTables;
      $query = 'SELECT codice,descri,tippag FROM `' . $gTables['pagame'] . "` WHERE tippag = 'D' OR tippag = 'C' OR tippag = 'O' OR tippag = 'K' ORDER BY tippag";
      echo "\t <select name=\"$name\" class=\"$class\">\n";
      $result = gaz_dbi_query($query);
      while ($r = gaz_dbi_fetch_array($result)) {
         $selected = '';
         if ($r['codice'] == $val) {
            $selected = "selected";
         }
         echo "\t\t<option value=\"" . $r['codice'] . "\" $selected >" . $r['descri'] . "</option>\n";
      }
      print "\t </select>\n";
   }

   function getECR_userData($login) {
      global $gTables;
      return gaz_dbi_get_row($gTables['cash_register'], 'adminid', $login);
   }

   function getECRdata($id) {
      global $gTables;
      return gaz_dbi_get_row($gTables['cash_register'], 'id_cash', $id);
   }

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
   function selectAsset($name, $val, $class = 'FacetSelect') {
        global $gTables, $admin_aziend;
        echo "<select id=\"$name\" name=\"$name\" class=\"$class\">\n";
        echo "\t<option value=\"0\"> ---------- </option>\n";
        $result = gaz_dbi_dyn_query("acc_fixed_assets, descri", $gTables['assets'], "type_mov = 1");
        while ($r = gaz_dbi_fetch_array($result)) {
            $selected = '';
            $v = $r["acc_fixed_assets"];
            if ($val == $v) {
                $selected .= " selected ";
            }
            echo "\t<option value=\"" . $v . "\"" . $selected . ">" . $r["acc_fixed_assets"] . "-" . $r['descri'] . "</option>\n";
        }
        echo "</select>\n";
   }

   function selRifDettaglioLinea($name, $val, $RiferimentoNumeroLinea, $class = '') {
        global $gTables, $admin_aziend;
        echo '<select id="'.$name.'" name="'.$name.'" class="'.$class.'">';
        echo '<option value="">Tutto il documento</option>';
		foreach ($RiferimentoNumeroLinea as $k=>$v) {
			$selected = '';
			if ($k == $val) $selected = ' selected';
			echo '<option value="'.$k.'" '.$selected.' >Linea n.'.$k.' '.$v.'</option>';
		}
        echo "</select>\n";
   }

   function concileArtico($name,$key,$val,$class='small') {
      global $gTables;
	  $acc='';
	  $query = 'SELECT * FROM `' . $gTables['artico'] . '`  ORDER BY `catmer`,`codice`';
      $acc .= '<select id="'.$name.'" name="'.$name.'" class="'.$class.'">';
      $acc .= '<option value="" style="background-color:#5bc0de;">NON IN MAGAZZINO</option>';
      $acc .= '<option value="Insert_New" style="background-color:#f0ad4e;">INSERISCI COME NUOVO</option>';
      $result = gaz_dbi_query($query);
      while ($r = gaz_dbi_fetch_array($result)) {
          $selected = '';
          $setstyle = '';
          if ($r[$key] == $val) {
              $selected = " selected ";
              $setstyle = ' style="background-color:#5cb85c;" ';
          }
          $acc .= '<option class="small" value="'.$r[$key].'"'.$selected.''.$setstyle.'>'.$r['codice'].'-'.substr($r['descri'],0,30).'</option>';
      }
      $acc .= '</select>';
		return $acc;
   }

   function selectRegistratoreTelematico($val,$user_name) { // funzione per selezionare tra i registratori telematici abiliti per l'utente
        global $gTables, $admin_aziend;
        echo '<select id="id_cash" name="id_cash">';
        echo '<option value="0">File XML (no RT)</option>';
        $result = gaz_dbi_dyn_query("id_cash, descri", $gTables['cash_register'], "enabled_users LIKE '%".$user_name."%'");
        while ($r = gaz_dbi_fetch_array($result)) {
            $selected = '';
            if ($val == $r["id_cash"]) {
                $selected .= " selected ";
            }
            echo '<option value="' . $r["id_cash"] . '"' . $selected . '>' . $r['descri'] . "</option>\n";
        }
        echo "</select>\n";
   }

   function chkRegistratoreTelematico($user_name) { // controllo se l'utente è abilitato ad almeno un RT e restituisco il valore altrimenti false
        global $gTables;
        // trovo il registratore che è stato usato per ultimo dall'utente abilitato
        $rs_last = gaz_dbi_dyn_query("*", $gTables['cash_register']." LEFT JOIN ".$gTables['tesdoc']." ON ".$gTables['cash_register'].".id_cash = ".$gTables['tesdoc'].".id_contract", "tipdoc ='VCO' AND id_contract > 0 AND enabled_users LIKE '%".$user_name."%'", $gTables['tesdoc'].'.datemi DESC,'.$gTables['tesdoc'].'.numdoc DESC', 0, 1);
        $exist = gaz_dbi_fetch_array($rs_last);
        return ($exist)?$exist['id_cash']:false;
   }

   function selectRepartoIVA($val,$id_cash=0) { // per selezionare l'aliquota IVA, tutte se viene prodotto un XML (id_cash=0) ed in base ai reparti del Registatore Telematico se viene utilizzato questo (id_cash > 0)
        global $gTables;
		$table_where=($id_cash>=1)?$gTables['cash_register_reparto']. " LEFT JOIN ". $gTables['aliiva']." ON ".$gTables['cash_register_reparto'].".aliiva_codice = ".$gTables['aliiva'].".codice":$gTables['aliiva'];
        echo '<select id="in_codvat" name="in_codvat">';
        echo '<option value="0">-------------</option>';
        $result = gaz_dbi_dyn_query($gTables['aliiva'].".codice, ".$gTables['aliiva'].".descri",$table_where);
        while ($r = gaz_dbi_fetch_array($result)) {
            $selected = '';
            if ($val == $r["codice"]) {
                $selected .= " selected ";
            }
            echo '<option value="' . $r["codice"] . '"' . $selected . '>' . $r['descri'] . "</option>\n";
        }
        echo "</select>\n";
   }

   function chkReparto($codvat,$id_cash) { // controllo se il codice IVA dell'articolo ha un reparto associato, se presente restituisco il valore
        global $gTables;
        $exist = gaz_dbi_get_row($gTables['cash_register_reparto'],"aliiva_codice",$codvat, "AND cash_register_id_cash = ".$id_cash);
        return ($exist)?$exist['reparto']:false;
   }

   function selectBanacc($val,$name='bank') { // per selezionare la banca d'accredito degli effetti
        $eof=false;
        global $gTables,$admin_aziend;
        echo '<select id="'.$name.'" name="'.$name.'">';
        $rs=gaz_dbi_dyn_query( $gTables['clfoco'].".*,".$gTables['banapp'].".codabi,".$gTables['banapp'].".codcab", $gTables['clfoco']. " LEFT JOIN ". $gTables['banapp']." ON ".$gTables['clfoco'].".banapp = ".$gTables['banapp'].".codice",$gTables['clfoco']. ".codice BETWEEN ".$admin_aziend['masban']."000001 AND ".$admin_aziend['masban']."999999 AND  banapp > 0");
        while ($r = gaz_dbi_fetch_array($rs)) {
            $selected = '';
            if ($val == $r["codice"]) {
                $selected .= " selected ";
                $eof=($r["addbol"]=='N')?false:true;
            }
            echo '<option value="' . $r["codice"] . '"' . $selected . '>' . $r['descri'] ." ABI:".$r['codabi']." CAB:".$r['codcab']. "</option>\n";
        }
        echo "</select>\n";
        return $eof;
   }
   function getAllPrevLots($codart,$datref) {
// restituisce la quantità residua di tutti i lotti precedenti o uguali alla data di riferimento, serve per proporre un inventario per lotti ad una data
      global $gTables;
// prendo tutti i movimenti dell'articolo e li raggruppo per ognuno di essi anche se non hanno lotti id_lotmag=0
      $sqlquery = "SELECT id_lotmag, SUM(quanti*operat) AS rest, identifier, lot_or_serial AS ls FROM " . $gTables['movmag'] . " LEFT JOIN " . $gTables['lotmag'] . " ON " . $gTables['movmag'] . ".id_lotmag =" . $gTables['lotmag'] . ".id LEFT JOIN " . $gTables['artico'] . " ON " . $gTables['movmag'] . ".artico =" . $gTables['artico'] . ".codice WHERE " . $gTables['movmag'] . ".artico = '" . $codart . "' AND datreg <= '".$datref."' AND caumag < 99 GROUP BY " . $gTables['movmag'] . ".id_lotmag ORDER BY ". $gTables['lotmag'] . ".id";
      $result = gaz_dbi_query($sqlquery);
      $acc=[];
      while ($row = gaz_dbi_fetch_array($result)) {
            $acc[] = $row;
      }
      return $acc;
   }

	// FUNZIONE PER RECUPERARE ULTIMO PROGRESSIVO PACCHETTO IN fae_flux, RESTITUISCE IL NUMERO PROGRESSIVO DELL'ULTIMO PACCHETTO CREATO/INVIATO
	function getLastPack()
	{
		global $gTables;
		$where = "(filename_zip_package != '') AND exec_date LIKE '" . date('Y') . "%' ";
		$orderby = "filename_zip_package DESC";
		$from = $gTables['fae_flux'];
		$result = gaz_dbi_dyn_query('*', $from, $where, $orderby, 0, 1);
		$row = gaz_dbi_fetch_array($result);
		if(!$row){$row['filename_zip_package']='00000.zip';}
		return substr($row['filename_zip_package'],-9,5);
	}

	function getFAEunpacked($include_fe_PA = true) { // FUNZIONE CHE CONTROLLA LO STATO DELLE FATTURE DA IMPACCHETTARE PER INVIARE ALLO SDI
		global $gTables, $admin_aziend;
    // controllo se impacchettare le fatture derivanti da corrispettivi non anonimi
    $fae_ticket_pack = gaz_dbi_get_row($gTables['company_config'], 'var', 'fae_ticket_pack');
	$fae_ticket_pack['val']=(isset($fae_ticket_pack['val']))?$fae_ticket_pack['val']:0;
    $packVCO = ($fae_ticket_pack['val']==0)?"":"OR (tipdoc = 'VCO' AND numfat > 0)";
		$calc = new Compute;
		$from = $gTables['tesdoc'] . ' AS tesdoc
				 LEFT JOIN ' . $gTables['pagame'] . ' AS pay ON tesdoc.pagame=pay.codice
				 LEFT JOIN ' . $gTables['clfoco'] . ' AS customer ON tesdoc.clfoco=customer.codice
				 LEFT JOIN ' . $gTables['anagra'] . ' AS anagraf ON customer.id_anagra=anagraf.id
				 LEFT JOIN ' . $gTables['country'] . ' AS country ON anagraf.country=country.iso
				 LEFT JOIN ' . $gTables['fae_flux'] . ' AS flux ON tesdoc.id_tes = flux.id_tes_ref ';
		$where = "(fattura_elettronica_zip_package IS NULL OR fattura_elettronica_zip_package = '')
				  AND (flux_status = '' OR flux_status = 'DI' OR flux_status = 'PI' OR flux_status IS NULL)
				  AND (tipdoc LIKE 'F__'  ".$packVCO." OR (tipdoc LIKE 'X__') )";
		if (!$include_fe_PA) {
			$where.= " AND LENGTH(fe_cod_univoco)<>6";
		}
		$orderby = "seziva ASC,tipdoc ASC, protoc ASC";
		$result = gaz_dbi_dyn_query('tesdoc.*, CONCAT(tesdoc.seziva,SUBSTRING(tesdoc.tipdoc,1,1),tesdoc.protoc) AS ctrlp, SUBSTRING(tesdoc.tipdoc,1,1) AS ctrlreg ,
							pay.tippag,pay.numrat,pay.incaut,pay.tipdec,pay.giodec,pay.tiprat,pay.mesesc,pay.giosuc,pay.id_bank,
							customer.codice, customer.speban AS addebitospese,
							CONCAT(anagraf.ragso1,\' \',anagraf.ragso2) AS ragsoc, anagraf.citspe, anagraf.prospe, anagraf.capspe, anagraf.country, anagraf.fe_cod_univoco, anagraf.pec_email, anagraf.e_mail, anagraf.country,
							country.istat_area, flux.flux_status, flux.id_tes_ref, flux.filename_ret, flux.filename_ori, flux.filename_son', $from, $where, $orderby);
		$docs['data'] = [];
		$ctrlp = '';
		$carry = 0;
		$ivasplitpay = 0;
		$somma_spese = 0;
		$totimpdoc = 0;
		$taxstamp = 0;
		$rit = 0;
		while ($tes = gaz_dbi_fetch_array($result)) {
      // se è la testata di una fattura differita con più DdT che manca di referenza su fae_flux allora la salto
      $checkddt = gaz_dbi_get_row($gTables['tesdoc']. ' as td LEFT JOIN ' . $gTables['fae_flux'] . ' as ff ON td.id_tes = ff.id_tes_ref', 'td.tipdoc', 'FAD', " AND td.seziva = ".$tes['seziva']." AND td.datfat = '".$tes['datfat']."' AND td.protoc = ".$tes['protoc']."  AND ff.flux_status <> '' AND ff.flux_status <> 'DI' AND ff.flux_status IS NOT NULL"); // se questo restituisce una riga vuol dire che il DdT non può comunque essere impacchettato in quanto
      if(!$checkddt){ // considero impachettabili solo i protocolli che non hanno alcuna testata risultante inviata dal controllo di sopra
        if ($tes['ctrlp'] <> $ctrlp) { // la prima testata della fattura
          if (strlen($ctrlp) > 0 && ($docs['data'][$ctrlp]['tes']['stamp'] >= 0.01 || $docs['data'][$ctrlp]['tes']['taxstamp'] >= 0.01 )) { // non è il primo ciclo faccio il calcolo dei bolli del pagamento e lo aggiungo ai castelletti
            $calc->payment_taxstamp($calc->total_imp + $calc->total_vat + $carry - $rit - $ivasplitpay + $taxstamp, $docs['data'][$ctrlp]['tes']['stamp'], $docs['data'][$ctrlp]['tes']['round_stamp'] * $docs['data'][$ctrlp]['tes']['numrat']);
            $calc->add_value_to_VAT_castle($docs['data'][$ctrlp]['vat'], $taxstamp + $calc->pay_taxstamp, $admin_aziend['taxstamp_vat']);
            $docs['data'][$ctrlp]['vat'] = $calc->castle;
            // aggiungo il castelleto conti
            if (!isset($docs['data'][$ctrlp]['acc'][$admin_aziend['boleff']])) {
              $docs['data'][$ctrlp]['acc'][$admin_aziend['boleff']]['import'] = 0;
            }
            $docs['data'][$ctrlp]['acc'][$admin_aziend['boleff']]['import'] += $taxstamp + $calc->pay_taxstamp;
          }
          $carry = 0;
          $ivasplitpay = 0;
          $cast_vat = [];
          $cast_acc = [];
          $somma_spese = 0;
          $totimpdoc = 0;
          $totimp_decalc = 0.00;
          $n_vat_decalc = 0;
          $spese_incasso = $tes['numrat'] * $tes['speban'];
          $taxstamp = 0;
          $rit = 0;
        } else {
          $spese_incasso = 0;
        }
        // aggiungo il bollo sugli esenti/esclusi se nel DdT c'è ma non è ancora stato mai aggiunto
        if ($tes['taxstamp'] >= 0.01 && $taxstamp < 0.01) {
          $taxstamp = $tes['taxstamp'];
        }
        if ($tes['virtual_taxstamp'] == 0 || $tes['virtual_taxstamp'] == 3) { //  se è a carico dell'emittente non lo aggiungo al castelletto IVA
          $taxstamp = 0.00;
        }
        if ($tes['traspo'] >= 0.01) {
          if (!isset($cast_acc[$admin_aziend['imptra']]['import'])) {
            $cast_acc[$admin_aziend['imptra']]['import'] = $tes['traspo'];
          } else {
            $cast_acc[$admin_aziend['imptra']]['import'] += $tes['traspo'];
          }
        }
        if ($spese_incasso >= 0.01) {
          if (!isset($cast_acc[$admin_aziend['impspe']]['import'])) {
            $cast_acc[$admin_aziend['impspe']]['import'] = $spese_incasso;
          } else {
            $cast_acc[$admin_aziend['impspe']]['import'] += $spese_incasso;
          }
        }
        if ($tes['spevar'] >= 0.01) {
          if (!isset($cast_acc[$admin_aziend['impvar']]['import'])) {
            $cast_acc[$admin_aziend['impvar']]['import'] = $tes['spevar'];
          } else {
            $cast_acc[$admin_aziend['impvar']]['import'] += $tes['spevar'];
          }
        }
        //recupero i dati righi per creare il castelletto
        $from = $gTables['rigdoc'] . ' AS rs
              LEFT JOIN ' . $gTables['aliiva'] . ' AS vat
              ON rs.codvat=vat.codice';
        $rs_rig = gaz_dbi_dyn_query('rs.*,vat.tipiva AS tipiva', $from, "rs.id_tes = " . $tes['id_tes'], "id_tes DESC");
        while ($r = gaz_dbi_fetch_array($rs_rig)) {
          if ($tes['tipdoc']=='XNC'){ // è una nota di credito del reverse charge lo SdI vuole che siano negativi gli importi in quanto non prevista una tipologia specifica
            $r['prelis']=-abs($r['prelis']);
          }
          if ($r['tiprig'] <= 1 || $r['tiprig'] == 90) { //ma solo se del tipo normale, forfait, vendita cespite
            //calcolo importo rigo
            $importo = CalcolaImportoRigo($r['quanti'], $r['prelis'], array($r['sconto'], $tes['sconto']));
            if ($r['tiprig'] == 1 || $r['tiprig'] == 90) { // se di tipo forfait o vendita cespite
              $importo = CalcolaImportoRigo(1, $r['prelis'], $tes['sconto']);
            }
            //creo il castelletto IVA
            if (!isset($cast_vat[$r['codvat']]['impcast'])) {
              $cast_vat[$r['codvat']]['impcast'] = 0;
              $cast_vat[$r['codvat']]['ivacast'] = 0;
              $cast_vat[$r['codvat']]['periva'] = $r['pervat'];
              $cast_vat[$r['codvat']]['tipiva'] = $r['tipiva'];
            }
            $cast_vat[$r['codvat']]['impcast'] += $importo;
            $cast_vat[$r['codvat']]['ivacast'] += round(($importo * $r['pervat']) / 100, 2);
            $totimpdoc += $importo;
            //creo il castelletto conti
            if (!isset($cast_acc[$r['codric']]['import'])) {
              $cast_acc[$r['codric']]['import'] = 0;
            }
            $cast_acc[$r['codric']]['import'] += $importo;
            if ($r['tiprig'] == 90) { // se è una vendita cespite lo indico sull'array dei conti
              $cast_acc[$r['codric']]['asset'] = 1;
            }
            $rit += round($importo * $r['ritenuta'] / 100, 2);
            // aggiungo all'accumulatore l'eventuale iva non esigibile (split payment)
            if ($r['tipiva'] == 'T') {
              $ivasplitpay += round(($importo * $r['pervat']) / 100, 2);
            }
          } elseif ($r['tiprig'] == 3) {
            $carry += $r['prelis'];
          }
        }
        $docs['data'][$tes['ctrlp']]['tes'] = $tes;
        $docs['data'][$tes['ctrlp']]['acc'] = $cast_acc;
        $docs['data'][$tes['ctrlp']]['car'] = $carry;
        $docs['data'][$tes['ctrlp']]['isp'] = $ivasplitpay;
        $docs['data'][$tes['ctrlp']]['rit'] = $rit;
        $somma_spese += $tes['traspo'] + $spese_incasso + $tes['spevar'];
        $calc->add_value_to_VAT_castle($cast_vat, $somma_spese, $tes['expense_vat']);
        $docs['data'][$tes['ctrlp']]['vat'] = $calc->castle;

        // QUI ACCUMULO I VALORI MASSIMI E MINIMI DEI PROTOCOLLI PER OGNI SINGOLO REGISTRO/SEZIONE IVA
        if (!isset($docs['head'][$tes['seziva']][$tes['ctrlreg']])){
          $docs['head'][$tes['seziva']][$tes['ctrlreg']]['min']=999999999;
          $docs['head'][$tes['seziva']][$tes['ctrlreg']]['max']=1;
        }
        if ($tes['ctrlreg']=='V'){ $tes['protoc']=$tes['numfat']; }
        $docs['head'][$tes['seziva']][$tes['ctrlreg']]['min']=($tes['protoc']<$docs['head'][$tes['seziva']][$tes['ctrlreg']]['min'])?$tes['protoc']:$docs['head'][$tes['seziva']][$tes['ctrlreg']]['min'];
        $docs['head'][$tes['seziva']][$tes['ctrlreg']]['max']=($tes['protoc']>$docs['head'][$tes['seziva']][$tes['ctrlreg']]['max'])?$tes['protoc']:$docs['head'][$tes['seziva']][$tes['ctrlreg']]['max'];
        // FINE ACCUMULO MIN-MAX PROTOCOLLI
        $ctrlp = $tes['ctrlp'];
      }
		}
		if (strlen($ctrlp) > 0 && ($docs['data'][$ctrlp]['tes']['stamp'] >= 0.01 || $taxstamp >= 0.01)) { // a chiusura dei cicli faccio il calcolo dei bolli del pagamento e lo aggiungo ai castelletti
			$calc->payment_taxstamp($calc->total_imp + $calc->total_vat + $carry - $rit - $ivasplitpay + $taxstamp, $docs['data'][$ctrlp]['tes']['stamp'], $docs['data'][$ctrlp]['tes']['round_stamp'] * $docs['data'][$ctrlp]['tes']['numrat']);
			// aggiungo al castelletto IVA
			$calc->add_value_to_VAT_castle($docs['data'][$ctrlp]['vat'], $taxstamp + $calc->pay_taxstamp, $admin_aziend['taxstamp_vat']);
			$docs['data'][$ctrlp]['vat'] = $calc->castle;
			// aggiungo il castelleto conti
			if (!isset($docs['data'][$ctrlp]['acc'][$admin_aziend['boleff']])) {
				$docs['data'][$ctrlp]['acc'][$admin_aziend['boleff']]['import'] = 0;
			}
			$docs['data'][$ctrlp]['acc'][$admin_aziend['boleff']]['import'] += $taxstamp + $calc->pay_taxstamp;
		}
		return $docs;
	}
	function computeTotFromVatCastle($data) {
		$tax = 0;
		$vat = 0;
		foreach ($data as $k => $v) {
			$tax += $v['impcast'];
			$vat += round($v['impcast'] * $v['periva']) / 100;
		}
		$tot = $vat + $tax;
		return array('taxable' => $tax, 'vat' => $vat, 'tot' => $tot);
	}

}

class Agenti {

   function getPercent($id_agente, $articolo = '') {
      global $gTables;
      if ($id_agente < 1) {
         return false;
      } else { // devo ricavare la percentuale associata all'articolo(prioritaria) o categoria merceologica
         $value = gaz_dbi_get_row($gTables['artico'], 'codice', $articolo);
         if (!isset($value['catmer'])) $value['catmer']=0;
         $rs = gaz_dbi_dyn_query($gTables['agenti'] . ".*," . $gTables['provvigioni'] . ".*", $gTables['agenti'] . " LEFT JOIN " . $gTables['provvigioni'] . " ON " . $gTables['agenti'] . ".id_agente = ". $gTables['provvigioni'] . ".id_agente", $gTables['provvigioni'] . ".id_agente = " . $id_agente . " AND ((cod_articolo = '" . $articolo . "' AND cod_articolo != '') OR (cod_catmer = " .         intval($value['catmer']) .
         " AND cod_articolo = ''))", 'cod_articolo DESC', 0, 1);
         $result = gaz_dbi_fetch_array($rs);
         if ($result) {
            return $result['percentuale'];
         } else {
            $result = gaz_dbi_get_row($gTables['agenti'], 'id_agente', $id_agente);
            return $result['base_percent'];
         }
      }
   }

}

class venditCalc extends Compute {

   function contractCalc($id_contract) {
//recupero il contratto da calcolare
      global $gTables, $admin_aziend;
      $this->contract_castle = array();
      $contract = gaz_dbi_get_row($gTables['contract'], "id_contract", $id_contract);
      $this->contract_castel[$contract['vat_code']]['impcast'] = $contract['current_fee'];
      $result = gaz_dbi_dyn_query('*', $gTables['contract_row'], $gTables['contract_row'] . '.id_contract =' . $id_contract, $gTables['contract_row'] . '.id_row');
      while ($row = gaz_dbi_fetch_array($result)) {
         $r_val = CalcolaImportoRigo($row['quanti'], $row['price'], array($row['discount']));
         if (!isset($this->contract_castel[$row['vat_code']])) {
            $this->contract_castel[$row['vat_code']]['impcast'] = 0.00;
         }
         $this->contract_castel[$row['vat_code']]['impcast']+=$r_val;
      }
      $this->add_value_to_VAT_castle($this->contract_castel, $admin_aziend['taxstamp'], $admin_aziend['taxstamp_vat']);
   }

   function computeRounTo($rows, $body_discount, $down = false, $decimal = 5) {
// questa funzione mi servrà per arrotondare ad 1 euro (sia per difetto che per eccesso) i documenti di vendita
      $tot = 0;
      $tqu = 0;
      foreach ($rows as $k => $v) {
         $rows[$k]['sortkey'] = $k; // mi serve per ricordare l'ordine originale
         $rows[$k]['sortquanti'] = 9999999; // mi serve per evitare di ordinare quantità a zero
         if ($v['tiprig'] == 1 || ($v['quanti'] >= 0.001 && $v['tiprig'] == 0)) {
            if ($v['tiprig'] == 0) { // tipo normale
               $tot_row = CalcolaImportoRigo($v['quanti'], $v['prelis'], array($v['sconto'], $body_discount, -$v['pervat']));
            } else {                 // tipo forfait
               $tot_row = CalcolaImportoRigo(1, $v['prelis'], -$v['pervat']);
               $v['quanti'] = 1;
            }
            $rows[$k]['totrow'] = $tot_row;
            $rows[$k]['sortquanti'] = $v['quanti'];
         }
         $tot+=$tot_row;
         $tqu+=$v['quanti'];
         $tot_row = 0;
      }
      $vt = ceil($tot);
      if ($down) {
         $vt = floor($tot);
      }
// cifra totale da arrontondare  e non superare!!!
      $diff = round(($vt - $tot), 2);
// cifra da arrotondare per ogni rigo (IVA compresa)
      $rest = $diff / $tqu;
// riordino l'array per quantità in modo da tentare di imputare le variazioni di prezzo per prima alle quantità maggiori dove è più difficile raggiungere questo obbiettivo
      usort($rows, function($a, $b) {
         return $b['sortquanti'] - $a['sortquanti'];
      });
// riattraverso l'array e scrivo di quanto dovrebbe essere aumentato il prezzo per ogni rigo
      $acc_diff = 0;
      $acc = $rows;
      foreach ($rows as $k => $v) { // riattraverso l'array e scrivo di quanto dovrebbe essere aumentato il prezzo per ogni rigo
         if ($v['tiprig'] == 1 || ($v['quanti'] >= 0.001 && $v['tiprig'] == 0)) {
// tolgo l'iva che verrà sommata ma ci aggiungo gli eventuali sconti
            $rest_part = $rest / (1 + $v['pervat'] / 100) / (1 - $body_discount / 100) / (1 - $v['sconto'] / 100);
            $acc[$k]['prelis'] = round(($v['prelis'] + $rest_part), $decimal);
            if ($v['tiprig'] == 0) { // tipo normale
               $new_tot_row = CalcolaImportoRigo($v['quanti'], $acc[$k]['prelis'], array($v['sconto'], $body_discount, -$v['pervat']));
            } else {                 // tipo forfait
               $new_tot_row = CalcolaImportoRigo(1, $acc[$k]['prelis'], -$v['pervat']);
            }
            $acc[$k]['totrow'] = $new_tot_row;
// accumulo la differenza
            $acc_diff -= ($rows[$k]['totrow'] - $new_tot_row);
         }
      }
// controllo se ho arrotondato tutta la diffarenza iniziale
      $ctrl_diff = round(($diff - $acc_diff), 2);
// sull'ultimo rigo che è pure quello con la quantità più bassa provo ad arrotondare perchè più facile farlo modificando il solo prezzo
      end($acc);
      $lastkey = key($acc);
      $decpow = pow(10, $decimal);
      if (($ctrl_diff <= -0.01 || $ctrl_diff >= 0.01) && $acc[$lastkey]['quanti'] > 0.001) { // se sto arrotondando per eccesso no posso diminuire di troppo allora il valore non dovrà eccedere
         $diff_prelis = ceil($ctrl_diff / (1 + $acc[$lastkey]['pervat'] / 100) / (1 - $body_discount / 100) / (1 - $acc[$lastkey]['sconto'] / 100) / $acc[$lastkey]['quanti'] * $decpow) / $decpow;
         $acc[$lastkey]['prelis'] += $diff_prelis;
         if ($v['tiprig'] == 0) { // tipo normale
            $new_tot_row = CalcolaImportoRigo($acc[$lastkey]['quanti'], $acc[$lastkey]['prelis'], array($acc[$lastkey]['sconto'], $body_discount, -$acc[$lastkey]['pervat']));
         } else {                 // tipo forfait
            $new_tot_row = CalcolaImportoRigo(1, $acc[$lastkey]['prelis'], -$acc[$lastkey]['pervat']);
         }
//vedo se sono riuscito a compensare la differenza iniziale
         $new_diff = round(($acc[$lastkey]['totrow'] - $new_tot_row - $diff + $acc_diff), 2);
         if ($new_diff >= 0.01) {
// non ci sono riuscito: provo con lo sconto che vado ad indicare in array sul rigo id=0
            $acc[0]['new_body_discount'] = $body_discount + (floor($new_diff / $tot * 10000)) / 100;
         }
      }
// INFINE riordino l'array secondo le key originarie
      usort($acc, function($a, $b) {
         return $a['sortkey'] - $b['sortkey'];
      });
      return $acc;
   }

   /**
    * controlla nell'ordine:
    * 1) prezzo netto cliente/articolo
    * 2) sconto cliente/articolo
    * 3) sconto cliente/raggruppamento (anche per tutti i super-raggruppamenti
    * 4) sconto cliente
    * 5) sconto articolo
    *
    * se trova un prezzo netto nella tabella sconti cliente/articolo restituisce il numero in negativo,
    * altrimenti restituisce un numero positivo
    */
   function trovaPrezzoNetto_Sconto($codcli, $codart) {
      global $gTables, $msgtoast;
      $tabellaClienti = $gTables['clfoco'];
      $tabellaArticoli = $gTables['artico'];
      $tabellaScontiArticoli = $gTables['sconti_articoli'];
      $tabellaScontiRaggruppamenti = $gTables['sconti_raggruppamenti'];
//cerco prezzo netto cliente/articolo
      $prezzo_netto = gaz_dbi_get_single_value($tabellaScontiArticoli, "prezzo_netto", "clfoco='$codcli' and codart='$codart'");
      if ($prezzo_netto > 0) {
         $msgtoast = $codart . ": prezzo netto articolo riservato al cliente";
         return -$prezzo_netto;
      }
//cerco sconto cliente/articolo
      $scontoTrovato = gaz_dbi_get_single_value($tabellaScontiArticoli, "sconto", "clfoco='$codcli' and codart='$codart'");
      if ($scontoTrovato > 0) { // sconto cliente/articolo
         $msgtoast = $codart . ": sconto articolo riservato al cliente";
         return $scontoTrovato;
      }
//cerco sconto cliente/raggruppamento
      $artico = gaz_dbi_get_row($gTables['artico'], "codice", $codart);
      if (isset($artico) && strlen($artico['ragstat']) >= 1) { // questo articolo fa parte di un raggruppamento statico, controllo se è stato selezionato un sconto particolare per il cliente
        $scontoTrovato = gaz_dbi_get_single_value($tabellaScontiRaggruppamenti, "sconto", "clfoco='$codcli' AND ragstat = '".$artico['ragstat']."'");
        if ($scontoTrovato > 0) { // sconto presente
          $msgtoast = $codart . ": sconto raggruppamento statistico riservato al cliente";
          return $scontoTrovato;
        }
      }
//cerco sconto cliente
      $scontoTrovato = gaz_dbi_get_single_value($tabellaClienti, "sconto", "codice='$codcli'");
      if ($scontoTrovato > 0) { // sconto cliente/articolo
         $msgtoast = $codart . ": sconto generico riservato al cliente";
         return $scontoTrovato;
      }
//cerco sconto articolo
//      $scontoTrovato = gaz_dbi_get_single_value($tabellaArticoli, "sconto", "codice='$codart'");
      $scontoGenericoArticolo = gaz_dbi_get_single_value($tabellaArticoli, "sconto", "codice='$codart'");
      if ($scontoGenericoArticolo > 0) { // sconto articolo
         $msgtoast = $codart . ": sconto da anagrafe articoli";
         return $scontoGenericoArticolo;
      }
      return 0;
   }

}
#[\AllowDynamicProperties]
class lotmag {

  public $available;
  public $lot;

  function __construct() {
    $this->available = [];
  }

   function getLot($id) {
// restituisce i dati relativi ad uno specifico lotto
      global $gTables;
      $sqlquery = "SELECT * FROM " . $gTables['lotmag'] . "
            LEFT JOIN " . $gTables['movmag'] . " ON " . $gTables['lotmag'] . ".id_movmag =" . $gTables['movmag'] . ".id_mov
            WHERE " . $gTables['lotmag'] . ".id = '" . $id . "'";
      $result = gaz_dbi_query($sqlquery);
      $this->lot = gaz_dbi_fetch_array($result);
      return $this->lot;
   }

   function getAvailableLots($codart, $excluded_movmag = 0, $date="", $negative=0, $all=false) {
// restituisce tutti i lotti non completamente venduti ordinandoli in base alla configurazione aziendale (FIFO o LIFO)
// e propone una ripartizione, se viene passato un movimento di magazzino questo verrà escluso perché si suppone sia lo stesso
// che si sta modificando
// Antonio Germani - si escludono dal conteggio tutti gli inventari: caumag 98 e 99. Gli inventari non hanno lotti, quindi bisogna analizzare sempre tutto il database.
// Antonio Germani - $excluded_movmag può essere un singolo ID oppure multipli ID in un array:  array("ID1", "ID2", "etc");
    global $gTables, $admin_aziend;
    $ob = ' ASC'; // FIFO-PWM-STANDARD (First In First Out)
    if ($admin_aziend['stock_eval_method'] == 2) {
       $ob = ' DESC'; // LIFO (Last In First Out)
    }
    if (is_array($excluded_movmag)){
      $n=0;$add_excl="";
      foreach($excluded_movmag as $each){
        if ($n>0){
          $add_excl.= " AND id_mov <> ".intval($each);
        } else {
          $add_excl.= intval($each);
        }
        $n++;
      }
      $excluded_movmag=$add_excl;
    }
	  $add_where="";
	  if (intval($date)>0){
		$add_where=$gTables['movmag'] . ".datreg < '". $date ."' AND ";
	  }
    // Antonio Germani - la data di creazione del primo lotto per il dato articolo
    $first_lot_date=gaz_dbi_get_row($gTables['movmag'], "artico", $codart, " AND id_lotmag > '1' AND caumag <> '99' AND operat = '1'", "MIN(datdoc)");
    if (!isset($first_lot_date)){
      $first_lot_date="1970-01-01";// imposto una data fittizia se non esiste una data reale
    }
    $sqlquery = "SELECT *, SUM(CASE WHEN caumag < 98 THEN (quanti*operat) ELSE 0 END)AS rest FROM " . $gTables['movmag'] . "
          LEFT JOIN " . $gTables['lotmag'] . " ON " . $gTables['movmag'] . ".id_lotmag =" . $gTables['lotmag'] . ".id
          WHERE ". $add_where . "artico = '" . $codart . "' AND id_mov <> " . $excluded_movmag . " AND datdoc >= '". $first_lot_date ."'
    GROUP BY " . $gTables['movmag'] . ".id_lotmag
    ORDER BY " . $gTables['lotmag'] .".expiry" . $ob .", ". $gTables['lotmag'] . ".identifier" . $ob;
    $result = gaz_dbi_query($sqlquery);
    $acc = [];
    $rs = false;
    while ($row = gaz_dbi_fetch_array($result)) {
      if ($row['rest'] >= 0.00001 || ($negative>0 && $row['rest'] <0) || $all) { // l'articolo ha almeno un lotto caricato
        $rs = true;
        $acc[] = $row;
      }
    }
    $this->available = $acc;
    return $rs;
  }

  function getLotQty($id, $excluded_movmag = 0) {
    // Antonio Germani - restituisce la quantità disponibile di uno specifico lotto
    global $gTables;
    $sqlquery = "SELECT operat, quanti FROM " . $gTables['movmag'] . " WHERE id_lotmag = '" . $id . "' AND id_mov <> " . $excluded_movmag;
    $result = gaz_dbi_query($sqlquery);
    $lotqty=0;
    while ($row = gaz_dbi_fetch_array($result)) {
      if ($row['operat']>0){$lotqty=$lotqty+$row['quanti'];}
      if ($row['operat']<0){$lotqty=$lotqty-$row['quanti'];}
    }
    return $lotqty;
  }

   function divideLots($quantity) {
// riparto la quantità tra i vari lotti presenti se questi non sono sufficienti
// ritorno il resto non assegnato
      $acc = array();
      $rest = floatval($quantity);
      foreach ($this->available as $v) {
         if ($v['rest'] >= $rest) { // c'è capienza
            $acc[$v['id_lotmag']] = $v + array('qua' => $rest);
         } elseif ($v['rest'] < $rest) { // non c'è capienza
            $acc[$v['id_lotmag']] = $v + array('qua' => $v['rest']);
         }
         $rest -= $v['rest'];
      }
      $this->divided = $acc;
      if ($rest >= 0.00001) {
// ritorno il resto, quindi non ho abbastanza lotti per contenere la quantità venduta
         return $rest;
      } else {
         return NULL;
      }
   }

   function dispLotID ($codart, $lotMag, $excluded_movmag = 0) {
// Antonio Germani - restituisce la disponibilità per id lotto
		global $gTables;
		$query="SELECT SUM(quanti*operat) FROM ". $gTables['movmag'] . " WHERE artico='" .$codart. "' AND id_lotmag='" .$lotMag. "' AND id_mov <> '". $excluded_movmag ."' AND caumag < '99' ";
		$sum_in=gaz_dbi_query($query);
		$sum =gaz_dbi_fetch_array($sum_in);
		$disp = $sum['SUM(quanti*operat)'];
		return $disp;
   }

  function check_lot_exit ($idlot, $tesdoc='') {// Antonio Germani - restituisce TRUE se un id lotto è uscito dal magazzino almeno uno volta
  // se viene passato idlot faccio il controllo solo su questo specifico lotto ( non considero un eventuale tesdoc passato )
  // se viene passato tesdoc e idlot è nullo faccio il controllo su tutti gli articoli con lotto contenuti in quel documento
		global $gTables;
    if (intval($idlot)>0){
      $query="SELECT id_mov FROM ". $gTables['movmag'] . " WHERE ".$gTables['movmag'] . ".operat='-1' AND ".$gTables['movmag'] . ".id_lotmag='" .$idlot. "' LIMIT 1";
      $check=gaz_dbi_query($query);
      if ($check->num_rows>0){
        return TRUE;
      }
    }elseif (intval($tesdoc)>0){// prendo tutti i righi del tesdoc con un lotto di carico
      $query="SELECT id_mag, id_lotmag FROM ". $gTables['rigdoc'] . " LEFT JOIN ". $gTables['movmag'] ." ON ". $gTables['movmag'] .".id_rif = ". $gTables['rigdoc'] .".id_rig WHERE ". $gTables['rigdoc'] . ".id_tes = '". $tesdoc ."' AND ".$gTables['movmag'] . ".operat='1' AND ".$gTables['movmag'] . ".id_lotmag>'0'";
      $check_rows=gaz_dbi_query($query);
      while ($row = gaz_dbi_fetch_array($check_rows)){// ciclo i righi con articolo lotto
        $query="SELECT id_mov FROM ". $gTables['movmag'] . " WHERE ".$gTables['movmag'] . ".operat='-1' AND ".$gTables['movmag'] . ".id_lotmag='" .$row['id_lotmag']. "' LIMIT 1";
        $check=gaz_dbi_query($query);
        if ($check->num_rows>0){
          return TRUE;
        }
      }
    }
    return FALSE;
  }
}

?>
