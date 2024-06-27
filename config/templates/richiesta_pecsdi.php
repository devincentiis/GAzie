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
require('../../config/templates/template.php'); //attingo sempre dal set standard, quelli personalizzati potrebbero creare problemi di impaginazione

class RichiestaPecSdi extends Template
{
    function setTesDoc()
    {
        $this->tesdoc = $this->docVars->tesdoc;
        $this->tesdoc = $this->docVars->tesdoc;
        $this->intesta1 = $this->docVars->intesta1;
        $this->intesta1bis = $this->docVars->intesta1bis;
        $this->intesta2 = $this->docVars->intesta2;
        $this->intesta3 = $this->docVars->intesta3;
        $this->intesta4 = $this->docVars->intesta4;
        $this->colore = $this->docVars->colore;
        $this->tipdoc = 'Richiesta codice SDI o indirizzo PEC per invio fatturazione elettronica';
        $this->cliente1 = $this->docVars->cliente1;
        $this->cliente2 = $this->docVars->cliente2;
        $this->cliente3 = $this->docVars->cliente3;
        $this->cliente4 = $this->docVars->cliente4;
        $this->cliente5 = $this->docVars->cliente5;
        $this->cliente6 = $this->docVars->client['sexper'];
		$this->luogo = $this->docVars->azienda['citspe'].' ('.$this->docVars->azienda['prospe'].')';
		$this->pec = $this->docVars->azienda['pec'];
        if ($this->docVars->intesta5 == 'F'){
           $this->descriAzienda = 'la sottoscritta';
        } elseif ($this->docVars->intesta5 == 'M'){
           $this->descriAzienda = 'il sottoscritto';
        } else {
           $this->descriAzienda = 'la società';
        }
        $this->giorno = substr($this->tesdoc['datemi'],8,2);
        $this->mese = substr($this->tesdoc['datemi'],5,2);
        $this->anno = substr($this->tesdoc['datemi'],0,4);
		$this->clientSedeLegale =''; // la sede legale verrà stampata al posto della destinazione
    }

    function newPage() {
        $this->AddPage();
        $this->SetFont('helvetica','',11);
    }

    function pageHeader() {
        $this->StartPageGroup();
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->newPage();
    }


    function compose()
    {
        $this->setTesDoc();
        $this->body();
    }

    function body()
    {
        $ucDescAzienda = ucfirst($this->descriAzienda);
        $testo = '
<p>
Gentile cliente, a partire dal 01/01/2019 viene introdotto l’obbligo di emissione di fattura elettronica.
</p>
<p>
Le fatture elettroniche saranno trasmesse unicamente attraverso il Sistema di Interscambio (SdI)
in formato XML.
</p>
<p>
Siamo pertanto a richiedere di controllare i Vostri dati sottoriportati aggiungendovi l\'indirizzo di posta elettronica certificata (PEC) e, se in vostro possesso, il Codice Univoco per il Sistema di Interscambio (SdI) e inviarli ad uno dei seguenti indirizzi di posta elettronica
<a href="mailto:'.$this->pec.'">'.$this->pec.'</a> <a href="mailto:'.$this->intesta4.'">'.$this->intesta4.'</a> oppure consegnando il cartaceo presso i nostri uffici.
</p>
<p>Solo così potremo emettere e trasmettere correttamente le fatture elettroniche a Voi destinate.
</p>
<p></p>
<p></p>
<p><b>RAGIONE SOCIALE</b>: '.str_pad($this->cliente1.''.$this->cliente2,54,'_').'</p>
<p><b>INDIRIZZO</b>: '.str_pad($this->cliente3,65,'_').'</p>
<p><b>CITTÀ</b>: '.str_pad($this->cliente4,66,'_').'</p>
<p><b>CODICE FISCALE - PARTITA IVA</b>: '.str_pad($this->cliente5,49,'_').'</p>
<p><b>INDIRIZZO PEC</b>: ____________________________________________________________</p>
<p><b>CODICE SDI</b>: _______________________________________________________________</p>
<p></p>
<p>DATA:  ___ / ___ / ______ </p>
<p></p>
<p></p>
<p>
Vi ringraziamo in anticipo per la collaborazione e con l’occasione porgiamo cordiali saluti.
</p>';
    $this->SetFont('helvetica','',12);
    $this->WriteHTMLCell(184,4,10,65,$testo, 0, 0, 0, true, 'J');
    }
    function pageFooter()
    {
    }
}
?>