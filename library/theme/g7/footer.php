<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
  (http://www.devincentiis.it)
  <http://gazie.sourceforge.net>
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
if (isset($styles) && is_array($styles)) {
    foreach ($styles as $v) {
        ?>
        <link href="../../library/theme/g7/<?php echo $v; ?>" rel="stylesheet" type="text/css" />
        <?php
    }
}
$period=(!empty($period))?$period:'15';
if (!isset($_SESSION['menu_alerts_lastcheck'])||((round(time()/60)-$_SESSION['menu_alerts_lastcheck'])> $period )){ // sono passati $period minuti
	// non ho mai controllato se ci sono nuovi ordini oppure è passato troppo tempo dall'ultimo controllo vado a farlo
		echo '<script>menu_check_from_modules();</script>';
} elseif(isset($_SESSION['menu_alerts']) && count($_SESSION['menu_alerts'])>=1) {
        foreach($_SESSION['menu_alerts'] as $k=>$v) {
            // se ho i dati per visualizzare il bottone relativo al modulo sincronizzato faccio il load per crearlo (mod,title,button,label,link,style)
            if ( is_array($v) && count($v) > 4 ) { // se ho i dati sufficienti creo l'elemento bottone tramite js
                echo "<script>menu_alerts_check('".$k."','".addslashes($v['title'])."','".addslashes($v['button'])."','".addslashes($v['label'])."','".addslashes($v['link'])."','".$v['style']."');</script>";
            }
        }
}
// solo quando verrà aggiornato KINT potremo utilizzarlo, tolto sulla 7.43
//if ( $debug_active==true ) echo "<div>".d($GLOBALS, $_SERVER)."</div>";

$contact_link=(isset($contact_link))?$contact_link:'';
?>

<!-- questo è contenuto in library/theme/g7/footer.php -->
<div class="navbar navbar-fixed-bottom main-footer" >
  <div class="col-lg-4 col-xs-12">
    Version <?php echo GAZIE_VERSION; ?>
  </div>
  <div class=" text-center col-lg-4 hidden-xs">
    <?php
    if ( $debug_active == true ){
      echo '<a class="btn btn-xs btn-danger" href="" style="cursor:default;"> DEBUG ATTIVATO </a> '.$_SESSION['aes_key'].' <a class="btn btn-xs btn-info" href="../../passhash.php" > HASHES UTILITY </a>';
    } ;
    ?>
  </div>
  <div class="text-right col-lg-4 hidden-xs">
    <a  target="_new" href="https://<?php echo $contact_link; ?>">https://<?php echo $contact_link; ?></a>
  </div>
</div>
<script src="../../js/jquery.ui/jquery-ui.min.js"></script>
<script><!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
  $.widget.bridge('uibutton', $.ui.button);
</script>
<script src="../../js/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="../../library/theme/g7/smartmenus-master/jquery.smartmenus.js" type="text/javascript"></script>
<script src="../../library/theme/g7/smartmenus-master/bootstrap/jquery.smartmenus.bootstrap.js" type="text/javascript"></script>

<script src="../../js/jquery.ui/datepicker-<?php echo substr($admin_aziend['lang'], 0, 2); ?>.js"></script>
<script src="../../js/custom/jquery.ui.autocomplete.html.js"></script>
<script src="../../js/custom/gz-library.js"></script>
<script src="../../js/tinymce/tinymce.min.js"></script>
<script src="../../js/custom/tinymce.js"></script>
<script>
// setto comunque dei check intervallati dei minuti inseriti in configurazione avanzata azienda 15*60*1000ms perché non è detto che si facciano i refresh, ad es. se il browser rimane fermo sulla stessa pagina per un lungo periodo > $period
setInterval(menu_check_from_modules,<?php echo intval(intval($period)*60000);?>);</script>
</div><!-- chiude <div class="container-fluid gaz-body"> presente su header.php -->
</body>
</html>
