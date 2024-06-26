<?php
/*
  --------------------------------------------------------------------------
Copyright (C) - Antonio De Vincentiis Montesilvano (PE) https://www.devincentiis.it - telefono +39 338 31 21 161
Copyright (C) - Antonio Germani Massignano (AP) https://www.lacasettabio.it - telefono +39 340 50 11 912
  --------------------------------------------------------------------------
*/

use Ddeboer\Imap\Server;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


class limsgazSynchro {
  // ATTENZIONE!!! Il nome della classe dev'essere costruita in modo da avere il nome modulo(solo lettere) +  gazSynchro | vedere in get_sync_status_ajax.php che lo chiama, vengono strippati i caratteri diversi dalle lettere
	function tryBase64Decode($s){
		// Check if there are valid base64 characters
		if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s)) {
			// Decode the string in strict mode and check the results
			try {
				$decoded = base64_decode($s, true);
				if ($decoded !== false) {
					// Encode the string again
					if(base64_encode($decoded) == $s) {
						return $decoded;
					} else {
						error_log('Charset non gestito in tryBase64Decode ' . print_r($decoded, true), 0);
						return $decoded;
					}
				}
			} catch (Exception $ex) {
				//$ex->getMessage();
			}
		}

		return $s;
	}


	function der2smime($file){
		$to = <<<TXT
MIME-Version: 1.0
Content-Disposition: attachment; filename="smime.p7m"
Content-Type: application/x-pkcs7-mime; smime-type=signed-data; name="smime.p7m"
Content-Transfer-Encoding: base64
\n
TXT;
		$from = file_get_contents($file);
		$to.= chunk_split(base64_encode($from));
		return file_put_contents($file,$to);
	}

	function extractDER($file){
		$tmp = tempnam(DATA_DIR . 'files/tmp/', 'ricder');
		$txt = tempnam(DATA_DIR . 'files/tmp/', 'rictxt');
		$flags = PKCS7_BINARY|PKCS7_NOVERIFY|PKCS7_NOSIGS;
		openssl_pkcs7_verify($file, $flags, $tmp); // estrazione certificato
		@openssl_pkcs7_verify($file, $flags, '/dev/null', array(), $tmp, $txt); // estrazione contenuto - questo potrebbe fallire se il file non è ASN.1 clean
		unlink($tmp);
		$out = file_get_contents($txt);
		unlink($txt);
		return $out;
	}

	function removeSignature($s){
		$start_xml = strpos($s, '<?xml ');
		if ($start_xml !== FALSE) {
			$s = substr($s, $start_xml);
		} else {
			$start_xml = strpos($s, '<?xml-stylesheet ');
			if ($start_xml !== FALSE) {
				$s = substr($s, $start_xml);
			}
		}
		preg_match_all('/<\/.+?>/', $s, $matches, PREG_OFFSET_CAPTURE);
		$lastMatch = end($matches[0]);
		// trovo l'ultimo carattere del tag di chiusura per eliminare la coda
		$f_end = $lastMatch[1]+strlen($lastMatch[0]);
		$s = substr($s, 0, $f_end);
		// elimino le sequenze di caratteri aggiunti dalla firma (ancora da testare approfonditamente)
		$s = preg_replace('/[\x{0004}]{1}[\x{0082}]{1}[\x{0001}-\x{001F}]{1}[\s\S]{1}/i', '', $s);
		$s = preg_replace('/[\x{0004}]{1}[\x{0082}]{1}[\s\S]{1}[\x{0000}]{1}/i', '', $s);
		$s = preg_replace('/[\x{0004}]{1}[\x{0081}]{1}[\s\S]{1}/i', '', $s);
		$s = preg_replace('/[\x{0004}]{1}[\s\S]{1}/i', '', $s);
		$s = preg_replace('/[\x{0003}]{1}[\s\S]{1}/i', '', $s);
		//$s = preg_replace('/[\x{0004}]{1}[A-Za-z]{1}/i', '', $s); // per eliminare tag finale
		return $s;
	}

	function recoverCorruptedXML($s){
		libxml_use_internal_errors(true);
		$xml = @simplexml_load_string($s);
		$errors = libxml_get_errors();
		if (!empty($errors) && is_array($errors) && count($errors)>0) {
			$lines = explode("\n", $s);
			foreach ($errors as $error) {
				if (strpos($error->message, 'Opening and ending tag mismatch')!==false) {
					$tag   = trim(preg_replace('/Opening and ending tag mismatch: (.*) line.*/', '$1', $error->message));
					$line  = $error->line-1;
					$lines[$line] = substr($lines[$line], 0, strpos($lines[$line], '</')).'</'.$tag.'>';
				}
			}
			libxml_clear_errors();
			return implode("\n", $lines);
		} else {
			return $s;
		}
	}

	
	function __construct() {
		$this->api_token=TRUE; //Joomla non ha bisogno di TOKEN, quindi è TRUE
	}

	function get_sync_status($last_day=false) { // in last_id passerò la data dell'ultimo flusso di fatture ricevute/trasmesse e controllerò tutte le email da quella data in avanti
		global $gTables, $admin_aziend;
		$codice_aziends = gaz_dbi_dyn_query("codice", $gTables['aziend'], "pariva = ".$admin_aziend['pariva'] , "codice ASC");
		@session_start();$rawres=[];
		$host = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_imap_server')['val'];
		$port = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_imap_port')['val'];
		$secure = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_imap_secure')['val'];
		$usr = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_imap_usr')['val'];
		$psw = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_imap_psw')['val'];
		$folder = gaz_dbi_get_row($gTables['company_config'], 'var', 'lims_inbox_folder')['val'];
		$folder = (strlen($folder) > 2)?$folder:'INBOX';
		$server = new Server(
        $host, // required
        $port,     // defaults to '993'
        '/imap/'.$secure.'/validate-cert',    // defaults to '/imap/ssl/validate-cert'
        array('/readonly')
    );
    $connection = $server->authenticate($usr, $psw);
    $mailbox = $connection->getMailbox($folder);
    $rs_last_flux = gaz_dbi_query("SELECT received_date FROM ".$gTables['fae_flux']." WHERE `flux_status` NOT LIKE '#%' ORDER BY exec_date DESC LIMIT 1");
    $last_flux = gaz_dbi_fetch_array($rs_last_flux);
    if (isset($last_day) && strtotime($last_day)>strtotime($last_flux['received_date'])){ // se ho passato il giorno
        $last_flux = substr($last_day,0,10);
        $interval = 'P01D';
    } elseif ($last_flux && intval(substr($last_flux['received_date'],0,1)) >= 1 ){ // controllo anche il giorno precedente l'ultimo
        $last_flux = substr($last_flux['received_date'],0,10);
        $interval = 'P01D';
    } else { // non ho flussi controllo gli ultimi 3 mesi
        $last_flux = null;
        $interval = 'P93D';
    }
		$rs_last_file = gaz_dbi_query("SELECT last_modified FROM ".$gTables['files']." WHERE `item_ref` = 'faesync' ORDER BY last_modified DESC LIMIT 1");
    $last_file = gaz_dbi_fetch_array($rs_last_file);
		$last_modified = (isset($last_file['last_modified']))?substr($last_file['last_modified'],0,10):$last_flux;
		if (strtotime($last_modified) < strtotime($last_flux)){
			$last_date=$last_modified;
		} else{
			$last_date=$last_flux;
		}
		if($last_date==0){
			$last_date="2000-01-01";
		}
    $lastday = new DateTimeImmutable($last_date); // dovrò passare DateTimeImmutable('AAAA-MM-GG'); con la data dell'ultimo flusso proveniente dal SdI presente in gaz_NNNfae_flux, ovvero quelli con flux_status NOT LIKE '#%'
    $daysAgo = $lastday->sub(new DateInterval($interval));
    $messages = $mailbox->getMessages(
        new Ddeboer\Imap\Search\Date\Since($daysAgo),
        \SORTDATE, // Sort criteria
        false // order
    );
    // controllo il contenuto della cartella della casella della PEC
    $mt = 0; // ricevuta fattura d'acquisto
    $in = 0; // conferma invio fattura di vendita
    $rc = 0; // ricevuta consegna fattura di vendita
    $ns = 0; // notifica di scarto fattura di vendita
    $dt = 0; // decorrenza termi fattura di vendita PA
    $mc = 0; // mancata consegna fattura di vendita
    $ot = 0; // altra notifica non catalogata
    $labels=[];
    $domDoc = new DOMDocument;
    foreach ($messages as $message) {
      $attachments = $message->getAttachments();
      foreach ($attachments as $attachment) {
        $fn = $attachment->getFilename();
        if (preg_match("/^[A-Z]{2}([0-9A-Z]+)_[a-zA-Z0-9]{5}[_]{0,1}([A-Z]{0,2})[_]{0,1}[0-9]{0,3}(.xml|.xml.p7m)$/i", $fn, $regs)) { // se il nome file è di interesse lo considero
          $label = 'Vai sulla lista dei flussi SdI';
          $link = '../vendit/report_fae_sdi.php';
          if ($regs[2]=='RC'){ // Ricevuta di Consegna
            $domDoc->loadXML($attachment->getDecodedContent());
            $IdentificativoSdI = $domDoc->getElementsByTagName('IdentificativoSdI')[0]->nodeValue;
            $NomeFile = $domDoc->getElementsByTagName('NomeFile')[0]->nodeValue;
            $DataOraRicezione = str_replace('T', ' ',substr($domDoc->getElementsByTagName('DataOraRicezione')[0]->nodeValue,0,19));
            $DataOraConsegna = str_replace('T', ' ',substr($domDoc->getElementsByTagName('DataOraConsegna')[0]->nodeValue,0,19));
            // cambio lo stato in CONSEGNATO solo se proveniente da uno stato precedente
            $samefile = gaz_dbi_get_row($gTables['fae_flux'], 'filename_ori', $NomeFile, " AND ( flux_status = 'PC' OR flux_status = 'IN' OR flux_status = 'DI' OR flux_status LIKE '@%' OR flux_status LIKE '#%' )");
            if ($samefile){
              $rc++;
              $labels['RC'] = $rc.' Ricevute di consegna di fatture di vendita ';
              gaz_dbi_query("UPDATE ".$gTables['fae_flux']." SET
              `flux_status` = '".$regs[2]."',
              `id_SDI` = '".$IdentificativoSdI."',
              `received_date` = '".$DataOraRicezione."',
              `exec_date` = '".$DataOraConsegna."',
              `delivery_date` = '".$DataOraConsegna."'
              WHERE `filename_ori` = '".$NomeFile."'");
            }
          } elseif ($regs[2]=='NS'){ // Notifica di Scarto
            $domDoc->loadXML($attachment->getDecodedContent());
            $IdentificativoSdI = $domDoc->getElementsByTagName('IdentificativoSdI')[0]->nodeValue;
            $Errori = $domDoc->getElementsByTagName('ListaErrori');
            $XMLErrori = $domDoc->saveHTML($Errori[0]);
            $NomeFile = $domDoc->getElementsByTagName('NomeFile')[0]->nodeValue;
            $DataOraRicezione = str_replace('T', ' ',substr($domDoc->getElementsByTagName('DataOraRicezione')[0]->nodeValue,0,19));
            // cambio lo stato in  SCARTATO solo se proveniente da uno stato precedente
            $samefile = gaz_dbi_get_row($gTables['fae_flux'], 'filename_ori', $NomeFile, " AND ( flux_status = 'PC' OR flux_status = 'IN' OR flux_status = 'DI' OR flux_status LIKE '@%' OR flux_status LIKE '#%' )");
            if ($samefile){
              $ns++;
              $labels['NS'] = $ns.' Notifiche di scarto di fatture di vendita';
              gaz_dbi_query("UPDATE ".$gTables['fae_flux']." SET
              `flux_status` = '".$regs[2]."',
              `id_SDI` = '".$IdentificativoSdI."',
              `received_date` = '".$DataOraRicezione."',
              `exec_date` = '".$DataOraRicezione."',
              `flux_descri` = '".addslashes(preg_replace('/\s\s+/', ' ', $XMLErrori))."'
              WHERE `filename_ori` = '".$NomeFile."'");
            }
          } elseif ($regs[2]=='MC'){ // Mancata Consegna
            $domDoc->loadXML($attachment->getDecodedContent());
            $IdentificativoSdI = $domDoc->getElementsByTagName('IdentificativoSdI')[0]->nodeValue;
            $NomeFile = $domDoc->getElementsByTagName('NomeFile')[0]->nodeValue;
            $DataOraRicezione = str_replace('T', ' ',substr($domDoc->getElementsByTagName('DataOraRicezione')[0]->nodeValue,0,19));
            // cambio lo stato in  SCARTATO solo se proveniente da uno stato precedente
            $samefile = gaz_dbi_get_row($gTables['fae_flux'], 'filename_ori', $NomeFile, " AND ( flux_status = 'PC' OR flux_status = 'IN' OR flux_status = 'DI' OR flux_status LIKE '@%' OR flux_status LIKE '#%' )");
            if ($samefile){
              $mc++;
              $labels['MC'] = $mc.' Mancate consegne di fatture di vendita';
              gaz_dbi_query("UPDATE ".$gTables['fae_flux']." SET
              `flux_status` = '".$regs[2]."',
              `id_SDI` = '".$IdentificativoSdI."',
              `received_date` = '".$DataOraRicezione."',
              `exec_date` = '".$DataOraRicezione."'
              WHERE `filename_ori` = '".$NomeFile."'");
            }
          } elseif ($regs[2]=='DT'){ // Decorrenza Termini
            $domDoc->loadXML($attachment->getDecodedContent());
            $IdentificativoSdI = $domDoc->getElementsByTagName('IdentificativoSdI')[0]->nodeValue;
            $NomeFile = $domDoc->getElementsByTagName('NomeFile')[0]->nodeValue;
            $DataOraRicezione = str_replace('T', ' ',substr($domDoc->getElementsByTagName('DataOraRicezione')[0]->nodeValue,0,19));
            // cambio lo stato in  SCARTATO solo se proveniente da uno stato precedente
            $samefile = gaz_dbi_get_row($gTables['fae_flux'], 'filename_ori', $NomeFile, " AND ( flux_status = 'PC' OR flux_status = 'IN' OR flux_status = 'DI' OR flux_status LIKE '@%' OR flux_status LIKE '#%' )");
            if ($samefile){
              $dt++;
              $labels['DT'] = $dt.' Mancate consegne di fatture di vendita';
              gaz_dbi_query("UPDATE ".$gTables['fae_flux']." SET
              `flux_status` = '".$regs[2]."',
              `id_SDI` = '".$IdentificativoSdI."',
              `received_date` = '".$DataOraRicezione."',
              `exec_date` = '".$DataOraRicezione."'
              WHERE `filename_ori` = '".$NomeFile."'");
            }
          } elseif ($regs[2]=='' && ( $regs[1] == $admin_aziend['pariva'] || $regs[1] == $admin_aziend['codfis'] )){ // Conferma fattura inviata
            // cambio lo stato in presa consegna solo se proveniente da inviata
            $samefile = gaz_dbi_get_row($gTables['fae_flux'], 'filename_ori', $regs[0], " AND ( flux_status = 'DI' OR flux_status = 'IN' OR flux_status = '' OR flux_status LIKE '@%' OR flux_status LIKE '#%' ) ");
            if ($samefile) {
                $in++;
                $labels['IN'] = $in.' Prese in consegna di fatture di vendita';
                gaz_dbi_query("UPDATE ".$gTables['fae_flux']." SET `received_date` = NOW(), `exec_date` = NOW(), `flux_status` = 'PC' WHERE `filename_ori` = '".$regs[0]."'");
            }
          } elseif ($regs[2]=='MT') { // Allegato insieme alla Fattura ricevuta con i dati SdI
            $domDoc->loadXML($attachment->getDecodedContent());
            file_put_contents(DATA_DIR."files/".$admin_aziend['company_id'].'/doc/'.$attachment->getFilename(),$attachment->getDecodedContent());
          } elseif ($regs[2]=='') { // Fattura ricevuta assieme a MT (sopra)
            $tipdoc_conv=array('TD01'=>'AFA','TD02'=>'AFA','TD03'=>'AFA','TD04'=>'AFC','TD05'=>'AFD','TD06'=>'AFA','TD08'=>'AFC','TD24'=>'AFT','TD25'=>'AFT','TD27'=>'AFA');
            // controllo se è caricabile
            $f_ex=TRUE;
			
			// INIZIO pulizia file xml o p7m
			$p7mContent = $attachment->getDecodedContent();
			$p7mContent = $this->tryBase64Decode($p7mContent);
			$tmpfatt = tempnam(DATA_DIR . 'files/tmp/', 'ricfat');
			file_put_contents($tmpfatt, $p7mContent);
			if (FALSE !== $this->der2smime($tmpfatt)) {
				$cert = tempnam(DATA_DIR . 'files/tmp/', 'ricpem');
				$retn = openssl_pkcs7_verify($tmpfatt, PKCS7_NOVERIFY, $cert);
				unlink($cert);
				if (!$retn) {
					//unlink($tmpfatt);
					//echo "Error verifying PKCS#7 signature in {$file_name}";
					error_log('errore in Verifica firma PKCS#7', 0);
					//echo 'errore in Verifica firma PKCS#7';
					//return false;
				}
				$isFatturaElettronicaSemplificata = false;
				$fatt = $this->extractDER($tmpfatt);
				if (empty($fatt)) {
					$test = @base64_decode(file_get_contents($tmpfatt));
					// Salto lo header (INDISPENSABILE perché la regexp funzioni sempre)
					if (strpos($test, 'FatturaElettronicaSemplificata') !== FALSE) {
						$isFatturaElettronicaSemplificata = true;
						if (preg_match('#(<[^>]*FatturaElettronicaSemplificata.*</[^>]*FatturaElettronicaSemplificata>)#', substr($test, 54), $gregs)) {
							$fatt = '<'.'?'.'xml version="1.0"'.'?'.'>' . $gregs[1]; // RECUPERO INTESTAZIONE XML
						}
					} else {
						if (preg_match('#(<[^>]*FatturaElettronica.*</[^>]*FatturaElettronica>)#', substr($test, 54), $gregs)) {
							$fatt = '<'.'?'.'xml version="1.0"'.'?'.'>' . $gregs[1]; // RECUPERO INTESTAZIONE XML
						}
					}
				} else {
					if (strpos($p7mContent, 'FatturaElettronicaSemplificata') !== FALSE) {
						$isFatturaElettronicaSemplificata = true;
					}
				}
			}
			unlink($tmpfatt);
			if (!empty($fatt)) {
				$invoiceContent = $fatt;
			} else {
				$invoiceContent = $this->removeSignature($p7mContent);
			}			
			// fine pulizia
            $domDoc->loadXML($invoiceContent);
            $domxpath = new DOMXPath($domDoc);
            $tipdoc=$domDoc->getElementsByTagname('TipoDocumento')[0]->nodeValue;
            if ($tipdoc=="TD07" OR $tipdoc=="TD08"){
              $isFatturaElettronicaSemplificata = TRUE;
            }else{
              $isFatturaElettronicaSemplificata = FALSE;
            }
            // Trasformo il tipdoc con i codici di GAzie
            $tipdoc=$tipdoc_conv[$domxpath->query("//FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/TipoDocumento")->item(0)->nodeValue];
            $datdoc=$domDoc->getElementsByTagname('Data')[0]->nodeValue;
            $numdoc=$domDoc->getElementsByTagname('Numero')[0]->nodeValue;
            if ($isFatturaElettronicaSemplificata) {
              $codiva=$domxpath->query("//FatturaElettronicaHeader/CedentePrestatore/IdFiscaleIVA/IdCodice")->item(0)->nodeValue;
              if ($domxpath->query("//FatturaElettronicaHeader/CedentePrestatore/CodiceFiscale")->length>=1){
                $codfis=$domxpath->query("//FatturaElettronicaHeader/CedentePrestatore/CodiceFiscale")->item(0)->nodeValue;
              } else {
                $codfis=$codiva;
              }
            } else {
              $codiva=$domxpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue;
              if ($domxpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->length>=1){
                $codfis=$domxpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0)->nodeValue;
              } else {
                $codfis=$codiva;
              }
            }
            foreach($codice_aziends as $codice){// controllo in tutte le aziende
              $r_invoice=gaz_dbi_dyn_query("*", "gaz_".sprintf('%03d',$codice['codice'])."tesdoc". " LEFT JOIN " . "gaz_".sprintf('%03d',$codice['codice'])."clfoco" . " ON " . "gaz_".sprintf('%03d',$codice['codice'])."tesdoc" . ".clfoco = " . "gaz_".sprintf('%03d',$codice['codice'])."clfoco" . ".codice LEFT JOIN " . $gTables['anagra'] . " ON " . "gaz_".sprintf('%03d',$codice['codice'])."clfoco" . ".id_anagra = " . $gTables['anagra'] . ".id", "tipdoc='".$tipdoc."' AND (pariva = '".$codiva."' OR codfis = '".$codfis."') AND datfat='".$datdoc."' AND numfat='".$numdoc."'", "id_tes", 0, 1);
              $exist_invoice=gaz_dbi_fetch_array($r_invoice);
              if ($exist_invoice) { // esiste un file che pur avendo un nome diverso è già stato acquisito ed ha lo stesso numero e data
                $msg['err'][] = 'same_content';
                $f_ex=false; // non è caricabile
                break;
              }
            }
            // FINE CONTROLLO NUMERO DATA
            if ($domDoc->getElementsByTagName("FatturaElettronicaHeader")->length < 1) { // non esiste il nodo <FatturaElettronicaHeader>
              $msg['err'][] = 'invalid_fae';
              $f_ex=false; // non è caricabile
            } else if ( ( !$isFatturaElettronicaSemplificata && @$domxpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue <> $admin_aziend['pariva'] && @$domxpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/CodiceFiscale")->item(0)->nodeValue <> $admin_aziend['codfis'] ) ||
              ( $isFatturaElettronicaSemplificata && @$domxpath->query("//FatturaElettronicaHeader/CessionarioCommittente/IdentificativiFiscali/IdFiscaleIVA/IdCodice")->item(0)->nodeValue <> $admin_aziend['pariva'] && @$domxpath->query("//FatturaElettronicaHeader/CessionarioCommittente/IdentificativiFiscali/CodiceFiscale")->item(0)->nodeValue <> $admin_aziend['codfis'] ) ) { // ne partita IVA ne codice fiscale coincidono con quella della azienda che sta acquisendo la fattura
              $msg['err'][] = 'not_mine';
              $f_ex=false; // non la visualizzo perché non è una mia fattura
            }
            if ($f_ex) {
              //echo "posso caricare ma devo controllare se non è stato già fatto";
              $check_existence = gaz_dbi_get_row($gTables['files'], 'title', $attachment->getFilename());
              if (!$check_existence){
                $file=pathinfo($attachment->getFilename());
                $file_id=gaz_dbi_table_insert('files', array("item_ref"=>"faesync","extension"=>$file['extension'],"title"=>$attachment->getFilename()));
                $check=file_put_contents(DATA_DIR."files/".$admin_aziend['company_id'].'/doc/'.$file_id.'.'.$file['extension'],$attachment->getDecodedContent());
                $mt++;
                $labels['MT'] = $mt.' fatture di acquisto ricevute da caricare';
                $label='Vai all\'acquisizione';
                $link = '../acquis/acquire_invoice.php';
                if ($check === FALSE){ // se è FALSE c\'è stato un errore in scrittura file
                $rawres['title'] = "E\' arrivata una fattura di acquisto:<br/>". $attachment->getFilename()."<br>ma non è stato possibile caricarla";
                $rawres['button'] = "ERRORE";
                $rawres['label'] = 'ERRORE di scrittura del file nella cartella /data/files/'.$admin_aziend['company_id'].'/doc/';
                $rawres['link'] = '';
                $rawres['style'] = 'danger';

                $_SESSION['menu_alerts']['lims']=$rawres;
                $this->rawres=$rawres;
                return;
                }
              }
            }else{
              //echo "NON posso caricare";
            }
          } else { // Non ancora catalogata
          }
        }
      }
    }
    $nnewpec= round($mt+$in+$rc+$ns+$dt+$mc+$ot);
    if ($nnewpec>=1) {
        $rawres['title'] = "Sono presenti le seguenti notifiche dello SdI:<br/>".implode(",<br/> ", $labels);
        $rawres['button'] =$nnewpec." nuov".(($nnewpec==1)?'a':'e')." SdI PEC";
        $rawres['label'] = $label;
        $rawres['link'] =  $link;
        $rawres['style'] = 'warning';
    }
    $_SESSION['menu_alerts']['lims']=$rawres;
    $this->rawres=$rawres;
	}

// mi servono solo per evitare gli errori quando vengono (sempre) chiamate dagli inserimenti/modifiche articoli di magazzino
  function SetProductQuantity($none=false) {
	}
	function SetupStore($none=false) {
	}
	function UpsertCategory($none=false) {
	}
	function UpsertParent($none=false) {
    }
	function UpsertProduct($none=false) {
	}

	function SendFaE($data=false) {
 		global $gTables, $admin_aziend;
    // inizio recupero allegato
    $zip=false;
    if (isset($data['id_tes'])) {   //se viene richiesta la stampa di un solo documento attraverso il suo id_tes
      $id_testata = intval($data['id_tes']);
      $testata = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', $id_testata);
      $where="tipdoc = '". $testata['tipdoc'] ."' AND seziva = ".$testata['seziva']." AND YEAR(datfat) = ".substr($testata['datfat'],0,4)." AND protoc = ".$testata['protoc'];
      if ($testata['tipdoc']=='VCO'){ // in caso di fattura allegata a scontrino mi baso solo sull'id_tes
        $where="id_tes = ".$id_testata;
      }
    } else { // in tutti gli altri casi devo passare i valori su $data
      if (!isset($data['protoc']) || !isset($data['year']) || !isset($data['seziva'])) {
        return 'Non sono stati passati i paramentri SendFae';
      } else {
        $where="tipdoc LIKE 'F__' AND seziva = ".intval($data['seziva'])." AND YEAR(datfat) = ".intval($data['year'])." AND protoc = ".intval($data['protoc']);
      }
	  $testate = gaz_dbi_dyn_query("*", $gTables['tesdoc'],$where,'datemi ASC, numdoc ASC, id_tes ASC');
	  $tesdoc = gaz_dbi_fetch_array($testate);
    }
    
    if (strpos($tesdoc['fattura_elettronica_zip_package'],'zip')>10){ // il file fa parte di un pacchetto, lo invio insieme
      $invoice_data['nome_file']=$tesdoc['fattura_elettronica_zip_package'];
      $file_url = DATA_DIR."files/".$admin_aziend['codice']."/".substr($invoice_data['nome_file'],0,37);
      $invoice_data['documento']=file_get_contents($file_url);
      $zip=true;
    } else { // è un file singolo
      $testate = gaz_dbi_dyn_query("*", $gTables['tesdoc'],$where,'datemi ASC, numdoc ASC, id_tes ASC');
      $invoice_data=create_XML_invoice($testate,$gTables,'rigdoc',false,false,true);
    }
    // fine recupero allegato
    $host = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_server')['val'];
    $port = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_port')['val'];
    $secure = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_secure')['val'];
    $usr = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_usr')['val'];
    $psw = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_psw')['val'];
    $companypec =gaz_dbi_get_row($gTables['company_config'], 'var', 'lims_address_for_fae')['val'];
    $sdiaddress =gaz_dbi_get_row($gTables['company_config'], 'var', 'lims_sdi_email')['val'];
    // indirizzo consulente se presente
    $dest_fae_copy = gaz_dbi_get_row($gTables['company_config'], 'var','dest_fae_zip_package')['val'];
    if (trim($companypec)==''){
      $companypec=$admin_aziend['pec'];
    }
    $mail = new PHPMailer(true);
    try {
      //Server settings
      $mail->SMTPDebug  = 0;                           //Enable verbose debug output default: SMTP::DEBUG_SERVER;
      $mail->isSMTP();                                 //Send using SMTP
      $mail->Host       = $host;                       //Set the SMTP server to send through
      $mail->SMTPAuth   = true;                        //Enable SMTP authentication
      $mail->Username   = $usr;                        //SMTP username
      $mail->Password   = $psw;                        //SMTP password
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //Enable implicit TLS encryption
      $mail->Port       = $port;                       //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
      //Recipients
      $mail->setFrom($companypec);
      $mail->addAddress($sdiaddress);                  //Add a recipient
      if ( strpos($dest_fae_copy,'@') > 4 ) { // ho un indirizzo al quale mandare in copia
        $mail->addAddress($dest_fae_copy);             //Add a recipient
      }
      //Attachments
      $mail->addStringAttachment($invoice_data['documento'],$invoice_data['nome_file']);
      //Content
      $mail->Subject = 'Invio fattura/e elettronica/e';
      $mail->Body    = 'Invio fattura/e elettronica/e '.($zip?'pacchetto':'file').': '.$invoice_data['nome_file'];
      $mail->send();
      // inizio aggiorno lo stato del flusso (fae_flux) a secondo che sia un invio singolo o a pacchetto zip
      if ($zip) { // è uno zip
				gaz_dbi_put_query($gTables['fae_flux'], "filename_zip_package = '" .$invoice_data['nome_file']."'", "flux_status", "@");
      } else { // è un file singolo faccio l'upsert
        $ex=gaz_dbi_get_row ( $gTables['fae_flux'], 'filename_ori', $invoice_data['nome_file'] );
        if ($ex) {
          gaz_dbi_put_query($gTables['fae_flux'], "filename_ori = '" . $invoice_data['nome_file']."'", "flux_status", "@");
        } else {
          // ricavo di nuovo la prima testata mi serve per valorizzare id_tes_ref in fae_flux
          $testate = gaz_dbi_dyn_query("*", $gTables['tesdoc'],$where,'datemi ASC, numdoc ASC, id_tes ASC');
          $tesdoc = gaz_dbi_fetch_array($testate);
          gaz_dbi_table_insert('fae_flux',['filename_ori'=>$invoice_data['nome_file'], 'id_tes_ref'=>$tesdoc['id_tes'],'exec_date'=>date("Y-m-d H:i:s"),'received_date'=>'0000-00-00 00:00:00','delivery_date'=>'0000-00-00 00:00:00','mail_id'=>0,'flux_status'=>'@','n_invio'=>1]);
        }
      }
      return true;
    } catch (Exception $e) {
      return '$where contiene: '.$where." Mailer Error: {$mail->ErrorInfo}";
    }
	}
}
