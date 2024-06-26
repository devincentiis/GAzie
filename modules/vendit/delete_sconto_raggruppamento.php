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

require_once("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
require_once("../../library/include/header.php");
$script_transl = HeadMain();
if (isset($_GET['clfoco'])) { // attivazione
   $form["codclfoco"] = $_GET['codclfoco'];
   $form["clfoco"] = $_GET['clfoco'];
   $form["codragstat"] = $_GET['codragstat'];
   $form["descrragstat"] = $_GET['descrragstat'];
   $form["sconto"] = $_GET['sconto'];
}
if (isset($_POST['Delete'])) {
   $codcli = $_POST['codclfoco'];
   $codragstat = $_POST['codragstat'];
   $tabellaSconti = $gTables['sconti_raggruppamenti'];
   $query = "delete from $tabellaSconti "
           . "where clfoco = '$codcli' and ragstat = '$codragstat'";
   $righeCancellate = gaz_dbi_query($query, true);
   alert($script_transl['mesg'][5] . $righeCancellate);
   windowsClose();
}
if (isset($_POST['Return'])) {
   header("Location: report_piacon.php");
   exit;
}
?>
<form method="POST">
    <input type="hidden" name="codragstat" value="<?php echo $form["codragstat"] ?>">
    <input type="hidden" name="codclfoco" value="<?php echo $form["codclfoco"] ?>">
    <div align="center"><font class="FacetFormHeaderFont"><?php echo $script_transl['mesg'][0]; ?> </font></div>
    <table class="GazFormDeleteTable">
        <tr>
            <td colspan="2" class="FacetDataTD" style="color: red;">
                <?php echo $script_transl['mesg'][1]; ?>
            </td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD"><?php echo $script_transl['header'][0]; ?> &nbsp;</td>
            <td class="FacetDataTD"> <input type="text"  disabled value="<?php echo $form['clfoco']; ?>">&nbsp;</td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD"><?php echo $script_transl['header'][1]; ?> &nbsp;</td>
            <td class="FacetDataTD"> <input type="text"  disabled value="<?php echo $form['descrragstat']; ?>">&nbsp;</td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD"><?php echo $script_transl['header'][2]; ?>  &nbsp;</td>
            <td class="FacetDataTD"> <input type="text"  disabled value="<?php echo $form['sconto']; ?>">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2" align="right"><?php echo $script_transl['mesg'][2]; ?> &nbsp;
                <input title="Torna indietro" type="submit" name="Return" value="<?php echo $script_transl['mesg'][3]; ?>" onclick="window.close()">&nbsp;
                <input title="Conferma l'eliminazione" type="submit" name="Delete" value="<?php echo $script_transl['mesg'][4]; ?>"&nbsp;
            </td>
        </tr></table>
</form>
<?php
require("../../library/include/footer.php");
?>