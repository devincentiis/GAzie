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
$admin_aziend=checkAdmin();
//$msg = "";

if ((isset($_POST['Update'])) or (isset($_GET['Update']))) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if ((isset($_POST['Insert'])) or (isset($_POST['Update']))) {   //se non e' il primo accesso
    $form['ritorno'] = $_POST['ritorno'];
    $form['hidden_req'] = $_POST['hidden_req'];
    $form['id'] = intval($_POST['id']);
    $form['description'] = $_POST['description'];
    $form['val'] = $_POST['val'];

    if ($toDo == 'update') {  // modifica
        $codice = array('id',$form['id']);
    	$table = 'company_config';
	    $columns = array( 'description', 'var', 'val' );
		$newValue['description'] = $_POST['category']."|".$_POST['description'];
		$newValue['var'] = "ruburl";
		$newValue['val'] = $_POST['val'];
		if ( substr($newValue['val'],0,4)!="http" ) {
			$newValue['val'] = "https://".$newValue['val'];
		}
		tableUpdate($table, $columns, $codice, $newValue);
        header("Location: ".$form['ritorno']);
        exit;
    } else {                  // inserimento
		$table = 'company_config';
		$columns = array( 'description','var','val' );
		$newValue['description'] = $_POST['category']."|".$_POST['description'];
		$newValue['var'] = "ruburl";
		$newValue['val'] = $_POST['val'];
		if ( substr($newValue['val'],0,4)!="http" ) {
			$newValue['val'] = "https://".$newValue['val'];
		}
		tableInsert($table, $columns, $newValue);
        header("Location: report_ruburl.php");
        exit;
    }
} elseif ((!isset($_POST['Update'])) and (isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req']=''; 
	$rub = gaz_dbi_get_row($gTables['company_config'],'id',intval($_GET['id']));    
    $form['id'] = $rub['id'];
    if ( strpos( $rub['description'],"|" ) ) {
        $valori = explode ("|", $rub['description']);
    } else {
        $valori[0] = "Altro";
        $valori[1] = $rub['description'];
    }
    $form['category'] = $valori[0];
	$form['description'] = $valori[1];
    $form['val'] = $rub['val'];
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req']='';
    $form['id'] = "";
    $form['category'] = "Altro";
	$form['description'] = "";
    $form['val'] = "";     
	$rs_ultima_lettera = gaz_dbi_dyn_query("*", $gTables['company_config'], "var=\"ruburl\"","id desc",0);
    $ultima_lettera = gaz_dbi_fetch_array($rs_ultima_lettera);
    if ($ultima_lettera) {
        $form['id'] = intval($ultima_lettera['id']) + 1;
    } else {
		$form['id'] = 1;
    }
}

require("../../library/include/header.php");
$script_transl=HeadMain();

echo "<form method=\"POST\">";
echo "<input type=\"hidden\" name=\"".ucfirst($toDo)."\" value=\"\">\n";
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\">\n";
echo "<input type=\"hidden\" value=\"".$form['hidden_req']."\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" name=\"\" value=\"".$form['id']."\">\n";
echo "<input type=\"hidden\" name=\"id\" value=\"".$form['id']."\">\n";

if ( !isset($valori) ) {
    $valori = array( "Altro", "");
}
?>
	<div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header with-border">
              <h4 class="box-title"><?php echo $script_transl['subtitle']; ?></h4>

              <div class="box-tools">
              </div>
            </div>

            <div class="box-body">
                <tbody>
                <div class="form-group">
                    <label><?php echo $script_transl['category']; ?></label>
                    <input class="form-control" placeholder="<?php echo $script_transl['inscat']; ?>" value="<?php echo $valori[0]; ?>" name="category" type="text">
                </div>
                <div class="form-group">
                    <label><?php echo $script_transl['description']; ?></label>
                    <input class="form-control" placeholder="<?php echo $script_transl['insdes']; ?>" value="<?php echo $valori[1]; ?>" name="description" type="text">
                </div>
                <div class="form-group">
                    <label><?php echo $script_transl['address']; ?></label>
                    <input class="form-control" placeholder="<?php echo $script_transl['insadd']; ?>" value="<?php echo $form['val']; ?>" name="val" type="text">
                </div>
                </div>
                <div class="box-footer">
                <a type="button" href="report_ruburl.php" class="btn btn-default"><?php echo $script_transl['back']; ?></a><button type="submit" class="btn btn-primary pull-right"><?php echo $script_transl['add']; ?></button>
              </div>
              </tbody>
            </div>
          </div>
        </div>
    </div>
</form>
<?php
require("../../library/include/footer.php");
?>