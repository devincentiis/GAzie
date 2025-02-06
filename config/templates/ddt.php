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
require('template_scheda.php');

class DDT extends Template_con_scheda
{
    public $tesdoc;
    public $giorno;
    public $mese;
    public $anno;
    public $sconto;
    public $trasporto;
    public $tot_rp;
    public $tipdoc;
    public $descriptive_last_ddt;
    public $show_artico_composit;
    public $sedelegale;
    public $numPages;
    public $_tplIdx;
    public $extdoc_acc;

    function setTesDoc()
    {
      $this->tesdoc = $this->docVars->tesdoc;
      $this->giorno = substr($this->tesdoc['datemi'],8,2);
      $this->mese = substr($this->tesdoc['datemi'],5,2);
      $this->anno = substr($this->tesdoc['datemi'],0,4);
      if ($this->tesdoc['datfat']){
        $datemi =  new DateTime($this->tesdoc['datemi']);
        $this->docVars->gazTimeFormatter->setPattern('MMMM');
        $nomemese = ucwords($this->docVars->gazTimeFormatter->format($datemi));
      } else {
        $nomemese = '';
      }
      $this->sconto = $this->tesdoc['sconto'];
      $this->trasporto = $this->tesdoc['traspo'];
      if ($this->tesdoc['tipdoc'] == 'DDR') {
          $descri='Reso';
      } elseif ($this->tesdoc['tipdoc'] == 'DDL') {
          $descri='Conto lavorazione';
      } elseif ($this->tesdoc['ddt_type'] == 'V') {
          $descri='Cessione in c/visione';
      } elseif ($this->tesdoc['ddt_type'] == 'Y') {
          $descri='Cessione per triangolazione';
      } elseif ($this->tesdoc['ddt_type'] == 'S') {
          $descri='Notula di Servizio';
      } elseif ($this->tesdoc['ddt_type'] == 'X') {
          $descri='Reso non lavorato';
      } elseif ($this->tesdoc['ddt_type'] == 'Z') {
          $descri='Reso da rottamare';
      } elseif ($this->tesdoc['ddt_type'] == 'W') {
          $descri='Reso non conforme';
      } elseif ($this->tesdoc['ddt_type'] == 'D') {
          $descri='Conto deposito';
      } elseif ($this->tesdoc['ddt_type'] == 'J') {
          $descri='Reso non utilizzabile';
      } elseif ($this->tesdoc['ddt_type'] == 'C') {
          $descri='Completamento';
      } elseif ($this->tesdoc['ddt_type'] == 'M') {
          $descri='Montaggio';
      } elseif ($this->tesdoc['ddt_type'] == 'L') {
          $descri='Lavorazione';
      } elseif ($this->tesdoc['ddt_type'] == 'O') {
          $descri='Reso da conto lavoro';
      } elseif (substr($this->tesdoc['clfoco'],0,1) == '2') { // DdT ricevuto da fornitore
          $descri='Ricevuto da fornitore';
      } else {
          $descri='Vendita';
      }
      if ($this->tesdoc['numdoc']>0){
        $numdoc = $this->tesdoc['numdoc'].'/'.$this->tesdoc['seziva'];
      } else {
        $numdoc = ' _ _ _ _ _ _ _';
      }
      $this->tipdoc = 'D.d.T. n.'.$numdoc.' del '.$this->giorno.' '.$nomemese.' '.$this->anno.' | Causale: '.$descri;
      $this->descriptive_last_ddt = $this->docVars->descriptive_last_ddt;
      $this->show_artico_composit = $this->docVars->show_artico_composit;
    }

    function newPage() {
        $this->AddPage();
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->SetFont('helvetica','',9);
        $this->Cell(35,6,'Codice',1,0,'L',1);
        $this->Cell(82,6,'Descrizione',1,0,'L',1);
        $this->Cell(10,6,'U.m.',1,0,'L',1);
        //$tipodoc = substr($this->tesdoc["tipdoc"], 0, 1);
        $this->Cell(25,6,'Quantità',1,0,'R',1);
        $this->Cell(25,6,'Prezzo',1,0,'R',1);
        $this->Cell(10,6,'%Sc',1,1,'R',1);
    }

