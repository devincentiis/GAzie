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
require("../../library/include/calsca.inc.php");
$rid_panel=false;

if (isset($_POST['hidden_req'])) { // accessi successivi
  $form['group_rid'] = intval($_POST['group_rid']);
  $form['bool_fixsca'] = intval($_POST['bool_fixsca']);
  $form['bool_fixemi'] = intval($_POST['bool_fixemi']);
  $form['date_fixsca'] = substr($_POST['date_fixsca'],0,10);
  $form['date_fixemi'] = substr($_POST['date_fixemi'],0,10);
  $form['hidden_req'] = htmlentities($_POST['hidden_req']);
  $form['ritorno'] = $_POST['ritorno'];
  $form['modamount'] = $_POST['modamount'];
  if (isset($_POST['submit']) && empty($msg)) {   //confermo la generazione
    $rs = getDocumentsBill(true,$form['group_rid'],json_decode($form['modamount'],true), $form);
    header("Location: report_effett.php");
    exit;
  }
} else { // primo accesso
  $form['hidden_req'] = '';
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['modamount'] = '';
  $form['group_rid'] = 2; // di default raggruppo in un solo RID tutto il cliente
  $form['bool_fixsca'] = 1; // di default propongo di emettere a scadenza fissa
  $form['bool_fixemi'] = 1;
  $date = date_create();
  date_modify($date,"first day of this month");
  $form['date_fixemi'] = date_format($date,"d/m/Y");
  date_add($date, date_interval_create_from_date_string("14 days"));
  $form['date_fixsca'] = date_format($date,"d/m/Y");
}

function getSaldo($gTables,$clfoco,$acc_id_con,$acc_toteff) {
  // questa funzione ritorna false se il saldo contabile coincide con il totale degli effetti da generare, altrimenti ritorna una matrice con il saldo contabile reale
  if (isset($acc_id_con[0]) && $acc_id_con[0]=='0') { // non è contabilizzata aggiungo essa stessa ed il saldo
    // ATTENZIONE! al momento se raggruppate e nessuna è contabilizzata coi potrebbero essere dei problemi
    $sqlquery= "SELECT SUM(import*(darave='D') - import*(darave='A')) AS saldo FROM ".$gTables['rigmoc']." WHERE codcon = ".$clfoco." GROUP BY codcon";
    $rs = gaz_dbi_query($sqlquery);
    $r = gaz_dbi_fetch_array($rs);
    return ( $r && $r['saldo'] && $r['saldo'] <> 0.00) ? round($r['saldo']+$acc_toteff,2) : false;
  } else { // è contabilizzata
    $impfat=0.00;
    $preparewhere='';
    foreach($acc_id_con as $k=>$v) {
      $rf=gaz_dbi_get_row($gTables['rigmoc'],'id_tes',$v," AND codcon = ".$clfoco." AND darave = 'D'");
      if ($rf){
        $impfat += $rf['import'];
        if ($preparewhere == '') { // il primo valore
          $preparewhere .=' WHERE id_tes <> '.$v;
        } else {
          $preparewhere .=' AND id_tes <> '.$v;
        }
      }
    }
    if ( !empty($preparewhere) ) {
      $sqlquery= "SELECT SUM(import*(darave='D') - import*(darave='A')) AS saldo FROM ".$gTables['rigmoc'].$preparewhere." AND codcon = ".$clfoco." GROUP BY codcon";
      $rs = gaz_dbi_query($sqlquery);
      $r = gaz_dbi_fetch_array($rs);
      return ( $r && $r['saldo'] && $r['saldo'] <> 0.00) ? round($r['saldo']+$impfat,2) : false;
    } else {
      return false;
    }
  }
}

