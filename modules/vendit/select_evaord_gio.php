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
$anno = date("Y");
$msg = "";

function getDayNameFromDayNumber($day_number) {
  global $gazTimeFormatter;
  $gazTimeFormatter->setPattern('eeee');
  return ucfirst(utf8_encode($gazTimeFormatter->format(new DateTime('@'.mktime(12,0,0,3,19+$day_number, 2017)))));
}

$upd_mm = new magazzForm;
$docOperat = $upd_mm->getOperators();

if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}

function azzera() {
   $anno = date("Y");
    $_POST['num_rigo'] = 0;
    $form['hidden_req'] = '';
    $form['righi'] = array();
    $form['indspe'] = '';
    $form['search']['clfoco'] = '';
    $form['id_tes'] = "new";
    $form['seziva'] = 1;
    $form['datemi_D'] = date("d");
    $form['datemi_M'] = date("m");
    $form['datemi_Y'] = $anno;
    $form['initra_D'] = date("d");
    $form['initra_M'] = date("m");
    $form['initra_Y'] = $anno;
    $form['initra_I'] = date("i");
    $form['initra_H'] = date("H");
    $form['traspo'] = 0.00;
    $form['speban'] = 0.00;
    $form['stamp'] = 0.00;
    $form['vettor'] = "";
    $form['portos'] = "";
    $form['imball'] = "";
    $form['pagame'] = "";
    $form['destin'] = '';
    $form['id_des'] = 0;
    $form['id_des_same_company'] = 0;
    $form['caumag'] = '';
    $form['id_agente'] = 0;
    $form['banapp'] = "";
    $form['spediz'] = "";
    $form['sconto'] = 0.00;
    //$form['ivaspe'] = $admin_aziend['preeminent_vat'];
    $form['listin'] = 1;
    $form['net_weight'] = 0;
    $form['gross_weight'] = 0;
    $form['units'] = 0;
    $form['volume'] = 0;
    $form['weekday_repeat']=0;
    $form['tipdoc']='';
    return $form;
}

if (!isset($_POST['id_tes'])) $form = azzera();

