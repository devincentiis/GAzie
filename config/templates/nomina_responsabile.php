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


class NominaResponsabile extends Template
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
      $this->tipdoc = 'NOMINA A RESPONSABILE DEL TRATTAMENTO DEI DATI PERSONALI';
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
        $premessa = "
<p><b>{$this->luogo}</b></p>
<p>In applicazione del “Codice in materia di protezione dei dati personali” di cui all’art. 29 del D. Lgs. n. 196/2003 (in seguito, <b>&ldquo;Codice&rdquo;</b>) e del considerando art. 28 del Regolamento UE 2016/679 (in seguito, <b>&ldquo;GDPR&rdquo;</b>): {$ucDescAzienda} <b>{$this->intesta1} {$this->intesta1bis}</b> {$this->intesta2} {$this->intesta3} email: {$this->intesta4} (in seguito <b>&ldquo;Titolare&rdquo;</b>), la informa:</p>
<ul>
  <li>visto il Decreto Legislativo 30 giugno 2003, n. 196. “Codice in materia di protezione dei dati personali”, di seguito definito “Codice”;</li>
  <li>preso atto che l’art. 4, comma 1, lettera g) del suddetto Decreto definisce il “Responsabile” come la persona fisica, la persona giuridica, la pubblica amministrazione e qualsiasi altro ente, associazione od organismo preposti dal Titolare al trattamento dei dati personali;</li>
  <li>considerato che il DL 14/8/2013, n. 93 contempla le violazioni Privacy anche nell'ambito della responsabilità amministrativa dell'azienda a norma del Dlgs 231/2001;</li>
  <li>considerata l’entrata in vigore del nuovo Regolamento Europeo Privacy UE 2016/679 del 27 Aprile 2016, pubblicato sulla Gazzetta Ufficiale dell’Unione Europea il 04 maggio 2016;</li>
  <li>atteso che l’art. 29, commi 2, 3, 4 e 5 del D. Lgs. n. 196/2003 dispone che:</li>
    <ol type=\"a\">
		<li>Se designato, il Responsabile è individuato tra soggetti che per esperienza, capacità ed affidabilità forniscano idonea garanzia del pieno rispetto delle vigenti disposizioni in materia di trattamento, ivi compreso il profilo relativo alla sicurezza.</li>
		<li>Ove necessario per esigenze organizzative, possono essere designati responsabili più soggetti, anche mediante suddivisione dei compiti.</li>
		<li>I compiti affidati al Responsabile sono analiticamente specificati per iscritto dal Titolare.</li>
		<li>Il Responsabile effettua il trattamento attenendosi alle istruzioni impartite dal Titolare il quale, anche tramite verifiche periodiche, vigila sulla puntuale osservanza delle disposizioni di cui al comma 2 e delle proprie istruzioni.</li>
	</ol>
</ul>
<p><b>ritenuto che {$this->descriResponsabile} {$this->cliente1}</b>, per l’ambito di attribuzioni, funzioni e competenze conferite, abbia i requisiti di esperienza, capacità ed affidabilità idonei a garantire il pieno rispetto delle vigenti disposizioni in materia di trattamento dei dati, ivi compreso il profilo relativo alla sicurezza;<br>
<b>ciò premesso;</b>
</p>";
    $nomina = "
<p><b>{$this->descriResponsabile} {$this->cliente1}</b>, in qualità di Responsabile del trattamento dei dati effettuato presso {$ucDescAzienda} <b>{$this->intesta1} {$this->intesta1bis}</b> con strumenti elettronici o comunque automatizzati o con strumenti diversi, per l’ambito di attribuzioni, competenze e funzioni assegnate.<br>
In qualità di Responsabile del trattamento dei dati, ha il compito e la responsabilità di adempiere a tutto quanto necessario per il rispetto delle disposizioni vigenti in materia e di osservare scrupolosamente quanto in essa previsto, nonché le seguenti istruzioni impartite dal Titolare.<br>
<b>Il Responsabile del Trattamento si impegna, entro e non oltre 30 gg. dalla data di sottoscrizione ed accettazione della presente nomina, ad impartire per iscritto ai propri collaboratori incaricati del trattamento, istruzioni in merito alle operazioni di trattamento dei dati personali ed a vigilare sulla loro puntuale applicazione.</b></p>
";
	$compiti1 = "<p>Ogni trattamento di dati personali deve avvenire nel rispetto primario dei seguenti principi di ordine generale:
Ai sensi dell’art. 11<sup>(1)</sup> del Codice, che prescrive le “Modalità del trattamento e requisiti dei dati”, per ciascun trattamento di propria competenza, il RESPONSABILE deve fare in modo che siano sempre rispettati i seguenti presupposti:</p>
<ul>
	<li>i dati devono essere <b>trattati</b>:
		<ul>
		<li> secondo il principio di <b>liceità </b>, vale a dire conformemente alle disposizioni del Codice, nonché alle disposizioni del Codice Civile, per cui, più in particolare, il trattamento non deve essere contrario a norme imperative, all’ordine pubblico ed al buon costume;
		</li>
		<li> secondo il principio fondamentale di <b>correttezza</b>, il quale deve ispirare chiunque tratti qualcosa che appartiene alla sfera altrui;
		</li>
		</ul>
	</li>
	<li> i dati devono essere <b>raccolti</b> solo per <u>scopi</u>:
		<ul>
		<li> <b>determinati</b>, vale a dire che non è consentita la raccolta come attività fine a se stessa;
		</li>
		<li> <b>espliciti</b>, nel senso che il soggetto interessato va informato sulle finalità del trattamento;
		</li>
		<li> <b>legittimi</b>, cioè, oltre al trattamento, come è evidente, anche il fine della raccolta dei dati deve essere lecito;
		</li>
		<li> <b>compatibili</b> con il presupposto per il quale sono inizialmente trattati, specialmente nelle operazioni di comunicazione e diffusione degli stessi;
		</li>
		</ul>
	</li>
