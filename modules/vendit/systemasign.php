<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
  (https://www.devincentiis.it)
  <https://gazie.sourceforge.net>
  Questo file: Copyright (C) 2026 Domenico Seccia - Assistenzacassa (https://assistenzacassa.it)
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
/*
  SystemaSign per GAzie (25/11/2026): manda il DDT alla firma digitale.
  systemasign.php?id_tes=N     -> scegli autista / banco / solo collegamento, poi link, QR, WhatsApp, stato e copia firmata
  systemasign.php?impostazioni -> collega questo GAzie all'istanza SystemaSign (indirizzo + invito del rivenditore)
*/
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
require_once("../../library/include/systemasign.inc.php");

function ss_h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$id_tes = isset($_REQUEST['id_tes']) ? intval($_REQUEST['id_tes']) : 0;
$messaggio = '';
$errore = '';

// ----------------------------------------------------------------- file: QR e PDF firmato (passano da qui, la chiave resta nel server)
if (isset($_GET['qr']) && $id_tes && ss_collegato()) {
    $riga = ss_riga($id_tes);
    if ($riga) {
        list($c, $png) = ss_api('GET', '/api/gestionale/ddt/' . intval($riga['ss_id']) . '/qr.png');
        if ($c == 200 && is_string($png)) {
            header('Content-Type: image/png');
            header('Cache-Control: no-store');
            echo $png;
            exit;
        }
    }
    http_response_code(404);
    exit;
}
if (isset($_GET['firmato']) && $id_tes && ss_collegato()) {
    $riga = ss_riga($id_tes);
    $testata = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', $id_tes);
    if ($riga && $testata) {
        list($c, $pdf) = ss_api('GET', '/api/gestionale/ddt/' . intval($riga['ss_id']) . '/firmato.pdf');
        if ($c == 200 && is_string($pdf) && substr($pdf, 0, 4) == '%PDF') {
            $nome = preg_replace('/[^A-Za-z0-9]+/', '_', 'DDT_' . $testata['numdoc'] . '_' . $testata['seziva'] . '_' . $testata['datemi']) . '_firmato.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nome . '"');
            header('Cache-Control: no-store');
            echo $pdf;
            exit;
        }
    }
    $errore = 'La copia firmata non e\' disponibile.';
}

// ----------------------------------------------------------------- collegamento all'istanza
$impostazioni = isset($_REQUEST['impostazioni']) || !ss_collegato();
if ($impostazioni && $_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['collega']) || isset($_POST['scollega']))) {
    if ($admin_aziend['Abilit'] < 8) {
        $errore = 'Solo un amministratore puo\' collegare SystemaSign.';
    } elseif (isset($_POST['scollega'])) {
        ss_scollega();
        $messaggio = 'GAzie e\' scollegato da SystemaSign. In SystemaSign, Impostazioni > Stampanti, puoi scollegare anche lo "GAzie" dall\'elenco dei PC.';
    } else {
        // dal file agente.ini caricato, dal testo incollato o dai due campi
        $letti = array('server' => '', 'invito' => '');
        if (!empty($_FILES['file_ini']['tmp_name']) && is_uploaded_file($_FILES['file_ini']['tmp_name']) && $_FILES['file_ini']['size'] < 20000) {
            $letti = ss_leggi_ini(file_get_contents($_FILES['file_ini']['tmp_name']));
        }
        $dal_campo = ss_leggi_ini(isset($_POST['invito']) ? $_POST['invito'] : '');
        $url = trim(isset($_POST['url']) ? $_POST['url'] : '') ?: ($letti['server'] ?: $dal_campo['server']);
        if ($letti['server'] !== '') {
            $url = $letti['server'];
        }
        $invito = $letti['invito'] ?: ($dal_campo['invito'] ?: trim(isset($_POST['invito']) ? $_POST['invito'] : ''));
        if ($url === '' || $invito === '') {
            $errore = 'Carica il file agente.ini scaricato da SystemaSign, oppure scrivi indirizzo e invito.';
        } else {
            $nome = 'GAzie ' . trim($admin_aziend['ragso1']);
            $esito = ss_collega($url, $invito, substr($nome, 0, 80));
            if ($esito === '') {
                $messaggio = 'Fatto: GAzie e\' collegato a SystemaSign. Nell\'elenco DDT ora c\'e\' il pulsante con la matita.';
            } else {
                $errore = 'Collegamento non riuscito: ' . $esito;
            }
        }
    }
}
// richiesta di informazioni e listino (solo se GAzie non e' collegato)
$richiesta_fatta = false;
if ($impostazioni && !ss_collegato() && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['richiedi'])) {
    if (empty($_POST['consenso'])) {
        $errore = 'Per mandare la richiesta serve il consenso a essere ricontattati.';
    } else {
        $esito = ss_richiedi_listino(array(
            'azienda' => substr(trim((string)$_POST['r_azienda']), 0, 120),
            'referente' => substr(trim((string)$_POST['r_referente']), 0, 80),
            'email' => substr(trim((string)$_POST['r_email']), 0, 120),
            'telefono' => substr(trim((string)$_POST['r_telefono']), 0, 30),
            'messaggio' => substr(trim((string)$_POST['r_messaggio']), 0, 2000),
            'consenso' => true,
            'origine' => 'GAzie',
            'versione' => defined('GAZIE_VERSION') ? GAZIE_VERSION : '',
        ));
        if ($esito === '') {
            $messaggio = 'Richiesta mandata: ti ricontattiamo con il listino all\'indirizzo che hai indicato.';
            $richiesta_fatta = true;
        } else {
            $errore = 'Richiesta non mandata: ' . $esito;
        }
    }
}
// stato del collegamento (prova dal vivo) per la pagina delle impostazioni
$prova = null;
if ($impostazioni && ss_collegato()) {
    list($c, $prova) = ss_api('GET', '/api/gestionale/destinazioni');
    if ($c != 200 || empty($prova['ok'])) {
        $prova = array('ok' => false, 'errore' => is_array($prova) && !empty($prova['errore']) ? $prova['errore'] : 'errore ' . $c);
    }
}

