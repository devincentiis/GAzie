// Ricerca globale nella barra in alto: tendina con i risultati raggruppati per tipo.
// I dati arrivano da modules/root/global_search.php?ajax=1; le pagine del menu si cercano direttamente nella barra laterale.
$(function () {
  var $input = $('#gz-gs-input');
  if (!$input.length) return;
  var $drop = $('<div class="gz-gs-drop"></div>').hide().insertAfter($input);
  var timer = null, lastQ = '', xhr = null;
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

  function render(q, groups, withPages) {
    $drop.empty();
    var menu = withPages ? menuMatches(q) : [];
    if (menu.length) groups = [{group: 'Pagine', icon: 'fa-bars', rows: menu}].concat(groups);
    if (!groups.length) {
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
    $drop.append($('<a class="gz-gs-all"></a>').attr('href', endpoint + '?q=' + encodeURIComponent(q)).text('Vedi tutti i risultati per "' + q + '"'));
    $drop.show();
  }

  function search() {
    var q = $.trim($input.val());
    if (q.replace(/[%*?]/g, '').length < 2) { $drop.hide(); lastQ = ''; return; }
    if (q === lastQ) { $drop.show(); return; }
    lastQ = q;
    if (xhr) xhr.abort();
    xhr = $.getJSON(endpoint, {ajax: 1, q: q}, function (data) {
      if ($.trim($input.val()) === q) render(q, data.groups || [], data.pages !== false);
    });
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
      if ($cur.length) { window.location.href = $cur.attr('href'); return; }
      var q = $.trim($input.val());
      if (q) window.location.href = endpoint + '?q=' + encodeURIComponent(q);
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