if ( isset($_POST['weekday_repeat']) ) {
   $res_orgio = gaz_dbi_dyn_query ("*", $gTables['tesbro'], "tipdoc='VOG' and weekday_repeat=".intval($_POST['weekday_repeat']),"id_tes");
   $rows = gaz_dbi_fetch_all ( $res_orgio );

   foreach ( $rows as $riga ) {

      $_POST['num_rigo'] = 0;
      $form['hidden_req'] = '';
      $form['righi'] = array();
      $form['indspe'] = '';
    $form['search']['clfoco'] = '';
    $form['id_tes'] = "new";
    $form['seziva'] = 1;
    $form['datemi_D'] = $_POST['datemi_D'];
    $form['datemi_M'] = $_POST['datemi_M'];
    $form['datemi_Y'] = $_POST['datemi_Y'];
    $form['initra_D'] = $_POST['initra_D'];
    $form['initra_M'] = $_POST['initra_M'];
    $form['initra_Y'] = $_POST['initra_Y'];
    $form['initra_I'] = $_POST['initra_I'];
    $form['initra_H'] = $_POST['initra_H'];
    $form['traspo'] = $_POST['traspo'];
    $form['speban'] = 0.00;
    $form['stamp'] = 0.00;
    $form['vettor'] = $_POST['vettor'];
    $form['portos'] = $_POST['portos'];
    $form['imball'] = $_POST['imball'];
    $form['pagame'] = $_POST['pagame'];
    $form['destin'] = $_POST['destin'];
    $form['id_des'] = $_POST['id_des'];
    $form['id_des_same_company'] = $_POST['id_des_same_company'];
    $form['caumag'] = '';
    $form['id_agente'] = $_POST['id_agente'];
    $form['banapp'] = $_POST['banapp'];
    $form['spediz'] = $_POST['spediz'];
    $form['portos'] = $_POST['portos'];
    $form['imball'] = $_POST['imball'];
    $form['sconto'] = $_POST['sconto'];
    $form['ivaspe'] = $admin_aziend['preeminent_vat'];
    $form['listin'] = 1;
    $form['net_weight'] = 0;
    $form['gross_weight'] = 0;
    $form['units'] = 0;
    $form['volume'] = 0;
    foreach ($_POST['search'] as $k => $v) {
        $form['search'][$k] = $v;
    }

      $_GET['id_tes'] = $riga['id_tes'];
      if (isset($_GET['id_tes'])) { //se � stato richiesto un ordine specifico lo carico
        $form['id_tes'] = intval($_GET['id_tes']);
        $testate = gaz_dbi_get_row($gTables['tesbro'], "id_tes", $form['id_tes']);

        $form['clfoco'] = $testate['clfoco'];
        $anagrafica = new Anagrafica();
        $cliente = $anagrafica->getPartner($form['clfoco']);
        $id_des = $anagrafica->getPartner($testate['id_des']);
        $form['search']['clfoco'] = substr($cliente['ragso1'], 0, 10);
        $form['weekday_repeat'] = $testate['weekday_repeat'];
        $form['seziva'] = $testate['seziva'];
        $form['tipdoc'] = $testate['tipdoc'];
        $form['indspe'] = $cliente['indspe'];
        //$form['traspo'] = $testate['traspo'];
        $form['speban'] = $testate['speban'];
        $form['stamp'] = $testate['stamp'];
        //$form['vettor'] = $testate['vettor'];
        //$form['portos'] = $testate['portos'];
        //$form['imball'] = $testate['imball'];
        $form['pagame'] = $testate['pagame'];
        $form['destin'] = $testate['destin'];
        $form['id_des'] = $testate['id_des'];
        $form['id_des_same_company'] = $testate['id_des_same_company'];
        $form['caumag'] = $testate['caumag'];
        $form['id_agente'] = $testate['id_agente'];
        $form['banapp'] = $testate['banapp'];
        //$form['spediz'] = $testate['spediz'];
        $form['sconto'] = $testate['sconto'];
        $form['listin'] = $testate['listin'];
        $form['net_weight'] = $testate['net_weight'];
        $form['gross_weight'] = $testate['gross_weight'];
        $form['search']['id_des'] = substr($id_des['ragso1'], 0, 10);
        $form['units'] = $testate['units'];
        $form['volume'] = $testate['volume'];
        $rs_righi = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = " . $form['id_tes'], "id_rig asc");
        while ($rigo = gaz_dbi_fetch_array($rs_righi)) {
            $articolo = gaz_dbi_get_row($gTables['artico'], "codice", $rigo['codart']);
            $form['righi'][$_POST['num_rigo']]['id_rig'] = $rigo['id_rig'];
            $form['righi'][$_POST['num_rigo']]['tiprig'] = $rigo['tiprig'];
            $form['righi'][$_POST['num_rigo']]['id_tes'] = $rigo['id_tes'];
            $form['righi'][$_POST['num_rigo']]['tipdoc'] = $testate['tipdoc'];
            $form['righi'][$_POST['num_rigo']]['datemi'] = $testate['datemi'];
            $form['righi'][$_POST['num_rigo']]['numdoc'] = $testate['numdoc'];
            $form['righi'][$_POST['num_rigo']]['descri'] = $rigo['descri'];
            $form['righi'][$_POST['num_rigo']]['id_body_text'] = $rigo['id_body_text'];
            $form['righi'][$_POST['num_rigo']]['codart'] = $rigo['codart'];
            $form['righi'][$_POST['num_rigo']]['unimis'] = $rigo['unimis'];
            $form['righi'][$_POST['num_rigo']]['prelis'] = $rigo['prelis'];
            $form['righi'][$_POST['num_rigo']]['provvigione'] = $rigo['provvigione'];
            $form['righi'][$_POST['num_rigo']]['ritenuta'] = $rigo['ritenuta'];
            $form['righi'][$_POST['num_rigo']]['sconto'] = $rigo['sconto'];
            $form['righi'][$_POST['num_rigo']]['quanti'] = $rigo['quanti'];
            $form['righi'][$_POST['num_rigo']]['id_doc'] = $rigo['id_doc'];
            $form['righi'][$_POST['num_rigo']]['codvat'] = $rigo['codvat'];
            $form['righi'][$_POST['num_rigo']]['pervat'] = $rigo['pervat'];
            $form['righi'][$_POST['num_rigo']]['codric'] = $rigo['codric'];
            $_POST['num_rigo'] ++;
        }
    }


//conferma dell'evasione di un ddt
    //controllo i campi
    $dataemiss = $form['datemi_Y'] . "-" . $form['datemi_M'] . "-" . $form['datemi_D'];
    $utsDataemiss = mktime(0, 0, 0, $form['datemi_M'], $form['datemi_D'], $form['datemi_Y']);
    $iniziotrasporto = $form['initra_Y'] . "-" . $form['initra_M'] . "-" . $form['initra_D'];
    $utsIniziotrasporto = mktime(0, 0, 0, $form['initra_M'], $form['initra_D'], $form['initra_Y']);
    if (substr($form['clfoco'], 0, 3) != $admin_aziend['mascli'])
        $msg .= "0+";
    if (!isset($form["righi"])) {
        $msg .= "1+";
    } else {
        $inevasi = "";
        foreach ($form['righi'] as $k => $v) {
            if ($v['id_doc'] == 0 and ( $v['tiprig'] == 0 or $v['tiprig'] == 1))
                $inevasi = "ok";
        }
        if (empty($inevasi)) {
            //$msg .= "2+";
        }
    }
    if (empty($form["pagame"]))
        $msg .= "3+";
    if (!checkdate($form['datemi_M'], $form['datemi_D'], $form['datemi_Y']))
        $msg .= "4+";
    if (!checkdate($form['initra_M'], $form['initra_D'], $form['initra_Y']))
        $msg .= "5+";
    if ($utsIniziotrasporto < $utsDataemiss) {
        $msg .= "6+";
    }

    if ($msg == "") {//procedo all'inserimento
        $iniziotrasporto .= " " . $form['initra_H'] . ":" . $form['initra_I'] . ":00";
        require("lang.".$admin_aziend['lang'].".php");
        $script_transl=$strScript['select_evaord.php'];

        //ricavo il numero progressivo
        $rs_ultimo_ddt = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "datemi LIKE '" . $form['datemi_Y'] . "%' AND (tipdoc like 'DD_' OR tipdoc = 'FAD') AND seziva = " . $form['seziva'], "numdoc DESC", 0, 1);
        $ultimo_ddt = gaz_dbi_fetch_array($rs_ultimo_ddt);
        // se e' il primo documento dell'anno, resetto il contatore
        if ($ultimo_ddt) {
            $form['numdoc'] = $ultimo_ddt['numdoc'] + 1;
        } else {
            $form['numdoc'] = 1;
        }
        //inserisco la testata
        $form['tipdoc'] = 'DDT';
        $form['ddt_type'] = 'T';
        $form['template'] = "FatturaSemplice";
        $form['id_con'] = '';
        $form['status'] = ''; //GENERATO
        $form['initra'] = $iniziotrasporto;
        $form['datemi'] = $dataemiss;

        tesdocInsert($form);

        //recupero l'id assegnato dall'inserimento
        $last_id = gaz_dbi_last_id();
        $ctrl_tes = 0;

        foreach ($form['righi'] as $k => $v) {
            /*if ($v['id_tes'] != $ctrl_tes) {  //se fa parte di un'ordine diverso dal precedente
                //inserisco un rigo descrittivo per il riferimento all'ordine sul DdT
                $row_descri['descri'] = " da ".$script_transl['doc_name'][$v['tipdoc']]." n." . $v['numdoc'] . " del " . substr($v['datemi'], 8, 2) . "-" . substr($v['datemi'], 5, 2) . "-" . substr($v['datemi'], 0, 4);
                $row_descri['id_tes'] = $last_id;
                $row_descri['tiprig'] = 2;
                rigdocInsert($row_descri);
            }*/
            //if (isset($v['checkval'])) {   //se e' un rigo selezionato
                //lo inserisco nel DdT
                $row = $v;
                unset($row['id_rig']);
                $row['id_tes'] = $last_id;
                rigdocInsert($row);
                $last_rigdoc_id = gaz_dbi_last_id();
                if ($v['id_body_text'] > 0) { //se � un rigo testo copio il contenuto vecchio su uno nuovo
                    $old_body_text = gaz_dbi_get_row($gTables['body_text'], "id_body", $v['id_body_text']);
                    bodytextInsert(array('table_name_ref' => 'rigdoc', 'id_ref' => $last_rigdoc_id, 'body_text' => $old_body_text['body_text']));
                    gaz_dbi_put_row($gTables['rigdoc'], 'id_rig', $last_rigdoc_id, 'id_body_text', gaz_dbi_last_id());
                }
                if ($admin_aziend['conmag'] == 2 and
                        $form['righi'][$k]['tiprig'] == 0 and ! empty($form['righi'][$k]['codart'])) { //se l'impostazione in azienda prevede l'aggiornamento automatico dei movimenti di magazzino
                    $upd_mm->uploadMag($last_rigdoc_id, $form['tipdoc'], $form['numdoc'], $form['seziva'], $dataemiss, $form['clfoco'], $form['sconto'], $form['caumag'], $v['codart'], $v['quanti'], $v['prelis'], $v['sconto'], 0, $admin_aziend['stock_eval_method']
                    );
                }
                //modifico il rigo dell'ordine indicandoci l'id della testata del DdT
                gaz_dbi_put_row($gTables['rigbro'], "id_rig", $v['id_rig'], "id_doc", $last_id);
            //}
            if ($ctrl_tes != 0 and $ctrl_tes != $v['id_tes']) {  //se non � il primo rigo processato
                //controllo se ci sono ancora righi inevasi
                $rs_righi_inevasi = gaz_dbi_dyn_query("id_tes", $gTables['rigbro'], "id_tes = $ctrl_tes AND id_doc = 0 AND tiprig BETWEEN 0 AND 1", "id_rig", 0, 1);
                $inevasi = gaz_dbi_fetch_array($rs_righi_inevasi);
                if (!$inevasi) {  //se non ci sono + righi da evadere
                    //modifico lo status della testata dell'ordine solo se completamente evaso
                    //gaz_dbi_put_row($gTables['tesbro'], "id_tes", $ctrl_tes, "status", "EVASO");
                }
            }
            $ctrl_tes = $v['id_tes'];
            //echo "ciao<br>".$msg;
        }
        //controllo se l'ultimo ordine tra quelli processati ha ancora righi inevasi
        $rs_righi_inevasi = gaz_dbi_dyn_query("id_tes", $gTables['rigbro'], "id_tes = $ctrl_tes AND id_doc = 0 AND tiprig BETWEEN 0 AND 1", "id_rig", 0, 1);
        $inevasi = "";
        $inevasi = gaz_dbi_fetch_array($rs_righi_inevasi);
        if (!$inevasi) {  //se non ci sono + righi da evadere
            //modifico lo status della testata dell'ordine solo se completamente evaso
            //gaz_dbi_put_row($gTables['tesbro'], "id_tes", $ctrl_tes, "status", "EVASO");
        }
        //$_SESSION['print_request'] = $last_id;

    } else echo $msg;
}
header("Location: report_doctra.php");
exit;
}
require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup','custom/autocomplete'));
$gForm = new venditForm();
?>
<form method="POST" name="myform">
<div class="FacetFormHeaderFont" align="center">
   Creazione DDT da Ordini settimanali del Giorno:
      <?php
    echo '<select name="weekday_repeat" class="FacetSelect">';
    for ( $t=0; $t!=7; $t++ ) {
        if ( $t == $form['weekday_repeat'] ) $selected = " selected";
        else $selected = "";
        echo "<option value='".$t."' ".$selected.">". getDayNameFromDayNumber($t)."</option>";
    }
    echo '</select>';
      ?>
