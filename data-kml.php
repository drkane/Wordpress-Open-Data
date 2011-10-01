<?php

// Needs:
// @title of the RSS feed
// @description of the RSS feed
// @timestamp variable for each record
// which variable should be used as a @title for each record
// which variable should be used as a @description for each record
// which variable should be used as a @guid for each record
// how to construct a @link for each record

function od_display_data($od_object){
	$od_data = $od_object->get_data();
	header("Content-type: application/vnd.google-earth.kml+xml");
	$output = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; // xml header
	$output .= "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
	$output .= "<channel>\n";
	$output .= "\t<atom:link href=\"http://" . htmlentities($_SERVER['SERVER_NAME']) . htmlentities($_SERVER['REQUEST_URI']) . "\" rel=\"self\" type=\"application/rss+xml\" />\n";
	$output .= "\t<title>RSS Data Feed</title>\n";
	$output .= "\t<link>http://" . htmlentities($_SERVER['SERVER_NAME']) . "</link>\n";
	$output .= "\t<description>RSS Data Feed</description>\n";
	foreach($data as $c){
		$od_timestamp = $c["Timestamp"];
		$od_timestamp = strtotime($od_timestamp);
		$od_timestamp = strftime('%a, %d %b %Y %H:%M:%S GMT',$od_timestamp);
		$output .= "\t<item>\n";
		if(strpos($c["Organisation"],"&")>0){
			$output .= "\t\t<title><![CDATA[" . trim($c["Organisation"]) . "]]></title>\n";
		} else {
			$output .= "\t\t<title>" . trim($c["Organisation"]) . "</title>\n";
		}
		if(strpos($c["Description"],"&")>0){
			$output .= "\t\t<description><![CDATA[" . trim($c["Description"]) . "]]></description>\n";
		} else {
			$output .= "\t\t<description>" . trim($c["Description"]) . "</description>\n";
		}
		$output .= "\t\t<guid isPermaLink=\"false\">" . $c["id"] . "</guid>\n";
		$output .= "\t\t<link>"."cut/" . $c["name"] . "</link>\n";
		$output .= "\t\t<pubDate>" . $od_timestamp . "</pubDate>\n";
		$output .= "\t</item>\n";
	}
	$output .= "</channel>\n";
	$output .= "</rss>\n";
	$output .= "</output>\n";
	return $output;
}

?>