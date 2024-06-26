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
// $img1px è una stringa equivalente ad una immagine jpg di 1 pixel
// e serve per evitare la visualizzazione dell'immagine di dafault del browser
// quando non la trova
$img1px= pack("c*",0xFF,0xD8,0xFF,0xE0,0x00,0x10,0x4A,0x46,0x49,0x46,0x00,0x01,0x02,
              0x00,0x00,0x64,0x00,0x64,0x00,0x00,0xFF,0xEC,0x00,0x11,0x44,0x75,
              0x63,0x6B,0x79,0x00,0x01,0x00,0x04,0x00,0x00,0x00,0x00,0x00,0x00,
              0xFF,0xEE,0x00,0x0E,0x41,0x64,0x6F,0x62,0x65,0x00,0x64,0xC0,0x00,
              0x00,0x00,0x01,0xFF,0xDB,0x00,0x84,0x00,0x1B,0x1A,0x1A,0x29,0x1D,
              0x29,0x41,0x26,0x26,0x41,0x42,0x2F,0x2F,0x2F,0x42,0x47,0x3F,0x3E,
              0x3E,0x3F,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,
              0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,
              0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,
              0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x01,0x1D,0x29,0x29,0x34,0x26,
              0x34,0x3F,0x28,0x28,0x3F,0x47,0x3F,0x35,0x3F,0x47,0x47,0x47,0x47,
              0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,
              0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,
              0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,0x47,
              0x47,0x47,0x47,0x47,0x47,0x47,0x47,0xFF,0xC0,0x00,0x11,0x08,0x00,
              0x01,0x00,0x01,0x03,0x01,0x22,0x00,0x02,0x11,0x01,0x03,0x11,0x01,
              0xFF,0xC4,0x00,0x4B,0x00,0x01,0x01,0x00,0x00,0x00,0x00,0x00,0x00,
              0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x06,0x01,0x01,0x00,
              0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,
              0x00,0x00,0x10,0x01,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,
              0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x11,0x01,0x00,0x00,0x00,0x00,
              0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0xFF,
              0xDA,0x00,0x0C,0x03,0x01,0x00,0x02,0x11,0x03,0x11,0x00,0x3F,0x00,
              0xA6,0x00,0x1F,0xFF,0xD9);
if (isset($_GET['table']) && isset($_GET['value'])){
  if (isset($_GET['field'])){
    if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {
      $f=filter_var(substr($_GET['field'],0,30),FILTER_SANITIZE_ADD_SLASHES);
    } else {
      $f=addslashes(substr($_GET['field'],0,30));
    }
  } else if (isset($_GET['group'])){
    $f='id_artico_group';
  } else {
    $f='codice';
  }
	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {
		$t=filter_var(substr($_GET['table'],0,30),FILTER_SANITIZE_ADD_SLASHES);
	} else {
		$t=addslashes(substr($_GET['table'],0,30));
	}
  $col = gaz_dbi_get_row($gTables[$t], $f, substr($_GET['value'],0,30));
  header ('Content-type: image/jpeg');
  if (empty($col['image'])) {
    echo $img1px;
  } else {
    $maxsize=1200;
    if (isset($_GET['maxsize']) && intval($_GET['maxsize'])>10){
      $maxsize=intval($_GET['maxsize']);
    }
    $gdImage = imagecreatefromstring($col['image']);
    list($width, $height) = getimagesizefromstring($col['image']);
    $ratio=round($width/$height,2);
    $resize=FALSE;
    if ($width>$maxsize && $ratio >= 1) {
      $toWidth=$maxsize;
      $wdiff=$width-$maxsize;
      $toHeight=intval($height-$wdiff/$ratio);
      $resize=TRUE;
    } elseif ($height>$maxsize && $ratio <= 1) {
      $toHeight=$maxsize;
      $ediff=$height-$maxsize;
      $toWidth=intval($height-$ediff*$ratio);
      $resize=TRUE;
    }
    if ($resize) {
      $gdRender = imagecreatetruecolor($toWidth, $toHeight);
      $colorBgAlpha = imagecolorallocatealpha($gdRender, 0, 0, 0, 127);
      imagecolortransparent($gdRender, $colorBgAlpha);
      imagefill($gdRender, 0, 0, $colorBgAlpha);
      imagecopyresampled($gdRender, $gdImage, 0, 0, 0, 0, $toWidth, $toHeight, $width, $height);
      imagetruecolortopalette($gdRender, false, 255);
      imagesavealpha($gdRender, true);
      ob_start();
      imagepng($gdRender);
      $imageContents = ob_get_contents();
      ob_end_clean();
      imagedestroy($gdRender);
      echo $imageContents;
    } else {
      echo $col['image'];
    }
  }
} elseif(isset($_GET['clfoco']))  { // crea immagine da testo per evitare i copia/incolla del testo
  $anagra = new Anagrafica;
  $i=intval($_GET['clfoco']);
  $data = $anagra->getPartner($i);
  $img = imagecreate(360, 150);
  $textbgcolor = imagecolorallocate($img,0,0,0);
  $textcolor = imagecolorallocate($img,255,255,255);
  imagestring($img, 4, 5, 5, $data['indspe'], $textcolor);
  imagestring($img, 4, 5, 25, $data['capspe'].' '.$data['citspe'].' ('.$data['prospe'].')', $textcolor);
  imagestring($img, 4, 5, 45, 'TEL: '.$data['telefo'].' '.$data['cell'], $textcolor);
  imagestring($img, 4, 5, 65, 'CF: '.$data['codfis'].' '.$data['pariva'], $textcolor);
  imagestring($img, 4, 5, 85, 'mail: '.$data['e_mail'], $textcolor);
  imagestring($img, 4, 5, 105, 'PEC:  '.$data['pec_email'], $textcolor);
  ob_start();
  imagepng($img);
  $imageContents = ob_get_contents();
  ob_end_clean();
  imagedestroy($img);
  header ('Content-type: image/jpeg');
  echo $imageContents;
}
?>
