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
$msg='';

function getMovements($cm_ini,$cm_fin,$art_ini,$art_fin,$date_ini,$date_fin)
    {
        global $gTables,$admin_aziend;
        $m=array();
        if ($art_fin=='') {
              $art_fin='zzzzzzzzzzzzzzz';
        }
        if ( $_POST['ric']=="" ) $_POST['ric']="%%";
        $where=" catmer BETWEEN ".$cm_ini." AND ".$cm_fin." AND".
               " artico BETWEEN '".$art_ini."' AND '".$art_fin."' AND".
               " datreg BETWEEN ".$date_ini." AND ".$date_fin." AND (".
               $gTables['artico'].".descri like '".$_POST['ric']."' OR ".
               $gTables['artico'].".codice like '".$_POST['ric']."')";
		$what=$gTables['movmag'].".*, ".
              $gTables['caumag'].".codice, ".$gTables['caumag'].".descri AS descau, ".
              $gTables['warehouse'].".name AS desmag, ".
              $gTables['clfoco'].".codice, ".
			  $gTables['lotmag'].".identifier, ".
              $gTables['orderman'].".id AS id_orderman, ".$gTables['orderman'].".description AS desorderman, ".
              $gTables['anagra'].".ragso1, ".$gTables['anagra'].".ragso2, ".
              $gTables['artico'].".codice, ".$gTables['artico'].".descri AS desart, ".$gTables['artico'].".unimis, ".$gTables['artico'].".scorta, ".$gTables['artico'].".image, ".$gTables['artico'].".catmer ";
        $table=$gTables['movmag']." LEFT JOIN ".$gTables['caumag']." ON ".$gTables['movmag'].".caumag = ".$gTables['caumag'].".codice
               LEFT JOIN ".$gTables['warehouse']." ON ".$gTables['movmag'].".id_warehouse = ".$gTables['warehouse'].".id
               LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['movmag'].".clfoco = ".$gTables['clfoco'].".codice
               LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra
               LEFT JOIN ".$gTables['orderman']." ON ".$gTables['movmag'].".id_orderman = ".$gTables['orderman'].".id
               LEFT JOIN ".$gTables['artico']." ON ".$gTables['movmag'].".artico = ".$gTables['artico'].".codice
			   LEFT JOIN ".$gTables['lotmag']." ON ".$gTables['movmag'].".id_lotmag = ".$gTables['lotmag'].".id";
        $rs=gaz_dbi_dyn_query ($what, $table,$where,"catmer ASC, artico ASC, datreg ASC, id_mov ASC");
        while ($r = gaz_dbi_fetch_array($rs)) {
            $m[] = $r;
        }
        return $m;
    }
