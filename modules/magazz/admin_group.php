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
// prima stesura: Antonio Germani
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
$msg = array('err' => array(), 'war' => array());
$modal_ok_insert = false;
$today=	strtotime(date("Y-m-d H:i:s",time()));
$presente="";
$largeimg="";

/** ENRICO FEDELE */
/* Inizializzo per aprire in finestra modale */
$modal = false;
if (isset($_POST['mode']) || isset($_GET['mode'])) {
    $modal = true;
    if (isset($_GET['ok_insert'])) {
        $modal_ok_insert = true;
    }
}
/** ENRICO FEDELE */
if (isset($_POST['Update']) || isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if(isset($_GET['delete'])) {
	gaz_dbi_table_update ("artico", $_GET['delete'], array("id_artico_group"=>"") );
	header("Location: ../magazz/admin_group.php?Update&id_artico_group=".$_GET['group']."&tab=variant");
}

if(isset($_GET['group_delete'])) {

	$query = "SELECT codice, descri FROM " . $gTables['artico'] . " WHERE id_artico_group = '".$_GET['group_delete']."'";
	$arts = gaz_dbi_query($query);
	while ($art = $arts->fetch_assoc()) {// scollego tutti gli articolo
	gaz_dbi_table_update ("artico", $art['codice'], array("id_artico_group"=>"") );
	}
	gaz_dbi_del_row($gTables['artico_group'], "id_artico_group", $_GET['group_delete']);// cancello il gruppo
	header("Location: ../magazz/report_artico.php");
	exit;
}

if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso

	$form = gaz_dbi_parse_post('artico_group');
	$form['id_artico_group'] = trim($form['id_artico_group']);
	$form['ritorno'] = $_POST['ritorno'];
	$form['ref_ecommerce_id_main_product'] = substr($_POST['ref_ecommerce_id_main_product'], 0, 9);
	$form['large_descri'] = filter_input(INPUT_POST, 'large_descri');
	$form['cosear'] = filter_var($_POST['cosear'],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$form['codart'] = filter_var($_POST['codart'],FILTER_SANITIZE_FULL_SPECIAL_CHARS);

	if ((isset($_GET['tab']) && $_GET['tab']=="variant") || ($_POST['cosear'] <> $_POST['codart']) ){
		$cl_home="";
		$cl_home_tab="";
		$cl_variant="active";
		$cl_variant_tab="in active";
	} else {
		$cl_home="active";
		$cl_home_tab="in active";
		$cl_variant="";
		$cl_variant_tab="";
	}

	if(isset($_POST['codart']) AND isset($_POST['OKsub'])&&$_POST['OKsub']=="Salva"){	// se si salva la selezione degli articoli facenti parte del gruppo
		if ($toDo == 'insert'){// se è un nuovo inserimento gruppo
			 if (empty($form["descri"])) { // controllo che sia stata inserita almeno la descrizione
				$msg['err'][] = 'descri';
			}
			if (strlen($_POST['codart'])==0){
				$msg['err'][] = 'empty_var';
				$cl_home="";
				$cl_home_tab="";
				$cl_variant="active";
				$cl_variant_tab="in active";
			}
		}
		// devo controllare se l'articolo che si sta inserendo appartiene già ad un altro gruppo
		$ckart=gaz_dbi_get_row($gTables['artico'], 'codice', $_POST['codart']);

		if (!isset($ckart['id_artico_group']) && strlen($_POST['codart'])>0){
			$msg['err'][] = 'grcod';
			$cl_home="";
			$cl_home_tab="";
			$cl_variant="active";
			$cl_variant_tab="in active";
		}

		if (count($msg['err']) == 0) {// nessun errore
			if (isset($_POST['codart']) && $toDo == 'insert'){
				gaz_dbi_table_insert('artico_group', $form);
				$form['id_artico_group']=gaz_dbi_last_id();
				gaz_dbi_table_update ("artico", $_POST['codart'], array("id_artico_group"=>$form['id_artico_group']) );
        if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname'])){
          // Aggiornamento parent su e-commerce
          $gs=$admin_aziend['synccommerce_classname'];
          $gSync = new $gs();
          if($gSync->api_token){
            $gSync->UpsertParent($form,$toDo);
            //exit;
          }
        }
				// il redirect deve modificare il form in update perché è stato già inserito
				header("Location: ../magazz/admin_group.php?Update&id_artico_group=".$form['id_artico_group']."&tab=variant");
			} elseif (isset($_POST['codart'])){
				gaz_dbi_table_update ("artico", $_POST['codart'], array("id_artico_group"=>$form['id_artico_group']));
				// il redirect deve modificare il form in update perché è stato già inserito
				header("Location: ../magazz/admin_group.php?Update&id_artico_group=".$form['id_artico_group']."&tab=variant");
			}
		}
	}

	/** ENRICO FEDELE */
	/* Controllo se il submit viene da una modale */
	if (isset($_POST['Submit']) || ($modal === true && isset($_POST['mode-act']))) { // conferma tutto
    /** ENRICO FEDELE */
		if ($toDo == 'update') {  // controlli in caso di modifica

		} else {
			// controllo che l'articolo ci sia gia'
			$rs_articolo = gaz_dbi_dyn_query('id_artico_group', $gTables['artico_group'], "id_artico_group = '" . $form['id_artico_group'] . "'", "id_artico_group DESC", 0, 1);
			$rs = gaz_dbi_fetch_array($rs_articolo);
			if ($rs) {
				$msg['err'][] = 'codice';
			}
		}
    if ($_FILES['userfile']['error']==1){
      $msg['err'][] = 'filetoobig';
    } else {
      if (!empty($_FILES['userfile']['name'])) {
        if (!( strtolower($_FILES['userfile']['type']) == "image/png" ||
            strtolower($_FILES['userfile']['type']) == "image/x-png" ||
            strtolower($_FILES['userfile']['type']) == "image/jpeg" ||
            strtolower($_FILES['userfile']['type']) == "image/jpg" ||
            strtolower($_FILES['userfile']['type']) == "image/gif" ||
            strtolower($_FILES['userfile']['type']) == "image/x-gif")) $msg['err'][] = 'filmim';
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
          } else {
            $target_filename=$file_name = $_FILES['userfile']['tmp_name'];
          }
      }
    }
		if (empty($form["id_artico_group"]) AND $toDo == 'update') {
			$msg['err'][] = 'valcod';
		}
		if (empty($form["descri"])) {
			$msg['err'][] = 'descri';
		}
		if ($toDo == 'insert') {
			if (!isset($_POST['variant'])){
				$msg['err'][] = 'empty_var';
			}
		}

		if (count($msg['err']) == 0) { // nessun errore
			if (!empty($_FILES['userfile']) && $_FILES['userfile']['size'] > 0) { //se c'e' una nuova immagine nel buffer
					if ($largeimg==0){
						$form['image'] = file_get_contents($_FILES['userfile']['tmp_name']);
					} else {
						$form['image'] = file_get_contents($target_filename);
					}
			} elseif ($toDo == 'update') { // altrimenti riprendo la vecchia ma solo se è una modifica
			  $oldimage = gaz_dbi_get_row($gTables['artico_group'], 'id_artico_group', $form['ref_ecommerce_id_main_product']);
			  $form['image'] = ($oldimage)?$oldimage['image']:'';
			} else {
			  $form['image'] = '';
			}

			$form['large_descri'] = htmlspecialchars_decode ($form['large_descri']);
			// aggiorno il db
			if ($toDo == 'insert') {
			  gaz_dbi_table_insert('artico_group', $form);
			} elseif ($toDo == 'update') {
			  gaz_dbi_table_update('artico_group', array( 0 => "id_artico_group", 1 => $form['id_artico_group']), $form);
			}
			if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname'])){
				// Aggiornamento parent su e-commerce
				$gs=$admin_aziend['synccommerce_classname'];
				$gSync = new $gs();
				if($gSync->api_token){
					$gSync->UpsertParent($form,$toDo);
					//exit;
				}
			}
			/** ENRICO FEDELE */
			/* Niente redirect se sono in finestra modale */
			if ($modal === false) {

				header("Location: ../../modules/magazz/report_artico.php");
				exit;

			} else {
				header("Location: ../../modules/magazz/admin_artico.php?mode=modal&ok_insert=1");
			  exit;
			}
		}
		/** ENRICO FEDELE */
	} elseif (isset($_POST['Return']) && $modal === false) { // torno indietro
		/* Solo se non sono in finestra modale */
		/** ENRICO FEDELE */
		header("Location: " . $form['ritorno']);
		exit;
	}
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
    $form = gaz_dbi_get_row($gTables['artico_group'], 'id_artico_group', intval($_GET['id_artico_group']));
	$form['cosear'] = "";
	$form['codart'] = "";

	if (isset($_GET['tab']) && $_GET['tab']=="variant"){
		$cl_home="";
		$cl_home_tab="";
		$cl_variant="active";
		$cl_variant_tab="in active";
	} else {
		$cl_home="active";
		$cl_home_tab="in active";
		$cl_variant="";
		$cl_variant_tab="";
	}

    /** ENRICO FEDELE */
    if ($modal === false) {
        $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    } else {
        $form['ritorno'] = 'admin_artico.php';
    }

} else { //se e' il primo accesso per INSERT
    $form = gaz_dbi_fields('artico');
	$form['cosear'] = "";
	$form['codart'] = "";
    /** ENRICO FEDELE */
    if ($modal === false) {
        $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    } else {
        $form['ritorno'] = 'admin_artico.php';
    }

    $form['web_public'] = 5;
    $form['depli_public'] = 1;

    // eventuale descrizione ampliata
    $form['large_descri'] = '';
	$form['ref_ecommerce_id_main_product']="";
	$form['id_artico_group'] = "";

	$cl_home="active";
	$cl_home_tab="in active";
	$cl_variant="";
	$cl_variant_tab="";
}

