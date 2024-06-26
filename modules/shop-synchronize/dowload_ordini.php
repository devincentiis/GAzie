<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2023 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  SHOP SYNCHRONIZE è un modulo creato per GAzie da Antonio Germani, Massignano AP
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

$period="";
$resserver = gaz_dbi_get_row($gTables['company_config'], "var", "server");
$ftp_host= $resserver['val'];

$OSaccpass = gaz_dbi_get_row($gTables['company_config'], "var", "accpass")['val'];// vecchio sistema di password non criptata
$rsdec=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'accpass'");
$rdec=gaz_dbi_fetch_row($rsdec);
$accpass=$rdec[0]?htmlspecialchars_decode($rdec[0]):'';
$accpass=(strlen($accpass)>0)?$accpass:$OSaccpass; // se la password decriptata non ha dato risultati provo a mettere la password non criptata

$path = gaz_dbi_get_row($gTables['company_config'], 'var', 'path');
$urlinterf = $path['val']."ordini-gazie.php";//nome del file interfaccia presente nella root del sito Joomla. Per evitare intrusioni indesiderate Il file dovrà gestire anche una password.
// il percorso per raggiungere questo file va impostato in configurazione avanzata azienda alla voce "Website root directory"
if (isset($_POST['Return'])) {
        header("Location: " . "./synchronize.php");
        exit;
    }
$test = gaz_dbi_query("SHOW COLUMNS FROM `" . $gTables['admin'] . "` LIKE 'enterprise_id'");
$exists = (gaz_dbi_num_rows($test)) ? TRUE : FALSE;
if ($exists) {
    $c_e = 'enterprise_id';
} else {
    $c_e = 'company_id';
}
$admin_aziend = gaz_dbi_get_row($gTables['admin'] . ' LEFT JOIN ' . $gTables['aziend'] . ' ON ' . $gTables['admin'] . '.' . $c_e . '= ' . $gTables['aziend'] . '.codice', "user_name", $_SESSION['user_name']);

