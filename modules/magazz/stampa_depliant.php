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
$template='templates';
if (strlen($admin_aziend['template']) > 1) {
	$template .= '.'.$admin_aziend['template'];
}
require("../../config/".$template."/report_template.php");
if (!isset($_GET['ci']) or ! isset($_GET['cf']) or ! isset($_GET['ai']) or ! isset($_GET['af'])) {
    header("Location: select_deplia.php");
    exit;
}
if (empty($_GET['af'])) {
    $_GET['af'] = 'zzzzzzzzzzzzzzz';
}

$listino = 'preve1';
if (isset($_GET['li'])) {
    if (substr($_GET['li'], 0, 3) == '2') {
        $listino = 'preve2';
    } elseif (substr($_GET['li'], 0, 3) == '3') {
        $listino = 'preve3';
    } elseif (substr($_GET['li'], 0, 3) == '4') {
        $listino = 'preve4';
    } elseif (substr($_GET['li'], 0, 3) == 'web') {
        $listino = 'web_price';
    }
}
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data = $admin_aziend['citspe'] . ", lì ";
if (isset($_GET['ds'])) {
  $giosta = substr($_GET['ds'], 0, 2);
  $messta = substr($_GET['ds'], 2, 2);
  $annsta = substr($_GET['ds'], 4, 4);
  $utssta = mktime(0, 0, 0, $messta, $giosta, $annsta);
  $luogo_data .= ucwords($gazTimeFormatter->format(new DateTime($annsta.'-'.$messta.'-'.$giosta)));
} else {
  $luogo_data .= ucwords($gazTimeFormatter->format(new DateTime()));
}

class Depliant extends Report_template {

