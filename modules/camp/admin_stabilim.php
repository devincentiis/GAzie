<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
// >> Gestione stabilimenti iscritti al SIAN - portale olio <<
require("../../library/include/datlib.inc.php");
require ("../../modules/magazz/lib.function.php");
$admin_aziend=checkAdmin();
$gForm = new magazzForm();
$msg = array('err' => array(), 'war' => array());

if ((isset($_POST['Update'])) or (isset($_GET['Update']))) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}

if ((isset($_GET['Update']) and  !isset($_GET['id_rif'])) or isset($_POST['Return'])) {
    header("Location: ".$_POST['ritorno']);
    exit;
}

if ((isset($_POST['Insert'])) or (isset($_POST['Update']))) {   //se non e' il primo accesso
	//Parsing
	$form=gaz_dbi_parse_post('campi');

	if (isset($_POST['ins'])){//            Se viene inviata la richiesta di conferma totale ...
        if ($toDo == 'insert'){ // e' un inserimento, controllo se il codice esiste
			$rs_ctrl = gaz_dbi_get_row($gTables['campi'],'id_rif',$form['id_rif']);
			if ($rs_ctrl){
				$msg['err'][]= "codice_usato";
			}
        }
	    if (empty($form['id_rif'])){  //codice SIAN vuoto
            $msg['err'][]= "codice";
		}
		if (empty($form['descri'])){  //denominazione vuota vuota
           $msg['err'][]= "denomin";
		}
	    if (count($msg['err']) == 0){// nessun errore
			if ($toDo == 'update'){ // e' una modifica
				gaz_dbi_table_update('campi',$form["codice"],$form);
			} else { // e' un'inserimento
				gaz_dbi_table_insert('campi',$form);
			}
			exit(header("Location: ".$_POST['ritorno']));
		}
	}
} elseif ((!isset($_POST['Update'])) and (isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
	$form['ritorno'] = $_POST['ritorno'];
    $campi = gaz_dbi_get_row($gTables['campi'],"id_rif",$_GET['id_rif']);
	$form['codice'] = $campi['codice'];
    $form['id_rif'] = $campi['id_rif'];
    $form['descri'] = $campi['descri'];
    $form['indirizzo'] = $campi['indirizzo'];
	$form['provincia'] = $campi['provincia'];
	$form['comune'] = $campi['comune'];
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
	$form['ritorno'] = $_SERVER['HTTP_REFERER'];
	$rs_ultimo_codice = gaz_dbi_dyn_query("codice", $gTables['campi'], 1 ,'codice desc',0,1);
    $ultimo_codice = gaz_dbi_fetch_array($rs_ultimo_codice);
    $form['codice'] = $ultimo_codice['codice']+1;
    $form['id_rif'] = "";
    $form['descri'] = "";
    $form['indirizzo'] = "";
	$form['provincia'] = "";
	$form['comune'] = "";
}
require("../../library/include/header.php");
$script_transl = HeadMain();
if ($toDo == 'update') {
   $title = ucwords($script_transl[$toDo].$script_transl[0])." n.".$form['codice'];
} else {
   $title = ucwords($script_transl[$toDo].$script_transl[0]);
}
?>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="<?php echo ucfirst($toDo); ?>" value="">
<input type="hidden" value="<?php echo $_POST['ritorno']; ?>" name="ritorno">
<input type="hidden" value="<?php echo $form['codice']; ?>" name="codice">
<div class="panel panel-default gaz-table-form">
    <div class="container-fluid">
		<div align="center" class="lead"><h1>Gestione stabilimenti iscritti al SIAN</h1></div>
<?php
	if (count($msg['err']) > 0) { // ho un errore
		$gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
	}
?>
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="cod_sian" class="col-sm-4 control-label"><?php echo $script_transl[1]; ?></label>
                    <input class="col-sm-8" type="text" onkeyup="this.value=this.value.replace(/[^\d]/,'')" value="<?php echo $form['id_rif']; ?>" name="id_rif" maxlength="10" />
                </div>
            </div>
		</div><!-- chiude row  -->
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="denominazione" class="col-sm-4 control-label"><?php echo $script_transl[2]; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['descri']; ?>" name="descri" maxlength="50" />
                </div>
            </div>
		</div><!-- chiude row  -->
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="indirizzo" class="col-sm-4 control-label"><?php echo $script_transl[3]; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['indirizzo']; ?>" name="indirizzo" maxlength="50" />
                </div>
            </div>
		</div><!-- chiude row  -->
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="comune" class="col-sm-4 control-label"><?php echo $script_transl[5]; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['comune']; ?>" name="comune" maxlength="50" />
                </div>
            </div>
		</div><!-- chiude row  -->
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="provincia" class="col-sm-4 control-label"><?php echo $script_transl[4]; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['provincia']; ?>" name="provincia" maxlength="2" />
                </div>
            </div>
		</div><!-- chiude row  -->
		<div class="col-sm-6 text-left"><input type="submit" name="Return" value="<?php echo $script_transl['return']; ?>"></div>
<?php
if ($toDo == 'update') {
	print '<div class="col-sm-6"><input type="submit" class="btn btn-warning" accesskey="m" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="'.ucfirst($script_transl['update']).'!"></div>';
} else {
	print '<div class="col-sm-6"><input type="submit" class="btn btn-warning" accesskey="i" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="'.ucfirst($script_transl['insert']).'!"></div>';
}
?>
</div><!-- chiude container fluid -->
</div><!-- chiude panel default -->
</form>
<?php
require("../../library/include/footer.php");
?>
