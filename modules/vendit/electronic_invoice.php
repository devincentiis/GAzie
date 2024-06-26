<?php
/*
 --------------------------------------------------------------------------
                            GAzie - Gestione Azienda
    Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
         (http://www.devincentiis.it)
           <http://gazie.sourceforge.net>
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
    scriva   alla   Free  Software Foundation, 51 Franklin Street,
    Fifth Floor Boston, MA 02110-1335 USA Stati Uniti.
 --------------------------------------------------------------------------
*/
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
require("../../library/include/electronic_invoice.inc.php");
$res_attach_pdf_to_fae = gaz_dbi_get_row($gTables['company_config'], 'var', 'attach_pdf_to_fae');
$attach_pdf_to_fae = isset($res_attach_pdf_to_fae['val'])?intval($res_attach_pdf_to_fae['val']):'';

// recupero i dati
if (isset($_GET['id_tes'])) {   //se viene richiesta la stampa di un solo documento attraverso il suo id_tes
  $id_testata = intval($_GET['id_tes']);
  $testata = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', $id_testata);
  if (substr($testata['tipdoc'],0,1)=='X'){
    $where="tipdoc = '". $testata['tipdoc'] ."' AND seziva = ".$testata['seziva']." AND YEAR(datreg) = ".substr($testata['datreg'],0,4)." AND protoc = ".$testata['protoc'];
  } else {
    $where="tipdoc = '". $testata['tipdoc'] ."' AND seziva = ".$testata['seziva']." AND YEAR(datfat) = ".substr($testata['datfat'],0,4)." AND protoc = ".$testata['protoc'];
  }
	if ($testata['tipdoc']=='VCO'){ // in caso di fattura allegata a scontrino mi baso solo sull'id_tes
		$where="id_tes = ".$id_testata;
	}
} elseif (isset($_GET['zn'])) { // nel caso abbia lo zip
	$where="filename_zip_package = '".substr($_GET['zn'], 0, 37)."'";
} else { // in tutti gli altri casi devo passare i valori su $_GET
  if (!isset($_GET['protoc']) || !isset($_GET['year']) || !isset($_GET['seziva'])) {
    header("Location: report_docven.php");
    exit;
  } else {
    $where="tipdoc LIKE 'F__' AND seziva = ".intval($_GET['seziva'])." AND YEAR(datfat) = ".intval($_GET['year'])." AND protoc = ".intval($_GET['protoc']);
  }
}

if (isset($_GET['reinvia'])) {   //se viene richiesto un reinvio con altro nome faccio avanzare il relativo contatore sulle testate delle fatture
  gaz_dbi_query ("UPDATE ".$gTables['tesdoc']." SET `fattura_elettronica_reinvii`=`fattura_elettronica_reinvii`+1 WHERE ".$where);
  if (isset($_GET['sdiflux'])) {  // qualora sia richiesto il reinvio ed è presente una libreria o un modulo per la gestione dei flussi SdI
    $namelib = preg_replace("/[^a-zA-Z]+/", "", $_GET['sdiflux']);
    // distinguo se libreria "modalità catsrl" oppure modulo "modalità gazSynchro"
    if ( file_exists('../'.$namelib.'/sync.function.php') ) { // modalità gazSynchro
      require_once('../'.$namelib.'/sync.function.php');
      $classnamesdiflux = $namelib.'gazSynchro';
      $sdifluxSync = new $classnamesdiflux();
      // invio tramite i metodi della classe per la sincronizzazione con SdI
      $sdifluxSync->SendFaE();
      $res=$sdifluxSync->SendFaE($_GET);
      if (strlen($res)>1){ // invio non riuscito
        print '<br/>'.$res;
        exit;
      } else { // invio riuscito
        header("Location: " . $_SERVER['HTTP_REFERER']);
      }
    } elseif(file_exists('../../library/'.$namelib.'/SendFaE.php'))  { // modalità catsrl
      require('../../library/'.$namelib.'/SendFaE.php');
      // invio tramite le funzioni  della classe per la sincronizzazione con SdI
    }
  }
} else if (isset($_GET['invia'])) {
  if (isset($_GET['sdiflux'])) {  // qualora sia richiesto il reinvio ed è presente una libreria o un modulo per la gestione dei flussi SdI
    $namelib = preg_replace("/[^a-zA-Z]+/", "", $_GET['sdiflux']);
    // distinguo se libreria "modalità catsrl" oppure modulo "modalità gazSynchro"
    if ( file_exists('../'.$namelib.'/sync.function.php') ) { // modalità gazSynchro
		require_once('../'.$namelib.'/sync.function.php');
		$classnamesdiflux = $namelib.'gazSynchro';
		$sdifluxSync = new $classnamesdiflux();
		// invio tramite i metodi della classe per la sincronizzazione con SdI
		$res = $sdifluxSync->SendFaE($_GET);
		if (strlen($res)>1) { // invio non riuscito
			print '<br/>'.$res;
			exit;
		} else { // invio riuscito
			header("Location: " . $_SERVER['HTTP_REFERER']);
		}
    } elseif(file_exists('../../library/'.$namelib.'/SendFaE.php')) { // modalità catsrl
		require('../../library/'.$namelib.'/SendFaE.php');
		if (isset($_GET['zn'])) {
			$zn = substr($_GET['zn'], 0, 37); // con questo metodo passo solo lo zip
			$file_url = DATA_DIR.'files/' . $admin_aziend['codice'] . '/' . $zn;
			$IdentificativiSdI = SendFattureElettroniche($file_url);
			if (!empty($IdentificativiSdI)) {
				if (is_array($IdentificativiSdI)) {
					gaz_dbi_put_query($gTables['fae_flux'], "filename_zip_package = '" . $zn."'", "flux_status", "@@");
					foreach ($IdentificativiSdI as $filename_ori=>$IdentificativoSdI) {
					gaz_dbi_put_query($gTables['fae_flux'], "filename_ori = '" . $filename_ori."'", "id_SDI", $IdentificativoSdI);
					}
				} else {
					echo '<p>' . print_r($IdentificativiSdI, true) . '</p>';
				}
			}
		}
		header('Location: report_fae_sdi.php?post_xml_result=OK');
    }
  }
}

