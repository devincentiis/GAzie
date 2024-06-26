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
require("../vendit/lib.function.php");
$lm = new lotmag;
$admin_aziend = checkAdmin();
$gForm = new magazzForm;
$msg = '';
$tot_val_giac = 0;
if (!isset($_POST['ritorno'])) { //al primo accesso allo script
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['date_Y'] = date("Y");
    $form['date_M'] = date("m");
    $form['date_D'] = date("d");
    $rs_first = gaz_dbi_dyn_query("codice", $gTables['catmer'], 1, "codice ASC", 0, 1);
    $cm_first = gaz_dbi_fetch_array($rs_first);
    $form['catmer'] = $cm_first['codice'];
    $utsdate = mktime(0, 0, 0, $form['date_M'], $form['date_D'], $form['date_Y']);
    $date = date("Y-m-d", $utsdate);
    if (!empty($form['catmer'])) {
      $result = gaz_dbi_dyn_query($gTables['artico'] . '.*, ' . $gTables['catmer'] . '.descri AS descat,' . $gTables['catmer'] . '.annota AS anncat', $gTables['artico'] . ' LEFT JOIN ' . $gTables['catmer'] . ' ON catmer = ' . $gTables['catmer'] . '.codice', "catmer = " . $form["catmer"] ." AND (" . $gTables['artico'] . '.good_or_service = 0 OR '. $gTables['artico'] . '.good_or_service = 2 ) AND ' . $gTables['artico'] . '.id_assets = 0', 'catmer ASC, ' . $gTables['artico'] . '.codice ASC');
    } else {
      $result = gaz_dbi_dyn_query($gTables['artico'] . '.*, ' . $gTables['catmer'] . '.descri AS descat,' . $gTables['catmer'] . '.annota AS anncat', $gTables['artico'] . ' LEFT JOIN ' . $gTables['catmer'] . ' ON catmer = ' . $gTables['catmer'] . '.codice', $gTables['artico'] . '.good_or_service = 0 OR '. $gTables['artico'] . '.good_or_service = 2', 'catmer ASC, ' . $gTables['artico'] . '.codice ASC');
    }
    if ($result) {
      // Imposto totale valore giacenza by DF
      while ($r = gaz_dbi_fetch_array($result)) {
        $mv = $gForm->getStockValue(false, addslashes($r['codice']), $date, null, $admin_aziend['decimal_price']);
        $magval = array_pop($mv);
        $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0,'v'=>0]:$magval;
        if (isset($magval['q_g']) && round($magval['q_g'],6) == "-0"){ // Antonio Germani - se si crea erroneamente un numero esponenziale negativo forzo la quantità a zero
					$magval['q_g']=0;
				}
        $form['a'][$r['codice']]['i_d'] = $r['descri'];
        $form['a'][$r['codice']]['i_l'] = $r['lot_or_serial'];
        $form['a'][$r['codice']]['i_u'] = $r['unimis'];
        $form['a'][$r['codice']]['v_a'] = $magval['v'];
        $form['a'][$r['codice']]['v_r'] = $magval['v'];
        $form['a'][$r['codice']]['i_a'] = $r['annota'];
        $form['a'][$r['codice']]['i_g'] = $r['catmer'];
        $form['a'][$r['codice']]['g_d'] = $r['descat'];
        $form['a'][$r['codice']]['g_a'] = $magval['q_g'];
        $form['a'][$r['codice']]['g_r'] = $magval['q_g'];
        $form['a'][$r['codice']]['v_g'] = $magval['v_g'];
        $form['a'][$r['codice']]['lotRestPost']=[];
        $form['a'][$r['codice']]['class'] = 'default';
        $form['vac_on' . $r['codice']] = '';
        if ($magval['q_g'] < 0) { // giacenza inferiore a 0
          $form['chk_on' . $r['codice']] = ' checked ';
          $form['a'][$r['codice']]['class'] = 'danger';
        } elseif ($magval['q_g'] > 0) { //
          $form['chk_on' . $r['codice']] = ' checked ';
          if ($magval['q_g']<=$r['scorta']){
            $form['a'][$r['codice']]['class'] = 'warning';
          }
        } elseif ( !isset($magval['q']) && ( $r['movimentabile'] == '' || $r['movimentabile'] == 'S' ) ) { // è un articolo mai movimentato e non posto fuori magazzino, potrei essere al primo inventario
          $form['chk_on' . $r['codice']] = ' checked ';
          $form['a'][$r['codice']]['class'] = 'info';
          $form['a'][$r['codice']]['v_r'] = $r['preacq']; // propongo il prezzo d'acquisto in anagrafica articolo
        } else { // giacenza = 0
          $form['chk_on' . $r['codice']] = '';
          $form['a'][$r['codice']]['class'] = 'danger';
        }
        $tot_val_giac+=$magval['v_g'];
      }
    }
} else { //nelle  successive entrate
    if (isset($_POST['Return'])) {
      header("Location: " . $_POST['ritorno']);
      exit;
    }
    $form['date_Y'] = intval($_POST['date_Y']);
    $form['date_M'] = intval($_POST['date_M']);
    $form['date_D'] = intval($_POST['date_D']);
    $form['catmer'] = intval($_POST['catmer']);
    if ($_POST['hidden_req'] == 'catmer' || $_POST['hidden_req'] == 'date') {
      $utsdate = mktime(0, 0, 0, $form['date_M'], $form['date_D'], $form['date_Y']);
      $date = date("Y-m-d", $utsdate);
      $where = "catmer = " . $form["catmer"];
      if ($form['catmer'] == 100) {
          $where = 1;
      }
      // visualizzo solo gli articoli escludendo i servizi
      $sac=gaz_dbi_get_row($gTables['company_config'], 'var', 'show_artico_composit');
      if (!$sac || $sac['val']==0 || $sac['val']=='') {
        $where .= " AND good_or_service != 1";
      } else {
        $where .= " AND good_or_service = 0"; // ... e articoli composti se previsto in configurazione avanzata azienda
      }
      $where .= " AND id_assets = 0"; // solo se non è un bene ammortizzabile
      $ctrl_cm = 0;
      $result = gaz_dbi_dyn_query($gTables['artico'] . '.*, ' . $gTables['catmer'] . '.descri AS descat,' . $gTables['catmer'] . '.annota AS anncat', $gTables['artico'] . ' LEFT JOIN ' . $gTables['catmer'] . ' ON catmer = ' . $gTables['catmer'] . '.codice', $where, 'catmer ASC, ' . $gTables['artico'] . '.codice ASC');
      if ($result) {
        // Imposto totale valore giacenza by DF
        while ($r = gaz_dbi_fetch_array($result)) {
          if ($r['catmer'] <> $ctrl_cm) {
              gaz_set_time_limit(30);
              $ctrl_cm = $r['catmer'];
          }
          $mv = $gForm->getStockValue(false, addslashes($r['codice']), $date, null, $admin_aziend['decimal_price']);
          $magval = array_pop($mv);
          $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0,'v'=>0]:$magval;
          if (isset($magval['q_g']) && round($magval['q_g'],6) == "-0"){ // Antonio Germani - se si crea erroneamente un numero esponenziale negativo forzo la quantità a zero
            $magval['q_g']=0;
          }
          $form['a'][$r['codice']]['i_d'] = $r['descri'];
          $form['a'][$r['codice']]['i_l'] = $r['lot_or_serial'];
          $form['a'][$r['codice']]['i_u'] = $r['unimis'];
          $form['a'][$r['codice']]['v_a'] = $magval['v'];
          $form['a'][$r['codice']]['v_r'] = $magval['v'];
          $form['a'][$r['codice']]['i_a'] = $r['annota'];
          $form['a'][$r['codice']]['i_g'] = $r['catmer'];
          $form['a'][$r['codice']]['g_d'] = $r['descat'];
          $form['a'][$r['codice']]['g_r'] = $magval['q_g'];
          $form['a'][$r['codice']]['g_a'] = $magval['q_g'];
          $form['a'][$r['codice']]['v_g'] = $magval['v_g'];
          $form['a'][$r['codice']]['lotRestPost']=[];
          $form['vac_on' . $r['codice']] = '';
          if ($magval['q_g'] < 0) { // giacenza inferiore a 0
              $form['chk_on' . $r['codice']] = ' checked ';
              $form['a'][$r['codice']]['class'] = 'danger';
          } elseif ($magval['q_g'] > 0) { //
            $form['chk_on' . $r['codice']] = ' checked ';
            if ($magval['q_g']<=$r['scorta']){
              $form['a'][$r['codice']]['class'] = 'warning';
            } else {
              $form['a'][$r['codice']]['class'] = 'default';
            }
          } else { // giacenza = 0
            $form['chk_on' . $r['codice']] = '';
            $form['a'][$r['codice']]['class'] = 'danger';
          }
          $tot_val_giac+=$magval['v_g'];
        }

      }
    } elseif (isset($_POST['preview']) || isset($_POST['insert'])|| $_POST['hidden_req'] == 'refr') {  //in caso di conferma
      $cau99 = gaz_dbi_get_row($gTables['caumag'], 'codice', 99);
      $cau98 = gaz_dbi_get_row($gTables['caumag'], 'codice', 98);
      $form['date_Y'] = $_POST['date_Y'];
      $form['date_M'] = $_POST['date_M'];
      $form['date_D'] = $_POST['date_D'];
      $form['catmer'] = $_POST['catmer'];
      $utsdate = mktime(0, 0, 0, $form['date_M'], $form['date_D'], $form['date_Y']);
      $date = date("Y-m-d", $utsdate);
      foreach ($_POST as $k => $v) { //controllo sui dati inseriti e flaggati
        if ($k == 'a') {
          foreach ($v as $ka => $va) { // ciclo delle singole righe (a)
            $form['chk_on' . $ka] = '';
            $postcodart=preg_replace("/[^a-zA-Z0-9-_]/",'_',$ka); // il post del codice articolo sostituisce i caratteri speciali con underscore, esempio punti o spazi
            if (isset($_POST['chk' .$postcodart])) { // se l'articolo e' da inventariare lo controllo
              $form['chk_on' . $ka] = ' checked ';
              if ($va['g_r'] < 0) {
                $msg .= $ka . '-0+';
              } elseif ($va['g_r'] == 0 && $va['g_a'] == 0) { //inutile fare l'inventario di una cosa che non c'era e non c'e'
                $msg .= $ka . '-2+';
              }
              if ($va['v_r'] <= 0) {
                $msg .= $ka . '-1+';
              }
            }
            // Antonio Germani - controllo che non sia già stato fatto l'inventario nello stesso giorno per lo stesso articolo (altrimenti non funziona bene getStockValue con articoli con lotti)
            $checkinv="NULL";
            $checkinv = gaz_dbi_get_row($gTables['movmag'], "artico", $ka, " AND caumag = '99' AND datdoc = '$date'");
            if ($checkinv) {
              $msg .= $ka . '-3+';
            }
            $form['vac_on' . $ka] = '';
            if (isset($_POST['vac' . $ka])) $form['vac_on' . $ka] = ' checked ';
            $form['a'][$ka]['i_d'] = substr($va['i_d'], 0, 30);
            $form['a'][$ka]['i_l'] = $va['i_l'];
            $form['a'][$ka]['i_u'] = substr($va['i_u'], 0, 3);
            $form['a'][$ka]['v_a'] = gaz_format_quantity($va['v_a'], 0, $admin_aziend['decimal_price']);
            $form['a'][$ka]['v_r'] = gaz_format_quantity($va['v_r'], 0, $admin_aziend['decimal_price']);
            $form['a'][$ka]['i_a'] = $va['i_a'];
            $form['a'][$ka]['i_g'] = $va['i_g'];
            $form['a'][$ka]['g_d'] = $va['g_d'];
            $form['a'][$ka]['g_r'] = $va['g_r'];
            $form['a'][$ka]['g_a'] = gaz_format_quantity($va['g_a'], 0, $admin_aziend['decimal_quantity']);
            $form['a'][$ka]['v_g'] = gaz_format_quantity($va['v_g'], 0, $admin_aziend['decimal_price']);
            $form['a'][$ka]['class'] = $va['class'];
            if ($va['i_l']>=1){  // se è un articolo con lotti o numero seriale riprendo gli eventuali post delle rimanenze dei singoli lotti
                $lm->getAvailableLots($ka,0,$form['date_Y'] . '-' . $form['date_M'] . '-' . $form['date_D']);
                $lotrests = $lm->available;
                foreach($lotrests as $k=>$v){
                    if (isset($_POST['lotRestPost' .$v['id_lotmag']])){
                      $lotquanti=floatval($_POST['lotRestPost' .$v['id_lotmag']]);
                      $lotquanti=($va['i_l']>1&&$lotquanti>1)?1:$lotquanti; // i seriali al massimo 1
                      $form['a'][$ka]['lotRestPost'][$v['id_lotmag']]['g_r']=$lotquanti;
                      $form['a'][$ka]['lotRestPost'][$v['id_lotmag']]['g_a']=$v['rest'];
                      $form['a'][$ka]['lotRestPost'][$v['id_lotmag']]['ide']=$v['identifier'];
                    }
                }
            }
            // Calcolo totale valore giacenza
            $tot_val_giac += $form['a'][$ka]['v_g'];
          }
        }
      }
      if (isset($_POST['insert']) && empty($msg)) { // se devo inserire e non ho errori rifaccio il ciclo dei righi per inserire i movimenti
        foreach ($form['a'] as $k => $v) { // ciclo delle singole righe (a)
          if ($form['chk_on' . $k] == ' checked ') {   // e' un rigo da movimentare
            if (isset($v['lotRestPost'])&&count($v['lotRestPost'])>=1) { // ci sono lotti stornati
              foreach( $v['lotRestPost'] as $kl => $vl ) {
                if ($vl['g_a'] > $vl['g_r']) { // giacenza reale minore -scarico
                  // devo fare prima uno storno per scaricare
                  $mq = $vl['g_a'] - $vl['g_r'];
                  movmagInsert(array('caumag' => 97,
                    'operat' => -1,
                    'datreg' => $form['date_Y'] . '-' . $form['date_M'] . '-' . $form['date_D'],
                    'tipdoc' => 'INV',
                    'desdoc' => $cau98['descri']. ' lotto: '.$vl['ide'],
                    'datdoc' => $form['date_Y'] . '-' . $form['date_M'] . '-' . $form['date_D'],
                    'artico' => $k,
                    'quanti' => $mq,
                    'prezzo' => $v['v_r'],
                    'id_lotmag'=>$kl));
                } elseif ($vl['g_a'] < $vl['g_r']) { // giacenza reale maggiore carico
                  // devo fare prima uno storno per caricare
                  $mq = $vl['g_r'] - $vl['g_a'];
                  movmagInsert(array('caumag' => 97,
                    'operat' => 1,
                    'datreg' => $form['date_Y'] . '-' . $form['date_M'] . '-' . $form['date_D'],
                    'tipdoc' => 'INV',
                    'desdoc' => $cau98['descri']. ' lotto: '.$vl['ide'],
                    'datdoc' => $form['date_Y'] . '-' . $form['date_M'] . '-' . $form['date_D'],
                    'artico' => $k,
                    'quanti' => $mq,
                    'prezzo' => $v['v_r'],
                    'id_lotmag'=>$kl));
                }
              }
                } elseif ($v['g_a'] > $v['g_r']) { // in caso di giacenza reale minore
                  // devo fare prima uno storno per scaricare
                  $mq = $v['g_a'] - $v['g_r'];
                  movmagInsert(array('caumag' => 98,
                    'operat' => -1,
                    'datreg' => $form['date_Y'] . '-' . $form['date_M'] . '-' . $form['date_D'],
                    'tipdoc' => 'INV',
                    'desdoc' => $cau98['descri'],
                    'datdoc' => $form['date_Y'] . '-' . $form['date_M'] . '-' . $form['date_D'],
                    'artico' => $k,
                    'quanti' => $mq,
                    'prezzo' => $v['v_r']));
                } elseif ($v['g_a'] < $v['g_r']) { // se maggiore carico
                  // devo fare prima uno storno per caricare
                  $mq = $v['g_r'] - $v['g_a'];
                  movmagInsert(array('caumag' => 98,
                    'operat' => 1,
                    'datreg' => $form['date_Y'] . '-' . $form['date_M'] . '-' . $form['date_D'],
                    'tipdoc' => 'INV',
                    'desdoc' => $cau98['descri'],
                    'datdoc' => $form['date_Y'] . '-' . $form['date_M'] . '-' . $form['date_D'],
                    'artico' => $k,
                    'quanti' => $mq,
                    'prezzo' => $v['v_r']));
                }
                movmagInsert(array('caumag' => 99,
                  'operat' => 1,
                  'datreg' => $form['date_Y'] . '-' . $form['date_M'] . '-' . $form['date_D'],
                  'tipdoc' => 'INV',
                  'desdoc' => $cau99['descri'],
                  'datdoc' => $form['date_Y'] . '-' . $form['date_M'] . '-' . $form['date_D'],
                  'artico' => $k,
                  'quanti' => $v['g_r'],
                  'prezzo' => $v['v_r']));
              }
          }
          header("Location: report_movmag.php");
          exit;
      }
    }
}
require("../../library/include/header.php");
require("./lang." . $admin_aziend['lang'] . ".php");
$script_transl = $strScript["inventory_stock.php"] + HeadMain();
?>
<script>
$(function () {
    // ENRICO FEDELE, .live è stato eliminato a partire dalla jquery 1.7, adesso si deve usare .on
    // la vecchia funzione dunque non andava più, ho scritto questa
    // devo essere noesto, non mi piace granchè, ma funziona.
    // IMPORTANTE: sarebbe opportuno rimuovere questo codice da qui e farlo confluire in gz-library.js
    $('.checkAll').on('click', function () {
        var goTo = false;
        if ($(this).hasClass('all')) {
            var goTo = true;
            $(this).toggleClass('none all');
            $(this).children('i').toggleClass('glyphicon-check glyphicon-unchecked');
        } else {
            $(this).toggleClass('all none');
            $(this).children('i').toggleClass('glyphicon-unchecked glyphicon-check');
        }
        changeCheckboxes(allCheckboxes, goTo);
    });

    $('.invertSelection').on('click', function () {
        changeCheckboxes(allCheckboxes);
        $('.checkAll').removeClass('all none');
        $('.checkAll').addClass('all');
        $('.checkAll').children('i').removeClass('glyphicon-check');
        $('.checkAll').children('i').addClass('glyphicon-unchecked');
    });
    function changeCheckboxes(list, value) {
        for (var i = list.length - 1; i >= 0; i--) {
            list[i].checked = (typeof value === 'boolean') ? value : !list[i].checked;
        }
    }
    //var inputs = document.getElementsByTagName('input');
    var inputs = document.getElementsByClassName('jq_chk');
    var allCheckboxes = [];
    for (var j = inputs.length - 1; j >= 0; j--) {
        if (inputs[j].type === 'checkbox') {
            allCheckboxes.push(inputs[j]);
        }
    }

    // SOTTO: attraverso una chiamata ajax sul database apre e propone sul dialog i valori da attribuire ad ogni singolo lotto dell'articolo, darò la possibilità all'utente di modificarli per singolo lotto. All'uscita, se confermo valorizzerò tanti elementi <input > quanti sono i lotti modificati, alla conferma del form padre questi <input> genereranno movimenti contabili di storno con causale 98 in base alla differenza con il valore risultante dai movimenti che lo precedono, la registrazione sul database dovrà avvenire con id_mov che precede quello di inventario causale 99 altrimenti, siccome i due movimenti sono in pari data, salterebbe tutta la logica. Quindi prima storno (98) per singoli lotti e poi inventario tutto l'articolo con causale 99  SEMPRE!
    $("#inputLotmagRest").dialog({ autoOpen: false });
    $('.inputLotmagRest').click(function() {
        $("span#lot_codart").html($(this).attr("codart"));
        var codart = $(this).attr('codart');
        var datref = $("#lot_datref").attr('datref');
        var lotserial=1;
        var maxls='';
        $.ajax({
            data: {'codart': codart,'datref':datref},
			dataType: 'json',
            type: 'POST',
            url: './get_lots.php', // qui chiamo lo script php per recuperare i lotti dell'articolo e le singole rimanenze
            success: function(output){
				var totReal = 0.00;
                var existLot=false;
				$.each(output, function (key, value) {
                    if(Number(value.ls)==2){
                        lotserial=2;
                        maxls='max=1';
                    }
                    existLot=true;
					totReal += parseFloat(value.rest);
					if ($('#lotRestPost'+value.id_lotmag).length === 0) { // input inesistente, propongo il resto che ho sul db
					} else { // input esistente, propongo il valore in esso contenuto sul form del dialog
                        value.rest=$('#lotRestPost'+value.id_lotmag).val();
					}
					$('#content_lots').append('<div class="row col-xs-12 bg-info"><div class="col-xs-6">'+value.identifier+' giacenza <b>' + parseFloat(value.rest)+'</b></div><div class="col-xs-3 text-right"> reale = </div><input type="number" class="col-xs-3" min=0 '+maxls+' id="lotRestDial'+value.id_lotmag+'" name="'+value.id_lotmag+'" maxlength="11" onchange="lotRestCalc('+lotserial+','+value.id_lotmag+');" onkeyup="lotRestCalc('+lotserial+','+value.id_lotmag+');" value="' + parseFloat(value.rest)+'" /></div>');
				});
                if (existLot){
                    $('#content_lots').append('<div class="row col-xs-12"><div class="col-xs-9 text-right">Totale reale : </div><div><input class="bg-warning col-xs-3 text-center" id="totReal" type="numeric" value="' + parseFloat(totReal) +'" disbled/></div></div>');
                    lotRestCalc(lotserial,0);
                }
            }
        });
        $( "#inputLotmagRest" ).dialog({
            minHeight: 1,
            minWidth: 450,
            modal: "true",
            show: "blind",
            hide: "explode",
            buttons: {
                "Annulla": function() {
					$('#content_lots').html(''); //svuoto il contenuto del form provvisorio sul dialog
                    $(this).dialog("destroy");
                },
                confirm:{
                    text:'Conferma',
                    'class':'btn btn-danger',
                    click:function (event, ui) {
                        $("[id='lotContent"+codart+"']").html('');
						// prima di chiudere dovrò appendere gli elementi input sul form padre per fare il post dei valori settati con il dialog (lato browser) e non perderli in conferma e/o preview
                        var lotsRests='';
                        var totReal = 0.00;
                        $('[id*="lotRestDial"]').each((i, v)=> {
                            lotsRests += '<p class="bg-warning">ID ' + v.name + ' reale:'+ v.value + '</p><input type="hidden" value="' + v.value + '" id="lotRestPost'+v.name+'" name="lotRestPost'+v.name+'">';
                            totReal += Number(v.value)<0?0:Number(v.value);
                        });
                        $("[id='totReal"+codart+"']").html(totReal);
                        $("[id='lotContent"+codart+"']").append(lotsRests);
						if ($('#is_preview').length === 0) { // non c'è una anteprima
                            $('#content_lots').html(''); //svuoto il contenuto del form provvisorio sul dialog
                            $(this).dialog("destroy");
                        } else {
                            $('#btnpreview').click();
                        }
					}
                }
            },
            close:function(event, ui){
				if ($('#is_preview').length === 0) { // non c'è una anteprima
                    $('#content_lots').html(''); //svuoto il contenuto del form provvisorio sul dialog
                    $(this).dialog("destroy");
                } else {
                    $('#btnpreview').click();
                }
            }
        });
        $("#inputLotmagRest" ).dialog( "open" );
    });
	$('.artnormal').change(function(){ // cambio il valore ad
		//		var ffd = $("#is_preview").val(); alert(ffd);
		if ($('#is_preview').length === 0) { // non c'è una anteprima
			$("[name='hidden_req']").val('refr');
			$("form[name='maschera']").submit();
		} else {
            $('#btnpreview').click();
        }
    });

});