function getDocumentsBill($upd = false,$group_rid=0,$modamount=[],$form=[]) {
    global $gTables, $admin_aziend;
    //$m1=microtime(true);
    $calc = new Compute;
    $from = $gTables['tesdoc'] . ' AS tesdoc
             LEFT JOIN ' . $gTables['pagame'] . ' AS pay
             ON tesdoc.pagame=pay.codice
             LEFT JOIN ' . $gTables['clfoco'] . ' AS customer
             ON tesdoc.clfoco=customer.codice
             LEFT JOIN ' . $gTables['anagra'] . ' AS anagraf
             ON anagraf.id=customer.id_anagra
             LEFT JOIN ' . $gTables['files'] . " AS files
             ON tesdoc.clfoco=( SELECT files.id_ref FROM ". $gTables['files'] . "  WHERE files.table_name_ref='clfoco' AND files.item_ref='mndtritdinf' ORDER BY files.id_doc DESC LIMIT 1 )";
    $where = "(tippag = 'B' OR tippag = 'T' OR tippag = 'V' OR tippag = 'I') AND geneff = '' AND tipdoc LIKE 'FA_'";
    $orderby = "datfat ASC, protoc ASC, id_tes ASC";
    $result = gaz_dbi_dyn_query('tesdoc.*, tesdoc.id_con AS id_movcon,
                        pay.tippag,pay.numrat,pay.tipdec,pay.giodec,pay.tiprat,pay.mesesc,pay.giosuc,customer.codice, customer.speban AS addebitospese, customer.iban,
                        CONCAT(anagraf.ragso1,\' \',anagraf.ragso2) AS ragsoc,CONCAT(anagraf.citspe,\' (\',anagraf.prospe,\')\') AS citta,
                        files.id_doc AS mndtritdinf', $from, $where, $orderby);
    $doc = array();
    $ctrlp = 0;
    while ($tes = gaz_dbi_fetch_array($result)) {
        //il numero di protocollo contiene anche l'anno nei primi 4 numeri
        $year_prot = intval(substr($tes['datfat'], 0, 4)) * 1000000 + $tes['protoc'];
        if ($year_prot <> $ctrlp) { // la prima testata della fattura
            if ($ctrlp > 0 && ($doc[$ctrlp]['tes']['stamp'] >= 0.01 || $taxstamp >= 0.01 )) { // non è il primo ciclo faccio il calcolo dei bolli del pagamento e lo aggiungo ai castelletti
                $calc->payment_taxstamp($calc->total_imp + $calc->total_vat + $carry - $rit + $taxstamp, $doc[$ctrlp]['tes']['stamp'], $doc[$ctrlp]['tes']['round_stamp'] * $doc[$ctrlp]['tes']['numrat']);
                $calc->add_value_to_VAT_castle($doc[$ctrlp]['vat'], $taxstamp + $calc->pay_taxstamp, $admin_aziend['taxstamp_vat']);
                $doc[$ctrlp]['vat'] = $calc->castle;
                // aggiungo il castelleto conti
                if (!isset($doc[$ctrlp]['acc'][$admin_aziend['boleff']])) {
                    $doc[$ctrlp]['acc'][$admin_aziend['boleff']]['import'] = 0;
                }
                $doc[$ctrlp]['acc'][$admin_aziend['boleff']]['import'] += $taxstamp + $calc->pay_taxstamp;
            }
            $carry = 0;
            $somma_spese = 0;
            $cast_vat = array();
            $totimp_decalc = 0.00;
            $n_vat_decalc = 0;
            $totimpdoc = 0;
            $spese_incasso = $tes['numrat'] * $tes['speban'];
            $cigcup = ''; /* aggiungo un solo valore di CIG CUP per ogni testata
             * che andrò a valorizzare solo se incontrerò questi sui righi tipo 11 e 12
             */
            $taxstamp = 0;
            $rit = 0;
        } else {
            $spese_incasso = 0;
        }
        // aggiungo il bollo sugli esenti/esclusi se nel DdT c'è ma non è ancora stato mai aggiunto
        if ($tes['taxstamp'] >= 0.01 && $taxstamp < 0.01) {
            $taxstamp = $tes['taxstamp'];
        }
        if ($tes['virtual_taxstamp'] == 0 || $tes['virtual_taxstamp'] == 3) { //  se è a carico dell'emittente non lo aggiungo al castelletto IVA
            $taxstamp = 0.00;
        }
        //recupero i dati righi per creare il castelletto
        $from = $gTables['rigdoc'] . ' AS rs
                    LEFT JOIN ' . $gTables['aliiva'] . ' AS vat
                    ON rs.codvat=vat.codice';
        $rs_rig = gaz_dbi_dyn_query('rs.*,vat.tipiva AS tipiva', $from, "rs.id_tes = " . $tes['id_tes'], "id_tes DESC");
        while ($r = gaz_dbi_fetch_array($rs_rig)) {
            if ($r['tiprig'] <= 1) {//ma solo se del tipo normale o forfait
                //calcolo importo rigo
                $importo = CalcolaImportoRigo($r['quanti'], $r['prelis'], array($r['sconto'], $tes['sconto']));
                if ($r['tiprig'] == 1) {
                    $importo = CalcolaImportoRigo(1, $r['prelis'], $tes['sconto']);
                }
                //creo il castelletto IVA
                if (!isset($cast_vat[$r['codvat']]['import'])) {
                    $cast_vat[$r['codvat']]['impcast'] = 0;
                    $cast_vat[$r['codvat']]['ivacast'] = round(($importo * $r['pervat']) / 100, 2);
                    $cast_vat[$r['codvat']]['import'] = 0;
                    $cast_vat[$r['codvat']]['periva'] = $r['pervat'];
                    $cast_vat[$r['codvat']]['tipiva'] = $r['tipiva'];
                }
                $cast_vat[$r['codvat']]['impcast']+=$importo;
                $cast_vat[$r['codvat']]['import']+=$importo;
                $totimpdoc += $importo;
                $rit+=round($importo * $r['ritenuta'] / 100, 2);
            } elseif ($r['tiprig'] == 3) {
                $carry += $r['prelis'];
            } elseif ($r['tiprig'] == 11) {
                $cigcup = 'CIG ' . $r['descri'];
            } elseif ($r['tiprig'] == 12) {
                $cigcup .= ' CUP ' . $r['descri'];
            }
        }
        $doc[$year_prot]['tes'] = $tes;
        $doc[$year_prot]['tes']['cigcup'] = $cigcup;
        $doc[$year_prot]['car'] = $carry;
        $doc[$year_prot]['rit'] = $rit;
        $ctrlp = $year_prot;
        $somma_spese += $tes['traspo'] + $spese_incasso + $tes['spevar'];
        $calc->add_value_to_VAT_castle($cast_vat, $somma_spese, $tes['expense_vat']);
        $doc[$ctrlp]['vat'] = $calc->castle;
        // quando confermo segno l'effetto come generato e se un RID valorizzo con l'ultimo mandato del cliente
        if ($upd) {
          gaz_dbi_query("UPDATE " . $gTables['tesdoc'] . " SET geneff = 'S' WHERE id_tes = " . $tes['id_tes'] . ";");
        }
    }
    if (count($doc)>0 && ($doc[$ctrlp]['tes']['stamp'] >= 0.01 || $taxstamp >= 0.01)) { // a chiusura dei cicli faccio il calcolo dei bolli del pagamento e lo aggiungo ai castelletti
        $calc->payment_taxstamp($calc->total_imp + $calc->total_vat + $carry - $rit + $taxstamp, $doc[$ctrlp]['tes']['stamp'], $doc[$ctrlp]['tes']['round_stamp'] * $doc[$ctrlp]['tes']['numrat']);
        // aggiungo al castelletto IVA
        $calc->add_value_to_VAT_castle($doc[$ctrlp]['vat'], $taxstamp + $calc->pay_taxstamp, $admin_aziend['taxstamp_vat']);
        $doc[$ctrlp]['vat'] = $calc->castle;
        // aggiungo il castelleto conti
        if (!isset($doc[$ctrlp]['acc'][$admin_aziend['boleff']])) {
            $doc[$ctrlp]['acc'][$admin_aziend['boleff']]['import'] = 0;
        }
        $doc[$ctrlp]['acc'][$admin_aziend['boleff']]['import'] += $doc[$ctrlp]['tes']['taxstamp'] + $calc->pay_taxstamp;
    }

    // INIZIO ciclo delle fatture che dovranno generare effetti con le singole scadenze
    $ctrl_date = '';
    $effacc=[];
    $cliscad=[];
    $raggr_acc=[]; // accumulatore di id_con e relativi importi
    $ke=0;
    foreach ($doc as $k => $v) {
      if ($ctrl_date <> substr($v['tes']['datemi'], 0, 4)) {
          $n = getReceiptNumber($v['tes']['datemi']);
      }
      // calcolo i totali
      $stamp = false;
      $round = 0;
      if ($v['tes']['tippag'] == 'T') {
          $stamp = $v['tes']['stamp'];
          $round = $v['tes']['numrat'] * $v['tes']['round_stamp'];
      }

      $tot = computeTot($v['vat'], $v['car'] - $v['rit']);
      //fine calcolo totali
      $rate = CalcolaScadenze($tot['tot'], substr($v['tes']['datfat'], 8, 2), substr($v['tes']['datfat'], 5, 2), substr($v['tes']['datfat'], 0, 4), $v['tes']['tipdec'], $v['tes']['giodec'], $v['tes']['numrat'], $v['tes']['tiprat'], $v['tes']['mesesc'], $v['tes']['giosuc']);
      $tot_doc = $tot['tot'];
      if ($tot['tot'] >= 0.01) {
        foreach ($rate['import'] as $k_r => $v_r) {
          $ke++;
          $v['tes']['tipeff'] = $v['tes']['tippag'];
          $v['tes']['scaden'] = $rate['anno'][$k_r] . '-' . $rate['mese'][$k_r] . '-' . $rate['giorno'][$k_r];
          $kgroup_scaden = $group_rid == 2 ? '' : $v['tes']['scaden'];
          $n_type = $v['tes']['tippag'];
          if ($n_type == 'B') {
              $n_type = 'R';
          }
          // valorizzo l'indice con una chiave clfoco-scaden (cliente-scadenza che eventualemente mi servirà sotto per accumulare in un unico RID le scadenze dello stesso cliente
          // per fare l'accumulo in fase di emissione della distinta RID darò lo stesso progressivo a righi diversi della tabella gaz_NNNeffett
          // controllo se il cliente ha già generato un rid con la stessa scadenza
          $v['tes']['status'] = '';
          if (isset($cliscad[$v['tes']['clfoco'].$kgroup_scaden]) && $v['tes']['tipeff']=='I' && $group_rid >=1) { // se ho chiesto il raggruppamento dei RID, non cambio il progressivo
            $v['tes']['progre'] = $cliscad[$v['tes']['clfoco'].$kgroup_scaden]['progre'];
            $effacc[$cliscad[$v['tes']['clfoco'].$kgroup_scaden]['key']]['raggru'] = 1; // segno come accumulato anche l'effetto precedente con lo stesso cliente-scadenza
            $effacc[$cliscad[$v['tes']['clfoco'].$kgroup_scaden]['key']]['status'] = 'RAGGR'; // segno RAGGR anche su effetto precedente, per visualizzare sui report, non incide sulla logica
            $v['tes']['raggru'] = 1;
            $v['tes']['status'] = 'RAGGR';
          } else { // altrimenti il progressivo sarà rivisto per l'accumulo
            $cliscad[$v['tes']['clfoco'].$kgroup_scaden] = ['progre'=>$n[$n_type],'key'=>$ke];
            $v['tes']['toteff'] = $v_r;
            $v['tes']['raggru'] = 0;
            $v['tes']['progre'] = $n[$n_type];
            $n[$n_type] ++;
          }
          $v['tes']['datemi'] = $v['tes']['datfat'];
          $tot_doc = round($tot_doc - $v_r, 2);
          $v['tes']['totfat'] = $tot['tot'];
          $v['tes']['salacc'] = 'C';
          if ($tot_doc == 0) {
              $v['tes']['salacc'] = 'S';
          }
          $v['tes']['impeff'] = $v_r;
          $v['tes']['id_doc'] = $v['tes']['id_tes'];
          $effacc[$ke]=$v['tes'];
        }
        // accumulo i totali fatture ed i relativi id_con per controllare i saldi contabili su una matrice indicizzata con progre
        if ($v['tes']['tipeff']=='I' && $group_rid >=1) {
          if (isset($raggr_acc[$v['tes']['progre']])) {
            $raggr_acc[$v['tes']['progre']]['acc_toteff']+=$tot['tot'];
          } else {
            $raggr_acc[$v['tes']['progre']]['acc_toteff']=$tot['tot'];
          }
          $raggr_acc[$v['tes']['progre']]['acc_last_scaden']=$v['tes']['scaden'];
          $raggr_acc[$v['tes']['progre']]['acc_id_con'][]=$v['tes']['id_movcon'];
          $raggr_acc[$v['tes']['progre']]['acc_id_tes'][]=$v['tes']['id_tes'];
        }
      }
      $ctrl_date = substr($v['tes']['datfat'], 0, 4);
    }

    // riciclo tutto per valorizzare con i totali dei raggruppati (per controllo del saldo contabile del cliente)
    $effetti=[];
    foreach ($effacc as $k=>$v) {
      if ($v['tipeff']=='I') {
        $v['acc_toteff']=$raggr_acc[$v['progre']]['acc_toteff'];
        $v['acc_last_scaden']=$raggr_acc[$v['progre']]['acc_last_scaden'];
        $v['acc_id_con']=$raggr_acc[$v['progre']]['acc_id_con'];
        $v['acc_id_tes']=$raggr_acc[$v['progre']]['acc_id_tes'];
      }
      $effetti[$k]=$v;

    }

    // FINE ciclo fatture con effetti con o senza progressivi raggruppati, pronti per essere visualizzati o inseriti sul db in base alla scelta

    //var_dump(microtime(true)-$m1);
    if ($upd) { // ho scelto di generare
      foreach ($effetti as $k=>$v) {
        // se scelte cambio le date emissione e scadenze dei RID
        if ($form['bool_fixemi']>=1 && $v['tipeff']=='I'){
          $v['datemi']= gaz_format_date($form['date_fixemi'],true);
        }
        if ($form['bool_fixsca']>=1 && $v['tipeff']=='I'){
          $v['scaden']= gaz_format_date($form['date_fixsca'],true);
        }
        // azzero id_con che altrimenti andrebbe ad essere valorizzato con quello della fattura
        $v['id_con']=0;
        if (isset($modamount[$k])) { // è stato modificato l'importo del RID
          $v['status'] .= 'MODIF'; // aggiungo tag MODIF per creare una descrizione diversa sul tracciato XML
          $v['impeff'] = floatval($modamount[$k]);
        }
        effettInsert($v);
      }
    } else {
      return $effetti;
    }
}

function getReceiptNumber($date) {
  global $gTables;
  $orderby = "datemi DESC, progre DESC";
  $where = "tipeff = 'B'";
  $result = gaz_dbi_dyn_query('*', $gTables['effett'], $where, $orderby, 0, 1);
  $lastB = gaz_dbi_fetch_array($result);
  $first['R'] = ($lastB)? (1 + $lastB['progre']):1;
  $where = "tipeff = 'T'";
  $result = gaz_dbi_dyn_query('*', $gTables['effett'], $where, $orderby, 0, 1);
  $lastT = gaz_dbi_fetch_array($result);
  $first['T'] = ($lastT)? (1 + $lastT['progre']):1;
  $where = "tipeff = 'V'";
  $result = gaz_dbi_dyn_query('*', $gTables['effett'], $where, $orderby, 0, 1);
  $lastV = gaz_dbi_fetch_array($result);
  $first['V'] = ($lastV)? (1 + $lastV['progre']):1;
  $where = "tipeff = 'I'";
  $result = gaz_dbi_dyn_query('*', $gTables['effett'], $where, $orderby, 0, 1);
  $lastI = gaz_dbi_fetch_array($result);
  $first['I'] = ($lastI)? (1 + $lastI['progre']):1;
  return $first;
}

function computeTot($data, $carry) {
    $tax = 0;
    $vat = 0;
    foreach ($data as $k => $v) {
        $tax += $v['impcast'];
        $vat += round($v['impcast'] * $v['periva']) / 100;
    }
    $tot = $vat + $tax + $carry;
    return array('taxable' => $tax, 'vat' => $vat, 'tot' => $tot);
}

require("../../library/include/header.php");
$script_transl = HeadMain(0,['calendarpopup/CalendarPopup']);
?>
<script>
$(function() {
  $("#date_fixemi").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
  $("#date_fixsca").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
	$("#dialog_modamount").dialog({ autoOpen: false });
	$('.dialog_modamount').click(function() {
		$("p#desfattura").html('fatt.' + $(this).attr("numfat") + ' del ' + $(this).attr("datfat") + ' scadenza ' + $(this).attr("scaden"));
		$("p#descliente").html($(this).attr("ragsoc"));
    var oriamount = $(this).attr("totfat");
    var conamount = $(this).attr("saldo");
    $("#oriamount").html("Originale € " + oriamount);
    $("#conamount").html("Contabile € " + conamount);
		var refdoc = $(this).attr('refdoc');
    var objmodamount = $.parseJSON($('#modamount').val());
    if(refdoc in objmodamount) {
      $("#newamount").val(objmodamount[refdoc]);
    } else {
      $("#newamount").val($(this).attr("saldo"));
    }
		$( "#dialog_modamount" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
   			close: {
					text:'Annulla e chiudi',
					'class':'btn btn-default',
          click:function() {
            $(this).dialog("destroy");
          }
        },
				confirm:{
					text:'Modifica',
					'class':'btn btn-warning',
					click:function (event, ui) {
            objmodamount[refdoc]=$("#newamount").val();
            $.each( objmodamount, function( key, value ) {
              if ( key == refdoc ) {
                if (value != oriamount) {
                  $('[refamo="'+key+'"]').html('€ ' + value + ' <del>' + oriamount + '</del>');
                  $('[refdoc="'+key+'"]').addClass('btn-danger').removeClass('btn-warning');
                } else {
                  delete objmodamount[key];
                  $('[refamo="'+key+'"]').html('€' + oriamount);
                  $('[refdoc="'+key+'"]').addClass('btn-warning').removeClass('btn-danger');
                }
              }
            });
            $('#modamount').val(JSON.stringify(objmodamount));
            //alert($('#modamount').val());
            $(this).dialog("destroy");
				}}
			}
		});
	$('#conamount').click(function() {
		$("#newamount").val(conamount);
  });
	$('#oriamount').click(function() {
		$("#newamount").val(oriamount);
  });
  $("#dialog_modamount" ).dialog( "open" );
	});
});
</script>
<?php
echo "<form method=\"POST\" name=\"create\">\n";
echo "<input type=\"hidden\" value=\"" . $form['hidden_req'] . "\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"" . $form['ritorno'] . "\" name=\"ritorno\" />\n";
$gForm = new GAzieForm();
?>
<div style="display:none" id="dialog_modamount" title="Cambio importo RID">
  <p>Cliente:</p>
  <p class="ui-state-highlight" id="descliente"></p>
  <p>Fattura:</p>
  <p class="ui-state-highlight" id="desfattura"></p>
  <div>
    <label>Importo attuale</label>
    <input type="text" value="" id="newamount" name="newamount" maxlength="12" />
  </div>
  <div class="row">
    <div class="btn btn-xs col-xs-12 col-sm-6 btn-default" id="oriamount" style="border: solid thin;" ></div>
    <div class="btn btn-xs col-xs-12 col-sm-6 btn-default" id="conamount" style="border: solid thin;"></div>
  </div>
