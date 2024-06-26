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

$strScript = array(
    "admin_assist.php" =>
    array('title' => 'Gestione Assistenze Tecniche',
        'ins_this' => 'Inserimento assistenza tecnica',
        'upd_this' => 'Modifica l\'assistenza ',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 2 caratteri!',
            'Cambia anagrafica'),
        'errors' => array('Il codice articolo &egrave; gi&agrave; esistente',
            'Si st&agrave; tentando di modificare il codice ad un articolo con dei movimenti di magazzino associati',
            'Codice di un articolo gi&agrave; esistente',
            'Il file dev\'essere nel formato PNG',
            'L\'immagine non dev\'essere pi&ugrave; grande di 10 kb',
            'Inserire un codice valido',
            'Inserire una descrizione',
            'Inserire l\'unit&agrave; di misura delle vendite',
            'Inserire l\'aliquota I.V.A.'
        ),
        'codice' => "Numero",
        'descrizione' => "Descrizione",
        'cliente' => "Cliente",
        'telefono' => "Telefono",
        'oggetto' => "Oggetto",
        'stato' => "Stato",
        'info_agg' => "Informazioni aggiuntive",
        'tecnico' => "Tecnico"
    ),
    "delete_assist.php" =>
    array('title' => 'Eliminazione Assistenza Tecnica',
        'alert'=>'Sei sicuro di volerlo eliminare?',
        'descrizione' => "Descrizione",
        'cliente' => "Cliente",
        'oggetto' => "Oggetto",
    )
);
?>