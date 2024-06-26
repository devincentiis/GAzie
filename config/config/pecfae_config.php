<?php

/*
Questo file funziona come il gconfig.
Nel server web deve esserci un file pecfae_configN.php con n=codice azienda (1 file per ogni azienda gestita)

*/



if (isset($_SERVER['SCRIPT_FILENAME']) && (str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME'])) {
   exit('Accesso diretto non consentito');
}

$admin_aziend = checkAdmin();
$fileConfPers=dirname(__FILE__) . '/pecfae_config_'.$admin_aziend['codice'].'.php';
if (file_exists($fileConfPers)) {
	include_once($fileConfPers);
}

define('indirizzo_pec_azienda', indirizzo_pec_azienda);
define('password_pec_azienda', password_pec_azienda);
define('indirizzo_pec_SDI', indirizzo_pec_SDI);

?>
