<div class="panel panel-default panel-body panel-help">
    <h3>GAzie - gestione aziendale</h3>
    <p> GAzie è un programma gestionale multiaziendale eseguibile su web server Apache con il supporto per il PHP e database Mysql.<br>
        Con esso è possibile emettere ogni tipo di documento di vendita (DdT, Fatture Immediate, Fatture Differite, Note di Credito e Debito, Parcelle, Contratti, ecc.) ed eventalmente i relativi effetti (Ri.Ba elettroniche secondo lo standard CBI, Cambiali-tratte) per il loro pagamento.<br>
        Emette le fatture verso la pubblica amministrazione anche in formato elettronico (XML).<br>
        Gli scontrini fiscali possono essere inviati direttamente al registratore di cassa e/o stampante fiscale.<br>

        Lo scadenzario a partite aperte di clienti e fornitori agevola i rapporti commerciali oltre che consentire un maggiore controllo delle posizioni creditorie/debitorie e delle registrazioni contabili.<br>

        La contabilità IVA e la prima nota sono gestite in maniera da permettere l'introduzione e la correzione dei movimenti contabili senza limitazioni di data o altri vincoli.<br>

La visualizzazione e la stampa del bilancio (periodico o annuale, in formato classico o riclassificato CEE) può essere fatta anche dopo la chiusura e riapertura dei conti.<br>

GAzie emette tutti i libri obbligatori per la contabilità semplificata, ordinaria e di magazzino di una piccola/media azienda.<br>

Ogni operazione di stampa cartacea genera comunque il documento in formato PDF.<br>

Dalla versione 6.4 si possono generare tutti i file necessari alla creazione e aggiornamento dei siti web statici di tutte le aziende presenti sull'installazione oltre che fare l'upload degli stessi sui relativi server web. Pertanto tutti i dati aziendali e il listino degli articoli di magazzino completo di immagini possono essere sincronizzati con il sito creato e aggiornato con un semplice click.<br>

Come ogni software libero lascia la possibilità di modificare i sorgenti e la piena libertà di adattare o aggiungere moduli per soddisfare le vostre necessità. Un programmatore può modellarlo "a misura" per coprire le molteplici, spesso imprevedibili, e particolarissime esigenze di molte aziende o professionisti.<br>
</p>
        
            <p>Sui monitor ampi il menù principale si ottiene spostando il puntatore del mouse sopra la voce <strong>Home</strong> in alto a sinistra, su quelli piccoli (smartphone) cliccando sul <strong>bottone</strong> che appare in alto a sinistra.<!-- Si veda
eventualmente questo breve <a title="guarda il video" href="help/italian/home_menu.ogv">filmato</a>.-->
            </p>

            <p>Per una guida generale introduttiva a GAzie si possono consultare alcuni capitoli tratti da &laquo;<big>a</big>2&raquo; <a href="http://www.archive.org/details/AppuntiDiInformaticaLibera" target="new">Appunti Di Informatica Libera</a>: <a
title="leggi la guida" href="http://appuntilinux.mirror.garr.it/mirrors/appuntilinux/a2/installazione_e_manutenzione_generale.pdf" target="_new"><em>Installazione e manutenzione generale</em></a>, <a title="leggi la guida" href="http://appuntilinux.mirror.garr.it/mirrors/appuntilinux/a2/manuale_sintetico_delle_funzionalita_principali_di_gazie.pdf" target="_new"><em>Manuale sintetico delle funzionalit&agrave; di Gazie</em></a>, <a title="leggi la guida" href="http://appuntilinux.mirror.garr.it/mirrors/appuntilinux/a2/tabelle_principali_della_base_di_dati_di_gazie.pdf" target="_new">
<em>Tabelle principali della base di dati di Gazie</em></a>, <a title="leggi la guida" href="http://appuntilinux.mirror.garr.it/mirrors/appuntilinux/a2/esercitazioni_con_gazie.pdf" target="_new">
<em>Esercitazioni con Gazie</em></a>. Si veda eventualmente anche questa <a title="leggi l'appendice" href="http://appuntilinux.mirror.garr.it/mirrors/appuntilinux/a2/gazie_non_e_un_giocattolo.htm" target="_new">appendice</a>
se si cerca assistenza.
            </p>
</div>


<ul class="nav nav-tabs">
   <li class="active"><a data-toggle="tab" href="#vendite">Vendite</a></li>
   <li ><a data-toggle="tab" href="#acquisti">Acquisti</a></li>
   
</ul>

<div class="tab-content contenuto-help">
    <div id="vendite" class="tab-pane fade in active">
		<p class="help-text">Da qui &egrave; possibile gestire le fatture di vendita, le note
		di credito e le note di debito a clienti, oltre che le parcelle. Per
		le fatture di vendita immediate (nel senso che non derivano da
		documenti di trasporto), si deve scegliere la voce <strong>Emetti
		fattura</strong>; per  le note di credito o di debito, vanno usate
		rispettivamente le voci <strong>Emetti nota credito</strong> e
		<strong>Emetti nota debito</strong>; per le fatture
		differite, va invece scelta la voce <strong>Fatturazione da
		D.d.t.</strong></p>

		<p class="help-text">Le fatture immediate, normali o accompagnatorie che siano, e le
		note di credito, in condizioni normali comportano anche
		l'aggiornamento dei movimenti di magazzino con gli scarichi o i
		carichi relativi.</p>

		<p class="help-text">Esempi: <br/>
		- <a target="_blank" title="guarda il video"
		href="http://www.youtube.com/watch?v=QL3NBjXj2Ro">emissione di una
		fatture immediata normale</a><br/>
		- <a target="_blank" title="guarda il video"
		href="http://www.youtube.com/watch?v=MY_P9kusXnU">contabilizzazione
		della fattura emessa</a><br/>
		- <a target="_blank" title="guarda il
		video" href="http://www.youtube.com/watch?v=w8N4VDrbG9U">emissione di una
		nota di accredito</a><br/>
		- <a target="_blank" title="guarda il
		video" href="http://www.youtube.com/watch?v=QC-VADDI7hs">contabilizzazione
		della nota di accredito</a><br/>
		</p>
	</div>

    <div id="acquisti" class="tab-pane fade in">
	<p class="help-text">Da qui &egrave; possibile emettere dei preventivi a clienti,
	attraverso una maschera di inserimento equivalente a quella di una
	fattura immediata normale. Il preventivo ha poi un aspetto finale
	molto simile a quello di una fattura.</p>

	<p class="help-text">Per inserire un nuovo preventivo si deve scegliere la voce
	<strong>Preventivo a cliente</strong>, mentre per accedere ai
	preventivi già emessi in precedenza, si deve selezionare la voce
	principale di questo men&ugrave;. Va osservato che il fatto di avere
	emesso un preventivo non comporta automatismi nei confronti del
	resto della gestione delle vendite.</p>
    </div>

</div>