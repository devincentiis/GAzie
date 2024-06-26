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
require('booking_template.php');
#[AllowDynamicProperties]
class BookingSummary extends Template
{
    function setTesDoc()
    {
        $this->tesdoc = $this->docVars->tesdoc;
        $this->giorno = substr($this->tesdoc['datemi'],8,2);
        $this->mese = substr($this->tesdoc['datemi'],5,2);
        $this->anno = substr($this->tesdoc['datemi'],0,4);
        $this->docVars->gazTimeFormatter->setPattern('MMMM');
        $this->nomemese = ucwords($this->docVars->gazTimeFormatter->format(new DateTime($this->tesdoc['datemi'])));
        $this->sconto = $this->tesdoc['sconto'];
        $this->trasporto = $this->tesdoc['traspo'];
        $this->tipdoc = 'Prenotazione n.'.$this->tesdoc['numdoc'].'/'.$this->tesdoc['seziva'].' del '.$this->giorno.' '.$this->nomemese.' '.$this->anno;
        $this->show_artico_composit = $this->docVars->show_artico_composit;
    }
    function newPage() {
        $this->AddPage();
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
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
        $lines = $this->docVars->getRigo('italian');
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
                    $this->Cell(25, 6, $rigo['codart'],1,0,'L',0,'',1);
                    $this->Cell(80, 6, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(7,  6, $rigo['unimis'],1,0,'C',0,'',1);
                    $this->Cell(16, 6, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R');
                    $this->Cell(18, 6, number_format($rigo['prelis'],$this->decimal_price,',',''),1,0,'R');
                    if ($rigo['sconto']>0) {
                       $this->Cell(8, 6,  number_format($rigo['sconto'],1,',',''),1,0,'C');
                    } else {
                       $this->Cell(8, 6, '',1,0,'C');
                    }
                    $this->Cell(20, 6, number_format($rigo['importo'],2,",","."),1,0,'R');
                    $this->Cell(12, 6, number_format($rigo['pervat'],2,",","."),1,1,'R');
                    break;
                case "1":
                    $this->Cell(25, 6, $rigo['codart'],1,0,'L',0,'',1);
                    $this->Cell(80, 6, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(49, 6, '',1);
                    $this->Cell(20, 6, number_format($rigo['importo'],2,",","."),1,0,'R');
                    $this->Cell(12, 6, number_format($rigo['pervat'],2,",","."),1,1,'R');
                    break;
                case "2":
                    $this->Cell(105,6,$rigo['descri'],'LR',0,'L',0,'',1);
                    $this->Cell(81,6,'','R',1);
                    break;
                case "3":
                    $this->Cell(25,6,'',1,0,'L');
                    $this->Cell(80,6,$rigo['descri'],'B',0,'L',0,'',1);
                    $this->Cell(49,6,'','B',0,'L');
                    $this->Cell(20,6,number_format($value['impcast'],2,",","."),1,0,'R');
                    $this->Cell(12,6,'',1,1,'R');
                    break;
                case "6":
                    $this->writeHtmlCell(186,6,10,$this->GetY(),$rigo['descri'],1,1);
                    break;
				 case "7":
                    $this->writeHtmlCell(186,6,10,$this->GetY(),$rigo['descri'],'LR',1);
                    break;
                case "210": // se è un'articolo composto visualizzo la quantità
                    if ( $this->show_artico_composit=="1" ) {
						$oldy = $this->GetY();
						$this->SetFont('helvetica', '', 8);
						$this->SetY($this->GetY()-6);
						$this->Cell(104, 8, '('.$rigo['unimis'].' '.gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity).')',0,0,'R');
						$this->SetY( $oldy );
						$this->SetFont('helvetica', '', 9);
					}
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
        $this->Rect(10,$y,186,212-$y); //questa marca le linee dx e sx del documento
        //stampo il castelletto
        $this->SetY(212);
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->Cell(62,6, 'Pagamento',1,0,'C',1);
        $this->Cell(68,6, '',1,0,'C',1);
        $this->Cell(56,6, 'T O T A L E',1,1,'C',1);
        $this->SetFont('helvetica', '', 8);
        if (isset($this->pagame['descri'])){
        $this->Cell(62,6, $this->pagame['descri'],1,0,'C');
        }

        $this->docVars->setTotal($this->tesdoc['traspo']);

        $totimpmer = $this->docVars->totimpmer;
        $speseincasso = $this->docVars->speseincasso;
        $totimpfat = $this->docVars->totimpfat;
        $totivafat = $this->docVars->totivafat;
        $vettor = $this->docVars->vettor;
        $impbol = $this->docVars->impbol;
		$taxstamp=$this->docVars->taxstamp;
        //stampo i totali
        $this->SetY(200);
        $this->SetFont('helvetica','',9);
        $this->Cell(36, 6,'Tot. Corpo',1,0,'C',1);
        $this->Cell(16, 6,'% Sconto',1,0,'C',1);
        $this->Cell(24, 6,'Spese Incasso',1,0,'C',1);
        //$this->Cell(26, 6,'Trasporto',1,0,'C',1);
        $this->Cell(36, 6,'Tot.Imponibile',1,0,'C',1);
        $this->Cell(26, 6,'Tot. I.V.A.',1,0,'C',1);
        $this->Cell(22, 6,'',1,1,'C',1);
		if ( $this->tesdoc['print_total']>0){
			$this->Cell(36, 6, number_format($totimpmer,2,",","."),1,0,'C');
			$this->Cell(16, 6, number_format($this->tesdoc['sconto'],2,",","."),1,0,'C');
			$this->Cell(24, 6, number_format($speseincasso,2,",","."),1,0,'C');
			//$this->Cell(26, 6, number_format($this->tesdoc['traspo'],2,",","."),1,0,'C');
			$this->Cell(36, 6, number_format($totimpfat,2,",","."),1,0,'C');
			$this->Cell(26, 6, number_format($totivafat,2,",","."),1,0,'C');
			$this->Cell(22, 6, '',1,0,'C');
		} else {
			$this->Cell(186, 6, '',1);
		}

		$this->SetY(224);
		$this->Cell(100, 6, 'Pagamenti effettuati', 'LTR', 0, 'C', 1);
		$this->Cell(30, 6, 'Importo', 'LTR', 1, 'C', 1);
		$payments = $this->docVars->getPag();
		$payed=0;
		foreach ($payments AS $key => $pay) {
			$this->Cell(100, 6, $pay['created'].' '.$pay['type'] . '-' . $pay['txn_id'] , 'LR', 0, 'C', 0, '', 1);
			$this->Cell(30, 6, number_format($pay['payment_gross'],2,",","."), 'LR', 1, 'C');
			$payed += $pay['payment_gross'];
		}
		$this->Cell(130, 6, '', 'T', 0, 'C');

        $this->SetY(218);
        $this->Cell(130);
        $this->SetFont('helvetica','B',18);
		if ( $this->tesdoc['print_total']>0){
			$this->Cell(56, 6, '€ '.number_format(($totimpfat + $totivafat + $impbol+$taxstamp),2,",","."), 1, 0, 'C');
			if ($payed>0){
				 $this->SetY(227);
				$this->Cell(130);
				$this->SetFont('helvetica','B',12);
				$this->Cell(56, 6, 'SALDO', 1, 0, 'C', 1);
				 $this->SetY(233);
				$this->Cell(130);
				$this->SetFont('helvetica','B',18);
				$this->Cell(56, 6, '€ '.number_format(($totimpfat + $totivafat + $impbol+$taxstamp-$payed),2,",","."), 1, 0, 'C');
			}
        } else {
			$this->Cell(56, 24, '',1);
		}
		$this->SetY(224);
       /*
      $this->SetFont('helvetica','',9);
      $this->Cell(62, 6,'Spedizione',1,1,'C',1);
      $this->Cell(62, 6,$this->tesdoc['spediz'],1,1,'C');
      $this->Cell(62, 6,'Vettore',1,1,'C',1);
      $this->Cell(186,6,((isset($vettor['descri']))?$vettor['descri']:''),1,1,'L');
      $this->Cell(36, 6);
      */
      // $this->Cell(150,6,'Firma del cliente per approvazione:',0,1,'L');
      //$this->Cell(86, 6);
      //$this->Cell(100,6,'','B',1,'L');
    }

    function Footer()
    {
        //Page footer
        $this->SetY(-20);
        $this->SetFont('helvetica', '', 8);
        if ( $this->sedelegale!="" ) {
            $this->MultiCell(184, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4 . ' ' . "SEDE LEGALE: ".$this->sedelegale, 0, 'C', 0);
        } else {
            $this->MultiCell(184, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4, 0, 'C', 0);
        }
    }
}

?>
