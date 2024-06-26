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
if (!ini_get('safe_mode')) { //se me lo posso permettere...
    ini_set('memory_limit', '128M');
    gaz_set_time_limit(0);
}
if (!isset($_GET['orderby'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
require("../../config/templates/report_template.php");

$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data = $admin_aziend['citspe'] . ", lì " . ucwords($gazTimeFormatter->format(new DateTime()));

$item_head = array('top' => array(array('lun' => 80, 'nam' => 'Descrizione'),
        array('lun' => 25, 'nam' => 'Numero Conto')
    )
);
/* ENRICO FEDELE */
/* Modifico larghezza e intestazione delle colonne */
$title = array('luogo_data' => $luogo_data,
    'title' => "LISTA DELLE PARTITE APERTE ",
    'hile' => array(array('lun' => 45, 'nam' => 'Fornitore'),
        array('lun' => 20, 'nam' => 'ID Partita'),
        array('lun' => 41, 'nam' => 'Descrizione'),
        array('lun' => 11, 'nam' => 'N.Doc.'),
        array('lun' => 13, 'nam' => 'D. Doc.'),
        array('lun' => 13, 'nam' => 'D. Reg.'),
        array('lun' => 15, 'nam' => 'Avere'),
        array('lun' => 15, 'nam' => 'Dare'),
        array('lun' => 13, 'nam' => 'Scad.')
    )
);
/* ENRICO FEDELE */
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
$pdf->SetFillColor(238, 238, 238);
$pdf->setRiporti('');
$pdf->AddPage();
$config = new Config;
$scdl = new Schedule;
$m = $scdl->getScheduleEntries(intval($_GET['orderby']), $admin_aziend['masfor']);
if (sizeof($scdl->Entries) > 0) {
    $ctrl_partner = 0;
    $ctrl_id_tes = 0;
    $ctrl_paymov = 0;

    /* ENRICO FEDELE */
    /* Inizializzo la variabili per il totale */
    $tot_dare = 0;
    $tot_avere = 0;
    /* ENRICO FEDELE */
	foreach ($scdl->Entries AS $key => $mv) {
        $pdf->SetFont('helvetica', '', 6);
        $border_partner = 0;
        $partner = '';
        $id_tes = '';
        $paymov = '';
        $border_paymov = 'LR';
        if ($mv["clfoco"] <> $ctrl_partner) {
            if ($ctrl_partner > 0) {
                $pdf->Cell(45, 1);
                $pdf->Cell(20, 1, '', 'T', 1);
            } else {
                $pdf->Ln(1);
            }
            $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
            $border_partner = 1;
            $partner = $mv["ragsoc"];
        }
        if ($mv["id_tes"] <> $ctrl_id_tes) {
            $id_tes = $mv["id_tes"];
			if ($mv['datdoc'] != '0000-00-00') {
				$mv["datdoc"] = gaz_format_date($mv["datdoc"]);
			} else {
				$mv['datdoc'] = '';
			}
        } else {
            $mv['descri'] = '';
            $mv['numdoc'] = '';
            $mv['seziva'] = '';
            $mv['datdoc'] = '';
            $partner = '';
        }
        if ($mv["id_tesdoc_ref"] <> $ctrl_paymov) {
            $paymov = $mv["id_tesdoc_ref"];
            $border_paymov = 1;
            $scdl->getStatus($paymov);
            $r = $scdl->Status;
            if ($r['sta'] == 1) { // CHIUSA
                $pdf->SetFillColor(230, 255, 230);
            } elseif ($r['sta'] == 2) { // ESPOSTA
                $pdf->SetFillColor(255, 245, 185);
            } elseif ($r['sta'] == 3) { // SCADUTA
                $pdf->SetFillColor(255, 160, 160);
            } elseif ($r['sta'] == 9) { // PAGAMENTO ANTICIPATO
                $pdf->SetFillColor(190, 190, 255);
            } else { // APERTA
                $pdf->SetFillColor(230, 255, 230);
            }
        }
        $descri_doc = $mv["numdoc"];
        if (empty($mv["numdoc"])) {
            $descri_doc = '';
        }
        if ($mv["id_rigmoc_doc"] == 0) {
            $expiry = '';
        } else {
            $expiry = gaz_format_date($mv["expiry"]);
        }
        $pdf->Cell(45, 4, $partner, $border_partner, 0, '', 0, '', 1);
        $pdf->Cell(20, 4, $paymov, $border_paymov, 0, 'R', 1, '', 2);
        $pdf->Cell(41, 4, $mv['descri'], 1, 0, 'C', 0, '', 1);
        $pdf->Cell(11, 4, $descri_doc, 1, 0, 'R', 0, '', 1);

        /* ENRICO FEDELE */
        /* Modifico la larghezza delle celle */
        $pdf->Cell(13, 4, $mv["datdoc"], 1, 0, 'C');
        $pdf->Cell(13, 4, gaz_format_date($mv["datreg"]), 1, 0, 'C');
        if ($mv['darave'] == 'D') {
            /* Incremento il totale del dare */
            $tot_dare += $mv['amount'];
            /* Modifico la larghezza delle celle */
            $pdf->Cell(15, 4, gaz_format_number($mv['amount']), 1, 0, 'R');
            $pdf->Cell(15, 4, '', 1, 0, 'R');
        } else {
            /* Incremento il totale dell'avere, e decremento quello del dare */
            $tot_avere += $mv['amount'];
            $tot_dare -= $mv['amount'];
            /* Modifico la larghezza delle celle */
            $pdf->Cell(15, 4, '', 1, 0, 'R');
            $pdf->Cell(15, 4, gaz_format_number($mv['amount']), 1, 0, 'R');
        }
        /* Modifico la larghezza della cella */
        $pdf->Cell(13, 4, $expiry, 1, 1, 'C');
        /* ENRICO FEDELE */
        $ctrl_partner = $mv["clfoco"];
        $ctrl_id_tes = $mv["id_tes"];
        $ctrl_paymov = $mv["id_tesdoc_ref"];
    }
    $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
    /* ENRICO FEDELE */
    /* Stampo una riga vuota sottile per separare leggermente il totale e metterlo in evidenza */
    $pdf->SetFont('helvetica', '', 1);
    $pdf->Cell(186, 1, '', 1, 1, 'C');

    /* Stampo la riga del totale, in grassetto italico "BI" */
    $pdf->SetFont('helvetica', 'BI', 6);
    $pdf->Cell(143, 4, 'TOTALE', 1, 0, 'R', false);

    $pdf->Cell(15, 4, gaz_format_number($tot_dare), 1, 0, 'R', false);
    $pdf->Cell(15, 4, gaz_format_number($tot_avere), 1, 0, 'R', true);
    /* Aggiunta la percentuale dell'avere rispetto al totale dare+avere, colorata come la cella avere per renderla intuitiva */
    $pdf->Cell(13, 4, gaz_format_number(100 * $tot_avere / ($tot_dare + $tot_avere)) . " %", 1, 1, 'C', true);
    /* ENRICO FEDELE */
}
$pdf->setRiporti('');
$pdf->Output();
?>
