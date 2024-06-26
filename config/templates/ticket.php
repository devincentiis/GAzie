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

class Ticket extends Template
{
    function setTesDoc()
    {
      $this->company = $this->docVars->azienda;
      $this->foro = gaz_dbi_get_row($this->docVars->gTables['provinces'],'abbreviation',$this->company['prospe']);
      $this->tesdoc = $this->docVars->tesdoc;
      $this->orderman = gaz_dbi_get_row($this->docVars->gTables['orderman'],'id',$this->tesdoc['id_orderman']);
      $this->tecnico=gaz_dbi_get_row($this->docVars->gTables['staff'], 'id_staff',$this->orderman['id_staff_def']);
      $anagrafica = new Anagrafica();
      $this->staff = $anagrafica->getPartner($this->tecnico['id_clfoco']);
      $this->campi=gaz_dbi_get_row($this->docVars->gTables['campi'], 'codice',$this->orderman['campo_impianto']);
      $this->lines = $this->docVars->getRigo();
      $this->giorno = substr($this->tesdoc['datemi'],8,2);
      $this->mese = substr($this->tesdoc['datemi'],5,2);
      $this->anno = substr($this->tesdoc['datemi'],0,4);
      $this->docVars->gazTimeFormatter->setPattern('MMMM');
      $this->nomemese = $this->docVars->gazTimeFormatter->format(new DateTime($this->tesdoc['datemi']));
      $this->sconto = $this->tesdoc['sconto'];
      $this->trasporto = $this->tesdoc['traspo'];
      $this->tipdoc = 'Ticket di assistenza n.'.$this->tesdoc['numdoc'].'/'.$this->tesdoc['seziva'].' del '.$this->giorno.' '.$this->nomemese.' '.$this->anno;
      $this->show_artico_composit = $this->docVars->show_artico_composit;
    }
    function newPage() {
        $this->AddPage();
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
		$url= "http://" . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/ts.php?id='.$this->tesdoc['id_orderman'].'&co='.$_SESSION['company_id'];
		require('../../library/qrcode/phpqrcode.php');
		$qrc = new QRcode();
		$qrc->png($url, DATA_DIR . 'files/tmp/qr.png');
		$this->Image( DATA_DIR . 'files/tmp/qr.png',10,78,22,22,'',$url);
        $this->SetFont('helvetica','',10);
        $this->Cell(22,5);
        $this->Cell(35,5,'Usa il QRCode con l\'indirizzo','',0,'L',0,'',1);
        $this->Cell(80,5,$url,'',0,'L',1,'',1);
        $this->Cell(51,5,'per controllare l\'avanzamento dei lavori','',1,'L',0,'',1);
        $this->Ln(2);
        $this->Cell(22,5);
        $this->Cell(44,5,'Descrizione intervento:','LT',0,'L',1,'',1);
        $this->Cell(120,5,$this->orderman['description'],'TR',1,'L',0,'',1);
        $this->Cell(22,5);
        $this->Cell(44,5,'Nome tecnico/consulente:','L',0,'L',1,'',1);
        $this->Cell(120,5,$this->staff['ragso2'].' '.$this->staff['ragso1'],'R',1,'L',0,'',1);
        $this->Cell(22,5);
        $this->Cell(44,5,'Luogo di esecuzione:','L',0,'L',1,'',1);
        $this->Cell(120,5,$this->campi['descri'],'R',1,'L',0,'',1);
        $this->Cell(66,5,'Suggerimenti e informazioni:','LT',0,'L',1,'',1);
        $this->Cell(120,5,'','TR',1,'L',0,'',1);
        $this->MultiCell(186,4,$this->orderman['add_info'],'BLR','L');
        $this->Ln(2);
    }

