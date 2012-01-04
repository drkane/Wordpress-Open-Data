<?php

/**
 * Used to create an RSS feed of data requested by the user
 *
 * @package WordPressOpenData
 */
function od_display_data($od_object){	$od_data = $od_object->get_data(); // get the data	header("Content-type: application/rss+xml");	$output = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; // xml header	$output .= "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\"\n";	$output .= "\t\txmlns:georss=\"http://www.georss.org/georss\">\n";	$output .= "<channel>\n";	$output .= "\t<atom:link href=\"http://" . htmlentities($_SERVER['SERVER_NAME']) . htmlentities($_SERVER['REQUEST_URI']) . "\" rel=\"self\" type=\"application/rss+xml\" />\n"; // include a link to the page as requested	$output .= "\t<title>". $od_object->get_table_metadata("nicename") ."</title>\n"; // Get the title of the dataset	$output .= "\t<link>" . od_change_datatype("html") . "</link>\n"; // link to the equivalent dataset	$output .= "\t<description>". $od_object->get_table_metadata("description") .". Filters used: "; // Get the description of the dataset	$output .= $od_object->get_filters("text"); // find the filters that have been used (and output them)	$output .= "</description>\n";	$rss_field_title = $od_object->get_field("title","rss"); // get the field used as RSS title	$rss_field_description = $od_object->get_field("description","rss"); // get the field used as RSS description	$rss_field_guid = $od_object->get_field("guid","rss"); // get the field used as RSS guid	$rss_field_timestamp = $od_object->get_field("timestamp","rss"); // get the field used as RSS timestamp (this is needed)	$rss_field_id = $od_object->get_field("id","rss"); // get the field used as RSS id	$geo_field_latlng = $od_object->get_field("latlng","geog"); // get the field used as latitude and longitude	foreach($od_data as $c){		$od_timestamp = $c[$rss_field_timestamp];		$od_timestamp = strtotime($od_timestamp);		$od_timestamp = strftime('%a, %d %b %Y %H:%M:%S GMT',$od_timestamp); // turn the timestamp into an RSS-compatible one		$od_title = trim($c[$rss_field_title]);		$od_desc = trim($c[$rss_field_description]);		$od_guid = trim($c[$rss_field_guid]);		$od_latlong = trim(str_replace(","," ",$c[$geo_field_latlng]));		if($od_object->selected_table==$od_object->default_table){			$od_link = get_bloginfo('url') . "/data/item/" . trim($c[$rss_field_id]);		} else {			$od_link = get_bloginfo('url') . "/" . $od_object->selected_table . "/item/" . trim($c[$rss_field_id]);		}		$output .= "\t<item>\n";		if(strpos($od_title,"&")>0){ // if there are &s in the title then use CDATA			$output .= "\t\t<title><![CDATA[$od_title]]></title>\n";		} else {			$output .= "\t\t<title>$od_title</title>\n";		}		if(strpos($od_desc,"&")>0){ // if there are &s in the description then use CDATA			$output .= "\t\t<description><![CDATA[$od_desc]]></description>\n";		} else {			$output .= "\t\t<description><![CDATA[$od_desc]]></description>\n";		}		$output .= "\t\t<guid isPermaLink=\"false\">$od_guid</guid>\n";		$output .= "\t\t<link>$od_link</link>\n";		$output .= "\t\t<pubDate>$od_timestamp</pubDate>\n";		$output .= "\t\t<georss:point>$od_latlong</georss:point>\n";		$output .= "\t</item>\n";	}	$output .= "</channel>\n";	$output .= "</rss>\n";	return $output;
}

?>