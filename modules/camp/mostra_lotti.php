<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
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
// >>>>>> Antonio Germani -- MOSTRA Lotti  <<<<<<

require("../../library/include/datlib.inc.php");
$lm = new lotmag;
$admin_aziend=checkAdmin();
$codice = filter_input(INPUT_GET, 'codice');
$lm -> getAvailableLots($codice,0);
require("../../library/include/header.php");

if (isset($_POST['close'])){
	foreach (glob("../../modules/camp/tmp/*") as $fn) {// prima cancello eventuali precedenti file temporanei
             unlink($fn);
    } // poi chiudo la finestra e esco
	echo "<script>window.close();</script>";exit;
}
?>

<body>
<div align="center" class="FacetFormHeaderFont">Elenco lotti disponibili per <?php echo $codice; ?></div>
<table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
    	<thead>
            <tr class="FacetDataTD">
				<th align="center" >Id lotto
                </th>
                <th align="center" >Numero lotto
                </th>
				<th align="center" >Scadenza
                </th>
				<th align="center" >Disponibilità
                </th>
                <th align="center" >Certificato
                </th>
            </tr>
			</thead>
<?php
	foreach (glob("../../modules/camp/tmp/*") as $fn) {// prima cancello eventuali precedenti file temporanei
             unlink($fn);
    }
	$tot=0;
	if (count($lm->available) > 0) {
		$count=array();
        foreach ($lm->available as $v_lm) {
			$key=$v_lm['identifier']." - ".gaz_format_date($v_lm['expiry']); // chiave per il conteggio dei totali raggruppati per lotto e scadenza
			if( !array_key_exists($key, $count) ){ // se la chiave ancora non c'è nell'array
				// Aggiungo la chiave con il rispettivo valore iniziale
				$count[$key] = $v_lm['rest'];
			} else {
				// Altrimenti, aggiorno il valore della chiave
				$count[$key] += $v_lm['rest'];
			}
			$tot+=$v_lm['rest'];
               $img="";
               echo '<tr class="FacetDataTD"><td class="FacetFieldCaptionTD">'
               . $v_lm['id']
               . '</td><td>' . $v_lm['identifier']
               . '</td><td>' . gaz_format_date($v_lm['expiry'])
				. '</td><td>' . gaz_format_quantity($v_lm['rest'], 0, $admin_aziend['decimal_quantity'])
                .'</td><td>';

				If (file_exists(DATA_DIR.'files/' . $admin_aziend['company_id'])>0) {
					// recupero il filename
					$dh = opendir(DATA_DIR.'files/' . $admin_aziend['company_id']);
					while (false !== ($filename = readdir($dh))) {
						$fd = pathinfo($filename);
						$r = explode('_', $fd['filename']);
						if ($r[0] == 'lotmag' && $r[1] == $v_lm['id']) {
							// assegno il nome file a img
							$img = $fd['basename'];
							}
						}
						if (strlen($img)>0) {
							$tmp_file = DATA_DIR."files/".$admin_aziend['company_id']."/".$img;
							// sposto nella cartella di lettura il relativo file temporaneo
							copy($tmp_file, "../../modules/camp/tmp/".$img);
							echo '<img src="../../modules/camp/tmp/'.$img.'" alt="certificato lotto" width="50" border="1" style="cursor: -moz-zoom-in;" onclick="this.width=500;" ondblclick="this.width=50;" />';
							echo '<a class="btn btn-xs  btn-elimina" href="../../modules/camp/tmp/'.$img.'" download><i class="glyphicon glyphicon-download"></i></a></td>';
							} else {
									echo '<i class="glyphicon glyphicon-eye-close"></i>';
								}
				}
            }
?>
		</table>
		</body>
		<div class="panel panel-default gaz-table-form">
			<div class="container-fluid">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<div class="text-center"><b>Totale disponibilità per lotti raggruppati</b>
							</div>
						</div>
					</div>
				</div><!-- chiude row  -->
				<?php
				foreach($count as $key => $val){
					?>
					<div class="row">
						<div class="form-group">
							<div class="col-sm-6">
							<?php
							echo "<b>Lotto:</b> ",$key;
							?>
							</div>
							<div class="col-sm-6">
							<?php
							echo "<b>Disponibile:</b> ",$val;
							?>
							</div>
						</div>
					</div><!-- chiude row  -->
					<?php
				}
				?>
				<div class="row">
						<div class="form-group">
							<div class="col-sm-6">
							<?php
							echo "<b>Totale prodotto disponibile:</b> ";
							?>
							</div>
							<div class="col-sm-6">
							<?php
							echo $tot;
							?>
							</div>
						</div>
					</div>
			</div>
		</div>
		<?php
	} else {
		echo '<div><button class="btn btn-xs btn-danger" type="image" >Non ci sono lotti disponibili.</button></div>';
    }
	?>
	<form method="post" name="closewindow">
	<input type="submit" title="elimina file temporanei e chiudi finestra" name="close" value="X"  style="float:right">
	</form>
