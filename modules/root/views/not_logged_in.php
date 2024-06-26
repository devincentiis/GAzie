<?php
if (is_numeric(substr($table_prefix,-4))) {
	// se è stato uno studente ad aver fatto il logout lo riporto sulla giusta pagina
	header("Location: ../school/student_login.php");
	exit;
}

include('_header.php');?>
<script src='../../js/sha256/forge-sha256.min.js'></script>
<form method="post" onsubmit="document.getElementById('login-password').value=forge_sha256(document.getElementById('login-password').value);" action="login_user.php" name="loginform" id="logform">
  <div id="loginbox" style="margin-top:5%;" class="mainbox animated fadeInDown col-sm-offset-4 col-sm-4">
      <div class="panel panel-info" >
          <div class="panel-heading panel-gazie">
              <div class="panel-title">
                  <?php echo MESSAGE_LOG_ADMIN; ?>
              </div>
              <div style="color: red; float:right; font-size: 100%; position: relative; top:-10px"></div>
          </div>
          <div style="padding-top:10px" class="panel-body" >
		<div><b><?php echo MESSAGE_WELCOME_ADMIN .' </b>'. MESSAGE_INTRO_ADMIN; ?><div>
              </br>
              <div class="bg-info"><?php echo MESSAGE_PSW_ADMIN; ?></div>
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
                  <input id="user_name" type="text" name="user_name" required class="form-control" style="height: 34px;" placeholder="<?php echo WORDING_USERNAME; ?>" />
              </div>

              <div style="padding-bottom: 25px;" class="input-group">
                  <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                  <input type="password" autocomplete="off" required style="height: 34px;" id="login-password" class="form-control" name="user_password" placeholder="<?php echo WORDING_PASSWORD; ?>">
              </div>
              <div id="capsWarning" class="alert alert-warning col-sm-12" style="display:none;">Blocco maiuscole attivato! Caps lock on! Bloqueo de mayusculas!</div>
              <div style="padding-top:10px">
                  <div class="col-lg-10 controls">
                      <a style="float:left;" href="login_password_reset.php"><?php echo WORDING_FORGOT_MY_PASSWORD; ?></a>
                  </div>
               <!--     <div class="col-sm-6">
                      <input  style="float:left;"  type="checkbox" id="user_rememberme" name="user_rememberme" value="1" />
                      <label for="user_rememberme"><?php // echo WORDING_REMEMBER_ME; ?></label>
                  </div> -->
                  <div class="col-lg-2">
                      <input style="float:right;" class="btn btn-success"  name="login" type="submit" value="<?php echo WORDING_LOGIN; ?>" >
                  </div>
              </div>
              <div style="padding-top:10px" class="form-group">
              <!--    <div class="col-sm-6 controls">
                      <a style="float:left;" href="login_register.php"><?php //echo WORDING_REGISTER_NEW_ADMIN; ?></a>
                  </div>-->
              </div>
          </div>
		<?php if (@checkSchool()) {
		?>
			<div style="padding-top:10px" class="panel-body" >
				<div style="padding-top:10px" class="form-group">
					<div class="col-sm-12 controls">
						<a href="../school/student_login.php" >
							<?php echo WORDING_LOGIN_AS_STUDENT; ?>
							<img src="../school/school.png">
						</a>
					</div>
				</div>

			</div>
		<?php }  ?>
      </div>
  </div>
</form>


<?php
include('_footer.php');

function checkSchool() {
    global $gTables;
    $exist_cr=gaz_dbi_query("SHOW TABLES LIKE '" . $gTables['classroom'] ."'");
    if (gaz_dbi_num_rows($exist_cr) >= 1){
        $ns = gaz_dbi_record_count($gTables['classroom'], 1);
        if ($ns >= 1) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}
?>
