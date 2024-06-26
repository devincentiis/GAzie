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
$admin_aziend=checkAdmin();
$msg = array('err' => array(), 'war' => array());
if ((isset($_POST['Update'])) or (isset($_GET['Update']))) {
    if (!isset($_GET['codice'])) {
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit;
    } else {
        $form['codice'] = substr($_GET['codice'],0,3);
    }
    $toDo = 'update';
} elseif ((isset($_POST['Insert'])) or (isset($_GET['Insert']))) {
    $toDo = 'insert';
} else {
    $toDo = '';
}

if (!isset($_POST['Update']) and isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
    $cau = gaz_dbi_get_row($gTables['caucon'],"codice",$form["codice"]);
    $form["descri"] = $cau["descri"];
    $form["insdoc"] = $cau["insdoc"];
    $form["regiva"] = $cau["regiva"];
    $form["operat"] = $cau["operat"];
    $form["n_rows"] = 0;
    //recupero i righi
    $rs_rows = gaz_dbi_dyn_query("*", $gTables['caucon_rows'], "caucon_cod = '" . $cau["codice"] . "'",'n_order');
    $i = 0;
    while ($row = gaz_dbi_fetch_array($rs_rows)) {
        $i++;
        $form['rows'][$i]['clfoco_mastro'] = substr($row['clfoco_ref'],0,3).'000000';
        $form['clfoco_sub'.$i] = $row['clfoco_ref'];
        $form['search']['clfoco_sub' .$i]  = '';
        $form['rows'][$i]['type_imp'] = $row['type_imp'];
        $form['rows'][$i]['dare_avere'] = $row['dare_avere'];
        $form["n_rows"] = $i;
    }
} elseif (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
    $form['ritorno'] = $_POST['ritorno'];
    $form['hidden_req']=htmlentities($_POST['hidden_req']);
    $form["codice"] = strtoupper(substr($_POST["codice"],0,3));
    $form["descri"] = substr($_POST["descri"],0,50);
    $form["insdoc"] = intval($_POST["insdoc"]);
    $form["regiva"] = intval($_POST["regiva"]);
    $form["operat"] = intval($_POST["operat"]);
    $form["n_rows"] = intval($_POST["n_rows"]);
	$del=false;
    $chk_acc=false;
    for ($i = 1; $i <= $form['n_rows']; $i++) {
		if ($del){ // sottraggo all'indice perché è avvenuta una eliminazione
			$j=$i-1;
		} else {
			$j=$i;
		}
		$form['rows'][$j]['clfoco_mastro'] = intval($_POST['rows'][$i]['clfoco_mastro']);
        $form['clfoco_sub'.$j] = intval($_POST['clfoco_sub'.$i]);
        $form['search']['clfoco_sub' .$j] = substr($_POST['search']['clfoco_sub' .$i],0,20);
        $form['rows'][$j]['type_imp'] = substr($_POST['rows'][$i]['type_imp'], 0, 1);
        $form['rows'][$j]['dare_avere'] = substr($_POST['rows'][$i]['dare_avere'], 0, 1);
		// creo il valore di clfoco_ref servirà per i controlli e per passare al database
		$form['rows'][$j]['clfoco_ref'] = max(array($form['rows'][$j]['clfoco_mastro'],$form['clfoco_sub'.$j]));
		if ($form['rows'][$j]['clfoco_ref']<100000000 && $chk_acc==false) {
			$chk_acc=true;
		}
		if (isset($_POST['del'])) { // Se viene inviata la richiesta di eliminazione rigo
			$delri = key($_POST['del']);
			if ($i == $delri){
				unset($form['clfoco_sub' . $i]);
				unset($form['search']['clfoco_sub' . $i]);
				unset($form['rows'][$i]);
				$del=true; // da questo momento sottraggo all'indice
			}
		}
	}
	if ($del){ // sottraggo dal numero righi perché è avvenuta una eliminazione
		$form["n_rows"]--;
	}

	if (isset($_POST['insrow'])) {    // Se viene inviata la richiesta di inserimento rigo
		$form["n_rows"]++;
        $form['rows'][$form["n_rows"]]['clfoco_mastro'] = 0;
        $form['clfoco_sub'.$form["n_rows"]] = 0;
        $form['search']['clfoco_sub' . $form["n_rows"]] = '';
        $form['rows'][$form["n_rows"]]['type_imp'] = '';
        $form['rows'][$form["n_rows"]]['dare_avere'] = '';
	}
	if (isset($_POST['confirm'])) {
		if ($toDo == 'insert') {  //se è un'inserimento
         if ($chk_acc) $msg['err'][] = "clfoco_ref";
         if (empty($form["descri"])) $msg['err'][] = "descri";
         if (!empty($form["codice"])) {
            $rs_cau = gaz_dbi_dyn_query("*", $gTables['caucon'], "codice = '".$form["codice"]."'","codice DESC",0,1);
            $rs = gaz_dbi_fetch_array($rs_cau);
            if ($rs) {
                 $msg['err'][] = "codice_exi";
            }
            switch ($form["codice"]) {
                   case "CHI":
                     $msg['err'][] = "CHI";
                   break;
                   case "APE":
                     $msg['err'][] = "APE";
                   break;
                   case "AMM":
                     $msg['err'][] = "AMM";
                   break;
            }
         } else {
           $msg['err'][] = "codice_emp";
         }
         if (count($msg['err']) < 1) {// nessun errore
            gaz_dbi_table_insert('caucon',$form);
			// inserisco tutti i righi
		    for ($i = 1; $i <= $form['n_rows']; $i++) {
				// aggiungo il codice e ordine all'array
				$form['rows'][$i]['caucon_cod'] = $form['codice'];
				$form['rows'][$i]['n_order'] = $i;
				gaz_dbi_table_insert('caucon_rows',$form['rows'][$i]);
			}
            header("Location: report_caucon.php");
            exit;
         }
       } else { //è una modifica
         if (empty($form["descri"])) $msg['err'][] = "descri";
         if ($chk_acc) $msg['err'][] = "clfoco_ref";
         if (count($msg['err']) < 1) {// nessun errore
            // aggiorno il db
            gaz_dbi_table_update('caucon',$form['codice'],$form);
			// prima elimino tutti i vecchi righi che si riferivano allo stesso codice...
            gaz_dbi_del_row($gTables['caucon_rows'], 'caucon_cod', $form['codice']);
			// ... e poi inserisco tutti i nuovi
		    for ($i = 1; $i <= $form['n_rows']; $i++) {
				// aggiungo il codice e ordine all'array
				$form['rows'][$i]['caucon_cod'] = $form['codice'];
				$form['rows'][$i]['n_order'] = $i;
				gaz_dbi_table_insert('caucon_rows',$form['rows'][$i]);
			}
            header("Location: report_caucon.php");
            exit;
         }
       }
    } elseif (isset($_POST['return'])) {
        header("Location: ".$_POST['ritorno']);
        exit;
    }

} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
    $form["codice"] = "";
    $form["descri"] = "";
    $form["insdoc"] = 0;
    $form["regiva"] = 0;
    $form["operat"] = 0;
    $form["n_rows"] = 0;
}
require("../../library/include/header.php");
$script_transl=HeadMain();
?>
<script type="text/javascript">
    $(function () {
		$('.dropdownmenustyle').selectmenu({ change: function( event, ui ) {  this.form.submit(); }});
	});