function getExtremeValue($table_name,$min_max='MIN')
    {
        $rs=gaz_dbi_dyn_query ($min_max.'(codice) AS value',$table_name);
        $data=gaz_dbi_fetch_array($rs);
        return $data['value'];
    }

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['this_date_Y']=date("Y");
    $form['this_date_M']=date("m");
    $form['this_date_D']=date("d");
    if (!isset($_GET['di'])) {
       $form['date_ini_D']=1;
       $form['date_ini_M']=1;
       $form['date_ini_Y']=date("Y");
    } else {
       $form['date_ini_D']=intval(substr($_GET['di'],0,2));
       $form['date_ini_M']=intval(substr($_GET['di'],2,2));
       $form['date_ini_Y']=intval(substr($_GET['di'],4,4));
    }
    if (!isset($_GET['df'])) {
       $form['date_fin_D']=date("d");
       $form['date_fin_M']=date("m");
       $form['date_fin_Y']=date("Y");
    } else {
       $form['date_fin_D']= intval(substr($_GET['df'],0,2));
       $form['date_fin_M']= intval(substr($_GET['df'],2,2));
       $form['date_fin_Y']= intval(substr($_GET['df'],4,4));
    }
    if ( !isset($_GET['ric']) ) {
        $form['ric'] = "";
    }
    if (isset($_GET['id'])) {
       $item=gaz_dbi_get_row($gTables['artico'],'codice',substr($_GET['id'],0,32));
       $form['art_ini']=$item['codice'];
       $form['art_fin']=$item['codice'];
       $form['cm_ini']=$item['catmer'];
       $form['cm_fin']=$item['catmer'];
    }  else {
       if (isset($_GET['ai'])) {
          $form['art_ini']=substr($_GET['ai'],0,32);
       } else {
          $form['art_ini']=getExtremeValue($gTables['artico']);
       }
       if (isset($_GET['af'])) {
          $form['art_fin']=substr($_GET['af'],0,32);
       } else {
          $form['art_fin']=getExtremeValue($gTables['artico'],'MAX');
       }
       if (isset($_GET['ci'])) {
          $form['cm_ini']=intval($_GET['ci']);
       } else {
          $form['cm_ini']=getExtremeValue($gTables['catmer']);
       }
       if (isset($_GET['cf'])) {
          $form['cm_fin']=intval($_GET['cf']);
       } else {
          $form['cm_fin']=getExtremeValue($gTables['catmer'],'MAX');
       }
    }
    $form['search']['art_ini']='';
    $form['search']['art_fin']='';
} else { // accessi successivi
    $form['hidden_req']=htmlentities($_POST['hidden_req']);
    $form['ritorno']=$_POST['ritorno'];
    $form['date_ini_D']=intval($_POST['date_ini_D']);
    $form['date_ini_M']=intval($_POST['date_ini_M']);
    $form['date_ini_Y']=intval($_POST['date_ini_Y']);
    $form['date_fin_D']=intval($_POST['date_fin_D']);
    $form['date_fin_M']=intval($_POST['date_fin_M']);
    $form['date_fin_Y']=intval($_POST['date_fin_Y']);
    $form['this_date_Y']=intval($_POST['this_date_Y']);
    $form['this_date_M']=intval($_POST['this_date_M']);
    $form['this_date_D']=intval($_POST['this_date_D']);
    $form['cm_ini']=intval($_POST['cm_ini']);
    $form['cm_fin']=intval($_POST['cm_fin']);
    $form['art_ini']=substr($_POST['art_ini'],0,32);
    $form['art_fin']=substr($_POST['art_fin'],0,32);
    $form['ric']=substr($_POST['ric'],0,32);
	foreach($_POST['search'] as $k=>$v){
       $form['search'][$k]=$v;
    }
    if (isset($_POST['return'])) {
        header("Location: ".$form['ritorno']);
        exit;
    }
}

//controllo i campi
if (!checkdate( $form['this_date_M'],$form['this_date_D'],$form['this_date_Y']) ||
    !checkdate( $form['date_ini_M'], $form['date_ini_D'], $form['date_ini_Y']) ||
    !checkdate( $form['date_fin_M'], $form['date_fin_D'], $form['date_fin_Y'])) {
    $msg .='0+';
}
$utsexe= mktime(0,0,0,$form['this_date_M'],$form['this_date_D'],$form['this_date_Y']);
$utsini= mktime(0,0,0,$form['date_ini_M'],$form['date_ini_D'],$form['date_ini_Y']);
$utsfin= mktime(0,0,0,$form['date_fin_M'],$form['date_fin_D'],$form['date_fin_Y']);
if ($utsini > $utsfin) {
    $msg .='1+';
}
if ($utsexe < $utsfin) {
    $msg .='2+';
}
if (strcasecmp($form['art_ini'],$form['art_fin'])>0) {
    $msg .='3+';
}
if ($form['cm_ini'] > $form['cm_fin']) {
    $msg .='4+';
}
// fine controlli

if (isset($_POST['print']) && $msg=='') {
    if ($form['art_fin']==0){
        $form['art_fin']==$form['art_ini'];
    }
    $_SESSION['print_request']=array('script_name'=>'stampa_schart',
                                     'ai'=>$form['art_ini'],
                                     'af'=>$form['art_fin'],
                                     'ci'=>$form['cm_ini'],
                                     'cf'=>$form['cm_fin'],
                                     'ri'=>date("dmY",$utsini),
                                     'rf'=>date("dmY",$utsfin),
                                     'ds'=>date("dmY",$utsexe)
                                     );
    header("Location: sent_print.php");
    exit;
}

