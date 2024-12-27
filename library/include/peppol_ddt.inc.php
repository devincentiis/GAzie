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

class peppolDocument {

  private $gTables;
  private $testata;
  private $azienda;
  private $pagame;
  private $banapp;
  private $banacc;
  private $cliente;
  private $agente;
  private $vettore;
  private $destinatario;
  private $transchr = ['“'=>'"','‘'=>'\'','€'=>'euro','©'=>'&#169;','®'=>'&#174;','È'=>'&#200;','É'=>'&#201;','Ì'=>'&#204;','À'=>'&#192;','Ò'=>'&#210;','Ù'=>'&#217;',"ø" => "&#248;", "£" => "&#163;"];
  private $doctprefix;

  function __construct($gTables,$idtes,$doctprefix='doc') {
    $this->doctprefix = $doctprefix;
    $this->gTables = $gTables;
    $this->testata = gaz_dbi_get_row($gTables['tes'.$doctprefix], 'id_tes', $idtes);
    $this->azienda = gaz_dbi_get_row($gTables['aziend'], 'codice', $_SESSION['company_id']);
    $this->pagame = gaz_dbi_get_row($gTables['pagame'], "codice", $this->testata['pagame']);
    $this->banapp = gaz_dbi_get_row($gTables['banapp'], "codice", $this->testata['banapp']);
    $anagrafica = new Anagrafica();
    $this->banacc = $anagrafica->getPartner($this->pagame['id_bank']);
    $this->cliente = $anagrafica->getPartner($this->testata['clfoco']);
    $rs_agente = gaz_dbi_get_row($gTables['agenti'], 'id_agente', $this->testata['id_agente']);
    $this->agente = ($rs_agente)?$anagrafica->getPartner($rs_agente['id_fornitore']):false;
    $rs_vettore = gaz_dbi_get_row($gTables['vettor'], 'codice', $this->testata['vettor']);
    $this->vettore = ($rs_vettore)?$anagrafica->getPartnerData($rs_vettore['id_anagra']):false;
    $this->vettore = ($this->vettore)?$this->vettore:$this->azienda; // se non ho un vettore vuol dire che sarà l'azienda stessa ( in Shipment PartyIdentification )
    // valorizzo la destinazione
    if ($this->testata['id_des'] >= 1){ // destinatario diverso dal cliente
      $des=$anagrafica->getPartnerData($this->testata['id_des']);
      $this->destinatario=$des;
    } elseif($this->testata['id_des_same_company'] >= 1){ // il destinatario è il cliente, ma in luogo diverso dalla sede
      $rs_destina = gaz_dbi_get_row($gTables['destina'], 'codice', $this->testata['id_des_same_company']);
      $this->destinatario = ($rs_destina)?$rs_destina:$this->cliente;
      $this->destinatario=$this->cliente;
      // aggiungo ad AddressLine la descrizione unità 1 alla ragione sociale ed evenualmente l'indice destin della testata
      $this->destinatario['ragso1'] = $rs_destina['unita_locale1'].' - '.$this->cliente['ragso1'];
    } else { // stessa sede del cliente
      $this->destinatario=$this->cliente;
    }
  }

  private function getRows() {
    $from = $this->gTables['rig'.$this->doctprefix] . ' AS rs
             LEFT JOIN ' . $this->gTables['aliiva'] . ' AS vat ON rs.codvat=vat.codice
             LEFT JOIN ' . $this->gTables['movmag'] . ' AS mom ON rs.id_mag=mom.id_mov
             LEFT JOIN ' . $this->gTables['lotmag'] . ' AS ltm ON mom.id_lotmag=ltm.id
		 ';
    $rs_rig = gaz_dbi_dyn_query('rs.*,vat.tipiva AS tipiva, vat.fae_natura AS natura, ltm.identifier AS idlotto, ltm.expiry AS scadenzalotto', $from, "rs.id_tes = " . $this->testata['id_tes'], "id_tes DESC, id_rig");
    $nr = 1;
    $rows = [];
    while ($r = gaz_dbi_fetch_array($rs_rig)) {
      // filtro le descrizioni
      $r['descri'] = strtr ( htmlspecialchars(htmlspecialchars_decode(trim(html_entity_decode($r['descri'], ENT_XML1 | ENT_QUOTES, 'UTF-8'))), ENT_XML1, 'UTF-8'), $this->transchr);
      $r['codart']=(empty(trim($r['codart'])))?'NOCOD_row_'.$r['id_rig']:$r['codart'];
      $r['codice_fornitore']=(empty(trim($r['codice_fornitore'])))?$r['codart']:$r['codice_fornitore'];
      $rows[$nr] = $r;
      $nr++;
    }
    return $rows;
  }

