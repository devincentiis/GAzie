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
require("../../config/templates/report_template.php");
$lines=file('../finann/IVdirCEE.bil');
foreach($lines as $line) {
        $nuova = explode(';',$line,2);
        $data[trim($nuova[0])] = trim($nuova[1]);
}
$nromani = array(0=>"",1=>"I",2=>"II",3=>"III",4=>"IV",5=>"V",6=>"VI",7=>"VII",8=>"VIII",9=>"IX",10=>"X",11=>"XI",12=>"XII",13=>"XIII",14=>"XIV",15=>"XV",16=>"XVI",17=>"XVII",18=>"XVIII",19=>"XIX");
$d_b=array('A'=>'att.','P'=>'pass.','E'=>'c.e.');
function convNum($n=0){
  $r=intval(substr($n,4,3));
  if ($r<1) {
    $r='';
  }
  return $r;
}
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data = $admin_aziend['citspe'] . ", lì " . ucwords($gazTimeFormatter->format(new DateTime()));
$where = " 1 ";
$what = $gTables['clfoco'].".*, CONCAT(".$gTables['anagra'].".ragso1, ' ',".$gTables['anagra'].".ragso2) AS ragsoc ";
$table = $gTables['clfoco']." LEFT JOIN ".$gTables['anagra']." ON (".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id)";
$result = gaz_dbi_dyn_query ($what, $table,$where,"codice ASC");
$item_head = array('top'=>array(array('lun' => 80,'nam'=>'Descrizione'),
                                array('lun' => 25,'nam'=>'Numero Conto')
                               )
                   );
$title = array('luogo_data'=>$luogo_data,
               'title'=>"PIANO DEI CONTI",
               'hile'=>array(   array('lun' => 15,'nam'=>'Codice'),
                                array('lun' => 55,'nam'=>'Denominazione'),
                                array('lun' => 65,'nam'=>'Voci del bilancio d\'esercizio'),
                                array('lun' => 51,'nam'=>'Note')
                            )
              );
