<div class="panel panel-info col-md-12" >
  <div class="box-header company-color">
    <b>Situazione della fatturazione</b>
    <a class="pull-right dialog_grid" id_bread="<?php echo $grr['id_bread']; ?>" style="cursor:pointer;"><i class="glyphicon glyphicon-cog"></i></a>
	</div>
  <div class="box-body">
    <table id="invoicing" class="table table-bordered table-striped dataTable"
		<thead>
      <tr>
        <th>Tipo di segnalazione</th>
        <th class="text-center">Numero</th>
        <th class="text-center">Operazione consigliata</th>
      </tr>
    </thead>
    <tbody>
    <!-- per adesso lo faccio collassare in caso di small device anche se si potrebbe fare uno switch in verticale -->
<?php
  $rs_fatacq=gaz_dbi_dyn_query( "COUNT(DISTINCT CONCAT(YEAR(datreg), protoc)) AS cnt", $gTables['tesdoc'], "id_con = 0 AND tipdoc LIKE 'AF_'", 'datreg');
	$fatacq = gaz_dbi_fetch_array($rs_fatacq);
	if ($fatacq['cnt']>0){
?>
    <tr>
        <td style="text-align: left;"><b class="text-info">Fatture d'acquisto da contabilizzare</b></td>
        <td><b><?php echo $fatacq['cnt']; ?></b></td>
        <td><a class="btn btn-info btn-xs" href="../acquis/accounting_documents.php?type=AF">Contabilizza fatture di acquisto<i class="glyphicon glyphicon-export"></i></a></td>
    </tr>
<?php
  }
  // se ho configurato un servizio di gestione flussi verso SdI controllo se ci sono invii in sospeso
  $sdi_flux = gaz_dbi_get_row($gTables['company_config'], 'var', 'send_fae_zip_package')['val'];
  if($sdi_flux){
    $rs_fae_flux=gaz_dbi_dyn_query( "COUNT(*) AS cnt", $gTables['fae_flux']." AS faeflux", "filename_son = '' AND ( flux_status = 'DI' OR flux_status = 'NS')", 'exec_date');
    $fae_flux = gaz_dbi_fetch_array($rs_fae_flux);
    if ($fae_flux['cnt']>0){
?>
      <tr>
          <td style="text-align: left;"><b class="text-danger">Fatture da inviare e/o scartate da (re)inviare</b></td>
          <td><b><?php echo $fae_flux['cnt']; ?></b></td>
          <td><a class="btn btn-danger btn-xs" href="../vendit/report_docven.php">Vai al report delle fatture<i class="glyphicon glyphicon-export"></i></a></td>
      </tr>
<?php
    }
    $rs_faeacq=gaz_dbi_dyn_query( "COUNT(*) AS cnt", $gTables['files']." AS faeacq", "item_ref = 'faesync' AND status = 0", 'id_doc');
    $fae_faeacq = gaz_dbi_fetch_array($rs_faeacq);
    if ($fae_faeacq['cnt']>0){
?>
      <tr>
          <td style="text-align: left;"><b class="text-success">Fatture d'acquisto arrivate</b></td>
          <td><b><?php echo $fae_faeacq['cnt']; ?></b></td>
          <td><a class="btn btn-success btn-xs" href="../acquis/acquire_invoice.php">Acquisisci le fatture dei fornitori<i class="glyphicon glyphicon-import"></i></a></td>
      </tr>
<?php
    }
  }
  $rs_fatven=gaz_dbi_dyn_query( "seziva, YEAR(datfat) AS year, CONCAT(YEAR(datfat),seziva,protoc) AS ctrl", $gTables['tesdoc'], "id_con = 0 AND tipdoc LIKE 'F%' GROUP BY seziva, YEAR(datfat), protoc", 'datfat,seziva,protoc');
  $ctrl_ys=0;
  $ctrl=0;
  $cnt=0;
  $acc=[];
	while ($fatven = gaz_dbi_fetch_array($rs_fatven)){
    if ($ctrl_ys != $fatven['year'].$fatven['seziva']) {
      $cnt=0;
    }
    if ($ctrl != $fatven['ctrl']) {
      $cnt++;
      $ctrl = $fatven['ctrl'];
    }
    // mi serve per raggruppare i DdT (tipdoc=FAD)
    $acc[$fatven['year'].$fatven['seziva']]=$cnt;
    $ctrl_ys=$fatven['year'].$fatven['seziva'];
  }
  $first=true;
  foreach($acc as $k => $v) {
?>
    <tr>
<?php
    if ($first){
?>
      <td style="text-align: left;"><b class="text-success">Fatture di vendita da contabilizzare</b></td>
<?php
    } else {
?>
      <td></td>
<?php
    }
?>
    <td><b><?php echo $v.' <small>(del '.substr($k,0,4).' sez.'.substr($k,4,1).')</small>'; ?></b></td>
    <td><a  class="btn btn-success btn-xs" href="../vendit/accounting_documents.php?type=F&vat_section=<?php echo substr($k,4,1);?>">Contabilizza fatture di vendita <br/><?php echo substr($k,0,4).'/'.substr($k,4,1); ?><i class="glyphicon glyphicon-export"></i></a></td>
    </tr>
<?php
    $first=false;
  }
  $rs_ddtven=gaz_dbi_dyn_query( "COUNT(*) AS cnt", $gTables['tesdoc'], "protoc = 0 AND ( tipdoc = 'DDT' OR tipdoc ='CMR' OR (tipdoc = 'DDV' AND datemi <  DATE_SUB(NOW(),INTERVAL 1 YEAR) ) )", 'id_tes');
	$ddtven = gaz_dbi_fetch_array($rs_ddtven);
	if ($ddtven['cnt']>0){
?>
    <tr>
        <td style="text-align: left;"><b class="text-warning">D.d.T. di vendita da fatturare</b></td>
        <td><b><?php echo $ddtven['cnt']; ?></b></td>
        <td><a class="btn btn-warning btn-xs" href="../vendit/emissi_fatdif.php">Genera fatture differite <i class="glyphicon glyphicon-export"></i></a></td>
    </tr>
<?php
  }
  $rs_geneff=gaz_dbi_dyn_query( "COUNT(*) AS cnt", $gTables['tesdoc']." AS tesdoc LEFT JOIN ". $gTables['pagame'] . ' AS pay ON tesdoc.pagame=pay.codice', "(tippag = 'B' OR tippag = 'T' OR tippag = 'V' OR tippag = 'I') AND geneff = '' AND tipdoc LIKE 'FA_'", 'id_tes');
	$geneff = gaz_dbi_fetch_array($rs_geneff);
	if ($geneff['cnt']>0){
?>
    <tr>
        <td style="text-align: left;"><b class="text-danger">Fatture che devono generare effetti</b></td>
        <td><b><?php echo $geneff['cnt']; ?></b></td>
        <td><a class="btn btn-danger btn-xs" href="../vendit/genera_effett.php">Genera effetti da fatture<i class="glyphicon glyphicon-export"></i></a></td>
    </tr>
<?php
  }
  $rs_contract=gaz_dbi_dyn_query( "start_date, months_duration, YEAR(MAX(tesdoc.data_ordine))*12 + MONTH(MAX(tesdoc.data_ordine)) AS covered_month, tacit_renewal ",
  $gTables['contract'] . " AS contract LEFT JOIN ".$gTables['tesdoc']." AS tesdoc ON contract.id_contract=tesdoc.id_contract ",
  "1", 'contract.id_contract');
  $contract=0;
  $now = date("Y")*12+date('m');
	while ($r = gaz_dbi_fetch_array($rs_contract)) {
    //var_dump($r);
    $strdate = ($r['start_date']!== null && strlen($r['start_date'])>7)?(substr($r['start_date'],0,4) * 12 + substr($r['start_date'],5,2)):0;
    $enddate = ($r['start_date']!== null && strlen($r['start_date'])>7)?($strdate+$r['months_duration']):0;
    if ( $r['tacit_renewal'] !== null && $strdate < $now && ($enddate < $now || $r['tacit_renewal'] >=1) ) {
      if ( $r['covered_month'] < $now ) {
        $contract ++;
      }
    }
  }
	if ( $contract >= 1 ) {
?>
    <tr>
        <td style="text-align: left;"><b class="text-warning">Contratti che devono generare fatture</b></td>
        <td><b><?php echo $contract; ?></b></td>
        <td><a class="btn btn-danger btn-xs" href="../vendit/invoice_from_contract.php">Genera fatture da contratti<i class="glyphicon glyphicon-export"></i></a></td>
    </tr>
<?php
  }
?>
    </tbody>
    </table>
	</div>
</div>
