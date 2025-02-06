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
require("../../library/include/calsca.inc.php");
require('template.php');

class FatturaAcquisto extends Template
{

    function setTesDoc()
    {
        $this->tesdoc = $this->docVars->tesdoc;
        $this->giorno = substr($this->tesdoc['datfat'],8,2);
        $this->mese = substr($this->tesdoc['datfat'],5,2);
        $this->anno = substr($this->tesdoc['datfat'],0,4);
        $this->nomemese = ucwords(strftime("%B", mktime (0,0,0,substr($this->tesdoc['datfat'],5,2),1,0)));
        $this->sconto = $this->tesdoc['sconto'];
        $this->trasporto = $this->tesdoc['traspo'];
        if ($this->tesdoc['tipdoc'] == 'ADT') {
            $descri='Ricevuto DdT d\'acquisto n.';
        } elseif ($this->tesdoc['tipdoc'] == 'AFA' || $this->tesdoc['tipdoc'] == 'AFT') {
            $descri='Ricevuta Fattura d\'acquisto n.';
        } elseif ($this->tesdoc['tipdoc'] == 'AFC') {
            $descri='Ricevuta Nota di credito n.';
        } elseif ($this->tesdoc['tipdoc'] == 'AFD') {
            $descri='Ricevuta Nota di debito n.';
        } else {
            $descri='** documento sconosciuto **';
        }
        $this->tipdoc=$descri.$this->tesdoc['numfat'].'/'.$this->tesdoc['seziva'].' del '.$this->giorno.' '.$this->nomemese.' '.$this->anno;

    }

    function newPage() {
        $this->AddPage();
        $this->SetFont('helvetica','',9);
        $this->Cell(25,6,'Codice',1,0,'C',1);
        $this->Cell(80,6,'Descrizione',1,0,'C',1);
        $this->Cell(7, 6,'U.m.',1,0,'C',1);
        $this->Cell(16,6,'Quantità',1,0,'C',1);
        $this->Cell(18,6,'Prezzo',1,0,'C',1);
        $this->Cell(8, 6,'%Sc.',1,0,'C',1);
        $this->Cell(20,6,'Importo',1,0,'C',1);
        $this->Cell(12,6,'%IVA',1,1,'C',1);
    }

    function pageHeader() {
        $this->StartPageGroup();
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->newPage();
    }


