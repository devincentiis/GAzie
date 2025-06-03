<?php
/*
  --------------------------------------------------------------------------
  Copyright (C) - Antonio De Vincentiis, Montesilvano anno 2020
  tel.3383121161
  a.devincentiis@tiscali.it
  Montesilvano (PE)
  --------------------------------------------------------------------------
  
!!!ATTENZIONE!!! DEVONO ESSERE PRESENTI 2 FILES SULL'ECOMMERCE:
catalog/model/catalog/ocgazie.php 
catalog/controller/api/ocgazie.php

l'endpoin d'accesso per il login/token è:
http(s)://mydomanin/index.php?route=api/login

*/
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin(9);
if (isset($_POST['Submit'])) { // conferma tutto
	$gSync = new gazSynchro();
	ini_set('max_execution_time', '0');
	if(!$gSync->api_token){
		$exeprint='<h2 class="text-center">CONNESSIONE FALLITA!!!</h2>';
	}else{
		// DOPO AVER PRESO IL TOKEN:
		$gSync->SetupStore();
		$exeprint=$gSync->rawres;
	}
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new GAzieForm();
?>
<form method="post">
<div class="panel panel-default gaz-table-form">
 <div class="container-fluid text-center">
  <div><?php echo $exeprint;?></div>
  <div class="text-center col-sm-6"><button type="submit" class="btn btn-warning" name="Submit">Conferma</button></div>
 </div>
</div> 
</form>
<?php

require("../../library/include/footer.php");
?>