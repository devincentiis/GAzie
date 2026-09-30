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
if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}

if (isset($_GET['cod'])) { //sto eliminando una intera installazione
    $result = gaz_dbi_dyn_query("*", $gTables['instal'], "codice = " . intval($_GET['id']));
    $row = gaz_dbi_fetch_array($result);
} else if (isset($_GET['id'])) { //sto eliminando un singolo intervento
    $result = gaz_dbi_dyn_query("*", $gTables['assist'], "id = " . intval($_GET['id']));
    $row = gaz_dbi_fetch_array($result);
} else {
    header("Location: " . $_POST['ritorno']);
    exit;
}

if (!$row) {
    header("Location: " . $_POST['ritorno']);
    exit;
}

if (isset($_POST['Delete'])) {
	if (isset($_GET['cod'])) { //sto eliminando una intera installazione
		gaz_dbi_del_row($gTables['assist'], "idinstallazione", $row['id']);
		gaz_dbi_del_row($gTables['instal'], "id", $row['id']);
	} else if (isset($_GET['id'])) { //sto eliminando un singolo intervento
		gaz_dbi_del_row($gTables['assist'], "id", $row['id']);
	}
    header("Location: " . $_POST['ritorno']);
    exit;
}

if (isset($_POST['Return'])) {
    header("Location: " . $_POST['ritorno']);
    exit;
}

$anagrafica = new Anagrafica();
$cliente = $anagrafica->getPartner($row['clfoco']);
require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<form method="POST">
    <input type="hidden" name="ritorno" value="<?php print $_POST['ritorno']; ?>">
    <div align="center"><font class="FacetFormHeaderFont"><?php print $script_transl['title'] . ' n.' . $row['codice']; ?> </font></div>
    <table class="GazFormDeleteTable">
        <!-- BEGIN Error -->
        <tr>
            <td colspan="2" class="FacetDataTDred">
                <?php
                    print $script_transl['alert'];
                ?>
            </td>
        </tr>
        <!-- END Error -->
        <tr>
            <td class="FacetFieldCaptionTD">ID &nbsp;</td><td class="FacetDataTD"><?php print $row["id"] ?>&nbsp;</td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD">N. &nbsp;</td><td class="FacetDataTD"><?php print $row["codice"] ?>&nbsp;</td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD"><?php print $script_transl['oggetto']; ?> &nbsp;</td><td class="FacetDataTD"><?php print $row["oggetto"] ?>&nbsp;</td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD"><?php print $script_transl['descrizione']; ?> &nbsp;</td><td class="FacetDataTD"><?php print $row["descrizione"] ?>&nbsp;</td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD"><?php print $script_transl['cliente']; ?> &nbsp;</td><td class="FacetDataTD"><?php print $cliente["ragso1"] ?>&nbsp;</td>
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