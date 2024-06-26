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
 ------------------------------------------------------------------------
  INTERFACCIA sincronizzazione e-commerce <-> GAzie
  ------------------------------------------------------------------------
  @Author    Antonio Germani 340-5011912
  @Website   http://www.programmisitiweb.lacasettabio.it
  @Copyright Copyright (C) 2018 - 2021 Antonio Germani All Rights Reserved.
  versione 3.0
  ------------------------------------------------------------------------
*/
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();

// Prendo l'id_currency
$test = gaz_dbi_query("SHOW COLUMNS FROM `" . $gTables['admin'] . "` LIKE 'enterprise_id'");
$exists = (gaz_dbi_num_rows($test)) ? TRUE : FALSE;
if ($exists) {
    $c_e = 'enterprise_id';
} else {
    $c_e = 'company_id';
}

$file_download = "dowload_ordini.php";
$file_download2 = "dowload_ordini2.php";
$file_upload = "upload_prodotti.php";
$file_downloader = "import_articoli.php";
$file_uploader = "export_articoli.php";
$file_INVdownloader = "import_inv_articoli.php";
if (file_exists($file_download2)) {
  $ord_download2="ON";
}else{
  $ord_download2="OFF";
}

if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}
if (isset($_POST['Return'])) {
        header("Location: " . $_POST['ritorno']);
        exit;
    }
