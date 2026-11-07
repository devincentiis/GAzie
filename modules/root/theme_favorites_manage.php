<?php
/*
  GAzie - gestione dei preferiti della barra laterale (tema adminlte_clean).
  Mostra i preferiti dell'utente nell'azienda (data/files/<company_id>/<user_name>.json) e permette di modificarli e rimuoverli.
*/
$module = 'root';
require("../../library/include/datlib.inc.php");
require_once("theme_favorites.lib.php");
$admin_aziend = checkAdmin();

$user = $admin_aziend['user_name'];
$companyId = $admin_aziend['company_id'];

// rimozione di un preferito dell'utente
if (isset($_POST['rimuovi']) && preg_match('#^/[a-z0-9_]+/[a-z0-9_]+\.php(\?[a-z0-9_&=.%-]*)?$#i', $_POST['rimuovi'])) {
    $list = array_values(array_filter(gzFavoritesFor($companyId, $user), function ($f) {
        return $f['link'] !== $_POST['rimuovi'];
    }));
    gzFavoritesSave($companyId, $user, $list);
    header('Location: theme_favorites_manage.php');
    exit;
}

$mine = gzFavoritesFor($companyId, $user);

// modifica di nome e link di un preferito dell'utente: la riga diventa un form (?modifica=posizione nell'elenco)
$pattern_link = '#^/[a-z0-9_]+/[a-z0-9_]+\.php(\?[a-z0-9_&=.%-]*)?$#i';
$editing = null;
$errore = '';
if (isset($_GET['modifica']) && isset($mine[(int) $_GET['modifica']])) {
    $editing = ['old' => $mine[(int) $_GET['modifica']]['link'], 'link' => $mine[(int) $_GET['modifica']]['link'], 'name' => (string) $mine[(int) $_GET['modifica']]['name'], 'code' => (string) ($mine[(int) $_GET['modifica']]['code'] ?? '')];
}
if (isset($_POST['salva'])) {
    $old = (string) ($_POST['old'] ?? '');
    $newLink = trim((string) ($_POST['link'] ?? ''));
    $newName = mb_substr(trim(strip_tags((string) ($_POST['name'] ?? ''))), 0, 60);
    // sigla del pulsante nella barra laterale: facoltativa, massimo 8 caratteri
    $newCode = mb_strtoupper(mb_substr(trim(strip_tags((string) ($_POST['code'] ?? ''))), 0, 8));
    $exists = false;
    $dupe = false;
    foreach ($mine as $f) {
        if ($f['link'] === $old) {
            $exists = true;
        }
        if ($f['link'] === $newLink && $newLink !== $old) {
            $dupe = true;
        }
    }
    if (!$exists) {
        $errore = 'Preferito non trovato.';
    } elseif (!preg_match($pattern_link, $newLink)) {
        $errore = 'Il link deve essere una pagina del tipo /modulo/pagina.php, eventualmente con parametri.';
    } elseif ($dupe) {
        $errore = 'Esiste già un preferito con questo link.';
    }
    if ($errore === '') {
        foreach ($mine as $i => $f) {
            if ($f['link'] === $old) {
                $mine[$i]['link'] = $newLink;
                $mine[$i]['name'] = $newName !== '' ? $newName : $newLink;
                if ($newCode !== '') {
                    $mine[$i]['code'] = $newCode;
                } else {
                    unset($mine[$i]['code']);
                }
            }
        }
        gzFavoritesSave($companyId, $user, $mine);
        header('Location: theme_favorites_manage.php');
        exit;
    }
    // errore: la riga resta in modifica con i valori inseriti
    $editing = ['old' => $old, 'link' => $newLink, 'name' => $newName, 'code' => $newCode];
}

