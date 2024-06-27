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

use setasign\Fpdi\Tcpdf\Fpdi;
class Template extends Fpdi {

  public $docVars;
  public $gaz_path;
  public $rigbro;
  public $aliiva;
  public $tesdoc;
  public $testat;
  public $pagame;
  public $banapp;
  public $banacc;
  public $logo;
  public $link;
  public $intesta1;
  public $intesta1bis;
  public $intesta2;
  public $intesta3;
  public $intesta4;
  public $sedelegale;
  public $numPages;
  public $_tplIdx;
  public $extdoc_acc;
  public $colore;
  public $decimal_quantity;
  public $decimal_price;
  public $perbollo;
  public $codice_partner;
  public $descri_partner;
  public $cod_univoco;
  public $pec_cliente;
  public $cliente1;
  public $cliente2;
  public $cliente3;
  public $cliente4;  // CAP, Città, Provincia
  public $cliente4b; // Nazione
  public $cliente5;  // P.IVA e C.F.
  public $agente;
  public $destinazione;
  public $clientSedeLegale;
  public $pers_title;
  public $fiscal_rapresentative;
  public $c_Attenzione;
  public $min;
  public $ora;
  public $day;
  public $month;
  public $year;
  public $withoutPageGroup;
  public $efattura;
  public $descriptive_last_row;
  public $descriptive_last_ddt;
  public $layout_pos_logo_on_doc;
  public $iban;

    function setVars(&$docVars, $Template = '') {
        $this->docVars = & $docVars;
        $this->gaz_path = '../../';
        $this->rigbro = $docVars->gTables['rigbro'];
        $this->aliiva = $docVars->gTables['aliiva'];
        $this->tesdoc = $docVars->tesdoc;
        $this->testat = $docVars->testat;
        $this->pagame = $docVars->pagame;
        $this->banapp = $docVars->banapp;
        $this->banacc = $docVars->banacc;
        $this->logo = $docVars->logo;
        $this->link = $docVars->link;
        $this->intesta1 = $docVars->intesta1;
        $this->intesta1bis = $docVars->intesta1bis;
        $this->intesta2 = $docVars->intesta2;
        $this->intesta3 = $docVars->intesta3 . $docVars->intesta4;
        $this->intesta4 = $docVars->codici;
        $this->sedelegale = $docVars->sedelegale;
        $this->colore = $docVars->colore;
        $this->decimal_quantity = $docVars->decimal_quantity;
        $this->decimal_price = $docVars->decimal_price;
        $this->perbollo = $docVars->perbollo;
        $this->codice_partner = $docVars->codice_partner;
        $this->descri_partner = $docVars->descri_partner;
        $this->cod_univoco = $docVars->cod_univoco;
        $this->pec_cliente = $docVars->pec_cliente;
        $this->cliente1 = $docVars->cliente1;
        $this->cliente2 = $docVars->cliente2;
        $this->cliente3 = $docVars->cliente3;
        $this->cliente4 = $docVars->cliente4;  // CAP, Città, Provincia
        $this->cliente4b = $docVars->cliente4b; // Nazione
        $this->cliente5 = $docVars->cliente5;  // P.IVA e C.F.
        $this->agente = $docVars->name_agente;
        if ( $docVars->destinazione == "" && isset($docVars->client['destin'])) {
            $this->destinazione = $docVars->client['destin'];
        } else {
            $this->destinazione = $docVars->destinazione;
        }
        $this->clientSedeLegale = '';
        $this->pers_title = $docVars->pers_title;
        if (!empty($docVars->clientSedeLegale)) {
            foreach ($docVars->clientSedeLegale as $value) {
                $this->clientSedeLegale .= $value . ' ';
            }
        }
        $this->fiscal_rapresentative = $docVars->fiscal_rapresentative;
        $this->c_Attenzione = $docVars->c_Attenzione;
        $this->min = $docVars->min;
        $this->ora = $docVars->ora;
        $this->day = $docVars->day;
        $this->month = $docVars->month;
        $this->year = $docVars->year;
        $this->withoutPageGroup = $docVars->withoutPageGroup;
        $this->efattura = $docVars->efattura;
        $this->descriptive_last_row = $this->docVars->descriptive_last_row;
        $this->descriptive_last_ddt = $this->docVars->descriptive_last_ddt;
        $this->layout_pos_logo_on_doc = $this->docVars->layout_pos_logo_on_doc;

        if ($this->tesdoc['tipdoc'] == 'AFA' || $this->tesdoc['tipdoc'] == 'AFT') {
          $this->iban = $docVars->iban;
        }
    }

