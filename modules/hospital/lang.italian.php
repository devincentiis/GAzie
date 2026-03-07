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
$strScript = [
  "admin_patient.php" =>
  [ 'title' => 'Anagrafica paziente',
    'err' => ['first_name' => '&Egrave; necessario indicare il nome',
      'last_name' => '&Egrave; necessario indicare il cognome',
      'sexper' => '&Egrave; necessario indicare il genere',
      'tax_code' => 'Il codice fiscale &egrave; formalmente errato',
      'tax_code_exist' => 'C\'è un\'altra con lo stesso codice fiscale',
      'same_codfis' => 'Esiste gi&agrave; una anagrafica con lo stesso Codice Fiscale',
      'e_mail' => 'Indirizzo email formalmente sbagliato',
      'imgavatar_type' => 'Formato file foto non valido',
      'imgavatar_size' => 'File foto troppo grande',
      'patient_doc_type' => 'Formato file documento non valido',
      'patient_doc_size' => 'File documento troppo grande'
    ],
    'id_patient'=>"N.",
    'first_name'=>"Nome",
    'last_name'=>"Cognome",
    'sexper' => "Genere",
    'sexper_value' => [''=>'--------','M'=>'Maschio','F'=>'Femmina'],
    'birth_date'=>"Data di nascita",
    'birth_place'=>"Comune di nascita",
    'birth_prov_code'=>"Provincia di nascita",
    'birth_country'=>"Nazione di nascita",
    'tax_code'=>"Codice fiscale",
    'iban'=>"IBAN",
    'health_card_number'=>"Numero tessera sanitaria",
    'telephone'=>"Recapito telefonico",
    'residence_address'=>"Indirizzo di residenza",
    'residence_place'=>"Comune di residenza",
    'residence_postal_code'=>"CAP",
    'residence_prov_code'=>"Provincia di residenza",
    'affiliated_health_company'=>'ASL di appartenenza',
    'marital_status'=>'Stato civile',
    'marital_status_value'=> [''=>'--------','1'=>'celibe/nubile','2'=>'divorziato/a','3'=>'coniugato/a','4'=>'separato/a','5'=>'vedovo/a'],
    'e_mail'=>"e-mail ",
    'note'=>"Annotazioni",
    'doc_expiry'=>"Data scadenza documenti"
  ],
  "select_patient.php" =>
  [ 'title' => 'Cerca paziente',
    'first_name'=>"Nome",
    'last_name'=>"Cognome",
    'tax_code'=>"Codice fiscale",
    'health_card_number'=>"Numero tessera sanitaria"
  ],
  "patient_dashboard.php" =>
  [ 'title' => 'Cartella clinica',
    'first_name'=>"Nome",
    'last_name'=>"Cognome",
    'tax_code'=>"Codice fiscale",
    'health_card_number'=>"Numero tessera sanitaria"
  ],
  "report_healtworkers.php" =>[
    'title' => 'Lista degli operatori e sanitari',
    'menu'=>'Operatori & sanitari',
    'user_name' => 'Nickname',
    'user_lastname' => "Cognome",
    'user_firstname' => "Nome",
    'sexper' => "Genere",
    'Abilit' => "Accesso come",
    'Abilit_value' => ['7'=>'Dirigente di struttura','6'=>'Coordinatore','5'=>'Operatore','0'=>'Nessuno'],
    'Access' => "Accessi"
  ],
  "admin_healtworker.php" =>
  [ 'title' => 'Gestione degli operatori e sanitari',
    'ins_this' => 'Inserimento nuovo operatore',
    'upd_this' => 'Modificare l\'operatore',
    'err' => [
      'exlogin' => 'Il nickname &egrave; gi&agrave; usato!',
      'user_lastname' => 'Inserire il cognome!',
      "user_name" => "Inserire il nickname!",
      'Password' => "E' necessario inserire la Password !",
      'passold' => "La vecchia password &egrave; sbagliata!",
      'passlen' => "La password non &egrave; sufficientemente lunga!",
      'confpass' => "La password &egrave; diversa da quella di conferma!",
      'upabilit' => "Non puoi aumentare il tuo Livello di Abilitazione l'operazione &egrave; riservata all'amministratore!",
      'filmim' => "Il file dev'essere in formato JPG",
      'filsiz' => "L'immagine non dev'essere pi&ugrave; grande di 64Kb",
      'Abilit' => "Non puoi avere un livello inferiore a 9 perch&egrave; sei l'unico amministratore!",
      'Abilit_stud' => "Non puoi avere un livello inferiore a 7 perch&egrave; sei l'unico operatore!",
      'charpass' => "La password non può contenere caratteri alcuni speciali \" / > <",
      'email'=>'Indirizzo e-mail formalmente errato',
      'signimg_type'=>'L\'immagine della firma dev\'essere PNG con canale alpha (trasparenza) ',
      'signimg_noalpha'=>'L\'immagine della firma non ha il canale alpha (trasparenza)'
    ],
    'user_name' => 'Nickname',
    'user_lastname' => "Cognome",
    'user_firstname' => "Nome",
    'sexper' => "Genere",
    'sexper_value' => ['' => '-', 'M' => 'Maschio', 'F' => 'Femmina'],
    'indspe' => 'Indirizzo',
    'capspe' => 'Codice Postale',
    'citspe' => 'Città',
    'prospe' => 'Provincia',
    'codfis' => 'Codice Fiscale',
    'datnas' => 'Data di nascita',
    'pariva' => 'Partita IVA',
    'user_email' => "Mail (anche per recupero password)",
    'az_email' => "Mail aziendale dell'operatore",
    'image' => 'Immagine dell\'operatore<br />(solo in formato JPG, max 64kb)',
    'Abilit' => "Accesso come",
    'Abilit_value' => ['7'=>'Dirigente di struttura','6'=>'Coordinatore','5'=>'Operatore','0'=>'Nessuno'],
    'company'=>'Azienda',
    'mesg_co' => array('Non &egrave; stato trovato nulla!', 'Minimo 2 caratteri', 'Azienda di lavoro'),
    'Access' => "Accessi",
    'user_password_new' => 'Password',
    'user_active' => 'Abilitazione operatore',
    'user_active_value' => array('1'=>'Attivo','0'=>'Disattivo'),
    'lang' => 'Language',
    'theme' => 'Tipo di menù<br>(sarà attivo dal prossimo login)',
    'style' => 'Struttura dello stile',
    'skin' => 'Aspetto dello stile',
    'mod_perm' => 'Permessi dei moduli',
    'report' => 'Lista degli operatori',
    'del_this' => 'Utente',
    'del_err' => 'Non puoi cancellarti perch&egrave; sei l\'unico ad avere i diritti di amministratore! ',
    'body_text' => 'Testo contenuto nelle email inviate',
    'sector' => 'Settori abilitati',
    'sector_value' =>['fotov'=>'Fotovoltaico','clima'=>'Clima-Caldaia-PDC','luceg'=>'Luce & Gas'],
    'signimg' => 'Immagine della firma <br /><small>solo in formato PNG con trasparenza</small>',
    'id_contract' => 'Numero di matricola',
    'iban' => 'IBAN',
    'codice_campi'=>"Reparto/luogo di lavoro"
  ],
  "report_pharma.php" =>
  ['title' => 'Lista dei farmaci',
    'codice' => "Codice",
    'descri' => "Descrizione",
    'ricerca' => "Ricerca",
    'good_or_service' => "Merce-Servizio",
    'good_or_service_value' => [0=>'<span class="text-success bg-success"><b>Merce</b></span>',1=>'<span class="text-warning bg-warning"><b>Servizio</b></span>',2=>'Composizione'],
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
    'cosear'=>'aggiungi "%" e invia per ricerca, oppure seleziona',
    'clfoco'=>'Fornitore',
    'lot'=>'Lotto',
  ],
  "admin_pharma.php" =>
  ['title' => 'Farmaci',
   'ins_this' => 'Inserimento farmaco',
   'upd_this' => 'Modifica il farmaco ',
   'err' => [
        'codice' => 'Il codice farmaco &egrave; gi&agrave; esistente',
        'movmag' => 'Si st&agrave; tentando di modificare il codice ad un farmaco con dei movimenti di magazzino associati, usa l\'apposita procedura',
        'filmim' => 'Il file dev\'essere nel formato PNG, JPG, GIF',
        'filsiz' => 'L\'immagine non dev\'essere pi&ugrave; grande di 64Kb',
        'valcod' => 'Inserire un codice valido',
        'disbas' => 'Non puoi cambiare il codice ad un farmaco contenuto in una distinta base, usa l\'apposita procedura',
        'descri' => 'Inserire una descrizione',
        'unimis' => 'Inserire l\'unit&agrave; di misura delle vendite',
        'aliiva' => 'Inserire l\'aliquota I.V.A.',
        'lotmag' => 'Per avere la tracciabilità per lotti è necessario attivare la contabilità di magazzino in configurazione azienda',
        'no_ins' => 'Non sono riuscito ad inserire l\'farmaco sul database',
        'char' => 'Sul codice farmaco ho sostituito i caratteri speciali non consentiti con "_" ',
        'codart_len' => 'Il codice farmaco ha una lunghezza diversa da quella stabilita in configurazione avanzata azienda ',
        'no_web' => 'Per attivare l\'farmaco nell\'e-commerce è necessario che sia inserito il riferimento ID e-commerce nella scheda magazzino',
        'no_lot' => 'Un servizio non può avere lotti'
    ],
    'war' => [
        'ok_ins' => 'Farmaco inserito con successo'
    ],
    'codice' => "Codice",
    'descri' => "Descrizione",
    'good_or_service' => "Tipologia di farmaco",
    'good_or_service_value' => array(0 => 'Merce', 1 => 'Servizio', 2=> 'Composizione'),
    'body_text' => "Testo descrizione estesa",
    'body_text_val' => [0=>'non inserire', 1=>'prima del rigo',2=>'dopo il rigo'],
    'lot_or_serial' => 'Lotti o numeri seriali',
    'lot_or_serial_value' => array(0 => 'No', 1 => 'Lotti', 2 => 'Seriale/Matricola'),
    'barcode' => "Codice a Barre EAN13",
    'image' => "Immagine (jpg,png,gif) max 64Kb",
    'unimis' => "Unit&agrave; di misura vendite",
    'quality' => "Qualità",
    'larghezza' => "Larghezza (mm)",
    'lunghezza' => "Lunghezza (mm)",
    'spessore' => "Spessore (mm)",
    'bending_moment'=>"Resistenza es.Wx cm³",
    'catmer' => "Categoria merceologica",
    'ragstat' => "Raggruppamento statistico",
    'preacq' => array( 0=>'Costo di produzione', 1=>'Prezzo d\'acquisto', 2=>'Costo di produzione' ),
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
    'classif_amb' => 'Classificazione ambientale',
    'classif_amb_value' => array(0=>'non classificato',1=>'irritante',2=>'nocivo',3=>'tossico',4=>'molto tossico'),
    'SIAN' => 'Movimenta il SIAN',
    'SIAN_value' => array(0=>'non movimenta',1=>'come olio',2=>'come olive',6=>'come fitosanitario',7=>'come vino'),
    'maintenance_period' => 'Periodicità manutenzione (gg)',
    'peso_specifico' => 'Peso specifico (kg/l) o Moltiplicatore',
    'volume_specifico' => 'Volume specifico',
    'pack_units' => 'Pezzi in imballo',
    'codcon' => 'Conto di ricavo su vendite',
    'id_cost' => 'Conto di costo su acquisti',
    'annota' => 'Annotazioni (pubblicate anche sul web)',
    'fornitori-codici' => 'Codici Fornitori',
    'document' => 'Documenti e/o certificazioni',
    'imageweb' => 'immagini e foto',
    'web_mu' => 'Unit&agrave; di misura online',
    'web_price' => 'Prezzo di vendita online',
    'web_multiplier' => 'Moltiplicatore prezzo web',
    'web_public' => 'Sincronizza sul sito web',
    'web_public_value' => array(0 => 'No', 1 => 'Si', 2 => 'Attivo e prestabilito', 3 => 'Attivo e pubblicato in home', 4 => 'Attivo, in home e prestabilito', 5 => 'Disattivato su web'),
    'ordinabile_value' => array( '' => '----','S' => 'Ordinare','N'=> 'Non ordinare'),
    'movimentabile' => 'Farmaco Movimentabile',
    'movimentabile_value' => array ( '' => '----','S' => 'Si','N'=>'No','E' => 'Esaurito'),
    'codice_fornitore' => 'Produttore',
    'utilizzato' => 'Utilizzato',
    'depli_public' => 'Pubblica sul catalogo',
    'depli_public_value' => array(0 => 'No', 1 => 'Si'),
    'web_url' => 'Web url<br />(es: https://site.com/item.html)',
    'modal_ok_insert' => 'Farmaco inserito con successo clicca sulla X in alto a destra per uscire oppure...',
    'iterate_invitation' => 'INSERISCI UN ALTRO ARTICOLO DI MAGAZZINO',
    'browse_for_file' => 'Sfoglia',
    'id_anagra' => 'Fornitore di riferimento',
    'last_buys' => 'Ultimi acquisti da fornitori',
    'ordinabile' => 'Farmaco ordinabile',
    'durability_mu' => 'Unità di misura durabilità',
    'durability' => 'Valore durabilità',
    'warranty_days' => 'Giorni di garanzia',
    'unita_durability' => array('' => '', '>' => '>', '<' => '<', 'H' => 'H', 'D' => 'D', 'M' => 'M'),
    'mesg' => ['La ricerca non ha dato risultati!',
        'Inserire almeno 1 carattere!',
        'Cambia fornitore'
      ]
  ],
  "admin_bed.php" =>
  ['title'=>'Posto letto',
    'ins_this' => 'Inserimento nuovo posto letto',
    'upd_this' => 'Modifica il posto letto',
    'err'=>[
      'id_room' => 'Indicare la stanza',
      'bedname' => 'Il nome del posto letto deve essere lungo almeno 1 carattere',
      'existname' => 'Esiste un posto letto con lo stesso nome nella stessa stanza'
    ],
    'war'=>[
     'ok_ins' => 'Posto letto inserito con successo'
    ],
    'id_room' => 'Stanza/Reparto',
    'bedname' => 'Nome o numero',
    'note_other' => 'Note o altro'
  ],
  "report_beds.php" =>
  ['title'=>'Posti letto',
    'id_bed' => 'ID',
    'id_room' => 'Stanza/Reparto',
    'id_ward' => 'Reparto',
    'bedname' => 'Nome o numero',
    'note_other' => 'Note o altro'
  ],
  "admin_room.php" =>
  ['title'=>'Stanza',
    'ins_this' => 'Inserimento nuova stanza',
    'upd_this' => 'Modifica la stanza',
    'err'=>[
      'id_ward' => 'Indicare il reparto',
      'roomname' => 'Il nome della stanza deve essere lungo almeno 1 carattere',
      'existname' => 'Esiste una stanza con lo stesso nome nello stesso reparto'
    ],
    'war'=>[
     'ok_ins' => 'Stanza inserita con successo'
    ],
    'id_ward' => 'Reparto',
    'roomname' => 'Nome o numero',
    'note_other' => 'Note o altro'
  ],
  "report_rooms.php" =>
  ['title'=>'Stanze',
    'id_room' => 'ID',
    'id_ward' => 'Reparto',
    'roomname' => 'Nome o numero',
    'note_other' => 'Note o altro'
  ],
  "admin_ward.php" =>
  ['title'=>'Reparto',
    'ins_this' => 'Inserimento nuovo reparto',
    'upd_this' => 'Modifica reparto',
    'err'=>[
      'wardname' => 'Il nome del reparto deve essere lungo almeno 3 caratteri',
      'existname' => 'Esiste un reparto con lo stesso nome nella stessa stanza'
    ],
    'war'=>[
     'ok_ins' => 'Reparto inserito con successo'
    ],
    'wardname' => 'Nome del raparto',
    'note_other' => 'Note o altro'
  ],
  "report_wards.php" =>
  ['title'=>'Reparti',
    'id_ward' => 'ID',
    'bedname' => 'Nome o numero',
    'note_other' => 'Note o altro'
  ],
  "admin_admission.php" =>
  [
    'mesg' => ['La ricerca non ha dato risultati!',
        'Inserire almeno 2 caratteri!',
        'Cambia cliente/fornitore'],
    'err' =>[
        'id_con' => 'paziente non selezionato',
        'tutor_descri8' => 'Necessaria la descrizione su "Altro" tutore',
        'tutor_descri1' => 'Necessario il grado su "Parente" tutore',
        'tutor_fname'  => 'Nome del tutore non indicato',
        'tutor_lname' => 'Cognome del tutore non indicato',
        'tutor_sex' => 'Genere del tutore non indicato',
        'bed' => 'Se in regime residenziale occorre indicare il letto',
        'regime' => 'Regime non indicato'
    ],
    'title' => 'Ammissione',
    'seziva' => 'Letto',
    'regime' => 'Regime',
    'regime_value' =>[''=>'-----------','0'=>'Residenziale','1'=>'Semi-residenziale','2'=>'Day-hospital'],
    'tutor' => 'in qualità di ',
    'tutor_value' =>[''=>'------------','1'=>'Parente, necessario grado =>','2'=>'Garante','3'=>'Tutore','4'=>'Amministratore di sostegno','8'=>'Altro, necessaria descrizione =>','9'=>'Tutela non necessaria'],
    'tutor_fname'=>"Nome tutore",
    'tutor_lname'=>"Cognome tutore",
    'tutor_sex' => "Genere del tutore",
    'tutor_sex_value' => [''=>'--------','M'=>'Maschio','F'=>'Femmina'],
    'tutor_birth_date'=>"Data di nascita",
    'tutor_birth_place'=>"Comune di nascita",
    'tutor_birth_prov_code'=>"Provincia di nascita",
    'tutor_birth_country'=>"Nazione di nascita",
    'tutor_tax_code'=>"Codice fiscale",
    'tutor_telephone'=>"Recapito telefonico",
    'tutor_residence_address'=>"Indirizzo di residenza",
    'tutor_residence_place'=>"Comune di residenza",
    'tutor_residence_postal_code'=>"CAP",
    'tutor_residence_prov_code'=>"Provincia di residenza"
  ],
  "admin_medical_record_sections.php" =>
  ['title'=>'Sezione delle cartelle cliniche',
    'ins_this' => 'Inserimento nuova sezione',
    'upd_this' => 'Modifica la sezione delle cartelle cliniche',
    'err'=>[
      'existname' => 'Esiste una sezione con lo stesso nome',
      'description' => 'La descrizione dev\'essere lunga almeno 6 caratteri'
    ],
    'war'=>[
     'ok_ins' => 'Sezione inserita con successo'
    ],
    'description' => 'Descrizione',
    'var' => 'Note o altro'
  ],
  "report_medical_record_sections.php" =>
  ['title'=>'Sezioni delle cartelle cliniche',
    'descri' => 'Descrizione',
    'var' => 'Note o altro'
  ]
];
?>
