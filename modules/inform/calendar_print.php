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
use tecnickcom\tcpdf\tcpdf;
use setasign\Fpdi\Tcpdf\Fpdi;
require("../../library/include/datlib.inc.php");
$gTables['calendar'] = $table_prefix . "_calendar";
$admin_aziend=checkAdmin();
$yobj=isset($_GET['year'])?intval($_GET['year']):date('Y')+1;
if (isset($_GET['clfoco']) && intval($_GET['clfoco'])>100000000){
  $anagrafica = new Anagrafica();
  $clfoco=$anagrafica->getPartner(intval($_GET['clfoco']));
} else {
  $clfoco=false;
}
$yi=($yobj-1).'-12-01';
$yf=($yobj+1).'-02-01';
//var_dump($yi,$yobj,$yf);
$begin = new DateTime($yi);
$end = new DateTime($yf);
$easter_date=date('md',easter_date($yobj));
$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($begin, $interval, $end);
$start_easter=false;
$count_easter=0;

class calPdf extends Fpdi {

  private $azienda = [];
  private $clfoco = false;
  private $month = '';
  private $year = '';
  private $logox = 50;
  private $logoy = 0;
  private $logoyoffset = 0;
  private $imglink = '';

  public function setGlobalData($aziend,$clfoco) {
    $this->azienda = $aziend;
    $this->clfoco = $clfoco;
    $im = imagecreatefromstring ($this->azienda['image']);
    $ratio = round(imagesx($im)/imagesy($im),2);
    $this->logox=48;$this->logoy=0;
    $iy = $this->logox/$ratio;
    $this->logoyoffset=round((30-$iy)/2,1);
    if ($ratio<1.6) {
      $this->logox=0;
      $this->logoy=30;
      $this->logoyoffset=0;
    }
    $this->imglink = !empty($this->azienda['web_url']) ? $this->azienda['web_url'] : 'https://gazie.sourceforge.io/';
  }

  public function Header() {
    $this->SetFillColor(hexdec(substr($this->azienda['colore'], 0, 2)), hexdec(substr($this->azienda['colore'], 2, 2)), hexdec(substr($this->azienda['colore'], 4, 2)));
    $this->SetDrawColor(hexdec(substr($this->azienda['colore'], 0, 2)), hexdec(substr($this->azienda['colore'], 2, 2)), hexdec(substr($this->azienda['colore'], 4, 2)));
    $this->Circle(105,8,2,0,360,'DF');
    $this->SetFont('helvetica', '', 10);
    $this->SetFont('times','B',34);
    $this->SetXY(48,15);
    $this->Cell(116,8,strtoupper($this->month),0,2,'C',false,'',0,false,'T','B');
    $this->SetXY(48,28);
    $this->SetFont('times','B',22);
    $this->Cell(116,6,$this->year,0,2,'C');
    $this->SetFont('helvetica', 'B', 10);
    $this->SetLineStyle(['width'=>3]);
    $this->RoundedRect(2,2,206,36,2);
    $this->SetXY(130,4);
    $this->Cell(75,6,$this->azienda['ragso1'].' '.$this->azienda['ragso2'],0,0,'R',0,'',1);
    $this->Image('@'.$this->azienda['image'],5,5+$this->logoyoffset,$this->logox,$this->logoy,'',$this->imglink);
		$this->ImageSVG('./img/calendar_withlove.svg',170,10,0,22,$this->imglink);
    //var_dump($this->clfoco);
    if ($this->clfoco) {
      $this->SetXY(120,30);
      $this->SetFont('helvetica', 'B', 12);
      $this->SetTextColor(255,0,0);
      $this->Cell(85,6,'to '.$this->clfoco['ragso1'].' '.$this->clfoco['ragso2'],0,0,'R',0,'',1);
      $this->SetTextColor(0);
    }
    $this->SetLineStyle(['width'=>3]);
    $this->RoundedRect(36,38,140,228,2);
  }

