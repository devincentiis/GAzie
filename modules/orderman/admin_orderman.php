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
require ("../../library/include/datlib.inc.php");
require ("../../modules/magazz/lib.function.php");
require ("../../modules/vendit/lib.function.php");
require ("../../modules/camp/lib.function.php");
$admin_aziend = checkAdmin();
$msg = "";
$lm = new lotmag;
$magazz = new magazzForm();
$gForm = new ordermanForm();
$campsilos = new silos();
$warnmsg="";
$block_var="";
$submit="ok";
function gaz_select_data ( $nomecontrollo, $valore ) {
        $result_input = '<input size="8" type="text" id="'.$nomecontrollo.'" name="'.$nomecontrollo.'" value="'.$valore.'">';
        $result_input .= '<script>
        $(function () {
            $("#'.$nomecontrollo.'").datepicker({dateFormat: "dd-mm-yy", showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true})
        });</script>';
        return $result_input;
    }

function gaz_select_ora ( $nomecontrollo, $valore ) {
	$nomeora = $nomecontrollo."_ora";
	$nomeminuti = $nomecontrollo."_minuti";
	$valoreora = explode ( ":", $valore );

	$result_input = "<select name=\"".$nomeora."\" >\n";
	for ($counter = 0; $counter <= 23; $counter++) {
		$selected = "";
		if ($counter == $valoreora[0])
			$selected = ' selected=""';
		$result_input .=  "<option value=\"" . sprintf('%02d', $counter) . "\" $selected >" . sprintf('%02d', $counter) . "</option>\n";
	}
	$result_input .= "</select>\n ";
	// select dell'ora
	$result_input .= "<select name=\"".$nomeminuti."\" >\n";
	for ($counter = 0; $counter <= 59; $counter++) {
		$selected = "";
		if ($counter == $valoreora[1])
			$selected = ' selected=""';
		$result_input .= "<option value=\"" . sprintf('%02d', $counter) . "\" $selected >" . sprintf('%02d', $counter) . "</option>\n";
	}
	$result_input .= "</select>";
	return $result_input;
}

