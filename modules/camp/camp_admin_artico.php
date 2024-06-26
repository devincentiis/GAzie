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
 // >> Gestione campi articolo <<
require("../../library/include/datlib.inc.php");
require ("../../modules/magazz/lib.function.php");
$admin_aziend = checkAdmin();
$msg = array('err' => array(), 'war' => array());
$modal_ok_insert = false;
$today=	strtotime(date("Y-m-d H:i:s",time()));
$presente="";
$largeimg=0;
$gForm = new magazzForm();
$openmore=false;

if(isset($_POST['delete']) AND $_POST['delete']>0) {
	gaz_dbi_del_row($gTables['camp_uso_fitofarmaci'], 'id', intval($_POST['delete']));
	$openmore=true;
	$_POST['delete']=0;
}

if (isset($_POST['Update']) || isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

// Antonio Germani questo serve per la nuova ricerca fornitore
if (isset($_POST['fornitore'])){
		$form['fornitore'] = $_POST['fornitore'];
		$form['id_anagra'] = intval ($form['fornitore']);
}

if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
	$form = gaz_dbi_parse_post('artico');
	$form['nomefito']=$_POST['nomefito'];
	$form['categoria']=$_POST['categoria'];
	$form['etichetta']=$_POST['etichetta'];
	$form['biologico']=$_POST['biologico'];
	$form['estrazione']=$_POST['estrazione'];
	$form['or_spec']=$_POST['or_spec'];
	$form['or_macro']=$_POST['or_macro'];
	$form['confezione']=$_POST['confezione'];
  $form['ritorno'] = $_POST['ritorno'];
  $form['ref_code'] = substr($_POST['ref_code'], 0, 32);
	$form['conferma'] = $_POST['conferma'];
	$form['aliiva'] = $_POST['aliiva'];
	if ($_POST['oldnomefito']<>$_POST['nomefito']){ // se è stato cambiato il nome del fitofarmaco prendo id_reg e propongo il codice
		$resfit=gaz_dbi_get_row($gTables['camp_fitofarmaci'], 'PRODOTTO', $_POST['nomefito']);
    $_POST['id_reg'] = $resfit['NUMERO_REGISTRAZIONE'];
		if (intval($_POST['id_reg'])>0){ // se è stato trovato nel DB del ministero propongo il codice
			$_POST['codice']=substr($_POST['nomefito'],0,32);
		} else { // altrimenti tolgo il nome del fitofarmaco che non esiste
			$_POST['nomefito']="";
			$_POST['id_reg']=0;
		}
	}
	$form['id_reg']=$_POST['id_reg'];
	$form['codice'] = trim($_POST['codice']);
	$query="SELECT * FROM ".$gTables['camp_uso_fitofarmaci']." LEFT JOIN ".$gTables['camp_avversita']." on (".$gTables['camp_avversita'].".id_avv = ".$gTables['camp_uso_fitofarmaci'].".id_avv) LEFT JOIN ".$gTables['camp_colture']." on (".$gTables['camp_colture'].".id_colt = ".$gTables['camp_uso_fitofarmaci'].".id_colt) WHERE numero_registrazione = ". $form['id_reg'] ." OR cod_art = '". $form['codice'] ."' ORDER BY nome_colt";
	$res_usofito = gaz_dbi_query($query);
	if (isset ($_POST['fornitore'])) {
		$form['fornitore'] = $_POST['fornitore'];
		$form['id_anagra'] = intval ($form['fornitore']);
	} else {
		$form['fornitore']="";
	}
	if (isset ($_POST['classif_amb'])) {
		$form['classif_amb']= $_POST['classif_amb'];
	} else {
		$form['classif_amb']=0;
	}
	if (isset ($_POST['scorta'])) {
		$form['scorta']= $_POST['scorta'];
	} else {
		$form['scorta']=0;
	}
	if (isset ($_POST['riordino'])) {
		$form['riordino']= $_POST['riordino'];
	} else {
		$form['riordino']=0;
	}
	if (isset ($_POST['tempo_sospensione'])) {
		$form['tempo_sospensione']= $_POST['tempo_sospensione'];
	} else {
		$form['tempo_sospensione']=0;
	}
	if (isset ($_POST['dose_massima'])) {
		$form['dose_massima']= $_POST['dose_massima'];
	} else {
		$form['dose_massima']=0;
	}
	if (isset ($_POST['rame_metallico'])) {
		$form['rame_metallico']= $_POST['rame_metallico'];
	} else {
		$form['rame_metallico']=0;
	}
	if (isset ($_POST['perc_N'])) {
		$form['perc_N']= $_POST['perc_N'];
	} else {
		$form['perc_N']=0;
	}
	if (isset ($_POST['perc_P'])) {
		$form['perc_P']= $_POST['perc_P'];
	} else {
		$form['perc_P']=0;
	}
	if (isset ($_POST['perc_K'])) {
		$form['perc_K']= $_POST['perc_K'];
	} else {
		$form['perc_K']=0;
	}

    // i prezzi devono essere arrotondati come richiesti dalle impostazioni aziendali

    $form["preacq"] = number_format($form['preacq'], $admin_aziend['decimal_price'], '.', '');

    $form['rows'] = array();

    $ndoc = 0;
    if (isset($_POST['rows'])) {
        foreach ($_POST['rows'] as $ndoc => $value) {
            $form['rows'][$ndoc]['id_doc'] = intval($value['id_doc']);
            $form['rows'][$ndoc]['extension'] = substr($value['extension'], 0, 5);
            $form['rows'][$ndoc]['title'] = substr($value['title'], 0, 255);
            $ndoc++;
        }
    }
    // fine documenti/certificati
    $form['body_text'] = filter_input(INPUT_POST, 'body_text');

    if (isset($_POST['Submit']) || ( isset($_POST['mode-act']))) { // conferma tutto

        if ($toDo == 'update') {  // controlli in caso di modifica
            if (trim($form['codice']) != trim($form['ref_code'])) { // se sto modificando il codice originario
                // controllo che l'articolo ci sia gia'
                $rs_articolo = gaz_dbi_dyn_query('codice', $gTables['artico'], "codice = '" . $form['codice'] . "'", "codice DESC", 0, 1);
                $rs = gaz_dbi_fetch_array($rs_articolo);
                if ($rs) {
                    $msg['err'][] = 'codice';
                }
                // controllo che il precedente non abbia movimenti di magazzino associati
                $rs_articolo = gaz_dbi_dyn_query('artico', $gTables['movmag'], "artico = '" . $form['ref_code'] . "'", "artico DESC", 0, 1);
                $rs = gaz_dbi_fetch_array($rs_articolo);
                if ($rs) {
                    $msg['err'][] = 'movmag';
                }
            }
        } else {
            // controllo che l'articolo ci sia gia'
            $rs_articolo = gaz_dbi_dyn_query('codice', $gTables['artico'], "codice = '" . $form['codice'] . "'", "codice DESC", 0, 1);
            $rs = gaz_dbi_fetch_array($rs_articolo);
            if ($rs) {
                $msg['err'][] = 'codice';
            }
        }
        if (!empty($_FILES['userfile']['name'])) {
            if (!( $_FILES['userfile']['type'] == "image/png" ||
                    $_FILES['userfile']['type'] == "image/x-png" ||
                    $_FILES['userfile']['type'] == "image/jpeg" ||
                    $_FILES['userfile']['type'] == "image/jpg" ||
                    $_FILES['userfile']['type'] == "image/gif" ||
                    $_FILES['userfile']['type'] == "image/x-gif"))
                $msg['err'][] = 'filmim';
            // controllo che il file non sia piu' grande di circa 64kb
            if ($_FILES['userfile']['size'] > 65530){
				 //$msg['err'][] = 'filsiz';
				 //Antonio Germani anziche segnalare errore ridimensiono l'immagine
							$maxDim = 80;
							$file_name = $_FILES['userfile']['tmp_name'];
							list($width, $height, $type, $attr) = getimagesize( $file_name );
							if ( $width > $maxDim || $height > $maxDim ) {
								$target_filename = $file_name;
								$ratio = $width/$height;
									if( $ratio > 1) {
										$new_width = $maxDim;
										$new_height = $maxDim/$ratio;
									} else {
											$new_width = $maxDim*$ratio;
											$new_height = $maxDim;
										}
								$src = imagecreatefromstring( file_get_contents( $file_name ) );
								$dst = imagecreatetruecolor( $new_width, $new_height );
								imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
								imagedestroy( $src );
								imagepng( $dst, $target_filename); // adjust format as needed
								imagedestroy( $dst );
							}
			$largeimg=1;
				// fine ridimensionamento immagine
			}
        }
        if (empty($form["codice"])) {
            $msg['err'][] = 'valcod';
        }
        if (empty($form["descri"])) {
            $msg['err'][] = 'descri';
        }
        if (empty($form["unimis"])) {
            $msg['err'][] = 'unimis';
        } else $form['uniacq']=$form['unimis'];
		if ($form['rame_metallico']>0 && $form["unimis"]<>"Kg"){
			if ($form['rame_metallico']>0 && $form["unimis"]<>"l"){
			$msg['err'][]= 'unimis2';}
		}
		if ($form['perc_N']>0 && $form["unimis"]<>"Kg"){

			$msg['err'][]= 'unimis3';
		}
		if ($form['perc_P']>0 && $form["unimis"]<>"Kg"){

			$msg['err'][]= 'unimis4';
		}
		if ($form['perc_K']>0 && $form["unimis"]<>"Kg"){

			$msg['err'][]= 'unimis5';
		}
		 //Antonio Germani controllo che sia stata inserita una categoria merceologica
		if (empty($form["catmer"])) {
            $msg['err'][] = 'catmer';
        }

		if (empty($form["aliiva"])) {
            $msg['err'][] = 'aliiva';
        }
        // per poter avere la tracciabilità è necessario attivare la contabità di magazzino in configurazione azienda
        if ($form["lot_or_serial"] > 0 && $admin_aziend['conmag'] <= 1) {
            $msg['err'][] = 'lotmag';
        }
		if ($form['SIAN']>0){ // inizio controlli SIAN
			if ($form['or_macro']==0){
				$msg['err'][] = 'or_macro';
			}
			if ($form['or_macro']==2 OR $form['or_macro']==4 OR $form['or_macro']==6 OR $form['or_macro']==8 OR $form['or_macro']==10 OR $form['or_macro']==11 OR $form['or_macro']==12 OR $form['or_macro']==13 OR $form['or_macro']==14){
				if ($form['or_spec']==0){
					$msg['err'][] = 'or_spec';
				}
			}
		}
        if (count($msg['err']) == 0) { // nessun errore
            if ($_FILES['userfile']['size'] > 0) { //se c'e' una nuova immagine nel buffer
              if ($largeimg==0){
               $form['image'] = file_get_contents($_FILES['userfile']['tmp_name']);
              } else {
                $form['image'] = file_get_contents($target_filename);
              }
            } elseif ($toDo == 'update') { // altrimenti riprendo la vecchia ma solo se è una modifica
                $oldimage = gaz_dbi_get_row($gTables['artico'], 'codice', $form['ref_code']);
                $form['image'] = $oldimage['image'];
            } else {
                $form['image'] = '';
            }
            /** inizio modifica FP 03/12/2015
             * aggiorno il campo con il codice fornitore
             */
            $form['clfoco'] = $form['id_anagra'];
            /** fine modifica FP */
            $tbt = trim($form['body_text']);
            // Se non ci sono errori  aggiorno il db

            if($_POST['or_spec']==0){$form['or_spec']="";}
            if($_POST['or_spec']==1){$form['or_spec']="Spagna";}
            if($_POST['or_spec']==2){$form['or_spec']="Grecia";}
            if($_POST['or_spec']==3){$form['or_spec']="Portogallo";}
            if($_POST['or_spec']==4){$form['or_spec']="Francia";}
            if($_POST['or_spec']==5){$form['or_spec']="Malta";}
            if($_POST['or_spec']==6){$form['or_spec']="Cipro";}
            if($_POST['or_spec']==7){$form['or_spec']="Penisola Iberica";}
            if($_POST['or_spec']==8){$form['or_spec']="Altro";}
            if ($toDo == 'insert') {
              gaz_dbi_table_insert('artico', $form);
              gaz_dbi_table_insert('camp_artico', $form);
              if (!empty($tbt)) {
                  bodytextInsert(array('table_name_ref' => 'artico_' . $form['codice'], 'body_text' => $form['body_text'], 'lang_id' => $admin_aziend['id_language']));
              }
            } elseif ($toDo == 'update') {
				gaz_dbi_table_update('artico', $form['ref_code'], $form);
                $esist=gaz_dbi_get_row($gTables['camp_artico'], 'codice', $form['ref_code']);
				if (isset($esist)){
					gaz_dbi_table_update('camp_artico', $form['ref_code'], $form);
				} else { // se non esiste lo inserisco
					gaz_dbi_table_insert('camp_artico', $form);
				}
                $bodytext = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", 'artico_' . $form['codice']);
                if (empty($tbt) && $bodytext) {
                    // è vuoto il nuovo ma non lo era prima, allora lo cancello
                    gaz_dbi_del_row($gTables['body_text'], 'id_body', $bodytext['id_body']);
                } elseif (!empty($tbt) && $bodytext) {
                    // c'è e c'era quindi faccio l'update
                    bodytextUpdate(array('id_body', $bodytext['id_body']), array('table_name_ref' => 'artico_' . $form['codice'], 'body_text' => $form['body_text'], 'lang_id' => $admin_aziend['id_language']));
                } elseif (!empty($tbt)) {
                    // non c'era lo inserisco
                    bodytextInsert(array('table_name_ref' => 'artico_' . $form['codice'], 'body_text' => $form['body_text'], 'lang_id' => $admin_aziend['id_language']));
                }
            }

			if ($toDo == 'insert') {
				$_SESSION['ok_ins']=$form['codice'].' - '.$form['descri'];
				header("Location: ../../modules/camp/camp_admin_artico.php?Update&codice=".$form['codice']);
			}else{
				header("Location: ../../modules/camp/camp_report_artico.php");
			}
            exit;
        }

    } elseif (isset($_POST['Return']) ) { // torno indietro

        header("Location: " . $form['ritorno']);
        exit;
    }

} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
  $form = gaz_dbi_get_row($gTables['artico'], 'codice', substr($_GET['codice'], 0, 32));
	$camp = gaz_dbi_get_row($gTables['camp_artico'], 'codice', substr($_GET['codice'], 0, 32));
	$query="SELECT * FROM ".$gTables['camp_uso_fitofarmaci']." LEFT JOIN ".$gTables['camp_avversita']." on (".$gTables['camp_avversita'].".id_avv = ".$gTables['camp_uso_fitofarmaci'].".id_avv) LEFT JOIN ".$gTables['camp_colture']." on (".$gTables['camp_colture'].".id_colt = ".$gTables['camp_uso_fitofarmaci'].".id_colt) WHERE numero_registrazione = ". $form['id_reg'] ." OR cod_art = '". $form['codice'] ."' ORDER BY nome_colt";
	$res_usofito = gaz_dbi_query($query);
	$res_fito=gaz_dbi_get_row($gTables['camp_fitofarmaci'], 'NUMERO_REGISTRAZIONE', $form['id_reg']);
	$form['nomefito']=($res_fito)?$res_fito['PRODOTTO']:'';
	$form['categoria']=($camp)?$camp['categoria']:0;
	$form['etichetta']=($camp)?$camp['etichetta']:0;
	$form['biologico']=($camp)?$camp['biologico']:0;
	$form['estrazione']=($camp)?$camp['estrazione']:0;
	$form['or_spec']=($camp)?$camp['or_spec']:'';
	$form['or_macro']=($camp)?$camp['or_macro']:0;
	$form['confezione']=($camp)?$camp['confezione']:0;
	$form['conferma'] = "";
	if(!isset($form['or_spec'])){$form['or_spec']=0;}
	if($form['or_spec']=="Spagna"){$form['or_spec']=1;}
	if($form['or_spec']=="Grecia"){$form['or_spec']=2;}
	if($form['or_spec']=="Portogallo"){$form['or_spec']=3;}
	if($form['or_spec']=="Francia"){$form['or_spec']=4;}
	if($form['or_spec']=="Malta"){$form['or_spec']=5;}
	if($form['or_spec']=="Cipro"){$form['or_spec']=6;}
	if($form['or_spec']=="Penisola Iberica"){$form['or_spec']=7;}
	if($form['or_spec']=="Altro"){$form['or_spec']=8;}

    $form['ritorno'] = $_SERVER['HTTP_REFERER'];

    $form['ref_code'] = $form['codice'];
    // i prezzi devono essere arrotondati come richiesto dalle impostazioni aziendali
    $form["preacq"] = number_format($form['preacq'], $admin_aziend['decimal_price'], '.', '');
    $form["preve1"] = number_format($form['preve1'], $admin_aziend['decimal_price'], '.', '');
    $form["preve2"] = number_format($form['preve2'], $admin_aziend['decimal_price'], '.', '');
    $form["preve3"] = number_format($form['preve3'], $admin_aziend['decimal_price'], '.', '');
    $form["preve4"] = number_format($form['preve4'], $admin_aziend['decimal_price'], '.', '');
    $form["web_price"] = number_format($form['web_price'], $admin_aziend['decimal_price'], '.', '');
    $form['rows'] = array();

    $form['id_anagra'] = $form['clfoco'];
	$anagra = gaz_dbi_get_row($gTables['clfoco'], "codice", $form['id_anagra']);
    if (isset($anagra)){
	$form['fornitore']=$form['id_anagra']." - ".$anagra['descri'];
	} else {
		$form['fornitore']="";
	}
    /** fine modifica FP */
    // inizio documenti/certificati
    $ndoc = 0;
    $rs_row = gaz_dbi_dyn_query("*", $gTables['files'], "item_ref = '" . $form['codice'] . "'", "id_doc DESC");
    while ($row = gaz_dbi_fetch_array($rs_row)) {
        $form['rows'][$ndoc] = $row;
        $ndoc++;
    }
    // fine documenti/certificati
    $bodytext = gaz_dbi_get_row($gTables['body_text'], "table_name_ref", 'artico_' . $form['codice']);
    $form['body_text'] = ($bodytext)?$bodytext['body_text']:'';

} else { //se e' il primo accesso per INSERT

	// controllo se la tabella DB fitofarmaci è popolata
	$query="SELECT * FROM ".$gTables['camp_fitofarmaci']. " LIMIT 1";
	$checkdbfito = gaz_dbi_query($query);
	if ($checkdbfito -> num_rows ==0) {
		$checkdbfito="WARNING";
	}
  $form = gaz_dbi_fields('artico');
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['ref_code'] = '';
  $form['aliiva'] = $admin_aziend['preeminent_vat'];
  // i prezzi devono essere arrotondati come richiesti dalle impostazioni aziendali
  $form["preacq"] = number_format($form['preacq'], $admin_aziend['decimal_price'], '.', '');
  $form["preve1"] = number_format($form['preve1'], $admin_aziend['decimal_price'], '.', '');
  $form["preve2"] = number_format($form['preve2'], $admin_aziend['decimal_price'], '.', '');
  $form["preve3"] = number_format($form['preve3'], $admin_aziend['decimal_price'], '.', '');
  $form["preve4"] = number_format($form['preve4'], $admin_aziend['decimal_price'], '.', '');
  $form["web_price"] = number_format($form['web_price'], $admin_aziend['decimal_price'], '.', '');
  $form['web_public'] = 0;
  $form['depli_public'] = 0;
	$form['SIAN']=0;
	$form['id_reg']=0;
	$form['categoria']="";
	$form['etichetta']=0;
	$form['biologico']=0;
	$form['estrazione']=0;
	$form['or_spec']="";
	$form['or_macro']="";
	$form['confezione']=0;
	$form['quality']="";
    $form['nomefito']="";
    $form['id_anagra'] = "";
	$form['fornitore'] = "";
    $form['conferma'] = "";
	 $form['oldnomefito'] = "";
    /** fine modifica FP */
    // eventuale descrizione amplia
    $form['body_text'] = '';
	$form['unimis']= '';
}
require("../../library/include/header.php");
$script_transl = HeadMain(0,array('custom/autocomplete',));