if ($attach_pdf_to_fae==1){
  require("../../library/include/document.php");
  //recupero i dati per pdf
  $onlyonet_r = gaz_dbi_dyn_query("*", $gTables['tesdoc'],$where,'datemi ASC, numdoc ASC, id_tes ASC',0,1);
  $onlyonet = gaz_dbi_fetch_array($onlyonet_r);
  if ($onlyonet) {
    $testate = gaz_dbi_dyn_query("*", $gTables['tesdoc'],$where,'datemi ASC, numdoc ASC, id_tes ASC');
    if ($onlyonet['tipdoc']=='FAD') {
      $pdf_content=createInvoiceFromDDT($testate,$gTables,'X');
    } else {
      $testata = gaz_dbi_fetch_array($testate);
      $pdf_content=createDocument($testata,$onlyonet['template'],$gTables,'rigdoc','X');
    }
  } else {
    $pdf_content=false;
  }
} else {
  $pdf_content=false;
}
//recupero i dati
$testate = gaz_dbi_dyn_query("*", $gTables['tesdoc'],$where,'datemi ASC, numdoc ASC, id_tes ASC');
if (isset($_GET['viewxml'])) {   //se viene richiesta una visualizzazione all'interno del browser
	$file_content=create_XML_invoice($testate,$gTables,'rigdoc',false,'from_string.xml',false, $pdf_content);
	$fae_xsl_file = gaz_dbi_get_row($gTables['company_config'], 'var', 'fae_style');
	$doc = new DOMDocument;
	$doc->preserveWhiteSpace = false;
	$doc->formatOutput = true;
 	$doc->loadXML($file_content);
  // ricavo l'allegato, e se presente metterò un bottone per permettere il download
  $yesatt = $doc->getElementsByTagName('NomeAttachment')->item(0);
  require("../../library/include/header.php");
  $script_transl = HeadMain(0, array('custom/modal_form'));
  if ($yesatt){
    $allegati = $doc->getElementsByTagName('Allegati');
    foreach ($allegati as $allitem){
      $nomeatt = $allitem->getElementsByTagName('NomeAttachment')->item(0);
      $name_file = $nomeatt->textContent;
      $contentatt = $allitem->getElementsByTagName('Attachment')->item(0);
      echo '<div class="text-center text-bold">Download allegato: <a download='.$name_file.'" href="data:application/'.pathinfo($name_file,PATHINFO_EXTENSION).';base64,'.$contentatt->textContent.'">'.$name_file.'</a></div>';
    }
  }
	$xpath = new DOMXpath($doc);
	$xslDoc = new DOMDocument();
	$xslDoc->load("../../library/include/".$fae_xsl_file['val'].".xsl");
	$xslt = new XSLTProcessor();
	$xslt->importStylesheet($xslDoc);
	$iframe_src = str_replace('"', '&quot;', $xslt->transformToXML($doc));
?>
  <div class="col-sm-6 text-center"><a class="btn btn-info" href="./electronic_invoice.php?id_tes=<?php echo $id_testata; ?>&viewforprint" target="_new">Visualizza per stampa</a></div><div class="col-sm-6"></div>
  <iframe style="border: none" width="99%" height="400px" sandbox="allow-same-origin" name="frame"
    srcdoc="<?=$iframe_src?>"
    onload="this.style.height = this.contentDocument.firstChild.scrollHeight + 'px'; this.contentDocument.body.style.textAlign = 'center';">
  </iframe>
<?php
  require("../../library/include/footer.php");
} elseif (isset($_GET['viewforprint'])) {   //se viene richiesta una prestampa
	$file_content=create_XML_invoice($testate,$gTables,'rigdoc',false,'from_string.xml',false, $pdf_content);
	$fae_xsl_file = gaz_dbi_get_row($gTables['company_config'], 'var', 'fae_style');
	$doc = new DOMDocument;
	$doc->preserveWhiteSpace = false;
	$doc->formatOutput = true;
 	$doc->loadXML($file_content);
	$xpath = new DOMXpath($doc);
	$xslDoc = new DOMDocument();
	$xslDoc->load("../../library/include/".$fae_xsl_file['val'].".xsl");
	$xslt = new XSLTProcessor();
	$xslt->importStylesheet($xslDoc);
	echo $xslt->transformToXML($doc);
} else { // .... altrimenti faccio il download diretto
	create_XML_invoice($testate,$gTables,'rigdoc',false,false,false,$pdf_content);
}
?>
