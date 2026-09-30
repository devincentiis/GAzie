<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
  (https://www.devincentiis.it)
  <https://gazie.sourceforge.net>
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

$anno = date("Y");
$msg = "";

if ( isset($_GET['ritorno']) ) {
   $form['id'] = $_GET['id'];
   $form['clfoco'] = $_GET['clfoco'];
   $form['ritorno']= $_GET['ritorno'];
} else if ( isset($_POST['ritorno']) && !isset($_GET['ritorno'])) {
   $form['id'] = $_POST['id'];
   $form['clfoco'] = $_POST['clfoco'];
   $form['ritorno']= $_POST['ritorno'];
}
/*if (!isset($_POST['ritorno'])) {
   $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}*/

if ( isset($_GET["ass"])  ) {
   gaz_dbi_table_update("assist", array ("id", $_GET['id']), array("idinstallazione" => $_GET['ass']));
   header("Location: ".$_SERVER['HTTP_REFERER']);
}
if ( isset($_POST["ass"]) ) {
   gaz_dbi_table_update("assist", array ("id", $_POST['id']), array("idinstallazione" => $_POST['ass']));
   header("Location: ".$form['ritorno']);
}

$result_install = gaz_dbi_dyn_query($gTables['instal'].".*", $gTables['instal'],"clfoco=".$form["clfoco"], $orderby, $limit, $passo);
$num = gaz_dbi_num_rows($result_install);
if ( $num == 0 ) header ("Location: ".$form['ritorno']);

require("../../library/include/header.php");
$script_transl=HeadMain();
?>
<br>
<center>Sono presenti delle installazioni per questo cliente, vuoi associare l'intervento?</center>
<br>
<form method="POST" name="form" enctype="multipart/form-data">
<table class="Tsmall" align="center">
<tr>
   <td class="FacetFieldCaptionTD">Installazioni per questo cliente </td>
		<td>
      <input type="hidden" name="id" value="<?php echo $form['id']; ?>">
      
      <input type="hidden" name="ritorno" value="<?php echo $form['ritorno']; ?>">
      <select name="ass" style="max-width: 100%;" >
		<?php
         while ( $row = gaz_dbi_fetch_array($result_install) ) {
				//if ( date("H:i", $tNow)==$form['ora_fine'] ) $selected = "selected";
				//else $selected="";
				echo "<option value=\"".$row['id']."\" ".$selected.">".$row['oggetto']." ".$row['descrizione']."</option>";
				//$tNow = strtotime('+30 minutes',$tNow);
			}
		?>
		</select>&nbsp;
      </td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['sqn']; ?></td>
	<td  class="FacetDataTD">
		<input name="Return" type="submit" value="<?php echo $script_transl['return']; ?>">
	</td>
	<td  class="FacetDataTD" align="right">
		<input name="Submit" type="submit" value="Associa !">
	</td>
</tr>
</table>
</form>