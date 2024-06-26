<?php
/*
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
  scriva   alla   Free  Software Foundation, 51 Franklin Street,
  Fifth Floor Boston, MA 02110-1335 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin(9);
$tp = $table_prefix . str_pad(intval($_GET['id']), 4, '0', STR_PAD_LEFT) . "\_";
$t_erased = array();
$msg = array();
if (isset($_POST['delete'])) {
    $form = gaz_dbi_get_row($gTables['students'], "student_id", intval($_GET['id']));
    $tp = $table_prefix . str_pad(intval($_GET['id']), 4, '0', STR_PAD_LEFT) . "\_";
    $ve = gaz_dbi_query("SELECT CONCAT(  'DROP VIEW `', TABLE_NAME,  '`;' ) AS query, TABLE_NAME as tn
FROM INFORMATION_SCHEMA.VIEWS
WHERE TABLE_NAME LIKE  '" . $tp . "%'");
    while ($r = gaz_dbi_fetch_array($ve)) {
        $t_erased[] = $r['tn'];
        gaz_dbi_query($r['query']);
    }
    $te = gaz_dbi_query("SELECT CONCAT(  'DROP TABLE `', TABLE_NAME,  '`;' ) AS query, TABLE_NAME as tn
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_NAME LIKE  '" . $tp . "%'");
    while ($r = gaz_dbi_fetch_array($te)) {
        $t_erased[] = $r['tn'];
        gaz_dbi_query($r['query']);
    }
// cancello il rigo dalla tabella students dell'installazione principale
    gaz_dbi_del_row($gTables['students'], 'student_id', intval($_GET['id']));
    $t_erased[] = '<b>' . $form['student_firstname'] . ' ' . $form['student_lastname'] ."</b>\n";
} elseif (isset($_GET['id'])) {
    $form = gaz_dbi_get_row($gTables['students'], "student_id", intval($_GET['id']));
    $msg[] = 'alert';
} else {
    header("Location: report_classrooms.php");
}

if (isset($_POST['Return'])) {
    header("Location: report_classrooms.php");
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<form method="post" action="<?php print $_SERVER['PHP_SELF'] . "?id=" . intval($_GET['id']); ?>" name="deleteform" class="form-horizontal" >
    <div class="container">    
        <div id="loginbox" style="margin-top:50px;" class="mainbox mainbox col-sm-offset-2 col-sm-8">                    
            <div class="panel panel-danger" >
                <div class="panel-heading panel-gazie">
                    <div class="panel-title">
                        <img width="7%" src="../../library/images/logo_180x180.png" />
                        <img width="5%" src="./school.png" />
                        <?php echo $script_transl['title'].' '.$form['student_lastname'].' '.$form['student_firstname']; ?>
                    </div>
                    <div style="color: red; float:right; font-size: 100%; position: relative; top:-10px"></div>
                    <?php
                    foreach ($t_erased as $v) {
                        echo '<div id="login-alert" class="alert alert-danger col-sm-12">';
                        echo $script_transl['tabella'] . $v;
                        echo '</div>';
                    }
                    ?>
                </div>
                <?php
                foreach ($msg as $v) {
                    echo '<div id="login-alert" class="alert alert-danger col-sm-12">';
                    echo $script_transl['msg'][$v];
                    echo '</div>';
                }
                ?>
                <table class="table table-responsive table-striped" >
                    <tr class="control">
                        <td class="col-sm-3"><?php echo $script_transl['Nome']; ?></td>
                        <td class="col-sm-9"><?php echo $form["student_firstname"]; ?></td>
                    </tr>
                    <tr class="control">
                        <td class="col-sm-3"><?php echo $script_transl['Cognome']; ?></td>
                        <td class="col-sm-9"><?php echo $form["student_lastname"]; ?></td>
                    </tr>
                    <tr class="control">
                        <td class="col-sm-3"><?php echo $script_transl['email']; ?></td>
                        <td class="col-sm-9"><?php echo $form["student_email"]; ?></td>
                    </tr>
                </table>
                <div style="padding-top:10px" class="panel-body" >
                    <?php if (!isset($_POST['delete'])) { ?>
                        <div style="padding-bottom: 25px;" class="input-group col-sm-6">
                            <input style="float:right;" class="btn btn-danger" type="submit" name="delete" value="<?php echo $script_transl['delete']; ?>" />
                        </div>
                    <?php } ?>
                </div>  <!-- chiude div panel-body -->
            </div>  <!-- chiude div panel -->
        </div>
    </div><!-- chiude div container -->
</form>

<?php
require("../../library/include/footer.php");
?>
