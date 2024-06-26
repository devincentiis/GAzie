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
  scriva   alla   Free  Software Foundation,  Inc.,   59
  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();

function getErrors($year) {
    global $gTables, $admin_aziend;
    $e = array();
    $where = "regiva > 0 and YEAR(datreg) = " . $year;
    $orderby = "seziva, regiva, datreg, protoc ";
    $rs = gaz_dbi_dyn_query("*,(seziva*10+regiva) AS ctrl_sr, DATE_FORMAT(datdoc,'%d-%m-%Y') AS dd, DATE_FORMAT(datreg,'%d-%m-%Y') AS dr", $gTables['tesmov'], $where, $orderby);
    $c_sr = 0;
    $c_p = 0;
    $c_ndoc = array();

    // BEGIN fromthestone: prendo il valore della configurazione per numerazione note credito/debito
    $num_nc_nd = gaz_dbi_get_row($gTables['company_config'], 'var', 'num_note_separate')['val'];
    // END fromthestone

    while ($r = gaz_dbi_fetch_array($rs)) {
        if ($c_sr != ($r['ctrl_sr'])) { // devo azzerare tutto perché è cambiata la sezione o il registro
            $c_sr = 0;
            $c_p = 0;
            $c_ndoc = array();
            if ($r['protoc'] <> 1) { // errore: il protocollo non è 1
                $e[] = array('err' => 'P', 'id' => $r['id_tes'], 'rg' => $r['regiva'], 'pr' => $r['protoc'], 'nd' => $r['numdoc'], 'dd' => $r['dd'], 'sz' => $r['seziva'], 'ty' => $r['caucon'], 'ex' => 1, 'de' => $r['descri'], 'dr' => $r['dr']);
            }
        } else {
            $ex = $c_p + 1;
            if ($r['protoc'] <> $ex) {  // errore: il protocollo non è consecutivo
                $e[] = array('err' => 'P', 'id' => $r['id_tes'], 'rg' => $r['regiva'], 'pr' => $r['protoc'], 'nd' => $r['numdoc'], 'dd' => $r['dd'], 'sz' => $r['seziva'], 'ty' => $r['caucon'], 'ex' => $ex, 'de' => $r['descri'], 'dr' => $r['dr']);
            }
        }
        if ($r['regiva'] < 4) { // il controllo sul numero solo per i registri delle fatture
            // fromthestone: comportamento standard, note credito e debito con diversa numerazione da fatture ->
          // num_note_separate = 1
            // per evitare la segnalazione di errore quando si passa da fattura immediata a differita e viceversa
            if ($num_nc_nd == 1) {
                $r['caucon'] = ($r['caucon']=='FAD')?'FAI':$r['caucon'];
            }
            else {
              // fromthestone: note credito e debito stessa numerazione da fatture
              $r['caucon'] = ($r['caucon'] == 'FAD' || $r['caucon'] == 'FNC' || $r['caucon'] == 'FND') ? 'FAI' : $r['caucon'];
            }

          if (isset($c_ndoc[$r['caucon']])) { // controllo se il numero precedente è questo-1
                $ex = $c_ndoc[$r['caucon']] + 1;
                if ($r['numdoc'] <> $ex) {  // errore: il numero non è consecutivo
                    $e[] = array('err' => 'N', 'id' => $r['id_tes'], 'rg' => $r['regiva'], 'pr' => $r['protoc'], 'nd' => $r['numdoc'], 'dd' => $r['dd'], 'sz' => $r['seziva'], 'ty' => $r['caucon'], 'ex' => $ex, 'de' => $r['descri'], 'dr' => $r['dr']);
                }
            } else {  // dal primo documento di questo tipo ci si aspetta il n.1
                if ($r['numdoc'] <> 1) { // errore: il numero non è 1
                    $e[] = array('err' => 'N', 'id' => $r['id_tes'], 'rg' => $r['regiva'], 'pr' => $r['protoc'], 'nd' => $r['numdoc'], 'dd' => $r['dd'], 'sz' => $r['seziva'], 'ty' => $r['caucon'], 'ex' => 1, 'de' => $r['descri'], 'dr' => $r['dr']);
                }
            }
        }
        $c_ndoc[$r['caucon']] = $r['numdoc'];
        $c_sr = $r['ctrl_sr'];
        $c_p = $r['protoc'];
    }
    return $e;
}

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['year'] = $anno = date("Y");
} else {
    $form['hidden_req'] = htmlentities($_POST['hidden_req']);
    $form['ritorno'] = $_POST['ritorno'];
    $form['year'] = intval($_POST['year']);
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new GAzieForm();
$linkHeaders = new linkHeaders($script_transl['header']);
echo "<form method=\"POST\" name=\"select\">\n";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['ritorno'] . "\" name=\"ritorno\" />\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'] . $script_transl['year'];
$gForm->selectNumber('year', $form['year'], 1, $form['year'] - 10, $form['year'] + 10, 'FacetSelect', 'year');
echo "\t </div>\n";
echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">\n";
$ctrl = 0;
$ctrl_reg = "";
$m = getErrors($form['year']);
if (sizeof($m) > 0) {
    $ctr_mv = '';
    echo "<tr>";
    $linkHeaders = new linkHeaders($script_transl['header']);
    $linkHeaders->output();
    echo "</tr>";
	foreach ($m AS $key => $mv) {
        if ($mv['err'] == 'P') {
            $p = 'red" >' . $mv["pr"] . $script_transl['expect'] . $mv["ex"];
            $nred = '';
            $n = $mv["nd"];
        } else {
            $p = '">' . $mv["pr"];
            $nred = 'red';
            $n = $mv["nd"] . ' (' . $script_transl['expect'] . $mv["ex"] . ')';
        }
        echo "<tr><td class=\"FacetDataTD\" align=\"center\"><a href=\"../contab/admin_movcon.php?Update&id_tes=" . $mv["id"] . "\" title=\"Modifica il movimento\" >" . $mv["id"] . "</a></td>\n
               <td class=\"FacetDataTD\" align=\"center\">" . $mv["dr"] . "</td>\n
               <td class=\"FacetDataTD\" align=\"center\">" . $mv["sz"] . "</td>\n
               <td class=\"FacetDataTD\" align=\"center\">" . $mv["rg"] . "</td>\n
               <td align=\"center\" class=\"FacetDataTD$p</td>\n
               <td class=\"FacetDataTD\" align=\"center\">" . $mv["ty"] . "</td>\n
               <td class=\"FacetDataTD$nred\">" . $mv["de"] . " n." . $n . $script_transl['pre_dd'] . $mv["dd"] . "</td></tr>\n";
    }
}
echo "</table></form>";
require("../../library/include/footer.php");
?>
