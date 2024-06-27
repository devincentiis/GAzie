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
  scriva   alla   Free  Software Foundation,  Inc.,   59
  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
  --------------------------------------------------------------------------
 */



class Report_template extends TCPDF {

    function setVars($admin_aziend, $altri_dati = '') {
      $this->logo = $admin_aziend['image'];
      if (!empty($admin_aziend['web_url'])) {
          $this->link = $admin_aziend['web_url'];
      } else {
          $this->link = '../config/admin_aziend.php';
      }
      $this->colore = $admin_aziend['colore'];
      $this->intesta1 = $admin_aziend['ragso1'] . ' ' . $admin_aziend['ragso2'];
      $this->intesta2 = $admin_aziend['indspe'] . ' ' . sprintf("%05d", $admin_aziend['capspe']) . ' ' . $admin_aziend['citspe'] . ' (' . $admin_aziend['prospe'] . ')';
      $this->intesta3 = 'Tel.' . $admin_aziend['telefo'] . ' C.F.:' . $admin_aziend['codfis'] . ' P.I.:' . $admin_aziend['pariva'];
      $this->intesta4 = $admin_aziend['e_mail'];
      if (isset($altri_dati['luogo_data'])) { // se viene passata il valore di luogo_data
        $this->luogo = $altri_dati['luogo_data'];
      } else {  // altrimenti uso quello di default
        $this->docVars->gazTimeFormatter->setPattern('dd MMMM yyyy');
        $this->luogo = $admin_aziend['citspe'] . ", lì " . ucwords($this->docVars->gazTimeFormatter->format(new DateTime()));
      }
      $this->SetCreator('GAzie - ' . $this->intesta1);
      if (isset($altri_dati['title'])) { // se viene passato il titolo
          $this->SetTitle($altri_dati['title']);
      }
      $this->SetAuthor($this->intesta4);
      $this->SetHeaderMargin(7);
      $this->SetTopMargin(44);
      $this->SetFooterMargin(23);
      $this->StartPageGroup();
      $this->altri_dati = $altri_dati;
    }

    function setRiporti($intesta_riporti = '') {
        $this->intesta_riporti = $intesta_riporti;
    }

    function setFreeFooter($free_footer = '') {
        $this->free_footer = $free_footer;
    }

    function setFreeHeader($free_header = '') {
        $this->free_header = $free_header;
    }

    function setPageTitle($intesta_title = '') {
        $this->altri_dati['title'] = $intesta_title;
    }

    function setItemGroup($intesta_item_group = '', $item_image = '', $item_link = '') {
        $this->item_image = $item_image;
        $this->item_link = $item_link;
        $this->intesta_item_group = $intesta_item_group;
    }

