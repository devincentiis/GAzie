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
require('../../library/include/datlib.inc.php');
$admin_aziend=checkAdmin();
require('./lang.'.$admin_aziend['lang'].'.php');
$script_transl = $strScript['select_bilanc.php'];
if (!isset($_GET['di']) or !isset($_GET['df']) or !isset($_GET['pi'])) {
    header('Location: select_bilanc.php');
    exit;
}
$pagini = intval($_GET['pi']);
$gioini = substr($_GET['di'],6,2);
$mesini = substr($_GET['di'],4,2);
$annini = substr($_GET['di'],0,4);
$utsini= mktime(0,0,0,$mesini,$gioini,$annini);
$giofin = substr($_GET['df'],6,2);
$mesfin = substr($_GET['df'],4,2);
$annfin = substr($_GET['df'],0,4);
$utsfin= mktime(0,0,0,$mesfin,$giofin,$annfin);
$utsdop= mktime(0,0,0,$mesini,$gioini-1,$annini+1);
$datainizio = date('Ymd',$utsini);
$datafine = date('Ymd',$utsfin);
$datadopo = date('Ymd',$utsdop);
$dettcf = $_GET['cf'];

//funzione per la creazione dell'array dei conti con saldo diverso da 0 e ordinati per tipo e numero di conto
function ValoriConti($datainizio,$datafine,$datadopo,$mastrocli,$mastrofor,$dettcf)
{
    global $gTables;
    $sqlquery = "SELECT codcon, SUM(import) AS somma, darave ".
                "FROM ".$gTables['rigmoc']." LEFT JOIN ".$gTables['tesmov']." ON ".
                $gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes ".
                "WHERE datreg BETWEEN ".$datainizio." AND ".$datafine." ".
                "AND caucon <> 'CHI' AND caucon <> 'APE' ".
                "OR (caucon = 'APE' AND datreg BETWEEN ".$datainizio." AND ".$datadopo.") ".
                "GROUP BY codcon, darave ".
                "ORDER BY codcon desc, darave";
    $rs_castel = gaz_dbi_query($sqlquery);
    $ctrlcodcon=0;
    $ctrlsaldo=0;
	$totclienti=0;
	$totfornitori=0;
    $costi =  array();
    $ricavi =  array();
    $attivo =  array();
    $passivo =  array();
    $clienti =  array();
    $fornitori =  array();
    while ($castel = gaz_dbi_fetch_array($rs_castel)) {
         if ($dettcf==2 && substr($castel['codcon'],0,3)==$mastrocli) {
               $codcon=$mastrocli*1000000;
		 } elseif ($dettcf==2 && substr($castel['codcon'],0,3)==$mastrofor) {
               $codcon=$mastrofor*1000000;
		 } else {
               $codcon=$castel['codcon'];
		 }


         if ($codcon != $ctrlcodcon and $ctrlcodcon != 0 ) {
            if ($ctrlsaldo != 0) {
               $ctrltipcon = substr($ctrlcodcon,0,1);
               switch  ($ctrltipcon){
                       case 4:  //economici
                       case 3:
                       if  ($ctrlsaldo > 0) {
                           $costi[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                       } else {
                           $ricavi[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                       }
                       break;
                       default: //patrimoniali
					   if  ($dettcf==3 && substr($ctrlcodcon,0,3)==$mastrocli) {
                            $clienti[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
							$totclienti += $ctrlsaldo;
					   } elseif ($dettcf==3 && substr($ctrlcodcon,0,3)==$mastrofor) {
                            $fornitori[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
							$totfornitori += $ctrlsaldo;
					   } else {
                          if  ($ctrlsaldo > 0) {
                              $attivo[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                          } else {
                              $passivo[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                          }
					   }
                       break;
                }
            }
            $ctrlsaldo=0;
        }
        if ($castel['darave'] == 'D') {
            $ctrlsaldo += $castel['somma'];
        } else {
            $ctrlsaldo -= $castel['somma'];
        }
        $ctrlcodcon=$codcon;
    }
    if ($ctrlsaldo != 0) {
        $ctrltipcon = substr($ctrlcodcon,0,1);
        switch  ($ctrltipcon){
                case 4:  //economici
                case 3:
                       if  ($ctrlsaldo > 0) {
                           $costi[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                       } else {
                           $ricavi[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                       }
                break;
                default: //patrimoniali
					   if  ($dettcf==3 && substr($ctrlcodcon,0,3)==$mastrocli) {
                            $clienti[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
							$totclienti += $ctrlsaldo;
					   } elseif ($dettcf==3 && substr($ctrlcodcon,0,3)==$mastrofor) {
                            $fornitori[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
							$totfornitori += $ctrlsaldo;
					   } else {
                          if  ($ctrlsaldo > 0) {
                              $attivo[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                          } else {
                              $passivo[$ctrlcodcon]=number_format($ctrlsaldo,2,'.','');
                          }
					   }
                       break;
        }
    }
	if ($dettcf==3) {
       $attivo[$mastrocli*1000000]=number_format($totclienti,2,'.','');
       $passivo[$mastrofor*1000000]=number_format($totfornitori,2,'.','');
    }
    ksort($costi);
    ksort($ricavi);
    ksort($attivo);
    ksort($passivo);
    ksort($clienti);
    ksort($fornitori);
    $conti = array('cos' => $costi,'ric' => $ricavi,'att' => $attivo,'pas' => $passivo,'cli' => $clienti,'for' => $fornitori);
    return $conti;
}
$title = $script_transl[6].$script_transl[7].$gioini.'-'.$mesini.'-'.$annini.$script_transl[8].$giofin.'-'.$mesfin.'-'.$annfin ;

$topCarry = array(array('lenght' => 100,'name'=>'da riporto : ','frame' => 0,'fill'=>0),
                  array('lenght' => 35,'name'=>'','frame' => 1,'fill'=>0));
$botCarry = array(array('lenght' => 100,'name'=>'a riporto : ','frame' => 0,'fill'=>0),
                  array('lenght' => 35,'name'=>'','frame' => 1,'fill'=>0));

require('../../config/templates/standard_template.php');
$pdf = new Standard_template('P','mm','A4',true,'UTF-8',false,true);
$pdf->setVars($admin_aziend,$title,0,1);
$pdf->AddPage();
$pdf->SetFont('helvetica','',10);
$conti = ValoriConti($datainizio,$datafine,$datadopo,$admin_aziend['mascli'],$admin_aziend['masfor'],$dettcf);
if ($conti) {
    $loss = round(array_sum($conti['cos']),2);
    $profit = round(array_sum($conti['ric']),2);
    $assets = round(array_sum($conti['att']),2);
    $liabilities = round(array_sum($conti['pas']),2);
    $ctrl_bal = round($loss + $profit + $assets + $liabilities,2);
    $income = round($loss + $profit,2);
    $pdf->Cell(190,5,$script_transl[9].$script_transl[8].$giofin.'-'.$mesfin.'-'.$annfin ,1,1,'C',1);
    $pdf->Ln(5);
    $mas=0;
    $ctrlmas=0;
    $totmas=0;
    $pdf->Cell(20,5,$script_transl[12],1,1,'C',1);
    foreach ($conti['att'] as $key => $value) {
        $mas=substr($key,0,3);
        if ($ctrlmas != $mas) {
           if ($ctrlmas != 0) {
              $pdf->Cell(100,5,'','L');
              $pdf->Cell(40,5,'','T');
              $pdf->Cell(15,5,$admin_aziend['curr_name'],'T');
              $pdf->Cell(35,5,gaz_format_number($totmas),1,1,'R');
           }
           $pdf->setTopCarryBar('');
           $pdf->setBotCarryBar('');
           $totmas = 0;
           $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
           $pdf->Cell(20,5,$mas,'L');
           $pdf->Cell(80,5,$descri['descri'],1,1,'C');
        }
        $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
        $pdf->Cell(20,5,substr($key,3,6),'L',0,'R');
        $pdf->Cell(80,5,$descri['descri'],'L',0,'L',0,'',1);
        $pdf->Cell(35,5,gaz_format_number($value),'L',1,'R');
        $totmas += $value;
        $topCarry[1]['name']= gaz_format_number($totmas);
        $botCarry[1]['name']= gaz_format_number($totmas);
        $pdf->setTopCarryBar($topCarry);
        $pdf->setBotCarryBar($botCarry);
        $ctrlmas = $mas;
    }
    $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
    $pdf->Cell(100,5,'','L');
    $pdf->Cell(40,5,'','T');
    $pdf->Cell(15,5,$admin_aziend['curr_name'],'T');
    $pdf->Cell(35,5,gaz_format_number($totmas),1,1,'R');
    $totmas=0;
    $pdf->setTopCarryBar('');
    $pdf->setBotCarryBar('');
    $pdf->Cell(100,5,'','T');
    $pdf->Cell(55,5,$script_transl[16].$script_transl[12],'LTB',0,'R',1);
    $pdf->Cell(35,5,gaz_format_number($assets),'RTB',1,'R',1);
    if ($income > 0 ) {    //perdita
        $pdf->Cell(100,5);
        $pdf->Cell(55,5,$script_transl[11],1,0,'R');
        $pdf->Cell(10,5,'==>','TB',0,'C');
        $pdf->Cell(25,5,gaz_format_number($income),'RTB',1,'R');
        $assets += $income;
        $pdf->Cell(100,5);
        $pdf->Cell(55,5,$script_transl[16],'LTB',0,'R',1);
        $pdf->Cell(35,5,gaz_format_number($assets),'RTB',1,'R',1);
    }
    $ctrlmas=0;
    $pdf->Cell(20,5,$script_transl[13],1,1,'C',1);
    foreach ($conti['pas'] as $key => $value){
        $mas=substr($key,0,3);
        if ($ctrlmas != $mas) {
           if ($ctrlmas != 0) {
              $pdf->Cell(100,5,'','L');
              $pdf->Cell(40,5,'','T');
              $pdf->Cell(15,5,$admin_aziend['curr_name'],'T');
              $pdf->Cell(35,5,gaz_format_number(-$totmas),1,1,'R');
           }
           $pdf->setTopCarryBar('');
           $pdf->setBotCarryBar('');
           $totmas = 0;
           $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
           $pdf->Cell(20,5,$mas,'L');
           $pdf->Cell(80,5,$descri['descri'],1,1,'C');
        }
        $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
        $pdf->Cell(20,5,substr($key,3,6),'L',0,'R');
        $pdf->Cell(80,5,$descri['descri'],'L',0,'L',0,'',1);
        $pdf->Cell(35,5,gaz_format_number(-$value),'L',1,'R');
        $totmas += $value;
        $topCarry[1]['name']= gaz_format_number(-$totmas);
        $botCarry[1]['name']= gaz_format_number(-$totmas);
        $pdf->setTopCarryBar($topCarry);
        $pdf->setBotCarryBar($botCarry);
        $ctrlmas = $mas;
    }
    $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
    $pdf->Cell(100,5,'','L');
    $pdf->Cell(40,5,'','T');
    $pdf->Cell(15,5,$admin_aziend['curr_name'],'T');
    $pdf->Cell(35,5,gaz_format_number(-$totmas),1,1,'R');
    $totmas=0;
    $pdf->setTopCarryBar('');
    $pdf->setBotCarryBar('');
    $pdf->Cell(100,5,'','T');
    $pdf->Cell(55,5,$script_transl[16].$script_transl[13],'LTB',0,'R',1);
    $pdf->Cell(35,5,gaz_format_number(-$liabilities),'RTB',1,'R',1);
    if ($income < 0 ) {    //utile
        $pdf->Cell(100,5);
        $pdf->Cell(55,5,$script_transl[10],1,0,'R');
        $pdf->Cell(10,5,'==>','TB',0,'C');
        $pdf->Cell(25,5,gaz_format_number(-$income),'RTB',1,'R');
        $liabilities += $income;
        $pdf->Cell(100,5);
        $pdf->Cell(55,5,$script_transl[16],'LTB',0,'R',1);
        $pdf->Cell(35,5,gaz_format_number(-$liabilities),'RTB',1,'R',1);
    }
	$pdf->AddPage();
    $pdf->Cell(190,5,$script_transl[17].$script_transl[7].$gioini.'-'.$mesini.'-'.$annini.$script_transl[8].$giofin.'-'.$mesfin.'-'.$annfin ,1,1,'C',1);
    $pdf->Ln(5);
    $ctrlmas=0;
    $pdf->Cell(20,5,$script_transl[15],1,1,'C',1);
    foreach ($conti['ric'] as $key => $value){
        $mas=substr($key,0,3);
        if ($ctrlmas != $mas) {
           if ($ctrlmas != 0) {
              $pdf->Cell(100,5,'','L');
              $pdf->Cell(40,5,'','T');
              $pdf->Cell(15,5,$admin_aziend['curr_name'],'T');
              $pdf->Cell(35,5,gaz_format_number(-$totmas),1,1,'R');
           }
           $pdf->setTopCarryBar('');
           $pdf->setBotCarryBar('');
           $totmas = 0;
           $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
           $pdf->Cell(20,5,$mas,'L');
           $pdf->Cell(80,5,$descri['descri'],1,1,'C');
        }
        $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
        $pdf->Cell(20,5,substr($key,3,6),'L',0,'R');
        $pdf->Cell(80,5,$descri['descri'],'L',0,'L',0,'',1);
        $pdf->Cell(35,5,gaz_format_number(-$value),'L',1,'R');
        $totmas += $value;
        $topCarry[1]['name']= gaz_format_number(-$totmas);
        $botCarry[1]['name']= gaz_format_number(-$totmas);
        $pdf->setTopCarryBar($topCarry);
        $pdf->setBotCarryBar($botCarry);
        $ctrlmas = $mas;
    }
    $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
    $pdf->Cell(100,5,'','L');
    $pdf->Cell(40,5,'','T');
    $pdf->Cell(15,5,$admin_aziend['curr_name'],'T');
    $pdf->Cell(35,5,gaz_format_number(-$totmas),1,1,'R');
    $totmas=0;
    $pdf->setTopCarryBar('');
    $pdf->setBotCarryBar('');
    $pdf->Cell(100,5,'','T');
    $pdf->Cell(55,5,$script_transl[16].$script_transl[15],'LTB',0,'R',1);
    $pdf->Cell(35,5,gaz_format_number(-$profit),'RTB',1,'R',1);
    if ($income > 0 ) {    //perdita
        $pdf->Cell(100,5);
        $pdf->Cell(55,5,$script_transl[11],1,0,'R');
        $pdf->Cell(10,5,'==>','TB',0,'C');
        $pdf->Cell(25,5,gaz_format_number($income),'RTB',1,'R');
        $profit -= $income;
        $pdf->Cell(100,5);
        $pdf->Cell(55,5,$script_transl[16],'LTB',0,'R',1);
        $pdf->Cell(35,5,gaz_format_number(-$profit),'RTB',1,'R',1);
    }
    $ctrlmas=0;
    $pdf->Cell(20,5,$script_transl[14],1,1,'C',1);
    foreach ($conti['cos'] as $key => $value){
        $mas=substr($key,0,3);
        if ($ctrlmas != $mas) {
           if ($ctrlmas != 0) {
              $pdf->Cell(100,5,'','L');
              $pdf->Cell(40,5,'','T');
              $pdf->Cell(15,5,$admin_aziend['curr_name'],'T');
              $pdf->Cell(35,5,gaz_format_number($totmas),1,1,'R');
           }
           $totmas = 0;
           $pdf->setTopCarryBar('');
           $pdf->setBotCarryBar('');
           $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
           $pdf->Cell(20,5,$mas,'L');
           $pdf->Cell(80,5,$descri['descri'],1,1,'C');
        }
        $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
        $pdf->Cell(20,5,substr($key,3,6),'L',0,'R');
        $pdf->Cell(80,5,$descri['descri'],'L',0,'L',0,'',1);
        $pdf->Cell(35,5,gaz_format_number($value),'L',1,'R');
        $totmas += $value;
        $topCarry[1]['name']= gaz_format_number($totmas);
        $botCarry[1]['name']= gaz_format_number($totmas);
        $pdf->setTopCarryBar($topCarry);
        $pdf->setBotCarryBar($botCarry);
        $ctrlmas = $mas;
    }
    $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$mas*1000000);
    $pdf->Cell(100,5,'','L');
    $pdf->Cell(40,5,'','T');
    $pdf->Cell(15,5,$admin_aziend['curr_name'],'T');
    $pdf->Cell(35,5,gaz_format_number($totmas),1,1,'R');
    $totmas=0;
    $pdf->setTopCarryBar('');
    $pdf->setBotCarryBar('');
    $pdf->Cell(100,5,'','T');
    $pdf->Cell(55,5,$script_transl[16].$script_transl[14],'LTB',0,'R',1);
    $pdf->Cell(35,5,gaz_format_number($loss),'RTB',1,'R',1);
    if ($income < 0 ) {    //utile
        $pdf->Cell(100,5);
        $pdf->Cell(55,5,$script_transl[10],1,0,'R');
        $pdf->Cell(10,5,'==>','TB',0,'C');
        $pdf->Cell(25,5,gaz_format_number(-$income),'RTB',1,'R');
        $loss -= $income;
        $pdf->Cell(100,5);
        $pdf->Cell(55,5,$script_transl[16],'LTB',0,'R',1);
        $pdf->Cell(35,5,gaz_format_number($loss),'RTB',1,'R',1);
    }
    $pdf->Ln(5);
}
$pdf->Ln(5);
$pdf->Cell(95,5,$script_transl[19],0,0,'L');
$pdf->Cell(95,5,$script_transl[25],0,1,'C');
if ($dettcf==3) {
	$pdf->AddPage();
//    $pdf->Cell(190,5,'DETTAGLIO CLIENTI E FORNITORI',1,1,'C',1);
//    $pdf->Ln(5);
    $pdf->Cell(50,5,'DETTAGLIO CLIENTI',1,1,'C',1);
    $pdf->Cell(100,5,'','L',1);
    $pdf->setTopCarryBar('');
    $pdf->setBotCarryBar('');
    $totmas = 0;
    $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$admin_aziend['mascli']*1000000);
    $pdf->Cell(20,5,$admin_aziend['mascli'],'L');
    $pdf->Cell(80,5,$descri['descri'],1,1,'C');
    foreach ($conti['cli'] as $key => $value){
       $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
       $pdf->Cell(20,5,substr($key,3,6),'L',0,'R');
       $pdf->Cell(80,5,$descri['descri']);
       $pdf->Cell(35,5,gaz_format_number($value),'L',1,'R');
       $totmas += $value;
       $topCarry[1]['name']= gaz_format_number($totmas);
       $botCarry[1]['name']= gaz_format_number($totmas);
       $pdf->setTopCarryBar($topCarry);
       $pdf->setBotCarryBar($botCarry);
    }
    $pdf->Cell(100,5,'','LB');
    $pdf->Cell(40,5,'','TB');
    $pdf->Cell(15,5,$admin_aziend['curr_name'],'TB');
    $pdf->Cell(35,5,gaz_format_number($totmas),1,1,'R');
    $pdf->setTopCarryBar('');
    $pdf->setBotCarryBar('');
    $pdf->AddPage();
    $pdf->Cell(50,5,'DETTAGLIO FORNITORI',1,1,'C',1);
    $pdf->Cell(100,5,'','L',1);
    $totmas = 0;
    $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$admin_aziend['masfor']*1000000);
    $pdf->Cell(20,5,$admin_aziend['masfor'],'L');
    $pdf->Cell(80,5,$descri['descri'],1,1,'C');
    foreach ($conti['for'] as $key => $value){
       $descri = gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
       $pdf->Cell(20,5,substr($key,3,6),'L',0,'R');
       $pdf->Cell(80,5,$descri['descri']);
       $pdf->Cell(35,5,gaz_format_number(-$value),'L',1,'R');
       $totmas += $value;
       $topCarry[1]['name']= gaz_format_number(-$totmas);
       $botCarry[1]['name']= gaz_format_number(-$totmas);
       $pdf->setTopCarryBar($topCarry);
       $pdf->setBotCarryBar($botCarry);
    }
    $pdf->Cell(100,5,'','LB');
    $pdf->Cell(40,5,'','TB');
    $pdf->Cell(15,5,$admin_aziend['curr_name'],'TB');
    $pdf->Cell(35,5,gaz_format_number(-$totmas),1,1,'R');
    $pdf->setTopCarryBar('');
    $pdf->setBotCarryBar('');
}
$pdf->Output();
if (!empty($_GET['sd'])) {
    gaz_dbi_put_row($gTables['aziend'], "codice", 1, "upginv", $pagini);
}
?>

