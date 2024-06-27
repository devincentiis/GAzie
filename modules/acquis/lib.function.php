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

class acquisForm extends GAzieForm {

    function selectSupplier($name, $val, $strSearch = '', $val_hiddenReq = '', $mesg='', $class = 'FacetSelect') {
        global $gTables, $admin_aziend;
        $anagrafica = new Anagrafica();
        if ($val > 100000000) { //vengo da una modifica della precedente select case quindi non serve la ricerca
            $partner = $anagrafica->getPartner($val);
            echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
            echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr($partner['ragso1'], 0, 8) . "\">\n";
            echo "\t<input type=\"submit\" value=\"" . $partner['ragso1'] . " " . $partner["ragso2"] . " " . $partner["citspe"] . " (" . $partner["codice"] . ")\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
        } else {
            if (strlen($strSearch) >= 2) { //sto ricercando un nuovo partner
                echo "\t<select tabindex=\"1\" name=\"$name\" class=\"FacetSelect\" onchange=\"this.form.hidden_req.value='$name'; this.form.submit();\">\n";
                echo "<option value=\"0\"> ---------- </option>";
                $partner = $anagrafica->queryPartners("*", "codice LIKE '" . $admin_aziend['masfor'] . "%' AND codice >" . intval($admin_aziend['masfor'] . '000000') . "  AND ragso1 LIKE '" . addslashes($strSearch) . "%'", "codice ASC");
                if (count($partner) > 0) {
                    foreach ($partner as $r) {
                        $selected = '';
                        if ($r['codice'] == $val) {
                            $selected = "selected";
                        }
                        echo "\t\t <option value=\"" . $r['codice'] . "\" $selected >" . $r['ragso1'] . " " . $r["ragso2"] . " " . $r["citspe"] . "</option>\n";
                    }
                    echo "\t </select>\n";
                } else {
                    $msg = $mesg[0];
                }
            } else {
                $msg = $mesg[1];
                echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
            }
            echo "\t<input tabindex=\"2\" type=\"text\" id=\"search_$name\" name=\"search[$name]\" value=\"" . $strSearch . "\" maxlength=\"15\"  class=\"FacetInput\">\n";
            if (isset($msg)) {
                echo "<input type=\"text\" style=\"color: red; font-weight: bold;\"  disabled value=\"$msg\">";
            }
            // echo "\t<input tabindex=\"3\" type=\"image\" align=\"middle\" name=\"search_str\" src=\"../../library/images/cerbut.gif\">\n";
            /** ENRICO FEDELE */
            /* Cambio l'aspetto del pulsante per renderlo bootstrap, con glyphicon */
            echo '<button type="submit" class="btn btn-default btn-sm" name="search_str" tabindex="3"><i class="glyphicon glyphicon-search"></i></button>';
            /** ENRICO FEDELE */
        }
    }

    function selAmmortamentoMin($nameFileXML, $name, $gruppo_specie, $val) {
        $refresh = '';
        if (file_exists('../../library/include/' . $nameFileXML)) {
            $xml = simplexml_load_file('../../library/include/' . $nameFileXML);
        } else {
            exit('Failed to open: ../../library/include/' . $nameFileXML);
        }
        echo "\t <select id=\"$name\" name=\"$name\" tabindex=13   class=\"col-sm-8 small\" style=\"max-width:300px\" onchange=\"this.form.hidden_req.value='ss_amm_min'; this.form.submit();\">\n";
        foreach ($xml->gruppo as $vg) {
            foreach ($vg->specie as $v) {
                $g_s = $vg->gn[0] . $v->ns[0];
                if ($g_s == $gruppo_specie) {
                    $i = 0;
                    echo '<option value="999"> ---------- </option>';
                    foreach ($v->ssd as $v2) {
                        $selected = '';
                        if ($val == $i) {
                            $selected = 'selected';
                        }
                        echo "\t\t <option value=\"" . $i . "\" $selected >" . $v->ssrate[$i] . '% ' . $v2 . "</option>\n";
                        $i++;
                    }
                }
            }
        }
        echo "\t </select>\n";
    }

