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
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if (!$isAjax) {
        $user_error = 'Access denied - not an AJAX request...';
        trigger_error($user_error, E_USER_ERROR);
    }
    require("../../library/include/datlib.inc.php");
    $config = new UserConfig;
    $admin_aziend=checkAdmin();
    if ($_POST['name']=='restore') {
  		gaz_dbi_query ("UPDATE ".$gTables['admin_config']." SET `var_value`='false' WHERE `var_name` LIKE 'LTE_%' AND adminid='".$_SESSION['user_name']."'");
    } else {
      // LTE_Collapsed e LTE_Onhover sono alternativi, lo stesso LTE_Boxed e LTE_Fixed
      $form['var_descri'] = substr($_POST['desc'],0,50);
      $form['var_name'] = substr($_POST['name'],0,30);
      $form['var_value'] = substr($_POST['val'],0,10);
      $form['adminid'] = $admin_aziend["user_name"];
      $config->setValue( $form['var_name'], $form );
      switch ($form['var_name']){
        case 'LTE_Collapsed':
          if ($form['var_value']=='true') {
            $form['var_name'] = 'LTE_Onhover';
            $form['var_value'] = 'false';
            $config->setValue( $form['var_name'], $form );
          }
        break;
        case 'LTE_Onhover':
          if ($form['var_value']=='true') {
            $form['var_name'] = 'LTE_Collapsed';
            $form['var_value'] = 'false';
            $config->setValue( $form['var_name'], $form );
          }
        break;
        case 'LTE_Boxed':
          if ($form['var_value']=='true') {
            $form['var_name'] = 'LTE_Fixed';
            $form['var_value'] = 'false';
            $config->setValue( $form['var_name'], $form );
          }
        break;
        case 'LTE_Fixed':
          if ($form['var_value']=='true') {
            $form['var_name'] = 'LTE_Boxed';
            $form['var_value'] = 'false';
            $config->setValue( $form['var_name'], $form );
          }
        break;
      }
    }
?>
