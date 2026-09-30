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
	foreach($_POST['search'] as $k=>$v){
		$form['search'][$k]=$v;
	}
	//$form['cosear'] = $_POST['cosear'];

	$form['codice'] = trim($form['codice']);

	$form['descrizione'] = $_POST['descrizione'];
	$form['seriale'] = $_POST['seriale'];
	$form['datainst'] = $_POST['annins'] . "-" . $_POST['mesins'] . "-" . $_POST['gioins'];
	$form['clfoco'] = $_POST['clfoco'];
	$form['note'] = $_POST['note'];
	$form['ritorno'] = $_POST['ritorno'];
	$form['ref_code'] = $_POST['ref_code'];

	$form['utente'] = $_SESSION['user_name'];

	//$form['rows'] = array();	
	if (isset($_POST['Submit'])) {
		// conferma tutto
		if ($toDo == 'update') {
			// controlli in caso di modifica
			if ($form['codice'] != $form['ref_code']) { 
				// se sto modificando il codice originario
				// controllo che l'articolo ci sia gia'
				$rs_assist = gaz_dbi_dyn_query('codice', $gTables['instal'], "codice = ".$form['codice'],"codice DESC",0,1);
				$rs = gaz_dbi_fetch_array($rs_assist);
				if ($rs) { 
					$msg .= '0+';
				}
			}
		} else {
			// controllo che l'articolo ci sia gia'
			$rs_articolo = gaz_dbi_dyn_query('codice', $gTables['instal'], "codice = ".$form['codice'],"codice DESC",0,1);
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
				gaz_dbi_table_insert('instal', $form);
			} elseif ($toDo == 'update') {
				if ( $form['clfoco']==0 ) $form['clfoco'] = $admin_aziend['mascli'] . '000001';
				gaz_dbi_table_update('instal', $form['ref_code'], $form);
			}
			header('Location: '.$form['ritorno']);
			exit;
		}
	} elseif (isset($_POST['Return'])) { // torno indietro
		header('Location: report_install.php');
		exit;
	}

} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) {

	if (!empty($_GET['idinstallazione'])) {
		$assist = gaz_dbi_get_row($gTables['instal'], "id", $_GET['idinstallazione']);
		//se e' il primo accesso per UPDATE
		$anagrafica = new Anagrafica();
		$cliente = $anagrafica->getPartner($assist['clfoco']);
		$form = gaz_dbi_get_row($gTables['instal'], "id", $_GET['idinstallazione']);
		$form['search']['clfoco'] = substr($cliente['ragso1'], 0, 10);
	} else {
		$assist = gaz_dbi_get_row($gTables['instal'], "codice", $_GET['codice']);
		//se e' il primo accesso per UPDATE
		$anagrafica = new Anagrafica();
		$cliente = $anagrafica->getPartner($assist['clfoco']);
		$form = gaz_dbi_get_row($gTables['instal'], "codice", $_GET['codice']);
		$form['search']['clfoco'] = substr($cliente['ragso1'], 0, 10);
	}

	//$form['codart'] = $assist['codart'];
	//$form['cosear'] = $assist['codart'];
	$form['ritorno'] = '../../modules/suppor/report_install.php';
	$form['ref_code'] = $form['codice'];

} else {
	//se e' il primo accesso per INSERT
	$form = gaz_dbi_fields("assist");
	$rs_ultima_ass = gaz_dbi_dyn_query("codice", $gTables['instal'], $where, "codice desc");
	$ultimo_documento = gaz_dbi_fetch_array($rs_ultima_ass);
	// se e' il primo documento dell'anno, resetto il contatore
	if ($ultimo_documento) {
		$form['codice'] = $ultimo_documento['codice'] + 1;
	} else {
		$form['codice'] = 1;
	}
	//$form['tipo'] = 'ASS';
	$form['utente'] = $_SESSION['user_name'];
	$form['datainst'] = date('Y-m-d');
	$form['seriale'] = '';
	//$form['cosear'] = '';
	//$form['codart'] = '';

	/*$rs_ultimo_tec = gaz_dbi_dyn_query("codice,tecnico", $gTables['assist'],"tecnico<>''","codice desc");
	$ultimo_tecnico = gaz_dbi_fetch_array($rs_ultimo_tec);
	$form['tecnico'] = $ultimo_tecnico['tecnico'];*/

	$form['search']['clfoco'] = '';
	$form['ritorno'] = $_SERVER['HTTP_REFERER'];
	$form['ref_code'] = '';
}