    function Header() {
        $this->SetFillColor(hexdec(substr($this->colore, 0, 2)), hexdec(substr($this->colore, 2, 2)), hexdec(substr($this->colore, 4, 2)));
        if (isset($this->altri_dati['cover']) and ! empty($this->altri_dati['cover'])) { // � stato passato il valore di pagina da stampare
            $this->descri_cover = $this->altri_dati['cover'];
            $this->printCover();
        } else {
            $this->SetFont('helvetica', '', 9);
            $this->Image('@' . $this->logo, 15, 5, 30, 0, '', $this->link);
            $this->Cell(40, 4);
            $this->Cell(118, 4, $this->intesta1, 0, 0, 'L',0,'',1);
            if (isset($this->altri_dati['page'])) { // � stato passato il valore di pagina da stampare
                $this->Cell(30, 4, $this->altri_dati['page'] . $this->GroupPageNo(), 0, 1, 'R');
            } else {
                $this->Cell(30, 4, 'Pagina ' . $this->getGroupPageNo() . ' di ' . $this->getPageGroupAlias(), 0, 1, 'R');
            }
            $this->Cell(40, 4);
            $this->Cell(130, 4, $this->intesta2, 0, 2, 'L');
            $this->Cell(130, 4, $this->intesta3, 0, 2, 'L');
            $this->Cell(118, 4, $this->intesta4, 0, 0, 'L');
            $this->Cell(30, 4, $this->luogo, 0, 1, 'R');
            if (!empty($this->item_image)) { //C'� una immagine associata
                $this->Image('@' . $this->item_image, 177, 28, 0, 20, '', $this->item_link);
                $this->Ln(4);
            }
            if (isset($this->intesta_item_group) and is_array($this->intesta_item_group)) { // c'� una descrizione dell'articolo
                $this->SetFont('helvetica', '', 9);
                $this->Cell(40);
                foreach ($this->intesta_item_group['top'] as $key => $value) {
                    $this->Cell($value['lun'], 4, $value['nam'], 1, 0, 'C', 1);
                }
                $this->Cell(1, 4, '', 0, 1);
                $this->Cell(40);
                foreach ($this->intesta_item_group['bot'] as $key => $value) {
                    $this->Cell($value['lun'], 4, $value['nam'], 1, 0, 'C');
                }
                $this->Cell(1, 4, '', 0, 1);
                $this->SetFont('helvetica', '', 8);
            }
            if (is_array($this->altri_dati) and isset($this->altri_dati['title'])) { // � una intestazione con titolo e testata tabella
                $this->Cell(40);
                $this->SetFont('helvetica', '', 12);
                $this->Cell(150, 15, $this->altri_dati['title'], 0, 1, 'L', 0, '', 1);
                $this->SetFont('helvetica', '', 9);
                foreach ($this->altri_dati['hile'] as $key => $value) {
//                 $this->Cell($value['lun'],4,$value['nam'],1,0,'C',1);
                    $this->MultiCell($value['lun'], 4, $value['nam'], 1, 'C', 1, 0); // per avere intestazioni su più righe
                }
                $this->Cell(1, 4, '', 0, 1);
                $this->SetFont('helvetica', '', 8);
            } elseif (is_string($this->altri_dati) and ! empty($this->altri_dati)) {  //solo con titolo
                $this->Cell(40);
                $this->SetFont('helvetica', '', 12);
                $this->Cell(130, 12, $this->altri_dati, 0, 1);
                $this->SetFont('helvetica', '', 8);
            }
            if (isset($this->intesta_riporti) and is_array($this->intesta_riporti)) { // c'� un riporto da pagina precedente
                $this->SetFont('helvetica', 'B', 8);
                foreach ($this->intesta_riporti['top'] as $key => $value) {
                    $this->Cell($value['lun'], 4, $value['nam'], 1, 0, 'R');
                }
                $this->Cell(1, 4, '', 0, 1);
                $this->SetFont('helvetica', '', 8);
            }
            if (isset($this->free_header) and is_array($this->free_header)) {
                foreach ($this->free_header as $value) {
                    $this->Cell($value['lun'], 4, $value['nam'], $value['con'], 0, $value['ali'], $value['fil']);
                }
            }
        }
    }

    function Footer() {
        $this->SetFillColor(hexdec(substr($this->colore, 0, 2)), hexdec(substr($this->colore, 2, 2)), hexdec(substr($this->colore, 4, 2)));
        if (isset($this->altri_dati['cover']) and ! empty($this->altri_dati['cover'])) { // � stato passato il valore di pagina da stampare
            $this->altri_dati['cover'] = '';
        } else {
            //Page footer
            if (isset($this->intesta_riporti) and is_array($this->intesta_riporti)) { // c'� un riporto da pagina precedente
                $this->SetFont('helvetica', 'B', 8);
                foreach ($this->intesta_riporti['bot'] as $key => $value) {
                    $this->Cell($value['lun'], 4, $value['nam'], 1, 0, 'R');
                }
                $this->Cell(1, 4, '', 0, 1);
                if (isset($this->free_footer) and is_array($this->free_footer)) {
                    foreach ($this->free_footer as $value) {
                        $this->Cell($value['lun'], 4, $value['nam'], $value['con'], 0, $value['ali'], $value['fil']);
                    }
                }
            }
            $this->SetFont('helvetica', '', 8);
            $this->MultiCell($this->getPageWidth() - 15, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4, 0, 'C');
        }
    }

    function printCover() {
        $this->Image('@' . $this->logo, 80, 80, 40, 0, '', $this->link);
        $this->SetFont('helvetica', '', 18);
        $this->SetXY(10, 130);
        $this->Cell(190, 6, $this->intesta1, 0, 2, 'C');
        $this->SetFont('helvetica', '', 12);
        $this->Cell(190, 6, $this->intesta2, 0, 2, 'C');
        $this->Cell(190, 6, $this->intesta3, 0, 2, 'C');
        $this->SetXY(55, 160);
        $this->SetFont('helvetica', '', 30);
        $this->MultiCell(100, 16, $this->descri_cover, 1, 'C', 1);
        $this->AddPage();
    }

