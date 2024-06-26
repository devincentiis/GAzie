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
require("../../library/include/datlib.inc.php");

$admin_aziend=checkAdmin();
$msg = "";

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
    $form=gaz_dbi_parse_post('campi');
	$form['nome_colt'] = $_POST['nome_colt'];
	$form['id_colture']= intval ($_POST['nome_colt']);
	$form['zona_vulnerabile'] = $_POST['zona_vulnerabile'];
	$form['limite_azoto_zona_vulnerabile']=$_POST['limite_azoto_zona_vulnerabile'];
	$form['limite_azoto_zona_non_vulnerabile']=$_POST['limite_azoto_zona_non_vulnerabile'];
	// Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {
       if (! empty($_FILES['userfile']['name'])) {
          if (!( $_FILES['userfile']['type'] == "image/png" ||
               $_FILES['userfile']['type'] == "image/x-png" ||
               $_FILES['userfile']['type'] == "image/jpeg" ||
               $_FILES['userfile']['type'] == "image/jpg" ||
               $_FILES['userfile']['type'] == "image/gif" ||
               $_FILES['userfile']['type'] == "image/x-gif"))
              $msg .= '8+';
              // controllo che il file non sia più; grande di 300kb
          if ( $_FILES['userfile']['size'] > 307200)
              $msg .= '9+';
       }else{
		   $form['userfile']="";
	   }

       if ($toDo == 'insert') { // e' un inserimento, controllo se il codice esiste
          $rs_ctrl = gaz_dbi_get_row($gTables['campi'],'codice',$form['codice']);
          if ($rs_ctrl){
             $msg .= "6+";
          }
       }
       if (empty($form['descri'])){  //descrizione vuota
             $msg .= "7+";
       }
	   if (empty($form['ricarico'])){  //dimensione vuota
             $msg .= "10+";
       }
       if ($msg == "") {// nessun errore
          // preparo la stringa dell'immagine
          if ($_FILES['userfile']['size'] > 0) { //se c'e' una nuova immagine nel buffer
             $form['image'] = file_get_contents($_FILES['userfile']['tmp_name']);
          } else {   // altrimenti riprendo la vecchia
             $oldimage = gaz_dbi_get_row($gTables['campi'],'codice',$form['codice']);
             $form['image'] = $oldimage['image'];
          }
          if ($toDo == 'update') { // e' una modifica
            gaz_dbi_table_update('campi',$form["codice"],$form);
          } else { // e' un'inserimento
            $form['giorno_decadimento']='0000-00-00 00:00:00';
			gaz_dbi_table_insert('campi',$form);
          }
          header("Location: ".$_POST['ritorno']);
          exit;
       }
  }
} elseif ((!isset($_POST['Update'])) and (isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $campi = gaz_dbi_get_row($gTables['campi'],"codice",$_GET['codice']);
	if (isset($campi['used_from_modules']) && strlen($campi['used_from_modules'])==0){
		$form['used_from_modules']=$module;
	}else{
		$form['used_from_modules']=$campi['used_from_modules'];
	}
    $form['ritorno'] = $_POST['ritorno'];
	$form['userfile'] = "";
    $form['codice'] = $campi['codice'];
    $form['descri'] = $campi['descri'];
    $form['web_url'] = $campi['web_url'];
	$form['id_colture'] = $campi['id_colture'];
	$form['zona_vulnerabile']=$campi['zona_vulnerabile'];
	$form['limite_azoto_zona_vulnerabile']=$campi['limite_azoto_zona_vulnerabile'];
	$form['limite_azoto_zona_non_vulnerabile']=$campi['limite_azoto_zona_non_vulnerabile'];
	$colt = gaz_dbi_get_row($gTables['camp_colture'],"id_colt",$form['id_colture']);
	if (isset($colt)){
	$form['nome_colt'] = $form['id_colture']." - ". $colt['nome_colt'];
	} else {
		$form['nome_colt']="";
	}
    $form['annota'] = $campi['annota'];
    $form['ricarico'] = str_replace('.', ',',$campi["ricarico"]);
	$form['giorno_decadimento'] =$campi['giorno_decadimento'];
	$form['codice_prodotto_usato'] =$campi['codice_prodotto_usato'];
	$form['id_mov'] =$campi['id_mov'];
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $rs_ultimo_codice = gaz_dbi_dyn_query("*", $gTables['campi'], 1 ,'codice desc',0,1);
    $ultimo_codice = gaz_dbi_fetch_array($rs_ultimo_codice);
    $form['codice'] = $ultimo_codice['codice']+1;
    $form['descri'] = '';
    $form['ricarico'] = 0;
    $form['web_url']='';
	$form['id_colture']= 0;
	$form['zona_vulnerabile']=0;
	$form['limite_azoto_zona_vulnerabile']=170;
	$form['limite_azoto_zona_non_vulnerabile']=340;
	$form['nome_colt']="";
    $form['annota'] = '';
	$form['giorno_decadimento'] ='0000-00-00 00:00:00';
	$form['codice_prodotto_usato'] ='';
	$form['id_mov'] ='';
	$form['userfile'] = "";
	$form['used_from_modules']=$module;
}
require("../../library/include/header.php");
$script_transl = HeadMain();
if ($toDo == 'update') {
   $title = ucwords($script_transl[$toDo].$script_transl[0])." n.".$form['codice'];
} else {
   $title = ucwords($script_transl[$toDo].$script_transl[0]);
}
print "<form method=\"POST\" enctype=\"multipart/form-data\">\n";

?>
<div class="panel panel-default gaz-table-form">
    <div class="container-fluid">
<?php
print "<div align=\"center\" class=\"lead\"><h1>$title</h1></div>";
print "<input type=\"hidden\" name=\"".ucfirst($toDo)."\" value=\"\">\n";
print "<input type=\"hidden\" value=\"".$_POST['ritorno']."\" name=\"ritorno\">\n";
print "<input type=\"hidden\" value=\"".$form['used_from_modules']."\" name=\"used_from_modules\">\n";
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
    echo "<div class=\"alert alert-warning\">".$message."</div>";
}
if ($toDo == 'update') {
	?>
	<div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="codice" class="col-sm-4 control-label"><?php echo $script_transl[1]; ?></label>
                    <input class="col-sm-8" type="hidden" value="<?php echo $form['codice']; ?>" name="codice" maxlength="3" />
					<label><?php echo $form['codice']; ?></label>
                </div>
            </div>
    </div><!-- chiude row  -->
	<?php
} else {
	?>
	<div class="row">
	<div class="col-md-12">
        <div class="form-group">
            <label for="codice" class="col-sm-4 control-label"><?php echo $script_transl[1]; ?></label>
            <input class="col-sm-8" type="text" value="<?php echo $form['codice']; ?>" name="codice" maxlength="3" />
		</div>
    </div>
	</div><!-- chiude row  -->
	<?php
}
?>
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="descri" class="col-sm-4 control-label"><?php echo $script_transl[2]; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['descri']; ?>" name="descri" maxlength="50" />
                </div>
            </div>
        </div><!-- chiude row  -->
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
					<img class="col-sm-2" src="../root/view.php?table=campi&value=<?php echo $form['codice'];?>" width="100">
                    <label for="userfile" class="col-sm-5 control-label"><?php echo $script_transl[3]; ?></label>
                    <input class="col-sm-5" type="file" value="<?php echo (isset($form['userfile']))?$form['userfile']:''; ?>" name="userfile" maxlength="50" />
                </div>
            </div>
        </div><!-- chiude row  -->
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ricarico" class="col-sm-4 control-label"><?php echo $script_transl[4]; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['ricarico']; ?>" name="ricarico" maxlength="5" />
                </div>
            </div>
        </div><!-- chiude row  -->
		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="web_url" class="col-sm-4 control-label"><?php echo $script_transl['web_url']; ?></label>
                    <input class="col-sm-8" type="text" value="<?php echo $form['web_url']; ?>" name="web_url" maxlength="255" />
                </div>
            </div>
        </div><!-- chiude row  -->

