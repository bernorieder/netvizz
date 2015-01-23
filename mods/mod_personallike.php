<?php

if(!isset($_GET["action"])) {

	echo '<h2>your like network:</h2>

	<p>Creates a bipartite network file (gdf format) from your friends and their likes (both users and liked objects are nodes).
	Only liked pages are taken into account, not external objects. Count on waiting about a second per friend.</p>

	<form method="get" action="index.php" id="user_dataform">
		<input id="user_action" type="hidden" name="action" value="do">
		<input id="user_module" type="hidden" name="module" value="personallike">

		<input type="submit" value="start" />
	</form>

	<hr class="fbcontentdivider" />

	<p><strong>file fields (network file - gdf format - nodes are users or liked objects):</strong><br />
	<i>node_type:</i> user or like<br />
	<i>node_category:</i> category of a like according to Facebook\'s ontology, gives "user" for users<br />
	<i>user_sex:</i> user specified sex<br />
	<i>user_locale:</i> user selected interface language<br />
	<i>user_likecount:</i> number of objects a user liked<br />
	<i>like_locallikes:</i> number of times an item was liked by your friends<br />
	<i>like_fulllikes:</i> number of times an item was liked by all Facebook users</p>

	<p><strong>Attention:</strong> data depends on your friends\' privacy settings.</p>';

} elseif($_GET["action"] == "do") {

	$filename = "./data/like_".$user_id."_".$nowdate."_".md5($nowdate).".gdf";

	// check whether file has already been generated for today
	if($handle = opendir("./data/")) {
    	while (false !== ($entry = readdir($handle))) {
    		if(preg_match("/".substr($filename, 7, 16 + strlen($user_id))."/", $entry)) {
    			echo 'Your file has already been generated for today:</p>';
    			echo '<p>Your <a href="./data/'.$entry.'">gdf file</a> (right click, save as...).</p>';
    			exit;
    		}
        }
    }


	// -------------------------------------------------------------------------
	// get friends' basic info

	$friendlist = $facebook ->api('/me/friends');

	$friends = array();
	foreach($friendlist["data"] as $friend) {
		$friends[] = $friend["id"];
	}


	$friendnames = array();

	for($i = 0; $i < count($friends); $i = $i + 200) {

		try {
			$tmp = $facebook->api(array(
				'method' => 'facebook.users.getInfo',
				'uids' => join(",",array_slice($friends,$i,200)),
				'fields' => array('name','locale','sex')
			));
		} catch (Exception $e) {
			echo 'API connection timeout. (Error message: '.$e.') Repeating call.<br />';
			if(preg_match("/Application request limit reached/", $e)) {
				echo "<br />Netvizz is out of API call credits. Too many users have been using this tool too hard. Try again at a later time.";
				exit;
			}
			sleep(1);
			$i -= 200;
			continue;
		}

		$friendnames = array_merge($friendnames,$tmp);
	}

	echo "<p>getting likes for " . count($friendnames) . ' friends</p>friends processed: ';
	flush(); ob_flush();




	// -------------------------------------------------------------------------
	// get friends' likes

	$likes = array();
	for($i = 0; $i < count($friends); $i = $i + 1) {

		try {
			$tmps = $facebook->api('/'.$friends[$i].'/likes?limit=10000&fields=name,likes,category');
		} catch (Exception $e) {
			echo 'API connection timeout. (Error message: '.$e.') Repeating call.<br />';
			if(preg_match("/Application request limit reached/", $e)) {
				echo "<br />Netvizz is out of API call credits. Too many users have been using this tool too hard. Try again at a later time.";
				exit;
			}
			sleep(1);
			$i--;
			continue;
		}

		$friendnames[$i]["likecount"] = count($tmps["data"]);

		foreach($tmps["data"] as $tmp) {
			if(!isset($likes[$tmp["id"]])) {
				$likes[$tmp["id"]] = array();
				$likes[$tmp["id"]]["name"] = $tmp["name"];
				$likes[$tmp["id"]]["category"] = $tmp["category"];
				$likes[$tmp["id"]]["likecount"] = $tmp["likes"];
				$likes[$tmp["id"]]["count"] = 1;
				$likes[$tmp["id"]]["likedby"] = array($friends[$i]);
			} else {
				$likes[$tmp["id"]]["count"]++;
				$likes[$tmp["id"]]["likedby"][] = $friends[$i];
			}
		}

		echo $i . " ";

		flush();
		ob_flush();

		usleep(500000);
	}

	// -------------------------------------------------------------------------
	// generate output

	$filename = "./data/like_".$user_id."_".$nowdate."_".md5($nowdate).".gdf";
	$content = "";
	$edgecounter = 0;

	$content .= "nodedef>name VARCHAR,label VARCHAR,node_type VARCHAR,node_category VARCHAR,user_sex VARCHAR,user_locale VARCHAR,user_likecount INT,like_locallikes INT,like_fulllikes INT\n";

	for($i = 0; $i < count($friends); $i++) {
		$content .= $friendnames[$i]["uid"] . "," . preg_replace("/[,\"\']/","_",$friendnames[$i]["name"]) . ",user,user";
		$content .= ','.$friendnames[$i]["sex"];
		$content .= ','.$friendnames[$i]["locale"];
		$content .= ','.$friendnames[$i]["likecount"];
		$content .= ",,\n";
	}

	foreach ($likes as $key => $value) {

		$key = preg_replace("/[,\"\']/","_",$key);
		$key = preg_replace("/[\n\r\t]/","_",$key);

		$content .= $key.",".preg_replace("/[,\"\']/","_",$value["name"]).",like,".$value["category"].",,,,".$value["count"].",".$value["likecount"];
		$content .= "\n";
	}



	$content .= "edgedef>node1 VARCHAR,node2 VARCHAR\n";

	foreach($likes as $key => $value) {
		foreach($value["likedby"] as $likedby) {
			$content .= $key . "," . $likedby . "\n";
			$edgecounter++;
		}
	}

	file_put_contents($filename, $content);

	logit($filename,$clientip,count($friendnames));

	// -------------------------------------------------------------------------
	// html

	echo '<h2>download</h2>';
	echo '<p>'.count($friendnames).' users, ' . count($likes) . ' different liked objects, ' .$edgecounter.' likes</p>';
	echo '<p>Your <a href="'.$filename.'">gdf file</a> (right click, save as...).</p>';
	echo '<p><b>Attention: some browsers add a .txt extension to the files, which must be removed after saving. When in doubt, use Firefox.</b></p>';

}

?>