/** ENRICO FEDELE */
/* Solo se non sono in finestra modale carico il file di lingua del modulo */
if ($modal === false) {
    require("../../library/include/header.php");
    $script_transl = HeadMain(0, array('custom/autocomplete'));
} else {
    $script = basename($_SERVER['PHP_SELF']);
    require("../../language/" . $admin_aziend['lang'] . "/menu.inc.php");
    require("../../modules/magazz/lang." . $admin_aziend['lang'] . ".php");
    if (isset($script)) { // se è stato tradotto lo script lo ritorno al chiamante
        $script_transl = $strScript[$script];
    }

    $script_transl = $strCommon + $script_transl;
}
if (intval($form['id_artico_group'])>0){
$query = "SELECT codice, descri FROM " . $gTables['artico'] . " WHERE id_artico_group = '".$form['id_artico_group']."'";
$arts = gaz_dbi_query($query);
}

?>

<script>
function itemErase(id,descri,group){
	$(".compost_name").append(descri);

	$("#confirm_erase").dialog({
		modal: true,
		show: "blind",
		hide: "explode",
		buttons: {
			No: function() {
				$(".compost_name").empty();
				$( this ).dialog( "close" );
			},
			Togli: function() {
				window.location.href = 'admin_group.php?delete='+id+'&group='+group;
			}

		  },
		  close: function(){
			$(".compost_name").empty();
		  }
		});
}
function groupErase(group,descri){
	$(".group_name").append(group+' '+descri);

	$("#confirm_destroy").dialog({
		modal: true,
		show: "blind",
		hide: "explode",
		buttons: {
			No: function() {
				$(".group_name").empty();
				$( this ).dialog( "close" );
			},
			Togli: function() {
				window.location.href = 'admin_group.php?group_delete='+group;
			}

		  },
		  close: function(){
			$(".group_name").empty();
		  }
		});
}
</script>

