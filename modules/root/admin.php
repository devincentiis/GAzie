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


require( "../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
$rescomp=gaz_dbi_get_row($gTables['config'], 'variable', 'users_noadmin_all_company');
$company_choice = ($rescomp)?$rescomp['cvalue']:'';
require( "../../modules/vendit/lib.function.php");
$lm = new lotmag;

if (!isset($_POST['hidden_req'])) {
  $form['hidden_req'] = '';
  $form['company_id'] = $admin_aziend['company_id'];
  $form['search']['company_id'] = '';
} else {
  if (isset($_POST['logout'])) {
    redirect('logout.php');
    exit;
  }
  $form['hidden_req'] = $_POST['hidden_req'];
  if (isset($_POST['company_id'])){
    $form['company_id'] = intval($_POST['company_id']);
    if ($company_choice==1 || $admin_aziend['Abilit'] >= 8){
      $form['search']['company_id'] = $_POST['search']['company_id'];
    }
  } else {
    $form['company_id'] = $admin_aziend['company_id'];
    $form['search']['company_id'] = '';
  }
}


$checkUpd = new CheckDbAlign;
$data = $checkUpd->TestDbAlign();
$backupMode = $checkUpd->backupMode();
$keep = $checkUpd->keepMode();
$lastBackup = $checkUpd->testDbBackup($keep[1]); // controllo se sono passati i giorni stabiliti in configurazione dall'ultimo backup
if (is_array($data)) {
  // induco l'utente ad aggiornare il db
  redirect( '../../setup/install/install.php?tp=' . $table_prefix);
  exit;
}

$folderMissing = controllaEsistenzaCartelle();


require("../../library/include/header.php");

$script_transl = HeadMain();
?>
<style>
#sortable div:hover {
    cursor: move;
}
#sortable>div {
	margin: 0 auto auto auto;
}
#sortable {
	display: flex;
	flex-wrap: wrap;
}
@media (max-width: 978px) {
	form .container, form .container #sortable .col-md-6, div .container-fluid {
		padding-left: 0px;
		padding-right: 0px;
	}
	div.panel {
		border-left:0px;
		border-right:0px;
		border-radius:0px;
	}
	#gaz-responsive-table table{
		padding: 0px;
		overflow-wrap: anywhere;
	}
	.dataTables_wrapper > div.row > div.col-sm-12{
		padding-right: 0px;
	}
	.row {
		margin-right: 0px;
		padding: 0px;
	}
}
.panel {
	padding: 0px 0px 5px 0px;
	margin: 0px 0px 5px 0px;
}

.btn-full {
	width: 100%;
	margin-top: 2px;
}
.btn-full>span {
	width: 100%;
	margin-top: 2px;
	white-space: normal;
}
.vertical-align {
    display: flex;
    align-items: center;
}

