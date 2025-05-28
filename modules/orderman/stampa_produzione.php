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
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
require("../../modules/magazz/lib.function.php");
$mag = new magazzForm;
$resord = gaz_dbi_get_row($gTables['orderman'], "id", intval($_GET['id_orderman']));
$what_print = gaz_dbi_get_row($gTables['company_config'], 'var', 'orderman_report_choice')['val']; // scelgo cosa stampare sul report: ordini d'acquisto (0) , fatture d'acquisto(2) o entrambi (1)
$now = new DateTime();
$luogo_data=$admin_aziend['citspe'].", lì ".$now->format('d-m-Y');
require("../../config/templates/report_template.php");
$title = array('luogo_data'=>$luogo_data,
           'title'=>"RIEPILOGO della produzione n.".intval($_GET['id_orderman']).' - '.$resord['description'],
           'hile'=>array(array('lun' => 16,'nam'=>'Numero'),
						array('lun' => 18,'nam'=>'Data'),
						array('lun' => 45,'nam'=>'Cliente'),
						array('lun' => 55,'nam'=>'Descrizione'),
						array('lun' => 30,'nam'=>'Informazioni'),
                         array('lun' => 15,'nam'=>'Articolo'),
                         array('lun' => 15,'nam'=>'Quantità'),
                         array('lun' => 30,'nam'=>'Lotto'),
                         array('lun' => 18,'nam'=>'Scadenza'),
						 array('lun' => 25,'nam'=>'Luogo'),
						 array('lun' => 10,'nam'=>'Durata'),
                        )
          );
$aRiportare = array('top' => array(array('lun' => 166, 'nam' => 'da riporto : '),
        array('lun' => 20, 'nam' => '')
    ),
    'bot' => array(array('lun' => 166, 'nam' => 'a riportare : '),
        array('lun' => 20, 'nam' => '')
    )
);

$restes = gaz_dbi_get_row($gTables['tesbro'], "id_tes", $resord['id_tesbro']);
$resrig = gaz_dbi_get_row($gTables['rigbro'], "id_rig", $resord['id_rigbro']);
$resrig = ($resrig)?$resrig:array('codart'=>'','quanti'=>0);
$resclfo = $restes?gaz_dbi_get_row($gTables['clfoco'], "codice", $restes['clfoco']):false;
$resclfo = ($resclfo)?$resclfo:array('descri'=>'');
$reslot = gaz_dbi_get_row($gTables['lotmag'], "id", $resord['id_lotmag']);
$reslot = ($reslot)?$reslot:array('identifier'=>'','expiry'=>0);
$resart = gaz_dbi_get_row($gTables['artico'], "codice", $resrig['codart']);
$rescamp = gaz_dbi_get_row($gTables['campi'], "codice", $resord['campo_impianto']);
$rescamp = ($rescamp)?$rescamp:array('descri'=>'');

$pdf = new Report_template('L','mm','A4',true,'UTF-8',false,true);
$pdf->setVars($admin_aziend,$title);
$pdf->SetFooterMargin(15);
$pdf->setRiporti('');
$pdf->AddPage();
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
$pdf->SetFont('helvetica','',8);
$pdf->setJPEGQuality(15);
$n="";
$pdf->Cell(16,4,$resord['id'],1, 0, 'C', 0, '', 1);
$pdf->Cell(18,4,($restes?gaz_format_date($restes['datemi']):''),1, 0, 'C', 0, '', 1);
$pdf->Cell(45,4,substr($resclfo['descri'],0,35),1, 0, 'L', 0, '', 1);
$pdf->Cell(55,4,substr($resord['description'],0,40),1, 0, 'L', 0, '', 1);
$pdf->Cell(30,4,substr($resord['add_info'],0,30),1, 0, 'L', 0, '', 1);
$pdf->Cell(15,4,$resrig['codart'],1, 0, 'L', 0, '', 1);
if ($resrig['quanti']>=0.01){
	$pdf->Cell(15,4,$resrig['quanti'],1, 0, 'L', 0, '', 1);
} else {
	$pdf->Cell(15,4,'',1, 0, 'L', 0, '', 1);
}
$pdf->Cell(30,4,substr($reslot['identifier'],0,30),1, 0, 'L', 0, '', 1);
if (strlen($reslot['expiry'])>=10){
	$pdf->Cell(18,4,gaz_format_date($reslot['expiry']),1, 0, 'L', 0, '', 1);
} else {
	$pdf->Cell(18,4,'',1, 0, 'L', 0, '', 1);
}
$pdf->Cell(25,4,substr($rescamp['descri'],0,25),1, 0, 'L', 0, '', 1);
$pdf->Cell(10,4,(($resord['duration']>=0.01)?$resord['duration']:''),1, 1, 'C', 0, '', 1);
$pdf->Ln(2);