</ul>";
$note1 = "<p><sup>(1)</sup><b>Art.11 - Modalità del trattamento e requisiti dei dati</b></p>
<ol>
<li>I dati personali devono essere:
	<ol type=\"a\">
	<li>trattati in modo lecito e secondo correttezza;</li>
	<li>raccolti e registrati per scopi determinati, espliciti e legittimi, ed utilizzati in altre operazioni del trattamento in termini compatibili con tali scopi;</li>
	<li>esatti e, se necessario, aggiornati;</li>
	<li>pertinenti, completi e non eccedenti rispetto alle finalità per le quali sono raccolti o successivamente trattati;</li>
	<li>conservati in una forma che consenta l'identificazione dell'interessato per un periodo di tempo non superiore a quello necessario agli scopi per i quali essi sono stati raccolti o successivamente trattati.</li>
	</ul>
</li>
<li>I dati personali trattati in violazione della disciplina rilevante in materia di trattamento dei dati personali non possono essere utilizzati.
</li>
</ol>
";
	$compiti2 = "
<ul>
<li>i dati devono, inoltre, essere:
<ul>
	<li> <b>esatti</b>, cioè, precisi e rispondenti al vero e, se necessario, <b>aggiornati</b>;
	</li>
	<li> <b>pertinenti</b>, ovvero, il trattamento è consentito soltanto per lo svolgimento delle funzioni istituzionali, in relazione all’attività che viene svolta;
	</li>
	<li> <b>completi</b>: non nel senso di raccogliere il maggior numero di informazioni possibili, bensì di contemplare specificamente il concreto interesse e diritto del soggetto interessato;
	</li>
	<li> <b>non eccedenti</b> in senso quantitativo rispetto allo scopo perseguito, ovvero devono essere raccolti solo i dati che siano al contempo strettamente necessari e sufficienti in relazione al fine, cioè la cui mancanza risulti di ostacolo al raggiungimento dello scopo stesso;
	</li>
	<li> <b>conservati</b> per un periodo non superiore a quello necessario per gli scopi del trattamento e comunque in base alle disposizioni aventi ad oggetto le modalità ed i tempi di conservazione degli atti amministrativi. Trascorso detto periodo i dati vanno resi anonimi o cancellati art. 16<sup>(2)</sup> e la loro comunicazione e diffusione non è più consentita art. 25<sup>(3)</sup>.
	</li>
	</ul>
</li>
</ul>Ciascun trattamento deve, inoltre, avvenire nei limiti imposti dal principio fondamentale di <b>riservatezza</b> e nel rispetto della dignità della persona dell’interessato al trattamento, ovvero deve essere effettuato eliminando ogni occasione di impropria conoscibilità dei dati da parte di terzi.
Se il trattamento di dati è effettuato in violazione dei principi summenzionati e di quanto disposto dal <b>Codice</b> è necessario provvedere al <b>“blocco”</b> dei dati stessi, vale a dire alla sospensione temporanea di ogni operazione di trattamento, fino alla regolarizzazione del medesimo trattamento (ad esempio fornendo l’informativa omessa), ovvero alla cancellazione dei dati se non è possibile regolarizzare.
";
$note2 = "<p><sup>(2)</sup><b>Art.16 - Cessazione del trattamento</b></p>
<ol>
<li>In caso di cessazione, per qualsiasi causa, di un trattamento i dati sono:
	<ol type=\"a\">
	<li>distrutti;</li>
	<li>ceduti ad altro titolare, purché destinati ad un trattamento in termini compatibili agli scopi per i quali i dati sono raccolti;</li>
	<li>conservati per fini esclusivamente personali e non destinati ad una comunicazione sistematica o alla diffusione;</li>
	<li>conservati o ceduti ad altro titolare, per scopi storici, statistici o scientifici, in conformità alla legge, ai regolamenti, alla normativa comunitaria e ai codici di deontologia e di buona condotta sottoscritti ai sensi dell'articolo 12.</li>
	</ol>
</li>
<li>La cessione dei dati in violazione di quanto previsto dal comma 1, lettera b), o di altre disposizioni rilevanti in materia di trattamento dei dati personali è priva di effetti.
</li>
</ol>
<p><sup>(3)</sup><b>Art.25 - Divieti di comunicazione e diffusione</b></p>
<ol>
<li>La comunicazione e la diffusione sono vietate, oltre che in caso di divieto disposto dal Garante o dall’autorità giudiziaria:
	<ol type=\"a\">
	<li>in riferimento a dati personali dei quali è stata ordinata la cancellazione, ovvero quando è decorso il periodo di tempo indicato nell'articolo 11, comma 1, lettera e) [non superiore a quello necessario agli scopi];</li>
	<li>per finalità diverse da quelle indicate nella notificazione del trattamento, ove prescritta.</li>
	</ol>
</li>
<li>È fatta salva la comunicazione o diffusione di dati richieste, in conformità alla legge, da forze di polizia, dall’autorità giudiziaria, da organismi di informazione e sicurezza o da altri soggetti pubblici ai sensi dell’articolo 58, comma 2, per finalità di difesa o di sicurezza dello Stato o di prevenzione, accertamento o repressione di reati.
</li>
</ol>
";
	$compiti3 = "Ciascun RESPONSABILE deve, inoltre, essere a conoscenza del fatto che per la violazione delle disposizioni in materia di trattamento dei dati personali sono previste <b>sanzioni penali</b> (artt. 167 e ss.).