  function createPeppolDdt($template,$causali) {
    $domDoc = new DOMDocument;
    $domDoc->preserveWhiteSpace = false;
    $domDoc->formatOutput = true;
    $domDoc->load("../../library/include/".$template);
    $xpath = new DOMXPath($domDoc);
    $rsx = $xpath->query("//cbc:ID")->item(0);
    $attrVal = $domDoc->createTextNode($this->testata['id_tes']);
    $rsx->appendChild($attrVal);
    $rsx = $xpath->query("//cbc:IssueDate")->item(0);
    $attrVal = $domDoc->createTextNode($this->testata['datemi']);
    $rsx->appendChild($attrVal);
    $rsx = $xpath->query("//cbc:Note")->item(0);
    $attrVal = $domDoc->createTextNode($this->testata['imball']);
    $rsx->appendChild($attrVal);
// DespatchSupplierParty
    $rsx = $xpath->query("//cac:DespatchSupplierParty/cac:Party")->item(0);
      $el1 = $domDoc->createElement("cbc:EndpointID", $this->azienda['country'].$this->azienda['pariva']);
      $at1 = $domDoc->createAttribute('schemeID');
      $at1->value = '0211';
      $el1->appendChild($at1);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cac:PartyIdentification");
      $el2 = $domDoc->createElement("cbc:ID", $this->azienda['country'].$this->azienda['pariva']);
      $at2 = $domDoc->createAttribute('schemeID');
      $at2->value = '0211';
      $el2->appendChild($at2);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cac:PostalAddress");
      $el2 = $domDoc->createElement("cbc:StreetName", $this->azienda['indspe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:CityName", $this->azienda['citspe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:PostalZone", $this->azienda['capspe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:CountrySubentity", $this->azienda['prospe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cac:Country");
      $el3 = $domDoc->createElement("cbc:IdentificationCode", $this->azienda['country']);
      $el2->appendChild($el3);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cac:PartyLegalEntity");
      $el2 = $domDoc->createElement("cbc:RegistrationName", $this->azienda['ragso1'].' '.$this->azienda['ragso2']);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
// DeliveryCustomerParty
    $rsx = $xpath->query("//cac:DeliveryCustomerParty/cac:Party")->item(0);
      $el1 = $domDoc->createElement("cbc:EndpointID", $this->cliente['fe_cod_univoco']);
      // la validazione di questo elemento restituisce errore se fe_cod_univoco è diverso da 6 caratteri perché al momento l'AgID lo ha previsto solo per la PA
      $at1 = $domDoc->createAttribute('schemeID');
      $at1->value = '0201';
      $el1->appendChild($at1);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cac:PartyIdentification");
      $el2 = $domDoc->createElement("cbc:ID", $this->cliente['pariva']);
      $at2 = $domDoc->createAttribute('schemeID');
      $at2->value = '0210';
      $el2->appendChild($at2);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cac:PostalAddress");
      $el2 = $domDoc->createElement("cbc:StreetName", $this->cliente['indspe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:CityName", $this->cliente['citspe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:PostalZone", $this->cliente['capspe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:CountrySubentity", $this->cliente['prospe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cac:Country");
      $el3 = $domDoc->createElement("cbc:IdentificationCode", $this->cliente['country']);
      $el2->appendChild($el3);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cac:PartyLegalEntity");
      $el2 = $domDoc->createElement("cbc:RegistrationName", $this->cliente['ragso1'].' '.$this->cliente['ragso2']);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
// BuyerCustomerParty
    $rsx = $xpath->query("//cac:BuyerCustomerParty/cac:Party")->item(0);
      $el1 = $domDoc->createElement("cac:PartyIdentification");
      $el2 = $domDoc->createElement("cbc:ID", $this->cliente['pariva']);
      $at2 = $domDoc->createAttribute('schemeID');
      $at2->value = '0210';
      $el2->appendChild($at2);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cac:PartyName");
      $el2 = $domDoc->createElement("cbc:Name", $this->cliente['ragso1'].' '.$this->cliente['ragso2']);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cac:PostalAddress");
      $el2 = $domDoc->createElement("cbc:StreetName", $this->cliente['indspe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:CityName", $this->cliente['citspe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:PostalZone", $this->cliente['capspe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:CountrySubentity", $this->cliente['prospe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cac:Country");
      $el3 = $domDoc->createElement("cbc:IdentificationCode", $this->cliente['country']);
      $el2->appendChild($el3);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
// SellerSupplierParty
    $rsx = $xpath->query("//cac:SellerSupplierParty/cac:Party")->item(0);
      $el1 = $domDoc->createElement("cac:PartyIdentification");
      $el2 = $domDoc->createElement("cbc:ID", $this->azienda['country'].$this->azienda['pariva']);
      $at2 = $domDoc->createAttribute('schemeID');
      $at2->value = '0211';
      $el2->appendChild($at2);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cac:PartyName");
      $el2 = $domDoc->createElement("cbc:Name", $this->azienda['ragso1'].' '.$this->azienda['ragso2']);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cac:PostalAddress");
      $el2 = $domDoc->createElement("cbc:StreetName", $this->azienda['indspe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:CityName", $this->azienda['citspe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:PostalZone", $this->azienda['capspe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:CountrySubentity", $this->azienda['prospe']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cac:Country");
      $el3 = $domDoc->createElement("cbc:IdentificationCode", $this->azienda['country']);
      $el2->appendChild($el3);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
// Shipment
    $rsx = $xpath->query("//cac:Shipment")->item(0);
      $el1 = $domDoc->createElement("cbc:ID", $this->testata['id_tes']);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cbc:Information", $causali[$this->testata['ddt_type']]);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cbc:GrossWeightMeasure", $this->testata['gross_weight']);
      $at1 = $domDoc->createAttribute('unitCode');
      $at1->value = 'KGM';
      $el1->appendChild($at1);
    $rsx->appendChild($el1);
      $el1 = $domDoc->createElement("cbc:TotalTransportHandlingUnitQuantity",  $this->testata['units']);
    $rsx->appendChild($el1);
      // Consignment
      $el1 = $domDoc->createElement("cac:Consignment");
      $el2 = $domDoc->createElement("cbc:ID", $this->testata['id_tes']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:Information", $this->testata['portos']);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cac:CarrierParty");
      $el3 = $domDoc->createElement("cac:PartyIdentification");
      $el4 = $domDoc->createElement("cbc:ID", $this->vettore['country'].$this->vettore['pariva']);
      $at4 = $domDoc->createAttribute('schemeID');
      $at4->value = '0211';
      $el4->appendChild($at4);
      $el3->appendChild($el4);
      $el2->appendChild($el3);
      $el3 = $domDoc->createElement("cac:PartyName");
      $el4 = $domDoc->createElement("cbc:Name", $this->vettore['ragso1'].' '.$this->vettore['ragso2']);
      $el3->appendChild($el4);
      $el2->appendChild($el3);
      $el3 = $domDoc->createElement("cac:PostalAddress");
      $el4 = $domDoc->createElement("cbc:StreetName", $this->vettore['indspe']);
      $el3->appendChild($el4);
      $el4 = $domDoc->createElement("cbc:CityName", $this->vettore['citspe']);
      $el3->appendChild($el4);
      $el4 = $domDoc->createElement("cbc:PostalZone", $this->vettore['capspe']);
      $el3->appendChild($el4);
      $el4 = $domDoc->createElement("cbc:CountrySubentity", $this->vettore['prospe']);
      $el3->appendChild($el4);
      $el4 = $domDoc->createElement("cac:Country");
      $el5 = $domDoc->createElement("cbc:IdentificationCode", $this->vettore['country']);
      $el4->appendChild($el5);
      $el3->appendChild($el4);
      $el2->appendChild($el3);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
      // Delivery
      $el1 = $domDoc->createElement("cac:Delivery");
      // $el2 = $domDoc->createElement("cbc:TrackingID", 'Attualmente non è gestita da GAzie la possibilità di indicare l'ID per il tracking');
      // $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cac:Despatch");
      $el3 = $domDoc->createElement("cac:DespatchAddress");
      $el4 = $domDoc->createElement("cbc:StreetName", $this->destinatario['indspe']);
      $el3->appendChild($el4);
      $el4 = $domDoc->createElement("cbc:CityName", $this->destinatario['citspe']);
      $el3->appendChild($el4);
      $el4 = $domDoc->createElement("cbc:PostalZone", $this->destinatario['capspe']);
      $el3->appendChild($el4);
      $el4 = $domDoc->createElement("cbc:CountrySubentity", $this->destinatario['prospe']);
      $el3->appendChild($el4);
      $el4 = $domDoc->createElement("cac:AddressLine");
      $el5 = $domDoc->createElement("cbc:Line",$this->testata['destin'].' '.$this->destinatario['ragso1'].' '.$this->destinatario['ragso2']);
      $el4->appendChild($el5);
      $el3->appendChild($el4);
      $el4 = $domDoc->createElement("cac:Country");
      $el5 = $domDoc->createElement("cbc:IdentificationCode", $this->vettore['country']);
      $el4->appendChild($el5);
      $el3->appendChild($el4);
      $el2->appendChild($el3);
      $el1->appendChild($el2);
    $rsx->appendChild($el1);
//DespatchLine
    $lines=$this->getRows();
    $rsx = $xpath->query("/")->item(0)->childNodes->item(0);
    $n_linea = 1;
    foreach($lines as $r){
      $el1 = $domDoc->createElement("cac:DespatchLine");
      $el2 = $domDoc->createElement("cbc:ID", $n_linea);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cbc:DeliveredQuantity", $r['quanti']);
      $at2 = $domDoc->createAttribute('unitCode');
      $at2->value = 'C62';
      $el2->appendChild($at2);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cac:OrderLineReference");
      $el3 = $domDoc->createElement("cbc:LineID",$r['id_order']);
      $el2->appendChild($el3);
      $el3 = $domDoc->createElement("cac:OrderReference");
      $el4 = $domDoc->createElement("cbc:ID",'ORD_N_'.$r['id_order']);
      $el3->appendChild($el4);
      $el2->appendChild($el3);
      $el1->appendChild($el2);
      $el2 = $domDoc->createElement("cac:Item");
      $el3 = $domDoc->createElement("cbc:Name",$r['descri']);
      $el2->appendChild($el3);
      $el3 = $domDoc->createElement("cac:SellersItemIdentification");
      $el4 = $domDoc->createElement("cbc:ID",$r['codart']);
      $el3->appendChild($el4);
      $el2->appendChild($el3);
      $el1->appendChild($el2);
      $rsx->append($el1);
      $n_linea++;
    }
    header("Content-type: text/plain");
    header("Content-Disposition: attachment; filename=PeppolDdT_" .$this->testata['id_tes']. ".xml");
    print $domDoc->saveXML();
  }
}
?>
