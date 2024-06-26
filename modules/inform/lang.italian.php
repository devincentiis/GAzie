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

$strScript = array(
    "report_letter.php" =>
    array("Lista delle lettere ",
        "Data ",
        "Numero ",
        "Tipo ",
        "Ragione sociale ",
        "Oggetto ",
        "Scrivi una nuova lettera",
        'mail_alert0' => 'Invio lettera con email',
        'mail_alert1' => 'Hai scelto di inviare una e-mail all\'indirizzo: ',
        'mail_alert2' => 'con allegato la seguente lettera:'),
    "admin_letter.php" =>
    array('title' => " Lettera ",
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 2 caratteri!',
            'Cambia anagrafica'),
        array("LET" => " Normale ", "DIC" => "Dichiarazione", "SOL" => " Sollecito ", "PRE" => " Preventivo ", "SMS" => " Messaggio di testo " ),
        " del ",
        " a ",
        " numero ",
        "Oggetto ",
        "alla c.a. ",
        "firma utente ",
        "Tipo ",
        "Corpo ",
        "Apponi nome utente",
        "La data non &egrave; corretta!",
        "Devi selezionare un cliente o un fornitore!"
    ),
    "update_control.php" =>
    array('title' => " Controllo aggiornamento software ",
        'new_ver1' => 'E\' disponibile una <b>nuova</b> versione (',
        'new_ver2' => ') di GAzie! <br>Per effettuare l\'aggiornamento puoi scaricare i files da',
        'is_align' => 'Non ci sono nuovi aggiornamenti disponibili. Questa versione di GAzie &egrave; aggiornata all\'ultima disponibile ',
        'no_conn' => 'Ci sono problemi di connessione al server per il controllo della versione!',
        'disabled' => 'Il controllo delle versioni aggiornate &egrave; stato disabilitato. &Egrave; possibile riattivarlo scegliendo uno dei servizi di check messi a disposizione dai seguenti siti',
        'zone' => 'ZONA',
        'city' => 'CITT&Agrave;',
        'sms' => 'SMS',
        'web' => 'Indirizzo WEB',
        'choice' => 'SCEGLI',
        'check_value' => array(0 => 'Abilita!', 1 => 'Abilitato'),
        'check_title_value' => array(0 => 'Abilita il controllo di versione da questo sito!', 1 => 'Disabilita il controllo di versione da questo sito!'),
        'all_disabling' => array(0 => 'Disabilita tutti!', 1 => 'Disabilita tutti i siti per il controllo della versione!')
    ),
    "report_anagra.php" =>
    array('title' => " Anagrafica contatti ",
        'new_ver1' => 'E\' disponibile una <b>nuova</b> versione (',
        'new_ver2' => ') di GAzie! <br>Per effettuare l\'aggiornamento puoi scaricare i files da',
        'is_align' => 'Non ci sono nuovi aggiornamenti disponibili. Questa versione di GAzie &egrave; aggiornata all\'ultima disponibile.',
        'no_conn' => 'Ci sono problemi di connessione al server per il controllo della versione!',
        'disabled' => 'Il controllo delle versioni aggiornate &egrave; stato disabilitato. &Egrave; possibile riattivarlo scegliendo uno dei servizi di check messi a disposizione dai seguenti siti',
        'zone' => 'ZONA',
        'city' => 'CITT&Agrave;',
        'sms' => 'SMS',
        'web' => 'Indirizzo WEB',
        'choice' => 'SCEGLI',
        'check_value' => array(0 => 'Abilita!', 1 => 'Abilitato'),
        'check_title_value' => array(0 => 'Abilita il controllo di versione da questo sito!', 1 => 'Disabilita il controllo di versione da questo sito!'),
        'all_disabling' => array(0 => 'Disabilita tutti!', 1 => 'Disabilita tutti i siti per il controllo della versione!')
    ),
    "gaziecart_update.php" =>
    array('title' => "Aggiornamento del catalogo online, estensione GAzieCart per Joomla!",
        'errors' => array('Il server non &egrave; stato trovato',
            'Impossibile fare il login, credenziali errate',
            'Direttorio inesistente',
            'Uno o pi&ugrave; file non sono stati aggiornati',
            "COMPLETATO!!! L'upload sul server web è andato a buon fine!"
        ),
        'server' => 'Nome del server FTP es: devincentiis.it',
        'user' => 'User - Nome utente per l\'autenticazione',
        'pass' => 'Password per l\'autenticazione',
        'path' => 'Dir. radice di joomla es. joomla/ opp. nulla',
        'listin' => 'Listino ',
        'listin_value' => array(1 => ' di Vendita 1', 2 => ' di Vendita 2', 3 => ' di Vendita 3', 'web' => ' di Vendita Online')
    ),
    "gazie_site_update.php" =>
    array('title' => "Aggiornamento del sito web",
        'errors' => array('Il server non &egrave; stato trovato',
            'Impossibile fare il login, credenziali errate',
            'Direttorio inesistente',
            'Uno o pi&ugrave; file non sono stati aggiornati'
        ),
        'server' => 'Nome del server FTP es: ftp.devincentiis.it',
        'user' => 'User - Nome utente per l\'autenticazione',
        'pass' => 'Password per l\'autenticazione',
        'path' => 'Directory radice del sito es. public_html/ opp. www/',
        'head_title' => 'Descrizione aggiuntiva al titolo',
        'head_subtitle' => 'Descrizione aggiuntiva al sottotitolo',
        'author' => 'Autore del sito (meta author)',
        'keywords' => 'Parole chiave del sito (meta keywords)',
        'listin' => 'Pubblicazione',
        'listin_value' => array(0 => 'Non pubblicare il listino', 1 => 'Listino senza prezzi', 2 => 'Listino con prezzi online'),
        'addpage' => 'Aggiungi una pagina al sito'
    ),
    "backup.php" =>
    array('title' => "Download del backup dei dati per mettere in sicurezza il lavoro",
        'errors' => array(),
        'instructions' => 'Aggiungere le istruzioni seguenti',
        'table_selection' => 'Backup di',
        'table_selection_value' => array(0 => ' tutte le tabelle della base di dati ', 1 => ' le sole tabelle con prefisso '),
        'text_encoding' => 'Codifica',
        'sql_submit' => 'Stai per generare e scaricare un file sql di backup del database di GAzie in formato .gz',
    ),
    "report_backup.php" =>
    array('title' => "Lista dei backup interni ",
        'errors' => array(),
        'id' => 'Identificativo',
        'ver' => 'Versione',
        'name' => 'Nome file',
        'size' => 'Dimensione',
        'rec' => 'Ripristina',
        'dow' => 'Scarica',
        'del' => 'Elimina',
        'config' => 'Configurazione',
        'backup_mode' => 'Modalità di backup',
        'backup_mode_value' => array('automatic' => 'Automatico', 'manual' => 'Manuale'),
        'sure' => 'Sei sicuro?',
        'recover' => 'Ripristinare'
    ),
    "report_anagra.php" =>
    array('title' => "Anagrafiche comuni"
    ),
    "delete_backup.php" =>
    array('title' => 'Eliminazione backup in corso',
        'sure' => 'Sei sicuro di voler cancellare il file?',
        'warning' => 'Attenzione'
    ),
    "admin_anagra.php" =>
    array('title' => 'Modifica anagrafica comune',
        'err' => array('ragso1' => '&Egrave; necessario indicare la Ragione Sociale',
            'indspe' => '&Egrave; necessario indicare l\'indirizzo',
            'capspe' => 'Il codice di avviamento postale (CAP) &egrave; sbagliato',
            'citspe' => '&Egrave; necessario indicare la citt&agrave;',
            'prospe' => '&Egrave; necessario indicare la provincia',
            'sexper' => '&Egrave; necessario indicare il sesso',
            'pf_no_codfis' => 'Codice fiscale sbagliato per una persona fisica',
            'pariva' => 'La partita IVA &egrave; formalmente errata!',
            'same_pariva' => 'Esiste gi&agrave una anagrafica con la stessa Partita IVA',
            'codfis' => 'Il codice fiscale &egrave; formalmente errato',
            'same_codfis' => 'Esiste gi&agrave; una anagrafica con lo stesso Codice Fiscale',
            'pf_ins_codfis' => 'E\' una persona fisica, inserire il codice fiscale',
            'datnas' => 'La data di nascita &egrave; sbagliata',
            'pec_email' => 'Indirizzo posta elettronica certificata formalmente sbagliato',
            'e_mail' => 'Indirizzo email formalmente sbagliato',
            'cf_pi_set' => 'E\' stata impostato un codice fiscale uguale alla partita IVA, se giusto, conferma nuovamente la modifica',
        ),
        'id' => "Codice ",
        'ragso1' => "Ragione sociale 1",
        'ragso1_placeholder' => "opp. nome cognome legale rappresentante",
        'ragso2' => "Ragione sociale 2",
        'sedleg' => 'Sede legale',
        'legrap_pf_nome' => 'Legale rappr(Nome - Cognome)',
        'legrap_pf_title' => "la ragione sociale lasciata vuota verrà riempita con questi campi",
        'luonas' => 'Luogo di nascita',
        'datnas' => 'Data di Nascita',
        'pronas' => 'Provincia di nascita',
        'counas' => 'Nazione di Nascita',
        'id_language' => 'Lingua',
        'id_currency' => 'Valuta',
        'sexper' => "Sesso/pers.giuridica ",
        'sexper_value' => array('' => '-', 'M' => 'Maschio', 'F' => 'Femmina', 'G' => 'Giuridica'),
        'indspe' => 'Indirizzo',
        'capspe' => 'Codice Postale',
        'citspe' => 'Citt&agrave; - Provincia',
        'prospe' => 'Provincia',
        'country' => 'Nazione',
        'latitude' => 'Latitudine',
        'longitude' => 'Longitudine',
        'telefo' => 'Telefono',
        'fax' => 'Fax',
        'cell' => 'Cellulare',
        'codfis' => 'Codice Fiscale',
        'pariva' => 'Partita IVA',
        'fiscal_reg' => 'Regime Fiscale',
        'fe_cod_univoco' => 'Cod.Univoco Destinatario (fatt.elettronica)',
        'pec_email' => 'Posta Elettronica Certificata',
        'e_mail' => 'e mail',
        'fatt_email' => 'Invio fattura:',
        'fatt_email_value' => array(0 => 'No, solo stampa PDF', 1 => 'In formato PDF su email', 2 => 'In formato XML su PEC', 3 => 'In formato PDF su email + XML su PEC')
    ),
	"report_municipalities.php" =>
    array('title' => 'Lista dei comuni',
        'id' => "ID",
        'name' => "Nome",
        'id_province' => "Provincia",
        'postal_code' => "Codice postale",
        'dialing_code' => "Prefisso tel.",
        'insert_mun' => 'Inserisci un nuovo comune',
		'name_search'=>'aggiungi "%" e invia per ricerca, oppure seleziona'
    ),
	"admin_municipalities.php" =>
    array('title' => 'Archivio dei comuni',
        'ins_this' => "Inserimento nuovo comune",
        'upd_this' => "Modifica il comune con ID:",
        'err' => array('name' => '&Egrave; necessario indicare il nome del comune',
            'postal_code' => '&Egrave; necessario indicare il codice postale',
            'email' => 'L\'indirizzo e-mail è sbagliato',
            'web_url' => 'L\'indirizzo del sito web è sbagliato'
        ),
        'id' => "ID",
        'name' => "Nome",
        'id_province' => "Provincia",
        'postal_code' => "Codice postale",
        'dialing_code' => "Prefisso telefonico",
        'stat_code' => 'Codice statistico',
		'code_register'=>'Codice alfanumerico',
		'web_url'=>'Sito web',
		'email'=>'E-Mail'
    ),
	"report_provinces.php" =>
    array('title' => 'Lista delle province',
        'id' => "ID",
        'name' => "Nome",
        'id_region' => "Regione",
        'abbreviation' => "Sigla provincia",
        'stat_code' => "Codice statistico",
        'insert_pro' => 'Inserisci una nuova provincia',
		'name_search'=>'aggiungi "%" e invia per ricerca, oppure seleziona'
    ),
	"admin_provinces.php" =>
    array('title' => 'Archivio delle province',
        'ins_this' => "Inserimento nuova provincia",
        'upd_this' => "Modifica la provincia con ID:",
        'err' => array('name' => '&Egrave; necessario indicare il nome della provincia',
            'abbreviation' => '&Egrave; necessario indicare la sigla della provincia',
            'email' => 'L\'indirizzo e-mail è sbagliato',
            'web_url' => 'L\'indirizzo del sito web è sbagliato'
        ),
        'id' => "ID",
        'name' => "Nome",
        'abbreviation' => "Sigla provincia",
        'id_region' => "Regione",
        'stat_code' => 'Codice statistico',
		'web_url'=>'Sito web',
		'email'=>'E-Mail'
    ),
	"admin_bank.php" =>
    array('title' => 'Archivio dei sportelli bancari',
        'ins_this' => "Inserimento nuovo sportello bancario",
        'upd_this' => "Modifica sportello bancario ID:",
        'err' => array('id_municipalities' => '&Egrave; necessario indicare il comune',
            'cap' => '&Egrave; necessario indicare il codice postale',
            'codabi' => 'Codice ABI non valido',
            'codcab' => 'Codice CAB non valido',
            'descriabi' => 'Descrizione banca non valida',
            'descricab' => 'Descrizione sportello non valido',
            'indiri' => 'Indirizzo non valido',
            'exist' => 'Sportello già in archivio'
        ),
        'id' => "ID",
        'iso_country' => "Nazione",
        'codabi' => "Codice ABI",
		'descriabi'=>'Banca',
        'codcab' => "Codice CAB",
		'descricab'=>'Descrizione sportello',
		'indiri'=>'Indirizzo',
		'id_municipalities'=>'Comune',
		'cap'=>'Codice postale'

    ),
    "report_ruburl.php" =>
    array('title'       => 'Archivio dei siti internet',
        'subtitle'      => 'Accesso rapido ai siti aziendali',
        'ins_this'      => "Inserimento nuovo indirizzo",
        'upd_this'      => "Modifica l'indirizzo",
        'id'            => "id",
        'description'   => "Descrizione",
        'address'       => "Indirizzo",
        'open'          => "Visita",
        'opentab'       => "Apri in un'altra finestra",
        'search'        => "Cerca",
        'delete'        => "Elimina",
        'category'      => "Categoria"
    ),
	"reconstruction_schedule.php" =>
    array('title' => 'Ricostruzione scadenzario da movimenti contabili',
        'err' => array('name' => '&Egrave; necessario indicare il nome della provincia',
            'abbreviation' => '&Egrave; necessario indicare la sigla della provincia',
            'email' => 'L\'indirizzo e-mail è sbagliato',
            'web_url' => 'L\'indirizzo del sito web è sbagliato'
        ),
        'id_partner' => "Cliente/Fornitore",
        'abbreviation' => "Sigla provincia",
        'id_region' => "Regione",
        'stat_code' => 'Codice statistico',
		'web_url'=>'Sito web',
		'email'=>'E-Mail'
    ),
    "admin_ruburl.php"  =>
    array('title'       => 'Aggiungi sito internet',
        'subtitle'      => 'Inserisci la descrizione e l\'indirizzo completo del tipo protocollo',
        'description'   => 'Descrizione',
        'address'       => 'Indirizzo',
        'category'      => 'Categoria',
        'other'         => 'Altro',
        'inscat'        => 'Inserisci una categoria',
        'insdes'        => 'Inserisci una descrizione',
        'insadd'        => 'Inserisci l\'indirizzo in formato URL',
        'back'          => 'Torna alla rubrica',
        'add'           => 'Aggiungi'
    ),
    "custom_from_fae.php" =>
      array('title' => 'Importa clienti e contratti utilizzando le fatture elettroniche',
      'disclaimer'=>'Da questa pagina potrai importare i clienti contenuti negli XML delle fatture elettroniche. La procedura è particolarmente comoda in fase di <b>migrazione da altri gestionali verso GAzie</b>. Basta ottenere un pacchetto con una esportazione massiva di fatture elettroniche di vendita dal portale "Fatture e Corrispettivi" oppure da uno dei tanti Terzi intermediari e poi caricare il pacchetto ZIP ottenuto direttamente qui. Si ricorda che per la trasmissione delle fatture di vendita si può continuare ad usare il portale del terzo intermediario, basta creare periodicamente il pacchetto ZIP e caricarlo sullo stesso.',
      'btn_acquire'=>'Carica il file',
      'war' => array(
          'ok_suppl' => 'Il cliente è già in archivio',
          'no_suppl' => 'Ho già questa anagrafica ma è un nuovo cliente',
          'no_anagr' => "Di questo nuovo cliente non ho l'anagrafica, utilizzerò questi dati per crearla",
          'no_db' => "Di questo file è stato fatto solo l'upload ma non è stata confermata la registrazione"
      ),
      'err' => array(
          'filmim' => 'Il file deve essere nel formato XML o P7M o uno ZIP che li contiene',
          'invalid_xml' => 'Il contenuto del file non è un XML valido',
          'invalid_fae' => 'Il contenuto del file XML non sembra essere una fattura elettronica',
          'no_upload' => 'File non caricato',
          'no_codpag' => 'Non hai selezionato la modalità di pagamento di default',
      ),
      'head_text1' => "Sotto sono riportate le anagrafiche dei clienti contenuti nel file: ",
      'head_text2' => "  che possono essere importate, fai le scelte in base alle esigenze",
      'pagame'=>'Modalità di pagamento',
      'nrow' => 'Rigo',
      'codart' => 'Codice',
      'descri' => 'Descrizione',
      'unimis' => 'U.M.',
      'quanti' => 'Quantità ',
      'prezzo' => 'Prezzo',
      'amount' => 'Importo',
      'sconto' => 'Sconto',
      'taxable' => 'Imponibile',
      'tax' => 'I.V.A.',
      'operation_type' => 'Tipo oper.',
      'conto' => 'Conto',
      'gencontract' => 'Genera contratti',
      'gencontract_value' => array(0 => 'No', 1 => 'Si, mensile con il valore del primo rigo', 2=>'Si, mensile con il valore del totale',3=>'Si, trimestrale con il valore del primo rigo', 4=>'Si, trimestrale con il valore del totale', 5=>'Si, mensile con i righi della prima fattura incontrata per il cliente', 6=>'Si, trimestrale con i righi della prima fattura incontrata per il cliente'),
      'genartico' => 'Genera articoli di magazzino',
      'genartico_value' => array(0 => 'No', 1 => 'Si, se inesistente e basandomi sul primo incontrato'),
    )
);
?>
