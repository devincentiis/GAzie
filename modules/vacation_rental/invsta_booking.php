<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (https://www.programmisitiweb.lacasettabio.it)
  --------------------------------------------------------------------------

 -----------------------------------------------------------------------
                         GAzie - Gestione Azienda
    Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
                          (https://www.devincentiis.it)
                      <https://gazie.sourceforge.net>
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
require("../../modules/vendit/lib.function.php");

$admin_aziend=checkAdmin();
if (isset($_SESSION['print_request'])){
    $id_tes = $_SESSION['print_request'];
    unset ($_SESSION['print_request']);
    $result = gaz_dbi_dyn_query("*", $gTables['tesbro'], "id_tes = '$id_tes'","id_tes desc");
    $documento = gaz_dbi_fetch_array($result);
    if ($documento['numdoc'] > 0) {
        echo "<HTML><HEAD><TITLE>Wait for PDF</TITLE>\n";
        echo "<script type=\"text/javascript\">\n";
        $_SESSION['script_ref']='report_broven.php?auxil='.$documento['tipdoc'];
        if ($documento['tipdoc'] == 'VPR') {
            echo "setTimeout(\"window.location='stampa_precli.php?id_tes=".$documento['id_tes']."'\",1000)\n";
        } else {
            echo "setTimeout(\"window.location='stampa_ordcli.php?id_tes=".$documento['id_tes']."'\",1000)\n";
        }
        echo "</script></HEAD>\n<BODY><DIV align=\"center\">Wait for PDF</DIV><DIV align=\"center\">Aspetta il PDF</DIV></BODY></HTML>";
    } else {
        header("Location:docume_vendit.php");
        exit;
    }
} else {
    $locazione = 'docume_vendit.php';
    if (isset($_SESSION['script_ref'])) {
        $locazione = $_SESSION['script_ref'];
        unset ($_SESSION['script_ref']);
    }
    header("Location: ".$locazione);
    exit;
}
?>