</div>

<div class="FacetFormHeaderFont text-center bg-info"><?php echo $script_transl['title'];?></div>
<div class="panel panel-success gaz-table-form" id="rid_panel">
 <div class="container-fluid">
<div class="col-xs-12 text-center bg-success text-success text-bold"> Scelte per generazione dei RID
</div>
<div class="col-xs-12 text-center">
  <div class="form-group">
    <label for="group_rid" class="form-label col-sm-6 text-right"><?php echo $script_transl['group_rid'];?>: </label>
    <?php
      $gForm->variousSelect('group_rid', $script_transl['group_rid_value'], $form['group_rid'], "col-sm-6", true, '', false, 'style="max-width: 400px;"');
    ?>
  </div>
</div>
<div class="col-xs-12 text-center">
  <div class="form-group">
    <label for="bool_fixemi" class="form-label col-sm-6 text-right"><?php echo $script_transl['bool_fixemi'];?>: </label>
    <?php
      $gForm->variousSelect('bool_fixemi', $script_transl['bool_fixemi_value'], $form['bool_fixemi'], "col-sm-6", true, '', false, 'style="max-width: 400px;"');
    ?>
  </div>
</div>
<div class="col-xs-12 text-center">
  <div class="form-group">
    <label for="date_fixemi" class="form-label col-sm-6 text-right">Data emissione RID fissa <small> (solo se imposta sopra) </small> : </label>
