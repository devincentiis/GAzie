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
$admin_aziend = checkAdmin();
$msg = array('err' => array(), 'war' => array());

if (!isset($_POST['hidden_req']) && isset($_GET['id']) && intval($_GET['id']) >= 1 ) { //al primo accesso allo script per update
    $form = gaz_dbi_get_row($gTables['warehouse'], 'id', intval($_GET['id']));
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
} elseif (!isset($_POST['hidden_req']) && !isset($_GET['id'])) { //al primo accesso allo script per insert
    $form = gaz_dbi_fields('warehouse');
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
} elseif (isset($_POST['id'])) { // accessi successivi
    $form = gaz_dbi_parse_post('warehouse');
    $form['ritorno'] = $_POST['ritorno'];
    $form['hidden_req'] = $_POST['hidden_req'];
    if (isset($_POST['ins'])) { // conferma tutto

        if (!empty($_FILES['userfile']['name'])) {
            if (!( $_FILES['userfile']['type'] == "image/png" ||
              $_FILES['userfile']['type'] == "image/x-png" ||
              $_FILES['userfile']['type'] == "image/jpeg" ||
              $_FILES['userfile']['type'] == "image/jpg" ||
              $_FILES['userfile']['type'] == "image/gif" ||
              $_FILES['userfile']['type'] == "image/x-gif")) $msg['err'][] = 'filmim';
				// controllo che il file non sia piu' grande di circa 64kb
            if ($_FILES['userfile']['size'] > 65530){
				//Antonio Germani anziche segnalare errore ridimensiono l'immagine
				$maxDim = 190;
				$file_name = $_FILES['userfile']['tmp_name'];
				list($width, $height, $type, $attr) = getimagesize( $file_name );
				if ( $width > $maxDim || $height > $maxDim ) {
					$target_filename = $file_name;
					$ratio = $width/$height;
					if( $ratio > 1) {
						$new_width = $maxDim;
						$new_height = $maxDim/$ratio;
					} else {
						$new_width = $maxDim*$ratio;
						$new_height = $maxDim;
					}
					$src = imagecreatefromstring( file_get_contents( $file_name ) );
					$dst = imagecreatetruecolor( $new_width, $new_height );
					imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
					imagedestroy( $src );
					imagepng( $dst, $target_filename); // adjust format as needed
					imagedestroy( $dst );
				}
                // fine ridimensionamento immagine
                $largeimg=1;
			}
        }

        if (strlen($form['name'])<4) {
            $msg['err'][] = 'name';
        }

        if ( count($msg['err']) == 0 || ( count($msg['war']) == 1 && $form['hidden_req'] == 'useanagra' )) { // nessun errore oppure ho scelto di usare una anagrafica esistente

            if (!empty($_FILES['userfile']) && $_FILES['userfile']['size'] > 0) { //se c'e' una nuova immagine nel buffer
                    if ($largeimg==0){
                        $form['image'] = file_get_contents($_FILES['userfile']['tmp_name']);
                    } else {
                        $form['image'] = file_get_contents($target_filename);
                    }
            } elseif ($form['id']>0) { // altrimenti riprendo la vecchia ma solo se è una modifica
              $oldimage = gaz_dbi_get_row($gTables['warehouse'], 'id', $form['id']);
              $form['image'] = $oldimage['image'];
            } else {
              $form['image'] = '';
            }

            if ($form['id']==0) { // ho un inserimento
                unset($form['id']);
                gaz_dbi_table_insert('warehouse', $form);
            } else { // modifica
                gaz_dbi_table_update('warehouse', ['id',$form['id']], $form);
            }
            header("Location: report_warehouse.php");
            exit;
        }
    } elseif (isset($_POST['Return'])) { // torno indietro
        header("Location: " . $form['ritorno']);
        exit;
    }
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new magazzForm();
$upd=($form['id']>0)?'upd_':'ins_';
?>
<script>
$(function () {
    $("#link_anagra").click(function() {
       $("input[name=hidden_req]").val('useanagra');
       $("input[name=ins]").trigger('click');
    });
});
</script>
<form role="form" method="post" name="pay_riba" enctype="multipart/form-data" >
    <input type="hidden" value="<?php echo $form['id'] ?>" name="id" />
    <input type="hidden" value="<?php echo $form['hidden_req'] ?>" name="hidden_req" />
    <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno">
<div class="text-center">
   <p><b><?php echo $script_transl[$upd.'this']; ?></b></p>
</div>
<?php
if (count($msg['err']) > 0) { // ho un errore
    $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
if (count($msg['war']) > 0) { // ho un alert
    $gForm->gazHeadMessage($msg['war'], $script_transl['err'], 'war');
}

if ($form['id'] == 0 ) { // inserimento
?>


<?php
}
?>
<div class="panel panel-default gaz-table-form">
  <div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="name" class="col-sm-4 control-label"><?php echo $script_transl['name']; ?> *</label>
                <input class="col-sm-8" type="text" value="<?php echo $form['name']; ?>" name="name" minlenght="4" maxlength="50"/>
            </div>
        </div>
    </div><!-- chiude row  -->
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="image" class="col-sm-4 control-label"><img src="../root/view.php?table=warehouse&field=id&value=<?php echo $form['id']; ?>" width="100" >*</label>
                <div class="col-sm-8"><?php echo $script_transl['image']; ?><input type="file" name="userfile" /></div>
            </div>
        </div>
    </div><!-- chiude row  -->
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="web_url" class="col-sm-4 control-label"><?php echo $script_transl['web_url']; ?></label>
                <input class="col-sm-8" type="text" value="<?php echo $form['web_url']; ?>" name="web_url" maxlength="255"/>
            </div>
        </div>
    </div><!-- chiude row  -->
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="note_other" class="col-sm-4 control-label"><?php echo $script_transl['note_other']; ?></label>
                <input class="col-sm-4" type="text" value="<?php echo $form['note_other']; ?>" name="note_other" id="note_other" maxlength="50"/>
            </div>
        </div>
    </div><!-- chiude row  -->
    <div class="row">
        <div class="col-md-12 text-center FacetFooterTD">
        <input class="btn btn-warning" id="preventDuplicate" onClick="chkSubmit();" type="submit" name="ins" value="<?php echo $script_transl['submit']; ?>" />
        </div>
    </div><!-- chiude row  -->
  </div>
</div>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
