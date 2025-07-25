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
#[AllowDynamicProperties]
class OrdineCliente extends Template
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

  function setTesDoc() {
    $this->tesdoc = $this->docVars->tesdoc;
    $this->giorno = substr($this->tesdoc['datemi'],8,2);
    $this->mese = substr($this->tesdoc['datemi'],5,2);
    $this->anno = substr($this->tesdoc['datemi'],0,4);
    $this->docVars->gazTimeFormatter->setPattern('MMMM');
    $this->nomemese = ucwords($this->docVars->gazTimeFormatter->format(new DateTime($this->tesdoc['datemi'])));
    $this->sconto = $this->tesdoc['sconto'];
    $this->trasporto = $this->tesdoc['traspo'];
    $this->tipdoc = 'Conferma d\'Ordine da Cliente n.'.$this->tesdoc['numdoc'].'/'.$this->tesdoc['seziva'].' del '.$this->giorno.' '.$this->nomemese.' '.$this->anno;
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

  function pageHeader() {
      $this->setTesDoc();
      $this->StartPageGroup();
      $this->newPage();
  }

  function body() {
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
          $this->Cell(80, 5, "Data documento: " . gaz_format_date($rigo['descri']), 'LR', 0, 'L', 0, '', 1);
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
        case "50":
          $this->Cell(25, 6,  $this->docVars->ExternalDoc[$rigo['id_rig']]['oriname'].'.'.$this->docVars->ExternalDoc[$rigo['id_rig']]['ext'],1,0,'L',0,'',1);
          $this->Cell(80, 6, $rigo['descri'],1,0,'L',0,'',1);
          $this->Cell(7,  6, $rigo['unimis'],1,0,'C');
          $this->Cell(16, 6, gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),1,0,'R',0,'',1);
          if ($rigo['prelis'] > 0) {
             $this->Cell(18, 6, number_format($rigo['prelis'],$this->decimal_price,',',''),1,0,'R');
          } else {
             $this->Cell(18, 6, '',1);
          }
          if ($rigo['sconto']> 0) {
             $this->Cell(8, 6,  number_format($rigo['sconto'],1,',',''),1,0,'C');
          } else {
             $this->Cell(8, 6, '',1);
          }
          if ($rigo['importo'] > 0) {
             $this->Cell(20, 6, gaz_format_number($rigo['importo']),1,0,'R',0,'',1);
          } else {
             $this->Cell(20, 6, '',1);
          }
          $this->Cell(12, 6, gaz_format_number($rigo['pervat']),1,1,'R');
        break;
        case "51":
          $this->Cell(25, 6, $this->docVars->ExternalDoc[$rigo['id_rig']]['oriname'].'.'.$this->docVars->ExternalDoc[$rigo['id_rig']]['ext'],1,0,'L',0,'',1);
          $this->Cell(80,6,$rigo['descri'],'LR',0,'L',0,'',1);
          $this->Cell(81,6,'','R',1);
        break;
        case "910":
          $cf = json_decode($rigo['custom_field']);
          $this->SetTextColor(205,0,0);
          $this->writeHtmlCell(186,6,10,$this->GetY(),"ANNULLATO:<br>il ".$cf->cancellation->date." per ".$cf->cancellation->reason."<br>".$rigo['descri']." ".$rigo['unimis']." ".floatval($rigo['quanti'])." x ".floatval($rigo['prelis']),1,1);
          $this->SetTextColor(0,0,0);
        break;
      }
    }
  }


  function compose() {
    $this->body();
  }

  function pageFooter() {
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
    $this->Cell(186,6,((isset($vettor['descri']))?$vettor['descri']:''),1,1,'L');
    $this->Cell(36, 6);
    $this->Cell(150,6,'Firma del cliente per approvazione:',0,1,'L');
    $this->Cell(86, 6);
    $this->Cell(100,6,'','B',1,'L');
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
    if ( $this->sedelegale!="" ) {
      $this->MultiCell(184, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4 . ' ' . "SEDE LEGALE: ".$this->sedelegale, 0, 'C', 0);
    } else {
      $this->MultiCell(184, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4, 0, 'C', 0);
    }
  }
}

?>
