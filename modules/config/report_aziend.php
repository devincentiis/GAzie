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
require('../../library/include/datlib.inc.php');
if (isset($_GET['change_co'])){
    changeEnterprise(intval($_GET['change_co']));
    header("Location: ../root/admin.php");
    exit;
}
$admin_aziend=checkAdmin(7);
require('../../library/include/header.php');
$script_transl = HeadMain();
$table=$gTables['aziend'].' LEFT JOIN '. $gTables['admin_module'].' ON '.$gTables['admin_module'].'.company_id = '.$gTables['aziend'].'.codice';
$where=$gTables['admin_module'].".adminid='".$admin_aziend["user_name"]."' GROUP BY company_id";
$rs = gaz_dbi_dyn_query ('*',$table,$where, $orderby, $limit, $passo);
echo '<div align="center" class="FacetFormHeaderFont">'.$script_transl['title'].'</div>';
echo '<div class="table-responsive"><table class="Tlarge table table-striped">';
// creo l'array (header => campi) per l'ordinamento dei record
$headers_co = array  (
            $script_transl['codice'] => "codice",
            $script_transl['ragso1'] => "ragso1",
            $script_transl['e_mail'] => "e_mail",
            $script_transl['telefo'] => "telefo",
            $script_transl['regime'] => "regime",
            $script_transl['ivam_t'] => "ivam_t"
            );
$linkHeaders = new linkHeaders($headers_co);
$linkHeaders -> output();
$recordnav = new recordnav($table,$where, $limit, $passo);
$recordnav -> output();
echo '<form method="GET" name="myform"><input type="hidden" name="change_co" value="">';
?>
<?php
while ($r = gaz_dbi_fetch_array($rs)) {
  if ($r['codice']==$_SESSION['company_id']) {
    echo '<tr>
          <td class="bg-success text-center"><a class="btn btn-xs btn-edit" href="admin_aziend.php" title="'.$script_transl['update'].'" ><i class="glyphicon glyphicon-edit"></i>&nbsp;'.$r["codice"].'</a></td>
          <td class="bg-success" title="'.$r["indspe"].' '.$r["citspe"].' ('.$r["prospe"].')"><b class="bg-warning "> <a href="admin_aziend.php" title="'.$script_transl['update'].'" >'.$r["ragso1"].' '.$r["ragso2"].' </a></b><span class="text-danger">  ( stai lavorando su questa) </span></td>';
  } else {
    $style='';
    echo '<tr>
         <td class="text-center"><div style="cursor:pointer;" onclick="myform.change_co.value=\''.$r['codice'].'\'; myform.submit();" >'.$r["codice"].'</div></td>
         <td title="CAMBIA E LAVORA SU QUESTA"><div class="clickarea" style="cursor:pointer;" onclick="myform.change_co.value=\''.$r['codice'].'\'; myform.submit();" >'.$r["ragso1"].' '.$r["ragso2"].' </div></td>';
  }
  echo '<td class="text-center">'.$r['e_mail'].' </td>
        <td class="text-center">'.$r['telefo'].' </td>
        <td class="text-center">'.$script_transl['regime_value'][$r["regime"]].'</td>
        <td class="text-center">'.$script_transl['ivam_t_value'][$r["ivam_t"]].'</td>
        </tr>';
}
?>
</form>
</table></div>
<?php
require('../../library/include/footer.php');
?>
