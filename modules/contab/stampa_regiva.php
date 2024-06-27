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
$admin_aziend = checkAdmin();
if (!ini_get('safe_mode')) { //se me lo posso permettere...
    ini_set('memory_limit', '128M');
    gaz_set_time_limit(0);
}
if (!isset($_GET['vr']) ||
        !isset($_GET['vs']) ||
        !isset($_GET['pi']) ||
        !isset($_GET['sd']) ||
        !isset($_GET['jp']) ||
        !isset($_GET['so']) ||
        !isset($_GET['cv']) ||
        !isset($_GET['ri']) ||
		!isset($_GET['lm']) ||
        !isset($_GET['rf'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

require("../../config/templates/standard_template.php");

class vatBook extends Standard_template {
  public $azienda;
  public $desregrc;
  public $script_transl ;
  public $endyear;
  public $vatsect;
  public $rc_sect;
  public $typbook;
  public $semplificata;
  public $inidate;
  public $enddate;
  public $logo;
  public $colore;
  public $link;
  public $intesta1;
  public $intesta2;
  public $intesta3;
  public $intesta4;
  public $luogo;
  public $n_page;
  public $rows;
  public $vat_castle;
  public $acc_castle;
  public $vat_castle_liq;
  public $acc_rows;
  public $acc_operation_type;
  public $taxable;
  public $tax;
  public $taxable_liq;
  public $tax_liq;
  public $top_bar;
  public $top_carry_bar;
  public $bot_carry_bar;

  function setData($data, $gTables, $admin_aziend) {
    $this->azienda = $admin_aziend;
    $this->desregrc = strtolower($admin_aziend['desez'.$admin_aziend['reverse_charge_sez']]);
    require("lang." . $admin_aziend['lang'] . ".php");
    $this->script_transl = $strScript['stampa_regiva.php'];
    $this->endyear = substr($data['f'], 4, 4);
    $this->vatsect = intval($data['vs']);
    $this->rc_sect = $admin_aziend['reverse_charge_sez'];
    $this->typbook = intval($data['vr']);
    $this->semplificata = intval($data['so']);
    $this->inidate = date("Ymd", mktime(0, 0, 0, substr($data['i'], 2, 2), substr($data['i'], 0, 2), substr($data['i'], 4, 4)));
    $this->enddate = date("Ymd", mktime(0, 0, 0, substr($data['f'], 2, 2), substr($data['f'], 0, 2), substr($data['f'], 4, 4)));
  }

  function getRows($gTables) { // recupera i righi dell'intervallo settato
    //recupero i movimenti IVA del conto insieme alle relative testate
    $what = $gTables['tesmov'] . ".*, " .
            $gTables['rigmoi'] . ".*,
    DATE_FORMAT(datliq,'%Y%m%d') AS dl,
            DATE_FORMAT(datreg,'%Y%m%d') AS dr,
            CONCAT(" . $gTables['anagra'] . ".ragso1, ' '," . $gTables['anagra'] . ".ragso2) AS ragsoc, " .
            $gTables['aliiva'] . ".descri AS desiva ";
    $table = $gTables['rigmoi'] . " LEFT JOIN " . $gTables['tesmov'] . " ON (" . $gTables['rigmoi'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes)
            LEFT JOIN " . $gTables['clfoco'] . " ON (" . $gTables['tesmov'] . ".clfoco = " . $gTables['clfoco'] . ".codice)
            LEFT JOIN " . $gTables['anagra'] . " ON (" . $gTables['anagra'] . ".id = " . $gTables['clfoco'] . ".id_anagra)
            LEFT JOIN " . $gTables['aliiva'] . " ON (" . $gTables['rigmoi'] . ".codiva = " . $gTables['aliiva'] . ".codice)";
    $orderby = "datreg ASC , protoc ASC, id_rig ASC";
    $where = "(datreg BETWEEN ".$this->inidate." AND ".$this->enddate." OR datliq BETWEEN ".$this->inidate." AND ".$this->enddate.") AND seziva = ".$this->vatsect." AND regiva = ".$this->typbook;
    //$where = "datreg BETWEEN " . $this->inidate . " AND " . $this->enddate . " AND seziva = " . $this->vatsect . " AND regiva = " . $this->typbook;
    $result = gaz_dbi_dyn_query($what, $table, $where, $orderby);
    $this->rows = array();
    $this->vat_castle = array();
    $this->vat_castle_liq = array();
    $this->acc_castle = array();
    $this->acc_operation_type[0] = 0;
    $this->taxable = 0.00;
    $this->tax = 0.00;
    $this->taxable_liq = 0.00;
    $this->tax_liq = 0.00;
    $ctrl_idtes = 0;
    while ($mov = gaz_dbi_fetch_array($result)) {
      $reg_yes=false;
      $codiva = $mov['codiva'];
      $id_tes = $mov['id_tes'];
      $op_typ = trim($mov['operation_type']);
      if ($op_typ == '') {
        $op_typ = 0;
      }
      switch ($mov['operat']) {
        case "1":
          $taxable = $mov['imponi'];
          $tax = $mov['impost'];
          break;
        case "2":
          $taxable = -$mov['imponi'];
          $tax = -$mov['impost'];
          break;
        default:
          $taxable = 0;
          $tax = 0;
          break;
      }
      if ($this->typbook==9){
        $taxable = 0;
        $tax = $mov['impost'];
      }
      // INIZIO TIPIZZAZIONE MOVIMENTI PER DISTINGUERE QUELLI CHE VANNO SUL REGISTRO DEL PERIODO
      // DA QUELLI CHE PARTECIPANO ALLA LIQUIDAZIONE IVE DEL PERIODO SELEZIONATO
      // INIZIO MOVIMENTI DI REGISTRO
      $mov['liq_class']='';
      if($mov['dr']<$this->inidate){ // fattura pregressa, precedente al periodo selezionato ma che concorre alla liquidazione
        $mov['liq_class']='danger';
      }elseif($mov['dr']>$this->enddate){// fattura successiva al periodo selezionato ma che concorre alla liquidazione es. acquisto egistrato nei 15gg successivi
        $mov['liq_class']='danger';
      }else{ // fatture che fanno parte del registro
  			$reg_yes=true; // il movimento fa parte del registro, a prescidere che sia liquidabile o meno
        $this->taxable += $taxable;
        if ($mov['tipiva'] != 'D' && $mov['tipiva'] != 'T') { // se NON indetraibili o split payment
          $this->tax += $tax;
        }
        if (!isset($this->vat_castle[$codiva])) {
          $this->vat_castle[$codiva]['taxable'] = 0;
          $this->vat_castle[$codiva]['tax'] = 0;
          $this->vat_castle[$codiva]['tipiva'] = $mov['tipiva'];
          $this->vat_castle[$codiva]['descri'] = $mov['desiva'];
          $this->vat_castle[$codiva]['periva'] = $mov['periva'];
        }
        $this->vat_castle[$codiva]['taxable'] += $taxable;
        $this->vat_castle[$codiva]['tax'] += $tax;
      }
      // FINE MOVIMENTI DI REGISTRO
      // INIZIO MOVIMENTI DI LIQUIDAZIONE
      if (!isset($this->vat_castle_liq[$codiva])){
        $this->vat_castle_liq[$codiva]['taxable'] = 0;
        $this->vat_castle_liq[$codiva]['tax'] = 0;
        $this->vat_castle_liq[$codiva]['tipiva'] = $mov['tipiva'];
        $this->vat_castle_liq[$codiva]['descri'] = $mov['desiva'];
        $this->vat_castle_liq[$codiva]['periva'] = $mov['periva'];
      }
      $mov['liq_val']='';
      if ($mov['dl']< $this->inidate){
        $mov['liq_val']='GIÀ LIQUIDATA';
        $mov['liq_class']='danger';
      } elseif ($mov['dl']>$this->enddate){
        $mov['liq_val']='NON LIQUIDATA';
        $mov['liq_class']='warning';
      } else {
        $mov['liq_val']=gaz_format_number($tax);
        $this->taxable_liq += $taxable;
        $this->tax_liq += $tax;
              $this->vat_castle_liq[$codiva]['taxable'] += $taxable;
              $this->vat_castle_liq[$codiva]['tax'] += $tax;
      }
      // FINE MOVIMENTI DI LIQUIDAZIONE
      // FINE TIPIZZAZIONE REGISTRO - LIQUIDAZIONE            // aggiungo ai totali generali
      //se e' una semplificata recupero anche i righi contabili
      $this->acc_rows = array();
      if (!isset($this->acc_operation_type[$op_typ])) {
          $this->acc_operation_type[$op_typ] = $taxable;
      } else {
          $this->acc_operation_type[$op_typ] += $taxable;
      }
      if ($this->semplificata == 1 && $ctrl_idtes <> $id_tes && $reg_yes==true) { // solo se il movimento fa parte del registro recupero i dati di costo
        $rs_accounting_rows = gaz_dbi_dyn_query("*", $gTables['rigmoc'] . " LEFT JOIN " . $gTables['clfoco'] . " ON (" . $gTables['rigmoc'] . ".codcon = " . $gTables['clfoco'] . ".codice)", "id_tes = '" . $mov['id_tes'] . "'
           AND codcon NOT LIKE '" . $this->azienda['mascli'] . "%'
           AND codcon NOT LIKE '" . $this->azienda['masfor'] . "%'
           AND codcon NOT LIKE '" . substr($this->azienda['cassa_'], 0, 3) . "%'
           AND codcon NOT LIKE '" . $this->azienda['masban'] . "%'
           AND codcon <> " . $this->azienda['ivaacq'] . "
           AND codcon <> " . $this->azienda['ivaven'] . "
           AND codcon <> " . $this->azienda['ivacor'], "id_rig asc");
        while ($acc_rows = gaz_dbi_fetch_array($rs_accounting_rows)) {
          $codcon = $acc_rows['codcon'];
          if (!isset($this->acc_castle[$codcon])) {
              $this->acc_castle[$codcon] = array('value' => 0, 'descri' => '');
              $this->acc_castle[$codcon]['descri'] = $acc_rows['descri'];
          }
          if (($acc_rows['darave'] == 'A' && $mov['regiva'] == 6) || ($acc_rows['darave'] == 'D' && $mov['regiva'] <= 5)) {
              $this->acc_castle[$codcon]['value'] -= $acc_rows['import'];
          } else {
              $this->acc_castle[$codcon]['value'] += $acc_rows['import'];
          }
          $this->acc_rows[$codcon] = array('value' => $acc_rows['import'], 'descri' => $acc_rows['descri']);
        }
        $this->rows[] = $mov + array('acc_rows' => $this->acc_rows);
      } else {
        $this->rows[] = $mov;
      }
      $ctrl_idtes = $id_tes;
    }
  }

}

function calcPeriod($dateIni, $dateFin, $period) {
  if ($period == 'M') { // mensile
    $period_num = 1 + substr($dateFin, 2, 2) - substr($dateIni, 2, 2) + (substr($dateFin, 4, 4) - substr($dateIni, 4, 4)) * 12;
    for ($i = 1; $i <= $period_num; $i++) {
      $rs[$i]['m'] = 'M';
      if ($period_num == 1) { // il solo
        $rs[$i]['i'] = date("dmY", mktime(0, 0, 0, substr($dateIni, 2, 2), substr($dateIni, 0, 2), substr($dateIni, 4, 4)));
        $rs[$i]['f'] = date("dmY", mktime(0, 0, 0, substr($dateFin, 2, 2), substr($dateFin, 0, 2), substr($dateFin, 4, 4)));
      } elseif ($i == 1) { // il primo
        $rs[$i]['i'] = date("dmY", mktime(0, 0, 0, substr($dateIni, 2, 2), substr($dateIni, 0, 2), substr($dateIni, 4, 4)));
        $rs[$i]['f'] = date("dmY", mktime(0, 0, 0, substr($dateIni, 2, 2) + 1, 0, substr($dateIni, 4, 4)));
      } elseif ($i == $period_num) { // l'ultimo
        $rs[$i]['i'] = date("dmY", mktime(0, 0, 0, substr($dateIni, 2, 2) + $i - 1, 1, substr($dateIni, 4, 4)));
        $rs[$i]['f'] = date("dmY", mktime(0, 0, 0, substr($dateFin, 2, 2), substr($dateFin, 0, 2), substr($dateFin, 4, 4)));
      } else { // gli intermedi
        $rs[$i]['i'] = date("dmY", mktime(0, 0, 0, substr($dateIni, 2, 2) + $i - 1, 1, substr($dateIni, 4, 4)));
        $rs[$i]['f'] = date("dmY", mktime(0, 0, 0, substr($dateIni, 2, 2) + $i, 0, substr($dateIni, 4, 4)));
      }
    }
  } elseif ($period == 'no') { // tutto
    $period_num = 1;
    $rs[1]['m'] = 'N';
    $rs[1]['i'] = $dateIni;
    $rs[1]['f'] = $dateFin;
  } else { // trimestrale
    if (substr($dateIni, 2, 2) >= 1 and substr($dateIni, 2, 2) < 4) {
      $tri_ini = 1;
    } elseif (substr($dateIni, 2, 2) >= 4 and substr($dateIni, 2, 2) < 7) {
      $tri_ini = 2;
    } elseif (substr($dateIni, 2, 2) >= 7 and substr($dateIni, 2, 2) < 10) {
      $tri_ini = 3;
    } else {
      $tri_ini = 4;
    }
    if (substr($dateFin, 2, 2) >= 1 and substr($dateFin, 2, 2) < 4) {
      $tri_fin = 1;
    } elseif (substr($dateFin, 2, 2) >= 4 and substr($dateFin, 2, 2) < 7) {
      $tri_fin = 2;
    } elseif (substr($dateFin, 2, 2) >= 7 and substr($dateFin, 2, 2) < 10) {
      $tri_fin = 3;
    } else {
      $tri_fin = 4;
    }
    $period_num = 1 + $tri_fin - $tri_ini + (substr($dateFin, 4, 4) - substr($dateIni, 4, 4)) * 4;
    for ($i = 1; $i <= $period_num; $i++) {
        $rs[$i]['m'] = 'T';
        if ($period_num == 1) { // il solo
            $rs[$i]['i'] = date("dmY", mktime(0, 0, 0, substr($dateIni, 2, 2), substr($dateIni, 0, 2), substr($dateIni, 4, 4)));
            $rs[$i]['f'] = date("dmY", mktime(0, 0, 0, substr($dateFin, 2, 2), substr($dateFin, 0, 2), substr($dateFin, 4, 4)));
        } elseif ($i == 1) { // il primo
            $rs[$i]['i'] = date("dmY", mktime(0, 0, 0, substr($dateIni, 2, 2), substr($dateIni, 0, 2), substr($dateIni, 4, 4)));
            $rs[$i]['f'] = date("dmY", mktime(0, 0, 0, $tri_ini * 3 + 1, 0, substr($dateIni, 4, 4)));
        } elseif ($i == $period_num) { // l'ultimo
            $rs[$i]['i'] = date("dmY", mktime(0, 0, 0, $tri_ini * 3 + ($i - 2) * 3 + 1, 1, substr($dateIni, 4, 4)));
            $rs[$i]['f'] = date("dmY", mktime(0, 0, 0, substr($dateFin, 2, 2), substr($dateFin, 0, 2), substr($dateFin, 4, 4)));
        } else { // gli intermedi
            $rs[$i]['i'] = date("dmY", mktime(0, 0, 0, $tri_ini * 3 + ($i - 2) * 3 + 1, 1, substr($dateIni, 4, 4)));
            $rs[$i]['f'] = date("dmY", mktime(0, 0, 0, $tri_ini * 3 + ($i - 2) * 3 + 4, 0, substr($dateIni, 4, 4)));
        }
    }
  }
  return $rs;
}

// -------------  INIZIO STAMPA  -------------------------------

$pdf = new vatBook('P', 'mm', 'A4', true, 'UTF-8', false, true);
$ini_page = intval($_GET['pi']);
if ($_GET['cv'] == 'cover') {
    $ini_page--;
}

$url_get = $_GET;
$period = $admin_aziend['ivam_t'];
if ($url_get['jp'] != 'jump') {
    $period = 'no';
}
$period_chopped = calcPeriod($url_get['ri'], $url_get['rf'], $period);
$p_max = count($period_chopped);
$gazTimeFormatter->setPattern('MMMM yyyy');
for ($i = 1; $i <= $p_max; $i++) {
  $pdf->setData($period_chopped[$i] + $url_get, $gTables, $admin_aziend);
  if ($pdf->vatsect==$pdf->rc_sect){
    $desreg = $pdf->desregrc.' sez.';
    $pdf->script_transl['title'][$pdf->typbook] = ucfirst($desreg). ' ';
  } else {
    $desreg = $pdf->script_transl['vat_section'];
  }
  if ($i == 1) {
    $n_page = array('ini_page' => $ini_page, 'year' => ucwords($desreg) . $pdf->vatsect . ' ' . $pdf->script_transl['page'] . ' ' . substr($url_get['ri'], 4, 4));
  } else {
    $n_page = false;
  }
  $descri_period = $pdf->script_transl['title'][$pdf->typbook] . ucwords($gazTimeFormatter->format(new DateTime('01-'.substr($period_chopped[$i]['i'], 2, 2).'-'.substr($period_chopped[$i]['i'], 4, 4))));
  if (substr($period_chopped[$i]['f'], 2, 6) != substr($period_chopped[$i]['i'], 2, 6)) {
    $descri_period .= ' - ' . ucwords($gazTimeFormatter->format(new DateTime('01-'.substr($period_chopped[$i]['f'], 2, 2).'-'.substr($period_chopped[$i]['f'], 4, 4))));
  }
  $pdf->setVars($admin_aziend, $descri_period, 0, $n_page);
  $pdf->getRows($gTables);
  if ($_GET['cv'] == 'cover') {
    $pdf->setCover($pdf->script_transl['cover_descri'][$pdf->typbook] . "\n" . substr($url_get['ri'], 4, 4) . "\n" . $desreg . $pdf->vatsect);
    $pdf->AddPage();
    $_GET['cv'] = '';
  }
  // creo la matrice dei valori per la stampa della barra delle descrizioni delle colonne
  $topCarry = array(array('lenght' => 114, 'name' => $pdf->script_transl['top_carry'], 'frame' => 'B', 'fill' => 0, 'font' => 8),
      array('lenght' => 17, 'name' => '', 'frame' => 1, 'fill' => 0),
      array('lenght' => 25, 'name' => '', 'frame' => 1, 'fill' => 0),
      array('lenght' => 17, 'name' => '', 'frame' => 1, 'fill' => 0),
      array('lenght' => 17, 'name' => '', 'frame' => 1, 'fill' => 0));
  $botCarry = array(array('lenght' => 114, 'name' => $pdf->script_transl['bot_carry'], 'frame' => 'T', 'fill' => 0, 'font' => 8),
      array('lenght' => 17, 'name' => '', 'frame' => 1, 'fill' => 1),
      array('lenght' => 25, 'name' => '', 'frame' => 1, 'fill' => 1),
      array('lenght' => 17, 'name' => '', 'frame' => 1, 'fill' => 1),
      array('lenght' => 17, 'name' => '', 'frame' => 1, 'fill' => 1));
  $top = array(array('lenght' => 10, 'name' => $pdf->script_transl['top']['prot'], 'frame' => 1, 'fill' => 1, 'font' => 7),
      array('lenght' => 18, 'name' => $pdf->script_transl['top']['dreg'], 'frame' => 1, 'fill' => 1),
      array('lenght' => 28, 'name' => $pdf->script_transl['top']['desc'], 'frame' => 1, 'fill' => 1),
      array('lenght' => 22, 'name' => $pdf->script_transl['top']['ddoc'], 'frame' => 1, 'fill' => 1),
      array('lenght' => 36, 'name' => $pdf->script_transl['partner_descri'][$pdf->typbook], 'frame' => 1, 'fill' => 1),
      array('lenght' => 17, 'name' => $pdf->script_transl['top']['txbl'], 'frame' => 1, 'fill' => 1),
      array('lenght' => 10, 'name' => $pdf->script_transl['top']['perc'], 'frame' => 1, 'fill' => 1),
      array('lenght' => 15, 'name' => $pdf->script_transl['top']['tax'], 'frame' => 1, 'fill' => 1),
      array('lenght' => 17, 'name' => $pdf->script_transl['top']['tot'], 'frame' => 1, 'fill' => 1),
      array('lenght' => 17, 'name' => $pdf->script_transl['top']['liq'], 'frame' => 1, 'fill' => 1));
  $pdf->setTopBar($top);
  $pdf->AddPage();
  $pdf->setFooterMargin(20.5);
  $pdf->setTopMargin(44.5);
  $pdf->SetFont('helvetica', '', 8);
  $maxY = $pdf->GetY();
  $ctrl = 0;
  $totimponi = 0.00;
  $totimpost = 0.00;
  $totindetr = 0.00;
  $totimponi_liq = 0.00;
  $totimpost_liq = 0.00;
  $totindetr_liq = 0.00;
  foreach ($pdf->rows as $k => $v) {
    $pdf->SetFillColor(230,230,230);
    $pdf->SetTextColor(0);
    switch ($v['operat']) {
      case "1":
        $imponi = $v['imponi'];
        $impost = $v['impost'];
        break;
      case "2":
        $imponi = -$v['imponi'];
        $impost = -$v['impost'];
        $pdf->SetTextColor(155, 0, 0);
        break;
      default:
        $imponi = 0;
        $impost = 0;
        break;
    }
    if($v['dr']<$pdf->inidate){ // fattura pregressa, precedente al periodo selezionato ma che concorre alla liquidazione
    }elseif($v['dr']>$pdf->enddate){// fattura successiva al periodo selezionato ma che concorre alla liquidazione es. acquisto egistrato nei 15gg successivi
    }else{
      $totimponi += $imponi;
      if ($v['tipiva'] != "D" && $v['tipiva'] != "T") {  // indetraibile o split payment
        $totimpost += $impost;
      }
    }
    if ($v['dl']<$pdf->inidate){
    } elseif ($v['dl']>$pdf->enddate){
    } else {
      $totimponi_liq += $imponi;
      $totimpost_liq += $impost;
    }
    if ($ctrl != $v['id_tes']) { // primo rigo iva del movimento contabile
      if ($maxY > 250) {
        $pdf->Ln();
        $pdf->Cell(190,1,'','T');
        $pdf->AddPage();
        $maxY = $pdf->GetY();
      }
      $pdf->SetY($maxY);
      $pdf->Cell(10, 4, $v['protoc'], 'LTB', 0, 'C');
      $pdf->Cell(18, 4, gaz_format_date($v['datreg']), 'LTB', 0, 'C');
      $pdf->Cell(32, 4, $v['numdoc'], 'LTB', 0, 'C');
      $pdf->Cell(18, 4, gaz_format_date($v['datdoc']), 'LTB', 0, 'R');
      $pdf->Cell(95, 4, $v['ragsoc'], 'LTR', 0, 'L', 1, '', 1);
      $pdf->Cell(17, 4, substr(gaz_format_date($v['datliq']),3), 1, 1, 'R', 0, '', 1);
      $topCarry[1]['name'] = gaz_format_number($totimponi) . ' ';
      $botCarry[1]['name'] = gaz_format_number($totimponi) . ' ';
      $topCarry[2]['name'] = gaz_format_number($totimpost) . ' ';
      $botCarry[2]['name'] = gaz_format_number($totimpost) . ' ';
      $topCarry[3]['name'] = gaz_format_number($totimponi + $totimpost) . ' ';
      $botCarry[3]['name'] = gaz_format_number($totimponi + $totimpost) . ' ';
      $topCarry[4]['name'] = gaz_format_number($totimponi_liq + $totimpost_liq) . ' ';
      $botCarry[4]['name'] = gaz_format_number($totimponi_liq + $totimpost_liq) . ' ';
      $pdf->setTopCarryBar($topCarry);
      $pdf->setBotCarryBar($botCarry);
      $pdf->Cell(56, 4, $v['descri'], 'LTB', 0, 'R', 0, '', 1);
      $pdf->Cell(12, 4, $v['operation_type'], 'LTB', 0, 'C', 0, '', 1);
      $pdf->Cell(10, 4, 'cod ' . $v['codiva'], 1, 0, 'C');
      $pdf->Cell(36, 4, $v['desiva'], 1, 0, 'L', 0, '', 1);
      $pdf->Cell(17, 4, gaz_format_number($imponi), 1, 0, 'R', 0, '', 1);
      $pdf->Cell(10, 4, floatval($v['periva']) . '%', 1, 0, 'C', 0, '', 1);
      $pdf->Cell(15, 4, gaz_format_number($impost), 1, 0, 'R', 0, '', 1);
      $pdf->Cell(17, 4, gaz_format_number($impost + $imponi), 1, 0, 'R', 0, '', 1);
      if($v['liq_class']=='warning') {
        $pdf->SetFillColor(255, 255, 102);
        $pdf->Cell(17, 4, $v['liq_val'], 1, 1, 'R', 1, '', 1);
        $pdf->SetFillColor(hexdec(substr($pdf->colore, 0, 2)), hexdec(substr($pdf->colore, 2, 2)), hexdec(substr($pdf->colore, 4, 2)));
      } elseif($v['liq_class']=='danger') {
        $pdf->SetFillColor(255, 102, 102);
        $pdf->Cell(17, 4, $v['liq_val'], 1, 1, 'R', 1, '', 1);
        $pdf->SetFillColor(hexdec(substr($pdf->colore, 0, 2)), hexdec(substr($pdf->colore, 2, 2)), hexdec(substr($pdf->colore, 4, 2)));
      } else {
        $pdf->Cell(17, 4, $v['liq_val'], 1, 1, 'R',0, '', 1);
      }
      $topY = $pdf->GetY();
      if (isset($v['acc_rows'])) {
        foreach ($v['acc_rows']as $k1 => $v1) {
          $pdf->SetFont('helvetica', '', 7);
          $pdf->Cell(10, 4, $k1, 'L', 0, '', 0, '', 1);
          $pdf->Cell(42, 4, $v1['descri'], '', 0, '', 0, '', 1);
          $pdf->Cell(1, 4, $admin_aziend['symbol']);
          $pdf->Cell(15, 4, gaz_format_number($v1['value']), 'R', 1, 'R');
          $pdf->SetFont('helvetica', '', 8);
        }
      }
      $maxY = $pdf->GetY();
    } else { // righi iva successivi al primo
      $pdf->SetY($topY);
      $pdf->Cell(68, 4, '', 'L');
      $pdf->Cell(10, 4, 'cod ' . $v['codiva'], 1, 0, 'C');
      $pdf->Cell(36, 4, $v['desiva'], 1, 0, 'L', 0, '', 1, 0, '', 1);
      $pdf->Cell(17, 4, gaz_format_number($imponi), 1, 0, 'R', 0, '', 1);
      $pdf->Cell(10, 4, floatval($v['periva']) . '%', 1, 0, 'C', 0, '', 1);
      $pdf->Cell(15, 4, gaz_format_number($impost), 1, 0, 'R', 0, '', 1);
      $pdf->Cell(17, 4, gaz_format_number($impost + $imponi), 1, 0, 'R', 0, '', 1);
      if($v['liq_class']=='warning') {
        $pdf->SetFillColor(255, 255, 102);
        $pdf->Cell(17, 4, $v['liq_val'], 1, 1, 'R', 1, '', 1);
        $pdf->SetFillColor(hexdec(substr($pdf->colore, 0, 2)), hexdec(substr($pdf->colore, 2, 2)), hexdec(substr($pdf->colore, 4, 2)));
      } elseif($v['liq_class']=='danger') {
        $pdf->SetFillColor(255, 102, 102);
        $pdf->Cell(17, 4, $v['liq_val'], 1, 1, 'R', 1, '', 1);
        $pdf->SetFillColor(hexdec(substr($pdf->colore, 0, 2)), hexdec(substr($pdf->colore, 2, 2)), hexdec(substr($pdf->colore, 4, 2)));
      } else {
        $pdf->Cell(17, 4, $v['liq_val'], 1, 1, 'R',0, '', 1);
      }
      $topCarry[1]['name'] = gaz_format_number($totimponi) . ' ';
      $botCarry[1]['name'] = gaz_format_number($totimponi) . ' ';
      $topCarry[2]['name'] = gaz_format_number($totimpost) . ' ';
      $botCarry[2]['name'] = gaz_format_number($totimpost) . ' ';
      $topCarry[3]['name'] = gaz_format_number($totimponi + $totimpost) . ' ';
      $botCarry[3]['name'] = gaz_format_number($totimponi + $totimpost) . ' ';
      $topCarry[4]['name'] = gaz_format_number($totimponi_liq + $totimpost_liq) . ' ';
      $botCarry[4]['name'] = gaz_format_number($totimponi_liq + $totimpost_liq) . ' ';
      $pdf->setTopCarryBar($topCarry);
      $pdf->setBotCarryBar($botCarry);
      if ($maxY < $pdf->GetY()) {
        $maxY = $pdf->GetY();
      }
      $topY = $pdf->GetY();
    }
    $ctrl = $v['id_tes'];
  }
  $pdf->SetTextColor(0);
  $pdf->setTopCarryBar('');
  $pdf->setBotCarryBar('');
  $pdf->Cell(190, 1, '', 'T');
  if ($pdf->typbook == 6) { // se è un acquisto metto la legenda (provvisorio)
    // inizio stampa legenda tipologie di operazioni
    $xml = simplexml_load_file('../../library/include/operation_type.xml');
    $pdf->SetFont('helvetica', 'B', 6);
    $pdf->Ln(2);
    $pdf->Cell(108, 3, $pdf->script_transl['operation_type_title'], 1, 1, 'C', 1);
    $pdf->Cell(15, 3, $pdf->script_transl['operation_type_code'], 1, 0, 'C');
    $pdf->Cell(78, 3, $pdf->script_transl['operation_type_name'], 1, 0);
    $pdf->Cell(15, 3, ucfirst($pdf->script_transl['amount']), 1, 1, 'C');
    $pdf->SetFont('helvetica', '', 7);
    $pdf->Cell(15, 3, ' =', 'LTB', 0, 'R');
    $pdf->Cell(75, 3, $pdf->script_transl['operation_type_other'], 'TB');
    $pdf->Cell(3, 3, $admin_aziend['symbol'], 'TB');
    $pdf->Cell(15, 3, gaz_format_number($pdf->acc_operation_type[0]), 'RTB', 1, 'R');
    foreach ($xml->record as $v) {
      $n = (array) $v->field;
      if (isset($pdf->acc_operation_type[$n[0]])) {
        $pdf->Cell(15, 3, $v->field[0] . ' =', 'LTB', 0, 'R');
        $pdf->Cell(75, 3, $v->field[1], 'TB');
        $pdf->Cell(3, 3, $admin_aziend['symbol'], 'TB');
        $pdf->Cell(15, 3, gaz_format_number($pdf->acc_operation_type[$n[0]]), 'RTB', 1, 'R');
      }
    }
    // fine stampa legenda tipi operazioni
  }
  if ($pdf->typbook < 9) { // se non è una lista di versamenti
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Ln(6);
    $pdf->Cell(190, 6, $pdf->script_transl['vat_castle_title'], 1, 1, 'C', 1);
    $pdf->Cell(20, 5, 'cod.', 1, 0, 'C', 1);
    $pdf->Cell(60, 5, $pdf->script_transl['descri'], 1, 0, 'C', 1);
    $pdf->Cell(30, 5, $pdf->script_transl['taxable'], 1, 0, 'R', 1);
    $pdf->Cell(20, 5, '%', 1, 0, 'C', 1);
    $pdf->Cell(30, 5, $pdf->script_transl['tax'], 1, 0, 'R', 1);
    $pdf->Cell(30, 5, $pdf->script_transl['tot'], 1, 1, 'R', 1);
    $pdf->SetFont('helvetica', '', 10);
    foreach ($pdf->vat_castle as $k => $v) {
      $pdf->Cell(20, 5, $k, 1, 0, 'C');
      $pdf->Cell(60, 5, $v['descri'], 1, 0, 'C', 0, '', 1);
      $pdf->Cell(30, 5, gaz_format_number($v['taxable']), 1, 0, 'R');
      $pdf->Cell(20, 5, $v['periva'] . '%', 1, 0, 'C');
      $align = 'R';
      if ($v['tipiva'] == 'D' || $v['tipiva'] == 'T') {
        // metto in evidenza che è indetraibile allineandolo a sinistra
        $align = 'L';
      }
      $pdf->Cell(30, 5, gaz_format_number($v['tax']), 1, 0, $align);
      $pdf->Cell(30, 5, gaz_format_number($v['taxable'] + $v['tax']), 1, 1, 'R');
    }
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(80, 5, $pdf->script_transl['tot_descri'], 1, 0, 'C', 1);
    $pdf->Cell(30, 5, gaz_format_number($pdf->taxable), 1, 0, 'R', 1);
    $pdf->Cell(20, 5);
    $pdf->Cell(30, 5, gaz_format_number($pdf->tax), 1, 0, 'R', 1);
    $pdf->Cell(30, 5, gaz_format_number($pdf->taxable + $pdf->tax), 1, 1, 'R', 1);
    if (count($pdf->acc_castle) > 0) {
      $pdf->Ln(6);
      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->Cell(35);
      $pdf->Cell(120, 6, $pdf->script_transl['acc_castle_title'], 1, 2, 'C', 1);
      $pdf->Cell(20, 5, 'cod.', 1, 0, 'C', 1);
      $pdf->Cell(75, 5, $pdf->script_transl['descri'], 1, 0, 'C', 1, '', 1);
      $pdf->Cell(25, 5, $pdf->script_transl['amount'], 1, 1, 'R', 1);
      $pdf->SetFont('helvetica', '', 8);
      foreach ($pdf->acc_castle as $k => $v) {
        $pdf->Cell(35);
        $pdf->Cell(20, 5, $k, 1, 0, 'C');
        $pdf->Cell(75, 5, $v['descri'], 1, 0, 'L', 0, '', 1);
        $pdf->Cell(25, 5, gaz_format_number($v['value']), 1, 1, 'R');
      }
    }
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Ln(6);
    $pdf->Cell(190, 6, $pdf->script_transl['vat_castle_liq_title'], 1, 1, 'C', 1);
    $pdf->Cell(20, 5, 'cod.', 1, 0, 'C', 1);
    $pdf->Cell(60, 5, $pdf->script_transl['descri'], 1, 0, 'C', 1);
    $pdf->Cell(30, 5, $pdf->script_transl['taxable'], 1, 0, 'R', 1);
    $pdf->Cell(20, 5, '%', 1, 0, 'C', 1);
    $pdf->Cell(30, 5, $pdf->script_transl['tax'], 1, 0, 'R', 1);
    $pdf->Cell(30, 5, $pdf->script_transl['tot'], 1, 1, 'R', 1);
    $pdf->SetFont('helvetica', '', 10);
    foreach ($pdf->vat_castle_liq as $k => $v) {
        $pdf->Cell(20, 5, $k, 1, 0, 'C');
        $pdf->Cell(60, 5, $v['descri'], 1, 0, 'C', 0, '', 1);
        $pdf->Cell(30, 5, gaz_format_number($v['taxable']), 1, 0, 'R');
        $pdf->Cell(20, 5, $v['periva'] . '%', 1, 0, 'C');
        $align = 'R';
        if ($v['tipiva'] == 'D' || $v['tipiva'] == 'T') {
            // metto in evidenza che è indetraibile allineandolo a sinistra
            $align = 'L';
        }
        $pdf->Cell(30, 5, gaz_format_number($v['tax']), 1, 0, $align);
        $pdf->Cell(30, 5, gaz_format_number($v['taxable'] + $v['tax']), 1, 1, 'R');
    }
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(80, 5, $pdf->script_transl['tot_liqui'], 1, 0, 'C', 1);
    $pdf->Cell(30, 5, gaz_format_number($pdf->taxable_liq), 1, 0, 'R', 1);
    $pdf->Cell(20, 5);
    $pdf->Cell(30, 5, gaz_format_number($pdf->tax_liq), 1, 0, 'R', 1);
    $pdf->Cell(30, 5, gaz_format_number($pdf->taxable_liq + $pdf->tax_liq), 1, 1, 'R', 1);
  } else { // è una lista dei versamenti
    $pdf->Ln(4);
    $pdf->Cell(156, 4,' T O T A L E   D E I    V E R S A M E N T I   € ', 'LTB', 0, 'R', 0, '', 1);
    $pdf->Cell(17, 4, gaz_format_number($pdf->tax_liq), 1, 1, 'R', 1, '', 1);
  }
}
if ($_GET['sd'] == 'sta_def') {
    switch ($pdf->typbook) {
        case 2:
            $azireg = 'upgve' . intval($_GET['vs']);
			$azilastm = 'umeve' . intval($_GET['vs']);
            break;
        case 4:
            $azireg = 'upgco' . intval($_GET['vs']);
			$azilastm = 'umeco' . intval($_GET['vs']);
            break;
        case 6:
            $azireg = 'upgac' . intval($_GET['vs']);
			$azilastm = 'umeac' . intval($_GET['vs']);
            break;
    }
    gaz_dbi_put_row($gTables['company_data'], 'var', $azireg, 'data', $pdf->getGroupPageNo() + $ini_page - 1);
	gaz_dbi_put_row($gTables['company_data'], 'var', $azilastm, 'data', $_GET['lm']);
}
$pdf->Output($descri_period . '.pdf');
?>
