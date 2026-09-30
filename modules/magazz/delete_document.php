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
if (isset($_POST['Delete'])) {
    gaz_dbi_del_row($gTables['files'], "id_doc", intval($_POST['id_doc']));
    $fn = DATA_DIR . "files/" . intval($_POST['id_doc']) . '.' . substr($_POST['ext'],0,4);
    unlink($fn);
    header("Location: browse_document.php");
    exit;
}

if (isset($_POST['Return'])) {
    header("Location: browse_document.php");
    exit;
}

if (!isset($_POST['Delete'])) {
    $id_doc= intval($_GET['id_doc']);
    $form = gaz_dbi_get_row($gTables['files'], "id_doc", $id_doc);
}
require("../../library/include/header.php");
$script_transl=HeadMain('','','browse_document');
?>
<form method="POST">
<input type="hidden" name="id_doc" value="<?php print intval($_GET['id_doc'])?>">
<input type="hidden" name="ext" value="<?php echo $form["extension"]; ?>">
<div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['warning'].'!!! '.$script_transl['delete'].$script_transl['title'].' ID n.'.intval($_GET['id_doc']); ?> </div>
<table class="GazFormDeleteTable">
  <tr>
    <td class="FacetFieldCaptionTD"><?php echo $script_transl['table_name_ref']; ?></td>
        <td class="FacetDataTD" colspan=2> <?php print $form["table_name_ref"]; ?>&nbsp;</td>
  </tr>
  <tr>
    <td class="FacetFieldCaptionTD"><?php echo $script_transl['item']; ?></td>
        <td class="FacetDataTD" colspan=2> <?php print $form["item_ref"]; ?>&nbsp;</td>
  </tr>
  <tr>
    <td class="FacetFieldCaptionTD"><?php echo $script_transl['note']; ?></td>
    <td class="FacetDataTD" colspan=2><?php print $form["title"] ?>&nbsp;</td>
  </tr>
 <tr>
    <td class="FacetFieldCaptionTD">File: </td>
    <td class="FacetDataTD" colspan=2><?php print DATA_DIR.'files/'.$form['id_doc'].'.'.$form["extension"] ?>&nbsp;</td>
  </tr>
<tr>
 <td align="right">
<?php
echo '<input type="submit" accesskey="r" name="Return" value="'.$script_transl['return'].'"></td><td colspan="2">
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