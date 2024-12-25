<?php
/*  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (https://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
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

$menu_data = array('m1' => array('link' => "docume_vacation_rental.php"),
                        'm2' => array(1 => array('link' => "report_accommodation.php", 'weight' => 1),
                        2 => array('link' => "report_facility.php", 'weight' => 2),
                        3 => array('link' => "report_booking.php?auxil=VOR", 'weight' => 3),
                        4 => array('link' => "report_extra.php", 'weight' => 4),
                        5 => array('link' => "report_discount.php", 'weight' => 5),
                        7 => array('link' => "report_booking.php?auxil=VPR", 'weight' => 7),
                        6 => array('link' => "settings.php", 'weight' => 6)
                        ),
                        'm3' => array('m2' => array(
                          1 => array( array('translate_key' => 1, 'link' => "admin_house.php?Insert", 'weight' => 10)
                          ),
                          2 => array(	array('translate_key' => 2, 'link' => "admin_facility.php", 'weight' => 20)
                          ),
                          3 => array( array('translate_key' => 3, 'link' => "admin_booking.php?Insert&tipdoc=VOR", 'weight' => 30)
                          ),
                          4 => array( array('translate_key' => 4, 'link' => "admin_extra.php?Insert", 'weight' => 40)
                          ),
                          5 => array( array('translate_key' => 5, 'link' => "admin_discount.php?Insert", 'weight' => 50)
                          ),
                          7 => array( array('translate_key' => 7, 'link' => "admin_booking.php?Insert&tipdoc=VPR", 'weight' => 70)
                          )
                        )
									 )
                );
$module_class='fas fa-landmark';

$update_db[]="CREATE TABLE `".$table_prefix."_XXXrental_events` ( `id` INT(32) NOT NULL AUTO_INCREMENT , `title` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `start` DATE NULL DEFAULT NULL , `end` DATE NULL DEFAULT NULL , `house_code` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `id_tesbro` INT(9) NOT NULL , PRIMARY KEY (`id`)) ENGINE = MyISAM;";
$update_db[]="CREATE TABLE `".$table_prefix."_XXXrental_prices` ( `id` INT(32) NOT NULL AUTO_INCREMENT , `title` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `start` DATE NULL DEFAULT NULL , `end` DATE NULL DEFAULT NULL , `house_code` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `price` DECIMAL(13,4) NOT NULL , PRIMARY KEY (`id`)) ENGINE = MyISAM;";
$update_db[]="CREATE TABLE `".$table_prefix."_XXXrental_ical` ( `id` INT(2) NOT NULL AUTO_INCREMENT , `url` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `codice_alloggio` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `ical_descri` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , PRIMARY KEY (`id`)) ENGINE = MyISAM; ";
$update_db[]="ALTER TABLE ".$table_prefix."_XXXrental_events ADD INDEX(`house_code`), ADD INDEX(`start`);";
$update_db[]="ALTER TABLE ".$table_prefix."_XXXrental_events ADD `id_rigbro` INT(9) NULL DEFAULT NULL COMMENT 'riferimento al rigo di rigbro', ADD `adult` INT(2) NULL AFTER `id_tesbro`, ADD `child` INT(2) NOT NULL AFTER `adult`, ADD `Ical_sync_id` INT(2) NULL DEFAULT NULL COMMENT 'Se l\'evento è stato inserito da una importazione di un Ical, ne imposto il suo id' AFTER `child`; ";
$update_db[]="CREATE TABLE `".$table_prefix."_XXXrental_extra` ( `id` INT(2) NOT NULL AUTO_INCREMENT , `mod_prezzo` INT(1) NOT NULL , `rif_alloggio` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `max_quantity` INT NOT NULL DEFAULT '0' , PRIMARY KEY (`id`)) ENGINE = MyISAM; ";
$update_db[]="ALTER TABLE ".$table_prefix."_XXXrental_extra ADD `obligatory` INT NULL DEFAULT '0' COMMENT '0=facoltativo - 1=obbligatorio', ADD `codart` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Riferimento alla tabella artico' AFTER `max_quantity`; ";
$update_db[]="INSERT INTO ".$table_prefix."_XXXcompany_config (`description`, `var`, `val`) VALUES ('Notti da bloccare prima e dopo ogni prenotazione', 'vacation_blockdays', '0'),('Notti minime da prenotare ', 'vacation_minnights', '1')";
$update_db[]="INSERT INTO ".$table_prefix."_XXXcompany_config (`description`, `var`, `val`) VALUES ('Abilita sistema punti (0=disabilitato; 1=abilitato)', 'pointenable', '0'),('1 punto per Euro', 'pointeuro', '0'),('Nome livello 1', 'pointlevel1name', ''),('Punti livello 1', 'pointlevel1', '0'),('Nome livello 2', 'pointlevel2name', ''),('Punti livello 2', 'pointlevel2', '0'),('Nome livello 3', 'pointlevel3name', ''),('Punti livello 3', 'pointlevel3', '0')";
$update_db[]="INSERT INTO ".$table_prefix."_XXXcompany_config (`id`, `description`, `var`, `val`) VALUES (NULL, 'ID pagamento con bonifico bancario per front-end (sincronizzazione con pagamenti GAzie)', 'vacation_id_pagbon', '0'), (NULL, 'ID pagamento con carta di credito off-line per front-end (sincronizzazione con pagamenti GAzie)', 'vacation_id_pagccoff', '0'), (NULL, 'ID pagamento con carta di credito on-line per front-end (sincronizzazione con pagamenti GAzie)', 'vacation_id_pagccon', '0') ";
$update_db[]="INSERT INTO ".$table_prefix."_XXXcompany_config (`id`, `description`, `var`, `val`) VALUES (NULL, 'URL front-end del regolamento locazioni', 'vacation_url_rules', NULL), (NULL, 'URL front-end del regolamento sulla privacy', 'vacation_url_privacy', NULL), (NULL, 'URL front-end pagina di accesso utente', 'vacation_url_user', NULL) ";
$update_db[]="INSERT INTO ".$table_prefix."_XXXcompany_config (`id`, `description`, `var`, `val`) VALUES (NULL, 'Usa prezzi IVA compresa nei calendari(si / no)', 'vacation_ivac', 'no');";
$update_db[]="INSERT INTO ".$table_prefix."_XXXcompany_config (`id`, `description`, `var`, `val`) VALUES (NULL, 'ID del conto corrente bancario su cui ricevere i bonifici delle prenotazioni alloggi', 'vacation_ccb', '0');";
$update_db[]="CREATE TABLE `".$table_prefix."_XXXrental_discounts` ( `id` INT NOT NULL AUTO_INCREMENT , `title` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL , `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL , `facility_id` INT(9) NULL , `accommodation_code` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL , `min_stay` TINYINT(3) NULL , `valid_from` DATE NULL , `valid_to` DATE NULL , `value` DECIMAL(12,2) NULL , `value_adult` DECIMAL(12,2) NULL , `value_child` DECIMAL(12,2) NULL , `is_percent` TINYINT(1) NULL , `priority` INT(11) NULL , `custom_field` TEXT NULL COMMENT 'Riferimenti generici utilizzabili sui moduli. Normalmente in formato json: {\"nome_modulo\":{\"nome_variabile\":{\"valore_variabile\": {}}}} ' , `stop_further_processing` TINYINT(3) NULL , `STATUS` VARCHAR(50) NULL , PRIMARY KEY (`id`)) ENGINE = MyISAM; ";
$update_db[]="ALTER TABLE ".$table_prefix."_XXXrental_discounts ADD `reusable` TINYINT(2) NOT NULL DEFAULT '0' COMMENT 'Se è zero, lo sconto è utilizzabile una sola volta', ADD `discount_voucher_code` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Se valorizzato questo è un buono sconto' AFTER `stop_further_processing`, ADD `id_anagra` INT(9) NULL DEFAULT NULL COMMENT 'Se valorizzato lo sconto è riservato ad un solo utente' AFTER `discount_voucher_code`;";
$update_db[]="ALTER TABLE ".$table_prefix."_XXXrental_discounts ADD INDEX(`id_anagra`), ADD INDEX(`accommodation_code`), ADD INDEX(`facility_id`);";
$update_db[]="ALTER TABLE ".$table_prefix."_XXXartico_group ADD `custom_field` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `adminid`; ";
$update_db[]="INSERT INTO ".$table_prefix."_XXXartico (`codice`, `descri`, `id_artico_group`, `codice_fornitore`, `id_assets`, `ref_ecommerce_id_product`, `custom_field`, `ecomm_option_attribute`, `quality`, `ordinabile`, `movimentabile`, `good_or_service`, `lot_or_serial`, `image`, `barcode`, `unimis`, `larghezza`, `lunghezza`, `spessore`, `bending_moment`, `catmer`, `ragstat`, `preacq`, `preve1`, `preve2`, `preve3`, `preve4`, `sconto`, `web_mu`, `web_price`, `web_multiplier`, `web_public`, `depli_public`, `web_url`, `aliiva`, `retention_tax`, `last_cost`, `payroll_tax`, `scorta`, `riordino`, `uniacq`, `classif_amb`, `maintenance_period`, `durability`, `durability_mu`, `warranty_days`, `mostra_qdc`, `peso_specifico`, `volume_specifico`, `dose_massima`, `rame_metallico`, `perc_N`, `perc_P`, `perc_K`, `tempo_sospensione`, `SIAN`, `id_reg`, `pack_units`, `codcon`, `id_cost`, `annota`, `adminid`, `last_modified`, `clfoco`, `last_used`) VALUES ('TASSA-TURISTICA', 'Tassa di soggiorno turistica', '0', '', '0', '', 'vacation_rental TASSA-TURISTICA', '', '', '', '', '1', 0, '', '', 'n', NULL, NULL, NULL, NULL, '0', '', '0.00000', '0.00000', '0.00000', '0.00000', '0.00000', '0.000', '', 0, '0', '0', '0', '', '11', '0', '0.00000', '0', '0.000', '0.000', '', '0', '0', '0', NULL, '0', '0', '0.000', '0', '0.000', '0.000', NULL, NULL, NULL, '0', '0', '0', '0', '215000000', '0', NULL, '', CURRENT_TIMESTAMP, NULL, NULL) ";
$update_db[]="ALTER TABLE ".$table_prefix."_XXXrental_events ADD `voucher_id` INT(11) NULL DEFAULT NULL COMMENT 'Se valorizzato indica il Riferimento ID discounts usato' AFTER `id_rigbro`, ADD `checked_in_date` DATETIME NULL DEFAULT NULL COMMENT 'Giorno e ora dell\'effettuato check-in' AFTER `voucher_id`, ADD `checked_out_date` DATETIME NULL DEFAULT NULL COMMENT 'Giorno e ora dell\'effettuato check-out' AFTER `checked_in_date`, ADD `access_code` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `checked_out_date`,  ADD `type` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'tipo di evento' AFTER `access_code`;";
$update_db[]="CREATE TABLE `".$table_prefix."_XXXrental_payments` ( `payment_id` INT(11) NOT NULL AUTO_INCREMENT , `type` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL , `item_number` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `txn_id` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `payment_gross` DECIMAL(14,5) NOT NULL , `currency_code` VARCHAR(5) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `payment_status` VARCHAR(20) NOT NULL , `id_tesbro` INT(9) NOT NULL , `created` DATETIME NOT NULL , PRIMARY KEY (`payment_id`)) ENGINE = MyISAM;";
$update_db[]="INSERT INTO ".$table_prefix."_XXXcompany_config (`id`, `description`, `var`, `val`) VALUES (NULL, 'URL di ritorno da Stripe-PayPal dopo avvenuto pagamento ', 'vacation_url_stripe', NULL), (NULL, 'e-mail di notifica generale (vuoto= disabilitato)', 'vacation_email_notification', NULL);";
$update_db[]="CREATE TABLE `".$table_prefix."_XXXrental_feedback_elements` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `element` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL , `facility` INT(9) NULL COMMENT 'eventuale riferimento alla struttura' , `status` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL , PRIMARY KEY (`id`)) ENGINE = MyISAM;";
$update_db[]="CREATE TABLE `".$table_prefix."_XXXrental_feedbacks` ( `id` INT(12) NOT NULL AUTO_INCREMENT , `reservation_id` INT(32) NOT NULL , `text` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `text_reply` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, `house_code` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `customer_anagra_id` INT(9) NOT NULL , `created_date` DATETIME NOT NULL, `modified_date` DATETIME NOT NULL, `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0= in attesa di approvazione\r\n1=approvato\r\n2=bloccato' , PRIMARY KEY (`id`)) ENGINE = MyISAM;";
$update_db[]="CREATE TABLE `".$table_prefix."_XXXrental_feedback_scores` ( `id` INT(12) NOT NULL AUTO_INCREMENT , `score` TINYINT(3) NOT NULL , `feedback_id` INT(12) NOT NULL , `element_id` INT(11) NOT NULL , PRIMARY KEY (`id`)) ENGINE = MyISAM; ";
$update_db[]="ALTER TABLE ".$table_prefix."_XXXrental_feedbacks ADD INDEX(`reservation_id`);";
$update_db[]="ALTER TABLE ".$table_prefix."_XXXrental_extra ADD `rif_facility` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT 'riferimento ad id_artico_group' AFTER `obligatory`;";
$update_db[]="INSERT INTO ".$table_prefix."_XXXcompany_config (`id`, `description`, `var`, `val`) VALUES (NULL, 'Abilita promemoria x giorni prima del check-in (0=invio disabilitato)', 'reminvacation_day', '0'),(NULL, 'Stato della prenotazione (vuoto=tutti gli stati)', 'reminvacation_status', 'CONFIRMED'),(NULL, 'Abilita promemoria pagamento x giorni dalla creazione della prenotazione(0=invio disabilitato)', 'rempayvacation_day', '0'),(NULL, 'Abilita annullamento prenotazione x giorni dopo il promemoria pagamento (0=disabilitato)', 'rempayaftervacation_day', '0');";
$update_db[]="ALTER TABLE `".$table_prefix."_XXXrental_events` ADD INDEX(`Ical_sync_id`);";
$update_db[]="ALTER TABLE `".$table_prefix."_XXXrental_events` ADD INDEX(`end`);";
$update_db[]="ALTER TABLE `".$table_prefix."_XXXrental_prices` ADD INDEX(`start`);";
$update_db[]="ALTER TABLE `".$table_prefix."_XXXrental_prices` ADD INDEX(`end`);";
$update_db[]="ALTER TABLE `".$table_prefix."_XXXrental_prices` ADD INDEX(`house_code`);";
$update_db[]="ALTER TABLE `".$table_prefix."_XXXrental_discounts` ADD `last_min` TINYINT(3) NOT NULL DEFAULT '0' COMMENT '0=last min escluso numero=giorni prima del checkin per avere lo sconto' AFTER `reusable`; ";
$update_db[]="ALTER TABLE `".$table_prefix."_XXXrental_discounts` ADD `level_points` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Abilita questo sconto per livello punti' AFTER `last_min`; ";
$update_db[]="ALTER TABLE `".$table_prefix."_XXXrental_prices` ADD `minstay` INT(3) NULL COMMENT 'Soggiorno minimo: se valorizzato annulla quello generale ' AFTER `price`; ";
$update_db[]="INSERT INTO ".$table_prefix."_XXXcompany_config (`description`, `var`, `val`) VALUES ('Scadenza punti gg (0=senza scadenza)', 'points_expiry', '0')";
$update_db[]="ALTER TABLE `".$table_prefix."_XXXartico` CHANGE `web_url` `web_url` VARCHAR(700) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;";
$update_db[]="INSERT INTO ".$table_prefix."_XXXcompany_config (`description`, `var`, `val`) VALUES ('URL front-end del regolamento web sel check-in', 'vacation_url_selfcheck', '')";
$update_db[]="ALTER TABLE `".$table_prefix."_XXXrental_feedback_elements` ADD `description` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT '' COMMENT 'Breve descrizione' AFTER `element`";
$update_db[]="ALTER TABLE `".$table_prefix."_XXXrental_payments` ADD `id_paymov` INT(11) NULL DEFAULT '0' COMMENT 'Connessione al movimento contabile di pagamento' AFTER `id_tesbro`, ADD `conto` INT(11) NOT NULL DEFAULT '0' COMMENT 'Conto di accredito' AFTER `id_paymov`;";
?>
