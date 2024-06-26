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

require_once("../../library/include/calsca.inc.php");
require('template_scheda.php');

class FatturaImmediata extends Template_con_scheda
{
    public $tesdoc;
    public $giorno;
    public $mese;
    public $anno;
    public $sconto;
    public $virtual_taxstamp;
    public $taxstamp;
    public $trasporto;
    public $tot_rp;
    public $tottraspo;
    public $tipdoc;
    public $descriptive_last_row;
    public $show_artico_composit;
    public $sedelegale;
    public $numPages;
    public $_tplIdx;
    public $extdoc_acc;

    function setTesDoc()
    {
      $this->tesdoc = $this->docVars->tesdoc;
      $this->giorno = substr($this->tesdoc['datfat'],8,2);
      $this->mese = substr($this->tesdoc['datfat'],5,2);
      $this->anno = substr($this->tesdoc['datfat'],0,4);
      if ($this->tesdoc['datfat']){
        $this->docVars->gazTimeFormatter->setPattern('MMMM');
        $nomemese = ucwords($this->docVars->gazTimeFormatter->format(new DateTime($this->tesdoc['datfat'])));
      } else {
        $nomemese = '';
      }
      $this->virtual_taxstamp=$this->tesdoc['virtual_taxstamp'];
      $this->taxstamp=$this->tesdoc['taxstamp'];
      $this->sconto = $this->tesdoc['sconto'];
      $this->trasporto = $this->tesdoc['traspo'];
      if (isset($this->tesdoc['fattura_elettronica_original_name']) && strlen($this->tesdoc['fattura_elettronica_original_name'])>10){ // file importato
        $numfat = $this->tesdoc['numfat'];
      } else if ($this->tesdoc['numfat']>0){
        $numfat = $this->tesdoc['numfat'].'/'.$this->tesdoc['seziva'];
      } else {
        $numfat = '_ _ _ _ _ _ _';
      }
        if ($this->tesdoc['tipdoc'] == 'FAF') {
            $descri = 'Autofattura (TD26) n.';
        } elseif ($this->tesdoc['tipdoc'] == 'FAA') {
            $descri = 'Fattura di acconto n.';
        } else {
            $descri = 'Fattura immediata n.';
        }
        $this->tipdoc = $descri.$numfat.' del '.$this->giorno.' '.$nomemese.' '.$this->anno;
    }
    function newPage() {
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->AddPage();
        $this->SetFont('helvetica','',9);
        $this->Cell(25,6,'Codice',1,0,'L',1);
        $this->Cell(80,6,'Descrizione',1,0,'L',1);
        $this->Cell(7, 6,'U.m.',1,0,'C',1);
        $this->Cell(16,6,'Quantità',1,0,'R',1);
        $this->Cell(20,6,'Prezzo',1,0,'R',1);
        $this->Cell(6, 6,'%Sc',1,0,'C',1);
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
        if ($this->docVars->regime_fiscale=='RF02') {
            $this->SetFont('helvetica', 'B', 10);
            $this->MultiCell(186, 10, "Operazione effettuata ai sensi dell'art.1 comma 100 Legge 244/2007.\nCompenso non assoggettato a ritenuta d'acconto ai sensi dell'art.27 del DL 98 del 06.07.2011",1,'L', 0,1);
            $this->SetFont('helvetica', '', 9);
        } elseif ($this->docVars->regime_fiscale=='RF19') {
            $this->SetFont('helvetica', 'B', 10);
            $this->MultiCell(186, 10, "Operazione effettuata ai sensi dell'art.1 commi da 54 a 89 Legge 190/2014 e successive modifiche.\nCompenso non assoggettato a ritenuta d'acconto ai sensi dall'art.1 comma 67 Legge n.190/2014",1,'L', 0,1);
            $this->SetFont('helvetica', '', 9);
        }
        $lines = $this->docVars->getRigo();
      $prevTiprig=false;
      foreach ($lines AS $key => $rigo) {
            if (($this->GetY() >= 157 && $this->taxstamp >= 0.01) || $this->GetY() >= 186 ) { // mi serve per poter stampare la casella del bollo
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
                    $this->Cell(25, 6, $rigo['codart'],1,0,'L', 0, '', 1);
                    $this->Cell(80, 6, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(7,  6, $rigo['unimis'],1,0,'C');
                    $this->Cell(16, 6, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R');
                    $this->Cell(20, 6, number_format($rigo['prelis'],$this->decimal_price,',','.'),1,0,'R');
                    if ($rigo['sconto']>0) {
                       $this->Cell(6, 6, floatval($rigo['sconto']),1,0,'C', 0, '', 1);
                    } else {
                       $this->Cell(6, 6, '',1,0,'C');
                    }
                    $this->Cell(20, 6, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(12, 6, gaz_format_number($rigo['pervat']),1,1,'R');
                    break;
                case "1":
                    $this->Cell(25, 6, $rigo['codart'],1,0,'L', 0, '', 1);
                    $this->Cell(80, 6, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(49, 6, '',1);
                    $this->Cell(20, 6, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(12, 6, gaz_format_number($rigo['pervat']),1,1,'R');
                    break;
                case "2":
                    //$this->Cell(25,6,'','L');
                    $this->Cell(154,6,$rigo['descri'],'LR',0,'L',0,'',1);
                    $this->Cell(32,6,'','R',1);
                    break;
                case "3":
                    $this->Cell(25,6,'',1,0,'L');
                    $this->Cell(80,6,$rigo['descri'],'B',0,'L',0,'',1);
                    $this->Cell(49,6,'','B',0,'L');
                    $this->Cell(20,6,gaz_format_number($rigo['prelis']),1,0,'R');
                    $this->Cell(12,6,'',1,1,'R');
                    break;
                case "4":
                    $this->Cell(25, 6, $rigo['codart'],1,0,'L', 0, '', 1);
                    $this->Cell(129, 6, $rigo['descri'].'('.floatval($rigo['provvigione']).'% di '.gaz_format_number($rigo['prelis']).')',1,0,'L',0,'',1);
                    $this->Cell(20, 6, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(12, 6, gaz_format_number($rigo['pervat']),1,1,'R');
                    break;
                case "6":
                case "8":
                    $this->writeHtmlCell(186,6,10,$this->GetY(),$rigo['descri'],1,1);
                    break;
                case "11":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Codice Identificativo Gara (CIG): " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                case "12":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Codice Unitario Progetto (CUP): " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                case "13":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Identificativo documento: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                case "14":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Data documento: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                case "15":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Num.Linea documento: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                case "16":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Codice Commessa/Convenzione: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                case "17":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Riferimento Amministrazione: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                case "21":
                  $descri21=$prevTiprig=='21'?'':'Causale:';
                  $this->Cell(20, 5, $descri21, 'L',0,'R');
                  $this->Cell(166, 5, $rigo['descri'], 'R', 1, 'L', 0, '', 1);
                    break;
                case "25":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Stato avanzamento lavori, fase: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                case "26":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Lettera intento: " . $rigo['descri']." del ".gaz_format_date($rigo['codart']), 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                case "31":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Dati Veicoli ex art.38, immatricolato il " . gaz_format_date($rigo['descri']).', km o ore:'.intval($rigo['quanti']), 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                case "50": // normale c/allegato
                  $this->Cell(25, 5, $this->docVars->ExternalDoc[$rigo['id_rig']]['oriname'].'.'.$this->docVars->ExternalDoc[$rigo['id_rig']]['ext'],1,0,'L',0,'',1);
                  $this->Cell(80, 5, $rigo['descri'],1,0,'L',0,'',1);
                  $this->Cell(7,  5, $rigo['unimis'],1,0,'C');
                  $this->Cell(16, 5, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R',0,'',1);
                  if ($rigo['prelis'] > 0) {
                     $this->Cell(20, 5, number_format($rigo['prelis'],$this->decimal_price,',',''),1,0,'R');
                  } else {
                     $this->Cell(20, 5, '',1);
                  }
                  if ($rigo['sconto']> 0) {
                     $this->Cell(6, 5,  number_format($rigo['sconto'],1,',',''),1,0,'C');
                  } else {
                     $this->Cell(6, 5, '',1);
                  }
                  if ($rigo['importo'] > 0) {
                     $this->Cell(20, 5, gaz_format_number($rigo['importo']),1,0,'R',0,'',1);
                  } else {
                     $this->Cell(20, 5, '',1);
                  }
                  $this->Cell(12, 5, gaz_format_number($rigo['pervat']), 1, 1, 'R');
                  $this->tot_rp +=$rigo['quanti'];
                  break;
                case "51": // descrittivo c/allegato
                  $this->Cell(25, 5, $this->docVars->ExternalDoc[$rigo['id_rig']]['oriname'].'.'.$this->docVars->ExternalDoc[$rigo['id_rig']]['ext'],1,0,'L',0,'',1);
                  $this->Cell(80,5,$rigo['descri'],'LR',0,'L',0,'',1);
                  $this->Cell(81,5,'','R',1);
                  break;
                case "210":
                    $oldy = $this->GetY();
                    $this->SetFont('helvetica', '', 8);
                    $this->SetY($this->GetY()-6);
                    $this->Cell(105, 8, '('.$rigo['unimis'].' '.gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity).')',0,0,'R');
                    $this->SetY( $oldy );
                    $this->SetFont('helvetica', '', 9);
                    break;
                case "90":
                    $this->Cell(154, 6, 'VENDITA CESPITE: ' . $rigo['codart'], 1, 0, 'L');
                    $this->Cell(20, 6, '', 1);
                    $this->Cell(12, 6, '', 1, 1);
                    $this->Cell(105, 6, $rigo['descri'],1,0,'L',0,'',1);
                    $this->Cell(49, 6, '',1);
                    $this->Cell(20, 6, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(12, 6, gaz_format_number($rigo['pervat']),1,1,'R');
                    break;
                }
                if ($rigo['ritenuta']>0) {
                    $this->Cell(154, 5,'Ritenuta d\'acconto al '.gaz_format_number($rigo['ritenuta']).'%','LB',0,'R');
                    $this->Cell(20, 5,gaz_format_number(round($rigo['importo']*$rigo['ritenuta']/100,2)),'RB',0,'R');
                    $this->Cell(12, 5,'',1,1,'R');
                }
        $prevTiprig=$rigo['tiprig'];
        }
    }


    function compose()
    {
        $this->body();
    }

    function pageFooter()
    {
        if (!empty($this->descriptive_last_row) ) { // aggiungo alla fine un eventuale rigo descrittivo dalla configurazione avanzata azienda
                $this->Cell(186,6,$this->descriptive_last_row,1,1,'L',0,'',1);
		}
        if ($this->taxstamp >= 0.01 ) {
            if ($this->virtual_taxstamp == 2 || $this->virtual_taxstamp == 3) {
                $this->Cell(186,5,'','LR',1);
                $this->Cell(130,8,'','L',0,0);
                $this->Cell(56,8,"Bollo assolto ai sensi del","TLR",1,"C");
                $this->Cell(130,8,'','L',0,0);
                $this->Cell(56,8,"decreto MEF 17.06.2014 (art.6)","LR",1,"C");
                $this->Cell(130,8,'','L',0,0);
                $this->Cell(56,8," € ".gaz_format_number($this->taxstamp),'LR',1,'C');
            } else {
                $this->Cell(186,5,'','LR',1);
                $this->Cell(150,8,'','L',0,0);
                $this->Cell(36,8,"Bollo applicato","TLR",1,"C");
                $this->Cell(150,8,'','L',0,0);
                $this->Cell(36,8,"sull'originale","LR",1,"C");
                $this->Cell(150,8,'','L',0,0);
                $this->Cell(36,8,"€ ".gaz_format_number($this->taxstamp),'LR',1,'C');
            }
        }
        $y = $this->GetY();
        $this->Rect(10,$y,186,188-$y); //questa marca le linee dx e sx del documento
        //stampo il castelletto
        $this->SetY(208);
        $this->Cell(62,6, 'Pagamento','LTR',0,'C',1);
        $this->Cell(68,6, 'Castelletto    I.V.A.','LTR',0,'C',1);
        $this->Cell(56,6, 'T O T A L E    F A T T U R A','LTR',1,'C',1);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(62,6, $this->pagame['descri'],'LR',0,'C');
        $this->Cell(18,4, 'Imponibile','LR',0,'C',1);
        $this->Cell(32,4, 'Aliquota','LR',0,'C',1);
        $this->Cell(18,4, 'Imposta','LR',1,'C',1);
        $this->docVars->setTotal();
        foreach ($this->docVars->cast as $key => $value) {
                if ($this->tesdoc['id_tes'] > 0) {
                   $this->Cell(62);
                   $this->Cell(18, 4, gaz_format_number($value['impcast']).' ', 'R', 0, 'R');
                   $this->Cell(32, 4, $value['descriz'],0,0,'C',0,'',1);
                   $this->Cell(18, 4, gaz_format_number($value['ivacast']).' ','L',1,'R');
                } else {
                   $this->Cell(62);
                   $this->Cell(68, 4,'','LR',1);
                 }
        }
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

        //stampo i totali
        $this->SetY(188);
        $this->SetFont('helvetica','',9);
        $this->Cell(82, 5,'Agente','LTR',0,'C',1);
        $this->Cell(26, 5,'Peso netto','LTR',0,'C',1);
        $this->Cell(26, 5,'Peso lordo','LTR',0,'C',1);
        $this->Cell(26, 5,'N.colli','LTR',0,'C',1);
        $this->Cell(26, 5,'Volume','LTR',1,'C',1);
        $this->Cell(82, 5,$this->agente,'LR');
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
        $this->Cell(36, 5,'Tot. Corpo','LTR',0,'C',1);
        $this->Cell(16, 5,'% Sconto','LTR',0,'C',1);
        $this->Cell(24, 5,'Spese Incasso','LTR',0,'C',1);
        $this->Cell(26, 5,'Trasporto','LTR',0,'C',1);
        $this->Cell(36, 5,'Tot.Imponibile','LTR',0,'C',1);
        $this->Cell(26, 5,'Tot. I.V.A.','LTR',0,'C',1);
        $this->Cell(22, 5,'Bolli(tratte)','LTR',1,'C',1);
        if ($totimpmer > 0) {
           $this->Cell(36, 5, gaz_format_number($totimpmer),'LBR',0,'C');
        } else {
           $this->Cell(36, 5,'','LBR');
        }
        if ($this->tesdoc['sconto'] > 0) {
           $this->Cell(16, 5, gaz_format_number($this->tesdoc['sconto']),'LBR',0,'C');
        } else {
           $this->Cell(16, 5,'','LBR');
        }
        if ($speseincasso > 0) {
           $this->Cell(24, 5, gaz_format_number($speseincasso),'LBR',0,'C');
        } else {
           $this->Cell(24, 5,'','LBR');
        }
        if ($this->trasporto > 0) {
           $this->Cell(26, 5, gaz_format_number($this->trasporto),'LBR',0,'C');
        } else {
           $this->Cell(26, 5,'','LBR');
        }
        if ($totimpfat > 0) {
           $this->Cell(36, 5, gaz_format_number($totimpfat),'LBR',0,'C');
        } else {
           $this->Cell(36, 5,'','LBR');
        }
        if ($totivafat > 0) {
           $this->Cell(26, 5, gaz_format_number($totivafat),'LBR',0,'C');
        } else {
           $this->Cell(26, 5,'','LBR');
        }
        if ($impbol > 0) {
            $this->Cell(22, 5, gaz_format_number($impbol),'LBR', 0,'C');
        } else {
           $this->Cell(22, 5,'','LBR');
        }

        $this->SetY(214);
        $this->Cell(130);
        $totale = $totimpfat + $totivafat + $impbol+ $taxstamp;
        if ($this->tesdoc['id_tes'] > 0) {
           if ($ritenuta>0) {
               $this->SetFont('helvetica','B',11);
               $this->Cell(56, 6, '€ '.gaz_format_number($totale),'LBR', 2, 'R');
               $this->SetFont('helvetica', '', 11);
               $this->Cell(56, 4,'Totale ritenute: € '.gaz_format_number($ritenuta),'LR', 2, 'R');
               $this->Cell(56, 6,'Totale a pagare: € '.gaz_format_number($totale-$ritenuta-$totivasplitpay),'LBR', 1, 'R');
           } else {
               $this->SetFont('helvetica','B',18);
               $this->Cell(56, 16, '€ '.gaz_format_number($totale-$totivasplitpay),'LBR', 1, 'C');
           }
        } else {
           $this->Cell(56, 24,'','LBR',1);
        }
        $this->SetY(220);
        $this->SetFont('helvetica','',9);
        if (!empty($this->banapp['descri']) && $this->pagame['tippag'] != 'D' && $this->pagame['tippag'] != 'O') {
           $this->Cell(62, 5, 'Banca d\'appoggio','LTR',1,'C',1);
           $this->Cell(62, 5, $this->banapp['descri'],'LR',1,'C',0,'',1);
           $this->Cell(62, 5, ' ABI '.sprintf("%05d",$this->banapp['codabi']).' CAB '.$this->banapp['codcab'],'LRB',0,'C');
        } elseif (!empty($this->banacc['iban'])){
           $this->Cell(62, 5, 'Banca d\'accredito','LTR',1,'C',1);
           $this->Cell(62, 5, $this->banacc['ragso1'],'LR',1);
           $this->Cell(62, 5, 'IBAN '.$this->banacc['iban'],'LRB');
        } else {
           $this->Cell(62, 5, '','LTR',1,'',1);
           $this->Cell(62, 5, '','LR',1);
           $this->Cell(62, 5, '','LRB',0);
        }
        $this->Cell(124,5, 'Date di Scadenza e Importo Rate','LTR',1,'C',1);
        $this->Cell(62, 5, 'Spedizione','LTR',0,'C',1);
        if ($this->pagame['tippag'] != 'D') {
           $this->Cell(31, 5, $ratpag['giorno']['0'].'-'.$ratpag['mese']['0'].'-'.$ratpag['anno']['0'],'LR',0,'C');
           $this->Cell(31, 5, $ratpag['giorno']['1'].'-'.$ratpag['mese']['1'].'-'.$ratpag['anno']['1'],'LR',0,'C');
           $this->Cell(31, 5, $ratpag['giorno']['2'].'-'.$ratpag['mese']['2'].'-'.$ratpag['anno']['2'],'LR',0,'C');
           $this->Cell(31, 5, $ratpag['giorno']['3'].'-'.$ratpag['mese']['3'].'-'.$ratpag['anno']['3'],'LR',1,'C');
           $this->Cell(62, 5, $this->tesdoc['spediz'],'LRB',0,'C');
           if ($ratpag['import']['0'] != 0) {
              $this->Cell(31, 5, gaz_format_number($ratpag['import']['0']),'LBR',0,'C');
           } else {
              $this->Cell(31, 5,'','LBR');
           }
           if ($ratpag['import']['1'] != 0) {
              $this->Cell(31, 5, gaz_format_number($ratpag['import']['1']),'LBR',0,'C');
           } else {
             $this->Cell(31, 5,'','LBR');
           }
           if ($ratpag['import']['2'] != 0) {
              $this->Cell(31, 5, gaz_format_number($ratpag['import']['2']),'LBR',0,'C');
           } else {
             $this->Cell(31, 5,'','LBR');
           }
           if ($ratpag['import']['3'] != 0) {
              $this->Cell(31, 5, gaz_format_number($ratpag['import']['3']),'LBR',1,'C');
           } else {
             $this->Cell(31, 5,'','LBR',1);
           }
        } else {
           $this->Cell(124, 5,'','LR',1);
           $this->Cell(62, 5, $this->tesdoc['spediz'],'LRB',0,'C');
           $this->Cell(124, 5,'','LBR',1);
        }
        if($this->pagame['incaut'] > 1 || $this->pagame['tippag']=='C') {
           $this->docVars->open_drawer();
        }
        if (empty($this->docVars->vettor['ragione_sociale'])){
            $signature=' Firma del conducente :';
        } else {
            $signature=' Firma/vettore:';
        }

        $this->Cell(35, 5,$signature,'LT',0,'L',1);
        $this->Cell(55, 5);
        $this->Cell(40, 5,'Inizio trasporto','LTR',0,'C',1);
        $this->Cell(56, 5,'Firma destinatario','LTR',1,'C',1);
        $this->Cell(90, 5,'','L');
        if ($this->tesdoc['id_tes'] > 0) {
           $this->Cell(40, 5, $this->day.'.'.$this->month.'.'.$this->year.' ore '.$this->ora.':'.$this->min,'LBR',0,'C');
        } else {
           $this->Cell(40, 5, 'data:              ore:    ','LBR',0,'L');
        }
        $this->Cell(56, 5,'','LR',1);
        $this->Cell(130,5,$this->docVars->vettor['ragione_sociale'].' '.
                          $this->docVars->vettor['indirizzo'].' '.
                          $this->docVars->vettor['citta'].' '.
                          $this->docVars->vettor['provincia'],'LBR',0,'L',0,'',1);
        $this->Cell(56, 5,'','LBR',1);
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
