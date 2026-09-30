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
require("../../library/include/header.php");
?>
<script type="text/javascript">
$(window).scroll(function () {
  if ($(document).height() <= $(window).scrollTop() + $(window).height()) {
    loadmore();
  }
});
$(window).on('load',(function () {
  loadmore();
}));
function loadmore() {
  var val = document.getElementById("row_no").value;
  $.ajax({
    type: 'post',
    url: 'report_assets_scroll.php',
    data: {
        getresult: val
    },
    beforeSend: function () {
        $('#loader-icon').show();
    },
    complete: function () {
        $('#loader-icon').hide();
    },
    success: function (response) {
      var content = document.getElementById("all_rows");
      content.innerHTML = content.innerHTML + response;
      document.getElementById("row_no").value = Number(val) + <?php echo PER_PAGE; ?>;
    }
  });
}
</script>
<?php
$script_transl = HeadMain();
?>
<div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['title']; ?></div>
  <div id="gaz-responsive-table" class="container-fluid table-responsive">
        <table class="table table-striped table-condensed cf">
            <thead>
                <tr>
                    <th>
                        ID
                    </th>
                    <th>
                        <?php echo $script_transl["datreg"]; ?>
                    </th>
                    <th>
                        <?php echo $script_transl["descri"]; ?>
                    </th>
                    <th>
                        <?php echo $script_transl["clfoco"]; ?>
                    </th>
                    <th class="text-right">
                        <?php echo $script_transl["amount"]; ?>
                    </th>
                    <th class="text-right">
                        <?php echo $script_transl["valamm"]; ?>
                    </th>
                </tr>
            </thead>
            <tbody id="all_rows">
            </tbody>
        </table>
</div>
<input type="hidden" id="row_no" value="0">
<div id="loader-icon"><img src="../../library/images/ui-anim_basic_16x16.gif" />
</div>
<?php
require("../../library/include/footer.php");
?>