</div>
<?php
$alert_sezione = '';
switch ($admin_aziend['fatimm']) {
    case 1:
    case 2:
    case 3:
        if ($admin_aziend['fatimm'] != $form['seziva'])
            $alert_sezione = $script_transl['alert1'];
        break;
    case "U":
        $alert_sezione = $script_transl['alert1'];
        break;
}
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
?>
    <input type="hidden" name="ritorno" value="<?php echo $_POST['ritorno']; ?>">
    <input type="hidden" name="id_tes" value="<?php echo $form['id_tes']; ?>">
    <input type="hidden" name="tipdoc" value="<?php echo $form['tipdoc']; ?>">
    <input type="hidden" name="speban" value="<?php echo $form['speban']; ?>">
    <input type="hidden" name="stamp" value="<?php echo $form['stamp']; ?>">
    <input type="hidden" name="listin" value="<?php echo $form['listin']; ?>">
    <input type="hidden" name="net_weight" value="<?php echo $form['net_weight']; ?>">
    <input type="hidden" name="gross_weight" value="<?php echo $form['gross_weight']; ?>">
    <input type="hidden" name="units" value="<?php echo $form['units']; ?>">
    <input type="hidden" name="volume" value="<?php echo $form['volume']; ?>">
    <input type="hidden" name="id_agente" value="<?php echo $form['id_agente']; ?>">
    <input type="hidden" name="caumag" value="<?php echo $form['caumag']; ?>">
    <br>
    <table class="Tlarge">
