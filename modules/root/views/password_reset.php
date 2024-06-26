<?php include('_header.php'); ?>
<script src='../../js/sha256/forge-sha256.min.js'></script>
<?php
if ($login->passwordResetLinkIsValid() == true) {
    ?>
    <form method="post" onsubmit="document.getElementById('login-password').value=forge_sha256(document.getElementById('login-password').value);document.getElementById('user_password_new').value=forge_sha256(document.getElementById('user_password_new').value);document.getElementById('user_password_repeat').value=forge_sha256(document.getElementById('user_password_repeat').value);" action="login_password_reset.php" name="new_password_form" id="resetform">
        <div class="container">
            <div id="loginbox" style="margin-top:50px;" class="mainbox mainbox col-sm-offset-2 col-sm-8">
                <div class="panel panel-info" >
                    <div class="panel-heading panel-gazie">
                        <div class="panel-title">
                            <?php echo MESSAGE_WELCOME; ?>
                        </div>
                        <div style="color: red; float:right; font-size: 100%; position: relative; top:-10px"></div>
                    </div>
                    <div style="padding-top:10px" class="panel-body" >
                        <h4 ><?php echo WORDING_CHANGE_PASSWORD; ?></h4>
                        <br/>
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
                            <input style="float:right;" class="btn btn-success" type="submit" name="submit_new_password" value="<?php echo WORDING_SUBMIT_NEW_PASSWORD; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <a style="float:left;" href="login_user.php"><?php echo WORDING_BACK_TO_LOGIN; ?></a>
                        </div>

                    </div>
                </div>
            </div>
        </div><!-- chiude div container -->
        <input type='hidden' name='user_name' value='<?php echo htmlspecialchars($_GET['user_name']); ?>' />
        <input type='hidden' name='user_password_reset_hash' value='<?php echo htmlspecialchars($_GET['verification_code']); ?>' />
    </form>
    <!-- no data from a password-reset-mail has been provided, so we simply show the request-a-password-reset form -->
<?php } else { ?>
    <form method="post" action="login_password_reset.php" name="password_reset_form">
        <div class="container">
            <div id="loginbox" style="margin-top:50px;" class="mainbox mainbox col-sm-offset-2 col-sm-8">
                <div class="panel panel-info" >
                    <div class="panel-heading panel-gazie">
                        <div class="panel-title">
                            <?php echo MESSAGE_WELCOME_ADMIN; ?>
                        </div>
                        <div style="color: red; float:right; font-size: 100%; position: relative; top:-10px"></div>
                    </div>
                    <div style="padding-top:10px" class="panel-body" >
                        <h4 ><?php echo WORDING_RESET_PASSWORD; ?></h4>
                        <p><?php echo WORDING_REQUEST_PASSWORD_RESET; ?></p>
                        <br/>
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
                            <input id="user_name" type="text" name="user_name" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_USERNAME; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group col-sm-12">
                            <input  style="float:right;" class="btn btn-success" type="submit" name="request_password_reset" value="<?php echo WORDING_RESET_PASSWORD; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <a style="float:left;" href="login_user.php"><?php echo WORDING_BACK_TO_LOGIN; ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- chiude div container -->
    </form>
<?php } ?>


<?php include('_footer.php'); ?>
