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
$admin_aziend=checkAdmin(6); // consento di operare solo a quelli con livello di almeno "Capo reparto"
$company_choice = gaz_dbi_get_row($gTables['config'], 'variable', 'users_noadmin_all_company')['cvalue'];
require("../../library/include/header.php");
$script_transl = HeadMain();
// visualizzo solo gli utenti con livello inferiore o uguale a quello dell'utente stesso e facente parte della stessa azienda in cui sto operando
$where='Abilit <='.$admin_aziend['Abilit'].' AND Abilit <= 7  AND (company_id = '.$_SESSION['company_id'].' OR company_id = 0)';
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
						data: {'type':'worker',ref:id},
						type: 'POST',
						url: './delete.php',
						success: function(output){
							window.location.replace("./report_healtworkers.php");
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
	<p><b>Utente:</b></p>
	<p>Nickname:</p>
	<p class="ui-state-highlight" id="idcodice"></p>
	<p>Nome e cognome:</p>
	<p class="ui-state-highlight" id="iddescri"></p>
</div>
<div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['title']; ?></div>
<div class="table-responsive"><table class="Tlarge table table-striped table-bordered table-condensed">
<?php
$headers_healtworkers=[
  $script_transl["user_name"] => "user_name",
  $script_transl['user_lastname'] => "",
  $script_transl['user_firstname'] => "",
  'matricola' => "id_contract",
  $script_transl['Abilit'] => "Abilit",
  'Luogo' => "codice_campi",
  $script_transl['Access'] => "Access",
  $script_transl['delete'] => ""
];
$linkHeaders = new linkHeaders($headers_healtworkers);
$linkHeaders -> output();
$result = gaz_dbi_dyn_query ('*',$gTables['staff']." stf LEFT JOIN ".$gTables['clfoco']." cfc ON stf.id_clfoco = cfc.codice
            LEFT JOIN ".$gTables['campi']." cmp ON stf.codice_campi = cmp.codice
            LEFT JOIN ".$gTables['anagra']." ngr ON ngr.id = cfc.id_anagra
            LEFT JOIN ".$gTables['admin']." dmn ON cfc.id_anagra = dmn.id_anagra", $where, $orderby, $limit, $passo);
$dto = new DateTime();
$anno = $dto->format("Y");
$mese = $dto->format("m");

while ($r = gaz_dbi_fetch_array($result)) {
  echo "<tr>";
  echo '<td title="'.$script_transl['update'].'" align="center"><a class="btn btn-xs btn-edit" href="admin_healtworker.php?user_name='.$r["user_name"].'&Update">'.$r["user_name"].' </a> &nbsp; </td>';
  echo '<td class="text-center">'.$r["user_lastname"]." </td>";
  echo '<td class="text-center">'.$r["user_firstname"]." </td>";
  echo '<td class="text-center">'.$r["id_contract"]." </td>";
  echo '<td class="text-center">'.$script_transl['Abilit_value'][$r["Abilit"]]." </td>";
  echo '<td class="text-center">'.$r['descri']." </td>";
  echo '<td class="text-center">'.$r["Access"]." </td><td align=\"center\">";
  if ($admin_aziend['Abilit']>=8){
?>
		<a class="btn btn-xs btn-elimina dialog_delete" title="Cancella l'operatore" ref="<?php echo $r['user_name'];?>" ragso="<?php echo $r['user_firstname'].' '.$r['user_lastname'];?>">
			<i class="glyphicon glyphicon-trash"></i>
		</a>
<?php
  }
  echo '</td></tr>
';
}
?>
</table></div>
<?php
require("../../library/include/footer.php");
?>
