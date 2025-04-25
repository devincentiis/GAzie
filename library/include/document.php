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

class DocContabVars {
  public $gazTimeFormatter;
  public $ecr;
  public $gTables;
  public $layout_pos_logo_on_doc;
  public $descriptive_last_row;
  public $descriptive_last_ddt;
  public $show_artico_composit;
  public $user;
  public $pagame;
  public $caumag;
  public $banapp;
  public $banacc;
  public $vettor;
  public $tableName;
  public $intesta1;
  public $intesta1bis;
  public $intesta2;
  public $intesta3;
  public $intesta4;
  public $intesta5;
  public $codici;
  public $colore;
  public $logo;
  public $decimal_quantity;
  public $decimal_price;
  public $link;
  public $sedelegale;
  public $perbollo;
  public $iva_bollo;
  public $client;
  public $descri_partner;
  public $codice_partner;
  public $cod_univoco;
  public $pec_cliente;
  public $cliente1;
  public $cliente2;
  public $cliente3;
  public $cliente4;
  public $cliente4b;
  public $cliente5;
  public $id_agente;
  public $rs_agente;
  public $name_agente;
  public $partner_dest;
  public $destinazione;
  public $clientSedeLegale;
  public $fiscal_rapresentative;
  public $c_Attenzione;
  public $tesdoc;
  public $min;
  public $ora;
  public $day;
  public $month;
  public $year;
  public $trasporto;
  public $testat;
  public $docRelNum;
  public $docRelDate;
  public $fae_reinvii;
  public $efattura;
  public $withoutPageGroup;
  public $pers_title;
  public $iban;
  public $regime_fiscale;
  public $totimp_body;
  public $body_castle;
  public $taxstamp;
  public $virtual_taxstamp;
  public $tottraspo;
  public $totale;
  public $riporto;
  public $aziendTel;
  public $aziendFax;
  public $azienda;
  public $artico_doc;
  public $roundcastle;
  public $ritenuta;
  public $ritenute;
  public $tot_ritenute;
  public $castel;
  public $impbol;
  public $totroundcastle;
  public $totriport;
  public $speseincasso;
  public $totivasplitpay;
  public $ExternalDoc;
  public $totimpmer;
  public $totimpfat;
  public $totivafat;
  public $tipdoc;
  public $cast;
  public $currency;

