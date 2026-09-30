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
require("../../library/include/header.php");
$script_transl = HeadMain();
//
// Anno predefinito.
//
$year      = date("Y")-1;
//
// Altre variabili usate nella maschera di I/O.
//
$extcon_w  = 0;
$extcon_r  = 0;
$eB7__ind  = 0;
$eB7__amm  = 0;
$eB7__com  = 0;
$eB8__ind  = 0;
$eB8__amm  = 0;
$eB8__com  = 0;
$eB9__ind  = 0;
$eB9__amm  = 0;
$eB9__com  = 0;
$eB10__ind = 0;
$eB10__amm = 0;
$eB10__com = 0;
$eB12__ind = 0;
$eB12__amm = 0;
$eB12__com = 0;
$eB13__ind = 0;
$eB13__amm = 0;
$eB13__com = 0;
$eB14__ind = 0;
$eB14__amm = 0;
$eB14__com = 0;
$num_dip   = 0;
$pD__breve = 0;
$pD__medio = 0;
$pD__lungo = 0;
//
// Array per la lettura dei dati extragestione
// dalla tabella extcon.
//
$extra;
//
// Salva i dati POST recepiti, con controllo.
//
if (isset ($_POST["extcon_w"])  && is_string ($_POST["extcon_w"]))   $extcon_w  = 1;
if (isset ($_POST["extcon_r"])  && is_string ($_POST["extcon_r"]))   $extcon_r  = 1;
if ($extcon_r) $extcon_w = 0;
//
if (isset ($_POST["year"])      && is_numeric ($_POST["year"]))      $year      = $_POST["year"];
if (isset ($_POST["eB7__ind"])  && is_numeric ($_POST["eB7__ind"]))  $eB7__ind  = $_POST["eB7__ind"];
if (isset ($_POST["eB7__amm"])  && is_numeric ($_POST["eB7__amm"]))  $eB7__amm  = $_POST["eB7__amm"];
if (isset ($_POST["eB7__com"])  && is_numeric ($_POST["eB7__com"]))  $eB7__com  = $_POST["eB7__com"];
if (isset ($_POST["eB8__ind"])  && is_numeric ($_POST["eB8__ind"]))  $eB8__ind  = $_POST["eB8__ind"];
if (isset ($_POST["eB8__amm"])  && is_numeric ($_POST["eB8__amm"]))  $eB8__amm  = $_POST["eB8__amm"];
if (isset ($_POST["eB8__com"])  && is_numeric ($_POST["eB8__com"]))  $eB8__com  = $_POST["eB8__com"];
if (isset ($_POST["eB9__ind"])  && is_numeric ($_POST["eB9__ind"]))  $eB9__ind  = $_POST["eB9__ind"];
if (isset ($_POST["eB9__amm"])  && is_numeric ($_POST["eB9__amm"]))  $eB9__amm  = $_POST["eB9__amm"];
if (isset ($_POST["eB9__com"])  && is_numeric ($_POST["eB9__com"]))  $eB9__com  = $_POST["eB9__com"];
if (isset ($_POST["eB10__ind"]) && is_numeric ($_POST["eB10__ind"])) $eB10__ind = $_POST["eB10__ind"];
if (isset ($_POST["eB10__amm"]) && is_numeric ($_POST["eB10__amm"])) $eB10__amm = $_POST["eB10__amm"];
if (isset ($_POST["eB10__com"]) && is_numeric ($_POST["eB10__com"])) $eB10__com = $_POST["eB10__com"];
if (isset ($_POST["eB12__ind"]) && is_numeric ($_POST["eB12__ind"])) $eB12__ind = $_POST["eB12__ind"];
if (isset ($_POST["eB12__amm"]) && is_numeric ($_POST["eB12__amm"])) $eB12__amm = $_POST["eB12__amm"];
if (isset ($_POST["eB12__com"]) && is_numeric ($_POST["eB12__com"])) $eB12__com = $_POST["eB12__com"];
if (isset ($_POST["eB13__ind"]) && is_numeric ($_POST["eB13__ind"])) $eB13__ind = $_POST["eB13__ind"];
if (isset ($_POST["eB13__amm"]) && is_numeric ($_POST["eB13__amm"])) $eB13__amm = $_POST["eB13__amm"];
if (isset ($_POST["eB13__com"]) && is_numeric ($_POST["eB13__com"])) $eB13__com = $_POST["eB13__com"];
if (isset ($_POST["eB14__ind"]) && is_numeric ($_POST["eB14__ind"])) $eB14__ind = $_POST["eB14__ind"];
if (isset ($_POST["eB14__amm"]) && is_numeric ($_POST["eB14__amm"])) $eB14__amm = $_POST["eB14__amm"];
if (isset ($_POST["eB14__com"]) && is_numeric ($_POST["eB14__com"])) $eB14__com = $_POST["eB14__com"];
if (isset ($_POST["num_dip"])   && is_numeric ($_POST["num_dip"]))   $num_dip   = $_POST["num_dip"];
if (isset ($_POST["pD__breve"]) && is_numeric ($_POST["pD__breve"])) $pD__breve = $_POST["pD__breve"];
if (isset ($_POST["pD__medio"]) && is_numeric ($_POST["pD__medio"])) $pD__medio = $_POST["pD__medio"];
if (isset ($_POST["pD__lungo"]) && is_numeric ($_POST["pD__lungo"])) $pD__lungo = $_POST["pD__lungo"];
//
// Cerca di leggere i dati relativi all'anno selezionato.
//
$query  = "SELECT * FROM " . $gTables['extcon'] . " WHERE year = \"$year\"";
$result = gaz_dbi_query ($query);
$nrows  = gaz_dbi_num_rows ($result);
//
// Se l'anno non c'è, aggiunge una riga vuota e la rilegge.
//
if ($nrows == 0)
  {
    $query  = "INSERT INTO " . $gTables['extcon'] . " (`year`) VALUES ($year)";
    $result = gaz_dbi_query ($query);
    $query  = "SELECT * FROM " . $gTables['extcon'] . " WHERE year = \"$year\"";
    $result = gaz_dbi_query ($query);
  }
