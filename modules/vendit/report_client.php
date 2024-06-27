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
$gForm = new venditForm;
$titolo = 'Clienti';
$clienti = $admin_aziend['mascli'];
$mascli = $clienti . "000000";
// campi ammissibili per la ricerca
$search_fields = [
  'codice' => "codice = $mascli + %d",
  'nome' => "CONCAT(ragso1, ragso2) LIKE '%%%s%%'",
  'idfisc' => "CONCAT(codfis, pariva) LIKE '%%%s%%'",
  'codmin' => "codice >= $mascli + GREATEST(%d, 1)",
  'codmax' => "codice <= $mascli + LEAST(%d, 999999)",
  'sexper' => "sexper = '%s'"
];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array(
  "Codice" => "codice",
  "Ragione Sociale" => "ragso1",
  "Tipo" => "sexper",
  "Citt&agrave;" => "citspe",
  "Telefono" => "telefo",
  "P.IVA - C.F." => "",
  "Privacy" => "",
  "Riscuoti" => "",
  "Visualizza <br /> e/o stampa" => "",
  "Cancella" => ""
);

require("../../library/include/header.php");
if (isset($_GET['privacy'])) {
    echo '<script> window.onload = function() {
    window.open("stampa_privacy.php?codice='.intval($_GET['privacy']).'", "_blank"); // will open new tab on window.onload
} </script>';
}
$script_transl = HeadMain();

