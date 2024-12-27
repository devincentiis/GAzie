<?php
/*
 --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (https://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2022 - Antonio De Vincentiis Montesilvano (PE)
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
	require("../../modules/magazz/lib.function.php");
	$upd_mm = new magazzForm;
	$admin_aziend = checkAdmin();
	switch ($_POST['type']) {
		case "artico":
				$i=substr($_POST['ref'],0,32);
				//Cancello le eventuali immagini web e i documenti
				$rs=gaz_dbi_dyn_query ("*",$gTables['files'],"table_name_ref = 'artico' AND item_ref = '".$i."'");
				foreach ($rs as $delimg){
					gaz_dbi_del_row($gTables['files'], "id_doc", $delimg['id_doc']);
					unlink (DATA_DIR."files/".$admin_aziend['codice']."/images/". $delimg['id_doc'] . "." . $delimg['extension']);
				}
				// Cancello l'eventuale body_text
				gaz_dbi_del_row($gTables['body_text'], "table_name_ref", "artico_".$i);
				//Cancello se presenti gli articoli presenti in distinta base
				$result = gaz_dbi_del_row($gTables['distinta_base'], "codice_composizione", $i );
				//Cancello l'articolo
				$result = gaz_dbi_del_row($gTables['artico'], "codice", $i);
			break;
		case "facility":
			$i=intval($_POST['ref']);
			$sql = "UPDATE ".$gTables['artico']." SET id_artico_group=0  WHERE id_artico_group=".$i;
			// sgancio gli articoli dal gruppo
			gaz_dbi_query($sql);
			//Cancello gruppo
			$result = gaz_dbi_del_row($gTables['artico_group'], "id_artico_group", $i);
		break;
		case "extra":
			$i=substr($_POST['ref'],0,32);
			//Cancello le eventuali immagini web e i documenti
			$rs=gaz_dbi_dyn_query ("*",$gTables['files'],"table_name_ref = 'artico' AND item_ref = '".$i."'");
			foreach ($rs as $delimg){
				gaz_dbi_del_row($gTables['files'], "id_doc", $delimg['id_doc']);
				unlink (DATA_DIR."files/".$admin_aziend['codice']."/images/". $delimg['id_doc'] . "." . $delimg['extension']);
			}
			// Cancello l'eventuale body_text
			gaz_dbi_del_row($gTables['body_text'], "table_name_ref", "artico_".$i);
			//Cancello se presenti gli articoli presenti in distinta base
			$result = gaz_dbi_del_row($gTables['distinta_base'], "codice_composizione", $i );
			//Cancello l'articolo
			$result = gaz_dbi_del_row($gTables['artico'], "codice", $i);
			//Cancello anche il rispettivo rigo dalla tabella rental_extra
			$result = gaz_dbi_del_row($gTables['rental_extra'], "codart", $i);
		break;
		case "booking":
			//procedo all'eliminazione della testata e dei righi...
			$tesbro = gaz_dbi_get_row($gTables['tesbro'], "id_tes", intval($_POST['id_tes']));// la testata che andrò ad eliminare
			//cancello la testata
			gaz_dbi_del_row($gTables['tesbro'], "id_tes", intval($_POST['id_tes']));
			//... e i righi
			$rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes =". intval($_POST['id_tes']),"id_tes DESC");
			while ($a_row = gaz_dbi_fetch_array($rs_righidel)) {
				gaz_dbi_del_row($gTables['rigbro'], "id_rig", $a_row['id_rig']);
                if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname']) AND $tesbro['tipdoc']!=="VOW"){
                    // aggiorno l'e-commerce ove presente se l'ordine non è web
                    $gs=$admin_aziend['synccommerce_classname'];
                    $gSync = new $gs();
					if($gSync->api_token){
						$gSync->SetProductQuantity($a_row['codart']);
					}
				}
				gaz_dbi_del_row($gTables['body_text'], "table_name_ref = 'rigbro' AND id_ref ",$a_row['id_rig']);
				// cancello anche l'evento
				$rental_events = gaz_dbi_get_row($gTables['rental_events'], "id_tesbro", intval($_POST['id_tes']));
						gaz_dbi_del_row($gTables['rental_events'], "id_tesbro", $_POST['id_tes']);
				// aggiorno buono sconto se c'è
				if (isset($rental_events['voucher_id']) && intval($rental_events['voucher_id'])>0){// se era stato usato un buono sconto
				  $rental_discounts  = gaz_dbi_get_row($gTables['rental_discounts'], "id", intval($rental_events['voucher_id']));
				  if ($rental_discounts['reusable']>0 AND $rental_discounts['STATUS']=="CLOSED"){// se lo sconto era stato chiuso
					$sql = "UPDATE ".$gTables['rental_discounts']." SET STATUS = 'CREATED' WHERE id = ".intval($rental_events['voucher_id']);
					$result = gaz_dbi_query($sql);// riapro lo sconto
				  }
				}
				// cancello anche tutti i pagamenti relativi
				gaz_dbi_del_row($gTables['rental_payments'], "id_tesbro", intval($_POST['id_tes']));
				// vedo se la prenotazione proveniva da un preventivo
				$prev = gaz_dbi_get_row($gTables['tesbro'], "numfat", intval($_POST['id_tes']), " AND datfat = '".$tesbro['datemi']."' AND tipdoc = 'VPR'");
				if ($prev){// se c'è il preventivo lo svincolo
					if ($data = json_decode($prev['custom_field'],true)){// se c'è un json in anagra
						if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental" lo aggiorno
						  $data['vacation_rental']['id_booking']='';
						  $custom_field = json_encode($data);
						}
					}
					$sql = "UPDATE ".$gTables['tesbro']." SET custom_field = '".$custom_field."', datfat = '0000-00-00', numfat = '0' WHERE id_tes = ".intval($prev['id_tes']);
					$result = gaz_dbi_query($sql);// resetto il preventivo
				}
			}
		break;
		case "ical":
			// elimino l'Ical dalla tabella ical
			gaz_dbi_del_row($gTables['rental_ical'], 'id', intval($_POST['ref']));
			// elimino tutti i suoi eventi dalla tabella rental_events
			gaz_dbi_del_row($gTables['rental_events'], 'Ical_sync_id', intval($_POST['ref']));
		break;
		case "discount":
			// elimino lo sconto dalla tabella rental_discounts
			gaz_dbi_del_row($gTables['rental_discounts'], 'id', intval($_POST['ref']));
		break;
		case "delete_payment":
			// elimino il pagamento
      $rent_pay=gaz_dbi_get_row($gTables['rental_payments'], 'payment_id', intval($_POST['ref']));
      if (intval($rent_pay['id_paymov'])>0){// se c'è stata, cancello la registrazione contabile del pagamento
        $paymov=gaz_dbi_get_row($gTables['paymov'], 'id', intval($rent_pay['id_paymov']));
        gaz_dbi_del_row($gTables['paymov'], 'id', intval($rent_pay['id_paymov']));
        $rigmoc=gaz_dbi_get_row($gTables['rigmoc'], 'id_rig', intval($paymov['id_rigmoc_pay']));
        gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', intval($rigmoc['id_tes']));
        gaz_dbi_del_row($gTables['tesmov'], 'id_tes', intval($rigmoc['id_tes']));
        if ($rent_pay['type']=="Caparra_confirmatoria"){
          $vacation_caparra_avere=gaz_dbi_get_row($gTables['company_config'], "var", 'vacation_caparra_avere')['val'];
          // cerco la registrazione di ricevimento caparra, ante imputazione
          $rigmoc2=gaz_dbi_get_row($gTables['rigmoc'], 'codcon', intval($vacation_caparra_avere), " AND import = ".$rigmoc['import']);
          if (isset($rigmoc2['id_tes'])){// se c'è, cancello i relativi rigmoc, paymov e tesmov anche del ricevimento
            gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', intval($rigmoc2['id_tes']));
            gaz_dbi_del_row($gTables['tesmov'], 'id_tes', intval($rigmoc2['id_tes']));
            gaz_dbi_del_row($gTables['paymov'], 'id_rigmoc_pay', intval($rigmoc2['id_rig']));

          }
        }
      }
			gaz_dbi_del_row($gTables['rental_payments'], 'payment_id', intval($_POST['ref']));
		break;
		case "delete_data":
		  // elimino dati carta di credito presenti nel data base

			$anagra = gaz_dbi_get_row($gTables['anagra'], "id", intval($_POST['ref']));
			if ($data = json_decode($anagra['custom_field'],true)){// se c'è un json in anagra
			if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental" lo aggiorno
			  $data['vacation_rental']['first_ccn']='';
			  $data['vacation_rental']['cvv']='';
			  $data['vacation_rental']['ccname']='';
			  $data['vacation_rental']['amount']='';
			} else { //se non c'è il modulo "vacation_rental" nel custom field lo aggiungo agli eventuali altri moduli già presenti
			  $data['vacation_rental']= array('vacation_rental'=>array('first_ccn' => '','cvv' => '','ccname' => '','amount' => ''));
			}
			}else{// se non c'è un json, lo inserisco
				$data=[];
				$data= array('vacation_rental'=>array('first_ccn' => encript(substr($_POST['ccnumber'],0,8)),'cvv' => encript($_POST['cccvv']),'ccname' => encript($_POST['ccname']),'amount' => encript($topay)));
			}
			$custom_field = json_encode($data);
			gaz_dbi_table_update('anagra', array('id',intval($_POST['ref'])), array('custom_field' => $custom_field));
		break;
	}
}
?>
