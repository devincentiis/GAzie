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
$message = "";

$titolo="Ricevute di pagamento emesse ai clienti";
$anno = date("Y");
require("../../library/include/header.php");
$script_transl=HeadMain();
?>
<div align="center" class="FacetFormHeaderFont">Ricevute di pagamento emesse ai clienti</div>
<div align="center" class="FacetText">(Per inserire un pagamento di un cliente andare sulla lista dei clienti o dei crediti e cliccare sulla banconota)</div>
<?php
$where = "tipdoc = 'VPA'";
$recordnav = new recordnav($gTables['tesbro'], $where, $limit, $passo);
$recordnav -> output();
?>
<table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
<tr>
<?php
// creo l'array (header => campi) per l'ordinamento dei record
$headers_tesdoc = array  (
              "ID" => "id_tes",
              "Numero" => "numdoc",
              "Data" => "datemi",
              "Cliente" => "clfoco",
              "Importo" => "portos",
              "Status" => "",
              "Stampa" => "",
              "Cancella" => ""
              );
$linkHeaders = new linkHeaders($headers_tesdoc);
$linkHeaders -> output();
?>
</tr>
<?php
if (!isset($_GET['flag_order']))
       $orderby = "id_tes desc";
$result = gaz_dbi_dyn_query ('*', $gTables['tesbro'], $where, $orderby, $limit, $passo);
$ctrlprotoc = "";
$anagrafica = new Anagrafica();
while ($a_row = gaz_dbi_fetch_array($result)) {
    $cliente = $anagrafica->getPartner($a_row['clfoco']);
    print "<tr>";
    print "<td class=\"FacetDataTD\" align=\"center\"><a class=\"btn btn-xs btn-edit\" href=\"salcon_credit.php?codice=".$a_row['id_tes']."&Update\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;".$a_row['id_tes']."</a></td>";
    print "<td class=\"FacetDataTD\" align=\"center\">".$a_row["numdoc"]." &nbsp;</td>";
    print "<td class=\"FacetDataTD\" align=\"center\">".gaz_format_date($a_row["datemi"])." &nbsp;</td>";
    print "<td class=\"FacetDataTD\" align=\"left\"><a title=\"Dettagli cliente\" href=\"report_client.php?nome=".$cliente["ragso1"]."\">".$cliente["ragso1"]."</a></td>";
    print "<td class=\"FacetDataTD\" align=\"right\">".$a_row["portos"]." &nbsp;</td>";
    print "<td class=\"FacetDataTD\" align=\"center\">".$a_row["status"]." &nbsp;</td>";
    print "<td class=\"FacetDataTD\" align=\"center\"><a class=\"btn btn-xs btn-default\" href=\"stampa_salcon.php?id_tes=".$a_row['id_tes']."\" target=\"_blank\"><i class=\"glyphicon glyphicon-print\"></i></a></td>";
    print "<td class=\"FacetDataTD\" align=\"center\"><a class=\"btn btn-xs  btn-elimina\" href=\"delete_salcon.php?id_tes=".$a_row['id_tes']."\"><i class=\"glyphicon glyphicon-trash\"></i></a></td>";
    print "</tr>";
}
?>
</table>
<?php
require("../../library/include/footer.php");
?>
