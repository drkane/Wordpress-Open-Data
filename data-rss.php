<?php

// Needs:
// @title of the RSS feed
// @description of the RSS feed

function od_display_data($od_object){
	$od_data = $od_object->get_data();
	header("Content-type: application/rss+xml");
	$output = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; // xml header
	$output .= "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
	$output .= "<channel>\n";
	$output .= "\t<atom:link href=\"http://" . htmlentities($_SERVER['SERVER_NAME']) . htmlentities($_SERVER['REQUEST_URI']) . "\" rel=\"self\" type=\"application/rss+xml\" />\n";
	$output .= "\t<title>RSS Data Feed</title>\n";
	$output .= "\t<link>" . get_bloginfo('url') . "</link>\n";
	$output .= "\t<description>RSS Data Feed</description>\n";
	$rss_field_title = $od_object->get_rss("title");
	$rss_field_description = $od_object->get_rss("description");
	$rss_field_guid = $od_object->get_rss("guid");
	$rss_field_timestamp = $od_object->get_rss("timestamp");
	$rss_field_id = $od_object->get_rss("id");
	foreach($od_data as $c){
		$od_timestamp = $c[$rss_field_timestamp];
		$od_timestamp = strtotime($od_timestamp);
		$od_timestamp = strftime('%a, %d %b %Y %H:%M:%S GMT',$od_timestamp);
		$od_title = trim($c[$rss_field_title]);
		$od_desc = trim($c[$rss_field_description]);
		$od_guid = trim($c[$rss_field_guid]);
		if($od_object->selected_table==$od_object->default_table){
			$od_link = get_bloginfo('url') . "/item/" . trim($c[$rss_field_id]);
		} else {
			$od_link = get_bloginfo('url') . "/" . $od_object->selected_table . "/item/" . trim($c[$rss_field_id]);
		}
		$output .= "\t<item>\n";
		if(strpos($od_title,"&")>0){
			$output .= "\t\t<title><![CDATA[$od_title]]></title>\n";
		} else {
			$output .= "\t\t<title>$od_title</title>\n";
		}
		if(strpos($od_desc,"&")>0){
			$output .= "\t\t<description><![CDATA[$od_desc]]></description>\n";
		} else {
			$output .= "\t\t<description>$od_desc</description>\n";
		}
		$output .= "\t\t<guid isPermaLink=\"false\">$od_guid</guid>\n";
		$output .= "\t\t<link>$od_link</link>\n";
		$output .= "\t\t<pubDate>$od_timestamp</pubDate>\n";
		$output .= "\t</item>\n";
	}
	$output .= "</channel>\n";
	$output .= "</rss>\n";
	$output .= "</output>\n";
	return $output;
}

?>