    function compose()
    {
        $this->body();
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
                if (isset ($rigo['identifier']) && strlen ($rigo['identifier'])>0){
                  if (intval ($rigo['expiry'])>0){
                    $rigo['descri']=$rigo['descri']." - lot: ".$rigo['identifier']." ".gaz_format_date($rigo['expiry']);
                  } else {
                    $rigo['descri']=$rigo['descri']." - lot: ".$rigo['identifier'];
                  }
                }

                switch($rigo['tiprig']) {
                case "0":
                    $this->Cell(25, 6, $rigo['codart'],1,0,'L');
                    $this->Cell(80, 6, $rigo['descri'],1,0,'L');
                    $this->Cell(7,  6, $rigo['unimis'],1,0,'C');
                    $this->Cell(16, 6, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R');
                    $this->Cell(18, 6, number_format($rigo['prelis'],$this->decimal_price,',',''),1,0,'R');
                    if ($rigo['sconto']>0) {
                       $this->Cell(8, 6,  number_format($rigo['sconto'],1,',',''),1,0,'C');
                    } else {
                       $this->Cell(8, 6, '',1,0,'C');
                    }
                    $this->Cell(20, 6, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(12, 6, gaz_format_number($rigo['pervat']),1,1,'R');
                    break;
                case "1":
                    $this->Cell(25, 6, $rigo['codart'],1,0,'L');
                    $this->Cell(80, 6, $rigo['descri'],1,0,'L');
                    $this->Cell(49, 6, '',1);
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
                case "6":
                case "8":
                    $this->writeHtmlCell(186,6,10,$this->GetY(),$rigo['descri'],1,1);
                    break;
                }
       }
    }

    function pageFooter()
    {
        $this->Cell(186,6,'','T',1);
        //stampo il castelletto
        $this->SetY(180);
        $this->SetFont('helvetica','B',48);
        $this->SetTextColor(255,150,150);
        $this->Cell(186,20,'N O N   F I S C A L E',0,1,'C');
        $this->SetTextColor(0,0,0);
        $this->SetY(212);
        $this->SetFont('helvetica','',8);
        $this->Cell(62,6,'Pagamento','LTR',0,'C',1);
        $this->Cell(68,6,'Castelletto I.V.A.','LTR',0,'C',1);
        $this->Cell(56,6,'T O T A L E    F A T T U R A','LTR',1,'C',1);
        $this->Cell(62,6,$this->pagame['descri'],'LBR',0,'L');
        $this->Cell(18,4,'Imponibile','LR',0,'C',1);
        $this->Cell(32,4,'Aliquota','LR',0,'C',1);
        $this->Cell(18,4,'Imposta','LR',1,'C',1);
        $this->docVars->setTotal();
        foreach ($this->docVars->cast as $key => $value) {
            $this->Cell(62);
            $this->Cell(18, 4, gaz_format_number($value['impcast']).' ','LR', 0, 'R');
            $this->Cell(32, 4, $value['descriz'],0,0,'C');
            $this->Cell(18, 4, gaz_format_number($value['ivacast']).' ','LR',1,'R');
        }
        //azzero il castelletto
        foreach ($this->docVars->castel as $i => $value) {
            unset($this->docVars->castel[$i]);
        }


        $totimpmer = $this->docVars->totimpmer;
        $speseincasso = $this->docVars->speseincasso;
        $totimpfat = $this->docVars->totimpfat;
        $totivafat = $this->docVars->totivafat;
        $vettor = $this->docVars->vettor;
        $impbol = $this->docVars->impbol;
        $totriport = $this->docVars->totriport;
        $taxstamp = $this->docVars->taxstamp;

        //effettuo il calcolo degli importi delle scadenze
        $totpag = $totimpfat + $impbol + $totriport + $totivafat;
        $ratpag = CalcolaScadenze($totpag, $this->giorno, $this->mese, $this->anno, $this->pagame['tipdec'],$this->pagame['giodec'],$this->pagame['numrat'],$this->pagame['tiprat'],$this->pagame['mesesc'],$this->pagame['giosuc']);
        if ($ratpag){
           //allungo l'array fino alla 4^ scadenza
           $ratpag['import'] = array_pad($ratpag['import'],4,'');
           $ratpag['giorno'] = array_pad($ratpag['giorno'],4,'');
           $ratpag['mese'] = array_pad($ratpag['mese'],4,'');
           $ratpag['anno'] = array_pad($ratpag['anno'],4,'');
        } else {
           for ($i = 0; $i <= 3; $i++) {
               $ratpag['import'][$i] = "";
               $ratpag['giorno'][$i] = "";
               $ratpag['mese'][$i] = "";
               $ratpag['anno'][$i] = "";
           }
        }
        //stampo i totali
        $this->SetY(200);
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->SetFont('helvetica', '', 9);
        $this->Cell(36, 6,'Totale merce','LTR',0,'C',1);
        $this->Cell(16, 6,'% Sconto','LTR',0,'C',1);
        $this->Cell(24, 6,'Spese Incasso','LTR',0,'C',1);
        $this->Cell(26, 6,'Trasporto','LTR',0,'C',1);
        $this->Cell(36, 6,'Tot.Imponibile','LTR',0,'C',1);
        $this->Cell(26, 6,'Tot. I.V.A.','LTR',0,'C',1);
        $this->Cell(22, 6,'Bolli','LTR',1,'C',1);
        if ($totimpmer > 0) {
           $this->Cell(36, 6, gaz_format_number($totimpmer),'LBR',0,'C');
        } else {
           $this->Cell(36, 6,'','LBR');
        }
        if ($this->tesdoc['sconto'] > 0) {
           $this->Cell(16, 6, gaz_format_number($this->tesdoc['sconto']),'LBR',0,'C');
        } else {
           $this->Cell(16, 6,'','LBR');
        }
        if ($speseincasso > 0) {
           $this->Cell(24, 6, gaz_format_number($speseincasso),'LBR',0,'C');
        } else {
           $this->Cell(24, 6,'','LBR');
        }
        if ($this->trasporto > 0) {
           $this->Cell(26, 6, gaz_format_number($this->trasporto),'LBR',0,'C');
        } else {
           $this->Cell(26, 6,'','LBR');
        }
        if ($totimpfat > 0) {
           $this->Cell(36, 6, gaz_format_number($totimpfat),'LBR',0,'C');
        } else {
           $this->Cell(36, 6,'','LBR');
        }
        if ($totivafat > 0) {
           $this->Cell(26, 6, gaz_format_number($totivafat),'LBR',0,'C');
        } else {
           $this->Cell(26, 6,'','LBR');
        }
        if ($impbol > 0) {
           $this->Cell(22, 6, gaz_format_number($impbol),'LBR',1,'C');
        } else {
           $this->Cell(22, 6,'','LBR');
        }
        $this->SetY(218);
        $this->Cell(130);
        $totale = $totimpfat + $totivafat + $impbol;
        $this->SetFont('helvetica','B',18);
        $this->Cell(56, 24, '€ '.gaz_format_number($totale),'LBR', 1, 'C');
        $this->SetY(224);
        $this->SetFont('helvetica','',9);
        $this->Cell(62, 6, 'Banca d\'appoggio','LTR',1,'C',1);
        $this->Cell(62, 6, $this->banapp['descri'],'LR',1,'L');
        if (!empty($this->banapp['descri'])) {
           $this->Cell(62, 6, ' ABI '.sprintf("%05d",$this->banapp['codabi']).' CAB '.$this->banapp['codcab'],'LRB',1,'C');
        } else {
           $this->Cell(62, 6,'','LRB',1);
        }
        $this->Cell(130,6, 'Date di Scadenza e Importo Rate','LTR',0,'C',1);
        $this->Cell(56, 6, 'Totale dei riporti','LTR',1,'C',1);
        $this->Cell(32, 6, $ratpag['giorno']['0'].'-'.$ratpag['mese']['0'].'-'.$ratpag['anno']['0'],'LR',0,'C');
        $this->Cell(33, 6, $ratpag['giorno']['1'].'-'.$ratpag['mese']['1'].'-'.$ratpag['anno']['1'],'LR',0,'C');
        $this->Cell(32, 6, $ratpag['giorno']['2'].'-'.$ratpag['mese']['2'].'-'.$ratpag['anno']['2'],'LR',0,'C');
        $this->Cell(33, 6, $ratpag['giorno']['3'].'-'.$ratpag['mese']['3'].'-'.$ratpag['anno']['3'],'LR',0,'C');
        $this->Cell(56, 6, '','R',1,'C');
        if ($ratpag['import']['0'] != 0) {
            $this->Cell(32, 6, gaz_format_number($ratpag['import']['0']),'LBR',0,'C');
            } else {
            $this->Cell(32, 6,'','LBR');
        }
        if ($ratpag['import']['1'] != 0) {
           $this->Cell(33, 6, gaz_format_number($ratpag['import']['1']),'LBR',0,'C');
           } else {
           $this->Cell(33, 6,'','LBR');
        }
        if ($ratpag['import']['2'] != 0) {
           $this->Cell(32, 6, gaz_format_number($ratpag['import']['2']),'LBR',0,'C');
           } else {
           $this->Cell(32, 6,'','LBR');
        }
        if ($ratpag['import']['3'] != 0) {
           $this->Cell(33, 6, gaz_format_number($ratpag['import']['3']),'LBR',0,'C');
           } else {
           $this->Cell(33, 6,'','LBR');
        }
        if ($totriport != 0) {
           $this->Cell(56, 6, gaz_format_number($totriport),'BR',1,'C');
           } else {
           $this->Cell(56, 6,'','BR',1);
        }
    }

    function Footer()
    {
        //Document footer
        $this->SetY(-20);
        $this->SetFont('helvetica','',8);
        $this->MultiCell(184,4,$this->intesta1.' '.$this->intesta2.' '.$this->intesta3.' '.$this->intesta4.' ',0,'C',0);
    }
}

?>
