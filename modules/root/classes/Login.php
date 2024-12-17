<?php

/*
--------------------------------------------------------------------------
GAzie - Gestione Azienda
Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
(https://www.devincentiis.it)
<https://gazie.sourceforge.net>
--------------------------------------------------------------------------
Questo programma e` free software;   e` lecito redistribuirlo  e/o
modificarlo secondo i  termini della Licenza Pubblica Generica GNU
come e` pubblicata dalla Free Software Foundation; o la versione 2
della licenza o (a propria scelta) una versione successiva.

Questo programma  e` distribuito nella speranza  che sia utile, ma
SENZA   ALCUNA GARANZIA; senza  neppure  la  garanzia implicita di
NEGOZIABILITA` o di  APPLICABILITA` PER UN  PARTICOLARE SCOPO.  Si
veda la Licenza Pubblica Generica GNU per avere maggiori dettagli.

Ognuno dovrebbe avere   ricevuto una copia  della Licenza Pubblica
Generica GNU insieme a   questo programma; in caso  contrario,  si
scriva   alla   Free  Software Foundation, 51 Franklin Street,
Fifth Floor Boston, MA 02110-1335 USA Stati Uniti.
--------------------------------------------------------------------------
*/

/**
* handles the user login/logout/session
* @author Panique
* @link https://www.php-login.net
* @link https://github.com/panique/php-login-advanced/
* @license https://opensource.org/licenses/MIT MIT License
*/
class Login
{
	/**
	* @var object $db_connection The database connection
	*/
	private $db_connection = null;
	/**
	* @var int $user_id The user's id
	*/
	private $user_id = null;
	/**
	* @var string $user_name The user's name
	*/
	private $user_name = "";
	/**
	* @var string $username_obj The user's name target of change
	*/
	private $username_obj = "";
	/**
	* @var string $user_email The user's mail
	*/
	private $user_email = "";
	/**
	* @var string $company_id the ID of company
	*/
	private $company_id = "";
	/**
	* @var boolean $user_is_logged_in The user's login status
	*/
	private $user_is_logged_in = false;
	/**
	* @var boolean $password_reset_link_is_valid Marker for view handling
	*/
	private $password_reset_link_is_valid = false;
	/**
	* @var int $password_is_expired 0 non scaduta, 1 scaduta da poco, >=2 scaduta da oltre 30gg
	*/
	private $password_is_expired = 0;
	/**
	* @var boolean $password_reset_was_successful Marker for view handling
	*/
	private $password_reset_was_successful = false;
	/**
	* @var boolean $password_change_was_successful Marker for view handling
	*/
	private $password_change_was_successful = false;
	/**
	* @var boolean $administrator_change_usr_password_was_successful Marker for view handling
	*/
	private $administrator_change_usr_password_was_successful = false;
	/**
	* @var array $errors Collection of error messages
	*/
	private $username_obj_abil = 5;
	/**
	* @var array $errors Collection of error messages
	*/
	private $table_banned = false;

	public $errors = array();
	/**
	* @var array $messages Collection of success / neutral messages
	*/
	public $messages = array();


	/**
	* the function "__construct()" automatically starts whenever an object of this class is created,
	* you know, when you do "$login = new Login();"
	*/
	public function __construct()
	{
		// create/read session
		//session_start();

		// TODO: organize this stuff better and make the constructor very small
		// TODO: unite Login and Registration classes ?

		// check the possible login actions:
		// 1. logout (happen when user clicks logout button)
		// 2. login via session data (happens each time user opens a page on your php project AFTER he has successfully logged in via the login form)
		// 3. login via cookie
		// 4. login via post data, which means simply logging in via the login form. after the user has submit his login/password successfully, his
		//    logged-in-status is written into his session data on the server. this is the typical behaviour of common login scripts.

		// if user tried to log out
		if (isset($_GET["logout"])) {
			$this->doLogout();

			// if user has an active session on the server
		} elseif (!empty($_SESSION['user_name']) && ($_SESSION['user_logged_in'] == 1)) {
			$this->loginWithSessionData();

			// checking for form submit from editing screen
			// user try to change his username
			if (isset($_POST["user_edit_submit_name"])) {
				// function below uses use $_SESSION['user_id'] et $_SESSION['user_email']
				$this->editUserName($_POST['user_name']);
				// user try to change his email
			} elseif (isset($_POST["user_edit_submit_email"])) {
				// function below uses use $_SESSION['user_id'] et $_SESSION['user_email']
				$this->editUserEmail($_POST['user_email']);
				// user try to change his password
			} elseif (isset($_POST["user_edit_submit_password"])) {
				// function below uses $_SESSION['user_name'] and $_SESSION['user_id']
				$this->editUserPassword($_POST['user_password_old'], $_POST['user_password_new'], $_POST['user_password_repeat']);
			}
      // un amministratore sta reimpostando un password ad un utente
      if (isset($_GET["un"])) {
				$this->username_obj=substr($_GET['un'],0,64);
        $this->username_obj_abil=$this->getUserData($this->username_obj)->Abilit;
			} elseif (isset($_POST["un"])) {
				$this->username_obj=substr($_POST['un'],0,64);
        $this->username_obj_abil=$this->getUserData($this->username_obj)->Abilit;
			}

			// login with cookie
		} elseif (isset($_COOKIE['rememberme'])) {
			$this->loginWithCookieData();

			// if user just submitted a login form
		} elseif (isset($_POST["login"])) {
			if (!isset($_POST['user_rememberme'])) {
				$_POST['user_rememberme'] = null;
			}
			$this->loginWithPostData($_POST['user_name'], $_POST['user_password'], $_POST['user_rememberme']);
		}
		// checking if user requested a password reset mail
		if (isset($_POST["request_password_reset"]) && isset($_POST['user_name'])) {
			$this->setPasswordResetDatabaseTokenAndSendMail($_POST['user_name']);
		} elseif (isset($_GET["user_name"]) && isset($_GET["verification_code"])) {
			$this->checkIfEmailVerificationCodeIsValid($_GET["user_name"], $_GET["verification_code"]);
		} elseif (isset($_POST["submit_new_password"])) {
			$this->editNewPassword($_POST['user_name'], $_POST['user_password'],$_POST['user_password_new'], $_POST['user_password_repeat']);
		} elseif (isset($_POST["submit_change_password"])) {
			$this->changePassword( $_POST['user_password_new'], $_POST['user_password_repeat']);
		} elseif (isset($_POST["change_very_old_password"])) {
			$this->changeVeryoldPassword($_POST['user_name'], $_POST['user_password'],$_POST['user_password_new'], $_POST['user_password_repeat']);
		} elseif (isset($_POST["administrator_change_usr_password"])) {
      $this->administratorChangeUsrPassword( $_POST['user_password_new'], $_POST['user_password_repeat']);
		}
	}

