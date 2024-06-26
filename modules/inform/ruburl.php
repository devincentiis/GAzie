<?php
 /*
 --------------------------------------------------------------------------
                            GAzie - Gestione Azienda
    Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
         (http://www.devincentiis.it)
           <http://gazie.sourceforge.net>
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
$titolo = 'Rubrica URL';
require("../../library/include/header.php");
$script_transl=HeadMain();

if ( !isset($_POST["id"]) ) $id = "";
else $id = "and id=".$_POST["id"];
if ( isset($_GET["id"]) ) $id = "and id=".$_GET["id"];

$corrente = "";
$result = gaz_dbi_dyn_query('*',$gTables['company_config'], "var=\"ruburl\" ".$id, $orderby,$limit,$passo);
$row = gaz_dbi_fetch_array($result);
?>
<style type="text/css">
html {overflow: auto;}
html, body, div, iframe {margin: 0px; padding: 0px; height: 100%; border: none;}
iframe {display: block; width: 100%; border: none; overflow-y: auto; overflow-x: hidden;}
</style>
<script language="JavaScript">
<!--
function resize_iframe()
{
	var height=window.innerWidth;//Firefox
	if (document.body.clientHeight)
	{
		height=document.body.clientHeight;//IE
	}
	//resize the iframe according to the size of the
	//window (all these should be on the same line)
	document.getElementById("glu").style.height=parseInt(height-
	document.getElementById("glu").offsetTop-15)+"px";
}
window.onresize=resize_iframe; 
</script>

<!--<iframe style="position: absolute; height: 90%; width: 100%;" frameborder="0" src="<?php //echo $row["indirizzo"]; ?>"></iframe>-->
<iframe id="glu" width="100%" onload="resize_iframe()" src="<?php echo $row["val"]; ?>"></iframe>
</form>
<?php
require("../../library/include/footer.php");
?>