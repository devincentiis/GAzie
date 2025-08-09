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

/* Questo script serve per inviare i dati ad un'altro che provvede alla generazione
   del PDF senza che al momento del ritorno indietro (back button) si abbia la richiesta
   da parte del browser di ripostare i dati.
   Vengono sfruttati i registri $_SESSION['print_request'] e $_SESSION['script_ref'];
   $_SESSION['print_request'] è bidimensionale e al suo interno sono contenuti i dati
   da passare tramite URL allo script il cui nome è contenuto in ['script_name'],
   nelle altre key si devono passare il nome della variabile (nella key) ed il suo valore.
*/

if (isset($_SESSION['print_request'])){
  $request = $_SESSION['print_request'];
  unset ($_SESSION['print_request']);
  if (isset($request['script_name'])) { // se viene stata inviata una richiesta di stampa con il nome del template
    //formattazione l'url
    $url="setTimeout(\"window.location='".$request['script_name'].".php?";
    unset($request['script_name']);
    foreach($request as $k=>$v){
      if ($v!=null || is_numeric($v)|| empty($v)) $url .=$k.'='.preg_replace("/\'/",'`',$v).'&';
    }
    $url .="'\",500)\n";
    //fine formattazione url
    echo "<HTML><HEAD><TITLE>Wait for PDF</TITLE>\n";
    echo "<script type=\"text/javascript\">\n";
    echo $url;
    echo "</script></HEAD>\n<BODY><DIV align=\"center\">Wait for PDF</DIV><DIV align=\"center\">Aspetta il PDF</DIV></BODY></HTML>";
  } else {  //altrimenti torno indietro
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit;
  }
} else {
  $ref=$_SERVER['HTTP_REFERER'];
  if (isset($_SESSION['script_ref'])) {
      $ref = $_SESSION['script_ref'];
      unset ($_SESSION['script_ref']);
  }
  header("Location: ".$ref);
  exit;
}
?>