In ogni caso la <b>responsabilità penale</b> per eventuale uso non corretto dei dati oggetto di tutela, resta a carico della singola persona cui l’uso illegittimo degli stessi sia imputabile.
Mentre, in merito alla <b>responsabilità civile</b>, si fa rinvio all’art. 15<sup>(4)</sup> del Codice, che dispone relativamente ai danni cagionati per effetto del trattamento ed ai conseguenti obblighi di risarcimento, implicando, a livello pratico, che, per evitare ogni responsabilità, l'operatore è tenuto a fornire la prova di avere applicato le misure tecniche di sicurezza più idonee a garantire appunto la sicurezza dei dati detenuti.
";
	$compiti4 = "<p>Il RESPONSABILE del trattamento dei dati personali, operando nell’ambito dei principi sopra ricordati, deve attenersi ai seguenti <b>compiti di carattere particolare</b>:</p>
<ol type=\"A\">
<li><b>identificare e censire</b> i trattamenti di dati personali, le banche dati e gli archivi gestiti con supporti informatici e/o cartacei necessari all’espletamento delle attività istituzionalmente rientranti nella propria sfera di competenza;
</li>
<li><b>predisporre il registro delle attività di trattamento</b> da esibire in caso di ispezioni delle Autorità e contenente almeno le seguenti informazioni:
	<ul>
		<li>il nome e i dati di contatto del Responsabile, del Titolare del trattamento e del Responsabile della protezione dei dati;</li>
		<li>le categorie dei trattamenti effettuati;</li>
		<li>descrizione delle misure di sicurezza tecniche ed organizzative applicate a protezione dei dati.</li>
	</ul>
</li>
<li>definire, per ciascun trattamento di dati personali, la <b>durata</b> del trattamento e la <b>cancellazione</b> o anonimizzazione dei dati obsoleti, nel rispetto della normativa vigente in materia di prescrizione e tenuta archivi;
</li>
</ol>";
$note4 = "<p><sup>(4)</sup><b>Art.15 - Danni cagionati per effetto del trattamento</b></p>
<ol>
<li>Chiunque cagiona danno ad altri per effetto del trattamento di dati personali è tenuto al risarcimento ai sensi dell'articolo 2050 del codice civile. [Art. 2050 - Responsabilità per l'esercizio di attività pericolose: Chiunque cagiona danno ad altri nello svolgimento di un'attività pericolosa, per sua natura o per la natura dei mezzi adoperati, è tenuto al risarcimento, se non prova di avere adottato tutte le misure idonee a evitare il danno].</li>
<li>Il danno non patrimoniale è risarcibile anche in caso di violazione dell'articolo 11.</li>
</ol>";
	$compiti5 = "<ol type=\"A\"  start=\"4\">
<li>ogni qualvolta si raccolgano dati personali, provvedere a che venga fornita l’<b>informativa</b><sup>(5)</sup> ai soggetti interessati. A cura dei Responsabili dovranno inoltre essere affissi i cartelli contenenti l’informativa, in tutti i luoghi ad accesso pubblico, con la precisazione che l’informazione resa attraverso la cartellonistica integra ma non sostituisce l’obbligo di informativa in forma orale o scritta;</li>
</ol>";
$note5 = "<p><sup>(5)</sup><b>Art. 13 - Informativa</b></p>
<ol>
<li>L'interessato o la persona presso la quale sono raccolti i dati personali sono previamente informati oralmente o per iscritto circa:
	<ol type=\"a\">
	<li>le finalità e le modalità del trattamento cui sono destinati i dati;</li>
	<li>la natura obbligatoria o facoltativa del conferimento dei dati;</li>
	<li>le conseguenze di un eventuale rifiuto di rispondere;</li>
	<li>i soggetti o le categorie di soggetti ai quali i dati personali possono essere comunicati o che possono venirne a conoscenza in qualità di responsabili o incaricati, e l'ambito di diffusione dei dati medesimi;</li>
	<li>i diritti di cui all'articolo 7;</li>
	<li>gli estremi identificativi del titolare e, se designati, del rappresentante nel territorio dello Stato ai sensi dell’articolo 5 e del responsabile. Quando il titolare ha designato più responsabili è indicato almeno uno di essi, indicando il sito della rete di comunicazione o le modalità attraverso le quali è conoscibile in modo agevole l’elenco aggiornato dei responsabili. Quando è stato designato un responsabile per il riscontro all’interessato in caso di esercizio dei diritti di cui all’articolo 7, è indicato tale responsabile.</li>
	</ol>
</li>
<li>L'informativa di cui al comma 1 contiene anche gli elementi previsti da specifiche disposizioni del presente codice e può non comprendere gli elementi già noti alla persona che fornisce i dati o la cui conoscenza può ostacolare in concreto l'espletamento, da parte di un soggetto pubblico, di funzioni ispettive o di controllo svolte per finalità di difesa o sicurezza dello Stato oppure di prevenzione, accertamento o repressione di reati.
</li>
<li>Il Garante può individuare con proprio provvedimento modalità semplificate per l’informativa fornita in particolare da servizi telefonici di assistenza e informazione al pubblico.
</li>
<li>Se i dati personali non sono raccolti presso l’interessato, l’informativa di cui al comma 1, comprensiva delle categorie di dati trattati, è data al medesimo interessato all’atto della registrazione dei dati o, quando è prevista la loro comunicazione, non oltre la prima comunicazione.
</li>
<li>La disposizione di cui al comma 4 non si applica quando:
	<ol type=\"a\">
	<li>i dati sono trattati in base ad un obbligo previsto dalla legge, da un regolamento o dalla normativa comunitaria;</li>
	<li>i dati sono trattati ai fini dello svolgimento delle investigazioni difensive di cui alla legge 7 dicembre 2000, n. 397, o, comunque, per far valere o difendere un diritto in sede giudiziaria, sempre che i dati siano trattati esclusivamente per tali finalità e per il periodo strettamente necessario al loro perseguimento;</li>
	<li>l’informativa all’interessato comporta un impiego di mezzi che il Garante, prescrivendo eventuali misure appropriate, dichiari manifestamente sproporzionati rispetto al diritto tutelato, ovvero si riveli, a giudizio del Garante, impossibile.</li>
	</ol>
