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
// prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
if (isset($_POST['term']) && isset($_POST['opt'])) {
  $opt = substr($_POST['opt'],0,30);
  $fn = substr($_POST['fn'],0,60);
  switch ($opt) {
    case 'upload_signed':
      if(move_uploaded_file($_FILES['file']['tmp_name'], DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . $_FILES['file']['name'])){
        $id_tes = intval($_POST['term']); // valore id_tes che dovrà valorizzare id_tes_ref di gaz_001fae_flux
        $v=[];
        $v['id_tes_ref']=$id_tes;
        $v['flux_status']='PI'; // scrivo "PI" sulla colonna flux_status per indicare che adesso è possibile inviare la fattura alla PA
        $v['filename_ret'] = $_FILES['file']['name']; // uso la colonna filename_ret della tabella gaz_001fae_flux per ricordare il nome del file firmato conservato sul filesystem
        $v['exec_date']=date("Y-m-d");
        $v['received_date']=date("Y-m-d");
        $v['delivery_date']=date("Y-m-d");
        $fae_flux=gaz_dbi_get_row($gTables['fae_flux'],'id_tes_ref',$v['id_tes_ref']);
        if ($fae_flux) { // ho già un flusso registrato per questa fattura lo sovrascrivo
          gaz_dbi_table_update('fae_flux', array('id_tes_ref',$v['id_tes_ref']), $v);
        } else { // registro sul flusso che questa fattura è pronta per essere inviata
          $v['filename_ori'] = $fn;
          gaz_dbi_table_insert('fae_flux', $v);
        }
        echo $v['filename_ret'];
      } else {
        return false;
      }
    break;
    default:
    return false;
  }
} else {
  return false;
}

?>
