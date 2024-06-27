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
 * Handles the user registration
 * @author Panique
 * @link https://www.php-login.net
 * @link https://github.com/panique/php-login-advanced/
 * @license https://opensource.org/licenses/MIT MIT License
 */
class Registration
{
    /**
     * @var object $db_connection The database connection
     */
    private $db_connection = null;
    /**
     * @var bool success state of registration
     */
    public $registration_successful = false;
    /**
     * @var bool success state of verification
     */
    public $verification_successful = false;
    /**
     * @var array collection of error messages
     */
    public $errors = array();
    /**
     * @var array collection of success / neutral messages
     */
    public $messages = array();

    /**
     * the function "__construct()" automatically starts whenever an object of this class is created,
     * you know, when you do "$login = new Login();"
     */
    public function __construct()
    {
        session_start();

        // if we have such a POST request, call the registerNewUser() method
        if (isset($_POST["register"])) {
            $this->registerNewUser($_POST['user_firstname'], $_POST['user_lastname'], $_POST['user_name'], $_POST['user_email'], $_POST['user_telephone'], $_POST['user_password_new'], $_POST['user_password_repeat'], $_POST["captcha"]);
        // if we have such a GET request, call the verifyNewUser() method
        } else if (isset($_GET["id"]) && isset($_GET["verification_code"])) {
            $this->verifyNewUser($_GET["id"], $_GET["verification_code"]);
        }
    }

    /**
     * Checks if database connection is opened and open it if not
     */
    private function databaseConnection()
    {
        // connection already opened
        if ($this->db_connection != null) {
            return true;
        } else {
            // create a database connection, using the constants from config/config.php
            try {
                // Generate a database connection, using the PDO connector
                // @see https://net.tutsplus.com/tutorials/php/why-you-should-be-using-phps-pdo-for-database-access/
                // Also important: We include the charset, as leaving it out seems to be a security issue:
                // @see https://wiki.hashphp.org/PDO_Tutorial_for_MySQL_Developers#Connecting_to_MySQL says:
                // "Adding the charset to the DSN is very important for security reasons,
                // most examples you'll see around leave it out. MAKE SURE TO INCLUDE THE CHARSET!"
                $this->db_connection = new PDO('mysql:host=' . DB_HOST . ';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
				$query_sql_mode = $this->db_connection->prepare("/*!50701 SET SESSION sql_mode='' */");
				$query_sql_mode->execute();
                return true;
            // If an error is catched, database connection failed
            } catch (PDOException $e) {
                $this->errors[] = MESSAGE_DATABASE_ERROR;
                return false;
            }
        }
    }

    /**
     * handles the entire registration process. checks all error possibilities, and creates a new user in the database if
     * everything is fine
     */
    private function registerNewUser($user_firstname, $user_lastname, $user_name, $user_email, $user_telephone, $user_password, $user_password_repeat, $captcha)
    {
        // we just remove extra space on username and email
        $user_name  = trim($user_name);
        $user_email = trim($user_email);

        // check provided data validity
        // TODO: check for "return true" case early, so put this first
        if (strtolower($captcha) != strtolower($_SESSION['captcha'])) {
            $this->errors[] = MESSAGE_CAPTCHA_WRONG;
        } elseif (empty($user_name)) {
            $this->errors[] = MESSAGE_USERNAME_EMPTY;
        } elseif (empty($user_password) || empty($user_password_repeat)) {
            $this->errors[] = MESSAGE_PASSWORD_EMPTY;
        } elseif ($user_password !== $user_password_repeat) {
            $this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
        } elseif (strlen($user_password) < 6) {
            $this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;
        } elseif (strlen($user_firstname) > 30 || strlen($user_firstname) < 2) {
            $this->errors[] = MESSAGE_FIRSTNAME_BAD_LENGTH;
        } elseif (strlen($user_lastname) > 30 || strlen($user_lastname) < 2) {
            $this->errors[] = MESSAGE_LASTNAME_BAD_LENGTH;
        } elseif (strlen($user_name) > 64 || strlen($user_name) < 2) {
            $this->errors[] = MESSAGE_USERNAME_BAD_LENGTH;
        } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $user_name)) {
            $this->errors[] = MESSAGE_USERNAME_INVALID;
        } elseif (empty($user_email)) {
            $this->errors[] = MESSAGE_EMAIL_EMPTY;
        } elseif (strlen($user_email) > 64) {
            $this->errors[] = MESSAGE_EMAIL_TOO_LONG;
        } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = MESSAGE_EMAIL_INVALID;