</li>
</ol>
<b>Art. 22 - Principi applicabili al trattamento di dati sensibili e giudiziari</b><br>
… Nel fornire l’<u>informativa</u> di cui all’articolo 13 i soggetti pubblici fanno espresso riferimento alla normativa che prevede gli obblighi o i compiti in base a cui è effettuato il trattamento dei dati sensibili e giudiziari …<br>
<b>Art. 105 - Modalità di trattamento [dei dati trattati per scopi storici e statistici]</b><br>
… gli scopi statistici o scientifici devono essere chiaramente determinati e resi noti all’interessato, nei modi di cui all’articolo 13…
";
	$compiti6 = "<ol type=\"A\"  start=\"5\">
<li>assicurare che la <b>comunicazione a terzi</b> e la diffusione dei dati personali avvenga entro i limiti stabiliti per i soggetti pubblici, ovvero, solo se prevista da una norma di legge o regolamento o se comunque necessaria per lo svolgimento di funzioni istituzionali<sup>(6)</sup>.<br>
Così, per i dati relativi ad <b>attività di studio e di ricerca</b> (art. 100)<sup>(7)</sup>, il RESPONSABILE è tenuto ad attenersi alla disciplina che dispone in merito ai casi in cui è possibile la comunicazione o diffusione anche a privati di dati personali diversi da quelli sensibili e giudiziari;</li>
<li>adempiere agli obblighi di <b>sicurezza</b>, quali:
	<ul>
		<li>adottare, tramite il supporto tecnico degli amministratori di sistema, tutte le <b>preventive misure di sicurezza</b>, ritenute <b>adeguate</b> al fine di ridurre al minimo i rischi di distruzione o perdita, anche accidentale, dei dati, di accesso non autorizzato o di trattamento non consentito o non conforme alle finalità della raccolta (art. 31)<sup>(8)</sup>;</li>
		<li>definire una politica di sicurezza per assicurare su base permanente la riservatezza, l’integrità, la disponibilità e la resilienza dei sistemi e servizi afferenti il trattamento dei dati;</li>
		<li>assicurarsi la capacità di ripristinare tempestivamente la disponibilità e l’accesso ai dati in caso di incidente fisico o tecnico;</li>
		<li>definire una procedura per testare, verificare e valutare regolarmente l’efficacia delle misure tecniche ed organizzative applicate;</li>
	</ul>
</li>
</ol>";
$note6 = "<p><sup>(6)</sup><b>Art.19 - Principi applicabili al trattamento di dati diversi da quelli sensibili e giudiziari</b></p>
… La comunicazione da parte di un soggetto pubblico ad altri soggetti pubblici è ammessa quando è prevista da una norma di legge o di regolamento. In mancanza di tale norma la comunicazione è ammessa quando è comunque necessaria per lo svolgimento di funzioni istituzionali …
<p><sup>(7)</sup><b>Art.100 - Dati relativi ad attività di studio e ricerca</b></p>
<ol>
<li>Al fine di promuovere e sostenere la ricerca e la collaborazione in campo scientifico e tecnologico i soggetti pubblici, ivi comprese le università e gli enti di ricerca, possono con autonome determinazioni comunicare e diffondere, anche a privati e per via telematica, dati relativi ad attività di studio e di ricerca, a laureati, dottori di ricerca, tecnici e tecnologi, ricercatori, docenti, esperti e studiosi, con esclusione di quelli sensibili o giudiziari.
</li>
<li>Resta fermo il diritto dell’interessato di opporsi per motivi legittimi ai sensi dell’articolo 7, comma 4, lettera a).
</li>
<li>I dati di cui al presente articolo non costituiscono documenti amministrativi ai sensi della legge 7 agosto 1990, n. 241.
</li>
<li>I dati di cui al presente articolo possono essere successivamente trattati per i soli scopi in base ai quali sono comunicati o diffusi.
</li>
</ol>
<p><sup>(8)</sup><b>Art. 31 - Obblighi di sicurezza</b></p>
I dati personali oggetto di trattamento sono custoditi e controllati, anche in relazione alle conoscenze acquisite in base al progresso tecnico, alla natura dei dati e alle specifiche caratteristiche del trattamento, in modo da ridurre al minimo, mediante l’adozione di adeguate e preventive misure di sicurezza, i rischi di distruzione o perdita, anche accidentale, dei dati stessi, di accesso non autorizzato o di trattamento non consentito o non conforme alle finalità della raccolta.";
	$compiti7 = "<ol type=\"A\"  start=\"7\">
<li>far osservare gli adempimenti previsti in caso di <b>nuovi trattamenti e cancellazione</b> di trattamenti:
	<ul>
		<li>in particolare, comunicare preventivamente al Titolare l’inizio di ogni attività (trattamento) che deve essere oggetto di notifica al Garante ex art. 37 del Codice<sup>(9)</sup>;</li>
		<li>segnalare al Titolare l’eventuale cessazione di trattamento<sup>(10)</sup>;</li>
	</ul>
