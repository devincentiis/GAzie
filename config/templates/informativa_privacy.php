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

class InformativaPrivacy extends Template
{
  public $descriAzienda;
  public $giorno;
  public $mese;
  public $anno;
  public $pec;
  public $luogo;
  public $cliente6;
  public $tipdoc;

    function setTesDoc()
    {
        $this->tesdoc = $this->docVars->tesdoc;
        $this->tesdoc = $this->docVars->tesdoc;
        $this->intesta1 = $this->docVars->intesta1;
        $this->intesta1bis = $this->docVars->intesta1bis;
        $this->intesta2 = $this->docVars->intesta2;
        $this->intesta3 = $this->docVars->intesta3;
        $this->intesta4 = $this->docVars->intesta4;
        $this->colore = $this->docVars->colore;
        $this->tipdoc = 'INFORMATIVA PER IL TRATTAMENTO DEI DATI PERSONALI';
        $this->cliente1 = $this->docVars->cliente1;
        $this->cliente2 = $this->docVars->cliente2;
        $this->cliente3 = $this->docVars->cliente3;
        $this->cliente4 = $this->docVars->cliente4;
        $this->cliente5 = $this->docVars->cliente5;
        $this->cliente6 = $this->docVars->client['sexper'];
		$this->luogo = $this->docVars->azienda['citspe'].' ('.$this->docVars->azienda['prospe'].')';
		$this->pec = $this->docVars->azienda['pec'];
        if ($this->docVars->intesta5 == 'F'){
           $this->descriAzienda = 'la sottoscritta';
        } elseif ($this->docVars->intesta5 == 'M'){
           $this->descriAzienda = 'il sottoscritto';
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
        $testo = "
<p>{$ucDescAzienda} <b>{$this->intesta1} {$this->intesta1bis}</b> {$this->intesta2} {$this->intesta3} email: {$this->intesta4} (in seguito <b>&ldquo;Titolare&rdquo;</b>),in qualità di titolare del trattamento, La informa ai sensi dell’art. 13 D.Lgs. 30.6.2003 n. 196 (in seguito, <b>&ldquo;Codice Privacy&rdquo;</b>) e dell’art. 13 Regolamento UE n. 2016/679 (in seguito, <b>&ldquo;GDPR&rdquo;</b>) che i Suoi dati saranno trattati con le modalità e per le finalità seguenti:</p>
<ol>
  <li><h4><b>Oggetto del Trattamento</b></h4>
    Il Titolare tratta i dati personali, identificativi quali: nome, cognome, ragione
    sociale, indirizzo, telefono, e-mail, riferimenti bancari e di pagamento (in seguito,
    <b>&ldquo;dati personali&rdquo;</b> o anche <b>&ldquo;dati&rdquo;</b>) da Lei comunicati in occasione della conclusione di
    contratti per la fornitura di merci e/o servizi e di cui qui riportiamo l'anagrafica: <br>
    {$this->cliente1} {$this->cliente2} {$this->cliente3} {$this->cliente4}.
  </li>

  <li><h4><b>Finalità del trattamento</b></h4>
    I Suoi dati personali sono trattati:<br>
    <ol type=\"A\">
      <li>senza il Suo consenso espresso (art. 24 lett. a), b), c) Codice Privacy e art. 6 lett.b), e) GDPR), per le seguenti Finalità di Servizio:
        <ul>
          <li>concludere i contratti per i servizi del Titolare;</li>
          <li>adempiere agli obblighi precontrattuali, contrattuali e fiscali derivanti da
            rapporti con Lei in essere;</li>
          <li>adempiere agli obblighi previsti dalla legge, da un regolamento, dalla
            normativa comunitaria o da un ordine dell’Autorità (come ad esempio in
            materia di antiriciclaggio);</li>
          <li>esercitare i diritti del Titolare, ad esempio il diritto di difesa in giudizio;</li>
        </ul><br>
      </li>
      <li>Solo previo Suo specifico e distinto consenso (artt. 23 e 130 Codice Privacy e art. 7
        GDPR), per le seguenti Finalità di Marketing:
        <ul>
          <li>inviarLe via e-mail, posta e/o sms e/o contatti telefonici, newsletter,
            comunicazioni commerciali e/o materiale pubblicitario su prodotti o servizi
            offerti dal Titolare e rilevazione del grado di soddisfazione sulla qualità dei
            servizi;</li>
          <li>inviarLe via e-mail, posta e/o sms e/o contatti telefonici comunicazioni
            commerciali e/o promozionali inerenti la nostra attività commerciale.</li>
        </ul>
      </li>
    </ol>
    <br>Le segnaliamo che se siete già nostri clienti, potremo inviarLe comunicazioni
    commerciali relative a servizi e prodotti del Titolare analoghi a quelli di cui ha già
    usufruito, salvo Suo dissenso (art. 130 c. 4 Codice Privacy).
  </li>

  <li><h4><b>Modalità di trattamento</b></h4>
    Il trattamento dei Suoi dati personali è realizzato per mezzo delle operazioni indicate
    all’art. 4 Codice Privacy e all’art. 4 n. 2) GDPR e precisamente: raccolta,
    registrazione, organizzazione, conservazione, consultazione, elaborazione,
    modificazione, selezione, estrazione, raffronto, utilizzo, interconnessione, blocco,
    comunicazione, cancellazione e distruzione dei dati. I Suoi dati personali sono
    sottoposti a trattamento sia cartaceo che elettronico e/o automatizzato.
    Il Titolare tratterà i dati personali per il tempo necessario per adempiere alle finalità di
    cui sopra e comunque per non oltre 10 anni dalla cessazione del rapporto per le
    Finalità di Servizio e per non oltre 2 anni dalla raccolta dei dati per le Finalità di
    Marketing.
  </li>

  <li><h4><b>Accesso ai dati</b></h4>
    I Suoi dati potranno essere resi accessibili per le finalità di cui all’art. 2.A) e 2.B):
    <ul>
      <li>a dipendenti e collaboratori del Titolare nella loro qualità di incaricati e/o
        responsabili interni del trattamento e/o amministratori di sistema;</li>
      <li>a società terze o altri soggetti (a titolo indicativo, istituti di credito, studi
        professionali, consulenti, società di assicurazione per la prestazione di servizi
        assicurativi, etc.) che svolgono attività in outsourcing per conto del Titolare,
        nella loro qualità di responsabili esterni del trattamento.</li>
    </ul>
  </li>

  <li><h4><b>Comunicazione dei dati</b></h4>
    Senza la necessità di un espresso consenso (ex art. 24 lett. a), b), d) Codice
    Privacy e art. 6 lett. b) e c) GDPR), il Titolare potrà comunicare i Suoi dati per
    le finalità di cui all’art. 2 ad altri organi della P.A., Autorità
    giudiziarie, a società di assicurazione per la prestazione di servizi assicurativi,
    nonché a quei soggetti ai quali la comunicazione sia obbligatoria per legge
    per l’espletamento delle finalità dette. Detti soggetti tratteranno i dati nella loro
    qualità di autonomi titolari del trattamento.
    I Suoi dati non saranno diffusi.
  </li>

  <li><h4><b>Trasferimento dati</b></h4>
    I dati personali sono conservati su server ubicati in {$this->luogo}, all’interno dell’Unione
    Europea.
  </li>

  <li><h4><b> Natura del conferimento dei dati e conseguenze del rifiuto di rispondere</b></h4>
    Il conferimento dei dati per le finalità di cui all’art. 2.A) è obbligatorio.
    In loro assenza, non potremo garantirLe i Servizi dell’art. 2.A).
    <br>Il conferimento dei dati per le finalità di cui all’art. 2.B) è invece facoltativo. Può quindi
    decidere di non conferire alcun dato o di negare successivamente la possibilità di trattare dati
    già forniti: in tal caso, non potrà ricevere newsletter, comunicazioni commerciali e materiale
    pubblicitario inerenti ai Servizi offerti.
  </li>

  <li><h4><b>Diritti dell’interessato</b></h4>
    Nella Sua qualità di interessato, ha i diritti di cui all’art. 7 Codice Privacy e
    art. 15 GDPR e precisamente i diritti di:
    <ol type=\"a\">
      <li>ottenere la conferma dell'esistenza o meno di dati personali che La
        riguardano, anche se non ancora registrati, e la loro comunicazione in
        forma intelligibile;
      </li>
      <li>ottenere l'indicazione: a) dell'origine dei dati personali; b) delle finalità e
        modalità del trattamento; c) della logica applicata in caso di trattamento
        effettuato con l'ausilio di strumenti elettronici; d) degli estremi
        identificativi del titolare, dei responsabili e del rappresentante designato ai
        sensi dell'art. 5, comma 2 Codice Privacy e art. 3, comma 1, GDPR; e) dei
        soggetti o delle categorie di soggetti ai quali i dati personali possono
        essere comunicati o che possono venirne a conoscenza in qualità di
        rappresentante designato nel territorio dello Stato, di responsabili o
        incaricati;
      </li>
      <li>ottenere: a) l'aggiornamento, la rettificazione ovvero, quando vi ha
        interesse, l'integrazione dei dati; b) la cancellazione, la trasformazione in
        forma anonima o il blocco dei dati trattati in violazione di legge, compresi
        quelli di cui non è necessaria la conservazione in relazione agli scopi per i
        quali i dati sono stati raccolti o successivamente trattati;
      </li>
      <li>l'attestazione che le operazioni di cui alle lettere a) e b) sono state portate
        a conoscenza, anche per quanto riguarda il loro contenuto, di coloro ai
        quali i dati sono stati comunicati o diffusi, eccettuato il caso in cui tale
        adempimento si rivela impossibile o comporta un impiego di mezzi
        manifestamente sproporzionato rispetto al diritto tutelato;
      </li>
      <li>opporsi, in tutto o in parte: a) per motivi legittimi al trattamento dei dati
        personali che La riguardano, ancorché pertinenti allo scopo della raccolta;
        b) al trattamento di dati personali che La riguardano a fini di invio di
        materiale pubblicitario o di vendita diretta o per il compimento di ricerche di
        mercato o di comunicazione commerciale, mediante l’uso di sistemi
        automatizzati di chiamata senza l’intervento di un operatore mediante e-mail
        e/o mediante modalità di marketing tradizionali mediante telefono e/o posta
        cartacea. Si fa presente che il diritto di opposizione dell’interessato,
        esposto al precedente punto b), per finalità di marketing diretto mediante
        modalità automatizzate si estende a quelle tradizionali e che comunque
        resta salva la possibilità per l’interessato di esercitare il diritto di
        opposizione anche solo in parte. Pertanto, l’interessato può decidere di
        ricevere solo comunicazioni mediante modalità tradizionali ovvero solo
        comunicazioni automatizzate oppure nessuna delle due tipologie di
        comunicazione.
      </li>
      <li>Ove applicabili, ha altresì i diritti di cui agli artt. 16-21 GDPR (Diritto di
        rettifica, diritto all’oblio, diritto di limitazione di trattamento, diritto alla
        portabilità dei dati, diritto di opposizione), nonché il diritto di reclamo
        all’Autorità Garante.
      </li>
    </ol>
  </li>

  <li><h4><b>Modalità di esercizio dei diritti</b></h4>
    Potrà in qualsiasi momento esercitare i diritti inviando:
    <ul>
      <li>una raccomandata a.r. a {$this->intesta1} {$this->intesta1bis} {$this->intesta2}.</li>
      <li>una e-mail all’indirizzo P.E.C.: {$this->pec}</li>
    </ul>
  </li>

  <li><h4><b>Titolare, responsabile e incaricati</b></h4>
    Il Titolare del trattamento è {$this->intesta1} {$this->intesta1bis} con sede
    in {$this->intesta2}.
    L’elenco aggiornato dei responsabili e degli incaricati al trattamento è custodito
    presso la sede legale del Titolare del trattamento.
  </li>

</ol>
<b>Ai sensi dell’art. 23 del D. Lgs. 196/2003 e degli art. 4,5 e 7 del G.D.P.R. 2016/679, dichiaro di aver preso visione della presente Informativa</b><br><br>
In Fede: __________________________
<br>Dichiaro inoltre di <br> |_| Autorizzare <br> |_|  Non Autorizzare
<br>il Titolare al trattamento dei miei dati nell’ambito delle finalità e nei modi qui documentati.<br><br>
In Fede: __________________________";

        $this->SetFont('courier','',7);
        $this->WriteHTMLCell(184,4,10,65,$testo, 0, 0, 0, true, 'J');
    }
    function pageFooter()
    {
    }
}
?>
