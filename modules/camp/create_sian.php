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
// >> Creazione del file .txt di upload per il SIAN <<

require("../../library/include/datlib.inc.php");
require ("../../modules/magazz/lib.function.php");
if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
    gaz_set_time_limit (0);
}

$admin_aziend=checkAdmin();
$id_sian = gaz_dbi_get_row($gTables['company_config'], 'var', 'id_sian');

if ($handle = opendir(DATA_DIR.'files/'.$admin_aziend['codice'].'/sian/')){
   while (false !== ($file = readdir($handle))){
       $prevfiles[]=$file;
   }
   closedir($handle);
}

if (!isset ($id_sian) or intval($id_sian['val']==0)){
echo "errore manca id sian. Per utilizzare questa gestione file SIAN è necessario inserire il proprio codice identificativo in configurazione azienda";
die;}

function getMovements($date_ini,$date_fin)
    {
        global $gTables,$admin_aziend;
        $m=array();
        $where="datdoc BETWEEN $date_ini AND $date_fin";
        $what=$gTables['movmag'].".*, ".
              $gTables['camp_mov_sian'].".*, ".
			  $gTables['artico'].".SIAN, ".
			  $gTables['anagra'].".ragso1, ".$gTables['anagra'].".id_SIAN, ".
			  $gTables['clfoco'].".id_anagra, ".
			  $gTables['rigdoc'].".id_tes, ".
			  $gTables['tesdoc'].".numdoc, ".
			  $gTables['lotmag'].".identifier, ".
			  $gTables['camp_artico'].".* ";
        $table=$gTables['movmag']." LEFT JOIN ".$gTables['camp_mov_sian']." ON (".$gTables['movmag'].".id_mov = ".$gTables['camp_mov_sian'].".id_movmag)
               LEFT JOIN ".$gTables['clfoco']." ON (".$gTables['movmag'].".clfoco = ".$gTables['clfoco'].".codice)
			   LEFT JOIN ".$gTables['camp_artico']." ON (".$gTables['movmag'].".artico = ".$gTables['camp_artico'].".codice)
               LEFT JOIN ".$gTables['artico']." ON (".$gTables['movmag'].".artico = ".$gTables['artico'].".codice)
			   LEFT JOIN ".$gTables['rigdoc']." ON (".$gTables['movmag'].".id_rif = ".$gTables['rigdoc'].".id_rig)
			   LEFT JOIN ".$gTables['tesdoc']." ON (".$gTables['rigdoc'].".id_tes = ".$gTables['tesdoc'].".id_tes)
			   LEFT JOIN ".$gTables['lotmag']." ON (".$gTables['lotmag'].".id = ".$gTables['movmag'].".id_lotmag)
			   LEFT JOIN ".$gTables['anagra']." ON (".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra)";
        $rs=gaz_dbi_dyn_query ($what,$table,$where, 'datreg ASC, id_mov ASC, clfoco ASC, operat DESC, tipdoc ASC');
        while ($r = gaz_dbi_fetch_array($rs)) {
            $m[] = $r;
        }
        return $m;
    }

$type_array=array();
// $type_zero è la stringa formattata SIAN vuota *** NON TOCCARE MAI!!! ***
$type_zero="                ;0000000000;0000000000;        ;          ;        ;          ;0000000000;0000000000;0000000000000;0000000000000;          ;          ;0000000000;00;00;00;                                                                                ;00;                                                                                ;0000000000000;0000000000000;0000000000000;0000000000000;0000000000000;0000000000000;0000000000000;                    ;                                                                                                                                                                                                                                                                                                            ; ; ; ; ; ; ; ; ; ; ; ;                 ;                 ;0000;          ;          ;             ;        ;          ; ;";
// $type_zero è la stringa formattata SIAN vuota *** NON TOCCARE MAI!!! ***

$giori = substr($_GET['ri'],0,2);
$mesri = substr($_GET['ri'],2,2);
$annri = substr($_GET['ri'],4,4);
$utsri= mktime(0,0,0,$mesri,$giori,$annri);
$giorf = substr($_GET['rf'],0,2);
$mesrf = substr($_GET['rf'],2,2);
$annrf = substr($_GET['rf'],4,4);
$utsrf= mktime(0,0,0,$mesrf,$giorf,$annrf);

$giosta = substr($_GET['ds'],0,2);
$messta = substr($_GET['ds'],2,2);
$annsta = substr($_GET['ds'],4,4);

$progr=0;
$datsta=$annsta.$messta.$giosta;
$datrf=$annrf.$mesrf.$giorf;

foreach ($prevfiles as $files){ // se nella stessa giornata sono stati creati altri file SIAN aumento il progressivo
	$f=explode("_",$files);
	if (isset($f[1])){
		if ($f[1]==$datsta){
			if($f[2]>$progr){
				$progr=$f[2];
			}
		}
	}
}
$progr++;


if (!isset($_POST['ritorno'])){// Antonio Germani - se non è stata ricaricata la pagina creo il nome del file
	$namefile=$admin_aziend['codfis']."_".$datsta."_".sprintf ("%05d",$progr)."_OPERREGI.txt";
} else { // altrimenti riprendo il nome file già creato
	$namefile=$_POST['namefile'];
}
$ritorno="file creato";
$gazTimeFormatter->setPattern('yyyyMMdd');
$result=getMovements($gazTimeFormatter->format(new DateTime('@'.$utsri)),$gazTimeFormatter->format(new DateTime('@'.$utsrf)));

if (sizeof($result) > 0 AND !isset($_POST['ritorno'])) { // se ci sono movimenti e la pagina non è stata ricaricata creo il file
	$myfile = fopen(DATA_DIR."files/".$admin_aziend['codice']."/sian/".$namefile, "w") or die("Unable to open file!");
	$nprog=1;$lastdatdoc="";$nprog_preced_file=0;
	foreach ($result as $key => $row) {
		$type_array= explode (";", $type_zero); // azzero il type array per ogni movimento da creare
		$note="";
		if ($row['SIAN']>0) {
			if ( $_GET['ud']==str_replace("-", "", $row['datdoc']) AND strlen ($row['status']) > 1) {
				// escludo i movimenti già inseriti null'ultimo file con stessa data
				$nprog_preced_file++; // segno il progressivo per la stessa data del precedente file
			} else {
					if (intval($row['id_orderman'])>0 AND $row['operat']==-1 AND $row['cod_operazione']<>"S7"){ // se è uno scarico di produzione
						continue; // escludo il movimento dal ciclo di creazione file perché le uscite di produzione di olio vengono lavorate insieme alle entrate
					}
					if ($lastdatdoc==$row['datdoc']){ // se il movimento ha la stessa data del precedente aumento il progressivo
						$nprog++;
					} else {
						if (intval($nprog_preced_file)>0 AND $_GET['ud']==str_replace("-", "", $row['datdoc'])){ // se ho un numero progressivo del file precedente e sono nella stessa data
							$nprog=$nprog_preced_file+1;// proseguo la numerazione del file precedente stessa data
							$nprog_preced_file=0;
						} else { // altrimenti ricomincio da 1
							$nprog=1;
						}
					}
					if (isset($row['datdoc'])) { //se c'è la data documento, imposto la data operazione come GGMMAAA
						$gio = substr($row['datdoc'],8,2);
						$mes = substr($row['datdoc'],5,2);
						$ann = substr($row['datdoc'],0,4);
						$dd=$gio.$mes.$ann;// data operazione
						$datdoc=$gio.$mes.$ann;// data documento nel formato GGMMAAA
					} else { // altrimenti la data operazione è quella di registrazione movimento
						$gio = substr($row['datreg'],8,2);
						$mes = substr($row['datreg'],5,2);
						$ann = substr($row['datreg'],0,4);
						$dd=$gio.$mes.$ann;
					}

				// >> Antonio Germani - caso produzione da orderman

					if (intval($row['id_orderman'])>0 AND $row['operat']==1){ // se è una produzione e il movimento è di entrata
						// cerco il movimento/i di scarico connesso/i
						unset($rs);
						$rs=gaz_dbi_dyn_query ("*",$gTables['camp_mov_sian'],"id_mov_sian_rif = '".$row['id_mov_sian']."'");
						$row4['quanti']=0;
						$row5=gaz_dbi_get_row($gTables['camp_artico'], 'codice', $row['artico']);
						foreach ($rs as $mov_sian){
							$rowmag=gaz_dbi_get_row($gTables['movmag'], 'id_mov', $mov_sian['id_movmag']);
							$row4['quanti']=$row4['quanti']+$rowmag['quanti'];
							$row['varieta']=$mov_sian['varieta'];
						}
						$row4['quanti'] = sprintf ("%013d", str_replace(".", "", number_format ($row4['quanti'],3))); // tolgo il separatore decimali perché il SIAN non lo vuole. le ultime tre cifre sono sempre decimali. Aggiungo zeri iniziali.
						$quantilitri=number_format($row['quanti']*$row5['confezione'],3);// trasformo le confezioni in litri
						$quantilitri = str_replace(".", "", $quantilitri); // tolgo il separatore decimali perché il SIAN non lo vuole. le ultime tre cifre sono sempre decimali. Aggiungo zeri iniziali.

						if ($row5['estrazione']==1){
							$type_array[31]="X"; // Flag prima spremitura a freddo a fine operazione
							$type_array[30]="X"; // Flag prima spremitura a freddo
						}
						if ($row5['estrazione']==2){
							$type_array[33]="X"; // Flag estratto a freddo a fine operazione
							$type_array[32]="X"; // Flag estratto a freddo
						}
						if ($row5['biologico']==1){ // se è biologico a fine operazione deve esserlo anche prima
							$type_array[35]="X"; // Flag biologico a fine operazione
							$type_array[34]="X"; // Flag biologico
						}
						if ($row5['biologico']==2){ // se è in conversione a fine operazione deve esserlo anche prima
							$type_array[37]="X"; // Flag in conversione a fine operazione
							$type_array[36]="X"; // Flag in conversione
						}
						$datdoc=""; // i tipi operazione L non vogliono la data del documento giustificativo
						$row['confezione']=""; // il campo capacità confezione, pur essendo previsto fra i campi facoltativi, viene rifiutato nei tipi operazione L
						if ($row['cod_operazione']==1){// Confezionamento con etichettatura
							$row['numdoc']="";
							$type_array[6]=str_pad("L", 10); // codice operazione
							$type_array[23]=sprintf ("%013d",$row4['quanti']); // quantità scarico olio sfuso
							$type_array[24]=sprintf ("%013d",$quantilitri); // quantità carico olio confezionato in litri
							$type_array[18]=sprintf ("%02d",$row5['or_macro']); // Codice Origine olio per macro area a fine operazione
							$type_array[19]=str_pad($row5['or_spec'], 80); // Descrizione Origine olio specifica a fine operazione
							$type_array[27]=str_pad(substr($row['identifier'], 0, 20 ), 20); // Lotto di appartenenza
							$type_array[16]=sprintf ("%02d",$row['or_macro']); // Codice Origine olio per macro area
							if (strlen($row['varieta'])>3){
								$type_array[28]=str_pad(substr(("Varietà ".$row['varieta']), 0, 300 ), 300); // Note (varietà)
							}
						}
						if ($row['cod_operazione']==2){// Confezionamento senza etichettatura
							$row['numdoc']="";
							$type_array[6]=str_pad("L1", 10); // codice operazione
							$type_array[23]=sprintf ("%013d",$row4['quanti']); // quantità scarico olio sfuso
							$type_array[24]=sprintf ("%013d",$quantilitri); // quantità carico olio confezionato in litri
							$type_array[18]=sprintf ("%02d",$row5['or_macro']); // Codice Origine olio per macro area a fine operazione
							$type_array[19]=str_pad($row5['or_spec'], 80); // Descrizione Origine olio specifica a fine operazione
							$type_array[39]="X"; // Flag NON etichettato a fine operazione
							$type_array[27]=str_pad(substr($row['identifier'], 0, 20 ), 20); // Lotto di appartenenza
							$type_array[16]=sprintf ("%02d",$row['or_macro']); // Codice Origine olio per macro area
							if (strlen($row['varieta'])>3){
								$type_array[28]=str_pad(substr(("Varietà ".$row['varieta']), 0, 300 ), 300); // Note (varietà)
							}
						}
						if ($row['cod_operazione']==3){// Etichettatura
							$row['numdoc']="";
							$type_array[6]=str_pad("L2", 10); // codice operazione
							$type_array[38]="X"; // Flag NON etichettato
							$type_array[15]=sprintf ("%02d",$row5['categoria']);// categoria olio fine operazione
							$type_array[24]=sprintf ("%013d",$quantilitri); // quantità carico olio confezionato in litri
							$type_array[25]=sprintf ("%013d",$quantilitri); // quantità scarico olio confezionato in litri
							$type_array[18]=sprintf ("%02d",$row5['or_macro']); // Codice Origine olio per macro area a fine operazione
							$type_array[16]=sprintf ("%02d",$row['or_macro']); // Codice Origine olio per macro area
							$type_array[27]=str_pad(substr($row['identifier'], 0, 20 ), 20); // Lotto di appartenenza
							if (strlen($row['varieta'])>3){
								$type_array[28]=str_pad(substr(("Varietà ".$row['varieta']), 0, 300 ), 300); // Note (varietà)
							}
						}
						if ($row['cod_operazione']==4){// Svuotamento di olio confezionato
							$type_array[6]=str_pad("X", 10); // codice operazione
							$type_array[18]=sprintf ("%02d",$row5['or_macro']); // Codice Origine olio per macro area a fine operazione
							$type_array[19]=str_pad($row5['or_spec'], 80); // Descrizione Origine olio specifica a fine operazione
							$type_array[15]=sprintf ("%02d",$row5['categoria']);// categoria olio fine operazione
							if ($row['etichetta']==0){// Flag NON etichettato
								$type_array[38]="X";
							}
							if (strlen($row['varieta'])>3){
								$type_array[28]=str_pad(substr(("Varietà ".$row['varieta']), 0, 300 ), 300); // Note (varietà)
							}
						}
						if ($row['cod_operazione']==5){// Movimentazione interna senza cambio di origine
							$type_array[6]=str_pad("M1", 10); // codice operazione
							$type_array[18]=sprintf ("%02d",$row5['or_macro']); // Codice Origine olio per macro area a fine operazione
							$type_array[19]=str_pad($row5['or_spec'], 80); // Descrizione Origine olio specifica a fine operazione
							$type_array[15]=sprintf ("%02d",$row5['categoria']);// categoria olio fine operazione
							$type_array[23]=sprintf ("%013d",str_replace(".", "", number_format($row['quanti'],3))); // quantità scarico olio sfuso
							$type_array[22]=sprintf ("%013d",str_replace(".", "", number_format($row['quanti'],3))); // quantità scarico olio sfuso
							$change=$row['recip_stocc']; // devo scambiare i contenitori
							$row['recip_stocc']=$row['recip_stocc_destin'];
							$row['recip_stocc_destin']=$change;
							if (strlen($row['varieta'])>3){
								$type_array[28]=str_pad(substr($row['varieta'], 0, 300 ), 300); // Note (varietà)
							}
							$row['numdoc']="";// azzero numero documento giustificativo perché non ammesso con M1
						}
					}
					if (intval($row['id_orderman'])>0 AND $row['operat']==-1 AND $row['cod_operazione']=="S7") {// è un'uscita di olio per produrre altro
						$type_array[6]=str_pad("S7", 10); // codice operazione > S7 scarico di olio destinato ad altri usi
						if ($row['SIAN']==1){ // se è olio
							if ($row['confezione']==0) { // se è sfuso
								$type_array[23]=sprintf ("%013d", str_replace(".", "", number_format($row['quanti'],3)));
							} else { // se è confezionato
								$row['recip_stocc']="";
								$quantilitri=number_format($row['quanti']*$row['confezione'],3);// trasformo le confezioni in litri
								$quantilitri = str_replace(".", "", $quantilitri);
								$type_array[25]=sprintf ("%013d",$quantilitri);
								$type_array[27]=str_pad(substr($row['identifier'], 0, 20 ), 20); // Lotto di appartenenza
								if ($row['etichetta']==0){// Flag NON etichettato
									$type_array[38]="X";
								}
							}
							$type_array[28]=str_pad($row['desdoc'], 300); // note, obbligatorio con S7
						} else { //se sono olive
							$type_array[10]=sprintf ("%013d", str_replace(".", "", number_format($row['quanti'],3)));
						}
						$row['confezione']=""; // annullo capacità confezione perché con S7 non è ammessa
						$datdoc=""; // annullo data documento giustificativo perché con S7 non è ammessa
					}

				// >> Antonio Germani - Caso Carico da acquisti e magazzino

					if ($row['operat']==1 AND intval($row['id_orderman'])==0){ //se è un carico NON connesso a produzione
						if ($row['cod_operazione']==10){// carico olio lampante da recupero
							$type_array[22]=sprintf ("%013d", str_replace(".", "", number_format($row['quanti'],3))); // carico olio sfuso
							$row['categoria']="4"; // categoria olio lampante
							$row['or_spec']="";
							$row['or_macro']="";
							$row['estrazione']="";
							$row['biologico']="";
							$row['numdoc']="";
							$datdoc="";
						} else {
							if ($row['SIAN']==1){ // se è olio
								if ($row['confezione']==0) { // se è sfuso

									$type_array[22]=sprintf ("%013d", str_replace(".", "", number_format($row['quanti'],3)));

								} else { // se è confezionato
									$quantilitri=number_format($row['quanti']*$row['confezione'],3);// trasformo le confezioni in litri
									$quantilitri = str_replace(".", "", $quantilitri);
									$type_array[24]=sprintf ("%013d",$quantilitri);
									$type_array[27]=str_pad(substr($row['identifier'], 0, 20 ), 20); // Lotto di appartenenza
									if ($row['etichetta']==0){// Flag NON etichettato
										$type_array[38]="X";
									}
								}
							} else { //se sono olive
								$type_array[9]=sprintf ("%013d", str_replace(".", "", number_format($row['quanti'],3)));
							}
							if ($row['cod_operazione']==3 OR $row['cod_operazione']==8 ){
								$type_array[7]=sprintf ("%010d",$row['id_SIAN']); // identificatore fornitore/cliente/terzista
							}
							$type_array[7]=sprintf ("%010d",$row['id_SIAN']); // identificatore fornitore/cliente/terzista/committente
							$row['confezione']="";
							if ($row['cod_operazione']==5 OR $row['cod_operazione']==9 OR $row['cod_operazione']==10) {
								$type_array[7]=sprintf ("%010d",""); // identificatore fornitore/cliente/terzista/committente
							}
							if ($row['cod_operazione']==5) {
								$type_array[13]=sprintf ("%010d",$row['id_SIAN']); // identificativo stabilimento di provenienza/destinazione olio
							}
						}
						$type_array[6]=str_pad("C".$row['cod_operazione'], 10); // codice operazione
						if (strlen($row['varieta'])>3){
							$type_array[28]=str_pad(substr(("Varietà ".$row['varieta']), 0, 300 ), 300); // Note (varietà)
						}
					}

				// >> Antonio Germani - Caso Scarico da vendite, magazzino e da DDL (ddt acquisto in conto la vorazione)

					if ($row['operat']==-1 AND intval($row['id_orderman'])==0){ // se è uno scarico NON connesso a produzione
						if ($row['tipdoc'] == "DDL" && $row['cod_operazione']="P"){
							$note="Campionamento/analisi ";
              $row['numdoc']="";
							$datdoc="";
							$type_array[28]=str_pad(substr($note, 0, 300 ), 300); // Note (campionamento o analisi)
						}
						$type_array[6]=str_pad("S".$row['cod_operazione'], 10); // codice operazione
						if ($row['SIAN']==1){ // se è olio
							if ($row['confezione']==0) { // se è sfuso
								$type_array[23]=sprintf ("%013d", str_replace(".", "", number_format($row['quanti'],3)));
							} else { // se è confezionato
								$quantilitri=number_format($row['quanti']*$row['confezione'],3);// trasformo le confezioni in litri
								$quantilitri = str_replace(".", "", $quantilitri);
								$type_array[25]=sprintf ("%013d",$quantilitri);
								$type_array[27]=str_pad(substr($row['identifier'], 0, 20 ), 20); // Lotto di appartenenza
							}
						} else { //se sono olive
							$type_array[10]=sprintf ("%013d", str_replace(".", "", number_format($row['quanti'],3)));
						}

						$row['confezione']="";// Tutte le operazioni di Vendita non vogliono la confezione indicata

						if ($row['cod_operazione']==1 OR $row['cod_operazione']==2 OR $row['cod_operazione']==3 OR $row['cod_operazione']==5 OR $row['cod_operazione']==10){
							$type_array[7]=sprintf ("%010d",$row['id_SIAN']); // identificatore fornitore/cliente/terzista/
							if (strlen($row['varieta'])>3){
								$type_array[28]=str_pad(substr(($note."Varietà ".$row['varieta']), 0, 300 ), 300); // Note (varietà)
							}
						}
						if ($row['cod_operazione']==6) { // cessione omaggio
							$type_array[7]=sprintf ("%010d",$row['id_SIAN']); // identificatore fornitore/cliente/terzista//facoltativo
							if (strlen($row['varieta'])>3){
								$type_array[28]=str_pad(substr(($note."Varietà ".$row['varieta']), 0, 300 ), 300); // Note (varietà)
							}
						}
						if ($row['cod_operazione']==4) {// scarico trasferimento ad altro deposito stessa impresa
							$type_array[13]=sprintf ("%010d",$row['id_SIAN']); // identificativo stabilimento di provenienza/destinazione olio
							if (strlen($row['varieta'])>3){
								$type_array[28]=str_pad(substr(($note."Varietà ".$row['varieta']), 0, 300 ), 300); // Note (varietà)
							}
						}
						if ($row['cod_operazione']==7) { // scarico altri usi
							$row['numdoc']="";
							$datdoc="";
							$row['confezione']="";
							$type_array[28]=str_pad("altro uso generico", 300); // note, obbligatorie con S7
						}
						if ($row['cod_operazione']==8) { //scarico autoconsumo
							$row['numdoc']="";
							$datdoc="";
							$row['confezione']="";
						}
						if ($row['cod_operazione']==12) { // Perdite o cali di olio
							$row['numdoc']="";
							$datdoc="";
							$row['confezione']="";
							$type_array[6]=str_pad("SP", 10); // codice operazione
						}
						if ($row['cod_operazione']==13) { // Separazione Morchie
							$row['numdoc']="";
							$datdoc="";
							$row['confezione']="";
							$type_array[6]=str_pad("Q", 10); // codice operazione
						}
					}

					// Antonio Germani - campi comuni a tutti i casi
					$type_array[0]=str_pad($admin_aziend['codfis'], 16); // aggiunge spazi finali
					$type_array[1]=sprintf ("%010d",$id_sian['val']); // identificativo stabilimento/deposito
					$type_array[2]=sprintf ("%010d",$nprog); // num. progressivo
					$type_array[3]=str_pad($dd, 8);//data dell'operazione
					$type_array[4]=str_pad($row['numdoc'], 10);// numero documento giustificativo
					$type_array[5]=str_pad($datdoc, 8);//data del documento giustificativo
					$type_array[11]=str_pad(substr($row['recip_stocc'], 0, 10 ), 10); // identificativo recipiente o silos di stoccaggio
					$type_array[12]=str_pad(substr($row['recip_stocc_destin'], 0, 10 ), 10); // identificativo recipiente o silos di stoccaggio destinazione
					$type_array[14]=sprintf ("%02d",$row['categoria']); // Categoria olio
					$type_array[16]=sprintf ("%02d",$row['or_macro']); // Codice Origine olio per macro area
					$type_array[17]=str_pad($row['or_spec'], 80); // Descrizione Origine olio specifica

					if ($row['estrazione']==1){
						$type_array[30]="X"; // Flag prima spremitura a freddo
					}
					if ($row['estrazione']==2){
						$type_array[32]="X"; // Flag estratto a freddo
					}
					if ($row['biologico']==1){
						$type_array[34]="X"; // Flag biologico
					}
					if ($row['biologico']==2){
						$type_array[36]="X"; // Flag in conversione
					}

					if ($row['confezione']>0){
						$type_array[45]=sprintf ("%013d", str_replace(".", "", $row['confezione'])); // capacità confezione
					}
					$type_array[48]="I";
					$type= implode(";",$type_array);
					$type=$type."\r\n";// il SIAN richiede un ritorno a capo dopo ogni record
					fwrite($myfile, $type);
					$lastdatdoc=$row['datdoc'];
					// modifico lo status del movimento SIAN come inviato inserendoci il nome file
					gaz_dbi_put_row($gTables['camp_mov_sian'], 'id_mov_sian', $row['id_mov_sian'], 'status', $namefile);
			}
		}
	}
	fclose($myfile);
	?>
	<!-- E necessario evitare che se si ricarica la pagina si rigeneri un nuovo file  -->
	<form name="myform" method="POST" enctype="multipart/form-data">
	<input type="hidden" value="<?php echo $ritorno; ?>" name="ritorno">
	<input type="hidden" value="<?php echo $namefile; ?>" name="namefile">
	<script type="text/javascript">
	document.myform.submit();
	</script>
	</form>
	<?php
}

require("../../library/include/header.php");
$script_transl=HeadMain(0,array('calendarpopup/CalendarPopup'));

$namefile=substr($namefile,0,-4)
?>
<div class="panel panel-default gaz-table-form">
    <div class="container-fluid">
		<div align="center">
			<p>
			Il file è stato generato. <br>Prima di accedere al portale del SIAN per l'upload bisogna scaricare il file nel proprio pc.
			</p>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label for="cod_silos" class="col-sm-4 control-label"><?php echo "Download del file generato: "; ?></label>
					<p><a href="../camp/getfilesian.php?filename=<?php echo $namefile;?>&folder=&ext=txt&company_id=<?php echo $admin_aziend['company_id'];?>" class="col-sm-6 control-label">
					<?php echo $namefile; ?>
					<i class="glyphicon glyphicon-file" title="Scarica il file appena generato"></i>
					</a></p>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label for="cod_silos" class="col-sm-4 control-label"><?php echo "Accedi al portale dell'olio del SIAN: "; ?></label>
					<p><a  class="btn btn-info btn-md" href="javascript:;" onclick="window.open('<?php echo"https://www.sian.it/icqrfportaleolioAR/start.do";?>', 'titolo', 'menubar=no, toolbar=no, width=800, height=400, left=80%, top=80%, resizable, status, scrollbars=1, location');">
					<img src="../../modules/camp/media/logo_sian.jpg" alt="Logo portale SIAN" title="Vai al portale dell'olio del SIAN" style="max-width:100%">
					</a></p>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
						<label for="cod_silos" class="col-sm-4 control-label"><?php echo "PROMEMORIA: "; ?></label>

					<p>AGEA considera come "campagna di commercializzazione" il periodo che va dal <b>1 luglio</b> al <b>30 giugno</b> dell'anno successivo. Quindi, dal primo di luglio, per poter continuare a inserire normalmente le operazioni nel registro, è necessario aver eseguito l'operazione di chiusura della campagna di commercializzazione. Questa operazione non avviene in automatico, deve essere fatta manualmente in qualsiasi momento a partire dal 1 luglio di ogni anno, senza scadenza alcuna. Se non eseguita, non sarà permesso registrare movimenti successivi al 30 giugno, se, invece, viene attivata correttamente non consente più di effettuare inserimenti, modifiche o eliminazioni alle operazioni di registro precedenti al 1 luglio.
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
<span class="navbar-fixed-bottom" style="left:20%; z-index:2000;"> Registro di campagna è un modulo di Antonio Germani</span>
<?php
require("../../library/include/footer.php");
?>
