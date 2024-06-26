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
require("../../library/include/ciftolet.inc.php");



$nuw = new numberstowords_it();

$testat = intval($_GET['id_tes']);

$tesbro = gaz_dbi_get_row($gTables['tesbro'],"id_tes",$testat);
//se non e' il tipo di documento stampabile da questo modulo ... va a casa
if ($tesbro['tipdoc'] <> 'AOB' and $tesbro['tipdoc'] <> 'AOA') {
    header("Location: report_pagdeb.php");
    exit;
}
if ($tesbro['tipdoc'] == 'AOA') {
    $descridoc= 'addebito';
} if ($tesbro['tipdoc'] == 'AOB') {
    $descridoc= 'bonifico';
}

// Non e' testato perche' non ho i dati :-(
$anagrafica = new Anagrafica();
//$fornitor = gaz_dbi_get_row($gTables['clfoco'],"codice",$tesbro['clfoco']);
$fornitor = $anagrafica->getPartner($tesbro['clfoco']);

//ricavo il conto della banca di addebito(sulla testata e' riportata impropriamente su numfat)
$bancadd = $anagrafica->getPartner($tesbro['numfat']);
$banacc = gaz_dbi_get_row($gTables['banapp'],"codice",$tesbro['banapp']);

$gazTimeFormatter->setPattern('MMMM');
$nomemese=ucwords($gazTimeFormatter->format(new DateTime("2000-".substr($tesbro['datemi'],5,2)."-01")));
$min = substr($tesbro['initra'],14,2);
$ora = substr($tesbro['initra'],11,2);
$day = substr($tesbro['initra'],8,2);
$month = substr($tesbro['initra'],5,2);
$year = substr($tesbro['initra'],0,4);
$banca1=$bancadd['ragso1'];
$banca2=$bancadd['indspe'];
$banca3=sprintf("%05d",$bancadd['capspe'])." ".$bancadd['citspe']." (".$bancadd['prospe'].")";
$emissione =$tesbro['numdoc'].' del '.substr($tesbro['datemi'],8,2).'/'.substr($tesbro['datemi'],5,2).'/'.substr($tesbro['datemi'],0,4);
$intesta1=$admin_aziend['ragso1'].' '.$admin_aziend['ragso2'];
$intesta2=$admin_aziend['indspe'].' '.sprintf("%05d", $admin_aziend['capspe']).' '.$admin_aziend['citspe'].' ('.$admin_aziend['prospe'].')';
$intesta3='Tel.'.$admin_aziend['telefo'].' C.F. '.$admin_aziend['codfis'].' P.I. '.$admin_aziend['pariva'];
$intesta4=$admin_aziend['e_mail'];
$logo=$admin_aziend['image'];
$datafirma=$admin_aziend['citspe']." lì ".substr($tesbro['datemi'],8,2).'/'.substr($tesbro['datemi'],5,2).'/'.substr($tesbro['datemi'],0,4);
$fornitore1=$fornitor['ragso1'];
$fornitore2=$fornitor['ragso2'];
$fornitore3=$fornitor['indspe'];
$fornitore4=sprintf("%05d", $fornitor['capspe']).' '.$fornitor['citspe'].' ('.$fornitor['prospe'].')';
$colore=$admin_aziend['colore'];

class PDF extends TCPDF
{
    function Header()
    {
        global $logo,$descridoc,$intesta1,$intesta2,$intesta3,$intesta4,$fornitore1,$fornitore2,$fornitore3,$fornitore4,$banca1,$banca2,$banca3,$emissione,$colore;
        //Logo
        $this->colore=$colore;
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $posiz=$this->GetY();
        $this->Image('@'.$logo,150,$posiz,30,0,'Logo aziendale');
        $this->SetFont('times','B',16);
        $this->Cell(130,6,$intesta1,0,1,'L');
        $this->SetFont('helvetica','',9);
        $this->Cell(130,4,$intesta2,0,2,'L');
        $this->Cell(130,4,$intesta3,0,2,'L');
        $this->Cell(130,4,$intesta4,0,0,'L');
        $this->Ln(5);
        $this->SetFont('helvetica','',14);
        $this->Cell(95,6,'Ordine di '.$descridoc.' n.'.$emissione,1,1,'L',1);
        $this->SetFont('helvetica','',10);
        $this->Cell(95,4,'a favore di :',0,1,'L');
        $this->SetFont('helvetica','',12);
        $this->Cell(95,5,$fornitore1,0,0,'L');
        $this->Cell(20,5,'Spett.le ',0,0,'R');
        $this->Cell(130,5,$banca1,0,1,'L');
        if (!empty($fornitore2)) {
           $this->Cell(115,5,$fornitore2,0,0,'L');
           $this->Cell(130,5,$banca2,0,1,'L');
           $this->Cell(115,5,$fornitore3,0,0,'L');
           $this->Cell(130,5,$banca3,0,1,'L');
           $this->Cell(115,5,$fornitore4,0,1,'L');
        } else {
           $this->Cell(115,5,$fornitore3,0,0,'L');
           $this->Cell(130,5,$banca2,0,1,'L');
           $this->Cell(115,5,$fornitore4,0,0,'L');
           $this->Cell(130,5,$banca3,0,1,'L');
           $this->Ln(5);
        }
        $this->Ln(6);
    }

    function Footer()
    {
        global $descridoc,$piede,$datafirma;
        //Page footer
        $this->SetY(-14);
        $this->SetFont('helvetica','',12);
        $this->Cell(190,5,$datafirma.' _____________________________________',0,1,'R');
        $this->SetFont('helvetica','',8);
        $this->Cell(90,4,'Copia '.$descridoc.' '.$piede,0,0,'R');
        $this->Cell(70,4,'firma',0,0,'R');
    }
}

