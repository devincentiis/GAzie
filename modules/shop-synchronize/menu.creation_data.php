<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  SHOP SYNCHRONIZE è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2021 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
$menu_data = array('m1' => array('link' => "docume_shop-synchronize.php"),
                        'm2' => array(  1 => array('link' => "synchronize.php", 'weight' => 1)
                         ),
                        'm3' => array()
                );

$module_class='fas fas fa-exchange-alt';

$admin_aziend=checkAdmin();

// Li commento, ma possono servire di esempio a chi clona e personalizza questo modulo per altri ecommerce
//$update_db[]="INSERT INTO ".$gTables['company_data']." (`description`, `var`) VALUES ('URL per API login dell\'ecommerce', 'oc_api_url')";
//$update_db[]="INSERT INTO ".$gTables['company_data']." (`description`, `var`) VALUES ('Nome utente per accesso ad API ecommerce', 'oc_api_username')";
//$update_db[]="INSERT INTO ".$gTables['company_data']." (`description`, `var`) VALUES ('Chiave per accesso ad API ecommerce', 'oc_api_key')";

// valorizzo automaticamente in configurazione azienda con il nome del modulo
$update_db[]="UPDATE ".$table_prefix."_aziend SET `gazSynchro`='shop-synchronize' WHERE `codice`=".$admin_aziend['company_id'];
?>
