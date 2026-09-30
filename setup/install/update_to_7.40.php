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

$idx=array(0=>array('id_province'=>'municipalities','name'=>'municipalities','postal_code'=>'municipalities')); 
foreach($idx[0] as $k=>$v){
	$rk=gaz_dbi_query("SHOW KEYS FROM ". $table_prefix . "_" .$v." WHERE 1");
	$ex=false;	
	while ($vk = gaz_dbi_fetch_array($rk)) {
		if ($vk['Column_name'] == $k){
			$ex=true;
		}
	}
	if (!$ex){
		gaz_dbi_query("ALTER TABLE ". $table_prefix . "_" .$v." ADD INDEX `".$k."` (`".$k."`)");		
		echo "<p>Ho creato l'index <b>".$k."</b> su ". $table_prefix . "_" .$v." perché non esisteva</p>";
	}
}
?>
