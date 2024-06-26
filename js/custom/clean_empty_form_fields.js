// copyright (c) 2018 Bill Erickson
// URL: http://www.billerickson.net/code/hide-empty-fields-get-form/
$(document).ready(function(){
    // Modifica solo le form con classe "clean_get", ad esempio quelle
    // dedicate all'ordinamento e alla ricerca nei moduli report.
    $("form.clean_get").submit(function() {
        $(this).find(":input").filter(function(){ return !this.value; }).attr("disabled", "disabled");
        return true; // ensure form still submits
    });

    // Un-disable form fields when page loads, in case they click back after submission
    $("form.clean_get").find( ":input" ).prop( "disabled", false );
});
//@-leo
