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
$admin_aziend=checkAdmin();
$rs = gaz_dbi_dyn_query($gTables['agenti_forn'].".*,".$gTables['clfoco'].".descri, ".$gTables['clfoco'].".codice", $gTables['agenti_forn']." LEFT JOIN ".$gTables['clfoco']." on ".$gTables['agenti_forn'].".id_fornitore = ".$gTables['clfoco'].".codice",  $gTables['agenti_forn'].".id_agente = ".intval($_GET['id_agente']),$gTables['agenti_forn'].".id_agente ASC",0,1);
$form = gaz_dbi_fetch_array($rs);
if (!isset($_POST['ritorno'])) {
        $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}
if (isset($_POST['Delete'])) {
    //procedo all'eliminazione della testata e dei righi...
    //cancello la testata
    gaz_dbi_del_row($gTables['agenti_forn'], 'id_agente', intval($_POST['id_agente']));
    //... e i righi
    gaz_dbi_del_row($gTables['provvigioni'], 'id_agente', intval($_POST['id_agente']));
    header("Location: ".$_POST['ritorno']);
    exit;
    }

if (isset($_POST['Return'])) {
    header("Location: ".$_POST['ritorno']);
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain('','','admin_agenti_forn');
?>
<form method="POST">
<input type="hidden" name="id_agente" value="<?php print $form['id_agente']; ?>">
<input type="hidden" name="ritorno" value="<?php print $_POST['ritorno']; ?>">
<div align="center" class="FacetFormHeaderFont">
<?php echo $script_transl['warning'].'!!! '.$script_transl[18] ; ?></div>
<table class="Tsmall" align="center">
<tr>
<?php
echo "<td class=\"FacetFieldCaptionTD\">$script_transl[2] : </td>
     <td class=\"FacetDataTD\">\n".$form['id_agente']."</td>";
?>
</tr>
<tr>
<?php
echo "<td class=\"FacetFieldCaptionTD\">$script_transl[3] : </td>
     <td class=\"FacetDataTD\">\n".$form['descri']."</td>";
?>
</tr>
<tr>
<?php
echo "<td class=\"FacetFieldCaptionTD\">$script_transl[6] : </td>
     <td class=\"FacetDataTD\">\n".$form['base_percent']."</td>";
?>
</tr>
<tr>
    <td align="right">
<?php
echo '<input type="submit" accesskey="r" name="Return" value="'.$script_transl['return'].'"></td><td>
     '.ucfirst($script_transl['safe']);
echo ' <input type="submit" accesskey="d" name="Delete" value="'.$script_transl['delete'].'">';
?>
</td>
</tr>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>