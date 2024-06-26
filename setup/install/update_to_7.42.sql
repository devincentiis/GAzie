UPDATE `gaz_config` SET `cvalue` = '137' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXvettor`	ADD COLUMN `id_anagra` INT NOT NULL AFTER `codice`,	ADD INDEX `id_anagra` (`id_anagra`);
UPDATE `gaz_XXXvettor` t1 JOIN `gaz_anagra` t2 ON t1.partita_iva = t2.pariva SET t1.id_anagra = t2.id WHERE t1.partita_iva > 0;
INSERT INTO `gaz_anagra` (ragso1, sexper, indspe, capspe, citspe, prospe, country,id_currency,id_language,telefo,pariva,codfis) SELECT ragione_sociale,'G', indirizzo, cap, citta,provincia,'IT',1,1,telefo,partita_iva,codice_fiscale FROM `gaz_XXXvettor` WHERE `gaz_XXXvettor`.`id_anagra` = 0 AND `gaz_XXXvettor`.`partita_iva` > 0;
UPDATE `gaz_XXXvettor` t1 JOIN `gaz_anagra` t2 ON t1.partita_iva = t2.pariva SET t1.id_anagra = t2.id WHERE t1.partita_iva > 0;
UPDATE `gaz_XXXvettor` SET codice_fiscale = partita_iva WHERE LENGTH (codice_fiscale) < 5;
ALTER TABLE `gaz_XXXvettor`	CHANGE COLUMN `descri` `conducente` VARCHAR(100) NOT NULL DEFAULT '' AFTER `n_albo`, ADD COLUMN `targa` VARCHAR(20) NOT NULL DEFAULT '' AFTER `telefo`;
ALTER TABLE `gaz_XXXrigbro`	CHANGE COLUMN `sconto` `sconto` DECIMAL(6,3) NULL DEFAULT '0.0' AFTER `prelis`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )