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

require("./lang.".$admin_aziend['lang'].".php");
$script_transl = $strScript["select_elencf.php"];
if (!isset($_GET['partner']) or
    !isset($_GET['anno'])) {
    header("Location: select_elencf.php");
    exit;
}
require("../../library/include/check.inc.php");

function createRowsAndErrors($partner){
    global $gTables,$admin_aziend,$script_transl;
    $nuw = new check_VATno_TAXcode();
    if ($partner == 2) {
       $search_partner = " clfoco LIKE '".$admin_aziend['masfor']."%'";
    } else {
       $search_partner = " clfoco LIKE '".$admin_aziend['mascli']."%'";
    }
    $sqlquery= "SELECT COUNT(".$gTables['rigmoi'].".id_tes) AS numdoc,
           codiva,".$gTables['aliiva'].".tipiva,SUM(impost - impost*2*((caucon LIKE '_NC') or (caucon LIKE '_FC' ))) AS imposta,
           SUM(imponi - imponi*2*((caucon LIKE '_NC') or (caucon LIKE '_FC' ))) AS imponibile,clfoco,CONCAT(ragso1,' ',ragso2) AS ragsoc, sedleg,sexper,indspe,
           citspe,prospe,codfis,pariva,allegato,operat
           FROM ".$gTables['rigmoi']."
           LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoi'].".id_tes = ".$gTables['tesmov'].".id_tes
           LEFT JOIN ".$gTables['aliiva']." ON ".$gTables['rigmoi'].".codiva = ".$gTables['aliiva'].".codice
           LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['tesmov'].".clfoco = ".$gTables['clfoco'].".codice
           LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra
           WHERE allegato = 1 AND YEAR(datdoc) = ".intval($_GET['anno'])." AND $search_partner
           GROUP BY clfoco, codiva
           ORDER BY ragso1";
    $result = gaz_dbi_query($sqlquery);
    $castel_partners= array();
    $error_partners= array();
    $total_column= array();
    if (gaz_dbi_num_rows($result) > 0 ) {
       // inizio creazione array righi ed errori
       $total_column['operazioni_imponibili'] = 0.00;
       $total_column['imposte_addebitate'] = 0.00;
       $total_column['operazioni_esente'] = 0.00;
       $total_column['operazioni_nonimp'] = 0.00;
       $progressivo = 0;
       $ctrl_partner = 0;
       $value_imponi = 0.00;
       $value_impost = 0.00;
       while ($row = gaz_dbi_fetch_array($result)) {
              if ($row['operat'] == 1) {
                $value_imponi = $row['imponibile'];
                $value_impost = $row['imposta'];
              } elseif ($row['operat'] == 2) {
                $value_imponi = $row['imponibile'];
                $value_impost = $row['imposta'];
              } else {
                $value_imponi = 0;
                $value_impost = 0;
              }
              if ($ctrl_partner != $row['clfoco']) {
                 // inizio controlli su CF e PI
                 $resultpi = $nuw->check_VAT_reg_no($row['pariva']);
                 if( strlen(trim($row['codfis'])) == 11) {
                     $resultcf = $nuw->check_VAT_reg_no($row['codfis']);
                     if (intval($row['codfis']) == 0) {
                        $error_partners[$row['clfoco']][] = $script_transl[7];
                     } elseif ($row['sexper'] != 'G') {
                        $error_partners[$row['clfoco']][] = $script_transl[8];
                     }
                 } else {
                     $resultcf = $nuw->check_TAXcode($row['codfis']);
                     if (empty($row['codfis'])) {
                         $error_partners[$row['clfoco']][] = $script_transl[9];
                     } elseif ($row['sexper'] == 'G' and
                         empty($resultcf)) {
                        $error_partners[$row['clfoco']][] = $script_transl[10];
                     } elseif ($row['sexper'] == 'M' and
                         empty($resultcf) and
                         (intval(substr($row['codfis'],9,2)) > 31 or
                         intval(substr($row['codfis'],9,2)) < 1) ) {
                         $error_partners[$row['clfoco']][] = $script_transl[11];
                     } elseif ($row['sexper'] == 'F' and
                         empty($resultcf) and
                         (intval(substr($row['codfis'],9,2)) > 71 or
                         intval(substr($row['codfis'],9,2)) < 41) ) {
                         $error_partners[$row['clfoco']][] = $script_transl[12];
                     } elseif (! empty ($resultcf)) {
                         $error_partners[$row['clfoco']][] = $script_transl[13];
                     }
                 }
                 if (! empty ($resultpi)) {
                    $error_partners[$row['clfoco']][] = $script_transl[14];
                    $error_partners['fatal_error'] = '';
                 } elseif (empty($row['pariva'])) {
                    $error_partners[$row['clfoco']][] = $script_transl[15];
                    $error_partners['fatal_error'] = '';
                 }
                 // fine controlli su CF e PI
                 $progressivo ++;
                 $castel_partners[$row['clfoco']] = array(
                      'Progressivo'=> $progressivo,
                      'Num_Documenti'=> $row['numdoc'],
                      'Rag_Sociale'=> $row['ragsoc'],
                      'Indirizzo'=> $row['indspe'],
                      'Comune'=> $row['citspe'],
                      'Provincia'=> $row['prospe'] ,
                      'Partita_IVA'=> $row['pariva'],
                      'Codice_Fiscale'=> $row['codfis']
                 );
                 if ($row['sexper'] == 'G'){
                        $castel_partners[$row['clfoco']]['persona_fisica'] = '';
                 } else {
                        $castel_partners[$row['clfoco']]['persona_fisica'] = 'X';
                 }
                 if (!empty($row['sedleg'])){
                     if ( preg_match("/([\w\,\.\s]+)([0-9]{5})[\s]+([\w\s\']+)\(([\w]{2})\)/",$row['sedleg'],$regs)) {
                        $castel_partners[$row['clfoco']]['Indirizzo'] = $regs[1];
                        $castel_partners[$row['clfoco']]['Comune'] = $regs[3];
                        $castel_partners[$row['clfoco']]['Provincia'] = $regs[4];
                     } else {
                       $error_partners[$row['clfoco']][] = $script_transl[16];
                     }
                 }
                 // inizio valorizzazione imponibile,imposta,senza_iva,art8
                 $castel_partners[$row['clfoco']]['operazioni_imponibili'] = 0;
                 $castel_partners[$row['clfoco']]['imposte_addebitate'] = 0;
                 $castel_partners[$row['clfoco']]['operazioni_esente'] = 0;
                 $castel_partners[$row['clfoco']]['operazioni_nonimp'] = 0;
                 switch ($row['tipiva']) {
                        case 'I':
                        case 'D':
                        case 'T':
                             $castel_partners[$row['clfoco']]['operazioni_imponibili'] = $value_imponi;
                             $total_column['operazioni_imponibili'] += $value_imponi;
                             $castel_partners[$row['clfoco']]['imposte_addebitate'] = $value_impost;
                             $total_column['imposte_addebitate'] += $value_impost;
                             if ($value_impost == 0){  //se non c'� imposta il movimento � sbagliato
                                $error_partners[$row['clfoco']][] = $script_transl[17];
                             }
                        break;
                        case 'E':
                             $castel_partners[$row['clfoco']]['operazioni_esente'] = $value_imponi;
                             $total_column['operazioni_esente'] += $value_imponi;
                             if ($value_impost != 0){  //se c'� imposta il movimento � sbagliato
                                $error_partners[$row['clfoco']][] = $script_transl[18];
                             }
                        break;
                        case 'N':
                        // case 'C':
                             $castel_partners[$row['clfoco']]['operazioni_nonimp'] = $value_imponi;
                             $total_column['operazioni_nonimp'] += $value_imponi;
                             if ($value_impost != 0){  //se c'� imposta il movimento � sbagliato
                                $error_partners[$row['clfoco']][] = $script_transl[18];
                             }
                        break;
                 }
                 // fine valorizzazione imponibile,imposta,esente,non imponibile

              } else { //movimenti successivi al primo ma dello stesso cliente/fornitore
                 // inizio addiziona valori imponibile,imposta,esente,non imponibile
                 switch ($row['tipiva']) {
                        case 'I':
                        case 'D':
                        case 'T':
                             $castel_partners[$row['clfoco']]['operazioni_imponibili'] += $value_imponi;
                             $total_column['operazioni_imponibili'] += $value_imponi;
                             $castel_partners[$row['clfoco']]['imposte_addebitate'] += $value_impost;
                             $total_column['imposte_addebitate'] += $value_impost;
                             if ($value_impost == 0){  //se non c'� imposta il movimento � sbagliato
                                $error_partners[$row['clfoco']][] = $script_transl[17];
                             }
                        break;
                        case 'E':
                             $castel_partners[$row['clfoco']]['operazioni_esente'] = $value_imponi;
                             $total_column['operazioni_esente'] += $value_imponi;
                             if ($value_impost != 0){  //se c'� imposta il movimento � sbagliato
                                $error_partners[$row['clfoco']][] = $script_transl[18];
                             }
                        break;
                        case 'N':
                        // case 'C':
                             $castel_partners[$row['clfoco']]['operazioni_nonimp'] = $value_imponi;
                             $total_column['operazioni_nonimp'] += $value_imponi;
                             if ($value_impost != 0){  //se c'� imposta il movimento � sbagliato
                                $error_partners[$row['clfoco']][] = $script_transl[18];
                             }
                        break;
                 }
                 // fine addiziona valori imponibile,imposta,esente,non imponibile
              }
              $ctrl_partner = $row['clfoco'];
       }
    } else {
              $error_partners[0] = $script_transl[21];
    }
    // fine creazione array righi ed errori
    return array($castel_partners,$error_partners,$total_column);
}

