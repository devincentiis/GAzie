UPDATE `gaz_config` SET `cvalue` = '34' WHERE `id` =2;
ALTER TABLE `gaz_aziend` ADD `decimal_quantity` INT NOT NULL AFTER `regime` ;
ALTER TABLE `gaz_rigdoc` CHANGE `quanti` `quanti` DECIMAL( 12, 3 );
ALTER TABLE `gaz_rigbro` CHANGE `quanti` `quanti` DECIMAL( 12, 3 ); 
ALTER TABLE `gaz_movmag` CHANGE `quanti` `quanti` DECIMAL( 12, 3 );
INSERT INTO `gaz_menu_script` (`id` ,`id_menu` ,`link` ,`icon` ,`class` ,`translate_key` ,`accesskey` ,`weight`)VALUES ('51', '7', 'report_salcon.php', '', '', '3', '', '3');
INSERT INTO `gaz_country` (`iso` ,`name` ,`printable_name` ,`iso3` ,`numcode` ,`bank_code_pos` ,`bank_code_lenght` ,`bank_code_fix` ,`bank_code_alpha` ,`account_number_pos` ,`account_number_lenght` ,`account_number_fix` ,`account_number_alpha` ,`VAT_number_lenght` ,`VAT_number_alpha`) VALUES ('SI', 'SLOVENIAN', 'Slovenian', 'SVN', '705' , '0', '5', '1', '0', '5', '10', '1', '', '8', '1');
ALTER TABLE `gaz_rigdoc` CHANGE `descri` `descri` VARCHAR( 511 );
ALTER TABLE `gaz_rigbro` CHANGE `descri` `descri` VARCHAR( 511 );
ALTER TABLE `gaz_artico` CHANGE `descri` `descri` VARCHAR( 255 );
UPDATE `gaz_config` SET `cvalue` = '35' WHERE `id` =2;
UPDATE `gaz_menu_script` SET `link` = 'admin_bank_account.php?Insert' WHERE `gaz_menu_script`.`id` =41 LIMIT 1 ; 
UPDATE `gaz_config` SET `cvalue` = '36' WHERE `id` =2;
UPDATE `gaz_aziend` SET `decimal_quantity` = 9 WHERE 1;
ALTER TABLE `gaz_aziend` ADD `country` VARCHAR( 2 ) NOT NULL AFTER `prospe` ;
UPDATE `gaz_aziend` SET `country` = 'IT' WHERE 1;
ALTER TABLE `gaz_rigbro` ADD `delivery_date` DATE NOT NULL AFTER `provvigione` ;
ALTER TABLE `gaz_rigdoc` ADD `id_order` INT NOT NULL AFTER `provvigione` ;