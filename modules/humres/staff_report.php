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
$mas_staff = $admin_aziend['mas_staff'] . "000000";
$worker = $admin_aziend['mas_staff'];
$where = "codice BETWEEN " . $worker . "000000 AND " . $worker . "999999 and codice > $mas_staff";

if (isset($_GET['auxil'])) {
    $auxil = filter_input(INPUT_GET, 'auxil');
} else {
    $auxil = "";
}

if (isset($_GET['auxil1'])) {
    $auxil1 = filter_input(INPUT_GET, 'auxil1');
} else {
    $auxil1 = "";
}

if (isset($_GET['all'])) {
  $auxil = "&all=yes";
  $passo = 100000;
} else {
  $where .= " AND status <>'HIDDEN' ";
  if (isset($_GET['auxil']) and $auxil1 == "") {
      $where .= " AND ragso1 LIKE '" . addslashes($auxil) . "%'";
  } elseif (isset($_GET['auxil1'])) {
      $codicetemp = intval($auxil1);
      $where .= " AND id_contract = " . $codicetemp ;
  }
}

if (!isset($_GET['field'])||strlen($_GET['field'])<2) {
    $orderby = "id_contract ASC";
}

if (isset($_GET['ricerca_completa'])) {
    $ricerca_testo = filter_input(INPUT_GET, 'ricerca_completa');
    $where .= " and ( ragso1 like '%" . $ricerca_testo . "%' ";
    $where .= " or ragso2 like '%" . $ricerca_testo . "%' ";
    $where .= " or pariva like '%" . $ricerca_testo . "%' ";
    $where .= " or pariva like '%" . $ricerca_testo . "%' ";
    $where .= " or codfis like '%" . $ricerca_testo . "%' ";
    $where .= " or citspe like '%" . $ricerca_testo . "%' )";
}