// INIZIO STAMPA LAVORI
// Antonio de Vincentiis - Stampo le eventuali lavorazioni documentate tramite il contenuto di tesbro con tipdoc "PRW" e relativi rigbro, dalla 7.35 staff_worked_hours conterrà il riferimento a detto documento con la nuova colonna id_tes
$tablejoin = $gTables['rigbro']." LEFT JOIN ".$gTables['tesbro']." ON ".$gTables['rigbro'].".id_tes = ".$gTables['tesbro'].".id_tes
    LEFT JOIN ".$gTables['staff']." ON ".$gTables['rigbro'].".id_body_text=".$gTables['staff'].".id_staff
    LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['staff'].".id_clfoco = ".$gTables['clfoco'].".codice";
$result = gaz_dbi_dyn_query ($gTables['tesbro'].".datemi,
	".$gTables['rigbro'].".descri,
	".$gTables['rigbro'].".quanti,
	".$gTables['rigbro'].".prelis,
	".$gTables['staff'].".id_staff,
	".$gTables['clfoco'].".descri AS des_staff",
    $tablejoin, $gTables['rigbro'].".id_orderman = ".intval($_GET['id_orderman'])." AND ".$gTables['tesbro'].".tipdoc ='PRW'" , $gTables['tesbro'].".datemi ASC, ".$gTables['tesbro'].".id_tes ASC");

$tot_prw_v=0;
$tot_prw_h=0;
if ($result->num_rows >0) {
  $pdf->SetFillColor(255,208,208);
  $pdf->Cell(277,5,'LAVORI ESEGUITI','LTR', 1, 'L', 1, '', 1);
  $pdf->Cell(20,5,'Data','LBR',0,'L',1);
  $pdf->Cell(80,5,'Addetto','LBR',0,'L',1);
  $pdf->Cell(105,5,'Descrizione','LBR',0,'L',1);
  $pdf->Cell(20,5,'Ore','LBR',0,'C',1);
  $pdf->Cell(25,5,'Costo','LBR',0,'R',1);
  $pdf->Cell(27,5,'Importo','LBR',1,'R',1);
  while($r = $result->fetch_assoc()){
    $pdf->Cell(20,5,gaz_format_date($r['datemi']),'LBR',0,'C');
    $pdf->Cell(80,5,$r['des_staff'],'LBR',0,'L');
    $pdf->Cell(105,5,$r['descri'],'LBR',0,'L', 0, '', 1);
    $pdf->Cell(20,5,floatval($r['quanti']),'LBR',0,'C');
    $pdf->Cell(25,5,gaz_format_number($r['prelis']),'LBR',0,'R');
    $pdf->Cell(27,5,gaz_format_number($r['quanti']*$r['prelis']),'LBR',1,'R');
    $tot_prw_v+=($r['quanti']*$r['prelis']);
    $tot_prw_h+=$r['quanti'];
  }
  $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
  $pdf->SetFont('helvetica','B',9);
  $pdf->Cell(205,5,' TOTALI DEI LAVORI ','LBT',0,'R',1);
  $pdf->Cell(20,5,round($tot_prw_h),'LBR',0,'C',1);
  $pdf->Cell(25,5,'medio: '.gaz_format_number(round($tot_prw_v/$tot_prw_h,2)),'LBR',0,'R',1);
  $pdf->Cell(27,5,gaz_format_number($tot_prw_v),'LBR',1,'R',1);
  $pdf->SetFont('helvetica','',8);
  $pdf->Ln(5);
} else {
	// Antonio Germani - Stampa operai
	$query="SELECT id_staff FROM ".$gTables['staff_worked_hours']. " WHERE id_orderman = ". intval($_GET['id_orderman'])." GROUP BY id_staff";
	$resoper = gaz_dbi_query($query);
	if ($resoper->num_rows > 0 ) {
		$pdf->Cell(39.5, 4, 'ELENCO ADDETTI:',1,1,'C',1,'',0);
    $nr=0;
		while($row = $resoper->fetch_assoc()){
      $nr++;
			$resstaff = gaz_dbi_get_row($gTables['staff'], "id_staff", $row['id_staff']);
			$resnome = gaz_dbi_get_row($gTables['clfoco'], "codice", $resstaff['id_clfoco']);
      $carry=0;
      if ( $nr > 6 ) {
        $carry = 1;
        $nr=0;
      }
			$pdf->Cell(39.5, 4, $resnome['descri'],1,$carry,'C', 0, '', 1);
		}
	}
	$pdf->Cell(0,0,'',0,1);$pdf->Cell(0,0,'',0,1);
}
// FINE STAMPA LAVORI


