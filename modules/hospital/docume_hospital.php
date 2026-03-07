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
/*
  dati paziente esempio:
  CHGNTN67A27Z404W
  CHIGURH
  ANTON
  STATI UNITI
  EE
  27/01/1967
*/

?>
<div class="panel panel-info">
  <div>
    <h3>
      <div class="text-center">
        <img src="../hospital/hospital.png"/>
      </div>
      <ul>
        <li>Il modulo <b>HOSPITAL</b> oltre che una base per implementare tutte le funzioni di una struttura sanitaria vuole essere un esempio di utilizzo della chiave <small><b>$_SESSION['aeskey']</b></small> per la conservazione dei dati sensibili. Attraverso la preventiva criptazione prima della conservazione sulle tabelle del database delle anagrafiche dei pazienti/ospiti e sui files dei documenti operativi provenienti dall'esterno. Il modulo si prefigge l'obiettivo di prevenire i data breach anche in caso di violazione del database e/o del filesystem, infatti i files conservati dopo l'upload ed il dump del DB sono illeggibili senza la citata chiave. Inoltre anche i documenti in uscita emessi dal modulo, ossia le lettere di ammissioni, dimissioni, cartelle cliniche, referti, ecc saranno tutti file pdf protetti da password OTP, pertanto due documenti identici se generati in tempi diversi avranno OTP diversi. Gli sviluppatori che vogliono approfondire i metodi posso vedere le funzioni presenti sul file lib.data.php. L'utilizzo della chiave <small><b>$_SESSION['aeskey']</b></small>  lo si può estendere anche in caso di sviluppo di un sistema interoperabile come lo standard HL7 CDA 2 (basato su XML).</li>
      </ul>
      <hr/>
      <p class="text-danger"> Se sei interessato alle sue funzionalità o sei uno sviluppatore e vuoi creare un sistema informativo con dati sensibili per la tua attività sanitaria e/o residenziale puoi contattare: </p>
      <p class="text-warning text-center">Antonio De Vincentiis Montesilvano (PE)</p>
      <p class="text-center"><a href="https://www.devincentiis.it"> https://www.devincentiis.it </a></p>
      <p class="text-center">Telefono +39 <a href="tel:+393383121161">3383121161</a></p>
    </h3>
  </div>
</div>
