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
$admin_aziend = checkAdmin(9);
require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new schoolForm();

function getGravatar($email,$d = 'mm') {
    $url = 'https://www.gravatar.com/avatar/';
    $url .= md5(strtolower(trim($email))).'?d='.$d;
    return $url;
}

?>
<form method="POST" id="form">
    <div class="text-center"><b><?php echo $script_transl['title']; ?></b></div>
    <div class="panel panel-default">
        <div id="gaz-responsive-table"  class="container-fluid">
            <table class="table table-responsive table-striped table-condensed cf">
                <thead>
                    <tr class="bg-success">              
                        <th>
                            <a href="#" class="orby" data-order="id">
                                ID
                            </a>
                        </th>
                        <th>
                            <a href="#" class="orby" data-order="classe">
                                <?php echo $script_transl["classe"]; ?>
                            </a>
                        </th>
                        <th>
                            <a href="#" class="orby" data-order="Cognome">
                                <?php echo $script_transl["Cognome"]; ?>
                            </a>
                        </th>
                        <th>
                            <a href="#" class="orby" data-order="Nome">
                                <?php echo $script_transl["Nome"]; ?>
                            </a>
                        </th>
                        <th>
                            <a href="#" class="orby" data-order="user_name">
                                <?php echo $script_transl["user_name"]; ?>
                            </a>
                        <th>
                            <a href="#" class="orby" data-order="email">
                                <?php echo $script_transl["email"]; ?>
                            </a>
                        </th>
                        <th>
                            <a href="#" class="orby" data-order="telephone">
                                <?php echo $script_transl["telephone"]; ?>
                            </a>
                        </th>
                        <th>
                            <a href="#" class="orby" data-order="active_head">
                                <?php echo $script_transl["active_head"]; ?>
                            </a>
                        </th>
                        <th class="orby">
                            <?php echo $script_transl["delete"]; ?>
                        </th>
                    </tr>      
                </thead>    
                <tbody id="all_rows">
                    <?php
                    $result = gaz_dbi_dyn_query('*', $gTables['students'],1,'student_classroom_id DESC, student_id DESC');
                    while ($r = gaz_dbi_fetch_array($result)) {
                        $cr = gaz_dbi_get_row($gTables['classroom'], "id", $r["student_classroom_id"]);
                        echo '<tr class="';
                        if ($r["student_active"] == 1) {
                            echo 'info';
                        } else {
                            echo 'warning';
                        }
                        echo '" title="' . $r["student_name"] . '">';
                        echo "<td title=\"" . $script_transl['update'] . "\">" . $r["student_id"] . "  &nbsp</td>";
                        echo "<td>" . $cr["classe"] . " " . $cr["sezione"] . " " . $cr["anno_scolastico"] . "/" . substr($cr["anno_scolastico"] + 1, 2, 2) . " &nbsp;</td>";
                        echo "<td>" . $r["student_lastname"] . " &nbsp;</td>";
                        echo "<td>" . $r["student_firstname"] . " &nbsp;</td>";
                        echo "<td>" . $r["student_name"] . " &nbsp;</td>";
                        echo '<td><img src="'.  getGravatar($r["student_email"]).'" height="32"> ' . $r["student_email"] . " </td>";
                        echo "<td>" . $r["student_telephone"] . " </td>";
                        echo '<td class="';
                        if ($r["student_active"] == 1) {
                            echo 'info';
                        } else {
                            echo 'danger';
                        }
                        echo '" >' . $script_transl['active'][$r["student_active"]] . " </td>";
                        echo '<td><a class="btn btn-xs  btn-elimina" href="delete_student.php?id=' . $r["student_id"] . '"><i class="glyphicon glyphicon-trash"></i></a></td>';
                        echo "</tr>";
                    }
                    ?>
                </tbody>     
            </table>
        </div>  
    </div>
</form>
<?php
require("../../library/include/footer.php");
?>