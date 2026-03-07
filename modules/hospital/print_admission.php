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
$admin_aziend = checkAdmin();

if (strlen($_GET['dp'])<4){
  exit;
} else {
  require_once("./lib.data.php");
  require_once("./lang.italian.php");
  $tesbro = gaz_dbi_get_row($gTables['tesbro'], 'id_tes', intval($_GET['id']));
  $patient=DecryptPersonalData($gTables['encrypted_personal_data'],'id_patient_bidx',$tesbro['id_con'])[0]; // l'id_patient l'ho in id_con di tesbro dell'ammissione
  // riprendo i valori del tutor criptati su custom_field
  $rs_cf=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(custom_field),'".$_SESSION['aes_key']."') AS custom_field FROM ".$gTables['tesbro']." WHERE id_tes = ".$tesbro['id_tes']);
  $doc=gaz_dbi_fetch_array($rs_cf);
  $cf_array=json_decode(hex2bin($doc['custom_field']),true);
  foreach ($cf_array['tutor'] as $k=>$v ) { // rivalorizzo con gli indici di immissione
    $patient['tutor_'.$k]=$v;
  }
}

use setasign\Fpdi\Tcpdf\Fpdi;

class TemplateProgettazione extends Fpdi {
  public $numPages;
  public $_tplIdx;

  function clearHeadFoot() {
    $this->print_header = false;
    $this->print_footer = false;
  }
}
$psw=substr($_GET['dp'],0,4);
$pdf = new TemplateProgettazione();
$pdf->SetProtection(['print', 'copy','modify'],$psw,'1233',0,null);
$pdf->setCreator($admin_aziend['ragso1'].' '.$admin_aziend['ragso2']);
$pdf->setAuthor($admin_aziend['user_firstname'].' '.$admin_aziend['user_lastname']);
$pdf->setTitle('Ammissione');
$pdf->SetSubject('ID');
$pdf->numPages = $pdf->setSourceFile('./templates/TemplateAmmissioneDimissione.pdf');
if ($patient['sexper']=='F'){
  $admindes='è stata ammessa';
  if ($tesbro['tipdoc']=='HDI'){
    $admindes='viene dimessa';
  }
  $titpers='la Signora';
  $dborn='nata il';
} else {
  $admindes='è stato ammesso';
  if ($tesbro['tipdoc']=='HDI'){
    $admindes='viene dimesso';
  }
  $titpers='il Signor';
  $dborn='nato il';
}
$regime=strtolower($strScript['admin_admission.php']['regime_value'][$tesbro['tipdoc_buf']]);

if ($pdf->numPages >= 1) {
  for ($i = 1; $i <= $pdf->numPages; $i++) {
    $pdf->_tplIdx = $pdf->importPage($i);
    $specs = $pdf->getTemplateSize($pdf->_tplIdx);
    // stabilisco se portrait-landscape
    if ($specs['height'] > $specs['width']){ //portrait
      $pl='P';
      $w=210;
      $h=297;
    } else { //landscape
      $pl='L';
      $w=297;
      $h=210;
    }
    $pdf->clearHeadFoot();
    $pdf->AddPage($pl);
    $pdf->useTemplate($pdf->_tplIdx,NULL,NULL,$w,$h, FALSE);
    $pdf->SetFont('times','',10);
    $pdf->SetXY(157,48);
    $pdf->Cell(30,5,gaz_format_date($tesbro['datemi']),0,1,'L',0,'',1);
    $pdf->SetXY(120,98);
    $pdf->Cell(70,5,$admin_aziend['citspe'].' ('.$admin_aziend['prospe'].') - '.$admin_aziend['ragso1'].' '.$admin_aziend['ragso2'] ,0,0,'L',0,'',1);
    $pdf->SetFont('times','B',10);
    $pdf->SetXY(35,108);
    if ($tesbro['tipdoc']=='HAD') {
      $pdf->Cell(120,5,'Ammissione paziente in regime '.$regime.'.',0,0,'L',0,'',1);
    } elseif ($tesbro['tipdoc']=='HDI'){
      $pdf->Cell(120,5,'Dimissione paziente dal regime '.$regime.'.',0,0,'L',0,'',1);
    }
    $pdf->SetXY(70,118);
    $pdf->SetFont('times','',10);
    $pdf->Cell(65,5,gaz_format_date($tesbro['datemi']),0,0,'L',0,'',1);
    $pdf->SetFont('times','B',10);
    $pdf->Cell(70,5,$admindes,0,0,'L',0,'',1);
    $pdf->SetFont('times','',10);
    $pdf->SetXY(18,133);
    if ($tesbro['tipdoc']=='HAD') {
      $pdf->Cell(64,5,'in regime '.$regime.' della Struttura di ' ,0,0,'C',0,'',4);
    } elseif ($tesbro['tipdoc']=='HDI'){
      $pdf->Cell(64,5,'dal regime '.$regime.' della Struttura di ' ,0,0,'C',0,'',4);
    }
    $pdf->Cell(126,5,$admin_aziend['citspe'].' ('.$admin_aziend['prospe'].') - '.$admin_aziend['ragso1'].' '.$admin_aziend['ragso2'] ,0,0,'L',0,'',1);
    $pdf->SetXY(18,143);
    $pdf->Cell(20,5,$titpers,0,0,'L',0,'',1);
    $pdf->Cell(5);
    $pdf->Cell(130,5,$patient['first_name'].' '.$patient['last_name'] ,0,0,'L',0,'',1);
    $pdf->SetXY(18,153);
    $pdf->Cell(20,5,$dborn,0,0,'L',0,'',1);
    $pdf->Cell(5);
    $pdf->Cell(85,5,gaz_format_date($patient['birth_date']),0,0,'L',0,'',1);
    $pdf->Cell(65,5,$patient['birth_place'].' ('.$patient['birth_prov_code'].')' ,0,0,'L',0,'',1);
  }
}
// aggiorno il database per tracciare la stampa
$sn=basename($_SERVER['REQUEST_URI']);
gaz_dbi_query("INSERT INTO ".$gTables['hospital_pdflog']." (url,adminid) VALUES ('".$sn."', '".$_SESSION['user_name']."' )");
$pdf->Output($admin_aziend['ragso1'].'_'.$admin_aziend['ragso2'].'_AmmissioneOspite.pdf');
?>