if ($openmore==true){
	?><!-- riapro il pannello dosi specifiche  -->
	<style>#more { display: unset; }</style>
	<?php
	$btn_uso="&#9650 Chiudi";
} else {
	?><!-- al primo accesso il pannello dosi specifiche deve essere spento -->
	<style>#more { display:none; }</style>
	<?php
	$btn_uso="&#9660 Apri dosi e usi"; // valore di default del pulsante apri dosi specifiche
}

// se è stata inviata una dose specifica
if (isset($_POST['OKsub']) AND $_POST['id_reg']>0 AND $_POST['dose']>0 AND intval($_POST['nome_colt'])>0 AND intval($_POST['nome_avv']>0)){// se inviata una dose specifica, la aggiungo al DB
	$btn_uso="&#9650 Chiudi";
  // controllo se è stata già inserita questa dose specifica
	$rscheck = gaz_dbi_dyn_query("*", $gTables['camp_uso_fitofarmaci'], "numero_registrazione = '".$_POST['id_reg']."' AND id_colt = '".intval($_POST['nome_colt'])."' AND id_avv ='".intval($_POST['nome_avv'])."'" ,2,0,1);
	if ($rscheck->num_rows == 0){ // se non è già inserita, la inserisco
		$formuso['id_colt'] = intval($_POST['nome_colt']);
		$formuso['id_avv'] = intval($_POST['nome_avv']);
		$formuso['cod_art'] = ($_POST)?$_POST['codice']:'';
		$formuso['dose'] = $_POST['dose'];
		$formuso['tempo_sosp'] = $_POST['tempo_sosp'];
		$formuso['numero_registrazione'] = $_POST['id_reg'];
		$formuso['max_tratt'] = $_POST['max_tratt'];
		gaz_dbi_table_insert('camp_uso_fitofarmaci',$formuso);
    ?><!-- continuo a tenere aperto il pannello dosi specifiche  -->
    <style>#more { display:block; }</style>
    <?php
    // ricarico le dosi presenti nel DB
    $query="SELECT * FROM ".$gTables['camp_uso_fitofarmaci']." LEFT JOIN ".$gTables['camp_avversita']." on (".$gTables['camp_avversita'].".id_avv = ".$gTables['camp_uso_fitofarmaci'].".id_avv) LEFT JOIN ".$gTables['camp_colture']." on (".$gTables['camp_colture'].".id_colt = ".$gTables['camp_uso_fitofarmaci'].".id_colt) WHERE numero_registrazione = ". $form['id_reg'] ." OR cod_art = '". $form['codice'] ."' ORDER BY nome_colt";
    $res_usofito = gaz_dbi_query($query);
	}
} elseif(isset($_POST['OKsub']) AND $toDo == 'insert'){
  $msg['err'][]= 'insert_before_OKsub';
}elseif  (isset($_POST['OKsub'])){
	?><!-- apro il pannello dosi specifiche  -->
	<style>#more { display:block; }</style>
	<?php
	$btn_uso="&#9650 Chiudi";
} else {
	?><!-- il pannello dosi specifiche deve essere spento -->
	<style>#more { display:none; }</style>
	<?php
	$btn_uso="&#9660 Apri dosi e usi";
}

