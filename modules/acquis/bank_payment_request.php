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
$anagrafica = new Anagrafica();

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['paymov'] = array();
    $form['entry_date'] = date("d/m/Y");
    $form['expiry_ini'] = date("d/m/Y");
    $form['expiry_fin'] = date("t/m/Y");
    $form['orderby'] = 0;
    $form['target_account'] = 0;
    $form['transfer_fees_acc'] = 0;
    $form['transfer_fees'] = 0.00;
    // recupero la descrizione di default
    require("lang." . $admin_aziend['lang'] . ".php");
    $script_transl = $strScript['bank_payment_request.php'];
    $form['description'] = $script_transl['description_value'];
} else { // accessi successivi
    $first = false;
    $form['hidden_req'] = htmlentities($_POST['hidden_req']);
    $form['ritorno'] = $_POST['ritorno'];
    $form['entry_date'] = substr($_POST['entry_date'], 0, 10);
    $form['expiry_ini'] = substr($_POST['expiry_ini'], 0, 10);
    $form['expiry_fin'] = substr($_POST['expiry_fin'], 0, 10);
    $form['orderby'] = intval($_POST['orderby']);
    $form['target_account'] = intval($_POST['target_account']);
    $form['description'] = substr($_POST['description'], 0, 100);
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

        // ----- INIZIO CONTROLLI FORMALI -----
        if ($form['target_account'] < 100000000) { // no ho selezionato il conto di adebito
            $msg['err'][] = 'noacc';
        }
        if (!isset($_POST['pay'])) {
            $msg['err'][] = 'nopay';
        }
        $ed = gaz_format_date($form['entry_date'], 2);
        $ei = gaz_format_date($form['expiry_ini'], 2);
        $ef = gaz_format_date($form['expiry_fin'], 2);
        if ($ei > $ef) {
            $msg['err'][] = 'expif';
        }
        // ----- FINE CONTROLLI FORMALI -----

        if (count($msg['err']) <= 0) { // non ci sono errori, posso procedere
            $paymov = new Schedule;
            // inserisco i dati postati
            $newValue = array('caucon' => 'BBA',
                'descri' => $form['description'],
                'id_doc' => 0,
                'datreg' => gaz_format_date($form['entry_date'], TRUE),
                'seziva' => 0,
                'protoc' => 0,
                'numdoc' => '',
                'datdoc' => gaz_format_date($form['entry_date'], TRUE),
                'clfoco' => 0,
                'regiva' => 0,
                'operat' => 0
            );
            $tes_id = tesmovInsert($newValue);
            $tot = 0.00;
            foreach ($_POST['pay'] as $k => $v) {
                $tot += $v;
                $rig_id = rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'D', 'codcon' => intval($_POST['clfoco'][$k]), 'import' => $v));
                $paymov_value = array('id_tesdoc_ref' => substr($k, 0, strpos($k, '.')),
                    'id_rigmoc_pay' => $rig_id,
                    'amount' => $v,
                    'expiry' => $newValue['datreg']);
                paymovInsert($paymov_value);
            }
            if ($form['transfer_fees'] >= 0.01 && $form['transfer_fees_acc'] > 100000000) { // ho le spese bancarie 
                rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'D', 'codcon' => $form['transfer_fees_acc'], 'import' => $form['transfer_fees']));
				if (TRUE) {//TO-DO: IN ANAGRAFICA CONTO CORRENTE CREARE OPZIONE PER CONTABILIZZAZIONE UNIFICATA O SU RIGA SEPARATA DELLE COMMISSIONI BANCARIE
					rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'A', 'codcon' => $form['target_account'], 'import' => round($form['transfer_fees'], 2)));
				} else {
					$tot += $form['transfer_fees'];
				}
            }
            rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'A', 'codcon' => $form['target_account'], 'import' => round($tot, 2)));
			header("Location: report_schedule_acq.php?id_tes=".$tes_id.'&xml');
            exit;
        }
    }
}
require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new acquisForm();
?>
<script type="text/javascript">
    $(function () {
        $("#entry_date,#expiry_ini, #expiry_fin").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
        $("#expiry_ini, #expiry_fin").change(function () {
            this.form.submit();
        });
        $('input:checkbox').on('change', function () {
            var sum = 0;
            $('.check_other,.check_payr').each(function () {
                if (this.checked)
                    sum = sum + parseFloat($(this).val());
            });
            $('#total').text((Math.round(sum * 100) / 100).toFixed(2))
        }).trigger("change");
        $("#checkAll").click(function () {
            $('input:checkbox').not(this).prop('checked', this.checked);
        });
        $("#checkPayr").click(function () {
            $('input:checkbox.check_payr').not(this).prop('checked', this.checked);
        });
        $("#checkOther").click(function () {
            $('input:checkbox.check_other').not(this).prop('checked', this.checked);
        });
    });
</script>
<form role="form" method="post" name="pay_riba" enctype="multipart/form-data" >
    <input type="hidden" value="<?php echo $form['hidden_req'] ?>" name="hidden_req" />
    <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno">
    <div class="text-center">
        <p><b><?php echo $script_transl['title']; ?></b></p>
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
                        <label for="expiry_ini" class="col-sm-4 control-label"><?php echo $script_transl['expiry_ini']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="expiry_ini" name="expiry_ini" tabindex=1 value="<?php echo $form['expiry_ini']; ?>">
                        </div>
                    </div>
                </div>
            </div><!-- chiude row  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="expiry_fin" class="col-sm-4 control-label"><?php echo $script_transl['expiry_fin']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="expiry_fin" name="expiry_fin" tabindex=2 value="<?php echo $form['expiry_fin']; ?>">
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
<?php
echo '<div class="table-responsive"><table class="Tmiddle table table-striped table-bordered table-condensed table-responsive">
			<tr>
				<td class="FacetFieldCaptionTD">' . $script_transl['orderby'] . '</td>
				<td  class="FacetDataTD">';
