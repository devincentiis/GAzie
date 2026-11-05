<?php
// Tema adminlte_clean: AdminLTE 2 con layout pulito. Menu e breadcrumb come da HeadMain().
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


// voci del menu in formato AdminLTE (treeview): restituisce il markup <li> dei figli
function gzMenuItems($array) {
  global $admin_aziend;
  $items = '';
  if (!is_array($array)) { return $items; }
  foreach ($array as $mnu) {
    if (!is_array($mnu)) { continue; }
    if (count($mnu) > 7) { // secondo livello con terzo livello
      if ($admin_aziend["Abilit"] >= $mnu["m2_ackey"]) {
        $children = '';
        if (!empty($mnu["link"])) {
          $scriptname = pathinfo($mnu["link"], PATHINFO_FILENAME);
          if (!empty($mnu["menu"])) {
            $label = stripslashes($mnu["menu"]);
          } elseif (str_contains($scriptname, 'report') || str_contains($scriptname, 'list')) {
            $label = 'Lista ' . stripslashes($mnu["name"]);
          } else {
            $label = stripslashes($mnu["name"]);
          }
          $children .= '<li><a href="' . $mnu["link"] . '">' . $label . '</a></li>';
        }
        $children .= gzMenuItems($mnu);
        if ($children !== '') {
          $items .= '<li class="treeview"><a href="#"><span>' . stripslashes($mnu["name"]) . '</span>'
                  . '<i class="fa fa-angle-left pull-right"></i></a>'
                  . '<ul class="treeview-menu">' . $children . '</ul></li>';
        }
      }
    } else { // secondo livello senza figli o terzo livello
      if (isset($mnu["m2_ackey"]) && $admin_aziend["Abilit"] >= $mnu["m2_ackey"]) {
        $items .= '<li><a href="' . $mnu['link'] . '">' . stripslashes($mnu['name']) . '</a></li>';
      }
      if (isset($mnu["m3_ackey"]) && $admin_aziend["Abilit"] >= $mnu["m3_ackey"]) {
        $items .= '<li><a href="' . $mnu['link'] . '">' . stripslashes($mnu['name']) . '</a></li>';
      }
    }
  }
  return $items;
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
            } else {
              $m3_link='../'.$row['name'].'/'.$row['m3_link'];
            }
            $path2 = parse_url($row['m2_link'], PHP_URL_PATH);
            $nfr2 = basename($path2,'.php');
            if (isset($chkes->excluded_script) && in_array($nfr2,$chkes->excluded_script)) {
              $m2_link = '';
              $acc_excluded[] = $nfr2;
            } else {
              $m2_link='../'.$row['name'].'/'.$row['m2_link'];
            }
            if ($row['access'] == 3) {
                if ($ctrl_m1 != $row['m1_id']) {
                  require("../../modules/" . $row['name'] . "/menu.".$admin_aziend['lang'].".php");
                  require("../../modules/" . $row['name'] . "/lang.".$admin_aziend['lang'].".php");
                  $othertransl=$strScript;
                }
                if ($ctrl_m2 != $row['m2_id']) {
                  $next_m3_name=false;
                  if (isset($othertransl[$nfr2.'.php'])) { // se è stato indicato il nome specifico per la voce di terzo livello generato dal secondo livello
                    $trl = $othertransl[$nfr2.'.php'];
                    if (isset($trl['menu'])) {
                      $next_m3_name=$trl['menu'];
                    }
                  }
                }
                if ($row['name'] == $module) {
                    $row['weight'] = 0;
                    if ($row['m3_link'] == $scriptname) {
                        $title_from_menu = $transl[$row['name']]['m3'][$row['m3_trkey']][0];
                    }
                    if ($ctrl_m2 != $row['m2_id'] and $ctrl_m1 != $row['m1_id']) {
                        require("../../modules/" . $row['name'] . "/lang.".$admin_aziend['lang'].".php");
                        $mymodtransl=$strScript;
                        if (isset($mymodtransl[$scriptname])) { // se Ã¨ stato tradotto lo script lo ritorno al chiamante
                            $translated_script = $mymodtransl[$scriptname];
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
                        $menuArray[$row['weight']][$row['m2_weight']] = array('link' => $m2_link,
                            'icon' => '../' . $row['name'] . '/' . $row['m2_icon'],
                            'name' => $transl[$row['name']]['m2'][$row['m2_trkey']][1],
                            'menu' => $next_m3_name,
                            'title' => $transl[$row['name']]['m2'][$row['m2_trkey']][0],
                            'm2_ackey' => $row["m2_ackey"],
                            'class' => $row['m2_class']);
                            $next_m3_name=false;
                  } elseif ($ctrl_m2 != $row['m2_id']) { // è solo il primo di menu2
                        $menuArray[$row['weight']][$row['m2_weight']] = array('link' => $m2_link,
                            'icon' => '../' . $row['name'] . '/' . $row['m2_icon'],
                            'name' => $transl[$row['name']]['m2'][$row['m2_trkey']][1],
                            'menu' => $next_m3_name,
                            'title' => $transl[$row['name']]['m2'][$row['m2_trkey']][0],
                            'm2_ackey' => $row["m2_ackey"],
                            'class' => $row['m2_class']);
                            $next_m3_name=false;
                    }
                    if (!empty($row['m3_link'])){
                      $menuArray[$row['weight']][$row['m2_weight']][$row['m3_weight']] = array('link' => $m3_link,
                        'icon' => '../' . $row['name'] . '/' . $row['m3_icon'],
                        'name' => $transl[$row['name']]['m3'][$row['m3_trkey']][1],
                        'title' => $transl[$row['name']]['m3'][$row['m3_trkey']][0],
                        'm3_ackey' => $row["m3_ackey"],
                        'class' => $row['m3_class']);
                    }
                } elseif ($ctrl_m1 != $row['m1_id']) { // è il primo di menu2
                    $menuArray[$row['weight']] = array('link' => '../' . $row['name'] . '/' . $row['link'],
                        'icon' => $row['icon'],
                        'name' => $transl[$row['name']]['name'],
                        'title' => $transl[$row['name']]['title'],
                        'm1_ackey' => $row['m1_ackey'],
                        'class' => $row['class']);
                    $menuArray[$row['weight']][$row['m2_weight']] = array('link' => $m2_link,
                        'icon' => '../' . $row['name'] . '/' . $row['m2_icon'],
                        'name' => $transl[$row['name']]['m2'][$row['m2_trkey']][1],
                        'menu' => $next_m3_name,
                        'title' => $transl[$row['name']]['m2'][$row['m2_trkey']][0],
                        'm2_ackey' => $row["m2_ackey"],
                        'class' => $row['m2_class']);
                        $next_m3_name=false;
                } else { // non è il primo di menu2
                    $menuArray[$row['weight']][$row['m2_weight']] = array('link' => $m2_link,
                        'icon' => '../' . $row['name'] . '/' . $row['m2_icon'],
                        'name' => $transl[$row['name']]['m2'][$row['m2_trkey']][1],
                        'menu' => $next_m3_name,
                        'title' => $transl[$row['name']]['m2'][$row['m2_trkey']][0],
                        'm2_ackey' => $row["m2_ackey"],
                        'class' => $row['m2_class']);
                        $next_m3_name=false;
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
        // ordine del titolo invertito: prima la voce, poi il menu, infine il nome dell'azienda
        $accTitle = implode('» ', array_reverse(explode('» ', $accTitle)));
    // sidebar AdminLTE con le voci del menu dell'utente
    ?>
    <aside class="main-sidebar">
      <section class="sidebar">
        <div class="gz-sidebar-logo-wrap">
          <img src="../../modules/root/view.php?table=aziend&amp;value=<?php echo (int) $admin_aziend['company_id']; ?>" alt="Logo" class="gz-sidebar-logo">
        </div>
        <?php
        // pulsanti rapidi: i preferiti dell'utente (stellina nella barra)
        $gzFavs = gzFavoritesFor($admin_aziend['company_id'], $admin_aziend['user_name']);
        // senza preferiti il pannello resta una striscia sottile, come da CSS (.gz-quick-empty)
        ?>
        <div class="gz-company-panel gz-quick-panel<?php echo $gzFavs ? '' : ' gz-quick-empty'; ?>">
        <?php
        foreach ($gzFavs as $gzFav) {
            $gzLabel = gzPageCode($gzFav['link'], $gzFav['code'] ?? '');
            echo '<a class="btn btn-xs btn-default gz-quick" href="../../modules' . htmlspecialchars($gzFav['link']) . '" title="' . htmlspecialchars($gzFav['name']) . '">' . htmlspecialchars($gzLabel) . '</a> ';
        }
        ?>
        </div>
        <ul class="sidebar-menu" data-widget="tree">
    <?php
    foreach ($menuArray as $link) {
      if ($admin_aziend["Abilit"] >= $link["m1_ackey"]) {
        $rsm = explode("/", $link['link']);
        // il modulo della pagina corrente risulta attivo e le sue voci sono gia' aperte
        $act = ($module == $rsm[1]) ? ' active menu-open' : '';
        echo '<li class="treeview' . $act . '">';
        echo '<a href="' . $link['link'] . '"><img height="20" src="../' . substr($link['icon'], 0, -4) . '/' . $link['icon'] . '"> <span>' . $link['name'] . '</span>';
        echo '<i class="fa fa-angle-left pull-right"></i></a>';
        $items = gzMenuItems($link);
        if ($items !== '') { echo '<ul class="treeview-menu">' . $items . '</ul>'; }
        echo "</li>\n";
      }
    }
    ?>
        </ul>
        <?php // in fondo alla sidebar: espande o riduce la barra, la scelta resta salvata nel browser ?>
        <div class="gz-sidebar-toggle">
          <input type="checkbox" id="gz-sidebar-expand" title="Espandi / riduci la barra">
        </div>
      </section>
    </aside>
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
        echo '<ol class="breadcrumb">';
        $yeslink=false;
        while ( $row = gaz_dbi_fetch_array($res_pos) ) {
          echo "<li><a class='btn btn-xs btn-default' href='".$row['link']."'>".$row['titolo']."</a></li>";
          $yeslink=true;
        }
        echo "<li><a class='btn btn-xs btn-default' href='../../modules/root/admin.php'><i class='fa fa-home'></i></a>&nbsp;<a class='btn btn-xs btn-default' href='../../modules/root/admin_breadcrumb.php?url=".$pos."'><i class='glyphicon glyphicon-cog'></i></a></li>";
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
          echo '<div class="hidden-xs hidden-sm"><ol class="breadcrumb">';
          // come sulle pagine di terzo livello: il secondo livello (genitore delle voci) è il primo elemento
          if (!in_array(pathinfo($riga['link'], PATHINFO_FILENAME), $acc_excluded)) echo '<li><a class="btn btn-xs btn-primary" href="'.$riga['link'].'">'.stripslashes($transl[$module]["m2"][$riga["translate_key"]]["1"]).'</a></li>';
          while ($r = gaz_dbi_fetch_array($result2)) {
            $linkbase =  pathinfo($r['link'], PATHINFO_FILENAME);
            if ( $admin_aziend["Abilit"]>=$r["accesskey"] && !in_array($linkbase,$acc_excluded) ) echo '<li><a class="btn btn-xs btn-default" href="'.$r["link"].'">'.stripslashes ($transl[$module]["m3"][$r["translate_key"]]["1"]).'</a></li>';
          }
        } else { // siamo su una pagina di 3 livello nel menu principale
          $posizione_s = explode ("&seziva",$posizione ); // sui report fatture/ddt aggiungo con js la sezione iva all'url per proporre quella corrente, questo fa si che non coincida con quanto sta sul db allora pulisco la referenza
          $posizione_php = explode (".php",$posizione_s[0]); // pulisco le referenze passate sull'url
          $result3    = gaz_dbi_dyn_query("*", $gTables['menu_script'] ," link LIKE '".$posizione_php[0].".php%'",'id',0,1);
          if ( $ms = gaz_dbi_fetch_array($result3) ) { // disegno i bottoni di accesso alle funzioni di questa pagina
              $result4    = gaz_dbi_dyn_query($gTables['menu_script'].".*,".$gTables['menu_module'].".link AS lmm,".$gTables['menu_module'].".translate_key AS tmm ", $gTables['menu_script']. " LEFT JOIN ".$gTables['menu_module']." ON ".$gTables['menu_script'].".id_menu = ".$gTables['menu_module'].".id LEFT JOIN ".$gTables['module']." ON ".$gTables['menu_module'].".id_module = ".$gTables['module'].".id", $gTables['menu_script'].".id_menu =".$ms['id_menu']." AND ".$gTables['module'].".name = '".$module."'",'name',0,99);
              echo '<div class="hidden-xs hidden-sm"><ol class="breadcrumb">';
              $first=true;
              while ($r = gaz_dbi_fetch_array($result4)) {
                $linkbase_lmm =  pathinfo($r['lmm'], PATHINFO_FILENAME);
                if ($first && !in_array($linkbase_lmm,$acc_excluded)) {
                  echo '<li><a class="btn btn-xs btn-primary" href="'.$r["lmm"].'">'.$transl[$module]["m2"][$r["tmm"]]["1"].'</a></li>';
                }
                $linkbase =  pathinfo($r['link'], PATHINFO_FILENAME);
                if ( $admin_aziend["Abilit"]>=$r["accesskey"] && !in_array($linkbase,$acc_excluded)) echo '<li><a class="btn btn-xs btn-default" href="'.$r["link"].'">'.stripslashes ($transl[$module]["m3"][$r["translate_key"]]["1"]).'</a></li>';
                $first=false;
              }
          }
        }
        echo "<li><a class='btn btn-xs btn-default' href=\"../../modules/root/admin.php\"><i class=\"fa fa-home\"></i></a>&nbsp;<a class='btn btn-xs btn-default' href='../../modules/root/admin_breadcrumb.php?url=".$pos."'><i class='glyphicon glyphicon-cog'></i></a></li>";
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
            $translated_script = $mymodtransl[$alternative_transl . '.php'];
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
				var confirmEl = document.getElementById(\'confirmSubmit\');
				var alPre = confirmEl ? confirmEl.value.toString() : \'\';
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
         // override di variabili dei moduli: bs3clean/overrides.php definisce $gzOverrides (vedi il file)
        global $sortable_headers;
        $gzOverrides = array();
        if (file_exists(__DIR__ . '/overrides.php')) {
          include __DIR__ . '/overrides.php';
        }
        if (isset($sortable_headers) && is_array($sortable_headers) && !empty($gzOverrides['sortable_headers'])) {
          $gzHeaderMap = $gzOverrides['sortable_headers'];
          $newHeaders = array();
          foreach ($sortable_headers as $label => $field) {
            $newLabel = isset($gzHeaderMap[$label]) ? $gzHeaderMap[$label] : $label;
            $newHeaders[$newLabel] = $field; // il campo di ordinamento resta quello originale
          }
          $sortable_headers = $newHeaders;
        }
        // testi personalizzati del tema: bs3clean/lang.<lingua>.php definisce $gzTextOverride, che sovrascrive i testi standard
        $gzTextOverride = array();
        $gzLangFile = __DIR__ . '/lang.' . $admin_aziend['lang'] . '.php';
        if (file_exists($gzLangFile)) {
          include $gzLangFile;
        }
        return array_merge($strCommon + $translated_script, $gzTextOverride);
}

// codice di traduzione di una pagina del menu (es. vendit-m2-2), per le statistiche di utilizzo
function get_transl_referer($rlink) {
    global $gTables;
    $clink = explode('/', $rlink);
    $n1 = gaz_dbi_get_row($gTables['module'], 'link', end($clink));
    if ($n1) {
        include "../../modules/" . $clink[1] . "/menu.italian.php";
        return $clink[1] . '-m1-' . $n1['id'];
    }
    $n2 = gaz_dbi_get_row($gTables['menu_module'], 'link', end($clink));
    if ($n2) {
        include "../../modules/" . $clink[1] . "/menu.italian.php";
        return $clink[1] . '-m2-' . $n2['translate_key'];
    }
    $n3 = gaz_dbi_get_row($gTables['menu_script'], 'link', end($clink));
    if ($n3) {
        include "../../modules/" . $clink[1] . "/menu.italian.php";
        return $clink[1] . '-m3-' . $n3['translate_key'];
    }
    // non è nel menu: provo sui file di traduzione dello script
    include "../../modules/" . $clink[1] . "/lang.italian.php";
    $n_scr = explode('?', end($clink));
    if (isset($mymodtransl[$n_scr[0]])) {
        if (isset($mymodtransl[$n_scr[0]]['title'])) {
            return $clink[1] . '-sc-' . $n_scr[0] . '-title';
        } elseif (isset($mymodtransl[$n_scr[0]][0])) {
            return $clink[1] . '-sc-' . $n_scr[0] . '-0';
        }
        return $clink[1] . '-none-script';
    }
    return $clink[1] . '-none-script_menu';
}

// colore pastello casuale per la voce nelle statistiche di utilizzo
function pastelColors() {
    $r = dechex(round(((float) rand() / (float) getrandmax()) * 127) + 127);
    $g = dechex(round(((float) rand() / (float) getrandmax()) * 127) + 127);
    $b = dechex(round(((float) rand() / (float) getrandmax()) * 127) + 127);
    return $r . $g . $b;
}

// file dei preferiti di un utente in una azienda: data/files/<company_id>/<user_name>.json
// il nome utente è ripulito, così non può uscire dalla cartella
function gzFavoritesFile($companyId, $user) {
    $name = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $user);
    return __DIR__ . '/../../../data/files/' . (int) $companyId . '/' . $name . '.json';
}

// preferiti dell'utente nell'azienda (stellina nella barra)
function gzFavoritesFor($companyId, $user) {
    $file = gzFavoritesFile($companyId, $user);
    return is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
}

// salva i preferiti dell'utente nell'azienda; la cartella viene creata se manca
function gzFavoritesSave($companyId, $user, $list) {
    $file = gzFavoritesFile($companyId, $user);
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0775, true);
    }
    file_put_contents($file, json_encode(array_values($list), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// la pagina corrente è nei preferiti dell'utente?
function gzIsFavorite($companyId, $user, $link) {
    foreach (gzFavoritesFor($companyId, $user) as $f) {
        if ($f['link'] === $link) {
            return true;
        }
    }
    return false;
}

// sigla di una pagina dal nome del file: prima lettera del prefisso + il resto in maiuscolo
// es. report_docven.php -> RDOCVEN, admin_utente.php -> AUTENTE
// sigla del pulsante: quella scelta dall'utente nei preferiti, altrimenti ricavata dal nome del file
function gzPageCode($link, $custom = '') {
    if ($custom !== '') {
        return $custom;
    }
    $base = pathinfo(basename((string) $link), PATHINFO_FILENAME);
    $parts = explode('_', $base);
    $code = mb_strtoupper(mb_substr($parts[0], 0, 1)) . mb_strtoupper(implode('', array_slice($parts, 1)));
    return $code !== '' ? $code : mb_strtoupper($base);
}
