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
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();

$deleted_rows = (isset($_POST['deleted_rows']))?$_POST['deleted_rows']:$deleted_rows=[];
$id_staff=intval($_POST['id_staff']);
$date=substr($_POST['date'],0,10);
// provo a recuperare sempre e comunque staff_worked_hours 
$work_h = gaz_dbi_get_row($gTables['staff_worked_hours'], "id_staff", $id_staff, "AND work_day = '$date'"); // mi carico il relativo rigo di staff_worked_hours

$tesbro_prw = gaz_dbi_get_row($gTables['tesbro'],'datemi', $date, "AND tipdoc = 'PRW'"); // controllo se in quel giorno è stato registrato almeno un lavoro

if ($tesbro_prw){
	$id_tes = $tesbro_prw['id_tes'];
} else { // non avendolo inserisco un rigo in tesbro con tipdoc=PRW
	$id_tes = gaz_dbi_table_insert("tesbro", ['tipdoc'=>'PRW','datemi'=>$date]);
}

// elimino TUTTI i vecchi righi (rigbro) del documento di tipo "PRW" per poi reinserirli tutti sotto partendo dai dati in $acc_staff_worked_hours
gaz_dbi_query("DELETE FROM " . $gTables['rigbro'] . " WHERE id_tes =".$id_tes." AND id_body_text = " . $id_staff); // ricordo che in questo caso id_body_text lo uso per indicare il riferimento all'addetto

