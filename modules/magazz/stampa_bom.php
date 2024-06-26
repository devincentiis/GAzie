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
$admin_aziend=checkAdmin();
$gForm = new magazzForm();
if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
    gaz_set_time_limit (0);
}

if (!isset($_GET['ri'])) {
    header("Location: report_artico.php");
    exit;
}else{
	$codart=filter_input(INPUT_GET,'ri');
	$artdata= gaz_dbi_get_row($gTables['artico'], 'codice',$codart);
	$result=$gForm->getBOM($codart);
}
$gazTimeFormatter->setPattern('MMMM');
$luogo_data=$admin_aziend['citspe'].", lì ".ucwords($gazTimeFormatter->format(new DateTime()));
require("../../config/templates/report_template.php");
$pdf = new Report_template('L','mm','A4',true,'UTF-8',false,true);
$title = array('luogo_data'=>$luogo_data,
               'title'=>"BOM - Distinta base di: ".$codart.' - '.$artdata['descri'],
               'hile'=>array(array('lun' => 60,'nam'=>'Codici ad albero'),
                             array('lun' => 106,'nam'=>'Descrizione materiale'),
                             array('lun' => 26,'nam'=>'Unità  misura'),
							 array('lun' => 37,'nam'=>'Quantità articolo'),
                             array('lun' => 37,'nam'=>'Quantità totale')
                            )
            );
$pdf->SetLeftMargin(16);


$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(39);
$pdf->SetFooterMargin(15);