    function setData($gTables, $tesdoc, $testat, $tableName, $ecr = false) {
      global $gazie_locale;
      $this->gazTimeFormatter = new IntlDateFormatter($gazie_locale,IntlDateFormatter::FULL,IntlDateFormatter::FULL);
      $this->ecr = $ecr;
      $this->gTables = $gTables;
      $admin_aziend = gaz_dbi_get_row($gTables['aziend'], 'codice', $_SESSION['company_id']);
      $this->layout_pos_logo_on_doc = gaz_dbi_get_row($gTables['company_config'], 'var', 'layout_pos_logo_on_doc')['val'];
      $this->descriptive_last_row = trim(gaz_dbi_get_row($gTables['company_config'], 'var', 'descriptive_last_row')['val'].'');
      $this->descriptive_last_ddt = gaz_dbi_get_row($gTables['company_config'], 'var', 'descriptive_last_ddt')['val'];
      $this->show_artico_composit = gaz_dbi_get_row($gTables['company_config'], 'var', 'show_artico_composit')['val'];
      $this->user = gaz_dbi_get_row($gTables['admin'], "user_name", $_SESSION["user_name"]);
      $this->pagame = gaz_dbi_get_row($gTables['pagame'], "codice", $tesdoc['pagame']);
      if (!$this->pagame) {
        $this->pagame = ['descri'=>'','id_bank'=>0,'tipdec'=>'','giodec'=>0,'numrat'=>1,'tiprat'=>'','mesesc'=>0,'giosuc'=>0,'incaut'=>0,'tippag'=>'D'];
      }
      if (isset($tesdoc['caumag']) && (!is_null($tesdoc['caumag']))) {
          $this->caumag = gaz_dbi_get_row($gTables['caumag'], "codice", $tesdoc['caumag']);
      }
      $banapp = gaz_dbi_get_row($gTables['banapp'], "codice", $tesdoc['banapp']);
      $this->banapp =($banapp)?$banapp:array('descri'=>'');
      $anagrafica = new Anagrafica();
      $this->banacc =($this->pagame)?$anagrafica->getPartner($this->pagame['id_bank']):'';
      $vettor = gaz_dbi_get_row($gTables['vettor'], "codice", $tesdoc['vettor']);
      $this->vettor =($vettor)?$vettor:array('ragione_sociale'=>'','indirizzo'=>'','citta'=>'','provincia'=>'');
      $this->tableName = $tableName;
      $this->intesta1 = $admin_aziend['ragso1'];
      $this->intesta1bis = $admin_aziend['ragso2'];
      $this->intesta2 = $admin_aziend['indspe'] . ' ' . sprintf("%05d", $admin_aziend['capspe']) . ' ' . $admin_aziend['citspe'] . ' (' . $admin_aziend['prospe'] . ')';
      $this->intesta3 = 'Tel.' . $admin_aziend['telefo'] . ' ';
      $this->aziendTel = $admin_aziend['telefo'];
      $this->aziendFax = $admin_aziend['fax'];
      $this->codici = '';
      if ($admin_aziend['codfis'] != '') {
          $this->codici .= 'C.F. ' . $admin_aziend['codfis'] . ' ';
      }
      if ($admin_aziend['pariva']) {
          $this->codici .= 'P.I. ' . $admin_aziend['pariva'] . ' ';
      }
      if (strlen($admin_aziend['REA_ufficio'])>1 && strlen($admin_aziend['REA_numero'])>3 ) {
          $this->codici .= 'R.E.A. ' . $admin_aziend['REA_ufficio'].' '.$admin_aziend['REA_numero'];
      }
      $this->intesta4 = $admin_aziend['e_mail'];
      $this->intesta5 = $admin_aziend['sexper'];
      $this->colore = $admin_aziend['colore'];
      $this->decimal_quantity = $admin_aziend['decimal_quantity'];
      $this->decimal_price = $admin_aziend['decimal_price'];
      $this->logo = $admin_aziend['image'];
      $this->link = $admin_aziend['web_url'];
      // leggo la sede legale dell'azienda
      $this->sedelegale = $admin_aziend['sedleg'];
      $this->perbollo = 0;
      $this->iva_bollo = gaz_dbi_get_row($gTables['aliiva'], "codice", $admin_aziend['taxstamp_vat']);
      $this->client = $anagrafica->getPartner($tesdoc['clfoco']);
      if(!$this->client){
        $this->client=['ragso1'=>': ','ragso2'=>'','pec_email'=>'','fe_cod_univoco'=>'','fe_cod_univoco'=>'','indspe'=>'','citspe'=>'','country'=>'IT','capspe'=>'','prospe'=>'','pariva'=>'','pariva'=>'','codfis'=>'','sedleg'=>'','fiscal_rapresentative_id'=>'','stapre'=>'','id_currency'=>$admin_aziend['id_currency']];
      }
      if ($this->client['id_currency'] < 1 ) { $this->client['id_currency'] = 1; }
      $this->currency = gaz_dbi_get_row($gTables['currencies'], "id", $this->client['id_currency']);
      $this->descri_partner =($this->client['country']=="IT")?'Cliente':'Customer';
      if (substr($tesdoc['clfoco'], 0, 3) == $admin_aziend['masfor']) {
          $this->descri_partner =  ($this->client['country']=="IT")?'Fornitore':'Supplier';
      }
      $this->codice_partner = intval(substr($tesdoc['clfoco'], 3, 6));
      $this->cod_univoco = $this->client['fe_cod_univoco'];
      $this->pec_cliente = $this->client['pec_email'];
      $this->cliente1 = $this->client['ragso1'];
      $this->cliente2 = $this->client['ragso2'];
      $this->cliente3 = $this->client['indspe'];
      if (!empty($this->client['citspe'])) {
        if ($this->client['country'] == 'IT') {
          $this->client['capspe'] = sprintf("%05d",$this->client['capspe']);
          $this->cliente4 = (($this->client['capspe']=='00000') ? '' : $this->client['capspe'].' ') . strtoupper($this->client['citspe']) . ' ' . strtoupper($this->client['prospe']);
        } else {
          $this->cliente4 = (empty($this->client['capspe']) ? '' : $this->client['capspe'].' ') . strtoupper($this->client['citspe']) . ' ' . strtoupper($this->client['prospe']);
        }
      } else {
          $this->cliente4 = '';
      }
      $country = gaz_dbi_get_row($gTables['country'], "iso", $this->client['country']);
      if ($country && $this->client['country'] != 'IT') {
          $this->cliente4b = strtoupper($country['name']);
      } else {
          $this->cliente4b = 'Italy';
      }
      if (!empty($this->client['pariva'])) {
          $this->cliente5 = 'P.I. ' . $this->client['pariva'] . ' ';
      } else {
          $this->cliente5 = '';
      }
      if ( $this->client['country']!="IT" && $this->client['country']!="" ) {
          $this->cliente5 = 'Fiscal num. ' . $this->client['country'] .$this->client['codfis'];
      } else if (!empty($this->client['pariva'])) { //se c'e' la partita iva
          if (!empty($this->client['codfis']) and $this->client['codfis'] == $this->client['pariva']) {
              $this->cliente5 = 'C.F. e P.I. ' . $this->client['country'] . $this->client['codfis'];
          } elseif (!empty($this->client['codfis']) and $this->client['codfis'] != $this->client['pariva']) {
              $this->cliente5 = 'C.F. ' . $this->client['codfis'] . ' P.I. ' . $this->client['country'] . $this->client['pariva'];
          } else { //per es. se non c'e' il codice fiscale
              $this->cliente5 = ' P.I. ' . $this->client['country'] . $this->client['pariva'];
          }
      } else { //se  NON c'e' la partita iva
          $this->cliente5 = '';
          if (!empty($this->client['codfis'])) {
              $this->cliente5 = 'C.F. ' . $this->client['codfis'];
          }
      }
      // variabile e' sempre un array
      $this->id_agente = gaz_dbi_get_row($gTables['agenti'], 'id_agente', $tesdoc['id_agente']);
      $this->rs_agente = ($this->id_agente)?$anagrafica->getPartner($this->id_agente['id_fornitore']):'';
      $this->name_agente = ($this->id_agente)?substr($this->rs_agente['ragso1'] . " " . $this->rs_agente['ragso2'], 0, 47):'';
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
      $this->clientSedeLegale = ((trim($this->client['sedleg']) != '') ? preg_split("/\n/", trim($this->client['sedleg'])) : array());
      $this->fiscal_rapresentative = false;
      if ($this->client['fiscal_rapresentative_id'] > 0) {
         $this->fiscal_rapresentative = gaz_dbi_get_row($gTables['anagra'], "id", $this->client['fiscal_rapresentative_id']);
      }
      if (isset($tesdoc['c_a'])) {
          $this->c_Attenzione = $tesdoc['c_a'];
      } else {
          $this->c_Attenzione = '';
      }
      //$this->client = $anagrafica->getPartner($tesdoc['clfoco']);
      $this->tesdoc = $tesdoc;
      $this->min = substr($tesdoc['initra'], 14, 2);
      $this->ora = substr($tesdoc['initra'], 11, 2);
      $this->day = substr($tesdoc['initra'], 8, 2);
      $this->month = substr($tesdoc['initra'], 5, 2);
      $this->year = substr($tesdoc['initra'], 0, 4);
      $this->trasporto = $tesdoc['traspo'];
      $this->testat = $testat;
      $this->docRelNum = $this->tesdoc["numdoc"];    // Numero del documento relativo
      $this->docRelDate = $this->tesdoc["datemi"];    // Data del documento relativo
      $this->fae_reinvii = '';
      if (isset($tesdoc['fattura_elettronica_reinvii'])) {
        $this->fae_reinvii = $this->tesdoc["fattura_elettronica_reinvii"];
      }
      $this->efattura='';
      switch ($tesdoc["tipdoc"]) {
          case "FAD":
          case "FAI":
          case "FAA":
          case "FAF":
          case "FAP":
          case "FAQ":
          case "FNC":
          case "FND":
              $this->docRelNum = $this->tesdoc["numfat"];
              $this->docRelDate = $this->tesdoc["datfat"];
			// in caso di fattura elettronica ricavo il nome del file
			if (substr($tesdoc['datfat'], 0, 4)>=2019 ) { // dal 2019 valorizzo il nome della e-fattura
				// faccio l'encode in base 36 per ricavare il progressivo unico di invio
				$data = array('azienda' => $admin_aziend['codice'],
							  'anno' => $this->docRelDate,
      						  'sezione' => $this->tesdoc["seziva"],
							  'fae_reinvii'=> $this->fae_reinvii,
							  'protocollo' => $this->tesdoc["protoc"]);
				$this->efattura = encodeSendingNumber($data, 36);
			}
              break;
          case "VCO":
              $this->docRelNum = $this->tesdoc["numfat"];
              $this->docRelDate = $this->tesdoc["datfat"];
			// in caso di fattura elettronica ricavo il nome del file
			if (substr($tesdoc['datfat'], 0, 4)>=2019 ) { // dal 2019 valorizzo il nome della e-fattura
				// faccio l'encode in base 36 per ricavare il progressivo unico di invio
				$data = array('azienda' => $admin_aziend['codice'],
							  'anno' => $this->docRelDate,
      						  'sezione' => $this->tesdoc["seziva"],
							  'fae_reinvii'=> $this->fae_reinvii+4, // sulle fatture allegate allo scontrino per non far coincidere il progressivo unico invio
							  'protocollo' => $this->tesdoc["numfat"]);
				$this->efattura = "IT" . $admin_aziend['codfis'] . "_".encodeSendingNumber($data, 36).'.xml';
			}
              $ecr = gaz_dbi_get_row($gTables['cash_register'], "id_cash", $tesdoc['id_contract']);
			$this->destinazione = '-';

              $this->ecr=($ecr)?$ecr['descri']:'';
              break;
          case "DDT":
          case "DDL":
          case "DDR":
          case "DDV":
          case "DDY":
          case "DDS":
          case "DDX":
          case "DDZ":
          case "DDW":
          case "DDD":
          case "DDJ":
          case "DDC":
          case "DDM":
          case "DDO":

          default:
              $this->docRelNum = $this->tesdoc["numdoc"];    // Numero del documento relativo
              $this->docRelDate = $this->tesdoc["datemi"];    // Data del documento relativo
      }
      $this->withoutPageGroup = false;
      if ( $this->client['country']!=="IT") {
          $this->pers_title = 'Dear';
      } else {
          $this->pers_title = 'Spett.le';
      }
      $admin_aziend['other_email']='';
      // se ho la mail in testata documento la inserisco sui dati aziendali per poterla passare alla funzione sendMail
      if (isset($tesdoc["email"])&&strlen($tesdoc["email"])>10){
        $admin_aziend['other_email']=$tesdoc["email"];
      }
          $this->azienda = $admin_aziend;
      if ($tesdoc['tipdoc'] == 'AFA' || $tesdoc['tipdoc'] == 'AFT') {
        $clfoco = gaz_dbi_get_row($gTables['clfoco'], "codice", $tesdoc['clfoco']);
        $this->iban = $clfoco['iban'];
      }
          $this->artico_doc = array(); // accumulatore referenze ai documenti degli articoli eventualemente da allegare
      // ATTRIBUISCO UN EVENTUALE REGIME FISCALE DIVERSO DALLA CONFIGURAZIONE AZIENDA SE LA SEZIONE IVA E' LEGATO AD ESSO TRAMITE IL RIGO var='sezione_regime_fiscale' IN gaz_XXXcompany_config
      $this->regime_fiscale=$this->azienda['fiscal_reg'];
      if ($fr=getRegimeFiscale($this->tesdoc["seziva"])) $this->regime_fiscale=$fr;
    }