// CONTROLLO QUANDO è StATO FATTO L'ULTIMO AGGIORNAMENTO del db fitofarmaci
if (isset($_POST['nomefito'])){
	$query="SELECT UPDATE_TIME FROM information_schema.tables WHERE TABLE_SCHEMA = '".$Database."' AND TABLE_NAME = '".$gTables['camp_fitofarmaci']."'";
	$result = gaz_dbi_query($query);
		while ($row = $result->fetch_assoc()) {
			 $update=strtotime($row['UPDATE_TIME']);
			}
	// 1 giorno è 24*60*60=86400 - 30 giorni 30*86400=2592000
		if (intval($update)+2592000<$today){$msg['err'][]= 'updatedb';}
}

if (isset($_POST['nomefito']) && strlen($form['nomefito'])>3){
		$query="SELECT ".'SCADENZA_AUTORIZZAZIONE'.",".'NUMERO_REGISTRAZIONE'.",".'INDICAZIONI_DI_PERICOLO'.",".'DESCRIZIONE_FORMULAZIONE'.",".'SOSTANZE_ATTIVE'.",".'IMPRESA'.",".'SEDE_LEGALE_IMPRESA'." FROM ".$gTables['camp_fitofarmaci']. " WHERE PRODOTTO ='". $form['nomefito']."'";
		$result = gaz_dbi_query($query);
			while ($row = $result->fetch_assoc()) {
				if (isset($row)) {$presente=1;}
			$form['descri']=$row['NUMERO_REGISTRAZIONE']." - ".$row['SOSTANZE_ATTIVE']." ".$row['DESCRIZIONE_FORMULAZIONE'];
			$form['body_text']=$row['SOSTANZE_ATTIVE']." ".$row['IMPRESA']." ".$row['SEDE_LEGALE_IMPRESA'];
			$indper=$row['INDICAZIONI_DI_PERICOLO'];
			$scadaut=strtotime(str_replace('/', '-', $row['SCADENZA_AUTORIZZAZIONE']));

			}
		if ($presente==1) { // se trovato nel database fitofarmaci

			if ($scadaut<1) {$msg['err'][] ='revocato';}
			// estraggo il simbolo della classe tossicologica

			$cltoss=$indper;
			if ($cltoss<>"") { $form['classif_amb']=0;
				if (stripos($cltoss,"IRRITANTE") !== false) {$form['classif_amb']=1;}
				if (stripos($cltoss,"NOCIVO") !== false) {$form['classif_amb']=2;}
				if (stripos($cltoss,"TOSSICO") !== false) {$form['classif_amb']=3;}
				if (stripos($cltoss,"MOLTO TOSSICO") !== false) {$form['classif_amb']=4;}
				if ($form['classif_amb']==0) {
					if (stripos($cltoss,"PERICOLOSO") !== false) {$form['classif_amb']=5;}
				}
			}
		}
}
// controllo se è scaduta l'autorizzazione fitofarmaco e avviso
if ($form['conferma']<>"Confermo deroga ".$form['nomefito'] AND $presente==1 AND ($scadaut>0 && $today>$scadaut)) {
	// 1 giorno è 24*60*60=86400
	if ($today-$scadaut> 31536000 OR $form['conferma']=="Non voglio usare ".$form['nomefito']){ // se è scaduto da più di un anno segnalo e blocco
		$msg['err'][] ='scaduto';
	} else { // altrimenti segnalo e faccio scegliere
		echo "<script type='text/javascript'> $(window).on('load',(function(){ $('#scadaut').modal('show'); }); </script>";
	}
}

