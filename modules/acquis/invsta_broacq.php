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
    $id_tes = $_SESSION['print_request'];
    unset ($_SESSION['print_request']);
    $result        = gaz_dbi_dyn_query("*", $gTables['tesbro'], "id_tes = '$id_tes' and status = 'GENERATO'","id_tes desc");
    $documento     = gaz_dbi_fetch_array($result);
    $TipoDocumento = array ("AOR" => "l'Ordine a Fornitore","APR" => "il Preveventivo d'Acquisto");
    if ($documento['numdoc'] > 0) {
        echo '<html>
				<head>
					<title>Wait for PDF</title>
					<script type="text/javascript">';
		/** Un pò di documentazione per capire bene la sintassi */
		//http://www.w3schools.com/jsref/met_win_settimeout.asp
		//https://developer.mozilla.org/en-US/docs/Web/API/WindowTimers/setTimeout
        if ($documento['tipdoc'] == 'AOR') {
			//\"window.location='stampa_ordfor.php?id_tes=".$documento['id_tes']."'\",1000
            echo 'setTimeout(function(){location.href="stampa_ordfor.php?id_tes='.$documento['id_tes'].'"} , 1000)';
        } else {
			//\"window.location='stampa_prefor.php?id_tes=".$documento['id_tes']."'\",1000
            echo 'setTimeout(function(){location.href="stampa_prefor.php?id_tes='.$documento['id_tes'].'"} , 1000)';
        }
        echo '			</script>
					</head>
					<body>
						<div align="center">Wait for PDF</div>
					</body>
				</html>';
    } else {
        header("Location:report_broacq.php");
        exit;
    }
} else {
    header("Location:report_broacq.php");
    exit;
}
?>