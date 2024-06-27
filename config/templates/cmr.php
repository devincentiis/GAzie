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

   function SetPos( $x, $y ) {
        $this->SetY($y);
        $this->SetX($x);
      }

   function body() {
      $this->SetCreator(PDF_CREATOR);
      $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
      $this->SetHeaderMargin(0);
      $this->SetFooterMargin(0);
      // remove default footer
      $this->setPrintFooter(false);
      // set auto page breaks
      $this->SetAutoPageBreak(false, PDF_MARGIN_BOTTOM);
      // set image scale factor
      $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
      // set font
      $this->SetFont('helvetica', '', 10);
    
    $chsmall = 6;

    for ( $t=1; $t<=4; $t++ ) { 
      $this->addPage();  
      if ( $t==1 ) {
        $copia = "il mittente";
      } else if ( $t==2 ) {
        $copia = "il destinatario";
      } else if ( $t==3 ) {
        $copia = "il corriere";
      } else if ( $t==4 ) {
        $copia = "il ";
      } 
    
      $ox = 14;
      $oy = 15;
      
      // Disegno casella numero 1
      $this->SetPos($ox,$oy);
      $this->Cell(89,30,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(86, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 6,$oy);
      $this->Cell(86, 0, 'Mittente (nome, indirizzo, stato)', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 6,$oy+2);
      $this->Cell(86, 0, 'Expediteur (nom, adresse, pays)', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 6,$oy+4);
      $this->Cell(86, 0, 'Absender (name, anschrift, land)', 0, 1, 'L', 0, '', 1);
      
      $ox = 14;
      $oy = 45;

      // Disegno la casella numero 2
      $this->SetPos($ox,$oy);
      $this->Cell(89,30,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '2', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 6,$oy);
      $this->Cell(80, 0, 'Destinatario (nome, indirizzo, paese)', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 6,$oy+2);
      $this->Cell(80, 0, 'Destinataire (nom, adresse, pays)', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 6,$oy+4);
      $this->Cell(80, 0, 'Empfanger (name, anschrift, land)', 0, 1, 'L', 0, '', 1);

      $ox = 14;
      $oy = 75;

      // Disegno la casella numero 3
      $this->SetPos($ox,$oy);
      $this->Cell(89,17,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '3', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 6,$oy);
      $this->Cell(80, 0, 'Luogo previsto per la consegna della merce (località, stato)', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 6,$oy+2);
      $this->Cell(80, 0, 'Lieu prevu pour la livraison de la marchandise (lieu, pays)', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 6,$oy+4);
      $this->Cell(80, 0, 'Auslieferungsort del gutes (ort, land)', 0, 1, 'L', 0, '', 1);

      $ox = 14;
      $oy = 92;

      // Disegno la casella numero 4
      $this->SetPos($ox,$oy);
      $this->Cell(89,17,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '4', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 6,$oy);
      $this->Cell(80, 0, 'Luogo e data della presa in carico della merce', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 6,$oy+2);
      $this->Cell(80, 0, 'Lieu er date de la prise en charge de la marchandise (lieu, pays, date)', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 6,$oy+4);
      $this->Cell(80, 0, 'Ort und tag der Ubernahme des gutes (ort, land, datum)', 0, 1, 'L', 0, '', 1);

      $ox = 14;
      $oy = 109;

      // Disegno la casella numero 5
      $this->SetPos($ox,$oy);
      $this->Cell(89,17,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '5', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 6,$oy);
      $this->Cell(80, 0, 'Documenti allegati', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 6,$oy+2);
      $this->Cell(80, 0, 'Documents annexes', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 6,$oy+4);
      $this->Cell(80, 0, 'Beigefugte dokumente', 0, 1, 'L', 0, '', 1);

      $ox = 14;
      $oy = 126;

      // Disegno la casella numero 6
      $this->SetPos($ox,$oy);
      $this->Cell(112,57,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '6', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 5,$oy);
      $this->Cell(80, 0, 'Contrassegni e numeri', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 5,$oy+2);
      $this->Cell(80, 0, 'Marques et numeros', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 5,$oy+4);
      $this->Cell(80, 0, 'Kennzeichen und Nummern', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox+30,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '7', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 35,$oy);
      $this->Cell(80, 0, 'Numero dei colli', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 35,$oy+2);
      $this->Cell(80, 0, 'Nombre des colis', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 35,$oy+4);
      $this->Cell(80, 0, 'Anzahl der packsucke', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox+55,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '8', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 60,$oy);
      $this->Cell(80, 0, 'Imballaggio', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 60,$oy+2);
      $this->Cell(80, 0, 'Mode d\'emballage', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 60,$oy+4);
      $this->Cell(80, 0, 'Art der Verpackung', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox+78,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '9', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 83,$oy);
      $this->Cell(80, 0, 'Denominaz. della merce', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 83,$oy+2);
      $this->Cell(80, 0, 'Nature de la marchandise', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 83,$oy+4);
      $this->Cell(80, 0, 'Bezeichnung des gutes', 0, 1, 'L', 0, '', 1);

      $ox = 14;
      $oy = 183;

      // Disegno la casella numero pre13 
      $this->SetPos($ox,$oy);
      $this->Cell(112,6,'',1,0,'L');
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox,$oy+1);
      $this->Cell(80, 0, 'Classe', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+3);
      $this->Cell(80, 0, 'Classe/Klasse', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+29,$oy+1);
      $this->Cell(80, 0, 'Cifra', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+29,$oy+3);
      $this->Cell(80, 0, 'Chiffre/Ziffer', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+56,$oy+1);
      $this->Cell(80, 0, 'Lettera', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+56,$oy+3);
      $this->Cell(80, 0, 'Lettre/Buchstabe', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+86,$oy+1);
      $this->Cell(80, 0, '(ADR*)', 0, 1, 'L', 0, '', 1);

      $ox = 14;
      $oy = 189;

      // Disegno la casella numero 13
      $this->SetPos($ox,$oy);
      $this->Cell(89,44,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+4,$oy-2);
      $this->Cell(80, 0, '3', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 10,$oy);
      $this->Cell(80, 0, 'Istruzioni del mittente', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+2);
      $this->Cell(80, 0, 'Instruction de l\'expediteur', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+4);
      $this->Cell(80, 0, 'Anweisungen des absender', 0, 1, 'L', 0, '', 1);

      $ox = 14;
      $oy = 233;

      // Disegno la casella numero 14
      $this->SetPos($ox,$oy);
      $this->Cell(89,13,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+4,$oy-2);
      $this->Cell(80, 0, '4', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 10,$oy);
      $this->Cell(80, 0, 'Istruzioni per il pagamento del nolo', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+2);
      $this->Cell(80, 0, 'Prescriptions d\'affranchissement', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+4);
      $this->Cell(80, 0, 'Franchtzahlungsanweisungen', 0, 1, 'L', 0, '', 1);

      $ox = 14;
      $oy = 246;

      // Disegno la casella numero 21
      $this->SetPos($ox,$oy);
      $this->Cell(89,10,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '2', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+4,$oy-2);
      $this->Cell(80, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 10,$oy);
      $this->Cell(80, 0, 'Compilato a', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+2);
      $this->Cell(80, 0, 'Etablie a', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+4);
      $this->Cell(80, 0, 'Ausgefertigt in', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 50,$oy);
      $this->Cell(80, 0, 'il', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 50,$oy+2);
      $this->Cell(80, 0, 'le', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 50,$oy+4);
      $this->Cell(80, 0, 'am', 0, 1, 'L', 0, '', 1);

      $ox = 14;
      $oy = 256;

      // Disegno la casella numero 22
      $this->SetPos($ox,$oy);
      $this->Cell(60,34,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '2', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+4,$oy-2);
      $this->Cell(80, 0, '2', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox,$oy+26);
      $this->Cell(80, 0, 'Firma e timbro del mittente', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+28);
      $this->Cell(80, 0, 'Signature et timbre de l\'expediteur', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+30);
      $this->Cell(80, 0, 'Unterschrift und stempel des absenders', 0, 1, 'L', 0, '', 1);

      $ox = 74;
      $oy = 256;

      // Disegno la casella numero 23
      $this->SetPos($ox,$oy);
      $this->Cell(60,34,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '2', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+4,$oy-2);
      $this->Cell(80, 0, '3', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 10,$oy);
      $this->Cell(80, 0, 'Firma e timbro del trasportatore', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+2);
      $this->Cell(80, 0, 'Signature et timbre du trasporteur', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+4);
      $this->Cell(80, 0, 'Anweisungen des frachtfuhrers', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox,$oy+13);
      $this->Cell(80, 0, 'Targa motrice', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+15);
      $this->Cell(80, 0, 'Numero d\'immatricolation de la motrice', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+17);
      $this->Cell(80, 0, 'Nummernschild der kraftmaschine', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox,$oy+26);
      $this->Cell(80, 0, 'Targa rimorchio', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+28);
      $this->Cell(80, 0, 'Tractor number plate', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+30);
      $this->Cell(80, 0, 'Nummernschild des Anhanger', 0, 1, 'L', 0, '', 1);

      $ox = 134;
      $oy = 256;

      // Disegno la casella numero 24
      $this->SetPos($ox,$oy);
      $this->Cell(60,34,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '2', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+4,$oy-2);
      $this->Cell(80, 0, '4', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 10,$oy);
      $this->Cell(80, 0, 'Merce ricevuta', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+2);
      $this->Cell(80, 0, 'Merchandise recues', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+4);
      $this->Cell(80, 0, 'Gut empfangen', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox,$oy+13);
      $this->Cell(80, 0, 'Luogo', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+15);
      $this->Cell(80, 0, 'Lieu', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+17);
      $this->Cell(80, 0, 'Ort', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox,$oy+26);
      $this->Cell(80, 0, 'Firma e timbro del destinatario', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+28);
      $this->Cell(80, 0, 'Tractor number plate', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+30);
      $this->Cell(80, 0, 'Nummernschild des Anhanger', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox + 30,$oy+13);
      $this->Cell(80, 0, 'il', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 30,$oy+15);
      $this->Cell(80, 0, 'le', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 30,$oy+17);
      $this->Cell(80, 0, 'am', 0, 1, 'L', 0, '', 1);

      $ox = 103;
      $oy = 246;

      // Disegno la casella numero 15
      $this->SetPos($ox,$oy);
      $this->Cell(91,10,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+4,$oy-2);
      $this->Cell(80, 0, '5', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 10,$oy);
      $this->Cell(80, 0, 'Rimborso / Reinboursement / Ruckerstattung', 0, 1, 'L', 0, '', 1);

      $ox = 103;
      $oy = 215;

      // Disegno la casella numero 20
      $this->SetPos($ox,$oy);
      $this->Cell(91,31,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '2', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+4,$oy-2);
      $this->Cell(80, 0, '0', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 9,$oy);
      $this->Cell(80, 0, 'Da pagare per', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 9,$oy+2);
      $this->Cell(80, 0, 'A payer par', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 9,$oy+4);
      $this->Cell(80, 0, 'Zu zahlen vom', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox,$oy);
      $this->Cell(25,7,'',1,0,'L');
      $this->SetPos($ox+25,$oy);
      $this->Cell(22,7,'',1,0,'L');
      $this->SetPos($ox+47,$oy);
      $this->Cell(22,7,'',1,0,'L');
      $this->SetPos($ox+69,$oy);
      $this->Cell(22,7,'',1,0,'L');

      $this->SetPos($ox + 25,$oy);
      $this->Cell(80, 0, 'Mittente', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 25,$oy+2);
      $this->Cell(80, 0, 'Expediteur', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 25,$oy+4);
      $this->Cell(80, 0, 'Absender', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox + 47,$oy);
      $this->Cell(80, 0, 'Valuta', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 47,$oy+2);
      $this->Cell(80, 0, 'Monnaie', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 47,$oy+4);
      $this->Cell(80, 0, 'Wahrung', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox + 69,$oy);
      $this->Cell(80, 0, 'Destinatario', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 69,$oy+2);
      $this->Cell(80, 0, 'Destinataire', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 69,$oy+4);
      $this->Cell(80, 0, 'Empfangen', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox,$oy+7);
      $this->Cell(25,8,'',1,0,'L');
      $this->SetPos($ox,$oy+15);
      $this->Cell(25,12,'',1,0,'L');
      $this->SetPos($ox,$oy+27);
      $this->Cell(25,4,'',1,0,'L');
      $this->SetPos($ox + 25,$oy+7);
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->SetPos($ox + 25,$oy+11);
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->SetPos($ox + 25,$oy+15);
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->SetPos($ox + 25,$oy+19);
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->SetPos($ox + 25,$oy+23);
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->SetPos($ox + 25,$oy+27);
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');
      $this->Cell(14,4,'',1,0,'L');
      $this->Cell(8,4,'',1,0,'L');

      $this->SetFont('helvetica', '', $chsmall-1);
      $this->SetPos($ox,$oy+7);
      $this->Cell(30, 0, 'Prezzo trasporto', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+9);
      $this->Cell(30, 0, 'Prix transport/Fracht', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+11);
      $this->Cell(30, 0, 'Abbonamento', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+13);
      $this->Cell(30, 0, 'Reducion/Ermlissingungen', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox,$oy+15);
      $this->Cell(30, 0, 'Saldo', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+17);
      $this->Cell(30, 0, 'Solde/zwischensumme', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+19);
      $this->Cell(30, 0, 'Maggiorazioni', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+21);
      $this->Cell(30, 0, 'Supplements/zuschlage', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+23);
      $this->Cell(30, 0, 'Supplementi', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+25);
      $this->Cell(30, 0, 'Charges/Nebengebuhren', 0, 1, 'L', 0, '', 1);

      $this->SetPos($ox,$oy+27);
      $this->Cell(30, 0, 'Saldo', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox,$oy+29);
      $this->Cell(30, 0, 'Solde/zwischensumme', 0, 1, 'L', 0, '', 1);
     
      $ox = 103;
      $oy = 189;

      // Disegno la casella numero 19
      $this->SetPos($ox,$oy);
      $this->Cell(91,26,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+4,$oy-2);
      $this->Cell(80, 0, '9', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 10,$oy);
      $this->Cell(80, 0, 'Convenzioni particolari', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+2);
      $this->Cell(80, 0, 'Convention particulieres', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+4);
      $this->Cell(80, 0, 'Besondere vereinbarungen', 0, 1, 'L', 0, '', 1);

      $ox = 126;
      $oy = 126;

      // Disegno la casella numero 10
      $this->SetPos($ox,$oy);
      $this->Cell(23,63,'',1,0,'L');
      $this->SetPos($ox-1,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+3,$oy-2);
      $this->Cell(80, 0, '0', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall-1);
      $this->SetPos($ox + 8,$oy);
      $this->Cell(80, 0, 'N. statistica', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 8,$oy+2);
      $this->Cell(80, 0, 'No statistique', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 8,$oy+4);
      $this->Cell(80, 0, 'Statistiknummer', 0, 1, 'L', 0, '', 1);

      $ox = 149;
      $oy = 126;

      // Disegno la casella numero 11
      $this->SetPos($ox,$oy);
      $this->Cell(22,63,'',1,0,'L');
      $this->SetPos($ox-1,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+3,$oy-2);
      $this->Cell(80, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall-1);
      $this->SetPos($ox + 7,$oy);
      $this->Cell(80, 0, 'Peso lordo Kg', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 7,$oy+2);
      $this->Cell(80, 0, 'Poids brut Kg', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 7,$oy+4);
      $this->Cell(80, 0, 'Bruttogewicht Kg', 0, 1, 'L', 0, '', 1);

      $ox = 171;
      $oy = 126;

      // Disegno la casella numero 12
      $this->SetPos($ox,$oy);
      $this->Cell(23,63,'',1,0,'L');
      $this->SetPos($ox-1,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+3,$oy-2);
      $this->Cell(80, 0, '2', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall-1);
      $this->SetPos($ox + 9,$oy);
      $this->Cell(80, 0, 'Volume m3', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 9,$oy+2);
      $this->Cell(80, 0, 'Cubage m3', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 9,$oy+4);
      $this->Cell(80, 0, 'Unfang in m3', 0, 1, 'L', 0, '', 1);

      $ox = 103;
      $oy = 92;

      // Disegno la casella numero 18
      $this->SetPos($ox,$oy);
      $this->Cell(91,34,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+4,$oy-2);
      $this->Cell(80, 0, '8', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox + 10,$oy);
      $this->Cell(80, 0, 'Riserve ed osservazioni del trasportatore', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+2);
      $this->Cell(80, 0, 'Reserves et observations du transporteur', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+4);
      $this->Cell(80, 0, 'Vorbehalte und bemerkungen del frachtfuhrers', 0, 1, 'L', 0, '', 1);

      $ox = 103;
      $oy = 51;

      // Disegno la casella numero 16
      $this->SetPos($ox,$oy);
      $this->Cell(91,41,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+4,$oy-2);
      $this->Cell(80, 0, '6', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);

      $this->SetPos($ox + 10,$oy);
      $this->Cell(80, 0, 'Trasportatore (nome, indirizzo, paese)', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+2);
      $this->Cell(80, 0, 'Transporteur (nom, adresse, pays)', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+4);
      $this->Cell(80, 0, 'Franchtfuhrer (name, anshrift, land)', 0, 1, 'L', 0, '', 1);      

      $ox = 103;
      $oy = 51+24;

      // Disegno la casella numero 17
      $this->SetPos($ox,$oy);
      $this->Cell(91,41,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', 26);
      $this->Cell(80, 0, '1', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+4,$oy-2);
      $this->Cell(80, 0, '7', 0, 1, 'L', 0, '', 1);
      $this->SetFont('helvetica', '', $chsmall);

      $this->SetPos($ox + 10,$oy);
      $this->Cell(80, 0, 'Trasportatore successivo (nome, indirizzo, paese)', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+2);
      $this->Cell(80, 0, 'Transporteur successifs (nom, adresse, pays)', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox + 10,$oy+4);
      $this->Cell(80, 0, 'Nachfolgende Franchtfuhrer (name, anshrift, land)', 0, 1, 'L', 0, '', 1);      


      $ox = 103;
      $oy = 15;

      // Disegno la casella numero Titolo
      $this->SetPos($ox,$oy);
      $this->Cell(91,36,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', 'B', $chsmall);
      $this->SetPos($ox ,$oy);
      $this->Cell(80, 0, 'LETTERA DI VETTURA INTERNAZIONALE', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox ,$oy+2);
      $this->Cell(80, 0, 'LETTRE DE VOITURE INTERNATIONALE', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox ,$oy+4);
      $this->Cell(80, 0, 'INTERNATIONAL CONSIGNMENT NOTE', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox ,$oy+6);
      $this->Cell(80, 0, 'FRACHTBRIEF - TRANSPORTDOKUMENT', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+34 ,$oy+12);
      $this->SetFont('helvetica', 'B', 10);
      $this->Cell(80, 0, 'N.', 0, 1, 'L', 0, '', 1);
      $this->SetFillColor(255, 255, 255);
      $this->SetPos($ox ,$oy+24);
      $this->SetFont('helvetica', '', 5);
      $this->MultiCell(28, 14, 'Questo trasporto è sottomesso, nonostante qualunque clausola contraria alla convenzione relativa al contratto di trasporto internazionale di merci su strada.', 0, 'J', 1, 2, '' ,'', true);
      $this->SetPos($ox+28 ,$oy+24);
      $this->MultiCell(28, 14, 'Ce trasport est soumis, no nobstant toute clause contraire, a la Convention relative au contrat de transport international de marchanfises parroute.', 0, 'J', 1, 2, '' ,'', true);
      $this->SetPos($ox+56 ,$oy+24);
      $this->MultiCell(32, 14, 'Diese beforderung unterliegt trotz einer gegenteiligen abmachung den bestimmungen de ubereinkommens uber den beforderungsvertrag im internationalen strassenguterverkehr.', 0, 'J', 1, 2, '' ,'', true);

      $ox = 146;
      $oy = 5;

      // Disegno la casella codice
      $this->SetPos($ox,$oy);
      $this->Cell(25,10,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox-1,$oy);
      $this->Cell(80, 0, 'Codice trasportatore', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox-1,$oy+2);
      $this->Cell(80, 0, 'Code transporteur', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox-1,$oy+4);
      $this->Cell(80, 0, 'Code of carrier', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox-1,$oy+6);
      $this->Cell(80, 0, 'Code frachtfuhrer', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+25,$oy);
      $this->SetFont('helvetica', '', 10);
      $this->Cell(80, 0, 'N.', 0, 1, 'L', 0, '', 1);

      $ox = 171;
      $oy = 5;

      // Disegno la casella numero Titolo
      $this->SetPos($ox,$oy);
      $this->Cell(23,10,'',1,0,'L');
      $this->SetPos($ox,$oy-2);
      $this->SetFont('helvetica', '', $chsmall);

      $ox = 85;
      $oy = 60;

      // Disegno la casella Titolo centrale
      $this->SetPos($ox,$oy);
      $this->SetTextColor(228,228,228);
      $this->SetFont('helvetica', 'B', 24);
      $this->Cell(10, 0, 'C', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+14,$oy);
      $this->Cell(10, 0, 'M', 0, 1, 'L', 0, '', 1);
      $this->SetPos($ox+28,$oy);
      $this->Cell(10, 0, 'R', 0, 1, 'L', 0, '', 1);
      $this->SetTextColor(0,0,0);

      $ox = 5;
      $oy = 4;

      // Disegno la casella numero pagina
      $this->SetPos($ox,$oy);
      $this->SetFont('helvetica', 'B', 28);
      $this->Cell(10, 0, $t, 0, 1, 'L', 0, '', 1);
      
      $this->SetFont('helvetica', '', $chsmall);
      $this->SetPos($ox+8,$oy-2);
      $this->Cell(40, 10, 'Esemplare per '.$copia, 0, 1, 'L', 0, '' , 1);
      $this->SetPos($ox+8,$oy);
      $this->Cell(40, 10, 'Exemplaire pour l\'expeditour', 0, 1, 'L', 0, '' , 1);
      $this->SetPos($ox+8,$oy+2);
      $this->Cell(40, 10, 'Copy for sender', 0, 1, 'L', 0, '' , 1);
      $this->SetPos($ox+8,$oy+4);
      $this->Cell(40, 10, 'Exemplar fur Absender', 0, 1, 'L', 0, '' , 1);
     




      
      $ox = 16;
      $oy = 25;

      // inizio la compilazione dei campi
      $this->SetFont('helvetica', '', 10);
      $this->SetPos($ox,$oy);
      $this->Cell(86, 0, $this->intesta1.' '.$this->intesta1bis, 0, 1, 'L', 0, '', 1);
      $this->SetX($ox);
      $this->Cell(86, 0, $this->intesta2, 0, 1, 'L', 0, '', 1);
      $this->SetX($ox);
      if ( isset($this->intesta5)) $this->Cell(86, 0, '( '.$this->intesta5.' )', 0, 1, 'L', 0, '', 1);

      $ox = 16;
      $oy = 54;

      // campo 2
      $this->SetPos($ox, $oy);
      $this->Cell(86, 0, $this->cliente1.' '.$this->cliente2, 0, 1, 'L', 0, '', 1);
      $this->SetX($ox);
      $this->Cell(86, 0, $this->cliente3, 0, 1, 'L', 0, '', 1);
      $this->SetX($ox);
      $this->Cell(86, 0, $this->cliente4 , 0, 1, 'L', 0, '', 1);
      $this->SetX($ox);
      $this->Cell(86, 0, $this->cliente4b, 0, 1, 'L', 0, '', 1);

      // campo 3
      $this->SetY(82);
      $this->SetX($ox);
      if ( $this->destinazione!="" ) {
        $this->MultiCell(70, 0, $this->destinazione, 0, 'J', 1, 2, '' ,'', true);
      } else {
        $this->Cell(70, 0, $this->cliente3.' - '.$this->cliente4 , 0, 1, 'L', 0, '', 1);
        $this->SetX($ox);
        $this->Cell(70, 0, $this->cliente4b , 0, 1, 'L', 0, '', 1);
      }
      $this->SetX($ox);
      
      // campo 4
      $this->SetY(99);
      $this->SetX($ox);
      $this->Cell(86, 0, $this->intesta2, 0, 1, 'L', 0, '', 1);
      $this->SetX($ox);
      $this->Cell(86, 0, 'Italy', 0, 1, 'L', 0, '', 1);
      $ox = 125;
      $oy = 58;

      // campo 16
      $this->SetPos($ox,$oy);
      $this->Cell(86,0,$this->docVars->vettor['ragione_sociale'],0,1,'L',0,'',1);
      $this->SetPos($ox,$oy+5);
      $this->Cell(86,0,$this->docVars->vettor['indirizzo'].' '.$this->docVars->vettor['citta'].' ('.$this->docVars->vettor['provincia'].')',0,1,'L',0,'',1);
      $this->SetPos($ox,$oy+10);
      $this->Cell(86,0,isset($this->docVars->vettor['telefo'])?"tel : ".$this->docVars->vettor['telefo']:'',0,1,'L',0,'',1);

      $ox = 144;
      $oy = 24;

      // numero documento
      $this->SetFont('helvetica', '', 24);
      $this->SetPos($ox,$oy);
      $this->Cell(38, 0, $this->docVars->tesdoc['numdoc'].'/'.substr($this->tesdoc['datemi'],0,4), 0, 1, 'R', 0, '', 1);

      // righi dei prodotti
      $ox = 16;
      $oy = 134;
      $num = 1;
      $lines = $this->docVars->getRigo();
		  foreach ($lines AS $key => $rigo) {
        if ($rigo['tiprig'] < 2) {
                $this->SetFont('helvetica', '', 12);
                $this->SetPos($ox,$oy);
                    //$this->Cell(16,6,$rigo['codart'],1,0,'L');
                    $this->Cell(10,6,$num,0,0,'L');
                    $tipodoc = substr($this->tesdoc["tipdoc"], 0, 1);
                    $this->Cell(10,6,$rigo['unimis'],0,0,'L');
                    $this->Cell(10,6,gaz_format_quantity($rigo['quanti'],1,$this->decimal_quantity),0,0,'L');
                    $this->Cell(80,6,$rigo['descri'],0,0,'L',0,'',1);
                $oy += 6;
                $num += 1;
        }
      }

      // campo 11
      $ox = 150;
      $oy = 136;
      $this->SetPos($ox,$oy);
      $this->Cell(15, 0, gaz_format_number($this->tesdoc['gross_weight']), 0, 1, 'R', 0, '', 1);

      // campo 12
      $ox = 172;
      $oy = 136;
      $this->SetPos($ox,$oy);
      $this->Cell(15, 0, gaz_format_number($this->tesdoc['volume']), 0, 1, 'R', 0, '', 1);
      
      $ox = 20;
      $oy = 240;
      $this->SetPos($ox,$oy);
      $this->Cell(90, 7, $this->tesdoc['portos'], 0, 1, 'L', 0, '', 1);   

      
    }
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
        // set background image
        //$img_file = '../../config/templates/cmr.png';
        //$img_file = '../../config/templates.felis/cmr.png';
		if(isset($img_file)){$this->Image($img_file,0, 0,210, 297,'','','',false,300,'',false,false,0);}
        // restore auto-page-break status
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        $this->setPageMark();
   }

}
?>