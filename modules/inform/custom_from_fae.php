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
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
$gForm = new informForm();
$msg = ['err'=>[],'war'=>[]];
$tipdoc_conv=array('TD01'=>'FAI','TD02'=>'FAA','TD03'=>'FAQ','TD04'=>'FNC','TD05'=>'FND','TD06'=>'FAP','TD24'=>'FAD','TD25'=>'FND','TD26'=>'FAF');
$preview = false; // visualizza dopo upload
$iszip = false;

function searchPagame($modalitapagamento='',$deadlines=[]) {
  global $gTables;
  $codpag=false;
  $numrat=count($deadlines);
  // si potrebbe fare meglio, ad es controllare giodec e tiprat, per il momento mi limito a questo che fa match con più probabilità, altrimenti dovrei controllare i più "prossimi" a parità di rate/giorni
  $rs_pagame = gaz_dbi_dyn_query('codice', $gTables['pagame'],"fae_mode ='".$modalitapagamento."' AND numrat = ".count($deadlines),"codice",0,1);
  $rs = gaz_dbi_fetch_array($rs_pagame);
  $r = $rs?$rs['codice']:0;
  return $r;
}

if (!isset($_POST['fattura_elettronica_original_name'])) { // primo accesso nessun upload
	$form['fattura_elettronica_original_name'] = '';
	$form['dirextract'] = '';
	$form['id_customer_group'] = 0; //tutti
	$form['codpag'] = 0;
	$form['gencontract'] = 0;
	$form['genartico'] = 0;
} else { // accessi successivi
	$form['fattura_elettronica_original_name'] = filter_var($_POST['fattura_elettronica_original_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$form['dirextract'] = filter_var($_POST['dirextract'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$form['id_customer_group'] = intval($_POST['id_customer_group']);
	$form['codpag'] = intval($_POST['codpag']);
	$form['gencontract'] = intval($_POST['gencontract']);
	$form['genartico'] = intval($_POST['genartico']);
	if (isset($_POST['Submit_file'])) { // conferma invio upload file
    if (!empty($_FILES['userfile']['name'])) {
      if ( $_FILES['userfile']['type'] == "application/pkcs7-mime" || $_FILES['userfile']['type'] == "text/xml" || $_FILES['userfile']['type'] == "application/zip"|| $_FILES['userfile']['type'] == "application/x-zip-compressed" ) {
        if (move_uploaded_file($_FILES['userfile']['tmp_name'], DATA_DIR.'files/' . $admin_aziend['codice'] . '/' . $_FILES['userfile']['name'])) { // nessun errore
          $form['fattura_elettronica_original_name'] = $_FILES['userfile']['name'];
          $preview = true;
        } else { // no upload
          $msg['err'][] = 'no_upload';
        }
        if ($_FILES['userfile']['type'] == "application/zip"|| $_FILES['userfile']['type'] == "application/x-zip-compressed" ) {
          $dirextract =  'unzipped'.date("YmdHis");
          $iszip = true;
          $zip = new ZipArchive;
          $res = $zip->open( DATA_DIR.'files/' . $admin_aziend['codice'] . '/' .$form['fattura_elettronica_original_name']);
          if ($res === TRUE) {
            $form['dirextract'] = $dirextract;
            $zip->extractTo( DATA_DIR. 'files/' . $admin_aziend['codice'] . '/' .$dirextract.'/' );
            $zip->close();
            //echo 'extraction successful';
          } else {
            //echo 'extraction error';
          }
        }
      } else { // mime del file non valido
        $msg['err'][] = 'filmim';
      }
		} else {
			$msg['err'][] = 'no_upload';
		}
	} elseif (isset($_POST['Submit_form'])) { // ho  confermato l'inserimento
    if ($form['codpag']<=0){
 			$msg['err'][] = 'no_codpag';
    }
    if(count($msg['err'])<=0){
      $anagrafica = new Anagrafica();
      $last = $anagrafica->queryPartners('*', "codice BETWEEN " . $admin_aziend['mascli'] . "000000 AND " . $admin_aziend['mascli'] . "999999", "codice DESC", 0, 1);
      $codice = $last[0]['codice'];
      if ($form['gencontract']>=1) { // la funzione contractUpdate mi serve dentro al ciclo for (vedi sotto)
        require_once("../vendit/lib.data.php");
        $vc=[];
        $vc['vat_section']=1;
        $vc['doc_type']='FAI';
        $vc['months_duration']=60;
        $vc['tacit_renewal']=1;
        $rs_last_contract = gaz_dbi_dyn_query('doc_number', $gTables['contract'], 1,'doc_number DESC',0,1);
        $last = gaz_dbi_fetch_array($rs_last_contract);
        $vc['doc_number'] = $last ? ($last['doc_number']+1) : 1;
      }
      $rs_last_catmer = gaz_dbi_dyn_query('codice', $gTables['catmer'], 1,'codice DESC',0,1);
      $lastcatmer = gaz_dbi_fetch_array($rs_last_catmer);
      $newcatmer = $lastcatmer? $lastcatmer['codice']:0;

      foreach ($_POST['rows'] as $v) {
        $jsonartico=json_decode(html_entity_decode($v['jsonartico']));
        $v['codfis']=substr($v['codfis'],0,16);
        $v['pariva']=substr($v['pariva'],0,11);
        $v['datnas']='2004-01-27';
        $rs_anagra = gaz_dbi_dyn_query('id', $gTables['anagra'], "( pariva <> '' AND pariva > 0 AND pariva = '" . $v['pariva'] . "' ) OR ( codfis <> '' AND codfis = '" . $v['codfis'] . "' )", "id", 0, 1);
        $partner_with_same_cfpi = gaz_dbi_fetch_array($rs_anagra);
        if (!$partner_with_same_cfpi) { // per evitare che se sul pacchetto zip ci sono più fatture dello stesso cliente questo venga duplicato
          $codice ++;
          $v['codice']=$codice;
          // security parsing
          $v['codpag']=$v['codpag']>=1?$v['codpag']:$form['codpag'];
          $v['ragso1']=filter_var(substr($v['ragso1'],0,100), FILTER_UNSAFE_RAW );
          $v['legrap_pf_nome']=filter_var(substr($v['legrap_pf_nome'],0,50), FILTER_UNSAFE_RAW );
          $v['legrap_pf_cognome']=filter_var(substr($v['legrap_pf_cognome'],0,50), FILTER_UNSAFE_RAW );
          $v['sexper']=substr($v['sexper'],0,1);
          $v['capspe']=substr($v['capspe'],0,5);
          $v['indspe']=filter_var($v['indspe'], FILTER_UNSAFE_RAW );
          $v['citspe']=strtoupper(filter_var(substr($v['citspe'],0,50), FILTER_UNSAFE_RAW ));
          $v['prospe']=substr($v['prospe'],0,2);
          $v['country']=substr($v['country'],0,2);
          $v['codpag']=intval($v['codpag']);
          $v['id_customer_group']=intval($form['id_customer_group']);
          $v['impfattura']=floatval($v['impfattura']);
          $v['datfattura']=substr($v['datfattura'],0,10);
          $v['descriprima']=filter_var(substr($v['descriprima'],0,100), FILTER_UNSAFE_RAW );
          $v['amountprima']=floatval($v['amountprima']);
          $anagrafica->insertPartner($v);
          // inserimento contratto se richiesto
          if ($form['gencontract']>=1) {
            $vc['id_customer']=$codice;
            $vc['conclusion_date']=$v['datfattura'];
            $vc['start_date']=$v['datfattura'];
            $vc['last_reassessment']=$v['datfattura'];
            switch ($form['gencontract']) {
              case "1": // mensile primo rigo
                $vc['periodicity']=1;
                $vc['current_fee']=$v['amountprima'];
              break;
              case "2": // mensile totale
                $vc['periodicity']=1;
                $vc['current_fee']=$v['impfattura'];
              break;
              case "3": // trimestrale primo rigo
                $vc['periodicity']=3;
                $vc['current_fee']=$v['amountprima'];
              break;
              case "4": // trimestrale totale
                $vc['periodicity']=3;
                $vc['current_fee']=$v['impfattura'];
              break;
            }
            $vc['initial_fee']=$vc['current_fee'];
            $vc['payment_method']=$v['codpag'];
            $vc['body_text']=$v['descriprima']; // valorizzo con il testo del primo rigo della fattura;
            $vc['status']='ASTEXT'; // uso il testo del contratto
            $ultimo_id=contractUpdate($vc);
            $ultimo_id_body=bodytextInsert(['table_name_ref'=>'contract','id_ref'=>$ultimo_id,'body_text'=>$vc['body_text'],'lang_id'=>$admin_aziend['id_language']]);
            gaz_dbi_put_row($gTables['contract'], 'id_contract', $ultimo_id, 'id_body_text', $ultimo_id_body);
            $vc['doc_number']++;
          }
        }
        // non lo faccio per il momento, ma se sotto valorizzassi il form con un json dei righi (codice,descrizione, prezzo, unità di misura) in $_POST mi ritroverei i dati per poter popolare anche le anagrafiche degli articoli
        if ($form['genartico']>=1) {
          foreach ($jsonartico as $jsona) {
            foreach ($jsona as $jv) {
              //var_dump($jv);
              // inserisco l'artico sul database ma solo se non ne ho uno con lo stesso codice
              $numinsert=gaz_dbi_query("INSERT IGNORE INTO ".$gTables['artico']." (codice,descri,unimis,preve1,sconto,aliiva) VALUES ('".$jv->codart."','".$jv->descri."','".$jv->unimis."','".$jv->preve1."','".$jv->sconto."','".$jv->aliiva."')",true);
              if ($numinsert >=1) {
                // controllo se ho già una categoria merceologica con la stessa descrizione;
                $yescatmer = gaz_dbi_get_row($gTables['catmer'], 'descri', $jv->catmer);
                if ($yescatmer) {  // ho già una categoria merceologica con la stessa descrizione, prendo il suo codice e lo uso per aggiornare l'articolo appena inserito
                  $catmer=$yescatmer['codice'];
                } else { // altrimenti la inserisco come nuova e aumento il contatore $newcatmer
                  $newcatmer++;
                  $catmer=$newcatmer;
                  gaz_dbi_query("INSERT INTO ".$gTables['catmer']." (codice,descri) VALUES ('".$newcatmer."','".$jv->catmer."')");
                }
                gaz_dbi_put_row($gTables['artico'], 'codice', $jv->codart, 'catmer', $catmer);
              }
            }
          }
        }
      }
      header("Location: ../vendit/report_client.php");
      exit;
    } else { // ho degli errori ripropongo il form
      $preview = true;
    }
	} elseif (isset($_POST['Cancel'])) { // ho confermato l'inserimento
    header("Location: ./custom_from_fae.php");
    exit;

	}
	if ($preview) { // non ho errori vincolanti posso proporre la visualizzazione in base al contenuto del file che ho caricato
    if (empty($form['dirextract'])) {  // file singolo
      $invoices[]=$form['fattura_elettronica_original_name'];
    } else { // era uno zip, leggo la directory e popolo l'array con i nomi dei files
      $dh = opendir( DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' .$form['dirextract'].'/' );
      while (false !== ($filename = readdir($dh))) {
        if($filename != "." && $filename != ".."){
          $invoices[] = $form['dirextract'].'/'.$filename;
        }
      }
      closedir($dh);
    }

    $i=0;
    foreach($invoices as $v) {
      // definisco l'array con tutti i dati che possono essere presi dalla fattura elettronica ( se li trovo )
      $form['rows'][$i]=['codfis'=>'','pariva'=>'','ragso1'=>'','ragso2'=>'','legrap_pf_nome'=>'','legrap_pf_cognome'=>'','sexper'=>'G','indspe'=>'','capspe'=>'','citspe'=>'','prospe'=>'','country'=>'IT','codpag'=>0];
      $nf= DATA_DIR.'files/' . $admin_aziend['codice'] . '/' . $v;
      $gForm->getInvoiceContent($nf);
      $xpath = $gForm->xpath;
      $doc = $gForm->doc;

      // mittente
      $mittente='';
 			if ($xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Cognome")->length >= 1) {
        $mittente .= $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Cognome")->item(0)->nodeValue;
      }
 			if ($xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Nome")->length >= 1) {
        $mittente .= ' '.$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Nome")->item(0)->nodeValue;
      }
 			if ($xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Denominazione")->length >= 1) {
        $mittente = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Denominazione")->item(0)->nodeValue;
      }
      $form['rows'][$i]['mittente'] = $mittente;

      // testata fattura
      $form['rows'][$i]['tipfattura'] = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/TipoDocumento")->item(0)->nodeValue;
      $form['rows'][$i]['numfattura'] = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Numero")->item(0)->nodeValue;
      $form['rows'][$i]['impfattura'] = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/ImportoTotaleDocumento")->item(0)->nodeValue;
      $form['rows'][$i]['datfattura'] =$xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Data")->item(0)->nodeValue;;
      // righi fattura
      $linee =$xpath->query("//FatturaElettronicaBody/DatiBeniServizi/DettaglioLinee");
      $li=0;
      $accartico=[];
      foreach ($linee as $lv) { // attraverso le linee
        $prto=$lv->getElementsByTagName('PrezzoTotale')->item(0)->nodeValue;
        $desc=$lv->getElementsByTagName('Descrizione')->item(0)->nodeValue;
        if ($li==0) { // per il momento prendo solo la prima linea
          $form['rows'][$i]['descriprima']=$prto;
          $form['rows'][$i]['amountprima']=$desc;
        }
        $li++;
        $prezzounitario = $lv->getElementsByTagName('PrezzoUnitario')->item(0)->nodeValue;
				$unimis = $lv->getElementsByTagName('UnitaMisura')->length >= 1 ? $lv->getElementsByTagName('UnitaMisura')->item(0)->nodeValue :	'';
				$pervat = $lv->getElementsByTagName('AliquotaIVA')->item(0)->nodeValue;

        // raccordo il codice IVA con la stessa natura ed aliquota IVA
        $natura = $lv->getElementsByTagName('Natura')->length >=1 ? $lv->getElementsByTagName('Natura')->item(0)->nodeValue : false;
				$aziendperiva = gaz_dbi_get_row($gTables['aliiva'], 'codice', $admin_aziend['preeminent_vat'])['aliquo'];
        if ( !$natura && $aziendperiva == $pervat) { // coincide con l'aliquota aziendale e non è esente
          $aliiva = $admin_aziend['preeminent_vat'];
        } else { // non è quella che mi aspettavo allora provo a trovarne una tra quelle con la stessa aliquota
          $filter_vat = "aliquo=" . $pervat;
          $orderby = 'codice ASC';
          if ($natura) {
            $filter_vat.= " AND fae_natura='" . $natura . "'";
            if (substr($natura,0,2)=='N6') { // con il reverse charge (N6.X) propongo quella più adatta ma considero una aliquota con IVA
              $filter_vat = "fae_natura='" . $natura . "' AND aliquo >= 0.1";
              $orderby = 'descri ASC'; // ci vorrebe un similar text con gli acquisti le aliquote
            }
          }
          $rs_last_codvat = gaz_dbi_dyn_query("*", $gTables['aliiva'], $filter_vat . " AND tipiva<>'T'", $orderby, 0, 1);
          $last_codvat = gaz_dbi_fetch_array($rs_last_codvat);
          if ($last_codvat) {
            $aliiva = $last_codvat['codice'];
          } else {
            $aliiva = $admin_aziend['preeminent_vat'];
          }
        }

        if ($lv->getElementsByTagName("Quantita")->length >= 1) {
          $form['rows'][$i]['quanti'] = $lv->getElementsByTagName('Quantita')->item(0)->nodeValue;
          $form['rows'][$i]['tiprig'] = 0; // rigo con quantità
        } else {
          $form['rows'][$i]['quanti'] = '';
          $form['rows'][$i]['tiprig'] = 1; // rigo senza quantità
        }

        // inizio applicazione sconto su rigo
        $sconto = 0;
        $acc_sconti=[];
        if ($lv->getElementsByTagName("ScontoMaggiorazione")->length >= 1) { // ho uno sconto/maggiorazione
          $acc_sconti=[];
          $sconti_forfait=[];
          $sconto_maggiorazione=$lv->getElementsByTagName("ScontoMaggiorazione");
          foreach ($sconto_maggiorazione as $sconti) { // potrei avere più elementi 2.2.1.10 <ScontoMaggiorazione>
            if ($prezzounitario < 0.00000001) { // se trovo l'elemento 2.2.1.9 <PrezzoUnitario> a zero calcolo lo sconto a forfait
              $sconti_forfait[]=($sconti->getElementsByTagName('Tipo')->item(0)->nodeValue == 'SC' ? -$sconti->getElementsByTagName('Importo')->item(0)->nodeValue : $sconti->getElementsByTagName('Importo')->item(0)->nodeValue);
            } elseif ($sconti->getElementsByTagName("Importo")->length >= 1 && $lv->getElementsByTagName('Importo')->item(0)->nodeValue >= 0.00001){
              // calcolo la percentuale di sconto partendo dall'importo del rigo e da quello dello sconto, il funzionamento di GAzie prevede la percentuale e non l'importo dello sconto
              $tot_rig= (!empty($form['rows'][$i]['quanti']) && $form['rows'][$i]['quanti']!=0) ? $form['rows'][$i]['quanti']*$prezzounitario : $prezzounitario;
              $acc_sconti[]=(!empty($form['rows'][$i]['quanti']) && intval($form['rows'][$i]['quanti'])>1) ? $form['rows'][$i]['quanti']*$lv->getElementsByTagName('Importo')->item(0)->nodeValue*100/$tot_rig : $lv->getElementsByTagName('Importo')->item(0)->nodeValue*100/$tot_rig;
              //$sconto=$lv->getElementsByTagName('Importo')->item(0)->nodeValue*100/$tot_rig;
            } elseif($sconti->getElementsByTagName("Percentuale")->length >= 1 && $sconti->getElementsByTagName('Percentuale')->item(0)->nodeValue>=0.00001){ // ho una percentuale accodo quella
              $acc_sconti[]=($sconti->getElementsByTagName('Tipo')->item(0)->nodeValue == 'SC' ? $sconti->getElementsByTagName('Percentuale')->item(0)->nodeValue : -$sconti->getElementsByTagName('Percentuale')->item(0)->nodeValue);
            }
          }
          if (count($sconti_forfait) > 0) {
            $sf=0;
            foreach($sconti_forfait as $scf){ // attraverso l'accumulatore di sconti forfait per ottenerne il totale
              $sf += $scf;
            }
            $prezzounitario = $sf;
          } else {
            $is=1;
            foreach($acc_sconti as $vsc){ // attraverso l'accumulatore di sconti per ottenerne uno solo
              $is *=(1-$vsc/100);
            }
            $sconto = round(100*(1-$is),3);
          }
        }

        // popolo il JSON con i dati del rigo solo se ho l'elemento CodiceArticolo 2.2.1.3
        if ($lv->getElementsByTagName('CodiceArticolo')->length >= 1) {
          $accartico[$i][]= ['catmer'=>$lv->getElementsByTagName('CodiceArticolo')->item(0)->getElementsByTagName("CodiceTipo")->item(0)->nodeValue,
                           'codart'=>$lv->getElementsByTagName('CodiceArticolo')->item(0)->getElementsByTagName("CodiceValore")->item(0)->nodeValue,
                           'descri'=>$desc,'unimis'=>$unimis,'sconto'=>$sconto,'preve1'=>$prezzounitario,'aliiva'=>$aliiva];
        }
        $form['rows'][$i]['jsonartico']=json_encode($accartico);
      }



      // pagamento
      $pagamento = 'MP05';
      $scadenze = [0=>0];
 			if ($xpath->query("//FatturaElettronicaBody/DatiPagamento/CondizioniPagamento")->length >= 1) {
        $pagamento = $xpath->query("//FatturaElettronicaBody/DatiPagamento/DettaglioPagamento/ModalitaPagamento")->item(0)->nodeValue;
        $deadlines=['days'=>0];
        if ($xpath->query("//FatturaElettronicaBody/DatiPagamento/DettaglioPagamento/DataScadenzaPagamento")->length >= 1) {
          $si=0;
          $objDataInizio = new DateTimeImmutable($form['rows'][$i]['datfattura']);
          $datescadenzepagamenti = $xpath->query("FatturaElettronicaBody/DatiPagamento/DettaglioPagamento");
          foreach ($datescadenzepagamenti as $datascadenza) { // potrei avere più elementi <DataScadenzaPagamento>
            $datsca = $datascadenza->getElementsByTagName('DataScadenzaPagamento')->item(0)->nodeValue;
            $objDataFine = new DateTimeImmutable($datsca);
            $interval =  $objDataInizio->diff($objDataFine);
            $objDataInizio = new DateTimeImmutable($datsca);
            // calcolo la differenza dei giorni con la data fattura ed eventualmente tra le scadenze
            $scadenze[$si]= (int)$interval->format("%a");
            $si++;
          }
        }
      }
      $form['rows'][$i]['codpag']=searchPagame($pagamento,$scadenze);

      // destinatario
      $destinatario='';
 			if ($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/Anagrafica/Cognome")->length >= 1) {
        $form['rows'][$i]['legrap_pf_cognome']=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/Anagrafica/Cognome")->item(0)->nodeValue;
        $destinatario .= $form['rows'][$i]['legrap_pf_cognome'];
      }
 			if ($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/Anagrafica/Nome")->length >= 1) {
        $form['rows'][$i]['legrap_pf_nome']=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/Anagrafica/Nome")->item(0)->nodeValue;
        $destinatario .= ' '.$form['rows'][$i]['legrap_pf_nome'];
      }
 			if ($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/Anagrafica/Denominazione")->length >= 1) {
        $form['rows'][$i]['ragso1']=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/Anagrafica/Denominazione")->item(0)->nodeValue;
      } else {
        $form['rows'][$i]['ragso1'] = $destinatario;
      }
 			if ($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/CodiceFiscale")->length >= 1) {
        $form['rows'][$i]['codfis']=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/CodiceFiscale")->item(0)->nodeValue;
        if (!is_numeric($form['rows'][$i]['codfis'])) {
          $form['rows'][$i]['sexper'] = substr($form['rows'][$i]['codfis'],9,2) > 40 ? 'F' : 'M';
        }
      }
			if ($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdCodice")->length >= 1) {
        $form['rows'][$i]['pariva']=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue;
			}
			if ($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Indirizzo")->length >= 1) {
        $form['rows'][$i]['indspe']=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Indirizzo")->item(0)->nodeValue;
			}
			if ($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/CAP")->length >= 1) {
        $form['rows'][$i]['capspe']=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/CAP")->item(0)->nodeValue;
			}
			if ($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Comune")->length >= 1) {
        $form['rows'][$i]['citspe']=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Comune")->item(0)->nodeValue;
			}
			if ($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Provincia")->length >= 1) {
        $form['rows'][$i]['prospe']=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Provincia")->item(0)->nodeValue;
			}
			if ($xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Nazione")->length >= 1) {
        $form['rows'][$i]['country']=$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/Sede/Nazione")->item(0)->nodeValue;
			}
      $i++;
    }
  }
}
require("../../library/include/header.php");
$script_transl = HeadMain();
	// INIZIO form che permetterà all'utente di interagire per (es.) imputare i vari costi al piano dei conti (contabilità) ed anche le eventuali merci al magazzino
    if (count($msg['err']) > 0) { // ho un errore
        $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
    }
    if (count($msg['war']) > 0) { // ho un alert
        $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
    }
?>
<script>
</script>
<div class="text-center" ><h2><?php echo $script_transl['title'];?></h2></div>
<div class="col-sm-1"></div>
<div class="panel panel-warning col-sm-10">
  <div class="text-warning"><?php echo $script_transl['disclaimer'];?></div>
</div>
<div class="col-sm-1"></div>
<form method="POST" name="form" enctype="multipart/form-data" id="add-invoice">
  <input type="hidden" name="fattura_elettronica_original_name" value="<?php echo $form['fattura_elettronica_original_name']; ?>" />
  <input type="hidden" name="dirextract" value="<?php echo $form['dirextract']; ?>" />
<?php
if ($preview){
 ?>
<div class="panel panel-default col-xs-12">
    <div class="panel-heading">
        <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12"><b><?php echo $script_transl['head_text1']. '</b><span class="label label-success">'.$form['fattura_elettronica_original_name'] .'</span><b>'.$script_transl['head_text2']; ?></b>
            </div>
        </div> <!-- chiude row  -->
    </div>
    <div class="panel-body">
      <div class="row">
          <div class="col-md-12">
              <div class="form-group">
                  <label for="id_customer_group" class="col-sm-4 control-label text-right">Gruppo clienti</label>
  <?php
$gForm->selectFromDB('customer_group', 'id_customer_group', 'id', $form['id_customer_group'], 'id', true, ' - ', 'descri','','col-xs-12 col-md-8 col-lg-6');
  ?>
              </div>
          </div>
      </div><!-- chiude row  -->
      <div class="row">
          <div class="col-md-12">
              <div class="form-group">
                  <label for="codpag" class="col-sm-4 control-label text-right">Pagamento di default *</label>
  <?php
$gForm->selectFromDB('pagame', 'codpag', 'codice', $form['codpag'], 'tippag`, `giodec`, `numrat', true, ' - ', 'descri', '', 'col-xs-12 col-md-8 col-lg-6' );
  ?>
              </div>
          </div>
      </div><!-- chiude row  -->
      <div class="row">
          <div class="col-md-12">
              <div class="form-group">
                  <label for="gencontract" class="col-sm-4 control-label text-right"><?php echo $script_transl['gencontract']; ?></label>
  <?php
$gForm->variousSelect('gencontract', $script_transl['gencontract_value'], $form['gencontract']);
  ?>
              </div>
          </div>
      </div><!-- chiude row  -->
      <div class="row">
          <div class="col-md-12">
              <div class="form-group">
                  <label for="genartico" class="col-sm-4 control-label text-right"><?php echo $script_transl['genartico']; ?></label>
  <?php
$gForm->variousSelect('genartico', $script_transl['genartico_value'], $form['genartico']);
  ?>
              </div>
          </div>
      </div><!-- chiude row  -->

<?php
foreach ($form['rows'] as $k => $v) { // attraverso le fatture
  $rs_anagra = gaz_dbi_dyn_query('id', $gTables['anagra'], "( pariva <> '' AND pariva = '" . $v['pariva'] . "' ) OR ( codfis <> '' AND codfis = '" . $v['codfis'] . "' )", "id", 0, 1);
  $partner_with_same_cfpi = gaz_dbi_fetch_array($rs_anagra);
  if (!$partner_with_same_cfpi) { // visualizzo solo se il cliente non è già presente

?>
<div class="panel panel-info">
  <div class="text-bold text-info bg-info">
    <input type="hidden" value="<?php echo $v['impfattura']; ?>" name="rows[<?php echo $k; ?>][impfattura]"/>
    <input type="hidden" value="<?php echo $v['datfattura']; ?>" name="rows[<?php echo $k; ?>][datfattura]"/>
    <input type="hidden" value="<?php echo $v['descriprima']; ?>" name="rows[<?php echo $k; ?>][descriprima]"/>
    <input type="hidden" value="<?php echo $v['amountprima']; ?>" name="rows[<?php echo $k; ?>][amountprima]"/>
    <input type="hidden" value='<?php echo $v['jsonartico']; ?>' name="rows[<?php echo $k; ?>][jsonartico]"/> -->
    <?php echo $v['mittente'].' tipo:'.$v['tipfattura'].' n.'.$v['numfattura'].' del '.gaz_format_date($v['datfattura']).' di € '.$v['impfattura']; ?>
  </div>
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="ragso1" class="col-sm-4 control-label text-right"> Ragione sociale </label>
              <input class="col-sm-4" type="text" value="<?php echo $v['ragso1']; ?>" name="rows[<?php echo $k; ?>][ragso1]" maxlength="100"/>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="legrap_pf_cognome" class="col-sm-4 control-label text-right"> Cognome </label>
              <input class="col-sm-4" type="text" value="<?php echo $v['legrap_pf_cognome']; ?>" name="rows[<?php echo $k; ?>][legrap_pf_cognome]" maxlength="60"/>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="legrap_pf_nome" class="col-sm-4 control-label text-right"> Nome </label>
              <input class="col-sm-4" type="text" value="<?php echo $v['legrap_pf_nome']; ?>" name="rows[<?php echo $k; ?>][legrap_pf_nome]" maxlength="60"/>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="sexper" class="col-sm-4 control-label text-right"> Sesso/Persona Giuridica </label>
              <input type="hidden" value="<?php echo $v['sexper']; ?>" name="rows[<?php echo $k; ?>][sexper]"/> <?php echo $v['sexper']; ?>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="codfis" class="col-sm-4 control-label text-right"> Codice Fiscale </label>
              <input type="hidden" value="<?php echo $v['codfis']; ?>" name="rows[<?php echo $k; ?>][codfis]"/> <?php echo $v['codfis']; ?>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="pariva" class="col-sm-4 control-label text-right"> Partita IVA </label>
              <input type="hidden" value="<?php echo $v['pariva']; ?>" name="rows[<?php echo $k; ?>][pariva]"/> <?php echo $v['pariva']; ?>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="indspe" class="col-sm-4 control-label text-right"> Indirizzo </label>
              <input class="col-sm-4" type="text" value="<?php echo $v['indspe']; ?>" name="rows[<?php echo $k; ?>][indspe]" maxlength="100"/>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="capspe" class="col-sm-4 control-label text-right"> CAP </label>
              <input class="col-sm-4" type="text" value="<?php echo $v['capspe']; ?>" name="rows[<?php echo $k; ?>][capspe]" maxlength="5"/>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="citspe" class="col-sm-4 control-label text-right"> Città </label>
              <input class="col-sm-4" type="text" value="<?php echo $v['citspe']; ?>" name="rows[<?php echo $k; ?>][citspe]" maxlength="60"/>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="prospe" class="col-sm-4 control-label text-right"> Provincia </label>
              <input class="col-sm-4" type="text" value="<?php echo $v['prospe']; ?>" name="rows[<?php echo $k; ?>][prospe]" maxlength="2"/>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="country" class="col-sm-4 control-label text-right"> Nazione </label>
              <input type="hidden" value="<?php echo $v['country']; ?>" name="rows[<?php echo $k; ?>][country]"/> <?php echo $v['country']; ?>
          </div>
      </div>
  </div><!-- chiude row  -->
  <div class="row">
      <div class="col-md-12">
          <div class="form-group">
              <label for="codpag" class="col-sm-4 control-label text-right">Pagamento</label>
<?php
$gForm->selectFromDB('pagame', 'rows['.$k.'][codpag]', 'codice', $v['codpag'], 'tippag`, `giodec`, `numrat', true, ' ', 'descri');
?>
          </div>
      </div>
  </div><!-- chiude row  -->

</div>
<?php
  } else {
?>
<div class="panel panel-info">
  <div class="text-bold text-info bg-info"><?php echo $v['mittente'].' tipo:'.$v['tipfattura'].' n.'.$v['numfattura'].' del '.gaz_format_date($v['datfattura']).' di € '.$v['impfattura']; ?></div>
  <div class="text-bold text-warning bg-warning"><?php echo $v['ragso1']; ?> già presente </div>
</div>
<?php
  }
}
?>
	   <div class="col-sm-6 text-center"><input name="Cancel" type="submit" class="btn btn-default" value="<?php echo $script_transl['cancel']; ?>" /> </div>
	   <div class="col-sm-6 text-center"><input name="Submit_form" type="submit" class="btn btn-warning" value="<?php echo $script_transl['submit']; ?>" /> </div>
    </div>
</div>


<?php
} else { // all'inizio chiedo l'upload di un file xml, p7m o zip
?>
<div class="panel panel-default gaz-table-form">
	<div class="container-fluid">
       <div class="row">
           <div class="col-md-12">
               <div class="form-group">
                   <label for="image" class="col-sm-4 control-label">Seleziona il file singolo o massivo<br/>(xml, p7m o zip)</label>
                   <div class="col-sm-8">File: <input type="file" accept=".xml,.p7m,.zip" name="userfile" />
				   </div>
               </div>
           </div>
       </div><!-- chiude row  -->
	   <div class="col-sm-12 text-right"><input name="Submit_file" type="submit" class="btn btn-warning" value="<?php echo $script_transl['btn_acquire']; ?>" />
	   </div>
	</div> <!-- chiude container -->
</div><!-- chiude panel -->
<input type="hidden" name="id_customer_group" value="0" />
<input type="hidden" name="codpag" value="0" />
<input type="hidden" name="gencontract" value="0" />
<input type="hidden" name="genartico" value="0" />

<?php
}
echo '</form>';
require("../../library/include/footer.php");
?>