    function initializeTotals() {
        // definisco le variabili dei totali
        $this->totimp_body = 0;
        $this->body_castle = array();
        $this->taxstamp = 0;
        $this->virtual_taxstamp = 0;
        $this->tottraspo = 0;
    }

    function open_drawer() { // apre il cassetto dell'eventuale registratore di cassa
        if ($this->ecr) {
            if (!empty($this->ecr['driver'])) {
                $ticket_printer = new $this->ecr['driver'];
                @$ticket_printer->set_serial($this->ecr['serial_port']);
                @$ticket_printer->open_drawer();
            }
        }
    }

    function getTicketRow() {
        // in caso di scontrino il calcolo dev'essere fatto scorporando dal totale l'IVA
        $rs_rig = gaz_dbi_dyn_query("*", $this->gTables[$this->tableName], "id_tes = $this->testat", "id_rig asc");
        $this->totale = 0;
        $this->riporto = 0;
        $results = array();
        while ($rigo = gaz_dbi_fetch_array($rs_rig)) {
            // aggiungo i dati dei lotti, false se non presenti
            $rigo['identifier'] = false;
            $rigo['expiry'] = false;
            $checklot=gaz_dbi_get_row($this->gTables['movmag'],'id_mov',$rigo['id_mag']);
            if ($checklot && strlen ($checklot['id_lotmag'])>0){
              $getlot=gaz_dbi_get_row($this->gTables['lotmag'],'id',$checklot['id_lotmag']);
              if (isset ($getlot['identifier']) && strlen ($getlot['identifier'])>0){
                if (intval ($getlot['expiry'])>0){
                  $rigo['identifier'] = $getlot['identifier'];
                  $rigo['expiry'] = $getlot['expiry'];
                } else {
                  $rigo['identifier'] = $getlot['identifier'];
                }
              }
            }
            $rigo['importo'] = 0;
            $rigo['totale'] = 0;
            //calcolo importo rigo
            if ($rigo['tiprig'] <= 1) {     // se del tipo normale o forfait
                if ($rigo['tiprig'] == 0) { // tipo normale
                    $rigo['totale'] = CalcolaImportoRigo($rigo['quanti'], $rigo['prelis'], array($rigo['sconto'], $this->tesdoc['sconto'], -$rigo['pervat']));
                } else {                    // tipo forfait
                    $rigo['totale'] = CalcolaImportoRigo(1, $rigo['prelis'], -$rigo['pervat']);
                }
                $rigo['importo'] = round($rigo['totale'] / (1 + $rigo['pervat'] / 100), 2);
                if (!isset($this->castel[$rigo['codvat']])) {
                    $iva = gaz_dbi_get_row($this->gTables['aliiva'], "codice", $rigo['codvat']);
                    $this->castel[$rigo['codvat']]['iva'] = 0.00;
                    $this->castel[$rigo['codvat']]['descri'] = $iva['descri'];
                    $this->castel[$rigo['codvat']]['importo'] = 0.00;
                }
                $this->castel[$rigo['codvat']]['importo']+=$rigo['importo'];
                $this->castel[$rigo['codvat']]['iva']+=$rigo['totale'] - $rigo['importo'];
                $this->totale+=$rigo['totale'];
            } else if ($rigo['tiprig'] == 3){
              $rigo['totale'] = $rigo['prelis'];
              $this->riporto += $rigo['prelis'];
            }
            $results[] = $rigo;
        }
        //inoltre devo settare la descrizione del misuratore fiscale
        $this->ecr = gaz_dbi_get_row($this->gTables['cash_register'], 'id_cash', $this->tesdoc['id_contract']);

        return $results;
    }

