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

$strScript = array(
    "select_schedule.php" =>
    array('title' => 'Selezione delle fatture da mandare in compensazione',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 2 caratteri!',
            'Cambia cliente/fornitore'
        ),
        'errors' => array('La data  non &egrave; corretta!',
            'Non sono stati trovati movimenti!'
        ),
        'confirm_entry' => 'Invia a Camera di Compensazione',
        'tutti' => 'Seleziona tutte ',
        'precisazione' => 'Puoi anche usare direttamente <a href="https://fe.cameracompensazione.it" target="_blank">Camera di Compensazione</a>',
        'accettazione' => ' <b>Per continuare</b> devi accettare <a href="https://webapp.cameracompensazione.it/termini-condizioni.html" target="_blank">Termini e condizioni</a> e <a href="https://webapp.cameracompensazione.it/privacy-policy.html" target="_blank">Privacy Policy</a> ',
        'account' => 'Cliente ',
        'orderby' => 'Ordina per: ',
        'orderby_value' => array(0 => 'Scadenza crescente', 1 => 'Scadenza decrescente',
            2 => 'Controparte crescente', 3 => 'Controparte decrescente'
        ),
        /** ENRICO FEDELE */
        /* Aggiunto header per ultima colonna della tabella, per abbellire il layout */
        'header' => array('Controparte' => '', 'Seleziona'=> '', 'ID Partita' => '', 'Status' => '', 'Mov.Cont.' => '', 'Descrizione' => '',
            'N.Doc.' => '', 'Data Doc.' => '', 'Data Reg.' => '', 'Dare' => '', 'Avere' => '',
            'Scadenza' => ''
        ),
        /** ENRICO FEDELE */
        'status_value' => array(0 => 'APERTA', 1 => 'CHIUSA', 2 => 'ESPOSTA', 3 => 'SCADUTA', 9 => 'ANTICIPO'),
        'total_open' => 'Totale partite aperte'
    )
);
?>