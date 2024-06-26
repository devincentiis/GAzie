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

/*
     !!!!!!!!!!!!!!!  ATTENZIONE !!!!!!!!!!!!!!!!!!!!!
QUESTO  TEMPLATE E' STATO PENSATO PER POTER STAMPARE LE RICEVUTE FISCALI
SU UN FOGLIO DI CARTA A4 PRENUMERATO DALLA TIPOGRAFIA AUTORIZZATA IN MODO
DA RIPORTARE LE DUE COPIE A5 AFFIANCATE
*/

require('buffetti_2xA5.php'); // template=originale buffetti=custom
require("../../library/include/calsca.inc.php");



class Received extends Template_2xA5
{
    function setTesDoc()
    {
      $this->tesdoc = $this->docVars->tesdoc;
      $this->giorno = substr($this->tesdoc['datemi'],8,2);
      $this->mese = substr($this->tesdoc['datemi'],5,2);
      $this->anno = substr($this->tesdoc['datemi'],0,4);
      if ($this->tesdoc['datfat']){
        $this->docVars->gazTimeFormatter->setPattern('MMMM');
        $nomemese = ucwords($this->docVars->gazTimeFormatter->format(new DateTime($this->tesdoc['datemi'])));
      } else {
        $nomemese = '';
      }
      $this->sconto = $this->tesdoc['sconto'];
      $this->trasporto = $this->tesdoc['traspo'];
      $this->virtual_taxstamp=$this->tesdoc['virtual_taxstamp'];
      $this->taxstamp=$this->tesdoc['taxstamp'];
      $descri='Ricevuta fiscale n.';
      if ($this->tesdoc['numdoc']>0){
        $numdoc = $this->tesdoc['numdoc'].'/'.$this->tesdoc['seziva'];
      } else {
        $numdoc = ' _ _ _ _ _ _ _';
      }
      $this->tipdoc = $descri.$numdoc.' del '.$this->giorno.' '.$nomemese.' '.$this->anno;
      $this->datdoc = $this->giorno.' '.$nomemese.' '.$this->anno;
      $this->totddt = 0.00;
    }

    function newPage() {
        $this->AddPage('L','A4');
		$this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
		$this->SetY(76);
        $this->SetFont('helvetica','',8);
        $this->Cell(18,6,'Codice',1,0,'L',1);
        $this->Cell(58,6,'Descrizione',1,0,'L',1);
        $this->Cell(5,6,'UM',1,0,'C',1);
        $this->Cell(12,6,'Quantità',1,0,'R',1);
        $this->Cell(15,6,'Prezzo',1,0,'R',1);
        $this->Cell(4,6,'Sc',1,0,'C',1);
        $this->Cell(15,6,'Importo',1,0,'R',1);
        $this->Cell(6,6,'%IVA',1,0,'R', 1, '', 1);
        $this->Cell(14);
        $this->Cell(18,6,'Codice',1,0,'L',1);
        $this->Cell(58,6,'Descrizione',1,0,'L',1);
        $this->Cell(5,6,'UM',1,0,'C',1);
        $this->Cell(12,6,'Quantità',1,0,'R',1);
        $this->Cell(15,6,'Prezzo',1,0,'R',1);
        $this->Cell(4,6,'Sc',1,0,'C',1);
        $this->Cell(15,6,'Importo',1,0,'R',1);
        $this->Cell(6,6,'%IVA',1,1,'R', 1, '', 1);
    }

    function pageHeader()
    {
        $this->StartPageGroup();
        $this->newPage();
		// set auto page breaks
		$this->SetAutoPageBreak(true, 10);
    }

