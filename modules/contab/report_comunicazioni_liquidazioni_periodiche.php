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
$admin_aziend = checkAdmin();
$gazTimeFormatter->setPattern('MMMM');

require("../../library/include/header.php");
$script_transl = HeadMain();

if (isset($_POST['hidden_req'])) { // accessi successivi allo script
    $form['hidden_req'] = $_POST["hidden_req"];
    $form['ritorno'] = $_POST['ritorno'];
} else {
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
}
?>
<form method="POST">
    <input type="hidden" value="<?php echo $form['hidden_req']; ?>" name="hidden_req" />
    <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno" />
    <div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['title']; ?></div>
    <div class="tab-content">
        <div id="lista" class="tab-pane fade in active">
            <div class="table-responsive">

                <table class="Tlarge table table-striped table-bordered table-condensed">
                    <tr>
                        <th class="FacetFieldCaptionTD">ID</th>
                        <th class="FacetFieldCaptionTD"><?php echo $script_transl['periodo']; ?></th>
                        <th class="FacetFieldCaptionTD">File XML</th>
                        <th class="FacetFieldCaptionTD"><?php echo $script_transl['vp4']; ?></th>
                        <th class="FacetFieldCaptionTD"><?php echo $script_transl['vp5']; ?></th>
                        <th class="FacetFieldCaptionTD"><?php echo $script_transl['vp7-13']; ?></th>
                        <th class="FacetFieldCaptionTD"><?php echo $script_transl['vp14']; ?></th>
                            <?php
                            $result = gaz_dbi_dyn_query('*', $gTables['liquidazioni_iva'], 1, 'anno DESC, mese_trimestre DESC');
                            while ($row = gaz_dbi_fetch_array($result)) {
                                if ($row["periodicita"] == 'T') {
                                    $descri_per = $script_transl['periodo_val'][$row['mese_trimestre']] . ' ' . $script_transl['periodicita_val'][$row["periodicita"]] . ' ' . $row["anno"];
                                } else {
                                    $descri_per = $script_transl['periodicita_val'][$row["periodicita"]] . ' ' . $gazTimeFormatter->format(new DateTime("2000-".$row['mese_trimestre']."-01"))  . ' ' . $row["anno"];
                                }
                                $altro=round($row['vp7'] - $row['vp8'] - $row['vp9'] - $row['vp10'] - $row['vp11'] + $row['vp12'] - $row['vp13'], 2);
                                echo "<tr class=\"FacetDataTD\">";
                                echo "<td><a class=\"btn btn-xs btn-default\" href=\"comunicazione_liquidazioni_periodiche.php?id=" . $row["id"] . "&Update\"><i class=\"glyphicon glyphicon-folder-open\"></i>&nbsp;&nbsp;" . $row["id"] . "</a> &nbsp</td>";
                                echo "<td align=\"center\">" . $descri_per . " &nbsp;</td>";
                                echo "<td align=\"center\">" . $row['nome_file_xml'] . ' <a class="btn btn-xs btn-default" href="download_comunicazione_periodica.php?id='.$row["id"].'"><i class="glyphicon glyphicon-download"></i></a>&nbsp;</td>';
                                echo "<td align=\"center\">" . gaz_format_number($row["vp4"]) . " &nbsp;</td>";
                                echo "<td align=\"center\">" . gaz_format_number($row["vp5"]) . " &nbsp;</td>";
                                echo "<td align=\"center\">" . gaz_format_number($altro) . " &nbsp;</td>";
                                echo "<td align=\"center\">" . gaz_format_number($row['vp4']-$row['vp5']+$altro) . " &nbsp;</td>";
                                echo "</tr>";
                            }
                            ?>
                </table>
            </div>
        </div>
    </div>

</form>
<?php
require("../../library/include/footer.php");
?>