// STAMPA LISTA ORDINI
$tot_aor=0.00;
if ($what_print<=1){
$title = array('luogo_data'=>$luogo_data,
           'title'=>"RIEPILOGO della produzione n.".intval($_GET['id_orderman']).' - '.$resord['description'],
           'hile'=>array()
          );

$pdf->setVars($admin_aziend,$title,false);

$ctrlAOR=0;
$ctrlAORtot=0.00;
$query="SELECT *,".$gTables['rigbro'].".descri AS rigdes, ".$gTables['rigbro'].".sconto AS scorig FROM ".$gTables['rigbro']. "
LEFT JOIN ".$gTables['tesbro']. " ON ".$gTables['rigbro'].".id_tes = ".$gTables['tesbro'].".id_tes
LEFT JOIN ".$gTables['clfoco']. " ON ".$gTables['tesbro'].".clfoco = ".$gTables['clfoco'].".codice
LEFT JOIN ".$gTables['anagra']. " ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id
WHERE tipdoc='AOR' AND ".$gTables['rigbro'].".id_orderman =".intval($_GET['id_orderman'])." ORDER BY datemi ASC, ".$gTables['tesbro'].".id_tes ASC";
$res=gaz_dbi_query($query);
$pdf->SetFillColor(255,199,199);

while($row=$res->fetch_assoc()){
	switch ($row['tiprig']){
	    case "0": // normale
	    case "50": // normale c/allegato
			$amount=CalcolaImportoRigo($row['quanti'],$row['prelis'],$row['scorig']);
        break;
		case "1": //forfait
			$amount=CalcolaImportoRigo(1,$row['prelis'],$row['scorig']);
        break;
		default:
		$amount=0;
	}
	$tot_aor+=$amount;
    $fillcell=($amount>=0.00001)?false:'1';
	$ctrlAORtot+=$amount;
	if ($ctrlAOR==0){
		$pdf->Cell(277,5,'LISTA DEGLI ORDINI A FORNITORI','LTR', 1, 'L', 1, '', 1);
		$pdf->Cell(105,5,'Fornitore','LBR',0,'L',1);
		$pdf->Cell(82,5,'descrizione acquisto','LBR',0,'L',1);
		$pdf->Cell(10,5,'U.M.','LBR',0,'C',1);
		$pdf->Cell(20,5,'quantità','LBR',0,'R',1);
		$pdf->Cell(20,5,'prezzo','LBR',0,'R',1);
		$pdf->Cell(10,5,'sconto','LBR',0,'C',1);
		$pdf->Cell(30,5,'importo','LBR',1,'R',1);
		$totord=0.00;
	} elseif ($ctrlAOR<>$row['id_tes']) {
		$pdf->Cell(85,5);
        $pdf->SetFillColor(230,230,230);
        $pdf->Cell(162,4,'Totale ordine n.'.$d['numdoc'].' del '.gaz_format_date($d['datemi']).' a '.$d['descri'].' € ','LBT', 0, 'R', 1, '', 1);
        $pdf->SetFont('helvetica','B',8);
        $pdf->Cell(30,4,gaz_format_number($totord),'RBT', 1, 'R', 1, '', 1);
        $pdf->SetFont('helvetica','',8);
        $pdf->Ln(2);
        $pdf->SetFillColor(255,199,199);
		$totord=0.00;
    }

	if ($ctrlAOR<>$row['id_tes']){
		$pdf->Cell(277,5,$row['descri'].' ORDINE n.'.$row['numdoc'].' del '.gaz_format_date($row['datemi']),1, 1, 'L', 0, '', 1);
		if ($amount>=0.01&&$ctrlAORtot==0.00){ // è cambiato l'ordine ma il precedente ha un totale a zero...
			$pdf->SetTextColor(255,0,0);
			$pdf->Cell(105,4);
			$pdf->Cell(172,5,' O R D I N E   D I   V A L O R E    N U L L O   ! ?',1,1,'C');
			$pdf->SetTextColor(0);
		}

	}
	if ($row['quanti']>=0.00001){
		$pdf->Cell(85,5);
		$pdf->Cell(20,5,$row['codart'],1,0,'C',0,'',1);
		$pdf->Cell(82,5,$row['rigdes'],1,0,'L',0,'',1);
		$pdf->Cell(10,5,$row['unimis'],1,0,'C');
		$pdf->Cell(20,5,floatval($row['quanti']),1,0,'R');
		$pdf->Cell(20,5,floatval($row['prelis']),1,0,'R');
		$pdf->Cell(10,5,floatval($row['scorig']),1,0,'C');
		$pdf->Cell(30,5,gaz_format_number($amount),1, 1,'C',$fillcell,'',1);
	}
    $totord+=$amount;
	$ctrlAOR=$row['id_tes'];
    $d=$row;
}
if ($tot_aor>=0.01){
	$pdf->Cell(85,5);
    $pdf->SetFillColor(230,230,230);
    $pdf->Cell(162,4,'Totale ordine n.'.$d['numdoc'].' del '.gaz_format_date($d['datemi']).' a '.$d['descri'].' € ','LBT', 0, 'R', 1, '', 1);
    $pdf->SetFont('helvetica','B',8);
    $pdf->Cell(30,4,gaz_format_number($totord),'RBT', 1, 'R', 1, '', 1);
    $pdf->Ln(2);
    $pdf->SetFont('helvetica','B',9);
	$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
	$pdf->Cell(247,4,'TOTALE DELL\'ORDINATO: ','LBT', 0, 'R', 1, '', 1);
	$pdf->Cell(30,4,'€ '.gaz_format_number($tot_aor),'RBT', 1, 'R', 1, '', 1);
    $pdf->SetFont('helvetica','',8);

}
}
// FINE STAMPA LISTA ORDINI

