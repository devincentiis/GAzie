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
  AUTORE: Andrea Biasi
  -------------------------------------------------------------------
 */

/*
  GAzie - impostazioni della ricerca globale (tema adminlte_clean).
  L'utente sceglie in quali fonti cercare; le scelte sono salvate in data/files/<company_id>/<user_name>_ricerca.json.
*/
$module = 'root';
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin(9);
require_once("global_search.lib.php");
if (isset($_GET['mode']) && $_GET['mode']=='db') {
  $mode='db';
  if (!preg_match('/^[a-zA-Z0-9_-]+$/', $_GET['user'])) {  die("Errore: Il nome utente  può contenere solo lettere, numeri, trattini (-) e underscore (_)."); }
  if (!gaz_dbi_get_row($gTables['admin'], 'user_name',  $_GET['user'])) {  die("Errore: Utente inesistente."); }
  $user = $_GET['user'];
} else {
  $mode='';
  $user = $admin_aziend['user_name'];
}
$companyId = $admin_aziend['company_id'];
$catalog = gsCatalog();
if ( $mode == 'db') {
  $disabled = gsDisabledForDb($companyId, $user);
  $settingsNow = gsSettingsForDb($companyId, $user);
} else {
  $disabled = gsDisabledFor($companyId, $user);
  $settingsNow = gsSettingsFor($companyId, $user);
}

// fonti che l'utente può usare: solo quelle dei moduli a cui ha accesso
$visible = array();
foreach ($catalog as $key => $src) {
    if (gsCanSee($src['module'], $src['script'],$user)) $visible[$key] = $src;
}

// attiva o disattiva una fonte (solo tra quelle visibili; le altre scelte restano come sono)
if (isset($_POST['fonte']) && isset($visible[$_POST['fonte']])) {
  if ($mode=='db'){
    $key=preg_replace('/[^a-z_]/','',$_POST['fonte']);
    $fonte_isset=gaz_dbi_get_row($gTables['breadcrumb'],'codice_aziend',$companyId,"AND adminid='".$user."' AND exec_mode = 5 AND file ='".$key."'");
    if ($fonte_isset){ // è attivata, disattivo rimuovendo
      $fonte_isset=gaz_dbi_del_row($gTables['breadcrumb'],'id_bread',$fonte_isset['id_bread']);
    } else { // attivo inserendo
      gaz_dbi_query("INSERT INTO `gaz_breadcrumb` (`exec_mode`,`file`,`titolo`,`adminid`,`codice_aziend`) VALUES ( 5, '".$key."','".$catalog[$key]['name']."','".$user."',".$companyId." )");
    }
  } else {
    $key = $_POST['fonte'];
    if (in_array($key, $disabled)) {
      $disabled = array_values(array_diff($disabled, array($key)));
    } else {
      $disabled[] = $key;
    }
    gsSaveDisabled($companyId, $user, $disabled);
  }
  header('Location: global_search_manage.php?mode='.$mode.'&user='.$user);
  exit;
}
if (isset($_POST['tendina']) && isset($visible[$_POST['tendina']]) && !empty($visible[$_POST['tendina']]['page_only'])) {
  if ($mode=='db'){
    $key=preg_replace('/[^a-z_]/','',$_POST['tendina']);
    $fonte_isset=gaz_dbi_get_row($gTables['breadcrumb'],'codice_aziend',$companyId,"AND adminid='".$user."' AND exec_mode = 5 AND file ='".$key."'");
    if ($fonte_isset){
      $fonte_isset['position_order'] ^= 1; // inverto lo stato della tendina
      gaz_dbi_query("UPDATE ".$gTables['breadcrumb']." SET position_order = ".$fonte_isset['position_order']." WHERE id_bread = ".$fonte_isset['id_bread']);
    }
  } else {
    $settings = gsSettingsFor($companyId, $user);
    $key = $_POST['tendina'];
    if (in_array($key, $settings['dropdown'])) {
        $settings['dropdown'] = array_values(array_diff($settings['dropdown'], array($key)));
    } else {
        $settings['dropdown'][] = $key;
    }
    gsSaveSettings($companyId, $user, $settings);
  }
  header('Location: global_search_manage.php?mode='.$mode.'&user='.$user);
  exit;
}
// opzione: codice articolo o barcode esatto inserito nel documento aperto (attiva se non è tra le escluse)
if (isset($_POST['opzione']) && $_POST['opzione'] == 'barcode_doc') {
  if ($mode=='db'){
    $fonte_isset=gaz_dbi_get_row($gTables['breadcrumb'],'codice_aziend',$companyId,"AND adminid='".$user."' AND exec_mode = 5 AND file ='barcode_doc'");
    if ($fonte_isset){ // è attivata, disattivo rimuovendo
      $fonte_isset=gaz_dbi_del_row($gTables['breadcrumb'],'id_bread',$fonte_isset['id_bread']);
    } else { // attivo inserendo
      gaz_dbi_query("INSERT INTO `gaz_breadcrumb` (`exec_mode`,`file`,`titolo`,`adminid`,`codice_aziend`) VALUES ( 5, 'barcode_doc','Return Automatico','".$user."',".$companyId." )");
    }
  } else {
    if (in_array('barcode_doc', $disabled)) {
        $disabled = array_values(array_diff($disabled, array('barcode_doc')));
    } else {
        $disabled[] = 'barcode_doc';
    }
    gsSaveDisabled($companyId, $user, $disabled);
  }
  header('Location: global_search_manage.php?mode='.$mode.'&user='.$user);
  exit;
}
if (isset($_POST['tutte'])) {
  // riattiva tutte le fonti visibili
  if ($mode=='db'){
    foreach ($catalog as $key => $src) {
      $fonte_isset=gaz_dbi_get_row($gTables['breadcrumb'],'codice_aziend',$companyId,"AND adminid='".$user."' AND exec_mode = 5 AND file ='".$key."'");
      if (!$fonte_isset) {
        gaz_dbi_query("INSERT INTO ".$gTables['breadcrumb']." (`exec_mode`,`file`,`titolo`,`adminid`,`codice_aziend`) VALUES ( 5, '".$key."','".$src['name']."','".$user."',".$companyId." )");
      }
    }
  } else {
    gsSaveDisabled($companyId, $user, array_values(array_diff($disabled, array_keys($visible))));
  }
  header('Location: global_search_manage.php?mode='.$mode.'&user='.$user);
  exit;
}

