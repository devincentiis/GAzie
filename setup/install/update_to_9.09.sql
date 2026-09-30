UPDATE `gaz_config` SET `cvalue` = '158' WHERE `id` =2;
ALTER TABLE `gaz_currency_history`
	CHANGE COLUMN `id_currency` `id_currency` INT NOT NULL COMMENT 'La valuta di base, normalmente 0 (euro)' FIRST,
	CHANGE COLUMN `change_value` `change_value` DECIMAL(12,5) NOT NULL COMMENT 'Il valore in euro della valuta obj' AFTER `id_currency`,
	CHANGE COLUMN `date_reference` `date_reference` DATE NOT NULL COMMENT 'La data cui il cambio della valuta fa riferimento' AFTER `change_value`,
	CHANGE COLUMN `id_currency_obj` `id_currency_obj` INT NOT NULL COMMENT 'Referenza a gaz_currencies con la valuta cui si riferisce il cambio' AFTER `date_reference`,
	ADD INDEX `id_currency` (`id_currency`),
	ADD INDEX `id_currency_obj` (`id_currency_obj`),
	ADD INDEX `date_reference` (`date_reference`);
ALTER TABLE `gaz_anagra` CHANGE `pariva` `pariva` VARCHAR(28) NOT NULL DEFAULT '0';
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`, `val`) VALUES ('Inserimento automatico rigo testo descrittivo articolo 0=mai,1=prima,2=dopo,','ext_artico_description','2');
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
