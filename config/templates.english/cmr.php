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

class Cmr extends Template {

   public function __construct() {
      parent::__construct();
   }

   function setTesDoc() {
      $this->tesdoc = $this->docVars->tesdoc;
      $this->tipdoc = "cmr";
   }

   function newPage() {
      $this->AddPage();
      $this->SetFont('helvetica', '', 9);
   }

   function SetTopMargin($dim) {
      parent::SetTopMargin(0);   // ignoro il margine
   }

   function pageHeader() {
        $this->StartPageGroup();
        // set margins
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->SetHeaderMargin(0);
        $this->SetFooterMargin(0);
        $this->setPrintFooter(false);
   }

   function compose() {
      $this->setTesDoc();
      $this->body();
   }

   function body() {
      $this->SetCreator(PDF_CREATOR);
      // set header and footer fonts
      $this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
      // set default monospaced font
      $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
      // set margins
      $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
      $this->SetHeaderMargin(0);
      $this->SetFooterMargin(0);
      // remove default footer
      $this->setPrintFooter(false);
      // set auto page breaks
      $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
      // set image scale factor
      $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
      // set font
      $this->SetFont('times', '', 12);
      // add a page
      $this->addPage();

      $offsetX = 6;
      $this->SetY(20);
      $this->SetX($offsetX);
      $this->Cell(70, 0, $this->intesta1.' '.$this->intesta1bis, 0, 1, 'L', 0, '', 1);
      $this->SetX($offsetX);
      $this->Cell(70, 0, $this->intesta2, 0, 1, 'L', 0, '', 1);
      $this->SetX($offsetX);
      $this->Cell(70, 0, 'Italy', 0, 1, 'L', 0, '', 1);

      $this->SetFont('times', '', 10);
      $this->SetY(53);
      $this->SetX($offsetX);
      $this->Cell(70, 0, $this->cliente1.' '.$this->cliente2, 0, 1, 'L', 0, '', 1);
      $this->SetX($offsetX);
      $this->Cell(70, 0, $this->cliente3.' - '.$this->cliente4 , 0, 1, 'L', 0, '', 1);
      $this->SetX($offsetX);
      $this->Cell(70, 0, $this->cliente4b, 0, 1, 'L', 0, '', 1);

      $this->SetY(77);
      $this->SetX($offsetX);
      $this->Cell(70, 0, $this->intesta2, 0, 1, 'L', 0, '', 1);
      $this->SetX($offsetX);
      $this->Cell(70, 0, 'Italy', 0, 1, 'L', 0, '', 1);

      $this->SetFont('times', '', 10);
      $this->SetY(99);
      $this->SetX($offsetX);
      $this->Cell(70, 0, $this->cliente3.' - '.$this->cliente4 , 0, 1, 'L', 0, '', 1);
      $this->SetX($offsetX);
      $this->Cell(70, 0, $this->cliente4b , 0, 1, 'L', 0, '', 1);

      $this->SetY(53);
      $this->SetX(96);
      $this->Cell(110,0,$this->docVars->vettor['ragione_sociale'],0,1,'L',0,'',1);
      $this->SetX(96);
      $this->Cell(110,0,$this->docVars->vettor['indirizzo'].' '.$this->docVars->vettor['citta'].' ('.$this->docVars->vettor['provincia'].')',0,1,'L',0,'',1);
      $this->SetX(96);
      $this->Cell(110,0,'',0,1,'L',0,'',1);



   }

   function pageFooter() {

   }

   function Footer() {

   }

   function Header() {
        // get the current page break margin
        $bMargin = $this->getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = $this->AutoPageBreak;
        // disable auto-page-break
        $this->SetAutoPageBreak(false, 0);
        // set bacground image
        $img_file = '../../config/templates/cmr.png';
        $this->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
        // restore auto-page-break status
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        $this->setPageMark();
   }

}








?>