//
// Se le variabili contengono dei dati, li memorizza nella tabella, altrimenti,
// li preleva dalla tabella.
//
if ($extcon_w)
  {
    //
    // Ci sono dati da memorizzare nella tabella.
    //
    $query  = "UPDATE " . $gTables['extcon'] . " "
            . "SET "
              . "cos_serv_ind = $eB7__ind, "
              . "cos_serv_amm = $eB7__amm, "
              . "cos_serv_com = $eB7__com, "
              . "cos_godb_ind = $eB8__ind, "
              . "cos_godb_amm = $eB8__amm, "
              . "cos_godb_com = $eB8__com, "
              . "cos_pers_ind = $eB9__ind, "
              . "cos_pers_amm = $eB9__amm, "
              . "cos_pers_com = $eB9__com, "
              . "cos_amms_ind = $eB10__ind, "
              . "cos_amms_amm = $eB10__amm, "
              . "cos_amms_com = $eB10__com, "
              . "cos_accr_ind = $eB12__ind, "
              . "cos_accr_amm = $eB12__amm, "
              . "cos_accr_com = $eB12__com, "
              . "cos_acca_ind = $eB13__ind, "
              . "cos_acca_amm = $eB13__amm, "
              . "cos_acca_com = $eB13__com, "
              . "cos_divg_ind = $eB14__ind, "
              . "cos_divg_amm = $eB14__amm, "
              . "cos_divg_com = $eB14__com, "
              . "num_dip      = $num_dip, "
              . "deb_breve    = $pD__breve, "
              . "deb_medio    = $pD__medio, "
              . "deb_lungo    = $pD__lungo "
            . "WHERE "
              . "year = \"$year\"";
    $result = gaz_dbi_query ($query);
  }
else
  {
    $extra = gaz_dbi_fetch_array ($result);
    //
    $eB7__ind  = $extra['cos_serv_ind'];
    $eB7__amm  = $extra['cos_serv_amm'];
    $eB7__com  = $extra['cos_serv_com'];
    $eB8__ind  = $extra['cos_godb_ind'];
    $eB8__amm  = $extra['cos_godb_amm'];
    $eB8__com  = $extra['cos_godb_com'];
    $eB9__ind  = $extra['cos_pers_ind'];
    $eB9__amm  = $extra['cos_pers_amm'];
    $eB9__com  = $extra['cos_pers_com'];
    $eB10__ind = $extra['cos_amms_ind'];
    $eB10__amm = $extra['cos_amms_amm'];
    $eB10__com = $extra['cos_amms_com'];
    $eB12__ind = $extra['cos_accr_ind'];
    $eB12__amm = $extra['cos_accr_amm'];
    $eB12__com = $extra['cos_accr_com'];
    $eB13__ind = $extra['cos_acca_ind'];
    $eB13__amm = $extra['cos_acca_amm'];
    $eB13__com = $extra['cos_acca_com'];
    $eB14__ind = $extra['cos_divg_ind'];
    $eB14__amm = $extra['cos_divg_amm'];
    $eB14__com = $extra['cos_divg_com'];
    $num_dip   = $extra['num_dip'];
    $pD__breve = $extra['deb_breve'];
    $pD__medio = $extra['deb_medio'];
    $pD__lungo = $extra['deb_lungo'];
  }
