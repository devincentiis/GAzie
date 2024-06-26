<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
  (http://www.devincentiis.it)
  <http://gazie.sourceforge.net>
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

require("../../library/include/header.php");
$script_transl = HeadMain();

if (isset($_POST['hidden_req'])) { // accessi successivi allo script
    $form['hidden_req'] = $_POST["hidden_req"];
    $form['ritorno'] = $_POST['ritorno'];
} else {
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
}
?>
<form method="POST">
    <input type="hidden" value="<?php echo $form['hidden_req']; ?>" name="hidden_req" />
    <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno" />
    <div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['title']; ?></div>
    <!--<div class="tab-content">-->
        <div id="lista" class="tab-pane fade in active">
            <div class="table-responsive">

                <table class="Tlarge table table-striped table-bordered table-condensed">
                    <tr>
                        <th class="FacetFieldCaptionTD">ID</th>
                        <th class="FacetFieldCaptionTD"><?php echo $script_transl['anno']; ?></th>
                        <th class="FacetFieldCaptionTD"><?php echo $script_transl['periodicita']; ?></th>
                        <th class="FacetFieldCaptionTD"><?php echo $script_transl['trimestre_semestre']; ?></th>
                        <th class="FacetFieldCaptionTD">File DTE</th>
                        <th class="FacetFieldCaptionTD">File DTR</th>
                        <th class="FacetFieldCaptionTD">File ZIP</th>
                        <?php
                         if ( $admin_aziend['Abilit']==9 )  echo '<th class="FacetFieldCaptionTD">Elimina</th>';
                        ?>
                        </tr>
                            <?php
                            $result = gaz_dbi_dyn_query('*', $gTables['comunicazioni_dati_fatture'], "nome_file_ZIP LIKE '%DF_Z%'", 'anno DESC, trimestre_semestre DESC');
                            while ($row = gaz_dbi_fetch_array($result)) {
                                echo "<tr class=\"FacetDataTD\">";
                                echo "<td><a class=\"btn btn-xs btn-default\" href=\"comunicazione_dati_fatture.php?id=" . $row["id"] . "&Update\"><i class=\"glyphicon glyphicon-folder-open\"></i>&nbsp;&nbsp;" . $row["id"] . "</a> &nbsp</td>";
                                echo "<td align=\"center\">" . $row['anno'] . " &nbsp;</td>";
                                echo '<td align="center">' . $script_transl['periodicita_value'][$row['periodicita']] . ' &nbsp;</td>';
                                echo '<td align="center">' . $script_transl['trimestre_semestre_value'][$row['periodicita']][$row['trimestre_semestre']] . ' &nbsp;</td>';
                                echo "<td align=\"center\">" . $row['nome_file_DTE'] . " &nbsp;</td>";
                                echo "<td align=\"center\">" . $row['nome_file_DTR'] . " &nbsp;</td>";
                                echo '<td align="center"><a class="btn btn-xs btn-default" href="download_comunicazione_dati_fatture.php?id='.$row["id"].'">'. $row['nome_file_ZIP'] .'<i class="glyphicon glyphicon-download"></i></a> &nbsp;</td>';
                                if ( $admin_aziend['Abilit']==9 )
                                    echo '<td align="center"><a class="btn btn-xs  btn-elimina dialog_delete" ref="'.$row['id'].'"><i class="glyphicon glyphicon-trash"></i></a> &nbsp;</td>';
                                echo "</tr>";
                            }
                            ?>
                </table>
            </div>
        </div>
    <!--</div>-->

<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
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
						data: {'type':'comunicazioni_dati_fatture',ref:id},
						type: 'POST',
						url: '../contab/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_comunicazioni_dati_fatture.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>

    <div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>Comunicazioni dati fatture:</b></p>
        <p>IDentificativo:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
	</div>
    <div style="display:none" id="dialog" title="<?php echo $script_transl['mail_alert0']; ?>">
        <p id="mail_alert1"><?php echo $script_transl['mail_alert1']; ?></p>
        <p class="ui-state-highlight" id="mail_adrs"></p>
        <p id="mail_alert2"><?php echo $script_transl['mail_alert2']; ?></p>
        <p class="ui-state-highlight" id="mail_attc"></p>
    </div>

    <div style="display:none" id="dialog1" title="<?php echo $script_transl['fae_alert0']; ?>">
        <p id="fae_alert1"><?php echo $script_transl['fae_alert1']; ?></p>
        <p class="ui-state-highlight" id="fae1"></p>
        <p id="fae_alert2"><?php echo $script_transl['fae_alert2']; ?><span id="fae2" class="bg-warning"></span></p>
    </div>

    <div style="display:none" id="dialog2" title="<?php echo $script_transl['report_alert0']; ?>">
        <p id="report_alert1"><?php echo $script_transl['report_alert1']; ?></p>
        <p class="ui-state-highlight" id="report1"></p>
    </div>

    <div style="display:none" id="dialog3" title="<?php echo $script_transl['faesdi_alert0']; ?>">
        <p id="faesdi_alert1"><?php echo $script_transl['faesdi_alert1']; ?></p>
        <p class="ui-state-highlight" id="mailpecsdi"></p>
    </div>


</form>
<?php
require("../../library/include/footer.php");
?>
