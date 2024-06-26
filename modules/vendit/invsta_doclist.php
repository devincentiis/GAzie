<?php

/*
  -----------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
  (http://www.devincentiis.it)
  <http://gazie.sourceforge.net>
  -----------------------------------------------------------------------
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
  -----------------------------------------------------------------------
 */
require("../../library/include/datlib.inc.php");

$admin_aziend = checkAdmin();
if (isset($_SESSION['print_request'])) {
   $id_tes = $_SESSION['print_request'];
   unset($_SESSION['print_request']);
   if (is_array($id_tes)) { // l'array deve contenere i limiti per la stampa multipla
      // si deve discernere i documenti singoli da quelli ottenuti
      // da pi� testate come le fatture differite (FAD-DDT)
      echo "<HTML><HEAD><TITLE>Wait for PDF</TITLE>\n";
      echo "<script type=\"text/javascript\">\n";
      $_SESSION['script_ref'] = $_SERVER['HTTP_REFERER'];
      $url = "setTimeout(\"window.location='stampa_doclist.php?td=" . $id_tes['tipdoc'] . "&si=" . $id_tes['seziva'] . "&cl=" . $id_tes['codcli'] .
              "&di=" . $id_tes['datini'] . "&df=" . $id_tes['datfin'] .
              "&pi=" . $id_tes['proini'] . "&pf=" . $id_tes['profin'] .
              "&ni=" . $id_tes['numini'] . "&nf=" . $id_tes['numfin'] .
              "&ag=" . $id_tes['id_agente'] .
              "&ti=" . $id_tes['titolo'] .
              "'\",1000)\n";
      echo $url;
      echo "</script></HEAD>\n<BODY><DIV align=\"center\">Wait for PDF</DIV><DIV align=\"center\">Aspetta il PDF</DIV></BODY></HTML>";
   } else {
      header("Location:select_docforlist.php");
      exit;
   }
} else {
   $locazione = 'select_docforlist.php';
   if (isset($_SESSION['script_ref'])) {
      $locazione = $_SESSION['script_ref'];
      unset($_SESSION['script_ref']);
   }
   header("Location: " . $locazione);
   exit;
}
?>