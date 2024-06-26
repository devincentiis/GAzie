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
$message = "Sei sicuro di voler duplicare?";
if (!isset($_POST['ritorno'])) {
	$_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}
if (isset($_POST['Duplicate'])) {
	//procedo alla duplicazione della testata e dei righi...
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$failure = false;
	try {
		$oldIdTes = $_POST['id_tes'];
		$numdoc = trovaNuovoNumero($gTables);  // numero nuovo documento
		// avvio una transazione sul DB
		mysqli_begin_transaction($link);

		/*
		  - per la copia del record di testata
		  query param. 1: numero documento da attribuire al nuovo record
		  query param. 2: id del record originale in tesbro
		*/
		$stmtInsTes = mysqli_prepare($link,
			"INSERT INTO {$gTables['tesbro']} (
				`id_tes`, `seziva`, `tipdoc`, `template`, `print_total`, `delivery_time`,
				`day_of_validity`, `datemi`, `protoc`, `numdoc`, `numfat`, `datfat`,
				`clfoco`, `pagame`, `banapp`, `vettor`, `listin`, `destin`,
				`id_des`, `id_des_same_company`, `spediz`, `portos`, `imball`, `traspo`,
				`speban`, `spevar`, `round_stamp`, `cauven`, `caucon`, `caumag`,
				`id_agente`, `sconto`, `expense_vat`, `stamp`, `taxstamp`, `virtual_taxstamp`,
				`net_weight`, `gross_weight`, `units`, `volume`, `initra`, `geneff`,
				`id_contract`, `id_con`, `status`, `adminid`, `last_modified`
			) (
			SELECT null, `seziva`, `tipdoc`, `template`, `print_total`, `delivery_time`,
				`day_of_validity`, DATE(CURRENT_TIMESTAMP), `protoc`, ?, '', '',
				`clfoco`, `pagame`, `banapp`, `vettor`, `listin`, `destin`,
				`id_des`, `id_des_same_company`, `spediz`, `portos`, `imball`, `traspo`,
				`speban`, `spevar`, `round_stamp`, `cauven`, `caucon`, `caumag`,
				`id_agente`, `sconto`, `expense_vat`, `stamp`, `taxstamp`, `virtual_taxstamp`,
				`net_weight`, `gross_weight`, `units`, `volume`, DATE(CURRENT_TIMESTAMP), `geneff`,
				`id_contract`, `id_con`, `status`, `adminid`, CURRENT_TIMESTAMP
			FROM {$gTables['tesbro']}
			WHERE id_tes = ?
			)"
		);
		mysqli_stmt_bind_param($stmtInsTes, 'ii', $numdoc, $oldIdTes);
		mysqli_stmt_execute($stmtInsTes);
		mysqli_stmt_close($stmtInsTes);
		$newIdTes = mysqli_insert_id($link);
		// eccesso di scrupolo...
		if ($newIdTes <= $oldIdTes) { throw new Exception('Non ho ottenuto una chiave valida per il nuovo documento!'); }

		/*
		  - per la copia del record di riga documento collegati
		  query param. 1: id/chiave del nuovo record di testata
		  query param. 2: id del record originale in tesbro
		*/
		$stmtInsRows = mysqli_prepare($link,
			"INSERT INTO {$gTables['rigbro']} (
				`id_rig`, `nrow`, `nrow_linked`, `id_tes`, `tiprig`, `codart`, `codice_fornitore`, `descri`, `id_body_text`,
				`unimis`, `quanti`, `prelis`, `sconto`, `codvat`, `pervat`,
				`codric`, `provvigione`, `ritenuta`, `delivery_date`, `id_doc`, `id_mag`,
				`status`
			) (
			SELECT null, `nrow`, `nrow_linked`, ?, `tiprig`, `codart`, `codice_fornitore`, `descri`, `id_body_text`,
				`unimis`, `quanti`, `prelis`, `sconto`, `codvat`, `pervat`,
				`codric`, `provvigione`, `ritenuta`, `delivery_date`, 0, 0,
				'INSERT'
			FROM {$gTables['rigbro']}
			WHERE id_tes = ? ORDER BY `id_rig`
			)"
		);

		mysqli_stmt_bind_param($stmtInsRows, 'ii', $newIdTes, $oldIdTes);
		mysqli_stmt_execute($stmtInsRows);
		mysqli_stmt_close($stmtInsRows);

		/*
		  individuo gli id delle righe in body_text che dovrò successivamente clonare
		  query param. 1: id del nuovo record di testata documento
		*/
		$stmtSlct = mysqli_prepare($link,
			"SELECT `id_rig`, `id_body_text`
			FROM {$gTables['rigbro']}
			WHERE `tiprig` = 6 AND `id_tes` = ?"
		);
		mysqli_stmt_bind_param($stmtSlct, 'i', $newIdTes);
		mysqli_stmt_execute($stmtSlct);
		$textRows = mysqli_stmt_get_result($stmtSlct);
		mysqli_stmt_close($stmtSlct);

		/*
		  preparo le query che saranno eseguite nel ciclo successivo

		  - per la copia dei record in body_text
		  query param. 1: id nuova riga documento tipo 6 precedentemente inserita in rig_bro
		  query param. 2: id del record originale in body_text
		*/
		$stmtInsRow = mysqli_prepare($link,
			"INSERT INTO {$gTables['body_text']} (`table_name_ref`, `id_ref`, `body_text`, `lang_id`) (
				SELECT `table_name_ref`, ?, `body_text`, `lang_id`
				FROM {$gTables['body_text']} WHERE `id_body` = ?
			)"
		);

		/*
		  - per la modifica dei riferimenti incrociati tra rigbro e body_text
		  query param. 1: id del nuovo record inserito in body_text
		  query param. 2: id nuova riga documento tipo 6 precedentemente inserita in rig_bro
		  query param. 3: id del nuovo record di testata documento
		*/
		$stmtUpdRow = mysqli_prepare($link,
			"UPDATE {$gTables['rigbro']}
			SET `id_body_text` = ?
			WHERE `id_rig` = ? AND `id_tes` = ?"
		);

		while ($tbl = mysqli_fetch_array($textRows, MYSQLI_ASSOC)) {
			// copio il record collegato in body_text modificandone i riferimenti
			mysqli_stmt_bind_param($stmtInsRow, 'ii', $tbl['id_rig'], $tbl['id_body_text']);
			mysqli_stmt_execute($stmtInsRow);
			$newIdText = mysqli_insert_id($link);
			// modifico il riferimento a body_text nella riga documento
			mysqli_stmt_bind_param($stmtUpdRow, 'iii', $newIdText, $tbl['id_rig'], $newIdTes);
			mysqli_stmt_execute($stmtUpdRow);
		}
		mysqli_stmt_close($stmtInsRow);
		mysqli_stmt_close($stmtUpdRow);
	}

	catch (Exception $ex) {
		$failure = true;
		mysqli_rollback($link);
		error_log($ex->__toString());
	}
	finally {
		if (!$failure) { mysqli_commit($link); }
		// disattivo il report degli errori msqli, che per default è off, onde evitare di influenzare altri script
		mysqli_report(MYSQLI_REPORT_OFF);
		header("Location: " . $_POST['ritorno']);
		exit;
	}
}

