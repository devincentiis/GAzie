<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
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
// ANTONIO GERMANI       >>> gestione uso fitofarmaci <<<

require("../../library/include/datlib.inc.php");

$admin_aziend=checkAdmin();
$msg = "";
$warning="";

if ((isset($_POST['Update'])) or (isset($_GET['Update']))) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}

if ((isset($_GET['Update']) and  !isset($_GET['id'])) or isset($_POST['Return'])) {
    header("Location: ".$_POST['ritorno']);
    exit;
}

if ((isset($_POST['Insert'])) or (isset($_POST['Update']))) {   //se non e' il primo accesso

	if (isset($_POST['Cancel'])){
		$_POST['cod_art'] = "";
		$_POST['codart'] = "";
		$_POST['nome_fito'] = "";
		$_POST['nome_fito'] = "";
		$_POST['id_colt'] = 0;
		$_POST['nome_colt'] = "";
		$_POST['id_avv'] = 0;
		$_POST['nome_avv'] = "";
		$_POST['dose'] = 0;
    $_POST['dose_hl'] = 0;
		$_POST['tempo_sosp'] = 0;
		$_POST['max_tratt'] = 0;
	}
	$_POST['id_colt'] = intval ($_POST['nome_colt']);
	$_POST['id_avv'] = intval ($_POST['nome_avv']);
	$_POST['cod_art'] = $_POST['codart'];
    $form=gaz_dbi_parse_post('camp_uso_fitofarmaci');//ricarico i registri per il form
	$form['nome_colt'] = $_POST['nome_colt'];
	$form['nome_avv'] = $_POST['nome_avv'];
	$form['nome_fito'] = $_POST['nome_fito'];
	$form['max_tratt'] = $_POST['max_tratt'];

	if ($form['nome_fito']){
		$form['numero_registrazione'] = gaz_dbi_get_row($gTables['camp_fitofarmaci'], "PRODOTTO", $form['nome_fito'])['NUMERO_REGISTRAZIONE'];
		if (intval($form['numero_registrazione'])>0){
			 $row = gaz_dbi_get_row($gTables['artico'], "id_reg", $form['numero_registrazione']);
			$form['cod_art'] = ($row)?$row['codice']:'';
		} else {
			$form['nome_fito']="";
		}
	} elseif ($form['cod_art']){
		$form['numero_registrazione'] = gaz_dbi_get_row($gTables['artico'], "codice", $form['cod_art'])['id_reg'];
		if (intval($form['numero_registrazione'])>0){
			$form['nome_fito'] = gaz_dbi_get_row($gTables['camp_fitofarmaci'], "NUMERO_REGISTRAZIONE", $form['numero_registrazione'])['PRODOTTO'];
		} else {
			$form['cod_art']=$_POST['codart'];
		}
	}
	if (($form['cod_art'] AND $form['nome_fito']) OR($form['cod_art']=="" AND $form['nome_fito']=="" )) {

	} else {
		$warning="NoGazie";
	}

    // Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {

		if ($toDo == 'insert') { // controllo se il codice esiste se e' un inserimento
			$rscheck = gaz_dbi_dyn_query("*", $gTables['camp_uso_fitofarmaci'], "NUMERO_REGISTRAZIONE = '".$_POST['numero_registrazione']."' AND id_colt = '".intval($_POST['nome_colt'])."' AND id_avv ='".intval($_POST['nome_avv'])."'" ,2,0,1);
			if ($rscheck->num_rows > 0){ // controllo se è stata giè inserita questa dose specifica
			    $msg .= "6+";
			}
		}
		if (isset ($form['id_colt'])){ // controllo coltivazione vuota
			if (intval ($form['id_colt'])== 0) {
			$msg .= "7+";
			} else {
				$rs_ctrl = gaz_dbi_get_row($gTables['camp_colture'],'id_colt',$form['id_colt']);
					if (empty ($rs_ctrl)){
				$msg .= "7+";
					}
				}
		} else {
			$msg .= "7+";
		}
       if (empty($form['cod_art'])){  // controllo codice articolo vuoto
             $msg .= "8+";
       } else {
			$rs_ctrl = gaz_dbi_get_row($gTables['artico'],'codice',$form['cod_art']);
				if (empty($rs_ctrl)){
				$msg .= "11+";
				}
			}
	   if (isset ($form['id_avv'])){ // controllo avversità vuota
			if (intval ($form['id_avv'])== 0) {
			$msg .= "9+";
			} else {
				$rs_ctrl = gaz_dbi_get_row($gTables['camp_avversita'],'id_avv',$form['id_avv']);
					if (empty($rs_ctrl)){
				$msg .= "9+";
					}
				}
		} else {
			$msg .= "9+";
		}
		if ($form['dose']==0 && $form['dose_hl']==0){
			$msg .= "12+";
		}

		if ($msg == "") {// nessun errore

			if ($toDo == 'update') { // e' una modifica

			$query="UPDATE " . $gTables['camp_uso_fitofarmaci'] . " SET max_tratt='". $form['max_tratt'] ."', cod_art ='"  .$form['cod_art']. "', id_colt ='" . $form['id_colt'] . "', id_avv = '".$form['id_avv']. "', dose = '".$form['dose']. "', dose_hl = '".$form['dose_hl']. "', tempo_sosp = '".$form['tempo_sosp']."', NUMERO_REGISTRAZIONE = '".$form['numero_registrazione']."' WHERE id ='". $form['id'] ."'";
			gaz_dbi_query ($query) ;
			header("Location: ".$_POST['ritorno']);
			exit;

			} else { // e' un'inserimento
				gaz_dbi_table_insert('camp_uso_fitofarmaci',$form);
				$form['id_colt'] = 0;
				$form['nome_colt'] = "";
				$form['id_avv'] = 0;
				$form['nome_avv'] = "";
				$form['dose'] = 0;
        $form['dose_hl'] = 0;
				$form['tempo_sosp'] = 0;
				$form['max_tratt'] = 0;
				$warning="inserito";
			}
			//header("Location: ".$_POST['ritorno']);
			//exit;
		}
	}
} elseif ((!isset($_POST['Update'])) and (isset($_GET['Update']))) { //se e' il primo accesso per update
    $form = gaz_dbi_get_row($gTables['camp_uso_fitofarmaci'],"id",$_GET['id']);
    $form['ritorno'] = $_POST['ritorno'];
	$colt = gaz_dbi_get_row($gTables['camp_colture'],"id_colt",$form['id_colt']);
	$form['nome_colt'] = $form['id_colt']." - ".$colt['nome_colt'];
	$avv = gaz_dbi_get_row($gTables['camp_avversita'],"id_avv",$form['id_avv']);
	$form['nome_avv'] = $form['id_avv']." - ".$avv['nome_avv'];
	$form['nome_fito'] = gaz_dbi_get_row($gTables['camp_fitofarmaci'], "NUMERO_REGISTRAZIONE", $form['numero_registrazione'])['PRODOTTO'];

} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
	// controllo se la tabella DB fitofarmaci è popolata
	$warning="";
	$query="SELECT * FROM ".$gTables['camp_fitofarmaci']. " LIMIT 1";
	$checkdbfito = gaz_dbi_query($query);
	if ($checkdbfito -> num_rows ==0) {
		$warning="NoFito";
	}
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['id'] = 0;
  $form['cod_art'] = "";
  $form['id_colt'] = 0;
	$form['nome_colt'] = "";
	$form['id_avv'] = 0;
	$form['nome_avv'] = "";
	$form['dose'] = 0;
  $form['dose_hl'] = 0;
	$form['tempo_sosp'] = 0;
	$form['nome_fito'] = "";
	$form['numero_registrazione'] = 0;
	$form['max_tratt'] = 0;
}

