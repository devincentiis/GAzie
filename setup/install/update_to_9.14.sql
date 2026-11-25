UPDATE `gaz_config` SET `cvalue` = '163' WHERE `id` =2;
ALTER TABLE `gaz_menu_module`	ADD COLUMN `trans_code` VARCHAR(6) NULL COMMENT 'Sequenza di caratteri da digitare per avvio rapido da tastiera' AFTER `accesskey`, ADD INDEX (`trans_code`);
ALTER TABLE `gaz_menu_script`	ADD COLUMN `trans_code` VARCHAR(6) NULL COMMENT 'Sequenza di caratteri da digitare per avvio rapido da tastiera' AFTER `accesskey`, ADD INDEX (`trans_code`);
UPDATE `gaz_menu_script` SET `trans_code`='MOVCON' WHERE `link`='admin_movcon.php?Insert';
UPDATE `gaz_menu_script` SET `trans_code`='MOVMAG' WHERE `link`='admin_movmag.php?Insert';
UPDATE `gaz_menu_script` SET `trans_code`='DDTVEN' WHERE `link`='admin_docven.php?Insert&tipdoc=DDT';
UPDATE `gaz_menu_script` SET `trans_code`='FATVEN' WHERE `link`='admin_docven.php?Insert&tipdoc=FAI';
UPDATE `gaz_menu_script` SET `trans_code`='ORDVEN' WHERE `link`='admin_broven.php?Insert&tipdoc=VOR';
UPDATE `gaz_menu_script` SET `trans_code`='PREVEN' WHERE `link`='admin_broven.php?Insert&tipdoc=VPR';
UPDATE `gaz_menu_script` SET `trans_code`='ORDACQ' WHERE `link`='admin_broacq.php?tipdoc=AOR';
UPDATE `gaz_menu_script` SET `trans_code`='PREACQ' WHERE `link`='admin_broacq.php?tipdoc=APR';
ALTER TABLE `gaz_menu_script`	ADD COLUMN `status` BOOLEAN NULL DEFAULT (1) COMMENT '1=enabled, 0=disabled' AFTER `weight`;
ALTER TABLE `gaz_menu_module`	ADD COLUMN `status` BOOLEAN NULL DEFAULT (1) COMMENT '1=enabled, 0=disabled' AFTER `weight`;
CREATE TABLE IF NOT EXISTS `gaz_api_token` ( `id` INT NOT NULL AUTO_INCREMENT, `adminid` VARCHAR(20) NOT NULL,`nome` VARCHAR(60) NOT NULL DEFAULT '', `token_hash` CHAR(64) NOT NULL, `created` DATETIME NOT NULL, `expires` DATETIME NOT NULL, `revoked` TINYINT NOT NULL DEFAULT 0, `last_used` DATETIME DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `token_hash` (`token_hash`));
ALTER TABLE `gaz_breadcrumb` COMMENT='Tabella utilizzata dai widget della dashboard personalizzate dai singoli utenti (exec_mode=2), per la personalizzazione delle ricerche globali - global_search (exec_mode=5), per la memorizzazione delle voci di menù preferite ', ADD INDEX (`file`);
INSERT INTO `gaz_breadcrumb` (`exec_mode`,`codice_aziend`,`adminid`,`file`,`titolo`)
SELECT 5,a.codice,u.user_name,'codici','Funzioni rapide (codici del menu)'
FROM `gaz_aziend` a,`gaz_admin` u;
INSERT INTO `gaz_breadcrumb` (`exec_mode`,`codice_aziend`,`adminid`,`file`,`titolo`)
SELECT 5,a.codice,u.user_name,'pagine','Pagine del menu'
FROM `gaz_aziend` a,`gaz_admin` u;
INSERT INTO `gaz_breadcrumb` (`exec_mode`,`codice_aziend`,`adminid`,`file`,`titolo`)
SELECT 5,a.codice,u.user_name,'barcode_doc','Return Automatico'
FROM `gaz_aziend` a,`gaz_admin` u;
INSERT INTO `gaz_breadcrumb` (`exec_mode`,`codice_aziend`,`adminid`,`file`,`titolo`)
SELECT 5,a.codice,u.user_name,'articoli','Articoli'
FROM `gaz_aziend` a,`gaz_admin` u;
INSERT INTO `gaz_breadcrumb` (`exec_mode`,`codice_aziend`,`adminid`,`file`,`titolo`)
SELECT 5,a.codice,u.user_name,'clienti','Clienti'
FROM `gaz_aziend` a,`gaz_admin` u;
INSERT INTO `gaz_breadcrumb` (`exec_mode`,`codice_aziend`,`adminid`,`file`,`titolo`)
SELECT 5,a.codice,u.user_name,'fornitori','Fornitori'
FROM `gaz_aziend` a,`gaz_admin` u;
INSERT INTO `gaz_breadcrumb` (`exec_mode`,`codice_aziend`,`adminid`,`file`,`titolo`)
SELECT 5,a.codice,u.user_name,'produzioni','Produzioni/commesse'
FROM `gaz_aziend` a,`gaz_admin` u;
CREATE TABLE IF NOT EXISTS `gaz_menu_custom_data` (`id` int unsigned NOT NULL AUTO_INCREMENT, `id_menu_module` int unsigned NOT NULL, `id_menu_script` int unsigned NOT NULL COMMENT 'Se zero riferito ad un menù di livello 2 (module)', `link` varchar(255) NOT NULL DEFAULT '' COMMENT 'Valorizzandolo diventa prioritario rispetto a quello presente su menu_module o menu_script inoltre consente di distinguere la lingua', `label` varchar(100) NOT NULL DEFAULT '', `descri` varchar(1000) NOT NULL, `custom_field` text  COMMENT 'Riferimenti generici utilizzabili sui moduli. Normalmente in formato json: {"nome_modulo":{"nome_variabile":{"valore_variabile": {}}}}', `lang_id` int NOT NULL, PRIMARY KEY (`id`) USING BTREE, KEY `table_name_ref` (`id_menu_module`) USING BTREE, KEY `id_ref` (`id_menu_script`) USING BTREE, KEY `code_ref` (`link`) USING BTREE) ENGINE=MyISAM COMMENT='Tabella utilizzabile sia per le traduzioni delle voci menù che per la personalizzazione delle stesse. Prevale rispetto ai dati contenuti nei files menu.italian.php dei vari moduli.';
UPDATE `gaz_languages` SET `description`='Lingua' WHERE `lang_id`=1;
UPDATE `gaz_languages` SET `description`='Language' WHERE `lang_id`=2;
UPDATE `gaz_languages` SET `description`='Idioma' WHERE `lang_id`=3;
DELETE m2 FROM `gaz_menu_module` m2 LEFT JOIN `gaz_module` m1 ON m2.`id_module` = m1.id WHERE m1.id IS NULL;
DELETE m3 FROM `gaz_menu_script` m3 LEFT JOIN `gaz_menu_module` m2 ON m3.`id_menu` = m2.id WHERE m2.id IS NULL;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id_menu FROM `gaz_menu_script` WHERE `link`='genera_movmag.php'), 'editable_inventory.php', '', '', 17, '','', 10, 1 FROM `gaz_menu_script`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
SET @adminid_existsXXX = ( SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gaz_XXXrigbro' AND COLUMN_NAME = 'adminid');
SET @adminid_queryXXX = IF(@adminid_existsXXX = 0, "ALTER TABLE `gaz_XXXrigbro` ADD COLUMN `adminid` VARCHAR(20) NOT NULL AFTER `status`","SELECT 1");
PREPARE stmtaXXX FROM @adminid_queryXXX;
EXECUTE stmtaXXX;
DEALLOCATE PREPARE stmtaXXX;
SET @last_modified_existsXXX = ( SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gaz_XXXrigbro' AND COLUMN_NAME = 'last_modified');
SET @last_modified_queryXXX = IF(@last_modified_existsXXX = 0,  "ALTER TABLE `gaz_XXXrigbro` ADD COLUMN `last_modified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `adminid`","SELECT 1");
PREPARE stmtbXXX FROM @last_modified_queryXXX;
EXECUTE stmtbXXX;
DEALLOCATE PREPARE stmtbXXX;
ALTER TABLE `gaz_XXXcontract` ADD COLUMN `covered_ym` VARCHAR(7) NOT NULL DEFAULT '' COMMENT 'Ultimo mese pagato (AAAA-MM), salvato sul contratto anche senza fatture/ricevute collegate' AFTER `note`;
ALTER TABLE `gaz_XXXcontract` ADD COLUMN `invoice_descri` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Descrizione del primo rigo in fattura (vuoto = testo standard)' AFTER `covered_ym`;
ALTER TABLE `gaz_XXXassist` ADD COLUMN `id_tesdoc` INT NOT NULL DEFAULT 0 COMMENT 'Documento di vendita generato dall\'intervento' AFTER `note`;
ALTER TABLE `gaz_XXXaliiva`	CHANGE COLUMN `taxstamp` `taxstamp` INT NULL DEFAULT (0) AFTER `aliquo`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'C', 0.0,'N1', 'Art. 29 DL.10/2026 (esclusi)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'S', 0.0,'N2.1', 'Art. 15 DL.10/2026(FC x territorialita)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'S', 0.0,'N2.1', 'Art. 17 DL.10/2026(B2B extraterritoriali)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'S', 0.0,'N2.1', 'Art. 18 DL.10/2026(serv.imm.risto.trasp.)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'S', 0.0,'N2.1', 'Art. 19 DL.10/2026(serv.cult.art.sport.)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'S', 0.0,'N2.1', 'Art. 20 DL.10/2026(B2C serv.particolari)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'S', 0.0,'N2.1', 'Art. 22 DL.10/2026(B2C soggetti extra-UE)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'S', 0.0,'N2.2', 'Art.  5 DL.10/2026(Non rilev.beni)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'S', 0.0,'N2.2', 'Art. 10 DL.10/2026(Non rilev.servizi)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'S', 0.0,'N2.2', 'Art.157 DL.10/2026(Regime forfettario)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'N', 0.0,'N3.1', 'Art. 45 C.1 L.a 10/2026(Esport.diretta cede.)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'N', 0.0,'N3.1', 'Art. 45 C.1 L.b 10/2026(Esport.cura cession.)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'N', 0.0,'N3.2', 'Art. 39 DL.10/2026(Cess.intra beni)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'N', 0.0,'N3.2', 'Art. 40 DL.10/2026(Call-off stock intra)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'N', 0.0,'N3.3', 'Art. 50 DL.10/2026(Cess. San Marino)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'N', 0.0,'N3.4', 'Art. 48 DL.10/2026(Cess.assimil.esport.)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'N', 0.0,'N3.4', 'Art. 49 DL.10/2026(Serv.internazionali)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'N', 0.0,'N3.4', 'Art. 50 DL.10/2026(Cess. Vaticano)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'N', 0.0,'N3.5', 'Art. 45 C.1 L.d 10/2026(Cess.dichiar.intento)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'N', 0.0,'N3.6', 'Altre oper. non imponibili s/plafond' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'E', 0.0, 'N4',  'Art. 37 DL.10/2026(Operazioni esenti)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'M', 0.0, 'N5',  'Art.141 DL.10/2026(Reg.agenzie viaggio)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'M', 0.0, 'N5',  'Regime del margine beni usati TU' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R',22.0,'N6.1', 'Acquisti Art.139 cc.7-8 (Rott.mat.recup)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R', 0.0,'N6.1', 'Vendite Art.139 cc.7-8 (Rott.mat.recup)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R',22.0,'N6.2', 'Acquisti Art.64 c.5 (Oro ind. partic.oper)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R', 0.0,'N6.2', 'Vendite Art.139 cc.7-8 (Oro ind. partic.oper)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R',22.0,'N6.3', 'Acquisti Art.64 c.6 L.a (Subappalti edili)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R', 0.0,'N6.3', 'Vendite Art.64 c.6 L.a (Subappalti edili)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R',22.0,'N6.4', 'Acquisti Art.64 c.6 L.b (Cess.Fabbric.)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R', 0.0,'N6.4', 'Vendite Art.64 c.6 L.b (Cess.Fabbric.)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R',22.0,'N6.5', 'Acquisti Art.64 c.6 fat (Cess.telef.cell.)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R', 0.0,'N6.5', 'Vendite Art.64 c.6 fat (Cess.telef.cell.)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R',22.0,'N6.6', 'Acquisti Art.64 c.6 fat (Tablet lap CI)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R', 0.0,'N6.6', 'Vendite Art.64 c.6 fat (Tablet lap CI)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R',22.0,'N6.7', 'Acquisti Art.64 c.6 L.c (Lav. su edifici)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R', 0.0,'N6.7', 'Vendite Art.64 c.6 L.c (Lav. su edifici)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R',22.0,'N6.8', 'Acquisti Art.64 c.6 fat (Oper.sett.energ.)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R', 0.0,'N6.8', 'Vendite Art.64 c.6 fat (Oper.sett.energ.)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R',22.0,'N6.9', 'Acquisti Art.64 a/disp (Altri rev.charge)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `aliquo`, `fae_natura`, `descri`) SELECT MAX(codice)+1 , 'R', 0.0,'N6.9', 'Vendite Art.64 a/disp (Altri rev.charge)' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXcompany_config` (`lang_id`, `description`, `var`, `val`) SELECT * FROM (SELECT 1 AS `lang_id`, 'Ricerca cliente/fornitore separata: vendite solo clienti, acquisti solo fornitori (0=No, 1=Si)' AS `description`, 'ricerca_partner_separata' AS `var`, '0' AS `val`) AS nuova WHERE NOT EXISTS (SELECT 1 FROM `gaz_XXXcompany_config` WHERE `var` = 'ricerca_partner_separata');
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