    // INIZIO funzioni per codice a barre by Olivier Platey
    function EAN13($x, $y, $barcode, $h = 16, $w = .35) {
        $this->Barcode($x, $y, $barcode, $h, $w, 13);
    }

    function UPC_A($x, $y, $barcode, $h = 16, $w = .35) {
        $this->Barcode($x, $y, $barcode, $h, $w, 12);
    }

    function GetCheckDigit($barcode) {
        //Compute the check digit
        $sum = 0;
        for ($i = 1; $i <= 11; $i+=2)
            $sum+=3 * $barcode[$i];
        for ($i = 0; $i <= 10; $i+=2)
            $sum+=$barcode[$i];
        $r = $sum % 10;
        if ($r > 0)
            $r = 10 - $r;
        return $r;
    }

    function TestCheckDigit($barcode) {
        //Test validity of check digit
        $sum = 0;
        for ($i = 1; $i <= 11; $i+=2)
            $sum+=3 * $barcode[$i];
        for ($i = 0; $i <= 10; $i+=2)
            $sum+=$barcode[$i];
        return ($sum + $barcode[12]) % 10 == 0;
    }

    function Barcode($x, $y, $barcode, $h, $w, $len) {
        global $admin_aziend;
        //Padding
        $barcode = str_pad($barcode, $len - 1, '0', STR_PAD_LEFT);
        if ($len == 12)
            $barcode = '0' . $barcode;
        //Add or control the check digit
        if (strlen($barcode) == 12)
            $barcode.=$this->GetCheckDigit($barcode);
        elseif (!$this->TestCheckDigit($barcode))
            $this->Error('Incorrect check digit');
        //Convert digits to bars
        $codes = array(
            'A' => array(
                '0' => '0001101', '1' => '0011001', '2' => '0010011', '3' => '0111101', '4' => '0100011',
                '5' => '0110001', '6' => '0101111', '7' => '0111011', '8' => '0110111', '9' => '0001011'),
            'B' => array(
                '0' => '0100111', '1' => '0110011', '2' => '0011011', '3' => '0100001', '4' => '0011101',
                '5' => '0111001', '6' => '0000101', '7' => '0010001', '8' => '0001001', '9' => '0010111'),
            'C' => array(
                '0' => '1110010', '1' => '1100110', '2' => '1101100', '3' => '1000010', '4' => '1011100',
                '5' => '1001110', '6' => '1010000', '7' => '1000100', '8' => '1001000', '9' => '1110100')
        );
        $parities = array(
            '0' => array('A', 'A', 'A', 'A', 'A', 'A'),
            '1' => array('A', 'A', 'B', 'A', 'B', 'B'),
            '2' => array('A', 'A', 'B', 'B', 'A', 'B'),
            '3' => array('A', 'A', 'B', 'B', 'B', 'A'),
            '4' => array('A', 'B', 'A', 'A', 'B', 'B'),
            '5' => array('A', 'B', 'B', 'A', 'A', 'B'),
            '6' => array('A', 'B', 'B', 'B', 'A', 'A'),
            '7' => array('A', 'B', 'A', 'B', 'A', 'B'),
            '8' => array('A', 'B', 'A', 'B', 'B', 'A'),
            '9' => array('A', 'B', 'B', 'A', 'B', 'A')
        );
        $code = '101';
        $p = $parities[$barcode[0]];
        for ($i = 1; $i <= 6; $i++)
            $code.=$codes[$p[$i - 1]][$barcode[$i]];
        $code.='01010';
        for ($i = 7; $i <= 12; $i++)
            $code.=$codes['C'][$barcode[$i]];
        $code.='101';
        $this->SetFillColor(0);
        //Draw bars
        for ($i = 0; $i < strlen($code); $i++) {
            if ($code[$i] == '1')
                $this->Rect($x + $i * $w, $y, $w, $h, 'F');
        }
        //Print text uder barcode
        $this->Text($x + 5, $y + $h + 11 / $this->k, substr($barcode, -$len));
        $this->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
    }

    // FINE funzioni per codice a barre by Olivier Platey
}

?>
