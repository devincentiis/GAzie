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

$result = gaz_dbi_dyn_query("*", $table_prefix.'_aziend', 1);

while ($row = gaz_dbi_fetch_array($result)) {
	$aziend_codice = sprintf("%03s", $row["codice"]);
	// inizio controlli presenza di indici altrimenti li creo 
	$idx=array(0=>array('tipiva'=>'aliiva','aliquo'=>'aliiva','id_tes'=>'rigdoc','id_order'=>'rigdoc','id_mag'=>'rigdoc','id_doc'=>'effett',
    'fattura_elettronica_zip_package'=>'tesdoc','var'=>'company_config','val'=>'company_config','description'=>'company_config',
    'codart'=>'lotmag','id_movmag'=>'lotmag','datreg'=>'movmag','artico'=>'movmag','expiry'=>'paymov','id_contract'=>'contract_row',
    'id_staff'=>'staff_skills','ragstat'=>'sconti_raggruppamenti','clfoco'=>'sconti_articoli')); 
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
}
$result = gaz_dbi_dyn_query("*", $table_prefix.'_aziend', 1);
while ($row = gaz_dbi_fetch_array($result)) {
	$aziend_codice = sprintf("%03s", $row["codice"]);
	// inizio controlli presenza di indici altrimenti li creo 
	$idx=array(0=>array('codart'=>'sconti_articoli','clfoco'=>'sconti_raggruppamenti','codice'=>'camp_artico',
    'id_movmag'=>'camp_mov_sian','ddt_type'=>'tesdoc','id_contract'=>'tesdoc','id_con'=>'tesdoc','reverse_charge_idtes'=>'rigmoi')); 
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
}
?>