</li>
</ol>";
$note7 = "<p><sup>(9)</sup><b>Art.37 - Notificazione del trattamento</b></p>
<ol>
<li>Il titolare notifica al Garante il trattamento di dati personali cui intende procedere, solo se il trattamento riguarda:
	<ol type=\"a\">
		<li>dati genetici, biometrici o dati che indicano la posizione geografica di persone od oggetti mediante una rete di comunicazione elettronica;</li>
		<li>dati idonei a rivelare lo stato di salute e la vita sessuale, trattati a fini di procreazione assistita, prestazione di servizi sanitari per via telematica relativi a banche di dati o alla fornitura di beni, indagini epidemiologiche, rilevazione di malattie mentali, infettive e diffusive, sieropositività, trapianto di organi e tessuti e monitoraggio della spesa sanitaria;</li>
		<li>dati idonei a rivelare la vita sessuale o la sfera psichica trattati da associazioni, enti od organismi senza scopo di lucro, anche non riconosciuti, a carattere politico, filosofico, religioso o sindacale;</li>
		<li>dati trattati con l'ausilio di strumenti elettronici volti a definire il profilo o la personalità dell'interessato, o ad analizzare abitudini o scelte di consumo, ovvero a monitorare l'utilizzo di servizi di comunicazione elettronica con esclusione dei trattamenti tecnicamente indispensabili per fornire i servizi medesimi agli utenti;</li>
		<li>dati sensibili registrati in banche di dati a fini di selezione del personale per conto terzi, nonchè dati sensibili utilizzati per sondaggi di opinione, ricerche di mercato e altre ricerche campionarie;</li>
		<li>dati registrati in apposite banche di dati gestite con strumenti elettronici e relative al rischio sulla solvibilità economica, alla situazione patrimoniale, al corretto adempimento di obbligazioni, a comportamenti illeciti o fraudolenti.</li>
	</ol>
</li>
<li>Il Garante può individuare altri trattamenti suscettibili di recare pregiudizio ai diritti e alle libertà dell'interessato, in ragione delle relative modalità o della natura dei dati personali, con proprio provvedimento adottato anche ai sensi dell'articolo 17. Con analogo provvedimento pubblicato sulla Gazzetta ufficiale della Repubblica italiana il Garante può anche individuare, nell'ambito dei trattamenti di cui al comma 1, eventuali trattamenti non suscettibili di recare detto pregiudizio e pertanto sottratti all'obbligo di notificazione.
</li>
<li>La notificazione è effettuata con unico atto anche quando il trattamento comporta il trasferimento all'estero dei dati.
</li>
<li>Il Garante inserisce le notificazioni ricevute in un registro dei trattamenti accessibile a chiunque e determina le modalità per la sua consultazione gratuita per via telematica, anche mediante convenzioni con soggetti pubblici o presso il proprio Ufficio. Le notizie accessibili tramite la consultazione del registro possono essere trattate per esclusive finalità di applicazione della disciplina in materia di protezione dei dati personali.
</li>
</ol>
<p><sup>(10)</sup><b>Art.16 - Cessazione del trattamento</b></p>
<ol>
<li>In caso di cessazione, per qualsiasi causa, di un trattamento i dati sono:
	<ol type=\"a\">
		<li>distrutti;</li>
		<li>ceduti ad altro titolare, purché destinati ad un trattamento in termini compatibili agli scopi per i quali i dati sono raccolti;</li>
		<li>conservati per fini esclusivamente personali e non destinati ad una comunicazione sistematica o alla diffusione;</li>
		<li>conservati o ceduti ad altro titolare, per scopi storici, statistici o scientifici, in conformità alla legge, ai regolamenti, alla normativa comunitaria e ai codici di deontologia e di buona condotta sottoscritti ai sensi dell'articolo 12.</li>
	</ol>
</li>
<li>La cessione dei dati in violazione di quanto previsto dal comma 1, lettera b), o di altre disposizioni rilevanti in materia di trattamento dei dati personali è priva di effetti.
</li>
</ol>";
	$compiti8 = "<ol type=\"A\"  start=\"8\">
<li>in merito agli <b>Incaricati</b>, il RESPONSABILE deve:
	<ul>
		<li>individuare, tra i propri collaboratori, designandoli per iscritto, gli Incaricati del trattamento<sup>(11)</sup>;</li>
		<li>recepire le istruzioni cui devono attenersi gli Incaricati nel trattamento dei dati impartite dal Titolare, assicurandosi che vengano materialmente consegnate agli stessi o siano già in loro possesso, unitamente al <b>“Regolamento per l’utilizzo e la gestione delle risorse strumentali informatiche e telematiche aziendali”</b> in allegato;</li>
		<li><b>adoperarsi</b> al fine di rendere effettive le suddette istruzioni cui devono attenersi gli incaricati del trattamento, curando in particolare il profilo della riservatezza, della sicurezza di accesso e della integrità dei dati e l’osservanza da parte degli Incaricati, nel compimento delle operazioni di trattamento, dei principi di carattere generale che informano la vigente disciplina in materia;</li>
		<li>stabilire le modalità di <b>accesso</b> ai dati e l’organizzazione del lavoro degli Incaricati, avendo cura di adottare preventivamente le misure organizzative idonee e impartire le necessarie istruzioni ai fini del <b>riscontro</b> di eventuali richieste di esecuzione dei diritti di cui all’art. 7;</li>
		<li>comunicare periodicamente, al Responsabile dei Sistemi Informativi Aziendali, l’elenco nominativo aggiornato degli Incaricati al trattamento con relativi profili autorizzativi per l’accesso alle banche dati di pertinenza;</li>
		<li>comunicare tempestivamente, al Responsabile dei Sistemi Informativi Aziendali, qualsiasi variazione ai profili autorizzativi concessi agli Incaricati per motivi di sicurezza.</li>
	</ul>
