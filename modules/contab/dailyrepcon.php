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

  >>>>>> Antonio Germani -- MOSTRA riepilogo vendite giornaliero  <<<<<<

 */
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
function dailyrep($id_con) { // restituisce i righi delle vendite giornaliere del movimento contabile
	global $gTables;

	$query ="
      SELECT ". $gTables['rigdoc'] .".*, ". $gTables['rigdoc'] .".sconto AS rig_sconto,". $gTables['artico'] .".catmer, ". $gTables['catmer'] .".descri AS descri_cat, ". $gTables['tesdoc'] .".*, ". $gTables['aliiva'] .".aliquo
	  FROM " . $gTables['tesdoc'] . "
      LEFT JOIN ". $gTables['rigdoc'] ." ON ".$gTables['rigdoc'].".id_tes=".$gTables['tesdoc'].".id_tes
      LEFT JOIN ". $gTables['artico'] ." ON ".$gTables['artico'].".codice=".$gTables['rigdoc'].".codart
	  LEFT JOIN ". $gTables['catmer'] ." ON ".$gTables['catmer'].".codice=".$gTables['artico'].".catmer
      LEFT JOIN ". $gTables['aliiva'] ." ON ".$gTables['aliiva'].".codice=".$gTables['tesdoc'].".expense_vat
	  WHERE ". $gTables['tesdoc'].".id_con = '". $id_con ."' AND (". $gTables['rigdoc'] .".tiprig = 0 OR ". $gTables['rigdoc'] .".codric > 0)
      ORDER BY catmer DESC, ". $gTables['tesdoc'] .".id_tes ASC;
      ";
    $result = gaz_dbi_query($query); // eseguo query
	$cat=[];
	$lastest="";
	$n=0;

	while ($res=$result->fetch_assoc()){ // raggruppo per categoria e faccio le somme per categoria

		if (!$res['catmer']){
			$res['catmer']= 9999 + $n;// creo una categoria fittizia
			$n++;
		}
		if (isset($cat[$res['catmer']]['sum'])){
			$cat[$res['catmer']]['sum'] += ($res['quanti']*$res['prelis'])-((($res['quanti']*$res['prelis'])*$res['sconto'])/100)-((($res['quanti']*$res['prelis'])*$res['rig_sconto'])/100);
			$cat[$res['catmer']]['sumvat'] += (((($res['quanti']*$res['prelis'])-((($res['quanti']*$res['prelis'])*$res['sconto'])/100)-((($res['quanti']*$res['prelis'])*$res['rig_sconto'])/100))*$res['pervat'])/100);
			$cat[$res['catmer']]['count'] += $res['quanti'];
			if ($res['id_tes'] <> $lastest){
				$cat[$res['catmer']]['traspo'] += $res['traspo'];
				$cat[$res['catmer']]['speban'] += $res['speban'];
				$cat[$res['catmer']]['spevar'] += $res['spevar'];
				$cat[$res['catmer']]['traspovat'] += $res['traspo']*$res['aliquo']/100;
				$cat[$res['catmer']]['spebanvat'] += $res['speban']*$res['aliquo']/100;
				$cat[$res['catmer']]['spevarvat'] += $res['spevar']*$res['aliquo']/100;
			}
		} else {
			$cat[$res['catmer']]['sum'] = ($res['quanti']*$res['prelis'])-((($res['quanti']*$res['prelis'])*$res['sconto'])/100)-((($res['quanti']*$res['prelis'])*$res['rig_sconto'])/100);
			$cat[$res['catmer']]['sumvat'] = (($cat[$res['catmer']]['sum']*$res['pervat'])/100);
			$cat[$res['catmer']]['count'] = $res['quanti'];
			$cat[$res['catmer']]['traspo'] = $res['traspo'];
			$cat[$res['catmer']]['speban'] = $res['speban'];
			$cat[$res['catmer']]['spevar'] = $res['spevar'];
			$cat[$res['catmer']]['traspovat'] = $res['traspo']*$res['aliquo']/100;
			$cat[$res['catmer']]['spebanvat'] = $res['speban']*$res['aliquo']/100;
			$cat[$res['catmer']]['spevarvat'] = $res['spevar']*$res['aliquo']/100;
		}
		$cat[$res['catmer']][] = $res;
		$lastest = $res['id_tes'];
	}
	return $cat;
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array());
$id_con=intval($_GET['id_con']);
$retcat=dailyrep($id_con);

