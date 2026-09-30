<?php
/*
  --------------------------------------------------------------------------
  Copyright (C) - Antonio De Vincentiis, Montesilvano anno 2020
  tel.3383121161
  a.devincentiis@tiscali.it
  Montesilvano (PE)
  --------------------------------------------------------------------------

l'endpoin d'accesso per il login/token è:
http(s)://mydomanin/index.php?route=api/login

*/
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin(9);
if (isset($_POST['Submit'])) { // conferma tutto
  $form['oc_api_url'] = filter_input(INPUT_POST, 'oc_api_url', FILTER_DEFAULT);
  $form['oc_api_username'] = filter_input(INPUT_POST, 'oc_api_username', FILTER_DEFAULT);
  $form['oc_api_key'] = filter_input(INPUT_POST, 'oc_api_key', FILTER_DEFAULT);
	gaz_dbi_put_row($gTables['company_data'], 'var', 'oc_api_url', 'data', $form['oc_api_url']);
	gaz_dbi_put_row($gTables['company_data'], 'var', 'oc_api_username', 'data', $form['oc_api_username']);
	gaz_dbi_put_row($gTables['company_data'], 'var', 'oc_api_key', 'data', $form['oc_api_key']);
  header("Location: ../root/admin.php");
	$restoken=false;
}elseif (isset($_POST['Test'])) { // test token
  $form['oc_api_url'] = filter_input(INPUT_POST, 'oc_api_url', FILTER_DEFAULT);
  $form['oc_api_username'] = filter_input(INPUT_POST, 'oc_api_username', FILTER_DEFAULT);
  $form['oc_api_key'] = filter_input(INPUT_POST, 'oc_api_key', FILTER_DEFAULT);
	$curl = curl_init($form['oc_api_url']);
	$post = array (
		'username' => $form['oc_api_username'],
		'key' => $form['oc_api_key']
	);
	curl_setopt_array( $curl, array(
		CURLOPT_RETURNTRANSFER=> TRUE,
		CURLOPT_POSTFIELDS      => $post
	));
	$rawres=curl_exec($curl);
	$restoken = json_decode($rawres);
	// valorizzo anche la SESSION così può essere usato il token per 40 minuti anche dalle funzioni in sync.opencarttre.php
	$_SESSION['api_token']=$restoken?$restoken->api_token:'';
	$_SESSION['api_url']=$form['oc_api_url'];
	curl_close($curl);
}else{ //se e' il primo accesso per UPDATE
  $form['oc_api_url'] = gaz_dbi_get_row($gTables['company_data'], 'var','oc_api_url')['data'];
  $form['oc_api_username'] = gaz_dbi_get_row($gTables['company_data'], 'var','oc_api_username')['data'];
  $form['oc_api_key'] = gaz_dbi_get_row($gTables['company_data'], 'var','oc_api_key')['data'];
	if (empty($form['oc_api_url'])){
		$form['oc_api_url']='http://localhost/oc30/index.php?route=api/login';
	}
	if (empty($form['oc_api_username'])){
		$form['oc_api_username']='Default';
	}
	if (empty($form['oc_api_key'])){
		$form['oc_api_key']='Get the value from System -> Users -> API and remember to authorize the IP address of the server where GAzie is hosted (localhost = ::1)
    Ottieni il valore da Sistema -> Utenti -> API e ricordati di autorizzare l\'indirizzo IP del server su cui è ospitato GAzie (localhost = ::1)';
	}
	$restoken=false;
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new opencarttreForm();

?>
<form method="post">
<div class="panel panel-default gaz-table-form">
 <div class="container-fluid text-center" id="alert_toast">
  <div class="form-group text-left">
    <label for="oc_api_url">URL per API login </label>
    <input type="text" class="form-control" name="oc_api_url" placeholder="Inserisci l'indirizzo del punto d'accesso all'API dell'ecommerce" value="<?php echo $form['oc_api_url'];?>">
  </div>
  <div class="form-group text-left">
    <label for="oc_api_username">Nome utente per token API ecommerce</label>
    <input type="text" class="form-control" name="oc_api_username" placeholder="Inserisci il nome utente per l'accesso all'API dell'ecommerce" value="<?php echo $form['oc_api_username'];?>">
  </div>
  <div class="form-group text-left">
    <label for="oc_api_key">Chiave per token API ecommerce</label>
    <textarea type="text" class="form-control" name="oc_api_key" placeholder="Inserisci la chiave per l'accesso all'API dell'ecommerce"><?php echo $form['oc_api_key'];?></textarea>
  </div>
  <div class="text-center text-bold col-xs-12">
<?php
if($restoken===false){
	$datares='';
} else if(is_array($restoken)&& count($restoken)==0){
	echo '<h3 class="text-danger bg-danger">AUTENTICAZIONE FALLITA!!!</h3>';
}else if(!isset($restoken->success)){
  echo '<h3 class="bg-danger text-danger">Nessun token: forse l\'endpoint è sbagliato '.var_dump($restoken).'</h3>';
}else{
  echo '<h3 class="bg-success text-success">';
  echo $restoken->success.'<br/>Token: '.$restoken->api_token;
  echo '</h3>';
}
?>
	</div>
  <div class="text-center col-sm-6"><button type="submit" class="btn btn-warning" name="Submit">Conferma</button></div><div class="text-center col-sm-6"><button type="submit" class="btn btn-info" name="Test">Test token</button></div>
 </div>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
