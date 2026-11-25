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
  Inserimento rapido da tastiera nei documenti di vendita (admin_docven.php,
  admin_broven.php). Caricato solo con inserimento_rapido = 1.
  Sequenza: cliente -> articolo (Invio sceglie il primo trovato) -> quantita'
  (Invio inserisce il rigo; se c'e' il campo prezzo #in_prelis passa prima
  da li') -> il cursore torna sull'articolo. Invio in un rigo gia' inserito
  e F10 confermano il documento.
  opzioni.fuoco: 'cliente', 'articolo' o '' (dove parte il cursore)
  opzioni.spostaPannello: true = riquadro di inserimento sopra i righi
*/
function inserimentoRapido(opzioni) {
  opzioni = opzioni || {};
  $(function () {
    var cerca = $("#search_cosear");
    var quanti = $('input[name="in_quanti"]');

    function inserisciRigo() {
      var b = document.getElementsByName("in_submit");
      if (b.length) {
        b[0].click();
      }
    }
    function confermaDocumento() {
      var c = document.getElementById("preventDuplicate");
      if (c && !c.disabled) {
        c.click();
      }
    }
    function eInvio(e) {
      return e.which === 13 || e.keyCode === 13;
    }

    // riquadro di inserimento subito sotto l'intestazione (stesso form, il POST non cambia)
    if (opzioni.spostaPannello) {
      var pannello = $(".panel.input-area");
      var corpo = $("#products-list").closest(".box-primary");
      if (pannello.length && corpo.length) {
        pannello.insertBefore(corpo);
      }
    }

    // ricerca articolo mentre si scrive (codice, descrizione, barcode, codice fornitore):
    // la scelta non ricarica la pagina, porta il cursore sulla quantita'
    if (cerca.length && $.fn.autocomplete) {
      cerca.attr("autocomplete", "off");
      cerca.autocomplete({
        html: true,
        source: "../../modules/root/search.php?opt=product",
        minLength: 2,
        autoFocus: true,
        open: function () {
          $(".ui-autocomplete").css("z-index", 1000);
        },
        select: function (event, ui) {
          $('[name="in_codart"]').remove();
          $("<input>").attr({type: "hidden", name: "in_codart"}).val(ui.item.id).insertAfter(cerca);
          cerca.val(ui.item.id);
          quanti.focus().select();
          return false;
        }
      });
      // Invio a tendina chiusa: niente invio implicito del form
      cerca.on("keydown", function (e) {
        if (eInvio(e) && !$(".ui-autocomplete").is(":visible")) {
          e.preventDefault();
        }
      });
    }

    // quantita': Invio va al prezzo se scrivibile, altrimenti inserisce il rigo
    quanti.on("keydown", function (e) {
      if (eInvio(e)) {
        e.preventDefault();
        var prezzo = $("#in_prelis:visible");
        if (prezzo.length) {
          prezzo.focus().select();
        } else {
          inserisciRigo();
        }
      }
    });
    $(document).on("keydown", "#in_prelis", function (e) {
      if (eInvio(e)) {
        e.preventDefault();
        inserisciRigo();
      }
    });

    // Invio in un rigo gia' inserito: conferma il documento
    // (l'invio implicito premerebbe il primo pulsante del form)
    $(document).on("keydown", 'input[name^="rows["]', function (e) {
      if (eInvio(e)) {
        e.preventDefault();
        confermaDocumento();
      }
    });

    // F10: conferma il documento
    $(document).on("keydown", function (e) {
      if (e.which === 121 || e.keyCode === 121) {
        e.preventDefault();
        confermaDocumento();
      }
    });

    // cursore di partenza, se la pagina non ne ha gia' scelto uno
    // (campo modificato prima del ricaricamento, lettore di codici a barre)
    var giaScelto = $('input[name="last_focus"]').val() || $('input[name="in_barcode"]').length;
    if (opzioni.fuoco && !giaScelto) {
      setTimeout(function () {
        var campo;
        if (opzioni.fuoco === "cliente") {
          campo = document.getElementById("search_clfoco");
        } else {
          campo = document.getElementById("search_cosear");
          if (campo && campo.value) {
            campo = null; // rigo in modifica: resto dove sono
          }
        }
        if (campo) {
          campo.focus();
        }
      }, 350);
    }
  });
}
