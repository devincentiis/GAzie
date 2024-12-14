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
require("../root/lib.function.php");
$checkUpd = new CheckDbAlign;
$kb=0;
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
<?php

//
// Verifica i parametri della chiamata.
//
require("../../library/include/header.php");
$script_transl = HeadMain();
if (isset($_POST['hidden_req'])) { // accessi successivi allo script
    $form['hidden_req'] = $_POST["hidden_req"];
    $form['ritorno'] = $_POST['ritorno'];
    $form['do_backup'] = $_POST["do_backup"];
    if (isset($_POST['save_config'])) { // ho chiesto la modifica della configurazione
        $nv = filter_input(INPUT_POST, 'backup_mode');
        $kb = filter_input(INPUT_POST, 'keep_backup');
        $fs = filter_input(INPUT_POST, 'freespace_backup');
        //$fi = filter_input(INPUT_POST, 'filebackup');
        $fi = (isset($fi))?$fi:'0';
        $checkUpd->backupMode($nv); // passando un valore alla stessa funzione faccio l'update
        gaz_dbi_put_row($gTables['config'], 'variable', 'keep_backup', 'cvalue', $kb);
        gaz_dbi_put_row($gTables['config'], 'variable', 'freespace_backup', 'cvalue', $fs);
        gaz_dbi_put_row($gTables['config'], 'variable', 'file_backup', 'cvalue', $fi);
    }
} else {
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['do_backup'] = 0;
}
$bm = $checkUpd->backupMode();
$keep = gaz_dbi_get_row($gTables['config'], 'variable', 'keep_backup');
$freespace = gaz_dbi_get_row($gTables['config'], 'variable', 'freespace_backup');
//$filebackup = gaz_dbi_get_row($gTables['config'], 'variable', 'file_backup');
?>

