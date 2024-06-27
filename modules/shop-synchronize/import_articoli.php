<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  SHOP SYNCHRONIZE è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2019-2021 - Antonio Germani, Massignano (AP)
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
// Antonio Germani - Importazione articoli da e-commerce a GAzie con creazione articolo in GAzie se non esiste o aggiornamento se esiste

require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
$resserver = gaz_dbi_get_row($gTables['company_config'], "var", "server");
$ftp_host= $resserver['val'];

$OSaccpass = gaz_dbi_get_row($gTables['company_config'], "var", "accpass")['val'];// vecchio sistema di password non criptata
$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'accpass'");
$rdec=gaz_dbi_fetch_row($rsdec);
$accpass=$rdec?htmlspecialchars_decode($rdec[0]):'';
$accpass=(strlen($accpass)>0)?$accpass:$OSaccpass; // se la password decriptata non ha dato risultati provo a mettere la password non criptata

$path = gaz_dbi_get_row($gTables['company_config'], 'var', 'path');
$urlinterf = $path['val']."dwnlArticoli-gazie.php";//nome del file interfaccia presente nella root del sito e-commerce. Per evitare intrusioni indesiderate Il file dovrà gestire anche una password.
// il percorso per raggiungere questo file va impostato in configurazione avanzata azienda alla voce "Website root directory"
$test = gaz_dbi_query("SHOW COLUMNS FROM `" . $gTables['admin'] . "` LIKE 'enterprise_id'");
$exists = (gaz_dbi_num_rows($test)) ? TRUE : FALSE;

if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}
if (isset($_POST['Return'])) {
        header("Location: " . $_POST['ritorno']);
        exit;
    }

if ($exists) {
    $c_e = 'enterprise_id';
} else {
    $c_e = 'company_id';
}
$admin_aziend = gaz_dbi_get_row($gTables['admin'] . ' LEFT JOIN ' . $gTables['aziend'] . ' ON ' . $gTables['admin'] . '.' . $c_e . '= ' . $gTables['aziend'] . '.codice', "user_name", $_SESSION["user_name"]);


