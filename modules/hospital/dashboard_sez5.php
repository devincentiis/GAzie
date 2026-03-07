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
if (!isset($_SESSION['id_patient'])) {
  header("Location: select_patient.php");
  exit;
} else {
  require_once("./lib.data.php");
  $patient=DecryptPersonalData($gTables['encrypted_personal_data'],'id_patient_bidx',intval($_SESSION['id_patient']))[0];
  preg_match_all('/(?<=\b)\w/iu',$patient['last_name'],$matches);
  $patient_redname=$patient['first_name'].' '.implode('.',$matches[0]).'.';
}

require("../../library/include/header.php");
$script_transl=HeadMain();
?>
<div class="panel col-xs-12">
  <div class="row text-center col-xs-12">
  sezione 5
  </div>
</div>
</div>
<?php
require("../../library/include/footer.php");
?>
