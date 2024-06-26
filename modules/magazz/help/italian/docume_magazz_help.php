<div class="panel panel-default panel-body panel-help">
<h3>Men&ugrave; Magazzino</h3>
<p>Qui si raccolgono le
funzioni relative alla gestione della contabilit&agrave; di magazzino,
incluse le stampe periodiche.</p>

<p>Per accedere agli elenchi degli articoli, delle categorie
merceologiche, dei movimenti e delle causali, basta selezionare la voce
principale relativa: <strong>Articoli</strong>, <strong>Cat.
merceologiche</strong>, <strong>Movimenti</strong>,
<strong>Causali</strong>, tralasciando le voci di men&ugrave; ulteriori
che appaiono mentre il puntatore del mouse vi si sposta sopra.</p>
</div>

<ul class="nav nav-tabs">
   <li class="active"><a data-toggle="tab" href="#Articoli">Articoli</a></li>
   <li ><a data-toggle="tab" href="#CatMerc">Categorie Merceologiche</a></li>
<!--inizio modifica FP 15/10/2015-->
   <li ><a data-toggle="tab" href="#RagStat">Raggruppamenti Statistici</a></li>
<!--fine modifica FP-->
   <li ><a data-toggle="tab" href="#Movimenti">Movimenti</a></li>
   <li ><a data-toggle="tab" href="#Causali">Causali</a></li>
   <li ><a data-toggle="tab" href="#Listini">Listini</a></li>
   <li ><a data-toggle="tab" href="#Prezzi">Modifica Prezzi</a></li>
   <li ><a data-toggle="tab" href="#Catalogo">Catalogo</a></li>
   <li ><a data-toggle="tab" href="#Statistiche">Statistiche</a></li>
</ul>

<div class="tab-content contenuto-help">
	<div id="Articoli" class="tab-pane fade in active">	
		<p class="help-text">Da qui &egrave; possibile intervenire nell'elenco degli articoli
		di magazzino. Per inserire un nuovo articolo si deve scegliere la voce
		<strong>Nuovo articolo di magazzino</strong>, mentre per accedere agli
		articoli gi&agrave; esistenti, si deve selezionare la voce principale di
		questo men&ugrave;.</p>
	</div>

	<div id="CatMerc" class="tab-pane fade in">
		<p class="help-text">Gli articoli di magazzino sono raggruppati in categorie, a cui
		si accede da questa voce di men&ugrave;. Se non si intende suddividere
		gli articoli in gruppi, &egrave; comunque necessario definire almeno
		una categoria generica.</p>

		<p class="help-text">Per inserire una nuova categoria si deve scegliere la voce
		<menuitem>Nuova categoria merceologica</menuitem>, mentre per accedere
		alle categorie gi&agrave; esistenti, si deve selezionare la voce
		principale di questo men&ugrave;.</p>
	</div>

    <!--inizio modifica FP 15/10/2015-->
	<div id="RagStat" class="tab-pane fade in">
		<p class="help-text">Gli articoli di magazzino sono raggruppati in raggruppamenti statistici, a cui
		si accede da questa voce di men&ugrave;. Se non si intende suddividere
		gli articoli in gruppi, &egrave; comunque necessario definire almeno
		un raggruppamento generico.</p>

		<p class="help-text">Per inserire un nuovo raggruppamento si deve scegliere la voce
		<menuitem>Nuovo raggruppamento statistico</menuitem>, mentre per accedere
		ai raggruppamenti gi&agrave; esistenti, si deve selezionare la voce
		principale di questo men&ugrave;.</p>
	</div>
