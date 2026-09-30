<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
// Consultazione database fitofarmaci importato dal Ministero della Salute

require("../../library/include/datlib.inc.php");



$admin_aziend=checkAdmin();
$titolo = 'Campi';
require("../../library/include/header.php");
$script_transl = HeadMain();
$form['nome_fito']="";

print "<form method=\"POST\" enctype=\"multipart/form-data\" id=\"consult-product\">\n";
print "<div align=\"center\" class=\"FacetFormHeaderFont\">CONSULTAZIONE DATABASE FITOFARMACI</div>";
print "<table class=\"Tmiddle table-striped\" align=\"center\">\n";
if (!empty($msg)) {
    $message = "";
    $rsmsg = array_slice( explode('+',chop($msg)),0,-1);
    foreach ($rsmsg as $value){
            $message .= $script_transl['error']."! -> ";
            $rsval = explode('-',chop($value));
            foreach ($rsval as $valmsg){
                    $message .= $script_transl[$valmsg]." ";
            }
            $message .= "<br />";
    }
    echo '<tr><td colspan="5" class="FacetDataTDred">'.$message."</td></tr>\n";
}
?>
<script>
<!-- Antonio Germani inizio script autocompletamento dalla tabella mysql fitofarmaci	-->
$(document).ready(function(){
//Autocomplete search using PHP, MySQLi, Ajax and jQuery
//generate suggestion on keyup
	$('#nomefito').keyup(function(e){
		e.preventDefault();
		var form = $('#consult-product').serialize();
		$.ajax({
			type: 'GET',
			url: 'do_search.php',
			data: form,
			dataType: 'json',
			success: function(response){
				if(response.error){
					$('#product_search').hide();
				}
				else{
					$('#product_search').show().html(response.data);
				}
			}
		});
	});
	//fill the input
	$(document).on('click', '.dropdown-item', function(e){
		e.preventDefault();
		$('#product_search').hide();
		var fullname = $(this).data('fullname');
		$('#nomefito').val(fullname);
		$('#consult-product').submit();
	});
});
<!-- fine autocompletamento -->
</script>

 <tr>
	<td class="FacetFieldCaptionTD">
		NOME FITOFARMACO
	</td>
	<td class="FacetDataTD">
		<div class="col-md-12">
			<input class="col-md-12" type="text" id="nomefito" name="nomefito" value="<?php echo $form['nome_fito']; ?>" placeholder="Ricerca nome fitofarmaco" autocomplete="off" tabindex="1">
			<ul class="dropdown-menu" style="left: 20%; padding: 0px;" id="product_search"></ul>
		</div>
	</td>
</tr>
<?php

if (isset ($_POST['nomefito'])) {
	$form['nome_fito']=$_POST['nomefito'];
	$fito = gaz_dbi_get_row($gTables['camp_fitofarmaci'], 'prodotto', $form['nome_fito']);
	?>

	<tr><td colspan="5" class="FacetDataTDred" align="center">
	<?php echo $form['nome_fito']; ?>
	</td>
	</tr>
	<tr>
	<td class="FacetFieldCaptionTD">NUMERO REGISTRAZIONE</td>
	<td class="FacetDataTD">
	<?php echo $fito['NUMERO_REGISTRAZIONE']; ?>
	</td>
	</tr>
	<tr>
	<td class="FacetFieldCaptionTD">IMPRESA</td>
	<td class="FacetDataTD">
	<?php echo $fito['IMPRESA']; ?>
	</td>
	</tr>
	<tr>
	<td class="FacetFieldCaptionTD">SEDE LEGALE</td>
	<td class="FacetDataTD">
	<?php echo $fito['SEDE_LEGALE_IMPRESA']; ?>
	</td>
	</tr>
	<tr>
	<td class="FacetFieldCaptionTD">SCADENZA AUTORIZZAZIONE</td>
	<td class="FacetDataTD">
	<?php echo $fito['SCADENZA_AUTORIZZAZIONE']; ?>
	</td>
	</tr>
	<tr>
	<td class="FacetFieldCaptionTD">INDICAZIONI DI PERICOLO</td>
	<td class="FacetDataTD">
	<?php echo $fito['INDICAZIONI_DI_PERICOLO']; ?>
	</td>
	</tr>
	<tr>
	<td class="FacetFieldCaptionTD">DESCRIZIONE FORMULAZIONE</td>
	<td class="FacetDataTD">
	<?php echo $fito['DESCRIZIONE_FORMULAZIONE']; ?>
	</td>
	</tr>
	<tr>
	<td class="FacetFieldCaptionTD">SOSTANZE ATTIVE</td>
	<td class="FacetDataTD">
	<?php echo $fito['SOSTANZE_ATTIVE']; ?>
	</td>
	</tr>
	<tr>
	<td class="FacetFieldCaptionTD">CONTENUTO di sostanze attive per 100g di prodotto</td>
	<td class="FacetDataTD">
	<?php echo $fito['CONTENUTO_PER_100G']; ?>
	</td>
	</tr>
	<tr>
	<td class="FacetFieldCaptionTD">ATTIVITà</td>
	<td class="FacetDataTD">
	<?php echo $fito['ATTIVITA']; ?>
	</td>
	</tr>
	<tr>
	<td class="FacetFieldCaptionTD">PPO prodotto per piante ornamentali</td>
	<td class="FacetDataTD">
	<?php echo $fito['PPO']; ?>
	</td>
	</tr>
	</table>
	</form>
  <a href="https://programmisitiweb.lacasettabio.it/quaderno-di-campagna/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:20%; z-index:2000;"> Registro di campagna è un modulo di Antonio Germani</a>
<?php
}
require("../../library/include/footer.php");
?>
