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
$admin_aziend=checkAdmin();
$msg = ['err' => [], 'war' => []];


function getOrders($dateini,$datefin,$status=3)
  {
    global $gTables,$admin_aziend;
    $m = [];
    $sql="SELECT ".$gTables['tesbro'].".*, ".$gTables['anagra'].".ragso1 FROM ".$gTables['tesbro']."
          LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['tesbro'].".clfoco = ".$gTables['clfoco'].".codice
          LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id
          WHERE tipdoc LIKE 'VO_' AND datemi BETWEEN '".gaz_format_date($dateini,true)."' AND '".gaz_format_date($datefin,true)."' ORDER BY datemi DESC, numdoc DESC ";
    $rs = gaz_dbi_query ($sql);
    while ($r = gaz_dbi_fetch_array($rs)) { // attraverso ed attribuisco lo stato
      $r['stato_evasione'] = 0; // 0 = Inevaso, 1 = Evasione parziale, 2 = Evaso
      $r['unimis'] = '';
      $zerorow = false;
      $totimpbro_da_evadere = 0;
      $totimpdoc_evaso = 0;
      $remains_atleastone = false; // Almeno un rigo e' rimasto da evadere.
      $processed_atleastone = false; // Almeno un rigo e' gia' stato evaso.
      $rigbro_result = gaz_dbi_dyn_query('tiprig,quanti,prelis,sconto,unimis', $gTables['rigbro'], "id_tes = " . $r['id_tes'] . " AND tiprig <=1 ", 'id_tes DESC');
      $totquanti_da_evadere=0;
      while ( $rigbro_r = gaz_dbi_fetch_array($rigbro_result) ) {
        if ( $rigbro_r['tiprig']==1 ){
          $totquanti_da_evadere += 1;
          $totimp_da_evadere = CalcolaImportoRigo($rigbro_r['quanti'], $rigbro_r['prelis'], $rigbro_r['sconto']);
        } elseif ($rigbro_r['tiprig']==0) {
          $r['unimis'] = $rigbro_r['unimis'];
          $totquanti_da_evadere += $rigbro_r['quanti'];
          $totimp_da_evadere = CalcolaImportoRigo($rigbro_r['quanti'], $rigbro_r['prelis'], $rigbro_r['sconto']);
        } else {
          $totimp_da_evadere = 0;
        }
        if ($totimp_da_evadere < 0.01 ) {
          $zerorow = true;
        }
        $totimpbro_da_evadere += $totimp_da_evadere;
      }
      $r['zerorow'] = $zerorow;
      $r['totquanti_da_evadere'] = $totquanti_da_evadere;
      $r['totimpbro_da_evadere'] = $totimpbro_da_evadere;
      $totquanti_evaso = 0;
      $totimp_evaso = 0;
      $r['doc'] = [];
      $ctrl_id_tes = 0;
      $rigdoc_result = gaz_dbi_dyn_query('tiprig,quanti,prelis,'. $gTables['rigdoc'].'.sconto,'. $gTables['rigdoc'].'.id_tes,'. $gTables['tesdoc'].'.numdoc,'. $gTables['tesdoc'].'.datemi,'. $gTables['tesdoc'].'.tipdoc', $gTables['rigdoc'].' LEFT JOIN '.$gTables['tesdoc'].' ON  ('.$gTables['rigdoc'].'.id_tes = '.$gTables['tesdoc'].'.id_tes AND ('.$gTables['tesdoc'].".tipdoc LIKE 'FA_' OR ".$gTables['tesdoc'].".tipdoc LIKE 'DD_' OR ".$gTables['tesdoc'].".tipdoc LIKE 'V__')) ", "id_order=" . $r['id_tes'] . " AND tiprig <=1 ", $gTables['tesdoc'].'.datemi DESC');
      while ($rigdoc_r = gaz_dbi_fetch_array($rigdoc_result)) {
        if ($ctrl_id_tes <> $rigdoc_r['id_tes']){
          $r['doc'][$rigdoc_r['id_tes']] = ['numdoc'=>$rigdoc_r['numdoc'],'datemi'=>$rigdoc_r['datemi'],'tipdoc'=>$rigdoc_r['tipdoc']];
        }
        $totquanti_evaso += $rigdoc_r['quanti'];
        $processed_atleastone = true;
        if ( $rigdoc_r['tiprig']==1 ){
          $totimp_evaso = CalcolaImportoRigo($rigdoc_r['quanti'], $rigdoc_r['prelis'], $rigdoc_r['sconto']);
        } elseif ($rigdoc_r['tiprig']==0) {
          $totimp_evaso = CalcolaImportoRigo($rigdoc_r['quanti'], $rigdoc_r['prelis'], $rigdoc_r['sconto']);
        } else {
          $totimp_evaso = 0;
        }
        $totimpdoc_evaso += $totimp_evaso;
      }
      $r['totimpdoc_evaso'] = $totimpdoc_evaso;
      $r['totquanti_evaso'] = $totquanti_evaso;
      // indico gli stati in array
      if ( $totquanti_evaso < $totquanti_da_evadere ) {
        $remains_atleastone = true;
      }
      if ($totquanti_evaso >= $totquanti_da_evadere){
        $r['stato_evasione'] = 2; // 2 = Evaso
      } else if ($totquanti_evaso <= 0 ) {
        $r['stato_evasione'] = 0; // 1 = Inevaso
      } else if ($remains_atleastone) {
        $r['stato_evasione'] = 1; // 1 = Evasione parziale
      }
      // popolo la matrice in base allo stato scelto sulla select
      switch($status) { // 0 = Evasi, 1= Solo con residui, 2 = Inevasi, 3 = Inevasi e residui, 9 = Tutti
        case 0: // Evasi
          if ( $r['stato_evasione'] == 2 ){
            $m[] = $r;
          }
        break;
        case 1: // Solo con residui
          if ( $r['stato_evasione'] == 1 ){
            $m[] = $r;
          }
        break;
        case 2: // Inevasi
          if ( $r['stato_evasione'] == 0 ){
            $m[] = $r;
          }
        break;
        case 3: // Inevasi e residui
          if ( $r['stato_evasione'] <= 1  ) {
            $m[] = $r;
          }
        break;
        default: // Tutti
          $m[] = $r;
      }
    }
    return $m;
}