        // finally if all the above checks are ok
        } else if ($this->databaseConnection()) {
            // check if username or email already exists
            $query_check_user_name = $this->db_connection->prepare('SELECT user_name, user_email FROM ' . DB_TABLE_PREFIX . '_admin WHERE user_name=:user_name OR user_email=:user_email');
            $query_check_user_name->bindValue(':user_name', $user_name, PDO::PARAM_STR);
            $query_check_user_name->bindValue(':user_email', $user_email, PDO::PARAM_STR);
            $query_check_user_name->execute();
            $result = $query_check_user_name->fetchAll();

            // if username or/and email find in the database
            // TODO: this is really awful!
            if (count($result) > 0) {
                for ($i = 0; $i < count($result); $i++) {
                    $this->errors[] = ($result[$i]["user_name"] == $user_name) ? MESSAGE_USERNAME_EXISTS : MESSAGE_EMAIL_ALREADY_EXISTS;
                }
            } else {
                // check if we have a constant HASH_COST_FACTOR defined (in config/hashing.php),
                // if so: put the value into $hash_cost_factor, if not, make $hash_cost_factor = null
                $hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);

                // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 character hash string
                // the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using PHP 5.3/5.4, by the password hashing
                // compatibility library. the third parameter looks a little bit shitty, but that's how those PHP 5.5 functions
                // want the parameter: as an array with, currently only used with 'cost' => XX.
                $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));
                // generate random hash for email verification (40 char string)
                $user_activation_hash = sha1(uniqid(mt_rand(), true));

                // write new users data into database
                $query_new_user_insert = $this->db_connection->prepare('INSERT INTO ' . DB_TABLE_PREFIX . '_admin (user_firstname,  user_lastname,  user_name,  user_password_hash,  user_email,  user_telephone,  user_activation_hash,  user_registration_ip, user_registration_datetime) VALUES(:user_firstname, :user_lastname, :user_name, :user_password_hash, :user_email, :user_telephone, :user_activation_hash, :user_registration_ip, now())');
                $query_new_user_insert->bindValue(':user_firstname', $user_firstname, PDO::PARAM_STR);
                $query_new_user_insert->bindValue(':user_lastname', $user_lastname, PDO::PARAM_STR);
                $query_new_user_insert->bindValue(':user_name', $user_name, PDO::PARAM_STR);
                $query_new_user_insert->bindValue(':user_password_hash', $user_password_hash, PDO::PARAM_STR);
                $query_new_user_insert->bindValue(':user_email', $user_email, PDO::PARAM_STR);
                $query_new_user_insert->bindValue(':user_telephone', $user_telephone, PDO::PARAM_STR);
				$query_new_user_insert->bindValue(':user_activation_hash', $user_activation_hash, PDO::PARAM_STR);
                $query_new_user_insert->bindValue(':user_registration_ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
                $query_new_user_insert->execute();


                // id of new user
                $user_id = $this->db_connection->lastInsertId();

                if ($query_new_user_insert) {
                    // send a verification email
                    if ($this->sendVerificationEmail($user_id, $user_email, $user_activation_hash)) {
                        // when mail has been send successfully
                        $this->messages[] = MESSAGE_VERIFICATION_MAIL_SENT;
                        $this->registration_successful = true;
                    } else {
                        // delete this users account immediately, as we could not send a verification email
                        $query_delete_user = $this->db_connection->prepare('DELETE FROM ' . DB_TABLE_PREFIX . '_admin WHERE user_id=:user_id');
                        $query_delete_user->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                        $query_delete_user->execute();

                        $this->errors[] = MESSAGE_VERIFICATION_MAIL_ERROR;
                    }
                } else {
                    $this->errors[] = MESSAGE_REGISTRATION_FAILED;
                }
            }
        }
    }

    /*
     * sends an email to the provided email address
     * @return boolean gives back true if mail has been sent, gives back false if no mail could been sent
     */
    public function sendVerificationEmail($user_id, $user_email, $user_activation_hash)
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
        // Impropriamente uso order_mail in quanto nelle installazioni didattiche non si ricevono ordini
        $mail->From = $this->email_conf['admin_mail'];

        //$mail->From = EMAIL_VERIFICATION_FROM;
        $mail->FromName = EMAIL_VERIFICATION_FROM_NAME;
        $mail->AddAddress($user_email);
        $mail->Subject = EMAIL_VERIFICATION_SUBJECT;

        $link = EMAIL_VERIFICATION_URL . '?id=' . urlencode($user_id) . '&verification_code=' . urlencode($user_activation_hash);

        // the link to your register.php, please set this value in config/email_verification.php
        $mail->AddEmbeddedImage('../../library/images/gazie.gif', 'glogo');
        $mail->Body = EMAIL_VERIFICATION_CONTENT . '<br> <img height="64" src="cid:glogo" /> <a href="' . $link.'"> <img src="cid:gazie" /> '.MESSAGE_EMAIL_LINK_FOR_VERIFYNG.'</a>';

        if(!$mail->Send()) {
            $this->errors[] = MESSAGE_VERIFICATION_MAIL_NOT_SENT . $mail->ErrorInfo;
            return false;
        } else {
            return true;
        }
    }

    /**
     * checks the id/verification code combination and set the user's activation status to true (=1) in the database
     */
    public function verifyNewUser($user_id, $user_activation_hash)
    {
        // if database connection opened
        if ($this->databaseConnection()) {
            // try to update user with specified information
            $query_update_user = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_admin SET user_active = 1, user_activation_hash = NULL WHERE user_id = :user_id AND user_activation_hash = :user_activation_hash');
            $query_update_user->bindValue(':user_id', intval(trim($user_id)), PDO::PARAM_INT);
            $query_update_user->bindValue(':user_activation_hash', $user_activation_hash, PDO::PARAM_STR);
            $query_update_user->execute();

            if ($query_update_user->rowCount() > 0) {
                $this->verification_successful = true;
                $this->messages[] = MESSAGE_REGISTRATION_ACTIVATION_SUCCESSFUL;
            } else {
                $this->errors[] = MESSAGE_REGISTRATION_ACTIVATION_NOT_SUCCESSFUL;
            }
        }
    }
}
