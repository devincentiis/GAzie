<?php
/*
QUESTO MODULO PERMETTE DI GESTIRE LA FATTURA ELETTRONICA PER MEZZO PEC SDI IN MANIERA ANALOGA ALLE LIBRERIE DI TERZE PARTI (TIPO catsrl)
IN CONFIGURAZIONE AVANZATA AZIENDA SI DEVE METTERE ALLA VOCE
"Nome della libreria di terze parti da usare per la eventuale trasmissione delle fatture elettroniche"
IL VALORE:   PecARUBA_SDI
PER LA CONFIGURAZIONE DELLA PEC UTILIZZA: config/config/pecfae_config_1.php (da creare copiando pecfae_config.php e valorizzandolo con i dati pec)
IN QUESTO MODO LA GESTIONE FAE PEC È AUTONOMA E LE EVENTUALI MAIL UTILIZZATE ALL'INTERNO DEL PROGRAMMA POSSONO CONTINUARE LA LORO GESTIONE ORDINARIA
SI CONSIGLIA DI FARE UNA PEC DEDICATA ALLA FATTURAZIONE ELETTRONICA (DIVERSA DALLA PEC AZIENDALE)

IL MODULO TRASMETTE, SCARICA LE RICEVUTE E LE FATTURE ACQUISTI DALLA PEC DELLO SDI
I FILE XML DA TRASMETTERE DEVONO ESSERE IN DATA_DIR/files/1
LE RICEVUTE DELLO SDI VENGONO SALVATE IN DATA_DIR/files/1/ricevutesdi
LE FATTURE ACQUISTI VENGONO SALVATE IN DATA_DIR/files/1/FAE_ACQUISTI
dove la cartella 1 è il codice azienda (se si lavora con l'azienda 2 sarà 2 etc...)
*/

// **********************  INIZIO DA VERIFICARE ***************
function SendFattureElettroniche($zip_fatture) {
	//require("../../library/include/datlib.inc.php");
	global $gTables ;
	$admin_aziend = checkAdmin();
	if (! isset($zip_fatture)) {
		echo "manca pacchetto fatture" ;
		return false ;
	} else {
		$content = new StdClass;
		$aurl=explode("/",$zip_fatture) ;
		//$content->name = substr($zip_fatture,-23,23);
		$content->name = $aurl[count($aurl)-1] ;
		$content->urlfile = $zip_fatture; // se passo l'url GAzieMail allega un file del file system e non da stringa
		$dest_fae_zip_package['e_mail'] = gaz_dbi_get_row($gTables['company_config'], 'var', 'dest_fae_zip_package')['val'];
		echo $content->urlfile . "<br/>" ;
		echo $content->name . "<br/>" ;
		echo $zip_fatture . "<br/>";
		if (!empty($dest_fae_zip_package['e_mail'])) {
			$gMail = new C_PecARUBA_SDI();
			if ($gMail->sendMail($admin_aziend, $user, $content)){
				// se la mail è stata trasmessa con successo aggiorno lo stato sulla tabella dei flussi
				gaz_dbi_put_query($gTables['fae_flux'], "filename_zip_package = '" . $content->name."'", "flux_status", "@");
				$data_invio = date("Y-m-d") ;
				// metto la data odierna come data di invio exec_date
	//			gaz_dbi_put_query($gTables['fae_flux'], "filename_zip_package = '" . $content->name."'", "exec_date", $data_invio);
				echo "<p>INVIO PACCHETTO FATTURE ELETTRONICHE RIUSCITO!!!</p>";
			}
		}
		return 0 ;
	}
}

// **********************  FINE DA VERIFICARE ***************

