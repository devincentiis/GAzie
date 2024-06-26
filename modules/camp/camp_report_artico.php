<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
require("../../library/include/datlib.inc.php");
require ("../../modules/magazz/lib.function.php");
$admin_aziend = checkAdmin();$mt=0;


if (isset($_POST['order_by'])&& !isset($_POST['button_mostra_tutto'])&& !isset($_POST['button_no_mostra_tutto'])) { // controllo se vengo da una richiesta di ordinamento
    $rn = filter_input(INPUT_POST, 'row_no');
    $ob = filter_input(INPUT_POST, 'order_by');
    $so = filter_input(INPUT_POST, 'sort');
    $cs = filter_input(INPUT_POST, 'cosear');
    $ca = filter_input(INPUT_POST, 'codart');
	$mt = $_POST['mostra'];

} else {
    $rn = "0";
    $ob = "descri";
    $so = "ASC";
    $cs = "";
    $ca = "";
	if (isset($_POST['mostra'])){
		$mt = $_POST['mostra'];
	} else {$mt=0;}
}

if (isset ($_POST['button_no_mostra_tutto'])) {unset($_POST['order_by']);$mt=1;$_POST['mostra']=$mt;}

if (isset ($_POST['button_mostra_tutto'])) {unset($_POST['order_by']);$mt=0;}


require("../../library/include/header.php");
?>
<script type="text/javascript">
    $(window).scroll(function ()
    {
        if ($(document).height() <= $(window).scrollTop() + 1 + $(window).height()) {
            loadmore();
        }
    });
    $(window).on('load', function () {
        loadmore();
    });
    function loadmore()
    {
        var rn = $("#row_no").val();
        var ob = $("#order_by").val();
        var so = $("#sort").val();
        var ca = '<?php echo $cs ?>';
		var mt = '<?php echo $mt ?>';

        $.ajax({
            type: 'post',
            url: 'report_artico_scroll.php',
            data: {
                rowno: rn,
                orderby: ob,
                sort: so,
                codart: ca,
				mostra: mt

            },
            beforeSend: function () {
                $('#loader-icon').show();
            },
            complete: function () {
                $('#loader-icon').hide();
            },
            success: function (response) {
                $("#all_rows").append(response); //append received data into the element
                $("#row_no").val(Number(rn) + <?php echo PER_PAGE; ?>);
                $('.gazie-tooltip').tooltip(
                        {html: true,
                            placement: 'auto bottom',
                            delay: {show: 50},
                            title: function () {
                                return '<span>' + this.getAttribute('data-label') + '</span><img src="../root/view.php?table=artico&value=' + this.getAttribute('data-id') + '" onerror="this.src=\'../../library/images/link_break.png\'" alt="' + this.getAttribute('data-label') + '"  style="max-height:150px;" />';
                            }
                        });
            }
        });
    }

    $(function () {
        $('.orby').click(function () {
            var v = $(this).attr('data-order');
            var actual_ob = $("#order_by").val();
            var actual_so = $("#sort").val();
            if (v === actual_ob) { // è la stessa colonna inverto l'ordine
                if (actual_so === 'ASC') {
                    $("#sort").val('DESC');
                } else {
                    $("#sort").val('ASC');
                }
            } else { // una colonna diversa la cambio
                $("#order_by").val(v);
            }
            $("#row_no").val(0); // quando richiedo un nuovo ordinamento devo necessariamente ricominciare da zero
            $("#form").submit();
        });
    });

</script>
<?php
$script_transl = HeadMain(0, array('custom/autocomplete'));
$gForm = new magazzForm();
?>
<div class="text-center"><b><?php echo $script_transl['title']; ?></b></div>
<form method="POST" id="form">
<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
	<p><b>articolo:</b></p>
	<p>codice:</p>
	<p class="ui-state-highlight" id="idcodice"></p>
	<p>Descrizione</p>
	<p class="ui-state-highlight" id="iddescri"></p>
</div>
<div class="panel panel-info col-lg-6">
<?php if ($mt==1) {	?>
	<label for="codice" ><?php echo "Elenco di tutti gli articoli"; ?></label>

			<button type="submit" name="button_mostra_tutto" title="Inverti" class="btn btn-default btn-sm"  >
<i class="glyphicon glyphicon-refresh" style="color:green">

<?php } else {?>
<label for="codice" ><?php echo "Elenco degli articoli da mostrare nel Q.d.c."; ?></label>

			<button type="submit" name="button_no_mostra_tutto" title="Inverti" class="btn btn-default btn-sm"  >
<i class="glyphicon glyphicon-refresh" style="color:red">

<?php } ?>
		</i></button>

	<input type="hidden" name="mostra"  value="<?php echo $mt; ?>">
</div>
<!-- </form>

<form method="POST" id="form"> -->

    <div class="panel panel-info col-lg-6">

        <div class="container-fluid">
            <label for="codice" class="col-lg-3 control-label"><?php echo $script_transl['codice'].'-'.$script_transl['descri']; ?></label>
            <?php
            $select_artico = new selectartico("codart");
            $select_artico->addSelected($ca);
            $select_artico->output(substr($cs, 0, 20), 'C', "col-lg-3");
            ?>
        </div>
    </div>


	<div class="panel panel-default">
        <div id="gaz-responsive-table"  class="container-fluid">
            <table class="table table-responsive table-striped table-condensed cf">
                <thead>
                    <tr class="bg-success">
                        <th>
                            <a href="#" class="orby" data-order="codice">
                                <?php echo $script_transl["codice"]; ?>
                            </a>
                        </th>
                        <th>
                            <a href="#" class="orby" data-order="descri">
                                <?php echo $script_transl["descri"]; ?>
                            </a>
                        </th>
						<th class="text-center">
                                <?php echo $script_transl["class"]; ?>
                        </th>
                        <th>
                            <a href="#" class="orby" data-order="good_or_service">
                                <?php echo $script_transl["good_or_service"]; ?>
                            </a>
                        </th>
                        <th class="text-center">
                            <a href="#" class="orby" data-order="catmer">
                                <?php echo $script_transl["catmer"]; ?>
                            </a>
                        </th>
                        <th class="text-right">
                            <?php echo $script_transl["unimis"]; ?>
                        </th>

                        <th class="text-center">
                            <?php echo $script_transl["stock"]; ?>
                        </th>

						 <th class="text-center">
                            <?php echo $script_transl["lot"]; ?>
                        </th>

                        <th class="text-center">
                            <?php echo $script_transl["clone"]; ?>
                        </th>
                        <th class="text-center">
                            <?php echo $script_transl["delete"]; ?>
                        </th>
                    </tr>
                </thead>
                <tbody id="all_rows">
                </tbody>
            </table>
        </div>
    </div>
    <input type="hidden" name="row_no" id="row_no" value="<?php echo $rn; ?>">
    <input type="hidden" name="order_by" id="order_by" value="<?php echo $ob; ?>">
    <input type="hidden" name="sort" id="sort" value="<?php echo $so; ?>">

</form>
<div id="loader-icon"><img src="../../library/images/ui-anim_basic_16x16.gif" />
</div>
<a href="https://programmisitiweb.lacasettabio.it/quaderno-di-campagna/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:20%; z-index:2000;"> Registro di campagna è un modulo di Antonio Germani</a>
<?php
require("../../library/include/footer.php");
?>
