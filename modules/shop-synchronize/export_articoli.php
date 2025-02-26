<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  SHOP SYNCHRONIZE è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2021 - Antonio Germani, Massignano (AP)
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
*/
/* Antonio Germani - ESPORTAZIONE MANUALE (update e insert) DEGLI ARTICOLI DA GAZIE ALL'E-COMMERCE -  GLI ARTICOLI già esistenti NELL'E-COMMERCE saranno aggiornati ALTRIMENTI, se richiesto con l'apposita spunta verranno iseriti solo se articoli semplici, se non richiesto saranno ignorati. Varianti e gruppi/parent NON VERRANNO CONSIDERATI */

require("../../library/include/datlib.inc.php");
require ("../../modules/magazz/lib.function.php");
$admin_aziend = checkAdmin();
$gForm = new magazzForm;
$resserver = gaz_dbi_get_row($gTables['company_config'], "var", "server");
$ftp_host= $resserver['val'];
$resftp_path = gaz_dbi_get_row($gTables['company_config'], "var", "ftp_path");
$ftp_path_upload=$resftp_path['val'];
$resuser = gaz_dbi_get_row($gTables['company_config'], "var", "user");
$ftp_user = $resuser['val'];

$OSftp_pass = gaz_dbi_get_row($gTables['company_config'], "var", "pass")['val'];// vecchio sistema di password non criptata
$OSaccpass_res = gaz_dbi_get_row($gTables['company_config'], "var", "accpass");// vecchio sistema di password non criptata
$OSaccpass=(isset($OSaccpass_res['val']))?$OSaccpass_res['val']:'';
$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'pass'");
$rdec=gaz_dbi_fetch_row($rsdec);
$rdec[0]=$rdec[0]??'';
$ftp_pass=$rdec?htmlspecialchars_decode($rdec[0]):'';
$ftp_pass=(strlen($ftp_pass)>0)?$ftp_pass:$OSftp_pass; // se la password decriptata non ha dato risultati provo a vedere se c'è ancora una password non criptata
$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'accpass'");
$rdec=gaz_dbi_fetch_row($rsdec);
$rdec[0]=$rdec[0]??'';
$accpass=$rdec?htmlspecialchars_decode($rdec[0]):'';
if(strlen($accpass)>0){
  // ho la password criptata e la uso
}elseif(strlen($OSaccpass)>0){
  $accpass=$OSaccpass;// // uso la vecchia password semplice
}else{
  // non ho una password, non posso continuare
  ?>
  <script>
  alert("<?php echo "Controllare impostazioni FTP (password) export articoli"; ?>");
  location.replace("./synchronize.php");
  </script>
  <?php
}

//Carico tutte le lingue del gestionale
$langs=gaz_dbi_fetch_all(gaz_dbi_dyn_query("*",$gTables['languages'],'lang_id > 1','lang_id'));

$respath = gaz_dbi_get_row($gTables['company_config'], "var", "path");
$web_site_path= $respath['val'];
$respath = gaz_dbi_get_row($gTables['company_config'], "var", "img_limit");
$img_limit= (isset($respath['val']))?$respath['val']:0;
$test = gaz_dbi_query("SHOW COLUMNS FROM `" . $gTables['admin'] . "` LIKE 'enterprise_id'");
$exists = (gaz_dbi_num_rows($test)) ? TRUE : FALSE;
if ($exists) {
    $c_e = 'enterprise_id';
} else {
    $c_e = 'company_id';
}
$admin_aziend = gaz_dbi_get_row($gTables['admin'] . ' LEFT JOIN ' . $gTables['aziend'] . ' ON ' . $gTables['admin'] . '.' . $c_e . '= ' . $gTables['aziend'] . '.codice', "user_name", $_SESSION["user_name"]);
$path = gaz_dbi_get_row($gTables['company_config'], 'var', 'path');
$urlinterf = $path['val']."articoli-gazie.php";// nome del file interfaccia presente nella root dell'e-commerce. Per evitare intrusioni indesiderate Il file dovrà gestire anche una password ($accpass).
// il percorso per raggiungere questo file va impostato in configurazione avanzata azienda alla voce "Website root directory

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;

if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}
if (isset($_POST['Return'])) {
        header("Location: " . $_POST['ritorno']);
        exit;
    }

