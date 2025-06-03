<div class="panel panel-info">
<h3>
  <div class="text-center">
    <img src="opencarttre.png" height="80" />
  </div>
<ul>
  <li>Questo è un modulo specifico per sincronizzare il gestionale con l'eCommerce <strong><a href="https://github.com/opencart/opencart" target="new">OpenCart</a></strong> nella versione <strong><a href="https://github.com/opencart/opencart/releases" target="new">3.0.X</a></strong> dopo aver installato il modulo di estensione (OCtreGazie.ocmod.zip) che trovate in questa directory.</li>
  <li>Per accedere alle API di Opencart 3 è necessario possedere un nome utente, una chiave ed avere il proprio indirizzo IP abilitato ad accedere alla risorsa.</li>
  <li>Sulla versione citata l'endpoint per ottenere il token è: "http(s)://myshopdomain/index.php?route=api/login". Sul lato amministrativo di Opencart le credenziali vanno indicate in System -> Users -> API, in accordo con quanto indicato alla voce di menù <strong>Credenziali API</strong> di questo modulo.</li>
  <li> Gli endpoint rappresentano il percorso dentro la directory catalog/controller, pertanto ad esempio per raggiungere la funzione getCustomers dentro extension/module/ocgazie.php diventa: "http(s)://myshopdomain/index.php?route=extension/module/ocgazie/getCustomers"</li>
</ul>
</h3>

</div>
<div class="panel panel-success">
<h4>
  <p class="text-danger"> Se vuoi creare un sistema informativo pre il tuo ecommerce o richiedere una consulenza contatta l'autore:</p>
  <p class="text-warning text-center">Antonio De Vincentiis Montesilvano (PE)</p>
  <p class="text-center"><a href="https://www.devincentiis.it"> https://www.devincentiis.it </a></p>
  <p class="text-center">Telefono +39 <a href="tel:+393383121161">3383121161</a></p>
</h4>
</div>

<ul class="nav nav-tabs">
   <li class="active"><a data-toggle="tab" href="#ScaricaOrdini">Scarica Ordini</a></li>
   <li ><a data-toggle="tab" href="#Listaclientiiscritti">Lista clienti iscritti</a></li>
   <li ><a data-toggle="tab" href="#Aggiornacatalogoweb">Aggiorna catalogo web</a></li>
   <li ><a data-toggle="tab" href="#CredenzialiAPI">Credenziali API</a></li>
</ul>

<div class="tab-content contenuto-help">
	<div id="ScaricaOrdini" class="tab-pane fade in active">
		<p class="help-text">Scarica gli ordini provenienti dallo store online che non risultano ancora importati sul gestionale.</p>
	</div>

	<div id="Listaclientiiscritti" class="tab-pane fade in">
		<p class="help-text">Report dei i clienti risultanti iscritti sul portale dello store online.</p>
	</div>
	<div id="Aggiornacatalogoweb" class="tab-pane fade in">
		<p class="help-text">Funzione che riallinea il contenuto del magazzino del gestionale con il catalogo presente online. Anche se il gestionale provvede immediatamente ad ogni inserimento/modifica del magazzino a sincronizzarlo in background è utile per una nuova instalazione o allorquando, per motivi tecnici, è mancato il collegamento tra le due infrastrutture.</p>
	</div>
	<div id="CredenzialiAPI" class="tab-pane fade in">
		<p class="help-text">Configurazione e credenziali per collegare le due infrastrutture attraverso API.</p>
	</div>
</div>
