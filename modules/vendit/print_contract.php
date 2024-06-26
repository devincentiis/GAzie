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
if (!isset($_GET['id_contract']) || $_GET['id_contract'] < 1) {
    header("Location: report_contract.php");
    exit;
} else {
    $id= intval($_GET['id_contract']);
}

require("./lang.".$admin_aziend['lang'].".php");
require("../../language/".$admin_aziend['lang']."/menu.inc.php");
$script_transl=$strCommon+$strScript['admin_contract.php'];
$anagrafica = new Anagrafica();

// recupero i dati relativi al contratto
$contract = gaz_dbi_get_row($gTables['contract'],'id_contract' ,$id);
$body = gaz_dbi_get_row($gTables['body_text'],'id_body' ,$contract['id_body_text']);
$rs_rows = gaz_dbi_dyn_query("*", $gTables['contract_row'], "id_contract = $id","id_row ASC");
$customer= $anagrafica->getPartner($contract['id_customer']);
$payment= gaz_dbi_get_row($gTables['pagame'],'codice' ,$contract['payment_method']);
$bank=gaz_dbi_get_row($gTables['banapp'],'codice' ,$contract['bank']);
$bank['descri']=$bank?$bank['descri']:'';
$revenue= gaz_dbi_get_row($gTables['clfoco'],'codice' ,$contract['cod_revenue']);
$vat= gaz_dbi_get_row($gTables['aliiva'],'codice' ,$contract['vat_code']);

if ($contract['tacit_renewal']==0) {
    $tacit_renewal = $script_transl['no'];
} else {
    $tacit_renewal = $script_transl['yes'];
}
if ($contract['periodic_reassessment']==0) {
    $periodic_reassessment = $script_transl['no'];
} else {
    $periodic_reassessment = $script_transl['yes'];
}

class PDF extends TCPDF
    {
    public $transl_page;
    public $transl_of;

    function setLang($lang='italian')
      {
      require("../../language/$lang/menu.inc.php");
      $this->transl_page = $strCommon['page'];
      $this->transl_of = $strCommon['of'];
      }
    function setFoot($foot)
      {
        $this->foot = $foot;
      }
    function Header()
      {
      }
    function Footer()
      {
        if (!isset($this->foot)){
           $this->SetFont('helvetica','',7);
           $this->Cell(186,5,$this->transl_page.$this->getGroupPageNo().$this->transl_of.$this->getPageGroupAlias(),0,0,'C');
        } else {
           $this->Cell(186,5,$this->foot,0,0,'C');
        }
      }

    }

