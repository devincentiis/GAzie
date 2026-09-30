UPDATE `gaz_config` SET `cvalue` = '143' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
UPDATE gaz_XXXpaymov t2 INNER JOIN gaz_XXXrigmoc t1 ON (t2.id_rigmoc_pay=t1.id_rig OR t2.id_rigmoc_doc=t1.id_rig)
SET t2.id_rigmoc_pay= IF ((t1.darave = 'A' AND t1.codcon<200000000),t2.id_rigmoc_pay,0),
t2.id_rigmoc_doc= IF ((t1.darave = 'D' AND t1.codcon<200000000),0,t2.id_rigmoc_doc) 
WHERE t2.id_rigmoc_pay>1 AND t2.id_rigmoc_doc>1;
UPDATE gaz_XXXpaymov t2 INNER JOIN gaz_XXXrigmoc t1 ON (t2.id_rigmoc_pay=t1.id_rig OR t2.id_rigmoc_doc=t1.id_rig)
SET t2.id_rigmoc_pay= IF ((t1.darave = 'D' AND t1.codcon>200000000),t2.id_rigmoc_pay,0),
t2.id_rigmoc_doc= IF ((t1.darave = 'A' AND t1.codcon>200000000),0,t2.id_rigmoc_doc) 
WHERE t2.id_rigmoc_pay>1 AND t2.id_rigmoc_doc>1;
UPDATE gaz_XXXpaymov SET id_tesdoc_ref = CONCAT(SUBSTRING(expiry,1,4),LPAD(CAST((id_rigmoc_pay + id_rigmoc_doc) AS CHAR) , 11, '0' )) WHERE id_tesdoc_ref LIKE '%new%';
ALTER TABLE `gaz_XXXcamp_mov_sian` ADD `varieta` VARCHAR(50) NOT NULL COMMENT 'Campo descrittivo della varietà da utilizzare per il campo note nel registro telematico oli SIAN' AFTER `stabil_dep`;
ALTER TABLE `gaz_XXXmovmag`	ADD INDEX (`datdoc`); 
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )