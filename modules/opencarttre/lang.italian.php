<?php
/*
  --------------------------------------------------------------------------
  Copyright (C) - Antonio De Vincentiis anno 2020
  tel.3383121161
  a.devincentiis@tiscali.it
  Montesilvano (PE)
  --------------------------------------------------------------------------
 */
$strScript = array("admin_staff.php" =>
    array('title' => 'Gestione del personale',
        'ins_this' => 'Inserisci un lavoratore',
        'upd_this' => 'Modifica  dati del lavoratore ',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 2 caratteri!',
            'Cambia anagrafica'),
        'errors' => array('&Egrave; necessario indicare Nome e Cognome',
            '&Egrave; necessario indicare l\'indirizzo',
            'Il codice di avviamento postale (CAP) &egrave; sbagliato',
            '&Egrave; necessario indicare la citt&agrave;',
            '&Egrave; necessario indicare la provincia',
            '&Egrave; necessario indicare il sesso',
            'L\'IBAN non &egrave; corretto',
            'L\'IBAN e la nazione sono diversi',
            'Codice fiscale sbagliato per una persona fisica',
            'La partita IVA &egrave; formalmente errata!',
            'Esiste gi&agrave un lavoratore con la stessa Partita IVA',
            'Il codice fiscale &egrave; formalmente errato',
            'Esiste gi&agrave; un lavoratore con lo stesso Codice Fiscale',
            'C.F. mancante! In automatico &egrave; stato<br />impostato con lo stesso valore della Partita IVA!',
            'E\' una persona fisica, inserire il codice fiscale',
            'Esiste una anagrafica con la stessa partita IVA',
            'Esiste una anagrafica con lo stesso Codice Fiscale',
            '&Egrave; necessario scegliere la modalit&agrave; di pagamento',
            'Il codice del cliente &egrave; gi&agrave; esistente, riprova l\'inserimento con quello proposto (aumentato di 1)',
            'La data di nascita &egrave; sbagliata',
            'Indirizzo email formalmente sbagliato',
            '&Egrave; necessario indicare il conto Mastro collaboratori in configurazione Azienda',
        ),
        'link_anagra' => ' Clicca sotto per inserire l\'anagrafica esistente sul piano dei conti',
        'codice' => "Codice ",
        'ragso1' => "Cognome",
        'ragso2' => "Nome",
        'luonas' => 'Luogo di nascita',
        'datnas' => 'Data di Nascita',
        'pronas' => 'Provincia di nascita',
        'counas' => 'Nazione di Nascita',
        'sexper' => "Sesso/pers.giuridica ",
        'sexper_value' => array('' => '-', 'M' => 'Maschio', 'F' => 'Femmina'),
        'indspe' => 'Indirizzo',
        'capspe' => 'Codice Postale',
        'citspe' => 'Citt&agrave; - Provincia',
        'prospe' => 'Provincia',
        'country' => 'Nazione',
        'telefo' => 'Telefono',
        'cell' => 'Cellulare',
        'codfis' => 'Codice Fiscale',
        'e_mail' => 'e mail',
        'id_agente' => 'Agente',
        'iban' => 'IBAN',
        'allegato' => 'Allegato IVA - Elenco Clienti',
        'yn_value' => array('S' => 'Si', 'N' => 'No'),
        'aliiva' => 'Riduzione I.V.A.',
        'ritenuta' => '% Ritenuta',
        'status' => 'Visibilit&agrave; alla ricerca',
        'status_value' => array('' => 'Attiva', 'HIDDEN' => 'Disabilitata'),
        'annota' => 'Annotazioni',
        'Codice_CCNL' => 'Codice CCNL',
        'job_title' => 'Mansione',
        'start_date'=>"Data assunzione",
		'end_date'=>"Data Fine Rapporto",
		'last_hourly_cost'=>'Ultimo costo orario'
    ),
    "staff_report.php" =>
    array('title' => 'Lista dei lavoratori',
        'errors' => '&Egrave; necessario indicare il conto Mastro collaboratori in configurazione Azienda',
        'header' => array(
            "Codice" => "codice",
            "Cognome" => "ragso1",
            "Nome" => "ragso2",
            "Sesso" => "sexper",
            "Mansione" => "job_title",
            "Città" => "citspe",
            "Telefono" => "telefo",
            "C.F." => "",
            "Visualizza <br /> e/o stampa"=>"",
            "Cancella" => "")
    ),
    "delete_staff.php" =>
    array('title' => 'Cancella l\'anagrafica del collaboratore',
        'errors' => 'Cliente non cancellabile perché ha  movimenti contabili!',
        "codice" => 'Codice',
        "ragso1" => 'Cognome',
        "ragso2" => 'Nome',
        "sexper" => 'Sesso',
        "job_title" => 'Mansione',
        "citspe" => 'Città',
        "telefo" => 'Telefono'
    ),
    "employee_timesheet.php" =>
    array('title' => "Registro delle presenze",
	      'err' => array('&Egrave; necessario indicare Nome e Cognome',
				'&Egrave; necessario indicare l\'indirizzo',
				'Il codice di avviamento postale (CAP) &egrave; sbagliato'),
		  'work_type'=>array(0=>array('ORD','Lavoro ordinario'),
							1=>array('STR','Lavoro straordinario'),
							2=>array('NOT','Lavoro notturno'),
							3=>array('DOM','Lavoro domenicale'),
							4=>array('FES','Lavoro festivo'),
							5=>array('MAG','Lavoro ordinario domenicale e/o festivo'),
							6=>array('TUR','Lavoro in turni')
						),
        'work_hou'=>"Ore lavorate Ordinarie",
        'cau_hextra'=>"Tipo di Straordinario",
        'work_hextra'=>"Ore Straordinarie",
        'absence_hou'=>"Ore di assenza",
        'absence_cau'=>"Causale assenza",
        'other_cau'=>"Altra causale",
        'other_qua'=>"Altra quantita",
		'note'=>"Note",
		'bot' => 'a riportare : ',
        'top' => 'da riporto :  ',
        'item_head' => array("id\nN.","Cognome\nNome"),
        'header' => array("id\nN.","Dati del\nCollaboratore","Tipo\nore"),
		'hours_normal'=>"ore normali",
		'hours_extra'=>"ore straordinarie",
		'absence_type'=>"causale assenza",
		'hours_absence'=>"ore assenza",
		'other_type'=>"altra causale",
		'hours_other'=>"altra quantità",
		)
);
?>