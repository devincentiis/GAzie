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
require("../../config/templates/report_template.php");
$admin_aziend = checkAdmin();
$config = new Config;
require("lang." . $admin_aziend['lang'] . ".php");
if (!isset($_GET['datini']) || !isset($_GET['datfin'])) {
  header("Location: select_fatturato_pagamenti.php");
  exit;
}
$di=substr($_GET['datini'],0,10);
$df=substr($_GET['datfin'],0,10);
$datini = gaz_format_date($di,true);
$datfin = gaz_format_date($df,true);
$totale=0.00;
$totres=['noacc'=>0];
$query="SELECT ".$gTables['tesdoc'].".id_tes, ".$gTables['tesdoc'].".id_con, ".$gTables['tesdoc'].".clfoco, ".$gTables['tesdoc'].".pagame, ".$gTables['tesdoc'].".tipdoc, ".$gTables['tesdoc'].".seziva, ".$gTables['tesdoc'].".protoc, ".$gTables['tesdoc'].".datfat, ".$gTables['tesdoc'].".numfat, ".$gTables['tesdoc'].".id_contract, ".
$gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2
FROM ".$gTables['tesdoc']."
LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['tesdoc'].".clfoco = ".$gTables['clfoco'].".codice
LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id
WHERE tipdoc LIKE 'F%' AND datfat BETWEEN '".$datini."' AND '".$datfin."' GROUP BY seziva, protoc, datfat ORDER BY datfat, seziva, protoc";
$resoper = gaz_dbi_query($query);
while($r=gaz_dbi_fetch_array($resoper) ) {
  if ($r['tipdoc'] == 'FAI'||$r['tipdoc'] == 'FAA') {
    $r['descridoc'] = "Fattura Immediata";
  } elseif($r['tipdoc'] == 'FAF'){
    $r['descridoc'] = "Autofattura (TD26)";
  } elseif ($r['tipdoc'] == 'FAD') {
    $r['descridoc'] = "Fattura Differita";
  } elseif ($r['tipdoc'] == 'FAP'||$r['tipdoc'] == 'FAQ') {
    $r['descridoc'] = "Parcella";
  } elseif ($r['tipdoc'] == 'FNC') {
    $r['descridoc'] = "Nota Credito";
  } elseif ($r['tipdoc'] == 'FND') {
    $r['descridoc'] = "Nota Debito";
  } else {
    $r['descridoc'] = "DOC.SCONOSCIUTO";
  }
  $importo = gaz_dbi_get_row($gTables['rigmoc'], 'id_tes', $r['id_con'], "AND codcon = ".$r['clfoco']);
  if ($importo && $importo['import'] >= 0.01 ) {
    $r['class']='';
    if (substr($r['tipdoc'],2,1)=='C'){
      $importo['import'] = -$importo['import'];
      $r['class']='R';
    }
    $totale+=$importo['import'];
    if (isset($totres[$r['pagame']])) {
      $totres[$r['pagame']] +=  $importo['import'];
    } else {
      $totres[$r['pagame']] =  $importo['import'];
    }
    $r['importo'] = $importo['import'];
  } else {
    // incremento il numero di documenti non contabilizzati per metterli in evidenza
    $totres['noacc']++;
    $r['importo'] = 'NON CONTABILIZZATA';
    $r['class']='D';
  }
  $movres[$r['pagame']][] = $r;
}
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data = $admin_aziend['citspe'] . ", lì " . ucwords($gazTimeFormatter->format(new DateTime()));
$title=['luogo_data'=>$luogo_data,
        'title'=>'Fatturato per tipo di pagamento dal '.$di.' al '.$df,
        'hile'=>[['lun'=>20,'nam'=>'Data'],
                 ['lun'=>10,'nam'=>'Prot.'],
                 ['lun'=>40,'nam'=>'N.'],
                 ['lun'=>85,'nam'=>'Cliente'],
                 ['lun'=>40,'nam'=>'Importo']
                ]
       ];
$pdf = new Report_template();
$pdf->setVars($admin_aziend, $title);
$pdf->SetTopMargin(40);
$pdf->SetFooterMargin(15);
$pdf->AddPage();
$pdf->setRiporti('');
$pdf->SetFillColor(235, 235, 235);
foreach($movres as $kpag=>$vpag){
  $pagame=gaz_dbi_get_row($gTables['pagame'], 'codice', $kpag);
  $pdf->SetFont('helvetica','B',10);
  $pdf->Cell(195,5,' Pagamento: '.$pagame['descri'],1,1,'L',1);
  $pdf->SetFont('helvetica','',8);
  foreach($vpag as $v){
    if ($v['class']=='R'){
      $pdf->SetTextColor(200,0,0);
    } elseif ($v['class']=='D'){
      $pdf->SetTextColor(200,0,0);
      $pdf->SetFont('helvetica','B',8);
    }
    // Data
    $pdf->Cell(20,4,gaz_format_date($v['datfat']),1,0,'C');
    // Protocollo/Sezione
    $pdf->Cell(10,4,$v['protoc'],1,0,'C');
    // Numero
    $pdf->Cell(40,4,' '.$v['descridoc'].' n.'.$v['numfat'].'/'.$v['seziva'],1);
    // Cliente
    $pdf->Cell(85,4,$v['ragso1']. '' .$v['ragso2'],1,0,'L',0,'',1);
    // Importo
    if ($v['importo']<>'NON CONTABILIZZATA') {
      $v['importo']=gaz_format_number($v['importo']);
    }
    $pdf->Cell(40,4,$v['importo'],1,1,'R');
    $pdf->SetTextColor(0,0,0);
  }
}

$pdf->Ln(6);
$pdf->SetFont('helvetica','B',10);
$pdf->Cell(50,5);
$pdf->Cell(100,5,'T O T A L I    R A G G R U P P A T I','LTR',1,'C');
foreach($totres as $k=>$v){
  $pdf->SetFont('helvetica','',9);
  if ($k=='noacc'){
    if ($v>=1) {
      $pdf->SetTextColor(200,0,0);
      $pdf->SetFont('helvetica','B',9);
      $pdf->Cell(50,5);
      $pdf->Cell(100,5,$v.' FATTURE NON CONTABILIZZATE','LR',1);
      $pdf->SetTextColor(0,0,0);
    }
  } else {
    $pagame=gaz_dbi_get_row($gTables['pagame'], 'codice', $k);
    $pdf->Cell(50,5);
    $pdf->Cell(65,5,$k.' - '.$pagame['descri'],'L');
    $pdf->Cell(35,5,gaz_format_number($v),'R',1,'R');
  }
}
$pdf->Cell(50,5);
$pdf->SetFont('helvetica','B',9);
$pdf->Cell(65,5,'TOTALE CONTABILIZZATO','LB');
$pdf->Cell(35,5,'€ '.gaz_format_number($totale),'RB',1,'R');
$pdf->Output('Fatturato per tipo di pagamento');
?>