<?php
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['datemi'] . "</td>\n";
echo "\t<td class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('datemi', $form['datemi_D'], $form['datemi_M'], $form['datemi_Y']);
echo "\t </td>";
?>
<td class="FacetFieldCaptionTD">
   Ordini del giorno
</td>
<td class="FacetDataTD">
</td>
<td colspan="2" class="FacetFieldCaptionTD">
   <input type="submit" accesskey="o" name="gddt" value="GENERA DDT!" />
</td>
</tr>
<tr>
<?php
echo '<td class="FacetFieldCaptionTD">' . $script_transl['banapp'] . "</td>\n";
echo '<td colspan="3" class="FacetDataTD">';
$select_banapp = new selectbanapp("banapp");
$select_banapp->addSelected($form["banapp"]);
$select_banapp->output();
echo "</td>\n";
echo "\t<td class=\"FacetFieldCaptionTD\" colspan=\"2\">" . $script_transl['initra'] . "\n";
$gForm->CalendarPopup('initra', $form['initra_D'], $form['initra_M'], $form['initra_Y']);
// select dell'ora
echo "\t <select name=\"initra_H\" class=\"FacetText\" >\n";
for ($counter = 0; $counter <= 23; $counter++) {
    $selected = "";
    if ($counter == $form['initra_H'])
        $selected = "selected";
    echo "\t\t <option value=\"" . sprintf('%02d', $counter) . "\" $selected >" . sprintf('%02d', $counter) . "</option>\n";
}
echo "\t </select>\n ";
// select dell'ora
echo "\t <select name=\"initra_I\" class=\"FacetText\" >\n";
for ($counter = 0; $counter <= 59; $counter++) {
    $selected = "";
    if ($counter == $form['initra_I'])
        $selected = "selected";
    echo "\t\t <option value=\"" . sprintf('%02d', $counter) . "\" $selected >" . sprintf('%02d', $counter) . "</option>\n";
}
echo "\t </select>\n";
echo "</td></tr><tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['traspo'] . ' ' . $admin_aziend['html_symbol'] . "</td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"traspo\" value=\"" . $form['traspo'] . "\" align=\"right\" maxlength=\"6\"  onChange=\"this.form.total.value=summa(this);\" />\n";
echo "\t </td>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['pagame'] . "</td><td  class=\"FacetDataTD\">\n";
$gForm->selectFromDB('pagame', 'pagame', 'codice', $form['pagame'], 'codice', 1, ' ', 'descri');
echo "\t </td>\n";
echo '<td class="FacetFieldCaptionTD">' . $script_transl['destin'] . "</td>\n";
        if ($form['id_des_same_company'] > 0) { //  è una destinazione legata all'anagrafica
            echo "<td class=\"FacetDataTD\">\n";
            $gForm->selectFromDB('destina', 'id_des_same_company', 'codice', $form['id_des_same_company'], 'codice', true, '-', 'unita_locale1', '', 'FacetSelect', null, '', "id_anagra = '" . $cliente['id_anagra'] . "'");
            echo "	<input type=\"hidden\" name=\"id_des\" value=\"" . $form['id_des'] . "\">
                <input type=\"hidden\" name=\"destin\" value=\"" . $form['destin'] . "\" /></td>\n";
        } elseif ($form['id_des'] > 0) { // la destinazione è un'altra anagrafica
            echo "<td class=\"FacetDataTD\">\n";
            $select_id_des = new selectPartner('id_des');
            $select_id_des->selectDocPartner('id_des', 'id_' . $form['id_des'], $form['search']['id_des'], 'id_des', $script_transl['mesg'], $admin_aziend['mascli']);
            echo "			<input type=\"hidden\" name=\"id_des_same_company\" value=\"" . $form['id_des_same_company'] . "\">
                                <input type=\"hidden\" name=\"destin\" value=\"" . $form['destin'] . "\" />
						</td>\n";
        } else {
            echo "			<td class=\"FacetDataTD\">";
            echo "				<textarea rows=\"1\" cols=\"30\" name=\"destin\" class=\"FacetInput\">" . $form["destin"] . "</textarea>
						</td>
						<input type=\"hidden\" name=\"id_des_same_company\" value=\"" . $form['id_des_same_company'] . "\">";
			echo "<input type=\"hidden\" name=\"id_des\" value=\"" . $form['id_des'] . "\">";
			echo "<input type=\"hidden\" name=\"search[id_des]\" value=\"" . $form['search']['id_des'] . "\">\n";
        }
