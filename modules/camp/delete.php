<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
// prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}
if (isset($_POST['type'])&&isset($_POST['ref'])) {
	require("../../library/include/datlib.inc.php");
	$admin_aziend = checkAdmin();
	switch ($_POST['type']) {
        case "catmer":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['catmer'],"codice",$i);
		break;
		case "avversity":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['camp_avversita'],"id_avv",$i);
		break;
		case "colture":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['camp_colture'],"id_colt",$i);
		break;
		case "usefito":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['camp_uso_fitofarmaci'],"id",$i);
		break;
		case "recstocc":
			$i=substr($_POST['ref'],0,10);
			gaz_dbi_del_row($gTables['camp_recip_stocc'],"cod_silos",$i);
		break;
		case "campi":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['campi'],"codice",$i);
		break;
		case "campmovmag":
			$i=intval($_POST['ref']);
			$form = gaz_dbi_get_row($gTables['movmag'], 'id_mov', $i);
			$id_mov=$i;$campo_impianto=$form['campo_impianto'];
			// inizio cancellazione ore operaio
			// controllo se clfoco è un operaio e ne prendo l'id_staff
			$res = gaz_dbi_get_row($gTables['staff'], "id_clfoco", $form['clfoco']);
			If (isset ($res)) { // se c'è nello staff, cioè è un operaio
				$rin = gaz_dbi_get_row($gTables['staff_worked_hours'], "id_staff ", $res['id_staff'], "AND work_day ='{$form['datdoc']}'");
				If (isset($rin)) { // se esiste il giorno dell'operaio prendo le ore normali lavorate e gli sottraggo quelle del movimento da cancellare
					$hours_normal=$rin['hours_normal']-$form['quanti'];
					// ne aggiorno il database
					$query = "UPDATE ". $gTables['staff_worked_hours']." SET hours_normal ='".$hours_normal."' WHERE id_staff = '".$res['id_staff']."' AND work_day = '".$form['datdoc']."'";
					gaz_dbi_query($query);
				}
			}
			// fine cancellazione ore operaio
			if ($campo_impianto>0) { // se c'è un campo di coltivazione aggiorno il giorno di sospensione
				$form2 = gaz_dbi_get_row($gTables['campi'], 'codice', intval($campo_impianto));
				if (intval($form2['id_mov'])==intval($id_mov)){
					// prendo tutti i movimenti di magazzino che hanno interessato il campo di coltivazione
					$n=0;$array=array();
					$query="SELECT ".'*'." FROM ".$gTables['movmag']. " WHERE campo_impianto ='". $campo_impianto."' AND operat ='-1' AND id_mov <> ".$form2['id_mov'];
					$result = gaz_dbi_query($query);
					while($row = $result->fetch_assoc()) {
						// cerco i giorni di sospensione del prodotto che si trovano in ogni movimento
						$artico= $row['artico'];
						$id_avversita=$row['id_avversita'];
						$id_colture=$row['id_colture'];
						$form3 =gaz_dbi_get_row($gTables['artico'], 'codice', $artico);
						$temp_sosp = $form3['tempo_sospensione'];
						// se è presente prendo il tempo di sospensione specifico altrimenti lascio quello generico
						$query2="SELECT ".'tempo_sosp'." FROM ".$gTables['camp_uso_fitofarmaci']. " WHERE cod_art ='". $artico ."' AND id_colt ='".$id_colture."' AND id_avv ='".$id_avversita."'";
						$result2 = gaz_dbi_query($query2);
						while ($row2 = $result2->fetch_assoc()) {
							$temp_sosp=$row2['tempo_sosp'];
						}
						// creo un array con tempo di sospensione + codice articolo + movimento magazzino
						$temp_deca=(intval($temp_sosp)*86400)+strtotime($row["datdoc"]);
						$array[$n]= array('temp_deca'=>$temp_deca,'datdoc'=>$row["datdoc"],'artico'=>$artico, 'id_mov'=>$row["id_mov"]);
						$n=$n+1;
					}
					// ordino l'array per tempo di sospensione
					rsort ($array);
					if (isset ($array[0]['temp_deca']) && $n>0) { // se c'è un tempo decadimento nei movimenti di magazzino e c'è almeno un movimento
						// aggiorno la tabella del campo di coltivazione con il movimento di magazzino che ha il decadimento più elevato
						$dt=date('Y/m/d', $array[0]['temp_deca']);
						$query="UPDATE " . $gTables['campi'] . " SET giorno_decadimento = '" . $dt .  "' , codice_prodotto_usato = '"  .$array[0]['artico']. "' , id_mov = '"  .$array[0]['id_mov'].  "' WHERE codice ='". intval($campo_impianto)."'";
						gaz_dbi_query ($query) ;
					} else { // in tutti gli altri casi
						// aggiorno la tabella del campo di coltivazione azzerando il decadimento e l'ID movimento che lo ha creato
						$query="UPDATE " . $gTables['campi'] . " SET giorno_decadimento = '" . "" .  "' , codice_prodotto_usato = '"  ."". "' , id_mov = '"  ."".  "' WHERE codice ='". intval($campo_impianto)."'";
						gaz_dbi_query ($query) ;
					}
				}
			}
			gaz_dbi_del_row($gTables['movmag'],"id_mov",$i);	// cancello il movimento di magazzino
			if (intval ($form['id_rif']) > 0 && intval ($form['id_rif'])<>intval($i)) {  //se il movimento di magazzino era connesso all'acqua
				gaz_dbi_del_row($gTables['movmag'],"id_mov",intval ($form['id_rif']));	// cancello il movimento di magazzino acqua
			}
		break;
		case "caumag":
			if (intval($_POST['ref']) > 80) {
				break;
			}
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['caumag'],"codice",$i);
		break;
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
	}
}
?>
