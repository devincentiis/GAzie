<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/

$strScript = array("camp_browse_document.php" =>
    array('title' => "Lista dei Documenti/Certificati",
        'errors' => array('Il formato del file non è stato accettato!',
            'Il file è troppo grande!',
            'Il file è vuoto!',
            'Nessun file selezionato'),
        'ins_this' => "Inserisci un Documento e/o Certificato",
        'upd_this' => "Modifica Documento e/o Certificato",
        'item' => "Articolo di riferimento",
        'table_name_ref' => "Tabella di riferimento",
        'note' => "Didascalia/Appunti/Note",
        'ext' => "Estensione",
        'select' => "Sel.",
        'code' => "Codice"),
	"admin_coltura.php" =>
    array(" coltura ",
        "ID ",
		"Descrizione coltura ",
		"Descrizione coltura vuota",
		"Codice già utilizzato"
    ),
	"admin_rec_stocc.php" =>
	 array(" Recipiente o silos di stoccaggio ",
        "Codice recipiente SIAN ",
		"Capacità ",
		"Titolo di possesso",
		"Destinato a DOP o IGP",
		'err' => array(
            'codice' => "Codice mancante",
			'codice_usato' => "Esiste già un contenitore con lo stesso codice",
			'capacita' => "Capacità mancante"
        ),

    ),
	"admin_stabilim.php" =>
	 array(" Stabilimento iscritto al SIAN ",
        "Codice stabilimento SIAN ",
		"Denominazione ",
		"Indirizzo",
		"Provincia",
		"Città",
		'err' => array(
            'codice' => "Codice SIAN mancante",
			'codice_usato' => "Esiste già uno stabilimento con lo stesso codice",
			'denomin' => "Denominazione stabilimento mancante"
        ),

    ),
	"sian.php" =>
	 array(0 => 'Creazione file upload per SIAN',
        'title' => 'Selezione per la creazione del File di carico e scarico per l\'upload al SIAN',
		'date' => 'Data di creazione file',
		'date_ini' => 'Data inizio movimenti',
		'date_fin' => 'Data fine movimenti',
        'errors' => array('La data  non &egrave; corretta!',
            'La data di inizio dei movimenti non pu&ograve; essere successiva alla data dell\'ultimo !',
            'La data di creazione del file non pu&ograve; essere precedente a quella dell\'ultimo movimento!',
			'La data di inizio non può essere precedente a quella dell\'ultimo file generato!',
			'Non è possibile generare il file SIAN con i movimenti di oggi. Potrà essere fatto domani!',
			'Incongruenza con la quantità di un contenitore o silos!'
        ),
		'header' => array('Data' => '', 'Prodotto' => '', 'Quantit&agrave;' => '',
            'ID SIAN - Fornitore/Cliente' => '', 'Recipiente di stoccaggio' => '', 'Capacità' => '', 'Descrizione doc.' => '', 'Operazione SIAN' => ''

        )
	),
	"admin_avversita.php" =>
    array(" avversità ",
        "ID ",
		"Descrizione avversità ",
		"Descrizione avversità vuota",
		"Codice già utilizzato"
    ),
	"admin_usofito.php" =>
    array(" Modalità uso fitofarmaco ",
        "ID ",
		"Nome fitofarmaco ",
		"Coltura",
		"Avversità",
		"Dose",
		"Codice già utilizzato",
		"Coltura inesistente",
		"Codice articolo vuoto o inesistente",
		"Avversità inesistente",
		"Tempo sospensione",
		"Fitofarmaco non presente in GAzie",
		"Dose non impostata"
    ),
    "camp_report_artico.php" =>
    array('title' => 'Lista delle merci e dei servizi',
        'codice' => "Codice",
        'descri' => "Descrizione",
        'good_or_service' => "Merce-Servizio",
        'unimis' => "U.M.",
        'catmer' => "Cat. merc.",
        'preacq' => 'Prezzo acquisto',
        'preve1' => 'Prezzo vend.1',
        'stock' => 'Giacenza',
        'aliiva' => 'IVA',
        'retention_tax' => 'Ritenuta',
        'payroll_tax' => 'Cassa Prev.',
        'barcode' => 'Cod.Barre',
        'clone' => 'Duplica',
		'class' => 'Classe',
		'lot' => 'Lotti'
    ),
    "report_statis.php" =>
    array("statistica ",
        "vendite",
        "acquisti",
        "anno",
        "ordinato per ",
        " da: ",
        " a: ",
        "Ultimo acquisto il ",
        "Ultima vendita il ",
        " dell'anno ",
        " Categoria merc. ",
        " Quantit&agrave; ",
        " Valore in ",
        " Fuori "),
    "camp_report_movmag.php" =>
    array("movimenti di magazzino ",
        "codice",
        "Inserisci ",
        "Lista dei ",
        "Data reg.",
        "Articolo",
        "Quantit&agrave;",
        "Avversit&agrave",
        "Annotazioni",
        " del ",
        "Genera movimenti da documenti",
		"Campo coltiv.",
		"Superficie",
		"Coltura",
		"Elenco movimenti agricoli",
		"Data attuaz.",
		"Operatore",
		"Acqua"),
    "camp_admin_movmag.php" =>
    array("movimento registro di campagna ",
        "Data della registrazione ",
        "Causale ",
        "Campo",
        "Fornitore",
        "C",
        "F",
        "Articolo",
        "Giorno di attuazione ",
        "Annotazione",
        "Sconto chiusura ",
        "Unit&agrave; di misura ",
        "Quantit&agrave; ",
        "Costo movimento ",
        "Sconto ",
        "La data del documento non &egrave; corretta!",
        "La data di registrazione non &egrave; corretta!",
        "La data del documento &egrave; successiva a quella di registazione!",
        "Articolo non presente o sconosciuto! Selezionare l'articolo fra quelli presenti nell'elenco a tendina.",
        "La quantit&agrave; non pu&ograve; essere uguale a zero!",
		"Avversità riscontrata",
		"Operatore",
		"Giacenza di magazzino",
		"Giacenza di magazzino insufficiente",
		"Non è possibile raccogliere. Tempo di sospensione insufficiente.",
		"Stai impiegando una quantità di prodotto superiore a quella ammessa! <br>Questo limite è generale e si trova nella scheda articolo.",
		"Stai superando il limite di rame metallo ammesso su questo campo di coltivazione!",
		"Non puoi più utilizzare questo prodotto in quanto ne è scaduta l'autorizzazione",
		"Il db fitofarmaci non viene aggiornato da oltre 30 giorni. Per effettuarlo: Merci/servizi > Aggiorna tabella fitofarmaci",
		"Produzione",
		"Hai impostato una produzione che non esiste. Devi selezionarla dal menù a tendina che appare. Se non è presente, devi creare una nuova produzione.",
		"Prezzo articolo ",
		"Operaio",
		"Coltura",
		"Stai impiegando una quantità di prodotto superiore a quella ammessa! <br>Questo limite è specifico (avversità/coltura) e si trova nella scheda uso fitofarmaci",
		"Senza campo di coltivazione non si può inserire una coltura!",
		"Per inserire un articolo con magazzino la causale del movimento deve operare uno scarico o un carico!",
		"La coltura impostata non esiste. Devi selezionare la coltura fra quelle che appaiono nel menù a tendina. Se non è presente, devi creare una nuova coltura.",
		"Lotto con giacenza inferiore alla quantità richiesta.",
		"E' possibile caricare solo file nel formato png peg jpeg pdf odt e di dimensione massima di 1 Mega.",
		"Non è possibile inserire articoli uguali con lotti su righe differenti.",
		"Lotti e certificati",
		"Gli articoli compositi possono essere caricati solo dal modulo produzioni!",
		"Stai superando il limite di azoto per anno previsto!",
		"Non è stato creato il codice articolo ACQUA. Per proseguire creare in Merci/servizi un articolo con codice ACQUA",
		"Non puoi più utilizzare questo fitifarmaco perché ne è stata revocata l'autorizzazione",
		"Mancano le ore",
        'operat' => 'Operazione',
        'operat_value' => array(-1 => "Scarico", 0 => "Non opera", 1 => "Carico"),
        'partner' => 'Cliente/Fornitore',
        'del_this' => 'Elimina la registrazione del Quaderno di campagna',
        'amount' => " Valore in ",
    ),
    "report_caumag.php" =>
    array("causali dei movimenti ",
        "Lista delle "
    ),
    "camp_admin_catmer.php" =>
    array("categoria merceologica ",
        "Numero ",
        "Descrizione ",
        "Immagine (jpg,png,gif) max 10kb: ",
        "% di ricarico ",
        "Annotazioni ",
        "codice gi&agrave; esistente!",
        "la descrizione &egrave; vuota!",
        "Il file immagine dev'essere nel formato PNG",
        "L'immagine non dev'essere pi&ugrave; grande di 10 kb",
        'web_url' => 'Web url<br />(es: https://site.com/group.html)'
    ),
	"admin_campi.php" =>
    array("Campo di coltivazione ",
        "Numero ",
        "Descrizione ",
        "Immagine (jpg,png,gif) max 300kb: ",
        "Dimensione in ha ",
        "Coltura in atto",
        "Codice gi&agrave; esistente!",
        "La descrizione &egrave; vuota!",
        "Il file immagine dev'essere nel formato PNG",
        "L'immagine non dev'essere pi&ugrave; grande di 300 kb",
		"La dimensione &egrave; vuota!",
		"ATTENZIONE: non puoi eliminare questo campo perché ci sono dei movimenti attivi. Elimina prima tutti i movimenti di questo campo.",
		"Note",
		'Zona vulnerabile da nitrati ZVN',
		'Limite di azoto per zone ZVN in Kg/ha',
		'Limite di azoto per zone non ZVN in Kg/ha',
        'web_url' => 'Mappa di Google<br />(es: https://goo.gl/maps/YajAcRexvDp)'

    ),
    "admin_ragstat.php" =>
    array("raggruppamento statistico ",
        "Numero ",
        "Descrizione ",
        "Immagine (jpg,png,gif) max 10kb: ",
        "% di ricarico ",
        "Annotazioni ",
        "codice gi&agrave; esistente!",
        "la descrizione &egrave; vuota!",
        "Il file immagine dev'essere nel formato PNG",
        "L'immagine non dev'essere pi&ugrave; grande di 10 kb",
        'web_url' => 'Web url<br />(es: https://site.com/group.html)'
    ),
    "camp_admin_caumag.php" =>
    array("causale di magazzino ",
        "Codice ",
        "Descrizione ",
        "Dati documento ",
        "Operazione",
        "Aggiorna Esistenza",
        "No",
        "Si",
        "Scarico",
        "Non opera",
        "Carico",
        "Cliente/Fornitore",
        "Cliente",
        "Entrambi",
        "Fornitore",
        "codice gi&agrave; esistente!",
        "la descrizione &egrave; vuota!",
        "Il codice dev'essere un numero minore di 80",
		"Modulo",
		"Utilizzabile solo dal modulo magazzino",
		"Utilizzabile solo dal modulo Registro di campagna",
		"Utilizzabile da entrambi i moduli"
    ),
    "genera_movmag.php" =>
    array("Genera movimenti di magazzino da documenti",
        "Data inizio ",
        "Data fine",
        "Azienda senza obbligo di magazzino fiscale!",
        " successiva alla ",
        "  righi sono da traferire in magazzino:",
        " Non ci sono righi da trasferire in magazzino!"),
    "camp_select_giomag.php" =>
    array(0 => 'Stampa Quaderno di campagna',
        'title' => 'Selezione per la visualizzazione e/o la stampa del quaderno di campagna',
        'errors' => array('La data  non &egrave; corretta!',
            'La data di inizio dei movimenti da stampare non pu&ograve; essere successiva alla data dell\'ultimo !',
            'La data di stampa non pu&ograve; essere precedente a quella dell\'ultimo movimento!'
        ),
        'date' => 'Data di stampa ',
        'date_ini' => 'Data registrazione inizio  ',
        'date_fin' => 'Data registrazione fine ',
        'header' => array('Data' => '', 'Causale' => '', 'Campo' => '', 'Superficie' => '', 'Coltura' => '', 'Prodotto' => '',
		'Classe' => '', 'Quantit&agrave;' => '', 'UM' => '', 'Avversit&agrave;' => '','Operatore' => '', 'Annotazioni' => ''
        )
    ),
	"select_dichiar_rame.php" =>
    array(0 => 'Dichiarazione rame metallo',
        'title' => 'Selezione per la visualizzazione e/o la stampa della dichiarazione rame metallo e delle fertilizzazioni',
        'errors' => array('La data  non &egrave; corretta!',
            'La data di inizio dei movimenti da stampare non pu&ograve; essere successiva alla data dell\'ultimo !',
            'La data di stampa non pu&ograve; essere precedente a quella dell\'ultimo movimento!'
        ),
        'date' => 'Data di stampa ',
        'date_ini' => 'Data inizio dichiarazione  ',
        'date_fin' => 'Data fine dichiarazione ',
		'sta_fert' => 'Stampa anche fertilizzazioni ',
        'header' => array('Data attuazione' => '', 'Causale' => '', 'Campo' => '', 'Superficie' => '', 'Prodotto' => '', 'Quantit&agrave;' => '', 'Rame metallo usato' => ''),
		'header_fert' => array('Data attuazione' => '', 'Causale' => '', 'Campo' => '', 'Superficie' => '', 'Prodotto' => '', 'Quantit&agrave;' => '', 'Rame metallo usato' => '', 'Fertilizzazione' => ''

        )
    ),
	 "camp_select_invmag.php" =>
    array(0 => 'Stampa inventario di magazzino',
        'title' => 'Selezione per la visualizzazione e/o la stampa dell\'inventario di magazzino. <br> E\' necessario creare prima i movimenti dell\'inventario su Merci/servizi > Inventario di magazzino. <br> Attenzione che il codice causale movimento dell\'inventario sia 99.',
        'errors' => array('La data  non &egrave; corretta!',
            'La data di inizio dei movimenti contabili da stampare non pu&ograve; essere successiva alla data dell\'ultimo !',
            'La data di stampa non pu&ograve; essere precedente a quella dell\'ultimo movimento!'
        ),
        'date' => 'Data di stampa ',
        'date_ini' => 'Data registrazione inizio  ',
        'date_fin' => 'Data registrazione fine ',
        'header' => array('Data' => '', 'Codice articolo' => '', 'Descrizione articolo' => '',
            'Causale movimento' => '', 'Valore totale' => '', 'UM' => '', 'Quantit&agrave;' => ''
        )
    ),
	"calc_prod.php"=>
	array("Produzione",
		"Calcolo della produzione: ",
		"iniziata il ",
		"nel campo di coltivazione ",
		"Costo totale della produzione (IVA esclusa)"
	),
    "recalc_exist_value.php" =>
    array("Rivalutazione esistenza articoli da movimenti di magazzino",
        "Anno di riferimento",
        "Metodo di rivalutazione, scelto in configurazione azienda",
        "Sono stati movimentati i seguenti",
        "articoli durante il ",
        "Movimenti",
        "Codice",
        "Descrizione",
        "Esistenza",
        "UM acq.",
        "Valore precedente",
        "Valore rivalutato",
        "NON RIVALUTATO vedi nota ",
        "(1) perch&egrave; ci sono degli acquisti negli anni successivi al ",
        "(2) perch&egrave; non ci sono movimenti di acquisto nel ",
        "Non ci sono articoli movimentati!"),
    "camp_inventory_stock.php" =>
    array('title' => "Inventario fisico di magazzino",
        'del' => "del",
        'catmer' => "Categoria Merceologica ",
        'select' => "Sel.",
        'code' => "Codice",
        'descri' => "Descrizione articolo",
        'mu' => "U.M.",
        'load' => "Carico",
        'unload' => "Scarico",
        'value' => "Nuovo valore giacenza",
        'v_a' => "Valore attuale",
        'v_r' => "Valore reale",
        'g_a' => "Giacenza attuale",
        'g_r' => "Giacenza reale",
        'g_v' => "Valore giacenza",
        'noitem' => "Non sono stati trovati articoli in questa categoria merceologica",
        'errors' => array(" La giacenza reale non pu&ograve; essere negativa",
            " Il valore reale non pu&ograve; essere negativo o uguale a zero",
            " Si st&agrave; tentando di fare l'inventario con giacenza attuale e reale entrambe a zero"),
        'preview_title' => 'Confermando le scelte fatte si registreranno i seguenti movimenti di magazzino:'
    ),
    "camp_select_schart.php" =>
    array(0 => 'Stampa schedari di magazzino',
        'title' => 'Selezione per la visualizzazione e/o la stampa delle schede di magazzino',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 1 carattere!',
            'Cambia articolo'
        ),
        'errors' => array('La data  non &egrave; corretta!',
            'La data di inizio dei movimenti contabili da stampare non pu&ograve; essere successiva alla data dell\'ultimo !',
            'La data di stampa non pu&ograve; essere precedente a quella dell\'ultimo movimento!',
            'L\'articolo iniziale non pu&ograve; avere un codice successivo a quello finale!',
            'La categoria merceologica iniziale non pu&ograve; avere un codice successivo a quello finale!'
        ),
        'date' => 'Data di stampa ',
        'cm_ini' => 'Categoria merceologica inizio ',
        'art_ini' => 'Articolo inizio ',
        'date_ini' => 'Data registrazione inizio  ',
        'cm_fin' => 'Categoria merceologica fine ',
        'art_fin' => 'Articolo fine ',
        'date_fin' => 'Data registrazione fine ',
        'header' => array('Data' => '', 'Causale' => '', 'Descrizione<br \>documento' => '',
             'UM' => '', 'Quantit&agrave;<br \> movimento' => '',
             'Quantit&agrave;<br \>giacenza' => ''
        ),
        'tot' => 'Consistenza'
    ),
    "stampa_schart.php" =>
    array(0 => 'SCHEDA DI MAGAZZINO dal ', 1 => ' al ',
        'bot' => 'a riportare : ',
        'top' => 'da riporto :  ',
        'item_head' => array('Codice', 'Cat.Merc', 'Descrizione', 'U.M.', 'ScortaMin.'),
        'header' => array('Data', 'Causale', 'Descrizione documento',
            'Prezzo', 'UM', 'Quantita',
            ' carico', ' scarico',
            'Q.ta giacenza', 'Val. giacenza'
        ),
        'tot' => 'Consistenza al '
    ),
    "select_deplia.php" =>
    array('title' => 'Selezione per la stampa del catalogo',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 1 carattere!',
            'Cambia articolo'
        ),
        'errors' => array('La data  non &egrave; corretta!',
            'L\'articolo iniziale non pu&ograve; avere un codice successivo a quello finale!',
            'La categoria merceologica iniziale non pu&ograve; avere un codice successivo a quello finale!'
        ),
        'date' => 'Data di stampa ',
        'cm_ini' => 'Categoria merceologica inizio ',
        'art_ini' => 'Articolo inizio ',
        'cm_fin' => 'Categoria merceologica fine ',
        'art_fin' => 'Articolo fine ',
        'barcode' => 'Stampa',
        'barcode_value' => array(0 => 'Immagini', 1 => 'Codici a Barre'),
        'listino' => 'Listino',
        'listino_value' => array(1 => ' di Vendita 1', 2 => ' di Vendita 2', 3 => ' di Vendita 3', 4 => ' di Vendita 4', 'web' => ' di Vendita Online')
    ),
    "select_listin.php" =>
    array('title' => 'Selezione per la stampa dei listini',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 1 carattere!',
            'Cambia articolo'
        ),
        'errors' => array('La data  non &egrave; corretta!',
            'L\'articolo iniziale non pu&ograve; avere un codice successivo a quello finale!',
            'La categoria merceologica iniziale non pu&ograve; avere un codice successivo a quello finale!'
        ),
        'date' => 'Data di stampa ',
        'cm_ini' => 'Categoria merceologica inizio ',
        'art_ini' => 'Articolo inizio ',
        'cm_fin' => 'Categoria merceologica fine ',
        'art_fin' => 'Articolo fine ',
        'listino' => 'Listino',
        'listino_value' => array(0 => 'd\'Acquisto', 1 => ' di Vendita 1', 2 => ' di Vendita 2', 3 => ' di Vendita 3', 4 => ' di Vendita 4', 'web' => ' di Vendita Online'),
        'id_anagra' => 'Fornitore (vuoto per tutti)',
        'ordineStampa' => 'Ordine di Stampa',
        'alternativeOrdineStampa' => array('default',
            'codice articolo',
            'descrizione articolo',
            'categoria articolo'
        ),
        'tipoStampa' => 'Tipo di Stampa',
        'alternativeTipoStampa' => array('espansa', 'compatta'),
    ),
    "update_vatrate.php" =>
    array('title' => 'Modifica aliquota IVA degli articoli',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 1 carattere!',
            'Cambia articolo'
        ),
        'errors' => array('Errore nullo',
            'L\'articolo iniziale non pu&ograve; avere un codice successivo a quello finale!',
            'La categoria merceologica iniziale non pu&ograve; avere un codice successivo a quello finale!'
        ),
        'cm_ini' => 'Categoria merceologica inizio ',
        'art_ini' => 'Articolo inizio ',
        'cm_fin' => 'Categoria merceologica fine ',
        'art_fin' => 'Articolo fine ',
        'rate_obj' => 'Aliquota oggetto della modifica',
        'rate_new' => 'Nuova aliquota',
        'header' => array('Cat.Merceologica' => '', 'Codice' => '', 'Descrizione' => '', 'U.M.' => '',
            'Aliquota vecchia' => '', 'Aliquota nuova' => ''
        )
    ),
    "update_prezzi.php" =>
    array('title' => 'Modifica prezzi di listino',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 1 carattere!',
            'Cambia articolo'
        ),
        'errors' => array('Valore "0" inaccettabile in questa modalit&agrave; di Modifica',
            'L\'articolo iniziale non pu&ograve; avere un codice successivo a quello finale!',
            'La categoria merceologica iniziale non pu&ograve; avere un codice successivo a quello finale!'
        ),
        'cm_ini' => 'Categoria merceologica inizio ',
        'art_ini' => 'Articolo inizio ',
        'cm_fin' => 'Categoria merceologica fine ',
        'art_fin' => 'Articolo fine ',
        'lis_obj' => 'Listino oggetto della modifica',
        'lis_bas' => 'Listino base di calcolo',
        'listino_value' => array(0 => 'd\'Acquisto', 1 => ' di Vendita 1', 2 => ' di Vendita 2', 3 => ' di Vendita 3', 4 => ' di Vendita 4', 'web' => ' di Vendita Online'),
        'mode' => 'Modalit&agrave; di modifica',
        'mode_value' => array('A' => 'Sostituzione', 'B' => 'Somma in percentuale', 'C' => 'Somma valore',
            'D' => 'Moltiplicazione per valore', 'E' => 'Divisione per valore', 'F' => 'Azzeramento e somma percentuale'),
        'valore' => 'Percentuale/valore',
        'round_mode' => 'Arrotondamento matematico a',
        'round_mode_value' => array('1 ', '10 centesimi', '1 centesimo', '1 millesimo', '0,1 millesimi', '0,01 millesimi'),
        'weight_valadd' => 'Incidenza su peso specifico es. €/kg',
        'header' => array('Cat.Merceologica' => '', 'Codice' => '', 'Descrizione' => '', 'U.M.' => '',
            'Prezzo vecchio' => '', 'Incidenza peso' => '', 'Prezzo nuovo' => ''
        )),
    "camp_admin_artico.php" =>
    array('title' => 'Gestione degli articoli',
        'ins_this' => 'Inserimento prodotto agricolo o lavorazione',
        'upd_this' => 'Modifica ',
        'err' => array(
            'codice' => 'Il codice articolo &egrave; gi&agrave; esistente',
            'movmag' => 'Si st&agrave; tentando di modificare il codice ad un articolo con dei movimenti di magazzino associati',
            'filmim' => 'Il file dev\'essere nel formato PNG, JPG, GIF',
            'filsiz' => 'L\'immagine non dev\'essere pi&ugrave; grande di 64 kb',
            'valcod' => 'Inserire un codice valido',
            'descri' => 'Inserire una descrizione',
            'unimis' => 'Inserire l\'unit&agrave; di misura',
            'unimis2' => 'Questo prodotto contiene rame metallo e quindi l\'unit&agrave; di misura può essere solo Kg o l',
            'unimis3' => 'Questo prodotto contiene azoto e quindi l\'unit&agrave; di misura può essere solo Kg',
            'unimis4' => 'Questo prodotto contiene fosforo e quindi l\'unit&agrave; di misura può essere solo Kg',
            'unimis5' => 'Questo prodotto contiene potassio e quindi l\'unit&agrave; di misura può essere solo Kg',
            'aliiva' => 'Inserire l\'aliquota I.V.A.',
            'catmer' => 'Selezionare una categoria merceologica',
            'lotmag' => 'Per avere la tracciabilità per lotti è necessario attivare la contabilità di magazzino in configurazione azienda',
            'scaduto' =>'Il prodotto non può più essere usato: è scaduta l\'autorizzazione del Ministero!',
            'updatedb' =>'Il db fitofarmaci non viene aggiornato da oltre 30 giorni. Per effettuarlo: Merci/servizi > Aggiorna tabella fitofarmaci',
            'or_macro' =>'E\' necessario inserire l\'origine macro area!',
            'or_spec' =>'E\' necessario specificare l\'origine specifica!',
            'revocato' =>'Il prodotto non può più essere usato: l\'autorizzazione del Ministero è stata revocata!',
            'insert_before_OKsub' => 'Per inserire una dose specifica bisogna aver prima inserito il prodotto'
        ),
        'codice' => "Codice",
        'perc_N' => "Titolo di azoto",
        'perc_P' => "Titolo di fosforo",
        'perc_K' => "Titolo di potassio",
        'descri' => "Descrizione",
        'good_or_service' => "Tipologia di articolo",
        'good_or_service_value' => array(0 => 'Prodotto', 1 => 'Lavorazione o Servizio', 2=> 'Composizione'),
        'body_text' => "Testo descrittivo (precede il rigo)",
        'lot_or_serial' => 'Lotti o numeri seriali',
        'lot_or_serial_value' => array(0 => 'No', 1 => 'Lotti', 2 => 'Seriale/Matricola'),
        'barcode' => "Codice a Barre EAN13",
        'image' => "Immagine (jpg,png,gif) max 64 kb",
        'unimis' => "Unit&agrave; di misura",
        'catmer' => "Categoria merceologica",
        'ragstat' => "Raggruppamento statistico",
        'preacq' => 'Prezzo (imponibile)',
        'preve1' => 'Prezzo di vendita listino 1',
        'preve2' => 'Prezzo di vendita listino 2',
        'preve3' => 'Prezzo di vendita listino 3',
        'preve4' => 'Prezzo di vendita listino 4',
        'preve1_sc' => 'Prezzo scontato 1',
        'preve2_sc' => 'Prezzo scontato 2',
        'preve3_sc' => 'Prezzo scontato 3',
        'preve4_sc' => 'Prezzo scontato 4',
        'sconto' => 'Sconto',
        'aliiva' => 'Aliquota IVA',
        'retention_tax' => 'Applica la ritenuta d\'acconto',
        'retention_tax_value' => array(0 => 'No', 1 => 'Si'),
        'payroll_tax' => 'Genera rigo Cassa Previdenziale',
        'payroll_tax_value' => array(0 => 'No', 1 => 'Si'),
        'esiste' => 'Esistenza attuale',
        'valore' => 'Valore dell\'esistente',
        'last_cost' => 'Costo dell\'ultimo acquisto',
        'scorta' => 'Scorta minima',
        'riordino' => 'Lotto acquisto',
        'uniacq' => 'Unit&agrave; di misura acquisti',
        'tempo_sospensione' => 'Tempo di sospensione in gg',
        'dose_ha' => 'Dose massima generica ad ha',
        'mostra_qdc' => 'Mostrare nel quaderno di campagna',
        'SIAN' => 'Movimenta il carico e scarico del SIAN',
        'estrazione' => 'Tipo di estrazione olio',
        'biologico' => 'Tipo di agricoltura olio',
        'etichetta' => 'Etichettatura olio',
        'categoria' => 'Categoria olio',
        'categoria_value' => array(0=>'',1=>'Olio di oliva vergine',2=>'Olio di oliva extravergine',3=>'Olio in attesa di classificazione',4=>'Olio di oliva',5=>'Olio di oliva raffinato',6=>'Olio di sansa di oliva',7=>'Olio di sansa raffinato'),
        'or_macro' => 'Macroarea di origine',
        'or_macro_value' => array(0=>'',1=>'ITA-Italia',2=>'PUE-Paesi dell\'Unione europea',3=>'UE-Unione europea',4=>'EXT-Paese extra Unione europea',5=>'Miscela di oli dell\'Unione europea',6=>'Combinazione stati e regioni UE',7=>'Miscela di oli non originari UE',8=>'Combinazione stati non UE e regioni extra UE',9=>'Miscela di oli originari UE e non originari UE',10=>'Combinazione stati e regioni Ue e extra UE',11=>'Olio (extra) vergine di oliva ottenuto nell\'UE',12=>'DOP IGP Italia',13=>'ADD-Olio/olive atto a divenire DOP/IGP (Italia)',14=>'DOP/IGP estera'),
        'or_spec' => 'Origine specifica',
        'or_spec_value' => array(0=>'',1=>'Spagna',2=>'Grecia',3=>'Portogallo',4=>'Francia',5=>'Malta',6=>'Cipro',7=>'Penisola Iberica',8=>'Altro'),
        'confezione' => 'Capacità confezione olio in litri',
        'classif_amb' => 'Classificazione tossicologica',
        'classif_amb_value' => array(0=>'non classificato',1=>'Irritante',2=>'Nocivo',3=>'Tossico',4=>'Molto tossico',5=>'Pericoloso ambiente'),
        'peso_specifico' => 'Peso specifico/Moltiplicatore',
        'volume_specifico' => 'Volume specifico',
        'pack_units' => 'Pezzi in imballo',
        'codcon' => 'Conto di ricavo su vendite',
        'id_cost' => 'Conto di costo su acquisti',
        'annota' => 'Annotazioni (pubblicate anche sul web)',
        'document' => 'Documenti: scheda di sicurezza, scheda tecnica, certificazioni etc.',
        'web_mu' => 'Unit&agrave; di misura online',
        'web_price' => 'Prezzo di vendita online',
        'web_multiplier' => 'Moltiplicatore prezzo web',
        'web_public' => 'Pubblica sul sito web',
        'web_public_value' => array(0 => 'No', 1 => 'Si'),
        'depli_public' => 'Pubblica sul catalogo',
        'depli_public_value' => array(0 => 'No', 1 => 'Si'),
        'web_url' => 'Web url<br />(es: https://site.com/item.html)',
        'modal_ok_insert' => 'Articolo inserito con successo clicca sulla X in alto a destra per uscire oppure...',
        'iterate_invitation' => 'INSERISCI UN ALTRO ARTICOLO DI MAGAZZINO',
        'browse_for_file' => 'Sfoglia',
        'id_anagra' => 'Fornitore',
        'rame_metallico' => "Rame metallo Kg/1Kg",
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 1 carattere!',
            'Cambia fornitore'
        ),
    )
);
?>