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
Questo di preventivo a cliente è un esempio di template senza logo ed intestazione aziendale
utilizzabile su carte intestate (prestampate da tipografia), può essere scelto attraverso la
finestra modale(dialog) che si apre quando si clicca sulla stampante del report
Antonio de Vincentiis
*/
require('template_lh.php');
class PreventivoCliente extends Template
{

  public $giorno;
  public $mese;
  public $anno;
  public $nomemese;
  public $sconto;
  public $trasporto;
  public $tipdoc;
  public $show_artico_composit;
  public $extdoc_acc;
  public $numPages;
  public $_tplIdx;

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
      $this->tipdoc = 'Preventivo a Cliente n.'.$this->tesdoc['numdoc'].'/'.$this->tesdoc['seziva'].' del '.$this->giorno.' '.$this->nomemese.' '.$this->anno;
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
                    $this->Cell(25, 6, $rigo['codart'],1,0,'L',0,'',1);
                    $this->Cell(80, 6, $rigo['descri'],1,0,'L',0,'',1);
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
                    $this->Cell(25, 6, $rigo['codart'],1,0,'L',0,'',1);
                    $this->Cell(80, 6, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(49, 6, '',1);
                    $this->Cell(20, 6, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(12, 6, gaz_format_number($rigo['pervat']),1,1,'R');
                    break;
                case "2":
                    $this->Cell(25,6,'','L');
                    $this->Cell(80,6,$rigo['descri'],'LR',0,'L',0,'',1);
                    $this->Cell(81,6,'','R',1);
                    break;
                case "3":
                    $this->Cell(25,6,'',1,0,'L');
                    $this->Cell(80,6,$rigo['descri'],'B',0,'L',0,'',1);
                    $this->Cell(49,6,'','B',0,'L');
                    $this->Cell(20,6,gaz_format_number($rigo['prelis']),1,0,'R');
                    $this->Cell(12,6,'',1,1,'R');
                    break;
                case "6":
                    $this->writeHtmlCell(186,0,10,$this->GetY(),$rigo['descri'],1,1);
                    break;
                case "14":
                    $this->Cell(25, 6, "",1,0,'L'); //$rigo['codart']
                    $this->Cell(80, 6, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(7,  6, $rigo['unimis'],1,0,'C');
                    $this->Cell(16, 6, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R');
                    $this->Cell(58, 6, "",1,1,'R');
                    break;
                case "50":
                    $this->Cell(25, 6, $this->docVars->ExternalDoc[$rigo['id_rig']]['oriname'].'.'.$this->docVars->ExternalDoc[$rigo['id_rig']]['ext'],1,0,'L',0,'',1);
                    $this->Cell(100, 6, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(7,  6, $rigo['unimis'],1,0,'C');
                    $this->Cell(14, 6, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R');
                    $this->Cell(40, 6, '',1,1);
                    break;
                case "51":
                    $this->Cell(25, 6,  $this->docVars->ExternalDoc[$rigo['id_rig']]['oriname'].'.'.$this->docVars->ExternalDoc[$rigo['id_rig']]['ext'],1,0,'L',0,'',1);
                    $this->Cell(100,6,$rigo['descri'],'LR',0,'L',0,'',1);
                    $this->Cell(61,6,'','R',1);
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
        $this->Cell(68,6, 'Castelletto I.V.A.',1,0,'C',1);
        $this->Cell(56,6, 'T O T A L E',1,1,'C',1);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(62,6, $this->pagame['descri'],1,0,'C');
        $this->Cell(25,4, 'Imponibile',1,0,'C',1);
        $this->Cell(18,4, 'Aliquota',1,0,'C',1);
        $this->Cell(25,4, 'Imposta',1,1,'C',1);
        $this->docVars->setTotal($this->tesdoc['traspo']);
		if ( $this->tesdoc['print_total']>0){
          foreach ($this->docVars->cast as $key => $value) {
            $this->Cell(62);
            $this->Cell(18, 4, gaz_format_number($value['impcast']).' ', 0, 0, 'R');
            $this->Cell(32, 4, $value['descriz'],0,0,'C');
            $this->Cell(18, 4, gaz_format_number($value['ivacast']).' ',0,1,'R');
          }
		}
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
        $this->Cell(26, 6,'Trasporto',1,0,'C',1);
        $this->Cell(36, 6,'Tot.Imponibile',1,0,'C',1);
        $this->Cell(26, 6,'Tot. I.V.A.',1,0,'C',1);
        $this->Cell(22, 6,'Peso in kg',1,1,'C',1);
		if ( $this->tesdoc['print_total']>0){
			$this->Cell(36, 6, gaz_format_number($totimpmer),1,0,'C');
			$this->Cell(16, 6, gaz_format_number($this->tesdoc['sconto']),1,0,'C');
			$this->Cell(24, 6, gaz_format_number($speseincasso),1,0,'C');
			$this->Cell(26, 6, gaz_format_number($this->tesdoc['traspo']),1,0,'C');
			$this->Cell(36, 6, gaz_format_number($totimpfat),1,0,'C');
			$this->Cell(26, 6, gaz_format_number($totivafat),1,0,'C');
			$this->Cell(22, 6, '',1,0,'C');
		} else {
			$this->Cell(186, 6, '',1);
		}

        $this->SetY(218);
        $this->Cell(130);
        $this->SetFont('helvetica','B',18);
		if ( $this->tesdoc['print_total']>0){
			$this->Cell(56, 24, '€ '.gaz_format_number($totimpfat + $totivafat + $impbol+$taxstamp), 1, 1, 'C');
        } else {
			$this->Cell(56, 24, '',1);
		}
        $this->SetY(224);
        $this->SetFont('helvetica','',9);
        $this->Cell(62, 6,'Spedizione',1,1,'C',1);
        $this->Cell(62, 6,$this->tesdoc['spediz'],1,1,'C');
        $this->Cell(62, 6,'Vettore',1,1,'C',1);
        $this->Cell(186,6,(isset($vettor['descri'])?$vettor['descri']:''),1,1,'L');
        // $this->Cell(186,6,'Il presente preventivo ha una validità di 2 giorni lavorativi, trascorso questo termine, i prezzi e le condizioni di vendita potrebbero',0,1,'L');
        $this->Cell(186,6,'Il presente preventivo ha una validità di '.$this->tesdoc['day_of_validity'].' giorni lavorativi, trascorso questo termine, i prezzi e le condizioni di vendita potrebbero',0,1,'L');
        $this->Cell(186,6,'subire delle modifiche che dipendono dalle situazioni di mercato.','B',1,'L');

		if (isset($this->docVars->ExternalDoc)){ // se ho dei documenti esterni allegati
			$this->print_header = false;
			$this->extdoc_acc=$this->docVars->ExternalDoc;
            reset($this->extdoc_acc);
			foreach ($this->extdoc_acc AS $key => $rigo) {
                $this->SetTextColor(255, 50, 50);
                $this->SetFont('helvetica', '', 6);
                if ($rigo['ext'] == 'pdf') {
                    $this->numPages = $this->setSourceFile( DATA_DIR . 'files/' . $rigo['file'] );
                    if ($this->numPages >= 1) {
                        for ($i = 1; $i <= $this->numPages; $i++) {
                            $this->_tplIdx = $this->importPage($i);
                            $specs = $this->getTemplateSize($this->_tplIdx);
							// stabilisco se portrait-landscape
							if ($specs['height'] > $specs['width']){ //portrait
								$pl='P';
								$w=210;
								$h=297;
							}else{ //landscape
								$pl='L';
								$w=297;
								$h=210;
							}
                            $this->AddPage($pl);
							$this->print_footer = false;
                            $this->useTemplate($this->_tplIdx,NULL,NULL,$w,$h, FALSE);
                            $this->SetXY(10, 0);
                            $this->Cell(190, 3,$this->intesta1 . ' ' . $this->intesta1bis." - documento allegato a: " . $this->tipdoc , 1, 0, 'C', 0, '', 1);
                        }
                    }
                } elseif (!empty($rigo['ext'])) {
                    list($w, $h) = getimagesize( DATA_DIR . 'files/' . $rigo['file'] );
					$this->SetAutoPageBreak(false, 0);
                    if ($w > $h) { //landscape
                        $this->AddPage('L');
						$this->print_footer = false;
                        $this->SetXY(10, 0);
                        $this->Cell(280, 3, $this->intesta1 . ' ' . $this->intesta1bis." - documento allegato a: " . $this->tipdoc, 1, 0, 'C', 0, '', 1);
						$this->image( DATA_DIR . 'files/' . $rigo['file'], 5, 3, 290 );
                    } else { // portrait
                        $this->AddPage('P');
						$this->print_footer = false;
                        $this->SetXY(10, 0);
                        $this->Cell(190, 3, $this->intesta1 . ' ' . $this->intesta1bis." - documento allegato a: " . $this->tipdoc, 1, 0, 'C', 0, '', 1);
						$this->image( DATA_DIR . 'files/' . $rigo['file'], 5, 3, 190 );
                    }
                }
            }
		}
    }

    function Footer()
    {
        //Page footer
    }
}
?>
