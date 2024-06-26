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
$admin_aziend = checkAdmin();
require("./lang." . $admin_aziend['lang'] . ".php");
$script_transl = $strScript["select_partner_status.php"];


if (!ini_get('safe_mode')) { //se me lo posso permettere...
    ini_set('memory_limit', '128M');
    gaz_set_time_limit(0);
}
if (!isset($_GET['date'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
require("../../config/templates/report_template.php");
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data=$admin_aziend['citspe'].", lì ".ucwords($gazTimeFormatter->format(new DateTime()));
$item_head = array('top' => array(array('lun' => 80, 'nam' => 'Descrizione'),
        array('lun' => 25, 'nam' => 'Numero Conto')
    )
);
$acc = array();
foreach ($script_transl['header'] as $k => $v) {
    $acc[] = $k;
}
$title = array('luogo_data' => $luogo_data,
    'title' => $script_transl['print_title'],
    'hile' => array(array('lun' => 28, 'nam' => $acc[0]),
        array('lun' => 28, 'nam' => $acc[1]),
        array('lun' => 28, 'nam' => $acc[2]),
        array('lun' => 28, 'nam' => $acc[3]),
        array('lun' => 28, 'nam' => $acc[4]),
        array('lun' => 28, 'nam' => $acc[5]),
        array('lun' => 18, 'nam' => $acc[6])
    )
);
$aRiportare = array('top' => array(array('lun' => 166, 'nam' => 'da riporto : '),
        array('lun' => 20, 'nam' => '')
    ),
    'bot' => array(array('lun' => 166, 'nam' => 'a riportare : '),
        array('lun' => 20, 'nam' => '')
    )
);
$pdf = new Report_template();
$pdf->setVars($admin_aziend, $title);
$pdf->setFooterMargin(22);
$pdf->setTopMargin(43);
$pdf->setRiporti('');
$pdf->AddPage();
$paymov = new Schedule;
if (isset($_GET['clfoco'])){
	$admin_aziend['mascli']=intval($_GET['clfoco']);
}

$paymov->setScheduledPartner($admin_aziend['mascli']);
if (sizeof($paymov->Partners) > 0) {
    $anagrafica = new Anagrafica();
    $partner_firstline = false;
    foreach ($paymov->Partners as $p) {
        if ( $partner_firstline ) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell(186, 5, 'SALDO PARTITE: '.$partner_firstline, 1, 1, 'R', 0, '', 1);
            $partner_firstline = false;
            $pdf->SetFont('helvetica', '', 8);
        };
        $prt = $anagrafica->getPartner($p);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
        $pdf->Ln(2);
        $pdf->Cell(186, 5, $prt['ragso1'] . " " . $prt['ragso2'] . " tel:" . $prt['telefo'] . " fax:" . $prt['fax'] . " mob:" . $prt['cell'] . " ", 1, 1, '', 1, '', 1);
        $pdf->SetFont('helvetica', '', 8);
        $paymov->getPartnerStatus($p, substr($_GET['date'], 0, 10));
        foreach ($paymov->PartnerStatus as $k => $v) {
            if ( !$partner_firstline ) {
                $partner_firstline=gaz_format_number($paymov->docData[$k]['saldo']);
            };
            $pdf->SetTextColor(255, 0, 0);
            $pdf->SetFillColor(230, 255, 230);
            $pdf->Cell(56, 5, "REF: " . $k, 1, 0, '', 1, '', 1);
            $pdf->SetTextColor(0);
            if ($paymov->docData[$k]['numdoc'] >= 1) {
                $pdf->Cell(130, 5, $paymov->docData[$k]['descri'] . ' n.' .
                        $paymov->docData[$k]['numdoc'] . '/' .
                        $paymov->docData[$k]['seziva'] . ' del ' .
                        gaz_format_date($paymov->docData[$k]['datdoc'])
                        , 1, 1, '', 1, '', 1);
            } else {
                $pdf->Cell(130, 5, $paymov->docData[$k]['descri'], 1, 1, '', 1, '', 1);
            }
            foreach ($v as $ki => $vi) {
                $pdf->SetFillColor(170, 255, 170);
                $v_op = '';
                $cl_exp = '';
                if ($vi['op_val'] >= 0.01) {
                    $v_op = gaz_format_number($vi['op_val']);
                }
                $v_cl = '';
                if ($vi['cl_val'] >= 0.01) {
                    $v_cl = gaz_format_number($vi['cl_val']);
                    $cl_exp = gaz_format_date($vi['cl_exp']);
                }
                $expo = '';
                if ($vi['expo_day'] >= 1) {
                    $expo = $vi['expo_day'];
                    if ($vi['cl_val'] == $vi['op_val']) {
                        $vi['status'] = 2; // la partita è chiusa ma è esposta a rischio insolvenza
                        $pdf->SetFillColor(255, 245, 185);
                        $class_paymov = 'FacetDataTDevidenziaOK';
                    }
                } else {
                    if ($vi['cl_val'] == $vi['op_val']) { // chiusa e non esposta
                        $cl_exp = '';
                        $pdf->SetFillColor(230, 255, 230);
                    } elseif ($vi['status'] == 3) { // SCADUTA
                        $cl_exp = '';
                        $pdf->SetFillColor(255, 160, 160);
                    } elseif ($vi['status'] == 9) { // PAGAMENTO ANTICIPATO
                        $pdf->SetFillColor(190, 190, 255);
                        $vi['expiry'] = $vi['cl_exp'];
                    }
                }
                $pdf->Cell(28, 4, $vi['id'], 'LTB', 0, 'C', 0, '', 1);
                $pdf->Cell(28, 4, $v_op, 1, 0, 'R');
                $pdf->Cell(28, 4, gaz_format_date($vi['expiry']), 1, 0, 'C', 0, '', 1);
                $pdf->Cell(28, 4, $v_cl, 1, 0, 'R');
                $pdf->Cell(28, 4, $cl_exp, 1, 0, 'C');
                $pdf->Cell(28, 4, $expo, 1, 0, 'C');
                $pdf->Cell(18, 4, $script_transl['status_value'][$vi['status']], 1, 1, 'C', 1);
            }
        }
    }
    if ( $partner_firstline ) {
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(186, 5, 'SALDO PARTITE: '.$partner_firstline, 1, 1, 'R', 0, '', 1);
        $partner_firstline = false;
        $pdf->SetFont('helvetica', '', 8);
    };

}
$pdf->setRiporti('');
$pdf->Output();
?>