?>
<script type="text/javascript">
    function calcDiscount() {
        var p1 = ($("#preve1").val() * (1 - $("#sconto").val() / 100)).toFixed(<?php echo $admin_aziend['decimal_price']; ?>);
        $("#preve1_sc").val(p1);
        var p2 = ($("#preve2").val() * (1 - $("#sconto").val() / 100)).toFixed(<?php echo $admin_aziend['decimal_price']; ?>);
        $("#preve2_sc").val(p2);
        var p3 = ($("#preve3").val() * (1 - $("#sconto").val() / 100)).toFixed(<?php echo $admin_aziend['decimal_price']; ?>);
        $("#preve3_sc").val(p3);
        var p4 = ($("#preve4").val() * (1 - $("#sconto").val() / 100)).toFixed(<?php echo $admin_aziend['decimal_price']; ?>);
        $("#preve4_sc").val(p4);
    }

	function itemErase(id,avver,colt,cod,upd){
		$(".dose_specifica").append('ID:'+id+' - '+colt+' - '+avver);
		//alert(id);
		$("#confirm_erase").dialog({
			modal: true,
			show: "blind",
			hide: "explode",
			buttons: {
				No: function() {
					$(".dose_specifica").empty();
					$( this ).dialog( "close" );
				},
				Togli: function() {
					document.getElementById('nomefito').value=cod;
					document.getElementById('delete').value=id;
					$('#add-product').submit();
				}
			  },
			  close: function(){
				$(".dose_specifica").empty();
			  }
			});
	}

    $(function () {
        $("#preve1,#preve2,#preve3,#preve4,#sconto").change(function () {
            var v = $(this).val().replace(/,/, '.');
            $(this).val(v);
            calcDiscount();
        });
    });

<!-- Antonio Germani inizio script autocompletamento dalla tabella mysql fitofarmaci	-->
	$(document).ready(function(){
	//Autocomplete search using PHP, MySQLi, Ajax and jQuery
	//generate suggestion on keyup
		$('#nomefito').keyup(function(e){
			e.preventDefault();
			var form = $('#add-product').serialize();
			$.ajax({
				type: 'GET',
				url: 'do_search.php',
				data: form,
				dataType: 'json',
				success: function(response){
					if(response.error){
						$('#product_search').hide();
					}
					else{
						$('#product_search').show().html(response.data);
					}
				}
			});
		});
		//fill the input
		$(document).on('click', '.dropdown-item', function(e){
			e.preventDefault();
			$('#product_search').hide();
			var fullname = $(this).data('fullname');
			$('#nomefito').val(fullname);
			$('#add-product').submit();
		});
	});
<!-- fine autocompletamento -->

