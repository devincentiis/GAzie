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
require("../../modules/magazz/lib.function.php");
$magazz = new magazzForm();
$query="SELECT codice, descri, scorta FROM " . $gTables['artico'] . " WHERE id_assets = 0 AND (good_or_service = 2 OR good_or_service = 0) AND movimentabile <> 'N' AND ordinabile <> 'N' ORDER BY catmer, codice, descri ";
$rs = gaz_dbi_query($query);
$understock=[];
while ($r = gaz_dbi_fetch_array($rs)) {
  $mv = $magazz->getStockValue(false, $r['codice']);
  $mv = array_pop($mv);
  if (is_array($mv)) { //articolo movimentato
    $mv['codice']= $r['codice'];
    $mv['descri']= $r['descri'];
    $mv['scorta']= $r['scorta'];
  } else {
    $mv=['q_g'=>0,'v_g'=>0,'codice'=>$r['codice'],'descri'=> $r['descri'], 'scorta'=> $r['scorta']];
  }
  if ($mv['q_g']<=0.000001){
    $mv['bg']='danger';
    $understock[]=$mv;
  } else if ($mv['q_g']<=$mv['scorta']) {
    $mv['bg']='warning';
    $understock[]=$mv;
  }
}
?>
<div class="panel panel-danger col-sm-12">
  <div class="box-header company-color">
    <b>Articoli da riordinare</b>
    <a class="pull-right dialog_grid" id_bread="<?php echo $grr['id_bread']; ?>" style="cursor:pointer;"><i class="glyphicon glyphicon-cog"></i></a>
  </div>
  <div class="box-body">
<?php
$i=count($understock);
if ($i>0){ // visualizzo scadenzario lotti sono se sono presenti
?>
    <table  id="understock" class="table table-bordered table-striped table-responsive dataTable" role="grid" aria-describedby="understock_info">
      <thead>
        <tr role="row">
          <th class="text-center">Codice</th>
          <th class="text-center">Descrizione</th>
          <th class="text-center">Scorta minima</th>
          <th class="text-center">Giacenza attuale</th>
        </tr>
      </thead>
      <tbody>
<?php
$bgc='bg-danger';
for ($x=0; $x<$i; $x++){
  echo '<tr>';
  echo '<td class="text-left"><a class="btn btn-xs btn-'.$understock[$x]['bg'].'" href="../magazz/admin_artico.php?Update&codice='.$understock[$x]['codice'].'" ><i class="glyphicon glyphicon-edit"></i> <span class="keyRow">' . $understock[$x]['codice'] . '</span></a></td>';
  echo '<td class="text-left">'. substr($understock[$x]['descri'],0,25) . "</td>";
  echo '<td class="text-center">'.floatval($understock[$x]['scorta']). "</td>";
  echo '<td class="text-center"><a class="btn btn-xs btn-'.$understock[$x]['bg'].'" href="../magazz/select_schart.php?di=0101' . date('Y') . '&df=' . date('dmY') . '&id=' .$understock[$x]['codice'] . '" target="_blank"><i class="glyphicon glyphicon-th-list"> '.floatval($understock[$x]['q_g']). ' </i>
      </a></td>';
  echo '</tr>';
}
$i--;
$keyRowFoundCli=$i.'_'.$understock[$i]['codice'];

?>
      </tbody>
    </table>
<?php
} else {
	echo '<div class="bg-success"><h3> NON CI SONO ARTICOLI DA RIORDINARE </h3></div>';
}
?>
  </div>
</div>
<script src="../../library/theme/lte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../../library/theme/lte/plugins/datatables/dataTables.bootstrap.min.js"></script>
<script>
$(function () {
  $("#understock").DataTable({
    "oLanguage": {
      "sUrl": "../../library/theme/lte/plugins/datatables/Italian.json"
    },
    "lengthMenu": [[5, 10, 20, 50, -1], [5, 10, 20, 50, "Tutti"]],
    "iDisplayLength": 10,
    "responsive": true,
    "ordering": false,
    "stateSave": true
  });
});
  function gotoPage(id,num)
	{
		var table = $(id).DataTable();
		table.page( num ).draw( false );
	}

	function searchPageOnTable(id,keyRow,lenPage)
	{
		var table = $(id).DataTable();

		var plainArray = table
			.column(0)
			.data()
			.toArray();

		var i;

		for(i= 0 ; i < plainArray.length; i++)
		{
			if(plainArray[i].split('"keyRow">')[1].replace("</span>","") == keyRow)
				break;
		}

		return Math.floor(i / lenPage)
	}
  $("head").append('<link rel="stylesheet" href="./admin.css">');

	$(window).on('load',(function(){
		keyRowCli = "<?php echo $keyRowFoundCli ?>";
		if(keyRowCli != ""){
			setTimeout(function(){num = searchPageOnTable('#understock',keyRowCli,$('#understock').DataTable().page.len())
				gotoPage('#understock',num);
				$("#understock").css("max-height","none");
				$("#understock").css("opacity","1");
				$(".wheel_load").css("display","none");
			},1000)
			}
			else
			{
				$("#understock").css("max-height","none");
				$("#understock").css("opacity","1");
				$(".wheel_load").css("display","none");
			}
		}));
</script>

