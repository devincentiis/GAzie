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
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
$gForm = new informForm();
$j=date('Y');
if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['hidden_req'] = '';
  $form['search_partner'] = '';
  $form['id_partner'] = 0;
  if (isset($_GET['id_partner'])){
    $form['id_partner'] = intval($_GET['id_partner']);
    $partner = gaz_dbi_get_row($gTables['clfoco'], 'codice',  $form['id_partner']);
    $form['search_partner'] = $partner['descri'];
    $form['descri_partner'] = $partner['descri'];
  }
  $form['year'] = date('m')>6 ? ($j+1) : $j;
} else { // accessi successivi
  $form['ritorno'] = $_POST['ritorno'];
  $form['hidden_req'] = $_POST['hidden_req'];
  $form['year'] = intval($_POST['year']);
  $form['id_partner'] = intval($_POST['id_partner']);
  $form['search_partner'] = '';
  if ($form['id_partner']>0){
    $partner = gaz_dbi_get_row($gTables['clfoco']." LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id", 'codice',  $form['id_partner']);
    $form['search_partner'] = $partner['ragso1'];
  }
  if ($_POST['hidden_req'] == 'change_partner') {
    $form['id_partner'] = 0;
    $form['search_partner'] = '';
    $form['descri_partner'] = '';
    $form['hidden_req'] = '';
  }
}
require("../../library/include/header.php");
$script_transl = HeadMain(0,['custom/autocomplete']);
?>
<script>
$( function() {
  $( "#search_partner" ).autocomplete({
    source: "search.php?opt=partner",
    minLength: 3,
    html: true, // optional (jquery.ui.autocomplete.html.js required)

    // optional (if other layers overlap autocomplete list)
    open: function(event, ui) {
        $(".ui-autocomplete").css("z-index", 1000);
    },
    select: function(event, ui) {
        $("#id_partner").val(ui.item.value);
        $(this).closest("#calform").submit();
    }
  });
});

function printPdf(urlPrintDoc){
  $(function(){
    $('#framePdf').attr('src',urlPrintDoc);
    $('#framePdf').css({'height': '100%'});
    $('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
    $('#closePdf').on( "click", function() {
      $('.framePdf').css({'display': 'none'});
    });
  });
};

</script>
<div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
	<div class="col-lg-12">
		<div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
		<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
	</div>
	<iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
</div>
<form method="POST" id="calform">
<input type="hidden" name="ritorno" value="<?php echo $form['ritorno']; ?>">
<input type="hidden" name="hidden_req" value="<?php echo $form['hidden_req']; ?>">
<div class="h3 text-center">Genera un calandario olandese </div>
<div class="panel panel-default div-bordered gaz-table-form">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="year" class="col-sm-6 control-label text-right">Anno</label>
<?php
$ys=[];
for($i=-1;$i<=3;$i++){
 $ys[($j+$i)]=($j+$i);
}
$gForm->variousSelect('year',$ys,$form['year'],'',false,'year',false);
?>
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="id_partner" class="col-sm-6 control-label text-right">with <span style="color: red;"><i class="fa fa-heart"></i></span> to:</label>
<?php
$gForm->selectPartner($form['search_partner'], $form['id_partner'], $admin_aziend['mascli']);
?>
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12 text-center"><a class="btn btn-md btn-warning" style="cursor:pointer;" <?php echo 'onclick="printPdf(\'calendar_print.php?year='.$form["year"].'&clfoco='.$form["id_partner"].'\')"'; ?> > <i class="glyphicon glyphicon-print"></i> <?php echo $script_transl['print']; ?></a></div>
    </div><!-- chiude row  -->
  </div>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
