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

class OrdineAcquistoProduzioni extends Template
{
  public $tesdoc;
  public $tipdoc;
  public $giorno;
  public $mese;
  public $anno;
  public $nomemese;
  public $sconto;
  public $trasporto;
  public $consegna;
  public $tot_rp;
  public $id_rig;
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
      $this->tipdoc = 'Ordine a fornitore n.'.$this->tesdoc['numdoc'].'/'.$this->tesdoc['seziva'].' del '.$this->giorno.' '.$this->nomemese.' '.$this->anno;
    }
    function newPage() {
        $this->AddPage();
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->Ln(4);
        $this->SetFont('helvetica','',12);
        $this->Cell(186,8,'Promemoria ordine d\'acquisto per reparto produzioni:',0,1);
        $this->SetFont('helvetica','',8);
	    $this->Cell(125,6,'Descrizione',1,0,'L',1); //M1 modifocato a mano
        $this->Cell(7, 6,'U.m.',1,0,'C',1);
        $this->Cell(14,6,'Quantità',1,0,'R',1); // M1 Modificato a mano
        $this->Cell(17,6,'',1,0,'R',1);// M1 Modificato a mano
        $this->Cell(8, 6,'',1,0,'C',1);
        $this->Cell(15,6,'',1,1,'R',1); //M1 Modificato a mano
    }

    function pageHeader()
    {
        $this->setTesDoc();
        $this->StartPageGroup();
        $this->newPage();
    }
    function body()
    {
		$this->tot_rp=0;
        $lines = $this->docVars->getRigo();
		$ctrl_orderman=0;
		foreach ($lines AS $key => $rigo) {
            if ($this->GetY() >= 185) {
                $this->Cell(186,6,'','T',1);
                $this->SetFont('helvetica', '', 20);
                $this->SetY(225);
                $this->Cell(186,12,'>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>> ',1,1,'R');
                $this->SetFont('helvetica', '', 8);
                $this->newPage();
                $this->Cell(186,5,'<<< --- SEGUE DA PAGINA PRECEDENTE --- <<< ',0,1);
            }
			if ($ctrl_orderman!=$rigo['id_orderman'] && $rigo['id_orderman']>0) {
				/* stampo il rigo riferito ad una produzione   */
				$this->SetFont('helvetica', 'B', 9);
				$this->Ln(1);
				$this->Cell(186, 6, 'Materiale per Produzione n. ' . $rigo['id_orderman'] . ' - ' .  $rigo['orderman_descri'], 1, 1, 'L');
				$this->SetFont('helvetica', '', 8);
			}
			switch($rigo['tiprig']) {
                case "0":
					$ctrldim=0;
					$rp=0.000;
					$dim='';
					$pcs='';
					$res_ps='';
					$rigo['quality']=(!empty(trim($rigo['quality'])))? ' Qualità: '.$rigo['quality']:'';
					$rigo['codart']=(!empty(trim($rigo['codart'])))? ' Cod:'.$rigo['codart']:'';
					$rigo['codice_fornitore']=(!empty(trim($rigo['codice_fornitore'])))? ' Vs.Cod:'.$rigo['codice_fornitore']:'';
                    if ($rigo['pezzi'] > 0 ) {
						$res_ps='kg/pz';
						if ($rigo['lunghezza'] >= 0.001) {
							$rp=$rigo['lunghezza']*$rigo['pezzi']/10**3;
							$res_ps='kg/m';
							$dim .= floatval($rigo['lunghezza']);
							$ctrldim+=$rigo['lunghezza'];
							if ($rigo['larghezza'] >= 0.001) {
								$rp=$rigo['larghezza']*$rp/10**3;
								$res_ps='kg/m²';
								$dim .= 'x'.floatval($rigo['larghezza']);
								$ctrldim+=$rigo['larghezza'];
								if ($rigo['spessore'] >= 0.001) {
									$rp=$rigo['spessore']*$rp;
									$res_ps='kg/l';
									$dim .= 'x'.floatval($rigo['spessore']);
									$ctrldim+=$rigo['spessore'];
								}
							}
						}
						$dim.='mm';
						$pcs='n.'.$rigo['pezzi'].' pezzi';
						if ($rigo['peso_specifico'] >= 0.001) {
							$res_ps = ' - '.floatval($rigo['peso_specifico']).' '.$res_ps.' peso teor. '.floatval($rp*$rigo['peso_specifico']).' kg';
							$this->tot_rp +=$rp*$rigo['peso_specifico'];
						}
                    } else {
						// non ho i pezzi ma ho il peso specifico per calcolare il peso ma ho l'unità di misura in KG  allora aggiungo al peso totale
						if (strtoupper(substr(trim($rigo['unimis']),0,2))=='KG' ){
							$this->tot_rp +=$rigo['quanti'];
						}
					}
					if ($ctrldim>0.0001){
						$this->Cell(105, 6, $rigo['descri'].' Dimensioni:'.$dim,'LTR',0,'L',0,'',1);
					}else{
						$this->Cell(105, 6, $rigo['descri'],'LTR',0,'L',0,'',1);
					}
					$this->Cell(20, 6, $pcs,'RTB',1,'L',0,'',1);
					$this->Cell(125, 6, $rigo['codart'].$rigo['codice_fornitore'].$rigo['quality'].$res_ps ,'LRB',0,'L',0,'',1);
                    $this->Cell(7,  6, $rigo['unimis'],1,0,'C');
                    $this->Cell(14, 6, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R',0,'',1);
                    $this->Cell(40, 6, '',1,1);
                    break;
                case "1":
                    $this->Cell(125, 6, $rigo['descri'],'LBR',0,'L');
                    //$this->Cell(20, 6, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(61, 6, '',1,1,'R');
                    break;
                case "2":
                    $this->Cell(125,6,$rigo['descri'],'LR',0,'L'); // Modificato a mano
                    $this->Cell(61,6,'','R',1);
                    break;
                case "3":
                    $this->Cell(25,6,'',1,0,'L');
                    $this->Cell(80,6,$rigo['descri'],'B',0,'L',0,'',1);
                    $this->Cell(49,6,'','B',0,'L');
                    //$this->Cell(20,6,gaz_format_number($rigo['prelis']),1,0,'R');
                    $this->Cell(20,6,'',1); // non stampo mai il prezzo
                    $this->Cell(12,6,'',1,1,'R');
                    break;
                case "50":
                    $this->Cell(25, 6,$this->docVars->ExternalDoc[$rigo['id_rig']]['oriname'].'.'.$this->docVars->ExternalDoc[$rigo['id_rig']]['ext'],1,0,'L',0,'',1);
                    $this->Cell(100, 6, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(7,  6, $rigo['unimis'],1,0,'C');
                    $this->Cell(14, 6, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R',0,'',1);
                    $this->Cell(40, 6, '',1,1);
                    $this->tot_rp +=$rigo['quanti'];
                    break;
                case "51":
                    $this->Cell(25, 6,$this->docVars->ExternalDoc[$rigo['id_rig']]['oriname'].'.'.$this->docVars->ExternalDoc[$rigo['id_rig']]['ext'],1,0,'L',0,'',1);
                    $this->Cell(100,6,$rigo['descri'],'LR',0,'L',0,'',1);
                    $this->Cell(61,6,'','R',1);
                    break;
                }
				$ctrl_orderman=$rigo['id_orderman'];
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
        if ($this->tot_rp > 0) {
            $this->Cell(29, 6,gaz_format_number($this->tot_rp),'LRB',0,'C');
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
        $this->SetY(-20);
        $this->SetFont('helvetica', '', 8);
        $this->MultiCell(186, 4, $this->intesta1.' '.$this->intesta2.' '.$this->intesta3.' '.$this->intesta4.' ', 0, 'C', 0);
    }
}

?>