if (isset($_POST['rec_pres'])) {
	$work_movements = $_POST['rec_pres'];
// INIZIO CONTROLLO ERRORI E ACCUMULO DATI PER AGGIORNAMENTO staff_worked_hours
	$noerr=true; // non ho errori
	$n=0;
	$acc_staff_worked_hours=['id_staff'=>$id_staff,'work_day'=>$date,'hours_normal'=>0,'id_work_type_extra'=>0,'hours_extra'=>0,'id_other_type'=>0,'hours_other'=>0,'id_absence_type'=>0,'hours_absence'=>0,'id_tes'=>$id_tes];
	foreach ($work_movements as $k=>$work_mov){ // faccio un primo ciclo per controllare se ci sono errori, per eliminare i righi eliminati e per accumulare i nuovi valori per staff_worked_hours
	  if (array_key_exists($work_mov['id'], $deleted_rows)) { // è un rigo da eliminare, non lo accumulo ma anzi lo elimino sia dal db che da questo array 
  		gaz_dbi_del_row($gTables['staff_work_movements'], "id",$work_mov['id']);
		unset($work_movements[$k]);
	  } else {
		if ( $work_mov['id_work_type'] > 0 ) {
			$type = gaz_dbi_get_row($gTables['staff_work_type'], "id_work", $work_mov['id_work_type']); // carico il tipo di movimento del vecchio cartellino
			$work_movements[$k]['type']=$type['id_work_type'];
		} else {
			$type['id_work_type'] = 0;
			$work_movements[$k]['type']=0;
		}
		if ($work_mov['start_work'] == "" AND $work_mov['end_work'] == ""){ 
			echo "ERRORE riga ",$n,": l'orario di inizio e di fine deve essere sempre impostato\n";
			$noerr=false; 
		} else {
			$st=explode(':',substr($work_mov['start_work'],-5));
			$minstart=$st[0]*60+$st[1];
			$et=explode(':',substr($work_mov['end_work'],-5));
			$minend=$et[0]*60+$et[1];
			$minwork=$minend-$minstart;
			$decimalwork=round($minwork/60,2);
			// aggiungo il valore all'array $work_movements per ritrovarmelo sotto quando valorizzerò quanti di rigbro per PRW
			$work_movements[$k]['quanti']=$decimalwork;
			if ($type['id_work_type']==0){// lavoro ordinario			
				$acc_staff_worked_hours['hours_normal']+=$decimalwork;		
			} elseif ($type['id_work_type']==1){// lavoro straordinario
				$acc_staff_worked_hours['hours_extra']+=$decimalwork;		
				$acc_staff_worked_hours['id_work_type_extra']=$type['id_work'];
			} elseif ($type['id_work_type'] > 1 && $type['id_work_type'] < 7){// lavoro notturno+lavoro domenicale+lavoro festivo+lavoro giorni riposo+lavoro in turni
				$acc_staff_worked_hours['hours_other']+=$decimalwork;		
				$acc_staff_worked_hours['id_other_type']=$type['id_work'];
			} elseif ($type['id_work_type'] == 9){// Assenza
				$acc_staff_worked_hours['id_absence_type'] = $type['id_work'];
				$acc_staff_worked_hours['hours_absence']+=$decimalwork;		
			}  
		}
		if (strtotime($work_mov['start_work']) > strtotime($work_mov['end_work'])){
			echo "ERRORE riga ",$n,": l'ora di fine è inferiore a quella di inizio\n";
			$noerr=false; 
		}
		if (((strtotime($work_mov['end_work'])-strtotime($work_mov['start_work']))/3600)>8){
			echo "ERRORE riga ",$n,": non si può inserire un movimento con più di 8 ore\n";
			$noerr=false; 
		}
		if ($work_mov['start_work'] == $work_mov['end_work']){ 
			echo "ERRORE riga ",$n,": l'orario di inizio e di fine non possono essere uguali\n";
			$noerr=false; 
		}
	  }
	}
// FINE CONTROLLO ERRORI E ACCUMULO DATI PER AGGIORNAMENTO staff_worked_hours

	if ($noerr) { // non ho errori posso aggiornare 
	  if (!$work_h) {// se non ne ho uno staff_worked_hours lo inserisco senza valori per poi aggiornarlo comunque alla fine
		$work_h['id']=gaz_dbi_table_insert("staff_worked_hours", []);
	  }
	  $n=0;
	  reset($work_movements);
	  foreach ($work_movements as $work_mov){
		$n++;
		if ($work_mov['id']>0){ // è un update
			// AGGIORNO IL CARTELLINO ovvero staff_work_movements
			$newValue =array("start_work"=>$date." ".$work_mov['start_work'], "end_work"=>$date." ".$work_mov['end_work'], "id_work_type"=>$work_mov['id_work_type'], "min_delay"=>$work_mov['min_delay'], "id_orderman"=>$work_mov['id_orderman'], "note"=>$work_mov['note'], "id_staff_worked_hours"=>$work_h['id'], "hourly_cost"=>$work_mov['hourly_cost']); 
			gaz_dbi_table_update("staff_work_movements", array('id', $work_mov['id']), $newValue);
		} else { // è un insert
			$value =array("id_staff"=>$id_staff, "start_work"=>$date." ".$work_mov['start_work'], "end_work"=>$date." ".$work_mov['end_work'], "id_work_type"=>$work_mov['id_work_type'], "min_delay"=>$work_mov['min_delay'], "id_orderman"=>$work_mov['id_orderman'], "note"=>$work_mov['note'], "id_staff_worked_hours"=>$work_h['id'], "hourly_cost"=>$work_mov['hourly_cost']);
			$work_mov['id']=gaz_dbi_table_insert("staff_work_movements", $value);
		}
		// GESTIONE RIGHI DOCUMENTO TIPO "PRW" in tesbro, verrà generato in automatico un documento di report giornaliero in tesbro-rigbro
		if ($work_mov['type']<9) { // inserisco un rigo solo se non è una assenza
			gaz_dbi_table_insert("rigbro", ['id_tes'=>$id_tes,'descri'=>$work_mov['note'],'id_body_text'=>$id_staff,'quanti'=>$work_mov['quanti'],'prelis'=>$work_mov['hourly_cost'],'id_orderman'=>$work_mov['id_orderman'],'id_doc'=>$work_mov['id']]);
		}
	  }	
	  // aggiorno staff_worked_hours
	  gaz_dbi_table_update("staff_worked_hours", array('id', $work_h['id']), $acc_staff_worked_hours);

	}
} else { // se sono qui vuol dire che non ho più righi potrebbe essere che abbia eliminato tutti i movimenti esistenti allora elimino staff_worked_hours 
	gaz_dbi_del_row($gTables['staff_worked_hours'], "id", $work_h['id']);
}
foreach ($deleted_rows as $del_row) { // gli eventuali righi rimanenti
	gaz_dbi_del_row($gTables['staff_work_movements'], "id", $del_row);
}

// elimino il rigo tipdoc "PRW" in tesbro quando questo non ha righi 
gaz_dbi_query("DELETE FROM " . $gTables['tesbro'] . " WHERE tipdoc='PRW' AND datemi = '".$date."' AND NOT EXISTS (SELECT NULL FROM ".$gTables['rigbro']." WHERE id_tes = ".$id_tes.")"); 