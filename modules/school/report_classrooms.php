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
$admin_aziend = checkAdmin(9);
require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new schoolForm();
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
                            <a href="#" class="orby" data-order="sezione">
                                <?php echo $script_transl["sezione"]; ?>
                            </a>
                        </th>
                        <th>
                            <a href="#" class="orby" data-order="anno_scolastico">
                                <?php echo $script_transl["anno_scolastico"]; ?>
                            </a>
                        </th>
                        <th>
                            <a href="#" class="orby" data-order="teacher">
                                <?php echo $script_transl["teacher"]; ?>
                            </a>
                        </th>
                        <th class="orby">
                            <?php echo $script_transl["delete"]; ?>
                        </th>
                    </tr>      
                </thead>    
                <tbody id="all_rows">
                    <?php
                    $result = gaz_dbi_dyn_query('*', $gTables['classroom'],1,'id DESC');
                    while ($r = gaz_dbi_fetch_array($result)) {
                        $te = gaz_dbi_get_row($gTables['admin'], "user_name", $r["teacher"]);
                        echo '<tr class="FacetDataTD" title="'.$r["title_note"] .'">';
                        echo "<td title=\"" . $script_transl['update'] . "\"><a class=\"btn btn-xs btn-default\" href=\"admin_classroom.php?id=" . $r["id"] . "&Update\">" . $r["id"] . " </a> &nbsp</td>";
                        echo "<td>" . $r["classe"] . " &nbsp;</td>";
                        echo "<td>" . $r["sezione"] . " &nbsp;</td>";
                        echo "<td>" . $r["anno_scolastico"] . "/" . substr($r["anno_scolastico"] + 1, 2, 2) . " </td>";
                        echo "<td>" . $te["user_firstname"] . " " . $te["user_lastname"] . " &nbsp;</td>";
                        echo '<td><a class="btn btn-xs  btn-elimina" href="delete_classroom.php?id=' . $r["id"] . '"><i class="glyphicon glyphicon-trash"></i></a></td>';
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