    function getRigo() {
        $from = $this->gTables[$this->tableName] . ' AS rs
                 LEFT JOIN ' . $this->gTables['aliiva'] . ' AS vat
                 ON rs.codvat=vat.codice';
        $rs_rig = gaz_dbi_dyn_query('rs.*,vat.tipiva AS tipiva', $from, "rs.id_tes = " . $this->testat, "id_tes DESC, id_rig");
        $this->tottraspo += $this->trasporto;
        if ($this->taxstamp < 0.01 && $this->tesdoc['taxstamp'] >= 0.01) {
            $this->taxstamp = $this->tesdoc['taxstamp'];
        }
        $this->roundcastle = [];
        $this->riporto = 0.00;
        $this->ritenuta = 0.00;
        $results = array();
        while ($rigo = gaz_dbi_fetch_array($rs_rig)) {
          // Antonio Germani - se c'è un codice a barre valorizzo barcode
          $art = gaz_dbi_get_row( $this->gTables['artico'], 'codice', $rigo['codart']);
          if ($art && intval($art['barcode'])>0){
            $rigo['barcode']=$art['barcode'];
          } else {
            $rigo['barcode']="";
          }
          // aggiungo i dati dei lotti, vuoti se non presenti
          $rigo['identifier'] = '';
          $rigo['expiry'] = '';
          $checklot=gaz_dbi_get_row($this->gTables['movmag'],'id_mov',$rigo['id_mag']);
          if ($checklot && strlen ($checklot['id_lotmag'])>0){
            $getlot=gaz_dbi_get_row($this->gTables['lotmag'],'id',$checklot['id_lotmag']);
            if (isset ($getlot['identifier']) && strlen ($getlot['identifier'])>0){
              if (intval ($getlot['expiry'])>0){
                  $rigo['identifier'] = $getlot['identifier'];
                  $rigo['expiry'] = $getlot['expiry'];
                } else {
                  $rigo['identifier'] = $getlot['identifier'];
              }
            }
          }
          $rigo['durability_mu'] = '';
          $rigo['durability'] = '';
          if (isset($art['durability_mu']) && ($art['durability_mu']==">" || $art['durability_mu']=="<")){ // se impostato accodo la durabilità alla descrizione serve per gli agroalimentari
            $rigo['durability_mu'] = $art['durability_mu'];
            $rigo['durability'] = $art['durability'];
          }
          // Antonio de Vincentiis - se l'articolo ha un documento passo la referenza files_id_doc
          $checkdoc=false;
          if ($rigo['tiprig']==0) {
              $checkdoc = gaz_dbi_get_row($this->gTables['files'], 'table_name_ref', 'artico', "AND item_ref = '".$rigo['codart']."'");
              if ( $checkdoc ){
                  $this->artico_doc[$rigo['codart']]='doc/'.$checkdoc['id_doc'].'.'.$checkdoc['extension'];
              }
          }
          $from = $this->gTables['orderman'] . ' AS om
                 LEFT JOIN ' . $this->gTables['tesbro'] . ' AS tb
                 ON om.id_tesbro=tb.id_tes';
          $rs_orderman = gaz_dbi_dyn_query('om.*,tb.datemi', $from, "om.id = " .  intval($rigo['id_orderman']));
          $rigo['orderman_data'] = gaz_dbi_fetch_array($rs_orderman);
          $rigo['orderman_descri']=($rigo['orderman_data'])?$rigo['orderman_data']['description']:'';
          if ($rigo['tiprig'] <= 1 || $rigo['tiprig'] == 4 || $rigo['tiprig'] == 50 || $rigo['tiprig'] == 90) {
            $tipodoc = substr($this->tesdoc["tipdoc"], 0, 1);
            $rigo['importo'] = CalcolaImportoRigo($rigo['quanti'], $rigo['prelis'], $rigo['sconto']);
            $v_for_castle = CalcolaImportoRigo($rigo['quanti'], $rigo['prelis'], array($rigo['sconto'], $this->tesdoc['sconto']));
            if ($rigo['tiprig'] == 1) {
              $rigo['importo'] = CalcolaImportoRigo(1, $rigo['prelis'], 0);
              $v_for_castle = CalcolaImportoRigo(1, $rigo['prelis'], $this->tesdoc['sconto']);
            } elseif ($rigo['tiprig'] == 4) {
                $rigo['importo'] = round($rigo['provvigione']*$rigo['prelis']/100,2);
                $v_for_castle = $rigo['importo'] ;
            } elseif ($rigo['tiprig'] == 50) { // normale con allegato
              // accumulo il file da allegare e lo indico al posto del codice articolo
              $attach_path = ($this->tableName=='rigdoc')?'doc/'.$rigo['id_rig'].'_rigdoc_':'rigbrodoc_';
              $doc=$this->getExtDoc($rigo['id_rig'],$attach_path);
              if (!$doc) { // se non ho trovato il file lo metto come tipo normale
                $rigo['tiprig'] = 0;
                // $rigo['descri'] = 'FILE MANCANTE';
              }
            } elseif ($rigo['tiprig'] == 90) {
                $rigo['importo'] = CalcolaImportoRigo(1, $rigo['prelis'], 0);
                $v_for_castle = CalcolaImportoRigo(1, $rigo['prelis'], $this->tesdoc['sconto']);
                $asset = gaz_dbi_get_row($this->gTables['assets'], 'acc_fixed_assets', $rigo['codric'], "AND type_mov = '1'");
                $rigo['codart'] = $asset['id'] . ' - ' . $asset['descri'] . ' (' . $rigo['codric'] . ')';
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
          } elseif ($rigo['tiprig'] == 6 || $rigo['tiprig'] == 7 || $rigo['tiprig'] == 8) {
            $body_text = gaz_dbi_get_row($this->gTables['body_text'], "id_body", $rigo['id_body_text']);
            $rigo['descri'] = $body_text['body_text'];
          } elseif ($rigo['tiprig'] == 3) {
            $this->riporto += $rigo['prelis'];
          } elseif ($rigo['tiprig'] == 91) {
            $this->roundcastle[$rigo['codvat']] = $rigo['prelis'];
          } elseif ($rigo['tiprig'] == 51) { // descrittivo con allegato
            // accumulo il file da allegare e lo indico al posto del codice articolo
            $attach_path = ($this->tableName=='rigdoc')?'doc/'.$rigo['id_rig'].'_rigdoc_':'rigbrodoc_';
            $doc=$this->getExtDoc($rigo['id_rig'],$attach_path);
            if (!$doc) { // se non ho trovato il file lo metto come tipo normale
              $rigo['tiprig'] = 2;
              $rigo['descri'] = 'FILE MANCANTE';
            }
          }
          if ($this->tesdoc['tipdoc']=='AFA' && $rigo['tiprig'] <= 2 && strlen($rigo['descri'])>70  ){
            /* 	se la descrizione no la si riesce a contenere in un rigo (es.fattura elettronica d'acquisto)
              aggiungo righi descrittivi
            */
            $descrizione_nuova='';
            $nuovi_righi=array();
            $n_r=explode(' ',$rigo['descri']);
            foreach($n_r as $v){
              if (strlen($descrizione_nuova)<=60){ // se  la descrizione è ancora abbastanza corta la aggiungo
                $descrizione_nuova .= ' '.$v;
              } else {
                // i righi iniziali sono aggiunti e definiti descrittivi
                $nuovi_righi[]=array('tiprig'=>2,'codart'=>'','descri'=>$descrizione_nuova,'quanti'=>0, 'unimis'=>'','prelis'=>0,'sconto'=>0,'prelis'=>0,'pervat'=>0,'codric'=>0,'provvigione'=>0,'ritenuta'=>0,'id_order'=>0,'id_mag'=>0,'id_orderman'=>0);
                // riparto con un nuovo valore di descrizione
                $descrizione_nuova = $v;
              }
            }
            // quando esco dal ciclo sull'ultimo rigo rimane dello stesso tipo originale
            $rigo['descri']=$descrizione_nuova;
            $nuovi_righi[]=$rigo;
            foreach($nuovi_righi as $v_nr) { // riattraverso l'array dei nuovi righi e sull'ultimo
              $results[] = $v_nr;
            }
          } else {
            $results[] = $rigo;
          }
        }
        return $results;
    }

    function setTotal() {
        $calc = new Compute();
        $this->totivafat = 0.00;
        $this->totimpfat = 0.00;
        $this->totimpmer = 0.00;
        $this->tot_ritenute = $this->ritenuta;
        $this->virtual_taxstamp = $this->tesdoc['virtual_taxstamp'];
        $this->impbol = 0.00;
        $this->totroundcastle = $this->roundcastle;
        $this->totriport = $this->riporto;
        $this->speseincasso = ((isset($this->tesdoc['speban']))?$this->tesdoc['speban']:0) * ((isset($this->pagame['numrat']))?$this->pagame['numrat']:1);
        $this->cast =[];
        if (!isset($this->castel)) {
            $this->castel = [];
        }
        if (!isset($this->totimp_body)) {
            $this->totimp_body = 0;
        }
        $this->totimpmer = $this->totimp_body;
        $this->totimp_body = 0;
        $somma_spese = $this->tottraspo + $this->speseincasso + $this->tesdoc['spevar'];
        $calc->add_value_to_VAT_castle($this->body_castle, $somma_spese, $this->tesdoc['expense_vat']);
        if ($this->tesdoc['stamp'] > 0) {
            $calc->payment_taxstamp($calc->total_imp + $this->totriport + $calc->total_vat - $calc->total_isp - $this->tot_ritenute + $this->taxstamp, $this->tesdoc['stamp'], $this->tesdoc['round_stamp'] * $this->pagame['numrat']);
            $this->impbol = $calc->pay_taxstamp;
        }
        $this->totimpfat = $calc->total_imp;
        $this->totivafat = $calc->total_vat;
        $this->totivasplitpay = $calc->total_isp;
        // aggiungo gli eventuali bolli al castelletto
        if ($this->virtual_taxstamp == 0 || $this->virtual_taxstamp == 3) { //  se è a carico dell'emittente non lo aggiungo al castelletto IVA
            $this->taxstamp = 0.00;
        }
        if ($this->impbol >= 0.01 || $this->taxstamp >= 0.01) {
            $calc->add_value_to_VAT_castle($calc->castle, $this->taxstamp + $this->impbol, $this->azienda['taxstamp_vat']);
        }
        if (count($this->roundcastle)>=1){ // ci sono stati dei tiprig = 91 per arrotondamenti IVA su castelletto
          $calc->round_VAT_castle($calc->castle,$this->roundcastle);
        }
        $this->cast = $calc->castle;
        $this->riporto = 0;
        $this->totroundcastle = $calc->totroundcastle;
        $this->ritenute = 0;
        $this->roundcastle = [];
        $this->castel = [];
    }

    function getExtDoc($id_rig, $prefix) {
      /* con questa funzione faccio il push sull'accumulatore dei righi contenenti "documenti esterni" da allegare al pdf
		  riprendo il nome del file relativo al documento e lo aggiungo alla matrice solo se il file esiste, prima di chiamare
		  questo metodo dovrò settare $this->id_rig
      */
      if (!isset($this->ExternalDoc)) {
        $this->ExternalDoc = [];
      }
      $r=false;
      $files = glob( DATA_DIR . 'files/' . $this->azienda['codice'].'/'.$prefix.'*.*');
      foreach($files as $file) {
        $fd = pathinfo($file);
        if ($prefix=='rigbrodoc_'){
          if ($fd['filename'] == $prefix.($prefix=='rigbrodoc_'?$id_rig:'')) {
            $r=['file'=> $file, 'oriname' => $fd['basename'],'ext' => $fd['extension']];
          }
          $this->ExternalDoc[$id_rig] = $r;
        } else {
          $e=explode('_rigdoc_',$fd['filename']);
          if ($e[0] == $id_rig) {
            $r=['file'=> $file, 'oriname' => $e[1],'ext' => $fd['extension']];
            $this->ExternalDoc[$id_rig] = $r;
          }
        }
      }
      // in ExternalDocs troverò gli eventuali documenti da allegare
      return $r;
    }
}

function createDocument($testata, $templateName, $gTables, $rows = 'rigdoc', $dest = false, $lang_template=false, $template=true) {
    if ($templateName=='Parcella') {
      $templateName='FatturaSemplice';
    }
    $templates = array('Received' => 'received',
        'CartaIntestata' => 'carta_intestata',
        'Lettera' => 'lettera',
        'FatturaAcquisto' => 'fattura_acquisto',
        'FatturaImmediata' => 'fattura_immediata',
        'PreventivoCliente' => 'preventivo_cliente',
        'OrdineCliente' => 'ordine_cliente',
        'OrdineWeb' => 'ordine_web',
        'FatturaSemplice' => 'fattura_semplice',
        'FatturaAllegata' => 'fattura_allegata',
        'Scontrino' => 'scontrino',
        'OrdineFornitore' => 'ordine_fornitore',
        'OrdineAcquistoProduzioni' => 'ordine_acquisto_produzioni',
        'PreventivoFornitore' => 'preventivo_fornitore',
        'InformativaPrivacy' => 'informativa_privacy',
        'RichiestaPecSdi' => 'richiesta_pecsdi',
        'NominaResponsabile'=>'nomina_responsabile',
        'NominaResponsabileEsterno'=>'nomina_responsabile_esterno',
        'NominaIncaricatoInterno'=>'nomina_incaricato_interno',
        'RegolamentoPrivacy'=>'privacy_regol',
        'DDT' => 'ddt',
        'Etichette' => 'etichette',
        'CMR' => 'cmr',
        'Ticket'=>'ticket',
        'Maintenance'=>'maintenance',
        'BookingSummary' => 'booking_summary'
    );
  if (!array_key_exists($templateName, $templates)){
    $templates[$templateName] = $templateName;
  }
	// Antonio Germani - seleziono quale template utilizzare per le ricevute fiscali in base alla configurazione azienda
	if ($templateName=='Received'){
		$stampa_ricevute = gaz_dbi_get_row($gTables['company_config'], 'var', 'received_template');
		if (strlen(trim($stampa_ricevute['val']))>2){
			$templates['Received']="received_".$stampa_ricevute['val'];
		}
	}
	// Antonio de Vincentiis - se sulla variabile "dda_A5" della configurazione avanzata azienda ho 1 allora seleziono il template con due DDT A5 affiancati su di un foglio A4
	if ($templateName=='DDT'){
		$ddt_A5 = gaz_dbi_get_row($gTables['company_config'], 'var', 'ddt_A5');
		if (intval($ddt_A5['val'])>=1){
			$templates['DDT']='ddt2xA5';
		}
	}
    $config = new Config;
    $configTemplate = new configTemplate;
    if ($lang_template) {
		$ts=$configTemplate->template;
		$configTemplate->setTemplateLang($lang_template);
		if (empty($ts)){$configTemplate->template=substr($configTemplate->template, 1);}
    }
	$lh=(($dest && $dest == 'H')?'_lh':''); // eventuale scelta di stampare su carta intestata, aggiungo il suffisso "lh";
	require_once ("../../config/templates" . ($configTemplate->template ? '.' . $configTemplate->template : '') . '/' . $templates[$templateName] .$lh. '.php');
    $pdf = new $templateName();
    $docVars = new DocContabVars();
    $docVars->setData($gTables, $testata, $testata['id_tes'], $rows, false);
    $docVars->initializeTotals();
    $pdf->setVars($docVars, $templateName);
    $pdf->setTesDoc();
    $pdf->setCreator('GAzie - ' . $docVars->intesta1);
    $pdf->setAuthor($docVars->user['user_lastname'] . ' ' . $docVars->user['user_firstname']);
    $pdf->setTitle($templateName);
    $pdf->setTopMargin(79);
    $pdf->setHeaderMargin(5);
    $pdf->Open();
    $pdf->pageHeader();
    $pdf->compose();
    $pdf->pageFooter();
    $doc_name = preg_replace("/[^a-zA-Z0-9]+/", "_", $docVars->intesta1 . '_' . $pdf->tipdoc) . '.pdf';
    // aggiungo all'array con indice 'azienda' altri dati
    $docVars->azienda['cliente1']=$docVars->cliente1;
    $docVars->azienda['doc_name']=$pdf->tipdoc.'.pdf';
    if ($dest && $dest != 'H' && $dest != 'X') { // è stata richiesta una e-mail
      if ($dest!=='E'){// se ho un indirizzo e-mail
        $docVars->client['e_mail']=$dest;// lo impongo per l'invio
      }
      $dest = 'S';     // Genero l'output pdf come stringa binaria
      // Costruisco oggetto con tutti i dati del file pdf da allegare
      $content = new StdClass;
      $content->urlfile=false;
      $content->name = $doc_name;
      $content->string = $pdf->Output($doc_name, $dest);
      $content->encoding = "base64";
      $content->mimeType = "application/pdf";
      $gMail = new GAzieMail();
      if ( $gMail->sendMail($docVars->azienda, $docVars->user, $content, $docVars->client, '', $template) ) {
        // memorizzo l'invio per questa email
        switch ( substr($testata['tipdoc'],0,2) ) {
          case 'VP': // preventivo cliente
          case 'VO': // ordine cliente
          case 'AP': // preventivo fornitore
          case 'AO': // ordine fornitore
            // sulla tabella tesbro ho già una colonna "email" dove memorizzo l'indirizzo al quale è stato spedito, al momento viene usata solo dal modulo acquisti (preventivi/ordini a fornitore) ma si potrebbe pensare di estendere il sistema anche sul modulo vendite
            $tabella_da_aggiornare = 'tesbro';
            $email_elem='ord';
            break;
          case 'FA': // fattura di vendita, parcella, ecc
          case 'FN': // nota credito o debito  vendita
            $tabella_da_aggiornare = 'tesdoc';
            $email_elem='fat';
            break;
          case 'DD': // documento di trasporto
            $tabella_da_aggiornare = 'tesdoc';
            $email_elem='ddt';
            break;
          case 'XF': // Reverse charge
            $tabella_da_aggiornare = 'tesdoc';
            $email_elem='fat';
            break;
          default: // non rientra nella casistica gestita al momento quindi non aggiorno custom_field
            $tabella_da_aggiornare = FALSE;
            break;
        }
        if ( $templateName == 'DDT' ) { // sul db potrei avere il tipdoc = FAD ma sto inviando una email
          $email_elem='ddt';
        }
        if ( $tabella_da_aggiornare ) { // è un tipo di documento per cui è previstorichiedo di aggiornare la colonna custom_field c
          $gaz_custom_field = gaz_dbi_get_single_value( $gTables[$tabella_da_aggiornare], 'custom_field', 'id_tes = '.$testata['id_tes'] );
          if ($gaz_custom_field !== NULL && $gaz_custom_data = json_decode( $gaz_custom_field, true)){
            $gaz_custom_data['email'][$email_elem] = date("d-m-Y h:i:s");
          }else{
            $gaz_custom_data['email'][$email_elem] = date("d-m-Y h:i:s");
          }
          $gaz_custom_field = json_encode($gaz_custom_data);
          gaz_dbi_table_update ($tabella_da_aggiornare, array(0=>'id_tes',1=>$testata['id_tes']), array('custom_field'=>$gaz_custom_field));
        }
      }
    } elseif ($dest && $dest == 'X') { // è stata richiesta una stringa da allegare
      $dest = 'S';     // Genero l'output pdf come stringa binaria
      $content=$pdf->Output($doc_name, $dest);
      return ($content);
    } else { // va all'interno del browser
      if ($testata['tipdoc']=='AOR'){
        // in caso di ordine a fornitore che non viene inviato via mail al fornitore ma solo al browser cambio la descrizione del file per ricordare a chi è stato fatto*/
        $doc_name = preg_replace("/[^a-zA-Z0-9]+/", "_", $docVars->cliente1 . '_' . $pdf->tipdoc) . '.pdf';
      }
      $pdf->Output($doc_name);
    }
}

function createMultiDocument($results, $templateName, $gTables, $dest = false, $lang_template=false, $template=true) {
    if ($templateName=='Parcella' || empty($templateName)) {
      $templateName='FatturaSemplice';
    }
    $templates = array('Received' => 'received',
        'CartaIntestata' => 'carta_intestata',
        'Lettera' => 'lettera',
        'FatturaAcquisto' => 'fattura_acquisto',
        'FatturaImmediata' => 'fattura_immediata',
        'PreventivoCliente' => 'preventivo_cliente',
        'OrdineCliente' => 'ordine_cliente',
        'OrdineWeb' => 'ordine_web',
        'FatturaSemplice' => 'fattura_semplice',
        'FatturaAllegata' => 'fattura_allegata',
        'Scontrino' => 'scontrino',
        'OrdineFornitore' => 'ordine_fornitore',
        'PreventivoFornitore' => 'preventivo_fornitore',
        'InformativaPrivacy' => 'informativa_privacy',
        'DDT' => 'ddt',
        'BookingSummary' => 'booking_summary'
    );
    $config = new Config;
    $configTemplate = new configTemplate;
    if ($lang_template) {
			$ts=$configTemplate->template;
			$configTemplate->setTemplateLang($lang_template);
			if (empty($ts)){$configTemplate->template=substr($configTemplate->template, 1);}
    }
    require("../../config/templates" . ($configTemplate->template ? '.' . $configTemplate->template : '') . '/' . $templates[$templateName] . '.php');
    $pdf = new $templateName();
    $docVars = new DocContabVars();
    $pdf->SetTitle($templateName);
    $pdf->SetTopMargin(79);
    $pdf->SetHeaderMargin(5);
    $ctrlprotoc = 0;
    $acczip=[];
    while ($tesdoc = gaz_dbi_fetch_array($results)) {
        //se il cliente non e' lo stesso di prima
        $ref = $tesdoc['protoc'];
        if ($templateName == 'DDT'|| $templateName == 'Scontrino') {
            $ref = $tesdoc['numdoc'];
        }
				$robj='rigdoc';
        if (strtolower(substr($templateName,0,3))=='ord'|strtolower(substr($templateName,0,3))=='pre') { // ordini o preventivi vado su rigbro
            $ref = $tesdoc['numdoc'];
            $robj ='rigbro';
        }
        if ($ref <> $ctrlprotoc && $ctrlprotoc > 0) {
          $pdf->pageFooter();
          if($dest=='Z'){
            $doc_name = preg_replace("/[^a-zA-Z0-9]+/", "_", $docVars->cliente1 . '_' . $pdf->tipdoc) . '.pdf';
            $pdf->Output(dirname(__DIR__,2).'/data/files/'.$docVars->azienda['codice'].'/'.$doc_name, 'F');
            $acczip[]=$doc_name;
            $pdf = new $templateName();
            $pdf->SetTitle($templateName);
            $pdf->SetTopMargin(79);
            $pdf->SetHeaderMargin(5);
            $pdf->setCreator('GAzie - ' . $docVars->intesta1);
            $pdf->setAuthor($docVars->user['user_lastname'] . ' ' . $docVars->user['user_firstname']);
            $pdf->Open();
          }
        }
        // Inizio pagina
        $testat = $tesdoc['id_tes'];
        $docVars->setData($gTables,$tesdoc,$testat,$robj);
        $docVars->initializeTotals();
        $pdf->setVars($docVars, $templateName);
        $pdf->setTesDoc();
        if ($ctrlprotoc == 0) {
            $pdf->setCreator('GAzie - ' . $docVars->intesta1);
            $pdf->setAuthor($docVars->user['user_lastname'] . ' ' . $docVars->user['user_firstname']);
            $pdf->Open();
        }
        //aggiungo una pagina
        $pdf->pageHeader();
        $ctrlprotoc = $tesdoc['protoc'];
        if ($templateName == 'DDT' || $templateName == 'Scontrino') {
            $ctrlprotoc = $tesdoc['numdoc'];
        }
        $testat = $tesdoc['id_tes'];
        $pdf->docVars->setData($gTables,$tesdoc,$testat,$robj);
        $pdf->compose();
    }
    $pdf->pageFooter();
    if ($dest=='E') { // è stata richiesta una e-mail
        $dest = 'S';     // Genero l'output pdf come stringa binaria
        // Costruisco oggetto con tutti i dati del file pdf da allegare
        $content = new stdClass();
        $content->urlfile=false;
        $content->name = $docVars->intesta1 . '_' . $templateName . '_n.' . $docVars->docRelNum . '_del_' . gaz_format_date($docVars->docRelDate) . '.pdf';
        $content->string = $pdf->Output($docVars->intesta1 . '_' . $templateName . '_n.' . $docVars->docRelNum . '_del_' . gaz_format_date($docVars->docRelDate) . '.pdf', $dest);
        $content->encoding = "base64";
        $content->mimeType = "application/pdf";
        $gMail = new GAzieMail();
        $gMail->sendMail($docVars->azienda, $docVars->user, $content, $docVars->client, '', $template);
    } elseif ($dest=='Z') { // è stato richiesto un pacchetto zip
        $doc_name = preg_replace("/[^a-zA-Z0-9]+/", "_", $docVars->cliente1 . '_' . $pdf->tipdoc) . '.pdf';
        $pdf->Output(dirname(__DIR__,2).'/data/files/'.$docVars->azienda['codice'].'/'.$doc_name, 'F');
        $acczip[]=$doc_name;
        CreateZipDocuments($acczip,$docVars->azienda);
    } elseif ($dest == 'X') { // è stata richiesta una stringa da allegare
        $dest = 'S';     // Genero l'output pdf come stringa binaria
        $doc_name = $docVars->intesta1 . '_' . $templateName . '_n.' . $docVars->tesdoc['numfat'] . '/' . $docVars->tesdoc['seziva'] . '_del_' . gaz_format_date($docVars->tesdoc['datfat']) . '.pdf';
        $content=$pdf->Output($doc_name, $dest);
        return ($content);
    } else { // va all'interno del browser
        $pdf->Output();
    }
}

function createInvoiceFromDDT($result, $gTables, $dest = false, $lang_template=false, $template=true) {
    $templateName = "FatturaDifferita";

    $config = new Config;
    $configTemplate = new configTemplate;
    if ($lang_template) {
		$ts=$configTemplate->template;
		$configTemplate->setTemplateLang($lang_template);
		if (empty($ts)){$configTemplate->template=substr($configTemplate->template, 1);}
    }
    require_once("../../config/templates" . ($configTemplate->template ? '.' . $configTemplate->template : '') . '/fattura_semplice.php');
    $pdf = new FatturaSemplice();
    $docVars = new DocContabVars();
    //$pdf->SetPageFormat($config->getValue('page_format'));
    $pdf->SetTitle('Fatture Differite da DDT');
    $pdf->SetTopMargin(79);
    $pdf->SetHeaderMargin(5);
    $pdf->Open();
    $ctrlprotoc = 0;
    $n = 0;
    while ($tesdoc = gaz_dbi_fetch_array($result)) {
        $idtes_arr[]=$tesdoc['id_tes'];
        //se il cliente non e' lo stesso di prima
        if ($tesdoc['protoc'] <> $ctrlprotoc) {
            $n++;
            //se non e' piu' lo stesso cliente e non e' il primo Ddt stampo il piede della fattura
            if ($ctrlprotoc <> 0) {
                $pdf->pageFooter();
            }
            // Inizio pagina
            // se non e' il tipo di documento stampabile da questo modulo ... va a casa
            if ($tesdoc['tipdoc'] <> 'FAD') {
                header("Location: report_docven.php");
                exit;
            }

            $testat = $tesdoc['id_tes'];
            $docVars->setData($gTables, $tesdoc, $testat, 'rigdoc');
            $docVars->initializeTotals();
            $pdf->setVars($docVars);
            $pdf->setTesDoc();
            if ($ctrlprotoc == 0) {
                $pdf->setCreator('GAzie - ' . $docVars->intesta1);
                $pdf->setAuthor($docVars->user['user_lastname'] . ' ' . $docVars->user['user_firstname']);
                $pdf->Open();
            }
            //aggiungo una pagina
            $pdf->pageHeader();
            $ctrlprotoc = $tesdoc['protoc'];
        }
        $testat = $tesdoc['id_tes'];
        $pdf->docVars->setData($gTables, $tesdoc, $testat, 'rigdoc');
        $pdf->compose();
    }
    if ($n > 1) { // è una stampa con molte fatture
        $doc_name = $docVars->intesta1 . '_Fatture_differite_da_DdT.pdf';
		$doc_name_email = "Fatture differite da Ddt.pdf";
    } else { // è la stampa di una sola fattura
        $doc_name = preg_replace("/[^a-zA-Z0-9]+/", "_", $docVars->intesta1 . '_' . $pdf->tipdoc) . '.pdf';
		$doc_name_email = $pdf->tipdoc . '.pdf';
    }
    $pdf->pageFooter();
    if ($dest && $dest!='X') { // è stata richiesta una e-mail
        if ($dest!=='E'){// se ho un indirizzo e-mail
          $docVars->client['e_mail']=$dest;// lo impongo per l'invio
        }
        $dest = 'S';     // Genero l'output pdf come stringa binaria
        // Costruisco oggetto con tutti i dati del file pdf da allegare
        if (!isset($content)) {
            $content = new stdClass;
        }
        $content->urlfile=false;
        $content->name = $doc_name;
        $content->string = $pdf->Output($doc_name, $dest);
        $content->encoding = "base64";
        $content->mimeType = "application/pdf";
        $docVars->azienda['doc_name'] = $doc_name_email;
        $gMail = new GAzieMail();
        if ( $gMail->sendMail($docVars->azienda, $docVars->user, $content, $docVars->client, '', $template) ) {// se l'invio e-mail è avvenuto con successo
          // ne memorizzo l'invio in tesdoc
          foreach ( $idtes_arr as $idtes) {
            $gaz_custom_field = gaz_dbi_get_single_value( $gTables['tesdoc'], 'custom_field', 'id_tes = '.$idtes );
            if ($gaz_custom_field !== NULL && $gaz_custom_data = json_decode( $gaz_custom_field, true)){
              $gaz_custom_data['email']['fat'] = date("d-m-Y h:i:s");
            }else{
              $gaz_custom_data['email']['fat'] = date("d-m-Y h:i:s");
            }
            $gaz_custom_field = json_encode($gaz_custom_data);
            gaz_dbi_table_update ('tesdoc', array(0=>'id_tes',1=>$idtes), array('custom_field'=>$gaz_custom_field));
          }
        }
    } elseif ($dest && $dest == 'X') { // è stata richiesta una stringa da allegare
        $dest = 'S';     // Genero l'output pdf come stringa binaria
        $content=$pdf->Output($doc_name, $dest);
        return ($content);
    } else { // va all'interno del browser
        $pdf->Output($doc_name);
    }
}

function createInvoiceACQFromDDT($result, $gTables, $dest = false, $lang_template=false, $template=true) {

    $templateName = "FatturaDifferita";

    $config = new Config;
    $configTemplate = new configTemplate;
    if ($lang_template) {
		$ts=$configTemplate->template;
		$configTemplate->setTemplateLang($lang_template);
		if (empty($ts)){$configTemplate->template=substr($configTemplate->template, 1);}
    }
    require_once("../../config/templates" . ($configTemplate->template ? '.' . $configTemplate->template : '') . '/fattura_acquisto.php');
    $pdf = new FatturaAcquisto();
    $docVars = new DocContabVars();
    //$pdf->SetPageFormat($config->getValue('page_format'));
    $pdf->SetTitle('Fatture Differite da DDT');
    $pdf->SetTopMargin(79);
    $pdf->SetHeaderMargin(5);
    $pdf->Open();
    $ctrlprotoc = 0;
    $n = 0;
    while ($tesdoc = gaz_dbi_fetch_array($result)) {
        //se il cliente non e' lo stesso di prima
        if ($tesdoc['protoc'] <> $ctrlprotoc) {
            $n++;
            //se non e' piu' lo stesso cliente e non e' il primo Ddt stampo il piede della fattura
            if ($ctrlprotoc <> 0) {
                $pdf->pageFooter();
            }
            // Inizio pagina
            // se non e' il tipo di documento stampabile da questo modulo ... va a casa
            if ($tesdoc['tipdoc'] <> 'AFT') {
                header("Location: report_docacq.php");
                exit;
            }

            $testat = $tesdoc['id_tes'];
            $docVars->setData($gTables, $tesdoc, $testat, 'rigdoc');
            $docVars->initializeTotals();
            $pdf->setVars($docVars);
            $pdf->setTesDoc();
            if ($ctrlprotoc == 0) {
                $pdf->setCreator('GAzie - ' . $docVars->intesta1);
                $pdf->setAuthor($docVars->user['user_lastname'] . ' ' . $docVars->user['user_firstname']);
                $pdf->Open();
            }
            //aggiungo una pagina
            $pdf->pageHeader();
            $ctrlprotoc = $tesdoc['protoc'];
        }
        $testat = $tesdoc['id_tes'];
        $pdf->docVars->setData($gTables, $tesdoc, $testat, 'rigdoc');
        $pdf->compose();
    }
    if ($n > 1) { // è una stampa con molte fatture
        $doc_name = $docVars->intesta1 . '_Fatture_differite_da_DdT.pdf';
		$doc_name_email = "Fatture differite da Ddt.pdf";
    } else { // è la stampa di una sola fattura
        $doc_name = preg_replace("/[^a-zA-Z0-9]+/", "_", $docVars->intesta1 . '_' . $pdf->tipdoc) . '.pdf';
		$doc_name_email = $pdf->tipdoc . '.pdf';
    }
    $pdf->pageFooter();
    if ($dest) { // è stata richiesta una e-mail
        if ($dest!=='E'){// se ho un indirizzo e-mail
          $docVars->client['e_mail']=$dest;// lo impongo per l'invio
        }
        $dest = 'S';     // Genero l'output pdf come stringa binaria
        // Costruisco oggetto con tutti i dati del file pdf da allegare
        if (!isset($content)) {
            $content = new stdClass;
        }
        $content->urlfile=false;
        $content->name = $doc_name;
        $content->string = $pdf->Output($doc_name, $dest);
        $content->encoding = "base64";
        $content->mimeType = "application/pdf";
        $docVars->azienda['doc_name'] = $doc_name_email;
        $gMail = new GAzieMail();
        $gMail->sendMail($docVars->azienda, $docVars->user, $content, $docVars->client, '', $template);
    } elseif ($dest && $dest == 'X') { // è stata richiesta una stringa da allegare
        $dest = 'S';     // Genero l'output pdf come stringa binaria
        $content=$pdf->Output($doc_name, $dest);
        return ($content);
    } else { // va all'interno del browser
        $pdf->Output($doc_name);
    }
}

function CreateZipDocuments($pdfDocs,$admin_aziend) {// crea un file .zip contenente i file che gli vengono passati nell'array $pdfDocs
	if (count($pdfDocs) > 0) {
		$zip = new ZipArchive;
		$zipname = substr(str_replace(" ","-",addslashes($admin_aziend['ragso1'])), 0, 30).'_documenti_del_'.date("Y-m-d_H-i").".zip";// il nome del pacchetto
		$zipnameurl=dirname(__DIR__,2).'/data/files/tmp/'.$zipname;
		$res = $zip->open($zipnameurl, ZipArchive::CREATE);
		if ($res === TRUE) {
			foreach ($pdfDocs as $doc){
				$fn_ori = dirname(__DIR__,2).'/data/files/'.$admin_aziend['codice'].'/'.$doc;
				$zip->addFile($fn_ori,$doc);
        //unlink($fn_ori);
			}
			$zip->close();
      header('Content-Type:application/zip');
      header('Content-disposition: attachment; filename='.$zipname);
      readfile($zipnameurl);
      // delete file tmp
      unlink($zipnameurl);
		} else {
			echo "Creazione zip fallita";
		}
	}
}

?>