if (isset($_GET['popup'])) { //controllo se proviene da una richiesta apertura popup
    $popup = $_GET['popup'];
} else {
    $popup = "";
}
if (isset($_GET['type'])) { // controllo se proviene anche da una richiesta del modulo camp
    $form['order_type'] = substr($_GET['type'],0,3);
}
if ((isset($_POST['Update'])) or (isset($_GET['Update']))) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}
if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}
if ((isset($_GET['Update']) and !isset($_GET['codice'])) or isset($_POST['Return'])) {
    header("Location: " . $_POST['ritorno']);
    exit;
}
if ((isset($_POST['Insert'])) || (isset($_POST['Update']))){ // se NON è il primo accesso

  $form = gaz_dbi_parse_post('orderman');
  $form['order_type'] = $_POST['order_type'];
  $form['description'] = $_POST['description'];
  $form['add_info'] = $_POST['add_info'];
  $form['gioinp'] = intval($_POST['gioinp']);
  $form['mesinp'] = intval($_POST['mesinp']);
  $form['anninp'] = intval($_POST['anninp']);
  if ($form['anninp']<=0){
    $form['gioinp'] = date("d");
    $form['mesinp'] = date("m");
    $form['anninp'] = date("Y");
  }
  $form['id_tesbro'] = (isset($_POST['id_tesbro']))?$_POST['id_tesbro']:0;
  $form['iniprod'] = (isset($_POST['iniprod']))?$_POST['iniprod']:date ("d-m-Y");
  $form['iniprodtime'] = (isset($_POST['iniprodtime_ora']))?$_POST['iniprodtime_ora'].":".$_POST['iniprodtime_minuti']:date ("H:i");

  $form['fineprod'] = (isset($_POST['fineprod']))?$_POST['fineprod']:date ("d-m-Y");
  $form['fineprodtime'] = (isset($_POST['fineprodtime_ora']))?$_POST['fineprodtime_ora'].":".$_POST['fineprodtime_minuti']:date ("H:i");
  $form['day_of_validity'] = $_POST['day_of_validity'];
  $form["campo_impianto"] = $_POST["campo_impianto"];
  $form['quantip'] = $_POST['quantip'];
  $form['cosear'] = $_POST['cosear'];
  $form['codart'] = (isset($_POST['codart']))?$_POST['codart']:'';

  if (strlen ($_POST['cosear'])>0) {
		$resartico = gaz_dbi_get_row($gTables['artico'], "codice", $form['cosear']);
		$form['codart'] =($resartico)?$resartico['codice']:'';
	}  else {
		$resartico = gaz_dbi_get_row($gTables['artico'], "codice", $form['codart']);
	}
	if ($resartico) {
		$form['lot_or_serial'] = $resartico['lot_or_serial'];
		$form['SIAN'] = $resartico['SIAN'];
		$form['preacq'] = $resartico['preacq'];
		$form['quality'] = $resartico['quality'];
	} else {
		$form['lot_or_serial'] = '';
		$form['SIAN'] = '';
		$form['preacq'] = "";
		$form['quality'] = "";
	}
	$form['cod_operazione'] = $_POST['cod_operazione'];
  $form['recip_stocc'] = $_POST['recip_stocc'];
  $form['old_recip_stocc'] = $_POST['old_recip_stocc'];
  $form['id_mov_sian_rif'] = $_POST['id_mov_sian_rif'];
	$form['recip_stocc_destin'] = $_POST['recip_stocc_destin'];
	if (strlen($form['recip_stocc'])>0){ // se c'è un recipiente di stoccaggio prendo l'ID del lotto
    if ($form['recip_stocc']==$form['old_recip_stocc']){// se non è cambiato il contenitore d'origine e stiamo in update
      $excluded_movmag=$form['id_mov_sian_rif'];// faccio escludere il movimento dal calcolo disponibilità
    }else{
      $excluded_movmag=0;
    }

		$idlotrecip=$campsilos->getLotRecip($form['recip_stocc'],$form['codart'],intval($excluded_movmag) ); // è un array dove [0] è l'ID lotto e [1] è il numero lotto
		if ($form['cod_operazione']==5){ // se è una movimentazione interna SIAN limito la quantità a quella disponibile per l'ID lotto
			$qtaLotId = $lm -> dispLotID ($form['codart'], $idlotrecip[0], $excluded_movmag);
			if ($form['quantip']>$qtaLotId){
				$form['quantip']=$qtaLotId; $warnmsg.="42+";
			}
		}
		if (intval($form['cod_operazione'] >0 AND intval($form['cod_operazione'])<4)) { // se sono operazioni che producono olio confezionato
		   $var_orig = $campsilos->getContentSil($form['recip_stocc']);
			unset($var_orig['varieta']['totale']);//tolgo il totale
			$var=implode(", ",array_keys($var_orig['varieta']));// creo l'elenco varietà
			if ($form['quality'] !== $var){ // se le varietà del silos non coincidono con quelle della confezione
				$warnmsg.= "44+";
			}
		}
	}
	if ($resartico && $resartico['good_or_service'] == 2) { // se è un articolo composto
			// prendo i componenti che formerano l'articolo e l'unità di misura
			$where="codice_composizione = '" . $form['codart'] . "'";
			$table = $gTables['distinta_base']."
			LEFT JOIN ".$gTables['artico']." on (".$gTables['distinta_base'].".codice_artico_base = ".$gTables['artico'].".codice)";
      $rescompo = gaz_dbi_dyn_query ($gTables['distinta_base'].".*, ".$gTables['artico'].".*", $table, $where );
	}

	if (intval($form['SIAN'])>0){ // se è una produzione SIAN se la data di questa produzione è antecedente a quella dell'ultimo file SIAN
		$uldtfile=getLastSianDay();
		if (strtotime($_POST['datreg']) < strtotime($uldtfile)){
			$warnmsg.="40+";
		}
	}

  $form['coseor'] = (isset($_POST['coseor']))?$_POST['coseor']:0;
  $quantiprod = 0;
  if (intval($form['coseor']) > 0) { // se c'è un numero ordine lo importo tramite l'id
    $res = gaz_dbi_get_row($gTables['tesbro'], "id_tes", $form['coseor']);
    $form['order'] = $res['numdoc'];
    $form['id_tes'] = $res['id_tes'];
    if (isset($res)) { // se esiste veramente l'ordine ne prendo il rigo per l'articolo selezionato
      if (strlen($form['codart'])>0){//(se selezionato)
        $res2 = gaz_dbi_get_row($gTables['rigbro'], "id_tes", $res['id_tes'], "AND codart = '{$form['codart']}'");
        $form['quantipord'] = $res2['quanti'];
        $form['id_tesbro'] = $res['id_tes'];
        $form['id_rigbro'] = $res2['id_rig'];
    // prendo tutte le produzioni/orderman in cui c'è questo rigbro per conteggiare la quantità eventualmente già prodotta
        $query = "SELECT id FROM " . $gTables['orderman'] . " WHERE id_rigbro = '" . $res2['id_rig'] . "'";
        $resor = gaz_dbi_query($query);

        while ($row = $resor->fetch_assoc()) { // scorro tutte le produzioni/orderman trovate
            // per ogni orderman consulto movmag e conteggio le quantità per articolo già prodotte
            $rowmag = gaz_dbi_get_row($gTables['movmag'], "artico", $form['codart'], "AND operat = '1' AND id_orderman ='{$row['id']}'");
            $quantiprod = ($rowmag)?($quantiprod + $rowmag['quanti']):0;
        }

      }
    } else { // se l'ordine non esiste ed è stato inserito un numero anomalo
      $form['codart'] = "";
      $form['quantip'] = 0;
      $form['id_tesbro'] = 0;
      $form['id_rigbro'] = 0;
      $form['order'] = 0;
      $form['quantipord'] = 0;
    }
    if ($toDo == "update") { // se update importo il nome del cliente dell'ordine
      $res3 = gaz_dbi_get_row($gTables['clfoco'], "codice", $res['clfoco']);
    }
  } else {
    $form['id_tes'] ="";
    $form['id_tesbro'] = 0;
    $form['id_rigbro'] = 0;
    $form['order'] = 0;
    $form['quantipord'] = 0;
  }

  $form['filename'] = (isset($_POST['filename']))?$_POST['filename']:'';
  $form['identifier'] = (isset($_POST['identifier']))?$_POST['identifier']:'';
  $form['expiry'] = (isset($_POST['expiry']))?$_POST['expiry']:'';

  if (strlen($_POST['datreg']) > 0) {
      $form['datreg'] = $_POST['datreg'];
  } else {
      $form['datreg'] = date("Y-m-d");
  }
  $form['id_movmag'] = $_POST['id_movmag'];
  $form['id_lotmag'] = (isset($_POST['id_lotmag']))?$_POST['id_lotmag']:'';
  if (isset($_POST['numcomp'])) {
    $form['numcomp'] = $_POST['numcomp'];
    if ($form['numcomp'] > 0) {
      for ($m = 0;$m < $form['numcomp'];++$m) {
        if (isset($_POST['artcomp' . $m])){
          $form['artcomp'][$m] = $_POST['artcomp' . $m];
          $form['SIAN_comp'][$m] = $_POST['SIAN_comp' . $m];
          if ($form['SIAN_comp'][$m]==0){
            $_POST['recip_stocc_comp' . $m]="";
          }
          $form['quality_comp'][$m] = $_POST['quality_comp' . $m];
          $form['quanti_comp'][$m] = $_POST['quanti_comp' . $m];
          $form['prezzo_comp'][$m] = $_POST['prezzo_comp' . $m];
          $form['q_lot_comp'][$m] = $_POST['q_lot_comp' . $m];
          $form['id_mov'][$m]=$_POST['id_mov' . $m];
          $form['old_quanti_comp'][$m]=$_POST['old_quanti_comp' . $m];
          $form['recip_stocc_comp'][$m] = (isset($_POST['recip_stocc_comp' . $m]))?$_POST['recip_stocc_comp' . $m]:"";
          if (strlen($form['recip_stocc_comp'][$m])>0 AND intval($form['cod_operazione'] >0 AND intval($form['cod_operazione'])<4)) { // se sono operazioni che producono olio confezionato
            $var_orig = $campsilos->getContentSil($form['recip_stocc_comp'][$m]);
            unset($var_orig['varieta']['totale']);//tolgo il totale
            $var=implode(", ",array_keys($var_orig['varieta']));// creo l'elenco varietà
            if ($form['quality_comp'][$m] !== $var){ // se le varietà del silos non coincidono con quelle della confezione
              $warnmsg.= "44+";$block_var="SI";
            }
          }
          if (isset($_POST['subtLot'. $m]) AND $form['q_lot_comp'][$m]>1){
            $form['q_lot_comp'][$m]--;
          }
          if (isset($_POST['addLot'. $m])){
            $form['q_lot_comp'][$m]++;
          }
          if (isset($_POST['manLot'. $m])) {
            $form['amLot'. $m] = $_POST['manLot'. $m];
          } elseif (isset($_POST['autoLot'. $m])) {
            $form['amLot'. $m] = $_POST['autoLot'. $m];
          } else {
            $form['amLot'. $m] = (isset ($_POST['amLot'. $m]))?$_POST['amLot'. $m]:'';
          }
          $tot_lot_quanti[$m]=0;
          for ($n = 0;$n < $form['q_lot_comp'][$m];++$n) { // se q lot comp è zero vuol dire che non ci sono lotti
            $form['id_lot_comp'][$m][$n] = (isset($_POST['id_lot_comp' . $m . $n]))?$_POST['id_lot_comp' . $m . $n]:0;
            $form['lot_quanti'][$m][$n] = (isset($_POST['lot_quanti' . $m . $n]))?$_POST['lot_quanti' . $m . $n]:0;
            $form['lot_idmov'][$m][$n] = (isset($_POST['lot_idmov' . $m . $n]))?$_POST['lot_idmov' . $m . $n]:0;
            $tot_lot_quanti[$m] += $form['lot_quanti'][$m][$n];
          }
          if ($_POST['hidden_req'] == 'manualUpd' && floatval($form['quanti_comp'][$m])<>floatval($tot_lot_quanti[$m])){
             $msg .= "48+";
          }
        }
      }


    } else {
        $form['amLot0']="";
        $form['id_mov'][0]=0;
        $form['old_quanti_comp'][0]=0;
    }
  }

    // Se viene inviata la richiesta di conferma totale ... ******   CONTROLLO ERRORI   ******

    $form['datemi'] = $form['anninp'] . "-" . $form['mesinp'] . "-" . $form['gioinp'];
    if (isset($_POST['ins'])) {

      $itemart = gaz_dbi_get_row($gTables['artico'], "codice", $form['codart']);
      if ($form['codart'] <> "" && !isset($itemart)) { // controllo se codice articolo non esiste o se è nullo
          $msg.= "20+";
      }
      if ($itemart && $itemart['good_or_service'] == 2 && isset($form['numcomp'])) { // se articolo composto,
      //controllo se le quantità inserite per ogni singolo lotto, di ogni componente, corrispondono alla richiesta della produzione e alla reale disponbilità
        for ($nc = 0;$nc <= $form['numcomp'] - 1;++$nc) {
          if ($form['quanti_comp'][$nc] == "ERRORE"){
            $msg.= "43+";//Non c'è sufficiente disponibilità di un ID lotto selezionato
          }

          if (intval($form['q_lot_comp'][$nc])>0) {
            $tot=0;
            for ($l=0; $l<$form['q_lot_comp'][$nc]; ++$l) {
              if ($lm -> getLotQty($form['id_lot_comp'][$nc][$l],$form['lot_idmov'][$nc][$l]) < $form['lot_quanti'][$nc][$l]){
                $msg.= "21+";//Non c'è sufficiente disponibilità di un ID lotto selezionato
              }
              $tot=$tot + $form['lot_quanti'][$nc][$l];

              $checklot = gaz_dbi_get_row($gTables['lotmag']." LEFT JOIN ".$gTables['movmag']." ON ".$gTables['movmag'].".id_mov = id_movmag", 'id', $form['id_lot_comp'][$nc][$l]);
              if (strtotime($form['datreg']) < strtotime($checklot['datdoc']) ){// non può uscire un lotto prima della data della sua creazione
                $msg .= "45+";// Il lotto non può uscire in tale data in quanto ancora inesistente
              }
              //controllo se l'ID lotto è presente nel silos selezionato
              if (strlen($form['recip_stocc_comp'][$nc])>0){
                $var_idlot = $campsilos->getContentSil($form['recip_stocc_comp'][$nc]);
                unset($var_idlot['id_lotti']['totale']);//tolgo il totale
                $var=array_keys($var_idlot['id_lotti']);// creo array idlotti presenti nel silos
                if (!in_array($form['id_lot_comp'][$nc][$l], $var)){ // se l'id del lotto non è nel silos
                  $msg.= "47+";
                }
              }
            }
            if ($tot != $form['quanti_comp'][$nc] && strlen($form['recip_stocc_comp'][$nc])==0){// se c'è un silos la quantità totale viene stabilita da GAzie e, quindi, non si deve controllare
              //echo "Componente:",$form['artcomp'][$nc];
              //$msg.="25+";//La quantità inserita di un lotto, di un componente, è errata
              /* Antonio Germani - permetto di inserire quantità diverse dalla prevista, l'importante è che ci sia disponibilità (può servire per esaurire un lotto senza lasciarne decimali insignificanti)  */
            }
            if (intval($form['SIAN']) > 0 AND $form['SIAN_comp'][$nc] > 0 AND $campsilos -> getCont($form['recip_stocc_comp'][$nc]) < $form['quanti_comp'][$nc] AND intval($form['cod_operazione'])!==3){
              $msg.= "41+"; // il silos di origine non ha sufficiente quantità olio
            }

          }
        }
      }
        if ($form['order'] > 0) { // se c'è un numero ordine controllo che esista veramente l'ordine
            $itemord = gaz_dbi_get_row($gTables['tesbro'], "numdoc", $form['order']);
            if (!isset($itemord)) {
                $msg.= "23+";
                unset($itemord);
            }
            if (isset($_POST['okprod']) && $_POST['okprod'] <> "ok" && $toDo == "insert") {
                $msg.= "24+";
            }
        }
        if (empty($form['description'])) { //descrizione vuota
            // imposto la descrizione predefinita
            $descli=(isset($res3['descri']))?'/'.$res3['descri']:'';
          if (intval($form['coseor']) > 0){
            $form['description'] = "Produzione ".$form['codart']." ordine ".$form['coseor'].$descli;
          } else {
            $form['description'] = "Produzione ".$form['codart'];
          }
        }
        if (strlen($form['order_type']) < 3) { //tipo produzione vuota
            $msg.= "12+";
        }

        if ($form['order_type'] == "IND") { // in produzione industriale
            if (strlen($form['codart']) == 0) { // articolo vuoto
                $msg.= "16+";
            }

            if ($form['quantip'] == 0 || $form['quantip']=="" ) { // quantità produzione vuota
                $msg.= "17+";
            }

            if (intval($form['datreg']) == 0) { // se manca la data di registrazione
                $msg.= "22+";
            }
          if (intval($form['SIAN']) > 0 ){ // controlli SIAN
            if (intval($form['cod_operazione'])<1) { // se manca il codice operazione SIAN
              $msg.= "26+";
            }
            if (intval($form['cod_operazione'])==5){ // se M1 , movimentazione interna olio sfuso
              if (strlen ($form['recip_stocc_destin']) == 0 ) { // se M1 e manca il recipiente di destinazione
                $msg.= "27+";
              }
              if ($form['recip_stocc_destin']==$form['recip_stocc']) { // se M1 e i recipienti sono uguali
                $msg.= "28+";
              }
              $get_sil=gaz_dbi_get_row($gTables['camp_recip_stocc'],'cod_silos',$form['recip_stocc_destin']);
			  $excluded_movmag_dest=($toDo == 'update')?$form['id_movmag']:0;
              if ($campsilos -> getCont($form['recip_stocc_destin'],'', $excluded_movmag_dest)+$form['quantip'] > $get_sil['capacita']){// se non c'è spazio sufficiente nel recipiente di destinazione
                $msg.= "46+";
              }
            }
            if (intval($form['cod_operazione'])==3) { // se L2 l'olio prodotto può essere solo etichettato
              $rescampartico = gaz_dbi_get_row($gTables['camp_artico'], "codice", $form['codart']);
              if ($rescampartico['etichetta']==0){
                $msg.= "30+";
              }
            }
            if ($toDo == 'insert' AND (intval($form['cod_operazione'])>0 AND intval($form['cod_operazione'])<3 AND $form['numcomp']==0)){ // se confezioniamo
              $msg.= "39+"; // manca l'olio sfuso
            }
            if (intval($form['cod_operazione']>0 AND intval($form['cod_operazione'])<4)) { // se sono operazioni che producono olio confezionato
              $rescampartico = gaz_dbi_get_row($gTables['camp_artico'], "codice", $form['codart']);
              if ($rescampartico['confezione']==0){ // se l'olio è sfuso segnalo l'errore
                $msg.= "37+";
              }
            }
            if (intval($form['cod_operazione']>0 AND intval($form['cod_operazione'])<3)) { // se sono operazioni che prelevano l'olio da recipiente
              for ($x = 0; $x < $form['numcomp']; $x++){ // controllo se ci sono i recipienti di stoccaggio per ogni componente
                if ($form['SIAN_comp'][$x]==1 && strlen($form['recip_stocc_comp'][$x])==0){
                  $msg.= "38+";
                }
              }
            }
          }

          if ($toDo == 'insert' AND intval($form['SIAN']) > 0 AND (isset($form['numcomp']) AND $form['numcomp']>0)) { // se ci sono componenti faccio il controllo errori SIAN sui componenti
              for ($m = 0;$m < $form['numcomp'];++$m) {
              $rescamparticocomp = gaz_dbi_get_row($gTables['camp_artico'], "codice", $form['artcomp'][$m]);
              if (isset($rescamparticocomp)){
                if (intval($form['cod_operazione'])==3 AND $rescamparticocomp['confezione']==0 ) { // se L2 etichettatura e c'è olio sfuso
                  $msg.= "29+";
                }
                if (intval($form['cod_operazione'])==3 AND $rescamparticocomp['etichetta']==1 ) { // se L2 etichettatura e c'è olio etichettato
                  $msg.= "32+";
                }
                if (intval($form['cod_operazione'])==3 AND ($rescamparticocomp['categoria']!== $rescampartico['categoria'] OR $rescamparticocomp['or_macro']!== $rescampartico['or_macro'] OR $rescamparticocomp['estrazione']!== $rescampartico['estrazione'] OR $rescamparticocomp['biologico']!== $rescampartico['biologico'] OR $rescamparticocomp['confezione']!== $rescampartico['confezione'] )) { // se L2 etichettatura e c'è olio non etichettato
                  $msg.= "31+";
                }
                if ($rescamparticocomp['id_campartico']>0 AND strlen($form['recip_stocc_comp'][$m])==0 AND (intval($form['cod_operazione'])>0 AND intval($form['cod_operazione'])<3)){
                $msg.= "38+";
                }
              }
            }
          }
        }// fine se in produzione industriale
        if ($msg == "") { // nessun errore
          // Antonio Germani >>>> inizio SCRITTURA dei database    §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§
          // echo"<pre>",print_r($form);die;
          $start_work = date_format(date_create_from_format('d-m-Y', $form['iniprod']), 'Y-m-d')." ".$form['iniprodtime'];
          $end_work = date_format(date_create_from_format('d-m-Y', $form['fineprod']), 'Y-m-d')." ".$form['fineprodtime'];
            // i dati dell'articolo che non sono nel form li avrò nell' array $resartico
          $form['quantip']=gaz_format_quantity($form['quantip']);// trasformo la quantità per salvarla nel database

          if ($toDo == "update") { // se è un update cancello tutto il vecchio tranne il rigo di orderman
            foreach (glob("../../modules/orderman/tmp/*") as $fn) {// cancello eventuali precedenti file temporanei nella cartella tmp
              unlink($fn);
            }
            $res = gaz_dbi_get_row($gTables['tesbro'],"id_tes",$form['id_tesbro']); // prendo il rigo di tesbro interessato

            if (strlen($form['identifier']) > 0) {// prendo il riferimento al vecchio lotto dell'articolo prodotto
              $resin = gaz_dbi_get_row($gTables['orderman'], "id", intval($_GET['codice']));
              $resin2 = gaz_dbi_get_row($gTables['lotmag'], "id", $resin['id_lotmag']);
            }

            // prendo tutti i movimenti di magazzino a cui fa riferimento la produzione
            $what=$gTables['movmag'].".id_mov ";
            $table=$gTables['movmag'];
            $where="id_orderman = ".intval($_GET['codice']);
            $resmov=gaz_dbi_dyn_query ($what,$table,$where);
            while ($r = gaz_dbi_fetch_array($resmov)) {
              gaz_dbi_del_row($gTables['movmag'], "id_mov", $r['id_mov']);// cancello i relativi movimenti di magazzino movmag

              gaz_dbi_del_row($gTables['camp_mov_sian'], "id_movmag", $r['id_mov']);// cancello i relativi movimenti SIAN
            }

			if (intval($res['clfoco'])==0) { // se NON è un ordine cliente esistente e quindi fu generato automaticamente da orderman
			$result = gaz_dbi_del_row($gTables['tesbro'], "id_tes", $form['id_tesbro']); // cancello tesbro
			$result = gaz_dbi_del_row($gTables['rigbro'], "id_tes", $form['id_tesbro']); // cancello rigbro
			$form['order']=0;
			} else { // se invece è un ordine cliente devo lasciarlo e solo sganciarlo da orderman
			gaz_dbi_query ("UPDATE " . $gTables['tesbro'] . " SET id_orderman = '' WHERE id_tes ='".$form['id_tesbro']."'") ; // sgancio tesbro da orderman
			}

            // in ogni caso riporto l'auto_increment all'ultimo valore disponibile
            $query="SELECT max(id)+1 AS li FROM ".$gTables['orderman'];
            $last_autincr=gaz_dbi_query($query);
            $li=gaz_dbi_fetch_array($last_autincr);
            $li=(isset($li['id']))?($li['id']+1):1;
            $query="ALTER TABLE ".$gTables['orderman']." AUTO_INCREMENT=".$li;
            gaz_dbi_query($query); // riporto l'auto_increment al primo disponibile per non avere vuoti di numerazione

          }
            if (intval($form['order']) > 0) { // se c'è un ordine prendo gli id tesbro e rigbro esistenti nel form
                $id_tesbro = $form['id_tesbro'];
                $id_rigbro = $form['id_rigbro'];
            }


          if ($form['order_type'] == "AGR" or $form['order_type'] == "RIC" or $form['order_type'] == "PRF") {
              // escludo AGR RIC e PRF dal creare movimento di magazzino e lotti
              $id_movmag="";
          }

          // INSERISCO
          // creo e salvo ORDERMAN, tesbro e rigbro
            $status=0;
            if (intval($form['order']) <= 0) { // se non c'è un numero ordine ne creo uno fittizio in TESBRO e RIGBRO
              if (($form['order_type'] != "AGR") OR ($form['order_type'] == "AGR" AND strlen($form['codart'])>0)) { // le produzioni agricole creano un ordine fittizio solo se c'è un articolo
                $id_tesbro=tesbroInsert(array('tipdoc'=>'PRO','datemi'=>$form['datemi'],'numdoc'=>time(),'status'=>'AUTOGENERA','adminid'=>$admin_aziend['adminid']));
              }
              if ($form['order_type'] == "IND") { $status=9; } // una produzione industriale senza ordine a riferimento la chiudo perché prodotto per stoccaggio in magazzino
            } else { // se c'è l'ordine lo collego ad orderman
              tesbroUpdate(array('id_tes',$form['id_tesbro']), array('id_orderman'=>$id_orderman));

              // usando i registri valorizzati per il form determino se devo mettere la produzione nello stato "9-chiuso" o lasciarla aperta
              if (($quantiprod+$form['quantip'])>=$form['quantipord']) {  // ho prodotto di più o uguale a quanto richiesto dall'ordine specificato
                  $res = gaz_dbi_get_row($gTables['tesbro'], "id_tes", $form['coseor']);
                  $res2 = gaz_dbi_get_row($gTables['rigbro'], "id_tes", $res['id_tes'], "AND codart = '".$form['codart']."'");
                  // prendo tutte le produzioni/orderman in cui c'è questo rigbro per conteggiare la quantità eventualmente già prodotta
                  $query = "SELECT id FROM " . $gTables['orderman'] . " WHERE id_rigbro = " . $res2['id_rig'];
                  $resor = gaz_dbi_query($query);
                  while ($row = $resor->fetch_assoc()) { // scorro tutte le produzioni/orderman trovate
                      // su ogni orderman precedente cambio lo stato
                      gaz_dbi_query("UPDATE " . $gTables['orderman'] . " SET stato_lavorazione = 9 WHERE id = " . $row['id']);
                  }
                  $status=9;
              }
            }
          // inserisco orderman: l'attuale produzione
            $form['start_work']=$start_work; $form['end_work']=$end_work; $form['id_tesbro']=(isset($id_tesbro))?$id_tesbro:0; $form['stato_lavorazione']=$status; $form['adminid']=$admin_aziend['adminid']; $form['duration']=$form['day_of_validity'];

			if (isset($_GET['codice']) && intval($_GET['codice'])>0){// se è un update aggiorno rigo orderman
				$id_orderman=intval($_GET['codice']);
				$update = array();
				$update[]="id";
				$update[]=$id_orderman;
				gaz_dbi_table_update('orderman', $update , $form);
			}else{// se è insert

				$id_orderman = gaz_dbi_table_insert('orderman', $form);
			}
            if (isset($id_tesbro)){// connetto tesbro a orderman
              tesbroUpdate(array('id_tes',$id_tesbro), array('id_orderman'=>$id_orderman));
            }

            //
            // scrittura movimento di magazzino MOVMAG carico dell'articolo prodotto
            if ($form['order_type'] == "IND") {
              $mv = $magazz->getStockValue(false, $form['codart'], null, null, $admin_aziend['decimal_price']);
              $price=(isset($mv['v']))?$mv['v']:0;
              if (!isset($mv['v']) OR $mv['v']==0){// se getStockValue non mi ha restituito il prezzo allora lo prendo dal prezzo di default
                $price=(isset($row['preacq']))?$row['preacq']:0;
              }
              $id_movmag=$magazz->uploadMag('0', 'PRO', '', '', $form['datemi'], '', '', '82', $form['codart'], $form['quantip'], $price, '', 0, $admin_aziend['stock_eval_method'], array('datreg' => $form['datreg'], 'operat' => '1', 'desdoc' => 'Produzione'), 0, (isset($id_lotmag))?$id_lotmag:0, $id_orderman, $form['campo_impianto']);
              $prod_id_movmag=$id_movmag; // mi tengo l'id_movmag del movimento di magazzino di entrata da produzione, mi servirà successivamente per valorizzare il prezzo in base alla composizione ed anche in caso di SIAN
              if ($form['SIAN']>0){ // imposto l'id movmag e salvo il movimento SIAN dell'articolo prodotto
                $form['id_movmag']=$id_movmag;
                if ($form['cod_operazione']==5){ // Movimentazione interna senza cambio di origine
                  $change=$form['recip_stocc'];// scambio i recipienti
                  $form['recip_stocc']=$form['recip_stocc_destin'];
                  $var_orig=$campsilos->getContentSil($form['recip_stocc'],$date="",$id_mov=0);
                  unset($var_orig['varieta']['totale']);//tolgo il totale
                  $form['recip_stocc_destin']=$change;
                  $var_dest=$campsilos->getContentSil($form['recip_stocc_destin'],$date="",$id_mov=0);
                  unset($var_dest['varieta']['totale']);//tolgo il totale
                  if (count($var_dest['varieta'])>0 && count($var_orig['varieta'])>0 && $block_var!=="SI"){
                    $form['varieta'] = "Traferimento olio ";
                    if (count($var_dest['varieta'])>0){
                      $form['varieta'] .= "varietà ". implode(", ",array_keys($var_dest['varieta']));
                    }
                    if (count($var_orig['varieta'])>0){
                      $form['varieta'] .= " al recipiente contenente varietà ". implode(", ",array_keys($var_orig['varieta']));
                    }
                  }
                }elseif ($block_var!=="SI") {
                  $form['varieta']=$form['quality'];
                }
                $id_mov_sian_rif=gaz_dbi_table_insert('camp_mov_sian', $form);
                $s7=""; // Si sta producendo olio
              } else {
                $s7=1; // Non si produce olio cioè l'articolo finito non è olio
                $id_mov_sian_rif="";
              }
            }
            if ($form['cod_operazione']==5){ // se è una movimentazione interna SIAN creo un movimento di magazzino in uscita per far riportare la giacenza
              // inserisco il movimento di magazzino dell'articolo in uscita
              $id_movmag=$magazz->uploadMag('0', 'PRO', '', '', $form['datemi'], '', '', '81', $form['codart'], $form['quantip'], $form['preacq'], '', 0, $admin_aziend['stock_eval_method'], array('datreg' => $form['datreg'], 'operat' => '-1', 'desdoc' => 'Movimentazione interna'), 0, $idlotrecip[0], $id_orderman, $form['campo_impianto']);

              // e creo anche il relativo movimento SIAN
              $form['id_movmag']=$id_movmag;
              $form['cod_operazione']="";
              $change=$form['recip_stocc']; // scambio di nuovo i recipienti
              $form['recip_stocc']=$form['recip_stocc_destin'];
              $var_orig=$campsilos->getContentSil($form['recip_stocc'],$date="",$id_mov=0);
              unset($var_orig['varieta']['totale']);//tolgo il totale
              $form['recip_stocc_destin']=$change;
              $var_dest=$campsilos->getContentSil($form['recip_stocc_destin'],$date="",$id_mov=0);
              unset($var_dest['varieta']['totale']);//tolgo il totale
              if (count($var_dest['varieta'])>0 && count($var_orig['varieta'])>0 && $block_var!=="SI"){
                $form['varieta'] = "Traferimento olio ";
                if (count($var_orig['varieta'])>0){
                    $form['varieta'] .= "varietà ". implode(", ",array_keys($var_orig['varieta']));
                  }
                if (count($var_dest['varieta'])>0){
                  $form['varieta'] .= " al recipiente contenente varietà ". implode(", ",array_keys($var_dest['varieta']));
                }
              }
              $form['id_mov_sian_rif']=$id_mov_sian_rif;
              gaz_dbi_table_insert('camp_mov_sian', $form);
              $form['id_movmag']=$prod_id_movmag;// reimposto l'id_movmag del movimento di entrata
              $id_movmag=$form['id_movmag'];
            }
            if ($itemart && $itemart['good_or_service'] == 2) { // se è un articolo composto
              $comp_total_val=0.00;
              for ($nc = 0;$nc <= $form['numcomp'] - 1;++$nc) { // *** faccio un ciclo con tutti i componenti da scaricare   ***
                // accumulo il valore dei singoli componenti, mi servirà a fine ciclo per valorizzare il movimento 'PRO' precedentemente inserito

                $comp_total_val += $form['quanti_comp'][$nc]*$form['prezzo_comp'][$nc]/$form['quantip'];
                if ($form['q_lot_comp'][$nc] > 0) { // se il componente ha lotti
                  for ($n = 0;$n < $form['q_lot_comp'][$nc];++$n) { //faccio un ciclo con i lotti di ogni singolo componente
                    if ($form['lot_quanti'][$nc][$n]>0){ // questo evita che, se è stato forzato un lotto a quantità zero, venga generato un  movimento di magazzino
                      // Scarico dal magazzino il componente usato e i suoi lotti
                      $id_mag=$magazz->uploadMag('0', 'PRO', '', '', $form['datemi'], '', '', '81', $form['artcomp'][$nc], $form['lot_quanti'][$nc][$n], $form['prezzo_comp'][$nc], '', 0, $admin_aziend['stock_eval_method'], array('datreg' => $form['datreg'], 'operat' => '-1', 'desdoc' => 'Scarico per Produzione con lotto'), 0, $form['id_lot_comp'][$nc][$n], $id_orderman, $form['campo_impianto']);

                      if ($form['SIAN_comp'][$nc]>0){ // imposto l'id movmag e creo il movimento SIAN del componente usato, se previsto
                        $form['id_movmag']=$id_mag;
                        $form['id_mov_sian_rif']=$id_mov_sian_rif; // connetto il mov sian del componente a quello del prodotto
                        $form['recip_stocc']=$form['recip_stocc_comp'][$nc];
                        gaz_dbi_query("UPDATE " . $gTables['camp_mov_sian'] . " SET recip_stocc = '" . $form['recip_stocc'] . "' WHERE id_mov_sian ='" . $id_mov_sian_rif . "'"); // aggiorno id_lotmag sul movmag
                        $form['cod_operazione']="";
                        $var_orig=$campsilos->getContentSil($form['recip_stocc'],$date="",$id_mov=0);
                        unset($var_orig['varieta']['totale']);//tolgo il totale
                        if (isset($var_orig) && $block_var!=="SI"){
                          $form['varieta'] = implode(", ",array_keys($var_orig['varieta']));
                        }
                        if ($s7==1){ // S7 è uno scarico di olio destinato ad altri consumi
                          $form['cod_operazione']="S7";
                        }
                        gaz_dbi_table_insert('camp_mov_sian', $form);
                      }
                    }
                  }
                } else { // se il componente non ha lotti scarico semplicemente il componente dal magazzino
                  // Scarico il magazzino con l'articolo usato
                  $id_mag=$magazz->uploadMag('0', 'PRO', '', '', $form['datemi'], '', '', '81', $form['artcomp'][$nc], $form['quanti_comp'][$nc], $form['prezzo_comp'][$nc], '', 0, $admin_aziend['stock_eval_method'], array('datreg' => $form['datreg'], 'operat' => '-1', 'desdoc' => 'Scarico per Produzione senza lotto'), 0, '', $id_orderman, $form['campo_impianto']);

                  if ($form['SIAN_comp'][$nc]>0){ // imposto l'id movmag e salvo il movimento SIAN del componente usato, se previsto
                    $form['id_movmag']=$id_mag;
                    $form['id_mov_sian_rif']=$id_mov_sian_rif;// connetto il mov sian del componente a quello del prodotto
                    $form['recip_stocc']=$form['recip_stocc_comp'][$nc];
                    gaz_dbi_query("UPDATE " . $gTables['camp_mov_sian'] . " SET recip_stocc = '" . $form['recip_stocc'] . "' WHERE id_mov_sian ='" . $id_mov_sian_rif . "'"); // aggiorno id_lotmag sul movmag
                    $form['cod_operazione']="";
                    $var_orig=$campsilos->getContentSil($form['recip_stocc'],$date="",$id_mov=0);
                    unset($var_orig['varieta']['totale']);//tolgo il totale
                    if (isset($var_orig) && $block_var!=="SI"){
                      $form['varieta'] = implode(", ",array_keys($var_orig['varieta']));
                    }
                    if ($s7==1){ // S7 è uno scarico di olio destinato ad altri consumi
                      $form['cod_operazione']="S7";
                    }
                    gaz_dbi_table_insert('camp_mov_sian', $form);
                  }
                }
              }
              if ($comp_total_val>0){// se è valorizzato, aggiorno il prezzo del movimento di produzione sulla base del prezzo dei componenti su movmag altrimenti lascio il valore di getStockValue or di preacq precedentemente inserito
                gaz_dbi_query("UPDATE " . $gTables['movmag'] . " SET prezzo = " . round($comp_total_val,5) . " WHERE id_mov = " . $prod_id_movmag);
              }
              $form['id_movmag']=$id_movmag;
            }
            if (intval($form['order']) <= 0 && isset($id_tesbro)) {// se non c'è l'ordine vero e devo creare quello fittizio
              //inserisco il rigo ordine rigbro
              if (!$resartico) {
                $resartico = ['descri'=>'','unimis'=>''];
              }
              if (!isset($id_movmag)) {
                $id_movmag=0;
              }
              $id_rigbro = rigbroInsert(array('id_tes'=>$id_tesbro,'codart'=>$form['codart'],'descri'=>$resartico['descri'],'unimis'=>$resartico['unimis'],'quanti'=>$form['quantip'],'id_mag'=>$id_movmag,'status'=>'AUTOGENERA','id_orderman'=>$id_orderman));
            }
            if (isset($id_rigbro)&& $form['order_type'] == "IND"){// connetto movmag dell'articolo prodotto al suo rigbro
              movmagUpdate(array('id_mov',$prod_id_movmag), array('id_rif'=>$id_rigbro));
            }

          $id_lotmag="";
          //Antonio Germani - > inizio LOTTO ARTICOLO PRODOTTO, se c'è lotto e se il prodotto lo richiede
          if ($form['lot_or_serial'] > 0) { // se l'articolo prodotto prevede un lotto

            // ripulisco il numero lotto inserito da caratteri dannosi
            $form['identifier'] = (empty($form['identifier'])) ? '' : filter_var($form['identifier'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            if (strlen($form['identifier']) == 0) { // se non c'è il lotto lo inserisco con data e ora in automatico
              $form['identifier'] = date("Ymd Hms");
            }
            if (strlen($form['expiry']) == 0) { // se non c'è la scadenza la inserisco a zero in automatico
              $form['expiry'] = "0000-00-00 00:00:00";
            }
            // è un nuovo INSERT
            if (strlen($form['identifier']) > 0 && $toDo == "insert") {
              //inserisco il nuovo id lotto in lotmag e movmag. Ogni produzione di Orderman deve avere un lotto diverso
              gaz_dbi_query("INSERT INTO " . $gTables['lotmag'] . "(codart,id_movmag,identifier,expiry) VALUES ('" . $form['codart'] . "','" . $id_movmag . "','" . $form['identifier'] . "','" . $form['expiry'] . "')");
              $id_lotmag = gaz_dbi_last_id();
              gaz_dbi_query("UPDATE " . $gTables['movmag'] . " SET id_lotmag = '" . $id_lotmag . "' WHERE id_mov ='" . $prod_id_movmag . "'"); // aggiorno id_lotmag sul movmag
            }
            //  è un UPDATE
            if (strlen($form['identifier']) > 0 && $toDo == "update" && $form['order_type'] == "IND") {
              if ($resin2['identifier'] == $form['identifier']) { // se ha lo stesso numero di lotto di quello precedentemente salvato faccio update di lotmag
                gaz_dbi_query("UPDATE " . $gTables['lotmag'] . " SET codart = '" . $form['codart'] . "' , id_movmag = '" . $prod_id_movmag . "' , identifier = '" . $form['identifier'] . "' , expiry = '" . $form['expiry'] . "' WHERE id = '" . $form['id_lotmag'] . "'");
                $id_lotmag = $form['id_lotmag'];
              } else { // se non è lo stesso numero, cancello il lotto iniziale e ne creo uno nuovo
                gaz_dbi_query("DELETE FROM " . $gTables['lotmag'] . " WHERE id = " . $resin['id_lotmag']);
                gaz_dbi_query("INSERT INTO " . $gTables['lotmag'] . "(codart,id_movmag,identifier,expiry) VALUES ('" . $form['codart'] . "','" . $prod_id_movmag . "','" . $form['identifier'] . "','" . $form['expiry'] . "')");
                $id_lotmag = gaz_dbi_last_id(); // vedo dove è stato salvato lotmag
              }
              // aggiorno id_lotmag dell'articolo prodotto su movmag
              gaz_dbi_query("UPDATE " . $gTables['movmag'] . " SET id_lotmag = '" . $id_lotmag . "' WHERE id_mov ='" . $prod_id_movmag . "'");

            }
          }
          // Antonio Germani - inizio salvo documento/CERTIFICATO lotto
          if (substr($form['filename'], 0, 7) <> 'lotmag_') { // se è stato cambiato il file, cioè il nome non inizia con lotmag e, quindi, anche se è un nuovo insert
            if (!empty($form['filename'])) { // e se ha un nome impostato nel form
              $tmp_file = DATA_DIR."files/tmp/" . $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'] . '_' . $form['filename'];
              // sposto il file nella cartella definitiva, rinominandolo e cancellandolo dalla temporanea
              $fd = pathinfo($form['filename']);
              rename($tmp_file, DATA_DIR."files/" . $admin_aziend['company_id'] . "/lotmag_" . $id_lotmag . '.' . $fd['extension']);
            }
          } // altrimenti se il file non è cambiato, anche se è update, non faccio nulla
          // <<< fine salvo lotti

          if (isset($id_rigbro)){// connetto orderman a rigbro e al lotto
            gaz_dbi_query("UPDATE " . $gTables['orderman'] . " SET id_rigbro = '".$id_rigbro."', id_lotmag = '".$id_lotmag."' WHERE id = '" . $id_orderman."'");
          }
          // se sono in un popup lo chiudo dopo aver salvato tutto
          if ($popup == 1) {
                    echo "<script>
            window.opener.location.reload(true);
            window.close();</script>";
          } else {
              header("Location: orderman_report.php");
          }
          exit;
        }
    }
    //  fine scrittura database

} elseif ((!isset($_POST['Update'])) and (isset($_GET['Update']))) {//   se e' il primo accesso per UPDATE
  if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname'])){
		// allineo l'e-commerce con eventuali ordini non ancora caricati
		$gs=$admin_aziend['synccommerce_classname'];
		$gSync = new $gs();
		if($gSync->api_token){
			$gSync->get_sync_status(0);
		}
	}
	$result = gaz_dbi_get_row($gTables['orderman'], "id", intval($_GET['codice']));
	$form['ritorno'] = $_POST['ritorno'];
	$form['id'] = intval($_GET['codice']);
	$form['order_type'] = $result['order_type'];
	$form['description'] = $result['description'];
	$form['id_tesbro'] = $result['id_tesbro'];
	$form['id_rigbro'] = $result['id_rigbro'];
	$form['add_info'] = $result['add_info'];
	$form['day_of_validity'] = $result['duration'];
  $form['iniprod'] = '';
  $form['iniprodtime'] = '00:00';
	if (is_string($result['start_work'])){
    $s = strtotime($result['start_work']);
    $form['iniprod'] = date('d-m-Y', $s);
    $form['iniprodtime'] = date('H:i', $s);
  }
	$form['fineprod'] = '';
	$form['fineprodtime'] = '00:00';
	if (is_string($result['end_work'])){
    $s=strtotime($result['end_work']);
    $form['fineprod'] = date('d-m-Y', $s);
    $form['fineprodtime'] = date('H:i', $s);
  }
	$result4 = gaz_dbi_get_row($gTables['movmag'], "id_orderman", intval($_GET['codice']), "AND operat ='1'");
	$form['datreg'] = ($result4)?$result4['datreg']:'';
	$form['quantip'] = ($result4)?$result4['quanti']:0;
	$form['id_movmag'] = ($result4)?$result4['id_mov']:0;
	$resmov_sian = gaz_dbi_get_row($gTables['camp_mov_sian'], "id_movmag", $form['id_movmag']);
  $resmov_sian_connected = gaz_dbi_get_row($gTables['camp_mov_sian'], "id_mov_sian_rif", $resmov_sian['id_mov_sian']);
  $form['id_mov_sian_rif'] =($resmov_sian_connected)?$resmov_sian_connected['id_movmag']:'';
	$form['cod_operazione'] =($resmov_sian)?$resmov_sian['cod_operazione']:'';
	$form['recip_stocc'] =($resmov_sian)?$resmov_sian['recip_stocc']:'';
	$form['recip_stocc_destin'] =($resmov_sian)?$resmov_sian['recip_stocc_destin']:'';
	if ($resmov_sian){// Controllo se è una movimentazione interna olio
		$result6 = gaz_dbi_get_row($gTables['movmag'], "id_orderman", intval($_GET['codice']), "AND operat ='-1'");
		if($result6){// se lo è prendo i recipienti di stoccaggio corretti
			$resmov_sian = gaz_dbi_get_row($gTables['camp_mov_sian'], "id_movmag", $result6['id_mov']);
			$form['recip_stocc'] =($resmov_sian)?$resmov_sian['recip_stocc']:'';
			$form['recip_stocc_destin'] =($resmov_sian)?$resmov_sian['recip_stocc_destin']:'';
		}
	}
  $form['old_recip_stocc']=$form['recip_stocc']; // il contenitore di origine precedentemente inserito
	$result2 = gaz_dbi_get_row($gTables['tesbro'], "id_tes", $result['id_tesbro']);
	$form['gioinp'] = substr(($result2)?$result2['datemi']:'', 8, 2);
	$form['mesinp'] = substr(($result2)?$result2['datemi']:'', 5, 2);
	$form['anninp'] = substr(($result2)?$result2['datemi']:'', 0, 4);
	$form['datemi'] = ($result2)?$result2['datemi']:'';
	$form['campo_impianto'] = $result['campo_impianto'];
	$form['id_lotmag'] = $result['id_lotmag'];
	$form['order'] = ($result2)?$result2['numdoc']:0;
	if (isset($result2['clfoco'])){
		$res3 = gaz_dbi_get_row($gTables['clfoco'], "codice", $result2['clfoco']);// importo il nome del cliente dell'ordine
	}
	$form['coseor'] = ($result2)?$result2['id_tes']:0;
	$form['id_tes'] = ($result2)?$result2['id_tes']:0;
	$result3 = gaz_dbi_get_row($gTables['rigbro'], "id_rig", $result['id_rigbro']);
	$form['codart'] = ($result3)?$result3['codart']:'';
	$form['quantipord'] = ($result3)?$result3['quanti']:0;
	$quantiprod=$form['quantipord'];
	$result5 = gaz_dbi_get_row($gTables['lotmag'], "id", $result['id_lotmag']);
	$form['identifier'] =($result5)?$result5['identifier']:'';
	$form['expiry'] =($result5)?$result5['expiry']:'';
	$resartico = gaz_dbi_get_row($gTables['artico'], "codice", $form['codart']);
	if ($resartico){
		$form['lot_or_serial'] = $resartico['lot_or_serial'];
		$form['SIAN'] = $resartico['SIAN'];
	} else {
		$form['lot_or_serial'] = '';
		$form['SIAN'] = '';
		$resartico=array('unimis'=>'','lot_or_serial'=>'','good_or_service'=>'');
	}
	if ($resartico && $resartico['good_or_service'] == 2) { // se è un articolo composto
			 // prendo i movimenti di magazzino dei componenti e l'unità di misura
			$where="operat = '-1' AND id_orderman = ". intval($_GET['codice']);
			$table = $gTables['movmag']." LEFT JOIN ".$gTables['artico']." on (".$gTables['movmag'].".artico = ".$gTables['artico'].".codice)
      LEFT JOIN ".$gTables['camp_mov_sian']." on (".$gTables['camp_mov_sian'].".id_movmag = ".$gTables['movmag'].".id_mov)
      ";
			$result7 = gaz_dbi_dyn_query ($gTables['movmag'].".*, ".$gTables['artico'].".*, ".$gTables['camp_mov_sian'].".*", $table, $where,"id_mov ASC" );

      // prendo i componenti che formano l'articolo e l'unità di misura
			$where="codice_composizione = '" . $form['codart'] . "'";
			$table = $gTables['distinta_base']."
			LEFT JOIN ".$gTables['artico']." on (".$gTables['distinta_base'].".codice_artico_base = ".$gTables['artico'].".codice)";
      $rescompo = gaz_dbi_dyn_query ($gTables['distinta_base'].".*, ".$gTables['artico'].".*", $table, $where );
	}

	if (isset($result7)) {// se ci sono stati componenti carico i righi dei componenti
      $m = 0;$form['artcomp']=array();
      while ($row = $result7->fetch_assoc()) {

        if(in_array($row['artico'], $form['artcomp'])) {// controllo se ci sono più lotti per stesso componente

            $n++;
            $nn=0;
            foreach ($form['artcomp'] as $artc){// ciclo per ritrovare in che posizione sta nell'array
              if($artc == $row['artico']){
                $form['id_lot_comp'][$nn][$n]= $row['id_lotmag'];
                $form['lot_quanti'][$nn][$n] = $row['quanti'];// quantità di questo lotto
                $form['lot_idmov'][$nn][$n] = $row['id_mov'];
                $form['q_lot_comp'][$nn] = $n+1;// quanti lotti per questo componente
                $form['quanti_comp'][$nn] = $form['quanti_comp'][$nn]+$row['quanti'];//
                $form['old_quanti_comp'][$nn] = $form['old_quanti_comp'][$nn]+$row['quanti'];
              }
              $nn++;// aumento posizione
            }
        } else {

          $n=0;
          $form['artcomp'][$m] = $row['artico'];
          $form['SIAN_comp'][$m] = $row['SIAN'];
          $form['quality_comp'][$m] = $row['quality'];
          $form['quanti_comp'][$m] = $row['quanti'];
          $form['old_quanti_comp'][$m] = $row['quanti'];

          $form['prezzo_comp'][$m] = $row['prezzo'];
          $form['recip_stocc_comp'][$m] = $row['recip_stocc'];
          $form['quality_comp'][$m] = $row['quality'];
          $form['id_lot_comp'][$m][$n]= $row['id_lotmag'];
          $form['lot_quanti'][$m][$n] = $row['quanti'];
          $form['lot_idmov'][$m][$n] = $row['id_mov'];
          $form['q_lot_comp'][$m] = 1;
          if ($form['id_lot_comp'][$m][$n]==0){
             $form['q_lot_comp'][$m] = 0;
             $form['lot_quanti'][$m][$n]= 0;
             $form['lot_idmov'][$m][$n] = 0;
          }
          $form['id_mov'][$m] = $row['id_mov'];
          $m++;
        }

        $form['numcomp'] = $m;
      }
    }
  // se è presente, recupero il file documento lotto
  $form['filename'] = "";
  if (file_exists(DATA_DIR.'files/' . $admin_aziend['company_id']) > 0) {
      // recupero il filename dal filesystem
      $dh = opendir(DATA_DIR.'files/' . $admin_aziend['company_id']);
      while (false !== ($filename = readdir($dh))) {
          $fd = pathinfo($filename);
          $r = explode('_', $fd['filename']);
          if ($r[0] == 'lotmag' && $r[1] == $result['id_lotmag']) {
              // riassegno il nome file
              $form['filename'] = $fd['basename'];
          }
      }
  }
	$form['mov'] = 0;
  $form['nmov'] = 0;
  $form['nmovdb'] = 0;
	$form['id_staff_def'] = $result['id_staff_def'];

  $form['cosear'] = "";

} else {                 //  se e' il primo accesso per INSERT
	if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname'])){
		// allineo l'e-commerce con eventuali ordini non ancora caricati
		$gs=$admin_aziend['synccommerce_classname'];
		$gSync = new $gs();
		if($gSync->api_token){
			$gSync->get_sync_status(0);
		}
	}
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
	$form['numdoc']="";
  if (isset($_GET['type'])) { // controllo se proviene anche da una richiesta del modulo camp
      $form['order_type'] = substr($_GET['type'],0,3);
  } else { // altrimenti prendo quello in configurazione azienda
      $form['order_type'] = $admin_aziend['order_type'];
  }
  $form['description'] = "";
  $form['id_tesbro'] = "";
  $form['add_info'] = "";
  $form['gioinp'] = date("d");
  $form['mesinp'] = date("m");
  $form['anninp'] = date("Y");
  $form['datemi'] = date("Y-m-d", time());
  $form['iniprod'] = date ("d-m-Y");
  $form['iniprodtime'] = date ("H:i");
  $form['fineprod'] = date ("d-m-Y");
  $form['fineprodtime'] = date ("H:i");
  $form['day_of_validity'] = "";
  $form["campo_impianto"] = "";
  $form['order'] = 0;
  $form['coseor'] = "";
  $form['codart'] = "";
  $form['cosear'] = "";
  $form['mov'] = 0;
  $form['nmov'] = 0;
  $form['nmovdb'] = 0;
  $form['filename'] = "";
  $form['identifier'] = "";
  $form['expiry'] = "";
  $form['lot_or_serial'] = "";
  $form['SIAN'] = "";
  $form['quality'] = "";
  $form['cod_operazione']="";
  $form['recip_stocc']="";
  $form['old_recip_stocc']="";
  $form['recip_stocc_destin']="";
  $form['id_mov_sian_rif']="";
  $form['datreg'] = date("Y-m-d");
  $form['quantip'] = "";
  $form['quantipord'] = "";
  $form['id_movmag'] = "";
  $form['id_lotmag'] = "";
  $form['numcomp'] = 0;
  $resartico['lot_or_serial']="";
  $resartico['good_or_service']="";
  $resartico['unimis']="";
  $form['id_tes']="";
  $form['id_staff_def']=0;
}
if (isset($_POST['Cancel'])) { // se è stato premuto ANNULLA
  $form['hidden_req'] = '';
  $form['order_type'] = "";
  $form['description'] = "";
  $form['id_tesbro'] = "";
  $form['add_info'] = "";
  $form['gioinp'] = date("d");
  $form['mesinp'] = date("m");
  $form['anninp'] = date("Y");
  $form['day_of_validity'] = "";
  $form["campo_impianto"] = "";
  $form['order'] = "";
  $form['codart'] = "";
  $form['mov'] = 0;
  $form['nmov'] = 0;
  $form['nmovdb'] = 0;
	$form['id_staff_def']=0;
  $form['filename'] = "";
  $form['identifier'] = "";
  $form['expiry'] = "";
  $form['quantip'] = "";
  $form['id_movmag'] = "";
  $form['id_lotmag'] = "";
  $form['numcomp'] = 0;
}
if (!empty($_FILES['docfile_']['name'])) { // Antonio Germani - se c'è un nome in $_FILES
    $prefix = $admin_aziend['adminid'] . '_' . $admin_aziend['company_id'];
    foreach (glob(DATA_DIR."files/tmp/" . $prefix . "_*.*") as $fn) { // prima cancello eventuali precedenti file temporanei
        unlink($fn);
    }
    $mt = substr($_FILES['docfile_']['name'], -3);
    if (($mt == "png" || $mt == "odt" || $mt == "peg" || $mt == "jpg" || $mt == "pdf") && $_FILES['docfile_']['size'] > 1000) { // se rispetta limiti e parametri lo salvo nella cartella tmp
        move_uploaded_file($_FILES['docfile_']['tmp_name'], DATA_DIR.'files/tmp/' . $prefix . '_' . $_FILES['docfile_']['name']);
        $form['filename'] = $_FILES['docfile_']['name'];
    } else {
        $msg.= "14+";
    }
}
require ("../../library/include/header.php");
$script_transl = HeadMain(0,array('custom/autocomplete',));
if ($toDo == 'update') {
    $title = ucwords($script_transl['upd_this']) . " n." . $form['id'];
} else {
    $title = ucwords($script_transl['ins_this']);
}
//echo "<pre>",print_r($form);
echo "<form method=\"POST\" name=\"myform\" enctype=\"multipart/form-data\">\n";
echo "<input type=\"hidden\" name=\"" . ucfirst($toDo) . "\" value=\"\">\n";
echo "<input type=\"hidden\" value=\"" . $_POST['ritorno'] . "\" name=\"ritorno\">\n";
echo "<input type=\"hidden\" name=\"hidden_req\" value=\"TRUE\">\n"; // per auto submit on change select input
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">$title</div>";
echo "<table class=\"Tmiddle table-striped\" align=\"center\">\n";
$addvalue="";
if (!empty($msg)) {
  $message = "";
  $rsmsg = array_slice(explode('+', chop($msg)), 0, -1);
  foreach ($rsmsg as $value) {
      $message.= $script_transl['error'] . "! -> ";
      $rsval = explode('-', chop($value));
      foreach ($rsval as $valmsg) {
          $message.= $script_transl[$valmsg] . " ";
      }
      $message.= "<br />";
  }
  echo '<tr><td colspan="5" class="FacetDataTDred">' . $message . "</td></tr>\n";
}
if (!empty($warnmsg)) {
  $message = "";
	$addvalue=" nonostante l'avviso";
  $rsmsg = array_slice(explode('+', chop($warnmsg)), 0, -1);
  foreach ($rsmsg as $value) {
    $message.= $script_transl['warning'] . "! -> ";
    $rsval = explode('-', chop($value));
    foreach ($rsval as $valmsg) {
        $message.= $script_transl[$valmsg] . " ";
    }
    $message.= "<br />";
  }
  echo '<tr><td colspan="5" class="FacetDataTDred">' . $message . "</td></tr>\n";
}
if ($toDo == 'update') {
  echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[0]</td><td class=\"FacetDataTD\"><input type=\"hidden\" name=\"id\" value=\"" . $form['id'] . "\" />" . $form['id'] . "</td></tr>\n";
}
// Antonio Germani > inserimento tipo di produzione
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[1]</td><td class=\"FacetDataTD\">";
?>
<script>
  $(function() {
    $( ".datepicker" ).datepicker({dateFormat: 'yy-mm-dd' });

});
</script>

