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
require('../../library/include/datlib.inc.php');
$admin_aziend = checkAdmin();

$msg = '';

if (isset($_POST['Update']) || isset($_GET['Update'])) {
	$toDo = 'update';
} else {
	$toDo = 'insert';
}

if (isset($_POST['Insert']) || isset($_POST['Update'])) { //se non e' il primo accesso	
	$form=gaz_dbi_parse_post('assist');
	$anagrafica = new Anagrafica();
	$cliente = $anagrafica->getPartner($_POST['clfoco']);
	if ( isset($_POST['hidden_req']) ) $form['hidden_req'] = $_POST['hidden_req'];
	// ...e della testata
	foreach($_POST['search'] as $k=>$v){
		$form['search'][$k]=$v;
	}
	$form['id'] = $_POST['id'];
	$form['cosear'] = $_POST['cosear'];
	$form['codart'] = $_POST['codart'];
	$form['codice'] = trim($form['codice']);
	$form['tipo'] = 'ASP';
	$form['descrizione'] = $_POST['descrizione'];
	//$form['soluzione'] = $_POST['soluzione'];
	$form['clfoco'] = $_POST['clfoco'];
	$form['stato'] = $_POST['cstato'];
	$form['ritorno'] = $_POST['ritorno'];
	$form['ref_code'] = $_POST['ref_code'];
	//$form['ore'] = $_POST['ore'];
	//$form['ora_inizio'] = $_POST['ora_inizio'];
	//$form['ora_fine'] = $_POST['ora_fine'];
	$form['ripetizione'] = $_POST['ripetizione'];
	$form['ogni'] = $_POST['ogni'];
	$form['utente'] = $_SESSION['user_name'];

	$form['rows'] = array();	
	if (isset($_POST['Submit'])) {
		// conferma tutto
		if ($toDo == 'update') {
			// controlli in caso di modifica
			if ($form['codice'] != $form['ref_code']) { 
				// se sto modificando il codice originario
				// controllo che l'articolo ci sia gia'
				$rs_assist = gaz_dbi_dyn_query('codice', $gTables['assist'], "codice = ".$form['codice'],"codice DESC",0,1);
				$rs = gaz_dbi_fetch_array($rs_assist);
				if ($rs) { 
					$msg .= '0+';
				}
			}
		} else {
			// controllo che l'articolo ci sia gia'
			$rs_articolo = gaz_dbi_dyn_query('codice', $gTables['assist'], "codice = ".$form['codice'],"codice DESC",0,1);
			$rs = gaz_dbi_fetch_array($rs_articolo);
			if ($rs) {
				$msg .= '2+';
			}
		}
		$msg .= (empty($form['codice']) ? '5+' : '');
		//$msg .= (empty($form['descrizione']) ? '6+' : '');
		if (empty($msg)) { 
			if (preg_match("/^id_([0-9]+)$/",$form['clfoco'],$match)) {
				$new_clfoco = $anagrafica->getPartnerData($match[1],1);
				$form['clfoco'] = $anagrafica->anagra_to_clfoco($new_clfoco,$admin_aziend['mascli']);
			}
			// aggiorno il db
			if ($toDo == 'insert') {
				if ( $form['clfoco']==0 ) $form['clfoco'] = $admin_aziend['mascli'] . '000001';
				gaz_dbi_table_insert('assist', $form);
			} elseif ($toDo == 'update') {
				if ( $form['clfoco']==0 ) $form['clfoco'] = $admin_aziend['mascli'] . '000001';
				gaz_dbi_table_update('assist', $form['ref_code'], $form);
			}
			header('Location: associa_install.php?id='.$form['codice'].'&clfoco='.$form['clfoco'].'&ritorno='.$form['ritorno']);
			exit;
		}
	} elseif (isset($_POST['Return'])) { // torno indietro
		header('Location: report_period.php');
		exit;
	}

} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) {

	$assist = gaz_dbi_get_row($gTables['assist'], "codice", $_GET['codice']);
	//se e' il primo accesso per UPDATE
	$anagrafica = new Anagrafica();
	$cliente = $anagrafica->getPartner($assist['clfoco']);
	$form = gaz_dbi_get_row($gTables['assist'], "codice", $_GET['codice']);
	$form['search']['clfoco'] = substr($cliente['ragso1'], 0, 10);
	$form['codart'] = $assist['codart'];
	$form['cosear'] = $assist['codart'];
	$form['ritorno'] = '../../modules/suppor/report_period.php';
	$form['ref_code'] = $form['codice'];

} else {
	//se e' il primo accesso per INSERT
	$form = gaz_dbi_fields("assist");
	$rs_ultima_ass = gaz_dbi_dyn_query("codice", $gTables['assist'], $where, "codice desc");
	$ultimo_documento = gaz_dbi_fetch_array($rs_ultima_ass);
	// se e' il primo documento dell'anno, resetto il contatore
	if ($ultimo_documento) {
		$form['codice'] = $ultimo_documento['codice'] + 1;
	} else {
		$form['codice'] = 1;
	}
	$form['tipo'] = 'ASS';
	$form['utente'] = $_SESSION['user_name'];
	$form['data'] = date('Y-m-d');
	$form['cosear'] = '';
	$form['codart'] = '';

	$rs_ultimo_tec = gaz_dbi_dyn_query("codice,tecnico", $gTables['assist'],"tecnico<>''","codice desc");
	$ultimo_tecnico = gaz_dbi_fetch_array($rs_ultimo_tec);
	$form['tecnico'] = $ultimo_tecnico['tecnico'];
	//$form['ore'] = '0.00';
	$form['stato'] = '0';
	$form['ripetizione'] = 1;
	$form['ogni'] = 'Anni';
	//echo $form['stato'];
	$form['search']['clfoco'] = '';
	$form['ritorno'] = '../../modules/suppor/report_period.php';//$_SERVER['HTTP_REFERER'];
	$form['ref_code'] = '';
}

