<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
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
// >> Creazione del file .txt di upload per il SIAN <<

require("../../library/include/datlib.inc.php");
require ("../../modules/magazz/lib.function.php");
if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
    gaz_set_time_limit (0);
}
$admin_aziend=checkAdmin();
$id_sian = gaz_dbi_get_row($gTables['company_config'], 'var', 'id_sian');

// controllo che ci sia la cartella sian
if (!file_exists(DATA_DIR.'files/'.$admin_aziend['codice'].'/sian/')) {// se non c'è la creo
    mkdir(DATA_DIR.'files/'.$admin_aziend['codice'].'/sian/', 0777);
}
// leggo i file eventualmente contenuti
if ($handle = opendir(DATA_DIR.'files/'.$admin_aziend['codice'].'/sian/')){
   while (false !== ($file = readdir($handle))){
       $prevfiles[]=$file;
   }
   closedir($handle);
}

if (!isset ($id_sian) or intval($id_sian['val']==0)){
echo "errore manca id sian. Per utilizzare questa gestione file SIAN è necessario inserire il proprio codice identificativo in configurazione azienda";
die;}

$type_array=array();
// $type_zero è la stringa ANAGFCTO formattata SIAN vuota *** NON TOCCARE MAI!!! ***
$type_zero="                ;  ;                ;0000000000;                                                                                                                                                      ;                                                                                                                                                      ;  ;   ;   ;";
// $type_zero è la stringa formattata SIAN vuota *** NON TOCCARE MAI!!! ***

$datsta=date("Y").date("m").date("d");

$progr=0;
foreach ($prevfiles as $files){ // se nella stessa giornata sono stati creati altri file SIAN come ANAGFCTO aumento il progressivo
	$f=explode("_",$files);
	if (isset($f[1]) AND $f[3] == "ANAGFCTO.txt"){
		if ($f[1]==$datsta){
			if($f[1]>$progr){
				$progr=$f[2];
			}
		}
	}
}
$progr++;

if (!isset($_POST['ritorno'])){// Antonio Germani - se non è stata ricaricata la pagina creo il nome del file
	$namefile=$admin_aziend['codfis']."_".$datsta."_".sprintf ("%05d",$progr)."_ANAGFCTO.txt";
} else { // altrimenti riprendo il nome file già creato
	$namefile=$_POST['namefile'];
}
$ritorno="file creato";

if (sizeof($_GET) > 0 AND !isset($_POST['ritorno'])) { // se ci sono movimenti e la pagina non è stata ricaricata creo il file
	$myfile = fopen(DATA_DIR."files/".$admin_aziend['codice']."/sian/".$namefile, "w") or die("Unable to open file!");

	foreach ($_GET as $row) {
		$type_array= explode (";", $type_zero); // azzero il type array per ogni movimento da creare

					// >> Antonio Germani - creo il record per questa anagrafica

					$anagra = gaz_dbi_get_row($gTables['anagra'],"id_SIAN",$row);
					if ($anagra['country']=="IT"){
						$municip = gaz_dbi_get_row($gTables['municipalities'],"name",$anagra['citspe']);
						$stato="IT";$iso="";$istatpro=substr($municip['stat_code'], 0, 3);$istatcom=substr($municip['stat_code'], 3, 3);
					} else {
						$country = gaz_dbi_get_row($gTables['country'],"iso",$anagra['country']);
						if ($country['istat_area']==11){
							$stato="CE";$iso=$country['iso'];$istatpro="";$istatcom="";
						} else {
							$stato="NE";$iso=$country['iso'];$istatpro="";$istatcom="";
						}
					}

					// Antonio Germani - campi comuni a tutti i casi
					$type_array[0]=str_pad($admin_aziend['codfis'], 16); // aggiunge spazi finali
					$type_array[1]=$stato; // Stato ditta
					$type_array[2]=str_pad ($anagra['codfis'],16); // Identificativo fiscale
					$type_array[3]=sprintf("%010d",$anagra['id_SIAN']);// codice soggetto
					$type_array[4]=str_pad($anagra['ragso1']." ".$anagra['ragso2'], 150);// denominazione soggetto
					$type_array[5]=str_pad($anagra['indspe'], 150);// indirizzo soggetto
					$type_array[6]=str_pad($iso, 2); // codice ISO Nazione
					$type_array[7]=str_pad($istatpro, 3); // codice ISTAT provincia
					$type_array[8]=str_pad($istatcom, 3); // codice ISTAT comune

					$type= implode(";",$type_array);
					$type=$type."\r\n";// il SIAN richiede un ritorno a capo dopo ogni record
					fwrite($myfile, $type);

					// aggiorno il campo status_sian in clfoco
					$id=$anagra['id'];
					gaz_dbi_put_query($gTables['clfoco'],"id_anagra=$id","status_SIAN","1");
	}
	fclose($myfile);
	?>
	<!-- E necessario evitare che se si ricarica la pagina si rigeneri un nuovo file  -->
	<form name="myform" method="POST" enctype="multipart/form-data">
	<input type="hidden" value="<?php echo $ritorno; ?>" name="ritorno">
	<input type="hidden" value="<?php echo $namefile; ?>" name="namefile">
	<script type="text/javascript">
	document.myform.submit();
	</script>
	</form>
	<?php
}

require("../../library/include/header.php");
$script_transl=HeadMain();

$namefile=substr($namefile,0,-4)
?>
<div class="panel panel-default gaz-table-form">
    <div class="container-fluid">
		<div align="center">
			<p>
			Il file è stato generato. <br>Prima di accedere al portale del SIAN per l'upload bisogna scaricare il file nel proprio pc.
			</p>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label for="cod_silos" class="col-sm-4 control-label"><?php echo "Download del file generato: "; ?></label>
					<p><a href="../camp/getfilesian.php?filename=<?php echo $namefile;?>&ext=txt&company_id=<?php echo $admin_aziend['codice']; ?>" class="col-sm-6 control-label">
					<?php echo $namefile; ?>
					<i class="glyphicon glyphicon-file" title="Scarica il file appena generato"></i>
					</a></p>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label for="cod_silos" class="col-sm-4 control-label"><?php echo "Accedi al portale dell'olio del SIAN: "; ?></label>
					<p><a  class="btn btn-info btn-md" href="javascript:;" onclick="window.open('<?php echo"https://www.sian.it/icqrfportaleolioAR/start.do";?>', 'titolo', 'menubar=no, toolbar=no, width=800, height=400, left=80%, top=80%, resizable, status, scrollbars=1, location');">
					<img src="../../modules/camp/media/logo_sian.jpg" alt="Logo portale SIAN" title="Vai al portale dell'olio del SIAN" style="max-width:100%">
					</a></p>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
require("../../library/include/footer.php");
?>
