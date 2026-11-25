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
  Prezzo, costo d'acquisto e prezzo consigliato nel riquadro di inserimento
  rigo (admin_docven.php, admin_broven.php). Caricato solo con
  prezzo_consigliato = 1.
  - articolo scelto: dati da dati_articolo_doc.php; prezzo di vendita a zero
    = propone il consigliato (giallo)
  - costo mancante e prezzo scritto: costo probabile dal ricarico (arancio)
  - costo corretto a mano: ricalcola il consigliato
  opzioni.clfoco, opzioni.listin: cliente e listino del documento
  opzioni.decimali: decimali dei prezzi
*/
function prezzoConsigliato(opzioni) {
  opzioni = opzioni || {};
  $(function () {
    var prezzo = $("#in_prelis");
    var acquisto = $("#in_acquisto");
    var consigliato = $("#in_prezzo_consigliato");
    var riferimento = $("#pc_codart"); // articolo a cui si riferiscono prezzo e costo
    var stato = $("#pc_costo"); // 0 = dalla scheda, 1 = scritto a mano, 2 = probabile
    var GIALLO = "#fcf8e3";
    var ARANCIO = "#ffe0b2";
    var decimali = (opzioni.decimali >= 0) ? opzioni.decimali : 2;
    var ricarico = 0;
    var richiesto = "";
    var prezzoToccato = false; // scritti a mano mentre arrivano i dati: non li sovrascrivo
    var costoToccato = false;
    if (!prezzo.length) {
      return;
    }

    function numero(v) {
      var n = parseFloat(String(v).replace(",", "."));
      return isNaN(n) ? 0 : n;
    }
    function arrotonda(n) {
      var f = Math.pow(10, decimali);
      return Math.round(n * f) / f;
    }
    function daCosto(costo) {
      return (costo > 0) ? arrotonda(costo * (1 + ricarico / 100)) : 0;
    }
    function colora(campo, colore) {
      campo.css({"background-color": colore || "", "font-style": (colore === GIALLO) ? "italic" : ""});
    }
    function codiceScelto() {
      var c = $('[name="in_codart"]');
      return c.length ? String(c.val() || "") : "";
    }
    function rigoArticolo() { // rigo normale (anche con allegato)
      var t = $('[name="in_tiprig"]');
      return !t.length || t.val() === "0" || t.val() === "50";
    }

    // dati dell'articolo; nuovo = articolo appena scelto (prezzo e costo si rifanno)
    function carica(codice, nuovo) {
      if (!codice || !rigoArticolo()) {
        return;
      }
      richiesto = codice;
      if (nuovo) { // i dati dell'articolo precedente non valgono piu'
        prezzoToccato = false;
        costoToccato = false;
        ricarico = 0;
        acquisto.val("");
        consigliato.val("");
        stato.val("0");
        colora(acquisto, "");
      }
      $.ajax({
        url: "../../modules/vendit/dati_articolo_doc.php",
        dataType: "json",
        cache: false,
        data: {codart: codice, clfoco: opzioni.clfoco || 0, listin: $('[name="listin"]').val() || opzioni.listin || 0}
      }).done(function (d) {
        if (!d || d.errore || codice !== richiesto) {
          return;
        }
        ricarico = numero(d.ricarico);
        if (!costoToccato && acquisto.val() === "") {
          acquisto.val(d.last_cost);
          stato.val("0");
        }
        colora(acquisto, (stato.val() === "2") ? ARANCIO : "");
        consigliato.val(daCosto(numero(acquisto.val())));
        if (prezzoToccato) {
          stimaCosto();
        }
        var pc = numero(consigliato.val());
        if (!prezzoToccato && (nuovo || numero(prezzo.val()) <= 0)) {
          if (numero(d.prezzo_vendita) > 0) {
            prezzo.val(d.prezzo_vendita);
            colora(prezzo, "");
          } else if (pc > 0) {
            prezzo.val(pc); // prezzo di vendita a zero: propongo il consigliato
            colora(prezzo, GIALLO);
          } else {
            prezzo.val(0);
            colora(prezzo, "");
          }
        } else if (numero(d.prezzo_vendita) <= 0 && pc > 0 && numero(prezzo.val()) === pc) {
          colora(prezzo, GIALLO);
        }
        riferimento.val(codice);
      });
    }

    // costo mancante (o gia' stimato) e prezzo scritto: costo probabile dal ricarico
    function stimaCosto() {
      var pv = numero(prezzo.val());
      var costoNoto = (stato.val() === "1" || (stato.val() === "0" && numero(acquisto.val()) > 0));
      if (!costoNoto && ricarico > 0 && pv > 0) {
        var costo = arrotonda(pv / (1 + ricarico / 100));
        acquisto.val(costo);
        stato.val("2");
        colora(acquisto, ARANCIO);
        consigliato.val(daCosto(costo));
      }
    }

    // prezzo scritto: vale per l'articolo scelto
    prezzo.on("input", function () {
      prezzoToccato = true;
      colora(prezzo, "");
      riferimento.val(codiceScelto());
      stimaCosto();
    });

    // costo corretto a mano: niente piu' "probabile", ricalcolo il consigliato
    acquisto.on("input", function () {
      costoToccato = true;
      stato.val("1");
      colora(acquisto, "");
      consigliato.val(daCosto(numero(acquisto.val())));
    });

    // scelta dell'articolo senza ricaricare la pagina (elenco o ricerca)
    $(document).on("change", 'select[name="in_codart"]', function () {
      carica(String($(this).val() || ""), true);
    });
    $(document).on("autocompleteselect", "#search_cosear", function (e, ui) {
      if (ui && ui.item && ui.item.id) {
        carica(String(ui.item.id), true);
      }
    });

    // all'apertura: articolo gia' nel riquadro
    var codice = codiceScelto();
    carica(codice, codice !== riferimento.val());
  });
}
