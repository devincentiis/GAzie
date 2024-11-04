<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-present - Antonio Germani, Massignano (AP)
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
// >> Selezione date per la generazione del file di upload per il SIAN <<

require("../../library/include/datlib.inc.php");
require ("../../modules/magazz/lib.function.php");
require ("../../modules/vendit/lib.function.php");
$admin_aziend=checkAdmin();
$msg='';
$silos = new silos;

// controllo che ci sia la cartella sian
$sianfolder = DATA_DIR.'files/' . $admin_aziend['codice'] . '/sian/';
if (!file_exists($sianfolder)) {// se non c'è la creo
    mkdir($sianfolder, 0777);
}

// prendo tutti i file della cartella sian e li leggo
if ($handle = opendir(DATA_DIR.'files/' . $admin_aziend['codice'] . '/sian/')){
	$i=0;
	while (false !== ($file = readdir($handle))){
		if (substr($file,-12) == "OPERREGI.txt"){
			if ($file=="." OR $file==".."){ continue;}
				$prevfiles[$i]['nome']=$file; // prendo nome file
				$prevfiles[$i]['content']=@file_get_contents(DATA_DIR.'files/' . $admin_aziend['codice'] . '/sian/'.$file);// prendo contenuto file
				$i++;
		}
	}
	closedir($handle);
	if (isset($prevfiles)){ // se ci sono file
		rsort($prevfiles);// ordino per nome file
	}
}

// vedo se l'ultimo file è di tipo 'I'nserimento o 'C'ancellazione
if (isset($prevfiles)){ // se ci sono files
	for ($n=0 ; $n <= $i-1 ; $n++){
		if (substr($prevfiles[$n]['content'],875,1)=="I"){ // se il file è di inserimento ne prendo la data dell'ultimo record
			$fileField=explode (";",$prevfiles[$n]['content']);
			$uldtfile=$fileField[((((count($fileField)-1)/49)-1)*49)+3];			
			$uldtfile=str_replace("-", "", $uldtfile); // imposto la data per la selezione
			break; // esco dal ciclo
		} else { // se non è 'I', cioè è 'C', faccio saltare il file successivo perché annullato da questo
			$n++;
		}
	}
}
if (!isset($uldtfile)) { // se non c'è la data, la imposto al primo gennaio dell'anno corrente
	$uldtfile="01"."01".date("Y");
}

