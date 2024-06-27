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
$msg = "";


if (!isset($_GET['gioini'])) { //al primo accesso allo script
    $_GET['gioini'] = "1";
    $_GET['mesini'] = "1";
    $_GET['annini'] = date("Y");
    $_GET['giofin'] = date("d");
    $_GET['mesfin'] = date("m");
    $_GET['annfin'] = date("Y");
}

if (!checkdate( $_GET['mesini'], $_GET['gioini'], $_GET['annini'])){
    $msg .= "1+";
}

if (!checkdate( $_GET['mesfin'], $_GET['giofin'], $_GET['annfin'])){
    $msg .= "2+";
}

if ($admin_aziend['conmag'] == 0){
    $msg .= "3+";
}

$utsini= mktime(0,0,0,$_GET['mesini'],$_GET['gioini'],$_GET['annini']);
$utsfin= mktime(0,0,0,$_GET['mesfin'],$_GET['giofin'],$_GET['annfin']);
$datainizio = date("Ymd",$utsini);
$datafine = date("Ymd",$utsfin);

if ($utsini > $utsfin)
    $msg .="1-4-2+";

if (isset($_GET['insert']) and $msg == "") {  //in caso di conferma
    $result = gaz_dbi_dyn_query($gTables['rigdoc'].".id_rig as id_rif,".$gTables['rigdoc'].".codart as artico,".$gTables['rigdoc'].".quanti,".$gTables['rigdoc'].".prelis as prezzo,".$gTables['rigdoc'].".sconto as scorig,".$gTables['rigdoc'].".id_rig,".$gTables['tesdoc'].".id_tes,".$gTables['tesdoc'].".tipdoc,".$gTables['tesdoc'].".protoc,".$gTables['tesdoc'].".seziva,".$gTables['tesdoc'].".datemi as datdoc,".$gTables['tesdoc'].".numdoc,".$gTables['tesdoc'].".seziva,".$gTables['tesdoc'].".clfoco,".$gTables['tesdoc'].".caumag,".$gTables['tesdoc'].".sconto as scochi,".$gTables['caumag'].".operat ", $gTables['rigdoc']." LEFT JOIN ".$gTables['tesdoc']." ON ".$gTables['rigdoc'].".id_tes = ".$gTables['tesdoc'].".id_tes LEFT JOIN ".$gTables['caumag']." ON ".$gTables['tesdoc'].".caumag = ".$gTables['caumag'].".codice LEFT JOIN ".$gTables['artico']." ON ".$gTables['rigdoc'].".codart = ".$gTables['artico'].".codice", "tiprig=0 AND LENGTH(TRIM(codart))>=1 AND movimentabile<>'N' AND id_mag=0 AND caumag<>0 AND datemi BETWEEN $datainizio AND $datafine ", " datemi ASC, ".$gTables['tesdoc'].".id_tes ASC, id_rig ASC");
    $numrow = gaz_dbi_num_rows($result);
    if ($numrow > 0) {
       $upd_mm = new magazzForm;
       $docOperat = $upd_mm->getOperators();
       $n=0;
       while ($row = gaz_dbi_fetch_array($result)) {
             if (!empty($row['artico'])) {
                   $n++;
                   if ($n > 15) {
                      gaz_set_time_limit (40); // azzero il tempo altrimenti vado in fatal_error
                      $n=0;
                   }
                   $upd_mm->uploadMag($row['id_rif'],
                                    $row['tipdoc'],
                                    $row['numdoc'],
                                    $row['seziva'],
                                    $row['datdoc'],
                                    $row['clfoco'],
                                    $row['scochi'],
                                    $row['caumag'],
                                    $row['artico'],
                                    $row['quanti'],
                                    $row['prezzo'],
                                    $row['scorig'],
                                    0,
                                    $admin_aziend['stock_eval_method'],
                                    false,
                                    $row['protoc']
                                    );
             }
       }
    header("Location:report_movmag.php");
    exit;
    }
}

require("../../library/include/header.php");
$script_transl = HeadMain();
echo "<form method=\"GET\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".ucfirst($script_transl[0])."</div>\n";
echo "<table class=\"Tmiddle table-striped\" align=\"center\">\n";
if (!empty($msg)) {
    $message = "";
    $rsmsg = array_slice( explode('+',chop($msg)),0,-1);
    foreach ($rsmsg as $value){
            $message .= $script_transl['error']."! -> ";
            $rsval = explode('-',chop($value));
            foreach ($rsval as $valmsg){
                    $message .= $script_transl[$valmsg]." ";
            }
            $message .= "<br />";
    }
    echo '<tr><td colspan="5" class="FacetDataTDred">'.$message."</td></tr>\n";
}
echo "<tr><td class=\"FacetFieldCaptionTD\">".$script_transl[1]."</td><td class=\"FacetDataTD\" colspan=\"3\">";
echo "\t <select name=\"gioini\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter = 1; $counter <= 31; $counter++ ){
    $selected = "";
    if($counter ==  $_GET['gioini'])
            $selected = "selected";
    echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"mesini\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