if (isset($_POST['Return'])) {
	header("Location: " . $_POST['ritorno']);
	exit;
}

//recupero i documenti non contabilizzati
$result = gaz_dbi_dyn_query("*", $gTables['tesbro'], "id_tes = " . intval($_GET['id_tes']), "id_tes desc");
$rs_righi = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = " . intval($_GET['id_tes']), "id_tes desc");
$numrig = gaz_dbi_num_rows($rs_righi);
$form = gaz_dbi_fetch_array($result);
$tipobro="";
switch ($form['tipdoc']) {
	case "VPR":
		$tipobro = "il preventivo";
		break;
	case "VOR":
	case "VOW":
		$tipobro = "l'ordine";
		break;
	case "VCO":
		$tipobro = "lo scontrino";
		break;
}
$titolo = "Duplica " . $tipobro . " n." . $form['numdoc'];
require("../../library/include/header.php");
$script_transl = HeadMain();
$anagrafica = new Anagrafica();
$cliente = $anagrafica->getPartner($form['clfoco']);

function trovaNuovoNumero($gTables) {
	// modifica di Antonio Espasiano come da post :
	// https://sourceforge.net/p/gazie/discussion/468173/thread/572dcb76/
	//
	$orderBy = "datemi desc, numdoc desc";
	parse_str(parse_url($_POST['ritorno'],PHP_URL_QUERY),$output);
	$condition = "{$gTables['tesbro']}.tipdoc='{$output['auxil']}'";
	if ($output['auxil'] == 'VO_') {
		$condition = "{$gTables['tesbro']}.tipdoc='VOR' OR {$gTables['tesbro']}.tipdoc='VOW'";
	}
	// ToDo: è da dimostrare che questa query faccia quello che ci si aspetta... in particolare fini del successivo IF
	$rs_ultimo_documento = gaz_dbi_dyn_query("numdoc", $gTables['tesbro'], $condition, $orderBy, 0, 1);
	$ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
	// se e' il primo documento dell'anno, resetto il contatore
	if ($ultimo_documento) {
		/*$orderBy = "datemi desc, numdoc desc";
		$rs_ultimo_documento = gaz_dbi_dyn_query("numdoc", $gTables['tesbro'], 1, $orderBy, 0, 1);
		$ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
		se e' il primo documento dell'anno, resetto il contatore
		if ($ultimo_documento) {
		*/
		$numdoc = $ultimo_documento['numdoc'] + 1;
	} else {
		$numdoc = 1;
	}
	return $numdoc;
}
?>
<form method="POST">
	<input type="hidden" name="id_tes" value="<?php print $form['id_tes']; ?>">
	<input type="hidden" name="ritorno" value="<?php print $_POST['ritorno']; ?>">
	<div align="center"><font class="FacetFormHeaderFont">Attenzione!!! Stai duplicando <?php echo $tipobro . " n." . $form['numdoc']; ?> </font></div>
	<table border="0" cellpadding="3" cellspacing="1" class="FacetFormTABLE" align="center">
		<!-- BEGIN Error -->
		<tr>
			<td colspan="2" class="FacetDataTD" style="color: red;">
				<?php
				if (!$message == "") {
					print "$message";
				}
				?>
			</td>
		</tr>
		<!-- END Error -->
		<tr>
			<td class="FacetFieldCaptionTD">Numero di ID &nbsp;</td><td class="FacetDataTD"><?php print $form["id_tes"] ?>&nbsp;</td>
		</tr>
		<tr>
			<td class="FacetFieldCaptionTD">Tipo documento &nbsp;</td><td class="FacetDataTD"><?php print $form["tipdoc"] ?>&nbsp;</td>
		</tr>
		<tr>
			<td class="FacetFieldCaptionTD">Numero Documento &nbsp;</td><td class="FacetDataTD"><?php print $form["numdoc"] ?>&nbsp;</td>
		</tr>
		<tr>
			<td class="FacetFieldCaptionTD">Cliente &nbsp;</td><td class="FacetDataTD"><?php print $cliente["ragso1"] ?>&nbsp;</td>
		</tr>
		<tr>
			<td class="FacetFieldCaptionTD">Num. di righi &nbsp;</td><td class="FacetDataTD"><?php print $numrig ?>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" align="right">Se sei sicuro conferma la duplicazione &nbsp;
				<input type="submit" name="Duplicate" value="DUPLICA !">&nbsp;
			</td>
		</tr>
	</table>
</form>
<?php
require("../../library/include/footer.php");
?>
