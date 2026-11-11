<?php
/*
  GAzie - tema adminlte_clean: chiusura della pagina e script comuni.
*/
$contact_link = (isset($contact_link)) ? $contact_link : '';
?>
        </section>
    </div>
    <footer class="main-footer">
        <?php
        // come nel tema lte: con il debug attivo (debug_active in gconfig.myconf.php) l'amministratore (livello 9)
        // vede le versioni di database e PHP, la chiave di sessione e il collegamento all'utilità degli hash
        if (!empty($debug_active) && isset($admin_aziend['Abilit']) && $admin_aziend['Abilit'] == 9) {
            $gzDbVer = gaz_dbi_fetch_array(gaz_dbi_query("SELECT @@version"));
            echo '<div class="gz-debug hidden-xs">'
                . '<span class="label label-danger">DEBUG ON</span>'
                . '<a class="btn btn-xs btn-info gz-debug-hash" href="../../passhash.php">HASHES UTILITY</a>'
                . ' '
                . 'DB: ' . htmlspecialchars($gzDbVer ? $gzDbVer[0] : '') . ' - PHP: ' . phpversion()
                . '<span class="gz-debug-key">' . (isset($_SESSION['aes_key']) ? htmlspecialchars((string) $_SESSION['aes_key'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '') . '</span>'
                . '</div>';
        }
        ?>
        <a target="_new" href="https://<?php echo $contact_link; ?>">https://<?php echo $contact_link; ?></a>
        <div class="pull-right hidden-xs">Version <?php echo defined('GAZIE_VERSION') ? GAZIE_VERSION : ''; ?></div>
    </footer>
</div>
<script src="../../js/jquery.ui/jquery-ui.min.js"></script>
<script src="../../js/jquery.ui/datepicker-<?php echo substr($admin_aziend['lang'], 0, 2); ?>.js"></script>
<script src="../../js/custom/jquery.ui.autocomplete.html.js"></script>
<script src="../../js/custom/gz-library.js"></script>
<script src="../../js/custom/global_search.js"></script>
<script src="../../js/tinymce/tinymce.min.js"></script>
<script src="../../js/custom/tinymce.js"></script>
<script src="../../library/theme/lte/adminlte/dist/js/app.js"></script>
<script>
$(function () {
  // home: i pannelli della stessa riga hanno la stessa altezza minima, quella del piu' alto.
  // Usa min-height: il piu' piccolo cresce e il piu' grande non viene mai compresso.
  function gzEqualWidgets() {
    var $panels = $('#sortable > div > .panel').css('min-height', '');
    var rows = {};
    $panels.each(function () {
      var top = Math.round($(this).closest('#sortable > div').offset().top);
      (rows[top] = rows[top] || []).push(this);
    });
    $.each(rows, function (top, els) {
      var max = 0;
      $(els).each(function () { max = Math.max(max, $(this).outerHeight()); });
      $(els).css('min-height', max + 'px');
    });
  }
  gzEqualWidgets();
  // i widget possono cambiare altezza quando arrivano dati o immagini: ricalcolo a pagina completa e dopo le chiamate AJAX
  $(window).on('load resize', gzEqualWidgets);
  $(document).ajaxStop(gzEqualWidgets);
});
</script>
<script>
// documentazione del modulo: il contenuto di docume_<modulo>.php va nella finestra della barra in alto
$(function () {
  $('#docmodal').on('click', function (e) {
    e.preventDefault();
    var module = $(this).attr('module');
    $.ajax({
      type: 'POST',
      url: '../../modules/' + module + '/docume_' + module + '.php',
      data: 'mode=modal', // da lasciare perché alcuni moduli usano mode
      success: function (msg) {
        $('#doc_modal .modal-body').html(msg);
      },
      error: function () {
        alert('Errore apertura documentazione');
      }
    });
  });
});
</script>
<script>
// stellina nella barra: aggiunge o toglie la pagina corrente dai preferiti della sidebar
$(function () {
  // casella in fondo alla sidebar: barra espansa o ridotta, scelta salvata nel browser
  var $expand = $('#gz-sidebar-expand');
  var saved = null;
  try { saved = window.localStorage.getItem('gzSidebarExpanded'); } catch (err) { saved = null; }
  if (saved !== null) {
    var expanded = saved === '1';
    $expand.prop('checked', expanded);
    $('body').toggleClass('sidebar-collapse', !expanded);
  } else {
    $expand.prop('checked', !$('body').hasClass('sidebar-collapse'));
  }
  $expand.on('change', function () {
    var expanded = $(this).is(':checked');
    $('body').toggleClass('sidebar-collapse', !expanded);
    try { window.localStorage.setItem('gzSidebarExpanded', expanded ? '1' : '0'); } catch (err) { }
  });

  // suggerimento dei codici rapidi: solo il nome del file php della pagina
  $('.gz-quick').each(function () { $(this).attr('title', this.pathname.split('/').pop()); });
  $('#gz-fav').on('click', function (e) {
    e.preventDefault();
    var link = $(this).data('link');
    var $item = $('.sidebar-menu a').filter(function () { return $(this).attr('href') === '../../modules' + link; }).first();
    // nome: la voce del menu se c'è, altrimenti il titolo dell'intestazione della pagina
    var name = $.trim($item.length ? $item.text() : ($('.content-header h1').first().text() || document.title));
    name = $.trim(name.replace(/^(Gestione( degli| dei)?|Visualizzazione( e)?|Emissione( di)?|Lista dei|Selezione e|Genera i)\s*/i, ''));
    $.post('../../modules/root/theme_favorites.php', {action: 'toggle', link: link, name: name, mode: 'db'}, function () {
      location.reload();
    });
  });
});
</script>
</body>
</html>
