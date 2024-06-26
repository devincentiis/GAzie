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
$titolo = "Eliminazione comune";
$message = "Sei sicuro di voler rimuovere ?";
$id = filter_input(INPUT_GET, 'id');
if (isset($_POST['Delete'])) {
    $result = gaz_dbi_del_row($gTables['municipalities'], "id", filter_input(INPUT_POST, 'id'));
    header("Location: report_municipalities.php");
    exit;
} else {
    $form = gaz_dbi_get_row($gTables['municipalities'], "id", $id);
}

if (isset($_POST['Return'])) {
    header("Location: report_municipalities.php");
    exit;
}


require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<form method="POST" action="<?php print $_SERVER['PHP_SELF'] . "?id=" . $id; ?>" >
    <input type="hidden" name="id" value="<?php print $id ?>">
    <div align="center" font class="FacetFormHeaderFont">Attenzione!!! Eliminazione Comune ID: <?php print $id; ?> </div>
    <table class="GazFormDeleteTable">
        <tr>
            <td colspan="2" class="FacetDataTDred">
                <?php
                if (!$message == "") {
                    print "$message";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD">ID comune</td>
            <td class="FacetDataTD"><?php print $id ?> </td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD">Descrizione  </td>
            <td class="FacetDataTD"><?php print $form["name"] ?> </td>
        </tr>
        <td colspan="2" align="right">Se sei sicuro conferma l'eliminazione  
            <!-- BEGIN Button Return --><input type="submit" name="Return" value="Indietro"><!-- END Button Return --> 
            <input type="submit" name="Delete" class="btn btn-danger" value="Elimina"> 
        </td>
        </tr>
    </table>
</form>
<?php
require("../../library/include/footer.php");
?>