$pdf=new PDF();
$pdf->SetTopMargin(65);
$pdf->SetHeaderMargin(5);
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
$pdf->AddPage();
$_POST['giofat'] = substr($tesbro['datfat'],8,2);
$_POST['mesfat'] = substr($tesbro['datfat'],5,2);
$_POST['annfat'] = substr($tesbro['datfat'],0,4);
if ($tesbro['tipdoc'] == 'AOB') {
   $text1= "La presente quale Ordine di Bonifico a favore della Spett.le ".
           $fornitore1.$fornitore2." ".$fornitore3." - ".$fornitore4.
           " per i seguenti pagamenti:";
   $text2= "da accreditare con valuta ".substr($tesbro['datfat'],8,2)."-".
           substr($tesbro['datfat'],5,2)."-".substr($tesbro['datfat'],0,4).
           "\nsul conto corrente con IBAN = ".$fornitor['iban'];
} elseif ($tesbro['tipdoc'] == 'AOA') {
  $text1= "La presente quale Ordine di Addebito a favore della Spett.le ".$fornitore1.$fornitore2." ".$fornitore3." - ".$fornitore4." per i seguenti pagamenti:";
  $text2= " con scadenza ".substr($tesbro['datfat'],8,2)."-".substr($tesbro['datfat'],5,2)."-".substr($tesbro['datfat'],0,4)." presso la ".$banacc['descri']." c/c ".$tesbro['spediz']." ABI ".$banacc['codabi']." CAB ".$banacc['codcab']." addebitando la stessa cifra sul nostro C/C n.".$bancadd['iban'].".";
}
$pdf->MultiCell(190,6,$text1,0,'L',0);
//azzero il totale
$totale=0.00;
$rs_rig = gaz_dbi_dyn_query("descri,prelis", $gTables['rigbro'], "id_tes = '$testat'","id_tes desc");
while ($rigo = gaz_dbi_fetch_array($rs_rig)) {
    $totale +=$rigo['prelis'];
    if ($pdf->GetY() <= 185) {
        $pdf->Cell(140,6,$rigo['descri'],0,0,'L');
        $pdf->Cell(20,6,$admin_aziend['curr_name'],0,0,'R');
        $pdf->Cell(30,6,gaz_format_number($rigo['prelis']),0,1,'R');
    } else {
        $pdf->Cell(140,6,$rigo['descri'],0,0,'L');
        $pdf->Cell(20,6,$admin_aziend['curr_name'],0,0,'R');
        $pdf->Cell(30,6,gaz_format_number($rigo['prelis']),0,1,'R');
        $pdf->SetY(277);
        $pdf->Cell(130,12,'>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>>',1,1,'C');
        $pdf->AddPage();
    }
}
$impwords = $nuw->euro2assegno($totale);
$pdf->Cell(190,1,'','B',1);
$pdf->Cell(50,6,'PER UN TOTALE DI',0,0,'L');
$pdf->SetFont('helvetica','',14);
$pdf->Cell(110,6,$impwords,'BTL',0,'R',1);
$pdf->Cell(30,6,gaz_format_number($totale),'BTR',1,'R',1);
$pdf->SetFont('helvetica','',12);
$pdf->MultiCell(190,6,$text2,0,'L',0);
$piede='ad uso interno';
if ($pdf->GetY() <= 133) {
    $pdf->SetY(136);
    $pdf->SetFont('helvetica','',12);
    $pdf->Cell(190,5,$datafirma.' _____________________________________',0,1,'R');
    $pdf->SetFont('helvetica','',8);
    $pdf->Cell(90,4,'Copia '.$descridoc.' ad uso interno',0,0,'R');
    $pdf->Cell(70,4,'firma',0,0,'R');
    $pdf->Line(0,149,210,149);
    $pdf->Ln(16);
    $pdf->Header();
} else {
    $pdf->AddPage();
}
//azzero i totali
$pdf->MultiCell(190,6,$text1,0,'L',0);
$rs_rig = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = '$testat'","id_tes desc");
while ($rigo = gaz_dbi_fetch_array($rs_rig)) {
    if ($pdf->GetY() <= 272) {
       $pdf->Cell(140,6,$rigo['descri'],0,0,'L');
       $pdf->Cell(20,6,$admin_aziend['curr_name'],0,0,'R');
       $pdf->Cell(30,6,gaz_format_number($rigo['prelis']),0,1,'R');
    } else {
      $pdf->Cell(140,6,$rigo['descri'],0,0,'L');
      $pdf->Cell(20,6,$admin_aziend['curr_name'],0,0,'R');
      $pdf->Cell(30,6,gaz_format_number($rigo['prelis']),0,1,'R');
      $pdf->SetY(277);
      $pdf->Cell(190,12,'>>> --- SEGUE SU PAGINA SUCCESSIVA --- >>>',1,1,'C');
      $pdf->AddPage();
    }
}
$impwords = $nuw->euro2assegno($totale);
$pdf->Cell(190,1,'','B',1);
$pdf->Cell(50,6,'PER UN TOTALE DI',0,0,'L');
$pdf->SetFont('helvetica','',14);
$pdf->Cell(110,6,$impwords,'BTL',0,'R',1);
$pdf->Cell(30,6,gaz_format_number($totale),'BTR',1,'R',1);
$pdf->SetFont('helvetica','',12);
$pdf->MultiCell(190,6,$text2,0,'L',0);
$piede='per la banca';
$pdf->Output();
?>
