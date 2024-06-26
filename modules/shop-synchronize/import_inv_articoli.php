<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
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
// Antonio Germani - Creazione INVENTARIO articoli da e-commerce a GAzie con creazione di movimento 99 nel magazzino GAzie

require("../../library/include/datlib.inc.php");
require ("../../modules/magazz/lib.function.php");
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
$today=date('Y-m-d');

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
			alert("<?php echo "Errore! Il file xml non è stato creato oppure non è possibile accedervi"; ?>");
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
			$_POST['codice'.$ord]=addslashes(substr($_POST['codice'.$ord],0,32)); // Il codice articolo di GAzie è max 15 caratteri


			$web_public=1;
			// se l'e-commerce ha mandato la priorità di pubblicazione la imposto
			if (intval($product->WebPublish)>0){
				$web_public=intval($product->WebPublish);
			}

			if ($product->Type=="parent"){ // se è un parent
				$esiste = gaz_dbi_get_row($gTables['artico_group'], "ref_ecommerce_id_main_product", $_POST['product_id'.$ord]);// controllo se esiste in GAzie
				$tablefile="artico_group";
				$itemref=($esiste)?$esiste['id_artico_group']:'';
			} else {
				$esiste = gaz_dbi_get_row($gTables['artico'], "ref_ecommerce_id_product", $_POST['product_id'.$ord]);// controllo se esiste in GAzie come id e-commerce
				$vat = gaz_dbi_get_row($gTables['aliiva'], "aliquo", $product->VAT, " AND tipiva = 'I'"); // prendo il codice IVA
				$tablefile="artico";
				$itemref=$_POST['codice'.$ord];
			}

			$id_artico_group="";
			if ($product->ParentId > 0){ // se è una variante

				$parent = gaz_dbi_get_row($gTables['artico_group'], "ref_ecommerce_id_main_product", $product->ParentId);// trovo il padre in GAzie

				if (!isset($parent)){
					header("Location: " . "../../modules/shop-synchronize/import_inv_articoli.php?success=2&parent=".$product->ParentId."&code=".$_POST['codice'.$ord]);
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

			  // Avvio la creazione degli inventari
			if ($esiste){
				if ($product->Type=="parent"){// se è un parent ***<<<<<
					// non faccio nulla perché il parent è un gruppo, non ha magazzino
				} else {

					$form=gaz_dbi_get_row($gTables['artico'], "ref_ecommerce_id_product", $product->Id);
					$qta=floatval($product->AvailableQty);
					$value=array('datreg'=>$today, 'caumag'=>99, 'operat'=>1, 'tipdoc'=>"INV", 'desdoc'=>"Inventario da e-commerce", 'datdoc'=>$today, 'artico'=>$form['codice'], 'quanti'=>$qta, 'prezzo'=>$form['web_price'], 'adminid'=>$admin_aziend['user_name']);
					//echo "<pre>",print_r($value);

					gaz_dbi_table_insert("movmag", $value);	// scrivo il rigo magazzino in questa maniera perché è un inventario di importazione da e-commerce e non deve aggiornare nuovamente le Q.tà dell'e-commerce
				}
			}
		}
		$ord++;
	}
	header("Location: " . "../../modules/shop-synchronize/import_inv_articoli.php?success=1");
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
					<div class="col-sm-12" align="center"><h4>Importazione INVENTARIO articoli dall'e-commerce in GAzie</h4>
						<p align="justify">Per gli articoli selezionati, qualora già esistenti in GAzie, verrà creato un movimento di magazzino come inventario iniziale </p>
						<p align="justify">Gli articoli da inventariare devono già essere presenti in GAzie e sincronizzati con l'e-commerce, altrimenti verranno ignorati.</p>

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
						<button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#downloader">Crea inventario in GAzie</button>
						<!-- Modal content-->
						<div id="downloader" class="modal fade" role="dialog">
							<div class="modal-dialog modal-content">
								<div class="modal-header" align="left">
									<button type="button" class="close" data-dismiss="modal">&times;</button>
									<h4 class="modal-title">ATTENZIONE !</h4>
								</div>
								<div class="modal-body">
									<p>Stai per creare un inventario prodotti in GAzie. <br>Questa operazione &egrave irreversibile. <br>Sei sicuro di volerlo fare?</p>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Annulla</button>
									<input type="submit" class="btn btn-danger pull-right" name="conferma"  value="Crea inventario in GAzie">
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
								<?php
								if ($product->Type != "parent"){
									?>
								<input type="checkbox" name="download<?php echo $n; ?>" value="download">
								<?php }
								?>
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
							<button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#downloader">Crea inventario in GAzie</button>

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
