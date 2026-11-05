<?php
/*
  GAzie - tema adminlte_clean: AdminLTE 2 con layout essenziale.
  Il menu e la logica di HeadMain() sono in function.php di questo tema.
*/
require_once("../../library/theme/adminlte_clean/function.php");
// statistiche di utilizzo del menu (gaz_menu_usage): come nel tema lte, conta gli accessi alle pagine dei moduli
if (isset($scriptname) && $scriptname != 'admin.php') {
    $gzUsage = gaz_dbi_dyn_query("*", $gTables['menu_usage'], ' adminid="' . $admin_aziend["user_name"] . '" AND company_id="' . $admin_aziend['company_id'] . '" AND link="' . $mod_uri . '" ', ' adminid', 0, 1);
    if (gaz_dbi_num_rows($gzUsage) == 0) {
        $gzValue = [];
        $gzValue['transl_ref'] = get_transl_referer($mod_uri);
        $gzValue['adminid'] = $admin_aziend["user_name"];
        $gzValue['company_id'] = $admin_aziend['company_id'];
        $gzValue['link'] = $mod_uri;
        $gzValue['click'] = 1;
        $gzValue['color'] = pastelColors();
        $gzValue['last_use'] = date('Y-m-d H:i:s');
        gaz_dbi_table_insert('menu_usage', $gzValue);
    } else {
        $gzRow = gaz_dbi_fetch_array($gzUsage);
        gaz_dbi_put_query($gTables['menu_usage'], ' adminid="' . $admin_aziend["user_name"] . '" AND company_id="' . $admin_aziend['company_id'] . '" AND link="' . $mod_uri . '"', 'click', $gzRow['click'] + 1);
    }
}
// skin scelta dall'utente in "Aspetto dello stile"; se non valida si usa quella standard
$skinFile = (!empty($admin_aziend['skin']) && preg_match('/^[a-z0-9_\-]+\.css$/', $admin_aziend['skin']) && file_exists(__DIR__ . '/skins/' . $admin_aziend['skin'])) ? $admin_aziend['skin'] : 'default.css';
$userName = trim($admin_aziend['user_firstname'] . ' ' . $admin_aziend['user_lastname']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title id="title_from_menu"><?php echo htmlspecialchars($admin_aziend['ragso1']); ?></title>
    <?php // icona della scheda: quella dell'azienda se presente, altrimenti quella generica (come nel tema lte); evita la richiesta di /favicon.ico
    $gzFaviconPath = file_exists(DATA_DIR . 'files/' . $admin_aziend['codice'] . '/favicon.ico')
        ? DATA_DIR . 'files/' . $admin_aziend['codice'] . '/favicon.ico'
        : '../../library/images/favicon.ico';
    ?>
    <link rel="icon" href="data:image/x-icon;base64,<?php echo base64_encode((string) @file_get_contents($gzFaviconPath)); ?>" type="image/x-icon">
    <link rel="stylesheet" href="../../js/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../library/theme/lte/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../library/theme/lte/adminlte/dist/css/AdminLTE.css">
    <link rel="stylesheet" href="../../library/theme/adminlte_clean/style.css">
    <?php // colore della barra superiore: quello dell'azienda (es. EAD6D6), come nel tema lte
    $gzCompanyColor = preg_match('/^[0-9a-fA-F]{6}$/', (string) $admin_aziend['colore']) ? $admin_aziend['colore'] : 'ffffff';
    // immagine di sfondo delle pagine: quella dell'azienda se presente, altrimenti quella generica (come nel tema lte)
    // ogni file si controlla da solo: se l'azienda non ha lo sfondo si usa quello generico, se manca anche quello non si scrive la regola
    $gzSfondoAzienda = DATA_DIR . 'files/' . $admin_aziend['codice'] . '/sfondo.png';
    $gzSfondoPath = file_exists($gzSfondoAzienda) ? $gzSfondoAzienda : '../../library/images/sfondo.png';
    $gzSfondo = file_exists($gzSfondoPath) ? base64_encode((string) file_get_contents($gzSfondoPath)) : '';
    ?>
    <style>
        .main-header .navbar, .main-header .logo { background-color: #<?php echo $gzCompanyColor; ?>; }
        /* footer e menu utente: stesso colore della barra superiore */
        body .main-footer { background-color: #<?php echo $gzCompanyColor; ?> !important; color: #123311; }
        body .main-footer a { color: #123311; }
        body .user-menu .dropdown-menu,
        body .user-menu .user-footer { background-color: #<?php echo $gzCompanyColor; ?> !important; }
        /* intestazione del menu utente: stesso colore della barra laterale (#222d32), con il testo chiaro della sidebar */
        body .user-menu .user-header.bg-light-blue { background-color: #222d32 !important; }
        body .user-menu .user-header.bg-light-blue > p,
        body .user-menu .user-header.bg-light-blue > p > small { color: #ffffff !important; }
        <?php if ($gzSfondo !== '') { ?>.content-wrapper { background-image: url("data:image/png;base64,<?php echo $gzSfondo; ?>"); }<?php } ?>
    </style>
    <link rel="stylesheet" href="../../library/theme/adminlte_clean/skins/<?php echo htmlspecialchars($skinFile); ?>">
    <link rel="stylesheet" href="../../js/jquery.ui/jquery-ui.css">
    <script src="../../js/jquery/jquery-3.7.1.min.js"></script>
    <script src="../../js/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body class="hold-transition sidebar-mini<?php echo (isset($scriptname) && strpos($scriptname, 'report') !== false) ? ' gz-report' : ''; ?>">
<script>
// barra espansa o ridotta come salvato, applicata prima della visualizzazione della pagina
try {
    var gzSaved = window.localStorage.getItem('gzSidebarExpanded');
    if (gzSaved !== null) {
        document.body.classList.toggle('sidebar-collapse', gzSaved !== '1');
    }
} catch (err) { }
</script>
    <div style="display:none" id="dialog_menu_alerts" title="">
        <div class="bg-warning text-bold" id="diatitle"></div>
    </div>
    <div class="wrapper">
        <form method="POST" name="head_form" action="../../modules/root/admin.php">
            <input type="hidden" name="hidden_req" value=""><?php // admin.php gestisce il logout solo se il POST contiene hidden_req ?>
            <div id="doc_modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header active">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title">Documentazione</h4>
                        </div>
                        <div class="modal-body edit-content small"></div>
                    </div>
                </div>
            </div>
            <header class="main-header">
                <a href="../../modules/root/admin.php" class="logo">
                    <span class="logo-mini"><img src="../../modules/root/view.php?table=aziend&amp;value=<?php echo (int) $admin_aziend['company_id']; ?>" alt="Logo" class="gz-logo-mini"></span>
                    <span class="logo-lg"><img src="../../modules/root/view.php?table=aziend&amp;value=<?php echo (int) $admin_aziend['company_id']; ?>" alt="Logo" class="gz-logo-lg"> <?php echo htmlspecialchars($admin_aziend['ragso1']); ?></span>
                </a>
                <nav class="navbar navbar-static-top" role="navigation">
                    <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                        <span class="sr-only">Menu</span>
                    </a>
                    <div class="navbar-custom-menu">
                        <ul class="nav navbar-nav">
                            <?php // ricerca globale in tutti i moduli (Ctrl+K), tendina gestita da js/custom/global_search.js ?>
                            <li class="gz-gs hidden-xs">
                                <div class="gz-gs-box"><?php // niente form: la barra è già dentro un form, Invio lo gestisce global_search.js ?>
                                    <i class="fa fa-search gz-gs-icon"></i>
                                    <input type="text" id="gz-gs-input" name="q" autocomplete="off" maxlength="50" placeholder="Cerca in GAzie... (Ctrl+K)" title="Ricerca in tutti i moduli. Jolly: * qualsiasi testo, ? un carattere">
                                </div>
                            </li>
                            <?php // stellina: aggiunge o toglie la pagina corrente ai preferiti della barra laterale
                            $gzFavOn = (isset($mod_uri) && gzIsFavorite($admin_aziend['company_id'], $admin_aziend['user_name'], $mod_uri));
                            if (isset($mod_uri) && $scriptname != 'admin.php') { ?>
                            <li><a href="#" id="gz-fav" data-link="<?php echo htmlspecialchars($mod_uri); ?>" title="<?php echo $gzFavOn ? 'Togli dai preferiti' : 'Aggiungi ai preferiti'; ?>"><i class="fa <?php echo $gzFavOn ? 'fa-star' : 'fa-star-o'; ?>"></i></a></li>
                            <?php } ?>
                            <?php // documentazione del modulo corrente (come nel tema lte): icona del modulo che apre la guida in una finestra
                            if (isset($module) && $module != 'root' && file_exists('../' . $module . '/docume_' . $module . '.php') && file_exists('../' . $module . '/menu.' . $admin_aziend['lang'] . '.php')) {
                                $rs_access_mod = gaz_dbi_dyn_query($gTables['admin_module'] . '.access', $gTables['module'] . ' LEFT JOIN ' . $gTables['admin_module'] . ' ON ' . $gTables['module'] . '.id=' . $gTables['admin_module'] . '.moduleid', "adminid='" . $admin_aziend['user_name'] . "' AND company_id=" . $admin_aziend['company_id'] . " AND " . $gTables['module'] . ".name='" . $module . "'", 'adminid', 0, 1);
                                $row_access_mod = gaz_dbi_fetch_array($rs_access_mod);
                                if ($row_access_mod && $row_access_mod['access'] == 3) {
                                    require '../' . $module . '/menu.' . $admin_aziend['lang'] . '.php';
                                    echo '<li><a id="docmodal" href="#" data-toggle="modal" data-target="#doc_modal" title="Documentazione modulo ' . htmlspecialchars($transl[$module]['name']) . '" module="' . htmlspecialchars($module) . '"><img src="../' . htmlspecialchars($module) . '/' . htmlspecialchars($module) . '.png" height="32"></a></li>';
                                }
                            }
                            ?>
                            <li class="dropdown user user-menu">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="<?php echo htmlspecialchars($userName); ?>">
                                    <i class="fa fa-user"></i>
                                    <span class="hidden-xs"><?php echo htmlspecialchars($userName); ?></span>
                                </a>
                                <?php $userPageUrl = '../config/admin_utente.php?user_name=' . urlencode($admin_aziend['user_name']) . '&amp;Update'; ?>
                                <ul class="dropdown-menu">
                                    <li class="user-header bg-light-blue">
                                        <img src="../root/view.php?table=admin&amp;field=user_name&amp;value=<?php echo urlencode($admin_aziend['user_name']); ?>" class="img-circle" alt="Utente">
                                        <p>
                                            <?php echo htmlspecialchars($userName); ?>
                                            <small><?php echo htmlspecialchars($admin_aziend['ragso1']); ?></small>
                                        </p>
                                    </li>
                                    <li class="user-body">
                                        <div class="gz-user-links">
                                            <a href="<?php echo $userPageUrl; ?>"><i class="fa fa-user"></i> Scheda utente</a>
                                            <a href="../root/api_token.php"><i class="fa fa-key"></i> Token API</a>
                                            <a href="../root/theme_favorites_manage.php"><i class="fa fa-star"></i> Preferiti</a>
                                            <a href="../root/global_search_manage.php"><i class="fa fa-search"></i> Ricerca globale</a>
                                            <?php if ($admin_aziend['Abilit'] >= 8) { ?>
                                            <a href="../config/admin_aziend.php"><i class="fa fa-building"></i> Azienda</a>
                                            <?php } ?>
                                        </div>
                                    </li>
                                    <li class="user-footer">
                                        <div class="pull-left"><a href="../root/login_password_change.php?un=<?php echo urlencode($admin_aziend['user_name']); ?>" class="btn btn-default btn-flat"><i class="fa fa-key"></i> Cambia password</a></div>
                                        <div class="pull-right"><button name="logout" type="submit" value=" Logout" class="btn btn-default btn-flat"><i class="fa fa-sign-out"></i> Esci</button></div>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header>
        </form>
