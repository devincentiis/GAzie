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

echo '<div class="help">';
if (file_exists(HELPDIR.'/'.$admin_aziend['lang']."/docume_stats_help.php")) {
  include(HELPDIR.'/'.$admin_aziend['lang']."/docume_stats_help.php");
}
?>
</div>
