<?php

if(!isset($_GET["action"])) {

	echo '<h2>your personal friend network:</h2>

	<p>Creates a network file (gdf format) with all the friendship connections in your personal network, as well as a stat file (tsv format).</p>

	<form method="get" action="index.php" id="user_dataform">
		<input id="user_action" type="hidden" name="action" value="do">
		<input id="user_module" type="hidden" name="module" value="personal">

		Select user data to include in the file (sex, interface language, and account age ranking are standard):<br /><br />
		<input id="user_activitycount" type="checkbox" name="activitycount" /> friends\' like and post count (public and visible to logged user, first 1000 only), includes counts for received likes and comments on posts,
		adds an additional ±6 seconds of waiting time per friend<br /><br />

		<input type="submit" value="start" />
	</form>

	<hr class="fbcontentdivider" />

	<p><strong>file fields (network file - gdf format - nodes are users):</strong><br />
	<i>sex:</i> user specified sex<br />
	<i>locale:</i> user selected interface language<br />
	<i>agerank:</i> accounts ranked by creation date where 1 is youngest<br />
	<i>like_count:</i> number of user likes<br />
	<i>post_count:</i> number of user posts<br />
	<i>post_like_count:</i> number of likes on user\'s posts<br />
	<i>post_comment_count:</i> number of comments on user\'s posts<br />
	<i>post_engagement_count:</i> post_comment_count + post_like_count</p>

	<p><strong>file fields (stat file - tsv format - rows are users):</strong><br />
	<i>sex:</i> user specified sex<br />
	<i>locale:</i> user selected interface language<br />
	<i>agerank:</i> accounts ranked by creation date where 1 is youngest<br />
	<i>like_count:</i> number of user likes<br />
	<i>post_count:</i> number of user posts<br />
	<i>post_like_count:</i> number of likes on the user\'s posts<br />
	<i>post_comment_count:</i> number of comments on the user\'s posts<br />
	<i>post_engagement_count:</i> post_comment_count and post_like_count summed</p>

	<p><strong>Attention:</strong> data depends on your friends\' privacy settings and the filtering choices you made for your newsfeed.</p>';


} elseif($_GET["action"] == "do") {

	// check whether user requests friends' activity count
	if($_GET["activitycount"] == "on") {
		$filename = "./data/personal_activitycount_".$user_id."_".$nowdate."_".md5($nowdate).".gdf";
		$filename_tsv = "./data/personal_activitycount_".$user_id."_".$nowdate."_".md5($nowdate).".tab";
	} else {
		$filename = "./data/personal_basic_".$user_id."_".$nowdate."_".md5($nowdate).".gdf";
		$filename_tsv = "./data/personal_basic_".$user_id."_".$nowdate."_".md5($nowdate).".tab";
	}

	if($handle = opendir("./data/")) {
    	while (false !== ($entry = readdir($handle))) {
    		if(preg_match("/".substr($filename, 7, 22 + strlen($user_id))."/", $entry)) {
    			echo 'Your files files have already been generated for today:</p>';
    			echo '<p>Your <a href="./data/'.preg_replace("/\.tab/",".gdf",$entry).'">gdf file</a> (right click, save as...).</p>';
    			echo '<p>Your <a href="./data/'.preg_replace("/\.gdf/",".tab",$entry).'">tab file</a> (right click, save as...).</p>';
    			exit;
    		}
        }
	}

	$friends = array();
	$friendnames = array();


	// -------------------------------------------------------------------------
	// get basic user data for friends

	$datatoget = array('uid','name','locale','sex');

	$friendlist = $facebook ->api('/me/friends');

	foreach($friendlist["data"] as $friend) {
		$friends[] = $friend["id"];
		$friendsSQL[] = "uid='" . $friend["id"] . "'";
	}

	for($i = 0; $i < count($friendsSQL); $i = $i + 45) {

		$tmpfriends = array_slice($friendsSQL,$i,45);
		$query = "SELECT " . implode(",", $datatoget) . " FROM user WHERE " . implode(" OR ", $tmpfriends);

		try {
			$tmpfriends = $facebook->api(array(
				'method' => 'fql.query',
				'query' => $query,
				'callback' => ''
			));
    	} catch (Exception $e) {
     		echo 'API connection timeout. (Error message: '.$e.') Repeating call.<br />';
     		flush(); ob_flush();
     		$i = $i - 45;
			usleep(1000000);
			continue;
		}

		if($tmpfriends != "") {
			$friendnames = array_merge($friendnames,$tmpfriends);
		} else {
			$i = $i - 45;
			usleep(1000000);
		}
	}



	// -------------------------------------------------------------------------
	// get likes and posts for friends

	if($_GET["actioncount"] == "on") {

		echo "getting user likes and posts (".count($friendnames)."):<br />";

		for($i = 0; $i < count($friendnames); $i = $i + 1) {
		//for($i = 0; $i < 10; $i = $i + 1) {

			try {
				$tmp = $facebook->api('/'.$friendnames[$i]["uid"].'/likes?fields=id&limit=1000');
			} catch (Exception $e) {
				echo 'API connection timeout. (Error message: '.$e.') Repeating call.<br />';
				if(preg_match("/Application request limit reached/", $e)) {
					echo "<br />Netvizz is out of API call credits. Too many users have been using this tool too hard. Try again at a later time.";
					exit;
				}
				$i--;
				sleep(1);
				continue;
			}
			$friendnames[$i]["like_count"] = count($tmp["data"]);

			usleep(100000);

			try {
				$tmp = $facebook->api('/'.$friendnames[$i]["uid"].'/posts?fields=id,comments.limit(1).summary(1),likes.limit(1).summary(1)&limit=1000');
			} catch (Exception $e) {
				echo 'API connection timeout. (Error message: '.$e.') Repeating call.<br />';
				$i--;
				usleep(5000000);
				continue;
			}
			$friendnames[$i]["post_count"] = count($tmp["data"]);

			//print_r($tmp); exit;

			$friendnames[$i]["post_likes_count"] = 0;
			$friendnames[$i]["post_comments_count"] = 0;
			foreach($tmp["data"] as $post) {
				if(isset($post["likes"]["summary"]["total_count"])) {
					$friendnames[$i]["post_like_count"] += $post["likes"]["summary"]["total_count"];
				}
				if(isset($post["comments"]["summary"]["total_count"])) {
					$friendnames[$i]["post_comments_count"] += $post["comments"]["summary"]["total_count"];
				}
			}

			$friendnames[$i]["post_engagement_count"] = $friendnames[$i]["post_comments_count"] + $friendnames[$i]["post_like_count"];

			echo $i . " ";

			flush();
			ob_flush();

			usleep(100000);
		}

		echo "<br /><br />";
	}



	// -------------------------------------------------------------------------
	// generate output for nodes

	$edgecounter = 0;

	$content = 'nodedef>name VARCHAR,label VARCHAR,sex VARCHAR,locale VARCHAR,agerank INT';
	$tsv = "uid\tname\tsex\tlocale\tagerank";
	if($_GET["actioncount"] == "on") {
		$content .= ',like_count INT,post_count INT,post_like_count INT,post_comment_count INT,post_engagement_count INT';
		$tsv .= "\tlike_count\tpost_count\tpost_like_count\tpost_comment_count\tpost_engagement_count";
	}
	$content .= "\n";
	$tsv .= "\n";

	for($i = 0; $i < count($friends); $i++) {
		$content .= $friendnames[$i]["uid"] . "," . $friendnames[$i]["name"] . ',' . $friendnames[$i]["sex"] . ',' . $friendnames[$i]["locale"] . ',' . (count($friendnames) - $i);
		$tsv .= $friendnames[$i]["uid"] . "\t" . addslashes($friendnames[$i]["name"]) . "\t" . $friendnames[$i]["sex"] . "\t" . $friendnames[$i]["locale"] . "\t" . (count($friendnames) - $i);
		if($_GET["actioncount"] == "on") {
			$content .= ','.$friendnames[$i]["like_count"].','.$friendnames[$i]["post_count"].','.$friendnames[$i]["post_like_count"].
					 ','.$friendnames[$i]["post_comments_count"].','.$friendnames[$i]["post_engagement_count"];
			$tsv .= "\t".$friendnames[$i]["like_count"]."\t".$friendnames[$i]["post_count"]."\t".$friendnames[$i]["post_like_count"]."\t".
					$friendnames[$i]["post_comments_count"]."\t".$friendnames[$i]["post_engagement_count"];
		}
		$content .= "\n";
		$tsv .= "\n";
	}

	$content .= "edgedef>node1 VARCHAR,node2 VARCHAR\n";


	// -------------------------------------------------------------------------
	// get connections between friends

	$blocksize = 50;
	$u1pos = 0;
	$u2pos = 0;

	asort($friends);
	$numFriends = count($friends);

	$queries1 = array();
	$queries2 = array();
	foreach($friends as $friend) {
		$queries1[] = "uid1=" . $friend;
		$queries2[] = "uid2=" . $friend;
	}

	$pairs = array();

	echo "getting connections (".$numFriends."): <br />";

	for($u1pos = 0; $u1pos < $numFriends; $u1pos = $u1pos + $blocksize) {

		echo $u1pos . " "; flush();ob_flush();

		for ($u2pos = $u1pos; $u2pos < $numFriends; $u2pos = $u2pos + $blocksize) {

			$u1query = join(" OR ",array_slice($queries1, $u1pos, $blocksize));
			$u2query = join(" OR ",array_slice($queries2, $u2pos, $blocksize));

			$query = "SELECT uid1, uid2 FROM friend WHERE (".$u1query.") AND (".$u2query.")";
			// SELECT uid1, uid2 FROM friend WHERE uid1=709686565 AND uid2=529065125

			try {
				$areFriends = $facebook->api(array(
					'method' => 'fql.query',
					'query' => $query,
					'callback' => ''
				));
    		} catch (Exception $e) {
     			echo 'API connection timeout. (Error message: '.$e.') Repeating call.<br />';
				if(preg_match("/Application request limit reached/", $e)) {
					echo "<br />Netvizz is out of API call credits. Too many users have been using this tool too hard. Try again at a later time.";
					exit;
				}
				flush();ob_flush();
				$u2pos = $u2pos - $blocksize;
				usleep(5000000);
				continue;
			}

			usleep(250000);

			if($areFriends != "") {
				for($k = 0; $k < count($areFriends); $k++) {
					$string1 = $areFriends[$k]["uid1"] . "," . $areFriends[$k]["uid2"] . "\n";
					$string2 = $areFriends[$k]["uid2"] . "," . $areFriends[$k]["uid1"] . "\n";
					if(!isset($pairs[$string1]) && !isset($pairs[$string2])) {
						$content .= $string1;
						$pairs[$string1] = true;
						$edgecounter++;
					}
				}

			} else {
				$u2pos = $u2pos - $blocksize;
				usleep(1000000);
			}
		}
	}

	file_put_contents($filename_tsv, $tsv);
	file_put_contents($filename, $content);

	logit($filename,$clientip,count($friendnames));

	// -------------------------------------------------------------------------
	// html

	echo '<h2>download</h2>';

	echo '<p>'.count($friendnames).' nodes, '.$edgecounter.' edges</p>';

	echo '<p>Your <a href="'.$filename.'">gdf file</a> (right click, save as...).</p>';
	echo '<p>Your <a href="'.$filename_tsv.'">tab file</a> (right click, save as...).</p>';
	echo '<p><b>Attention: some browsers add a .txt extension to the files, which must be removed after saving. When in doubt, use Firefox.</b></p>';
}

?>