function SendFatturaElettronica($xml_fattura) {
	//require("../../library/include/datlib.inc.php");
	global $gTables ;
	$admin_aziend = checkAdmin();
	if (! isset($xml_fattura)) {
		echo "manca file fattura" ;
		return false ;
	} else {
		$content = new StdClass;
		$aurl=explode("/",$xml_fattura) ;
		//$content->name = substr($xml_fattura,-23,23);
		$content->name = $aurl[count($aurl)-1] ;  // l'ultima parte dell'url è il nome del file
		$content->urlfile = $xml_fattura; // se passo l'url GAzieMail allega un file del file system e non da stringa
		//	$dest_fae_zip_package['e_mail'] = gaz_dbi_get_row($gTables['company_config'], 'var', 'dest_fae_zip_package')['val'];
		echo $content->urlfile . "<br/>" ;
		echo $content->name . "<br/>" ;
		echo $xml_fattura . "<br/>";
		//		if (!empty($dest_fae_zip_package['e_mail'])) {
		//			$gMail = new GAzieMail();
		$gMail = new C_PecARUBA_SDI();
		if ($gMail->sendMail($admin_aziend, $user, $content)){
			// se la mail è stata trasmessa con successo aggiorno lo stato sulla tabella dei flussi
			gaz_dbi_put_query($gTables['fae_flux'], "filename_ori = '" . $content->name."'", "flux_status", "@");
			$data_invio = date("Y-m-d") ;
			// metto la data odierna come data mail
			gaz_dbi_put_query($gTables['fae_flux'], "filename_ori = '" . $content->name."'", "data", $data_invio);

			echo "<p>INVIO FATTURA ELETTRONICA RIUSCITO!!!</p>";
		}
		//		}
		return 0 ;
	}
}

