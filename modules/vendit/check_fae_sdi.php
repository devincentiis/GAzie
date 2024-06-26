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
$send_fae_zip_package = gaz_dbi_get_row($gTables['company_config'], 'var', 'send_fae_zip_package')['val'];

if ( file_exists('../'.$send_fae_zip_package.'/sync.function.php') ) { // modalità gazSynchro
  $classnamesdiflux = $send_fae_zip_package.'gazSynchro';
  require_once('../'.$send_fae_zip_package.'/sync.function.php');
  $sdifluxSync = new $classnamesdiflux();
  $year = date("Y");
  $sdifluxSync->get_sync_status($year.'-01-01');
  // non stampo nulla in quanto le notifiche avvengono sulla barra del menu
  //print_r($sdifluxSync->rawres);
  // torno sul report
  header("Location: " . $_SERVER['HTTP_REFERER']);

} elseif(file_exists('../../library/'.$send_fae_zip_package.'/SendFaE.php'))  { // modalità catsrl
	$where1 = " id_SDI!=0 AND (flux_status='@' OR flux_status='@@' OR flux_status='IN' OR (filename_ori LIKE '%.xml.p7m' AND (flux_status='MC' OR flux_status='RC'))) ";
	$risultati = gaz_dbi_dyn_query("*", $gTables['fae_flux'], $where1);
	if (!$risultati) {
		die('<p align="center"><a href="./report_fae_sdi.php">Ritorna a report Fatture elettroniche</a></p>');
	}
	$IdentificativiSdI = array();
	while ($r = gaz_dbi_fetch_array($risultati)) {
		$IdentificativiSdI[] = $r['id_SDI'];
	}
	require('../../library/' . $send_fae_zip_package. '/SendFaE.php');
	$notifiche = ReceiveNotifiche(array($admin_aziend['country'].$admin_aziend['codfis'] => $IdentificativiSdI));
	if (!empty($notifiche)) {
		if (is_array($notifiche)) {
			$nuove_notifiche = false;
			foreach ($notifiche as $id_SDI=>$notifica) {
				gaz_dbi_put_query($gTables['fae_flux'], "id_SDI='" . $id_SDI . "'", "flux_status", $notifica['esito']);
				if (!empty($notifica['motivo'])) {
					if (is_array($notifica['motivo'])) {
						$descri_notifiche = '';
						foreach ($notifica['motivo'] as $descri_notifica) {
							if (!empty($descri_notifiche)) $descri_notifiche.= '<br />';
							$descri_notifiche.= $descri_notifica;
						}
					} else {
						$descri_notifiche = $notifica['motivo'];
					}
					gaz_dbi_put_query($gTables['fae_flux'], "id_SDI='" . $id_SDI . "'", "flux_descri", addslashes($descri_notifiche));
				}
				$nuove_notifiche = true;
			}
			if ($nuove_notifiche) {
				echo 'Completato';
			} else {
				echo 'Completato senza nuove notifiche';
			}
		} else {
			echo '<p>' . print_r($notifiche, true) . '</p>';
		}
	}
	echo '<p align="center"><a href="./report_fae_sdi.php">Ritorna a report Fatture elettroniche</a></p>';
	exit();

}
?>