require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<div class="box box-primary">
  <div class="box-body">
    <h4>Preferiti della barra laterale</h4>
    <?php if (!$mine) { ?>
      <p>Non hai ancora preferiti. Usa la stellina nella barra in alto per aggiungere la pagina che stai guardando.</p>
    <?php } else { ?>
    <p>Trascina le righe per cambiare l'ordine: lo stesso ordine vale per i pulsanti nella barra laterale.</p>
    <?php if ($errore !== '') { ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($errore); ?></div>
    <?php } ?>
    <table class="table table-striped table-bordered table-condensed">
      <thead>
        <tr><th></th><th>Sigla</th><th>Nome</th><th>Pagina</th><th></th></tr>
      </thead>
      <tbody id="gz-fav-sort">
      <?php foreach ($mine as $i => $f) {
        $sigla = gzPageCode($f['link'], $f['code'] ?? '');
        $isEdit = $editing !== null && $editing['old'] === $f['link'];
      ?>
      <tr data-link="<?php echo htmlspecialchars($f['link']); ?>">
        <td class="text-center" style="cursor: move;"><i class="fa fa-bars"></i></td>
        <?php if ($isEdit) { ?>
        <td colspan="4">
          <form method="POST" class="form-inline" style="margin:0">
            <input type="hidden" name="old" value="<?php echo htmlspecialchars($editing['old']); ?>">
            <input type="text" name="code" value="<?php echo htmlspecialchars($editing['code']); ?>" maxlength="8" placeholder="<?php echo htmlspecialchars(gzPageCode($editing['link'])); ?>" title="Sigla del pulsante nella barra laterale (vuoto = automatica)" class="form-control input-sm" style="width: 90px;">
            <input type="text" name="name" value="<?php echo htmlspecialchars($editing['name']); ?>" maxlength="60" placeholder="Nome" class="form-control input-sm" style="width: 200px;">
            <input type="text" name="link" value="<?php echo htmlspecialchars($editing['link']); ?>" placeholder="/modulo/pagina.php" class="form-control input-sm" style="width: 360px;">
            <button type="submit" name="salva" value="1" class="btn btn-xs btn-warning">Salva</button>
            <a href="theme_favorites_manage.php" class="btn btn-xs btn-default">Annulla</a>
          </form>
        </td>
        <?php } else { ?>
        <td><?php echo htmlspecialchars($sigla); ?></td>
        <td><?php echo htmlspecialchars((string) $f['name']); ?></td>
        <td><?php echo htmlspecialchars($f['link']); ?></td>
        <td>
          <a href="theme_favorites_manage.php?modifica=<?php echo $i; ?>" class="btn btn-xs btn-default">Modifica</a>
          <form method="POST" style="margin:0; display:inline">
            <button type="submit" name="rimuovi" value="<?php echo htmlspecialchars($f['link']); ?>" class="btn btn-xs btn-default" onclick="return confirm('Rimuovere questo preferito?');">Rimuovi</button>
          </form>
        </td>
        <?php } ?>
      </tr>
      <?php } ?>
      </tbody>
    </table>
    <script>
    $(function () {
      // ordine dei preferiti: al rilascio di una riga salvo il nuovo ordine, che poi vale anche per la barra laterale
      $('#gz-fav-sort').sortable({
        cancel: 'input, button, a, select',
        helper: function (e, tr) {
          var $original = tr.children();
          var $helper = tr.clone();
          $helper.children().each(function (index) {
            $(this).width($original.eq(index).width());
          });
          return $helper;
        },
        update: function () {
          var order = $('#gz-fav-sort > tr').map(function () { return $(this).data('link'); }).get();
          $.post('theme_favorites.php', {action: 'riordina', order: JSON.stringify(order)});
        }
      });
    });
    </script>
    <?php } ?>
    <br>
    <br>
    <h4>Come funzionano i preferiti</h4>
    <ul>
      <li><b>Aggiungere:</b> nella barra in alto, la stellina aggiunge la pagina che stai guardando ai tuoi preferiti. Se la pagina è già tra i preferiti, la stellina la toglie.</li>
      <li><b>Nome:</b> viene preso dalla voce del menu o dal titolo della pagina, senza prefissi come "Gestione" o "Visualizzazione". Il nome è limitato a 60 caratteri.</li>
      <li><b>Codici rapidi:</b> i preferiti compaiono come pulsanti corti nel pannello in cima alla barra laterale, sotto il logo. Il pulsante mostra un codice breve ricavato dal nome del file della pagina; passando il mouse si vede il nome completo.</li>
      <li><b>Senza preferiti:</b> se non ne hai, il pannello mostra le voci che usi più spesso.</li>
      <li><b>Pagine con parametri:</b> il preferito ricorda anche i parametri della pagina, per esempio un inserimento già impostato su un tipo di documento.</li>
      <li><b>Personali:</b> ogni utente vede e modifica solo i propri preferiti.</li>
      <li><b>Salvataggio:</b> i preferiti sono memorizzati nella cartella <code>data/files/&lt;azienda&gt;/</code>, con un file per ogni utente (<code>&lt;utente&gt;.json</code>). Così ogni utente ha i propri preferiti in ogni azienda. Il file viene creato alla prima aggiunta di un preferito.</li>
      <li><b>Rimozione:</b> da questa pagina, il pulsante "Rimuovi" elimina il preferito dopo una conferma. Lo stesso effetto si ottiene togliendo la stellina dalla pagina.</li>
      <li><b>Modifica:</b> il pulsante "Modifica" permette di cambiare il nome, il link e la sigla del preferito (massimo 8 caratteri). Se la sigla resta vuota, viene ricavata dal nome del file della pagina. Il link deve essere una pagina del tipo <code>/modulo/pagina.php</code>, eventualmente con parametri, e non può coincidere con un altro preferito.</li>
    </ul>
  </div>
</div>
<?php
require("../../library/include/footer.php");