function ReceiveFattF($array_fattf) {
	global $gTables ;
	$admin_aziend = checkAdmin();
	require_once('../../library/php-imap/ImapMailbox.php');
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Turn off PHP output compression
	ini_set('zlib.output_compression', false);
	//Flush (send) the output buffer and turn off output buffering
	//ob_end_flush();
	while (@ob_end_flush());
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);
	//Alcuni browser non iniziano ad eseguire output fino a quando non viene superato un certo numero di byte
	for($i = 0; $i < 1300; $i++)
	{
		echo ' ';
	}
	set_time_limit(3600);

	// IMAP
	require_once('../../config/config/pecfae_config.php');  // questo file ha funzionamento analogo a gconfig.php e gconfig.myconf.php:
																												// include ulteriori file pecfae_config_n.php con n=codice azienda lavoro
																												// in cui sono contenuti:
																												// l'account PEC aziendale per fatturazione elettronica
																												// e indirizzo PEC SDI assegnato
																												// N.B. questi file aziendali devono essere MOLTO RISERVATI ******
	$cemail['val'] = indirizzo_pec_azienda ;
	$cpassword['val'] = password_pec_azienda;
	$cmailSDI['val'] = indirizzo_pec_SDI;
	//$cmailSDI = gaz_dbi_get_row($gTables['company_config'],'var','dest_fae_zip_package');
	$cfiltro['val'] = "UNSEEN" ;
	$cpopimap['val'] = "{imaps.pec.aruba.it/ssl}" ;
	define('CATTACHMENTS_DIR', DATA_DIR . 'files/' . $admin_aziend['codice'] . '/FAE_ACQUISTI');
	if (! is_dir(CATTACHMENTS_DIR)) {
		if (mkdir(CATTACHMENTS_DIR,0777)) {
			echo ' Creata cartella ' . CATTACHMENTS_DIR . ' <br/>';
		} else {
			echo ' Non posso creare la cartella ' . CATTACHMENTS_DIR . ' <br/>';
			echo ' Verifica i permessi della cartella ' . DATA_DIR . 'files/'.$admin_aziend['codice']. ' <br/>';
		}
	}
	//	$mailbox = new ImapMailbox($cpopimap['val'], $cemail['val'], $cpassword['val'], CATTACHMENTS_DIR, 'utf-8');
	$mailbox = new ImapMailbox($cpopimap['val'], $cemail['val'], $cpassword['val'], CATTACHMENTS_DIR, 'utf-8');
	$mails = array();
	//se passato checkall verranno riscaricate tutte le email senza tener conto dell'eventule filtro: UNSEEN (solo non lette)
	if (isset($_GET['checkall'])) {
		$cfiltro['val'] = str_replace("UNSEEN","", $cfiltro['val']);
	}
	// Get some mail
	$mailsIds = $mailbox->searchMailBox($cfiltro['val'] );
	if(!$mailsIds) {
		echo('Nessuna nuova email con questo filtro: ' . $cfiltro['val']);
		//  die("<p align=\"center\"><a href=\"./acquire_invoice.php\">Ritorna a acquisisci fatture</a></p>");
	}
	echo "Attendere: Verifico la posta elettronica sulla casella " . $cemail['val'] ."    ";
	$n_email = count($mailbox->getMailsInfo($mailsIds));
	echo "N. email da controllare: " . $n_email ."<br />";
	$allegato1 = new IncomingMailAttachment();
	$allegato2 = new IncomingMailAttachment();
	$domDoc = new DOMDocument;
	$identif_iva_az_lavoro = "IT".$admin_aziend['codfis'] ;
	foreach($mailsIds as $mailId) {
		$mail = $mailbox->getMail($mailId);
		$data_mail =  $mail->date;
		$mittente = substr($mail->fromName,-strlen($cmailSDI['val']),strlen($cmailSDI['val']));
		$aaa = $mail->getAttachments();
		$ccc = array_values($aaa);
		if (count($ccc) == 0) {
			//echo "senza allegato " ;
			// $mailbox->markMailAsUnread($mailId) ;
			continue ;
		}
		$allegato1 = $ccc[0];
		$nome_allegato1 = $allegato1->name;
		$info_allegato1=explode( '_', $nome_allegato1);
		if ($mittente != $cmailSDI['val'] )
		{
			// non proviene da PEC SDI: non la considero, elimino l'allegato dal filesystem  e riprendo il ciclo
			// $mailbox->markMailAsUnread($mailId) ;
			unlink(CATTACHMENTS_DIR.'/'.$nome_allegato1) ;
			continue ;
		}
		if ($info_allegato1[0] == $identif_iva_az_lavoro) {
			// Proviene da SDI e ha per identificativo iva quello dell'azienda di lavoro : trattasi di ricevuta per fattura vendita trasmessa,
			// segno mail come da leggere per poterla poi prendere da vendite, cancello il file allegato dal filesystem e riprendo il ciclo
			$mailbox->markMailAsUnread($mailId) ;
			unlink(CATTACHMENTS_DIR.'/'.$nome_allegato1) ;
			continue ;
		}
		// Proviene da SDI e non ha nostro identificativo azienda lavoro: trattasi di fattura acquisto
		// trattasi di fattura di nostro fornitore: avrà 2 allegati la fattura e file Metadati SDI
		$data_ora_ricezione = $data_mail ;
		$data_ora_consegna=$data_mail;
		$allegato2 = $ccc[1];
		$nome_allegato2 = $allegato2->name;
		$info_allegato2=explode( '_', $nome_allegato2);
		echo "Arrivata fattura Acquisto da: " . $info_allegato1[0] . "<br/>";
		if ($info_allegato1[2] == "MT") {  // l'allegato 1 è il file Metadati da cui ricavo l'identificativo SDI
			$domDoc->load($allegato1->filePath);
			$xpath = new DOMXPath($domDoc);
			$result = $xpath->query("//IdentificativoSdI")->item(0);
			$id = $result->textContent;
			$FattF[$id]['idsdi'] = $id;
			$FattF[$id]['ricezione'] = substr($data_mail,0,10) ;
			$domDoc->load($allegato2->filePath);
			$xpath = new DOMXPath($domDoc);
			$result = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0);
			$FattF[$id]['fornitore'] = $result->textContent ;
			$result = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Numero")->item(0);
			$FattF[$id]['numero'] = $result->textContent ;
			$result = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Data")->item(0);
			$FattF[$id]['data_fatt'] = $result->textContent ;
			$FattF[$id]['nome_file'] = $nome_allegato2 ;
		} else {  // l'allegato 1 è la FATTURA
			$domDoc->load($allegato2->filePath);
			$xpath = new DOMXPath($domDoc);
			$result = $xpath->query("//IdentificativoSdI")->item(0);
			$id = $result->textContent;
			$FattF[$id]['idsdi'] = $id;
			$FattF[$id]['ricezione'] = substr($data_mail,0,10) ;
			$domDoc->load($allegato1->filePath);
			$xpath = new DOMXPath($domDoc);
			$result = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0);
			$FattF[$id]['fornitore'] = $result->textContent ;
			$result = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Numero")->item(0);
			$FattF[$id]['numero'] = $result->textContent ;
			$result = $xpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Data")->item(0);
			$FattF[$id]['data_fatt'] = $result->textContent ;
			$FattF[$id]['nome_file'] = $nome_allegato1 ;
		}
	}
	echo "Completato ... sono state ricevute N.".count($FattF)." fatture acquisto <br/>" ;
	echo "Puoi importarle da ". CATTACHMENTS_DIR .' usando il Pulsante SFOGLIA';

	return $FattF ;
	
}

