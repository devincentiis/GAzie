<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  SHOP SYNCHRONIZE è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-present - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
   ------------------------------------------------------------------------
  FUNZIONI di sincronizzazione via FTP e-commerce <-> GAzie
  ------------------------------------------------------------------------
  @Author    Antonio Germani 340-5011912
  ------------------------------------------------------------------------
 */

/*
QUESTA CLASSE CONTERRA' DELLE FUNZIONI DI NOME STANDARD PER INTERAGIRE CON LE API DEI VARI E-COMMERCE
SOTTO VEDETE UNA SOLA FUNZIONE DI COSTRUTTO DI ESEMPIO PER LA PRESA DEL TOKEN.
GAzie userà dei nomi di funzione per eseguire le varie operazioni di sincronizzazione, con il proseguire
dello sviluppo vedrete delle chiamate ad esse che però al momento saranno vuote e a discrezione dei
singoli sviluppatori utilizzarle per passare O ricevere dati (d)allo store online, tramite le specifiche API.
I nomi standard di funzione saranno:
"UpsertProduct","get_sync_status","UpsertCategory","UpsertCustomer","UpdateStore",ecc
e dovranno essere gli stessi anche su eventuali "moduli cloni" per la sincronizzazione di GAzie.
Con questo stratagemma basterà indicare in configurazione azienda  il nome del modulo che si vuole
utilizzare per il sincronismo che tutti gli altri moduli di GAzie nel momento in cui effettueranno
un aggiornamento dei dati punteranno alle funzioni contenute nel modulo alternativo richiesto,
 pittosto che a questo.
*/

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;

class shopsynchronizegazSynchro {
  public $rawres =[];
  public $api_token = TRUE;

	function __construct() {
		// Quando istanzio questa classe prendo il token, sempre.
		// Se $this->api_token ritorna FALSE vuol dire che le credenziali sono sbagliate
		/* token opencart
		global $gTables,$admin_aziend;
		$this->oc_api_url = gaz_dbi_get_row($gTables['company_data'], 'var','oc_api_url')['data'];
		$oc_api_username = gaz_dbi_get_row($gTables['company_data'], 'var','oc_api_username')['data'];
		$oc_api_key = gaz_dbi_get_row($gTables['company_data'], 'var','oc_api_key')['data'];
		// prendo il token
		$curl = curl_init($this->oc_api_url);
		$post = array('username' => $oc_api_username,'key'=>$oc_api_key);
		curl_setopt_array($curl,array(CURLOPT_RETURNTRANSFER=>TRUE,CURLOPT_POSTFIELDS=>$post));
		$raw_response = curl_exec($curl);
		if(!$raw_response){
			$this->api_token=false;
		}else{
			$res = json_decode($raw_response);
			$this->api_token=$res->api_token;
			curl_close($curl);
		}*/
		$this->api_token=TRUE; //la sincronizzazione via FTP non ha bisogno di TOKEN, quindi è TRUE
	}

	function SetupStore() {
		// aggiorno i dati comuni a tutto lo store: Anagrafica Azienda, Aliquote IVA, dati richiesti ai nuovi clienti (CF,PI,indirizzo,ecc) in custom_field e tutto ciò che necessita per evitare di digitarlo a mano su ecommerce-admin
	}