$gazTimeFormatter->setPattern('MMMM');
for( $counter = 1; $counter <= 12; $counter++ ){
  $selected = "";
  if($counter == $_GET['mesini']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"annini\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter =  date("Y")-10; $counter <=  date("Y")+10; $counter++ ){
    $selected = "";
    if($counter == $_GET['annini'])
            $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select></td></tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">".$script_transl[2]."</td><td class=\"FacetDataTD\" colspan=\"3\">";
echo "\t <select name=\"giofin\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter = 1; $counter <= 31; $counter++ ){
    $selected = "";
    if($counter ==  $_GET['giofin'])
            $selected = "selected";
    echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"mesfin\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter = 1; $counter <= 12; $counter++ ){
  $selected = "";
  if($counter == $_GET['mesfin']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"annfin\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter =  date("Y")-10; $counter <=  date("Y")+10; $counter++ ){
    $selected = "";
    if($counter == $_GET['annfin'])
            $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select></td></tr>\n";
if ($msg == "") {
    echo "<tr><td class=\"FacetFieldCaptionTD\"></td><td align=\"right\" colspan=\"4\"  class=\"FacetFooterTD\">
         <input type=\"submit\" name=\"Return\" value=\"".$script_transl['return']."\">&nbsp;<input type=\"submit\" name=\"anteprima\" value=\"".$script_transl['view']."!\">&nbsp;</td></tr>\n";
}
echo "</table>\n";
if (isset($_GET['anteprima']) and $msg == "") {
    $result = gaz_dbi_dyn_query($gTables['rigdoc'].".*,".$gTables['tesdoc'].".tipdoc,".$gTables['tesdoc'].".numdoc,".$gTables['tesdoc'].".datemi,".$gTables['tesdoc'].".datfat,".$gTables['tesdoc'].".imball,".$gTables['tesdoc'].".clfoco,".$gTables['tesdoc'].".caumag,".$gTables['tesdoc'].".sconto as scochi", $gTables['rigdoc']." LEFT JOIN ".$gTables['tesdoc']." ON ".$gTables['rigdoc'].".id_tes = ".$gTables['tesdoc'].".id_tes LEFT JOIN ".$gTables['caumag']." ON ".$gTables['tesdoc'].".caumag = ".$gTables['caumag'].".codice LEFT JOIN ".$gTables['artico']." ON ".$gTables['rigdoc'].".codart = ".$gTables['artico'].".codice", "tiprig=0 AND LENGTH(TRIM(codart))>=1 AND movimentabile<>'N' AND id_mag=0 AND caumag<>0 AND datemi BETWEEN $datainizio AND $datafine ", " datemi ASC, ".$gTables['tesdoc'].".id_tes ASC, id_rig ASC");
    $numrow = gaz_dbi_num_rows($result);
    echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
    if ($numrow > 0) {
       echo "<tr><td class=\"FacetFieldCaptionTD\" colspan=\"6\" >$numrow ".$script_transl[5]."</td></tr>";
       require("../../modules/vendit/lang.".$admin_aziend['lang'].".php");
       //$desdoc = $strScript["admin_docven.php"][0];
       $vendoc = $strScript["admin_docven.php"]['doc_name'];
       require("../../modules/acquis/lang.".$admin_aziend['lang'].".php");
       //$desdoc += $strScript["admin_docacq.php"][0];

       $desdoc = array_merge($vendoc, $strScript["admin_docacq.php"][0]);
       while ($row = gaz_dbi_fetch_array($result)) {
             echo "<tr>\n";
             $valore = CalcolaImportoRigo($row['quanti'], $row['prelis'], $row['sconto']) ;
             $valore = CalcolaImportoRigo(1, $valore, $row['scochi']) ;
             $descri = ((empty($desdoc[$row['tipdoc']])) ? $row['tipdoc'] : $desdoc[$row['tipdoc']])." n.".$row['numdoc'];
             echo "<td class=\"FacetDataTD\" align=\"center\">".gaz_format_date($row["datemi"])." &nbsp;</td>\n";
             echo "<td class=\"FacetDataTD\" align=\"center\">".$row["caumag"]." - ".$row["descri"]."</td>\n";
             echo "<td class=\"FacetDataTD\" align=\"center\">$descri</td>\n";
             echo "<td class=\"FacetDataTD\" align=\"center\">".$row["codart"]."</td>\n";
             echo "<td class=\"FacetDataTD\" align=\"center\">".gaz_format_quantity($row["quanti"],1,$admin_aziend['decimal_quantity'])."</td>\n";
             echo "<td class=\"FacetDataTD\" align=\"right\">".gaz_format_number($valore)." </td>";
             echo "</tr>\n";
       }
       echo "<tr><td colspan=\"6\" align=\"center\"><input type=\"submit\" name=\"insert\" value=\"".strtoupper($script_transl[0])." !\"></td></tr>";
    } else {
       echo "<tr><td class=\"FacetDataTDred\" align=\"center\">".$script_transl[6]."</td></tr>";
    }
    echo "</table>";
}
?>

</form>
<?php
require("../../library/include/footer.php");
?>
