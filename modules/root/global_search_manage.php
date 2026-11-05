<?php
/*
  GAzie - impostazioni della ricerca globale (tema adminlte_clean).
  L'utente sceglie in quali fonti cercare; le scelte sono salvate in data/files/<company_id>/<user_name>_ricerca.json.
*/
$module = 'root';
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
require_once("global_search.lib.php");

$user = $admin_aziend['user_name'];
$companyId = $admin_aziend['company_id'];
$catalog = gsCatalog();
$disabled = gsDisabledFor($companyId, $user);
$settingsNow = gsSettingsFor($companyId, $user);

// fonti che l'utente può usare: solo quelle dei moduli a cui ha accesso
$visible = array();
foreach ($catalog as $key => $src) {
    if (gsCanSee($src['module'], $src['script'])) $visible[$key] = $src;
}

// attiva o disattiva una fonte (solo tra quelle visibili; le altre scelte restano come sono)
if (isset($_POST['fonte']) && isset($visible[$_POST['fonte']])) {
    $key = $_POST['fonte'];
    if (in_array($key, $disabled)) {
        $disabled = array_values(array_diff($disabled, array($key)));
    } else {
        $disabled[] = $key;
    }
    gsSaveDisabled($companyId, $user, $disabled);
    header('Location: global_search_manage.php');
    exit;
}
if (isset($_POST['tendina']) && isset($visible[$_POST['tendina']]) && !empty($visible[$_POST['tendina']]['page_only'])) {
    $settings = gsSettingsFor($companyId, $user);
    $key = $_POST['tendina'];
    if (in_array($key, $settings['dropdown'])) {
        $settings['dropdown'] = array_values(array_diff($settings['dropdown'], array($key)));
    } else {
        $settings['dropdown'][] = $key;
    }
    gsSaveSettings($companyId, $user, $settings);
    header('Location: global_search_manage.php');
    exit;
}
if (isset($_POST['tutte'])) {
    // riattiva tutte le fonti visibili
    gsSaveDisabled($companyId, $user, array_values(array_diff($disabled, array_keys($visible))));
    header('Location: global_search_manage.php');
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<div class="box box-primary">
  <div class="box-body">
    <h4>Ricerca globale</h4>
    <p>Scegli in quali dati cercare con il campo "Cerca in GAzie" nella barra in alto (scorciatoia <b>Ctrl+K</b>).</p>
    <table class="table table-striped table-bordered table-condensed" style="max-width: 640px;">
      <thead>
        <tr><th></th><th>Cerca in</th><th>Modulo</th><th class="text-center">Stato</th><th class="text-center">Anche nella tendina</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($visible as $key => $src) {
        $on = !in_array($key, $disabled);
      ?>
      <tr>
        <td class="text-center"><i class="fa <?php echo htmlspecialchars($src['icon']); ?>"></i></td>
        <td><?php echo htmlspecialchars($src['name']); ?></td>
        <td><?php echo $src['module'] !== '' ? htmlspecialchars($src['module']) : '<span class="text-muted">tutti</span>'; ?></td>
        <td class="text-center"><?php echo $on ? '<span class="label label-success">attiva</span>' : '<span class="label label-default">esclusa</span>'; ?></td>
        <td class="text-center">
          <?php if (!empty($src['page_only'])) { ?>
          <form method="POST" style="margin:0">
            <input type="hidden" name="tendina" value="<?php echo htmlspecialchars($key); ?>">
            <input type="checkbox" onchange="this.form.submit()" title="Mostra questi risultati anche nella tendina mentre scrivi"<?php echo in_array($key, $settingsNow['dropdown']) ? ' checked' : ''; ?><?php echo $on ? '' : ' disabled'; ?>>
          </form>
          <?php } else { ?>
          <i class="fa fa-check text-muted" title="Sempre nella tendina"></i>
          <?php } ?>
        </td>
        <td class="text-right">
          <form method="POST" style="margin:0">
            <button type="submit" name="fonte" value="<?php echo htmlspecialchars($key); ?>" class="btn btn-xs btn-default"><?php echo $on ? 'Togli dalla ricerca' : 'Aggiungi alla ricerca'; ?></button>
          </form>
        </td>
      </tr>
      <?php } ?>
      </tbody>
    </table>
    <form method="POST" style="margin:0">
      <button type="submit" name="tutte" value="1" class="btn btn-sm btn-default">Riattiva tutte</button>
    </form>
    <br>
    <h4>Come funziona</h4>
    <ul>
      <li><b>Fonti:</b> compaiono solo i dati dei moduli a cui hai accesso. Una fonte esclusa non viene più cercata, né nella tendina né nella pagina dei risultati.</li>
      <li><b>Anche nella tendina:</b> le righe dei documenti sono le tabelle più grandi, quindi di base compaiono solo nella pagina completa dei risultati (Invio o "Vedi tutti i risultati"). Spuntando la casella compaiono anche nella tendina mentre scrivi, con qualche decimo di secondo in più di attesa.</li>
      <li><b>Jolly:</b> <code>*</code> (o <code>%</code>) sostituisce qualsiasi testo e <code>?</code> un solo carattere. Per esempio <code>toner*hp</code> trova "TONER NERO PER HP" e <code>fel?s</code> trova "Felis". La ricerca è sempre di tipo "contiene".</li>
      <li><b>Pagine del menu:</b> cerca tra le voci della barra laterale, per arrivare velocemente a una pagina scrivendone il nome.</li>
      <li><b>Personali:</b> ogni utente ha le proprie scelte, in ogni azienda.</li>
      <li><b>Salvataggio:</b> le scelte sono memorizzate in <code>data/files/&lt;azienda&gt;/&lt;utente&gt;_ricerca.json</code>, accanto ai preferiti. Senza il file tutte le fonti sono attive.</li>
    </ul>
  </div>
</div>
<?php
require("../../library/include/footer.php");
