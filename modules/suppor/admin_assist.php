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
	$form['codice'] = trim($form['codice']);
	$form['tipo'] = 'ASS';
	$form['id'] = $_POST['id'];
	$form['descrizione'] = $_POST['descrizione'];
	$form['soluzione'] = $_POST['soluzione'];
	$form['clfoco'] = $_POST['clfoco'];
	$form['ritorno'] = $_POST['ritorno'];
	$form['ref_code'] = $_POST['ref_code'];
	$form['data'] = $_POST['annass'] . "-" . $_POST['mesass'] . "-" . $_POST['gioass'];
	$form['ore'] = $_POST['ore'];
	$form['ora_inizio'] = $_POST['ora_inizio'];
	$form['ora_fine'] = $_POST['ora_fine'];
	$form['note'] = $_POST['note'];
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
		$msg .= (empty($form['oggetto']) ? '6+' : '');
		if (empty($msg)) { 
			if (preg_match("/^id_([0-9]+)$/",$form['clfoco'],$match)) {
				$new_clfoco = $anagrafica->getPartnerData($match[1],1);
				$form['clfoco']=$anagrafica->anagra_to_clfoco($new_clfoco,$admin_aziend['mascli']);
			}
			// aggiorno il db
			if ($toDo == 'insert') {
				if ( $form['clfoco']==0 ) $form['clfoco'] = $admin_aziend['mascli'] . '000001';
				gaz_dbi_table_insert('assist',$form);
			} elseif ($toDo == 'update') {
				if ( $form['clfoco']==0 ) $form['clfoco'] = $admin_aziend['mascli'] . '000001';
				gaz_dbi_table_update('assist',$form['ref_code'],$form);
			}
			if (empty($_GET['popup'])) {
			//header('Location: '.$form['ritorno']);
			header('Location: associa_install.php?id='.$form['codice'].'&clfoco='.$form['clfoco'].'&ritorno='.$form['ritorno']);
			} else {
				echo "<script>window.opener.location.reload(false);window.close();</script>";
			}
			exit();
		}
	} elseif (isset($_POST['Return'])) { // torno indietro
		header('Location: '.$form['ritorno']);
		exit;
	}
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { 
	//se e' il primo accesso per UPDATE
	$assist = gaz_dbi_get_row($gTables['assist'],"codice",$_GET['codice']);
	$anagrafica = new Anagrafica();
	$cliente = $anagrafica->getPartner($assist['clfoco']);
	$form = gaz_dbi_get_row($gTables['assist'], 'codice', $_GET['codice']);
	$form['search']['clfoco']=substr($cliente['ragso1'],0,10);
	$form['ritorno'] = 'report_assist.php';
	$form['ref_code'] = $form['codice'];
} else { 
	//se e' il primo accesso per INSERT
	$form = gaz_dbi_fields('assist');
	$rs_ultima_ass = gaz_dbi_dyn_query("codice", $gTables['assist'],$where,"codice DESC");
	$ultimo_documento = gaz_dbi_fetch_array($rs_ultima_ass);
	// se e' il primo documento dell'anno, resetto il contatore
	if ($ultimo_documento) {
		$form['codice'] = $ultimo_documento['codice'] + 1;
	} else {
		$form['codice'] = 1;
	}
	$rs_ultimo_tec = gaz_dbi_dyn_query("codice, tecnico", $gTables['assist'], "tecnico<>''", "codice DESC");
	
	// non viene assegnato il tecnico se l'intervento è aperto
	// $ultimo_tecnico = gaz_dbi_fetch_array($rs_ultimo_tec);
	$form['tecnico'] = ""; //$ultimo_tecnico['tecnico']; 
	$form['tipo'] = 'ASS';	
	$form['utente'] = $_SESSION['user_name'];
	$form['data'] = date('Y-m-d');
	$form['ore'] = '0.00';
	$form['stato'] = 'aperto';
	$form['search']['clfoco'] = '';
	$form['ritorno'] = 'report_assist.php';
	$form['ref_code'] = '';
}

$form['gioass'] = substr($form['data'], 8, 2);
$form['mesass'] = substr($form['data'], 5, 2);
$form['annass'] = substr($form['data'], 0, 4);


// disegno maschera di inserimento modifica
require('../../library/include/header.php');
$script_transl = HeadMain(0,array('calendarpopup/CalendarPopup'));

