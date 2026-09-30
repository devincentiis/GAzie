$(function() {
	$('.paper').click(function(e){    
		$("#nome_file").val($(e.target).text().replace('.xml.p7m','.xml')); 
		$("#status").val(""); 
	})
	$('.paper1').click(function(e){    
		$("#status").val($(e.target).text());
		$("#nome_file").val(""); 
	})
});