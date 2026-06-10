<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-20223 - Antonio Germani, Massignano (AP)
  (http://www.programmisitiweb.lacasettabio.it)
  --------------------------------------------------------------------------
  --------------------------------------------------------------------------
Copyright (C) - Antonio Germani Massignano (AP) https://www.lacasettabio.it - telefono +39 340 50 11 912
  --------------------------------------------------------------------------
   --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2023 - Antonio De Vincentiis Montesilvano (PE)
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
  --------------------------------------------------------------------------

  --------------------------------------------------------------------------
  --------------------------------------------------------------------------
NOTA BENE:
-Copiare questo file fuori dalla cartella pubblica del dominio (cioè fuori dalla root web), in modo che non sia raggiungibile via browser, in una cartella config (se non c'è crearla).
-Rinominarlo in vacation_rental_settings.php dopo averlo incollato.
-Esempio: se la root web è /home/server/tuosito.it, spostare il file in /home/server/config/vacation_rental_settings.php.
-Assicurarsi che il file abbia permessi adeguati (lettura per il processo PHP, non scrivibile pubblicamente) e che non venga committato nel repository (aggiungere a .gitignore).
-Infine compilare le dovute impostazioni
  --------------------------------------------------------------------------
  --------------------------------------------------------------------------


*/

if (count(get_included_files()) ==1 OR basename($_SERVER['PHP_SELF']) == basename(__FILE__)){// impedisce accesso diretto
  exit('Restricted Access');
}else{
	$idDB="_001";// ID azienda per stabilire a quale ID azienda del data base dovrà accedere il front-end del sito web
	$token="yourtokenword"; // inserisci una parola chiave, che verrà usata dagli script, per bloccare gli accessi diretti.
	$smtp_pass="xxxxxxx"; // la password e-mail smtp (con la nuova criptazione non posso più prenderla dal DB)
	$imap_pwr="xxxxxxx";
	$seziva="1"; // la sezione iva da inserire nelle nuove prenotazioni
	$stripe_con="xxxxxxx"; // numero codice conto prima nota Stripe
	$return_url="https://gmonamour.it";
	$return_url_userDashboard="./modules/vacation_rental/NEWuser_dashboard.php?lang=";
	$return_url_extra="https://www.gmonamour.it/it/service/grazie-extra";

return [
    'host' => 'localhost',
    'dbname' => 'xxxxxx_gazie',
    'user' => 'xxxxxx_gazie',
    'pass' => 'xxxxxxxx',
	'return_url' => "https://gmonamour.it",
	'return_url_userDashboard' => "https://gestgazie.lacasettabio.it/modules/vacation_rental/user_dashboard.php?lang=",
	'return_url_extra' => "https://www.gmonamour.it/it/service/grazie-extra",
	'idDB' => "_001",// ID azienda per stabilire a quale ID azienda del data base dovrà accedere il front-end del sito web
	'token' =>"yourtokenword", // inserisci una parola chiave, che verrà usata dagli script, per bloccare gli accessi diretti.
	'smtp_pass' => "xxxxxxxx", // la password e-mail smtp (con la nuova criptazione non posso più prenderla dal DB)
	'imap_pwr' => "xxxxxxxx",
	'seziva' => "1", // la sezione iva da inserire nelle nuove prenotazioni
	'stripe_con' => "xxxxxxxx"
];
}
?>
