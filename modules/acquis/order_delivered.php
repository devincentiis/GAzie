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
require("../../modules/magazz/lib.function.php");
$admin_aziend = checkAdmin();
$msg = array('err' => array(), 'war' => array());
$upd_mm = new magazzForm;
$docOperat = $upd_mm->getOperators();
$anagrafica = new Anagrafica();


if (!isset($_POST['id_tes'])) { //al primo accesso  faccio le impostazioni ed il controllo di presenza ordini ricevibili
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
    if (isset($_GET['id_tes'])) { //se è stato richiesto un ordine specifico lo carico
        $form = gaz_dbi_get_row($gTables['tesbro'], "id_tes", intval($_GET['id_tes']));
		$form['numddt'] ='';
		$form['datreg'] =date("d/m/Y");
        $fornitore = $anagrafica->getPartner($form['clfoco']);
        $id_des = $anagrafica->getPartner($form['id_des']);
        $rs_rows = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = " . $form['id_tes'], "id_rig asc");
	    $nr = 0;
        while ($rigo = gaz_dbi_fetch_array($rs_rows)) {
            $articolo = gaz_dbi_get_row($gTables['artico'], "codice", $rigo['codart']);
            $form['rows'][$nr]['id_rig'] = $rigo['id_rig'];
            $form['rows'][$nr]['tiprig'] = $rigo['tiprig'];
            $form['rows'][$nr]['id_tes'] = $rigo['id_tes'];
            $form['rows'][$nr]['tipdoc'] = $form['tipdoc'];
            $form['rows'][$nr]['datemi'] = $form['datemi'];
            $form['rows'][$nr]['numdoc'] = $form['numdoc'];
            $form['rows'][$nr]['descri'] = $rigo['descri'];
            $form['rows'][$nr]['id_orderman'] = $rigo['id_orderman'];
            $form['rows'][$nr]['codart'] = $rigo['codart'];
            $form['rows'][$nr]['unimis'] = $rigo['unimis'];
            $form['rows'][$nr]['prelis'] = $rigo['prelis'];
            $form['rows'][$nr]['provvigione'] = $rigo['provvigione'];
            $form['rows'][$nr]['ritenuta'] = $rigo['ritenuta'];
            $form['rows'][$nr]['sconto'] = $rigo['sconto'];
            $form['rows'][$nr]['quanti'] = $rigo['quanti'];
            // controllo se ci sono dei rows già ricevuti nei rigdoc
            $totale_ricevibile = $rigo['quanti'];
            $rs_ricevuti = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_order = " . $rigo['id_rig'], "id_rig ASC");
            while ($rg_ricevuti = gaz_dbi_fetch_array($rs_ricevuti)) {
                $totale_ricevibile -= $rg_ricevuti['quanti'];
            }
            $form['rows'][$nr]['checkval'] = '';
            if ($totale_ricevibile>=0.00001) {
                // decommentare sotto in caso si voglia che venga selezionato  tutto di default 
                //$form['rows'][$nr]['checkval'] = ' checked ';
            }
            $form['rows'][$nr]['ricevibile'] = $totale_ricevibile;
            $form['rows'][$nr]['totric'] = $totale_ricevibile;
            $form['rows'][$nr]['codvat'] = $rigo['codvat'];
            $form['rows'][$nr]['pervat'] = $rigo['pervat'];
            $form['rows'][$nr]['codric'] = $rigo['codric'];
            $nr ++;
        }
    }
} else { //negli accessi successivi riporto solo il form
    $form['id_tes'] = $_POST['id_tes'];
    $form['seziva'] = $_POST['seziva'];
    $form['clfoco'] = $_POST['clfoco'];
    $form['sconto'] = $_POST['sconto'];
    $form['id_orderman'] = $_POST['id_orderman'];
    $form['pagame'] = $_POST['pagame'];
    $fornitore = $anagrafica->getPartner($form['clfoco']);
    $form['numdoc'] = $_POST['numdoc'];
    $form['numddt'] = $_POST['numddt'];
    $form['tipdoc'] = substr($_POST['tipdoc'], 0, 3);
    $form['datemi'] = substr($_POST['datemi'], 0, 10);
    $form['datreg'] = substr($_POST['datreg'], 0, 10);
    if(isset($_POST['rows'])){
        $form['rows'] = $_POST['rows'];
		foreach($_POST['rows'] as $kr=>$vr){
			$form['rows'][$kr]['checkval']=(isset($vr['checkval']))?' checked ':' ';
		}
    }
}

