UPDATE `gaz_config` SET `cvalue` = '96' WHERE `id` =2;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='report_client.php'), 'select_sconti_articoli.php', '', '', 41, '', 7 FROM `gaz_menu_script`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='report_client.php'), 'select_sconti_raggruppamenti.php', '', '', 42, '', 8 FROM `gaz_menu_script`;
UPDATE `gaz_menu_script` SET `weight`=5 WHERE `link`='select_esportazione_articoli_venduti.php';
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='../magazz/report_statis.php'), 'select_analisi_fatturato_clienti.php', '', '', 43, '', 3  FROM `gaz_menu_script`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='../magazz/report_statis.php'), 'select_analisi_fatturato_cliente_fornitore.php', '', '', 44, '', 4  FROM `gaz_menu_script`;
INSERT INTO `gaz_menu_module` SELECT MAX(id)+1, (SELECT id FROM `gaz_module` WHERE `name`='suppor'), 'report_install.php', '', '', 3, '', 3  FROM `gaz_menu_module`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT MAX(id) FROM `gaz_menu_module`), 'admin_install.php?Insert', '', '', 3, '', 1  FROM `gaz_menu_script`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='select_liqiva.php'), 'select_spesometro_analitico.php', '', '', 8, '', 2  FROM `gaz_menu_script`;
INSERT INTO `gaz_config` (`id`, `description`, `variable`, `cvalue`, `weight`, `show`, `last_modified`) VALUES (NULL, 'Menu/header/footer personalizzabile', 'theme', 'g7', '0', '0', '2016-11-12 19:00:00');
CREATE TABLE IF NOT EXISTS `gaz_classroom` (  `id` INT NOT NULL AUTO_INCREMENT, `classe` VARCHAR(16) NOT NULL, `sezione` VARCHAR(16) NOT NULL, `anno_scolastico` INT NOT NULL, `teacher` VARCHAR(50) NOT NULL, `location` VARCHAR(100) NOT NULL, `title_note` VARCHAR(200) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM;
CREATE TABLE IF NOT EXISTS `gaz_students` (
 `student_id` INT NOT NULL AUTO_INCREMENT COMMENT 'auto incrementing student_id of each student, unique index',
 `student_classroom_id` INT NOT NULL COMMENT 'classroom_id of student',
 `student_firstname` VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL COMMENT 'student''s first name',
 `student_lastname` VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL COMMENT 'student''s last name',
 `student_name` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'student''s name, unique',
 `student_password_hash` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'student''s password in salted and hashed format',
 `student_email` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'student''s email, unique',
 `student_telephone` VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL COMMENT 'student''s telephone number',
 `student_active` TINYINT NOT NULL COMMENT 'student''s activation status',
 `student_activation_hash` VARCHAR(40) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'student''s email verification hash string',
 `student_password_reset_hash` CHAR(40) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'student''s password reset code',
 `student_password_reset_timestamp` bigint(20) DEFAULT NULL COMMENT 'timestamp of the password reset request',
 `student_rememberme_token` VARCHAR(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'student''s remember-me cookie token',
 `student_failed_logins` TINYINT NOT NULL COMMENT 'student''s failed login attemps',
 `student_last_failed_login` INT DEFAULT NULL COMMENT 'unix TIMESTAMP of last failed login attempt',
 `student_registration_datetime` datetime NOT NULL,
 `student_registration_ip` VARCHAR(39) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
 PRIMARY KEY (`student_id`),
 UNIQUE KEY `student_name` (`student_name`),
 UNIQUE KEY `student_email` (`student_email`)
) ENGINE=MyISAM AUTO_INCREMENT=1 COLLATE=utf8_unicode_ci COMMENT='student data';
ALTER TABLE `gaz_aziend` ADD COLUMN `capital_gains_account` INT NOT NULL AFTER `min_rate_deprec`;
ALTER TABLE `gaz_aziend` ADD COLUMN `capital_loss_account` INT NOT NULL AFTER `capital_gains_account`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
CREATE TABLE `gaz_XXXsconti_articoli` (`clfoco` INT,`codart` VARCHAR(15),`sconto` decimal(6,3),`prezzo_netto` decimal(14,5), primary key(`clfoco`,`codart`)) ENGINE=MyISAM;
CREATE TABLE `gaz_XXXsconti_raggruppamenti` (`clfoco` INT,`ragstat` CHAR(15),`sconto` decimal(6,3), primary key(`clfoco`,`ragstat`)) ENGINE=MyISAM;
ALTER TABLE `gaz_XXXassist` ADD `ripetizione` VARCHAR(10) COLLATE 'utf8_general_ci' NOT NULL AFTER `prezzo`;
ALTER TABLE `gaz_XXXassist` ADD `codart` VARCHAR(15) NOT NULL AFTER `ore`;
ALTER TABLE `gaz_XXXassist` change `ripetizione` `ripetizione` int NULL DEFAULT '1' AFTER `prezzo`, ADD `ogni` int NULL DEFAULT '365' AFTER `ripetizione`;
ALTER TABLE `gaz_XXXassist` ADD `codeart` VARCHAR(10) COLLATE 'utf8_general_ci' NULL AFTER `prezzo`;
ALTER TABLE `gaz_XXXassist` CHANGE `ogni` `ogni` VARCHAR(10) NULL DEFAULT 'Anni' AFTER `ripetizione`;
CREATE TABLE `gaz_XXXinstal` ( `id` INT NOT NULL, `clfoco` INT NOT NULL, `descrizione` VARCHAR(255) NOT NULL, `seriale` VARCHAR(255) NOT NULL, `datainst` DATE NOT NULL, `note` TEXT NOT NULL ) ENGINE=MyISAM;
ALTER TABLE `gaz_XXXinstal` ADD `codice` INT NOT NULL AFTER `id`;
ALTER TABLE `gaz_XXXinstal` ADD `oggetto` VARCHAR(100) NOT NULL AFTER `clfoco`;
ALTER TABLE `gaz_XXXassist` ADD `idinstallazione` INT NOT NULL AFTER `id`;
ALTER TABLE `gaz_XXXinstal` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
UPDATE `gaz_XXXcompany_config` SET `description`='GAzie school or order mail address' WHERE  `var`='order_mail';
ALTER TABLE `gaz_XXXassets`	CHANGE COLUMN `id_tes` `id_movcon` INT NOT NULL COMMENT 'può essere riferito a gaz_001tesmov in caso di acquisto bene o a gaz_001rigmoc in altri casi, ad esempio negli ammortamenti di fine anno' AFTER `id`;
ALTER TABLE `gaz_XXXassets` CHANGE COLUMN `type_mov` `type_mov` INT NOT NULL COMMENT 'tipologia di movimento sul libro cespiti es.1=acquisto, 10 rivalutazione, 50 ammortamento, 90 alienazione' AFTER `id_movcon`;
ALTER TABLE `gaz_XXXassets` CHANGE COLUMN `acc_no_detuct_cost` `acc_no_deduct_cost` INT NOT NULL AFTER `no_deduct_vat_rate`;
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`, `val`) VALUES ('Modo di selezione dei clienti/fornitori sui report delle fatture.<br>Dropbox=0, Ricerca testuale=1', 'partner_select_mode', '1');
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