  public function Compose($acc,$gazTF) {
    $this->SetFillColor(hexdec(substr($this->azienda['colore'], 0, 2)), hexdec(substr($this->azienda['colore'], 2, 2)), hexdec(substr($this->azienda['colore'], 4, 2)));
    $gazTF->setPattern('MMMM');
    $first=true;
    foreach($acc as $k=>$v) {
      if($first){
        $this->year=substr($k,0,4)+1;
        $first=false;
      }
    }
    $ctrl_moon=false;
    for ($i=1; $i<=12; $i++) {
      $mo=str_pad($i,2,'0',STR_PAD_LEFT);
      $dtm = new DateTime($this->year.'-'.$mo.'-01');
      $this->month = $gazTF->format($dtm);
      $this->AddPage();
      // inizio composizione mese attuale
      $y=40;
      for ($j=1; $j<=32; $j++) {
        $y += 14;
        if($j==17){
          $x=106;
          $y=40;
          $this->SetXY(106,40);
        } elseif($j==1){
          $x=38;
          $y=40;
          $this->SetXY(38,40);
        } elseif($j>17){
          $x=106;
          $this->SetX(106);
        } else {
          $x=38;
          $this->SetX(38);
        }
        $moon=false;
        $week='';
        if (isset($acc[$this->year.$mo][$j])){
          $cday=$acc[$this->year.$mo][$j]['label'];
          $iday=explode(',',$acc[$this->year.$mo][$j]['info'])[0];
          if ($ctrl_moon <> $acc[$this->year.$mo][$j]['moonphase']) { // è cambiata la fase lunare
            $moon = './img/moon'.$acc[$this->year.$mo][$j]['moonphase'].'.svg';
          }
          $ctrl_moon=$acc[$this->year.$mo][$j]['moonphase'];
          if ($acc[$this->year.$mo][$j]['week']) { // è cambiata la settimana
            $week = $acc[$this->year.$mo][$j]['week'].'^ settimana';
          }
          if ($acc[$this->year.$mo][$j]['holiday']==1) {
            $this->SetTextColor(255,0,0);
          }
        } else {
          $cday='';
          $iday='';
        }
        $this->SetFont('helvetica','B',20);
        $this->Cell(6,10,'','TL');
        $this->Cell(32,10,$cday,'T',0,'',false,'',0,false,'T','T');
        $this->SetFont('helvetica','',8);
        $this->SetTextColor(0);
        $this->Cell(30,10,$week,'TR',1,'',false,'',0,false,'T','T');
        if ($moon){
      		$this->ImageSVG($moon,$x+55,$y+4,0,6);
        }
        $this->SetX($x);
        $this->Cell(68,4,$iday,'LBR',1,'',false,'',1,false,'T','T');
      }
      // fine composizione mese attuale

      // inizio composizione mese precedente
      $this->SetXY(4,40);
      $this->SetFont('helvetica','B',10);
      for ($j=1; $j<=31; $j++) {
        if ($i==1) { // è il mese precedente di gennaio quindi dicembre dell'anno prima
          $ym=($this->year-1).'12';
        } else {
          $ym=$this->year.str_pad(($i-1),2,'0',STR_PAD_LEFT);
        }
        if ($j==1) { // prima del giorno 1 stampo il nome del mese
          $dtm = new DateTime(substr($ym,0,4).'-'.substr($ym,4,2).'-01');
          $this->Cell(30,7,ucfirst($gazTF->format($dtm)).' '.substr($ym,0,4),1,1,'L',1,'',1);
        }
        $cday=isset($acc[$ym][$j])?$acc[$ym][$j]['label']:'';
        $iday=isset($acc[$ym][$j])?explode(',',$acc[$ym][$j]['info'])[0]:'';
        if (isset($acc[$ym][$j]) && $acc[$ym][$j]['holiday']==1) {
          $this->SetTextColor(255,0,0);
        } else {
          $this->SetTextColor(0);
        }
        $this->SetX(4);
        $this->Cell(30,7,$cday,1,1);
      }
      // fine composizione mese precedente

      // inizio composizione mese successivo
      $this->SetTextColor(0);
      $this->SetXY(178,40);
      $this->SetFont('helvetica','B',10);
      for ($j=1; $j<=31; $j++) {
        if ($i==12) { // è il mese precedente di gennaio quindi dicembre dell'anno prima
          $ym=($this->year+1).'01';
        } else {
          $ym=$this->year.str_pad(($i+1),2,'0',STR_PAD_LEFT);
        }
        if ($j==1) { // prima del giorno 1 stampo il nome del mese
          $dtm = new DateTime(substr($ym,0,4).'-'.substr($ym,4,2).'-01');
          $this->Cell(30,7,ucfirst($gazTF->format($dtm)).' '.substr($ym,0,4),1,1,'L',1,'',1);
        }
        $cday=isset($acc[$ym][$j])?$acc[$ym][$j]['label']:'';
        $iday=isset($acc[$ym][$j])?explode(',',$acc[$ym][$j]['info'])[0]:'';
        if (isset($acc[$ym][$j]) && $acc[$ym][$j]['holiday']==1) {
          $this->SetTextColor(255,0,0);
        } else {
          $this->SetTextColor(0);
        }
        $this->SetX(178);
        $this->Cell(30,7,$cday,1,1);
      }
      // fine composizione mese successivo
    }
  }