<!--fine modifica FP-->
	<div id="Movimenti" class="tab-pane fade in">
		<p class="help-text">Da qui &egrave; possibile intervenire nei movimenti di magazzino
		(scritture di carico e scarico). Per inserire un nuovo movimento si
		deve scegliere la voce <strong>Nuovo movimento di
		magazzino</strong>, mentre per accedere ai movimenti gi&agrave;
		esistenti, si deve selezionare la voce principale di questo
		men&ugrave;.</p>

		<p class="help-text">Dai movimenti inseriti &egrave; possibile ottenere la stampa del
		giornale di magazzino attraverso la voce <menuitem>Stampa giornale
		di magazzino</menuitem>, mentre per ottenere una stampa dettagliata,
		articolo per articolo, si deve scegliere la voce <menuitem>Stampa
		schedari articoli di magazzino.</p>

		<!-- <p>In molti casi, i movimenti di magazzino possono essere generati
		automaticamente, a seguito dell'inserimento di documenti che ne
		rappresentano implicitamente il movimento (per esempio nel caso dell'emissione
		dei documenti di trasporto o di fatture). In condizioni normali, la produzione
		di questi movimenti automatici avviene senza alcuna richiesta esplicita; tuttavia,
		&egrave; possibile configurare GAzie in modo che non lo faccia: in questa
		evenienza particolare, si pu&ograve; rendere utile la voce
		<strong>Genera movimenti di magazzino</strong> per ottenere queste
		registrazioni.</p> -->

		<p class="help-text">Esempi: <br>- <a target="_blank" title="guarda il video"
		href="http://www.youtube.com/watch?v=qnypB8B8nH4">carico per
		acquisto</a><br>
		- <a target="_blank" title="guarda il video"
		href="http://www.youtube.com/watch?v=Wm9Ix2IboiI">scarico per
		vendita</a><br>- <a target="_blank" title="guarda il video"
		href="http://www.youtube.com/watch?v=GIpPRKImO34">carico per
		acquisto</a><br>- <a target="_blank" title="guarda il video"
		href="http://www.youtube.com/watch?v=fyvXMJuCl-g">scarico per
		vendita</a><br>- <a target="_blank" title="guarda il video"
		href="http://www.youtube.com/watch?v=kyV8HJ6cDAE">scarico per
		vendita</a><br>- <a target="_blank" title="guarda il video"
		href="http://www.youtube.com/watch?v=vc5kDenlgHo">stampa del giornale di magazzino</a></p>
	</div>

	<div id="Causali" class="tab-pane fade in">
		<p class="help-text">Da qui &egrave; possibile gestire le causali usate per descrivere
		i movimenti di magazzino. Le causali predefinite, riconoscibili
		perch&eacute; sono scritte con un testo tutto maiuscolo, non vanno
		modificate, essendo utilizzate automaticamente dalla procedura;
		tuttavia, si possono creare altre causali, in base alla propria
		convenienza.</p>

		<p class="help-text">Per inserire una nuova cusale si deve scegliere la voce
		<menuitem>Nuova causale di magazzino</menuitem>, mentre per accedere
		alle causali gi&agrave; esistenti, si deve selezionare la voce
		principale di questo men&ugrave;.</p>
	</div>

	<div id="Listini" class="tab-pane fade in">
		<p class="help-text">Da qui si ottiene la stampa del listino di vendita, dove viene
		comunque richiesto di selezionare quale listino evidenziare. Sono
		disponibili quattro prezzi di vendita alternativi, ma eventualmente,
		se l'informazione risulta disponibile, &egrave; possibile anche
		stampare il prezzo dell'ultimo acquisto.</p>
	</div>

    <div id="Prezzi" class="tab-pane fade in">
		<p class="help-text">Quando si deve procedere alla modifica sistematica dei prezzi di
		listino di vendita degli articoli, attraverso questa funzione è
		possibile attribuire un incremento fisso o percentuale, rispetto al
		prezzo unitario annotato in precedenza. Si osservi che se il
		cambiamento riguarda uno solo o pochi articoli, conviene intervenire
		per questo tipo di modifica direttamente nell'elenco degli
		articoli.</p>
	</div>

	<div id="Catalogo" class="tab-pane fade in">
		<p class="help-text">Da qui &egrave; possibile ottenere la stampa di un catalogo degli
		articoli di magazzino, scegliendo uno dei prezzi di listino di vendita
		disponibili.</p>

	</div>

    <div id="Statistiche" class="tab-pane fade in">
		<p class="help-text">Da qui &egrave; possibile ottenere un grafico con l'entit&agrave;
		degli scarichi per vendita, o dei carichi per acquisto, suddiviso
		per articolo.</p>
	</div>
</div>
