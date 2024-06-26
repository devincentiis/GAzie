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
  scriva   alla   Free  Software Foundation,  Inc.,   59
  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
require('../../vendor/tecnickcom/tcpdf/tcpdf.php');

class Standard_template extends TCPDF {

    public function setVars($admin_aziend, $title = false, $luogo_data = 1, $n_page = true) {
        $this->title = $title;
        $this->logo = $admin_aziend['image'];
        $this->colore = $admin_aziend['colore'];
        if (!empty($admin_aziend['web_url'])) {
            $this->link = $admin_aziend['web_url'];
        } else {
            $this->link = '../config/admin_aziend.php';
        }
        $this->intesta1 = $admin_aziend['ragso1'] . ' ' . $admin_aziend['ragso2'];
        $this->intesta2 = $admin_aziend['indspe'] . ' ' . sprintf("%05d", $admin_aziend['capspe']) . ' ' . $admin_aziend['citspe'] . ' (' . $admin_aziend['prospe'] . ')';
        $this->intesta3 = 'Tel.' . $admin_aziend['telefo'] . ' C.F. ' . $admin_aziend['codfis'] . ' P.I. ' . $admin_aziend['pariva'];
        $this->intesta4 = $admin_aziend['e_mail'];
        if ($luogo_data === 1) { // se viene passato a 1 stampo luogo_data di systema
            $this->luogo = $admin_aziend['citspe'] . ", lì " . date("d ") . ucfirst(strftime("%B", mktime(0, 0, 0, date("m")))) . date(" Y");
        } elseif (!empty($luogo_data)) {  // opp. uso quello passato
            $this->luogo = $luogo_data;
        } else {  // altrimenti non lo stampo
            $this->luogo = '';
        }
        $this->SetCreator('GAzie' . $this->intesta1);
        $this->SetTitle($this->title);
        $this->SetAuthor($this->intesta4);
        $this->SetHeaderMargin(7);
        $this->SetTopMargin(44);
        $this->SetFooterMargin(23);
        if ($n_page) {
            $this->StartPageGroup();
            $this->n_page = $n_page;
        }
        $this->SetFillColor(hexdec(substr($this->colore, 0, 2)), hexdec(substr($this->colore, 2, 2)), hexdec(substr($this->colore, 4, 2)));
    }

    public function setCover($cover_data = false) {
        $this->cover = $cover_data;
    }

    public function setNpage($page_data) {
        $this->n_page = $page_data;
    }

    public function setTopBar($top_bar = false) {
        $this->top_bar = $top_bar;
    }

    public function setTopCarryBar($top_carry_bar = false) {
        $this->top_carry_bar = $top_carry_bar;
    }

    public function setBotCarryBar($bot_carry_bar = false) {
        $this->bot_carry_bar = $bot_carry_bar;
    }