$config = new Config;
$pdf=new PDF();
$pdf->setLang($admin_aziend['lang']);
$pdf->SetTopMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetHeaderMargin(5);
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
$pdf->StartPageGroup();
$pdf->AddPage();
$pdf->writeHtmlCell(186,6,10,$pdf->GetY(),$body['body_text'],0,1);
$pdf->lastPage();
if ($pdf->GetY() > 250){
    $pdf->AddPage();
}
$pdf->Ln(4);
$pdf->Cell(73,4,$admin_aziend['ragso1'].' '.$admin_aziend['ragso2'],0,0,'C');
$pdf->Cell(35,4);
$pdf->Cell(73,4,$customer['ragso1'].' '.$customer['ragso2'],0,1,'C');
$pdf->Cell(73,8,'','B',0,'C');
$pdf->Cell(35,8);
$pdf->Cell(73,8,'','B',1,'C');
$pdf->StartPageGroup();
$pdf->AddPage();
$pdf->Image('@'.$admin_aziend['image'],15,8,20,0);
$pdf->Cell(40,4);
$pdf->SetFont('times','B',12);
$pdf->Cell(130,5,$admin_aziend['ragso1'].' '.$admin_aziend['ragso2'],0,2,'L');
$pdf->SetFont('helvetica','',9);
$pdf->Cell(130,4,$admin_aziend['indspe'].' '.sprintf("%05d",$admin_aziend['capspe']).' '.$admin_aziend['citspe'].' ('.$admin_aziend['prospe'].')',0,2,'L');
$pdf->Cell(130,4,'Tel.'.$admin_aziend['telefo'].' C.F. '.$admin_aziend['codfis'].' P.I. '.$admin_aziend['pariva'],0,2,'L');
$pdf->Cell(130,4,$admin_aziend['e_mail'],0,1,'L');
$pdf->SetFont('helvetica','B',10);
$pdf->Cell(186,4,$script_transl['append'],1,1,'C',1);
$pdf->SetFont('helvetica','',9);
$pdf->Ln(4);
$pdf->Cell(50,4,$script_transl['customer'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$customer['ragso1'].' '.$customer['ragso2'].' - C.F.'.$customer['codfis'],'RTB',1);
$pdf->Cell(50,4,$script_transl['vat_section'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$contract['vat_section'],'RTB',1);
$pdf->Cell(50,4,$script_transl['doc_number'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$contract['doc_number'],'RTB',1);
$pdf->Cell(50,4,$script_transl['conclusion_date'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$contract['conclusion_date'],'RTB',1);
$pdf->Cell(50,4,$script_transl['start_date'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$contract['start_date'],'RTB',1);
$pdf->Cell(50,4,$script_transl['current_fee'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$contract['current_fee'],'RTB',1);
$pdf->Cell(50,4,$script_transl['periodicity'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$script_transl['periodicity_value'][$contract['periodicity']],'RTB',1);
$pdf->Cell(50,4,$script_transl['months_duration'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$contract['months_duration'],'RTB',1);
$pdf->Cell(50,4,$script_transl['tacit_renewal'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$tacit_renewal,'RTB',1);
$pdf->Cell(50,4,$script_transl['periodic_reassessment'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$periodic_reassessment,'RTB',1);
$pdf->Cell(50,4,$script_transl['payment_method'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$payment['descri'],'RTB',1);
$pdf->Cell(50,4,$script_transl['bank'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$bank['descri'],'RTB',1);
$pdf->Cell(50,4,$script_transl['doc_type'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$script_transl['doc_type_value'][$contract['doc_type']],'RTB',1);
$pdf->Cell(50,4,$script_transl['cod_revenue'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$contract['cod_revenue'].' - '.($revenue?$revenue['descri']:''),'RTB',1);
$pdf->Cell(50,4,$script_transl['vat_code'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$contract['vat_code'].' - '.($vat?$vat['descri']:''),'RTB',1);
$pdf->Cell(50,4,$script_transl['initial_fee'],'LTB',0);
$pdf->Cell(11,4,':','TB',0,'R');
$pdf->Cell(125,4,$contract['initial_fee'],'RTB',1);
$pdf->Ln(4);
if (gaz_dbi_num_rows($rs_rows)){
   $pdf->Cell(186,4,$script_transl['rows_title'],0,1,'C');
}
while ($row = gaz_dbi_fetch_array($rs_rows)) {
      $importo = CalcolaImportoRigo($row['quanti'], $row['price'], $row['discount']);
      $pdf->Cell(90,4,$row['descri'],1);
      $pdf->Cell(11,4,$row['unimis'],1,0,'C');
      $pdf->Cell(20,4,gaz_format_quantity($row['quanti'],1,$admin_aziend['decimal_quantity']),1,0,'R');
      $pdf->Cell(25,4,number_format($row['price'],$admin_aziend['decimal_price'],',',''),1,0,'R');
      $pdf->Cell(10,4,gaz_format_quantity($row['discount'],1,9),1,0,'R');
      $pdf->Cell(30,4,number_format($importo,2,',',''),1,1,'R');
}

if (!empty($_GET['dest']) && $_GET['dest'] == 'E') { // è stata richiesta una e-mail
	require("../../library/include/document.php");
	$dest = 'S';     // Genero l'output pdf come stringa binaria
	$doc_name = 'Contratto n.' . $contract['doc_number'] . ' del ' . gaz_format_date($contract['conclusion_date']) . '.pdf';
    $azienda = gaz_dbi_get_row($gTables['aziend'], 'codice', $_SESSION['company_id']);
    $user = gaz_dbi_get_row($gTables['admin'], "user_name", $_SESSION["user_name"]);
	// Costruisco oggetto con tutti i dati del file pdf da allegare
	$content = new StdClass;
	$content->urlfile=false;
	$content->name = $doc_name;
	$content->string = $pdf->Output($doc_name, $dest);
	$content->encoding = "base64";
	$content->mimeType = "application/pdf";
	$gMail = new GAzieMail();
	$gMail->sendMail($azienda, $user, $content, $customer);
} else {
	$pdf->Output();
}

?>
