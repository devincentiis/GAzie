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

class OrdineFornitore extends Template
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
      $this->nomemese = ucwords($this->docVars->gazTimeFormatter->format(new DateTime("2000-".substr($this->tesdoc['datemi'],5,2)."-01")));
      $this->sconto = $this->tesdoc['sconto'];
      $this->trasporto = $this->tesdoc['traspo'];
      $this->tipdoc = 'Ordine a fornitore n.'.$this->tesdoc['numdoc'].'/'.$this->tesdoc['seziva'].' del '.$this->giorno.' '.$this->nomemese.' '.$this->anno;
		if ($this->tesdoc['initra']>0) {
			$this->giorno = substr($this->tesdoc['initra'],8,2);
			$this->mese = substr($this->tesdoc['initra'],5,2);
			$this->anno = substr($this->tesdoc['initra'],0,4);
			$this->nomemese = ucwords($this->docVars->gazTimeFormatter->format(new DateTime("2000-".substr($this->tesdoc['initra'],5,2)."-01")));
			$this->consegna = 'Consegna richiesta per il giorno '.$this->giorno.' '.$this->nomemese.' '.$this->anno;
		} else {
			$this->consegna = '';
		}
    }
    function newPage() {
        $this->AddPage();
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->Ln(4);
        $this->SetFont('helvetica','',8);
	    $this->Cell(127,6,'Descrizione',1,0,'L',1); //M1 modifocato a mano
        $this->Cell(7, 6,'U.m.',1,0,'C',1);
        $this->Cell(14,6,'Quantità',1,0,'R',1); // M1 Modificato a mano
        $this->Cell(17,6,'Prezzo',1,0,'R',1);// M1 Modificato a mano
        $this->Cell(8, 6,'%Sc.',1,0,'C',1);
        $this->Cell(15,6,'Importo',1,1,'R',1); //M1 Modificato a mano
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
      if ($this->GetY() >= 205) {
        $this->Cell(186,6,'','T',1);
        $this->SetFont('helvetica', '', 20);
        $this->SetY(225);
        $this->Cell(186,12,'>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>> ',1,1,'R');
        $this->SetFont('helvetica', '', 8);
        $this->newPage();
        $this->Cell(186,5,'<<< --- SEGUE DA PAGINA PRECEDENTE --- <<< ',0,1);
      }
			if ($ctrl_orderman!=$rigo['id_orderman'] && $rigo['id_orderman']>0) {
				$this->SetFont('helvetica', 'B', 9);
				$this->Ln(1);
				$this->Cell(188, 6, 'Materiale per Produzione n. ' . $rigo['id_orderman'] . ' - ' .  $rigo['orderman_descri'], 1, 1, 'L');
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
						$rp=$rigo['pezzi'];
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
						$this->Cell(107, 6, $rigo['descri'].' Dimensioni:'.$dim,'LTR',0,'L',0,'',1);
					}else{
						$this->Cell(107, 6, $rigo['descri'],'LTR',0,'L',0,'',1);
					}
					$this->Cell(20, 6, $pcs,'RTB',0,'L',0,'',1);
					$this->Cell(46, 6, 'Consegna prevista il '.gaz_format_date($rigo['delivery_date']),'RTB',1,'L',0,'',1);
					$this->Cell(127, 6, $rigo['codart'].$rigo['codice_fornitore'].$rigo['quality'].$res_ps ,'LRB',0,'L',0,'',1);
          $this->Cell(7,  6, $rigo['unimis'],1,0,'C');
          $this->Cell(14, 6, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R'); // Modificato a mano
          if ($rigo['prelis'] > 0) {
             $this->Cell(17, 6, number_format($rigo['prelis'],$this->decimal_price,',',''),1,0,'R'); // Modificato a mano
          } else {
             $this->Cell(17, 6, '',1); // Modificato a mano
          }
          if ($rigo['sconto']> 0) {
             $this->Cell(8, 6,  number_format($rigo['sconto'],1,',',''),1,0,'C');
          } else {
             $this->Cell(8, 6, '',1);
          }
          if ($rigo['importo'] > 0) {
             $this->Cell(15, 6, gaz_format_number($rigo['importo']),1,1,'R'); // Modificato a mano
          } else {
             $this->Cell(15, 6, '',1,1); // Modificato a mano
          }
          //$this->Cell(12, 6, gaz_format_number($rigo['pervat']),1,1,'R');
          break;
        case "1":
          $this->Cell(127, 6, $rigo['descri'],'LBR',0,'L',0,'',1);
          $this->Cell(46,6,'','B',0,'L');
          $this->Cell(15, 6, gaz_format_number($rigo['importo']),1,1,'R',0,'',1);
          break;
        case "2":
          $this->Cell(127,6,$rigo['descri'],'LR',0,'L',0,'',1); // Modificato a mano
          $this->Cell(81,6,'','R',1);
          break;
        case "3":
          $this->Cell(27,6,'',1,0,'L');
          $this->Cell(80,6,$rigo['descri'],'B',0,'L');
          $this->Cell(49,6,'','B',0,'L');
          $this->Cell(20,6,gaz_format_number($rigo['prelis']),1,0,'R');
          $this->Cell(12,6,'',1,1,'R');
          break;
        case "6":
          $this->writeHtmlCell(188,6,10,$this->GetY(),$rigo['descri'],1,1);
          break;
        case "50": // normale c/allegato
          $this->Cell(27, 6, $this->docVars->ExternalDoc[$rigo['id_rig']]['oriname'].'.'.$this->docVars->ExternalDoc[$rigo['id_rig']]['ext'],1,0,'L',0,'',1);
          $this->Cell(100, 6, $rigo['descri'],1,0,'L',0,'',1);
          $this->Cell(7,  6, $rigo['unimis'],1,0,'C');
          $this->Cell(14, 6, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R',0,'',1);
          if ($rigo['prelis'] > 0) {
             $this->Cell(17, 6, number_format($rigo['prelis'],$this->decimal_price,',',''),1,0,'R');
          } else {
             $this->Cell(17, 6, '',1);
          }
          if ($rigo['sconto']> 0) {
             $this->Cell(8, 6,  number_format($rigo['sconto'],1,',',''),1,0,'C');
          } else {
             $this->Cell(8, 6, '',1);
          }
          if ($rigo['importo'] > 0) {
             $this->Cell(15, 6, gaz_format_number($rigo['importo']),1,0,'R',0,'',1);
          } else {
             $this->Cell(15, 6, '',1);
          }
          $this->Ln();
          $this->tot_rp +=$rigo['quanti'];
          break;
        case "51": // descrittivo c/allegato
          $this->Cell(27, 6, $this->docVars->ExternalDoc[$rigo['id_rig']]['oriname'].'.'.$this->docVars->ExternalDoc[$rigo['id_rig']]['ext'],1,0,'L',0,'',1);
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
        $this->Rect(10,$y,188,208-$y); //questa marca le linee dx e sx del documento
		if ($this->consegna <> '') {
			$this->SetFont('helvetica','B',10);
			$this->Cell(188,6,$this->consegna,'BT',1,'C',1);
		}
        $this->SetFont('helvetica','I',11);
        $this->Cell(186,8,'Ogni modifica ai dati soprariportati dev\'essere preventivamente autorizzata.','T',1);
        //stampo il castelletto
        $this->SetFont('helvetica', '', 8);
        $this->SetY(218);
        $this->Cell(63,6, 'Pagamento','LTR',0,'C',1);
        $this->Cell(68,6, 'Castelletto    I.V.A.','LTR',0,'C',1);
        $this->Cell(57,6, 'T O T A L E','LTR',1,'C',1);
        $this->Cell(63,6, $this->pagame['descri'],'LR',0,'C',0,'',1);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(18,4, 'Imponibile','LR',0,'C',1);
        $this->Cell(32,4, 'Aliquota','LR',0,'C',1);
        $this->Cell(18,4, 'Imposta','LR',1,'C',1);
        $this->docVars->setTotal($this->tesdoc['traspo']);
        foreach ($this->docVars->cast as $key => $value) {
                if ($this->tesdoc['id_tes'] > 0) {
                   $this->Cell(63);
                   $this->Cell(18, 4, gaz_format_number($value['impcast']).' ', 'R', 0, 'R');
                   $this->Cell(32, 4, $value['descriz'],0,0,'C',0,'',1);
                   $this->Cell(18, 4, gaz_format_number($value['ivacast']).' ','L',1,'R');
                } else {
                   $this->Cell(63);
                   $this->Cell(68, 4,'','LR',1);
                 }
        }

        $totimpmer = $this->docVars->totimpmer;
        $speseincasso = $this->docVars->speseincasso;
        $totimpfat = $this->docVars->totimpfat;
        $totivafat = $this->docVars->totivafat;
        $vettor = $this->docVars->vettor;
        $impbol = $this->docVars->impbol;
        $totriport = $this->docVars->totriport;
        if ($impbol > 0) {
            $this->Cell(62);
            $this->Cell(18, 4, gaz_format_number($impbol).' ', 0, 0, 'R');
            $this->Cell(32, 4, $this->docVars->iva_bollo['descri'], 'LR', 0, 'C',0,'',1);
            $this->Cell(18, 4,gaz_format_number($this->docVars->iva_bollo['aliquo']*$impbol).' ',0,1,'R');
        }
        //stampo i totali
        $this->SetY(208);
        $this->SetFont('helvetica','',9);
        $this->Cell(37, 5,'Tot. Corpo','LTR',0,'C',1);
        $this->Cell(16, 5,'% Sconto','LTR',0,'C',1);
        $this->Cell(24, 5,'Spese Incasso','LTR',0,'C',1);
        $this->Cell(26, 5,'Trasporto','LTR',0,'C',1);
        $this->Cell(37, 5,'Tot.Imponibile','LTR',0,'C',1);
        $this->Cell(26, 5,'Tot. I.V.A.','LTR',0,'C',1);
        $this->Cell(22, 5,'Bolli','LTR',1,'C',1);
        $this->Cell(37, 5, gaz_format_number($totimpmer),1,0,'C');
        $this->Cell(16, 5, gaz_format_number($this->tesdoc['sconto']),1,0,'C');
        $this->Cell(24, 5, gaz_format_number($speseincasso),1,0,'C');
        $this->Cell(26, 5, gaz_format_number($this->tesdoc['traspo']),1,0,'C');
        $this->Cell(37, 5, gaz_format_number($totimpfat),1,0,'C');
        $this->Cell(26, 5, gaz_format_number($totivafat),1,0,'C');
        $this->Cell(22, 5,'',1,0,'C');
        $this->SetY(224);
        $this->Cell(131);
        $totale = $totimpfat + $totivafat + $impbol;
        $this->SetFont('helvetica','B',16);
        if ($totale > 0) {
           $this->Cell(57, 18, '€ '.gaz_format_number($totale), 'LR', 1, 'C');
        } else {
           $this->Cell(57, 18,'','LR',1);
        }
        $this->SetY(230);
        $this->SetFont('helvetica','',9);
        $this->Cell(63, 6,'Spedizione','LTR',1,'C',1);
        $this->Cell(63, 6,$this->tesdoc['spediz'],'LBR',1,'C');
        $this->Cell(74, 6,'Porto','LTR',0,'C',1);
        $this->Cell(29, 6,'Peso netto','LTR',0,'C',1);
        $this->Cell(29, 6,'Peso lordo','LTR',0,'C',1);
        $this->Cell(28, 6,'N.colli','LTR',0,'C',1);
        $this->Cell(28, 6,'Volume','LTR',1,'C',1);
        $this->Cell(74, 6,$this->tesdoc['portos'],'LBR',0,'C');
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
              $this->print_footer = false;
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
        $this->MultiCell(184, 4, $this->intesta1.' '.$this->intesta2.' '.$this->intesta3.' '.$this->intesta4.' ', 0, 'C', 0);
    }
}

?>
