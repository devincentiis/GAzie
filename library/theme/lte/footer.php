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
require("../../modules/root/lang.".$admin_aziend['lang'].".php");

?>
</div>
</section>
<?php
// se viene visualizzata una pagina specifica non visualizzare il footer
$url = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);
?>
    <footer class="main-footer">
      <div class="col-lg-4 col-xs-12">
        Version <?php echo GAZIE_VERSION; ?>
      </div>
      <div class=" text-center col-lg-4 hidden-xs">
        <?php
        if ( $debug_active == true ){
          echo '<a class="btn btn-xs btn-danger" href="" style="cursor:default;"> DEBUG ATTIVATO </a> '.$_SESSION['aes_key'].' <a class="btn btn-xs btn-info" href="../../passhash.php" > HASHES UTILITY </a>';
        } ;
        ?>
      </div>
      <div class="text-right col-lg-4 hidden-xs">
        <?php echo $strScript['admin.php']['auth']; ?>:  <a  target="_new" title="<?php echo $strScript['admin.php']['auth']; ?>" href="https://<?php echo $contact_link; ?>">https://<?php echo $contact_link; ?></a>
      </div>
    </footer>

    <!-- Control Sidebar -->
      <aside class="control-sidebar control-sidebar-dark">
        <!-- Create the tabs -->
        <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
          <li><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-bar-chart"></i></a></li>
          <li class="active"><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-list-ul"></i></a></li>
        </ul>
        <!-- Tab panes -->
        <div class="tab-content">
          <!-- Home tab content -->
          <div class="tab-pane" id="control-sidebar-home-tab">
              <ul class="control-sidebar-menu">
                <?php
            $result   = gaz_dbi_dyn_query("*", $gTables['menu_usage'], ' company_id="' . $admin_aziend['company_id'] . '" AND adminid="' . $admin_aziend["user_name"] . '" ', ' click DESC, last_use DESC', 0, 20);
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
                  <li>
                    <a href="<?php
                            if ($r["link"] != "")
                                echo '../../modules' . $r["link"];
                            else
                                echo "&nbsp;";
                            ?>">
                          <i class="menu-icon fa <?php echo get_rref_type( $r["link"] ); ?>" style="color:#<?php echo $r["color"]; ?>"></i>
                          <div class="menu-info">
                            <h4 class="control-sidebar-subheading">
                                <?php
                                    echo pulisci_rref_name( $rref_name );
                                ?>
                            </h4>
                            <p><?php echo $r["click"] . ' click'; ?></p>
                          </div>
                    </a>
                  </li>
                  <?php
                }
            }
            ?>
                </ul>

          </div><!-- /.tab-pane -->
          <!-- Stats tab content -->
          <div class="tab-pane" id="control-sidebar-stats-tab">Stats Tab Content</div><!-- /.tab-pane -->
          <!-- Settings tab content -->
          <div class="tab-pane active" id="control-sidebar-settings-tab">
            <form method="post">
              <!--<h3 class="control-sidebar-heading">Impostazioni </h3>-->
              <?php
                printCheckbox( "Stile Fixed", "LTE_Fixed", "Attiva lo stile fisso" );
                printCheckbox("Stile Boxed", "LTE_Boxed", "Attiva lo stile boxed" );
                printCheckbox("Menu Ridotto", "LTE_Collapsed", "Collassa il menu principale" );
                printCheckbox("Menu Automatico", "LTE_Onhover", "Espandi automaticamente il menu" );
                printCheckbox("Sidebar Aperto", "LTE_SidebarOpen", "Mantieni la barra aperta" );
              ?>
              <div class='form-group'>
                <a onclick="restoreUserConfig();" style="cursor: pointer;">Ripristina default</a>
              </div>
            </form>
          </div><!-- /.tab-pane -->
        </div>
      </aside>

      <div class="control-sidebar-bg"></div>
    </div><!-- ./wrapper -->
    <script src="../../js/jquery.ui/jquery-ui.min.js"></script>
    <script><!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
      $.widget.bridge('uibutton', $.ui.button);
    </script>
    <script type="text/javascript">
      function processForm(el) {
        var checkbox = $(el);
        $.ajax( {
          type: 'POST',
          url: '../../modules/root/lte_post_config.php',
          data: { 'name': checkbox.attr('name'),
                  'val': checkbox.is(':checked'),
                  'desc': checkbox.attr('hint')
          },
          success: function(data) {
            //alert(data);
            window.location.reload();
          }
        });
      }
      function restoreUserConfig() {
        $.ajax( {
          type: 'POST',
          url: '../../modules/root/lte_post_config.php',
          data: { 'name':'restore' },
          success: function(data) {
            //alert(data);
            window.location.reload();
          }
        });
      }
    </script>
    <script src="../../js/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script src="../../library/theme/lte/plugins/slimScroll/jquery.slimscroll.min.js"></script>
    <script src="../../library/theme/lte/plugins/fastclick/fastclick.min.js"></script>
    <script>
        var AdminLTEOptions = {
            sidebarExpandOnHover: <?php echo $config->getValue('LTE_Onhover'); ?>,
            enableBoxRefresh: true,
            enableBSToppltip: true
        };
    </script>

    <script src="../../library/theme/lte/adminlte/dist/js/app.js"></script>
    <script src="../../js/custom/jquery.ui.autocomplete.html.js"></script>
    <script src="../../js/custom/gz-library.js"></script>
    <script src="../../js/tinymce/tinymce.min.js"></script>
    <script src="../../js/custom/tinymce.js"></script>
    <script src="../../js/jquery.ui/datepicker-<?php echo substr($admin_aziend['lang'], 0, 2); ?>.js"></script>
    </body>
</html>
