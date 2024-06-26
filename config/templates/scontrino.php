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

require("../../library/include/calsca.inc.php");
require('template.php');

class Scontrino extends Template
{

    function setTesDoc()
    {
      $this->tesdoc = $this->docVars->tesdoc;
      $this->giorno = substr($this->tesdoc['datfat'],8,2);
      $this->mese = substr($this->tesdoc['datfat'],5,2);
      $this->anno = substr($this->tesdoc['datfat'],0,4);
      $this->docVars->gazTimeFormatter->setPattern('dd MMMM yyyy');
      $this->data = $this->docVars->gazTimeFormatter->format(new DateTime($this->tesdoc['datemi']));
      $this->sconto = $this->tesdoc['sconto'];
      $this->tipdoc = 'Copia non fiscale dello Scontrino n.'.$this->tesdoc['numdoc'].' del '.$this->data;
      $this->descriptive_last_row = $this->docVars->descriptive_last_row;
      $this->efattura = '';
      $this->ecr = $this->docVars->ecr;
    }

    function newPage() {
        $this->AddPage();
        $this->SetFont('helvetica','',8);
        $this->Cell(124);
        $this->Cell(62,5,'Registratore','LTR',1,'C',1);
        $this->Cell(124);
        $this->Cell(62,5,$this->ecr,'LBR',1,'C');
        $this->Ln(2);
        $this->Cell(23,6,'Codice',1,0,'C',1);
        $this->Cell(75,6,'Descrizione',1,0,'C',1);
        $this->Cell(6, 6,'U.m.',1,0,'C',1);
        $this->Cell(14,6,'Quantità',1,0,'C',1);
        $this->Cell(15,6,'Prezzo',1,0,'C',1);
        $this->Cell(7, 6,'%Sc.',1,0,'C',1);
        $this->Cell(18,6,'Importo',1,0,'C',1);
        $this->Cell(10,6,'%IVA',1,0,'C',1);
        $this->Cell(18,6,'Totale',1,1,'C',1);
    }

    function pageHeader() {
        $this->StartPageGroup();
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->newPage();
    }


    function compose()
    {
        $this->setTesDoc();
        $this->body();
    }