// disegno maschera di inserimento modifica
require('../../library/include/header.php');
$script_transl = HeadMain();

if ($toDo == 'insert') echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['ins_this']."</div>";
else echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['upd_this']." '".$form['codice']."'</div>";
if (!empty($msg)) echo $script_transl['errors'][substr($msg, 0, 1)];
$select_cliente = new selectPartner('clfoco');
?>
<form method="POST" name="form" enctype="multipart/form-data">
<input type="hidden" name="ritorno" value="<?php echo $form['ritorno']; ?>">
<input type="hidden" name="ref_code" value="<?php echo $form['ref_code']; ?>">
<input type="hidden" name="codice" value="<?php echo $form['codice']; ?>">
<input type="hidden" name="id" value="<?php echo $form['id']; ?>">
<input type="hidden" name="<?php echo ucfirst($toDo); ?>" value="">
<table class="Tmiddle">
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['codice']; ?> </td>
	<td colspan="2" class="FacetDataTD">
		<button ><?php echo $form['codice']; ?></button>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD">Data</td>
	<td colspan="2" class="FacetDataTD">
		<input type="text" name="data" value="<?php echo $form['data']; ?>" align="right" maxlength="255"/>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['cliente']; ?> </td>
	<td colspan="2" class="FacetDataTD">
	<?php 
		$select_cliente->selectDocPartner('clfoco', $form['clfoco'], $form['search']['clfoco'], 'clfoco', $script_transl['mesg'], $admin_aziend['mascli']);
	?>
</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['tecnico']; ?> </td>
	<td colspan="2" class="FacetDataTD">
		<select name="ctecnico" onchange="updateInputTecnico(this.value)">
		<?php
		$result = gaz_dbi_dyn_query(" DISTINCT ".$gTables['assist'].".tecnico", $gTables['assist'],"", "tecnico", "0", "9999");
		while ($tecnici = gaz_dbi_fetch_array($result)) {
			if ( $form['tecnico'] == $tecnici['tecnico'] ) $selected = 'selected'; 
			else $selected = '';
			echo '<option value="'.$tecnici['tecnico'].'" '.$selected.'>'.$tecnici['tecnico'].'</option>';
		}
		?>
		</select> 
		<input type="text" name="tecnico" id="tecnico" value="<?php echo $form['tecnico']; ?>" align="right" maxlength="255"/>
		<button id="toggleTec" type="button">Altro</button>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['oggetto']; ?> </td>
	<td colspan="2" class="FacetDataTD">
		<input type="text" name="oggetto" value="<?php echo $form['oggetto']; ?>" align="right" maxlength="255"/>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['descrizione']; ?> </td>
	<td colspan="2" class="FacetDataTD">
		<textarea type="text" name="descrizione" align="right" cols="67" rows="3" class="mceClass"><?php echo $form['descrizione']; ?></textarea>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD">Articolo</td>
	<td class="FacetDataTD">
		<?php
		$select_artico = new selectartico('codart');
		$select_artico->addSelected($form['codart']);
		$select_artico->output($form['cosear']);
		?>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['prezzo']; ?> </td>
	<td colspan="2" class="FacetDataTD">
		<input type="text" name="prezzo" value="<?php echo $form['prezzo']; ?>" align="right" maxlength="255"/>
	</td>
