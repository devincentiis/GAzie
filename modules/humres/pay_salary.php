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

if ( !isset($_POST['hidden_req']) && isset($_GET['id_tes']) && intval($_GET['id_tes']) >= 1 ) { //al primo accesso allo script per update
    $form['id_tes'] = intval($_GET['id_tes']);
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    // recupero la descrizione di default
    require("lang." . $admin_aziend['lang'] . ".php");
    $script_transl = $strScript['pay_salary.php'];
    $form['description'] = $script_transl['description_value'];
	// riprendo la testata per valorizzare la banca
	$tesmov=gaz_dbi_get_row($gTables['tesmov'], 'id_tes', $form['id_tes']);
    $form['target_account'] =$tesmov['clfoco'];
    $form['entry_date'] = gaz_format_date($tesmov['datreg'],false,false);
	$form['transfer_fees_acc'] = 0;
	$form['transfer_fees'] = 0.00;
    $form['description'] = $tesmov['descri'];
	// riprendo i righi per valorizzare eventuali costi e singoli bonifici
	$rs = gaz_dbi_dyn_query("*", $gTables['rigmoc']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['rigmoc'].".codcon = ".$gTables['clfoco'].".codice LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id",
	"id_tes = ".$form['id_tes'], 'id_rig');
	$form['rows']=[];
    while ($r = gaz_dbi_fetch_array($rs)) { // propongo il form degli stipendi in base ai dati presnti sul db
	  if(substr($admin_aziend['mas_staff'],0,3)==substr($r['codice'],0,3)){
		$form['rows'][$r['codice']]['iban']=$r['iban'];
		$form['rows'][$r['codice']]['ragso1']=$r['ragso1'];
		$form['rows'][$r['codice']]['ragso2']=$r['ragso2'];
		$form['rows'][$r['codice']]['check_status']=$r['codice'];
		$form['rows'][$r['codice']]['amount']=$r['import'];
	  }elseif(substr($r['codice'],0,1)=='3'){ // è il conto di costo delle spese
		$form['transfer_fees_acc'] = $r['codice'];
		$form['transfer_fees'] = $r['import'];
	  }
	}
} elseif ( !isset($_POST['hidden_req']) && !isset($_GET['id_tes'])) { //al primo accesso allo script per insert
    $form['id_tes'] = 0;
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['entry_date'] = date("d/m/Y");
    $form['target_account'] = 0;
    $form['transfer_fees_acc'] = 0;
    $form['transfer_fees'] = 0.00;
    // recupero la descrizione di default
    require("lang." . $admin_aziend['lang'] . ".php");
    $script_transl = $strScript['pay_salary.php'];
    $form['description'] = $script_transl['description_value'];
	$rs = gaz_dbi_dyn_query("*", $gTables['staff']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['staff'].".id_clfoco = ".$gTables['clfoco'].".codice LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id",
	" (start_date <= '".gaz_format_date($form['entry_date'],true)."' OR start_date IS NULL) AND (end_date IS NULL OR end_date > '".gaz_format_date($form['entry_date'],true)."' - INTERVAL 3 MONTH OR end_date <= '2010-01-01')", 'id_contract');
	$form['rows']=[];
    while ($r = gaz_dbi_fetch_array($rs)) { // propongo il form degli stipendi in base ai dati presnti sul db
		$lsr = gaz_dbi_dyn_query("*", $gTables['rigmoc']." LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes","codcon = ".$r['codice'], 'datreg DESC',0,1);
		$ls = gaz_dbi_fetch_array($lsr);
		$form['rows'][$r['codice']]['iban']=$r['iban'];
		$form['rows'][$r['codice']]['ragso1']=$r['ragso1'];
		$form['rows'][$r['codice']]['ragso2']=$r['ragso2'];
		$form['rows'][$r['codice']]['check_status']=$r['codice'];
		$form['rows'][$r['codice']]['amount']=($ls)?$ls['import']:0;
	}
} elseif (isset($_POST['id_tes'])) { // accessi successivi
    $form['id_tes'] = intval($_POST['id_tes']);
    $form['hidden_req'] = htmlentities($_POST['hidden_req']);
    $form['ritorno'] = $_POST['ritorno'];
    $form['entry_date'] = substr($_POST['entry_date'], 0, 10);
    $form['target_account'] = intval($_POST['target_account']);
    $form['description'] = substr($_POST['description'], 0, 100);
	$form['rows']=[];
    // ----- INIZIO CONTROLLI FORMALI -----
	foreach( $_POST['rows'] as $k => $v) {
		$form['rows'][$k]['ragso1']= $v['ragso1'];
		$form['rows'][$k]['ragso2']= $v['ragso2'];
		$form['rows'][$k]['iban']= substr($v['iban'],0,27);
		$form['rows'][$k]['amount']= number_format($v['amount'],2,'.','');
		$form['rows'][$k]['check_status']= (isset($_POST['rows'][$k]['check_status']))?$k:false;
		if ($v['amount'] < 1 && isset($_POST['rows'][$k]['check_status'])){ // importo troppo basso
			$msg['err'][] = 'nopay';
		}
	}
    if ($form['target_account'] < 100000000) { // no ho selezionato il conto di adebito
        $msg['err'][] = 'noacc';
    }
    $ed = gaz_format_date($form['entry_date'], 2);
    if ($ei > $ef) {
        $msg['err'][] = 'expif';
    }
    // ----- FINE CONTROLLI FORMALI -----
    $bank_data = gaz_dbi_get_row($gTables['clfoco'], 'codice', $form['target_account']);
    if (!isset($_POST['ins'])) {
        if ($bank_data['maxrat'] >= 0.01 && $_POST['transfer_fees'] < 0.01) { // se il conto corrente bancario prevede un addebito per bonifici allora lo propongo
            $form['transfer_fees_acc'] = $bank_data['cosric'];
            $form['transfer_fees'] = $bank_data['maxrat'];
        } elseif (substr($form['target_account'], 0, 3) == substr($admin_aziend['cassa_'], 0, 3)) {
            $form['transfer_fees_acc'] = 0;
            $form['transfer_fees'] = 0.00;
        } else {
            $form['transfer_fees_acc'] = intval($_POST['transfer_fees_acc']);
            $form['transfer_fees'] = floatval($_POST['transfer_fees']);
        }
    } else {
        $form['transfer_fees_acc'] = intval($_POST['transfer_fees_acc']);
        $form['transfer_fees'] = floatval($_POST['transfer_fees']);
        if (count($msg['err']) <= 0) { // non ci sono errori, posso procedere
          // inserisco i dati postati
          $newValue = array('caucon' => 'BBH',
              'descri' => $form['description'],
              'id_doc' => 0,
              'datreg' => gaz_format_date($form['entry_date'], TRUE),
              'seziva' => 0,
              'protoc' => 0,
              'numdoc' => '',
              'datdoc' => gaz_format_date($form['entry_date'], TRUE),
              'clfoco' => $form['target_account'],
              'regiva' => 0,
              'operat' => 0
          );
		  if ($form['id_tes']==0) { // ho un inserimento
			$tes_id = tesmovInsert($newValue);
		  } else {
			tesmovUpdate(array('id_tes',$form['id_tes']),$newValue);
			gaz_dbi_del_row($gTables['rigmoc'],'id_tes',$form['id_tes']);// cancello i vecchi righi e li reinserisco da capo
			$tes_id = $form['id_tes'];
		  }
		  $tot = 0.00;
          foreach ($form['rows'] as $k => $v) {
				if($v['check_status']){
					$tot += $v['amount'];
					$rig_id = rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'D', 'codcon' => $k, 'import' => $v['amount']));
				}
          }
          if ($form['transfer_fees'] >= 0.01 && $form['transfer_fees_acc'] > 100000000) { // ho le spese bancarie
            rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'D', 'codcon' => $form['transfer_fees_acc'], 'import' => $form['transfer_fees']));
			$tot += $form['transfer_fees'];
          }
          rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'A', 'codcon' => $form['target_account'], 'import' => round($tot, 2)));
		  header("Location: report_pay_salary.php?id_tes=".$tes_id.'&xml');
          exit;
        }
    }
}
require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new humresForm();
$upd=($form['id_tes']>0)?'_upd':'';
?>
<script type="text/javascript">
    $(function () {
		var button_text = $("#preventDuplicate").attr('btn-text');
        $("#entry_date").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
        $('input:checkbox,input[name*="amount"]').on('change', function () {
            var sum = 0;
            var nbo = 0;
            $('input[name*="amount"]').each(function () {
				var checkid = $(this).attr('chk_id');
				var tal = parseFloat($(this).val()) || 0.00;
				if ($('#'+checkid).is(':checked') && $('#'+checkid).is(':enabled')) {
					sum = sum + tal;
                    nbo ++;
				}
            });

			$("#preventDuplicate").attr('value',button_text + nbo + ' tot.€ '+((Math.round(sum * 100) / 100).toFixed(2)).toString().replace(".", ",") );
        }).trigger("change");
        $("#checkPayr").click(function () {
            $('input:checkbox.check_payr').not(this).prop('checked', this.checked);
        });
    });
