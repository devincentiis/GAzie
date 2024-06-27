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
class Registration {

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
    public function __construct() {
        session_start();
        // if we have such a POST request, call the registerNewUser() method
        if (isset($_POST["register"])) {
            $this->registerNewUser($_POST['student_classroom_id'], $_POST['student_firstname'], $_POST['student_lastname'], $_POST['student_name'], $_POST['student_email'], $_POST['student_telephone'], $_POST['student_password_new'], $_POST['student_password_repeat'], $_POST["captcha"]);
            // if we have such a GET request, call the verifyNewUser() method
        } else if (isset($_GET["id"]) && isset($_GET["verification_code"])) {
            $this->verifyNewUser($_GET["id"], $_GET["verification_code"]);
        }
    }

    /**
     * Checks if database connection is opened and open it if not
     */
    private function databaseConnection() {
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
                $this->db_connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
				$query_select_classroom = $this->db_connection->prepare("/*!50701 SET SESSION sql_mode='' */");
				$query_select_classroom->execute();
                return true;
                // If an error is catched, database connection failed
            } catch (PDOException $e) {
                $this->errors[] = MESSAGE_DATABASE_ERROR;
                return false;
            }
        }
    }

    public function select_classroom() {
        $this->databaseConnection();
        $query_select_classroom = $this->db_connection->prepare('SELECT * FROM ' . DB_TABLE_PREFIX . '_classroom LEFT JOIN ' . DB_TABLE_PREFIX . '_admin ON ' . DB_TABLE_PREFIX . '_classroom.teacher = ' . DB_TABLE_PREFIX . '_admin.user_name');
        $query_select_classroom->execute();
        $this->classroom_data = $query_select_classroom->fetchAll();
    }

    /**
     * handles the entire registration process. checks all error possibilities, and creates a new user in the database if
     * everything is fine
     */
    private function registerNewUser($student_classroom_id, $student_firstname, $student_lastname, $student_name, $student_email, $student_telephone, $student_password, $student_password_repeat, $captcha) {
        // we just remove extra space on username and email
        $student_classroom_id = trim($student_classroom_id);
        $student_name = trim($student_name);
        $student_email = trim($student_email);

        // check provided data validity
        // TODO: check for "return true" case early, so put this first
        if (strtolower($captcha) != strtolower($_SESSION['captcha'])) {
            $this->errors[] = MESSAGE_CAPTCHA_WRONG;
        } elseif (empty($student_name)) {
            $this->errors[] = MESSAGE_USERNAME_EMPTY;
        } elseif (empty($student_password) || empty($student_password_repeat)) {
            $this->errors[] = MESSAGE_PASSWORD_EMPTY;
        } elseif ($student_password !== $student_password_repeat) {
            $this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
        } elseif (strlen($student_password) < 6) {
            $this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;
        } elseif (strlen($student_firstname) > 30 || strlen($student_firstname) < 2) {
            $this->errors[] = MESSAGE_FIRSTNAME_BAD_LENGTH;
        } elseif (strlen($student_lastname) > 30 || strlen($student_lastname) < 2) {
            $this->errors[] = MESSAGE_LASTNAME_BAD_LENGTH;
        } elseif (strlen($student_name) > 64 || strlen($student_name) < 2) {
            $this->errors[] = MESSAGE_USERNAME_BAD_LENGTH;
        } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $student_name)) {
            $this->errors[] = MESSAGE_USERNAME_INVALID;
        } elseif (empty($student_email)) {
            $this->errors[] = MESSAGE_EMAIL_EMPTY;
        } elseif ($student_classroom_id < 1) {
            $this->errors[] = MESSAGE_CLASSROOM_EMPTY;
        } elseif (strlen($student_email) > 64) {
            $this->errors[] = MESSAGE_EMAIL_TOO_LONG;
        } elseif (!filter_var($student_email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = MESSAGE_EMAIL_INVALID;

            // finally if all the above checks are ok
        } else if ($this->databaseConnection()) {
            // check if username or email already exists
            $query_check_student_name = $this->db_connection->prepare('SELECT student_name, student_email FROM ' . DB_TABLE_PREFIX . '_students WHERE student_name=:student_name OR student_email=:student_email');
            $query_check_student_name->bindValue(':student_name', $student_name, PDO::PARAM_STR);
            $query_check_student_name->bindValue(':student_email', $student_email, PDO::PARAM_STR);
            $query_check_student_name->execute();
            $result = $query_check_student_name->fetchAll();

            // if username or/and email find in the database
            // TODO: this is really awful!
            if (count($result) > 0) {
                for ($i = 0; $i < count($result); $i++) {
                    $this->errors[] = ($result[$i]['student_name'] == $student_name) ? MESSAGE_USERNAME_EXISTS : MESSAGE_EMAIL_ALREADY_EXISTS;
                }
            } else {
                // check if we have a constant HASH_COST_FACTOR defined (in config/hashing.php),
                // if so: put the value into $hash_cost_factor, if not, make $hash_cost_factor = null
                $hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);

                // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 character hash string
                // the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using PHP 5.3/5.4, by the password hashing
                // compatibility library. the third parameter looks a little bit shitty, but that's how those PHP 5.5 functions
                // want the parameter: as an array with, currently only used with 'cost' => XX.
                $student_password_hash = password_hash($student_password, PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));
                // generate random hash for email verification (40 char string)
                $student_activation_hash = sha1(uniqid(mt_rand(), true));

                // write new gaz_students data into database
                $query_new_student_insert = $this->db_connection->prepare('INSERT INTO ' . DB_TABLE_PREFIX . '_students (student_classroom_id,  student_firstname,  student_lastname,  student_name,  student_password_hash, student_email,  student_telephone,  student_activation_hash,  student_registration_ip, student_registration_datetime) VALUES(:student_classroom_id, :student_firstname, :student_lastname, :student_name, :student_password_hash, :student_email, :student_telephone, :student_activation_hash, :student_registration_ip, now())');
                $query_new_student_insert->bindValue(':student_classroom_id', $student_classroom_id, PDO::PARAM_STR);
                $query_new_student_insert->bindValue(':student_firstname', $student_firstname, PDO::PARAM_STR);
                $query_new_student_insert->bindValue(':student_lastname', $student_lastname, PDO::PARAM_STR);
                $query_new_student_insert->bindValue(':student_name', $student_name, PDO::PARAM_STR);
                $query_new_student_insert->bindValue(':student_password_hash', $student_password_hash, PDO::PARAM_STR);
                $query_new_student_insert->bindValue(':student_email', $student_email, PDO::PARAM_STR);
                $query_new_student_insert->bindValue(':student_telephone', $student_telephone, PDO::PARAM_STR);
                $query_new_student_insert->bindValue(':student_activation_hash', $student_activation_hash, PDO::PARAM_STR);
                $query_new_student_insert->bindValue(':student_registration_ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
                $query_new_student_insert->execute();

                // id of new user
                $student_id = $this->db_connection->lastInsertId();

                if ($query_new_student_insert) {
                    // send a verification email
                    if ($this->sendVerificationEmail($student_id, $student_email, $student_activation_hash)) {
                        // when mail has been send successfully
                        $this->messages[] = MESSAGE_VERIFICATION_MAIL_SENT;
                        $this->registration_successful = true;
                    } else {
                        // delete this gaz_students account immediately, as we could not send a verification email
                        $query_delete_user = $this->db_connection->prepare('DELETE FROM ' . DB_TABLE_PREFIX . '_students WHERE student_id=:student_id');
                        $query_delete_user->bindValue(':student_id', $student_id, PDO::PARAM_INT);
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

    public function sendVerificationEmail($student_id, $student_email, $student_activation_hash) {
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

        $mail->FromName = EMAIL_VERIFICATION_FROM_NAME;
        $mail->AddAddress($student_email);
        $mail->Subject = EMAIL_VERIFICATION_SUBJECT;

        $link = EMAIL_VERIFICATION_URL . '?id=' . urlencode($student_id) . '&verification_code=' . urlencode($student_activation_hash);

        // the link to your register.php, please set this value in config/email_verification.php
        $mail->AddEmbeddedImage('./school.png', 'gschool');
        $mail->AddEmbeddedImage('../../library/images/logo_180x180.png', 'glogo');
        $mail->Body = EMAIL_VERIFICATION_CONTENT . '<br> <img height="64" src="cid:glogo" /> <a href="' . $link.'"> <img src="cid:gschool" /> '.MESSAGE_EMAIL_LINK_FOR_VERIFYNG.'</a>';
        if (!$mail->Send()) {
            $this->errors[] = MESSAGE_VERIFICATION_MAIL_NOT_SENT . $mail->ErrorInfo;
            return false;
        } else {
            return true;
        }
    }

    private function getInstallSqlFile() {
        //serve per trovare il primo file .sql di installazione piu' recente e possibilmente nella lingua scelta
        $lastInstallSqlFile = "";
        $ctrlLastVersion = 0;
        $relativePath = '../../setup/install';
        if ($handle = opendir($relativePath)) {
            while ($file = readdir($handle)) {
                if (($file == ".") || ($file == ".."))
                    continue;
                if (preg_match("/^install_([0-9]{1,2})\.([0-9]{1,2})\.sql$/", $file, $regs)) {
                    //faccio il push solo se e' una versione di valore maggiore della precedente
                    $versionFile = $regs[1] * 100 + $regs[2];
                    if ($versionFile > $ctrlLastVersion) {
                        $lastInstallSqlFile = $file;
                        $ctrlLastVersion = $versionFile;
                    }
                } else {
                    continue;
                }
            }
            closedir($handle);
        }
        return $lastInstallSqlFile;
    }

    private function executeQueryFileInstall($student_id, $last_file) {
        // Inizializzo l'accumulatore e sostituisco il prefisso
        $tmpSql = file_get_contents("../../setup/install/" . $last_file);
        $tmpSql = preg_replace("/gaz_/", DB_TABLE_PREFIX . str_pad($student_id, 4, '0', STR_PAD_LEFT) . '_', $tmpSql);  //sostituisco gaz_ con il prefisso personalizzato
        $tmpSql = preg_replace("/CREATE DATABASE IF NOT EXISTS gazie/", "CREATE DATABASE IF NOT EXISTS " . DB_NAME, $tmpSql);
        $tmpSql = preg_replace("/USE gazie/", "USE " . DB_NAME, $tmpSql);
        // Iterazione per ciascuna linea del file.
        $lineArray = explode(";\n", $tmpSql);
        foreach ($lineArray as $l) {
            $l = ltrim($l);
            if (!empty($l)) {
                $this->db_connection->query($l);
            }
        }
        return true;
    }

    /**
     * checks the id/verification code combination and set the user's activation status to true (=1) in the database
     */
    public function verifyNewUser($student_id, $student_activation_hash) {
        // if database connection opened
        if ($this->databaseConnection()) {
            // try to update user with specified information
            $query_update_user = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . '_students SET student_active = 1, student_activation_hash = NULL WHERE student_id = :student_id AND student_activation_hash = :student_activation_hash');
            $query_update_user->bindValue(':student_id', intval(trim($student_id)), PDO::PARAM_INT);
            $query_update_user->bindValue(':student_activation_hash', $student_activation_hash, PDO::PARAM_STR);
            $query_update_user->execute();
            if ($query_update_user->rowCount() > 0) {

                /* GAZIE
                 * qui faccio tutto quanto occorre per creare una nuova serie di tabelle con prefisso
                 * per avere una nuova gestione separata dello studente che si è registrato */
                $query_get_student_password = $this->db_connection->prepare('SELECT student_password_hash, student_name, student_firstname, student_lastname, student_email FROM ' . DB_TABLE_PREFIX . '_students WHERE student_id = :student_id');
                $query_get_student_password->bindValue(':student_id', intval(trim($student_id)), PDO::PARAM_INT);
                $query_get_student_password->execute();
                $r = $query_get_student_password->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_LAST);
                $student_password = $r[0]; //questa la userò per popolare gaz_admin della hash della password
                $student_name = $r[1]; // user_name
                $student_firstname = $r[2]; // Nome
                $student_lastname = $r[3]; // Cognome
                $student_email = $r[4]; // email
                $last_file = $this->getInstallSqlFile();
                $this->executeQueryFileInstall($student_id, $last_file);
                $this->db_connection->query('TRUNCATE `' . DB_TABLE_PREFIX . str_pad($student_id, 4, '0', STR_PAD_LEFT) . "_admin`;");
                // add student into new gazNNNN_admin
                $gravatar_url = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($student_email))).'?d=mm';
                $gravatar_img = @file_get_contents($gravatar_url);
                $query_add_student_to_admin = $this->db_connection->prepare('INSERT INTO ' . DB_TABLE_PREFIX . str_pad($student_id, 4, '0', STR_PAD_LEFT) . '_admin (user_id, user_lastname, user_firstname,image, lang, user_name,  user_password_hash, user_active, Abilit,  company_id, datpas) VALUES(:user_id, :user_lastname, :user_firstname,:image, :lang, :user_name, :user_password_hash, 1, 8 , 1, NOW())');
                $query_add_student_to_admin->bindValue(':user_id', intval(trim($student_id)), PDO::PARAM_INT);
                $query_add_student_to_admin->bindValue(':user_name', $student_name, PDO::PARAM_STR);
                $query_add_student_to_admin->bindValue(':user_firstname', $student_firstname, PDO::PARAM_STR);
                $query_add_student_to_admin->bindValue(':user_lastname', $student_lastname, PDO::PARAM_STR);
                $query_add_student_to_admin->bindValue(':image', $gravatar_img, PDO::PARAM_LOB);
                $query_add_student_to_admin->bindValue(':lang', TRANSL_LANG, PDO::PARAM_STR);
                $query_add_student_to_admin->bindValue(':user_password_hash', $student_password, PDO::PARAM_STR);
                $query_add_student_to_admin->execute();
                // update admin_module with new username
                $query_update_admin_module = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . str_pad($student_id, 4, '0', STR_PAD_LEFT) . "_admin_module SET adminid = :student_name WHERE adminid = 'amministratore'");
                $query_update_admin_module->bindValue(':student_name', $student_name, PDO::PARAM_STR);
                $query_update_admin_module->execute();
                // update admin_config with new username
                $query_update_admin_module = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . str_pad($student_id, 4, '0', STR_PAD_LEFT) . "_admin_config SET adminid = :student_name WHERE adminid = 'amministratore'");
                $query_update_admin_module->bindValue(':student_name', $student_name, PDO::PARAM_STR);
                $query_update_admin_module->execute();
                // update breadcrumb with new username
                $query_update_admin_module = $this->db_connection->prepare('UPDATE ' . DB_TABLE_PREFIX . str_pad($student_id, 4, '0', STR_PAD_LEFT) . "_breadcrumb SET adminid = :student_name WHERE adminid = 'amministratore'");
                $query_update_admin_module->bindValue(':student_name', $student_name, PDO::PARAM_STR);
                $query_update_admin_module->execute();
                /* GAZIE FINE                 */

                $this->verification_successful = true;
                $this->messages[] = MESSAGE_REGISTRATION_ACTIVATION_SUCCESSFUL . $last_file;
            } else {
                $this->errors[] = MESSAGE_REGISTRATION_ACTIVATION_NOT_SUCCESSFUL;
            }
        }
    }

}