// STAMPA LISTA DOCUMENTI D'ACQUISTO
$tot_acq=0.00;
if ($what_print>=1) {
  $ctrlACQ=0;
  $ctrlACQtot=0.00;
  $query="SELECT *,".$gTables['rigdoc'].".descri AS rigdes, ".$gTables['rigdoc'].".sconto AS scorig FROM ".$gTables['rigdoc']. "
  LEFT JOIN ".$gTables['tesdoc']. " ON ".$gTables['rigdoc'].".id_tes = ".$gTables['tesdoc'].".id_tes
  LEFT JOIN ".$gTables['clfoco']. " ON ".$gTables['tesdoc'].".clfoco = ".$gTables['clfoco'].".codice
  LEFT JOIN ".$gTables['anagra']. " ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id
  WHERE (tipdoc LIKE 'AF_' OR (tipdoc LIKE 'AD_' AND numfat < 1)) AND ".$gTables['rigdoc'].".id_orderman =".intval($_GET['id_orderman'])." ORDER BY datemi ASC, ".$gTables['tesdoc'].".id_tes ASC";
  $res=gaz_dbi_query($query);
  $pdf->SetFillColor(255,199,199);
  $tipdoc_descri=['AFA'=>'Fattura','AFT'=>'DdT (fatturato)','ADT'=>'D.d.T.','AFC'=>'Nota Credito'];
  while($row=$res->fetch_assoc()){
    if ($row['tipdoc']=='AFC') { $row['prelis']=-$row['prelis']; }
    switch ($row['tiprig']){
        case "0": // normale
        case "50": // normale c/allegato
        $amount=CalcolaImportoRigo($row['quanti'],$row['prelis'],$row['scorig']);
          break;
      case "1": //forfait
        $amount=CalcolaImportoRigo(1,$row['prelis'],$row['scorig']);
          break;
      default:
      $amount=0;
    }
    $tot_acq+=$amount;
      $fillcell=($amount>=0.00001)?false:'1';
    if ($ctrlACQ==0){
      $pdf->Cell(277,5,'LISTA DEGLI ACQUISTI A FORNITORI','LTR', 1, 'L', 1, '', 1);
      $pdf->Cell(105,5,'Fornitore','LBR',0,'L',1);
      $pdf->Cell(82,5,'descrizione acquisto','LBR',0,'L',1);
      $pdf->Cell(10,5,'U.M.','LBR',0,'C',1);
      $pdf->Cell(20,5,'quantità','LBR',0,'R',1);
      $pdf->Cell(20,5,'prezzo','LBR',0,'R',1);
      $pdf->Cell(10,5,'sconto','LBR',0,'C',1);
      $pdf->Cell(30,5,'importo','LBR',1,'R',1);
    } elseif ($ctrlACQ<>$row['id_tes']) {
      $pdf->Cell(85,5);
          $pdf->SetFillColor(230,230,230);
          $pdf->Cell(162,4,'Totale '.$tipdoc_descri[$row['tipdoc']].' n.'.$d['numdoc'].' del '.gaz_format_date($d['datemi']).' a '.$d['descri'].' € ','LBT', 0, 'R', 1, '', 1);
          $pdf->SetFont('helvetica','B',8);
          $pdf->Cell(30,4,gaz_format_number($ctrlACQtot),'RBT', 1, 'R', 1, '', 1);
          $pdf->SetFont('helvetica','',8);
          $pdf->Ln(2);
          $pdf->SetFillColor(255,199,199);
      }

    if ($ctrlACQ<>$row['id_tes'] && $ctrlACQ>0){
      $pdf->Cell(277,5,$row['descri'].' '.$tipdoc_descri[$row['tipdoc']].' n.'.$row['numdoc'].' del '.gaz_format_date($row['datemi']),1, 1, 'L', 0, '', 1);
      if ($amount>=0.01&&$ctrlACQtot==0.00){ // è cambiato l'ordine ma il precedente ha un totale a zero...
        $pdf->SetTextColor(255,0,0);
        $pdf->Cell(105,4);
        $pdf->Cell(172,5,' A C Q U I S T O  D I   V A L O R E    N U L L O   ! ?',1,1,'C');
        $pdf->SetTextColor(0);
      }
      $ctrlACQtot=0.00;
    }
    $ctrlACQtot+=$amount;
    if ($row['quanti']>=0.00001){
      $pdf->Cell(85,5);
      $pdf->Cell(20,5,$row['codart'],1,0,'C',0,'',1);
      $pdf->Cell(82,5,$row['rigdes'],1,0,'L',0,'',1);
      $pdf->Cell(10,5,$row['unimis'],1,0,'C');
      $pdf->Cell(20,5,floatval($row['quanti']),1,0,'R');
      $pdf->Cell(20,5,floatval($row['prelis']),1,0,'R');
      $pdf->Cell(10,5,floatval($row['scorig']),1,0,'C');
      $pdf->Cell(30,5,gaz_format_number($amount),1, 1,'C',$fillcell,'',1);
    }
    $ctrlACQ=$row['id_tes'];
      $d=$row;
  }
  if ($tot_acq>=0.01){
    $pdf->Cell(85,5);
    $pdf->SetFillColor(230,230,230);
    $pdf->Cell(162,4,'Totale  n.'.$d['numdoc'].' del '.gaz_format_date($d['datemi']).' a '.$d['descri'].' € ','LBT', 0, 'R', 1, '', 1);
    $pdf->SetFont('helvetica','B',8);
    $pdf->Cell(30,4,gaz_format_number($ctrlACQtot),'RBT', 1, 'R', 1, '', 1);
    $pdf->Ln(2);
    $pdf->SetFont('helvetica','B',9);
    $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
    $pdf->Cell(247,4,'TOTALE DEGLI ACQUISTI A FORNITORI: ','LBT', 0, 'R', 1, '', 1);
    $pdf->Cell(30,4,'€ '.gaz_format_number($tot_acq),'RBT', 1, 'R', 1, '', 1);
    $pdf->SetFont('helvetica','',8);

  }
}
// FINE STAMPA LISTA DOCUMENTI D'ACQUISTO