// ----------------------------------------------------------------- invio del DDT
$testata = $id_tes ? gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', $id_tes) : null;
if ($id_tes && !$testata) {
    $errore = 'Documento non trovato.';
} elseif ($testata && !ss_documento_firmabile($testata)) {
    $errore = 'Si mandano alla firma i DDT di vendita e le fatture accompagnatorie.';
    $testata = null;
}
$elenco = ($testata && $testata['tipdoc'] == 'FAI') ? 'report_docven.php' : 'report_doctra.php';
$elenco_nome = ($testata && $testata['tipdoc'] == 'FAI') ? 'Elenco fatture' : 'Elenco DDT';
if (!$impostazioni && $testata && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['invia'])) {
    $tipo = $_POST['tipo'];
    $invia_a = array('tipo' => $tipo);
    $descr = 'solo collegamento';
    if ($tipo == 'autista') {
        $invia_a['autista_id'] = intval($_POST['autista_id']);
        $descr = 'autista ' . substr((string)$_POST['autista_nome'], 0, 80);
    } elseif ($tipo == 'banco') {
        $descr = 'banco';
    }
    $pdf = ss_pdf_ddt($testata);
    if ($pdf === '') {
        $errore = 'GAzie non ha prodotto il PDF del documento.';
    } else {
        $dati = ss_dati_ddt($testata);
        unset($dati['cellulare']);
        list($c, $r) = ss_api('POST', '/api/gestionale/ddt', array('pdf' => base64_encode($pdf), 'dati' => $dati, 'invia_a' => $invia_a));
        if ($c == 200 && !empty($r['ok'])) {
            ss_salva($id_tes, $r, $descr);
            header('Location: systemasign.php?id_tes=' . $id_tes . '&mandato=1');
            exit;
        }
        $errore = 'SystemaSign non ha accettato il documento: ' . (is_array($r) && !empty($r['errore']) ? $r['errore'] : 'errore ' . $c);
    }
}

// ----------------------------------------------------------------- stato aggiornato e destinazioni
$riga = ($testata && !$impostazioni) ? ss_riga($id_tes) : null;
$stato = null;
if ($riga) {
    list($c, $r) = ss_api('GET', '/api/gestionale/ddt/' . intval($riga['ss_id']));
    if ($c == 200 && !empty($r['ok'])) {
        $stato = $r;
        if ($r['stato'] != $riga['stato'] || (string)$r['firmatario'] != $riga['firmatario']) {
            ss_salva($id_tes, $r);
            $riga = ss_riga($id_tes);
        }
    }
}
$destinazioni = null;
if ($testata && !$impostazioni && (!$riga || $riga['stato'] == 'da_firmare')) {
    list($c, $d) = ss_api('GET', '/api/gestionale/destinazioni');
    if ($c == 200 && !empty($d['ok'])) {
        $destinazioni = $d;
    } elseif (!$errore) {
        $errore = 'SystemaSign non risponde: ' . (is_array($d) && !empty($d['errore']) ? $d['errore'] : 'errore ' . $c);
    }
}
if (isset($_GET['mandato']) && !$errore) {
    $messaggio = 'Documento mandato alla firma.';
}

