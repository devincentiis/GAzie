UPDATE `gaz_config` SET `cvalue` = '122' WHERE `id` =2;
ALTER TABLE `gaz_breadcrumb` ADD COLUMN `exec_mode` INT NULL COMMENT 'Modo di visualizzazione/esecuzione dello script: 0=simple link (solo su breadcrumb),1=button link,2=frame window,3=background execution' AFTER `id_bread`, ADD COLUMN `position_order` INT NULL AFTER `link`,ADD COLUMN `icon` BLOB NOT NULL AFTER `position_order`, ADD COLUMN `adminid` VARCHAR(20) NOT NULL DEFAULT '' AFTER `icon`;
ALTER TABLE `gaz_breadcrumb` COMMENT='Tabella utilizzata sia per la personalizzazione della breadcrumb del menù (exec_mode=0) che per i widget della dashboard personalizzate dai singoli utenti (exec_mode=2)';
ALTER TABLE `gaz_breadcrumb` CHANGE COLUMN `file` `file` VARCHAR(255) NOT NULL AFTER `exec_mode`, CHANGE COLUMN `titolo` `titolo` VARCHAR(255) NOT NULL AFTER `file`, CHANGE COLUMN `link` `link` VARCHAR(255) NOT NULL AFTER `titolo`;

-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