$pdf->setRiporti('');

// INIZIO REPORT MOVIMENTI DI MAGAZZINO GENERATI DALLA PRODUZIONE
$sql="SELECT *, ".$gTables['rigbro'].".descri AS rigdes, ".$gTables['rigbro'].".id_orderman AS id_om, ".$gTables['rigbro'].".status AS rd_status, ".$gTables['rigbro'].".sconto AS scorig, ".$gTables['tesbro'].".numdoc AS tbnd , ".$gTables['assets'].".descri AS desmach  FROM ".$gTables['rigbro']."
LEFT JOIN ".$gTables['tesbro']." ON ".$gTables['rigbro'].".id_tes = ".$gTables['tesbro'].".id_tes
LEFT JOIN ".$gTables['assets']." ON ".$gTables['tesbro'].".id_parent_doc = ".$gTables['assets'].".id
LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['tesbro'].".clfoco = ".$gTables['clfoco'].".codice
LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id
WHERE tipdoc='PR4' AND ".$gTables['rigbro'].".id_orderman AND NOT EXISTS ( SELECT  null FROM ".$gTables['movmag']." mm WHERE ".$gTables['rigbro'].".id_mag = mm.id_mov )
ORDER BY datemi ASC, ".$gTables['tesbro'].".id_tes ASC;";
$res_orph = gaz_dbi_query ($sql);
while ($orph = gaz_dbi_fetch_array($res_orph)) {
  $id_mag=$mag->uploadMag($orph['id_rig'], 'PR4', $orph['tbnd'], $orph['id_parent_doc'],  $orph['datemi'], $orph['status'], 0, 81, $orph['codart'], $orph['quanti'],$orph['prelis'], 0, 0, $admin_aziend['stock_eval_method'], false, $orph['rd_status'], 0,$orph['id_om'],0,'Lavor. nesting '.addslashes($orph['codice_fornitore']).' dim.utile '.$orph['lunghezza'].'x'.$orph['larghezza'].'x'.$orph['spessore']);
  gaz_dbi_put_row($gTables['rigbro'], 'id_rig', $orph['id_rig'], 'id_mag', $id_mag); // inserisco il riferimento movmag nel rigo
}
// controllo se ho dei feedback di lavorazioni provenienti da macchine 4.0 che pur risultando aver movimentato il magazzino in reatà sono orfani dello stesso, se orfani li reinserisco

