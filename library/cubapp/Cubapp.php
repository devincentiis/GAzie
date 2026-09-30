<?php

/*
 * CubAPP API Library
 * Class CubAPP API handles methods and properties to integrate CubAPP Cloud System.
 * URL: https://www.cubapp.it
 * Author: Connecta srls <info@connectasrl.it>
 * Date: 2016-07-01
 */

class CubAPP
{
	private static $CONFIG;

	public function __construct()
	{
		self::$CONFIG = CUBAPP_CONFIG;
	}

	private function AESencrypt($plaintext, $secretKey, $iV)
	{
		require_once(self::$CONFIG['PHPSECLIB_DIR'].'Crypt/AES.php');

		$aes = new Crypt_AES(CRYPT_AES_MODE_CBC);
		$aes->setKey($secretKey);
		$aes->setIV($iV);
		return base64_encode($aes->encrypt($plaintext));

	}

	private function AESdecrypt($ciphertext, $secretKey, $iV)
	{
		require_once(self::$CONFIG['PHPSECLIB_DIR'].'Crypt/AES.php');

		$aes = new Crypt_AES(CRYPT_AES_MODE_CBC);
		$aes->setKey($secretKey);
		$aes->setIV($iV);
		return base64_decode($aes->decrypt($ciphertext));

	}

	private function RSAencrypt($plaintext, $key_mod, $key_exp)
	{
		require_once(self::$CONFIG['PHPSECLIB_DIR'].'Math/BigInteger.php');
		require_once(self::$CONFIG['PHPSECLIB_DIR'].'Crypt/RSA.php');

		$rsa = new Crypt_RSA();
		$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
		$rsa->loadKey('<RSAKeyValue><Modulus>'.$key_mod.'</Modulus><Exponent>'.$key_exp.'</Exponent></RSAKeyValue>');
		return base64_encode($rsa->encrypt(base64_encode($plaintext)));

	}

	private function prepareBlobObject($token, $secretKey, $secretIV, $blobObject)
	{
		$blobObject['token'] = $token;

		$blobString = json_encode($blobObject);

		$b64_compressedBlobString = base64_encode(gzencode($blobString));

		$str_hash = hash_hmac('sha256', $b64_compressedBlobString, base64_decode(self::$CONFIG['HASH_KEY']));

		$b64_cipherCompressedBlobString = $this->AESencrypt($b64_compressedBlobString, $secretKey, $secretIV);

		$b64_cipherSharedKey = $this->RSAencrypt($secretKey, self::$CONFIG['RSA_PUBLIC_KEY_MOD'], self::$CONFIG['RSA_PUBLIC_KEY_EXP']);
		$b64_cipherSharedIV = $this->RSAencrypt($secretIV, self::$CONFIG['RSA_PUBLIC_KEY_MOD'], self::$CONFIG['RSA_PUBLIC_KEY_EXP']);

		$curl_post_data = json_encode(array(
			'api_key' => self::$CONFIG['API_KEY'],
			'api_hwsn' => self::$CONFIG['API_KEY'],
			'blob' => $b64_cipherCompressedBlobString,
			'key' => $b64_cipherSharedKey,
			'iv' => $b64_cipherSharedIV,
			'hash' => $str_hash,
			'token' => $token
		));

		return $curl_post_data;
	}

	private function decryptResponse($retval, $secretKey, $secretIV)
	{
		$responseDecrypted = $this->AESdecrypt(base64_decode($retval['response']), $secretKey, $secretIV);

		$hash = hash_hmac('sha256', $responseDecrypted, base64_decode(self::$CONFIG['HASH_KEY']));

		if ($hash != $retval['hash']) {
			die('Risposta non valida');
		} else {
			$responseJson = json_decode($responseDecrypted, true);
		}

		return $responseJson;

	}

	private function restRequest($gSyncRequest, $blobObject)
	{
		require_once(self::$CONFIG['PHPSECLIB_DIR'].'Crypt/Random.php');

		/* START TOKEN REQUEST */
		$token_url = self::$CONFIG['API_BASE_URL'].'token';
		$curl = curl_init($token_url);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		/*
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Host: srv1.cubapp.com'
		));
		curl_setopt($curl, CURLOPT_RESOLVE, ['srv1.cubapp.com:443:80.88.88.114']);
		*/
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		$curl_response = curl_exec($curl);
		if ($curl_response === false) {
			$info = curl_getinfo($curl);
			$error_message = 'Error Code: ' . curl_errno($curl) . '; Message: ' . curl_error($curl);
			curl_close($curl);
			die($error_message);
		}
		curl_close($curl);
		/* END TOKEN REQUEST */

		$retval = json_decode($curl_response);

		$secretKey = bin2hex(openssl_random_pseudo_bytes(128 / 8));
		$secretIV = base64_encode(crypt_random_string(mt_rand(0, 128 / 8)));

		$dato = $this->prepareBlobObject($retval->token, $secretKey, $secretIV, $blobObject);

		/* START API REQUEST */
		$service_url = self::$CONFIG['API_BASE_URL'].$gSyncRequest;
		$curl = curl_init($service_url);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		/*
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Content-Type: application/json',
			'Content-Length: ' . strlen($dato),
			'Host: srv1.cubapp.com'
		));
		*/
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Content-Type: application/json',
			'Content-Length: ' . strlen($dato))
		);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $dato);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		$curl_response = curl_exec($curl);
		if ($curl_response === false) {
			$info = curl_getinfo($curl);
			$error_message = 'Error Code: ' . curl_errno($curl) . '; Message: ' . curl_error($curl);
			curl_close($curl);
			die($error_message);
		}
		curl_close($curl);
		/* END API REQUEST */
		if (strpos($curl_response, '500 Internal Server Error') !== false) {
            //return null;
			return $curl_response;
		}
		$data = json_decode($curl_response, true);

		$retval = $this->decryptResponse($data, $secretKey, $secretIV);

		return $retval;

	}

	public function inviaSMS($cellulare, $messaggio)
	{
		$gSyncRequest = 'spedizione/sms';
		$blobObject = array(
			'contact_number' => $cellulare,
			'sms_text' => utf8_encode($messaggio)
		);

		return $this->restRequest($gSyncRequest, $blobObject);
	}

}

/* End of file Cubapp.php */

?>