    function pageHeader()
    {
        $this->setTesDoc();
        $this->StartPageGroup();
        $this->newPage();
    }
    function body()
    {
		foreach ($this->lines AS $key => $rigo) {
			if ($key==5){
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
		  if($key>=5){
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
                    $this->Cell(105,6,$rigo['descri'],'LR',0,'L',0,'',1);
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
                    $this->writeHtmlCell(186,6,10,$this->GetY(),$rigo['descri'],1,1);
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
    }


    function compose()
    {
        $this->body();
    }

    function pageFooter()
    {
        $this->SetY(200);
        $this->SetFont('helvetica','B',8);
        $this->Cell(186, 5,"Condizioni generali",1,1,'C',1);
        $this->Cell(186,4,'Assistenza hardware:','LR',1,'L');
        $this->SetFont('helvetica','',6);
        $this->MultiCell(186,4,"1) L'intervento se in garanzia, copre esclusivamente i difetti di conformità del prodotto acquistato presso ".$this->intesta1.", ai sensi della legge. Non sono coperti da garanzia i prodotti che presentino chiari segni di manomissione o guasti causati da un'uso improprio del prodotto o da agenti esterni non riconducibili a vizi e/o difetti di fabbricazione. Pertanto in tal caso ".$this->intesta1." non sarà tenuta ad effettuare gratuitamente le riparazioni necessarie, ma potrà effettuarle, su richiesta del cliente a pagamento e secondo il preventivo che verrà fornito.
		2) Il cliente dichiara di essere a conoscenza che l'intervento per la riparazione può comportare l'eventuale perdita totale o parziale di programmi e dati in qualunque modo contenuti o registrati nel prodotto consegnato per la riparazione. ".$this->intesta1." non si assume responsabilità alcuna riguardo a tale perdita, pertanto è esclusiva cura del cliente assicurarsi di aver effettuato le copie di sicurezza dei dati. A tale proposito si consiglia di richiedere a ".$this->intesta1.", che provvederà a titolo oneroso, per l'effettuazione dei backup di tutti i dati. In ogni caso il cliente è unico ed esclusivo responsabile di dati, informazioni e programmi contenuti o registrati in qualunque modo nel prodotto consegnato a ".$this->intesta1." con particolare riferimento alla liceità e legittima titolarità degli stessi.\n",'LR','L');
        $this->Ln(0);
        $this->SetFont('helvetica','B',8);
        $this->Cell(186,4,'Assistenza software:','LR',1,'L');
        $this->SetFont('helvetica','',6);
        $this->MultiCell(186,4,"1) Il servizio verrà prestato dal personale ".$this->intesta1." o scelto da quest'ultima durante l'orario in vigore per il proprio personale e compatibilmente con la sua disponibilità di personale e risorse. ".$this->intesta1." si riserva la facoltà di affidare i servizi di assistenza informatica a terzi che, a suo insindacabile giudizio, possiedano la competenza e le risorse necessarie.
		2) Sono espressamente esclusi dai servizi e dalle prestazioni: la cessione di software, materiali di consumo, materiale hardware di qualunque natura e/o quant'altro non specificatamente richiesto dal cliente. Nel  caso  in  cui  fossero  necessari  software  e/o  materiali  vari  durante  gli  interventi  sopracitati,  questi  dovranno essere forniti dal Cliente. I beni eventualmente necessari forniti da ".$this->intesta1." e saranno fatturati separatamente.\n",'LR','L');
        $this->Ln(0);
        $this->SetFont('helvetica','B',8);
        $this->Cell(186,4,'Foro competente:','LR',1,'L');
        $this->SetFont('helvetica','',6);
        $this->MultiCell(186,4,"Per qualsiasi controversia è esclusivamente competente il foro di ".strtoupper($this->foro['name']).".\n ",'LBR','L');
        $this->Ln(1);
        $this->Cell(93,5,'per '.$this->intesta1,0,0,'L');
        $this->Cell(93,5,'Firma del cliente per approvazione:',0,1,'L');
        $this->Cell(73,5,'','B',0,'L');
        $this->Cell(20,5);
        $this->Cell(83,5,'','B',1,'L');
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
