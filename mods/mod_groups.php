<?php

if(!isset($_GET["action"])) {

	echo '<h2>groups:</h2>

	<p>Netvizz can extract two types of social networks from groups you are a member of (both create gdf files, users are <a href="faq.php">anonymized</a>):
	<ul>
	<li>friendship connections (API limits for group data are changing regularly, current version should be able to get up to 5000 group members. This may take a very long time, i.e. hours).</li>
	<li>interactions (if a user comments or likes another user\'s post, a directed link is created - currently the last 200 posts are take into account)</li>
	</ul>

	<hr class="fbcontentdivider" />

	<p>You are a member of the following groups:<br /><br />';

	try {
		$groups = $facebook->api('/me/groups');
	} catch (Exception $e) {
		echo 'An error has occurred. ('.$e.')';
		exit;
	}

	foreach($groups["data"] as $group) {
		echo $group["name"] . ' ( <a href="index.php?module=groups&action=group&groupid='.$group["id"].'">friendship connections</a> /
								  <a href="index.php?module=groups&action=groupinteractions&groupid='.$group["id"].'" >interactions</a> )<br />';
	}

	echo '</p>

	<hr class="fbcontentdivider" />

	<p><strong>file fields (friendship connections network - gdf format - nodes are users):</strong><br />
	<i>sex:</i> user specified sex<br />
	<i>locale:</i> user selected interface language<br />
	<i>groupid:</i> unique identifier of the group</p>

	<p><strong>file fields (interactions network - gdf format - nodes are users):</strong><br />
	<i>sex:</i> user specified sex<br />
	<i>locale:</i> user selected interface language<br />
	<i>posts:</i> number of posts a user posted to the group</p>

	<p><strong>file fields (interactions posts - tsv format - rows are posts):</strong><br />
	<i>message:</i> the message of the post<br />
	<i>created_time:</i> when the post was made<br />
	<i>comments:</i> the number of comments the post received<br />
	<i>likes:</i> the number of likes the post received<br />
	<i>commentsandlikes:</i> the upper two summed</p>';
}


// ------------------------------------------
// GROUP - friendship connections
// ------------------------------------------

