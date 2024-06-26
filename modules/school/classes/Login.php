<?php

/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
  (http://www.devincentiis.it)
  <http://gazie.sourceforge.net>
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
 * @link http://www.php-login.net
 * @link https://github.com/panique/php-login-advanced/
 * @license http://opensource.org/licenses/MIT MIT License
 */
class Login {

    /**
     * @var object $db_connection The database connection
     */
    private $db_connection = null;

    /**
     * @var int $student_id The user's id
     */
    private $student_id = null;

    /**
     * @var string $student_name The user's name
     */
    private $student_name = "";

    /**
     * @var string $student_email The user's mail
     */
    private $student_email = "";

    /**
     * @var boolean $student_is_logged_in The user's login status
     */
    private $student_is_logged_in = false;

    /**
     * @var string $student_gravatar_image_url The user's gravatar profile pic url (or a default one)
     */
    public $student_gravatar_image_url = "";

    /**
     * @var string $student_gravatar_image_tag The user's gravatar profile pic url with <img ... /> around
     */
    public $student_gravatar_image_tag = "";

    /**
     * @var boolean $password_reset_link_is_valid Marker for view handling
     */
    private $password_reset_link_is_valid = false;

    /**
     * @var boolean $password_reset_was_successful Marker for view handling
     */
    private $password_reset_was_successful = false;

    /**
     * @var array $errors Collection of error messages
     */
    public $errors = array();

    /**
     * @var array $messages Collection of success / neutral messages
     */
    public $messages = array();

    /**
     * the function "__construct()" automatically starts whenever an object of this class is created,
     * you know, when you do "$login = new Login();"
     */
    public function __construct() {
        // create/read session
        // session_start();
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
        } elseif (!empty($_SESSION['student_name']) && ($_SESSION['student_logged_in'] == 1)) {
            $this->loginWithSessionData();

            // checking for form submit from editing screen
            // user try to change his username
            if (isset($_POST["student_edit_submit_name"])) {
                // function below uses use $_SESSION['student_id'] et $_SESSION['student_email']
                $this->editUserName($_POST['student_name']);
                // user try to change his email
            } elseif (isset($_POST["student_edit_submit_email"])) {
                // function below uses use $_SESSION['student_id'] et $_SESSION['student_email']
                $this->editUserEmail($_POST['student_email']);
                // user try to change his password
            } elseif (isset($_POST["student_edit_submit_password"])) {
                // function below uses $_SESSION['student_name'] and $_SESSION['student_id']
                $this->editUserPassword($_POST['student_password_old'], $_POST['student_password_new'], $_POST['student_password_repeat']);
            }

            // login with cookie
        } elseif (isset($_COOKIE['rememberme'])) {
            $this->loginWithCookieData();

            // if user just submitted a login form
        } elseif (isset($_POST["login"])) {
            if (!isset($_POST['student_rememberme'])) {
                $_POST['student_rememberme'] = null;
            }
            $this->loginWithPostData($_POST['student_name'], $_POST['student_password'], $_POST['student_rememberme']);
        }

        // checking if user requested a password reset mail
        if (isset($_POST["request_password_reset"]) && isset($_POST['student_name'])) {
            $this->setPasswordResetDatabaseTokenAndSendMail($_POST['student_name']);
        } elseif (isset($_GET["student_name"]) && isset($_GET["verification_code"])) {
            $this->checkIfEmailVerificationCodeIsValid($_GET["student_name"], $_GET["verification_code"]);
        } elseif (isset($_POST["submit_new_password"])) {
            $this->editNewPassword($_POST['student_name'], $_POST['student_password_reset_hash'], $_POST['student_password_new'], $_POST['student_password_repeat']);
        }

        // get gravatar profile picture if user is logged in
        if ($this->isUserLoggedIn() == true) {
            $this->getGravatarImageUrl($this->student_email);
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
                // @see http://net.tutsplus.com/tutorials/php/why-you-should-be-using-phps-pdo-for-database-access/
                // Also important: We include the charset, as leaving it out seems to be a security issue:
                // @see http://wiki.hashphp.org/PDO_Tutorial_for_MySQL_Developers#Connecting_to_MySQL says:
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
     * Search into database for the user data of student_name specified as parameter
     * @return user data as an object if existing user
     * @return false if student_name is not found in the database
     * TODO: @devplanete This returns two different types. Maybe this is valid, but it feels bad. We should rework this.
     * TODO: @devplanete After some resarch I'm VERY sure that this is not good coding style! Please fix this.
     */
    private function getUserData($student_name) {
        // if database connection opened
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_user = $this->db_connection->prepare('SELECT * FROM ' . DB_TABLE_PREFIX . '_students WHERE student_name = :student_name');
            $query_user->bindValue(':student_name', $student_name, PDO::PARAM_STR);
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
    private function loginWithSessionData() {
        $this->student_name = $_SESSION['student_name'];
        $this->student_email = $_SESSION['student_email'];

        // set logged in status to true, because we just checked for this:
        // !empty($_SESSION['student_name']) && ($_SESSION['student_logged_in'] == 1)
        // when we called this method (in the constructor)
        $this->student_is_logged_in = true;
    }
    // recupero ip chiamante
    private function getUserIP() {
        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = $_SERVER['REMOTE_ADDR'];
        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }
        return $ip;
    }

    /**
     * Logs in via the Cookie
     * @return bool success state of cookie login
     */
    private function loginWithCookieData() {
        if (isset($_COOKIE['rememberme'])) {
            // extract data from the cookie
            list ($student_id, $token, $hash) = explode(':', $_COOKIE['rememberme']);
            // check cookie hash validity
            if ($hash == hash('sha256', $student_id . ':' . $token . COOKIE_SECRET_KEY) && !empty($token)) {
                // cookie looks good, try to select corresponding user
                if ($this->databaseConnection()) {
                    // get real token from database (and all other data)
                    $sth = $this->db_connection->prepare('SELECT student_id, student_name, student_email FROM ' . DB_TABLE_PREFIX . '_students WHERE student_id = :student_id
                                                      AND student_rememberme_token = :student_rememberme_token AND student_rememberme_token IS NOT NULL');
                    $sth->bindValue(':student_id', $student_id, PDO::PARAM_INT);
                    $sth->bindValue(':student_rememberme_token', $token, PDO::PARAM_STR);
                    $sth->execute();
                    // get result row (as an object)
                    $result_row = $sth->fetchObject();
                    if (isset($result_row->student_id)) {


                        // write user data into PHP SESSION [a file on your server]
                        $_SESSION['table_prefix'] = DB_TABLE_PREFIX . str_pad($result_row->student_id, 4, '0', STR_PAD_LEFT);
                        $_SESSION['student_id'] = $result_row->student_id;
                        $_SESSION['student_name'] = $result_row->student_name;
                        $_SESSION["user_name"] = $result_row->student_name;
                        $_SESSION['student_email'] = $result_row->student_email;
                        $_SESSION['company_id'] = 1;
                        $_SESSION['student_logged_in'] = 1;

                        // declare user id, set the login status to true
                        $this->student_id = $result_row->student_id;
                        $this->student_name = $result_row->student_name;
                        $this->student_email = $result_row->student_email;
                        $this->student_is_logged_in = true;

						// INIZIO ---- ripresa del valore del tema (g6,g7,lte)
						$rt = $this->db_connection->prepare('SELECT var_value FROM ' . DB_TABLE_PREFIX . str_pad($result_row->student_id, 4, '0', STR_PAD_LEFT) . '_admin_config WHERE var_name = \'theme\' AND adminid = :user_name');
						$rt->bindValue(':user_name', $result_row->student_name, PDO::PARAM_STR);
						$rt->execute();
						// get result row (as an object)
						$rt_row = $rt->fetchObject();
						$_SESSION['theme'] = $rt_row->var_value;
						// FINE ---- ripresa del valore del tema (g6,g7,lte)

						// increment the login counter for that user
						$acc = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX. str_pad($result_row->student_id, 4, '0', STR_PAD_LEFT) . '_admin '
						. " SET Access = Access+1, last_ip = '". $this->getUserIP()."' WHERE user_name = :user_name ;");
						$acc->execute(array(':user_name' => $result_row->student_name));

                        // Cookie token usable only once
                        $this->newRememberMeCookie();
                        return true;
                    }
                }
            }
            // A cookie has been used but is not valid... we delete it
            $this->deleteRememberMeCookie();
            $this->errors[] = MESSAGE_COOKIE_INVALID;
        }
        return false;
    }

    /**
     * Logs in with the data provided in $_POST, coming from the login form
     * @param $student_name
     * @param $student_password
     * @param $student_rememberme
     */
    private function loginWithPostData($student_name, $student_password, $student_rememberme) {
        if (empty($student_name)) {
            $this->errors[] = MESSAGE_USERNAME_EMPTY;
        } else if (empty($student_password)) {
            $this->errors[] = MESSAGE_PASSWORD_EMPTY;

            // if POST data (from login form) contains non-empty student_name and non-empty student_password
        } else {
            // user can login with his username or his email address.
            // if user has not typed a valid email address, we try to identify him with his student_name
            if (!filter_var($student_name, FILTER_VALIDATE_EMAIL)) {
                // database query, getting all the info of the selected user
                $result_row = $this->getUserData(trim($student_name));

                // if user has typed a valid email address, we try to identify him with his student_email
            } else if ($this->databaseConnection()) {
                // database query, getting all the info of the selected user
                $query_user = $this->db_connection->prepare('SELECT * FROM ' . DB_TABLE_PREFIX . '_students WHERE student_email = :student_email');
                $query_user->bindValue(':student_email', trim($student_name), PDO::PARAM_STR);
                $query_user->execute();
                // get result row (as an object)
                $result_row = $query_user->fetchObject();
            }

            // if this user not exists
            if (!isset($result_row->student_id)) {
                // was MESSAGE_USER_DOES_NOT_EXIST before, but has changed to MESSAGE_LOGIN_FAILED
                // to prevent potential attackers showing if the user exists
                $this->errors[] = MESSAGE_LOGIN_FAILED;
            } else if (($result_row->student_failed_logins >= 3) && ($result_row->student_last_failed_login > (time() - 30))) {
                $this->errors[] = MESSAGE_PASSWORD_WRONG_3_TIMES;
                // using PHP 5.5's password_verify() function to check if the provided passwords fits to the hash of that user's password
            } else if (!password_verify($student_password, $result_row->student_password_hash)) {
                // increment the failed login counter for that user
                $sth = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_students '
                        . 'SET student_failed_logins = student_failed_logins+1, student_last_failed_login = :student_last_failed_login '
                        . 'WHERE student_name = :student_name OR student_email = :student_name');
                $sth->execute(array(':student_name' => $student_name, ':student_last_failed_login' => time()));

                $this->errors[] = MESSAGE_PASSWORD_WRONG;
                // has the user activated their account with the verification email
            } else if ($result_row->student_active != 1) {
                $this->errors[] = MESSAGE_ACCOUNT_NOT_ACTIVATED;
            } else {

                // write user data into PHP SESSION [a file on your server]
                $_SESSION['table_prefix'] = DB_TABLE_PREFIX . str_pad($result_row->student_id, 4, '0', STR_PAD_LEFT);
                $_SESSION['student_id'] = $result_row->student_id;
                $_SESSION['student_name'] = $result_row->student_name;
                $_SESSION["user_name"] = $result_row->student_name;
                $_SESSION['student_email'] = $result_row->student_email;
                $_SESSION['company_id'] = 1;
                $_SESSION['student_logged_in'] = 1;
                // declare user id, set the login status to true
                $this->student_id = $result_row->student_id;
                $this->student_name = $result_row->student_name;
                $this->student_email = $result_row->student_email;
                $this->student_is_logged_in = true;

				// INIZIO ---- ripresa del valore del tema (g6,g7,lte)
				$rt = $this->db_connection->prepare('SELECT var_value FROM ' . DB_TABLE_PREFIX. str_pad($result_row->student_id, 4, '0', STR_PAD_LEFT) . '_admin_config WHERE var_name = \'theme\' AND adminid = :user_name');
				$rt->bindValue(':user_name', $result_row->student_name, PDO::PARAM_STR);
				$rt->execute();
				// get result row (as an object)
				$rt_row = $rt->fetchObject();
				$_SESSION['theme'] = $rt_row->var_value;
				// FINE ---- ripresa del valore del tema (g6,g7,lte)

				// increment the login counter for that user
				$acc = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . str_pad($result_row->student_id, 4, '0', STR_PAD_LEFT) . '_admin '
				. " SET Access = Access+1, last_ip = '". $this->getUserIP()."' WHERE user_name = :user_name ;");
				$acc->execute(array(':user_name' => $result_row->student_name));


                // reset the failed login counter for that user
                $sth = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_students '
                        . 'SET student_failed_logins = 0, student_last_failed_login = NULL '
                        . 'WHERE student_id = :student_id AND student_failed_logins != 0');
                $sth->execute(array(':student_id' => $result_row->student_id));

                // if user has check the "remember me" checkbox, then generate token and write cookie
                if (isset($student_rememberme)) {
                    $this->newRememberMeCookie();
                } else {
                    // Reset remember-me token
                    $this->deleteRememberMeCookie();
                }

                // OPTIONAL: recalculate the user's password hash
                // DELETE this if-block if you like, it only exists to recalculate gaz_students's hashes when you provide a cost factor,
                // by default the script will use a cost factor of 10 and never change it.
                // check if the have defined a cost factor in config/hashing.php
                if (defined('HASH_COST_FACTOR')) {
                    // check if the hash needs to be rehashed
                    if (password_needs_rehash($result_row->student_password_hash, PASSWORD_DEFAULT, array('cost' => HASH_COST_FACTOR))) {

                        // calculate new hash with new cost factor
                        $student_password_hash = password_hash($student_password, PASSWORD_DEFAULT, array('cost' => HASH_COST_FACTOR));

                        // TODO: this should be put into another method !?
                        $query_update = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_students SET student_password_hash = :student_password_hash WHERE student_id = :student_id');
                        $query_update->bindValue(':student_password_hash', $student_password_hash, PDO::PARAM_STR);
                        $query_update->bindValue(':student_id', $result_row->student_id, PDO::PARAM_INT);
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
    private function newRememberMeCookie() {
        // if database connection opened
        if ($this->databaseConnection()) {
            // generate 64 char random string and store it in current user data
            $random_token_string = hash('sha256', mt_rand());
            $sth = $this->db_connection->prepare("UPDATE " . DB_TABLE_PREFIX . "_students SET student_rememberme_token = :student_rememberme_token WHERE student_id = :student_id");
            $sth->execute(array(':student_rememberme_token' => $random_token_string, ':student_id' => $_SESSION['student_id']));

            // generate cookie string that consists of userid, randomstring and combined hash of both
            $cookie_string_first_part = $_SESSION['student_id'] . ':' . $random_token_string;
            $cookie_string_hash = hash('sha256', $cookie_string_first_part . COOKIE_SECRET_KEY);
            $cookie_string = $cookie_string_first_part . ':' . $cookie_string_hash;

            // set cookie
            setcookie('rememberme', $cookie_string, time() + COOKIE_RUNTIME, "/", COOKIE_DOMAIN);
        }
    }

    /**
     * Delete all data needed for remember me cookie connection on client and server side
     */
    private function deleteRememberMeCookie() {
        // if database connection opened
        if ($this->databaseConnection()) {
            // Reset rememberme token
            $sth = $this->db_connection->prepare("UPDATE " . DB_TABLE_PREFIX . "_students SET student_rememberme_token = NULL WHERE student_id = :student_id");
            $sth->execute(array(':student_id' => $_SESSION['student_id']));
        }

        // set the rememberme-cookie to ten years ago (3600sec * 365 days * 10).
        // that's obivously the best practice to kill a cookie via php
        // @see http://stackoverflow.com/a/686166/1114320
        setcookie('rememberme', false, time() - (3600 * 3650), '/', COOKIE_DOMAIN);
    }

    /**
     * Perform the logout, resetting the session
     */
    public function doLogout() {
        $this->deleteRememberMeCookie();

        $_SESSION = array();
        session_destroy();

        $this->student_is_logged_in = false;
        $this->messages[] = MESSAGE_LOGGED_OUT;
    }

    /**
     * Simply return the current state of the user's login
     * @return bool user's login status
     */
    public function isUserLoggedIn() {
        return $this->student_is_logged_in;
    }

    /**
     * Edit the user's name, provided in the editing form
     */
    public function editUserName($student_name) {
        // prevent database flooding
        $student_name = substr(trim($student_name), 0, 64);

        if (!empty($student_name) && $student_name == $_SESSION['student_name']) {
            $this->errors[] = MESSAGE_USERNAME_SAME_LIKE_OLD_ONE;

            // username cannot be empty and must be azAZ09 and 2-64 characters
            // TODO: maybe this pattern should also be implemented in Registration.php (or other way round)
        } elseif (empty($student_name) || !preg_match("/^(?=.{2,64}$)[a-zA-Z][a-zA-Z0-9]*(?: [a-zA-Z0-9]+)*$/", $student_name)) {
            $this->errors[] = MESSAGE_USERNAME_INVALID;
        } else {
            // check if new username already exists
            $result_row = $this->getUserData($student_name);

            if (isset($result_row->student_id)) {
                $this->errors[] = MESSAGE_USERNAME_EXISTS;
            } else {
                // write user's new data into database
                $query_edit_student_name = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_students SET student_name = :student_name WHERE student_id = :student_id');
                $query_edit_student_name->bindValue(':student_name', $student_name, PDO::PARAM_STR);
                $query_edit_student_name->bindValue(':student_id', $_SESSION['student_id'], PDO::PARAM_INT);
                $query_edit_student_name->execute();

                if ($query_edit_student_name->rowCount()) {
                    $_SESSION['student_name'] = $student_name;
                    $this->messages[] = MESSAGE_USERNAME_CHANGED_SUCCESSFULLY . $student_name;
                } else {
                    $this->errors[] = MESSAGE_USERNAME_CHANGE_FAILED;
                }
            }
        }
    }

    /**
     * Edit the user's email, provided in the editing form
     */
    public function editUserEmail($student_email) {
        // prevent database flooding
        $student_email = substr(trim($student_email), 0, 64);

        if (!empty($student_email) && $student_email == $_SESSION["student_email"]) {
            $this->errors[] = MESSAGE_EMAIL_SAME_LIKE_OLD_ONE;
            // user mail cannot be empty and must be in email format
        } elseif (empty($student_email) || !filter_var($student_email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = MESSAGE_EMAIL_INVALID;
        } else if ($this->databaseConnection()) {
            // check if new email already exists
            $query_user = $this->db_connection->prepare('SELECT * FROM ' . DB_TABLE_PREFIX . '_students WHERE student_email = :student_email');
            $query_user->bindValue(':student_email', $student_email, PDO::PARAM_STR);
            $query_user->execute();
            // get result row (as an object)
            $result_row = $query_user->fetchObject();

            // if this email exists
            if (isset($result_row->student_id)) {
                $this->errors[] = MESSAGE_EMAIL_ALREADY_EXISTS;
            } else {
                // write ' . DB_TABLE_PREFIX .'_students new data into database
                $query_edit_student_email = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_students SET student_email = :student_email WHERE student_id = :student_id');
                $query_edit_student_email->bindValue(':student_email', $student_email, PDO::PARAM_STR);
                $query_edit_student_email->bindValue(':student_id', $_SESSION['student_id'], PDO::PARAM_INT);
                $query_edit_student_email->execute();

                if ($query_edit_student_email->rowCount()) {
                    $_SESSION['student_email'] = $student_email;
                    $this->messages[] = MESSAGE_EMAIL_CHANGED_SUCCESSFULLY . $student_email;
                } else {
                    $this->errors[] = MESSAGE_EMAIL_CHANGE_FAILED;
                }
            }
        }
    }

    /**
     * Edit the user's password, provided in the editing form
     */
    public function editUserPassword($student_password_old, $student_password_new, $student_password_repeat) {
        if (empty($student_password_new) || empty($student_password_repeat) || empty($student_password_old)) {
            $this->errors[] = MESSAGE_PASSWORD_EMPTY;
            // is the repeat password identical to password
        } elseif ($student_password_new !== $student_password_repeat) {
            $this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
            // password need to have a minimum length of 6 characters
        } elseif (strlen($student_password_new) < 6) {
            $this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;

            // all the above tests are ok
        } else {
            // database query, getting hash of currently logged in user (to check with just provided password)
            $result_row = $this->getUserData($_SESSION['student_name']);

            // if this user exists
            if (isset($result_row->student_password_hash)) {

                // using PHP 5.5's password_verify() function to check if the provided passwords fits to the hash of that user's password
                if (password_verify($student_password_old, $result_row->student_password_hash)) {

                    // now it gets a little bit crazy: check if we have a constant HASH_COST_FACTOR defined (in config/hashing.php),
                    // if so: put the value into $hash_cost_factor, if not, make $hash_cost_factor = null
                    $hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);

                    // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 character hash string
                    // the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using PHP 5.3/5.4, by the password hashing
                    // compatibility library. the third parameter looks a little bit shitty, but that's how those PHP 5.5 functions
                    // want the parameter: as an array with, currently only used with 'cost' => XX.
                    $student_password_hash = password_hash($student_password_new, PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));

                    // write ' . DB_TABLE_PREFIX .'_students new hash into database
                    $query_update = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_students SET student_password_hash = :student_password_hash WHERE student_id = :student_id');
                    $query_update->bindValue(':student_password_hash', $student_password_hash, PDO::PARAM_STR);
                    $query_update->bindValue(':student_id', $_SESSION['student_id'], PDO::PARAM_INT);
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
    public function setPasswordResetDatabaseTokenAndSendMail($student_name) {
        $student_name = trim($student_name);

        if (empty($student_name)) {
            $this->errors[] = MESSAGE_USERNAME_EMPTY;
        } else {
            // generate timestamp (to see when exactly the user (or an attacker) requested the password reset mail)
            // btw this is an integer ;)
            $temporary_timestamp = time();
            // generate random hash for email password reset verification (40 char string)
            $student_password_reset_hash = sha1(uniqid(mt_rand(), true));
            // database query, getting all the info of the selected user
            $result_row = $this->getUserData($student_name);

            // if this user exists
            if (isset($result_row->student_id)) {

                // database query:
                $query_update = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_students SET student_password_reset_hash = :student_password_reset_hash,
                                                               student_password_reset_timestamp = :student_password_reset_timestamp
                                                               WHERE student_name = :student_name');
                $query_update->bindValue(':student_password_reset_hash', $student_password_reset_hash, PDO::PARAM_STR);
                $query_update->bindValue(':student_password_reset_timestamp', $temporary_timestamp, PDO::PARAM_INT);
                $query_update->bindValue(':student_name', $student_name, PDO::PARAM_STR);
                $query_update->execute();

                // check if exactly one row was successfully changed:
                if ($query_update->rowCount() == 1) {
                    // send a mail to the user, containing a link with that token hash string
                    $this->sendPasswordResetMail($student_name, $result_row->student_email, $student_password_reset_hash);
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
    public function sendPasswordResetMail($student_name, $student_email, $student_password_reset_hash) {
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
        $mail->AddAddress($student_email);
        $mail->Subject = EMAIL_PASSWORDRESET_SUBJECT;
        $mail->AddEmbeddedImage('./school.png', 'gschool');
        $mail->AddEmbeddedImage('../../library/images/logo_180x180.png', 'glogo');

        $link = EMAIL_PASSWORDRESET_URL . '?student_name=' . urlencode($student_name) . '&verification_code=' . urlencode($student_password_reset_hash);
        $mail->Body = EMAIL_PASSWORDRESET_CONTENT . '<br> <img height="64" src="cid:glogo" /> <a href="' . $link . '"> <img src="cid:gschool" /> ' . MESSAGE_EMAIL_LINK_FOR_RESET . '</a>';
        ;

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
    public function checkIfEmailVerificationCodeIsValid($student_name, $verification_code) {
        $student_name = trim($student_name);

        if (empty($student_name) || empty($verification_code)) {
            $this->errors[] = MESSAGE_LINK_PARAMETER_EMPTY;
        } else {
            // database query, getting all the info of the selected user
            $result_row = $this->getUserData($student_name);

            // if this user exists and have the same hash in database
            if (isset($result_row->student_id) && $result_row->student_password_reset_hash == $verification_code) {

                $timestamp_one_hour_ago = time() - 3600; // 3600 seconds are 1 hour

                if ($result_row->student_password_reset_timestamp > $timestamp_one_hour_ago) {
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
    public function editNewPassword($student_name, $student_password_reset_hash, $student_password_new, $student_password_repeat) {
        // TODO: timestamp!
        $student_name = trim($student_name);

        if (empty($student_name) || empty($student_password_reset_hash) || empty($student_password_new) || empty($student_password_repeat)) {
            $this->errors[] = MESSAGE_PASSWORD_EMPTY;
            // is the repeat password identical to password
        } else if ($student_password_new !== $student_password_repeat) {
            $this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
            // password need to have a minimum length of 6 characters
        } else if (strlen($student_password_new) < 6) {
            $this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;
            // if database connection opened
        } else if ($this->databaseConnection()) {
            // now it gets a little bit crazy: check if we have a constant HASH_COST_FACTOR defined (in config/hashing.php),
            // if so: put the value into $hash_cost_factor, if not, make $hash_cost_factor = null
            $hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);

            // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 character hash string
            // the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using PHP 5.3/5.4, by the password hashing
            // compatibility library. the third parameter looks a little bit shitty, but that's how those PHP 5.5 functions
            // want the parameter: as an array with, currently only used with 'cost' => XX.
            $student_password_hash = password_hash($student_password_new, PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));

            // write ' . DB_TABLE_PREFIX .'_students new hash into database
            $query_update = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_students SET student_password_hash = :student_password_hash,
                                                           student_password_reset_hash = NULL, student_password_reset_timestamp = NULL
                                                           WHERE student_name = :student_name AND student_password_reset_hash = :student_password_reset_hash');
            $query_update->bindValue(':student_password_hash', $student_password_hash, PDO::PARAM_STR);
            $query_update->bindValue(':student_password_reset_hash', $student_password_reset_hash, PDO::PARAM_STR);
            $query_update->bindValue(':student_name', $student_name, PDO::PARAM_STR);
            $query_update->execute();

            // check if exactly one row was successfully changed:
            if ($query_update->rowCount() == 1) {
                $this->password_reset_was_successful = true;
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
    public function passwordResetLinkIsValid() {
        return $this->password_reset_link_is_valid;
    }

    /**
     * Gets the success state of the password-reset action.
     * TODO: should be more like getPasswordResetSuccessStatus
     * @return boolean
     */
    public function passwordResetWasSuccessful() {
        return $this->password_reset_was_successful;
    }

    /**
     * Gets the username
     * @return string username
     */
    public function getUsername() {
        return $this->student_name;
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     * Gravatar is the #1 (free) provider for email address based global avatar hosting.
     * The URL (or image) returns always a .jpg file !
     * For deeper info on the different parameter possibilities:
     * @see http://de.gravatar.com/site/implement/images/
     *
     * @param string $email The email address
     * @param string $s Size in pixels, defaults to 50px [ 1 - 2048 ]
     * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
     * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
     * @param array $atts Optional, additional key/value attributes to include in the IMG tag
     * @source http://gravatar.com/site/implement/images/php/
     */
    public function getGravatarImageUrl($email, $s = 50, $d = 'mm', $r = 'g', $atts = array()) {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        //$url .= "?s=$s&d=$d&r=$r&f=y";
        // the image url (on gravatarr servers), will return in something like
        // http://www.gravatar.com/avatar/205e460b479e2e5b48aec07710c08d50?s=80&d=mm&r=g
        // note: the url does NOT have something like .jpg
        $this->student_gravatar_image_url = $url;

        // build img tag around
        $url = '<img src="' . $url . '"';
        foreach ($atts as $key => $val)
            $url .= ' ' . $key . '="' . $val . '"';
        $url .= ' />';

        // the image url like above but with an additional <img src .. /> around
        $this->student_gravatar_image_tag = $url;
    }

}
