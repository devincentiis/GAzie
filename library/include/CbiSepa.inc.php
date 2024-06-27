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

class CbiSepa {

    function setCbiPayReqVars($gTables,$head) {
		// qui setto tutti i valori per l'intestazione
        $this->azienda = gaz_dbi_get_row($gTables['aziend'], 'codice', $_SESSION['company_id']);
        $this->bank = gaz_dbi_get_row($gTables['clfoco'].' LEFT JOIN '.$gTables['banapp'].' ON '.$gTables['clfoco'].'.banapp = '.$gTables['banapp'].".codice", $gTables['clfoco'].'.codice',$head['bank']);
        $this->OthrId = (intval($this->azienda['pariva'])>=100)?$this->azienda['pariva']:$this->azienda['codfis'];
		$this->CreDtTm = date('Y-m-d\TH:i:s');
        $this->MsgId = dechex(rand(100,999).date('siHdmY')).'-';
		$this->CtgyPurpCd = (isset($head['CtgyPurpCd']) && strlen($head['CtgyPurpCd']) == 4) ? $head['CtgyPurpCd'] : false;
		// INTC  IntraCompanyPayment  Intra-company payment
		// INTE  Interest  Payment of interest.
		// PENS  PensionPayment  Payment of pension.
		// SALA  SalaryPayment  Payment of salaries.
		// SSBE  SocialSecurityBenefit  Payment of child benefit, family allowance.
		// SUPP  SupplierPayment  Payment to a supplier.
		// TAXS  TaxPayment  Payment of taxes.
		// TREA  TreasuryPayment  Treasury transaction
		// OTHR  Other
		$this->FileName = (isset($head['FileName']) && strlen($head['FileName']) >= 16) ? $head['FileName'] : 'XMLCBIpay'.date('Ymdhis');
    }

