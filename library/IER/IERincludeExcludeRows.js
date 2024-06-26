// Credit: m_zolfo
var IERisModified = 0;
var IERresultRIERS = "";


$(window).on('load', function () {
	//read all rows from file
	//read like array
	//$("head").append('<link rel="stylesheet" href="../IERincludeExcludeRows.css">');
	$("head").append('<link rel="stylesheet" href="../../library/IER/IERincludeExcludeRows.css">');

	clearIncludeExcludeRows();
	writeExcludeRows();

	$("#IERsaveIncludeExcludeRows").css("display","none");
	$("#IERresetIncludeExcludeRows").css("display","none");

	IERisModified = 1;
});

function readIncludeExcludeRowsArray(){
	return readIncludeExcludeRowsString().split(";");
}

function setExcludeRows(){
	listRowsAll = readIncludeExcludeRowsArray();

	IERisModified = 0;
	$("#IERsaveIncludeExcludeRows").removeClass("excluded");
	$("#IERresetIncludeExcludeRows").removeClass("active");

	if(listRowsAll.length > 0)
		$("#IERresetIncludeExcludeRows").addClass("active");

	for (m = 0; m < listRowsAll.length; ++m) {
		//u =listRowsAll[m].replace(/(?:\r\n\|\r|\n)/g,"");
    u =listRowsAll[m].replace(/[\r\n_\s]/g, '');
    if(u.length > 1 && $(u) != "undefined")
      includeExcludeRow(u);
	}

	IERisModified = 1;
}

function getMyUrl(){
	// ritorna {modulo}/{nome_script} dello script in esecuzione - es.: magazz/admin_artico
	url=window.location.href;
	urlArr= url.split("/");
	url = urlArr[urlArr.length - 2]+ "/" + urlArr[urlArr.length - 1];
	urlArr = url.split(".");
	scriptname = urlArr[0];
	return  scriptname;
}

function readIncludeExcludeRowsString(){
	result = "";

	$.ajax({
		type: 'POST',
		url: "../../modules/root/IERincludeExcludeRows.php",
        async: false,
        data: {
		 fn: "read",
		 filename: getMyUrl() + ".IER",
		 value: "",
		},
		success: function(msg){
			IERresultRIERS = msg;
		}
	});
	result = IERresultRIERS;
	IERresultRIERS = "";
	return ""+result;
}

function clearIncludeExcludeRows(){
	// change title attribute for div
	$("#IERenableIncludeExcludeRows").attr('title','Personalizza videata');
	$("#IERsaveIncludeExcludeRows").attr('title','Nessuna modifica fatta');

	listRowsAll = document.getElementsByClassName("IERincludeExcludeRow");

	$("#IERincludeExcludeRowsInput").val("");

	for (i = 0; i < listRowsAll.length; ++i) {
		listRowsAll[i].style.display = "block";

		child = document.getElementById('iEbtn'+listRowsAll[i].getAttribute('id'));

		if(child)
			listRowsAll[i].removeChild(child);
	}
}

function writeIncludeExcludeRows(){
	// for all elements with class "IERincludeExcludeRow" and if the id is in list "listRowsExclude"
	listRowsAll = document.getElementsByClassName("IERincludeExcludeRow");

	for (i = 0; i < listRowsAll.length; ++i) {
		includeExcludeBTN = '<div class="IERincludeExcludeBTN" onclick="includeExcludeRow(\''+listRowsAll[i].getAttribute('id')+'\')" id="iEbtn'+listRowsAll[i].getAttribute('id')+'" ></div>';
		listRowsAll[i].innerHTML += includeExcludeBTN;
	}
}

function writeExcludeRows(){
	//read the array from db
	listIERA = readIncludeExcludeRowsArray();

	if(listIERA.length > 0 && listIERA[0].replace(" ", "") != "")
	{
		$("#IERenableIncludeExcludeRows").addClass("IERmodified");
		$("#IERresetIncludeExcludeRows").addClass("IERenabled");
		$("#IERresetIncludeExcludeRows").on("click",function(){resetIncludeExcludeRows()});
	}
	else
	{
		$("#IERenableIncludeExcludeRows").removeClass("IERmodified");
		$("#IERresetIncludeExcludeRows").removeClass("IERenabled");
		$("#IERresetIncludeExcludeRows").off("click");
	}

	for (i = 0; i < listIERA.length; ++i){
		//u = "#"+listIERA[i].replace(/(?:\r\n|\r|\n)/g,"");
    u = "#"+listIERA[i];
    u =u.replace(/[\r\n_\s]/g, '');
    if(u.length > 1 && $(u) != "undefined")
      $(u).css("display","none");
	}
}

