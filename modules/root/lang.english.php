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

$strScript = array("admin.php" =>
    array('morning' => "good morning",
        'afternoon' => "good afternoon",
        'evening' => "good evening",
        'night' => "good night",
        'errors' => array(' It is necessary to align its Database version ',
            ' to version ',
            '  by clicking HERE ',
            ' He remembers that for the good application working the directive magic_quotes_gpc must be set  Off into php.ini file!',
            ' Attenzione il backup esterno risale a più di 10 giorni, fallo adesso ',
            'legrap' => ' ATTENZIONE!!! Hai dimenticato di indicare il nome e/o il cognome del legale rappresentante',
            'rea' => ' ATTENZIONE!!! Nelle aziende persone giuridiche deve indicare i dati relativi alla registrazione al REA in configurazione azienda (clicca sul logo)'
        ),
        'access' => ", this is your access n.",
        'pass' => "<br />Last password update: ",
        'logout' => "If you want exit click the button",
        'company' => " You are running the company:<br /> ",
        'mesg_co' => array('The search yielded no results!', 'Insert at least 2 characters!', 'Change company'),
        'upd_company' => "Update company config",
        'business' => "for the business management.",
        'proj' => "Project manager: ",
        'devel' => "Development, documentation, bug report: ",
        'change_usr' => "Change your data",
        'user_regol' => 'LEGGI IL "REGOLAMENTO UTILIZZO RISORSE INFORMATICHE"',
        'auth' => "Contact",
        'strBottom' => array(
            array('href' => "https://www.kernel.org/",
                'img' => "linux.gif",
                'title' => "Linux (kernel)"),
            array('href' => "https://www.apache.org",
                'img' => "apache.gif",
                'title' => "Apache the Web Server more used in the world!"),
            array('href' => "https://mariadb.org",
                'img' => "mariadb.png",
                'title' => "MariaDB, il database dentro il quale GAzie archivia i suoi dati"),
            array('href' => "https://www.php.net",
                'img' => "phppower.gif",
                'title' => "Go to PHP official web site, the language for Dynamic Web!"),
            array('href' => "https://sourceforge.net/projects/tcpdf/",
                'img' => "tcpdf.jpg",
                'title' => "You find TCPDF here, the PHP class FPDF based used to produce the GAzie's documents!"),
            array('href' => "https://jquery.com/",
                'img' => "jquery.png",
                'title' => "La libreria javascript per il web"),
            array('href' => "https://getbootstrap.com/",
                'img' => "bootstrap.png",
                'title' => "Bootstrap, front end web library"),
            array('href' => "https://www.mozilla.org/products/firefox/all.html",
                'img' => "firefox.gif",
                'title' => "Download FIREFOX, the browser GAzie has been tested with!")
        ),
        'sca_scacli' => 'Scadenzario Clienti',
        'sca_scafor' => 'Scadenzario Fornitori',
        'sca_cliente' => 'Cliente',
        'sca_fornitore' => 'Fornitore',
        'sca_avere' => 'Avere',
        'sca_dare' => 'Dare',
        'sca_saldo' => 'Saldo',
        'sca_scadenza' => 'Scadenza',
        'cod' => 'Codice',
        'des' => 'Descrizione',
        'lot' => 'Lotto',
        'res' => 'Quantità',
        'inscalot' => 'Lotti in scadenza',
        'scalot' => 'Lotti scaduti'
    ),
    "login_admin.php" =>
    array(/* 0 */ " The new password to be long at least ",
        /* 1 */ " characters,<BR> various from previous and the equal one to that one of control !<br>",
        /* 2 */ " You have had approached the program but yours password it is past due,<br /> you must insert of one new!",
        /* 3 */ " User and/or Password incorrect!<br>",
        /* 4 */ " Denied access to this module !",
        /* 5 */ " New password",
        /* 6 */ " New confirmation password",
        'log' => "Access to system localized in:",
        'welcome' => "Welcome to GAzie",
        'intro' => "the Enterprise Resource Planning that allows you to keep track of the accounts, documentation, sales, purchases, warehouses and more, for many companies simultaneously.",
        'usr_psw' => "Enter your username and password that you have been assigned to start:",
        'ins_psw' => "Enter Password",
        'label_conf_psw' => "Confirm Password",
        'conf_psw' => "Re-enter Password",
        'label_new_psw' => "New Password",
        'new_psw' => "Enter New Password",
        'student'=>'If you are a student you can log from here'
        ));
