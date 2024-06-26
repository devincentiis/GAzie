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

class Certificate extends Template {

    function setTesDoc() {
        $this->tesdoc = $this->docVars->tesdoc;
        $this->giorno = substr($this->tesdoc['datemi'], 8, 2);
        $this->mese = substr($this->tesdoc['datemi'], 5, 2);
        $this->anno = substr($this->tesdoc['datemi'], 0, 4);
        if ($this->tesdoc['tipdoc'] == 'FAD' || substr($this->tesdoc['tipdoc'], 0, 2) == 'DD') {
            $this->descridoc = ' D.d.T. n.';
        } elseif ($this->tesdoc['tipdoc'] == 'VCO' && $this->tesdoc['numfat'] > 0) {
            $this->descridoc = ' Receipt n.' . $this->tesdoc['numdoc'];
            $this->tesdoc['numdoc'] = $this->tesdoc['numfat'];
            $this->descridoc .= ' with attached invoice n.';
        } elseif ($this->tesdoc['tipdoc'] == 'VCO') {
            $this->descridoc = ' Receipt n.';
            $this->cliente1 = 'Anonymous customer';
        } else {
            $this->descridoc = ' Invoice n.';
        }
        $this->tipdoc = "Documents, certificates of origin, declarations of performance";
        $this->destinazione = array(' The products have been sold with: ', $this->descridoc . $this->tesdoc['numdoc'] . '/' . $this->tesdoc['seziva'] . ' del ' . $this->giorno . '-' . $this->mese . '-' . $this->anno);
    }

    function newPage() {
        $this->AddPage();
        $this->SetFillColor(hexdec(substr($this->colore, 0, 2)), hexdec(substr($this->colore, 2, 2)), hexdec(substr($this->colore, 4, 2)));
        $this->MultiCell(0, 8, 'In thanking you for the trust placed in us with your purchase, we attach here the original documents relating to the products listed below:', 0, 'L', 0, 1);
        $this->Ln(6);
        $this->SetFont('helvetica', '', 9);
        $this->Cell(100, 6, 'Codice - Descrizione del materiale', 1, 0, 'L', 1);
        $this->Cell(10, 6, 'U.m.', 1, 0, 'C', 1);
        $this->Cell(15, 6, 'Quantità', 1, 0, 'R', 1);
        $this->Cell(9, 6, 'ID', 1, 0, 'C', 1);
        $this->Cell(25, 6, 'Lotto-N.Serie', 1, 0, 'C', 1);
        $this->Cell(27, 6, 'Cod.Fornitore', 1, 1, 'R', 1, '', 1);
    }

    function pageHeader() {
        $this->StartPageGroup();
        $this->newPage();
        $this->noDocs = true;
        $this->atLeastOneWithoutDoc = false;
        $this->lines = $this->docVars->getLots();
        $this->SetFillColor(255, 150, 150);
		foreach ($this->lines AS $key => $rigo) {
            $fill = 0;
            if (!empty($rigo['ext'])) {
                $this->noDocs = false;
            } else {
                $this->atLeastOneWithoutDoc = true;
                $fill = 1;
            }
            if ($this->GetY() >= 215) {
                $this->Cell(155, 6, '', 'T', 1);
                $this->SetFont('helvetica', '', 20);
                $this->SetY(225);
                $this->Cell(185, 12, '>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>> ', 1, 1, 'R');
                $this->SetFont('helvetica', '', 9);
                $this->newPage();
                $this->Cell(185, 5, '<<< --- SEGUE DA PAGINA PRECEDENTE --- <<< ', 0, 1);
            }
            $this->Cell(100, 6, $rigo['codart'] . '-' . $rigo['descri'], 1, 0, 'L', $fill, '', 1);
            $this->Cell(10, 6, $rigo['unimis'], 1, 0, 'C', $fill);
            $this->Cell(15, 6, gaz_format_quantity($rigo['quanti'], 1, $this->decimal_quantity), 1, 0, 'R', $fill);
            $this->Cell(9, 6, $rigo['id_lotmag'], 1, 0, 'C', $fill, '', 1);
            $this->Cell(25, 6, $rigo['identifier'], 1, 0, 'C', $fill, '', 1);
            $this->Cell(27, 6, $rigo['supplier'], 1, 1, 'C', $fill);
        }
        if ($this->atLeastOneWithoutDoc) {
            $this->SetFont('helvetica', '', 10);
            $this->Ln(6);
            $this->Cell(6, 6, '', 1, 0, 'L', 1);
            $this->Cell(178, 6, "<- Lotti senza documenti allegati", 0, 1);
        }
        $this->SetFillColor(hexdec(substr($this->colore, 0, 2)), hexdec(substr($this->colore, 2, 2)), hexdec(substr($this->colore, 4, 2)));
    }

