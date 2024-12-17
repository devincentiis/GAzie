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
$admin_aziend = checkAdmin(9);

$loaderimg='
  <style>
		#loader {
			border: 12px solid #f3f3f3;
			border-radius: 50%;
			border-top: 12px solid #444444;
			width: 70px;
			height: 70px;
			animation: spin 1s linear infinite;
		}
		@keyframes spin {
			100% {
				transform: rotate(360deg);
			}
		}
		.center {
			position: absolute;
			top: 0;
			bottom: 0;
			left: 0;
			right: 0;
			margin: auto;
		}
	</style>
';

if (isset($_POST['hidden_req'])) { // accessi successivi allo script
  $form['hidden_req'] = $_POST["hidden_req"];
  $form['ritorno'] = $_POST['ritorno'];
} else {  // al primo accesso allo script
  $form['hidden_req'] = '';
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
}
if (isset($_POST['return'])) {
  header("Location: " . $form['ritorno']);
  exit;
}
if (isset($_GET['okexternal'])) { // è pronto il backup esterno lo invio sul browser
  // Impostazione degli header per l'opzione "save as"
  header("Pragma: no-cache");
  header("Expires: 0");
  header("Content-Type: application/octet-stream");
  header("Content-Length: ".filesize(DATA_DIR.'files/tmp/bckupext.zip'));
  header("Content-Disposition: attachment; filename=\"".$Database . '-' . date("YmdHi") . '-v' . GAZIE_VERSION.".zip\"");
  readfile(DATA_DIR.'files/tmp/bckupext.zip');
  unlink(DATA_DIR.'files/tmp/bckupext.zip');
  // aggiorno la data dell'ultimo backup
  gaz_dbi_put_row($gTables['config'], 'variable', 'last_backup', 'cvalue', date('Y-m-d'));
} elseif (isset($_GET['external'])) { // propongo il backup esterno preparo il file zip temporaneo
  require("../../library/include/header.php");
  $script_transl = HeadMain();
  echo $loaderimg;
  ?>
  <script>
  $(function() {
    $('.okext').click(function() {
      $('#loader').toggle();
      $('#btnrow').toggle();
      var tp = $(this).attr('tp');
      $.ajax({
        data: {'type':tp},
        type: 'GET',
        url: '../inform/ajax.php',
        success: function(output) {
          $('#loader').toggle();
          alert('Backup pronto, scaricalo');
          window.location.replace("../inform/backup.php?okexternal");
        }
      });
    });
  });
  </script>
  <div class="text-center text-warning bg-warning row" id="btnrow">
    <div class="col-md-2"></div>
    <div class="col-md-3"><a class="btn btn-md btn-warning text-bold okext" tp="bckextyed">Compresi i dati (data/files)</a></div>
    <div class="col-md-2"></div>
    <div class="col-md-3"><a class="btn btn-md btn-warning text-bold okext" tp="bckextnod">Senza data/files</a></div>
    <div class="col-md-2"></div>
  </div>
  <div id="loader" class="center" style="display:none"></div>
  <?php
  require('../../library/include/footer.php');
} else { // è richiesto un backup interno (su GAzie)
  require("../../library/include/header.php");
  $script_transl = HeadMain();
  echo $loaderimg;
  ?>
  <script>
    $.ajax({
      data: {'type':'save'},
      type: 'GET',
      url: '../inform/ajax.php',
      success: function(output) {
        alert('Backup terminato');
        window.location.replace("../inform/report_backup.php");
      }
    });
  </script>
  <h1 class="text-center text-warning bg-warning">Attendi la fine del backup interno (sul server web) <h1>
  <div id="loader" class="center"></div>
  <?php
  require('../../library/include/footer.php');
}
?>
