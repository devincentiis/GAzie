<?php

if (isset($_SESSION['table_prefix'])) {

   $table_prefix = substr($_SESSION['table_prefix'],0,12);

} elseif (isset($_POST['tp'])) {

	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {

		$table_prefix = filter_var(substr($_POST['tp'],0,12),FILTER_SANITIZE_ADD_SLASHES);

	} else if ( defined('FILTER_SANITIZE_MAGIC_QUOTES') ) {

		$table_prefix = filter_var(substr($_POST['tp'],0,12),FILTER_SANITIZE_MAGIC_QUOTES);

	} else {

		$table_prefix = addslashes(substr($_POST['tp'],0,12));

	}

} elseif(isset($_GET['tp'])) {

	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {

		$table_prefix = filter_var(substr($_GET['tp'],0,12),FILTER_SANITIZE_ADD_SLASHES);

	} else if ( defined('FILTER_SANITIZE_MAGIC_QUOTES') ) {

		$table_prefix = filter_var(substr($_GET['tp'],0,12),FILTER_SANITIZE_MAGIC_QUOTES);

	} else {

		$table_prefix = addslashes(substr($_GET['tp'],0,12));

	}

} else {

	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {

		$table_prefix = filter_var(substr($table_prefix,0,12),FILTER_SANITIZE_ADD_SLASHES);

	} else if ( defined('FILTER_SANITIZE_MAGIC_QUOTES') ) {

		$table_prefix = filter_var(substr($table_prefix,0,12),FILTER_SANITIZE_MAGIC_QUOTES);

	} else {

		$table_prefix = addslashes(substr($table_prefix,0,12));

	}

}

// se ho il modulo "camp" attivo allora aggiungo una voce al menù
$camp_mod = gaz_dbi_get_row($table_prefix.'_module','name', 'camp');
if ($camp_mod){
	gaz_dbi_query("INSERT INTO `gaz_menu_script` SELECT MAX(id)+1 , (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='report_movmag.php' AND `id_module`> 6), 'calc_prod.php', '', '', 12, '', 15  FROM `gaz_menu_script`");
	// aggiungo una voce al menù_module (2°livello)
	gaz_dbi_query("INSERT INTO `gaz_menu_module` SELECT MAX(id)+1 , ".$camp_mod['id'].", 'fitofarmaci.php', '', '', 6, '', 6  FROM `gaz_menu_module`");
	// cancello dal 3° livello il link spostato sul menù di 2° livello
	gaz_dbi_query("DELETE FROM `gaz_menu_script` WHERE `link` = 'update_fitofarmaci.php'");
	// aggiungo le nuove voci di menù di 3° livello
	gaz_dbi_query("INSERT INTO `gaz_menu_script` SELECT MAX(id)+1 , (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='fitofarmaci.php'), 'admin_avv.php', '', '', 13, '', 1  FROM `gaz_menu_script`");
	gaz_dbi_query("INSERT INTO `gaz_menu_script` SELECT MAX(id)+1 , (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='fitofarmaci.php'), 'admin_colt.php', '', '', 14, '', 5  FROM `gaz_menu_script`");
	gaz_dbi_query("INSERT INTO `gaz_menu_script` SELECT MAX(id)+1 , (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='fitofarmaci.php'), 'report_fitofarmaci.php', '', '', 15, '', 10  FROM `gaz_menu_script`");
	gaz_dbi_query("INSERT INTO `gaz_menu_script` SELECT MAX(id)+1 , (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='fitofarmaci.php'), 'update_fitofarmaci.php', '', '', 11, '', 15  FROM `gaz_menu_script`");
	gaz_dbi_query("INSERT INTO `gaz_menu_script` SELECT MAX(id)+1 , (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='fitofarmaci.php'), 'update_fitofarmaci.php', '', '', 16, '', 20  FROM `gaz_menu_script`");
	echo "<p>Ho modificato il menù del modulo <b>Registro di campagna</b></p>";
}