//echo "<td class=\"FacetDataTD\"><textarea rows=\"1\" cols=\"30\" name=\"destin\" class=\"FacetInput\">" . $form['destin'] . "</textarea></td>\n";
echo "</tr><tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['id_agente'] . "</td>";
echo "<td class=\"FacetDataTD\">\n";
$select_agente = new selectAgente("id_agente");
$select_agente->addSelected($form["id_agente"]);
$select_agente->output();
echo "</td>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['spediz'] . "</td>\n";
echo "<td class=\"FacetDataTD\"><input type=\"text\" name=\"spediz\" value=\"" . $form["spediz"] . "\" maxlength=\"50\"  class=\"FacetInput\">\n";
$select_spediz = new SelectValue("spedizione");
$select_spediz->output('spediz', 'spediz');
echo "</td>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['portos'] . "</td>\n";
echo "<td class=\"FacetDataTD\"><input type=\"text\" name=\"portos\" value=\"" . $form["portos"] . "\" maxlength=\"50\"  class=\"FacetInput\">\n";
$select_spediz = new SelectValue("portoresa");
$select_spediz->output('portos', 'portos');
echo "</td>\n";
echo "</td></tr>\n";
echo '<tr><td class="FacetFieldCaptionTD">';
echo "%" . $script_transl['sconto'] . ":</td><td class=\"FacetDataTD\"><input type=\"text\" value=\"" . $form['sconto'] . "\" maxlength=\"5\"  name=\"sconto\" onChange=\"this.form.total.value=sconta(this);\">";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['imball'] . "</td>\n";
echo "<td class=\"FacetDataTD\"><input type=\"text\" name=\"imball\" value=\"" . $form["imball"] . "\" maxlength=\"50\"  class=\"FacetInput\">\n";
$select_spediz = new SelectValue("imballo");
$select_spediz->output('imball', 'imball');
echo "</td>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['vettor'] . "</td>\n";
echo "<td class=\"FacetDataTD\">\n";
$select_vettor = new selectvettor("vettor");
$select_vettor->addSelected($form["vettor"]);
$select_vettor->output();
echo "</td>\n";
echo "</tr></table>\n";
if (!empty($form['righi'])) {
    echo '<div align="center"><b>' . $script_transl['preview_title'] . '</b></div>';
    echo "<table class=\"Tlarge table table-striped table-bordered table-condensed\">";
    echo "<tr class=\"FacetFieldCaptionTD\"><td> " . $script_transl['codart'] . "</td>
   <td> " . $script_transl['descri'] . "</td>
   <td align=\"center\"> " . $script_transl['unimis'] . "</td>
   <td align=\"right\"> " . $script_transl['quanti'] . "</td>
   <td align=\"right\"> " . $script_transl['prezzo'] . "</td>
   <td align=\"right\"> " . $script_transl['sconto'] . "</td>
   <td align=\"right\"> " . $script_transl['provvigione'] . "</td>
   <td align=\"right\"> " . $script_transl['amount'] . "</td>
   </tr>";
    $ctrl_tes = 0;
    $total_order = 0;
    foreach ($form['righi'] as $k => $v) {
        $checkin = ' disabled ';
        $imprig = 0;
        //calcolo importo rigo
        switch ($v['tiprig']) {
            case "0":
                $imprig = CalcolaImportoRigo($form['righi'][$k]['quanti'], $form['righi'][$k]['prelis'], $form['righi'][$k]['sconto']);
                if ($v['id_doc'] == 0) {
                    $checkin = ' checked';
                    $total_order += $imprig;
                }
                break;
            case "1":
                $imprig = CalcolaImportoRigo(1, $form['righi'][$k]['prelis'], 0);
                if ($v['id_doc'] == 0) {
                    $checkin = ' checked';
                    $total_order += $imprig;
                }
                break;
            case "2":
                $checkin = '';
                break;
            case "3":
                $checkin = '';
                break;
            case "6":
                $body_text = gaz_dbi_get_row($gTables['body_text'], 'id_body', $v['id_body_text']);
                $v['descri'] = substr($body_text['body_text'], 0, 80);
                $checkin = '';
                break;
        }
        if ($ctrl_tes != $v['id_tes']) {
            echo "<tr><td class=\"FacetDataTD\" colspan=\"7\"> " . $script_transl['from'] . " <a href=\"admin_broven.php?Update&id_tes=" . $v["id_tes"] . "\" title=\"" . $script_transl['upd_ord'] . "\"> " . $script_transl['doc_name'][$v['tipdoc']] . " n." . $v['numdoc'] . "</a> " . $script_transl['del'] . ' ' . gaz_format_date($v['datemi']) . " </td></tr>";
        }
        echo "<tr>";
        echo "<input type=\"hidden\" name=\"righi[$k][id_tes]\" value=\"" . $v['id_tes'] . "\">\n";
        echo "<input type=\"hidden\" name=\"righi[$k][datemi]\" value=\"" . $v['datemi'] . "\">\n";
        echo "<input type=\"hidden\" name=\"righi[$k][tipdoc]\" value=\"" . $v['tipdoc'] . "\">\n";
        echo "<input type=\"hidden\" name=\"righi[$k][numdoc]\" value=\"" . $v['numdoc'] . "\">\n";
        echo "<input type=\"hidden\" name=\"righi[$k][id_rig]\" value=\"" . $v['id_rig'] . "\">\n";
        echo "<input type=\"hidden\" name=\"righi[$k][tiprig]\" value=\"" . $v['tiprig'] . "\">\n";
        echo "<input type=\"hidden\" name=\"righi[$k][id_doc]\" value=\"" . $v['id_doc'] . "\">\n";
        echo "<input type=\"hidden\" name=\"righi[$k][id_body_text]\" value=\"" . $v['id_body_text'] . "\">\n";
        echo "<input type=\"hidden\" name=\"righi[$k][codvat]\" value=\"" . $v['codvat'] . "\">\n";
        echo "<input type=\"hidden\" name=\"righi[$k][pervat]\" value=\"" . $v['pervat'] . "\">\n";
        echo "<input type=\"hidden\" name=\"righi[$k][ritenuta]\" value=\"" . $v['ritenuta'] . "\">\n";
        echo "<input type=\"hidden\" name=\"righi[$k][codric]\" value=\"" . $v['codric'] . "\">\n";
        echo "<td><input type=\"hidden\" name=\"righi[$k][codart]\" value=\"" . $v['codart'] . "\">" . $v['codart'] . "</td>\n";
        echo "<td><input type=\"hidden\" name=\"righi[$k][descri]\" value=\"" . $v['descri'] . "\">" . $v['descri'] . "</td>\n";
        echo "<td align=\"center\"><input type=\"hidden\" name=\"righi[$k][unimis]\" value=\"" . $v['unimis'] . "\">" . $v['unimis'] . "</td>\n";
        echo "<td align=\"right\"><input type=\"hidden\" name=\"righi[$k][quanti]\" value=\"" . $v['quanti'] . "\">" . $v['quanti'] . "</td>\n";
        echo "<td align=\"right\"><input type=\"hidden\" name=\"righi[$k][prelis]\" value=\"" . $v['prelis'] . "\">" . $v['prelis'] . "</td>\n";
        echo "<td align=\"right\"><input type=\"hidden\" name=\"righi[$k][provvigione]\" value=\"" . $v['provvigione'] . "\">" . $v['provvigione'] . "</td>\n";
        echo "<td align=\"right\"><input type=\"hidden\" name=\"righi[$k][sconto]\" value=\"" . $v['sconto'] . "\">" . $v['sconto'] . "</td>\n";
        echo "<td class=\"FacetDataTD\" align=\"right\">$imprig</td>\n";
        echo "<td class=\"FacetFieldCaptionTD\" align=\"center\"><input type=\"checkbox\" name=\"righi[$k][checkval]\"  title=\"" . $script_transl['checkbox'] . "\" $checkin value=\"$imprig\" onclick=\"this.form.total.value=calcheck(this);\"></td>\n";
        echo "</tr>";
        $ctrl_tes = $v['id_tes'];
    }
    echo "<tr><td class=\"FacetDataTD\">\n";
    echo "<input type=\"submit\" name=\"Return\" value=\"" . $script_transl['return'] . "\">&nbsp;</td>\n";
    echo "<td align=\"right\" colspan=\"5\" class=\"FacetFieldCaptionTD\">\n";
    echo "<input type=\"submit\" name=\"ddt\" value=\"" . $script_transl['issue_ddt'] . "\" accesskey=\"d\" />\n";
    echo "<input type=\"submit\" name=\"fai\" value=\"" . $script_transl['issue_fat'] . "\" accesskey=\"f\" />\n";
    if (!empty($alert_sezione))
        echo " &sup1;";
    echo "<input type=\"submit\" name=\"vco\" value=\"" . $script_transl['issue_cor'] . "\" accesskey=\"c\" />\n";
    echo "</td><input type=\"hidden\" name=\"hiddentot\" value=\"$total_order\">\n";
    echo "<td colspan=\"2\" class=\"FacetFieldCaptionTD\" align=\"right\">" . $script_transl['taxable'] . " " . $admin_aziend['html_symbol'] . " &nbsp;\n";
    echo "<input type=\"text\"  style=\"text-align:right;\" value=\"" . number_format(($total_order - $total_order * $form['sconto'] / 100 + $form['traspo']), 2, '.', '') . "\" name=\"total\"  readonly />\n";
    echo "</td></tr>";
    if (!empty($alert_sezione))
        echo "<tr><td colspan=\"3\"></td><td colspan=\"2\" class=\"FacetDataTDred\">$alert_sezione </td></tr>";
}
?>
    </table>
    </div>
