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
  scriva   alla   Free  Software Foundation,  Inc.,   59
  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
$msg = '';

function extract_gaziecartxml($conn, $msgno) {  // estraggo l'allegatoxml dagli ordini
    $att = '';
    $structure = imap_fetchstructure($conn, $msgno);
    if (isset($structure->parts) && count($structure->parts)) {
        for ($i = 0; $i < count($structure->parts); $i++) {
            if ($structure->parts[$i]->ifdparameters) {
                foreach ($structure->parts[$i]->dparameters as $object) {
                    if ((strtolower($object->attribute) == 'filename' || strtolower($object->attribute) == 'name') && empty($att) && $object->value == 'gaziecart.xml') {
                        $att = imap_fetchbody($conn, $msgno, $i + 1);
                        if ($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                            $att = base64_decode($att);
                        } elseif ($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                            $att = quoted_printable_decode($att);
                        }
                    }
                }
            }
        }
    }
    return $att;
}

if (!extension_loaded('imap')) {
    $msg .="2+";
}

if (!isset($_POST['ritorno'])) { // al primo accesso allo script
    $vat = gaz_dbi_get_row($gTables['aliiva'], 'codice', $admin_aziend['preeminent_vat']);
    $form['vat'] = $vat['aliquo'];
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
    $mail = gaz_dbi_get_row($gTables['company_config'], 'var', 'order_mail');
    $form['email'] = $mail['val'];
    $mail = gaz_dbi_get_row($gTables['company_config'], 'var', 'order_server');
    $form['server'] = $mail['val'];
    $form['pass'] = '__nopassword__';
} else { //se non e' il primo accesso
    //qui si deve fare un parsing di quanto arriva dal browser...
    $form['ritorno'] = $_POST['ritorno'];
    $form['vat'] = $_POST['vat'];
    $form['email'] = $_POST['email'];
    $form['pass'] = $_POST['pass'];
    $form['server'] = $_POST['server'];
    $form['hidden_req'] = $_POST['hidden_req'];
    foreach ($_POST['search'] as $k => $v) {
        $form['search'][$k] = $v;
        if (substr($k, 0, 6) == 'clfoco') {
            $form[$k] = $_POST[$k];
        }
    }
    // Se viene inviata la richiesta di importare un ordine
    if (isset($_POST['import'])) {
        // faccio il controllo se e' stato selezionato il relativo cliente
        $n = key($_POST['import']);
        if ($form['clfoco' . $n] < 100000000) { // se non e' stato selezionato il cliente restituisco l'errore
            $msg .="0+";
        } else {
            $anagrafica = new Anagrafica();
            $cli = $anagrafica->getPartner(intval($form['clfoco' . $n]));
        }
        if (empty($msg)) {  // non ci sono errori formali: importo l'ordine web
            $form['tipdoc'] = 'VOW';
            $form['seziva'] = 1;
            $form['numdoc'] = $n;
            $form['clfoco'] = $form['clfoco' . $n];
            $form['pagame'] = $cli['codpag'];
            $form['banapp'] = $cli['banapp'];
            $form['listin'] = $cli['listin'];
            $form['status'] = 'GENERATO';
            $form['initra'] = substr($n, 0, 4) . "-" . substr($n, 4, 2) . "-" . substr($n, 6, 2) . " " . substr($n, 8, 2) . ":" . substr($n, 10, 2) . ":" . substr($n, 12, 2);
            $form['datemi'] = substr($n, 0, 4) . "-" . substr($n, 4, 2) . "-" . substr($n, 6, 2);
            $text = filter_var($_POST['order'][$n]['text'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            tesbroInsert($form);
            //recupero l'id assegnato dall'inserimento
            $ultimo_id = gaz_dbi_last_id();
            //inserisco i rows
            foreach ($_POST['order'][$n]['row'] as $kr => $vr) {
                $item = gaz_dbi_get_row($gTables['artico'], 'codice', $vr['codice']);

                $data['id_tes'] = $ultimo_id;
                $data['codart'] = $vr['codice'];
                $data['tiprig'] = 0;
                $data['descri'] = $vr['descri'];
                $data['codvat'] = $item['aliiva'];
                $data['pervat'] = $vr['taxrate'];
                if ($item['codcon'] > 100000000) {
                    $data['codric'] = $item['codcon'];
                } else {
                    $data['codric'] = $admin_aziend['impven'];
                }
                $data['status'] = 'INSERT';
                if (strtoupper($item['unimis']) == strtoupper($vr['unimis'])) {
                    // unita' di misura coincidenti
                    $data['quanti'] = $vr['quanti'];
                    $data['unimis'] = $vr['unimis'];
                    $data['prelis'] = $vr['prezzoweb'];
                    rigbroInsert($data);
                } else {
                    require("lang." . $admin_aziend['lang'] . ".php");
                    $script_transl = $strScript['import_gaziecart.php'];
                    // nel caso in cui il sito usa una unita' diversa dalla normale
                    $r_tot = CalcolaImportoRigo($vr['quanti'], $vr['prezzoweb'], 0);
                    $data['unimis'] = $item['unimis'];
                    if ($item['web_multiplier'] <> 0) {
                        $q = round($item['web_multiplier'] * $vr['quanti'], intval($admin_aziend['decimal_quantity']));
                    } else {
                        $q = $vr['quanti'];
                    }
                    $price = floatval($r_tot / $q);
                    $data['quanti'] = $q;
                    $data['prelis'] = $price;
                    rigbroInsert($data);
                    $dr['tiprig'] = 2;
                    $dr['id_tes'] = $ultimo_id;
                    $dr['descri'] = '\'--> ' . $script_transl['des1'] . $vr['quanti'] . ' ' . $vr['unimis'] . ' x ' . $vr['prezzoweb'] . ' ' . $admin_aziend['curr_name'];
                    $dr['status'] = 'INSERT';
                    rigbroInsert($dr);
                }
            }
            if (!empty($text)) { // se ho una comunicazione del cliente in allegato
                rigbroInsert(array('id_tes' => $ultimo_id, 'tiprig' => 6));
                $last_rigbro_id = gaz_dbi_last_id();
                bodytextInsert(array('table_name_ref' => 'rigbro', 'id_ref' => $last_rigbro_id, 'body_text' => $text));
                gaz_dbi_put_row($gTables['rigbro'], 'id_rig', $last_rigbro_id, 'id_body_text', gaz_dbi_last_id());
            }
            $_SESSION['print_request'] = $ultimo_id;
            header("Location: invsta_broven.php");
            exit;
            $_POST['readmail'] = true;
        }
    }
    // Se viene inviata la richiesta di leggere la casella di posta
    if (isset($_POST['readmail'])) {
        if ($form['pass'] == '__nopassword__') {
            $mail = gaz_dbi_get_row($gTables['company_config'], 'var', 'order_pass');
            $form['pass'] = $mail['val'];
        } else { // se ho impostato una nuova password modifico quella del database
            gaz_dbi_put_row($gTables['company_config'], 'var', 'order_pass', 'val', $form['pass']);
        }
        if (preg_match("/^([_a-z0-9-]+\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $form['email'], $regs)) {
            gaz_dbi_put_row($gTables['company_config'], 'var', 'order_mail', 'val', $form['email']);
            gaz_dbi_put_row($gTables['company_config'], 'var', 'order_server', 'val', $form['server']);
        }
        // agire qui sotto per modificare manualmente gli altri parametri di connessione
        $conn = imap_open("{" . $form['server'] . ":110/pop3/notls}INBOX", $regs[1], $form['pass']);
        $n_messaggi = imap_num_msg($conn);
        $messaggi = imap_fetch_overview($conn, "1:$n_messaggi");
		foreach ($messaggi AS $key => $value) {
            if ($value->seen == 0) {
                if (preg_match("/^Ordine n\.([0-9]{14})$/", $value->subject, $regs)) {  // se l'oggetto e' un ordine proveniente da GAzieCart prelevo l'allegato
                    // in $regs[1] c'� il numero dell'ordine che serve per controllare se � gi� stato elaborato
                    $rs = gaz_dbi_dyn_query("*", $gTables['tesbro'], "tipdoc = 'VOW' AND numdoc = " . $regs[1], "id_tes", 0, 1);
                    $y = gaz_dbi_fetch_array($rs);
                    if ($y) {
                        $form['order'][$regs[1]]['name'] = '__ACQUIRED__';
                        $form['order'][$regs[1]]['text'] = '';
                    } else { // acquisisco i dati dell'ordine e li mostro
                        $ord_xml = extract_gaziecartxml($conn, $value->msgno);
                        $xml = simplexml_load_string($ord_xml);
                        $n = substr($xml->head->number, 0, 14);
                        $form['order'][$n]['name'] = $xml->head->name;
                        $form['clfoco' . $n] = 0;
                        $form['search']['clfoco' . $n] = $xml->head->name;
                        $form['order'][$n]['text'] = $xml->head->text;
                        $r = 0;
                        foreach ($xml->row as $vr) {
                            $form['order'][$n]['row'][$r]['codice'] = $vr->codice;
                            $form['order'][$n]['row'][$r]['descri'] = $vr->descri;
                            $form['order'][$n]['row'][$r]['unimis'] = $vr->unimis;
                            $form['order'][$n]['row'][$r]['quanti'] = $vr->quanti;
                            $form['order'][$n]['row'][$r]['prezzoweb'] = $vr->prezzoweb;
                            $form['order'][$n]['row'][$r]['taxrate'] = $vr->taxrate;
                            $r++;
                        }
                    }
                }
            }
        }
        @imap_close($conn);
    } else {
        foreach ($_POST['order'] as $k => $v) {
            $form['order'][$k] = $v;
        }
    }
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array(''));
echo "<form method=\"POST\" name=\"order\">\n";
$gForm = new GAzieForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'] . "</div>\n";
echo "<input type=\"hidden\" value=\"" . $form['ritorno'] . "\" name=\"ritorno\">\n";
echo "<input type=\"hidden\" value=\"\" name=\"search['none']\">\n";
echo "<input type=\"hidden\" value=\"" . $form['vat'] . "\" name=\"vat\">\n";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="6" class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['email'] . "</td><td class=\"FacetDataTD\">\n";
echo '<input type="text" name="email" id="email" value="' . $form['email'] . '" />';
echo "\t</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['pass'] . "</td><td class=\"FacetDataTD\">\n";
echo '<input type="password" name="pass" id="pass" value="' . $form['pass'] . '" />';
echo "\t</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['server'] . "</td><td class=\"FacetDataTD\">\n";
echo '<input type="text" name="server" id="server" value="' . $form['server'] . '" />';
echo "\t</td>\n";
echo "</tr>\n";
echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
echo '<td colspan="6" align="right"> <input type="submit" name="readmail" value="';
echo $script_transl['submit'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";
echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">\n";

// Se viene inviata la richiesta di leggere la casella di posta...
if (isset($form['order'])) {
    foreach ($form['order'] as $k => $v) {
        echo "<input type=\"hidden\" value=\"" . $v['name'] . "\" name=\"order[$k][name]\">\n";
        echo "<input type=\"hidden\" value=\"" . $v['text'] . "\" name=\"order[$k][text]\">\n";
        if ($v['name'] == '__ACQUIRED__') {
            echo "<tr><td class=\"FacetFieldCaptionTD\" colspan=\"6\">" . $script_transl['name'] . " n." . $k . " &egrave; gi&agrave; stato acquisito</td></tr>\n";
        } else { // acquisisco i dati dell'ordine e li mostro
            echo "<tr>";
            echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['name'] . " n." . $k . " </td>";
            echo "<td class=\"FacetFieldCaptionTD\" colspan=\"6\">" . $v['name'];
            $select_cliente = new selectPartner('clfoco' . $k);
            $select_cliente->selectDocPartner('clfoco' . $k, $form['clfoco' . $k], $form['search']['clfoco' . $k], 'clfoco' . $k, $script_transl['mesg'], $admin_aziend['mascli']);
            echo " &nbsp;</td>";
            echo "</tr>\n";
            $tot = 0;
            $vat = 0;
            foreach ($v['row'] as $kr => $vr) {
                echo "<input type=\"hidden\" value=\"" . $vr['codice'] . "\" name=\"order[$k][row][$kr][codice]\">\n";
                echo "<input type=\"hidden\" value=\"" . $vr['descri'] . "\" name=\"order[$k][row][$kr][descri]\">\n";
                echo "<input type=\"hidden\" value=\"" . $vr['unimis'] . "\" name=\"order[$k][row][$kr][unimis]\">\n";
                echo "<input type=\"hidden\" value=\"" . $vr['prezzoweb'] . "\" name=\"order[$k][row][$kr][prezzoweb]\">\n";
                echo "<input type=\"hidden\" value=\"" . $vr['quanti'] . "\" name=\"order[$k][row][$kr][quanti]\">\n";
                echo "<input type=\"hidden\" value=\"" . $vr['taxrate'] . "\" name=\"order[$k][row][$kr][taxrate]\">\n";
                $tot_r = round($vr['quanti'] * floatval($vr['prezzoweb']), 2);
                $tot+=$tot_r;
                echo "<tr>";
                echo "<td class=\"FacetDataTD\">" . $vr['codice'] . " </td>";
                echo "<td class=\"FacetDataTD\">" . $vr['descri'] . " </td>";
                echo "<td class=\"FacetDataTD\">" . $vr['unimis'] . " </td>";
                echo "<td class=\"FacetDataTD\" align=\"right\"> " . $vr['quanti'] . " &nbsp;</td>";
                echo "<td class=\"FacetDataTD\" align=\"right\"> " . $vr['prezzoweb'] . " &nbsp;</td>";
                echo "<td class=\"FacetDataTD\" align=\"right\"> " . gaz_format_number($tot_r) . " &nbsp;</td>";
                echo "<td class=\"FacetDataTD\" align=\"right\"> " . floatval($vr['taxrate']) . "%</td>";
                echo "</tr>\n";
                $vat+=$tot_r * $vr['taxrate'] / 100;
            }
            $vat = round($vat, 2);
            echo "<tr><td colspan=\"6\" class=\"FacetDataTD\" align=\"right\">" . $script_transl['amount'] . ": " . gaz_format_number($tot) . " &nbsp;</td></tr>\n";
            echo "<tr><td colspan=\"6\" class=\"FacetDataTD\" align=\"right\">" . $script_transl['tax'] . ": " . gaz_format_number($vat) . " &nbsp;</td></tr>\n";
            echo "<tr><td colspan=\"6\" class=\"FacetDataTD\" align=\"right\">" . $script_transl['tot'] . $script_transl['name'] . ": " . gaz_format_number($tot + $vat) . " &nbsp;</td></tr>\n";
            echo "<tr><td class=\"FacetDataTD\" colspan=\"5\">" . $v['text'] . " &nbsp;</td><td align=\"right\"><input class=\"FacetText\" type=\"submit\" name=\"import[" . $k . "]\" value=\"Acquisisci!\" /></td></tr>\n";
        }
        echo "<tr><td colspan=\"7\"><hr /></td></tr>\n";
    }
    echo "</table>\n";
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>