    function compose()
    {
        $lines = $this->docVars->getRigo();
		foreach ($lines AS $key => $rigo) {
            if (($this->GetY() >= 122 && $this->taxstamp >= 0.01) ||$this->GetY() >= 146 ) { // mi serve per poter stampare la casella del bollo
                $this->Cell(133,5,'','T',1);
                $this->SetFont('helvetica', '', 14);
                $this->SetY(165);
                $this->Cell(133,12,'>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>> ',1,0,'R');
				$this->Cell(14);
                $this->Cell(133,12,'>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>> ',1,1,'R');
                $this->SetFont('helvetica', '', 8);
                $this->newPage();
                $this->Cell(133,5,'<<< --- SEGUE DA PAGINA PRECEDENTE --- <<< ',1,0);
				$this->Cell(14);
                $this->Cell(133,5,'<<< --- SEGUE DA PAGINA PRECEDENTE --- <<< ',1,1);
            }
                switch($rigo['tiprig']) { // la larghezza dei righi dev'essere max 145
                case "0":
                    $this->Cell(18,4, $rigo['codart'],1,0,'L', 0, '', 1);
                    $this->Cell(58,4, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(5,4, $rigo['unimis'],1,0,'C');
                    $this->Cell(12,4, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R');
                    $this->Cell(15,4, number_format($rigo['prelis'],$this->decimal_price,',','.'),1,0,'R');
                    if ($rigo['sconto']>0) {
                       $this->Cell(4,4, floatval($rigo['sconto']),1,0,'C');
                    } else {
                       $this->Cell(4,4, '',1,0,'C');
                    }
                    $this->Cell(15,4, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(6,4, floatval($rigo['pervat']),1,0,'R');
					$this->Cell(14);
                    $this->Cell(18,4, $rigo['codart'],1,0,'L', 0, '', 1);
                    $this->Cell(58,4, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(5,4, $rigo['unimis'],1,0,'C');
                    $this->Cell(12,4, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R');
                    $this->Cell(15,4, number_format($rigo['prelis'],$this->decimal_price,',','.'),1,0,'R');
                    if ($rigo['sconto']>0) {
                       $this->Cell(4,4, floatval($rigo['sconto']),1,0,'C');
                    } else {
                       $this->Cell(4,4, '',1,0,'C');
                    }
                    $this->Cell(15,4, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(6,4, floatval($rigo['pervat']),1,1,'R');
                    break;
                case "1":
                    $this->Cell(76,4, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(36,4, '',1);
                    $this->Cell(15,4, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(6,4, floatval($rigo['pervat']),1,0,'R');
					$this->Cell(14);
                    $this->Cell(76,4, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(36,4, '',1);
                    $this->Cell(15,4, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(6,4, floatval($rigo['pervat']),1,1,'R');
                    break;
                case "2":
                    $this->Cell(133,4,$rigo['descri'],'LR',0,'L',0,'',1);
					$this->Cell(14);
                    $this->Cell(133,4,$rigo['descri'],'LR',1,'L',0,'',1);
                    break;
				}
        }
	}
    function pageFooter()
    {
        $this->docVars->setTotal();
        $totimpmer = $this->docVars->totimpmer;
        $speseincasso = $this->docVars->speseincasso;
        $totimpfat = $this->docVars->totimpfat;
        $totivafat = $this->docVars->totivafat;
        $totivasplitpay = $this->docVars->totivasplitpay;
        $vettor = $this->docVars->vettor;
        $impbol = $this->docVars->impbol;
        $totriport = $this->docVars->totriport;
        $ritenuta = $this->docVars->tot_ritenute;
	    $taxstamp=$this->docVars->taxstamp;
        if ($this->virtual_taxstamp == 0 || $this->virtual_taxstamp == 3) { // azzero i bolli in caso di non addebito al cliente
            $taxstamp=0;
        }
		/*
        //effettuo il calcolo degli importi delle scadenze
        $totpag = $totimpfat+$impbol+$totriport+$totivafat-$ritenuta+$taxstamp-$totivasplitpay;
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
		*/

        if (!empty($this->descriptive_last_row) ) { // aggiungo alla fine un eventuale rigo descrittivo dalla configurazione avanzata azienda
                $this->Cell(133,5,$this->descriptive_last_row,1,0,'L',0,'',1);
				$this->Cell(14);
				$this->Cell(133,5,$this->descriptive_last_row,1,1,'L',0,'',1);
		}

        if ($this->taxstamp >= 0.01 ) {
            if ($this->virtual_taxstamp == 2 || $this->virtual_taxstamp == 3) {
                $this->Cell(70,8,'','L',0,0);
                $this->Cell(63,8,"Bollo assolto ai sensi del","TLR",0,"C");
				$this->Cell(14);
                $this->Cell(70,8,'','L',0,0);
                $this->Cell(63,8,"Bollo assolto ai sensi del","TLR",1,"C");
                $this->Cell(70,8,'','L',0,0);
                $this->Cell(63,8,"decreto MEF 17.06.2014 (art.6)","LR",0,"C");
				$this->Cell(14);
                $this->Cell(70,8,'','L',0,0);
                $this->Cell(63,8,"decreto MEF 17.06.2014 (art.6)","LR",1,"C");
                $this->Cell(70,8,'','L',0,0);
                $this->Cell(63,8," € ".gaz_format_number($this->taxstamp),'LR',0,'C');
				$this->Cell(14);
                $this->Cell(70,8,'','L',0,0);
                $this->Cell(63,8," € ".gaz_format_number($this->taxstamp),'LR',1,'C');
            } else {
                $this->Cell(70,8,'','L',0,0);
                $this->Cell(63,8,"Bollo applicato","LR",0,"C");
				$this->Cell(14);
                $this->Cell(70,8,'','L',0,0);
                $this->Cell(63,8,"Bollo applicato","LR",1,"C");
                $this->Cell(70,8,'','L',0,0);
                $this->Cell(63,8,"sull'originale","LR",0,"C");
				$this->Cell(14);
                $this->Cell(70,8,'','L',0,0);
                $this->Cell(63,8,"sull'originale","LR",1,"C");
                $this->Cell(70,8,'','L',0,0);
                $this->Cell(63,8,"€ ".gaz_format_number($this->taxstamp),'LR',0,'C');
				$this->Cell(14);
                $this->Cell(70,8,'','L',0,0);
                $this->Cell(63,8,"€ ".gaz_format_number($this->taxstamp),'LR',1,'C');
            }
        }
        $y = $this->GetY();
        $this->Rect(10,$y,133,155-$y); //questa marca le linee dx e sx del documento
        $this->Rect(157,$y,133,155-$y); //questa marca le linee dx e sx del documento

        /*
		// bolli tratte
        if ($impbol > 0) {
          $this->Cell(40,4, gaz_format_number($impbol),'LBR', 0,'C');
        } else {
          $this->Cell(40,4,'','LBR');
        }

		$this->Cell(14);
        if ($impbol > 0) {
          $this->Cell(40,4, gaz_format_number($impbol),'LBR', 0,'C');
        } else {
          $this->Cell(40,4,'','LBR');
        }
        */
        $this->SetY(172);
		$this->Setx(0);
		$this->SetFont('helvetica', '', 12);
        foreach ($this->docVars->cast as $key => $value) {
			$this->Setx(0);
                if ($this->tesdoc['id_tes'] > 0) {

                   $this->Cell(30,4, gaz_format_number($value['impcast']).' ', 0, 0, 'R',0,'',1);
				   $this->Cell(2);
                   $this->Cell(8,4, intval($value['periva']),0,0,'R',0,'',1);
				   $this->Cell(2);
                   $this->Cell(22,4, gaz_format_number($value['ivacast']).' ',0,0,'R',0,'',1);
				   $this->Cell(44);
                   $this->Cell(45,4,'',0);
                   $this->Cell(30,4, gaz_format_number($value['impcast']).' ', 0, 0, 'R',0,'',1);
				   $this->Cell(2);
                   $this->Cell(8,4, intval($value['periva']),0,0,'R',0,'',1);
				   $this->Cell(2);
                   $this->Cell(22,4, gaz_format_number($value['ivacast']).' ',0,1,'R',0,'',1);
                } else {
                   $this->Cell(100,4,'','LR',1);
                 }
        }
		$this->SetFont('helvetica', '', 7);
        //stampo i totali
        $this->SetY(153);
        $this->Cell(37, 5,'Tot. Corpo','LTR',0,'C',1);
        $this->Cell(19, 5,'% Sconto','LTR',0,'C',1);
        $this->Cell(20, 5,'Spese Incasso','LTR',0,'C',1);
        $this->Cell(20, 5,'Trasporto','LTR',0,'C',1);
        $this->Cell(37, 5,'Tot.Imponibile','LTR',0,'C',1);
		$this->Cell(14);
        $this->Cell(37, 5,'Tot. Corpo','LTR',0,'C',1);
        $this->Cell(19, 5,'% Sconto','LTR',0,'C',1);
        $this->Cell(20, 5,'Spese Incasso','LTR',0,'C',1);
        $this->Cell(20, 5,'Trasporto','LTR',0,'C',1);
        $this->Cell(37, 5,'Tot.Imponibile','LTR',1,'C',1);
        if ($totimpmer > 0) {
           $this->Cell(37, 5, gaz_format_number($totimpmer),'LBR',0,'C');
        } else {
           $this->Cell(37, 5,'','LBR');
        }
        if ($this->tesdoc['sconto'] > 0) {
           $this->Cell(19, 5, gaz_format_number($this->tesdoc['sconto']),'LBR',0,'C');
        } else {
           $this->Cell(19, 5,'','LBR');
        }
        if ($speseincasso > 0) {
           $this->Cell(20, 5, gaz_format_number($speseincasso),'LBR',0,'C');
        } else {
           $this->Cell(20, 5,'','LBR');
        }
        if ($this->trasporto > 0) {
           $this->Cell(20, 5, gaz_format_number($this->trasporto),'LBR',0,'C');
        } else {
           $this->Cell(20, 5,'','LBR');
        }
        if ($totimpfat > 0) {
           $this->Cell(37, 5, gaz_format_number($totimpfat),'LBR',0,'C');
        } else {
           $this->Cell(37, 5,'','LBR');
        }
		$this->Cell(14);
        if ($totimpmer > 0) {
           $this->Cell(37, 5, gaz_format_number($totimpmer),'LBR',0,'C');
        } else {
           $this->Cell(37, 5,'','LBR');
        }
        if ($this->tesdoc['sconto'] > 0) {
           $this->Cell(19, 5, gaz_format_number($this->tesdoc['sconto']),'LBR',0,'C');
        } else {
           $this->Cell(19, 5,'','LBR');
        }
        if ($speseincasso > 0) {
           $this->Cell(20, 5, gaz_format_number($speseincasso),'LBR',0,'C');
        } else {
           $this->Cell(20, 5,'','LBR');
        }
        if ($this->trasporto > 0) {
           $this->Cell(20, 5, gaz_format_number($this->trasporto),'LBR',0,'C');
        } else {
           $this->Cell(20, 5,'','LBR');
        }
        if ($totimpfat > 0) {
           $this->Cell(37, 5, gaz_format_number($totimpfat),'LBR',0,'C');
        } else {
           $this->Cell(37, 5,'','LBR');
        }
        $this->SetY(184);
        $this->Cell(103,9,'',0);
        $totale = $totimpfat + $totivafat + $impbol+ $taxstamp;
        if ($this->tesdoc['id_tes'] > 0) {
            $this->SetFont('helvetica','B',14);
            $this->Cell(33, 9, '€ '.gaz_format_number($totale-$totivasplitpay),0, 0, 'C');
        } else {
           $this->Cell(33,9,'',0,0);
        }
		$this->Cell(19);
        $this->Cell(100,9,'',0);
        if ($this->tesdoc['id_tes'] > 0) {
            $this->SetFont('helvetica','B',14);
            $this->Cell(33, 9, '€ '.gaz_format_number($totale-$totivasplitpay),0, 1, 'C');
        } else {
           $this->Cell(33,9,'',0,1);
        }
	}

    function Footer()
    {
    }
}
?>
