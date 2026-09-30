<?php include('_header.php'); ?>

<!-- show registration form, but only if we didn't submit already -->
<?php if (!$registration->registration_successful && !$registration->verification_successful) { ?>
    <form method="post" action="login_register.php" name="registerform">
        <div class="container">    
            <div id="loginbox" style="margin-top:50px;" class="mainbox mainbox col-sm-offset-2 col-sm-8">                    
                <div class="panel panel-info" >
                    <div class="panel-heading panel-gazie">
                        <div class="panel-title">
                            <?php echo MESSAGE_WELCOME_REGISTRATION ?>
                        </div>
                        <div style="color: red; float:right; font-size: 100%; position: relative; top:-10px"></div>
                    </div>
                    <div style="padding-top:10px" class="panel-body" >
                        <p><?php echo MESSAGE_INTRO_REGISTRATION; ?></p>
                        <p><?php echo MESSAGE_PSW_REGISTRATION; ?></p>
                        <?php
                        if (isset($registration)) {
                            if ($registration->errors) {
                                foreach ($registration->errors as $error) {
                                    echo '<div id="login-alert" class="alert alert-danger col-sm-12">';
                                    echo $error;
                                    echo '</div>';
                                }
                            }
                            if ($registration->messages) {
                                foreach ($registration->messages as $message) {
                                    echo '<div id="login-alert" class="alert alert-danger col-sm-12">';
                                    echo $message;
                                    echo '</div>';
                                }
                            }
                        }
                        ?>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input id="user_firstname" type="text"  pattern="[a-zA-Z\ ]{2,30}" name="user_firstname" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_FIRSTNAME; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input id="user_lastname" type="text"  pattern="[a-zA-Z\ ]{2,30}" name="user_lastname" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_LASTNAME; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input id="user_name" type="text"  pattern="[a-zA-Z0-9]{2,64}" name="user_name" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_USERNAME; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="user_password_new" type="password"  pattern=".{8,}" name="user_password_new" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_PASSWORD; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="user_password_repeat" type="password"  pattern=".{8,}" name="user_password_repeat" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_PASSWORD_REPEAT; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
                            <input id="user_email" type="email" name="user_email" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_EMAIL; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-phone"></i></span>
                            <input id="user_telephone" type="tel" name="user_telephone" class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_TELEPHONE; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <img src="tools/showCaptcha.php" alt="captcha" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon glyphicon-hand-right"></i></span>
                            <input type="text" name="captcha"  required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_CAPTCHA; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group col-sm-12">
                            <input style="float:right;" class="btn btn-success"  type="submit" name="register" value="<?php echo WORDING_REGISTER; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <a style="float:left;" href="login_user.php"><?php echo WORDING_BACK_TO_LOGIN; ?></a>
                        </div>
                    </div>  <!-- chiude div panel-body -->
                </div>  <!-- chiude div panel -->
            </div>
        </div><!-- chiude div container -->
    </form>
    <?php
} else if ($registration->verification_successful) {
    ?>
    <div class="container">    
        <div id="loginbox" style="margin-top:50px;" class="mainbox mainbox col-sm-offset-2 col-sm-8">                    
            <div class="panel panel-success" >
                <div class="panel-heading panel-gazie">
                    <div class="panel-title">
                        <img width="5%" src="./school.png" />
                        <h4 ><?php echo MESSAGE_WELCOME ?></h4>
                        <?php
                        // show potential errors / feedback (from registration object)
                        if ($registration->messages) {
                            foreach ($registration->messages as $message) {
                                echo $message;
                            }
                        }
                        ?>
                    </div>
                    <div style="color: red; float:right; font-size: 100%; position: relative; top:-10px"></div>
                </div>
                <div style="padding-top:10px" class="panel-body" >
                    <div style="padding-bottom: 25px;" class="input-group col-sm-12">
                        <input style="float:right;" class="btn btn-info"  onclick="location.href = 'login_user.php';"  type="submit" name="register" value="<?php echo WORDING_GO_TO_LOGIN; ?>" />
                    </div>
                </div>  <!-- chiude div panel-body -->
            </div>  <!-- chiude div panel -->
        </div>
    </div><!-- chiude div container -->

    <?php
} else if ($registration->registration_successful) {
    ?>
    <div class="container">    
        <div id="loginbox" style="margin-top:50px;" class="mainbox mainbox col-sm-offset-2 col-sm-8">                    
            <div class="panel panel-warning" >
                <div class="panel-heading panel-gazie">
                    <div class="panel-title">
                        <img width="5%" src="./school.png" />                    
                        <h4 ><?php echo MESSAGE_WELCOME ?></h4>
                        <?php
                        // show potential errors / feedback (from registration object)
                        if ($registration->messages) {
                            foreach ($registration->messages as $message) {
                                echo $message;
                            }
                        }
                        ?>
                    </div>
                    <div style="color: red; float:right; font-size: 100%; position: relative; top:-10px"></div>
                </div>
            </div>  <!-- chiude div panel -->
        </div>
    </div><!-- chiude div container -->
    <?php
}
include('_footer.php');
?>
