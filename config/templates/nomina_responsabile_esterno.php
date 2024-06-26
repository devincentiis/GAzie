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
require('../../config/templates/template.php'); //attingo sempre dal set standard, quelli personalizzati potrebbero creare problemi di impaginazione


class NominaResponsabileEsterno extends Template
{
    function setTesDoc()
    {
      $this->tesdoc = $this->docVars->tesdoc;
      if ($this->docVars->client['sexper'] == 'F'){
         $this->descriResponsabile = 'la Signora';
      } elseif ($this->docVars->client['sexper'] == 'M'){
         $this->descriResponsabile = 'il Signor';
      } else {
         $this->descriResponsabile = 'la Spettabile';
      }
      $this->service_descri=$this->docVars->client['external_service_descri'];
      if ($this->docVars->tesdoc['clfoco']<=100000000) {
        // non è un partner commerciale ma un responsabile interno (con diritti di amministrazione di gazie)
        $this->user = gaz_dbi_get_row($this->docVars->gTables['admin'], "user_id", $this->docVars->tesdoc['clfoco']);
        $this->cliente1 = $this->user['user_firstname'].' '.$this->user['user_lastname'];
        $this->pers_title='per il/la Sig.r/a';
        $this->descriResponsabile = 'il/la Signor/a';
      }
      $this->intesta1 = $this->docVars->intesta1;
      $this->intesta1bis = $this->docVars->intesta1bis;
      $this->intesta2 = $this->docVars->intesta2;
      $this->intesta3 = $this->docVars->intesta3;
      $this->intesta4 = $this->docVars->intesta4;
      $this->colore = $this->docVars->colore;
      $this->tipdoc = 'NOMINA DEL RESPONSABILE ESTERNO DEL TRATTAMENTO DEI DATI';
      $this->docVars->gazTimeFormatter->setPattern('dd MMMM yyyy');
      $this->luogo = $this->docVars->azienda['citspe'].' ('.$this->docVars->azienda['prospe'].'), lì '.$this->docVars->gazTimeFormatter->format(new DateTime());
      $this->pec = $this->docVars->azienda['pec'];
      if ($this->docVars->intesta5 == 'F'){
         $this->descriAzienda = 'la ditta';
      } elseif ($this->docVars->intesta5 == 'M'){
         $this->descriAzienda = 'la ditta';
      } else {
         $this->descriAzienda = 'la società';
      }
      $this->giorno = substr($this->tesdoc['datemi'],8,2);
      $this->mese = substr($this->tesdoc['datemi'],5,2);
      $this->anno = substr($this->tesdoc['datemi'],0,4);
      $this->clientSedeLegale =''; // la sede legale verrà stampata al posto della destinazione
    }

    function newPage() {
        $this->AddPage();
        $this->SetFont('helvetica','',11);
    }

    function pageHeader() {
        $this->StartPageGroup();
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->newPage();
    }


    function compose()
    {
        $this->setTesDoc();
        $this->body();
    }

    function body()
    {
        $ucDescAzienda = ucfirst($this->descriAzienda);
        $body = "
<p><b>{$this->luogo}</b></p>
<p>In applicazione del “Codice in materia di protezione dei dati personali” di cui all’art. 29 del D. Lgs. n. 196/2003  e del considerando art. 28 del Regolamento UE 2016/679 (in seguito <b>&ldquo;Reg.&rdquo;</b>): {$ucDescAzienda} <b>{$this->intesta1} {$this->intesta1bis}</b> {$this->intesta2} {$this->intesta3} email: {$this->intesta4} (in seguito <b>&ldquo;Titolare&rdquo;</b>), con riferimento al trattamento connesso al contratto per: <strong>{$this->service_descri}</strong>, stipulato con {$this->descriResponsabile} <strong> {$this->cliente1}</strong>, il <strong>Titolare</strong> designa&nbsp; quale responsabile del trattamento per tutta la durata del contratto suddetto.<br /><strong>{$this->cliente1}</strong>, sottoscrivendo la presente, accetta la designazione a responsabile e conferma di garantire la messa in atto di misure tecniche ed organizzative adeguate affinch&egrave; il trattamento relativo al servizio affidatogli sia conforme al Reg. e garantisca la tutela dei diritti degli interessati. In particolare {$this->cliente1} si impegna al rispetto delle seguenti istruzioni anche in caso di trasferimento dei dati verso paesi terzi:</p>
<ul>
<li>trattare i dati personali, ivi inclusi eventuali categorie particolari di dati ove necessari, nel pieno rispetto dei principi e delle disposizioni del Reg. ed esclusivamente per gli scopi specificati nel contratto;</li>
<li>garantire che le persone autorizzate al trattamento si siano impegnate alla riservatezza o abbiano un adeguato obbligo di riservatezza;</li>
<li>adottare misure tecniche ed organizzative adeguate per garantire un livello di sicurezza adeguato al rischio secondo quanto disposto dall&rsquo;art. 32 del Reg.;</li>
<li>non ricorrere ad altro responsabile senza aver preventivamente ottenuto l&rsquo;autorizzazione del <strong>Titolare</strong> e sulla base di contratto o altro atto giuridico che riporti queste istruzioni;</li>
<li>assistere il <strong>Titolare</strong> nel garantire il rispetto degli obblighi in materia di sicurezza, ivi inclusa la gestione di eventuali violazioni dei dati, di valutazione di impatto e dell&rsquo;eventuale consultazione preventiva del Garante;</li>
<li>cancellare o restituire al <strong>Titolare</strong> tutti i dati personali al termine della prestazione del servizio o in caso di revoca della presente designazione, cancellando eventuali copie esistenti eccettuate eventuali esigenze di loro conservazione in adempimento di obblighi normativi di cui dovr&agrave; essere fornita attestazione al <strong>Titolare</strong>;</li>
<li>mettere a disposizione del <strong>Titolare</strong> tutte le informazioni necessarie per dimostrare il rispetto degli obblighi imposti dal Reg. e contribuendo alle attivit&agrave; di revisione, comprese le ispezioni, realizzate dal <strong>Titolare</strong> o da altro soggetto incaricato dal <strong>Titolare</strong> stesso.</li>
</ul>
<p><strong>{$this->cliente1}</strong> si impegna a mantenere indenne e manlevato il <strong>Titolare</strong> per ogni danno, onere, costo, spesa e/o pretesa di terzi eventualmente derivante dalla violazione degli obblighi di cui alla presente <strong>nomina a responsabile del trattamento.</strong></p>";
        $this->SetFont('helvetica', '', 11);
		$this->y=$this->GetY();
        $this->WriteHTMLCell(184,4,10,$this->y-20,$body, 0, 1, 0, true, 'J');
        $this->Ln(5);
        $this->SetFont('courier','',10);
        $this->Cell(184,4,$this->luogo, 0, 1);
        $this->Ln(5);
        $this->SetFont('courier','B',10);
        $this->Cell(62,4,'IL TITOLARE', 0, 0, 'C');
        $this->Cell(30);
        $this->Cell(92,4,'per accettazione IL RESPONSABILE DEL TRATTAMENTO', 0, 1, 'R');
        $this->Ln(8);
        $this->Cell(62,4,'','B');
        $this->Cell(30);
        $this->Cell(92,4,'','B',1);
        $this->SetFont('courier','',7);
        $this->Cell(62,4,$this->intesta1);
        $this->Cell(30);
        $this->Cell(92,4,$this->cliente1);
    }
    function pageFooter()
    {
    }
}
?>