function getMovements($date_ini,$date_fin)
    {
        global $gTables,$admin_aziend;
        $m=array();
        $where="datdoc BETWEEN $date_ini AND $date_fin AND ".$gTables['camp_mov_sian'].".id_movmag > 0";
        $what=$gTables['movmag'].".*, ".
              $gTables['camp_mov_sian'].".*, ".
			  $gTables['artico'].".SIAN, ".
			  $gTables['anagra'].".ragso1, ".$gTables['anagra'].".id_SIAN, ".
			  $gTables['clfoco'].".id_anagra, ".
			  $gTables['camp_recip_stocc'].".capacita, "." camp_recip_stocc_destin.capacita as capacita_destin, ".
			  $gTables['camp_artico'].".or_macro, ".$gTables['camp_artico'].".or_spec, ".$gTables['camp_artico'].".estrazione, ".$gTables['camp_artico'].".biologico, ".$gTables['camp_artico'].".etichetta, ".$gTables['camp_artico'].".categoria ";
        $table=$gTables['movmag']." LEFT JOIN ".$gTables['camp_mov_sian']." ON (".$gTables['movmag'].".id_mov = ".$gTables['camp_mov_sian'].".id_movmag)
               LEFT JOIN ".$gTables['clfoco']." ON (".$gTables['movmag'].".clfoco = ".$gTables['clfoco'].".codice)
			   LEFT JOIN ".$gTables['camp_artico']." ON (".$gTables['movmag'].".artico = ".$gTables['camp_artico'].".codice)
               LEFT JOIN ".$gTables['artico']." ON (".$gTables['movmag'].".artico = ".$gTables['artico'].".codice)
			   LEFT JOIN ".$gTables['camp_recip_stocc']." ON (".$gTables['camp_recip_stocc'].".cod_silos = ".$gTables['camp_mov_sian'].".recip_stocc)
			   LEFT JOIN ".$gTables['camp_recip_stocc']." as camp_recip_stocc_destin ON (camp_recip_stocc_destin.cod_silos = ".$gTables['camp_mov_sian'].".recip_stocc_destin)
			   LEFT JOIN ".$gTables['anagra']." ON (".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra)";
        $rs=gaz_dbi_dyn_query ($what,$table,$where, 'datreg ASC, id_mov ASC, clfoco ASC, operat DESC,tipdoc ASC ');
        while ($r = gaz_dbi_fetch_array($rs)) {
            $m[] = $r;
        }
        return $m;
    }

// controllo contenitori-silos
$init_mov=substr($uldtfile,4,4)."-".substr($uldtfile,2,2)."-".substr($uldtfile,0,2);
$dateinit=date_create($init_mov);
date_sub($dateinit,date_interval_create_from_date_string("2 days"));
$init_mov= date_format($dateinit,"Y-m-d");
	$orderby=2;
	$limit=0;
	$passo=2000000;
	$where="";
	$what=	$gTables['camp_recip_stocc'].".cod_silos, ".
			$gTables['camp_recip_stocc'].".capacita";
	$groupby= "";
	$table=$gTables['camp_recip_stocc'];
	$ressilos=gaz_dbi_dyn_query ($what,$table,$where,$orderby,$limit,$passo,$groupby);
	while ($r = gaz_dbi_fetch_array($ressilos)) { // controllo sul totale iniziale dei silos
		$totalcont = $silos->getCont($r['cod_silos']);
				
		$totcont[$r['cod_silos']]=$silos->getCont($r['cod_silos'],"", 0, $init_mov);
		$maxcont[$r['cod_silos']]=$r['capacita'];
		
		if ($totalcont<0){
			$message = "Giacenza negativa nel silos ".$r['cod_silos']." !";
			$msg .='5+';
		}
		if ($totalcont>$r['capacita']){
			$message = "Il contenuto del silos è ".$r['cod_silos']." e supera la sua capacità dichiarata !";
			$msg .='5+';
		}
	}

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['this_date_Y']=date("Y");
    $form['this_date_M']=date("m");
    $form['this_date_D']=date("d");
    $form['date_ini_D']=substr($uldtfile,0,2); // imposto la data di inizio partendo da quella dell'ultimo file
    $form['date_ini_M']=substr($uldtfile,2,2);
    $form['date_ini_Y']=substr($uldtfile,4,4);
    $form['date_fin_D']=date('d', strtotime('-1 day', strtotime(date("Y-m-d"))));
    $form['date_fin_M']=date('m', strtotime('-1 day', strtotime(date("Y-m-d"))));
    $form['date_fin_Y']=date('Y', strtotime('-1 day', strtotime(date("Y-m-d"))));
} else { // accessi successivi
    $form['hidden_req']=htmlentities($_POST['hidden_req']);
    $form['ritorno']=$_POST['ritorno'];
	/*
    $form['date_ini_D']=substr($uldtfile,0,2); // impongo la data di inizio partendo da quella dell'ultimo file
    $form['date_ini_M']=substr($uldtfile,2,2);
    $form['date_ini_Y']=substr($uldtfile,4,4);
	*/
	$form['date_ini_D']=intval($_POST['date_ini_D']);
    $form['date_ini_M']=intval($_POST['date_ini_M']);
    $form['date_ini_Y']=intval($_POST['date_ini_Y']);
	
    $form['date_fin_D']=intval($_POST['date_fin_D']);
    $form['date_fin_M']=intval($_POST['date_fin_M']);
    $form['date_fin_Y']=intval($_POST['date_fin_Y']);
    $form['this_date_Y']=intval($_POST['this_date_Y']);
    $form['this_date_M']=intval($_POST['this_date_M']);
    $form['this_date_D']=intval($_POST['this_date_D']);
    if (isset($_POST['return'])) {
        header("Location: ".$form['ritorno']);
        exit;
    }
}
$date_ini =  sprintf("%04d%02d%02d",$form['date_ini_Y'],$form['date_ini_M'],$form['date_ini_D']);
$date_fin =  sprintf("%04d%02d%02d",$form['date_fin_Y'],$form['date_fin_M'],$form['date_fin_D']);

