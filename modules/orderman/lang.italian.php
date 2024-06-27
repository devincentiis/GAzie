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

$strScript = array("admin_orderman.php" =>
		array('title' => 'Amministrazione delle produzioni',
        'ins_this' => 'Inserisci una nuova produzione',
        'upd_this' => 'Aggiorna la produzione',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 2 caratteri!'),
        'errors' => array(),
		'cod_operaz_value' => array(0 => '', 1 => 'L - Confezionamento con etichettatura', 2 => 'L1 - Confezionamento senza etichettatura',3 => 'L2 - Etichettatura',4 => 'X - Svuotamento di olio confezionato',5 => 'M1 - Movimentazione interna olio sfuso (senza cambio origine)'),
		"ID ",
		"Tipo di produzione",
		"Descrizione produzione",
		"Informazioni supplementari",
		"Descrizione vuota",
		"Data inizio produzione",
		"Durata produzione in giorni",
		"Luogo di produzione",
		"Ordine",
		"Articolo prodotto",
		"Operaio",
		"Durata produzione in ore",
		"Tipo di produzione vuoto",
		"Lotto di produzione",
		"Si possono caricare solo file nel formato png, jpg, pdf, odt di dimensioni inferiori a 1M",
		"Quantità produzione",
		"Manca l'articolo prodotto",
		"Manca la quantità prodotta",
		"Gli operai non possono lavorare più di 13 ore al giorno: D. Lgs. 66/2003",
		"Aggiungi operaio",
		"Articolo non presente o sconosciuto! Selezionare l'articolo fra quelli mostrati nell'elenco a tendina.",
		"Non c'è sufficiente disponibilità di un ID lotto selezionato",
		"Manca la data di registrazione",
		"Il numero d'ordine inserito è inesistente",
		"L'articolo è già stato prodotto per questo ordine",
		"La quantità inserita di un lotto, di un componente, è errata",
		"Non è stata impostata l'operazione SIAN",
		"Manca il recipiente di destinazione",
		"Il recipiente di destinazione non può essere lo stesso di origine",
		"L'operazione L2 di etichettatura prevede solo olio confezionato",
		"L'operazione L2 di etichettatura prevede che l'olio prodotto sia etichettato",
		"Le caratteristiche di estrazione, categoria, origine, biologico e capacità confezione dell'articolo prodotto e del componente devono essere uguali",
		"L'olio componente non può essere già etichettato",
		"Data inizio lavori",
		"Data fine lavori",
		"Ora inizio",
		"Ora fine",
		"Per l'operazione SIAN imposata l'olio prodotto deve essere confezionato",
		"Manca il recipiente di stoccaggio",
		"Per confezionare si deve avere almeno il componente Olio sfuso",
		"La data di registrazione è prcedente all'ultimo movimento inviato al SIAN. Se si conferma, questo movimento non sarà inviato al SIAN.",
		"Il recipiente di stoccaggio selezionato non ha sufficiente olio",
		"La movimentazione interna di olio si può fare solo per singolo ID lotto. <br>La quantità produzione richiesta è superiore a quella disponibile, pertanto è stata diminuita.<br>
		Per la parte mancante si deve fare un'ulteriore operazione.",
		"Non c'è sufficiente disponibilità di un componente",
		"La/e varietà del silos non coincide/ono con quella/e del prodotto confezionato. Se si procede senza correggere, la registrazione al SIAN sarà senza varietà",
		"Non può uscire un lotto in data precedente alla sua creazione",
		"Il recipiente di destinazione non ha spazio sufficiente per contenere la quantità impostata",
		"id lotto inesistente nel silos selezionato",
    "ATTENZIONE: hai inserito un quantitativo di componenti diverso da quello richiesto"
		),
	"orderman_report.php" =>
		array('title' => 'Produzioni/commesse',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 2 caratteri!')
			),
	"admin_luoghi.php" =>
	array('title' => 'Siti di produzione',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 2 caratteri!'),
		"Id",
		"Descrizione",
		"Immagine (jpg,png,gif) max 300kb: ",
		"Note",
		"Codice già usato",
		"Descrizione vuota",
		"L'immagine non dev'essere pi&ugrave; grande di 300 kb",
		"L'immagine inserita ha un formato non ammesso",
		"Luogo di produzione",
		'web_url' => 'Mappa di Google<br />(es: https://goo.gl/maps/YajAcRexvDp)'
			)
);
?>
