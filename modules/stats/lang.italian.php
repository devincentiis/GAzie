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
$strScript = array("docume_stats.php" =>
    array('title' => "Statistiche- documentazione",
		'hours_other'=>"altra quantità",
		),
    "report_statis.php" =>
    array('title' => "Statistiche delle vendite",
		"statistica ",
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
        " Fuori ",
        " del mese ",
        " della categoria "),
    "select_analisi_acquisti_clienti.php" =>
    array('title' => 'Analisi acquisti clienti',
        'errors' => array('Inserire l\'anno da analizzare!',
        ),
        "Selezione Cliente ",
        "Inserire min. 2 caratteri",
        "Tutti i clienti!",
        "Non &egrave; stato trovato nulla",
        'Anno',
        'Cadenza',
    ),
    "select_analisi_agenti.php" =>
    array('title' => 'Analisi agenti',
        "Data periodo inizio",
        "Data periodo fine",
        "Livello max",
        'errors' => array('Inserire il livello da analizzare!',
        ),
    ),
    "esportazione_articoli_venduti_per_fornitore.php" =>
    array('title' => 'Esportazione articoli venduti',
        'errors' => array('Inserire l\'anno da analizzare!', 'Manca il fornitore!',
        ),
        'titleLista' => 'Selezionare i clienti',
        'header' => array('Codice' => '', 'Ragione Sociale' => '', 'Selezionato' => ''),
        "Selezione Fornitore ",
        "Inserire min. 2 caratteri",
        "Tutti i fornitori!",
        "Non &egrave; stato trovato nulla",
        'Anno',
        'esporta' => 'Esporta',
        'fine' => 'Esportazione terminata. Clicca qui per continuare.'
    ),
    "select_analisi_fatturato_clienti.php" =>
    array('title' => 'Analisi fatturato clienti',
        "Data periodo inizio",
        "Data periodo fine",
        'id_agente' => "Agente",
        'preview' => 'Anteprima',
        'print' => 'Stampa',
        'totale' => 'TOTALE',
        'errors' => array(
        ),
        'header' => array('Cod. cliente' => '', 'Cliente' => '', 'Fatturato' => '', 'Costi' => '',
            'Margine %' => ''
        ),
    ),
    "select_analisi_fatturato_cliente_fornitore.php" =>
    array('title' => 'Analisi fatturato cliente x fornitore',
        "Data periodo inizio",
        "Data periodo fine",
        "Inserire min. 2 caratteri",
        "Tutti i clienti!",
        "Non &egrave; stato trovato nulla",
        'preview' => 'Anteprima',
        'print' => 'Stampa',
        'totale' => 'TOTALE',
        'partner' => 'Cliente ',
        'errors' => array('Inserire il cliente'
        ),
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 2 caratteri!',
            'Cambia cliente',
        ),
        'header' => array('Cod. fornitore' => '', 'Fornitore' => '', 'Fatturato' => '', 'Costi' => '',
            'Margine %' => ''
        ),
    ),
    "select_analisi_avanzamento_per_fornitore.php" =>
    array('title' => 'Analisi avanzamento venduto/acquistato x fornitore',
        "Data Vendite inizio",
        "Data Vendite fine",
        "Data Acquisti inizio",
        "Data Acquisti fine",
        'preview' => 'Anteprima',
        'print' => 'Stampa',
        'totale' => 'TOTALE',
        'partner' => 'Cliente ',
        'errors' => array('Inserire il cliente'
        ),
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 2 caratteri!',
            'Cambia cliente',
        ),
        'header' => array('Cod. fornitore' => '', 'Fornitore' => '', 'Acquistato' => '', 'Venduto' => '',
            'Avanzamento %' => ''
        ),
    )
);
?>