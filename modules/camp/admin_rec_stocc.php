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
 // >> Gestione recipienti di stoccaggio <<
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
if ((isset($_GET['Update']) and  !isset($_GET['codice'])) or isset($_POST['Return'])) {
    header("Location: ".$_POST['ritorno']);
    exit;
}

if ((isset($_POST['Insert'])) or (isset($_POST['Update']))) {   //se non e' il primo accesso
	//Parsing
	$form=gaz_dbi_parse_post('camp_recip_stocc');
	// Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])){
        if ($toDo == 'insert'){ // e' un inserimento, controllo se il codice esiste
			$rs_ctrl = gaz_dbi_get_row($gTables['camp_recip_stocc'],'cod_silos',$form['cod_silos']);
			if ($rs_ctrl){
				$msg['err'][]= "codice_usato";
			}
        }
	    if (empty($form['cod_silos'])){  //codice vuoto
            $msg['err'][]= "codice";
		}
		if (empty($form['capacita'])){  //capacità vuota
           $msg['err'][]= "capacita";
		}
	    if (count($msg['err']) == 0){// nessun errore
			if ($toDo == 'update'){ // e' una modifica
				$update = array();
				$update[]="cod_silos";
				$update[]=$_GET['codice'];
				gaz_dbi_table_update('camp_recip_stocc',$update,$form);
			} else { // e' un'inserimento
				gaz_dbi_table_insert('camp_recip_stocc',$form);
			}
			exit(header("Location: ".$_POST['ritorno']));
		}
	}
} elseif ((!isset($_POST['Update'])) and (isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
	$form['ritorno'] = $_POST['ritorno'];
    $camp_recip_stocc = gaz_dbi_get_row($gTables['camp_recip_stocc'],"cod_silos",$_GET['codice']);
    $form['cod_silos'] = $camp_recip_stocc['cod_silos'];
    $form['nome'] = $camp_recip_stocc['nome'];
    $form['capacita'] = $camp_recip_stocc['capacita'];
    $form['affitto'] = $camp_recip_stocc['affitto'];
	$form['dop_igp'] = $camp_recip_stocc['dop_igp'];
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
	$form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['cod_silos'] = "";
    $form['capacita'] = "";
    $form['nome'] = "";
    $form['affitto'] = 0;
    $form['dop_igp']= 0;
}
require("../../library/include/header.php");
$script_transl = HeadMain();
if ($toDo == 'update') {
   $title = ucwords($script_transl[$toDo].$script_transl[0])." n.".$form['cod_silos'];
} else {
   $title = ucwords($script_transl[$toDo].$script_transl[0]);
}
?>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="<?php echo ucfirst($toDo); ?>" value="">
<input type="hidden" value="<?php echo $_POST['ritorno']; ?>" name="ritorno">
<div class="panel panel-default gaz-table-form">
    <div class="container-fluid">
		<div align="center" class="lead"><h1>Gestione Recipienti di stoccaggio olio</h1></div>
<?php
	if (count($msg['err']) > 0) { // ho un errore
		$gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
	}
?>
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="cod_silos" class="col-sm-4 control-label"><?php echo $script_transl[1]; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['cod_silos']; ?>" name="cod_silos" maxlength="10" />
                </div>
            </div>
		</div><!-- chiude row  -->
    <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="nome_silos" class="col-sm-4 control-label">Nome</label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['nome']; ?>" name="nome" maxlength="20" />
                </div>
            </div>
		</div><!-- chiude row  -->
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="capacita" class="col-sm-4 control-label"><?php echo $script_transl[2]; ?></label> &nbsp; Kg
                    <input class="col-sm-2" type="number" step="any" min="0.001" value="<?php echo $form['capacita']; ?>" name="capacita" maxlength="10" />
                </div>
            </div>
		</div><!-- chiude row  -->
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
				<label for="affitto" class="col-sm-4 control-label"><?php echo $script_transl['3']; ?></label>
				<label>Proprietà</label>
				<input  type="radio" name="affitto" value="0"<?php if ($form['affitto']==0){echo " checked";}?>>
				<label>Affitto/comodato</label>
				<input  type="radio" name="affitto" value="1"<?php if ($form['affitto']==1){echo " checked";}?>>
			</div>
            </div>
		</div><!-- chiude row  -->
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
				<label for="dop_igp" class="col-sm-4 control-label"><?php echo $script_transl['4']; ?></label>
				<label>No</label>
				<input  type="radio" name="dop_igp" value="0"<?php if ($form['dop_igp']==0){echo " checked";}?>>
				<label>Sì</label>
				<input  type="radio" name="dop_igp" value="1"<?php if ($form['dop_igp']==1){echo " checked";}?>>
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
<a href="https://programmisitiweb.lacasettabio.it/quaderno-di-campagna/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:20%; z-index:2000;"> Registro di campagna è un modulo di Antonio Germani</a>
<?php
require("../../library/include/footer.php");
?>