<form method="POST" name="form" enctype="multipart/form-data" id="add-product">
	<?php
	if (!empty($form['descri'])) $form['descri'] = htmlentities($form['descri'], ENT_QUOTES);
	if ($modal === true) {
		echo '<input type="hidden" name="mode" value="modal" />
			  <input type="hidden" name="mode-act" value="submit" />';
	}
	echo '<input type="hidden" name="ritorno" value="' . $form['ritorno'] . '" />';
	echo '<input type="hidden" name="ref_ecommerce_id_main_product" value="' . $form['ref_ecommerce_id_main_product'] . '" />';

	if ($modal_ok_insert === true) {
		echo '<div class="alert alert-success" role="alert">' . $script_transl['modal_ok_insert'] . '</div>';
		echo '<div class=" text-center"><button class="btn btn-lg btn-default" type="submit" name="none">' . $script_transl['iterate_invitation'] . '</button></div>';
	} else {
	   $gForm = new magazzForm();
		/** ENRICO FEDELE */
		/* Se sono in finestra modale, non visualizzo questo titolo */
		$changesubmit = '';
		if ($modal === false) {
			?>
				<!--+ DC - 06/02/2019 -->
				<script type="text/javascript" src="../../library/IER/IERincludeExcludeRows.js"></script>

				<input type="hidden" id="IERincludeExcludeRowsInput" name="IERincludeExcludeRowsInput" />

			<div id="IERenableIncludeExcludeRows" title="Personalizza videata" onclick="enableIncludeExcludeRows()"></div>
				<a target="_blank" href="../wiki/099 - Interfaccia generale/99.. Personalizzare una form a run-time (lato utente).md"><div id="IERhelpIncludeExcludeRows" title="Aiuto"></div></a>
				<div id="IERsaveIncludeExcludeRows" title="Nessuna modifica fatta" onclick="saveIncludeExcludeRows()"></div>
			<div id="IERresetIncludeExcludeRows" title="Ripristina"></div>
			<!--- DC - 06/02/2019 -->
				<?php
		}
		echo '<input type="hidden" name="' . ucfirst($toDo) . '" value="" />';
		if (count($msg['err']) > 0) { // ho un errore
			$gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
		}
		if (isset($_SESSION['ok_ins'])){
			$gForm->toast('L\'articolo ' . $_SESSION['ok_ins'].' è stato inserito con successo, sotto per modificarlo. Oppure puoi: <a class="btn btn-info" href="admin_artico.php?Insert">Inserire uno nuovo articolo</a> ' , 'alert-last-row', 'alert-success');
			unset($_SESSION['ok_ins']);
		}
		if ($toDo == 'insert') {
			echo '<div class="text-center"><h3>' . $script_transl['ins_this'] . '</h3></div>';
		} else {
			echo '<div class="text-center"><h3>' . $script_transl['upd_this'] . ' ' . $form['id_artico_group'] . '</h3></div>';
		}
		?>

		<div class="panel panel-warning gaz-table-form"><p><?php echo $script_transl['info']; ?> </p></div>
			<div class="panel panel-default gaz-table-form div-bordered">
				<div class="container-fluid">
					<ul class="nav nav-pills">
						<li class="<?php echo $cl_home;?>"><a data-toggle="pill" href="#home"><?php echo $script_transl['home']; ?></a></li>

						<li class="<?php echo $cl_variant;?>"><a data-toggle="pill" href="#variant"><?php echo $script_transl['variant']; ?></a></li>

						<li style="float: right;"><?php echo '<input name="Submit" type="submit" class="btn btn-warning" value="' . ucfirst($script_transl[$toDo]) . '" />'; ?></li>
					</ul>
					<div class="tab-content">
						<div id="home" class="tab-pane fade <?php echo $cl_home_tab;?>">
							<?php if ($toDo !== 'insert'){?>
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<label for="codice" class="col-sm-4 control-label"><?php echo $script_transl['codice']; ?></label>
										<input class="col-sm-4" type="text" value="<?php echo $form['id_artico_group']; ?>" name="id_artico_group" id="id_artico_group" maxlength="9" tabindex="1" readonly="readonly"/>
									</div>
								</div>
							</div><!-- chiude row  -->
							<?php } else {
								echo '<input type="hidden" name="id_artico_group" value="" />';
							}?>
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<label for="descri" class="col-sm-4 control-label"><?php echo $script_transl['descri']; ?></label>
										<input class="col-sm-8" type="text" value="<?php echo $form['descri']; ?>" name="descri" maxlength="255" id="suggest_descri_artico" />
									</div>
								</div>
							</div><!-- chiude row  -->
							<!--+ DC - 06/02/2019 -->
							<!--
							Come rendere una videata personalizzabile:
							Su tutte le div con class="row" (tranne quelle che contengono campi obbligatori)
							sostituirle nel seguente modo:
							PRIMA:
							<div class="row">
							DOPO:
							<div id="catMer" class="row IERincludeExcludeRow">
							In pratica inserite un id (unico per ogni riga) ed aggiungere la classe "IERincludeExcludeRow"
							-->

							<!--+ DC - 06/02/2019 div class="row" --->
							<div id="bodyText" class="row IERincludeExcludeRow">
								<div class="col-md-12">
									<div class="form-group">
										<label for="large_descri" class="col-sm-4 control-label"><?php echo $script_transl['body_text']; ?></label>
										<div class="col-sm-8">
											<textarea id="large_descri" name="large_descri" class="mceClass"><?php echo $form['large_descri']; ?></textarea>
										</div>
									</div>
								</div>
							</div><!-- chiude row  -->

							<!--+ DC - 06/02/2019 div class="row" --->
							<div id="image" class="row IERincludeExcludeRow">
								<div class="col-md-12">
									<div class="form-group">
										<label for="image" class="col-sm-4 control-label"><img src="../root/view.php?table=artico_group&value=<?php echo $form['id_artico_group']; ?>&field=id_artico_group" width="100" >*</label>
										<div class="col-sm-8"><?php echo $script_transl['image']; ?><input type="file" name="userfile" /></div>
									</div>
								</div>
							</div><!-- chiude row  -->

							<!--+ DC - 06/02/2019 div class="row" --->
							<div id="refEcommercIdProduct" class="row IERincludeExcludeRow">
								<div class="col-md-12">
									<div class="form-group">
										<label for="ref_ecommerce_id_product" class="col-sm-4 control-label">ID riferimento e-commerce</label>
										<input class="col-sm-4" type="text" value="<?php echo $form['ref_ecommerce_id_main_product']; ?>" name="ref_ecommerce_id_main_product" maxlength="15" />
									</div>
								</div>
							</div><!-- chiude row  -->
							<!--+ DC - 06/02/2019 div class="row" --->
							<div id="webUrl" class="row IERincludeExcludeRow">
							<div class="col-md-12">
								<div class="form-group">
									<label for="web_url" class="col-sm-4 control-label"><?php echo $script_transl['web_url']; ?></label>
									<input class="col-sm-8" type="text" value="<?php echo $form['web_url']; ?>" name="web_url" maxlength="255" />
								</div>
							</div>
							</div><!-- chiude row  -->
							<!--+ DC - 06/02/2019 div class="row" --->
							<div id="depliPublic" class="row IERincludeExcludeRow">
								<div class="col-md-12">
									<div class="form-group">
										<label for="depli_public" class="col-sm-4 control-label"><?php echo $script_transl['depli_public']; ?></label>
				<?php
				$gForm->variousSelect('depli_public', $script_transl['depli_public_value'], $form['depli_public'], "col-sm-8", true, '', false, 'style="max-width: 200px;"');
				?>
									</div>
								</div>
							</div><!-- chiude row  -->
							<!--+ DC - 06/02/2019 div class="row" --->
							<div id="webPublic" class="row IERincludeExcludeRow">
								<div class="col-md-12">
									<div class="form-group">
										<label for="web_public" class="col-sm-4 control-label"><?php echo $script_transl['web_public']; ?></label>
				<?php
				$gForm->variousSelect('web_public', $script_transl['web_public_value'], $form['web_public'], "col-sm-8", true, '', false, 'style="max-width: 200px;"');
				?>
									</div>
								</div>
							</div><!-- chiude row  -->
							</div><!-- chiude tab-pane  -->

							<div id="variant" class="tab-pane fade <?php echo $cl_variant_tab;?>">
								<div class="container-fluid">
								<?php $color='eeeeee';

								echo '<ul class="col-xs-12 col-sm-12 col-md-11 col-lg-10">';
								$v=0;
								if (isset($arts)){
								while ($art = $arts->fetch_assoc()) {

									$icona=(is_array($art['codice']))?'<a class="btn btn-xs btn-warning collapsible" id="'.$art['codice'].'" data-toggle="collapse" data-target=".' . $art['codice'] . '"><i class="glyphicon glyphicon-list"></i></a>':'';
									echo '<div style="background-color: #'.$color.'">
									<a class="btn btn-xs btn-success" href="admin_artico.php?Update&amp;codice=' . $art['codice'] . '">'.$art['codice'].'</a> - '.$art['descri'].' '.$icona.' _ _ _ _ ';
									if (intval($arts->num_rows)>1){
										echo '<a class="btn btn-xs btn-danger" onclick="itemErase(\''.addslashes($art['codice']).'\', \''.addslashes($art['descri']).'\', \''.addslashes($form['id_artico_group']).'\');">  togli X </a>';
									}
									echo '</div>';
									$color=($color=='fcfcfc')?'eeeeee':'fcfcfc';
									echo '<input type="hidden" name="variant['.$v.']" value="' . $art['codice'] . '" />';
									$v++;
								}
								}
								?>
								</ul>
								<div class="col-xs-12 col-md-6">Nuovo componente:
									<?php
									$select_artico = new selectartico("codart");
									$select_artico->addSelected($form['codart']);
									$select_artico->output(substr($form['cosear'], 0,32),'C',"");
									?>
								</div>
								<div class="col-xs-12 col-md-2">
									<input type="submit" class="btn btn-warning" name="OKsub" value="Salva">
								</div>
							</div>
						</div>

					</div><!-- chiude tab-pane  -->

				<div class="col-sm-12">
					<?php
					/** ENRICO FEDELE */
					/* SOlo se non sono in finestra modale */
					if ($modal === false) {
						echo '<div class="col-sm-4 text-left"><input name="none" type="submit" value="" disabled></div>';
					}
					?>
					<div class="col-md-12">
						<div class="col-sm-6 text-center">

							<a class="btn btn-xs btn-danger" onclick="groupErase('<?php echo addslashes($form['id_artico_group']); ?>','<?php echo addslashes($form['descri']); ?>')">  Elimina </a>

						</div>
						<div class="col-sm-6 text-center">
							<input name="Submit" type="submit" class="btn btn-warning" value="<?php echo ucfirst($script_transl[$toDo]);?>" />
						</div>
					</div>
					<?php
				}
				?>
				</div>
			</div> <!-- chiude container -->
		</div><!-- chiude panel -->
