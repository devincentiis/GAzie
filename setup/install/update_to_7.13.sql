UPDATE `gaz_config` SET `cvalue` = '112' WHERE `id` =2;
ALTER TABLE `gaz_aziend`
	ADD COLUMN `REA_ufficio` VARCHAR(2) NOT NULL DEFAULT '' COMMENT 'Fattura elettronica 1.2.4.1: Sigla della provincia dell\'Ufficio del registro delle imprese presso il quale è registrata la società' AFTER `legrap_pf_cognome`,
	ADD COLUMN `REA_numero` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Fattura elettronica 1.2.4.2: Numero di iscrizione al registro delle imprese' AFTER `REA_ufficio`,
	ADD COLUMN `REA_capitale` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Fattura elettronica 1.2.4.3: Nei soli casi di società di capitali (SpA, SApA, SRL), l\'elemento informativo va valorizzato per indicare il capitale sociale' AFTER `REA_numero`,
	ADD COLUMN `REA_socio` VARCHAR(2) NOT NULL DEFAULT '' COMMENT 'Fattura elettronica 1.2.4.4: Nei casi di spa e srl, l\'elemento informativo va valorizzato per indicare se vi è un socio unico oppure se vi sono più soci, valori ammessi [SU] socio unico e [SM] più soci' AFTER `REA_capitale`,
	ADD COLUMN `REA_stato` VARCHAR(2) NOT NULL DEFAULT '' COMMENT 'Fattura elettronica 1.2.4.5: Indica se la Società si trova in stato di liquidazione oppure no, valori ammessi [LS] in liquidazione [LN] non in liquidazione' AFTER `REA_socio`,
	DROP COLUMN `rea`;
UPDATE `gaz_aziend` SET `REA_stato`='LN' WHERE 1;
UPDATE `gaz_config` SET `cvalue` = '113' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`) VALUES ('Eventuale ultimo rigo descrittivo su fatture es. Contributo CONAI', 'descriptive_last_row');
CREATE TABLE `gaz_XXXsyncronize_oc` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `table_oc` VARCHAR(40) NOT NULL,
  `table_gz` VARCHAR(40) NOT NULL,
  `id_oc` INT NOT NULL,
  `id_gz` INT NOT NULL,
  `date_created` datetime NOT NULL,
  `date_update` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;
ALTER TABLE `gaz_XXXtesdoc`	ADD COLUMN `fattura_elettronica_reinvii` INT NULL COMMENT 'Numero di reinvii allo SdI della fattura elettronica, assieme alla sezione, alla data fattura e al numero di protocollo e dopo un encode in base 36 determinerà gli ultimi cinque caratteri del nome file' AFTER `fattura_elettronica_original_content`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
