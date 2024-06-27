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

require("../../library/include/calsca.inc.php");
require('template.php');

class Etichette extends Template {

   public function __construct() {
      parent::__construct();
      $dimPage = array(184, 62);
      TCPDF::setPageFormat($dimPage, "L");
   }

   function setTesDoc() {
      $this->tesdoc = $this->docVars->tesdoc;
      $this->tipdoc = "etichetta";
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
//      $this->SetFillColor(hexdec(substr($this->colore, 0, 2)), hexdec(substr($this->colore, 2, 2)), hexdec(substr($this->colore, 4, 2)));
//      $this->newPage();
   }

   function compose() {
      $this->setTesDoc();
      $this->body();
   }

   function body() {
      $largEtichetta = 130;
      $offsetX = 45;
      $numColli = max(1,$this->tesdoc['units']);   // stampo una etichetta per ogni collo, ma almeno una
      for ($k = 1; $k <= $numColli; $k++) {  //per il numero di colli
         $this->newPage();
         $this->SetY(13);
      $this->SetFont('helvetica', '', 8);
      $this->SetX($offsetX);
      $this->Cell($largEtichetta, 0, 'MITTENTE', 0, 1, 'L', 0, '', 1);
      $this->SetX($offsetX);
      $this->Cell($largEtichetta, 0, $this->intesta1, 0, 1, 'L', 0, '', 1);      
      $this->SetX($offsetX);
$this->Cell($largEtichetta, 0, $this->intesta2 . " " . $this->intesta3/* ." ".$this->intesta4 */, 0,1,'L',0,'', 1);

      }
   }

   function pageFooter() {
//      $rigaFooter = 20;
//      $this->SetY($rigaFooter);
//      $this->Cell(62, 6, 'Piede', 'LTR', 0, 'C', 1);
   }

   function Footer() {
//Document footer
      $this->SetY(-35);
        $this->SetFont('helvetica', '', 16);
        $this->Cell((isset($largEtichetta))?$largEtichetta:'', 0, "---------------------------------------------------------------------------", 0, 1, 'L', 0, '', 1);
         $this->Cell((isset($largEtichetta))?$largEtichetta:'', 0, 'DESTINATARIO', 0, 10, 'L', 0, '', 1);
         $this->SetFont('helvetica', '', 14);
         $this->Cell((isset($largEtichetta))?$largEtichetta:'', 0, $this->cliente1 . " " . $this->cliente2, 0, 1, 'L', 0, '', 1);
         if ($this->tesdoc['destin'] != "")
         	{
         	 $this->Cell((isset($largEtichetta))?$largEtichetta:'', 0, $this->tesdoc['destin'], 0, 1, 'L', 0, '', 1);
         	}else{
		     $this->Cell((isset($largEtichetta))?$largEtichetta:'', 0, $this->cliente3 . " " . $this->cliente4, 0, 1, 'L', 0, '', 1);
        	}
   }

   function Header() {
      $this->Image('@' . $this->logo, '', '', 30, 0);
      $this->Cell(0, 0, '' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
   }

}

?>