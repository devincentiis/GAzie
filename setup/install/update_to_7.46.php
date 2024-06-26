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

$result = gaz_dbi_dyn_query("*", $table_prefix.'_admin', 1);
while ($row = gaz_dbi_fetch_array($result)) {
	gaz_dbi_query("INSERT INTO ". $table_prefix . "_anagra SET ragso1='" . addslashes($row['user_firstname'])."', ragso2='" . addslashes($row['user_lastname'])."', legrap_pf_nome='" . addslashes($row['user_firstname'])."', legrap_pf_cognome='" . addslashes($row['user_lastname'])."', e_mail='" . addslashes($row['user_email'])."', telefo='" . addslashes($row['user_telephone'])."'");		
    $id_anagra=gaz_dbi_last_id();
    gaz_dbi_put_row($table_prefix . "_admin",'user_id',$row['user_id'],'id_anagra',$id_anagra);
    echo "<p>Il nome e cognome dell'utente <b>".$row['user_name']."</b> adesso è presente anche sul'archivio delle anagrafiche comuni (".$table_prefix."_anagra)</p>";
}

$result = gaz_dbi_dyn_query("*", $table_prefix.'_aziend', 1);
while ($row = gaz_dbi_fetch_array($result)) {
	$aziend_codice = sprintf("%03s", $row["codice"]);
	// inizio controlli presenza di indici altrimenti li creo 
	$idx=array(0=>array('codart'=>'rigbro','tiprig'=>'rigbro','id_doc'=>'rigbro','id_mag'=>'rigbro','id_contract'=>'tesbro','id_con'=>'tesbro','id_orderman'=>'tesbro','filename_ori'=>'fae_flux','filename_zip_package'=>'fae_flux','id_tes_ref'=>'fae_flux','filename_son'=>'fae_flux','id_SDI'=>'fae_flux','flux_status'=>'fae_flux','mail_id'=>'fae_flux','exec_date'=>'fae_flux','received_date'=>'fae_flux','delivery_date'=>'fae_flux','operat'=>'movmag','id_lotmag'=>'movmag','id_assets'=>'movmag','numfat'=>'tesdoc','datfat'=>'tesdoc')); 
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
	gaz_dbi_query("INSERT INTO ". $table_prefix . "_". $aziend_codice."files SET table_name_ref='effett', item_ref='distinta', title='Distinta fittizia per retrocompatibilità 7.46'");		
    $id_doc=gaz_dbi_last_id();
	gaz_dbi_query("UPDATE ". $table_prefix . "_" . $aziend_codice."effett SET id_distinta=".$id_doc." WHERE status='DISTINTATO'");		
    echo "<p>Azienda n. ".$row["codice"]." creata una distinta fittizia per gli effetti con status=DISTINTATO</p>";
}
?>