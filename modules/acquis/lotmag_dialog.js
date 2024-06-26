
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2018 - Antonio De Vincentiis Montesilvano (PE)
  (http://www.devincentiis.it)
  <http://gazie.sourceforge.net>
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
function lotmagDialog(ref) {
    var nrow = ref.id.substring(6, 15),
            tips = $(".validateTips");
    ;
    function updateTips(t) {
        tips.text(t).addClass("ui-state-highlight");
        setTimeout(function () {
            tips.removeClass("ui-state-highlight", 1500);
        }, 500);
    }
    function checkField(open) {
        var bval = true;
        var files = $('#' + nrow + '_file').prop("files");
        var fi = $.map(files, function (val) {
            return val.name;
        });
        var id = $("#" + nrow + "_identifier").val();
        if (fi == "") { // non è stato scelto il file
            updateTips("Errore! Non è stato selezionato un file ");
            bval = false;
        } else if (id == "") {// non è stata scritto il seriale, avverto che ne assegnerò uno interno 
            updateTips("Attenzione! Verrà asseganto un valore automatico al numero di serie/matricola/identificativo/targa del prodotto. ");
            bval = false;
            $("#" + nrow + "_identifier").val('#');
        }
        return bval;
    }

    $("#lm_dialog" + nrow).dialog({
        autoOpen: false,
        show: "scale",
        width: "80%",
        modal: true,
        open: function () {
            $('#' + nrow + '_identifier').change(function () {
                $('#lotmag_' + nrow + '_identifier').val($(this).val());
            });
            $('#' + nrow + '_identifier').change(function () {
                $('#lotmag_' + nrow + '_file').val($(this).val());
            });
            $('#' + nrow + '_expiry').change(function () {
                $('#lotmag_' + nrow + '_expiry').val($(this).val());
            });
            $('#' + nrow + '_expiry').datepicker({
                dateFormat: "dd-mm-yy"
            });
        },
        buttons: {
            "Conferma": function () {
                $(this).dialog("close");
            }
        },
        beforeClose: function (event, ui) {
            if (!checkField(true)) {
                return false;
            } else {
                updateTips("");
            }
        },
        close: function () {
        }
    });
    $("#lm_dialog" + nrow).dialog("open");
}