	/**
	* Checks if database connection is opened. If not, then this method tries to open it.
	* @return bool Success status of the database connecting process
	*/
	private function databaseConnection() {
		// if connection already exists
		if ($this->db_connection != null) {
			return true;
		} else {
			try {
				// Generate a database connection, using the PDO connector
				// @see https://net.tutsplus.com/tutorials/php/why-you-should-be-using-phps-pdo-for-database-access/
				// Also important: We include the charset, as leaving it out seems to be a security issue:
				// @see https://wiki.hashphp.org/PDO_Tutorial_for_MySQL_Developers#Connecting_to_MySQL says:
				// "Adding the charset to the DSN is very important for security reasons,
				// most examples you'll see around leave it out. MAKE SURE TO INCLUDE THE CHARSET!"
				$this->db_connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
				$this->db_connection->exec("/*!50701 SET SESSION sql_mode='' */");
				return true;
			} catch (PDOException $e) {
				$this->errors[] = MESSAGE_DATABASE_ERROR . $e->getMessage();
			}
		}
		// default return
		return false;
	}

	/**
	* Search into database for the user data of user_name specified as parameter
	* @return user data as an object if existing user
	* @return false if user_name is not found in the database
	* TODO: @devplanete This returns two different types. Maybe this is valid, but it feels bad. We should rework this.
	* TODO: @devplanete After some resarch I'm VERY sure that this is not good coding style! Please fix this.
	*/
	private function getUserData($user_name, $user_password_sha='')
	{
		// if database connection opened
		if ($this->databaseConnection()) {
			// database query, getting all the info of the selected user
			$query_user = $this->db_connection->prepare("SELECT *, SHA2(CONVERT(AES_DECRYPT(UNHEX(aes_key), UNHEX(SHA2('".$user_password_sha."', 512))) USING utf8), 512) AS aes_key_pass FROM " . DB_TABLE_PREFIX . '_admin WHERE user_name = :user_name');
			$query_user->bindValue(':user_name', $user_name, PDO::PARAM_STR);
			$query_user->execute();
			// get result row (as an object)
			return $query_user->fetchObject();
		} else {
			return false;
		}
	}

	/**
	* Logs in with S_SESSION data.
	* Technically we are already logged in at that point of time, as the $_SESSION values already exist.
	*/
	private function loginWithSessionData()
	{
		$this->user_name = $_SESSION['user_name'];
		$this->user_email = $_SESSION['user_email'];

		// set logged in status to true, because we just checked for this:
		// !empty($_SESSION['user_name']) && ($_SESSION['user_logged_in'] == 1)
		// when we called this method (in the constructor)
		$this->user_is_logged_in = true;
	}

	// recupero ip chiamante
	private function getUserIP() {
    if (getenv('HTTP_CLIENT_IP')) { $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) { $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('HTTP_X_FORWARDED')) { $ip = getenv('HTTP_X_FORWARDED');
    } elseif (getenv('HTTP_FORWARDED_FOR')) { $ip = getenv('HTTP_FORWARDED_FOR');
    } elseif (getenv('HTTP_FORWARDED')) { $ip = getenv('HTTP_FORWARDED');
    } else { $ip = $_SERVER['REMOTE_ADDR']; }
    return $ip;
  }

	// store failed IP
	private function storeFailedIP() {
    // increase the failed attempts counter in order to ban the originating IP
    $query_ip = $this->db_connection->prepare("SELECT * FROM " . DB_TABLE_PREFIX . "_banned_ip WHERE ipv4 = :ipv4 AND `reference` ='postlogin';");
    $query_ip->bindValue(':ipv4', $this->getUserIP(), PDO::PARAM_STR);
    $query_ip->execute();
    $ip_row = $query_ip->fetchObject();
    if (isset($ip_row->id)) {
      $acc = $this->db_connection->prepare("UPDATE " . DB_TABLE_PREFIX . "_banned_ip  SET `attempts` = attempts + 1 WHERE `id`= ".$ip_row->id.";");
    } else {
      $acc = $this->db_connection->prepare("INSERT INTO ". DB_TABLE_PREFIX . "_banned_ip  (`reference`, `ipv4`, `attempts`) VALUES ('postlogin','".$this->getUserIP()."',1);");
    }
    $acc->execute();
  }

	private function checkBanned() {
    try {
      $tabex = $this->db_connection->query("SELECT 1 FROM `". DB_TABLE_PREFIX ."_banned_ip` LIMIT 1");
    } catch (Exception $e) {
      $this->table_banned = false;
      return false;
    }
    $this->table_banned = true;
    $query_ban = $this->db_connection->prepare("SELECT * FROM " . DB_TABLE_PREFIX . "_banned_ip WHERE ipv4 = '".$this->getUserIP()."' AND `reference` ='postlogin';");
    $query_ban->execute();
    $ip_ban = $query_ban->fetchObject();
    if (isset($ip_ban->id) && $ip_ban->attempts >10 ) { // questo IP ha fattto oltre 10 tentativi falliti, non potrà più accedere a meno che non lo si rimuove dalla tabella del DB
      return true;
    } else {
      return false;
    }
	}



