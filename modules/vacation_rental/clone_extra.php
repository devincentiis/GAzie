<?php

/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (http://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------

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
  scriva   alla   Free  Software Foundation,  Inc.,   59
  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
require("../../library/include/datlib.inc.php");

$admin_aziend = checkAdmin();


if (!isset($_POST['ritorno'])) {
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
} else {
    $form['ritorno'] = $_POST['ritorno'];
}

if (isset($_GET['codice']) and ! isset($_POST['back'])) {
    $new_codice = substr($_GET['codice'], 0, 30) . '_2';
    // controllo che l'articolo con il codice creato automaticamente non esista già
    $rs_articolo = gaz_dbi_dyn_query('codice', $gTables['artico'], "codice = '" . $new_codice . "'", "codice DESC", 0, 1);
    $risultato = gaz_dbi_fetch_array($rs_articolo);
    if ($risultato) {
        require("../../library/include/header.php");
        $script_transl = HeadMain();
        echo "<form method=\"POST\">\n";
        echo "<input type=\"hidden\" value=\"" . $form['ritorno'] . "\" name=\"ritorno\">\n";
        echo "<div>Codice(" . $new_codice . ") esistente usare l'apposita procedura se lo si vuole modificare ! \n";
        echo "<input type=\"submit\" value=\"Torna indietro\" name=\"back\"></div>\n";
        echo "</form>\n";
        require("../../library/include/footer.php");
    } else { // se non esiste posso duplicare
        $originalArtico = gaz_dbi_get_row($gTables['artico'], 'codice', substr($_GET['codice'], 0, 32)); //prelevo l'originale
        $originalRentaExtra = gaz_dbi_get_row($gTables['rental_extra'], 'codart', substr($_GET['codice'], 0, 32)); //prelevo l'originale
        $originalRentaExtra['codart'] = $new_codice;
        $id_extra=gaz_dbi_table_insert('rental_extra', $originalRentaExtra);

        //$data = json_decode($originalArtico['custom_field'],true);// trasformo il json custom_field in array. NB: al momento questa riga non serve ma potrà servire in futuro qualora il custom field contenesse altri dati
        $array= array('vacation_rental'=>array('extra' => $id_extra));// creo l'array per il custom field
        $originalArtico['custom_field'] = json_encode($array);// codifico in json  e lo inserisco nel form

        $originalArtico['codice'] = $new_codice;
        gaz_dbi_table_insert('artico', $originalArtico);

        header("Location: admin_extra.php?codice=" . $new_codice . "&Update");
        exit;
    }
} else {
    header("Location: " . $form['ritorno']);
    exit;
}


