<?php
try {
	$client = new SoapClient('https://localhost/gazie/library/soap/server/welcome.wsdl');
	echo("SOAP Client creato con successo!<br />");
	$result=$client->getWelcome();
	echo("Servizio Disponibile<br />");
	print_r("Risposta dal server: ".$result." <br />");
} catch (SoapFault $exception) {
	print_r($exception);
}
?>