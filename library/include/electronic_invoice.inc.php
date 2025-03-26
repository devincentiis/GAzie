<?php

/* $
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

require_once("../../library/include/expiry_calc.php");

class invoiceXMLvars {
  public $totimp_body;
  public $taxstamp;
  public $virtual_taxstamp;
  public $tot_trasporto;
  public $body_castle;
  public $ivasplitpay;
  public $DatiVari;
  public $IdRig_NumeroLinea;
  public $DatiSAL;
  public $DatiVeicoli;
  public $DatiIntento;
  public $DatiDDT;
  public $SpeseIncassoTrasporti;
  public $SpeseBolli;
  public $gTables;
  public $descriptive_last_row;
  public $attach_pdf_to_fae = 0;
  public $azienda;
  public $pagame;
  public $banapp;
  public $banacc;
  public $tableName;
  public $intesta1;
  public $intesta1bis;
  public $intesta2;
  public $intesta3;
  public $aziendTel;
  public $aziendFax;
  public $REA_ufficio;
  public $REA_numero;
  public $REA_capitale;
  public $REA_socio;
  public $REA_stato;
  public $codici;
  public $sempl_accom;
  public $intesta4;
  public $intesta5;
  public $TipoRitenuta;
  public $colore;
  public $decimal_quantity;
  public $decimal_price;
  public $logo;
  public $link;
  public $perbollo;
  public $iva_bollo;
  public $client;
  public $cliente1;
  public $cliente2;
  public $cliente3;
  public $pec_email;
  public $id_agente;
  public $rs_agente;
  public $name_agente;
  public $destinazione;
  public $fiscal_rapresentative;
  public $clientSedeLegale;
  public $tesdoc;
  public $expense_pervat;
  public $min;
  public $ora;
  public $day;
  public $month;
  public $year;
  public $trasporto;
  public $testat;
  public $ddt_data;
  public $reverse;
  public $TipoDocumento;
  public $docRelNum;
  public $docRelDate;
  public $docDescri=false;
  public $protoc;
  public $fae_reinvii;
  public $seziva;
  public $docYear;
  public $IdCodice;
  public $totimp_decalc;
  public $totimp_doc;
  public $regime_fiscale;
  public $FormatoTrasmissione;
  public $Causale;
  public $RiferimentoAmministrazione;
  public $riporto;
  public $ritenuta;
  public $cassa_prev;
  public $castel;
  public $totivafat;
  public $totimpfat;
  public $totimpmer;
  public $tot_ritenute;
  public $impbol;
  public $BolloVirtuale;
  public $totriport;
  public $speseincasso;
  public $total_imp;
  public $total_vat;
  public $total_exc_with_duty;
  public $total_isp;
  public $totroundcastle;
  public $castle;
  public $chk_taxstamp;
  public $cast;
  public $vettore;
  public $ritenute;
  public $descrifae_vat;
  public $descrifae_natura;
  public $reverse_charge_sez;
  public $transchr = ['“'=>'"','‘'=>'\'','€'=>'euro','©'=>'&#169;','®'=>'&#174;','È'=>'&#200;','É'=>'&#201;','Ì'=>'&#204;','À'=>'&#192;','Ò'=>'&#210;','Ù'=>'&#217;',"ø" => "&#248;", "£" => "&#163;"];
  public $ExternalDocs= [];

  function setXMLvars($gTables, $tesdoc, $testat, $tableName, $ecr = false) {
    $this->gTables = $gTables;
    $admin_aziend = gaz_dbi_get_row($gTables['aziend'], 'codice', $_SESSION['company_id']);
    $this->descriptive_last_row = trim(gaz_dbi_get_row($gTables['company_config'], 'var', 'descriptive_last_row')['val']);
    $res_attach_pdf_to_fae = gaz_dbi_get_row($gTables['company_config'], 'var', 'attach_pdf_to_fae');
    $this->attach_pdf_to_fae = (isset($res_attach_pdf_to_fae['val']))?intval($res_attach_pdf_to_fae['val']):'';
    $this->azienda = $admin_aziend;
    $this->pagame = gaz_dbi_get_row($gTables['pagame'], "codice", $tesdoc['pagame']);
    $this->banapp = gaz_dbi_get_row($gTables['banapp'], "codice", $tesdoc['banapp']);
    $anagrafica = new Anagrafica();
    $this->banacc = $anagrafica->getPartner($this->pagame['id_bank']);
    $this->tableName = $tableName;
    $this->intesta1 = $admin_aziend['ragso1'];
    $this->intesta1bis = $admin_aziend['ragso2'];
    $this->intesta2 = $admin_aziend['indspe'] . ' ' . sprintf("%05d", $admin_aziend['capspe']) . ' ' . $admin_aziend['citspe'] . ' (' . $admin_aziend['prospe'] . ')';
    $this->intesta3 = 'Tel.' . $admin_aziend['telefo'] . ' ';
    $this->aziendTel = $admin_aziend['telefo'];
    $this->aziendFax = $admin_aziend['fax'];
    // REA
    $this->REA_ufficio = $admin_aziend['REA_ufficio'];
    $this->REA_numero = $admin_aziend['REA_numero'];
    $this->REA_capitale = $admin_aziend['REA_capitale'];
    $this->REA_socio = $admin_aziend['REA_socio'];
    $this->REA_stato = $admin_aziend['REA_stato'];
    $this->codici = '';
    if ($admin_aziend['codfis'] != '') {
        $this->codici .= 'C.F. ' . $admin_aziend['codfis'] . ' ';
    }
    if ($admin_aziend['pariva']) {
      $this->codici .= 'P.I. ' . $admin_aziend['pariva'] . ' ';
    }
    if ($tesdoc['template'] == 'FatturaImmediata') {
      $this->sempl_accom = true;
    } else {
      $this->sempl_accom = false;
    }
    $this->intesta4 = $admin_aziend['e_mail'];
    $this->intesta5 = $admin_aziend['sexper'];
    if ($admin_aziend['sexper'] == 'G') {
      $this->TipoRitenuta = 'RT02';
    } else {
      $this->TipoRitenuta = 'RT01';
    }
    $this->colore = $admin_aziend['colore'];
    $this->decimal_quantity = $admin_aziend['decimal_quantity'];
    $this->decimal_price = $admin_aziend['decimal_price'];
    $this->logo = $admin_aziend['image'];
    $this->link = $admin_aziend['web_url'];
    $this->perbollo = 0;
    $this->iva_bollo = gaz_dbi_get_row($gTables['aliiva'], "codice", $admin_aziend['taxstamp_vat']);
    $this->client = $anagrafica->getPartner($tesdoc['clfoco']);
    $this->cliente1 = $this->client['ragso1'];
    $this->cliente2 = $this->client['ragso2'];
    $this->cliente3 = $this->client['indspe'];
    $this->pec_email = $this->client['pec_email'];
    // variabile e' sempre un array
    $this->id_agente = gaz_dbi_get_row($gTables['agenti'], 'id_agente', $tesdoc['id_agente']);
    $this->rs_agente = ($this->id_agente)?$anagrafica->getPartner($this->id_agente['id_fornitore']):false;
    $this->name_agente =($this->rs_agente)?substr($this->rs_agente['ragso1'] . " " . $this->rs_agente['ragso2'], 0, 47):'';
    if ((isset($tesdoc['id_des_same_company'])) and ( $tesdoc['id_des_same_company'] > 0)) {
      $this->partner_dest = gaz_dbi_get_row($gTables['destina'], 'codice', $tesdoc['id_des_same_company']);
      $this->destinazione = substr($this->partner_dest['unita_locale1'] . " " . $this->partner_dest['unita_locale2'], 0, 45);
      $this->destinazione .= "\n" . substr($this->partner_dest['indspe'], 0, 45);
      $this->destinazione .= "\n" . substr($this->partner_dest['capspe'] . " " . $this->partner_dest['citspe'] . " (" . $this->partner_dest['prospe'] . ")", 0, 45);
    } elseif ((isset($tesdoc['id_des'])) and ( $tesdoc['id_des'] > 0)) {
      $this->partner_dest = $anagrafica->getPartnerData($tesdoc['id_des']);
      $this->destinazione = substr($this->partner_dest['ragso1'] . " " . $this->partner_dest['ragso2'], 0, 45);
      $this->destinazione .= "\n" . substr($this->partner_dest['indspe'], 0, 45);
      $this->destinazione .= "\n" . substr($this->partner_dest['capspe'] . " " . $this->partner_dest['citspe'] . " (" . $this->partner_dest['prospe'] . ")", 0, 45);
    } else {
      if (isset($tesdoc['destin']) and is_array($tesdoc['destin'])) {
        $this->destinazione = $tesdoc['destin'];
      } elseif (isset($tesdoc['destin']) and is_string($tesdoc['destin'])) {
        $destino = preg_split("/[\r\n]+/i", $tesdoc['destin'], 3);
        $this->destinazione = substr($destino[0], 0, 45);
        foreach ($destino as $key => $value) {
          if ($key == 1) {
            $this->destinazione .= "\n" . substr($value, 0, 45) . "\n";
          } elseif ($key > 1) {
            $this->destinazione .= substr(preg_replace("/[\r\n]+/i", ' ', $value), 0, 45);
          }
        }
      } else {
         $this->destinazione = '';
      }
    }
    $this->vettore = false;
    if ($tesdoc['vettor']>0){
      $this->vettore = gaz_dbi_get_row($gTables['vettor'].' LEFT JOIN '.$gTables['anagra'].' ON '.$gTables['vettor'].'.id_anagra = '.$gTables['anagra'].".id", "codice", $tesdoc['vettor']);
    }
    $this->fiscal_rapresentative = false;
    if ($this->client['fiscal_rapresentative_id']>0){
      $this->fiscal_rapresentative = gaz_dbi_get_row($gTables['anagra'], "id", $this->client['fiscal_rapresentative_id']);
    }
    $this->clientSedeLegale = ((trim($this->client['sedleg']) != '') ? preg_split("/\n/", trim($this->client['sedleg'])) : array());
    $this->client = $anagrafica->getPartner($tesdoc['clfoco']);
    $this->tesdoc = $tesdoc;
    $this->expense_pervat = gaz_dbi_get_row($gTables['aliiva'], "codice", $this->tesdoc['expense_vat']);
    $this->min = substr($tesdoc['initra'], 14, 2);
    $this->ora = substr($tesdoc['initra'], 11, 2);
    $this->day = substr($tesdoc['initra'], 8, 2);
    $this->month = substr($tesdoc['initra'], 5, 2);
    $this->year = substr($tesdoc['initra'], 0, 4);
    $this->trasporto = $tesdoc['traspo'];
    $this->testat = $testat;
    $this->ddt_data = false;
    $this->reverse = false;
    $this->TipoDocumento = 'TD01';    // <TipoDocumento> 2.1.1.1
    $this->docRelNum = $this->tesdoc["numdoc"].'/'.$this->tesdoc["seziva"];    // Numero del documento relativo
    $this->docRelDate = $this->tesdoc["datemi"];    // Data del documento relativo
    $this->protoc = $this->tesdoc["protoc"];
    $this->fae_reinvii = $this->tesdoc["fattura_elettronica_reinvii"];
    switch ($tesdoc["tipdoc"]) {
      case "FAD": // Fattura differita di cui all’articolo 21, comma 4, lettera a
        $this->TipoDocumento = 'TD24';
        $this->ddt_data = true;
        $this->docRelNum = $this->tesdoc["numfat"].'/'.$this->tesdoc["seziva"];
        $this->docRelDate = $this->tesdoc["datfat"];
        $this->docDescri = 'Fattura_differita_';
        break;
      case "FAI": // Fattura immediata
        $this->docRelNum = $this->tesdoc["numfat"].'/'.$this->tesdoc["seziva"];
        $this->docRelDate = $this->tesdoc["datfat"];
        $this->docDescri = 'Fattura_immediata_';
        break;
      case "FAA": // Fattura d'acconto
        $this->TipoDocumento = 'TD02';    // <TipoDocumento> 2.1.1.1
        $this->docRelNum = $this->tesdoc["numfat"].'/'.$this->tesdoc["seziva"];
        $this->docRelDate = $this->tesdoc["datfat"];
        $this->docDescri = 'Fattura_di_acconto_';
        break;
      case "FAF": // Fattura d'acconto
        $this->TipoDocumento = 'TD27';    // <TipoDocumento> 2.1.1.1
        $this->docRelNum = $this->tesdoc["numfat"].'/'.$this->tesdoc["seziva"];
        $this->docRelDate = $this->tesdoc["datfat"];
        $this->docDescri = 'Fattura_di_acconto_';
        break;
      case "FNC":
        $this->TipoDocumento = 'TD04';    // <TipoDocumento> 2.1.1.1
        $this->docRelNum = $this->tesdoc["numfat"].'/'.$this->tesdoc["seziva"];
        $this->docRelDate = $this->tesdoc["datfat"];
        $this->docDescri = 'Nota_di_credito_';
        break;
      case "FND":
        $this->TipoDocumento = 'TD05';    // <TipoDocumento> 2.1.1.1
        $this->docRelNum = $this->tesdoc["numfat"].'/'.$this->tesdoc["seziva"].'/ND';
        $this->docRelDate = $this->tesdoc["datfat"];
        $this->docDescri = 'Nota_di_debito_';
        break;
      case "FAP":
        $this->TipoDocumento = 'TD06';    // <TipoDocumento> 2.1.1.1
        $this->docRelNum = $this->tesdoc["numfat"].'/'.$this->tesdoc["seziva"];
        $this->docRelDate = $this->tesdoc["datfat"];
        $this->docDescri = 'Parcella_';
        break;
      case "FAQ": // Parcella d'acconto
        $this->TipoDocumento = 'TD03';    // <TipoDocumento> 2.1.1.1
        $this->docRelNum = $this->tesdoc["numfat"].'/'.$this->tesdoc["seziva"];
        $this->docRelDate = $this->tesdoc["datfat"];
        $this->docDescri = 'Parcella_di_acconto';
        break;
      case "VCO":
        $this->protoc = $this->tesdoc["numfat"]; //forzo il protocollo al numero fattura in caso di registro corrispettivi
        $this->fae_reinvii = $this->fae_reinvii+4; // e aggiungo 4 per non far collidere con un eventuale fattura normale della stessa sezione
        $this->docRelNum = $this->tesdoc["numfat"].'/'.$this->tesdoc["seziva"].'/SCONTR';
        $this->docRelDate = $this->tesdoc["datfat"];
        $this->docDescri = 'Fattura_allegata_a_corrispettivo';
        break;
      case "XFA":
      case "XNC":
        $this->decimal_price = 8; // sicome è un documento proveniente dall'esterno forzo ad un numero maggiore di decimali maggiore rispetto a quelli selezionabili su GAzie
        $this->reverse_charge_sez = $admin_aziend['reverse_charge_sez'];
        $this->TipoDocumento = $this->tesdoc["status"];
        $this->reverse = true;
        $this->docRelNum = $this->tesdoc["protoc"].'/'.$this->tesdoc["seziva"]; // sulle autofatture utilizzo il protocollo per avere sequenzialità
        $this->docRelDate = $this->tesdoc["datreg"];
        $this->docDescri = false;
        break;
      case "DDT":
      case "DDL":
      case "DDR":
      default:
        $this->ddt_data = true;
        $this->docRelNum = $this->tesdoc["numdoc"].'/'.$this->tesdoc["seziva"];    // Numero del documento relativo
        $this->docRelDate = $this->tesdoc["datemi"];    // Data del documento relativo
        $this->docDescri = false;
    }
    $this->docDescri .= $this->tesdoc["numfat"].'-'.$this->tesdoc["seziva"].'_del_'.gaz_format_date($this->tesdoc["datfat"]);    // Descrizione per allegato PDF
    $this->seziva = $this->tesdoc["seziva"];
    $this->docYear = substr($this->tesdoc["datemi"], 0, 4);    // Anno del documento
    $this->IdCodice = $admin_aziend['codfis'];
    $this->totimp_body = 0;
    $this->totimp_decalc = 0;
    $this->totimp_doc = 0;
    // ATTRIBUISCO UN EVENTUALE REGIME FISCALE DIVERSO DALLA CONFIGURAZIONE AZIENDA SE LA SEZIONE IVA E' LEGATO AD ESSO TRAMITE IL RIGO var='sezione_regime_fiscale' IN gaz_XXXcompany_config
    $this->regime_fiscale=$this->azienda['fiscal_reg'];
    if ($fr=getRegimeFiscale($this->seziva)) $this->regime_fiscale=$fr;
    // riprendo il valore percentuale da usare per i righi descrittivi
    $descrifaealiiva=gaz_dbi_get_row($gTables['aliiva'], "codice", $this->azienda['preeminent_vat']);
    $this->descrifae_vat = number_format($descrifaealiiva['aliquo'],2,'.','');
    $this->descrifae_natura = $descrifaealiiva['fae_natura'];
  }

  function getXMLrows() {
    $this->tot_trasporto += $this->trasporto;
    if ($this->taxstamp < 0.01 && $this->tesdoc['taxstamp'] >= 0.01) {
      $this->taxstamp = $this->tesdoc['taxstamp'];
    }
    $from = $this->gTables[$this->tableName] . ' AS rs
             LEFT JOIN ' . $this->gTables['aliiva'] . ' AS vat ON rs.codvat=vat.codice
             LEFT JOIN ' . $this->gTables['movmag'] . ' AS mom ON rs.id_mag=mom.id_mov
             LEFT JOIN ' . $this->gTables['lotmag'] . ' AS ltm ON mom.id_lotmag=ltm.id
		 ';
    $rs_rig = gaz_dbi_dyn_query("rs.*,vat.tipiva AS tipiva, vat.fae_natura AS natura, ltm.identifier AS idlotto, DATE_FORMAT(ltm.expiry,'%d-%m-%Y') AS scadenzalotto", $from, "rs.id_tes = " . $this->testat, "id_tes DESC, id_rig");
    $this->riporto = 0.00;
    $this->ritenuta = 0.00;
    $this->cassa_prev = array();
    $last_normal_row = 0;
    $nr = 1;
    $results = array();
    $dom = new DOMDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    // questi mi servono per associare i numeri righi ad id_rig  per riferire i valori sull'accumulatore per 2.1.X
    $id_rig_ref=array();
    $ctrl_idtes=0;
    $nr_idtes=1;
    while ($rigo = gaz_dbi_fetch_array($rs_rig)) {
      // filtro le descrizioni
      $rigo['descri'] = strtr ( htmlspecialchars(htmlspecialchars_decode(trim(html_entity_decode($rigo['descri'], ENT_XML1 | ENT_QUOTES, 'UTF-8'))), ENT_XML1, 'UTF-8'), $this->transchr);
      if ($ctrl_idtes<>$rigo['id_tes']){ // è cambiata la testata riparto da NumeroLinea 1 e azzero l'array ref
        $nr_idtes=1;
        $id_rig_ref=array();
      }
      $rigo['sconto_su_imponibile'] = array();
      $rigo['codice_tipo']='';
      if ($rigo['tiprig'] <= 1 || $rigo['tiprig'] == 50) { // normale, forfait o normale c/allegato
        if (!empty($rigo['codart'])){ // ho un codice articolo lo riprendo per settare il codice tipo ( ci metterò se bene o servizio e categoria merceologica)
          $artico = gaz_dbi_get_row($this->gTables['artico'], "codice", $rigo['codart']);
          $rigo['codice_tipo']=($artico['good_or_service'] == 0 OR $artico['good_or_service'] == 2) ? 'BENE_CAT_'.$artico['catmer'] : ($artico['catmer'] >=1 ? 'SERVIZIO_CAT_'.$artico['catmer'] : 'SERVIZIO');
        }
        $id_rig_ref[$nr_idtes]=$rigo['id_rig']; // associo l'id_rig al numero rigo mi servirà per valorizzare l'accumulatore per 2.1.X
        $nr_idtes++; // è un tipo rigo a cui possono essere riferiti i dati degli elementi 2.1.X, lo aumento
        $rigo['importo'] = CalcolaImportoRigo($rigo['quanti'], $rigo['prelis'],0);
        $v_for_castle = CalcolaImportoRigo($rigo['quanti'], $rigo['prelis'], array($rigo['sconto'], $this->tesdoc['sconto']));
        if ($rigo['tiprig'] == 1) { // forfait
          $rigo['importo'] = CalcolaImportoRigo(1, $rigo['prelis'], 0);
          $v_for_castle = CalcolaImportoRigo(1, $rigo['prelis'], $this->tesdoc['sconto']);
          $rigo['quanti']=1;
        } elseif ($rigo['tiprig'] == 50) { // normale con allegato
          // accumulo il file da allegare e lo indico al posto del codice articolo
          $this->getExtDoc($rigo['id_rig'],$rigo['descri']);
        }
        $sconto_su_imponibile = round($v_for_castle - $rigo['importo'], 2); // qui metto l'eventuale totale imponibile scontato
        if (abs($sconto_su_imponibile)>=0.01){
          if ($sconto_su_imponibile*$v_for_castle <= 0) {// se hanno segni differenti o lo sconto è totale
                       $t = 'SC';
          } else {
                       $t = 'MG'; // è una maggiorazione
          }
          $perc_sconto=100*(1-(1-$rigo['sconto']/100)*(1-$this->tesdoc['sconto']/100));
          $rigo['sconto_su_imponibile'][$rigo['id_rig']]=array('tipo'=>$t,'importo_sconto'=>$sconto_su_imponibile,'scorig'=>floatval($rigo['sconto']),'scotes'=>floatval($this->tesdoc['sconto']),'perc_sconto'=>$perc_sconto,'rigo'=>$rigo);
        }
        if (!isset($this->castel[$rigo['codvat']])) {
          $this->castel[$rigo['codvat']] = 0;
        }
        if (!isset($this->body_castle[$rigo['codvat']])) {
          $this->body_castle[$rigo['codvat']]['impcast'] = 0;
        }
        $this->body_castle[$rigo['codvat']]['impcast'] += $v_for_castle;
        $this->castel[$rigo['codvat']] += $v_for_castle;
        $this->totimp_body += $rigo['importo'];
        $this->ritenuta += round($rigo['importo'] * $rigo['ritenuta'] / 100, 2);
        $this->totimp_doc += $v_for_castle;
        // aggiungo all'accumulatore l'eventuale iva non esigibile (split payment PA)
        if ($rigo['tipiva'] == 'T') {
            $this->ivasplitpay += round(($v_for_castle * $rigo['pervat']) / 100, 2);
        }
        $this->descrifae_vat = $rigo['pervat'];
        $this->descrifae_natura = $rigo['natura'];
      } elseif ($rigo['tiprig'] == 2) { // descrittivo

      } elseif ($rigo['tiprig'] == 51) { // descrittivo c/allegato
          // accumulo il file da allegare e lo indico al posto del codice articolo
          $this->getExtDoc($rigo['id_rig'],$rigo['descri']);
      } elseif ($rigo['tiprig'] == 4) { // cassa previdenziale
        if (!isset($this->castel[$rigo['codvat']])) {
          $this->castel[$rigo['codvat']] = 0;
        }
        if (!isset($this->body_castle[$rigo['codvat']])) {
          $this->body_castle[$rigo['codvat']]['impcast'] = 0;
        }
        $rigo['importo'] = round($rigo['provvigione']*$rigo['prelis']/100,2);
        $v_for_castle = $rigo['importo'] ;
        $this->body_castle[$rigo['codvat']]['impcast'] += $v_for_castle;
        $this->castel[$rigo['codvat']] += $v_for_castle;
        $this->totimp_body += $rigo['importo'];
        $this->ritenuta += round($rigo['importo'] * $rigo['ritenuta'] / 100, 2);
        $this->totimp_doc += $v_for_castle;
        // aggiungo all'accumulatore l'eventuale iva non esigibile (split payment PA)
        if ($rigo['tipiva'] == 'T') {
          $this->ivasplitpay += round(($v_for_castle * $rigo['pervat']) / 100, 2);
        }
        /* con codart valorizzo l'elemento <TipoCassa> e creo l'array che mi servirà per generare gli elementi <DatiCassaPrevidenziale> */
        if (!isset($this->cassa_prev[$rigo['codart']])) { // se il tipo cassa non ce l'ho
          $this->cassa_prev[$rigo['codart']] = array('AlCassa'=>$rigo['provvigione'],'ImportoContributoCassa'=>$rigo['importo'],'ImponibileCassa'=>$rigo['prelis'],'AliquotaIVA'=>$rigo['pervat'],'Ritenuta'=>$rigo['ritenuta'],'Natura'=>$rigo['natura']);
        } else { // ho già l'elemento <TipoCassa>
          $this->cassa_prev[$rigo['codart']]['ImponibileCassa'] +=$rigo['prelis'];
          $this->cassa_prev[$rigo['codart']]['ImportoContributoCassa'] +=$rigo['importo'];
        }
      } elseif ($rigo['tiprig'] == 6 || $rigo['tiprig'] == 8) { // testo
        $body_text = gaz_dbi_get_row($this->gTables['body_text'], "id_body", $rigo['id_body_text']);
        if ($body_text['body_text']!= NULL){
          $dom->loadHTML($body_text['body_text']);
          $rigo['descri'] = htmlspecialchars_decode(str_replace('&amp;#xD;','',trim(htmlentities(strip_tags($dom->saveXML())))));
          $rigo['tiprig'] = 'D';
        }
      } elseif ($rigo['tiprig'] == 3) {  // var.totale fattura
        $this->riporto += $rigo['prelis'];
      } elseif ($rigo['tiprig']>10 && $rigo['tiprig']<17) {
        if ($rigo['codric']>0){
          $this->IdRig_NumeroLinea[$id_rig_ref[$rigo['codric']]]=$rigo['id_rig']; // qui riferirò l'id_rig del rigo da riportare sull'accumulatore per 2.1.X con l'id_rig del normale
        } else {
          $id_rig_ref[$rigo['codric']]=0;
        }
        $weight_tiprig=array(11=>7,12=>6,13=>2,14=>3,15=>4,16=>5);
        // qui valorizzo l'accumulatore 2.1.X e dipende dal tipo che ho scritto su codvat
        switch ($rigo['codvat']) {
          case "6":
            $this->DatiVari[5][$id_rig_ref[$rigo['codric']]][$weight_tiprig[$rigo['tiprig']]]=$rigo['descri'];
          break;
          case "5":
            $this->DatiVari[4][$id_rig_ref[$rigo['codric']]][$weight_tiprig[$rigo['tiprig']]]=$rigo['descri'];
          break;
          case "4":
            $this->DatiVari[3][$id_rig_ref[$rigo['codric']]][$weight_tiprig[$rigo['tiprig']]]=$rigo['descri'];
          break;
          case "3":
            $this->DatiVari[2][$id_rig_ref[$rigo['codric']]][$weight_tiprig[$rigo['tiprig']]]=$rigo['descri'];
          break;
          case "2":
                    default:
            $this->DatiVari[1][$id_rig_ref[$rigo['codric']]][$weight_tiprig[$rigo['tiprig']]]=$rigo['descri'];
          break;
        }
      } elseif ($rigo['tiprig'] == 17) {  // 2.2.1.15 RiferimentoAmministrazione
        $this->RiferimentoAmministrazione=$rigo['descri'];
      }elseif ($rigo['tiprig'] == 21) {  // Causale
        $this->Causale[]=$rigo['descri'];
      } elseif ($rigo['tiprig'] == 25) {  // DatiSAL
        $this->DatiSAL[]=$rigo['descri']; //faccio il push sull'array
      } elseif ($rigo['tiprig'] == 26) {  // Dati Intento
        $this->DatiIntento=array('RiferimentoTesto'=>$rigo['descri'],'RiferimentoData'=>$rigo['codart']);
      } elseif ($rigo['tiprig'] == 31) {  // DatiVeicoli 2.3
        $this->DatiVeicoli=array('Data'=>$rigo['descri'],'TotalePercorso'=>intval($rigo['quanti']));
      } elseif ($rigo['tiprig'] == 90) {
        $this->id_rig_ref[$nr_idtes]=$rigo['id_rig'];
        $nr_idtes++; // è un tipo rigo a cui possono essere riferiti i DatiOrdiniAcquisto,ecc lo aumento
      }
      $ctrl_idtes=$rigo['id_tes'];
      $results[$nr] = $rigo;
      $nr++;
    }
    // se ho dei trasporti lo aggiungo ai righi del relativo DdT
    if ($this->trasporto >= 0.1) {
      $rigo_T = array('id_rig'=>0,'tiprig'=>'T','descri'=>'TRASPORTO','importo'=>$this->trasporto,'pervat'=>$this->expense_pervat['aliquo'],'ritenuta'=>0,'natura'=>$this->expense_pervat['fae_natura']);
      $results[$nr] = $rigo_T;
      $nr++;
    }
    return $results;
  }

  function setXMLtot() {
    $calc = new Compute();
    $this->totivafat = 0.00;
    $this->totimpfat = 0.00;
    $this->totimpmer = 0.00;
    $this->tot_ritenute = $this->ritenuta;
    $this->virtual_taxstamp = $this->tesdoc['virtual_taxstamp'];
    $this->impbol = 0.00;
    $this->BolloVirtuale = false; // ovviamente il bollo potrà essere solo virtuale ma comunque lo setto per evidenziare l'errore
    if ($this->tesdoc['virtual_taxstamp'] == 2 || $this->tesdoc['virtual_taxstamp'] == 3) { // bollo virtualmente assolto
      $this->BolloVirtuale = 'SI';
    }
    $this->totriport = $this->riporto;
    $this->speseincasso = $this->tesdoc['speban'] * $this->pagame['numrat'];
    if (!isset($this->castel)) {
      $this->castel = array();
    }
    if (!isset($this->totimp_body)) {
      $this->totimp_body = 0;
    }
    $this->totimpmer = $this->totimp_body;
    $this->totimp_body = 0;
    if (!isset($this->totimp_doc)) {
      $this->totimp_doc = 0;
    }
    $this->totimpfat = $this->totimp_doc;
    $this->totimp_doc = 0;
    $somma_spese = $this->tot_trasporto + $this->speseincasso + $this->tesdoc['spevar'];
    $calc->add_value_to_VAT_castle($this->body_castle, $somma_spese, $this->tesdoc['expense_vat']);
    if ($this->tesdoc['stamp'] > 0) {
      $calc->payment_taxstamp($calc->total_imp + $this->totriport + $calc->total_vat - $this->tot_ritenute + $this->taxstamp - $this->ivasplitpay, $this->tesdoc['stamp'], $this->tesdoc['round_stamp'] * $this->pagame['numrat']);
      $this->impbol = $calc->pay_taxstamp;
    }
    $this->totimpfat = $calc->total_imp;
    $this->totivafat = $calc->total_vat;
    // aggiungo gli eventuali bolli al castelletto
    $this->chk_taxstamp = true;
    if ($this->virtual_taxstamp == 0 || $this->virtual_taxstamp == 3) { //  se è a carico dell'emittente non lo aggiungo al castelletto IVA
      $this->chk_taxstamp = false;
    }
    if ($this->impbol >= 0.01 || ($this->taxstamp >= 0.01 && $this->chk_taxstamp)) {
      $this->impbol += $this->taxstamp;
      $calc->add_value_to_VAT_castle($calc->castle, $this->impbol, $this->azienda['taxstamp_vat']);
    } elseif (!$this->chk_taxstamp) { // bollo da non addebitare ma esistente
      $this->impbol = $this->taxstamp;
    }
    $this->cast = $calc->castle;
    $this->riporto = 0;
    $this->ritenute = 0;
  }
  function getExtDoc($id_rig,$descri) {
    // in ExternalDocs metterò gli eventuali documenti da allegare
    $files = glob( DATA_DIR . 'files/' . $this->azienda['codice'].'/doc/'.$id_rig.'_rigdoc_*.*');
    foreach($files as $file) {
      $fd = pathinfo($file);
      $e=explode('_rigdoc_',$fd['filename']);
      if ($e[0] == $id_rig) {
        $this->ExternalDocs[] = ['file'=>$file,'oriname'=>$e[1],'ext'=>$fd['extension'],'descri'=>$descri,'path'=>$file];
      }
    }
  }
}

