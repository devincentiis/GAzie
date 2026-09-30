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
$msg=['err'=>[],'war'=>[]];
$msg['war'][] = 'alert';
function getLastMonth($sez, $reg) { // Antonio Germani - funzione per recuperare, dal DB, l'ultimo mese stampato
    global $gTables;
    if ($reg == 6) {
        $reg = 'umeac' . $sez;
    } elseif ($reg == 4) {
        $reg = 'umeco' . $sez;
    } else {
        $reg = 'umeve' . $sez;
    }
    $r = gaz_dbi_get_row($gTables['company_data'], 'var', $reg);
	return $r['data'];
}

function getMovements($vat_section, $vat_reg, $anno) {
    global $gTables, $admin_aziend;
    // BEGIN fromthestone: prendo il valore della configurazione per numerazione note credito/debito
    $num_nc_nd = gaz_dbi_get_row($gTables['company_config'], 'var', 'num_note_separate')['val'];
    // END fromthestone
    $m = array();
    $where = "(datreg BETWEEN ".$anno."0101 AND ".$anno."1231) AND seziva = ".$vat_section." AND regiva =". $vat_reg;
    $orderby = "datreg, protoc";
    $rs = gaz_dbi_dyn_query("YEAR(datreg) AS ctrl_sr,
					  DATE_FORMAT(datliq,'%Y%m%d') AS dl,
                      DATE_FORMAT(datreg,'%Y%m%d') AS dr,
                      CONCAT(" . $gTables['anagra'] . ".ragso1, ' '," . $gTables['anagra'] . ".ragso2) AS ragsoc,clfoco,codiva,
                      protoc,numdoc,datreg,datliq,datdoc,caucon,regiva,operat,imponi,impost,periva,
                      " . $gTables['tesmov'] . ".descri AS descri,
                      " . $gTables['aliiva'] . ".descri AS desvat,
                      " . $gTables['tesmov'] . ".id_tes AS id_tes,
                      " . $gTables['rigmoi'] . ".tipiva AS tipiva", $gTables['rigmoi'] . " LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoi'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes
        LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesmov'] . ".clfoco = " . $gTables['clfoco'] . ".codice
        LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['anagra'] . ".id = " . $gTables['clfoco'] . ".id_anagra
        LEFT JOIN " . $gTables['aliiva'] . " ON " . $gTables['rigmoi'] . ".codiva = " . $gTables['aliiva'] . ".codice", $where, $orderby);
    $c_sr = 0;
    $c_id = 0;
    $c_p = 0;
    $c_ndoc = array();
    while ($r = gaz_dbi_fetch_array($rs)) {
      $r['numdoc']=is_numeric($r['numdoc'])?$r['numdoc']: (int)filter_var($r['numdoc'], FILTER_SANITIZE_NUMBER_INT);;
      // inizio controllo errori di numerazione
      $date_reg=gaz_format_date($r['datreg'],false,3);
      if (empty($r['tipiva'])) {  // errore: aliquota IVA non tipizzata
        $r['err_t'] = 'ERROR';
      }
      if ($c_sr != ($r['ctrl_sr'])) { // devo azzerare tutto perché cambiato l'anno
        $numero_atteso_posizione = $r['protoc'];
        $c_sr = 0;
        $c_id = 0;
        $c_p = 0;
        $c_ndoc = array();
        if ($r['protoc'] <> 1) { // errore: il protocollo non é 1
          // non lo rilevo in quanto i registri IVA non sono annuali
        }
      } else {
        $ex = $c_p + 1;
        if ($r['id_tes'] <> $c_id) {
          $numero_atteso_posizione++;
          if ($r['protoc'] <> $numero_atteso_posizione) {  // errore: il protocollo non è consecutivo
            $r['err_p'] = $numero_atteso_posizione;
          }
        }
      }
      if ($r['regiva'] < 4 && $vat_section <> $admin_aziend['reverse_charge_sez']) { // il controllo sul numero solo per i registri delle fatture di vendita e non reverse charge
        // fromthestone: comportamento standard, note credito e debito con diversa numerazione da fatture ->
        // num_note_separate = 1
        // per evitare la segnalazione di errore quando si passa da fattura immediata a differita e viceversa
        if ($num_nc_nd == 1) {
          $r['caucon'] = ($r['caucon']=='FAD')?'FAI':$r['caucon'];
        } else {
          // fromthestone: note credito e debito stessa numerazione da fatture
          $r['caucon'] = ($r['caucon'] == 'FAD' || $r['caucon'] == 'FNC' || $r['caucon'] == 'FND') ? 'FAI' : $r['caucon'];
        }
        if (isset($c_ndoc[$r['caucon']])) { // controllo se il numero precedente � questo-1
          $ex = $c_ndoc[$r['caucon']] + 1;
          if ($r['numdoc'] <> $ex && $c_id <> $r['id_tes']) {  // errore: il numero non � consecutivo
            $r['err_n'] = $ex;
          }
        } else {  // dal primo documento di questo tipo ci si aspetta il n.1
          if ($r['numdoc'] <> 1) { // errore: il numero non � 1
            // non lo rilevo in quanto i registri IVA non sono annuali
          }
        }
      }
      $c_ndoc[$r['caucon']] = $r['numdoc'];
      $c_sr = $r['ctrl_sr'];
      $c_id = $r['id_tes'];
      $c_p = $r['protoc'];
      // fine controllo errori di numerazione
      $m[] = $r;
    }
    return $m;
}



if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
  $form['hidden_req'] = '';
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  require("lang." . $admin_aziend['lang'] . ".php");
  $form['anno'] = date("Y");
  $form['vat_section'] = 1;
  $form['vat_reg'] = 0;
} else { // accessi successivi
  $form['hidden_req'] = htmlentities($_POST['hidden_req']);
  $form['ritorno'] = $_POST['ritorno'];
  $form['anno'] = intval($_POST['anno']);
  $form['vat_section'] = intval($_POST['vat_section']);
  $form['vat_reg'] = intval($_POST['vat_reg']);
  if ($form['hidden_req'] == 'vat_reg' || $form['hidden_req'] == 'vat_section' || $form['hidden_req'] == 'anno') {
    $form['hidden_req'] = '';
  }
}