if (isset($_POST['conferma']) && isset($_POST['num_orders'])) { // se confermato e ci sono ordini

	$numdoc=""; $year="";
    // scrittura ordini su database di GAzie
	for ($ord=0 ; $ord<=$_POST['num_orders']; $ord++){// ciclo gli ordini e li scrivo nel database
    if (isset($_POST['download'.$ord]) ) {
      if ($numdoc=="" and $year==""){// se sono al primo ciclo degli ordini
        // ricavo il progressivo numero d'ordine di GAzie in base al tipo di documento
        $orderdb = "numdoc desc";
        $sql_documento = "YEAR(datemi) = " . substr($_POST['datemi'.$ord],0,4) . " and tipdoc = 'VOW'";
        $rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesbro'], $sql_documento, $orderdb, 0, 1);
        $ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
        // se e' il primo documento dell'anno, resetto il contatore
        if ($ultimo_documento) {
          $numdoc = $ultimo_documento['numdoc'] + 1;
        } else {
          $numdoc = 1;
        }
        $year=substr($_POST['datemi'.$ord],0,4);
      }elseif(intval(substr($_POST['datemi'.$ord],0,4))> intval($year)) {// se è cambiato l'anno durante il ciclo degli ordini e sono nel nuovo anno
        $numdoc = 1;// ricomincio la numerazione
        $year=substr($_POST['datemi'.$ord],0,4);// reimposto year con il nuovo anno
      }

      $query = "SHOW TABLE STATUS LIKE '" . $gTables['anagra'] . "'";
      $result = gaz_dbi_query($query);
      $row = $result->fetch_assoc();
      $id_anagra = $row['Auto_increment']; // trovo l'ID che avrà ANAGRA: Anagrafica cliente

      $anagrafica = new Anagrafica();
      $last = $anagrafica->queryPartners('*', "codice BETWEEN " . $admin_aziend['mascli'] . "000000 AND " . $admin_aziend['mascli'] . "999999", "codice DESC", 0, 1);
      $codice = substr($last[0]['codice'], 3) + 1;
      $clfoco = $admin_aziend['mascli'] * 1000000 + $codice;// trovo il codice di CLFOCO da connettere all'anagrafica cliente	se cliente inesistente

      $listin=intval($_POST['numlist'.$ord]);
      $listinome=$_POST['numlistnome'.$ord];
      $includevat=$_POST['includevat'.$ord];

      $stapre="T"; // stampa prezzi con totale

			$esiste=0;
			if (strlen($_POST['ref_ecommerce_id_customer'.$ord])>0){ // controllo esistenza cliente per codice e-commerce
				unset($cl);
				$cl = gaz_dbi_get_row($gTables['clfoco'], "ref_ecommerce_id_customer", $_POST['ref_ecommerce_id_customer'.$ord]);
				if (isset($cl)){
					$clfoco=$cl['codice'];
					$esiste=1;
				}
			}
			// provo a ricongiungere i pagamenti
			if(strlen($_POST['idpagame'.$ord])>0){//se l'e-commerce ha inviato il suo id di riferimento lo inserisco nella testata
				//provo a ricongiungerlo con GAzie
				$pag = gaz_dbi_get_row($gTables['pagame'], "web_payment_ref", $_POST['idpagame'.$ord]);
				$idpagame=(isset($pag['codice']))?$pag['codice']:0;
			}else{// altrimenti non iserisco alcun pagamento
				$idpagame=0;
			}

			if ($esiste==0) { //registro cliente se non esiste
				if ($_POST['country'.$ord]=="IT"){ // se la nazione è IT
					$lang="1";
          if (substr_compare($_POST['pariva'.$ord], "IT", 0, 2, true)==0){// se c'è IT davanti alla partita iva
            $_POST['pariva'.$ord]=substr($_POST['pariva'.$ord],2);// tolgo IT
          }
          if (strlen($_POST['pariva'.$ord])<>11 && intval($_POST['pariva'.$ord])==0){// se non è una partita iva allora è un privato
              $_POST['pariva'.$ord]=""; // deve essere vuoto
              $_POST['fe_cod_univoco'.$ord] = "0000000";// il codice univoco deve essere 7 volte zero
          }
				}elseif ($_POST['country'.$ord]=="EN") {// se non è italiano imposto il codice univoco con 7 X maiuscolo e se non c'è imposto il codice fiscale con il codice cliente
					$lang="2";
					$_POST['fe_cod_univoco'.$ord]="XXXXXXX";
					if (strlen($_POST['codfis'.$ord])==0 || strlen($_POST['codfis'.$ord])<7){
						$_POST['codfis'.$ord] = sprintf("%07d", $clfoco);// riempio il campo codice fiscale con clfoco di almeno 7 cifre
					}
					if (strlen($_POST['pariva'.$ord])==0 || strlen($_POST['pariva'.$ord])<7){
						$_POST['pariva'.$ord]= sprintf("%07d", $clfoco);// riempio il campo piva con il codice clfoco di almeno 7 cifre
					}
				}elseif ($_POST['country'.$ord]=="ES") {// se non è italiano imposto il codice univoco con 7 X maiuscolo e se non c'è imposto il codice fiscale con il codice cliente
					$lang="3";
					$_POST['fe_cod_univoco'.$ord]="XXXXXXX";
					if (strlen($_POST['codfis'.$ord])==0 || strlen($_POST['codfis'.$ord])<7){
						$_POST['codfis'.$ord] = sprintf("%07d", $clfoco);// riempio il campo codice fiscale con clfoco di almeno 7 cifre
					}
					if (strlen($_POST['pariva'.$ord])==0 || strlen($_POST['pariva'.$ord])<7){
						$_POST['pariva'.$ord]= sprintf("%07d", $clfoco);// riempio il campo piva con il codice clfoco di almeno 7 cifre
					}
				}else {// se non è italiano imposto il codice univoco con 7 X maiuscolo e se non c'è imposto il codice fiscale con il codice cliente
					$lang="2";
					$_POST['fe_cod_univoco'.$ord]="XXXXXXX";
					if (strlen($_POST['codfis'.$ord])==0 || strlen($_POST['codfis'.$ord])<7){
						$_POST['codfis'.$ord] = sprintf("%07d", $clfoco);// riempio il campo codice fiscale con clfoco di almeno 7 cifre
					}
					if (strlen($_POST['pariva'.$ord])==0 || strlen($_POST['pariva'.$ord])<7){
						$_POST['pariva'.$ord]= sprintf("%07d", $clfoco);// riempio il campo piva con il codice clfoco di almeno 7 cifre
					}
				}
				if (strlen ($_POST['codfis'.$ord])==16 && intval ($_POST['codfis'.$ord])==0){ // se il codice fiscale non è numerico
						if (substr($_POST['codfis'.$ord],9,2)>40){ // deduco il sesso
							$sexper="F";
						} else {
							$sexper="M";
						}
				} else {
					$sexper="G";
          if (strlen ($_POST['codfis'.$ord])==0){// se non è stato passato il codice fiscale
            $_POST['codfis'.$ord]="00000000000";// GAzie vuole 11 zeri
          }

				}
				gaz_dbi_query("INSERT INTO " . $gTables['anagra'] . "(ragso1,ragso2,sexper,indspe,capspe,citspe,prospe,country,id_currency,id_language,telefo,codfis,pariva,fe_cod_univoco,e_mail,pec_email) VALUES ('" . addslashes(substr($_POST['ragso1'.$ord], 0, 50)) . "', '" . addslashes(substr($_POST['ragso2'.$ord], 0, 50)) . "', '". $sexper. "', '". addslashes(substr($_POST['indspe'.$ord], 0, 60)) ."', '".substr($_POST['capspe'.$ord], 0, 10)."', '". addslashes(substr($_POST['citspe'.$ord], 0, 60)) ."', '". substr($_POST['prospe'.$ord], 0, 2) ."', '" . substr($_POST['country'.$ord], 0, 3). "', '1', '".$lang."', '". substr($_POST['telefo'.$ord], 0, 50) ."', '". substr(strtoupper($_POST['codfis'.$ord]), 0, 16) ."', '" . substr($_POST['pariva'.$ord], 0, 12) . "', '" . substr($_POST['fe_cod_univoco'.$ord], 0, 7) . "', '". substr($_POST['email'.$ord], 0, 60) . "', '". substr($_POST['pec_email'.$ord], 0, 60) . "')");

				gaz_dbi_query("INSERT INTO " . $gTables['clfoco'] . "(ref_ecommerce_id_customer,codice,id_anagra,listin,descri,destin,speban,stapre,codpag) VALUES ('".$_POST['ref_ecommerce_id_customer'.$ord]."', '". $clfoco . "', '" . $id_anagra . "', '". $listin ."' , '" .addslashes(substr($_POST['ragso1'.$ord], 0, 50))." ".addslashes(substr($_POST['ragso2'.$ord], 0, 49)) . "', '". addslashes(substr($_POST['destin'.$ord], 0, 100)) ."', 'S', '". $stapre ."', '". $idpagame ."')");
			}

			if ($_POST['order_discount_price'.$ord]>0){ // se il sito ha mandato uno sconto totale a valore calcolo lo sconto in percentuale da dare ad ogni rigo
				$lordo=$_POST['order_full_price'.$ord]+$_POST['order_discount_price'.$ord]-$_POST['speban'.$ord]-$_POST['traspo'.$ord];
				$netto=$lordo-$_POST['order_discount_price'.$ord];
				$percdisc= 100-(($netto/$lordo)*100);
			} else {
				$percdisc="";
			}

			if ($_POST['codvatcost'.$ord] == ""){ // se l'e-commerce non ha mandato il codice delle spese incasso  e trasporto
				$expense_vat = $admin_aziend['preeminent_vat']; // ci metto quelle preminenti aziendali
			} else {
				$expense_vat = $_POST['codvatcost'.$ord]; // altrimenti metto il codice che ha mandato
			}
			if ($includevat=="true"){ // se l'e-commerce include l'iva la scorporo alle spese banca e trasporto
				$vat = gaz_dbi_get_row($gTables['aliiva'], "codice", $expense_vat);
				$div="1.".intval($vat['aliquo']);
				$_POST['speban'.$ord]=floatval($_POST['speban'.$ord]) / $div;
				$_POST['traspo'.$ord]=floatval($_POST['traspo'.$ord]) / $div;
			}

			// registro testata ordine
			$tesbro['destin']=chunk_split (substr($_POST['destin'.$ord], 0, 100),44);$tesbro['ref_ecommerce_id_order']=$_POST['ref_ecommerce_id_order'.$ord];$tesbro['tipdoc']='VOW';$tesbro['seziva']=intval($_POST['seziva'.$ord]);$tesbro['print_total']='1';$tesbro['datemi']=$_POST['datemi'.$ord];$tesbro['numdoc']=$numdoc;$tesbro['datfat']='0000-00-00';$tesbro['clfoco']=$clfoco;$tesbro['pagame']=$idpagame;$tesbro['listin']=$listin;$tesbro['spediz']=substr($_POST['spediz'.$ord], 0, 50);$tesbro['traspo']=$_POST['traspo'.$ord];$tesbro['speban']=$_POST['speban'.$ord];$tesbro['caumag']='1';$tesbro['expense_vat']=$expense_vat;$tesbro['initra']=substr($_POST['datemi'.$ord], 0, 19);$tesbro['status']='ONLINE-SHOP';$tesbro['adminid']=$admin_aziend['adminid'];
			$id_tesbro=tesbroInsert($tesbro);

			// Gestione righi ordine
			for ($row=0; $row<=$_POST['num_rows'.$ord]; $row++){

				if ($_POST['type'.$ord.$row] <> "discount"){
					// controllo se esiste l'articolo in GAzie
					$ckart = gaz_dbi_get_row($gTables['artico'], "ref_ecommerce_id_product", $_POST['refid'.$ord.$row]);
					if ($ckart){
						$codart=$ckart['codice']; // se esiste ne prendo il codice come $codart
						$descri=$ckart['descri'].$_POST['adddescri'.$ord.$row];// se esiste, lo metto in $descri e aggiungo l'eventuale adddescription
					}

					if (!$ckart){ // se non esiste, creo un nuovo articolo su gazie
						if ($_POST['stock'.$ord.$row]>0){
							$good_or_service=0;
						} else {
							$good_or_service=1;
						}
						if ($_POST['aliiva'.$ord.$row]==""){ // se il sito non ha mandato l'aliquota IVA dell'articolo ci metto quello che deve mandare come base aziendale riservato alle spese
							$_POST['codvat'.$ord.$row]=$_POST['codvatcost'.$ord];
							$_POST['aliiva'.$ord.$row]=$_POST['aliivacost'.$ord];
						}
						if ($_POST['codvat'.$ord.$row]<1){ // se il sito non ha mandato il codice iva di GAzie cerco di ricavarlo dalla tabella aliiva
							$vat = gaz_dbi_get_row($gTables['aliiva'], "aliquo", $_POST['aliiva'.$ord.$row], " AND tipiva = 'I'");
							$codvat=$vat['codice'];
							$aliiva=$vat['aliquo'];
						} else {
							$codvat=$_POST['codvat'.$ord.$row];
							$aliiva=$_POST['aliiva'.$ord.$row];
						}
						if ($includevat=="true" AND $_POST['prelis_imp'.$ord.$row]==0){ // se l'e-commerce include l'iva e non ha mandato il prezzo imponibile, scorporo l'iva dal prezzo dell'articolo
							$div=0;
							$div="1.".intval($aliiva);
							$prelis=$_POST['prelis_vatinc'.$ord.$row] / $div;
						} elseif ($includevat=="true" AND $_POST['prelis_imp'.$ord.$row]>0) {
							$prelis=$_POST['prelis_imp'.$ord.$row];
						}
						if ($includevat!=="true"){ // se l'ecommerce non iclude l'iva uso il prezzo imponibile
							$prelis=$_POST['prelis_imp'.$ord.$row];
						}

						$id_artico_group="";
						$arrayvar="";
						if ($_POST['product_parent_id'.$ord.$row] > 0 OR $_POST['type'.$ord.$row] == "variant" ){ // se è una variante

							// controllo se esiste il suo artico_group/padre in GAzie
							unset($parent);
							$parent = gaz_dbi_get_row($gTables['artico_group'], "ref_ecommerce_id_main_product", $_POST['product_parent_id'.$ord.$row]);// trovo il padre in GAzie
							if ($parent){ // se esiste il padre
								$id_artico_group=$parent['id_artico_group']; // imposto il riferimento al padre
							} else {// se non esiste lo devo creare con i pochi dati che ho
								$parent['descri']=$_POST['descri'.$ord.$row];
								gaz_dbi_query("INSERT INTO " . $gTables['artico_group'] . "(descri,large_descri,image,web_url,ref_ecommerce_id_main_product,web_public,depli_public,adminid) VALUES ('" . addslashes($parent['descri']) . "', '" . htmlspecialchars_decode (addslashes($parent['descri'])). "', '', '', '". substr($_POST['product_parent_id'.$ord.$row], 0, 50) . "', '1', '1', '". $admin_aziend['adminid'] ."')");
								$id_artico_group=gaz_dbi_last_id(); // imposto il riferimento al padre
							}

							if (strlen($_POST['descri'.$ord.$row])<2){ // se non c'è la descrizione della variante
								$_POST['descri'.$ord.$row]=$parent['descri']."-".$_POST['characteristic'.$ord.$row];// ci metto quella del padre accodandoci la variante
							}

							// creo un json array per la variante
							$arrayvar= array("var_id" => floatval($_POST['characteristic_id'.$ord.$row]), "var_name" => $_POST['characteristic'.$ord.$row]);
							$arrayvar = json_encode ($arrayvar);

						}

						// ricongiungo la categoria dell'e-commerce con quella di GAzie, se esiste
						$category="";
						if (intval($_POST['catmer'.$ord.$row])>0){
							$cat = gaz_dbi_get_row($gTables['catmer'], "ref_ecommerce_id_category", addslashes (substr($_POST['catmer'.$ord.$row],0,15)));// controllo se esiste in GAzie
							if ($cat){
								$category=$cat['codice'];
							}
						}

						// se non esiste la categoria in GAzie, la creo
						if ($category == 0 OR $category == ""){
							$rs_ultimo_codice = gaz_dbi_dyn_query("*", $gTables['catmer'], 1 ,'codice desc',0,1);
							$ultimo_codice = gaz_dbi_fetch_array($rs_ultimo_codice);
							$cat['codice'] = $ultimo_codice['codice']+1;
							$cat['ref_ecommerce_id_category'] = substr($_POST['catmer'.$ord.$row], 0, 50);
							$cat['descri'] = substr($_POST['catmer_descri'.$ord.$row], 0, 50);
							gaz_dbi_table_insert('catmer',$cat);
							// assegno l'id categoria al prossimo insert artico
							$category=$cat['codice'];
						}

						// prima di inserire il nuovo articolo controllo se il suo codice è stato già usato
						unset($usato);
						$usato = gaz_dbi_get_row($gTables['artico'], "codice", substr($_POST['codice'.$ord.$row],0,15));// controllo se il codice è già stato usato in GAzie
						if ($usato){ // se il codice è già in uso lo modifico accodandoci l'ID
							$_POST['codice'.$ord.$row]=substr($_POST['codice'.$ord.$row],0,10)."-".substr($_POST['refid'.$ord.$row],0,4);
						}

						// inserisco il nuovo articolo
						gaz_dbi_query("INSERT INTO " . $gTables['artico'] . "(peso_specifico,web_mu,web_multiplier,ecomm_option_attribute,id_artico_group,web_public,codice,descri,ref_ecommerce_id_product,good_or_service,unimis,catmer,".$listinome.",aliiva,codcon,adminid) VALUES ('". $_POST['peso_specifico'.$ord.$row] ."', '". substr($_POST['unimis'.$ord.$row], 0, 3) ."', '1', '". $arrayvar ."', '". $id_artico_group ."', '1', '". addslashes (substr($_POST['codice'.$ord.$row],0,15)) ."', '". addslashes(substr($_POST['descri'.$ord.$row], 0, 50)) ."', '".substr($_POST['refid'.$ord.$row], 0, 50)."', '".$good_or_service."', '" . substr($_POST['unimis'.$ord.$row], 0, 3) . "', '". $category ."', '". $prelis ."', '".$codvat."', '420000006', '" . $admin_aziend['adminid'] . "')");
						$codart= substr($_POST['codice'.$ord.$row],0,15);// dopo averlo creato ne prendo il codice come $codart
						$descri= $_POST['descri'.$ord.$row].$_POST['adddescri'.$ord.$row]; //prendo anche la descrizione

					} else {// se l'articolo esiste in GAzie
						$codvat=gaz_dbi_get_row($gTables['artico'], "codice", $codart)['aliiva'];
						$aliiva=$_POST['aliiva'.$ord.$row];
						if ($includevat=="true" AND floatval($_POST['prelis_imp'.$ord.$row])==0){ // se l'e-commerce include l'iva e non ha mandato il prezzo imponibile, scorporo l'iva dal prezzo dell'articolo
							$div=0;
							$div="1.".intval($aliiva);
							$prelis=$_POST['prelis_vatinc'.$ord.$row] / $div;
						} elseif ($includevat=="true" AND floatval($_POST['prelis_imp'.$ord.$row])>0) {
							$prelis=$_POST['prelis_imp'.$ord.$row];
						}
						if ($includevat!=="true"){ // se l'ecommerce non iclude l'iva uso il prezzo imponibile
							$prelis=$_POST['prelis_imp'.$ord.$row];
						}
					}
					// salvo rigo su database tabella rigbro
					$rigbro['id_tes']=intval($id_tesbro);$rigbro['tiprig']=0;$rigbro['codart']=$codart;$rigbro['descri']=addslashes(substr($descri, 0, 50));$rigbro['unimis']=substr($_POST['unimis'.$ord.$row], 0, 3);$rigbro['quanti']=floatval($_POST['quanti'.$ord.$row]);$rigbro['prelis']=$prelis;$rigbro['sconto']=$percdisc;$rigbro['codvat']=$codvat;$rigbro['codric']='420000006';$rigbro['pervat']=$aliiva;$rigbro['status']='ONLINE-SHOP';
					rigbroInsert($rigbro);
				}else{
					// salvo rigo sconto su database tabella rigbro
					$vat = gaz_dbi_get_row($gTables['aliiva'], "aliquo", $_POST['aliiva'.$ord.$row], " AND tipiva = 'I'");
					$codvat=$vat['codice'];//ricavo il codice dall'aliquota che mi ha passato l'e-commerce
					$aliiva=$vat['aliquo'];
					$rigbro['id_tes']=intval($id_tesbro);$rigbro['tiprig']=0;$rigbro['codart']='';$rigbro['descri']=addslashes(substr($_POST['descri'.$ord.$row], 0, 50));$rigbro['unimis']="n";$rigbro['quanti']=floatval($_POST['quanti'.$ord.$row]);$rigbro['prelis']= -$_POST['prelis_imp'.$ord.$row];$rigbro['sconto']="";$rigbro['codvat']=$codvat;$rigbro['codric']='420000006';$rigbro['pervat']=$aliiva;$rigbro['status']='ONLINE-SHOP';
					rigbroInsert($rigbro);
				}
			}
      if (strlen($_POST['note'.$ord])>3){// se l'ecommerce ha inviato delle note all'ordine, le accodo ai righi come rigo descrittivo
        $rigbro['id_tes']=intval($id_tesbro);$rigbro['tiprig']=2;$rigbro['codart']='';$rigbro['descri']=addslashes(substr($_POST['note'.$ord], 0, 1000));$rigbro['unimis']='';$rigbro['quanti']=0;$rigbro['prelis']=0;$rigbro['sconto']=0;$rigbro['codvat']=0;$rigbro['codric']=0;$rigbro['pervat']=0;$rigbro['status']='ONLINE-SHOP';
				rigbroInsert($rigbro);
      }

			$numdoc++; //incremento il numero d'ordine GAzie
		}
	}
	header("Location: " . "../../modules/vendit/report_broven.php?auxil=VOW");
    exit;
}

