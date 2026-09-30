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
$admin_aziend = checkAdmin();
require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<div class="panel panel-info">
  <div>
    <h2>
      <div class="text-center">
        <img src="lims.png"/>
      </div>
      <ul>
        <li>Il modulo <b>LIMS</b> è sviluppato in base alle specifiche necessità dei laboratori. </li>
        <li>Possono essere realizzati collegamenti ad-hoc con gli strumenti di analisi ed i loro software di gestione, i dispositivi di campionamento, la sensoristica.</li>
        <li>Potranno essere generati i verbali di campionamento, le accettazioni dei campioni, i rapporti di prova, le metodiche, i reports e le verifiche strumentali messe in essere.</li>
        <li>Verranno ricordate le scadenze delle tarature/calibrature e degli accreditamenti.</li>
      </ul>
      <hr/>
      <p class="text-danger"> Se vuoi creare un sistema informativo su misura per il tuo laboratorio o per qualsiasi altro chiarimento contatta l'autore:</p>
      <p class="text-warning text-center">Antonio De Vincentiis Montesilvano (PE)</p>
      <p class="text-center"><a href="https://www.devincentiis.it"> https://www.devincentiis.it </a></p>
      <p class="text-center">Telefono +39 <a href="tel:+393383121161">3383121161</a></p>
    </h2>
  </div>
</div>
<?php
require("../../library/include/footer.php");
?>
