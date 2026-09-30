<?php
/*
  --------------------------------------------------------------------------
  Copyright (C) - Antonio De Vincentiis, Montesilvano anno 2020
  tel.3383121161
  a.devincentiis@tiscali.it
  Montesilvano (PE)
  --------------------------------------------------------------------------

ATTENZIONE DEVONO ESSERE PRESENTI 2 FILES SULL'ECOMMERCE:
catalog/model/catalog/ocgazie.php
catalog/controller/api/ocgazie.php


l'endpoin d'accesso per il login/token è:
http(s)://mydomanin/index.php?route=api/login

*/
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin(9);
$datares=[];
$gSync = new opencarttregazSynchro();
if($gSync->api_token){
  $rs_last_id = gaz_dbi_dyn_query("*", $gTables['tesbro'],"`tipdoc` = 'VOW'" ,'`ref_ecommerce_id_order` DESC',0,1);
  $last_id = gaz_dbi_fetch_array($rs_last_id);
  if ($last_id){
    $id=intval($last_id['ref_ecommerce_id_order']);
  } else{
    $id=0;
  }
  $gSync->get_sync_status($id);
  //var_dump($gSync->rawres);
  $datares=json_decode($gSync->rawres);
}

if (isset($_POST['Submit'])) { // conferma tutto
	$anagrafica = new Anagrafica();
	$acc_id=[];
	foreach($datares as $v){
		// per prima inserisco il cliente qualora non ci sia già...
		$custom=false;
		if ($v->customer_id>0){ // è un cliente registrato, quindi presumibilmente ho già il codice
			$custom_field=get_object_vars(json_decode($v->custom_field));
			$custom_field[1]=($custom_field[1]=='NO')?'':$custom_field[1];
			// in $custom_field ho CF e PI
			$custom = gaz_dbi_get_row($gTables['clfoco'], 'ref_ecommerce_id_customer',$v->customer_id);
		}else{
			$custom_field[1]='';
		}
		$pagame = gaz_dbi_get_row($gTables['pagame'], 'web_payment_ref',$v->payment_code);
		if(!$pagame){$pagame['codice']=1;}
		if (!$custom){ // non ho il cliente: provo a cercarlo per codice fiscale
			$custom['codfis']=$custom_field[1];
			$search = new selectPartner('');
      $partner = $search->queryAnagra(array("a.codfis" => " = '" . addslashes($custom['codfis']) . "'"));
			if (count($partner) > 0&& strlen($custom['codfis'])>4) {
				if (strlen($partner[0]['codice'])==9){ // ho anagrafica e piano conti
					// mi limito a mettere il ref_ecommerce_id_customer
					gaz_dbi_put_row($gTables['clfoco'],'codice',$partner[0]['codice'],'ref_ecommerce_id_customer',$v->customer_id);
					// riprendo comunque il  pagamento dal cliente che ho già
					$pagame['codice'] = gaz_dbi_get_row($gTables['clfoco'], 'codice',$partner[0]['codice'])['codpag'];
				} else{ // ho solo l'anagrafica creo il cliente in clfoco e riferisco con ref_ecommerce_id_customer
					$new_clfoco = $anagrafica->getPartnerData($partner[0]['id'], 1);
					$custom['codice'] = $anagrafica->anagra_to_clfoco($new_clfoco, $admin_aziend['mascli'],$pagame['codice']);
					gaz_dbi_put_row($gTables['clfoco'],'codice',$custom['codice'],'ref_ecommerce_id_customer',$v->customer_id);
				}
			} else { // non ho nulla devo inserire tutti i dati
				//trovo il primo codice cliente libero
        $last_partner = gaz_dbi_dyn_query("*", $gTables['clfoco'], 'codice BETWEEN ' . $admin_aziend['mascli']. '000001 AND ' . $admin_aziend['mascli'] . '999999', "codice DESC", 0, 1);
        $last = gaz_dbi_fetch_array($last_partner);
        if ($last) { $custom['codice'] = $last['codice'] + 1; } else { $custom['codice'] = $admin_aziend['mascli']. '000001';
        }
				if ($v->customer_id>0){ // è un cliente che si è registrato
					$custom['ragso1']=$v->firstname.' '.$v->lastname;
					$custom['ref_ecommerce_id_customer']=$v->customer_id;
					$custom['pariva']=$custom_field[2];
					$custom['sexper']='G';
					$custom['legrap_pf_cognome']='';
					$custom['legrap_pf_nome']='';
					if (strlen($custom['codfis'])==16){
						$custom['sexper']=(intval(substr($custom['codfis'],9,2))>39)?'F':'M';
						$custom['legrap_pf_cognome']=$v->lastname;
						$custom['legrap_pf_nome']=$v->firstname;
						$custom['ragso1']=$v->lastname.' '.$v->firstname;
					}
					$custom['indspe']=$v->address_1;
					$custom['capspe']=$v->postcode;
					$custom['citspe']=$v->city;
					$custom['prospe']=$v->code;
					$custom['country']=$v->iso_code_2;
					$custom['codpag']=$pagame['codice'];
					$custom['ragdoc'] = 'S';
					$custom['speban'] = 'S';
          $custom['addbol'] = 'S';
          $custom['spefat'] = 'N';
          $custom['stapre'] = 'S';
          $custom['id_currency'] = 1;
          $custom['id_language'] = 1;
					$custom['telefo']=$v->telephone;
					$custom['e_mail']=$v->email;
					$custom['annota'] = 'Inserito da web';
					$anagrafica->insertPartner($custom);
				}else{ // è un cliente che non si è registrato uso solo la destinazione
					$custom['codice']=0;
				}

			}
		}

		// inserisco la testata sul database
    // ricavo i progressivi in base al tipo di documento
    $rs_lo = gaz_dbi_dyn_query("*", $gTables['tesbro'], "YEAR(datemi) = " . substr($v->date_added,0,4) . " AND tipdoc LIKE 'VO_'", "numdoc DESC", 0, 1);
    $lo = gaz_dbi_fetch_array($rs_lo);
    // se e' il primo documento dell'anno, resetto il contatore
    if ($lo) { $form['numdoc'] = $lo['numdoc'] + 1; } else { $form['numdoc'] = 1; }
        $tesbro['clfoco'] = $custom['codice'];
        $tesbro['template'] ='OrdineWeb';
		$tesbro['pagame']=$pagame['codice'];
		$tesbro['destin']=$v->shipping_firstname.' '.$v->shipping_lastname."\n".$v->shipping_address_1.$v->shipping_address_2."\n".$v->shipping_postcode.' '.$v->shipping_city;
        if(isset($v->shipping[0])){
            $tesbro['destin'] .= ' ('.$v->shipping[0]->code.')';
        }
		$tesbro['expense_vat']=$admin_aziend['preeminent_vat'];
		$expense_vat_percent = gaz_dbi_get_row($gTables['aliiva'], 'codice',$admin_aziend['preeminent_vat'])['aliquo'];
        $tesbro['tipdoc'] = 'VOW';
        $tesbro['numdoc'] =$v->order_id;
        $tesbro['seziva'] = 1;
		$tesbro['ref_ecommerce_id_order']=$v->order_id;
        $tesbro['protoc'] = 0;
        $tesbro['numfat'] = 0;
        $tesbro['datfat'] = 0;
		$tesbro['spediz'] = $v->shipping_method;
		$tesbro['caumag'] = 1;
        //inserisco la testata
        $tesbro['status'] = 'DA WEB';
        $tesbro['initra'] = $v->date_added;
        $tesbro['datemi'] = substr($v->date_added,0,10);
		foreach($v->total as $vt){
			if ($vt->code=='shipping'){
				$tesbro['traspo'] = round($vt->value/(1+$expense_vat_percent/100),2);
			}
		}
		$last_tb_id=tesbroInsert($tesbro);
		$acc_id[]=$last_tb_id;
		foreach($v->product as $vp){
			$rigbro['id_tes'] = $last_tb_id;
			$rigbro['codric'] = $admin_aziend['impven'];
			$artico=gaz_dbi_get_row($gTables['artico'], 'codice',$vp->model);
            $vp_perc=0.00;
            if ($vp->price>=0.01){
                $vp_perc=round($vp->tax/$vp->price*100,1);
            }
			if (!$artico){
				$rigbro['codart'] = '';
				$rigbro['descri'] = $vp->name;
				$rigbro['unimis'] = 'n.';
				$rigbro['quanti'] = $vp->quantity;
				$rigbro['prelis'] = $vp->price;
				// ricerco l'aliquota più vicina in base alla percentuale
				$rs_perc = gaz_dbi_query("SELECT * FROM ".$gTables['aliiva']." WHERE `tipiva`='I' AND `fae_natura`='' ORDER BY ABS(`aliquo` - ".$vp_perc.") LIMIT 1");
				$perc = gaz_dbi_fetch_array($rs_perc);
				if($perc){
					$rigbro['codvat'] = $perc['codice'];
					$rigbro['pervat'] = $perc['aliquo'];
				}else{ // in ultima istanza uso la preminente
					$rigbro['codvat'] =$admin_aziend['preeminent_vat'];
					$rigbro['pervat'] = $vp_perc;
				}
			}else{
				$rigbro['codart'] = $artico['codice'];
				$rigbro['descri'] = $vp->name;
				$rigbro['unimis'] = 'n.';
				$rigbro['quanti'] = $vp->quantity;
				$rigbro['prelis'] = $vp->price;
				$rigbro['codvat'] = $artico['aliiva'];
				$rigbro['pervat'] = $vp_perc;
			}
			rigbroInsert($rigbro);
		}

	}
	$GLOBALS['menu_alerts_lastcheck']=0;
	$_SESSION['menu_alerts_data']=''; // annullo l'alert

	require("../../library/include/document.php");
    //recupero i documenti da stampare
    $where = "tipdoc = 'VOW' AND id_tes BETWEEN ".min($acc_id)." AND ".max($acc_id);
    $from = $gTables['tesbro'] . " A LEFT JOIN " . $gTables['clfoco'] . " B ON A.clfoco=B.codice
                                   LEFT JOIN " . $gTables['anagra'] . " C ON B.id_anagra=C.id  ";
    //recupero i documenti da stampare
    $testate = gaz_dbi_dyn_query("*", $from, $where);
    if ($testate->num_rows > 0) {
        createMultiDocument($testate, 'OrdineWeb', $gTables);
    } else {
        alert("Nessun documento da stampare");
        tornaPaginaPrecedente();
    }
    exit;
}
require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new GAzieForm();
?>
<div class="panel panel-default panel-body panel-help">
	<p>Confermando si acquisiranno e stamperanno (pdf) gli ordini fatti dai clienti sullo store online. Assieme agli ordini verranno inseriti sul gestionale anche i <span class="btn btn-warning btn-sm">nuovi clienti</span> evidenziati nella lista sottostante con un <strong>bottone arancione</strong> sulla ragione sociale. </p>
