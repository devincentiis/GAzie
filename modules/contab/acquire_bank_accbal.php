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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
$admin_aziend = checkAdmin();
$msg = array('err' => array(), 'war' => array());
$toDo = 'upload';
$f_ex=false; // visualizza file

$send_fae_zip_package = gaz_dbi_get_row($gTables['company_config'], 'var', 'send_fae_zip_package');

if (!isset($_POST['item_ref'])) { // primo accesso nessun upload
	$form['item_ref'] = '';
	$form['date_ini_D'] = '01';
	$form['date_ini_M'] = date('m', strtotime('last month'));
	$form['date_ini_Y'] = date('Y', strtotime('last month'));
	$form['date_fin_D'] = date('d');
	$form['date_fin_M'] = date('m');
	$form['date_fin_Y'] = date('Y');
	$form['curr_doc'] = 0;
} else { // accessi successivi  
	$form['item_ref'] = filter_var($_POST['item_ref'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$form['date_ini_D'] = '01';
	$form['date_ini_M'] = date('m');
	$form['date_ini_Y'] = date('Y');
	$form['date_fin_D'] = date('d');
	$form['date_fin_M'] = date('m');
	$form['date_fin_Y'] = date('Y');
	if (!isset($_POST['datreg'])){
		$form['datreg'] = date("d/m/Y");
		$form['seziva'] = 1;
	} else {
		$form['datreg'] = substr($_POST['datreg'],0,10);
		$form['seziva'] = intval($_POST['seziva']);
	}
	if (isset($_POST['Submit_file'])) { // conferma invio upload file
        if (!empty($_FILES['userfile']['name'])) {
            if (!( $_FILES['userfile']['type'] == "application/vnd.ms-excel" || $_FILES['userfile']['type'] == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" || $_FILES['userfile']['type'] == "application/vnd.oasis.opendocument.spreadsheet")) {
				$msg['err'][] = 'filmim';
			} else {
                if (move_uploaded_file($_FILES['userfile']['tmp_name'], DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . $_FILES['userfile']['name'])) { // nessun errore
					$form['item_ref'] = $_FILES['userfile']['name'];
				} else { // no upload
					$msg['err'][] = 'no_upload';
				}
			}
		} else if (!empty($_POST['selected_SdI'])) {
			require('../../library/' . $send_fae_zip_package['val'] . '/SendFaE.php');
			$FattF = DownloadFattF(array($admin_aziend['country'].$admin_aziend['codfis'] => array('id_SdI' => $_POST['selected_SdI'])));
			if (!empty($FattF) && is_array($FattF) && file_put_contents( DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . key($FattF), base64_decode($FattF[key($FattF)])) !== FALSE) { // nessun errore
				$form['item_ref'] = key($FattF);
			} else { // no upload
				$msg['err'][] = 'no_upload';
			}
		}
	} else if (isset($_POST['Submit_form'])) { // ho  confermato l'inserimento
		$form['pagame'] = intval($_POST['pagame']);
		$form['new_acconcile'] = intval($_POST['new_acconcile']);
        if ($form['pagame'] <= 0 ) {  // ma non ho selezionato il pagamento
			$msg['err'][] = 'no_pagame';
		}
		// faccio i controlli sui righi
		foreach($_POST as $kr=>$vr){
			if (substr($kr,0,7)=='codvat_' && $vr<=0 && $vr !='000000000') {
				$msg['err'][] = 'no_codvat';
			}	
			if (substr($kr,0,7)=='codric_' && $vr<=0 && $vr !='000000000') {
				$msg['err'][] = 'no_codric';
			}	
		}
	} else if (isset($_POST['Download'])) { // faccio il download dell'allegato
		$name = filter_var($_POST['Download'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment;  filename="'.$name.'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize( DATA_DIR . 'files/tmp/' . $name ));
		readfile( DATA_DIR . 'files/tmp/' . $name );
		exit;
	} else if (isset($_POST['Submit_list'])) { // ho richiesto l'elenco delle fatture passive
		$form['date_ini_D'] = str_pad($_POST['date_ini_D'], 2, '0', STR_PAD_LEFT);
		$form['date_ini_M'] = str_pad($_POST['date_ini_M'], 2, '0', STR_PAD_LEFT);
		$form['date_ini_Y'] = $_POST['date_ini_Y'];
		$form['date_fin_D'] = str_pad($_POST['date_fin_D'], 2, '0', STR_PAD_LEFT);
		$form['date_fin_M'] = str_pad($_POST['date_fin_M'], 2, '0', STR_PAD_LEFT);
		$form['date_fin_Y'] = $_POST['date_fin_Y'];
		$FattF = array();
		$where1 = " tipdoc LIKE 'A%' AND item_ref!='' AND datreg BETWEEN '" . $form['date_ini_Y'] . '-' . $form['date_ini_M'] . '-' . $form['date_ini_D'] . "' AND '" . $form['date_fin_Y'] . '-' . $form['date_fin_M'] . '-' . $form['date_fin_D'] . "'";
		$risultati = gaz_dbi_dyn_query("*", $gTables['files'], $where1);
		if ($risultati) {
			while ($r = gaz_dbi_fetch_array($risultati)) {
				$FattF[] = $r['item_ref'];
			}
		}
		require('../../library/' . $send_fae_zip_package['val'] . '/SendFaE.php');
		$AltreFattF = ReceiveFattF(array($admin_aziend['country'].$admin_aziend['codfis'] => array('fattf' => $FattF, 'ini_date' => $form['date_ini_Y'] . '-' . $form['date_ini_M'] . '-' . $form['date_ini_D'], 'fin_date' => $form['date_fin_Y'] . '-' . $form['date_fin_M'] . '-' . $form['date_fin_D'])));
	}

	$dbfile = gaz_dbi_get_row($gTables['files'], 'item_ref', $form["item_ref"]);
	if ($dbfile && !empty($form['item_ref'])) { // c'è anche sul database, è una modifica
		$toDo = 'update';
		$form['datreg'] = gaz_format_date($dbfile['datreg'], false, false);
		$form['seziva'] = $dbfile['seziva'];
		$msg['err'][] = 'file_exists';
	} elseif (!empty($form['item_ref'])) { // non c'è sul database è un inserimento
		$toDo = 'insert';
		// INIZIO acquisizione e pulizia file xml o p7m
		$file_name = DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . $form['item_ref'];
        $path_info = pathinfo($file_name);
		if (!isset($_POST['datreg'])){
			$form['datreg'] = date("d/m/Y",filemtime($file_name));
		}
        // inizio caricamento contenuto del file
        $sheet = PhpOffice\PhpSpreadsheet\IOFactory::load($file_name);
        $writer = new PhpOffice\PhpSpreadsheet\Writer\Html($sheet);
        $writer->save(DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' .$path_info['filename'].".htm");
        $res = $sheet->getActiveSheet()->toArray(null, true, true, true);
        foreach($res as $i=>$v){ // faccio un primo giro per analizzare e tentare di mappare automaticamente
            // print "<br/>".$i.": ";
            foreach($v as $k=>$vc){ // faccio un primo giro per analizzare e tentare di mappare automaticamente
               // print ' '.$k.'['.$vc.']';
            }
        }
		$f_ex=true;
	} else {
		$toDo = 'upload';
	}

	// definisco l'array dei righi 
	$form['rows'] = array();

	$anagra_with_same_pi = false; // sarà true se è una anagrafica esistente ma non è un fornitore sul piano dei conti 

 
	if ($f_ex) { // non ho errori di file,  faccio altri controlli sul contenuto del file
		// VALORIZZO IL FORM PER LA RICONCILIAZIONE in $form['rows'] in base alla mappatura trovata o imposta
        
        
	}
}

require('../../library/include/header.php');
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup'));
$gForm = new contabForm();
echo "<script type=\"text/javascript\">
var cal = new CalendarPopup();
var calName = '';
function setMultipleValues(y,m,d) {
     document.getElementById(calName+'_Y').value=y;
     document.getElementById(calName+'_M').selectedIndex=m*1-1;
     document.getElementById(calName+'_D').selectedIndex=d*1-1;
}
function setDate(name) {
  calName = name.toString();
  var year = document.getElementById(calName+'_Y').value.toString();
  var month = document.getElementById(calName+'_M').value.toString();
  var day = document.getElementById(calName+'_D').value.toString();
  var mdy = month+'/'+day+'/'+year;
  cal.setReturnFunction('setMultipleValues');
  cal.showCalendar('anchor', mdy);
}
</script>
";
?>
<script type="text/javascript">
    $(function () {
        $("#datreg").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
        $("#datreg,#new_acconcile").change(function () {
            this.form.submit();
        });
    });
</script>
<style>
#htmlSheet table[class^="sheet"] {
    width: 100%;
}
</style>
<div align="center" ><b><?php echo $script_transl['title'];?></b></div>
<form method="POST" name="form" enctype="multipart/form-data" id="add-invoice">
    <input type="hidden" name="item_ref" value="<?php echo $form['item_ref']; ?>">
<?php
	// INIZIO form che permetterà all'utente di interagire per (es.) imputare i vari costi al piano dei conti (contabilità) ed anche le eventuali merci al magazzino
    if (count($msg['err']) > 0) { // ho un errore
        $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
    }
    if (count($msg['war']) > 0) { // ho un alert
        $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
    }

if ($toDo=='insert' || $toDo=='update' ) {
	if ($f_ex){

?>
<div class="panel panel-default">
    <div class="panel-heading">
        <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12"><?php echo $script_transl['head_text1']. '<span class="label label-success">'.$form['item_ref'] .'</span>'.$script_transl['head_text2']; ?>
            </div>
        </div> <!-- chiude row  -->
    </div>
    <div class="panel-body">
        <div class="form-group">
            <div class="form-group col-md-6 col-lg-3 nopadding">
                 <label for="datreg" class="col-form-label"><?php echo $script_transl['datreg']; ?></label>
                 <div>
                     <input type="text" id="datreg" name="datreg" value="<?php echo $form['datreg']; ?>">
                 </div>
            </div>
            <div class="form-group col-md-6 col-lg-3 nopadding">
                 <label for="pagame" class="col-form-label" >Banca</label>
                 <div>
                        <?php
                      //  $select_pagame = new selectpagame("pagame");
                      //  $select_pagame->addSelected($form["pagame"]);
                      //  $select_pagame->output(false, "col-lg-12");
                        ?>
                </div>
            </div>
        </div> <!-- chiude row  -->
    </div>
</div>
<div class="col-xs-12">
  <a class="btn btn-primary" data-toggle="collapse" href="#htmlSheet" role="button" aria-expanded="false" aria-controls="htmlSheet">
    Visualizza il contenuto del file <?php echo $form['item_ref']; ?>
  </a>
 <div class="collapse col-xs-12" id="htmlSheet">
 <?php echo file_get_contents(DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' .$path_info['filename'].".htm"); ?>
 </div>
</div>	
<?php
/*		$rowshead=array();
		$ctrl_ddt='';
		$exist_movmag=false;
		$new_acconcile=$form['new_acconcile'];
		foreach ($form['rows'] as $k => $v) { 
			$k--;
			if (!empty($v['NumeroDDT'])){
				if ($ctrl_ddt!=$v['NumeroDDT']){
					// qui valorizzo il rigo di riferimento al ddt
					$exist_ddt='';
					if ($v['exist_ddt']){ // ho un ddt d'acquisto già inserito 
						$exist_ddt='<span class="warning">- questo DdT &egrave; gi&agrave; stato inserito <a class="btn btn-xs btn-success" href="admin_docacq.php?id_tes='. $v['exist_ddt']['id_tes'] . '&Update"><i class="glyphicon glyphicon-edit"></i>&nbsp;'.$v['exist_ddt']['id_tes'].'</a></span>'; 
						$tipddt=$v['exist_ddt']['tipdoc'];
					} else {
						$tipddt="Ddt";
					}
					$ctrl_ddt=$v['NumeroDDT'];
					$rowshead[$k]='<td colspan=13><b> da '.$tipddt.' n.'.$v['NumeroDDT'].' del '.gaz_format_date($v['DataDDT']).' '.$exist_ddt.'</b></td>';
										
					if ($anomalia!=""){ // La FAE non ha i riferimenti linea nei ddt
						if ($anomalia == "AnomaliaDDT=FAT"){
							$rowshead[$k]='<td colspan=13><p class="text-warning"><b>> ANOMALIA FAE: questa fattura fa riferimento ad un DDT che ha il suo numero e stessa data. E\' da considerarsi come accompagnatoria. <</b></p><p>GAzie acquisirà questo documento come fattura semplice AFA.</p></td>';
						} else if ($anomalia=="AnomaliaExistDdt"){
						$rowshead[$k]='<td colspan=13><p class="text-warning"><b>> ANOMALIA FAE: questa fattura riporta DDT senza però collegare gli articoli ai rispettivi DDT <</b></p><p>GAzie è in grado di rimediare a patto che i DDT già inseriti non differiscano dalla FAE.</p></td>';
						} else {
							$rowshead[$k]='<td colspan=13><p class="text-warning"><b>> ANOMALIA FAE: questa fattura riporta DDT senza però collegare gli articoli ai rispettivi DDT <</b></p><p>Se è presente più di un DDT, prima di inserire la FAE, si consiglia di inserire manualmente i DDT altrimenti verrà creato automaticamente un unico ddt di raggruppamento anomalo.</p></td>';
						}						
					}					
				}				
			} else if (!empty($ctrl_ddt)){
				$ctrl_ddt='';
				$rowshead[$k]='<td colspan=13> senza riferimento a DdT</td>';
			}
			
			if ($new_acconcile>100000000){
				$form['codric_'.$k]=$new_acconcile;
			}
            $codric_dropdown = $gForm->selectAccount('codric_'.$k, $form['codric_'.$k], array('sub',1,3), '', false, "col-sm-12 small",'style="max-width: 350px;"', false, true);
			$codvat_dropdown = $gForm->selectFromDB('aliiva', 'codvat_'.$k, 'codice', $form['codvat_'.$k], 'aliquo', true, '-', 'descri', '', 'col-sm-12 small', null, 'style="max-width: 350px;"', false, true);
			$codart_dropdown = $gForm->concileArtico('codart_'.$k,'codice',$form['codart_'.$k]);
			//forzo i valori diversi dalla descrizione a vuoti se è descrittivo
			if (abs($v['prelis'])<0.00001){ // siccome il prezzo è a zero mi trovo di fronte ad un rigo di tipo descrittivo 
				$v['codice_fornitore'] = '';
				$v['unimis'] = '';
				$v['quanti'] = '';
				$v['unimis'] = '';
				$v['prelis'] = '';
				$v['sconto'] = '';
				$v['amount'] = '';
				$v['ritenuta'] = '';
				$v['pervat'] = '';
				$codric_dropdown = '<input type="hidden" name="codric_'.$k.'" value="000000000" />';
				$codvat_dropdown = '<input type="hidden" name="codvat_'.$k.'" value="000000000" />';
				$codart_dropdown = '<input type="hidden" name="codart_'.$k.'" />';
			} else {
				//$v['prelis']=gaz_format_number($v['prelis']);
				$v['amount']=gaz_format_number($v['amount']);
				$v['ritenuta']=floatval($v['ritenuta']);
				$v['pervat']=floatval($v['pervat']);
			}		
			// creo l'array da passare alla funzione per la creazione della tabella responsive
            $resprow[$k] = array(
                array('head' => $script_transl["nrow"], 'class' => '',
                    'value' => $k+1),
                array('head' => $script_transl["codart"], 'class' => '',
                    'value' => $v['codice_fornitore']),
                array('head' => $script_transl["codart"], 'class' => '',
                    'value' => $codart_dropdown),
                array('head' => $script_transl["descri"], 'class' => 'col-sm-12 col-md-3 col-lg-3',
                    'value' => $v['descri']),
                array('head' => $script_transl["unimis"], 'class' => '',
                    'value' => $v['unimis']),
                array('head' => $script_transl["quanti"], 'class' => 'text-right numeric',
                    'value' => $v['quanti']),
                array('head' => $script_transl["prezzo"], 'class' => 'text-right numeric',
                    'value' => $v['prelis']),
                array('head' => $script_transl["sconto"], 'class' => 'text-right numeric',
                    'value' => $v['sconto']),
                array('head' => $script_transl["amount"], 'class' => 'text-right numeric', 
					'value' => $v['amount'], 'type' => ''),
                array('head' => $script_transl["conto"], 'class' => 'text-center numeric', 
					'value' => $codric_dropdown, 'type' => ''),
                array('head' => $script_transl["tax"], 'class' => 'text-center numeric', 
					'value' => $codvat_dropdown, 'type' => ''),
                array('head' => '%', 'class' => 'text-center numeric', 
					'value' => $v['pervat'], 'type' => ''),
                array('head' => 'Ritenuta', 'class' => 'text-center numeric', 
					'value' => $v['ritenuta'], 'type' => '')
            );

		}
		$gForm->gazResponsiveTable($resprow, 'gaz-responsive-table', $rowshead);
*/
?>	   
</form>
<br>
<?php
	}
	if (substr($_SESSION['theme'],-2)!='te'){ 
		/* se non ho "lte" come motore di interfaccia allora richiamo subito il footer
		 * della pagina e poi visualizzo l'xml altrimenti non mi fa il submit del form */
		require("../../library/include/footer.php");
	}
	if (substr($_SESSION['theme'],-3)=='lte'){ 
		// footer  richiamato alla fine in caso di utilizzo di lte 
		require("../../library/include/footer.php");
	}
} else { // all'inizio chiedo l'upload di un file xml o p7m 
?>
<div class="panel panel-default gaz-table-form">
	<div class="container-fluid">
       <div class="row">
           <div class="col-md-12">
               <div class="form-group">
                   <label for="image" class="col-sm-4 control-label">Seleziona il file ( xls o xlsx o ods )</label>
                   <div class="col-sm-8">File: <input type="file" accept=".xls,.xlsx,.ods" name="userfile" />
				   </div>
               </div>
           </div>
       </div><!-- chiude row  -->
<?php
if (!empty($send_fae_zip_package['val']) && $send_fae_zip_package['val']!='pec_SDI') {
?>
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label for="image" class="col-sm-4 control-label">o consulta il canale telematico</label>
					<div class="col-sm-8">
						<br />
						<div class="col-sm-6">dal
<?php
						$gForm->CalendarPopup('date_ini', $form['date_ini_D'], $form['date_ini_M'], $form['date_ini_Y'], 'FacetSelect', 1);
?>
						</div>
						<div class="col-sm-6">al
<?php
						$gForm->CalendarPopup('date_fin', $form['date_fin_D'], $form['date_fin_M'], $form['date_fin_Y'], 'FacetSelect', 1);
?>
						</div>
						<div class="col-sm-12 text-center"><input name="Submit_list" type="submit" class="btn btn-success" value="VISUALIZZA" />
						</div>
					</div>
				</div>
			</div>
		</div><!-- chiude row  -->
<?php
	if (!empty($AltreFattF)) {
?>
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label for="image" class="col-sm-4 control-label">Scegli la fattura da acquisire</label>
					<div class="col-sm-12">
						<br />
<?php
		if (is_array($AltreFattF)) {
			echo "<table class=\"Tlarge table table-striped table-bordered table-condensed\">";
			echo '<tr><th>Seleziona</th><th>Id SdI</th><th>Ricezione</th><th>Fornitore</th><th>Numero</th><th>Data</th></tr>';
			foreach ($AltreFattF as $AltraFattF) {
				echo '<tr>';
				echo '<td align="center"><input type="radio" name="selected_SdI" value="' . $AltraFattF[0] . '" /></td>';
				echo '<td>' . implode('</td><td>', $AltraFattF) . '</td>';
				echo '</tr>';
			}
			echo '</table><br />';
		} else {
			echo '<p>' . print_r($AltreFattF, true) . '</p>';
		}
?>
					</div>
				</div>
			</div>
		</div><!-- chiude row  -->
<?php
	}
}
?>
		<div class="col-sm-12 text-right"><input name="Submit_file" type="submit" class="btn btn-warning" value="<?php echo $script_transl['btn_acquire']; ?>" />
		</div>
		<br /><br />
	</div> <!-- chiude container -->
</div><!-- chiude panel -->
<?php 
	require("../../library/include/footer.php");
}
?>