require("../../config/templates/report_template.php");

$pdf = new Report_template();
$item_head = array('top'=>array(array('lun' => 21,'nam'=>'Codice'),
                                array('lun' => 18,'nam'=>'Cat.Merc'),
                                array('lun' => 60,'nam'=>'Descrizione'),
                                array('lun' => 10,'nam'=>'U.M.'),
                                array('lun' => 18,'nam'=>'Scorta')
                               )
                   );

$title = array('title'=>$script_transl[4].' '.$script_transl[2].' - '.$script_transl[5].' '.$_GET['anno'],
               'hile'=>array(array('lun' => 8,'nam'=>'N.'),
                             array('lun' => 80,'nam'=>'Rag.Soc,Cognome,Nome/Pers,CF,Partita IVA,N.Fat'),
                             array('lun' => 54,'nam'=>'Indirizzo/Imponib.,Imposta,Esente'),
                             array('lun' => 39,'nam'=>'Comune/Non Impon,Totale'),
                             array('lun' => 6,'nam'=>'Pr.')
                            )
              );
$aRiportare = array('top'=>array(array('lun' => 88,'nam'=>'da riporto : '),
                           array('lun' => 18,'nam'=>''),
                           array('lun' => 18,'nam'=>''),
                           array('lun' => 18,'nam'=>''),
                           array('lun' => 19,'nam'=>''),
                           array('lun' => 20,'nam'=>''),
                           array('lun' =>  6,'nam'=>'')
                           ),
                    'bot'=>array(array('lun' => 88,'nam'=>'a riportare : '),
                           array('lun' => 18,'nam'=>''),
                           array('lun' => 18,'nam'=>''),
                           array('lun' => 18,'nam'=>''),
                           array('lun' => 19,'nam'=>''),
                           array('lun' => 20,'nam'=>''),
                           array('lun' =>  6,'nam'=>'')
                           )
                    );
