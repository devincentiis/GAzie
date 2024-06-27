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


require('template.php');

class PreventivoFornitore extends Template
{
    function setTesDoc()
    {
        $this->tesdoc = $this->docVars->tesdoc;
        $this->giorno = substr($this->tesdoc['datemi'],8,2);
        $this->mese = substr($this->tesdoc['datemi'],5,2);
        $this->anno = substr($this->tesdoc['datemi'],0,4);
        $this->nomemese = ucwords(strftime("%B", mktime (0,0,0,substr($this->tesdoc['datemi'],5,2),1,0)));
        $this->sconto = $this->tesdoc['sconto'];
        $this->trasporto = $this->tesdoc['traspo'];
        $this->tipdoc = 'Richiesta di preventivo n.'.$this->tesdoc['numdoc'].'/'.$this->tesdoc['seziva'].' del '.$this->giorno.' '.$this->nomemese.' '.$this->anno;
    }
    function newPage() {
        $this->AddPage();
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->Ln(4);
        $this->SetFont('helvetica','',12);
        $this->Cell(186,8,'Vogliate cortesemente inviarci la Vostra migliore offerta per l\'acquisto dei sottoelencati prodotti:',0,1);
        $this->SetFont('helvetica','',9);
        $this->Cell(25,6,'Codice',1,0,'L',1);
        $this->Cell(80,6,'Descrizione',1,0,'L',1);
        $this->Cell(7, 6,'U.m.',1,0,'C',1);
        $this->Cell(16,6,'Quantità',1,0,'R',1);
        $this->Cell(18,6,'Prezzo',1,0,'R',1);
        $this->Cell(8, 6,'%Sc.',1,0,'C',1);
        $this->Cell(20,6,'Importo',1,0,'R',1);
        $this->Cell(12,6,'%IVA',1,1,'R',1);
    }

