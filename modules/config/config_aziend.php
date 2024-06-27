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

require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin(9);
//print_r($admin_aziend);
$modal_ok_insert = false;
$modal = false;
if (isset($_POST['mode']) || isset($_GET['mode'])) {
    $pdb=gaz_dbi_get_row($gTables['company_config'], 'var', 'menu_alerts_check')['val'];
    $period=($pdb==0)?60:$pdb;
    $modal = true;
    if (isset($_GET['ok_insert'])) {
        $modal_ok_insert = true;
    }
}

// e-mail TESTER &  PEC TESTER - Antonio Germani
if ((isset($_GET['e-test']) && $_GET['e-test']==TRUE) || (isset($_GET['pec-test']) && $_GET['pec-test']==TRUE)){

  $user = array('user_name'=>$admin_aziend['user_name'],'user_firstname'=>$admin_aziend['user_firstname'],'user_lastname'=>$admin_aziend['user_lastname'],'user_email'=>'pippo');
  $admin_data = ['codice'=>$admin_aziend['codice'],'web_url'=>$admin_aziend['web_url'],'ragso1'=>$admin_aziend['ragso1'],'ragso2'=>$admin_aziend['ragso2'],'colore'=>$admin_aziend['colore'],'e_mail'=>$admin_aziend['e_mail'],'country'=>$admin_aziend['country'],'pec'=>$admin_aziend['pec']];
  // Inizializzo PHPMailer
      //
      if (isset($_GET['e-test']) && $_GET['e-test']==TRUE){// se devo provare la mail semplice
        $rspsw=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'smtp_password'");
        $rpsw=gaz_dbi_fetch_row($rspsw);
        $config_pass = $rpsw?$rpsw[0]:'';
        $config_mailer = gaz_dbi_get_row($gTables['company_config'], 'var', 'mailer');
        $config_port = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_port');
        $config_secure = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_secure');
        $config_user = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_user');
        $config_host = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_server');
        $sender=$admin_data['e_mail'];
      }else{// se devo provare la PEC
        $rspsw=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'pec_smtp_psw'");
        $rpsw=gaz_dbi_fetch_row($rspsw);
        $config_pass = $rpsw?$rpsw[0]:'';
        $config_mailer = gaz_dbi_get_row($gTables['company_config'], 'var', 'mailer');
        $config_port = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_port');
        $config_secure = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_secure');
        $config_user = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_usr');
        $config_host = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_server');
        $sender=$admin_data['pec'];
      }
      require_once "../../library/phpmailer/class.phpmailer.php";
      require_once "../../library/phpmailer/class.smtp.php";
      $mail = new PHPMailer();
      $mail->Host = $config_host['val'];
      $mail->IsHTML();                                // Modalita' HTML
      $mail->CharSet = 'UTF-8';
      // Imposto il server SMTP
      if (!empty($config_port['val'])) {
          $mail->Port = $config_port['val'];             // Imposto la porta del servizio SMTP
      }
      switch ($config_mailer['val']) {
        case "smtp":
          // Invio tramite protocollo SMTP
          $mail->Timeout =   8;
          $mail->SMTPDebug = FALSE;                           // Attivo il debug
          $mail->IsSMTP();                                // Modalita' SMTP
          if (!empty($config_secure['val'])) {
            $mail->SMTPSecure = $config_secure['val']; // Invio tramite protocollo criptato
          } else {
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
          }
          $mail->SMTPAuth = (!empty($config_user['val']) && $config_mailer['val'] == 'smtp' ? TRUE : FALSE );
          if ($mail->SMTPAuth) {
            $mail->Username = $config_user['val'];     // Imposto username per autenticazione SMTP
            $mail->Password = $config_pass;     // Imposto password per autenticazione SMTP
          }

          $mail->SetFrom($sender, $admin_data['ragso1'] . " " . $admin_data['ragso2']);
          $mail->AddAddress($admin_data['e_mail']);// destinatario
          // Imposto l'oggetto dell'email
          $subject = $admin_data['ragso1'] . " " . $admin_data['ragso2'] . " - TEST INVIO "; //subject
          $mail->Subject = $subject;
          // Imposto il testo HTML dell'email
          $body_text = "<div><b>Questo è un test di invio. Se leggi questo messaggio è tutto OK! ;)</b></div>\n";
          $mail->MsgHTML($body_text);
        break;
      }
  if ( $mail->Send() ) {
    if (isset($_GET['e-test']) && $_GET['e-test']==TRUE){
      $data = ["send" => "SUCCESS","sender" => $admin_data['e_mail'],"pec" => ""];
    }else{
      $data = ["send" => "SUCCESS","sender" => $admin_data['e_mail'],"pec" => $admin_data['pec']];
    }
  echo json_encode($data);exit;
  }else{
  $data = ["error" =>  $mail->ErrorInfo];
  echo json_encode($data);exit;
  }
}