<!-- inizio autocompletamento fornitore-->
$(document).ready(function() {
$("input#autocomplete2").autocomplete({
	source: [<?php
$stringa="";
$query="SELECT * FROM ".$gTables['clfoco']." WHERE codice > 212000001 AND codice < 213000000";
$result = gaz_dbi_query($query);
while($row = $result->fetch_assoc()){
	$stringa.="\"".$row['codice']." - ".$row['descri']."\", ";
}
$stringa=substr($stringa,0,-1);
echo $stringa;
?>],
	minLength:1,
select: function(event, ui) {
	//assign value back to the form element
	if(ui.item){
		$(event.target).val(ui.item.value);
	}
	//submit the form
	$(event.target.form).submit();
}
});
});
<!-- fine autocompletamento -->
</script>

<form method="POST" name="form" enctype="multipart/form-data" id="add-product">

	<?php
	if (!empty($form['descri'])) $form['descri'] = htmlentities($form['descri'], ENT_QUOTES);
	echo '<input type="hidden" name="ritorno" value="' . $form['ritorno'] . '" />';
	echo '<input type="hidden" name="ref_code" value="' . $form['ref_code'] . '" />';
	echo '<input type="hidden" name="id_reg" value="' . $form['id_reg'] . '" />';
	echo '<input type="hidden" name="oldnomefito" value="' . $form['nomefito'] . '" />';
	echo '<input type="hidden" name="conferma" value="' . $form['conferma'] . '" />';
	echo '<input type="hidden" id="delete" name="delete" value="" />';
	echo '<input type="hidden" name="' . ucfirst($toDo) . '" value="" />';
		if ($form['good_or_service']==0) {
			$mv = $gForm->getStockValue(false, $form['codice']);
			if (isset($mv)){
				$magval = array_pop($mv);
			} else {
				$magval['q_g']=0;
				$magval['v_g']=0;
			}
		} else {
			$magval['q_g']=0;
			$magval['v_g']=0;
		}


		if (count($msg['err']) > 0) { // ho un errore
			$gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
		}
		if (isset($_SESSION['ok_ins'])){
			$gForm->toast('L\'articolo ' . $_SESSION['ok_ins'].' è stato inserito con successo, sotto per modificarlo. Oppure puoi: <a class="btn btn-info" href="camp_admin_artico.php?Insert">Inserire uno nuovo articolo</a> ' , 'alert-last-row', 'alert-success');
			unset($_SESSION['ok_ins']);
		}

		if (isset($checkdbfito) AND $checkdbfito == "WARNING"){ // se non c'è bisogna creare il data base fitofarmaci
			?>
			<div class="alert alert-warning alert-dismissible" style="max-width: 70%; margin-left: 15%;">
				<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
				<strong>Warning!</strong> Il database Fitofarmaci non esiste. E' necessario crearlo <a  href="javascript:Popup('../../modules/camp/update_fitofarmaci.php')"> Crea database Fitofarmaci <i class="glyphicon glyphicon-import" style="color:green" ></i></a>
			</div>
			<?php
		}
		$cl_home="active";$cl_variant="";
		?>

		<div class="panel panel-default gaz-table-form div-bordered">
			<div class="container-fluid">
				<ul class="nav nav-pills">
					<li class="active"><a data-toggle="pill" href="#home">DATI GENERICI</a></li>
					<li><a data-toggle="pill" href="#car">CARATTERISTICHE</a></li>
					<li><a data-toggle="pill" href="#sian">SIAN</a></li>
					<li><a data-toggle="pill" href="#mag">MAGAZZINO/CONTABILITà</a></li>

					<li style="float: right;"><?php echo '<input name="Submit" type="submit" class="btn btn-warning" value="' . ucfirst($script_transl[$toDo]) . '" />'; ?></li>
				</ul>
				<?php

				if ($toDo == 'insert') {
					echo '<div align="center" class="lead"><h1>' . $script_transl['ins_this'] . '</h1></div>';
				} else {
					echo '<div align="center" class="lead"><h1>' . $script_transl['upd_this'] . ' ' . $form['codice'] . '</h1></div>';
				}

				?>
				<div class="tab-content">

