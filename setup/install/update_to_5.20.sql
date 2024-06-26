UPDATE `gaz_config` SET `cvalue` = '78' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
RENAME TABLE `gaz_XXXconfig` TO `gaz_XXXcompany_config` ;
INSERT INTO `gaz_XXXcompany_config` SELECT MAX(id)+1, 'SMTP Mail Server', 'smtp_server', 'localhost'  FROM `gaz_XXXcompany_config`;
INSERT INTO `gaz_XXXbody_text` SELECT MAX(id_body)+1,'body_send_doc_email', '0', '<h3><span style="color: #000000;">La presente e-mail per trasmetterVi i documenti che troverete in allegato.</span></h3>','' FROM `gaz_XXXbody_text`;
INSERT INTO `gaz_XXXcompany_config` SELECT MAX(id)+1, 'Mail Notification Request', 'return_notification', 'yes'  FROM `gaz_XXXcompany_config`;
-- STOP_WHILE( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query seguenti su tutte le aziende dell'installazione)
