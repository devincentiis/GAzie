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
// prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}
if ((isset($_POST['type'])&&isset($_POST['ref'])) OR (isset($_POST['type'])&&isset($_POST['id_tes']))) {
	require("../../library/include/datlib.inc.php");
	$calc = new Schedule;
	$admin_aziend = checkAdmin();
	switch ($_POST['type']) {
        case "movcon":
			$i=intval($_POST['id_tes']);
			//cancello i righi contabili
			$result = gaz_dbi_dyn_query("*", $gTables['rigmoc'],"id_tes = ".$i,"id_tes asc");
			while ($a_row = gaz_dbi_fetch_array($result)) {
				gaz_dbi_del_row($gTables['rigmoc'], "id_rig", $a_row['id_rig']);
				// elimino le eventuali partite aperte
				$calc->updatePaymov($a_row['id_rig']);
			}
			//cancello i righi iva
			// se il rigo ha un reverse charge cancello anche il documento fittizio "X" prodotto in fase di contabilizzazione
			$rs_rev_ch=gaz_dbi_dyn_query("*",$gTables['rigmoi'],"reverse_charge_idtes = ". $i." OR id_tes = ".$i,'reverse_charge_idtes DESC');
      while ($rev_ch = gaz_dbi_fetch_array($rs_rev_ch)) {
				gaz_dbi_del_row($gTables['rigmoi'], 'id_tes', $rev_ch['reverse_charge_idtes']);
				$rs_rev_rm=gaz_dbi_dyn_query("*",$gTables['rigmoc'],"id_tes = ".$rev_ch['reverse_charge_idtes'],'id_rig'); // riprendo i righi per poter eliminare anche lo scadenzario
				while ($rev_rm = gaz_dbi_fetch_array($rs_rev_rm)) {
					// elimino le eventuali partite aperte
					$calc->updatePaymov($rev_rm['id_rig']);
				}
				gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', $rev_ch['reverse_charge_idtes']);
				gaz_dbi_del_row($gTables['tesmov'], 'id_tes', $rev_ch['reverse_charge_idtes']);
				// il documento lo elimino solo se è di tipo X
				$rs_idtes_ch=gaz_dbi_dyn_query("*",$gTables['tesdoc'],"tipdoc LIKE 'X%' AND id_con = ". $rev_ch['reverse_charge_idtes'],'id_tes DESC',0,1);
				$idtes_ch = gaz_dbi_fetch_array($rs_idtes_ch);
				if ($idtes_ch){
					gaz_dbi_del_row($gTables['tesdoc'], 'id_tes', $idtes_ch['id_tes']);
					gaz_dbi_del_row($gTables['rigdoc'], 'id_tes', $idtes_ch['id_tes']);
          // non esistendo più la fattura reverse perché eliminata sopra elimino anche l'eventuale flusso con il SdI e la tolgo anche da dentro il pacchetto zip
          $ffr = gaz_dbi_get_row($gTables['fae_flux'], "id_tes_ref", $idtes_ch['id_tes']);
          if ($ffr) {
            $zip = new ZipArchive;
            $res = $zip->open(DATA_DIR.'files/'.$admin_aziend['codice'].'/'.$ffr['filename_zip_package']);
            if ($res === TRUE) {
              $zip->deleteName($ffr['filename_ori']);
              $zip->close();
            }
          }
          // infine cancello anche dalla tabella del db
					gaz_dbi_del_row($gTables['fae_flux'], 'id_tes_ref', $idtes_ch['id_tes']);
				}
        // annullo la contabilizzazione anche alla testata documento che lo contiene
        gaz_dbi_put_query($gTables['tesdoc'], 'id_con ='.$rev_ch['reverse_charge_idtes'],'id_con',0);
			}
			gaz_dbi_del_row($gTables['rigmoi'], 'id_tes', $i);

			//cancello la testata
			gaz_dbi_del_row($gTables['tesmov'], "id_tes", $i);
			// se si riferisce ad un documento contabilizzato annullo il riferimento al movimento
			gaz_dbi_put_query($gTables['tesdoc'], 'id_con ='.$i,'id_con',0);
			// se si riferisce ad un effetto contabilizzato annullo il riferimento al movimento
			gaz_dbi_put_query($gTables['effett'], 'id_con ='.$i,'id_con',0);
			//cancello anche l'eventuale rigo sul registro beni ammortizzabili
			$rs_assets=gaz_dbi_dyn_query("*",$gTables['assets'],"id_movcon = ". $i,'id',0,1);
			$assets = gaz_dbi_fetch_array($rs_assets);
			if ($assets) {
				gaz_dbi_del_row($gTables['assets'], "id",$assets['id']);
				// ... ed il relativo articolo in magazzino
				gaz_dbi_del_row($gTables['artico'], "id_assets",$assets['id']);
			}
			gaz_dbi_del_row($gTables['assets'], "id_movcon",$i);
		break;
		case "piacon":
				$i=intval($_POST['ref']);
				gaz_dbi_del_row($gTables['clfoco'], "codice", $i);
		break;
		case "caucon":
				$i=substr($_POST['ref'],0,3);
				gaz_dbi_del_row($gTables['caucon'], "codice", $i);
				//cancello anche i righi
				gaz_dbi_del_row($gTables['caucon_rows'], "caucon_cod", $i);
		break;
		case "comunicazioni_dati_fatture":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['comunicazioni_dati_fatture'], 'id', $i);
		break;
	}
}
?>