<!-- DATI GENERICI TAB  -->
					<div id="home" class="tab-pane fade in active">
						<!-- Modal content scadenza autorizzazione  -->
						<div id="scadaut" class="modal fade" role="dialog">
							<div class="modal-dialog modal-content">
								<div class="modal-header" align="left">

									<h4 class="modal-title">ATTENZIONE !</h4>
								</div>
								<div class="modal-body">
									<p>Hai scelto un fitofarmaco con autorizzazione scaduta. <br>Puoi usarlo solo se sei a conoscenza che c'è una deroga. <br>Sei sicuro di volerlo fare?</p>
								</div>
								<div class="modal-footer">
									<input type="submit" class="btn btn-default pull-left" name="conferma"  value="Non voglio usare <?php echo $form['nomefito']; ?>">
									<input type="submit" class="btn btn-danger pull-right" name="conferma"  value="Confermo deroga <?php echo $form['nomefito']; ?>">
								</div>
							</div>
						</div>

						<div class="row bg-info">
							<div class="col-md-12">

								<div class="form-group">
									<label for="nomefito" class="col-sm-4 control-label">
									<span data-toggle="popover" title="Inserimento fitofarmaco"
									data-content="Per connettere un fitofarmaco al database del Ministero della salute, inserire il nome commerciale del prodotto e confermarlo nella tendina che appare. <br>Altrimenti lasciare vuoto il campo fitofarmaco e scrivere solo il codice."
									class="glyphicon glyphicon-info-sign" style="cursor: pointer;">
									</span>
									<?php echo "Fitofarmaco"; ?></label>
									<input class="col-sm-6" type="text" id="nomefito" name="nomefito" value="<?php echo $form['nomefito']; ?>" placeholder="Ricerca nome fitofarmaco" autocomplete="off" tabindex="1">
									<?php
									if ($form['nomefito']){
										?>
										<input class="col-sm-2" title="Aggiungi dosi per colture specifiche" type="button" name="button1" id="nextbt" rel="more" value="<?php echo $btn_uso;?>" onclick="buttonToggle(this,'&#9660 Apri dosi e usi','&#9650 Chiudi');" style="float: right;">
										<?php
									}
									?>
								</div>
								<ul class="dropdown-menu" style="left: 35%; padding: 0px;" id="product_search"></ul>
							</div>
		<!-- INSERIMENTO DOSI PER COLTURE SPECIFICHE -->
							<?php if ($form['nomefito']){ ?>
							<div id="more" class="col-sm-12 bg-info">
								<div  align="center" >
									<h3>Inserimento dosi per colture specifiche</h3>
								</div>

								<?php $color='eeeeee';
								echo '<ul class="col-sm-12">';
								$v=0;
								if ($toDo == 'insert') {
									$upd="insert";
								} else {
									$upd="update";
								}
								if (isset($res_usofito)){
									if (isset($form['unimis'])){
										$unimis = $form['unimis']."/ha";
									} else {
										$unimis = "un. mis./ha";
									}
								while ($usofito = $res_usofito->fetch_assoc()) {

									echo '<div style="background-color: #'.$color.'">
									<a class="btn btn-xs btn-success" onclick="window.open(\'admin_usofito.php?Update&id='. $usofito['id']. '\', \'newwindow\',\'width=1000,height=700\'); return false;" target="_blank">'.$usofito['id'].'</a> - '.$usofito['numero_registrazione'].' - '.$usofito['id_colt'].$usofito['nome_colt'].' - '.$usofito['id_avv'].$usofito['nome_avv'].' - Dose:'.$usofito['dose'].$unimis.' - Sospensione:'.$usofito['tempo_sosp'].'gg - Trattamenti per coltura:'.$usofito['max_tratt'].' ';
									if (intval($res_usofito->num_rows)>0){
										?>
										<a style="float:right;" class="btn btn-xs btn-danger" onclick="itemErase('<?php echo addslashes($usofito['id']); ?>', '<?php echo addslashes($usofito['nome_avv']);?>', '<?php echo addslashes($usofito['nome_colt']);?>', '<?php echo $form['nomefito'];?>', '<?php echo $upd; ?>');">  togli X </a>
										<?php
									}
									echo '</div>';
									$color=($color=='fcfcfc')?'eeeeee':'fcfcfc';
									echo '<input type="hidden" name="usofito['.$v.']" value="' . $usofito['cod_art'] . '" />';
									$v++;
								}
								}
								?>
								</ul>
								<div class="col-sm-12 bg-warning">
									<div class="row">
										<h4>Aggiungi una nuova dose massima specifica:</h4>
									</div>
									<!-- Antonio Germani inizio script autocompletamento dalla tabella mysql camp_coltura	-->
									  <script>
										$(document).ready(function() {
										$("input#autocomplete2").autocomplete({
											source: [<?php
										$stringa="";
										$query="SELECT * FROM ".$gTables['camp_colture'];
										$result = gaz_dbi_query($query);
										while($row = $result->fetch_assoc()){
											$stringa.="\"".$row['id_colt']." - ".$row['nome_colt']."\", ";
										}
										$stringa=substr($stringa,0,-2);
										echo $stringa;
										?>],
											minLength:2,
										select: function(event, ui) {
											//assign value back to the form element
											if(ui.item){
												$(event.target).val(ui.item.value);
											}
											//submit the form
											//$(event.target.form).submit();
										}
										});
										});
									  </script>
									 <!-- fine autocompletamento -->
									<div class="row">
										<b>Coltura:</b>
										<input id="autocomplete2" type="text" value="" name="nome_colt" maxlength="50"/>
										<!-- per funzionare autocomplete, id dell'input deve essere autocomplete2 -->

										<!-- inizio inserisci avversita  -->
										<!-- Antonio Germani inizio script autocompletamento dalla tabella mysql camp_avversita	-->
										  <script>
											$(document).ready(function() {
											$("input#autocomplete3").autocomplete({
												source: [<?php
											$stringa="";
											$query="SELECT * FROM ".$gTables['camp_avversita'];
											$result = gaz_dbi_query($query);
											while($row = $result->fetch_assoc()){
												$stringa.="\"".$row['id_avv']." - ".$row['nome_avv']."\", ";
											}
											$stringa=substr($stringa,0,-2);
											echo $stringa;
											?>],
												minLength:2,
											select: function(event, ui) {
												//assign value back to the form element
												if(ui.item){
													$(event.target).val(ui.item.value);
												}
												//submit the form
												//$(event.target.form).submit();
											}
											});
											});
										  </script>
										 <!-- fine autocompletamento -->
										<b> Avversità:</b>
										<input id="autocomplete3" type="text" value="" name="nome_avv" maxlength="50"/>
										<!-- per funzionare autocomplete, id dell'input deve essere autocomplete3 -->

									</div>
									<div class="row">
										<b>Dose:</b>
										<input type="text" name="dose" value="" maxlength="8"  />
										<?php
										echo $unimis;
										?>
										<b> Tempo sospensione:</b>
										<input type="text" name="tempo_sosp" value="" maxlength="2"  /> gg
									</div>
									<div class="row">
										<b>Numero massimo di trattamenti per coltivazione:</b>
										<input type="number" name="max_tratt" value="" maxlength="8" />
									</div>
									<div class="row">
										<input type="submit" class="btn btn-warning" name="OKsub" value="Salva dose">
									</div>
								</div>


							</div>
							<?php } ?>
		<!-- FINE INSERIMENTO DOSI PER COLTURE SPECIFICHE -->

						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="codice" class="col-sm-4 control-label"><?php echo $script_transl['codice']; ?></label>
									<input class="col-sm-8" type="text" value="<?php echo $form['codice']; ?>" name="codice" maxlength="255" tabindex="2">
								</div>
							</div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="descri" class="col-sm-4 control-label"><?php echo $script_transl['descri']; ?></label>
									<input class="col-sm-8" type="text" value="<?php echo $form['descri']; ?>" name="descri" maxlength="255" tabindex="3">
								</div>
							</div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for = "good_or_service" class = "col-sm-4 control-label"><?php echo $script_transl['good_or_service']; ?>*</label>
									<?php
									$gForm->variousSelect('good_or_service', $script_transl['good_or_service_value'], $form['good_or_service'], "col-sm-8", true, '', false, 'tabindex="4" onchange = "this.form.submit();" style = "max-width: 200px;"');
									?>
								</div>
							</div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="body_text" class="col-sm-4 control-label"><?php echo $script_transl['body_text']; ?></label>
									<div class="col-sm-8">
									<!-- tabindex con editor mce non funziona perché TinyMCE crea un iframe! -->
										<textarea tabindex="5" id="body_text" name="body_text" class="mceClass" ><?php echo $form['body_text']; ?></textarea>
									</div>
								</div>
							</div>
						</div><!-- chiude row  -->

						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="lot_or_serial" class="col-sm-4 control-label"><?php echo $script_transl['lot_or_serial'] . ' (' . $admin_aziend['ritenuta'] . '%)'; ?></label>
									<?php
									$gForm->variousSelect('lot_or_serial', $script_transl['lot_or_serial_value'], $form['lot_or_serial'], "col-sm-8", true, '', false, 'tabindex="6" style="max-width: 200px;"');
									?>
								</div>
							</div>
						</div><!-- chiude row  -->

						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="image" class="col-sm-4 control-label"><img src="../root/view.php?table=artico&value=<?php echo $form['codice']; ?>" width="100" >*</label>

									<div class="col-sm-8"><?php echo $script_transl['image']; ?><input tabindex="7" type="file" name="userfile" /></div>
								</div>
							</div>
						</div><!-- chiude row  -->

						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="unimis" class="col-sm-4 control-label"><?php echo $script_transl['unimis']; ?></label>
								 <!--   <input class="col-sm-2" type="text" value="<?php echo $form['unimis']; ?>" name="unimis" maxlength="6" tabindex="2"> -->
								 <?php if ($form['good_or_service']==0){?>
										<select tabindex="8" name="unimis">
											<option <?php if($form['unimis'] == 'Kg'){echo("selected");}?>>Kg</option>
											<option <?php if($form['unimis'] == 'l'){echo("selected");}?>>l</option>
											<option <?php if($form['unimis'] == 'n'){echo("selected");}?>>n</option>
										</select>
								 <?php } else { ?>
									 <select tabindex="8" name="unimis">
											<option <?php if($form['unimis'] == 'h'){echo("selected");}?>>h</option>
											<option <?php if($form['unimis'] == 'n'){echo("selected");}?>>n</option>
											<option <?php if($form['unimis'] == 'ha'){echo("selected");}?>>ha</option>
											<option <?php if($form['unimis'] == 'l'){echo("selected");}?>>l</option>
										</select>
								<?php } ?>
								</div>
							</div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="catmer" class="col-sm-4 control-label"><?php echo $script_transl['catmer']; ?></label>
									<?php
									$gForm->selectFromDB('catmer', 'catmer', 'codice', $form['catmer'], false, 1, ' - ', 'descri', '', 'col-sm-8', null, 'tabindex="18" style="max-width: 250px;"');
									?>
								</div>
							</div>
						</div><!-- chiude row  -->
						<?php if ($toDo == "insert") {$form['mostra_qdc']=1;}  ?> <!-- se inserito da qdc deve essere di default un articolo del qdc  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
								<label for="mostra_qdc" class="col-sm-4 control-label"><?php echo $script_transl['mostra_qdc']; ?></label>
									<input tabindex="9" type="radio" name="mostra_qdc" value="1" <?php if ($form['mostra_qdc']==1){echo "checked";}?> > Sì <br>
									<input type="radio" name="mostra_qdc" value="0" <?php if ($form['mostra_qdc']==0){echo "checked";}?> > No
							   </div>
						   </div>
						</div><!-- chiude row  -->
					</div><!-- chiude pane  -->

