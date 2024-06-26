<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  SHOP SYNCHRONIZE è un modulo creato per GAzie da Antonio Germani, Massignano AP 
	  Copyright (C) 2018-2021 - Antonio Germani, Massignano (AP)
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
$rs = gaz_dbi_query("SELECT ".$gTables['admin_module'].".access FROM ".$gTables['admin_module']." LEFT JOIN ".$gTables['module']." ON ".$gTables['admin_module'].".moduleid=".$gTables['module'].".id WHERE `adminid`='".$admin_aziend['user_name']."' AND ".$gTables['module'].".name='shop-synchronize'");
$test=gaz_dbi_fetch_array($rs);
?>
<div class="panel panel-info col-sm-12">
<?php
if ($test && $test['access']==3){ 
?>
<div class="box-header company-color">
	<h4 class="box-title"><i class="glyphicon glyphicon-transfer"></i> SINCRONIZZAZIONE SHOP ONLINE</h4>
	<a class="pull-right dialog_grid" id_bread="<?php echo $grr['id_bread']; ?>" style="cursor:pointer;"><i class="glyphicon glyphicon-cog"></i></a>
</div>
<div class="box-body">
	<h4><a href="../shop-synchronize/synchronize.php"> Procedi alla sincronizzazione del sito per lo shopping online <i class="glyphicon glyphicon-transfer"></i></a></h4>
</div>
<?php
} else {
?>
<div class="box-header company-color">
	<h3 class="box-title">LA FUNZIONE SINCRONIZZAZIONE SHOP FUNZIONA SOLO ATTIVANDO IL RELATIVO MODULO </h3>
</div>

<?php	
}
?>
</div>