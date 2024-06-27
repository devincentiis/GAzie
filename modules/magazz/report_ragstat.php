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
$titolo = 'Categorie Merceologiche';
require("../../library/include/header.php");
$script_transl =HeadMain();

if (isset($_GET['auxil'])) {
   $auxil = $_GET['auxil'];
}
if (isset($_GET['all'])) {
   $auxil = "&all=yes";
   $where = "descri like '%'";
   $passo = 100000;
} else {
   if (isset($_GET['auxil'])) {
      $where = "descri like '".addslashes($_GET['auxil'])."%'";
   }
}

if (!isset($_GET['auxil'])) {
   $auxil = "";
   $where = "descri like '".addslashes($auxil)."%'";
}
?>
<div align="center" class="FacetFormHeaderFont">Raggruppamenti statistici</div>
<?php
$recordnav = new recordnav($gTables['ragstat'], $where, $limit, $passo);
$recordnav -> output();

/** ENRICO FEDELE */
/* pulizia del codice, eliminato boxover, aggiunte classi bootstrap alla tabella, convertite immagini in glyphicons */
?>
<form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<div class="table-responsive">
<table class="Tlarge table table-bordered table-condensed">
    <thead>
        <tr>
        	<td></td>
        	<td class="FacetFieldCaptionTD">Descrizione:
        		<input type="text" name="auxil" value="<?php if ($auxil != "&all=yes") echo $auxil; ?>" maxlength="6" tabindex="1" class="FacetInput">
        	</td>
        	<td>
        		<input type="submit" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value=1;">
        	</td>
        	<td>
        		<input type="submit" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value=1;">
        	</td>
        </tr>
        <tr>
<?php
$result = gaz_dbi_dyn_query ('*', $gTables['ragstat'], $where, $orderby, $limit, $passo);
// creo l'array (header => campi) per l'ordinamento dei record
$headers_ragstat = array  (
            "Codice" => "codice",
            "Descrizione" => "descri",
            "% Ricarico" => "ricarico",
            "Annotazioni" => "annota",
            "Cancella" => ""
            );
$linkHeaders = new linkHeaders($headers_ragstat);
$linkHeaders -> output();
?>
		</tr>
	</thead>
	<tbody>
<?php
while ($a_row = gaz_dbi_fetch_array($result)) {
    echo '		<tr>
				<td class="text-center">
					<a class="btn btn-xs btn-edit" href="admin_ragstat.php?Update&codice='.$a_row["codice"].'">
						<i class="glyphicon glyphicon-edit"></i>&nbsp;'.$a_row["codice"].'
					</a>
				</td>
				<td>
					<span class="gazie-tooltip col-xs-12" data-type="ragstat-thumb" data-id="'.$a_row['codice'].'" data-title="'.$a_row['annota'].'" data-maxsize="360">'.$a_row["descri"].'</span>
				</td>
				<td class="text-center">'.$a_row["ricarico"].'</td>
				<td class="text-center">'.$a_row["annota"].'</td>
				<td class="text-center">
					<a class="btn btn-xs  btn-elimina" href="delete_ragstat.php?codice='.$a_row["codice"].'">
						<i class="glyphicon glyphicon-trash"></i>
					</a>
				</td>
			</tr>';
}
?>
			</tbody>
		</table>
  </div>
	<?php
require("../../library/include/footer.php");
?>
<!--<script src="../../js/boxover/boxover.js"></script>-->