//
// Produce il form.
//
echo "<form method=\"POST\">";
echo "<p><big><strong>Informazioni addizionali per la riclassificazione di bilancio</strong></big></p>";
//
echo "<table>\n";
//
echo "<tr>\n";
echo "<th>anno di riferimento</th>\n";
echo "<td><input type=\"text\" name=\"year\"  value=\"".$year."\"></td>\n";
echo "<td><input type=\"submit\" name=\"extcon_r\" value=\"conferma\"></td>\n";
echo "<td></td>\n";
echo "</tr>\n";
//
echo "<tr>\n";
echo "<td colspan=\"4\"><hr></td>\n";
echo "</tr>\n";
//
echo "<tr>\n";
echo "<th></th>\n";
echo "<th>quota<br>costi industriali</th>\n";
echo "<th>quota<br>costi amministrativi</th>\n";
echo "<th>quota<br>costi commerciali</th>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td>c.e. B7) costi per servizi</td>\n";
echo "<td><input type=\"text\" name=\"eB7__ind\"  value=\"".$eB7__ind."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB7__amm\"  value=\"".$eB7__amm."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB7__com\"  value=\"".$eB7__com."\"></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td>c.e. B8) costi per godimento di beni di terzi</td>\n";
echo "<td><input type=\"text\" name=\"eB8__ind\"  value=\"".$eB8__ind."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB8__amm\"  value=\"".$eB8__amm."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB8__com\"  value=\"".$eB8__com."\"></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td>c.e. B9) costi per il personale</td>\n";
echo "<td><input type=\"text\" name=\"eB9__ind\"  value=\"".$eB9__ind."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB9__amm\"  value=\"".$eB9__amm."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB9__com\"  value=\"".$eB9__com."\"></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td>c.e. B10) ammortamenti e svalutazioni</td>\n";
echo "<td><input type=\"text\" name=\"eB10__ind\"  value=\"".$eB10__ind."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB10__amm\"  value=\"".$eB10__amm."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB10__com\"  value=\"".$eB10__com."\"></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td>c.e. B12) accantonamenti per rischi</td>\n";
echo "<td><input type=\"text\" name=\"eB12__ind\"  value=\"".$eB12__ind."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB12__amm\"  value=\"".$eB12__amm."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB12__com\"  value=\"".$eB12__com."\"></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td>c.e. B13) altri accantonamenti</td>\n";
echo "<td><input type=\"text\" name=\"eB13__ind\"  value=\"".$eB13__ind."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB13__amm\"  value=\"".$eB13__amm."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB13__com\"  value=\"".$eB13__com."\"></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td>c.e. B14) oneri diversi di gestione</td>\n";
echo "<td><input type=\"text\" name=\"eB14__ind\"  value=\"".$eB14__ind."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB14__amm\"  value=\"".$eB14__amm."\"></td>\n";
echo "<td><input type=\"text\" name=\"eB14__com\"  value=\"".$eB14__com."\"></td>\n";
echo "</tr>\n";
//
echo "<tr>\n";
echo "<td colspan=\"4\"><hr></td>\n";
echo "</tr>\n";
//
echo "<tr>\n";
echo "<td>numero di dipendenti durante l'anno</td>\n";
echo "<td colspan=\"3\"><input type=\"text\" name=\"num_dip\"  value=\"".$num_dip."\"></td>\n";
echo "</tr>\n";
//
echo "<tr>\n";
echo "<td colspan=\"4\"><hr></td>\n";
echo "</tr>\n";
//
echo "<tr>\n";
echo "<th></th>\n";
echo "<th>debiti a<br>breve termine</th>\n";
echo "<th>debiti a<br>medio termine</th>\n";
echo "<th>debiti a<br>lungo termine</th>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td>passivo D) debiti</td>\n";
echo "<td><input type=\"text\" name=\"pD__breve\"  value=\"".$pD__breve."\"></td>\n";
echo "<td><input type=\"text\" name=\"pD__medio\"  value=\"".$pD__medio."\"></td>\n";
echo "<td><input type=\"text\" name=\"pD__lungo\"  value=\"".$pD__lungo."\"></td>\n";
echo "</tr>\n";
//
echo "<tr>\n";
echo "<td colspan=\"4\"><hr></td>\n";
echo "</tr>\n";
//
echo "<tr>\n";
echo "<td><input type=\"submit\" name=\"extcon_r\" value=\"annulla\"></td>\n";
echo "<td></td>\n";
echo "<td></td>\n";
echo "<td><input type=\"submit\" name=\"extcon_w\" value=\"inserisci questi valori\"></td>\n";
echo "</tr>\n";
//
echo "<table>\n";
echo "</table>\n";
//
echo "\n";
echo "\n";
echo "</form>\n";
//
require("../../library/include/footer.php");
?>
