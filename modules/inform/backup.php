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

if (isset($_POST['hidden_req'])) { // accessi successivi allo script
    $form['hidden_req'] = $_POST["hidden_req"];
    $form['ritorno'] = $_POST['ritorno'];
    $form['do_backup'] = $_POST["do_backup"];
} else {  // al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['do_backup'] = 0;
}

if (isset($_POST['return'])) {
  header("Location: " . $form['ritorno']);
  exit;
}
if (!isset($_GET['automatic']) && (($form['do_backup'] != 1 && isset($_GET['external'])) || isset($_GET['internal']))){ // se non è una richiesta automatica
	require("../../library/include/header.php");
  $script_transl = HeadMain();
	?>
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
	<div id="loader" class="center"></div>
<script>
  document.querySelector("body").style.visibility = "hidden";
  document.querySelector("#loader").style.visibility = "visible";
$(function(){
  $('#remote_submit').on('click',function(e){
//    $('#loader').css('visibility','visible');
  });
});
</script>
<?php
}
if ($form['do_backup'] != 1 && isset($_GET['external'])) {// è il primo accesso e viene richiesto il backup esterno (sul browser)
  // Mostra il modulo form e poi termina la visualizzazione

  echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'];
  echo "</div>\n";
  echo "<form method=\"POST\">";
  echo "<input type=\"hidden\" name=\"do_backup\" value=\"1\">";
  echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
  echo "<input type=\"hidden\" value=\"" . $form['ritorno'] . "\" name=\"ritorno\" />\n";
  echo "<table class=\"Tsmall\" align=\"center\">\n";
  echo "<tr><td colspan='2' align=\"center\">" . $script_transl['sql_submit'] . "</td></tr>";
  echo "<tr><td class=\"FacetDataTD\"><input type=\"submit\" name=\"return\" value=\"" . $script_transl['return'] . "\">&#128281;</td>
            <td class=\"FacetDataTD\" align=\"right\"><input type=\"submit\" id=\"remote_submit\" value=\"&#9196;" . $script_transl['submit'] . "\"></td></tr>";
  echo "</table>\n</form>\n";
  require("../../library/include/footer.php");
?>
<script>
  document.querySelector("body").style.visibility = "visible";
  document.querySelector("#loader").style.visibility = "hidden";
</script>
<?php
} else {// non è il primo accesso oppure è il primo accesso ma è richiesto un backup interno (su GAzie)
  if (isset($_GET['internal'])){// backup interno (sul server hosting)
    if (!isset($_GET['automatic'])){ // se non è una richiesta automatica posso inviare l header
      echo "<div align=\"center\" class=\"FacetFormHeaderFont\">Backup interno dei dati per mettere in sicurezza il lavoro" ;
      echo "</div>\n";
      echo "<form method=\"POST\">";
      echo "<table class=\"Tsmall\" align=\"center\">\n";
      echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
      echo "<input type=\"hidden\" value=\"" . $form['ritorno'] . "\" name=\"ritorno\" />\n";
    }
    ?>
    <script>
      $.ajax({
        data: {'type':'save'},
        type: 'GET',
        url: 'ajax.php',
        success: function(output){
          //alert(output);
          document.querySelector("body").style.visibility = "visible";
          document.querySelector("#loader").style.visibility = "hidden";
          if(output.length > 0){// se restituisce un messaggio
            document.querySelector("#success").style.visibility = "hidden";
            document.querySelector("#error").innerHTML += "<b>"+output+"</b>";
          }else{
            document.querySelector("#error").style.visibility = "hidden";
          }

        }
      });
    </script>
    <?php
    if (!isset($_GET['automatic'])){ // se non è una richiesta automatica
      echo "<tr id='error'></tr>";
      echo "<tr id='success'><td colspan='2' align=\"center\"><b>Backup del database correttamente eseguito e salvato sul server</b></td></tr>";
      echo "<input type=\"hidden\" name=\"do_backup\" value=\"1\">";
      echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
      echo "<tr><td class=\"FacetFieldCaptionTD\"><input type=\"submit\" name=\"return\" value=\"" . $script_transl['return'] . "\"></td>";
      echo "</table>\n</form>\n";
      require("../../library/include/footer.php");
    }else{
      header("Location: " . $form['ritorno']);
    }
  } else {// Esegue il backup sul browser
    if (isset($_GET['external'])) {
      $gbackup = new gazBackup($link);
      $gbackup->save(DATA_DIR.'files/tmp/tmp-backup.sql'); // lo andrò a cancellare con il metodo gazDataDir dopo averlo aggiunto allo zip
      $zipname=$Database . '-' . date("YmdHi") . '-v' . GAZIE_VERSION ;
      $gbackup->gazDataDir($zipname);
      // Impostazione degli header per l'opzione "save as"
      header("Pragma: no-cache");
      header("Expires: 0");
      header("Content-Type: application/octet-stream");
      header("Content-Length: ".filesize(DATA_DIR.'files/tmp/'.$zipname.'.zip'));
      header("Content-Disposition: attachment; filename=\"".$zipname.".zip\"");
      readfile(DATA_DIR.'files/tmp/'.$zipname.'.zip');
      unlink(DATA_DIR.'files/tmp/'.$zipname.'.zip');
      // aggiorno la data dell'ultimo backup
			gaz_dbi_put_row($gTables['config'], 'variable', 'last_backup', 'cvalue', date('Y-m-d'));
    }
  }
}
?>
