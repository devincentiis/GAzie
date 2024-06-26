// copyright (c) 2024 Marco Rimoldi - Licenza GPLv2.
$(document).ready(function(){
    // cerchiamo di modificare solo le select generate da gaz_flt_dsp_select
    // e le select dei sezionali
    var selects = $('select.input-sm, select[name="sezione"]');
    // gaz_flt_dsp_select usa "All", qui usiamo invece valori vuoti
    // (in questo modo i campi non usati possono essere esclusi)
    $("option", selects).filter(function(){ return this.value == "All"; }).val("");

    // la stessa funzione imposta onchange="this.form.submit()" sulle select:
    // l'azione non lancia un evento "submit" e non può essere intercettata.
    // per non andare a modificare la funzione rimpiazziamo l'attributo onchange:
    selects.attr('onchange', null).change(function() { $(this.form).submit(); });

    // adesso è possibile applicare, ad esempio, clean_empty_form_fields.js
});