</style>
<script>
$(function(){
	function isMobile() {
		return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
	}
	if (!isMobile()) {
      $("#sortable").sortable({
        update: function (event, ui) {
            var data = $(this).sortable('serialize');
            // POST to server using $.post or $.ajax
            $.ajax({
                data: data,
                type: 'post',
                url: './dashboard_update.php'
            });
        }
			});
      $("#sortable").disableSelection();
	}
});
</script>
<?php
//Backup automatico
if ($backupMode == "automatic" && $lastBackup && $admin_aziend['Abilit'] == 9) {
    $sysdisk = $checkUpd->get_system_disk();
    $freespace = gaz_dbi_get_row($gTables['config'], 'variable', 'freespace_backup');
    $percspace = (disk_total_space($sysdisk) / 100) * $freespace["cvalue"];
    $files = glob(DATA_DIR.'files/backups/*.zip');
    array_multisort(array_map('filemtime', $files), SORT_NUMERIC, SORT_ASC, $files);
    if (count($files) > intval($keep[0])) {
      if (count($files) > $keep[0] && $keep[0] > 0) {
        for ($i = 0; $i < count($files) - ($keep[0]); $i++)
        unlink($files[$i]);
      }
    }
    if (disk_free_space($sysdisk) < $percspace) {
      $i = 0;
      while (disk_free_space($sysdisk) < $freespace && $i < count($files)) {
        if ($i <= count($files) - 30) {
          unlink($files[$i]);
        }
        $i++;
      }
    }
?>
 <script>
    $.ajax({
      data: {'type':'save'},
      type: 'GET',
      url: '../inform/ajax.php',
      success: function(output){
        alert('Backup terminato');
        window.location.replace("./admin.php");
      }
    });
  </script>
  <h1 class="text-center text-warning bg-warning">Attendi la fine del backup automatico (ogni <?php echo $keep[1]; ?> giorni)<h1>
<?php
} else {
?>
<div class="container-fluid">
  <form method="POST" name="gaz_form">
    <input type="hidden" value="<?php echo $form['hidden_req'];?>" name="hidden_req" />
    <div class="container" style="width: auto;">

        <?php
		if ( $folderMissing )
		{
			echo '<div class="alert alert-danger text-center" role="alert">';
			echo 'Attenzione manca la cartella all\'interno di "data/files" per l\'azienda corrente';
			echo '</div>';
		}
		$student = false;
		if (preg_match("/([a-z0-9]{1,9})[0-9]{4}$/", $table_prefix, $tp)) {
			$rs_student = gaz_dbi_dyn_query("*", $tp[1] . '_students', "student_name = '" .  trim($admin_aziend["user_name"]) . "'");
			$student = gaz_dbi_fetch_array($rs_student);
		}
    if ( $lastBackup && !is_array($student) && $backupMode <> "automatic") {
            ?>
            <div class="alert alert-danger text-center" role="alert">
                <?php
                if ($admin_aziend['Abilit'] > 8) {
                    echo $script_transl['errors'][4] .' '.$keep[1].' giorni : <a  class="btn btn-md btn-warning" href="../inform/backup.php?' . $checkUpd->backupMode() . '">BACKUP!</a> &nbsp; (' . $checkUpd->backupMode() . ')';
                } else {
                    echo $script_transl['errors'][4] .' '.$keep[1].' giorni, avvisa l\'amministratore di sistema';
                }
                ?>
            </div>
            <?php
        }
        if (empty($admin_aziend['legrap_pf_nome']) || empty($admin_aziend['legrap_pf_cognome'])) {
            ?>
            <div class="alert alert-danger text-center" role="alert">
                <?php
                    echo $script_transl['errors']['legrap'] ;
                ?>
            </div>
            <?php
        }
        if ($admin_aziend['sexper']=='G' && ( empty($admin_aziend['REA_ufficio']) || empty($admin_aziend['REA_socio']) || strlen($admin_aziend['REA_numero']) < 4)) {
            ?>
            <div class="alert alert-danger text-center" role="alert">
                <?php
                    echo $script_transl['errors']['rea'] ;
                ?>
            </div>
            <?php
        }
$get_widgets = gaz_dbi_dyn_query("*", $gTables['breadcrumb'],"exec_mode=2 AND adminid='".$admin_aziend['user_name']."' AND (codice_aziend = 0 OR codice_aziend = ".$admin_aziend['codice'].")", 'position_order');
echo '<div id="sortable" class="vertical-align">';
while ( $grr = gaz_dbi_fetch_array($get_widgets) ) {
  $dfn = explode('/',$grr['file']);
  $query = 'SELECT am.access, am.custom_field FROM ' . $gTables['admin_module'] . ' AS am' .
           ' LEFT JOIN ' . $gTables['module'] . ' AS module ON module.id=am.moduleid' .
           " WHERE am.adminid='" .$admin_aziend['user_name']. "' AND module.name='".$dfn[0]."' AND am.company_id = ".$admin_aziend['codice']." AND am.access >= 3";
  $result = gaz_dbi_query($query) or gaz_die ( $query, "1030", __FUNCTION__ );
  if (gaz_dbi_num_rows($result) >= 1) {
    $row = gaz_dbi_fetch_array($result);
    $chkes = is_string($row['custom_field'])? json_decode($row['custom_field']) : false;
    $isexcl = ($chkes && isset($chkes->excluded_script))?$chkes->excluded_script:[];
    $okaccess = (in_array(substr($dfn[1],0,-4),$isexcl))?false:true;
    if ($okaccess) {
      $col_lg=(!empty($grr['grid_class']))?$grr['grid_class']:'';
      echo '<div class="col-xs-12 col-md-6 '.$col_lg.' text-center" id="position-'.$grr['id_bread'].'">';
      require('../'.$grr['file']);
      echo '</div>';
    }
  }

}
echo '</div>';

?>
    </div>
	<div style="display:none" id="dialog_grid" title="Scegli la larghezza del widget"></div>
</form>
<script>
$(function() {
	$("#dialog_grid").dialog({ autoOpen: false });
	$('.dialog_grid').click(function() {
		var id = $(this).attr('id_bread');
		$( "#dialog_grid" ).dialog({
			position: { my:"right top", at:"center center", of: this },
			minHeight: 1,
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
            "1": function (event, ui) {
			  $.ajax({
			    data: { id_bread:id,gridlg:'1'},
				type: 'post',
				url: './dashboard_update.php',
				success: function(output){
					window.location.replace("./admin.php");
				}
			  });
			},
            "2": function (event, ui) {
			  $.ajax({
			    data: { id_bread:id,gridlg:'2'},
				type: 'post',
				url: './dashboard_update.php',
				success: function(output){
					window.location.replace("./admin.php");
				}
			  });
			},
            "4": function (event, ui) {
			  $.ajax({
			    data: { id_bread:id,gridlg:'4'},
				type: 'post',
				url: './dashboard_update.php',
				success: function(output){
					window.location.replace("./admin.php");
				}
			  });
			},
            "Non cambiare": function() {
				$(this).dialog("close");
            }
			}
		});
		$("#dialog_grid" ).dialog( "open" );
	});
});

</script>
</div>
<?php
}
require('../../library/include/footer.php');
?>