if (isset($_POST['renum'])) {
  $m = getMovements($form['vat_section'], $form['vat_reg'], $form['anno']);
  if (sizeof($m) > 0) {
    foreach ($m AS $key => $mv) {
      if (isset($mv['err_p'])) {
        // un errore modifico tesmov
        gaz_dbi_query("UPDATE ".$gTables['tesmov']." SET protoc = ".$mv['err_p']." WHERE id_tes = ".$mv['id_tes']);
        // modifico anche il riferimento in paymov
        $old_id_tesdoc_ref = substr($mv['datreg'], 0, 4) . $form['vat_reg'] . $form['vat_section'] . str_pad($mv['protoc'], 9, 0, STR_PAD_LEFT);
        $new_id_tesdoc_ref = substr($mv['datreg'], 0, 4) . $form['vat_reg'] . $form['vat_section'] . str_pad($mv['err_p'], 9, 0, STR_PAD_LEFT);
        gaz_dbi_query("UPDATE ".$gTables['paymov']." SET id_tesdoc_ref = '".$new_id_tesdoc_ref."' WHERE id_tesdoc_ref = '".$old_id_tesdoc_ref."'");
      }
    }
  }
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new contabForm();
if (count($msg['war']) > 0) { // ho un errore
  $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
}
if (count($msg['err']) > 0) { // ho un errore
  $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
?>
<script>
$(function () {
  $('#newcodart').bind("change keyup", function() {
    var val = $(this).val();
    var regex = /[^a-zA-Z0-9 _\-\.\/,!Ф()?]/g;
    if (val.match(regex)) {
      $(this).css("background", "red");
      val = val.replace(regex, "");
      $(this).val(val);
    } else {
      $(this).css("background", "white");
    }
  });
});
</script>
<form method="POST" name="select">
<input type="hidden" value="<?php echo $form['hidden_req']; ?>" name="hidden_req" />
<input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno" />
<div class="FacetFormHeaderFont text-center"><?php echo $script_transl['title']; ?></div>
<?php
echo "<table class=\"Tmiddle table-striped\">\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['vat_reg'] . "</td><td  class=\"FacetDataTD\">\n";
$gForm->variousSelect('vat_reg', $script_transl['vat_reg_value'], $form['vat_reg'], 'FacetSelect', true, 'vat_reg');
echo "</td>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['vat_section'] . "</td><td class=\"FacetDataTD\">\n";
$gForm->selectNumber('vat_section', $form['vat_section'], false, 1, 9, 'FacetSelect', 'vat_section');
echo "\t</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['anno'] . "</td><td colspan=\"3\" class=\"FacetDataTD\">\n";
$gForm->selectNumber('anno', $form['anno'], false, date("Y")-5,date("Y")+5, 'FacetSelect', 'anno');
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";

$m = getMovements($form['vat_section'], $form['vat_reg'], $form['anno']);
echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
$err = 0;
if (sizeof($m) > 0) {
  echo "<tr>";
  $linkHeaders = new linkHeaders($script_transl['header']);
  $linkHeaders->output();
  echo "</tr>";
  $totimponi = 0.00;
  $totimpost = 0.00;
  $totindetr = 0.00;
  $totimponi_liq = 0.00;
  $totimpost_liq = 0.00;
  $totindetr_liq = 0.00;
  $ctrlmopre = 0;
  $castle_imponi=array();
  $castle_descri[0]='';
  $castle_percen[0]='';
  foreach ($m AS $key => $mv) {
    $class_m='';
    if ($mv['operat'] == 1||$form['vat_reg']==9) {
        $imponi = $mv['imponi'];
        $impost = $mv['impost'];
    } elseif ($mv['operat'] == 2) {
        $imponi = -$mv['imponi'];
        $impost = -$mv['impost'];
    } else {
        $imponi = 0;
        $impost = 0;
    }
    if ($mv['regiva'] == 4) {
        $mv['ragsoc'] = $mv['descri'];
        $mv['descri'] = '';
    }
    if (!isset($castle_impost_liq[$mv['codiva']])){
              $castle_imponi_liq[$mv['codiva']] = 0;
      $castle_impost_liq[$mv['codiva']] = 0;
    }
    $liq_val='';
    $red_p = '';
    if (isset($mv['err_p'])) {
        $red_p = 'red';
        $err++;
        echo "<tr>";
        echo "<td colspan=\"8\" class=\"FacetDataTDred\">" . $script_transl['errors']['P'] . ": era atteso ".$mv['err_p']."</td>";
        echo "</tr>";
    }
    $red_d = '';
    if (isset($mv['err_n'])) {
        $red_d = 'red';
        $err++;
        echo "<tr>";
        echo "<td colspan=\"8\" class=\"FacetDataTDred\">" . $script_transl['errors']['N'] . ": </td>";
        echo "</tr>";
    }
    $red_t = '';
    if (isset($mv['err_t'])) {
        $red_t = 'red';
        $err++;
        echo "<tr>";
        echo "<td colspan=\"8\" class=\"FacetDataTDred\">" . $script_transl['errors']['T'] . ": </td>";
        echo "</tr>";
    }
    echo '<tr class="'.$class_m.'">';
    echo "<td align=\"right\" class=\"FacetDataTD\">" . $mv['protoc'] . "  </td>";
    echo "<td align=\"center\"><a href=\"admin_movcon.php?id_tes=" . $mv['id_tes'] . "&Update\" title=\"Modifica il movimento contabile\">id " . $mv['id_tes'] . "</a><br />" . gaz_format_date($mv['datreg']). "</td>";
    echo "<td>" . $mv['descri'] . " n." . $mv['numdoc'] . $script_transl['of'] . gaz_format_date($mv['datdoc']) . "  </td>";
    echo "<td>" . substr($mv['ragsoc'].'', 0, 30) . " </td>";
    echo "<td align=\"right\">" . gaz_format_number($imponi) . "  </td>";
    echo "<td align=\"center\">" . $mv['periva'] . "  </td>";
    echo "<td align=\"right\">" . gaz_format_number($impost) . "  </td>";
    echo "<td align=\"center\">" . substr(gaz_format_date($mv['datliq']),3) . $liq_val."  </td>";
    echo "</tr>";
  }
}
echo "</table>\n";
if ($err>=1 && sizeof($m) > 0) {
  ?>
  <div class="bg-warning text-center"><input type="submit" class="btn btn-danger" name="renum" value="Sequenza errata sei sicuro e consideri che verranno rinumerati solo i movimenti contabili: PROCEDI" >
  </div>
  <?php
} elseif (sizeof($m) > 0 && !isset($_POST['renum'])) {
  ?>
  <div class="bg-success text-center"><input type="submit" class="btn btn-success" name="okseq" value="Ottimo lavoro, la sequenza è giusta, rinumerazione non necessaria!" >
  </div>
  <?php
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
