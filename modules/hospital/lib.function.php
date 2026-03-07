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
class hospitalForm extends GAzieForm {
  function selectAsl($gTables,$name,$val,$ret_type=false,$class='') {
      $query = 'SELECT id_asl,denominazione,name,codice FROM ' . $gTables['asl'] . ' LEFT JOIN ' . $gTables['regions'] . ' ON ' . $gTables['asl'] . '.regione =' . $gTables['regions'] . '.id WHERE 1 ORDER BY regione';
      $acc = '<select id="'.$name.'" name="'.$name.'" class="'.$class.'" >';
      $acc .= '<option value="0"';
      $acc .= intval($val)==0?' selected ':' ';
      $acc .= ' -------- ';
      $acc .= '</option>';
      $rs = gaz_dbi_query($query);
      $otherbed=false;
      while ($r = gaz_dbi_fetch_array($rs)) {
        $otherbed=true;
        $selected = '';
        if ($r['id_asl'] == intval($val)) {
          $selected = "selected";
        }
        $acc .= '<option value="'.$r['id_asl'] . '" '.$selected.' >'.$r['name'].' '.$r['denominazione'].'  '.$r['codice'];
        $acc .= '</option>';
      }
      $acc .='</select>';
      if ($ret_type){
        return $acc;
      } else {
        echo $acc;
      }
  }
  function selectBed($name,$val,$ret_type=false,$class='',$refresh=false) {
    global $gTables;
    $query = 'SELECT id_bed,bedname,roomname,wardname FROM ' . $gTables['bed'].' LEFT JOIN '.$gTables['room'].' ON '.$gTables['bed'].'.id_room = '.$gTables['room'].'.id_room LEFT JOIN '.$gTables['ward'].' ON '.$gTables['room'].'.id_ward = '.$gTables['ward'].'.id_ward WHERE 1 ORDER BY '.$gTables['room'].'.id_ward, '.$gTables['bed'].'.id_room, id_bed';
    $acc = '<select id="'.$name.'" name="'.$name.'" class="'.$class.'" '.($refresh?'onchange="this.form.submit();"':'').' >';
    $acc .= '<option value="0"';
    $acc .= intval($val)==0?' selected ':' ';
    $acc .= '> -----------';
    $acc .= '</option>';
    $rs = gaz_dbi_query($query);
    while ($r = gaz_dbi_fetch_array($rs)) {
      $selected = '';
      if ($r['id_bed'] == intval($val)) {
        $selected = "selected";
      }
      $acc .= '<option value="'.$r['id_bed'] . '" '.$selected.'>'.$r['bedname'].' stanza: '.$r['roomname'].' reparto: '.$r['wardname'];
      $acc .= '</option>';
    }
    $acc .='</select>';
    if ($ret_type){
      return $acc;
    } else {
      echo $acc;
    }
  }

  function selectRoom($name,$val,$ret_type=false,$class='',$refresh=false) {
    global $gTables;
    $query = 'SELECT id_room,roomname,wardname FROM ' . $gTables['room'].' LEFT JOIN '.$gTables['ward'].' ON '.$gTables['room'].'.id_ward = '.$gTables['ward'].'.id_ward WHERE 1 ORDER BY '.$gTables['room'].'.id_ward,id_room';
    $acc = '<select id="'.$name.'" name="'.$name.'" class="'.$class.'" '.($refresh?'onchange="this.form.submit();"':'').' >';
    $acc .= '<option value="0"';
    $acc .= intval($val)==0?' selected ':' ';
    $acc .= '> - - - - - - - - -';
    $acc .= '</option>';
    $rs = gaz_dbi_query($query);
    while ($r = gaz_dbi_fetch_array($rs)) {
      $selected = '';
      if ($r['id_room'] == intval($val)) {
        $selected = "selected";
      }
      $acc .= '<option value="'.$r['id_room'] . '" '.$selected.'> '.$r['roomname'].' Rep:'.$r['wardname'];
      $acc .= '</option>';
    }
    $acc .='</select>';
    if ($ret_type){
      return $acc;
    } else {
      echo $acc;
    }
  }
  function selectWard($name,$val,$ret_type=false,$class='',$refresh=false) {
    global $gTables;
    $query = 'SELECT id_ward,wardname FROM ' . $gTables['ward'].' WHERE 1 ORDER BY id_ward';
    $acc = '<select id="'.$name.'" name="'.$name.'" class="'.$class.'" '.($refresh?'onchange="this.form.submit();"':'').' >';
    $acc .= '<option value="0"';
    $acc .= intval($val)==0?' selected ':' ';
    $acc .= '> - - - - - - - - -';
    $acc .= '</option>';
    $rs = gaz_dbi_query($query);
    while ($r = gaz_dbi_fetch_array($rs)) {
      $selected = '';
      if ($r['id_ward'] == intval($val)) {
        $selected = "selected";
      }
      $acc .= '<option value="'.$r['id_ward'] . '" '.$selected.'> '.$r['wardname'];
      $acc .= '</option>';
    }
    $acc .='</select>';
    if ($ret_type){
      return $acc;
    } else {
      echo $acc;
    }
  }

  function encryptDoc($content)
  {
    global $gTables;
    $ivlong=gaz_dbi_get_row($gTables['config'], 'variable', 'cookie_secret_key')['cvalue'];
    $cipher = 'aes-256-cbc';
    $ivLenght = openssl_cipher_iv_length($cipher);
    $iv = substr($ivlong,0,$ivLenght);
    return openssl_encrypt($content, $cipher,hash('sha256',$_SESSION['aes_key']),0,$iv);
  }

  function decryptDoc($content)
  {
    global $gTables;
    $ivlong=gaz_dbi_get_row($gTables['config'], 'variable', 'cookie_secret_key')['cvalue'];
    $cipher = 'aes-256-cbc';
    $ivLenght = openssl_cipher_iv_length($cipher);
    $iv = substr($ivlong,0,$ivLenght);
    return openssl_decrypt($content, $cipher,hash('sha256',$_SESSION['aes_key']),0,$iv);
  }
}
?>
