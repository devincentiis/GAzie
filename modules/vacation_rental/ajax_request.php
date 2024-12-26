<?php
/*
 --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-present - Antonio Germani, Massignano (AP)
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
$admin_aziend=checkAdmin();
$libFunc = new magazzForm();

if (isset($_GET['term'])) {
    if (isset($_GET['opt'])) {
        $opt = $_GET['opt'];
    } else {
        $opt = 'orders';
    }
    switch ($opt) {

      case 'reg_movcon_payment':
        $tesbro = gaz_dbi_get_row($gTables['tesbro'], "id_tes", intval($_GET['term']));
        $query = "SELECT * FROM " . $gTables['rental_payments'] . " WHERE id_tesbro ='". intval($_GET['term']) ."' ORDER BY payment_id ASC";
        $result = gaz_dbi_query($query);// prendo tutti i pagamenti ricevuti
        while ($r = gaz_dbi_fetch_array($result)){// li ciclo e, dopo controllo, li registro
          $descri="RISCOSSO ";
          if ($r['id_paymov']>0){// se questo pagamento è stato già registrato, controllo che esista ancora la registrazione
            $check = gaz_dbi_get_row($gTables['paymov'], 'id', $r['id_paymov']);
            if(is_array($check)){// se è già registrato e non è caparra lo salto
              if ($r['type']=="Caparra_confirmatoria"){// se è una caparra precedentemente registrata, la attribuisco al cliente
                $descri="IMPUTATA ";
                $r['type'].=" alla locazione";
                $r['conto']=gaz_dbi_get_row($gTables['company_config'], "var", 'vacation_caparra_dare')['val'];// imposto il corretto conto di imputazione

              }else{
                echo "Pagamento del ",substr($r['created'],0,10)," di € ",gaz_format_quantity($r['payment_gross'],1,2)," già registrato\n";
                continue;
              }
            }else{
              echo "Esisteva ma è stato cancellato: procedo in una nuova registrazione\n";
            }
          }// registro il movimento contabile del pagamento
            $tes_val = array('caucon' => '',
              'descri' => $descri.$r['type'],
              'datreg' => substr($r['created'],0,10),
              'datdoc' => substr($r['created'],0,10),
              'clfoco' => $tesbro['clfoco']
            );
            tesmovInsert($tes_val);
            $tes_id = gaz_dbi_last_id();
            rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'D', 'codcon' => $r['conto'], 'import' => $r['payment_gross'] ));
            rigmocInsert(array('id_tes' => $tes_id, 'darave' => 'A', 'codcon' => $tesbro['clfoco'], 'import' => $r['payment_gross'] ));
            $rig_id = gaz_dbi_last_id();
            $res_rigmoc = gaz_dbi_get_row($gTables['rigmoc'], 'id_tes', intval($_GET['tescon']), " AND codcon = ".intval($_GET['codcon']));// prendo il rigo della registrazione documento
            $k = gaz_dbi_get_row($gTables['paymov'], 'id_rigmoc_doc', $res_rigmoc['id_rig']);
            paymovInsert(array('id_tesdoc_ref' => $k['id_tesdoc_ref'], 'id_rigmoc_pay' => $rig_id, 'amount' => $r['payment_gross'], 'expiry' => substr($r['created'],0,10)));
            $paymov_id = gaz_dbi_last_id();
            gaz_dbi_put_query($gTables['rental_payments'], " payment_id = ".$r['payment_id'], "id_paymov", $paymov_id);
        }
        echo "\nRegistrazione terminata";
      break;

      case 'point':
        // Antonio Germani prendo i dati IMAP utente, se ci sono
        $custom_field = gaz_dbi_get_row($gTables['anagra'], 'id', $admin_aziend['id_anagra'])['custom_field'];
        $imap_usr='';
        if ($data = json_decode($custom_field,true)){// se c'è un json
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

        $points_expiry = gaz_dbi_get_row($gTables['company_config'], 'var', 'points_expiry')['val'];
        if (is_numeric($_GET['points']) && intval($_GET['points'])<>0 && strlen($_GET['motive'])>2){
          $result = gaz_dbi_get_row($gTables['anagra'], "id", intval($_GET['ref']));
          if (isset($result['custom_field']) && $data = json_decode($result['custom_field'],true)){// se c'è un json in anagra lo acquisisco in $data
            if (isset($data['vacation_rental']['points'])){

              if (intval($points_expiry)>0){// se i punti hanno una scadenza
                $date=(isset($data['vacation_rental']['points_date']))?date_create($data['vacation_rental']['points_date']):date_create("2023-09-01");
                date_add($date,date_interval_create_from_date_string(intval($points_expiry)." days"));// aggiungo la durata dei punti
                if (strtotime(date_format($date,"Y-m-d")) < strtotime(date("Y-m-d"))){// se i punti sono scaduti
                  echo "I vecchi punti scaduti sono stati cancellati. ";
                  $data['vacation_rental']['points'] = intval($_GET['points']);// cancello i vecchi e inserisco i nuovi
                }else{// i punti accumulati sono validi
                  $data['vacation_rental']['points'] = intval($data['vacation_rental']['points'])+intval($_GET['points']);// aggiungo i nuovi ai vecchi
                }
              }else{// i punti non hano scadenza
                $data['vacation_rental']['points'] = intval($data['vacation_rental']['points'])+intval($_GET['points']);// aggiungo i nuovi ai vecchi
              }

            }else{// se non ci sono mai stati punti
              $data['vacation_rental']['points']=intval($_GET['points']);
            }
            $data['vacation_rental']['points_date']=date("Y-m-d");
            $data['vacation_rental']['points']=(intval($data['vacation_rental']['points'])<0)?0:$data['vacation_rental']['points'];// evito di mandare i punti in negativo
            $custom_field = json_encode($data);
            gaz_dbi_update_anagra(array('id', intval($_GET['ref'])), array('custom_field'=>$custom_field,));
            echo "Punti attribuiti correttamente. Totale attuale: ",$data['vacation_rental']['points'];
            if ($_GET['email']=="true" && (filter_var($result['e_mail'], FILTER_VALIDATE_EMAIL) || filter_var($result['e_mail2'], FILTER_VALIDATE_EMAIL))){
              $language=gaz_dbi_get_row($gTables['languages'], "lang_id", $result['id_language']); // carico la lingua del cliente
              $langarr = explode(" ",$language['title_native']);
              $lang = strtolower($langarr[0]);
              if (file_exists("lang.".$lang.".php")){// se esiste
                include "lang.".$lang.".php";// carico il file traduzione lingua
              }else{// altrimenti carico di default la lingua inglese
                include "lang.english.php";
              }
              $script_transl=$strScript['report_booking.php'];
              $tesbro = gaz_dbi_get_row($gTables['tesbro'], "id_tes", intval($_GET['idtes']));
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

              // creo e invio email di conferma
              //Recipients
              $mail->setFrom($admin_aziend['e_mail'],$admin_aziend['ragso1']." ".$admin_aziend['ragso2']); // sender (e-mail dell'account che sta inviando)
              $mail->addReplyTo($admin_aziend['e_mail']); // reply to sender (e-mail dell'account che sta inviando)
              if (filter_var($result['e_mail'], FILTER_VALIDATE_EMAIL)){
                $mail->addAddress($result['e_mail']);                  // se c'è invio all'email destinatario principale
                if (filter_var($result['e_mail2'], FILTER_VALIDATE_EMAIL)){
                  $mail->addCC($result['e_mail2']); //invio per conoscenza al secondo indirizzo
                }
              } elseif (filter_var($result['e_mail2'], FILTER_VALIDATE_EMAIL)){
                $mail->addAddress($result['e_mail2']);                  // altrimenti alla secondaria
              }else{
                ?>
                <script>
                alert('ERRORE, impossibile inviare: non ci sono indirizzi mail validi a cui inviare');
                </script>
                <?php
              }
              if ($imap_usr==''){
                $mail->addCC($admin_aziend['e_mail']); //invio copia a mittente
              }
              $mail->isHTML(true);
              $mail->Subject = $script_transl['booking']." ".$tesbro['numdoc'].' '.$script_transl['of'].' '.gaz_format_date($tesbro['datemi']);
              $mail->Body    = "<p>".$script_transl['email_give_point']." ".$_GET['points']." ".$script_transl['email_give_point2']." ".$_GET['motive']."</p><p>".$script_transl['email_give_point3']." ".$data['vacation_rental']['points']." ".$script_transl['points']."</p><p><b>".$admin_aziend['ragso1']." ".$admin_aziend['ragso2']."</b></p>";
              if($mail->send()) {
                echo ". E-mail inviata";
                if ($imap_usr!==''){// se ho un utente imap carico la mail nella sua posta inviata
                  if($imap = @imap_open("{".$imap_server.":".$imap_port."/".$imap_secure."}".$imap_sent_folder, $imap_usr, $imap_pwr)){
                    if ($append=@imap_append($imap, "{".$imap_server."}".$imap_sent_folder, $mail->getSentMIMEMessage(),"\\seen")){
                            // inserimento avvenuto
                    }else{
                      $errors = @imap_errors();
                      ?>
                      <script>
                      alert('carico mail inviata in 'posta inviata' NON riuscito <?php echo implode ('; ', $errors ); ?>');
                      </script>
                      <?php
                    }
                  }else{
                    $errors = @imap_errors();
                      ?>
                       <script>
                      alert('carico mail inviata in 'posta inviata' NON riuscito <?php echo implode ('; ', $errors ); ?>');
                      </script>
                      <?php
                  }
                }
              }else {
                echo "Errore imprevisto nello spedire la mail di attribuzione punti: " . $mail->ErrorInfo;
              }
            }else{
              echo ". Impossibile inviare e-mail: indirizzo mancante o non corretto";
            }
          }
        }else{
          echo "No data passed!"," points:",$_GET['points']," - motive:",$_GET['motive'];
        }
      break;
      case 'orders':
        $codice= substr($_GET['term'],0,15);
        $orders= $libFunc->getorders($codice);
        echo json_encode($orders);
      break;
      case 'lastbuys':
        $codice= substr($_GET['term'],0,15);
        $lastbuys= $libFunc->getLastBuys($codice,false);
        echo json_encode($lastbuys);
      break;
      case 'group':
        $codice= intval($_GET['term']);
        $query = "SELECT descri, id_artico_group FROM " . $gTables['artico_group'] . " WHERE id_artico_group ='". $codice ."' LIMIT 1";
        $result = gaz_dbi_query($query);
        $n=0;
        while ($res = $result->fetch_assoc()){
          $return[$n]=$res;
          $n++;
        }
        $query = "SELECT codice, descri FROM " . $gTables['artico'] . " WHERE id_artico_group ='". $codice ."'";
        $result = gaz_dbi_query($query);
        while ($res = $result->fetch_assoc()){
          $return[$n]=$res;
          $n++;
        }
        echo json_encode($return);
      break;
      case'load_votes':
        $return=array();
        $codice= intval($_GET['term']);
        $query = "SELECT score, element FROM ". $gTables['rental_feedback_scores'] ." LEFT JOIN ". $gTables['rental_feedback_elements'] ." ON ". $gTables['rental_feedback_elements'] .".id =  ". $gTables['rental_feedback_scores'] .".element_id WHERE feedback_id ='". $codice ."' ORDER BY ". $gTables['rental_feedback_scores'] .".id ASC";
        $result = gaz_dbi_query($query);
        $n=0;
        while ($res = $result->fetch_assoc()){
          $return[$n]=$res;
          $n++;
        }
        echo json_encode($return);
      break;
      case'change_feed_status':
        $codice= intval($_GET['term']);
        // prendo il vecchio feedback (punteggi e stato)
        $query = "SELECT score, element FROM ". $gTables['rental_feedback_scores'] ." LEFT JOIN ". $gTables['rental_feedback_elements'] ." ON ". $gTables['rental_feedback_elements'] .".id =  ". $gTables['rental_feedback_scores'] .".element_id WHERE feedback_id ='". $codice ."' ORDER BY ". $gTables['rental_feedback_scores'] .".id ASC";
        $result = gaz_dbi_query($query);
        $n=0;
        while ($res = $result->fetch_assoc()){
          $feedback['scores'][$n]=$res;
          $n++;
        }
        $feedback_row = gaz_dbi_get_row($gTables['rental_feedbacks'], 'id', $codice);
        $feedback['old_status'] = $feedback_row['status'];
        $ref = $feedback_row['house_code'];
        $feedback['new_status'] = intval($_GET['status']);

        $toDo="NONE";
        if (($feedback['old_status']==0 || $feedback['old_status']==2) && intval($_GET['status'])==1){// se il cambio stato comporta una aggiunta al punteggio generale alloggio
          $toDo="ADD";
        }
        if ($feedback['old_status']==1 && (intval($_GET['status'])==0 || intval($_GET['status'])==2)){// se il cambio stato comporta una sottrazione al punteggio generale alloggio
          $toDo="SUBTRACT";
        }

        // cambio stato al feedback
        $query = "UPDATE ".$gTables['rental_feedbacks']." SET status = ". intval($_GET['status']) ." WHERE id = ".$codice;
        gaz_dbi_query($query);

        if (!empty($admin_aziend['synccommerce_classname']) && class_exists($admin_aziend['synccommerce_classname'])){
              // aggiorno l'e-commerce ove presente
              $gs=$admin_aziend['synccommerce_classname'];
              $gSync = new $gs();
          if($gSync->api_token){
            $gSync->UpsertFeedback($feedback,$toDo,$ref);
            //print_r($feedback);echo" - TODO:",$toDo;
          }
        }
      break;
      case'clone':
        $res = gaz_dbi_dyn_query("*", $gTables['rental_prices'], "year(start) = ". substr($_GET['parent_year'],0,4) ." AND house_code = '".substr($_GET['term'],0,15)."'","id ASC");
         $table = 'rental_prices';
         if (intval($_GET['parent_year'])==intval($_GET['child_year']) && intval($_GET['percent'])>0){// se gli anni sono uguali faccio update
           $res = gaz_dbi_dyn_query("*", $gTables['rental_prices'], "year(start) = ". substr($_GET['parent_year'],0,4) ." AND house_code = '".substr($_GET['term'],0,15)."'","id ASC");

            if ($res->num_rows >0){
              while ($row = gaz_dbi_fetch_array($res)) {
                $dif=abs(intval(substr($row['end'],0,4))-intval(substr($row['start'],0,4)));
                $row['start']=substr($_GET['child_year'],0,4).substr($row['start'],4);
                $year_end=strval(intval(substr($_GET['child_year'],0,4))+$dif);
                $row['end']=$year_end.substr($row['end'],4);
                if (floatval($_GET['percent'])>0){
                  if ($_GET['operat']=='+'){
                    $row['price'] = round($row['price']+(($row['price']*$_GET['percent'])/100),0);
                    $row['title'] = $row['price']." € sogg. min.".$row['minstay'];
                  }
                  if ($_GET['operat']=='-'){
                    $row['price'] = round($row['price']-(($row['price']*$_GET['percent'])/100),0);
                    $row['title'] = $row['price']." € sogg. min.".$row['minstay'];
                  }
                }
                $columns = array('price', 'title');
                $codice = array('id', $row['id']);// sarebbe il where
                $newValue = array('price' => $row['price'], 'title' => $row['title']);
                tableUpdate($table, $columns, $codice, $newValue);// aggiorno solo il prezzo
              }
              echo "Anno correttamente aggiornato";
            }else{
              echo "Non c'è nulla da aggiornare";
            }
         }else{
          while ($row = gaz_dbi_fetch_array($res)) {// prima controllo se posso clonare (il periodo da clonare deve essere vuoto)
            $dif=abs(intval(substr($row['end'],0,4))-intval(substr($row['start'],0,4)));
            $row['start']=substr($_GET['child_year'],0,4).substr($row['start'],4);
            $year_end=strval(intval(substr($_GET['child_year'],0,4))+$dif);
            $row['end']=$year_end.substr($row['end'],4);
            $start=date ("Y-m-d", strtotime($row['start']));
            $end=date ("Y-m-d", strtotime($row['end']));
            while (strtotime($start) < strtotime($end)) {// ciclo il periodo giorno per giorno per controllare se esiste già un prezzo
              $checking = gaz_dbi_get_row($gTables['rental_prices'], "house_code", substr($_GET['term'],0,15), " AND start <= '". $start ."' AND end > '". $start."'");
              if (isset ($checking)){
                echo "ERRORE clonazione non avvenuta: nell'anno ",$_GET['child_year']," uno o più giorni hanno il prezzo già impostato";
                exit;
              }
              $start = date ("Y-m-d", strtotime("+1 days", strtotime($start)));// aumento di un giorno il ciclo
            }
          }
          if (!isset ($checking)){// se posso clonare
            $res = gaz_dbi_dyn_query("*", $gTables['rental_prices'], "year(start) = ". substr($_GET['parent_year'],0,4) ." AND house_code = '".substr($_GET['term'],0,15)."'","id ASC");

            if ($res->num_rows >0){
              while ($row = gaz_dbi_fetch_array($res)) {
                $dif=abs(intval(substr($row['end'],0,4))-intval(substr($row['start'],0,4)));
                $row['start']=substr($_GET['child_year'],0,4).substr($row['start'],4);
                $year_end=strval(intval(substr($_GET['child_year'],0,4))+$dif);
                $row['end']=$year_end.substr($row['end'],4);
                if (floatval($_GET['percent'])>0){
                  if ($_GET['operat']=='+'){
                    $row['price'] = round($row['price']+(($row['price']*$_GET['percent'])/100),0);
                    $row['title'] = $row['price']." € sogg. min.".$row['minstay'];
                  }
                  if ($_GET['operat']=='-'){
                    $row['price'] = round($row['price']-(($row['price']*$_GET['percent'])/100),0);
                    $row['title'] = $row['price']." € sogg. min.".$row['minstay'];
                  }
                }
                $row['id']="";
                $columns = array('id', 'title', 'start', 'end', 'house_code', 'price', 'minstay');
                tableInsert($table, $columns, $row);// Clono
              }
              echo "Anno correttamente clonato";
            }else{
              echo "Non c'è nulla da clonare";
            }
          }
        }
      break;
      case 'selfcheck':
        // Antonio Germani prendo i dati IMAP utente, se ci sono
        $custom_field = gaz_dbi_get_row($gTables['anagra'], 'id', $admin_aziend['id_anagra'])['custom_field'];
        $imap_usr='';
        if ($data = json_decode($custom_field,true)){// se c'è un json
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

        $tesbro = gaz_dbi_get_row($gTables['tesbro'], 'id_tes', intval($_GET['term']));

        if (isset($tesbro['custom_field']) && $datatesbro = json_decode($tesbro['custom_field'], TRUE)) { // se esiste un json nel custom field della testata
          $datatesbro['vacation_rental']['self_checkin_status']=intval($_GET['new_status']);
          if (isset($_GET['msgself']) && isset($_GET['email']) && $_GET['email']=='true'){// se devo inviare la mail e ho un messaggio, lo memorizzo
            $datatesbro['vacation_rental']['self_checkin_status_msg']=$_GET['msgself'];
          }
          $custom_field = json_encode($datatesbro);
          $codice=[];
          $codice[0]='id_tes';
          $codice[1]=intval($_GET['term']);
          tesbroUpdate($codice, array('custom_field'=>$custom_field));

        }else{
          echo "ERRORE: non può esistere una prenotazione senza custom_field";
          return;
        }
        if ($_GET['email']=='true' && filter_var($_GET['cust_mail'], FILTER_VALIDATE_EMAIL)){
          $result = gaz_dbi_get_row($gTables['anagra'], "id", intval($_GET['id_anagra']));
          $language=gaz_dbi_get_row($gTables['languages'], "lang_id", $result['id_language']); // carico la lingua del cliente
          $langarr = explode(" ",$language['title_native']);
          $lang = strtolower($langarr[0]);
          if (file_exists("lang.".$lang.".php")){// se esiste
            include "lang.".$lang.".php";// carico il file traduzione lingua
          }else{// altrimenti carico di default la lingua inglese
            include "lang.english.php";
          }
          $script_transl=$strScript['report_booking.php'];
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

          // creo e invio email di conferma
          //Recipients
          $mail->setFrom($admin_aziend['e_mail'],$admin_aziend['ragso1']." ".$admin_aziend['ragso2']); // sender (e-mail dell'account che sta inviando)
          $mail->addReplyTo($admin_aziend['e_mail']); // reply to sender (e-mail dell'account che sta inviando)
          if (filter_var($result['e_mail'], FILTER_VALIDATE_EMAIL)){
            $mail->addAddress($result['e_mail']);                  // se c'è invio all'email destinatario principale
            if (filter_var($result['e_mail2'], FILTER_VALIDATE_EMAIL)){
              $mail->addCC($result['e_mail2']); //invio per conoscenza al secondo indirizzo
            }
          } elseif (filter_var($result['e_mail2'], FILTER_VALIDATE_EMAIL)){
            $mail->addAddress($result['e_mail2']);                  // altrimenti alla secondaria
          }else{
            ?>
            <script>
            alert('ERRORE, impossibile inviare: non ci sono indirizzi mail validi a cui inviare');
            </script>
            <?php
          }
          if ($imap_usr==''){
            $mail->addCC($admin_aziend['e_mail']); //invio copia a mittente
          }
          $mail->isHTML(true);
          $mail->Subject = "Web self check-in: ".$script_transl['booking']." ".$tesbro['numdoc'].' '.$script_transl['of'].' '.gaz_format_date($tesbro['datemi']);
          $mail->Body = "<p>".$script_transl['email_selfchek']." ".$_GET['new_text']."</p>";
          if(strlen($_GET['msgself'])>2){
             $mail->Body .= "<p>".$script_transl['email_selfchek_msg']." ".$_GET['msgself']."<p>";
          }
          $mail->Body .= "<p><b>".$admin_aziend['ragso1']." ".$admin_aziend['ragso2']."</b></p>";

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
            echo "Errore imprevisto nello spedire la mail di self check-in: " . $mail->ErrorInfo;
          }
        }

      break;
      case 'export':
        $err=0;$ver="";
        $year=intval($_GET['term']);
        $result=gaz_dbi_query("SELECT * FROM ".$gTables['rental_prices']." WHERE (year(start) = ".$year." OR year(end) = ".$year.") AND house_code = '". substr ($_GET['ref'],0,15)."'");
        $file = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $file .="<!--
        - phpMyAdmin XML Dump
        - version 5.2.0
        - Antonio Germani
        - https://www.programmisitiweb.lacasettabio.it
        -
        - Creato il: ". date("d M Y H:i:s")."
        - Versione del server: ".mysqli_get_server_info($link)."
        - Web server Versione PHP: ".PHP_VERSION."
        -->";
        $file .= "\n<pma_xml_export version=\"1.0\" xmlns:pma=\"https://docs.phpmyadmin.net/et/latest/import_export.html\">\n";
        $file .= "\t<!--- Database: '".$Database."'-->\n";
        $file .= "\t<database name=\"".$Database."\">\n";
        $file .= "\t\t<!-- Tabella ".$gTables['rental_prices']." -->\n";

        if($result->num_rows >0){
           while($res = $result->fetch_assoc()){
            $file  .= "\t\t<table name=\"".$gTables['rental_prices']."\">\n";
              foreach($res as $key => $value){
                 $file .= "\t\t\t<column name=\"".$key."\">";
                 $file .= $value;
                 $file .= "</column>\n";
              }
            $file .= "\t\t</table>\n";
           }
        }
        $file .= "\t</database>\n";
        $file .= "</pma_xml_export>\n";
        $xmlFileC = "prices_backup/".$_GET['ref']."/".$_GET['ref']."_prices_table_".$year.".xml";
          if (!file_exists("prices_backup")) {
            mkdir("prices_backup", 0777, true);
          }
          if (!file_exists("prices_backup/".$_GET['ref'])) {// se non esiste la cartella alloggio, la creo
            mkdir("prices_backup/".$_GET['ref'], 0777, true);
          }
          if (file_exists($xmlFileC)) {// se esiste già un file di backup dello stesso anno, creo una versione (al massimo 10 versioni)
            $err=1;
            for($x = 1; $x <= 10; $x++){// cerco se c'è spazio per una nuova versione
               if (!file_exists("prices_backup/".$_GET['ref']."/".$_GET['ref']."_prices_table_".$year."(".$x.").xml")) {// se trovo spazio cambio nome al nuovo file aggiungendo la versione
                 $xmlFileC = "prices_backup/".$_GET['ref']."/".$_GET['ref']."_prices_table_".$year."(".$x.").xml";
                 $err=0;
                 $ver="- versione (".$x.")";
                 break;
               }
            }
          }
          if ($err==0){// se posso salvare
            $xmlHandle = fopen($xmlFileC, "w");
            if (@fwrite($xmlHandle, $file)){
              fclose($xmlHandle);
              echo "File xml correttamente salvato ",$ver;
              return;
            }else{
              echo "File non salvato, ERRORE:",json_encode(error_get_last());
            }
          }else{
            echo "Lo spazio per ulteriori copie è pieno; cancellarne almeno una prima di procedere.";
          }
      break;

      case 'get_files':
        $directory = "prices_backup/".$_GET['term'];
        if (file_exists($directory)){
          $files = scandir($directory);
          $ret=json_encode (array_slice($files,2));// rimuovo i primi due elementi (.,..)
          echo $ret;
        }else{
          echo "Non ci sono file da importare in:",$directory;
        }
        return;
      break;

      case 'del_files':
        $directory = "prices_backup/".$_GET['ref']."/".$_GET['term'];
        if (file_exists($directory)){
          if (unlink($directory)){
            echo "File eliminato";
          }else{
            echo "File non eliminato, ERRORE:",json_encode(error_get_last());
          }
        }else{
          echo "Non esiste questo file:",$directory;
        }
        return;
      break;

      case 'restore_files':
      $err=0;
        $directory = "prices_backup/".$_GET['ref']."/".$_GET['term'];
        if (strlen($_GET['year'])<>4 || intval($_GET['year'])==0){
          echo "Impostare correttamente l'anno in cui importare";
          $err=1;
          return;
          break;
        }
        if (file_exists($directory)){
          $xml = simplexml_load_file($directory);
          //echo "<pre>",print_r($xml);
          foreach($xml->database->table as $column){
            $cols="";
            $values="";
            $first='';
            $table=(string) $column['name'];
            foreach ($column->column as $col){
              if (((string) $col['name'])=="id"){
                continue;
              }
              if ($col['name']=="start" || $col['name']=="end"){
                $col[0]=$_GET['year'].substr($col[0],-6);// modifico la data con l'anno richiesto
              }
              $cols .=$first.((string) $col['name']);
              $values .= $first."'".$col[0]."'";
              $first=', ';
            }

            $query = "INSERT INTO ".$table." (".$cols.") VALUES (".$values.")";
             if (!gaz_dbi_query($query)){
               echo "ERRORE scrittura data base:",json_encode(error_get_last());
               $err=1;
               break;
             }
          }
        }else{
          echo "Non hai selezionato il file da importare";
          $err=1;
        }
        if ($err==0){
          echo "Prezzi importati nel DB";
        }
        return;
      break;

      default:
      return false;
    }
}
?>