function ReceiveNotifiche () {
	require_once('../../library/php-imap/ImapMailbox.php');
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Turn off PHP output compression
	ini_set('zlib.output_compression', false);
	//Flush (send) the output buffer and turn off output buffering
	//ob_end_flush();
	while (@ob_end_flush());
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);
	//Alcuni browser non iniziano ad eseguire output fino a quando non viene superato un certo numero di byte
	for($i = 0; $i < 1300; $i++)
	{
		echo ' ';
	}
	set_time_limit(3600);
	$admin_aziend = checkAdmin();
	global $gTables;
	// IMAP
	require_once('../../config/config/pecfae_config.php');
	$cemail['val'] = indirizzo_pec_azienda ;
	$cpassword['val'] = password_pec_azienda;
	$cmailSDI['val'] = indirizzo_pec_SDI;
	//$cmailSDI = gaz_dbi_get_row($gTables['company_config'],'var','dest_fae_zip_package');
	$cfiltro['val'] = "UNSEEN" ;
	$cpopimap['val'] = "{imaps.pec.aruba.it/ssl}" ;
	define('CATTACHMENTS_DIR', DATA_DIR . 'files/' . $admin_aziend['codice'] . '/ricevutesdi');
	if (! is_dir(CATTACHMENTS_DIR)) {
		if (mkdir(CATTACHMENTS_DIR,0777)) {
			echo ' Creata cartella ' . CATTACHMENTS_DIR . ' <br/>';
		} else {
			echo ' Non posso creare la cartella ' . CATTACHMENTS_DIR . ' <br/>';
			echo ' Verifica i permessi della cartella ' . DATA_DIR . 'files/' . $admin_aziend['codice'] . ' <br/>';
		}
	}
	$mailbox = new ImapMailbox($cpopimap['val'], $cemail['val'], $cpassword['val'], CATTACHMENTS_DIR, 'utf-8');
	$mails = array();
	//se passato checkall verranno riscaricate tutte le email senza tener conto dell'eventule filtro: UNSEEN (solo non lette)
	if (isset($_GET['checkall'])) {
		$cfiltro['val'] = str_replace("UNSEEN","", $cfiltro['val']);
	}
	// Get some mail
	$mailsIds = $mailbox->searchMailBox($cfiltro['val'] );
	if(!$mailsIds) {
		echo('Nessuna nuova email con questo filtro: ' . $cfiltro['val']);
		die("<p align=\"center\"><a href=\"./report_fae_sdi.php\">Ritorna a report Fatture elettroniche</a></p>");
	}
	echo "Attendere: Verifico la posta elettronica sulla casella " . $cemail['val'] ."<br />";
	$n_email = count($mailbox->getMailsInfo($mailsIds));
	echo "N. email: " . $n_email ."<br />";
	$bbb = new IncomingMailAttachment();
	$domDoc = new DOMDocument;
	echo "QUESTO È IL MODULO PEC_SDI: " . "<br/>";
	echo "I file Ricevute vengono salvati in: " .  CATTACHMENTS_DIR . "<br/>";
	$identif_iva_az_lavoro = "IT".$admin_aziend['codfis'] ;
	foreach($mailsIds as $mailId) {
		$mail = $mailbox->getMail($mailId);
		$data_mail =  $mail->date;
		$mittente = substr($mail->fromName,-strlen($cmailSDI['val']),strlen($cmailSDI['val']));
		$aaa= $mail->getAttachments();
		$ccc = array_values($aaa);
		if (count($ccc) == 0) {
			//echo "senza allegato " ;
			// $mailbox->markMailAsUnread($mailId) ;
			continue ;
		}
		$bbb = $ccc[0];
		$nome_file_ret = $bbb->name;
		$nome_info=explode( '_', $nome_file_ret );
		if ($mittente != $cmailSDI['val'] )
		{
			// non proviene da PEC SDI: non la considero elimino gli allegati, segno la mail come non letta  e riprendo il ciclo
			foreach ($ccc as $allegato)  {
				$bbb = $allegato ;
				$nome_file_ret = $bbb->name;
				unlink(CATTACHMENTS_DIR.'/'.$nome_file_ret) ;
			}
			//	    $mailbox->markMailAsUnread($mailId) ;
			continue ;
		}
		if ($nome_info[0] != $identif_iva_az_lavoro) {
			// trattasi di fattura di nostro fornitore: Segnalo la presenza ma la tolgo dalla cartella ricevutesdi e marco la mail come non letta
			// in moda da poterla scaricare a mezzo acquisisci fattura acquisto
			echo "C'è una nuova fattura Acquisto di: " . $nome_info[0] . "   la puoi scaricare da acquisti<br/>";
			foreach($ccc as $allegato) {
				$bbb = $allegato ;
				$nome_file_ret = $bbb->name;
				unlink(CATTACHMENTS_DIR.'/'.$nome_file_ret) ;
			}
			$mailbox->markMailAsUnread($mailId) ;
			continue ; //
		}
		echo "Arrivata Ricevuta: " . $nome_info[2] ." per " . $nome_info[1] . "<br/>";
		$domDoc->load($bbb->filePath);
		$xpath = new DOMXPath($domDoc);
		$result = $xpath->query("//MessageId")->item(0);
		$message_id = $result->textContent;
		$data_ora_ricezione="";
		$errore = "";
		$status="";
		//aggiungere dei controlli
		$nome_info=explode( '_', $nome_file_ret );
		$nome_status = $nome_info[2];
		$progressivo_status = substr($nome_info[3],0,3);
		if ($nome_status == 'MC') {
			$flag="ric" ;
			$status = "MC";
			$result = $xpath->query("//IdentificativoSdI")->item(0);
			$idsidi = $result->textContent;
			$result = $xpath->query("//NomeFile")->item(0);
			$nome_file = $result->textContent;
			$result = $xpath->query("//DataOraRicezione")->item(0);
			$data_ora_ricezione = $result->textContent;
			$result = $xpath->query("//DataOraConsegna")->item(0);
			$data_ora_consegna =$result->textContent;
		} elseif ($nome_status == 'NS') {
			$status = "NS";
			$flag="ric" ;
			$result = $xpath->query("//IdentificativoSdI")->item(0);
			$idsidi = $result->textContent;
			$result = $xpath->query("//NomeFile")->item(0);
			$nome_file = $result->textContent;
			$result = $xpath->query("//DataOraRicezione")->item(0);
			$data_ora_ricezione = $result->textContent;
			$data_ora_consegna =$data_ora_ricezione;
			$result = $xpath->query("//ListaErrori/Errore/Descrizione")->item(0);
			$errore = $result->textContent;
		} elseif ($nome_status == 'RC') {
			$flag="ric" ;
			$status = "RC";
			$result = $xpath->query("//IdentificativoSdI")->item(0);
			$idsidi = $result->textContent;
			$result = $xpath->query("//NomeFile")->item(0);
			$nome_file = $result->textContent;
			$result = $xpath->query("//DataOraRicezione")->item(0);
			$data_ora_ricezione = $result->textContent;
			$result = $xpath->query("//DataOraConsegna")->item(0);
			$data_ora_consegna = $result->textContent;
		}  elseif ($nome_status == 'NE') {
			$flag="ric" ;
			$status = "NE";
			$result = $xpath->query("//IdentificativoSdI")->item(0);
			$idsidi = $result->textContent;
			$result = $xpath->query("//NomeFile")->item(0);
			$nome_file = $result->textContent;
			$result = $xpath->query("//Esito")->item(0);
			$errore = $result->textContent;
			if ($errore == "EC02") {
				$result = $xpath->query("//Descrizione")->item(0);
				$errore = "EC02: " . $result->textContent;
			}
			$data_ora_ricezione =$data_mail;
			$data_ora_consegna =$data_mail;
		}  elseif ($nome_status == 'DT') {
			$status = "DT";
			$flag="ric" ;
			$result = $xpath->query("//IdentificativoSdI")->item(0);
			$idsidi = $result->textContent;
			$result = $xpath->query("//NomeFile")->item(0);
			$nome_file = $result->textContent;
			$result = $xpath->query("//Descrizione")->item(0);
			$errore = $result->textContent;
			$data_ora_ricezione =$data_mail;
			$data_ora_consegna =$data_mail;
		}
		$nome_file_ori =str_replace('.xml.p7m','.xml', $nome_file);
		$verifica = gaz_dbi_get_row($gTables['fae_flux'], 'filename_ori ', $nome_file_ori);
		if ($verifica == false) {
			$id_tes = 0;
			$data_exec = $data_mail ;
		} else {
			$id_flux = $verifica['id'] ;
			$data_exec = $verifica['exec_date'] ;
			$id_tes = $verifica['id_tes_ref'];
		}
		//non dovrebbero esserci ma verifica eventuali doppioni causa errori sulla casella di posta elettronica
		$verifica = gaz_dbi_get_row($gTables['fae_flux'], 'mail_id', $message_id);
		if ($verifica == false || $flag == "acq") {
			$valori=array('filename_ori'=>$nome_file,
			'id_tes_ref'=>$id_tes,
			'exec_date'=>$data_exec,
			'received_date'=>$data_ora_ricezione,
			'delivery_date'=>$data_ora_consegna,
			'filename_son'=>'',
			'id_SDI'=>$idsidi,
			'filename_ret'=>$nome_file_ret,
			'mail_id'=>$message_id,
			'data'=>'',
			'flux_status'=>$status,
			'progr_ret'=>$progressivo_status,
			'flux_descri'=>$errore);
			if ($id_tes == 0 && $flag == "ric") {
				echo " Attenzione ricevuta senza invio, inserisco in fae_flux ". $idsidi . " " . $nome_file . " " . $status . " ". $progressivo_status."<br/>";
				fae_fluxInsert($valori);
			} elseif ($id_tes > 0 && $flag == "ric") {
				// voglio che le ricevute aggiornino lo stesso record dell'invio fattura così da chiudere il ciclo
				echo "Aggiorno fae_flux ". $idsidi . " " . $nome_file . " " . $status . " ". $progressivo_status."<br/>";
				gaz_dbi_put_query($gTables['fae_flux'], "id = '" . $id_flux."'", "received_date", $data_ora_ricezione);
				gaz_dbi_put_query($gTables['fae_flux'], "id = '" . $id_flux."'", "delivery_date", $data_ora_consegna);
				gaz_dbi_put_query($gTables['fae_flux'], "id = '" . $id_flux."'", "id_SDI", $idsidi);
				gaz_dbi_put_query($gTables['fae_flux'], "id = '" . $id_flux."'", "filename_ret", $nome_file_ret);
				gaz_dbi_put_query($gTables['fae_flux'], "id = '" . $id_flux."'", "mail_id", $message_id);
				gaz_dbi_put_query($gTables['fae_flux'], "id = '" . $id_flux."'", "flux_status", $status);
				gaz_dbi_put_query($gTables['fae_flux'], "id = '" . $id_flux."'", "progr_ret", $progressivo_status);
				gaz_dbi_put_query($gTables['fae_flux'], "id = '" . $id_flux."'", "flux_descri", $errore);
			}
			echo  $idsidi . " " . $nome_file . " " . $status . " ". $progressivo_status."<br/>";
		} else {
			echo " presente ". $idsidi . " " . $nome_file . " " . $status . " ". $progressivo_status."<br/>";
		}
	}
	gaz_dbi_put_row($gTables['company_config'],'var','last_fae_email','val',$n_email);
	echo "Completato";
	return null ;
	echo "<p align=\"center\"><a href=\"./report_fae_sdi.php\">Ritorna a report Fatture elettroniche</a></p>";
}

