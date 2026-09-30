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

function tryBase64Decode($s) {
	// Check if there are valid base64 characters
	if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s)) {
		// Decode the string in strict mode and check the results
		try {
			$decoded = base64_decode($s, true);
			if ($decoded !== false) {
				// Encode the string again
				if(base64_encode($decoded) == $s) {
                	return $decoded;
                } else {
					error_log('Charset non gestito in tryBase64Decode ' . print_r($decoded, true), 0);
                	return $decoded;
                }
			}
		} catch (Exception $ex) {
			//$ex->getMessage();
		}
	}
	return $s;
}

$result = gaz_dbi_dyn_query("*", $table_prefix.'_aziend', 1);
while ($row = gaz_dbi_fetch_array($result)) {
	$aziend_codice = sprintf("%03s", $row["codice"]);
    $tesdoc = $table_prefix . "_" . $aziend_codice.'tesdoc'; // nome tabella di ogni azienda attivata
    $res=gaz_dbi_query("SELECT id_tes FROM ".$tesdoc." WHERE tipdoc LIKE 'AF_' AND ( fattura_elettronica_original_content IS NOT NULL OR fattura_elettronica_original_content <> '')");
    while ($r = gaz_dbi_fetch_array($res)) {
        // spostamente campi BLOB su files
        $filecontent = gaz_dbi_get_row($tesdoc, 'id_tes', $r['id_tes']); // non posso richiamarli assieme al rigo 62 perché andrei in out of memory 
        $fn = DATA_DIR . 'files/' . $row["codice"] . '/'.$r['id_tes'].'.inv';
        file_put_contents ( $fn, tryBase64Decode($filecontent['fattura_elettronica_original_content'])); 
    }
    // ho testato tutto ma il drop della colonna, ma non si sa mai ... ve lo lascio fare manualmente, magari in un secondo tempo 
    //gaz_dbi_query("ALTER TABLE `".$table_prefix . "_" . $aziend_codice.'tesdoc'."`	DROP COLUMN `fattura_elettronica_original_content`");
}
?>