</script>
<?php

echo "<form method=\"POST\" name=\"select\">\n";
echo "<input type=\"hidden\" value=\"".$form['hidden_req']."\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\" />\n";
echo "<input type=\"hidden\" value=\"\" name=\"".ucfirst($toDo)."\">\n";
$gForm = new contabForm();
if ($toDo == 'insert') {
   echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['ins_this']."</div>\n";
} else {
   echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['upd_this']." '".$form['codice']."'</div>\n";
   echo "<input type=\"hidden\" value=\"".$form['codice']."\" name=\"codice\" />\n";
}
if (count($msg['err']) > 0) { // ho un errore
    $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}

?>
    <div class="panel panel-default gaz-table-form">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="codice" class="col-sm-6 control-label"><?php echo $script_transl['codice']; ?></label>
                        <input class="col-sm-6" type="text" value="<?php echo $form['codice']; ?>" name="codice" maxlength="3" />
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="descri" class="col-sm-6 control-label"><?php echo $script_transl['descri']; ?></label>
                        <input class="col-sm-6" type="text" value="<?php echo $form['descri']; ?>" name="descri" maxlength="50" />
					</div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="insdoc" class="col-sm-6 control-label"><?php echo $script_transl['insdoc']; ?></label>
                        <?php
                        $gForm->variousSelect('insdoc', $script_transl['insdoc_value'], $form['insdoc'], "col-sm-6", true, '', false, 'style="max-width: 100px;"');
                        ?>
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="regiva" class="col-sm-6 control-label"><?php echo $script_transl['regiva']; ?></label>
                        <?php
                        $gForm->variousSelect('regiva', $script_transl['regiva_value'], $form['regiva'], "col-sm-6", true, '', false, 'style="max-width: 200px;"');
                        ?>
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="operat" class="col-sm-6 control-label"><?php echo $script_transl['operat']; ?></label>
                        <?php
                        $gForm->variousSelect('operat', $script_transl['operat_value'], $form['operat'], "col-sm-6", true, '', false, 'style="max-width: 200px;"');
                        ?>
                    </div>
                </div>
            </div><!-- chiude row  -->
        </div><!-- chiude container  -->
    </div><!-- chiude panel  -->
    <div class="panel panel-info">
        <div class="container-fluid">
