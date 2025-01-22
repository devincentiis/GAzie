<?php
/*
  -------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2024 Antonio De Vincentiis Montesilvano (PE)
  (https://www.devincentiis.it)
  <https://gazie.sourceforge.net>
  -------------------------------------------------------------------
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
  -------------------------------------------------------------------
 */
$config = new UserConfig;

$pdb = intval(gaz_dbi_get_row($gTables['company_config'], 'var', 'menu_alerts_check')['val']);
$period = ($pdb < 15)? 60 : $pdb;

require("../../library/theme/lte/function.php");

if ( isset( $maintenance ) && $maintenance!=FALSE && $maintenance!=$_SESSION['user_email'] ) {
	header("Location: ../../modules/root/maintenance.php");
	exit();
}

if (!strstr($_SERVER["REQUEST_URI"], "login_admin") == "login_admin.php") {
    $_SESSION['lastpage'] = $_SERVER["REQUEST_URI"];
}
global $module;
$prev_script = '';
$menuclass = ' class="FacetMainMenu" ';
$style = 'default.css';
$skin = 'default.css';
if (isset($_POST['logout'])) {
    header("Location: logout.php");
    exit;
}

if (isset( $scriptname) && $scriptname != $prev_script && $scriptname != 'admin.php' ) { // aggiorno le statistiche solo in caso di cambio script
    $result = gaz_dbi_dyn_query("*", $gTables['menu_usage'], ' adminid="' . $admin_aziend["user_name"] . '" AND company_id="' . $admin_aziend['company_id'] . '" AND link="' . $mod_uri . '" ', ' adminid', 0, 1);
    $value = array();
    if (gaz_dbi_num_rows($result) == 0) {
        $value['transl_ref'] = get_transl_referer($mod_uri);
        $value['adminid'] = $admin_aziend["user_name"];
        $value['company_id'] = $admin_aziend['company_id'];
        $value['link'] = $mod_uri;
        $value['click'] = 1;
        $value['color'] = pastelColors();
        $value['last_use'] = date('Y-m-d H:i:s');
        gaz_dbi_table_insert('menu_usage', $value);
    } else {
        $usage = gaz_dbi_fetch_array($result);
        gaz_dbi_put_query($gTables['menu_usage'], ' adminid="' . $admin_aziend["user_name"] . '" AND company_id="' . $admin_aziend['company_id'] . '" AND link="' . $mod_uri . '"', 'click', $usage['click'] + 1);
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="mobile-web-app-capable" content="yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-title" content="<?php echo $admin_aziend['ragso1'];?>">
		<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title id='title_from_menu'></title>
<?php
if (file_exists(DATA_DIR.'files/'.$admin_aziend['codice'].'/favicon.ico')) { // usa l'icona aziendale
    $ico=base64_encode(@file_get_contents( DATA_DIR . 'files/' . $admin_aziend['codice'] . '/favicon.ico' ));
    $ico114=base64_encode(@file_get_contents( DATA_DIR . 'files/' . $admin_aziend['codice'] . '/logo_114x114.png' ));
    $sfondo=base64_encode(@file_get_contents( DATA_DIR . 'files/' . $admin_aziend['codice'] . '/sfondo.png' ));
} else { // uso quella generica
    $ico=base64_encode(@file_get_contents('../../library/images/favicon.ico' ));
    $ico114=base64_encode(@file_get_contents('../../library/images/logo_114x114.png' ));
    $sfondo=base64_encode(@file_get_contents('../../library/images/sfondo.png' ));
}
?>
    <link rel="icon" href="data:image/x-icon;base64,<?php echo $ico; ?>"  type="image/x-icon" />
		<link rel="icon" sizes="114x114" href="data:image/x-icon;base64,<?php echo $ico114; ?>"  type="image/x-icon" />
		<link rel="apple-touch-icon" href="data:image/x-icon;base64,<?php echo $ico114; ?>"  type="image/x-icon">
		<link rel="apple-touch-startup-image" href="data:image/x-icon;base64,<?php echo $ico114; ?>"  type="image/x-icon">
		<link rel="apple-touch-icon-precomposed" sizes="114x114" href="data:image/x-icon;base64,<?php echo $ico114; ?>"  type="image/x-icon" />
    <link href="../../js/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../<?php echo(STATIC_VERSION);?>library/theme/lte/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../<?php echo(STATIC_VERSION);?>library/theme/lte/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="../../<?php echo(STATIC_VERSION);?>library/theme/lte/adminlte/dist/css/AdminLTE.css">
    <link href="../../<?php echo(STATIC_VERSION);?>js/jquery.ui/jquery-ui.css" rel="stylesheet">
		<script src="../../js/jquery/jquery-3.7.1.min.js"></script>
    <?php
    if (!empty($admin_aziend['style']) && file_exists("../../library/theme/lte/scheletons/" . $admin_aziend['style'])) {
        $style = $admin_aziend['style'];
    }
    if (!empty($admin_aziend['skin']) && file_exists("../../library/theme/lte/skins/" . $admin_aziend['skin'])) {
        $skin = $admin_aziend['skin'];
        if (strpos($skin,'black')===false){ // non cambio lo sfondo
        } else { // se è black inverto lo sfondo (negativo)
          $im = @imagecreatefrompng( DATA_DIR . 'files/' . $admin_aziend['codice'] . '/sfondo.png');
          imagefilter($im, IMG_FILTER_NEGATE);
          ob_start ();
          imagepng($im);
          $image_data = ob_get_contents ();
          ob_end_clean ();
          $sfondo=base64_encode($image_data);
        }
    }
    if ( $debug_active == true ){
      echo '<style> pre.xdebug-var-dump { z-index: 820; position: relative; } </style>';
    } ;
    ?>
        <link href="../../<?php echo(STATIC_VERSION);?>library/theme/lte/scheletons/<?php echo $style; ?>" rel="stylesheet" type="text/css" />
        <link href="../../<?php echo(STATIC_VERSION);?>library/theme/lte/skins/<?php echo $skin; ?>" rel="stylesheet" type="text/css" />
        <style>
            .company-color, .company-color-bright, li.user-header, .company-color-logo, .dropdown-menu > li > a:hover, .dropdown-menu > li.user-body:hover, .navbar-default .navbar-nav > li > a:hover,
            nav.navbar.navbar-static-top.company-color-bright:hover
            {
              background-color: #<?= $admin_aziend['colore'] ?>;
              color: black;
            }
            .adminlte-gazie .main-sidebar {
              background-color: #<?= $admin_aziend['colore'] ?>;
            }
            .company-color-logo:hover {
              filter: brightness(80%);
            }
            li.blink{
              animation:blink 700ms infinite alternate;
              padding-top:10px;
            }
            li.blink>a.btn{
              padding:5px;
            }
            @keyframes blink {
              from { opacity:1; } to { opacity:0; }
            }
            .ui-dialog-buttonset>button.btn.btn-confirm:first-child {
                background-color: #f9b54d;
            }
            .dropdown-menu > li.user-body > a {
              white-space: normal;
            }
            .sidebar-menu > li:hover > a,
            .sidebar-menu > li.active > a {
              border-left-color: #<?= $admin_aziend['colore'] ?>;
            }
            .sidebar a, treeview-menu > li > a {
              color: #<?= $admin_aziend['colore'] ?>;
            }
            .sidebar-menu .treeview-menu.menu-open > li {
              border-left: 2px solid #<?= $admin_aziend['colore'] ?>;
            }
            .content-wrapper {
              background-image: url("data:image/x-icon;base64,<?= $sfondo; ?>");
            }
            a.logo.company-color-logo span img {
              height: auto;
              width: auto;
              max-width: 50px;
              max-height: 50px;
              padding: 1px;
            }
            th a, .breadcrumb li a, a i.glyphicon-cog {
              color: #<?= $admin_aziend['colore'] ?>;
              filter: brightness(0.5);
            }
            .nav-pills > li.active > a, .nav-pills > li.active > a:hover, .nav-pills > li.active > a:focus {
              background-color: #<?= $admin_aziend['colore'] ?>;
              color: #000;
            }

        </style>
<script>
$(function() {
	$("#dialog_menu_alerts").dialog({ autoOpen: false });
});
function menu_alerts_check(mod,title,button,label,link,style){
	// questa funzione attiva l'alert sulla barra del menù e viene richiamata sia dalla funzione menu_check_from_modules() dal browser tramite setInterval che alla fine della pagina (lato server) quando il controllo fatto dal php tramite $_SESSION['menu_alerts_lastcheck'] è scaduto
    // faccio append solo se già non esiste
    if (style && style.length >= 2) { // solo se style è valorizzato faccio l'alert sul menu
        $("li.blink").html( '<a mod="'+mod+'" class="btn btn-'+style+' dialog_menu_alerts" title="'+title.replace(/(<([^>]+)>)/ig,"")+'" >'+button+'</a>').click(function() {
			$("p#diatitle").html(title);
			$( "#dialog_menu_alerts" ).dialog({
                title: button ,
				minHeight: 210,
				width: "auto",
				modal: "true",
				show: "blind",
				hide: "explode",
				buttons: {
					'confirm':{
						text: label,
						'class':'btn btn-confirm',
						click:function (event, ui) {
						$.ajax({
							data: {'mod':mod },
							type: 'POST',
							url: '../root/delete_menu_alert.php',
							success: function(data){
								window.location.href=link;
							}
						});
					}},
					delete:{
						text:'Posponi',
						'class':'btn btn-danger delete-button',
						click:function (event, ui) {
						$.ajax({
							data: {'mod':mod },
							type: 'POST',
							url: '../root/delete_menu_alert.php',
							success: function(data){
								//alert(data);
								window.location.reload(true);
							}
						});
					}},
					"Lascia": function() {
						$(this).dialog('destroy');
					}
				}
			});
			$("#dialog_menu_alerts" ).dialog( "open" );
		});
    }
}

function menu_check_from_modules() {
    // chiamata al server per aggiornare il tempo dell'ultimo controllo
	$.ajax({
		type: 'GET',
		url: "../root/session_menu_alert_lastcheck.php",
		success: function(){
		  var j=0;
          // nome modulo
          var title = '';
          var button = '';
          var label = '';
          var style = '';
          var link = '';
          var mod = '';
          // controllo la presenza di nuove notifiche
          $.ajax({
            type: 'GET',
            url: '../root/get_sync_status_ajax.php',
            data: {},
            dataType: 'json',
            success: function (data) {
			  if (data) {
				$.each(data, function(i, v) {
				  // nome modulo
				  title = v['title'];
				  button = v['button'];
				  label = v['label'];
				  link = v['link'];
				  style = v['style'];
				  mod = i;
				  //console.log(mod);
				  j++;
				  menu_alerts_check(mod,title,button,label,link,style);
				});
              }
            }
          });
        }
	});
}
// setto comunque dei check intervallati dei minuti inseriti in configurazione avanzata azienda 15*60*1000ms perché non è detto che si facciano i refresh, ad es. se il browser rimane fermo sulla stessa pagina per un lungo periodo > $period
setInterval(menu_check_from_modules,<?php echo intval((int)$period*60000);?>);

    $(function () {
        $("#docmodal").click(function () {
		var module = $(this).attr('module');
            $.ajax({
                type: "POST",
                url: "../../modules/"+module+"/docume_"+module+".php",
                data: 'mode=modal',// da lasciare perché alcuni moduli usano mode
                success: function (msg) {
					$("#doc_modal .modal-sm").css('width', '80%');
                    $("#doc_modal .modal-body").html(msg);
                },
                error: function () {
                    alert("Errore apertura documentazione");
                }
            });
        });
    });

</script>
    </head>
    <?php
    // imposto le opzioni del tema caricando le opzioni del database

    $val = $config->getValue('LTE_Fixed');
    if (!isset($val)) {
        $config->setDefaultValue();
        header("Location: ../../modules/root/admin.php");
    } else {
        $val = "";
    }

    if ($config->getValue('LTE_Fixed') == "true")
        $val = " fixed";
    if ($config->getValue('LTE_Boxed') == "true")
        $val = " layout-boxed";
    if ($config->getValue('LTE_Collapsed') == "true")
        $val .= " sidebar-collapse";
    if ($config->getValue('LTE_Onhover') == "true")
        $val .= " wysihtml5-supported sidebar-mini sidebar-collapse";
    if ($config->getValue('LTE_SidebarOpen') == "true")
        $val .= " sidebar-mini sidebar-open control-sidebar-open";

    echo "<body class=\"hold-transition adminlte-gazie " . $val . "\">";
    ?>

    <form method="POST" name="head_form" action="../../modules/root/admin.php">
		<div style="display:none" id="dialog_menu_alerts" title="">
			<p class="ui-state-highlight" id="diatitle"></p>
		</div>
		<div id="doc_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-sm">
				<div class="modal-content">
					<div class="modal-header active">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="myModalLabel"><?php echo "Documentazione"; ?></h4>
					</div>
					<div class="modal-body edit-content small"></div>
				</div>
			</div>
		</div>
        <div class="wrapper">
            <header class="main-header">
                <!-- Logo -->
                <a href="../../modules/root/admin.php" class="logo company-color-logo">
                    <!-- mini logo for sidebar mini 50x50 pixels -->
                    <span class="logo-mini">
                        <img src="../../modules/root/view.php?table=aziend&amp;value=<?php echo $admin_aziend["company_id"]; ?>" alt="Logo" border="0" title="<?php echo $admin_aziend["ragso1"]; ?>" />
                    </span>
                    <!-- logo for regular state and mobile devices -->
                    <span class="logo-lg">
                        <img src="../../modules/root/view.php?table=aziend&amp;value=<?php echo $admin_aziend["company_id"]; ?>" alt="Logo" border="0" title="<?php echo $admin_aziend["ragso1"]; ?>" />
                        &nbsp;
<?php
$rslen=strlen($admin_aziend["ragso1"]);
if ($rslen < 18) {
  echo $admin_aziend["ragso1"];
} elseif ($rslen < 25) {
  echo '<span style="font-size: 10px;">'.$admin_aziend["ragso1"].'</span>';
} else {
  echo '<span style="font-size: 8px;">'.$admin_aziend["ragso1"].'</span>';
}
?>                   </span>
                </a>
                <!-- Header Navbar: style can be found in header.less -->
                <nav class="navbar navbar-static-top company-color-bright" role="navigation">
                    <!-- Sidebar toggle button-->
                    <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                        <span class="sr-only">Toggle navigation</span>
                    </a>
                    <div class="navbar-custom-menu">
                        <ul class="nav navbar-nav">
							<li class='blink'></li>
                            <?php
                            //leggo se il modulo è abilitato
							$res_access_mod = gaz_dbi_dyn_query($gTables['admin_module'].'.access', $gTables['module'].' LEFT JOIN '. $gTables['admin_module'].' ON '. $gTables['module'].'.id='. $gTables['admin_module'].'.moduleid',"adminid='".$admin_aziend["user_name"]."' AND company_id=".$admin_aziend['company_id'],'adminid' ,0,1);
                            $row_access_mod = gaz_dbi_fetch_array($res_access_mod);
                            if ($row_access_mod && $row_access_mod['access'] == 3 ) {
                                //visualizzo la documentazione standard
								require '../' . $module . '/menu.' . $admin_aziend['lang'] . '.php';
                                echo '<li><a id="docmodal" href="#myModal" data-toggle="modal" data-target="#doc_modal" title="Documentazione modulo '. $transl[$module]['name'] .'" module="'. $module .'"><img src="../'.$module.'/'.$module.'.png" height="32"><span class="hidden-xs">'.$transl[$module]['name']."</span></a></li>";
                            }
                            ?>
                            <!-- Messages: style can be found in dropdown.less-->
                            <li class="dropdown messages-menu">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    <i class="fa fa-star" style="color: yellow; text-shadow: 0 0 10px #000;"></i>
                                    <!--<span class="label label-success">4</span>-->
                                </a>
                                <ul class="dropdown-menu">
                                    <li class="header">Funzioni più utilizzate</li>
                                    <li>
                                        <!-- inner menu: contains the actual data -->
                                        <ul class="menu">
                                            <?php
                                            $result = gaz_dbi_dyn_query("*", $gTables['menu_usage'], ' company_id="' . $admin_aziend['company_id'] . '" AND adminid="' . $admin_aziend["user_name"] . '" ', ' click DESC, last_use DESC', 0, 8);
                                            if (gaz_dbi_num_rows($result) > 0) {
                                                while ($r = gaz_dbi_fetch_array($result)) {
                                                    $rref = explode('-', $r['transl_ref']);

                                                    switch ($rref[1]) {
                                                        case 'm1':
                                                            require '../' . $rref[0] . '/menu.' . $admin_aziend['lang'] . '.php';
                                                            $rref_name = $transl[$rref[0]]['title'];
                                                            break;
                                                        case 'm2':
                                                            require '../' . $rref[0] . '/menu.' . $admin_aziend['lang'] . '.php';
                                                            $rref_name = $transl[$rref[0]]['m2'][$rref[2]][0];
                                                            break;
                                                        case 'm3':
                                                            require '../' . $rref[0] . '/menu.' . $admin_aziend['lang'] . '.php';
                                                            $rref_name = $transl[$rref[0]]['m3'][$rref[2]][0];
                                                            break;
                                                        case 'sc':
                                                            require '../' . $rref[0] . '/lang.' . $admin_aziend['lang'] . '.php';
                                                            $rref_name = $strScript[$rref[2]][$rref[3]];
                                                            break;
                                                        default:
                                                            $rref_name = 'Nome script non trovato';
                                                            break;
                                                    }
                                                    ?>
                                                    <li><!-- start message -->
                                                        <a href="<?php
                                                if ($r["link"] != "")
                                                    echo '../../modules' . $r["link"];
                                                else
                                                    echo "&nbsp;";
                                                ?>">
                                                            <div class="pull-left">
                                                                <i class="fa fa-archive" style="color:#<?php echo $r["color"]; ?>"></i>
                                                            </div>
                                                            <h4>
                                                    <?php echo substr($rref_name, 0, 28); ?>
                                                                <small style="top: -8px;"><i class="fa fa-thumbs-o-up"></i> <?php echo $r["click"] . ' click'; ?></small>
                                                            </h4>
                                                            <p><?php echo substr($r["link"], 0, 38); ?></p>
                                                        </a>
                                                    </li>
        <?php
    }
}
?>
                                        </ul>
                                    </li>
                                    <li class="footer"><a href="../../modules/root/admin.php">Vedi tutte</a></li>
                                </ul>
                            </li>

                            <!-- Sezione link più usati -->
                            <li class="dropdown messages-menu">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    <i class="fa fa-clock-o" style="color: #a200fb; text-shadow: 0 0 10px #db00fb;"></i>
                                    <!--<span class="label label-success">4</span>-->
                                </a>
                                <ul class="dropdown-menu">
                                    <li class="header">Ultime funzioni utilizzate</li>
                                    <li>
                                        <!-- inner menu: contains the actual data -->
                                        <ul class="menu">
                                            <?php
                                            $res_last = gaz_dbi_dyn_query("*", $gTables['menu_usage'], ' company_id="' . $admin_aziend['company_id'] . '" AND adminid="' . $admin_aziend["user_name"] . '" ', ' last_use DESC, click DESC', 0, 8);
                                            if (gaz_dbi_num_rows($res_last) > 0) {
                                                while ($rl = gaz_dbi_fetch_array($res_last)) {
                                                    $rlref = explode('-', $rl['transl_ref']);
                                                    switch ($rlref[1]) {
                                                        case 'm1':
                                                            require '../' . $rlref[0] . '/menu.' . $admin_aziend['lang'] . '.php';
                                                            $rlref_name = $transl[$rlref[0]]['title'];
                                                            break;
                                                        case 'm2':
                                                            require '../' . $rlref[0] . '/menu.' . $admin_aziend['lang'] . '.php';
                                                            $rlref_name = $transl[$rlref[0]]['m2'][$rlref[2]][0];
                                                            break;
                                                        case 'm3':
                                                            require '../' . $rlref[0] . '/menu.' . $admin_aziend['lang'] . '.php';
                                                            $rlref_name = $transl[$rlref[0]]['m3'][$rlref[2]][0];
                                                            break;
                                                        case 'sc':
                                                            require '../' . $rlref[0] . '/lang.' . $admin_aziend['lang'] . '.php';
                                                            $rlref_name = $strScript[$rlref[2]][$rlref[3]];
                                                            break;
                                                        default:
                                                            $rlref_name = 'Nome script non trovato';
                                                            break;
                                                    }
                                                    ?>
                                                    <li>
                                                        <a href="<?php
                                                if ($rl["link"] != "")
                                                    echo '../../modules' . $rl["link"];
                                                else
                                                    echo "&nbsp;";
                                                    ?>">
                                                            <div class="pull-left">
                                                                <i class="fa fa-archive" style="color:#<?php echo $rl["color"]; ?>"></i>
                                                            </div>
                                                            <h4>
        <?php
        if (is_string($rlref_name)) {
            echo substr($rlref_name, 0, 28);
        } else {
            //print_r( $rlref_name);
            echo 'Nome script non trovato';
        }
        ?>
                                                                <small style="top: -8px;"><i class="fa fa-clock-o"></i> <?php echo gaz_time_from(strtotime($rl["last_use"])); ?></small>
                                                            </h4>
                                                            <p><?php echo substr($rl["link"], 0, 38); ?></p>
                                                        </a>
                                                    </li>
        <?php
    }
}
?>
                                        </ul>
                                    </li>
                                    <li class="footer"><a href="../../modules/root/admin.php">Vedi tutte</a></li>
                                </ul>
                            </li>

                            <!-- User Account: style can be found in dropdown.less -->
                            <li class="dropdown user user-menu">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    <img src="<?php echo '../root/view.php?table=admin&field=user_name&value=' . $admin_aziend["user_name"]; ?>" class="user-image" alt="User Image" style="box-shadow: 0 0 10px #000;">
                                    <span class="hidden-xs"><?php echo $admin_aziend['user_firstname'] . ' ' . $admin_aziend['user_lastname']; ?></span>
                                </a>
                                <ul class="dropdown-menu">
<?php
if($admin_aziend['Abilit']>=8) {
?>
                                    <!-- User image -->
                                    <li class="user-header"><a href="../config/admin_utente.php?user_name=<?php echo $admin_aziend["user_name"]; ?>&Update">
                                        <img src="<?php echo '../root/view.php?table=admin&field=user_name&value=' . $admin_aziend["user_name"]; ?>" class="img-circle" alt="User" height=80></a>
                                        <p><?php echo $admin_aziend['user_firstname'] . ' ' . $admin_aziend['user_lastname']; ?>
                                            <small>
                                                Questo è il tuo <b><?php echo $admin_aziend['Access']; ?>°</b> accesso<br/>
                                                La tua password risale al <b><?php echo gaz_format_date($admin_aziend['datpas']); ?></b><br>
                                            </small>
                                        </p>
                                    </li>
                                    <!-- Menu Body -->
                                    <li class="user-body">
                                      <a href="../config/admin_aziend.php">
                                        <div class="col-xs-12 text-center">
                                          <img class="img-circle dit-picture" src="../../modules/root/view.php?table=aziend&value=<?php echo $admin_aziend['company_id']; ?>" height=100 alt="Logo" border="0" >
                                        </div>
                                        <div class="col-xs-12">
                                          <?php echo $admin_aziend['ragso1'] . " " . $admin_aziend['ragso2']; ?>
                                        </div>
                                      </a>
                                    </li>
<?php
}
?>

                                    <!-- Menu Footer-->
                                    <li class="user-footer">
                                        <div class="text-center">
                                            <button name="logout" type="submit" value=" Logout" class="btn btn-default">Logout
                                            <i class="glyphicon glyphicon-log-out"></i>
                                            </button>
                                        </div>
                                    </li>
                                </ul>
                            </li>
                            <!-- Control Sidebar Toggle Button -->
<?php
echo "<li><a href=\"#\" data-toggle=\"control-sidebar\"><i class=\"fa fa-bars\"></i></a></li>";

if (!isset($_SESSION['menu_alerts_lastcheck'])||((round(time()/60)-$_SESSION['menu_alerts_lastcheck'])> $period )){ // sono passati $period minuti
	// non ho mai controllato se ci sono nuovi ordini oppure è passato troppo tempo dall'ultimo controllo vado a farlo
		echo '<script>menu_check_from_modules();</script>';
} elseif(isset($_SESSION['menu_alerts']) && count($_SESSION['menu_alerts'])>=1) {
        foreach($_SESSION['menu_alerts'] as $k=>$v) {
            // se ho i dati per visualizzare il bottone relativo al modulo sincronizzato faccio il load per crearlo (mod,title,button,label,link,style)
            if ( is_array($v) && count($v) > 4 ) { // se ho i dati sufficienti creo l'elemento bottone tramite js
                echo "<script>menu_alerts_check('".$k."','".addslashes($v['title'])."','".addslashes($v['button'])."','".addslashes($v['label'])."','".addslashes($v['link'])."','".$v['style']."');</script>";
            }
        }
}
?>

                        </ul>
                    </div>
                </nav>
            </header>
            <!-- Left side column. contains the logo and sidebar -->
            <aside class="main-sidebar">
                <!-- sidebar: style can be found in sidebar.less -->
                <section class="sidebar">
                    <!-- Sidebar user panel -->
                    <!--<div class="user-panel">
                      <div class="pull-left image">
                        <img src="<?php //echo '../root/view.php?table=admin&field=user_name&value=' . $admin_aziend["user_name"];  ?>" class="img-circle" alt="User Image">
                      </div>
                      <div class="pull-left info">
                        <p><?php //echo $admin_aziend['Nome'].' '.$admin_aziend['Cognome'];  ?></p>
                        <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
                      </div>
                    </div>
                    <!-- search form-->
                    <ul class="sidebar-menu">
                        <!--<li class="header">MENU' PRINCIPALE</li>-->