</form>
<script type="text/javascript">
    function pulldown_menu(selectName, destField)
    {
        // Create a variable url to contain the value of the
        // selected option from the the form named broven and variable selectName
        var url = document.myform[selectName].options[document.myform[selectName].selectedIndex].value;
        document.myform[destField].value = url;
    }

    function calcheck(checkin)
    {
        with (checkin.form) {
            if (checkin.checked == false) {
                hiddentot.value = eval(hiddentot.value) - eval(checkin.value);
            } else {
                hiddentot.value = eval(hiddentot.value) + eval(checkin.value);
            }
            var totalecheck = eval(hiddentot.value) - eval(hiddentot.value) * eval(sconto.value) / 100 + eval(traspo.value);
            return((Math.round(totalecheck * 100) / 100).toFixed(2));
        }
    }

    function summa(sumtraspo)
    {
        if (isNaN(parseFloat(eval(sumtraspo.value)))) {
            sumtraspo.value = 0.00;
        }
        var totalecheck = eval(document.myform.hiddentot.value) - eval(document.myform.hiddentot.value) * eval(document.myform.sconto.value) / 100 + eval(sumtraspo.value);
        return((Math.round(totalecheck * 100) / 100).toFixed(2));
    }

    function sconta(percsconto)
    {
        if (isNaN(parseFloat(eval(percsconto.value)))) {
            percsconto.value = 0.00;
        }
        var totalecheck = eval(document.myform.hiddentot.value) - eval(document.myform.hiddentot.value) * eval(percsconto.value) / 100 + eval(document.myform.traspo.value);
        return((Math.round(totalecheck * 100) / 100).toFixed(2));
    }

</script>
<script type="text/javascript" id="datapopup">
    var cal = new CalendarPopup();
    cal.setReturnFunction("setMultipleValues");
    function setMultipleValues(y, m, d) {
        document.getElementById(calName + '_Y').value = y;
        document.getElementById(calName + '_M').selectedIndex = m * 1 - 1;
        document.getElementById(calName + '_D').selectedIndex = d * 1 - 1;
    }
    function setDate(name) {
        calName = name.toString();
        var year = document.getElementById(calName + '_Y').value.toString();
        var month = document.getElementById(calName + '_M').value.toString();
        var day = document.getElementById(calName + '_D').value.toString();
        var mdy = month + '/' + day + '/' + year;
        cal.setReturnFunction('setMultipleValues');
        cal.showCalendar('anchor', mdy);
    }
</script>
<?php
require("../../library/include/footer.php");
?>