// se ho il modulo "orderman" attivo allora aggiungo due voci di menù
$orderman_mod = gaz_dbi_get_row($table_prefix.'_module','name', 'orderman');
if($orderman_mod){
	gaz_dbi_query("INSERT INTO `gaz_menu_script` SELECT MAX(id)+1 , (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='orderman_report.php'), 'report_luoghi.php', '', '', 2, '', 5  FROM `gaz_menu_script`");
	gaz_dbi_query("INSERT INTO `gaz_menu_script` SELECT MAX(id)+1 , (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='fitofarmaci.php'), 'admin_luoghi.php?Insert', '', '', 3, '', 10  FROM `gaz_menu_script`");
}

$limit="99999999";
$passo="99999999";
$nu=0;
$result = gaz_dbi_dyn_query("*", $table_prefix.'_aziend', 1);

while ($row = gaz_dbi_fetch_array($result)) {
	$aziend_codice = sprintf("%03s", $row["codice"]);
	
	// inizio controlli presenza di alcuni indici altrimenti li creo perché senza di essi diverse query ricorsive sarebbero troppo lente in caso di tabelle con molti righi
	$idx=array(	0=>array('id_doc'=>'effett'),
				1=>array('id_tes'=>'rigdoc'),
				2=>array('tipdoc'=>'tesdoc'),
				3=>array('datfat'=>'tesdoc'),
				4=>array('numfat'=>'tesdoc'),
				5=>array('id_tes'=>'rigmoc'),
				6=>array('expiry'=>'paymov'),
				7=>array('id_tes'=>'rigmoi'),
				8=>array('id_order'=>'rigdoc'));
	foreach($idx as $vi){
		foreach($vi as $k=>$v){
			$rk=gaz_dbi_query("SHOW KEYS FROM ". $table_prefix . "_" . $aziend_codice.$v." WHERE 1");
			$ex=false;	
			while ($vk = gaz_dbi_fetch_array($rk)) {
				if ($vk['Column_name'] == $k){
					$ex=true;
				}
			}
			if (!$ex){
				gaz_dbi_query("ALTER TABLE ". $table_prefix . "_" . $aziend_codice.$v." ADD INDEX `".$k."` (`".$k."`)");		
				echo "<p>Ho creato l'index <b>".$k."</b> su ". $table_prefix . "_" . $aziend_codice.$v." perché non esisteva</p>";
			}
		}
	}
	// fine controlli - creazioni indici mancanti

	/* CONVERSIONE VECCHI ORDINI CON I NUOVI EVADIBILI PARZIALMENTE */
	$rtesbro = gaz_dbi_dyn_query("*", $table_prefix . "_" . $aziend_codice."tesbro", "tipdoc='VOR'", "id_tes DESC"); 
	while ($rtb = gaz_dbi_fetch_array($rtesbro)) {
		$rrigbro = gaz_dbi_dyn_query("*", $table_prefix . "_" . $aziend_codice."rigbro", "id_tes=".$rtb["id_tes"], "id_rig DESC");
		while ($rrb = gaz_dbi_fetch_array($rrigbro)) {
			if ( $rrb["id_doc"]!=0 ) {
				$nu++;
				$res = gaz_dbi_query("UPDATE ".$table_prefix . "_" . $aziend_codice."rigdoc set id_order=".$rtb["id_tes"]." WHERE id_tes=".$rrb["id_doc"]." and codart='".$rrb["codart"]."';");
				if ( !$res ) {
					echo "errore UPDATE ".$table_prefix . "_" . $aziend_codice."rigdoc set id_order=".$rtb["id_tes"]." WHERE id_tes=".$rrb["id_doc"]." and codart='".$rrb["codart"]."';<br>";
				}
			}
		}
	}
}
echo "<p>Ho convertito <b>".$nu." righi di ordini</b> nella nuova versione evadibili parzialmente</p>";

?>