<?php


/* Antonio Germani -  COLTURA */
?>
<!-- Antonio Germani inizio script autocompletamento dalla tabella mysql camp_colture	-->
  <script>
	$(document).ready(function() {
	$("input#autocomplete4").autocomplete({
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
        $(event.target.form).submit();
    }
	});
	});
  </script>
 <!-- fine autocompletamento -->

		<div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="nome_colt" class="col-sm-4 control-label"><?php echo $script_transl[5]; ?></label>
                    <input id="autocomplete4" class="col-sm-8" type="text" value="<?php echo $form['nome_colt']; ?>" name="nome_colt" maxlength="50" />
					<input type="hidden" value="<?php echo intval ($form['nome_colt']); ?>" name="id_colture"/>
                </div>
            </div>
        </div><!-- chiude row  -->

<!-- fine coltura -->

	<div class="row">
        <div class="col-md-12">
            <div class="form-group">
				<label for="zona_vulnerabile" class="col-sm-4 control-label"><?php echo $script_transl['13']; ?></label>
				<label >Sì</label>
				<input  type="radio" name="zona_vulnerabile" value="1" <?php if ($form['zona_vulnerabile']==1){echo "checked";}?> >
				<label >No</label>
				<input  type="radio" name="zona_vulnerabile" value="0" <?php if ($form['zona_vulnerabile']==0){echo "checked";}?> >
			</div>
        </div>
    </div><!-- chiude row  -->
	<div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="limite_azoto_zona_vulnerabile" class="col-sm-4 control-label"><?php echo $script_transl[14]; ?></label>
                <input class="col-sm-8" type="text" value="<?php echo $form['limite_azoto_zona_vulnerabile']; ?>" name="limite_azoto_zona_vulnerabile" maxlength="3" />
            </div>
        </div>
    </div><!-- chiude row  -->
	<div class="row">
		<div class="col-md-12">
            <div class="form-group">
                <label for="limite_azoto_zona_non_vulnerabile" class="col-sm-4 control-label"><?php echo $script_transl[15]; ?></label>
                <input class="col-sm-8" type="text" value="<?php echo $form['limite_azoto_zona_non_vulnerabile']; ?>" name="limite_azoto_zona_non_vulnerabile" maxlength="3" />
            </div>
        </div>
    </div><!-- chiude row  -->
	<div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="annota" class="col-sm-4 control-label"><?php echo $script_transl[12]; ?></label>
                <input class="col-sm-8" type="text" value="<?php echo $form['annota']; ?>" name="annota" maxlength="50" />
            </div>
        </div>
    </div><!-- chiude row  -->
	<div class="col-sm-6 text-left"><input type="submit" name="Return" value="<?php echo $script_transl['return'] ?>"></div>
<?php



if ($toDo == 'update') {
   print '<div class="col-sm-6"><input type="submit" accesskey="m" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="'.ucfirst($script_transl['update']).'!"></div>';
} else {
   print '<div class="col-sm-6"><input type="submit" accesskey="i" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="'.ucfirst($script_transl['insert']).'!"></div>';
}

?>
	</div><!-- chiude container  -->
</div><!-- chiude panel  -->
</form>
<?php
require("../../library/include/footer.php");
?>