require("../../library/include/header.php");
$script_transl = HeadMain();
if ($toDo == "update") {
   $title = ucwords($script_transl[$toDo].$script_transl[0])." n.".$form['id'];
} else {
   $title = ucwords($script_transl[$toDo].$script_transl[0]);
}
?>
<script>
<!-- Antonio Germani - chiude automaticamente tutti gli alert autodism -->
$(document).ready(function () {
	window.setTimeout(function() {
		$(".autodism").fadeTo(1000, 0).slideUp(500, function(){
			$(this).remove();
		});
	}, 3000);
});

<!-- Antonio Germani inizio script autocompletamento dalla tabella mysql fitofarmaci	-->
	$(document).ready(function(){
	//Autocomplete search using PHP, MySQLi, Ajax and jQuery
	//generate suggestion on keyup
		$('#nomefito').keyup(function(e){
			e.preventDefault();
			var form = $('#add-product').serialize();
			$.ajax({
				type: 'GET',
				url: 'do_search.php',
				data: form,
				dataType: 'json',
				success: function(response){
					if(response.error){
						$('#product_search').hide();
					}
					else{
						$('#product_search').show().html(response.data);
					}
				}
			});
		});
		//fill the input
		$(document).on('click', '.dropdown-item', function(e){
			e.preventDefault();
			$('#product_search').hide();
			var fullname = $(this).data('fullname');
			$('#nomefito').val(fullname);
			$('#add-product').submit();
		});
	});
