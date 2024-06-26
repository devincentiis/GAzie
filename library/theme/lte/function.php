<?php

function bc_get_current_path( $posizione ) {
    $intpos=0;
    $pos="";
    $found=false;
    foreach ( $posizione as $posizione_modulo ) {
        if ( $posizione_modulo == "modules" || $found) {
            $found=true;
            $intpos++;
            $pos .= $posizione_modulo.'/';
        }
    }
    $pos = rtrim($pos,"/");

    if ( strpos($pos, "?") ) {
        $pos = explode ("?", $pos);
        if ( is_array($pos) ) $pos = $pos[0];
    }
    return $pos;
}

function get_rref_type($value) {
    if ( stristr($value, "report_") ) return "fa-list";
    if ( stristr($value, "admin_") ) return "fa-pencil";
    if ( stristr($value, "select_") ) return "fa-print";
    if ( stristr($value, "accounting_") ) return "fa-suitcase";
}

function pulisci_rref_name($value) {
    $value = str_replace("Gestione dei", "", $value);
    $value = str_replace("Gestione degli", "", $value);
    $value = str_replace("Gestione", "", $value);
    $value = str_replace("Emissione di", "", $value);
    $value = str_replace("Emissione", "", $value);
    $value = str_replace("Visualizzazione e", "", $value);
    $value = str_replace("Visualizzazione", "", $value);
    $value = str_replace("Lista dei", "", $value);
    $value = str_replace("Selezione e", "", $value);
    $value = str_replace("Genera i", "", $value);
    //$value = ucfirst($value);
    return substr( $value, 0, 28);
}

function printCheckbox( $Caption, $varName, $Descrizione ) {
    global $gTables, $form;
    $config = new UserConfig;

    $admin_config = $config->getValue($varName);
    echo "<div class='form-group'>";
    echo "<label class='control-sidebar-subheading'>";
    echo $Caption;
    if ( $admin_config!="false" ) {
        $val = "checked='".$admin_config."'";
    } else {
        $val="";
    }
    echo "<input type='checkbox' hint='".$Descrizione."' class='pull-right' name='".$varName."' ".$val." onclick='processForm(this)' />";
    echo "</label><p>".$Descrizione."</p></div>";
}

function pastelColors() {
    $r = dechex(round(((float) rand() / (float) getrandmax()) * 127) + 127);
    $g = dechex(round(((float) rand() / (float) getrandmax()) * 127) + 127);
    $b = dechex(round(((float) rand() / (float) getrandmax()) * 127) + 127);
    return $r . $g . $b;
}

function submenu($array, $index, $sub="") {
    global $admin_aziend;
    if(!is_array($array)) { return ;}
    $numsub = 0;
    foreach($array as $i => $mnu) {
        if(!is_array($mnu)) {continue;}
    $submnu = '';
    if ($numsub === 0) {
            echo "<ul class=\"treeview-menu\">";
        }
	if (count($mnu)>6) {
            if ( $admin_aziend["Abilit"]>=$mnu["m2_ackey"] ) {
            echo "<li>";
            if ( $mnu["name"]=="Azienda" ) {
                $sub = '<a href="'. $mnu["link"] .'">Modifica '.$submnu.stripslashes($mnu["name"]);
            } else if ( $mnu["name"]=="Lista delle produzioni" ) {
                $sub = '<a href="'. $mnu["link"] .'">'.$submnu.stripslashes($mnu["name"]);
            } else if ( $mnu["name"]!="Documentazione" ) {
                $sub = '<a href="'. $mnu["link"] .'">Lista '.$submnu.stripslashes($mnu["name"]);
            } else {
                $sub = '<a href="'. $mnu["link"] .'">'.$submnu.stripslashes($mnu["name"]);
            }
            echo "  <a href=\"#\" hint=\"".$submnu.stripslashes($mnu["name"])."\">". $submnu.stripslashes($mnu["name"]);
            echo "      <i class=\"fa fa-angle-left pull-right\"></i>";
            echo "  </a>";
            submenu($mnu, 1, $sub);
            $sub="";
            echo "</li>";
            }
        } else {
            if ( isset($mnu["m2_ackey"])  ) {
                if ( $admin_aziend["Abilit"]>=$mnu["m2_ackey"] ) {
                    if ( $sub!="" ) {
                        echo "<li>$sub</a></li>";
                        $sub="";
                    }
                    echo "<li >";
                    echo "  <a href=\"". $mnu['link'] ."\">". $submnu.stripslashes($mnu['name']) ."</a>";
                    echo "</li>";
                }
            }
            if ( isset($mnu["m3_ackey"]) ) {
                if ( $admin_aziend["Abilit"]>=$mnu["m3_ackey"] ) {
                    if ( $sub!="" ) {
                        echo "<li>$sub</a></li>";
                        $sub="";
                    }
                    echo "<li >";
                    echo "  <a href=\"". $mnu['link'] ."\">". $submnu.stripslashes($mnu['name']) ."</a>";
                    echo "</li>";
                }
            }
        }
	$numsub++;
    }
    if ($numsub > 0) {
        echo "    </ul>";
    }
}