if ($toDo == 'insert') echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['ins_this']."</div>";
else echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['upd_this']." '".$form['codice']."'</div>";
if (!empty($msg)) echo $script_transl['errors'][substr($msg, 0, 1)];
$select_cliente = new selectPartner('clfoco');
?>
<form method="POST" name="form" enctype="multipart/form-data">
<input type="hidden" name="ritorno" value="<?php echo $form['ritorno']; ?>">
<input type="hidden" name="ref_code" value="<?php echo $form['ref_code']; ?>">
<input type="hidden" name="id" value="<?php echo $form['id']; ?>">
<input type="hidden" name="codice" value="<?php echo $form['codice']; ?>">
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
		<input class="FacetText" type="text" style="text-align:center" name="gioass" value="<?php echo $form['gioass'] ?>">
		<input class="FacetText" type="text" style="text-align:center" name="mesass" value="<?php echo $form['mesass'] ?>">
		<input class="FacetText" type="text" style="text-align:center" name="annass" value="<?php echo $form['annass'] ?>">
		<a href="#" onClick="cal.showCalendar('anchor','<?php echo $form['mesass'] . "/" . $form['gioass'] . "/" . $form['annass'] ?>'); return false;" title=" cambia la data! " name="anchor" id="anchor" class="btn btn-default btn-sm">
			<i class="glyphicon glyphicon-calendar"></i>
		</a>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['cliente']; ?> </td>
	<td colspan="2" class="FacetDataTD">
	<?php 
		$select_cliente->selectDocPartner('clfoco',$form['clfoco'],$form['search']['clfoco'],'clfoco',$script_transl['mesg'],$admin_aziend['mascli']);
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
					echo "<option value=\"".$tecnici['tecnico']."\" ".$selected.">".$tecnici['tecnico']."</option>";
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
		<textarea type="text" name="descrizione" align="right" maxlength="255" cols="67" rows="<?php echo (!empty($_GET['popup'])) ? 2 : 3; ?>"><?php echo $form['descrizione']; ?></textarea>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['soluzione']; ?> </td>
	<td colspan="2" class="FacetDataTD">
		<textarea type="text" name="soluzione" align="right" maxlength="255" cols="67" rows="<?php echo (!empty($_GET['popup'])) ? 2 : 4; ?>"><?php echo $form['soluzione']; ?></textarea>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['info_agg']; ?> </td>
	<td colspan="2" class="FacetDataTD">
		<input type="text" name="info_agg" value="<?php echo $form['info_agg']; ?>" align="right" maxlength="255"/>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD">Ore</td>
	<td colspan="2" class="FacetDataTD">
		ora inizio : <select name="ora_inizio" onchange="calculateTime()">
		<?php
			//$form['ora_inizio']
			$start = '08:00';
			$end = '19:30';
			$tStart = strtotime($start);
			$tEnd = strtotime($end);
			$tNow = $tStart;
			while($tNow <= $tEnd){
				if ( date('H:i', $tNow)==$form['ora_inizio'] ) $selected = 'selected';
				else $selected='';
				echo "<option value=\"".date('H:i',$tNow)."\" ".$selected.">".date('H:i',$tNow)."</option>";
				$tNow = strtotime('+30 minutes',$tNow);
			}
		?>
		</select>&nbsp;
		ora fine : <select name="ora_fine" onchange="calculateTime()">
		<?php
			$tNow = $tStart;
			while($tNow <= $tEnd){
				if ( date('H:i', $tNow)==$form['ora_fine'] ) $selected = 'selected';
				else $selected='';
				echo "<option value=\"".date('H:i',$tNow)."\" ".$selected.">".date('H:i',$tNow)."</option>";
				$tNow = strtotime('+30 minutes',$tNow);
			}
		?>
		</select>&nbsp;
		Totale : <input size="16" type="text" id="ore" name="ore" value="<?php echo $form['ore']; ?>" align="right" maxlength="255" />
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['note']; ?> </td>
	<td colspan="2" class="FacetDataTD">
		<textarea type="text" name="note" align="right" maxlength="255" cols="67" rows="<?php echo (!empty($_GET['popup'])) ? 2 : 4; ?>"><?php echo $form['note']; ?></textarea>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['stato']; ?> </td>
	<td colspan="2" class="FacetDataTD">
		<select name="cstato" onchange="updateInputStato(this.value)">
			<?php
			$stati = array ('avvisare', 'bloccato', 'aperto', 'effettuato', 'fatturato');
			foreach ($stati as $i=>$stato) {
			?>
			<option value="<?php echo $stato; ?>" <?php if ( $form['stato'] == $stato ) echo 'selected'; ?>><?php echo $stato; ?></option>
			<?php
			}

			$altri_stati = gaz_dbi_dyn_query(" DISTINCT ".$gTables['assist'].".stato,".$gTables['assist'].".tipo", $gTables['assist']," stato NOT IN ('" . implode("', '", $stati) . "') AND tipo='ASS'", "stato", "0", "9999");
			while ($altro_stato = gaz_dbi_fetch_array($altri_stati)) {				
			?>
			<option value="<?php echo $altro_stato['stato']; ?>" <?php if ( $form['stato'] == $altro_stato['stato'] ) echo 'selected'; ?>><?php echo $altro_stato['stato']; ?></option>
			<?php
			}
			?>
		</select> 
		<input type="text" name="stato" id="stato" value="<?php echo $form['stato']; ?>" align="right" maxlength="255"/>
		<button id="toggleSta" type="button">Altro</button>
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
<?php
//$result = gaz_dbi_dyn_query(" DISTINCT ".$gTables['assist'].".stato,".$gTables['assist'].".tipo", $gTables['assist']," stato!='effettuato' and stato!='aperto' and stato != 'fatturato' and tipo='ASS'", "stato", "0", "9999");
?>
</form>
<?php
require('../../library/include/footer.php');
?>
<script src="../../js/custom/autocomplete.js"></script>
<script type="text/javascript" language="JavaScript" ID="datapopup">
    var cal = new CalendarPopup();
    cal.setReturnFunction("setMultipleValues");
    function setMultipleValues(y, m, d) {
        document.form.annass.value = y;
        document.form.mesass.value = LZ(m);
        document.form.gioass.value = LZ(d);
    }
</script>
<script type="text/javascript">
function updateInputStato(ish){
	document.getElementById("stato").value = ish;
}
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
$( document.getElementById("toggleSta") ).click(function() {
	$( "#stato" ).fadeIn('fast');
});
$(function() {
 $("#tecnico").fadeOut('fast');//toggle('fold');
 $("#stato").toggle('fold');
})
</script>