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
	// aggiungo una voce al menù_module (2°livello)
	gaz_dbi_query("INSERT INTO `". $table_prefix . "_menu_module` SELECT MAX(id)+1 , ".$camp_mod['id'].", 'sian.php', '', '', 7, '', 7  FROM `". $table_prefix . "_menu_module`");
	// aggiungo le nuove voci di menù di 3° livello
	gaz_dbi_query("INSERT INTO `". $table_prefix . "_menu_script` SELECT MAX(id)+1 , (SELECT MIN(id) FROM `". $table_prefix . "_menu_module` WHERE `link`='sian.php'), 'rec_stocc.php', '', '', 17, '', 1  FROM `". $table_prefix . "_menu_script`");
	gaz_dbi_query("INSERT INTO `". $table_prefix . "_menu_script` SELECT MAX(id)+1 , (SELECT MIN(id) FROM `". $table_prefix . "_menu_module` WHERE `link`='sian.php'), 'admin_sian_files.php', '', '', 19, '', 5  FROM `". $table_prefix . "_menu_script`");
	gaz_dbi_query("INSERT INTO `". $table_prefix . "_menu_script` SELECT MAX(id)+1 , (SELECT MIN(id) FROM `". $table_prefix . "_menu_module` WHERE `link`='sian.php'), 'stabilim.php', '', '', 18, '', 10  FROM `". $table_prefix . "_menu_script`");
	gaz_dbi_query("INSERT INTO `". $table_prefix . "_menu_script` SELECT MAX(id)+1 , (SELECT MIN(id) FROM `". $table_prefix . "_menu_module` WHERE `link`='sian.php'), 'camp_anagra.php', '', '', 20, '', 15  FROM `". $table_prefix . "_menu_script`");
	echo "<p>Ho modificato il menù del modulo <b>Registro di campagna</b></p>";
}

$result = gaz_dbi_dyn_query("*", $table_prefix.'_aziend', 1);
while ($row = gaz_dbi_fetch_array($result)) {
	$aziend_codice = sprintf("%03s", $row["codice"]);
	// inizio controlli presenza di indici, ne ricontrollo molti in quanto in passato un bug in fase di creazione nuova azienda li perdeva compromettendo gravemente la performance di molte query 
	$idx=array(0=>array('ref'=>'company_data','var'=>'company_data','description'=>'company_data','tipdoc(1)'=>'tesdoc','sezivaXXX1'=>'tesdoc','protoc'=>'tesdoc','id_agenteXXX1'=>'agenti','id_agenteXXX2'=>'agenti_forn','id_fornitore'=>'agenti_forn','codiceXXX1'=>'aliiva','codiceXXX2'=>'caucon','caucon_cod'=>'caucon_rows','codiceXXX3'=>'caumag','codiceXXX4'=>'clfoco','codiceXXX5'=>'imball','id_rigbro'=>'orderman','codiceXXX6'=>'pagame','codiceXXX7'=>'portos','codiceXXX8'=>'spediz','id_staff'=>'staff_worked_hours','sezivaXXX2'=>'tesbro','datemi'=>'tesbro')); 
	foreach($idx as $vi){
		foreach($vi as $k=>$v){
			$matches=false;
			$retK=preg_match('/([a-zA-Z_]+)XXX[0-9]{1,2}/',$k,$matches); // sulle colonne con lo stesso nome dovrò accodare (e poi strippare) 'XXXN' per evitare di la ridefinizione della stessa 
			$k=($matches)?$matches[1]:$k;
			$rk=gaz_dbi_query("SHOW KEYS FROM ". $table_prefix . "_" . $aziend_codice.$v." WHERE 1");
			$ex=false;	
			while ($vk = gaz_dbi_fetch_array($rk)) {
				if ($vk['Column_name']==$k){
					$ex=true;
				}
			}
			if (!$ex){
				$matches = null;
				$retV=preg_match('/([a-zA-Z_]+)\\(([0-9]+)\\)/',$k,$matches); // quando definisco un indice di lunghezza inferiore a quella della colonna stessa
				$nam_idx=$k;
				$nam_col=$k;
				$idx_len='';
				if ($matches){
				  $nam_idx=$matches[1].$matches[2];
				  $nam_col=$matches[1];
				  $idx_len='('.$matches[2].')';
				}
				gaz_dbi_query("ALTER TABLE ". $table_prefix . "_" . $aziend_codice.$v." ADD INDEX `idx_".$nam_idx."` (`".$nam_col."`".$idx_len.") USING BTREE");					
				echo "<p>Ho creato l'index '".$nam_idx."' su <b>".$nam_col."</b> della tabella ". $table_prefix . "_" . $aziend_codice.$v." perché non esisteva</p>";
			}
		}
	}
	// fine controlli - creazioni indici mancanti
	
	// sposto il set delle icone di default su ogni azienda della installazione
	$dst="../../data/files/" . $row['codice'];
	$src="../../library/images/default";
	$dir = opendir($src);
	while(false !== ( $file = readdir($dir)) ) { 
		if (( $file != '.' ) && ( $file != '..' )) { 
			if ( is_dir($src . '/' . $file) ) { 
				// recurse_copy($src . '/' . $file,$dst . '/' . $file); 
			} else { 
				copy($src . '/' . $file,$dst . '/' . $file); 
			} 
		} 
	} 
	closedir($dir); 
}
?>