<form method="POST">
    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="pill" href="#lista"><?php echo $script_transl['title']; ?></a></li>
        <?php
        if ( $admin_aziend["Abilit"]=="9" ) {
            echo "<li><a data-toggle=\"pill\" href=\"#config\">".$script_transl['config']."</a></li>";
        }
        ?>
    </ul>

    <input type="hidden" name="do_backup" value="1">
    <input type="hidden" value="<?php echo $form['hidden_req']; ?>" name="hidden_req" />
    <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno" />

    <div class="tab-content">
        <div id="lista" class="tab-pane fade in active">
            <div class="table-responsive">

            <table class="Tlarge table table-striped table-bordered table-condensed">
                <tr>
                    <th class="FacetFieldCaptionTD"><?php echo $script_transl['id']; ?></th>
                    <th class="FacetFieldCaptionTD"><?php echo $script_transl['ver']; ?></th>
                    <th class="FacetFieldCaptionTD"><?php echo $script_transl['name']; ?></th>
                    <th class="FacetFieldCaptionTD"><?php echo $script_transl['size']; ?></th>
                    <th class="FacetFieldCaptionTD"><?php echo $script_transl['dow']; ?></th>
                    <?php
                        if ( $admin_aziend["Abilit"]=="9") {
                            echo "<th class=\"FacetFieldCaptionTD\">".$script_transl['rec']."</th>";
                            echo "<th class=\"FacetFieldCaptionTD\" align=\"center\">".$script_transl['delete']."</th>";
                        }
                    ?>
                </tr>
                <?php
                $interval = 0;
                $files = array();
                if ($handle = opendir(DATA_DIR.'files/backups/')) {
                    while (false !== ($file = readdir($handle))) {
                        if ($file != "." && $file != ".." && preg_match("/(gz|zip)$/", $file)) {
                            $files[filemtime(DATA_DIR.'files/backups/' . $file)] = $file;
                        }
                    }
                    closedir($handle);
                    krsort($files);
                    $reallyLastModified = end($files);
                    $index = 0;
                    $id = array ();
                    foreach ($files as $file) {

                        preg_match('/-(.*?)-/',$file, $id);

                        if ($index < 30) { // se il modo è manuale visualizzo solo gli ultimi 30 backup, gli eccedenti li cancello
                            ?>
                            <tr class="FacetDataTD"><td><a class="btn btn-xs btn-default" style="cursor:default;" href="">
                                        <?php echo (count($id)>0) ? $id[1] : "nd"; ?>
                                    </a></td>
                                <td>
                                    <?php
                                        if ( preg_match('/-v(.*?).(zip|sql)/',$file, $versione)>0 )
                                            echo $versione[1];
                                    ?>
                                </td>
                                <td>
                                    <?php echo $file; ?>
                                </td>
                                <td>
                                    <?php echo formatSizeUnits(filesize(DATA_DIR.'files/backups/' . $file)); ?>
                                </td>
                                <td align="center">
                                    <a class="btn btn-xs btn-default" href="downlo_backup.php?id=<?php echo $file; ?>"><i class="glyphicon glyphicon-download"></i></a>
                                </td>
                                <?php
                                if ( $admin_aziend["Abilit"]=="9") { ?>
                                <td align="center">
                                    <a class="btn btn-xs btn-default" href="recover_backup.php?id=<?php echo $file ?>"><i class="glyphicon glyphicon-repeat"></i></a>
                                </td>
                                <td align="center">
                                    <a class="btn btn-xs btn-default" href="delete_backup.php?id=<?php echo $file ?>"><i class="glyphicon glyphicon-trash"></i></a>
                                </td>
                                <?php } ?>
                            </tr>
                            <?php
                            $index++;
                        } elseif ( $bm !== "automatic"){
                           unlink(DATA_DIR."files/backups/" . $file);
                        }
                    }
                }
                ?>
            </table>
            </div>
        </div>
        <div id="config" class="tab-pane fade">
            <div class="FacetDataTD div-config div-table">
                <div class="div-table-row">
                    <div class="div-table-col">
                        <h4><?php echo $script_transl['backup_mode']; ?></h4>
                        <ul class="licheck">

                        <?php

                        foreach ($script_transl['backup_mode_value'] as $k => $v) {
                            echo '<li><input type="radio" name="backup_mode" value="' . $k . '"';
                            if ($bm == $k) {
                                echo ' checked ';
                            }
                            echo '/>&nbsp;' . $v . '</li>';
                        }
                        ?>
                        </ul>
                    </div>
                </div>
                <div class="div-table-row">
                    <div class="div-table-col">
                        <h4>Automatico</h4>
                        <ul class="licheck">
                           <!-- <li>Esegui backup dei files (la cartella di gazie verrà salvata) : <input <?php echo ($filebackup['cvalue']==1) ? 'checked="checked"' : ''; ?> type="checkbox" name="filebackup" value="1" /> </li> -->
                            <li>Numero di backup da conservare, periodicità in giorni : <input type="text" name="keep_backup" value="<?php echo $keep['cvalue']; ?>" /> separati da virgola esempio "52,7" </li>
                            <li>Spazio da lasciare libero (%) : <input type="text" name="freespace_backup" value="<?php echo $freespace['cvalue']; ?>" /> raggiunto il limite i backup vecchi verranno cancellati</li>
                        </ul>
                    </div>
                </div>
                <div class="div-table-row">
                    <div class="div-table-col" >
                        <input class="btn btn-md btn-warning" type="submit" name="save_config" value=" <?php echo $script_transl['update']; ?>" />
                    </div>
                </div>
            </div>
        </div>
    </div>

</form>
<script>
document.onreadystatechange = function() {
    if (document.readyState !== "complete") {
        document.querySelector("body").style.visibility = "hidden";
        document.querySelector("#loader").style.visibility = "visible";
    } else {
        document.querySelector("#loader").style.display = "none";
        document.querySelector("body").style.visibility = "visible";
    }
};
</script>
<?php
require("../../library/include/footer.php");
?>
