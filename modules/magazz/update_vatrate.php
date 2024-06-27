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
$msg = '';

function getItems($rate_oby,$cm_ini,$cm_fin,$art_ini,$art_fin) {
        global $gTables,$admin_aziend;
        $m=array();
        if ($art_fin=='') {
              $art_fin='zzzzzzzzzzzzzzz';
        }
        $where=$gTables['artico'].".codice BETWEEN '$art_ini' AND '$art_fin' AND catmer BETWEEN $cm_ini AND ".$cm_fin.' AND aliiva = '.$rate_oby;
        //recupero gli articoli in base alle scelte impostate
        $rs=gaz_dbi_dyn_query ($gTables['artico'].'.*, '.$gTables['aliiva'].'.descri AS desiva',
                               $gTables['artico'].' LEFT JOIN '.$gTables['aliiva'].' ON '.$gTables['artico'].".aliiva = ".$gTables['aliiva'].'.codice',
                               $where,'catmer, '.$gTables['artico'].'.codice');
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
    $form['rate_new']=1;
    $form['rate_obj']=1;
    if (isset($_GET['id'])) {
       $item=gaz_dbi_get_row($gTables['artico'],'codice',substr($_GET['id'],0,15));
       $form['art_ini']=$item['codice'];
       $form['art_fin']=$item['codice'];
       $form['cm_ini']=$item['catmer'];
       $form['cm_fin']=$item['catmer'];
    }  else {
       if (isset($_GET['ai'])) {
          $form['art_ini']=substr($_GET['ai'],0,15);
       } else {
          $form['art_ini']=getExtremeValue($gTables['artico']);
       }
       if (isset($_GET['af'])) {
          $form['art_fin']=substr($_GET['af'],0,15);
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
    $form['rate_new']=substr($_POST['rate_new'],0,3);
    $form['rate_obj']=substr($_POST['rate_obj'],0,3);
    $form['cm_ini']=intval($_POST['cm_ini']);
    $form['cm_fin']=intval($_POST['cm_fin']);
    $form['art_ini']=substr($_POST['art_ini'],0,15);
    $form['art_fin']=substr($_POST['art_fin'],0,15);
    foreach($_POST['search'] as $k=>$v){
       $form['search'][$k]=$v;
    }
    if (isset($_POST['return'])) {
        header("Location: ".$form['ritorno']);
        exit;
    }
}

//controllo i campi
if (strcasecmp($form['art_ini'],$form['art_fin'])>0) {
    $msg .='1+';
}
if ($form['cm_ini'] > $form['cm_fin']) {
    $msg .='2+';
}
// fine controlli

if (isset($_POST['submit']) && $msg=='') {
  //Modifico l'aliquota IVA di tutti gli articoli selezionati...
  $m=getItems($form['rate_obj'],$form['cm_ini'],$form['cm_fin'],$form['art_ini'],$form['art_fin']);
  if (sizeof($m) > 0) {
		foreach ($m AS $key => $mv) {
            // questo e' troppo lento: gaz_dbi_put_row($gTables['artico'],'codice',$mv['codice'],$name_obj,$new_price);
            gaz_dbi_query ("UPDATE ".$gTables['artico']." SET aliiva = ".$form['rate_new']." WHERE codice = '".$mv['codice']."';");
        }
        header("Location:report_artico.php");
        exit;
  }
}

require("../../library/include/header.php");
$script_transl=HeadMain();
echo "<form method=\"POST\" name=\"select\">\n";
echo "<input type=\"hidden\" value=\"".$form['hidden_req']."\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\" />\n";
$gForm = new magazzForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'];
echo "</div>\n";
echo "<table class=\"Tsmall\" align=\"center\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="2" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
}
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['rate_obj']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectFromDB('aliiva','rate_obj','codice',$form['rate_obj'],false,false,'-','descri','rate_obj');
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['cm_ini']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectFromDB('catmer','cm_ini','codice',$form['cm_ini'],false,false,'-','descri','cm_ini');
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['cm_fin']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectFromDB('catmer','cm_fin','codice',$form['cm_fin'],false,false,'-','descri','cm_fin');
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['art_ini']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selItem('art_ini',$form['art_ini'],$form['search']['art_ini'],$script_transl['mesg'],$form['hidden_req']);
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['art_fin']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selItem('art_fin',$form['art_fin'],$form['search']['art_fin'],$script_transl['mesg'],$form['hidden_req']);
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['rate_new']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectFromDB('aliiva','rate_new','codice',$form['rate_new'],false,false,'-','descri','rate_new');
echo "</tr>\n";
echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
echo "<td align=\"left\">";
echo '<td align="right"><input type="submit" class="btn btn-warning" accesskey="i" name="preview" value="';
echo $script_transl['view'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table>\n";

if (isset($_POST['preview']) and $msg=='') {
  $m=getItems($form['rate_obj'],$form['cm_ini'],$form['cm_fin'],$form['art_ini'],$form['art_fin']);
  $new = gaz_dbi_get_row($gTables['aliiva'],'codice',$form['rate_new']);
  echo "<table class=\"Tmiddle table-striped table-responsive\">";
  if (sizeof($m) > 0) {
        if ($form['rate_new']=='0') {
           $name_bas='preacq';
        } elseif ($form['rate_new']=='web') {
           $name_bas='web_price';
        } else {
           $name_bas='preve'.$form['rate_new'];
        }
        if ($form['rate_obj']=='0') {
           $name_obj='preacq';
        } elseif ($form['rate_obj']=='web') {
           $name_obj='web_price';
        } else {
           $name_obj='preve'.$form['rate_obj'];
        }
        echo "<tr>";
        $linkHeaders=new linkHeaders($script_transl['header']);
        $linkHeaders->output();
        echo "</tr>";
        $ctr_mv=0;
		foreach ($m AS $key => $mv) {
      if ($mv['catmer']>$ctr_mv){
          $cm=gaz_dbi_get_row($gTables['catmer'],'codice',$mv['catmer']);
          echo "<tr><td class=\"FacetFieldCaptionTD\">".$mv['catmer'].' - '.$cm['descri']." &nbsp</td><td colspan=\"5\"></td></tr>\n";
      }
      echo "<tr><td></td>\n";
      echo "<td class=\"FacetDataTD\">".$mv['codice']." &nbsp;</td>";
      echo "<td class=\"FacetDataTD\">".$mv['descri']." &nbsp;</td>";
      echo "<td align=\"center\" class=\"FacetDataTD\">".$mv['unimis']." &nbsp;</td>\n";
      echo "<td align=\"center\" class=\"FacetDataTD\">".$mv['desiva']."</td>\n";
      echo "<td align=\"center\" class=\"FacetDataTD\">".$new['descri']."</tr>\n";
      $ctr_mv=$mv['catmer'];
    }
    echo "\t<tr class=\"FacetFieldCaptionTD\">\n";
    echo '<td colspan="6" align="right"><input class="btn btn-warning" type="submit" name="submit" value="';
    echo $script_transl['submit'];
    echo '">';
    echo "\t </td>\n";
    echo "\t </tr>\n";
  }
  echo "</table>";
  $form['hidden_req']='';

}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