$pdf->setVars($admin_aziend,$title);
$pdf->setAuthor($admin_aziend['ragso1'].' '.$_SESSION["user_name"]);
$pdf->setTitle($title['title']);
$pdf->SetTopMargin(43);
$pdf->SetFooterMargin(20);
$pdf->AddPage();
if ($_GET['partner'] == 1 or $_GET['partner'] == 3){
    $queryData = createRowsAndErrors(1);
    $castel_partners = $queryData[0];
    foreach ($castel_partners as $key=>$value ) {
      $totale = $value['operazioni_imponibili']+
                $value['operazioni_nonimp']+
                $value['operazioni_esente']+
                $value['imposte_addebitate'];
      $pdf->SetFont('helvetica','',7);
      $pdf->Cell(8,3,$value['Progressivo'],'LTR',0,'R');
      $pdf->Cell(80,3,$value['Rag_Sociale'],'T');
      $pdf->Cell(54,3,$value['Indirizzo'],'T');
      $pdf->Cell(39,3,$value['Comune'],'T');
      $pdf->Cell(6,3,$value['Provincia'],'TR',1,'C');
      $aRiportare['top'][1]['nam'] += $value['operazioni_imponibili'];
      $aRiportare['bot'][1]['nam'] += $value['operazioni_imponibili'];
      $aRiportare['top'][2]['nam'] += $value['imposte_addebitate'];
      $aRiportare['bot'][2]['nam'] += $value['imposte_addebitate'];
      $aRiportare['top'][3]['nam'] += $value['operazioni_esente'];
      $aRiportare['bot'][3]['nam'] += $value['operazioni_esente'];
      $aRiportare['top'][4]['nam'] += $value['operazioni_nonimp'];
      $aRiportare['bot'][4]['nam'] += $value['operazioni_nonimp'];
      $aRiportare['top'][5]['nam'] += $totale;
      $aRiportare['bot'][5]['nam'] += $totale;
      $pdf->setRiporti($aRiportare);
      $pdf->Cell(8,3,'','LB');
      $pdf->Cell(20,3,$value['persona_fisica'],'B');
      $pdf->Cell(30,3,$value['Codice_Fiscale'],'B');
      $pdf->Cell(20,3,$value['Partita_IVA'],'B',0,'C');
      $pdf->Cell(10,3,$value['Num_Documenti'],'B',0,'R');
      $pdf->Cell(18,3,gaz_format_number($value['operazioni_imponibili']),'B',0,'R');
      $pdf->Cell(18,3,gaz_format_number($value['imposte_addebitate']),'B',0,'R');
      $pdf->Cell(18,3,gaz_format_number($value['operazioni_esente']),'B',0,'R');
      $pdf->Cell(19,3,gaz_format_number($value['operazioni_nonimp']),'B',0,'R');
      $pdf->Cell(20,3,gaz_format_number($totale),'B',0,'R');
      $pdf->Cell(6,3,'','B',1);
    }
}
$pdf->Ln(1);
$pdf->SetFont('helvetica','B',8);
$aRiportare['bot'][0]['nam'] = ' TOTALI : ';
foreach ($aRiportare['bot'] as $key=>$value){
           if ($key > 0 and $key < 6) {
              $value['nam']= gaz_format_number($value['nam']);
           }
           $pdf->Cell($value['lun'],4,$value['nam'],1,0,'R');
}
$pdf->setRiporti('');
$pdf->setPageTitle($script_transl[4].' '.$script_transl[3].' - '.$script_transl[5].' '.$_GET['anno']);
$pdf->AddPage();
if ($_GET['partner'] == 2 or $_GET['partner'] == 3){
    $aRiportare['top'][1]['nam'] = 0;
    $aRiportare['bot'][1]['nam'] = 0;
    $aRiportare['top'][2]['nam'] = 0;
    $aRiportare['bot'][2]['nam'] = 0;
    $aRiportare['top'][3]['nam'] = 0;
    $aRiportare['bot'][3]['nam'] = 0;
    $aRiportare['top'][4]['nam'] = 0;
    $aRiportare['bot'][4]['nam'] = 0;
    $aRiportare['top'][5]['nam'] = 0;
    $aRiportare['bot'][5]['nam'] = 0;
    $queryData = createRowsAndErrors(2);
    $castel_partners = $queryData[0];
    //print_r($castel_partners);
    foreach ($castel_partners as $key=>$value ) {
      $totale = $value['operazioni_imponibili']+
                $value['operazioni_nonimp']+
                $value['operazioni_esente']+
                $value['imposte_addebitate'];
      $pdf->SetFont('helvetica','',7);
      $pdf->Cell(8,3,$value['Progressivo'],'LTR',0,'R');
      $pdf->Cell(80,3,$value['Rag_Sociale'],'T');
      $pdf->Cell(54,3,$value['Indirizzo'],'T');
      $pdf->Cell(39,3,$value['Comune'],'T');
      $pdf->Cell(6,3,$value['Provincia'],'TR',1,'C');
      $aRiportare['top'][1]['nam'] += $value['operazioni_imponibili'];
      $aRiportare['bot'][1]['nam'] += $value['operazioni_imponibili'];
      $aRiportare['top'][2]['nam'] += $value['imposte_addebitate'];
      $aRiportare['bot'][2]['nam'] += $value['imposte_addebitate'];
      $aRiportare['top'][3]['nam'] += $value['operazioni_esente'];
      $aRiportare['bot'][3]['nam'] += $value['operazioni_esente'];
      $aRiportare['top'][4]['nam'] += $value['operazioni_nonimp'];
      $aRiportare['bot'][4]['nam'] += $value['operazioni_nonimp'];
      $aRiportare['top'][5]['nam'] += $totale;
      $aRiportare['bot'][5]['nam'] += $totale;
      $pdf->setRiporti($aRiportare);
      $pdf->Cell(8,3,'','LB');
      $pdf->Cell(20,3,$value['persona_fisica'],'B');
      $pdf->Cell(30,3,$value['Codice_Fiscale'],'B');
      $pdf->Cell(20,3,$value['Partita_IVA'],'B',0,'C');
      $pdf->Cell(10,3,$value['Num_Documenti'],'B',0,'R');
      $pdf->Cell(18,3,gaz_format_number($value['operazioni_imponibili']),'B',0,'R');
      $pdf->Cell(18,3,gaz_format_number($value['imposte_addebitate']),'B',0,'R');
      $pdf->Cell(18,3,gaz_format_number($value['operazioni_esente']),'B',0,'R');
      $pdf->Cell(19,3,gaz_format_number($value['operazioni_nonimp']),'B',0,'R');
      $pdf->Cell(20,3,gaz_format_number($totale),'B',0,'R');
      $pdf->Cell(6,3,'','B',1);
    }
}
$pdf->Ln(1);
$pdf->SetFont('helvetica','B',8);
$aRiportare['bot'][0]['nam'] = ' TOTALI : ';
foreach ($aRiportare['bot'] as $key=>$value){
           if ($key > 0 and $key < 6) {
              $value['nam']= gaz_format_number($value['nam']);
           }
           $pdf->Cell($value['lun'],4,$value['nam'],1,0,'R');
}
$pdf->setRiporti('');

$pdf->Output();
?>