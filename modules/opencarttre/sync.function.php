<?php
/*
  --------------------------------------------------------------------------
  Copyright (C) - Antonio De Vincentiis anno 2020
  tel.3383121161
  a.devincentiis@tiscali.it
  Montesilvano (PE)
  --------------------------------------------------------------------------
*/

class opencarttregazSynchro {
  var $oc_api_url='';
  var $api_token='';
  var $rawres='';
	function __construct() {
		// Quando istanzio questa classe prendo il token, sempre, ma prima controllo se è buono
    $apiurlsess=isset($_SESSION['api_url'])?$_SESSION['api_url']:'';
    $apitoksess=isset($_SESSION['api_token'])?$_SESSION['api_token']:'';
    // rimpiazzo l'endpoint
		$urlok=str_replace('api/login','extension/module/ocgazie/okToken&store_id=0',$apiurlsess);
		$curl = curl_init($urlok);
		curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>TRUE,CURLOPT_POSTFIELDS=>'', CURLOPT_COOKIE => 'OCSESSID='.$apitoksess]);
		$ok_response = curl_exec($curl);
		if (!$ok_response){ // ... solo se  il token test non ha dato buon esito ne chiedo un altro prima di fare un exec
			global $gTables,$admin_aziend;
      $this->oc_api_url = gaz_dbi_get_row($gTables['company_data'], 'var','oc_api_url')['data'];
      $oc_api_username = gaz_dbi_get_row($gTables['company_data'], 'var','oc_api_username')['data'];
      $oc_api_key = gaz_dbi_get_row($gTables['company_data'], 'var','oc_api_key')['data'];
			// prendo il token
			$curl = curl_init($this->oc_api_url);
			$post = array('username' => $oc_api_username,'key'=>$oc_api_key);
			curl_setopt_array($curl,[ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post]);
			$raw_response = curl_exec($curl);
			$_SESSION['api_url']=$this->oc_api_url;
			if(!$raw_response){
				$_SESSION['api_token']=false;
				// Se $this->api_token ritorna FALSE vuol dire che le credenziali sono sbagliate
				$this->api_token=false;
			}else{
				$res = json_decode($raw_response);
				$_SESSION['api_token']=$res->api_token;
				$this->api_token=$res->api_token;
				curl_close($curl);
      }
			$this->rawres=$raw_response.'<br/>';
		}else{ // non sono passati 40 minuti: utilizzo il token che ho già e sembra funzionare
			$this->api_token=$_SESSION['api_token'];
			$this->oc_api_url=$_SESSION['api_url'];
		}
	}
	function SetupStore() {
		global $gTables,$admin_aziend;
		// aggiorno i dati comuni a tutto lo store: Anagrafica Azienda, Aliquote IVA, dati richiesti ai nuovi clienti (CF,PI,indirizzo,ecc) in custom_field e tutto ciò che necessita per evitare di digitarlo a mano su ecommerce-admin

		// aggiorno i dati azienda
		$urlupdstore=str_replace('api/login','extension/module/ocgazie/setupStore&store_id=0',$this->oc_api_url);
		$curl = curl_init($urlupdstore);
		$fields = array (	'store_id' => '0','data'=>array('image'=>bin2hex($admin_aziend['image']),'config_name'=>$admin_aziend['ragso1'].' '.$admin_aziend['ragso2'],'config_address'=>$admin_aziend['indspe'].' '.$admin_aziend['citspe'].' ('.$admin_aziend['prospe'].')','config_email'=>$admin_aziend['e_mail'],'config_telephone'=>$admin_aziend['telefo'],'config_fax'=>$admin_aziend['fax']));
		$post = http_build_query($fields);
		curl_setopt_array( $curl, [ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_COOKIE => 'OCSESSID='.$this->api_token]);
		$raw_response = curl_exec( $curl );
		$this->rawres.= $raw_response.'<br/>';
		curl_close($curl);

		// aggiorno le aliquote IVA (tax_rate, e geo_zone mettendo "0" per Italia (IVA) e usando gli stessi codici IVA di GAzie per tax_rate_id)
		$urltaxrate=str_replace('api/login','extension/module/ocgazie/upsertTaxRate&store_id=0',$this->oc_api_url);
		$curl = curl_init($urltaxrate);
		$res=gaz_dbi_dyn_query('*', $gTables['aliiva'], 1);
		while ($r=gaz_dbi_fetch_array($res)){
			$fields = array (	'tax_rate_id' => $r['codice'],'data'=>array('geo_zone_id'=>'380','name'=>$r['descri'],'rate'=>$r['aliquo'],'type'=>'P'));
			$post = http_build_query($fields);
			curl_setopt_array( $curl, [ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_COOKIE => 'OCSESSID='.$this->api_token]);
			$raw_response = curl_exec( $curl );
			$this->rawres.= $raw_response.'<br/>';
		}
		curl_close($curl);

		// setto gli stati di magazzino (stock_status)
		$urlstockstatus=str_replace('api/login','extension/module/ocgazie/setStockStatus&store_id=0',$this->oc_api_url);
		$curl = curl_init($urlstockstatus);
		$fields = array ('status' => 'status');
		$post = http_build_query($fields);
		curl_setopt_array( $curl, [ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_COOKIE => 'OCSESSID='.$this->api_token]);
		$raw_response = curl_exec( $curl );
		$this->rawres.= $raw_response.'<br/>';
		curl_close($curl);

		// setto la zona IVA Italia (geo_zone)
		$urlgeozone=str_replace('api/login','extension/module/ocgazie/setGeoZone&store_id=0',$this->oc_api_url);
		$curl = curl_init($urlgeozone);
		$fields = array ('gzone' => 'gzone');
		$post = http_build_query($fields);
		curl_setopt_array( $curl, [ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_COOKIE => 'OCSESSID='.$this->api_token]);
		$raw_response = curl_exec( $curl );
		$this->rawres.= $raw_response.'<br/>';
		curl_close($curl);

		// inserisco i custom_field per codice fiscale e partita IVA
		$urlcustomfield=str_replace('api/login','extension/module/ocgazie/setCustomField&store_id=0',$this->oc_api_url);
		$curl = curl_init($urlcustomfield);
		$fields = array ('customfield' => 'customfield');
		$post = http_build_query($fields);
		curl_setopt_array( $curl, [ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_COOKIE => 'OCSESSID='.$this->api_token]);
		$raw_response = curl_exec( $curl );
		$this->rawres.= $raw_response.'<br/>';
		curl_close($curl);

	}
	function UpsertCategory($d) {
		// aggiorno la categoria merceologica (category)
		$urlcategory=str_replace('api/login','extension/module/ocgazie/upsertCategory&store_id=0',$this->oc_api_url);
		$curl = curl_init($urlcategory);
		$fields = array('category_id'=>$d['codice'],'data'=>array('descri'=>$d['descri'],'image'=>$d['heximage'],'top'=>$d['top'],'annota'=>$d['annota']));
		$post = http_build_query($fields);
		curl_setopt_array( $curl, [ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_COOKIE => 'OCSESSID='.$this->api_token]);
		$raw_response = curl_exec( $curl );
		$this->rawres.=$raw_response.'<br/>';
		curl_close($curl);
	}
	function UpsertProduct($d) {
		// aggiorno l'articolo di magazzino (product)
		$urlproduct=str_replace('api/login','extension/module/ocgazie/upsertProduct&store_id=0',$this->oc_api_url);
		$curl = curl_init($urlproduct);
		/*
			ATTRIBUISCO LO STATO IN ACCORDO CON LA FUNZIONE API "setStockStatus" DOVE:
			$ss_descri[0]=array(1=>'In magazzino',2=>'Sottoscorta',3=>'In arrivo');
			$ss_descri[1]=array(1=>'In stock',2=>'Under-stock',3=>'In arrival');
		*/
		if ($d['quantity'] < 0) { // attribuisco lo stato in base alla giacenza e alla scorta minima indicata sul gestionale
			$d['quantity']=0;
			$stock_status = '3';
		} elseif ($d['quantity'] > 0) { //
			$stock_status = '1';
			if ($d['quantity']<=$d['scorta']){
				$stock_status = '2';
			}
		} else { // giacenza = 0
			$stock_status = '3';
		}
    // visibilità sull'ecommerce
    $view_status=( $d['web_public'] > 1 && $d['web_public'] < 5)?1:0;

		if ($d['web_price']>=0.00001){// in archivio ho valorizzato il prezzo web altrimenti uso quello normale listino 1
			$d['preve1']=round($d['web_price']*$d['web_multiplier'],4);
		}
    // controllo se ho una immagine migliore dell'articolo
 		global $gTables,$admin_aziend;
		$bigimage = gaz_dbi_get_row($gTables['files'],'table_name_ref','artico'," AND item_ref='".$d['codice']."' AND (extension LIKE 'jpg' OR extension LIKE 'png')");
    if ($bigimage && $bigimage['id_doc'] > 0){
      // controllo se ho il file immagine più grande
      $filename=DATA_DIR."files/".$admin_aziend['codice']."/images/". $bigimage['id_doc'] . "." . $bigimage['extension'];
      if (file_exists($filename)) {
        $d['heximage'] = bin2hex(file_get_contents($filename));
      }
    }
		$fields = array ('product_id' => intval($d['ref_ecommerce_id_product']),
								'data'=>array(	'name'=>$d['descri'],'model'=>$d['codice'],'quantity'=>$d['quantity'],'description'=>$d['body_text'].' ',
								'image'=>$d['heximage'],'price'=>floatval($d['preve1']),'category_id'=>intval($d['catmer']),'weight'=>floatval($d['peso_specifico']),'length'=>floatval($d['lunghezza']),'width'=>floatval($d['larghezza']),'height'=>floatval($d['spessore']),'tax_class_id'=>intval($d['aliiva']),'manufacturer_id'=>intval($d['clfoco']),'meta_keyword'=>$d['annota'].' ',	'stock_status_id'=>$stock_status,'status'=>$view_status));
		$post = http_build_query($fields);
		curl_setopt_array($curl,[ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_COOKIE => 'OCSESSID='.$this->api_token]);
		$raw_response = curl_exec($curl);
		if (!$raw_response){
			$this->rawres.='!Errore aggiornamento articolo!<br />';
		}else{
			$this->rawres.=$raw_response.'<br/>';
		}
		curl_close($curl);
	}

	function SetProductQuantity($d) {
		global $gTables;
		// aggiorno la quantità disponibile (quantity)
		$urlproduct=str_replace('api/login','extension/module/ocgazie/setProductQuantity&store_id=0',$this->oc_api_url);
		$curl = curl_init($urlproduct);
		$gForm = new magazzForm();
	  $mv = $gForm->getStockValue(false, $d);
		$magval = array_pop($mv);
    $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
		// trovo l'id di riferimento per opencart product
		$id = gaz_dbi_get_row($gTables['artico'],"codice",$d)['ref_ecommerce_id_product'];
		$fields = array ('product_id' => intval($id),'quantity'=>intval($magval['q_g']));
		$post = http_build_query($fields);
		curl_setopt_array($curl,[ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_COOKIE => 'OCSESSID='.$this->api_token]);
		$raw_response = curl_exec($curl);
		if (!$raw_response){
			$this->rawres.='!Errore aggiornamento quanrtità articolo!<br />';
		}else{
			$this->rawres.=$raw_response.'<br/>';
		}
		curl_close($curl);
	}
	function get_sync_status($last_id) {
		// prendo gli eventuali ordini arrivati assieme ai dati del cliente, se nuovo lo importo (order+customer),
		// in $last_id si deve passare l'ultimo ordine già importato al fine di non importare tutto ma solo i nuovi
		$urlorder=str_replace('api/login','extension/module/ocgazie/getOrder&store_id=0',$this->oc_api_url);
		$curl = curl_init($urlorder);
		$fields = array('last_id'=>$last_id);
		$post = http_build_query($fields);
		curl_setopt_array( $curl, [ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_COOKIE => 'OCSESSID='.$this->api_token]);
		$this->rawres = curl_exec( $curl );
		curl_close($curl);
	}
	function GetCustomers($last_customer='') {
		// chiamo e listo TUTTI i clienti che si sono registrati
		$urlcustomers=str_replace('api/login','extension/module/ocgazie/getCustomers',$this->oc_api_url);
		$curl = curl_init($urlcustomers);
		$fields = array('last_customer'=>$last_customer);
		$post = http_build_query($fields);
		curl_setopt_array( $curl, [ CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_COOKIE => 'OCSESSID='.$this->api_token]);
		$this->rawres = curl_exec( $curl );
		curl_close($curl);
	}
}

?>
