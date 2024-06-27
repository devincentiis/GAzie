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
    NEGOZIABILITA` o di  APPLICABILITA` PER UN  PWorkerLARE SCOPO.  Si
    veda la Licenza Pubblica Generica GNU per avere maggiori dettagli.

    Ognuno dovrebbe avere   ricevuto una copia  della Licenza Pubblica
    Generica GNU insieme a   questo programma; in caso  contrario,  si
    scriva   alla   Free  Software Foundation, 51 Franklin Street,
    Fifth Floor Boston, MA 02110-1335 USA Stati Uniti.
 --------------------------------------------------------------------------
*/

require("../../library/include/datlib.inc.php");

$admin_aziend=checkAdmin();

if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
}

if (!isset($_GET['year']) || !isset($_GET['month'])) {
    header("Location: docume_humres.php");
    exit;
}

$dto = new DateTime();
$dto->setDate(intval($_GET['year']), intval($_GET['month']),1);
$dto->modify('first day of this month');
$first_day = $dto->format('Y-m-d');
$dto->modify('last day of this month');
$last_day = $dto->format('Y-m-d');
$aDates = array();
$st_date = strtotime($first_day);
$ed_date = strtotime($last_day);
//Luca 2018-11-14 Con il cambio dell'ora il report sballava
//for ($i = $st_date; $i <= $ed_date; $i += (60 * 60 * 24)) {
for ($i = $st_date; $i <= $ed_date; $i = mktime(0, 0, 0, date("m",$i)  , date("d",$i)+1, date("Y",$i)) ) {
	$currDate = array('strdate'=>date('Y-m-d', $i),'daydate'=>date('w',$i),'tsdate'=>$i);
	// in $aDates accumulo i giorni del mese
  $aDates[] = $currDate;
}
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data=$admin_aziend['citspe'].", lì ".ucwords($gazTimeFormatter->format(new DateTime()));

require("../../config/templates/report_template.php");
require("lang.".$admin_aziend['lang'].".php");
$script_transl=$strScript['employee_timesheet.php'];
$where=" (start_date <= '".$last_day."' ) AND (end_date < '2000-01-01' OR end_date IS NULL OR end_date > '".$first_day."')";
$what="*";
$tables=$gTables['staff'] . ' AS st LEFT JOIN ' . $gTables['clfoco'] . ' AS wo ON st.id_clfoco=wo.codice ';
$result = gaz_dbi_dyn_query($what, $tables, $where, 'id_staff');

$gazTimeFormatter->setPattern('MMMM yyyy');
$title = array('luogo_data'=>$luogo_data,
               'title'=>$script_transl['title'].' del mese di '.$gazTimeFormatter->format(new DateTime($first_day)),
               'hile'=>array(array('lun' => 40,'nam'=>$script_transl['header'][1]),
                             array('lun' => 20,'nam'=>$script_transl['header'][2],'col'=>array(255,255,255))
                            )
              );
// accodo a $title l'array dei giorni segnando le domeniche in rosso
$gazTimeFormatter->setPattern('E');
for ($i=0; $i<=30; $i++){
	if (isset($aDates[$i])){
		$col=array(255,255,255);
		if ($aDates[$i]['daydate']==0){
			$col=array(255,150,150);
		}
		if ($aDates[$i]['daydate']==6){
			$col=array(255,230,200);
		}
		$title['hile'][]=array('lun'=>7,'nam'=>substr($aDates[$i]['strdate'],-2)."\n". substr($gazTimeFormatter->format(new DateTime($aDates[$i]['strdate'])),0,2), 'col'=>$col);
	} else {
		$title['hile'][]=array('lun'=>7,'nam'=>" \n ");
	}
}