    function printItem($code, $description, $price = '', $um = '', $un = 0, $note = '', $image = '', $barcode = '', $link = false, $vat = '') {
        global $admin_aziend;
        $this->SetFillColor(hexdec(substr($this->colore, 0, 2)), hexdec(substr($this->colore, 2, 2)), hexdec(substr($this->colore, 4, 2)));
        $this->SetFont('helvetica', '', 9);
        if (floatval($price) < 0.00001) {
            $price = '';
            $vat = '';
            $um = '';
        } else {
            $price = number_format($price, $admin_aziend['decimal_price'], ',', '.');
        }
        $x = $this->GetX();
        $y = $this->GetY();
        $lf = 0;
        if (!empty($image)) {
            if ($x > 20) {
                $lf = 1;
                $y -= 20;
                $x = 103;
                $this->SetY($y);
                $this->SetX($x);
            }
            if (!$link) {
                $link = 'admin_artico.php?codice=' . $code . '&Update';
            }
            try {   //FP: intercetto gli errori per immagini corrotte
                $livelloPrecedente = error_reporting(E_ALL ^ E_WARNING);
                $im = imagecreatefromstring($image); // Antonio de Vincentiis: questa funzione da problemi di fatal error con alcune immagini, quando corrotte. Per capire qual'è l'immagine che causa il problema basta decommentare il rigo di sotto e lanciare lo script
                //print $code.'<br>';
                $ratio=imagesx($im)/imagesy($im);
                $xx=73;
                $yy=0;
                if ($ratio>0.8){ // ho una immagine troppo larga per essere contenuta in 20
                    $w=20; // impongo venti come larghezza
                    $h=20/$ratio;// ... e diminuisco l'altezza con il ratio
                    $yy=12.5-$h/2; // faccio il padding verticale
                } else { // immagine che non entra per altezza
                    $w=25*$ratio; // e diminuisco la larghezza con il ratio
                    $h=25; // impongo l'altezza
                    $xx=93-($w/2)*2; // faccio il padding orizzontale
                }
                $this->Image('@'.$image,$x+$xx,$y+$yy,$w, $h,'',$link,'R',false,'300','',false,false,'R',false);
            } catch (Exception $exc) {
//                echo $exc->getTraceAsString();
                $this->Cell($x, $y, "Immagine non disponibile");
            } finally {
                error_reporting($livelloPrecedente);
            }

            $this->Cell(93, 5, $code, 'LTR', 2, 'L', 0, '', 1);
			if (strlen($description)>110) {
                 $this->Cell(70, 5, substr($description,0,(strlen($description)/2)), 'L', 2, 'L', 0, '', 1);
                 $this->Cell(70, 5, substr($description,(strlen($description)/2)), 'L', 2, 'L', 0, '', 1);
			} else {
				$this->Cell(70, 5, $description, 'L', 2, 'L', 0, '', 1);
				$this->cell(70, 5,'','L',2,'L',0,'',1);
			}

            if ($un > 0) {
                $un .= ' N./Pack';
            } else {
                $un = '';
            }
            $this->Cell(93, 5, $price . ' ' . $admin_aziend['symbol'] . '/' . $um . ' ' . $vat . ' ' . $un, 'LR', 2);
            $this->Cell(73, 5, $note, 'LB', 0, 'L',0,'',1);
            $this->Cell(20, 5, '', 'BR', $lf, 'R');
        } elseif (!empty($barcode)) {
            if ($x > 20) {
                $lf = 1;
                $y -= 20;
                $x = 103;
                $this->SetY($y);
                $this->SetX($x);
            }
            $this->EAN13($x + 50, $y + 10, $barcode, 6);
            $this->SetY($y);
            $this->SetX($x);
			if (strlen($description)>110) {
				$this->Cell(93, 5, $code . ' - ' . substr($description,0,(strlen($description)/2)), 'LTR', 2, 'L', 0, '', 1);
				$this->Cell(93, 5, substr($description,(strlen($description)/2)), 'LR', 2, 'L', 0, '', 1);
			} else {
				$this->Cell(93, 5, $code . ' - ' . substr($description,0,(strlen($description)/2)), 'LTR', 2, 'L', 0, '', 1);
				$this->Cell(93, 5, substr($description,(strlen($description)/2)), 'LR', 2, 'L', 0, '', 1);
			}
            $this->Cell(93, 5, '', 'LR', 2);
            $this->Cell(93, 5, $price . ' ' . $admin_aziend['symbol'] . '/' . $um, 'LR', 2);
            $this->Cell(93, 5, $vat, 'LBR', $lf);
        } else {
            if ($x > 20) {
                $lf = 1;
                $y -= 20;
                $x = 103;
                $this->SetY($y);
                $this->SetX($x);
            }
			 $this->Cell(93, 5, $code, 'LTR', 2, 'L', 0, '', 1);
			if (strlen($description)>110) {
                 $this->Cell(93, 5, substr($description,0,(strlen($description)/2)), 'LR', 2, 'L', 0, '', 1);
                 $this->Cell(93, 5, substr($description,(strlen($description)/2)), 'LR', 2, 'L', 0, '', 1);
			} else {
				$this->Cell(93, 5, $description, 'LR', 2, 'L', 0, '', 1);
				$this->cell(93, 5,'','LR',2,'L',0,'',1);
			}

            if ($un > 0) {
                $un .= ' N./Pack';
            } else {
                $un = '';
            }
            $this->Cell(93, 5, $price . ' ' . $admin_aziend['symbol'] . '/' . $um . ' ' . $vat . ' ' . $un, 'LR', 2);
            $this->Cell(73, 5, $note, 'LB', 0, 'R');
            $this->Cell(20, 5, '', 'BR', $lf, 'R');
        }
    }

    function printGroupItem($code, $description, $image = '', $link = false) {
        $this->SetFillColor(hexdec(substr($this->colore, 0, 2)), hexdec(substr($this->colore, 2, 2)), hexdec(substr($this->colore, 4, 2)));
        $x = $this->GetX();
        $y = $this->GetY();
        $this->SetFont('helvetica', '', 10);
        if ($x > 20) {
            $this->SetY($y + 5);
            $this->SetX(10);
        }
        if (empty($image)) {
            $this->Cell(120, 6, 'Cat.Merceologica: ' . $code . ' - ' . $description, 'T', 1, 'L', 1);
        } else {
            if (!$link) {
                $link = 'admin_catmer.php?codice=' . $code . '&Update';
            }
            $this->Image('@' . $image, $x + 120, $y + 1, 0, 19, '', $link);
            $this->Cell(120, 20, 'Cat.Merceologica: ' . $code . ' - ' . $description, 'T', 1, 'L', 1);
        }
    }

}

$title = array('luogo_data' => $luogo_data, 'title' => 'C A T A L O G O', 'hile' => array());

$where = $gTables['catmer'] . ".codice BETWEEN " . intval($_GET['ci']) . " AND " . intval($_GET['cf']) . " AND " .
        $gTables['artico'] . ".codice BETWEEN '" . substr($_GET['ai'], 0, 15) . "' AND '" . substr($_GET['af'], 0, 15) . "' AND id_assets = 0";
