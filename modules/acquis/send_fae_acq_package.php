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

if (isset($_GET['fn'])) { 
	$gMail = new GAzieMail();
    $user = gaz_dbi_get_row($gTables['admin'], "user_name", $_SESSION["user_name"]);
   
	$d=filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$file_url = DATA_DIR."files/tmp/".substr($d['fn'],0,43);	
    
    $content = new StdClass;
    $content->name = substr($d['fn'],0,43);
    $content->urlfile = $file_url; // se passo l'url GAzieMail allega un file del file system e non da stringa
    $dest_fae_zip_package['e_mail'] = gaz_dbi_get_row($gTables['company_config'], 'var', 'dest_fae_zip_package')['val']; 
		
    if (strlen($dest_fae_zip_package['e_mail'])>4) {       
        if ($gMail->sendMail($admin_aziend, $user, $content, $dest_fae_zip_package,'',false)){            
            echo "<p>INVIO FATTURE ELETTRONICHE RIUSCITO!!!</p>";
        }
    } else{
		echo "Invio impossibile manca indirizzo e-mail";
	}
	// delete file tmp
	unlink($file_url);    
}
?>