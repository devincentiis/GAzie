<?php include('_header.php'); ?>
<script src='../../js/sha256/forge-sha256.min.js'></script>
<?php
if($login->getUsernameObj() <> '' && ( ( $login->getUsername() <> $login->getUsernameObj() && $_SESSION['Abilit'] == 9 ) || ( $login->getUsername() <> $login->getUsernameObj() && $_SESSION['Abilit'] == 8 && $login->getUsernameObjAbilit() < 8 ) )) { // sono un amministratore e sto cambiando la password ad un utente di livello inferiore (e rigenero aes_key)
?>
    <form method="post" action="login_password_change.php" name="password_change_form" onsubmit="document.getElementById('user_password_new').value=forge_sha256(document.getElementById('user_password_new').value);document.getElementById('user_password_repeat').value=forge_sha256(document.getElementById('user_password_repeat').value);">
        <div class="container">
            <div id="loginbox" style="margin-top:50px;" class="mainbox mainbox col-sm-offset-2 col-sm-8">
                <div class="panel panel-success" >
                    <div class="panel-heading panel-gazie">
                        <div class="panel-title">
                            <?php echo 'RIPRISTINA PASSWORD ED AES_KEY DELL\'UTENTE: '.$login->getUsernameObj().'<input type="hidden" name="un" value="'.$login->getUsernameObj().'"'; ?>
                        </div>
                        <div style="color: red; float:right; font-size: 100%; position: relative; top:-10px"></div>
                    </div>
                    <div style="padding-top:10px" class="panel-body" >
                        <?php
                        if (isset($login)) {
                            if ($login->errors) {
                                foreach ($login->errors as $error) {
                                    echo '<div id="login-alert" class="alert alert-danger col-sm-12">';
                                    echo $error;
                                    echo '</div>';
                                }
                            }
                            if ($login->messages) {
                                foreach ($login->messages as $message) {
                                    echo '<div id="login-alert" class="alert alert-success col-sm-12">';
                                    echo $message;
                                    echo '</div>';
                                }
                            }
                        }
                        ?>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="user_password_new" type="password" name="user_password_new" pattern=".{6,}" required autocomplete="off"  style="height: 34px;" class="form-control" placeholder="<?php echo WORDING_NEW_PASSWORD; ?>" />
                        </div>

                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="user_password_repeat" type="password" name="user_password_repeat" pattern=".{6,}" required autocomplete="off" style="height: 34px;" class="form-control"  placeholder="<?php echo WORDING_NEW_PASSWORD_REPEAT; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group col-sm-12">
                            <input style="float:right;" class="btn btn-warning" type="submit" name="administrator_change_usr_password" value="<?php echo WORDING_SUBMIT_NEW_PASSWORD; ?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- chiude div container -->
    </form>

<?php
} elseif ($login->isPasswordExpired() == 1) { // password scaduta da poco
    ?>
    <form method="post" onsubmit="document.getElementById('user_password_new').value=forge_sha256(document.getElementById('user_password_new').value);
document.getElementById('user_password_repeat').value=forge_sha256(document.getElementById('user_password_repeat').value);" action="login_password_change.php" name="password_change_form" id="changeform">
        <div class="container">
            <div id="loginbox" style="margin-top:50px;" class="mainbox mainbox col-sm-offset-2 col-sm-8">
                <div class="panel panel-warning" >
                    <div class="panel-heading panel-gazie">
                        <div class="panel-title">
                            <?php echo 'RICHIESTO CAMBIO PASSWORD SCADUTA (da meno di 30 giorni)'; ?>
                        </div>
                        <div style="color: red; float:right; font-size: 100%; position: relative; top:-10px"></div>
                    </div>
                    <div style="padding-top:10px" class="panel-body" >
                        <?php
                        if (isset($login)) {
                            if ($login->errors) {
                                foreach ($login->errors as $error) {
                                    echo '<div id="login-alert" class="alert alert-danger col-sm-12">';
                                    echo $error;
                                    echo '</div>';
                                }
                            }
                            if ($login->messages) {
                                foreach ($login->messages as $message) {
                                    echo '<div id="login-alert" class="alert alert-success col-sm-12">';
                                    echo $message;
                                    echo '</div>';
                                }
                            }
                        }
                        ?>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="user_password_new" type="password" name="user_password_new" pattern=".{6,}" required autocomplete="off"  style="height: 34px;" class="form-control" placeholder="<?php echo WORDING_NEW_PASSWORD; ?>" />
                        </div>

                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="user_password_repeat" type="password" name="user_password_repeat" pattern=".{6,}" required autocomplete="off" style="height: 34px;" class="form-control"  placeholder="<?php echo WORDING_NEW_PASSWORD_REPEAT; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group col-sm-12">
                            <input style="float:right;" class="btn btn-warning" type="submit" name="submit_change_password" value="<?php echo WORDING_SUBMIT_NEW_PASSWORD; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <a style="float:left;" href="login_user.php"><?php echo WORDING_BACK_TO_LOGIN; ?></a>
                        </div>

                    </div>
                </div>
            </div>
        </div><!-- chiude div container -->
    </form>
    <!-- no data from a password-reset-mail has been provided, so we simply show the request-a-password-reset form -->
<?php
} else {  // password scaduta oltre 30 gg
?>
    <form method="post" action="login_password_change.php" name="password_change_form" onsubmit="document.getElementById('login-password').value=forge_sha256(document.getElementById('login-password').value);document.getElementById('user_password_new').value=forge_sha256(document.getElementById('user_password_new').value);document.getElementById('user_password_repeat').value=forge_sha256(document.getElementById('user_password_repeat').value);">
        <div class="container">
            <div id="loginbox" style="margin-top:50px;" class="mainbox mainbox col-sm-offset-2 col-sm-8">
                <div class="panel panel-danger" >
                    <div class="panel-heading panel-gazie">
                        <div class="panel-title">
                            <?php
                            $usr=$login->getUsername();
                            if ($login->getUsernameObj() == $usr) {
                              echo 'CAMBIA LA PASSWORD DELL\'UTENTE: <b>'.$usr.'</b><input type="hidden" name="un" value="'.$login->getUsernameObj().'">' ;
                            } else {
                              echo 'PASSWORD SCADUTA DA OLTRE 30 GIORNI';
                            }
                            ?>
                        </div>
                        <div style="color: red; float:right; font-size: 100%; position: relative; top:-10px"></div>
                    </div>
                    <div style="padding-top:10px" class="panel-body" >
                        <?php
                        if (isset($login)) {
                            if ($login->errors) {
                                foreach ($login->errors as $error) {
                                    echo '<div id="login-alert" class="alert alert-danger col-sm-12">';
                                    echo $error;
                                    echo '</div>';
                                }
                            }
                            if ($login->messages) {
                                foreach ($login->messages as $message) {
                                    echo '<div id="login-alert" class="alert alert-success col-sm-12">';
                                    echo $message;
                                    echo '</div>';
                                }
                            }
                        }
                        ?>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input id="user_name" type="text" name="user_name" value="<?php echo $usr; ?>" required class="form-control" style="height: 34px;" placeholder="<?php echo WORDING_USERNAME; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input type="password" autocomplete="off" required style="height: 34px;" id="login-password" class="form-control" name="user_password" placeholder="<?php echo WORDING_PASSWORD; ?>">
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="user_password_new" type="password" name="user_password_new" pattern=".{6,}" required autocomplete="off"  style="height: 34px;" class="form-control" placeholder="<?php echo WORDING_NEW_PASSWORD; ?>" />
                        </div>

                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="user_password_repeat" type="password" name="user_password_repeat" pattern=".{6,}" required autocomplete="off" style="height: 34px;" class="form-control"  placeholder="<?php echo WORDING_NEW_PASSWORD_REPEAT; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group col-sm-12">
                            <input style="float:right;" class="btn btn-warning" type="submit" name="change_very_old_password" value="<?php echo WORDING_SUBMIT_NEW_PASSWORD; ?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- chiude div container -->
    </form>
<?php } ?>


<?php include('_footer.php'); ?>
