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
$transl['vendit'] = array('name' => " Vendite",
    'title' => "Gestione Vendite",
    'm2' => array(1 => array("Gestione Scontrini e Ricevute", "Corrispettivi"),
        2 => array("Gestione Fatture, Note credito, Note Debito", "Fatture"),
        3 => array("Gestione dei Documenti di Trasporto", "D.d.T."),
        4 => array("Gestione Preventivi ai Clienti", "Preventivi"),
        5 => array("Gestione Ordini dei Clienti", "Ordini"),
        6 => array("Gestione degli Effetti", "Effetti"),
        7 => array("Gestione dell'Archivio dei Clienti", "Clienti"),
        8 => array("Gestione Agenti di Vendita", "Agenti"),
        9 => array("Statistiche delle Vendite", "Statistiche"),
        10 => array("Abbonamenti - Contratti", "Contratti"),
        11 => array("Scadenzario a partite aperte", "Scadenzario")
    ),
    'm3' => array(1 => array("Emissione Scontrini Fiscali", "Emetti Scontrino"),
        2 => array("Emissione Fattura di Vendita", "Emetti Fattura"),
        3 => array("Emissione Nota di Credito", "Emetti Nota Credito"),
        4 => array("Emissione Nota di Debito", "Emetti Nota Debito"),
        5 => array("Stampa documenti gi&agrave; emessi", "Ristampa Documenti"),
        6 => array("Genera fatture differite da D.d.T.", "Fatturazione D.d.T."),
        7 => array("Contabilizzazione fatture emesse", "Contabilizza fatture"),
        8 => array("Emissione Documento di Trasporto", "Emetti D.d.T."),
        9 => array("Emissione di preventivo a cliente", "Preventivo a cliente"),
        10 => array("Ricevuto ordine da cliente", "Ordine da cliente"),
        11 => array("Evasione ordini dei clienti", "Evadi ordini"),
        12 => array("Emetti nuovo effetto", "Emetti effetto"),
        13 => array("Genera effetti da fatture di vendita", "Genera effetti da fatture"),
        14 => array("Stampa effetti generati", "Stampa effetti"),
        15 => array("Distinta effetti cartacei", "Distinta Cambiali-Tratte (pdf)"),
        16 => array("Genera file RiBa standard CBI", "Distinta RiBa (cbi)"),
        17 => array("Contabilizzazione effetti", "Contabilizza effetti"),
        18 => array("Inserisci un nuovo cliente", "Nuovo cliente"),
        19 => array("Lista dei crediti verso clienti", "Lista dei crediti"),
        20 => array("Inserimento nuovo agente di vendita", "Nuovo agente"),
        21 => array("Riscossione di un credito vantato verso un cliente", "Riscossione credito da cliente"),
        22 => array("Inserimento nuovo contratto - abbonamento", "Nuovo Contratto"),
        23 => array("Generazione ricevute/fatture da contratti ", "Genera ricevute/fatture da contratti"),
        24 => array("Contabilizzazione ricevute e scontrini", "Contabilizza ricevute/scontrini"),
        25 => array("Lista delle ricevute emesse", "Ricevute"),
        26 => array("Chiusura giornaliera registratore di cassa", "Chiusura giornaliera registratore di cassa"),
        27 => array("Emissione Parcella", "Emetti Parcella"),
        28 => array("Importa ordini dal WEB", "Importa Ordini WEB"),
        29 => array("Genera file MAV standard CBI", "Distinta MAV (cbi)"),
        30 => array("Flusso fatture elettroniche", "Flusso fatture elettroniche"),
        31 => array("Report partite aperte", "Report partite aperte"),
        32 => array("Selezione e stampa stato clienti", "Stato delle scadenze"),
        33 => array("Gestione degli indirizzi di destinazione dei Clienti", "Destinazioni"),
        34 => array("Inserimento nuovo indirizzo di destinazione", "Nuova destinazione"),
        35 => array("Emetti Ricevuta Fiscale", "Emetti ricevuta"),
        36 => array("Stampa lista documenti", "Lista documenti"),
        37 => array("Stampa lista clienti", "Lista clienti"),
        38 => array("Stampa analisi acquisti clienti", "Analisi acquisti clienti"),
        39 => array("Stampa analisi agenti", "Analisi agenti"),
        40 => array("Esportazione articoli venduti", "Esportazione articoli venduti"),
        41 => array("Gestione sconti clienti/articoli", "Sconti clienti/articoli"),
        42 => array("Gestione sconti clienti/raggruppamenti statistici", "Sconti clienti/raggruppamenti statistici"),
        43 => array("Stampa analisi fatturato clienti", "Analisi fatturato clienti"),
        44 => array("Stampa analisi fatturato cliente x fornitore", "Analisi fatturato cliente x fornitore"),
        45 => array("Vendita bene ammortizzabile (su libro cespiti)", "Alienazione bene ammortizzabile"),
        46 => array("Visualizza Ordini settimanali del giorno", "Ordini settimanali del giorno"),
        47 => array("Nuovo ordine settimanale del giorno", "Inserisci ordine settimanale del giorno"),
        48 => array("Evadi ordini settimanali del giorno", "Evadi ordini settimanali del giorno"),
        49 => array("Emetti scontrino prezzi IVA inclusa", "Scontrino prezzi IVA inclusa"),
        50 => array("Emetti CMR", "Emetti CMR"),
        51 => array("Lista CMR", "Lista CMR"),
        52 => array("Impacchetta fatture elettroniche", "Impacchetta fatture elettroniche"),
        53 => array("Genera fatture differite da CMR", "Fatturazione CMR"),
        54 => array("Importa XML fattura di vendita", "Importa XML fattura di vendita"),
        55 => array("Lista Clienti per zone", "Lista Clienti per zone"),
        56 => array("Lista dei Registratori Telematici", "Lista Registratori Telematici"),
        57 => array("Nuovo Registratore Telematico", "Nuovo Registratore Telematico"),
        58 => array("Genera file RID CBI-SEPA", "Distinta RID (xml)"),
        59 => array("Lista delle distinte generate", "Distinte generate"),
        60 => array("Stampa libera lista effetti", "Stampa lista effetti"),
        61 => array("Nuovo gruppo clienti", "Nuovo gruppo clienti"),
        62 => array("Lista gruppi clienti", "Lista gruppi clienti")
    )
);
?>
