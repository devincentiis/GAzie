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

$admin_aziend=checkAdmin();
$message = "Sei sicuro di voler rimuovere ?";
if (isset($_POST['Delete'])) {
    //procedo all'eliminazione della testata e dei righi...
    //cancello la testata
    gaz_dbi_del_row($gTables['tesbro'], "id_tes", intval($_POST['id_tes']));
    //... e i righi
    $rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = ".intval($_GET['id_tes']) ,"id_tes desc");
    while ($a_row = gaz_dbi_fetch_array($rs_righidel)) {
           gaz_dbi_del_row($gTables['rigbro'], "id_rig", $a_row['id_rig']);
           }
    if (isset($_POST['delmovcon'])) { //se e' stata scelta la cancellazione del movimento...
       //cancello la testata del movimento contabile
       gaz_dbi_del_row($gTables['tesmov'], "id_tes", $_POST['id_con']);
       //... e i righi
       $rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigmoc'], "id_tes = ".intval($_POST['id_con']),"id_tes desc");
       while ($a_row = gaz_dbi_fetch_array($rs_righidel)) {
           gaz_dbi_del_row($gTables['rigmoc'], "id_rig", $a_row['id_rig']);
       }
    }
    header("Location: report_salcon.php");
    exit;
    }

if (isset($_POST['Return'])) {
    header("Location: report_broven.php");
    exit;
    }
//recupero i documenti non contabilizzati
$result = gaz_dbi_dyn_query("*", $gTables['tesbro'], "id_tes = ".intval($_GET['id_tes']) ,"id_tes desc");
$rs_righi = gaz_dbi_dyn_query("*", $gTables['rigbro'],"id_tes = ".intval($_GET['id_tes']) ,"id_tes desc");
$numrig = gaz_dbi_num_rows($rs_righi);
$form = gaz_dbi_fetch_array($result);
$anagrafica = new Anagrafica();
$cliente = $anagrafica->getPartner($form["clfoco"]);
$titolo="Elimina la ricevuta di riscossione n.".$form['numdoc'];
require("../../library/include/header.php");
$script_transl=HeadMain();
?>
<form method="POST">
<input type="hidden" name="id_tes" value="<?php print $form['id_tes']; ?>">
<div><font class="text-center text-danger">Attenzione!!! Stai eliminando la ricevuta di riscossione n.<?php echo $form['numdoc']; ?> </font></div>
<table class="GazFormDeleteTable">
<!-- BEGIN Error -->
<tr>
    <td class="FacetDataTDred">
    <?php
    if (! $message == "") {
        print "$message";
    }
    ?>
    </td>
<td class="FacetDataTD">
<input type="checkbox" title="Per stampare la ricevuta seleziona questa checkbox" name="delmovcon">
per eliminare anche i movimenti contabili<br>seleziona questa checkbox.
</td>
</tr>
<!-- END Error -->
<tr>
<td class="FacetFieldCaptionTD">Numero di ID &nbsp;</td><td class="FacetDataTD"><?php print $form["id_tes"] ?>&nbsp;</td>
</tr>
<tr>
<td class="FacetFieldCaptionTD">Numero Ricevuta &nbsp;</td><td class="FacetDataTD"><?php print $form["numdoc"] ?>&nbsp;</td>
</tr>
<tr>
<td class="FacetFieldCaptionTD">Numero movimento contabile &nbsp;</td><td class="FacetDataTD"><?php print $form["id_con"]."</td><td><input type=\"hidden\" name=\"id_con\" value=\"".$form["id_con"]."\"></td>"; ?>
</tr>
<tr>
<td class="FacetFieldCaptionTD">Cliente &nbsp;</td><td class="FacetDataTD"><?php print $cliente["ragso1"] ?>&nbsp;</td>
</tr>
<tr>
<td class="FacetFieldCaptionTD">Num. di righi &nbsp;</td><td class="FacetDataTD"><?php print $numrig ?>&nbsp;</td>
</tr>
<tr>
<td colspan="2" align="right">Se sei sicuro conferma l'eliminazione &nbsp;
<input type="submit" name="Delete" class="btn btn-danger" value="Elimina">&nbsp;
</td>
</tr>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>