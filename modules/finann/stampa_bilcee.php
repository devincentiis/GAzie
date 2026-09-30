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

$nromani = array(0=>"",1=>"I",2=>"II",3=>"III",4=>"IV",5=>"V",6=>"VI",7=>"VII",8=>"VIII",9=>"IX",10=>"X",11=>"XI",12=>"XII",13=>"XIII",14=>"XIV",15=>"XV",16=>"XVI",17=>"XVII",18=>"XVIII",19=>"XIX");
$attdesc = array('A'=>array("Titolo"=>") CREDITI VERSO SOCI:"),'B'=>array("Titolo"=>") IMMOBILIZZAZIONI:",1=>" - Immobilizzazioni immateriali: ",2=>" - Immobilizzazioni materiali:",3=>" - Immobilizzazioni finanziarie: "),'C'=>array("Titolo"=>") ATTIVO CIRCOLANTE:",1=>" - Rimanenze: ",2=>" - Crediti: ",3=>" - Attività finanziarie: ",4=>" - Disponibilità liquide: "),'D'=>array("Titolo"=>") RATEI E RISCONTI:"));
$pasdesc = array('A'=>array("Titolo"=>") PATRIMONIO NETTO:",1=>" - Capitale:",2=>" - Riserva da sovrapprezzo delle azioni:",3=>" - Riserva di rivalutazione:",4=>" - Riserva legale:",5=>" - Riserva per azioni proprie in portafoglio:",6=>" - Riserve statutarie:",7=>" - Altre riserve distintamente indicate:",8=>" - Utili (perdite) portati a nuovo:",9=>" - Utile (perdita) dell'esercizio:"),'B'=>array("Titolo"=>") FONDI RISCHI E ONERI:"),'C'=>array("Titolo"=>") TRATTAMENTO DI FINE RAPPORTO DI LAVORO SUBORDINATO:"),'D'=>array("Titolo"=>") DEBITI:"),'E'=>array("Titolo"=>") RATEI E RISCONTI:"));
$ecodesc = array('A'=>array("Titolo"=>") Valore della produzione:"),'B'=>array("Titolo"=>") Costi della produzione:"),'C'=>array("Titolo"=>") Proventi e oneri finanziari:"),'D'=>array("Titolo"=>") Rettifiche di valore di attivit� finanziarie:"),'E'=>array("Titolo"=>") Proventi e oneri straordinari:"),'_'=>array("Titolo"=>") Risultato prima delle imposte:"));
if (!isset($_GET['bilini']) or !isset($_GET['bilfin'])){
    header("Location: select_bilcee.php");
    exit;
}
$title = 'Stampa bilancio CEE';
$logo = $admin_aziend['image'];
require("../../config/templates/report_template.php");
$gioini = substr($_GET['bilini'],6,2);
$mesini = substr($_GET['bilini'],4,2);
$annini = substr($_GET['bilini'],0,4);
$utsini= mktime(0,0,0,$mesini,$gioini,$annini);
$utsdop= mktime(0,0,0,$mesini,$gioini-1,$annini+1);
$giofin = substr($_GET['bilfin'],6,2);
$mesfin = substr($_GET['bilfin'],4,2);
$annfin = substr($_GET['bilfin'],0,4);
$utsfin= mktime(0,0,0,$mesfin,$giofin,$annfin);
$dataini = date("Ymd",$utsini);
$datafin = date("Ymd",$utsfin);
$datadop = date("Ymd",$utsdop);
//Carica i dati del bilancio IV direttiva CEE
//Legge le linee del file
$data = array();
$descon = array();
$lines=file('IVdirCEE.bil');
foreach($lines as $line) {
        $nuova = explode(';',$line,2);
        $descon[trim($nuova[0])] = $nuova[1];
        $data[] = trim($nuova[0]);
}
$data = array_slice($data,1);
$where = "datreg BETWEEN '$dataini' AND '$datafin' AND caucon <> 'CHI' AND caucon <> 'APE' OR (caucon = 'APE' AND datreg BETWEEN '$dataini' AND '$datadop') GROUP BY codcon ";
$orderby = " codcon ";
$rs_castel = gaz_dbi_dyn_query("codcon, ragso1, SUM(import*(darave='D')-import*(darave='A')) AS saldo, ceedar, ceeave", $gTables['rigmoc']."
                                LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes
                                LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['rigmoc'].".codcon = ".$gTables['clfoco'].".codice
                                LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra", $where, $orderby);
//procedura per la creazione dell'array dei conti riclassificati
while ($castel = gaz_dbi_fetch_array($rs_castel)) {
      if ($castel["saldo"] > 0) { //se l'eccedenza � in dare
         if (! in_array(trim($castel['ceedar']),$data)) {//se non e' riclassificato
            // vedo se c'� la riclassificazione sul mastro
            $mastro = gaz_dbi_get_row($gTables['clfoco'],'codice',substr($castel['codcon'],0,3)."000000");
            $castel['ceedar']=trim($mastro['ceedar']);
            if (! in_array($castel['ceedar'],$data)) {//se non e' riclassificato neanche il mastro
                $castel['ceedar']=$castel['codcon'];
            }
         }
         $conti[$castel['codcon']] = array($castel["saldo"],$castel["ragso1"],$castel["ceedar"]);
      }
      if ($castel["saldo"] < 0) {//se l'eccedenza � in avere
         if (! in_array(trim($castel['ceeave']),$data)) {
            // vedo se c'� la riclassificazione sul mastro
            $mastro = gaz_dbi_get_row($gTables['clfoco'],"codice",substr($castel['codcon'],0,3)."000000");
            $castel['ceeave']=trim($mastro['ceeave']);
            if (! in_array(trim($castel['ceeave']),$data)) {//se non e' riclassificato neanche il mastro
                $castel['ceeave']=$castel['codcon'];
            }
         }
         $conti[$castel['codcon']] = array($castel["saldo"],$castel["ragso1"],$castel["ceeave"]);
      }
}
$contiassoc = array();
foreach ($conti as $value){
 if (! array_key_exists($value[2],$contiassoc))
    $contiassoc[$value[2]] = $value[0];
 else
    $contiassoc[$value[2]] += $value[0];
}
ksort($contiassoc); //array conti creato chiave con codice e valore con saldo totale!

// calcolo l'utile o la perdita (conto economico) e ricreo gli array attivita,passivita,economico.
$economico = array();
$attivo = array();
$passivo = array();
$risulta = array();
foreach ($contiassoc as $key => $value) {
        $ctrlett = substr($key,1,1);
        $ctrlrom = substr($key,2,2);
        $ctrltipcon = substr($key,0,1);
        switch($ctrltipcon)
        {
        case 'E':
        case 4:
        case 3:
        if (trim($ctrlett) == '') {
          $ctrlett='_';
        }
        $economico = $economico + array($key=>$value);
        $risulta[$ctrlett][$ctrlrom][$key] = -$value;
        break;
        case 'A':
        case 1:
        $attivo[$ctrlett][$ctrlrom][$key] = $value;
        break;
        case 'P':
        case 2:
        $passivo[$ctrlett][$ctrlrom][$key] = -$value;
        break;
        }
}
$passivo['A']['09']['PA09000'] = -array_sum($economico);//aggiungo l'utile(perdita) sul relativo conto e riclassifico
ksort($passivo);
ksort($risulta);
$totrom =0.00;
$totlet =0.00;
$totale =0.00;

$item_head = array('top'=>array(array('lun' => 162,'nam'=>'Codice'),
                                array('lun' => 18,'nam'=>'Scorta')
                               )
                   );

$title = array('title'=>"BILANCIO IV direttiva CEE dal ".date("d-m-Y",$utsini)." al ".date("d-m-Y",$utsfin),
               'hile'=>array()
              );
$aRiportare = array('top'=>array(array('lun' => 168,'nam'=>'da riporto : '),
                           array('lun' => 19,'nam'=>'')
                           ),
                    'bot'=>array(array('lun' => 168,'nam'=>'a riportare : '),
                           array('lun' => 19,'nam'=>'')
                           )
                    );

$pdf = new Report_template('P','mm','A4',true,'UTF-8',false,true);
$pdf->setVars($admin_aziend,$title);
$pdf->AddPage();
$pdf->SetFooterMargin(20);
$pdf->Cell(186,6,'STATO PATRIMONIALE AL '.date("d-m-Y",$utsfin),'LTR',1,'C');
$pdf->SetFont('helvetica','B',12);
$pdf->SetTextColor(0,0,255);
$pdf->Cell(186,6,'ATTIVO','LTR',1,'C');
$pdf->SetTextColor(0,0,0);
$pdf->SetFont('helvetica','',8);
foreach ($attivo as $keylet => $vallet) {
        if (! key_exists($keylet,$attdesc)) $keylet = "non class.";
           $pdf->SetFont('helvetica','B',8);
           $pdf->Cell(186,4,$keylet.$attdesc[$keylet]['Titolo'],'LR',1);
           $pdf->SetFont('helvetica','',8);
        foreach ($vallet as $keyrom => $valrom) {
                $pdf->Cell(15,4,'','LR');
                $pdf->Cell(50,4,$nromani[intval($keyrom)].@$attdesc[$keylet][intval($keyrom)],'R',0,'R');
                $pdf->Cell(121,4,'','LR',1);
                foreach ($valrom as $key => $value) {
                        $conto = substr($key,4,3);
                        if ($conto == 0) $conto = ""; else $conto=intval($conto);
                        $totrom +=$value;
                        $totlet +=$value;
                        $totale +=$value;
                        $descrizio = trim($descon[$key]);
                        if ((int)$key < 100000000){//controllo per i conti non classificati
                           if ($value > 0) $stampaval = gaz_format_number($value); else $stampaval = "(".gaz_format_number(-$value).")";
                           $pdf->Cell(15,4,'','LR');
                           $pdf->Cell(50,4,'','R');
                           $pdf->Cell(8,4,$conto.substr($key,7,1).")",'L',0,'R');
                           $pdf->Cell(72,4,$descrizio,'R');
                           $pdf->Cell(10,4,$admin_aziend['curr_name'],'LR',0,'C');
                           $pdf->Cell(31,4,$stampaval,'LR',1,'R');
                        } else {
                           if ($value > 0) $stampaval = gaz_format_number($value); else $stampaval = "(".gaz_format_number(-$value).")";
                           $descricon=gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
                           $pdf->Cell(15,4,'','LR');
                           $pdf->Cell(50,4,'','R');
                           $pdf->Cell(8,4,$key.' - ','L',0,'R');
                           $pdf->Cell(72,4,$descricon.' non riclassificato','R');
                           $pdf->Cell(10,4,$admin_aziend['curr_name'],'LR',0,'C');
                           $pdf->Cell(31,4,$stampaval,'LR',1,'R');
                        }
                }
                if ($totrom > 0) $stampaval = gaz_format_number($totrom); else $stampaval = "(".gaz_format_number(-$totrom).")";
                $pdf->Cell(15,4,'','LR');
                $pdf->Cell(50,4,'','B');
                $pdf->Cell(80,4,"Totale ".$nromani[intval($keyrom)],'BTR',0,'R');
                $pdf->Cell(10,4,$admin_aziend['curr_name'],1,0,'C');
                $pdf->Cell(31,4,$stampaval,1,1,'R');
                $totrom=0.00;
     }
     if($totlet > 0) $stampaval = gaz_format_number($totlet); else $stampaval = "(".gaz_format_number(-$totlet).")";
     $pdf->SetFont('helvetica','B',8);
     $pdf->Cell(145,4,"Totale ".$keylet,'LB',0,'R');
     $pdf->Cell(10,4,$admin_aziend['curr_name'],1,0,'C');
     $pdf->Cell(31,4,$stampaval,1,1,'R');
     $pdf->SetFont('helvetica','',8);
     $totlet=0.00;
}
$pdf->SetFont('helvetica','B',12);
$pdf->SetTextColor(0,0,255);
$pdf->Cell(155,6,"TOTALE DELL'ATTIVO ",1,0,'R');
$pdf->Cell(31,6,gaz_format_number($totale),1,1,'R');
$totale=0.00;
$pdf->Ln(6);
$pdf->SetTextColor(127,64,64);
$pdf->Cell(186,6,'PASSIVO','LTR',1,'C');
$pdf->SetTextColor(0,0,0);
$pdf->SetFont('helvetica','',8);
foreach ($passivo as $keylet => $vallet) {
        if (! key_exists($keylet,$pasdesc)) $keylet = "non class.";
           $pdf->SetFont('helvetica','B',8);
           $pdf->Cell(186,4,$keylet.$pasdesc[$keylet]['Titolo'],'LR',1);
           $pdf->SetFont('helvetica','',8);
        foreach ($vallet as $keyrom => $valrom) {
                $pdf->Cell(15,4,'','LR');
                $pdf->Cell(50,4,$nromani[intval($keyrom)].@$pasdesc[$keylet][intval($keyrom)],'R',0,'R');
                $pdf->Cell(121,4,'','LR',1);
                foreach ($valrom as $key => $value) {
                        $conto = substr($key,4,3);
                        if ($conto == 0) $conto = ""; else $conto=intval($conto);
                        $totrom +=$value;
                        $totlet +=$value;
                        $totale +=$value;
                        $descrizio = trim($descon[$key]);
                        if ((int)$key < 100000000){//controllo per i conti non classificati
                           if ($value > 0) $stampaval = gaz_format_number($value); else $stampaval = "(".gaz_format_number(-$value).")";
                           $pdf->Cell(15,4,'','LR');
                           $pdf->Cell(50,4,'','R');
                           $pdf->Cell(8,4,$conto.substr($key,7,1).")",'L',0,'R');
                           $pdf->Cell(72,4,$descrizio,'R');
                           $pdf->Cell(10,4,$admin_aziend['curr_name'],'LR',0,'C');
                           $pdf->Cell(31,4,$stampaval,'LR',1,'R');
                        } else {
                           if ($value > 0) $stampaval = gaz_format_number($value); else $stampaval = "(".gaz_format_number(-$value).")";
                           $descricon=gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
                           $pdf->Cell(15,4,'','LR');
                           $pdf->Cell(50,4,'','R');
                           $pdf->Cell(8,4,$key.' - ','L',0,'R');
                           $pdf->Cell(72,4,$descricon.' non riclassificato','R');
                           $pdf->Cell(10,4,$admin_aziend['curr_name'],'LR',0,'C');
                           $pdf->Cell(31,4,$stampaval,'LR',1,'R');
                        }
                }
                if ($totrom > 0) $stampaval = gaz_format_number($totrom); else $stampaval = "(".gaz_format_number(-$totrom).")";
                $pdf->Cell(15,4,'','LR');
                $pdf->Cell(50,4,'','B');
                $pdf->Cell(80,4,"Totale ".$nromani[intval($keyrom)],'BTR',0,'R');
                $pdf->Cell(10,4,$admin_aziend['curr_name'],1,0,'C');
                $pdf->Cell(31,4,$stampaval,1,1,'R');
                $totrom=0.00;
     }
     if($totlet > 0) $stampaval = gaz_format_number($totlet); else $stampaval = "(".gaz_format_number(-$totlet).")";
     $pdf->SetFont('helvetica','B',8);
     $pdf->Cell(145,4,"Totale ".$keylet,'LB',0,'R');
     $pdf->Cell(10,4,$admin_aziend['curr_name'],1,0,'C');
     $pdf->Cell(31,4,$stampaval,1,1,'R');
     $pdf->SetFont('helvetica','',8);
     $totlet=0.00;
}
$pdf->SetFont('helvetica','B',12);
$pdf->SetTextColor(127,64,64);
$pdf->Cell(155,6,"TOTALE DEL PASSIVO ",1,0,'R');
$pdf->Cell(31,6,gaz_format_number($totale),1,1,'R');
$totale=0.00;
$pdf->Ln(6);
$pdf->SetTextColor(255,150,50);
$pdf->Cell(186,6,'CONTO ECONOMICO DAL '.date("d-m-Y",$utsini).' AL '.date("d-m-Y",$utsfin),'LTR',1,'C');
$pdf->SetTextColor(0,0,0);
$pdf->SetFont('helvetica','',8);
foreach ($risulta as $keylet => $vallet) {
        if (! key_exists($keylet,$ecodesc)) $keylet = "non class.";
           $pdf->SetFont('helvetica','B',8);
           $pdf->Cell(186,4,$keylet.$ecodesc[$keylet]['Titolo'],'LR',1);
           $pdf->SetFont('helvetica','',8);
        foreach ($vallet as $keyrom => $valrom) {
                $pdf->Cell(15,4,'','LR');
                $pdf->Cell(50,4,$nromani[intval($keyrom)].@$ecodesc[$keylet][intval($keyrom)],'R',0,'R');
                $pdf->Cell(121,4,'','LR',1);
                foreach ($valrom as $key => $value) {
                        $conto = substr($key,4,3);
                        if ($conto == 0) $conto = ""; else $conto=intval($conto);
                        $totrom +=$value;
                        $totlet +=$value;
                        $totale +=$value;
                        $descrizio = trim($descon[$key]);
                        if ((int)$key < 100000000){//controllo per i conti non classificati
                           if ($value > 0) $stampaval = gaz_format_number($value); else $stampaval = "(".gaz_format_number(-$value).")";
                           $pdf->Cell(15,4,'','LR');
                           $pdf->Cell(50,4,'','R');
                           $pdf->Cell(8,4,$conto.substr($key,7,1).")",'L',0,'R');
                           $pdf->Cell(72,4,$descrizio,'R');
                           $pdf->Cell(10,4,$admin_aziend['curr_name'],'LR',0,'C');
                           $pdf->Cell(31,4,$stampaval,'LR',1,'R');
                        } else {
                           if ($value > 0) $stampaval = gaz_format_number($value); else $stampaval = "(".gaz_format_number(-$value).")";
                           $descricon=gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
                           $pdf->Cell(15,4,'','LR');
                           $pdf->Cell(50,4,'','R');
                           $pdf->Cell(8,4,$key.' - ','L',0,'R');
                           $pdf->Cell(72,4,$descricon.' non riclassificato','R');
                           $pdf->Cell(10,4,$admin_aziend['curr_name'],'LR',0,'C');
                           $pdf->Cell(31,4,$stampaval,'LR',1,'R');
                        }
                }
                if ($totrom > 0) $stampaval = gaz_format_number($totrom); else $stampaval = "(".gaz_format_number(-$totrom).")";
                $pdf->Cell(15,4,'','LR');
                $pdf->Cell(50,4,'','B');
                $pdf->Cell(80,4,"Totale ".$nromani[intval($keyrom)],'BTR',0,'R');
                $pdf->Cell(10,4,$admin_aziend['curr_name'],1,0,'C');
                $pdf->Cell(31,4,$stampaval,1,1,'R');
                $totrom=0.00;
     }
     if($totlet > 0) $stampaval = gaz_format_number($totlet); else $stampaval = "(".gaz_format_number(-$totlet).")";
     $pdf->SetFont('helvetica','B',8);
     $pdf->Cell(145,4,"Totale ".$keylet,'LB',0,'R');
     $pdf->Cell(10,4,$admin_aziend['curr_name'],1,0,'C');
     $pdf->Cell(31,4,$stampaval,1,1,'R');
     $pdf->SetFont('helvetica','',8);
     $totlet=0.00;
}
$pdf->SetFont('helvetica','B',12);
$pdf->SetTextColor(255,150,50);
$pdf->Cell(155,6,"UTILE (PERDITA) D'ESERCIZIO ",1,0,'R');
if($totale > 0) $stampaval = gaz_format_number($totale); else $stampaval = "(".gaz_format_number(-$totale).")";
$pdf->Cell(31,6,$stampaval,1,1,'R');
$totale=0.00;
$pdf->SetTextColor(0,0,0);
$pdf->Output();
?>
