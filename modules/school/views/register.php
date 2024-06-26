<?php include('_header.php'); ?>

<!-- show registration form, but only if we didn't submit already -->
<?php if (!$registration->registration_successful && !$registration->verification_successful) { ?>
    <form method="post" action="student_register.php" name="registerform">
        <div class="container">
            <div id="loginbox" style="margin-top:50px;" class="mainbox mainbox col-sm-offset-2 col-sm-8">
                <div class="panel panel-info" >
                    <div class="panel-heading panel-gazie">
                        <div class="panel-title">
                            <img width="7%" src="../../library/images/logo_180x180.png" />
                            <img width="5%" src="./school.png" />
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
                            <span class="input-group-addon"><?php echo MESSAGE_CLASSROOM_REGISTRATION; ?></span>
                            <select required class="form-control" style="padding: 2px 2px; height: 30px;" name="student_classroom_id" id="student_classroom_id">
                                <option value="">------------------</option>
                                <?php
                                $registration->select_classroom();
                                foreach ($registration->classroom_data as $row):
                                    ?>
                                    <option value="<?php echo $row["id"] ?>"><?php echo $row["classe"] . ' ' . $row["sezione"] . ' ' . MESSAGE_CLASSROOM_TEACHER . ' ' . $row["user_firstname"] . ' ' . $row["user_lastname"]; ?></option>
                                <?php endforeach ?>
                            </select>

                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input id="student_firstname" type="text"  pattern="[a-zA-Z\ ]{2,30}" name="student_firstname" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_FIRSTNAME; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input id="student_lastname" type="text"  pattern="[a-zA-Z\ ]{2,30}" name="student_lastname" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_LASTNAME; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input id="student_name" type="text"  pattern="[a-zA-Z0-9]{2,64}" name="student_name" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_USERNAME; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="student_password_new" type="password"  pattern=".{8,}" name="student_password_new" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_PASSWORD; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="student_password_repeat" type="password"  pattern=".{8,}" name="student_password_repeat" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_PASSWORD_REPEAT; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
                            <input id="student_email" type="email" name="student_email" required class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_EMAIL; ?>" />
                        </div>
                        <div style="padding-bottom: 25px;" class="input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-phone"></i></span>
                            <input id="student_telephone" type="tel" name="student_telephone" class="form-control" style="height: 34px;"  placeholder="<?php echo WORDING_REGISTRATION_TELEPHONE; ?>" />
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
                            <a style="float:left;" href="student_login.php"><?php echo WORDING_BACK_TO_LOGIN; ?></a>
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
                        <img width="7%" src="../../library/images/logo_180x180.png" />
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
                        <input style="float:right;" class="btn btn-info"  onclick="location.href = 'student_login.php';"  type="submit" name="register" value="<?php echo WORDING_GO_TO_LOGIN; ?>" />
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
                        <img width="7%" src="../../library/images/logo_180x180.png" />
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