if (isset($_POST['subdoc'])) { 
    if (!isset($form["rows"])) {
        $msg['err'][] = 'norows';
    } else {
        foreach ($form['rows'] as $k => $v){
			if(isset($v['ricevibile'])&&$v['ricevibile']>$v['totric']&&isset($v['checkval'])&&($v['tiprig']==0||$v['tiprig']==1)){
				$msg['err'][] = 'upres';
			}
        }
    }
    if($form['numddt']==""){
        $msg['err'][] = 'numdoc';
    }
    if (count($msg['err'])==0){//procedo all'inserimento del ddt d'acquisto per chiudere la partita
		$td['tipdoc']='ADT';
		$td['status']='DA ORDINE';
		$td['seziva']=1;
		$td['data_ordine']=$form['datemi'];
		$td['datemi']=$td['datreg']=gaz_format_date($form['datreg'],true);
		$td['numdoc']=$td['numfat']=$form['numddt'];
		$td['clfoco']=$form['clfoco'];
		$td['id_parent_doc'] = $form['id_tes'];
		$td['id_orderman'] = $form['id_orderman'];
		$td['pagame'] = $form['pagame'];
        tesdocInsert($td);
		$last_id=gaz_dbi_last_id();
		foreach ($form['rows'] as $k => $v) {
            if (isset($v['checkval'])&&strlen($v['checkval'])>=2&&isset($v['ricevibile'])&&$v['ricevibile']>=0.00001) {   //se e' un rigo selezionato
                $row = $v;
                unset($row['id_rig']);
                $row['id_tes'] = $last_id;
                $row['id_order'] = $v['id_rig'];
                $row['status'] ='DA ORDINE';
                $row['quanti'] = $v['ricevibile'];
                $rowid=rigdocInsert($row);
                gaz_dbi_put_row($gTables['rigdoc'], "id_rig", $rowid, "id_orderman", $row['id_orderman'] );
                //modifico il rigo dell'ordine indicandoci l'id della testata di questo documento
                gaz_dbi_put_row($gTables['rigbro'], "id_rig", $v['id_rig'], "id_doc", $last_id);
            }
        }
        header("Location: report_broacq.php?flt_tipo=AOR");
        exit;
    }
} elseif (isset($_POST['Return'])) {  //ritorno indietro
    header("Location: " . $_POST['ritorno']);
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('custom/autocomplete'));
$gForm = new acquisForm();
?>
<script type="text/javascript">
    $(function () {
        $("#datreg").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
    });
</script>
<div class="FacetFormHeaderFont">Ricevuto materiale ordinato</div>
<form class="form-horizontal" role="form" method="post" name="tesdoc" enctype="multipart/form-data" >
    <input type="hidden" value="<?php echo $form['id_tes']; ?>" name="id_tes">
    <input type="hidden" value="<?php echo $form['datemi']; ?>" name="datemi">
    <input type="hidden" value="<?php echo $form['tipdoc']; ?>" name="tipdoc">
    <input type="hidden" value="<?php echo $form['seziva']; ?>" name="seziva">
    <input type="hidden" value="<?php echo $form['clfoco']; ?>" name="clfoco">
    <input type="hidden" value="<?php echo $form['numdoc']; ?>" name="numdoc">
    <input type="hidden" value="<?php echo $form['sconto']; ?>" name="sconto">
    <input type="hidden" value="<?php echo $form['id_orderman']; ?>" name="id_orderman">
    <input type="hidden" value="<?php echo $form['pagame']; ?>" name="pagame">
    <div class="text-center">
