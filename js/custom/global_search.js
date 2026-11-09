// Ricerca globale nella barra in alto: tendina con i risultati raggruppati per tipo.
// I dati arrivano da modules/root/global_search.php?ajax=1; le pagine del menu si cercano direttamente nella barra laterale.
$(function () {
  var $input = $('#gz-gs-input');
  var modeVal = $input.attr('mode') ?? "";
  if (!$input.length) return;
  var $drop = $('<div class="gz-gs-drop"></div>').hide().insertAfter($input);
  var timer = null, lastQ = '', xhr = null, lastArt = null;

  // dopo un inserimento dalla ricerca (pagina ricaricata) il cursore torna nella casella di ricerca;
  // il piccolo ritardo serve perché alcune pagine dei documenti spostano il focus su un loro campo al caricamento
  try {
    if (sessionStorage.getItem('gzGsRefocus') === '1') {
      sessionStorage.removeItem('gzGsRefocus');
      setTimeout(function () { $input.focus(); }, 300);
    }
  } catch (e) {}
  var endpoint = '../../modules/root/global_search.php';

  // voci di menu della barra laterale che contengono il testo (solo quelle con un link vero)
  function menuMatches(q) {
    var out = [], seen = {};
    // stessi jolly della ricerca sul server: * e % = qualsiasi testo, ? = un carattere
    var re = new RegExp(q.replace(/[.+^${}()|[\]\\]/g, '\\$&').replace(/[*%]+/g, '.*').replace(/\?/g, '.'), 'i');
    $('.sidebar-menu a').each(function () {
      var href = $(this).attr('href') || '';
      var text = $.trim($(this).text());
      if (!href || href === '#' || !text || seen[href]) return;
      if (re.test(text)) {
        seen[href] = true;
        out.push({title: text, detail: '', url: href});
      }
    });
    return out.slice(0, 5);
  }

  // pezzi del termine separati dai jolly (* % = qualsiasi testo, ? = un carattere), già pronti per una RegExp
  function pieces(q) {
    return q.split(/[%*?]+/).map(function (p) { return $.trim(p); }).filter(function (p) { return p.length >= 2; })
      .map(function (p) { return p.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); });
  }

  function addText(el, text, q) {
    // evidenzia i pezzi del termine senza mai inserire HTML proveniente dai dati
    var p = pieces(q);
    if (!p.length) { el.appendChild(document.createTextNode(text)); return; }
    var re = new RegExp(p.join('|'), 'gi'), last = 0, m;
    while ((m = re.exec(text)) !== null) {
      if (m[0] === '') { re.lastIndex++; continue; }
      el.appendChild(document.createTextNode(text.slice(last, m.index)));
      var mk = document.createElement('mark');
      mk.textContent = m[0];
      el.appendChild(mk);
      last = m.index + m[0].length;
    }
    el.appendChild(document.createTextNode(text.slice(last)));
  }

  // pagina di un documento (admin_docven, admin_broven, admin_docacq, admin_broacq...): ha il campo articolo e il pulsante di conferma riga
  function docForm() {
    if (!/\/admin_[a-z_]+\.php$/i.test(window.location.pathname)) return null;
    var btn = document.querySelector('button[name="in_submit"]');
    if (!btn || !btn.form || !btn.form.elements['in_codart']) return null;
    return btn;
  }

  // inserisce l'articolo nel documento aperto come fa la lettura con la pistola barcode: codice nel campo articolo, quantità 1, conferma riga
  function insertIntoDocument(codice) {
    var btn = docForm();
    if (!btn) return false;
    var f = btn.form;
    var el = f.elements['in_codart'];
    if (el.tagName === 'SELECT') {
      var found = false;
      for (var i = 0; i < el.options.length; i++) { if (el.options[i].value === codice) { found = true; break; } }
      if (!found) el.add(new Option(codice, codice));
    }
    el.value = codice;
    if (f.elements['cosear']) f.elements['cosear'].value = codice;
    var qt = f.elements['in_quanti'];
    if (qt && (!qt.value || parseFloat(String(qt.value).replace(',', '.')) === 0)) qt.value = 1;
    // la pagina si ricarica con la nuova riga: al caricamento il cursore torna nella ricerca, pronto per la lettura successiva
    try { sessionStorage.setItem('gzGsRefocus', '1'); } catch (e) {}
    if (f.requestSubmit) { f.requestSubmit(btn); } else { btn.click(); }
    return true;
  }

  // articolo trovato con il codice esatto: nel documento aperto lo inserisce, altrimenti apre la scheda articolo
  function useArticle(art) {
    $drop.hide();
    if (insertIntoDocument(art.codice)) { $input.val(''); lastQ = ''; return; }
    window.location.href = art.url;
  }

  function render(q, groups, withPages, tcLink, art) {
    // se tcLink non è false vuol dire che è stato selezionato uno script tramite trans_code per link rapido da tastiera
    if (tcLink){
      window.location.href = "../"+tcLink;
    }
    $drop.empty();
    // codice articolo o barcode esatto: prima voce della tendina, Invio la usa subito
    if (art) {
      $drop.append($('<div class="gz-gs-head"></div>').append($('<i class="fa fa-barcode"></i>')).append(document.createTextNode(' Articolo')));
      var aa = document.createElement('a');
      aa.className = 'gz-gs-item gz-gs-art';
      aa.href = art.url;
      var at = document.createElement('span');
      at.appendChild(document.createTextNode(art.codice + ' - ' + art.descri));
      aa.appendChild(at);
      var ad = document.createElement('small');
      ad.appendChild(document.createTextNode(docForm() ? 'Invio: inserisci nel documento' : 'Invio: apri la scheda articolo'));
      aa.appendChild(ad);
      $(aa).on('click', function (ev) { ev.preventDefault(); useArticle(art); });
      $drop.append(aa);
    }
    var menu = withPages ? menuMatches(q) : [];
    // le funzioni rapide restano in cima; le pagine del menu vengono subito dopo
    if (menu.length) {
      var pg = {group: 'Pagine', icon: 'fa-bars', rows: menu};
      groups = (groups.length && groups[0].group === 'Funzioni rapide') ? [groups[0], pg].concat(groups.slice(1)) : [pg].concat(groups);
    }
    if (!groups.length && !art) {
      $drop.append($('<div class="gz-gs-empty"></div>').text('Nessun risultato'));
    }
    $.each(groups, function (_, g) {
      $drop.append($('<div class="gz-gs-head"></div>').append($('<i class="fa"></i>').addClass(g.icon)).append(document.createTextNode(' ' + g.group)));
      $.each(g.rows, function (_, r) {
        var a = document.createElement('a');
        a.className = 'gz-gs-item';
        a.href = r.url;
        var t = document.createElement('span');
        addText(t, r.title, q);
        a.appendChild(t);
        if (r.detail) {
          var d = document.createElement('small');
          addText(d, r.detail, q);
          a.appendChild(d);
        }
        $drop.append(a);
      });
    });
    $drop.append($('<a class="gz-gs-all"></a>').attr('href', endpoint + '?q=' + encodeURIComponent(q) + '&mode=' + modeVal).text('Vedi tutti i risultati per "' + q + '"'));
    $drop.show();
  }

  function search() {
    var q = $.trim($input.val());
    if (q.replace(/[%*?]/g, '').length < 2) { $drop.hide(); lastQ = ''; return; }
    if (q === lastQ) { $drop.show(); return; }
    lastQ = q;
    if (xhr) xhr.abort();
    xhr = $.getJSON(endpoint, {ajax: 1, q: q, mode: modeVal}, function (data) {
      lastArt = {q: q, art: data.artico_exact || null};
      if ($.trim($input.val()) === q) render(q, data.groups || [], data.pages !== false, data.tc_link, data.artico_exact || null);
    });
  }

  // Invio senza voce scelta: se il testo è un codice articolo esatto lo usa, altrimenti apre la pagina dei risultati.
  // La pistola barcode scrive il codice e preme Invio subito, prima che parta la ricerca: in quel caso si chiede al server ora.
  function enterWithoutSelection(q) {
    var goResults = function () { window.location.href = endpoint + '?q=' + encodeURIComponent(q); };
    if (lastArt && lastArt.q === q) {
      if (lastArt.art) { useArticle(lastArt.art); } else { goResults(); }
      return;
    }
    clearTimeout(timer);
    if (xhr) xhr.abort();
    $.getJSON(endpoint, {ajax: 1, q: q}, function (data) {
      if (data.tc_link) { window.location.href = '../' + data.tc_link; return; }
      if (data.artico_exact) { useArticle(data.artico_exact); return; }
      goResults();
    }).fail(goResults);
  }

  $input.on('input', function () {
    clearTimeout(timer);
    timer = setTimeout(search, 300);
  });
  $input.on('focus', function () { if (lastQ) $drop.show(); });

  // frecce per scorrere, Invio per aprire la voce scelta o la pagina dei risultati, Esc per chiudere
  $input.on('keydown', function (e) {
    var $items = $drop.find('a.gz-gs-item, a.gz-gs-all');
    var $cur = $items.filter('.active');
    var idx = $items.index($cur);
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      if (!$items.length) return;
      idx = (e.key === 'ArrowDown') ? Math.min(idx + 1, $items.length - 1) : Math.max(idx - 1, 0);
      $cur.removeClass('active');
      $items.eq(idx).addClass('active')[0].scrollIntoView({block: 'nearest'});
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if ($cur.length) {
        if ($cur.hasClass('gz-gs-art')) { $cur.trigger('click'); return; }
        window.location.href = $cur.attr('href');
        return;
      }
      var q = $.trim($input.val());
      if (q) enterWithoutSelection(q);
    } else if (e.key === 'Escape') {
      $drop.hide();
      $input.blur();
    }
  });

  $(document).on('mousedown', function (e) {
    if (!$(e.target).closest('.gz-gs').length) $drop.hide();
  });

  // Ctrl+K (o Cmd+K) da qualsiasi pagina porta il cursore nel campo di ricerca
  $(document).on('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
      e.preventDefault();
      $input.focus().select();
    }
  });
});
