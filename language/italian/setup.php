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
$errors['is_align']      = "Il database &egrave; allineato con il software";
$errors['no_conn']       = "La connessione al database non &egrave; andata a buon fine.<br/>Impostare correttamente username, password <br/>e nome del database nel file config/config/gconfig.php";
$errors['no_data_files_writable']  = "Il server web non ha i permessi (visibili in alto a sinistra) di scrittura per la directory " . DATA_DIR . "files, sono necessari per caricare e memorizzazione documenti e immagini";
$errors['no_tcpdf_cache_writable'] = "Il server web non ha i permessi (visibili in alto a sinistra) di scrittura per la directory " . K_PATH_CACHE.", essi consentono a TCPDF di generare i file pdf";

$msg                     = Array();
$msg['title']            = "Installa o Aggiorna la Base Dati di GAzie ";
$msg['install']          = "Installa";
$msg['upgrade']          = "Aggiorna";
$msg['error']            = "Errore";
$msg['gi_install']       = "Installazione Base Dati di ";
$msg['gi_upgrade']       = "Aggiornamento Base Dati di ";
$msg['gi_upg_to']        = "alla versione";
$msg['gi_upg_from']      = "dalla versione";
$msg['gi_lang']          = "Seleziona lingua";
$msg['gi_error']         = "";
$msg['gi_is_align']      = "Clicca qui per entrare";
$msg['gi_usr_psw']       = "User = amministratore <br />Password = password";
?>