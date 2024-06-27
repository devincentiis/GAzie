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
require("../../config/templates/report_template.php");
if (!isset($_GET['code'])) {
    header("Location: report_artico.php");
    exit;
}
$article = gaz_dbi_get_row($gTables['artico'], 'codice', substr($_GET['code'],0,15));
$article['preacq'] = number_format($article['preacq'],$admin_aziend['decimal_price'],',','.');
$article['preve1'] = number_format($article['preve1'],$admin_aziend['decimal_price'],',','.');
$article['preve2'] = number_format($article['preve2'],$admin_aziend['decimal_price'],',','.');
$article['preve3'] = number_format($article['preve3'],$admin_aziend['decimal_price'],',','.');
$article['preve4'] = number_format($article['preve4'],$admin_aziend['decimal_price'],',','.');

$item_head = array('top'=>array(array('lun' => 21,'nam'=>'Codice'),
                                array('lun' => 18,'nam'=>'Cat.Merc'),
                                array('lun' => 60,'nam'=>'Descrizione'),
                                array('lun' => 10,'nam'=>'U.M.'),
                                array('lun' => 18,'nam'=>'Scorta')
                               )
                   );
$item_head['bot']= array(array('lun' => 21,'nam'=>$article['codice']),
                          array('lun' => 18,'nam'=>$article['catmer']),
                          array('lun' => 60,'nam'=>$article['descri']),
                          array('lun' => 10,'nam'=>$article['unimis']),
                          array('lun' => 18,'nam'=>number_format($article['scorta'],1,',',''))
                          );
$pdf = new report_Template();
$pdf->setVars($admin_aziend);
$config = new Config;
$pdf->setAuthor($admin_aziend['ragso1'].' '.$_SESSION["user_name"]);
$pdf->setTitle('Stampa codici a barre');
$pdf->SetTopMargin(40);
if (empty($article['image'])){
   $pdf->setItemGroup($item_head);
   $n=4;
} else {
   $pdf->setItemGroup($item_head,$article['image'],$article['web_url']);
   $n=3;
}
$pdf->AddPage();
$pdf->SetFont('helvetica','',9);
$pdf->Ln(2);
$x=10 ;
$y = $pdf->GetY();
for ($m = 0; $m < 10; $m++) {
   for ($i = 0; $i < $n; $i++) {
        if ($article['barcode'] > 0){
            $pdf->EAN13($x+($i*49),$y+($m*22),$article['barcode'],12);
        } else {
            $pdf->text($x+($i*49),$y+($m*22),'SENZA BARCODE EAN13');
        }
     }
   $n=4;
}
$pdf->Output();
?>