</li>
<li>trasmettere le richieste degli interessati al Titolare, ai fini dell’esercizio dei diritti dell’interessato, ai sensi degli artt. 7, 8, 9 e 10<sup>(12)</sup> del D. Lgs. 196/2003;
</li>
</ol>";
$note8 = "<p><sup>(11)</sup><b>Art.30 - Incaricati del trattamento</b></p>
<ol>
<li>Le operazioni di trattamento possono essere effettuate solo da incaricati che operano sotto la diretta autorità del titolare o del responsabile, attenendosi alle istruzioni impartite.
</li>
<li>La designazione è effettuata per iscritto e individua puntualmente l’ambito del trattamento consentito. Si considera tale anche la documentata preposizione della persona fisica ad una unità per la quale è individuato, per iscritto, l’ambito del trattamento consentito agli addetti all’unità medesima.
</li>
</ol>
<p><sup>(12)</sup><b>Art.7 - Diritto di accesso ai dati personali ed altri diritti</b></p>
<ol>
<li>L'interessato ha diritto di ottenere la conferma dell'esistenza o meno di dati personali che lo riguardano, anche se non ancora registrati, e la loro comunicazione in forma intelligibile.
</li>
<li>L'interessato ha diritto di ottenere l'indicazione:
	<ol type=\"a\">
		<li>dell'origine dei dati personali;</li>
		<li>delle finalità e modalità del trattamento;</li>
		<li>della logica applicata in caso di trattamento effettuato con l'ausilio di strumenti elettronici;</li>
		<li>degli estremi identificativi del titolare, dei responsabili e del rappresentante designato ai sensi dell'articolo 5, comma 2;</li>
		<li>dei soggetti o delle categorie di soggetti ai quali i dati personali possono essere comunicati o che possono venirne a conoscenza in qualità di rappresentante designato nel territorio dello Stato, di responsabili o incaricati.</li>
	</ol>
</li>
<li>L'interessato ha diritto di ottenere:
	<ol type=\"a\">
		<li>l'aggiornamento, la rettificazione ovvero, quando vi ha interesse, l'integrazione dei dati;</li>
		<li>la cancellazione, la trasformazione in forma anonima o il blocco dei dati trattati in violazione di legge, compresi quelli di cui non è necessaria la conservazione in relazione agli scopi per i quali i dati sono stati raccolti o successivamente trattati;</li>
		<li>l'attestazione che le operazioni di cui alle lettere a) e b) sono state portate a conoscenza, anche per quanto riguarda il loro contenuto, di coloro ai quali i dati sono stati comunicati o diffusi, eccettuato il caso in cui tale adempimento si rivela impossibile o comporta un impiego di mezzi manifestamente sproporzionato rispetto al diritto tutelato.</li>
	</ol>
</li>
<li>L'interessato ha diritto di opporsi, in tutto o in parte:
	<ol type=\"a\">
		<li>per motivi legittimi al trattamento dei dati personali che lo riguardano, ancorchè pertinenti allo scopo della raccolta;</li>
		<li>al trattamento di dati personali che lo riguardano a fini di invio di materiale pubblicitario o di vendita diretta o per il compimento di ricerche di mercato o di comunicazione commerciale.</li>
	</ol>
</li>
</ol>
<p><b>Art.8 - Esercizio dei diritti</b></p>
<ol>
<li>I diritti di cui all'articolo 7 sono esercitati con richiesta rivolta senza formalità al titolare o al responsabile, anche per il tramite di un incaricato, alla quale è fornito idoneo riscontro senza ritardo.
</li>
<li>I diritti di cui all'articolo 7 non possono essere esercitati con richiesta al titolare o al responsabile o con ricorso ai sensi dell'articolo 145, se i trattamenti di dati personali sono effettuati:
	<ol type=\"a\">
		<li>in base alle disposizioni del decreto-legge 3 maggio 1991, n. 143, convertito, con modificazioni, dalla legge luglio 1991, n. 197,e successive modificazioni, in materia di riciclaggio;</li>
		<li>in base alle disposizioni del decreto-legge 31 dicembre 1991,n. 419, convertito, con modificazioni, dalla legge 18 febbraio 1992,n. 172, e successive modificazioni, in materia di sostegno alle vittime di richieste estorsive;</li>
		<li>da Commissioni parlamentari d'inchiesta istituite ai sensi dell'articolo 82 della Costituzione;</li>
		<li>da un soggetto pubblico, diverso dagli enti pubblici economici,in base ad espressa disposizione di legge, per esclusive finalità inerenti alla politica monetaria e valutaria, al sistema dei pagamenti, al controllo degli intermediari e dei mercati creditizi e finanziari, nonchè alla tutela della loro stabilità;</li>
		<li>ai sensi dell'articolo 24, comma 1, lettera f), limitatamente al periodo durante il quale potrebbe derivarne un pregiudizio effettivo e concreto per lo svolgimento delle investigazioni difensive o per l'esercizio del diritto in sede giudiziaria;</li>
		<li>da fornitori di servizi di comunicazione elettronica accessibili al pubblico relativamente a comunicazioni telefoniche in entrata, salvo che possa derivarne un pregiudizio effettivo e concreto per lo svolgimento delle investigazioni difensive di cui alla legge 7 dicembre 2000, n. 397;</li>
		<li>per ragioni di giustizia, presso uffici giudiziari di ogni ordine e grado o il Consiglio superiore della magistratura o altri organi di autogoverno o il Ministero della giustizia;</li>
		<li>ai sensi dell'articolo 53, fermo restando quanto previsto dalla legge 1 aprile 1981, n. 121.</li>
	</ol>