$totivacomp=0;
$totiva=0;
$totimp=0;
?>
<div align="center" class="FacetFormHeaderFont">Riepilogo corrispettivi contabilizzati del movimento n. <?php echo $id_con; ?></div>
<form method="GET" >
<div class="table-responsive">
	<table class="Tlarge table table-bordered table-condensed table-striped">
		<tr>
			<td class="FacetFieldCaptionTD">
				<?php echo "Data"; ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php echo "Quantità"; ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php echo "Categoria";; ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php echo "Prezzo unitario medio imponibile"; ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php echo "Prezzo unitario medio IVA compresa"; ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php echo "Aliquota IVA"; ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php echo "IVA unitaria"; ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php echo "Imponibile"; ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php echo "IVA tot."; ?>
			</td>
		</tr>
		<?php
		$tottraspo=0;
		$tottraspovat=0;
		$totspeban=0;
		$totspebanvat=0;
		$totspevar=0;
		$totspevarvat=0;
		$n=0;$tot_qta=0;
		$rigo_stampa=[];
		$key = array();
		$rigo = array();
		foreach ($retcat as $cat){ // ciclo le righe per categoria raggruppata
			foreach ($cat as $catrow){
				if (isset($catrow['descri_cat']) AND $catrow['catmer']<9999){ // se categoria esistente stampo il rigo
					$rigo_stampa['datemi'][$n]=$catrow['datemi'];$rigo_stampa['count'][$n]=$cat['count'];$rigo_stampa['descri_cat'][$n]=$catrow['descri_cat'];$rigo_stampa['pr_un'][$n]=number_format($cat['sum']/$cat['count'],4,",",".");$rigo_stampa['pr_un_ivato'][$n]=number_format(($cat['sum']+$cat['sumvat'])/$cat['count'],2);$rigo_stampa['pervat'][$n]=$catrow['pervat'];$rigo_stampa['iva_unit'][$n]=gaz_format_number($cat['sumvat']/$cat['count']);$rigo_stampa['impon'][$n]=gaz_format_number($cat['sum']);$rigo_stampa['iva'][$n]=gaz_format_number($cat['sumvat']);
					$n++;$tot_qta += $cat['count'];
					break;
				} elseif (isset($catrow['descri'])){// se è una categoria fittizia
					if(isset($key[$catrow['descri']])){// e se c'è descri Creo una chiave per le spese
						$key[$catrow['descri']][0] += $catrow['prelis'];
						$key[$catrow['descri']]['vat'] += $cat['sumvat'];
					}else{
						$key[$catrow['descri']][0] = $catrow['prelis'];
						$key[$catrow['descri']]['pervat'] = $catrow['pervat'];
						$key[$catrow['descri']]['vat'] = $cat['sumvat'];
					}
				}
			}
			$totivacomp += $cat['sum']+$cat['sumvat'];
			$totimp += $cat['sum'];
			$totiva += $cat['sumvat'];
			$tottraspo += $cat['traspo'];
			$tottraspovat += $cat['traspo']+$cat['traspovat'];
			$totspeban += $cat['speban'];
			$totspebanvat += $cat['speban']+$cat['spebanvat'];
			$totspevar += $cat['spevar'];
			$totspevarvat += $cat['spevar']+$cat['spevarvat'];
		}

		foreach($key as $k => $value){ // stampo i righi delle categorie fittizie
			$rigo_stampa['datemi'][$n]=$catrow['datemi'];$rigo_stampa['count'][$n]=1.00000;$rigo_stampa['descri_cat'][$n]=$k;$rigo_stampa['pr_un'][$n]=gaz_format_number($value[0]);$rigo_stampa['pr_un_ivato'][$n] = number_format(($value[0]+$value['vat']),2);$rigo_stampa['pervat'][$n]=$value['pervat'];$rigo_stampa['iva_unit'][$n]=gaz_format_number($value['vat']);$rigo_stampa['impon'][$n]=gaz_format_number($value[0]);$rigo_stampa['iva'][$n]=gaz_format_number($value['vat']);
			$n++;$tot_qta += 1;
		}
		// se presenti in testata (vecchio sistema) stampo le spese della testata
		if ($tottraspo>0){
			$rigo_stampa['datemi'][$n]=$catrow['datemi'];$rigo_stampa['count'][$n]=1;$rigo_stampa['descri_cat'][$n]=" Spese trasporto";$rigo_stampa['pr_un'][$n]="";$rigo_stampa['pr_un_ivato'][$n]="";$rigo_stampa['pervat'][$n]="";$rigo_stampa['iva_unit'][$n]="";$rigo_stampa['impon'][$n]=gaz_format_number($tottraspo);$rigo_stampa['iva'][$n]=gaz_format_number($tottraspovat);
			$n++;$tot_qta += 1;
			$totimp += $tottraspo;
			$totiva += $tottraspovat;
		}
		if ($totspeban>0){
			$rigo_stampa['datemi'][$n]=$catrow['datemi'];$rigo_stampa['count'][$n]=1;$rigo_stampa['descri_cat'][$n]=" Spese incasso";$rigo_stampa['pr_un'][$n]="";$rigo_stampa['pr_un_ivato'][$n]="";$rigo_stampa['pervat'][$n]="";$rigo_stampa['iva_unit'][$n]="";$rigo_stampa['impon'][$n]=gaz_format_number($totspeban);$rigo_stampa['iva'][$n]=gaz_format_number($totspebanovat);
			$n++;$tot_qta += 1;
			$totimp += $totspeban;
			$totiva += $totspebanvat;
		}
		if ($totspevar>0){
			$rigo_stampa['datemi'][$n]=$catrow['datemi'];$rigo_stampa['count'][$n]=1;$rigo_stampa['descri_cat'][$n]=" Spese varie";$rigo_stampa['pr_un'][$n]="";$rigo_stampa['pr_un_ivato'][$n]="";$rigo_stampa['pervat'][$n]="";$rigo_stampa['iva_unit'][$n]="";$rigo_stampa['impon'][$n]=gaz_format_number($totspevar);$rigo_stampa['iva'][$n]=gaz_format_number($totspevarvat);
			$n++;$tot_qta += 1;
			$totimp += $totspevar;
			$totiva += $totspevarvat;
		}

		// A causa del criterio dell A.d.E. di calcolare iva e importi (vuole partire dal prezzo IVA compresa con due soli decimali) si creano delle discrepanze dovute agli arrotondamenti
		// Devo quindi fare il conteggio secondo il criterio A.d.E. e confrontarlo con quello di GAzie, poi, se serve, bisogna aggiustare
		$totAdE = 0;
		$totAdE_iva = 0;
		foreach ($retcat as $cat){
			foreach ($cat as $catrow){// per ogni rigo categoria
				if (isset($catrow['descri_cat']) AND $catrow['catmer']<9999){ // se categoria esistente conteggio il rigo
					$totAdE_row = (number_format(($cat['sum']+$cat['sumvat'])/$cat['count'],2) * $cat['count']);
					$totAdE += $totAdE_row;
					$catrow['pervat'] = str_replace (".","",substr("0".$catrow['pervat'],-4,4));
					$strvat ="1.".$catrow['pervat'];
					$totAdE_iva += $totAdE_row - ($totAdE_row/floatval($strvat));
					break;
				}
			}
		}
		foreach($key as $k => $value){ // per ogni rigo delle categorie fittizie
			$totAdE_row = number_format(($value[0]+$value['vat']),2);
			$totAdE += $totAdE_row;
			$value['vat'] = str_replace (".","",substr("0".$value['vat'],-4,4));
			$strvat ="1.".$value['vat'];
			$totAdE_iva += $totAdE_row - $totAdE_row/floatval($strvat);
		}
		// se presenti in testata (vecchio sistema) conteggio le spese della testata
		if ($tottraspo>0){
			$totAdE += $tottraspo + $tottraspovat;
			$totAdE_iva += $tottraspovat;
		}
		if ($totspeban>0){
			$totAdE += $totspeban + $totspebanvat;
			$totAdE_iva += $totspebanvat;
		}
		if ($totspevar>0){
			$totAdE += $totspevar + $totspevarvat;
			$totAdE_iva += $totspevarvat;
		}
		$rk=-1;
		if (isset($rigo_stampa['pr_un_ivato']) && $totAdE <> gaz_format_number(($totimp+$totiva))){ // se i totali non coincidono
			$key1="";$key2="";$r=0;$aster="";
			foreach ($rigo_stampa['pr_un_ivato'] as $row){ // trovo il prezzo unitario più alto con la minima quantità
				if ($r == 0){
					$key2=$rigo_stampa['count'][$r]; $key1=$row; $rk=$r;
				} else {
					if ($key2>=$rigo_stampa['count'][$r]){
						if ($key1<$row){
							$key1=$row; $rk=$r;
						}
						$key2=$rigo_stampa['count'][$r];
					}
				}
				$r++;
			}
			$diff =  ($totimp+$totiva) - $totAdE;
			$diff_prod = $diff/$rigo_stampa['count'][$rk]; // divido la differenza per la quantità della categoria selezionata prima
			$rigo_stampa['pr_un_ivato'][$rk] += $diff_prod; // quindi ne modifico il prezzo unitario con la differenza
		}

		for ($x = 0; $x <= $n-1; $x++){	// stampo tutti irighi
			$aster="";
			if ($x == $rk){ // se c'è stato un arrotondamento lo segnalo
				$aster=" *";
			}
			echo "<tr><td>".gaz_format_date($rigo_stampa['datemi'][$x])."</td><td>".gaz_format_number($rigo_stampa['count'][$x])."</td><td>".$rigo_stampa['descri_cat'][$x]."</td><td>".$rigo_stampa['pr_un'][$x]."</td><td>".number_format((($rigo_stampa['pr_un_ivato'][$x])),2,",",".").$aster."</td><td>".$rigo_stampa['pervat'][$x]."</td><td>".$rigo_stampa['iva_unit'][$x]."</td><td>".$rigo_stampa['impon'][$x]."</td><td>".$rigo_stampa['iva'][$x]."</td></tr>";
		}

		// stampo i totali
		echo "<tr></tr><tr class=\"FacetDataTD\"><td class=\"FacetDataTD\"></td><td class=\"FacetDataTD\"></td><td class=\"FacetDataTD\"> Totale giornaliero </td><td class=\"FacetDataTD\"></td><td class=\"FacetDataTD\"></td><td class=\"FacetDataTD\"></td><td class=\"FacetDataTD\"></td><td class=\"FacetDataTD\">". gaz_format_number($totimp) ."</td><td class=\"FacetDataTD\">". gaz_format_number($totiva) ."</td></tr>";
		echo "<tr class=\"FacetDataTD\"><td class=\"FacetDataTD\"></td><td class=\"FacetDataTD\"></td><td class=\"FacetDataTD\"> Totale giornaliero </td><td class=\"FacetDataTD\"></td><td class=\"FacetDataTD\"></td><td class=\"FacetDataTD\"></td><td class=\"FacetDataTD\"> IVA compresa </td><td class=\"FacetDataTD\"><b>". gaz_format_number(($totimp+$totiva)) ."</b></td></tr>";

		?>
		</tr>
	</table>
	<?php

	if ($rk !== -1){
		echo " * = apportato arrotondamento";
	}
	?>
</div>
</form>
<?php

?>