// fine controllo rigdoc senza movmag
$where = $gTables['movmag'].". id_orderman = ".intval($_GET['id_orderman'])." AND quanti > 0 AND ".$gTables['movmag'].".operat = -1 ";
$what = $gTables['movmag'].".*, ".
        $gTables['caumag'].".codice, ".$gTables['caumag'].".descri AS descau, ".
        $gTables['assets'].".descri AS desass, ".
        $gTables['clfoco'].".codice, ".
        $gTables['orderman'].".id AS id_orderman, ".$gTables['orderman'].".description AS desorderman, ".
        $gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2, ".
        $gTables['artico'].".codice, ".$gTables['artico'].".descri AS desart, ".$gTables['artico'].".web_url, ".$gTables['artico'].".unimis, ".$gTables['artico'].".scorta, ".$gTables['artico'].".image, ".$gTables['artico'].".catmer ";
        $table=$gTables['movmag']." LEFT JOIN ".$gTables['caumag']." ON ".$gTables['movmag'].".caumag = ".$gTables['caumag'].".codice
               LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['movmag'].".clfoco = ".$gTables['clfoco'].".codice
               LEFT JOIN ".$gTables['assets']." ON ".$gTables['movmag'].".id_assets = ".$gTables['assets'].".id
               LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra
               LEFT JOIN ".$gTables['orderman']." ON ".$gTables['movmag'].".id_orderman = ".$gTables['orderman'].".id
               LEFT JOIN ".$gTables['artico']." ON ".$gTables['movmag'].".artico = ".$gTables['artico'].".codice";
$result = gaz_dbi_dyn_query ($what, $table,$where,"datreg ASC, id_mov ASC");
$numrow = gaz_dbi_num_rows($result);
$tot_mom_v=0;
if ($numrow>=1){
  $title = array('luogo_data'=>$luogo_data,
         'title'=>"RIEPILOGO della produzione n.".intval($_GET['id_orderman']).' - '.$resord['description'],
         'hile'=>array(array('lun' => 20,'nam'=>'Data'),
                       array('lun' => 37,'nam'=>'Causale'),
					array('lun' => 87,'nam'=>'Operazione'),
					array('lun' => 30,'nam'=>'Codice'),
					array('lun' => 62,'nam'=>'Articolo'),
					array('lun' => 17,'nam'=>'Quantità'),
                       array('lun' => 7,'nam'=>'U.M.'),
					array('lun' => 17,'nam'=>'Prezzo')
                      )
        );
  $pdf->setVars($admin_aziend,$title,false);
  $pdf->SetFillColor(255,199,199);
  $pdf->Ln(5);
	$pdf->Cell(277,4,'MOVIMENTI DI MAGAZZINO RELATIVI ALLA PRODUZIONE',1, 1, 'C', 1, '', 1);
	$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
	$pdf->Cell(20,4,'Data',1, 0, 'C', 0, '', 1);
  $pdf->Cell(99,4,'Descrizione scarico',1, 0, 'C', 0, '', 1);
  $pdf->Cell(30,4,'Codice',1, 0, 'C', 0, '', 1);
  $pdf->Cell(37,4,'Descrizione articolo',1, 0, 'L', 0, '', 1);
  $pdf->Cell(17,4,'Quantità',1, 0, 'R', 0, '', 1);
  $pdf->Cell(7,4,'U.m.',1, 0, 'C', 0, '', 1);
	$pdf->Cell(25, 4,'Lotto',1, 0, 'L', 0, '', 1);
	$pdf->Cell(25, 4, 'Scadenza' ,1, 0, 'L', 0, '', 1);
  $pdf->Cell(17,4,'Prezzo unit.',1, 1, 'C', 0, '', 1);
  $totq=0;

  while ($mv = gaz_dbi_fetch_array($result)) {
    $totq+=floatval($mv['quanti']*$mv['operat']);
    $tot_mom_v+=floatval($mv['quanti']*$mv['prezzo']);
    $desop=(isset($mv['desass'])&&strlen($mv['desass'])>2)?$mv['desdoc'].' con '.$mv['desass']:$mv['desdoc'];
    $pdf->Cell(20,4,gaz_format_date($mv['datreg']),1, 0, 'C', 0, '', 1);
    $pdf->Cell(37,4,$mv['descau'],1, 0, 'C', 0, '', 1);
    $pdf->Cell(62,4,$desop,1, 0, 'L', 0, '', 1);
    $pdf->Cell(30,4,$mv['codice'],1, 0, 'C', 0, '', 1);
    $pdf->Cell(37,4,$mv['desart'],1, 0, 'L', 0, '', 1);
    $pdf->Cell(17,4,floatval($mv['quanti']),1, 0, 'R', 0, '', 1);
    $pdf->Cell(7,4,$mv['unimis'],1, 0, 'C', 0, '', 1);
    $reslot = gaz_dbi_get_row($gTables['lotmag'], "id", $mv['id_lotmag']);
    $pdf->Cell(25, 4, ($reslot)?$reslot['identifier']:'',1, 0, 'L', 0, '', 1);
    if (isset($reslot) AND $reslot['expiry']==0) {
      $pdf->Cell(25, 4, "" ,1, 0, 'L', 0, '', 1);
    } else {
      $pdf->Cell(25, 4, ($reslot)?gaz_format_date($reslot['expiry']):'' ,1, 0, 'L', 0, '', 1);
    }
    $pdf->Cell(17,4,number_format($mv['prezzo'],$admin_aziend['decimal_price'],$admin_aziend['decimal_symbol'], $admin_aziend['thousands_symbol']),1, 1, 'C', 0, '', 1);
  }
  $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
  $pdf->SetFont('helvetica','B',9);
  $pdf->Cell(186,5,'TOTALE MATERIALE LAVORATO: ','LBT', 0, 'R', 1, '', 1);
  $pdf->Cell(17,5,abs($totq),'BT', 0, 'R', 1, '', 1);
  $pdf->Cell(57,5,'','BT', 0, 'R', 1, '', 1);
  $pdf->Cell(17,5,'€ '.number_format($tot_mom_v, $admin_aziend['decimal_price'], $admin_aziend['decimal_symbol'], $admin_aziend['thousands_symbol']),'RBT', 1, 'R', 1, '', 1);
}
// FINE REPORT MOVIMENTI DI MAGAZZINO GENERATI DALLA PRODUZIONE

