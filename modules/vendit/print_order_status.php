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

if (!isset($_GET['utsini']) || !isset($_GET['utsfin']) ||  !isset($_GET['status']) ) {
  header("Location: docume_vendit.php");
  exit;
} else {
  $status = intval($_GET['status']);
  $m = getOrders(date("d-m-Y",$_GET['utsini']),date("d-m-Y",$_GET['utsfin']),$status);
  require("../../config/templates/report_template.php");
  require("lang.".$admin_aziend['lang'].".php");
  $script_transl = $strScript["select_order_status.php"];
  $gazTimeFormatter->setPattern('dd MMMM yyyy');
  $luogo_data = $admin_aziend['citspe'].", lì ".ucwords($gazTimeFormatter->format(new DateTime()));
  $title =['luogo_data'=> $luogo_data,
           'title'=>'Situazione ordini ('.$script_transl['status_value'][$status] .') dal '.date("d-m-Y",$_GET['utsini']).' al '.date("d-m-Y",$_GET['utsfin']),
           'hile'=>[['lun' => 10,'nam'=>'Num.'],
                    ['lun' => 18,'nam'=>'Data'],
                    ['lun' => 60,'nam'=>'Cliente'],
                    ['lun' => 20,'nam'=>'Q ordine'],
                    ['lun' => 20,'nam'=>'Q evasa '],
                    ['lun' => 20,'nam'=>'€ ordine'],
                    ['lun' => 20,'nam'=>'€ evasi '],
                    ['lun' => 20,'nam'=>' Stato  ']
                   ]
            ];
  $aRiportare = ['top'=>[['lun' => 88,'nam'=>'da riporto : '],
                         ['lun' => 20,'nam'=>''],
                         ['lun' => 20,'nam'=>''],
                         ['lun' => 20,'nam'=>''],
                         ['lun' => 20,'nam'=>'']
                        ],
                 'bot'=>[['lun' => 88,'nam'=>'a riportare : '],
                         ['lun' => 20,'nam'=>''],
                         ['lun' => 20,'nam'=>''],
                         ['lun' => 20,'nam'=>''],
                         ['lun' => 20,'nam'=>'']
                        ]
                ];
  $pdf = new Report_template();
  $pdf->setVars($admin_aziend,$title);
  $pdf->SetTopMargin(43);
  $pdf->SetFooterMargin(22);
  $config = new Config;
  $pdf->SetFont('helvetica','',8);
  $pdf->AddPage();
  $aRiportare['top'][1]['nam'] = 0;
  $aRiportare['bot'][1]['nam'] = 0;
  $aRiportare['top'][2]['nam'] = 0;
  $aRiportare['bot'][2]['nam'] = 0;
  $aRiportare['top'][3]['nam'] = 0;
  $aRiportare['bot'][3]['nam'] = 0;
  $aRiportare['top'][4]['nam'] = 0;
  $aRiportare['bot'][4]['nam'] = 0;

  foreach ($m AS $key => $mv) {
    $aRiportare['top'][1]['nam'] = floatval($mv['tot']['qtotord']);
    $aRiportare['bot'][1]['nam'] = floatval($mv['tot']['qtotord']);
    $aRiportare['top'][2]['nam'] = floatval($mv['tot']['qtoteva']);
    $aRiportare['bot'][2]['nam'] = floatval($mv['tot']['qtoteva']);
    $aRiportare['top'][3]['nam'] = gaz_format_number($mv['tot']['vtotord']);
    $aRiportare['bot'][3]['nam'] = gaz_format_number($mv['tot']['vtotord']);
    $aRiportare['top'][4]['nam'] = gaz_format_number($mv['tot']['vtoteva']);
    $aRiportare['bot'][4]['nam'] = gaz_format_number($mv['tot']['vtoteva']);
    $pdf->setRiporti($aRiportare);
    $pdf->Cell(10,4,$mv['numdoc'],'LT',0,'R',0,'./admin_broven.php?id_tes='.$mv['id_tes'].'&Update',1);
    $pdf->Cell(18,4,gaz_format_date($mv['datemi']),'LT',0,'L',0,'',1);
    $pdf->Cell(60,4,$mv['ragso1'],'LT',0,'L',0,'',1);
    $pdf->Cell(4,4,$mv['unimis'],'LT',0,'L');
    $pdf->Cell(16,4,floatval(round($mv['totquanti_da_evadere'],5)),'T',0,'R');
    $pdf->Cell(4,4,$mv['unimis'],'LT',0,'L');
    $pdf->Cell(16,4,floatval(round($mv['totquanti_evaso'],5)),'T',0,'R');
    if ($mv['zerorow']) {
      $pdf->SetTextColor(255,0,0);
      $pdf->SetFont('dejavusans','',8);
      $pdf->Cell(20,4,'⚠'.gaz_format_number($mv['totimpbro_da_evadere']),'LT',0,'R');
      $pdf->SetTextColor(0);
      $pdf->SetFont('helvetica','',8);
    } else {
      $pdf->Cell(20,4,gaz_format_number($mv['totimpbro_da_evadere']),'LT',0,'R');
    }
    $pdf->Cell(20,4,gaz_format_number($mv['totimpdoc_evaso']),'LT',0,'R');
    switch($mv['stato_evasione']) { // 0 = Inevaso, 1 = Evasione parziale, 2 = Evaso
      case 0: // 0 = Inevaso
        $pdf->SetFillColor(217, 83, 83);
        $pdf->Circle($pdf->getX() + 2.5, $pdf->getY()+2,1.5,0,360,'F');
        $pdf->Cell(20,4,'INEVASO','LTR',1,'R');
      break;
      case 1: // 1 = Evasione parziale
        $pdf->SetFillColor(240, 173, 78);
        $pdf->Circle($pdf->getX() + 2.5, $pdf->getY()+2,1.5,0,360,'F');
        $pdf->Cell(20,4,'RESIDUO','LTR',1,'R');
        $pdf->SetFont('helvetica','',7);
        foreach ($mv['doc'] as $k=>$d){
          $pdf->Cell(168,4,'','T');
          $pdf->Cell(20,4,'n.'.$d['numdoc'].' del '.gaz_format_date($d['datemi']),'LTR',1,'R',0,'stampa_docven.php?id_tes='.$k,1);
        }
        $pdf->SetFont('helvetica','',8);
      break;
      case 2: // 2 = Evaso
        $pdf->SetFillColor(92, 184, 92);
        $pdf->Circle($pdf->getX() + 2.5, $pdf->getY()+2,1.5,0,360,'F');
        $pdf->Cell(20,4,'EVASO ','LTR',1,'R');
        $pdf->SetFont('helvetica','',7);
        foreach ($mv['doc'] as $k=>$d){
          $pdf->Cell(168,4,'','T');
          $pdf->Cell(20,4,'n.'.$d['numdoc'].' del '.gaz_format_date($d['datemi']),'LTR',1,'R',0,'stampa_docven.php?id_tes='.$k,1);
        }
        $pdf->SetFont('helvetica','',8);
      break;
    }
  }
  $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
  $pdf->Cell(188,1,'','T',1);
  $pdf->SetFont('helvetica','B',8);
  $pdf->Cell(88,5, 'Totali : ', 0, 0, 'R');
  $pdf->Cell(20,5, $aRiportare['top'][1]['nam'],1, 0, 'R');
  $pdf->Cell(20,5, $aRiportare['top'][2]['nam'],'BTR', 0, 'R');
  $pdf->Cell(20,5, '€ '.$aRiportare['top'][3]['nam'],'BTR', 0, 'R',1);
  $pdf->Cell(20,5, '€ '.$aRiportare['top'][4]['nam'],'BTR', 1, 'R',1);
  $pdf->setRiporti('');
  $pdf->Output();
}
?>
