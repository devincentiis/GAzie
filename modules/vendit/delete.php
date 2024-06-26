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
// prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}

if ((isset($_POST['type'])&&isset($_POST['ref'])) || (isset($_POST['type']) && isset($_POST['id_tes']))) {
	require("../../library/include/datlib.inc.php");
	require("../../modules/magazz/lib.function.php");
	$upd_mm = new magazzForm;
	$admin_aziend = checkAdmin();
	switch ($_POST['type']) {
        case "docven":
				$i=intval($_POST['ref']);
				if (isset($_POST['id_tes'])) { //sto eliminando un singolo documento
					$result = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "id_tes = " . intval($_POST['id_tes']));
					$row = gaz_dbi_fetch_array($result);
					if (substr($row['tipdoc'], 0, 2) == 'DD') {
						$rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datemi) = '" . substr($row['datemi'], 0, 4) . "' AND tipdoc LIKE '" . substr($row['tipdoc'], 0, 2) . "_' AND seziva = " . $row['seziva'] . " ", "numdoc DESC", 0, 1);
					} elseif ($row['tipdoc'] == 'RDV') {
						$rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "id_tes = " . intval($_POST['id_tes']));
					} elseif ($row['tipdoc'] == 'VCO') {
						$rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "datemi = '" . $row['datemi'] . "' AND tipdoc = 'VCO' AND seziva = " . $row['seziva'], "datemi DESC, numdoc DESC", 0, 1);
					} elseif ($row['tipdoc'] == 'RPL') {
						$rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "id_tes = " . intval($_POST['id_tes']));
					} else {
						$rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datemi) = '" . substr($row['datemi'], 0, 4) . "' AND tipdoc LIKE '" . substr($row['tipdoc'], 0, 1) . "%' AND seziva = " . $row['seziva'] . " ", "protoc DESC, numdoc DESC", 0, 1);
					}
				} elseif (isset($_POST['anno']) and isset($_POST['seziva']) and isset($i)) { //sto eliminando una fattura differita
					$result = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datfat) = '" . intval($_POST['anno']) . "' AND seziva = '" . intval($_POST['seziva']) . "' AND protoc = '" . $i . "' AND tipdoc LIKE 'F__'");
					$row = gaz_dbi_fetch_array($result);
					$rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datfat) = '" . substr($row['datfat'], 0, 4) . "' AND tipdoc LIKE '" . substr($row['tipdoc'], 0, 1) . "__' AND seziva = " . $row['seziva'] . " ", "protoc DESC, numdoc DESC", 0, 1);
				} else { //non ci sono dati sufficenti per stabilire cosa eliminare
				break;
				}
				//controllo se sono stati emessi documenti nel frattempo...
				$ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
				if ($ultimo_documento) {
					if (($ultimo_documento['tipdoc'] == 'VRI' || $ultimo_documento['tipdoc'] == 'VCO'
						|| substr($ultimo_documento['tipdoc'], 0, 2) == 'DD' || $ultimo_documento['tipdoc'] == 'RPL' || $ultimo_documento['tipdoc'] == 'RDV' || $ultimo_documento['tipdoc'] == 'CMR' )
						&& $ultimo_documento['numdoc'] == $row['numdoc']) {
						gaz_dbi_del_row($gTables['tesdoc'], 'id_tes', $row['id_tes']);
						gaz_dbi_del_row($gTables['tesmov'], 'id_tes', $row['id_con']);
            // prima di eliminare i righi contabili devo eliminare le eventuali partite aperte ad essi collegati
            $rs_rmocdel = gaz_dbi_dyn_query("*", $gTables['rigmoc'], "id_tes = ".$row['id_con'],"id_tes");
            while ($rd = gaz_dbi_fetch_array($rs_rmocdel)) {
                gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_doc", $rd['id_rig']);
            }
						gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', $row['id_con']);
						gaz_dbi_del_row($gTables['rigmoi'], 'id_tes', $row['id_con']);
						gaz_dbi_put_query($gTables['rigbro'], 'id_doc = ' . $row["id_tes"], "id_doc", "");
						//cancello i righi
						$rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = '" . $row['id_tes'] . "'");
						while ($val_old_row = gaz_dbi_fetch_array($rs_righidel)) {
							gaz_dbi_del_row($gTables['rigdoc'], "id_rig", $val_old_row['id_rig']);
							gaz_dbi_del_row($gTables['body_text'], "table_name_ref = 'rigdoc' AND id_ref", $val_old_row['id_rig']);
							if (intval($val_old_row['id_mag']) > 0) {  //se c'è stato un movimento di magazzino lo azzero
								$upd_mm->uploadMag('DEL', '', '', '', '', '', '', '', '', '', '', '', $val_old_row['id_mag']);
								// se c'è stato, cancello pure il movimento sian
								gaz_dbi_del_row($gTables['camp_mov_sian'], "id_movmag", $val_old_row['id_mag']);
							}
						}
						// in caso di eliminazione di un reso da c/visione che quindi ha un link su un DDV
						if ($ultimo_documento['id_doc_ritorno'] > 0 ) {
								gaz_dbi_put_row($gTables['tesdoc'], 'id_tes', $ultimo_documento['id_doc_ritorno'], 'id_doc_ritorno',0);
						}
						break;
					} elseif ($ultimo_documento['protoc'] == intval($i) and $ultimo_documento['tipdoc'] != 'FAD') {
						//allora procedo all'eliminazione della testata e dei righi...
						//cancello la testata
						gaz_dbi_del_row($gTables['tesdoc'], "id_tes", $row['id_tes']);
						gaz_dbi_del_row($gTables['tesmov'], 'id_tes', $row['id_con']);
                        // prima di eliminare i righi contabili devo eliminare le eventuali partite aperte ad essi collegati
                        $rs_rmocdel = gaz_dbi_dyn_query("*", $gTables['rigmoc'], "id_tes = ".$row['id_con'],"id_tes");
                        while ($rd = gaz_dbi_fetch_array($rs_rmocdel)) {
                            gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_doc", $rd['id_rig']);
                        }
						gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', $row['id_con']);
						gaz_dbi_del_row($gTables['rigmoi'], 'id_tes', $row['id_con']);
						gaz_dbi_put_query($gTables['rigbro'], 'id_doc = ' . $row["id_tes"], "id_doc", "");
						// cancello pure l'eventuale movimento di split payment
						$r_split = gaz_dbi_get_row($gTables['tesmov'], 'id_doc', $row['id_tes']);
                        if ($r_split) {
                            gaz_dbi_del_row($gTables['tesmov'], 'id_tes', $r_split['id_tes']);
                            // prima di eliminare i righi contabili devo eliminare le eventuali partite aperte ad essi collegati
                            $rs_rmocdel = gaz_dbi_dyn_query("*", $gTables['rigmoc'], "id_tes = ".$r_split['id_tes'],"id_tes");
                            while ($rd = gaz_dbi_fetch_array($rs_rmocdel)) {
                                gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_doc", $rd['id_rig']);
                            }
                            gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', $r_split['id_tes']);
                        }
						//cancello i righi
						$rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = " . $row['id_tes'] );
						while ($val_old_row = gaz_dbi_fetch_array($rs_righidel)) {
							gaz_dbi_del_row($gTables['rigdoc'], "id_rig", $val_old_row['id_rig']);
							gaz_dbi_del_row($gTables['body_text'], "table_name_ref = 'rigdoc' AND id_ref", $val_old_row['id_rig']);
							if (intval($val_old_row['id_mag']) > 0) {  //se c'� stato un movimento di magazzino lo azzero
								$upd_mm->uploadMag('DEL', '', '', '', '', '', '', '', '', '', '', '', $val_old_row['id_mag']);

								// se c'è stato, cancello pure il movimento sian
								gaz_dbi_del_row($gTables['camp_mov_sian'], "id_movmag", $val_old_row['id_mag']);
							}
						}
						break;
					} elseif ($ultimo_documento['protoc'] == intval($i) and $ultimo_documento['tipdoc'] == 'FAD') {
						//allora procedo alla modifica delle testate per ripristinare i DdT...
						if ( $row["ddt_type"]!="R") {
							gaz_dbi_put_row($gTables['tesdoc'], "id_tes", $row["id_tes"], "tipdoc", "DD" . $row["ddt_type"]);
						} else {
							gaz_dbi_put_row($gTables['tesdoc'], "id_tes", $row["id_tes"], "tipdoc", "CM" . $row["ddt_type"]);
						}
						gaz_dbi_put_row($gTables['tesdoc'], "id_tes", $row["id_tes"], "protoc", "");
						gaz_dbi_put_row($gTables['tesdoc'], "id_tes", $row["id_tes"], "numfat", "");
						gaz_dbi_put_row($gTables['tesdoc'], "id_tes", $row["id_tes"], "datfat", "");
						gaz_dbi_del_row($gTables['tesmov'], 'id_tes', $row['id_con']);
            // prima di eliminare i righi contabili devo eliminare le eventuali partite aperte ad essi collegati
            $rs_rmocdel = gaz_dbi_dyn_query("*", $gTables['rigmoc'], "id_tes = ".$row['id_con'],"id_tes");
            while ($rd = gaz_dbi_fetch_array($rs_rmocdel)) {
                gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_doc", $rd['id_rig']);
            }
						gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', $row['id_con']);
						gaz_dbi_del_row($gTables['rigmoi'], 'id_tes', $row['id_con']);
						while ($a_row = gaz_dbi_fetch_array($result)) {
							if ( $row["ddt_type"]!="R") {
								gaz_dbi_put_row($gTables['tesdoc'], "id_tes", $a_row["id_tes"], "tipdoc", "DD" . $a_row["ddt_type"]);
							} else {
								gaz_dbi_put_row($gTables['tesdoc'], "id_tes", $a_row["id_tes"], "tipdoc", "CM" . $a_row["ddt_type"]);
							}
							gaz_dbi_put_row($gTables['tesdoc'], "id_tes", $a_row["id_tes"], "protoc", "");
							gaz_dbi_put_row($gTables['tesdoc'], "id_tes", $a_row["id_tes"], "numfat", "");
							gaz_dbi_put_row($gTables['tesdoc'], "id_tes", $a_row["id_tes"], "datfat", "");
							gaz_dbi_del_row($gTables['tesmov'], 'id_tes', $row['id_con']);
              // prima di eliminare i righi contabili devo eliminare le eventuali partite aperte ad essi collegati
              $rs_rmocdel = gaz_dbi_dyn_query("*", $gTables['rigmoc'], "id_tes = ".$row['id_con'],"id_tes");
              while ($rd = gaz_dbi_fetch_array($rs_rmocdel)) {
                  gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_doc", $rd['id_rig']);
              }
							gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', $row['id_con']);
							gaz_dbi_del_row($gTables['rigmoi'], 'id_tes', $row['id_con']);
							// cancello pure l'eventuale movimento di split payment
							$r_split = gaz_dbi_get_row($gTables['tesmov'], 'id_doc', $a_row['id_tes']);
              if ($r_split) {
                gaz_dbi_del_row($gTables['tesmov'], 'id_tes', $r_split['id_tes']);
                // prima di eliminare i righi contabili devo eliminare le eventuali partite aperte ad essi collegati
                $rs_rmocdel = gaz_dbi_dyn_query("*", $gTables['rigmoc'], "id_tes = ".$r_split['id_tes'],"id_tes");
                while ($rd = gaz_dbi_fetch_array($rs_rmocdel)) {
                    gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_doc", $rd['id_rig']);
                }
                gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', $r_split['id_tes']);
              }
						}
						break;
					} elseif ($ultimo_documento['protoc'] != $row["protoc"]) {
						$message = "Si sta tentando di eliminare un documento <br /> diverso dall'ultimo emesso!";
					}
				} else {
					$message = "Si sta tentando di eliminare un documento <br /> inesistente o contabilizzato!";
				}
		break;
		case "broven":
			//procedo all'eliminazione della testata e dei righi...
			$tipdoc = gaz_dbi_get_row($gTables['tesbro'], "id_tes", intval($_POST['id_tes']))['tipdoc'];
			//cancello la testata
			gaz_dbi_del_row($gTables['tesbro'], "id_tes", intval($_POST['id_tes']));
			//... e i righi
			$rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes =". intval($_POST['id_tes']),"id_tes DESC");
			while ($a_row = gaz_dbi_fetch_array($rs_righidel)) {
				gaz_dbi_del_row($gTables['rigbro'], "id_rig", $a_row['id_rig']);
        if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname']) AND $tipdoc!=="VOW"){
          // aggiorno l'e-commerce ove presente se l'ordine non è web
          $gs=$admin_aziend['synccommerce_classname'];
          $gSync = new $gs();
          if($gSync->api_token){
            $gSync->SetProductQuantity($a_row['codart']);
          }
				}
        if (intval($a_row['id_body_text']>0)){// se c'era un body text lo cancello
          gaz_dbi_del_row($gTables['body_text'], "table_name_ref = 'rigbro' AND id_ref ",$a_row['id_rig']);
        }
        if (intval($a_row['tiprig'])==50 || intval($a_row['tiprig'])==51){
          $urlarr=(glob(DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/rigbrodoc_' . $a_row['id_rig'].".*"));
          if (isset($urlarr)){
            $fn = pathinfo($urlarr[0]);
            unlink(DATA_DIR . 'files/' . $admin_aziend['company_id'] . '/rigbrodoc_' . $a_row['id_rig'] . '.' . $fn['extension']);
          }
        }
			}
		break;
		case "effett":
			// Rilegge i dati dell'effetto.
			$effetto = gaz_dbi_get_row($gTables['effett'], "id_tes", intval($_POST['id_tes']));
			// elimina subito la registrazione.
			if ($effetto['id_con'] > 0) {
				gaz_dbi_del_row($gTables['tesmov'], 'id_tes', $effetto['id_con']);
                // prima di eliminare i righi contabili devo eliminare le eventuali partite aperte ad essi collegati
                $rs_rmocdel = gaz_dbi_dyn_query("*", $gTables['rigmoc'], "id_tes = ".$effetto['id_con'],"id_tes");
                while ($rd = gaz_dbi_fetch_array($rs_rmocdel)) {
                    gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_doc", $rd['id_rig']);
                }
				gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', $effetto['id_con']);
			}
			$result = gaz_dbi_del_row($gTables['effett'], "id_tes", intval($_POST['id_tes']));
			// i dati univoci della fattura che ha originato l'effetto
			$where = "protoc=$effetto[protoc] AND seziva=$effetto[seziva] AND datfat='$effetto[datfat]'";
			// se la fattura non ha altri effetti associati resettiamo il flag geneff
			$altri_effetti = gaz_dbi_record_count($gTables['effett'], $where);
			if (!$altri_effetti) {
				gaz_dbi_query("UPDATE $gTables[tesdoc] SET geneff = '' WHERE $where AND tipdoc LIKE 'F%'");
			}
		break;
		case "client":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['clfoco'], 'codice', $i);
		break;
		case "destinazioni":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['destina'], "codice", $i);
		break;
		case "agenti":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['agenti'], 'id_agente', $i);
			gaz_dbi_del_row($gTables['provvigioni'], 'id_agente', $i);
		break;
		case "contract":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['contract'], 'id_contract', $i);
			gaz_dbi_del_row($gTables['contract_row'], 'id_contract', $i);
      gaz_dbi_del_row($gTables['body_text'], "table_name_ref = 'contract' AND id_ref", $i);
		break;
		case "ecr":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['cash_register'], 'id_cash', $i);
			gaz_dbi_del_row($gTables['cash_register_reparto'], 'cash_register_id_cash', $i);
			gaz_dbi_del_row($gTables['cash_register_tender'], 'cash_register_id_cash', $i);
		break;
		case "customer_schedule":
			$paymov= new Schedule;
			$paymov->deleteClosedPaymov(intval($_POST['ref']));
		break;
		case "mndtritdinf":
			$i=intval($_POST['ref']);
			$f=gaz_dbi_get_row($gTables['files'], "id_doc", $i);
      unlink(DATA_DIR . "files/" .$admin_aziend['codice']."/doc/". $i. ".".$f['extension']);
			gaz_dbi_del_row($gTables['files'], 'id_doc', $i);
		break;
		case "distinte":
			$i=intval($_POST['ref']);
			$f=gaz_dbi_get_row($gTables['files'], "id_doc", $i);
      unlink(DATA_DIR . "files/" .$admin_aziend['codice']."/doc/". $i. ".".$f['extension']);
			gaz_dbi_del_row($gTables['files'], 'id_doc', $i);
      gaz_dbi_query("UPDATE $gTables[effett] SET id_distinta = 0, banacc = 0 WHERE id_distinta=$i");
		break;
		case "customer_group":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['customer_group'], "id", $i);
		break;
    case "email":
    $i=filter_var($_POST['ref'], FILTER_VALIDATE_EMAIL);
    gaz_dbi_put_query($gTables['tesdoc'], " email LIKE '%".$i."%' AND id_tes =".intval($_POST['tes_id']),'email','');
    break;
    case "clfoco_doc":
    $i=intval($_POST['ref']);
    gaz_dbi_del_row($gTables['files'], 'id_doc', $i);
    break;
	}
}
?>