<?php
    if (count($msg['err']) > 0) { // ho un errore
        $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
    }
    if (count($msg['war']) > 0) { // ho un alert
        $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
    }
?>
    </div>
    <div class="panel panel-default">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="ragso1" class="col-sm-4 control-label" >Fornitore:</label>
                        <div><b><?php echo $fornitore['ragso1'];?></b>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="numdoc" class="col-sm-4 control-label">Ordine numero:</label>
                        <div class="col-sm-8"><b><?php echo $form['numdoc']; ?></b> del <?php echo gaz_format_date($form['datemi']); ?>
						</div>                
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="numddt" class="col-sm-4 control-label"><?php echo $script_transl['numddt']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="numddt" name="numddt" value="<?php echo $form['numddt']; ?>">
                        </div>
                    </div>
                </div>                    
                <div class="col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="datemi" class="col-sm-4 control-label"><?php echo $script_transl['datemi']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="datreg" name="datreg" value="<?php echo $form['datreg']; ?>">
                        </div>
                    </div>
                </div>
            </div> <!-- chiude row  -->
        </div><!-- chiude container  -->
    </div><!-- chiude panel  -->        
<?php	
if (!empty($form['rows'])) {
    $tot = 0;
    foreach ($form['rows'] as $k => $v) {
		$checkin='';
		$artico = gaz_dbi_get_row($gTables['artico'], 'codice', $v['codart']);
        $btn_class = 'btn-success';
        $btn_title = '';
        if ($v['tiprig'] == 0) {
            if ($artico['good_or_service']>0){ 
				$btn_class = 'btn-info';
				$btn_title = 'Servizio';
			} else {
                $btn_class = 'btn-success';
				$btn_title = ' titolo';
            }
        }
        // calcolo importo totale (iva inclusa) del rigo e creazione castelletto IVA
        if ($v['tiprig'] <= 1) {    //ma solo se del tipo normale o forfait
            if ($v['tiprig'] == 0) { // tipo normale
                $tot_row = CalcolaImportoRigo($v['quanti'], $v['prelis'], array($v['sconto'], $form['sconto'], -$v['pervat']));
            } else {                 // tipo forfait
                $tot_row = CalcolaImportoRigo(1, $v['prelis'], -$v['pervat']);
            }
            if (!isset($castel[$v['codvat']])) {
                $castel[$v['codvat']] = 0.00;
            }
            $castel[$v['codvat']]+=$tot_row;
            // calcolo il totale del rigo stornato dell'iva
            $imprig = round($tot_row / (1 + $v['pervat'] / 100), 2);
            $tot+=$tot_row;
        }
	    // fine calcolo importo rigo, totale e castelletto IVA
        // colonne non editabili
        echo "<input type=\"hidden\" value=\"" . $v['codart'] . "\" name=\"rows[$k][codart]\">\n";
        echo "<input type=\"hidden\" value=\"" . $v['tiprig'] . "\" name=\"rows[$k][tiprig]\">\n";
        echo "<input type=\"hidden\" value=\"" . $v['codvat'] . "\" name=\"rows[$k][codvat]\">\n";
        echo "<input type=\"hidden\" value=\"" . $v['pervat'] . "\" name=\"rows[$k][pervat]\">\n";
        echo "<input type=\"hidden\" value=\"" . $v['totric'] . "\" name=\"rows[$k][totric]\">\n";
        echo "<input type=\"hidden\" value=\"" . $v['id_rig'] . "\" name=\"rows[$k][id_rig]\">\n";
        echo "<input type=\"hidden\" value=\"" . $v['id_orderman'] . "\" name=\"rows[$k][id_orderman]\">\n";
        // colonne editabili
        echo "<input type=\"hidden\" value=\"" . $v['descri'] . "\" name=\"rows[$k][descri]\">\n";
        echo "<input type=\"hidden\" value=\"" . $v['unimis'] . "\" name=\"rows[$k][unimis]\">\n";
        echo "<input type=\"hidden\" value=\"" . $v['quanti'] . "\" name=\"rows[$k][quanti]\">\n";
        echo "<input type=\"hidden\" value=\"" . $v['prelis'] . "\" name=\"rows[$k][prelis]\">\n";
        echo "<input type=\"hidden\" value=\"" . $v['sconto'] . "\" name=\"rows[$k][sconto]\">\n";

        // creo l'array da passare alla funzione per la creazione della tabella responsive
        $resprow[$k] = array(
            array('head' => $script_transl["nrow"], 'class' => '',
                'value' => '<button disabled type="image" name="upper_row[' . $k . ']" class="btn btn-default btn-sm">
                            ' . ($k + 1) . '</button>'),
            array('head' => $script_transl["codart"], 'class' => '',
                'value' => ' <button disabled class="btn ' . $btn_class . ' "
				title="' . $script_transl['update'] . $script_transl['thisrow'] . '! ' . $btn_title . '">
                            ' . $v['codart'] . '
                            </button>'
            ),
            array('head' => $script_transl["descri"], 'class' => '',
                'value' =>  $v['descri'] 
            ),
            array('head' => $script_transl["unimis"], 'class' => '',
                'value' => $v['unimis']
            ),
            array('head' => $script_transl["quanti"], 'class' => 'text-right numeric',
                'value' => 'residuo:'.floatval($v['totric']).' <input type="number" step="any" name="rows[' . $k . '][ricevibile]" value="' . $v['ricevibile'] . '" maxlength="11" />'
            ),
            array('head' => $script_transl["prezzo"], 'class' => 'text-right numeric',
                'value' =>  $v['prelis'] 
            ),
            array('head' => $script_transl["sconto"], 'class' => 'text-right numeric',
                'value' => $v['sconto']),
            array('head' => $script_transl["amount"], 'class' => 'text-right numeric', 'value' => gaz_format_number($imprig), 'type' => ''),
            array('head' => $script_transl["codvat"], 'class' => 'text-center numeric', 'value' => $v['pervat'], 'type' => ''),
            array('head' => $script_transl["total"], 'class' => 'text-right numeric bg-warning', 'value' => gaz_format_number($tot_row), 'type' => ''),
            array('head' => 'Sel.', 'class' => 'text-center',
                'value' => '<label class="btn '.$btn_class.'"><input type="checkbox" name="rows['.$k.'][checkval]"  title="' . $script_transl['checkbox'] . '" '.$checkin.' '.$v['checkval'].' value="1" onclick="this.form.total.value=calcheck(this);"></label>')
        );

        switch ($v['tiprig']) {
            case "0":
                break;
            case "1":
                // in caso di rigo forfait non stampo alcune colonne
                $resprow[$k][3]['value'] = ''; //unimis
                $resprow[$k][4]['value'] = ''; //quanti
                // scambio l'input con la colonna dell'importo... 
                $resprow[$k][7]['value'] = $resprow[$k][5]['value'];
                // ... e poi non la visualizzo più
                $resprow[$k][5]['value'] = ''; //prelis
                $resprow[$k][6]['value'] = ''; //sconto
                break;
            case "2":
                $resprow[$k][3]['value'] = ''; //unimis
                $resprow[$k][4]['value'] = ''; //quanti
                $resprow[$k][5]['value'] = ''; //prelis
                $resprow[$k][6]['value'] = ''; //sconto
                $resprow[$k][7]['value'] = ''; //quanti
                $resprow[$k][8]['value'] = ''; //prelis
                $resprow[$k][9]['value'] = '';
                $resprow[$k][10]['value'] = '';
                break;
        }
    }
    $gForm->gazResponsiveTable($resprow, 'gaz-responsive-table');
}
?>
<div class="panel panel-info">
    <div class="container-fluid"><div class="col-xs-1 col-md-3 col-lg-5"></div><div class="col-xs-10 col-md-6 col-lg-2"><input class="btn btn-warning col-xs-12" type="submit" name="subdoc" value="<?php echo $script_transl['confirm']; ?>" /></div><div class="col-xs-1 col-md-3 col-lg-5"></div>
    </div><!-- chiude container  -->
</div><!-- chiude panel  -->        
</form>
<?php
require("../../library/include/footer.php");
?>
