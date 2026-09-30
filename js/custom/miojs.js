function toast(msg) {
    $().toastmessage('showToast', {
        text: msg,
        sticky: false,
        position: 'center',
        type: 'notice',
        closeText: '',
        close: function () {
            console.log("toast is closed ...");
        }
    });
}
function cambiaDestinazione(valoreSelezionato) {
//    if(valoreSelezionato.value!=""){
    document.getElementsByName("destin")[0].value = valoreSelezionato.value;
//    }
}
function attivaDialog(dest) {
//     edit della tabella di lookup
    if (window.showModalDialog) {
        window.showModalDialog(dest, "temp",
                "dialogWidth:900px;dialogHeight:600px");
    } else {
        window.open(dest, 'lookup', 'height=600,width=900,toolbar=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes ,modal=yes');
//        alert("Clicca qui al termine delle modifiche per aggiornare l'elenco");
    }
}