// INIZIO REPORT DELLE LAVORAZIONI DELLE MACCHINE 4.0
$tot_pr4=0.00;
$query="SELECT *,".$gTables['rigbro'].".descri AS rigdes, ".$gTables['rigbro'].".sconto AS scorig , ".$gTables['assets'].".descri AS desmach FROM ".$gTables['rigbro']. "
  LEFT JOIN ".$gTables['tesbro']. " ON ".$gTables['rigbro'].".id_tes = ".$gTables['tesbro'].".id_tes
  LEFT JOIN ".$gTables['assets']. " ON ".$gTables['tesbro'].".id_parent_doc = ".$gTables['assets'].".id
  LEFT JOIN ".$gTables['clfoco']. " ON ".$gTables['tesbro'].".clfoco = ".$gTables['clfoco'].".codice
  LEFT JOIN ".$gTables['anagra']. " ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id
  WHERE tipdoc='PR4' AND ".$gTables['rigbro'].".id_mag = 0 AND ".$gTables['rigbro'].".id_orderman =".intval($_GET['id_orderman'])." ORDER BY datemi ASC, ".$gTables['tesbro'].".id_tes ASC";
$res=gaz_dbi_query($query);
if ($res){
  $pdf->Ln(5);
  $pdf->SetFont('helvetica','',8);
  $ctrlPR4=0;
  $ctrlPR4tot=0.00;
  $pdf->SetFillColor(210,210,251);
  while($row=$res->fetch_assoc()){
    switch ($row['tiprig']){
        case "0": // normale
        case "50": // normale c/allegato
        $amount=CalcolaImportoRigo($row['quanti'],$row['prelis'],$row['scorig']);
          break;
      case "1": //forfait
        $amount=CalcolaImportoRigo(1,$row['prelis'],$row['scorig']);
          break;
      default:
      $amount=0;
    }
    $tot_pr4+=$amount;
    $fillcell=($amount>=0.00001)?false:'1';
    $ctrlPR4tot+=$amount;
    if ($ctrlPR4==0){
      $pdf->Cell(277,5,'LISTA DELLE LAVORAZIONI CON MACCHINE 4.0','LTR', 1, 'L', 1, '', 1);
      $pdf->Cell(105,5,'Macchina','LBR',0,'L',1);
      $pdf->Cell(82,5,'descrizione lavorazione','LBR',0,'L',1);
      $pdf->Cell(10,5,'U.M.','LBR',0,'C',1);
      $pdf->Cell(20,5,'quantità','LBR',0,'R',1);
      $pdf->Cell(25,5,'prezzo','LBR',0,'R',1);
      $pdf->Cell(35,5,'importo','LBR',1,'R',1);
      $totord=0.00;
    } elseif ($ctrlPR4<>$row['id_tes']) {
      $pdf->Cell(85,5);
      $pdf->SetFillColor(230,230,230);
      $pdf->Cell(157,4,'Totale flusso n.'.$d['numdoc'].' del '.gaz_format_date($d['datemi']).' a '.$d['descri'].' € ','LBT', 0, 'R', 1, '', 1);
      $pdf->SetFont('helvetica','B',8);
      $pdf->Cell(35,4,gaz_format_number($totord),'RBT', 1, 'R', 1, '', 1);
      $pdf->SetFont('helvetica','',8);
      $pdf->Ln(2);
      $pdf->SetFillColor(210,210,251);
      $totord=0.00;
    }

    if ($ctrlPR4<>$row['id_tes']){
      $pdf->Cell(277,5,$row['descri'].' FLUSSO n.'.$row['numdoc'].' del '.gaz_format_date($row['datemi']),1, 1, 'L', 0, '', 1);
      if ($amount>=0.01&&$ctrlPR4tot==0.00){ // è cambiato l'ordine ma il precedente ha un totale a zero...
        $pdf->SetTextColor(255,0,0);
        $pdf->Cell(105,4);
        $pdf->Cell(172,5,' L A V O R A Z I O N E   D I   V A L O R E    N U L L O   ! ?',1,1,'C');
        $pdf->SetTextColor(0);
      }

    }
    if ($row['quanti']>=0.00001){
      $pdf->Cell(85,5);
      $pdf->Cell(20,5,$row['desmach'],1,0,'C',0,'',1);
      $pdf->Cell(82,5,$row['rigdes'],1,0,'L',0,'',1);
      $pdf->Cell(10,5,$row['unimis'],1,0,'C');
      $pdf->Cell(20,5,floatval($row['quanti']),1,0,'R');
      $pdf->Cell(25,5,floatval($row['prelis']),1,0,'R');
      $pdf->Cell(35,5,gaz_format_number($amount),1, 1,'C',$fillcell,'',1);
    }
    $totord+=$amount;
    $ctrlPR4=$row['id_tes'];
    $d=$row;
  }
  if ($tot_pr4>=0.01){
    $pdf->Cell(85,5);
    $pdf->SetFillColor(230,230,230);
    $pdf->Cell(162,4,'Totale flusso n.'.$d['numdoc'].' del '.gaz_format_date($d['datemi']).' a '.$d['descri'].' € ','LBT', 0, 'R', 1, '', 1);
    $pdf->SetFont('helvetica','B',8);
    $pdf->Cell(30,4,gaz_format_number($totord),'RBT', 1, 'R', 1, '', 1);
    $pdf->Ln(2);
    $pdf->SetFont('helvetica','B',9);
    $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
    $pdf->Cell(247,4,'TOTALE COSTO MACCHINA: ','LBT', 0, 'R', 1, '', 1);
    $pdf->Cell(30,4,'€ '.gaz_format_number($tot_pr4),'RBT', 1, 'R', 1, '', 1);
    $pdf->SetFont('helvetica','',8);
  }
}
// FINE REPORT DELLE LAVORAZIONI DELLE MACCHINE 4.0



