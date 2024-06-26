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
$titolo = "Contabilizzazione effetti";
if (!isset($_POST['gioexe']))
    $_POST['gioexe'] = date("d");
if (!isset($_POST['mesexe']))
    $_POST['mesexe'] = date("m");
if (!isset($_POST['annexe']))
    $_POST['annexe'] = date("Y");
if (!isset($_POST['cktipo']))
    $_POST['cktipo'] = 0;
if (!isset($_POST['ckdata']))
    $_POST['ckdata'] = 1;

$message = "";

//controllo i limiti delle scadenza che sono state richieste per la stampa
if (!isset($_POST["proini"])) {
    //recupero il primo protocollo da contabilizzare
    $rs_primodoc = gaz_dbi_dyn_query("*", $gTables['effett'], "YEAR(datemi) = '" . $_POST['annexe'] . "' and id_con = 0 and banacc > 0 ", 'progre asc', 0, 1);
    $primodoc = gaz_dbi_fetch_array($rs_primodoc);
    if ($primodoc) {
        $_POST["proini"] = $primodoc['progre'];
    } else {
        $_POST["proini"] = 1;
    }
}

if (!isset($_POST["profin"])) {
    //recupero l'ultimo protocollo da contabilizzare
    $rs_ultimdoc = gaz_dbi_dyn_query("*", $gTables['effett'], "YEAR(datemi) = " . intval($_POST['annexe']) . " AND id_con = 0 AND banacc > 0", 'progre desc', 0, 1);
    $ultimdoc = gaz_dbi_fetch_array($rs_ultimdoc);
    if ($ultimdoc) {
        $_POST["profin"] = $ultimdoc['progre'];
    } else {
        $_POST["profin"] = 9999;
    }
}

//controllo se ci sono effetti da contabilizzare nell'anno selezionato.
if ($_POST['cktipo'] == 0) {
    $querytip = "";
} elseif ($_POST['cktipo'] == 1) {
    $querytip = " and tipeff = \"B\" ";
} elseif ($_POST['cktipo'] == 2) {
    $querytip = " and tipeff = \"T\" ";
} elseif ($_POST['cktipo'] == 3) {
    $querytip = " and tipeff = \"V\" ";
} elseif ($_POST['cktipo'] == 4) {
    $querytip = " and tipeff = \"I\" ";
}

$result = gaz_dbi_dyn_query("*", $gTables['effett'], "YEAR(datemi) = " . intval($_POST['annexe']) . " AND id_con = 0 AND banacc > 0 $querytip", 'id_tes asc', 0, 1);
$ctrldoc = gaz_dbi_fetch_array($result);

if (!$ctrldoc)
    $message .= "Non ci sono effetti da contabilizzare per l'anno e il tipo selezionato <br />";

if (!checkdate($_POST['mesexe'], $_POST['gioexe'], $_POST['annexe']))
    $message .= "La data " . $_POST['gioexe'] . "-" . $_POST['mesexe'] . "-" . $_POST['annexe'] . " non &egrave; corretta! <br />";

if ($_POST['ckdata'] == 1) {
    $utsexe = mktime(0, 0, 0, $_POST['mesexe'], $_POST['gioexe'], $_POST['annexe']);
    //recupero l'ultimo protocollo da contabilizzare
    $rs_ultimdoc = gaz_dbi_dyn_query("*", $gTables['effett'], "YEAR(datemi) = " . $_POST['annexe'] . " and id_con = 0 and progre between '" . intval($_POST['proini']) . "' and '" . intval($_POST['profin']) . "' ", 'progre desc', 0, 1);
    $dataultimdoc = gaz_dbi_fetch_array($rs_ultimdoc);
	if ($dataultimdoc) {
		$giofin = substr($dataultimdoc['datfat'], 8, 2);
		$mesfin = substr($dataultimdoc['datfat'], 5, 2);
		$annfin = substr($dataultimdoc['datfat'], 0, 4);
		$utsfin = mktime(0, 0, 0, $mesfin, $giofin, $annfin);
	} else {
		$utsfin = $utsexe;
	}
    if ($utsexe < $utsfin) {
        $message .="Almeno un effetto tra quelli selezionati ha la data di emissione successiva a quella di registrazione !<br />";
    }
}

if (isset($_POST['Return'])) {
    header("Location:report_effett.php");
    exit;
}