require("../../library/include/header.php");
$script_transl=HeadMain(0,array('calendarpopup/CalendarPopup'));
echo "<script type=\"text/javascript\">
var cal = new CalendarPopup();
var calName = '';
function setMultipleValues(y,m,d) {
     document.getElementById(calName+'_Y').value=y;
     document.getElementById(calName+'_M').selectedIndex=m*1-1;
     document.getElementById(calName+'_D').selectedIndex=d*1-1;
}
function setDate(name) {
  calName = name.toString();
  var year = document.getElementById(calName+'_Y').value.toString();
  var month = document.getElementById(calName+'_M').value.toString();
  var day = document.getElementById(calName+'_D').value.toString();
  var mdy = month+'/'+day+'/'+year;
  cal.setReturnFunction('setMultipleValues');
  cal.showCalendar('anchor', mdy);
}
</script>
";
echo "<form method=\"POST\" name=\"select\">\n";
echo "<input type=\"hidden\" value=\"".$form['hidden_req']."\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\" />\n";
//echo "<input type=\"hidden\" value=\"".$form['search']."\" name=\"search\" />\n";
$gForm = new magazzForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'];
echo "</div>\n";
?>
<div class="panel panel-info gaz-table-form div-bordered">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="date" class="col-xs-12 col-md-4 control-label"><?php echo $script_transl['date']; ?></label>
          <?php $gForm->CalendarPopup('this_date',$form['this_date_D'],$form['this_date_M'],$form['this_date_Y'],'FacetSelect',1); ?>
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="cm_ini" class="col-xs-12 col-md-4 control-label"><?php echo $script_transl['cm_ini']; ?></label>
          <?php $gForm->selectFromDB('catmer','cm_ini','codice',$form['cm_ini'],false,false,'-','descri','cm_ini'); ?>
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="cm_fin" class="col-xs-12 col-md-4 control-label"><?php echo $script_transl['cm_fin']; ?></label>
          <?php $gForm->selectFromDB('catmer','cm_fin','codice',$form['cm_fin'],false,false,'-','descri','cm_fin'); ?>
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="art_ini" class="col-xs-12 col-md-4 control-label"><?php echo $script_transl['art_ini']; ?></label>
          <?php $gForm->selItem('art_ini',$form['art_ini'],$form['search']['art_ini'],$script_transl['mesg'],$form['hidden_req']); ?>
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="art_fin" class="col-xs-12 col-md-4 control-label"><?php echo $script_transl['art_fin']; ?></label>
          <?php $gForm->selItem('art_fin',$form['art_fin'],$form['search']['art_fin'],$script_transl['mesg'],$form['hidden_req']); ?>
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="art_fin" class="col-xs-12 col-md-4 control-label">Articolo pers.(% jolly)</label>
          <input name="ric" value="<?php echo $form['ric']; ?>"/>
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="date_ini" class="col-xs-12 col-md-4 control-label"><?php echo $script_transl['date_ini']; ?></label>
          <?php $gForm->CalendarPopup('date_ini',$form['date_ini_D'],$form['date_ini_M'],$form['date_ini_Y'],'FacetSelect',1); ?>
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label for="date_fin" class="col-xs-12 col-md-4 control-label"><?php echo $script_transl['date_fin']; ?></label>
          <?php $gForm->CalendarPopup('date_fin',$form['date_fin_D'],$form['date_fin_M'],$form['date_fin_Y'],'FacetSelect',1); ?>
        </div>
      </div>
    </div><!-- chiude row  -->
    <div class="text-center FacetFooterTD col-xs-12">
    <input type="submit" class="btn btn-info" accesskey="i" name="preview" value="<?php echo $script_transl['view']; ?>" tabindex="100" >
    </div><!-- chiude row  -->
  </div>