// elenco delle funzioni rapide che l'utente può usare (codici del menu)
$quickExact = null;
$quickList = gsQuickCodes('', 500, $quickExact, $user);

require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<div class="panel panel-info">
  <div class="box-body text-center">
    <h4>Ricerca globale dell'utente: <?= $user ?></h4>
    <p>Scegli in quali dati cercare con il campo "Cerca in GAzie" nella barra in alto (scorciatoia <b>Ctrl+K</b>).</p>
    <table class="table Tmiddle table-striped table-bordered table-condensed" align="center">
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
    <?php $barcodeOn = !in_array('barcode_doc', $disabled); ?>
    <h4><i class="fa fa-barcode"></i> Codice articolo e barcode</h4>
    <p>Se il testo cercato coincide con il codice a barre, il codice articolo o il codice produttore di un solo articolo, premendo <b>Invio</b>
      (la pistola barcode lo preme da sola): nelle pagine dei documenti (fatture, DDT, preventivi, ordini di vendita e di acquisto) l'articolo viene aggiunto
      come nuova riga con quantità 1; nelle altre pagine si apre la scheda dell'articolo.</p>
    <form method="POST" style="margin:0">
      <?php echo $barcodeOn ? '<span class="label label-success">attiva</span>' : '<span class="label label-default">disattivata</span>'; ?>
      <button type="submit" name="opzione" value="barcode_doc" class="btn btn-xs btn-default"><?php echo $barcodeOn ? 'Disattiva' : 'Attiva'; ?></button>
    </form>
    <br>
    <h4>Funzioni rapide</h4>
    <p>Digita uno di questi codici (o l'inizio) nel campo "Cerca in GAzie": <b>Invio</b> sul codice completo apre subito la funzione.<?php echo in_array('codici', $disabled) ? ' <span class="label label-default">esclusa dalla ricerca</span>' : ''; ?></p>
    <?php if ($quickList) { ?>
    <table class="table Tmiddle table-striped table-bordered table-condensed" align="center">
      <thead><tr><th>Codice</th><th>Funzione</th><th>Modulo</th></tr></thead>
      <tbody>
      <?php foreach ($quickList as $qr) { ?>
      <tr>
        <td><code><?php echo htmlspecialchars($qr['code']); ?></code></td>
        <td><a href="<?php echo htmlspecialchars($qr['url']); ?>"><?php echo htmlspecialchars($qr['label']); ?></a></td>
        <td><?php echo htmlspecialchars($qr['detail']); ?></td>
      </tr>
      <?php } ?>
      </tbody>
    </table>
    <?php } else { ?>
    <p class="text-muted">Nessuna funzione rapida disponibile per i tuoi permessi.</p>
    <?php } ?>
    <br>
    <h4>Come funziona</h4>
  </div>
  <div>
    <ul>
      <li><b>Fonti:</b> compaiono solo i dati dei moduli a cui hai accesso. Una fonte esclusa non viene più cercata, né nella tendina né nella pagina dei risultati.</li>
      <li><b>Anche nella tendina:</b> le righe dei documenti sono le tabelle più grandi, quindi di base compaiono solo nella pagina completa dei risultati (Invio o "Vedi tutti i risultati"). Spuntando la casella compaiono anche nella tendina mentre scrivi, con qualche decimo di secondo in più di attesa.</li>
      <li><b>Jolly:</b> <code>*</code> (o <code>%</code>) sostituisce qualsiasi testo e <code>?</code> un solo carattere. Per esempio <code>toner*hp</code> trova "TONER NERO PER HP" e <code>fel?s</code> trova "Felis". La ricerca è sempre di tipo "contiene".</li>
      <li><b>Pagine del menu:</b> cerca tra le voci della barra laterale, per arrivare velocemente a una pagina scrivendone il nome.</li>
      <li><b>Funzioni rapide:</b> sono i codici assegnati alle voci del menu (campo <code>trans_code</code> in <code>gaz_menu_script</code> e <code>gaz_menu_module</code>); si cercano dall'inizio del codice e compaiono in cima alla tendina.</li>
      <li><b>Personali:</b> ogni utente ha le proprie scelte, in ogni azienda.</li>
      <li><b>Salvataggio:</b> le scelte sono memorizzate in <code>data/files/&lt;azienda&gt;/&lt;utente&gt;_ricerca.json</code>, accanto ai preferiti. Senza il file tutte le fonti sono attive.</li>
    </ul>
  </div>
</div>
<?php
require("../../library/include/footer.php");
