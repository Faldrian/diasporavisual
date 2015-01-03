
/**
 * globale Arbeitsvariablen:
 */
var rootNode = new Object();
var guidToNodeMap = {};

/**
 * Damit wird der Prozess angestoßen.
 */
function startCrawl() {
	var url = $('#url').val() + '.json';
	$.getJSON('ajax.php', {'command': 'start', 'url': url}, responseHandler);
	
	$('#start').attr("disabled", "disabled");
}

function responseHandler(data) {
	// Erstmal schauen, was für eine Antwort kam
	if(data['command'] == "reload") { // Einen erneuten Request abschicken
		setTimeout('ajaxReload()', 1000);
	}
	if(data['command'] == "display") { // Einen erneuten Request abschicken
		// Mach nichts. Hier würde später das fertige Diagramm übermittelt werden.
		// -- Oder jedenfalls der Job abgeschlossen.
		
		// Button wieder freischalten:
		$('#start').removeAttr("disabled");
	}
	
	if(typeof data['insertNode'] != 'undefined') {
		// Es sollen Nodes eingefügt werden
		addNode(data['insertNode']['parent'], data['insertNode']['this'], data['insertNode']['data']);
	}
	
	// Wenn Statusnachrichten vorhanden sind, immer anzeigen
	if(data["msg"] != "") {
		$('#status').append('<div>' + data["msg"] + '</div>');
	}

}

function ajaxReload() {
	$.getJSON('ajax.php', {'command': 'reload'}, responseHandler);
}

function addNode(parentNodeGuid, thisGuid, data) {
	if(parentNodeGuid == "0") {
		// Fülle den Root-Node mit daten
		rootNode.Content = data;
		rootNode.Nodes = new Array();
		
		// Referenz setzen
		guidToNodeMap[thisGuid] = rootNode;
	} else {
		var parentNode = guidToNodeMap[parentNodeGuid];
		
		var newNode = {Content: data, Nodes: new Array()};
		parentNode.Nodes.push(newNode);
		
		guidToNodeMap[thisGuid] = newNode;
	}
	
	// Redraw Tree
	var container = document.getElementById('woods');
	DrawTree({
		Container: container,
		RootNode: rootNode,
		Layout: 'Vertical'
	});
}
