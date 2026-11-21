<?php
// prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}

require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();

header('Content-Type: application/json');

// Legge il payload JSON inviato da AJAX
$input = file_get_contents('php://input');
$form_data = json_decode($input, true);
if (!$form_data || !isset($form_data['elem'])) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Dati non validi']);
  exit;
}

try {
  if ($form_data['mode'] === 'm2') { // ordinare il menù 2: cambio solo  weight di menu_module
    $order = 5;
    foreach ($form_data['elem'] as $el) {
      gaz_dbi_put_row($gTables['menu_module'],'id',$el['id_menu_module'],'weight',$order);
      $order += 5;
    }
  } else if ($form_data['mode'] === 'm3') { // qui non so cosa è stato spostato quindi devo riprendere tutto il menù e controllare se e cosa è cambiato paragonandolo al contenuto del db
    $menu_module_data = getMenuData(intval($form_data['lang']))[$form_data['modname']]['db_menu'];
    $moved = [];
    $i=0;
    $prev_m2_empty = 0;
    $ctrl_id_menu_script = -1;
    foreach ($menu_module_data as $k => $v) {
      $prev_id_menu_module = 0;
      if ($v['id_menu_script'] == 0 && $v['id_menu_script'] == $ctrl_id_menu_script) { //  se ho due m2 consecutivi vuol dire che il precedente era vuoto e me lo appunto in $moved, servirà sotto in salita per attribuirglielo
        $prev_id_menu_module = $form_data['elem'][($k-1)]['id_menu_module'];
      }
      $ctrl_id_menu_script = $v['id_menu_script'];
      if ($form_data['elem'][$k]['id_menu_script'] != $v['id_menu_script'] ) { // riprendo solo la porzione della lista che è stata modificata
        $moved[$i] = ['db'=>['weight'=>$v['weight'],'id_menu_script'=>$v['id_menu_script'],'id_menu_module'=>$v['id_menu_module'],'prev_id_menu_module'=>$prev_id_menu_module ],'fr'=>$form_data['elem'][$k]];
        $i++;
      }
    }
    $primo=$moved[0];
    $ultim=$moved[($i-1)];
    $m2_mod=[];
    $updw = 0;
    if ( $primo['db']['id_menu_module'] != $primo['db']['prev_id_menu_module'] && $primo['db']['prev_id_menu_module'] > 0 ) {
      $m2_mod=[$primo['db']['prev_id_menu_module'],$ultim['db']['id_menu_module']];
    } else if ( $primo['db']['id_menu_module'] != $primo['fr']['id_menu_module'] || $ultim['db']['id_menu_module'] != $ultim['fr']['id_menu_module']) {
      $m2_mod=[$primo['db']['id_menu_module'],$ultim['db']['id_menu_module']];
    } else {
      $m2_mod=[$primo['db']['id_menu_module']];
    }
    if ( $primo['db']['id_menu_script'] == $ultim['fr']['id_menu_script'] && $primo['db']['id_menu_script'] > 0 ) { // discesa, ovvero il primo elemento sul db me lo ritrovo sull'ultimo del form
      $new_weight = $ultim['db']['weight']+1;
      // error_log( 'new_weight:'.$new_weight." discesa\n");
      $updw = -1;
    } else if ( $ultim['db']['id_menu_script'] == $primo['fr']['id_menu_script'] && $ultim['db']['id_menu_script'] > 0 ) { // salita, ovvero il primo elemento sul form è quello che stava
      $new_weight = $primo['db']['weight']-1;
      // error_log ('new_weight:'.$new_weight." salita\n");
      $updw = 1;
    } else {
      // error_log( "Errore: non può non essere stato spostato nulla! \n");
    }

    // foreach($moved as $dbv){
    //  error_log (json_encode($dbv)."\n");
    // }
    error_log (json_encode($m2_mod)."\n");
    // da questo momento in poi ho i dati per poter effettuare le modifiche alla/e tabella/e
    // gli intermedi tra il primo e l'ultimo dovranno fare solo degli shift tramite la colonna weight di menu_script, senza cambiare mai il riferimento a menu_module
    // il primo o l'ultimo dovrà subire una modifica a secondo che sia stato fatto un spostamento all'interno dello stesso id_menu_module oppure in un altro
    // se è una SALITA: prendo il primo elemento del form e gli do il weight del 1° elemento del db -1, se diverso gli cambio l'id_menu_module con quello del form,
    // alla fine riciclo comunque il menu_module dela destinazione per distanziare i weight di 5, distanza necessaria qualora l'aggiornamento di versione voglia inserire delle voci intermedie
    // se è una DISCESA prendo l'ultimo elemento del form e gli do il weight dell'ultimo elemento del db +1, se diverso gli cambio l'id_menu_module con quello del form,
    // alla fine riciclo comunque il menu_module dela destinazione per gli stessi motivi della salita.
    // quindi che si sia cambiato id_menu_module o no il/gli id_menu_module interessato/i devono essere ciclati per distanziare i weight di 5
    if (count($m2_mod) == 1 ){ // è uno spostamento nell'ambito dello stesso m2, cambio solo il weight dell'elemento spostato e riciclo per distanziare di 5
      if ( $updw == 1 ) { // salita
        gaz_dbi_put_row($gTables['menu_script'],'id',$primo['fr']['id_menu_script'],'weight',($primo['db']['weight']-1));
      } else if ( $updw == -1 ){ // DISCESA prendo l'ultimo elemento del form e gli do il weight dell'ultimo elemento del db +1
        gaz_dbi_put_row($gTables['menu_script'],'id',$ultim['fr']['id_menu_script'],'weight',($ultim['db']['weight']+1));
      }
      // riordino l'unioc m2 distanziando gli script di 5
      $rsm3 = gaz_dbi_dyn_query("*", $gTables['menu_script'],'id_menu = '.$m2_mod[0],'weight ASC');
      $order = 5;
      while ($m3 = gaz_dbi_fetch_assoc($rsm3)) {
        gaz_dbi_put_row($gTables['menu_script'],'id',$m3['id'],'weight',$order);
        $order += 5;
      }
    } else if (count($m2_mod) == 2) { // spostamento tra due m2 diversi
      if ( $updw == 1 ) { // SALITA devo controllare se sto spostando su un m2 vuoto, in questo caso dovrò cambiargli id_menu_module con quello contenuto dentro $primo['db']['prev_id_menu_module'] che è valorizzato > 0 e sostituire anche il valore dentro $m2_mod
        if ( $primo['db']['prev_id_menu_module'] > 0 ) {
          $primo['db']['id_menu_module'] = $primo['db']['prev_id_menu_module']; // cambio con quello reale
          $primo['db']['weight'] = 6; // diventerà 5, il primo
        }
        gaz_dbi_put_row($gTables['menu_script'],'id',$primo['fr']['id_menu_script'],'weight',($primo['db']['weight']-1));
        gaz_dbi_put_row($gTables['menu_script'],'id',$primo['fr']['id_menu_script'],'id_menu',($primo['db']['id_menu_module']));
      } else if ( $updw == -1 ){ // DISCESA prendo l'ultimo elemento del form e gli do il weight dell'ultimo elemento del db +1
        gaz_dbi_put_row($gTables['menu_script'],'id',$ultim['fr']['id_menu_script'],'weight',($ultim['db']['weight']+1));
        gaz_dbi_put_row($gTables['menu_script'],'id',$ultim['fr']['id_menu_script'],'id_menu',($ultim['db']['id_menu_module']));
      }
      foreach($m2_mod as $m2id ) { // riordino entrambi gli m2 distanziandoli di 5
        $rsm3 = gaz_dbi_dyn_query("*", $gTables['menu_script'],'id_menu = '.$m2id,'weight ASC');
        $order = 5;
        while ($m3 = gaz_dbi_fetch_assoc($rsm3)) {
          gaz_dbi_put_row($gTables['menu_script'],'id',$m3['id'],'weight',$order);
          $order += 5;
        }
      }
    }
    //error_log(json_encode($form_data['elem']));
    foreach ($form_data['elem'] as $el) {
    }
  }
  echo json_encode(['status' => 'success', 'data' => $form_data]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