$form['gioins'] = substr($form['datainst'], 8, 2);
$form['mesins'] = substr($form['datainst'], 5, 2);
$form['annins'] = substr($form['datainst'], 0, 4);

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
<input type="hidden" name="codice" value="<?php echo $form['codice']; ?>">
<input type="hidden" name="<?php echo ucfirst($toDo); ?>" value="">
<div class="table-responsive">
<table class="Tlarge table table-striped table-bordered table-condensed">
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['codice']; ?> </td>
	<td colspan="2" class="FacetDataTD">
		<button ><?php echo $form['codice']; ?></button>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD">Data Installazione</td>
	<td colspan="2" class="FacetDataTD">
		<input class="FacetText" type="text" style="text-align:center" name="gioins" value="<?php echo $form['gioins'] ?>">
		<input class="FacetText" type="text" style="text-align:center" name="mesins" value="<?php echo $form['mesins'] ?>">
		<input class="FacetText" type="text" style="text-align:center" name="annins" value="<?php echo $form['annins'] ?>">
		<a href="#" onClick="cal.showCalendar('anchor','<?php echo $form['mesins'] . "/" . $form['gioins'] . "/" . $form['annins'] ?>'); return false;" title=" cambia la data! " name="anchor" id="anchor" class="btn btn-default btn-sm">
			<i class="glyphicon glyphicon-calendar"></i>
		</a>
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
	<td class="FacetFieldCaptionTD">Oggetto</td>
	<td colspan="2" class="FacetDataTD">
		<input type="text" name="oggetto" value="<?php echo $form['oggetto']; ?>" align="right" maxlength="255"/>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['descrizione']; ?> </td>
	<td colspan="2" class="FacetDataTD">
		<input type="text" name="descrizione" value="<?php echo $form['descrizione']; ?>" maxlength="255" />
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD">Note</td>
	<td colspan="2" class="FacetDataTD">
		<textarea type="text" name="note" align="right" cols="67" rows="3" class="mceClass"><?php echo $form['note']; ?></textarea>
	</td>
</tr>
<tr>
	<td class="FacetFieldCaptionTD">Seriale</td>
	<td colspan="2" class="FacetDataTD">
		<input type="text" name="seriale" value="<?php echo $form['seriale']; ?>" align="right" maxlength="255"/>
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
</div>

</form>
<?php
if ( !isset($_GET['Insert']) ) {

	$num = gaz_dbi_record_count( $gTables['assist'], "idinstallazione=0 and clfoco=".$form['clfoco'] );
	if ( $num > 0 ) {
		echo "<br><center>Ci sono assistenze non assegnate per ".$cliente['ragso1']."</center><br>";
		$result = gaz_dbi_dyn_query($gTables['assist'].".*", $gTables['assist'], "idinstallazione=0 and clfoco=".$form['clfoco'], $orderby, $limit, $passo);
		echo "<table class='Tlarge table table-striped table-bordered table-condensed table-responsive'>";
		while ( $row = gaz_dbi_fetch_array($result) ) {
			if ( $row['tipo'] == 'ASS' ) {
				$tipo = 'Intervento di assistenza';
				$color = '#5bc0de';
			} else {
				$tipo = 'Assistenza periodica';
				$color = '#428bca';
			}

			echo "<tr><td bgcolor='$color'>".$row['codice']."</td>";
			echo "<td bgcolor='$color'>".$tipo."</td>";
			echo "<td bgcolor='$color'>".$row['data']."</td>";
			echo "<td bgcolor='$color'>".$row['oggetto']."</td>";
			echo "<td bgcolor='$color'><a class='btn btn-xs btn-danger' href='associa_install.php?id=".$row['id']."&ass=".$form['id']."'><i class='glyphicon glyphicon-retweet'></i> Associa</a></td></tr>";
		}
		echo "</table>";
	}

	$_GET['auxil'] = '';
	$_GET['clfoco'] = $form['clfoco'];
	$_GET['tecnico'] = 'All';
	$_GET['stato'] = 'All';
	$_GET['flt_passo'] = '50';
	$_GET['idinstallazione'] = $form['id'];
	$_GET['all'] = 'all';

	include('report_assist.php');

	echo "<center>------------------------------------------------------------------------------------------</center>";

	include('report_period.php');

	echo "</div>";
} 
?>
<!--</div>-->
<script type="text/javascript" language="JavaScript" ID="datapopup">
    var cal = new CalendarPopup();
    cal.setReturnFunction("setMultipleValues");
    function setMultipleValues(y, m, d) {
        document.form.annins.value = y;
        document.form.mesins.value = LZ(m);
        document.form.gioins.value = LZ(d);
    }
</script>
<?php
require('../../library/include/footer.php');
?>
<script src="../../js/custom/autocomplete.js"></script>
<script type="text/javascript">
tinyMCE.init({
	menubar: false,
	statusbar: false
});
</script>