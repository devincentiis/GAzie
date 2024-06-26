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
$admin_aziend = checkAdmin();
if (!ini_get('safe_mode')) { //se me lo posso permettere...
    ini_set('memory_limit', '128M');
    gaz_set_time_limit(0);
}
$msg = '';
$gForm = new GAzieForm();
function getFAEunpacked($date=""){// prende tutte le fae di acquisto non impacchettate
	global $gTables, $admin_aziend;
	$from = $gTables['tesdoc'];
	$where = "(fattura_elettronica_zip_package IS NULL OR fattura_elettronica_zip_package = '') AND (tipdoc LIKE 'AF_') AND (fattura_elettronica_original_name LIKE '______%')";
	if (intval($date>0)){
		$where .= " AND datfat <= '". $date ."'";
	}
	$orderby = "datfat ASC, protoc ASC";
	$resultFAE = gaz_dbi_dyn_query("*", $from, $where, $orderby);	
	return gaz_dbi_fetch_all($resultFAE);
}
function getFAEpacked($name){// prende le fae di acquisto impacchettate con il nome passato in $name
	global $gTables, $admin_aziend;
	$from = $gTables['tesdoc'];
	$where = "(fattura_elettronica_zip_package = '".$name."') AND (tipdoc LIKE 'AF_')";
	$orderby = "datfat ASC, protoc ASC";
	$resultFAE = gaz_dbi_dyn_query("*", $from, $where, $orderby);	
	return gaz_dbi_fetch_all($resultFAE);
}

if (isset($_POST['reset'])){
	unset($_POST);
	$msg="";
	
}
if (isset($_GET['name'])){
	$resultFAE=getFAEpacked(substr($_GET['name'],0,100));	
}elseif (!isset($_POST['packet'])){
	$resultFAE=getFAEunpacked();
} else{
	$resultFAE=getFAEunpacked($_POST['this_date_Y']."-".$_POST['this_date_M']."-".$_POST['this_date_D']);
}

if (!isset($_POST['packet']) && !isset($_GET['name'])) { //al primo accesso allo script
			
	if(count($resultFAE)>0){
		$form['latest_date_Y'] = substr($resultFAE[count($resultFAE)-1]['datfat'], 0, 4);
		$form['latest_date_M'] = substr($resultFAE[count($resultFAE)-1]['datfat'], 5, 2);
		$form['latest_date_D'] = substr($resultFAE[count($resultFAE)-1]['datfat'], 8, 2);
		
		$form['first_date_Y'] = substr($resultFAE[0]['datfat'], 0, 4);
		$form['first_date_M'] = substr($resultFAE[0]['datfat'], 5, 2);
		$form['first_date_D'] = substr($resultFAE[0]['datfat'], 8, 2);
	}	
	
} else {    // accessi successivi o se c'è una richiesta esterna GET	
	
	if (count($resultFAE) > 0 && !isset($_GET['email'])) {
		CreateZipFAEacq($resultFAE);		
	}
	if (count($resultFAE) > 0 && isset($_GET['email'])){
		CreateZipFAEacq($resultFAE,$_GET['email']);
		exit;
	}
	if (isset($_GET['email'])){
		exit;
	}
}

if(count($resultFAE)==0){
	$msg="Non ci sono fatture da impacchettare";
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup'));

echo "<script type=\"text/javascript\">
var cal = new CalendarPopup();
var calName = '';
function setMultipleValues(y,m,d) {
     document.getElementById(calName+'_Y').value=y;
     document.getElementById(calName+'_M').selectedIndex=m*1-1;
     document.getElementById(calName+'_D').selectedIndex=d*1-1;
}
function setDate(name) {
  calName = name.toString();
  var year = document.getElementById(calName+'_Y').value.toString();
  var month = document.getElementById(calName+'_M').value.toString();
  var day = document.getElementById(calName+'_D').value.toString();
  var mdy = month+'/'+day+'/'+year;
  cal.setReturnFunction('setMultipleValues');
  cal.showCalendar('anchor', mdy);
}
</script>
";
?>

<form method="POST">
	<div align="center" class="FacetFormHeaderFont"> Impacchetta fatture di acquisto
	</div>
	<table class="Tsmall">
		<?php
		
		if($msg!==""){
			?>
			<tr>
				<td>
				<p><?php echo $msg; ?></p>
				</td>
			</tr>
			<?php
		}else{
			?>
			<tr>
				<td class="text-right">Dal</td>
				<td>
				<?php
				echo $form['first_date_D'],"-", $form['first_date_M'],"-", $form['first_date_Y'];
				?>
				</td>
			</tr>
			<tr>
				<td class="text-right">Fino al </td>
				<td>
				<?php
				$gForm->CalendarPopup('this_date', $form['latest_date_D'], $form['latest_date_M'], $form['latest_date_Y'], 't', 1);
				?>
				</td>
			</tr>
			<?php
		}
		?>
		<tr class="FacetDataTD">
			<td><input type="submit" name="reset" value="Reset">
			</td>
			<?php
			if(count($resultFAE)>0){
				?>
				<td align="right"><input class="btn btn-warning" type="submit" name="packet" value="Crea pacchetto .zip">					
				</td>
				<?php
			}
			?>
		</tr>		
	</table>
</form>
<?php
require("../../library/include/footer.php");
?>