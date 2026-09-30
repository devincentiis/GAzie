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
function dialogSchedule(paymov) {

    var wWidth = $(window).width(),
            dWidth = wWidth * 0.7,
            clfoco = paymov.id.substring(6, 15),
            nrow = paymov.id.substring(23),
            id_rig = $("#id_rig_rc" + nrow).val(),
            tesdoc_ref = $("#post_" + nrow + "_0_id_tesdoc_ref").val();
    tot_amount = $("#impoRC" + nrow).val(); //mi servirà per controllare che il totale delle partite sia uguale a questo

    var descri = $("#descri"),
            expiry = $("#expiry"),
            amount = $("#amount"),
            allFields = $([]).add(descri).add(expiry).add(amount),
            tips = $(".validateTips");

    //disegno la prima riga della table (con i titoli delle colonne e il tasto +)
    function updateCloseFormOnStart() {
        $("#pm_form_container_" + nrow + " tbody tr").remove();

        $("#pm_form_container_" + nrow + " tbody").replaceWith('<tbody> <tr id="pm_header_' + nrow + '" def="nopost" >' +
                '<td class="ui-widget ui-widget-content " >ID partita </td>' +
                '<td class="ui-widget ui-widget-content " >Data Chiusura</td>' +
                '<td class="ui-widget-right ui-widget-content ">Importo</td>' +
                '<td class="ui-widget-right ui-widget-content "><button id="addCloseExpiry' + nrow + '" value="' + nrow + '" class="btn btn-xs btn-default">' +
                '<i class="glyphicon glyphicon-plus"></i></button></td></tr></tbody>');
    }

    //ridisegno tutta la table tranne la prima riga
    function updateCloseForm() {
        $("#pm_form_container_" + nrow + " tbody tr:not(:first)").remove();

        $("#pm_post_container_" + nrow + " div").each(function (i, v) {
            var idv = $(v).attr('id').split('_');
            var id_sub = idv[2];
            var id = $('input[id=post_' + nrow + '_' + id_sub + '_id_tesdoc_ref]:first', v).focus().attr('value');
            var ex = $('input[id=post_' + nrow + '_' + id_sub + '_expiry]:first', v).focus().attr('value');
            var am = $('input[id=post_' + nrow + '_' + id_sub + '_amount]:first', v).focus().attr('value');

            updateCloseFormRiga(id_sub, id, ex, am);
        });
    }

    //disegno la singola riga della table
    function updateCloseFormRiga(id_sub, id, ex, am) {
        $("#pm_form_container_" + nrow + " tbody").append('<tr id="pm_form_' + id_sub + '">' +
                '<td><button class="btn btn-xs btn-default" id="unlink_' + id_sub + '"><img id="image_unlink_' + id_sub + '" src="../../library/images/link_break.png" width="12" /></button> ' + id + '</td>' +
                '<td class="ui-widget-right ui-widget-content " ><input id="form_' + nrow + '_' + id_sub + '_expiry" type="text" name="paymov[' + nrow + '][' + id_sub + '][expiry]" value="' + ex + '" id="post_' + nrow + '_' + id_sub + '_expiry" /></td>' +
                '<td class="ui-widget-right ui-widget-content " ><input id="form_' + nrow + '_' + id_sub + '_amount" style="text-align:right;" type="text" name="paymov[' + nrow + '][' + id_sub + '][amount]" value="' + am + '" id="post_' + nrow + '_' + id_sub + '_amount" /></td>' +
                '<td class="ui-widget-right ui-widget-content " ><button id="btn_' + id_sub + '" class="btn btn-xs btn-default"><i class="glyphicon glyphicon-remove"></i></button></td>' +
                '</tr>');

        $('#form_' + nrow + '_' + id_sub + '_expiry').change(function () {
            if (checkDate($(this), "La data di Scadenza è sbagliata (gg-mm-aaaa)")) {
                $('#post_' + nrow + '_' + id_sub + '_expiry').val($(this).val());
            }
        });

        $('#form_' + nrow + '_' + id_sub + '_expiry').datepicker({
            dateFormat: "dd-mm-yy"
        });

        $('#form_' + nrow + '_' + id_sub + '_amount').change(function () {
            if (checkAmount($(this), "L'importo è sbagliato")) {
                $('#post_' + nrow + '_' + id_sub + '_amount').val($(this).val());
            }
        });

        //click sul tasto X, cancello la riga
        $("#btn_" + id_sub).click(function () {
            $("#pm_form_" + id_sub).remove();
            $("#pm_post_" + id_sub).remove();
            updateCloseForm();
        });

        $("#unlink_" + id_sub).click(function () {
            $('#post_' + nrow + '_' + id_sub + '_id_tesdoc_ref').val('');
            updateCloseForm();
            $('#image_unlink_' + id_sub).remove();
        });
    }

    function updateOpenFormOnStart() {
        $("#pm_form_container_" + nrow + " tbody tr").remove();
        $("#pm_form_container_" + nrow + " tbody").replaceWith('<tbody> <tr id="pm_header_' + nrow + '" def="nopost" >' +
                '<td class="ui-widget ui-widget-content " >ID partita </td>' +
                '<td class="ui-widget ui-widget-content " >Scadenza</td>' +
                '<td class="ui-widget-right ui-widget-content ">Importo</td>' +
                '<td class="ui-widget-right ui-widget-content "><button id="addOpenExpiry' + nrow + '" value="' + nrow + '" class="btn btn-xs btn-default">' +
                '<i class="glyphicon glyphicon-plus"></i></button></td></tr></tbody>');
    }

    function updateOpenForm() {
        $("#pm_form_container_" + nrow + " tbody tr:not(:first)").remove();
        $("#pm_post_container_" + nrow + " div").each(function (i, v) {
            var idv = $(v).attr('id').split('_');
            var id_sub = idv[2];
            var id = $('input[id=post_' + nrow + '_' + id_sub + '_id_tesdoc_ref]:first', v).focus().attr('value');
            var ex = $('input[id=post_' + nrow + '_' + id_sub + '_expiry]:first', v).focus().attr('value');
            var am = $('input[id=post_' + nrow + '_' + id_sub + '_amount]:first', v).focus().attr('value');

            updateOpenFormRiga(id_sub, id, ex, am);
        });
    }

    function updateOpenFormRiga(id_sub, id, ex, am) {
        $("#pm_form_container_" + nrow + " tbody").append('<tr id="pm_form_' + id_sub + '">' +
                '<td><button class="btn btn-xs btn-default" id="unlink_' + id_sub + '"><img id="image_unlink_' + id_sub + '" src="../../library/images/link_break.png" width="12" /></button> ' + id + '</td>' +
                '<td class="ui-widget-right ui-widget-content " ><input id="form_' + nrow + '_' + id_sub + '_expiry" type="text" name="paymov[' + nrow + '][' + id_sub + '][expiry]" value="' + ex + '" id="post_' + nrow + '_' + id_sub + '_expiry" /></td>' +
                '<td class="ui-widget-right ui-widget-content " ><input id="form_' + nrow + '_' + id_sub + '_amount" style="text-align:right;" type="text" name="paymov[' + nrow + '][' + id_sub + '][amount]" value="' + am + '" id="post_' + nrow + '_' + id_sub + '_amount" /></td>' +
                '<td class="ui-widget-right ui-widget-content " ><button id="btn_' + id_sub + '" class="btn btn-xs btn-default"><i class="glyphicon glyphicon-remove"></i></button></td>' +
                "</tr>");

        $('#form_' + nrow + '_' + id_sub + '_expiry').change(function () {
            if (checkDate($(this), "La data di Scadenza è sbagliata (gg-mm-aaaa)")) {
                $('#post_' + nrow + '_' + id_sub + '_expiry').val($(this).val());
            }
        });

        $('#form_' + nrow + '_' + id_sub + '_expiry').datepicker({
            dateFormat: "dd-mm-yy"
        });

        $('#form_' + nrow + '_' + id_sub + '_amount').change(function () {
            if (checkAmount($(this), "L'importo è sbagliato")) {
                $('#post_' + nrow + '_' + id_sub + '_amount').val($(this).val());
            }
        });

        $("#btn_" + id_sub).click(function () {
            $("#pm_form_" + id_sub).remove();
            $("#pm_post_" + id_sub).remove();
            updateOpenForm();
        });
        $("#unlink_" + id_sub).click(function () {
            $('#post_' + nrow + '_' + id_sub + '_id_tesdoc_ref').val('');
            updateOpenForm();
            $('#image_unlink_' + id_sub).remove();
        });
    }

    function getSamePaymov(tes_ref, excl_val, link) {
        $.get("same_paymov.php",
                {id_tesdoc_ref: tes_ref, id_exc: excl_val},
                function (data) {
                    var j = 0;
                    $.each(data, function (i, value) {
						var desnumdoc=value.numdoc?' n.'+value.numdoc+'/'+value.seziva+' del '+value.datdoc:'';
                        if (j == 0) {
                            if (link) {
                                link_ref = '<button id="linking_same_' + j + '" class="btn btn-xs btn-default"><i class="glyphicon glyphicon-link"></i></button>';
                            }
                            $("#db-contain" + nrow + " tbody").append("<tr>" +
                                    "<td class='ui-widget-content ui-state-active' colspan=7" + ' class="ui-widget ui-widget-content " > Altri movimenti della stessa partita ' + tes_ref + ' ' + link_ref + "</td></tr>");
                        }
                        $("#db-contain" + nrow + " tbody").append("<tr>" +
                                "<td" + ' class="ui-widget ui-widget-content " ></td>' +
                                "<td" + ' class="ui-widget ui-widget-content " > ' + value.descri + desnumdoc + "</td>" +
                                "<td" + ' class="ui-widget ui-widget-content " >' + value.expiry + "</td>" +
                                "<td" + ' class="ui-widget-right ui-widget-content " >' + value.amount + "</td>" +
                                '<td class="ui-widget-right ui-widget-content " >' + value.darave + '</td>' +
                                '<td class="ui-widget-right ui-widget-content "><a target="NEW" href="admin_movcon.php?id_tes=' + value.id_tes + '&Update" class="btn btn-xs btn-default"><i class="glyphicon glyphicon-new-window"></i></a></td>' +
                                "</tr>");

                        $("#linking_same_" + j).click(function () {
                            var docref = value.datdoc.substring(0, 4);
                            docref += value.regiva;
                            docref += value.seziva * 1000000000 + parseInt(value.protoc);
							var ex= value.expiry;
							var am= value.amount;
                            updateSchedule(docref, ex, am);
                            updateCloseForm();
                        });
                        j++;
                    });
                }, "json"
                );
    }

    function getOtherMov(term_val, excl_val, link) {
        $.get("expiry.php",
                {clfoco: term_val, id_tesdoc_ref: excl_val},
                function (data) {
                    var j = 0;
                    $.each(data, function (i, value) {
						var desnumdoc=value.numdoc?' n.'+value.numdoc+'/'+value.seziva+' del '+value.datdoc:'';
                        var ri = parseInt(value.regiva) || 0;
                        var pr = parseInt(value.protoc) || 0;
                        if (j == 0) {
                            $("#db-contain" + nrow + " tbody").append("<tr>" +
                                    "<td class='ui-widget-content ui-state-active' colspan=7" + ' class="ui-widget ui-widget-content " > Altri movimenti ' + ((value.ragso1!==null)?(' di: '+value.ragso1 + ' ' + value.ragso2):'') + '</td></tr>');
                        }
                        $("#db-contain" + nrow + " tbody").append("<tr>" +
                                '<td class="ui-widget-right ui-widget-content "><button id="linking_' + j + '" class="btn btn-xs btn-default"><i class="glyphicon glyphicon-link"></i></button>'+value.id_tesdoc_ref+'</td>'+value.id_tesdoc_ref +
                                "<td" + ' class="ui-widget ui-widget-content " > ' + value.descri + desnumdoc + "</td>" +
                                "<td" + ' class="ui-widget ui-widget-content " >' + value.expiry + "</td>" +
                                "<td" + ' class="ui-widget-right ui-widget-content " >' + value.amount + "</td>" +
                                '<td class="ui-widget-right ui-widget-content " >' + value.darave + '</td>' +
                                '<td class="ui-widget-right ui-widget-content "><a target="NEW" href="admin_movcon.php?id_tes=' + value.id_tes + '&Update" class="btn btn-xs btn-default"><i class="glyphicon glyphicon-new-window"></i></a></td>' +
                                "</tr>");

                        $("#linking_" + j).click(function () {
							if (ri >= 1) { // ha un riferimento ad un documento IVA
								var docref = value.datdoc.substring(0, 4);
								docref += value.regiva;
								docref += value.seziva * 1000000000 + parseInt(value.protoc);
							} else { // ha un riferimento ad un rigo contabile
								var docref= value.id_tesdoc_ref;
							}
							var ex= value.expiry;
							var am= value.amount;
                            updateSchedule(docref, ex, am);
                            updateCloseForm();
                            updateOpenForm();
                        });

                        j++;

                    });
                }, "json"
                );
    }

    function updateSchedule(docref,ex,am) {
		var expiry = ex.substring(8,10);
		expiry += ex.substring(4,8);
		expiry += ex.substring(0,4);
        $("#pm_post_container_" + nrow + " div").each(function (i, v) {
            var idv = $(v).attr('id').split('_');
            var id_sub = idv[2];
            var tesref = $('input[id=post_' + nrow + '_' + id_sub + '_id_tesdoc_ref]:first', v).focus().attr('value');
            if (tesref == '') {// replace value only if row is empty
                $('input[id=post_' + nrow + '_' + id_sub + '_id_tesdoc_ref]:first', v).val(docref);
                $('input[id=post_' + nrow + '_' + id_sub + '_expiry]:first', v).val(expiry);
                $('input[id=post_' + nrow + '_' + id_sub + '_amount]:first', v).val(am);
			}
        });

        updateTips("");

        return true;
    }

    function updateTips(t) {
        tips.text(t).addClass("ui-state-highlight");
        setTimeout(function () {
            tips.removeClass("ui-state-highlight", 1500);
        }, 500);
    }

    function checkTot(n) {
        var accu = 0;
        $("#pm_post_container_" + nrow + " div").each(function (i, v) {
            var idv = $(v).attr('id').split('_');
            var id_sub = idv[2];
            accu += parseFloat($('input[id=post_' + nrow + '_' + id_sub + '_amount]:first', v).focus().attr('value'));
        });
        accu = accu.toFixed(2);
        if (accu == tot_amount) {
            return true;
        } else {
            $('#impoRC' + nrow).val(accu);
            $('#impoRC' + nrow).trigger('change');
            return true;
        }
    }

    function checkField(open) {
        var bval = true;
        $("#pm_post_container_" + nrow + " div").each(function (i, v) {
            var idv = $(v).attr('id').split('_');
            var id_sub = idv[2];
            var tesref = $('input[id=post_' + nrow + '_' + id_sub + '_id_tesdoc_ref]:first', v).focus().attr('value');
            var am = parseFloat($('input[id=post_' + nrow + '_' + id_sub + '_amount]:first', v).focus().attr('value')).toFixed(2);
            var d = $('input[id=post_' + nrow + '_' + id_sub + '_expiry]:first', v).focus().attr('value').split("-");
            var day, month, year;
            day = d[0] - 0;
            month = d[1] - 0;
            year = d[2] - 0;
            if (am < 0.00) {
                updateTips("Errore ! Un rigo importo non è stato valorizzato");
                bval = false;
            } else if (!(month > 0 && month < 13 && year > 2000 && year < 3000 && day > 0 && day <= (new Date(year, month, 0)).getDate()) && open) { // la data è indispensabile solo in caso di apertura
                updateTips("Errore ! Un rigo scadenza non è stato valorizzato (gg-mm-aaaa)");
                bval = false;
            } else if (tesref == "") { // la partita di riferimento non è stata valorizzata
                updateTips("Errore ! Un rigo è senza partita di riferimento");
                bval = false;
            }
        });
        return bval;
    }

    function checkDate(o_date, n) {
        var d, day, month, year;
        var chk_date = o_date.val().toString().length;
        d = o_date.val().toString().replace(/\//g, "-").split("-");
        day = d[0] - 0;
        month = d[1] - 0;
        year = d[2] - 0;
        if ((month > 0 && month < 13 && year > 2000 && year < 3000 && day > 0 && day <= (new Date(year, month, 0)).getDate()) || chk_date == 0) {
            o_date.removeClass();
            updateTips('');
            return true;
        } else {
            o_date.addClass("ui-state-error");
            updateTips("Errore ! " + n);
            return false;
        }
    }

    function checkAmount(o, n) {
        var amou;
        amou = parseFloat(o.val().toString().replace(/\,/g, ".")).toFixed(2);
        if (amou < 0.00) {
            o.addClass("ui-state-error");
            updateTips("Errore ! " + n);
            return false;
        } else {
            o.removeClass();
            o.val(amou);
            updateTips('');
            return true;
        }
    }

    $("#dialog_open" + nrow).dialog({
        autoOpen: false,
        show: "scale",
        width: dWidth,
        modal: true,
        open: function () {
            updateOpenFormOnStart();
            updateOpenForm();
            getSamePaymov(tesdoc_ref, id_rig, false);
            getOtherMov(clfoco, tesdoc_ref, true);
        },
        buttons: [{
          text: "Conferma e chiudi",
          "class": "btn btn-warning",
          click: function() {
            $( this ).dialog( "close" );
          }
        }],
        beforeClose: function (event, ui) {
            if (!checkField(true) || !checkTot('Il totale delle scadenze non coincide con il totale rigo')) {
                return false;
            }
        },
        close: function () {
            allFields.val("").removeClass("ui-state-error");
            $("#db-contain" + nrow + " tbody").remove();
            $("#db-contain" + nrow).append("<tbody></tbody>");
        }
    });

    $("#dialog_open" + nrow).dialog("open");

    $("#dialog_close" + nrow).dialog({
        autoOpen: false,
        show: "scale",
        width: dWidth,
        modal: true,
        open: function () {
            updateCloseFormOnStart();
            updateCloseForm();
            getSamePaymov(tesdoc_ref, id_rig, true);
            getOtherMov(clfoco, tesdoc_ref, true);
        },
        buttons: {
            "Conferma": function () {
                $(this).dialog("close");
            }
        },
        beforeClose: function (event, ui) {
            if (!checkField(false) || !checkTot('Il totale delle scadenze non coincide con il totale rigo')) {
                return false;
            }
        },
        close: function () {
            allFields.val("").removeClass("ui-state-error");
            $("#db-contain" + nrow + " tbody").remove();
            $("#db-contain" + nrow).append("<tbody></tbody>");
        }

    });

    $("#dialog_close" + nrow).dialog("open");

    //click sul bottone +
    $("#addOpenExpiry" + nrow).click(function () {
        var id_btn = new Date().valueOf().toString();

        $("#pm_post_container_" + nrow).append('<div id="pm_post_' + id_btn + '">' +
                '<input type="hidden" id="post_' + nrow + '_' + id_btn + '_id" name="paymov[' + nrow + '][' + id_btn + '][id]" value="new" />' +
                '<input type="hidden" id="post_' + nrow + '_' + id_btn + '_id_tesdoc_ref" name="paymov[' + nrow + '][' + id_btn + '][id_tesdoc_ref]" value="' + tesdoc_ref + '" />' +
                '<input type="hidden" id="post_' + nrow + '_' + id_btn + '_expiry" name="paymov[' + nrow + '][' + id_btn + '][expiry]" value="" />' +
                '<input type="hidden" id="post_' + nrow + '_' + id_btn + '_amount" name="paymov[' + nrow + '][' + id_btn + '][amount]" value="0.00" />' +
                '</div>');

        updateOpenFormRiga(id_btn, tesdoc_ref, "", "0.00");
    }
    );

    //click sul bottone +
    $("#addCloseExpiry" + nrow).click(function () {
        var id_btn = new Date().valueOf().toString();
        //oggi = $.datepicker.formatDate( "dd-mm-yy", new Date());

        $("#pm_post_container_" + nrow).append('<div id="pm_post_' + id_btn + '">' +
                '<input type="hidden" id="post_' + nrow + '_' + id_btn + '_id" name="paymov[' + nrow + '][' + id_btn + '][id]" value="new" />' +
                '<input type="hidden" id="post_' + nrow + '_' + id_btn + '_id_tesdoc_ref" name="paymov[' + nrow + '][' + id_btn + '][id_tesdoc_ref]" value="' + tesdoc_ref + '" />' +
                '<input type="hidden" id="post_' + nrow + '_' + id_btn + '_expiry" name="paymov[' + nrow + '][' + id_btn + '][expiry]" value="" />' +
                '<input type="hidden" id="post_' + nrow + '_' + id_btn + '_amount" name="paymov[' + nrow + '][' + id_btn + '][amount]" value="0.00" />' +
                '</div>');

        updateCloseFormRiga(id_btn, tesdoc_ref, "", "0.00");
    }
    );
}
