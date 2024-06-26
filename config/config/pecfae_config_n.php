<?php

/*
Questo file funziona come il gconfig.
Nel server web deve esserci un file pecfae_config_n.php con n=codice azienda (1 file per ogni azienda gestita)

*/



if (isset($_SERVER['SCRIPT_FILENAME']) && (str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME'])) {
   exit('Accesso diretto non consentito');
}

define('indirizzo_pec_azienda', "tua pecfae");
define('password_pec_azienda', "tua passw pecfae");
define('indirizzo_pec_SDI', "pec sdi a te assegnato");


?>