function getDateLimit($status)
  {
    global $gTables,$admin_aziend;
    $sql="SELECT MIN(".$gTables['tesbro'].".datemi) AS mindatemi FROM ".$gTables['rigbro']."
          LEFT JOIN ".$gTables['tesbro']." ON ".$gTables['rigbro'].".id_tes = ".$gTables['tesbro'].".id_tes
          LEFT JOIN ".$gTables['rigdoc']." ON ( ".$gTables['rigbro'].".id_tes = ".$gTables['rigdoc'].".id_order AND ".$gTables['rigbro'].".codart = ".$gTables['rigdoc'].".codart )
          WHERE ".$gTables['tesbro'].".tipdoc LIKE 'VO_' AND ".$gTables['rigdoc'].".id_order > 0";
    $rs = gaz_dbi_query ($sql);
    return gaz_dbi_fetch_array($rs);
}

$setDateLimit=false;
if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
  $form['hidden_req'] = '';
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $date = new DateTime('first day of this month');
  $date->modify('-3 months');
  $form['dateini'] = $date->format('d/m/Y');
  $form['datefin'] = date("d/m/Y");
  $form['status'] = 3; // Inevasi e con residuo
} else { // accessi successivi
  $form['hidden_req']=htmlentities($_POST['hidden_req']);
  $form['ritorno']=$_POST['ritorno'];
  $form['dateini']=substr($_POST['dateini'],0,10);
  $form['datefin']=substr($_POST['datefin'],0,10);
  $form['status'] = intval($_POST['status']);
  if ($form['hidden_req'] == 'status' && $form['status'] > 0 && $form['status'] < 9 ) {
    // solo se ho modificato la scelta degli status da visualizzare e non è Evasi o Tutti allora reimposto anche i limiti di date
    $mindatemi = getDateLimit($form['status']);
    $form['dateini'] = gaz_format_date($mindatemi['mindatemi'],false,true);
    $form['hidden_req'] = '';
  }
  if (isset($_POST['return'])) {
    header("Location: ".$form['ritorno']);
    exit;
  }
}

//controllo i campi
$utsini= mktime(0,0,0,substr($form['dateini'],3,2), substr($form['dateini'],0,2), substr($form['dateini'],6,4));
$utsfin= mktime(0,0,0,substr($form['datefin'],3,2), substr($form['datefin'],0,2), substr($form['datefin'],6,4));
if ($utsini > $utsfin) {
  $msg['err'][] = "wrongdate";
}
// fine controlli

if (isset($_POST['print']) && $msg=='') {
  $_SESSION['print_request']=array('script_name'=>'stampa_order_status','utsdateini'=>$utsini,'utsdatefin'=>$utsfin,'status'=>$form['status']);
  header("Location: sent_print.php");
  exit;
}
require("../../library/include/header.php");
$script_transl=HeadMain();
$gForm = new GazieForm();
if (count($msg['err']) >= 1) {
  echo '<div class="text-center">'.$gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err')."</div>";
}
?>
<script>
$(function () {
  $(".datepicker").datepicker({dateFormat: 'dd-mm-yy'});
  $("#dateini").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
  $("#dateini").change(function () {
    this.form.submit();
  });
  $("#datefin").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
  $("#datefin").change(function () {
    this.form.submit();
  });
});
</script>
<form method="POST" name="select">
  <input type="hidden" value="<?= $form['hidden_req'] ?>" name="hidden_req" />
  <input type="hidden" value="<?= $form['ritorno'] ?>" name="ritorno" />
<div class="FacetFormHeaderFont text-center bg-info"><?= $script_transl['title']; ?></div>
<div>
  <div class="form-group text-center">
    <label for="dateini" class="form-label"><?= $script_transl['dateini']; ?>: </label>
    <input type="text" class="" id="dateini" name="dateini" value="<?php echo $form['dateini']; ?>" maxlength="10" />
  </div>
