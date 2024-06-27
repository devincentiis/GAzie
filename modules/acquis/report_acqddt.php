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
$script_transl=HeadMain();
if (!isset($_GET['field'])) {
	$orderby='datemi DESC, numdoc DESC';
}

?>
<div align="center" class="FacetFormHeaderFont"> <?php echo $script_transl['title']; ?></div>
<?php
$where = "tipdoc = 'ADT' OR tipdoc = 'RDL'";
$recordnav = new recordnav($gTables['tesdoc'], $where, $limit, $passo);
$recordnav -> output();
?>
<div class="table-responsive"><table class="Tlarge table table-striped table-bordered table-condensed">
		<tr>
<?php
// creo l'array (header => campi) per l'ordinamento dei record
$headers_tesdoc = array  (
            "ID" => "id_tes",
            "Numero-protocollo" => "protoc",
            "Data" => "datemi",
            "Fornitore (cod.)" => "clfoco",
            "Status" => "",
            "Stampa" => "",
            "Cancella" => ""
            );
$linkHeaders = new linkHeaders($headers_tesdoc);
$linkHeaders -> output();
?>
		</tr>
<?php
$result = gaz_dbi_dyn_query ('*', $gTables['tesdoc'], $where, $orderby, $limit, $passo);
$anagrafica = new Anagrafica();
while ($a_row = gaz_dbi_fetch_assoc($result)) {
    $cliente = $anagrafica->getPartner($a_row['clfoco']);
    echo '			<tr class="FacetDataTD">
						<td>
							<a class="btn btn-xs btn-default" href="admin_docacq.php?id_tes='.$a_row["id_tes"].'&Update">
								<i class="glyphicon glyphicon-edit"></i>'.$a_row["id_tes"].'
							</a>
						</td>
						<td>'.$a_row["numdoc"].' - '.$a_row["protoc"].'</td>
						<td>'.gaz_format_date($a_row["datemi"]).'</td>
						<td>'.$cliente["ragso1"].'</td>
						<td>'.$a_row["status"].'</td>
						<td>
							<a class="btn btn-xs btn-default" href="stampa_docacq.php?id_tes='.$a_row["id_tes"].'" title="Stampa" target="_blank">
								<i class="glyphicon glyphicon-print"></i>
							</a>
						</td>
						<td>
							<a class="btn btn-xs btn-default" href="delete_docacq.php?id_tes='.$a_row["id_tes"].'" title="Cancella">
								<i class="glyphicon glyphicon-trash"></i>
							</a>
						</td>
					</tr>';
}
?>
		</table>
        </div>
	<?php
require("../../library/include/footer.php");
?>