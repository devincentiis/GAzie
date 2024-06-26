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

/*
  -- TRANSLATED BY : Dante Becerra Lagos (softenglish@gmail.com)
 */

$strScript = array("admin.php" =>
    array('morning' => "Buenos dias",
        'afternoon' => "Buenas tardes",
        'evening' => "Buenas tardes",
        'night' => "Buenas noches",
        'errors' => array(' Es necesario ajustar su versi&oacute;n de base de datos ',
            ' a version ',
            '  hacer clic AQUI ',
            ' Recordar que para el buen funcionamiento de la aplicacion la directiva magic_quotes_gpc debe estar en Off en el archivo php.ini!',
            ' Attenzione il backup esterno risale a più di 10 giorni, fallo adesso ',
            'legrap' => ' ATTENZIONE!!! Hai dimenticato di indicare il nome e/o il cognome del legale rappresentante',
            'rea' => ' ATTENZIONE!!! Nelle aziende persone giuridiche deve indicare i dati relativi alla registrazione al REA in configurazione azienda (clicca sul logo)'
        ),
        'access' => ", este es su acceso numero",
        'pass' => "<br />Ultima actualizacion de clave : ",
        'logout' => "Si desea salir haga clic en el boton",
        'company' => " Ud esta administrando la empresa:<br /> ",
        'mesg_co' => array('La busqueda no dio resultados!', 'Inserte al menos 2 caracteres!', 'Cambiar empresa'),
        'upd_company' => "Cambiar datos de la empresa",
        'business' => "para la edministracion de negocios.",
        'proj' => "Administrador de Proyecto: ",
        'devel' => "Desarrollo, documentacion, reporte de errores: ",
        'change_usr' => "Cambiar sus datos",
        'user_regol' => 'LEGGI IL "REGOLAMENTO UTILIZZO RISORSE INFORMATICHE"',
        'auth' => "Contacto",
        'strBottom' => array(
            array('href' => "http://www.kernel.org/",
                'img' => "linux.gif",
                'title' => "Linux (kernel)"),
            array('href' => "http://www.apache.org",
                'img' => "apache.gif",
                'title' => "Apache the Web Server more used in the world!"),
            array('href' => "https://mariadb.org",
                'img' => "mariadb.png",
                'title' => "MariaDB, il database dentro il quale GAzie archivia i suoi dati"),
            array('href' => "http://www.php.net",
                'img' => "phppower.gif",
                'title' => "Go to PHP official web site, the language for Dynamic Web!"),
            array('href' => "http://sourceforge.net/projects/tcpdf/",
                'img' => "tcpdf.jpg",
                'title' => "You find TCPDF here, the PHP class FPDF based used to produce the GAzie's documents!"),
            array('href' => "https://jquery.com/",
                'img' => "jquery.png",
                'title' => "La libreria javascript per il web"),
            array('href' => "http://getbootstrap.com/",
                'img' => "bootstrap.png",
                'title' => "Bootstrap, front end web library"),
            array('href' => "http://www.mozilla.org/products/firefox/all.html",
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
    array(/* 0 */ " La nueva contrase&ntilde;a debe tener un largo de al menos ",
        /* 1 */ " caracteres,<BR> diferente de la antigua e igual a la de la confirmaci&oacute;n!<br>",
        /* 2 */ " ha tenido acceso a GAzie <br> pero su contrase&ntilde;a ha caducado, debe insertar una nueva!<br>",
        /* 3 */ " Usuario y/o contrase&ntilde;a incorrecta!<br>",
        /* 4 */ " Acceso denegado a este m&oacute;dulo!",
        /* 5 */ " Nueva contrase&ntilde;a",
        /* 6 */ " Confirmacion de nueva contrase&ntilde;a",
        'log' => "El acceso al sistema localiza en:",
        'welcome' => "Bienvenida a GAzie",
        'intro' => "la planificación de recursos empresariales que le permite realizar un seguimiento de las cuentas, la documentación, ventas, compras, almacenes y más, para muchas empresas al mismo tiempo.",
        'usr_psw' => "Introduzca su nombre de usuario y la contraseña que se le ha asignado para comenzar:",
        'ins_psw' => "Introduzca contraseña",
        'label_conf_psw' => "Confirmacion de nueva contrase&ntilde;a",
        'conf_psw' => "Escriba la contraseña otra vez",
        'label_new_psw' => "Nueva contrase&ntilde;a",
        'new_psw' => "Introduzca nueva contraseña",
        'student'=>'Si usted es un estudiante puede iniciar sesión desde aquí'
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