</div>
<div>
  <div class="form-group text-center">
    <label for="datefin" class="form-label"><?php echo $script_transl['datefin']; ?>: </label>
    <input type="text" class="" id="datefin" name="datefin" value="<?php echo $form['datefin']; ?>" maxlength="10"/>
  </div>
</div>
<div>
  <div class="form-group text-center">
    <label for="status" class="form-label"><?php echo $script_transl['status']; ?>: </label>
    <?php $gForm->variousSelect('status', $script_transl['status_value'], $form['status'], '', 0, 'status'); ?>
  </div>
</div>
<?php
if ( count($msg['err']) == 0) {
  $m=getOrders($form['dateini'],$form['datefin'],$form['status']);
  if (sizeof($m) > 0) {
    $ctr_mv='';
    echo "<table class=\"Tlarge table table-striped table-responsive\"><tr>";
    $linkHeaders=new linkHeaders($script_transl['header']);
    $linkHeaders->output();
    echo "</tr>";
		foreach ($m AS $key => $mv) {
      // start prepare column
      $colqua = '';
      $colimp = '';
      $colsta = '';
      switch($mv['stato_evasione']) { // 0 = Inevaso, 1 = Evasione parziale, 2 = Evaso
        case 0: // 0 = Inevaso
          $colimp = '<a class="btn btn-xs btn-danger" href="select_evaord.php?id_tes='.$mv['id_tes'].'" title="Ordine inevaso per € '.gaz_format_number($mv['totimpbro_da_evadere']).'">Non evaso (€ '.gaz_format_number($mv['totimpbro_da_evadere']).') </a>';
          $colqor = $mv['unimis'].' '.floatval(round($mv['totquanti_da_evadere'],5));
          $colqev = $mv['unimis'].' '.floatval(round($mv['totquanti_evaso'],5));
          $colsta = $mv['stato_evasione'].( $mv['zerorow'] ? '<div class="btn btn-xs btn-danger"> X </div>' : '' );
        break;
        case 1: // 1 = Evasione parziale
          $colimp = '<a class="btn btn-xs btn-warning" href="select_evaord.php?id_tes='.$mv['id_tes'].'" title="Ordine parzialmente evaso € '.gaz_format_number($mv['totimpdoc_evaso']).' su '.gaz_format_number($mv['totimpbro_da_evadere']).'">Saldo da evadere (€ '.gaz_format_number($mv['totimpbro_da_evadere']).') </a>';
          $colqor = $mv['unimis'].' '.floatval(round($mv['totquanti_da_evadere'],5)).' - '.floatval(round($mv['totquanti_evaso'],5));
          $colqev = $mv['unimis'].' '.floatval(round($mv['totquanti_evaso'],5));
          $colsta = $mv['stato_evasione'].( $mv['zerorow'] ? '<div class="btn btn-xs btn-danger"> X </div>' : '' );
        break;
        case 2: // 2 = Evaso
          $colimp = '<a class="btn btn-xs btn-success" href="select_evaord.php?id_tes='.$mv['id_tes'].'" title="Ordine di € '.gaz_format_number($mv['totimpbro_da_evadere']).'">Evaso (€ '.gaz_format_number($mv['totimpdoc_evaso']).') </a>';
          $colqor = $mv['unimis'].' '.floatval(round($mv['totquanti_da_evadere'],5)).' - '.floatval(round($mv['totquanti_evaso'],5));
          $colqev = $mv['unimis'].' '.floatval(round($mv['totquanti_evaso'],5));
          $colsta = $mv['stato_evasione'].( $mv['zerorow'] ? '<div class="btn btn-xs btn-danger"> X </div>' : '' );
        break;
      }
      // end prepare column
      echo "<tr>";
      echo '<td class="FacetDataTD text-center"><a href="./admin_broven.php?id_tes='.$mv['id_tes'].'&Update" target="_blank">'.$mv['id_tes']."</a></td>";
      echo '<td class="FacetDataTD text-center">'.$mv['numdoc']."</td>";
      echo '<td class="FacetDataTD text-center">'.gaz_format_date($mv['datemi'])."</td>";
      echo '<td class="FacetDataTD text-center"><a href="./admin_client.php?codice='.substr($mv['clfoco'],-6).'&Update" target="_blank">'.$mv['ragso1'].'</a></td>';
      echo '<td class="FacetDataTD text-center small">'.$colqor."</td>";
      echo '<td class="FacetDataTD text-center small">'.$colqev."</td>";
      echo '<td class="FacetDataTD text-center small">'.$colimp."</td>";
      echo '<td class="FacetDataTD text-center">'.$colsta."</td>";
      echo "</tr>\n";
    }
    echo '<td class="FacetFooterTD text-center" colspan=7><input type="submit" class="btn btn-warning" name="print" value="';
    echo $script_transl['print'];
    echo '">';
    echo "\t </td>\n";
    echo "\t </tr>\n";
  }
  echo "</table>\n";
}
?>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