<?php
if ($toDo == "insert") {

  $gForm->variousSelect("order_type", $script_transl['order_type'], $form['order_type'], '', true, 'order_type');

} else {
  echo $form['order_type'], "&nbsp &nbsp";
  echo '<input type="hidden" name="order_type" value="' . $form['order_type'] . '">';
}
// inserimento data di registrazione
if ($form['order_type'] == "IND") {
  echo '<label>' . 'Data registrazione magazzino: ' . ' </label><input class="datepicker" type="text" onchange="this.form.submit();" name="datreg"  value="' . $form['datreg'] . '">';
} else {
  echo '<input type="hidden" name="datreg" value="">';
	echo '<input type="hidden" name="recip_stocc" value="">';
  echo '<input type="hidden" name="old_recip_stocc" value="">';
	echo '<input type="hidden" name="recip_stocc_destin" value="">';
  echo '<input type="hidden" name="id_mov_sian_rif" value="">';
	echo '<input type="hidden" name="cod_operazione" value="">';
  if ($form['order_type'] != "") {
    echo "Non registra magazzino!";
  }
}

?>
</td></tr>
<?php
if ($form['order_type'] <> "AGR") { // Se non è produzione agricola

    // Antonio Germani > inserimento ordine
	?>
	<tr>
		<td class="FacetFieldCaptionTD"><?php echo $script_transl['8']; ?> </td>
		<td colspan="2" class="FacetDataTD">
			<?php
		if (isset($res3) && $res3 && $toDo == "update") {
			echo "N: ",$form['order']," - Cliente: ",$res3['descri'];
		?>
			<input type="hidden" name="order" Value="<?php echo $form['order']; ?>"/>
			<input type="hidden" name="coseor" Value="<?php echo $form['coseor']; ?>"/>
      <input type="hidden" name="id_tesbro" Value="<?php echo $form['id_tesbro']; ?>"/>
			<?php
		} else {
			// Inserimento ORDINE
			$select_order = new selectorder("id_tes");
			$select_order->addSelected($form['id_tes']);
			$select_order->output($form['coseor']);
		}
    ?>
		</td>
	</tr>
	<?php
  if ($form['order'] > 0 && $toDo != 'update') { // se c'è un ordine e non siamo in update seleziono l'articolo fra quelli ordinati
		echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[9] . "</td><td class=\"FacetDataTD\">\n";
		// SELECT articolo da rigbro
		$gForm->selectFromDB('rigbro', 'cosear','codart', $form['codart'], 'id_tes', 1, ' - ','descri','TRUE','FacetSelect' , null, '','id_tes = '. $form['id_tes'].' ');
	} else { //se non c'è l'ordine seleziono l'articolo da artico
		?>
		<!-- Antonio Germani > inserimento articolo	con autocomplete dalla tabella artico-->
		<tr>
		<td class="FacetFieldCaptionTD"><?php echo $script_transl['9']; ?> </td>
		<td colspan="2" class="FacetDataTD">
		<?php
		if ($toDo == "update") {
			echo $form['codart'];?>
			<input type="hidden" name="codart" Value="<?php echo $form['codart']; ?>"/>
			<input type="hidden" name="cosear" Value="<?php echo $form['cosear']; ?>"/>
			<?php
		} else {
			$select_artico = new selectartico("codart");
			$select_artico->addSelected($form['codart']);
			$select_artico->output(substr($form['cosear'], 0, 20));
		}
	}
	echo '<input type="hidden" name="lot_or_serial" value="' .(($resartico)?$resartico['lot_or_serial']:''). '"/>';

  if ($resartico && $resartico['good_or_service'] == 2) { // se è un articolo composto
		?>
		<div class="container-fluid">
			<div class="row" style="margin-left: 0px;">
				<div align="center">
				<a  title="Vai alla distinta base composizione" class="col-sm-12 btn btn-info btn-md" href="javascript:;" onclick="window.open('<?php echo"../../modules/magazz/admin_artico_compost.php?Update&codice=".$form['codart'];?>', 'menubar=no, toolbar=no, width=800, height=400, left=80%, top=80%, resizable, status, scrollbars=1, location');">
				Articolo composto &nbsp<span class="glyphicon glyphicon-tasks"></span></a>
				</div>
			</div>
			<?php

				$nc = 0; // numero componente
				$l = 0; // numero lotto componente

				if ($toDo =="update"){// se update creo array di tutti i movimenti di magazzino da escludere
					$excluded_movmag=array();
					$exc=0;
					foreach($form['artcomp'] as $artco){// per ogni componente
						if (isset($form['lot_idmov'][$exc])){
						  foreach ($form['lot_idmov'][$exc] as $excl_lot){// ciclo i suoi lotti
							if (!in_array($excl_lot,$excluded_movmag)){// se non c'è già aggiungo movimento magazzino riferito al lotto da escludere
								$excluded_movmag[]=$excl_lot;
							}
						  }
						}
					  if (!in_array($form['id_mov'][$exc],$excluded_movmag)){// se non c'è già aggiungo movimento magazzino da escludere
						$excluded_movmag[]=$form['id_mov'][$exc];
					  }
					  $exc++;
					}
					if(($key = array_search('0', $excluded_movmag)) !== false){
						 unset($excluded_movmag[$key]);// tolgo eventuali id zero
					}
        }else{ // se non è update non escludo nulla
					$excluded_movmag=0;
				}

        while ($row = $rescompo->fetch_assoc()) { // creo gli input dei componenti visualizzandone anche disponibilità di magazzino
          $nmix=$nc;$mix="";$passrecstoc="";$ko="";
          if ($form['quantip'] > 0) {
            ?><div class="container-fluid" style="border: 1px solid green;"><?php

            if ($row['SIAN']==1 & isset($form['recip_stocc_comp'][$nc]) && strlen($form['recip_stocc_comp'][$nc])>0){// se c'è un recipiente di stoccaggio del componente
              // il contenuto potrebbe essere una miscela di articoli diversi con lotti e/o senza lotti
              $passrecstoc="Passato nel ciclo recipiente stoccaggio";
              $artico = gaz_dbi_get_row($gTables['artico'], "codice", $row['codice_artico_base']);
              ?><div class="row" style="margin-left: 0px;">
                  <div class="col-sm-3 "  style="background-color:lightcyan;"><?php echo $row['codice_artico_base']; ?>
                  </div>
                  <?php
                  $content=$campsilos->getCont($form['recip_stocc_comp'][$nc],'',$excluded_movmag);// la quantita totale disponibile nel silos
                  $row['quantita_artico_base'] = number_format ((floatval($row['quantita_artico_base']) * floatval($form['quantip'])),6);// la quantità necessaria per la produzione

                  if ($content >= $row['quantita_artico_base']){//controllo disponibilità
                    $perc_util=number_format((($row['quantita_artico_base']/$content)*100),8);// percentuale di utilizzo con 8 cifre decimali max
                    ?><div class="col-sm-3 "  style="background-color:lightcyan;"><?php echo $row['unimis']," ","Necessari: ", number_format(str_replace(",","",$row['quantita_artico_base']),5,",","."); ?>
                    </div>
                    <div class="col-sm-6" style="background-color:lightgreen;"> OK ne verrà usato <?php echo gaz_format_number($perc_util); ?> % di <?php echo $row['unimis']," ",$content; ?> come segue:</div>
                    <?php
                  } else {
                    ?><div class="col-sm-1" style="background-color:red; float:right;"> KO</div>
                    <input type="hidden" name="quanti_comp<?php echo $nc; ?>" value="ERRORE"> <!-- quantità 	insufficiente componente, ERRORE -->
                    <?php
                    $ko="KO";$submit="NO";
                  }
              ?></div> <!-- chiude row del nome articolo composto --><?php

              $mix="";
              $resmovs=$campsilos->getSilosArtico($form['recip_stocc_comp'][$nc]);// array con i codici articoli contenuti nel silos
              ?>
              <div style="background-color:lightgreen;"> <!-- elenco articoli prelevati in percentuale -->
              <?php
              if ($artico['lot_or_serial'] == 1 && $ko=="KO"){
                ?>
                <input type="hidden" name="SIAN_comp<?php echo $nc; ?>" value="<?php echo $row['SIAN']; ?>">
                <input type="hidden" class="FacetSelect" name="artcomp<?php echo $nc; ?>" value="" readonly="readonly">
                <input type="hidden" name="prezzo_comp<?php echo $nc; ?>" value="">
                <input type="hidden" name="old_quanti_comp<?php echo $nc; ?>" value="">
                <input type="hidden" name="id_mov<?php echo $nc; ?>" value="">
                <input type="hidden" name="quality_comp<?php echo $nc; ?>" value="">
                <input type="hidden" name="quanti_comp<?php echo $nc; ?>" value=""> <!-- quantità utilizzata di ogni componente   -->
                <input type="hidden" name="id_lot_comp<?php echo $nc, $l; ?>" value="">
                <input type="hidden" name="q_lot_comp<?php echo $nc; ?>" value=0>
                <input type="hidden" class="FacetSelect" name="lot_quanti<?php echo $nc, $l; ?>" value="" readonly="readonly" style="cursor: not-allowed;" title="Valore non modificabile in quanto il componente viene prelevato da un recipiente">
                <?php
                $nc++;
              }

              if ($artico['lot_or_serial'] == 1 && $ko==""){// se il componente prevede lotti gestisco gli articoli con i rispettivi lotti
                $sil_idlots=$campsilos->getContentSil($form['recip_stocc_comp'][$nc],"",0,$excluded_movmag);// prendo il contenuto in lotti e varietà dalla data dell'ultimo svuotamento totale del silos
                $errsil="0";
                if (isset($sil_idlots['id_lotti']['totale']) && $sil_idlots['id_lotti']['totale']<>$content){// il contenuto del silos non corrisponde al contenuto del silos in lotti
                  ?><div class="container-fluid" style="border: 1px solid red; background-color:lightyellow;"><?php

                  echo "ERRORE nel recipiente di stoccaggio selezionato: le movimentazioni dei lotti presentano degli errori che non permettono di registrare una produzione.";
                  $errsil="1";$submit="NO";
                  ?>
                  <button>
                  <a class="button" href="../camp/rec_stocc.php">Recipienti di stoccaggio</a>
                  </button>
                  </div><?php

                }
                if ($errsil=="0"){
                unset($sil_idlots['id_lotti']['totale']);//tolgo il totale
                $idlots=array_keys($sil_idlots['id_lotti']);// creo array idlotti presenti nel silos

                  foreach ($idlots as $idlot) {// ciclo i lotti contenuti e li prendo in percentuale creando un componente per ciascuno
                    if ($idlot>0){// se c'è un id lotto
                      $qta=$sil_idlots['id_lotti'][$idlot];
                      $codartlot = gaz_dbi_get_row($gTables['lotmag'], "id", $idlot);
                      $mv = $magazz->getStockValue(false, $codartlot['codart'], null, null, $admin_aziend['decimal_price']);
                      $magval = array_pop($mv);
                      $price_comp=($magval)?$magval['v']:0;
                      if ($price_comp==0){// se getStockValue non mi ha restituito il prezzo allora lo prendo dal prezzo di default
                        $price_comp=$row['preacq'];
                      }

                      ?>
                      <div class="row">
                        <input type="hidden" name="SIAN_comp<?php echo $nc; ?>" value="<?php echo $row['SIAN']; ?>">
                        <input type="text" class="FacetSelect" name="artcomp<?php echo $nc; ?>" value="<?php echo $codartlot['codart']; ?>" readonly="readonly">
                        <input type="hidden" name="prezzo_comp<?php echo $nc; ?>" value="<?php echo $price_comp; ?>">
                        <input type="hidden" name="old_quanti_comp<?php echo $nc; ?>" value="<?php echo (isset($form['old_quanti_comp'][$nc]))?$form['old_quanti_comp'][$nc]:0; ?>">
                        <input type="hidden" name="id_mov<?php echo $nc; ?>" value="<?php echo (isset($form['id_mov'][$nc]))?$form['id_mov'][$nc]:0; ?>">
                        <input type="hidden" name="quality_comp<?php echo $nc; ?>" value="<?php echo $row['quality']; ?>">
                        <input type="hidden" name="quanti_comp<?php echo $nc; ?>" value="<?php echo floatval(preg_replace('/[^\d.]/', '', number_format((($qta*$perc_util)/100),8))); ?>"> <!-- quantità utilizzata di ogni componente   -->
                        <input type="hidden" name="id_lot_comp<?php echo $nc, $l; ?>" value="<?php echo $idlot; ?>">
                        <input type="hidden" name="q_lot_comp<?php echo $nc; ?>" value=1>
                        <input type="text" class="FacetSelect" name="lot_quanti<?php echo $nc, $l; ?>" value="<?php echo floatval(preg_replace('/[^\d.]/', '', number_format((($qta*$perc_util)/100),8))); ?>" readonly="readonly" style="cursor: not-allowed;" title="Valore non modificabile in quanto il componente viene prelevato da un recipiente">
                        <span>su <?php echo gaz_format_number($qta),$row['unimis'];?> disponibili, lotto n: <?php echo $codartlot['identifier'],(intval($codartlot['expiry'])>0)?" scadenza: ".$codartlot['expiry']:''," id: ",$idlot;?></span>
                      </div>
                      <?php
                      if ($nc>$nmix){// se è un componente mix successivo al primo
                        ?>
                        <input type="hidden" name="recip_stocc_comp<?php echo $nc; ?>" value="<?php echo $form['recip_stocc_comp'][$nmix]; ?>">
                        <?php // imposto il recipiente uguale al primo componente
                      }
                      $nc++;
                    }else{// se non c'è lotto (ad esempio nel silos sono mischiati articoli con lotti e senza lotti)
                      $mix="Miscela di articoli con e senza lotti";
                    }
                  }

                  if ($mix!==""){//se è una Miscela di articoli con e senza lotti
                      foreach ($resmovs as $res) {// ciclo gli articoli senza lotti presenti nel silos e li inserisco fra i componenti
                        $qta=$campsilos->getCont($form['recip_stocc_comp'][$nmix],$res);
                        $artico_sil = gaz_dbi_get_row($gTables['artico'], "codice", $res);
                        if ($artico_sil['lot_or_serial'] == 0){// se l'articolo non prevede lotti
                          $mv = $magazz->getStockValue(false, $res, null, null, $admin_aziend['decimal_price']);
                          $magval = array_pop($mv);
                          $price_comp=($magval)?$magval['v']:0;
                          if ($price_comp==0){// se getStockValue non mi ha restituito il prezzo allora lo prendo dal prezzo di default
                            $price_comp=$row['preacq'];
                          }
                          ?>
                          <div class="row">
                            <input type="hidden" name="SIAN_comp<?php echo $nc; ?>" value="<?php echo $row['SIAN']; ?>">
                            <input type="text" class="FacetSelect" name="artcomp<?php echo $nc; ?>" value="<?php echo $res; ?>" readonly="readonly">
                            <input type="hidden" name="prezzo_comp<?php echo $nc; ?>" value="<?php echo $price_comp; ?>">
                            <input type="hidden" name="old_quanti_comp<?php echo $nc; ?>" value="<?php echo $form['old_quanti_comp'][$nc]; ?>">
                            <input type="hidden" name="id_mov<?php echo $nc; ?>" value="<?php echo $form['id_mov'][$nc]; ?>">
                            <input type="hidden" name="quality_comp<?php echo $nc; ?>" value="<?php echo $row['quality']; ?>">
                            <input type="text" class="FacetSelect" name="quanti_comp<?php echo $nc; ?>" value="<?php echo floatval(preg_replace('/[^\d.]/', '', number_format((($qta*$perc_util)/100),8))); ?>" readonly="readonly"> <!-- quantità utilizzata di ogni componente   -->
                            <input type="hidden" name="id_lot_comp<?php echo $nc; ?>0" value="">
                            <input type="hidden" name="q_lot_comp<?php echo $nc; ?>" value="">
                            <span>su <?php echo gaz_format_number($qta),$row['unimis'];?> disponibili, senza lotto</span>

                          </div>
                          <?php
                          if ($nc>$nmix){// se è un componente mix successivo al primo
                            ?>
                            <input type="hidden" name="recip_stocc_comp<?php echo $nc; ?>" value="<?php echo $form['recip_stocc_comp'][$nmix]; ?>">
                            <?php // imposto il recipiente uguale al primo componente
                          }
                          $nc++;
                        }
                      }
                  }
                }else{
                  ?>
                   <input type="hidden" name="SIAN_comp<?php echo $nc; ?>" value="<?php echo $row['SIAN']; ?>">
                      <input type="hidden" class="FacetSelect" name="artcomp<?php echo $nc; ?>" value="" readonly="readonly">
                      <input type="hidden" name="prezzo_comp<?php echo $nc; ?>" value="">
                      <input type="hidden" name="old_quanti_comp<?php echo $nc; ?>" value="">
                      <input type="hidden" name="id_mov<?php echo $nc; ?>" value="">
                      <input type="hidden" name="quality_comp<?php echo $nc; ?>" value="">
                      <input type="hidden" name="quanti_comp<?php echo $nc; ?>" value=""> <!-- quantità utilizzata di ogni componente   -->
                      <input type="hidden" name="id_lot_comp<?php echo $nc, $l; ?>" value="">
                      <input type="hidden" name="q_lot_comp<?php echo $nc; ?>" value=0>
                      <input type="hidden" class="FacetSelect" name="lot_quanti<?php echo $nc, $l; ?>" value="" readonly="readonly" style="cursor: not-allowed;" title="Valore non modificabile in quanto il componente viene prelevato da un recipiente">
                 <?php
                 $nc++;
                }
              }elseif ($ko=="") {// se NON ci sono lotti gestisco solo gli articoli contenuti nel silos

                foreach ($resmovs as $res) {// ciclo gli articoli e prendo in percentuale creando più componenti se necessario
                  $qta=$campsilos->getCont($form['recip_stocc_comp'][$nmix],$res);
                  $mv = $magazz->getStockValue(false, $res, null, null, $admin_aziend['decimal_price']);
                  $magval = array_pop($mv);
                  $price_comp=($magval)?$magval['v']:0;

                  if ($price_comp==0){// se getStockValue non mi ha restituito il prezzo allora lo prendo dal prezzo di default
                    $price_comp=$row['preacq'];
                  }
                  ?>
                  <div class="row">
                    <input type="hidden" name="SIAN_comp<?php echo $nc; ?>" value="<?php echo $row['SIAN']; ?>">
                    <input type="text" class="FacetSelect" name="artcomp<?php echo $nc; ?>" value="<?php echo $res['artico']; ?>" readonly="readonly">
                    <input type="hidden" name="prezzo_comp<?php echo $nc; ?>" value="<?php echo $price_comp; ?>">
                    <input type="hidden" name="old_quanti_comp<?php echo $nc; ?>" value="<?php echo $form['old_quanti_comp'][$nc]; ?>">
                    <input type="hidden" name="id_mov<?php echo $nc; ?>" value="<?php echo $form['id_mov'][$nc]; ?>">
                    <input type="hidden" name="quality_comp<?php echo $nc; ?>" value="<?php echo $row['quality']; ?>">
                    <input type="text" class="FacetSelect" name="quanti_comp<?php echo $nc; ?>" value="<?php echo floatval(preg_replace('/[^\d.]/', '', number_format((($qta*$perc_util)/100),8))); ?>" readonly="readonly"> <!-- quantità utilizzata di ogni componente   -->
                    <input type="hidden" name="id_lot_comp<?php echo $nc; ?>0" value="">
                    <input type="hidden" name="q_lot_comp<?php echo $nc; ?>" value="">
                    <span>su <?php echo gaz_format_number($qta),$row['unimis'];?> disponibili, senza lotto</span>

                  </div>
                  <?php
                  if ($nc>$nmix){// se è un componente mix successivo al primo
                    ?>
                    <input type="hidden" name="recip_stocc_comp<?php echo $nc; ?>" value="<?php echo $form['recip_stocc_comp'][$nmix]; ?>">
                    <?php // imposto il recipiente uguale al primo componente
                  }
                  $nc++;
                }
              }
              ?>
              </div><!-- chiude elenco articoli prelevati in percentuale -->
              <?php
            } else { // il componente NON ha recipiente di stoccaggio
              $row['quantita_artico_base'] = number_format ((floatval($row['quantita_artico_base']) * floatval($form['quantip'])),6);
              $mv = $magazz->getStockValue(false, $row['codice_artico_base'], null, null, $admin_aziend['decimal_price']);
              $magval = array_pop($mv);
              $price_comp=($magval)?$magval['v']:0;
              if ($price_comp==0){// se getStockValue non mi ha restituito il prezzo allora lo prendo dal prezzo di default
                $price_comp=$row['preacq'];
              }
              // controllo disponibilità in magazzino
              $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
              if ($toDo == "update" && isset($form['old_quanti_comp'][$nc])) { // se è un update riaggiungo la quantità utilizzata
                $magval['q_g'] = $magval['q_g'] + floatval($form['old_quanti_comp'][$nc]);
              }
              ?>
              <input type="hidden" name="SIAN_comp<?php echo $nc; ?>" value="<?php echo $row['SIAN']; ?>">
              <input type="hidden" name="artcomp<?php echo $nc; ?>" value="<?php echo $row['codice_artico_base']; ?>">
              <input type="hidden" name="prezzo_comp<?php echo $nc; ?>" value="<?php echo $price_comp; ?>">
              <input type="hidden" name="quality_comp<?php echo $nc; ?>" value="<?php echo $row['quality']; ?>">
              <input type="hidden" name="old_quanti_comp<?php echo $nc; ?>" value="<?php echo ((isset($form['old_quanti_comp'][$nc]))?$form['old_quanti_comp'][$nc]:0); ?>">
              <input type="hidden" name="id_mov<?php echo $nc; ?>" value="<?php (isset($form['id_mov'][$nc]))?$form['id_mov'][$nc]:0 ?>">
              <div class="row" style="margin-left: 0px;">
                <div class="col-sm-3 "  style="background-color:lightcyan;"><?php echo $row['codice_artico_base']; ?>
                </div>
                <!-- Antonio Germani devo usare number_format perché la funzione gaz_format_quantity non accetta più di 3 cifre dopo la virgola. -->
                <div class="col-sm-4 "  style="background-color:lightcyan;"><?php echo $row['unimis']," ","Necessari: ", number_format(str_replace(",","",$row['quantita_artico_base']),5,",","."); ?>
                </div>
                <div class="col-sm-4 "  style="background-color:lightcyan;"><?php echo "Disponibili: ", number_format($magval['q_g'],5,",","."); ?>
                </div>
                <?php
                if (number_format($magval['q_g'],5,".","") - floatval(preg_replace('/[^\d.]/', '', $row['quantita_artico_base'])) >= 0) { // giacenza sufficiente
                  ?>
                  <input type="hidden" name="quanti_comp<?php echo $nc; ?>" value="<?php echo floatval(preg_replace('/[^\d.]/', '', $row['quantita_artico_base'])); ?>"> <!-- quantità utilizzata di ogni componente   -->
                  <div class="col-sm-1" style="background-color:lightgreen; float:right;"> OK</div>
                  <?php
                } else { // giacenza insufficiente
                  $ko="KO";$submit="NO";
                  ?>
                  <input type="hidden" name="quanti_comp<?php echo $nc; ?>" value="ERRORE"> <!-- quantità 	insufficiente componente, ERRORE -->
                  <div class="col-sm-1" style="background-color:red; float:right;"> KO</div>
                  <?php
                }
                ?>
              </div> <!-- chiude row del nome articolo composto -->
              <?php
            }
            // Antonio Germani - Inizio form SIAN
            if ($row['SIAN']>0 AND $form['order_type'] == "IND"){ // se l'articolo prevede un movimento SIAN e siamo su prod.industriale
              $rescampbase = gaz_dbi_get_row($gTables['camp_artico'], "codice", $row['codice_artico_base']);
              if ($rescampbase['confezione']==0){ // se è sfuso apro la richiesta contenitore

                ?>
                <div class="row">
                <label for="camp_recip_stocc_comp" class="col-sm-5"><?php echo "Recipiente stoccaggio del componente"; ?></label>
                <?php
                if (!isset($form['recip_stocc_comp'][$nmix])){
                  $form['recip_stocc_comp'][$nmix]="";
                }
                $campsilos->selectSilos('recip_stocc_comp'.$nmix ,'cod_silos', $form['recip_stocc_comp'][$nmix], 'cod_silos', 1,'capacita','TRUE','col-sm-7' , null, '','','',$row['codice_artico_base'],$excluded_movmag);
                ?>
                </div>
                <?php
              } else {
                echo '<input type="hidden" name="recip_stocc_comp'.$nmix.'" value="">';
              }
            } else {
              echo '<input type="hidden" name="recip_stocc_comp'.$nmix.'" value="">';
            }

            if ($passrecstoc<>""){// se sono passato per il ciclo recipente di stoccaggio
              ($nc>0)?$nc--:$nc=0; // porto indietro nc se si può altrimenti è zero
            }

            if ($ko=="" && $passrecstoc<>"" && (isset($form['recip_stocc_comp'][$nc]) || strlen($form['recip_stocc_comp'][$nmix])>0)){// se c'è un recipiente di stoccaggio del componente e sono gia passato per i relativi input
            // non faccio nulla, ho già fatto prima
            } else {

              // Antonio Germani - inserimento lotti in uscita
              $artico = gaz_dbi_get_row($gTables['artico'], "codice", $row['codice_artico_base']);
              if ($artico['lot_or_serial'] == 1) { // se il componente prevede lotti

                if ($toDo !="update"){
                  $form['id_mov'][$nc]=0;
                  $excluded_movmag=0;
                } else {
                  // creo array con ID lotti da escludere
                    $excluded_movmag=0;
                    if (isset($form['lot_idmov'][$nc])){
                      $excluded_movmag=array();
                      foreach ($form['lot_idmov'][$nc] as $excl_lot){

                        $excluded_movmag[]=$excl_lot;
                      }
                    }

                }
                $lm->getAvailableLots($row['codice_artico_base'],$excluded_movmag);
                $ld = $lm->divideLots(str_replace(",","",$row['quantita_artico_base']));
                if ((!isset($_POST['Update'])) and (isset($_GET['Update']))){// se è il primo accesso per update devo impostare le vecchie scelte

                  //echo "<pre>",print_r($form['id_lot_comp'][$nc]);die;

                  $n=0;
                  foreach($form['artcomp'] as $artcomp){// ciclo i codici articolo per trovare la posizione nell'array
                    if ($row['codice_artico_base']==$artcomp){// quando trovato e se prevede lotti
                      //echo "trovato:",$artcomp,"<pre>",print_r($form['id_lot_comp'][$n]),print_r($form['lot_quanti'][$n]);
                      $nn=0;
                      foreach($form['id_lot_comp'][$n] as $idlotcomp){// ciclo gli ID lotti di questo componente
                        $lm->divided[$idlotcomp]['qua']=$form['lot_quanti'][$n][$nn];// lo preseleziono
                        $nn++;
                      }
                    }
                    $n++;
                  }

                  /*
                  if(array_search($idlotcomp, array_column($lm->divided, 'id')) !== false) {
                    echo $idlotcomp,' value is in multidim array';
                  } else {
                    echo $idlotcomp,' value is not in multidim array AGGIUNGO';
                  }
                  */

                  $form['amLot'. $nc] = "manuale";
                }

                $l = 0;
                //echo "<pre>divided:",print_r($lm->divided);
                if ($ld > 0 && ((isset($_POST['Update'])) || (!isset($_GET['Update'])))) { // se NON è il primo accesso per update segnalo preventivamente l'errore Q.ta insufficiente
                  echo "ERRORE ne mancano:", gaz_format_quantity($ld,","), "<br>"; // >>>>> quantità insufficiente - metto come valore ERRORE così potrò ritrovarlo facilmente e annullo quanti lotti sono interessati per questo componente
                  ?>
                  <input type="hidden" name="lot_quanti<?php echo $nc, $l; ?>" value="ERRORE">
                  <input type="hidden" name="lot_idmov<?php echo $nc, $l; ?>" value="<?php echo (isset($form['lot_idmov'][$nc][$l]))?$form['lot_idmov'][$nc][$l]:0; ?>">
                  <input type="hidden" name="q_lot_comp<?php echo $nc; ?>" value="">
                  <?php
                } else {
                  if (!isset($form['amLot'. $nc])){
                    $form['amLot'. $nc]="";
                  }
                  if (($form['amLot'. $nc] == "autoLot" OR $form['amLot'. $nc]=="") && $ko==""){ // se selezione lotti automatica
                    // ripartisco la quantità introdotta tra i vari lotti disponibili per l'articolo
                    foreach ($lm->divided as $k => $v) { // ciclo i lotti scelti da divideLots
                      if ($v['qua'] >= 0.00001) {
                        ?><div class="row"><?php
                        //$form['id_lot_comp'][$nc][$l]="";
                        //$form['lot_quanti'][$nc][$l]="";

                          if (!isset($form['id_lot_comp'][$nc][$l]) or (intval($form['id_lot_comp'][$nc][$l])==0)) {
                            $form['id_lot_comp'][$nc][$l] = $v['id']; // al primo ciclo, cioè id lotto è zero, setto il lotto
                          }

                          if ((isset($_POST['Update'])) || (!isset($_GET['Update']))){ // se non è il primo accesso per update altrimenti uso il riparto presente nel db DI MOVMAG
                            $form['lot_quanti'][$nc][$l] = $v['qua']; // la quantità in base al riparto
                          }
                          $selected_lot = $lm->getLot($form['id_lot_comp'][$nc][$l]);
                          $disp= $lm -> dispLotID ($artico['codice'], $selected_lot['id'],(isset($form['lot_idmov'][$nc][$l]))?$form['lot_idmov'][$nc][$l]:0);
                          echo '<button class="btn btn-xs btn-success"  title="Lotto selezionato automaticamente" data-toggle="collapse" href="#lm_dialog' . $nc . $l.'">' . $selected_lot['id'] . ' Lotto n.: ' . $selected_lot['identifier'];
                          if (intval($selected_lot['expiry'])>0){
                            echo ' Scadenza: ' . gaz_format_date($selected_lot['expiry']);
                          }
                          echo ' disponibili:' . gaz_format_quantity($disp);
                          echo '  <i class="glyphicon glyphicon-tag"></i></button>';

                        ?>
                        <input type="hidden" name="lot_idmov<?php echo $nc, $l; ?>" value="<?php echo ((isset($form['lot_idmov'][$nc][$l]))?$form['lot_idmov'][$nc][$l]:0); ?>">
                        <input type="hidden" name="id_lot_comp<?php echo $nc, $l; ?>" value="<?php echo $form['id_lot_comp'][$nc][$l]; ?>">
                        Quantità<input type="text" name="lot_quanti<?php echo $nc, $l; ?>" value="<?php echo $form['lot_quanti'][$nc][$l]; ?>" onchange="this.form.submit();">
                        <?php
                        $l++;
                        ?></div><?php
                      }
                    }

                    ?>
                    Passa a <input type="submit" class="btn glyphicon glyphicon-trash-circle" name="manLot<?php echo $nc; ?>" id="preventDuplicate" onClick="chkSubmit();" value="manuale">&#128075;
                    <?php
                  } elseif ($form['amLot'. $nc] == "manuale" && $ko==""){	// se selezione manuale
                    for ($l = 0;$l < $form['q_lot_comp'][$nc];++$l) {
                      ?><div class="row"><?php
                      if (!isset($form['id_lot_comp'][$nc][$l]) or (intval($form['id_lot_comp'][$nc][$l])==0)) {
                        $form['id_lot_comp'][$nc][$l] = 0; // appena aggiunto rigo lotto ciclo setto il lotto a zero
                        $form['lot_quanti'][$nc][$l] = 0;
                      }else{
                        $selected_lot = $lm->getLot($form['id_lot_comp'][$nc][$l]);
                      }
                      $disp= $lm -> dispLotID ($artico['codice'], $selected_lot['id'],(isset($form['lot_idmov'][$nc][$l]))?$form['lot_idmov'][$nc][$l]:0);
                      echo '<button class="btn btn-xs btn-success"  title="Lotto selezionato automaticamente" data-toggle="collapse" href="#lm_dialog' . $nc . $l.'">' . $selected_lot['id'] . ' Lotto n.: ' . $selected_lot['identifier'];
                      if (intval($selected_lot['expiry'])>0){
                        echo ' Scadenza: ' . gaz_format_date($selected_lot['expiry']);
                      }
                      echo ' disponibili:' . gaz_format_quantity($disp);
                      echo '  <i class="glyphicon glyphicon-tag"></i></button>';
                      ?>
                      <input type="hidden" name="lot_idmov<?php echo $nc, $l; ?>" value="<?php echo $form['lot_idmov'][$nc][$l]; ?>">
                      <input type="hidden" name="id_lot_comp<?php echo $nc, $l; ?>" value="<?php echo $form['id_lot_comp'][$nc][$l]; ?>">
                      Quantità<input type="text" name="lot_quanti<?php echo $nc, $l; ?>" value="<?php echo $form['lot_quanti'][$nc][$l]; ?>" onchange="this.form.hidden_req.value='manualUpd'; this.form.submit();">
                      </div>
                      <?php
                    }
                    ?>
                    <div>
                    <button type="submit" name="addLot<?php echo $nc; ?>" title="Aggiungi rigo lotto" class="btn btn-default"  style="border-radius= 85px; "> <i class="glyphicon glyphicon-plus-sign"></i></button>
                    <button type="submit" name="subtLot<?php echo $nc; ?>" title="Togli rigo lotto" class="btn btn-default"  style="border-radius= 85px; "> <i class="glyphicon glyphicon-minus-sign"></i></button>
                    &nbsp; &nbsp; Passa a <input type="submit" class="btn " name="autoLot<?php echo $nc; ?>" id="preventDuplicate" onClick="chkSubmit();" value="autoLot">&#128187;
                    </div>
                    <?php
                  }
                  ?>
                  <input type="hidden" name="amLot<?php echo $nc; ?>" id="preventDuplicate" value="<?php echo $form['amLot'.$nc]; ?>">

                  <input type="hidden" name="q_lot_comp<?php echo $nc; ?>" value="<?php echo $l; ?>">
                  <?php // q lot comp ha volutamente una unità in più per distinguerlo da quando è zero cioè nullo

                  for ($cl = 0; $cl < $l; $cl++) {
                    ?>
                    <!-- Antonio Germani - Cambio lotto -->
                    <div id="lm_dialog<?php echo $nc,$cl;?>" class="collapse" >
                      <?php
                      if ((count($lm->available) > 1)) {
                        foreach ($lm->available as $v_lm) {
                          if ($v_lm['id'] <> $form['id_lot_comp'][$nc][$cl]) {
                            echo '<div>Cambia con:<button class="btn btn-xs btn-warning" type="text" onclick="this.form.submit();" name="id_lot_comp'.$nc.$cl.'" value="'.$v_lm['id'].'">'
                            . $v_lm['id']. ' lotto n.:' . $v_lm['identifier'];
                            if (intval($v_lm['expiry'])>0){
                              echo ' scadenza:' . gaz_format_date($v_lm['expiry']);
                            }
                            echo ' disponibili:' . gaz_format_quantity($v_lm['rest'])
                            . '</button></div>';
                          }
                        }
                      } else {
                        echo '<div><button class="btn btn-xs btn-danger" type="image" >Non ci sono disponibili altri lotti.</button></div>';
                      }
                      ?>
                    </div>
                    <?php
                  }
                }
                ?>

                <input type="hidden" name="id_mov<?php echo $nc; ?>" value="<?php echo $form['id_mov'][$nc]; ?>">
                <?php

              } else { // se non prevede lotto azzero id_lotmag e q_lot_mag di $nc
                echo " Componente senza lotto";
                if ($toDo != "update"){
                  $form['id_mov'][$nc]=0;
                }
                ?>
                <input type="hidden" name="lot_idmov' . $nc . '0" value="">
                <input type="hidden" name="id_mov<?php echo $nc; ?>" value="<?php echo $form['id_mov'][$nc]; ?>">
                <input type="hidden" name="id_lot_comp' . $nc . '0" value="">
                <input type="hidden" name="q_lot_comp<?php echo $nc; ?>" value=""> <!--// non ci sono lotti per questo componente-->
                <?php

              }
            }
            ?>
            <?php
            $nc++;
         ?>
		</div>	<!-- chiude container  -->
		<?php
					}
				}
				echo '<input type="hidden" name="numcomp" value="' . $nc . '">'; // Antonio Germani - Nota bene: numcomp ha sempre una unità in più! Non l'ho tolta per distinguere se c'è un solo componente o nessuno.

	}
	?>
	</td>
	</tr>
	<?php // Antonio Germani - Inizio form SIAN
	if ($form['SIAN'] > 0 && $form['order_type'] == "IND") { // se l'articolo prevede un movimento SIAN e siamo su prod.industriale
		$rescampbase = gaz_dbi_get_row($gTables['camp_artico'], "codice", $form['codart']);
		echo "<tr><td class=\"FacetFieldCaptionTD\">Gestione SIAN</td>";
		echo "<td>";
		?>
		<div class="container-fluid">
			<div class="row">
				<label for="cod_operazione" class="col-sm-6 control-label"><?php echo "Tipo operazione SIAN"; ?></label>
				<?php

				$gForm->variousSelect('cod_operazione', $script_transl['cod_operaz_value'], $form['cod_operazione'], "col-sm-6", false, '', false);

				?>
			</div>
			<?php if ($rescampbase['confezione']==0){
        if ($form['recip_stocc']==$form['old_recip_stocc'] && $toDo == "update"){// se non è cambiato il contenitore d'origine e stiamo in update
          $excluded_movmag=$form['id_mov_sian_rif'];// faccio escludere il movimento dal calcolo disponibilità recipiente di origine
		  $excluded_movmag_dest=$form['id_movmag'];// faccio escludere il movimento dal calcolo disponibilità recipiente di destinazione
        }else{
          $excluded_movmag=0;          
		  $excluded_movmag_dest=0;
        }

        ?>
				<div class="row">
					<label for="camp_recip_stocc" class="col-sm-6"><?php echo "Recipiente stoccaggio"; ?></label>
					<?php
					$campsilos->selectSilos('recip_stocc' ,'cod_silos', $form['recip_stocc'], 'cod_silos', 1,'capacita','TRUE','col-sm-6' , null, '', false, false, '', $excluded_movmag);
					?>
				</div>
				<?php
				if ($form['cod_operazione']==5){ ?>
					<div class="row">
					<label for="camp_recip_stocc" class="col-sm-6"><?php echo "Recipiente stoccaggio destinazione"; ?></label>
					<?php
					$campsilos->selectSilos('recip_stocc_destin' ,'cod_silos', $form['recip_stocc_destin'], 'cod_silos', 1,'capacita','TRUE','col-sm-6' , null, '', false, false, '', $excluded_movmag_dest);
					?>
				</div>
				<?php
				} else {
					echo '<input type="hidden" name="recip_stocc_destin" value="">';
				}
			} else {
				echo '<tr><td><input type="hidden" name="recip_stocc" value="">';
				echo '<input type="hidden" name="recip_stocc_destin" value="">';
			}

		echo "</div>";
		echo"</td></tr>";
	} else {
		echo '<tr><td><input type="hidden" name="recip_stocc" value="">';
		echo '<input type="hidden" name="recip_stocc_destin" value="">';
		echo '<input type="hidden" name="cod_operazione" value=""></td></tr>';
	}
echo '<input type="hidden" name="old_recip_stocc" value="',$form["old_recip_stocc"],'">';
echo '<input type="hidden" name="id_mov_sian_rif" value="',$form["id_mov_sian_rif"],'">';

	?>
	<!--- Antonio Germani - inserimento quantità  -->
	<tr>
		<td class="FacetFieldCaptionTD"><?php echo $script_transl['15']; ?> </td>
		<td colspan="2" class="FacetDataTD">
			<?php

				?>
				<input type="text" name="quantip" onchange="this.form.submit()" value="<?php echo $form['quantip']; ?>" />
				<?php
				echo ($resartico)?$resartico['unimis']:'';
				// Antonio Germani - Visualizzo quantità prodotte e rimanenti
				if (($form['order']) > 0 && strlen($form['codart']) > 0) { // se c'è un ordine e c'è un articolo selezionato, controllo se è già stato prodotto

					if ($quantiprod > 0) { // se c'è stata già una produzione per questo articolo e per questo ordine
						echo " già prodotti : <b>", $quantiprod. "</b>";
						echo " Ne servono ancora : <b>". gaz_format_quantity($form['quantipord'] - $quantiprod, 0, $admin_aziend['decimal_quantity']), "</b>";
					} else {
						echo " L'ordine ne richiede : <b>", gaz_format_quantity($form['quantipord'], 0, $admin_aziend['decimal_quantity'])."</b>";
					}
					if ($form['quantipord'] - $quantiprod > 0) {
						$form['okprod'] = "ok";
					} else {
						$form['okprod'] = "";
					}
					?>
					<input type="hidden" name="okprod" value="<?php echo $form['okprod']; ?>">
					<?php
				} else {
					?>
					<input type="hidden" name="okprod" value="">
					<?php
				}

			?>
			<input type="hidden" name="id_movmag" value="<?php echo $form['id_movmag']; ?>">
		</td>
	</tr>
	<?php
} else { // se è produzione agricola
   echo "<tr><td><input type=\"hidden\" name=\"order\" value=\"\">";
	?>
	<!-- Antonio Germani > inserimento articolo	con autocomplete dalla tabella artico-->
	<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['9']; ?> </td>
	<td colspan="2" class="FacetDataTD">
	<?php
	if ($toDo == "update") {
		echo $form['codart'];?>
		<input type="hidden" name="codart" Value="<?php echo $form['codart']; ?>"/>
		<input type="hidden" name="cosear" Value="<?php echo $form['cosear']; ?>"/>
    <input type="hidden" name="coseor" Value="<?php echo $form['coseor']; ?>"/>
    <input type="hidden" name="id_tesbro" Value="<?php echo $form['id_tesbro']; ?>"/>
		<?php
	} else {
		$select_artico = new selectartico("codart");
		$select_artico->addSelected($form['codart']);
		$select_artico->output(substr($form['cosear'], 0,32));
	}
	?>

  <input type="hidden" name="id_movmag" value="">
  <input type="hidden" name="quantip" value="">
  </td>
  </tr>
  <?php
}
?>
<!--- Antonio Germani - inserimento descrizione  -->
<tr>
	<td class="FacetFieldCaptionTD"><?php echo $script_transl['2']; ?> </td>
	<td colspan="2" class="FacetDataTD">
	<input type="text" name="description" value="<?php echo htmlspecialchars($form['description']); ?>" maxlength="80" />
	</td>
