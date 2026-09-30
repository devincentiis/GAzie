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

require_once('../../modules/root/config_login.php');
$hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);

$result = gaz_dbi_dyn_query("*", $table_prefix.'_admin', 1);
while ($row = gaz_dbi_fetch_array($result)) {
	$user_password_hash = password_hash($row['Password'], PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));
	gaz_dbi_put_row($table_prefix.'_admin', 'user_id', $row['user_id'], 'user_password_hash', $user_password_hash);		
	echo "Ho eseguito hash alla password dell'utente ".$row['user_name'].'<br>';
}
$result=gaz_dbi_query("ALTER TABLE ". $table_prefix."_admin DROP COLUMN Password");
?>