// RIEPILOGO
if( $pdf->GetY() > 150 ){ $pdf->AddPage(); }
//if($what_print==1){ $tot_acq=0; } // se ho scelto di stampare sul report sia l'ordinato che l'acquistato tramite dtt-fatture non stampo quest'ultimo sul totale generale
$totgen=$tot_aor+$tot_acq+$tot_mom_v+$tot_prw_v+$tot_pr4;
if ($totgen>=0.01){
  $pdf->SetFont('helvetica','',9);
  $pdf->Ln(8);
  $pdf->Cell(70);
  $pdf->Cell(126,5,' R I E P I L O G O    T O T A L I',1, 1, 'C', 1, '', 1);
  if(($tot_prw_v+$tot_pr4)>=0.01){
	$pdf->Cell(70);
	$pdf->Cell(100,5,'COSTO DEL LAVORO: ','LBT', 0, 'L', 0, '', 1);
	$pdf->Cell(26,5,gaz_format_number($tot_prw_v+$tot_pr4),'RBT', 1, 'R', 0, '', 1);
  }
  if($tot_aor>=0.01){
	$pdf->Cell(70);
	$pdf->Cell(100,5,'MATERIALE ORDINATO: ','LBT', 0, 'L', 0, '', 1);
	$pdf->Cell(26,5,gaz_format_number($tot_aor),'RBT', 1, 'R', 0, '', 1);
  }
  if($tot_acq>=0.01){
	$pdf->Cell(70);
	$pdf->Cell(100,5,'MATERIALE ACQUISTATO: ','LBT', 0, 'L', 0, '', 1);
	$pdf->Cell(26,5,gaz_format_number($tot_acq),'RBT', 1, 'R', 0, '', 1);
  }
  if($tot_mom_v>=0.01){
	$pdf->Cell(70);
	$pdf->Cell(100,5,'MATERIALE LAVORATO: ','LBT', 0, 'L', 0, '', 1);
	$pdf->Cell(26,5,gaz_format_number($tot_mom_v),'RBT', 1, 'R', 0, '', 1);
	$pdf->SetFont('helvetica','B',10);
  }
  $pdf->Cell(70);
  $pdf->Cell(100,8,'TOTALE GENERALE: ','LBT', 0, 'R', 1, '', 1);
  $pdf->Cell(26,8,'€ '.gaz_format_number($totgen),'RBT', 1, 'R', 1, '', 1);
}
// FINE RIEPILOGO
$pdf->Output($title['title'].'.pdf','I');
?>