//se viene richiesta la contabilizzazione utilizza la stessa procedura per l'anteprima ma questa volta inserisco i dati nei db
if (isset($_POST['genera'])and $message == "") {
    //recupero i documenti da contabilizzare
    $result = gaz_dbi_dyn_query("*", $gTables['effett'], "YEAR(datemi) = '" . intval($_POST['annexe']) . "' AND id_con = 0 AND progre BETWEEN " . intval($_POST['proini']) . " AND " . intval($_POST['profin']) . " AND banacc > 0 $querytip", 'progre, id_tes');
    while ($effett = gaz_dbi_fetch_array($result)) {
        if ($effett['tipeff'] == 'B') {
            //inserisco la testata
            $newValue = array('caucon' => 'RIB',
                'descri' => 'EMESSA RICEVUTA BANCARIA',
                'datreg' => $effett['datemi'],
                'seziva' => $effett['seziva'],
                'id_doc' => $effett['id_tes'],
                'protoc' => $effett['id_tes'],
                'numdoc' => $effett['progre'],
                'datdoc' => $effett['datemi'],
                'clfoco' => $effett['clfoco']
            );
            $ultimo_id = tesmovInsert($newValue);
            //recupero l'id assegnato dall'inserimento

            // inserisco i due righi, partendo dal conto dare.
            rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => 'D', 'codcon' => $admin_aziend['coriba'], 'import' => $effett['impeff']));
            // continuo con il conto clienti.
            $paymov_id = rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => 'A', 'codcon' => $effett['clfoco'], 'import' => $effett['impeff']));
            // memorizzo l'id del cliente
        }
        if ($effett['tipeff'] == 'T') {
            //inserisco la testata
            $newValue = array('caucon' => 'TRA',
                'descri' => 'EMESSA CAMBIALE TRATTA',
                'datreg' => $effett['datemi'],
                'seziva' => $effett['seziva'],
                'id_doc' => $effett['id_tes'],
                'protoc' => $effett['id_tes'],
                'numdoc' => $effett['progre'],
                'datdoc' => $effett['datemi'],
                'clfoco' => $effett['clfoco']
            );
            $ultimo_id = tesmovInsert($newValue);
            //recupero l'id assegnato dall'inserimento

            // inserisco i due righi partendo dal conto dare
            rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => 'D', 'codcon' => $admin_aziend['cotrat'], 'import' => $effett['impeff']));
            // continuo con il conto clienti.
            $paymov_id = rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => 'A', 'codcon' => $effett['clfoco'], 'import' => $effett['impeff']));
            // memorizzo l'id del cliente
        }
        if ($effett['tipeff'] == 'V') {
            //inserisco la testata
            $newValue = array('caucon' => 'MAV',
                'descri' => 'EMESSO MAV',
                'datreg' => $effett['datemi'],
                'seziva' => $effett['seziva'],
                'id_doc' => $effett['id_tes'],
                'protoc' => $effett['id_tes'],
                'numdoc' => $effett['progre'],
                'datdoc' => $effett['datemi'],
                'clfoco' => $effett['clfoco']
            );
            $ultimo_id = tesmovInsert($newValue);
            //recupero l'id assegnato dall'inserimento

            // inserisco i due righi partendo dal conto dare.
            rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => 'D', 'codcon' => $effett['banacc'], 'import' => $effett['impeff']));
            // continuo con il cliente.
            $paymov_id = rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => 'A', 'codcon' => $effett['clfoco'], 'import' => $effett['impeff']));
            // memorizzo l'id del cliente
        }
        if ($effett['tipeff'] == 'I') {
            //inserisco la testata
            $newValue = array('caucon' => 'RID',
                'descri' => 'EMESSO RID',
                'datreg' => $effett['datemi'],
                'seziva' => $effett['seziva'],
                'id_doc' => $effett['id_tes'],
                'protoc' => $effett['id_tes'],
                'numdoc' => $effett['progre'],
                'datdoc' => $effett['datemi'],
                'clfoco' => $effett['clfoco']
            );
            $ultimo_id = tesmovInsert($newValue);
            //recupero l'id assegnato dall'inserimento

            // inserisco i due righi partendo dal conto dare.
            rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => 'D', 'codcon' => $effett['banacc'], 'import' => $effett['impeff']));
            // continuo con il cliente.
            $paymov_id = rigmocInsert(array('id_tes' => $ultimo_id, 'darave' => 'A', 'codcon' => $effett['clfoco'], 'import' => $effett['impeff']));
            // memorizzo l'id del cliente
        }
        // aggiungo un movimento alle partite aperte
        paymovInsert(array('id_tesdoc_ref' => substr($newValue['datreg'], 0, 4) . '2' . $effett['seziva'] . str_pad($effett['protoc'], 9, 0, STR_PAD_LEFT), 'id_rigmoc_pay' => $paymov_id, 'amount' => $effett['impeff'], 'expiry' => $effett['scaden']));
        //vado a modificare l'effetto cambiando il numero di riferimento al movimento
        gaz_dbi_put_row($gTables['effett'], "id_tes", $effett["id_tes"], "id_con", $ultimo_id);
    }
    header("Location: report_effett.php");
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<form method="POST">
    <div align="center" class="FacetFormHeaderFont">Contabilizzazione Effetti</div>
    <table class="Tmiddle table-striped">
        <!-- BEGIN Error -->
        <tr>
            <td colspan="2" class="FacetDataTD"  style="color: red;">
                <?php
                if (!$message == "") {
                    echo "$message";
                }
                ?>
            </td>
        </tr>
        <!-- END Error -->
        <tr>
            <td class="FacetFieldCaptionTD">Data </td>
            <td class="FacetDataTD" >
                <?php
                // select del giorno
                echo "\t <select name=\"gioexe\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
                for ($counter = 1; $counter <= 31; $counter++) {
                    $selected = "";
                    if ($counter == $_POST['gioexe'])
                        $selected = "selected";
                    echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
                }
                echo "\t </select>\n";
                // select del mese
                echo "\t <select name=\"mesexe\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
                $gazTimeFormatter->setPattern('MMMM');
                for ($counter = 1; $counter <= 12; $counter++) {
                  $selected = "";
                  if ($counter == $_POST['mesexe'])
                       $selected = "selected";
                  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
                  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
                }
                echo "\t </select>\n";
                // select del anno
                echo "\t <select name=\"annexe\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
                for ($counter = 2002; $counter <= 2030; $counter++) {
                    $selected = "";
                    if ($counter == $_POST['annexe'])
                        $selected = "selected";
                    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
                }
                echo "\t </select>\n";
                ?>
            </td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD"> Data registrazione &nbsp;</td>
            <td class="FacetDataTD">
                <?php
                if ($_POST['ckdata'] == 0) {
                    $checked0 = "checked";
                    $checked1 = "";
                } else {
                    $checked1 = "checked";
                    $checked0 = "";
                }
                echo "\t\t <input type=\"radio\" name=\"ckdata\" value=0 $checked0> data emissione \n";
                echo "\t\t <input type=\"radio\" name=\"ckdata\" value=1 $checked1> come sopra \n";
                ?>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD"> Tipo effetto &nbsp;</td>
            <td class="FacetDataTD">
                <?php
                if ($_POST['cktipo'] == 0) {
                    $checked4 = "";
                    $checked3 = "";
                    $checked2 = "";
                    $checked1 = "";
                    $checked0 = "checked";
                    $querytip = "";
                }
                if ($_POST['cktipo'] == 1) {
                    $checked4 = "";
                    $checked3 = "";
                    $checked2 = "";
                    $checked1 = "checked";
                    $checked0 = "";
                    $querytip = " and tipeff = \"B\" ";
                }
                if ($_POST['cktipo'] == 2) {
                    $checked4 = "";
                    $checked3 = "";
                    $checked2 = "checked";
                    $checked1 = "";
                    $checked0 = "";
                    $querytip = " and tipeff = \"T\" ";
                }
                if ($_POST['cktipo'] == 3) {
                    $checked4 = "";
                    $checked3 = "checked";
                    $checked2 = "";
                    $checked1 = "";
                    $checked0 = "";
                    $querytip = " and tipeff = \"V\" ";
                }
                if ($_POST['cktipo'] == 4) {
                    $checked4 = "checked";
                    $checked3 = "";
                    $checked2 = "";
                    $checked1 = "";
                    $checked0 = "";
                    $querytip = " and tipeff = \"I\" ";
                }
                echo "\t\t <input type=\"radio\" name=\"cktipo\" value=0 $checked0 onclick=\"this.form.submit()\"> TUTTE \n";
                echo "\t\t <input type=\"radio\" name=\"cktipo\" value=1 $checked1 onclick=\"this.form.submit()\"> R.B. \n";
                echo "\t\t <input type=\"radio\" name=\"cktipo\" value=2 $checked2 onclick=\"this.form.submit()\"> TRATTE \n";
                echo "\t\t <input type=\"radio\" name=\"cktipo\" value=3 $checked3 onclick=\"this.form.submit()\"> MAV \n";
                echo "\t\t <input type=\"radio\" name=\"cktipo\" value=4 $checked4 onclick=\"this.form.submit()\"> RID \n";
                ?>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD">Progressivo iniziale &nbsp;</td>
            <td class="FacetDataTD"><input type="hidden" name="proini" value="<?php echo $_POST["proini"] ?>" maxlength="5" class="FacetInput"> <?php echo $_POST["proini"]; ?> &nbsp;</td>
        </tr>
        <tr>
            <td class="FacetFieldCaptionTD">Progressivo finale &nbsp;</td>
            <td class="FacetDataTD"><input title="Numero dell'ultimo effetto che si intende contabilizzare" type="text" name="profin" value="<?php echo $_POST["profin"] ?>" maxlength="5" class="FacetInput">&nbsp;</td>
        <tr>
            <td class="FacetFooterTD"></td>
            <td colspan="2" align="right"  class="FacetFooterTD">
                <input type="submit" name="anteprima" class="btn btn-info" value="Visualizza l'anteprima">&nbsp;
            </td>
        </tr>
    </table>
    <?php