$gForm->variousSelect('orderby', $script_transl['orderby_value'], $form['orderby'], 'FacetSelect', 0, 'orderby');
echo '			</td>
			</tr>';
echo '		  </table></div>';
?>
    <div class="panel panel-default gaz-table-form">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xs-12">
                    <div class="col-sm-2">
                        <?php echo $script_transl['sel_payr']; ?> <input type="checkbox" id="checkPayr">
                    </div>
                    <div class="col-sm-2">
                        <?php echo $script_transl['sel_other']; ?> <input type="checkbox" id="checkOther">
                    </div>
                    <div class="col-sm-2">
                        <?php echo $script_transl['sel_all']; ?> <input type="checkbox" id="checkAll">
                    </div>
                    <div class="col-sm-2"><?php echo $script_transl['total']; ?></div>
                    <div class="col-sm-1 pull-right" id="total">0.00</div>
                </div>
            </div><!-- chiude row  -->
            <?php
            $paymov = new Schedule;
            switch ($form['orderby']) {
                case 1:
                    $orderby = 'expiry DESC';
                    break;
                case 2:
                    $orderby = 'amount ASC';
                    break;
                case 3:
                    $orderby = 'amount DESC';
                    break;
                default:
                    $orderby = 'expiry ASC';
            }
            $rs = gaz_dbi_dyn_query("*", $gTables['paymov'], "expiry BETWEEN '" . gaz_format_date($form['expiry_ini'], true) . "' AND '" . gaz_format_date($form['expiry_fin'], true) . "' AND id_rigmoc_doc >= 1 GROUP BY id_tesdoc_ref, expiry", $orderby);
            while ($r = gaz_dbi_fetch_array($rs)) {
                $doc_data = $paymov->getDocFromID($r['id_rigmoc_doc']);
                $status = $paymov->getAmount($r['id_tesdoc_ref'], $r['expiry']);
                if ((substr($doc_data['clfoco'], 0, 3)==$admin_aziend['masfor'] || substr($doc_data['codcon'], 0, 3)==$admin_aziend['masfor']) &&
                        $status >= 0.01) { // considero solo i fornitori non saldati 
                	if (substr($doc_data['clfoco'], 0, 3)!=$admin_aziend['masfor'] && substr($doc_data['codcon'], 0, 3)==$admin_aziend['masfor']) {
                    	$doc_data['clfoco'] = $doc_data['codcon'];
                    }
                   	$fornitore = $anagrafica->getPartner($doc_data['clfoco']);
                    $pagamento = gaz_dbi_get_row($gTables['pagame'], 'codice', $fornitore['codpag']);
                    $class = 'check_other';
					if ($pagamento['tippag'] == 'O') {
                        $class = 'check_payr'; 
                    }
                    ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="entry_date" class="col-sm-11 control-label">
								<?php
								if (!strlen($fornitore['iban'])==27){
									echo '<a class="btn btn-xs btn-danger" title="Il fornirore non ha l\'IBAN" href="./admin_fornit.php?codice='.intval(substr($fornitore['codice'],3,6)).'&Update">NO IBAN</a>';
								}
								echo $fornitore['ragso1'] . ' ' . $fornitore['ragso2']; 
								?>
                                    <a class="btn btn-xs btn-edit" title="<?php echo $script_transl['upd_entry']; ?>" href="../contab/admin_movcon.php?id_tes=<?php echo $doc_data['id_tes']; ?>&Update">
                                        <?php echo ' ' . $doc_data['descri'] . ' ' . $doc_data['numdoc'] . ' del ' . gaz_format_date($doc_data['datdoc']) . ' prot.' . $doc_data['protoc']; ?>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-1 pull-right">
                                <input type="checkbox" class="<?php echo $class; ?>" value="<?php echo $status; ?>" id="<?php echo $r['id_tesdoc_ref']; ?>" name="pay[<?php echo $r['id_tesdoc_ref'].'.'.$r['expiry']; ?>]" <?php echo (!strlen($fornitore['iban'])==27)?'disabled title="Inserire IBAN del fornitore"':''; ?>>
                                <input type="hidden" value="<?php echo $r['expiry']; ?>" name="expires[<?php echo $r['id_tesdoc_ref'].'.'.$r['expiry']; ?>]">
                                <input type="hidden" value="<?php echo $doc_data['clfoco']; ?>" name="clfoco[<?php echo $r['id_tesdoc_ref'].'.'.$r['expiry']; ?>]">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="col-sm-8">
                                <?php echo $pagamento['descri']; ?>
                            </div>
                            <div class="col-sm-4 pull-right">
                                <?php echo 'scad.' . gaz_format_date($r['expiry']) . ' di € ' . gaz_format_number($status); ?>
                            </div>
                        </div>
                    </div><!-- chiude row  -->
                    <?php
                }
            }
            ?>
            <div class="row">
                <div class="col-md-12">
                    <input class="bg-danger pull-right" id="preventDuplicate" onClick="chkSubmit();" type="submit" name="ins" value="<?php echo $script_transl['confirm_entry']; ?>" />
                </div>
            </div><!-- chiude row  -->

        </div> <!-- chiude container -->
    </div><!-- chiude panel -->
</form>

<?php
require("../../library/include/footer.php");
?>