<?php
  echo '<input id="date_fixemi" class="col-sm-6" name="date_fixemi" value="'.$form["date_fixemi"].'" maxlength=10 />';
?>
  </div>
</div>
<div class="col-xs-12 text-center">
  <div class="form-group">
    <label for="bool_fixsca" class="form-label col-sm-6 text-right"><?php echo $script_transl['bool_fixsca'];?>: </label>
    <?php
      $gForm->variousSelect('bool_fixsca', $script_transl['bool_fixsca_value'], $form['bool_fixsca'], "col-sm-6", true, '', false, 'style="max-width: 400px;"');
    ?>
  </div>
</div>
<div class="col-xs-12 text-center">
  <div class="form-group">
    <label for="date_fixsca" class="form-label col-sm-6 text-right">Date scadenza RID fissa <small> (solo se imposta sopra) </small> : </label>
<?php
  echo '<input id="date_fixsca" class="col-sm-6" name="date_fixsca" value="'.$form["date_fixsca"].'" maxlength=10 />';
?>
  </div>
</div>
  </div>
</div>


<div class="col-xs-12 text-center text-info bg-info">
  <input class="btn btn-info" type="submit" name="preview" id='preview' value="<?php echo $script_transl['view'];?>" />
</div>
<?php
//mostro l'anteprima
if (isset($_POST['preview'])) {
  $modamount=[];
    $errors=false;
    $rs = getDocumentsBill(false,$form['group_rid']);
    echo "<br /><div align=\"center\"><b>" . $script_transl['preview'] . "</b></div>";
    echo '<div class=" table-responsive"><table class="Tlarge table">';
    echo "<th>#</th>
         <th>" . $script_transl['date_reg'] . "</th>
         <th>" . $script_transl['protoc'] . "</th>
         <th>" . $script_transl['doc_type'] . "</th>
         <th>N.</th>
         <th>" . $script_transl['customer'] . "</th>
         <th>" . $script_transl['tot'] . " fattura</th>\n
         <th> Importo effetto</th>\n";
    $ctrl_date = '';
    $tot_type = ['B'=>0,'I'=>0,'T'=>0,'V'=>0];
    $class_type = ['B'=>'bg-success','I'=>'bg-info','T'=>'bg-warning','V'=>'bg-default'];
    $ctrldoc=0;
    $ctrlclfoco=0;
    foreach ($rs as $k => $v) {
      $tot_type[$v['tippag']]+=$v['impeff'];
      $e='';
      if ($ctrl_date <> substr($v['datfat'], 0, 4)) {
        $n = getReceiptNumber($v['datfat']);
      }
      if (strlen($v['iban'])<20&&$v['tippag']=='I') { // non c'è l'iban
        $errors=true;
        $e=$script_transl['errors']['noiban'];
      }
      if ($v['tippag']=='I') { // è un RID
        $rid_panel=true; // visualizzo il pannello scelte RID
        if ($v['mndtritdinf']<1) { // non c'è il mandato
          $errors=true;
          $e=$script_transl['errors']['nomandato'];
        }
      }
      if ($v['banapp']<1&&($v['tippag']=='B'||$v['tippag']=='T')) { // non c'è la banca d'appoggio
        $errors=true;
        $e=$script_transl['errors']['nobanapp'];
      }

      if ($ctrldoc<>$v['protoc']){
        $saldo=false;
        if ($v['tippag']=='I' && $form['group_rid']>=1) {
          // se è raggruppato controllo il saldo
          $saldo=getSaldo($gTables,$v['clfoco'],$v['acc_id_con'],$v['acc_toteff']);
          $v['scaden']=$v['acc_last_scaden'];
        }
        echo '<tr class="'.$class_type[$v['tippag']].'"><td>'.$k.'</td>
           <td align="center">' . gaz_format_date($v['datfat']) . "</td>
           <td title=\"".$v['cigcup']."\" align=\"center\">" . $v['protoc'] . '/' . $v['seziva'] . "</td>
           <td>" . $script_transl['doc_type_value'][$v['tipdoc']] . ' <a class="btn btn-xs btn-default" title="Visualizza in stile" href="./electronic_invoice.php?id_tes='.$v['id_tes'].'&viewxml" target="_blank"><i class="glyphicon glyphicon-eye-open"></i></a></td>
           <td>' . $v['numfat'] . '</td>
           <td><a class="btn btn-xs btn-'.((strlen($e)>10)?"danger":"success").'" target="_blank" href="../contab/select_partit.php?id='.$v['clfoco'].'" title="Anagrafica cliente">'.$v['ragsoc']. "</a></td>
           <td align=\"right\">" . $admin_aziend['symbol'].' '.gaz_format_number($v['totfat']) . '</td><td class="text-right">';
          if ($v['tipeff']=='I'){
            if ($saldo && abs($saldo) > 0.00 && end($v['acc_id_tes'])==$v['id_tes']){ // se più di uno solo sull'ultimo vado eventualmente a modificare il saldo
              $saldothis=$v['totfat'];
              if (end($v['acc_id_tes'])==$v['id_tes']) {
                $saldothis = $saldo - $v['acc_toteff'] + $v['impeff'];
              }
              echo '<small>Saldo contabile € '.$saldo.'</small> <div class="btn btn-danger btn-xs dialog_modamount" title="modifica importo RID" ragsoc="'.$v['ragsoc'].'" refdoc="'.$k.'" scaden="'.gaz_format_date($v['scaden']).'" numfat="'.$v['numfat'].'" datfat="'.gaz_format_date($v['datfat']).'" totfat="'.number_format($v['totfat'], 2, '.', '' ).'" saldo="'.$saldothis.'"><i class="glyphicon glyphicon-edit" ></i></div>';
            }
          }
          echo '</td></tr>';
      }
      echo "<tr><td></td>";
      echo '<td align="right" colspan="6"><span class="text-danger">'.$e.'</span> ';
      echo $script_transl['gen'] .'<b>' .$script_transl['type_value'][$v['tippag']] .
      ' n.' . $v['progre'] .(($v['raggru']>=1)?' <span class="text-danger">[raggruppato] </span>':'').'</b> ' . $script_transl['end'] . gaz_format_date($v['scaden']);
      echo '</td><td align="right" refamo="'.$k.'">€ ';
      if ($saldo && $saldo>=0.01 && end($v['acc_id_tes'])==$v['id_tes']) {
        echo $saldothis.' <del>'.gaz_format_number($v['impeff']).'</del>';
        // accumulo in modamount i nuovi importi trovati dal saldo cliente
        $modamount[$k]="".$saldothis;
      } else {
        echo gaz_format_number($v['impeff']);
      }
      echo "</td></tr>\n";
      $ctrldoc=$v['protoc'];
      $ctrl_date=substr($v['datfat'],0,4);
/*       if ($saldo && $v['raggru']>=1) {
        echo '<tr><td colspan=6>';
         var_dump($v['acc_id_con'],$v['acc_toteff'],$v['id_movcon']);
        echo '</td></tr>';
      }
*/
    }
  $form['modamount'] = json_encode($modamount);
  foreach ($tot_type as $k_t => $v_t) {
    if ($v_t > 0) {
      echo "\t<tr>\n";
      echo '<td class=\"FacetFieldCaptionTD\" colspan="6" align="right"><b>';
      echo $script_transl['total_value'][$k_t];
      echo "</b></td>
             <td class=\"FacetFieldCaptionTD\" align=\"right\"><b>";
      echo $admin_aziend['symbol'] . ' ' . gaz_format_number($v_t);
      echo "</b> </td>\n";
      echo "\t </tr>\n";
    }
  }
  echo "\t<tr>\n";
  echo "\t </tr></table></div>\n";
  echo '<div class="text-center">'.($errors?'<span class="gaz-costi">Attenzione!!! Puoi generare gli effetti ma devi essere consapevole degli errori sopra riportati</span><br/>':'').'<button type="submit" class="btn btn-warning" name="submit">'.$script_transl['submit'].'</button>';
  echo "\t </div>\n";
}
echo '<input type="hidden" value=\''.$form['modamount'].'\' name="modamount" id="modamount" />';
echo '<input type="hidden" value="'.$form['bool_fixemi'].'" name="bool_fixemi" id="bool_fixemi" />';
echo '<input type="hidden" value="'.$form['bool_fixsca'].'" name="bool_fixsca" id="bool_fixsca" />';
echo '<input type="hidden" value="'.$form['date_fixemi'].'" name="date_fixemi" id="date_fixemi" />';
echo '<input type="hidden" value="'.$form['date_fixsca'].'" name="date_fixsca" id="date_fixsca" />';
?>
</div>
</form>
<?php
if (!$rid_panel) {
  echo '<style>#rid_panel{display:none;}</style>';
}
require("../../library/include/footer.php");
?>