//controllo le date
if (!checkdate( $form['this_date_M'],$form['this_date_D'],$form['this_date_Y']) ||
    !checkdate( $form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']) ||
    !checkdate( $form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y'])) {
    $msg .='0+';
}
$utsexe= mktime(0,0,0,$form['this_date_M'],$form['this_date_D'],$form['this_date_Y']);
$utsini= mktime(0,0,0,$form['date_ini_M'],$form['date_ini_D'],$form['date_ini_Y']);
$utsfin= mktime(0,0,0,$form['date_fin_M'],$form['date_fin_D'],$form['date_fin_Y']);

if ($utsini > $utsfin) {
    $msg .='1+';
}
if ($utsexe < $utsfin) {
    $msg .='2+';
}
if ($utsfin>strtotime('-1 day', strtotime(date("Y-m-d")))) {
    $msg .='4+';
}

// fine controlli

if (isset($_POST['create']) && $msg=='') {
	
	// per creare devo obbligatoriamente impostare la data di inizio partendo da quella dell'ultimo file creato
	$form['date_ini_D']=substr($uldtfile,0,2); 
    $form['date_ini_M']=substr($uldtfile,2,2);
    $form['date_ini_Y']=substr($uldtfile,4,4);	
	$utsini= mktime(0,0,0,$form['date_ini_M'],$form['date_ini_D'],$form['date_ini_Y']);

    $utsini=date("dmY",$utsini);
    $utsfin=date("dmY",$utsfin);
    $utsexe=date("dmY",$utsexe);
	$uldtfile=$form['date_ini_Y'].$form['date_ini_M'].$form['date_ini_D'];

    header("Location: create_sian.php?ri=$utsini&rf=$utsfin&ds=$utsexe&ud=$uldtfile");
    exit;
}


require("../../library/include/header.php");
$script_transl=HeadMain(0,array('calendarpopup/CalendarPopup'));
echo "<script type=\"text/javascript\">
var cal = new CalendarPopup();
var calName = '';
function setMultipleValues(y,m,d) {
     document.getElementById(calName+'_Y').value=y;
     document.getElementById(calName+'_M').selectedIndex=m*1-1;
     document.getElementById(calName+'_D').selectedIndex=d*1-1;
}
function setDate(name) {
  calName = name.toString();
  var year = document.getElementById(calName+'_Y').value.toString();
  var month = document.getElementById(calName+'_M').value.toString();
  var day = document.getElementById(calName+'_D').value.toString();
  var mdy = month+'/'+day+'/'+year;
  cal.setReturnFunction('setMultipleValues');
  cal.showCalendar('anchor', mdy);
}
</script>
";
?>
<div class="panel panel-default gaz-table-form">
    <div class="container-fluid">
		<div align="center"><b>Disposizione per la tenuta del registro SIAN</b>
		<p align="justify">
		Tutti coloro che producono e/o detengono olio sfuso a fini commerciali, sono obbligati a comunicare all’AGEA le quantità di produzione e vendita olio attraverso l’inserimento dei dati nel registro telematico SIAN.
		Sono esentati coloro che producono olio destinato all’autoconsumo, ossia consumato dal titolare e/o familiari del medesimo, sino al 3° grado, e fino ad un massimo 3,5 quintali di olio.
		Le annotazioni nei registri e quindi l'invio del file di upload, si effettuano entro e non oltre il sesto giorno successivo a quello dell’operazione, giorni festivi compresi.
		Gli olivicoltori che detengono e commercializzano esclusivamente olio, allo stato sfuso e/o confezionato, ottenuto da olive provenienti dalla propria azienda, molite
		presso il frantoio proprio o di terzi, possono effettuare entro il 10 di ogni mese le annotazioni dei dati relativi alle operazioni del mese precedente, a condizione
		che l’olio ottenuto dalla molitura non sia superiore ai 700 chilogrammi per campagna di commercializzazione (dal 1 luglio al 30 giugno dell'anno successivo). Decreti MiPAAF n° 8077/2009 e n° 16059/2013.
		</p></div>
	</div>
</div>
<?php
echo "<form method=\"POST\" name=\"select\">\n";
echo "<input type=\"hidden\" value=\"".$form['hidden_req']."\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\" />\n";
$gForm = new magazzForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tsmall\" align=\"center\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="2" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
	if (!empty($message)){
		echo "<script type='text/javascript'>alert('$message');</script>";
	}
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date']."</td><td  class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('this_date',$form['this_date_D'],$form['this_date_M'],$form['this_date_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">".$script_transl['date_ini']."</td><td  class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_ini',$form['date_ini_D'],$form['date_ini_M'],$form['date_ini_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['date_fin']."</td><td  class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('date_fin',$form['date_fin_D'],$form['date_fin_M'],$form['date_fin_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
echo "<td align=\"left\"><input type=\"submit\" name=\"return\" value=\"".$script_transl['return']."\">\n";
echo '<td align="right"> <input type="submit" accesskey="i" name="preview" value="';
echo $script_transl['view'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";


if (isset($_POST['preview']) and $msg=='') {
	$m=getMovements($date_ini,$date_fin);
	echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
	if (sizeof($m) > 0) {
        $ctr_mv='';
        echo "<tr>";
        $linkHeaders=new linkHeaders($script_transl['header']);
        $linkHeaders->output();
        echo "</tr>";
		$genera="";		
		$nr=0;
        foreach($m as $key => $mv){
			$er="";
			if ($mv['id_movmag']>0){ // se è un movimento del SIAN connesso al movimento di magazzino
			
			$legenda_cod_op= array('1'=>'Confezionamento con etichettatura','2'=>'Confezionamento senza etichettatura','3'=>'Etichettatura','4'=>'Svuotamento di olio confezionato','5'=>'Movimentazione interna senza cambio di origine','S7'=>'Scarico di olio destinato ad altri usi','10'=>'Carico olio lampante da recupero','8'=>'Reso olio confezionato da clienti','9'=>'Olio ha ottenuto certificazione DOP');
				if ($form['date_ini_Y'].$form['date_ini_M'].$form['date_ini_D']==str_replace("-", "", $mv['datdoc']) AND strlen($mv['status'])>1) {
				// escludo i movimenti già inseriti null'ultimo file con stessa data
				} else if ($mv['id_orderman']>0 AND $mv['operat']==-1 AND $mv['cod_operazione']<>"S7"){
					// escludo i movimenti di produzione in uscita
						$totcont[$mv['recip_stocc']] -= $mv['quanti'];
						//echo "<br>PRODUZIONE SCarico fusto ",$mv['recip_stocc']," di:",$mv['quanti'];
						if ($totcont[$mv['recip_stocc']]<0){
							//echo $mv['desdoc'],"ERRORE <",$nr;
							$message = "Al rigo ".$nr." la giacenza del silos ".$mv['recip_stocc']." è negativa";
							$msg .='5+';$er="style='background-color: red';";
						}
						$totcont[$mv['recip_stocc_destin']] += $mv['quanti'];
						//echo "<br>PRODUZIONE carico fusto ",$mv['recip_stocc_destin']," di:",$mv['quanti'];
						if ($totcont[$mv['recip_stocc_destin']]>$maxcont[$mv['recip_stocc_destin']]){
							//echo "<br>",$mv['desdoc'],"ERRORE >",$nr," totcont:",$totcont[$mv['recip_stocc_destin']]," - maxcont:",$maxcont[$mv['recip_stocc_destin']];
							$message = "Al rigo ".$nr." la quantità del silos ".$mv['recip_stocc_destin']." è ".$totcont[$mv['recip_stocc_destin']]." e supera la sua capacità dichiarata di ".$maxcont[$mv['recip_stocc_destin']];
							$msg .='5+';$er="style='background-color: red';";
						}
				} else {	
					$nr++;
					if ($mv['id_orderman']==0 AND $mv['operat']==1){
						$legenda_cod_op['3']='Carico olio da lavorazione/deposito presso terzi';
						$legenda_cod_op['5']='Carico olio da altro stabilimento/deposito stessa impresa';
						$totcont[$mv['recip_stocc']] += $mv['quanti'];
						//echo "<br>carico fusto ",$mv['recip_stocc']," di:",$mv['quanti'];
						if ($totcont[$mv['recip_stocc']]>$maxcont[$mv['recip_stocc']]){
							//echo "<br>",$mv['desdoc'],"ERRORE >",$nr," totcont:",$totcont[$mv['recip_stocc']]," - maxcont:",$maxcont[$mv['recip_stocc']];
							$message = "Al rigo ".$nr." la quantità del silos ".$mv['recip_stocc']." è ".$totcont[$mv['recip_stocc']]." e supera la sua capacità dichiarata di ".$maxcont[$mv['recip_stocc']];
							$msg .='5+';$er="style='background-color: red';";
						}			
					}
					if ($mv['id_orderman']==0 AND $mv['operat']==-1){
						$legenda_cod_op['0']='Vendita olio a consumatore finale';
						$legenda_cod_op['1']='Vendita/cessione olio a ditta italiana';
						$legenda_cod_op['2']='Vendita/cessione olio a ditta comunitaria';
						$legenda_cod_op['3']='Vendita/cessione olio a ditta extracomunitaria';
						$legenda_cod_op['4']='Scarico olio trasferimento altro stabilimento/deposito';
						$legenda_cod_op['6']='Omaggio olio confezionato';
						$legenda_cod_op['8']='Scarico olio autoconsumo';
						$legenda_cod_op['12']='Perdite, cali, campionamento, analisi';
						$legenda_cod_op['13']='Separazione morchie';
						$totcont[$mv['recip_stocc_destin']] -= $mv['quanti'];
						//echo "<br>SCarico fusto ",$mv['recip_stocc_destin']," di:",$mv['quanti'];
						if ($totcont[$mv['recip_stocc_destin']]<0){
							//echo $mv['desdoc'],"ERRORE <",$nr;
							$message = "Al rigo ".$nr." la giacenza del silos ".$mv['recip_stocc_destin']." è negativa";
							$msg .='5+';$er="style='background-color: red';";
						}
					}
					$genera="ok";
					$datedoc = substr($mv['datdoc'],8,2).'-'.substr($mv['datdoc'],5,2).'-'.substr($mv['datdoc'],0,4);
           			$movQuanti = $mv['quanti']*$mv['operat'];
					$style="";
					if (strtotime(substr($uldtfile,0,2)."-".substr($uldtfile,2,2)."-".substr($uldtfile,4,4))>=strtotime($datedoc)){
						$style="style='background-color: #fbd3d3';";
					}
					$style=($er=="")?$style:$er;
					echo "<tr ",$style,"><td class=\"FacetDataTD\">".$nr."-  ".$datedoc." &nbsp;</td>";
					echo "<td class=\"FacetDataTD\" align=\"center\">".$mv['artico']." &nbsp;</td>\n";
					echo "<td class=\"FacetDataTD\" align=\"center\">".gaz_format_quantity($movQuanti,1,3)."</td>\n";
					echo "<td class=\"FacetDataTD\" align=\"center\">".$mv['id_SIAN']." - ".$mv['ragso1']." &nbsp;</td>\n";
					if ($mv['capacita_destin']>0){
						echo "<td class=\"FacetDataTD\" align=\"center\">".$mv['recip_stocc_destin']." - cap. Kg ".$mv['capacita_destin']." &nbsp;</td>\n";
					}else{
						echo "<td class=\"FacetDataTD\" align=\"center\"></td>\n";	
					}
					if ($mv['capacita']>0){
						echo "<td class=\"FacetDataTD\" align=\"center\">".$mv['recip_stocc']." - cap. Kg ".$mv['capacita']." &nbsp;</td>\n";
					}else{
						echo "<td class=\"FacetDataTD\" align=\"center\"></td>\n";	
					}

					echo "<td class=\"FacetDataTD\" align=\"center\">".$mv['desdoc']." &nbsp;</td>\n";
					echo "<td class=\"FacetDataTD\" align=\"center\">".$legenda_cod_op[$mv['cod_operazione']]." &nbsp;</td>\n";
					echo "<td class=\"FacetDataTD\" align=\"center\">".$mv['varieta']." &nbsp;</td>\n";
					echo "</tr>\n";
					$ctr_mv = $mv['artico'];
				}
				
				
			}
         }
         echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
		 if (!empty($msg)) {
			echo '<td colspan="2" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
			if (!empty($message)){
				echo "<script type='text/javascript'>alert('$message');</script>";
			}
		}elseif ($genera=="ok"){
			echo '<td colspan="7" align="right"><input type="submit" name="create" value="';
			echo "Genera file SIAN";
			echo '">';
			echo "\t </td>\n";
		 }
         echo "\t </tr>\n";
	}
  echo "</table></form>";
}
?>
<a href="https://programmisitiweb.lacasettabio.it/quaderno-di-campagna/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:20%; z-index:2000;"> Registro di campagna è un modulo di Antonio Germani</a>
<?php
require("../../library/include/footer.php");
?>