    function compose() {
        $this->print_header = false;
        reset($this->lines);
		foreach ($this->lines AS $key => $rigo) {
            $this->SetTextColor(255, 50, 50);
            if ($this->noDocs) {
                $this->SetFont('helvetica', '', 16);
                $this->Ln(10);
                $this->Cell(186, 5, "NON SONO STATI TROVATI DOCUMENTI ALLEGATI AI LOTTI", 0, 1, 'C');
            } else {
                $this->SetFont('helvetica', '', 6);
                if ($rigo['ext'] == 'pdf') {
                    $this->numPages = $this->setSourceFile( DATA_DIR . 'files/' . $rigo['file'] );
                    if ($this->numPages >= 1) {
                        for ($i = 1; $i <= $this->numPages; $i++) {
                            $this->_tplIdx = $this->importPage($i);
                            $specs = $this->getTemplateSize($this->_tplIdx);
							// stabilisco se portrait-landscape
							if ($specs['h'] > $specs['w']){ //portrait
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
                            $this->Cell(190, 3, $this->intesta1 . ' ' . $this->intesta1bis . " - COPIA CONFORME ALL'ORIGINALE - da " . $this->descridoc . $this->tesdoc['numdoc'] . '/' . $this->tesdoc['seziva'] . ' del ' . $this->giorno . '-' . $this->mese . '-' . $this->anno . ' Lotto: ' . $rigo['identifier'] . ' ( Pagina ' . $this->getGroupPageNo() . ' di ' . $this->getPageGroupAlias() . ' )', 1, 0, 'C', 0, '', 1);
                            $this->print_footer = false;
                        }
                    }
                } elseif (!empty($rigo['ext'])) {
                    list($w, $h) = getimagesize( DATA_DIR . 'files/' . $rigo['file'] );
                    if ($w > $h) { //landscape
                        $this->AddPage('L');
                        $this->SetXY(10, 0);
                        $this->Cell(280, 3, $this->intesta1 . ' ' . $this->intesta1bis . " - COPIA CONFORME ALL'ORIGINALE - da " . $this->descridoc . $this->tesdoc['numdoc'] . '/' . $this->tesdoc['seziva'] . ' del ' . $this->giorno . '-' . $this->mese . '-' . $this->anno . ' Lotto: ' . $rigo['identifier'] . ' ( Pagina ' . $this->getGroupPageNo() . ' di ' . $this->getPageGroupAlias() . ' )', 0, 1, 'C', 1, '', 1);
                        $this->image( DATA_DIR . 'files/' . $rigo['file'], 5, 3, 290 );
                    } else { // portrait
                        $this->AddPage('P');
                        $this->SetXY(10, 0);
                        $this->Cell(190, 3, $this->intesta1 . ' ' . $this->intesta1bis . " - COPIA CONFORME ALL'ORIGINALE - da " . $this->descridoc . $this->tesdoc['numdoc'] . '/' . $this->tesdoc['seziva'] . ' del ' . $this->giorno . '-' . $this->mese . '-' . $this->anno . ' Lotto: ' . $rigo['identifier'] . ' ( Pagina ' . $this->getGroupPageNo() . ' di ' . $this->getPageGroupAlias() . ' )', 0, 1, 'C', 1, '', 1);
                        $this->image( DATA_DIR . 'files/' . $rigo['file'], 5, 3, 200 );
                    }
                    $this->print_footer = false;
                }
            }
        }
    }

    function Footer() {
        //Page footer
        $this->SetY(-25);
        $this->Line(10, 270, 197, 270);
        $this->SetFont('helvetica', '', 8);
        $this->MultiCell(186, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4 . ' ', 0, 'C', 0);
    }

}

?>