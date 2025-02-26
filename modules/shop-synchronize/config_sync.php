<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  SHOP SYNCHRONIZE è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2021 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
-------------------------------------------------------------------------

*** ANTONIO GERMANI  ***
**Configurazione inpostazioni FTP per sincronizzazione con modulo shop-synchronize**
***

 */
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin(9);
$getenable_sync = gaz_dbi_get_row($gTables['aziend'], 'codice', $admin_aziend['codice'])['gazSynchro'];
$enable_sync = explode(",",$getenable_sync);


  if (count($_POST) > 0) { // ho modificato i valori
    $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if (!empty($_FILES['myfile']['name'])) {
			// cancello eventuale vecchio file e salvo il nuovo nella cartella files
			$path = DATA_DIR . 'files/' . $admin_aziend['codice'] . '/secret_key/';
			if (!file_exists($path)) { // se è la prima volta e non esiste la cartella la creo
				mkdir($path, 0777, true);
			}
			$exten = strtolower(pathinfo($_FILES['myfile']['name'], PATHINFO_EXTENSION));
			$file_pattern = $path.$_FILES['myfile']['name'];
			@unlink ( $file_pattern );// nel caso non esistesse perché è cambiato il nome evito segnalazione errore
			move_uploaded_file($_FILES['myfile']['tmp_name'], $file_pattern);

		}
    foreach ($_POST as $k => $v) {
      if ($k=="chiave" AND !empty($_FILES['myfile']['name'])){

        if ( $v !== $_FILES['myfile']['name']){
          unlink ($path.$v);
        }
        $v=$_FILES['myfile']['name'];
      }
      $value=filter_var($v, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $key=filter_var($k, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      if ( (strpos($key,"pass")!==false || strpos($key,"psw") !==false ) && $key!=="keypass"){

        $tripsw=trim($value);
        gaz_dbi_query("UPDATE ".$gTables['company_config']." SET val = TO_BASE64(AES_ENCRYPT('".addslashes($value)."','".$_SESSION['aes_key']."')) WHERE var = '".$key."'");

      }else{

        gaz_dbi_put_row($gTables['company_config'], 'var', $key, 'val', $value);

      }

    }

		$n=0;
		unset ($value);
		if (isset ($_POST['addval'])){
			foreach ($_POST['addval'] as $add) {
        if (strlen($add)>0){ // insert solo se valorizzato

          if ($_POST['addvar'][$n]=="chiave" AND !empty($_FILES['myfile']['name'])){
            $add=$_FILES['myfile']['name'];
          }
          $value['var']=$_POST['addvar'][$n];
          $value['val']=$add;

          $value['description']=$_POST['adddes'][$n];
          if (strpos($value['var'],"psw") !== false || (strpos($value['var'],"pass") !== false && $value['var']!=="keypass")){// se è una password la cripto
            gaz_dbi_query("INSERT INTO ".$gTables['company_config']." (description, val, var) VALUES ('".$value['description']."', TO_BASE64(AES_ENCRYPT('".addslashes($value['val'])."','".$_SESSION['aes_key']."')),  '".$value['var']."')");


          }else{
           gaz_dbi_table_insert('company_config', $value);
          }
          $n++;
        }
			}

		}
    if ($_POST['set_enable_sync']=="SI" && $enable_sync[0] == "shop-synchronize"){// se era già attivato ed è rimasto attivato
      // non faccio nulla
    }else{
      if ($_POST['set_enable_sync']=="SI" && $enable_sync[0] !== "shop-synchronize"){
        array_unshift($enable_sync , 'shop-synchronize');// aggiungo shopsync all'inizio dell'array
      } else {
        if ($enable_sync[0] == "shop-synchronize"){
          unset($enable_sync[0]);
        }
      }
      $set_sync=implode(",", $enable_sync);
      gaz_dbi_table_update("aziend", $admin_aziend['codice'], array("gazSynchro"=>$set_sync));// aggiorno i nomi dei moduli
    }

    header("Location: config_sync.php?ok");
    exit;
  }

//$script = basename($_SERVER['PHP_SELF']);
require('../../library/include/header.php');
	$script_transl = HeadMain();
require("../../language/" . $admin_aziend['lang'] . "/menu.inc.php");
require("./lang." . $admin_aziend['lang'] . ".php");
if (isset($script)) { // se è stato tradotto lo script lo ritorno al chiamante
    $script_transl = $strScript[$script];
}

$script_transl = $strCommon + $script_transl;
$result = gaz_dbi_dyn_query("*", $gTables['company_config'], "1=1", ' id ASC', 0, 1000);
?>
<div align="center" class="FacetFormHeaderFont">
	Impostazioni per sincronizzazione sito web tramite il modulo shop-synchronize
    <br> di Antonio Germani
</div>


<div class="panel panel-default gaz-table-form div-bordered">
  <div class="container-fluid">
    <div class="tab-content">
        <div id="generale" class="tab-pane fade in active">
        <form method="post" id="sbmt-form" enctype="multipart/form-data">
        <?php if (isset($_GET["ok"])) { ?>
            <div class="alert alert-success text-center" role="alert">
                <?php echo "Le modifiche sono state salvate correttamente<br/>"; ?>
            </div>
            <script>
              var newURL = location.href.split("?")[0];
              window.history.pushState('object', document.title, newURL);// tolgo l'ok dall'url
            </script>
        <?php }

        $ph='Invisibile, digita solo se vuoi cambiarla (minimo 6 caratteri)';

        if (gaz_dbi_num_rows($result) > 0) {
            while ($r = gaz_dbi_fetch_array($result)) {

              if ($r['var']=="server"){
                $server["id"]=$r["id"];
                $server["description"]=$r["description"];
                $server["var"]=$r["var"];
                $server["val"]=$r["val"];
              }

              if ($r['var']=="user"){
                $user["id"]=$r["id"];
                $user["description"]=$r["description"];
                $user["var"]=$r["var"];
                $user["val"]=$r["val"];
              }

              if ($r['var']=="pass"){
                $pass["id"]=$r["id"];
                $pass["description"]=$r["description"];
                $pass["var"]=$r["var"];
                //$pass["val"]=$r["val"];
                $pass["val"]="";

              }

              if ($r['var']=="ftp_path"){
                $ftp_path["id"]=$r["id"];
                $ftp_path["description"]=$r["description"];
                $ftp_path["var"]=$r["var"];
                $ftp_path["val"]=$r["val"];
              }

              if ($r['var']=="Sftp"){
                $Sftp["id"]=$r["id"];
                $Sftp["description"]=$r["description"];
                $Sftp["var"]=$r["var"];
                $Sftp["val"]=$r["val"];
              }

              if ($r['var']=="port"){
                $port["id"]=$r["id"];
                $port["description"]=$r["description"];
                $port["var"]=$r["var"];
                $port["val"]=$r["val"];
              }

              if ($r['var']=="home"){
                $home["id"]=$r["id"];
                $home["description"]=$r["description"];
                $home["var"]=$r["var"];
                $home["val"]=$r["val"];
              }

              if ($r['var']=="chiave"){
                $chiave["id"]=$r["id"];
                $chiave["description"]=$r["description"];
                $chiave["var"]=$r["var"];
                $chiave["val"]=$r["val"];
              }

              if ($r['var']=="psw_chiave"){
                $psw_chiave["id"]=$r["id"];
                $psw_chiave["description"]=$r["description"];
                $psw_chiave["var"]=$r["var"];
                $psw_chiave["val"]="";
              }

              if ($r['var']=="menu_alerts_check"){
                $alert["id"]=$r["id"];
                $alert["description"]=$r["description"];
                $alert["var"]=$r["var"];
                $alert["val"]=$r["val"];
              }

              if ($r['var']=="path"){
                $path["id"]=$r["id"];
                $path["description"]=$r["description"];
                $path["var"]=$r["var"];
                $path["val"]=$r["val"];
              }

              if ($r['var']=="keypass"){
                $keypass["id"]=$r["id"];
                $keypass["description"]=$r["description"];
                $keypass["var"]=$r["var"];
                $keypass["val"]=$r["val"];

              }

              if ($r['var']=="accpass"){
                $accpass["id"]=$r["id"];
                $accpass["description"]=$r["description"];
                $accpass["var"]=$r["var"];
                //$accpass["val"]=$r["val"];
                $accpass["val"]="";

              }

            }

			?>

			<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $user["id"]; ?>" class="col-sm-5 control-label"><?php echo $user["description"]; ?></label>
				<div class="col-sm-7">
					<input type="text" class="form-control input-sm" id="input<?php echo $user["id"]; ?>" name="<?php echo $user["var"]; ?>" placeholder="<?php echo $user["var"]; ?>" value="<?php echo $user["val"]; ?>">

				</div>
				</div>
			</div><!-- chiude row  -->
			<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $server["id"]; ?>" class="col-sm-5 control-label"><?php echo $server["description"]; ?></label>
				<div class="col-sm-7">
					<input type="text" class="form-control input-sm" id="input<?php echo $server["id"]; ?>" name="<?php echo $server["var"]; ?>" placeholder="<?php echo $server["var"]; ?>" value="<?php echo $server["val"]; ?>">
				</div>
				</div>
			</div><!-- chiude row  -->
			<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $pass["id"]; ?>" class="col-sm-5 control-label"><?php echo $pass["description"]; ?></label>
				<div class="col-sm-7">
					<input type="password" class="form-control input-sm" id="input<?php echo $pass["id"]; ?>" name="<?php echo $pass["var"]; ?>" placeholder="<?php echo $ph; ?>" value="<?php echo $pass["val"]; ?>">
				</div>
				</div>
			</div><!-- chiude row  -->
			<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $ftp_path["id"]; ?>" class="col-sm-5 control-label"><?php echo $ftp_path["description"],". <p style='font-size:8px;'> Percorso FTP assoluto del server per raggiungere la cartella dei file di interfaccia a partire dalla posizione di accesso FTP </p>"; ?></label>
				<div class="col-sm-7">
					<input type="text" class="form-control input-sm" id="input<?php echo $ftp_path["id"]; ?>" name="<?php echo $ftp_path["var"]; ?>" placeholder="<?php echo $ftp_path["var"]; ?>" value="<?php echo $ftp_path["val"]; ?>">
				</div>
				</div>
			</div><!-- chiude row  -->
			<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $path["id"]; ?>" class="col-sm-5 control-label"><?php echo $path["description"],". <p style='font-size:8px;'> Percorso per raggiungere la cartella dei file di interfaccia a partire dal dominio del sito e compreso http(s). Ad esempio: https://shoptest.it/GAzie_sync/</p>"; ?></label>
				<div class="col-sm-7">
					<input type="text" class="form-control input-sm" id="input<?php echo $path["id"]; ?>" name="<?php echo $path["var"]; ?>" placeholder="<?php echo $path["var"]; ?>" value="<?php echo $path["val"]; ?>">
				</div>
				</div>
			</div><!-- chiude row  -->
			<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $user["id"]; ?>" class="col-sm-5 control-label">Attiva la sincronizzazione automatica <p style='font-size:8px;'> Per un corretto allineamento di GAzie con l'e-commerce, si consiglia di mantere sempre attivato.</p></label>
				<div class="col-sm-7">
				<?php
				if ($enable_sync[0]=="shop-synchronize"){
					?>
					<input type="radio" value="SI" name="set_enable_sync" checked="checked" >Si - No<input type="radio" value="NO" name="set_enable_sync">
					<?php
				} else {
					?>
					<input type="radio" value="SI" name="set_enable_sync">Si - No<input type="radio" value="NO" name="set_enable_sync" checked="checked">
					<?php
				}
				?>
				</div>
				</div>
			</div><!-- chiude row  -->
			<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $alert["id"]; ?>" class="col-sm-5 control-label"><?php echo $alert["description"]; ?></label>
				<div class="col-sm-7">
					<input type="text" class="form-control input-sm" id="input<?php echo $alert["id"]; ?>" name="<?php echo $alert["var"]; ?>" placeholder="<?php echo $alert["var"]; ?>" value="<?php echo $alert["val"]; ?>">
				</div>
				</div>
			</div><!-- chiude row  -->
			<?php

			if (isset($accpass['id']) AND $accpass['id']>0){
				?>
				<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $accpass["id"]; ?>" class="col-sm-5 control-label"><?php echo $accpass["description"]; ?></label>
				<div class="col-sm-7">
					<input type="password" class="form-control input-sm" id="input<?php echo $accpass["id"]; ?>" name="<?php echo $accpass["var"]; ?>" placeholder="<?php echo $ph; ?>" value="<?php echo $accpass["val"]; ?>">
				</div>
				</div>
				</div><!-- chiude row  -->
				<?php
			} else {
				?>
				<div class="row">
				<div class="form-group" >
				<label for="inputport" class="col-sm-5 control-label">Password di accesso ai file interfaccia shop-sync</label>
				<div class="col-sm-7">
					<input type="password" class="form-control input-sm" name="addval[]" >
					<input type="hidden" name="addvar[]" value="accpass">
					<input type="hidden" name="adddes[]" value="Password di accesso ai file di interfaccia shop-sync">
				</div>
				</div>
				</div><!-- chiude row  -->
				<?php
			}

			if (isset($Sftp['id']) AND $Sftp['id']>0){
				?>
				<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $Sftp["id"]; ?>" class="col-sm-5 control-label"><?php echo $Sftp["description"],". <p style='font-size:8px;'> Se impostato su sì, selezionare anche se si intende usare la password o il file della chiave segreta </p>"; ?></label>
				<div class="col-sm-3">

				    <?php
					if ($Sftp["val"]=="SI"){
						?>
						<input type="radio" value="SI" name="<?php echo $Sftp["var"]; ?>" checked="checked" >Si - No<input type="radio" value="NO" name="<?php echo $Sftp["var"]; ?>">
						<?php
					} else {
						?>
						<input type="radio" value="SI" name="<?php echo $Sftp["var"]; ?>">Si - No<input type="radio" value="NO" name="<?php echo $Sftp["var"]; ?>" checked="checked">
						<?php
					}
					?>
				</div>
				<div class="col-sm-4">
					<?php
					if ($keypass["val"]=="key"){
						?>

						<input type="radio" value="key" name="<?php echo $keypass["var"]; ?>" checked="checked" >Key - Password<input type="radio" value="pass" name="<?php echo $keypass["var"]; ?>">
						<?php
					} else {
						?>
						<input type="radio" value="key" name="<?php echo $keypass["var"]; ?>">Key - Password<input type="radio" value="pass" name="<?php echo $keypass["var"]; ?>" checked="checked">
						<?php
					}
					?>
				</div>
				</div>
				</div><!-- chiude row  -->
				<?php
			} else {
				?>
				<div class="row">
				<div class="form-group" >
				<label for="inputSftp" class="col-sm-5 control-label">Usa il protocollo di trasferimento file sicuro Sftp. Se impostato su sì, selezionare anche se si intende usare la password o il file della chiave segreta.</label>
				<div class="col-sm-3">
					<input type="radio" value="SI" name="addval[]">Si - No<input type="radio" value="NO" name="addval[]" checked="checked">
					<input type="hidden" name="addvar[]" value="Sftp">
					<input type="hidden" name="adddes[]" value="Usa il protocollo di trasferimento file sicuro Sftp">
				</div>

				<div class="col-sm-4">
				<select name="addval[]" id="cars" >
					<option value="pass">Password</option>
					<option value="key">File chiave segreta</option>
				</select>
					<input type="hidden" name="addvar[]" value="keypass">
					<input type="hidden" name="adddes[]" value="Usa password o file chiave segreta">
				</div>
				</div>
				</div><!-- chiude row  -->
				<?php
			}

			if (isset($chiave['id']) AND $chiave['id']>0){
				?>
				<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $chiave["id"]; ?>" class="col-sm-5 control-label"><?php echo $chiave["description"],". <p style='font-size:8px;'> Se impostato sopra, selezionare il file della chiave privata segreta da caricare. </p>"; ?></label>
				<div class="col-sm-7">
				<input type="file" id="myfile" name="myfile">
				<input type="text" class="form-control input-sm" id="input<?php echo $chiave["id"]; ?>" name="<?php echo $chiave["var"]; ?>" placeholder="<?php echo $chiave["var"]; ?>" value="<?php echo $chiave["val"]; ?>" disabled="disabled">
				<input type="hidden" id="input<?php echo $chiave["id"]; ?>" name="<?php echo $chiave["var"]; ?>" value="<?php echo $chiave["val"]; ?>">
				</div>
				</div>
				</div><!-- chiude row  -->
				<?php
			} else {
				?>
				<div class="row">
				<div class="form-group" >
				<label for="inputport" class="col-sm-5 control-label">Chiave segreta Sftp/SSH. Se impostato sopra, caricare il file della chiave privata segreta.</label>
				<div class="col-sm-7">
				<input type="file" id="myfile" name="myfile">
				<input type="text" class="form-control input-sm" name="addval[]" disabled="disabled" value="" >
				<input type="hidden" name="addval[]" value="SFTP_key">
				<input type="hidden" name="addvar[]" value="chiave">
				<input type="hidden" name="adddes[]" value="Chiave segreta Sftp">
				</div>
				</div>
				</div><!-- chiude row  -->
				<?php
			}
      if (isset($psw_chiave['id']) AND $psw_chiave['id']>0){
				?>
				<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $psw_chiave["id"]; ?>" class="col-sm-5 control-label"><?php echo $psw_chiave["description"]," "; ?><p style='font-size:8px;'>(lasciare vuoto se non c'è password)</p></label>
				<div class="col-sm-7">
					<input type="password" class="form-control input-sm" id="input<?php echo $psw_chiave["id"]; ?>" name="<?php echo $psw_chiave["var"]; ?>" placeholder="<?php echo $ph; ?>" value="<?php echo $psw_chiave["val"]; ?>" minlength="6">
				</div>
				</div>
				</div><!-- chiude row  -->
				<?php
			} else {
				?>
				<div class="row">
				<div class="form-group" >
				<label for="inputport" class="col-sm-5 control-label">Password di accesso alla chiave privata SFTP/SSH (lasciare vuoto se non c'è password)</label>
				<div class="col-sm-7">
					<input type="password" class="form-control input-sm" name="addval[]" minlength="6" placeholder="<?php echo $ph; ?>">
					<input type="hidden" name="addvar[]" value="psw_chiave">
					<input type="hidden" name="adddes[]" value="Password di accesso alla chiave SFTP/SSH">
				</div>
				</div>
				</div><!-- chiude row  -->
				<?php
			}


			if (isset($port['id']) AND $port['id']>0){
				?>
				<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $port["id"]; ?>" class="col-sm-5 control-label"><?php echo $port["description"],". <p style='font-size:8px;'> Se si usa il semplice FTP lasciare vuoto. </p>"; ?></label>
				<div class="col-sm-7">
					<input type="text" class="form-control input-sm" id="input<?php echo $port["id"]; ?>" name="<?php echo $port["var"]; ?>" placeholder="<?php echo $port["var"]; ?>" value="<?php echo $port["val"]; ?>">
				</div>
				</div>
				</div><!-- chiude row  -->
				<?php
			} else {
				?>
				<div class="row">
				<div class="form-group" >
				<label for="inputport" class="col-sm-5 control-label">Porta Sftp. Se si usa il semplice FTP lasciare vuoto.</label>
				<div class="col-sm-7">
					<input type="text" class="form-control input-sm" name="addval[]" >
					<input type="hidden" name="addvar[]" value="port">
					<input type="hidden" name="adddes[]" value="Porta Sftp">
				</div>
				</div>
				</div><!-- chiude row  -->
				<?php
			}

			if (isset($home['id']) AND $home['id']>0){
				?>
				<div class="row">
				<div class="form-group" >
				<label for="input<?php echo $home["id"]; ?>" class="col-sm-5 control-label"><?php echo $home["description"],". <p style='font-size:8px;'> Se non si usa lasciare vuoto. </p>"; ?></label>
				<div class="col-sm-7">
					<input type="text" class="form-control input-sm" id="input<?php echo $home["id"]; ?>" name="<?php echo $home["var"]; ?>" placeholder="<?php echo $home["var"]; ?>" value="<?php echo $home["val"]; ?>">
				</div>
				</div>
				</div><!-- chiude row  -->
				<?php
			} else {
				?>
				<div class="row">
				<div class="form-group" >
				<label for="inputport" class="col-sm-5 control-label">ID per pubblicazione in home page.  Se non si usa lasciare vuoto.</label>
				<div class="col-sm-7">
					<input type="text" class="form-control input-sm" name="addval[]" >
					<input type="hidden" name="addvar[]" value="home">
					<input type="hidden" name="adddes[]" value="Id per pubblicazione in home page">
				</div>
				</div>
				</div><!-- chiude row  -->
				<?php
			}

        }
        ?>
        <div class="row">
            <div class="form-group" >
                <div class="col-sm-6 text-center">
                    <button type="button" onclick="window.location.href='synchronize.php'" class="btn btn-primary">Indietro</button>
                </div>
                <div class="col-sm-6 text-center">
                    <button type="submit" class="btn btn-warning">Salva</button>
                </div>
            </div>
        </div>
        </form>
    </div><!-- chiude generale  -->

  </div><!-- chiude tab-content  -->
 </div><!-- chiude container-fluid  -->
</div><!-- chiude panel  -->

<?php
require("../../library/include/footer.php");
?>