    function pageHeader()
    {
        $this->StartPageGroup();
        $this->newPage();
    }

    function compose()
    {
		// define barcode style
		$style = array(
		'position' => '',
		'align' => 'C',
		'stretch' => false,
		'fitwidth' => true,
		'cellfitalign' => '',
		'border' => false,
		'hpadding' => '2',
		'vpadding' => '',
		'fgcolor' => array(0,0,0),
		'bgcolor' => false, //array(255,255,255),
		'text' => true,
		'font' => 'helvetica',
		'fontsize' => 6,
		'stretchtext' => 4
		);
    $lines = $this->docVars->getRigo();
    $prevTiprig=false;
    foreach ($lines AS $key => $rigo) {
            if ($rigo['sconto'] < 0.001) {
                $rigo['sconto']='';
            }
            if ( $this->descriptive_last_ddt!="" ){
				$maxy=200;
			} else {
				$maxy=215;
			}
            if ($this->GetY() >= $maxy) {
                $this->Cell(155,6,'','T',1);
                $this->SetFont('helvetica', '', 20);
                $this->SetY(225);
                $this->Cell(185,12,'>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>> ',1,1,'R');
                $this->SetFont('helvetica', '', 9);
                $this->newPage();
                $this->Cell(185,5,'<<< --- SEGUE DA PAGINA PRECEDENTE --- <<< ',0,1);
            }
                if (isset ($rigo['identifier']) && strlen ($rigo['identifier'])>0){
                  if (intval ($rigo['expiry'])>0){
                    $rigo['descri']=$rigo['descri']." - lot: ".$rigo['identifier']." ".gaz_format_date($rigo['expiry']);
                  } else {
                    $rigo['descri']=$rigo['descri']." - lot: ".$rigo['identifier'];
                  }
                }
                if ($rigo['tiprig'] < 2) {
                  $h=6;
                  if (intval($rigo['barcode'])>0){
                    $h=16;
                    $x = $this->GetX();
                    $y = $this->GetY();
                    $this->Cell(35,$h,$rigo['codart'],1,1,'L', 0, '', 1,false, '', 'T');
                    $this->write1DBarcode($rigo['barcode'], 'EAN13', '', $y+4, '', 11, 0.33, $style, 'M');
                    $this->SetXY($x+35,$y);
                  } else {
                    $this->Cell(35,$h,$rigo['codart'],1,0,'L', 0, '', 1);
                  }
                  $this->Cell(82,$h,$rigo['descri'],1,0,'L',0,'',1);
                  $tipodoc = substr($this->tesdoc["tipdoc"], 0, 1);
                  $this->Cell(10,$h,$rigo['unimis'],1,0,'L');
                  $this->Cell(25,$h,gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R');
                  if (($this->docVars->client['stapre'] == 'S' || $this->docVars->client['stapre'] == 'T' ) && floatval($rigo['prelis']) >= 0.00001 ) {
                      $this->Cell(25,$h,number_format($rigo['prelis'],$this->decimal_price,',',''),'TB',0,'R');
                      $this->Cell(10,$h,floatval($rigo['sconto']),1,1,'R', 0, '', 1);
                  } else {
                      $this->Cell(25,$h);
                      $this->Cell(10,$h,'','R',1);
                  }
                } elseif ($rigo['tiprig'] == 2) {
                   $this->Cell(127,6,$rigo['descri'],'LR',0,'L', 0, '', 1);
                   $this->Cell(60,6,'','R',1);
                } elseif ($rigo['tiprig']==6 || $rigo['tiprig']==7) {
                    $this->writeHtmlCell(187,6,10,$this->GetY(),$rigo['descri'],1,1);
                } elseif ($rigo['tiprig'] == 11) {
                    $this->Cell(35,6,'','L');
                    $this->Cell(117, 6, "Codice Identificativo Gara (CIG): " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(35,6,'','R',1);
                } elseif ($rigo['tiprig'] == 12) {
                    $this->Cell(35,6,'','L');
                    $this->Cell(117, 6, "Codice Unitario Progetto (CUP): " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(35,6,'','R',1);
                } elseif ($rigo['tiprig'] == 13) {
                    $this->Cell(35,6,'','L');
                    $this->Cell(117, 6, "Identificativo documento: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(35,6,'','R',1);
                } elseif ( $rigo['tiprig'] == 14 ) {
                    $this->Cell(35, 6, "",'L');
                    $this->Cell(117, 6, "Data documento: " . gaz_format_date($rigo['descri']), 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(35,6,'','R',1);
                } elseif ($rigo['tiprig'] == 15) {
                    $this->Cell(35,6,'','L');
                    $this->Cell(117, 6, "Num.Linea documento: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(35,6,'','R',1);
                } elseif ($rigo['tiprig'] == 16) {
                    $this->Cell(35,6,'','L');
                    $this->Cell(117, 6, "Codice Commessa/Convenzione: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(35,6,'','R',1);
                } elseif ($rigo['tiprig'] == 17) {
                    $this->Cell(35,6,'','L');
                    $this->Cell(117, 6, "Riferimento Amministrazione: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(35,6,'','R',1);
                } elseif ($rigo['tiprig'] == 21) {
                  $descri21=$prevTiprig=='21'?'':'Causale:';
                  $this->Cell(20, 5, $descri21, 'L',0,'R');
                  $this->Cell(167, 5, $rigo['descri'], 'R', 1, 'L', 0, '', 1);
                } elseif ( $rigo['tiprig'] == 25 ) {
                  $this->Cell(35,6,'','L');
                  $this->Cell(117, 6, "Stato avanzamento lavori, fase: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                  $this->Cell(35,6,'','R',1);
                } elseif ( $rigo['tiprig'] == 31 ) {
                  $this->Cell(35, 6, "",'L');
                  $this->Cell(117, 6, "Dati Veicoli ex art.38, immatricolato il " . gaz_format_date($rigo['descri']).', km o ore:'.intval($rigo['quanti']), 'LR', 0, 'L', 0, '', 1);
                  $this->Cell(35,6,'','R',1);
                } elseif ( $rigo['tiprig'] == 50 ) {
                  $this->Cell(35, 6, $this->docVars->ExternalDoc[$rigo['id_rig']]['oriname'].'.'.$this->docVars->ExternalDoc[$rigo['id_rig']]['ext'],1,0,'L',0,'',1);
                  $this->Cell(82, 6, $rigo['descri'],1,0,'L',0,'',1);
                  $this->Cell(10,  6, $rigo['unimis'],1,0,'C');
                  $this->Cell(25, 6, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R',0,'',1);
                  if (($this->docVars->client['stapre'] == 'S' || $this->docVars->client['stapre'] == 'T' ) && $rigo['prelis'] > 0) {
                     $this->Cell(25, 6, number_format($rigo['prelis'],$this->decimal_price,',',''),1,0,'R');
                  } else {
                     $this->Cell(25, 6, '',1);
                  }
                  if (($this->docVars->client['stapre'] == 'S' || $this->docVars->client['stapre'] == 'T' ) && $rigo['sconto']> 0) {
                     $this->Cell(10, 6,  number_format($rigo['sconto'],1,',',''),1,0,'C');
                  } else {
                     $this->Cell(10, 6, '',1);
                  }
                  $this->tot_rp +=$rigo['quanti'];
                } elseif ( $rigo['tiprig'] == 51 ) {
                  $this->Cell(35, 5, $this->docVars->ExternalDoc[$rigo['id_rig']]['oriname'].'.'.$this->docVars->ExternalDoc[$rigo['id_rig']]['ext'],1,0,'L',0,'',1);
                  $this->Cell(117,5,$rigo['descri'],'LR',0,'L',0,'',1);
                  $this->Cell(35,5,'','R',1);
                } elseif ($rigo['tiprig'] == 210 ) {
                  if ( $this->show_artico_composit=="1" ) {
                    $oldy = $this->GetY();
                    $this->SetFont('helvetica', '', 8);
                    $this->SetY($this->GetY()-6);
                    $this->Cell(110, 8, '('.$rigo['unimis'].' '.gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity).')',0,0,'R');
                    $this->SetY( $oldy );
                    $this->SetFont('helvetica', '', 9);
                  }
                } elseif ($rigo['tiprig'] == 90) {
                    $this->Cell(152, 6, 'VENDITA CESPITE: ' . $rigo['codart'], 1, 0, 'L');
                    $this->Cell(25, 6, '', 1);
                    $this->Cell(10, 6, '', 1, 1);
                    $this->Cell(152, 6, $rigo['descri'],1,0,'L',0,'',1);
                    if ($this->docVars->client['stapre'] == 'S' OR $this->docVars->client['stapre'] == 'T') {
                        $this->Cell(25,6,number_format($rigo['importo'],$this->decimal_price,',',''),'TB',0,'R');
                        $this->Cell(10,6,$rigo['sconto'],1,1,'R');
                    } else {
                        $this->Cell(25,6);
                        $this->Cell(10,6,'','R',1);
                    }
                }
        $prevTiprig=$rigo['tiprig'];
      }
    }

    function pageFooter() {
        $y = $this->GetY();
        if ( $this->descriptive_last_ddt!="" ) {
            //$mess_ddt = explode("|",$this->descriptive_last_ddt);
            $this->Rect(10,$y,187,215-$y); //questa marca le linee dx e sx del documento
            $this->SetY(215);
            // visualizzo un messaggio sul fondo dei righi del ddt
            $this->SetFont('helvetica','',8);
            $this->MultiCell(187, 4, $this->descriptive_last_ddt, 'LR', 'L', 0, 1, '', '', true);
        } else {
            $this->Rect(10,$y,187,220-$y); //questa marca le linee dx e sx del documento
            $this->SetY(220);
        }
        $this->SetFont('helvetica','',9);
        $this->Cell(83, 5,'Agente','LTR',0,'C',1);
        $this->Cell(26, 5,'Peso netto','LTR',0,'C',1);
        $this->Cell(26, 5,'Peso lordo','LTR',0,'C',1);
        $this->Cell(26, 5,'N.colli','LTR',0,'C',1);
        $this->Cell(26, 5,'Volume','LTR',1,'C',1);
        $this->Cell(83, 5,$this->agente,'LR');
        if ($this->tesdoc['net_weight'] > 0) {
            $this->Cell(26, 5,gaz_format_number($this->tesdoc['net_weight']),'LR',0,'C');
        } else {
            $this->Cell(26, 5,'','LR');
        }
        if ($this->tesdoc['gross_weight'] > 0) {
            $this->Cell(26, 5,gaz_format_number($this->tesdoc['gross_weight']),'LR',0,'C');
        } else {
            $this->Cell(26, 5,'','LR');
        }
        if ($this->tesdoc['units'] > 0) {
            $this->Cell(26, 5,$this->tesdoc['units'],'LR',0,'C');
        } else {
            $this->Cell(26, 5,'','LR');
        }
        if ($this->tesdoc['volume'] > 0) {
            $this->Cell(26, 5,gaz_format_number($this->tesdoc['volume']),'LR',1,'C');
        } else {
            $this->Cell(26, 5,'','LR',1);
        }
		//Antonio Germani - Se richiesto nella scheda cliente stampo il totale iva compresa
        if ($this->docVars->client['stapre'] == 'T') {
            $this->Cell(109,5,'Pagamento - Banca','LTR',0,'C',1);
            $this->Cell(78,5,'TOTALE A PAGARE (segue fattura)','LTR',1,'C',1);
            $this->Cell(109,6,$this->pagame['descri'].' '.$this->banapp['descri'],'LBR',0,'C',0,'',1);

            // calcolo il totale che il cliente dovrà pagare per questo documento
            // utile per esempio su consegna merce con pagamento alla consegna o comunque per ricevere il pagamento anticipatamente
            $this->docVars->setTotal();
            $this->tottraspo = $this->docVars->tottraspo;
            $totimpmer = $this->docVars->totimpmer;
            $speseincasso = $this->docVars->speseincasso;
            $totimpfat = $this->docVars->totimpfat;
            $totivafat = $this->docVars->totivafat;
            $totivasplitpay = $this->docVars->totivasplitpay;
            $vettor = $this->docVars->vettor;
            $impbol = $this->docVars->impbol;
            $totriport = $this->docVars->totriport;
            $ritenuta = $this->docVars->tot_ritenute;
            $taxstamp = $this->docVars->taxstamp;
            $totale = $totimpfat + $totivafat + $impbol + $taxstamp;

            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(78,6, "€ ". gaz_format_number($totale),'LBR',1,'C',0,'',1);
            $this->SetFont('helvetica','',9);
        } else {

            $this->Cell(187,5,'Pagamento - Banca','LTR',1,'C',1);
            $this->Cell(187,5,$this->pagame['descri'].' '.$this->banapp['descri'],'LBR',1,'C',0,'',1);
        }
        $this->Cell(51,5,'Spedizione','LTR',0,'C',1);
		if (!empty($this->docVars->vettor['ragione_sociale'])) {
			$this->Cell(114,5,'Vettore','LTR',0,'C',1);
		} else {
			$this->Cell(57,5,'Imballo','LTR',0,'C',1);
			$this->Cell(57,5,'Porto','LTR',0,'C',1);
		}
        $this->Cell(22,5,'Trasporto','LTR',1,'C',1);
        $this->Cell(51,5,$this->tesdoc['spediz'],'LBR',0,'C');
		if (!empty($this->docVars->vettor['ragione_sociale'])) {
			$this->Cell(114,5,$this->docVars->vettor['ragione_sociale'].' '.
							  $this->docVars->vettor['indirizzo'].' '.
							  $this->docVars->vettor['citta'].' '.
							  $this->docVars->vettor['provincia'],'LBR',0,'C',0,'',1);
        } else {
			$this->Cell(57,5,$this->tesdoc['imball'],'LBR',0,'C');
			$this->Cell(57,5,$this->tesdoc['portos'],'LBR',0,'C',0,'',1);
        }
        if ($this->docVars->tesdoc['traspo'] == 0) {
            $ImportoTrasporto = "";
        } else {
            $ImportoTrasporto = gaz_format_number($this->docVars->tesdoc['traspo']);
        }
        $this->Cell(22,5,$ImportoTrasporto,'LBR',1,'C');
        $this->Cell(51,5,'Inizio trasporto','LTR',0,'C',1);
        if (empty($this->docVars->vettor['ragione_sociale'])) {
            $signature = ' Firma del conducente ';
        } else {
            $signature = ' Firma del vettore ';
        }
        $this->Cell(68,5,$signature,'LTR',0,'C',1);
        $this->Cell(68,5,'Firma destinatario','LTR',1,'C',1);
        if ($this->day > 0) {
           $this->Cell(51,5,'data '.$this->day.'-'.$this->month.'-'.$this->year,'LR',0,'C');
        } else {
           $this->Cell(51,5,'      data','LR',0,'L');
        }
        $this->Cell(68,5,'','R',0);
        $this->Cell(68,5,'','R',1);
        $this->Cell(51,5,'ora '.$this->ora.':'.$this->min,'LRB',0,'C');
        $this->Cell(68,5,'','RB',0);
        $this->Cell(68,5,'','RB',1);
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
        if(isset($this->appendix)){
          if ($this->appendix==false){
              // sull'appendice non stampo il footer
              unset($this->appendix);
          } else {
           $this->SetY(-20);
           $this->SetFont('helvetica','',8);
            if ( $this->sedelegale!="" ) {
                $this->MultiCell(184, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4 . ' ' . "SEDE LEGALE: ".$this->sedelegale, 0, 'C', 0);
            } else {
                $this->MultiCell(184, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4, 0, 'C', 0);
            }
          }
        } else {
           $this->SetY(-20);
           $this->SetFont('helvetica','',8);
            if ( $this->sedelegale!="" ) {
                $this->MultiCell(184, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4 . ' ' . "SEDE LEGALE: ".$this->sedelegale, 0, 'C', 0);
            } else {
                $this->MultiCell(184, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4, 0, 'C', 0);
            }
        }
    }
}
?>
