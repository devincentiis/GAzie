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

class Template_con_scheda extends Template
{
    public function SchedaTrasporto()
    {
        $this->SetXY(10,10);
        $this->SetFont('times','B',14);
        $this->Cell(184,6,'SCHEDA DI TRASPORTO',0,1,'C');
        $this->SetFont('times','I',8);
        $this->Cell(184,4,'(da compilarsi a cura del committente e conservare dal vettore a bordo del veicolo: art. 7-bis, D.Lgs. 286/2005)',0,1,'C');

        $this->SetFont('times','B',11);
        $this->Cell(46,8,'A - DATI DEL VETTORE');
        $this->SetFont('times','I',8);
        $this->Cell(138,8,'(art. 2, comma 1, lett. b - D.Lgs. 286/2005)',0,1);
        $this->SetFont('times','',8);
        $this->Cell(62,4,'Denominazione (1) Ragione Sociale Ditta','LT');
        $this->Cell(122,4,$this->docVars->vettor['ragione_sociale'],'LTR',1);
        $this->Cell(62,4,'Indirizzo e Sede Azienda','L');
        $this->Cell(122,4,$this->docVars->vettor['indirizzo'].' '.$this->docVars->vettor['cap'].' '.$this->docVars->vettor['citta'].' ('.$this->docVars->vettor['provincia'].')','LR',1);
        $this->Cell(62,4,'(riferimenti telefonici o mail)','L');
        $this->Cell(122,4,$this->docVars->vettor['telefo'],'LR',1);
        $this->Cell(62,4,'Partita IVA',1);
        $this->Cell(122,4,$this->docVars->vettor['partita_iva'],1,1);
        $this->Cell(62,4,'Numero iscrizione albo autotrasportatori',1);
        $this->Cell(122,4,$this->docVars->vettor['n_albo'],1,1);

        $this->SetFont('times','B',11);
        $this->Cell(62,8,'B - DATI DEL COMMITTENTE');
        $this->SetFont('times','I',8);
        $this->Cell(122,8,'(art. 2, comma 1, lett. c - D.Lgs. 286/2005)',0,1);
        $this->SetFont('times','',8);
        $this->Cell(62,4,'Denominazione (1) Ragione Sociale Ditta','LT');
        $this->Cell(122,4,$this->intesta1.' '.$this->intesta1bis,'LTR',1);
        $this->Cell(62,4,'Indirizzo e Sede Azienda','L');
        $this->Cell(122,4,$this->intesta2,'LR',1);
        $this->Cell(62,4,'(riferimenti telefonici o mail)','L');
        $this->Cell(122,4,$this->intesta3,'LR',1);
        $this->Cell(62,4,'Partita IVA / Codice Fiscale',1);
        $this->Cell(122,4,$this->intesta4,1,1);

        $this->SetFont('times','B',11);
        $this->Cell(60,8,'C - DATI DEL CARICATORE');
        $this->SetFont('times','I',8);
        $this->Cell(124,8,'(art. 2, comma 1, lett. d - D.Lgs. 286/2005)',0,1);
        $this->SetFont('times','',8);
        $this->Cell(62,4,'Denominazione (1) Ragione Sociale Ditta','LT');
        $this->Cell(122,4,$this->intesta1.' '.$this->intesta1bis,'LTR',1);
        $this->Cell(62,4,'Indirizzo e Sede Azienda','L');
        $this->Cell(122,4,$this->intesta2,'LR',1);
        $this->Cell(62,4,'(riferimenti telefonici o mail)','L');
        $this->Cell(122,4,$this->intesta3,'LR',1);
        $this->Cell(62,4,'Partita IVA / Codice Fiscale',1);
        $this->Cell(122,4,$this->intesta4,1,1);

        $this->SetFont('times','B',11);
        $this->Cell(86,8,'D - DATI DEL PROPRIETARIO DELLA MERCE');
        $this->SetFont('times','I',8);
        $this->Cell(98,8,'(art. 2, comma 1, lett. e - D.Lgs. 286/2005)',0,1);
        $this->SetFont('times','',8);
        $this->Cell(62,4,'Denominazione (1) Ragione Sociale Ditta','LT');
        $this->Cell(122,4,$this->cliente1.' '.$this->cliente2,'LTR',1);
        $this->Cell(62,4,'Indirizzo e Sede Azienda','L');
        if (!empty($this->clientSedeLegale)) {
          $this->Cell(122,4,$this->clientSedeLegale,'LR',1);
        } else {
          $this->Cell(122,4,$this->cliente3.' '.$this->cliente4,'LR',1);
        }
        $this->Cell(62,4,'(riferimenti telefonici o mail)','L');
        $this->Cell(122,4,$this->docVars->client['telefo'].' '.$this->docVars->client['e_mail'],'LR',1);
        $this->Cell(62,4,'Partita IVA / Codice Fiscale',1);
        $this->Cell(122,4,$this->cliente5,1,1);

        $this->Ln(4);
        $this->SetFont('times','B',10);
        $this->Cell(184,8,'Eventuali dichiarazioni (2)','LTR',1);
        $this->Cell(184,14,'','LBR',1);

        $this->Ln(4);
        $this->Cell(62,5,'E - DATI MERCE TRASPORTATA',1,1);
        $this->SetFont('times','',8);
        $this->Cell(62,4,'Tipologia','LT');
        $this->Cell(122,4,'Vedi '.$this->tipdoc,'LTR',1);
        $this->Cell(62,4,'Quantita\' / Peso','LT');
        $this->Cell(122,4,'Vedi '.$this->tipdoc,'LTR',1);
        $this->Cell(62,4,'Luogo di carico merce',1);
        $this->Cell(122,4,$this->intesta2,1,1);
        $this->Cell(62,4,'Luogo di scarico',1);
        if (!empty($this->destinazione)) {
           $this->Cell(122,4,$this->destinazione,'LBR',1);
        } else {
           $this->Cell(122,4,$this->cliente3.' '.$this->cliente4,1,1);
        }
        $this->Ln(5);
        $this->SetFont('times','B',10);
        $this->Cell(184,8,'Osservazioni varie (3)','LTR',1);
        $this->Cell(184,10,'','LBR',1);

        $this->Ln(4);
        $this->Cell(184,8,'Eventuali istruzioni (4)','LTR',1);
        $this->Cell(184,10,'','LBR',1);

        $this->Ln(4);
        $this->Cell(52,5,'Luogo e data compilazione',1,1);
        $this->SetFont('times','',8);
        $this->Cell(52,4,'Luogo e data',1);
        $this->Cell(66,4,'Dati compilatore (5)',1);
        $this->Cell(66,4,'Firma compilatore',1,1);
        $this->Cell(52,12,$this->docVars->azienda['citspe'].', '.$this->giorno.'.'.$this->mese.'.'.$this->anno,1);
        $this->Cell(66,12,$this->docVars->user['user_firstname'].' '.$this->docVars->user['user_firstname'],1);
        $this->Cell(66,12,'',1,1);

        $this->Cell(67,3,'-----------------------------------------------------------',0,1);
        $this->SetFont('times','',7);
        $this->Cell(184,3,"(1) Utilizzare denominazione sociale per le societa' di capitali;  ragione sociale per le societa' di persone e la ditta per le imprese individuali",0,1);
        $this->Cell(184,3,"(2) Da compilare nei casi in cui non e' possibile indicare la figura del proprietario",0,1);
        $this->Cell(184,3,"(3) Da compilare a cura del vettore o suo conducente qualora si verifichino variazioni rispetto alle indicazioni originarie del presente ",0,1);
        $this->Cell(184,3,"    documento (es. variazione luogo di scarico, variazione tipologia e quantita' merce, ...)",0,1);
        $this->Cell(184,3,"(4) Riportare eventuali istruzioni fornite dal committente o da uno dei soggetti della filiera del trasporto al vettore",0,1);
        $this->Cell(184,3,"(5) Indicare le generalita' di chi sottoscrive la scheda in nome e per conto del committente",0,1);
    }
}
?>