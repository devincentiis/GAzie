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


 /*
--------=======oooooooooooo!!!!!  ATTENZIONE !!!!!ooooooooooo========-------------
QUESTO FILE DI CONFIGURAZIONE CONTIENE UNA SERIE DI SETTAGGI ADATTI AD UN AMBIENTE DI
SVILUPPO E POTENZIALMENTE INSICURO IN QUANTO AD ES. USA IL NOME UTENTE root SENZA
PASSWORD PER ACCEDERE AL DATABASE.
PER POTER USARE GAZIE IN PRODUZIONE OCCORRE PERSONALIZZARE QUESTI PARAMETRI CAMBIANDO
IL NOME DEL FILE "gconfig.myconf.default.php" IN "gconfig.myconf.php".
SUCCESSIVAMENTE "gconfig.myconf.php" LO SI DOVRA' MODIFICARE IN ACCORDO CON LE
IMPOSTAZIONI DEL VOSTRO SERVER. FACENDO COSI' EVITERETE DI USARE I SETTAGGI CONTENUTI
IN QUESTO FILE CHE SONO APPUNTO INADATTI E CHE VERREBBE SOVRASCRITTO AD OGNI AGGIORNAMENTO
DI GAZIE, AGGIORNAMENTO CHE CONSISTE, APPUNTO, NELLA SOVRASCRITTURA DI TUTTI I FILES
DELLA CARTELLA "gazie" COMPRESO IL PRESENTE "gconfig.php"
*/



 if (isset($_SERVER['SCRIPT_FILENAME']) && (str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME'])) {
    exit('Accesso diretto non consentito');
}

$fileConfPers=dirname(__FILE__) . '/gconfig.myconf.php';
if (file_exists($fileConfPers)) {
	include_once($fileConfPers);
}


// abilita il debug delle variabili nel footer della pagina (impostare true/false)
$debug_active = (defined('debug_active')) ? debug_active : FALSE;

// definisco il livello di verbosità degli errori (https://www.php.net/manual/en/errorfunc.constants.php)
$error_reporting_level = (defined('error_reporting_level')) ? error_reporting_level : 0;

// attiva la modalità manutenzione non è consentito l'accesso e l'uso dei moduli (FALSE oppure 'email amministratore')
$maintenance = (defined('maintenance')) ? maintenance : FALSE;


//nome DBMS usato per la libreria specifica (MySQL=mysql.lib, SQLite=sqlite.lib, ecc)
//per il momento disponibile solo la libreria mysql.lib
$NomeDB = (defined('NomeDB')) ? NomeDB : 'mysqli';

////////////////////////////////////////////////////////////////////////
//
// Parametri di accesso: server, db, utente, passwd
//
////////////////////////////////////////////////////////////////////////
//
// Server MySQL. Si può specificare anche la porta per connettersi a
// MySQL, per esempio:
//
// $Host = "mysql.2freehosting.com:3306";
//
$Host = (defined('Host')) ? Host : 'localhost';

//
// Nome della base di dati a cui ci si connette.
//
$Database = (defined('Database')) ? Database : 'gazie';

//
// Utente della base di dati che ha il permesso di accedervi con tutti
// i privilegi necessari.
//
$User = (defined('User')) ? User : 'root';

//
// Parola d'ordine necessaria per accedere alla base di dati
// in qualità di utente $User.
//
$Password = (defined('Password')) ? Password : '';

//
// Porta sulla quale è in ascolto il database (normalmente 3306 per mysql, 3307 per mariadb)
$Port = (defined('Port')) ? Port : 3306;

//
// Prefisso delle tabelle di Gazie.
//
// ATTENZIONE: il prefisso delle tabelle predefinito è "gaz". Eventualmente, si
// possono usare altri prefissi, ma composti sempre dai primi tre caratteri
// "gaz" e seguiti da un massimo di nove caratteri, costituiti da lettere
// minuscole e cifre numeriche. Per esempio, "gaz123" è valido, mentre "gaga1"
// o "gaz_123" non sono validi.
//
$table_prefix = (defined('table_prefix')) ? table_prefix : 'gaz';

//
// Fuso orario, per la rappresentazione corretta delle date, indipendentemente
// dalla collocazione del server HTTP+PHP. MA NON FUNZIONA, perché MySQL aggiorna
// in modo indipendente le date di accesso alle tabelle.
//
$Timezone = (defined('Timezone')) ? Timezone : 'Europe/Rome';

//
// Testo da aggiungere eventualmente ai messaggi di posta elettronica, sistematicamente,
// per qualche motivo.
//
define('EMAIL_FOOTER', (defined('MY_EMAIL_FOOTER')) ? MY_EMAIL_FOOTER : 'E-mail generata da GAzie ver.');

//
// GAzie utilizza la funzione PHP set_time_limit() per consentire il completamento
// di elaborazioni che richiedono più tempo del normale.
// In condizioni normali, la variabile $disable_set_time_limit deve corrispondere
// a FALSE. La modifica del valore a TRUE serve solo in situazioni eccezionali,
// per esempio quando si vuole installare GAzie presso un servizio che vieta
// l'uso della funzione set_time_limit(), sapendo però che ciò pregiudica il funzionamento
// corretto di GAzie.
//
$disable_set_time_limit = (defined('disable_set_time_limit')) ? disable_set_time_limit : FALSE;

//
// Se il servente HTTP-PHP non ha una configurazione locale corretta,
// questa può essere impostata qui, espressamente.
//
$gazie_locale = (defined('gazie_locale')) ? gazie_locale : '';

//
// Numero di righe per pagina sui report, determina anche quante ne saranno caricate dallo scroll-onload
//
define('PER_PAGE', (defined('MY_PER_PAGE')) ? MY_PER_PAGE : 30);

//
// Le seguenti definizioni assegnano il percorso delle directory che devono essere scrivibili
// dal web server.
//
// Directory usata da modules/root/retrieve.php
//
define('DATA_DIR', (defined('MY_DATA_DIR')) ? MY_DATA_DIR : '../../data/');

//
// Directory usata dal modulo tcpdf
//
define('K_PATH_CACHE', (defined('MY_K_PATH_CACHE')) ? MY_K_PATH_CACHE : '../../data/files/tmp/');

////////////////////////////////////////////////////////////////////////
// definisce il nome della sessione ma solo in caso di uso dei domini di livello superiore al secondo, in
// caso di installazione su domini di secondo livello viene attribuito automaticamente
// il nome del direttorio di installazione
define('_SESSION_NAME', (defined('MY_SESSION_NAME')) ? MY_SESSION_NAME : 'technical');

//url di default per l'aggiornamento di GAzie
$update_URI_files = (defined('update_URI_files')) ? update_URI_files : 'https://sourceforge.net/projects/gazie';

// url per comunicare (ping) il mio nuovo IP DINAMICO  all'hosting di appoggio
define('SET_DYNAMIC_IP', (defined('MY_SET_DYNAMIC_IP')) ? MY_SET_DYNAMIC_IP : '');

// directory help personalizzati (normalmente usato dai files modules/nomemodulo/docume_nomemodulo.php
define( 'HELPDIR',  (defined('MY_HELPDIR')) ? MY_HELPDIR : 'help');

// versione software
define( 'GAZIE_VERSION',  (defined('MY_GAZIE_VERSION')) ? MY_GAZIE_VERSION: '9.10');

// versioning degli asset (file statici)
//define('STATIC_VERSION', ''); // se si usa nginx e non c'è la direttiva su nginx.conf si disattiva con la stringa vuota
define('STATIC_VERSION', 'gazieVersion' . GAZIE_VERSION . '/');

// permetti la modifica dei ddt fatturati, utile se bisogna modificare i prezzi degli articoli
$modifica_fatture_ddt = (defined('modifica_fatture_ddt')) ? modifica_fatture_ddt : FALSE;

$contact_link = (defined('MY_CONTACT_LINK')) ? MY_CONTACT_LINK : 'devincentiis.it';
?>
