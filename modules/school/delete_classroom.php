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
$error = '';

if (isset($_POST['Delete'])) {
    $student = gaz_dbi_get_row($gTables['students'], "student_classroom_id", intval($_GET['id']));
    if ($student) {
        $error = 'not_empty';
    } else {
        $deleted = gaz_dbi_del_row($gTables['classroom'], "id", intval($_GET['id']));
        header("Location: report_classrooms.php");
        exit;
    }
} elseif (isset($_GET['id'])) {
    
} else {
    header("Location: report_classrooms.php");
}

if (isset($_POST['Return'])) {
    header("Location: report_classrooms.php");
    exit;
}
$form = gaz_dbi_get_row($gTables['classroom'], "id", intval($_GET['id']));

require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<form method="post" action="<?php print $_SERVER['PHP_SELF'] . "?id=" . intval($_GET['id']); ?>" name="deleteform" class="form-horizontal" >
    <div class="container">    
        <div id="loginbox" style="margin-top:50px;" class="mainbox mainbox col-sm-offset-2 col-sm-8">                    
            <div class="panel panel-info" >
                <div class="panel-heading panel-gazie">
                    <div class="panel-title">
                        <img width="7%" src="../../library/images/logo_180x180.png" />
                        <img width="5%" src="./school.png" />
                        <?php echo $script_transl['title']; ?>
                    </div>
                    <div style="color: red; float:right; font-size: 100%; position: relative; top:-10px"></div>
                    <?php
                    if (!empty($error)) {
                        echo '<div id="login-alert" class="alert alert-danger col-sm-12">';
                        echo $script_transl['errors'][$error];
                        echo '</div>';
                    }
                    ?>
                </div>
                <table class="table table-responsive table-striped" >
                    <tr class="control">
                        <td class="col-sm-3">ID</td>
                        <td class="col-sm-9"><?php echo $form["id"]; ?></td>
                    </tr>
                    <tr class="control">
                        <td class="col-sm-3"><?php echo $script_transl['classe']; ?></td>
                        <td class="col-sm-9"><?php echo $form["classe"]; ?></td>
                    </tr>
                    <tr class="control">
                        <td class="col-sm-3"><?php echo $script_transl['sezione']; ?></td>
                        <td class="col-sm-9"><?php echo $form["sezione"]; ?></td>
                    </tr>
                    <tr class="control">
                        <td class="col-sm-3"><?php echo $script_transl['anno_scolastico']; ?></td>
                        <td class="col-sm-9"><?php echo $form["anno_scolastico"]; ?></td>
                    </tr>
                    <tr class="control">
                        <td class="col-sm-3"><?php echo $script_transl['teacher']; ?></td>
                        <td class="col-sm-9"><?php echo $form["teacher"]; ?></td>
                    </tr>
                    <tr class="control">
                        <td class="col-sm-3"><?php echo $script_transl['location']; ?></td>
                        <td class="col-sm-9"><?php echo $form["location"]; ?></td>
                    </tr>
                    <tr class="control">
                        <td class="col-sm-3"><?php echo $script_transl['title_note']; ?></td>
                        <td class="col-sm-9"><?php echo $form["title_note"]; ?></td>
                    </tr>
                </table>
                <div style="padding-top:10px" class="panel-body" >
                    <div style="padding-bottom: 25px;" class="input-group col-sm-6">
                        <input style="float:right;" class="btn btn-danger" type="submit" name="Delete" value="<?php echo $script_transl['delete']; ?>" />
                    </div>
                </div>  <!-- chiude div panel-body -->
            </div>  <!-- chiude div panel -->
        </div>
    </div><!-- chiude div container -->
</form>

<?php
require("../../library/include/footer.php");
?>