  public function Footer() {
    $this->SetDrawColor(hexdec(substr($this->azienda['colore'], 0, 2)), hexdec(substr($this->azienda['colore'], 2, 2)), hexdec(substr($this->azienda['colore'], 4, 2)));
    $this->SetLineStyle(['width'=>3]);
    $this->RoundedRect(2,266,206,29,2);
    $yofs = 0;
    if ($this->logoy>25){
      $logoy=25;
      $logox=0;
      // compenso la differenza sull'offset
      $yofs = round(30-$this->logoy,1);
    } else {
      $logox=$this->logox;
      $logoy=$this->logoy;
    }
    $yofs += ($this->logoyoffset>=0.1)?round($this->logoyoffset*0.5,1):0;
    $this->Image('@'.$this->azienda['image'],153,268+$yofs,$logox,$logoy,'',$this->imglink);
    $this->SetFont('helvetica','B',10);
    $this->SetXY(10,268);
    $this->Cell(160,5,$this->azienda['ragso1'].' '.$this->azienda['ragso2'],0,1);
    $this->SetFont('helvetica','',10);
    $this->SetX(10);
    $this->Cell(160,4,$this->azienda['indspe'],0,1,0,0,'',1);
    $this->SetX(10);
    $this->Cell(160,4,$this->azienda['capspe'].' '.$this->azienda['citspe'].' ('.$this->azienda['prospe'].')',0,1,0,0,'',1);
    $this->SetX(10);
    $this->Cell(105,4,'Tel: '.$this->azienda['telefo'].' - e-mail: '.$this->azienda['e_mail'],0,1,0,0,'',1);
    $this->SetFont('helvetica','B',14);
    $this->SetTextColor(100,100,100);
    $this->Cell(200,8,$this->imglink,0,0,'C',0,$this->imglink,1);
  }

  public function getMoonPhase($day,$month,$year) {
    if ($month < 4) { $year = $year - 1; $month = $month + 12; }
    $days_y = 365.25 * $year;
    $days_m = 30.42 * $month;
    $julian = $days_y + $days_m + $day - 694039.09;
    $julian = $julian / 29.53;
    $phase = intval($julian);
    $julian = $julian - $phase;
    $phase = round($julian * 8 + 0.5);
    if ($phase == 8) { $phase = 0; }
    return $phase;
  }

}

$pdf = new calPdf();
$pdf->setGlobalData($admin_aziend,$clfoco);
$pdf->SetMargins(0,5,3);
$pdf->SetFooterMargin(5);
$pdf->setAuthor($admin_aziend['user_lastname'] . ' ' . $admin_aziend['user_firstname']);
$pdf->SetCreator('GAzie ' .GAZIE_VERSION.' - '. $admin_aziend['ragso1'] . ' ' . $admin_aziend['ragso2']);
$pdf->setTitle('Calendario olandese dell\'anno '.$yobj);
$pdf->SetFont('helvetica','',7);
$ctrl_month=0;
$acc=[];
$gazTimeFormatter->setPattern('d eee');
$week=false;
foreach($period as $dt) {
  $m=$dt->format('n');
  $d=$dt->format('j');
  $kd=$dt->format('md');
  $ym=$dt->format('Ym');
  $mf=$pdf->getMoonPhase($d,$m,$yobj);
  $w=$dt->format('w');
  $dbdaycal=gaz_dbi_get_row($gTables['calendar'],'day',$d," AND month = ".$m);
  if ($kd == $easter_date) {
    $start_easter=true;
    $dbdaycal['info']='Pasqua';
  }
  if ($start_easter) {
    $count_easter++;
    $dbdaycal['holiday']=1;
    if ($count_easter>=2) {
      $start_easter=false;
      $dbdaycal['info']='Lunedì dell\'Angelo';
    }
  }
  $week=false;
  if ($w==0) {
    $dbdaycal['holiday']=1;
  } elseif ($w==1) {
    $week = intval($dt->format("W"));
  }
  $acc[$ym][$d]=['label'=>$gazTimeFormatter->format($dt),'holiday'=>$dbdaycal['holiday'],'info'=>$dbdaycal['info'],'week'=>$week,'moonphase'=>$mf];
  $ctrl_month=$m;
}
$pdf->Compose($acc,$gazTimeFormatter);
$pdf->Output('Calendario_olandese_'.$yobj.'_with_love_from_'.str_replace(' ','_',$admin_aziend['ragso1']).'_'.str_replace(' ','_',$admin_aziend['ragso2']).'.pdf');
?>
