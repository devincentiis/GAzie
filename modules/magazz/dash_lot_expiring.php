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

// Antonio Germani - controllo scadenze articoli con lotti
$query="SELECT codice, descri FROM " . $gTables['artico'] . " WHERE  lot_or_serial = '1'";
$result = gaz_dbi_query($query);
$cod=0;
$inscad=0;
$lotinscad=array();
while ($row = gaz_dbi_fetch_array($result)) {
	$lm -> getAvailableLots($row['codice'],0);
	if (count($lm->available) > 0) {
		foreach ($lm->available as $v_lm) {
			// 1 giorno è 86400 secondi ;  3 mesi sono 15552000
			if (!empty($v_lm['expiry']) && strtotime($v_lm['expiry'])>0 and (strtotime($v_lm['expiry'])-15552000)<=strtotime (date("Ymd")) and strtotime($v_lm['expiry']) > strtotime (date("Ymd"))) { // lotti prossimi alla scadenza
				$lotinscad[$inscad]['codice']=$row['codice'];
				$lotinscad[$inscad]['descri']=$row['descri'];
				$lotinscad[$inscad]['identifier']=$v_lm['identifier'];
				$lotinscad[$inscad]['expiry']=$v_lm['expiry'];
				$lotinscad[$inscad]['rest']=$v_lm['rest'];
				$inscad++;
			}
		}
	}
}
?>
        <div class="panel panel-warning col-sm-12">
            <div class="box-header company-color">
                <b><?php echo $script_transl['inscalot']; ?></b>
				<a class="pull-right dialog_grid" id_bread="<?php echo $grr['id_bread']; ?>" style="cursor:pointer;"><i class="glyphicon glyphicon-cog"></i></a>
            </div>
            <div class="box-body">
<?php

if (count($lotinscad)>0){ // visualizzo scadenzario lotti sono se sono presenti
?>
                <table id="inscad" class="table table-bordered table-striped table-responsive dataTable" role="grid" aria-describedby="clienti_info">
                    <thead>
                        <tr role="row">
                            <th  tabindex="0" rowspan="1" colspan="1" style="width: 120px;"><?php echo $script_transl['cod']; ?></th>
                            <th  tabindex="0" rowspan="1" colspan="1" style="width: 310px;"><?php echo $script_transl['des']; ?></th>
							<th  tabindex="0" rowspan="1" colspan="1" style="width: 120px;" ><?php echo $script_transl['lot']; ?></th>
                            <th  tabindex="0" rowspan="1" colspan="1" style="width: 120px;" ><?php echo $script_transl['sca_scadenza']; ?></th>
							<th  tabindex="0" rowspan="1" colspan="1" style="width: 110px;" ><?php echo $script_transl['res']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- lotti in scadenza -->
                        <?php
						for ($x=0; $x<count($lotinscad); $x++){
							 echo "<tr role='row'>";
							 echo "<td align='left'>" . $lotinscad[$x]['codice'] . "</td>";
							 echo "<td align='left'>" . substr($lotinscad[$x]['descri'],0,21) . "</td>";
							 echo "<td align='left'>" . $lotinscad[$x]['identifier'] . "</td>";
							 echo "<td align='left'>" . gaz_format_date($lotinscad[$x]['expiry']) . "</td>";
							 echo "<td align='left'>" . gaz_format_number($lotinscad[$x]['rest']) . "</td>";
							echo "</tr>";
						}
                        ?>
                    </tbody>
                  </table>
<?php
} else {
	echo '<div class="bg-success"><h4>NON CI SONO PRODOTTI SCADENTI PRIMA DI 3 MESI </h4></div>';
}
?>	</div>
        </div>
