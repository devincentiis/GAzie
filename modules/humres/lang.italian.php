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
            'Matricola esistente'
        ),
        'link_anagra' => ' Clicca sotto per inserire l\'anagrafica esistente sul piano dei conti',
        'codice' => "Codice ",
        'ragso1' => "Cognome / Ragione sociale",
        'ragso2' => "Nome",
        'id_contract' => 'Numero di matricola',
        'luonas' => 'Luogo di nascita',
        'datnas' => 'Data di Nascita',
        'pronas' => 'Provincia di nascita',
        'counas' => 'Nazione di Nascita',
        'sexper' => "Sesso/pers.giuridica ",
        'sexper_value' => array('' => '-', 'M' => 'Maschio', 'F' => 'Femmina', 'G' => 'Giuridica'),
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
        'codice_campi'=>"Reparto/luogo di lavoro",
        'start_date'=>"Data assunzione",
		'end_date'=>"Data Fine Rapporto",
		'last_hourly_cost'=>'Ultimo costo orario'
    ),
    "staff_report.php" =>
    array('title' => 'Lista dei lavoratori',
        'errors' => '&Egrave; necessario indicare il conto Mastro collaboratori in configurazione Azienda',
        'header' => array(
            "Matricola" => "id_contract",
            "Cognome" => "ragso1",
            "Nome" => "ragso2",
            "Sesso" => "sexper",
            "Mansione" => "job_title",
            "Città" => "citspe",
			"IBAN" => "",
            "Telefono" => "",
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
		),
    "pay_salary.php" =>
    array('title' => 'Genera il file XML-CBI per bonifico massivo e contabilizza salari e stipendi ',
		  'title_upd' => 'Modifica la registrazione e rigenera il file XML-CBI dei bonifici salari e stipendi',
        'err' => array(
            'nopay' => 'Salario - stipendio non valorizzato',
            'noacc' => 'Non è stato selezionato il conto corrente di addebito',
            'expif' => 'La data di inizio non può essere precedente a quella dell\'ultimo emesso'
        ),
        'entry_date' => 'Data di registrazione',
        'orderby' => 'Ordina per: ',
        'target_account' => 'Conto corrente di addebito',
        'transfer_fees_acc' => 'Conto addebito spese bancarie',
        'transfer_fees' => 'Eventuali spese bancarie',
        'description' => 'Descrizione del movimento contabile',
        'description_value' => 'BONIFICO CEDOLINO PAGA',
        'status_value' => array(0 => 'APERTA', 1 => 'CHIUSA', 2 => 'ESPOSTA', 3 => 'SCADUTA', 9 => 'ANTICIPO'),
        'total' => ' N.',
        'confirm_entry' => 'Genera XML-CBI e contabilizza i bonifici',
        'confirm_entry_upd' => 'Rigenera l\'XML-CBI e aggiorna la contabilità dei bonifici',
        'upd_entry'=> 'Modifica il movimento contabile generato da questo documento'
    ),
    "report_pay_salary.php" =>
    array('title' => 'Lista bonifici per salari e stipendi ',
    )

);
?>
