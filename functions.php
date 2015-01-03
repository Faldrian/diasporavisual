<?php


/**
 * Holt eine Webseite und cached sie
 * -> url = url, die geladen werden soll
 * -> cache = array, in den gecachet wird
 */
function geturl($url, &$cache) {
	if(isset($cache[$url])) {
		return $cache[$url];
	}
	
	// Hole den Inhalt der Webseite
	//$content = file_get_contents($url);
	
	$ch = curl_init();
 
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
	curl_setopt($ch, CURLOPT_URL, $url);
	 
	$content = curl_exec($ch);
	curl_close($ch);
	
	
	// Speicher das in den Cache
	$cache[$url] = $content;
	return $content;
}

/**
 * Untersucht, ob ein Diaspora-JSON-Objekt einen Reshare darstellt.
 */
function isReshare($json) {
	// Wenn das Root-Element nicht leer ist, ist es ein Reshare.
	return ($json->post_type == "Reshare");
}

function buildPostQuery($host, $guid) {
	return "https://$host/posts/$guid.json";
}

function buildInteractionsQuery($host, $guid) {
	return "https://$host/posts/$guid/interactions.json";
}


function dispatcher() {
	global $_SESSION;
	
	// Gibt es noch Jobs?
	if(!empty($_SESSION['todo'])) {
		$job = array_shift($_SESSION['todo']);
		
		if($job['job'] == MODE_TOROOT) {
			crawlToRoot($job['data'], $_SESSION['cache']);
		} else {
			inspectTree($job['data'],$_SESSION['cache']);
		}
	} else {
		// Fertig!
		// TODO implement
		echo json_encode(
				array(
						'command' => 'display',
						'msg' => "Fertig."
				)
		);
	}
}


/**
 * 
 */
function crawlToRoot($data, &$cache) {
	$page = json_decode(geturl($data['url'], $cache));
	
	// Basisinformationen zum Beitrag
	$host_parts = parse_url($data['url']);
	$host = $host_parts['host'];
	$author = $page->author->diaspora_id;
	$guid = $page->guid;
	
	if(isReshare($page)) {
		// Weitere Infos, wenn es ein Reshare ist.
		$originalGuid   = $page->root->guid;
		$originalAuthor = $page->root->author->diaspora_id;
		
		// Dieser Beitrag ist also ein Reshare.
		// Dann Lege mal die Wurzel zum weiteruntersuchen auf den Stack.
		pushTodo(MODE_TREE, array(
			'url' => buildInteractionsQuery($host, $originalGuid),
			'guid' => $originalGuid,
			'parent' => '0',
			'avatar' => $page->root->author->avatar->small
		));
		
		// Anweisungen fürs Ajax
		echo json_encode(
				array(
						'command' => 'reload',
						'msg' => "Reshare: $author teilt Beitrag von $originalAuthor"
				)
		);

	} else {
		// Der Beitrag ist gar kein Reshare, wir können also gleich mit dem nächsten Schritt weitermachen
		pushTodo(MODE_TREE, array(
			'url' => buildInteractionsQuery($host, $guid),
			'guid' => $guid,
			'parent' => '0',
			'avatar' => $page->author->avatar->small
		));
		
		dispatcher();
	}
}

/**
 * Diese Funktion sucht Informationen zum aktuellen Beitrag heraus
 * und setzt alle Reshares dieses Beitrags auf die Jobliste.
 */
function inspectTree($data, &$cache) {
	$page = json_decode(geturl($data['url'], $cache));
	
	// Basisinformationen zum Beitrag
	$host_parts = parse_url($data['url']);
	$host = $host_parts['host'];
	
	// Interaktionsinformationen:
	$sumLikes = count($page->likes);
	$sumComments = count($page->comments);
	$sumReshares = count($page->reshares);
	
	// Alle Reshares in die Queue packen
	$info = "";
	foreach($page->reshares as $reshare) {
		pushTodo(MODE_TREE, array(
			'url' => buildInteractionsQuery($host, $reshare->guid),
			'guid' => $reshare->guid,
			'parent' => $data['guid'],
			'avatar' => $reshare->author->avatar->small
		));
		
		$info .= "Reshare | ThisGuid: ".$data['guid'].' --> ReshareGuid: '.$reshare->guid.'<br />';
	}
	
	// Schauen, ob ein Avatar angegeben ist (=volle url), sonst default benutzen:
	if(substr($data['avatar'], 0 ,4) != "http") {
		$data['avatar'] = "img/noavatar.png";
	}
	
	// Baue den Link zum eigentlichen Beitrag zusammen
	$linkToPost = "https://$host/posts/".$data['guid'];
	
	// Anweisungen fürs Ajax
	echo json_encode(
			array(
					'command' => 'reload',
					'insertNode' => array(
							'parent' => $data['parent'],
							'this' => $data['guid'],
							'data' => '<a href="'.$linkToPost.'" target="_blank"><img src="'.$data['avatar'].'"><span><img src="img/heart.png">'.$sumLikes.'<br /><img src="img/comment.png">'.$sumComments.'</span></a>'
							),
					'msg' => "<p>Dies ist Beitrag ".$data['guid'].".<br />Likes: <font color=red>$sumLikes</font>, Comments: <font color=red>$sumComments</font>, Reshares: <font color=red>$sumReshares</font><br />".$info."</p>"
			)
	);
}


/**
 * Schiebt weitere Jobs in die Queue, die abgearbeitet wird.
 */
function pushTodo($job, $data) {
	global $_SESSION;
	array_push($_SESSION['todo'],array('job' => $job, 'data' => $data));
}

