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
require('template.php');

class Lettera extends Template
{

    function setTesDoc()
    {
        $this->tesdoc = $this->docVars->tesdoc;
        $this->giorno = substr($this->tesdoc['datemi'],8,2);
        $this->mese = substr($this->tesdoc['datemi'],5,2);
        $this->anno = substr($this->tesdoc['datemi'],0,4);
        $this->nomemese = ucwords(strftime("%B", mktime (0,0,0,substr($this->tesdoc['datemi'],5,2),1,0)));
        if ($this->tesdoc['tipdoc']=='SOL') {
            $this->tipdoc = 'Sollecito del '.$this->giorno.' '.$this->nomemese.' '.$this->anno;
        } elseif ($this->tesdoc['tipdoc']=='DIC')  {
            $this->tipdoc = 'Dichiarazione del '.$this->giorno.' '.$this->nomemese.' '.$this->anno;
        } else  {
            $this->tipdoc = 'Lettera n.'.$this->tesdoc['numdoc'].' del '.$this->giorno.' '.$this->nomemese.' '.$this->anno;
        }
    }

    function newPage() {
        $this->SetTopMargin(90);
        $this->AddPage();
        $this->SetFont('helvetica','',10);
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
        $this->writeHtml($this->docVars->tesdoc['corpo'],true,0,true,0);
    }

    function pageFooter()
    {
        if (!empty($this->docVars->tesdoc['signature'])) {
            $this->Cell(174,5,$this->docVars->tesdoc['signature'],0,0,'R');
        }
    }

    function Footer()
    {
        //Document footer
        $this->SetY(-20);
        $this->SetFont('helvetica','',8);
        $this->Cell(184,1,'','B',1);
        $this->MultiCell(184,4,$this->intesta1.' '.$this->intesta2.' '.$this->intesta3.' '.$this->intesta4.' ',0,'C',0);
    }
}

?>