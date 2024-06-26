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
if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
    gaz_set_time_limit (0);
}
if (!isset($_GET['id_rig']) ) {
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit;
}

require("./lang.".$admin_aziend['lang'].".php");
$script_transl = $strScript["print_customer_payment_receipt.php"];
require("../../config/templates/report_template.php");

function getData($id_rig)
{
    // restituisce tutti i dati relativi al rigo contabile del pagamento
    global $gTables;
    $anagrafica = new Anagrafica();
    $paymov = new Schedule;
    $sqlquery= "SELECT ".$gTables['tesmov'].".*, ".$gTables['paymov'].".*, ".$gTables['rigmoc'].".import
    FROM ".$gTables['rigmoc']." LEFT JOIN ".$gTables['paymov']." ON ".$gTables['paymov'].".id_rigmoc_pay = ".$gTables['rigmoc'].".id_rig
    LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes
    WHERE ".$gTables['rigmoc'].".id_rig = $id_rig ORDER BY expiry ASC";
    $rs = gaz_dbi_query($sqlquery);
    $a=array();
    $i=1;
    while ($r = gaz_dbi_fetch_array($rs)) {
        $a[$i] = $r;
        $a[$i]['t'] = $paymov->getDocumentData($r['id_tesdoc_ref'], $r['clfoco']);
        $i++;
    }
    return array('d'=>$a,'partner'=>$anagrafica->getPartner($a[1]['clfoco']));
}

$d=getData(intval($_GET['id_rig']));
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data=$admin_aziend['citspe'].", lì ".ucwords($gazTimeFormatter->format(new DateTime('@'.mktime(12,0,0,substr($d['d'][1]['datreg'],5,2),substr($d['d'][1]['datreg'],8,2),substr($d['d'][1]['datreg'],0,4)))));
$item_head = array('top'=>array(array('lun' => 80,'nam'=>'Descrizione'),
                                array('lun' => 25,'nam'=>'Numero Conto')
                               )
                   );
$title = array('luogo_data'=>$luogo_data,
               'title'=>$script_transl['title'].$d['partner']['ragso1'].' '.$d['partner']['ragso2'],
               'hile'=>array(   array('lun' => 30,'nam'=>$script_transl['id_tesdoc_ref']),
                                array('lun' => 70,'nam'=>$script_transl['descri']),
                                array('lun' => 25,'nam'=>$script_transl['numfat']),
                                array('lun' => 25,'nam'=>$script_transl['datfat']),
                                array('lun' => 36,'nam'=>$script_transl['amount'])
                            )
              );
$aRiportare = array('top'=>array(array('lun' => 166,'nam'=>'da riporto : '),
                           array('lun' => 20,'nam'=>'')
                           ),
                    'bot'=>array(array('lun' => 166,'nam'=>'a riportare : '),
                           array('lun' => 20,'nam'=>'')
                           )
                    );
$pdf = new Report_template();
$pdf->setVars($admin_aziend,$title);
$pdf->setFooterMargin(22);
$pdf->setTopMargin(43);
$pdf->SetFillColor(160, 255,220 );
$pdf->setRiporti('');
$pdf->AddPage();
$config = new Config;
$paymov = new Schedule;
$ctrl_pm=0;
$pdf->SetFont('helvetica','',10);
$pdf->Cell(100,6,$d['d'][1]['descri'],1,1,'L',1,'',1);

foreach ($d['d'] AS $k => $mv) {
    if ($ctrl_pm <> $mv["id_tesdoc_ref"]){
    }
    $pdf->Cell(30,6,$mv['id_tesdoc_ref'],'LTB',0,'L',0,'',1);
    $pdf->Cell(70,6,$mv['t']['descri'],1,0,'L',0,'',1);
    $pdf->Cell(25,6,$mv['t']["numdoc"].'/'.$mv['t']['seziva'],1,0,'C',0);
    $pdf->Cell(25,6,gaz_format_date($mv['t']["datdoc"]),1,0,'C',0);
    $pdf->Cell(36,6,gaz_format_number($mv['amount']),1,1,'R',0);
    $ctrl_pm=$mv["id_tesdoc_ref"];
}
$pdf->Ln(10);
$pdf->SetFont('courier','',5);
$pdf->Cell(80,6,$d['d'][1]['id_tes'],'LTB',0,'L');
$pdf->SetFont('helvetica','B',10);
$pdf->Cell(70,6,$script_transl['tot'].': ','TB',0,'R');
$pdf->Cell(36,6,'€ '.gaz_format_number($d['d'][1]['import']),'RTB',1,'R',1,'',1);
$pdf->Ln(10);
$pdf->SetFont('courier','',8);
$pdf->Cell(100,6,'');
$pdf->Cell(60,6,$admin_aziend['user_firstname'].' '.$admin_aziend['user_lastname'],0,1,'C');
$pdf->Cell(100,6);
$pdf->Cell(60,20,'____________________________',0,0,'C');
$pdf->setRiporti('');
$pdf->Output();
?>