<!-- fine autocompletamento -->
<!-- Antonio Germani inizio script autocompletamento dalla tabella mysql artico	-->
	$(document).ready(function(){
	//Autocomplete search using PHP, MySQLi, Ajax and jQuery
	//generate suggestion on keyup
		$('#codart').keyup(function(e){
			e.preventDefault();
			var form = $('#add-product').serialize();
			$.ajax({
				type: 'POST',
				url: 'do_search.php',
				data: form,
				dataType: 'json',
				success: function(response){
					if(response.error){
						$('#codart_search').hide();
					}
					else{
						$('#codart_search').show().html(response.data);
					}
				}
			});
		});
		//fill the input
		$(document).on('click', '.dropdown-item2', function(e){
			e.preventDefault();
			$('#codart_search').hide();
			var fullname = $(this).data('fullname');
			$('#codart').val(fullname);
			$('#add-product').submit();
		});
	});
<!-- fine autocompletamento -->
<!-- script per popup -->
	var stile = "top=10, left=10, width=600, height=800 status=no, menubar=no, toolbar=no scrollbar=no";
	   function Popup(apri) {
	      window.open(apri, "", stile);
	   }

<!-- Antonio Germani inizio script autocompletamento dalla tabella mysql camp_coltura	-->
	$(document).ready(function() {
	$("input#autocomplete2").autocomplete({
		source: [<?php
	$stringa="";
	$query="SELECT * FROM ".$gTables['camp_colture'];
	$result = gaz_dbi_query($query);
	while($row = $result->fetch_assoc()){
		$stringa.="\"".$row['id_colt']." - ".$row['nome_colt']."\", ";
	}
	$stringa=substr($stringa,0,-2);
	echo $stringa;
	?>],
		minLength:2,
	select: function(event, ui) {
        //assign value back to the form element
        if(ui.item){
            $(event.target).val(ui.item.value);
        }
        //submit the form
        //$(event.target.form).submit();
    }
	});
	});
 <!-- fine autocompletamento -->

<!-- Antonio Germani inizio script autocompletamento dalla tabella mysql camp_avversita	-->
	$(document).ready(function() {
	$("input#autocomplete3").autocomplete({
		source: [<?php
	$stringa="";
	$query="SELECT * FROM ".$gTables['camp_avversita'];
	$result = gaz_dbi_query($query);
	while($row = $result->fetch_assoc()){
		$stringa.="\"".$row['id_avv']." - ".$row['nome_avv']."\", ";
	}
	$stringa=substr($stringa,0,-2);
	echo $stringa;
	?>],
		minLength:2,
	select: function(event, ui) {
        //assign value back to the form element
        if(ui.item){
            $(event.target).val(ui.item.value);
        }
        //submit the form
        //$(event.target.form).submit();
    }
	});
	});

 <!-- fine autocompletamento -->
</script>

