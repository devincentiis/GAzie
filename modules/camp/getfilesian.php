<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
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

require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
if (isset($_GET['filename'])&&isset($_GET['ext'])&&isset($_GET['company_id'])){
	$bfn = filter_var($_GET['filename'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$ext = filter_var($_GET['ext'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$fn=$bfn.'.'.$ext;
	$ci = intval($_GET['company_id']);
	if (file_exists(DATA_DIR."files/".$ci."/sian/".$fn)){
	$mime=mime_content_type(DATA_DIR.'files/'.$ci.'/sian/'.$fn);
	$fs=filesize(DATA_DIR.'files/'.$ci.'/sian/'.$fn);
	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=".$fn);
	header ('Content-length: ' .$fs);
	header("Content-Type: ".$mime);
	header("Content-Transfer-Encoding: binary");
	readfile(DATA_DIR.'files/'.$ci.'/sian/'.$fn);
	} else {
		echo "ERRORE: impossibile scaricare il file perché non esiste";
		$loc = $_SERVER['HTTP_REFERER'];
		?>
		<input type="button" value="Back" onClick="window.location = '<?php echo $loc;?>'" />
		<?php
	}
}
?>
