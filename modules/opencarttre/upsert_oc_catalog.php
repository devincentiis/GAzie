<?php
/*
  --------------------------------------------------------------------------
  Copyright (C) - Antonio De Vincentiis, Montesilvano anno 2020
  tel.3383121161
  a.devincentiis@tiscali.it
  Montesilvano (PE)
  --------------------------------------------------------------------------

*/
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin(9);
if (isset($_POST['Submit'])) { // conferma tutto
	$acccat=[];
	$accpro=[];
	$gSync = new opencarttregazSynchro();
	ini_set('max_execution_time', '0');
  //var_dump($gSync);
	// aggiorno le categorie merceologiche (category)
	$urlcategory=str_replace('api/login','extension/module/ocgazie/upsertCategory&store_id=0',$gSync->oc_api_url);
	$curl = curl_init($urlcategory);
	$res=gaz_dbi_dyn_query('codice, descri, HEX(image) AS heximage,top, annota', $gTables['catmer'], 1);
	while ($r=gaz_dbi_fetch_array($res)) {
		$fields = [
				'category_id' => $r['codice'],
				'data'=>['descri'=>$r['descri'],'image'=>$r['heximage'],'top'=>$r['top'],'annota'=>$r['annota']]
				];
		$post = http_build_query($fields);
		curl_setopt_array( $curl,[ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_COOKIE => 'OCSESSID='.$gSync->api_token]);
		$raw_response = curl_exec( $curl );
    //var_dump($raw_response);
		$acccat[]= json_decode(json_decode($raw_response));
	}
	curl_close($curl);

	// aggiorno gli articoli (product)
	$urlproduct=str_replace('api/login','extension/module/ocgazie/upsertProduct&store_id=0',$gSync->oc_api_url);
	$curl = curl_init($urlproduct);
	require("../magazz/lib.function.php");
	$gForm = new magazzForm();
	$res=gaz_dbi_dyn_query('codice, descri, HEX(image) AS heximage,ref_ecommerce_id_product,preve1,web_price,web_multiplier,web_public,catmer,peso_specifico,lunghezza,larghezza,spessore,scorta,clfoco,aliiva,annota', $gTables['artico'], 1);
	while ($r=gaz_dbi_fetch_array($res)){
	  $bodytext = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", 'artico_' . $r['codice']);
    $description = ($bodytext)? $bodytext['body_text']: ' ';
	  $mv = $gForm->getStockValue(false, $r['codice']);
		$magval = array_pop($mv);
    $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
		if (isset($magval['q_g']) && round($magval['q_g'],6) == "-0"){
			$magval['q_g']=0;
		}
		$quantity=(is_numeric($magval))?0:intval($magval['q_g']);
		if ($quantity < 0) { // attribuisco lo stato in base alla giacenza e alla scorta minima indicata sul gestionale
			$quantity=0;
			$stock_status = '3';
		} elseif ($quantity > 0) { //
			$stock_status = '1';
			if ($quantity<=$r['scorta']){
				$stock_status = '2';
			}
		} else { // giacenza = 0
			$stock_status = '3';
		}
		if($admin_aziend['conmag'] <= 1){ // se non gestisco la contabilità di magazzino ci indico solo la scorta e metto sempre disponibile
			$stock_status = '1';
			$quantity=intval($r['scorta']);
		}
		if ($r['web_price']>=0.00001){// in archivio ho valorizzato il prezzo web altrimenti uso quello normale listino 1
			$r['preve1']=round($r['web_price']*$r['web_multiplier'],4);
		}
		$fields = array (
			'product_id' => intval($r['ref_ecommerce_id_product']),
			'data'=>array(	'name'=>$r['descri'],
      'model'=>$r['codice'],
      'quantity'=>$quantity,
      'description'=>$description,
      'image'=>$r['heximage'],
      'price'=>floatval($r['preve1']),
      'category_id'=>intval($r['catmer']),
      'weight'=>floatval($r['peso_specifico']),
      'length'=>floatval($r['lunghezza']),
      'width'=>floatval($r['larghezza']),
      'height'=>floatval($r['spessore']),
      'tax_class_id'=>intval($r['aliiva']),
      'manufacturer_id'=>intval($r['clfoco']),
      'meta_keyword'=>$r['annota'].' ',
      'stock_status_id'=>$stock_status,
      'status'=>intval($r['web_public']))
			);
		$post = http_build_query($fields);
		curl_setopt_array( $curl,[ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_COOKIE => 'OCSESSID='.$gSync->api_token]);
		$raw_response = curl_exec( $curl );
		$accpro[]= json_decode( json_decode($raw_response));
	}
	curl_close($curl);
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new GAzieForm();
?>
<form method="post">
<div class="panel panel-default panel-body panel-help">
	<p>Confermando si aggiornerà tutto il catalogo presente sullo store online prendendo i dati presenti sul gestionale, ed in particolare quanto contenuto sugli archivi delle <strong>Categorie merceologiche</strong> e delle
	<strong>Merci e servizi</strong> del modulo Magazzino.</p>
	<p>Questa procedura è normalmente da usare solo al momento della installazione delle infrastrutture, ovvero quando per qualche motivo tecnico di collegamento il gestionale non ha provveduto ad eseguire in backgroud le operazione di sincronizzazione in <strong>tempo reale.</strong></p>
</div>
<?php
if (isset($_POST['Submit'])) {
?>
<div class="panel panel-default gaz-table-form">
	<div class="container-fluid text-center">
		<div class="table-responsive">
			<table class="table table-sm">
			<thead>
				<tr>
					<th>CATEGORIE MERCEOLOGICHE
					</th>
					<th>Descrizione
					</th>
					<th>su Home Page
					</th>
					<th>
					</th>
				</tr>
			</thead>
			<tbody>
<?php
foreach($acccat as $v){

?>
				<tr>
					<td class="text-left"><?php echo $v->category_id;?>
					</td>
					<td class="text-left"><?php echo $v->description;?>
					</td>
					<td class="text-left"><?php echo $v->top;?>
					</td>
					<td class="text-right btn btn-success">Aggiornata
					</td>
				</tr>
<?php
}
?>
			</tbody>
			</table>
		</div>
		<div class="table-responsive">
			<table class="table table-sm">
			<thead>
				<tr>
					<th>CODICE ARTICOLO
					</th>
					<th>Descrizione
					</th>
					<th>Quantità
					</th>
					<th>
					</th>
				</tr>
			</thead>
			<tbody>
<?php
foreach($accpro as $v){
?>
				<tr>
					<td class="text-left"><?php echo $v->model;?>
					</td>
					<td class="text-left"><?php echo $v->name;?>
					</td>
					<td class="text-left"><?php echo $v->quantity;?>
					</td>
					<td class="text-right btn btn-success btn-sm">Aggiornato
					</td>
				</tr>
<?php
}
?>
			</tbody>
			</table>
		</div>
<?php
}
?>
  <div class="text-center col-xs-12 bg-info"><button type="submit" class="btn btn-warning" name="Submit">Aggiorna</button></div>
 </div>
</div>
</form>
<?php

require("../../library/include/footer.php");
?>