	private function loginWithPostData($user_name, $user_password, $user_rememberme)
	{
		$chkbnd=true;
    if (empty($user_name)) {
			$this->errors[] = MESSAGE_USERNAME_EMPTY;
		} else if (empty($user_password)) {
			$this->errors[] = MESSAGE_PASSWORD_EMPTY;
			// if POST data (from login form) contains non-empty user_name and non-empty user_password
		} else {
			// user can login with his username or his email address.
			// if user has not typed a valid email address, we try to identify him with his user_name
			if (!filter_var($user_name, FILTER_VALIDATE_EMAIL)) {
				// database query, getting all the info of the selected user
				$userdata = $this->getUserData(trim($user_name), $user_password);
				// if user has typed a valid email address, we try to identify him with his user_email
			} else if ($this->databaseConnection()) {
				// database query, getting all the info of the selected user
				$query_user = $this->db_connection->prepare("SELECT * FROM " . DB_TABLE_PREFIX . '_admin WHERE user_email = :user_email');
				$query_user->bindValue(':user_email', trim($user_name), PDO::PARAM_STR);
				$query_user->execute();
				// get result row (as an object)
				$userdata = $query_user->fetchObject();
			}
      if ($this->checkBanned()) {
        $this->errors[] = MESSAGE_IP_BANNED;
      } elseif (! isset($userdata->user_id)) { // if this user not exists
				// se la password risulta essere sbagliata ed ho un il vecchio nome della colonna "Password" propongo di aggiornare il database
				$query_us = $this->db_connection->prepare('SELECT * FROM ' . DB_TABLE_PREFIX . '_admin WHERE user_name = :user_name');
				$query_us->bindValue(':user_name', trim($user_name), PDO::PARAM_STR);
				$query_us->execute();
				// get result row (as an object)
				$r_row = $query_us->fetchObject();
				if (isset($r_row->Password)) {
					$this->errors[] = MESSAGE_TRY_UPDATE_DATABASE;
				} else {
					// was MESSAGE_USER_DOES_NOT_EXIST before, but has changed to MESSAGE_LOGIN_FAILED
					// to prevent potential attackers showing if the user exists
					$this->errors[] = MESSAGE_LOGIN_FAILED;
				}
        $this->storeFailedIP();
			} else if (($userdata->user_failed_logins >= 3) && ($userdata->user_last_failed_login > (time() - 60))) {
				$this->errors[] = MESSAGE_PASSWORD_WRONG_3_TIMES;
				// using PHP 5.5's password_verify() function to check if the provided passwords fits to the hash of that user's password
			} else if (!password_verify($user_password, $userdata->user_password_hash)) {
				// increment the failed login counter for that user
				$sth = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin '
				. 'SET user_failed_logins = user_failed_logins+1, user_last_failed_login = :user_last_failed_login '
				. 'WHERE user_name = :user_name OR user_email = :user_name');
				$sth->execute(array(':user_name' => $user_name, ':user_last_failed_login' => time()));
				$this->errors[] = MESSAGE_PASSWORD_WRONG;
        $this->storeFailedIP();
				// has the user activated their account with the verification email
			} else if ($userdata->user_active != 1) {
				$this->errors[] = MESSAGE_ACCOUNT_NOT_ACTIVATED;
			} else {
				// INIZIO ---- ripresa del valore del tema (g7,lte o altri personalizzati)
				$rt = $this->db_connection->prepare('SELECT var_value FROM ' . DB_TABLE_PREFIX . '_admin_config WHERE var_name = \'theme\' AND adminid = :user_name');
				$rt->bindValue(':user_name', $userdata->user_name, PDO::PARAM_STR);
				$rt->execute();
				// get result row (as an object)
				$rt_row = $rt->fetchObject();
				$_SESSION['theme'] = $rt_row->var_value;
				// FINE ---- ripresa del valore del tema (g7,lte o altri personalizzati)

				/*  se sul file config/config/gconfig.php scelgo di comunicare ad un hosting d'appoggio
				il mio eventuale nuovo IP DINAMICO del router ADSL faccio un ping ad esso così altri utenti
				che sono a conoscenza del meccanismo possono richiederlo e successivamente essere ridiretti
				qui tramite HTTPS */
				if (SET_DYNAMIC_IP != '') {
					@ini_set('default_socket_timeout',3);
					@file_get_contents(SET_DYNAMIC_IP);
				}

        // riprendo dalla configurazione generale i giorni di scadenza password
        $giopas = $this->db_connection->prepare("SELECT cvalue FROM  ". DB_TABLE_PREFIX . "_config WHERE variable = 'giornipass'");
        $giopas->execute();
        // get result row (as an object)
        $valgiopas = $giopas->fetchObject(); // in $valgiopas->cvalue ho i giorni di validità della password
        $vgp = (int)$valgiopas->cvalue;
        $today = new DateTime();
        $datpasone = new DateTime($userdata->datpas);
        $datpastwo = new DateTime($userdata->datpas);
        $datpasone->modify('+'.$vgp.' days');
        $datpastwo->modify('+'.($vgp+30).' days');
        // si possono verificare 3 casi: password scaduta da poco, scaduta da oltre 30gg, e non scaduta (rispetto alla data odierna + giornipass di gaz_config)
        if ($today >= $datpastwo ) { //  scaduta oltre i 30gg
          $this->password_is_expired = 2;
        } elseif ($today >= $datpasone ) { // scaduta entro i 30gg
          $this->password_is_expired = 1;
        }

        if ($this->password_is_expired <= 1) {
          // increment the login counter for that user
          $acc = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin '
          . " SET Access = Access+1, last_ip = '". $this->getUserIP()."' WHERE user_name = :user_name ;");
          $acc->execute(array(':user_name' => $userdata->user_name));

          // insert login user data into gaz_admin_login_history
          $acc = $this->db_connection->prepare('INSERT INTO ' . DB_TABLE_PREFIX . '_admin_login_history '
          . ' (`login_user_id`, `login_datetime`, `login_user_ip`) VALUES ('. $userdata->user_id.",'".date('Y-m-d H:i:s')."', '".$this->getUserIP()."');");
          $acc->execute();
          // write user data into PHP SESSION [a file on your server]
          $_SESSION['user_id'] = $userdata->user_id;
          $_SESSION['user_name'] = $userdata->user_name;
          $_SESSION['user_email'] = $userdata->user_email;
          $_SESSION['company_id'] = $userdata->company_id;
          $_SESSION['user_logged_in'] = 1;
          $prepared_key = openssl_pbkdf2($user_password.$userdata->user_name, AES_KEY_SALT, 16, 1000, "sha256");
          $_SESSION['aes_key'] = openssl_decrypt(base64_decode($userdata->aes_key),"AES-128-CBC",$prepared_key,OPENSSL_RAW_DATA, AES_KEY_IV);
          // declare user id, set the login status to true
          $this->user_id = $userdata->user_id;
          $this->user_name = $userdata->user_name;
          $this->user_email = $userdata->user_email;
          $this->company_id = $userdata->company_id;
          $this->user_is_logged_in = true;
          // reset the failed login counter for that user
          $sth = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin '
          . 'SET user_failed_logins = 0, user_last_failed_login = NULL '
          . 'WHERE user_id = :user_id AND user_failed_logins != 0');
          $sth->execute(array(':user_id' => $userdata->user_id));
          if ($this->table_banned){ // clear banned IP only if table exist
            $acc = $this->db_connection->prepare("DELETE FROM " . DB_TABLE_PREFIX . "_banned_ip WHERE `ipv4` = '".$this->getUserIP()."' AND `reference` = 'postlogin';");
            $acc->execute();
          }
          // if user has check the "remember me" checkbox, then generate token and write cookie
          if (isset($user_rememberme)) {
            $this->newRememberMeCookie();
          } else {
            // Reset remember-me token
            $this->deleteRememberMeCookie();
          }
        }

