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
require_once("./lib.data.php");
$admin_aziend = checkAdmin();
$r=DecryptPersonalData($gTables['encrypted_personal_data'],'id_patient_bidx',intval($_SESSION['id_patient']))[0];
$rs_asl = gaz_dbi_dyn_query("*",$gTables['asl'].' LEFT JOIN '.$gTables['regions'].' ON '.$gTables['asl'].'.regione = '.$gTables['regions'].'.id',$gTables['asl'].'.id_asl ='.$r['affiliated_health_company'], $gTables['asl'].'.id_asl', 0, 1);
$rasl = gaz_dbi_fetch_array($rs_asl);
//Set the Content Type
header('Content-type: image/png');
if (isset($_GET['back'])){
  // Create Image From Existing File
  $pngimg = imagecreatefrompng('ts_back.png');
  // Allocate A Color For The Text
  $black = imagecolorallocate($pngimg,0,0,0);
  $white = imagecolorallocate($pngimg,255,255,255);
  // Set Path to Font File
  $font = 'SansB.ttf';
  // Print Text On Image
  imagettftext($pngimg,20,0,40,345, $black, $font, $r['last_name']);
  imagettftext($pngimg,20,0,40,405, $black, $font, $r['first_name']);
  imagettftext($pngimg,20,0,740,405, $black, $font, $r['birth_date']);
  imagettftext($pngimg,20,0,40,465, $black, $font, $r['tax_code']);
  if ($rasl) { // ho la asl di appartenenza
    imagettftext($pngimg,18,0,450,463, $black, $font, 'ASL '.$rasl['denominazione'].' '.$rasl['codice']);
  }
  imagettftext($pngimg,20,0,40,525, $black, $font, $r['health_card_number']);
  imagettftext($pngimg,20,0,740,525, $black, $font, $r['doc_expiry']);

} else { // front
  // Create Image From Existing File
  $pngimg = imagecreatefrompng('ts_front.png');
  // Allocate A Color For The Text
  $black = imagecolorallocate($pngimg,0,0,0);
  $white = imagecolorallocate($pngimg,255,255,255);
  // Set Path to Font File
  $font = 'SansB.ttf';
  // Print Text On Image
  imagettftext($pngimg,24,0,380,210, $black, $font, $r['tax_code']);
  imagettftext($pngimg,20,0,10,435, $black, $font, $r['doc_expiry']);
  imagettftext($pngimg,24,0,828,207, $black, $font, $r['sexper']);
  imagettftext($pngimg,20,0,380,285, $black, $font, $r['last_name']);
  imagettftext($pngimg,20,0,380,325, $black, $font, $r['first_name']);
  imagettftext($pngimg,20,0,380,395, $black, $font, $r['birth_place']);
  imagettftext($pngimg,20,0,380,440, $black, $font, $r['birth_prov_code']);
  imagettftext($pngimg,20,0,380,515, $black, $font, $r['birth_date']);
  if ($rasl) { // ho la asl di appartenenza
    imagettftext($pngimg,14,0,560,455, $black, $font, $rasl['denominazione']);
    imagettftext($pngimg,14,0,615,485, $black, $font, $rasl['name']);
    imagettftext($pngimg,20,0,645,520, $black, $font, $rasl['codice']);
  }
  $rs_img=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(content),'".$_SESSION['aes_key']."') AS content, status, extension, adminid  FROM ".$gTables['files']." WHERE id_ref = " .intval($_SESSION['id_patient']). " AND table_name_ref = 'patient_imgavatar' ORDER BY id_doc");
  $rimg = gaz_dbi_fetch_array($rs_img);
  if ($rimg) { // ho una immagine
    $avaimg = imagecreatefromstring(hex2bin($rimg['content']));
    $w = imagesx($avaimg);
    $h = imagesy($avaimg);
    $m=max($w,$h);
    $backgrd = imagecreatetruecolor($m,$m);
    imagefill($backgrd,0,0,imagecolorallocate($backgrd,255,255,255));
    imagecopy($backgrd,$avaimg,0,0,0,0,$w,$h);
    $avaimg = imagescale($backgrd,-1,50);
    imagefilter($avaimg, IMG_FILTER_GRAYSCALE);
    imagefilter($avaimg, IMG_FILTER_COLORIZE,30,80,180,0);
    imagecopymerge($pngimg, $avaimg,560,460,0,0,50,50,70);
    imagedestroy($avaimg);
    imagedestroy($backgrd);
  }
}
// Send Image to Browser
imagepng($pngimg);
// Clear Memory
imagedestroy($pngimg);
?>
