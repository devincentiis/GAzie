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
// prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}


if (isset($_POST['rowno'])) { //	Evitiamo errori se lo script viene chiamato direttamente
    require("../../library/include/datlib.inc.php");
    $admin_aziend = checkAdmin();
    require("./lang." . $admin_aziend['lang'] . ".php");
    $script_transl = $strScript['report_municipalities.php'];
    $no = intval($_POST['rowno']);
    $ob = filter_input(INPUT_POST, 'orderby');
    $so = filter_input(INPUT_POST, 'sort');
    $ca = filter_input(INPUT_POST, 'id');
    if (empty($ca)) {
        $where = '1';
    } else {
        $where = "id LIKE '" . $ca . "'";
    }

    $gForm = new informForm();
    $result = gaz_dbi_dyn_query('*', $gTables['municipalities'], $where, $ob . ' ' . $so, $no, PER_PAGE);
    while ($row = gaz_dbi_fetch_array($result)) {
        $provinces = gaz_dbi_get_row($gTables['provinces'], "id", $row["id_province"]);
        ?>
        <tr>
            <td data-title="<?php echo $script_transl["id"]; ?>" class="text-center">
                <a class="btn btn-xs btn-edit" href="../inform/admin_municipalities.php?Update&id=<?php echo $row['id']; ?>" ><i class="glyphicon glyphicon-edit"></i>&nbsp;<?php echo $row['id']; ?></a>
            </td>
            <td data-title="<?php echo $script_transl["name"]; ?>">
                <span class="gazie-tooltip" data-type="product-thumb" data-id="<?php echo $row["name"]; ?>" data-label="<?php echo $row['name']; ?>"><?php echo $row["name"]; ?></span>
            </td>
            <td data-title="<?php echo $script_transl["id_province"]; ?>">
                <span class="gazie-tooltip" data-type="product-thumb" data-id="<?php echo $row["id_province"]; ?>" data-label="<?php echo $row['id_province']; ?>"><?php echo $row['id_province'].'-'.$provinces["name"]; ?></span>
            </td>
            <td data-title="<?php echo $script_transl["postal_code"]; ?>" class="text-center">
                <span class="gazie-tooltip" data-type="product-thumb" data-id="<?php echo $row["postal_code"]; ?>" data-label="<?php echo $row['postal_code']; ?>"><?php echo $row["postal_code"]; ?></span>
            </td>
            <td data-title="<?php echo $script_transl["dialing_code"]; ?>" class="text-center">
                <span class="gazie-tooltip" data-type="product-thumb" data-id="<?php echo $row["dialing_code"]; ?>" data-label="<?php echo $row['dialing_code']; ?>"><?php echo $row["dialing_code"]; ?></span>
            </td>
            <td class="text-center">
                <a class="btn btn-xs  btn-elimina" href="delete_municipalities.php?id=<?php echo $row["id"]; ?>">
                    <i class="glyphicon glyphicon-trash"></i>
                </a>
            </td>
        </tr>
        <?php
    }
    exit();
}
?>