function HeadMain($idScript = '', $jsArray = '', $alternative_transl = false, $cssArray = '') {
    global $module, $admin_aziend, $radix, $scriptname, $gTables, $mod_uri, $debug_active;
    if (is_array($jsArray)) {
        foreach ($jsArray as $v) {
            echo '			<script type="text/javascript" src="../../js/' . $v . '.js"></script>';
        }
    }
    if (is_array($cssArray)) {
        foreach ($cssArray as $v) {
            echo '			<link rel="stylesheet" type="text/css" href="../../modules/' . $v . '">';
        }
    }
    $result = getAccessRights($_SESSION["user_name"], $_SESSION['company_id']);
    $acc_excluded = [];
    if (gaz_dbi_num_rows($result) > 0) {
        // creo l'array associativo per la generazione del menu con JSCookMenu
        $ctrl_m1 = 0;
        $ctrl_m2 = 0;
        $ctrl_m3 = 0;
        $menuArray = array();
        $transl = array();
        while ($row = gaz_dbi_fetch_array($result)) {
            $chkes =  is_string($row['custom_field'])? json_decode($row['custom_field']):false;
            $path3 = is_string($row['m3_link'])?parse_url($row['m3_link'], PHP_URL_PATH):'';
            $nfr3 = basename($path3,'.php');
            if (isset($chkes->excluded_script) && in_array($nfr3,$chkes->excluded_script)) {
              $row['m3_link'] = '';
              $acc_excluded[] = $nfr3;
            }
            $path2 = parse_url($row['m2_link'], PHP_URL_PATH);
            $nfr2 = basename($path2,'.php');
            if (isset($chkes->excluded_script) && in_array($nfr2,$chkes->excluded_script)) {
              $row['m2_link'] = '../../..'.$_SERVER['PHP_SELF'];
              $acc_excluded[] = $nfr2;
            }
            if ($row['access'] == 3) {
                if ($ctrl_m1 != $row['m1_id']) {
                    if ( file_exists ( "../../modules/" . $row['name'] . "/menu.".$admin_aziend['lang'].".php" ) )
						require("../../modules/" . $row['name'] . "/menu.".$admin_aziend['lang'].".php");
                }
                if ($row['name'] == $module) {
                    $row['weight'] = 0;
                    if ($row['m3_link'] == $scriptname) {
                        $title_from_menu = $transl[$row['name']]['m3'][$row['m3_trkey']][0];
                    }

                    if ($ctrl_m2 != $row['m2_id'] and $ctrl_m1 != $row['m1_id']) {
                        require("../../modules/" . $row['name'] . "/lang.".$admin_aziend['lang'].".php");
                        if (isset($strScript[$scriptname])) { // se Ã¨ stato tradotto lo script lo ritorno al chiamante
                            $translated_script = $strScript[$scriptname];
                            if (isset($translated_script['title'])) {
                                $title_from_menu = $translated_script['title'];
                            }
                        }
                    }
                }
                if (isset($row['m3_id']) and $row['m3_id'] > 0) { // Ã¨ un menu3
                    if ($ctrl_m2 != $row['m2_id'] and $ctrl_m1 != $row['m1_id']) { // Ã¨ pure il primo di menu2 e menu1
                        $menuArray[$row['weight']] = array('link' => '../' . $row['name'] . '/' . $row['link'],
                            'icon' => $row['icon'],
                            'name' => $transl[$row['name']]['name'],
                            'title' => $transl[$row['name']]['title'],
                            'm1_ackey' => $row['m1_ackey'],
                            'class' => $row['class']);
                        $menuArray[$row['weight']][$row['m2_weight']] = array('link' => '../' . $row['name'] . '/' . $row['m2_link'],
                            'icon' => '../' . $row['name'] . '/' . $row['m2_icon'],
                            'name' => $transl[$row['name']]['m2'][$row['m2_trkey']][1],
                            'title' => $transl[$row['name']]['m2'][$row['m2_trkey']][0],
                            'm2_ackey' => $row["m2_ackey"],
                            'class' => $row['m2_class']);
                    } elseif ($ctrl_m2 != $row['m2_id']) { // Ã¨ solo il primo di menu2
                        $menuArray[$row['weight']][$row['m2_weight']] = array('link' => '../' . $row['name'] . '/' . $row['m2_link'],
                            'icon' => '../' . $row['name'] . '/' . $row['m2_icon'],
                            'name' => $transl[$row['name']]['m2'][$row['m2_trkey']][1],
                            'title' => $transl[$row['name']]['m2'][$row['m2_trkey']][0],
                            'm2_ackey' => $row["m2_ackey"],
                            'class' => $row['m2_class']);
                    }
                    if (!empty($row['m3_link'])){
                      $menuArray[$row['weight']][$row['m2_weight']][$row['m3_weight']] = array('link' => '../' . $row['name'] . '/' . $row['m3_link'],
                        'icon' => '../' . $row['name'] . '/' . $row['m3_icon'],
                        'name' => $transl[$row['name']]['m3'][$row['m3_trkey']][1],
                        'title' => $transl[$row['name']]['m3'][$row['m3_trkey']][0],
                        'm3_ackey' => $row["m3_ackey"],
                        'class' => $row['m3_class']);
                    }
                } elseif ($ctrl_m1 != $row['m1_id']) { // Ã¨ il primo di menu2
                    $menuArray[$row['weight']] = array('link' => '../' . $row['name'] . '/' . $row['link'],
                        'icon' => $row['icon'],
                        'name' => $transl[$row['name']]['name'],
                        'title' => $transl[$row['name']]['title'],
                        'm1_ackey' => $row['m1_ackey'],
                        'class' => $row['class']);
                    $menuArray[$row['weight']][$row['m2_weight']] = array('link' => '../' . $row['name'] . '/' . $row['m2_link'],
                        'icon' => '../' . $row['name'] . '/' . $row['m2_icon'],
                        'name' => $transl[$row['name']]['m2'][$row['m2_trkey']][1],
                        'title' => $transl[$row['name']]['m2'][$row['m2_trkey']][0],
                        'm2_ackey' => $row["m2_ackey"],
                        'class' => $row['m2_class']);
                } else { // non Ã¨ il primo di menu2
                    $menuArray[$row['weight']][$row['m2_weight']] = array('link' => '../' . $row['name'] . '/' . $row['m2_link'],
                        'icon' => '../' . $row['name'] . '/' . $row['m2_icon'],
                        'name' => $transl[$row['name']]['m2'][$row['m2_trkey']][1],
                        'title' => $transl[$row['name']]['m2'][$row['m2_trkey']][0],
                        'm2_ackey' => $row["m2_ackey"],
                        'class' => $row['m2_class']);
                }
            }
            $ctrl_m1 = $row['m1_id'];
            $ctrl_m2 = $row['m2_id'];
            $ctrl_m3 = $row['m3_id'];
        }
        if (!empty($idScript)) {
            if (is_array($idScript)) { // $idScript dev'essere un array con index [0] per il numero di menu e index[1] per l'id dello script
                if ($idScript[0] == 2) {
                    echo '			&raquo;' . $transl[$module]['m2'][$idScript[1]][0];
                } elseif ($idScript[0] == 3) {
                    echo '			&raquo;' . $transl[$module]['m3'][$idScript[1]][0];
                }
            } elseif ($idScript > 0) {
                echo '			&raquo;' . $transl[$module]['m3'][$idScript][0];
            }
        } elseif (isset($title_from_menu)) {
            //echo '			&raquo;' . $title_from_menu;
        }

				$accTitle=$admin_aziend['ragso1'] . '» ' . $menuArray[0]['title'];
        if (!empty($idScript)) {
					if (is_array($idScript)) { // $idScript dev'essere un array con index [0] per il numero di menu e index[1] per l'id dello script
            if ($idScript[0] == 2) {
              $accTitle.= '» ' . $transl[$module]['m2'][$idScript[1]][0];
            } elseif ($idScript[0] == 3) {
              $accTitle.= '» ' . $transl[$module]['m3'][$idScript[1]][0];
            }
          } elseif ($idScript > 0) {
            $accTitle.= '» ' . $transl[$module]['m3'][$idScript][0];
          }
        } elseif (isset($title_from_menu)) {
          $accTitle.= '» ' . $title_from_menu;
        }
    $i = 0;
    $colors = array ( "#00CD66", "#DC143C", "#20B2AA", "#FAFAD2", "#CD8500", "#EEEE00", "#B7B7B7", "#20B2AA", "#00FF7F", "#FFDAB9", "#006400", "#d3f5c6", "#673723", "#6b0490", "#1527cc", "#0cacd8" );$icons = array ("fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle","fa fa-circle");
    foreach ($menuArray as $link) {
      if ( $admin_aziend["Abilit"]>=$link["m1_ackey"] ) {
				$rsm = explode("/",$link['link']);
				$act=($module==$rsm[1])?' active':'';
        echo '<li class="treeview'.$act.'">';
        echo "  <a href=\"". $link['link'] ."\">\n";
        echo "    <img height=\"24\" src=\"../".substr($link['icon'],0,-4)."/".$link['icon']."\">\n";
        echo "      <span>". $link['name'] ."</span>\n";
        echo "    <i class=\"fa fa-angle-left pull-right\"></i>\n";
        echo "  </a>\n";
        submenu($link, $i);
        echo "          </li>\n";
      }
      $i++;
    }
?>
    </ul>
    </section>
    </aside>
</form>
    <div class="content-wrapper">
      <section class="content-header">
    <?php
    global $gTables, $module;
    $pos="";
    $ci_sono_tasti_nel_menu=false;
    $posizione = explode( '/',$_SERVER['REQUEST_URI'] );
    $pos = bc_get_current_path($posizione);
    $posizione = array_pop( $posizione );
    $res_pos = gaz_dbi_dyn_query("*", $gTables['breadcrumb'], ' file="'.$pos.'" AND exec_mode=0', ' id_bread',0,999);

    if ( gaz_dbi_num_rows($res_pos)>0 ) {
        $row = gaz_dbi_fetch_array($res_pos);
        echo "<ol class='breadcrumb'>";
        $yeslink=false;
        while ( $row = gaz_dbi_fetch_array($res_pos) ) {
          echo "<li><a href='".$row['link']."'>".$row['titolo']."</a></li>";
          $yeslink=true;
        }
        echo "<li><a href='../../modules/root/admin.php'><i class='fa fa-home'></i></a>&nbsp;<a href='../../modules/root/admin_breadcrumb.php?url=".$pos."'><i class='glyphicon glyphicon-cog'></i></a></li>";
        echo "</ol>";
        if ($yeslink) echo "<h1><a href='".$row['link']."'>".$row['titolo']."</a></h1>";
    } else {
		  if ($pos=='modules/root/admin.php') { // sulla home page
				echo "<a href='../../modules/root/admin_dash.php'><i class='glyphicon glyphicon-cog'></i></a>";
				echo "</ol>";
		  } else {
        $result    = gaz_dbi_dyn_query("*", $gTables['menu_module'] , ' link="'.$posizione.'" ',' id',0,1);
        if (!gaz_dbi_num_rows($result) > 0) {
          $posizionex = explode ("?",$posizione );
          $result    = gaz_dbi_dyn_query("*", $gTables['menu_module'] , ' link="'.$posizionex[0].'" ',' id',0,1);
        }
        $riga = gaz_dbi_fetch_array($result);
        if ($riga && $riga["id"]!="" ) { // siamo su una pagina di 2 livello nel menu principale
          $result2 = gaz_dbi_dyn_query("*", $gTables['menu_script'] , ' id_menu='.$riga["id"].' ','id',0);
          echo "<div><ol class=\"breadcrumb\">";
          while ($r = gaz_dbi_fetch_array($result2)) {
            $linkbase =  pathinfo($r['link'], PATHINFO_FILENAME);
            if ( $admin_aziend["Abilit"]>=$r["accesskey"] && !in_array($linkbase,$acc_excluded) ) echo '<li><a href="'.$r["link"].'">'.stripslashes ($transl[$module]["m3"][$r["translate_key"]]["1"]).'</a></li>';
          }
        } else { // siamo su una pagina di 3 livello nel menu principale
          $posizionexsez = explode ("&seziva",$posizione ); // sui report fatture/ddt aggiungo con js la sezione iva all'url per proporre quella corrente, questo fa si che non coincida con quanto sta sul db allora pulisco la referenza
          $result3    = gaz_dbi_dyn_query("*", $gTables['menu_script'] , ' link="'.$posizionexsez[0].'"',' id',0,1);
          if ( $ms = gaz_dbi_fetch_array($result3) ) { // disegno i bottoni di accesso alle funzioni di questa pagina
              $result4    = gaz_dbi_dyn_query($gTables['menu_script'].".*,".$gTables['menu_module'].".link AS lmm,".$gTables['menu_module'].".translate_key AS tmm ", $gTables['menu_script']. " LEFT JOIN ".$gTables['menu_module']." ON ".$gTables['menu_script'].".id_menu = ".$gTables['menu_module'].".id LEFT JOIN ".$gTables['module']." ON ".$gTables['menu_module'].".id_module = ".$gTables['module'].".id", $gTables['menu_script'].".id_menu =".$ms['id_menu']." AND ".$gTables['module'].".name = '".$module."'",'name',0,99);
              echo '<div><ol class="breadcrumb">';
              $first=true;
              while ($r = gaz_dbi_fetch_array($result4)) {
                if ($first) echo '<li><b class="FacetFooterTD"><a href="'.$r["lmm"].'">'.$transl[$module]["m2"][$r["tmm"]]["1"].'</a></b></li>';
                $linkbase =  pathinfo($r['link'], PATHINFO_FILENAME);
                if ( $admin_aziend["Abilit"]>=$r["accesskey"] && !in_array($linkbase,$acc_excluded)) echo '<li><a href="'.$r["link"].'">'.stripslashes ($transl[$module]["m3"][$r["translate_key"]]["1"]).'</a></li>';
                $first=false;
              }
          }
        }
        echo "<li><a href=\"../../modules/root/admin.php\"><i class=\"fa fa-home\"></i></a>&nbsp;<a href='../../modules/root/admin_breadcrumb.php?url=".$pos."'><i class='glyphicon glyphicon-cog'></i></a></li>";
        echo "</ol>";
        // mostra il titolo se siamo su una pagina di secondo livello
        echo "<h1>";
        $m23=($riga && $riga["id"]!="")?'m2':'m3';
        if (isset($riga)){
          echo "&nbsp;".stripslashes($transl[$module][$m23][$riga["translate_key"]][0]);
        }
        echo "</h1>";
		  }
    }
?>
      </section>
      <section class="content">
<?php
    }
    if (!isset($translated_script)) {
        if ($alternative_transl) { // se e' stato passato il nome dello script sul quale mi devo basare per la traduzione
            $translated_script = $strScript[$alternative_transl . '.php'];
        } else {
            $translated_script = array($module);
        }
    }
    require("../../language/".$admin_aziend['lang']."/menu.inc.php");
    echo '<script type="text/javascript">';
    echo '$("#title_from_menu").text("'.$accTitle.'")';
		echo '
		 countclick = 0;
		 function chkSubmit() {
            if ( window.history.replaceState ) {
                window.history.replaceState( null, null, window.location.href );
            }
			if(countclick > 0) {
				alert("' . $strCommon['wait_al'] . '");
				document.getElementById(\'preventDuplicate\').disabled=true;
				return false;
			} else {
				var alPre = document.getElementById(\'confirmSubmit\').value.toString();
				if (alPre) {
					var conf = confirm (alPre);
					if (!conf) {
						document.getElementById(\'preventDuplicate\').disabled=true;
						return true;
					}
				}
				countclick++;
				document.getElementById(\'preventDuplicate\').hidden=true;
				return true;
			}
		 }
		 </script>
         <!--<div class="container" role="main">-->';
         return ($strCommon + $translated_script);
}

function get_transl_referer($rlink) {
            global $gTables;
            $clink = explode('/', $rlink);
            $n1 = gaz_dbi_get_row($gTables['module'], 'link', end($clink));
            if ($n1) {
                include "../../modules/" . $clink[1] . "/menu.italian.php";
                return $clink[1] . '-m1-' . $n1['id'];
            } else {
                $n2 = gaz_dbi_get_row($gTables['menu_module'], 'link', end($clink));
                if ($n2) {
                    include "../../modules/" . $clink[1] . "/menu.italian.php";
                    return $clink[1] . '-m2-' . $n2['translate_key'];
                } else {
                    $n3 = gaz_dbi_get_row($gTables['menu_script'], 'link', end($clink));
                    if ($n3) {
                        include "../../modules/" . $clink[1] . "/menu.italian.php";
                        return $clink[1] . '-m3-' . $n3['translate_key'];
                    } else { // non l'ho trovato neanche nel m3, provo sui file di traduzione
                        include "../../modules/" . $clink[1] . "/lang.italian.php";
                        // tento di risalire allo script giusto
                        $n_scr = explode('?', end($clink));
                        if (isset($strScript[$n_scr[0]])) { // ho trovato una traduzione per lo script
                            if (isset($strScript[$n_scr[0]]['title'])) { // ho trovato una traduzione per lo script con index specifico
                                if (is_array($strScript[$n_scr[0]]['title'])) {
                                    return $clink[1] . '-sc-' . $n_scr[0] . '-title-' . array_shift(array_slice($strScript[$n_scr[0]]['title'], 0, 1));
                                } else {
                                    return $clink[1] . '-sc-' . $n_scr[0] . '-title';
                                }
                            } elseif (isset($strScript[$n_scr[0]][0])) { // ho trovato una traduzione per lo script nel primo elemento
                                if (is_array($strScript[$n_scr[0]][0])) {
                                    return $clink[1] . '-sc-' . $n_scr[0] . '-0-' . array_shift(array_slice($strScript[$n_scr[0]][0], 0, 1));
                                } else {
                                    return $clink[1] . '-sc-' . $n_scr[0] . '-0';
                                }
                            } else { // non ho trovato nulla nemmeno sui file tipo lang.english.php
                                return $clink[1] . '-none-script';
                            }
                        } else { // non c'è traduzione per questo script
                            return $clink[1] . '-none-script_menu';
                        }
                    }
                }
            }
    }

?>
