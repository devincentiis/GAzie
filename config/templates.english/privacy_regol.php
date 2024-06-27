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

class RegolamentoPrivacy extends Template
{
    function setTesDoc()
    {
		$this->tesdoc = $this->docVars->tesdoc;
        $this->user = gaz_dbi_get_row($this->docVars->gTables['admin'], "user_id", $this->docVars->tesdoc['clfoco']);
		$this->intesta1 = $this->docVars->intesta1;
        $this->intesta1bis = $this->docVars->intesta1bis;
        $this->intesta2 = $this->docVars->intesta2;
        $this->intesta3 = $this->docVars->intesta3;
        $this->intesta4 = $this->docVars->intesta4;
        $this->colore = $this->docVars->colore;
        $this->tipdoc = 'REGOLAMENTO UTILIZZO E GESTIONE RISORSE INFORMATICHE';
		$this->luogo = $this->docVars->azienda['citspe'].' ('.$this->docVars->azienda['prospe'].'), lì '.date('d M Y');
        $this->giorno = substr($this->tesdoc['datemi'],8,2);
        $this->mese = substr($this->tesdoc['datemi'],5,2);
        $this->anno = substr($this->tesdoc['datemi'],0,4);
		$this->pers_title='';
    }

    function newPage() {
        $this->AddPage();
        $this->SetFont('helvetica','',11);
    }

    function pageHeader() {
		$this->setTopMargin(53);
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
        $this->SetFont('helvetica','B',11);
        $this->Cell(184,4,'“Regolamento per l’utilizzo e la gestione delle risorse strumentali informatiche e telematiche aziendali”', 'B', 1, 'C', 1, '', 1);
		$this->Ln(5);
		$this->SetFont('helvetica','',11);
		$this->writeHtml($this->tesdoc['corpo']);
    }
    function pageFooter()
    {
    }
}
?>