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
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}

if ((isset($_POST['type']) && isset($_POST['ref'])) OR (isset($_POST['type']) && isset($_POST['id_tes']) && is_numeric($_POST['id_tes']))) {
	require("../../library/include/datlib.inc.php");
	require("../../modules/magazz/lib.function.php");
	$upd_mm = new magazzForm;
	$admin_aziend = checkAdmin();
  $send_fae_zip_package = gaz_dbi_get_row($gTables['company_config'], 'var', 'send_fae_zip_package');
	switch ($_POST['type']) {
    case "broacq":
      $i=intval($_POST['id_tes']);
      //cancello la testata
      gaz_dbi_del_row($gTables['tesbro'], "id_tes", $i);
      //... e i righi
      $rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = '{$i}'","id_tes desc");
      while ($a_row = gaz_dbi_fetch_array($rs_righidel)) {
        gaz_dbi_del_row($gTables['rigbro'], "id_rig", $a_row['id_rig']);
      }
      break;
		case "docacq":
			$i=intval($_POST['id_tes']);
			$data = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", $i);

			$sync_mods=[];
			$sync_mods=explode(",",$admin_aziend['gazSynchro']);
			if (in_array($send_fae_zip_package['val'],$sync_mods)){ // se c'è il modulo di sincronizzazione fatture elettroniche tolgo l'acquisizione al file
				$where = [];
				$where[]="title";
				$where[]=$data['fattura_elettronica_original_name'];
				$set['custom_field']="";
        $set['status']=0;
				gaz_dbi_table_update("files", $where, $set);
			}

			if ($data['tipdoc']!="AFT"){ // se non è una fattura AFT con DDT a riferimento posso cancellare
        gaz_dbi_del_row($gTables['tesdoc'], "id_tes", $i);
        if ($data['id_con'] >= 1) {
          gaz_dbi_del_row($gTables['tesmov'], 'id_tes', $data['id_con']);
          // se la fattura di acquisto è riferita in un bene ammortizzabile elimino i righi in gaz_001assets
          gaz_dbi_del_row($gTables['assets'], "id_movcon",$data['id_con']);
          // qui controllo se il documento ha generato reverse charge ed eventualmente elimino anche quello
          $id_rc=gaz_dbi_get_row($gTables['rigmoi'], 'reverse_charge_idtes', $data['id_con']); // in $id_rc['id_tes'] ho il riferimento a tesmov figlio
          // cancello l'eventuale figlio (fattura su reg.vendite del reverse charge)
          if ($id_rc){
            gaz_dbi_del_row($gTables['tesmov'], 'id_tes', $id_rc['id_tes']);
            gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', $id_rc['id_tes']);
            gaz_dbi_del_row($gTables['rigmoi'], 'id_tes', $id_rc['id_tes']);
            // cancello da tesdoc e rigdoc i documenti fittizi con tipdoc XFA o XNC che sono riferito a questo reverse
            $id_tes_rc = gaz_dbi_get_row($gTables['tesdoc'], 'id_con', $id_rc['id_tes'])['id_tes']; // riprendo l'id_tes del tesdoc che vado ad eliminare per eliminare assieme a rigdoc
            gaz_dbi_del_row($gTables['tesdoc'], 'id_tes', $id_tes_rc);
            gaz_dbi_del_row($gTables['rigdoc'], 'id_tes', $id_tes_rc);
          }
          // prima di eliminare i righi contabili devo eliminare le eventuali partite aperte ad essi collegati
          $rs_rmocdel = gaz_dbi_dyn_query("*", $gTables['rigmoc'], "id_tes = ".$data['id_con'],"id_tes");
          while ($rd = gaz_dbi_fetch_array($rs_rmocdel)) {
            gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_doc", $rd['id_rig']);
          }
          // ... quindi elimino il rigo contabile
          gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', $data['id_con']);
          gaz_dbi_del_row($gTables['rigmoi'], 'id_tes', $data['id_con']);
        }
        $rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = '".$i."'","id_tes desc");
        while ($a_row = gaz_dbi_fetch_array($rs_righidel)){
          gaz_dbi_del_row($gTables['rigdoc'], "id_rig", $a_row['id_rig']);
          if (intval($a_row['id_mag']) > 0){  //se c'è stato un movimento di magazzino lo azzero
            $mag=gaz_dbi_get_row($gTables['movmag'], "id_mov", $a_row['id_mag']);// ma prima ne ne prendo i relativi dati

            $upd_mm->uploadMag('DEL', '', '', '', '', '', '', '', '', '', '', '', $a_row['id_mag']);

            if (intval($mag['id_lotmag'])>0 && intval($mag['operat'])==1){// se aveva creato un id lotto lo cancello
               gaz_dbi_del_row($gTables['lotmag'], "id", $mag['id_lotmag']);
            }

            // cancello pure eventuale movimento sian
            gaz_dbi_del_row($gTables['camp_mov_sian'], "id_movmag", $a_row['id_mag']);
          }
        }
			} else { // se è AFT (fattura con ddt a riferimento)
        if (empty($data['ddt_type'])){
          $tipdoc="ADT";
          $data['ddt_type']="T";
        } elseif ( $data['ddt_type']=="T") {
					$tipdoc="AD".$data["ddt_type"];
				} elseif ($data['ddt_type']=="L"){
					$tipdoc="RD".$data["ddt_type"];
				} else {
					$tipdoc="AM".$data["ddt_type"]; // Contratto di traporto in entrata
				}
				$groups=gaz_dbi_dyn_query("*", $gTables['tesdoc'], "protoc = ".$data['protoc']." AND datfat = '".$data['datfat']."' AND seziva = ".$data['seziva']." AND clfoco = ".$data['clfoco']." AND tipdoc='".$data['tipdoc']."'");
				while ($data = gaz_dbi_fetch_array($groups)){
          if ($data['status']=='DdtAnomalo'){
            gaz_dbi_del_row($gTables['tesdoc'], "id_tes", $data['id_tes']);
            $rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = '".$i."'","id_tes desc");
            while ($a_row = gaz_dbi_fetch_array($rs_righidel)) {
              gaz_dbi_del_row($gTables['rigdoc'], "id_rig", $a_row['id_rig']);
              if (intval($a_row['id_mag']) > 0){  //se c'� stato un movimento di magazzino lo azzero
                $upd_mm->uploadMag('DEL', '', '', '', '', '', '', '', '', '', '', '', $a_row['id_mag']);
                // cancello pure eventuale movimento sian
                gaz_dbi_del_row($gTables['camp_mov_sian'], "id_movmag", $a_row['id_mag']);
              }
            }
          } else {
            $datreg = gaz_dbi_get_row($gTables['movmag'], 'id_rif', $data['id_tes']); // riprendo la data di registrazione da movmag
            $newval=$data;
            $newval['datreg']=$datreg['datreg'];$newval['protoc']="";$newval['ddt_type']="";$newval['numfat']="";$newval['datfat']="";$newval['id_con']=0;$newval['tipdoc']=$tipdoc;
            $newval['fattura_elettronica_original_name']="";$newval['fattura_elettronica_original_content']="";
            tesdocUpdate(array('id_tes', $newval['id_tes']), $newval);
          }
          // qui controllo se il documento ha generato reverse charge ed eventualmente elimino anche quello
          if ( $data['id_con'] >= 1) {
            $id_rc=gaz_dbi_get_row($gTables['rigmoi'], 'reverse_charge_idtes', $data['id_con']); // in $id_rc['id_tes'] ho il riferimento a tesmov figlio
            // cancello l'eventuale figlio (fattura su reg.vendite del reverse charge)
            if ($id_rc){
              gaz_dbi_del_row($gTables['tesmov'], 'id_tes', $id_rc['id_tes']);
              gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', $id_rc['id_tes']);
              gaz_dbi_del_row($gTables['rigmoi'], 'id_tes', $id_rc['id_tes']);
              // cancello da tesdoc e rigdoc i documenti fittizi con tipdoc XFA o XNC che sono riferito a questo reverse
              $id_tes_rc = gaz_dbi_get_row($gTables['tesdoc'], 'id_con', $id_rc['id_tes'])['id_tes']; // riprendo l'id_tes del tesdoc che vado ad eliminare per eliminare assieme a rigdoc
              gaz_dbi_del_row($gTables['tesdoc'], 'id_tes', $id_tes_rc);
              gaz_dbi_del_row($gTables['rigdoc'], 'id_tes', $id_tes_rc);
            }
            gaz_dbi_del_row($gTables['tesmov'], 'id_tes', $data['id_con']);
            // prima di eliminare i righi contabili devo eliminare le eventuali partite aperte ad essi collegati
            $rs_rmocdel = gaz_dbi_dyn_query("*", $gTables['rigmoc'], "id_tes = ".$data['id_con'],"id_tes");
            while ($rd = gaz_dbi_fetch_array($rs_rmocdel)) {
              gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_doc", $rd['id_rig']);
            }
            // ... quindi elimino il rigo contabile
            gaz_dbi_del_row($gTables['rigmoc'], 'id_tes', $data['id_con']);
            gaz_dbi_del_row($gTables['rigmoi'], 'id_tes', $data['id_con']);
          }
				}
			}
      break;
		case "pagdeb":
			$i=intval($_POST['id_tes']);
			//cancello la testata
			gaz_dbi_del_row($gTables['tesbro'], "id_tes", $i);
			//... e i righi
			$rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = '{$i}'","id_tes desc");
			while ($a_row = gaz_dbi_fetch_array($rs_righidel)) {
				  gaz_dbi_del_row($gTables['rigbro'], "id_rig", $a_row['id_rig']);
			}
      break;
		case "fornit":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['clfoco'], 'codice', $i);
      break;
		case "email":
			$i=filter_var($_POST['ref'], FILTER_VALIDATE_EMAIL);
			gaz_dbi_put_query($gTables['tesbro'], " email LIKE '%".$i."%'",'email','');
      break;
		case "supplier_schedule":
			$paymov= new Schedule;
			$paymov->deleteClosedPaymov(intval($_POST['ref']));
      break;
	  case "packacq":
			$name=$_POST['ref'];
			gaz_dbi_query("UPDATE " . $gTables['tesdoc'] . " SET fattura_elettronica_zip_package = '' WHERE fattura_elettronica_zip_package = '".$name."'");
      break;
	}
}
?>