<?php
	echo '<input type="hidden" value="'.$form['n_rows'].'" name="n_rows" />';
    for ($k = 1; $k <= $form['n_rows']; $k++) {
			$row_class = 'alternate-row-even';
		    if ($k % 2 == 0) {
				$row_class = 'alternate-row-odd';
			}
			?>
            <div class="row <?php echo $row_class; ?>">
                <div class="col-sm-6 col-md-1 col-lg-1">
                    <div class="form-group">
                        <label for="n_rows" class="col-sm-6 control-label">n.</label>
                        <?php
						echo $k;
                        ?>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="clfoco_mastro" class="col-sm-6 control-label"><?php echo $script_transl['clfoco_mastro']; ?></label>
                        <?php
						$gForm->selMasterAcc('rows['.$k.'][clfoco_mastro]', $form['rows'][$k]['clfoco_mastro'], "rows[$k][clfoco_mastro]",'col-sm-6');
						$gForm->lockSubtoMaster($form['rows'][$k]["clfoco_mastro"], 'clfoco_sub' . $k);
                        ?>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="clfoco_sub" class="col-sm-6 control-label"><?php echo $script_transl['clfoco_sub']; ?></label>
                        <?php
						$gForm->selSubAccount('clfoco_sub' . $k, $form['clfoco_sub' . $k],
						$form['search']['clfoco_sub' . $k],
						$form['hidden_req'],
						$script_transl['mesg'],'col-sm-6');
                        ?>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2 col-lg-2">
                    <div class="form-group">
                        <label for="tipim" class="col-sm-4 control-label"><?php echo $script_transl['tipim']; ?></label>
                        <?php
                        $gForm->variousSelect('rows['.$k.'][type_imp]', $script_transl['tipim_value'], $form['rows'][$k]['type_imp'], "col-sm-8", true, '', false, 'style="max-width: 300px;"');
                        ?>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2 col-lg-2">
                    <div class="form-group">
                        <label for="daav" class="col-sm-6 control-label"><?php echo $script_transl['daav']; ?></label>
                        <?php
                        $gForm->variousSelect('rows['.$k.'][dare_avere]', $script_transl['daav_value'], $form['rows'][$k]['dare_avere'], "col-sm-8", true, '', false, 'style="max-width: 200px;"');
                        ?>
                    </div>
                </div>
                <div class="col-sm-6 col-md-1 col-lg-1">
                    <div class="form-group">
					<?php
					echo '<button type="submit" class="btn-default btn-sm btn-elimina" name="del[' . $k . ']" title="' . $script_transl['delrow'] . '!"><i class="glyphicon glyphicon-trash"></i></button>';
					?>
                    </div>
                </div>
            </div><!-- chiude row  -->
<?php
}
?>
            <div class="row">
                <div class="col-xs-12 text-right">
                  <input class="btn btn-success" tabindex=10 onClick="chkSubmit();" type="submit" name="insrow" value="<?php echo $script_transl['add_row'];?>">
                </div>
			</div><!-- chiude row  -->
        </div><!-- chiude container  -->
	</div><!-- chiude panel  -->
<?php
if ($form['n_rows'] >= 1) {
?>
            <div class="row">
              <div class="col-xs-12 FacetFooterTD text-center">
                <input class="btn btn-warning" tabindex=10 onClick="chkSubmit();" type="submit" name="confirm" value="<?php echo ucfirst($script_transl[$toDo]);?>">
              </div>
	        </div><!-- chiude row  -->

<?php
}
?>
	</form>
<?php
require("../../library/include/footer.php");
?>