$access=base64_encode($accpass);
require('../../library/include/header.php');
$script_transl = HeadMain();
?>
<form method="POST" name="download" id="ordini" enctype="multipart/form-data">
<?php
// avvio il file di interfaccia presente nel sito web remoto
$headers = @get_headers($urlinterf.'?access='.$access);
if ( isset($headers[0]) AND intval(substr($headers[0], 9, 3))==200){ // controllo se il file esiste o mi dà accesso

	$xml=simplexml_load_file($urlinterf.'?access='.$access.'&rnd='.time()) ;
	if (!$xml){
		?>
		<script>
		alert("<?php echo "Errore nella creazione del file xml"; ?>");
		location.replace("./synchronize.php");
		</script>
		<?php
	}

	?>


	<input type="hidden" name="download" value="download" >

			<table class="table table-striped" style="margin: 0 auto; max-width: 80%; margin-top:10px;">
				<thead>
					<tr>
					<th></th>
					<th>Codice</th>
					<th>Nome</th>
					<th>Cognome</th>
					<th>Città</th>
					<th>Totale</th>
					<th>Scarica</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$n=0;
					foreach($xml->Documents->children() as $order) { // carico le testate degli ordini
						$nr=0;
						?>
						<tr>
						<?php
						echo '<td>';
						echo $n;
						echo '</td><td>';
						echo $order->Number;
						echo '<input type="hidden" name="numdoc'. $n .'" value="'. $order->Number . '">';
						echo '</td><td>';
						echo $order->CustomerName;
						echo '<input type="hidden" name="ragso1'. $n .'" value="'. $order->CustomerSurname ." ". $order->CustomerName . '">';
						echo '</td><td>';
						echo $order->CustomerSurname;
						echo '<input type="hidden" name="ragso2'. $n .'" value="'. $order->BusinessName . '">';
						echo '</td><td>';
						echo $order->CustomerCity ;
						echo '<input type="hidden" name="citspe'. $n .'" value="'. $order->CustomerCity .'">';
						echo '</td><td>';
						echo gaz_format_number($order->Total);
						echo '<input type="hidden" name="order_full_price'. $n .'" value="'. $order->Total .'">';
						echo '<input type="hidden" name="order_discount_price'. $n .'" value="'. $order->TotalDiscount .'">';
						echo '</td><td>';
						echo '<input type="hidden" name="ref_ecommerce_id_order'. $n .'" value="'. $order->Numbering .'">';
						echo '<input type="hidden" name="ref_ecommerce_id_customer'. $n .'" value="'. $order->CustomerCode .'">';
						echo '<input type="hidden" name="prospe'. $n .'" value="'. $order->CustomerProvince .'">';
						echo '<input type="hidden" name="capspe'. $n .'" value="'. $order->CustomerPostCode .'">';
						echo '<input type="hidden" name="indspe'. $n .'" value="'. $order->CustomerAddress .'">';
						echo '<input type="hidden" name="country'. $n .'" value="'. $order->CustomerCountry .'">';
						echo '<input type="hidden" name="codfis'. $n .'" value="'. $order->CustomerFiscalCode .'">';
						echo '<input type="hidden" name="pariva'. $n .'" value="'. $order->CustomerVatCode .'">';
						echo '<input type="hidden" name="telefo'. $n .'" value="'. $order->CustomerTel .'">';
						echo '<input type="hidden" name="datemi'. $n .'" value="'. $order->DateOrder .'">';
						echo '<input type="hidden" name="pagame'. $n .'" value="'. $order->PaymentName .'">';
						echo '<input type="hidden" name="idpagame'. $n .'" value="'. $order->PaymentId .'">';
						echo '<input type="hidden" name="numlistnome'. $n .'" value="'. $order->PriceList .'">';
						echo '<input type="hidden" name="numlist'. $n .'" value="'. $order->PriceListNum .'">';
						echo '<input type="hidden" name="includevat'. $n .'" value="'. $order->PricesIncludeVat .'">';
						echo '<input type="hidden" name="speban'. $n .'" value="'. $order->CostPaymentAmount .'">';
						echo '<input type="hidden" name="traspo'. $n .'" value="'. $order->CostShippingAmount .'">';
						echo '<input type="hidden" name="destin'. $n .'" value="'. $order->CustomerShippingDestin .'">';
						echo '<input type="hidden" name="spediz'. $n .'" value="'. $order->Carrier .'">';
						echo '<input type="hidden" name="codvatcost'. $n .'" value="'. $order->CostVatCode .'">';
						echo '<input type="hidden" name="aliivacost'. $n .'" value="'. $order->CostVatAli .'">';
						echo '<input type="hidden" name="seziva'. $n .'" value="'. $order->SezIva .'">';
						echo '<input type="hidden" name="email'. $n .'" value="'. $order->CustomerEmail .'">';
						echo '<input type="hidden" name="pec_email'. $n .'" value="'. $order->CustomerPecEmail .'">';
            echo '<input type="hidden" name="note'. $n .'" value="'. $order->CustomerNote .'">';
						echo '<input type="hidden" name="fe_cod_univoco'. $n .'" value="'. $order->CustomerCodeFattEl .'">';
						foreach($xml->Documents->Document[$n]->Rows->children() as $orderrow) { // carico le righe degli articoli ordinati
							echo '<input type="hidden" name="codice'. $n . $nr.'" value="'. $orderrow->Code . '">';
							echo '<input type="hidden" name="type'. $n . $nr.'" value="'. $orderrow->Type . '">';
							echo '<input type="hidden" name="descri'. $n . $nr.'" value="'. $orderrow->Description . '">';
							echo '<input type="hidden" name="adddescri'. $n . $nr.'" value="'. $orderrow->AddDescription . '">';
							echo '<input type="hidden" name="stock'. $n . $nr.'" value="'. $orderrow->Stock . '">';
							echo '<input type="hidden" name="catmer'. $n . $nr.'" value="'. $orderrow->Category . '">';
							echo '<input type="hidden" name="catmer_descri'. $n . $nr.'" value="'. $orderrow->ProductCategory . '">';
							echo '<input type="hidden" name="quanti'. $n . $nr.'" value="'. $orderrow->Qty . '">';
							echo '<input type="hidden" name="prelis_imp'. $n . $nr.'" value="'. $orderrow->Price . '">';
							echo '<input type="hidden" name="prelis_vatinc'. $n . $nr.'" value="'. $orderrow->PriceVATincl . '">';
							echo '<input type="hidden" name="codvat'. $n . $nr.'" value="'. $orderrow->VatCode . '">';
							echo '<input type="hidden" name="aliiva'. $n . $nr.'" value="'. $orderrow->VatAli . '">';
							echo '<input type="hidden" name="refid'. $n . $nr.'" value="'. $orderrow->Id . '">';
							echo '<input type="hidden" name="unimis'. $n . $nr.'" value="'. $orderrow->MeasureUnit . '">';
							echo '<input type="hidden" name="peso_specifico'. $n . $nr.'" value="'. $orderrow->ProductWeight . '">';
							echo '<input type="hidden" name="num_rows'. $n .'" value="'. $nr . '">';
							echo '<input type="hidden" name="product_parent_id'. $n . $nr .'" value="'. $orderrow->ParentId .'">';// se ci sono varianti questo è l'id del padre
							echo '<input type="hidden" name="characteristic_id'. $n . $nr .'" value="'. $orderrow->CharacteristicId .'">';
							echo '<input type="hidden" name="characteristic'. $n . $nr .'" value="'. $orderrow->Characteristic .'">';
							$nr++;
						}

						if(gaz_dbi_get_row($gTables['tesbro'], "ref_ecommerce_id_order", $order->Numbering, " AND datemi  = '".$order->DateOrder."'")){
						?>
						<span class="glyphicon glyphicon-ban-circle text-danger" title="Già scaricato"></span>
						<?php
						} else {
							?>
							<input type="checkbox" name="download<?php echo $n; ?>" value="download">
							<?php
						}
						?>
						<input type="hidden" name="num_orders" value="<?php echo $n; ?>">
						</td></tr>
						<?php



						$n++;
					}

					?>

					<tr>
					<td style="text-align: right;">
					<input type="submit" name="Return"  value="Indietro">
					</td>
					<td></td><td></td>
					<td style="background-color:lightgreen;">
					<?php
					echo "Connesso a " . $ftp_host;
					?>
					</td>
					<td></td><td></td>
					<td>
					<input type="submit" name="conferma"  onClick="chkSubmit();" value="Scarica">
					</td>
					</tr>
				</tbody>
			</table>

	<?php
} else { // IL FILE INTERFACCIA NON ESISTE > ESCO
	$msg=($headers)?substr($headers[0], 9, 3):'non riesco ad accedere';
	?>
	<script>
	var msg = '<?php echo $msg; ?>';
	alert("Errore di connessione al file di interfaccia web = " + msg );
	location.replace("./synchronize.php");
    </script>
	<?php
}
require("../../library/include/footer.php");
?>
</form>