    public function Header() {
        $this->SetFillColor(hexdec(substr($this->colore, 0, 2)), hexdec(substr($this->colore, 2, 2)), hexdec(substr($this->colore, 4, 2)));
        if (isset($this->cover)) { // se viene passata la copertina
            $this->Image('@' . $this->logo, 80, 80, 40, 0, '', $this->link);
            $this->SetFont('helvetica', '', 18);
            $this->SetXY(10, 130);
            $this->Cell(190, 6, $this->intesta1, 0, 2, 'C', 0, '', 1);
            $this->SetFont('helvetica', '', 12);
            $this->Cell(190, 6, $this->intesta2, 0, 2, 'C');
            $this->Cell(190, 6, $this->intesta3, 0, 2, 'C');
            $this->SetXY(55, 160);
            $this->SetFont('helvetica', '', 30);
            $this->MultiCell(100, 16, $this->cover, 1, 'C', 1);
        } else {
			$im = imagecreatefromstring ( $this->logo );
			$ratio = round(imagesx($im)/imagesy($im),2);
			$x=34; $y=0;
			if ($ratio<1.26){ $x=0; $y=27; }
            $this->Image('@' . $this->logo, 10, 10, $x, $y, '', $this->link);
            $this->Cell(40, 4);
            $this->SetFont('times', 'B', 10);
            $this->Cell(123, 5, $this->intesta1, 0, 0, 'L', 0, '', 1);
            $this->SetFont('helvetica', '', 9);
            if ($this->n_page === 1) { // se viene passato a 1 stampo luogo_data di systema
                $this->Cell(28, 5, 'Pag. ' . $this->getGroupPageNo() . ' di ' . $this->getPageGroupAlias(), 0, 1, 'R', 0, '', 1);
            } elseif (is_array($this->n_page) and isset($this->n_page['year'])) {  // se � un array contenente l'anno
                $page = $this->getGroupPageNo() + $this->n_page['ini_page'] - 1;
                $this->Cell(28, 5, $this->n_page['year'] . '/' . $page, 0, 1, 'R', 0, '', 1);
            } elseif (is_string($this->n_page)) {  // opp. uso quello passato
                $this->Cell(28, 5, $this->n_page, 0, 1, 'R', 0, '', 1);
            } else {  // altrimenti non lo stampo
                $this->Cell(28, 5, '', 0, 1, 'R');
            }
            $this->Cell(40, 4);
            $this->Cell(130, 4, $this->intesta2, 0, 2, 'L');
            $this->Cell(130, 4, $this->intesta3, 0, 2, 'L');
            $this->Cell(118, 4, $this->intesta4, 0, 0, 'L');
            $this->Cell(38, 4, $this->luogo, 0, 1, 'R');
            if (isset($this->title)) {
                $this->Ln(4);
                $this->Cell(40, 4);
                $this->SetFont('helvetica', 'B', 12);
                $this->Cell(150, 5, $this->title, 1, 1, 'L', 1);
            } else {
                $this->Cell(1, 5, '', 0, 1);
            }
            $this->Ln(1);
            if (isset($this->top_bar)) {
                if (is_array($this->top_bar)) { // se viene passato l'array della barra di testa
                    if (isset($this->top_bar[0]['font'])) {
                        $this->SetFont('helvetica', '', intval($this->top_bar[0]['font']));
                    } else {
                        $this->SetFont('helvetica', '', 10);
                    }
                    foreach ($this->top_bar as $value) {
                        $this->Cell($value['lenght'], 5, $value['name'], $value['frame'], 0, 'R', $value['fill']);
                    }
                    $this->Cell(1, 5, '', 0, 1);
                } elseif (is_string($this->top_bar)) {  // opp. uso quello passato
                    $this->Cell(186, 5, $this->top_carry_bar, 0, 1, 'C');
                }
            } else {
                $this->Ln(5);
            }
            if (isset($this->top_carry_bar)) {
                if (is_array($this->top_carry_bar)) { // se viene passato l'array della barra di riporto
                    if (isset($this->top_carry_bar[0]['font'])) {
                        $this->SetFont('helvetica', '', intval($this->top_carry_bar[0]['font']));
                    } else {
                        $this->SetFont('helvetica', '', 10);
                    }
                    foreach ($this->top_carry_bar as $value) {
                        $this->Cell($value['lenght'], 5, $value['name'], $value['frame'], 0, 'R', $value['fill']);
                    }
                    $this->Cell(1, 5, '', 0, 1);
                } elseif (is_string($this->top_carry_bar)) {  // opp. uso quello passato
                    $this->Cell(186, 5, $this->top_carry_bar, 0, 1, 'C');
                }
            }
        }
    }

    public function Footer() {
        $this->SetFillColor(hexdec(substr($this->colore, 0, 2)), hexdec(substr($this->colore, 2, 2)), hexdec(substr($this->colore, 4, 2)));
        if (isset($this->cover)) { // se viene passata la copertina
            unset($this->cover);
        } else {
            if (isset($this->bot_carry_bar)) {
                if (is_array($this->bot_carry_bar)) { // se viene passato l'array della barra di riporto
                    if (isset($this->bot_carry_bar[0]['font'])) {
                        $this->SetFont('helvetica', '', intval($this->bot_carry_bar[0]['font']));
                    } else {
                        $this->SetFont('helvetica', '', 10);
                    }
                    foreach ($this->bot_carry_bar as $value) {
                        $this->Cell($value['lenght'], 5, $value['name'], $value['frame'], 0, 'R', $value['fill']);
                    }
                    $this->Cell(1, 5, '', 0, 1);
                } elseif (is_string($this->bot_carry_bar)) {  // opp. uso quello passato
                    $this->Cell(186, 5, $this->bot_carry_bar, 0, 1, 'C');
                }
            } else {
                $this->Ln(4);
            }
            $this->SetFont('helvetica', '', 8);
            $this->MultiCell(190, 4, $this->intesta1 . ' ' . $this->intesta2 . ' ' . $this->intesta3 . ' ' . $this->intesta4 . ' ', 0, 'C', 0);
        }
    }

}

?>