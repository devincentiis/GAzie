<?php
/*
 --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (https://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
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
// prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}
use Ddeboer\Imap\Server;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require("../../library/include/datlib.inc.php");
require("../../modules/magazz/lib.function.php");

$admin_aziend = checkAdmin();

if (isset($_POST['type'])&&isset($_POST['ref'])) {
   // imposto PHP Mailer per invio email di cambio stato
        $host = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_server')['val'];
        $usr = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_user')['val'];
        //$psw = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_password')['val'];
        $rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'smtp_password'");
        $rdec=gaz_dbi_fetch_row($rsdec);
        $psw=$rdec?$rdec[0]:'';
        $port = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_port')['val'];
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        //Server settings
        $mail->SMTPDebug  = 0;                           //Enable verbose debug output default: SMTP::DEBUG_SERVER;
        $mail->isSMTP();                                 //Send using SMTP
        $mail->Host       = $host;                       //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                        //Enable SMTP authentication
        $mail->Username   = $usr;                        //SMTP username
        $mail->Password   = $psw;                        //SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //Enable implicit TLS encryption
        $mail->Port       = $port;                       //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
        // Antonio Germani prendo i dati IMAP utente, se ci sono
        $custom_field = gaz_dbi_get_row($gTables['anagra'], 'id', $admin_aziend['id_anagra'])['custom_field'];
        $imap_usr='';
        if ($data = json_decode($custom_field,true)){// se c'è un json e c'è una mail aziendale utente
          if (isset($data['config'][$admin_aziend['company_id']]) && is_array($data['config'])){ // se c'è il modulo "config" e c'è l'azienda attuale posso procedere
            list($encrypted_data, $iv) = explode('::', base64_decode($data['config'][$admin_aziend['company_id']]['imap_pwr']), 2);
            $imap_pwr=openssl_decrypt($encrypted_data, 'aes-128-cbc', $_SESSION['aes_key'], 0, $iv);
            $imap_usr=$data['config'][$admin_aziend['company_id']]['imap_usr'];
            $imap_sent_folder=$data['config'][$admin_aziend['company_id']]['imap_sent_folder'];
            $imap_server = gaz_dbi_get_row($gTables['company_config'], 'var', 'imap_server')['val'];
            $imap_port = gaz_dbi_get_row($gTables['company_config'], 'var', 'imap_port')['val'];
            $imap_secure = gaz_dbi_get_row($gTables['company_config'], 'var', 'imap_secure')['val'];
          }
        }
  switch ($_POST['type']) {
		case "set_new_stato_lavorazione":
			$i=intval($_POST['ref']); // id_tesbro
      // ricarico il json custom field tesbro e controllo
      $tesbro=gaz_dbi_get_row($gTables['tesbro'], "id_tes", $i); // carico la tesbro
      $clfoco=gaz_dbi_get_row($gTables['clfoco'], "codice", $tesbro['clfoco']);
      $anagra=gaz_dbi_get_row($gTables['anagra'], "id", $clfoco['id_anagra']); // carico la anagra
      $language=gaz_dbi_get_row($gTables['languages'], "lang_id", $anagra['id_language']); // carico la lingua
      $langarr = explode(" ",$language['title_native']);
      $lang = strtolower($langarr[0]);
      if (file_exists("lang.".$lang.".php")){// se esiste
        include "lang.".$lang.".php";// carico il file traduzione lingua
      }else{// altrimenti carico di default la lingua inglese
        include "lang.english.php";
      }
      $script_transl=$strScript['booking_form.php'];

      if ($data = json_decode($tesbro['custom_field'],true)){// se c'è un json

        if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental" lo aggiorno
          if (substr($_POST['new_status'],0,9)=="CANCELLED"){// se la prenotazione va cancellata azzero anche i reminder
            $data['vacation_rental']['rem_pag']="";
            $data['vacation_rental']['rem_checkin']="";
          }
          $data['vacation_rental']['status']=substr($_POST['new_status'],0,10);
          $custom_json = json_encode($data);
        } else { //se non c'è il modulo "vacation_rental" lo aggiungo
          $data['vacation_rental']= array('status' => substr($_POST['new_status'],0,10));
          $custom_json = json_encode($data);
        }
      }else { //se non c'è un json creo "vacation_rental"
          $data['vacation_rental']= array('status' => substr($_POST['new_status'],0,10));
          $custom_json = json_encode($data);
      }
      gaz_dbi_put_row($gTables['tesbro'], 'id_tes', $i, 'custom_field', $custom_json);
      if ($_POST['email']=='true' && strlen($_POST['cust_mail'])>4){// se richiesto invio mail

        // creo e invio email di conferma
        //Recipients
        $mail->setFrom($admin_aziend['e_mail'],$admin_aziend['ragso1']." ".$admin_aziend['ragso2']); // sender (e-mail dell'account che sta inviando)
        $mail->addReplyTo($admin_aziend['e_mail']); // reply to sender (e-mail dell'account che sta inviando)
        $mail->addAddress($_POST['cust_mail']); // email destinatario
        if (filter_var($_POST['cust_mail2'], FILTER_VALIDATE_EMAIL)){ // se c'è una seconda mail destinatario gliela mando per conoscenza
           $mail->addCC($_POST['cust_mail2']);
        }
        if ($imap_usr==''){
          $mail->addCC($admin_aziend['e_mail']); //invio copia a mittente
        }
        $mail->isHTML(true);
        $mail->Subject = $script_transl['changement']." ".$tesbro['numdoc'].' '.$script_transl['of'].' '.gaz_format_date($tesbro['datemi']);
        $mail->Body    = "<p>".$script_transl['change_status'].": ".$script_transl[$_POST['new_status']]."</p><p><b>".$admin_aziend['ragso1']." ".$admin_aziend['ragso2']."</b></p>";
        if($mail->send()) {
          if ($imap_usr!==''){// se ho un utente imap carico la mail nella sua posta inviata
            if($imap = @imap_open("{".$imap_server.":".$imap_port."/".$imap_secure."}".$imap_sent_folder, $imap_usr, $imap_pwr)){
              if ($append=@imap_append($imap, "{".$imap_server."}".$imap_sent_folder, $mail->getSentMIMEMessage(),"\\seen")){
                      // inserimento avvenuto
              }else{
                $errors = @imap_errors();
                ?>
                <script>
                alert('carico mail inviata in posta inviata NON riuscito <?php echo implode ('; ', $errors ); ?>');
                </script>
                <?php
              }
            }else{
              $errors = @imap_errors();
                ?>
                 <script>
                alert('carico mail inviata in posta inviata NON riuscito <?php echo implode ('; ', $errors ); ?>');
                </script>
                <?php
            }
          }
        }else {
          echo "Errore imprevisto nello spedire la mail di modifica status: " . $mail->ErrorInfo;
        }
      }
		break;
    case "set_new_status_check":
			$i=intval($_POST['ref']); // id_tesbro
      $pointenable = gaz_dbi_get_row($gTables['company_config'], 'var', 'pointenable')['val'];
      $pointeuro = gaz_dbi_get_row($gTables['company_config'], 'var', 'pointeuro')['val'];
      $datetime  = date ('Y-m-d H:i:s', strtotime($_POST['datetime']));
      $tesbro=gaz_dbi_get_row($gTables['tesbro'], "id_tes", $i); // carico la tesbro
      $clfoco=gaz_dbi_get_row($gTables['clfoco'], "codice", $tesbro['clfoco']);
      $anagra=gaz_dbi_get_row($gTables['anagra'], "id", $clfoco['id_anagra']); // carico la anagra
      $lan="it";
      $language=gaz_dbi_get_row($gTables['languages'], "lang_id", $anagra['id_language']); // carico la lingua specifica del cliente
      $langarr = explode(" ",$language['title_native']);
      $lang = strtolower($langarr[0]);
      if (file_exists("lang.".$lang.".php")){// se esiste la lingua richiesta
        include "lang.".$lang.".php";// carico il file traduzione lingua
		$lan=$language['sef'];
      }else{// altrimenti carico di default la lingua inglese
        include "lang.english.php";
		$lan="en";
      }
      $script_transl=$strScript['booking_form.php'];
      $res=gaz_dbi_get_row($gTables['company_config'], "var", 'vacation_url_user');
      $vacation_url_user=$res['val'];// carico l'url per la pagina front-end utente

      if ($_POST['new_status']=="OUT"){
        $updt= "checked_out_date = '". $datetime."'";
      }elseif($_POST['new_status']=="IN"){
        $updt= "checked_in_date = '". $datetime."', checked_out_date = NULL";
      }else{
        $updt= "checked_in_date = NULL, checked_out_date = NULL";
      }
      $old_checked_out_date=gaz_dbi_get_row($gTables['rental_events'], "id_tesbro", $i, " AND type = 'ALLOGGIO'")['checked_out_date'];

      gaz_dbi_query ("UPDATE " . $gTables['rental_events'] . " SET ".$updt." WHERE id_tesbro =".$i." AND type= 'ALLOGGIO'");

      if (intval($pointenable)==1 && filter_var($_POST['cust_mail'], FILTER_VALIDATE_EMAIL)){// se è attivato il sistema punti e il destinatario ha un e-mail valida
        $points_expiry = gaz_dbi_get_row($gTables['company_config'], 'var', 'points_expiry')['val'];

        // creo e invio email di conferma
        //Recipients
        $mail->setFrom($admin_aziend['e_mail'],$admin_aziend['ragso1']." ".$admin_aziend['ragso2']); // sender (e-mail dell'account che sta inviando)
        $mail->addReplyTo($admin_aziend['e_mail']); // reply to sender (e-mail dell'account che sta inviando)
        $mail->addAddress($_POST['cust_mail']);                  // email destinatario
        if (filter_var($_POST['cust_mail2'], FILTER_VALIDATE_EMAIL)){ // se c'è una seconda mail destinatario gliela mando per conoscenza
           $mail->addCC($_POST['cust_mail2']);
        }
        if ($imap_usr==''){
          $mail->addCC($admin_aziend['e_mail']);             //invio copia a mittente
        }
        $mail->isHTML(true);
        $mail->Subject = $script_transl['booking']." ".$tesbro['numdoc'].' '.$script_transl['of'].' '.gaz_format_date($tesbro['datemi']);
        if ((!isset($old_checked_out_date) || intval($old_checked_out_date)==0) && $_POST['new_status']=="OUT" && floatval($pointeuro)>0){// se è abilitato attribuisco i punti al checkout
          $amount=get_totalprice_booking($i,FALSE,FALSE,"",TRUE);
          $points=intval($amount/$pointeuro);
          if ($data = json_decode($anagra['custom_field'],true)){// se c'è un json in anagra
            if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental" nel custom field lo aggiorno
              if (isset($data['vacation_rental']['points'])){// se ci sono già punti accumulati
                if (intval($points_expiry)>0){// se i punti hanno una scadenza
                  $date=(isset($data['vacation_rental']['points_date']))?date_create($data['vacation_rental']['points_date']):date_create("2023-09-01");
                  date_add($date,date_interval_create_from_date_string(intval($points_expiry)." days"));// aggiungo la durata dei punti
                  if (strtotime(date_format($date,"Y-m-d")) < strtotime(date("Y-m-d"))){// se i punti sono scaduti
                    $data['vacation_rental']['points'] = $points;// cancello i vecchi e inserisco i nuovi
                  }else{// i punti accumulati sono validi
                    $data['vacation_rental']['points'] = intval($data['vacation_rental']['points'])+$points;// aggiungo i nuovi ai vecchi
                  }
                }else{// i punti non hano scadenza
                  $data['vacation_rental']['points'] = intval($data['vacation_rental']['points'])+$points;// aggiungo i nuovi ai vecchi
                }
              }else{
                $data['vacation_rental']['points'] = $points;
              }
              $data['vacation_rental']['points_date']=date("Y-m-d");
              $custom_json = json_encode($data);
              gaz_dbi_put_row($gTables['anagra'], 'id', $anagra['id'], 'custom_field', $custom_json);
              $level=get_user_points_level($anagra['id']);
              if(intval($level)>0){
                $sql = "SELECT val FROM ".$gTables['company_config']." WHERE var = 'pointlevel".$level."name' LIMIT 1";
                if ($result = mysqli_query($link, $sql)) {
                  $val = mysqli_fetch_assoc($result);
                  $level_name=$val['val'];
                }
              }else{
                $level_name="nessun livello raggiunto";
              }
              $mail->Body    = "<p>".$script_transl['give_point']." ".$data['vacation_rental']['points']." ".$script_transl['give_point1']." ".$level_name."</p><p>".$script_transl['regards']."</p><p><b>".$admin_aziend['ragso1']." ".$admin_aziend['ragso2']."</b></p>";
              $mail->Body    .="<p><a href='https://www.gmonamour.it/".$lan."/service/fidelity-mon-amour'>Fidelity Mon Amour</a></p>";

              if($mail->send()) {
                if ($imap_usr!==''){// se ho un utente imap carico la mail nella sua posta inviata
                  if($imap = @imap_open("{".$imap_server.":".$imap_port."/".$imap_secure."}".$imap_sent_folder, $imap_usr, $imap_pwr)){
                    if ($append=@imap_append($imap, "{".$imap_server."}".$imap_sent_folder, $mail->getSentMIMEMessage(),"\\seen")){
                            // inserimento avvenuto
                    }else{
                      $errors = @imap_errors();
                      ?>
                      <script>
                      alert('carico mail inviata in posta inviata NON riuscito <?php echo implode ('; ', $errors ); ?>');
                      </script>
                      <?php
                    }
                  }else{
                    $errors = @imap_errors();
                      ?>
                       <script>
                      alert('carico mail inviata in posta inviata NON riuscito <?php echo implode ('; ', $errors ); ?>');
                      </script>
                      <?php
                  }
                }
              }else {
                echo "Errore imprevisto nello spedire la mail di notifica attribuzione punti: " . $mail->ErrorInfo;
              }
            }else{// altrimenti lo creo
              $data['vacation_rental']['points'] = $points;
              $data['vacation_rental']['points_date']=date("Y-m-d");
              $custom_json = json_encode($data);
              gaz_dbi_put_row($gTables['anagra'], 'id', $anagra['id'], 'custom_field', $custom_json);
              $level=get_user_points_level($anagra['id']);
              if(intval($level)>0){
              $sql = "SELECT val FROM ".$gTables['company_config']." WHERE var = 'pointlevel".$level."name' LIMIT 1";
              if ($result = mysqli_query($link, $sql)) {
                $val = mysqli_fetch_assoc($result);
                $level_name=$val['val'];
              }
              }else{
                $level_name="nessun livello raggiunto";
              }
              $mail->Body    = "<p>".$script_transl['give_point']." ".$data['vacation_rental']['points']." ".$script_transl['give_point1']." ".$level_name."</p><p>".$script_transl['regards']."</p><p><b>".$admin_aziend['ragso1']." ".$admin_aziend['ragso2']."</b></p>";
              $mail->Body    .="<p><a href='https://www.gmonamour.it/".$lan."/service/fidelity-mon-amour'>Fidelity Mon Amour</a></p>";
              if($mail->send()) {
                if ($imap_usr!==''){// se ho un utente imap carico la mail nella sua posta inviata
                  if($imap = @imap_open("{".$imap_server.":".$imap_port."/".$imap_secure."}".$imap_sent_folder, $imap_usr, $imap_pwr)){
                    if ($append=@imap_append($imap, "{".$imap_server."}".$imap_sent_folder, $mail->getSentMIMEMessage(),"\\seen")){
                            // inserimento avvenuto
                    }else{
                      $errors = @imap_errors();
                      ?>
                      <script>
                      alert('carico mail inviata in posta inviata NON riuscito <?php echo implode ('; ', $errors ); ?>');
                      </script>
                      <?php
                    }
                  }else{
                    $errors = @imap_errors();
                      ?>
                       <script>
                      alert('carico mail inviata in posta inviata NON riuscito <?php echo implode ('; ', $errors ); ?>');
                      </script>
                      <?php
                  }
                }
              }else {
              echo "Errore imprevisto nello spedire la mail di notifica attribuzione punti: " . $mail->ErrorInfo;
              }
            }
          }else{// se NON c'è un json in anagra
            $data['vacation_rental']['points'] = $points;
            $data['vacation_rental']['points_date']=date("Y-m-d");
            $custom_json = json_encode($data);
            gaz_dbi_put_row($gTables['anagra'], 'id', $anagra['id'], 'custom_field', $custom_json);
            $level=get_user_points_level($anagra['id']);
            if(intval($level)>0){
            $sql = "SELECT val FROM ".$gTables['company_config']." WHERE var = 'pointlevel".$level."name' LIMIT 1";
            if ($result = mysqli_query($link, $sql)) {
              $val = mysqli_fetch_assoc($result);
              $level_name=$val['val'];
            }
            }else{
              $level_name="nessun livello raggiunto";
            }
            $mail->Body    = "<p>".$script_transl['give_point']." ".$data['vacation_rental']['points']." ".$script_transl['give_point1']." ".$level_name."</p><p>".$script_transl['regards']."</p><p><b>".$admin_aziend['ragso1']." ".$admin_aziend['ragso2']."</b></p>";
                    $mail->Body    .="<p><a href='https://www.gmonamour.it/".$lan."/service/fidelity-mon-amour'>Scopri i vantaggi del programma <b>Fidelity Mon Amour</b></a></p>";
            if($mail->send()) {
              if ($imap_usr!==''){// se ho un utente imap carico la mail nella sua posta inviata
                if($imap = @imap_open("{".$imap_server.":".$imap_port."/".$imap_secure."}".$imap_sent_folder, $imap_usr, $imap_pwr)){
                  if ($append=@imap_append($imap, "{".$imap_server."}".$imap_sent_folder, $mail->getSentMIMEMessage(),"\\seen")){
                          // inserimento avvenuto
                  }else{
                    $errors = @imap_errors();
                    ?>
                    <script>
                    alert('carico mail inviata in posta inviata NON riuscito <?php echo implode ('; ', $errors ); ?>');
                    </script>
                    <?php
                  }
                }else{
                  $errors = @imap_errors();
                    ?>
                     <script>
                    alert('carico mail inviata in posta inviata NON riuscito <?php echo implode ('; ', $errors ); ?>');
                    </script>
                    <?php
                }
              }
            }else {
            echo "Errore imprevisto nello spedire la mail di notifica attribuzione punti: " . $mail->ErrorInfo;
            }
          }
        }
        if (intval($old_checked_out_date)>0 && $_POST['new_status']!=="OUT" && floatval($pointeuro)>0){// se è abilitato e si sta regredendo dal check-out tolgo i punti
          $amount=get_totalprice_booking($i,FALSE,FALSE,"",TRUE);
          $points=intval($amount/$pointeuro);
          if ($data = json_decode($anagra['custom_field'],true)){// se c'è un json in anagra
            if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental" lo aggiorno
              if (isset($data['vacation_rental']['points']) && intval($data['vacation_rental']['points'])>0){
                $data['vacation_rental']['points'] = intval($data['vacation_rental']['points'])-$points;
                $data['vacation_rental']['points'] = ($data['vacation_rental']['points']>=0)?$data['vacation_rental']['points']:0;
              }else{
                $data['vacation_rental']['points'] = 0;
              }
              $custom_json = json_encode($data);
              gaz_dbi_put_row($gTables['anagra'], 'id', $anagra['id'], 'custom_field', $custom_json);
              $level=get_user_points_level($anagra['id']);
              if(intval($level)>0){
                $sql = "SELECT val FROM ".$gTables['company_config']." WHERE var = 'pointlevel".$level."name' LIMIT 1";
                if ($result = mysqli_query($link, $sql)) {
                  $val = mysqli_fetch_assoc($result);
                  $level_name=$val['val'];
                }
              }else{
                $level_name="nessun livello raggiunto";
              }
              $mail->Body    = "<p>".$script_transl['delete_point']." ".$points." ".$script_transl['give_point1']." ".$level_name."</p><p>".$script_transl['regards']."</p><p><b>".$admin_aziend['ragso1']." ".$admin_aziend['ragso2']."</b></p>";
              if($mail->send()) {
                if ($imap_usr!==''){// se ho un utente imap carico la mail nella sua posta inviata
                  if($imap = @imap_open("{".$imap_server.":".$imap_port."/".$imap_secure."}".$imap_sent_folder, $imap_usr, $imap_pwr)){
                    if ($append=@imap_append($imap, "{".$imap_server."}".$imap_sent_folder, $mail->getSentMIMEMessage(),"\\seen")){
                            // inserimento avvenuto
                    }else{
                      $errors = @imap_errors();
                      ?>
                      <script>
                      alert('carico mail inviata in posta inviata NON riuscito <?php echo implode ('; ', $errors ); ?>');
                      </script>
                      <?php
                    }
                  }else{
                    $errors = @imap_errors();
                      ?>
                       <script>
                      alert('carico mail inviata in posta inviata NON riuscito <?php echo implode ('; ', $errors ); ?>');
                      </script>
                      <?php
                  }
                }
              }else {
                echo "Errore imprevisto nello spedire la mail di notifica di cancellazione punti: " . $mail->ErrorInfo;
              }
            }
          }
        }
      }

      if (isset($_POST['email']) && $_POST['email']=='true' && strlen($_POST['cust_mail'])>4 && strlen($vacation_url_user)>4){// se richiesto invio mail di richiesta recensione

        $event=gaz_dbi_get_row($gTables['rental_events'], "id_tesbro", $i, " AND type = 'ALLOGGIO'"); // carico l'evento prenotazione

        // creo e invio email di conferma
        //Recipients
        $mail->setFrom($admin_aziend['e_mail'],$admin_aziend['ragso1']." ".$admin_aziend['ragso2']); // sender (e-mail dell'account che sta inviando)
        $mail->addReplyTo($admin_aziend['e_mail']); // reply to sender (e-mail dell'account che sta inviando)
        $mail->addAddress($_POST['cust_mail']);                  // email destinatario
        if (filter_var($_POST['cust_mail2'], FILTER_VALIDATE_EMAIL)){ // se c'è una seconda mail destinatario gliela mando per conoscenza
           $mail->addCC($_POST['cust_mail2']);
        }
        if ($imap_usr==''){
          $mail->addCC($admin_aziend['e_mail']);             //invio copia a mittente
        }
        $mail->isHTML(true);
        $mail->Subject = $script_transl['feedback_request'].$script_transl['booking']." ".$tesbro['numdoc'].' '.$script_transl['of'].' '.gaz_format_date($tesbro['datemi']);
        $mail->Body    = "<p>".$script_transl['ask_feedback']."</p><p><a href=".$vacation_url_user.">".$vacation_url_user."</a></p>".$script_transl['use_access']."<br>Password: <b>".$event['access_code']."</b><br>ID: <b>".$event['id_tesbro']."</b><br>".$script_transl['booking_number'].": <b>".$tesbro['numdoc']."</b><p>".$script_transl['ask_feedback2']."</p><p><b>".$admin_aziend['ragso1']." ".$admin_aziend['ragso2']."</b></p>";
        if($mail->send()) {
          if ($imap_usr!==''){// se ho un utente imap carico la mail nella sua posta inviata
            if($imap = @imap_open("{".$imap_server.":".$imap_port."/".$imap_secure."}".$imap_sent_folder, $imap_usr, $imap_pwr)){
              if ($append=@imap_append($imap, "{".$imap_server."}".$imap_sent_folder, $mail->getSentMIMEMessage(),"\\seen")){
                      // inserimento avvenuto
              }else{
                $errors = @imap_errors();
                ?>
                <script>
                alert('carico mail inviata in posta inviata NON riuscito <?php echo implode ('; ', $errors ); ?>');
                </script>
                <?php
              }
            }else{
              $errors = @imap_errors();
                ?>
                <script>
                alert('carico mail inviata in posta inviata NON riuscito <?php echo implode ('; ', $errors ); ?>');
                </script>
                <?php
            }
          }
        }else {
          echo "Errore imprevisto nello spedire la mail di modifica status: " . $mail->ErrorInfo;
        }
      }
		break;
	}
}
?>