$pdf = new Report_template();
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(45);
$pdf->SetFooterMargin(10);
$config = new Config;
$pdf->SetFont('helvetica','',7);
$pdf->AddPage('L',$config->getValue('page_format'));
$ctrlWorker='';
$ctrl_id=0;
while ($mv = gaz_dbi_fetch_array($result)) {
    if ($ctrlWorker!=$mv['id_staff']) {
		if ($pdf->getY()>160){
			$pdf->AddPage('L',$config->getValue('page_format'));
		}
		$y=$pdf->getY();
		$init_y=$y;
		$anagrafica = new Anagrafica();
		$worker = $anagrafica->getPartner($mv['codice']);
		if (!empty($ctrlWorker)) { // non sono al primo lavoratore

		}
        $pdf->SetFillColor(hexdec(substr($pdf->colore, 0, 2)), hexdec(substr($pdf->colore, 2, 2)), hexdec(substr($pdf->colore, 4, 2)));
		$pdf->Cell(40,5,$mv['id_staff'].') '.$mv['descri'],'RTL',2,'L',1, '', 1);
		$pdf->Cell(40,5,$mv['job_title'],'RL',2, '', 0,'',1);
		$pdf->Cell(40,5,$worker['indspe'],'RL',2,'R');
 		$pdf->Cell(40,5,$worker['citspe'].' ('.$worker['prospe'].')','RL',2,'R');
 		$pdf->Cell(40,5,$worker['codfis'],'RL',2);
 		$pdf->Cell(40,5,'Tel.'.$worker['telefo'].' / '.$worker['cell'],'RBL',0,'R');
		$x=$pdf->getX();
		// ritorno al primo rigo del lavoratore
		$pdf->setXY($x,$y);
		$pdf->SetFillColor(220,220,220);
		$pdf->Cell(20,5,$script_transl['hours_normal'], 'T', 2, 'C', 0, '', 1);
		$pdf->Cell(20,5,$script_transl['hours_extra'],'RL',2, 'C', 1, '', 1);
		$pdf->Cell(20,5,$script_transl['absence_type'],'RL',2, 'C', 0, '', 1);
		$pdf->Cell(20,5,$script_transl['hours_absence'],'RL',2, 'C', 1, '', 1);
		$pdf->Cell(20,5,$script_transl['other_type'],'RL',2, 'C', 0, '', 1);
		$pdf->Cell(20,5,$script_transl['hours_other'],'RBL',0, 'C', 1, '', 1);
		$x=$pdf->getX();
		// attraverso il mese
		// inizializzo la legenda
		$leg_absence=array();
		$leg_other=array();
		$leg_note=array();
		for ($i=0; $i<=30; $i++){
			// ritorno al primo rigo del lavoratore
			$pdf->setXY($x,$y);
			if (isset($aDates[$i])) {
				$pdf->SetFillColor(255,255,255);
				$k=$i+1;
				// richiamo dal database i dati del giorno
				$work_h = gaz_dbi_get_row($gTables['staff_worked_hours'], "id_staff", $mv['id_staff'], "AND work_day = '{$aDates[$i]['strdate']}'");
				// riprendo pure tutte le note da staff_work_movements (cartellino)
				$card_res = gaz_dbi_dyn_query('note', $gTables['staff_work_movements'], "id_staff = " .intval($mv['id_staff']). " AND start_work BETWEEN '" . $aDates[$i]['strdate'] ." 00:00:00' AND '" . $aDates[$i]['strdate'] ." 23:59:59'");
				$accnote=(empty($work_h['note']))?'':$work_h['note'].', ';
				while($cr=gaz_dbi_fetch_array($card_res) ) {
					$accnote.=(empty($cr['note']))?'':$cr['note'].', ';
				}
				$accnote=substr($accnote,0,-2);
				if (isset($work_h) AND $work_h['hours_normal']>=0.01){
					$hn=floatval($work_h['hours_normal']);
				} else {
					$hn='-';
				}
				if (isset($work_h) AND $work_h['hours_extra']>=0.01){
					$he=floatval($work_h['hours_extra']);
				} else {
					$he='';
				}
				if (isset($work_h) AND $work_h['id_absence_type']>=1){
					$r_at = gaz_dbi_get_row($gTables['staff_work_type'], "id_work", $work_h['id_absence_type']);
					$at=$r_at['causal'];
					$leg_absence[$at]=$r_at['descri_ext'];
				} else {
					$at='';
				}
				if (isset($work_h) AND $work_h['hours_absence']>=0.01){
					$ha=number_format($work_h['hours_absence'],1,',','');
				} else {
					$ha='';
				}
				if (isset($work_h) AND $work_h['id_other_type']>=1){
					$r_ot = gaz_dbi_get_row($gTables['staff_work_type'], "id_work", $work_h['id_other_type']);
					$ot=$script_transl['work_type'][$r_ot['id_work_type']][0];
					$leg_other[$ot]= $script_transl['work_type'][$r_ot['id_work_type']][1].'=>'.$r_ot['descri'];
				} else {
					$ot='';
				}
				if (isset($work_h) AND $work_h['hours_other']>=0.01){
					$ho=floatval($work_h['hours_other']);
				} else {
					$ho='';
				}
				if (!empty($accnote)){
					$dn=gaz_format_date($aDates[$i]['strdate'],false,true);
					$leg_note[$dn]= $accnote;
				}
				if ($aDates[$i]['daydate']==0){
					$pdf->SetFillColor(255,150,150);
					$pdf->Cell(7,5,$hn,'RTL',2,'C',1);
				} else if ($aDates[$i]['daydate']==6){
					$pdf->SetFillColor(255,230,200);
					$pdf->Cell(7,5,$hn,'RTL',2,'C',1);
				} else {
					$pdf->Cell(7,5,$hn,'RTL',2,'C');
				}
				$pdf->SetFillColor(220,220,220);
				$pdf->Cell(7,5,$he,'RL',2,'L',1);
				$pdf->Cell(7,5,$at,'RL',2,'C');
				$pdf->Cell(7,5,$ha,'RL',2,'L',1);
				$pdf->Cell(7,5,$ot,'RL',2,'C');
				$pdf->Cell(7,5,$ho,'RBL',0,'L',1);
				$x=$pdf->getX();
			}
		}
		$pdf->setY($init_y+25);
		$pdf->Ln();
		// stampa legende
		if (count($leg_absence)>=1){
			// creo il testo della legenda delle assenze
			$txt='';
			foreach($leg_absence as $k=>$v){
				$txt .= $k.' ) '.$v."\n";
			}
			$pdf->setX(5);
			$pdf->Cell(45,3,'Legenda tipi assenze:',0,0,'R');
			$pdf->setX(10);
			$pdf->setCellPaddings(40);
			$pdf->MultiCell(277,7,$txt, 1, 'L', 0, 1, '', '', true);
			$pdf->setCellPaddings(1);
		}
		if (count($leg_other)>=1){
			// creo il testo della legenda delle ore diverse
			$txt='';
			foreach($leg_other as $k=>$v){
				$txt .= $k.' ) '.$v."\n";
			}
			$pdf->setX(5);
			$pdf->Cell(45,3,'Legenda altri tipi:',0,0,'R');
			$pdf->setX(10);
			$pdf->setCellPaddings(40);
			$pdf->MultiCell(277,7,$txt, 1, 'L', 0, 1, '', '', true);
			$pdf->setCellPaddings(1);
		}
		if (count($leg_note)>=1){
			// creo il testo della legenda delle note sui giorni
			$txt='';
			foreach($leg_note as $k=>$v){
				$txt .= 'in data '.$k.': '.$v."\n";
			}
			$pdf->setX(5);
			$pdf->Cell(45,3,'Note:',0,0,'R');
			$pdf->setX(10);
			$pdf->setCellPaddings(40);
			$pdf->MultiCell(277,7,$txt, 1, 'L', 0, 1, '', '', true);
			$pdf->setCellPaddings(1);
		}
    }
	$pdf->Ln(2);
    $ctrlWorker = $mv['id_staff'];
}
$pdf->SetFont('helvetica','',7);
$pdf->setRiporti('');
$pdf->Output();
?>
