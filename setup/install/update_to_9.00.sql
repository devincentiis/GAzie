UPDATE `gaz_config` SET `cvalue` = '150' WHERE `id` =2;
CREATE TABLE IF NOT EXISTS `gaz_anagraes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ragso1` TINYBLOB,
  `ragso2` TINYBLOB,
  `sedleg` BLOB,
  `legrap_pf_nome` TINYBLOB,
  `legrap_pf_cognome` TINYBLOB,
  `sexper` TINYBLOB,
  `datnas` TINYBLOB,
  `luonas` TINYBLOB,
  `pronas` TINYBLOB,
  `counas` TINYBLOB,
  `indspe` TINYBLOB,
  `capspe` TINYBLOB,
  `citspe` TINYBLOB,
  `prospe` TINYBLOB,
  `country` TINYBLOB,
  `telefo` TINYBLOB,
  `fax` TINYBLOB,
  `cell` TINYBLOB,
  `codfis` TINYBLOB,
  `pariva` TINYBLOB,
  `fe_cod_univoco` TINYBLOB,
  `e_mail` TINYBLOB,
  `e_mail2` TINYBLOB,
  `pec_email` TINYBLOB,
  `adminid` VARCHAR(20) DEFAULT NULL,
  `last_modified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM COMMENT='Per contenere le anagrafiche criptate, utilizzabile in abbinamento con altre tabelle contenenti dati particolarmente sensibili criptati anch\'essi .';
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXcompany_data`
	ADD COLUMN `dataes` MEDIUMBLOB NULL COMMENT 'consente l\'utilizzo di AES_ENCRYPT' AFTER `data`,
	ADD COLUMN `adminid` VARCHAR(20) NOT NULL AFTER `ref`,
	ADD COLUMN `last_modified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `adminid`;
ALTER TABLE `gaz_XXXfiles`
	ADD COLUMN `content` MEDIUMBLOB NULL COMMENT 'per contenere riferimenti o l\'intero file dentro il database, eventualemente criptato' AFTER `title`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
