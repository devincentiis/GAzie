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
$titolo = "Controllo movimenti contabili";

require("../../library/include/header.php");
$script_transl=HeadMain();
echo '<div align="center" class="FacetFormHeaderFont">Controllo sbilancio movimenti contabili</div>';
?>
<table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
<form method="POST">
<?php
$where = "codcon not like '%000000' group by ".$gTables['rigmoc'].".id_tes";
$result = gaz_dbi_dyn_query ($gTables['tesmov'].".id_tes, ".$gTables['rigmoc'].".codcon as accnum,descri,sum(import*(darave='D')) as dare,sum(import*(darave='A')) as avere", $gTables['rigmoc']." left join ".$gTables['tesmov']." on ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes ", $where, 1);
$message = '<tr><th class="FacetFieldCaptionTD">Numero ID</th><th class="FacetFieldCaptionTD">Descrizione </th><th class="FacetFieldCaptionTD">DARE </th><th class="FacetFieldCaptionTD">AVERE </th><th class="FacetFieldCaptionTD">SBILANCIO</th></tr>
         <tr><td class="FacetDataTDred" align="left" colspan="5">I seguenti movimenti contabili presentano degli errori e quindi sono da modificare cliccando sul numero di ID :</td></tr>';
while ($a_row = gaz_dbi_fetch_array($result)) {
      if ($a_row['dare'] != $a_row['avere']){
       if ($message != "") {
            echo $message;
            $message = "";
       }                         
         echo "<tr><td class=\"FacetDataTD\" align=\"center\"><a href=\"../contab/admin_movcon.php?Update&id_tes=".$a_row["id_tes"]."\" title=\"Modifica il movimento\" >".$a_row["id_tes"]."</a></td><td class=\"FacetDataTD\">".$a_row["descri"]."</td><td class=\"FacetDataTD\" align=\"right\">".$a_row["dare"]."</td><td class=\"FacetDataTD\" align=\"right\">".$a_row["avere"]."</td><td class=\"FacetDataTDred\" align=\"right\">".gaz_format_number($a_row["dare"]-$a_row["avere"])."</td></tr>\n";
      }
}

$where = "codcon < 1";
$result = gaz_dbi_dyn_query ($gTables['tesmov'].".id_tes, ".$gTables['rigmoc'].".codcon as accnum, id_rig,descri ", $gTables['rigmoc']." left join ".$gTables['tesmov']." on ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes ", $where, 1);
while ($a_row = gaz_dbi_fetch_array($result)) {
      if ($a_row['accnum'] < 100000000 ){
       if ($message != "") {
            echo $message;
            $message = "";
       }                         
         echo "<tr><td class=\"FacetDataTD\" align=\"center\"><a href=\"../contab/admin_movcon.php?Update&id_tes=".$a_row["id_tes"]."\" title=\"Modifica il movimento\" >".$a_row["id_tes"]."</a></td><td class=\"FacetDataTD\">".$a_row["descri"]."</td><td class=\"FacetDataTDred\" colspan=\"3\" align=\"center\">Il rigo ".$a_row["id_rig"]." non ha il conto di riferimento</td></tr>\n";
      }
}

if ($message !=  "") {
   echo "<tr><td class=\"FacetFormHeaderFont\" align=\"center\" colspan=\"5\">Il controllo effettuato non ha evidenziato movimenti con sbilanci DARE/AVERE !</td></tr>\n";
}
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>