    function create_XML_CBIPaymentRequest($gTables,$head,$data,$save_id_doc=false) {
        // in $data dovrò passare tutti i dati necessari per la creazione degli elementi <CdtTrfTxInf>
        // le chiavi sono: EndToEndId (id univoco) ,InstdAmt (importo), Nm (descrizione creditore), IBAN (iban accredito), Ustrd (descrizione debito pagato) ognuno creerà il relativo elemento dentro <CdtTrfTxInf>
		// in $save_id_doc potrà essere indicato il nome del file da salvare in "data/files/{company_id}/doc/{$save_id_doc}.xml"
        $CtrlSum = 0.00;
        $NbOfTxs = 1;
        $domDoc = new DOMDocument;
        $domDoc->preserveWhiteSpace = false;
        $domDoc->formatOutput = true;
        $this->setCbiPayReqVars($gTables,$head);
        $domDoc->load("../../library/include/template_CBIPaymentRequest.xml");
        $xpath = new DOMXPath($domDoc);
        $rootNamespace = $domDoc->lookupNamespaceUri($domDoc->namespaceURI);
        $xpath->registerNamespace('x', $rootNamespace);
        $results = $xpath->query("//x:GrpHdr/x:MsgId")->item(0);
        $attrVal = $domDoc->createTextNode($this->MsgId);
        $results->appendChild($attrVal);
        $results = $xpath->query("//x:GrpHdr/x:CreDtTm")->item(0);
        $attrVal = $domDoc->createTextNode($this->CreDtTm);
        $results->appendChild($attrVal);
        $results = $xpath->query("//x:GrpHdr/x:InitgPty/x:Nm")->item(0);
        $attrVal = $domDoc->createTextNode($this->bank['descri']);
        $results->appendChild($attrVal);
        $results = $xpath->query("//x:GrpHdr/x:InitgPty/x:Id/x:OrgId/x:Othr/x:Id")->item(0);
        $attrVal = $domDoc->createTextNode($this->bank['cuc_code']);
        $results->appendChild($attrVal);
        $results = $xpath->query("//x:PmtInf/x:PmtInfId")->item(0);
        $attrVal = $domDoc->createTextNode($this->MsgId);
        $results->appendChild($attrVal);
        $results = $xpath->query("//x:PmtInf/x:ReqdExctnDt")->item(0);
        $attrVal = $domDoc->createTextNode(substr($this->CreDtTm,0,10));
        $results->appendChild($attrVal);
        $results = $xpath->query("//x:PmtInf/x:Dbtr/x:Nm")->item(0);
        $attrVal = $domDoc->createTextNode($this->bank['descri']);
        $results->appendChild($attrVal);
        $results = $xpath->query("//x:PmtInf/x:Dbtr/x:Id/x:OrgId/x:Othr/x:Id")->item(0);
        $attrVal = $domDoc->createTextNode($this->OthrId);
        $results->appendChild($attrVal);
        $results = $xpath->query("//x:PmtInf/x:DbtrAcct/x:Id/x:IBAN")->item(0);
        $attrVal = $domDoc->createTextNode($this->bank['iban']);
        $results->appendChild($attrVal);
        $results = $xpath->query("//x:PmtInf/x:DbtrAgt/x:FinInstnId/x:ClrSysMmbId/x:MmbId")->item(0);
        $attrVal = $domDoc->createTextNode(str_pad($this->bank['codabi'],5,'0',STR_PAD_LEFT));
        $results->appendChild($attrVal);
        // creo gli elementi dei singoli bonifici
        foreach($data as $v){
            $PmtInf = $xpath->query("//x:PmtInf")->item(0);
            $el = $domDoc->createElement("CdtTrfTxInf", "");
                $el1 = $domDoc->createElement("PmtId", "");
                    $el2 = $domDoc->createElement("InstrId", $NbOfTxs);
                    $el1->appendChild($el2);
                    $el2 = $domDoc->createElement("EndToEndId", $this->MsgId.$NbOfTxs);
                    $el1->appendChild($el2);
                $el->appendChild($el1);
                $el1 = $domDoc->createElement("PmtTpInf", "");
                    $el2 = $domDoc->createElement("CtgyPurp", "");
                        $el3 = $domDoc->createElement("Cd", $this->CtgyPurpCd?$this->CtgyPurpCd:$v["CtgyPurpCd"]);
                        $el2->appendChild($el3);
                    $el1->appendChild($el2);
                $el->appendChild($el1);
                $el1 = $domDoc->createElement("Amt", "");
                    $el2 = $domDoc->createElement("InstdAmt", $v['InstdAmt']);
                    $newel2 = $el1->appendChild($el2);
                    $newel2->setAttribute("Ccy", "EUR");
                $el->appendChild($el1);
                $el1 = $domDoc->createElement("Cdtr", "");
                    $el2 = $domDoc->createElement("Nm", $v['Nm']);
                    $el1->appendChild($el2);
                $el->appendChild($el1);
                $el1 = $domDoc->createElement("CdtrAcct", "");
                    $el2 = $domDoc->createElement("Id", "");
                        $el3 = $domDoc->createElement("IBAN", $v['IBAN']);
                        $el2->appendChild($el3);
                    $el1->appendChild($el2);
                $el->appendChild($el1);
                $el1 = $domDoc->createElement("RmtInf", "");
                    $el2 = $domDoc->createElement("Ustrd", $v['Ustrd']);
                    $el1->appendChild($el2);
                $el->appendChild($el1);
            $PmtInf->appendChild($el);
            $NbOfTxs++;
            $CtrlSum += $v['InstdAmt'];
        }
        $results = $xpath->query("//x:GrpHdr/x:NbOfTxs")->item(0);
        $attrVal = $domDoc->createTextNode($NbOfTxs-1);
        $results->appendChild($attrVal);
        $results = $xpath->query("//x:GrpHdr/x:CtrlSum")->item(0);
        $attrVal = $domDoc->createTextNode(number_format(round($CtrlSum,2),2,'.',''));
        $results->appendChild($attrVal);
        $cont=$domDoc->saveXML();
		if ($save_id_doc) $domDoc->save(DATA_DIR . "files/".$_SESSION['company_id']."/doc/". $save_id_doc . ".xml" );
        header("Content-type: text/plain");
        header("Content-Disposition: attachment; filename=".$this->FileName.".xml");
        print $cont;
    }

}

?>
