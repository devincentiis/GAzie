UPDATE `gaz_config` SET `cvalue` = '121' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXorderman` ADD COLUMN `id_staff_def` INT NOT NULL COMMENT 'Utilizzabile per la preselezione della persona addetta alla produzione/consulenza/intervento e/o al collaboratore responsabile della stessa' AFTER `duration`,
	ADD COLUMN `stato_lavorazione` INT NOT NULL COMMENT '0=aperto, 1=in attesa, 2=in lavorazione, 3=materiale ordinato, 4=incontrate difficoltà, 5=in attesa di spedizione, 6=spedito, 7=consegnato, 8=non chiuso, 9=chiuso' AFTER `id_staff_def`,
	ADD COLUMN `tracking_no` VARCHAR(50) NOT NULL AFTER `stato_lavorazione`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
