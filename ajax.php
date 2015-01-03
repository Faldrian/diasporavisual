<?php
/* Ajax-Interface:
 * 
 * command = "start"
 * ++ Startet einen neuen Lauf.
 * 		url = url des Beitrags, mit dem gestartet werden soll
 * 
 * command = "reload"
 * ++ Setzt den aktuellen Lauf fort, wenn noch etwas zu tun ist.
 * 
 * 
 * 
 * 
 * Workflow:
 * 1. Verfolge den Beitrag zurück zur Wurzel (mode 0)
 * 2. Crawl von der Wurzel aus alles durch (mode 1)
 */

// Konstanten
const MODE_TOROOT = "toroot"; // Gehe zur Wurzel des Baums
const MODE_TREE   = "tree";   // Untersuche den Knoten auf Blatt-Knoten

require('functions.php');

if(!isset($_GET['command'])) {
	die('Das muss man schon vernünftig aufrufen...');
}

// Alle Zwischenergebnisse werden in der Session gespeichert.
session_start();

if($_GET['command'] == 'start') {
	// Datenstrukturen anlegen / aufräumen
	$_SESSION['mode'] = 0;
	$_SESSION['cache'] = array();
	$_SESSION['todo'] = array(); // Da kommen urls rein, die noch untersucht werden müssen
	
	pushTodo(MODE_TOROOT, array('url' => $_GET['url']));
	
	dispatcher();
} elseif ($_GET['command'] == 'reload') {
	dispatcher();
}


