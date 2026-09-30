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
$msg = "";

function getMovements($account, $date_ini, $date_fin) {
    global $gTables;
    $where = " codcon = $account AND datreg BETWEEN '$date_ini' AND '$date_fin' AND protoc>0";
    $orderby = " datreg, id_tes ASC ";
    $select = $gTables['tesmov'] . ".id_tes," . $gTables['tesmov'] . ".descri AS tesdes,datreg,codice,protoc,numdoc,datdoc," . $gTables['clfoco'] . ".descri,import*(darave='D') AS dare,import*(darave='A') AS avere";
    $table = $gTables['clfoco'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON " . $gTables['clfoco'] . ".codice = " . $gTables['rigmoc'] . ".codcon LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes ";
    $m = array();
    $rs = gaz_dbi_dyn_query($select, $table, $where, $orderby);
    $anagrafica = new Anagrafica();
    while ($r = gaz_dbi_fetch_array($rs)) {
        $m[] = $r;
    }
    return $m;
}
if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
	$form['datini']='01/01/'.date('Y', strtotime('-1 year'));
    $form['datfin'] = date("d/m/Y");
	$form['hidden_req']='';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'] . ' ';
}else{
	$form['datini']=substr($_POST['datini'],0,10);
	$form['datfin']=substr($_POST['datfin'],0,10);
	$form['hidden_req']='';
    $form['ritorno'] = substr($_POST['ritorno'],0,30);
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new acquisForm();
$span = 9;
$saldo = 0.00;
$m = getMovements(intval($_GET['id']), gaz_format_date($form['datini'],true), gaz_format_date($form['datfin'],true));
?>
<script>
    $(function () {
        $("#datini,#datfin").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
        $("#datini,#datfin").change(function () {
            this.form.submit();
        });
	});
</script>
<div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['title'];?></div>
<form class="form-horizontal" role="form" method="post" name="contab" enctype="multipart/form-data" >
    <input type="hidden" value="<?php echo $form['hidden_req'] ?>" name="hidden_req" />
    <input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno">
    <div class="panel panel-default">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12 col-md-6 col-lg-6">
                    <div class="form-group">
                        <label for="datini" class="col-sm-4 control-label"><?php echo $script_transl['datini']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="datini" name="datini" placeholder="GG/MM/AAAA" tabindex=9 value="<?php echo $form['datini']; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-6">
                    <div class="form-group">
                        <label for="datfin" class="col-sm-4 control-label"><?php echo $script_transl['datfin']; ?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="datfin" name="datfin" placeholder="GG/MM/AAAA" tabindex=7 value="<?php echo $form['datfin']; ?>">
                        </div>
                    </div>
                </div>                    
			</div>
		</div>
	</div>                    
<?php
echo "<div class=\"table-responsive\"><table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
if (sizeof($m) > 0) {
        echo "<tr>";
        $linkHeaders = new linkHeaders($script_transl['header']);
        $linkHeaders->output();
        echo "</tr>";
        foreach($m as $key=>$mv){
            $saldo += $mv['dare'];
            $saldo -= $mv['avere'];
            echo "<tr class=\"FacetDataTD\"><td>" . gaz_format_date($mv["datreg"]) . " &nbsp;</td>";
            echo "<td align=\"center\"><a href=\"../contab/admin_movcon.php?id_tes=" . $mv["id_tes"] . "&Update\">" . $mv["id_tes"] . "</a> &nbsp</td>";
            echo '<td>' . $mv["tesdes"] . '</td>';
            if (!empty($mv['numdoc'])) {
                echo "<td align=\"center\">" . $mv["protoc"] . " &nbsp;</td>";
                echo "<td align=\"center\">" . $mv["numdoc"] . " &nbsp;</td>";
                echo "<td align=\"center\">" . gaz_format_date($mv["datdoc"]) . " &nbsp;</td>";
            } else {
                echo "<td colspan=\"3\"></td>";
            }
            echo "<td align=\"right\">" . gaz_format_number($mv['dare']) . " &nbsp;</td>";
            echo "<td align=\"right\">" . gaz_format_number($mv['avere']) . " &nbsp;</td></tr>";
        }
} else {
    echo "<tr><td class=\"FacetDataTDred\" align=\"center\">" . $script_transl['errors'][4] . "</td></tr>\n";
}
echo "</table></div></form>";
?>
<?php

require("../../library/include/footer.php");
?>