//mostro l'anteprima
    if (isset($_POST['anteprima']) and $message == "") {
        //recupero i documenti da contabilizzare
        $result = gaz_dbi_dyn_query("*", $gTables['effett'], "datemi like '" . intval($_POST['annexe']) . "%' and id_con = 0  and banacc > 0 and progre between " . $_POST['proini'] . ' and ' . $_POST['profin'] . ' ' . $querytip, 'tipeff asc, scaden asc');
        echo "<div><center><b>ANTEPRIMA CONTABILIZZAZIONE </b></div>";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        echo "<th class=\"FacetFieldCaptionTD\">Scadenza</th><th class=\"FacetFieldCaptionTD\">Emissione</th><th class=\"FacetFieldCaptionTD\">Tipo</th><th class=\"FacetFieldCaptionTD\">Progr.</th><th class=\"FacetFieldCaptionTD\">Cliente</th><th class=\"FacetFieldCaptionTD\">Importo</th><th class=\"FacetFieldCaptionTD\">Saldo<br />Conto</th><th class=\"FacetFieldCaptionTD\">N.Fatt.</th><th class=\"FacetFieldCaptionTD\">Data Fattura</th>";
        $anagrafica = new Anagrafica();
        $totEffetti = 0;
        while ($effett = gaz_dbi_fetch_array($result)) {
            $client = $anagrafica->getPartner($effett['clfoco']);
            $pagame = gaz_dbi_get_row($gTables['pagame'], "codice", $effett['pagame']);
            $giorno = substr($effett['datfat'], 8, 2);
            $mese = substr($effett['datfat'], 5, 2);
            $anno = substr($effett['datfat'], 0, 4);
            //stampo i totali
            echo '<tr><td>' . $effett['scaden'] . '</td><td>' . $effett['datemi'] . '</td><td>' . $effett['tipeff'] . '</td><td>' . $effett['progre'] . '</td><td>' . $client['ragso1'] . '</td><td align=\'right\'>' . gaz_format_number($effett['impeff']) . '</td><td align=\'right\'>' . $effett['salacc'] . '</td><td align=\'right\'>' . $effett['numfat'] . '/' . $effett['seziva'] . '</td><td align=\'right\'>' . $effett['datfat'] . '</td></tr>';
            $totEffetti+=$effett['impeff'];
        }
        $strTotEffetti = gaz_format_number($totEffetti);
        echo "<tr><td class=\"text-right bg-info\" colspan=\"5\"><b>Totale:</b></td><td align=\"right\" class=\"bg-info\"><b>$strTotEffetti</b></td></tr>";
        echo "<tr><td colspan=\"9\" class=\"FacetFooterTD text-center\"><input type=\"submit\" class=\"btn btn-warning\" name=\"genera\" value=\"CONFERMA LA CONTABILIZZAZIONE DEGLI EFFETTI SOPRAELENCATI !\"></td></tr>";
        echo '</table>';
    }
    ?>
</form>
<?php
require("../../library/include/footer.php");
?>
