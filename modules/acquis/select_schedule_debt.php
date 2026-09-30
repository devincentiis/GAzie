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
$admin_aziend = checkAdmin();

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['this_date_D'] = date("d");
    $form['orderby'] = 2;
} else { // accessi successivi
    $form['hidden_req'] = htmlentities($_POST['hidden_req']);
    $form['ritorno'] = $_POST['ritorno'];
    if (isset($_POST['return'])) {
        header("Location: " . $form['ritorno']);
        exit;
    }
    $form['orderby'] = intval($_POST['orderby']);
}
// fine controlli

if (isset($_POST['print']) && empty($msg)) {
    $_SESSION['print_request'] = array('script_name' => 'print_schedule',
        'orderby' => $form['orderby']
    );
    header("Location: sent_print.php");
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup', 'custom/autocomplete' ));
echo '<form method="POST" name="select">
		<input type="hidden" value="' . $form['hidden_req'] . '" name="hidden_req" />
		<input type="hidden" value="' . $form['ritorno'] . '" name="ritorno" />';
$gForm = new acquisForm();
echo '	<div align="center" class="FacetFormHeaderFont">' . $script_transl['title'] . '</div>
		<div class="table-responsive">
	  	<table class="Tmiddle table table-striped table-bordered table-condensed">
			<tr>
				<td class="FacetFieldCaptionTD">' . $script_transl['orderby'] . '</td>
				<td  class="FacetDataTD">';
$gForm->variousSelect('orderby', $script_transl['orderby_value'], $form['orderby'], 'FacetSelect', 0, 'orderby');
echo '			</td>
			</tr>';
echo '		  </table></div>';

//if (isset($_POST['preview'])) {
$scdl = new Schedule;
$m = $scdl->getScheduleEntries($form['orderby'], $admin_aziend['masfor']);

echo '<div class="table-responsive"><table class="Tlarge table table-striped">';

if (sizeof($scdl->Entries) > 0) {
    $ctrl_partner = 0;
    $ctrl_id_tes = 0;
    $ctrl_paymov = 0;
    $tot = array('dare' => 0, 'avere' => 0);

    echo '	<thead>
					<tr>';
    $linkHeaders = new linkHeaders($script_transl['header']);
    $linkHeaders->output();
    echo ' 		</tr>
				</thead>
				<tbody>';

	foreach ($scdl->Entries AS $key => $mv) {
        $class_partner = '';
        $class_paymov = '';
        $partner = '';
        $id_tes = '';
        $paymov = '';
        $status_del = false;
        if ($mv['clfoco'] <> $ctrl_partner) {
            $class_partner = '';
            $partner = $mv['ragsoc'];
        }
        if ($mv['id_tes'] <> $ctrl_id_tes) {
            $id_tes = $mv['id_tes'];
          if ($mv['datdoc'] != '0000-00-00') {
            $mv['datdoc'] = gaz_format_date($mv['datdoc']);
          } else {
            $mv['datdoc'] = '';
          }
        } else {
            $mv['descri'] = '';
            $mv['numdoc'] = '';
            $mv['datdoc'] = '';
            $class_partner = '';
            $partner = '';
            $status_descr = '';
        }
        if ($mv['id_tesdoc_ref'] <> $ctrl_paymov) {
            $paymov = $mv['id_tesdoc_ref'];
            $scdl->getStatus($paymov);
            $r = $scdl->Status;
            $status_descr = $script_transl['status_value'][$r['sta']] ;
            if ($r['sta'] == 1) { // CHIUSA
                $class_paymov = 'bg-success';
                $status_del = true;
            } elseif ($r['sta'] == 2) { // ESPOSTA
                $class_paymov = 'bg-warning';
            } elseif ($r['sta'] == 3) { // SCADUTA
                $class_paymov = 'bg-danger';
                $status_descr .= " &nbsp;<a title=\"Riscuoti\" class=\"btn btn-xs btn-default btn-pagamento\" href=\"supplier_payment.php?partner=" . $mv["clfoco"] . "\"><i class=\"glyphicon glyphicon-euro\"></i></a>";
            } elseif ($r['sta'] == 9) { // PAGAMENTO ANTICIPATO
                $class_paymov = 'bg-info';
            } else { // APERTA
                $class_paymov = 'bg-default';
                $status_descr .= " &nbsp;<a title=\"Riscuoti\" class=\"btn btn-xs btn-default btn-pagamento\" href=\"supplier_payment.php?partner=" . $mv["clfoco"] . "\"><i class=\"glyphicon glyphicon-euro\"></i></a>";
            }
        }
        echo '<tr>
							<td class="' . $class_partner . '">' . $partner . '&nbsp;</td>
							<td class="' . $class_paymov . ' text-center">' . $paymov . '&nbsp;</td>
							<td class="' . $class_paymov . ' text-center">' . $status_descr . '&nbsp;</td>
							<td class="text-center"><a href="../contab/admin_movcon.php?id_tes=' . $mv["id_tes"] . '&Update">' . $mv['descri'] . '</a>&nbsp;</td>
							<td class="text-center">' . $mv["numdoc"] . '&nbsp;</td>
							<td class="text-center">' . $mv["datdoc"] . '&nbsp;</td>
							<td class="text-center">' . gaz_format_date($mv["datreg"]) . '&nbsp;</td>';
        if ($mv['darave'] == 'D') {
            $tot['dare'] += $mv["amount"];
            //$tot['avere'] -= $mv["amount"];

            echo '			<td class="text-center">' . gaz_format_number($mv["amount"]) . '&nbsp;</td>
								<td class="text-center"></td>';
        } else {
            $tot['avere'] += $mv["amount"];
            $tot['dare'] -= $mv["amount"];
            echo '			<td class="text-center"></td>
								<td class="text-center">' . gaz_format_number($mv["amount"]) . '&nbsp;</td>';
        }
        echo '				<td class="text-center">' . gaz_format_date($mv["expiry"]) . '&nbsp;</td>
								<td class="text-center">';
        // Permette di cancellare il documento.
        if ($status_del) {
            echo '					<a class="btn btn-xs  btn-elimina" title="Cancella tutti i movimenti relativi a questa partita oramai chiusa (rimarranno comunque i movimenti contabili)" href="delete_schedule.php?id_tesdoc_ref=' . $paymov . '"><i class="glyphicon glyphicon-trash"></i></a>';
        } else {
            echo '					<button title="Non &egrave; possibile cancellare una partita ancora aperta" class="btn btn-xs   disabled"><i class="glyphicon glyphicon-trash"></i></button>';
        }
        echo '				</td>
							</tr>';
        $ctrl_id_tes = $mv["id_tes"];
        $ctrl_paymov = $mv["id_tesdoc_ref"];
    }
    echo '					<tr class="FacetFormHeaderFont">
	 							<td colspan=7 class="text-right">' . $script_transl['total_open'] . ': </td>
								<td class="text-right">' . gaz_format_number($tot['dare']) . '</td>
								<td class="text-right">' . gaz_format_number($tot['avere']) . '</td>
								<td class="text-right">' . gaz_format_number(100 * abs($tot['dare'] / ($tot['dare'] + $tot['avere']))) . ' %</td><td></td></tr><tr>
								<td colspan=11 class="FacetFooterTD text-center">
									<input type="submit" class="btn btn-warning" name="print" value="' . $script_transl['print'] . '" />
								</td>
							</tr>';
} else {
    echo '					<tr>
	 							<td class="FacetDataTDred text-center">' . $script_transl['errors'][1] . '</td>
							</tr>';
}
echo '		</tbody>
 			</table></div>
		</form>';
//}
?>
<?php
require("../../library/include/footer.php");
?>