elseif($_GET["action"] == "group" && isset($_GET["groupid"])) {

	$gid = $_GET["groupid"];


	// -------------------------------------------------------------------------
	// check group ownership and deanonymize for group owners

	$query = '/'.$gid.'/?fields=owner';

	try {
		$data = $facebook->api($query);
	} catch (Exception $e) {
		echo 'API connection timeout. (Error message: '.$e.') Please reload the page.<br />';
		flush(); ob_flush(); }

	echo "Group owner: " . $data["owner"]["name"] . " (id:" . $data["owner"]["id"] . ")<br /><br />";

	if($data["owner"]["id"] == $user_id) {
		$anon = false;
		echo "anonymization off: you are the group owner<br /><br />"; }


	// -------------------------------------------------------------------------
	// get users from group

	$gmembers = array();
	$exit = 0;
	$count = 0;

	echo 'retrieving users:<br />';

	while($exit == 0) {

		$oldcount = count($gmembers);
		$timeout = 0;

		try {
			$newgmembers = $facebook->api(array(
				'method' => 'fql.query',
				'query' => 'SELECT uid FROM group_member WHERE gid='.$gid.' LIMIT ' . $count * 500 . ',500',
				'callback' => ''
			));
		} catch (Exception $e) {
 			echo 'An error has occurred. (Error message: '.$e.') - Repeating call.<br />';
			$timeout = 1;
		}

		if($timeout == 0) {

			for($i = 0; $i < count($newgmembers); $i++) {
				if(!in_array($newgmembers[$i]["uid"],$gmembers)) {
					array_push($gmembers,$newgmembers[$i]["uid"]);
				}
			}

			if(count($gmembers) == $oldcount) { $exit = 1; }

			if($count == 9) { $exit = 1; }

			echo $count * 500 . "<br />";

			$count++;
		}

		flush(); ob_flush();
	}

	echo "<br />";


	$membernames = array();

	for($i=0; $i < count($gmembers); $i = $i + 100) {

		try {
			$tmp = $facebook->api(array(
				'method' => 'facebook.users.getInfo',
				'uids' => join(",",array_slice($gmembers,$i,100)),
				'fields' => array('name','locale','sex')
			));
		} catch (Exception $e) {
 			echo 'An error has occurred. (Error message: '.$e.') - Repeating call.<br />';
			$i = $i - 100;
		}

		if($tmp != "") {
			$membernames = array_merge($membernames,$tmp);
		}
	}


	// -------------------------------------------------------------------------
	// generate output

	$filename = "./data/group_".$gid."_".$nowdate."_".md5($nowdate).".gdf";
	$content = "";
	$edgecounter = 0;

	$content .= "nodedef>name VARCHAR,label VARCHAR,sex VARCHAR,locale VARCHAR,groupid VARCHAR\n";

	for($i = 0; $i < count($membernames); $i++) {
		if($anon == true) {
			$content .= sha1($membernames[$i]["uid"]) . ",user_" . sha1($membernames[$i]["uid"]) . "," . $membernames[$i]["sex"] . "," . $membernames[$i]["locale"] . "," . $gid . "\n";
		} else {
			$content .= $membernames[$i]["uid"] . "," . $membernames[$i]["name"] . "," . $membernames[$i]["sex"] . "," . $membernames[$i]["locale"] . "," . $gid . "\n";
		}
	}

	$content .= "edgedef>node1 VARCHAR,node2 VARCHAR\n";

	file_put_contents($filename, $content);
	$content = "";


	$blocksize = 45;
	$u1pos = 0;
	$u2pos = 0;

	asort($gmembers);
	$numFriends = count($gmembers);


	$queries1 = array();
	$queries2 = array();
	foreach($gmembers as $gmember) {
		$queries1[] = "uid1=" . $gmember;
		$queries2[] = "uid2=" . $gmember;
	}

	$pairs = array();

	echo 'retrieving connections:<br />';

	for($u1pos = 0; $u1pos < $numFriends; $u1pos = $u1pos + $blocksize) {

		echo $u1pos . " of " . $numFriends . "<br />";

		for ($u2pos = $u1pos; $u2pos < $numFriends; $u2pos = $u2pos + $blocksize) {

			$u1query = join(" OR ",array_slice($queries1, $u1pos, $blocksize));
			$u2query = join(" OR ",array_slice($queries2, $u2pos, $blocksize));

			$query = "SELECT uid1, uid2 FROM friend WHERE (".$u1query.") AND (".$u2query.")";

			$areFriends = null;

			try {
				$areFriends = $facebook->api(array(
					'method' => 'fql.query',
					'query' => $query,
					'callback' => ''
				));
    		} catch (Exception $e) {
     			echo 'An error has occurred. (Error message: '.$e.') - Repeating call.<br />';
				$u2pos = $u2pos - $blocksize;
				continue;
    		}

			flush();
			ob_flush();

			usleep(250000);

			if($areFriends != "") {

				for($k = 0; $k < count($areFriends); $k++) {
					$string1 = $areFriends[$k]["uid1"] . "," . $areFriends[$k]["uid2"] . "\n";
					$string2 = $areFriends[$k]["uid2"] . "," . $areFriends[$k]["uid1"] . "\n";
					if(!isset($pairs[$string1]) && !isset($pairs[$string2])) {
						if($anon == true) {
							$content .= sha1($areFriends[$k]["uid1"]) . "," . sha1($areFriends[$k]["uid2"]) . "\n";
						} else {
							$content .= $areFriends[$k]["uid1"] . "," . $areFriends[$k]["uid2"] . "\n";
						}
						$pairs[$string1] = true;
						$edgecounter++;
					}
				}

				file_put_contents($filename, $content,FILE_APPEND);

				$content = "";

			} else {

				$u2pos = $u2pos - $blocksize;

				usleep(1000000);
			}
		}
	}

	logit($filename,$clientip,count($membernames));

	// -------------------------------------------------------------------------
	// html

	echo '<h2>download</h2>';

	echo '<p>'.count($membernames).' nodes, '.$edgecounter.' edges</p>';

	echo '<p>Your <a href="'.$filename.'">gdf file</a> (right click, save as...).</p>';
}


// ------------------------------------------
// GROUP - interactions
// ------------------------------------------

