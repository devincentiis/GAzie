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

// inserisco i widget attualmente sviluppati sulla dashboard della home di tutti gli utenti 
$data=array(array('exec_mode'=>2,'file'=>'root/dash_company_widget.php','titolo'=>'Azienda','position_order'=>1),
			array('exec_mode'=>2,'file'=>'root/dash_user_widget.php','titolo'=>'Utente','position_order'=>2),
			array('exec_mode'=>2,'file'=>'root/dash_customer_schedule.php','titolo'=>'Scadenzario clienti','position_order'=>3),
			array('exec_mode'=>2,'file'=>'root/dash_supplier_schedule.php','titolo'=>'Scadenzario fornitori','position_order'=>4),
			array('exec_mode'=>2,'file'=>'magazz/dash_lot_expired.php','titolo'=>'Lotti scaduti','position_order'=>5),
			array('exec_mode'=>2,'file'=>'magazz/dash_lot_expiring.php','titolo'=>'Lotti in scadenza','position_order'=>6),
			array('exec_mode'=>2,'file'=>'root/dash_numclick_widget.php','titolo'=>'Più utilizzati','position_order'=>7),
			array('exec_mode'=>2,'file'=>'root/dash_lastclick_widget.php','titolo'=>'Ultimi utilizzati','position_order'=>8)
			);
$get_users=gaz_dbi_dyn_query("*", $table_prefix . "_admin","1");
$gTables['breadcrumb']=$table_prefix . "_breadcrumb";
while($rus=gaz_dbi_fetch_array($get_users)){
	foreach($data as $v){
		$v['adminid']=$rus["user_name"];
		gaz_dbi_table_insert('breadcrumb',$v);
	}
	echo "<p>Ho creato una <b>dashboard personalizzabile</b> per l'utente <b>".$v['adminid']."</b></p>";
}
?>