if (isset ($_POST['download'])) {
	if (file_exists($file_download)) {
		$period=0;
		header("Location: " . $file_download );
	} else {
		header("Location: " . $_POST['ritorno']);
        exit;
	}
} elseif (isset ($_POST['download2'])){
	if ($ord_download2=="ON") {
		$period=0;
		header("Location: " . $file_download2 );
	} else {
		header("Location: " . $_POST['ritorno']);
        exit;
	}
} elseif (isset ($_POST['upload'])) {

	if (file_exists($file_upload)){
		include $file_upload;
	} else {
		header("Location: " . $_POST['ritorno']);
        exit;
		}
} elseif (isset ($_POST['downloader'])) {

	if (file_exists($file_downloader)){ // importazione
		if (!isset($_POST['scarprezzo'])){
			$_POST['scarprezzo']="";
		}
		if (!isset($_POST['scardescrizione'])){
			$_POST['scardescrizione']="";
		}
		header("Location: " . $file_downloader."?upd=".(isset($_POST['upd']) ? $_POST['upd'] : 0) ."&updpre=".(isset($_POST['updpre']) ? $_POST['updpre'] : 0)."&updname=".(isset($_POST['updname']) ? $_POST['updname'] : 0)."&upddes=".(isset($_POST['upddes']) ? $_POST['upddes'] : 0)."&updimm=".(isset($_POST['updimm']) ? $_POST['updimm'] : 0)."&imp=".(isset($_POST['imp']) ? $_POST['imp'] : 0)."&imppre=".(isset($_POST['imppre']) ? $_POST['imppre'] : 0)."&impdes=".(isset($_POST['impdes']) ? $_POST['impdes'] : 0)."&impimm=".(isset($_POST['impimm']) ? $_POST['impimm'] : 0));
		exit;
	} else {
		header("Location: " . $_POST['ritorno']);
        exit;
		}
} elseif (isset ($_POST['uploader'])) {

	if (file_exists($file_uploader)){ // esportazione/aggiornamento-inserimento
		header("Location: " . $file_uploader."?prezzo=".(isset($_POST['prezzo']) ? $_POST['prezzo'] : 0)."&qta=".(isset($_POST['quantita']) ? $_POST['quantita'] : 0)."&descri=".(isset($_POST['descri']) ? $_POST['descri'] : 0)."&img=".(isset($_POST['immagine']) ? $_POST['immagine'] : 0)."&name=".(isset($_POST['name']) ? $_POST['name'] : 0)."&todo=".(isset($_POST['insert']) ? $_POST['insert'] : 'update'));
		exit;
	} else {
		header("Location: " . $_POST['ritorno']);
        exit;
		}
} elseif (isset ($_POST['INVdownloader'])) {

	if (file_exists($file_INVdownloader)){ // importazione

		header("Location: " . $file_INVdownloader);
		exit;
	} else {
		header("Location: " . $_POST['ritorno']);
        exit;
		}
}else {
	require('../../library/include/header.php');
	$script_transl = HeadMain();
	?>
<form method="POST" name="chouse" enctype="multipart/form-data">
	<input type="hidden" name="ritorno" value="<?php echo $_POST['ritorno'];?>" >
	<div class="container-fluid" style="max-width:90%;">
		<div class="row bg-primary" >
			<div class="col-sm-12" align="center"><h4>Sincronizzazione di GAzie con sito e-commerce</h4>
				<p align="justify">Scarica ordini: importa ordini dal sito a GAzie</p>
				<p align="justify">Aggiorna prodotti: aggiorna le quantità disponibili da Gazie al sito</p>
			</div>
		</div>
		<div>
			<div class="row bg-info">
				<div class="col-sm-4  bg-warning" align="center">
					<input type="submit" id="preventDuplicate" class="btn btn-primary" name="Return"  onClick="chkSubmit();" value="Indietro">
				</div>
				<div class="col-sm-4  bg-success" align="center">
					<input type="submit" id="preventDuplicate" class="btn btn-primary" name="download"  onClick="chkSubmit();" value="Scarica ordini">
          <?php
          if ($ord_download2=="ON") {
            ?>
            <input type="submit" id="preventDuplicate" class="btn btn-primary" name="download2"  onClick="chkSubmit();" value="Scarica ordini2">
            <?php
          }
          ?>
				</div>
				<div class="col-sm-4 bg-warning" align="center">
					<input type="submit" id="preventDuplicate" class="btn btn-primary" name="upload"  onClick="chkSubmit();" value="Aggiorna q.t&agrave; prodotti">
				</div>
			</div>
			<div class="row bg-info">
				<div class="col-sm-9  bg-info" align="center">
					<input title="Più selezioni" type="button" name="button1" id="nextbt" rel="more" value="&#9660 più selezioni" onclick="buttonToggle(this,'&#9650','&#9660')">
				</div>
				<div class="col-sm-3  bg-info" align="center">
					<input title="Impostazioni" type="button" name="settings"  onclick="window.location.href='config_sync.php'" value="Impostazioni" >
					<input title="Documentazione" type="button" name="document"  onclick="window.location.href='docume_shop-synchronize.php'" value="Documentazione">
				</div>
			</div>
			<div id="more">
				<div class="row bg-warning" style="border-top: 1px solid;">
					<div class="col-sm-12 bg-warning" align="center" >
							<h3 class="text-primary">ESPORTAZIONE - aggiornamento/inserimento articoli nell'e-commerce</h3>
					</div>
					<div class="col-sm-12  bg-warning" align="left" style="font-size: 18;">
						UPDATE: <input type="checkbox" name="quantita" value="updqty" checked> Quantit&agrave &nbsp
						<input type="checkbox" name="prezzo" value="updprice"> Prezzo &nbsp
						<input type="checkbox" name="name" value="updnam" > Nome &nbsp
						<input type="checkbox" name="descri" value="upddes" > Descrizione estesa &nbsp
						<input type="checkbox" name="immagine" value="updimg" > immagine &nbsp
					</div>
					<div class="col-sm-12  bg-warning" align="left" style="font-size: 18;">
						INSERT: <input type="checkbox" name="insert" value="insert" > Se non presente inserisci l'articolo (NB: deve essere attivata la sincronizzazione nel sito web e deve essere un articolo semplice, NO varianti e NO gruppi/parent) &nbsp
					</div>

						<div class="col-sm-12  bg-warning">
							<input type="submit" class="btn btn-danger btn-sm pull-right" name="uploader"  value="Seleziona i prodotti da aggiornare/inserire">
						</div>

				</div>
				<div class="row bg-success" style="border-top: 1px solid;">
						<div class="col-sm-12 bg-success" align="center" >
							<h3 class="text-primary">IMPORTAZIONE - inserimento o aggiornamento articoli in GAzie</h3>
						</div>

						<div class="col-sm-6  bg-success" align="left" style="font-size: 18;">
							<input type="checkbox" name="upd" value="updval" > Attiva modifica articolo<br><br>
							<p> Nell'articolo variare anche:</p>
							<!-- <input type="checkbox" name="impquantita" value="dwldqty"> quantit&agrave &nbsp -->
							<input type="checkbox" name="updpre" value="updpre"> Prezzo web &nbsp
							<input type="checkbox" name="updname" value="updnam" > Nome &nbsp
							<input type="checkbox" name="upddes" value="upddes" > Descrizione estesa &nbsp
							<input type="checkbox" name="updimm" value="updimg" > Immagine &nbsp
						</div>
						<div class="col-sm-6  bg-success" align="left" style="font-size: 18;">
							<input type="checkbox" name="imp" value="impval" > Attiva inserimento articolo<br><br>
							<p> Nell'articolo inserire anche:</p>
							<!-- <input type="checkbox" name="scarquantita" value="dwldqty"> quantit&agrave &nbsp -->
							<input type="checkbox" name="imppre" value="dwlprice"> Prezzo web &nbsp
							<input type="checkbox" name="impdes" value="dwldes" > Descrizione estesa &nbsp
							<input type="checkbox" name="impimm" value="dwlimg" > Immagine &nbsp
						</div>
						<div class="col-sm-12  bg-success">
							<input type="submit" class="btn btn-danger btn-sm pull-right" name="downloader"  value="Seleziona i prodotti da importare o aggiornare">
						</div>

				</div>
				<div class="row bg-success" style="border-top: 1px solid;">
					<div class="col-sm-12  bg-success">
						<input type="submit" class="btn btn-secondary btn-sm pull-left" name="INVdownloader" title="Importa inventario iniziale" value="Inventario iniziale">
					</div>
				</div>


			</div>
		</div>
	</div>
<div class="navbar navbar-fixed-bottom"	style="
    margin-left: 25%; border: none; z-index:2000; max-width: 170px;">
<a target="_new" href="https://programmisitiweb.lacasettabio.it/">Modulo di Antonio Germani</a>
</div>
</form>

	<style>#more { display:none; }</style>
	<script>
		function buttonToggle(where, pval, nval) {
			var table = document.getElementById(where.attributes.rel.value);
			where.value = (where.value == pval) ? nval : pval;
			table.style.display = (table.style.display == 'block') ? 'none' : 'block';
		}
	</script>
	<?php
}
require("../../library/include/footer.php");
?>