require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<style>
  .ss-box { max-width: 720px; margin: 10px auto; }
  .ss-scelte .btn { display: block; width: 100%; margin-bottom: 10px; padding: 14px 10px; font-size: 17px; white-space: normal; text-align: left; }
  .ss-scelte .btn small { display: block; font-size: 12px; opacity: .8; }
  .ss-azioni .btn { margin: 0 6px 8px 0; padding: 10px 14px; font-size: 15px; }
  .ss-qr { width: 220px; max-width: 70%; border: 1px solid #ddd; border-radius: 6px; }
  .ss-righe { font-size: 13px; }
  .ss-intesta { display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 6px; }
</style>
<div class="ss-box">
<?php if ($messaggio) { ?><div class="alert alert-success"><?php echo ss_h($messaggio); ?></div><?php } ?>
<?php if ($errore) { ?><div class="alert alert-danger"><?php echo ss_h($errore); ?></div><?php } ?>

<?php if ($impostazioni) { ?>
  <div class="panel panel-default">
    <div class="panel-heading"><b>Firma digitale SystemaSign</b></div>
    <div class="panel-body">
      <p>Con SystemaSign i DDT e le fatture accompagnatorie si firmano sul telefono: dell'autista alla consegna, sul tablet del banco
        o sul telefono del cliente (QR o WhatsApp). La copia firmata torna qui in GAzie e parte per email al cliente.</p>
      <?php if (ss_collegato()) { ?>
        <?php if ($prova && !empty($prova['ok'])) { ?>
          <div class="alert alert-success">
            <b>Collegato</b> a <?php echo ss_h(ss_cfg('systemasign_url')); ?><?php echo !empty($prova['istanza']) ? ' (' . ss_h($prova['istanza']) . ')' : ''; ?>.<br>
            Autisti: <?php echo $prova['autisti'] ? ss_h(implode(', ', array_map(function ($a) { return $a['nome']; }, $prova['autisti']))) : 'nessuno (si creano in SystemaSign, Impostazioni &gt; Operatori)'; ?>.
          </div>
        <?php } else { ?>
          <div class="alert alert-danger"><b>Collegamento da rifare</b>: <?php echo ss_h(ss_cfg('systemasign_url')); ?> risponde "<?php echo ss_h($prova ? $prova['errore'] : ''); ?>".
            Scollega e ricollega con un invito nuovo.</div>
        <?php } ?>
        <?php if ($admin_aziend['Abilit'] >= 8) { ?>
        <form method="post" action="systemasign.php?impostazioni" onsubmit="return confirm('Scollegare GAzie da SystemaSign? I pulsanti di firma spariscono dagli elenchi finche\' non lo ricolleghi.');">
          <a class="btn btn-default" href="systemasign.php?impostazioni"><i class="glyphicon glyphicon-refresh"></i> Prova collegamento</a>
          <button class="btn btn-danger" name="scollega" value="1"><i class="glyphicon glyphicon-off"></i> Scollega</button>
        </form>
        <?php } ?>
      <?php } elseif ($admin_aziend['Abilit'] >= 8) { ?>
        <ol>
          <li>Procurati il <b>file di collegamento</b> (agente.ini): te lo da' il rivenditore, che lo scarica in SystemaSign da
            <b>Impostazioni &gt; Stampanti</b>. Vale per un solo collegamento e scade dopo 30 giorni.</li>
          <li>Caricalo qui sotto e premi <b>Collega</b>. Da quel momento nell'elenco DDT (e nelle fatture accompagnatorie) compare la matita per mandarli alla firma.</li>
        </ol>
        <form method="post" enctype="multipart/form-data" action="systemasign.php?impostazioni">
          <div class="form-group">
            <label>File agente.ini</label>
            <input type="file" name="file_ini" accept=".ini,text/plain">
          </div>
          <details style="margin-bottom:12px">
            <summary>Oppure scrivi indirizzo e invito</summary>
            <div class="form-group" style="margin-top:8px">
              <label>Indirizzo dell'istanza</label>
              <input class="form-control" name="url" placeholder="https://azienda.systemasign.com" value="<?php echo ss_h(ss_cfg('systemasign_url')); ?>">
            </div>
            <div class="form-group">
              <label>Invito</label>
              <input class="form-control" name="invito" placeholder="inv-..." autocomplete="off">
              <p class="help-block">La riga "invito = inv-..." del file agente.ini (si puo' incollare anche tutto il file).</p>
            </div>
          </details>
          <button class="btn btn-primary" name="collega" value="1"><i class="glyphicon glyphicon-link"></i> Collega</button>
        </form>
      <?php } else { ?>
        <p class="text-warning">Per collegare SystemaSign serve un utente amministratore di GAzie.</p>
      <?php } ?>
    </div>
  </div>
  <?php if (!ss_collegato() && !$richiesta_fatta) { ?>
  <div class="panel panel-info">
    <div class="panel-heading"><b>Non hai ancora SystemaSign?</b> Richiedi informazioni e listino</div>
    <div class="panel-body">
      <p>SystemaSign e' un servizio in abbonamento (<a href="https://systemasign.com" target="_blank" rel="noopener">systemasign.com</a>).
        Il modulo in GAzie e' gratuito: si paga solo il servizio, se lo attivi.</p>
      <form method="post" action="systemasign.php?impostazioni">
        <div class="row">
          <div class="form-group col-sm-6"><label>Azienda</label><input class="form-control" name="r_azienda" maxlength="120" required value="<?php echo ss_h($admin_aziend['ragso1']); ?>"></div>
          <div class="form-group col-sm-6"><label>Referente</label><input class="form-control" name="r_referente" maxlength="80" value="<?php echo ss_h(trim($admin_aziend['user_firstname'] . ' ' . $admin_aziend['user_lastname'])); ?>"></div>
          <div class="form-group col-sm-6"><label>Email</label><input class="form-control" type="email" name="r_email" maxlength="120" required value="<?php echo ss_h($admin_aziend['e_mail'] ?: $admin_aziend['user_email']); ?>"></div>
          <div class="form-group col-sm-6"><label>Telefono</label><input class="form-control" name="r_telefono" maxlength="30" value="<?php echo ss_h($admin_aziend['telefo']); ?>"></div>
        </div>
        <div class="form-group"><label>Messaggio</label>
          <textarea class="form-control" name="r_messaggio" rows="3" maxlength="2000">Vorrei ricevere il listino di SystemaSign per far firmare DDT e fatture accompagnatorie. Ne emettiamo circa ... al mese.</textarea></div>
        <div class="checkbox"><label><input type="checkbox" name="consenso" value="1" required>
          Acconsento a essere ricontattato per questa richiesta. I dati qui sopra vanno solo a SystemaSign e partono solo premendo il pulsante.</label></div>
        <button class="btn btn-info" name="richiedi" value="1"><i class="glyphicon glyphicon-envelope"></i> Richiedi il listino</button>
      </form>
    </div>
  </div>
  <?php } ?>

<?php } elseif ($testata) {
    $dati = ss_dati_ddt($testata);
    $link = $riga ? $riga['link'] : '';
?>
  <div class="panel panel-default">
    <div class="panel-heading ss-intesta">
      <span><b><?php echo ss_h(ss_nome_documento($testata)); ?> n. <?php echo ss_h($dati['numero']); ?></b> del <?php echo ss_h($dati['data_doc']); ?></span>
      <?php if ($riga) { ?><span class="label label-<?php echo ss_classe_stato($riga['stato']); ?>" style="font-size:13px"><?php echo ss_h(ss_testo_stato($riga['stato'])); ?></span><?php } ?>
    </div>
    <div class="panel-body">
      <p style="font-size:16px"><b><?php echo ss_h($dati['cliente']); ?></b><?php if ($dati['destinazione']) { ?><br><span class="text-muted"><?php echo ss_h($dati['destinazione']); ?></span><?php } ?></p>
      <table class="table table-condensed ss-righe">
        <?php foreach ($dati['righe'] as $r) { ?>
          <tr><td><?php echo ss_h($r['descrizione']); ?></td><td class="text-right" style="white-space:nowrap"><?php echo $r['quantita'] !== null ? ss_h(rtrim(rtrim(number_format($r['quantita'], 3, ',', ''), '0'), ',') . ' ' . $r['um']) : ''; ?></td></tr>
        <?php } ?>
      </table>

      <?php if ($riga && $riga['stato'] != 'da_firmare') { ?>
        <p>Firmato<?php echo $riga['firmatario'] ? ' da <b>' . ss_h($riga['firmatario']) . '</b>' : ''; ?><?php echo $stato && $stato['firmato_il'] ? ' il ' . ss_h($stato['firmato_il']) : ''; ?>.</p>
        <?php if ($stato && !empty($stato['riserve'])) { ?><div class="alert alert-warning">Firmato con riserve: controlla la copia firmata.</div><?php } ?>
        <div class="ss-azioni">
          <a class="btn btn-success" href="systemasign.php?id_tes=<?php echo $id_tes; ?>&firmato=1" target="_blank"><i class="glyphicon glyphicon-file"></i> Copia firmata</a>
          <a class="btn btn-default" href="<?php echo $elenco; ?>"><?php echo $elenco_nome; ?></a>
        </div>
        <?php if ($stato && !empty($stato['email_copia'])) { ?><p class="text-muted">Copia firmata mandata a <?php echo ss_h($stato['email_copia']); ?>.</p><?php } ?>
      <?php } else { ?>
        <?php if ($riga) { ?>
          <p>Mandato a: <b><?php echo ss_h($riga['destinazione']); ?></b>. Il cliente puo' firmare anche dal suo telefono:</p>
          <div class="ss-azioni">
            <a class="btn btn-primary" href="<?php echo ss_h($link); ?>" target="_blank"><i class="glyphicon glyphicon-pencil"></i> Firma su questo telefono</a>
            <?php
              $cell = $dati['cellulare'];
              $testo = ss_nome_documento($testata) . ' n. ' . $dati['numero'] . ' del ' . $dati['data_doc'] . ': per firmare la consegna apri ' . $link;
            ?>
            <a class="btn btn-success" href="https://wa.me/<?php echo $cell; ?>?text=<?php echo rawurlencode($testo); ?>" target="_blank"><i class="glyphicon glyphicon-share-alt"></i> WhatsApp<?php echo $cell ? ' +' . ss_h($cell) : ' (scegli il contatto)'; ?></a>
            <button type="button" class="btn btn-default" onclick="navigator.clipboard && navigator.clipboard.writeText('<?php echo ss_h($link); ?>'); this.innerText='Copiato';"><i class="glyphicon glyphicon-link"></i> Copia link</button>
          </div>
          <p><img class="ss-qr" src="systemasign.php?id_tes=<?php echo $id_tes; ?>&qr=1" alt="QR per firmare"></p>
          <p class="text-muted">Inquadra il QR col telefono del cliente. Questa pagina si aggiorna da sola quando arriva la firma.</p>
          <p class="text-muted"><?php echo $dati['email'] !== '' ? 'La copia firmata partira\' per email a <b>' . ss_h($dati['email']) . '</b>.' : 'Nessuna email in anagrafica: la copia firmata non parte per email (si puo\' scrivere al momento della firma).'; ?></p>
          <hr><p><b>Mandalo di nuovo</b> (se hai cambiato il documento o vuoi un'altra destinazione):</p>
        <?php } else { ?>
          <p><b>Dove lo mandi alla firma?</b></p>
        <?php } ?>
        <?php if ($destinazioni) { ?>
        <form method="post" action="systemasign.php" class="ss-scelte">
          <input type="hidden" name="id_tes" value="<?php echo $id_tes; ?>">
          <input type="hidden" name="invia" value="1">
          <input type="hidden" name="autista_nome" value="">
          <?php foreach ($destinazioni['autisti'] as $a) { ?>
            <button class="btn btn-primary" name="tipo" value="autista" onclick="this.form.autista_id.value='<?php echo intval($a['id']); ?>';this.form.autista_nome.value=this.dataset.nome;" data-nome="<?php echo ss_h($a['nome']); ?>">
              <i class="glyphicon glyphicon-road"></i> Autista: <?php echo ss_h($a['nome']); ?><small>Lo trova nell'app dell'autista, firma alla consegna</small></button>
          <?php } ?>
          <input type="hidden" name="autista_id" value="">
          <button class="btn btn-info" name="tipo" value="banco"><i class="glyphicon glyphicon-shopping-cart"></i> Banco<small>Il cliente firma sul tablet del banco</small></button>
          <button class="btn btn-default" name="tipo" value="collegamento"><i class="glyphicon glyphicon-phone"></i> Solo collegamento<small>Firma qui, col QR o col link su WhatsApp</small></button>
        </form>
        <?php } ?>
      <?php } ?>
    </div>
  </div>
  <p class="text-center"><a href="<?php echo $elenco; ?>"><?php echo $elenco_nome; ?></a> &middot; <a href="systemasign.php?impostazioni&id_tes=<?php echo $id_tes; ?>">Impostazioni SystemaSign</a></p>
  <?php if ($riga && $riga['stato'] == 'da_firmare') { ?>
  <script>
    // quando il DDT viene firmato la pagina si ricarica e mostra la copia firmata
    setTimeout(function () { if (!document.hidden) { location.href = 'systemasign.php?id_tes=<?php echo $id_tes; ?>'; } else { location.reload(); } }, 20000);
  </script>
  <?php } ?>
<?php } ?>
</div>
<?php
require("../../library/include/footer.php");