function lotRestCalc(ls,id_lotmag) {
	var totReal = 0.00;
	$('[id*="lotRestDial"]').each((i, v) => {
        var lv = Number(v.value);
        if (ls>1&&lv>1){
            lv=1;
            $('#lotRestDial'+id_lotmag).val('1');
        }
        if (lv<0){
            lv=0;
            $('#lotRestDial'+id_lotmag).val('0');
        }
        totReal += lv;
	});
	$('#totReal').val(totReal);
	$('#totReal').addClass('bg-danger').removeClass('bg-warning');
}
</script>
<form method="POST" name="maschera">
	<input type="hidden" name="hidden_req" value="" />
	<input type="hidden" name="ritorno" value="<?php echo $_POST['ritorno']; ?>" />
  <div align="center" class="FacetFormHeaderFont">
<?php
echo ucfirst($script_transl['title']) . ' ' . $script_transl['del'];
$gForm->Calendar('date', $form['date_D'], $form['date_M'], $form['date_Y'], 'FacetSelect', 'date');
echo $script_transl['catmer'];
$gForm->selectFromDB('catmer', 'catmer', 'codice', $form['catmer'], false, false, '-', 'descri', 'catmer', 'FacetSelect', array('value' => 100, 'descri' => '*** ' . $script_transl['all'] . ' ***'));
?>
  </div>
  <div class="table-responsive">
  <table class="Tlarge table table-striped table-bordered table-condensed">