</li>
<li>Il Garante, anche su segnalazione dell'interessato, nei casi dicui al comma 2, lettere a), b), d), e) ed f) provvede nei modi di cui agli articoli 157, 158 e 159 e, nei casi di cui alle lettere c), g) ed h) del medesimo comma, provvede nei modi di cui all'articolo 160.
</li>
<li>L'esercizio dei diritti di cui all'articolo 7, quando non riguarda dati di carattere oggettivo, può avere luogo salvo che concerna la rettificazione o l'integrazione di dati personali di tipo valutativo, relativi a giudizi, opinioni o ad altri apprezzamenti di tipo soggettivo, nonché l'indicazione di condotte da tenersi o di decisioni in via di assunzione da parte del titolare del trattamento.
</li>
</ol>
<p><b>Art.9 - Modalità di esercizio</b></p>
<ol>
<li>La richiesta rivolta al titolare o al responsabile può essere trasmessa anche mediante lettera raccomandata, telefax o posta elettronica. Il Garante può individuare altro idoneo sistema in riferimento a nuove soluzioni tecnologiche. Quando riguarda l'esercizio dei diritti di cui all'articolo 7, commi 1 e 2, la richiesta può essere formulata anche oralmente e in tal caso è annotata sinteticamente a cura dell'incaricato o del responsabile.
</li>
<li>Nell'esercizio dei diritti di cui all'articolo 7 l'interessato può conferire, per iscritto, delega o procura a persone fisiche, enti, associazioni od organismi. L'interessato può, altresì, farsi assistere da una persona di fiducia.
</li>
<li>I diritti di cui all'articolo 7 riferiti a dati personali concernenti persone decedute possono essere esercitati da chi ha un interesse proprio, o agisce a tutela dell'interessato o per ragioni familiari meritevoli di protezione.
</li>
<li>L'identità dell'interessato è verificata sulla base di idonei elementi di valutazione, anche mediante atti o documenti disponibili o esibizione o allegazione di copia di un documento di riconoscimento. La persona che agisce per conto dell'interessato esibisce o allega copia della procura, ovvero della delega sottoscritta in presenza di un incaricato o sottoscritta e presentata unitamente a copia fotostatica non autenticata di un documento di riconoscimento dell'interessato. Se l'interessato è una persona giuridica, un ente o un'associazione, la richiesta è avanzata dalla persona fisica legittimata in base ai rispettivi statuti od ordinamenti.
</li>
<li>La richiesta di cui all'articolo 7, commi 1 e 2, è formulata liberamente e senza costrizioni e può essere rinnovata, salva l'esistenza di giustificati motivi, con intervallo non minore di novanta giorni.
</li>
</ol>
<p><b>Art.10 - Riscontro all'interessato</b></p>
<ol>
<li>Per garantire l'effettivo esercizio dei diritti di cui all'articolo 7 il titolare del trattamento è tenuto ad adottare idonee misure volte, in particolare:
	<ol type=\"a\">
		<li>ad agevolare l'accesso ai dati personali da parte dell'interessato, anche attraverso l'impiego di appositi programmi per elaboratore finalizzati ad un'accurata selezione dei dati che riguardano singoli interessati identificati o identificabili;
		</li>
		<li>a semplificare le modalità e a ridurre i tempi per il riscontro al richiedente, anche nell'ambito di uffici o servizi preposti alle relazioni con il pubblico.
		</li>
	</ol>
</li>
<li>I dati sono estratti a cura del responsabile o degli incaricati e possono essere comunicati al richiedente anche oralmente, ovvero offerti in visione mediante strumenti elettronici, sempre che in tali casi la comprensione dei dati sia agevole, considerata anche la qualità e la quantità delle informazioni. Se vi è richiesta, si provvede alla trasposizione dei dati su supporto cartaceo o informatico, ovvero alla loro trasmissione per via telematica.
<li>Salvo che la richiesta sia riferita ad un particolare trattamento o a specifici dati personali o categorie di dati personali, il riscontro all'interessato comprende tutti i dati personali che riguardano l'interessato comunque trattati dal titolare. Se la richiesta è rivolta ad un esercente una professione sanitaria o ad un organismo sanitario si osserva la disposizione di cui all'articolo 84, comma 1.
</li>
<li>Quando l'estrazione dei dati risulta particolarmente difficoltosa il riscontro alla richiesta dell'interessato può avvenire anche attraverso l'esibizione o la consegna in copia di atti e documenti contenenti i dati personali richiesti.
</li>
<li>Il diritto di ottenere la comunicazione in forma intelligibile dei dati non riguarda dati personali relativi a terzi, salvo che la scomposizione dei dati trattati o la privazione di alcuni elementi renda incomprensibili i dati personali relativi all'interessato.
</li>
<li>La comunicazione dei dati è effettuata in forma intelligibile anche attraverso l'utilizzo di una grafia comprensibile. In caso di comunicazione di codici o sigle sono forniti, anche mediante gli incaricati, i parametri per la comprensione del relativo significato.
</li>
<li>Quando, a seguito della richiesta di cui all'articolo 7, commi1 e 2, lettere a), b) e c) non risulta confermata l'esistenza di dati che riguardano l'interessato, può essere chiesto un contributo spese non eccedente i costi effettivamente sopportati per la ricerca effettuata nel caso specifico.
</li>
<li>Il contributo di cui al comma 7 non può comunque superare l'importo determinato dal Garante con provvedimento di carattere generale, che può individuarlo forfettariamente in relazione al caso in cui i dati sono trattati con strumenti elettronici e la risposta è fornita oralmente. Con il medesimo provvedimento il Garante può prevedere che il contributo possa essere chiesto quando i dati personali figurano su uno speciale supporto del quale è richiesta specificamente la riproduzione, oppure quando, presso uno o più titolari, si determina un notevole impiego di mezzi in relazione alla complessità o all'entità delle richieste ed è confermata l'esistenza di dati che riguardano l'interessato.
</li>
<li>Il contributo di cui ai commi 7 e 8 è corrisposto anche mediante versamento postale o bancario, ovvero mediante carta di pagamento o di credito, ove possibile all'atto della ricezione del riscontro e comunque non oltre quindici giorni da tale riscontro.
</li>
</li>
</ol>
";
	$compiti9 = "<ol type=\"A\"  start=\"10\">
