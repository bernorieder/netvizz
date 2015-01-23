<script type="text/javascript" language="javascript">

	function sendPagenetwork(_id) {
		_depth = document.getElementById("pagenetwork_depth").value;
		document.location.href = "index.php?module=pagelike&action=pagenetwork&pageid="+_id+"&depth="+_depth;
	}

</script>


<?php

if(!isset($_GET["action"])) {

	echo '<h2>page like network:</h2>

	<p>This module starts with a selected page (the "seed") and retrieves all the pages that page likes. It will
	continue until the specified crawl depth is reached (currently limited to 2). The output is a network file (gdf format) containing a (directed) network of pages.
	Because node ids are unique, you can combine several networks in gephi.</p>

	<hr class="fbcontentdivider" />

	<p>You have "liked" the following pages (will only show first 500):<br /><br />

	<input id="pagenetwork_depth" type="text" value="1" maxlength="1" size="3" /> depth (max 2)</p><p>';


	try {
		$pages = $facebook->api('/me/likes?limit=500');
	} catch (Exception $e) {
		echo 'An error has occurred. ('.$e.')';
		exit;
	}

	foreach($pages["data"] as $page) {
		echo $page["name"] . ' (<a href="javascript:sendPagenetwork(\''.$page["id"]. '\')">start here</a>)<br />';
	};

	echo '</p>

	<hr class="fbcontentdivider" />

	<p><strong>file fields (network - gdf format - nodes are pages):</strong><br />
	<i>category:</i> page category according to Facebook\'s ontology<br />
	<i>like_count:</i> number of likes a page has received<br />
	<i>talking_about_count:</i> current attention metric provided by Facebook</p>';

} elseif($_GET["action"] == "pagenetwork" && isset($_GET["pageid"])) {

	$pid = $_GET["pageid"];

	if($_GET["depth"] == 1 || $_GET["depth"] == 2) {
		$depth = $_GET["depth"];
	} else if($_GET["depth"] == "x3" || $_GET["depth"] == "x4") {
		$depth = preg_replace("/x/","",$_GET["depth"]);
		echo "going deep: " . $depth . " ";
	} else {
		echo "GET parameter error"; exit;
	}


	$nodes = array();
	$edges = array();

	echo "crawling page: "; flush(); ob_flush();

	getnetnode($pid);

	for($i = 0; $i < $depth; $i++) {
		foreach($nodes as $key => $node) {
			if($node["done"] == 0) {
				getnetnode($key);
			}
		}
	}


	$content .= "nodedef>name VARCHAR,label VARCHAR,category VARCHAR,like_count INT,talking_about_count INT\n";
	$nodecounter = 0;
	foreach($nodes as $key => $node) {
		if($node["done"] == 1) {
			$content .= $key . "," . $node["name"] . "," . $node["category"] . "," . $node["like_count"] . "," . $node["talking_about_count"] . "\n";
			$nodecounter++;
		}
	}

	$content .= "edgedef>node1 VARCHAR,node2 VARCHAR\n";
	foreach($edges as $edge) {
		$tmpnodes = explode("_XXX_",$edge);
		if($nodes[$tmpnodes[0]]["done"] == 1 && $nodes[$tmpnodes[1]]["done"] == 1) {
			$content .=  $tmpnodes[0] . "," . $tmpnodes[1] . "\n";
		}
	}

	$filename = "./data/pagenetwork_".$pid."_".$nowdate."_".md5($nowdate).".gdf";
	file_put_contents($filename, $content);

	echo '<h2>download</h2>';

	echo '<p>retrieved ' . $nodecounter . ' pages with a crawl depth of '.$depth.'.</p>';

	echo '<p>Your <a href="'.$filename.'">gdf file</a> (right click, save as...).</p>';

	echo '<p><b>Attention: some browsers add a .txt extension to the files, which must be removed after saving. When in doubt, use Firefox.</b></p>';

	logit($filename,$clientip,$nodecounter);

}

function getnetnode($pid) {

	global $facebook,$nodes,$edges;

	$query = "/".$pid;

	echo $pid . " ";  flush(); ob_flush();
	
	$go = true;
	
	while($go == true) {
		
		try {
			$tmpinfo = $facebook->api($query);
		} catch (Exception $e) {
			echo 'API connection problem - nl. (Error message: '.$e.') Repeating call.<br />';
			flush(); ob_flush();
			sleep(1);
			continue;
		}
		
		$go = false;
	}

	$thelikes = array();
	$tmplikes = null;
	$go = true;
	
	while($go == true) {

		if(isset($tmplikes["paging"]["next"])) {
			$query = "/".$pid."/likes?limit=100&after=".$tmplikes["paging"]["cursors"]["after"];
		} else {
			$query = "/".$pid."/likes?limit=100";
		}
		
		
		try {
			$tmplikes = $facebook->api($query);
		} catch (Exception $e) {
			echo 'API connection problem - nl. (Error message: '.$e.') Repeating call.<br />';
			flush(); ob_flush();
			sleep(1);
			continue;
		}
		
		
		$thelikes = array_merge($thelikes,$tmplikes["data"]);
		
		//print_r($tmplikes);
		//sleep(1);
		
		if(!isset($tmplikes["paging"]["next"])) {
			$go = false;
		}
	}

	$tmpinfo["name"] = preg_replace("/[,\"\']/"," ",$tmpinfo["name"]);
	$tmpinfo["name"] = preg_replace("/[\n\r\t]/"," ",$tmpinfo["name"]);

	$node = array();
	$node["id"] = $tmpinfo["id"];
	$node["category"] = $tmpinfo["category"];
	//$node["founded"] = $tmpinfo["founded"];			// various date formats, not essential
	$node["like_count"] = $tmpinfo["likes"];
	$node["talking_about_count"] = $tmpinfo["talking_about_count"];
	$node["name"] = $tmpinfo["name"];
	$node["pagelikes"] = $thelikes;
	$node["done"] = 1;


	$nodes[$node["id"]] = $node;

	foreach($node["pagelikes"] as $pagelike) {

		if(!isset($nodes[$pagelike["id"]])) {
			$tmpnode = array("id" => $pagelike["id"],"done" => 0);
			$nodes[$pagelike["id"]] = $tmpnode;
		}

		$edge = $pid . "_XXX_" . $pagelike["id"];

		if(!isset($edges[$edge])) {
			$edges[$edge] = $edge;
 		}
	}
}

?>