// classe per l'invio delle fatture elettroniche a mezzo pec ARUBA su pec SDI
class C_PecARUBA_SDI {

	function sendMail($admin_data, $user, $content) {
		global $gTables;
		$admin_aziend=checkAdmin();
		require_once "../../config/config/pecfae_config.php";
		require_once "../../library/phpmailer/class.phpmailer.php";
		require_once "../../library/phpmailer/class.smtp.php";

		// definisco il server SMTP e il mittente
		$config_mailer = 'smtp';
		$config_host = "smtps.pec.aruba.it";
		$config_notif = "yes";
		$config_port = 465;
		$config_secure = "ssl";
		$config_user = indirizzo_pec_azienda; // indirizzo pec utilizzato per fatturazione elettronica
		$config_pass = password_pec_azienda; // password pec
		$mailto = indirizzo_pec_SDI; //destinatario indirizzo pec SDI assegnato per trasmissione fattura elelttronica
		//$config_replyTo = gaz_dbi_get_row($gTables['company_config'], 'var', 'reply_to');
		// attingo il contenuto del corpo della email dall'apposito campo della tabella configurazione utente
		$user_text = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'body_send_doc_email', "AND adminid = '{$user['user_name']}'");
		$company_text = gaz_dbi_get_row($gTables['company_config'], 'var', 'company_email_text');
		$admin_data['web_url'] = trim($admin_data['web_url']);