$access=base64_encode($accpass);
if (!isset($_GET['success'])){
	// avvio il file di interfaccia presente nel sito web remoto
	$headers = @get_headers($urlinterf.'?access='.$access);

	if ( isset($headers[0]) AND intval(substr($headers[0], 9, 3))==200){ // controllo se ho avuto accesso al file interfaccia
		$xml=simplexml_load_file($urlinterf.'?access='.$access.'&rnd='.time()) ; // carico il file xml appena creato
		if (!$xml){ // se non è stato creato o non ho accesso
			?>
			<script>
			alert("<?php echo "Errore! Il file xml non è stato creato oppure c'è un errore nella sua formattazione"; ?>");
			location.replace("<?php echo $_POST['ritorno']; ?>");
			</script>
			<?php
		}
	} else { // ERRORE FILE INTERFACCIA > ESCO
		echo "Errore di connessione al file di interfaccia web";
			?>
			<script>
			alert("<?php echo " Errore di connessione al file di interfaccia web = ",intval(substr($headers[0], 9, 3)),"<br> Controllare codice errore o riprovare fra qualche minuto!"; ?>");
			location.replace("<?php echo $_POST['ritorno']; ?>");
			</script>
			<?php
			exit;
		}
}

if (isset($_POST['conferma'])) { // se confermato
    // scrittura articoli su database di GAzie
	$ord=0;
	foreach($xml->Documents->children() as $product) { // ciclo gli articoli e scrivo i database
		unset($form);
		if (isset($_POST['download'.$ord])){ // se selezionato

			unset($esiste);
			$_POST['codice'.$ord]=addslashes(substr($_POST['codice'.$ord],0,15)); // Il codice articolo di GAzie è max 15 caratteri

			// ricongiungo la categoria dell'e-commerce con quella di GAzie, se esiste
			$category=0;
			if (intval($product->ProductCategoryId)>0){// se l'e-commerce ha inviato una categoria
				$cat = gaz_dbi_get_row($gTables['catmer'], "ref_ecommerce_id_category", $product->ProductCategoryId);
				if ($cat){// controllo se esiste in GAzie
					$category=$cat['codice'];
				}
				// se non esiste la categoria in GAzie, la creo
				if ($category == 0 OR $category == ""){
					$rs_ultimo_codice = gaz_dbi_dyn_query("*", $gTables['catmer'], 1 ,'codice desc',0,1);
					$ultimo_codice = gaz_dbi_fetch_array($rs_ultimo_codice);
					$cat['codice'] = $ultimo_codice['codice']+1;
					$cat['ref_ecommerce_id_category'] = $product->ProductCategoryId;
					$cat['descri'] = $product->ProductCategory;
					gaz_dbi_table_insert('catmer',$cat);
					// assegno l'id categoria al prossimo insert artico
					$category=$cat['codice'];

				}
			}

			$web_public=1;
			// se l'e-commerce ha mandato la priorità di pubblicazione la imposto
			if (intval($product->WebPublish)>0){
				$web_public=intval($product->WebPublish);
			}

			if ($product->Type=="parent"){ // se è un parent
				$esiste = gaz_dbi_get_row($gTables['artico_group'], "ref_ecommerce_id_main_product", $_POST['product_id'.$ord]);// controllo se esiste in GAzie
				$tablefile="artico_group";
				$itemref=($esiste)?$esiste['id_artico_group']:'';
			} else {// se è variante
				$esiste = gaz_dbi_get_row($gTables['artico'], "ref_ecommerce_id_product", $_POST['product_id'.$ord]);// controllo se esiste in GAzie come id e-commerce

				$vat = gaz_dbi_get_row($gTables['aliiva'], "aliquo", $product->VAT, " AND tipiva = 'I'"); // prendo il codice IVA
				if(!isset($vat['codice'])){// se non ho trovato una corrispondenza con l'aliquota passata dall'ecommerce
					$vat['codice']=1;// di default metto id 1
				}

				$tablefile="artico";
				$itemref=$_POST['codice'.$ord];
			}

			if (isset($esiste) AND strlen($product->ProductImgUrl)>0 AND $_GET['updimm']=="updimg" AND $_GET['upd']=="updval"){ // se è aggiornamento, se c'è un'immagine, se selezionato e se è attivo l'aggiornamento
				// cancello l'immagine presente nella cartella

				$imgres = gaz_dbi_get_row($gTables['files'], "table_name_ref", $tablefile, "AND id_ref ='1' AND item_ref = '". $itemref ."'");
				if (isset($imgres)){
					gaz_dbi_del_row($gTables['files'], 'id_doc',$imgres['id_doc']);
					@unlink (DATA_DIR."files/".$admin_aziend['company_id']."/images/". $imgres['id_doc'] . "." . $imgres['extension']);

				}
			}

			// se è inserimento o se è update e c'è un'immagine e se è selezionato
			if ((!$esiste AND strlen($product->ProductImgUrl)>0 AND $_GET['impimm']=="dwlimg" AND $_GET['imp']=="impval") OR ($esiste AND strlen( $product->ProductImgUrl)>0 AND $_GET['updimm']=="updimg" AND $_GET['upd']=="updval")){
				$target_filename="";
				// salvo l'immagine HQ
				$url = $product->ProductImgUrl;
				$expl= explode ("/", $product->ProductImgUrl);
				$form['table_name_ref']= $tablefile;
				$form['id_ref']= '1';
				$form['item_ref']= $itemref;
				$ext= explode (".",$expl[count($expl)-1]);
				$form['extension']= $ext[count($ext)-1];
				$form['title']= "Immagine web articolo: ".$_POST['codice'.$ord];

				gaz_dbi_table_insert('files',$form);// inserisco i dati dell'immagine nella tabella files
				$form['id_doc']= gaz_dbi_last_id();//recupero l'id assegnato dall'inserimento
				$imgweb=DATA_DIR.'files/'.$admin_aziend['company_id'].'/images/'.$form['id_doc'].'.'.$form['extension'];
				if (intval(file_put_contents($imgweb, file_get_contents($url))) == 0){ // scrivo l'immagine web HQ nella cartella files
					echo "ERRORE nella scrittura in GAzie dell'immagine: ",$url, " <br>Riprovare in quanto potrebbe trattarsi di un Errore momentaneo. Se persiste, controllare che le immagine dell'e-commerce abbiano il permesso per essere lette oppure che sia presente in GAzie la cartella images in data/files/nrAzienda/";die;
				}

				// inizio gestione salvataggio immagine blob
				$img = DATA_DIR.'files/tmp/'.$expl[count($expl)-1];
				// scrivo l'immagine nella cartella tmp temporanea
				file_put_contents($img, file_get_contents($url));
				// ridimensiono l'immagine per rientrare nei 64k
				$maxDim = 190;
				list($width, $height, $type, $attr) = getimagesize( $img );
				if ( $width > $maxDim || $height > $maxDim ) {
					$target_filename = $img;
					$ratio = $width/$height;
					if( $ratio > 1) {
						$new_width = $maxDim;
						$new_height = $maxDim/$ratio;
					} else {
							$new_width = $maxDim*$ratio;
							$new_height = $maxDim;
					}
					$src = imagecreatefromstring( file_get_contents( $img ) );
					$dst = imagecreatetruecolor( $new_width, $new_height );
					imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
					imagedestroy( $src );
					imagepng( $dst, $target_filename); // adjust format as needed
					imagedestroy( $dst );
				}
				//Carico l'immagine ridimensionata
				if (strlen($target_filename)>0){
					$immagine= addslashes (file_get_contents($target_filename));
				} else {
					$immagine= addslashes (file_get_contents($url));
				}
				unlink ($img);// cancello l'immagine temporanea
				if ($esiste && $product->Type=="parent"){ // se è un parent che esiste già
					gaz_dbi_query("UPDATE ". $gTables['artico_group'] . " SET image = '".$immagine."' WHERE ref_ecommerce_id_main_product = '".$_POST['product_id'.$ord]."'");

				}else {
					if ($esiste){
						$codice=$esiste['codice'];
					} else{
						$codice=$_POST['codice'.$ord];
					}
					$test=gaz_dbi_query("UPDATE ". $gTables['artico'] . " SET image = '".$immagine."' WHERE codice = '".$codice."'");
				}
				// fine salvataggio immagine

			} else {
				$immagine="";
			}

			$id_artico_group="";
			if ($product->ParentId > 0){ // se è una variante

				$parent = gaz_dbi_get_row($gTables['artico_group'], "ref_ecommerce_id_main_product", $product->ParentId);// trovo il padre in GAzie

				if (!isset($parent)){
					header("Location: " . "../../modules/shop-synchronize/import_articoli.php?success=2&parent=".$product->ParentId."&code=".$_POST['codice'.$ord]);
					exit;
				}
				$id_artico_group=$parent['id_artico_group']; // imposto il riferimento al padre
				if (strlen($product->Name)<2){ // se non c'è la descrizione della variante
					$product->Name=$parent['descri']."-".$product->Characteristic;// ci metto quella del padre accodandoci la variante
				}
			}
			if ($product->Type=="variant" AND strlen($product->Characteristic)>0 ){ // se una variante
				// creo un json array per la variante
				$arrayvar= array("var_id" => floatval($product->CharacteristicId), "var_name" => strval($product->Characteristic));
				$arrayvar = json_encode ($arrayvar);
			} else {
				$arrayvar = "";
			}
			if ($esiste AND $_GET['upd']=="updval"){ // se esiste l'articolo ed è attivo l'update, aggiorno l'articolo
					// Body text
					if (strlen(htmlspecialchars_decode($product->Description))>0 AND $_GET['upddes']=="upddes"){ // se c'è una descrizione estesa body_text ed è selezionata
						if ($product->Type=="parent"){ // se è un parent
							gaz_dbi_query("UPDATE ". $gTables['artico_group'] . " SET large_descri = '". addslashes (htmlspecialchars_decode ($product->Description)) ."' WHERE ref_ecommerce_id_main_product = '".$_POST['product_id'.$ord]."'");
						} else {
							$esist = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", "artico_".$esiste['codice']);
							$form['body_text'] = htmlspecialchars_decode ($product->Description);// qui addslashes non si deve mettere perché ci pensa gaz_dbi-...

							if($esiste){
								$form['table_name_ref']="artico_".$esiste['codice'];
							} else {
								$form['table_name_ref']="artico_".$_POST['codice'.$ord];
							}
							$form['lang_id']=1;
							if ($esist) { // se c'è già
								$where = array("0" => "table_name_ref", "1" => "artico_".$esiste['codice']);
								gaz_dbi_table_update("body_text",$where, $form); // la aggiorno nel DB
							} else { // altrimenti
								gaz_dbi_table_insert('body_text', $form); // la scrivo nel DB
							}
						}
					}

					if (intval($category)>0){
						$updcat="catmer = '". $category ."',";
					} else {
						$updcat="";
					}
					$extra_upd="";
					if (strlen($product->WebUrl)>1){// se è stato mandato un web url lo aggiorno
						$extra_upd .= "web_url = '".$product->WebUrl."',";
					}
					if (strlen($product->Unimis)>0){// se è stata mandata l'unità di misura url lo aggiorno
						$extra_upd .= "web_mu = '".$product->Unimis."',";
					}
					if (strlen($product->Weight)>0){// se è stato mandato il peso lo aggiorno
						$extra_upd .= "peso_specifico = '".$product->Weight."',";
					}
					if (strlen($product->BarCode)==13){// se è stato mandato un barcode lo aggiorno
						$extra_upd .= "barcode = '".$product->BarCode."',";
					}
					if (isset($vat['codice'])){
						$extra_upd .= "aliiva = '".$vat['codice']."',";
					}
					if ($_GET['updpre']=="updpre" AND $_GET['updname']=="updnam") { // se devo aggiornare prezzo e nome

						if ($product->Type=="parent"){ // se è un parent
							gaz_dbi_query("UPDATE ". $gTables['artico_group'] . " SET descri = '". htmlspecialchars_decode (addslashes($product->Name)) ."', web_public = '".$web_public."' WHERE ref_ecommerce_id_main_product = '".$_POST['product_id'.$ord]."'");
						} else {
							gaz_dbi_query("UPDATE ". $gTables['artico'] . " SET ".$extra_upd." ecomm_option_attribute = '".$arrayvar."', ". $updcat ." peso_specifico = '".$product->Weight."', descri = '".addslashes($product->Name)."', web_price = '".addslashes($product->Price)."' , id_artico_group ='". $id_artico_group ."', web_public = '".$web_public."' WHERE ref_ecommerce_id_product = '". $_POST['product_id'.$ord] ."'");
						}
					} elseif ($_GET['updpre']!=="updpre" AND $_GET['updname']=="updnam") { // altrimenti non aggiorno il prezzo ma aggiorno il nome
						if ($product->Type=="parent"){ // se è un parent
							gaz_dbi_query("UPDATE ". $gTables['artico_group'] . " SET descri = '". htmlspecialchars_decode (addslashes($product->Name)) ."', web_public = '".$web_public."' WHERE ref_ecommerce_id_main_product = '".$_POST['product_id'.$ord]."'");
						} else {
							gaz_dbi_query("UPDATE ". $gTables['artico'] . " SET ".$extra_upd." ecomm_option_attribute = '".$arrayvar."', ". $updcat ." peso_specifico = '".$product->Weight."', descri = '".addslashes($product->Name)."', id_artico_group ='". $id_artico_group ."', web_public = '".$web_public."' WHERE ref_ecommerce_id_product = '". $_POST['product_id'.$ord] ."'");
						}
					} elseif ($_GET['updpre']=="updpre" AND $_GET['updname']!=="updnam" AND $product->Type!=="parent") { // altrimenti aggiorno il prezzo ma non aggiorno il nome
						gaz_dbi_query("UPDATE ". $gTables['artico'] . " SET ".$extra_upd." ecomm_option_attribute = '".$arrayvar."', ". $updcat ." peso_specifico = '".$product->Weight."', web_price = '".addslashes($product->Price)."', id_artico_group ='". $id_artico_group ."', web_public = '".$web_public."' WHERE ref_ecommerce_id_product = '". $_POST['product_id'.$ord] ."'");
					} else {// oppure aggiorno i dati default ma no nome e no prezzo
						if ($product->Type=="parent"){ // se è un parent
							gaz_dbi_query("UPDATE ". $gTables['artico_group'] . " SET descri = '". htmlspecialchars_decode (addslashes($product->Name)) ."', web_public = '".$web_public."' WHERE ref_ecommerce_id_main_product = '".$_POST['product_id'.$ord]."'");
						} else {
							gaz_dbi_query("UPDATE ". $gTables['artico'] . " SET ".$extra_upd." ecomm_option_attribute = '".$arrayvar."', ". $updcat ." peso_specifico = '".$product->Weight."', id_artico_group ='". $id_artico_group ."', web_public = '".$web_public."' WHERE ref_ecommerce_id_product = '". $_POST['product_id'.$ord] ."'");
						}
					}

			} elseif (!$esiste AND $_GET['imp']=="impval"){ // altrimenti, se è attivo l'inserimento, inserisco un nuovo articolo

				// prima di inserire il nuovo controllo se l'e-commerce ha mandato il codice articolo e se è già in uso in GAzie

				if (strlen($_POST['codice'.$ord])<1){// se l'e-commerce non ha inviato un codice me lo creo
					$_POST['codice'.$ord] = substr($product->Name,0,10)."-".substr($_POST['product_id'.$ord],-4);
				}

				unset($usato);
				$usato = gaz_dbi_get_row($gTables['artico'], "codice", $_POST['codice'.$ord]);// controllo se il codice è già stato usato in GAzie
				if ($usato){ // se il codice è già in uso lo modifico
					$_POST['codice'.$ord]=substr($_POST['codice'.$ord],0,10)."-".substr($_POST['product_id'.$ord],-4);
				}

				if ($product->Type=="parent"){// se è un parent ***<<<<<
					if (strlen(htmlspecialchars_decode($product->Description))>0 AND $_GET['impdes']!=="dwldes"){ // se non è stata selezionata la descrizione estesa
						$product->Description = ""; // la annullo
					}
					gaz_dbi_query("INSERT INTO " . $gTables['artico_group'] . "(descri,large_descri,image,web_url,ref_ecommerce_id_main_product,web_public,depli_public,adminid) VALUES ('" . addslashes($product->Name) . "', '" . addslashes (htmlspecialchars_decode ($product->Description)). "', '" . $immagine . "', '". $product->WebUrl . "', '". $_POST['product_id'.$ord] . "', '".$web_public."', '1', '". $admin_aziend['adminid'] ."')");
				} else {

					if ($_GET['imppre']=="dwlprice") { // se devo inserire anche il prezzo web
						gaz_dbi_query("INSERT INTO " . $gTables['artico'] . "(web_url,web_multiplier,ecomm_option_attribute,catmer,barcode,peso_specifico,codice,ref_ecommerce_id_product,descri,web_mu,web_price,unimis,image,web_public,depli_public,aliiva,id_artico_group) VALUES ('". $product->WebUrl ."', '1', '". $arrayvar ."', '" . $category . "', '" . $product->BarCode . "', '" . $product->Weight . "', '" . $_POST['codice'.$ord] . "', '" . $_POST['product_id'.$ord]. "', '" . addslashes($product->Name). "', '".$product->Unimis . "', '". addslashes($product->Price). "', '".$product->Unimis."', '".$immagine."', '".$web_public."', '1', '".$vat['codice']."', '". $id_artico_group ."')");
					} else { // altrimenti lo inserisco senza prezzo web
						gaz_dbi_query("INSERT INTO " . $gTables['artico'] . "(web_url,web_multiplier,ecomm_option_attribute,catmer,barcode,peso_specifico,codice,ref_ecommerce_id_product,descri,web_mu,unimis,image,web_public,depli_public,aliiva,id_artico_group) VALUES ('". $product->WebUrl ."', '1', '". $arrayvar ."', '" . $category . "', '" . $product->BarCode . "', '" . $product->Weight . "', '" . $_POST['codice'.$ord] . "', '" . $_POST['product_id'.$ord]. "', '" . addslashes($product->Name). "', '".$product->Unimis . "', '".$product->Unimis."', '".$immagine."', '".$web_public."', '1', '".$vat['codice']."', '". $id_artico_group ."')");
					}
					if (strlen(htmlspecialchars_decode($product->Description))>0 AND $_GET['impdes']=="dwldes"){ // se c'è una descrizione estesa - body_text ed è selezionata
						$form['body_text'] = htmlspecialchars_decode ($product->Description);// qui addslashes non si deve mettere perché ci pensa il successivo gaz_dbi_table_insert
						$form['table_name_ref']="artico_".$_POST['codice'.$ord];
						$form['lang_id']=1;
						gaz_dbi_table_insert('body_text', $form); // la scrivo nel DB
					}
				}
			}
		}
		$ord++;
	}
	header("Location: " . "../../modules/shop-synchronize/import_articoli.php?success=1");
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
    function submit() {
        var checks = document.getElementsByClassName('check');
        var str = '';
        for ( i = 0; i < checks.length; i++) {
            if ( checks[i].checked === true ) {
                str += checks[i].value + " ";
            }
        }
        alert(str);
    }
</script>
		<form method="POST" name="download" enctype="multipart/form-data">
			<input type="hidden" name="ritorno" value="<?php echo $_POST['ritorno'];?>" >
			<input type="hidden" name="download" value="download" >
			<div class="container-fluid" style="max-width:90%;">
				<div class="row bg-primary" >
					<div class="col-sm-12" align="center"><h4>Importazione di articoli dall'e-commerce in GAzie</h4>
						<p align="justify">Gli articoli selezionati verranno aggiornati o, se inesistenti, verranno creati. </p>
					</div>
				</div>
				<div class="row bg-info">
					<div class="col-sm-4">
						<input type="submit" name="Return"  value="Indietro">
					</div>
					<div class="col-sm-4" style="background-color:lightgreen;">
						<?php echo "Connesso a " . $ftp_host;?>
					</div>
					<div class="col-sm-4" align="right">
						<!-- Trigger the modal with a button -->
						<button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#downloader">Carica prodotti in GAzie</button>
						<!-- Modal content-->
						<div id="downloader" class="modal fade" role="dialog">
							<div class="modal-dialog modal-content">
								<div class="modal-header" align="left">
									<button type="button" class="close" data-dismiss="modal">&times;</button>
									<h4 class="modal-title">ATTENZIONE !</h4>
								</div>
								<div class="modal-body">
									<p>Stai per caricare/aggiornare definitivamente i prodotti in GAzie. <br>Questa operazione &egrave irreversibile. <br>Sei sicuro di volerlo fare?</p>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Annulla</button>
									<input type="submit" class="btn btn-danger pull-right" name="conferma"  value="Carica prodotti in GAzie">
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
						TUTTI <input type="checkbox" onClick="check(this)">
					</div>
				</div>

					<?php
					$n=0;
					foreach($xml->Documents->children() as $product) {
						$nr=0;
						$rowclass="bg-success";
						if ($product->Type == "parent"){
							$rowclass="bg-warning";
						}
						?>
						<div class="row <?php echo $rowclass ?>" style="border-bottom: 1px solid;">
							<div class="col-sm-2">
								<?php echo $n;?>
							</div>
							<div class="col-sm-3">
								<?php echo $product->Code;
								echo '<input type="hidden" name="codice'. $n .'" value="'. $product->Code . '">';
								?>
							</div>
							<div class="col-sm-5">
								<?php echo $product->Name;
								?>
							</div>
							<div class="col-sm-1">
								<?php
								echo '<input type="hidden" name="product_id'. $n .'" value="'. $product->Id .'">';

								?>
							</div>
							<div class="col-sm-1" align="right">
								<input type="checkbox" name="download<?php echo $n; ?>" value="download">

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
							<?php echo "Connesso a " . $ftp_host;?>
						</div>
						<div class="col-sm-4" align="right">
							<!-- Trigger the modal with a button -->
							<button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#downloader">Carica prodotti in GAzie</button>

						</div>
					</div>
			</div>
		</form>
		<?php
} else {
	if ($_GET['success']==1){
	?>
	<div class="alert alert-success alert-dismissible">
		<a href="../../modules/shop-synchronize/synchronize.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>Fatto!</strong> Operazione conclusa con successo.
	</div>
	<?php
	} else {
		?>
	<div class="alert alert-danger alert-dismissible">
		<a href="../../modules/shop-synchronize/synchronize.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>Errore, importazione interrotta!</strong> Si è tentato di importare una variante senza aver prima importato/creato un articolo padre in artico_group.
		<p>ParentID mancante: <?php echo $_GET['parent']; ?> Codice variante: <?php echo $_GET['code']; ?></p>
	</div>
	<?php
	}
}
require("../../library/include/footer.php");
?>
