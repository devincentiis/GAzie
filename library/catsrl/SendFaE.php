<?php
function PostCallCATsrl($CATSRL_ENDPOINT, $file_to_send)
{
	$CA_FILE = 'CA_Agenzia_delle_Entrate.pem';

	// initialise the curl request
	$request = curl_init();

	// send a file
	curl_setopt($request, CURLOPT_CAINFO, dirname(__FILE__).'/'.$CA_FILE);
	curl_setopt($request, CURLOPT_POST, true);
	curl_setopt(
		$request,
		CURLOPT_POSTFIELDS,
		array(
		  'file_contents' => curl_file_create($file_to_send)
		)
	);
	curl_setopt($request, CURLOPT_URL, $CATSRL_ENDPOINT);

	// output the response
	curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($request);
	//echo('1-'.print_r($request,true)."<br />\n");
	//echo('2-'.print_r($result,true)."<br />\n");
	//echo('3-'.print_r(curl_error($request),true)."<br />\n");

	// close the session
	curl_close($request);

	return $result;
}

function PostRequestCATsrl($CATSRL_ENDPOINT, $enquiry)
{
	$CA_FILE = 'CA_Agenzia_delle_Entrate.pem';

	// initialise the curl request
	$request = curl_init();

	// send a file
	curl_setopt($request, CURLOPT_CAINFO, dirname(__FILE__).'/'.$CA_FILE);
	curl_setopt($request, CURLOPT_POST, true);
	curl_setopt(
		$request,
		CURLOPT_POSTFIELDS,
		array(
		  'enquiry' => base64_encode(json_encode($enquiry))
		)
	);
	curl_setopt($request, CURLOPT_URL, $CATSRL_ENDPOINT);

	// output the response
	curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($request);
	//echo('1-'.print_r($request,true)."<br />\n");
	//echo('2-'.print_r($result,true)."<br />\n");
	//echo('3-'.print_r(curl_error($request),true)."<br />\n");

	// close the session
	curl_close($request);

	return $result;
}

function SendFattureElettroniche($zip_fatture)
{
	$CATSRL_ENDPOINT = 'https://fatture.catsrl.it/gazie/RiceviZip.php';

	$result = PostCallCATsrl($CATSRL_ENDPOINT, realpath($zip_fatture));
	//echo('0-'.$result."<br />\n");

	$open_tag = '<PROTS>';
	$close_tag = '</PROTS>';

	$open_tag_pos = strpos($result, $open_tag);
	if ($open_tag_pos === FALSE) {
		return $result;
	}
	$close_tag_pos = strpos($result, $close_tag);
	if ($close_tag_pos === FALSE) {
		return $result;
	}

	$IdentificativiSdI = json_decode(base64_decode(substr($result, $open_tag_pos+7, $close_tag_pos-$open_tag_pos-7)), true);

	return $IdentificativiSdI;
}

function SendFatturaElettronica($xml_fattura)
{
	$CATSRL_ENDPOINT = 'https://fatture.catsrl.it/gazie/RiceviXml.php';

	$result = PostCallCATsrl($CATSRL_ENDPOINT, realpath($xml_fattura));
	//echo('0-'.$result."<br />\n");

	$open_tag = '<PROT>';
	$close_tag = '</PROT>';

	$open_tag_pos = strpos($result, $open_tag);
	if ($open_tag_pos === FALSE) {
		return $result;
	}
	$close_tag_pos = strpos($result, $close_tag);
	if ($close_tag_pos === FALSE) {
		return $result;
	}

	$IdentificativoSdI = array(substr($result, $open_tag_pos+6, $close_tag_pos-$open_tag_pos-6));

	return $IdentificativoSdI;
}

function ReceiveNotifiche($array_sdi)
{
	$CATSRL_ENDPOINT = 'https://fatture.catsrl.it/gazie/InviaNotifiche.php';

	$result = PostRequestCATsrl($CATSRL_ENDPOINT, $array_sdi);
	//echo('0-'.$result."<br />\n");

	$open_tag = '<PROTS>';
	$close_tag = '</PROTS>';

	$open_tag_pos = strpos($result, $open_tag);
	if ($open_tag_pos === FALSE) {
		return $result;
	}
	$close_tag_pos = strpos($result, $close_tag);
	if ($close_tag_pos === FALSE) {
		return $result;
	}

	$IdentificativiSdI = json_decode(base64_decode(substr($result, $open_tag_pos+7, $close_tag_pos-$open_tag_pos-7)), true);

	return $IdentificativiSdI;
}

function ReceiveFattF($array_fattf)
{
	$CATSRL_ENDPOINT = 'https://fatture.catsrl.it/gazie/InviaFattF.php';

	$result = PostRequestCATsrl($CATSRL_ENDPOINT, $array_fattf);
	//echo('0-'.$result."<br />\n");

	$open_tag = '<FATTF>';
	$close_tag = '</FATTF>';

	$open_tag_pos = strpos($result, $open_tag);
	if ($open_tag_pos === FALSE) {
		return $result;
	}
	$close_tag_pos = strpos($result, $close_tag);
	if ($close_tag_pos === FALSE) {
		return $result;
	}

	$AltreFattF = json_decode(base64_decode(substr($result, $open_tag_pos+7, $close_tag_pos-$open_tag_pos-7)), true);

	if (is_array($AltreFattF)) {
		$FattF = array();
		foreach ($AltreFattF as $AltraFattF) {
			$FattF[] = explode(';', $AltraFattF);
		}
		return $FattF;
	}

	return $AltreFattF;
}

function DownloadFattF($fattf_sdi)
{
	$CATSRL_ENDPOINT = 'https://fatture.catsrl.it/gazie/ScaricaFattF.php';

	$result = PostRequestCATsrl($CATSRL_ENDPOINT, $fattf_sdi);
	//echo('0-'.$result."<br />\n");

	$open_tag = '<FATTF>';
	$close_tag = '</FATTF>';

	$open_tag_pos = strpos($result, $open_tag);
	if ($open_tag_pos === FALSE) {
		return $result;
	}
	$close_tag_pos = strpos($result, $close_tag);
	if ($close_tag_pos === FALSE) {
		return $result;
	}

	$FattF = json_decode(base64_decode(substr($result, $open_tag_pos+7, $close_tag_pos-$open_tag_pos-7)), true);

	return $FattF;
}

?>