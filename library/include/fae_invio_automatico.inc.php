<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
  (https://www.devincentiis.it)
  <https://gazie.sourceforge.net>
  Questo file: Copyright (C) 2026 Domenico Seccia - Assistenzacassa (https://assistenzacassa.it)
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

  Invio automatico della fattura elettronica alla conferma di una fattura nuova (admin_docven.php).
  Si attiva in Configurazione azienda > Configurazione Avanzata con send_fae_automatico = 1 e usa la libreria
  indicata in send_fae_zip_package (library/<nome>/SendFaE.php, es. youdox o catsrl), come l'invio manuale
  dall'elenco dei flussi (report_fae_sdi.php): stesso file nella cartella dei documenti, stessa riga in fae_flux,
  stesso stato "@" e riferimento in id_SDI se l'invio riesce; se non riesce il motivo resta in flux_descri e la
  fattura si puo' rimandare a mano. Una fattura gia' trasmessa non viene rimandata.
*/

function fae_invio_automatico($gTables, $admin_aziend, $id_tes)
{
	$auto = gaz_dbi_get_row($gTables['company_config'], 'var', 'send_fae_automatico');
	$lib = gaz_dbi_get_row($gTables['company_config'], 'var', 'send_fae_zip_package');
	if (!$auto || trim($auto['val']) != '1' || !$lib || trim($lib['val']) == '') {
		return;
	}
	$nome_lib = basename(trim($lib['val']));
	$file_lib = dirname(__DIR__) . '/' . $nome_lib . '/SendFaE.php';
	if (!file_exists($file_lib)) {
		return;
	}
	require_once(__DIR__ . '/electronic_invoice.inc.php');
	require_once($file_lib);
	$t = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', intval($id_tes));
	if (!$t || substr($t['tipdoc'], 0, 1) != 'F') {
		return;
	}
	// tutte le testate della stessa fattura (come in electronic_invoice.php)
	$where = "tipdoc = '" . $t['tipdoc'] . "' AND seziva = " . intval($t['seziva']) . " AND YEAR(datfat) = " . substr($t['datfat'], 0, 4) . " AND protoc = " . intval($t['protoc']);
	$testate = gaz_dbi_dyn_query("*", $gTables['tesdoc'], $where, 'datemi ASC, numdoc ASC, id_tes ASC');
	$xml = create_XML_invoice($testate, $gTables, 'rigdoc', false, false, true, false);
	if (!$xml || empty($xml['documento']) || empty($xml['nome_file'])) {
		return;
	}
	$nome = $xml['nome_file'];
	$flux = gaz_dbi_get_row($gTables['fae_flux'], 'filename_ori', $nome);
	if ($flux && !in_array($flux['flux_status'], array('DI', '##', ''))) {
		return; // gia' trasmessa o in lavorazione: niente doppioni
	}
	if (!$flux) {
		if (!function_exists('fae_fluxInsert')) { // sta in modules/vendit/lib.data.php, caricato da datlib nelle pagine delle vendite
			require_once(dirname(dirname(__DIR__)) . '/modules/vendit/lib.data.php');
		}
		fae_fluxInsert(array('filename_ori' => $nome, 'filename_zip_package' => '', 'id_tes_ref' => intval($id_tes),
			'exec_date' => date('Y-m-d H:i:s'), 'received_date' => '', 'delivery_date' => '', 'filename_son' => '', 'id_SDI' => 0,
			'filename_ret' => '', 'mail_id' => 0, 'data' => '', 'flux_status' => 'DI', 'n_invio' => intval($t['fattura_elettronica_reinvii']) + 1,
			'progr_ret' => '000', 'flux_descri' => ''));
	}
	$percorso = DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . $nome;
	file_put_contents($percorso, $xml['documento']);
	$esito = SendFatturaElettronica($percorso);
	$dove = "filename_ori = '" . addslashes($nome) . "'";
	if (is_array($esito) && !empty($esito)) {
		gaz_dbi_put_query($gTables['fae_flux'], $dove, 'flux_status', '@');
		gaz_dbi_put_query($gTables['fae_flux'], $dove, 'id_SDI', $esito[0]);
		gaz_dbi_put_query($gTables['fae_flux'], $dove, 'flux_descri', 'Inviata automaticamente alla conferma (' . $nome_lib . ')');
	} else {
		$motivo = is_string($esito) ? strip_tags($esito) : 'risposta non valida';
		gaz_dbi_put_query($gTables['fae_flux'], $dove, 'flux_descri', addslashes(mb_substr('Invio automatico non riuscito: ' . $motivo, 0, 250)));
	}
}
