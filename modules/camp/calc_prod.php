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
//requisito di gazie
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
$msg = "";$form['campo_impianto']="";

if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}
if (isset($_POST['Return'])) {
    header("Location: ".$_POST['ritorno']);
    exit;
}

if (isset($_POST['print'])) {
	// da fare


 header("Location: ".$_POST['ritorno']);
          exit;
}

if (isset($_POST['id_produzione'])){
	$form['id_produzione']=$_POST['id_produzione'];
} else {
	$form['id_produzione']="";
	$form['campo_impianto']="";
}



require("../../library/include/header.php");
$script_transl=HeadMain();
?>
<!-- Antonio Germani - Inizio form   -->
<form method="POST" enctype="multipart/form-data">
<input type="hidden" value="<?php echo $_POST['ritorno']; ?>" name="ritorno">
<div align="center"><font class="FacetFormHeaderFont">Calcolo del costo della produzione</font></div>
<table border="0" cellpadding="3" cellspacing="1" class="FacetFormTABLE" align="center">
<?php
if (!empty($msg)) {
    $message = "";
    $rsmsg = array_slice( explode('+',chop($msg)),0,-1);
    foreach ($rsmsg as $value){
            $message .= $script_transl['error']."! -> ";
            $rsval = explode('-',chop($value));
            foreach ($rsval as $valmsg){
                    $message .= $script_transl[$valmsg]." ";
            }
            $message .= "<br />";
    }
    echo '<tr><td colspan="5" class="FacetDataTDred">'.$message."</td></tr>\n";
}
?>
<tr>
<td colspan="2" class="FacetDataTDred">

</td>
</tr>
<!-- Antonio Germani - Inizio selezione produzione  -->
<?php
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[0] . "</td><td class=\"FacetDataTD\">\n";
echo "<select name=\"id_produzione\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
echo "<option value=\"\">-------------</option>\n";
$result = gaz_dbi_dyn_query("*", $gTables['orderman'],"order_type = 'AGR' AND stato_lavorazione < 9");

while ($row = gaz_dbi_fetch_array($result)) {
    $selected = "";
    if ($form['id_produzione'] == $row['id']) {
        $selected = " selected ";
    }
    echo "<option value=\"" . $row['id'] . "\"" . $selected . ">" . $row['id'] . " - " . $row['description'] . " - Periodo di coltivazione ". gaz_format_date ($row['start_work']) ." - ". gaz_format_date ($row['end_work']) ."</option>\n";

}
echo "</select>&nbsp;";
// prendo la produzione
$item = gaz_dbi_get_row($gTables['orderman'], "id", $form['id_produzione']);
// prendo il campo di coltivazione
$item2 = gaz_dbi_get_row($gTables['campi'], "codice", ($item)?$item['campo_impianto']:0);
?>
<!-- fine selezione produzione  -->
<?php if (isset($_POST['id_produzione'])){
	echo "<tr><td colspan=\"2\" class=\"FacetFieldCaptionTD\">", $script_transl[1], ($item)?$item['description']:'' . " " . $script_transl[2] ."-". gaz_format_date ($item['start_work']), " " . $script_transl[3], ($item)?$item['campo_impianto']:0, " ", ($item2)?$item2['descri']:'' , "</td></tr>";
$costo_produzione=0;
$query="SELECT * FROM ".$gTables['movmag']." WHERE ".'id_orderman' . " = " . "'".$form['id_produzione']."'" ;
	$res = gaz_dbi_query($query);

while($row = $res->fetch_assoc()){
	if ($row['operat']<1) { // Antonio Germani evito che ci entri un acquisto > solo per sicurezza poi si potrà pure togliere <
	$imp_riga=(($row['prezzo']-(($row['prezzo']*$row['scorig'])/100))*$row['quanti']);
	$item3 = gaz_dbi_get_row($gTables['caumag'], "codice", $row['caumag']);
	echo "<tr><td colspan=\"2\" class=\"FacetFieldCaptionTD\">".gaz_format_date ($row['datdoc'])." ".$item3['descri']." ".$row['artico']." € ".gaz_format_number($imp_riga,2)." </td></tr>";
	$costo_produzione=$costo_produzione+$imp_riga;
	}
}
echo "<tr><td colspan=\"2\" class=\"FacetDataTD\" align=\"right\">".$script_transl[4] . " ". gaz_format_number($costo_produzione) . " " . $admin_aziend['symbol'] ."</td></tr>";
}
?>
<tr>
<td class="FacetDataTD" align="left">
<input type="submit" name="Return" value="<?php echo $script_transl['return']; ?>">
</td>
<td class="FacetDataTD" align="right">
<!-- da fare
<input type="submit" accesskey="i" name="print" id="preventDuplicate" onClick="chkSubmit();" value="<?php echo ucfirst($script_transl['print']);?>">
-->
</td></tr>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