IERenable = false;

function enableIncludeExcludeRows(){
	if(IERenable)
	{
		/*alert(isModified);
		if(isModified)
			alert('1. modificato');*/

		if(IERisModified == 2)
		{
			if(confirm("Impostazioni modificate, vuoi salvarle ?")) {
						saveIncludeExcludeRows();
						return;
			}
		}

		writeExcludeRows();

		$("#IERsaveIncludeExcludeRows").css("display","none");
		$("#IERresetIncludeExcludeRows").css("display","none");

		clearIncludeExcludeRows();
		writeExcludeRows();

		$("#IERsaveIncludeExcludeRows").css("display","none");
		$("#IERresetIncludeExcludeRows").css("display","none");
	}
	else
	{

		/*alert(isModified);
		if(isModified)
			alert('2. modificato');*/

		clearIncludeExcludeRows();
		writeIncludeExcludeRows();

		// change title attribute for div
		$("#IERenableIncludeExcludeRows").attr('title','Esci da personalizzazione');

		$("#IERsaveIncludeExcludeRows").css("display","block");
		$("#IERresetIncludeExcludeRows").css("display","block");

		setExcludeRows();
	}
	IERenable = !IERenable;
}

function saveIncludeExcludeRows(){
  if(IERisModified == 1 || IERisModified == 0)
		return;

	//if($("#IERincludeExcludeRowsInput").val().length <= 0)
	//	return;

	//alert($("#IERincludeExcludeRowsInput").val());
  //alert(getMyUrl() + ".IER");

	$.post("../../modules/root/IERincludeExcludeRows.php",
	{
    fn: "save",
	filename: getMyUrl() + ".IER",
	value: $("#IERincludeExcludeRowsInput").val(),
	},
	function(data, status){
			alert("Salvataggio impostazioni eseguito con successo");
	});

	IERisModified = 1;
	enableIncludeExcludeRows();
}

function includeExcludeRow(id) {
	if(IERisModified == 1)
	{
		$("#IERsaveIncludeExcludeRows").addClass("excluded");
		IERisModified = 2

		// change title attribute for div
		$("#IERsaveIncludeExcludeRows").attr('title','Salva nuove impostazioni');
	}
	listRowsEx = $("#IERincludeExcludeRowsInput").val().split(";");
	isEx = false;
	stringValue = "";
	i = 0;

	for (i = 0; i < listRowsEx.length; ++i) {
		if(listRowsEx[i]!=id )
		{
			if(listRowsEx[i]!=null && listRowsEx[i].length > 0)
			{
				if(stringValue.length > 0)
					stringValue += ";";
					stringValue += listRowsEx[i];
			}
		}
		else
		{
			isEx = true;
		}
	}

	if(stringValue.length > 0)
		stringValue += ";";

	if(isEx == false)
		stringValue += id;

	stringValue.replace(";;",";");

	if(stringValue.substring(stringValue.length -1,stringValue.length ) == ";" )
		stringValue = stringValue.substring(0,stringValue.length -1);

	$("#IERincludeExcludeRowsInput").val(stringValue);

	if(isEx)
		$("#iEbtn"+id).removeClass("excluded");
	else
		$("#iEbtn"+id).addClass("excluded");

		if($(".IERincludeExcludeBTN.excluded").length > 0) {
			$("#IERresetIncludeExcludeRows").addClass("IERenabled");
			$("#IERresetIncludeExcludeRows").on("click",function(){resetIncludeExcludeRows()});
		} else {
			$("#IERresetIncludeExcludeRows").removeClass("IERenabled");
			$("#IERresetIncludeExcludeRows").off("click");
		}
}

function resetIncludeExcludeRows() {
	clearIncludeExcludeRows();
	writeIncludeExcludeRows();
	includeExcludeRow("");
}
