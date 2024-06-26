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

$admin_aziend=checkAdmin();




$logo=$admin_aziend['image'];
$testat = $_GET['id_tes'];
$tesbro = gaz_dbi_get_row($gTables['tesbro'],"id_tes",$testat);
//se non e' il tipo di documento stampabile da questo modulo ... va a casa
if ($tesbro['tipdoc'] <> 'VPA') {
    header("Location: report_salcon.php");
    exit;
    }
if ($tesbro['status'] == 'GENERATO' or $tesbro['status'] == 'MODIFICATO')
    gaz_dbi_put_row($gTables['tesbro'],"id_tes",$tesbro['id_tes'],"status",'STAMPATO');
$anagrafica = new Anagrafica();
$client = $anagrafica->getPartner($tesbro['clfoco']);
$pagame = gaz_dbi_get_row($gTables['pagame'],"codice",$tesbro['pagame']);
$vettor = gaz_dbi_get_row($gTables['vettor'],"codice",$tesbro['vettor']);
$day = substr($tesbro['datemi'],8,2);
$month = substr($tesbro['datemi'],5,2);
$year = substr($tesbro['datemi'],0,4);
$emissione =$tesbro['numdoc'].' del '.substr($tesbro['datemi'],8,2).'/'.substr($tesbro['datemi'],5,2).'/'.substr($tesbro['datemi'],0,4);
$cliente1=$client['ragso1'];
$cliente2=$client['ragso2'];
$cliente3=$client['indspe'];
$cliente4=$client['capspe'].' '.$client['citspe'].' ('.$client['prospe'].')';
$intesta1=$admin_aziend['ragso1'].' '.$admin_aziend['ragso2'];
$intesta2=$admin_aziend['indspe'].' '.$admin_aziend['capspe'].' '.$admin_aziend['citspe'].' ('.$admin_aziend['prospe'].')';
$intesta3='Tel.'.$admin_aziend['telefo'].' C.F. '.$admin_aziend['codfis'].' P.I. '.$admin_aziend['pariva'];
$intesta4=$admin_aziend['e_mail'];
$gazTimeFormatter->setPattern('MMMM');
$nomemese = $gazTimeFormatter->format(new DateTime("2000-".$month."-01"));
$dataluogo = $admin_aziend['citspe'].', lì '.$day.' '.$nomemese.' '.$year;

class PDF extends TCPDF
    {
function Header()
    {
    global $logo,$intesta1,$intesta2,$intesta3,$intesta4,$cliente1,$cliente2,$cliente3,$cliente4,$dataluogo;
    //Logo
    $posiz=$this->GetY();
    $this->Image('@'.$logo,8,$posiz,40,0,'Logo aziendale');
    $this->Cell(50,4);
    $this->SetFont('times','',16);
    $this->Cell(150,5,$intesta1,0,2,'L');
    $this->SetFont('helvetica','',10);
    $this->Cell(130,4,$intesta2,0,2,'L');
    $this->Cell(130,4,$intesta3,0,2,'L');
    $this->Cell(130,4,$intesta4,0,1,'L');
    $this->Ln(3);
    $this->Cell(50,10);
    $this->Cell(70,10,$dataluogo,0,0,'L');
    $this->Cell(70,10,'Pagina '.$this->PageNo().' di '.$this->getAliasNbPages(),0,1,'R');
    $this->SetFont('helvetica','',12);
    $this->Cell(102,5,'Riscossione credito verso ',0,0,'R');
    $this->Cell(80,5,$cliente1,0,1);
    if (!empty($cliente2)) {
        $this->Cell(102,5);
        $this->Cell(80,5,$cliente2,0,1);
    }
    $this->Cell(102,5);
    $this->Cell(80,5,$cliente3,0,1);
    $this->Cell(102,5);
    $this->Cell(80,5,$cliente4,0,1);
    $this->Ln(3);
    }

function Footer()
    {
    global $piede;
    //Page footer
    $this->SetY(-10);
    $this->SetFont('helvetica','',8);
    $this->MultiCell(190,4,'Copia '.$piede,0,'C',0);
    }
    }

$pdf=new PDF();
$pdf->SetTopMargin(65);
$pdf->SetHeaderMargin(5);
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
$pdf->AddPage();
$rs_rig = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes like '$testat'","id_tes desc");
$importo = 0;
while ($rigo = gaz_dbi_fetch_array($rs_rig)) {
    if ($pdf->GetY() <= 280) {
          $pdf->Cell(150,8,$rigo['descri'],1,0,'L');
          $pdf->Cell(40,8,gaz_format_number($rigo['prelis']),1,1,'R');
          $importo += preg_replace("/\,/",'.', $rigo['prelis']);

    } else {
       $pdf->SetY(285);
       $pdf->Cell(150,12,'>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>>',1,1,'C');
       $pdf->AddPage();
    }
}
$piede='il cliente';
$pdf->Cell(150,8,'TOTALE RISCOSSO',1,0,'R');
$pdf->Cell(40,8,gaz_format_number($importo),1,1,'R',1);

if ($pdf->GetY() <= 135)
    {
    $pdf->SetY(140);
    $pdf->SetFont('helvetica','',8);
    $pdf->MultiCell(190,4,'Copia per il cliente',0,'C',0);
    $pdf->Ln(8);
    $pdf->Line(0,145,210,145);
    $pdf->Header();
    }
 else   $pdf->AddPage();
$rs_rig = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes like '$testat'","id_tes desc");
$importo = 0;
while ($rigo = gaz_dbi_fetch_array($rs_rig)) {
    if ($pdf->GetY() <= 280) {
          $pdf->Cell(150,8,$rigo['descri'],1,0,'L');
          $pdf->Cell(40,8,gaz_format_number($rigo['prelis']),1,1,'R');
          $importo += $rigo['prelis'];
    } else {
       $pdf->SetY(285);
       $pdf->Cell(190,12,'>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>>',1,1,'C');
       $pdf->AddPage();
    }
}
$piede='ad uso amministrativo';
$pdf->Cell(150,8,'TOTALE RISCOSSO',1,0,'R');
$pdf->Cell(40,8,gaz_format_number($importo),1,1,'R',1);
$pdf->Output();
?>
