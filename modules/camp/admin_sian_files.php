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
// >> gestione dei file .txt di upload per il SIAN <<

require("../../library/include/datlib.inc.php");
require ("../../modules/magazz/lib.function.php");
$admin_aziend = checkAdmin();

require("../../library/include/header.php");
$script_transl = HeadMain();

if ( isset($_POST['confirm'])){ // cancello il file dalla cartella di GAzie
	$filetodelete=DATA_DIR."files/".$admin_aziend['codice']."/sian/".$_POST['confirm'];
	if (substr($_POST['confirm'],-12) == "OPERREGI.txt"){
		// rimetto a zero lo status dei movimenti SIAN del file cancellato
		gaz_dbi_put_query($gTables['camp_mov_sian'], "status = '". $_POST['confirm']. "'", 'status', "0");
		// poi cancello il file
		unlink ($filetodelete);
		unset ($_POST,$form);
	}
	if (isset($_POST['confirm']) AND substr($_POST['confirm'],-12) == "ANAGFCTO.txt"){
		$fileContent=@file_get_contents($filetodelete); // prendo il contenuto del file
		$filerecord=str_split ($fileContent,363);
		foreach ($filerecord as $record) { // prendo l'ID anagrafica corrispondente
			$ana = gaz_dbi_get_row($gTables['anagra'],"codfis",substr($record,20,16));
			$id=$ana['id'];// rimetto a zero lo status_SIAN
			gaz_dbi_put_query($gTables['clfoco'],"id_anagra=$id","status_SIAN","0");
		}
		unlink ($filetodelete);
		unset ($_POST,$form);
	}
}

if (isset ($_POST['confirmsian'])){ // creo il file di cancellazione da inviare al SIAN
	$filetodelete=DATA_DIR."files/".$admin_aziend['codice']."/sian/".$_POST['confirmsian'];
	$fileContent=@file_get_contents($filetodelete); // prendo il contenuto dell'ultimo file da modificare
	$fileField=explode (";",$fileContent);
	$i = 0;
	foreach ($fileField as $a) { // ciclo gli elementi
		$i++;
		if ($i % 49 == 0) { // sostituisco ogni 49 elementi che corrisponde al tipo di record inviato: C=cancellazione
			if ($fileField[$i-1]=="C"){
				echo "<script type='text/javascript'>alert('errore il file è già di cancellazione!');</script>";$err="err"; break; // errore il file è già di cancellazione
			} else {
				$fileField[$i-1]="C";
			}
		}
	}
	if (!isset($err)){
		$fileContent=implode (";",$fileField);
		$nameContent=explode("_",$_POST['confirmsian']);
		$progrfile=intval($nameContent[2])+1;
		$nameContent[2]=sprintf ("%05d",$progrfile);
		$namefile=implode("_",$nameContent);
		$myfile = fopen(DATA_DIR."files/".$admin_aziend['codice']."/sian/".$namefile, "w") or die("Unable to open file!");
		fwrite($myfile, $fileContent);
		fclose($myfile);
	}
}

$form['delete']="";
$form['deletesian']="";
if (isset ($_POST['del'])){
	if ($_POST['del']=="delsian"){
		$form['deletesian']=$_POST['first'];
	} else {
		$form['delete']=$_POST['first'];
	}
}

// prendo tutti i file della cartella sian
if ($handle = opendir(DATA_DIR.'files/'.$admin_aziend['codice'].'/sian/')){
	while ($file = readdir($handle)){
		if ($file == '.' || $file == '..') {
			continue;
		}
		$files[]=$file;
	}
	closedir($handle);
	if (isset($files)){
		sort($files);
	}
}

?>
<form method="POST" enctype="multipart/form-data">
<div class="panel panel-default gaz-table-form">
    <div class="container-fluid">
		<div align="center" class="lead"><h1>Gestione dei file creati per l'upload al SIAN</h1></div>
		  <table class="col-md-12 table-bordered table-striped table-condensed cf">
		<thead class="cf">
	<tr>
	<th class="col-md-7">Nome file</th>
	<th class="col-md-2">Giorno di creazione</th>
	<th class="col-md-2">tipo</th>
	<th class="col-md-1">Scarica</th>
	</tr>
	</thead>
	<tbody>
	<?php
	if (strlen($form['delete'])>0){
			?>
			<tr>
				<td class="bg-danger">Conferma la cancellazione da GAzie di
				<input type="submit" name="confirm" title="clicca per confermare" value="<?php echo $form['delete']; ?>" class="btn btn-xs  btn-elimina">
				</td>
				<td>
				<input type="submit" name="null" value="Annulla">
				</td>
			</tr>
	<?php
	}
	if (strlen($form['deletesian'])>0){
			?>
			<tr>
				<td class="bg-warning">Conferma di voler creare un file SIAN che annulli questo:
				<input type="submit" name="confirmsian" title="clicca per confermare" value="<?php echo $form['deletesian']; ?>" class="btn btn-xs  btn-elimina">
				</td>
				<td>
				<input type="submit" name="null" value="Annulla">
				</td>
			</tr>
	<?php
	}
	if (isset($files)){ // se ci sono files
		foreach (array_reverse($files) as $file){
			$filetoread=DATA_DIR."files/".$admin_aziend['codice']."/sian/".$file;
			$fileContent=@file_get_contents($filetoread); // prendo il contenuto del file
			$fileField=explode (";",$fileContent);
			if (substr($filetoread,-12) == "OPERREGI.txt"){
				if ($fileField[48]=="I"){
					$filetype="Inser. mov.";
				} else {
					$filetype="Canc. mov.";
				}
			}
			if (substr($filetoread,-12) == "ANAGFCTO.txt"){
				$filetype="Anagr.";
			}
			$data=explode("_",$file);
			$gio = substr($data[1],6,2);
			$mes = substr($data[1],4,2);
			$ann = substr($data[1],0,4);
			?>
			<tr>
				<td data-title="Code"><?php echo $file;?></td>
				<td data-title="Giorno"><?php echo $gio,"-",$mes,"-",$ann;?></td>
				<td data-title="Tipo"><?php echo $filetype;?></td>
				<td data-title="Scarica">
				<a href="../camp/getfilesian.php?filename=<?php echo substr($file,0,-4);?>&folder=&ext=txt&company_id=<?php echo $admin_aziend['company_id'];?>">
				<i class="glyphicon glyphicon-file" title="Scarica il file appena generato">
				</i></a></td>
				<?php
				if (!isset($first)){
					?>
					<td align="center">
					<button type="submit" onclick = "this.form.submit();" title="Elimina da GAzie" name="del" value="del" class="btn btn-xs  btn-elimina" >
					<span class="glyphicon glyphicon-trash"></span>
					</button>
          <input type="hidden" name="first" value="<?php echo $file;?>">
					</td>
					<?php if (substr($filetoread,-12) == "OPERREGI.txt"){ ?>
					<td align="center">
					<button type="submit" onclick = "this.form.submit();" title="crea file di cancellazione" name="del" value="delsian" class="btn btn-xs btn-default" >
					<span class="glyphicon glyphicon-trash"></span>
					</button>
          <input type="hidden" name="first" value="<?php echo $file;?>">
					</td>
					<?php }?>
				<?php
					$first=1;
				}
				?>
			</tr>
			<?php
		}
	}
	?>
	</tbody>
	</table>
	</div>
</div>
</form>
<span class="navbar-fixed-bottom" style="left:20%; z-index:2000;"> Registro di campagna è un modulo di Antonio Germani</span>
<?php
require("../../library/include/footer.php");
?>
