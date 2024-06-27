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
                            <a href="#" class="orby" data-order="user_name">
                                <?php echo $script_transl["user_name"]; ?>
                            </a>
                        </th>
                        <th>
                            <a href="#" class="orby" data-order="cognome">
                                <?php echo $script_transl["Cognome"]; ?>
                            </a>
                        </th>
                        <th>
                            <a href="#" class="orby" data-order="nome">
                                <?php echo $script_transl["Nome"]; ?>
                            </a>
                        </th>
                    </tr>      
                </thead>    
                <tbody id="all_rows">
                    <?php
                    $rs = gaz_dbi_dyn_query('*', $gTables['admin'],'Abilit > 8');
                    while ($r = gaz_dbi_fetch_array($rs)) {
                        echo "<tr class=\"FacetDataTD\">";
                        echo "<td title=\"" . $script_transl['update'] . "\"><a class=\"btn btn-xs btn-default\" href=\"../config/admin_utente.php?user_name=" . $r["user_name"] . "&Update\">" . $r["user_name"] . " </a> &nbsp</td>";
                        echo "<td>" . $r["user_lastname"] . " &nbsp;</td>";
                        echo "<td>" . $r["user_firstname"] . " &nbsp;</td>";
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