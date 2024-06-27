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
$admin_aziend=checkAdmin();
$titolo = 'Webmail';
require("../../library/include/header.php");
$script_transl=HeadMain();
$where = "var = \"ruburl\"";
$orderby = "id desc";

if ( !isset($_POST["id"]) ) $id = 1;
else $id = $_POST["id"];

if ( isset($_GET["field"]) ) {
	$orderby = $_GET["field"];
	if ( isset($_GET["flag_order"]) ) {
		$orderby .= " ".$_GET["flag_order"];
	} else {
		$orderby .= " DESC";
	}
}
if ( isset($_GET['auxil'])) {
	$auxil = $_GET['auxil'];
	$cerca = " and ( description like '%".$auxil."%' or val like '%".$auxil."%' ) ";
} else {
	$auxil = "";
	$cerca = "";
}

$corrente = "";
$result = gaz_dbi_dyn_query('*',$gTables['company_config'], "var=\"ruburl\"".$cerca, $orderby, $limit, $passo);

$headers = array  (
	$script_transl['id'] => "id",
	$script_transl['category'] => "",
	$script_transl['description'] => "description",
	$script_transl['address'] => "val",
	$script_transl['open'] => "",
	$script_transl['delete'] => ""             
 );
$linkHeaders = new linkHeaders($headers);
?>
	<form>
	<div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h4 class="box-title"><?php echo $script_transl['subtitle']; ?></h4>
              <div class="box-tools">
                <div class="input-group input-group-sm" style="width: 150px;">
                  <input name="auxil" class="form-control pull-right" placeholder="<?php echo $script_transl['search']; ?>" type="text" value="<?php echo $auxil; ?>">

                  <div class="input-group-btn">
                    <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                  </div>
                </div>
              </div>
            </div>
            <div class="box-body table-responsive no-padding">
              <table class="table table-hover">
                <tbody>
					<?php 
					$linkHeaders -> output();
					while ($row = gaz_dbi_fetch_array($result)) {
						if ( $row["id"] == $id ) {
							$corrente = $row["val"];
							$default = "selected";
						} else {
							$default = "";
						}
						if ( strpos( $row["description"],"|" ) ) {
							$valori = explode ("|", $row['description']);
						} else {
							$valori[0] = "Altro";
							$valori[1] = $row['description'];
						}
					?>
					<tr>
						<td>
						<a class="btn btn-xs btn-default" href="admin_ruburl.php?id=<?php echo $row["id"]; ?>&Update">
							<i class="glyphicon glyphicon-edit"></i> <?php echo $row["id"]; ?>
						</a>
						</td>
						<td>
							<?php echo $valori[0]; ?>
						</td>
						<td>
							<?php echo $valori[1]; ?>
						</td>
						<td>
							<a href="ruburl.php?id=<?php echo $row['id']; ?>"><?php echo $row["val"]; ?>
						</td>
						<td>
						<a class="btn btn-xs btn-default" hint="<?php echo $script_transl['opentab']; ?>" target="_blank" href="<?php echo $row["val"]; ?>">
  						<div id="title"><?php echo $script_transl['opentab']; ?></div><?php //echo parse_url($row["val"], PHP_URL_HOST); ?>
						</a>						
						</td>
						<td>
							<a class="btn btn-xs  btn-elimina" href="delete_ruburl.php?id=<?php echo $row["id"]; ?>">
								<i class="glyphicon glyphicon-trash"></i></a>
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
    </div>
	</form>
<?php
require("../../library/include/footer.php");
?>