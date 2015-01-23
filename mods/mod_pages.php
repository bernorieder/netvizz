


<script type="text/javascript" language="javascript">

	function sendPage(_id,_fromwho) {

		var _radios = document.getElementsByName("gettype");

		for (var i = 0; i < _radios.length; i++) {
		    if (_radios[i].checked) {
		        _gettype = _radios[i].value;
		    }
		}

		_num = document.getElementById("page_posts").value;
		_diggnot = document.getElementById("diggnot").checked;
		_url = "index.php?module=pages&action=pages&pageid="+_id+"&fromwho="+_fromwho+"&gettype="+_gettype+"&diggnot="+_diggnot;
		if(_gettype == "daterange") {
			_since = document.getElementById("since").value;
			_until = document.getElementById("until").value;
			_url += "&since="+_since+"&until="+_until;
		} else {
			_url += "&posts="+_num;
		}
		//alert(_url);
		document.location.href = _url;
	}

</script>


<?php

if(!isset($_GET["action"])) {



	echo '<h2>pages:</h2>

	<p>This module gets the last posts (specify number in the field below) on the page and creates:
	<ol type="a">
	<li>A bipartite graph file in gdf format that shows posts, users (<a href="faq.php">anonymized</a>), and connections between the two. A user is connected to a post if she commented or liked it.</li>
	<li>A tabular file (tsv) that lists different metrics for each post.</li>
	<li>A tabular file (tsv) that contains the text of user comments (users <a href="faq.php">anonymized</a>).</li>
	<li>A tabular file (tsv) that contains page likes per user country.</li>
	</ol>
	Processing time depends a lot on page size - may take up to an hour. The script may run out of memory for very large pages (> 1M comments/likes). Consider grabbing stats only in this case or work with
	smaller date blocks.</p>

	<hr class="fbcontentdivider" />

	<p>You have "liked" the following pages (will only show first 500):<br /><br />

	get the <input type="radio" name="gettype" value="last" checked="checked" /> last <input id="page_posts" type="text" value="50" maxlength="3" size="3" /> posts (max. 999) or
	<input type="radio" name="gettype" value="daterange" /> posts between <input type="text" id="since" value="'.date("Y-m-d", time() - 60 * 60 * 24 * 7).'"  style="width:70px;" /> and
	<input type="text" id="until" value="'.date("Y-m-d", time() - 60 * 60 * 24).'" style="width:70px;" /></p><p>
	<input type="checkbox" id="diggnot" name="diggnot" /> get only post statistics (no network, comment, and per country files, much faster and can deal with larger pages)</p><p>';

	try {
		$pages = $facebook->api('/me/likes?limit=500');
	} catch (Exception $e) {
		echo 'An error has occurred. ('.$e.')';
		exit;
	}

	foreach($pages["data"] as $page) {
		echo $page["name"] . ' ( <a href="javascript:sendPage(\''.$page["id"].
			 '\',\'posts\')">post by page only</a> / <a href="javascript:sendPage(\''.$page["id"].'\',\'feed\')">posts by page and users</a> )<br />';
	}

	echo '</p>

	<hr class="fbcontentdivider" />

	<p><strong>file fields (network file - gdf format - nodes are either posts or users):</strong><br />
	<i>type:</i> either "user" (if node is user) or "post_page_pageid" (post by page) or "post_user_pageid" (post by user)<br />
	<i>type_post:</i> Facebook\'s post classification (e.g. photo, status, etc.)<br />
	<i>post_published:</i> publishing date<br />
	<i>post_published_unix:</i> publishing date as Unix timestamp (for easy conversion and ranking)<br />
	<i>user_locale:</i> user selected interface language (empty if node is post)<br />
	<i>user_sex:</i> user specified sex (empty if node is post)<br />
	<i>likes:</i> number of actually retreived likes a post reveiced or a user made<br />
	<i>likes_count_fb:</i> Facebook provided like count for posts (can be higher than actually retieved likes)<br />
	<i>comments_all:</i> number of comments made on a post or by a user<br />
	<i>comments_base:</i> number of base level comments (in threaded conversations)<br />
	<i>comments_replies:</i> number of reply level comments (in threaded conversations)<br />
	<i>comment_likes:</i> number of likes on comments (posts only)<br />
	<i>shares:</i> number of shares (posts only)<br />
	<i>engagement:</i> likes, comments_all and shares summed<br />
	<i>post_id:</i> id of the post (empty for users)<br />
	<i>post_link:</i> link of the post (empty for users)</p>

	<p><i>edge weight</i> encodes the number of times a user commeted or liked a post</p>

	<p><strong>file fields (stat file - tsv format - rows are posts):</strong><br />
	<i>type:</i> Facebook\'s post classification (e.g. photo, status, etc.)<br />
	<i>by:</i> either"post_page_pageid" (post by page) or "post_user_pageid" (post by user)<br />
	<i>post_message:</i> text of the post<br />
	<i>picture:</i> picture URL (if a picture is attached to the post)<br />
	<i>link:</i> link URL (if the post points to external contant)<br />
	<i>link_domain:</i> domain name of link<br />
	<i>post_published:</i> publishing date<br />
	<i>post_published_unix:</i> publishing date as Unix timestamp (for easy conversion and ranking)<br />
	<i>likes:</i> number of actually retreived likes a post reveiced or a user made<br />
	<i>likes_count_fb:</i> Facebook provided like count for posts (can be higher than actually retieved likes)<br />
	<i>comments_all:</i> number of comments made on a post or by a user<br />
	<i>comments_base:</i> number of base level comments (in threaded conversations)<br />
	<i>comments_replies:</i> number of reply level comments (in threaded conversations)<br />
	<i>shares:</i> number of shares<br />
	<i>comment_likes:</i> number of likes on comments<br />
	<i>engagement:</i> likes, comments_all and shares summed<br />
	<i>post_id:</i> id of the post<br />
	<i>post_link:</i> link of the post</p>

	<p><strong>file fields (comments file - tsv format - rows are comments):</strong><br />
	<i>post_id:</i> id of the post<br />
	<i>post_by:</i> author of the post<br />
	<i>post_text:</i> text of the post<br />
	<i>post_published:</i> publishing date of the post<br />
	<i>comment_id:</i> id of the comment<br />
	<i>comment_by:</i> author of the comment<br />
	<i>is_reply:</i> whether the comment is a reply to another comment (in threaded conversations)<br />
	<i>comment_message:</i> text of the comment<br />
	<i>comment_published:</i> publishing date of the comment<br />
	<i>comment_like_count:</i> number of likes on the comment</p>';



} elseif($_GET["action"] == "pages" && isset($_GET["pageid"])) {

	$toget = $_GET["posts"];
	$diggnot = ($_GET["diggnot"] == "true") ? true : false;
	$frame = ($toget < 100) ? $toget:100;
	

	if($_GET["override"] != "true" && (preg_match("/[^0-9]/",$_GET["posts"]) || $_GET["posts"] > 999)) {
		echo "GET parameter error"; exit;
	}

	if($_GET["gettype"] == "daterange" && isset($_GET["since"]) && isset($_GET["until"])) {
		$since = $_GET["since"];
		$until = $_GET["until"];
		if(!preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})/",$since) || !preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})/",$until)) {
			echo "GET parameter error"; exit;
		}
		$since = strtotime($since . "T00:00:00+0000");
		$until = strtotime($until . "T23:59:59+0000");
		$timemode = "daterange";
		echo "from: " . gmdate(DATE_ISO8601,$since) . " to " . gmdate(DATE_ISO8601,$until) . "<br /><br />";
		$frame = 50;
	} else {
		$until = false;
		$timemode = "lastposts";
		echo "trying to get " . $frame . " posts<br />";
	}


	$pids = array();
	$pids[] = $_GET["pageid"];
	// $pids = array(8304333127,5281959998);
	// nytimes: 8304333127
	// WSJ: 5281959998

	foreach($pids as $pid) {

		// create filenames for all three files and a tmp file for edges
		$fn = "./data/page_".$pid."_".$nowdate;
		$filename = $fn . ".gdf";
		$filename_tsv = $fn . ".tab";
		$filename_comments_tsv = $fn . "_comments.tab";
		$filename_country_tsv = $fn . "_country.tab";
		$filename_tmp_edges = $fn . "_tmpedges";

		// if only stat file is selected, follow this path, too cumbersome to keep them combined
		if($diggnot == true) {			
			
			$tsv_diggnot = "type\tby\tpost_message\tpicture\tlink\tlink_domain\tpost_published\tpost_published_unix\tpost_published_sql\tlikes_count_fb\tcomments_count_fb\tshares\tengagement\tpost_id\tpost_link\n";
			
			$frame = 20;
			$run = true;
			$i = 0;
			$posts = array();
	
			while($run == true) {
	
				$query = '/'.$pid.'/' . $_GET["fromwho"] . '?fields=id,created_time,from,type,picture,link,story,message,shares,actions,likes.limit(1).summary(true),comments.limit(1).summary(true)&limit='.$frame;
				if($until != false) { $query .= "&until=" . $until; }

				try {
					$tmp = $facebook->api($query);
				} catch (Exception $e) {
					echo 'API connection error. (Error message: '.$e.') Repeating call.<br />';
					flush(); ob_flush();
					sleep(3);
					continue;
				}
	
				if(count($tmp["data"]) == 0) { break; }		// if there are no more posts, break loop
	
				//print_r($tmp); exit;
	
				//print_r($tmp["data"]); exit;
	
				foreach($tmp["data"] as $tmppost) {
					if(strtotime($tmppost["created_time"]) >= $since) {
						$tmppost["like_count"] = ($tmppost["likes"]["summary"]["total_count"] == "") ? 0:$tmppost["likes"]["summary"]["total_count"];
						$tmppost["comment_count"] = ($tmppost["comments"]["summary"]["total_count"] == "") ? 0:$tmppost["comments"]["summary"]["total_count"];
						unset($tmppost["likes"]);
						$posts[] = $tmppost;
						$i++;
					}
	
					$until = strtotime($tmppost["created_time"]) - 1;
				}
	
	
				if($timemode == "lastposts") {
					echo "pid: " . $pid . " / until: " . gmdate(DATE_ISO8601,$until) .  " - " . $i . " of " . $toget . " (" . count($tmp["data"]) . ")<br>";
					if($i >= $toget) { $run = false; }
				} else {
					echo "pid: " . $pid . " / until:" . gmdate(DATE_ISO8601,$until) . " (" . count($tmp["data"]) . "," . memory_get_usage(true) . ")<br>";
					if($until <= $since) { $run = false; }
				}
	
				flush(); ob_flush();
			}
			
			//print_r($posts); exit;
			
			$pcount = count($posts);
			
			foreach($posts as $post) {
				
				$msg = (isset($post["message"])) ? $post["message"]:$post["story"];
				$msg = preg_replace("/[,\"\']/","_",$msg);
				$msg = preg_replace("/[\n\r\t]/"," ",$msg);
				$from = ($post["from"]["id"] == $pid) ? "post_page_" . $pid :  "post_user_" . $pid;
				if ($post["shares"]["count"] == "") { $post["shares"]["count"] = 0; }
				preg_match_all('/\/\/(.*?)(\/|$)/i', $post["link"], $tmp);
				$domain = preg_replace("/www./","",$tmp[1][0]);
				
				$tsv_diggnot .= $post["type"]."\t".$from."\t".$msg."\t".$post['picture']."\t".$post["actions"][0]["link"]."\t".$domain."\t".$post['created_time']."\t".
				strtotime($post['created_time'])."\t".date("Y-m-d H:i:s",strtotime($post['created_time']))."\t".$post["like_count"]."\t".$post["comment_count"]."\t".
				$post['shares']['count']."\t".($post["like_count"]+$post["comment_count"]+$post['shares']['count'])."\t".$post["id"]."\t".$post['actions'][0]["link"]."\n";
			}

			file_put_contents($filename_tsv, $tsv_diggnot, FILE_APPEND);
			
			echo '<h2>download</h2>';

			echo '<p>retrieved data for ' . $pcount . ' posts</p>';

			$files = array($filename_tsv);

			zipit($fn,$files);
			logit($filename_tsv,$clientip,$pcount);
			
			exit;
		}


		// write first line, rest is written continuously
		$tsv = "type\tby\tpost_message\tpicture\tlink\tlink_domain\tpost_published\tpost_published_unix\tpost_published_sql\tlikes\tlikes_count_fb\tcomments_all\tcomments_base\tcomments_replies\tshares\tcomment_likes\tengagement\tpost_id\tpost_link\n";
		$comments_tsv = "post_id\tpost_by\tpost_text\tpost_published\tcomment_id\tcomment_by\tis_reply\tcomment_message\tcomment_published\tcomment_like_count\n";
		$tmp_edges = "";
		file_put_contents($filename_tsv, $tsv);
		file_put_contents($filename_comments_tsv, $comments_tsv);
		file_put_contents($filename_tmp_edges, $tmp_edges);



		// --- get post list ---

		$run = true;
		$i = 0;
		$postids = array();

		while($run == true) {

			$query = '/'.$pid.'/' . $_GET["fromwho"] . '?fields=id,created_time,likes.limit(1).summary(true)&limit='.$frame;
			if($until != false) { $query .= "&until=" . $until; }

			try {
				$tmp = $facebook->api($query);
			} catch (Exception $e) {
				echo 'API connection error. (Error message: '.$e.') Repeating call.<br />';
				flush(); ob_flush();
				sleep(3);
				continue;
			}

			if(count($tmp["data"]) == 0) { break; }		// if there are no more posts, break loop

			foreach($tmp["data"] as $tmppost) {
				if(strtotime($tmppost["created_time"]) >= $since) {
					$tmppost["like_count"] = ($tmppost["likes"]["summary"]["total_count"] == "") ? 0:$tmppost["likes"]["summary"]["total_count"];
					unset($tmppost["likes"]);
					$postids[] = $tmppost;
					$i++;
				}

				$until = strtotime($tmppost["created_time"]) - 1;
			}


			if($timemode == "lastposts") {
				echo "pid: " . $pid . " / until: " . gmdate(DATE_ISO8601,$until) .  " - " . $i . " of " . $toget . " (" . count($tmp["data"]) . ")<br>";
				if($i >= $toget) { $run = false; }
			} else {
				echo "pid: " . $pid . " / until:" . gmdate(DATE_ISO8601,$until) . " (" . count($tmp["data"]) . "," . memory_get_usage(true) . ")<br>";
				if($until <= $since) { $run = false; }
			}

			flush(); ob_flush();
		}

		//print_r($postids);


		echo "retreived ".count($postids)." posts. now digging for likes and comments: "; flush(); ob_flush();

		// --- work through posts ---
		$nodes = array();

		for($i = 0; $i < count($postids); $i++) {

			$edges = array();

			$postid = $postids[$i]["id"];

			$query = '/'.$postid.'/?fields=from,type,picture,link,story,message,shares,actions';

			echo $i." "; flush(); ob_flush(); sleep(2);

			try {
				$post = $facebook->api($query);
			} catch (Exception $e) {
				echo 'API connection error collecting post. (Error message: '.$e.') Repeating call.<br />';
				flush(); ob_flush();
				$i--;
				continue;
			}

			// -- digg into likes
			$tmplikes = array();
			$run = true;
			while($run == true) {

				$query = '/'.$postid.'/likes?limit=1000&summary=true&fields=id,name&after=' . $tmphash;

				//echo "dig:" . count($tmplikes) . " ".$query."<br />"; flush(); ob_flush();

				try {
					$likes = $facebook->api($query);
				} catch (Exception $e) {
					echo 'API connection error. (Error message: '.$e.') Repeating call.<br />';
					flush(); ob_flush();
					sleep(3);
					continue;
				}

				//echo "likes ".count($likes["data"])."<br />";

				$stepout = true;
				foreach($likes["data"] as $like) {
					if(!isset($tmplikes[$like["id"]])) {
						$tmplikes[$like["id"]] = $like["name"];
						$stepout = false;
					}
				}

				$tmphash = $likes["paging"]["cursors"]["after"];

				//echo "dig:" . count($tmplikes) . "<br />"; flush(); ob_flush();

				if($stepout == true) {
					$run = false;
				}
			}


			// -- digg into comments
			$tmpcomments = array();
			$run = true;
			$comment_likes = 0;
			while($run == true) {

				$query = '/'.$postid.'/comments?limit=100&fields=created_time,message,like_count,from,comments.limit(100)&filter=toplevel&after=' . $tmphash;

				//echo "dig:" . count($tmpcomments) . " ".$query."<br />"; flush(); ob_flush();

				try {
					$comments = $facebook->api($query);
				} catch (Exception $e) {
					echo 'API connection error. (Error message: '.$e.') Repeating call.<br />';
					flush(); ob_flush();
					sleep(3);
					continue;
				}

				//echo "comments ".count($comments["data"])."<br />";

				$stepout = true;
				foreach($comments["data"] as $comment) {
					if(!isset($tmpcomments[$comment["id"]])) {
						$comment_likes += $comment["like_count"];
						$tmpcomments[$comment["id"]] = $comment;
						$stepout = false;
					}
				}

				$tmphash = $comments["paging"]["cursors"]["after"];

				// echo "dig:" . count($tmpcomments) . "<br />"; flush(); ob_flush();

				if($stepout == true) {
					$run = false;
				}
			}

			//print_r($tmpcomments);

			// -- process comment thread structure
			$proccomments = array();
			$replies = 0;
			foreach($tmpcomments as $comment) {

				$comment["is_reply"] = 0;

				if(isset($comment["comments"])) {

					foreach($comment["comments"]["data"] as $reply) {
						$reply["is_reply"] = 1;
						$proccomments[$reply["id"]] = $reply;
						$replies++;
					}

					$comment["comments"] = "";
				}

				$proccomments[$comment["id"]] = $comment;
			}

			unset($tmpcomments);

			$post["likes_count_fb"] = $postids[$i]["like_count"];
			$post["likes"] = $tmplikes;
			$post["likes_retrieved"] = count($tmplikes);
			$post["comments"] = $proccomments;
			$post["comments_all"] = count($proccomments);
			$post["comments_base"] = count($proccomments) - $replies;
			$post["comments_replies"] = $replies;
			$post["comment_likes_count"] = $comment_likes;



			// -- process post
			$tsv = "";
			$comments_tsv = "";
			$msg = (isset($post["message"])) ? $post["message"]:$post["story"];
			$msg = preg_replace("/[,\"\']/","_",$msg);
			$msg = preg_replace("/[\n\r\t]/"," ",$msg);
			$from = ($post["from"]["id"] == $pid) ? "post_page_" . $pid :  "post_user_" . $pid;
			if ($post["shares"]["count"] == "") { $post["shares"]["count"] = 0; }

			//print_r($post); exit;

			$nodes[$post["id"]] = array(
				"type" => $from,
				"type_post" => $post["type"],
				"label" => $msg,
				"created_time" => $post["created_time"],
				//"created_time_unix" => strtotime($post["created_time"]),
				"likes" => $post["likes_retrieved"],
				"likes_count_fb" => $post["likes_count_fb"],
				"comments_all" => $post["comments_all"],
				"comments_base" => $post["comments_base"],
				"comments_replies" => $post["comments_replies"],
				"shares" => $post["shares"]["count"],
				"comment_likes" => $post["comment_likes_count"],
				"engagement" => ($post["likes_retrieved"] + $post["comments_all"] + $post["shares"]["count"] + $post["comment_likes_count"]),
				"post_link" => $post["actions"][0]["link"]
			);

			// -- process likes
			foreach($post["likes"] as $lid => $lname) {
				if(!isset($nodes[$lid])) {
					$nodes[$lid] = array(
						"type" => "user",
						"type_post" => "user",
						"label" => preg_replace("/[,\"\']/","_",$lname),
						"likes" => 1,
						"comments_all" => 0,
						"comments_base" => 0,
						"comments_replies" => 0,
						"engagement" => 1
					);
				} else {
					$nodes[$lid]["engagement"]++;
					$nodes[$lid]["likes"]++;
				}

				if(!isset($edges[$lid."_XXX_".$post["id"]])) {
					$edges[$lid."_XXX_".$post["id"]] = 1;
				} else {
					$edges[$lid."_XXX_".$post["id"]]++;
				}
			}

			// -- process comments
			foreach($post["comments"] as $cid => $comment) {

				$comment["message"] = preg_replace("/\s+/"," ",$comment["message"]);
				$comment_by = ($anon) ? sha1($comment["from"]["id"]):$comment["from"]["id"];
				$post_by = ($anon) ? sha1($post["from"]["id"]):$post["from"]["id"];
				$comments_tsv .= $post["id"]."\t".$post_by."\t".$msg."\t".$post["created_time"]."\t".$comment["id"]."\t".$comment_by."\t".$comment["is_reply"]."\t".
								$comment["message"]."\t".$comment["created_time"]."\t".$comment["like_count"] . "\n";
				//$comments_tsv = "post_id\tpost_by\tpost_text\tpost_published\tcomment_id\tcomment_by\tis_reply\tcomment_message\tcomment_published\tcomment_like_count\n";

				if(!isset($nodes[$comment["from"]["id"]])) {
					$nodes[$comment["from"]["id"]] = array(
						"type" => "user",
						"type_post" => "user",
						"label" => preg_replace("/[,\"\']/","_",$comment["from"]["name"]),
						"likes" => 0,
						"comments_all" => 0,
						"comments_base" => 0,
						"comments_replies" => 0,
						"engagement" => 0
					);
				}

				$nodes[$comment["from"]["id"]]["engagement"]++;
				$nodes[$comment["from"]["id"]]["comments_all"]++;
				$nodes[$comment["from"]["id"]]["comments_base"] += 1 - $comment["is_reply"];
				$nodes[$comment["from"]["id"]]["comments_replies"] += $comment["is_reply"];


				if(!isset($edges[$comment["from"]["id"]."_XXX_".$post["id"]])) {
					$edges[$comment["from"]["id"]."_XXX_".$post["id"]] = 1;
				} else {
					$edges[$comment["from"]["id"]."_XXX_".$post["id"]]++;
				}
			}

			preg_match_all('/\/\/(.*?)(\/|$)/i', $post["link"], $tmp);
			$domain = preg_replace("/www./","",$tmp[1][0]);

			// $tsv = "type\tby\tpost_message\tpicture\tlink\tlink_domain\tpost_published\tpost_published_alt\tpost_published_alt\tlikes\tlikes_count_fb\tcomments_all\tcomments_base\tcomments_replies\tshares\tcomment_likes\tengagement\tpost_id\tpost_link\n";
			$tsv .= $nodes[$post["id"]]["type_post"]."\t".$from."\t".$nodes[$post["id"]]["label"]."\t".$post["picture"]."\t".
					$post["link"]."\t".$domain."\t".$nodes[$post["id"]]["created_time"]."\t".strtotime($nodes[$post["id"]]["created_time"])."\t".date("Y-m-d H:i:s",strtotime($nodes[$post["id"]]["created_time"]))."\t".
					$nodes[$post["id"]]["likes"]."\t".$nodes[$post["id"]]["likes_count_fb"]."\t".$nodes[$post["id"]]["comments_all"]."\t".$nodes[$post["id"]]["comments_base"]."\t".
					$nodes[$post["id"]]["comments_replies"]."\t".$nodes[$post["id"]]["shares"]."\t".$nodes[$post["id"]]["comment_likes"]."\t".$nodes[$post["id"]]["engagement"]."\t".
					$post["id"]."\t".$nodes[$post["id"]]["post_link"]."\n";


			file_put_contents($filename_tsv, $tsv, FILE_APPEND);
			file_put_contents($filename_comments_tsv, $comments_tsv, FILE_APPEND);

			$tmp_edges = "";
			foreach($edges as $key => $edge) {
				$tmp = explode("_XXX_", $key);
				$tmp_edges .= ($anon) ? "\n".sha1($tmp[0]).",".sha1($tmp[1]).",".$edge : "\n".$tmp[0].",".$tmp[1].",". $edge;
				$edgecounter += $edge;
			}

			file_put_contents($filename_tmp_edges, $tmp_edges, FILE_APPEND);

			// free up some memory
			unset($tsv);
			unset($comments_tsv);
			unset($tmp_edges);
			unset($post);
			$edges = array();

			$j++;
		}
	}

	$pcount = count($postids);
	echo "<br />retrieving infos for " . (count($nodes) - count($postids)) . " users. "; flush(); ob_flush();

	echo "memory footprint: " . memory_get_usage(true) . "/";
	unset($postids);
	echo "<br />";
	flush(); ob_flush();

	// --- retrieving locale and sex for all users ---

	$counter = 0;
	$tmpnodes = array();

	$keys = array_keys($nodes);
	for ($i = 0; $i < count($keys); $i++) {
	//foreach($nodes as $key => $value) {

		$key = $keys[$i];

		$nodes[$key]["locale"] = "";
		$nodes[$key]["sex"] = "";

		if(!preg_match("/post_/",$nodes[$key]["type"])) {

			$tmpnodes[$key] = "uid='" . $key . "'";
			$counter++;

			if(count($tmpnodes) == 250 || count($nodes) - $pcount - $counter == 0) {

				$in = true;

				while($in == true) {

					$in = false;

					$query = "SELECT locale,sex,uid FROM user WHERE " . implode(" OR ", $tmpnodes);

					try {
						$tmpusers = $facebook->api(array(
							'method' => 'fql.query',
							'query' => $query,
							'callback' => ''
						));
			    	} catch (Exception $e) {
			     		echo 'API error. (Error message: '.$e.') Repeating call.<br />';
						$in = true;
						sleep(3);
						continue;
					}

					echo $counter . " "; flush(); ob_flush();

					foreach($tmpusers as $tmpuser) {
						$nodes[$tmpuser["uid"]]["locale"] = $tmpuser["locale"];
						$nodes[$tmpuser["uid"]]["sex"] = $tmpuser["sex"];
					}

					$tmpnodes = array();

					sleep(0.2);
				}
			}
		}
	}



	// --- retrieving locale and sex for all users ---

	/*

	$counter = 0;
	$notcounter = 0;
	$tmpnodes = array();
	$tmpkeys = array();

	foreach($nodes as $key => $value) {

		$nodes[$key]["locale"] = "";
		$nodes[$key]["sex"] = "";

		if(!preg_match("/post_/",$value["type"])) { $tmpkeys[] = $key; }
	}


	echo "<br />retrieving infos for " . count($tmpkeys) . " users: "; flush(); ob_flush();

	for($i = 0; $i < count($tmpkeys); $i++) {

		$key = $tmpkeys[$i];
		$node = $nodes[$key];

		$tmpnodes[$key] = "uid='" . $key . "'";

		if($i % 100 == 0 || $i == (count($tmpkeys) - 1)) {

			$query = "SELECT locale,sex,uid FROM user WHERE " . implode(" OR ", $tmpnodes);

			try {
				$tmpusers = $facebook->api(array(
					'method' => 'fql.query',
					'query' => $query,
					'callback' => ''
				));
	    	} catch (Exception $e) {
	     		echo 'API connection timeout. (Error message: '.$e.') Repeating call.<br />';
				$i = $i - 100;																	// if call fails, need to go back to last $i
				continue;
			}

			echo $i . " "; flush(); ob_flush();

			foreach($tmpusers as $tmpuser) {
				$nodes[$tmpuser["uid"]]["locale"] = $tmpuser["locale"];
				$nodes[$tmpuser["uid"]]["sex"] = $tmpuser["sex"];
			}

			$tmpnodes = array();

			sleep(0.2);
		}
	}
	 */
	 
	 
	// --- likes per country ---
	
	$in = true;

	while($in == true) {

		$query = '/'.$pid.'?fields=insights';

		try {
			$res = $facebook->api($query);
		} catch (Exception $e) {
			echo 'API connection error. (Error message: '.$e.') Repeating call.<br />';
			flush(); ob_flush();
			sleep(3);
			continue;
		}

		$in = false;

		$percountry = $res["insights"]["data"][0]["values"][count($res["insights"]["data"][0]["values"])-1];
		
		$country_tsv = "country\tcount (".$percountry["end_time"].")\n";
		
		foreach($percountry["value"] as $key => $val) {
			$country_tsv .= $key ."\t" . $val . "\n";
		}
		
		file_put_contents($filename_country_tsv, $country_tsv);
	}



	// --- generate output ---

	// $tsv = "type\tby\tpost_message\tpicture\tlink\tlink_domain\tpost_published\tpost_published_unix\tlikes\tlikes_count_fb\tcomments_all\tcomments_base\tcomments_replies\tshares\tcomment_likes\tengagement\tpost_id\tpost_link\n";
	$nodecounter = 0;
	$content = "nodedef>name VARCHAR,label VARCHAR,type VARCHAR,type_post VARCHAR,post_published VARCHAR,post_published_unix INT,user_locale VARCHAR,user_sex VARCHAR,".
			   "likes INT,likes_count_fb INT,comments_all INT,comments_base INT,comments_replies INT,comment_likes INT,shares INT,engagement INT,post_id VARCHAR,post_link VARCHAR\n";

	foreach($nodes as $key => $node) {

		if($anon) {
			$name = sha1($key);
			$label = (preg_match("/post_/",$node["type"])) ? $node["label"] : "user_".$key;
		} else {
			$name = $key;
			$label = $node["label"];
		}

		$label  = preg_replace("/\t/", " ", $label);
		$post_id = (preg_match("/post_/",$node["type"])) ? $key:"";

		$content .= $name.",".$label.",".$node["type"].",".$node["type_post"].",".$node["created_time"].",".$node["created_time_unix"].",".$node["locale"].",".$node["sex"].",".
					$node["likes"].",".$node["likes_count_fb"].",".$node["comments_all"].",".$node["comments_base"].",".$node["comments_replies"].",".$node["comment_likes"].",".
					$node["shares"].",".$node["engagement"].",".$post_id.",".$node["post_link"]."\n";

		$nodecounter++;
	}

	$content .= "edgedef>node1 VARCHAR,node2 VARCHAR,weight INT";
	unset($nodes);
	$content .= file_get_contents($filename_tmp_edges);
	file_put_contents($filename, $content, FILE_APPEND);
	//file_put_contents($filename_tsv, $tsv); 						moved to streamed writing
	//file_put_contents($filename_comments_tsv, $comments_tsv);

	echo '<h2>download</h2>';

	echo '<p>extracted data from ' . $pcount . ' posts, with ' . ($nodecounter - $pcount) . ' users liking or commenting ' . $edgecounter . ' times</p>';

	$files = array($filename,$filename_tsv,$filename_comments_tsv,$filename_country_tsv);

	zipit($fn,$files);
	logit($filename,$clientip,count($friendnames));

	/*
	echo '<p>Your <a href="'.$filename.'">gdf file</a> (right click, save as...).</p>';
	echo '<p>Your <a href="'.$filename_tsv.'">tsv stat file</a> (right click, save as...).</p>';
	echo '<p>Your <a href="'.$filename_comments_tsv.'">tsv comments file</a> (right click, save as...).</p>';
	echo '<p><b>Attention: some browsers add a .txt extension to the files, which must be removed after saving. When in doubt, use Firefox.</b></p>';
	*/
}

?>