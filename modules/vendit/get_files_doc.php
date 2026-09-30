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
if(isset($_GET['id_doc']))  { // crea immagine dal campo BLOB della tabella gaz_001files per riprendere i documenti criptati con $_SESSION['aes_key']
  $d=intval($_GET['id_doc']);
  $rsdoc=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(content),'".$_SESSION['aes_key']."') AS content, title, extension, adminid  FROM ".$gTables['files']." WHERE id_doc = ".$d);
  $doc=gaz_dbi_fetch_row($rsdoc);
  $content=hex2bin($doc[0]);
  $mime=strtolower($doc[2]);
  // se non è un file dell'utente e l'utente non è amministratore non consento la visualizzazione
  if($_SESSION['Abilit'] < 8 && $_SESSION['user_name']!=$doc[3]){
    $mime='txt';
    $content='NON PUOI VISUALIZZARE QUESTO DOCUMENTO IN QUANTO NON SEI AMMINISTRATORE E NON E\' STATO CARICATO DA TE';
  }
  switch ($mime) {
    case 'pdf':
      header('Content-type: application/pdf');
    break;
    case 'jpg':
      header ('Content-type: image/jpg');
    break;
    case 'png':
      header ('Content-type: image/png');
    break;
    case 'gif':
      header ('Content-type: image/gif');
    break;
    default:
      header ('Content-type: text/html; charset=utf-8');
    break;
  }
  header("Content-Disposition:inline;filename=".$doc[1].'.'.$doc[2]);
  header('Content-Length: '.strlen( $content ));
  header('Cache-Control: public, must-revalidate, max-age=0');
  header('Pragma: public');
  header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
  header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
  echo $content;
}
?>