else if($_GET["action"] == "groupinteractions" && isset($_GET["groupid"])) {

	$gid = $_GET["groupid"];

	// -------------------------------------------------------------------------
	// check group ownership and deanonymize for group owners

	$query = '/'.$gid.'/?fields=owner';

	try {
		$data = $facebook->api($query);
	} catch (Exception $e) {
		echo 'An error has occurred. (Error message: '.$e.') Please reload the page.<br />';
		flush(); ob_flush(); exit; }

	echo "Group owner: " . $data["owner"]["name"] . " (id:" . $data["owner"]["id"] . ")<br /><br />";

	if($data["owner"]["id"] == $user_id) {
		$anon = false;
		echo "anonymization off: you are the group owner<br /><br />"; }


	// -------------------------------------------------------------------------
	// get users from group

	$gmembers = array();
	$exit = 0;
	$count = 0;

	echo 'retrieving users:<br />';

	while($exit == 0) {

		$oldcount = count($gmembers);
		$timeout = 0;

		try {
			$newgmembers = $facebook->api(array(
				'method' => 'fql.query',
				'query' => 'SELECT uid FROM group_member WHERE gid='.$gid.' LIMIT ' . $count * 500 . ',500',
				'callback' => ''
			));
		} catch (Exception $e) {
 			echo 'An error has occurred. (Error message: '.$e.') Repeating call.<br />';
			$timeout = 1;
		}

		if($timeout == 0) {

			for($i = 0; $i < count($newgmembers); $i++) {
				if(!in_array($newgmembers[$i]["uid"],$gmembers)) {
					array_push($gmembers,$newgmembers[$i]["uid"]);
				}
			}

			if(count($gmembers) == $oldcount) { $exit = 1; }

			if($count == 9) { $exit = 1; }

			echo $count * 500 . "<br />";

			$count++;
		}

		flush(); ob_flush();
	}

	echo "<br />";


	$membernames = array();

	for($i=0; $i < count($gmembers); $i = $i + 100) {

		try {
			$tmp = $facebook->api(array(
				'method' => 'facebook.users.getInfo',
				'uids' => join(",",array_slice($gmembers,$i,100)),
				'fields' => array('name','locale','sex')
			));
		} catch (Exception $e) {
 			echo 'An error has occurred. (Error message: '.$e.') Repeating call.<br />';
			$i = $i - 100;
		}

		if($tmp != "") {
			foreach($tmp as $tmpuser) {
				$membernames[$tmpuser["uid"]] = $tmpuser;
			}
		}
	}

	//print_r($membernames);
	//exit;

	$nodes = array();
	$edges = array();

	$query = '/'.$gid.'/?fields=feed.limit(200).fields(from,comments.limit(1000),likes.limit(1000),created_time,type,message)';

	$stop = false;

	$tsvcontent = "id\ttype\tmessage\tcreated_time\tcomments\tlikes\tcommentsandlikes\n";

	while($stop == false) {

		try {
			$posts = $facebook->api($query);
		} catch (Exception $e) {
			echo 'API connection timeout. (Error message: '.$e.') Repeating call.<br />';
			flush();
			ob_flush();
			continue;
		}

		$stop = true;


		foreach($posts["feed"]["data"] as $post) {

			$msg = preg_replace("/[,\"\']/","_",$post["message"]);
			$msg = preg_replace("/[\n\r\t]/","_",$msg);

			if(!isset($nodes[$post["from"]["id"]])) {
				$nodes[$post["from"]["id"]] = array(
												"name" => $post["from"]["name"],
												"posts" => 1
												);
			} else {
				$nodes[$post["from"]["id"]]["posts"]++;
			}

			$likecount = 0;

			foreach($post["likes"]["data"] as $like) {

				$likecount++;

				if(!isset($nodes[$like["id"]])) {
					$nodes[$like["id"]] = array(
											"name" => $like["name"],
											"posts" => 0
											);
				}

				$edgename = $like["id"] . "_XXX_" . $post["from"]["id"];
				if(!isset($edges[$edgename])) {
					$edges[$edgename] = 1;
				} else {
					$edges[$edgename]++;
				}
			}

			$commentcount = 0;

			foreach($post["comments"]["data"] as $comment) {

				$commentcount++;

				if(!isset($nodes[$comment["from"]["id"]])) {
					$nodes[$comment["from"]["id"]] = array(
											"name" =>$comment["from"]["name"],
											"posts" => 0
											);
				}

				$edgename = $comment["from"]["id"] . "_XXX_" . $post["from"]["id"];
				if(!isset($edges[$edgename])) {
					$edges[$edgename] = 1;
				} else {
					$edges[$edgename]++;
				}

			}

			$tsvcontent .= $post["id"] . "\t" . $post["type"] . "\t" . $msg . "\t" . $post["created_time"] . "\t" . $commentcount . "\t" . $likecount . "\t" . ($likecount + $commentcount) . "\n";
		}
	}

	$content .= "nodedef>name VARCHAR,label VARCHAR,sex VARCHAR,locale VARCHAR,posts INT\n";

	$nodecounter = 0;
	$edgecounter = 0;

	foreach($nodes as $key => $node) {
		if($anon == true) {
			$content .= sha1($key) . ",user_" . sha1($key);
		} else {
			$content .= $key . "," . $node["name"];
		}
		$content .= "," . $membernames[$key]["sex"] . "," . $membernames[$key]["locale"] . "," . $node["posts"] . "\n";
		$nodecounter++;
	}

	$content .= "edgedef>node1 VARCHAR,node2 VARCHAR,weight INT\n";

	foreach($edges as $key => $edge) {
		$tmp = explode("_XXX_", $key);
		if($anon == true) {
			$content .= sha1($tmp[0]) . "," . sha1($tmp[1]) . "," . $edge . "\n";
		} else {
			$content .= $tmp[0] . "," . $tmp[1] . "," . $edge . "\n";
		}
		$edgecounter += $edge;
	}

	$filename = "./data/groupinteractions_".$gid."_".$nowdate."_".md5($nowdate).".gdf";
	$filename_tsv = "./data/groupposts_".$gid."_".$nowdate."_".md5($nowdate).".tab";

	file_put_contents($filename, $content);
	file_put_contents($filename_tsv, $tsvcontent);

	logit($filename,$clientip,count($friendnames));

	// -------------------------------------------------------------------------
	// html

	echo '<h2>download</h2>';

	echo '<p>extracted data from ' . count($posts["feed"]["data"]) . ' posts, ' . ($nodecounter ) . ' users active, commenting or liking ' . $edgecounter . ' times</p>';

	echo '<p>Your <a href="'.$filename.'">gdf file</a> for user interactions (right click, save as...).</p>';

	echo '<p>Your <a href="'.$filename_tsv.'">tab file</a> for posts (right click, save as...).</p>';

	echo '<p><b>Attention: some browsers add a .txt extension to the files, which must be removed after saving. When in doubt, use Firefox.</b></p>';
}
?>