$pdf = new Report_template();
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(39);
$pdf->SetFooterMargin(22);
$config = new Config;
$pdf->SetFont('helvetica','',7);
$pdf->AddPage('P',$config->getValue('page_format'));
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
$ctrl_mas = 0;
$max_y = 0;
while ($row = gaz_dbi_fetch_array($result)) {
    $mas=intval(substr($row['codice'],0,3));
    $y=$pdf->GetY();
    if ($y<250){
        $max_str=intval(13*(254-$y));
        if ($ctrl_mas<$mas ){  // mastri
            if (intval(substr($row['codice'],3,6))==0) {
               $cm_d=trim($row['ceedar']);
               $cm_a=trim($row['ceeave']);
            }
            if (isset($note[1])){ // ma se ho una nota del mastro precedente la devo stampare
                $pdf->AddPage('P',$config->getValue('page_format'));
                $pdf->Cell(135);
                $pdf->SetFont('helvetica','',7);
                $pdf->MultiCell(51,4,$note[1],1,'L',true);
                $y=$pdf->GetY();
                $max_str=intval(13*(254-$y));
            }
            $start_y=$y;
            if ($max_y>$y) { //se con i conti son andato oltre...
                $pdf->Cell(135,1,'','T');
                $pdf->SetY($max_y); // inizio dalla loro fine
                $start_y=$max_y;
            }
            $note = str_split($row['annota'],$max_str);
            $note = count($note)>0?$note:[0=>''];
            $pdf->SetFont('helvetica','B',7);
            $pdf->Cell(15,4,$mas,1,0,'C',1);
            $pdf->Cell(120,4,$row['descri'],'LTB',0,'L',1);
            $pdf->SetFont('helvetica','',7);
            $pdf->MultiCell(51,4,$note[0],1,'L',true);
            $pdf->SetY($start_y+4); // mi riposiziono all'inizio
        } else {   //conti
            $pdf->Cell(15,4,$row['codice'],'L',0,'C');
            $pdf->Cell(55,4,$row['descri'],'L',0,'L',false,'',1);
            $pdf->SetFont('helvetica','',6);
            $cee='';
            $ce_d=trim($row['ceedar']);
            if (isset($data[$ce_d]) && !empty($data[$ce_d])) {
                $des_cee=substr($ce_d,1,1);
                $des_cee.=$nromani[intval(substr($ce_d,2,2))];
                $des_cee.= convNum($ce_d) . substr($ce_d,7,1);
                $cee='DARE: '.$d_b[substr($ce_d,0,1)].' '.$des_cee;
                if (substr($row['codice'],0,1)=='2'){
                  $cee .= ' (in diminuzione)';
                }
                $cee .=' '.$data[$ce_d];
            }
            $ce_a=trim($row['ceeave']);
            if (isset($data[$ce_a]) && !empty($data[$ce_a])) {
                $des_cee=substr($ce_a,1,1);
                $des_cee.=$nromani[intval(substr($ce_a,2,2))];
                $des_cee.= convNum($ce_a) . substr($ce_a,7,1);
                if (empty($cee)){
                    $cee='AVERE: ';
                } else {
                    $cee.="\nAVERE: ";
                }
                $cee.=$d_b[substr($ce_a,0,1)].' '.$des_cee;
                if (substr($row['codice'],0,1)=='1'){
                  $cee .= ' (in diminuzione)';
                }
                $cee .=' '.$data[$ce_a];
            }
            if (empty($cee)) { // se il conto non � riclassificato controllo che lo sia il mastro
                if (isset($data[$cm_d]) && !empty($data[$cm_d])) {
                    $des_cee=substr($cm_d,1,1);
                    $des_cee.=$nromani[intval(substr($cm_d,2,2))];
                    $des_cee.= convNum($cm_d) . substr($cm_d,7,1);
                    $cee='DARE: '.$d_b[substr($cm_d,0,1)].' '.$des_cee;
                    if (substr($row['codice'],0,1)=='2'){
                      $cee .= ' (in diminuzione)';
                    }
                    $cee .=' '.$data[$cm_d];
                }
                if (isset($data[$cm_a]) && !empty($data[$cm_a])) {
                    $des_cee=substr($cm_a,1,1);
                    $des_cee.=$nromani[intval(substr($cm_a,2,2))];
                    $des_cee.= convNum($cm_a) . substr($cm_a,7,1);
                       if (empty($cee)){
                          $cee='AVERE: ';
                       } else {
                          $cee.="\nAVERE: ";
                       }
                    $cee.=$d_b[substr($cm_a,0,1)].' '.$des_cee;
                    if (substr($row['codice'],0,1)=='1'){
                        $cee .= ' (in diminuzione)';
                    }
                    $cee .=' '.$data[$cm_a];
                }
            }
            if (empty($cee)) {
                $cee=$row['annota'];
            } else {
                $cee .="\n".$row['annota'];
            }
            $pdf->MultiCell(65,4,$cee,'LR','L',false);
            $ly=$pdf->GetY();
            $pdf->Line(10,$ly,10,$y);
            $pdf->Line(25,$ly,25,$y);
            $pdf->SetFont('helvetica','',7);

        }
    } else {
           $pdf->Cell(135,1,'','T');
           $pdf->AddPage('P',$config->getValue('page_format'));
           if (isset($note[1])){
               $y=$pdf->GetY();
               $pdf->Cell(135);
               $pdf->SetFont('helvetica','',7);
               $pdf->MultiCell(51,4,$note[1],1,'L',true);
               $max_y=$pdf->GetY();
               unset($note);
               $pdf->SetY($y);
           } else {
               $max_y=$pdf->GetY();
           }
            $pdf->Cell(15,4,$row['codice'],'L',0,'C');
            $pdf->Cell(55,4,$row['descri'],'L');
            $pdf->SetFont('helvetica','',6);
            $cee='';
            $ce_d=trim($row['ceedar']);
            if (isset($data[$ce_d]) && !empty($data[$ce_d])) {
                $des_cee=substr($ce_d,1,1);
                $des_cee.=$nromani[intval(substr($ce_d,2,2))];
                $des_cee.= convNum($ce_d) . substr($ce_d,7,1);
                $cee='DARE: '.$d_b[substr($ce_d,0,1)].' '.$des_cee;
                if (substr($row['codice'],0,1)=='2'){
                  $cee .= ' (in diminuzione)';
                }
                $cee .=' '.$data[$ce_d];
            }
            $ce_a=trim($row['ceeave']);
            if (isset($data[$ce_a]) && !empty($data[$ce_a])) {
                $des_cee=substr($ce_a,1,1);
                $des_cee.=$nromani[intval(substr($ce_a,2,2))];
                $des_cee.= convNum($ce_a) . substr($ce_a,7,1);
                if (empty($cee)){
                    $cee='AVERE: ';
                } else {
                    $cee.="\nAVERE: ";
                }
                $cee.=$d_b[substr($ce_a,0,1)].' '.$des_cee;
                if (substr($row['codice'],0,1)=='1'){
                  $cee .= ' (in diminuzione)';
                }
                $cee .=' '.$data[$ce_a];
            }
            if (empty($cee)) { // se il conto non � riclassificato controllo che lo sia il mastro
                if (isset($data[$cm_d]) && !empty($data[$cm_d])) {
                    $des_cee=substr($cm_d,1,1);
                    $des_cee.=$nromani[intval(substr($cm_d,2,2))];
                    $des_cee.= convNum($cm_d) . substr($cm_d,7,1);
                    $cee='DARE: '.$d_b[substr($cm_d,0,1)].' '.$des_cee;
                    if (substr($row['codice'],0,1)=='2'){
                      $cee .= ' (in diminuzione)';
                    }
                    $cee .=' '.$data[$cm_d];
                }
                if (isset($data[$cm_a]) && !empty($data[$cm_a])) {
                    $des_cee=substr($cm_a,1,1);
                    $des_cee.=$nromani[intval(substr($cm_a,2,2))];
                    $des_cee.= convNum($cm_a) . substr($cm_a,7,1);
                       if (empty($cee)){
                          $cee='AVERE: ';
                       } else {
                          $cee.="\nAVERE: ";
                       }
                    $cee.=$d_b[substr($cm_a,0,1)].' '.$des_cee;
                    if (substr($row['codice'],0,1)=='1'){
                        $cee .= ' (in diminuzione)';
                    }
                    $cee .=' '.$data[$cm_a];
                }
            }
            if (empty($cee)) {
                $cee=$row['annota'];
            } else {
                $cee .="\n".$row['annota'];
            }
            $pdf->MultiCell(65,4,$cee,'LR','L',false);
            $ly=$pdf->GetY();
            $pdf->Line(10,$ly,10,$y);
            $pdf->Line(25,$ly,25,$y);
            $pdf->SetFont('helvetica','',7);


    }
    $ctrl_mas = $mas;
}
$pdf->Cell(135,1,'','T');
$pdf->Output($title['title'].'_'.$admin_aziend['ragso1'].'.pdf');
?>