<!--   >>>>>>>>>>>    inizio FORM            >>>>>>>>>>  -->
<form method="POST" enctype="multipart/form-data" id="add-product">
<div align="center" class="FacetFormHeaderFont">
	<?php echo $title; ?>
</div>
<div class="panel panel-default gaz-table-form div-bordered">
	<div class="container-fluid">
		<?php
		if ($warning == "NoFito"){ // se non c'è, bisogna creare il data base fitofarmaci
			?>
			<div class="alert alert-warning alert-dismissible" style="max-width: 70%; margin-left: 15%;">
				<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
				<strong>Warning!</strong> Il database Fitofarmaci non esiste. E' necessario crearlo <a  href="javascript:Popup('../../modules/camp/update_fitofarmaci.php')"> Crea database Fitofarmaci <i class="glyphicon glyphicon-import" style="color:green" ></i></a>
			</div>
			<?php
		}
		if ($warning == "NoGazie"){ // Articolo non presente in GAzie
			?>
			<div class="alert alert-warning alert-dismissible" style="max-width: 70%; margin-left: 15%;">
				<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
				<strong>Warning!</strong> Questo fitofarmaco non esiste in GAzie. Per utilizzarlo è necessario inserirlo <a  href="javascript:Popup('../../modules/camp/camp_admin_artico.php?Insert')"> Inserisci Fitofarmaco <i class="glyphicon glyphicon-import" style="color:green" ></i></a>
			</div>
			<?php
		}
		if ($warning == "inserito"){ // Dose fitofarmaco correttamente inserita
			?>
			<div class="autodism alert alert-success alert-dismissible" style="max-width: 70%; margin-left: 15%;">
				<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
				<strong>OK!</strong> Inserimento avvenuto correttamente
			</div>
			<?php
		}
		?>
		<input type="hidden" name="<?php echo ucfirst($toDo); ?>" value="">
		<input type="hidden" value="<?php echo $_POST['ritorno']; ?>" name="ritorno">
		<input type="hidden" value="<?php echo $form['numero_registrazione']; ?>" name="numero_registrazione">


		<?php
		if (!empty($msg)) {
			$message = "";
			$rsmsg = array_slice( explode('+',chop($msg)),0,-1);
			foreach ($rsmsg as $value){
					$message .= $script_transl['error']."! -> ";
					$rsval = explode('-',chop($value));
					foreach ($rsval as $valmsg){
							$message .= $script_transl[$valmsg]." ";
					}
					$message .= "<br />";
			}
		?>
		<div class="row bg-info">
			<div colspan="5" class="FacetDataTDred">
				<?php echo $message; ?>
			</div>
		</div> <!-- row -->
		<?php
		}
		?>
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label class="FacetFieldCaptionTD"><?php echo $script_transl[1]; ?>
					</label>
					<input type="hidden" name="id" value="<?php echo $form['id']; ?>" /><?php echo $form['id']; ?>
				</div>
			</div>
		</div> <!-- row -->

		<!-- inizio inserisci articolo   -->
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label class="col-sm-4 FacetFieldCaptionTD">
						<?php
						//echo $script_transl[2];
            echo "FITOFARMACO";
						?>
					</label>
					<input class="col-sm-8 FacetSelect" type="hidden" id="nomefito" name="nome_fito" value="<?php echo $form['nome_fito']; ?>" placeholder="Ricerca nome fitofarmaco" autocomplete="off" tabindex="1">
					<ul class="dropdown-menu" style="left: 20%; padding: 0px;" id="product_search"></ul>
				</div>
			</div>
		</div> <!-- row -->

		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label class="col-sm-4 FacetFieldCaptionTD">
						<?php
						echo "Codice articolo fitofarmaco";
						?>
					</label>
					<input class="col-sm-8 FacetSelect" type="text" id="codart" name="codart" value="<?php echo $form['cod_art']; ?>" placeholder="Ricerca codice articolo fitofarmaco" autocomplete="off" tabindex="2">
					<ul class="dropdown-menu" style="left: 20%; padding: 0px;" id="codart_search"></ul>
				</div>
			</div>
		</div> <!-- row -->

		<!-- inizio inserisci coltura  -->
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label class="col-sm-4 FacetFieldCaptionTD">
						<?php echo $script_transl[3]; ?>
					</label>
					<input class="col-sm-8 FacetSelect" id="autocomplete2" type="text" value="<?php echo $form['nome_colt']; ?>" name="nome_colt" maxlength="50" tabindex="3">
					<input type="hidden" value="<?php echo intval ($form['nome_colt']); ?>" name="id_colt"/>
				</div>
			</div>
		</div> <!-- row --> <!-- per funzionare autocomplete, id dell'input deve essere autocomplete2 -->

		<!-- inizio inserisci avversita  -->
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label class="col-sm-4 FacetFieldCaptionTD">
						<?php echo $script_transl[4]; ?>
					</label>
					 <input class="col-sm-8 FacetSelect" id="autocomplete3" type="text" value="<?php echo $form['nome_avv']; ?>" name="nome_avv" maxlength="50" tabindex="4">
					 <input type="hidden" value="<?php echo intval ($form['nome_avv']); ?>" name="id_avv"/>
				</div>
			</div>
		</div> <!-- row --> <!-- per funzionare autocomplete, id dell'input deve essere autocomplete3 -->

		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label class="col-sm-4 FacetFieldCaptionTD">
						<?php echo $script_transl[5]; ?>
					</label>
					<input class="col-sm-3" type="text" name="dose" value="<?php echo number_format ($form['dose'],$admin_aziend['decimal_price'], ',', ''); ?>" maxlength="8"  tabindex="5"/>
					<?php
					$res2 = gaz_dbi_get_row($gTables['artico'], 'codice', $form['cod_art']);
					echo ($res2)?$res2['uniacq']:'';
					?>
					/ha
				</div>
			</div>
		</div> <!-- row -->
    <div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label class="col-sm-4 FacetFieldCaptionTD">
						<?php echo $script_transl[5]; ?>
					</label>
					<input class="col-sm-3" type="text" name="dose_hl" value="<?php echo number_format ($form['dose_hl'],$admin_aziend['decimal_price'], ',', ''); ?>" maxlength="8"  tabindex="5"/>
					<?php
					$res2 = gaz_dbi_get_row($gTables['artico'], 'codice', $form['cod_art']);
					echo ($res2)?$res2['uniacq']:'';
					?>
					/hl
				</div>
			</div>
		</div> <!-- row -->
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label class="col-sm-4 FacetFieldCaptionTD">
						<?php echo $script_transl[10]; ?>
					</label>
					<input class="col-sm-3" type="text" name="tempo_sosp" value="<?php echo $form['tempo_sosp']; ?>" maxlength="2"  tabindex="6"/>
					gg
				</div>
			</div>
		</div> <!-- row -->
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label class="col-sm-4 FacetFieldCaptionTD">
						Numero massimo di trattamenti per coltura:
					</label>
					<input class="col-sm-3" type="number" name="max_tratt" value="<?php echo $form['max_tratt'];?>" maxlength="8" tabindex="7"/>
				</div>
			</div>
		</div> <!-- row -->
		<div class="row">
		<?php
		if ($toDo !== 'update') {
			?>
			<input type="submit" class="pull-left" name="Cancel" value="<?php echo $script_transl['cancel']; ?>">
			<?php
		}
		?>

		<input type="submit" name="Return" value="<?php echo $script_transl['return']; ?>">
		<?php
		if ($toDo == 'update') {
			?>
		   <input type="submit" class="pull-right" accesskey="m" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="<?php echo ucfirst($script_transl['update'])?>" tabindex="8">


			<?php
		} else {
			?>
			<input type="submit" class="pull-right" accesskey="i" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="<?php echo ucfirst($script_transl['insert'])?>" tabindex="8">
			<?php
		}
		?>
		</div> <!-- row -->
	</div> <!-- container  -->
</div> <!-- panel  -->
</form>

<?php
require("../../library/include/footer.php");
?>