        // OPTIONAL: recalculate the user's password hash
        // DELETE this if-block if you like, it only exists to recalculate users's hashes when you provide a cost factor,
        // by default the script will use a cost factor of 10 and never change it.
        // check if the have defined a cost factor in config/hashing.php
        if (defined('HASH_COST_FACTOR')) {
          // check if the hash needs to be rehashed
          if (password_needs_rehash($userdata->user_password_hash, PASSWORD_DEFAULT, array('cost' => HASH_COST_FACTOR))) {

            // calculate new hash with new cost factor
            $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT, array('cost' => HASH_COST_FACTOR));

            // TODO: this should be put into another method !?
            $query_update = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin SET user_password_hash = :user_password_hash WHERE user_id = :user_id');
            $query_update->bindValue(':user_password_hash', $user_password_hash, PDO::PARAM_STR);
            $query_update->bindValue(':user_id', $userdata->user_id, PDO::PARAM_INT);
            $query_update->execute();

            if ($query_update->rowCount() == 0) {
              // writing new hash was successful. you should now output this to the user ;)
            } else {
              // writing new hash was NOT successful. you should now output this to the user ;)
            }
          }
        }
			}
		}
	}

	/**
	* Create all data needed for remember me cookie connection on client and server side
	*/
	private function newRememberMeCookie()
	{
		// if database connection opened
		if ($this->databaseConnection()) {
			// get cookie_secret_key from gaz_config
			$csk = $this->db_connection->prepare("SELECT cvalue FROM  ". DB_TABLE_PREFIX . "_config WHERE variable = 'cookie_secret_key'");
			$csk->execute();
			// get result row (as an object)
			$csk_row = $csk->fetchObject();

			// generate 64 char random string and store it in current user data
			$random_token_string = hash('sha256', mt_rand());
			$sth = $this->db_connection->prepare("UPDATE " . DB_TABLE_PREFIX . "_admin SET user_rememberme_token = :user_rememberme_token WHERE user_id = :user_id");
			$sth->execute(array(':user_rememberme_token' => $random_token_string, ':user_id' => $_SESSION['user_id']));

			// generate cookie string that consists of userid, randomstring and combined hash of both
			$cookie_string_first_part = $_SESSION['user_id'] . ':' . $random_token_string;
			$cookie_string_hash = hash('sha256', $cookie_string_first_part . $csk_row->cvalue);
			$cookie_string = $cookie_string_first_part . ':' . $cookie_string_hash;

			// set cookie
			setcookie('rememberme', $cookie_string, time() + COOKIE_RUNTIME, "/", COOKIE_DOMAIN);
		}
	}

	/**
	* Delete all data needed for remember me cookie connection on client and server side
	*/
	private function deleteRememberMeCookie()
	{
		// if database connection opened
		if ($this->databaseConnection()) {
			// Reset rememberme token
			$sth = $this->db_connection->prepare("UPDATE " . DB_TABLE_PREFIX . "_admin SET user_rememberme_token = NULL WHERE user_id = :user_id");
			$sth->execute(array(':user_id' => $_SESSION['user_id']));
		}

		// set the rememberme-cookie to ten years ago (3600sec * 365 days * 10).
		// that's obivously the best practice to kill a cookie via php
		// @see https://stackoverflow.com/a/686166/1114320
		setcookie('rememberme', false, time() - (3600 * 3650), '/', COOKIE_DOMAIN);
	}

	/**
	* Perform the logout, resetting the session
	*/
	public function doLogout()
	{
		$this->deleteRememberMeCookie();

		$_SESSION = array();
		session_destroy();

		$this->user_is_logged_in = false;
		$this->messages[] = MESSAGE_LOGGED_OUT;
	}

	/**
	* Simply return the current state of the user's login
	* @return bool user's login status
	*/
	public function isUserLoggedIn()
	{
		return $this->user_is_logged_in;
	}

	/**
	*  ritorna lo stato di scadenza password utente
	*/
	public function isPasswordExpired()
	{
		return $this->password_is_expired;
	}

	/**
	* Edit the user's name, provided in the editing form
	*/
	public function editUserName($user_name)
	{
		// prevent database flooding
		$user_name = substr(trim($user_name), 0, 64);

		if (!empty($user_name) && $user_name == $_SESSION['user_name']) {
			$this->errors[] = MESSAGE_USERNAME_SAME_LIKE_OLD_ONE;

			// username cannot be empty and must be azAZ09 and 2-64 characters
			// TODO: maybe this pattern should also be implemented in Registration.php (or other way round)
		} elseif (empty($user_name) || !preg_match("/^(?=.{2,64}$)[a-zA-Z][a-zA-Z0-9]*(?: [a-zA-Z0-9]+)*$/", $user_name)) {
			$this->errors[] = MESSAGE_USERNAME_INVALID;

		} else {
			// check if new username already exists
			$userdata = $this->getUserData($user_name);

			if (isset($userdata->user_id)) {
				$this->errors[] = MESSAGE_USERNAME_EXISTS;
			} else {
				// write user's new data into database
				$query_edit_user_name = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin SET user_name = :user_name WHERE user_id = :user_id');
				$query_edit_user_name->bindValue(':user_name', $user_name, PDO::PARAM_STR);
				$query_edit_user_name->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
				$query_edit_user_name->execute();

				if ($query_edit_user_name->rowCount()) {
					$_SESSION['user_name'] = $user_name;
					$this->messages[] = MESSAGE_USERNAME_CHANGED_SUCCESSFULLY . $user_name;
				} else {
					$this->errors[] = MESSAGE_USERNAME_CHANGE_FAILED;
				}
			}
		}
	}

	/**
	* Edit the user's email, provided in the editing form
	*/
	public function editUserEmail($user_email)
	{
		// prevent database flooding
		$user_email = substr(trim($user_email), 0, 64);

		if (!empty($user_email) && $user_email == $_SESSION["user_email"]) {
			$this->errors[] = MESSAGE_EMAIL_SAME_LIKE_OLD_ONE;
			// user mail cannot be empty and must be in email format
		} elseif (empty($user_email) || !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
			$this->errors[] = MESSAGE_EMAIL_INVALID;

		} else {
			// check if new username already exists
			$userdata = $this->getUserData($user_name);

			if (isset($userdata->user_id)) {
				$this->errors[] = MESSAGE_USERNAME_EXISTS;
			} else {
				// write user's new data into database
				$query_edit_user_name = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin SET user_name = :user_name WHERE user_id = :user_id');
				$query_edit_user_name->bindValue(':user_name', $user_name, PDO::PARAM_STR);
				$query_edit_user_name->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
				$query_edit_user_name->execute();

				if ($query_edit_user_name->rowCount()) {
					$_SESSION['user_name'] = $user_name;
					$this->messages[] = MESSAGE_USERNAME_CHANGED_SUCCESSFULLY . $user_name;
				} else {
					$this->errors[] = MESSAGE_USERNAME_CHANGE_FAILED;
				}
			}
		}
	}

	/**
	* Edit the user's password, provided in the editing form
	*/
	public function editUserPassword($user_password_old, $user_password_new, $user_password_repeat)
	{
		if (empty($user_password_new) || empty($user_password_repeat) || empty($user_password_old)) {
			$this->errors[] = MESSAGE_PASSWORD_EMPTY;
			// is the repeat password identical to password
		} elseif ($user_password_new !== $user_password_repeat) {
			$this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
			// password need to have a minimum length of 6 characters
		} elseif (strlen($user_password_new) < 8) {
			$this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;

			// all the above tests are ok
		} else {
			// database query, getting hash of currently logged in user (to check with just provided password)
			$userdata = $this->getUserData($_SESSION['user_name']);

			// if this user exists
			if (isset($userdata->user_password_hash)) {

				// using PHP 5.5's password_verify() function to check if the provided passwords fits to the hash of that user's password
				if (password_verify($user_password_old, $userdata->user_password_hash)) {

					// now it gets a little bit crazy: check if we have a constant HASH_COST_FACTOR defined (in config/hashing.php),
					// if so: put the value into $hash_cost_factor, if not, make $hash_cost_factor = null
					$hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);

					// crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 character hash string
					// the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using PHP 5.3/5.4, by the password hashing
					// compatibility library. the third parameter looks a little bit shitty, but that's how those PHP 5.5 functions
					// want the parameter: as an array with, currently only used with 'cost' => XX.
					$user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));

					// write users new hash into database
					$query_update = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin SET user_password_hash = :user_password_hash WHERE user_id = :user_id');
					$query_update->bindValue(':user_password_hash', $user_password_hash, PDO::PARAM_STR);
					$query_update->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
					$query_update->execute();

					// check if exactly one row was successfully changed:
					if ($query_update->rowCount()) {
						$this->messages[] = MESSAGE_PASSWORD_CHANGED_SUCCESSFULLY;
					} else {
						$this->errors[] = MESSAGE_PASSWORD_CHANGE_FAILED;
					}
				} else {
					$this->errors[] = MESSAGE_OLD_PASSWORD_WRONG;
				}
			} else {
				$this->errors[] = MESSAGE_USER_DOES_NOT_EXIST;
			}
		}
	}

	/**
	* Sets a random token into the database (that will verify the user when he/she comes back via the link
	* in the email) and sends the according email.
	*/
	public function setPasswordResetDatabaseTokenAndSendMail($user_name)
	{
		$user_name = trim($user_name);

		if (empty($user_name)) {
			$this->errors[] = MESSAGE_USERNAME_EMPTY;

		} else {
			// generate timestamp (to see when exactly the user (or an attacker) requested the password reset mail)
			// btw this is an integer ;)
			$temporary_timestamp = time();
			// generate random hash for email password reset verification (40 char string)
			$user_password_reset_hash = sha1(uniqid(mt_rand(), true));
			// database query, getting all the info of the selected user
			$userdata = $this->getUserData($user_name);

			// if this user exists
			if (isset($userdata->user_id)) {

				// database query:
				$query_update = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin SET user_password_reset_hash = :user_password_reset_hash, user_password_reset_timestamp = :user_password_reset_timestamp WHERE user_name = :user_name');
				$query_update->bindValue(':user_password_reset_hash', $user_password_reset_hash, PDO::PARAM_STR);
				$query_update->bindValue(':user_password_reset_timestamp', $temporary_timestamp, PDO::PARAM_INT);
				$query_update->bindValue(':user_name', $user_name, PDO::PARAM_STR);
				$query_update->execute();

				// check if exactly one row was successfully changed:
				if ($query_update->rowCount() == 1) {
					// send a mail to the user, containing a link with that token hash string
					$this->sendPasswordResetMail($user_name, $userdata->user_email, $user_password_reset_hash);
					return true;
				} else {
					$this->errors[] = MESSAGE_DATABASE_ERROR;
				}
			} else {
				$this->errors[] = MESSAGE_USER_DOES_NOT_EXIST;
			}
		}
		// return false (this method only returns true when the database entry has been set successfully)
		return false;
	}

	/**
	* Sends the password-reset-email.
	*/
	public function sendPasswordResetMail($user_name, $user_email, $user_password_reset_hash)
	{
		$mail = new PHPMailer;
		// get email send config from GAzie db
		$var = array('admin_mail', 'admin_smtp_server', 'admin_return_notification', 'admin_mailer', 'admin_smtp_port', 'admin_smtp_secure', 'admin_smtp_user', 'admin_smtp_password');
		foreach ($var as $v) {
      $qv=($v=='admin_smtp_password')?"AES_DECRYPT(FROM_BASE64(cvalue),'JnèGCM(ùRp$9ò{-c') AS cvalue":'cvalue';
      // ATTENZIONE!!! L'AES_KEY di default JnèGCM(ùRp$9ò{-c qui è in chiaro eventualmente cambiarlo con altro valore, molto dipende da come utilizzate il gestionale ed in particolare se presente il modulo school o volete consentire la registrazione da remoto (sconsigliato per azienda in produzione)
			$query_email_smtp_conf = $this->db_connection->prepare('SELECT '.$qv.' FROM ' . DB_TABLE_PREFIX . '_config WHERE variable=:variable');
			$query_email_smtp_conf->bindValue(':variable', $v, PDO::PARAM_STR);
			$query_email_smtp_conf->execute();
			$r = $query_email_smtp_conf->fetchAll();
			$this->email_conf[$v] = $r[0]['cvalue'];
		}

		// please look into the config/config.php for much more info on how to use this!
		// use SMTP or use mail()
		if (EMAIL_USE_SMTP) {
			// Set mailer to use SMTP
			$mail->IsSMTP();
			//useful for debugging, shows full SMTP errors
			//$mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
			// Enable SMTP authentication
			$mail->SMTPAuth = EMAIL_SMTP_AUTH;
			// Enable encryption, usually SSL/TLS
			$email_smtp_encr = trim($this->email_conf['admin_smtp_secure']);
			if (strlen($email_smtp_encr) > 2) {
				$mail->SMTPSecure = $email_smtp_encr;
			}

			// Specify host server
			$mail->Host = $this->email_conf['admin_smtp_server']; // EMAIL_SMTP_HOST;
			$mail->Username = $this->email_conf['admin_smtp_user']; //EMAIL_SMTP_USERNAME;
			$mail->Password = $this->email_conf['admin_smtp_password']; //EMAIL_SMTP_PASSWORD;
			$mail->Port = $this->email_conf['admin_smtp_port']; //EMAIL_SMTP_PORT;
		} else {
			$mail->IsMail();
		}
		$mail->IsHTML(true);
		$mail->From = $this->email_conf['admin_mail'];
		$mail->FromName = EMAIL_PASSWORDRESET_FROM_NAME;
		$mail->AddAddress($user_email);
		$mail->Subject = EMAIL_PASSWORDRESET_SUBJECT;
		$mail->AddEmbeddedImage('../../library/images/gazie.gif', 'gazie');
		$link = EMAIL_PASSWORDRESET_URL . '?user_name=' . urlencode($user_name) . '&verification_code=' . urlencode($user_password_reset_hash);
		$mail->Body = EMAIL_PASSWORDRESET_CONTENT . '<br><a href="' . $link . '"> <img src="cid:gazie" /> ' . MESSAGE_EMAIL_LINK_FOR_RESET . '</a>';


		if (!$mail->Send()) {
			$this->errors[] = MESSAGE_PASSWORD_RESET_MAIL_FAILED . $mail->ErrorInfo;
			return false;
		} else {
			$this->messages[] = MESSAGE_PASSWORD_RESET_MAIL_SUCCESSFULLY_SENT;
			return true;
		}
	}

	/**
	* Checks if the verification string in the account verification mail is valid and matches to the user.
	*/
	public function checkIfEmailVerificationCodeIsValid($user_name, $verification_code)
	{
		$user_name = trim($user_name);

		if (empty($user_name) || empty($verification_code)) {
			$this->errors[] = MESSAGE_LINK_PARAMETER_EMPTY;
		} else {
			// database query, getting all the info of the selected user
			$userdata = $this->getUserData($user_name);

			// if this user exists and have the same hash in database
			if (isset($userdata->user_id) && $userdata->user_password_reset_hash == $verification_code) {

				$timestamp_one_hour_ago = time() - 3600; // 3600 seconds are 1 hour

				if ($userdata->user_password_reset_timestamp > $timestamp_one_hour_ago) {
					// set the marker to true, making it possible to show the password reset edit form view
					$this->password_reset_link_is_valid = true;
				} else {
					$this->errors[] = MESSAGE_RESET_LINK_HAS_EXPIRED;
				}
			} else {
				$this->errors[] = MESSAGE_USER_DOES_NOT_EXIST;
			}
		}
	}

	/**
	* Checks and writes the new password.
	*/
	public function editNewPassword($user_name, $user_password, $user_password_new, $user_password_repeat )
	{
		// TODO: timestamp!
		$user_name = trim($user_name);

		if (empty($user_name) || empty($user_password_new) || empty($user_password_repeat) || empty($user_password) ){
			$this->errors[] = MESSAGE_PASSWORD_EMPTY;
			// is the repeat password identical to password
		} else if ($user_password_new !== $user_password_repeat) {
			$this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
			// password need to have a minimum length of 6 characters
		} else if (strlen($user_password_new) < 8) {
			$this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;
			// if database connection opened
		} else if ($this->databaseConnection()) {
			$hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);
			$user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));
      // faccio la verifica della vecchia
      $query_user = $this->db_connection->prepare("SELECT * FROM " . DB_TABLE_PREFIX . '_admin WHERE user_name = :user_name');
      $query_user->bindValue(':user_name', trim($user_name), PDO::PARAM_STR);
      $query_user->execute();
      $userdata = $query_user->fetchObject();
      if (password_verify( $user_password  , $userdata->user_password_hash )){ // verifico la vecchia password
        // sostituisco aes_key
        $prepared_key = openssl_pbkdf2($user_password.$user_name, AES_KEY_SALT, 16, 1000, "sha256");
        $old_aes_key = openssl_decrypt(base64_decode($userdata->aes_key),"AES-128-CBC",$prepared_key,OPENSSL_RAW_DATA, AES_KEY_IV);
        $prepared_key = openssl_pbkdf2($user_password_new.$user_name, AES_KEY_SALT, 16, 1000, "sha256");
        $aes_key = base64_encode(openssl_encrypt($old_aes_key,"AES-128-CBC",$prepared_key,OPENSSL_RAW_DATA, AES_KEY_IV));
        // write users new hash into database
        $query_update = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin SET user_password_hash = :user_password_hash,
                              user_password_reset_hash = NULL, user_password_reset_timestamp = NULL, aes_key = :aes_key, datpas = NOW()
                              WHERE user_name = :user_name');
        $query_update->bindValue(':user_password_hash', $user_password_hash, PDO::PARAM_STR);
        $query_update->bindValue(':aes_key', $aes_key, PDO::PARAM_STR); // con aes_key controllo se effettivamente l'utente ha cambiato la password
        $query_update->bindValue(':user_name', $user_name, PDO::PARAM_STR);
        $query_update->execute();

        // check if exactly one row was successfully changed:
        if ($query_update->rowCount() == 1) {
          $this->password_reset_was_successful = true;
          $this->messages[] = MESSAGE_PASSWORD_CHANGED_SUCCESSFULLY;
        } else {          $this->errors[] = MESSAGE_PASSWORD_CHANGE_FAILED;
        }
      } else {
        $this->errors[] = MESSAGE_LOGIN_FAILED;
			}

		}
	}

	/**
	* Checks and writes the new password.
	*/
	public function changePassword($user_password_new, $user_password_repeat)
	{
		$user_name = $_SESSION['user_name'];
    $this->password_is_expired = 1;
    if ($user_password_new !== $user_password_repeat) {
			$this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
			// password need to have a minimum length of 6 characters
		} else if (strlen($user_password_new) < 8) {
			$this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;
			// if database connection opened
		} else if ($this->databaseConnection()) {
			// now it gets a little bit crazy: check if we have a constant HASH_COST_FACTOR defined (in config/hashing.php),
			// if so: put the value into $hash_cost_factor, if not, make $hash_cost_factor = null
			$hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);
      $user_password_hash = password_hash($user_password_new , PASSWORD_DEFAULT, ['cost' => $hash_cost_factor]);
      // ripreparo la chiave per criptare la chiave contenuta in $_SESSION con la nuova password e metterla aes_key di gaz_admin
      $prepared_key = openssl_pbkdf2($user_password_new.$user_name, AES_KEY_SALT, 16, 1000, "sha256");
      $aes_key = base64_encode(openssl_encrypt($_SESSION['aes_key'],"AES-128-CBC",$prepared_key,OPENSSL_RAW_DATA, AES_KEY_IV));

			// write users new  password hash and aes_key into database
			$query_update = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin SET user_password_hash = :user_password_hash,
														user_password_reset_hash = NULL, user_password_reset_timestamp = NULL, aes_key = :aes_key, datpas = NOW()
														WHERE user_name = :user_name AND aes_key <> :aes_key ');
			$query_update->bindValue(':user_password_hash', $user_password_hash, PDO::PARAM_STR);
			$query_update->bindValue(':aes_key', $aes_key, PDO::PARAM_STR); // con aes_key controllo se effettivamente l'utente ha cambiato la password
			$query_update->bindValue(':user_name', $user_name, PDO::PARAM_STR);
			$query_update->execute();
			// check if exactly one row was successfully changed:
			if ($query_update->rowCount() == 1) {
				$this->password_change_was_successful = true;
        $this->password_is_expired = 0;
				$this->messages[] = MESSAGE_PASSWORD_CHANGED_SUCCESSFULLY;
			} else {
				$this->errors[] = MESSAGE_PASSWORD_CHANGE_FAILED;
			}
		}
	}

	public function changeVeryoldPassword($user_name, $user_password, $user_password_new, $user_password_repeat)
	{
    $this->password_is_expired = 2;
		$user_name = trim($user_name);
		if (empty($user_name) || empty($user_password) || empty($user_password_new) || empty($user_password_repeat)) {
			$this->errors[] = MESSAGE_PASSWORD_EMPTY;
			// is the repeat password identical to password
		} else if ($user_password_new !== $user_password_repeat) {
			$this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
			// password need to have a minimum length of 6 characters
		} else if ($user_password_new == $user_password) {
			$this->errors[] = MESSAGE_PASSWORD_SAME;
		} else if (strlen($user_password_new) < 8) {
			$this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;
			// if database connection opened
		} else if ($this->databaseConnection()) {
			// now it gets a little bit crazy: check if we have a constant HASH_COST_FACTOR defined (in config/hashing.php),
			// if so: put the value into $hash_cost_factor, if not, make $hash_cost_factor = null
			$hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);
      $user_password_hash = password_hash($user_password_new , PASSWORD_DEFAULT, ['cost' => $hash_cost_factor]);
      // faccio la verifica della vecchia
      $query_user = $this->db_connection->prepare("SELECT * FROM " . DB_TABLE_PREFIX . '_admin WHERE user_name = :user_name');
      $query_user->bindValue(':user_name', trim($user_name), PDO::PARAM_STR);
      $query_user->execute();
      $userdata = $query_user->fetchObject();
      if (password_verify( $user_password  , $userdata->user_password_hash )){ // verifico la vecchia password
        // sostituisco aes_key
        $prepared_key = openssl_pbkdf2($user_password.$user_name, AES_KEY_SALT, 16, 1000, "sha256");
        $old_aes_key = openssl_decrypt(base64_decode($userdata->aes_key),"AES-128-CBC",$prepared_key,OPENSSL_RAW_DATA, AES_KEY_IV);
        $prepared_key = openssl_pbkdf2($user_password_new.$user_name, AES_KEY_SALT, 16, 1000, "sha256");
        $aes_key = base64_encode(openssl_encrypt($old_aes_key,"AES-128-CBC",$prepared_key,OPENSSL_RAW_DATA, AES_KEY_IV));
        // write users new hash into database
        $query_update = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin SET user_password_hash = :user_password_hash,
                              user_password_reset_hash = NULL, user_password_reset_timestamp = NULL, aes_key = :aes_key, datpas = NOW()
                              WHERE user_name = :user_name');
        $query_update->bindValue(':user_password_hash', $user_password_hash, PDO::PARAM_STR);
        $query_update->bindValue(':aes_key', $aes_key, PDO::PARAM_STR); // con aes_key controllo se effettivamente l'utente ha cambiato la password
        $query_update->bindValue(':user_name', $user_name, PDO::PARAM_STR);
        $query_update->execute();
        // check if exactly one row was successfully changed:
        if ($query_update->rowCount() == 1) {
          $this->password_change_was_successful = true;
          $this->password_is_expired = 0;
          $this->messages[] = MESSAGE_PASSWORD_CHANGED_SUCCESSFULLY;
        } else {
          $this->errors[] = MESSAGE_PASSWORD_CHANGE_FAILED;
        }

      } else {
				$this->errors[] = MESSAGE_LOGIN_FAILED;
			}
		}
	}

	public function administratorChangeUsrPassword($user_password_new, $user_password_repeat)
	{
    $this->password_is_expired = 0;
		$user_name = $this->username_obj;
		if (empty($user_name) || empty($user_password_new) || empty($user_password_repeat)) {
			$this->errors[] = MESSAGE_PASSWORD_EMPTY;
			// is the repeat password identical to password
		} else if ($user_password_new !== $user_password_repeat) {
			$this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
			// password need to have a minimum length of 6 characters
		} else if (strlen($user_password_new) < 8) {
			$this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;
			// if database connection opened
		} else if ($this->databaseConnection()) {
			// now it gets a little bit crazy: check if we have a constant HASH_COST_FACTOR defined (in config/hashing.php),
			// if so: put the value into $hash_cost_factor, if not, make $hash_cost_factor = null
			$hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);
      $user_password_hash = password_hash($user_password_new , PASSWORD_DEFAULT, ['cost' => $hash_cost_factor]);
      // faccio la verifica della vecchia
      $query_user = $this->db_connection->prepare("SELECT * FROM " . DB_TABLE_PREFIX . '_admin WHERE user_name = :user_name');
      $query_user->bindValue(':user_name', trim($user_name), PDO::PARAM_STR);
      $query_user->execute();
      $userdata = $query_user->fetchObject();
      // sostituisco aes_key
      $prepared_key = openssl_pbkdf2($user_password_new.$user_name, AES_KEY_SALT, 16, 1000, "sha256");
      $aes_key = base64_encode(openssl_encrypt($_SESSION['aes_key'],"AES-128-CBC",$prepared_key,OPENSSL_RAW_DATA, AES_KEY_IV));
      // write users new hash into database
      $query_update = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin SET user_password_hash = :user_password_hash,
                            user_password_reset_hash = NULL, user_password_reset_timestamp = NULL, aes_key = :aes_key, datpas = NOW()
                            WHERE user_name = :user_name');
      $query_update->bindValue(':user_password_hash', $user_password_hash, PDO::PARAM_STR);
      $query_update->bindValue(':aes_key', $aes_key, PDO::PARAM_STR); // con aes_key controllo se effettivamente l'utente ha cambiato la password
      $query_update->bindValue(':user_name', $user_name, PDO::PARAM_STR);
      $query_update->execute();
      // check if exactly one row was successfully changed:
      if ($query_update->rowCount() == 1) {
        $this->administrator_change_usr_password_was_successful = true;
        $this->messages[] = MESSAGE_PASSWORD_CHANGED_SUCCESSFULLY;
      } else {
        $this->errors[] = MESSAGE_PASSWORD_CHANGE_FAILED;
      }
		}
	}

	/**
	* Gets the success state of the password-reset-link-validation.
	* TODO: should be more like getPasswordResetLinkValidationStatus
	* @return boolean
	*/
	public function passwordResetLinkIsValid()
	{
		return $this->password_reset_link_is_valid;
	}

	/**
	* Gets the success state of the password-reset action.
	* TODO: should be more like getPasswordResetSuccessStatus
	* @return boolean
	*/
	public function passwordResetWasSuccessful()
	{
		return $this->password_reset_was_successful;
	}

	public function passwordChangeWasSuccessful()
	{
		return $this->password_change_was_successful;
	}

	public function administratorChangeUsrPasswordWasSuccessful()
	{
		return $this->administrator_change_usr_password_was_successful;
	}

	public function getUsernameObj()
	{
		return $this->username_obj;
	}

	public function getUsernameObjAbilit()
	{
		return $this->username_obj_abil;
	}


	/**
	* Gets the username
	* @return string username
	*/
	public function getUsername()
	{
		return $this->user_name;
	}

}
