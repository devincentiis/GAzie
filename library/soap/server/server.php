<?php
// Con public ottengo errore in PHP protected è senza nulla.
class GAzieWelcome {
	function getWelcome(){
	 	echo($name."<br>");
	    return 'Ciao sono GAzie';
	}
}
/* OPZIONALMENTE: Definire la versione del messaggio soap. Il secondo parametro non è obbligatorio. */
$server= new SoapServer("welcome.wsdl", array('soap_version' => SOAP_1_2));
$server->setClass("GAzieWelcome");
// Infine la funzione handle processa una richiesta SOAP e manda un messaggio di ritorno 
// al client che l’ha richiesta.
$server->handle();
?>