    function pageHeader()
    {
        $this->setTesDoc();
        $this->StartPageGroup();
        $this->newPage();
    }
    function body()
    {
        $lines = $this->docVars->getRigo();
		foreach ($lines AS $key => $rigo) {
            if ($this->GetY() >= 185) {
                $this->Cell(186,6,'','T',1);
                $this->SetFont('helvetica', '', 20);
                $this->SetY(225);
                $this->Cell(186,12,'>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>> ',1,1,'R');
                $this->SetFont('helvetica', '', 9);
                $this->newPage();
                $this->Cell(186,5,'<<< --- SEGUE DA PAGINA PRECEDENTE --- <<< ',0,1);
            }
                switch($rigo['tiprig']) {
                case "0":
                    $this->Cell(25, 6, $rigo['codart'],1,0,'L');
                    $this->Cell(80, 6, $rigo['descri'],1,0,'L');
                    $this->Cell(7,  6, $rigo['unimis'],1,0,'C');
                    $this->Cell(16, 6, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R');
                    if ($rigo['prelis'] > 0) {
                       $this->Cell(18, 6, number_format($rigo['prelis'],$this->decimal_price,',',''),1,0,'R');
                    } else {
                       $this->Cell(18, 6, '',1);
                    }
                    if ($rigo['sconto']> 0) {
                       $this->Cell(8, 6,  number_format($rigo['sconto'],1,',',''),1,0,'C');
                    } else {
                       $this->Cell(8, 6, '',1);
                    }
                    if ($rigo['importo'] > 0) {
                       $this->Cell(20, 6, gaz_format_number($rigo['importo']),1,0,'R');
                    } else {
                       $this->Cell(20, 6, '',1);
                    }
                    $this->Cell(12, 6, gaz_format_number($rigo['pervat']),1,1,'R');
                    break;
                case "1":
                    $this->Cell(25, 6, '','LBR',0,'L');
                    $this->Cell(80, 6, $rigo['descri'],'LBR',0,'L');
                    $this->Cell(49, 6,'',1);
                    $this->Cell(20, 6, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(12, 6, gaz_format_number($rigo['pervat']),1,1,'R');
                    break;
                case "2":
                    $this->Cell(25,6,'','L');
                    $this->Cell(80,6,$rigo['descri'],'LR',0,'L');
                    $this->Cell(81,6,'','R',1);
                    break;
                case "3":
                    $this->Cell(25,6,'',1,0,'L');
                    $this->Cell(80,6,$rigo['descri'],'B',0,'L');
                    $this->Cell(49,6,'','B',0,'L');
                    $this->Cell(20,6,gaz_format_number($rigo['prelis']),1,0,'R');
                    $this->Cell(12,6,'',1,1,'R');
                    break;
                }
       }
    }


    function compose()
    {
        $this->body();
    }

    function pageFooter()
    {
        $y = $this->GetY();
        $this->Rect(10,$y,186,210-$y); //questa marca le linee dx e sx del documento
        $this->SetY(222);
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->Cell(62,6, 'Pagamento',1,0,'C',1);
        $this->Cell(68,6, 'Castelletto I.V.A.',1,0,'C',1);
        $this->Cell(56,6, 'T O T A L E','LTR',1,'C',1);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(62,6, $this->pagame['descri'],1,0,'C');
        $this->Cell(25,4, 'Imponibile','LR',0,'C',1);
        $this->Cell(18,4, 'Aliquota','LR',0,'C',1);
        $this->Cell(25,4, 'Imposta','LR',1,'C',1);
        $this->docVars->setTotal();
        $totimpmer = $this->docVars->totimpmer;
        $speseincasso = $this->docVars->speseincasso;
        $totimpfat = $this->docVars->totimpfat;
        $totivafat = $this->docVars->totivafat;
        $vettor = $this->docVars->vettor;
        $impbol = $this->docVars->impbol;

        //stampo i totali
        $this->SetY(210);
        $this->SetFont('helvetica','',9);
        $this->Cell(36, 6,'Tot. Corpo','LTR',0,'C',1);
        $this->Cell(16, 6,'% Sconto','LTR',0,'C',1);
        $this->Cell(24, 6,'Spese Incasso','LTR',0,'C',1);
        $this->Cell(26, 6,'Trasporto','LTR',0,'C',1);
        $this->Cell(36, 6,'Tot.Imponibile','LTR',0,'C',1);
        $this->Cell(26, 6,'Tot. I.V.A.','LTR',0,'C',1);
        $this->Cell(22, 6,'Bolli','LTR',1,'C',1);

        $this->Cell(36, 6, '','LBR');
        $this->Cell(16, 6, '','LBR');
        $this->Cell(24, 6, '','LBR');
        $this->Cell(26, 6, '','LBR');
        $this->Cell(36, 6, '','LBR');
        $this->Cell(26, 6, '','LBR');
        $this->Cell(22, 6, '','LBR');
        $this->SetY(228);
        $this->Cell(130);
        $this->SetFont('helvetica','B',18);
        $this->Cell(56, 18, '', 'LBR', 1);
        $this->SetY(234);
        $this->SetFont('helvetica','',9);
        $this->Cell(62, 6,'Spedizione','LTR',1,'C',1);
        $this->Cell(62, 6,$this->tesdoc['spediz'],'LBR',1,'C');
        $this->Cell(72, 6,'Porto','LTR',0,'C',1);
        $this->Cell(29, 6,'Peso netto','LTR',0,'C',1);
        $this->Cell(29, 6,'Peso lordo','LTR',0,'C',1);
        $this->Cell(28, 6,'N.colli','LTR',0,'C',1);
        $this->Cell(28, 6,'Volume','LTR',1,'C',1);
        $this->Cell(72, 6,$this->tesdoc['portos'],'LBR',0,'C');
        if ($this->tesdoc['net_weight'] > 0) {
            $this->Cell(29, 6,gaz_format_number($this->tesdoc['net_weight']),'LRB',0,'C');
        } else {
            $this->Cell(29, 6,'','LRB');
        }
        if ($this->tesdoc['gross_weight'] > 0) {
            $this->Cell(29, 6,gaz_format_number($this->tesdoc['gross_weight']),'LRB',0,'C');
        } else {
            $this->Cell(29, 6,'','LRB');
        }
        if ($this->tesdoc['units'] > 0) {
            $this->Cell(28, 6,$this->tesdoc['units'],'LRB',0,'C');
        } else {
            $this->Cell(28, 6,'','LRB');
        }
        if ($this->tesdoc['volume'] > 0) {
            $this->Cell(28, 6,gaz_format_number($this->tesdoc['volume']),'LRB',1,'C');
        } else {
            $this->Cell(28, 6,'','LRB',1);
        }
    }
    function Footer()
    {
        //Page footer
        $this->SetY(-20);
        $this->SetFont('helvetica', '', 8);
        $this->MultiCell(186, 4, $this->intesta1.' '.$this->intesta2.' '.$this->intesta3.' '.$this->intesta4.' ', 0, 'C', 0);
    }
}

?>