	function concileArtico($name,$search,$val) {
		global $gTables;
		$selopt=[''=>['bgc'=>'ffffff','des'=>'NON IN MAGAZZINO'],'Insert_from_db'=>['bgc'=>'adf04e','des'=>'CERCA ARTICOLO MAGAZZINO'],'Insert_New'=>['bgc'=>'f0ad4e','des'=>'INSERISCI COME NUOVO'],'Insert_W_lot'=>['bgc'=>'5bc0de','des'=>'INSERISCI NUOVO C/LOTTO'],'Insert_W_matr'=>['bgc'=>'f04ead','des'=>'INSERISCI NUOVO C/MATRICOLA']];
		$art = gaz_dbi_get_row($gTables['artico'], 'codice', $val);
		$acc = '<div class="col-xs-12">';
		if ($art) {
			$acc .= '<input type="submit" class="bg-info" tabindex="999" value="'.$art['descri'].'" name="change" onclick="this.form.hidden_req.value=\'change_'.$name.'\';" title="Cambia articolo">';
			$acc .= '<input type="hidden" name="search_'.$name.'" value="'.$art['descri'].'" />';
			$acc .= '<input type="hidden" id="'.$name.'" name="'.$name.'" value="'.$val.'">';
		} elseif($val=='Insert_from_db'){
			$acc .= '<input type="text" name="search_'.$name.'" artref="'.$name.'" class="search_artico" placeholder="Cerca articolo" value="' . $search . '"  maxlength="16" />';
			$acc .= '<input type="hidden" id="'.$name.'" name="'.$name.'" value="'.$val.'">';
		} else {
			$acc .= '<select id="'.$name.'" name="'.$name.'" onchange="this.form.submit();">';
			foreach($selopt as $k=>$v){
				$s=($k==$val)?' selected':'';
				$acc .= '<option value="'.$k.'" style="background-color:#'.$v['bgc'].';" '.$s.'>'.$v['des'].'</option>';
			}
			$acc .= '</select>';
		}
		$acc .= '</div>';
		return $acc;
	}


  function concile_id_order_row($varname,$val_codart,$val_selected,$class='small') {
     global $gTables;
	 $acc='';
	 // riprendo tutti i righi degli ordini relativi all'articolo e controllo che siano stati ricevuti
	 $query = 'SELECT * FROM `'.$gTables['rigbro']."` LEFT JOIN `".$gTables['tesbro']."` AS ".$gTables['rigbro'].".id_tes=".$gTables['tesbro'].".id_tes WHERE `tiprig` <=1 AND `codart` ='".$val_codart."' ORDER BY ".$gTables['rigbro'].".id_rig";
     $result = gaz_dbi_query($query);
     while ($r = gaz_dbi_fetch_array($result)) {
	 }
     $acc .= '<select id="'.$varname.'" name="'.$varname.'" class="'.$class.'">';
     $acc .= '<option value="" style="background-color:#5bc0de;">senza riferimento</option>';
     $result = gaz_dbi_query($query);
     while ($r = gaz_dbi_fetch_array($result)) {
         $selected = '';
         $setstyle = '';
         if ($r['id_rig'] == $val_selected) {
             $selected = " selected ";
             $setstyle = ' style="background-color:#5cb85c;" ';
         }
         $acc .= '<option class="small" value="'.$r['id_rig'].'"'.$selected.$setstyle.'>'.$r['codice'].'-'.substr($r['descri'],0,30).'</option>';
     }
     $acc .= '</select>';
	 return $acc;
  }

  function getOrderStatus($idtes){
		global $gTables;
		$acc[0]=false;
		$acc[1]=[];
		$acc[2]=[];
    $remains=false; // Almeno un rigo e' rimasto da evadere.
    $processed=false; // Almeno un rigo e' gia' stato evaso.
    $rb_r=gaz_dbi_dyn_query('*',$gTables['rigbro'],"id_tes=" . $idtes . " AND tiprig < 1",'id_tes DESC');
    while($rb=gaz_dbi_fetch_array($rb_r) ) {
      $da_evadere=$rb['quanti'];
      $evaso=0;
      $rd_r=gaz_dbi_dyn_query('*',$gTables['rigdoc'],"id_order=".$rb['id_rig'],'id_tes DESC');
      while($rd=gaz_dbi_fetch_array($rd_r)) {
        $acc[2][$rd['id_tes']]='y';
        $evaso+=$rd['quanti'];
        $processed=true;
      }
      if($evaso<$da_evadere ){
        $acc[1][]=$rb['id_rig'];
        $remains=true;
      }
    }
    if($remains&&!$processed){
      // non ho ancora ricevuto nulla
      $acc[0]=0;
    }elseif(!$processed){
      // non ci sono righi normali
      $acc[0]=false;
    }elseif($remains){
      // è parzialmente ricevuto
      $acc[0]=1;
    }else{
      // è completamente ricevuto
      $acc[0]=2;
    }
		return $acc;
	}