$config = new Config;
$pdf->AddPage();
$pdf->SetFont('helvetica','',9);
if (sizeof($result) > 0) {
	$acc=[];
  $i=0;
	foreach ($result as $k0=>$v0){
    $i++;
    $bg=($i%2)?true:false;
		if (!isset($acc[$k0])){
			$acc[$k0]['t']=$v0['totq'];
			$acc[$k0]['d']=$v0['descri'];
			$acc[$k0]['u']=$v0['uniacq'];
		}else{$acc[$k0]['t']+=$v0['totq'];}
		$pdf->SetFillColor(255,225,255);
		$pdf->Cell(27,5,$k0,1,0,'C',1, '', 1);
		$pdf->Cell(33);
		$pdf->SetFillColor(240,240,240);
		$pdf->Cell(106,5,$v0['descri'],1, 0, 'L', $bg, '', 1);
		$pdf->Cell(26,5,$v0['uniacq'],1, 0, 'C', $bg, '', 1);
		$pdf->Cell(37,5,floatval($v0['quantita_artico_base']),1, 0, 'C', $bg, '', 1);
		$pdf->Cell(37,5,$v0['totq'],1,1,'R',$bg);
		if (is_array($v0['codice_artico_base'])){
			foreach ($v0['codice_artico_base'] as $k1=>$v1){
        $i++;
        $bg=($i%2)?true:false;
				if (!isset($acc[$k1])){
					$acc[$k1]['t']=$v1['totq'];
					$acc[$k1]['d']=$v1['descri'];
					$acc[$k1]['u']=$v1['uniacq'];
				}else{$acc[$k1]['t']+=$v1['totq'];}
				$pdf->SetFillColor(225,255,255);
				$pdf->Cell(7);
				$pdf->Cell(27,5,$k1,1,0,'C',1);
				$pdf->Cell(26);
				$pdf->SetFillColor(240,240,240);
				$pdf->Cell(106,5,$v1['descri'],1, 0, 'L', $bg, '', 1);
				$pdf->Cell(26,5,$v1['uniacq'],1, 0, 'C', $bg, '', 1);
				$pdf->Cell(37,5,floatval($v1['quantita_artico_base']),1, 0, 'C', $bg, '', 1);
				$pdf->Cell(37,5,$v1['totq'],1,1,'R',$bg);
				if (is_array($v1['codice_artico_base'])){
					foreach ($v1['codice_artico_base'] as $k2=>$v2){
            $i++;
            $bg=($i%2)?true:false;
            if (!isset($acc[$k2])){
							$acc[$k2]['t']=$v2['totq'];
							$acc[$k2]['d']=$v2['descri'];
							$acc[$k2]['u']=$v2['uniacq'];
						}else{$acc[$k2]['t']+=$v2['totq'];}
            $pdf->SetFillColor(189, 252, 201);
						$pdf->Cell(14);
						$pdf->Cell(27,5,$k2,1, 0, 'C', 1, '', 1);
						$pdf->Cell(19);
            $pdf->SetFillColor(240,240,240);
						$pdf->Cell(106,5,$v2['descri'],1, 0, 'L', $bg, '', 1);
						$pdf->Cell(26,5,$v2['uniacq'],1, 0, 'C', $bg, '', 1);
						$pdf->Cell(37,5,floatval($v2['quantita_artico_base']),1, 0, 'C', $bg, '', 1);
						$pdf->Cell(37,5,$v2['totq'],1,1,'R',$bg);
						if (is_array($v2['codice_artico_base'])){
							foreach ($v2['codice_artico_base'] as $k3=>$v3){
                $i++;
                $bg=($i%2)?true:false;
								if (!isset($acc[$k3])){
									$acc[$k3]['t']=$v3['totq'];
									$acc[$k3]['d']=$v3['descri'];
									$acc[$k3]['u']=$v3['uniacq'];
								}else{$acc[$k3]['t']+=$v3['totq'];}
								$pdf->SetFillColor(255,255,225);
								$pdf->Cell(21);
								$pdf->Cell(27,5,$k3,1, 0, 'C', 1, '', 1);
								$pdf->Cell(12);
                $pdf->SetFillColor(240,240,240);
								$pdf->Cell(106,5,$v3['descri'],1, 0, 'L', $bg, '', 1);
								$pdf->Cell(26,5,$v3['uniacq'],1, 0, 'C', $bg, '', 1);
								$pdf->Cell(37,5,floatval($v3['quantita_artico_base']),1, 0, 'C', $bg, '', 1);
								$pdf->Cell(37,5,$v3['totq'],1,1,'R',$bg);
								if (is_array($v3['codice_artico_base'])){
									foreach ($v3['codice_artico_base'] as $k4=>$v4){
                    $i++;
                    $bg=($i%2)?true:false;
										if (!isset($acc[$k4])){
                      $acc[$k4]['t']=$v4['totq'];
											$acc[$k4]['d']=$v4['descri'];
											$acc[$k4]['u']=$v4['uniacq'];
										}else{$acc[$k4]['t']+=$v4['totq'];}
                    $pdf->SetFillColor(245, 230, 233);
										$pdf->Cell(28);
										$pdf->Cell(25,5,$k4,1, 0, 'C', 1, '', 1);
										$pdf->Cell(7);
                    $pdf->SetFillColor(240,240,240);
										$pdf->Cell(106,5,$v4['descri'],1, 0, 'L', $bg, '', 1);
										$pdf->Cell(26,5,$v4['uniacq'],1, 0, 'C', $bg, '', 1);
										$pdf->Cell(37,5,floatval($v4['quantita_artico_base']),1, 0, 'C', $bg, '', 1);
										$pdf->Cell(37,5,$v4['totq'],1,1,'R',$bg);
									}
								}
							}
						}
					}
				}
			}
		}
	}
	ksort($acc);
$title = array('luogo_data'=>$luogo_data,
               'title'=>"BOM - DISTINTA BASE dell'articolo: ".$codart.' - '.$artdata['descri'],
               'hile'=>array(array('lun' => 30,'nam'=>false),
							 array('lun' => 30,'nam'=>'Codice'),
                             array('lun' => 106,'nam'=>'Descrizione materiale'),
                             array('lun' => 26,'nam'=>'Unità  misura'),
							 array('lun' => 37,'nam'=>'Quantità articolo')
                            )
            );

$pdf->setVars($admin_aziend,$title);
$pdf->SetFooterMargin(15);
$pdf->AddPage();
$pdf->SetFont('helvetica','B',12);
$pdf->SetFillColor(255, 152, 102);
$pdf->Cell(30);
$pdf->Cell(199,8,' D I S T I N T A     C O M P O N E N T I ',1,1,'C',1);
$pdf->SetFillColor(hexdec(substr($pdf->colore, 0, 2)), hexdec(substr($pdf->colore, 2, 2)), hexdec(substr($pdf->colore, 4, 2)));
$pdf->Cell(30);
$pdf->Cell(30,8,'Codice',1,0,'C', 0, '', 1);
$pdf->Cell(106,8,'Descrizione',1, 0, 'L', 0, '', 1);
$pdf->Cell(26,8,'Unità misura',1,0,'C',1);
$pdf->Cell(37,8,'Quantità',1,1,'C',1);

$pdf->SetFont('helvetica','',9);
$pdf->SetFillColor(240, 240, 240);
$i=0;
foreach ($acc as $ka=>$va){
  $bg=($i%2)?true:false;
	$pdf->Cell(30);
	$pdf->Cell(30,5,$ka,1,0,'L', $bg, '', 1);
	$pdf->Cell(106,5,$va['d'],1, 0, 'L', $bg, '', 1);
	$pdf->Cell(26,5,$va['u'],1,0,'C',$bg);
	$pdf->Cell(37,5,$va['t'],1,1,'C',$bg);
  $i++;
}

}


$pdf->Output();
?>
