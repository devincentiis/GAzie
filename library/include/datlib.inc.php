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

$path_root = $_SERVER['DOCUMENT_ROOT'];
require( "../../config/config/gconfig.php" );
require( "../../library/include/" . $NomeDB . ".lib.php" );
require( "../../library/include/function.inc.php"  );

if ($debug_active) {
	error_reporting(E_ALL);
  ini_set("xdebug.var_display_max_children", '-1');
  ini_set("xdebug.var_display_max_data", '-1');
  ini_set("xdebug.var_display_max_depth", '-1');
} else {
	error_reporting($error_reporting_level);
}

if (isset($_SESSION['table_prefix'])) {
   $table_prefix=substr($_SESSION['table_prefix'],0,12);
} elseif(isset($_GET['tp'])) {
	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {
		$table_prefix=filter_var(substr($_GET['tp'],0,12),FILTER_SANITIZE_ADD_SLASHES);
	} else {
		$table_prefix=addslashes(substr($_GET['tp'],0,12));
	}
} else {
	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {
		$table_prefix=filter_var(substr($table_prefix,0,12),FILTER_SANITIZE_ADD_SLASHES);
	} else {
		$table_prefix=addslashes(substr($table_prefix,0,12));
	}
}

// tabelle comuni alle aziende della stessa gestione
$tn = array('admin','admin_config','admin_module','anagra','anagraes','aziend','bank','breadcrumb',
    'camp_avversita','camp_colture','camp_fitofarmaci','camp_uso_fitofarmaci','classroom',
    'config','country','currencies','currency_history','destina','forme_giuridiche','languages',
    'menu_module','menu_script','menu_usage','module','municipalities','provinces','regions',
    'staff_absence_type','staff_work_type','students');
foreach ($tn as $v) {
    $gTables[$v] = $table_prefix . "_" . $v;
}

date_default_timezone_set($Timezone);

if ($gazie_locale != "") {
  setlocale(LC_TIME, $gazie_locale);
} else {
  if (isset($link) && $link) {
    $local = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? gaz_dbi_get_row($gTables['config'], 'variable', 'win_locale'):gaz_dbi_get_row($gTables['config'], 'variable', 'lin_locale');
    $gazie_locale = $local['cvalue'];
    setlocale(LC_TIME, $local['cvalue']);
  }
}

$gazTimeFormatter = new IntlDateFormatter($gazie_locale,IntlDateFormatter::FULL,IntlDateFormatter::FULL,$Timezone);

$id = 1;
if (isset($_SESSION['company_id'])) {
    $id = sprintf('%03d', $_SESSION['company_id']);
}

/* controllo anche se includere il file dei nomi di tabelle specifico del modulo
  residente nella directory del module stesso, con queste caratteristiche:
  modules/nome_modulo/lib.data.php
 */
if (@file_exists('./lib.data.php')) {
    require('./lib.data.php');
}

//tabelle aziendali
$tn = array('agenti','agenti_forn','aliiva','artico','artico_group','artico_position','assets','assist','banapp','body_text',
'campi','camp_artico','camp_mov_sian','camp_recip_stocc','cash_register','cash_register_reparto','cash_register_tender',
'catmer','caucon','caucon_rows','caumag','clfoco','company_config','company_data','comunicazioni_dati_fatture','customer_group',
'contract', 'contract_row','distinta_base','effett','expdoc','extcon','fae_flux','files','imball','instal','letter',
'liquidazioni_iva', 'lotmag', 'movmag','orderman','pagame','pagame_distribution','paymov','portos','provvigioni','ragstat',
'registro_trattamento_dati','rigbro','rigdoc','rigmoc','rigmoi','sconti_articoli','sconti_raggruppamenti','shelves','spediz',
'staff','staff_skills','staff_worked_hours','staff_work_movements','tesbro','tesdoc','tesmov','vettor','warehouse');
foreach ($tn as $v) {
    $gTables[$v] = $table_prefix . "_" . $id . $v;
}

?>
