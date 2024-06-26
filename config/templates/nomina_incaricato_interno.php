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

class NominaIncaricatoInterno extends Template
{
    function setTesDoc()
    {
      $this->tesdoc = $this->docVars->tesdoc;
      $this->user = gaz_dbi_get_row($this->docVars->gTables['admin'], "user_id", $this->docVars->tesdoc['clfoco']);
      $this->intesta1 = $this->docVars->intesta1;
      $this->intesta1bis = $this->docVars->intesta1bis;
      $this->intesta2 = $this->docVars->intesta2;
      $this->intesta3 = $this->docVars->intesta3;
      $this->intesta4 = $this->docVars->intesta4;
      $this->colore = $this->docVars->colore;
      $this->tipdoc = 'NOMINA A INCARICATO DEL TRATTAMENTO DEI DATI PERSONALI';
      $this->cliente1 = $this->user['user_firstname'].' '.$this->user['user_lastname'];
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
      $this->pers_title=$this->docVars->intesta5 ;
      $this->pers_title='Informativa alla nomina per il Sig.';
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
        $premessa = "
<p><b>{$this->luogo}</b></p>
<p>In applicazione del “Codice in materia di protezione dei dati personali” di cui all’art. 29 del D. Lgs. n. 196/2003 (in seguito anche <b>&ldquo;Codice&rdquo;</b>): {$ucDescAzienda} <b>{$this->intesta1} {$this->intesta1bis}</b> {$this->intesta2} {$this->intesta3} email: {$this->intesta4} (in seguito anche <b>&ldquo;Titolare&rdquo;</b>),</p>
<p><b>premesso che:</b></p>
<ul>
  <li>connessa al ruolo stesso di dipendente del{$this->descriAzienda} {$this->intesta1} {$this->intesta1bis}, ai sensi dell’art. 30 del D.Lgs. 196/2003, è la <b>qualifica di Incaricato del Trattamento dei dati Personali</b>;</li>
  <li>la formalizzazione della suddetta qualifica di Incaricato <b>è indispensabile</b> in quanto legittima il flusso delle informazioni personali nell’ambito dell’azienda ed uffici e tra i dipendenti stessi del{$this->descriAzienda} {$this->intesta1} {$this->intesta1bis}; quindi, essa è altresì necessaria al fine di esonerare i dipendenti dall’irrogazione di sanzioni civili, amministrative e penali (salvo il caso di dolo o colpa) che, viceversa, potrebbero scaturire nell’ipotesi di comunicazioni di dati effettuate da soggetti non designati INCARICATI, come previsto dal suddetto art. 30;</li>
</ul>";
    $nomina = "
<p>la S.V. in qualità di Incaricato al trattamento dei dati personali ex art. 30 del Dlgs 196/03.
Tale nomina è in relazione alle operazioni di trattamento di dati personali e sensibili ai quali la S.V. ha quotidianamente accesso nell’ambito delle attività che effettivamente svolge nel{$this->descriAzienda} {$this->intesta1} {$this->intesta1bis}</p>
<p>Ambito del trattamento consentito:</p>
<ul type=\"square\">
	<li>Raccolta
	</li>
	<li>Registrazione
	</li>
	<li>Organizzazione
	</li>
	<li>Conservazione
	</li>
	<li>Consultazione
	</li>
	<li>Comunicazione
	</li>
	<li>Elaborazione
	</li>
	<li>Modifica
	</li>
	<li>Selezione
	</li>
	<li>Estrazione
	</li>
	<li>Raffronto
	</li>
	<li>Utilizzo
	</li>
	<li>Interconnessione
	</li>
</ul>
<p>In ottemperanza al Codice in materia di protezione dei dati personali ex D.Lgs 196/2003 e del Regolamento Privacy UE 679/2016, che regola il trattamento dei dati personali, laddove costituisce trattamento “<i>qualunque operazione o complesso di operazioni, effettuati anche senza l’ausilio di strumenti elettronici, concernenti la raccolta, la registrazione, l’organizzazione, la conservazione, la consultazione, l’elaborazione, la modificazione, la selezione, l’estrazione, il raffronto, l’utilizzo, l’interconnessione, il blocco, la comunicazione, la diffusione, la cancellazione e la distruzione di dati, anche se non registrati in una banca di dati</i>”, ed in relazione al presente atto di nomina, <b>la S.V. è incaricato<sup>(1)</sup> al trattamento dei dati personali</b> (<i>tutti quei dati idonei a identificare direttamente o indirettamente una persona fisica</i>) <b>e dei dati sensibili</b> (<i>i dati personali idonei a rivelare l’origine razziale ed etnica, le convinzioni religiose, filosofiche o di altro genere, le opinioni politiche, l’adesione a partiti, sindacati, associazioni od organizzazioni a carattere religioso, filosofico, politico o sindacale, lo stato di salute e la vita sessuale</i>) <b>la cui conoscenza ed il cui trattamento siano strettamente necessari per adempiere ai compiti assegnati.</b></p>
<p>Nel trattamento dei dati la S.V. deve scrupolosamente attenersi alle seguenti istruzioni:</p>
<ul>
	<li>trattare i dati in modo <b>lecito</b> e secondo <b>correttezza</b>;
	</li>
	<li>raccogliere i dati e registrarli per gli scopi inerenti l’attività svolta;
	</li>
	<li>verificare, ove possibile, che i dati siano esatti e, se necessario, aggiornarli;
	</li>
	<li>verificare che i dati siano pertinenti, completi e non eccedenti le finalità per le quali sono stati raccolti o successivamente trattati, secondo le indicazioni ricevute dal Responsabile del trattamento;
	</li>
	<li>mantenere la massima riservatezza sui dati di cui si effettua il trattamento;
	</li>
	<li>non utilizzare, comunicare o diffondere alcuno dei dati predetti se non previamente autorizzato dal Titolare del trattamento o dal Responsabile;
	</li>
	<li>adottare le necessarie cautele per assicurare la segretezza della componente riservata della credenziale e la diligente custodia dei dispositivi in possesso ed uso esclusivo dell'incaricato;
	</li>
	<li>in caso di allontanamento, anche temporaneo, dal posto di lavoro, l'incaricato dovrà verificare che non vi sia possibilità, da parte di terzi, di accedere a dati personali per i quali sia in corso un qualunque tipo di trattamento, sia cartaceo che informatizzato.
	</li>
	<li>in particolare, per quanto concerne l’utilizzo degli strumenti informatici, dovranno essere scrupolosamente osservate le disposizioni contenute nel “Regolamento per l’utilizzo e la gestione delle risorse strumentali informatiche e telematiche aziendali”, documento appendice della presente nomina e comunque forniti dal Titolare e/o dal Responsabile
	</li>
	<li>per quanto concerne gli archivi cartacei, l’accesso è consentito solo se previamente autorizzato dal Responsabile o dal Titolare del trattamento e deve riguardare i soli dati personali la cui conoscenza sia strettamente necessaria per adempiere i compiti assegnati, avendo particolare riguardo a:
		<ul>
			<li>i documenti cartacei devono essere prelevati dagli archivi per il tempo strettamente necessario allo svolgimento delle mansioni;
			</li>
			<li>atti e documenti contenenti dati sensibili o giudiziari devono essere custoditi in contenitori muniti di serratura e devono essere controllati in modo tale che a tali atti e documenti non possano accedere persone prive di autorizzazione;
			</li>
			<li>atti e documenti contenenti dati sensibili o giudiziari devono essere restituiti al termine delle operazioni affidate;
			</li>
			<li>eventuali fotocopie o copie di documenti in pellicola devono essere autorizzate e custodite con le stesse modalità dei documenti originali;
			</li>
		</ul>
	</li>
</ul>
<p><sup>(1)</sup><b>Incaricati del trattamento</b>: chi “tratta” i dati non può che essere un incaricato.
Data la nozione amplissima di “trattamento” (art. 4 comma 1), non c’è dubbio che tutti i dipendenti/collaboratori aziendali debbano firmare per presa visione la formale nomina ad incaricati del trattamento, in quanto questa è la precondizione per poter svolgere il lavoro che contrattualmente sono tenuti a svolgere.<br>
Rifiutare la nomina equivale a:</p>
<ul type=\"square\">
	<li>non poter “trattare” alcun dato e quindi a
	</li>
	<li>non poter svolgere il lavoro per cui si è assunti.
	</li>
</ul>
<p>Il rifiuto potrebbe pertanto portare:</p>
<ul type=\"square\">
	<li>comunque ad una sanzione disciplinare
	</li>
	<li>al limite, alla risoluzione del rapporto di lavoro.
	</li>
</ul>
";
		$this->setTopMargin(53);
        $this->SetFont('courier','',9);
		$this->y=$this->GetY();
        $this->WriteHTMLCell(184,4,10,$this->y-25,$premessa, 0, 1, 0, true, 'J');
        $this->SetFont('courier','B',10);
        $this->Cell(184,4,'NOMINA', 0, 1, 'C');
		$this->y=$this->GetY();
        $this->SetFont('courier','',9);
        $this->WriteHTMLCell(184,4,10,$this->y+3,$nomina, 0, 1, 0, true, 'J');
        $this->Ln(2);
        $this->SetFont('courier','',9);
        $this->Cell(92,4,$this->luogo);
        $this->SetFont('courier','B',9);
        $this->Cell(92,4,'firma del Titolare o del Responsabile del Trattamento', 0, 1, 'R');

    }
    function pageFooter()
    {
    }
}
?>
