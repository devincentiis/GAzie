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
//Set the Content Type
header('Content-type: image/png');
if (isset($_GET['id'])) {
  $rs_img=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(content),'".$_SESSION['aes_key']."') AS content FROM ".$gTables['files']." WHERE id_doc = " .intval($_GET['id']));
  $rimg = gaz_dbi_fetch_array($rs_img);
  if ($rimg) { // ho la firma
    $img = imagecreatefromstring(hex2bin($rimg['content']));
    $w = imagesx($img);
    $h = imagesy($img);
    $backgrd = imagecreatetruecolor($w,$h);
    imagefill($backgrd,0,0,imagecolorallocate($backgrd,255,255,255));
    imagecopy($backgrd,$img,0,0,0,0,$w,$h);
    imagedestroy($img);
  }
}
// Send Image to Browser
imagepng($backgrd);
// Clear Memory
imagedestroy($backgrd);
?>
