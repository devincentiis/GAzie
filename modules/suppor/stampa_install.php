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
//require("../../library/include/ptemplate.inc.php");
$admin_aziend=checkAdmin();
$title = "";
require("lang.".$admin_aziend['lang'].".php");
if ( !isset($_GET['id'])) {
    header("Location: report_install.php");
    exit;
}
require("../../config/templates/report_template.php");

if ( isset($_GET['id']) ){
   $sql = $gTables['instal'].'.id = '.intval($_GET['id']).' ';
} else {
   $sql = $gTables['instal'].'.id > 0 ';
}
$where = $sql;

$file = "../../config/templates/report_install.php";

if ( file_exists($file) ) {
   $result = gaz_dbi_dyn_query($gTables['instal'].".*,
		".$gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2, ".$gTables['anagra'].".indspe, 
      ".$gTables['anagra'].".capspe, ".$gTables['anagra'].".telefo, ".$gTables['anagra'].".cell, 
      ".$gTables['anagra'].".citspe, ".$gTables['anagra'].".prospe, ".$gTables['anagra'].".fax,
      ".$gTables['clfoco'].".codice ",  $gTables['instal'].
		" LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['instal'].".clfoco = ".$gTables['clfoco'].".codice". 
		" LEFT JOIN ".$gTables['anagra'].' ON '.$gTables['clfoco'].'.id_anagra = '.$gTables['anagra'].'.id',
		$where, "id", $limit, $passo);
		
   $pdf = new Report_template();
   $pdf->setVars($admin_aziend,$title);
   $pdf->SetTopMargin(32);
   $pdf->SetFooterMargin(20);
   $config = new Config;
   $pdf->AddPage('P',$config->getValue('page_format'));
   $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
   $row   = gaz_dbi_fetch_array($result);
   $html = file_get_contents( $file );
   
   //cerca i tag per compilare le variabili
   $var = "row";
   $content = getTextBetweenTags($var, $html);
   foreach( $content as $item )
   {     
      $html = str_replace ( "<".$var.">".$item."</".$var.">", $row[$item], $html );
   }
   
   $var = "admin_aziend";
   $content = getTextBetweenTags($var, $html);
   foreach( $content as $item )
   {
      $html = str_replace ( "<".$var.">".$item."</".$var.">", $admin_aziend[$item], $html );
   }
   
   
   $pdf->writeHTMLCell(0, 20, '', '', $html, 0, 1, 0, true, '', true);
   $pdf->Output();
} else {
   header("Location: report_period.php");
}
?>