<!-- CARATTERISTICHE TAB  -->
					<div id="car" class="tab-pane fade">
						<?php if ($form['good_or_service']==0 OR $form['good_or_service']==2){ ?>
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
									<label for="classif_amb" class="col-sm-4 control-label"><?php echo $script_transl['classif_amb']; ?></label>
								<?php
								$gForm->variousSelect('classif_amb', $script_transl['classif_amb_value'], $form['classif_amb'], "col-sm-8", false, '', false, 'tabindex="19" style="max-width: 200px;"');
								?>
								   </div>
							   </div>
							</div><!-- chiude row  -->
							<!-- Antonio Germani  il TEMPO DI SOSPENSIONE -->
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<label for="tempo_sospensione" class="col-sm-4 control-label"><?php echo $script_transl['tempo_sospensione']; ?></label>
										<input tabindex="24" class="col-sm-4" type="number" min="0" step="any" value="<?php echo $form['tempo_sospensione']; ?>" name="tempo_sospensione" maxlength="13" />
									</div>
								</div>
							</div><!-- chiude row  -->
							<!-- Antonio Germani  la DOSE AD ETTARO  -->
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<label for="dose_massima" class="col-sm-4 control-label"><?php echo $script_transl['dose_ha']; ?></label>
										<input tabindex="25" class="col-sm-4" type="number" min="0" step="any" value="<?php echo $form['dose_massima']; ?>" name="dose_massima" maxlength="13" />
									</div>
								</div>
							</div><!-- chiude row  -->
							<!-- Antonio Germani  il RAME METALLO e N P K -->
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<label for="rame_metallico" class="col-sm-4 control-label"><?php echo $script_transl['rame_metallico']; ?></label>
										<input tabindex="26" class="col-sm-4" type="number" min="0" step="any" value="<?php echo $form['rame_metallico']; ?>" name="rame_metallico" maxlength="13" />
									</div>
								</div>
							</div><!-- chiude row  -->
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<label for="perc_N" class="col-sm-4 control-label"><?php echo $script_transl['perc_N']; ?></label>
										<input tabindex="27" class="col-sm-4" type="number" min="0" step="any" value="<?php echo $form['perc_N']; ?>" name="perc_N" maxlength="3" />
									</div>
								</div>
							</div><!-- chiude row  -->
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<label for="perc_P" class="col-sm-4 control-label"><?php echo $script_transl['perc_P']; ?></label>
										<input tabindex="28" class="col-sm-4" type="number" min="0" step="any" value="<?php echo $form['perc_P']; ?>" name="perc_P" maxlength="3" />
									</div>
								</div>
							</div><!-- chiude row  -->
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<label for="perc_K" class="col-sm-4 control-label"><?php echo $script_transl['perc_K']; ?></label>
										<input tabindex="29" class="col-sm-4" type="number" min="0" step="any" value="<?php echo $form['perc_K']; ?>" name="perc_K" maxlength="3" />
									</div>
								</div>
							</div><!-- chiude row  -->
							<?php if ($toDo == 'update') { ?>
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label for="annota" class="col-sm-4 control-label"><?php echo $script_transl['document']; ?></label>
											<?php if ($ndoc > 0) { // se ho dei documenti  ?>

												<?php foreach ($form['rows'] as $k => $val) { ?>
												<div>
														<input type="hidden" value="<?php echo $val['id_doc']; ?>" name="rows[<?php echo $k; ?>][id_doc]">
														<input type="hidden" value="<?php echo $val['extension']; ?>" name="rows[<?php echo $k; ?>][extension]">
														<input type="hidden" value="<?php echo $val['title']; ?>" name="rows[<?php echo $k; ?>][title]">

														<a href="../root/retrieve.php?id_doc=<?php echo $val["id_doc"]; ?>" title="<?php echo $script_transl['view']," o Scarica: ", DATA_DIR . 'files/' . $val['id_doc'] . '.' . $val['extension']; ?>" class="btn btn-default btn-sm">
															<i class="glyphicon glyphicon-file"></i>
														</a><?php echo " ",$val['title']; ?>
														<input tabindex="30" type="button" value="<?php echo ucfirst($script_transl['update']); ?>" onclick="location.href = 'admin_document.php?id_doc=<?php echo $val['id_doc']; ?>&Update'" />
												</div>
												<?php } ?>
													<input tabindex="31" type="button" value="<?php echo ucfirst($script_transl['insert']); ?>" onclick="location.href = 'admin_document.php?item_ref=<?php echo $form['codice']; ?>&Insert'" />

											<?php } else { // non ho documenti  ?>
												<input tabindex="31" type="button" value="<?php echo ucfirst($script_transl['insert']); ?>" onclick="location.href = 'admin_document.php?item_ref=<?php echo $form['codice']; ?>&Insert'">
											<?php } ?>
										</div>
									</div>
								</div>
							<?php } else { ?>
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
										Dopo l'inserimento sarà possibile iniziare a caricare la scheda di sicurezza e i documenti da allegare
										</div>
									</div>
								</div>
							<?php } ?>
						<?php } else { ?>
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
								Hai selezionato una lavorazione o servizio
								<input type="hidden" name="classif_amb" value="" />
								<input type="hidden" name="tempo_sospensione" value="" />
								<input type="hidden" name="dose_massima" value="" />
								<input type="hidden" name="rame_metallico" value="" />
								<input type="hidden" name="perc_N" value="" />
								<input type="hidden" name="perc_K" value="" />
								<input type="hidden" name="perc_P" value="" />
								</div>
							</div>
						</div>
						<?php } ?>

					</div><!-- chiude pane  -->