</tr>
<?php
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[3]</td><td class=\"FacetDataTD\">";
?>
		<textarea type="text" name="add_info" align="right" maxlength="255" cols="67" rows="3"><?php echo $form['add_info']; ?></textarea>
<?php
echo "</td></tr>\n";

if ($form['order_type'] <> "AGR") { // Se non è produzione agricola
  // DATA inizio produzione
  echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[5] . "</td><td class=\"FacetDataTD\">\n";
  echo "\t <select name=\"gioinp\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
  for ($counter = 1;$counter <= 31;$counter++) {
      $selected = "";
      if ($counter == $form['gioinp']) $selected = "selected";
      echo "\t <option value=\"$counter\"  $selected >$counter</option>\n";
  }
  echo "\t </select>\n";
  echo "\t <select name=\"mesinp\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
  $gazTimeFormatter->setPattern('MMMM');
  for ($counter = 1;$counter <= 12;$counter++) {
    $selected = "";
    if ($counter == $form['mesinp']) $selected = "selected";
    $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
    echo "\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
  }
  echo "\t </select>\n";
  echo "\t <select name=\"anninp\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
  for ($counter = date("Y") - 10;$counter <= date("Y") + 10;$counter++) {
      $selected = "";
      if ($counter == $form['anninp']) $selected = "selected";
      echo "\t <option value=\"$counter\"  $selected >$counter</option>\n";
  }
  echo "\t </select></td>\n";
  // end data inizio produzione
} else {
	?>
	<input type="hidden" name="gioinp" Value=""/>
	<input type="hidden" name="mesinp" Value=""/>
	<input type="hidden" name="anninp" Value=""/>
	<?php
  $form['datemi']=$form['anninp']."-".$form['mesinp']."-".$form['gioinp'];
}
if (!isset($form['datemi']) || strlen($form['datemi'])==0 || intval($form['datemi'])==0){//quando non c'è la data emissione la select addetto blocca tutto il form, quindi ci metto quella di inizio produzione
  $form['datemi']=date ("Y-m-d");
}
// Antonio Germani > DURATA produzione

echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[11]</td>";

echo "<td class=\"FacetDataTD\"><input type=\"number\" name=\"day_of_validity\" min=\"0\" maxlength=\"3\" step=\"any\"  size=\"10\" value=\"" . $form['day_of_validity'] . "\"  /></td></tr>\n";
/*Antonio Germani LUOGO di produzione  */
echo "<tr><td class=\"FacetFieldCaptionTD\">" . $script_transl[7] . "</td><td class=\"FacetDataTD\">\n";
// SELECT luogo di produzione da campi
$gForm->selectFromDB('campi', 'campo_impianto','codice', $form['campo_impianto'], 'codice', 1, ' - ','descri','TRUE','FacetSelect' , null, '');
echo "</td></tr>";

// Antonio Germani selezione responsabile o addetto alla produzione fra l'elenco staff
// SELECT da staff con acquisizione nome da clfoco
echo "<tr><td class=\"FacetFieldCaptionTD\">Responsabile/addetto produzione</td><td class=\"FacetDataTD\">\n";
$gForm->selectWorker($form['id_staff_def'],$form['anninp'],$form['mesinp'], "style='max-width: 300px;'",true);
?>
</select>

<!-- Inserimento data inizio lavori -->
<tr>
  <td class="FacetFieldCaptionTD"><?php echo $script_transl[33]; ?></td>
  <td class="FacetDataTD">
  <?php echo gaz_select_data ( "iniprod", $form['iniprod'] ),"&nbsp; Ora inizio ", gaz_select_ora ( "iniprodtime", $form['iniprodtime'] ); ?>
  </td>
