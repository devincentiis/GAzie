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
if (isset($_GET['auxil'])) {
    $auxil = $_GET['auxil'];
} else {
    $auxil = 1;
}

if (isset($_GET['progre'])) {
    if ($_GET['progre'] > 0) {
        $progressivo = intval($_GET['progre']);
        $auxil = $_GET['auxil'] . "&progre=" . $progressivo;
        $where = "progre = '$progressivo'";
        $passo = 1;
    }
} else {
    $progressivo = '';
}

if (isset($_GET['all'])) {
    $where = " 1 ";
    $auxil = $_GET['auxil'] . "&all=yes";
    $passo = 100000;
    $progressivo = '';
}
require("../../library/include/header.php");
$script_transl = HeadMain('', '', 'select_effett');
if (!isset($_GET['field']) || empty($_GET['field'])) {
    $orderby = "scaden DESC, numfat DESC";
}
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("ragso"));
		var id = $(this).attr('ref');
		$( "#dialog_delete" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
   			close: {
					text:'Non eliminare',
					'class':'btn btn-default',
          click:function() {
            $(this).dialog("close");
          }
        },
				delete:{
					text:'Elimina',
					'class':'btn btn-danger',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'effett',id_tes:id},
						type: 'POST',
						url: '../vendit/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_effett.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['report']; ?></div>
<?php
$recordnav = new recordnav($gTables['effett'], $where, $limit, $passo);
$recordnav->output();
?>
<form method="GET">
    <div class="box-primary table-responsive">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>effetto:</b></p>
        <p>Numero ID:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Cliente:</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
    <table class="Tlarge table table-striped table-bordered table-condensed">
        <input type="hidden" name="auxil" value="<?php print substr($auxil, 0, 1); ?>">
        <tr>
            <td class="FacetFieldCaptionTD"></td>
            <td class="FacetFieldCaptionTD">Num.:
                <input type="text" name="progre" value="<?php if (isset($progressivo)) print $progressivo; ?>" maxlength="6" tabindex="1" class="FacetInput">
            </td>
            <td class="FacetFieldCaptionTD" colspan="9"></td>
            <td class="FacetFieldCaptionTD">
                <input type="submit" name="search" value="<?php echo $script_transl['search']; ?>" tabindex="1" onClick="javascript:document.report.all.value = 1;">
            </td>
            <td class="FacetFieldCaptionTD">
                <input type="submit" name="all" value="<?php echo $script_transl['vall']; ?>" onClick="javascript:document.report.all.value = 1;">
            </td>
        </tr>
        <?php
        $headers_banapp = array(
            'ID' => "id_tes",
            $script_transl['progre'] => "progre",
            $script_transl['date_emi'] => "datemi",
            $script_transl['type'] => "tipeff",
            $script_transl['date_exp'] => "scaden",
            $script_transl['clfoco'] => "clfoco",
            $script_transl['impeff'] => "impeff",
            $script_transl['salacc'] => "salacc",
            $script_transl['banapp'] => "banapp",
            $script_transl['status'] => "",
            $script_transl['print'] => "",
            $script_transl['source'] => "",
            $script_transl['delete'] => ""
        );
        $linkHeaders = new linkHeaders($headers_banapp);
        $linkHeaders->output();
        ?>
        </tr>
        <?php
        $result = gaz_dbi_dyn_query('*', $gTables['effett'], $where, $orderby, $limit, $passo);
        $anagrafica = new Anagrafica();
        while ($r = gaz_dbi_fetch_array($result)) {
            $cliente = $anagrafica->getPartner($r['clfoco']);
            $banapp = gaz_dbi_get_row($gTables['banapp'], "codice", $r['banapp']);
            echo "<tr class=\"FacetDataTD\">";
            echo "<td align=\"right\"><a class=\"btn btn-xs btn-edit\" href=\"admin_effett.php?Update&id_tes=" . $r["id_tes"] . "\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;" . $r["id_tes"] . "</a> &nbsp</td>";
            echo "<td align=\"right\"><a href=\"admin_effett.php?Update&id=" . $r["id_tes"] . "\">" . $r["progre"] . "</a> &nbsp</td>";
            echo "<td align=\"right\">" . gaz_format_date($r["datemi"]) . "</td>";
            echo "<td align=\"center\">" .  $script_transl['type_value'][$r["tipeff"]] . (($r['status']=='RAGGRUPPA')?' <span class="text-danger">[raggruppato] </span>':'')."</td>";
            echo "<td align=\"center\">" . gaz_format_date($r["scaden"]) . " &nbsp;</td>";
            echo "<td title=\"" . $script_transl['date_doc'] . ": " . gaz_format_date($r["datfat"]) . " n." . $r["numfat"] . "/" . $r["seziva"] . ' ' . $admin_aziend['html_symbol'] . " " . gaz_format_number($r["totfat"]) . "\">" . $cliente["ragso1"] . " &nbsp;</td>";
            echo "<td align=\"right\">" . gaz_format_number($r["impeff"]) . " &nbsp;</td>";
            echo "<td align=\"center\">" . $script_transl['salacc_value'][$r["salacc"]] . " &nbsp;</td>";
            echo "<td>" . (($banapp)?$banapp["descri"]:'') . " &nbsp;</td>";
            // Colonna "Stato"
            echo '<td align="center">';
            if ($r["id_distinta"] > 0) {
                if ($r["id_con"] > 0) {
                    $tesmov_result = gaz_dbi_dyn_query('*', $gTables['tesmov'], "id_tes = " . $r["id_con"], 'id_tes');
                    $tesmov_r = gaz_dbi_fetch_array($tesmov_result);
                    if ($tesmov_r["id_tes"] == $r["id_con"]) {
                        // L'effetto risulta contabilizzato regolarmente.
                        echo ' <a  class="btn btn-xs btn-default" href="../contab/admin_movcon.php?id_tes=' . $r["id_con"] . '&Update">Cont. n.' . $r["id_con"] . "</a>\n ";
                    } else {
                        // vado a modificare l'effetto azzerando il
                        // riferimento alla registrazione contabile
                        gaz_dbi_put_row($gTables['effett'], "id_tes", $r["id_tes"], "id_con", 0);
                        // Mostro che l'effetto è da contabilizzare nuovamente.
                        echo ' <a href="contab_effett.php">Contabilizza</a>';
                    }
                } else {
                    // L'effetto e' da contabilizzare.
                    echo '<a href="contab_effett.php">Contabilizza</a> ';
                }
                echo '<a class="btn btn-xs btn-success" href="stampa_distint.php?id_distinta='.$r["id_distinta"].'">Distinta '.$r["id_distinta"].' (pdf)</a> ';
                ?>
                <a href="../root/retrieve.php?id_doc=<?php echo $r["id_distinta"]; ?>"  class="btn btn-default btn-sm">download <i class="glyphicon glyphicon-download"></i> </a>
                <?php
            } else {
                echo ' <a class="btn btn-xs btn-info" href="';
                if ($r["tipeff"] == "T") {
                    echo 'distin_effett.php">Distinta su file PDF Cambiali Tratte';
                } elseif ($r["tipeff"] == "B") {
                    echo 'select_filerb.php">Distinta su file CBI RiBa';
                } elseif ($r["tipeff"] == "I") {
                    echo 'select_filerid.php">Distinta su file XML RID';
                } elseif ($r["tipeff"] == "V") {
                    echo 'select_filemav.php">Distinta su file MAV';
                } else {
                    echo '">';
                }
                echo '</a> ';
            }
            echo '</td>';
            // Colonna "Stampa"
            echo "<td align=\"center\"><a class=\"btn btn-xs btn-default btn-stampa\" href=\"stampa_effett.php?id_tes=" . $r["id_tes"] . "\" target=\"_blank\"><i class=\"glyphicon glyphicon-print\"></i></a></td>";
            // Colonna "Origine"
            echo "<td align=\"center\">";
            // Se id_doc ha un valore diverso da zero, cerca la fattura nella tabella gazXXX_tesdoc.
            if ($r["id_doc"] != 0) {
                //
                $tesdoc_result = gaz_dbi_dyn_query('*', $gTables['tesdoc'], "id_tes = " . $r["id_doc"], 'id_tes', 0, 1);
                //
                $tesdoc_r = gaz_dbi_fetch_array($tesdoc_result);
                if ($tesdoc_r["tipdoc"] == "FAI") {
                    // Fattura immediata
                    echo "<a class=\"btn btn-xs btn-default\" title=\"" . $script_transl['sourcedoc'] . "\" href=\"../vendit/stampa_docven.php?id_tes=" . $tesdoc_r["id_tes"] . "\">ft " . $tesdoc_r["numfat"] . "</a>";
                } elseif ($tesdoc_r["tipdoc"] == "FAD") {
                    // Fattura differita
                    echo "<a class=\"btn btn-xs btn-default\" title=\"" . $script_transl['sourcedoc'] . "\" href=\"../vendit/stampa_docven.php?td=2&si=" . $tesdoc_r["seziva"] . "&pi=" . $tesdoc_r['protoc'] . "&pf=" . $tesdoc_r['protoc'] . "&di=" . $tesdoc_r["datfat"] . "&df=" . $tesdoc_r["datfat"] . "\">ft " . $tesdoc_r["numfat"] . "</a>";
                }
            }
            echo "</td>";
            // Colonna "Elimina"
            echo "<td align=\"center\">";
            ?>
			<a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella il documento e la registrazione contabile relativa" ref="<?php echo $r['id_tes'];?>" ragso="<?php echo $cliente['ragso1']; ?>">
				<i class="glyphicon glyphicon-trash"></i>
			</a>
			</td>
			<?php
			echo "</td></tr>";
        }
        ?>
        <tr><th class="FacetFieldCaptionTD" colspan="13"></th></tr>
    </table>
    </div>
<?php
require("../../library/include/footer.php");
?>
