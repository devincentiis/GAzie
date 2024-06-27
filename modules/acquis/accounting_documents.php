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
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
require("../../library/include/calsca.inc.php");
$msg=['err'=>[],'war'=>[]];

function getExtremeDocs($type = '_', $vat_section = 1, $date = false) {
    global $gTables;
    $type = substr($type, 0, 2);
    $docs = [];
    if ($date) {
        $date = ' AND datreg <= ' . $date;
    } else {
        $date = '';
    }
    $from = $gTables['tesdoc'];
    $where = "id_con = 0 AND seziva = $vat_section AND tipdoc LIKE '$type" . "_' $date";
    $orderby = "datreg ASC, protoc ASC";
    $result = gaz_dbi_dyn_query('*', $from, $where, $orderby, 0, 1);
    $row = gaz_dbi_fetch_array($result);
    if (!$row) $row=['protoc'=>'1','datreg'=>date("Y-m-d")];
    $docs['ini'] = array('proini' => $row['protoc'], 'date' => $row['datreg']);
    $row=false;
    $orderby = "datreg DESC, protoc DESC";
    $result = gaz_dbi_dyn_query('*', $from, $where, $orderby, 0, 1);
    $row = gaz_dbi_fetch_array($result);
    if (!$row) $row=['protoc'=>$docs['ini']['proini'],'datreg'=>$docs['ini']['date']];
    $docs['fin'] = array('profin' => $row['protoc'], 'date' => $row['datreg']);
    return $docs;
}