</tr>

<!-- Inserimento data fine lavori -->
<tr>
  <td class="FacetFieldCaptionTD"><?php echo $script_transl[34]; ?></td>
  <td class="FacetDataTD">
  <?php echo gaz_select_data ( "fineprod", $form['fineprod'] ),"&nbsp;Ora fine ", gaz_select_ora ( "fineprodtime", $form['fineprodtime'] ); ?>
  </td>
</tr>
<?php
if ($form['order_type'] <> "AGR") { // input esclusi se NON produzione agricola
  // Antonio Germani > Inizio LOTTO in entrata o creazione nuovo

  if (intval($form['lot_or_serial']) == 1) { // se l'articolo prevede il lotto apro la gestione lotti nel form
    ?>
    <tr><td class="FacetFieldCaptionTD"><?php echo $script_transl[13]; ?></td>
    <td class="FacetDataTD" >
    <input type="hidden" name="filename" value="<?php echo $form['filename']; ?>">
    <input type="hidden" name="id_lotmag" value="<?php echo $form['id_lotmag']; ?>">
    <?php
    if (strlen($form['filename']) == 0) {
        echo '<div><button class="btn btn-xs btn-danger" type="image" data-toggle="collapse" href="#lm_dialog">' . 'Inserire nuovo certificato' . ' ' . '<i class="glyphicon glyphicon-tag"></i>' . '</button></div>';
    } else {
        echo '<div><button class="btn btn-xs btn-success" type="image" data-toggle="collapse" href="#lm_dialog">' . $form['filename'] . ' ' . '<i class="glyphicon glyphicon-tag"></i>' . '</button>';
        echo '</div>';
    }
    if (strlen($form['identifier']) == 0) {
        echo '<div><button class="btn btn-xs btn-danger" type="image" data-toggle="collapse" href="#lm_dialog_lot">' . 'Inserire nuovo Lotto' . ' ' . '<i class="glyphicon glyphicon-tag"></i></button></div>';
    } else {
        if (intval($form['expiry']) > 0) {
            echo '<div><button class="btn btn-xs btn-success" type="image" data-toggle="collapse" href="#lm_dialog_lot">' . $form['identifier'] . ' ' . gaz_format_date($form['expiry']) . '<i class="glyphicon glyphicon-tag"></i></button></div>';
        } else {
            echo '<div><button class="btn btn-xs btn-success" type="image" data-toggle="collapse" href="#lm_dialog_lot" >' . $form['identifier'] . '<i class="glyphicon glyphicon-tag" ></i></button></div>';
        }
    }
    ?>
    <div id="lm_dialog" class="collapse" >
      <div class="form-group">
        <div>
          <input type="file" onchange="this.form.submit();" name="docfile_">
			 </div>
		  </div>
    </div>

    <div id="lm_dialog_lot" class="collapse" >
      <div class="form-group">
        <div>
          <label>Numero: </label><input type="text" name="identifier" value="<?php echo $form['identifier']; ?>" >
          <br>
          <label>Scadenza: </label><input class="datepicker" type="text" onchange="this.form.submit();" name="expiry"  value="<?php echo $form['expiry']; ?>">
        </div>
      </div>
    </div>
    <?php
  } else {
      echo '<tr><td><input type="hidden" name="filename" value="' . $form['filename'] . '">';
      echo '<input type="hidden" name="identifier" value="' . $form['identifier'] . '">';
      echo '<input type="hidden" name="id_lotmag" value="' . $form['id_lotmag'] . '">';
      echo '<input type="hidden" name="expiry" value="' . $form['expiry'] . '"></td></tr>';
  }

    // fine LOTTI in entrata
} else { //se è produzione agricola
  echo "<tr><td><input type=\"hidden\" name=\"nmov\" value=\"0\">";
  echo "<input type=\"hidden\" name=\"nmovdb\" value=\"\">\n";
  // echo "<input type=\"hidden\" name=\"staff0\" value=\"\">\n";
  echo "<input type=\"hidden\" name=\"filename\" value=\"\">\n";
  echo "<input type=\"hidden\" name=\"expiry\" value=\"\">\n";
  echo "<input type=\"hidden\" name=\"identifier\" value=\"\">\n";
  echo "<input type=\"hidden\" name=\"id_lotmag\" value=\"\">\n";
	echo "<input type=\"hidden\" name=\"SIAN\" value=\"\">\n";
	echo "<input type=\"hidden\" name=\"quality\" value=\"\">\n";
  echo "<input type=\"hidden\" name=\"lot_or_serial\" value=\"\"></td></tr>";
}
echo '<tr><td colspan=2 class="FacetFooterTD text-center" >';
$disabled="";
if ($submit=="NO"){
  $disabled="disabled";
  $title="C'è un componente KO oppure un problema nei movimenti del recipiente di stoccaggio: non puoi procedere";
}
if ($toDo == 'update') {
    echo '<input type="submit" accesskey="m" title="'.$title.'" class="btn btn-warning" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="' . ucfirst($script_transl['update']) . $addvalue . '" '.$disabled.'>';
} else {
    echo '<input type="submit" accesskey="i" title="'.$title.'" class="btn btn-warning" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="' . ucfirst($script_transl['insert']) . $addvalue . '" '.$disabled.'>';
}
echo "</td></tr></table>\n";
?>
</form>
<?php
require ("../../library/include/footer.php");
?>
