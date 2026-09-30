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
class Template_2xA5 extends Fpdi {

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
        $this->colore = $docVars->colore;
        $this->decimal_quantity = $docVars->decimal_quantity;
        $this->decimal_price = $docVars->decimal_price;
        $this->perbollo = $docVars->perbollo;
        $this->codice_partner = $docVars->codice_partner;
        $this->descri_partner = $docVars->descri_partner;
        $this->cod_univoco = $docVars->cod_univoco;
        $this->cliente1 = $docVars->cliente1;
        $this->cliente2 = $docVars->cliente2;
        $this->cliente3 = $docVars->cliente3;
        $this->cliente4 = $docVars->cliente4;  // CAP, Città, Provincia
        $this->cliente4b = $docVars->cliente4b; // Nazione
        $this->cliente5 = $docVars->cliente5;  // P.IVA e C.F.
        $this->agente = $docVars->name_agente;
        $this->destinazione = $docVars->destinazione;
        $this->clientSedeLegale = '';
        if (!empty($docVars->clientSedeLegale)) {
            foreach ($docVars->clientSedeLegale as $value) {
                $this->clientSedeLegale .= $value . ' ';
            }
        }
        $this->c_Attenzione = $docVars->c_Attenzione;
        $this->min = $docVars->min;
        $this->ora = $docVars->ora;
        $this->day = $docVars->day;
        $this->month = $docVars->month;
        $this->year = $docVars->year;
        $this->withoutPageGroup = $docVars->withoutPageGroup;
    }

    function Header() {
            $this->SetFillColor(hexdec(substr($this->colore, 0, 2)), hexdec(substr($this->colore, 2, 2)), hexdec(substr($this->colore, 4, 2)));
			// INTESTAZIONE 1
            $this->SetFont('times', 'B', 11);
            $this->Cell(85, 6, $this->intesta1, 0, 0, 'L');
			$this->Cell(65);
            $this->Cell(85, 6, $this->intesta1, 0, 1, 'L');
            $this->SetFont('helvetica', '', 7);
            $interlinea = 14;
            if (!empty($this->intesta1bis)) {
                $this->Cell(85, 4, $this->intesta1bis, 0, 0, 'L');
				$this->Cell(65);
                $this->Cell(85, 4, $this->intesta1bis, 0, 1, 'L');
                $interlinea = 10;
            }
            $this->Cell(85, 4, $this->intesta2, 0, 0, 'L');
			$this->Cell(65);
            $this->Cell(85, 4, $this->intesta2, 0, 1, 'L');
            $this->Cell(85, 4, $this->intesta3, 0, 0, 'L');
			$this->Cell(65);
            $this->Cell(85, 4, $this->intesta3, 0, 1, 'L');
            $this->Cell(85, 4, $this->intesta4, 0, 0, 'L');
			$this->Cell(65);
            $this->Cell(85, 4, $this->intesta4, 0, 1, 'L');
            $this->Image('@' . $this->logo, 100, 5, 40, 0, '', $this->link);
            $this->Image('@' . $this->logo, 250, 5, 40, 0, '', $this->link);
            $this->Ln($interlinea);
            $this->SetFont('helvetica', '', 9);
            $this->Cell(85, 5, $this->tipdoc, 1, 0, 'L', 1);
			$this->Cell(65);
            $this->Cell(85, 5, $this->tipdoc, 1, 1, 'L', 1);
            if ($this->tesdoc['tipdoc'] == 'NOP' || $this->withoutPageGroup) {
                $this->Cell(170, 5);
            } else {
                $this->Cell(20, 5, 'Pag. ' . $this->getGroupPageNo() . ' di ' . $this->getPageGroupAlias(), 0, 0, 'L');
				$this->Cell(130);
                $this->Cell(20, 5, 'Pag. ' . $this->getGroupPageNo() . ' di ' . $this->getPageGroupAlias(), 0, 0, 'L');
            }
            $this->Ln(6);
            $interlinea = $this->GetY();
            $this->Ln(6);
            $this->SetFont('helvetica', '', 7);
            if (!empty($this->destinazione)) {
	            $start_destinazione = $this->GetY();
                if (is_array($this->destinazione)) { //quando si vuole indicare un titolo diverso da destinazione si deve passare un array con titolo index 0 e descrizione index 1
                    $this->Cell(60, 5, $this->destinazione[0], 'LTR', 0, 'L', 1);
					$this->Cell(90);
                    $this->Cell(60, 5, $this->destinazione[0], 'LTR', 1, 'L', 1);
                    $this->MultiCell(60, 4, $this->destinazione[1], 'LBR', 'L');
					$this->SetXY(160, $start_destinazione + 5);
                    $this->MultiCell(60, 4, $this->destinazione[1], 'LBR', 'L');
                } else {
                    $this->Cell(60, 5, "Destinazione :", 'LTR', 0, 'L', 1);
					$this->Cell(90);
                    $this->Cell(60, 5, "Destinazione :", 'LTR', 1, 'L', 1);
                    $this->MultiCell(60, 4, $this->destinazione, 'LBR', 'L');
					$this->SetXY(160, $start_destinazione + 5);
                    $this->MultiCell(60, 4, $this->destinazione, 'LBR', 'L');
                }
            }
			if ($this->codice_partner > 0){
				$this->SetXY(28, $interlinea - 5);
				$this->Cell(10, 4, $this->descri_partner, 'LT', 0, 'R', 1);
				$this->Cell(55, 4, ': ' . $this->cliente5, 'TR', 1);
				$this->Cell(18);
				$this->Cell(18, 4, ' cod.: ' . $this->codice_partner, 'LB', 0, 'L');
				$this->Cell(30, 4, ' cod.univoco: ' . $this->cod_univoco, 'RB', 0, 'L');
				$this->Cell(17, 4, '', 'T');
				$this->SetXY(178, $interlinea - 5);
				$this->Cell(10, 4, $this->descri_partner, 'LT', 0, 'R', 1);
				$this->Cell(55, 4, ': ' . $this->cliente5, 'TR', 1);
				$this->Cell(168);
				$this->Cell(18, 4, ' cod.: ' . $this->codice_partner, 'LB', 0, 'L');
				$this->Cell(30, 4, ' cod.univoco: ' . $this->cod_univoco, 'RB', 0, 'L');
				$this->Cell(17, 4, '', 'T');

			}
			$this->SetXY(75, $interlinea + 3);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(15, 5, 'Spett.le ', 0, 0, 'R');
            $this->Cell(55, 5, $this->cliente1, 0, 0, 'L', 0, '', 1);
			$this->Cell(77);
            $this->Cell(15, 5, 'Spett.le ', 0, 0, 'R');
            $this->Cell(55, 5, $this->cliente1, 0, 1, 'L', 0, '', 1);
            if (!empty($this->cliente2)) {
                $this->Cell(80);
                $this->Cell(55, 5, $this->cliente2, 0, 0, 'L', 0, '', 1);
                $this->Cell(92);
                $this->Cell(55, 5, $this->cliente2, 0, 1, 'L', 0, '', 1);
            }
            $this->SetFont('helvetica', '', 10);
            $this->Cell(80);
            $this->Cell(55, 5, $this->cliente3, 0, 0, 'L', 0, '', 1);
            $this->Cell(92);
            $this->Cell(55, 5, $this->cliente3, 0, 1, 'L', 0, '', 1);
            $this->Cell(80);
            $this->Cell(55, 5, $this->cliente4, 0, 0, 'L', 0, '', 1);
            $this->Cell(92);
            $this->Cell(55, 5, $this->cliente4, 0, 1, 'L', 0, '', 1);
            if (!empty($this->cliente4b)) {
                $this->Cell(80);
                $this->Cell(55, 5, $this->cliente4b, 0, 0, 'L', 0, '', 1);
				$this->Cell(92);
				$this->Cell(55, 5, $this->cliente4b, 0, 1, 'L', 0, '', 1);
            }
            $this->SetFont('helvetica', '', 7);
            if (!empty($this->c_Attenzione)) {
                $this->SetFont('helvetica', '', 10);
                $this->Cell(80, 8, 'alla C.A.', 0, 0, 'R');
                $this->Cell(55, 8, $this->c_Attenzione, 0, 1, 'L', 0, '', 1);
            }
            $this->SetFont('helvetica', '', 7);
            if (!empty($this->clientSedeLegale)) {
                $this->Cell(80, 8, 'Sede legale: ', 0, 0, 'R');
                $this->Cell(55, 8, $this->clientSedeLegale, 0, 0, 'L', 0, '', 1);
                $this->Cell(12);
                $this->Cell(80, 8, 'Sede legale: ', 0, 0, 'R');
                $this->Cell(55, 8, $this->clientSedeLegale, 0, 1, 'L', 0, '', 1);
            } else {
                $this->Ln(4);
            }
			$this->Line(149, 5, 149, 205, array('dash' => '5,5','width' => 0.2,'color' => array(150, 150, 150)));
    }

}

?>
