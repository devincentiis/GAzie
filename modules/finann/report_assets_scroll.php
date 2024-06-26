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
// prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}
if (isset($_POST['getresult'])) { //	Evitiamo errori se lo script viene chiamato direttamente
    require("../../library/include/datlib.inc.php");
    $admin_aziend = checkAdmin();
    require("./lang.".$admin_aziend['lang'].".php");
    $script_transl = $strScript['report_assets.php'];
    $no = intval($_POST['getresult']);
    $result = gaz_dbi_dyn_query('*', $gTables['assets'], 'type_mov = 1', 'id', $no, PER_PAGE);
    while ($row = gaz_dbi_fetch_array($result)) {
        $tesmov = gaz_dbi_get_row($gTables['tesmov'], "id_tes", $row['id_movcon']);
        $anagrafica = new Anagrafica();
        $fornitore = $anagrafica->getPartner($tesmov['clfoco']);
        ?>
        <tr>
            <td data-title="ID">
                <a class="btn btn-xs btn-edit" href="../acquis/admin_assets.php?Update&id=<?php echo $row['id']; ?>" ><i class="glyphicon glyphicon-edit"></i>&nbsp;<?php echo $row['id']; ?></a>
            </td>
            <td data-title="<?php echo $script_transl["datreg"]; ?>">
                <?php echo gaz_format_date($tesmov["datreg"]); ?>
            </td>
            <td data-title="<?php echo $script_transl["descri"]; ?>">
                <?php echo $row["descri"]; ?>
            </td>
            <td data-title="<?php echo $script_transl["clfoco"]; ?>">
                <?php echo $fornitore["descri"]; ?>
            </td>
            <td data-title="<?php echo $script_transl["amount"]; ?>" class="text-right">
                <?php echo gaz_format_number($row["a_value"] * $row["quantity"]); ?>
            </td>
            <td data-title="<?php echo $script_transl["valamm"]; ?>"  class="text-right">
                <?php echo round($row["valamm"],1); ?>%
            </td>
        </tr>
        <?php
    }
    exit();
}
?>
