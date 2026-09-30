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
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin(9);

if (count($_POST) > 10) {
  foreach ($_POST as $key => $value) {
    if ($key == 'admin_mail_pass' || $key == 'admin_smtp_password' ) {
      $tripsw=trim($value);
      if ( strlen($tripsw)>=8 ) {
        gaz_dbi_query("UPDATE ".$gTables['config']." SET cvalue = TO_BASE64(AES_ENCRYPT('".addslashes($value)."','".$_SESSION['aes_key']."')) WHERE variable = '".$key."'");
      }
    } else {
      gaz_dbi_put_row($gTables['config'], 'variable', $key, 'cvalue', $value);
    }
  }
  header("Location: set_config_data.php?ok_insert");
}
$script = basename($_SERVER['PHP_SELF']);
require("../../language/" . $admin_aziend['lang'] . "/menu.inc.php");
require("./lang." . $admin_aziend['lang'] . ".php");
if (isset($script)) { // se è stato tradotto lo script lo ritorno al chiamante
  $script_transl = $strScript[$script];
}
$script_transl = $strCommon + $script_transl;
$result = gaz_dbi_dyn_query("*", $gTables['config'], 1, ' id ASC', 0, 1000);
?>
<script>
$('#uppersave').click(function(){
  $("#bottomsave").click();
  return false;
});

$("#sbmt-form").submit(function (e) {
  $.ajax({
    type: "POST",
    url: "../root/set_config_data.php?ok_insert",
    data: $("#sbmt-form").serialize(), // serializes the form's elements.
    success: function (data) {
        $("#edit-modal .modal-sm").css('width', '100%');
        $("#edit-modal .modal-body").html(data);
        $('#edit-modal').animate({ scrollTop: 0 }, 'slow');
  },
  error: function(data){
      alert(data);
  }
});
  e.preventDefault(); // avoid to execute the actual submit of the form.
});
</script>
<form method="post" id="sbmt-form">
<div class="panel panel-default gaz-table-form div-bordered">
  <div id="generale" class="tab-pane fade in active">
  <div class="container-fluid">
      <div class="FacetDataTD">
        <div class="alert alert-danger text-center" role="alert">
          <strong>Attenzione</strong> la modifica di questi valori può compromettere la funzionalità di GAzie!
        </div>
        <div class="text-right col-xs-12"><div class="btn btn-sm btn-warning" id="uppersave">Salva</div></div>
          <?php
          if (isset($_GET["ok_insert"])) {
            echo '<div class="alert alert-success text-center col-xs-12" role="alert"><strong>*** Le modifiche sono state salvate! ***</strong></div>';
          }
          if (gaz_dbi_num_rows($result) > 0) {
            while ($r = gaz_dbi_fetch_array($result)) {
              ?>
              <div class="form-group row">
                <label for="input<?php echo $r["id"]; ?>" class="col-sm-4 control-label"><?php echo $r["description"]; ?></label>
                <div class="col-sm-8">
              <?php
              if ($r['variable'] == 'theme') {
                echo '<select name="' . $r['variable'] . '" class="form-control input-sm">';
                $relativePath = '../../library/theme/';
                if ($handle = opendir($relativePath)) {
                  while ($file = readdir($handle)) {
                    if (($file == ".") or ( $file == "..") or ( $file == ".svn"))
                      continue;
                    $selected = "";
                    if ($r["cvalue"] == $file) {
                        $selected = " selected ";
                    }
                    echo "<option value=\"" . $file . "\"" . $selected . ">" . ucfirst($file) . "</option>";
                  }
                  closedir($handle);
                  echo "</select>";
                }
              } else if ($r['variable'] == 'admin_mail_pass' || $r['variable'] == 'admin_smtp_password' ) {
                $title='';
                if ( $debug_active == true ){ // con il debug attivo mostro le password in chiaro in title
                  $rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(cvalue),'".$_SESSION['aes_key']."') FROM ".$gTables['config']." WHERE variable = '".$r["variable"]."'");
                  $rdec=gaz_dbi_fetch_row($rsdec);
                  $title = $rdec?'title="'.$rdec[0].'"':'';
                }
?>
                <input type="password" class="form-control input-sm text-bold" id="input<?php echo $r["id"]; ?>" name="<?php echo $r['variable']; ?>" placeholder="Invisibile, digita solo se vuoi cambiarla" value='' <?php echo $title; ?> >
<?php
              } else {
?>
                <input type="text" class="form-control input-sm" id="input<?php echo $r["id"]; ?>" title="<?php echo $r['variable']; ?>" name="<?php echo $r['variable']; ?>" placeholder="<?php echo $r['variable']; ?>" value='<?php echo $r["cvalue"]; ?>' >
<?php
              }
              ?>
              <?php
              echo '</div></div>';
            }
          }
?>
          <div class="form-group">
              <div class="col-sm-offset-6 col-sm-6">
                  <button type="submit" id="bottomsave" class="btn btn-warning">Salva</button>
              </div>
          </div>
      </div>
    </div>
  </div>
</div>
</form>
