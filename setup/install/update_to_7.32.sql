UPDATE `gaz_config` SET `cvalue` = '127' WHERE `id` =2;
UPDATE `gaz_admin` SET `style`='DEFAULT.css',`skin`='DEFAULT.css' WHERE 1;
ALTER TABLE `gaz_breadcrumb` ADD COLUMN `grid_class` VARCHAR(127) NOT NULL DEFAULT '' AFTER `icon`;
ALTER TABLE `gaz_aziend` ADD COLUMN `sync_ecom_mod` VARCHAR(50) NOT NULL DEFAULT 'shop-synchronize' COMMENT 'indico il modulo dove trovare la classe contenente le funzioni  necessarie per aggiornare un sito ecommerce tramite le sue API ad ogni cambiamento di movimento di magazzino, cliente, articolo, categoria merceologica, aliquota IVA, presenza di nuovi ordini e/o clienti inseriti dal sito. GAzie provvederà a fare delle chiamate alle funzioni contenute in essa' AFTER `web_url`;
ALTER TABLE `gaz_anagra` CHANGE `indspe` `indspe` VARCHAR( 60 ) NOT NULL ;
ALTER TABLE `gaz_anagra` CHANGE `citspe` `citspe` VARCHAR( 60 ) NOT NULL ;
ALTER TABLE `gaz_anagra` CHANGE `e_mail` `e_mail` VARCHAR( 60 ) NOT NULL ;
ALTER TABLE `gaz_anagra` CHANGE `pec_email` `pec_email` VARCHAR( 60 ) NOT NULL ;
UPDATE `gaz_menu_module` SET `link`='report_broven.php?auxil=VO_' WHERE  `link`='report_broven.php?auxil=VOR';
DELETE FROM `gaz_menu_script` WHERE `link`='import_gaziecart.php';
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXtesbro`	ADD COLUMN `ref_ecommerce_id_order` VARCHAR(50) NULL DEFAULT '' COMMENT 'Identificativo ordine attribuito dall\'eventuale ecommerce collegato attraverso API' AFTER `tipdoc`, ADD INDEX `ref_ecommerce_id_order` (`ref_ecommerce_id_order`);
ALTER TABLE `gaz_XXXartico`	ADD COLUMN `ref_ecommerce_id_product` VARCHAR(50) NULL DEFAULT '' COMMENT 'Codice di riferimento allo stesso articolo in eventuale ecommerce collegato attraverso API' AFTER `codice_fornitore`, ADD INDEX `ref_ecommerce_id_product` (`ref_ecommerce_id_product`);
ALTER TABLE `gaz_XXXcatmer`	ADD COLUMN `ref_ecommerce_id_category` VARCHAR(50) NULL DEFAULT '' COMMENT 'Codice di riferimento alla stessa categoria articoli in eventuale ecommerce collegato attraverso API' AFTER `web_url`, ADD INDEX `ref_ecommerce_id_category` (`ref_ecommerce_id_category`);
ALTER TABLE `gaz_XXXclfoco`	ADD COLUMN `ref_ecommerce_id_customer` VARCHAR(50) NULL DEFAULT '' COMMENT 'Codice di riferimento allo stesso cliente in eventuale ecommerce collegato attraverso API' AFTER `descri`, ADD INDEX `ref_ecommerce_id_customer` (`ref_ecommerce_id_customer`);
INSERT INTO `gaz_XXXcompany_config` (`description`,`var`,`val`) VALUES ('Nome della libreria di terze parti da usare per inviare sms','send_sms','');
ALTER TABLE `gaz_XXXcatmer`	ADD COLUMN `top` TINYINT NOT NULL DEFAULT 0 COMMENT 'posizione di visualizzazione/pubblicazione' AFTER `ricarico`;
SET @a  = 0 ;
UPDATE `gaz_XXXartico` SET `ref_ecommerce_id_product` = @a:=@a+1 WHERE 1 ORDER BY `catmer`, `codice`;
ALTER TABLE `gaz_XXXaliiva`	CHANGE COLUMN `fae_natura` `fae_natura` VARCHAR(4) NOT NULL AFTER `taxstamp`;
ALTER TABLE `gaz_XXXpagame`	ADD COLUMN `web_payment_ref` VARCHAR(50) NOT NULL COMMENT 'Riferimento al metodo di pagamento utilizzato dall\'ecommerce' AFTER `id_bank`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)