</form>

<div class="modal" id="confirm_erase" title="Togli questo articolo dal gruppo">
    <fieldset>
       <div class="compost_name"></div>
    </fieldset>
</div>
<div class="modal" id="confirm_destroy" title="Distruggi questo gruppo">

    <fieldset>
       <div class="group_name"></div>
    </fieldset>
<p>NB: Eliminerai anche i collegamenti alle varianti</p>
</div>
<script type="text/javascript">
    // Basato su: http://www.abeautifulsite.net/whipping-file-inputs-into-shape-with-bootstrap-3/
    $(document).on('change', '.btn-file :file', function () {
        var input = $(this),
                numFiles = input.get(0).files ? input.get(0).files.length : 1,
                label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
        input.trigger('fileselect', [numFiles, label]);
    });
    $(document).ready(function () {
        $('.btn-file :file').on('fileselect', function (event, numFiles, label) {

            var input = $(this).parents('.input-group').find(':text'),
                    log = numFiles > 1 ? numFiles + ' files selected' : label;
            if (input.length) {
                input.val(log);
            } else {
                if (log)
                    alert(log);
            }

        });
    });</script>

<?php
/** ENRICO FEDELE */
/* SOlo se non sono in finestra modale */
if ($modal === false) {
} else {
    ?>
    <script type="text/javascript">
        $("#add-product").submit(function (e) {
            $.ajax({
                type: "POST",
                url: "../../modules/magazz/admin_group.php",
                data: $("#add-product").serialize(), // serializes the form's elements.
                success: function (data) {
                    $("#edit-modal .modal-sm").css('width', '100%');
                    $("#edit-modal .modal-body").html(data);
                }
            });
            e.preventDefault(); // avoid to execute the actual submit of the form.
        });
    </script>
    <?php
}
require("../../library/include/footer.php");
?>