    function body()
    {
        $lines = $this->docVars->getTicketRow();
		foreach ($lines AS $key => $rigo) {
            if ($this->GetY() >= 195) {
                $this->Cell(186,6,'','T',1);
                $this->SetFont('helvetica','',14);
                $this->SetY(225);
                $this->Cell(186,12,'>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>> ',1,1,'R');
                $this->SetFont('helvetica','',9);
                $this->newPage();
                $this->Cell(186,5,'<<< --- SEGUE DA PAGINA PRECEDENTE --- <<< ',0,1);
            }
                switch($rigo['tiprig']) {
                case "0":
                    $this->Cell(23, 5, $rigo['codart'],1,0,'L',0,'L',0,'',1);
                    $this->Cell(75, 5, $rigo['descri'],1,0,'L',0,'L',0,'',1);
                    $this->Cell(6, 5, $rigo['unimis'],1,0,'C');
                    $this->Cell(14, 5, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R');
                    $this->Cell(15, 5, number_format($rigo['prelis'],$this->decimal_price,',',''),1,0,'R');
                    if ($rigo['sconto']>0) {
                       $this->Cell(7, 5,  number_format($rigo['sconto'],1,',',''),1,0,'C');
                    } else {
                       $this->Cell(7, 5, '',1,0,'C');
                    }
                    $this->Cell(18, 5, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(10, 5, gaz_format_number($rigo['pervat']),1,0,'R');
                    $this->Cell(18, 5, gaz_format_number($rigo['totale']),1,1,'R');
                    break;
                case "1":
                    $this->Cell(23, 5, $rigo['codart'],1,0,'L',0,'L',0,'',1);
                    $this->Cell(75, 5, $rigo['descri'],1,0,'L',0,'L',0,'',1);
                    $this->Cell(42, 5, '',1);
                    $this->Cell(18, 5, gaz_format_number($rigo['importo']),1,0,'R');
                    $this->Cell(10, 5, gaz_format_number($rigo['pervat']),1,0,'R');
                    $this->Cell(18, 5, gaz_format_number($rigo['totale']),1,1,'R');
                    break;
                case "2":
                    $this->Cell(23,5,'','L');
                    $this->Cell(75,5,$rigo['descri'],'LR',0,'L',0,'L',0,'',1);
                    $this->Cell(88,5,'','R',1);
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
                case "21":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Causale: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                case "25":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Stato avanzamento lavori, fase: " . $rigo['descri'], 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                case "31":
                    $this->Cell(25, 5, '', 'L');
                    $this->Cell(80, 5, "Dati Veicoli ex art.38, immatricolato il " . gaz_format_date($rigo['descri']).', km o ore:'.intval($rigo['quanti']), 'LR', 0, 'L', 0, '', 1);
                    $this->Cell(81, 5, '', 'R', 1);
                    break;
                }
        }
    }

    function pageFooter()
    {
        //effettuo il calcolo degli importi delle scadenze
        $ratpag = CalcolaScadenze($this->docVars->totale, $this->giorno, $this->mese, $this->anno, $this->pagame['tipdec'],$this->pagame['giodec'],$this->pagame['numrat'],$this->pagame['tiprat'],$this->pagame['mesesc'],$this->pagame['giosuc']);
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
        //FINE calcolo scadenze
        if (!empty($this->descriptive_last_row) ) { // aggiungo alla fine un eventuale rigo descrittivo dalla configurazione avanzata azienda
          if (strlen($this->descriptive_last_row)>200){// Antonio Germani - se è troppo lungo lo divido in due righe
            $txt1="";$txt2="";
            $descrtoolong=explode(" ",$this->descriptive_last_row);
            for ($n=0; $n<count($descrtoolong); $n++){
              if ($n<count($descrtoolong)/2){
                $txt1 .= $descrtoolong[$n]." ";
              }else{
                $txt2 .= $descrtoolong[$n]." ";
              }
            }
            $this->Cell(186,6,$txt1,1,1,'L',0,'',1);
            $this->Cell(186,6,$txt2,1,1,'L',0,'',1);
          } else {
            $this->Cell(186,6,$this->descriptive_last_row,1,1,'L',0,'',1);
          }
        }
        //stampo i totali
        $y = $this->GetY();
        $this->Rect(10,$y,186,212-$y); //questa marca le linee dx e sx del documento
        //stampo il castelletto
        $this->SetY(212);
        $this->Cell(62,6,'Pagamento','LTR',0,'C',1);
        $this->Cell(68,6,'Castelletto I.V.A.','LTR',0,'C',1);
        $this->Cell(56,6,'T O T A L E    F A T T U R A','LTR',1,'C',1);
        $this->SetFont('helvetica','',8);
        $this->Cell(62,6,$this->pagame['descri'],'LBR',0,'L');
        $this->Cell(18,4,'Imponibile','LR',0,'C',1);
        $this->Cell(32,4,'Aliquota','LR',0,'C',1);
        $this->Cell(18,4,'Imposta','LR',1,'C',1);
        foreach ($this->docVars->castel as $v) {
                if ($this->tesdoc['id_tes'] > 0) {
                   $this->Cell(62);
                   $this->Cell(18, 4, gaz_format_number($v['importo']).' ','LR', 0, 'R');
                   $this->Cell(32, 4, $v['descri'],0,0,'C');
                   $this->Cell(18, 4, gaz_format_number($v['iva']).' ','LR',1,'R');
                } else {
                   $this->Cell(62);
                   $this->Cell(68, 4,'','LR',1);
                }
        }
        $this->SetY(218);
        $this->SetFont('helvetica','B',16);
        $this->Cell(130);
        $this->Cell(56,12,'€ '.gaz_format_number($this->docVars->totale),'LR',1,'C');
        $this->SetY(224);
        $this->SetFont('helvetica','',9);
        if (!empty($this->banacc['iban'])){
           $this->Cell(62, 6, 'Banca d\'accredito','LTR',1,'C',1);
           $this->Cell(62, 5, $this->banacc['ragso1'],'LR',1);
           $this->Cell(62, 6, 'IBAN '.$this->banacc['iban'],'LRB',1,'C');
        } else {
           $this->Cell(62, 6, '','LTR',1,'',1);
           $this->Cell(62, 6, '','LR',1);
           $this->Cell(62, 6, '','LRB',1);
        }
        $this->Cell(130,6, 'Date di Scadenza e Importo Rate','LTR',1,'C',1);
        $this->Cell(32, 6, $ratpag['giorno']['0'].'-'.$ratpag['mese']['0'].'-'.$ratpag['anno']['0'],'LR',0,'C');
        $this->Cell(33, 6, $ratpag['giorno']['1'].'-'.$ratpag['mese']['1'].'-'.$ratpag['anno']['1'],'LR',0,'C');
        $this->Cell(32, 6, $ratpag['giorno']['2'].'-'.$ratpag['mese']['2'].'-'.$ratpag['anno']['2'],'LR',0,'C');
        $this->Cell(33, 6, $ratpag['giorno']['3'].'-'.$ratpag['mese']['3'].'-'.$ratpag['anno']['3'],'LR',1,'C');
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
           $this->Cell(33, 6, gaz_format_number($ratpag['import']['3']),'LBR',1,'C');
           } else {
           $this->Cell(33, 6,'','LBR',1);
        }
        $this->SetXY(140,230);
        $this->SetFont('helvetica','',10);
        $this->MultiCell(56,30,'','LBR',1,'J');
    }

    function Footer()
    {
        //Document footer
        $this->SetY(-20);
        $this->SetFont('helvetica','',8);
        if ( $this->sedelegale!="" ) {
            $this->MultiCell(184, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4 . ' ' . "SEDE LEGALE: ".$this->sedelegale, 0, 'C', 0);
        } else {
            $this->MultiCell(184, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4, 0, 'C', 0);
        }
    }
}

?>
