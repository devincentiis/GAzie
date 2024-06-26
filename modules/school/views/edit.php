<?php include('_header.php'); ?>
<?php
// show potential errors / feedback (from login object)
if (isset($login)) {
    if ($login->errors) {
        foreach ($login->errors as $error) {
            echo $error;
        }
    }
    if ($login->messages) {
        foreach ($login->messages as $message) {
            echo $message;
        }
    }
}
?>

<!-- clean separation of HTML and PHP -->
<h2><?php echo htmlspecialchars($_SESSION['student_name']); ?> <?php echo WORDING_EDIT_YOUR_CREDENTIALS; ?></h2>

<!-- edit form for username / this form uses HTML5 attributes, like "required" and type="email" -->
<form method="post" action="edit.php" name="student_edit_form_name">
    <label for="student_name"><?php echo WORDING_NEW_USERNAME; ?></label>
    <input id="student_name" type="text" name="student_name" pattern="[a-zA-Z0-9]{2,64}" required /> (<?php echo WORDING_CURRENTLY; ?>: <?php echo htmlspecialchars($_SESSION['student_name']); ?>)
    <input type="submit" name="student_edit_submit_name" value="<?php echo WORDING_CHANGE_USERNAME; ?>" />
</form><hr/>

<!-- edit form for user email / this form uses HTML5 attributes, like "required" and type="email" -->
<form method="post" action="edit.php" name="student_edit_form_email">
    <label for="student_email"><?php echo WORDING_NEW_EMAIL; ?></label>
    <input id="student_email" type="email" name="student_email" required /> (<?php echo WORDING_CURRENTLY; ?>: <?php echo htmlspecialchars($_SESSION['student_email']); ?>)
    <input type="submit" name="student_edit_submit_email" value="<?php echo WORDING_CHANGE_EMAIL; ?>" />
</form><hr/>

<!-- edit form for user's password / this form uses the HTML5 attribute "required" -->
<form method="post" action="edit.php" name="student_edit_form_password">
    <label for="student_password_old"><?php echo WORDING_OLD_PASSWORD; ?></label>
    <input id="student_password_old" type="password" name="student_password_old" autocomplete="off" />

    <label for="student_password_new"><?php echo WORDING_NEW_PASSWORD; ?></label>
    <input id="student_password_new" type="password" name="student_password_new" autocomplete="off" />

    <label for="student_password_repeat"><?php echo WORDING_NEW_PASSWORD_REPEAT; ?></label>
    <input id="student_password_repeat" type="password" name="student_password_repeat" autocomplete="off" />

    <input type="submit" name="student_edit_submit_password" value="<?php echo WORDING_CHANGE_PASSWORD; ?>" />
</form><hr/>

<!-- backlink -->
                            <a style="float:left;" href="student_login.php"><?php echo WORDING_BACK_TO_LOGIN; ?></a>

<?php include('_footer.php'); ?>
