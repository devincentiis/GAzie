<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
	  --------------------------------------------------------------------------
	  Questo programma e` free software;   e` lecito redistribuirlo  e/o
	  modificarlo secondo i  termini della Licenza Pubblica Generica GNU
	  come e` pubblicata dalla Free Software Foundation; o la versione 2
	  della licenza o (a propria scelta) una versione successiva.

	  Questo programma  e` distribuito nella speranza  che sia utile, ma
	  SENZA   ALCUNA GARANZIA; senza  neppure  la  garanzia implicita di
	  NEGOZIABILITA` o di  APPLICABILITA` PER UN  PARTICOLARE SCOPO.  Si
	  veda la Licenza Pubblica Generica GNU per avere maggiori dettagli.

	  Ognuno dovrebbe avere   ricevuto una copia  della Licenza Pubblica
	  Generica GNU insieme a   questo programma; in caso  contrario,  si
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
$menu_data = array( 'm1'=>array('link'=>"docume_camp.php"),
                    'm2'=>array(1=>array('link'=>"camp_report_artico.php",'weight'=>1),
								2=>array('link'=>"camp_report_catmer.php",'weight'=>2),
								3=>array('link'=>"camp_report_movmag.php",'weight'=>3),
								4=>array('link'=>"camp_report_caumag.php",'weight'=>4),
								5=>array('link'=>"report_campi.php",'weight'=>5),
								6=>array('link'=>"fitofarmaci.php",'weight'=>6),
								7=>array('link'=>"sian.php",'weight'=>7)
                               ),
                    'm3'=>array('m2'=>array(1=>array(
                                                    array('translate_key'=>1,'link'=>"camp_admin_artico.php?Insert",'weight'=>1),
													array('translate_key'=>2,'link'=>"camp_inventory_stock.php",'weight'=>5),
													array('translate_key'=>3,'link'=>"camp_stampa_invmag.php",'weight'=>10),
													array('translate_key'=>16,'link'=>"camp_browse_document.php",'weight'=>11)
                                                    ),
											2=>array(
                                                    array('translate_key'=>4,'link'=>"camp_admin_catmer.php?Insert",'weight'=>1)
                                                    ),
											3=>array(
                                                    array('translate_key'=>5,'link'=>"camp_admin_movmag.php?Insert",'weight'=>1),
                                                    array('translate_key'=>6,'link'=>"camp_select_schart.php",'weight'=>5),
                                                    array('translate_key'=>7,'link'=>"camp_select_giomag.php",'weight'=>10),
													array('translate_key'=>12,'link'=>"calc_prod.php",'weight'=>15)
                                                    ),
											4=>array(
                                                    array('translate_key'=>8,'link'=>"camp_admin_caumag.php?Insert",'weight'=>1)
                                                    ),
											5=>array(
                                                    array('translate_key'=>9,'link'=>"admin_campi.php?Insert",'weight'=>1),
                                                    array('translate_key'=>10,'link'=>"select_dichiar_rame.php",'weight'=>5)
                                                    ),
											6=>array(
                                                    array('translate_key'=>13,'link'=>"admin_avv.php",'weight'=>1),
                                                    array('translate_key'=>14,'link'=>"admin_colt.php",'weight'=>5),
													array('translate_key'=>15,'link'=>"report_fitofarmaci.php",'weight'=>10),
													array('translate_key'=>11,'link'=>"update_fitofarmaci.php",'weight'=>15)
                                                    ),
											7=>array(
                                                    array('translate_key'=>17,'link'=>"rec_stocc.php",'weight'=>1),
													array('translate_key'=>18,'link'=>"stabilim.php",'weight'=>5),
													array('translate_key'=>19,'link'=>"admin_sian_files.php",'weight'=>10),
													array('translate_key'=>20,'link'=>"camp_anagra.php",'weight'=>15)
                                                    )
                                            )
                               )
                  );
$module_class='fas fa-seedling';
$update_db[]="ALTER TABLE ".$table_prefix."_camp_fitofarmaci ADD INDEX(`PRODOTTO`);";
$update_db[]="ALTER TABLE `".$table_prefix."_camp_uso_fitofarmaci` CHANGE `dose` `dose` DECIMAL(8,3) NOT NULL COMMENT 'unità di misura / ha'; ";
$update_db[]="ALTER TABLE `".$table_prefix."_camp_uso_fitofarmaci` ADD `dose_hl` DECIMAL(8,3) NOT NULL COMMENT 'unità di misura / hl' AFTER `dose`; ";
?>