</div>
<?php
$date_ini =  sprintf("%04d%02d%02d",$form['date_ini_Y'],$form['date_ini_M'],$form['date_ini_D']);
$date_fin =  sprintf("%04d%02d%02d",$form['date_fin_Y'],$form['date_fin_M'],$form['date_fin_D']);
if (isset($_POST['preview']) and $msg=='') {
	$hrefdoc = json_decode(gaz_dbi_get_row($gTables['config'], 'variable', 'report_movmag_ref_doc')['cvalue']);
	$rshref=get_object_vars($hrefdoc);
	$m=getMovements($form['cm_ini'],$form['cm_fin'],$form['art_ini'],$form['art_fin'],$date_ini,$date_fin);
	echo '<div class="table-responsive"><table class="table table-striped">';
	if (sizeof($m) > 0) {
    $ctr_mv='';
    $ctrl_id=0;
    $trsl=array_keys($script_transl['header']);
    $th='<tr><th>'.$trsl[0].'</th><th>'.$trsl[1].'</th><th>'.$trsl[2].'</th><th>'.$trsl[3].'</th><th class="text-right">'.$trsl[4].'</th><th class="text-right">'.$trsl[5].'</th><th>'.$trsl[6].'</th><th>'.$trsl[7].'</th><th class="text-right">'.$trsl[8].'</th><th class="text-right">'.$trsl[9].'</th><th class="text-right">'.$trsl[10].'</th></tr>';
    $ctr_di = '2000-01-01';
		foreach ($m AS $key => $mv) {
			// richiamo il file del modulo che ha generato il movimento di magazzino per avere le informazioni sul documento genitore
			require_once("../".$rshref[$mv['tipdoc']]."/prepare_ref_doc_movmag.php");
			$funcn=preg_replace('/[0-9]+/', '', $rshref[$mv['tipdoc']]);
			$funcn=$funcn.'_prepare_ref_doc';
			$mv['id_rif']=($mv['id_rif']==0 && $mv['id_orderman']>0 && $mv['tipdoc']=="PRO")?$mv['id_orderman']:$mv['id_rif'];
			$mv['id_rif']=($mv['id_rif']==0 && $mv['tipdoc']=="MAG")?$mv['id_mov']:$mv['id_rif'];
			$docdata=$funcn($mv['tipdoc'],$mv['id_rif']);
      if ($ctr_mv != $mv['artico']) {
        if (!empty($ctr_mv)) {
          echo '<tr><td colspan=11></td></tr>';
          $sum=0.00;
        }
        echo '<tr><td class="FacetDataTD text-center" colspan=11><b>'.$mv['artico']." - ".$mv['desart'].'</b></td></tr>';
        echo $th;
      }
      $magval= $gForm->getStockValue($mv['id_mov'],$mv['artico'],$mv['datreg'],$admin_aziend['stock_eval_method'],$admin_aziend['decimal_price']);
      $mval=end($magval);
      // se è un inventario allerto che esso deve essere registrato dopo qualsiasi altro movimento dello stesso giorno
      if ($mv['datreg']==$ctr_di) {
        echo '<tr><td colspan=11 class="text-center bg-danger text-danger">L\'INVENTARIO DI SOPRA È STATO ESEGUITO PRIMA DI ALTRI MOVIMENTI DELLO STESSO GIORNO, <b>MODIFICALO</b> ED EVENTUALMENTE CORREGGI IL VALORE</td></tr>';
      }
      echo '<tr>
            <td class="text-center">'.gaz_format_date($mv['datreg'])."</td>";
      echo "<td align=\"center\">".$mv['caumag'].'-'.substr($mv['descau'],0,20)."</td>";
      echo '<td align="center">'.($mv['desmag']==''?'Sede':substr($mv['desmag'],0,25))."</td>";
			if ($mv['id_orderman']>0){
				$mv['desdoc'].= ' '.$mv['desorderman'];
			}
      echo '<td>';
			if (isset($hrefdoc->{$mv['tipdoc']}) && $mv['id_rif'] > 0){ // vedi sopra quando si vuole riferire ad un documento genitore di un modulo specifo
          echo '<a href="'.$docdata['link'].'">'.substr($mv['desdoc'].' del '.gaz_format_date($mv['datdoc']).' - '.$mv['ragso1'].' '.$mv['ragso2'],0,85);
			} else {
        //var_dump($mv);
				echo '<a href="admin_movmag.php?id_mov='.$mv["id_mov"].'&Update">'.substr($mv['desdoc'].' del '.gaz_format_date($mv['datdoc']).' - '.$mv['ragso1'].' '.$mv['ragso2'],0,85);
			}
			if (intval($mv['id_lotmag'])>0){
				echo " lotto: ",$mv['id_lotmag'],"-",$mv['identifier'];
			}
			echo '</a></td>';
      echo "<td align=\"right\">".number_format($mv['prezzo'],$admin_aziend['decimal_price'],',','.')."</td>";
      echo "<td align=\"right\">".$mv['unimis']."</td>\n";
      echo '<td class="text-right">'.gaz_format_quantity($mv['quanti']*$mv['operat'],1,$admin_aziend['decimal_quantity'])."</td>";
      if ($mv['operat']==1) {
        echo "<td align=\"right\">".number_format($mv['prezzo']*$mv['quanti'],$admin_aziend['decimal_price'],',','')."</td><td></td>";
      } else {
        echo "<td></td><td align=\"right\">".number_format($mv['prezzo']*$mv['quanti'],$admin_aziend['decimal_price'],',','')."</td>";
      }
      echo "<td align=\"right\">".($mval?number_format($mval['q_g'],$admin_aziend['decimal_price'],',','.'):'')."</td>";
      echo "<td align=\"right\">".($mval?number_format($mval['v_g'],$admin_aziend['decimal_price'],',','.'):'')."</td>";
      echo "</tr>";
      $ctr_mv = $mv['artico'];
      $ctr_di = ($mv['caumag']== 99)?$mv['datreg']:'2000-01-01';
    }
    echo '<tr><td colspan=11 class="FacetFooterTD text-center"><input class="btn btn-warning" type="submit" name="print" value="'.$script_transl['print'].'"></td></tr>';
	}
	echo "</table></div></form>";
}
?>
<?php
require("../../library/include/footer.php");
?>