if (isset($_POST['conferma'])) { // se confermato

	if (gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')['val']=="SI"){

		// SFTP login with private key and password
		$ftp_port = gaz_dbi_get_row($gTables['company_config'], "var", "port")['val'];
		$ftp_key = gaz_dbi_get_row($gTables['company_config'], "var", "chiave")['val'];
    $rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'psw_chiave'");
    $rdec=gaz_dbi_fetch_row($rsdec);
    $rdec[0]=$rdec[0]??'';
    $ftp_keypass=$rdec?htmlspecialchars_decode($rdec[0]):'';

		if (gaz_dbi_get_row($gTables['company_config'], "var", "keypass")['val']=="key"){ // SFTP log-in con KEY    
			
      try {
        $key = PublicKeyLoader::load(file_get_contents('../../data/files/'.$admin_aziend['codice'].'/secret_key/'. $ftp_key ),$ftp_keypass);
      }
      catch(Exception $e) {
        ?>
        <script>
        alert("<?php echo 'SFTP Error Message: ' .$e->getMessage();
        echo " Controlla percorso e password della chiave pubblica. Se stai usando un server locale, controlla anche che sia installato e abilitato SSH"; ?>");
        location.replace("./synchronize.php");
        </script>
        <?php
        exit;
      }


			$sftp = new SFTP($ftp_host, $ftp_port);
			if (!$sftp->login($ftp_user, $key)) {
				// non si connette: key LOG-IN FALSE
				?>
				<script>
				alert("<?php echo "Mancata connessione Sftp con file chiave segreta: impossibile scaricare gli ordini dall\'e-commerce"; ?>");
				location.replace("<?php echo $_POST['ritorno']; ?>");
				</script>
				<?php
			} else {
				?>
				<!--
				<div class="alert alert-success text-center" >
				<strong>ok</strong> Connessione SFTP con chiave riuscita.
				</div>
				-->
				<?php
			}
		} else { // SFTP log-in con password

			$sftp = new SFTP($ftp_host, $ftp_port);
			if (!$sftp->login($ftp_user, $ftp_pass)) {
				// non si connette: password LOG-IN FALSE
				?>
				<script>
				alert("<?php echo "Mancata connessione Sftp con password: impossibile sincronizzare l\'e-commerce"; ?>");
				location.replace("<?php echo $_POST['ritorno']; ?>");
				</script>
				<?php
			} else {
				?>
				<div class="alert alert-success text-center" >
				<strong>ok</strong> Connessione SFTP con password riuscita.
				</div>
				<?php
			}
		}
	} else {
		// imposto la connessione al server
    try {
    		$conn_id = ftp_connect($ftp_host);
    } catch (Error $ex) { // Error is the base class for all internal PHP error exceptions.
      ?>
			<script>
			alert("<?php echo "Errore: connessione FTP a " . $ftp_host . ": ". $ex->getMessage() ." Controllare se l'estensione FTP è abilitata in Php!"; ?>");
			location.replace("<?php echo $_POST['ritorno']; ?>");
			</script>
			<?php
    }

		// controllo se la connessione è OK...
		if ((!$conn_id)){
			  // ERRORE chiudo la connessione FTP
			header("Location: " . "../../modules/shop-synchronize/export_articoli.php?success=8");
			exit;
		}else{// se ho avuto la connessione al server

      // effettuo login con user e pass
      $mylogin = ftp_login($conn_id, $ftp_user, $ftp_pass);
    }

		if ((!$mylogin)){
       // ERRORE chiudo la connessione FTP
			ftp_quit($conn_id);
			header("Location: " . "../../modules/shop-synchronize/export_articoli.php?success=7");
			exit;
		}
		//FTP turn passive mode on
		ftp_pasv($conn_id, true);
	}
	if ($_GET['img']=="updimg"){ // se si devono aggiornare le immagini
		if (!@ftp_mkdir($conn_id, $ftp_path_upload."images")){ // se non c'è la cartella images la creo
			// get contents of the current directory
			$files = ftp_nlist($conn_id, $ftp_path_upload."images");
      if (count($files)>0){
        foreach ($files as $key => $item) {
          if (in_array($item, array($ftp_path_upload.'images/.', $ftp_path_upload.'images/..'))) {
             unset($files[$key]);
          }
        }
        foreach ($files as $file){ // se c'era, cancello i files del precedente aggiornamento
          if (@ftp_delete($conn_id, $file)){
          } else {
            header("Location: " . "../../modules/shop-synchronize/export_articoli.php?success=6");
            exit;
          }
        }
      }
		}
	}

		// creo il file xml
	$xml_output = '<?xml version="1.0" encoding="UTF-8"?>
	<GAzieDocuments AppVersion="1" Creator="Antonio Germani Copyright" CreatorUrl="https://www.programmisitiweb.lacasettabio.it">';
	$xml_output .= "\n<Products>\n";
	for ($ord=0 ; $ord<=$_POST['num_products']; $ord++){// ciclo gli articoli e creo il file xml
		if (isset($_POST['download'.$ord])){ // se selezionato
			$barcode="";
      $xml_output .= "\t<Product>\n";
			$xml_output .= "\t<Id>".$_POST['ref_ecommerce_id_product'.$ord]."</Id>\n";
			$xml_output .= "\t<ParentId>".$_POST['ref_ecommerce_id_main_product'.$ord]."</ParentId>\n";
			$xml_output .= "\t<ToDo>".$_POST['ToDo'.$ord]."</ToDo>\n";
			if (intval($_POST['ref_ecommerce_id_main_product'.$ord])>0){
				if ($_POST['ref_ecommerce_id_product'.$ord]<1){
					$xml_output .= "\t<Type>parent</Type>\n";
				} else {
					$xml_output .= "\t<Type>variant</Type>\n";
					$artic = gaz_dbi_get_row($gTables['artico'],"codice",$_POST['codice'.$ord]);// prendo gli ulteriori dati da passare nell xml
          $barcode=$artic['barcode'];
					if (json_decode($_POST['ecomm_option_attribute'.$ord]) != null){ // se esiste un json per attributo della variante dell'e-commerce
						$var = json_decode($_POST['ecomm_option_attribute'.$ord],true);
						$var_name=(isset($var['var_name']))?$var['var_name']:'null';
						$xml_output .= "\t<Characteristic>".$var_name."</Characteristic>\n";
						$xml_output .= "\t<CharacteristicId>".$var['var_id']."</CharacteristicId>\n";
					}
				}
			} else {//se è un prodotto semplice
				$xml_output .= "\t<Type>product</Type>\n";
				$artic = gaz_dbi_get_row($gTables['artico'],"codice",$_POST['codice'.$ord]);// prendo gli ulteriori dati da passare nell xml
        $barcode=$artic['barcode'];
			}

			if (isset($_POST['catmer'.$ord]) && intval($_POST['catmer'.$ord])>0){// se GAzie ha una categoria
				$ecomm_catmer = gaz_dbi_get_row($gTables['catmer'],"codice",intval($_POST['catmer'.$ord]))['ref_ecommerce_id_category'];
				$xml_output .= "\t<ProductCategory>".$ecomm_catmer."</ProductCategory>\n";
			}elseif (intval($_POST['ref_ecommerce_id_main_product'.$ord])>0 && $_POST['ref_ecommerce_id_product'.$ord]<1){// se non ce l'ha ed è un parent ci metto quella di una variante
				$parent_catmer_res = gaz_dbi_get_row($gTables['artico'],"id_artico_group",intval($_POST['codice'.$ord]));
				$parent_catmer=(isset($parent_catmer_res))?$parent_catmer_res['catmer']:'';
				$ecomm_catmer = gaz_dbi_get_row($gTables['catmer'],"codice",intval( $parent_catmer))['ref_ecommerce_id_category'];
				$xml_output .= "\t<ProductCategory>".$ecomm_catmer."</ProductCategory>\n";
				if (isset($parent_catmer_res['aliiva'])){
					$aliquo=gaz_dbi_get_row($gTables['aliiva'], "codice", intval($parent_catmer_res['aliiva']));
					if (isset($aliquo['aliquo'])){
						$xml_output .= "\t<VAT>".$aliquo['aliquo']."</VAT>\n";
					}
				}
			}
			$xml_output .= "\t<Code>".$_POST['codice'.$ord]."</Code>\n";
			$xml_output .= "\t<BarCode>".$barcode."</BarCode>\n";
      $pes_spec=(isset($artic['peso_specifico']))?$artic['peso_specifico']:'';
      $xml_output .= "\t<Peso>".$pes_spec."</Peso>\n";
      $larg_mm=(isset($artic['larghezza']))?$artic['larghezza']:'';
      $lung_mm=(isset($artic['lunghezza']))?$artic['lunghezza']:'';
      $spess_mm=(isset($artic['spessore']))?$artic['spessore']:'';
			$xml_output .= "\t<Largmm>".$larg_mm."</Largmm>\n";
      $xml_output .= "\t<Lungmm>".$lung_mm."</Lungmm>\n";
      $xml_output .= "\t<Spessmm>".$spess_mm."</Spessmm>\n";
			if ($_GET['qta']=="updqty" || $_GET['todo']!=="insert"){
				$xml_output .= "\t<AvailableQty>".$_POST['quanti'.$ord]."</AvailableQty>\n";
			}
			if (($_GET['prezzo']=="updprice" || $_GET['todo']=="insert") AND $_POST['web_price'.$ord]>0){
				// Calcolo il prezzo IVA compresa
				$aliquo=gaz_dbi_get_row($gTables['aliiva'], "codice", intval($_POST['aliiva'.$ord]))['aliquo'];
				$web_price_vat_incl= $_POST['web_price'.$ord] +(($_POST['web_price'.$ord]*$aliquo)/100);
				$web_price_vat_incl=number_format($web_price_vat_incl, $admin_aziend['decimal_price'], '.', '');
				$xml_output .= "\t<Price>".$_POST['web_price'.$ord]."</Price>\n";
				$xml_output .= "\t<PriceVATincl>".$web_price_vat_incl."</PriceVATincl>\n";
				$xml_output .= "\t<VAT>".$aliquo."</VAT>\n";
			}
      $xml_output .= "\t<WebUrl>".$artic['web_url']."</WebUrl>\n";
      if (($_GET['name']=="updnam" || $_GET['todo']=="insert") AND strlen($_POST['descri'.$ord])>0){
        $xml_output .= "\t<Name>".$_POST['descri'.$ord]."</Name>\n";
      }
      if (($_GET['descri']=="upddes" || $_GET['todo']=="insert") AND (isset($_POST['body_text'.$ord]) && strlen($_POST['body_text'.$ord])>0)){
        $xml_output .= "\t<Description>".preg_replace('/[\x00-\x1f]/','',htmlspecialchars($_POST['body_text'.$ord]))."</Description>\n";
      }

      $xml_output .= "\t<Languages>\n";
      foreach($langs as $lang){// carico le traduzioni dal DB e le metto nelle rispettive lingue
        $xml_output .= "\t\t<Lang>\n";
        $xml_output .= "\t\t\t<lang_code>".$lang['lang_code']."</lang_code>\n";
        $bodytextlang = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", 'artico', " AND code_ref = '".substr($_POST['codice'.$ord],0,32)."' AND lang_id = ".$lang['lang_id']);
        $lang_descri = (isset($bodytextlang['descri']))?$bodytextlang['descri']:$_POST['descri'.$ord];
        $lang_bodytext = (isset($bodytextlang['body_text']))?$bodytextlang['body_text']:filter_var($_POST['body_text'.$ord], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $obj = (isset($bodytextlang['custom_field']))?json_decode($bodytextlang['custom_field']):'';
        $lang_web_url = (isset($obj->web_url))?$obj->web_url:$artic['web_url'];
        // invio i testi multilingua
        if (($_GET['name']=="updnam" || $_GET['todo']=="insert") AND strlen($_POST['descri'.$ord])>0){
          $lang_descri = (isset($bodytextlang['descri']))?$bodytextlang['descri']:$_POST['descri'.$ord];
          $xml_output .= "\t\t\t<Name>".$lang_descri."</Name>\n";
        }
        if (($_GET['descri']=="upddes" || $_GET['todo']=="insert") AND (isset($_POST['body_text'.$ord]) && strlen($_POST['body_text'.$ord])>0)){
          $lang_bodytext = (isset($bodytextlang['body_text']))?$bodytextlang['body_text']:filter_var($_POST['body_text'.$ord], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
          $xml_output .= "\t\t\t<Description>".preg_replace('/[\x00-\x1f]/','',htmlspecialchars($lang_bodytext, ENT_QUOTES, 'UTF-8'))."</Description>\n";
        }

        $xml_output .= "\t\t\t<WebUrl>".$lang_web_url."</WebUrl>\n";
        $xml_output .= "\t\t</Lang>\n";
      }
      $xml_output .= "\t</Languages>\n";

			$xml_output .= "\t<WebPublish>".$_POST['web_public'.$ord]."</WebPublish>\n";// 1=attivo su web; 2=attivo e prestabilito; 3=attivo e pubblicato in home; 4=attivo, in home e prestabilito; 5=disattivato su web"
			if (isset($_POST['imgurl'.$ord]) && ($_GET['img']=="updimg" || $_GET['todo']=="insert") && (strlen($_POST['imgurl'.$ord])>0)){ // se è da aggiornare e c'è un'immagine HQ
				if (@ftp_put($conn_id, $ftp_path_upload."images/".$_POST['imgname'.$ord], $_POST['imgurl'.$ord],  FTP_BINARY)){
					// ho scritto l'immagine web HQ nella cartella e-commerce
					ftp_chmod($conn_id, 0664, $ftp_path_upload."images/".$_POST['imgname'.$ord]);// fornisco i permessi necessari all'immagine
					$xml_output .= "\t<ImgUrl>".$web_site_path."images/".$_POST['imgname'.$ord]."</ImgUrl>\n"; // ne scrivo l'url nel file xml
				} else {
					// ERRORE chiudo la connessione FTP
					ftp_quit($conn_id);die;
					header("Location: " . "../../modules/shop-synchronize/export_articoli.php?success=5");
					exit;
				}
			}elseif (isset($_POST['imgblob'.$ord]) && ($_GET['img']=="updimg" || $_GET['todo']=="insert") && (strlen($_POST['imgblob'.$ord])>0)){// se è da aggiornare e c'è un'immagine blob
				file_put_contents("../../data/files/tmp/img.jpg", base64_decode($_POST['imgblob'.$ord])); // salvo immagine nella cartella temporanea

				if (@ftp_put($conn_id, $ftp_path_upload."images/".str_replace(' ', '_', $_POST['codice'.$ord]).".jpg", "../../data/files/tmp/img.jpg",  FTP_BINARY)){
					// scrivo l'immagine web blob nella cartella images dell'e-commerce
					ftp_chmod($conn_id, 0664, $ftp_path_upload."images/".str_replace(' ', '_', $_POST['codice'.$ord]).".jpg");// fornisco i permessi necessari all'immagine
					$xml_output .= "\t<ImgUrl>".$web_site_path."images/".str_replace(' ', '_', $_POST['codice'.$ord]).".jpg</ImgUrl>\n"; // ne scrivo l'url nel file xml
				} else {
					// ERRORE chiudo la connessione FTP
					ftp_quit($conn_id);
					header("Location: " . "../../modules/shop-synchronize/export_articoli.php?success=5");
					exit;
				}
			}

			$xml_output .= "\t</Product>\n";
		}
	}
	$xml_output .="</Products>\n</GAzieDocuments>";
	$xmlFileP = "prodotti.xml";
	$xmlHandle = fopen($xmlFileP, "w");
	fwrite($xmlHandle, $xml_output);
	fclose($xmlHandle);

	// *** creazione file xml delle categorie ***
	// carico in $categories le categorie che sono presenti in GAzie
	$categories = gaz_dbi_query ("SELECT * FROM ".$gTables['catmer']." WHERE top > '0' ORDER BY codice");
	// creo il file xml
	$xml_output = '<?xml version="1.0" encoding="UTF-8"?>
	<GAzieDocuments AppVersion="1" Creator="Antonio Germani Copyright" CreatorUrl="https://www.programmisitiweb.lacasettabio.it">';
	$xml_output .= "\n<Categories>\n";
	while ($cat = gaz_dbi_fetch_array($categories)){ // le ciclo
		$xml_output .= "\t<Category>\n";
		$xml_output .= "\t<Codice>".$cat['codice']."</Codice>\n";
		$xml_output .= "\t<Descri>".htmlspecialchars($cat['descri'])."</Descri>\n";
		$xml_output .= "\t<LargeDescri>".htmlspecialchars($cat['large_descri'])."</LargeDescri>\n";
		$xml_output .= "\t<WebUrl>".$cat['web_url']."</WebUrl>\n";
		$xml_output .= "\t<RefIdCat>".$cat['ref_ecommerce_id_category']."</RefIdCat>\n";
		$xml_output .= "\t<Top>".$cat['top']."</Top>\n";
		$xml_output .= "\t</Category>\n";
	}
	$xml_output .="</Categories>\n</GAzieDocuments>";
	$xmlFileC = "categorie.xml";
	$xmlHandle = fopen($xmlFileC, "w");
	fwrite($xmlHandle, $xml_output);
	fclose($xmlHandle);

	if (gaz_dbi_get_row($gTables['company_config'], 'var', 'Sftp')['val']=="SI"){

		if ($sftp->put($ftp_path_upload."prodotti.xml", $xmlFileP, SFTP::SOURCE_LOCAL_FILE)){
			$sftp->put($ftp_path_upload."categorie.xml", $xmlFileC, SFTP::SOURCE_LOCAL_FILE);
			$sftp->disconnect();
			?>
			<!--
			<div class="alert alert-success text-center" >
			<strong>ok</strong> il file xml è stato trasferito al sito web tramite SFTP.
			</div>
			-->
			<?php
		}else {
			// chiudo la connessione FTP
			$sftp->disconnect();
			?>
			<script>
			alert("<?php echo "Errore di upload del file xml tramite SFTP"; ?>");
			location.replace("<?php echo $_POST['ritorno']; ?>");
			</script>
			<?php
		}
	} else { // FTP semplice
		// upload file xml
		if (ftp_put($conn_id, $ftp_path_upload."prodotti.xml", $xmlFileP, FTP_ASCII)){
			ftp_put($conn_id, $ftp_path_upload."categorie.xml", $xmlFileC, FTP_ASCII);
			// è OK
			//echo "xml trasferito";
		} else{
			// ERRORE chiudo la connessione FTP
			ftp_quit($conn_id);
			header("Location: " . "../../modules/shop-synchronize/export_articoli.php?success=4");
			exit;
		}
	}
	$access=base64_encode($accpass);

	// avvio il file di interfaccia presente nel sito web remoto

	$file = @fopen ($urlinterf.'?access='.$access, "r");
	if ( $file ){ // controllo se il file esiste o mi dà accesso
		while (!feof($file)) { // scorro il file generato dall'interfaccia durante la sua eleborazione
			$line = fgets($file);
			$ln=explode("-",$line);
			if (isset($ln) && count($ln)>1 && strlen($ln[3])>0){ // Se l'e-commerce ha restituito l'ID riferito ad un articolo
				// vado a modificare il riferimento id e-commerce nell'articolo di GAzie
				gaz_dbi_put_row($gTables['artico'], "codice", rtrim($ln[3],"<br>\n"), "ref_ecommerce_id_product", $ln[1]); // tolgo <br>\n perché viene aggiunto dall'ecommerce
				gaz_dbi_put_row($gTables['artico'], "codice", rtrim($ln[3],"<br>\n"), "web_public", "5");// lo imposto come disattivato
			}
		}

	} else {// ho avuto problemi
		$headers = get_headers ($urlinterf.'?access='.$access);// controllo l'header
		if(intval(substr($headers[0], 9, 3))==400){
			// chiudo la connessione FTP
			ftp_quit($conn_id);
			header("Location: " . "../../modules/shop-synchronize/export_articoli.php?success=3");
			exit;
		}else{
			// chiudo la connessione FTP
			ftp_quit($conn_id);
			header("Location: " . "../../modules/shop-synchronize/export_articoli.php?success=2");
			exit;
		}

	}
	// chiudo la connessione FTP
	ftp_quit($conn_id);
	header("Location: " . "../../modules/shop-synchronize/export_articoli.php?success=1");
    exit;
} else {
	require('../../library/include/header.php');
	$script_transl = HeadMain();
}

if (!isset($_GET['success'])){
// Apro il form per la selezione degli articoli
		?>
	<script>
	function selectCheckbox() {
		var inputs = document.getElementsByTagName('input');
		var checkboxes = [];
		for (var i = 0; i < inputs.length; i++){
			var input = inputs[i];
			if (input.getAttribute('type') == 'checkbox'){
				checkboxes.push(input);
			}
		}
		return checkboxes;
	}
	function check(checks){
	  var checkboxes = selectCheckbox();
	  for(var i=0; i < checkboxes.length; i++){
		checkboxes[i].checked = checks.checked;
	  }
	}
	</script>
		<form method="POST" name="download" enctype="multipart/form-data">
			<input type="hidden" name="ritorno" value="<?php echo $_POST['ritorno'];?>" >
			<div class="container-fluid" style="max-width:90%;">
				<div class="row bg-primary" >
					<div class="col-sm-12" align="center"><h4>Esportazione di articoli da GAzie</h4>
						<p align="justify">Gli articoli selezionati per update verranno aggiornati nell'e-commerce se già esistenti, altrimenti verranno ignorati. </p>
						<p align="justify">Gli articoli selezionati per insert verranno inseriti nell'e-commerce. </p>
						<?php
						if ($_GET['img']=="updimg") {?>
							<b> Hai selezionato di trasferire le immagini: questa operazione potrebbe richiedere molti minuti di attesa!</b>
							<?php
						}
						?>
					</div>
				</div>
				<div class="row bg-info">
					<div class="col-sm-4">
						<input type="submit" name="Return"  value="Indietro">
					</div>
					<div class="col-sm-4" style="background-color:lightgreen;">
						<?php echo "E-commerce sincronizzato: " . $ftp_host;?>
					</div>
					<div class="col-sm-4" align="right">
						<!-- Trigger the modal with a button -->
						<button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#downloader">Aggiorna i prodotti nell'e-commerce</button>
						<!-- Modal content-->
						<div id="downloader" class="modal fade" role="dialog">
							<div class="modal-dialog modal-content">
								<div class="modal-header" align="left">
									<button type="button" class="close" data-dismiss="modal">&times;</button>
									<h4 class="modal-title">ATTENZIONE!</h4>
								</div>
								<div class="modal-body">
									<p>Stai per sincronizzare definitivamente i prodotti nell'e-commerce. <br>Questa operazione &egrave irreversibile. <br>Sei sicuro di volerlo fare?</p>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Annulla</button>
									<input type="submit" class="btn btn-danger pull-right" name="conferma"  value="Aggiorna l'e-commerce">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row bg-info" style="border-bottom: 1px solid;">
					<div class="col-sm-2">
						<h4>Progressivo</h4>
					</div>
					<div class="col-sm-2">
						<h4>Codice</h4>
					</div>
					<div class="col-sm-5">
						<h4>Nome</h4>
					</div>
					<div class="col-sm-2" align="right">
						<h4>Seleziona</h4>
					</div>
					<div class="col-sm-1">
						TUTTI <input class="select_all" type="checkbox" onClick="check(this)">
					</div>
				</div>
				<?php

				if (isset($_GET['todo'])){
				}else{
					$_GET['todo']="";
				}

				if ($_GET['todo']!=="insert"){
					$where="web_public >= '1'";
				}else{
					$where="web_public BETWEEN 1 AND 4";
				}
				// carico in $artico gli articoli che sono presenti in GAzie
				$artico = gaz_dbi_query ('SELECT ecomm_option_attribute, codice, catmer, web_price, descri, aliiva, ref_ecommerce_id_product, id_artico_group, web_public, image, good_or_service FROM '.$gTables['artico'].' WHERE '.$where.' ORDER BY codice');
				$n=0;
				while ($item = gaz_dbi_fetch_array($artico)){ // li ciclo
					$ref_ecommerce_id_main_product="";
					if ($item['id_artico_group']>0){
						$artico_group = gaz_dbi_query ('SELECT ref_ecommerce_id_main_product FROM '.$gTables['artico_group'].' WHERE id_artico_group = \''.$item['id_artico_group'].'\'');
						$item_group = gaz_dbi_fetch_array($artico_group);
						$ref_ecommerce_id_main_product = $item_group['ref_ecommerce_id_main_product'];
					}
          if ($item['good_or_service']==1){// se non movimenta il magazzino
            $avqty=NULL;
          }else{
            $avqty = 0;
            $ordinatic = $gForm->get_magazz_ordinati($item['codice'], "VOR");
            $mv = $gForm->getStockValue(false, $item['codice']);
            $magval = array_pop($mv);
            if (isset($magval['q_g'])){
              $avqty=$magval['q_g']-$ordinatic;
            }
            if ($avqty<0 or $avqty==""){
              $avqty="0";
            }
          }

					?>
					<div class="row bg-success" style="border-bottom: 1px solid;">
							<div class="col-sm-2">
								<?php echo $n;?>
							</div>
							<div class="col-sm-2">
								<?php echo $item['codice'];
								echo '<input type="hidden" name="codice'. $n .'" value="'. $item['codice'] . '">';
								?>
							</div>
							<div class="col-sm-6">
								<?php echo $item['descri'];
								echo '<input type="hidden" name="descri'. $n .'" value="'. $item['descri'] . '">';
								?>
							</div>
							<div class="col-sm-1">
								<?php
								if ($_GET['descri']=="upddes" || $_GET['todo']=="insert"){ // se devo aggiornare il body_text
									$body = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", "artico_". $item['codice']);
									if (isset($body['body_text'])){
										echo '<input type="hidden" name="body_text'. $n .'" value="'. preg_replace('/[\x00-\x1f]/','',htmlspecialchars($body['body_text'])) . '">';
									}
								}
								echo '<input type="hidden" name="web_public'. $n .'" value="'. $item['web_public'] . '">';
								echo '<input type="hidden" name="quanti'. $n .'" value="'. $avqty .'">';
								echo '<input type="hidden" name="aliiva'. $n .'" value="'. $item['aliiva'] .'">';
								echo '<input type="hidden" name="catmer'. $n .'" value="'. $item['catmer'] .'">';
								echo '<input type="hidden" name="web_price'. $n .'" value="'. $item['web_price'] .'">';
								echo '<input type="hidden" name="ref_ecommerce_id_main_product'. $n .'" value="'. $ref_ecommerce_id_main_product .'">';
								echo '<input type="hidden" name="ref_ecommerce_id_product'. $n .'" value="'. $item['ref_ecommerce_id_product'] .'">';
								if (intval($ref_ecommerce_id_main_product)==0 && $item['ref_ecommerce_id_product']==0 && $_GET['todo']=="insert"){// se è un articolo semplice e non esiste nell'e-commerce ed è stato selezionato l'inserimento
									echo '<input type="hidden" name="ToDo'. $n .'" value="insert">Insert';
								} else {
									echo '<input type="hidden" name="ToDo'. $n .'" value="update">Update';
								}
								echo '<input type="hidden" name="ecomm_option_attribute'. $n .'" value="'. htmlspecialchars($item['ecomm_option_attribute']) .'">';
								if ($_GET['img']=="updimg" || $_GET['todo']=="insert"){ // se devo aggiornare o inserire l'immagine ne trovo l'url di GAzie
									unset ($imgres);
									$imgres = gaz_dbi_get_row($gTables['files'], "table_name_ref", "artico", "AND id_ref ='1' AND item_ref = '". $item['codice']."'");
									if (isset($imgres['id_doc']) AND $imgres['id_doc']>0){ // se c'è un'immagine
										$imgurl=DATA_DIR."files/".$admin_aziend['company_id']."/images/". $imgres['id_doc'] . "." . $imgres['extension'];
										$imgblob="";echo ", Img HQ";
									} else {
										$imgurl="";
										$imgres['id_doc']="";
										$imgres['extension']="";
										$imgblob=$item['image'];echo ", Img blob";
									}
									echo '<input type="hidden" name="imgurl'. $n .'" value="'. $imgurl .'">';
									echo '<input type="hidden" name="imgname'. $n .'" value="'. $imgres['id_doc'] . "." . $imgres['extension'] .'">';
									echo '<input type="hidden" name="imgblob'. $n .'" value="'. base64_encode($imgblob) .'">';
								}
								?>
							</div>
							<div class="col-sm-1 check" align="right">
								<input class="single_checkbox" type="checkbox" name="download<?php echo $n; ?>" value="download">
								<input type="hidden" name="num_products" value="<?php echo $n; ?>">
							</div>
					</div>
				<?php
				$n++;
				}

				// carico in $parent i gruppi che sono presenti in GAzie
				$parent = gaz_dbi_query ('SELECT * FROM '.$gTables['artico_group'].' WHERE web_public = \'1\' ORDER BY id_artico_group');
        if (isset($parent) && $parent->num_rows>0){
          ?>
          <div class="row bg-warning" style="border-bottom: 1px solid;">
            <div class="col-sm-12">
              Articoli genitori/gruppi:
            </div>
          </div>
          <?php
        }
				while ($item = gaz_dbi_fetch_array($parent)){ // ciclo i PARENT / GRUPPI
          // carico in $parent i gruppi che sono presenti in GAzie
          $parent_variant = gaz_dbi_query ("SELECT * FROM ".$gTables['artico']." WHERE id_artico_group = '".$item['id_artico_group']."' ORDER BY codice ASC");
          $quanti=0;
          while ($itemvar = gaz_dbi_fetch_array($parent_variant)){ // ciclo le varianti di questo parent

            if ($itemvar['good_or_service']==1){// se non movimenta il magazzino
              $quanti=NULL;
            }else{
              $ordinatic = $gForm->get_magazz_ordinati($itemvar['codice'], "VOR");
              $mv = $gForm->getStockValue(false, $itemvar['codice']);
              $avqty= 0;
              $magval = array_pop($mv);
              if (isset($magval['q_g'])){
                $avqty=$magval['q_g']-$ordinatic;
              }
              if ($avqty<0 or $avqty==""){
                $avqty= 0;
              }
              $quanti += $avqty;
            }
          }

					?>
					<div class="row bg-warning" style="border-bottom: 1px solid;">
							<div class="col-sm-2">
								<?php echo $n;?>
							</div>
							<div class="col-sm-2">
								<?php echo $item['id_artico_group'];
								echo '<input type="hidden" name="codice'. $n .'" value="'. $item['id_artico_group'] . '">';
								?>
							</div>
							<div class="col-sm-6">
								<?php echo $item['descri'];
								echo '<input type="hidden" name="descri'. $n .'" value="'. $item['descri'] . '">';
								?>
							</div>
							<div class="col-sm-1">
								<?php

								echo '<input type="hidden" name="body_text'. $n .'" value="'. preg_replace('/[\x00-\x1f]/','',htmlspecialchars($item['large_descri'])) . '">';
								echo '<input type="hidden" name="ToDo'. $n .'" value="update">Update';// per i parent solo update!!!
								echo '<input type="hidden" name="web_public'. $n .'" value="'. $item['web_public'] . '">';
								echo '<input type="hidden" name="quanti'. $n .'" value="'.$quanti.'">';
								echo '<input type="hidden" name="aliiva'. $n .'" value="">';
								echo '<input type="hidden" name="web_price'. $n .'" value="">';
								echo '<input type="hidden" name="ref_ecommerce_id_main_product'. $n .'" value="'. $item['ref_ecommerce_id_main_product'] .'">';
								echo '<input type="hidden" name="ref_ecommerce_id_product'. $n .'" value="">';

								if ($_GET['img']=="updimg"){ // se devo aggiornare l'immagine cerco l'url di quella HQ High Quality in GAzie
                  unset ($imgres);
                  // NB: al momento i gruppi/parent non gestiscono le immagini HQ
                  /*  quando verrà gestitata bata decommentare la riga seguente e tutto funziona già
									$imgres = gaz_dbi_get_row($gTables['files'], "table_name_ref", "artico_group", "AND id_ref ='1' AND item_ref = '". $item['id_artico_group']."'");
									*/
                  // Si preferisce l'immagine HQ, in mancanza si invia la blob
									if (isset($imgres['id_doc']) AND $imgres['id_doc']>0){ // se c'è un'immagine High Quality
										$imgurl=DATA_DIR."files/".$admin_aziend['company_id']."/images/". $imgres['id_doc'] . "." . $imgres['extension'];
										$imgblob="";echo "Img HQ";
									} else {
										$imgurl="";
										$imgres['id_doc']="";
										$imgres['extension']="";
										$imgblob=$item['image'];echo "img blob";

									}
									echo '<input type="hidden" name="imgurl'. $n .'" value="'. $imgurl .'">';
									echo '<input type="hidden" name="imgname'. $n .'" value="'. $imgres['id_doc'] . "." . $imgres['extension'] .'">';
									echo '<input type="hidden" name="imgblob'. $n .'" value="'. base64_encode($imgblob) .'">';
								}
								?>
							</div>
							<div class="col-sm-1" align="right">
								<input type="checkbox" name="download<?php echo $n; ?>" value="download">
								<input type="hidden" name="num_products" value="<?php echo $n; ?>">
							</div>

					</div>
				<?php
				$n++;
				}

				?>
				<div class="row bg-info">
					<div class="col-sm-4">
						<input type="submit" name="Return"  value="Indietro">
					</div>
					<div class="col-sm-4" style="background-color:lightgreen;">
						<?php echo "E-commerce sincronizzato: " . $ftp_host;?>
					</div>
					<div class="col-sm-4" align="right">
						<!-- Trigger the modal with a button -->
						<button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#downloader">Aggiorna i prodotti nell'e-commerce</button>

					</div>
				</div>


			</div> <!-- container fluid -->
		</form>
<?php
} elseif ($_GET['success']==1){
	?>
	<div class="alert alert-success alert-dismissible">
		<a href="../../modules/shop-synchronize/synchronize.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>Fatto!</strong> Operazione conclusa con successo.
	</div>
<?php
} elseif ($_GET['success']==2){
	?>
	<div class="alert alert-danger alert-dismissible">
		<a href="../../modules/shop-synchronize/synchronize.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>ERRORE!</strong> Manca il file di interfaccia nell'e-commerce o non è stato possibile accedervi!.
	</div>
<?php
} elseif ($_GET['success']==3){
	?>
	<div class="alert alert-danger alert-dismissible">
		<a href="../../modules/shop-synchronize/synchronize.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>ERRORE!</strong> Il file di interfaccia nell'e-commerce non parte!.
	</div>
<?php
} elseif ($_GET['success']==4){
	?>
	<div class="alert alert-danger alert-dismissible">
		<a href="../../modules/shop-synchronize/synchronize.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>ERRORE!</strong> L'upload del file xml non è riuscito!.
	</div>
<?php
} elseif ($_GET['success']==5){
	?>
	<div class="alert alert-danger alert-dismissible">
		<a href="../../modules/shop-synchronize/synchronize.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>ERRORE!</strong> L'upload dell'immagine dell'articolo non è riuscito!.
	</div>
<?php
} elseif ($_GET['success']==6){
	?>
	<div class="alert alert-danger alert-dismissible">
		<a href="../../modules/shop-synchronize/synchronize.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>ERRORE!</strong> Non è riuscita la cancellazione delle vecchie immagini temporanee della cartella images remota!.
	</div>
<?php
}elseif ($_GET['success']==7){
	?>
	<div class="alert alert-danger alert-dismissible">
		<a href="../../modules/shop-synchronize/synchronize.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>ERRORE!</strong> accesso FTP  non riuscito! (controllare impostazione user e password)!.
	</div>
<?php
}elseif ($_GET['success']==8){
	?>
	<div class="alert alert-danger alert-dismissible">
		<a href="../../modules/shop-synchronize/synchronize.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>ERRORE!</strong> accesso al server FTP  non riuscito! (controllare nome server o se si ha accesso ad ftp esterno)!.
	</div>
<?php
}

require("../../library/include/footer.php");

if (isset($_GET['img']) && $_GET['img']=='updimg' && intval($img_limit)>0){
	?>
	<script>
	$('.select_all').hide();
	var limit = <?php echo intval($img_limit); ?>;
	$('input.single_checkbox').on('click', function (evt) {
		if ($('.single_checkbox:checked').length > limit) {
			this.checked = false;
			alert('Se si aggiornano le immagini, il limite massimo selezionabile è <?php echo intval($img_limit); ?> prodotti');
		}
	});
	</script>
	<?php
}
?>