$result = gaz_dbi_dyn_query($gTables['artico'] . ".codice AS codart," .
        $gTables['artico'] . ".depli_public AS depli," .
        $gTables['artico'] . ".descri AS desart," .
        $gTables['artico'] . ".image AS imaart," .
        $gTables['artico'] . ".catmer," .
        $gTables['artico'] . ".unimis," .
        $gTables['artico'] . ".barcode AS barcod," .
        $gTables['artico'] . ".web_url AS linkart," .
        $gTables['artico'] . ".web_mu," .
        $gTables['artico'] . ".web_multiplier," .
        $gTables['artico'] . ".$listino AS prezzo," .
        $gTables['artico'] . ".annota AS annart," .
        $gTables['artico'] . ".pack_units AS units," .
        $gTables['catmer'] . ".descri AS descat," .
        $gTables['catmer'] . ".image AS imacat," .
        $gTables['catmer'] . ".codice AS codcat," .
        $gTables['catmer'] . ".web_url AS linkcat," .
        $gTables['aliiva'] . ".aliquo," .
        $gTables['catmer'] . ".annota AS anncat ", $gTables['artico'] . " LEFT JOIN " . $gTables['aliiva'] . " ON " . $gTables['artico'] . ".aliiva = " . $gTables['aliiva'] . ".codice " .
        " LEFT JOIN " . $gTables['catmer'] . " ON " . $gTables['artico'] . ".catmer = " . $gTables['catmer'] . ".codice", $where, "codcat, codart");
$pdf = new Depliant();
$pdf->setVars($admin_aziend, $title);
$pdf->Open();
$pdf->SetTopMargin(32);
$pdf->setFooterMargin(10);
$pdf->AddPage();
$ctrl_cm = 0;
while ($row = gaz_dbi_fetch_array($result)) {
    if ($row['depli'] < 1) {
        continue;
    }
    if (isset($_GET['bc']) && intval($_GET['bc']) == 1) { // per stampare i barcode in luogo delle immagini
        $row['imaart'] = '';
        $row['imacat'] = '';
    } else {
        $row['barcod'] = '';
    }
    $vat = '+IVA ' . floatval($row['aliquo']) . '%';
    if ($listino == 'web_price') {
        $price = $row['prezzo'] * $row['web_multiplier'];
        $row['unimis'] = $row['web_mu'];
    } else {
        $price = $row['prezzo'];
    }
    if ($row['codcat'] <> $ctrl_cm) {
		if (!isset($_GET['bc']) || $_GET['jumpcat']!="on"){
			if ($pdf->GetY() > 250) {
				$pdf->AddPage();
			}
		} else {
			if ($ctrl_cm > 0) {
				$pdf->AddPage();
			}
		}
		$pdf->printGroupItem($row['codcat'], $row['descat'], $row['imacat'], $row['linkcat']);
    }
    if (!empty($row['imaart']) || !empty($row['barcod'])) {
        if ($pdf->GetY() > 235 && $pdf->GetX() > 90) {
            $pdf->printItem($row['codart'], $row['desart'], $price, $row['unimis'], $row['units'], $row['annart'], $row['imaart'], substr($row['barcod'], 0, 13), $row['linkart'], $vat);
            $pdf->AddPage();
        } elseif ($pdf->GetY() > 235) {
            $pdf->AddPage();
            $pdf->printItem($row['codart'], $row['desart'], $price, $row['unimis'], $row['units'], $row['annart'], $row['imaart'], substr($row['barcod'], 0, 13), $row['linkart'], $vat);
        } else {
            $pdf->printItem($row['codart'], $row['desart'], $price, $row['unimis'], $row['units'], $row['annart'], $row['imaart'], substr($row['barcod'], 0, 13), $row['linkart'], $vat);
        }
    } else {
		if ($pdf->GetY() > 235 && $pdf->GetX() > 90) {
			$pdf->printItem($row['codart'], $row['desart'], $price, $row['unimis'], $row['units'], $row['annart'], $row['imaart'], substr($row['barcod'], 0, 13), $row['linkart'], $vat);
			$pdf->AddPage();
		} elseif ($pdf->GetY() > 235) {
			$pdf->printItem($row['codart'], $row['desart'], $price, $row['unimis'], $row['units'], $row['annart'], $row['imaart'], substr($row['barcod'], 0, 13), $row['linkart'], $vat);
			$pdf->AddPage();
		} else {
			$pdf->printItem($row['codart'], $row['desart'], $price, $row['unimis'], $row['units'], $row['annart'], $row['imaart'], substr($row['barcod'], 0, 13), $row['linkart'], $vat);
		}
    }
    $ctrl_cm = $row['codcat'];
}
$pdf->Output();
?>