</tr>
<?php
/*$part = explode( '-', $form['ripetizione'] );
if ( $part[1] == 'G' ) $durata = 'Giorni';
if ( $part[1] == 'M' ) $durata = 'Mesi';
if- ( $part[1] == 'A' ) $durata = 'Anni';/*/
$durata = $form['ogni'];
?>
<tr>
	<td class="FacetFieldCaptionTD">Ripeti ogni</td>
	<td colspan="2" class="FacetDataTD">
		<input type="text" name="ripetizione" align="right" maxlength="255" value="<?php echo $form['ripetizione']; ?>">
		<select name="ogni">
			<option value="Nessuna" <?php if ($durata == 'Nessuna') echo 'selected'; ?>>Nessuna</option>
			<option value="Giorni" <?php if ($durata == 'Giorni') echo 'selected'; ?>>Giorni</option>
			<option value="Mesi" <?php if ($durata == 'Mesi') echo 'selected'; ?>>Mesi</option>
			<option value="Anni" <?php if ($durata == 'Anni') echo 'selected'; ?>>Anni</option>
		</select>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['stato']; ?> </td>
	<td colspan="2" class="FacetDataTD">
		<select name="cstato" onchange="updateInputStato(this.value)">
			<?php
			/*$result = gaz_dbi_dyn_query(" DISTINCT ".$gTables['assist'].".stato", $gTables['assist']," stato!='aperto' and stato != 'chiuso'", "stato", "0", "9999");
			while ($stati = gaz_dbi_fetch_array($result)) {
				if ( $form['stato']==$stati['stato'] ) {exit();$selected = 'selected';} 
					else $selected = '';
					echo "<option value=\"".$stati['stato']."\" ".$selected.">".$stati['stato']."</option>";
			}*/
			?>
			<option value="0" <?php if ( $form['stato']=='0') echo 'selected'; ?>><?php echo $per_stato[0]; ?></option>
			<option value="1" <?php if ( $form['stato']=='1') echo 'selected'; ?>><?php echo $per_stato[1]; ?></option>
			<option value="2" <?php if ( $form['stato']=='2') echo 'selected'; ?>><?php echo $per_stato[2]; ?></option>
			<option value="3" <?php if ( $form['stato']=='3') echo 'selected'; ?>><?php echo $per_stato[3]; ?></option>
			<option value="4" <?php if ( $form['stato']=='4') echo 'selected'; ?>><?php echo $per_stato[4]; ?></option>
		</select> 
		<!--<input type="text" name="stato" id="stato" value="<?php echo $form['stato']; ?>" align="right" maxlength="255"/>-->
		<!--<button id="toggleSta" type="button">Altro</button>-->
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['sqn']; ?></td>
	<td class="FacetDataTD">
		<input name="Return" type="submit" value="<?php echo $script_transl['return']; ?>">
	</td>
	<td class="FacetDataTD" align="right">
		<input name="Submit" type="submit" value="<?php echo ucfirst($script_transl[$toDo]); ?>">
	</td>
</tr>
</table>

</form>
<?php
require('../../library/include/footer.php');
?>
<script src="../../js/custom/autocomplete.js"></script>
<script type="text/javascript">
/*function updateInputStato(ish){
	document.getElementById("stato").value = ish;
}*/
function updateInputTecnico(ish){
	document.getElementById("tecnico").value = ish;
}
function calculateTime() {
	var minend = parseInt($("select[name='ora_fine']").val().split(':')[1],10);
	var minstart = parseInt($("select[name='ora_inizio']").val().split(':')[1],10);
	var hstart = parseInt($("select[name='ora_inizio']").val().split(':')[0],10);
	var hend = parseInt($("select[name='ora_fine']").val().split(':')[0],10);

	var min = minend - minstart;
	if ( min<=-1 ) {
		min = "30";
		hend -= 1;
	}

	if ( hstart <= hend ) {
		var hour = hend - hstart;
	} else {
		var hour = (hend+24)-hstart;
	}
	if ( min == "30" ) min = "50";
	document.getElementById('ore').value = hour+"."+min;
}
</script>
<script>
$( document.getElementById("toggleTec") ).click(function() {
	$( "#tecnico" ).fadeIn('fast');//toggle( "fold" );
});
/*$( document.getElementById("toggleSta") ).click(function() {
	$( "#stato" ).fadeIn('fast');
});*/
$(function() {
 $("#tecnico").fadeOut('fast');//toggle('fold');
 //$("#stato").toggle('fold');
})
</script>