		$subject = $admin_data['ragso1'] . " " . $admin_data['ragso2'] . " - Trasmissione documenti"; //subject
		// aggiungo al corpo  dell'email
		$body_text = "<div><b>" . $admin_data['cliente1']. "</b></div>\n";
		$body_text .= "<div>" . $admin_data['doc_name']. "</div>\n";
		$body_text .= "<div>" . $company_text['val'] . "</div>\n";
		$body_text .= ( empty($admin_data['web_url']) ? "" : "<h4><span style=\"color: #000000;\">Web: <a href=\"" . $admin_data['web_url'] . "\">" . $admin_data['web_url'] . "</a></span></h4>" );
		$body_text .= "<h3><span style=\"color: #000000; background-color: #" . $admin_data['colore'] . ";\">" . $admin_data['ragso1'] . " " . $admin_data['ragso2'] . "</span></h3>";
		$body_text .= "<address><div style=\"color: #" . $admin_data['colore'] . ";\">" . $user['user_firstname'] . " " . $user['user_lastname'] . "</div>\n";
		$body_text .= "<div>" . $user_text['var_value'] . "</div></address>\n";
		$body_text .= "<hr /><small>" . EMAIL_FOOTER . " " . GAZIE_VERSION . "</small>\n";
		//
		// Inizializzo PHPMailer
		//
		$mail = new PHPMailer();
		$mail->Host = $config_host;
		$mail->IsHTML();                                // Modalita' HTML
		$mail->CharSet = 'UTF-8';
		// Imposto il server SMTP
		if (!empty($config_port)) {
			$mail->Port = $config_port;             // Imposto la porta del servizio SMTP
		}
		switch ($config_mailer) {
			case "smtp":
			// Invio tramite protocollo SMTP
			$mail->SMTPDebug = false;                           // Attivo il debug
			$mail->IsSMTP();                                // Modalita' SMTP
			if (!empty($config_secure)) {
				$mail->SMTPSecure = $config_secure; // Invio tramite protocollo criptato
			} else {
				$mail->SMTPOptions = array('ssl' => array('verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true));
			}
			$mail->SMTPAuth = (!empty($config_user) && $config_mailer == 'smtp' ? TRUE : FALSE );
			if ($mail->SMTPAuth) {
				$mail->Username = $config_user;     // Imposto username per autenticazione SMTP
				$mail->Password = $config_pass;     // Imposto password per autenticazione SMTP
			}
			break;
			case "mail":
			default:
			break;
		}
		$mittente = $config_user;
		// Imposto eventuale richiesta di notifica
		if ($config_notif == 'yes') {
			$mail->AddCustomHeader($mail->HeaderLine("Disposition-notification-to", $mittente));
		}
		$mail->setLanguage(strtolower($admin_data['country']));
		// Imposto email del mittente
		$mail->SetFrom($mittente, $admin_data['ragso1'] . " " . $admin_data['ragso2']);
		// Imposto email del destinatario
		$mail->Hostname = $config_host;
		$mail->AddAddress($mailto);
		// Se ho una mail utente lo utilizzo come mittente tra i destinatari in cc
		$mail->AddCC($mittente, $admin_data['ragso1'] . " " . $admin_data['ragso2']);
		// Imposto l'oggetto dell'email
		$mail->Subject = $subject;
		// Imposto il testo HTML dell'email
		$mail->MsgHTML($body_text);
		// Aggiungo la fattura in allegato
		if ($content->urlfile){ // se devo trasmettere un file allegato passo il suo url
			$mail->AddAttachment( $content->urlfile, $content->name );
		} else { // altrimenti metto il contenuto del pdf che presumibilmente mi arriva da document.php
			$mail->AddStringAttachment($content->string, $content->name, $content->encoding, $content->mimeType);
		}
		// Creo una veste grafica
		//require("../../library/include/datlib.inc.php");
		require("../../library/include/header.php");
		$script_transl = HeadMain();
		// Invio...
		if ($mail->Send()) {
			echo "invio e-mail riuscito... <strong>OK</strong><br />mail send has been successful... <strong>OK</strong>"; // or use booleans here
			require("../../library/include/footer.php");
			return true;
		} else {
			echo "<br />invio e-mail <strong style=\"color: #ff0000;\">NON riuscito... ERROR!</strong><br />mail send has<strong style=\"color: #ff0000;\"> NOT been successful... ERROR!</strong> ";
			echo "<br />mailer error: " . $mail->ErrorInfo;
			require("../../library/include/footer.php");
			return false;
		}
	}

}


?>
