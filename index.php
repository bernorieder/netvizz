<?php

// create a .gdf from personal network, group or page
// http://apps.facebook.com/netvizz/

// written by Bernhard Rieder (rieder@uva.nl)
// use freely for research purposes

header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

require './ini.php';				// contains $appid and $secret
require './src/facebook.php';		// FB PHP-SDK

$modules = array("personal","personallike","groups","pagelike","pages");	// every module needs to be registered here for basic hacking protection

ignore_user_abort(false);
set_time_limit(3600*5);
ini_set("memory_limit","8500M");
ini_set("error_reporting",1);

$facebook = new Facebook(array(
  'appId'  => $appid,
  'secret' => $secret
));

$user_id = $facebook->getUser();

if (isset($_SERVER["REMOTE_ADDR"]))    {
	$clientip = $_SERVER["REMOTE_ADDR"];
} else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))    {
	$clientip = $_SERVER["HTTP_X_FORWARDED_FOR"];
} else if (isset($_SERVER["HTTP_CLIENT_IP"]))    {
	$clientip = $_SERVER["HTTP_CLIENT_IP"];
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>netvizz</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="facebook.css" rel="stylesheet" type="text/css" />
</head>

<body class="fbbody">

<h1>netvizz v1.03</h1>

<?php

if($user_id != "529065125") {
	//echo "due to resource constaints, netvizz is currently not available. please check back tomorrow"; exit;
}

$nowdate = date("Y_m_d_H_i_s");


// +++++ try to minimize token timeout by always getting the latest token +++++

if(!$user_id) {

	$and = "";
	
	if(isset($_GET["module"])) {
		$and = http_build_query($_GET,'','&');
	}
	$and = "?".$and;
	//print_r($_GET); exit;

	$url = $facebook->getLoginUrl(array('scope' => 'user_status,user_groups,friends_likes,user_likes,read_stream,read_insights','redirect_uri' => 'https://apps.facebook.com/netvizz/'.$and));
	echo "<script type=\"text/javascript\">parent.location.href = '$url';</script>";
	exit;

} else {

	$_SESSION['access_token'] = $facebook->getAccessToken();

	try {
		$graph_url = "https://graph.facebook.com/oauth/access_token?";
		$graph_url .= "client_id=".$appid;
		$graph_url .= "&client_secret=".$secret;
		$graph_url .= "&grant_type=fb_exchange_token";
		$graph_url .= "&fb_exchange_token=".$_SESSION['access_token'];

		$response = @file_get_contents($graph_url);
		$params = null;
		parse_str($response, $params);

		$_SESSION['access_token'] = $params['access_token'];
		//echo $_SESSION['access_token'] . "<br/><br/>";

		$facebook->setAccessToken($_SESSION['access_token']);
		//echo $facebook->getAccessToken();

	} catch (Exception $e) {

		echo "token request failed: " . $e;
	}
}



// +++++ check whether user has given the full permissions +++++

try {
	$permissions = $facebook->api('/me/permissions');
} catch (Exception $e) {
	echo "Error: " . $e;
	if(preg_match("/Application request limit reached/", $e)) {
		echo "<br />Netvizz is out of API call credits. Too many users have been using this tool too hard. Try again at a later time.";
	}
	exit;
}

$perms = $permissions["data"][0];

if($perms['user_status'] != 1 || $perms['user_groups'] != 1 || $perms['friends_likes'] != 1 || $perms['user_likes'] != 1 || $perms['read_stream'] != 1 || $perms['read_insights'] != 1) {

	echo "<p>This application has features that require extended permission to access data. It won't work without them.</p>";

	$url = $facebook->getLoginUrl(array('scope' => 'user_status,user_groups,friends_likes,user_likes,read_stream,read_insights','redirect_uri' => 'https://apps.facebook.com/netvizz/'));
	echo "<script type=\"text/javascript\">parent.location.href = '$url';</script>";
	exit;
}



// +++++ module selection screen +++++

if(!isset($_GET["module"])) {

	echo '<p>Netvizz is a tool that extracts data from different sections of the Facebook platform (personal profile, groups, pages) for research purposes. File outputs
	can be easily analyzed in standard software.</p>

	<p>For questions, please consult the <a href="https://lab.digitalmethods.net/~brieder/facebook/netvizz/faq.php">FAQ</a> and <a href="https://lab.digitalmethods.net/~brieder/facebook/netvizz/privacy.php">privacy</a> sections. Non-commercial use only.</p>

	<p><b>New:</b> there is now an <a href="http://youtu.be/XxH0Tm8NXik" target="_blank">overview video</a> that introduces the different modules and other things to consider.</p>

	<p>Big networks may take some time to process. <b>Be patient and try not to reload!</b></p>

	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
		Developing and hosting netvizz costs time and money. If the tool is useful for you, please consider to
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="hosted_button_id" value="DVF49JX2TMUXA">
		<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" vspace="4" hspace="4" align="middle" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
		<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
	</form>

	<p>The following modules are currently available:</p>

	<div>
		<a href="index.php?module=personal">personal network</a> - extracts your friends and the friendship connections between them
	</div>
	<div>
		<a href="index.php?module=personallike">personal like network</a> - creates a network that combines your friends and the objects they liked in a bipartite graph
	</div>
	<div>
		<a href="index.php?module=groups">group data</a> - creates networks and tabular files for both friendships and interactions in groups
	</div>
	<div>
		<a href="index.php?module=pagelike">page like network</a> - creates a network of pages connected through the likes between them
	</div>
	<div>
		<a href="index.php?module=pages">page data</a> - creates networks and tabular files for user activity around posts on pages
	</div>';

	echo '<hr class="fbcontentdivider" />
	<br/>1.03 - 14.01.2015 - added like stats per country to page module
	<br/>1.02 - 31.12.2014 - fixed bug in like network (was limited to 100 likes per page), added stat only feature to page module
	<br/>1.01 - 03.07.2014 - added date range selector to page module
	<br/>1.0 - 24.01.2014 - big refactoring, move to module structure, bugfixes and optimizations, file field descriptions
	<br/>0.93 - 20.10.2013 - Like network changes: fixing call limit, removing location, adding liked object category and full likecount
	<br/>0.92 - 03.10.2013 - Various changes to lower load on the API
	<br/>0.91 - 03.10.2013 - Bugfixes for pages
	<br/>0.9 - 07.07.2013 - Added page like network feature (<a href="http://thepoliticsofsystems.net/2013/07/scrutinizing-a-network-of-likes-on-facebook-and-some-thoughts-on-network-analysis-and-visualization/">blog post</a>), new page engine (with threaded comments), maintainance
	<br/>0.84 - 18.05.2013 - Added "link" field to page output, moved to new server
	<br/>0.83 - 04.05.2013 - Retrieved comments for pages limited to 800 per post because of API changes
	<br/>0.82 - 12.04.2013 - Optimizations, smaller bugfixes
	<br/>0.81 - 23.03.2013 - All group data anonymized, pages local bug fixed
	<br/>0.8 - 15.03.2013 - Comment extraction for pages, minor bugfixes
	<br/>0.73 - 01.03.2013 - Bug fixes for ego network counts, dynamic request frame sizing for handling very large pages, donate button
	<br/>0.72 - 15.01.2013 - Page feature now gets both page and user posts
	<br/>0.71 - 30.12.2012 - More maintainance
	<br/>0.7 - 14.12.2012 - Maintainance, housekeeping, new measures for personal profiles, users are anonymized for page data
	<br/>0.61 - 20.11.2012 - Added group interactions feature
	<br/>0.6 - 23.10.2012 - Added page data feature (<a href="http://thepoliticsofsystems.net/2012/10/new-netvizz-feature-page-networks-and-statistics/">blog post</a>)
	<br/>0.51 - 09.09.2012 - Cosmetic changes
	<br/>0.5 - 08.09.2012 - Added like network feature (<a href="http://thepoliticsofsystems.net/2012/09/new-netvizz-feature-bipartite-like-networks/">blog post</a>)
	<br/>0.44 - 25.05.2012 - Major bug fixed, huge and groups should be much faster now
	<br/>0.43 - 23.05.2012 - Finally moved to new server, https works now
	<br/>0.42 - 30.01.2012 - Group methods updated
	<br/>0.41 - 11.10.2011 - Fixed group permissions
	<br/>0.4 - 29.9.2011 - Moved most things to the graph API
	<p>IP: '.$clientip.' / UID: '.$user_id.'</p>';

} else {

	// anonymization
	$anon = true;

	// load requested module after basic security check
	if(in_array($_GET["module"], $modules)) {
		include "mods/mod_".$_GET["module"].".php";
	} else {
		echo "unregistered module, sorry.";
	}
}



function logit($filename,$clientip,$size) {

	global $user_id;

	$logtext = date('Y-m-d H:i:s') . " " . $size . " " . $user_id . " " . $filename . "\n";
	file_put_contents("access.log", $logtext,FILE_APPEND);
}


function zipit($filename,$files) {

	echo '<p>Compressing files...</p>'; flush(); ob_flush();

	$zip = new ZipArchive();
	$filename = $filename . ".zip";

	if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
    	exit("cannot open <$filename>\n");
	}

	foreach ($files as $file) {
		$cleanfile = preg_replace("/\.\/data\//", "", $file);		// cleaning up the filename to counter uncompress problems (with "." maybe?)
		$zip->addFile($file,$cleanfile);
		echo $cleanfile . "<br />";
	}

	echo '<p>Your files have been generated. ' . $zip->numFiles . ' files were zipped. ';
	echo 'Download the <a href="'.$filename.'">zip archive</a>.</p>';

	echo '<p>For file descriptions, go back to the module page and scroll to the bottom.</p>';

	$zip->close();

	foreach ($files as $file) {
		unlink($file);
	}
}

?>

</body>
</html>