<?php
if (!empty($msg)) {
?>
    <tr>
			<td colspan="9" class="FacetDataTDred"><?php echo $gForm->outputErrors($msg, $script_transl['errors']); ?></td>
		</tr>
<?php
}
?>
    <thead>
      <tr>
        <th class="FacetFieldCaptionTD"><a href="export_inventory_stock.php" class="btn btn-default btn-xs">Esporta</a></th>
        <th class="FacetFieldCaptionTD"><?php echo $script_transl['code']; ?></th>
        <th class="FacetFieldCaptionTD"><?php echo $script_transl['descri']; ?></th>
        <th class="FacetFieldCaptionTD"><?php echo $script_transl['mu']; ?></th>
        <th class="FacetFieldCaptionTD"><?php echo $script_transl['v_a']; ?></th>
        <th class="FacetFieldCaptionTD" align="right"><?php echo $script_transl['v_r']; ?></th>
        <th class="FacetFieldCaptionTD"><?php echo $script_transl['g_a']; ?></th>
        <th class="FacetFieldCaptionTD" align="right"><?php echo $script_transl['g_r']; ?></th>
        <th class="FacetFieldCaptionTD"><?php echo $script_transl['g_v']; ?></th>
      </tr>
    </thead>
    <tbody>
<?php
$ctrl_cm = 0;
if (isset($form['a'])) {
  $elem_n = 0;
  foreach ($form['a'] as $k => $v) {
    //ini default value
    $tooltip = ' class="gazie-tooltip" data-type="product-thumb" data-id="' . $k . '" data-title="' . $v['i_a'] . '"';
    // end default value
    if ($ctrl_cm <> $v['i_g']) {
      $cm_tooltip = ' class="gazie-tooltip" data-type="catmer-thumb" data-id="' . $v['i_g'] . '" data-title="' . $v['g_d'] . '"';
?>
      <tr>
        <td><input type="hidden" value="<?php echo $v['g_d']; ?>" name="a[<?php echo $k; ?>][g_d]" />
<?php
      if ($ctrl_cm == 0) {
?>
        <a href="javascript:void(0);" class="checkAll all btn btn-default btn-xs" title="<?php echo $script_transl['selall']; ?>"><i class="glyphicon glyphicon-unchecked"></i></a>
				<a href="javascript:void(0);" class="invertSelection btn btn-default btn-xs" title="<?php echo $script_transl['invsel']; ?>"><i class="glyphicon glyphicon-refresh"></i></a>
<?php
      }
?>
        </td>
        <td class="FacetFieldCaptionTD" colspan="8" align="left"><span <?php echo $cm_tooltip; ?>><?php echo $v['i_g'] . ' - ' . $v['g_d'] ?></span>
        </td>
			</tr>
<?php
    }
?>
      <tr class="<?php echo $v['class']; ?>">
        <td class="FacetFieldCaptionTD" align="center">
          <input type="hidden" value="<?php echo $v['i_a']; ?>" name="a[<?php echo $k; ?>][i_a]" />
          <input type="hidden" value="<?php echo $v['class']; ?>" name="a[<?php echo $k; ?>][class]" />
          <input type="hidden" value="<?php echo $v['i_g']; ?>" name="a[<?php echo $k; ?>][i_g]" />
          <input type="hidden" value="<?php echo $v['g_d']; ?>" name="a[<?php echo $k; ?>][g_d]" />
          <input type="hidden" value="<?php echo $v['i_d']; ?>" name="a[<?php echo $k; ?>][i_d]" />
          <input type="hidden" value="<?php echo $v['i_l']; ?>" name="a[<?php echo $k; ?>][i_l]" />
          <input type="hidden" value="<?php echo $v['i_u']; ?>" name="a[<?php echo $k; ?>][i_u]" />
          <input type="hidden" value="<?php echo $v['v_a']; ?>" name="a[<?php echo $k; ?>][v_a]" />
          <input type="hidden" value="<?php echo $v['v_r']; ?>" name="a[<?php echo $k; ?>][v_r]" />
          <input type="hidden" value="<?php echo $v['g_a']; ?>" name="a[<?php echo $k; ?>][g_a]" />
          <input type="hidden" value="<?php echo $v['v_g']; ?>" name="a[<?php echo $k; ?>][v_g]" />
          <input class="jq_chk" name="chk<?php echo $k; ?>" <?php echo $form['chk_on' . $k]; ?>  type="checkbox" />
		</td>
    <td align="left"><span <?php echo $tooltip; ?>><a class="btn btn-default btn-xs" href="./admin_artico.php?codice=<?php echo $k; ?>" target="_blank"><?php echo $k; ?></a></span></td>
<?php
echo '<td align="left"><span ' . $tooltip . '>' . $v['i_d'] . '</span></td>
      <td align="center">' . $v['i_u'] . '</td>
      <td align="right">' . gaz_format_quantity($v['v_a'], 0, $admin_aziend['decimal_price']) . '</td>
      <td align="right">
        <input id="vac' . $k . '" name="vac' . $k . '" ' . $form['vac_on' . $k] . ' onClick="toggle(\'vac' . $k . '\', \'a[' . $k . '][v_r]\')" type="checkbox" />
        <input type="text" style="text-align:right" onchange="document.maschera.chk' . $k . '.checked=true" id="a[' . $k . '][v_r]" name="a[' . $k . '][v_r]" value="' . gaz_format_quantity($v['v_r'], 0, $admin_aziend['decimal_price']) . '" disabled="disabled" /></td>
      <td class="FacetFieldCaptionTD" align="right">' . gaz_format_quantity($v['g_a'], 0, $admin_aziend['decimal_quantity']) . '</td>
      <td align="right">';
		if ($v['i_l']>=1 && $v['g_r']>0){ // se articolo con lotti ...
			echo '<div id="lotContent'.$k.'" class="col-xs-6">';
        if (isset($v['lotRestPost']) && count($v['lotRestPost'])>=1){
          $totReal=0.00;
          $classTot='bg-danger';
        } else {
          $v['lotRestPost']=[];
          $totReal=$v['g_r'];
          $classTot='bg-default';
        };
        foreach( $v['lotRestPost'] as $kl => $vl ) {
            $totReal += $vl['g_r'];
            echo '<p class="bg-warning">ID '. $kl . ' reale:'.$vl['g_r'].'</p><input type="hidden" value="'. $vl['g_r']. '" id="lotRestPost'.$kl.'" name="lotRestPost'.$kl.'">';
        }
        $form['a'][$k]['g_r'] = $totReal;
        echo '</div><button type="button" class="btn btn-default" style="padding: 0px 0px 0px 5px;"  title="Articolo con lotti: modifica per singoli lotti"><a class="inputLotmagRest" codart="'.$k.'"><div style="text-align:right; padding: 3px; cursor:pointer; border:1px;"><i class="glyphicon glyphicon-tag"></i><span class="'.$classTot.'" id="totReal'.$k.'">
			' . $totReal . '</span></div></a></button>
			<input type="hidden" name="a[' . $k . '][g_r]" value="' . $totReal . '"/>';
		} else {
			echo '<input type="text" style="text-align:right" onchange="document.maschera.chk' . $k . '.checked=true" class="artnormal" name="a[' . $k . '][g_r]" value="' . $v['g_r'] . '">';
		}
		echo '
		</td>
		<td  align="right">' . gaz_format_number($v['v_g']) . '</td>
		</tr>';
      $ctrl_cm = $v['i_g'];
      $elem_n++;
  }
  echo '		<tr>
  				<td colspan="2" class="FacetFieldCaptionTD">
				</td>
				<td align="center" colspan="6" class="FacetFieldCaptionTD">
					<input type="submit" class="btn btn-warning" name="preview" id="btnpreview" value="' . $script_transl['view'].'" />
				</td>
				<td align="right" class="bg-primary">Totale ' . gaz_format_number($tot_val_giac) . '</td>
			</tr>';
  if (isset($_POST['preview']) && empty($msg)) { // e' possibile confermare, non i sono errori formali
      echo '	</table></div><div class="table-responsive">
	 			<table class="Tmiddle table-striped">
				<tr>
	 					<td colspan="8" class="FacetFormHeaderFont"><span class="text-danger">' . $script_transl['preview_title'] . '</span></td>
				</tr>
				<tr>
	 					<td class="FacetFieldCaptionTD">Causale</td>
					<td class="FacetFieldCaptionTD">' . $script_transl['code'] . '</td>
					<td class="FacetFieldCaptionTD">' . $script_transl['descri'] . '</td>
					<td class="FacetFieldCaptionTD">' . $script_transl['mu'] . '</td>
					<td class="FacetFieldCaptionTD" align="right">' . $script_transl['load'] . '</td>
					<td class="FacetFieldCaptionTD" align="right">' . $script_transl['unload'] . '</td>
					<td class="FacetFieldCaptionTD" align="right">' . $script_transl['v_r'] . '</td>
					<td class="FacetFieldCaptionTD">' . $script_transl['value'] . '</td>
				</tr>';
      foreach ($form['a'] as $k => $v) { // ciclo delle singole righe (a)
          if ($form['chk_on' . $k] == ' checked ') {   // e' un rigo da movimentare
              $load = '';
              $unload = '';
              if (isset($v['lotRestPost'])&&count($v['lotRestPost'])>=1) { // ci sono lotti stornati
                  foreach( $v['lotRestPost'] as $kl => $vl ) {
                      if ($vl['g_a'] > $vl['g_r']) { // senza lotti giacenza reale minore -scarico
                          // devo fare prima uno storno per scaricare
                          $mq = $vl['g_a'] - $vl['g_r'];
                          echo '		<tr>
		 				<td>98-' . $cau98['descri'] . ' lotto: '.$vl['ide'].'</td>
						<td align="left">' . $k . '</td>
						<td align="left">' . $v['i_d'] . '</td>
						<td align="left">' . $v['i_u'] . '</td>
						<td></td>
						<td align="right">' . gaz_format_quantity($mq, 0, $admin_aziend['decimal_quantity']) . '</td>
						<td align="right">' . $v['v_r'] . '</td>
						<td align="right">' . gaz_format_number($v['v_r'] * $mq) . '</td>
                          </tr>';
                      } elseif ($vl['g_a'] < $vl['g_r']) { // senza lotti giacenza reale maggiore carico
                          // devo fare prima uno storno per caricare
                          $mq = $vl['g_r'] - $vl['g_a'];
                          echo '		<tr>
		 				<td>98-' . $cau98['descri'] . ' lotto: '.$vl['ide'].'</td>
						<td align="left">' . $k . '</td>
						<td align="left">' . $v['i_d'] . '</td>
						<td align="left">' . $v['i_u'] . '</td>
						<td align="right">' . gaz_format_quantity($mq, 0, $admin_aziend['decimal_quantity']) . '</td>
						<td></td>
						<td align="right">' . $v['v_r'] . '</td>
						<td align="right">' . gaz_format_number($v['v_r'] * $mq) . '</td>
                          </tr>';
                      }
                  }
              } elseif ($v['g_a'] > $v['g_r']) { // senza lotti giacenza reale minore -scarico
                  // devo fare prima uno storno per scaricare
                  $mq = floatval($v['g_a']) - floatval($v['g_r']);
                  echo '		<tr>
		 				<td>98-' . $cau98['descri'] . '</td>
						<td align="left">' . $k . '</td>
						<td align="left">' . $v['i_d'] . '</td>
						<td align="left">' . $v['i_u'] . '</td>
						<td></td>
						<td align="right">' . gaz_format_quantity($mq, 0, $admin_aziend['decimal_quantity']) . '</td>
						<td align="right">' . $v['v_r'] . '</td>
						<td align="right">' . gaz_format_number($v['v_r'] * $mq) . '</td>
					</tr>';
              } elseif ($v['g_a'] < $v['g_r']) { // senza lotti giacenza reale maggiore carico
                  // devo fare prima uno storno per caricare
                  $mq = floatval($v['g_r']) - floatval($v['g_a']);
                  echo '		<tr>
		 				<td>98-' . $cau98['descri'] . '</td>
						<td align="left">' . $k . '</td>
						<td align="left">' . $v['i_d'] . '</td>
						<td align="left">' . $v['i_u'] . '</td>
						<td align="right">' . gaz_format_quantity($mq, 0, $admin_aziend['decimal_quantity']) . '</td>
						<td></td>
						<td align="right">' . $v['v_r'] . '</td>
						<td align="right">' . gaz_format_number($v['v_r'] * $mq) . '</td>
					</tr>';
              }
              echo '		<tr>
						<td>99-' . $cau99['descri'] . '</td>
						<td align="left">' . $k . '</td>
						<td align="left">' . $v['i_d'] . '</td>
						<td align="left">' . $v['i_u'] . '</td>
						<td align="right">' . $v['g_r'] . '</td>
						<td></td>
						<td align="right">' . $v['v_r'] . '</td>
						<td align="right">' . gaz_format_number(floatval($v['v_r']) * floatval($v['g_r'])) . '</td>
					</tr>';
          }
      }
      echo '		<tr>
	 					<td align="right" colspan="8" class="text-center">
						<input class="btn btn-warning" type="submit" name="insert" id="is_preview" value="' . $script_transl['submit'] . '">
					</td>
				</tr>';
  }
} else {
  echo '		<tr>
  				<td colspan="9" class="FacetDataTDred">' . $script_transl['noitem'] . '</td>
			</tr>';
}
?>
</tbody>
</table></div>
<div style="display: none;" id="inputLotmagRest" title="Giacenza singoli lotti al <?php echo $form['date_D'].'-'.$form['date_M'].'-'.$form['date_Y']; ?>">
    <span id="lot_datref" datref="<?php echo $form['date_Y'].'-'.$form['date_M'].'-'.$form['date_D']; ?>" ></span>
    <p><b>Articolo: </b><span class="ui-state-highlight" id="lot_codart"></span> Lotti:</p>
    <div id="content_lots">
    </div>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
