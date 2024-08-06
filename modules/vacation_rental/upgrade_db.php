<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  Vacation Rental è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
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
//upgrade database per il modulo Vacation Rental
$dbname = constant("Database");
global $table_prefix;

$id_mod = gaz_dbi_get_row($table_prefix.'_module', 'name', 'vacation_rental')['id'];// id modulo vacation rental
$query = "SELECT MAX(id) AS maxid FROM `".$table_prefix."_menu_module`";
$result = gaz_dbi_query ($query);
$row = gaz_dbi_fetch_array($result);
$nextid=$row['maxid']+1;// ultimo id tabella menu_module

// da qui in poi iserire le query che saranno eseguite su ogni azienda con il modulo attivo

/*  >>> esempio di come vanno impostate le query il numero [147] rappresenta la versione dell'update di GAzie
$upgrade_db[147][]="ALTER TABLE ".$table_prefix."_XXXrental_discounts ADD `test2` TINYINT(2) NOT NULL DEFAULT '0' COMMENT 'test update 2';";
*/

$upgrade_db[148][]="INSERT INTO ".$table_prefix."_XXXcompany_config (`id`, `description`, `var`, `val`) VALUES (NULL, 'URL di ritorno da Stripe-PayPal dopo avvenuto pagamento ', 'vacation_url_stripe', NULL);";
$upgrade_db[148][]="CREATE TABLE `".$dbname.$table_prefix."_XXXrental_feedback_elements` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `element` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL , `facility` INT(9) NULL COMMENT 'eventuale riferimento alla struttura' , `status` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL , PRIMARY KEY (`id`)) ENGINE = MyISAM;";
$upgrade_db[148][]="CREATE TABLE `".$dbname.$table_prefix."_XXXrental_feedbacks` ( `id` INT(12) NOT NULL AUTO_INCREMENT , `reservation_id` INT(32) NOT NULL , `text` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `text_reply` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, `house_code` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `customer_anagra_id` INT(9) NOT NULL , `created_date` DATETIME NOT NULL, `modified_date` DATETIME NOT NULL, `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0= in attesa di approvazione\r\n1=approvato\r\n2=bloccato' , PRIMARY KEY (`id`)) ENGINE = MyISAM;";
$upgrade_db[148][]="CREATE TABLE `".$dbname.$table_prefix."_XXXrental_feedback_scores` ( `id` INT(12) NOT NULL AUTO_INCREMENT , `score` TINYINT(3) NOT NULL , `feedback_id` INT(12) NOT NULL , `element_id` INT(11) NOT NULL , PRIMARY KEY (`id`)) ENGINE = MyISAM; ";
$upgrade_db[149][]="ALTER TABLE ".$table_prefix."_XXXrental_feedbacks ADD INDEX(`reservation_id`);";
$upgrade_db[150][]="ALTER TABLE ".$table_prefix."_XXXrental_extra ADD `rif_facility` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT 'riferimento ad id_artico_group' AFTER `obligatory`;";
$upgrade_db[150][]="ALTER TABLE `".$table_prefix."_XXXrental_extra` CHANGE `rif_alloggio` `rif_alloggio` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL; ";
$upgrade_db[151][]="INSERT INTO ".$table_prefix."_XXXcompany_config (`id`, `description`, `var`, `val`) VALUES (NULL, 'Abilita promemoria x giorni prima del check-in (0=invio disabilitato)', 'reminvacation_day', '0'),(NULL, 'Stato della prenotazione (vuoto=tutti gli stati)', 'reminvacation_status', 'CONFIRMED'),(NULL, 'Abilita promemoria pagamento x giorni dalla creazione della prenotazione (0=invio disabilitato)', 'rempayvacation_day', '0'),(NULL, 'Abilita annullamento prenotazione x giorni dopo il promemoria pagamento (0=disabilitato)', 'rempayaftervacation_day', '0');";
$upgrade_db[151][]="INSERT INTO ".$table_prefix."_XXXcompany_config (`id`, `description`, `var`, `val`) VALUES (NULL, 'e-mail di notifica generale (vuoto= disabilitato)', 'vacation_email_notification', NULL);";
$upgrade_db[152][]="ALTER TABLE `".$table_prefix."_XXXrental_events` ADD INDEX(`Ical_sync_id`);";
$upgrade_db[152][]="ALTER TABLE `".$table_prefix."_XXXrental_events` ADD INDEX(`end`);";
$upgrade_db[152][]="ALTER TABLE `".$table_prefix."_XXXrental_prices` ADD INDEX(`start`);";
$upgrade_db[152][]="ALTER TABLE `".$table_prefix."_XXXrental_prices` ADD INDEX(`end`);";
$upgrade_db[152][]="ALTER TABLE `".$table_prefix."_XXXrental_prices` ADD INDEX(`house_code`);";
$upgrade_db[153][]="UPDATE `".$table_prefix."_XXXartico` SET `custom_field` = 'vacation_rental TASSA-TURISTICA' WHERE `".$table_prefix."_XXXartico`.`codice` = 'TASSA-TURISTICA';";
$upgrade_db[153][]="INSERT INTO `".$table_prefix."_menu_module` (`id`, `id_module`, `link`, `icon`, `class`, `translate_key`, `accesskey`, `weight`) VALUES ('".$nextid."', '".$id_mod."', 'report_booking.php?auxil=VPR', '', '', '7', '', '7') ";
$upgrade_db[153][]="INSERT INTO `".$table_prefix."_menu_script` (`id`, `id_menu`, `link`, `icon`, `class`, `translate_key`, `accesskey`, `weight`) VALUES (NULL, '".$nextid."', 'admin_booking.php?Insert&tipdoc=VPR', '', '', '7', '', '7') ";
$upgrade_db[154][]="ALTER TABLE `".$table_prefix."_XXXrental_discounts` ADD `last_min` TINYINT(3) NOT NULL DEFAULT '0' COMMENT '0=last min escluso numero=giorni prima del checkin per avere lo sconto' AFTER `reusable`; ";
$upgrade_db[155][]="INSERT INTO ".$table_prefix."_XXXcompany_config (`description`, `var`, `val`) VALUES ('Abilita sistema punti (0=disabilitato; 1=abilitato)', 'pointenable', '0'),('1 punto per Euro', 'pointeuro', '0'),('Nome livello 1', 'pointlevel1name', ''),('Punti livello 1', 'pointlevel1', '0'),('Nome livello 2', 'pointlevel2name', ''),('Punti livello 2', 'pointlevel2', '0'),('Nome livello 3', 'pointlevel3name', ''),('Punti livello 3', 'pointlevel3', '0');";
$upgrade_db[155][]="ALTER TABLE `".$table_prefix."_XXXrental_discounts` ADD `level_points` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Abilita questo sconto per livello punti' AFTER `last_min`; ";
$upgrade_db[155][]="ALTER TABLE `".$table_prefix."_XXXrental_prices` ADD `minstay` INT(3) NULL COMMENT 'Soggiorno minimo: se valorizzato annulla quello generale ' AFTER `price`; ";
$upgrade_db[155][]="INSERT INTO ".$table_prefix."_XXXcompany_config (`description`, `var`, `val`) VALUES ('Scadenza punti gg (0=senza scadenza)', 'points_expiry', '0')";
$upgrade_db[155][]="ALTER TABLE `".$table_prefix."_XXXartico` CHANGE `web_url` `web_url` VARCHAR(700) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;";
$upgrade_db[155][]="INSERT INTO ".$table_prefix."_XXXcompany_config (`description`, `var`, `val`) VALUES ('URL front-end del regolamento web sel check-in', 'vacation_url_selfcheck', '')";
$upgrade_db[160][]="ALTER TABLE `".$table_prefix."_XXXrental_feedback_elements` ADD `description` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT '' COMMENT 'Breve descrizione' AFTER `element`";
?>
