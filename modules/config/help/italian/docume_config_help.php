<div class="panel panel-default panel-body panel-help">
	<h3>Men&ugrave; Archivi di base</h3>
	<p>Qui si raccolgono le
	funzioni relative alla gestione delle tabelle che riguardano la procedura
	nel complesso. Tuttavia, non tutte le tabelle di codifica dei dati sono
	raccolte in questa funzionalit&agrave;; per esempio, il piano dei conti,
	i clienti e i fornitori, si trovano raccolti in contesti differenti.
	Negli &laquo;archivi di base&raquo; la tabella pi&ugrave; importante
	&egrave; quella che descrive i dati principali dell'azienda, con
	l'associazione ad altre tabelle, come il piano dei conti, per
	l'esecuzione di operazioni automatiche.</p>
	
	<p>Una caratteristica importante e particolare della gestione di Gazie
	&egrave; costituita dal modo in cui vengono numerati i documenti. Gazie
	numera i documenti relativi alla vendita (DDT, fatture e note di
	acredito o di addebito) in modo indipendente; pertanto, la numerazione
	che deve poi apparire nei registri IVA (in tal caso i DDT non vengono
	considerati) deve essere riattribuita con un numero di protocollo. In
	altri termini, il numero di emissione dei documenti della vendita non
	pu&ograve; essere equivalente al numero di protocollo del registro
	IVA.</p>

	<p>I registri IVA possono essere suddivisi in serie. Convenzionalmente,
	la serie di numerazione dei registri si effettua con una lettera
	alfabetica, per cui il documento 21/C delle vendite &egrave; il
	ventunesimo del registro IVA &laquo;C&raquo; delle vendite. Gazie
	per&ograve; definisce le serie di numerazione come &laquo;sezioni&raquo;
	e le numera con una cifra numerica. Pertanto, il documento 21/C viene
	rappresentato come 21/3. Inoltre, con GAzie la serie (la sezione) appare
	sempre e non &egrave; possibile farne a meno se se ne intende gestire
	una sola.</p>
</div>

<ul class="nav nav-tabs">
   <li class="active"><a data-toggle="tab" href="#Azienda">Azienda</a></li>
   <li ><a data-toggle="tab" href="#CC">C/C Bancari</a></li>
   <li ><a data-toggle="tab" href="#Aliquote">Aliquota IVA</a></li>
   <li ><a data-toggle="tab" href="#Pagamenti">Pagamenti</a></li>
   <li ><a data-toggle="tab" href="#Banche">Banche</a></li>
   <li ><a data-toggle="tab" href="#Spedizioni">Spedizioni</a></li>
   <li ><a data-toggle="tab" href="#Utenti">Utenti</a></li>
</ul>

<div class="tab-content contenuto-help">
    <div id="Azienda" class="tab-pane fade in active">
		<p class="help-text">La voce principale di questo men&ugrave; consente di accedere
		alla scheda di configurazione dei dati anagrafici dell'azienda e
		tante altre informazioni, collegate alle altre tabelle di
		configurazione. Si tratta della parte della configurazione più
		importante e delicata di tutto il sistema di gestione. I dati in
		questione si riferiscono all'azienda attiva. Eventualmente, con la
		voce <strong>Lista aziende installate</strong>, &egrave; possibile
		accedere all'elenco delle aziende esistenti per intervenire nei dati
		di un'azienda non attiva.</p>
		
		<p class="help-text">La voce <strong>Crea nuova azienda</strong> consente di creare
		una nuova azienda, condividendo il piano dei conti ed eventualmente
		gli altri &laquo;archivi di base&raquo;.</p>
	</div>


    <div id="CC" class="tab-pane fade in">
	    <p class="help-text">I conti correnti bancari a cui si accede attraverso questa voce
		di men&ugrave; sono quelli intrattenuti dall'azienda gestita con i
		rispettivi istituti di credito. Le banche presso le quali sono
		gestiti tali conti correnti, dovrebbero essere gi&agrave; elencate
		tra le banche di appoggio. Selezionando la voce principale del
		men&ugrave; si ottiene l'elenco dei conti, mentre per inserire un
		nuovo rapporto si aggiunge la voce <strong>Nuovo conto corrente
		bancario</strong>.</p>

		<p class="help-text">I conti correnti bancari definiti in questo modo, appartengono in
		pratica ai conti della contabilit&agrave; generale, nel mastro
		stabilito per questo nella tabella che descrive i dati principali
		dell'azienda. In altri termini, i codici dei conti correnti bancari
		sono gli stessi usati poi nel piano dei conti.</p>

		<p class="help-text">Nell'elenco dei conti correnti bancari che si ottiene
		selezionando la voce principale di questo men&ugrave;, c'&egrave;
		una colonna denominata &laquo;Anteprima&raquo; contiene dei
		riferimenti ipertestuali che portano alla visualizzazione del
		partitario (mastrino) del conto bancario relativo, ottenuto in
		pratica dalle scritture presenti in contabilità generale.
	</div>

    <div id="Aliquote" class="tab-pane fade in">
		<p class="help-text">La voce principale di questo men&ugrave; porta alla tabella dei
		vari tipi di IVA utilizzabili. Eventualmente, la selezione della
		voce <strong>Nuova aliquota IVA</strong> porta alla maschera di
		inserimento di una nuova aliquota.</p>
	</div>

	<div id="Pagamenti" class="tab-pane fade in">
		<p class="help-text">La voce principale di questo men&ugrave; porta all'elenco dei
		vari tipi di pagamento utilizzabili, in relazione ai dati da
		inserire nelle fatture. Eventualmente, la voce <strong>Nuova
		condizione di pagamento</strong> porta alla maschera di inserimento
		di un nuovo tipo da inserire.</p>
	</div>

	<div id="Banche" class="tab-pane fade in">
	    <p class="help-text">La voce principale di questo men&ugrave; porta all'elenco delle
		banche di appoggio, ovvero le banche dei clienti, contenente le
		informazioni necessarie per la compilazione delle fatture di vendita
		e per l'emissione degli effetti attivi. Per inserire una nuova banca
		di appoggio si deve scegliere invece la voce <strong>Nuova banca di
		appoggio</strong>.
	</div>

    <div id="Spedizioni" class="tab-pane fade in">
		<p class="help-text"><strong>Spedizioni</strong>, <strong>Vettori</strong>, <strong>Imballi</strong>, <strong>Porti</strong> Questi men&ugrave; pemettono di intervenire negli elenchi dei
		tipi di spedizione, dei vettori, dei tipi di imballo e dei tipi di
		porto o resa, rispettivamente. Si tratta pertanto di informazioni
		relative all'accompagnamento delle merci trasportate.</p>
	</div>

	<div id="Utenti" class="tab-pane fade in">
		<p class="help-text">A partire da questa voce del men&ugrave; &egrave; possibile
		accedere all'elenco degli utenti della procedura, o alla maschera
		di inserimento di un nuovo utente. GAzie richiede che sia presente
		almeno l'utente <strong>amministratore</strong>, ma ne possono
		essere definiti altri, con minori privilegi.</p>
	</div>
</div>