if (count($_POST) > 10) {
	$error='&ok_insert';
  $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  foreach ($_POST as $k => $v) {
    $key=filter_var($k, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if(substr($key,0,4)=='json'){
      $v=html_entity_decode($v, ENT_QUOTES, 'UTF-8');
      if (isJson($v)){
        $value=$v;
      } else {
        $value='ERRORE!!! JSON NON VALIDO!: '.filter_var($v, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $error='&json_error';
      }
    } else {
      $value=filter_var($v, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      if ( strpos($key,"pass")===false && strpos($key,"psw")===false ){
        gaz_dbi_put_row($gTables['company_config'], 'var', $key, 'val', $value);
      } else { // solo se le password sono di lunghezza >=8 aggiorno altrimenti lascio com'era
        $tripsw=trim($value);
        if ( strlen($tripsw)>=8 ) {
          gaz_dbi_query("UPDATE ".$gTables['company_config']." SET val = TO_BASE64(AES_ENCRYPT('".addslashes($value)."','".$_SESSION['aes_key']."')) WHERE var = '".$key."'");
        }
      }
    }
  }
  header("Location: config_aziend.php?mode=modal".$error);
  exit;
}

if ($modal === false) {
    require("../../library/include/header.php");
    $script_transl = HeadMain(0, array('custom/autocomplete'));
} else {
    $script = basename($_SERVER['PHP_SELF']);
    require("../../language/" . $admin_aziend['lang'] . "/menu.inc.php");
    require("./lang." . $admin_aziend['lang'] . ".php");
    if (isset($script)) { // se è stato tradotto lo script lo ritorno al chiamante
        $script_transl = $strScript[$script];
    }
    $script_transl = $strCommon + $script_transl;
}
$result = gaz_dbi_dyn_query("*", $gTables['company_config'], "1=1", ' id ASC', 0, 1000);
?>
<div align="center" class="FacetFormHeaderFont">
    <?php echo $script_transl['title']; ?><br>
</div>

<ul class="nav nav-pills">
        <li class="active"><a data-toggle="pill" href="#generale">Configurazione</a></li>
        <li class=""><a data-toggle="pill" href="#email">Test e-mail</a></li>
        <li class=""><a data-toggle="pill" href="#pec">Test PEC</a></li>
        <li style="float: right;"><div class="btn btn-warning" id="upsave">Salva</div></li>
</ul>
<div class="panel panel-default gaz-table-form div-bordered">
  <div class="container-fluid">
    <div class="tab-content">
        <div id="generale" class="tab-pane fade in active">
        <form method="post" id="sbmt-form">
        <?php
        if ($modal) { ?>
        	<input type="hidden" name="mode" value="modal" />
        <?php
        }
        if (isset($_GET["ok_insert"])) { ?>
            <div class="alert alert-success text-center head-msg" role="alert"><b>
                <?php echo "Le modifiche sono state salvate correttamente<br/>"; ?>
            </b></div>
        <?php }
        if (isset($_GET["json_error"])) { ?>
            <div class="alert alert-danger text-center head-msg" role="alert"><b>
                <?php echo "Il valore immesso non è un JSON valido!<br/>"; ?>
            </b></div>
        <?php }
        $mail_sender='';
        if (gaz_dbi_num_rows($result) > 0) {
            while ($r = gaz_dbi_fetch_array($result)) {
                ?>
                <div class="row">
                  <div class="form-group" >
                    <label for="input<?php echo $r["id"]; ?>" class="col-sm-5 control-label"><?php echo $r["description"]; ?></label>
                    <div class="col-sm-7">
                        <?php
                        if($r['var']=='company_email_text'||substr($r['var'],0,4)=='json'){
                        ?>
                        <textarea id="input<?php echo $r["id"]; ?>" name="<?php echo $r["var"]; ?>" style="width:100%;"><?php echo $r['val']; ?></textarea>
						<?php
                        } else {
                          if ($r['var']=='reply_to') {
                           $mail_sender = $r['val'];
                          }
                          if ( strpos($r["var"],"pass")===false && strpos($r["var"],"psw")===false ) {
                            $icls='';
                            $ph=$r["var"];
                            $title=$r["var"];
                            $ty='text';
                          } else {
                            if ( $debug_active == true ){ // con il debug attivo mostro le password in chiaro in title
                              $rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = '".$r["var"]."'");
                              $rdec=gaz_dbi_fetch_row($rsdec);
                              $title=$rdec?$rdec[0]:'';
                            }
                            $ph='Invisibile, digita solo se vuoi cambiarla';
                            $r["val"] ='';
                            $icls='text-bold';
                            $ty='password';
                          }
                        ?>
                        <input type="<?php echo $ty; ?>" class="form-control input-sm <?php echo $icls; ?>" id="input<?php echo $r["id"]; ?>" title="<?php echo $title; ?>" name="<?php echo $r["var"]; ?>" placeholder="<?php echo $ph; ?>" value="<?php echo $r["val"]; ?>">
						<?php
						}
						?>
                    </div>
                  </div>
                </div><!-- chiude row  -->
                <?php
            }
        }
        ?>
        <div class="row">
            <div class="form-group" >
                <label class="col-sm-5 control-label"></label>
                <div class="col-sm-7 text-center">
                    <button type="submit" class="btn btn-warning">Salva</button>
                </div>
            </div>
        </div>
        </form>
    </div><!-- chiude generale  -->
    <div id="email" class="tab-pane fade">
			<div>Il test di configurazione email ti permette di verificare la configurazione della tua mail. <br>Ricordarsi di inserire l'idirizzo e-mail nelle impostazioni di configurazione azienda<br><b>Salva</b> la configurazione prima di avviare il test.</i>
        </div>
		</br></br><hr>

    <div id="wait">
      <span>Please wait...</span>
    </div>

			<div id="btn_send" class="btn btn-default">TEST INVIO MAIL</div>
			<div id="reply_send"></div>
    </script>
    </div><!-- chiude email  -->
    <div id="pec" class="tab-pane fade">
			<div>Il test di configurazione pec ti permette di verificare la configurazione della tua pec. <br>Ricordarsi di inserire l'idirizzo pec nelle impostazioni di configurazione azienda<br><b>Salva</b> la configurazione prima di avviare il test.</i>
        </div>
		</br></br><hr>

    <div id="waitPEC">
      <span>Please wait...</span>
    </div>

			<div id="btn_sendPEC" class="btn btn-default">TEST INVIO MAIL da PEC</div>
			<div id="reply_sendPEC"></div>
    </script>
    </div><!-- chiude pec  -->
  </div><!-- chiude tab-content  -->
 </div><!-- chiude container-fluid  -->
</div><!-- chiude panel  -->
<script>
if ($(".head-msg").length) {
}
$( "#wait" ).hide();
$("#btn_send").click( function() {
	$.ajax({
		url: "config_aziend.php?e-test=true",
		type: "GET",
		data: { 'e-test': true },
     beforeSend: function () {
      // ... your initialization code here (so show loader) ...
      $( "#wait" ).show();
    },
    complete: function () {
      // ... your finalization code here (hide loader) ...
      $( "#wait" ).hide();
    },
		success: function(json) {
			result = JSON.parse(json);
			if (  result.send == 'SUCCESS') {
		  		$("#reply_send").html( "<strong>Invio riuscito</strong><br><div>Controlla se ti è arrivata una email in <i>"+result.sender+"</i></div>");
			} else {
				$("#reply_send").html("<strong>Invio FALLITO!</strong><br><div>Errore: "+result.error+"</div>");
			}
		},
		error: function(richiesta,stato,errori){
 				$("#reply_send").html("<strong>Invio FALLITO!</strong><br><div>"+errori+"</div>");
		},
	})
});
$( "#waitPEC" ).hide();
$("#btn_sendPEC").click( function() {
	$.ajax({
		url: "config_aziend.php?pec-test=true",
		type: "GET",
		data: { 'pec-test': true },
     beforeSend: function () {
      // ... your initialization code here (so show loader) ...
      $( "#waitPEC" ).show();
    },
    complete: function () {
      // ... your finalization code here (hide loader) ...
      $( "#waitPEC" ).hide();
    },
		success: function(json) {
			result = JSON.parse(json);
			if (  result.send == 'SUCCESS') {
		  		$("#reply_sendPEC").html( "<strong>Invio riuscito</strong><br><div>Controlla se ti è arrivata una email in <i>"+result.sender+" proveniente da "+result.pec+"</i></div>");
			} else {
				$("#reply_sendPEC").html("<strong>Invio FALLITO!</strong><br><div>Errore: "+result.error+"</div>");
			}
		},
		error: function(richiesta,stato,errori){
 				$("#reply_sendPEC").html("<strong>Invio FALLITO!</strong><br><div>"+errori+"</div>");
		},
	})
});
<?php
if ($modal === false) {
?>
$( "#upsave" ).click(function() {
    $( "#sbmt-form" ).submit();
});
<?php
} else {
?>
$("#sbmt-form").submit(function (e) {
    $.ajax({
        type: "POST",
        url: "config_aziend.php?mode=modal",
        data: $("#sbmt-form").serialize(), // serializes the form's elements.
        success: function (data) {
            $("#edit-modal .modal-sm").css('width', '100%');
            $("#edit-modal .modal-body").html(data);
		},
        error: function(data){
            alert(data);
        }
    });
    e.preventDefault(); // avoid to execute the actual submit of the form.
});
$( "#upsave" ).click(function() {
    $.ajax({
        type: "POST",
        url: "config_aziend.php?mode=modal",
        data: $("#sbmt-form").serialize(), // serializes the form's elements.
        success: function (data) {
            $("#edit-modal .modal-sm").css('width', '100%');
            $("#edit-modal .modal-body").html(data);
        },
        error: function(data){
            alert(data);
        }
    });
    e.preventDefault(); // avoid to execute the actual submit of the form.
});
<?php
	// solo per evitare errori in finestra modale
	$period=false;
	function get_rref_type($value) {
	}
  function pulisci_rref_name(){
  }
  function printCheckbox(){
  }
  $config = new UserConfig;
}
?>
</script>
<?php
if ($modal === false) {
  require("../../library/include/footer.php");
}
function isJson($str) {
	return !preg_match('/[^,:{}\\[\\]0-9.\\-+Eaeflnr-u \\n\\r\\t]/', preg_replace('/"(\\.|[^"\\\\])*"/', '', $str));
}
?>
