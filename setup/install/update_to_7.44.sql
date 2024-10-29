UPDATE `gaz_config` SET `cvalue` = '139' WHERE `id` =2;
UPDATE `gaz_country` SET `istat_area`='12' WHERE  `iso`='GB';
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='report_movcon.php'), 'acquire_bank_accbal.php', '', '', 15, '', 5  FROM `gaz_menu_script`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXfiles` ADD INDEX (`item_ref`);
ALTER TABLE `gaz_XXXfiles`	CHANGE COLUMN `item_ref` `item_ref` VARCHAR(100) NOT NULL COMMENT 'a secondo dell\'utilizzo che se ne fà può contenere il codice articolo, il filename o altre referenze' AFTER `id_ref`;
ALTER TABLE `gaz_XXXcatmer`	CHANGE COLUMN `ref_ecommerce_id_category` `ref_ecommerce_id_category` VARCHAR(50) NULL DEFAULT '' COMMENT 'Riferimento alla categoria articoli in eventuale ecommerce sincronizzato' AFTER `web_url`;
ALTER TABLE `gaz_XXXartico`	ADD COLUMN `id_artico_group` INT NOT NULL COMMENT 'Se valorizzato punta all\'id di gaz_NNNartico_group e serve per indicare ad un ecommerce sincronizzato che questa è una variante del prodotto genericamente indicato nella tabella citata' AFTER `descri`, CHANGE COLUMN `ref_ecommerce_id_product` `ref_ecommerce_id_product` VARCHAR(50) NULL DEFAULT '' COMMENT 'Riferimento all\'articolo in eventuale ecommerce sincronizzato' AFTER `id_assets`, ADD INDEX `id_artico_group` (`id_artico_group`);
ALTER TABLE `gaz_XXXartico`	ADD COLUMN `ecomm_option_attribute` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Riferimenti  agli attributi, opzioni, varianti utilizzati dall\'ecommerce sincronizzato. Normalmente un array json' AFTER `ref_ecommerce_id_product`;
CREATE TABLE IF NOT EXISTS `gaz_XXXartico_group` (
  `id_artico_group` INT NOT NULL AUTO_INCREMENT,
  `descri` VARCHAR(50) NOT NULL DEFAULT '',
  `large_descri` TEXT NOT NULL,
  `image` blob NOT NULL,
  `web_url` VARCHAR(255) NOT NULL,
  `ref_ecommerce_id_main_product` VARCHAR(50) DEFAULT '' COMMENT 'Riferimento all''articolo principale (padre) in eventuale ecommerce sincronizzato, così ogni rigo di gaz_NNNartico sarà equiparabile alle singole varianti, opzioni, attributi, ecc a secondo del diagramma ER utilizzato',
  `web_public` TINYINT NOT NULL DEFAULT '1',
  `depli_public` TINYINT NOT NULL DEFAULT '1',
  `adminid` VARCHAR(20) NOT NULL DEFAULT '',
  `last_modified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_artico_group`),
  KEY `ref_ecommerce_id_main_product` (`ref_ecommerce_id_main_product`) USING BTREE
) ENGINE=MyISAM;
ALTER TABLE `gaz_XXXcatmer`	ADD COLUMN `large_descri` TEXT NOT NULL AFTER `descri`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )