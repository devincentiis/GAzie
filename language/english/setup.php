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
$errors                  = Array();
$errors['is_align']      = "Database is aligned with last version";
$errors['no_conn']       = "The database connection is not successful.<br /> Correctly set username, password and database name <br />in the config/config/gconfig.php file";
$errors['no_data_files_writable']  = "The server does not have permission (min. 666) to store the documents in the directory " . DATA_DIR . "files";
$errors['no_tcpdf_cache_writable'] = "TCPDF can't generate PDF files without required permission (min. 666)  for " . K_PATH_CACHE;

$msg                     = Array();
$msg['title']            = "Install or Upgrade GAzie Database";
$msg['install']          = "Install";
$msg['upgrade']          = "Upgrade";
$msg['error']            = "Error";
$msg['gi_install']       = "Install Database of ";
$msg['gi_upgrade']       = "Upgrade Database of ";
$msg['gi_upg_to']        = "to";
$msg['gi_upg_from']      = "from version";
$msg['gi_lang']          = "Selection language";
$msg['gi_error']         = "";
$msg['gi_is_align']      = "Click here for Login";
$msg['gi_usr_psw']       = "User = amministratore <br />Password = password";
?>