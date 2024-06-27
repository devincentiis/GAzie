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


/*
     !!!!!!!!!!!!!!!  ATTENZIONE !!!!!!!!!!!!!!!!!!!!!
QUESTO  TEMPLATE NON E' ATTIVO DI DEFAULT MA BASTA RINOMINARLO
in "ddt.php" SOVRASCRIVENDO L'ORIGINALE PER AVER DUE DOCUMENTI
DI TRASPORTO AFFIANCATI SU UN SOLO FOGLIO DI CARTA A4
*/


require('template_2xA5.php');

class DDT extends Template_2xA5
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
      if ($this->tesdoc['tipdoc'] == 'DDR') {
          $descri='D.d.T. per Reso n.';
      } elseif ($this->tesdoc['tipdoc'] == 'DDL') {
          $descri='D.d.T. c/lavorazione n.';
      } elseif ($this->tesdoc['ddt_type'] == 'V') {
          $descri='D.d.T. cessione in c/visione n.';
      } elseif ($this->tesdoc['ddt_type'] == 'Y') {
          $descri='D.d.T. cessione per triangolazione n.';
      } else {
          $descri='Documento di Trasporto n.';
      }
		if ($this->tesdoc['numdoc']>0){
			$numdoc = $this->tesdoc['numdoc'].'/'.$this->tesdoc['seziva'];
		} else {
			$numdoc = ' _ _ _ _ _ _ _';
		}
        $this->tipdoc = $descri.$numdoc.' del '.$this->giorno.' '.$nomemese.' '.$this->anno;
        $this->totddt = 0.00;
    }

    function newPage() {
        $this->AddPage('L','A4');
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->SetFont('helvetica','',7);
        $this->Cell(20,6,'Codice',1,0,'L',1);
        $this->Cell(64,6,'Descrizione',1,0,'L',1);
        $this->Cell(7,6,'U.m.',1,0,'L',1);
        $this->Cell(20,6,'Quantità',1,0,'R',1);
        $this->Cell(17,6,'Prezzo',1,0,'R',1);
        $this->Cell(7,6,'%Sc.',1,0,'R',1);
        $this->Cell(14);
        $this->Cell(20,6,'Codice',1,0,'L',1);
        $this->Cell(64,6,'Descrizione',1,0,'L',1);
        $this->Cell(7,6,'U.m.',1,0,'L',1);
        $this->Cell(20,6,'Quantità',1,0,'R',1);
        $this->Cell(17,6,'Prezzo',1,0,'R',1);
        $this->Cell(7,6,'%Sc.',1,1,'R',1);
    }

    function pageHeader()
    {
        $this->StartPageGroup();
        $this->newPage();
    }

    function compose()
    {
        $lines = $this->docVars->getRigo();
        foreach($lines as $key=>$rigo) {
            // calcolo importo totale (iva inclusa) del rigo e creazione castelletto IVA
            if ($rigo['tiprig'] <= 1) {    //ma solo se del tipo normale o forfait
                if ($rigo['tiprig'] == 0) { // tipo normale
                    $tot_row = CalcolaImportoRigo($rigo['quanti'], $rigo['prelis'], array($rigo['sconto'], $this->sconto, -$rigo['pervat']));
                } else {                 // tipo forfait
                    $tot_row = CalcolaImportoRigo(1, $rigo['prelis'], -$rigo['pervat']);
                }
                // calcolo il totale del rigo stornato dell'iva
                $imprig = round($tot_row / (1 + $rigo['pervat'] / 100), 2);
                $this->totddt += $tot_row;
            }
            // fine calcolo importo rigo, totale e castelletto IVA
            if ($this->GetY() >= 215) {
                $this->Cell(155,6,'','T',1);
                $this->SetFont('helvetica', '', 20);
                $this->SetY(225);
                $this->Cell(185,12,'>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>> ',1,1,'R');
                $this->SetFont('helvetica', '', 9);
                $this->newPage();
                $this->Cell(185,5,'<<< --- SEGUE DA PAGINA PRECEDENTE --- <<< ',0,1);
            }
                if ($rigo['tiprig'] < 2) {
                    $this->Cell(20,6,$rigo['codart'],1,0,'L');
                    $this->Cell(64,6,$rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(7,6,$rigo['unimis'],1,0,'L');
                    $this->Cell(20,6,gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R');
                    if (($this->docVars->client['stapre'] == 'S' OR $this->docVars->client['stapre'] == 'T' ) && floatval($rigo['prelis']) >= 0.00001 ) {
                        $this->Cell(17,6,number_format($tot_row,$this->decimal_price,',',''),'TB',0,'R');
                        $this->Cell(7,6,$rigo['sconto']>=0.01?floatval($rigo['sconto']):'',1,0,'R',0,'',1);
                    } else {
                        $this->Cell(17,6);
                        $this->Cell(7,6,'','R',0);
                    }
                    $this->Cell(14);
                    $this->Cell(20,6,$rigo['codart'],1,0,'L');
                    $this->Cell(64,6,$rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(7,6,$rigo['unimis'],1,0,'L');
                    $this->Cell(20,6,gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R');
                    if (($this->docVars->client['stapre'] == 'S' OR $this->docVars->client['stapre'] == 'T') && floatval($rigo['prelis']) >= 0.00001 ) {
                        $this->Cell(17,6,number_format($tot_row,$this->decimal_price,',',''),'TB',0,'R');
                        $this->Cell(7,6,$rigo['sconto']>=0.01?floatval($rigo['sconto']):'',1,1,'R',0,'',1);
                    } else {
                        $this->Cell(17,6);
                        $this->Cell(7,6,'','R',1);
                    }

				} elseif ($rigo['tiprig'] == 2) {
                   $this->Cell(20,6,'','L');
                   $this->Cell(64,6,$rigo['descri'],'LR');
                   $this->Cell(51,6,'','R',0);
                   $this->Cell(14);
                   $this->Cell(20,6,'','L');
                   $this->Cell(64,6,$rigo['descri'],'LR');
                   $this->Cell(51,6,'','R',1);
                } elseif ($rigo['tiprig']==6 || $rigo['tiprig']==7) {
					$y = $this->GetY();
                    $this->writeHtmlCell(135,6,10,$y,$rigo['descri'],1,1);
				    $this->writeHtmlCell(135,6,159,$y,$rigo['descri'],1,1);
                } elseif ($rigo['tiprig'] == 11) {
                    $this->Cell(20,6,'','L');
                    $this->Cell(64,6,"CIG: " . $rigo['descri'],'LR',0,'L');
                    $this->Cell(51,6,'','R',0);
                    $this->Cell(14);
                    $this->Cell(20,6,'','L');
                    $this->Cell(64,6,"CIG: " . $rigo['descri'],'LR',0,'L');
                    $this->Cell(51,6,'','R',1);
                } elseif ($rigo['tiprig'] == 12) {
                    $this->Cell(20,6,'','L');
                    $this->Cell(64,6,"CUP: " . $rigo['descri'],'LR',0,'L');
                    $this->Cell(51,6,'','R',0);
                    $this->Cell(14);
                    $this->Cell(20,6,'','L');
                    $this->Cell(64,6,"CUP: " . $rigo['descri'],'LR',0,'L');
                    $this->Cell(51,6,'','R',1);
                } elseif ($rigo['tiprig'] == 13) {
                    $this->Cell(20,6,'','L');
                    $this->Cell(64,6,"IdDocumento: " . $rigo['descri'],'LR',0,'L');
                    $this->Cell(51,6,'','R',0);
                    $this->Cell(14);
                    $this->Cell(20,6,'','L');
                    $this->Cell(64,6,"IdDocumento: " . $rigo['descri'],'LR',0,'L');
                    $this->Cell(51,6,'','R',1);
                }  elseif ($rigo['tiprig'] == 90) {
                    $this->Cell(101, 6, 'VENDITA CESPITE: ' . $rigo['codart'], 1, 0, 'L');
                    $this->Cell(17, 6, '', 1);
                    $this->Cell(7, 6, '', 1, 1);
                    $this->Cell(101, 6, $rigo['descri'],1,0,'L',0,'',1);
                    if ($this->docVars->client['stapre'] == 'S' OR $this->docVars->client['stapre'] == 'T') {
                        $this->Cell(17,6,number_format($rigo['importo'],$this->decimal_price,',',''),'TB',0,'R');
                        $this->Cell(7,6,$rigo['sconto']>=0.01?floatval($rigo['sconto']):'',1,1,'R',0,'',1);
                    } else {
                        $this->Cell(17,6);
                        $this->Cell(7,6,'','R',1);
                    }
                }
       }
    }

    function pageFooter() {
        $y = $this->GetY();
        $this->Rect(10,$y,135,152-$y); //questa marca le linee dx e sx del documento
        $this->Rect(159,$y,135,152-$y); //questa marca le linee dx e sx del documento
        $this->SetY(152);
        $this->SetFont('helvetica','',8);
        $this->Cell(111, 5,'Pagamento - Banca','LTR',0,'C',1);
        $this->Cell(24,5,'T O T A L E','LTR',0,'C',1);
        $this->Cell(14);
        $this->Cell(111, 5,'Pagamento - Banca','LTR',0,'C',1);
        $this->Cell(24,5,'T O T A L E','LTR',1,'C',1);
        $this->Cell(111,5,$this->pagame['descri'],'LBR',0,'C',0,'',1);
        $this->Cell(24,5,gaz_format_number($this->totddt),'LBR',0,'C',0,'',1);
        $this->Cell(14);
        $this->Cell(111,5,$this->pagame['descri'],'LBR',0,'C',0,'',1);
        $this->Cell(24,5,gaz_format_number($this->totddt),'LBR',1,'C',0,'',1);
        $this->Cell(40,5,'Spedizione','LTR',0,'C',1,'',1);
        $this->Cell(80,5,'Vettore','LTR',0,'C',1,'',1);
        $this->Cell(15,5,'Trasporto','LTR',0,'C',1,'',1);
        $this->Cell(14);
        $this->Cell(40,5,'Spedizione','LTR',0,'C',1,'',1);
        $this->Cell(80,5,'Vettore','LTR',0,'C',1,'',1);
        $this->Cell(15,5,'Trasporto','LTR',1,'C',1,'',1);
        $this->Cell(40,5,$this->tesdoc['spediz'],'LBR',0,'C');
        $this->Cell(80,5,$this->docVars->vettor['ragione_sociale'].' '.
                          $this->docVars->vettor['indirizzo'].' '.
                          $this->docVars->vettor['citta'].' '.
                          $this->docVars->vettor['provincia'],'LBR',0,'C',0,'',1);
        if ($this->docVars->tesdoc['traspo'] == 0) {
            $ImportoTrasporto = "";
        } else {
            $ImportoTrasporto = gaz_format_number($this->docVars->tesdoc['traspo']);
        }
        $this->Cell(15,5,$ImportoTrasporto,'LBR',0,'C');
        $this->Cell(14);
        $this->Cell(40,5,$this->tesdoc['spediz'],'LBR',0,'C');
        $this->Cell(80,5,$this->docVars->vettor['ragione_sociale'].' '.
                          $this->docVars->vettor['indirizzo'].' '.
                          $this->docVars->vettor['citta'].' '.
                          $this->docVars->vettor['provincia'],'LBR',0,'C',0,'',1);
        if ($this->docVars->tesdoc['traspo'] == 0) {
            $ImportoTrasporto = "";
        } else {
            $ImportoTrasporto = gaz_format_number($this->docVars->tesdoc['traspo']);
        }
        $this->Cell(15,5,$ImportoTrasporto,'LBR',1,'C');
        $this->Cell(51,5,'Inizio trasporto','LTR',0,'C',1);
        if (empty($this->docVars->vettor['ragione_sociale'])){
            $signature=' Firma del conducente o destinatario ';
        } else {
            $signature=' Firma del vettore ';
        }
        $this->Cell(84,5,$signature,'LTR',0,'C',1);
        $this->Cell(14);
        $this->Cell(51,5,'Inizio trasporto','LTR',0,'C',1);
        if (empty($this->docVars->vettor['ragione_sociale'])){
            $signature=' Firma del conducente o destinatario ';
        } else {
            $signature=' Firma del vettore ';
        }
        $this->Cell(84,5,$signature,'LTR',1,'C',1);
        if ($this->day > 0) {
           $this->Cell(51,5,'data '.$this->day.'-'.$this->month.'-'.$this->year,'LR',0,'C');
        } else {
           $this->Cell(51,5,'      data','LR',0,'L');
        }
        $this->Cell(84,5,'','R',0);
        $this->Cell(14);
        if ($this->day > 0) {
           $this->Cell(51,5,'data '.$this->day.'-'.$this->month.'-'.$this->year,'LR',0,'C');
        } else {
           $this->Cell(51,5,'      data','LR',0,'L');
        }
        $this->Cell(84,5,'','R',1);
        $this->Cell(51,5,'ora '.$this->ora.':'.$this->min,'LRB',0,'C');
        $this->Cell(84,5,'','RB',0);
        $this->Cell(14);
        $this->Cell(51,5,'ora '.$this->ora.':'.$this->min,'LRB',0,'C');
        $this->Cell(84,5,'','RB',1);
    }

    function Footer()
    {
           $this->SetY(-20);
           $this->SetFont('helvetica','',8);
           $this->MultiCell(135,4,$this->intesta1.' '.$this->intesta2.' '.$this->intesta3.' '.$this->intesta4.' ',0,'C',0);
           $this->SetXY(155,-20);
           $this->SetFont('helvetica','',8);
           $this->MultiCell(135,4,$this->intesta1.' '.$this->intesta2.' '.$this->intesta3.' '.$this->intesta4.' ',0,'C',0);
    }
}
?>