function create_XML_invoice($testata, $gTables, $rows = 'rigdoc', $dest = false, $name_ziparchive = false, $returnDocument=false, $pdf_content=false) {
  $XMLvars = new invoiceXMLvars();
  $domDoc = new DOMDocument;
	$domDoc->preserveWhiteSpace = false;
	$domDoc->formatOutput = true;
  $ctrl_doc = '';
  $ctrl_fat = '';
  $n_linea = 1;
  // definisco le variabili dei totali
  $XMLvars->totimp_body = 0;
  $XMLvars->taxstamp = 0;
  $XMLvars->virtual_taxstamp = 0;
  $XMLvars->tot_trasporto = 0;
  $XMLvars->body_castle = array();
  $XMLvars->ivasplitpay = 0.00;
	// inizializzo l'accumulatore per 2.1.X DatiVari
	$XMLvars->DatiVari=array();
	$XMLvars->IdRig_NumeroLinea=array();
	// inizializzo l'accumulatore per DatiSAL 2.1.7
	$XMLvars->DatiSAL=array();
	// inizializzo la variabile per DatiVeicoli 2.3
	$XMLvars->DatiVeicoli=false;
  // inizializzo la variabile per DatiIntento
  $XMLvars->DatiIntento=false;
	// inizializzo l'accumulatore per DatiDDT
	$XMLvars->DatiDDT=array();
	// inizializzo la somma delle SpeseAccessorie
	$XMLvars->SpeseIncassoTrasporti=0;
	$XMLvars->SpeseBolli=0;
  while ($tesdoc = gaz_dbi_fetch_array($testata)) {
    $XMLvars->setXMLvars($gTables, $tesdoc, $tesdoc['id_tes'], $rows, false);
    $cod_destinatario=trim($XMLvars->client['fe_cod_univoco']); // elemento 1.1.4
		if ($ctrl_fat <> $XMLvars->tesdoc['numfat']) {
			// stabilisco quale template dovrò usare ad ogni cambio di fattura
			if ($XMLvars->docYear <= 2016) { // FAttura Elettronica PA fino al 2016
				$domDoc->load("../../library/include/template_fae.xml");
				$XMLvars->FormatoTrasmissione='FPA';
			} elseif (strlen($cod_destinatario)<=0 || strlen($cod_destinatario)>=7) { // FAttura Elettronica Privati
				$domDoc->load("../../library/include/template_fae_FPR12.xml");
				$XMLvars->FormatoTrasmissione='FPR';
			} else { // FAttura Elettronica PA a partire dal 2017
				$domDoc->load("../../library/include/template_fae_FPA12.xml");
				$XMLvars->FormatoTrasmissione='FPA';
			}
      // inizializzo la variabile per Causale 2.1.1.11 e se il regime fiscale è RF02 (contribuenti minimi) o RF19 (regime forfettario) allora indico le relative diciture
      $XMLvars->Causale=[];
      if ($XMLvars->regime_fiscale=='RF02') {
        $XMLvars->Causale[]= "Operazione effettuata ai sensi dell'art.1 comma 100 Legge 244/2007. Compenso non assoggettato a ritenuta d'acconto ai sensi dell'art.27 del DL 98 del 06.07.2011";
      } elseif ($XMLvars->regime_fiscale=='RF19') {
        $XMLvars->Causale[]= "Operazione effettuata ai sensi dell'art.1 commi da 54 a 89 Legge 190/2014 e successive modifiche. Compenso non assoggettato a ritenuta d'acconto ai sensi dall'art.1 comma 67 Legge n.190/2014";
      }
      // inizializzo 2.2.1.15 RiferimentoAmministrazione
      $XMLvars->RiferimentoAmministrazione=false;
			$xpath = new DOMXPath($domDoc);
		}
    // controllo se ho un ufficio diverso da quello di base
    if (isset($tesdoc['id_des_same_company']) && $tesdoc['id_des_same_company'] > 0) {
      $dest = gaz_dbi_get_row($gTables['destina'], 'codice', $tesdoc['id_des_same_company']);
      $cod_destinatario=trim($dest['fe_cod_ufficio']); // elemento 1.1.4
      $XMLvars->client['fe_cod_univoco']=$cod_destinatario;
    }
		// se c'è un ddt di origine ogni testata creerà un riferimento in <DatiDDT>
    if ($XMLvars->ddt_data) { // se c'è un ddt di origine ogni testata faccio il push sull'accumulatore per creare il blocco <DatiDDT>
			$XMLvars->DatiDDT[$XMLvars->tesdoc['numdoc']]=array("DataDDT"=>$XMLvars->tesdoc['datemi'],"RiferimentoNumeroLinea"=>array());
    }
    if (empty($ctrl_doc)) {
      $id_progressivo = substr($XMLvars->docRelDate, 2, 2) . $XMLvars->seziva .$XMLvars->fae_reinvii . str_pad($XMLvars->protoc, 6, '0', STR_PAD_LEFT);
      //per il momento sono singole chiamate xpath a regime e' possibile usare un array associativo da passare ad una funzione
      $results = $xpath->query("//FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese")->item(0);
      $attrVal = $domDoc->createTextNode('IT');
      $results->appendChild($attrVal);
      $results = $xpath->query("//FatturaElettronicaHeader/DatiTrasmissione/ProgressivoInvio")->item(0);
      $attrVal = $domDoc->createTextNode($id_progressivo);
      $results->appendChild($attrVal);
      $results = $xpath->query("//FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice")->item(0);
      $attrVal = $domDoc->createTextNode($XMLvars->IdCodice);
      $results->appendChild($attrVal);
      $results = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdPaese")->item(0);
      $attrVal = $domDoc->createTextNode($XMLvars->reverse?$XMLvars->client['country']:$XMLvars->azienda['country']);
      $results->appendChild($attrVal);
      //il IdCodice iva e' la partita iva?
      $results = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0);
			if ($XMLvars->reverse&&($XMLvars->TipoDocumento=='TD17'||$XMLvars->TipoDocumento=='TD18'|| ($XMLvars->TipoDocumento=='TD19' && $XMLvars->client['country']<>'IT'))) { // gli stranieri metto il codice fiscale in mancanza la partita IVA se non ho nessuno dei due uso XXXXXXX
				if (strlen($XMLvars->client['codfis'])>3){
					$vidc=trim($XMLvars->client['codfis']);
				} elseif (strlen($XMLvars->client['pariva'])>3){
					$vidc=trim($XMLvars->client['pariva']);
				} else {
					$vidc='XXXXXXX';
				}
				$attrVal = $domDoc->createTextNode($vidc);
			} elseif ($XMLvars->TipoDocumento=='TD16') {
				if (strlen($XMLvars->client['pariva'])>3){
					$vidc=trim($XMLvars->client['pariva']);
				} else {
					$vidc='00000000000';
				}
				$attrVal = $domDoc->createTextNode($vidc);
			} else {
				$attrVal = $domDoc->createTextNode(trim($XMLvars->azienda['pariva']));
			}
      $results->appendChild($attrVal);
      //nodo 1.2.1.2 Codice Fiscale richiesto da alcune amministrazioni come obbligatorio ma da non indicare sulle autofatture a stanieri
			if ($XMLvars->reverse&&$XMLvars->TipoDocumento<>'TD16') {
				$results = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0);
				$results->parentNode->removeChild($results);
			} elseif ($XMLvars->TipoDocumento=='TD16') {
				if (strlen($XMLvars->client['codfis'])>3){
					$vidc=trim($XMLvars->client['codfis']);
				} else {
					$vidc='XXXXXXX';
				}
				$results = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0);
				$attrVal = $domDoc->createTextNode($vidc);
				$results->appendChild($attrVal);
			} else {
				$results = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0);
				$attrVal = $domDoc->createTextNode(trim($XMLvars->IdCodice));
				$results->appendChild($attrVal);
			}
			if ($XMLvars->FormatoTrasmissione == "FPA") {
				//nodo 1.1.4
				$results = $xpath->query("//FatturaElettronicaHeader/DatiTrasmissione/CodiceDestinatario")->item(0);
				$attrVal = $domDoc->createTextNode(trim($XMLvars->client['fe_cod_univoco']));
				$results->appendChild($attrVal);
			} else {
				$results = $xpath->query("//FatturaElettronicaHeader/DatiTrasmissione/CodiceDestinatario")->item(0);
				if ($XMLvars->reverse) {
					$attrVal = $domDoc->createTextNode("0000000");
					$results->appendChild($attrVal);
				} elseif (strlen($cod_destinatario) < 6 ) {
					if ($XMLvars->client['country']=='IT'){
						$attrVal = $domDoc->createTextNode("0000000");
					} else {
						$attrVal = $domDoc->createTextNode("XXXXXXX");
					}
					$results->appendChild($attrVal);
					if (strlen(trim($XMLvars->client['pec_email'])) > 6 ) { // l'elemento per la pec la creo solo se c'è
						//nodo 1.1.6
						$el = $domDoc->createElement("PECDestinatario", trim($XMLvars->client['pec_email']));
						$results1 = $xpath->query("//FatturaElettronicaHeader/DatiTrasmissione")->item(0);
						$results1->appendChild($el);
					}
				} else {
					$attrVal = $domDoc->createTextNode(trim($XMLvars->client['fe_cod_univoco']));
					$results->appendChild($attrVal);
        }
			}
			if ($XMLvars->reverse) { // sulle autofatture (da TD16 a TD20) utilizzo i dati azienda per popolare il CessionarioCommittente e aggiungo l'elemento <SoggettoEmittente> per indicarlo
        // INIZIO REVERSE
        if ($XMLvars->TipoDocumento<>'TD16' && $XMLvars->fiscal_rapresentative && $XMLvars->fiscal_rapresentative['pariva'] > 100000) { // se è uno straniero controllo che non abbia il legale rappresentante in Italia se si aggiungo l'elemento 1.3 <RappresentanteFiscale>
					$feh = $xpath->query("//FatturaElettronicaHeader")->item(0);
					$fehcp = $xpath->query("//FatturaElettronicaHeader/CessionarioCommittente")->item(0);
					$ee = $domDoc->createElement("RappresentanteFiscale","");
					$el = $domDoc->createElement("DatiAnagrafici","");
					$el1 = $domDoc->createElement("IdFiscaleIVA","");
					$el->appendChild($el1);
					$el2 = $domDoc->createElement("IdPaese", $XMLvars->fiscal_rapresentative['country']);
					$el1->appendChild($el2);
					$el2 = $domDoc->createElement("IdCodice", $XMLvars->fiscal_rapresentative['pariva']);
					$el1->appendChild($el2);
					$el1 = $domDoc->createElement("Anagrafica","");
					if (($XMLvars->fiscal_rapresentative['sexper']!='G') && (trim($XMLvars->fiscal_rapresentative['legrap_pf_nome'])!='') && (trim($XMLvars->fiscal_rapresentative['legrap_pf_nome'])!='')) {
						// se è una persona fisica e ha valorizzato nome e cognome inserisco questi dati
						$el2 = $domDoc->createElement("Nome", substr(trim($XMLvars->fiscal_rapresentative['legrap_pf_nome']), 0, 80));
						$el1->appendChild($el2);
						$el2 = $domDoc->createElement("Cognome", substr(trim($XMLvars->fiscal_rapresentative['legrap_pf_cognome']), 0, 80));
						$el1->appendChild($el2);
					} else {
						 // Se è una ditta inserisco la denominazione
						$el2 = $domDoc->createElement("Denominazione", substr(htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->fiscal_rapresentative['ragso1'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true) . " " . htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->fiscal_rapresentative['ragso2'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true), 0, 80));
						$el1->appendChild($el2);
					}
					$el->appendChild($el1);
					$ee->appendChild($el);
					$feh->insertBefore($ee,$fehcp);
        }
				$rsDatiAnagrafici = $xpath->query("//CessionarioCommittente/DatiAnagrafici")->item(0);
				$rsAnagrafica = $xpath->query("//CessionarioCommittente/DatiAnagrafici/Anagrafica")->item(0);
				$el = $domDoc->createElement("IdFiscaleIVA", '');
				$el->appendChild($domDoc->createElement('IdPaese', $XMLvars->azienda['country']));
				$el->appendChild($domDoc->createElement('IdCodice', $XMLvars->azienda['pariva']));
				$rsDatiAnagrafici->insertBefore($el, $rsAnagrafica);
				$el = $domDoc->createElement("CodiceFiscale", trim($XMLvars->azienda['codfis']));
				$rsDatiAnagrafici->insertBefore($el, $rsAnagrafica);
				if (($XMLvars->azienda['sexper']!='G') && (trim($XMLvars->azienda['legrap_pf_nome'])!='') && (trim($XMLvars->azienda['legrap_pf_nome'])!='')) {
					// se è una persona fisica e ha valorizzato nome e cognome inserisco questi dati
					$results = $xpath->query("//CessionarioCommittente/DatiAnagrafici/Anagrafica")->item(0);
					$el = $domDoc->createElement("Nome", substr(trim($XMLvars->azienda['legrap_pf_nome']), 0, 80));
					$results->appendChild($el);
					$el = $domDoc->createElement("Cognome", substr(trim($XMLvars->azienda['legrap_pf_cognome']), 0, 80));
					$results->appendChild($el);
				} else {
					// Se è una ditta inserisco la denominazione
					$results = $xpath->query("//CessionarioCommittente/DatiAnagrafici/Anagrafica")->item(0);
					$el = $domDoc->createElement("Denominazione", substr(htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->azienda['ragso1'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true) . " " . htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->azienda['ragso2'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true), 0, 80));
					$results->appendChild($el);
				}
				$results = $xpath->query("//CessionarioCommittente/Sede/Indirizzo")->item(0);
				$attrVal = $domDoc->createTextNode(trim($XMLvars->azienda['indspe']));
				$results->appendChild($attrVal);
				$el = $domDoc->createElement("Provincia", strtoupper(trim($XMLvars->azienda['prospe'])));
				$results = $xpath->query("//CessionarioCommittente/Sede")->item(0);
				$results1 = $xpath->query("//CessionarioCommittente/Sede/Nazione")->item(0);
				if ($XMLvars->azienda['country']=='IT'){
					$results->insertBefore($el, $results1);
				}
				$results = $xpath->query("//CessionarioCommittente/Sede/Comune")->item(0);
				$attrVal = $domDoc->createTextNode(trim($XMLvars->azienda['citspe']));
				$results->appendChild($attrVal);
				$results = $xpath->query("//CessionarioCommittente/Sede/CAP")->item(0);
				$attrVal = $domDoc->createTextNode(trim($XMLvars->azienda['capspe']));
				$results->appendChild($attrVal);

				$results = $xpath->query("//CessionarioCommittente/Sede/Nazione")->item(0);
				$attrVal = $domDoc->createTextNode(trim($XMLvars->azienda['country']));
				$results->appendChild($attrVal);

				$rsFatturaElettronicaHeader = $xpath->query("//FatturaElettronicaHeader")->item(0);
				$el = $domDoc->createElement("SoggettoEmittente","CC");
				$rsFatturaElettronicaHeader->appendChild($el);
 				if (strlen($XMLvars->client['capspe']) <> 5 || $XMLvars->client['country']!='IT'){
					$XMLvars->client['capspe']='00000';
				}
        // FINE REVERSE
			} else {
				// nodo 1.4.1.2 codice fiscale del committente
				$el = $domDoc->createElement("CodiceFiscale", trim($XMLvars->client['codfis']));
				$results = $xpath->query("//CessionarioCommittente/DatiAnagrafici")->item(0);
				$results1 = $xpath->query("//CessionarioCommittente/DatiAnagrafici/Anagrafica")->item(0);
				if ($XMLvars->client['codfis'] != '00000000000') {
					if ($XMLvars->client['country'] == 'IT') {
						$results->insertBefore($el, $results1);
					} else {
						// agli stranieri se non ho partita IVA metto quello che trovo nel codice fiscale, se non ho nulla metto un valore fittizio
						if (strlen(str_replace('0', '', $XMLvars->client['pariva'])) == 0) {
							if (strlen($XMLvars->client['codfis']) != 0) {
								$XMLvars->client['pariva'] = $XMLvars->client['codfis'];
							} else {
								$XMLvars->client['pariva'] = '00000000000';
							}
						}
					}
				} else if ($XMLvars->client['country'] != 'IT') {
					// agli stranieri se non ho il codice fiscale metto un valore fittizio in partita IVA
					if (strlen(str_replace('0', '', $XMLvars->client['pariva'])) == 0) {
						$XMLvars->client['pariva'] = '00000000000';
					}
				}
				// nodo 1.4.1.1 partita IVA del committente, se disponibile
				if (!empty($XMLvars->client['pariva']) && ($XMLvars->client['pariva']!='00000000000' || $XMLvars->client['country']!='IT')) {
					if ($XMLvars->client['country']!='IT' && $XMLvars->client['pariva']=='00000000000') {
						$XMLvars->client['pariva'] = '0000000';
					}
					$el = $domDoc->createElement("IdFiscaleIVA", '');
					$results = $el->appendChild($domDoc->createElement('IdPaese', $XMLvars->client['country']));
					$results = $el->appendChild($domDoc->createElement('IdCodice', $XMLvars->client['pariva']));
					$results = $xpath->query("//CessionarioCommittente/DatiAnagrafici")->item(0);
					if ($XMLvars->client['country']=='IT' && $XMLvars->client['codfis']!='00000000000') {
						$results1 = $xpath->query("//CessionarioCommittente/DatiAnagrafici/CodiceFiscale")->item(0);
					} else {
						$results1 = $xpath->query("//CessionarioCommittente/DatiAnagrafici/Anagrafica")->item(0);
					}
					$results->insertBefore($el, $results1);
				}
				if (($XMLvars->client['sexper']!='G') && (trim($XMLvars->client['legrap_pf_nome'])!='') && (trim($XMLvars->client['legrap_pf_nome'])!='')) {
					// se è una persona fisica e ha valorizzato nome e cognome inserisco questi dati
					$results = $xpath->query("//CessionarioCommittente/DatiAnagrafici/Anagrafica")->item(0);
					$el = $domDoc->createElement("Nome", substr(trim($XMLvars->client['legrap_pf_nome']), 0, 80));
					$results->appendChild($el);
					$el = $domDoc->createElement("Cognome", substr(trim($XMLvars->client['legrap_pf_cognome']), 0, 80));
					$results->appendChild($el);
				} else {
					 // Se è una ditta inserisco la denominazione
					$results = $xpath->query("//CessionarioCommittente/DatiAnagrafici/Anagrafica")->item(0);
					$el = $domDoc->createElement("Denominazione", substr(htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->client['ragso1'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true) . " " . htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->client['ragso2'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true), 0, 80));
					$results->appendChild($el);
				}
				$results = $xpath->query("//CessionarioCommittente/Sede/Indirizzo")->item(0);
				$attrVal = $domDoc->createTextNode(trim($XMLvars->client['indspe']));
				$results->appendChild($attrVal);
				$el = $domDoc->createElement("Provincia", strtoupper(trim($XMLvars->client['prospe'])));
				$results = $xpath->query("//CessionarioCommittente/Sede")->item(0);
				$results1 = $xpath->query("//CessionarioCommittente/Sede/Nazione")->item(0);
				if ($XMLvars->client['country']=='IT'){
					$results->insertBefore($el, $results1);
				}
				$results = $xpath->query("//CessionarioCommittente/Sede/Comune")->item(0);
				$attrVal = $domDoc->createTextNode(trim($XMLvars->client['citspe']));
				$results->appendChild($attrVal);
				if (strlen($XMLvars->client['capspe']) <> 5 || $XMLvars->client['country']!='IT'){
					$XMLvars->client['capspe']='00000';
				}
				$results = $xpath->query("//CessionarioCommittente/Sede/CAP")->item(0);
				$attrVal = $domDoc->createTextNode(trim($XMLvars->client['capspe']));
				$results->appendChild($attrVal);
				$results = $xpath->query("//CessionarioCommittente/Sede/Nazione")->item(0);
				$attrVal = $domDoc->createTextNode(trim($XMLvars->client['country']));
				$results->appendChild($attrVal);
				// creo il nodo 1.4.4 <RappresentanteFiscale> se il cliente ne ha uno con partita IVA
				if ($XMLvars->fiscal_rapresentative && $XMLvars->fiscal_rapresentative['pariva'] > 100000) {
					$resfr = $xpath->query("//CessionarioCommittente")->item(0);
					$el = $domDoc->createElement("RappresentanteFiscale","");
					$el1 = $domDoc->createElement("IdFiscaleIVA","");
					$el->appendChild($el1);
					$el2 = $domDoc->createElement("IdPaese", $XMLvars->fiscal_rapresentative['country']);
					$el1->appendChild($el2);
					$el2 = $domDoc->createElement("IdCodice", $XMLvars->fiscal_rapresentative['pariva']);
					$el1->appendChild($el2);
					if (($XMLvars->fiscal_rapresentative['sexper']!='G') && (trim($XMLvars->fiscal_rapresentative['legrap_pf_nome'])!='') && (trim($XMLvars->fiscal_rapresentative['legrap_pf_nome'])!='')) {
						// se è una persona fisica e ha valorizzato nome e cognome inserisco questi dati
						$el1 = $domDoc->createElement("Nome", substr(trim($XMLvars->fiscal_rapresentative['legrap_pf_nome']), 0, 80));
						$el->appendChild($el1);
						$el1 = $domDoc->createElement("Cognome", substr(trim($XMLvars->fiscal_rapresentative['legrap_pf_cognome']), 0, 80));
						$el->appendChild($el1);
					} else {
						 // Se è una ditta inserisco la denominazione
						$el1 = $domDoc->createElement("Denominazione", substr(htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->fiscal_rapresentative['ragso1'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true) . " " . htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->fiscal_rapresentative['ragso2'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true), 0, 80));
						$el->appendChild($el1);
					}
					$resfr->appendChild($el);
				}
			}
      // se cliente e fornitore coincidono allora forzo il tipo documento come autofattura TD27
      if (trim($XMLvars->azienda['pariva'])==trim($XMLvars->client['pariva']) && ( $XMLvars->TipoDocumento=='TD01' || $XMLvars->TipoDocumento=='TD24' || $XMLvars->TipoDocumento=='TD16')) {
        $XMLvars->TipoDocumento='TD27';
      }
      $results = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/TipoDocumento")->item(0);
      $attrVal = $domDoc->createTextNode($XMLvars->TipoDocumento);
      $results->appendChild($attrVal);
      //sempre in euro?
      $results = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Divisa")->item(0);
      $attrVal = $domDoc->createTextNode("EUR");
      $results->appendChild($attrVal);
      $results = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Data")->item(0);
      $attrVal = $domDoc->createTextNode(trim($XMLvars->docRelDate));
      $results->appendChild($attrVal);
      $results = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Numero")->item(0);
      $attrVal = $domDoc->createTextNode(trim($XMLvars->docRelNum));
      $results->appendChild($attrVal);
      $results = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Denominazione")->item(0);
      $attrVal = $domDoc->createTextNode($XMLvars->reverse?substr(htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->client['ragso1'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true) . " " . htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->client['ragso2'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true), 0, 80):trim($XMLvars->intesta1 . " " . $XMLvars->intesta1bis));
      $results->appendChild($attrVal);
      //regime fiscale RF01 valido per il regime fiscale ordinario
      $results = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/RegimeFiscale")->item(0);
      $attrVal = $domDoc->createTextNode($XMLvars->reverse?'RF01':trim($XMLvars->regime_fiscale));
      $results->appendChild($attrVal);
      $results = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Indirizzo")->item(0);
      $attrVal = $domDoc->createTextNode($XMLvars->reverse?trim($XMLvars->client['indspe']):trim($XMLvars->azienda['indspe']));
      $results->appendChild($attrVal);
      $results = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/CAP")->item(0);
      $attrVal = $domDoc->createTextNode($XMLvars->reverse?$XMLvars->client['capspe']:trim($XMLvars->azienda['capspe']));
      $results->appendChild($attrVal);
      $results = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Comune")->item(0);
      $attrVal = $domDoc->createTextNode($XMLvars->reverse?trim($XMLvars->client['citspe']):trim($XMLvars->azienda['citspe']));
      $results->appendChild($attrVal);
      $results = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Provincia")->item(0);
      $attrVal = $domDoc->createTextNode($XMLvars->reverse?$XMLvars->client['prospe']:$XMLvars->azienda['prospe']);
      $results->appendChild($attrVal);
      $results = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Nazione")->item(0);
      $attrVal = $domDoc->createTextNode($XMLvars->reverse?$XMLvars->client['country']:$XMLvars->azienda['country']);
      $results->appendChild($attrVal);
			//IscrizioneREA
      if ($XMLvars->REA_ufficio != "" && $XMLvars->REA_numero != "" && !$XMLvars->reverse) { // ho i dati minimi indispensabili per valorizzare il REA
        $results = $xpath->query("//CedentePrestatore")->item(0);
        $el = $domDoc->createElement("IscrizioneREA","");
        $el1 = $domDoc->createElement("Ufficio", $XMLvars->REA_ufficio);
        $el->appendChild($el1);
        $el1 = $domDoc->createElement("NumeroREA", $XMLvars->REA_numero);
        $el->appendChild($el1);
        if (floatval($XMLvars->REA_capitale) > 1) {
            $el1 = $domDoc->createElement("CapitaleSociale", $XMLvars->REA_capitale);
            $el->appendChild($el1);
        }
        if (strlen($XMLvars->REA_socio) >= 2) {
            $el1 = $domDoc->createElement("SocioUnico", $XMLvars->REA_socio);
            $el->appendChild($el1);
        }
        $el1 = $domDoc->createElement("StatoLiquidazione", $XMLvars->REA_stato);
        $el->appendChild($el1);
        $results->appendChild($el);
      }
    } elseif ($ctrl_doc <> $XMLvars->docRelNum) { // quando cambia il DdT
        // non faccio nulla
    }
    //elenco beni in fattura
    $lines = $XMLvars->getXMLrows();
		$idrig_n_linea[0]=0;
		foreach ($lines AS $key => $rigo) {
			// creo un array per associare l'id_rig al NumeroLinea mi servirà per riferire sui DatiVari
			$idrig_n_linea[$rigo['id_rig']]=$n_linea;
      $nl = false;
			$sc_su_imp['importo_sconto']=0.00;
      switch ($rigo['tiprig']) {
        case "0":       // normale
        case "50":      // normale c/allegato
					$last_pervat = $rigo['pervat'];
					$benserv = $xpath->query("//FatturaElettronicaBody/DatiBeniServizi")->item(0);
          $el = $domDoc->createElement("DettaglioLinee", "\n");
          $el1 = $domDoc->createElement("NumeroLinea", $n_linea);
          $el->appendChild($el1);
					if ($rigo['quanti']*$rigo['prelis']<0 && !$XMLvars->reverse) {
						// se quantità o prezzo negativo si tratta di rigo sconto = SC
						$el1 = $domDoc->createElement("TipoCessionePrestazione", "SC");
						$el->appendChild($el1);
						$rigo['quanti']=abs($rigo['quanti']);
						$rigo['prelis']=abs($rigo['prelis'])*-1;
					}
					$codart=preg_replace("/[^A-Za-z0-9]i/",'',$rigo['codart']);
          if (!empty($codart)) { // ho un codice articolo creo l'elemento
						$el1 = $domDoc->createElement("CodiceArticolo", '');
						$el2 = $domDoc->createElement("CodiceTipo",$rigo['codice_tipo']); // il codice tipo è obbligatorio è stato formattato in precedenza per indicarci se è un bene o un servizio e la categoria merceologica
						$el1->appendChild($el2);
						$el2 = $domDoc->createElement("CodiceValore",$codart); // qui metto il valore del codice vero e proprio e che avevo parsato in precedenza
						$el1->appendChild($el2);
						//$el2 = $domDoc->createElement("Quantita", number_format($rigo['quanti'], 2, '.', ''));
						//$el1->appendChild($el2);
						$el->appendChild($el1);
					}
          if ($rigo['idlotto']!='') { // se ho un lotto di magazzino lo accodo alla descrizione
            $rigo['descri'] .= ' LOTTO: '.$rigo['idlotto'];
            if ( substr($rigo['scadenzalotto'],-4) > 2000 ){ // se sul lotto ho indicato la scadenza accodo pure essa
              $rigo['descri'] .= ' SCAD.'.$rigo['scadenzalotto'];
            }
          }
          $el1 = $domDoc->createElement("Descrizione", substr($rigo['descri'], -1000) );
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("Quantita", number_format($rigo['quanti'], 3, '.', ''));
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("UnitaMisura", $rigo['unimis']);
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("PrezzoUnitario", number_format($rigo['prelis'], $XMLvars->decimal_price, '.', ''));
          $el->appendChild($el1);
					// qualora questo rigo preveda uno sconto
          if (isset($rigo['sconto_su_imponibile'][$rigo['id_rig']])) {
						$sc_su_imp=$rigo['sconto_su_imponibile'][$rigo['id_rig']];
						/* AGGIUNGO GLI EVENTUALI SCONTI, LO SCONTO CHIUSURA VERRA' MESSO IN CASCATA SUI SINGOLI RIGHI */
						if ($sc_su_imp['scorig']>=0.01 || $sc_su_imp['scorig']<=-0.01){
              $el1 = $domDoc->createElement("ScontoMaggiorazione", "");
              $sc1 = $domDoc->createElement("Tipo", $sc_su_imp['tipo']);
              $el1->appendChild($sc1);
              $sc1 = $domDoc->createElement("Percentuale", number_format(round($sc_su_imp['scorig'],2), 2, '.', ''));
              $el1->appendChild($sc1);
							$el->appendChild($el1);
						}
						if ($sc_su_imp['scotes']>=0.01 || $sc_su_imp['scotes']<=-0.01){
              $el1 = $domDoc->createElement("ScontoMaggiorazione", "");
              $sc1 = $domDoc->createElement("Tipo", $sc_su_imp['tipo']);
              $el1->appendChild($sc1);
              $sc1 = $domDoc->createElement("Percentuale", number_format(round($sc_su_imp['scotes'],2), 2, '.', ''));
              $el1->appendChild($sc1);
							$el->appendChild($el1);
						}
					}
          $el1 = $domDoc->createElement("PrezzoTotale", number_format(round($rigo['importo']+$sc_su_imp['importo_sconto'],2), 2, '.', ''));
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("AliquotaIVA", number_format($rigo['pervat'], 2, '.', ''));
          $el->appendChild($el1);
          if (abs($rigo['ritenuta']) > 0.00001) {
              $el1 = $domDoc->createElement("Ritenuta", 'SI');
              $el->appendChild($el1);
          }
          if (abs($rigo['pervat']) < 0.01) {
						$last_natura = $rigo['natura'];
            $el1 = $domDoc->createElement("Natura", $rigo['natura']);
            $el->appendChild($el1);
          }
					if ( $XMLvars->RiferimentoAmministrazione ) {
            $el1 = $domDoc->createElement("RiferimentoAmministrazione", $XMLvars->RiferimentoAmministrazione);
            $el->appendChild($el1);
					}
					if ( !empty($XMLvars->DatiIntento) && $rigo['natura']=='N3.5' ) {
						$el1 = $domDoc->createElement("AltriDatiGestionali", '');
            $el->appendChild($el1);
						$el2 = $domDoc->createElement("TipoDato", 'INTENTO');
						$el1->appendChild($el2);
						$el2 = $domDoc->createElement("RiferimentoTesto",  $XMLvars->DatiIntento['RiferimentoTesto']);
						$el1->appendChild($el2);
						$el2 = $domDoc->createElement("RiferimentoData",  $XMLvars->DatiIntento['RiferimentoData']);
						$el1->appendChild($el2);
					}
					// se è una fattura allegata allo scontrino fiscale
          if ($XMLvars->tesdoc['tipdoc']=='VCO') {
       			$el1 = $domDoc->createElement("AltriDatiGestionali", '');
            $el->appendChild($el1);
            $el2 = $domDoc->createElement("TipoDato", 'SCONTRINO FISCALE');
            $el1->appendChild($el2);
            $el2 = $domDoc->createElement("RiferimentoTesto", 'NUMERO');
            $el1->appendChild($el2);
            $el2 = $domDoc->createElement("RiferimentoNumero", $XMLvars->tesdoc['numdoc']);
            $el1->appendChild($el2);
            $el2 = $domDoc->createElement("RiferimentoData",  $XMLvars->tesdoc['datemi']);
            $el1->appendChild($el2);
          }
          $benserv->appendChild($el);
          $nl = true;
          break;
        case "1":
        case "90": // forfait, vendita cespite
					$last_pervat = $rigo['pervat'];
					$benserv = $xpath->query("//FatturaElettronicaBody/DatiBeniServizi")->item(0);
          $el = $domDoc->createElement("DettaglioLinee", "\n");
          $el1 = $domDoc->createElement("NumeroLinea", $n_linea);
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("Descrizione", substr($rigo['descri'], -1000));
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("PrezzoUnitario", number_format($rigo['importo'], 2, '.', ''));
          $el->appendChild($el1);
					// qualora questo rigo preveda uno sconto
          if (isset($rigo['sconto_su_imponibile'][$rigo['id_rig']])) {
						$sc_su_imp=$rigo['sconto_su_imponibile'][$rigo['id_rig']];
						/* AGGIUNGO GLI EVENTUALI SCONTI, LO SCONTO CHIUSURA VERRA' MESSO IN CASCATA SUI SINGOLI RIGHI */
						if ($sc_su_imp['scorig']>=0.01 || $sc_su_imp['scorig']<=-0.01){
              $el1 = $domDoc->createElement("ScontoMaggiorazione", "");
              $sc1 = $domDoc->createElement("Tipo", $sc_su_imp['tipo']);
              $el1->appendChild($sc1);
              $sc1 = $domDoc->createElement("Percentuale", number_format(round($sc_su_imp['scorig'],2), 2, '.', ''));
              $el1->appendChild($sc1);
							$el->appendChild($el1);
						}
						if ($sc_su_imp['scotes']>=0.01 || $sc_su_imp['scotes']<=-0.01){
              $el1 = $domDoc->createElement("ScontoMaggiorazione", "");
              $sc1 = $domDoc->createElement("Tipo", $sc_su_imp['tipo']);
              $el1->appendChild($sc1);
              $sc1 = $domDoc->createElement("Percentuale", number_format(round($sc_su_imp['scotes'],2), 2, '.', ''));
              $el1->appendChild($sc1);
							$el->appendChild($el1);
						}
					}
          $el1 = $domDoc->createElement("PrezzoTotale", number_format(round($rigo['importo']+$sc_su_imp['importo_sconto'],2), 2, '.', ''));
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("AliquotaIVA", number_format($rigo['pervat'], 2, '.', ''));
          $el->appendChild($el1);
          if (abs($rigo['ritenuta']) > 0.00001) {
              $el1 = $domDoc->createElement("Ritenuta", 'SI');
              $el->appendChild($el1);
          }
          if (abs($rigo['pervat']) < 0.01) {
						$last_natura = $rigo['natura'];
            $el1 = $domDoc->createElement("Natura", $rigo['natura']);
            $el->appendChild($el1);
          }
					if ( !empty($XMLvars->DatiIntento) && $rigo['natura']=='N3.5' ) {
						$el1 = $domDoc->createElement("AltriDatiGestionali", '');
            $el->appendChild($el1);
						$el2 = $domDoc->createElement("TipoDato", 'INTENTO');
						$el1->appendChild($el2);
						$el2 = $domDoc->createElement("RiferimentoTesto",  $XMLvars->DatiIntento['RiferimentoTesto']);
						$el1->appendChild($el2);
						$el2 = $domDoc->createElement("RiferimentoData",  $XMLvars->DatiIntento['RiferimentoData']);
						$el1->appendChild($el2);
					}
          $benserv->appendChild($el);
          $nl = true;
          break;
        case "T":       // trasporto
					$benserv = $xpath->query("//FatturaElettronicaBody/DatiBeniServizi")->item(0);
          $el = $domDoc->createElement("DettaglioLinee", "\n");
          $el1 = $domDoc->createElement("NumeroLinea", $n_linea);
          $el->appendChild($el1);
					$el1 = $domDoc->createElement("TipoCessionePrestazione", 'AC');
          // aggiungo la spesa accessoria al riepilogo
          $XMLvars->SpeseIncassoTrasporti += number_format(round($rigo['importo']+$sc_su_imp['importo_sconto'],2), 2, '.', '');
					$el->appendChild($el1);
          $el1 = $domDoc->createElement("Descrizione", substr($rigo['descri'], -1000));
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("PrezzoUnitario", number_format($rigo['importo'], 2, '.', ''));
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("PrezzoTotale", number_format(round($rigo['importo']+$sc_su_imp['importo_sconto'],2), 2, '.', ''));
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("AliquotaIVA", number_format($rigo['pervat'], 2, '.', ''));
          $el->appendChild($el1);
          if ($rigo['ritenuta'] > 0) {
            $el1 = $domDoc->createElement("Ritenuta", 'SI');
            $el->appendChild($el1);
          }
          if ($rigo['pervat'] <= 0) {
            $el1 = $domDoc->createElement("Natura", $rigo['natura']);
            $el->appendChild($el1);
          }
					if ( !empty($XMLvars->DatiIntento) && $rigo['natura']=='N3.5' ) {
						$el1 = $domDoc->createElement("AltriDatiGestionali", '');
            $el->appendChild($el1);
						$el2 = $domDoc->createElement("TipoDato", 'INTENTO');
						$el1->appendChild($el2);
						$el2 = $domDoc->createElement("RiferimentoTesto",  $XMLvars->DatiIntento['RiferimentoTesto']);
						$el1->appendChild($el2);
						$el2 = $domDoc->createElement("RiferimentoData",  $XMLvars->DatiIntento['RiferimentoData']);
						$el1->appendChild($el2);
					}
          $benserv->appendChild($el);
          $nl = true;
          break;
        case "2": // descrittivo
        case "51": // descrittivo c/allegato
          // a volte viene usato un rigo descrittivo solo per lasciare uno spazio, in questi casi per evitare lo scarto lo valorizzo con un punto
          if (strlen(trim($rigo['descri'])) < 1) {
            $rigo['descri'] = '.';
          }
					$benserv = $xpath->query("//FatturaElettronicaBody/DatiBeniServizi")->item(0);
          $el = $domDoc->createElement("DettaglioLinee", "\n");
          $el1 = $domDoc->createElement("NumeroLinea", $n_linea);
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("Descrizione", substr($rigo['descri'], -1000));
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("PrezzoUnitario", '0.00');
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("PrezzoTotale", '0.00');
          $el->appendChild($el1);
          $el1 = $domDoc->createElement("AliquotaIVA", number_format($XMLvars->descrifae_vat, 2, '.', ''));
          $el->appendChild($el1);
          if ($XMLvars->descrifae_vat <= 0) {
            $el1 = $domDoc->createElement("Natura", $XMLvars->descrifae_natura);
            $el->appendChild($el1);
          }
          $benserv->appendChild($el);
          $nl = true;
          break;
        case "D": // testo
          $rdescri = wordwrap($rigo['descri'],1000,'<t@g>');
          $arrdescri = explode('<t@g>',$rdescri);
          foreach($arrdescri as $vd) {
            $benserv = $xpath->query("//FatturaElettronicaBody/DatiBeniServizi")->item(0);
            $el = $domDoc->createElement("DettaglioLinee", "\n");
            $el1 = $domDoc->createElement("NumeroLinea", $n_linea);
            $el->appendChild($el1);
            $el1 = $domDoc->createElement("Descrizione", $vd);
            $el->appendChild($el1);
            $el1 = $domDoc->createElement("PrezzoUnitario", '0.00');
            $el->appendChild($el1);
            $el1 = $domDoc->createElement("PrezzoTotale", '0.00');
            $el->appendChild($el1);
            $el1 = $domDoc->createElement("AliquotaIVA", number_format($XMLvars->descrifae_vat, 2, '.', ''));
            $el->appendChild($el1);
            if ($XMLvars->descrifae_vat <= 0) {
              $el1 = $domDoc->createElement("Natura", $XMLvars->descrifae_natura);
              $el->appendChild($el1);
            }
            $benserv->appendChild($el);
            if ($XMLvars->ddt_data){
              // è un rigo di ddt devo aggiungere il riferimento alla linea nell'apposito array che ho creato in precedenza
              $XMLvars->DatiDDT[$XMLvars->tesdoc['numdoc']]['RiferimentoNumeroLinea'][]=$n_linea;
            }
            $n_linea++;
          }
          $nl = false;
          break;
      } // fine switch tiprig
			if ($XMLvars->ddt_data && $nl){
				// è un rigo di ddt devo aggiungere il riferimento alla linea nell'apposito array che ho creato in precedenza
				$XMLvars->DatiDDT[$XMLvars->tesdoc['numdoc']]['RiferimentoNumeroLinea'][]=$n_linea;
			}
      if ($nl) {
          $n_linea++;
      }
    }
    $ctrl_doc = $XMLvars->tesdoc['numdoc'];
    $ctrl_fat = $XMLvars->tesdoc['numfat'];
  } // fine while righi
  // ----- CALCOLO TOTALI E RATE DEL PAGAMENTO
  $XMLvars->setXMLtot();
  $totpar = $XMLvars->totimpfat + $XMLvars->totivafat; //totale della fattura al lordo della RDA e dell'IVA
  $totpag = $totpar - $XMLvars->tot_ritenute - $XMLvars->ivasplitpay; // totale a pagare
  if ($XMLvars->impbol >= 0.01 && ($XMLvars->virtual_taxstamp == 1 || $XMLvars->virtual_taxstamp == 2)) { // se si è scelto di assolvere il bollo sia in modo fisico che virtuale
    $totpag = $totpag + $XMLvars->impbol;
    $totpar = $totpar + $XMLvars->impbol;
  }
  $ex = new Expiry;
  $totpagini=$totpag;
  if ($XMLvars->totriport <= -0.01){  // se la fattura contiene dei righi di tipo 3 la cui somma è negativa ( ad esempio quando si vuole stornare dalle rate uno o più note credito) quello tolto dal
    $totpag += $XMLvars->totriport;
  }
  $ratpag = $ex->CalcExpiry($totpag, $XMLvars->tesdoc["datfat"], $XMLvars->pagame['tipdec'], $XMLvars->pagame['giodec'], $XMLvars->pagame['numrat'], $XMLvars->pagame['tiprat'], $XMLvars->pagame['mesesc'], $XMLvars->pagame['giosuc']);
  // echo  "<pre>",print_r($ratpag),echo  "</pre>";
  if ($XMLvars->totriport <= -0.01){  // se la fattura contiene dei righi di tipo 3 la cui somma è negativa ( ad esempio quando si vuole stornare dalle rate uno o più note credito) quello tolto dal calcolo delle rate lo aggiungo come valore ad una nuova scadenze
    $ctrltot=round(($totpagini+$XMLvars->totriport),2);
    $ratpag[] = ['date'=>$XMLvars->tesdoc["datfat"],'amount'=>$XMLvars->totriport];
    if ($ctrltot <= -0.01){ // ho uno storno negativo, salto completamente l'indicazione del pagamento
      $ratpag=[];
    } elseif($ctrltot==0.00){ // fattura stornata esattamente tutta
      $ratpag=[0=>['date'=>$XMLvars->tesdoc["datfat"],'amount'=>$XMLvars->totriport]];
    }
  }

  if ($XMLvars->pagame['numrat'] > 1) {
    $cond_pag = 'TP01';
  } else {
    $cond_pag = 'TP02';
  }
  // --- FINE CALCOLO TOTALI
  // alla fine del ciclo sui righi faccio diverse aggiunte es. causale, bolli, descrizione aggiuntive, e spese di incasso, queste essendo cumulative per diversi eventuali DdT non hanno un riferimento
  if ($XMLvars->DatiVeicoli) {
    $results = $xpath->query("//FatturaElettronicaBody")->item(0);
    $el = $domDoc->createElement("DatiVeicoli", '');
		$el1 = $domDoc->createElement("Data", $XMLvars->DatiVeicoli['Data']);
		$el->appendChild($el1);
		$el1 = $domDoc->createElement("TotalePercorso", $XMLvars->DatiVeicoli['TotalePercorso']);
		$el->appendChild($el1);
    $results->appendChild($el);
  }
  if ($XMLvars->tesdoc['speban'] >= 0.01) {
		$results = $xpath->query("//FatturaElettronicaBody/DatiBeniServizi")->item(0);
    $el = $domDoc->createElement("DettaglioLinee", "\n");
    $el1 = $domDoc->createElement("NumeroLinea", $n_linea);
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("TipoCessionePrestazione", 'AC');
    // aggiungo la spesa accessoria al riepilogo
    $XMLvars->SpeseIncassoTrasporti += number_format(($XMLvars->tesdoc['speban'] * $XMLvars->pagame['numrat']), 2, '.', '');
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("Descrizione", 'SPESE INCASSO');
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("Quantita", number_format($XMLvars->pagame['numrat'],2,'.',''));
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("UnitaMisura", 'N.');
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("PrezzoUnitario", number_format($XMLvars->tesdoc['speban'], 2, '.', ''));
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("PrezzoTotale", number_format(($XMLvars->tesdoc['speban'] * $XMLvars->pagame['numrat']), 2, '.', ''));
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("AliquotaIVA", number_format($XMLvars->expense_pervat['aliquo'], 2, '.', ''));
    $el->appendChild($el1);
    if (floatval($XMLvars->expense_pervat['aliquo']) < 0.1 ) {
      $el1 = $domDoc->createElement("Natura", $XMLvars->expense_pervat['fae_natura']);
      $el->appendChild($el1);
    }
    $results->appendChild($el);
    $n_linea++;
  }
  // eventualemente aggiungo i rimborsi per i bolli, ma solo se sono da addebitare
  if ($XMLvars->impbol >= 0.01 && $XMLvars->chk_taxstamp) {
    $results = $xpath->query("//FatturaElettronicaBody/DatiBeniServizi")->item(0);
    $el = $domDoc->createElement("DettaglioLinee", "\n");
    $el1 = $domDoc->createElement("NumeroLinea", $n_linea);
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("TipoCessionePrestazione", 'AC');
    // aggiungo la spesa accessoria al riepilogo
    $XMLvars->SpeseBolli += number_format($XMLvars->impbol, 2, '.', '');
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("Descrizione", 'RIMBORSO SPESE PER BOLLI ');
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("PrezzoUnitario", number_format($XMLvars->impbol, 2, '.', ''));
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("PrezzoTotale", number_format($XMLvars->impbol, 2, '.', ''));
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("AliquotaIVA", number_format($XMLvars->iva_bollo['aliquo'], 2, '.', ''));
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("Natura", $XMLvars->iva_bollo['fae_natura']);
    $el->appendChild($el1);
    $results->appendChild($el);
    $n_linea++;
  }
  // ... e se voluto anche il rigo descrittivo derivante dalla configurazione avanzata azienda
  if (!empty($XMLvars->descriptive_last_row) && !$XMLvars->reverse ) {
    $results = $xpath->query("//FatturaElettronicaBody/DatiBeniServizi")->item(0);
    $el = $domDoc->createElement("DettaglioLinee", "\n");
    $el1 = $domDoc->createElement("NumeroLinea", $n_linea);
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("Descrizione", $XMLvars->descriptive_last_row);
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("PrezzoUnitario", '0.00');
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("PrezzoTotale", '0.00');
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("AliquotaIVA", number_format($last_pervat, 2, '.', ''));
    $el->appendChild($el1);
    if (abs($last_pervat) < 0.01) {
      $el1 = $domDoc->createElement("Natura", $last_natura);
      $el->appendChild($el1);
    }
    $results->appendChild($el);
    $n_linea++;
  }
  // DatiVari
  $results = $xpath->query("//FatturaElettronicaBody/DatiGenerali")->item(0);
	$dati_vari_nomi=array('','DatiOrdineAcquisto','DatiContratto','DatiConvenzione','DatiRicezione','DatiFattureCollegate');
	ksort($XMLvars->DatiVari); // l'ordine degli elementi è importante altrimenti non passa il controllo
	foreach($XMLvars->DatiVari as $k0 => $v0){
		$el0 = $domDoc->createElement($dati_vari_nomi[$k0], "");
		foreach($v0 as $k1 => $v1){
			if ($k1>0){
				$el1 = $domDoc->createElement('RiferimentoNumeroLinea', $idrig_n_linea[$k1]);
				$el0->appendChild($el1);
			}
			ksort($v1); // l'ordine degli elementi è importante altrimenti non passa il controllo
			foreach($v1 as $k2 => $v2){
				switch ($k2) {
					case "7":       // CodiceCIG
					$el1 = $domDoc->createElement('CodiceCIG', $v2);
					break;
					case "6":       // CodiceCUP
					$el1 = $domDoc->createElement('CodiceCUP', $v2);
					break;
					case "2":       // IdDocumento
					$el1 = $domDoc->createElement('IdDocumento', $v2);
					break;
					case "3":       // Data
					$el1 = $domDoc->createElement('Data', $v2);
					break;
					case "4":       // NumItem
					$el1 = $domDoc->createElement('NumItem', $v2);
					break;
					case "5":       // CodiceCommessaConvenzione
					$el1 = $domDoc->createElement('CodiceCommessaConvenzione', $v2);
					break;
				}
				$el0->appendChild($el1);
			}
			$results->appendChild($el0);
		}
	}

  // DatiFattureCollegate per Reverse Charge
	if ($XMLvars->reverse&&($XMLvars->TipoDocumento=='TD16'||$XMLvars->TipoDocumento=='TD17'||$XMLvars->TipoDocumento=='TD18'||$XMLvars->TipoDocumento=='TD19')) {
		$el0 = $domDoc->createElement($dati_vari_nomi[5], "");
		$el1 = $domDoc->createElement('IdDocumento', htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->tesdoc['numfat'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true) );
		$el0->appendChild($el1);
		$el1 = $domDoc->createElement('Data', $XMLvars->tesdoc['datfat']);
		$el0->appendChild($el1);
		$results->appendChild($el0);
  }

	// DatiSAL
  if (count($XMLvars->DatiSAL)>0) {
		$results = $xpath->query("//FatturaElettronicaBody/DatiGenerali")->item(0);
		foreach ($XMLvars->DatiSAL as $k=>$v) {
			$el = $domDoc->createElement("DatiSAL",'');
			$el1 = $domDoc->createElement("RiferimentoFase", intval($v));
			$el->appendChild($el1);
			$results->appendChild($el);
		}
  }

  if ($XMLvars->ddt_data) {
		$results = $xpath->query("//FatturaElettronicaBody/DatiGenerali")->item(0);
		foreach ($XMLvars->DatiDDT as $k0=>$v0) {
			$el_ddt = $domDoc->createElement("DatiDDT", "");
      $el1 = $domDoc->createElement("NumeroDDT", $k0.'/'.$XMLvars->tesdoc["seziva"]);
      $el_ddt->appendChild($el1);
      $el1 = $domDoc->createElement("DataDDT", $v0['DataDDT']);
      $el_ddt->appendChild($el1);
			foreach ($v0['RiferimentoNumeroLinea'] as $k1=>$v1) {
				$el1 = $domDoc->createElement("RiferimentoNumeroLinea", $v1);
				$el_ddt->appendChild($el1);
			}
			$results->appendChild($el_ddt);
		}
  }
  if ($XMLvars->tot_ritenute > 0) {
    $results = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento")->item(0);
    $el = $domDoc->createElement("DatiRitenuta", "");
    $el1 = $domDoc->createElement("TipoRitenuta", $XMLvars->TipoRitenuta);
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("ImportoRitenuta", number_format($XMLvars->tot_ritenute, 2, '.', ''));
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("AliquotaRitenuta", number_format($XMLvars->azienda['ritenuta'], 2, '.', ''));
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("CausalePagamento", $XMLvars->azienda['causale_pagam_770']);
    $el->appendChild($el1);
    $results->appendChild($el);
  }
  if ($XMLvars->impbol >= 0.01 && $XMLvars->BolloVirtuale) {
    $results = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento")->item(0);
    $el = $domDoc->createElement("DatiBollo", "");
    $el1 = $domDoc->createElement("BolloVirtuale", $XMLvars->BolloVirtuale);
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("ImportoBollo", number_format($XMLvars->impbol, 2, '.', ''));
    $el->appendChild($el1);
    $results->appendChild($el);
  }
  if (count($XMLvars->cassa_prev) >= 1) {
    $results = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento")->item(0);
	  foreach ($XMLvars->cassa_prev as $key => $value) {
      $el = $domDoc->createElement("DatiCassaPrevidenziale", "");
      $el1 = $domDoc->createElement("TipoCassa", $key);
      $el->appendChild($el1);
      $el1 = $domDoc->createElement("AlCassa", number_format($value['AlCassa'], 2, '.', ''));
      $el->appendChild($el1);
      $el1 = $domDoc->createElement("ImportoContributoCassa", number_format($value['ImportoContributoCassa'], 2, '.', ''));
      $el->appendChild($el1);
      $el1 = $domDoc->createElement("ImponibileCassa", number_format($value['ImponibileCassa'], 2, '.', ''));
      $el->appendChild($el1);
      $el1 = $domDoc->createElement("AliquotaIVA", number_format($value['AliquotaIVA'], 2, '.', ''));
      $el->appendChild($el1);
      if ($value['Ritenuta']>=0.01){
        $el1 = $domDoc->createElement("Ritenuta", 'SI');
        $el->appendChild($el1);
      }
      if (substr($value['Natura'],0,1)=='N'){
        $el1 = $domDoc->createElement("Natura", $value['Natura']);
        $el->appendChild($el1);
      }
      $results->appendChild($el);
    }
  }
  //Modifica per il sicoge che richiede obbligatoriamente popolato il punto 2.1.1.9
  $results = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento")->item(0);
  $el = $domDoc->createElement("ImportoTotaleDocumento", number_format($totpar, 2, '.', ''));  // totale fatura al lordo di RDA
  $results->appendChild($el);
  if (count($XMLvars->Causale)>0) {
    $results = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento")->item(0);
		foreach ($XMLvars->Causale as $k=>$v) {
			$el = $domDoc->createElement("Causale",$v);
			$results->appendChild($el);
		}
  }
  $results = $xpath->query("//FatturaElettronicaBody/DatiBeniServizi")->item(0);
  foreach ($XMLvars->cast as $key => $value) {
    $el = $domDoc->createElement("DatiRiepilogo", "\n");
    $el1 = $domDoc->createElement("AliquotaIVA", number_format($value['periva'], 2, '.', ''));
    $el->appendChild($el1);
    if ($value['periva'] < 0.01) {
      $el1 = $domDoc->createElement("Natura", $value['fae_natura']);
      $el->appendChild($el1);
    }
    if ( $XMLvars->SpeseIncassoTrasporti>=0.01 && $key==$XMLvars->tesdoc['expense_vat'] ){ // aggiungo SpeseAccessorie di trasporti e spese incasso
      $el1 = $domDoc->createElement("SpeseAccessorie", number_format($XMLvars->SpeseIncassoTrasporti, 2, '.', ''));
      $el->appendChild($el1);
    } elseif ( $XMLvars->SpeseBolli>=0.01 && $key==$XMLvars->azienda['taxstamp_vat'] ){ // aggiungo SpeseAccessorie dei bolli
      $el1 = $domDoc->createElement("SpeseAccessorie", number_format($XMLvars->SpeseBolli, 2, '.', ''));
      $el->appendChild($el1);
    }
    // necessario per l'elemento 2.2.2.7
    $value['esigibilita'] = 'I'; // I=esigibiltà immediata
    if ($XMLvars->azienda['fiscal_reg'] == 'RF16' || $XMLvars->azienda['fiscal_reg'] == 'RF17') {
      $value['esigibilita'] = 'D';
    }
    if ($value['tipiva'] == 'T') { // è un'IVA non esigibile per split payment PA
      $value['esigibilita'] = 'S'; // S=scissione dei pagamenti
    }
    $el1 = $domDoc->createElement("ImponibileImporto", number_format($value['impcast'], 2, '.', ''));
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("Imposta", number_format($value['ivacast'], 2, '.', ''));
    if ($value['fae_natura'] == 'N1' || $value['fae_natura'] == 'N2' || $value['fae_natura'] == 'N3' || $value['fae_natura'] == 'N4' || $value['fae_natura'] == 'N5' || $value['fae_natura'] == 'N6') {
      //non viene inserito il nodo EsigibilitaIVA
    } else {
      $el->appendChild($el1);
      $el1 = $domDoc->createElement("EsigibilitaIVA", $value['esigibilita']);
    }
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("RiferimentoNormativo", $value['descriz']);
    $el->appendChild($el1);
    $results->appendChild($el);
  }
  if ($XMLvars->sempl_accom) {
    // se è una fattura accompagnatoria qui inserisco anche i dati relativi al trasporto
    $results = $xpath->query("//FatturaElettronicaBody/DatiGenerali")->item(0);
    $el = $domDoc->createElement("DatiTrasporto", "");
		if ($XMLvars->vettore) { // ho un vettore
			$el1 = $domDoc->createElement("DatiAnagraficiVettore", '');
			$el2 = $domDoc->createElement("IdFiscaleIVA", '');
			$el3 = $domDoc->createElement("IdPaese", $XMLvars->vettore['country']);
			$el2->appendChild($el3);
			$el3 = $domDoc->createElement("IdCodice", $XMLvars->vettore['partita_iva']);
			$el2->appendChild($el3);
			$el1->appendChild($el2);
			$el2 = $domDoc->createElement("Anagrafica", '');
			$el3 = $domDoc->createElement("Denominazione",$XMLvars->vettore['ragione_sociale']);
			$el2->appendChild($el3);
			$el1->appendChild($el2);
			$el->appendChild($el1);
		}
		if (strlen(trim($XMLvars->tesdoc['spediz']))>=4){
			$el1 = $domDoc->createElement("MezzoTrasporto", $XMLvars->tesdoc['spediz']);
			$el->appendChild($el1);
		}
    $el1 = $domDoc->createElement("CausaleTrasporto", 'VENDITA');
    $el->appendChild($el1);
		if ($XMLvars->tesdoc['units']>=1){
			$el1 = $domDoc->createElement("NumeroColli", $XMLvars->tesdoc['units']);
			$el->appendChild($el1);
		}
		if (strlen(trim($XMLvars->tesdoc['imball']))>=4){
			$el1 = $domDoc->createElement("Descrizione", $XMLvars->tesdoc['imball']);
			$el->appendChild($el1);
		}
		if (($XMLvars->tesdoc['net_weight']+$XMLvars->tesdoc['gross_weight'])>=0.001){
      $pn=floatval($XMLvars->tesdoc['net_weight']);
      $pl=floatval($XMLvars->tesdoc['gross_weight']);
      $ump='kg';
      if ($pn>1000||$pl>1000){
        $ump='ton';
        $pn=$pn/1000;
        $pl=$pl/1000;
      }
			$el1 = $domDoc->createElement("UnitaMisuraPeso", $ump);
			$el->appendChild($el1);
			if ($XMLvars->tesdoc['gross_weight']>=0.001){
				$el1 = $domDoc->createElement("PesoLordo", number_format($pl,2,'.',''));
				$el->appendChild($el1);
			}
			if ($XMLvars->tesdoc['net_weight']>=0.001){
				$el1 = $domDoc->createElement("PesoNetto",  number_format($pn,2,'.',''));
				$el->appendChild($el1);
			}
    }
    $el1 = $domDoc->createElement("DataInizioTrasporto", substr($XMLvars->tesdoc['initra'], 0, 10));
    $el->appendChild($el1);
    $el1 = $domDoc->createElement("DataOraConsegna", substr($XMLvars->tesdoc['initra'], 0, 10) . 'T' . substr($XMLvars->tesdoc['initra'], 11, 8));
    $el->appendChild($el1);
    $results->appendChild($el);
  }
  if ($XMLvars->TipoDocumento=='TD04' || $XMLvars->reverse || count($ratpag)<1){
    // in caso di nota credito, di reverse charge, e di righi con variazione al totale fattura (tipo 3 ) che stornano oltre il totale fattura salto i DatiPagamento che non sono comunque obbligatori <0.N>
  } else {
    // elementi dei <DatiPagamento> (2.4)
    $results = $xpath->query("//FatturaElettronicaBody")->item(0);
    $el = $domDoc->createElement("DatiPagamento", "");
    $el1 = $domDoc->createElement("CondizioniPagamento", $cond_pag); // 2.4.1
    $el->appendChild($el1);
    $results->appendChild($el);
    foreach ($ratpag as $k => $v) {
      $results = $xpath->query("//FatturaElettronicaBody/DatiPagamento")->item(0);
      $el = $domDoc->createElement("DettaglioPagamento", ''); // 2.4.2
      if ($v['amount'] <= -0.01) { // ho un importo negativo derivante dai righi di tipo 3 negativi, es. storno note di credito
        $el1 = $domDoc->createElement("Beneficiario", 'Credito di '.substr(htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->client['ragso1'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true) . " " . htmlspecialchars(str_replace(chr(0xE2).chr(0x82).chr(0xAC),"",trim($XMLvars->client['ragso2'])), ENT_XML1 | ENT_QUOTES, 'UTF-8', true), 0, 80)); // 2.4.2.1
        $el->appendChild($el1);
        $el1 = $domDoc->createElement("ModalitaPagamento", 'MP22'); // 2.4.2.2
        $el->appendChild($el1);
        $el1 = $domDoc->createElement("DataScadenzaPagamento", $v['date']); // 2.4.2.5
        $el->appendChild($el1);
        $el1 = $domDoc->createElement("ImportoPagamento", number_format(-$v['amount'],2,'.','')); // 2.4.2.6
        $el->appendChild($el1);
      } else {
        $el1 = $domDoc->createElement("Beneficiario",
        htmlspecialchars(trim($XMLvars->intesta1 . " " . $XMLvars->intesta1bis), ENT_XML1 | ENT_QUOTES, 'UTF-8', true)); // 2.4.2.1
        $el->appendChild($el1);
        $el1 = $domDoc->createElement("ModalitaPagamento", $XMLvars->pagame['fae_mode']); // 2.4.2.2
        $el->appendChild($el1);
        $el1 = $domDoc->createElement("DataScadenzaPagamento", $v['date']); // 2.4.2.5
        $el->appendChild($el1);
        $el1 = $domDoc->createElement("ImportoPagamento", number_format($v['amount'],2,'.','')); // 2.4.2.6
        $el->appendChild($el1);
        if ($XMLvars->pagame['tippag'] == 'B' && isset($XMLvars->banapp['codabi'])) { // se il pagamento è una RiBa indico CAB e ABI
            $el1 = $domDoc->createElement("ABI", str_pad($XMLvars->banapp['codabi'], 5, '0', STR_PAD_LEFT)); // 2.4.2.14
            $el->appendChild($el1);
            $el1 = $domDoc->createElement("CAB", str_pad($XMLvars->banapp['codcab'], 5, '0', STR_PAD_LEFT)); // 2.4.2.15
            $el->appendChild($el1);
        } elseif (!empty($XMLvars->banacc['iban'])) { // se il pagamento ha un IBAN associato
            $el1 = $domDoc->createElement("IBAN", $XMLvars->banacc['iban']); // 2.4.2.13
            $el->appendChild($el1);
        }
      }
      $results->appendChild($el);
    }
  }
  // se in configurazione azienda ho scelto di allegare anche il PDF
  if ($XMLvars->attach_pdf_to_fae >= 1 && $pdf_content && $XMLvars->docDescri) {
      // elemento di <Allegati> (2.5)
      $results = $xpath->query("//FatturaElettronicaBody")->item(0);
      $el = $domDoc->createElement("Allegati", "");
      $el1 = $domDoc->createElement("NomeAttachment", $XMLvars->docDescri.'.pdf'); // 2.5.1
      $el->appendChild($el1);
      $el1 = $domDoc->createElement("FormatoAttachment",  'pdf'); // 2.5.3
      $el->appendChild($el1);
      $el1 = $domDoc->createElement("Attachment", base64_encode($pdf_content)); // 2.5.5
      $el->appendChild($el1);
      $results->appendChild($el);
  }
  if(count($XMLvars->ExternalDocs)>=1){
    foreach ($XMLvars->ExternalDocs as $v) {
      // elementi di <Allegati> (2.5)
      $results = $xpath->query("//FatturaElettronicaBody")->item(0);
      $el = $domDoc->createElement("Allegati", "");
      $el1 = $domDoc->createElement("NomeAttachment", $v['oriname'].'.'.$v['ext']); // 2.5.1
      $el->appendChild($el1);
      $el1 = $domDoc->createElement("FormatoAttachment",  $v['ext']); // 2.5.3
      $el->appendChild($el1);
      if (strlen(trim($v['descri'])) >= 1 ){
        $el1 = $domDoc->createElement("DescrizioneAttachment", $v['descri']); // 2.5.4
        $el->appendChild($el1);
      }
      $data = file_get_contents($v['path']);
      $el1 = $domDoc->createElement("Attachment", base64_encode($data)); // 2.5.5
      $el->appendChild($el1);
      $results->appendChild($el);
    }
  }
	$faename_base = 36;
	// faccio l'encode in base 36 per ricavare il progressivo unico di invio
	if ($XMLvars->reverse) {// è una autofattura reverse charge encodo così:
		$faename_base = 62;
		$faename_maxsez = 9;
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$faename_base = 36;
			$faename_maxsez = 5;
		}
		/*  dovrò modificare la matrice in questo con valore fisso "5" sulla prima cifre, e faccio uno shift degli altri
		  ------------------------- SCHEMA DEI DATI PER AUTOFATTURE  ------------------
		  |  VALORE FISSO   |  ANNO DOCUMENTO  | N.REINVII |    NUMERO PROTOCOLLO     |
		  |    INT (2 )     |      INT(1)      |   INT(1)  |        INT(4)            |
		  |       "5"       |        9         |     9     |         9999             |
		  | $data[sezione]  |   $data[anno] $data[fae_reinvii]  $data[protocollo]     |
		  -----------------------------------------------------------------------------
		 */
		$data = [
			'azienda' => $XMLvars->azienda['codice'],
			'sezione' => min($faename_maxsez, $XMLvars->reverse_charge_sez),
			'anno' => '200'.$XMLvars->seziva, // uso la sezione del reverse ( lo passo come anno )
			'fae_reinvii' => substr($XMLvars->docRelDate,3,1), // in reinvii ci passo l'anno
			'protocollo' => intval($XMLvars->fae_reinvii*10000 + $XMLvars->protoc) // sul progressivo ci sarà una cifra in meno perché sulla prima c'è il reinvio
		];
	} else {
		$data = [
			'azienda' => $XMLvars->azienda['codice'],
			'anno' => $XMLvars->docRelDate,
			'sezione' => $XMLvars->seziva,
			'fae_reinvii' => $XMLvars->fae_reinvii,
			'protocollo' => $XMLvars->protoc
		];
	}

	$progressivo_unico_invio = encodeSendingNumber($data, $faename_base);
	$nome_file = 'IT' . $XMLvars->IdCodice . '_' . $progressivo_unico_invio;
	$id_tes = $XMLvars->tesdoc['id_tes'];
	$data_ora_exec = date('Y-m-d H:i:s');
	// se è un reinvio allora faccio l'upload del genitore indicando in filename_son il nome di questo nuovo file
	if ($XMLvars->fae_reinvii >= 1) {
	// faccio l'encode in base 36 per ricavare il progressivo unico di invio
		if ($XMLvars->reverse) {// è una autofattura reverse charge encodo così:
			/*  dovrò modificare la matrice in questo con valore fisso "5" sulla prima cifre, e faccio uno shift degli altri
			  ------------------------- SCHEMA DEI DATI PER AUTOFATTURE  ------------------
			  |  VALORE FISSO   |  ANNO DOCUMENTO  | N.REINVII |    NUMERO PROTOCOLLO     |
			  |    INT (2 )     |      INT(1)      |   INT(1)  |        INT(4)            |
			  |       "59       |        9         |     9     |         9999             |
			  | $data[sezione]  |   $data[anno] $data[fae_reinvii]  $data[protocollo]     |
			  -----------------------------------------------------------------------------
			 */
			$parent = [
				'azienda' => $XMLvars->azienda['codice'],
				'sezione' => min($faename_maxsez, $XMLvars->reverse_charge_sez),
				'anno' => '200'.$XMLvars->seziva, // uso la sezione del reverse ( lo passo come anno )
				'fae_reinvii' => substr($XMLvars->docRelDate,3,1), // in reinvii ci passo l'anno
				'protocollo' => intval(($XMLvars->fae_reinvii-1)*10000 + $XMLvars->protoc) // sul progressivo ci sarà una cifra in meno perché sulla prima c'è il reinvio
			];
		} else {
			$parent = [
				'azienda' => $XMLvars->azienda['codice'],
				'anno' => $XMLvars->docRelDate,
				'sezione' => $XMLvars->seziva,
				'fae_reinvii' => $XMLvars->fae_reinvii-1,
				'protocollo' => $XMLvars->protoc
			];
		}
		$parent_progressivo_unico_invio = encodeSendingNumber($parent, $faename_base);
		$parent_nome_file = 'IT' . $XMLvars->IdCodice . '_' . $parent_progressivo_unico_invio;
		gaz_dbi_query ("UPDATE ".$gTables['fae_flux']." SET `filename_son`='".$nome_file.".xml' WHERE `filename_ori`='".$parent_nome_file . ".xml'");
	}

	if ($name_ziparchive) {
		if ($name_ziparchive != 'from_string.xml') {
			$verifica = gaz_dbi_get_row($gTables['fae_flux'], 'filename_ori', $nome_file . ".xml");
			if ($verifica == false) {
				$valori = array('filename_ori' => $nome_file . ".xml",
					'filename_zip_package'=>$name_ziparchive,
					'id_tes_ref' => $id_tes,
					'exec_date' => $data_ora_exec,
					'received_date' => '',
					'delivery_date' => '',
					'filename_son' => '',
					'id_SDI' => 0,
					'filename_ret' => '',
					'mail_id' => 0,
					'data' => '',
					'flux_status' => 'DI',
					'progr_ret' => '000',
					'flux_descri' => '');
				fae_fluxInsert($valori);
			}
		}
		return $domDoc->saveXML();
	} else {
    if($returnDocument){
      return ["nome_file" => $nome_file . ".xml", "documento" => $domDoc->saveXML()];
    } else{
      $verifica = gaz_dbi_get_row($gTables['fae_flux'], 'filename_ori', $nome_file . ".xml");
      if ($verifica == false) {
        $valori = array('filename_ori' => $nome_file . ".xml",
            'filename_zip_package'=>'',
            'id_tes_ref' => $id_tes,
            'exec_date' => $data_ora_exec,
            'received_date' => '',
            'delivery_date' => '',
            'filename_son' => '',
            'id_SDI' => 0,
            'filename_ret' => '',
            'mail_id' => 0,
            'data' => '',
            'flux_status' => ($XMLvars->FormatoTrasmissione == "FPA") ? '##' : 'DI',
            'n_invio' => $XMLvars->fae_reinvii+1,
            'progr_ret' => '000',
            'flux_descri' => '');
        fae_fluxInsert($valori);
      }
      header("Content-type: text/plain");
      header("Content-Disposition: attachment; filename=" . $nome_file . ".xml");
      print $domDoc->saveXML();
    }
	}
}
?>
