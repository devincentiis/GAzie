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
$admin_aziend=checkAdmin(9);
if (isset($_POST['Submit'])) { // conferma tutto
}
$datares=[];
$gSync = new opencarttregazSynchro();
if($gSync->api_token){
  $gSync->GetCustomers();
}
$datares=json_decode($gSync->rawres);
	//exit;


require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new GAzieForm();
//print '<pre>';print_r(get_declared_classes());print '<br>'.$admin_aziend['synccommerce_classname'].'</pre>';

?>
<form method="post">
<div class="panel panel-default panel-body panel-help">

<p>La lista sottostante riporta i clienti che si sono iscritti allo store online anche se non hanno mai fatto un ordine, essi avranno all'inizio del rigo un <span class="btn btn-default btn-xs">bottone bianco</span> indicante il numero di ID. I clienti già importati sul gestionale avranno un <span class="btn btn-success btn-xs">bottone verde</span> con il codice cliente e il link che permette la modifica dei dati anagrafici. Acquisendo gli ordini dallo store online al gestionale attraverso la voce di menù <strong>Scarica Ordini</strong> di questo modulo, ovvero attivando in homepage il widget con il pannello di controllo del modulo <strong>SincroCommerce</strong> che esegue un controllo remoto sulla presenza di nuovi ordini ad ogni visita della homepage e propone l'acquisizione dei nuovi.
</p>
</div>
<div class="panel panel-default gaz-table-form">
	<div class="container-fluid text-center">
		<div class="table-responsive">
			<table class="table table-sm table-striped">
			<thead>
				<tr>
					<th>ID
					</th>
					<th>COGNOME/RAGIONE SOCIALE 1
					</th>
					<th>NOME/RAGIONE SOCIALE 2
					</th>
					<th>INDIRIZZO
					</th>
					<th>CITTA'
					</th>
					<th>EMAIL
					</th>
					<th>TELEFONO
					</th>
				</tr>
			</thead>
			<tbody>
<?php
foreach($datares as $v){
	// se è un cliente linkato a clfoco metto un link sull'id e lo indico col verde
  //var_dump($v);
  $exist = gaz_dbi_get_row($gTables['clfoco'], 'ref_ecommerce_id_customer',$v->customer_id);
  $exist=$exist?$exist['codice']:0;
	$v->customer_id=($exist>100000000)?'<a class="btn btn-success btn-xs" href="../vendit/admin_client.php?codice='.substr($exist,3,6).'&Update">COD:'.intval(substr($exist,3,6)).'</a>':'<a class="btn btn-default btn-xs">ID: '.$v->customer_id."</a>";

?>
				<tr>
					<td class="text-left"><?php echo $v->customer_id;?>
					</td>
					<td class="text-left"><?php echo $v->lastname;?>
					</td>
					<td class="text-left"><?php echo $v->firstname;?>
					</td>
					<td class="text-left"><?php echo $v->address_1;?>
					</td>
					<td class="text-left"><?php echo $v->city.' ('.$v->code.')';?>
					</td>
					<td class="text-left"><?php echo $v->email;?>
					</td>
					<td class="text-left"><?php echo $v->telephone;?>
					</td>
				</tr>
<?php
}
?>
			</tbody>
			</table>
		</div>
	</div>
</div>
</form>
<?php
require("../../library/include/footer.php");
?>
