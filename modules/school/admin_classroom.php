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
$msg = array('err' => array(), 'war' => array());
if (isset($_POST['Update']) || isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
    $form = gaz_dbi_parse_post('classroom');
    $form['ritorno'] = $_POST['ritorno'];
    if (isset($_POST['Submit'])) { // conferma tutto
        if (empty($form["classe"])) {
            $msg['err'][] = 'classe';
        }
        if (empty($form["sezione"])) {
            $msg['err'][] = 'sezione';
        }
        if (empty($form["teacher"])) {
            $msg['err'][] = 'teacher';
        }
        if (empty($form["anno_scolastico"])) {
            $msg['err'][] = 'anno_scolastico';
        }
        if (count($msg['err']) == 0) { // nessun errore
            // aggiorno il db
            if ($toDo == 'insert') {
                gaz_dbi_table_insert('classroom', $form);
            } elseif ($toDo == 'update') {
                gaz_dbi_table_update('classroom', array('id', $form['id']), $form);
            }
            header("Location: report_classrooms.php");
            exit;
        }
    } elseif (isset($_POST['Return'])) { // torno indietro
        header("Location: " . $form['ritorno']);
        exit;
    }
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
    $form = gaz_dbi_get_row($gTables['classroom'], 'id', intval($_GET['id']));
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
} else { //se e' il primo accesso per INSERT
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['id'] = '';
    $form['teacher'] = '';
    $form['classe'] = '';
    $form['sezione'] = '';
    $form['anno_scolastico'] = date("Y");
    $form['location'] = '';
    $form['title_note'] = '';
}
require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new schoolForm();
?>
<form method="POST" name="form" enctype="multipart/form-data" id="add-product">
    <?php
    echo '<input type="hidden" name="ritorno" value="' . $form['ritorno'] . '" />';
    echo '<input type="hidden" name="' . ucfirst($toDo) . '" value="" />';
    if ($toDo == 'insert') {
        echo '<div class="text-center"><b>' . $script_transl['ins_this'] . '</b></div>';
    } else {
        echo '<div class="text-center"><b>' . $script_transl['upd_this'] . ' ' . $form['classe'] . ' ' . $form['sezione'] . '</b></div>';
    }
    if (count($msg['err']) > 0) { // ho un errore
        $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
    }
    ?>
    <div class="panel panel-default gaz-table-form">
        <div class="container-fluid">
            <?php if ($toDo == 'update') { ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="id" class="col-sm-4 control-label">ID</label>
                            <p><?php echo $form['id']; ?></p>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                <?php
            }
            echo '<input type="hidden" name="id" value="'.$form['id'].'" />';
            ?>            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="classe" class="col-sm-4 control-label"><?php echo $script_transl['classe']; ?></label>
                        <input class="col-sm-4" type="text" value="<?php echo $form['classe']; ?>" name="classe" maxlength="16" />
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="sezione" class="col-sm-4 control-label"><?php echo $script_transl['sezione']; ?></label>
                        <input class="col-sm-8" type="text" value="<?php echo $form['sezione']; ?>" name="sezione" maxlength="16" />
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="anno_scolastico" class="col-sm-4 control-label"><?php echo $script_transl['anno_scolastico']; ?></label>
                        <input class="col-sm-4" type="number" min="2016" max="2049" value="<?php echo $form['anno_scolastico']; ?>" name="anno_scolastico" />
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="teacher" class="col-sm-4 control-label"><?php echo $script_transl['teacher']; ?></label>
                        <?php
                        $gForm->selectTeacher($form['teacher'], "style='max-width: 250px;'");
                        ?>
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="location" class="col-sm-4 control-label"><?php echo $script_transl['location']; ?></label>
                        <input class="col-sm-8" type="text" value="<?php echo $form['location']; ?>" name="location" maxlength="100" />
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="title_note" class="col-sm-4 control-label"><?php echo $script_transl['title_note']; ?></label>
                        <input class="col-sm-8" type="text" value="<?php echo $form['title_note']; ?>" name="title_note" maxlength="255" />
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="col-sm-12">
                <?php
                echo '<div class="col-sm-4 text-left"><input name="none" type="submit" value="" disabled></div>';
                echo '<div class="col-sm-8 text-center"><input name="Submit" type="submit" class="btn btn-warning" value="' . ucfirst($script_transl[$toDo]) . '!" /></div>';
                ?>
            </div>
        </div> <!-- chiude container --> 
    </div><!-- chiude panel -->
</form>
    
<?php
require("../../library/include/footer.php");
?>