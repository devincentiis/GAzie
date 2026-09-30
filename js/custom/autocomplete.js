$(function() {
	// utilizzata dalla funzione selectDocPartner, quando incontra un solo risultato nella select lo conferma in automatico
	$( "#onlyone_submit" ).trigger( "click" );

	$( "#search_clfoco" ).autocomplete({
		source: "../../modules/root/search.php",
		minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)

      // optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#search_clfoco").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});
	$( "#search_id_customer" ).autocomplete({
		source: "../../modules/root/search.php",
		minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)

      // optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#search_id_customer").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});
	$( "#search_cosear" ).autocomplete({
		source: "../../modules/root/search.php?opt=product",
		minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)

      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#search_cosear").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});
	$( "#search_order" ).autocomplete({
		source: "../../modules/root/search.php?opt=order",
		minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)

      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#search_order").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});
	$( "#search_production" ).autocomplete({
		source: "../../modules/root/search.php?opt=production",
		minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#search_production").val(ui.item.description);
			$("#in_id_orderman").val(ui.item.id);
			$(this).closest("form").submit();
		}
	});
	$( "#search_contract" ).autocomplete({
		source: "../../modules/root/search.php?opt=contract&clfoco="+$("#clfoco").val(),
		minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#search_contract").val(ui.item.conclusion_date);
			$("#id_contract").val(ui.item.id_contract);
			$(this).closest("form").submit();
		}
	});
	$( "#search_location" ).autocomplete({
		source: "../../modules/root/search.php?opt=location",
		minLength: 2,
		html: true, // optional (jquery.ui.autocomplete.html.js required)
		focus: function( event, ui ) {
			$( "#search_location" ).val( ui.item.value );
			$( "#search_location-capspe" ).val( ui.item.id );
			$( "#search_location-prospe" ).val( ui.item.prospe );
			$( "#country").val( ui.item.country );
			return false;
		},
		select: function( event, ui ) {
			$( "#search_location" ).val( ui.item.value );
			$( "#search_location-capspe" ).val( ui.item.id ); /* era capspe che è uguale a id, inutile duplicare un elemento dell'array*/
			$( "#search_location-prospe" ).val( ui.item.prospe );
			$( "#country").val( ui.item.country );  //grazie ad Emanuele Ferrarini
			return false;
		}
	});
	$('#search_location').blur(function() {
		if( !$(this).val() ) {
			$( "#search_location-capspe" ).val("");
			$( "#search_location-prospe" ).val("");
			$( "#country").val("IT");
		}
	});

	$( "#search_luonas" ).autocomplete({
		source: "../../modules/root/search.php?opt=location",
		minLength: 2,
		html: true, // optional (jquery.ui.autocomplete.html.js required)
		focus: function( event, ui ) {
			$( "#search_luonas" ).val( ui.item.value );
			$( "#search_pronas" ).val( ui.item.prospe );
			$( "#cuonas").val( ui.item.country );
			return false;
		},
		select: function( event, ui ) {
			$( "#search_luonas" ).val( ui.item.value );
			$( "#search_pronas" ).val( ui.item.prospe );
			$( "#cuonas").val( ui.item.country );  //grazie ad Emanuele Ferrarini
			return false;
		}
	});
	$('#search_luonas').blur(function() {
		if( !$(this).val() ) {
			$( "#search_pronas" ).val("");
			$( "#cuonas").val("IT");
		}
	});
	$( "#search_Codice_CCNL" ).autocomplete({
		source: "../../modules/humres/get_contract.php",
		minLength: 2,
		html: true, // optional (jquery.ui.autocomplete.html.js required)
		focus: function( event, ui ) {
			$( "#search_Codice_CCNL" ).val( ui.item.value );
		},
		select: function( event, ui ) {
			$( "#search_Codice_CCNL" ).val( ui.item.value );
			return false;
		}
	});
	$( "#search_municipalities" ).autocomplete({
		source: "../../modules/root/search.php?opt=municipalities",
		minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)

      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#search_municipalities").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});
	$( "#search_employee" ).autocomplete({
		source: "../../modules/root/search.php?opt=employee",
		minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#search_employee").val(ui.item.value);
			$("#id_employee").val(ui.item.id);
			$("#hidden_req").val(ui.item.id);
			$(this).closest("form").submit();
		}
	});
	$( "#suggest_new_codart" ).autocomplete({
		source: "../../modules/root/search.php?opt=suggest_new_codart",
		minLength: 1,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#suggest_new_codart").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});
	$( "#suggest_descri_artico" ).autocomplete({
		source: "../../modules/root/search.php?opt=suggest_descri_artico",
		minLength: 3,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#suggest_descri_artico").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});
	$( "#search_position" ).autocomplete({
		source: "../../modules/root/search.php?opt=position",
		minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#search_position").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});


});

