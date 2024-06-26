<?php
$gazTimeFormatter->setPattern('H');
$t = $gazTimeFormatter->format(new DateTime());
if ($t > 4 && $t <= 13) {
    $msg = $script_transl['morning'];
} elseif ($t > 13 && $t <= 17) {
    $msg = $script_transl['afternoon'];
} elseif ($t > 17 && $t <= 21) {
    $msg = $script_transl['evening'];
} else {
    $msg = $script_transl['night'];
}
?>
<div class="panel panel-info col-md-12" >
  <div class="box-header company-color">
    <b><?php echo ucfirst($msg) . " " . $admin_aziend['user_firstname'] . ' </b>(ip=' . $admin_aziend['last_ip'] . ')'; ?>
		<a class="pull-right dialog_grid" id_bread="<?php echo $grr['id_bread']; ?>" style="cursor:pointer;"><i class="glyphicon glyphicon-cog"></i></a>
  </div>
  <div class="img-containter">
    <a href="../config/admin_utente.php?user_name=<?php echo $admin_aziend['user_name']; ?>&Update">
      <img class="img-circle usr-picture" src="view.php?table=admin&field=user_name&value=<?php echo $admin_aziend['user_name'] ?>" alt="<?php echo $admin_aziend['user_lastname'] . ' ' . $admin_aziend['user_firstname']; ?>" title="<?php echo $script_transl['change_usr']; ?>" >
    </a>
  </div>
  <div>
    <?php echo $script_transl['access'] . $admin_aziend['Access'] . $script_transl['pass'] . gaz_format_date($admin_aziend['datpas']) ?>
  </div>
  <div>
    <a class="btn btn-default" href="../config/print_privacy_regol.php" > <?php echo $script_transl['user_regol'];?></a>
  </div>
</div>
<style>
img.usr-picture:hover {
	box-shadow: 0 0 5px #<?php echo $admin_aziend['colore']; ?>;
}
</style>