    function Header() {
        if (isset($this->appendix)) { // se viene passato l'appendice
        } else {
            $this->SetFillColor(hexdec(substr($this->colore, 0, 2)), hexdec(substr($this->colore, 2, 2)), hexdec(substr($this->colore, 4, 2)));
            $this->SetFont('times', 'B', 14);

            //*+ DC - 16/01/2018
            //$this->Cell(130, 6, $this->intesta1, 0, 1, 'L');
            if ($this->layout_pos_logo_on_doc=='LEFT') {
                $this->SetXY(80,5);
                $this->Cell(130, 6, $this->intesta1, 0, 1, 'L');
                $this->SetXY(80,11);
            } else {
                $this->Cell(130, 6, $this->intesta1, 0, 1, 'L');
            }
            //*- DC - 16/01/2018

            $this->SetFont('helvetica', '', 8);
            $interlinea = 10;
            if (!empty($this->intesta1bis)) {
                $this->Cell(130, 3, $this->intesta1bis, 0, 2, 'L');
                $interlinea = 7;
            }
            $this->Cell(130, 3, $this->intesta2, 0, 2, 'L');
            $this->Cell(130, 3, $this->intesta3, 0, 2, 'L');
            if ( $this->sedelegale!="" ) {
				$this->Cell(130, 3, $this->intesta4, 0, 2, 'L');
				$this->Cell(130, 3, "SEDE LEGALE: ".$this->sedelegale, 0, 0, 'L');
			} else {
				$this->Cell(130, 3, $this->intesta4, 0, 0, 'L');
			}
			$im = imagecreatefromstring ( $this->logo );
			$ratio = round(imagesx($im)/imagesy($im),2);
			$x=60; $y=0;
			if ($ratio<1.71){ $x=0; $y=35; }

      //*+ DC - 16/01/2018
      //$this->Image('@' . $this->logo, 130, 5, $x, $y, '', $this->link);
      if ($this->layout_pos_logo_on_doc=='LEFT') {
        $this->Image('@' . $this->logo, 10, 7, $x, $y, '', '');
      } else {
        $this->Image('@' . $this->logo, 130, 5, $x, $y, '', $this->link);
      }
      //*- DC - 16/01/2018
      $this->Line(0, 93, 3, 93); //questa marca la linea d'aiuto per la piegatura del documento
      $this->Line(0, 143, 3, 143); //questa marca la linea d'aiuto per la foratura del documento
      $this->Ln($interlinea);
			if (!empty($this->efattura)){
				$this->SetFont('helvetica','B',9);
				$this->SetTextColor(255,0,0);
				$this->Cell(110,0,'Copia di cortesia del documento elettronico inviato al Sistema di Interscambio',0,1,'L',0,'',1);
				$this->SetTextColor(0,0,0);
			}
            $this->SetFont('helvetica', '', 10);
            $this->Cell(110, 5, $this->tipdoc, 1, 1, 'L', 1, '', 1);
            if ($this->tesdoc['tipdoc'] == 'NOP' || $this->withoutPageGroup) {
                $this->Cell(30, 5);
            } else {
                $this->Cell(30, 5, 'Pag. ' . $this->getGroupPageNo() . ' di ' . $this->getPageGroupAlias(), 0, 0, 'L');
            }
            $this->Ln(6);
            $interlinea = $this->GetY();
            $this->Ln(6);
            $this->SetFont('helvetica', '', 9);
            if (!empty($this->destinazione)) {
                if (is_array($this->destinazione)) { //quando si vuole indicare un titolo diverso da destinazione si deve passare un array con titolo index 0 e descrizione index 1
                    $this->Cell(80, 5, $this->destinazione[0], 'LTR', 2, 'L', 1);
                    $this->MultiCell(80, 4, $this->destinazione[1], 'LBR', 'L');
                } else {
                    $this->Cell(80, 5, "Destinazione :", 'LTR', 2, 'L', 1);
                    $this->MultiCell(80, 4, $this->destinazione, 'LBR', 'L');
                }
            }
			if ($this->codice_partner > 0){
				$this->SetXY(35, $interlinea - 5);
				$this->Cell(13, 4, $this->descri_partner, 'LT', 0, 'R', 1, '', 1);
				$this->Cell(72, 4, ': ' . $this->cliente5, 'TR', 1, 0, '', 1);
				$this->Cell(25);
				$this->Cell(20, 4, ' cod.: ' . $this->codice_partner, 'LB', 0, 'L');
				$to='';
				if (trim($this->cod_univoco)!=''){
					$to.=' Dest: '.$this->cod_univoco;
				}
				if (trim($this->pec_cliente)!=''){
					$to.=' Pec: '.$this->pec_cliente;
				}
				$this->Cell(65, 4,$to.' ' , 'BR', 0, 'L', 0, '', 1);
            }
			$this->SetXY(110, $interlinea + 6);
            $this->SetFont('helvetica', '', 10);
			if (!empty($this->cliente1)){ // Antonio Germani - se non c'è cliente evito di scrivere (serve per template scontrino)
            $this->Cell(15, 5, $this->pers_title.' ', 0, 0, 'R');
            $this->Cell(75, 5, $this->cliente1, 0, 1, 'L', 0, '', 1);
			} else {
				$this->Cell(15, 5,'', 0, 0, 'R');
				$this->Cell(75, 5,'', 0, 1, 'L', 0, '', 1);
			}
            if (!empty($this->cliente2)) {
                $this->Cell(115);
                $this->Cell(75, 5, $this->cliente2, 0, 1, 'L', 0, '', 1);
            }
            $this->SetFont('helvetica', '', 10);
            $this->Cell(115);
            if (strlen($this->cliente3) > 50) {
                $this->SetFont('helvetica', '', 7);
                $this->Cell(75, 5, $this->cliente3, 0, 1, 'L', 0, '', 1);
                $this->SetFont('helvetica', '', 10);
            } else {
                $this->Cell(75, 5, $this->cliente3, 0, 1, 'L', 0, '', 1);
            }
            $this->Cell(115);
            $this->Cell(75, 5, $this->cliente4, 0, 1, 'L', 0, '', 1);
            $this->SetFont('helvetica', '', 7);
            if (!empty($this->c_Attenzione)) {
                $this->SetFont('helvetica', '', 10);
                $this->Cell(115, 8, 'alla C.A.', 0, 0, 'R');
                $this->Cell(75, 8, $this->c_Attenzione, 0, 1, 'L', 0, '', 1);
            }
            $this->SetFont('helvetica', '', 7);
            if ($this->fiscal_rapresentative) {
                $this->Cell(115, 8, 'Legale Rappresentante ', 0, 0, 'R');
                $this->Cell(75, 8, $this->fiscal_rapresentative['ragso1']." ".$this->fiscal_rapresentative['ragso2']." ".$this->fiscal_rapresentative['country'].$this->fiscal_rapresentative['pariva'], 0, 1, 'L', 0, '', 1);
            } elseif (!empty($this->clientSedeLegale)) {
                $this->Cell(115, 8, 'Sede legale: ', 0, 0, 'R');
                $this->Cell(75, 8, $this->clientSedeLegale, 0, 1, 'L', 0, '', 1);
            } else {
                $this->Ln(4);
            }
        }
    }

}

?>
