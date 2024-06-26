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
  scriva   alla   Free  Software Foundation,  Inc.,   59
  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
if (isset($_POST['Delete'])) {
    gaz_dbi_del_row($gTables['letter'], 'id_let', $_GET['id_let']);
    header("Location: report_letter.php");
    exit;
} else {
    $form = gaz_dbi_get_row($gTables['letter'], "id_let", $_GET['id_let']);
    if ($form['adminid'] != $_SESSION["user_name"]) { //non � l'utente che ha scritto la lettera
        header("Location: report_letter.php");
        exit;
    }
    $anagrafica = new Anagrafica();
    $partner = $anagrafica->getPartner($form['clfoco']);
}

if (isset($_POST['Return'])) {
    header("Location: report_letter.php");
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain('', '', 'admin_letter');
print "<form method=\"POST\">\n";
print "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['warning'] . '!!! ' . $script_transl['delete'] . $script_transl['title'] . "</div>\n";
print "<table class=\"Tmiddle table-striped\" align=\"center\">\n";
print "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[3] . "</td><td class=\"FacetDataTD\">" . $form['numero'] . "</td></tr>";
print "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[1] . "</td><td class=\"FacetDataTD\">" . $form['write_date'] . "</td></tr>\n";
print "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[2] . "</td><td class=\"FacetDataTD\">" . $partner['ragso1'] . "</td></tr>\n";
print "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[4] . "</td><td class=\"FacetDataTD\">" . $form['oggetto'] . "</td></tr>\n";
print "<td align=\"right\"><input type=\"submit\" name=\"Return\" value=\"" . $script_transl['return'] . "\"></td><td align=\"right\"><input type=\"submit\" name=\"Delete\" value=\"" . strtoupper($script_transl['delete']) . "!\"></td></tr>";
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>