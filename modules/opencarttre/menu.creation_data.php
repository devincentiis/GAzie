<?php
/*
  --------------------------------------------------------------------------
  Copyright (C) - Antonio De Vincentiis, Montesilvano anno 2020
  tel.3383121161
  a.devincentiis@tiscali.it
  Montesilvano (PE)
  --------------------------------------------------------------------------
*/
$menu_data = array( 'm1'=>array('link'=>"docume_opencarttre.php"),
	'm2'=>array(1=>array('translate_key'=>1,'link'=>"get_oc_order.php",'weight'=>1),
							2=>array('translate_key'=>2,'link'=>"list_oc_customers.php",'weight'=>5),
							3=>array('translate_key'=>3,'link'=>"upsert_oc_catalog.php",'weight'=>10),
							4=>array('translate_key'=>4,'link'=>"admin_oc_api.php",'weight'=>15)));
$module_class='fas fas fa-exchange-alt';
$update_db[]="INSERT INTO ".$gTables['company_data']." (`description`, `var`) VALUES ('URL per API login dell\'ecommerce', 'oc_api_url')";
$update_db[]="INSERT INTO ".$gTables['company_data']." (`description`, `var`) VALUES ('Nome utente per accesso ad API ecommerce', 'oc_api_username')";
$update_db[]="INSERT INTO ".$gTables['company_data']." (`description`, `var`) VALUES ('Chiave per accesso ad API ecommerce', 'oc_api_key')";
$update_db[]="UPDATE ".$gTables['aziend']." SET gazSynchro=CONCAT('opencarttre,',gazSynchro) WHERE 1";
?>
