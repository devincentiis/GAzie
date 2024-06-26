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
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();

if ( isset($_GET["Stampa"]) ) {
	$esc="";
	if (isset ($_GET['escludi'])){
		$esc="?esc=escludi";
	}
    header("Location: ../../modules/magazz/stampa_situaz.php".$esc);
}

require("../../library/include/header.php");
$script_transl = Headmain();
$passo = 1000;

gaz_flt_var_assign('descri', 'v');

if (isset($_GET['all'])) {
    $auxil = "&all=yes";
    $passo = 100000;
}
?>
<div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['title']; ?> </div>
<?php
$recordnav = new recordnav($gTables['artico'], $where, $limit, $passo);
$recordnav->output();

$show_artico_composit = gaz_dbi_get_row($gTables['company_config'], 'var', 'show_artico_composit');
?>
<form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
        <tbody>
        <tr>
            <td class="FacetFieldCaptionTD">
                &nbsp;
            </td>
            <td class="FacetFieldCaptionTD">
                <?php gaz_flt_disp_int("descri", "Articolo"); //gaz_flt_disp_select ( "clfoco", $gTables['anagra'].".ragso1", $gTables['clfoco'].' LEFT JOIN '.$gTables['anagra'].' ON '.$gTables['clfoco'].'.id_anagra = '.$gTables['anagra'].'.id', $all, $orderby, "ragso1");  ?>
			</td>
            <td class="FacetFieldCaptionTD" colspan="3">
			    <input type="submit" class="btn btn-xs btn-default" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value = 1;">
			</td>
            <td class="FacetFieldCaptionTD">
                <input type="submit" class="btn btn-xs btn-default" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value = 1;">
			</td>
            <td class="FacetFieldCaptionTD">
				<input type='submit' class='btn btn-default btn-xs' name='Stampa' value='&nbsp;Stampa&nbsp;' />
				<input type='checkbox' name='escludi' value='escludi'/> Escludi esauriti
			</td>
            </tr>
            <?php
            // creo l'array (header => campi) per l'ordinamento dei record
            $headers_artico = array("Codice" => "codice",
                "Descrizione" => "descri",
                "UmV" => "",
                "Pezzi in stock" => "",
                "Ordinato Cliente" => "",
                "Ordinato Fornitore" => "",
                "Totale" => "" );

            $linkHeaders = new linkHeaders($headers_artico);
            $gForm = new magazzForm();
			$show_artico_composit = gaz_dbi_get_row($gTables['company_config'], 'var', 'show_artico_composit');
			$tipo_composti = gaz_dbi_get_row($gTables['company_config'], 'var', 'tipo_composti');
            // Antonio Germani -  se siamo in composti STD si prendono anche gli articoli composti
            if ( $tipo_composti['val']=="STD") {
                $result = gaz_dbi_dyn_query("*", $gTables['artico'], "good_or_service != 1 and ". $where, $orderby, $limit, $passo);               
            } else { // se siamo in composti KIT si prendono solo gli articoli normali
                $result = gaz_dbi_dyn_query("*", $gTables['artico'], "good_or_service=0 and ". $where, $orderby, $limit, $passo);
            }
            echo '<tr>'. $linkHeaders->output() .'</tr>';
            while ($r = gaz_dbi_fetch_array($result)) {
                $totale = 0;
                $ordinatif = $gForm->get_magazz_ordinati($r['codice'], "AOR");
                $ordinatic = $gForm->get_magazz_ordinati($r['codice'], "VOR");
				$ordinatic = $ordinatic + $gForm->get_magazz_ordinati($r['codice'], "VOW");
				
                $mv = $gForm->getStockValue(false, $r['codice']);
                $magval = array_pop($mv);
                $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
				if (isset ($magval['q_g']) && round($magval['q_g'],6) == "-0"){ // Antonio Germani - se si crea erroneamente un numero esponenziale negativo forzo la quantità a zero
					$magval['q_g']=0;
				}
                $totale = $magval['q_g']-$ordinatic+$ordinatif;
				$bclass='success';
				$rclass='';
				if ($totale<=0.1){
					$bclass='warning';
					$rclass='warning';
				}elseif($magval['q_g']<=0){
					$bclass='danger';
					$rclass='danger';
				}
                echo '<tr class="'.$rclass.'">
	   			    <td width="5%"><a class="btn btn-xs btn-'.$bclass.' btn-block" href="admin_artico.php?codice=' . $r["codice"] . '&amp;Update">
				    <i class="glyphicon glyphicon-edit"></i>&nbsp;' . $r["codice"] . '</a></td>';
                echo '	<td width="30%">
	   				<span class="gazie-tooltip" data-type="product-thumb" data-id="' . $r["codice"] . '" data-title="' . $r['annota'] . '">' . $r["descri"] . '</span>
                    </td><td align="center" title="">'.$r['unimis'].'</td>';
                echo '<td align="right">' . gaz_format_quantity($magval['q_g'],1,3) . ' </td>
                    <td align="center">' . gaz_format_quantity($ordinatic,1,3) . ' </td>
                    <td align="center">' . gaz_format_quantity($ordinatif,1,3) . ' </td>
                    <td align="right">'. gaz_format_quantity($totale,1,3).'</td></tr>';
            }
            echo '<tr><td class="FacetFieldCaptionTD" colspan="10" align="right">&nbsp;</td></tr>';
            ?>
        </tbody>
    </table>
</form>
<?php
require("../../library/include/footer.php");
?>