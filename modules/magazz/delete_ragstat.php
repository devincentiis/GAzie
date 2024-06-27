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
$titolo="Cancella il raggruppamento statistico";
if (isset($_POST['Delete']))
    {
        $result = gaz_dbi_del_row($gTables['ragstat'], "codice", $_POST['codice']);
        header("Location: report_ragstat.php");
        exit;
    }

if (isset($_POST['Return']))
        {
        header("Location: report_ragstat.php");
        exit;
        }

if (!isset($_POST['Delete']))
    {
    $codice= $_GET['codice'];
    $form = gaz_dbi_get_row($gTables['ragstat'], "codice", $codice);
    }

require("../../library/include/header.php"); HeadMain();
?>
<form method="POST">
<input type="hidden" name="codice" value="<?php print $codice?>">
<div><font class="text-center text-danger">Attenzione!!! Eliminazione Raggruppamento Statistico N.<?php print $codice; ?> </font></div>
<table class="GazFormDeleteTable">
<tr>
<td colspan="2" class="FacetDataTDred">
<?php
if (! $message == "")
    {
    print "$message";
    }
?>
</td>
</tr>
<tr>
<tr>
<td class="FacetFieldCaptionTD">Numero raggruppamento statistico &nbsp;</td>
<td class="FacetDataTD"> <?php print $form["codice"]; ?>&nbsp;</td>
</tr>
<tr>
<td class="FacetFieldCaptionTD">Descrizione &nbsp;</td>
<td class="FacetDataTD"><?php print $form["descri"] ?>&nbsp;</td>
</tr>
<tr>
<td class="FacetFieldCaptionTD">Annotazioni &nbsp;</td>
<td class="FacetDataTD"><?php print $form["annota"] ?>&nbsp;</td>
</tr>
<td colspan="2" align="right">Se sei sicuro conferma l'eliminazione &nbsp;
<input type="submit" name="Delete" class="btn btn-danger" value="Elimina">&nbsp;
</td>
</tr>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>