$partners = "{$gTables['clfoco']} LEFT JOIN {$gTables['anagra']} ON {$gTables['clfoco']}.id_anagra = {$gTables['anagra']}.id";
$ts = new TableSorter(
    $partners,
    $passo,
    ['codice' => 'desc'],
    ['codmin' => 1, 'codmax' => 999999]
);
?>
<script>
$(function() {
	$("#dialog_vies").dialog({ autoOpen: false });
	$('.dialog_vies').click(function() {
    $("p#pariva").html($(this).attr("country") + " " + $(this).attr("ref"));
    var country = $(this).attr('country');
    var pariva = $(this).attr('ref');
    $.ajax({
      data: {'type':'client', country:country, pariva:pariva},
      type: 'POST',
      url: '../vendit/check_vies.php',
      success: function(output){
                  alert(output);
      }
    });
  });
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
						data: {'type':'client',ref:id},
						type: 'POST',
						url: '../vendit/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_client.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
function clipandgo(pi,url) {
  navigator.clipboard.writeText(pi);
  alert("Partita IVA " + pi + " copiata negli appunti, puoi incollarla sul sito dell'AdE per il controllo");
  window.open(url,'_blank');
}
</script>
<div align="center" class="FacetFormHeaderFont">Clienti</div>
<div align="center"><?php $ts->output_navbar(); ?></div>
<form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="clean_get">
	<div style="display:none" id="dialog_vies" title="Dati VIES">
        <p>Partita IVA:</p>
        <p class="ui-state-highlight" id="pariva"></p>
	</div>
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>cliente:</b></p>
        <p>Codice:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Ragione sociale:</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
    <div class="box-primary table-responsive">
    <table class="Tlarge table table-striped table-bordered table-condensed">
        <tr>
            <td class="FacetFieldCaptionTD">
                <?php gaz_flt_disp_int("codice", "Codice cli."); ?>
            </td>
            <td class="FacetFieldCaptionTD">
                <?php gaz_flt_disp_int("nome", "Nome cliente"); ?>
            </td>
            <td class="FacetFieldCaptionTD">
                <?php gaz_flt_disp_select("sexper", "sexper", $gTables["anagra"], " sexper <> ''", "sexper asc"); ?>
            </td>
            <td class="FacetFieldCaptionTD">
                &nbsp;
            </td>
            <td class="FacetFieldCaptionTD">
                &nbsp;
            </td>
            <td class="FacetFieldCaptionTD">
                <?php gaz_flt_disp_int("idfisc", "C.F. o P.I."); ?>
            </td>
            <td class="FacetFieldCaptionTD">
                &nbsp;
            </td>
            <td class="FacetFieldCaptionTD">
                &nbsp;
            </td>
            <td class="FacetFieldCaptionTD">
                <input type="submit" class="btn btn-sm btn-default" name="search" value="Cerca" tabindex="1" >
                <?php $ts->output_order_form();  ?>
            </td>
            <td class="FacetFieldCaptionTD">
                <a class="btn btn-sm btn-default" href="?">Reset</a>
            </td>
        </tr>
        <?php
        $result = gaz_dbi_dyn_query('*', $partners, $ts->where, $ts->orderby, $ts->getOffset(), $ts->getLimit());
        ?>
        <tr>
            <?php $ts->output_headers(); ?>
        </tr>
        <?php
$accmov=[];
$rs=gaz_dbi_query("SELECT clfoco , COUNT(*) FROM ".$gTables['tesdoc']."  GROUP BY clfoco");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};
$rs=gaz_dbi_query("SELECT clfoco, COUNT(*) FROM ".$gTables['tesbro']."  GROUP BY clfoco");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};
$rs=gaz_dbi_query("SELECT clfoco, COUNT(*) FROM ".$gTables['tesmov']."  GROUP BY clfoco");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};
while ($r = gaz_dbi_fetch_array($result)) {
  echo "<tr class=\"FacetDataTD\">";
  // Colonna codice cliente
  echo "<td align=\"center\"><a class=\"btn btn-xs btn-edit\" href=\"admin_client.php?codice=" . substr($r["codice"], 3) . "&Update\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;" .intval(substr($r["codice"],3)) . "</a> &nbsp</td>";
  // Colonna ragione sociale
  echo '<td><span class="gazie-tooltip col-xs-12" data-type="anagra-thumb" data-id="'. $r['codice'] .'" data-title="'. $r["ragso1"].' '.$r["ragso2"].'">' . $r["ragso1"] . " </span></td>";
  // colonna sesso
  echo "<td align=\"center\">" . $r["sexper"] . "</td>";
  // colonna indirizzo
  $google_string = str_replace(" ", "+", $r["indspe"]) . "," . str_replace(" ", "+", $r["capspe"]) . "," . str_replace(" ", "+", $r["citspe"]) . "," . str_replace(" ", "+", $r["prospe"]);
  echo "<td title=\"" . $r["capspe"] . " " . $r["indspe"] . "\">";
  echo "<a class=\"btn btn-xs btn-default\" rel=\"noreferrer\" target=\"_blank\" href=\"https://www.google.it/maps/place/" . $google_string . "\">" . $r["citspe"] . " (" . $r["prospe"] . ")&nbsp;<i class=\"glyphicon glyphicon-map-marker\"></i></a>";
  echo "<a class=\"btn btn-xs btn-default\" rel=\"noreferrer\" target=\"_blank\" href=\"https://www.google.it/maps/dir/" . $admin_aziend['latitude'] . "," . $admin_aziend['longitude'] . "/" . $google_string . "\">  <i class=\"glyphicon glyphicon-random\"></i></a>";
  echo "</td>";
  // composizione telefono
  $title = "";
  $telefono = "";
  if (!empty($r["telefo"])) {
      $telefono = $r["telefo"];
      if (!empty($r["cell"])) {
          $title .= "cell:" . $r["cell"];
      }
      if (!empty($r["fax"])) {
          $title .= " fax:" . $r["fax"];
      }
  } elseif (!empty($r["cell"])) {
      $telefono = $r["cell"];
      if (!empty($r["fax"])) {
          $title .= " fax:" . $r["fax"];
      }
  } elseif (!empty($r["fax"])) {
      $telefono = "fax:" . $r["fax"];
  } else {
      $telefono = "_";
      $title = " nessun contatto telefonico memorizzato ";
  }
  // colonna telefono
  echo "<td title=\"$title\" align=\"center\">" . gaz_html_call_tel($telefono) . " &nbsp;</td>";
  // colonna fiscali
  if ($r['pariva'] > 0 && empty($r['codfis'])) {
      echo "<td align=\"center\">" . gaz_html_ae_checkiva($r['country'], $r['pariva']) . "</td>";
  } elseif ($r['pariva'] < 1 && !empty($r['codfis'])) {
      echo "<td align=\"center\">" . $r['codfis'] . "</td>";
  } elseif ($r['pariva'] >= 1 && !empty($r['codfis'])) {
      if ($r['pariva'] == $r['codfis']) {
          echo '<td align="center">'.gaz_html_ae_checkiva($r['country'], $r['pariva']).'</td>';
      } else {
          echo "<td align=\"center\">" . gaz_html_ae_checkiva($r['country'], $r['pariva']) . "<br/>" . $r['codfis'] . "</td>";
      }
  } else {
      echo "<td class=\"FacetDataTDred\" align=\"center\"> * NO * </td>";
  }
  // colonna stampa privacy
  echo "<td align=\"center\"><a title=\"stampa informativa sulla privacy\" class=\"btn btn-xs btn-default\" href=\"stampa_privacy.php?codice=" . $r["codice"] . "\" target=\"_blank\"><i class=\"glyphicon glyphicon-eye-close\"></i></a><a title=\"stampa richiesta codice sdi o pec\" class=\"btn btn-xs btn-default\" href=\"stampa_richiesta_pecsdi.php?codice=" . $r["codice"] . "\" target=\"_blank\"><i class=\"glyphicon glyphicon-inbox\"></i></a></td>";
  echo "<td title=\"Effettuato un pagamento da " . $r["ragso1"] . "\" align=\"center\"><a class=\"btn btn-xs btn-default btn-pagamento\" href=\"customer_payment.php?partner=" . $r["codice"] . "\"><i class=\"glyphicon glyphicon-euro\"></i></a></td>";
  echo "<td title=\"Visualizza e stampa il partitario\" align=\"center\">  <a class=\"btn btn-xs btn-default\" href=\"report_contcli.php?id=".$r["codice"]."\"  target=\"_blank\"><i class=\"glyphicon glyphicon-list-alt\"></i></a> <a class=\"btn btn-xs btn-default\" href=\"../contab/select_partit.php?id=".$r["codice"]."\" target=\"_blank\"><i class=\"glyphicon glyphicon-check\"></i>&nbsp;<i class=\"glyphicon glyphicon-print\"></a></td>";
  echo "<td align=\"center\">";
  if (isset($accmov[$r["codice"]])){
    ?>
    <button title="Impossibile cancellare perché ci sono dei movimenti associati" class="btn btn-xs btn-default disabled"><i class="glyphicon glyphicon-trash"></i></button>
    <?php
  } else {
    ?>
    <a class="btn btn-xs btn-elimina dialog_delete" title="Cancella il cliente" ref="<?php echo $r['codice'];?>" ragso="<?php echo $r['ragso2']," ",$r['ragso1'];?>"><i class="glyphicon glyphicon-trash"></i></a>
    <?php
  }
  echo "</td></tr>\n";
}
?>
    </table>
  </div>
</form>

<script src="../../js/custom/fix_select.js" type="text/javascript"></script>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>