$errors = array(
    'access_module' => 'Non hai il diritto di accedere al modulo',
    'access_script' => 'Non hai il diritto di accedere alla funzione richiesta'
);
if (!defined("MESSAGE_WELCOME")) {
// GAzie
    define("MESSAGE_WELCOME", "Benvenuto su: <b>GAzie a scuola</b>");
    define("MESSAGE_WELCOME_ADMIN", "Benvenuto su: <b>GAzie</b>");
    define("MESSAGE_LOG", "Accesso al gestionale didattico su server localizzato in ");
    define("MESSAGE_LOG_ADMIN", "Accesso al gestionale su server localizzato in ");
    define("MESSAGE_INTRO", "con esso ti potrai esercitare nell'utilizzo di un gestionale multiaziendale che tiene sotto controllo i conti, la documentazione, le vendite, gli acquisti, il magazzino e tanto altro e di molte ditte contemporaneamente.");
    define("MESSAGE_INTRO_ADMIN", "il Gestionale multiAZIEndale che ti permette di tenere sotto controllo i conti, la documentazione, le vendite, gli acquisti, il magazzino e tanto altro e di molte ditte contemporaneamente.");
    define("MESSAGE_PSW", "Inserisci il nome utente e la password che hai scelto in fase di iscrizione al servizio");
    define("MESSAGE_PSW_ADMIN", "Inserisci le credenziali di accesso per iniziare:");
    define("MESSAGE_WELCOME_REGISTRATION", "Registrati su: <b>GAzie a scuola</b>");
    define("MESSAGE_WELCOME_REGISTRATION_ADMIN", "Registrati su: <b>GAzie</b>");
    define("MESSAGE_INTRO_REGISTRATION", "così ti potrai esercitare nell'utilizzo di un gestionale multiaziendale che tiene sotto controllo i conti, la documentazione, le vendite, gli acquisti, il magazzino e tanto altro e di molte ditte contemporaneamente.");
    define("MESSAGE_PSW_REGISTRATION", "Dopo aver scelto la classe compila tutti gli altri campi del form sottostante, successivamante riceverai una mail con un link da cliccare per confermare l'accesso al servizio");
    define("MESSAGE_CLASSROOM_REGISTRATION", "Scegli la classe di appartenenza");
    define("MESSAGE_CLASSROOM_TEACHER", "prof.");
    define("WORDING_REGISTRATION_FIRSTNAME", "Nome anagrafico (lettere o spazi, min.2 max.30)");
    define("WORDING_REGISTRATION_LASTNAME", "Cognome anagrafico (lettere o spazi, min.2 max.30)");
    define("WORDING_REGISTRATION_TELEPHONE", "Numero di telefono (facoltativo)");
    define("WORDING_GO_TO_LOGIN", "Vai alla pagina per l'accesso");
    define("MESSAGE_EMAIL_LINK_FOR_VERIFYNG", "CLICCA QUI PER ATTIVARE");
    define("MESSAGE_EMAIL_LINK_FOR_RESET", "CLICCA QUI PER REIMPOSTARE LA TUA PASSWORD");
    define("MESSAGE_TRY_UPDATE_DATABASE", "<a href='../../setup/install/install.php'> Aggiorna il database prima di accedere</a>");

// login & registration classes
    define("MESSAGE_ACCOUNT_NOT_ACTIVATED", "Il tuo account non è attivo. Clicca sul link di conferma della mail che abbiamo inviato oppure contatta l'amministratore di sistema.");
    define("MESSAGE_CAPTCHA_WRONG", "I caratteri di controllo che hai inserito non coincidono con quelli dell'immagine!");
    define("MESSAGE_COOKIE_INVALID", "Invalid cookie");
    define("MESSAGE_DATABASE_ERROR", "Database connection problem.");
    define("MESSAGE_EMAIL_ALREADY_EXISTS", "Questo indirizzo email è già registrato. Usa <b>\"Ho dimenticato le credenziali per l'accesso\"</b> dalla pagina per l'accesso per ripristinarla");
    define("MESSAGE_EMAIL_CHANGE_FAILED", "Sorry, your email changing failed.");
    define("MESSAGE_EMAIL_CHANGED_SUCCESSFULLY", "Your email address has been changed successfully. New email address is ");
    define("MESSAGE_EMAIL_EMPTY", "Email cannot be empty");
    define("MESSAGE_EMAIL_INVALID", "Your email address is not in a valid email format");
    define("MESSAGE_EMAIL_SAME_LIKE_OLD_ONE", "Sorry, that email address is the same as your current one. Please choose another one.");
    define("MESSAGE_EMAIL_TOO_LONG", "Email cannot be longer than 64 characters");
    define("MESSAGE_LINK_PARAMETER_EMPTY", "Empty link parameter data.");
    define("MESSAGE_LOGGED_OUT", "You have been logged out.");
// The "login failed"-message is a security improved feedback that doesn't show a potential attacker if the user exists or not
    define("MESSAGE_LOGIN_FAILED", "Le credenziali di accesso sono sbagliate. Riprova.");
    define("MESSAGE_OLD_PASSWORD_WRONG", "Your OLD password was wrong.");
    define("MESSAGE_PASSWORD_BAD_CONFIRM", "Le due password non coincidono");
    define("MESSAGE_PASSWORD_CHANGE_FAILED", "Sorry, your password changing failed.");
    define("MESSAGE_PASSWORD_CHANGED_SUCCESSFULLY", "La password è stata cambiata con successo! Adesso puoi entrare usando la nuova");
    define("MESSAGE_IP_BANNED", "Your IP address has been banned, please contact your system administrator");
    define("MESSAGE_PASSWORD_EMPTY", "Password field was empty");
    define("MESSAGE_PASSWORD_RESET_MAIL_FAILED", "Password reset mail NOT successfully sent! Error: ");
    define("MESSAGE_PASSWORD_RESET_MAIL_SUCCESSFULLY_SENT", "La mail per la reimpostazione della password è stata inviata con successo!");
    define("MESSAGE_PASSWORD_TOO_SHORT", "Password has a minimum length of 6 characters");
    define("MESSAGE_PASSWORD_WRONG", "Credenziali sbagliate. Riprova");
    define("MESSAGE_PASSWORD_WRONG_3_TIMES", "Hai sbagliato  la password 3 o più volte. Attendi 30 seconds per provare di nuovo.");
    define("MESSAGE_REGISTRATION_ACTIVATION_NOT_SUCCESSFUL", "Spiecenti, ma il codice di verifica non risulta essere più valido!");
    define("MESSAGE_REGISTRATION_ACTIVATION_SUCCESSFUL", "L'attivazione ha avuto successo, ho popolato il tuo database con il file: ");
    define("MESSAGE_REGISTRATION_FAILED", "Sorry, your registration failed. Please go back and try again.");
    define("MESSAGE_RESET_LINK_HAS_EXPIRED", "Your reset link has expired. Please use the reset link within one hour.");
    define("MESSAGE_VERIFICATION_MAIL_ERROR", "Sorry, we could not send you an verification mail. Your account has NOT been created.");
    define("MESSAGE_VERIFICATION_MAIL_NOT_SENT", "Verification Mail NOT successfully sent! Error: ");
    define("MESSAGE_VERIFICATION_MAIL_SENT", "Le tue credenziali sono state registrate con successo. Ti abbiamo inviato una mail per verificare la correttezza del tuo indirizzo. Solo quando cliccherai sul link in essa contenuto il gestionale GAzie a scuola sarà operativo e potrai accederci.");
    define("MESSAGE_VERIFICATION_MAIL_SENT_ADMIN", "Le tue credenziali sono state registrate con successo. Ti abbiamo inviato una mail per verificare la correttezza del tuo indirizzo. Solo quando cliccherai sul link in essa contenuto il gestionale GAzie sarà operativo e potrai accederci.");
    define("MESSAGE_USER_DOES_NOT_EXIST", "Questo nome utente non esiste!");
    define("MESSAGE_USERNAME_BAD_LENGTH", "Username cannot be shorter than 2 or longer than 64 characters");
    define("MESSAGE_USERNAME_CHANGE_FAILED", "Sorry, your chosen username renaming failed");
    define("MESSAGE_USERNAME_CHANGED_SUCCESSFULLY", "Your username has been changed successfully. New username is ");
    define("MESSAGE_USERNAME_EMPTY", "Username field was empty");
    define("MESSAGE_USERNAME_EXISTS", "Sorry, that username is already taken. Please choose another one.");
    define("MESSAGE_USERNAME_INVALID", "Username does not fit the name scheme: only a-Z and numbers are allowed, 2 to 64 characters");
    define("MESSAGE_USERNAME_SAME_LIKE_OLD_ONE", "Spiacenti ma questo nome utente è già utilizzato da un altro allievo.");

// views
    define("WORDING_BACK_TO_LOGIN", "Torna alla pagina di accesso");
    define("WORDING_CHANGE_EMAIL", "Change email");
    define("WORDING_CHANGE_PASSWORD", "Cambia la password");
    define("WORDING_CHANGE_USERNAME", "Change username");
    define("WORDING_CURRENTLY", "currently");
    define("WORDING_EDIT_USER_DATA", "Edit user data");
    define("WORDING_EDIT_YOUR_CREDENTIALS", "You are logged in and can edit your credentials here");
    define("WORDING_FORGOT_MY_PASSWORD", "Ho dimenticato le credenziali per l'accesso");
    define("WORDING_LOGIN", "Accedi");
    define("WORDING_LOGOUT", "Esci");
    define("WORDING_NEW_EMAIL", "New email");
    define("WORDING_NEW_PASSWORD", "Inserisci la nuova password");
    define("WORDING_NEW_PASSWORD_REPEAT", "Ripeti la nuova password");
    define("WORDING_NEW_USERNAME", "New username (username cannot be empty and must be azAZ09 and 2-64 characters)");
    define("WORDING_OLD_PASSWORD", "Your OLD Password");
    define("WORDING_PASSWORD", "Password");
    define("WORDING_PROFILE_PICTURE", "Your profile picture");
    define("WORDING_REGISTER", "Registrami");
    define("WORDING_REGISTER_NEW_ACCOUNT", "Sono un nuovo alunno");
    define("WORDING_REGISTER_NEW_ADMIN", "Sono un nuovo utente");
    define("WORDING_REGISTRATION_CAPTCHA", "Inserisci i caratteri che vedi nell'immagine di sopra");
    define("WORDING_REGISTRATION_EMAIL", "Email (Attenzione! Inserisci un indirizzo valido per poter ricevere la mail con il link per la verifica)");
    define("WORDING_REGISTRATION_PASSWORD", "Password (minimo 8 caratteri!)");
    define("WORDING_REGISTRATION_PASSWORD_REPEAT", "Ripeti la password");
    define("WORDING_REGISTRATION_USERNAME", "Username per il login (nickname fatto di lettere e numeri, min.8 max.64)");
    define("WORDING_REMEMBER_ME", "Ricordamelo per 2 settimane");
    define("WORDING_REQUEST_PASSWORD_RESET", "Richiedi il reset della password. Inserisci il tuo nome utente  e riceverai una  mail con le istruzioni:");
    define("WORDING_RESET_PASSWORD", "Richiedi una nuova password");
    define("WORDING_SUBMIT_NEW_PASSWORD", "Registra la nuova password");
    define("WORDING_USERNAME", "Nome utente");
    define("WORDING_YOU_ARE_LOGGED_IN_AS", "You are logged in as ");
    define("WORDING_LOGIN_AS_STUDENT", "Se sei uno studente puoi accedere da qui");


// ex config
// for: password reset email data
    define("EMAIL_PASSWORDRESET_FROM_NAME", "GAzie");
    define("EMAIL_PASSWORDRESET_SUBJECT", "Reimpostazione password di GAzie");
    define("EMAIL_PASSWORDRESET_CONTENT", "Clicca sul link seguente per reimpostare la tua password:");
// for: verification email data
    define("EMAIL_VERIFICATION_FROM_NAME", "GAzie");
    define("EMAIL_VERIFICATION_SUBJECT", "Registrazione su GAzie");
    define("EMAIL_VERIFICATION_CONTENT", "Clicca su questo link per completare la registrazione e accedere al servizio GAzie a scuola:");
}

?>