function getDocumentsAccounts($type = '___', $vat_section = 1, $date = false, $protoc = 999999999) {
  global $gTables, $admin_aziend;
  $calc = new Compute;
  $type = substr($type, 0, 2);
  if ($date) {
    $p = ' AND protoc <= ' . $protoc . ' AND YEAR(datreg) = ' . substr($date, 0, 4);
    $d = ' AND datreg <= ' . $date;
  } else {
    $d = '';
    $p = '';
  }
  $from = $gTables['tesdoc'] . ' AS tesdoc
	LEFT JOIN ' . $gTables['pagame'] . ' AS pay ON tesdoc.pagame=pay.codice
	LEFT JOIN ' . $gTables['clfoco'] . ' AS supplier ON tesdoc.clfoco=supplier.codice
	LEFT JOIN ' . $gTables['anagra'] . ' AS anagraf ON supplier.id_anagra=anagraf.id
	LEFT JOIN ' . $gTables['country'] . ' AS country ON anagraf.country=country.iso';
  $where = "id_con = 0 AND seziva = $vat_section AND tipdoc LIKE '$type" . "_' $d $p";
  $orderby = "datreg ASC, protoc ASC";
  $result = gaz_dbi_dyn_query('tesdoc.*,
                      pay.tippag,pay.numrat,pay.incaut,pay.tipdec,pay.giodec,pay.tiprat,pay.mesesc,pay.giosuc,pay.id_bank,
                      supplier.codice, supplier.speban AS addebitospese, supplier.operation_type,
					CONCAT(anagraf.ragso1,\' \',anagraf.ragso2) AS ragsoc,CONCAT(anagraf.citspe,\' (\',anagraf.prospe,\')\') AS citta, anagraf.country, anagraf.fiscal_reg,
					country.istat_area', $from, $where, $orderby);
  $doc = [];
	$docrows=[];
  $ctrlp = 0;
  $carry = 0;
  $ivasplitpay = 0;
  $somma_spese = 0;
  $totimpdoc = 0;
  $rit = 0;
	$classv='default';
  while ($tes = gaz_dbi_fetch_array($result)) {
    if ($tes['protoc'] <> $ctrlp) { // la prima testata della fattura
      // azzero l'accumulatore dei righi
      $docrows=[];
      $title='Modifica';
      $accpaymov=[];
      $accpaymov['no']='Partita riferita a questa Nota Credito';
      switch ($tes['tipdoc']) {
        case "AFA":
        $bol=$admin_aziend['taxstamp_account'];
        $classv='success';
        break;
        case "AFT":
        $bol=$admin_aziend['taxstamp_account'];
        $classv='success disabled';
        $title='Puoi editare solo i DdT relativi';
        break;
        case "AFC":
        $bol=$admin_aziend['taxstamp_account'];
        $classv='danger';
        // per le note credito è necessario l'intervento dell'utente per scegliere quale partita(fattura) verrà chiusa da essa quindi riprendo tutte le eventuali partite ancora aperte/scadute del fornitore
        $paymov = new Schedule();
        $paymov->getPartnerStatus($tes['clfoco']);
        foreach($paymov->PartnerStatus as $k0=>$v0){
          $vpm=0;
          $totf=0;
          foreach($v0 as $k1=>$v1){
            $totf+=$v1['op_val'];
            // accumulo solo se è aperta 0 o scaduta 3
            if($v1['status']==0||$v1['status']==3){$vpm+=($v1['op_val']-$v1['cl_val']);}
          }
          if($vpm>0){$accpaymov[$k0]=$v1['descri'].' € '.gaz_format_number($vpm).' residuo='.gaz_format_number($vpm);}
        }
        break;
        case "AFD":
        $bol=$admin_aziend['taxstamp_account'];
        $classv='info';
        break;
        default:
        $bol=$admin_aziend['boleff'];
        $classv='default';
        break;
      }
    if ($ctrlp > 0 && ($doc[$ctrlp]['tes']['stamp'] >= 0.01 || $doc[$ctrlp]['tes']['taxstamp'] >= 0.01 )) { // non è il primo ciclo faccio il calcolo dei bolli del pagamento e lo aggiungo ai castelletti
      $calc->payment_taxstamp($calc->total_imp + $calc->total_vat + $carry - $rit - $ivasplitpay + $taxstamp, $doc[$ctrlp]['tes']['stamp'], $doc[$ctrlp]['tes']['round_stamp'] * $doc[$ctrlp]['tes']['numrat']);
      $calc->add_value_to_VAT_castle($doc[$ctrlp]['vat'], $taxstamp + $calc->pay_taxstamp, $admin_aziend['taxstamp_vat']);
      $doc[$ctrlp]['vat'] = $calc->castle;
      // aggiungo il castelleto conti
      if (!isset($doc[$ctrlp]['acc'][$bol])) {
          $doc[$ctrlp]['acc'][$bol]['import'] = 0;
      }
      $doc[$ctrlp]['acc'][$bol]['import'] += $taxstamp + $calc->pay_taxstamp;
    }
    $carry = 0;
    $ivasplitpay = 0;
    $cast_vat = [];
    $cast_acc = [];
    $totroundcastle = 0;
    $roundcastle = [];
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
    $from = $gTables['rigdoc'] . ' AS rigdoc
                LEFT JOIN ' . $gTables['aliiva'] . ' AS aliiva
                ON rigdoc.codvat=aliiva.codice';
    $rs_rig = gaz_dbi_dyn_query('rigdoc.*,aliiva.tipiva AS tipiva', $from, "rigdoc.id_tes = " . $tes['id_tes'], "rigdoc.id_tes DESC");
    while ($r = gaz_dbi_fetch_array($rs_rig)) {
      if ($r['tiprig'] <= 1  || $r['tiprig'] == 4 || $r['tiprig'] == 90) { // se del tipo normale, forfait, cassa previdenziale, vendita cespite
        //calcolo importo rigo
        $importo = CalcolaImportoRigo($r['quanti'], $r['prelis'], array($r['sconto'], $tes['sconto']));
        if ($r['tiprig']==1||$r['tiprig']== 90) { // se di tipo forfait e vendita cespite
            $importo = CalcolaImportoRigo(1, $r['prelis'], $tes['sconto']);
        } elseif($r['tiprig']==4){ // cassa previdenziale sul database  trovo la percentuale sulla colonna provvigione
            $importo = round($r['prelis']*$r['provvigione']/100,2);
        }
        //creo il castelletto IVA
        if (!isset($cast_vat[$r['codvat']]['impcast'])) {
          $cast_vat[$r['codvat']]['impcast'] = 0;
          $cast_vat[$r['codvat']]['ivacast'] = 0;
          $cast_vat[$r['codvat']]['periva'] = $r['pervat'];
          $cast_vat[$r['codvat']]['tipiva'] = $r['tipiva'];
          $cast_vat[$r['codvat']]['impneg'] = 0;
          $cast_vat[$r['codvat']]['ivaneg'] = 0;
          $cast_vat[$r['codvat']]['roundcastle'] = 0;
        }
        if (FALSE && $importo<0.00) {
          $cast_vat[$r['codvat']]['impneg'] += $importo;
          $cast_vat[$r['codvat']]['ivaneg'] += round(($importo * $r['pervat']) / 100, 2);
        } else {
          $cast_vat[$r['codvat']]['impcast'] += $importo;
          $cast_vat[$r['codvat']]['ivacast'] += round(($importo * $r['pervat']) / 100, 2);
        }
        $totimpdoc += $importo;
        //creo il castelletto conti
        if (!isset($cast_acc[$r['codric']]['import'])) {
            $cast_acc[$r['codric']]['import'] = 0;
            $cast_acc[$r['codric']]['accneg'] = 0;
        }
        if (FALSE && $importo<0.00) {
            $cast_acc[$r['codric']]['accneg'] += $importo;
        } else {
          $cast_acc[$r['codric']]['import'] += $importo;
        }
        if ($r['status'] == 'ASS10') { // se è un rigo di incremento vendita cespite lo indico sull'array dei conti, ASS10 in status l'ho indicato con acquire_invoice quando ho chiesto di incrementare il valore del bene
          $cast_acc[$r['codric']]['type_mov'] = 10;
        }
        $rit += round($importo * $r['ritenuta'] / 100, 2);
        // aggiungo all'accumulatore l'eventuale iva non esigibile (split payment)
        if ($r['tipiva'] == 'T') {
            $ivasplitpay += round(($importo * $r['pervat']) / 100, 2);
        }
      } elseif ($r['tiprig'] == 3) {
        $carry += $r['prelis'];
      } elseif ($r['tiprig'] == 91) {
        $roundcastle[$r['codvat']] = $r['prelis'];
        $totroundcastle += $r['prelis'];
      }
   		$docrows[]=$r; // i righi mi serviranno per creare una autofattura "clone" ma con IVA valorizzata
    }
    // la presenza di scadenze provenienti dall'XML mi crea l'array che valorizzerà paymov
    $rspm = gaz_dbi_dyn_query('*', $gTables['expdoc'], " id_tes = " . $tes['id_tes']);
    $dtfa = new DateTime($tes['datfat']);
    while ($r = gaz_dbi_fetch_array($rspm)) {
      $doc[$tes['protoc']]['pay'][] = $r;
      $dtex = new DateTime($r['DataScadenzaPagamento']);
      if ($r['ModalitaPagamento'] == 'MP01' && $dtex <= $dtfa ) { // se ho una ModalitaPagamento contanti (MP01) e la scadenza coincide con la data della fattura non apro la partita e faccio la chiusura automatica per cassa
        $contanti = gaz_dbi_get_row($gTables['pagame'], 'fae_mode', 'MP01','AND incaut > 100000000');
        $tes['incaut']=($contanti)?$contanti['incaut']:0;
      }
    }
    $doc[$tes['protoc']]['docrows'] = $docrows;
    $doc[$tes['protoc']]['accpaymov'] = $accpaymov;
    $doc[$tes['protoc']]['title'] = $title;
    $doc[$tes['protoc']]['classv'] = $classv;
    $doc[$tes['protoc']]['tes'] = $tes;
    $doc[$tes['protoc']]['acc'] = $cast_acc;
    $doc[$tes['protoc']]['totroundcastle'] = $totroundcastle;
    $doc[$tes['protoc']]['car'] = $carry;
    $doc[$tes['protoc']]['isp'] = $ivasplitpay;
    $doc[$tes['protoc']]['rit'] = $rit;
    $somma_spese += $tes['traspo'] + $spese_incasso + $tes['spevar'];
    $calc->add_value_to_VAT_castle($cast_vat, $somma_spese, $tes['expense_vat']);
    if (count($roundcastle)>=1){ // ci sono stati dei tiprig = 91 per arrotondamenti IVA su castelletto
      $calc->round_VAT_castle($calc->castle,$roundcastle);
    }
    if (count($calc->castle)==0){
        $vat = gaz_dbi_get_row($gTables['aliiva'], 'codice', $admin_aziend['preeminent_vat']);
        $vat['periva']=$vat['aliquo'];
        $vat['impcast']=0;
        $vat['imponi']=0;
        $vat['impneg']=0;
        $vat['ivacast']=0;
        $doc[$tes['protoc']]['vat']=array($vat['codice']=>$vat);
    } else {
        $doc[$tes['protoc']]['vat'] = $calc->castle;
    }
    $ctrlp = $tes['protoc'];
  }

  if ((!empty($doc[$ctrlp]) && $doc[$ctrlp]['tes']['stamp']>=0.01) || (!empty($taxstamp) && $taxstamp>=0.01)) { // a chiusura dei cicli faccio il calcolo dei bolli del pagamento e lo aggiungo ai castelletti
      $calc->payment_taxstamp($calc->total_imp + $calc->total_vat + $carry - $rit - $ivasplitpay + $taxstamp, $doc[$ctrlp]['tes']['stamp'], $doc[$ctrlp]['tes']['round_stamp'] * $doc[$ctrlp]['tes']['numrat']);
      // aggiungo al castelletto IVA
      $calc->add_value_to_VAT_castle($doc[$ctrlp]['vat'], $taxstamp + $calc->pay_taxstamp, $admin_aziend['taxstamp_vat']);
      $doc[$ctrlp]['vat'] = $calc->castle;
      // aggiungo il castelletto conti
      if (!isset($doc[$ctrlp]['acc'][$bol])) {
          $doc[$ctrlp]['acc'][$bol]['import'] = 0;
      }
      $doc[$ctrlp]['acc'][$bol]['import'] += $taxstamp + $calc->pay_taxstamp;
  }
  return $doc;
}

function computeTot($data) {
  $tax = 0;
  $vat = 0;
  foreach ($data as $k => $v) {
    if (!empty($vv['impneg'])) {
      $tax += $v['impcast']+$v['impneg'];
      $vat += round(($v['impcast']+$v['impneg']) * $v['periva']) / 100;
    } else {
      $tax += $v['impcast'];
      $vat += $v['ivacast'];
    }
  }
  $tot = $vat + $tax;
  return array('taxable' => $tax, 'vat' => $vat, 'tot' => $tot);
}

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    if (isset($_GET['type'])) {
        $form['type'] = substr($_GET['type'], 0, 2);
    } else {
        $form['type'] = 'F';
    }
    if (isset($_GET['vat_section'])) {
        $form['vat_section'] = intval($_GET['vat_section']);
    } else {
        $form['vat_section'] = 1;
    }
    $extreme = getExtremeDocs($form['type'], $form['vat_section']);
    if ($extreme['ini']['proini'] > 0) {
      $form['this_date_Y'] = substr($extreme['fin']['date'], 0, 4);
      $form['this_date_M'] = substr($extreme['fin']['date'], 5, 2);
      $form['this_date_D'] = substr($extreme['fin']['date'], 8, 2);
    } else if (isset($_GET['datreg'])) {
      $form['this_date_Y'] = substr($_GET['datreg'], 0, 4);
      $form['this_date_M'] = substr($_GET['datreg'], 4, 2);
      $form['this_date_D'] = substr($_GET['datreg'], 6, 2);
    }  else {
      $form['this_date_Y'] = date("Y");
      $form['this_date_M'] = date("m");
      $form['this_date_D'] = date("d");
    }
    $form['proini'] = $extreme['ini']['proini'];
    $form['profin'] = (isset($_GET['last'])) ? intval($_GET['last']) : $extreme['fin']['profin'];
    $form['year_ini'] = substr($extreme['ini']['date'], 0, 4);
    $form['year_fin'] = substr($extreme['fin']['date'], 0, 4);
    $form['hidden_req'] = '';
    $rs = getDocumentsAccounts($form['type'], $form['vat_section'], $form['this_date_Y'].$form['this_date_M'].$form['this_date_D'] , $form['profin']);
} else {    // accessi successivi
  $form['type'] = substr($_POST['type'], 0, 2);
  $form['vat_section'] = intval($_POST['vat_section']);
  $form['this_date_Y'] = intval($_POST['this_date_Y']);
  $form['this_date_M'] = intval($_POST['this_date_M']);
  $form['this_date_D'] = intval($_POST['this_date_D']);
  $form['proini'] = intval($_POST['proini']);
  $form['profin'] = intval($_POST['profin']);
  $form['year_ini'] = intval($_POST['year_ini']);
  $form['year_fin'] = intval($_POST['this_date_Y']);
  if (isset($_POST['accpaymov'])) {
    $paymoverr=false;
    foreach($_POST['accpaymov']as$prot=>$v){
      // in $v ho il id_tesdoc_ref selezionato;
      $form["accpaymov_$prot"] = $v;
      $form['accpaymov'][$prot]= $v;
      // controllo se le partite di scadenzario delle note credito sono state tutte selezionate
      if($v<2&&$v!='no'){$paymoverr=true;}
    }
    if($paymoverr){$msg['err'][]="nopaymov";}
  }
  $form['hidden_req'] = htmlentities($_POST['hidden_req']);
  if (!checkdate($form['this_date_M'], $form['this_date_D'], $form['this_date_Y'])) $msg['err'][]="date";
  if ($form['hidden_req'] == 'type' || $form['hidden_req'] == 'vat_section') {   //se cambio il registro
    $extreme = getExtremeDocs($form['type'], $form['vat_section']);
    if ($extreme['ini']['proini'] > 0) {
      $form['this_date_Y'] = substr($extreme['fin']['date'], 0, 4);
      $form['this_date_M'] = substr($extreme['fin']['date'], 5, 2);
      $form['this_date_D'] = substr($extreme['fin']['date'], 8, 2);
    } else {
      $form['this_date_Y'] = date("Y");
      $form['this_date_M'] = date("m");
      $form['this_date_D'] = date("d");
    }
    $form['proini'] = $extreme['ini']['proini'];
    $form['profin'] = $extreme['fin']['profin'];
    $form['year_ini'] = substr($extreme['ini']['date'], 0, 4);
    $form['year_fin'] = substr($extreme['fin']['date'], 0, 4);
  }
  $form['hidden_req'] = '';
  $uts = new DateTime('@'.mktime(12,0,0,$form['this_date_M'],$form['this_date_D'],$form['this_date_Y']));
	$rs = getDocumentsAccounts($form['type'], $form['vat_section'], $uts->format('Ymd'), $form['profin']);

  if (isset($_POST['gosubmit']) && count($msg['err'])==0) {   //confermo la contabilizzazione
    if (!empty($rs) && count($rs)>0) {
        require("lang." . $admin_aziend['lang'] . ".php");
        $script_transl = $strScript['accounting_documents.php'];
        foreach ($rs as $k => $v) {
          switch ($v['tes']['tipdoc']) {
            case "FAD":case "FAI":case "FAP":case "FND":case "FAA":case "FAF":
              $reg = 2;
              $op = 1;
              $da_c = 'A';
              $da_p = 'D';
              $kac = $admin_aziend['ivaven'];
              $krit = $admin_aziend['c_ritenute'];
            break;
            case "FNC":$reg = 2;
              $op = 2;
              $da_c = 'D';
              $da_p = 'A';
              $kac = $admin_aziend['ivaven'];
              $krit = $admin_aziend['c_ritenute'];
            break;
            case "VCO":$reg = 4;
              $op = 1;
              $da_c = 'A';
              $da_p = 'D';
              $kac = $admin_aziend['ivacor'];
              $krit = $admin_aziend['c_ritenute'];
            break;
            case "VRI":$reg = 4;
              $op = 1;
              $da_c = 'A';
              $da_p = 'D';
              $kac = $admin_aziend['ivacor'];
              $krit = $admin_aziend['c_ritenute'];
            break;
            case "AFA":case "AFT":$reg = 6;
              $op = 1;
              $da_c = 'D';
              $da_p = 'A';
              $kac = $admin_aziend['ivaacq'];
              $krit = $admin_aziend['c_ritenute_autonomi'];
            break;
              case 'AFC':$reg = 6;
                $op = 2;
                $da_c = 'A';
                $da_p = 'D';
                $kac = $admin_aziend['ivaacq'];
                $krit = $admin_aziend['c_ritenute_autonomi'];
              break;
              case 'AFD':$reg = 6;
                $op = 1;
                $da_c = 'D';
                $da_p = 'A';
                $kac = $admin_aziend['ivaacq'];
                $krit = $admin_aziend['c_ritenute_autonomi'];
              break;
              default:$reg = 0;
                $op = 0;
              break;
          }
          $tot = computeTot($v['vat']);
          // fine calcolo totali
          // inserisco la testata
          $newValue = array('caucon' => $v['tes']['tipdoc'],
              'descri' => $script_transl['doc_type_value'][$v['tes']['tipdoc']].' n.'.$v['tes']['numfat'],
              'id_doc' => $v['tes']['id_tes'],
              'datreg' => $v['tes']['datreg'],
              'seziva' => $v['tes']['seziva'],
              'protoc' => $v['tes']['protoc'],
              'numdoc' => $v['tes']['numfat'],
              'datdoc' => $v['tes']['datfat'],
              'clfoco' => $v['tes']['clfoco'],
              'regiva' => $reg,
              'operat' => $op
          );
          // controllo le date per inserire la giusta competenza di liquidazione
          $dr = new DateTime($v['tes']['datreg']);
          $df = new DateTime($v['tes']['datfat']);
          $diff = $df->diff($dr);
          if (($dr->format('Y')-$df->format('Y'))>0){ //  ATTENZIONE !!! Non sono sicuro se fa bene: è saltato l'anno	può essere liquidato in dichiarazione IVA ovvero nella liquidazione	del mese di ricevimento
            $newValue['datliq']=$v['tes']['datreg'];
          } elseif (($dr->format('m')-$df->format('m'))==1&&$diff->format('d')<=15){ // è saltato il mese e siamo nei primi quindici giorni
            $df->modify('last day of this month');
            $newValue['datliq']=$df->format('Y-m-d');
          } else {
            $newValue['datliq']=$v['tes']['datreg'];
          }
          $datliq=$newValue['datliq'];
          $tes_id =tesmovInsert($newValue);
          //inserisco i righi iva nel db
          $acc_reverse_charge=[];
          $imp_reverse_charge=0.00;
          $iva_reverse_charge=0.00;
          foreach ($v['vat'] as $k => $vv) {
            $vat = gaz_dbi_get_row($gTables['aliiva'], 'codice', $vv['codiva']);
            //aggiungo i valori mancanti all'array
            $vv['operation_type'] = $vat['operation_type'];
            $vv['descri_vat'] = $vat['descri'];
            $vv['id_tes'] = $tes_id;
            $vv['impost'] = round($vv['ivacast'],2);
            $iva_id = rigmoiInsert($vv);
            if (substr($vv['fae_natura'],0,2)=='N6' || $v['tes']['fiscal_reg'] == 'RF34' ) { // accumulo su matrice le aliquote che produrranno reverse charge usando come chiave l'id_rig del rigmoi appena inserito
              $vv['tesmov_id'] = $tes_id;
              $acc_reverse_charge[$iva_id] = $vv;
              $imp_reverse_charge += $vv['imponi'];
              $iva_reverse_charge += $vv['impost'];
            }
            if (!empty($vv['impneg']) && $vv['impneg']<0.00) {	// se ho un valore negativo sulla stessa aliquota creo uno storno
              $vv['impost'] = round($vv['impneg'] * $vv['periva']) / 100;
              $vv['imponi'] = $vv['impneg'];
              $iva_id = rigmoiInsert($vv);
              if (substr($vv['fae_natura'],0,3)=='N6.' || $v['tes']['fiscal_reg'] == 'RF34') {
                  $vv['tesmov_id'] = $tes_id;
                  $acc_reverse_charge[$iva_id] = $vv;
                  $imp_reverse_charge -= $vv['imponi'];
                  $iva_reverse_charge -= $vv['impost'];
              }
            }
          }
          $tot_reverse_charge = $imp_reverse_charge + $iva_reverse_charge;
          // calcolo le rate
          $rate = CalcolaScadenze($tot['tot'] - $v['rit'] - $iva_reverse_charge, substr($v['tes']['datfat'], 8, 2), substr($v['tes']['datfat'], 5, 2), substr($v['tes']['datfat'], 0, 4), $v['tes']['tipdec'], $v['tes']['giodec'], $v['tes']['numrat'], $v['tes']['tiprat'], $v['tes']['mesesc'], $v['tes']['giosuc']);
          // rateizzo anche l'iva split payment
          $rateisp = CalcolaScadenze($v['isp'], substr($v['tes']['datfat'], 8, 2), substr($v['tes']['datfat'], 5, 2), substr($v['tes']['datfat'], 0, 4), $v['tes']['tipdec'], $v['tes']['giodec'], $v['tes']['numrat'], $v['tes']['tiprat'], $v['tes']['mesesc'], $v['tes']['giosuc']);
          if ($tot['tot']>=0.01){
            $paymov_id =rigmocInsert(array('id_tes'=>$tes_id,'darave'=>$da_p,'codcon' =>$v['tes']['clfoco'],'import' =>($tot['tot'] - $v['rit'])));
          } elseif ($tot['tot']<=-0.01) {
            $paymov_id =rigmocInsert(array('id_tes'=>$tes_id,'darave'=>$da_c,'codcon' =>$v['tes']['clfoco'],'import' =>(-$tot['tot'] + $v['rit'])));
          }
          foreach ($v['acc'] as $acc_k => $acc_v) {
              if ($acc_v['import'] != 0) {
                if (isset($acc_v['type_mov'])) { // qui eseguo le registrazioni relative all'acquisto per incremento del valore del cespite inserendo anche i righi sulla tabella gaz_001azzets type_mov = 10
                  $asset = gaz_dbi_get_row($gTables['assets'], 'acc_fixed_assets', $acc_k, "AND type_mov = 1"); // riprendo l'asset
                  if ($asset) {
                    unset($asset['id']);
                    $asset['id_movcon'] = $tes_id;
                    $asset['type_mov'] = 10;
                    $asset['descri'] = 'FATTURA DI ACQUISTO '. $v['tes']['numfat'] . ' del ' . gaz_format_date($v['tes']['datfat']);
                    $asset['quantity'] = 1;
                    $asset['unimis'] = ''; // non lo conosco
                    $asset['pagame'] = $v['tes']['pagame'];
                    $asset['a_value'] = $acc_v['import'];
                    gaz_dbi_table_insert('assets', $asset);
                    // faccio l'update del riferimento sui righi IVA già inseriti indicando che riguarda l'incremento di valore di un bene ammortizzabile
                    gaz_dbi_put_row($gTables['rigmoi'], 'id_tes', $tes_id, 'operation_type', 'BENAMM');
                  }
                }
                $dacost=$da_c;
                if($acc_v['import']<0.00) {
                    $dacost = ($da_c=='A')?'D':'A';
                    $acc_v['import']=abs($acc_v['import']);
                }
                rigmocInsert(array('id_tes' => $tes_id, 'darave' => $dacost, 'codcon' => $acc_k, 'import' => $acc_v['import']));
                if ($acc_v['accneg']<0.00){ // ho dei righi negativi riferiti allo stesso conto
                  rigmocInsert(array('id_tes' => $tes_id, 'darave' => $da_p, 'codcon' => $acc_k, 'import' => -$acc_v['accneg']));
                }
              }
          }
            if ($tot['vat']>=0.01) {
              rigmocInsert(array('id_tes' => $tes_id, 'darave' => $da_c, 'codcon' => $kac, 'import' => $tot['vat']));
            } elseif ($tot['vat']<=-0.01) {
              rigmocInsert(array('id_tes' => $tes_id, 'darave' => $da_p, 'codcon' => $kac, 'import' => -$tot['vat']));
            }
            if ($v['rit'] > 0) {  // se ho una ritenuta d'acconto
                rigmocInsert(array('id_tes' => $tes_id, 'darave' => $da_p, 'codcon' => $krit, 'import' => $v['rit']));
            }
            if ($v['tes']['incaut'] > 100000000 ) {  // se il pagamento prevede l'incasso automatico o sul tracciato XML avevo ModalitaPagamento=MP01
                rigmocInsert(array('id_tes' => $tes_id, 'darave' => $da_c, 'codcon' => $v['tes']['clfoco'], 'import' => ($tot['tot'] - $v['rit'] - $iva_reverse_charge)));
                rigmocInsert(array('id_tes' => $tes_id, 'darave' => $da_p, 'codcon' => $v['tes']['incaut'], 'import' => ($tot['tot'] - $v['rit'] - $iva_reverse_charge)));
            } else { // altrimenti inserisco le partite aperte
              if (isset($v['pay'])&&count($v['pay'])>0){ // se ho i dati provenienti dal XML li uso
                foreach ($v['pay'] as $v_pay) {
                    // preparo l'array da inserire sui movimenti delle partite aperte
                    $paymov_value = array('id_tesdoc_ref' => substr($v['tes']['datreg'], 0, 4) . $reg . $v['tes']['seziva'] . str_pad($v['tes']['protoc'], 9, 0, STR_PAD_LEFT),
                        'id_rigmoc_doc' => $paymov_id,
                        'amount' => round($v_pay['ImportoPagamento'],2),
                        'expiry' => $v_pay['DataScadenzaPagamento']);
                    if ($op == 2) { // le note credito sono assimilabili ad un pagamento, ovvero ad una chiusura di partita pertanto modifico l'array prima di passarlo
                        unset($paymov_value['id_rigmoc_doc']);
                        $paymov_value['id_rigmoc_pay'] = $paymov_id;
					if (count($v['accpaymov'])>1 && isset($form["accpaymov_".$v['tes']['protoc']]) && $form["accpaymov_".$v['tes']['protoc']]!='no') { $paymov_value['id_tesdoc_ref'] = $form["accpaymov_".$v['tes']['protoc']];}
                    }
                    paymovInsert($paymov_value);
                }
              } else { // ... altrimenti uso le scadenze del metodo di pagamento del fornitore
                foreach ($rate['import'] as $k_rate => $v_rate) {
                    // preparo l'array da inserire sui movimenti delle partite aperte
                    $paymov_value = array('id_tesdoc_ref' => substr($v['tes']['datreg'], 0, 4) . $reg . $v['tes']['seziva'] . str_pad($v['tes']['protoc'], 9, 0, STR_PAD_LEFT),
                        'id_rigmoc_doc' => $paymov_id,
                        'amount' => $v_rate,
                        'expiry' => $rate['anno'][$k_rate] . '-' . $rate['mese'][$k_rate] . '-' . $rate['giorno'][$k_rate]);
                    if ($op == 2) { // le note credito sono assimilabili ad un pagamento, ovvero ad una chiusura di partita pertanto modifico l'array prima di passarlo
                        unset($paymov_value['id_rigmoc_doc']);
                        $paymov_value['id_rigmoc_pay'] = $paymov_id;
					if (count($v['accpaymov'])>1 && isset($form["accpaymov_".$v['tes']['protoc']]) && $form["accpaymov_".$v['tes']['protoc']]!='no') { $paymov_value['id_tesdoc_ref'] = $form["accpaymov_".$v['tes']['protoc']];}
                    }
                    paymovInsert($paymov_value);
                }
              }
            }
            // alla fine modifico le testate documenti introducendo il numero del movimento contabile
            gaz_dbi_put_query($gTables['tesdoc'], "tipdoc = '" . $v['tes']['tipdoc'] . "' AND datfat = '" . $v['tes']['datfat'] . "' AND seziva = " . $v['tes']['seziva'] . " AND protoc = " . $v['tes']['protoc'], "id_con", $tes_id);
            // movimenti di storno in caso di split payment
            if ($v['isp'] > 0) {
                // inserisco la testata del movimento di storno Split payment
                $newValue = array('caucon' => 'ISP',
                    'descri' => 'STORNO IVA SPLIT PAYMENT',
                    'id_doc' => $v['tes']['id_tes'],
                    'datreg' => $v['tes']['datreg'],
                    'seziva' => $v['tes']['seziva'],
                    'protoc' => $v['tes']['protoc'],
                    'numdoc' => $v['tes']['numfat'],
                    'datdoc' => $v['tes']['datfat'],
                    'clfoco' => $v['tes']['clfoco'],
                    'regiva' => '',
                    'operat' => ''
                );
                $tes_id =tesmovInsert($newValue);
                rigmocInsert(array('id_tes' => $tes_id, 'darave' => $da_p, 'codcon' => $kac, 'import' => $v['isp']));
                rigmocInsert(array('id_tes' => $tes_id, 'darave' => $da_c, 'codcon' => $admin_aziend['split_payment'], 'import' => $v['isp']));
                rigmocInsert(array('id_tes' => $tes_id, 'darave' => $da_p, 'codcon' => $admin_aziend['split_payment'], 'import' => $v['isp']));
                $paymov_id =rigmocInsert(array('id_tes' => $tes_id, 'darave' => $da_c, 'codcon' => $v['tes']['clfoco'], 'import' => $v['isp']));
                // chiudo le partite aperte dell'iva split payment
                foreach ($rateisp['import'] as $k_rate => $v_rate) { // preparo l'array da inserire sui movimenti delle partite aperte
                    $paymov_value = array('id_tesdoc_ref' => substr($v['tes']['datreg'], 0, 4) . $reg . $v['tes']['seziva'] . str_pad($v['tes']['protoc'], 9, 0, STR_PAD_LEFT),
                        'id_rigmoc_pay' => $paymov_id,
                        'amount' => $v_rate,
                        'expiry' => $rate['anno'][$k_rate] . '-' . $rate['mese'][$k_rate] . '-' . $rate['giorno'][$k_rate]);
					if (count($v['accpaymov'])>1 && isset($form["accpaymov_".$v['tes']['protoc']]) && $form["accpaymov_".$v['tes']['protoc']]!='no') { $paymov_value['id_tesdoc_ref'] = $form["accpaymov_".$v['tes']['protoc']];}
                    paymovInsert($paymov_value);
                }
            }
    if ( abs($tot_reverse_charge) >= 0.01 ) {
      // ho accumulato un reverse charge creo un movimento contabile e IVA per documento di vendita sul sezionale scelto in configurazione azienda, entro il 2023 inserirò da qui anche i dati in gaz_NNNtesdoc e rigdoc per poter generare il relativo XML da trasmette all'AdE

      // per prima cosa dovrò controllare se c'è il cliente con la stessa anagrafica
      $anagrafica = new Anagrafica();
      $partner = $anagrafica->getPartner($v['tes']['clfoco']);
      $rc_cli = gaz_dbi_get_row($gTables['clfoco'], "codice LIKE '" . $admin_aziend['mascli'] . "%' AND id_anagra ", $partner['id']);
      $fiscal_rapresentative_country=false;
      if ($rc_cli) { // ho già il cliente
        if ($partner['fiscal_rapresentative_id']>0) { // ho il rappresentante fiscale in italia
          $fiscal_rapresentative_country = gaz_dbi_get_row($gTables['anagra'], "id", $partner['fiscal_rapresentative_id'])['country'];
        }
      } else { // non ho il cliente lo dovrò creare sul piano dei conti
          $new_cli = $anagrafica->getPartnerData($partner['id']);
          $rc_cli['codice'] = $anagrafica->anagra_to_clfoco($new_cli, $admin_aziend['mascli'],$v['tes']['pagame']);
      }
      // inserisco la testata del movimento di storno reverse charge
      $rs_ultimo_protoc = gaz_dbi_dyn_query("*", $gTables['tesmov'], "YEAR(datreg) = ".substr($v['tes']['datreg'],0,4)." AND regiva = 2 AND seziva = ".$admin_aziend['reverse_charge_sez'],  "protoc DESC", 0, 1);
      $ultimo_protoc = gaz_dbi_fetch_array($rs_ultimo_protoc);
      $protoc = 1;
      if ($ultimo_protoc) {
          $protoc = $ultimo_protoc['protoc']+1;
      }
			if ($v['tes']['tipdoc'] == 'AFC') {
				$newValue = array('caucon' => 'FNC',
					'descri' => 'NOTA CREDITO REVERSE CHARGE',
					'id_doc' => $v['tes']['id_tes'],
					'datreg' => $v['tes']['datreg'],
					'datliq' => $datliq,
					'seziva' => $admin_aziend['reverse_charge_sez'],
					'protoc' => $protoc,
					'numdoc' => $v['tes']['numfat'],
					'datdoc' => $v['tes']['datfat'],
					'clfoco' => $rc_cli['codice'],
					'regiva' => 2,
					'operat' => 2
				);
			} else {
				$newValue = array('caucon' => 'FAI',
					'descri' => 'FATTURA REVERSE CHARGE',
					'id_doc' => $v['tes']['id_tes'],
					'datreg' => $v['tes']['datreg'],
					'datliq' => $datliq,
					'seziva' => $admin_aziend['reverse_charge_sez'],
					'protoc' => $protoc,
					'numdoc' => $v['tes']['numfat'],
					'datdoc' => $v['tes']['datfat'],
					'clfoco' => $rc_cli['codice'],
					'regiva' => 2,
					'operat' => 1
				);
			}
      $rctes_id =tesmovInsert($newValue);
			// inserisco un documento fittizio in tesdoc al fine di generare un XML dal registro con il sezionale (normalmente 9) del Reverse Charge
			// stabilisco il tipo di documento per lo SdI (TD16,TD17,TD18,TD19,TD20) e lo insterisco sulla colonna status di tesdoc
			$status='TD16'; // operazioni interne (italiani)
			if ($v['tes']['country']<>'IT') {
				$status='TD17'; // acquisto servizi dall'estero
        if ($vv['operation_type']<>'SERVIZ'&& $v['tes']['istat_area']==11) {
          $status='TD18';
        }
        // se il fornitore ha una partita IVA italiana pur essendo straniero diventa TD19
        require_once("../../library/include/check.inc.php");
        $cf_pi = new check_VATno_TAXcode();
        $r_pi = $cf_pi->check_VAT_reg_no($partner['pariva'], 'IT');
        if (empty($r_pi)) {
          $status='TD19';
        }
			} else if ($v['tes']['fiscal_reg'] == 'RF34') {
        $status='TD01';
      }
			$tesdocVal = ['tipdoc' => 'XFA',
				'template' => 'FatturaAcquisto',
				'id_con' => $rctes_id,
				'datreg' => $v['tes']['datreg'],
				'seziva' => $admin_aziend['reverse_charge_sez'],
				'protoc' => $protoc,
				'numdoc' => $protoc, // nelle autofatture utilizzo il numero di protocollo del sezionale al fine di avere sequezialità, il numero reale dato dal fornitore è scritto sulla descrizione del rigo
				'numfat' => $v['tes']['numfat'],
				'datemi' => $v['tes']['datfat'],
				'datfat' => $v['tes']['datfat'],
				'initra' => $v['tes']['datfat'],
				'clfoco' => $v['tes']['clfoco'],
				'pagame' => $v['tes']['pagame'],
				'regiva' => 2,
				'operat' => 1,
				'status' => $status
			];

			if ($v['tes']['tipdoc'] == 'AFC') {
				$tesdocVal['tipdoc'] = 'XNC';
				$tesdocVal['operat'] = 2;
			}
			$last_id_tes_tesdoc=tesdocInsert($tesdocVal);

			$rigdocVal = ['id_tes'=> $last_id_tes_tesdoc];
      $rigdocVal['descri']=(isset($rigdocVal['descri']))?$rigdocVal['descri']:'';
			$rigdocVal['descri'] .= 'ACQUISTO n.'.$v['tes']['numfat'].' del '.gaz_format_date($v['tes']['datfat']);
      // inserisco i righi IVA
      $acc_iva=0.00;
      $acc_imp=0.00;
      foreach( $acc_reverse_charge as $krc=>$vrc ) {
        $acc_iva+=$vrc['impost'];
        $acc_imp+=$vrc['impcast'];
        // vado ad indicare l'id del movimento padre sul rigo iva
        $vrc['reverse_charge_idtes'] = $vrc['tesmov_id'];
        $vrc['id_tes'] = $rctes_id;
        rigmoiInsert($vrc);
        /*
        // sul documento inserisco un rigo per ogni aliquota riportante il totale imponibile del Reverse Charge
        $rigdocVal['descri'] .= ' '.$vrc['descri_vat'];
        $rigdocVal['codvat'] = $vrc['codiva'];
        $rigdocVal['prelis'] = $v['tes']['tipdoc']=='AFC'?-abs($vrc['impcast']):$vrc['impcast'];
        $rigdocVal['pervat'] = $vrc['periva'];*/
      }
      foreach ($v['docrows'] as $kdr => $vdr) {
        $rigdocVal['tiprig'] = $vdr['tiprig'];
        $rigdocVal['descri'] = $vdr['descri'];
        $rigdocVal['quanti'] = $vdr['quanti'];
        $rigdocVal['unimis'] = $vdr['unimis'];
        $rigdocVal['codvat'] = $vdr['codvat'];
        $rigdocVal['prelis'] = $v['tes']['tipdoc']=='AFC'?-abs($vdr['prelis']):$vdr['prelis'];
        $rigdocVal['sconto'] = $vdr['sconto'];
        $rigdocVal['pervat'] = $vdr['pervat'];
        rigdocInsert($rigdocVal);
      }

			if ($v['tes']['tipdoc'] == 'AFC') {
				// inserisco i tre righi contabili della fattura che vanno sul registro IVA vendite
				rigmocInsert(array('id_tes' => $rctes_id, 'darave' => 'A', 'codcon' => $rc_cli['codice'], 'import' => $acc_imp + $acc_iva));
				rigmocInsert(array('id_tes' => $rctes_id, 'darave' => 'D', 'codcon' => $rc_cli['codice'], 'import' => $acc_imp));
				rigmocInsert(array('id_tes' => $rctes_id, 'darave' => 'D', 'codcon' => $admin_aziend['ivaven'], 'import' => $acc_iva));

				// infine creo un movimento di storno dell'IVA
				rigmocInsert(array('id_tes' => $rctes_id, 'darave' => 'A', 'codcon' => $v['tes']['clfoco'], 'import' => $acc_iva));
				rigmocInsert(array('id_tes' => $rctes_id, 'darave' => 'D', 'codcon' => $rc_cli['codice'], 'import' => $acc_iva));
			} else {
				// inserisco i tre righi contabili della fattura che vanno sul registro IVA vendite
				rigmocInsert(array('id_tes' => $rctes_id, 'darave' => 'D', 'codcon' => $rc_cli['codice'], 'import' => $acc_imp + $acc_iva));
				rigmocInsert(array('id_tes' => $rctes_id, 'darave' => 'A', 'codcon' => $rc_cli['codice'], 'import' => $acc_imp));
				rigmocInsert(array('id_tes' => $rctes_id, 'darave' => 'A', 'codcon' => $admin_aziend['ivaven'], 'import' => $acc_iva));

				// infine creo un movimento di storno dell'IVA
				rigmocInsert(array('id_tes' => $rctes_id, 'darave' => 'D', 'codcon' => $v['tes']['clfoco'], 'import' => $acc_iva));
				rigmocInsert(array('id_tes' => $rctes_id, 'darave' => 'A', 'codcon' => $rc_cli['codice'], 'import' => $acc_iva));
			}
      // faccio l'update del riferimento sui righi inseriti per il movimento padre
      gaz_dbi_put_row($gTables['rigmoi'], 'id_tes', $tes_id, 'reverse_charge_idtes', $rctes_id);
    }

    }
      if ($form['type'] == 'AF') {
				header("Location: ../../modules/acquis/report_docacq.php");
 			} else {
				header("Location: ../../modules/vendit/report_docven.php");
 			}
      exit;
    } else {
      $msg['err'][]="nodoc";
    }
  }
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup'));
echo "<script>
var cal = new CalendarPopup();
var calName = '';
function setMultipleValues(y,m,d) {
     document.getElementById(calName+'_Y').value=y;
     document.getElementById(calName+'_M').selectedIndex=m*1-1;
     document.getElementById(calName+'_D').selectedIndex=d*1-1;
}
function setDate(name) {
  calName = name.toString();
  var year = document.getElementById(calName+'_Y').value.toString();
  var month = document.getElementById(calName+'_M').value.toString();
  var day = document.getElementById(calName+'_D').value.toString();
  var mdy = month+'/'+day+'/'+year;
  cal.setReturnFunction('setMultipleValues');
  cal.showCalendar('anchor', mdy);
}
</script>
";

echo "<form method=\"POST\">\n";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['proini'] . "\" name=\"proini\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['year_ini'] . "\" name=\"year_ini\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['year_fin'] . "\" name=\"year_fin\" />\n";
$gForm = new GAzieForm();

echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'] . $script_transl['vat_section'];
$gForm->selectNumber('vat_section', $form['vat_section'], 0, 1, 9, 'FacetSelect', 'vat_section');
echo "</div>\n";
if (count($msg['err']) > 0) { // ho un errore
    $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
echo "<table class=\"Tsmall\">\n";
echo "<tr>\n";
echo '<td class="text-right">'. $script_transl['date'] . " </td><td>\n";
$gForm->CalendarPopup('this_date', $form['this_date_D'], $form['this_date_M'], $form['this_date_Y'], 't', 1);
echo "</tr>\n";
echo "<tr>\n";
echo '<td class="text-right">' . $script_transl['type'] . ": </td><td>\n";
$gForm->variousSelect('type', $script_transl['type_value'], $form['type'], '', 0, 'type');
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo '<td class="text-right">Anno contabile: </td>';
echo '<td> ' . $form['year_fin'] . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo '<td class="text-right">' . $script_transl['proini'] . ": </td>\n";
echo '<td> '. $form['proini'] . " / " . $form['year_ini'] . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo '<td class="text-right">'. $script_transl['profin'] . ": </td>\n";
echo '<td><input type="text" name="profin" value="' . $form['profin'] . '" maxlength="9" onchange="this.form.hidden_req.value=\'profin\'; this.form.submit();"/> / ' . $form['year_fin'] . "</td>\n";
echo "</tr>\n";
echo "\t<tr class=\"FacetDataTD\">\n";
echo "\t<td><input type=\"submit\" name=\"return\" value=\"" .
 $script_transl['return'] . "\"></td>\n";
echo "<td></td>\n";
echo "\t </tr>\n";
echo "</table>\n";

echo "<div align=\"center\"><b>" . $script_transl['preview'] . "</b></div>";
echo "<div class=\"box-primary table-responsive\">";
echo "<table class=\"Tlarge table table-striped table-bordered table-condensed\">";
echo "<th class=\"FacetFieldCaptionTD\">" . $script_transl['date_reg'] . "</th>
     <th class=\"FacetFieldCaptionTD\">" . $script_transl['protoc'] . "</th>
     <th class=\"FacetFieldCaptionTD\">" . $script_transl['doc_type'] . "</th>
     <th class=\"FacetFieldCaptionTD\">N.</th>
     <th class=\"FacetFieldCaptionTD\">" . $script_transl['supplier'] . "</th>
     <th class=\"FacetFieldCaptionTD\">" . $script_transl['taxable'] . "</th>
     <th class=\"FacetFieldCaptionTD\">" . $script_transl['vat'] . "</th>
     <th class=\"FacetFieldCaptionTD\">" . $script_transl['tot'] . "</th>\n";
foreach ($rs as $k => $v) {
    //var_dump($v['docrows']);
    $tot = computeTot($v['vat']);
    //fine calcolo totali
    echo "<tr class=\"text-center\">
           <td align=\"center\">" . gaz_format_date($v['tes']['datfat']) . '</td>
           <td title="'.$v['title'].'"><a class="btn btn-small btn-'.$v['classv'].'" href="./admin_docacq.php?Update&id_tes='.$v['tes']['id_tes'].'">' . $v['tes']['protoc'] . '</a></td>
           <td class="">' . $script_transl['doc_type_value'][$v['tes']['tipdoc']];
			if (count($v['accpaymov'])>1) { // devo selezionare una partita dello scadenzario
				$form["accpaymov_$k"]=isset($form["accpaymov_$k"])?$form["accpaymov_$k"]:'';
				echo ' <span class="text-'.$v['classv'].'">: partita da chiudere sullo scadenzario:</span><br/>';
				$gForm->variousSelect("accpaymov[{$k}]", $v['accpaymov'], $form["accpaymov_$k"], '', 1, 'changepaymov',false,'',true);
			}
    echo '</td><td>' . $v['tes']['numfat'] . "</td>
           <td>" . $v['tes']['ragsoc'] . "</td>
           <td align=\"right\">" . gaz_format_number($tot['taxable']) . "</td>
           <td align=\"right\">" . gaz_format_number($tot['vat']) . "</td>
           <td align=\"right\">" . gaz_format_number($tot['tot']) . "</td>
           </tr>\n";
}
if (count($rs) > 0) {
    echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
    echo '<td colspan="9" class="text-center"><input type="submit" class="btn btn-warning" name="gosubmit" value="';
    echo $script_transl['submit'];
    echo '">';
    echo "\t </td>\n";
    echo "\t </tr>\n";
} else {
    echo "\t<tr>\n";
    echo '<td colspan="9" align="center" class="FacetDataTDred">';
    echo $script_transl['err']['nodoc'];
    echo "\t </td>\n";
    echo "\t </tr>\n";
}

?>
</table>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