<!-- SIAN TAB  -->
					<div id="sian" class="tab-pane fade">
					<?php if ($form['good_or_service']==0 OR $form['good_or_service']==2){?>
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
								<label for="SIAN" class="col-sm-4 control-label"><?php echo $script_transl['SIAN']; ?></label>
									<input tabindex="10" type="radio" name="SIAN" value="0" <?php if ($form['SIAN']==0){echo "checked";}?> > NO <br>
									<input type="radio" name="SIAN" value="1" <?php if ($form['SIAN']==1){echo "checked";}?> > Olio
									<input type="radio" name="SIAN" value="2" <?php if ($form['SIAN']==2){echo "checked";}?> > Olive
							   </div>
						   </div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for = "or_macro" class = "col-sm-4 control-label"><?php echo $script_transl['or_macro']; ?></label>
									<?php
									$gForm->variousSelect('or_macro', $script_transl['or_macro_value'], $form['or_macro'], "col-sm-8", true, '', false, 'tabindex="11" style = "max-width: 200px;"');
									?>
								</div>
							</div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for = "or_spec" class = "col-sm-4 control-label"><?php echo $script_transl['or_spec']; ?></label>
									<?php
									$gForm->variousSelect('or_spec', $script_transl['or_spec_value'], $form['or_spec'], "col-sm-8", true, '', false, 'tabindex="12" style = "max-width: 200px;"');
									?>
								</div>
							</div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
								<label for="estrazione" class="col-sm-4 control-label"><?php echo $script_transl['estrazione']; ?></label>
									<input tabindex="13" type="radio" name="estrazione" value="0" <?php if ($form['estrazione']==0){echo "checked";}?> > null <br>
									<input type="radio" name="estrazione" value="1" <?php if ($form['estrazione']==1){echo "checked";}?> > Prima spremitura a freddo
									<input type="radio" name="estrazione" value="2" <?php if ($form['estrazione']==2){echo "checked";}?> > Estratto a freddo
							   </div>
						   </div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
								<label for="biologico" class="col-sm-4 control-label"><?php echo $script_transl['biologico']; ?></label>
									<input tabindex="14" type="radio" name="biologico" value="0" <?php if ($form['biologico']==0){echo "checked";}?> > Convenzionale <br>
									<input type="radio" name="biologico" value="1" <?php if ($form['biologico']==1){echo "checked";}?> > Biologico
									<input type="radio" name="biologico" value="2" <?php if ($form['biologico']==2){echo "checked";}?> > In conversione
							   </div>
						   </div>
						</div><!-- chiude row  -->
						<div class="row">
						<div class="col-md-12">
							<div class="form-group">
							<label for="etichetta" class="col-sm-4 control-label"><?php echo $script_transl['etichetta']; ?></label>
								<input tabindex="15" type="radio" name="etichetta" value="0" <?php if ($form['etichetta']==0){echo "checked";}?> > Non etichettato <br>
								<input type="radio" name="etichetta" value="1" <?php if ($form['etichetta']==1){echo "checked";}?> > Etichettato
							</div>
						</div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for = "categoria" class = "col-sm-4 control-label"><?php echo $script_transl['categoria']; ?></label>
									<?php
									$gForm->variousSelect('categoria', $script_transl['categoria_value'], $form['categoria'], "col-sm-8", true, '', false, 'tabindex="16" style = "max-width: 200px;"');
									?>
								</div>
							</div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="confezione" class="col-sm-4 control-label"><?php echo $script_transl['confezione']; ?></label>
									<input tabindex="17" class="col-sm-2" type="number" step="any" min="0" value="<?php echo $form['confezione']; ?>" name="confezione" maxlength="15" /> (se sfuso indicare 0)
								</div>
							</div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="varieta" class="col-sm-4 control-label">Varietà (da utilizzare per i monovarietali)</label>
									<input tabindex="18" class="col-sm-2" type="text" value="<?php echo $form['quality']; ?>" name="quality" maxlength="15" />
								</div>
							</div>
						</div><!-- chiude row  -->
						<?php } else { ?>
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
								Hai selezionato una lavorazione o servizio
								<input type="hidden" name="SIAN" value="" />
								<input type="hidden" name="or_macro" value="" />
								<input type="hidden" name="or_spec" value="" />
								<input type="hidden" name="estrazione" value="" />
								<input type="hidden" name="biologico" value="" />
								<input type="hidden" name="etichetta" value="" />
								<input type="hidden" name="categoria" value="" />
								<input type="hidden" name="confezione" value="" />
								<input type="hidden" name="quality" value="" />
								</div>
							</div>
						</div>
						<?php } ?>
					</div><!-- chiude pane -->

<!-- MAGAZZINO TAB  -->
					<div id="mag" class="tab-pane fade">
						<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="preacq" class="col-sm-4 control-label"><?php echo $script_transl['preacq']; ?> di acquisto</label>
								<input tabindex="20" class="col-sm-4" type="number" step="any" min="0" value="<?php echo $form['preacq']; ?>" name="preacq" maxlength="15" />
							</div>
						</div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="aliiva" class="col-sm-4 control-label"><?php echo $script_transl['aliiva']; ?></label>
									<?php
									$gForm->selectFromDB('aliiva', 'aliiva', 'codice', $form['aliiva'], 'codice', 0, ' - ', 'descri', '', 'col-sm-8', null, 'tabindex="21" style="max-width: 350px;"');
									?>
								</div>
							</div>
						</div><!-- chiude row  -->
					<?php if ($form['good_or_service']==0 OR $form['good_or_service']==2){?>
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="esiste" class="col-sm-4 control-label"><?php echo $script_transl['esiste']; ?></label>
									<div class="col-sm-2"><?php echo gaz_format_quantity(($magval)?$magval['q_g']:0,1,$admin_aziend['decimal_quantity']); ?></div>
								</div>
							</div>
						</div><!-- chiude row  -->

						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="scorta" class="col-sm-4 control-label"><?php echo $script_transl['scorta']; ?></label>
									<input tabindex="22" class="col-sm-4" type="number" min="0" step="any" value="<?php echo $form['scorta']; ?>" name="scorta" maxlength="13" />
								</div>
							</div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="riordino" class="col-sm-4 control-label"><?php echo $script_transl['riordino']; ?></label>
									<input tabindex="23" type="text" min="0" step="any" class="col-sm-4" value="<?php echo $form['riordino']; ?>" name="riordino" maxlength="13" />
								</div>
							</div>
						</div><!-- chiude row  -->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="id_cost" class="col-sm-4 control-label"><?php echo $script_transl['id_anagra']; ?></label>
									<input tabindex="32" class="col-sm-4" id="autocomplete2" type="text" value="<?php echo $form['fornitore']; ?>" name="fornitore" maxlength="15" /> <!-- per funzionare autocomplete2, id dell'input deve essere autocomplete -->
								</div>
							</div>
						</div><!-- chiude row  -->

					<?php } else { ?>
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
								Hai selezionato una lavorazione o servizio che non ha bisogno di magazzino
								<input type="hidden" name="scorta" value="" />
								<input type="hidden" name="riordino" value="" />
								<input type="hidden" name="fornitore" value="" />

								</div>
							</div>
						</div>
					<?php } ?>
					</div><!-- chiude pane -->
				</div><!-- chiude tab content -->

				<div class="col-sm-12">
					<?php
					echo '<div class="col-sm-12 text-center"><input tabindex="34" name="Submit" type="submit" class="btn btn-warning" value="' . ucfirst($script_transl[$toDo]) . '" /></div>';
					?>
				</div>

			</div><!-- chiude container -->
		</div>	<!-- chiude panel -->
</form>
<div class="modal" id="confirm_erase" title="Togli dose specifica">
    <fieldset>
       <div class="dose_specifica"></div>
    </fieldset>
</div>
<?php
if (isset($formuso) OR $openmore==true){
	echo "<script type='text/javascript'>\n" . "window.location.hash = '#more';" . //◄■■■ JUMP TO LOCAL ANCHOR.
	"</script>\n";
}
?>
<script type="text/javascript">
	function buttonToggle(where, pval, nval) {
		var table = document.getElementById(where.attributes.rel.value);
		if (where.value == pval){
			where.value = nval;
			table.style.display = 'unset';
		} else if (where.value == nval) {
			where.value = pval;
			table.style.display = 'none';
		}
	}
	<!-- script per popup -->
	var stile = "top=10, left=10, width=600, height=800, status=no, menubar=no, toolbar=no, scrollbar=no";
	function Popup(apri) {
	  window.open(apri, "", stile);
	}
	<!-- script per popover -->
	$(document).ready(function(){
		$('[data-toggle="popover"]').popover({
			html: true
		});
	});
</script>
<!---->
<style>
.ui-dialog{
	position:fixed;
	top: 30% !important;

}
</style>
<?php
require("../../library/include/footer.php");
?>
