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
if (isset($_POST['gohome'])) {
  header("Location: admin.php");
  exit;
}
$r = gaz_dbi_get_row($gTables['admin'],"user_name",$_SESSION["user_name"]);
require("./lang.".$r['lang'].".php");
echo '<form method="post">';
echo '<div class="error_box">';
if (isset($_GET['module'])) {
  echo $errors['access_module'].' <span class="error">'.$_GET['module'].'</span> ---> ';
} else {
  echo $errors['access_script'].' <span class="error">'.$_GET['script'].'.php</span> ';
}
echo ' <button onclick="history.back()">'.$errors['back'].'</button>  ---> <button type="submit" name="gohome">Home page</button>';
echo '</div>';
?>
</form>
