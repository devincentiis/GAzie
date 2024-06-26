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

if (isset($_POST['print'])) {
    $_SESSION['print_request'] = array('script_name' => 'print_schedule',
        'orderby' => $form['orderby']
    );
    header("Location: sent_print.php");
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup','custom/autocomplete' ));
echo '<form method="POST" name="select">
		<input type="hidden" value="' . $form['hidden_req'] . '" name="hidden_req" />
		<input type="hidden" value="' . $form['ritorno'] . '" name="ritorno" />';
$gForm = new venditForm();
/** Modifico il form per l'ordinamento, lo rendo più snello, niente più tasto anteprima (vedi considerazioni di seguito) */
echo '<div align="center" class="FacetFormHeaderFont">' . $script_transl['title'] . '</div>
	  <table class="Tmiddle table table-striped table-bordered table-condensed table-responsive">
	  	<tr>
			<td class="FacetFieldCaptionTD">' . $script_transl['orderby'] . '</td>
			<td class="FacetDataTD">';
$gForm->variousSelect('orderby', $script_transl['orderby_value'], $form['orderby'], 'FacetSelect', 0, 'orderby');
echo '		</td>
			<td align="left">
				<input type="submit" name="return" value="' . $script_transl['return'] . '" />
			</td>
		</tr>
	  </table>
	  <br />';
$scdl = new Schedule;
$m = $scdl->getScheduleEntries($form['orderby'], $admin_aziend['mascli']);
echo '<table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
  			<thead>';
if (sizeof($scdl->Entries) > 0) {
    $ctrl_partner = 0;
    $ctrl_id_tes = 0;
    $ctrl_paymov = 0;

    /* ENRICO FEDELE */
    /* Inizializzo le variabili per il totale */
    /* $tot_dare  = 0;
      $tot_avere = 0; */
    $tot = array('dare' => 0, 'avere' => 0);
    /* ENRICO FEDELE */

    echo '	<tr>';
    $linkHeaders = new linkHeaders($script_transl['header']);
    $linkHeaders->output();
    echo '		</tr>
				</thead>
				<tbody>';
    $status_descr = '';
	foreach ($scdl->Entries AS $key => $mv) {
        $class_partner = '';
        $class_paymov = '';
        $partner = '';
        $id_tes = '';
        $paymov = '';
        $status_del = false;
        $status_descr = '';
        if ($mv["codice"] <> $ctrl_partner) {
            $class_partner = 'FacetDataTD';
            $partner = $mv["ragsoc"];
        }
        if ($mv["id_tes"] <> $ctrl_id_tes) {
            $id_tes = $mv["id_tes"];
            $mv["datdoc"] = $mv["id_doc"] ? gaz_format_date($mv["datdoc"]) : '';
        } else {
            $mv['descri'] = '';
            $mv['numdoc'] = '';
            $mv['datdoc'] = '';
            $class_partner = '';
            $partner = '';
            $status_descr = '';
        }
        if ($mv["id_tesdoc_ref"] <> $ctrl_paymov) {
            $paymov = $mv["id_tesdoc_ref"];
            $scdl->getStatus($paymov);
            $r = $scdl->Status;
            $status_descr .= $script_transl['status_value'][$r['sta']];
            // link
            $riscuoti_btn = sprintf('&nbsp; <a title="Riscuoti" class="btn btn-xs btn-default btn-pagamento" href="customer_payment.php?partner=' . $mv["codice"] . '%s"><i class="glyphicon glyphicon-euro"></i></a>',
                $mv['id_doc'] ? '&amp;numdoc=' . $mv['numdoc'] . '&amp;datdoc=' . gaz_format_date($mv['datdoc'], true) : '');
            switch($r['sta']) {
                case 1: // CHIUSA
                    $class_paymov = 'bg-success';
                    $status_del = true;
                    break;
                case 2: // ESPOSTA
                    $class_paymov = 'bg-warning';
                    break;
                case 3: // SCADUTA
                    $class_paymov = 'bg-danger';
                    $status_descr .= $riscuoti_btn;
                    break;
                case 9: // ANTICIPO
                    $class_paymov = 'bg-info';
                    break;
                default: //APERTA
                    $class_paymov = 'bg-default';
                    $status_descr .= $riscuoti_btn;
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
        if ($mv['id_rigmoc_pay'] == 0) {
            /* Incremento il totale del dare */
            $tot['dare'] += $mv['amount'];
            /* Allineo a destra il testo, i numeri sono così più leggibili e ordinati, li formatto con apposita funzione */
            echo "<td class=\"FacetDataTD\" align=\"right\">" . gaz_format_number($mv["amount"]) . " &nbsp;</td>";
            echo "<td class=\"FacetDataTD\"></td>";
        } else {
            /* Incremento il totale dell'avere, e decremento quello del dare */
            $tot['avere'] += $mv['amount'];
            $tot['dare'] -= $mv['amount'];
            echo "<td class=\"FacetDataTD\"></td>";
            echo "<td class=\"FacetDataTD\" align=\"right\">" . gaz_format_number($mv["amount"]) . " &nbsp;</td>";
        }
        /* ENRICO FEDELE */
        echo "<td align=\"center\" class=\"FacetDataTD\">" . gaz_format_date($mv["expiry"]) . " &nbsp;</td>";
        echo "<td align=\"center\" class=\"FacetDataTD\"> ";
        // Permette di cancellare il documento.
        if ($status_del) {
            echo "<a class=\"btn btn-xs  btn-elimina\" title=\"Cancella tutti i movimenti relativi a questa partita oramai chiusa (rimarranno comunque i movimenti contabili)\" href=\"delete_schedule.php?id_tesdoc_ref=" . $paymov . "\"><i class=\"glyphicon glyphicon-trash\"></i></a>";
        } else {
            echo "<button title=\"Non &egrave; possibile cancellare una partita ancora aperta\" class=\"btn btn-xs   disabled\"><i class=\"glyphicon glyphicon-trash\"></i></button>";
        }
        echo "</td></tr>\n";
        $ctrl_id_tes = $mv["id_tes"];
        $ctrl_paymov = $mv["id_tesdoc_ref"];
        $ctrl_partner = $mv["codice"];
    }
    /** ENRICO FEDELE */
    /* Stampo il totale del dare, dell'avere, e la percentuale dell'avere rispetto al totale dare+avere */
    /* Aumento il colspan nell'ultima riga per ricomprendere anche l'ultima colonna, il pulsante stampa ora va sotto opzioni */
    echo '<tr class="FacetFormHeaderFont">
			<td class="text-right" colspan=7>' . $script_transl['total_open'] . ': </td>
			<td class="text-right">' . gaz_format_number($tot['dare']) . '</td>
			<td class="text-right">' . gaz_format_number($tot['avere']) . '</td>
			<td class="text-center">' . gaz_format_number(100 * $tot['avere'] / ($tot['dare'] + $tot['avere'])) . ' %</td>
			<td class="text-center"></td></tr><tr><td colspan=11 class="FacetFooterTD text-center"><input type="submit" class="btn btn-warning" name="print" value="' . $script_transl['print'] . '"></td>
		  </tr>';
} else {
    echo '	<tr>
	 			<td class="FacetDataTDred" align="center">' . $script_transl['errors'][1] . '</td>
			</tr>';
}
echo '
  			</table>
	  	</form>';
/** ENRICO FEDELE */
/* Chiudeva il controllo if (isset($_POST['preview'])) */
//}
/** ENRICO FEDELE */
?>
<?php
require("../../library/include/footer.php");
?>
