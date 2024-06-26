<?php
/*
 -----------------------------------------------------------------------
                         GAzie - Gestione Azienda
    Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
                          (http://www.devincentiis.it)
                      <http://gazie.sourceforge.net>
 -----------------------------------------------------------------------
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
 -----------------------------------------------------------------------
*/
require("../../library/include/datlib.inc.php");

$admin_aziend=checkAdmin();

if (isset($_SESSION['print_request'])){
    $id_let = $_SESSION['print_request'];
    unset ($_SESSION['print_request']);
    $result = gaz_dbi_dyn_query("*", $gTables['letter'], "id_let = '$id_let' ","id_let desc");
    $documento = gaz_dbi_fetch_array($result);
    //Creo l'array associativo delle descrizioni dei documenti
    $tipoLettera = array ("LET"=>'La Lettera ',"DIC"=>'La Dichiarazione ',"SOL"=>'Il Sollecito ');
    if ($documento) {
        echo "<HTML><HEAD><TITLE>Wait for PDF</TITLE>\n";
        echo "<script type=\"text/javascript\">\n";
        echo "setTimeout(\"window.location='stampa_letter.php?id_let=".$documento['id_let']."'\",1000)\n";
        echo "</script></HEAD>\n<BODY><DIV align=\"center\">Wait for PDF</DIV></BODY></HTML>";
    } else {
        header("Location:report_letter.php");
        exit;
    }
} else {
    header("Location:report_letter.php");
    exit;
}
?>