</script>
<form role="form" method="post" name="pay_riba" enctype="multipart/form-data" >
    <input type="hidden" value="<?php echo $form['id_tes'] ?>" name="id_tes" />
    <input type="hidden" value="<?php echo $form['hidden_req'] ?>" name="hidden_req" />
    <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno">
    <div class="text-center">
        <p><b><?php echo $script_transl['title'.$upd]; ?></b></p>
    </div>
    <?php
    if (count($msg['err']) > 0) { // ho un errore
        $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
    }
    if (count($msg['war']) > 0) { // ho un alert
        $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
    }
    ?>
    <div class="panel panel-default gaz-table-form">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="entry_date" class="col-sm-4 control-label"><?php echo $script_transl['entry_date']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="entry_date" name="entry_date" value="<?php echo $form['entry_date']; ?>">
                        </div>
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="target_account" class="col-sm-4 control-label"><?php echo $script_transl['target_account']; ?></label>
                        <div class="col-sm-8">
                            <?php
                            $select_bank = new selectconven("target_account");
                            $select_bank->addSelected($form['target_account']);
                            $select_bank->output($admin_aziend['masban'], false, true, 'target_account');
                            ?>
                        </div>
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="transfer_fees_acc" class="col-sm-4 control-label"><?php echo $script_transl['transfer_fees_acc']; ?></label>
                        <div class="col-sm-8">
                            <?php
                            $gForm->selectAccount('transfer_fees_acc', $form['transfer_fees_acc'], 3, '', false, "col-sm-6 small");
                            ?>
                        </div>
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="transfer_fees" class="col-sm-4 control-label"><?php echo $script_transl['transfer_fees']; ?></label>
                        <div class="col-sm-4">
                            <input type="number" step="0.01" min="0.00" max="100" class="form-control" id="transfer_fees" name="transfer_fees" placeholder="<?php echo $script_transl['transfer_fees']; ?>" value="<?php echo $form['transfer_fees']; ?>">
                        </div>
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="description" class="col-sm-4 control-label"><?php echo $script_transl['description']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="description" name="description" value="<?php echo $form['description']; ?>">
                        </div>
                    </div>
                </div>
            </div><!-- chiude row  -->
        </div> <!-- chiude container -->
    </div><!-- chiude panel -->
   <div class="panel panel-default gaz-table-form">
        <div class="container-fluid">
            <div class="row">
				<div class="col-sm-12">
					<div class="col-xs-11 text-right">Seleziona</div>
					<div class="col-xs-1">
                        <input type="checkbox" id="checkPayr">
                    </div>
                </div>
            </div><!-- chiude row  -->
        </div> <!-- chiude container -->
    </div><!-- chiude panel -->
    <div class="panel panel-default gaz-table-form">
        <div class="container-fluid">
            <?php
			$tot=0.00;
            foreach( $form['rows'] as $k => $v) {
				$tot+=$v['amount'];
                $class = 'check_other';
				if (strlen($v['iban'])==27) {
                    $class = 'check_payr';
                }
				echo '<input type="hidden" value="'.$v['ragso1'].'" name="rows['.$k.'][ragso1]" />';
				echo '<input type="hidden" value="'.$v['ragso2'].'" name="rows['.$k.'][ragso2]" />';
				echo '<input type="hidden" value="'.$v['iban'].'" name="rows['.$k.'][iban]" />';
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="entry_date" class="col-xs-7 control-label">
							<?php
							if (!strlen($v['iban'])==27){
								echo '<a class="btn btn-xs btn-danger" title="Il collaboratore non ha l\'IBAN" href="./admin_staff.php?codice='.intval(substr($k,3,6)).'&Update">NO IBAN</a>';
							}
							echo $v['ragso1'].' '.$v['ragso2']; ?><div class="text-right">
							Importo:</div>
                        </label>
                        <div class="col-xs-4"><input type="number" step="0.01" min="0.00" max="99999.99" chk_id="<?php echo $k; ?>" name="rows[<?php echo $k?>][amount]" value="<?php echo $v['amount'];?>" class="text-right"/>
                        </div>
						<div class="col-xs-1 text-right">
							<input type="checkbox" class="<?php echo $class; ?>" value="<?php echo $v['check_status']; ?>" id="<?php echo $k; ?>" name="rows[<?php echo $k?>][check_status]" <?php echo (!strlen($v['iban'])==27)?'disabled title="Inserire IBAN del fornitore"':''; echo ($v['check_status'])?'checked':false; ?>>
						</div>

						</div>
					</div>
					</div><!-- chiude row  -->
                <?php
            }
            ?>
            <div class="row">
                <div class="col-xs-12 text-center">
                <input class="btn btn-warning" id="preventDuplicate" onClick="chkSubmit();" type="submit" name="ins" value="insert" btn-text="<?php echo $script_transl['confirm_entry'.$upd].' '.$script_transl['total']; ?>"/>
                </div>
            </div><!-- chiude row  -->
        </div> <!-- chiude container -->
    </div><!-- chiude panel -->
</form>

<?php
require("../../library/include/footer.php");
?>
