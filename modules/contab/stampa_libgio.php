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
function get_render_time($prev) {
    list($usec, $sec) = explode(" ", microtime());
    $this_time = ((float) $usec + (float) $sec);
    return round($this_time - $prev, 8);
}

define('ROWS_PERPAGE',60);

require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
use setasign\Fpdi\Tcpdf\Fpdi;

if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','512M');
    gaz_set_time_limit (240);
}

if (!isset($_GET['regini']) or !isset($_GET['regfin'])) {
    header("Location: select_libgio.php");
    exit;
}
$gioini = substr($_GET['regini'],0,2);
$mesini = substr($_GET['regini'],3,2);
$annini = substr($_GET['regini'],6,4);
$utsini = mktime(0,0,0,$mesini,$gioini,$annini);
$giofin = substr($_GET['regfin'],0,2);
$mesfin = substr($_GET['regfin'],3,2);
$annfin = substr($_GET['regfin'],6,4);
$utsfin = mktime(0,0,0,$mesfin,$giofin,$annfin);
$datainizio = date("Y-m-d",$utsini);
$datafine = date("Y-m-d",$utsfin);
$admin_aziend['title'] = 'Libro Giornale dal '.date("d-m-Y",$utsini).' al '.date("d-m-Y",$utsfin);
$where = "`datreg` >= '".$datainizio." 00:00:00' AND `datreg` <= '". $datafine." 23:59:59'";
$rs_count = gaz_dbi_dyn_query('COUNT(*) AS nr',$gTables['tesmov'].' AS tm INNER JOIN '.$gTables['rigmoc'].' AS rm ON tm.id_tes=rm.id_tes', $where);
$pagetot=ceil(gaz_dbi_fetch_assoc($rs_count)['nr']/ROWS_PERPAGE); //numero di pagine necessarie
$field = "tm.id_tes, tm.descri, tm.datreg, DATE_FORMAT(tm.datreg,'%d-%m-%Y') AS dr, tm.seziva, tm.protoc, tm.numdoc, DATE_FORMAT(tm.datdoc,'%d-%m-%Y') AS dd,  rm.import*(rm.darave='A') AS avere, rm.import*(rm.darave='D') AS dare, rm.codcon, SUBSTR(cf.descri,1,35) AS cfdes";
$from = $gTables['tesmov'] . ' AS tm INNER JOIN '.$gTables['rigmoc'].' AS rm ON tm.id_tes=rm.id_tes INNER JOIN ' . $gTables['clfoco'] . ' AS cf ON rm.codcon=cf.codice ';
$orderby = "`datreg`,`id_tes`,`id_rig`";
$p = 1;
$r = 1;
$i = 1;
$result = gaz_dbi_dyn_query($field, $from, $where, $orderby);
$a[0] = $pagetot;
$rip = array();
$rid = 0.00;
$ria = 0.00;
$curr_month = 0;
$month_changed = false;
while ($mov = gaz_dbi_fetch_assoc($result)) {
	if ($r > ROWS_PERPAGE) {
		if (!$month_changed) {
			$rip[$p]['dare'] = $rid;
			$rip[$p]['avere'] = $ria;
			$r = 1;
			$p++;
		} else {
			$month_changed = false;
			$r = 2;
		}
	}

	if (isset($_GET['pdfamese'])) {
		$next_month = substr($mov['dr'],3,2);
		if ($curr_month != $next_month) {
			if ($curr_month != 0) {
				$month_changed = true;
				$rip[$p]['dare'] = $rid;
				$rip[$p]['avere'] = $ria;
				$p++;
				$rid+= $mov['dare'];
				$ria+= $mov['avere'];
				$mov['pagerow'] = 1;
				$a[1][$p][$i] = $mov;
				$r = ROWS_PERPAGE + 1;
				$i++;
				$curr_month = $next_month;
				continue;
			}
			$curr_month = $next_month;
		}
	}

	$rid+= $mov['dare'];
	$ria+= $mov['avere'];
	$mov['pagerow'] = $r;
	$a[1][$p][$i] = $mov;
	$r++;
	$i++;
}
$rip[$p]['dare']=$rid;
$rip[$p]['avere']=$ria;
$dl=false;
if (isset($_GET['pdfa']) || isset($_GET['pdfamese'])) {
  class GL_template extends  Fpdi {
    public $ad_az;
    public $intesta1;
    public $intesta2;
    public $intesta3;
    function SetVars($admin_aziend) {
      $this->ad_az = $admin_aziend;
      $this->intesta1 = $admin_aziend['ragso1'] . ' ' . $admin_aziend['ragso2'];
      $this->intesta2 = $admin_aziend['indspe'] . ' ' . sprintf("%05d", $admin_aziend['capspe']) . ' ' . $admin_aziend['citspe'] . ' (' . $admin_aziend['prospe'] . ')';
      $this->intesta3 = ' C.F.:' . $admin_aziend['codfis'] . ' P.I.:' . $admin_aziend['pariva'];
      $this->setFooterMargin(18);
      $this->setTopMargin(18);
      $this->SetFont('helvetica','',7);
    }
    function Header() {
      $this->Image('@'.$this->ad_az['image'],10,10,0,12);
      $this->SetFont('helvetica','B',8);
      $this->SetXY(40,10);
      $this->Cell(85,4,$this->intesta1,0,0,'C');
      $this->Cell(75,4,$this->ad_az['title'],0,1,'R');
      $this->SetFont('helvetica','',7);
      $this->SetX(40);
      $this->Cell(85,4,$this->intesta2,0,2,'C');
      $this->Cell(85,4,$this->intesta3,0,0,'C');
    }
    function Footer() {
      $this->SetFont('helvetica','',7);
      $this->MultiCell(190,4,$this->intesta1.' '.$this->intesta2.' '.$this->intesta3,0,'C');
    }
	}
	$pdf = new GL_template('P', 'mm', 'A4', true, 'UTF-8', false, true);
} else {
  $dl=true;
	require('../../library/tFPDF/mem_image.php');
    class GL_template extends PDF_MemImage {
      public $ad_az;
      public $intesta1;
      public $intesta2;
      public $intesta3;
      function SetVars($admin_aziend) {
        $this->ad_az = $admin_aziend;
        $this->intesta1 = $admin_aziend['ragso1'] . ' ' . $admin_aziend['ragso2'];
        $this->intesta2 = $admin_aziend['indspe'] . ' ' . sprintf("%05d", $admin_aziend['capspe']) . ' ' . $admin_aziend['citspe'] . ' (' . $admin_aziend['prospe'] . ')';
        $this->intesta3 = ' C.F.:' . $admin_aziend['codfis'] . ' P.I.:' . $admin_aziend['pariva'];
      }
		function Header() {
			$this->MemImage($this->ad_az['image'],10,10,0,12);
			$this->SetFont('helvetica','B',8);
    	$this->SetX(40);
      $this->Cell(85,4,$this->intesta1,0,0,'C');
	    $this->Cell(75,4,$this->ad_az['title'],0,1,'R');
			$this->SetFont('helvetica','',7);
    	$this->SetX(40);
      $this->Cell(85,4,$this->intesta2,0,2,'C');
	    $this->Cell(85,4,$this->intesta3,0,0,'C');
    	}
      function Footer() {
        $this->MultiCell(190,4,$this->intesta1.' '.$this->intesta2.' '.$this->intesta3,0,'C');
	    }
	}
	$pdf = new GL_template();
}
$pdf->SetVars($admin_aziend);
$pdf->SetFillColor(hexdec(substr($pdf->ad_az['colore'], 0, 2)), hexdec(substr($pdf->ad_az['colore'], 2, 2)), hexdec(substr($pdf->ad_az['colore'], 4, 2)));
$pdf->SetTitle($admin_aziend['title']);
$pdf->SetAuthor($pdf->intesta1.' usando GAzie versione '.GAZIE_VERSION);
$ci=0;
if ( count($a)>=2 ){
  foreach($a[1] as $k1=>$v1) {
    $pdf->AddPage();
    $pdf->SetXY(85,18);
    if (isset($_GET['pdfamese'])) {
      $pdf->Cell(75,4,'Pagina '.$k1,0,1,'R');
    } else {
      $pdf->Cell(75,4,'Pagina '.$k1.' di '.$a[0],0,1,'R');
    }
    $pdf->Cell(10,4,'Rigo',1,0,'R',1);
    $pdf->Cell(78,4,'Descrizione movimento',1,0,'L',1);
    $pdf->Cell(16,4,'Cod. conto',1,0,'C',1);
    $pdf->Cell(46,4,'Descrizione conto',1,0,'L',1);
    $pdf->Cell(20,4,'Dare',1,0,'R',1);
    $pdf->Cell(20,4,'Avere',1,1,'R',1);
    if ($k1>1){
      $pdf->Cell(104,4,'','B');
      $pdf->Cell(46,4,'--> da riporto ','B',0,'R');
      $pdf->Cell(20,4,number_format($rip[$k1-1]['dare'],2,',',''),1,0,'R',1);
      $pdf->Cell(20,4,number_format($rip[$k1-1]['avere'],2,',',''),1,1,'R',1);
    }

    foreach($v1 as $k2=>$v2){
      $v2['dare']=($v2['dare']>0)?number_format($v2['dare'],2,',',''):'';
      $v2['avere']=($v2['avere']>0)?number_format($v2['avere'],2,',',''):'';
      if ($ci!=$v2['id_tes']){
        $ds=wordwrap($v2['descri'],50,"XZX");
        $dx=explode("XZX",$ds);
        $dsx=$v2['dr'].' '.$dx[0];
        $b='T';
      } else {
        if(isset($dx[1])){$dsx=$dx[1];}else{$dsx='';}
        if(!empty($v2["numdoc"])&&$b=='T'){$dsx.= " n.".$v2['numdoc']."/".$v2['seziva']." del ".$v2['dd'];}
        $b='';
      }

          $pdf->Cell(10,4,$k2,1,0,'R');
          $pdf->Cell(78,4,$dsx,$b,0,'L',0,'',1);
          $pdf->Cell(16,4,$v2['codcon'],'LT',0,'C');
          $pdf->Cell(46,4,($dl?substr($v2['cfdes'],0,31):$v2['cfdes']),'LT',0,'L',0,'',1);
          $pdf->Cell(20,4,$v2['dare'],'LT',0,'R');
          $pdf->Cell(20,4,$v2['avere'],'LRT',1,'R');
        $ci=$v2['id_tes'];
    }

    if (isset($_GET['pdfamese'])) {
      if ($pdf->GetY()>5 && $pdf->GetY()<265) {
        $pdf->Cell(190,4,'//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////','LRT',1);
        while ($pdf->GetY() < 265) {
          $pdf->Cell(190,4,'//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////','LR',1);
        }
      }
    }

    if (isset($_GET['pdfamese'])) {
      $pdf->Cell(50,4,'Pagina '.$k1,'T');
    } else {
      $pdf->Cell(50,4,'Pagina '.$k1.' di '.$a[0],'T');
    }

    $desrip=($k1==$pagetot)?'':'a riporto --> ';
    $pdf->Cell(100,4,$desrip,'T',0,'R');
    $pdf->Cell(20,4,number_format($rip[$k1]['dare'],2,',',''),1,0,'R',1);
    $pdf->Cell(20,4,number_format($rip[$k1]['avere'],2,',',''),1,1,'R',1);
  }
} else {
  $pdf->AddPage();
  $pdf->SetFont('helvetica','',16);
  $pdf->SetXY(50,60);
  $pdf->Cell(100,4,'NESSUN MOVIMENTO CONTABILE NEI LIMITI SCELTI',0,0,'C');
}
$pdf->Output();
?>
