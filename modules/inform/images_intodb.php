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
    scriva   alla   Free  Software Foundation,  Inc.,   59
    Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
 --------------------------------------------------------------------------
*/
require("../../library/include/datlib.inc.php");

$admin_aziend=checkAdmin();
$fileArray = Array();
$relativePath = '../../images';

function imm_to_db($file,$table,$value,$field, $table2view)
{
  //Load an image in a global variable
  $image=addslashes(file_get_contents("../../library/images/".$file));
  //Put in to database
  gaz_dbi_put_row($table, $field, $value,'image',$image);
  // It's good to see what we put in db :-)
  print '<img src="../../library/include/view.php?table='.$table2view.'&value='.$value.'" border="0"><br />';
}


if ($handle = opendir($relativePath)) {
    while ($file = readdir($handle)) {
        //print $file;
        if(($file == ".") or ($file == "..")) continue;
        if (preg_match("/logo\.jpg/",$file,$regs)){ //se è l'immagine del logo lo inserisco nella tabella aziend
           imm_to_db ($regs[0],$gTables['aziend'],'1','codice', 'aziend');
           print $regs[0]." inserito nella tabella configurazione azienda <br />";
        } elseif (preg_match("/^art([^\w]{1,15})\.jpg/",$file,$regs)){ //... se è l'immagine di un'articolo in artico
           imm_to_db ($regs[0],$gTables['artico'],$regs[1],'codice', 'artico');
           print $regs[0]." inserito nella tabella articoli <br />";
        } elseif (preg_match("/^catmer([0-9]{1,3})\.jpg/",$file,$regs)){ //... se è l'immagine di una categoria merceologica in catmer
           imm_to_db ($regs[0],$gTables['catmer'],$regs[1],'codice', 'catmer');
           print $regs[0]." inserito nella tabella categorie merceologiche <br />";
        } elseif (preg_match("/^UTE([^\w]{1,20})\.jpg/",$file,$regs)){ //... se è l'immagine di un utente in admin
           imm_to_db ($regs[0],$gTables['admin'],$regs[1],"user_name", 'admin');
           print $regs[0]." inserito nella tabella utenti<br />";
        }
    }
    closedir($handle);
}
?>