<?php include('_header.php'); ?>

<form method="post" action="student_login.php" name="loginform">
    <div class="container">
        <div id="loginbox" style="margin-top:50px;" class="mainbox mainbox col-sm-offset-2 col-sm-8">
            <div class="panel panel-info" >
                <div class="panel-heading panel-gazie">
                    <div class="panel-title">
                        <img width="5%" src="../../library/images/logo_180x180.png" />
                        <img width="5%" src="./school.png" />
                        <?php echo MESSAGE_LOG; ?> <?php echo $server_lang; ?> <img width="5%" src="../../language/<?php echo TRANSL_LANG; ?>/flag.png" />
                    </div>
                    <div style="color: red; float:right; font-size: 100%; position: relative; top:-10px"></div>
                </div>
                <div style="padding-top:10px" class="panel-body" >
                    <h4 ><?php echo MESSAGE_WELCOME ?></h4>
                    <p><?php echo MESSAGE_INTRO; ?></p>
                    <p><?php echo MESSAGE_PSW; ?></p><br/>
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
                                echo '<div id="login-alert" class="alert alert-danger col-sm-12">';
                                echo $message;
                                echo '</div>';
                            }
                        }
                    }
                    ?>
                    <div style="padding-bottom: 25px;" class="input-group">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                        <input id="student_name" type="text" name="student_name" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_USERNAME; ?>" />
                    </div>

                    <div style="padding-bottom: 25px;" class="input-group">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                        <input type="password" autocomplete="off" required style="height: 34px;" id="login-password" class="form-control" name="student_password" placeholder="<?php echo WORDING_PASSWORD; ?>">
                    </div>
                    <div id="capsWarning" class="alert alert-warning col-sm-12" style="display:none;">Blocco maiuscole attivato! Caps lock on! Bloqueo de mayusculas!</div>
                    <div class="row">
                        <div class="col-xs-4 text-center">
                            <input type="checkbox" id="student_rememberme" name="student_rememberme" value="1" />
                            <label for="student_rememberme"><?php echo WORDING_REMEMBER_ME; ?></label>
                        </div>
                        <div class="col-xs-4 text-center">
                            <input class="btn btn-success"  name="login" type="submit" value="<?php echo WORDING_LOGIN; ?>" >
                        </div>
                        <div class="col-xs-4 text-center">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-4 text-center">
                            <a href="student_register.php"><?php echo WORDING_REGISTER_NEW_ACCOUNT; ?></a>
                        </div>
                        <div class="col-xs-4 text-center">
                            <a href="student_password_reset.php"><?php echo WORDING_FORGOT_MY_PASSWORD; ?></a>
                        </div>
                        <div class="col-xs-4 text-center">
                            <a href="../root/admin.php"><?php echo WORDING_GO_TO_LOGIN; ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- chiude div container -->
</form>


<?php include('_footer.php'); ?>