  function UpsertFeedback($feedback,$toDo,$ref="") {// aggiorno i dati feedback
		// $feedback è un array e mi permette di inviare al sito web qualunque cosa

    @session_start();
			global $gTables,$admin_aziend;
			$rawres=[];

			$ftp_host = gaz_dbi_get_row($gTables['company_config'], "var", "server")['val'];
			$ftp_path_upload = gaz_dbi_get_row($gTables['company_config'], "var", "ftp_path")['val'];
			$ftp_user = gaz_dbi_get_row($gTables['company_config'], "var", "user")['val'];
			$OSftp_pass = gaz_dbi_get_row($gTables['company_config'], "var", "pass")['val'];// vecchio sistema di password non criptata
			$OSaccpass = gaz_dbi_get_row($gTables['company_config'], "var", "accpass")['val'];// vecchio sistema di password non criptata
			$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'pass'");
			$rdec=gaz_dbi_fetch_row($rsdec);
			$ftp_pass=$rdec[0]?htmlspecialchars_decode($rdec[0]):'';
			$ftp_pass=(strlen($ftp_pass)>0)?$ftp_pass:$OSftp_pass; // se la password decriptata non ha dato risultati provo a vedere se c'è ancora una password non criptata
			if ($OSaccpass && $urlinterf = gaz_dbi_get_row($gTables['company_config'], 'var', 'path')['val']."upd-feedback.php"){// se sono state impostate
				$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'accpass'");
				$rdec=gaz_dbi_fetch_row($rsdec);
				$accpass=$rdec[0]?htmlspecialchars_decode($rdec[0]):'';
				$accpass=(strlen($accpass)>0)?$accpass:$OSaccpass; // se la password decriptata non ha dato risultati provo a mettere la password non criptata
			}else{
				$rawres['title'] = "Problemi con le impostazioni FTP: manca il percorso al file interfaccia e/o la sua password di accesso! AGGIORNARE MANUALMENTE il feedback di ". $ref. " nel sito web";
				$rawres['button'] = 'Avviso eCommerce';
				$rawres['label'] = "OK fammi controllare le impostazioni";
				$rawres['link'] = '../shop-synchronize/config_sync.php';
				$rawres['style'] = 'danger';
				$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
				$this->rawres=$rawres;
				return;
			}

			if (gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')['val']=="SI"){// SFTP login with private key and password
				$ftp_port = gaz_dbi_get_row($gTables['company_config'], "var", "port")['val'];
				$ftp_key = gaz_dbi_get_row($gTables['company_config'], "var", "chiave")['val'];
				if (gaz_dbi_get_row($gTables['company_config'], "var", "keypass")['val']=="key"){ // SFTP log-in con KEY
					$key = PublicKeyLoader::load(file_get_contents('../../data/files/'.$admin_aziend['codice'].'/secret_key/'. $ftp_key .''),$ftp_pass);
					$sftp = new SFTP($ftp_host, $ftp_port);
					if (!$sftp->login($ftp_user, $key)) {
						// non si connette: key LOG-IN FALSE
						$rawres['title'] = "Problemi con la connessione Sftp usando il file chiave. AGGIORNARE MANUALMENTE il feedback di ". $ref. " nel sito web";
						$rawres['button'] = 'Avviso eCommerce';
						$rawres['label'] = "OK fammi controllare le impostazioni";
						$rawres['link'] = '../shop-synchronize/config_sync.php';
						$rawres['style'] = 'danger';
						$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
						$this->rawres=$rawres;
						return;
					}
				} else { // SFTP log-in con password
					$sftp = new SFTP($ftp_host, $ftp_port);
					if (!$sftp->login($ftp_user, $ftp_pass)) {
						// non si connette: password LOG-IN FALSE
						$rawres['title'] = "Problemi con la connessione Sftp usando la password. AGGIORNARE MANUALMENTE il feedback di ". $ref. " nel sito web";
						$rawres['button'] = 'Avviso eCommerce';
						$rawres['label'] = "OK fammi controllare le impostazioni";
						$rawres['link'] = '../shop-synchronize/config_sync.php';
						$rawres['style'] = 'danger';
						$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
						$this->rawres=$rawres;
						return;
					}
				}
			} else {
				// imposto la connessione al server
				if ($conn_id = @ftp_connect($ftp_host)){
				  // effettuo login con user e pass
				  $mylogin = ftp_login($conn_id, $ftp_user, $ftp_pass);
				  // controllo se la connessione è OK...
				  if ((!$conn_id) or (!$mylogin)){
					// non si connette FALSE
					$rawres['title'] = "Problemi con le impostazioni FTP in configurazione avanzata azienda: nome utente e/o password. AGGIORNARE MANUALMENTE il feedback di ". $ref. " nel sito web. - password inserita:".$ftp_pass."...-old:".$OSftp_pass;
					$rawres['button'] = 'Avviso eCommerce';
					$rawres['label'] = "OK fammi controllare le impostazioni";
					$rawres['link'] = '../config/admin_aziend.php';
					$rawres['style'] = 'danger';
					$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
					$this->rawres=$rawres;
					return;
				  }

				}else{
				  $rawres['title'] = "Problemi con le impostazioni FTP: Non riesco a connettermi con l'host! AGGIORNARE MANUALMENTE il feedback di ". $ref. " nel sito web";
				  $rawres['button'] = 'Avviso eCommerce';
				  $rawres['label'] = "OK fammi controllare le impostazioni";
				  $rawres['link'] = '../config/admin_aziend.php';
				  $rawres['style'] = 'danger';
				  $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
				  $this->rawres=$rawres;
				  return;
				}
			}

		  $house_row = gaz_dbi_get_row($gTables['artico'], 'codice', $ref);
		  $ref_ecommerce_id_product = $house_row['ref_ecommerce_id_product'];
		  if (strlen($ref_ecommerce_id_product)>0){// se esiste un riferimento del sito web
			// convert array to xml
			//function to convert array to xml
			function array_to_xml($array, &$xml) {
			  foreach($array as $key => $value) {
				if(is_array($value)) {
				  if(!is_numeric($key)){
					  $subnode = $xml->addChild("$key");
					  array_to_xml($value, $subnode);
				  }else{
					  $subnode = $xml->addChild("item");
					  array_to_xml($value, $subnode);
				  }
				}else {
				  $xml->addChild("$key",htmlspecialchars("$value"));
				}
			  }
			}

        //creating object of SimpleXMLElement
        $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><GAzieDocuments AppVersion=\"1\" Creator=\"Antonio Germani 2023\" CreatorUrl=\"https://www.programmisitiweb.lacasettabio.it\"></GAzieDocuments>");
		$parent = $xml->addChild("feedback");
        //function call to convert array to xml
        array_to_xml($feedback,$parent);
        // aggiungo il todo
        $parent->addChild("toDo",htmlspecialchars("$toDo"));
        // aggiungo il ref
        $parent->addChild("ref",htmlspecialchars("$ref_ecommerce_id_product"));
        //saving generated xml file
        $xmlFile='feedback.xml';
        $xml_file = $xml->asXML('feedback.xml');
        //success and error message based on xml creation
        if($xml_file){
          // 'XML file have been generated successfully.';
          if (gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')['val']=="SI"){
            // invio file xml tramite Sftp
            if ($sftp->put($ftp_path_upload."feedback.xml", $xmlFile, SFTP::SOURCE_LOCAL_FILE)){
              $sftp->disconnect();
            }else {
              // chiudo la connessione SFTP
              $sftp->disconnect();
              $rawres['title'] = "Upload tramite Sftp del file xml non riuscito. AGGIORNARE MANUALMENTE il feedback di ". $ref. " nel sito web";
              $rawres['button'] = 'Avviso eCommerce';
              $rawres['label'] = "OK, controllerò l'errore di scrittura SFTP";
              $rawres['link'] = '';
              $rawres['style'] = 'danger';
			  $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
			  $this->rawres=$rawres;
			  return;
            }
          } else {
            //turn passive mode on
            ftp_pasv($conn_id, true);
            // upload file xml
            if (@ftp_put($conn_id, $ftp_path_upload."feedback.xml", $xmlFile, FTP_ASCII)){
            } else{
              $rawres['title'] = "Upload del file xml non riuscito. AGGIORNARE MANUALMENTE il feedback di ". $ref. " nel sito web. <br> Ecco come è impostato il percorso per il file feedback.xml = ".$ftp_path_upload;
              $rawres['button'] = 'Avviso eCommerce';
              $rawres['label'] = "OK, controllerò l'errore di scrittura ftp";
              $rawres['link'] = '';
              $rawres['style'] = 'danger';
			  $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
				$this->rawres=$rawres;
				return;
            }
            // chiudo la connessione FTP
            ftp_quit($conn_id);
          }
          $access=base64_encode($accpass);
          // avvio il file di interfaccia presente nel sito web remoto
          $file = @fopen ($urlinterf.'?access='.$access, "r");
          if ($file){ // controllo se il file mi ha dato accesso regolare
            // se serve, qui posso controllare cosa ha restituito l'interfaccia tramite gli echo
            while (!feof($file)) { // scorro il file generato dall'interfaccia durante la sua eleborazione
                $line = fgets($file);
                // se serve, quì eseguo le operazioni con i dati restituiti
            }
            fclose($file);
          } else { // Riporto il codice di errore
            $rawres['title'] = "Impossibile connettersi all'interfaccia: ".intval(substr($headers[0], 9, 3)).". AGGIORNARE MANUALMENTE il feedback di ". $ref. " nel sito web";
            $rawres['button'] = 'Avviso eCommerce';
            $rawres['label'] = "OK, controllerò perché il file di interfaccia non ha funzionato";
            $rawres['link'] = '';
            $rawres['style'] = 'danger';
          }

          if (isset($rawres)){
            $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
            $this->rawres=$rawres;
          }
        }else{
            //'XML file generation error.';
            $rawres['title'] = "Problemi con la generazione del file xml di UpsertFeedback()! AGGIORNARE MANUALMENTE il feedback di ". $ref. " nel sito web!";
            $rawres['button'] = 'Avviso eCommerce';
            $rawres['label'] = "OK farò dei controlli";
            $rawres['link'] = '';
            $rawres['style'] = 'danger';
            $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
            $this->rawres=$rawres;
            return;
        }
      }

	}
	function UpsertCategory($d,$toDo="") {
		// usando il token precedentemente avuto si dovranno eseguire tutte le operazioni necessarie ad aggiornare la categorie merceologica quindi:
		// in base alle API messe a disposizione dallo specifico store (Opencart,Prestashop,Magento,ecc) si passeranno i dati in maniera opportuna...

			@session_start();
			global $gTables,$admin_aziend;
			$rawres=[];
			$gForm = new magazzForm();
			$ftp_host = gaz_dbi_get_row($gTables['company_config'], "var", "server")['val'];
			$ftp_path_upload = gaz_dbi_get_row($gTables['company_config'], "var", "ftp_path")['val'];
			$ftp_user = gaz_dbi_get_row($gTables['company_config'], "var", "user")['val'];
			$OSftp_pass = gaz_dbi_get_row($gTables['company_config'], "var", "pass")['val'];// vecchio sistema di password non criptata
			$OSaccpass = gaz_dbi_get_row($gTables['company_config'], "var", "accpass")['val'];// vecchio sistema di password non criptata
			$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'pass'");
			$rdec=gaz_dbi_fetch_row($rsdec);
			$ftp_pass=$rdec[0]?htmlspecialchars_decode($rdec[0]):'';
			$ftp_pass=(strlen($ftp_pass)>0)?$ftp_pass:$OSftp_pass; // se la password decriptata non ha dato risultati provo a vedere se c'è ancora una password non criptata
			if ($OSaccpass && $urlinterf = gaz_dbi_get_row($gTables['company_config'], 'var', 'path')['val']."upd-category.php"){// se sono state impostate
				$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'accpass'");
				$rdec=gaz_dbi_fetch_row($rsdec);
				$accpass=$rdec[0]?htmlspecialchars_decode($rdec[0]):'';
				$accpass=(strlen($accpass)>0)?$accpass:$OSaccpass; // se la password decriptata non ha dato risultati provo a mettere la password non criptata
			}else{
				$rawres['title'] = "Problemi con le impostazioni FTP: manca il percorso al file interfaccia e/o la sua password di accesso! AGGIORNARE MANUALMENTE la categoria ". $d['codice']."-".$d['descri']. " nel sito web ".$toDo;
				$rawres['button'] = 'Avviso eCommerce';
				$rawres['label'] = "OK fammi controllare le impostazioni";
				$rawres['link'] = '../shop-synchronize/config_sync.php';
				$rawres['style'] = 'danger';
				$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
				$this->rawres=$rawres;
				return;
			}
			if (gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')['val']=="SI"){// SFTP login with private key and password
				$ftp_port = gaz_dbi_get_row($gTables['company_config'], "var", "port")['val'];
				$ftp_key = gaz_dbi_get_row($gTables['company_config'], "var", "chiave")['val'];
				if (gaz_dbi_get_row($gTables['company_config'], "var", "keypass")['val']=="key"){ // SFTP log-in con KEY
					$key = PublicKeyLoader::load(file_get_contents('../../data/files/'.$admin_aziend['codice'].'/secret_key/'. $ftp_key .''),$ftp_pass);
					$sftp = new SFTP($ftp_host, $ftp_port);
					if (!$sftp->login($ftp_user, $key)) {
						// non si connette: key LOG-IN FALSE
						$rawres['title'] = "Problemi con la connessione Sftp usando il file chiave. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
						$rawres['button'] = 'Avviso eCommerce';
						$rawres['label'] = "Aggiornare i dati del gruppo: ". $d['codice']."-".$d['descri'];
						$rawres['link'] = '../shop-synchronize/synchronize.php';
						$rawres['style'] = 'danger';
						$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
						$this->rawres=$rawres;
						return;
					}
				} else { // SFTP log-in con password
					$sftp = new SFTP($ftp_host, $ftp_port);
					if (!$sftp->login($ftp_user, $ftp_pass)) {
						// non si connette: password LOG-IN FALSE
						$rawres['title'] = "Problemi con la connessione Sftp usando la password. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
						$rawres['button'] = 'Avviso eCommerce';
						$rawres['label'] = "Aggiornare la categoria: ". $d['codice']."-".$d['descri'];
						$rawres['link'] = '../shop-synchronize/synchronize.php';
						$rawres['style'] = 'danger';
						$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
						$this->rawres=$rawres;
						return;
					}
				}
			} else {
				// imposto la connessione al server
				if ($conn_id = @ftp_connect($ftp_host)){
          // effettuo login con user e pass
          $mylogin = ftp_login($conn_id, $ftp_user, $ftp_pass);
          // controllo se la connessione è OK...
          if ((!$conn_id) or (!$mylogin)){
            // non accede FALSE
            $rawres['title'] = "Problemi di accesso FTP (utente e/o password). AGGIORNARE ". $d['codice']."-".$d['descri'] ." NELL'E-COMMERCE MANUALMENTE!";
            $rawres['button'] = 'Avviso eCommerce';
            $rawres['label'] = "Vai alle impostazioni FTP ";
            $rawres['link'] = '../shop-synchronize/config_sync.php';
            $rawres['style'] = 'danger';
            $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
            $this->rawres=$rawres;
            return;
          }
        }else{
          // non si connette FALSE
            $rawres['title'] = "Problema con la connessione FTP Host=".$ftp_host.". AGGIORNARE ". $d['codice']."-".$d['descri'] ." NELL'E-COMMERCE MANUALMENTE!";
            $rawres['button'] = 'Avviso eCommerce';
            $rawres['label'] = "Vai alle impostazioni FTP ";
            $rawres['link'] = '../shop-synchronize/config_sync.php';
            $rawres['style'] = 'danger';
            $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
            $this->rawres=$rawres;
            return;
        }
			}
			// creo il file xml
			$xml_output = '<?xml version="1.0" encoding="UTF-8"?>
			<GAzieDocuments AppVersion="1" Creator="Antonio Germani" CreatorUrl="https://www.programmisitiweb.lacasettabio.it">';
			$xml_output .= "\n<Categories>\n";
			$xml_output .= "\t<Category>\n";
			$xml_output .= "\t<ToDo>".$toDo."</ToDo>\n";
			$xml_output .= "\t<Codice>".$d['codice']."</Codice>\n";
			$xml_output .= "\t<Descri>".$d['descri']."</Descri>\n";
    		$xml_output .= "\t<LargeDescri>".preg_replace('/[\x00-\x1f]/','',htmlspecialchars($d['large_descri'], ENT_QUOTES, 'UTF-8'))."</LargeDescri>\n";
			$xml_output .= "\t<WebUrl>".$d['web_url']."</WebUrl>\n";
			$xml_output .= "\t<RefIdCat>".$d['ref_ecommerce_id_category']."</RefIdCat>\n";
			$xml_output .= "\t<Top>".$d['top']."</Top>\n";// 0 => 'NON sincronizzato', 1 => 'Attivo e pubblicato in home', 2 => 'Attivo', 3 => 'Disattivato'
			$xml_output .= "\t</Category>\n";
			$xml_output .="</Categories>\n</GAzieDocuments>";
			$xmlFile = "category.xml";
			$xmlHandle = fopen($xmlFile, "w");
			fwrite($xmlHandle, $xml_output);
			fclose($xmlHandle);

			if (gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')['val']=="SI"){
				// invio file xml tramite Sftp
				if ($sftp->put($ftp_path_upload."category.xml", $xmlFile, SFTP::SOURCE_LOCAL_FILE)){
					$sftp->disconnect();
				}else {
					// chiudo la connessione SFTP
					$sftp->disconnect();
					$rawres['title'] = "Upload tramite Sftp del file xml non riuscito. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
					$rawres['button'] = 'Avviso eCommerce';
					$rawres['label'] = "Aggiornare i dati della categoria: ". $d['ref_ecommerce_id_category'] ."-". $d['descri'];
					$rawres['link'] = '../shop-synchronize/synchronize.php';
					$rawres['style'] = 'danger';
					$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
					$this->rawres=$rawres;
					return;
				}
			} else {
				//turn passive mode on
				ftp_pasv($conn_id, true);
				// upload file xml
				if (ftp_put($conn_id, $ftp_path_upload."category.xml", $xmlFile, FTP_ASCII)){
				} else{
					$rawres['title'] = "Upload del file xml non riuscito. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
					$rawres['button'] = 'Avviso eCommerce';
					$rawres['label'] = "Aggiornare i dati della categoria: ". $d['ref_ecommerce_id_category'] ."-". $d['descri'];
					$rawres['link'] = '../shop-synchronize/synchronize.php';
					$rawres['style'] = 'danger';
					$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
					$this->rawres=$rawres;
					return;
				}
				// chiudo la connessione FTP
				ftp_quit($conn_id);
			}
			$access=base64_encode($accpass);
			// avvio il file di interfaccia presente nel sito web remoto
			$file = fopen ($urlinterf.'?access='.$access, "r");
			if ($file){ // controllo se il file mi ha dato accesso regolare
				// se serve, qui posso controllare cosa ha restituito l'interfaccia tramite gli echo
				while (!feof($file)) { // scorro il file generato dall'interfaccia durante la sua eleborazione
					$line = fgets($file);
					if (substr($line,0,7)=="INSERT-"){ // Se l'e-commerce ha restituito l'ID riferito ad un insert
					  $ins_id=intval(substr($line,7));// vado a modificare il riferimento id e-commerce nella categoria di GAzie
					  gaz_dbi_put_row($gTables['catmer'], "codice", $d['codice'], "ref_ecommerce_id_category", $ins_id);
					}
				}
				fclose($file);
			} else { // Riporto il codice di errore
				$rawres['title'] = "Impossibile connettersi all'interfaccia: ".intval(substr($headers[0], 9, 3)).". AGGIORNARE L'E-COMMERCE MANUALMENTE!";
				$rawres['button'] = 'Avviso eCommerce';
				$rawres['label'] = "Aggiornare i dati della categoria: ". $d['ref_ecommerce_id_category'] ."-". $d['descri'];
				$rawres['link'] = '../shop-synchronize/synchronize.php';
				$rawres['style'] = 'danger';
			}

			if (isset($rawres)){
				$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
				$this->rawres=$rawres;
			}
	}
	function UpsertParent($p,$toDo="") {
		// aggiorno i dati del genitore delle varianti
		if ($p['web_public'] > 0){ // se pubblicato su web aggiorno l'articolo di magazzino (product)
			@session_start();
			global $gTables,$admin_aziend;
			$rawres=[];
			$gForm = new magazzForm();
			$ftp_host = gaz_dbi_get_row($gTables['company_config'], "var", "server")['val'];
			$ftp_path_upload = gaz_dbi_get_row($gTables['company_config'], "var", "ftp_path")['val'];
			$ftp_user = gaz_dbi_get_row($gTables['company_config'], "var", "user")['val'];
			$OSftp_pass = gaz_dbi_get_row($gTables['company_config'], "var", "pass")['val'];// vecchio sistema di password non criptata
			$OSaccpass = gaz_dbi_get_row($gTables['company_config'], "var", "accpass")['val'];// vecchio sistema di password non criptata
			$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'pass'");
			$rdec=gaz_dbi_fetch_row($rsdec);
			$ftp_pass=$rdec[0]?htmlspecialchars_decode($rdec[0]):'';
			$ftp_pass=(strlen($ftp_pass)>0)?$ftp_pass:$OSftp_pass; // se la password decriptata non ha dato risultati provo a vedere se c'è ancora una password non criptata
			if ($OSaccpass && $urlinterf = gaz_dbi_get_row($gTables['company_config'], 'var', 'path')['val']."articoli-gazie.php"){// se sono state impostate
				$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'accpass'");
				$rdec=gaz_dbi_fetch_row($rsdec);
				$accpass=$rdec[0]?htmlspecialchars_decode($rdec[0]):'';
				$accpass=(strlen($accpass)>0)?$accpass:$OSaccpass; // se la password decriptata non ha dato risultati provo a mettere la password non criptata
			}else{
				$rawres['title'] = "Problemi con le impostazioni FTP: manca il percorso al file interfaccia e/o la sua password di accesso! AGGIORNARE MANUALMENTE il genitore ". $p. " nel sito web ".$toDo;
				$rawres['button'] = 'Avviso eCommerce';
				$rawres['label'] = "OK fammi controllare le impostazioni";
				$rawres['link'] = '../shop-synchronize/config_sync.php';
				$rawres['style'] = 'danger';
				$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
				$this->rawres=$rawres;
				return;
			}
			$idHome = gaz_dbi_get_row($gTables['company_config'], "var", "home")['val'];
			// "group-gazie.php" è il nome del file interfaccia presente nella root dell'e-commerce. Per evitare intrusioni indesiderate Il file dovrà gestire anche una password. Per comodità viene usata la stessa FTP.
			// il percorso per raggiungere questo file va impostato in configurazione avanzata azienda alla voce "Website root directory"

			// calcolo la disponibilità in magazzino
			$rig_vars = gaz_dbi_dyn_query('*', $gTables['artico'], "id_artico_group = " . $p['id_artico_group']);
			$totav=0;
            while ( $rig_var = gaz_dbi_fetch_array($rig_vars) ) {// ciclo tutte le varianti di questo gruppo
				$ordinati=0;$avqty=0;
				$mv = $gForm->getStockValue(false, $rig_var['codice']);
				$magval = array_pop($mv);
				if (!isset($magval['q_g']))	{
					$qg=0;
				} else {
					$qg=floatval($magval['q_g']);
				}
				$ordinati = $gForm->get_magazz_ordinati($rig_var['codice'], "VOR");
				$ordinati = $ordinati + $gForm->get_magazz_ordinati($rig_var['codice'], "VOW");
				$avqty=$qg-$ordinati;
				if ($avqty<0 or $avqty==""){ // per l'e-commerce la disponibilità non può essere nulla o negativa
					$avqty="0";
				}
				$totav=$totav+$avqty;
			}

			if (gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')['val']=="SI"){// SFTP login with private key and password

				$ftp_port = gaz_dbi_get_row($gTables['company_config'], "var", "port")['val'];
				$ftp_key = gaz_dbi_get_row($gTables['company_config'], "var", "chiave")['val'];

				if (gaz_dbi_get_row($gTables['company_config'], "var", "keypass")['val']=="key"){ // SFTP log-in con KEY
					$key = PublicKeyLoader::load(file_get_contents('../../data/files/'.$admin_aziend['codice'].'/secret_key/'. $ftp_key .''),$ftp_pass);
					$sftp = new SFTP($ftp_host, $ftp_port);
					if (!$sftp->login($ftp_user, $key)) {
						// non si connette: key LOG-IN FALSE
						$rawres['title'] = "Problemi con la connessione Sftp usando il file chiave. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
						$rawres['button'] = 'Avviso eCommerce';
						$rawres['label'] = "Aggiornare i dati del gruppo: ". $p['id_artico_group'] ."-". $p['descri'];
						$rawres['link'] = '../shop-synchronize/synchronize.php';
						$rawres['style'] = 'danger';
						$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
						$this->rawres=$rawres;
						return;
					}
				} else { // SFTP log-in con password

					$sftp = new SFTP($ftp_host, $ftp_port);
					if (!$sftp->login($ftp_user, $ftp_pass)) {
						// non si connette: password LOG-IN FALSE
						$rawres['title'] = "Problemi con la connessione Sftp usando la password. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
						$rawres['button'] = 'Avviso eCommerce';
						$rawres['label'] = "Aggiornare i dati del gruppo: ". $p['id_artico_group'] ."-". $p['descri'];
						$rawres['link'] = '../shop-synchronize/synchronize.php';
						$rawres['style'] = 'danger';
						$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
						$this->rawres=$rawres;
						return;
					}
				}
			} else {

				// imposto la connessione al server
        if ($conn_id = @ftp_connect($ftp_host)){
          // effettuo login con user e pass
          $mylogin = ftp_login($conn_id, $ftp_user, $ftp_pass);
          // controllo se la connessione è OK...
          if ((!$conn_id) or (!$mylogin)){
            // non accede FALSE
            $rawres['title'] = "Problemi di accesso FTP (utente e password). AGGIORNARE ". $p['id_artico_group'] ."-". $p['descri'] ." NELL'E-COMMERCE MANUALMENTE!";
            $rawres['button'] = 'Avviso eCommerce';
            $rawres['label'] = "Vai alle impostazioni FTP ";
            $rawres['link'] = '../shop-synchronize/config_sync.php';
            $rawres['style'] = 'danger';
            $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
            $this->rawres=$rawres;
            return;
          }
        }else{
          // non si connette FALSE
            $rawres['title'] = "Problema con la connessione FTP Host=".$ftp_host.". AGGIORNARE ". $p['id_artico_group'] ."-". $p['descri'] ." NELL'E-COMMERCE MANUALMENTE!";
            $rawres['button'] = 'Avviso eCommerce';
            $rawres['label'] = "Vai alle impostazioni FTP ";
            $rawres['link'] = '../shop-synchronize/config_sync.php';
            $rawres['style'] = 'danger';
            $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
            $this->rawres=$rawres;
            return;
        }
			}
      if($toDo=="insert"){// se è insert la pubblicazione è sempre disattivata su web
        $p['web_public']=5;
      }
			// creo il file xml
			$xml_output = '<?xml version="1.0" encoding="UTF-8"?>
			<GAzieDocuments AppVersion="1" Creator="Antonio Germani 2018" CreatorUrl="https://www.programmisitiweb.lacasettabio.it">';
			$xml_output .= "\n<Products>\n";
				$xml_output .= "\t<Product>\n";
        $xml_output .= "\t<ToDo>".$toDo."</ToDo>\n";
				$xml_output .= "\t<Id>".$p['ref_ecommerce_id_main_product']."</Id>\n";
				$xml_output .= "\t<Code>".$p['id_artico_group']."</Code>\n";
				$xml_output .= "\t<Type>parent</Type>\n";
				$xml_output .= "\t<ParentId>".$p['id_artico_group']."</ParentId>\n";
				$xml_output .= "\t<Name>".$p['descri']."</Name>\n";
        $xml_output .= "\t<Price>0</Price>\n";// un parent non può avere il prezzo
				$xml_output .= "\t<PriceVATincl>0</PriceVATincl>\n";
				$xml_output .= "\t<Description>".preg_replace('/[\x00-\x1f]/','',htmlspecialchars($p['large_descri'], ENT_QUOTES, 'UTF-8'))."</Description>\n";
				$xml_output .= "\t<AvailableQty>".$totav."</AvailableQty>\n";
				$xml_output .= "\t<WebPublish>".$p['web_public']."</WebPublish>\n";// 1=attivo su web; 2=attivo e prestabilito; 3=attivo e pubblicato in home; 4=attivo, in home e prestabilito; 5=disattivato su web"
				$xml_output .= "\t<IdHome>".$idHome."</IdHome>\n";// id per pubblicazione home su web
				$xml_output .= "\t</Product>\n";
        if($toDo=="insert"){// se è un inserimento invio anche tutte le varianti
          $vars=gaz_dbi_dyn_query("*", $gTables['artico'], "id_artico_group =". $p['id_artico_group']);
            foreach($vars as $var){
              $xml_output .= "\t<Product>\n";
              $xml_output .= "\t<ToDo>".$toDo."</ToDo>\n";
              $xml_output .= "\t<Id>".$var['ref_ecommerce_id_product']."</Id>\n";
              $xml_output .= "\t<Code>".$var['codice']."</Code>\n";
              $xml_output .= "\t<Type>variant</Type>\n";
              $xml_output .= "\t<ParentId>".$p['id_artico_group']."</ParentId>\n";
              $xml_output .= "\t<Name>".$var['descri']."</Name>\n";
              // Calcolo il prezzo IVA compresa
              $aliquo=gaz_dbi_get_row($gTables['aliiva'], "codice", intval($var['aliiva']))['aliquo'];
              $web_price_vat_incl=$var['web_price']+(($var['web_price']*$aliquo)/100);
              $web_price_vat_incl=number_format($web_price_vat_incl, $admin_aziend['decimal_price'], '.', '');
              $xml_output .= "\t<Price>".$var['web_price']."</Price>\n";

              $xml_output .= "\t<PriceVATincl>".$web_price_vat_incl."</PriceVATincl>\n";
              $body=gaz_dbi_get_row($gTables['body_text'], 'table_name_ref', "artico_'".$var['codice']);
              if(!$body){
                $body['body_text']="";
              }
              $xml_output .= "\t<Description>".preg_replace('/[\x00-\x1f]/','',htmlspecialchars($body['body_text'], ENT_QUOTES, 'UTF-8'))."</Description>\n";
              $xml_output .= "\t<AvailableQty>0</AvailableQty>\n";
              $xml_output .= "\t<WebPublish>".$p['web_public']."</WebPublish>\n";// 1=attivo su web; 2=attivo e prestabilito; 3=attivo e pubblicato in home; 4=attivo, in home e prestabilito; 5=disattivato su web"
              $xml_output .= "\t<IdHome>".$idHome."</IdHome>\n";// id per pubblicazione home su web
              $xml_output .= "\t</Product>\n";
          }
        }
			$xml_output .="</Products>\n</GAzieDocuments>";
			$xmlFile = "prodotti.xml";
			$xmlHandle = fopen($xmlFile, "w");
			fwrite($xmlHandle, $xml_output);
			fclose($xmlHandle);

			if (gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')['val']=="SI"){
				// invio file xml tramite Sftp
				if ($sftp->put($ftp_path_upload."prodotti.xml", $xmlFile, SFTP::SOURCE_LOCAL_FILE)){
					$sftp->disconnect();
				}else {
					// chiudo la connessione SFTP
					$sftp->disconnect();
					$rawres['title'] = "Upload tramite Sftp del file xml non riuscito. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
					$rawres['button'] = 'Avviso eCommerce';
					$rawres['label'] = "Aggiornare i dati del gruppo: ". $p['id_artico_group'] ."-". $p['descri'];
					$rawres['link'] = '../shop-synchronize/synchronize.php';
					$rawres['style'] = 'danger';
				}
			} else {
				//turn passive mode on
				ftp_pasv($conn_id, true);
				// upload file xml
				if (ftp_put($conn_id, $ftp_path_upload."prodotti.xml", $xmlFile, FTP_ASCII)){
				} else{
					$rawres['title'] = "Upload del file xml non riuscito. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
					$rawres['button'] = 'Avviso eCommerce';
					$rawres['label'] = "Aggiornare i dati del gruppo: ". $p['id_artico_group'] ."-". $p['descri'];
					$rawres['link'] = '../shop-synchronize/synchronize.php';
					$rawres['style'] = 'danger';
				}
				// chiudo la connessione FTP
				ftp_quit($conn_id);
			}
			$access=base64_encode($accpass);
			// avvio il file di interfaccia presente nel sito web remoto
			$file = fopen ($urlinterf.'?access='.$access, "r");
			if ($file){ // controllo se il file mi ha dato accesso regolare
				// se serve, qui posso controllare cosa ha restituito l'interfaccia tramite gli echo
        $nl=0;
        while (!feof($file)) { // scorro il file generato dall'interfaccia durante la sua eleborazione
            $line = fgets($file);
            if (substr($line,0,7)=="INSERT-"){ // Se l'e-commerce ha restituito l'ID riferito ad un insert
              $ins_id=intval(substr($line,7));// vado a modificare il riferimento id e-commerce nell'articolo di GAzie
              if($nl==0){// è il parent
                gaz_dbi_put_row($gTables['artico_group'], "id_artico_group", $p['id_artico_group'], "ref_ecommerce_id_main_product", $ins_id);
              } else {// è una variante
                gaz_dbi_put_row($gTables['artico'], "codice", $var['codice'], "ref_ecommerce_id_product", $ins_id);
              }
            }
            $nl++;
        }
        fclose($file);
      } else { // Riporto il codice di errore
				$rawres['title'] = "Impossibile connettersi all'interfaccia: ".intval(substr($headers[0], 9, 3)).". AGGIORNARE L'E-COMMERCE MANUALMENTE!";
				$rawres['button'] = 'Avviso eCommerce';
				$rawres['label'] = "Aggiornare i dati del gruppo: ". $p['id_artico_group'] ."-". $p['descri'];
				$rawres['link'] = '../shop-synchronize/synchronize.php';
				$rawres['style'] = 'danger';
			}
		}
		if (isset($rawres)){
		  $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
		  $this->rawres=$rawres;
		}
	}
	function UpsertProduct($d,$toDo="") { // Aggiorna o inserisce articol da GAzie a e-commerce

		if ($d['web_public'] > 0){ // se pubblicato su web aggiorno l'articolo di magazzino (product)
			@session_start();
			global $gTables,$admin_aziend;
			$rawres=[];
			$ftp_host = gaz_dbi_get_row($gTables['company_config'], "var", "server")['val'];
			$ftp_path_upload = gaz_dbi_get_row($gTables['company_config'], "var", "ftp_path")['val'];
			$ftp_user = gaz_dbi_get_row($gTables['company_config'], "var", "user")['val'];
			$OSftp_pass = gaz_dbi_get_row($gTables['company_config'], "var", "pass")['val'];// vecchio sistema di password non criptata
			$OSaccpass = gaz_dbi_get_row($gTables['company_config'], "var", "accpass")['val'];// vecchio sistema di password non criptata
			$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'pass'");
			$rdec=gaz_dbi_fetch_row($rsdec);
			$ftp_pass=$rdec[0]?htmlspecialchars_decode($rdec[0]):'';
			$ftp_pass=(strlen($ftp_pass)>0)?$ftp_pass:$OSftp_pass; // se la password decriptata non ha dato risultati provo a vedere se c'è ancora una password non criptata
			if ($OSaccpass && $urlinterf = gaz_dbi_get_row($gTables['company_config'], 'var', 'path')['val']."articoli-gazie.php"){// se sono state impostate
				$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'accpass'");
				$rdec=gaz_dbi_fetch_row($rsdec);
				$accpass=$rdec[0]?htmlspecialchars_decode($rdec[0]):'';
				$accpass=(strlen($accpass)>0)?$accpass:$OSaccpass; // se la password decriptata non ha dato risultati provo a mettere la password non criptata
			}else{
				$rawres['title'] = "Problemi con le impostazioni FTP: manca il percorso al file interfaccia e/o la sua password di accesso! AGGIORNARE MANUALMENTE il prodotto ".$d." nel sito web ".$toDo;
				$rawres['button'] = 'Avviso eCommerce';
				$rawres['label'] = "OK fammi controllare le impostazioni";
				$rawres['link'] = '../shop-synchronize/config_sync.php';
				$rawres['style'] = 'danger';
				$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
				$this->rawres=$rawres;
				return;
			}
			$idHome = gaz_dbi_get_row($gTables['company_config'], "var", "home")['val'];
			// "articoli-gazie.php" è il nome del file interfaccia presente nella root dell'e-commerce. Per evitare intrusioni indesiderate Il file dovrà gestire anche una password. Per comodità viene usata la stessa FTP.
			// il percorso per raggiungere questo file va impostato in configurazione avanzata azienda alla voce "Website root directory"

			// carico tutti i dati dell'articolo
			$id = gaz_dbi_get_row($gTables['artico'],"codice",$d['codice']);
			if (!isset($id)){
			$rawres['title'] = "Prodotto non correttamente sincronizzato. Controllare le sue impostazioni e ID di riferimento all e-commerce!";
			$rawres['button'] = 'Avviso eCommerce';
			$rawres['label'] = "Aggiornare i dati di: ". $d['codice'];
			$rawres['link'] = '../shop-synchronize/synchronize.php';
			$rawres['style'] = 'danger';
			$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
			$this->rawres=$rawres;
			return;
			}

			if ($d['good_or_service']==1){// se non movimenta il magazzino
				$avqty=NULL;
			}else{
				// calcolo la disponibilità in magazzino
				$gForm = new magazzForm();
				$mv = $gForm->getStockValue(false, $d['codice']);
				$magval = array_pop($mv);

				$fields = array ('product_id' => $id['ref_ecommerce_id_product'],'quantity'=>intval((isset($magval['q_g']))?$magval['q_g']:0));
				$ordinati = $gForm->get_magazz_ordinati($d['codice'], "VOR");
				$ordinati = $ordinati + $gForm->get_magazz_ordinati($d['codice'], "VOW");
				$avqty=$fields['quantity']-$ordinati;
				if ($avqty<0 or $avqty==""){ // per l'e-commerce la disponibilità non può essere nulla o negativa
				  $avqty="0";
				}
			}

			$ecomm_catmer = gaz_dbi_get_row($gTables['catmer'],"codice",$d['catmer'])['ref_ecommerce_id_category'];
			if (intval($d['barcode'])==0) {// se non c'è barcode allora è nullo
				$d['barcode']="NULL";
			}

			if (gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')['val']=="SI"){// SFTP login with private key and password

				$ftp_port = gaz_dbi_get_row($gTables['company_config'], "var", "port")['val'];
				$ftp_key = gaz_dbi_get_row($gTables['company_config'], "var", "chiave")['val'];

				if (gaz_dbi_get_row($gTables['company_config'], "var", "keypass")['val']=="key"){ // SFTP log-in con KEY
					$key = PublicKeyLoader::load(file_get_contents('../../data/files/'.$admin_aziend['codice'].'/secret_key/'. $ftp_key .''),$ftp_pass);
					$sftp = new SFTP($ftp_host, $ftp_port);
					if (!$sftp->login($ftp_user, $key)) {
						// non si connette: key LOG-IN FALSE
						$rawres['title'] = "Problemi con la connessione Sftp usando il file chiave. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
						$rawres['button'] = 'Avviso eCommerce';
						$rawres['label'] = "Aggiornare i dati dell'articolo: ". $d['codice'];
						$rawres['link'] = '../shop-synchronize/synchronize.php';
						$rawres['style'] = 'danger';
						$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
						$this->rawres=$rawres;
						return;
					}
				} else { // SFTP log-in con password

					$sftp = new SFTP($ftp_host, $ftp_port);
					if (!$sftp->login($ftp_user, $ftp_pass)) {
						// non si connette: password LOG-IN FALSE
						$rawres['title'] = "Problemi con la connessione Sftp usando la password. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
						$rawres['button'] = 'Avviso eCommerce';
						$rawres['label'] = "Aggiornare i dati dell'articolo: ". $d['codice'];
						$rawres['link'] = '../shop-synchronize/synchronize.php';
						$rawres['style'] = 'danger';
						$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
						$this->rawres=$rawres;
						return;
					}
				}
			} else {

				// imposto la connessione al server
        if ($conn_id = @ftp_connect($ftp_host)){

          // effettuo login con user e pass
          $mylogin = ftp_login($conn_id, $ftp_user, $ftp_pass);

          // controllo se la connessione è OK...
          if ((!$conn_id) or (!$mylogin)){
            // non si connette FALSE
            $rawres['title'] = "Problemi con le impostazioni FTP in configurazione avanzata azienda. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
            $rawres['button'] = 'Avviso eCommerce';
            $rawres['label'] = "Aggiornare i dati dell'articolo: ". $d['codice'];
            $rawres['link'] = '../shop-synchronize/synchronize.php';
            $rawres['style'] = 'danger';
            $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
            $this->rawres=$rawres;
            return;
          }
        }else{
          // non si connette al server
          $rawres['title'] = "Problemi con la connessione al server controllare l'impostazione host. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
          $rawres['button'] = 'Avviso eCommerce';
          $rawres['label'] = "Aggiornare i dati dell'articolo: ". $d['codice'];
          $rawres['link'] = '../shop-synchronize/synchronize.php';
          $rawres['style'] = 'danger';
          $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
          $this->rawres=$rawres;
          return;
        }
			}

			// Calcolo il prezzo IVA compresa
			$aliquo=gaz_dbi_get_row($gTables['aliiva'], "codice", intval($d['aliiva']))['aliquo'];
			$web_price_vat_incl=$d['web_price']+(($d['web_price']*$aliquo)/100);
			$web_price_vat_incl=number_format($web_price_vat_incl, $admin_aziend['decimal_price'], '.', '');
	 		// creo il file xml
			$xml_output = '<?xml version="1.0" encoding="UTF-8"?>
			<GAzieDocuments AppVersion="1" Creator="Antonio Germani 2018" CreatorUrl="https://www.lacasettabio.it">';
			$xml_output .= "\n<Products>\n";
				$xml_output .= "\t<Product>\n";
				$xml_output .= "\t<ToDo>".$toDo."</ToDo>\n";
				$xml_output .= "\t<Id>".$d['ref_ecommerce_id_product']."</Id>\n";
				if ($id['id_artico_group']>0){
					$xml_output .= "\t<Type>variant</Type>\n";
					$parid = gaz_dbi_get_row($gTables['artico_group'], "id_artico_group", $id['id_artico_group'])['ref_ecommerce_id_main_product'];
					$xml_output .= "\t<ParentId>".$parid."</ParentId>\n";
					$var = json_decode($id['ecomm_option_attribute']);
					$xml_output .= "\t<Characteristic>".$var->var_name."</Characteristic>\n";
					$xml_output .= "\t<CharacteristicId>".$var->var_id."</CharacteristicId>\n";
				} else {
					$xml_output .= "\t<Type>product</Type>\n";
					$xml_output .= "\t<ParentId></ParentId>\n";
				}
				$xml_output .= "\t<Code>".$d['codice']."</Code>\n";
				$xml_output .= "\t<BarCode>".$d['barcode']."</BarCode>\n";
				$xml_output .= "\t<Peso>".$d['peso_specifico']."</Peso>\n";
				$xml_output .= "\t<Largmm>".$d['larghezza']."</Largmm>\n";
				$xml_output .= "\t<Lungmm>".$d['lunghezza']."</Lungmm>\n";
				$xml_output .= "\t<Spessmm>".$d['spessore']."</Spessmm>\n";
				$xml_output .= "\t<Name>".preg_replace('/[\x00-\x1f]/','',htmlspecialchars($d['descri'], ENT_QUOTES, 'UTF-8'))."</Name>\n";
				$xml_output .= "\t<Description>".preg_replace('/[\x00-\x1f]/','',htmlspecialchars($d['body_text'], ENT_QUOTES, 'UTF-8'))."</Description>\n";
				$xml_output .= "\t<Price>".$d['web_price']."</Price>\n";
				$xml_output .= "\t<PriceVATincl>".$web_price_vat_incl."</PriceVATincl>\n";
				$xml_output .= "\t<VAT>".$aliquo."</VAT>\n";
				$xml_output .= "\t<Unimis>".$d['unimis']."</Unimis>\n";
				$xml_output .= "\t<ProductCategory>".$ecomm_catmer."</ProductCategory>\n";
				$xml_output .= "\t<AvailableQty>".$avqty."</AvailableQty>\n";
				$xml_output .= "\t<WebPublish>".$d['web_public']."</WebPublish>\n";// 1=attivo su web; 2=attivo e prestabilito; 3=attivo e pubblicato in home; 4=attivo, in home e prestabilito; 5=disattivato su web"
				$xml_output .= "\t<IdHome>".$idHome."</IdHome>\n";// id per pubblicazione home su web
				$xml_output .= "\t</Product>\n";
			$xml_output .="</Products>\n</GAzieDocuments>";
			$xmlFile = "prodotti.xml";
			$xmlHandle = fopen($xmlFile, "w");
			fwrite($xmlHandle, $xml_output);
			fclose($xmlHandle);

			if (gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')['val']=="SI"){
				// invio file xml tramite Sftp
				if ($sftp->put($ftp_path_upload."prodotti.xml", $xmlFile, SFTP::SOURCE_LOCAL_FILE)){
					$sftp->disconnect();
				}else {
					// chiudo la connessione SFTP
					$sftp->disconnect();
					$rawres['title'] = "Upload tramite Sftp del file xml non riuscito. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
					$rawres['button'] = 'Avviso eCommerce';
					$rawres['label'] = "Aggiornare i dati dell'articolo: ". $d['codice'];
					$rawres['link'] = '../shop-synchronize/synchronize.php';
					$rawres['style'] = 'danger';
				}
			} else {
				//turn passive mode on
				ftp_pasv($conn_id, true);
				// upload file xml
				if (ftp_put($conn_id, $ftp_path_upload."prodotti.xml", $xmlFile, FTP_ASCII)){
				} else{
					$rawres['title'] = "Upload del file xml non riuscito. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
					$rawres['button'] = 'Avviso eCommerce';
					$rawres['label'] = "Aggiornare i dati dell'articolo: ". $d['codice'];
					$rawres['link'] = '../shop-synchronize/synchronize.php';
					$rawres['style'] = 'danger';
				}
				// chiudo la connessione FTP
				ftp_quit($conn_id);
			}

			$access=base64_encode($accpass);

			// avvio il file di interfaccia presente nel sito web remoto
			$file = fopen ($urlinterf.'?access='.$access, "r");
			if ( $file){ // controllo se il file mi ha dato accesso regolare
        while (!feof($file)) { // scorro il file generato dall'interfaccia durante la sua eleborazione
            $line = fgets($file);
            if (substr($line,0,7)=="INSERT-"){ // Se l'e-commerce ha restituito l'ID riferito ad un insert
            $ins_id=intval(substr($line,7));// vado a modificare il riferimenot id e-commerce nell'articolo di GAzie
              gaz_dbi_put_row($gTables['artico'], "codice", $d['codice'], "ref_ecommerce_id_product", $ins_id);
            }
        }
        fclose($file);
			} else { // ERRORE di connessione
				$rawres['title'] = "L'interfaccia non si connette. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
					$rawres['button'] = 'Avviso eCommerce';
					$rawres['label'] = "Aggiornare i dati dell'articolo: ". $d['codice'];
					$rawres['link'] = '../shop-synchronize/synchronize.php';
					$rawres['style'] = 'danger';
			}
		}
		if (isset($rawres)){
      $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
      $this->rawres=$rawres;
		}

	}
	function SetProductQuantity($d) {
		// aggiornamento quantità disponibile di un articolo

		@session_start();
		global $gTables,$admin_aziend;
		$rawres=[];
		$id = gaz_dbi_get_row($gTables['artico'],"codice",$d);
		if (isset($id['web_public']) && $id['web_public'] > 0){
			$ftp_host = gaz_dbi_get_row($gTables['company_config'], "var", "server")['val'];
			$ftp_path_upload = gaz_dbi_get_row($gTables['company_config'], "var", "ftp_path")['val'];
			$ftp_user = gaz_dbi_get_row($gTables['company_config'], "var", "user")['val'];
			$OSftp_pass = gaz_dbi_get_row($gTables['company_config'], "var", "pass")['val'];// vecchio sistema di password non criptata
			$OSaccpass = gaz_dbi_get_row($gTables['company_config'], "var", "accpass")['val'];// vecchio sistema di password non criptata
			$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'pass'");
			$rdec=gaz_dbi_fetch_row($rsdec);
			$ftp_pass=$rdec[0]?htmlspecialchars_decode($rdec[0]):'';
			$ftp_pass=(strlen($ftp_pass)>0)?$ftp_pass:$OSftp_pass; // se la password decriptata non ha dato risultati provo a vedere se c'è ancora una password non criptata
			if ($OSaccpass && $urlinterf = gaz_dbi_get_row($gTables['company_config'], 'var', 'path')['val']."articoli-gazie.php"){// se sono state impostate
				$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'accpass'");
				$rdec=gaz_dbi_fetch_row($rsdec);
				$accpass=$rdec[0]?htmlspecialchars_decode($rdec[0]):'';
				$accpass=(strlen($accpass)>0)?$accpass:$OSaccpass; // se la password decriptata non ha dato risultati provo a mettere la password non criptata
			}else{
				$rawres['title'] = "Problemi con le impostazioni FTP: manca il percorso al file interfaccia e/o la sua password di accesso! AGGIORNARE MANUALMENTE la quantità di ". $d. " nel sito web";
				$rawres['button'] = 'Avviso eCommerce';
				$rawres['label'] = "OK fammi controllare le impostazioni";
				$rawres['link'] = '../shop-synchronize/config_sync.php';
				$rawres['style'] = 'danger';
				$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
				$this->rawres=$rawres;
				return;
			}
			// "articoli-gazie.php" è il nome del file interfaccia presente nella root del sito e-commerce. Per evitare intrusioni indesiderate Il file dovrà gestire anche una password. Per comodità viene usata la stessa FTP.
			// il percorso per raggiungere questo file va impostato in configurazione avanzata azienda alla voce "Website root directory
			$gForm = new magazzForm();
			$mv = $gForm->getStockValue(false, $d);
			$magval = array_pop($mv);
			// creo array fields con ID di riferimento e  disponibilità
      $magvalq_g=(isset($magval['q_g']))?$magval['q_g']:0;
			$fields = array ('product_id' => $id['ref_ecommerce_id_product'],'quantity'=>intval($magvalq_g));
			$ordinati = $gForm->get_magazz_ordinati($d, "VOR");
			$ordinati = $ordinati + $gForm->get_magazz_ordinati($d, "VOW");
			$avqty=$fields['quantity']-$ordinati;
			if ($avqty<0 or $avqty==""){ // per l'e-commerce la disponibilità non può essere nulla o negativa
				$avqty="0";
			}
			if (intval($id['barcode'])==0) {// se non c'è barcode allora è nullo
				$id['barcode']="NULL";
			}

			if ((gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')) AND gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')['val']=="SI"){// SFTP login with private key and password

				$ftp_port = gaz_dbi_get_row($gTables['company_config'], "var", "port")['val'];
				$ftp_key = gaz_dbi_get_row($gTables['company_config'], "var", "chiave")['val'];

				if (gaz_dbi_get_row($gTables['company_config'], "var", "keypass")['val']=="key"){ // SFTP log-in con KEY
					$key = PublicKeyLoader::load(file_get_contents('../../data/files/'.$admin_aziend['codice'].'/secret_key/'. $ftp_key .''),$ftp_pass);
					$sftp = new SFTP($ftp_host, $ftp_port);
					if (!$sftp->login($ftp_user, $key)) {
						// non si connette: key LOG-IN FALSE
						$rawres['title'] = "Problemi con la connessione Sftp usando il file chiave. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
						$rawres['button'] = 'Avviso eCommerce';
						$rawres['label'] = "Aggiornare la quantità dell'articolo: ". $d;
						$rawres['link'] = '../shop-synchronize/synchronize.php';
						$rawres['style'] = 'danger';
						$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
						$this->rawres=$rawres;
						return;
					}
				} else { // SFTP log-in con password

					$sftp = new SFTP($ftp_host, $ftp_port);
					if (!$sftp->login($ftp_user, $ftp_pass)) {
						// non si connette: password LOG-IN FALSE
						$rawres['title'] = "Problemi con la connessione Sftp usando la password. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
						$rawres['button'] = 'Avviso eCommerce';
						$rawres['label'] = "Aggiornare la quantità dell'articolo: ". $d;
						$rawres['link'] = '../shop-synchronize/synchronize.php';
						$rawres['style'] = 'danger';
						$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
						$this->rawres=$rawres;
						return;
					}
				}
			} else {

				// imposto la connessione al server
				$conn_id = ftp_connect($ftp_host);

				// controllo se la connessione è OK...
				if ((!$conn_id)){
					// non si connette FALSE
					$rawres['title'] = "Problemi con le impostazioni FTP in configurazione avanzata azienda. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
					$rawres['button'] = 'Avviso eCommerce';
					$rawres['label'] = "Aggiornare la quantità dell'articolo: ". $d;
					$rawres['link'] = '../shop-synchronize/synchronize.php';
					$rawres['style'] = 'danger';
					$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
					$this->rawres=$rawres;
					return;
				}

				// effettuo login con user e pass
				$mylogin = ftp_login($conn_id, $ftp_user, $ftp_pass);

				// controllo se il log-in è OK...
				if ((!$mylogin)){
					// non si connette FALSE
					$rawres['title'] = "Problemi con le impostazioni FTP in configurazione avanzata azienda. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
					$rawres['button'] = 'Avviso eCommerce';
					$rawres['label'] = "Aggiornare la quantità dell'articolo: ". $d;
					$rawres['link'] = '../shop-synchronize/synchronize.php';
					$rawres['style'] = 'danger';
					$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
					$this->rawres=$rawres;
					return;
				}
			}

	 		// creo il file xml
			$xml_output = '<?xml version="1.0" encoding="ISO-8859-1"?>
			<GAzieDocuments AppVersion="1" Creator="Antonio Germani 2018" CreatorUrl="https://www.lacasettabio.it">';
			$xml_output .= "\n<Products>\n";
				$xml_output .= "\t<Product>\n";
				$xml_output .= "\t<Id>".$id['ref_ecommerce_id_product']."</Id>\n";
				$xml_output .= "\t<Code>".$id['codice']."</Code>\n";
				$xml_output .= "\t<BarCode>".$id['barcode']."</BarCode>\n";
				$xml_output .= "\t<AvailableQty>".$avqty."</AvailableQty>\n";
				if ($id['id_artico_group']>0){
					$xml_output .= "\t<Type>variant</Type>\n";
					$parid = gaz_dbi_get_row($gTables['artico_group'], "id_artico_group", $id['id_artico_group'])['ref_ecommerce_id_main_product'];

					$xml_output .= "\t<ParentId>".$parid."</ParentId>\n";
				} else {
					$xml_output .= "\t<Type>product</Type>\n";
					$xml_output .= "\t<ParentId></ParentId>\n";
				}
				$xml_output .= "\t</Product>\n";
			$xml_output .="</Products>\n</GAzieDocuments>";
			$xmlFile = "prodotti.xml";
			$xmlHandle = fopen($xmlFile, "w");
			fwrite($xmlHandle, $xml_output);
			fclose($xmlHandle);

			if (gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')['val']=="SI"){
				// invio file xml tramite Sftp
				if ($sftp->put($ftp_path_upload."prodotti.xml", $xmlFile, SFTP::SOURCE_LOCAL_FILE)){
					$sftp->disconnect();
				}else {
					// chiudo la connessione SFTP
					$sftp->disconnect();
					$rawres['title'] = "Upload tramite Sftp del file xml non riuscito. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
					$rawres['button'] = 'Avviso eCommerce';
					$rawres['label'] = "Aggiornare i dati dell'articolo: ". $d;
					$rawres['link'] = '../shop-synchronize/synchronize.php';
					$rawres['style'] = 'danger';
				}
			} else { // invio tramite ftp semplice
				//turn passive mode on
				ftp_pasv($conn_id, true);
				// upload file xml
				if (ftp_put($conn_id, $ftp_path_upload."prodotti.xml", $xmlFile, FTP_ASCII)){
				} else{
					$rawres['title'] = "Upload del file xml non riuscito. AGGIORNARE L'E-COMMERCE MANUALMENTE!";
					$rawres['button'] = 'Avviso eCommerce';
					$rawres['label'] = "Aggiornare la quantità dell'articolo: ". $d;
					$rawres['link'] = '../shop-synchronize/synchronize.php';
					$rawres['style'] = 'danger';
				}
				// chiudo la connessione FTP
				ftp_quit($conn_id);
			}
			$access=base64_encode($accpass);
			// avvio il file di interfaccia presente nel sito web remoto
			$file = fopen ($urlinterf.'?access='.$access, "r");
			if ($file){ // controllo se il file mi ha dato accesso regolare
				// se serve, qui posso controllare cosa ha restituito l'interfaccia tramite gli echo
        fclose($file);
      } else { // Riporto il codice di errore
				$rawres['title'] = "Impossibile connettersi all'interfaccia: ".intval(substr($headers[0], 9, 3)).". AGGIORNARE L'E-COMMERCE MANUALMENTE!";
				$rawres['button'] = 'Avviso eCommerce';
				$rawres['label'] = "Aggiornare la quantità dell'articolo: ". $d;
				$rawres['link'] = '../shop-synchronize/synchronize.php';
				$rawres['style'] = 'danger';
			}
		}
		if (isset($rawres)){
      $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
      $this->rawres=$rawres;
    }
	}
	function get_sync_status($last_id) {
		// prendo gli eventuali ordini arrivati assieme ai dati del cliente, se nuovo lo importo (order+customer),
		// in $last_id si deve passare l'ultimo ordine già importato al fine di non importare tutto ma solo i nuovi
		//Antonio Germani - $last_id non viene usato perché si controlla con una query se l'ordine è già stato importato
		@session_start();

		global $gTables,$admin_aziend;
        $rawres=[];
		$OSftp_pass = gaz_dbi_get_row($gTables['company_config'], "var", "pass")['val'];// vecchio sistema di password non criptata
			$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'pass'");
			$rdec=gaz_dbi_fetch_row($rsdec);
			$OSaccpass = gaz_dbi_get_row($gTables['company_config'], "var", "accpass")['val'];// vecchio sistema di password non criptata
			if ($OSaccpass && $urlinterf = gaz_dbi_get_row($gTables['company_config'], 'var', 'path')['val']."ordini-gazie.php"){// se sono state impostate
				$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'accpass'");
				$rdec=gaz_dbi_fetch_row($rsdec);
				$accpass=$rdec[0]?htmlspecialchars_decode($rdec[0]):'';
				$accpass=(strlen($accpass)>0)?$accpass:$OSaccpass; // se la password decriptata non ha dato risultati provo a mettere la password non criptata
			}else{
				$rawres['title'] = "Problemi con le impostazioni FTP: manca il percorso al file interfaccia e/o la sua password di accesso! ORDINI NON SCARICATI";
				$rawres['button'] = 'Avviso eCommerce';
				$rawres['label'] = "OK fammi controllare le impostazioni";
				$rawres['link'] = '../shop-synchronize/config_sync.php';
				$rawres['style'] = 'danger';
				$_SESSION['menu_alerts']['shop-synchronize']=$rawres;
				$this->rawres=$rawres;
				return;
			}

		$access=base64_encode($accpass);
		// avvio il file di interfaccia presente nel sito web remoto
		$headers = @get_headers($urlinterf.'?access='.$access);
		$count=0;
		if ( is_array($headers) AND intval(substr($headers[0], 9, 3))==200){ // controllo se il file esiste o mi dà accesso
			if(file_get_contents($urlinterf.'?access='.$access.'&rnd='.time())) {// se non è vuoto posso procedere
				$xml=simplexml_load_file($urlinterf.'?access='.$access.'&rnd='.time());
				if (!$xml){
                    $rawres['title'] = "L'interfaccia non si apre: impossibile scaricare gli ordini dall'e-commerce";
                    $rawres['button'] = 'Avviso eCommerce';
                    $rawres['label'] = "L'interfaccia non si apre o non esiste";
                    $rawres['link'] = '';
                    $rawres['style'] = 'danger';
    			}
    			$countDocument=0;$numdoc=""; $year="";

    			foreach($xml->Documents->children() as $order) { // ciclo gli ordini

					if(!gaz_dbi_get_row($gTables['tesbro'], "ref_ecommerce_id_order", $order->Numbering, " AND datemi  = '".$order->DateOrder."'")){// se l'ordine non esiste lo carico in GAzie
						if ($numdoc=="" and $year==""){// se sono al primo ciclo degli ordini
							// ricavo il progressivo numero d'ordine di GAzie in base al tipo di documento
							$orderdb = "numdoc desc";
							$sql_documento = "YEAR(datemi) = " . substr($order->DateOrder,0,4) . " and tipdoc = 'VOW'";
							$rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesbro'], $sql_documento, $orderdb, 0, 1);
							$ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
							// se e' il primo documento dell'anno, resetto il contatore
							if ($ultimo_documento) {
								$numdoc = $ultimo_documento['numdoc'] + 1;
							} else {
								$numdoc = 1;
							}
							$year=substr($order->DateOrder,0,4);
						}elseif(intval(substr($order->DateOrder,0,4))> intval($year)) {// se è cambiato l'anno durante il ciclo degli ordini e sono nel nuovo anno
							$numdoc = 1;// ricomincio la numerazione
						}
						$query = "SHOW TABLE STATUS LIKE '" . $gTables['anagra'] . "'";
						$result = gaz_dbi_query($query);
						$row = $result->fetch_assoc();
						$id_anagra = $row['Auto_increment']; // questo è l'ID che avrà ANAGRA: Anagrafica cliente
						$anagrafica = new Anagrafica();
						$last = $anagrafica->queryPartners('*', "codice BETWEEN " . $admin_aziend['mascli'] . "000000 AND " . $admin_aziend['mascli'] . "999999", "codice DESC", 0, 1);
						$codice = substr($last[0]['codice'], 3) + 1;
						$clfoco = $admin_aziend['mascli'] * 1000000 + $codice;// questo è il codice di CLFOCO da connettere all'anagrafica cliente se il cliente non esiste
						$esiste=0;
						if (strlen($order->CustomerCode)>0){ // controllo esistenza cliente per codice e-commerce
							unset($cl);
							$cl = gaz_dbi_get_row($gTables['clfoco'], "ref_ecommerce_id_customer", $order->CustomerCode);
							if (isset($cl)){
								$clfoco=$cl['codice'];
								$esiste=1;
							}
						}
						// provo a ricongiungere i pagamenti
						if(strlen($order->PaymentId)>0){//se l'e-commerce ha inviato il suo id di riferimento lo inserisco nella testata
							//provo a ricongiungerlo con GAzie
							$pag = gaz_dbi_get_row($gTables['pagame'], "web_payment_ref", $order->PaymentId);
							$idpagame=(isset($pag['codice']))?$pag['codice']:0;
						}else{// altrimenti non iserisco alcun pagamento
							$idpagame=0;
						}
						if ($esiste==0) { //registro cliente se non esiste
							if ($order->CustomerCountry=="IT"){ // se la nazione è IT
								$lang="1";
								if (substr_compare($order->CustomerVatCode, "IT", 0, 2, true)==0){// se c'è IT davanti alla partita iva
								  $order->CustomerVatCode=substr($order->CustomerVatCode,2);// tolgo IT
								}
								if (strlen($order->CustomerVatCode)<>11 && intval($order->CustomerVatCode)==0){// se non è una partita iva allora è un privato
									$order->CustomerVatCode=""; // deve essere vuoto
									$order->CustomerCodeFattEl = "0000000";// il codice univoco deve essere 7 volte zero
								}

							} elseif ($order->CustomerCountry=="EN"){ // se la nazione è EN // se non è italiano imposto il codice univoco con 7 X maiuscolo e il codice fiscale con il codice clfoco assegnato da GAzie
								$lang="1";
								$order->CustomerCodeFattEl = "XXXXXXX";
								if (strlen($order->CustomerFiscalCode)==0 || strlen($order->CustomerFiscalCode)<7){
									$order->CustomerFiscalCode =  sprintf("%07d", $clfoco);// riempio il campo codice fiscale con clfoco di almeno 7 cifre
								}
								if (strlen($order->CustomerVatCode)==0 || strlen($order->CustomerVatCode)<7){// se non è stato inviato nulla o comunque ha meno di 7 caratteri
									$order->CustomerVatCode= sprintf("%07d", $clfoco);// riempio il campo piva con il codice clfoco di almeno 7 cifre
								}
							}elseif ($order->CustomerCountry=="ES"){ // se la nazione è ES // se non è italiano imposto il codice univoco con 7 X maiuscolo e il codice fiscale con il codice clfoco assegnato da GAzie
								$lang="2";
								$order->CustomerCodeFattEl = "XXXXXXX";
								if (strlen($order->CustomerFiscalCode)==0 || strlen($order->CustomerFiscalCode)<7){
									$order->CustomerFiscalCode =  sprintf("%07d", $clfoco);// riempio il campo codice fiscale con clfoco di almeno 7 cifre
								}
								if (strlen($order->CustomerVatCode)==0 || strlen($order->CustomerVatCode)<7){// se non è stato inviato nulla o comunque ha meno di 7 caratteri
									$order->CustomerVatCode= sprintf("%07d", $clfoco);// riempio il campo piva con il codice clfoco di almeno 7 cifre
								}
							}else{ // altrimenti se non è italiano imposto la lingua EN e il codice univoco con 7 X maiuscolo e il codice fiscale con il codice clfoco assegnato da GAzie
								$lang="1";
								$order->CustomerCodeFattEl = "XXXXXXX";
								if (strlen($order->CustomerFiscalCode)==0 || strlen($order->CustomerFiscalCode)<7){
									$order->CustomerFiscalCode =  sprintf("%07d", $clfoco);// riempio il campo codice fiscale con clfoco di almeno 7 cifre
								}
								if (strlen($order->CustomerVatCode)==0 || strlen($order->CustomerVatCode)<7){// se non è stato inviato nulla o comunque ha meno di 7 caratteri
									$order->CustomerVatCode= sprintf("%07d", $clfoco);// riempio il campo piva con il codice clfoco di almeno 7 cifre
								}
							}
							if (strlen ($order->CustomerFiscalCode)==16 AND intval ($order->CustomerFiscalCode)==0){ // se il codice fiscale non è numerico
								if (substr($order->CustomerFiscalCode,9,2)>40){ // deduco il sesso
									$sexper="F";
								} else {
									$sexper="M";
								}
							} else {
								$sexper="G";
								if (strlen ($order->CustomerFiscalCode)==0){// se non è stato passato il codice fiscale
								  $order->CustomerFiscalCode = "00000000000";//GAzie vuole 11 zeri
								}
							}
							gaz_dbi_query("INSERT INTO " . $gTables['anagra'] . "(ragso1,ragso2,sexper,indspe,capspe,citspe,prospe,country,id_currency,id_language,telefo,codfis,pariva,fe_cod_univoco,e_mail,pec_email) VALUES ('" . addslashes($order->CustomerSurname)." ". addslashes($order->CustomerName) . "', '" . addslashes($order->BusinessName) . "', '". $sexper. "', '".addslashes($order->CustomerAddress) ."', '".$order->CustomerPostCode."', '". addslashes($order->CustomerCity) ."', '". $order->CustomerProvince ."', '" . addslashes($order->CustomerCountry). "', '1', '".$lang."', '". $order->CustomerTel ."', '". strtoupper($order->CustomerFiscalCode) ."', '" . $order->CustomerVatCode . "', '" . $order->CustomerCodeFattEl . "', '". $order->CustomerEmail . "', '". $order->CustomerPecEmail . "')");

							gaz_dbi_query("INSERT INTO " . $gTables['clfoco'] . "(ref_ecommerce_id_customer,codice,id_anagra,listin,descri,destin,speban,stapre,codpag) VALUES ('". $order->CustomerCode ."', '". $clfoco . "', '" . $id_anagra . "', '". intval($order->PriceListNum) ."' ,'" .addslashes($order->CustomerName)." ".addslashes($order->CustomerSurname) . "', '". addslashes($order->CustomerShippingDestin) ."', 'S', 'T', '".$idpagame."')");
						}

						if ($order->TotalDiscount>0){ // se il sito ha mandato uno sconto totale a valore calcolo lo sconto in percentuale da dare ad ogni rigo
							$lordo=$order->Total+$order->TotalDiscount-$order->CostPaymentAmount-$order->CostShippingAmount;
							$netto=$lordo-$order->TotalDiscount;
							$percdisc= 100-(($netto/$lordo)*100);
						} else {
							$percdisc="";
						}

						if ($order->PricesIncludeVat=="true"){ // se il sito include l'iva la scorporo dalle spese banca e trasporto
							$CostPaymentAmount=floatval($order->CostPaymentAmount)/ 1.22; // floatval traforma da alfabetico a numerico
							$CostShippingAmount=floatval($order->CostShippingAmount) / 1.22;
						} else {
							$CostPaymentAmount=floatval($order->CostPaymentAmount);
							$CostShippingAmount=floatval($order->CostShippingAmount);
						}

						// registro testata ordine
						$tesbro['destin']=chunk_split ($order->CustomerShippingDestin,44);$tesbro['ref_ecommerce_id_order']=$order->Numbering;$tesbro['tipdoc']='VOW';$tesbro['seziva']=$order->SezIva;$tesbro['print_total']='1';$tesbro['datemi']=$order->DateOrder;$tesbro['numdoc']=$numdoc;$tesbro['datfat']='0000-00-00';$tesbro['clfoco']=$clfoco;$tesbro['pagame']=$idpagame;$tesbro['listin']=$order->PriceListNum;$tesbro['spediz']=$order->Carrier;$tesbro['traspo']=$CostShippingAmount;$tesbro['speban']=$CostPaymentAmount;$tesbro['caumag']='1';$tesbro['expense_vat']=$admin_aziend['preeminent_vat'];$tesbro['initra']=$order->DateOrder;$tesbro['status']='ONLINE-SHOP';$tesbro['adminid']=$admin_aziend['adminid'];
						$id_tesbro=tesbroInsert($tesbro);

						// Gestione righi ordine
						foreach($xml->Documents->Document[$countDocument]->Rows->children() as $orderrow) { // carico le righe dell'ordine
              if ($orderrow->Type <> "discount"){
                // controllo se esiste l'articolo in GAzie
                $ckart = gaz_dbi_get_row($gTables['artico'], "ref_ecommerce_id_product", $orderrow->Id);
                if ($ckart){
                  $codart=$ckart['codice']; // se esiste ne prendo il codice come $codart
                  $descri=$ckart['descri'].$orderrow->AddDescription;// se esiste ne prendo descri e ci aggiungo una eventuale descrizione aggiuntiva
                }
                if (!$ckart){ // se non esiste creo un nuovo articolo su gazie
                  if ($orderrow->Stock>0){
                    $good_or_service=0;//come servizio, non deve movimentare il magazzino
                  } else {
                    $good_or_service=1; //come merce, movimenta il magazzino
                  }
                  if ($orderrow->VatAli==""){ // se il sito non ha mandato l'aliquota IVA dell'articolo di GAzie ci metto quella che deve mandare come base aziendale per le spese
                    $orderrow->VatCode=$order->CostVatCode;
                    $orderrow->VatAli=$order->CostVatAli;
                  }

                  if ($orderrow->VatCode<1){ // se il sito non ha mandato il codice iva di GAzie cerco di ricavarlo dalla tabella aliiva
                    $vat = gaz_dbi_get_row($gTables['aliiva'], "aliquo", $orderrow->VatAli, " AND tipiva = 'I'");
                    $codvat=$vat['codice'];
                    $aliiva=$vat['aliquo'];
                  } else {
                    $codvat=$orderrow->VatCode;
                    $aliiva=$orderrow->VatAli;
                  }
                  if ($order->PricesIncludeVat=="true" AND floatval($orderrow->Price) == 0){ // se l'e-commerce include l'iva e non ha mandato il prezzo imponibile, scorporo l'iva dal prezzo dell'articolo
                    $div=floatval("1.".$aliiva);
                    $Price=floatval($orderrow->PriceVATincl) / $div;
                  } else {// se l'ecommerce non iclude l'iva uso il prezzo imponibile
                    $Price=floatval($orderrow->Price);
                  }

                  $id_artico_group="";
                  $arrayvar="";
                  if ($orderrow->ParentId > 0 OR $orderrow->Type == "variant" ){ // se è una variante

                    // controllo se esiste il suo artico_group/padre in GAzie
                    unset($parent);
                    $parent = gaz_dbi_get_row($gTables['artico_group'], "ref_ecommerce_id_main_product", $orderrow->ParentId);// trovo il padre in GAzie
                    if ($parent){ // se esiste il padre
                      $id_artico_group=$parent['id_artico_group']; // imposto il riferimento al padre
                    } else {// se non esiste lo devo creare con i pochi dati che ho
                      $parent['descri']=$orderrow->Description;
                      gaz_dbi_query("INSERT INTO " . $gTables['artico_group'] . "(descri,large_descri,image,web_url,ref_ecommerce_id_main_product,web_public,depli_public,adminid) VALUES ('" . addslashes($parent['descri']) . "', '" . htmlspecialchars_decode ($parent['descri']). "', '', '', '". $orderrow->ParentId . "', '1', '1', '". $admin_aziend['adminid'] ."')");
                      $id_artico_group=gaz_dbi_last_id(); // imposto il riferimento al padre
                    }

                    if (strlen($orderrow->Description)<2){ // se non c'è la descrizione della variante
                      $orderrow->Description=$parent['descri']."-".$orderrow->Characteristic;// ci metto quella del padre accodandoci la variante
                    }

                    // creo un json array per la variante
                    $arrayvar= array("var_id" => floatval($orderrow->CharacteristicId), "var_name" => strval($orderrow->Characteristic));
                    $arrayvar = json_encode ($arrayvar);

                  }

                  // se l'e-commerce non ha inviato un codice me lo creo
                  if (strlen($orderrow->Code)<1){
                    $orderrow->Code = substr($orderrow->Description,0,10)."-".substr($orderrow->Id,-4);
                  }

                  // ricongiungo la categoria dell'e-commerce con quella di GAzie, se esiste
                  $category="";
                  if (intval($orderrow->Category)>0){
                    $cat = gaz_dbi_get_row($gTables['catmer'], "ref_ecommerce_id_category", addslashes (substr($orderrow->Category,0,15)));// controllo se esiste in GAzie
                    if ($cat){
                      $category=$cat['codice'];
                    }
                  }
                  // se non esiste la categoria in GAzie, la creo
                  if ($category == 0 OR $category == ""){
                    $ultimo_codice=array();
                    $rs_ultimo_codice = gaz_dbi_dyn_query("*", $gTables['catmer'], 1 ,'codice desc',0,1);
                    $ultimo_codice = gaz_dbi_fetch_array($rs_ultimo_codice);
                    $cat['codice'] = $ultimo_codice['codice']+1;
                    $cat['ref_ecommerce_id_category'] = $orderrow->Category;
                    $cat['descri'] = $orderrow->ProductCategory;
                    gaz_dbi_table_insert('catmer',$cat);
                    // assegno l'id categoria al prossimo insert artico
                    $category=$cat['codice'];
                  }

                  // prima di inserire il nuovo articolo controllo se il suo codice è stato già usato
                  unset($usato);
                  $usato = gaz_dbi_get_row($gTables['artico'], "codice", $orderrow->Code);// controllo se il codice è già stato usato in GAzie
                  if ($usato){ // se il codice è già in uso lo modifico accodandoci l'ID
                    $orderrow->Code=substr($orderrow->Code,0,10)."-".substr($orderrow->Id,0,4);
                  }

                  gaz_dbi_query("INSERT INTO " . $gTables['artico'] . "(peso_specifico,web_mu,web_multiplier,ecomm_option_attribute,id_artico_group,codice,descri,ref_ecommerce_id_product,good_or_service,unimis,catmer,preve2,web_price,web_public,aliiva,codcon,adminid) VALUES ('". $orderrow->ProductWeight ."', '". $orderrow->MeasureUnit ."', '1', '". $arrayvar ."', '". $id_artico_group ."', '". substr($orderrow->Code,0,15) ."', '". addslashes($orderrow->Description) ."', '". $orderrow->Id ."', '". $good_or_service ."', '" . $orderrow->MeasureUnit . "', '" .$category . "', '". $Price ."', '". $orderrow->Price ."', '1', '".$codvat."', '420000006', '" . $admin_aziend['adminid'] . "')");
                  $codart= substr($orderrow->Code,0,15);// dopo averlo creato ne prendo il codice come $codart
                  $descri= $orderrow->Description.$orderrow->AddDescription; //prendo anche la descrizione e ci aggiungo una eventuale descrizione aggiuntiva

                } else { // se esiste l'articolo in GAzie uso comunque il prezzo dell'e-commerce
                  $codvat=gaz_dbi_get_row($gTables['artico'], "codice", $codart)['aliiva'];
                  $aliiva=$orderrow->VatAli;
                  if ($order->PricesIncludeVat=="true" AND floatval($orderrow->Price) == 0){ // se l'e-commerce include l'iva e non ha mandato il prezzo imponibile, scorporo l'iva dal prezzo dell'articolo
                    $div=floatval("1.".$aliiva);
                    $Price=floatval($orderrow->PriceVATincl) / $div;
                  } else {// se l'ecommerce non iclude l'iva uso il prezzo imponibile
                    $Price=floatval($orderrow->Price);
                  }
                }

                // salvo rigo su database tabella rigbro
                $rigbro['id_tes']=intval($id_tesbro);$rigbro['tiprig']=0;$rigbro['codart']=$codart;$rigbro['descri']=addslashes($descri);$rigbro['unimis']=$orderrow->MeasureUnit;$rigbro['quanti']=$orderrow->Qty;$rigbro['prelis']=$Price;$rigbro['sconto']=$percdisc;$rigbro['codvat']=$codvat;$rigbro['codric']='420000006';$rigbro['pervat']=$aliiva;$rigbro['status']='ONLINE-SHOP';
                rigbroInsert($rigbro);
              }else{
                // salvo rigo SCONTO su database tabella rigbro
                $vat = gaz_dbi_get_row($gTables['aliiva'], "aliquo", $orderrow->VatAli, " AND tipiva = 'I'");
                $codvat=$vat['codice'];
                $aliiva=$vat['aliquo'];
                $rigbro['id_tes']=intval($id_tesbro);$rigbro['tiprig']=0;$rigbro['codart']='';$rigbro['descri']=addslashes($orderrow->Description);$rigbro['unimis']='n';$rigbro['quanti']=$orderrow->Qty;$rigbro['prelis']= -$orderrow->Price;$rigbro['sconto']='';$rigbro['codvat']=$codvat;$rigbro['codric']='420000006';$rigbro['pervat']=$aliiva;$rigbro['status']='ONLINE-SHOP';
                rigbroInsert($rigbro);
              }
						}
            if (strlen($order->CustomerNote)>3){// se l'ecommerce ha inviato delle note all'ordine, le accodo ai righi come rigo descrittivo
              $rigbro['id_tes']=intval($id_tesbro);$rigbro['tiprig']=2;$rigbro['codart']='';$rigbro['descri']=addslashes(substr($order->CustomerNote, 0, 1000));$rigbro['unimis']='';$rigbro['quanti']=0;$rigbro['prelis']=0;$rigbro['sconto']=0;$rigbro['codvat']=0;$rigbro['codric']=0;$rigbro['pervat']=0;$rigbro['status']='ONLINE-SHOP';
              rigbroInsert($rigbro);
            }
						$count++;//aggiorno contatore nuovi ordini
						$countDocument++;//aggiorno contatore Document

					} else {
						$countDocument++;//aggiorno contatore Document
					}
					$numdoc++; //incremento il numero d'ordine GAzie
				}
			}
		} else { // IL FILE INTERFACCIA NON ESISTE > chiudo la connessione ftp
			if (!is_array($headers)){
            $rawres['title'] = "Impossibile scaricare gli ordini. Controllare le impostazioni nel modulo shop-synchronize.";
            $rawres['button'] = 'Avviso eCommerce';
            $rawres['label'] = "Codice errore = Impostazioni mancanti o errate";
            $rawres['link'] = '';
            $rawres['style'] = 'danger';
			}elseif (intval(substr($headers[0], 9, 3))==0) {
				$rawres['title'] = "Controllare la connessione internet, la presenza dei file di intefaccia e le impostazioni ftp: impossibile scaricare gli ordini";
				$rawres['button'] = 'Avviso eCommerce';
				$rawres['label'] = "Codice errore = ".intval(substr($headers[0], 9, 3));
				$rawres['link'] = '';
				$rawres['style'] = 'danger';
			}
		}
		if ($count>0){
            $t=($count==1)?"È arrivato ". $count ." ordine":"Sono arrivati ". $count ." ordini";
            $b=($count==1)?"Nuovo ordine":$count ." nuovi ordini";
            $rawres['title'] = $t." dall'e-commerce";
            $rawres['button'] = $b;
            $rawres['label'] = 'Acquisizione ordini';
            $rawres['link'] = '../vendit/report_broven.php?auxil=VOW';
            $rawres['style'] = 'warning';
		}
		if (isset($rawres)){
      $_SESSION['menu_alerts']['shop-synchronize']=$rawres;
      $this->rawres=$rawres;
    }
	}
}