</div>
<form method="post">
<div class="panel panel-default gaz-table-form">
 <div class="container-fluid text-center">
		<div class="table-responsive">
			<table class="table table-sm">
			<thead>
				<tr>
					<th>ID
					</th>
					<th colspan="2">COGNOME,NOME,RAGIONE SOCIALE
					</th>
					<th>CITTA'
					</th>
					<th>PROVINCIA
					</th>
					<th>C.F. / P.IVA
					</th>
					<th>TELEFONO / EMAIL
					</th>
					<th>IMPORTO
					</th>
				</tr>
			</thead>
			<tbody>
<?php
$toggle=false;
require("../../library/include/check.inc.php");
$cf_pi = new check_VATno_TAXcode();
foreach($datares as $v){
	//print_r($v);
	$toggle=true;
	if ($v->customer_id>0){ // è un cliente registrato, quindi presumibilmente ho già il codice
		$custom_field=get_object_vars(json_decode($v->custom_field));
		$custom_field[1]=($custom_field[1]=='NO')?'':$custom_field[1];
    $cf = $cf_pi->check_TAXcode($custom_field[1],'IT');
		if (strlen($cf)>3){
			$custom_field[1]='<div class="btn btn-danger">Errato * '.$custom_field[1].' *</div>';
		}
		$pi = $cf_pi->check_VAT_reg_no($custom_field[2],'IT');
		if (strlen($pi)>3){
			$custom_field[2]='<div class="btn btn-danger">Errata * '.$custom_field[2].' *</div>';
		}
		// se è un cliente linkato a clfoco metto un link sull'id e lo indico col verde
		$exist = gaz_dbi_get_row($gTables['clfoco'], 'ref_ecommerce_id_customer',$v->customer_id);
	} else {
		$exist['codice']=0;
		 $custom_field[1]="<span class='bg-warning'>NON";
		 $custom_field[2]='REGISTRATO</span>';
	}
	$customer=($exist['codice']>100000000)?$v->lastname.' '.$v->firstname.'<br /><a class="btn btn-default btn-xs" href="../vendit/admin_client.php?codice='.substr($exist['codice'],3,6).'&Update" target="_blank"> '.$exist['descri'].' </a>':'<div class="btn btn-warning" title="NUOVO CLIENTE">'.$v->lastname.' '.$v->firstname.' </div>';
?>
				<tr>
					<td class="text-center"><?php echo 'Ordine<br />'.$v->order_id;?>
					</td>
					<td colspan="2" class="text-LEFT"><?php echo $customer;?>
					</td>
					<td class="text-LEFT"><?php echo $v->shipping_city;?>
					</td>
					<td class="text-LEFT"><?php echo $v->shipping_zone;?>
					</td>
					<td class="text-LEFT"><?php echo $custom_field[1].'<br />'.$custom_field[2];?>
					</td>
					<td class="text-LEFT"><?php echo $v->telephone.'<br />'.$v->email;?>
					</td>
					<td class="text-center"><?php echo 'Totale € '.gaz_format_number($v->total);?>
					</td>
				</tr>
<?php
	foreach($v->product as $vp){
?>
				<tr class="FacetFieldCaptionTD">
					<td>Rigo <?php echo $vp->order_product_id;?>
					</td>
					<td><?php echo $vp->model;?>
					</td>
					<td colspan="3"><?php echo $vp->name;?>
					</td>
					<td><?php echo 'n. '.$vp->quantity;?>
					</td>
					<td class="text-right"><?php echo '€ '.gaz_format_number($vp->price);?>
					</td>
					<td class="text-right"><?php echo '€ '.gaz_format_number($vp->total);?>
					</td>
				</tr>
<?php
	}
}
?>
			</tbody>
			</table>
		</div>
<?php
if ($toggle){
?>
<div class="text-center col-xs-12 bg-info"><button type="submit" class="btn btn-success" name="Submit">ACQUISISCI E STAMPA</button></div>
<?php
}else{
?>
<div class="text-center col-xs-12 bg-danger">NON HO TROVATO NUOVI ORDINI DA ACQUISIRE DAL SITO</div>
<?php
}
?>
 </div>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
