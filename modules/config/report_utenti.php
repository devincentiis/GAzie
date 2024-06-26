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
$admin_aziend=checkAdmin(9);
$company_choice = gaz_dbi_get_row($gTables['config'], 'variable', 'users_noadmin_all_company')['cvalue'];
require("../../library/include/header.php");
$script_transl = HeadMain('','','admin_utente');
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
						data: {'type':'utente',ref:id},
						type: 'POST',
						url: '../config/delete.php',
						success: function(output){
							window.location.replace("./report_utenti.php");
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
<div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['report']; ?></div>
<?php
$recordnav = new recordnav($gTables['admin'], $where, $limit, $passo);
$recordnav -> output();
?>
<div class="table-responsive"><table class="Tlarge table table-striped table-bordered table-condensed">
<?php
$headers_utenti=[
  $script_transl["user_name"] => "user_name",
  $script_transl['user_lastname'] => "Cognome",
  $script_transl['user_firstname'] => "Nome",
  $script_transl['Abilit'] => "Abilit",
  $script_transl['company'] => "",
  'Privacy'=>'',
  'Tema' => "",
  $script_transl['Access'] => "Access",
  $script_transl['delete'] => ""
];
$linkHeaders = new linkHeaders($headers_utenti);
$linkHeaders -> output();

// posso eliminare gli utenti amministratori solo se non sono soli
$rs_admins = gaz_dbi_dyn_query("user_id", $gTables['admin'], " Abilit = 9 ", "user_id",0);
$admins = gaz_dbi_num_rows($rs_admins);

$result = gaz_dbi_dyn_query ('*', $gTables['admin'], $where, $orderby, $limit, $passo);
while ($r = gaz_dbi_fetch_array($result)) {
	// RESPONSABILE O INCARICATO: DIPENDE DAL LIVELLO DI ABILITAZIONE
	$ri_descr='stampa nomina INCARICATO trattamento dati personali';
	$regol_lnk='';
	$company = gaz_dbi_get_row($gTables['aziend'], 'codice', $r['company_id']);
  $tema = explode('/',gaz_dbi_get_row($gTables['admin_config'], 'adminid', $r["user_name"], " AND var_name ='theme'")['var_value']);
	if ($r["Abilit"]>8){
		$company['ragso1']=$script_transl['all'];
		$ri_descr='stampa nomina RESPONSABILE trattamento dati personali';
		$regol_lnk=' _ <a title="stampa e/o edita il REGOLAMENTO per l’utilizzo e la gestione delle risorse informatiche" class="btn btn-xs btn-default" href="edit_privacy_regol.php?user_id=' . $r["user_id"] . '" target="_blank"><i class="glyphicon glyphicon-list"></i></a> ';
	}
	if ($company_choice>0){
		$company['ragso1']=$script_transl['all'];
	}
  echo "<tr>";
  echo "<td title=\"".$script_transl['update']."\" align=\"center\"><a class=\"btn btn-xs btn-edit\" href=\"admin_utente.php?user_name=".$r["user_name"]."&Update\">".$r["user_name"]." </a> &nbsp</td>";
  echo "<td>".$r["user_lastname"]." </td>";
  echo "<td>".$r["user_firstname"]." </td>";
  echo "<td align=\"center\">".$r["Abilit"]." </td>";
  echo "<td>".$company['ragso1']." </td>";
  // colonna stampa nomina trattamento dati personali
  echo "<td align=\"center\"><a title=\"".$ri_descr."\" class=\"btn btn-xs btn-default\" href=\"stampa_nomina.php?user_id=" . $r["user_id"] . "\" target=\"_blank\"><i class=\"glyphicon glyphicon-eye-close\"></i></a>".
	$regol_lnk."
	</td>";
	// colonna tema
  echo '<td align="center">'.$tema[3].'</td>';
  echo "<td align=\"center\">".$r["Access"]." </td><td align=\"center\">";
  if ($admins <=1 && $r["Abilit"] == 9 ){
		?>
		<button title="Impossibile cancellare perché è l'unico amministratore " class="btn btn-xs   disabled"><i class="glyphicon glyphicon-trash"></i></button>
		<?php
	} else {
		?>
		<a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella l'utente" ref="<?php echo $r['user_name'];?>" ragso="<?php echo $r['user_firstname'].' '.$r['user_lastname'];?>">
			<i class="glyphicon glyphicon-trash"></i>
		</a>
		<?php
	}
  echo "</td></tr>";
}
?>
</table></div>
<?php
require("../../library/include/footer.php");
?>