require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("staffdes"));
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
						data: {'type':'staff',ref:id},
						type: 'POST',
						url: '../humres/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./staff_report.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<div align="center" class="FacetFormHeaderFont"> <?php
    echo $script_transl['title'] . '</div>';
    if ($admin_aziend['mas_staff'] <= 199) {
        echo '<div class="alert alert-danger text-center" role="alert">' . $script_transl['errors'] . '</div>';
    }
    ?>
    <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>lavoratore:</b></p>
        <p>codice:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Descrizione</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
	<div class="table-responsive">
        <table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
            <tr>
                <td class="FacetFieldCaptionTD">
                    <input placeholder="Cerca" class="input-xs form-control" type="text" name="auxil1" value="<?php echo $auxil1 ?>" maxlength="6" tabindex="1" class="FacetInput">
                </td>
                <td class="FacetFieldCaptionTD">
                    <input placeholder="Cerca Cognome" class="input-xs form-control" type="text" name="auxil" value="<?php if ($auxil != "&all=yes") echo $auxil; ?>" maxlength="6" tabindex="1" class="FacetInput">
                </td>
                <td>
                    <input type="submit" class="btn btn-xs btn-default" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value = 1;">
                </td>
                <td colspan="3">
                    <input type="submit" class="btn btn-xs btn-default" name="all" value="Mostra tutti (anche i nascosti)" onClick="javascript:document.report.all.value = 1;">
                </td>
            </tr>
            <tr>
                <?php
				$groupby= "codice";
                $result = gaz_dbi_dyn_query('*', $gTables['clfoco'] . ' LEFT JOIN ' . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id'
						. ' LEFT JOIN ' . $gTables['rigmoc'] . ' ON ' . $gTables['rigmoc'] . '.codcon = ' . $gTables['clfoco'] . '.codice'
                        . ' LEFT JOIN ' . $gTables['staff'] . ' ON ' . $gTables['staff'] . '.id_clfoco = ' . $gTables['clfoco'] . '.codice', $where. ' AND SUBSTRING(' . $gTables['staff'] . '.id_clfoco,4,6) > 0', $orderby, $limit, $passo, $groupby);
                $recordnav = new recordnav($gTables['clfoco'] . ' LEFT JOIN ' . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id LEFT JOIN ' . $gTables['staff'] . ' ON ' . $gTables['clfoco'] . '.codice = ' . $gTables['staff'] . '.id_clfoco', $where, $limit, $passo);
                $recordnav->output();
                ?>
            </tr>
            <tr>
                <?php
                $linkHeaders = new linkHeaders($script_transl['header']);
                $linkHeaders->setAlign(array('left', 'left', 'left', 'center', 'left', 'center', 'center', 'center', 'center', 'center','center'));
                $linkHeaders->output();
                ?>
            </tr>
            <?php
// creo la matrice con il numero di presenze del collaboratore
$accmov=[];
$rs=gaz_dbi_query("SELECT id_staff , COUNT(*) FROM ".$gTables['staff_worked_hours']." GROUP BY id_staff");
while ($r=gaz_dbi_fetch_row($rs)) {
  $accmov[$r[0]]=isset($accmov[$r[0]])?($accmov[$r[0]]+$r[1]):(int)$r[1];
};

            while ($r = gaz_dbi_fetch_array($result)) {
                echo "<tr>";
                // Colonna codice staffe
                echo "<td align=\"center\"><a class=\"btn btn-xs btn-edit\" href=\"admin_staff.php?codice=" . substr($r["id_clfoco"], 3) . "&Update\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;" . intval($r["id_contract"]) . "</a> &nbsp</td>";
                // Colonna cognome
                echo "<td>" . $r["ragso1"] . " &nbsp;</td>";
                // Colonna nome
                echo "<td>" . $r["ragso2"] . " &nbsp;</td>";
                // colonna sesso
                echo "<td align=\"center\">" . $r["sexper"] . "</td>";
                // Colonna mansione
                echo "<td>" . $r["job_title"] . " &nbsp;</td>";
                // colonna indirizzo
                $google_string = str_replace(" ", "+", $r["indspe"]) . "," . str_replace(" ", "+", $r["capspe"]) . "," . str_replace(" ", "+", $r["citspe"]) . "," . str_replace(" ", "+", $r["prospe"]);
                echo "<td title=\"" . $r["capspe"] . " " . $r["indspe"] . "\">";
                echo "<a class=\"btn btn-xs btn-default\" target=\"_blank\" href=\"https://www.google.it/maps/place/" . $google_string . "\">" . $r["citspe"] . " (" . $r["prospe"] . ")&nbsp;<i class=\"glyphicon glyphicon-map-marker\"></i></a>";
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
                } else {
                    $telefono = "_";
                    $title = " nessun contatto telefonico memorizzato ";
                }
                // colonna iban
                echo "<td align=\"center\">" . $r["iban"] . " &nbsp;</td>";
                // colonna telefono
                echo "<td title=\"$title\" align=\"center\">" . gaz_html_call_tel($telefono) . " &nbsp;</td>";
                // colonna fiscali
                echo "<td align=\"center\">" . $r['codfis'] . "</td>";
                // colonna contabilità
                echo '<td align="center"><a class="btn btn-xs btn-default" href="../contab/select_partit.php?id=' . $r["id_clfoco"] . '" target="_blank">
                    <i class="glyphicon glyphicon-check"></i>&nbsp;<i class="glyphicon glyphicon-print"></i>
                    </a></td>';
                // colonna stampa privacy
                echo "<td align=\"center\">";
				if (intval($r['codcon']) > 0){
					?>
					<button title="Collaboratore non cancellabile, ha movimenti contabili associati" class="btn btn-xs   disabled"><i class="glyphicon glyphicon-trash"></i></button>
					<?php
				} else if (isset($accmov[$r['id_staff']])){
          echo '<button class="btn btn-xs  btn-default" disabled title="Il collaboratore non è eliminabile perché è presente su '. $accmov[$r['id_staff']].' registrazioni"><i class="glyphicon glyphicon-trash"></i></button>';
        }  else {
				?>
				<a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $r['id_clfoco'];?>" staffdes="<?php echo $r['ragso1']; ?>">
					<i class="glyphicon glyphicon-trash"></i>
				</a>
				<?php
				}
				echo "</td></tr>\n";
            }
            ?>
    </form>
</table></div>
<?php
require("../../library/include/footer.php");
?>