 	function CodiceFornitoreFromCodart($codart,$clfoco) { // restituisce il codice_fornitore di un articolo acquistato in precedenza dallo stesso fornitore, serve in fase di inserimento DdT manuale per consentire la successiva riconciliazione quando arriverà la fattura
		global $gTables;
    $rs_codice_fornitore=gaz_dbi_dyn_query('codice_fornitore',$gTables['rigdoc'].' LEFT JOIN '.$gTables['tesdoc'].' ON '.$gTables['rigdoc'].'.id_tes = '.$gTables['tesdoc'].'.id_tes',"codart='" . $codart . "' AND codice_fornitore <> '' AND tipdoc LIKE 'A%' AND ".$gTables['tesdoc'].'.clfoco = '.intval($clfoco),'id_rig DESC',0,1);
    $res=gaz_dbi_fetch_array($rs_codice_fornitore);
    if ($res){
      return $res;
    } else {
      return false;
    }
	}

 	function CodartFromCodiceFornitore($codice_fornitore,$clfoco) { // restituisce il codice articolo (codart) di un articolo acquistato in precedenza dallo stesso fornitore, serve in fase di acquisizione delle fattura elettroniche che non hanno
		global $gTables;
    $rs_codart=gaz_dbi_dyn_query('codart',$gTables['rigdoc'].' LEFT JOIN '.$gTables['tesdoc'].' ON '.$gTables['rigdoc'].'.id_tes = '.$gTables['tesdoc'].'.id_tes',"codice_fornitore='" . str_replace("'","",$codice_fornitore) . "' AND codart <> '' AND tipdoc LIKE 'A%' AND ".$gTables['tesdoc'].'.clfoco = '.intval($clfoco).' GROUP BY codart','id_rig DESC',0,2);
    $acc=[];
    while ( $res = gaz_dbi_fetch_array($rs_codart)) {
      $acc[]= $res;
    }
    // solo se ho trovato un solo codart allora mi trovo di fronte ad un fornitore che usa codici univoci e quindi affidabile
    if (count($acc)==1) {
      return $res;
    } else {
      return false;
    }
	}

}

function getLastOrdPrice($codart,$supplier) {
// restituisce l'ultimo prezzo d'acquisto dell'articolo al fornitore
	$r=false;
    global $gTables, $admin_aziend;
    $sqlquery = "SELECT " . $gTables['rigbro'] . ".* FROM " . $gTables['rigbro'] . "
          LEFT JOIN " . $gTables['tesbro'] . " ON " . $gTables['rigbro'] . ".id_tes =" . $gTables['tesbro'] . ".id_tes
          WHERE tipdoc = 'AOR' AND codart = '" . $codart . "' AND prelis >= 0.00001 AND clfoco = '" . $supplier . "' ORDER BY datemi DESC LIMIT 1";
    $result = gaz_dbi_query($sqlquery);
	$row = gaz_dbi_fetch_array($result);
	if($row){
		$r=$row;
	}
    return $r;
}
function CreateZipFAEacq($resultFAE,$email=""){// crea un file .zip contenente i file che gli vengono passati nell'array $resultFAE
	global $gTables, $admin_aziend;
	if (count($resultFAE) > 0) {
		$zip = new ZipArchive;
		$zipname = substr(date("Y-m-d-h-i-s")."_".str_replace(" ","-",addslashes($admin_aziend['ragso1'])), 0, 39).".zip";// il nome del pacchetto
		$zipnameurl=DATA_DIR."files/tmp/".$zipname;
		$res = $zip->open($zipnameurl, ZipArchive::CREATE);
		if ($res === TRUE) {
			foreach ($resultFAE as $resFAE){
				$fn_ori = DATA_DIR.'files/'.$admin_aziend['codice'].'/'.$resFAE['fattura_elettronica_original_name'];
				$zip->addFile($fn_ori,$resFAE['fattura_elettronica_original_name']);
				// aggiorno la testata della FAE
				gaz_dbi_query("UPDATE " . $gTables['tesdoc'] . " SET fattura_elettronica_zip_package = '".$zipname."' WHERE fattura_elettronica_original_name = '".$resFAE['fattura_elettronica_original_name']."'");
			}
			$zip->close();
			$file_url = $zipnameurl;
			if(file_exists($zipnameurl) && $email=="") {
				header("Location: download_acq_zip_package.php?fn=".$zipname);
			}elseif(file_exists($zipnameurl) && $email=="email") {
				header("Location: send_fae_acq_package.php?fn=".$zipname);
			} else {
				echo "Il pacchetto non esiste. Errore creazione zip";
			}
		} else {
			echo "Inizio creazione zip fallita";
		}
	}
}
?>