<li>collaborare con il Titolare per l’evasione delle richieste degli interessati ai sensi dell’art. 10 del D. Lgs. 196/2003 e delle istanze del Garante per la protezione dei dati personali;
</li>
<li><b>comunicare al Titolare</b> i contatti di Terzi (persone fisiche, persone giuridiche, associazioni) indicando le esatte generalità, comprensive di Codice Fiscale e/o Partita I.V.A., delibera di nomina/convenzione e oggetto della prestazione, che trattano dati personali negli ambiti di competenza. A seguito di detta comunicazione, il Titolare provvederà, se del caso, a <b>nominarli Responsabili esterni del trattamento</b>;
</li>
<li>collaborare con il Titolare provvedendo a fornire ogni informazione dal medesimo richiesta;
</li>
<li>comunicare tempestivamente al Titolare ogni notizia rilevante ai fini della tutela della riservatezza;
</li>
<li><b>comunicare</b> tempestivamente al Titolare eventuali <b>violazioni dei dati</b> (distruzione, perdita, divulgazione illecita o accesso non autorizzato) per i conseguenti adempimenti verso il Garante;
</li>
</ol>
<p><b>Il Responsabile del trattamento risponde al Titolare per ogni violazione o mancata attivazione di quanto previsto dalla normativa in materia di tutela dei dati personali relativamente al settore di competenza. Resta fermo, in ogni caso, che la responsabilità penale per l’eventuale uso non corretto dei dati oggetto di tutela è a carico della singola persona cui l’uso illegittimo sia imputabile;</b></p>
<p><b>L’incarico di Responsabile del trattamento dei dati è attribuito personalmente e non è suscettibile di delega. Esso decade alla revoca dell’incarico di responsabilità affidato.<br>Infine si invita la S.V. a prendere visione completa dell “Regolamento per l’utilizzo e la gestione delle risorse strumentali informatiche e telematiche aziendali” che integra il presente documento.</b></p>
<p>Per tutto quanto non espressamente previsto nel presente atto, si rinvia alle disposizioni generali vigenti in materia di protezione dei dati personali.</p>";

        $this->SetFont('courier','',9);
		$this->y=$this->GetY();
        $this->WriteHTMLCell(184,4,10,$this->y-20,$premessa, 0, 1, 0, true, 'J');
        $this->SetFont('courier','B',12);
        $this->Cell(184,4,'NOMINA', 0, 1, 'C');
		$this->y=$this->GetY();
        $this->SetFont('courier','',9);
        $this->WriteHTMLCell(184,4,10,$this->y+3,$nomina, 0, 1, 0, true, 'J');
        $this->AddPage();
		$this->Ln(-20);
        $this->SetFont('courier','B',14);
        $this->MultiCell(184,4,"Compiti ed istruzioni PER I RESPONSABILI\nDEL TRATTAMENTO DEI DATI PERSONALI", 0, 'C', 0, 1);
        $this->SetFont('courier','',11);
        $this->MultiCell(184,4,"in applicazione del ‘Codice in materia di protezione dei dati personali’ (D.Lgs. 196/2003) e del considerando art. 28 del Regolamento UE 2016/679", 0, 'L', 0, 1);
        $this->SetFont('courier','B',12);
        $this->Ln(2);
        $this->MultiCell(104,4,"PRINCIPI GENERALI DA OSSERVARE", 'B', 'L', 0, 1);
		$this->y=$this->GetY();
        $this->SetFont('courier','',10);
        $this->WriteHTMLCell(184,4,10,95,$compiti1, 'B', 1, 0, true, 'J');
		$this->y=$this->GetY();
        $this->SetFont('courier','',7);
        $this->WriteHTMLCell(184,4,10,$this->y+2,$note1, 0, 1, 0, true, 'J');
        $this->AddPage();
		$this->y=$this->GetY();
        $this->SetFont('courier','',10);
        $this->WriteHTMLCell(184,4,10,$this->y-20,$compiti2, 'B', 1, 0, true, 'J');
		$this->y=$this->GetY();
        $this->SetFont('courier','',7);
        $this->WriteHTMLCell(184,4,10,$this->y+2,$note2, 0, 1, 0, true, 'J');
        $this->AddPage();
		$this->y=$this->GetY();
        $this->SetFont('courier','',10);
        $this->WriteHTMLCell(184,4,10,$this->y-20,$compiti3, 0, 1, 0, true, 'J');
        $this->SetFont('courier','B',12);
        $this->Ln(2);
        $this->MultiCell(104,4,"COMPITI PARTICOLARI DEL RESPONSABILE", 'B', 'L', 0, 1);
		$this->y=$this->GetY();
        $this->SetFont('courier','',10);
        $this->WriteHTMLCell(184,4,10,$this->y+2,$compiti4, 'B', 1, 0, true, 'J');
		$this->y=$this->GetY();
        $this->SetFont('courier','',7);
        $this->WriteHTMLCell(184,4,10,$this->y+2,$note4, 0, 1, 0, true, 'J');
        $this->AddPage();
        $this->SetFont('courier','',10);
        $this->WriteHTMLCell(184,4,10,65,$compiti5, 'B', 1, 0, true, 'J');
		$this->y=$this->GetY();
        $this->SetFont('courier','',7);
        $this->WriteHTMLCell(184,4,10,$this->y+2,$note5, 0, 1, 0, true, 'J');
        $this->AddPage();
        $this->SetFont('courier','',10);
        $this->WriteHTMLCell(184,4,10,65,$compiti6, 'B', 1, 0, true, 'J');
		$this->y=$this->GetY();
        $this->SetFont('courier','',7);
        $this->WriteHTMLCell(184,4,10,$this->y+2,$note6, 0, 1, 0, true, 'J');
        $this->AddPage();
        $this->SetFont('courier','',10);
        $this->WriteHTMLCell(184,4,10,65,$compiti7, 'B', 1, 0, true, 'J');
		$this->y=$this->GetY();
        $this->SetFont('courier','',7);
        $this->WriteHTMLCell(184,4,10,$this->y+2,$note7, 0, 1, 0, true, 'J');
        $this->AddPage();
        $this->SetFont('courier','',10);
        $this->WriteHTMLCell(184,4,10,65,$compiti8, 'B', 1, 0, true, 'J');
		$this->y=$this->GetY();
        $this->SetFont('courier','',7);
        $this->WriteHTMLCell(184,4,10,$this->y+2,$note8, 0, 1, 0, true, 'J');
        $this->AddPage();
        $this->SetFont('courier','',10);
        $this->WriteHTMLCell(184,4,10,65,$compiti9, 0, 1, 0, true, 'J');
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
